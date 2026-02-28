#!/usr/bin/env python3
"""
Audit extracted JSON-LD schema stored in SQLite and report validation issues.

Usage:
  python scripts/audit_schema_from_db.py --db reports/active_schema_YYYYMMDD_HHMMSS.sqlite
"""

from __future__ import annotations

import argparse
import json
import os
import sqlite3
from collections import Counter, defaultdict
from dataclasses import dataclass, asdict
from datetime import datetime, timezone
from typing import Any, Dict, Iterable, List, Optional


def utc_now() -> str:
    return datetime.now(timezone.utc).isoformat(timespec="seconds")


def as_type_list(raw_type: Any) -> List[str]:
    if isinstance(raw_type, list):
        return [str(x) for x in raw_type]
    if isinstance(raw_type, str):
        return [raw_type]
    return []


def has_key(obj: Dict[str, Any], key: str) -> bool:
    return key in obj and obj[key] not in (None, "", [], {})


def ensure_dict(value: Any) -> Optional[Dict[str, Any]]:
    return value if isinstance(value, dict) else None


@dataclass
class Issue:
    severity: str
    code: str
    message: str
    page_url: str
    schema_type: str
    node_id: int
    schema_id: Optional[str]


def validate_address_node(node: Dict[str, Any], page_url: str, schema_type: str, node_id: int, schema_id: Optional[str]) -> List[Issue]:
    issues: List[Issue] = []
    flat_address_keys = {"streetAddress", "addressLocality", "addressRegion", "postalCode"}
    has_flat = any(has_key(node, k) for k in flat_address_keys)
    address = node.get("address")

    if not has_key(node, "address"):
        issues.append(Issue("error", "missing_address", "Missing required 'address' object.", page_url, schema_type, node_id, schema_id))
        if has_flat:
            issues.append(Issue("error", "flat_address_fields", "Address fields are flat; expected nested address object.", page_url, schema_type, node_id, schema_id))
        return issues

    address_obj = ensure_dict(address)
    if address_obj is None:
        issues.append(Issue("error", "address_not_object", "Address is not an object.", page_url, schema_type, node_id, schema_id))
        return issues

    if str(address_obj.get("@type", "")) != "PostalAddress":
        issues.append(Issue("warning", "address_missing_postal_type", "Address object should have @type=PostalAddress.", page_url, schema_type, node_id, schema_id))

    for field in ("streetAddress", "addressLocality", "addressRegion", "postalCode"):
        if not has_key(address_obj, field):
            issues.append(Issue("warning", f"address_missing_{field}", f"Address missing '{field}'.", page_url, schema_type, node_id, schema_id))

    if not has_key(address_obj, "addressCountry"):
        issues.append(Issue("warning", "address_missing_country", "Address missing 'addressCountry'.", page_url, schema_type, node_id, schema_id))

    return issues


def validate_review_node(node: Dict[str, Any], page_url: str, schema_type: str, node_id: int, schema_id: Optional[str]) -> List[Issue]:
    issues: List[Issue] = []
    if has_key(node, "author_name"):
        issues.append(Issue("error", "review_uses_author_name_alias", "Uses 'author_name'; expected nested 'author' object.", page_url, schema_type, node_id, schema_id))
    if has_key(node, "itemReviewed_name"):
        issues.append(Issue("error", "review_uses_itemReviewed_name_alias", "Uses 'itemReviewed_name'; expected nested 'itemReviewed' object.", page_url, schema_type, node_id, schema_id))

    if not has_key(node, "author"):
        issues.append(Issue("error", "review_missing_author", "Review missing required 'author'.", page_url, schema_type, node_id, schema_id))
    else:
        author = ensure_dict(node.get("author"))
        if author is None:
            issues.append(Issue("warning", "review_author_not_object", "Review author should be an object.", page_url, schema_type, node_id, schema_id))
        else:
            if not has_key(author, "name"):
                issues.append(Issue("warning", "review_author_missing_name", "Review author object missing 'name'.", page_url, schema_type, node_id, schema_id))

    if not has_key(node, "itemReviewed"):
        issues.append(Issue("error", "review_missing_itemReviewed", "Review missing required 'itemReviewed'.", page_url, schema_type, node_id, schema_id))
    else:
        item = ensure_dict(node.get("itemReviewed"))
        if item is None:
            issues.append(Issue("warning", "review_itemReviewed_not_object", "itemReviewed should be an object.", page_url, schema_type, node_id, schema_id))
        else:
            if not has_key(item, "name"):
                issues.append(Issue("warning", "review_itemReviewed_missing_name", "itemReviewed object missing 'name'.", page_url, schema_type, node_id, schema_id))
    return issues


def validate_service_node(node: Dict[str, Any], page_url: str, schema_type: str, node_id: int, schema_id: Optional[str]) -> List[Issue]:
    issues: List[Issue] = []
    if has_key(node, "provider_name"):
        issues.append(Issue("error", "service_uses_provider_name_alias", "Uses 'provider_name'; expected nested 'provider' object.", page_url, schema_type, node_id, schema_id))
    if not has_key(node, "provider"):
        issues.append(Issue("error", "service_missing_provider", "Service missing required 'provider'.", page_url, schema_type, node_id, schema_id))
    else:
        provider = ensure_dict(node.get("provider"))
        if provider is None:
            issues.append(Issue("warning", "service_provider_not_object", "Service provider should be an object.", page_url, schema_type, node_id, schema_id))
        else:
            if not has_key(provider, "name"):
                issues.append(Issue("warning", "service_provider_missing_name", "Service provider object missing 'name'.", page_url, schema_type, node_id, schema_id))
    return issues


def validate_event_node(node: Dict[str, Any], page_url: str, schema_type: str, node_id: int, schema_id: Optional[str]) -> List[Issue]:
    issues: List[Issue] = []
    if not has_key(node, "startDate"):
        issues.append(Issue("error", "event_missing_startDate", "Event missing required 'startDate'.", page_url, schema_type, node_id, schema_id))
    if not has_key(node, "location"):
        issues.append(Issue("error", "event_missing_location", "Event missing required 'location'.", page_url, schema_type, node_id, schema_id))
    if not has_key(node, "eventStatus"):
        issues.append(Issue("warning", "event_missing_eventStatus", "Event missing 'eventStatus'.", page_url, schema_type, node_id, schema_id))
    if not has_key(node, "eventAttendanceMode"):
        issues.append(Issue("warning", "event_missing_eventAttendanceMode", "Event missing 'eventAttendanceMode'.", page_url, schema_type, node_id, schema_id))
    return issues


def validate_course_node(node: Dict[str, Any], page_url: str, schema_type: str, node_id: int, schema_id: Optional[str]) -> List[Issue]:
    issues: List[Issue] = []
    offers = node.get("offers")
    if isinstance(offers, list):
        for idx, offer in enumerate(offers):
            if isinstance(offer, dict) and not has_key(offer, "@type"):
                issues.append(Issue("error", "course_offer_missing_type", f"Course offers[{idx}] missing @type.", page_url, schema_type, node_id, schema_id))
    elif isinstance(offers, dict):
        if not has_key(offers, "@type"):
            issues.append(Issue("error", "course_offer_missing_type", "Course offers object missing @type.", page_url, schema_type, node_id, schema_id))
    return issues


def validate_itemlist_node(node: Dict[str, Any], page_url: str, schema_type: str, node_id: int, schema_id: Optional[str]) -> List[Issue]:
    issues: List[Issue] = []
    items = node.get("itemListElement")
    if isinstance(items, list):
        for idx, item in enumerate(items):
            if isinstance(item, dict) and not has_key(item, "@type"):
                issues.append(Issue("error", "itemlist_element_missing_type", f"itemListElement[{idx}] missing @type.", page_url, schema_type, node_id, schema_id))
    elif isinstance(items, dict):
        if not has_key(items, "@type"):
            issues.append(Issue("error", "itemlist_element_missing_type", "itemListElement object missing @type.", page_url, schema_type, node_id, schema_id))
    return issues


def validate_node(node: Dict[str, Any], page_url: str, node_id: int) -> List[Issue]:
    issues: List[Issue] = []
    types = as_type_list(node.get("@type"))
    schema_id = str(node.get("@id")) if node.get("@id") is not None else None

    if not types:
        issues.append(Issue("error", "missing_type", "Schema node missing @type.", page_url, "(unknown)", node_id, schema_id))
        return issues

    for schema_type in types:
        if schema_type in ("LocalBusiness", "ChildCare"):
            issues.extend(validate_address_node(node, page_url, schema_type, node_id, schema_id))
            if not has_key(node, "name"):
                issues.append(Issue("warning", "missing_name", f"{schema_type} missing 'name'.", page_url, schema_type, node_id, schema_id))
            if not has_key(node, "telephone"):
                issues.append(Issue("warning", "missing_telephone", f"{schema_type} missing 'telephone'.", page_url, schema_type, node_id, schema_id))
            if schema_type == "LocalBusiness" and not has_key(node, "geo"):
                issues.append(Issue("warning", "missing_geo", "LocalBusiness missing 'geo'.", page_url, schema_type, node_id, schema_id))
        elif schema_type == "Review":
            issues.extend(validate_review_node(node, page_url, schema_type, node_id, schema_id))
        elif schema_type == "Service":
            issues.extend(validate_service_node(node, page_url, schema_type, node_id, schema_id))
        elif schema_type == "Event":
            issues.extend(validate_event_node(node, page_url, schema_type, node_id, schema_id))
        elif schema_type == "Course":
            issues.extend(validate_course_node(node, page_url, schema_type, node_id, schema_id))
        elif schema_type == "ItemList":
            issues.extend(validate_itemlist_node(node, page_url, schema_type, node_id, schema_id))

    return issues


def build_markdown(report: Dict[str, Any]) -> str:
    lines: List[str] = []
    lines.append("# Schema Validation Audit")
    lines.append("")
    lines.append(f"- Generated: {report['generated_at']}")
    lines.append(f"- Source DB: `{report['db_path']}`")
    lines.append(f"- Pages scanned: {report['pages_total']}")
    lines.append(f"- Schema nodes scanned: {report['schema_nodes_total']}")
    lines.append(f"- Parse error blocks: {report['parse_error_blocks']}")
    lines.append(f"- Error issues: {report['error_count']}")
    lines.append(f"- Warning issues: {report['warning_count']}")
    lines.append("")

    lines.append("## Top Error Codes")
    lines.append("")
    lines.append("| Code | Count |")
    lines.append("|---|---:|")
    for code, count in report["top_error_codes"]:
        lines.append(f"| `{code}` | {count} |")
    lines.append("")

    lines.append("## Top Warning Codes")
    lines.append("")
    lines.append("| Code | Count |")
    lines.append("|---|---:|")
    for code, count in report["top_warning_codes"]:
        lines.append(f"| `{code}` | {count} |")
    lines.append("")

    lines.append("## Most Affected Pages")
    lines.append("")
    lines.append("| URL | Issues |")
    lines.append("|---|---:|")
    for url, count in report["top_pages"]:
        lines.append(f"| {url} | {count} |")
    lines.append("")

    lines.append("## Issues by Schema Type")
    lines.append("")
    lines.append("| Schema Type | Issues |")
    lines.append("|---|---:|")
    for schema_type, count in report["issues_by_schema_type"]:
        lines.append(f"| `{schema_type}` | {count} |")
    lines.append("")

    lines.append("## Parse Errors")
    lines.append("")
    if report["parse_error_examples"]:
        for item in report["parse_error_examples"]:
            lines.append(f"- `{item['page_url']}`: {item['message']}")
    else:
        lines.append("- None")
    lines.append("")

    lines.append("## Sample Validation Issues")
    lines.append("")
    for sample in report["sample_issues"]:
        lines.append(
            f"- [{sample['severity'].upper()}] `{sample['code']}` on `{sample['schema_type']}` "
            f"at `{sample['page_url']}`: {sample['message']}"
        )
    lines.append("")
    return "\n".join(lines)


def main() -> int:
    parser = argparse.ArgumentParser(description="Audit schema nodes from a SQLite snapshot.")
    parser.add_argument("--db", required=True, help="Path to SQLite DB produced by download_active_schema.py")
    parser.add_argument("--out-json", default="", help="Output JSON report path")
    parser.add_argument("--out-md", default="", help="Output markdown report path")
    args = parser.parse_args()

    db_path = args.db
    if not os.path.isfile(db_path):
        raise SystemExit(f"DB not found: {db_path}")

    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    out_json = args.out_json or f"reports/schema_validation_audit_{timestamp}.json"
    out_md = args.out_md or f"reports/schema_validation_audit_{timestamp}.md"
    os.makedirs(os.path.dirname(out_json) or ".", exist_ok=True)
    os.makedirs(os.path.dirname(out_md) or ".", exist_ok=True)

    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row

    pages_total = conn.execute("SELECT COUNT(*) FROM pages").fetchone()[0]
    schema_nodes_total = conn.execute("SELECT COUNT(*) FROM schema_nodes").fetchone()[0]

    issues: List[Issue] = []
    page_issue_count: Counter[str] = Counter()
    schema_issue_count: Counter[str] = Counter()
    code_error_count: Counter[str] = Counter()
    code_warning_count: Counter[str] = Counter()

    # Parse errors from extracted blocks.
    parse_error_rows = conn.execute(
        """
        SELECT p.url AS page_url, sb.parse_error
        FROM schema_blocks sb
        JOIN pages p ON p.id = sb.page_id
        WHERE sb.parse_ok = 0
        """
    ).fetchall()
    for row in parse_error_rows:
        issue = Issue(
            severity="error",
            code="jsonld_parse_error",
            message=str(row["parse_error"]),
            page_url=str(row["page_url"]),
            schema_type="(block)",
            node_id=0,
            schema_id=None,
        )
        issues.append(issue)
        page_issue_count[issue.page_url] += 1
        schema_issue_count[issue.schema_type] += 1
        code_error_count[issue.code] += 1

    # Validate each node.
    rows = conn.execute(
        """
        SELECT sn.id AS node_id, sn.schema_json, p.url AS page_url
        FROM schema_nodes sn
        JOIN pages p ON p.id = sn.page_id
        """
    ).fetchall()

    for row in rows:
        node_id = int(row["node_id"])
        page_url = str(row["page_url"])
        try:
            node = json.loads(row["schema_json"])
        except json.JSONDecodeError as exc:
            issue = Issue(
                severity="error",
                code="node_json_parse_error",
                message=str(exc),
                page_url=page_url,
                schema_type="(node)",
                node_id=node_id,
                schema_id=None,
            )
            node_issues = [issue]
        else:
            node_issues = validate_node(node, page_url, node_id)

        for issue in node_issues:
            issues.append(issue)
            page_issue_count[issue.page_url] += 1
            schema_issue_count[issue.schema_type] += 1
            if issue.severity == "error":
                code_error_count[issue.code] += 1
            else:
                code_warning_count[issue.code] += 1

    error_count = sum(1 for i in issues if i.severity == "error")
    warning_count = sum(1 for i in issues if i.severity == "warning")

    report = {
        "generated_at": utc_now(),
        "db_path": db_path,
        "pages_total": pages_total,
        "schema_nodes_total": schema_nodes_total,
        "parse_error_blocks": len(parse_error_rows),
        "error_count": error_count,
        "warning_count": warning_count,
        "top_error_codes": code_error_count.most_common(25),
        "top_warning_codes": code_warning_count.most_common(25),
        "top_pages": page_issue_count.most_common(50),
        "issues_by_schema_type": schema_issue_count.most_common(50),
        "parse_error_examples": [
            {"page_url": str(r["page_url"]), "message": str(r["parse_error"])}
            for r in parse_error_rows[:25]
        ],
        "sample_issues": [asdict(i) for i in issues[:100]],
        "issues": [asdict(i) for i in issues],
    }

    with open(out_json, "w", encoding="utf-8") as f:
        json.dump(report, f, indent=2, ensure_ascii=False)

    with open(out_md, "w", encoding="utf-8") as f:
        f.write(build_markdown(report))

    print(f"[schema-audit] db={db_path}")
    print(f"[schema-audit] pages={pages_total} nodes={schema_nodes_total}")
    print(f"[schema-audit] errors={error_count} warnings={warning_count}")
    print(f"[schema-audit] json={out_json}")
    print(f"[schema-audit] md={out_md}")
    conn.close()
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

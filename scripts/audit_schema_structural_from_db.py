#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import os
import re
import sqlite3
from collections import Counter, defaultdict
from dataclasses import asdict, dataclass
from datetime import datetime, timezone
from typing import Any, Dict, List, Optional


VALID_TYPES = {
    "Thing","Action","CreativeWork","Event","Intangible","Organization","Person","Place","Product","Article",
    "BlogPosting","NewsArticle","LocalBusiness","ChildCare","Preschool","EducationalOrganization","Service","Review",
    "AggregateRating","Rating","FAQPage","Question","Answer","HowTo","HowToStep","JobPosting","VideoObject","ImageObject",
    "WebPage","WebSite","BreadcrumbList","ListItem","Offer","PostalAddress","GeoCoordinates","OpeningHoursSpecification",
    "ContactPoint","Course","CourseInstance","Menu","MenuItem","Schedule","EducationalOccupationalCredential","ItemList",
    "CollectionPage","AboutPage","ContactPage","ImageGallery","SpecialAnnouncement","MonetaryAmount","QuantitativeValue","PropertyValue",
}


@dataclass
class Issue:
    severity: str
    code: str
    message: str
    page_url: str
    schema_type: str
    node_id: int
    schema_id: Optional[str]


def p(v: Any) -> bool:
    return v not in (None, "", [], {})


def tlist(v: Any) -> List[str]:
    if isinstance(v, list):
        return [str(x).strip() for x in v if str(x).strip()]
    if isinstance(v, str) and v.strip():
        return [v.strip()]
    return []


def d(v: Any) -> Optional[Dict[str, Any]]:
    return v if isinstance(v, dict) else None


def add(issues: List[Issue], sev: str, code: str, msg: str, url: str, st: str, nid: int, sid: Optional[str]) -> None:
    issues.append(Issue(sev, code, msg, url, st, nid, sid))


def bucket(url: str) -> str:
    if re.match(r"^https://chromaela\.com/locations/[^/]+/?$", url):
        return "location-campus"
    if re.match(r"^https://chromaela\.com/childcare/[^/]+/?$", url):
        return "location-geo"
    if re.match(r"^https://chromaela\.com/programs/[^/]+/?$", url):
        return "program"
    if "taxonomy=location_region" in url:
        return "service-area-taxonomy"
    if url == "https://chromaela.com/" or re.match(r"^https://chromaela\.com/(about|parents|curriculum|contact-us|schedule-a-tour|communities|careers|blog|newsroom|programs|locations)/?$", url):
        return "core"
    return "other"


def validate_node(node: Dict[str, Any], url: str, nid: int, issues: List[Issue]) -> None:
    sid = str(node.get("@id")) if node.get("@id") is not None else None
    if not p(node.get("@type")):
        add(issues, "error", "missing_type", "Missing @type.", url, "(unknown)", nid, sid)
        return
    types = tlist(node.get("@type"))
    if not types:
        add(issues, "error", "invalid_type", "Invalid @type format.", url, "(unknown)", nid, sid)
        return

    for st in types:
        if st not in VALID_TYPES:
            add(issues, "warning", "unknown_schema_type", f"Unknown/non-whitelisted type '{st}'.", url, st, nid, sid)

        if p(node.get("url")) and isinstance(node.get("url"), str) and not re.match(r"^https?://", node.get("url").strip()):
            add(issues, "warning", "invalid_url", f"Invalid URL value: {node.get('url')}", url, st, nid, sid)

        if st in ("LocalBusiness", "ChildCare", "Preschool"):
            addr = node.get("address")
            flat = any(p(node.get(k)) for k in ("streetAddress", "addressLocality", "addressRegion", "postalCode"))
            if not p(addr):
                add(issues, "error", "local_missing_address", "Missing address object.", url, st, nid, sid)
            elif d(addr) is None:
                add(issues, "error", "local_address_not_object", "Address must be object.", url, st, nid, sid)
            else:
                at = str(addr.get("@type", "")).strip()
                if at and at != "PostalAddress":
                    add(issues, "error", "local_address_wrong_type", f"Address @type should be PostalAddress, got '{at}'.", url, st, nid, sid)
                for f in ("streetAddress", "addressLocality", "addressRegion", "postalCode"):
                    if not p(addr.get(f)):
                        add(issues, "warning", f"local_address_missing_{f}", f"Address missing {f}.", url, st, nid, sid)
            if flat:
                add(issues, "warning", "local_flat_address_fields_present", "Flat address keys present at root; prefer nested PostalAddress only.", url, st, nid, sid)

            geo = d(node.get("geo"))
            if geo:
                gt = str(geo.get("@type", "")).strip()
                if gt and gt != "GeoCoordinates":
                    add(issues, "warning", "geo_wrong_type", f"Geo @type should be GeoCoordinates, got '{gt}'.", url, st, nid, sid)
                if not p(geo.get("latitude")) or not p(geo.get("longitude")):
                    add(issues, "warning", "geo_missing_lat_lng", "Geo missing latitude/longitude.", url, st, nid, sid)

        if st == "FAQPage":
            me = node.get("mainEntity")
            if not isinstance(me, list):
                add(issues, "error", "faq_mainEntity_not_array", "FAQPage mainEntity must be array.", url, st, nid, sid)
            else:
                for i, q in enumerate(me):
                    if not isinstance(q, dict):
                        add(issues, "error", "faq_question_not_object", f"mainEntity[{i}] is not object.", url, st, nid, sid)
                        continue
                    qt = str(q.get("@type", "")).strip()
                    if qt and qt != "Question":
                        add(issues, "error", "faq_question_wrong_type", f"mainEntity[{i}] expected Question, got '{qt}'.", url, st, nid, sid)
                    ans = d(q.get("acceptedAnswer"))
                    if ans is None:
                        add(issues, "error", "faq_missing_answer", f"mainEntity[{i}] missing acceptedAnswer object.", url, st, nid, sid)
                    elif not p(ans.get("text")):
                        add(issues, "error", "faq_answer_missing_text", f"mainEntity[{i}] answer missing text.", url, st, nid, sid)

        if st in ("BreadcrumbList", "ItemList"):
            arr = node.get("itemListElement")
            if not p(arr):
                add(issues, "error", f"{st.lower()}_missing_itemListElement", "itemListElement missing.", url, st, nid, sid)
            elif not isinstance(arr, list):
                add(issues, "error", f"{st.lower()}_itemListElement_not_array", "itemListElement must be array.", url, st, nid, sid)
            else:
                for i, it in enumerate(arr):
                    if not isinstance(it, dict):
                        add(issues, "error", f"{st.lower()}_item_not_object", f"itemListElement[{i}] not object.", url, st, nid, sid)
                        continue
                    tt = str(it.get("@type", "")).strip()
                    if tt and tt != "ListItem":
                        add(issues, "warning", f"{st.lower()}_item_wrong_type", f"itemListElement[{i}] expected ListItem got '{tt}'.", url, st, nid, sid)
                    if st == "BreadcrumbList":
                        if not p(it.get("position")):
                            add(issues, "error", "breadcrumb_missing_position", f"Breadcrumb item[{i}] missing position.", url, st, nid, sid)
                        if isinstance(it.get("name"), str) and re.search(r"<[^>]+>", it.get("name")):
                            add(issues, "error", "breadcrumb_name_html", f"Breadcrumb item[{i}] name contains HTML.", url, st, nid, sid)

        if st == "Review":
            if p(node.get("author_name")):
                add(issues, "error", "review_legacy_author_name", "Legacy author_name key detected.", url, st, nid, sid)
            if p(node.get("itemReviewed_name")):
                add(issues, "error", "review_legacy_itemReviewed_name", "Legacy itemReviewed_name key detected.", url, st, nid, sid)

        if st == "Service" and p(node.get("provider_name")):
            add(issues, "error", "service_legacy_provider_name", "Legacy provider_name key detected.", url, st, nid, sid)

        if st == "Course":
            offers = node.get("offers")
            if isinstance(offers, list):
                for i, o in enumerate(offers):
                    if isinstance(o, dict) and not p(o.get("@type")):
                        add(issues, "error", "course_offer_missing_type", f"offers[{i}] missing @type.", url, st, nid, sid)
            elif isinstance(offers, dict) and not p(offers.get("@type")):
                add(issues, "error", "course_offer_missing_type", "offers missing @type.", url, st, nid, sid)


def to_md(rep: Dict[str, Any]) -> str:
    lines = []
    lines.append("# Schema Structural Audit (Option 1)")
    lines.append("")
    lines.append(f"- Generated: {rep['generated_at']}")
    lines.append(f"- DB: `{rep['db_path']}`")
    lines.append(f"- Pages: **{rep['pages_total']}**")
    lines.append(f"- Nodes: **{rep['schema_nodes_total']}**")
    lines.append(f"- Errors: **{rep['error_count']}**")
    lines.append(f"- Warnings: **{rep['warning_count']}**")
    lines.append("")
    lines.append("## HTTP Status")
    lines.append("")
    lines.append("| Status | Count |")
    lines.append("|---|---:|")
    for s, c in rep["status_counts"]:
        lines.append(f"| {s} | {c} |")
    lines.append("")
    lines.append("## By Route Bucket")
    lines.append("")
    lines.append("| Bucket | Errors | Warnings | Pages |")
    lines.append("|---|---:|---:|---:|")
    for r in rep["bucket_rows"]:
        lines.append(f"| `{r['bucket']}` | {r['errors']} | {r['warnings']} | {r['pages']} |")
    lines.append("")
    lines.append("## Error Codes")
    lines.append("")
    lines.append("| Code | Count | Pages |")
    lines.append("|---|---:|---:|")
    for code, count in rep["error_codes"]:
        lines.append(f"| `{code}` | {count} | {rep['error_code_pages'][code]} |")
    lines.append("")
    lines.append("## Warning Codes")
    lines.append("")
    lines.append("| Code | Count | Pages |")
    lines.append("|---|---:|---:|")
    for code, count in rep["warning_codes"]:
        lines.append(f"| `{code}` | {count} | {rep['warning_code_pages'][code]} |")
    lines.append("")
    lines.append("## Full Issue Inventory (Code -> URL)")
    lines.append("")
    for sec, obj in (("Errors", rep["errors_by_code_url"]), ("Warnings", rep["warnings_by_code_url"])):
        lines.append(f"### {sec}")
        lines.append("")
        if not obj:
            lines.append("- None")
            lines.append("")
            continue
        for code in sorted(obj.keys()):
            lines.append(f"#### `{code}`")
            for item in obj[code]:
                lines.append(f"- ({item['count']}) {item['url']}")
            lines.append("")
    lines.append("## Questions For You")
    lines.append("")
    lines.append("1. Should we treat all `unknown_schema_type` warnings as must-remove?")
    lines.append("2. For `location-geo` pages, keep LocalBusiness/ChildCare with complete address or replace with Service/WebPage only?")
    lines.append("3. Should we enforce `addressCountry` as required for all PostalAddress objects?")
    lines.append("4. Should all 13 non-200 sitemap URLs be removed in the same release?")
    lines.append("")
    return "\n".join(lines)


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--db", required=True)
    ap.add_argument("--out-json", required=True)
    ap.add_argument("--out-md", required=True)
    args = ap.parse_args()
    os.makedirs(os.path.dirname(args.out_json) or ".", exist_ok=True)
    os.makedirs(os.path.dirname(args.out_md) or ".", exist_ok=True)

    conn = sqlite3.connect(args.db)
    conn.row_factory = sqlite3.Row
    pages_total = conn.execute("select count(*) from pages").fetchone()[0]
    nodes_total = conn.execute("select count(*) from schema_nodes").fetchone()[0]
    status = conn.execute("select status_code,count(*) c from pages group by status_code order by status_code").fetchall()
    rows = conn.execute("select url,status_code,coalesce(error,'') as error from pages where status_code<>200 order by status_code,url").fetchall()
    parse_rows = conn.execute("select p.url,sb.parse_error from schema_blocks sb join pages p on p.id=sb.page_id where sb.parse_ok=0").fetchall()
    node_rows = conn.execute("select sn.id,sn.schema_json,p.url from schema_nodes sn join pages p on p.id=sn.page_id").fetchall()
    conn.close()

    issues: List[Issue] = []
    for r in rows:
        add(issues, "error", "page_http_non_200", f"HTTP {r['status_code']} during crawl.", str(r["url"]), "(page)", 0, None)
    for r in parse_rows:
        add(issues, "error", "jsonld_parse_error", str(r["parse_error"]), str(r["url"]), "(block)", 0, None)
    for r in node_rows:
        try:
            node = json.loads(r["schema_json"])
        except json.JSONDecodeError as e:
            add(issues, "error", "schema_node_json_parse_error", str(e), str(r["url"]), "(node)", int(r["id"]), None)
            continue
        if not isinstance(node, dict):
            add(issues, "error", "schema_node_not_object", "Schema node is not object.", str(r["url"]), "(node)", int(r["id"]), None)
            continue
        validate_node(node, str(r["url"]), int(r["id"]), issues)

    err = Counter(); warn = Counter(); err_pages = defaultdict(set); warn_pages = defaultdict(set)
    bkt = defaultdict(lambda: {"errors": 0, "warnings": 0, "pages": set()})
    ge = defaultdict(Counter); gw = defaultdict(Counter)
    for i in issues:
        b = bucket(i.page_url); bkt[b]["pages"].add(i.page_url)
        if i.severity == "error":
            err[i.code] += 1; err_pages[i.code].add(i.page_url); bkt[b]["errors"] += 1; ge[i.code][i.page_url] += 1
        else:
            warn[i.code] += 1; warn_pages[i.code].add(i.page_url); bkt[b]["warnings"] += 1; gw[i.code][i.page_url] += 1

    rep = {
        "generated_at": datetime.now(timezone.utc).isoformat(timespec="seconds"),
        "db_path": args.db, "pages_total": pages_total, "schema_nodes_total": nodes_total,
        "error_count": sum(err.values()), "warning_count": sum(warn.values()),
        "status_counts": [(int(r["status_code"]), int(r["c"])) for r in status],
        "error_codes": err.most_common(), "warning_codes": warn.most_common(),
        "error_code_pages": {k: len(v) for k, v in err_pages.items()},
        "warning_code_pages": {k: len(v) for k, v in warn_pages.items()},
        "bucket_rows": [{"bucket": k, "errors": int(v["errors"]), "warnings": int(v["warnings"]), "pages": len(v["pages"])} for k, v in sorted(bkt.items())],
        "errors_by_code_url": {c: [{"url": u, "count": n} for u, n in sorted(m.items(), key=lambda x: (-x[1], x[0]))] for c, m in ge.items()},
        "warnings_by_code_url": {c: [{"url": u, "count": n} for u, n in sorted(m.items(), key=lambda x: (-x[1], x[0]))] for c, m in gw.items()},
        "issues": [asdict(i) for i in issues],
    }
    with open(args.out_json, "w", encoding="utf-8") as f:
        json.dump(rep, f, indent=2, ensure_ascii=False)
    with open(args.out_md, "w", encoding="utf-8") as f:
        f.write(to_md(rep))
    print(f"[schema-structural-audit] pages={pages_total} nodes={nodes_total} errors={rep['error_count']} warnings={rep['warning_count']}")
    print(args.out_json)
    print(args.out_md)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

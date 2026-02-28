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

REQ = {
    "LocalBusiness": ["name", "address"], "ChildCare": ["name", "address"],
    "Article": ["headline", "author", "datePublished"], "BlogPosting": ["headline", "author", "datePublished"],
    "Event": ["name", "startDate", "location"], "FAQPage": ["mainEntity"],
    "JobPosting": ["title", "description", "datePosted", "hiringOrganization"], "Product": ["name"],
    "Review": ["itemReviewed", "author"], "Person": ["name"], "Organization": ["name"],
    "VideoObject": ["name", "description", "thumbnailUrl", "uploadDate"], "HowTo": ["name", "step"],
    "Service": ["name", "provider"], "Course": ["name", "provider"], "BreadcrumbList": ["itemListElement"],
    "AggregateRating": ["ratingValue", "reviewCount"],
}
REC = {
    "LocalBusiness": ["telephone", "openingHours", "geo", "image", "priceRange"],
    "ChildCare": ["telephone", "openingHours", "geo", "image", "priceRange", "aggregateRating"],
    "Article": ["image", "dateModified", "publisher"], "Event": ["endDate", "image", "description", "offers"],
    "JobPosting": ["baseSalary", "employmentType", "jobLocation"], "Product": ["image", "description", "offers", "aggregateRating"],
    "Person": ["image", "jobTitle", "worksFor"], "Organization": ["logo", "contactPoint", "sameAs"],
}
VALID = {
    "Thing","Action","CreativeWork","Event","Intangible","Organization","Person","Place","Product","Article",
    "BlogPosting","NewsArticle","LocalBusiness","ChildCare","Preschool","EducationalOrganization","Service","Review",
    "AggregateRating","Rating","FAQPage","Question","Answer","HowTo","HowToStep","JobPosting","VideoObject","ImageObject",
    "WebPage","WebSite","BreadcrumbList","ListItem","Offer","PostalAddress","GeoCoordinates","OpeningHoursSpecification",
    "ContactPoint","Course","CourseInstance","Menu","MenuItem","Schedule","EducationalOccupationalCredential","ItemList",
    "CollectionPage","AboutPage","ContactPage","ImageGallery","SpecialAnnouncement","MonetaryAmount","QuantitativeValue","PropertyValue",
}
PHONE = re.compile(r"^[\+\d\-\(\)\s\.]+$")
VALID_PROVIDER = {"Organization", "Person", "LocalBusiness", "ChildCare", "Preschool", "EducationalOrganization"}
VALID_AREA = {"City", "State", "Country", "Place", "GeoShape", "AdministrativeArea"}
VALID_EVENT_LOC = {"Place", "VirtualLocation", "PostalAddress"}


@dataclass
class Issue:
    severity: str
    code: str
    message: str
    page_url: str
    schema_type: str
    node_id: int
    schema_id: Optional[str]


def present(v: Any) -> bool:
    return v not in (None, "", [], {})


def types_of(v: Any) -> List[str]:
    if isinstance(v, list):
        return [str(x).strip() for x in v if str(x).strip()]
    if isinstance(v, str) and v.strip():
        return [v.strip()]
    return []


def asdict_or_none(v: Any) -> Optional[Dict[str, Any]]:
    return v if isinstance(v, dict) else None


def iso_ok(v: Any) -> bool:
    if not isinstance(v, str) or not v.strip():
        return False
    s = v.strip()
    if s.endswith("Z"):
        s = s[:-1] + "+00:00"
    try:
        datetime.fromisoformat(s)
        return True
    except ValueError:
        return False


def add(issues: List[Issue], sev: str, code: str, msg: str, url: str, st: str, nid: int, sid: Optional[str]) -> None:
    issues.append(Issue(sev, code, msg, url, st, nid, sid))


def bucket(url: str) -> str:
    if url == "https://chromaela.com/":
        return "core-home"
    if re.match(r"^https://chromaela\.com/(about|programs|locations|parents|contact-us|careers|blog|newsroom|schedule-a-tour|communities|curriculum)/?$", url):
        return "core"
    if re.match(r"^https://chromaela\.com/programs/[^/]+/?$", url):
        return "program"
    if re.match(r"^https://chromaela\.com/locations/[^/]+/?$", url):
        return "location-campus"
    if re.match(r"^https://chromaela\.com/childcare/[^/]+/?$", url):
        return "location-geo"
    if "taxonomy=location_region" in url:
        return "service-area-taxonomy"
    return "other"


def validate_node(node: Dict[str, Any], url: str, nid: int, issues: List[Issue]) -> None:
    sid = str(node.get("@id")) if node.get("@id") is not None else None
    tps = types_of(node.get("@type"))
    if not tps:
        add(issues, "error", "missing_type", "Schema node missing @type.", url, "(unknown)", nid, sid)
        return

    for st in tps:
        if st not in VALID:
            add(issues, "warning", "unknown_schema_type", f"Unknown/non-whitelisted schema type '{st}'.", url, st, nid, sid)

        for f in REQ.get(st, []):
            if not present(node.get(f)):
                add(issues, "error", f"missing_required_{f}", f"Missing required field '{f}' for '{st}'.", url, st, nid, sid)
        for f in REC.get(st, []):
            if not present(node.get(f)):
                add(issues, "warning", f"missing_recommended_{f}", f"Missing recommended field '{f}' for '{st}'.", url, st, nid, sid)

        for f in ("url", "image", "logo", "thumbnailUrl", "contentUrl"):
            v = node.get(f)
            if isinstance(v, str) and v.strip() and not re.match(r"^https?://", v.strip()):
                add(issues, "warning", f"invalid_url_{f}", f"Invalid URL in '{f}': {v}", url, st, nid, sid)
        for f in ("datePublished", "dateModified", "datePosted", "startDate", "endDate", "uploadDate"):
            if present(node.get(f)) and not iso_ok(node.get(f)):
                add(issues, "warning", f"invalid_date_{f}", f"Invalid ISO date in '{f}'.", url, st, nid, sid)
        if isinstance(node.get("telephone"), str) and node["telephone"].strip() and not PHONE.match(node["telephone"].strip()):
            add(issues, "warning", "invalid_telephone_format", f"Invalid telephone format: {node['telephone']}", url, st, nid, sid)
        if present(node.get("ratingValue")):
            try:
                rv = float(node["ratingValue"])
                if rv < 0 or rv > 5:
                    add(issues, "warning", "rating_out_of_range", f"ratingValue out of range (0-5): {rv}", url, st, nid, sid)
            except Exception:
                add(issues, "warning", "rating_not_numeric", "ratingValue is not numeric.", url, st, nid, sid)

        if st in ("LocalBusiness", "ChildCare", "Preschool"):
            flat = any(present(node.get(k)) for k in ("streetAddress", "addressLocality", "addressRegion", "postalCode"))
            addr = asdict_or_none(node.get("address"))
            if not present(node.get("address")):
                if flat:
                    add(issues, "error", "flat_address_fields_without_address_object", "Flat address keys present without nested address object.", url, st, nid, sid)
            elif addr is None:
                add(issues, "error", "address_not_object", "address is not an object.", url, st, nid, sid)
            else:
                at = str(addr.get("@type", "")).strip()
                if at and at != "PostalAddress":
                    add(issues, "error", "address_wrong_type", f"address.@type should be PostalAddress, got '{at}'.", url, st, nid, sid)
                for f in ("streetAddress", "addressLocality", "addressRegion", "postalCode", "addressCountry"):
                    if not present(addr.get(f)):
                        add(issues, "warning", f"address_missing_{f}", f"Address missing '{f}'.", url, st, nid, sid)
            geo = asdict_or_none(node.get("geo"))
            if geo:
                gt = str(geo.get("@type", "")).strip()
                if gt and gt != "GeoCoordinates":
                    add(issues, "warning", "geo_wrong_type", f"geo.@type should be GeoCoordinates, got '{gt}'.", url, st, nid, sid)
                if not present(geo.get("latitude")) or not present(geo.get("longitude")):
                    add(issues, "warning", "geo_missing_lat_lng", "GeoCoordinates missing latitude/longitude.", url, st, nid, sid)

        if st == "Review":
            if present(node.get("author_name")):
                add(issues, "error", "review_legacy_author_name", "Legacy author_name found.", url, st, nid, sid)
            if present(node.get("itemReviewed_name")):
                add(issues, "error", "review_legacy_itemReviewed_name", "Legacy itemReviewed_name found.", url, st, nid, sid)
        if st == "Service":
            if present(node.get("provider_name")):
                add(issues, "error", "service_legacy_provider_name", "Legacy provider_name found.", url, st, nid, sid)
            prov = asdict_or_none(node.get("provider"))
            if prov:
                pt = str(prov.get("@type", "")).strip()
                if pt and pt not in VALID_PROVIDER:
                    add(issues, "warning", "service_provider_invalid_type", f"Invalid provider @type '{pt}'.", url, st, nid, sid)
                if not present(prov.get("name")):
                    add(issues, "warning", "service_provider_missing_name", "Provider missing name.", url, st, nid, sid)
            ar = asdict_or_none(node.get("areaServed"))
            if ar:
                at = str(ar.get("@type", "")).strip()
                if at and at not in VALID_AREA:
                    add(issues, "warning", "service_areaServed_invalid_type", f"Invalid areaServed @type '{at}'.", url, st, nid, sid)
        if st == "Event":
            for f in ("eventAttendanceMode", "eventStatus"):
                if not present(node.get(f)):
                    add(issues, "error", f"event_missing_{f}", f"Event missing '{f}'.", url, st, nid, sid)
            loc = asdict_or_none(node.get("location"))
            if loc:
                lt = str(loc.get("@type", "")).strip()
                if lt and lt not in VALID_EVENT_LOC:
                    add(issues, "warning", "event_location_invalid_type", f"Invalid event location type '{lt}'.", url, st, nid, sid)
        if st == "Course":
            offers = node.get("offers")
            if isinstance(offers, list):
                for idx, o in enumerate(offers):
                    if isinstance(o, dict) and not present(o.get("@type")):
                        add(issues, "error", "course_offer_missing_type", f"offers[{idx}] missing @type.", url, st, nid, sid)
            elif isinstance(offers, dict) and not present(offers.get("@type")):
                add(issues, "error", "course_offer_missing_type", "offers missing @type.", url, st, nid, sid)
        if st in ("ItemList", "BreadcrumbList"):
            items = node.get("itemListElement")
            if not present(items):
                add(issues, "error", f"{st.lower()}_missing_itemListElement", "itemListElement missing.", url, st, nid, sid)
            elif not isinstance(items, list):
                add(issues, "error", f"{st.lower()}_itemListElement_not_array", "itemListElement must be array.", url, st, nid, sid)
            else:
                for idx, item in enumerate(items):
                    if not isinstance(item, dict):
                        add(issues, "error", f"{st.lower()}_item_not_object", f"itemListElement[{idx}] not object.", url, st, nid, sid)
                        continue
                    it = str(item.get("@type", "")).strip()
                    if it and it != "ListItem":
                        add(issues, "warning", f"{st.lower()}_item_wrong_type", f"itemListElement[{idx}] type '{it}' != ListItem.", url, st, nid, sid)
                    if st == "BreadcrumbList":
                        if not present(item.get("position")):
                            add(issues, "error", "breadcrumb_missing_position", f"Breadcrumb item[{idx}] missing position.", url, st, nid, sid)
                        if isinstance(item.get("name"), str) and re.search(r"<[^>]+>", item["name"]):
                            add(issues, "error", "breadcrumb_name_contains_html", f"Breadcrumb item[{idx}] name contains HTML.", url, st, nid, sid)
        if st == "FAQPage":
            me = node.get("mainEntity")
            if not isinstance(me, list):
                add(issues, "error", "faq_mainEntity_not_array", "mainEntity should be array of Question.", url, st, nid, sid)
            else:
                for idx, q in enumerate(me):
                    if not isinstance(q, dict):
                        add(issues, "error", "faq_question_not_object", f"Question[{idx}] not object.", url, st, nid, sid)
                        continue
                    qt = str(q.get("@type", "")).strip()
                    if qt and qt != "Question":
                        add(issues, "error", "faq_question_wrong_type", f"Question[{idx}] wrong @type '{qt}'.", url, st, nid, sid)
                    a = asdict_or_none(q.get("acceptedAnswer"))
                    if a is None:
                        add(issues, "error", "faq_question_missing_answer", f"Question[{idx}] missing acceptedAnswer.", url, st, nid, sid)
                    elif not present(a.get("text")):
                        add(issues, "error", "faq_answer_missing_text", f"Question[{idx}] answer missing text.", url, st, nid, sid)


def build_md(report: Dict[str, Any]) -> str:
    lines = []
    lines.append("# Full Schema Warning/Error Audit (Expanded)")
    lines.append("")
    lines.append(f"- Generated: {report['generated_at']}")
    lines.append(f"- Snapshot DB: `{report['db_path']}`")
    lines.append("")
    lines.append("## Coverage")
    lines.append("")
    lines.append(f"- Pages scanned: **{report['pages_total']}**")
    lines.append(f"- Schema nodes scanned: **{report['schema_nodes_total']}**")
    lines.append(f"- Parse-error blocks: **{report['parse_error_blocks']}**")
    lines.append(f"- Total errors: **{report['error_count']}**")
    lines.append(f"- Total warnings: **{report['warning_count']}**")
    lines.append("")
    lines.append("## HTTP Status")
    lines.append("")
    lines.append("| Status | Count |")
    lines.append("|---|---:|")
    for code, cnt in report["status_counts"]:
        lines.append(f"| {code} | {cnt} |")
    lines.append("")
    lines.append("## Issues by Route Bucket")
    lines.append("")
    lines.append("| Bucket | Error Count | Warning Count | Affected Pages |")
    lines.append("|---|---:|---:|---:|")
    for b in report["bucket_summary"]:
        lines.append(f"| `{b['bucket']}` | {b['errors']} | {b['warnings']} | {b['pages']} |")
    lines.append("")
    lines.append("## Error Codes")
    lines.append("")
    lines.append("| Code | Count | Unique Pages |")
    lines.append("|---|---:|---:|")
    for code, cnt in report["error_codes"]:
        lines.append(f"| `{code}` | {cnt} | {report['error_code_pages'][code]} |")
    lines.append("")
    lines.append("## Warning Codes")
    lines.append("")
    lines.append("| Code | Count | Unique Pages |")
    lines.append("|---|---:|---:|")
    for code, cnt in report["warning_codes"]:
        lines.append(f"| `{code}` | {cnt} | {report['warning_code_pages'][code]} |")
    lines.append("")
    lines.append("## Full Inventory by Code and URL")
    lines.append("")
    for label, obj in (("Errors", report["errors_by_code_url"]), ("Warnings", report["warnings_by_code_url"])):
        lines.append(f"### {label}")
        lines.append("")
        if not obj:
            lines.append("- None")
            lines.append("")
            continue
        for code in sorted(obj.keys()):
            lines.append(f"#### `{code}`")
            for it in obj[code]:
                lines.append(f"- ({it['count']}) {it['url']}")
            lines.append("")
    lines.append("## Questions For You")
    lines.append("")
    lines.append("1. Should `LocalBusiness`/`ChildCare` be allowed only on `/locations/*` pages?")
    lines.append("2. For `/childcare/*` and service-area pages, should we emit `Service` + `WebPage` only?")
    lines.append("3. For core pages (`about`, `parents`, `curriculum`, `schedule-a-tour`), should we remove `ChildCare` schema unless page is a real campus?")
    lines.append("4. Should unknown types (for example `Speakable`) be removed from output or kept as warning-only?")
    lines.append("5. Should we remove all 13 broken `?post_type=team_member&p=...` URLs from sitemap immediately?")
    lines.append("")
    return "\n".join(lines)


def main() -> int:
    p = argparse.ArgumentParser()
    p.add_argument("--db", required=True)
    p.add_argument("--out-json", required=True)
    p.add_argument("--out-md", required=True)
    args = p.parse_args()
    os.makedirs(os.path.dirname(args.out_json) or ".", exist_ok=True)
    os.makedirs(os.path.dirname(args.out_md) or ".", exist_ok=True)

    conn = sqlite3.connect(args.db)
    conn.row_factory = sqlite3.Row
    issues: List[Issue] = []
    pages_total = conn.execute("select count(*) from pages").fetchone()[0]
    nodes_total = conn.execute("select count(*) from schema_nodes").fetchone()[0]
    status_rows = conn.execute("select status_code,count(*) c from pages group by status_code order by status_code").fetchall()
    non200 = conn.execute("select url,status_code,coalesce(error,'') e from pages where status_code<>200 order by status_code,url").fetchall()
    for r in non200:
        add(issues, "error", "page_http_non_200", f"HTTP {r['status_code']} during crawl.", str(r["url"]), "(page)", 0, None)
    parse_rows = conn.execute("select p.url page_url,sb.parse_error from schema_blocks sb join pages p on p.id=sb.page_id where sb.parse_ok=0").fetchall()
    for r in parse_rows:
        add(issues, "error", "jsonld_parse_error", str(r["parse_error"]), str(r["page_url"]), "(block)", 0, None)
    rows = conn.execute("select sn.id node_id,sn.schema_json,p.url page_url from schema_nodes sn join pages p on p.id=sn.page_id").fetchall()
    conn.close()

    for r in rows:
        try:
            node = json.loads(r["schema_json"])
        except json.JSONDecodeError as exc:
            add(issues, "error", "schema_node_json_parse_error", str(exc), str(r["page_url"]), "(node)", int(r["node_id"]), None)
            continue
        if not isinstance(node, dict):
            add(issues, "error", "schema_node_not_object", "Schema node is not an object.", str(r["page_url"]), "(node)", int(r["node_id"]), None)
            continue
        validate_node(node, str(r["page_url"]), int(r["node_id"]), issues)

    err = Counter(); warn = Counter(); err_pages = defaultdict(set); warn_pages = defaultdict(set)
    by_schema = Counter(); bsum = defaultdict(lambda: {"errors": 0, "warnings": 0, "pages": set()})
    gerr = defaultdict(Counter); gwarn = defaultdict(Counter)
    for i in issues:
        by_schema[i.schema_type] += 1
        b = bucket(i.page_url); bsum[b]["pages"].add(i.page_url)
        if i.severity == "error":
            err[i.code] += 1; err_pages[i.code].add(i.page_url); bsum[b]["errors"] += 1; gerr[i.code][i.page_url] += 1
        else:
            warn[i.code] += 1; warn_pages[i.code].add(i.page_url); bsum[b]["warnings"] += 1; gwarn[i.code][i.page_url] += 1

    report = {
        "generated_at": datetime.now(timezone.utc).isoformat(timespec="seconds"),
        "db_path": args.db, "pages_total": pages_total, "schema_nodes_total": nodes_total,
        "parse_error_blocks": len(parse_rows), "error_count": sum(err.values()), "warning_count": sum(warn.values()),
        "status_counts": [(int(r["status_code"]), int(r["c"])) for r in status_rows],
        "non200_urls": [{"url": str(r["url"]), "status_code": int(r["status_code"]), "error": str(r["e"])} for r in non200],
        "error_codes": err.most_common(), "warning_codes": warn.most_common(),
        "error_code_pages": {k: len(v) for k, v in err_pages.items()},
        "warning_code_pages": {k: len(v) for k, v in warn_pages.items()},
        "issues_by_schema_type": by_schema.most_common(),
        "bucket_summary": [{"bucket": b, "errors": int(v["errors"]), "warnings": int(v["warnings"]), "pages": len(v["pages"])} for b, v in sorted(bsum.items())],
        "errors_by_code_url": {c: [{"url": u, "count": n} for u, n in sorted(m.items(), key=lambda x: (-x[1], x[0]))] for c, m in gerr.items()},
        "warnings_by_code_url": {c: [{"url": u, "count": n} for u, n in sorted(m.items(), key=lambda x: (-x[1], x[0]))] for c, m in gwarn.items()},
        "issues": [asdict(i) for i in issues],
    }
    with open(args.out_json, "w", encoding="utf-8") as f:
        json.dump(report, f, indent=2, ensure_ascii=False)
    with open(args.out_md, "w", encoding="utf-8") as f:
        f.write(build_md(report))
    print(f"[full-schema-audit] db={args.db}")
    print(f"[full-schema-audit] pages={pages_total} nodes={nodes_total}")
    print(f"[full-schema-audit] errors={report['error_count']} warnings={report['warning_count']}")
    print(f"[full-schema-audit] json={args.out_json}")
    print(f"[full-schema-audit] md={args.out_md}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

#!/usr/bin/env python3
"""
Contract and quality validator for the Chroma public geo feed.

Usage:
  python plugins/chroma-agent-api/tools/geo_feed_validate.py --url https://chromaela.com/wp-json/chroma-agent/v1/geo-feed
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from typing import Any, Dict, List, Tuple
from urllib.request import Request, urlopen


REQUIRED_TOP_LEVEL = [
    "success",
    "cached",
    "contract_version",
    "generated_at_gmt",
    "source",
    "summary",
    "brand",
    "curriculum",
    "locations",
    "programs",
]


def fetch_json(url: str) -> Dict[str, Any]:
    req = Request(
        url=url,
        headers={
            "User-Agent": "ChromaGeoFeedValidator/1.0",
            "Accept": "application/json",
        },
    )
    with urlopen(req, timeout=20) as response:
        payload = response.read()
    text = payload.decode("utf-8-sig")
    return json.loads(text)


def load_json_file(path: str) -> Dict[str, Any]:
    with open(path, "r", encoding="utf-8-sig") as f:
        return json.load(f)


def is_abs_url(value: Any) -> bool:
    if not isinstance(value, str) or value.strip() == "":
        return False
    return value.startswith("http://") or value.startswith("https://")


def validate(payload: Dict[str, Any]) -> Tuple[List[str], Dict[str, Any]]:
    errors: List[str] = []
    warnings: List[str] = []

    if not isinstance(payload, dict):
        return ["payload_not_object"], {
            "location_count": 0,
            "program_count": 0,
            "errors": ["payload_not_object"],
            "warnings": [],
            "metrics": {
                "absolute_url_failures": 0,
                "coordinates_numeric_failures": 0,
                "locations_missing_faqs": 0,
                "locations_empty_programs_with_upstream_map": 0,
            },
        }

    for key in REQUIRED_TOP_LEVEL:
        if key not in payload:
            errors.append(f"missing_top_level:{key}")

    locations = payload.get("locations", [])
    programs = payload.get("programs", [])
    if not isinstance(locations, list):
        errors.append("type_error:locations:not_list")
        locations = []
    if not isinstance(programs, list):
        errors.append("type_error:programs:not_list")
        programs = []

    program_ids = set()
    location_ids = set()
    program_location_map = {}

    for location in locations:
        location_id = location.get("id")
        if isinstance(location_id, int):
            location_ids.add(location_id)

    for program in programs:
        pid = program.get("id")
        if isinstance(pid, int):
            program_ids.add(pid)
        loc_refs = program.get("locations_served", [])
        if not isinstance(loc_refs, list):
            errors.append(f"type_error:program:{pid}:locations_served:not_list")
            continue

        served_ids = []
        for ref in loc_refs:
            lid = ref.get("id") if isinstance(ref, dict) else None
            if isinstance(lid, int):
                served_ids.append(lid)
                if lid not in location_ids:
                    errors.append(f"orphan_program_location:{pid}:{lid}")
        program_location_map[pid] = served_ids

    abs_url_failures = 0
    coord_failures = 0
    empty_programs_despite_map = 0
    missing_faqs = 0

    for location in locations:
        lid = location.get("id")
        if not is_abs_url(location.get("url")):
            abs_url_failures += 1

        coords_norm = location.get("coordinates_normalized", {})
        if coords_norm:
            lat = coords_norm.get("latitude")
            lng = coords_norm.get("longitude")
            if lat is not None and not isinstance(lat, (int, float)):
                coord_failures += 1
            if lng is not None and not isinstance(lng, (int, float)):
                coord_failures += 1

        offered = location.get("programs_offered", [])
        if not isinstance(offered, list):
            errors.append(f"type_error:location:{lid}:programs_offered:not_list")
            offered = []

        upstream_has_map = any(isinstance(lid, int) and lid in ids for ids in program_location_map.values())
        if upstream_has_map and not offered:
            empty_programs_despite_map += 1
            errors.append(f"empty_programs_offered_with_upstream_map:{lid}")

        for ref in offered:
            pid = ref.get("id") if isinstance(ref, dict) else None
            if isinstance(pid, int) and pid not in program_ids:
                errors.append(f"orphan_location_program:{lid}:{pid}")

        if not location.get("faqs"):
            missing_faqs += 1
            warnings.append(f"missing_faqs:{lid}")

        phone_e164 = location.get("phone_e164")
        if isinstance(phone_e164, str) and phone_e164 and not re.match(r"^\+\d{8,15}$", phone_e164):
            errors.append(f"invalid_phone_e164:{lid}:{phone_e164}")

    report = {
        "location_count": len(locations),
        "program_count": len(programs),
        "errors": errors,
        "warnings": warnings,
        "metrics": {
            "absolute_url_failures": abs_url_failures,
            "coordinates_numeric_failures": coord_failures,
            "locations_missing_faqs": missing_faqs,
            "locations_empty_programs_with_upstream_map": empty_programs_despite_map,
        },
    }
    return errors, report


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--url", help="Geo feed URL")
    parser.add_argument("--file", help="Path to geo feed JSON file")
    args = parser.parse_args()

    if not args.url and not args.file:
        parser.error("one of --url or --file is required")

    payload = load_json_file(args.file) if args.file else fetch_json(args.url)
    errors, report = validate(payload)
    print(json.dumps(report, indent=2))
    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main())

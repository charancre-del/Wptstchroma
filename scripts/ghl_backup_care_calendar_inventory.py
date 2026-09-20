#!/usr/bin/env python3
"""Read-only inventory of campus calendars used by Backup Care projection."""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any

from ghl_backup_care_calendar_acceptance import calendar_team_member_ids, require_success
from ghl_backup_care_live import request_json, usable_credentials


def run_inventory(env_files: tuple[Path, ...], manifest_path: Path) -> dict[str, Any]:
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    source, token, location_id = usable_credentials(env_files)
    campuses: list[dict[str, Any]] = []
    for configured in manifest.get("campuses", []):
        calendar_id = str(configured.get("source_calendar_id", "")).strip()
        status, payload = request_json(token, f"/calendars/{calendar_id}", {})
        response = require_success(status, payload, f"Get calendar {calendar_id}")
        calendar = response.get("calendar", response)
        team_ids = calendar_team_member_ids(calendar)
        campuses.append(
            {
                "campus_id": configured.get("id"),
                "campus_name": configured.get("name"),
                "calendar_id": calendar_id,
                "calendar_name": calendar.get("name"),
                "calendar_status": calendar.get("isActive"),
                "team_member_ids": team_ids,
                "projection_ready": len(team_ids) == 1,
            }
        )
    return {
        "mode": "read_only_backup_care_calendar_inventory",
        "ok": len(campuses) == len(manifest.get("campuses", [])),
        "credential_values_printed": False,
        "credential_source": str(source),
        "location_fingerprint": f"{location_id[:4]}...{location_id[-4:]}",
        "campus_count": len(campuses),
        "single_member_calendar_count": sum(item["projection_ready"] for item in campuses),
        "campuses": campuses,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--env", action="append", default=[])
    parser.add_argument("--manifest", required=True)
    parser.add_argument("--output")
    args = parser.parse_args()
    result = run_inventory(
        tuple(Path(item) for item in args.env), Path(args.manifest)
    )
    encoded = json.dumps(result, indent=2)
    if args.output:
        Path(args.output).write_text(encoded + "\n", encoding="utf-8")
    print(encoded)
    return 0 if result["ok"] else 1


if __name__ == "__main__":
    raise SystemExit(main())

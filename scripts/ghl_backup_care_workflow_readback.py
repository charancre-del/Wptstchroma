#!/usr/bin/env python3
"""Read-only GHL workflow reconciliation for the Backup Care manifest."""

from __future__ import annotations

import argparse
import json
from pathlib import Path

from ghl_backup_care_live import list_items, request_json, require_success, usable_credentials


def run(env_files: tuple[Path, ...], manifest_path: Path) -> dict:
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    expected = manifest.get("workflow_specification", [])
    source, token, location_id = usable_credentials(env_files)
    status, payload = request_json(token, "/workflows/", {"locationId": location_id})
    require_success(status, payload, "Read workflows")
    workflows = list_items(payload, ("workflows", "data"))
    by_id = {str(item.get("id")): item for item in workflows if item.get("id")}
    reconciled = []
    for configured in expected:
        live = by_id.get(str(configured.get("id")))
        reconciled.append(
            {
                "event_key": configured.get("event_key"),
                "expected_id": configured.get("id"),
                "expected_name": configured.get("name"),
                "found": live is not None,
                "live_name": live.get("name") if live else None,
                "live_status": live.get("status") if live else None,
                "live_version": live.get("version") if live else None,
            }
        )
    name_matches = [
        {
            "id": item.get("id"),
            "name": item.get("name"),
            "status": item.get("status"),
            "version": item.get("version"),
        }
        for item in workflows
        if "backup care" in str(item.get("name", "")).lower()
        or str(item.get("name", "")).lower().startswith("bc ")
    ]
    return {
        "mode": "read_only_backup_care_workflow_reconciliation",
        "ok": True,
        "credential_values_printed": False,
        "credential_source": str(source),
        "location_fingerprint": f"{location_id[:4]}...{location_id[-4:]}",
        "total_visible_workflows": len(workflows),
        "expected_workflow_count": len(expected),
        "expected_ids_found": sum(item["found"] for item in reconciled),
        "expected_workflows": reconciled,
        "backup_care_name_matches": name_matches,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--env", action="append", default=[])
    parser.add_argument("--manifest", required=True)
    parser.add_argument("--output")
    args = parser.parse_args()
    report = run(tuple(Path(item) for item in args.env), Path(args.manifest))
    encoded = json.dumps(report, indent=2)
    if args.output:
        Path(args.output).write_text(encoded + "\n", encoding="utf-8")
    print(encoded)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

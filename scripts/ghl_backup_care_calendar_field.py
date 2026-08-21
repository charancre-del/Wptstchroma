#!/usr/bin/env python3
"""Provision and read back the Backup Care attendance calendar event field."""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any

from ghl_backup_care_live import (
    custom_field_state,
    ensure_custom_fields,
    usable_credentials,
)


CONFIRMATION = "APPLY_BACKUP_CARE_CALENDAR_EVENT_FIELD"
OBJECT_KEY = "custom_objects.backup_care_attendance"
FIELD_KEY = f"{OBJECT_KEY}.ghl_calendar_event_id"


def field_summary(field: dict[str, Any]) -> dict[str, Any]:
    return {
        "id": field.get("id"),
        "name": field.get("name"),
        "fieldKey": field.get("fieldKey"),
        "dataType": field.get("dataType"),
        "showInForms": field.get("showInForms"),
        "isUnique": field.get("isUnique"),
    }


def run(env_files: tuple[Path, ...], confirmation: str | None) -> dict[str, Any]:
    if confirmation != CONFIRMATION:
        raise RuntimeError(f"Provisioning requires --confirm {CONFIRMATION}; no changes were made")
    source, token, location_id = usable_credentials(env_files)
    before_fields, _ = custom_field_state(token, location_id, OBJECT_KEY)
    before = next((item for item in before_fields if item.get("fieldKey") == FIELD_KEY), None)
    result = ensure_custom_fields(
        token,
        location_id,
        {
            "display_name": "Backup Care Attendance",
            "primary_display_field": "attendance_id",
            "fields": [
                {
                    "key": "ghl_calendar_event_id",
                    "label": "GHL Calendar Event ID",
                    "type": "single_line",
                    "required": False,
                }
            ],
            "unique_fields": [],
        },
        OBJECT_KEY,
    )
    after_fields, _ = custom_field_state(token, location_id, OBJECT_KEY)
    after = next((item for item in after_fields if item.get("fieldKey") == FIELD_KEY), None)
    if not after:
        raise RuntimeError("The GHL calendar event field was not present on readback")
    return {
        "mode": "live_backup_care_attendance_field_provisioning",
        "ok": True,
        "credential_values_printed": False,
        "credential_source": str(source),
        "location_fingerprint": f"{location_id[:4]}...{location_id[-4:]}",
        "object_key": OBJECT_KEY,
        "field_key": FIELD_KEY,
        "preexisting": before is not None,
        "before": field_summary(before) if before else None,
        "provision_result": result,
        "after": field_summary(after),
        "forms_created": 0,
        "workflows_activated": 0,
        "payments_attempted": 0,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--env", action="append", default=[])
    parser.add_argument("--confirm")
    parser.add_argument("--output")
    args = parser.parse_args()
    report = run(tuple(Path(item) for item in args.env), args.confirm)
    encoded = json.dumps(report, indent=2)
    if args.output:
        Path(args.output).write_text(encoded + "\n", encoding="utf-8")
    print(encoded)
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(json.dumps({"ok": False, "error": str(exc)}))
        raise SystemExit(1)

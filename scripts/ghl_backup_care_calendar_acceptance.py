#!/usr/bin/env python3
"""Secret-safe temporary acceptance for Backup Care calendar projection."""

from __future__ import annotations

import argparse
import json
from datetime import date, datetime, time
from pathlib import Path
from typing import Any
from zoneinfo import ZoneInfo

from ghl_backup_care_live import request_json, usable_credentials
from ghl_backup_care_services_v2 import contact_safety, require_ok, safe_error


CONFIRMATION = "CREATE_AND_DELETE_BACKUP_CARE_CALENDAR_APPOINTMENTS"


def require_success(status: int, payload: Any, label: str) -> dict[str, Any]:
    if status < 200 or status >= 300 or not isinstance(payload, dict):
        raise RuntimeError(f"{label} failed with HTTP {status}: {safe_error(payload)}")
    return payload


def appointment_times(day: date) -> tuple[str, str]:
    timezone = ZoneInfo("America/New_York")
    start = datetime.combine(day, time(9, 30), timezone)
    end = datetime.combine(day, time(10, 0), timezone)
    return start.isoformat(), end.isoformat()


def appointment_payload(
    location_id: str,
    calendar_id: str,
    contact_id: str,
    assigned_user_id: str,
    address: str,
    child_label: str,
    care_date: date,
) -> dict[str, Any]:
    start_time, end_time = appointment_times(care_date)
    return {
        "locationId": location_id,
        "calendarId": calendar_id,
        "contactId": contact_id,
        "assignedUserId": assigned_user_id,
        "title": f"BC CALENDAR ACCEPTANCE | {child_label} | {care_date.isoformat()}",
        "description": "Temporary Backup Care child-date projection acceptance. Delete after verification.",
        "address": address,
        "meetingLocationType": "custom",
        "overrideLocationConfig": True,
        "appointmentStatus": "confirmed",
        "startTime": start_time,
        "endTime": end_time,
        "ignoreDateRange": True,
        "ignoreFreeSlotValidation": True,
        "toNotify": False,
    }


def calendar_team_member_ids(calendar: dict[str, Any]) -> list[str]:
    ids: set[str] = set()
    for member in calendar.get("teamMembers", []):
        if not isinstance(member, dict):
            continue
        for key in ("userId", "id"):
            value = str(member.get(key, "")).strip()
            if value:
                ids.add(value)
    return sorted(ids)


def matrix_appointments(
    token: str,
    location_id: str,
    calendar_id: str,
    contact_id: str,
    dates: tuple[date, date],
) -> list[dict[str, Any]]:
    timezone = ZoneInfo("America/New_York")
    start = datetime.combine(min(dates), time.min, timezone)
    end = datetime.combine(max(dates), time.max, timezone)
    status, payload = request_json(
        token,
        "/calendars/events",
        {
            "locationId": location_id,
            "calendarId": calendar_id,
            "startTime": str(int(start.timestamp() * 1000)),
            "endTime": str(int(end.timestamp() * 1000)),
        },
    )
    response = require_success(status, payload, "List calendar acceptance appointments")
    events = response.get("events", response.get("appointments", []))
    return [
        item
        for item in events
        if isinstance(item, dict)
        and str(item.get("title", "")).startswith("BC CALENDAR ACCEPTANCE |")
        and str(item.get("contactId", "")) == contact_id
    ]


def run_acceptance(
    env_files: tuple[Path, ...],
    calendar_id: str,
    contact_id: str,
    assigned_user_id: str,
    address: str,
    dates: tuple[date, date],
    confirmation: str | None,
) -> dict[str, Any]:
    if confirmation != CONFIRMATION:
        raise RuntimeError(f"Acceptance requires --confirm {CONFIRMATION}; no changes were made")

    source, token, location_id = usable_credentials(env_files)
    contact_status, contact_payload = request_json(token, f"/contacts/{contact_id}", {})
    contact_response = require_ok(contact_status, contact_payload, "Get test contact")
    contact = contact_response.get("contact", contact_response)
    safety = contact_safety(contact, contact_id)
    if not safety["reserved_test_domain"] or not safety["test_marker_present"]:
        raise RuntimeError("The selected contact is not an isolated test contact; no changes were made")

    calendar_status, calendar_payload = request_json(token, f"/calendars/{calendar_id}", {})
    calendar_response = require_ok(calendar_status, calendar_payload, "Get campus calendar")
    calendar = calendar_response.get("calendar", calendar_response)
    team_member_ids = calendar_team_member_ids(calendar)
    if assigned_user_id not in team_member_ids:
        raise RuntimeError(
            "The assigned user is not on the campus calendar team. "
            f"Allowed user IDs: {', '.join(team_member_ids) or 'none'}; no changes were made"
        )

    recovered_cleanup: list[dict[str, Any]] = []
    for existing in matrix_appointments(token, location_id, calendar_id, contact_id, dates):
        event_id = str(existing.get("id", "")).strip()
        if not event_id:
            continue
        delete_status, delete_payload = request_json(
            token, f"/calendars/events/{event_id}", {}, method="DELETE"
        )
        recovered_cleanup.append(
            {
                "event_id": event_id,
                "delete_status": delete_status,
                "delete_error": safe_error(delete_payload) if delete_status >= 400 else None,
            }
        )
    if any(item["delete_status"] < 200 or item["delete_status"] >= 300 for item in recovered_cleanup):
        raise RuntimeError("An earlier test appointment could not be recovered; no new changes were made")

    created: list[dict[str, Any]] = []
    created_ids: list[str] = []
    cleanup: list[dict[str, Any]] = []
    try:
        for child_label in ("TEST CHILD A", "TEST CHILD B"):
            for care_date in dates:
                payload = appointment_payload(
                    location_id,
                    calendar_id,
                    contact_id,
                    assigned_user_id,
                    address,
                    child_label,
                    care_date,
                )
                status, response = request_json(
                    token,
                    "/calendars/events/appointments",
                    {},
                    method="POST",
                    payload=payload,
                )
                created_appointment = require_success(status, response, "Create calendar appointment")
                event_id = str(created_appointment.get("id", "")).strip()
                if not event_id:
                    raise RuntimeError("Created calendar appointment has no event ID")
                created_ids.append(event_id)
                read_status, read_payload = request_json(
                    token, f"/calendars/events/appointments/{event_id}", {}
                )
                readback_response = require_ok(
                    read_status, read_payload, "Read back calendar appointment"
                )
                readback = readback_response.get(
                    "event", readback_response.get("appointment", readback_response)
                )
                created.append(
                    {
                        "event_id": event_id,
                        "title": readback.get("title"),
                        "calendar_id": readback.get("calendarId"),
                        "contact_id_matches": readback.get("contactId") == contact_id,
                        "assigned_user_id": readback.get("assignedUserId"),
                        "appointment_status": readback.get("appointmentStatus"),
                        "address": readback.get("address"),
                        "start_time": readback.get("startTime"),
                        "end_time": readback.get("endTime"),
                    }
                )
    finally:
        for event_id in reversed(created_ids):
            delete_status, delete_payload = request_json(
                token, f"/calendars/events/{event_id}", {}, method="DELETE"
            )
            cleanup.append(
                {
                    "event_id": event_id,
                    "delete_status": delete_status,
                    "delete_error": safe_error(delete_payload) if delete_status >= 400 else None,
                }
            )

    expected_titles = {
        f"BC CALENDAR ACCEPTANCE | {child} | {care_date.isoformat()}"
        for child in ("TEST CHILD A", "TEST CHILD B")
        for care_date in dates
    }
    cleanup_ok = len(cleanup) == 4 and all(
        200 <= item["delete_status"] < 300 for item in cleanup
    )
    created_ok = (
        len(created) == 4
        and {str(item.get("title")) for item in created} == expected_titles
        and all(item["calendar_id"] == calendar_id for item in created)
        and all(item["contact_id_matches"] for item in created)
    )
    return {
        "mode": "temporary_calendar_matrix_acceptance",
        "ok": created_ok and cleanup_ok,
        "credential_values_printed": False,
        "credential_source": str(source),
        "location_fingerprint": f"{location_id[:4]}...{location_id[-4:]}",
        "test_contact": safety,
        "calendar": {
            "id": calendar_id,
            "name": calendar.get("name"),
            "team_member_count": len(team_member_ids),
            "team_member_ids": team_member_ids,
        },
        "matrix": {
            "children": ["TEST CHILD A", "TEST CHILD B"],
            "dates": [item.isoformat() for item in dates],
            "expected_appointments": 4,
            "created_appointments": len(created),
            "appointments": created,
        },
        "notifications_attempted": False,
        "payment_attempted": False,
        "workflow_activation_attempted": False,
        "cleanup": cleanup,
        "recovered_cleanup": recovered_cleanup,
        "cleanup_ok": cleanup_ok,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--env", action="append", default=[])
    parser.add_argument("--calendar-id", required=True)
    parser.add_argument("--contact-id", required=True)
    parser.add_argument("--assigned-user-id", required=True)
    parser.add_argument("--address", default="550 Grayson Pkwy, Grayson, GA 30017")
    parser.add_argument("--date", action="append", required=True)
    parser.add_argument("--confirm")
    parser.add_argument("--output")
    args = parser.parse_args()
    if len(args.date) != 2:
        raise RuntimeError("Exactly two --date values are required")
    result = run_acceptance(
        tuple(Path(item) for item in args.env),
        args.calendar_id,
        args.contact_id,
        args.assigned_user_id,
        args.address,
        tuple(date.fromisoformat(item) for item in args.date),
        args.confirm,
    )
    encoded = json.dumps(result, indent=2)
    if args.output:
        Path(args.output).write_text(encoded + "\n", encoding="utf-8")
    print(encoded)
    return 0 if result["ok"] else 1


if __name__ == "__main__":
    raise SystemExit(main())

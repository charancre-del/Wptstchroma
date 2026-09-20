#!/usr/bin/env python3
"""Secret-safe Services v2 inspection and acceptance helpers for Backup Care."""

from __future__ import annotations

import argparse
import json
import sys
from datetime import date, datetime, time
from email.utils import parseaddr
from pathlib import Path
from typing import Any
from zoneinfo import ZoneInfo

from ghl_backup_care_live import request_json, safe_error, usable_credentials


SENSITIVE_KEY_PARTS = (
    "authorization",
    "secret",
    "token",
    "password",
    "email",
    "phone",
    "address",
)
ACCEPTANCE_CONFIRMATION = "CREATE_AND_DELETE_BACKUP_CARE_SERVICE_BOOKINGS"


def redact(value: Any, key: str = "") -> Any:
    lowered = key.lower()
    if any(part in lowered for part in SENSITIVE_KEY_PARTS):
        return "[redacted]"
    if isinstance(value, dict):
        return {name: redact(item, name) for name, item in value.items()}
    if isinstance(value, list):
        return [redact(item) for item in value]
    return value


def require_ok(status: int, payload: Any, label: str) -> dict[str, Any]:
    if status != 200 or not isinstance(payload, dict):
        raise RuntimeError(f"{label} failed with HTTP {status}: {safe_error(payload)}")
    return payload


def contact_safety(contact: dict[str, Any], contact_id: str) -> dict[str, Any]:
    email = str(contact.get("email", "")).strip().lower()
    parsed_email = parseaddr(email)[1]
    local, _, domain = parsed_email.partition("@")
    name = " ".join(
        str(contact.get(field, "")) for field in ("firstName", "lastName", "name")
    ).lower()
    return {
        "contact_id": contact_id,
        "email_domain": domain or "missing",
        "reserved_test_domain": domain
        in {"example.com", "example.org", "example.net", "example.test"},
        "test_marker_present": "test" in local or "test" in name or "acceptance" in name,
        "dnd": bool(contact.get("dnd")),
    }


def inspect(
    env_files: tuple[Path, ...], service_id: str, booking_id: str | None
) -> dict[str, Any]:
    source, token, location_id = usable_credentials(env_files)
    service_status, service_payload = request_json(
        token,
        f"/calendars/services/catalog/{service_id}",
        {"locationId": location_id},
    )
    service = require_ok(service_status, service_payload, "Get Backup Care service")
    report: dict[str, Any] = {
        "mode": "read_only_services_v2_inspection",
        "ok": True,
        "credential_values_printed": False,
        "credential_source": str(source),
        "location_fingerprint": (
            f"{location_id[:4]}...{location_id[-4:]}"
            if len(location_id) >= 10
            else "configured"
        ),
        "service": redact(service),
    }
    if booking_id:
        booking_status, booking_payload = request_json(
            token,
            f"/calendars/services/bookings/{booking_id}",
            {"locationId": location_id},
        )
        booking = require_ok(
            booking_status, booking_payload, "Get prior Backup Care service booking"
        )
        report["prior_booking"] = redact(booking)
        contact_id = str(booking.get("contactId", "")).strip()
        if contact_id:
            contact_status, contact_payload = request_json(
                token,
                f"/contacts/{contact_id}",
                {},
            )
            contact_response = require_ok(
                contact_status, contact_payload, "Get prior booking contact"
            )
            contact = contact_response.get("contact", contact_response)
            report["prior_contact_safety"] = contact_safety(contact, contact_id)
    return report


def booking_times(day: date) -> tuple[str, str]:
    timezone = ZoneInfo("America/New_York")
    start = datetime.combine(day, time(9, 30), timezone)
    end = datetime.combine(day, time(10, 0), timezone)
    return start.isoformat(), end.isoformat()


def matrix_bookings(
    token: str, location_id: str, service_location_id: str, dates: tuple[date, date]
) -> list[dict[str, Any]]:
    timezone = ZoneInfo("America/New_York")
    start = datetime.combine(min(dates), time.min, timezone)
    end = datetime.combine(max(dates), time.max, timezone)
    status, payload = request_json(
        token,
        "/calendars/services/bookings",
        {
            "locationId": location_id,
            "startTime": str(int(start.timestamp() * 1000)),
            "endTime": str(int(end.timestamp() * 1000)),
            "timezone": "America/New_York",
            "serviceLocationId": service_location_id,
        },
    )
    response = require_ok(status, payload, "List matrix acceptance bookings")
    return [
        item
        for item in response.get("bookings", [])
        if isinstance(item, dict)
        and str(item.get("title", "")).startswith("BC MATRIX ACCEPTANCE |")
    ]


def run_matrix_acceptance(
    env_files: tuple[Path, ...],
    service_id: str,
    prior_booking_id: str,
    service_location_id: str,
    staff_id: str,
    dates: tuple[date, date],
    confirmation: str | None,
) -> dict[str, Any]:
    if confirmation != ACCEPTANCE_CONFIRMATION:
        raise RuntimeError(
            f"Acceptance requires --confirm {ACCEPTANCE_CONFIRMATION}; no changes were made"
        )
    source, token, location_id = usable_credentials(env_files)
    prior_status, prior_payload = request_json(
        token,
        f"/calendars/services/bookings/{prior_booking_id}",
        {"locationId": location_id},
    )
    prior = require_ok(prior_status, prior_payload, "Get prior test booking")
    contact_id = str(prior.get("contactId", "")).strip()
    contact_status, contact_payload = request_json(token, f"/contacts/{contact_id}", {})
    contact_response = require_ok(contact_status, contact_payload, "Get test contact")
    contact = contact_response.get("contact", contact_response)
    safety = contact_safety(contact, contact_id)
    if not safety["reserved_test_domain"] or not safety["test_marker_present"]:
        raise RuntimeError("The selected contact is not an isolated test contact; no changes were made")

    baseline_matches = matrix_bookings(
        token, location_id, service_location_id, dates
    )
    if baseline_matches:
        raise RuntimeError(
            "Existing matrix acceptance bookings were found; no changes were made"
        )
    created: list[dict[str, Any]] = []
    created_ids: list[str] = []
    cleanup: list[dict[str, Any]] = []
    children = ("TEST CHILD A", "TEST CHILD B")
    try:
        for child in children:
            for care_date in dates:
                start_time, end_time = booking_times(care_date)
                title = f"BC MATRIX ACCEPTANCE | {child} | {care_date.isoformat()}"
                status, payload = request_json(
                    token,
                    "/calendars/services/bookings",
                    {"overrideAvailability": "true", "skipSchedulingNotice": "true"},
                    method="POST",
                    payload={
                        "locationId": location_id,
                        "contactId": contact_id,
                        "startTime": start_time,
                        "endTime": end_time,
                        "timezone": "America/New_York",
                        "services": [{"id": service_id, "staffId": staff_id}],
                        "serviceLocationId": service_location_id,
                        "title": title,
                        "status": "new",
                    },
                )
                if status != 201 or not isinstance(payload, dict):
                    raise RuntimeError(
                        f"Create matrix booking failed with HTTP {status}: {safe_error(payload)}"
                    )
                booking_id = str(payload.get("bookingId", "")).strip()
                if not booking_id:
                    raise RuntimeError("Created matrix booking has no bookingId")
                created_ids.append(booking_id)
                read_status, read_payload = request_json(
                    token,
                    f"/calendars/services/bookings/{booking_id}",
                    {"locationId": location_id},
                )
                readback = require_ok(read_status, read_payload, "Read back matrix booking")
                created.append(
                    {
                        "booking_id": booking_id,
                        "title": readback.get("title"),
                        "start_time": readback.get("startTime"),
                        "end_time": readback.get("endTime"),
                        "status": readback.get("status"),
                        "service_location_id": readback.get("serviceLocationId"),
                        "service_count": len(readback.get("services", [])),
                        "service_ids": [
                            item.get("id")
                            for item in readback.get("services", [])
                            if isinstance(item, dict)
                        ],
                    }
                )
    finally:
        for booking_id in reversed(created_ids):
            delete_status, delete_payload = request_json(
                token,
                f"/calendars/services/bookings/{booking_id}",
                {},
                method="DELETE",
            )
            verify_status, verify_payload = request_json(
                token,
                f"/calendars/services/bookings/{booking_id}",
                {"locationId": location_id},
            )
            cleanup.append(
                {
                    "booking_id": booking_id,
                    "delete_status": delete_status,
                    "delete_error": safe_error(delete_payload) if delete_status >= 400 else None,
                    "readback_status": verify_status,
                    "deleted_flag": (
                        bool(verify_payload.get("deleted"))
                        if isinstance(verify_payload, dict)
                        else None
                    ),
                }
            )

    expected_titles = {
        f"BC MATRIX ACCEPTANCE | {child} | {care_date.isoformat()}"
        for child in children
        for care_date in dates
    }
    created_titles = {str(item.get("title")) for item in created}
    cleanup_ok = all(
        item["delete_status"] == 200
        and (item["readback_status"] == 404 or item["deleted_flag"] is True)
        for item in cleanup
    )
    remaining_matches = matrix_bookings(
        token, location_id, service_location_id, dates
    )
    cleanup_ok = cleanup_ok and not remaining_matches
    return {
        "mode": "temporary_services_v2_matrix_acceptance",
        "ok": len(created) == 4 and created_titles == expected_titles and cleanup_ok,
        "credential_values_printed": False,
        "credential_source": str(source),
        "location_fingerprint": f"{location_id[:4]}...{location_id[-4:]}",
        "test_contact": safety,
        "matrix": {
            "children": list(children),
            "dates": [item.isoformat() for item in dates],
            "expected_appointments": 4,
            "created_appointments": len(created),
            "appointments": created,
        },
        "payment_attempted": False,
        "workflow_activation_attempted": False,
        "cleanup": cleanup,
        "cleanup_ok": cleanup_ok,
        "remaining_acceptance_bookings": len(remaining_matches),
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "command", choices=("inspect", "matrix-acceptance"), nargs="?", default="inspect"
    )
    parser.add_argument("--env-file", action="append", type=Path, dest="env_files")
    parser.add_argument("--service-id", required=True)
    parser.add_argument("--booking-id")
    parser.add_argument("--service-location-id")
    parser.add_argument("--staff-id")
    parser.add_argument("--date", action="append", dest="dates")
    parser.add_argument("--confirm")
    parser.add_argument("--output", type=Path)
    args = parser.parse_args()
    if not args.env_files:
        parser.error("at least one --env-file is required")
    if args.command == "matrix-acceptance":
        if not args.booking_id or not args.service_location_id or not args.staff_id:
            parser.error(
                "matrix-acceptance requires --booking-id, --service-location-id, and --staff-id"
            )
        if not args.dates or len(args.dates) != 2:
            parser.error("matrix-acceptance requires exactly two --date values")
        report = run_matrix_acceptance(
            tuple(args.env_files),
            args.service_id,
            args.booking_id,
            args.service_location_id,
            args.staff_id,
            tuple(date.fromisoformat(value) for value in args.dates),
            args.confirm,
        )
    else:
        report = inspect(tuple(args.env_files), args.service_id, args.booking_id)
    rendered = json.dumps(report, indent=2)
    if args.output:
        args.output.parent.mkdir(parents=True, exist_ok=True)
        args.output.write_text(rendered + "\n", encoding="utf-8")
    print(rendered)
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(json.dumps({"ok": False, "error": str(exc)}), file=sys.stderr)
        raise SystemExit(1)

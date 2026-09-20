#!/usr/bin/env python3
"""Validate and price a backup-care order without calling Stripe or GHL."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import sys
from datetime import date, datetime
from pathlib import Path
from typing import Any
from zoneinfo import ZoneInfo, ZoneInfoNotFoundError


ROOT = Path(__file__).resolve().parents[1]
DEFAULT_MANIFEST = ROOT / "infrastructure" / "ghl" / "backup-care" / "manifest.json"
EMAIL_RE = re.compile(r"^[^@\s]+@[^@\s]+\.[^@\s]+$")
CHILD_ID_RE = re.compile(r"^[A-Za-z0-9_-]{1,64}$")
REQUIRED_POLICIES = (
    "backup_care_terms",
    "full_payment",
    "refund_and_reschedule_deadline",
    "no_discretionary_exceptions",
    "privacy_and_communications",
)
ORDER_FIELDS = {
    "contract_version",
    "client_request_id",
    "campus_id",
    "parent",
    "children",
    "attendance",
    "policy_acceptance",
}
PARENT_FIELDS = {"first_name", "last_name", "email", "mobile_phone"}
CHILD_FIELDS = {
    "client_child_id",
    "first_name",
    "last_name",
    "date_of_birth",
    "age_group",
    "enrollment_record_id",
    "enrollment_record_complete",
}
ATTENDANCE_FIELDS = {"client_child_id", "care_date", "planned_dropoff_local"}


def load_json(path: Path) -> dict[str, Any]:
    with path.open("r", encoding="utf-8") as handle:
        value = json.load(handle)
    if not isinstance(value, dict):
        raise ValueError(f"{path} must contain a JSON object")
    return value


def parse_date(value: Any, field: str, errors: list[str]) -> date | None:
    if not isinstance(value, str):
        errors.append(f"{field} must be an ISO date")
        return None
    try:
        return date.fromisoformat(value)
    except ValueError:
        errors.append(f"{field} must be an ISO date")
        return None


def parse_time_minutes(value: str) -> int:
    hour, minute = value.split(":", 1)
    return int(hour) * 60 + int(minute)


def booking_timezone(name: str, now: datetime | None) -> Any:
    try:
        return ZoneInfo(name)
    except ZoneInfoNotFoundError:
        if name == "America/New_York":
            if now is not None and now.tzinfo is not None:
                return now.tzinfo
            return datetime.now().astimezone().tzinfo
        raise


def require_text(
    value: Any,
    field: str,
    errors: list[str],
    *,
    minimum: int = 1,
    maximum: int = 128,
) -> str:
    if not isinstance(value, str) or not value.strip():
        errors.append(f"{field} is required")
        return ""
    result = value.strip()
    if len(result) < minimum:
        errors.append(f"{field} must contain at least {minimum} characters")
    if len(result) > maximum:
        errors.append(f"{field} exceeds {maximum} characters")
    return result


def reject_unknown_keys(
    value: dict[str, Any], allowed: set[str], field: str, errors: list[str]
) -> None:
    unknown = sorted(set(value) - allowed)
    if unknown:
        errors.append(f"{field} contains unsupported fields: {', '.join(unknown)}")


def line_item_key(request_id: str, campus_id: str, child_id: str, care_date: str) -> str:
    source = "|".join((request_id, campus_id, child_id, care_date)).encode("utf-8")
    return "bcu_" + hashlib.sha256(source).hexdigest()[:24]


def validate_and_quote(
    order: dict[str, Any],
    manifest: dict[str, Any],
    *,
    now: datetime | None = None,
    closures: set[str] | None = None,
    occupancy: dict[str, int] | None = None,
) -> dict[str, Any]:
    errors: list[str] = []
    warnings: list[str] = []
    reject_unknown_keys(order, ORDER_FIELDS, "order", errors)
    timezone_name = str((manifest.get("program") or {}).get("timezone"))
    timezone = booking_timezone(timezone_name, now)
    if now is None:
        now = datetime.now(timezone)
    elif now.tzinfo is None:
        now = now.replace(tzinfo=timezone)
    else:
        now = now.astimezone(timezone)

    if order.get("contract_version") != 1:
        errors.append("contract_version must be 1")
    request_id = require_text(
        order.get("client_request_id"), "client_request_id", errors
    )
    if request_id and not 16 <= len(request_id) <= 128:
        errors.append("client_request_id must contain 16 to 128 characters")

    campuses = {
        str(campus.get("id")): campus for campus in manifest.get("campuses") or []
    }
    campus_id = require_text(order.get("campus_id"), "campus_id", errors, maximum=64)
    campus = campuses.get(campus_id)
    if campus_id and campus is None:
        errors.append("campus_id is not a configured Chroma campus")

    parent = order.get("parent")
    if not isinstance(parent, dict):
        errors.append("parent must be an object")
        parent = {}
    reject_unknown_keys(parent, PARENT_FIELDS, "parent", errors)
    require_text(parent.get("first_name"), "parent.first_name", errors, maximum=80)
    require_text(parent.get("last_name"), "parent.last_name", errors, maximum=80)
    email = require_text(parent.get("email"), "parent.email", errors, maximum=254)
    if email and not EMAIL_RE.fullmatch(email):
        errors.append("parent.email is invalid")
    phone = require_text(
        parent.get("mobile_phone"), "parent.mobile_phone", errors, maximum=32
    )
    if phone and len(re.sub(r"\D", "", phone)) < 7:
        errors.append("parent.mobile_phone is invalid")

    children = order.get("children")
    if not isinstance(children, list) or not 1 <= len(children) <= 10:
        errors.append("children must contain 1 to 10 records")
        children = []
    child_ids: set[str] = set()
    age_groups = set((manifest.get("program") or {}).get("age_groups") or [])
    today = now.date()
    for index, child in enumerate(children):
        field = f"children[{index}]"
        if not isinstance(child, dict):
            errors.append(f"{field} must be an object")
            continue
        reject_unknown_keys(child, CHILD_FIELDS, field, errors)
        child_id = require_text(
            child.get("client_child_id"), f"{field}.client_child_id", errors, maximum=64
        )
        if child_id and not CHILD_ID_RE.fullmatch(child_id):
            errors.append(f"{field}.client_child_id has invalid characters")
        if child_id in child_ids:
            errors.append(f"Duplicate client_child_id: {child_id}")
        child_ids.add(child_id)
        require_text(child.get("first_name"), f"{field}.first_name", errors, maximum=80)
        require_text(child.get("last_name"), f"{field}.last_name", errors, maximum=80)
        birth_date = parse_date(child.get("date_of_birth"), f"{field}.date_of_birth", errors)
        if birth_date and birth_date >= today:
            errors.append(f"{field}.date_of_birth must be before today")
        if child.get("age_group") not in age_groups:
            errors.append(f"{field}.age_group is invalid")
        require_text(
            child.get("enrollment_record_id"),
            f"{field}.enrollment_record_id",
            errors,
            minimum=8,
        )
        if child.get("enrollment_record_complete") is not True:
            errors.append(f"{field}.enrollment_record_complete must be true")

    attendance = order.get("attendance")
    if not isinstance(attendance, list) or not 1 <= len(attendance) <= 310:
        errors.append("attendance must contain 1 to 310 child-date units")
        attendance = []
    pairs: set[tuple[str, str]] = set()
    represented_children: set[str] = set()
    care_dates: set[date] = set()
    requested_per_date: dict[str, int] = {}
    line_items: list[dict[str, str]] = []
    booking = (manifest.get("business_rules") or {}).get("booking") or {}
    same_day_deadline = parse_time_minutes(
        str(booking.get("same_day_booking_deadline_local") or "07:30")
    )
    cutoff = parse_time_minutes(str(booking.get("dropoff_cutoff_local") or "09:30"))
    operating_days = set(booking.get("operating_days") or [])
    horizon_days = booking.get("booking_horizon_days")

    for index, unit in enumerate(attendance):
        field = f"attendance[{index}]"
        if not isinstance(unit, dict):
            errors.append(f"{field} must be an object")
            continue
        reject_unknown_keys(unit, ATTENDANCE_FIELDS, field, errors)
        child_id = require_text(
            unit.get("client_child_id"), f"{field}.client_child_id", errors, maximum=64
        )
        if child_id not in child_ids:
            errors.append(f"{field}.client_child_id does not match a child")
        care_date_value = unit.get("care_date")
        parsed_care_date = parse_date(care_date_value, f"{field}.care_date", errors)
        care_date_text = care_date_value if isinstance(care_date_value, str) else ""
        pair = (child_id, care_date_text)
        if pair in pairs:
            errors.append(f"Duplicate child-date unit: {child_id}/{care_date_text}")
        pairs.add(pair)
        represented_children.add(child_id)
        if parsed_care_date:
            care_dates.add(parsed_care_date)
            requested_per_date[care_date_text] = requested_per_date.get(care_date_text, 0) + 1
            if parsed_care_date < today:
                errors.append(f"{field}.care_date is in the past")
            if parsed_care_date.isoweekday() not in operating_days:
                errors.append(f"{field}.care_date is not an operating day")
            if parsed_care_date == today:
                current_minutes = now.hour * 60 + now.minute
                if current_minutes > same_day_deadline:
                    errors.append(
                        f"{field}.care_date missed the 7:30 AM same-day deadline"
                    )
            if horizon_days and (parsed_care_date - today).days > int(horizon_days):
                errors.append(f"{field}.care_date exceeds the booking horizon")
            if closures is not None and (
                f"*|{care_date_text}" in closures
                or f"all|{care_date_text}" in closures
                or f"{campus_id}|{care_date_text}" in closures
            ):
                errors.append(f"{field}.care_date is closed for backup care")
        planned_dropoff = unit.get("planned_dropoff_local")
        if planned_dropoff is not None:
            try:
                dropoff_minutes = parse_time_minutes(str(planned_dropoff))
            except (TypeError, ValueError):
                errors.append(f"{field}.planned_dropoff_local must use HH:MM")
            else:
                if campus is not None:
                    opening = parse_time_minutes(str(campus["published_open"]))
                    if not opening <= dropoff_minutes <= cutoff:
                        errors.append(
                            f"{field}.planned_dropoff_local must be between campus "
                            "opening and 9:30 AM"
                        )
        if request_id and campus_id and child_id and care_date_text:
            line_items.append(
                {
                    "line_item_key": line_item_key(
                        request_id, campus_id, child_id, care_date_text
                    ),
                    "client_child_id": child_id,
                    "care_date": care_date_text,
                }
            )

    missing_attendance = sorted(child_ids - represented_children)
    if missing_attendance:
        errors.append(
            "Every child must have at least one care date: " + ", ".join(missing_attendance)
        )
    if len(care_dates) > 31:
        errors.append("One order may contain at most 31 distinct care dates")

    capacity_limit = int(
        ((manifest.get("business_rules") or {}).get("capacity") or {}).get(
            "max_booking_units_per_campus_per_care_date"
        )
        or 0
    )
    if occupancy is not None:
        for care_date_text, requested in requested_per_date.items():
            current = int(occupancy.get(f"{campus_id}|{care_date_text}", 0))
            if current + requested > capacity_limit:
                errors.append(f"Capacity is unavailable for {care_date_text}")

    policies = order.get("policy_acceptance")
    if not isinstance(policies, dict):
        errors.append("policy_acceptance must be an object")
        policies = {}
    reject_unknown_keys(policies, set(REQUIRED_POLICIES), "policy_acceptance", errors)
    for policy in REQUIRED_POLICIES:
        if policies.get(policy) is not True:
            errors.append(f"policy_acceptance.{policy} must be true")

    if not booking.get("booking_horizon_days"):
        warnings.append("Future booking horizon is unresolved; payment remains disabled")
    if closures is None:
        warnings.append("GHL closure records must be checked before payment")
    if occupancy is None:
        warnings.append("The 100-unit campus/date ceiling must be checked before payment")
    warnings.append("Enrollment-record references must be revalidated before care")

    price = (manifest.get("business_rules") or {}).get("price") or {}
    amount_cents = int(price.get("amount_cents") or 0)
    quote = None
    if not errors:
        quote = {
            "currency": str(price.get("currency") or "USD"),
            "unit_amount_cents": amount_cents,
            "unit_count": len(line_items),
            "total_amount_cents": amount_cents * len(line_items),
            "billing_unit": "per_child_per_care_date",
            "line_items": line_items,
        }

    return {
        "contract_valid": not errors,
        "payment_creation_allowed": not errors and closures is not None and occupancy is not None,
        "campus_id": campus_id or None,
        "child_count": len(children),
        "care_date_count": len(care_dates),
        "unit_count": len(attendance),
        "quote": quote,
        "errors": errors,
        "warnings": warnings,
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--order", type=Path, required=True)
    parser.add_argument("--manifest", type=Path, default=DEFAULT_MANIFEST)
    parser.add_argument(
        "--now",
        help="ISO timestamp used only for deterministic local tests.",
    )
    args = parser.parse_args()
    now = datetime.fromisoformat(args.now) if args.now else None
    result = validate_and_quote(
        load_json(args.order.resolve()), load_json(args.manifest.resolve()), now=now
    )
    print(json.dumps(result, indent=2))
    return 0 if result["contract_valid"] else 2


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(json.dumps({"contract_valid": False, "error": str(exc)}), file=sys.stderr)
        raise SystemExit(1)

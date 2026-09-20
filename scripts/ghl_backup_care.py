#!/usr/bin/env python3
"""Validate the no-live GHL side of the backup-care infrastructure.

GHL forms and Custom Objects are the authoritative record system. A thin order
cart preserves the multi-child, multi-date contract unless the Services v2
native-checkout pilot proves the full matrix. This command performs local
validation by default and exposes read-only GHL inventory checks only.
"""

from __future__ import annotations

import argparse
import json
import os
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Any


BASE_URL = "https://services.leadconnectorhq.com"
EXPECTED_CAMPUS_COUNT = 24
DEFAULT_MANIFEST = (
    Path(__file__).resolve().parents[1]
    / "infrastructure"
    / "ghl"
    / "backup-care"
    / "manifest.json"
)


class GhlClient:
    """Minimal read-only client; mutating HTTP methods are intentionally absent."""

    def __init__(self, token: str, location_id: str) -> None:
        self.location_id = location_id
        self.headers = {
            "Authorization": f"Bearer {token}",
            "Accept": "application/json",
            "Version": "v3",
            "User-Agent": "ChromaBackupCareReadiness/2.0",
        }

    def get(
        self, path: str, *, params: dict[str, Any] | None = None
    ) -> dict[str, Any]:
        url = BASE_URL + path
        if params:
            url += "?" + urllib.parse.urlencode(params)
        request = urllib.request.Request(url, headers=self.headers, method="GET")
        try:
            with urllib.request.urlopen(request, timeout=45) as response:
                result = json.load(response)
        except urllib.error.HTTPError as exc:
            raw = exc.read().decode("utf-8", "replace")[:4000]
            try:
                error = json.loads(raw)
            except json.JSONDecodeError:
                error = {"message": "Non-JSON response from GHL"}
            safe = {
                key: error.get(key)
                for key in ("statusCode", "message", "error", "title", "detail")
                if key in error
            }
            raise RuntimeError(
                f"GHL GET {path} returned HTTP {exc.code}: {safe}"
            ) from exc
        if not isinstance(result, dict):
            raise RuntimeError(f"GHL GET {path} returned a non-object response")
        return result

    def calendars(self) -> list[dict[str, Any]]:
        result = self.get(
            "/calendars/",
            params={"locationId": self.location_id, "showDrafted": "true"},
        )
        return list(result.get("calendars") or [])

    def forms(self) -> list[dict[str, Any]]:
        result = self.get("/forms/", params={"locationId": self.location_id})
        return list(result.get("forms") or [])

    def objects(self) -> list[dict[str, Any]]:
        result = self.get("/objects/", params={"locationId": self.location_id})
        for key in ("objects", "schemas", "data"):
            value = result.get(key)
            if isinstance(value, list):
                return list(value)
        return []


def load_manifest(path: Path) -> dict[str, Any]:
    with path.open("r", encoding="utf-8") as handle:
        manifest = json.load(handle)
    if not isinstance(manifest, dict):
        raise ValueError("Manifest root must be a JSON object")
    return manifest


def parse_time(value: str) -> tuple[int, int]:
    hour, minute = value.split(":", 1)
    parsed = (int(hour), int(minute))
    if parsed[0] not in range(24) or parsed[1] not in range(60):
        raise ValueError(f"Invalid local time: {value}")
    return parsed


def minutes_since_midnight(value: str) -> int:
    hour, minute = parse_time(value)
    return hour * 60 + minute


def manifest_blockers(manifest: dict[str, Any]) -> list[str]:
    blockers: list[str] = []
    campuses = manifest.get("campuses") or []
    if len(campuses) != EXPECTED_CAMPUS_COUNT:
        blockers.append(
            f"Expected {EXPECTED_CAMPUS_COUNT} campuses, found {len(campuses)}"
        )

    campus_ids = [str(item.get("id", "")) for item in campuses]
    duplicate_ids = sorted(
        campus_id for campus_id in set(campus_ids) if campus_ids.count(campus_id) > 1
    )
    if duplicate_ids:
        blockers.append("Duplicate campus IDs: " + ", ".join(duplicate_ids))

    missing_emails = [
        str(item.get("id")) for item in campuses if not item.get("notification_email")
    ]
    if missing_emails:
        blockers.append(
            "Missing campus notification email: " + ", ".join(missing_emails)
        )
    missing_coordinates = [
        str(item.get("id"))
        for item in campuses
        if not isinstance(item.get("latitude"), (int, float))
        or not isinstance(item.get("longitude"), (int, float))
    ]
    if missing_coordinates:
        blockers.append(
            "Missing campus coordinates: " + ", ".join(missing_coordinates)
        )

    architecture = manifest.get("architecture") or {}
    if architecture.get("checkout") != "wordpress_server_side_child_date_cart":
        blockers.append("Checkout must use the WordPress server-side child-date cart")
    if architecture.get("native_ghl_forms_used") is not True:
        blockers.append("Native GHL forms must be enabled for intake")
    native_widget_enabled = architecture.get("native_ghl_calendar_widget_used") is True
    if architecture.get("authoritative_booking_store") != "wordpress_transaction_ledger_with_ghl_custom_object_readback":
        blockers.append("Booking state must use the transaction ledger with GHL readback")
    if architecture.get("authoritative_child_record_store") != "ghl_custom_objects":
        blockers.append("GHL Custom Objects must be the authoritative child store")

    rules = manifest.get("business_rules") or {}
    booking = rules.get("booking") or {}
    cutoff = str(booking.get("dropoff_cutoff_local") or "")
    deadline = str(booking.get("same_day_booking_deadline_local") or "")
    if not cutoff or not deadline:
        blockers.append("Drop-off cutoff or same-day booking deadline is missing")
    else:
        notice = int(booking.get("minimum_notice_minutes") or 0)
        if minutes_since_midnight(cutoff) - minutes_since_midnight(deadline) != notice:
            blockers.append(
                "Same-day deadline must be exactly the minimum notice before cutoff"
            )
    if booking.get("booking_horizon_days") != 365:
        blockers.append("Future booking horizon must be 365 days")
    if booking.get("closure_source") != "ghl_backup_care_closure_custom_object":
        blockers.append("The GHL Backup Care Closure object must be the closure source")
    if not booking.get("closure_source_verified_by_api"):
        blockers.append("Closure enforcement has not passed API acceptance testing")

    price = rules.get("price") or {}
    if price.get("amount_cents") != 11500 or price.get("currency") != "USD":
        blockers.append("Latest approved unit price must be $115.00 USD")
    if price.get("tax_treatment") != "all_in_no_added_tax_or_processing_fee":
        blockers.append("The $115 unit price must remain all-in")

    capacity = rules.get("capacity") or {}
    if capacity.get("max_booking_units_per_campus_per_care_date") != 100:
        blockers.append("Campus/date booking ceiling must be 100 child-date units")
    if not capacity.get("licensed_capacity_and_ratio_attested"):
        blockers.append("Licensed capacity and staffing-ratio controls are not attested")

    cancellation = rules.get("cancellation") or {}
    if cancellation.get("reschedulable_until_hours_before_care") != 72:
        blockers.append("Reschedule deadline must be 72 hours before care")
    if cancellation.get("refund_owner_email") != "billing@chromaela.com":
        blockers.append("Refund owner must be billing@chromaela.com")

    payment = rules.get("payment") or {}
    if payment.get("provider") != "stripe":
        blockers.append("Payment provider must be Stripe")
    if not payment.get("provider_connection_confirmed"):
        blockers.append("Stripe connection in GHL has not been confirmed")

    ghl = manifest.get("ghl") or {}
    missing_schema_keys = [
        name
        for name, schema_key in (ghl.get("custom_object_schema_keys") or {}).items()
        if not schema_key
    ]
    if missing_schema_keys:
        blockers.append(
            "Missing GHL Custom Object schema keys: " + ", ".join(missing_schema_keys)
        )
    missing_association_ids = [
        name
        for name, association_id in (ghl.get("association_ids") or {}).items()
        if not association_id
    ]
    if missing_association_ids:
        blockers.append(
            f"Missing {len(missing_association_ids)} required GHL association IDs"
        )
    missing_form_ids = [
        name
        for name, form_id in (ghl.get("form_ids") or {}).items()
        if not form_id
    ]
    if missing_form_ids:
        blockers.append("Missing GHL form IDs: " + ", ".join(missing_form_ids))
    if ghl.get("custom_field_uniqueness_confirmed") is not True:
        blockers.append("GHL unique-field rules require UI confirmation")
    if ghl.get("association_cardinality_confirmed") is not True:
        blockers.append("GHL one-to-many association cardinality requires UI confirmation")
    if ghl.get("workflows_created_inactive") is not True:
        blockers.append("GHL inactive Backup Care workflows have not been created")
    services = ghl.get("services_v2") or {}
    if native_widget_enabled and not services.get("native_checkout_acceptance_passed"):
        blockers.append("Native GHL checkout cannot be enabled before acceptance passes")
    if ghl.get("calendar_provisioning_enabled") is not False:
        blockers.append("Native calendar provisioning must remain disabled")
    if ghl.get("workflow_activation_enabled") is not False:
        blockers.append("GHL workflow activation must remain disabled")

    stripe = manifest.get("stripe") or {}
    if stripe.get("unit_amount_cents") != 11500:
        blockers.append("Stripe unit price must be $115.00")
    if stripe.get("integration_owner") != "wordpress_backup_care_coordinator":
        blockers.append("Stripe integration must be owned by the WordPress backup-care coordinator")
    if stripe.get("client_totals_trusted") is not False:
        blockers.append("Stripe totals must be calculated by the server")

    unresolved_gates = [
        str(gate.get("id"))
        for gate in manifest.get("deployment_gates") or []
        if gate.get("required") and gate.get("status") != "complete"
    ]
    if unresolved_gates:
        blockers.append("Unresolved deployment gates: " + ", ".join(unresolved_gates))
    if manifest.get("live_changes_allowed") is not True:
        blockers.append("Manifest live_changes_allowed is false")
    return blockers


def safe_live_inventory(
    calendars: list[dict[str, Any]],
    forms: list[dict[str, Any]],
    objects: list[dict[str, Any]],
) -> dict[str, Any]:
    backup_calendars = [
        {"id": item.get("id"), "name": item.get("name")}
        for item in calendars
        if "backup care" in str(item.get("name", "")).lower()
        or str(item.get("name", "")).startswith("BC | ")
    ]
    backup_forms = [
        {"id": item.get("id"), "name": item.get("name")}
        for item in forms
        if "backup care" in str(item.get("name", "")).lower()
    ]
    backup_objects = [
        {
            "id": item.get("id"),
            "name": item.get("name") or item.get("displayName"),
            "key": item.get("key") or item.get("schemaKey"),
        }
        for item in objects
        if "backup care"
        in str(item.get("name") or item.get("displayName") or "").lower()
    ]
    return {
        "mode": "read_only",
        "live_calendar_count": len(calendars),
        "live_form_count": len(forms),
        "live_object_count": len(objects),
        "existing_backup_care_calendars": backup_calendars,
        "existing_backup_care_forms": backup_forms,
        "existing_backup_care_objects": backup_objects,
        "planned_public_calendar_widgets": 0,
    }


def credentials_from_environment() -> tuple[str, str]:
    token = os.environ.get("HIGHLEVEL_TOKEN") or os.environ.get(
        "GHL_PRIVATE_INTEGRATION_KEY"
    )
    location_id = os.environ.get("HIGHLEVEL_LOCATION_ID") or os.environ.get(
        "GHL_LOCATION_ID"
    )
    if not token or not location_id:
        raise RuntimeError(
            "Set HIGHLEVEL_TOKEN and HIGHLEVEL_LOCATION_ID (or their GHL aliases) "
            "in the process environment. Never place them in the manifest."
        )
    return token, location_id


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--manifest", type=Path, default=DEFAULT_MANIFEST)
    parser.add_argument(
        "--check-live",
        action="store_true",
        help="Read calendars, forms, and object schemas from GHL without changing them.",
    )
    parser.add_argument(
        "--apply-drafts",
        action="store_true",
        help="Deprecated and always refused for the order-cart architecture.",
    )
    args = parser.parse_args()

    if args.apply_drafts:
        raise RuntimeError(
            "Native calendar draft creation is disabled. The approved architecture "
            "uses a server-side order cart and read-only GHL preparation."
        )

    manifest = load_manifest(args.manifest.resolve())
    blockers = manifest_blockers(manifest)
    summary: dict[str, Any] = {
        "ok": not blockers,
        "mode": manifest.get("mode"),
        "architecture": (manifest.get("architecture") or {}).get("checkout"),
        "campus_count": len(manifest.get("campuses") or []),
        "price_cents": (manifest.get("business_rules") or {})
        .get("price", {})
        .get("amount_cents"),
        "supports_multiple_children": True,
        "supports_multiple_dates": True,
        "blockers": blockers,
    }

    if args.check_live:
        token, location_id = credentials_from_environment()
        client = GhlClient(token, location_id)
        summary["live_inventory"] = safe_live_inventory(
            client.calendars(), client.forms(), client.objects()
        )

    print(json.dumps(summary, indent=2))
    return 0 if not blockers else 2


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(json.dumps({"ok": False, "error": str(exc)}), file=sys.stderr)
        raise SystemExit(1)

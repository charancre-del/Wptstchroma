#!/usr/bin/env python3
"""Validate and safely upsert Backup Care closure records in GHL."""

from __future__ import annotations

import argparse
import csv
import json
import urllib.error
import urllib.parse
import urllib.request
from datetime import date, datetime, timedelta
from pathlib import Path
from typing import Any
from zoneinfo import ZoneInfo

from ghl_backup_care_live import DEFAULT_ENV_FILES, usable_credentials


BASE_URL = "https://services.leadconnectorhq.com"
APPLY_CONFIRMATION = "APPLY_BACKUP_CARE_CLOSURES"
DEFAULT_MANIFEST = Path("infrastructure/ghl/backup-care/manifest.json")
SCHEMA_KEY = "custom_objects.backup_care_closure"
CSV_FIELDS = ("closure_date", "campus_id", "reason", "status")


def request_json(
    token: str,
    path: str,
    *,
    method: str = "GET",
    params: dict[str, str] | None = None,
    payload: Any = None,
    version: str = "v3",
) -> tuple[int, Any]:
    query = urllib.parse.urlencode(params or {})
    url = BASE_URL + path + (("?" + query) if query else "")
    body = json.dumps(payload).encode("utf-8") if payload is not None else None
    request = urllib.request.Request(
        url,
        headers={
            "Accept": "application/json",
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json",
            "User-Agent": "ChromaBackupCareClosures/1.0",
            "Version": version,
        },
        data=body,
        method=method,
    )
    try:
        with urllib.request.urlopen(request, timeout=45) as response:
            return response.status, json.load(response)
    except urllib.error.HTTPError as exc:
        try:
            response_payload = json.loads(exc.read().decode("utf-8", "replace"))
        except json.JSONDecodeError:
            response_payload = {"message": "Non-JSON error response"}
        return exc.code, response_payload


def require_success(status: int, payload: Any, action: str) -> dict[str, Any]:
    if status < 200 or status >= 300 or not isinstance(payload, dict):
        message = payload.get("message") if isinstance(payload, dict) else "Invalid response"
        raise RuntimeError(f"{action} failed with HTTP {status}: {str(message)[:240]}")
    return payload


def load_manifest(path: Path) -> dict[str, Any]:
    payload = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(payload, dict):
        raise ValueError("Manifest must contain a JSON object")
    return payload


def validate_csv(
    path: Path,
    manifest: dict[str, Any],
    *,
    today: date | None = None,
) -> list[dict[str, str]]:
    today = today or datetime.now(ZoneInfo("America/New_York")).date()
    horizon_days = int(manifest["business_rules"]["booking"]["booking_horizon_days"])
    last_date = today + timedelta(days=horizon_days)
    campus_ids = {str(campus["id"]) for campus in manifest.get("campuses", [])}
    rows: list[dict[str, str]] = []
    seen: set[str] = set()

    with path.open("r", encoding="utf-8-sig", newline="") as handle:
        reader = csv.DictReader(handle)
        if tuple(reader.fieldnames or ()) != CSV_FIELDS:
            raise ValueError("Closure CSV header must be exactly: " + ",".join(CSV_FIELDS))
        for line_number, raw in enumerate(reader, start=2):
            normalized = {key: (raw.get(key) or "").strip() for key in CSV_FIELDS}
            if not any(normalized.values()):
                continue
            try:
                closure_date = date.fromisoformat(normalized["closure_date"])
            except ValueError as exc:
                raise ValueError(f"Line {line_number}: closure_date must be YYYY-MM-DD") from exc
            campus_id = normalized["campus_id"].lower()
            if campus_id != "all" and campus_id not in campus_ids:
                raise ValueError(f"Line {line_number}: unknown campus_id {campus_id!r}")
            if closure_date < today or closure_date > last_date:
                raise ValueError(
                    f"Line {line_number}: closure_date must be between {today} and {last_date}"
                )
            status = normalized["status"].lower() or "active"
            if status not in {"active", "inactive"}:
                raise ValueError(f"Line {line_number}: status must be active or inactive")
            closure_key = f"{campus_id}__{closure_date.isoformat()}"
            if closure_key in seen:
                raise ValueError(f"Line {line_number}: duplicate closure {closure_key}")
            seen.add(closure_key)
            rows.append(
                {
                    "closure_key": closure_key,
                    "closure_date": closure_date.isoformat(),
                    "campus_id": campus_id,
                    "reason": normalized["reason"],
                    "status": status,
                }
            )
    if not rows:
        raise ValueError("Closure CSV contains no records")
    return rows


def search_record(token: str, location_id: str, closure_key: str) -> dict[str, Any] | None:
    status, payload = request_json(
        token,
        f"/objects/{urllib.parse.quote(SCHEMA_KEY, safe='')}/records/search",
        method="POST",
        payload={
            "locationId": location_id,
            "page": 1,
            "pageLimit": 20,
            "query": closure_key,
            "searchAfter": [],
        },
    )
    response = require_success(status, payload, f"Search closure {closure_key}")
    records = response.get("customObjectRecords", response.get("records", []))
    for record in records if isinstance(records, list) else []:
        properties = record.get("properties", {}) if isinstance(record, dict) else {}
        if str(properties.get("closure_key", "")) == closure_key:
            return record
    return None


def upsert_record(
    token: str, location_id: str, properties: dict[str, str]
) -> dict[str, str]:
    closure_key = properties["closure_key"]
    existing = search_record(token, location_id, closure_key)
    encoded_schema = urllib.parse.quote(SCHEMA_KEY, safe="")
    if existing:
        record_id = str(existing.get("id", ""))
        if not record_id:
            raise RuntimeError(f"Existing closure {closure_key} has no record ID")
        status, payload = request_json(
            token,
            f"/objects/{encoded_schema}/records/{urllib.parse.quote(record_id, safe='')}",
            method="PUT",
            params={"locationId": location_id},
            payload={"properties": properties},
        )
        require_success(status, payload, f"Update closure {closure_key}")
        action = "updated"
    else:
        status, payload = request_json(
            token,
            f"/objects/{encoded_schema}/records",
            method="POST",
            payload={"locationId": location_id, "properties": properties},
            version="2023-02-21",
        )
        response = require_success(status, payload, f"Create closure {closure_key}")
        record_id = str((response.get("record") or {}).get("id", ""))
        if not record_id:
            raise RuntimeError(f"Created closure {closure_key} has no record ID")
        action = "created"

    readback = search_record(token, location_id, closure_key)
    if not readback or str(readback.get("id", "")) != record_id:
        raise RuntimeError(f"Closure readback failed for {closure_key}")
    readback_properties = readback.get("properties", {})
    for key, value in properties.items():
        if str(readback_properties.get(key, "")) != value:
            raise RuntimeError(f"Closure readback mismatch for {closure_key}.{key}")
    return {"closure_key": closure_key, "record_id": record_id, "action": action}


def apply_closures(
    rows: list[dict[str, str]],
    env_files: tuple[Path, ...],
    manifest: dict[str, Any],
    confirmation: str | None,
) -> dict[str, Any]:
    if confirmation != APPLY_CONFIRMATION:
        raise ValueError(f"Apply requires --confirm {APPLY_CONFIRMATION}; no changes were made")
    source, token, location_id = usable_credentials(env_files)
    expected_location_id = str(manifest.get("ghl", {}).get("location_id") or manifest.get("location_id") or "")
    if expected_location_id and location_id != expected_location_id:
        raise RuntimeError("Working GHL credential belongs to a different location")
    results = [upsert_record(token, location_id, row) for row in rows]
    return {
        "ok": True,
        "mode": "apply",
        "credential_source": str(source),
        "credential_values_printed": False,
        "record_count": len(results),
        "results": results,
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("command", choices=("validate", "apply"), nargs="?", default="validate")
    parser.add_argument("--csv", required=True, type=Path)
    parser.add_argument("--manifest", type=Path, default=DEFAULT_MANIFEST)
    parser.add_argument("--env-file", action="append", type=Path, dest="env_files")
    parser.add_argument("--confirm")
    parser.add_argument("--output", type=Path)
    args = parser.parse_args()

    manifest = load_manifest(args.manifest)
    rows = validate_csv(args.csv, manifest)
    if args.command == "apply":
        report = apply_closures(
            rows,
            tuple(args.env_files or DEFAULT_ENV_FILES),
            manifest,
            args.confirm,
        )
    else:
        report = {
            "ok": True,
            "mode": "validate_only",
            "changes_made": False,
            "record_count": len(rows),
            "closures": rows,
        }
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
        print(json.dumps({"ok": False, "error": str(exc)}))
        raise SystemExit(1)

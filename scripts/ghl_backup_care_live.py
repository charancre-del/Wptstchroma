#!/usr/bin/env python3
"""Audit GHL access for the Backup Care infrastructure without exposing secrets.

The script is designed to run on the Chroma VPS, where existing applications
already provide the GHL token and location ID. Its default ``probe`` command is
strictly read-only and reports only credential source names, HTTP statuses, and
inventory counts.
"""

from __future__ import annotations

import argparse
import json
import re
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path
from typing import Any


BASE_URL = "https://services.leadconnectorhq.com"
DEFAULT_ENV_FILES = (
    Path("/opt/chroma-onboarding/.env"),
    Path("/opt/chroma-ops-dashboard/.env"),
    Path("/opt/n8n/.env"),
)
DEFAULT_FOLDER_NAME = "Backup Care"
APPLY_CONFIRMATION = "APPLY_BACKUP_CARE_GHL_20260819"
TOKEN_NAMES = ("GHL_ACCESS_TOKEN", "GHL_PRIVATE_INTEGRATION_KEY", "HIGHLEVEL_TOKEN")
LOCATION_NAMES = ("GHL_LOCATION_ID", "HIGHLEVEL_LOCATION_ID")
ENV_LINE = re.compile(r"^(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$")


def parse_env(path: Path) -> dict[str, str]:
    values: dict[str, str] = {}
    for raw_line in path.read_text(encoding="utf-8", errors="replace").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#"):
            continue
        match = ENV_LINE.match(line)
        if not match:
            continue
        value = match.group(2).strip()
        if len(value) >= 2 and value[0] == value[-1] and value[0] in "\"'":
            value = value[1:-1]
        values[match.group(1)] = value
    return values


def first_value(values: dict[str, str], names: tuple[str, ...]) -> tuple[str, str] | None:
    for name in names:
        value = values.get(name)
        if value:
            return name, value
    return None


def request_json(
    token: str, path: str, params: dict[str, str], *, method: str = "GET", payload: Any = None
) -> tuple[int, Any]:
    url = BASE_URL + path + "?" + urllib.parse.urlencode(params)
    body = json.dumps(payload).encode("utf-8") if payload is not None else None
    request = urllib.request.Request(
        url,
        headers={
            "Accept": "application/json",
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json",
            "User-Agent": "ChromaBackupCareProvisioner/1.0",
            "Version": "v3",
        },
        data=body,
        method=method,
    )
    try:
        with urllib.request.urlopen(request, timeout=45) as response:
            return response.status, json.load(response)
    except urllib.error.HTTPError as exc:
        try:
            payload: Any = json.loads(exc.read().decode("utf-8", "replace"))
        except json.JSONDecodeError:
            payload = {"message": "Non-JSON error response"}
        return exc.code, payload


def item_count(payload: Any, keys: tuple[str, ...]) -> int | None:
    if not isinstance(payload, dict):
        return None
    for key in keys:
        value = payload.get(key)
        if isinstance(value, list):
            return len(value)
    return None


def list_items(payload: Any, keys: tuple[str, ...]) -> list[dict[str, Any]]:
    if not isinstance(payload, dict):
        return []
    for key in keys:
        value = payload.get(key)
        if isinstance(value, list):
            return [item for item in value if isinstance(item, dict)]
    return []


def safe_error(payload: Any) -> str | None:
    if not isinstance(payload, dict):
        return None
    for key in ("message", "error", "title", "detail"):
        value = payload.get(key)
        if isinstance(value, str):
            return value[:240]
        if isinstance(value, list):
            return "; ".join(str(item) for item in value)[:240]
    return None


def probe_file(path: Path) -> dict[str, Any]:
    result: dict[str, Any] = {"env_file": str(path), "exists": path.exists()}
    if not path.exists():
        return result

    values = parse_env(path)
    token_item = first_value(values, TOKEN_NAMES)
    location_item = first_value(values, LOCATION_NAMES)
    result["token_variable"] = token_item[0] if token_item else None
    result["location_variable"] = location_item[0] if location_item else None
    if not token_item or not location_item:
        result["usable"] = False
        return result

    token = token_item[1]
    location_id = location_item[1]
    endpoints = (
        ("objects", "/objects/", ("objects", "schemas", "data")),
        ("forms", "/forms/", ("forms",)),
        ("services", "/calendars/services/catalog", ("services", "data")),
        ("associations", "/associations/", ("associations", "data")),
    )
    checks: dict[str, Any] = {}
    for name, endpoint, keys in endpoints:
        status, payload = request_json(token, endpoint, {"locationId": location_id})
        check: dict[str, Any] = {"status": status, "count": item_count(payload, keys)}
        if status >= 400:
            check["error"] = safe_error(payload)
        checks[name] = check

    result["checks"] = checks
    result["usable"] = checks["objects"]["status"] == 200
    result["location_fingerprint"] = (
        f"{location_id[:4]}...{location_id[-4:]}" if len(location_id) >= 10 else "configured"
    )
    return result


def usable_credentials(files: tuple[Path, ...]) -> tuple[Path, str, str]:
    for path in files:
        if not path.exists():
            continue
        values = parse_env(path)
        token_item = first_value(values, TOKEN_NAMES)
        location_item = first_value(values, LOCATION_NAMES)
        if not token_item or not location_item:
            continue
        status, _ = request_json(
            token_item[1], "/objects/", {"locationId": location_item[1]}
        )
        if status == 200:
            return path, token_item[1], location_item[1]
    raise RuntimeError("No candidate environment contains a working GHL credential")


def pick(item: dict[str, Any], names: tuple[str, ...]) -> dict[str, Any]:
    return {name: item.get(name) for name in names if name in item}


def safe_inventory(files: tuple[Path, ...]) -> dict[str, Any]:
    source, token, location_id = usable_credentials(files)
    endpoint_specs = {
        "objects": ("/objects/", ("objects", "schemas", "data")),
        "forms": ("/forms/", ("forms",)),
        "services": ("/calendars/services/catalog", ("services", "data")),
        "service_locations": (
            "/calendars/services/locations",
            ("locations", "serviceLocations", "data"),
        ),
        "associations": ("/associations/", ("associations", "data")),
    }
    keep = {
        "objects": (
            "id",
            "key",
            "name",
            "labels",
            "description",
            "primaryDisplayProperty",
            "primaryDisplayPropertyDetails",
        ),
        "forms": ("id", "name", "dateAdded", "dateUpdated"),
        "services": (
            "id",
            "name",
            "slug",
            "description",
            "isPrivate",
            "formId",
            "payment",
            "staff",
            "serviceDuration",
            "serviceDurationUnit",
        ),
        "service_locations": (
            "id",
            "name",
            "slug",
            "address",
            "locationType",
        ),
        "associations": (
            "id",
            "key",
            "associationType",
            "firstObjectKey",
            "firstObjectLabel",
            "firstObjectCardinality",
            "secondObjectKey",
            "secondObjectLabel",
            "secondObjectCardinality",
        ),
    }
    inventory: dict[str, Any] = {
        "mode": "read_only_inventory",
        "credential_values_printed": False,
        "credential_source": str(source),
        "location_fingerprint": f"{location_id[:4]}...{location_id[-4:]}",
    }
    for name, (endpoint, keys) in endpoint_specs.items():
        status, payload = request_json(token, endpoint, {"locationId": location_id})
        items = list_items(payload, keys)
        inventory[name] = {
            "status": status,
            "count": len(items) if status == 200 else None,
            "items": [pick(item, keep[name]) for item in items],
        }
        if status >= 400:
            inventory[name]["error"] = safe_error(payload)

    tag_status, tag_payload = request_json(
        token,
        f"/locations/{urllib.parse.quote(location_id, safe='')}/tags",
        {},
    )
    tags = list_items(tag_payload, ("tags", "data"))
    backup_care_tags = [
        item
        for item in tags
        if str(item.get("name", "")).lower().startswith("backup-care")
    ]
    inventory["tags"] = {
        "status": tag_status,
        "total_count": len(tags) if tag_status == 200 else None,
        "backup_care_count": len(backup_care_tags) if tag_status == 200 else None,
        "items": [pick(item, ("id", "name")) for item in backup_care_tags],
    }
    if tag_status >= 400:
        inventory["tags"]["error"] = safe_error(tag_payload)

    workflow_status, workflow_payload = request_json(
        token, "/workflows/", {"locationId": location_id}
    )
    workflows = list_items(workflow_payload, ("workflows", "data"))
    backup_care_workflows = [
        item
        for item in workflows
        if str(item.get("name", "")).startswith("BC |")
        or "backup care" in str(item.get("name", "")).lower()
    ]
    inventory["workflows"] = {
        "status": workflow_status,
        "total_count": len(workflows) if workflow_status == 200 else None,
        "backup_care_count": len(backup_care_workflows)
        if workflow_status == 200
        else None,
        "items": [
            pick(item, ("id", "name", "status")) for item in backup_care_workflows
        ],
    }
    if workflow_status >= 400:
        inventory["workflows"]["error"] = safe_error(workflow_payload)

    field_inventory: dict[str, Any] = {}
    for obj in inventory["objects"]["items"]:
        object_key = obj.get("key")
        if not isinstance(object_key, str):
            continue
        status, payload = request_json(
            token,
            "/custom-fields/object-key/" + urllib.parse.quote(object_key, safe=""),
            {"locationId": location_id},
        )
        fields = list_items(payload, ("fields",))
        folders = list_items(payload, ("folders",))
        field_inventory[object_key] = {
            "status": status,
            "fields": [
                pick(
                    item,
                    (
                        "id",
                        "name",
                        "fieldKey",
                        "dataType",
                        "parentId",
                        "showInForms",
                        "isUnique",
                    ),
                )
                for item in fields
            ],
            "folders": [pick(item, ("id", "name", "objectKey")) for item in folders],
        }
        if status >= 400:
            field_inventory[object_key]["error"] = safe_error(payload)
    inventory["custom_fields"] = field_inventory
    return inventory


def require_success(status: int, payload: Any, action: str) -> dict[str, Any]:
    if status not in (200, 201):
        raise RuntimeError(f"{action} failed with HTTP {status}: {safe_error(payload)}")
    if not isinstance(payload, dict):
        raise RuntimeError(f"{action} returned a non-object response")
    return payload


def normalize_option(value: str) -> str:
    normalized = re.sub(r"[^a-z0-9]+", "_", value.lower()).strip("_")
    return normalized or "option"


def field_payload(
    field: dict[str, Any], object_key: str, location_id: str, parent_id: str
) -> dict[str, Any]:
    source_type = str(field.get("type") or "single_line")
    data_types = {
        "single_line": "TEXT",
        "multi_line": "LARGE_TEXT",
        "number": "NUMERICAL",
        "date": "DATE",
        "date_time": "TEXT",
        "single_dropdown": "SINGLE_OPTIONS",
        "checkbox": "CHECKBOX",
        "file_upload": "FILE_UPLOAD",
    }
    data_type = data_types[source_type]
    options = field.get("options") or []
    if data_type == "SINGLE_OPTIONS" and not options:
        data_type = "TEXT"
    if data_type == "CHECKBOX" and not options:
        options = ["accepted"]
    payload: dict[str, Any] = {
        "locationId": location_id,
        "name": field["label"],
        "description": "Backup Care operational field.",
        "placeholder": "",
        "showInForms": True,
        "dataType": data_type,
        "fieldKey": f"{object_key}.{field['key']}",
        "objectKey": object_key,
        "parentId": parent_id,
    }
    if options:
        payload["options"] = [
            {"key": normalize_option(str(option)), "label": str(option).replace("_", " ").title()}
            for option in options
        ]
    if data_type == "FILE_UPLOAD":
        # The live v3 API expects an array here despite its published schema
        # describing a single enum string.
        payload["acceptedFormats"] = ["all"]
        payload["maxFileLimit"] = 3
    if field.get("unique"):
        payload["isUnique"] = True
    return payload


def custom_field_state(
    token: str, location_id: str, object_key: str
) -> tuple[list[dict[str, Any]], list[dict[str, Any]]]:
    status, payload = request_json(
        token,
        "/custom-fields/object-key/" + urllib.parse.quote(object_key, safe=""),
        {"locationId": location_id},
    )
    require_success(status, payload, f"Read custom fields for {object_key}")
    return list_items(payload, ("fields",)), list_items(payload, ("folders",))


def ensure_custom_object(
    token: str, location_id: str, spec: dict[str, Any], objects: list[dict[str, Any]]
) -> tuple[dict[str, Any], bool]:
    object_key = "custom_objects." + spec["internal_name"]
    existing = next((item for item in objects if item.get("key") == object_key), None)
    if existing:
        return existing, False
    payload = {
        "labels": {"singular": spec["display_name"], "plural": spec["plural_name"]},
        "key": object_key,
        "description": f"Chroma Backup Care {spec['display_name'].removeprefix('Backup Care ')} records.",
        "locationId": location_id,
        "primaryDisplayPropertyDetails": {
            "key": spec["primary_display_field"],
            "name": next(
                field["label"]
                for field in spec["fields"]
                if field["key"] == spec["primary_display_field"]
            ),
            "dataType": "TEXT",
            "isUnique": True,
        },
    }
    status, response = request_json(token, "/objects/", {}, method="POST", payload=payload)
    response = require_success(status, response, f"Create {object_key}")
    created = response.get("object") if isinstance(response.get("object"), dict) else response
    if created.get("key") != object_key:
        raise RuntimeError(
            f"Created object key mismatch: expected {object_key}, got {created.get('key')}"
        )
    objects.append(created)
    return created, True


def ensure_field_folder(
    token: str,
    location_id: str,
    object_key: str,
    folders: list[dict[str, Any]],
) -> tuple[dict[str, Any], bool]:
    existing = next((item for item in folders if item.get("name") == DEFAULT_FOLDER_NAME), None)
    if existing:
        return existing, False
    status, response = request_json(
        token,
        "/custom-fields/folder",
        {},
        method="POST",
        payload={
            "objectKey": object_key,
            "name": DEFAULT_FOLDER_NAME,
            "locationId": location_id,
        },
    )
    response = require_success(status, response, f"Create field folder for {object_key}")
    created = next(
        (
            response[key]
            for key in ("folder", "customFieldFolder", "data")
            if isinstance(response.get(key), dict)
        ),
        response,
    )
    if not created.get("id"):
        raise RuntimeError(f"Field folder response for {object_key} has no id")
    folders.append(created)
    return created, True


def ensure_custom_fields(
    token: str,
    location_id: str,
    spec: dict[str, Any],
    object_key: str,
) -> dict[str, Any]:
    fields, folders = custom_field_state(token, location_id, object_key)
    folder, folder_created = ensure_field_folder(
        token, location_id, object_key, folders
    )
    existing_keys = {str(item.get("fieldKey")) for item in fields}
    primary_key = f"{object_key}.{spec['primary_display_field']}"
    created_fields: list[dict[str, Any]] = []
    existing_field_keys: list[str] = []
    for field in spec["fields"]:
        full_key = f"{object_key}.{field['key']}"
        if full_key == primary_key or full_key in existing_keys:
            existing_field_keys.append(full_key)
            continue
        payload = field_payload(field, object_key, location_id, str(folder["id"]))
        status, response = request_json(
            token, "/custom-fields/", {}, method="POST", payload=payload
        )
        response = require_success(status, response, f"Create field {full_key}")
        created = response.get("field") if isinstance(response.get("field"), dict) else response
        if created.get("fieldKey") != full_key:
            raise RuntimeError(
                f"Created field key mismatch: expected {full_key}, got {created.get('fieldKey')}"
            )
        created_fields.append(pick(created, ("id", "name", "fieldKey", "dataType")))
        existing_keys.add(full_key)
    return {
        "folder_id": folder.get("id"),
        "folder_created": folder_created,
        "created_fields": created_fields,
        "existing_field_count": len(existing_field_keys),
        "unique_fields_requiring_ui_confirmation": list(spec.get("unique_fields") or []),
    }


def ensure_associations(
    token: str, location_id: str, object_keys: dict[str, str]
) -> list[dict[str, Any]]:
    status, payload = request_json(token, "/associations/", {"locationId": location_id})
    require_success(status, payload, "Read associations")
    existing = {str(item.get("key")): item for item in list_items(payload, ("associations", "data"))}
    specs = (
        {
            "key": "backup_care_parent_child",
            "firstObjectLabel": "Backup Care Children",
            "firstObjectKey": "contact",
            "secondObjectLabel": "Parent or Guardian",
            "secondObjectKey": object_keys["child"],
        },
        {
            "key": "backup_care_parent_order",
            "firstObjectLabel": "Backup Care Orders",
            "firstObjectKey": "contact",
            "secondObjectLabel": "Booking Parent",
            "secondObjectKey": object_keys["order"],
        },
        {
            "key": "backup_care_order_attendance",
            "firstObjectLabel": "Attendance Units",
            "firstObjectKey": object_keys["order"],
            "secondObjectLabel": "Backup Care Order",
            "secondObjectKey": object_keys["attendance"],
        },
        {
            "key": "backup_care_child_attendance",
            "firstObjectLabel": "Attendance Units",
            "firstObjectKey": object_keys["child"],
            "secondObjectLabel": "Backup Care Child",
            "secondObjectKey": object_keys["attendance"],
        },
    )
    result: list[dict[str, Any]] = []
    for spec in specs:
        item = existing.get(spec["key"])
        created = False
        if not item:
            status, response = request_json(
                token,
                "/associations/",
                {},
                method="POST",
                payload={"locationId": location_id, **spec},
            )
            item = require_success(status, response, f"Create association {spec['key']}")
            created = True
        result.append(
            {
                **pick(
                    item,
                    (
                        "id",
                        "key",
                        "firstObjectKey",
                        "firstObjectLabel",
                        "secondObjectKey",
                        "secondObjectLabel",
                    ),
                ),
                "created": created,
                "cardinality_requires_ui_confirmation": True,
            }
        )
    return result


def ensure_tags(
    token: str, location_id: str, tag_names: list[str]
) -> list[dict[str, Any]]:
    path = f"/locations/{urllib.parse.quote(location_id, safe='')}/tags"
    status, payload = request_json(token, path, {})
    require_success(status, payload, "Read location tags")
    existing = {
        str(item.get("name", "")).lower(): item
        for item in list_items(payload, ("tags", "data"))
    }
    results: list[dict[str, Any]] = []
    for name in tag_names:
        item = existing.get(name.lower())
        created = False
        if not item:
            status, response = request_json(
                token, path, {}, method="POST", payload={"name": name}
            )
            response = require_success(status, response, f"Create tag {name}")
            item = next(
                (
                    response[key]
                    for key in ("tag", "data")
                    if isinstance(response.get(key), dict)
                ),
                response,
            )
            created = True
        results.append({**pick(item, ("id", "name")), "created": created})
    return results


def provision_data_model(
    files: tuple[Path, ...],
    setup_spec_path: Path,
    manifest_path: Path | None,
    confirmation: str | None,
) -> dict[str, Any]:
    if confirmation != APPLY_CONFIRMATION:
        raise RuntimeError(
            f"Provisioning requires --confirm {APPLY_CONFIRMATION}; no changes were made"
        )
    source, token, location_id = usable_credentials(files)
    setup_spec = json.loads(setup_spec_path.read_text(encoding="utf-8"))
    if setup_spec.get("mode") != "preparation_only":
        raise RuntimeError("Setup spec mode changed unexpectedly; refusing live provisioning")

    status, payload = request_json(token, "/objects/", {"locationId": location_id})
    require_success(status, payload, "Read objects before provisioning")
    objects = list_items(payload, ("objects", "schemas", "data"))
    object_results: dict[str, Any] = {}
    object_keys: dict[str, str] = {}
    for spec in setup_spec["custom_objects"]:
        obj, created = ensure_custom_object(token, location_id, spec, objects)
        role = spec["internal_name"].removeprefix("backup_care_")
        object_keys[role] = str(obj["key"])
        object_results[role] = {
            "id": obj.get("id"),
            "key": obj.get("key"),
            "created": created,
            "fields": ensure_custom_fields(token, location_id, spec, str(obj["key"])),
        }

    associations = ensure_associations(token, location_id, object_keys)
    tag_names: list[str] = []
    if manifest_path:
        manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
        tag_names = [str(name) for name in manifest.get("ghl", {}).get("tags", [])]
    tags = ensure_tags(token, location_id, tag_names) if tag_names else []
    return {
        "mode": "live_data_model_provisioning",
        "ok": True,
        "credential_values_printed": False,
        "credential_source": str(source),
        "location_fingerprint": f"{location_id[:4]}...{location_id[-4:]}",
        "objects": object_results,
        "associations": associations,
        "tags": tags,
        "forms_created": 0,
        "workflows_activated": 0,
        "services_created": 0,
    }


def ensure_service_locations(
    token: str, location_id: str, campuses: list[dict[str, Any]]
) -> list[dict[str, Any]]:
    status, payload = request_json(
        token, "/calendars/services/locations", {"locationId": location_id}
    )
    require_success(status, payload, "Read service locations")
    existing = {
        str(item.get("slug")): item
        for item in list_items(payload, ("locations", "serviceLocations", "data"))
    }
    results: list[dict[str, Any]] = []
    for campus in campuses:
        slug = "backup-care-" + str(campus["id"])
        item = existing.get(slug)
        created = False
        if not item:
            status, response = request_json(
                token,
                "/calendars/services/locations",
                {},
                method="POST",
                payload={
                    "locationId": location_id,
                    "name": campus["name"],
                    "slug": slug,
                    "address": campus["address"],
                    "locationType": "offline",
                },
            )
            response = require_success(
                status, response, f"Create service location {campus['id']}"
            )
            item = next(
                (
                    response[key]
                    for key in ("serviceLocation", "location", "data")
                    if isinstance(response.get(key), dict)
                ),
                response,
            )
            created = True
        if not item.get("id"):
            raise RuntimeError(f"Service location {slug} response has no id")
        results.append(
            {
                **pick(item, ("id", "name", "slug", "address", "locationType")),
                "campus_id": campus["id"],
                "created": created,
            }
        )
    return results


def ensure_private_service(token: str, location_id: str) -> dict[str, Any]:
    status, payload = request_json(
        token, "/calendars/services/catalog", {"locationId": location_id}
    )
    require_success(status, payload, "Read service catalog")
    services = list_items(payload, ("services", "data"))
    slug = "backup-care-day-test"
    existing = next((item for item in services if item.get("slug") == slug), None)
    if existing:
        return {**pick(existing, ("id", "name", "slug", "isPrivate", "payment")), "created": False}

    staff_ids = sorted(
        {
            str(staff["id"])
            for service in services
            for staff in (service.get("staff") or [])
            if isinstance(staff, dict) and staff.get("id")
        }
    )
    if not staff_ids:
        raise RuntimeError("No existing Services v2 staff are available for the test service")
    status, response = request_json(
        token,
        "/calendars/services/catalog",
        {},
        method="POST",
        payload={
            "locationId": location_id,
            "name": "BC | Backup Care Day | TEST",
            "slug": slug,
            "staff": [{"id": staff_id} for staff_id in staff_ids],
            "description": (
                "Private acceptance-test service for Chroma Backup Care. "
                "$115 per child per care date. Do not publish or book."
            ),
            "eventColor": "#D4552D",
            "payment": {"amount": 115, "deposit": 115, "depositType": "amount"},
            "serviceDuration": 30,
            "serviceDurationUnit": "mins",
            "preBuffer": 0,
            "preBufferUnit": "mins",
            "postBuffer": 0,
            "postBufferUnit": "mins",
            "isPrivate": True,
            "variations": [],
        },
    )
    response = require_success(status, response, "Create private Backup Care test service")
    service = next(
        (
            response[key]
            for key in ("service", "data")
            if isinstance(response.get(key), dict)
        ),
        response,
    )
    if not service.get("id"):
        raise RuntimeError("Created Backup Care test service response has no id")
    return {
        **pick(service, ("id", "name", "slug", "isPrivate", "payment")),
        "created": True,
    }


def provision_service_shell(
    files: tuple[Path, ...], manifest_path: Path, confirmation: str | None
) -> dict[str, Any]:
    if confirmation != APPLY_CONFIRMATION:
        raise RuntimeError(
            f"Provisioning requires --confirm {APPLY_CONFIRMATION}; no changes were made"
        )
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    if manifest.get("live_changes_allowed") is not True:
        raise RuntimeError(
            "The release manifest does not authorize live changes; no changes were made"
        )
    if manifest.get("ghl", {}).get("calendar_provisioning_enabled") is not True:
        raise RuntimeError(
            "The release manifest disables GHL calendar provisioning; no changes were made"
        )
    source, token, location_id = usable_credentials(files)
    manifest_location_id = str(manifest.get("ghl", {}).get("location_id", "")).strip()
    if not manifest_location_id or location_id != manifest_location_id:
        raise RuntimeError(
            "The selected GHL credential does not match the manifest location; no changes were made"
        )
    locations = ensure_service_locations(token, location_id, manifest["campuses"])
    service = ensure_private_service(token, location_id)
    return {
        "mode": "live_private_service_provisioning",
        "ok": True,
        "credential_values_printed": False,
        "credential_source": str(source),
        "location_fingerprint": f"{location_id[:4]}...{location_id[-4:]}",
        "service": service,
        "service_locations": locations,
        "service_locations_created": sum(1 for item in locations if item["created"]),
        "service_is_private": service.get("isPrivate") is True,
        "payment_charged": False,
        "global_settings_require_ui": True,
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument(
        "command",
        nargs="?",
        choices=(
            "probe",
            "inventory",
            "provision-data-model",
            "provision-service-shell",
        ),
        default="probe",
    )
    parser.add_argument("--env-file", action="append", type=Path, dest="env_files")
    parser.add_argument("--output", type=Path)
    parser.add_argument("--setup-spec", type=Path)
    parser.add_argument("--manifest", type=Path)
    parser.add_argument("--confirm")
    args = parser.parse_args()

    files = tuple(args.env_files or DEFAULT_ENV_FILES)
    if args.command == "provision-data-model":
        if not args.setup_spec:
            parser.error("provision-data-model requires --setup-spec")
        report = provision_data_model(files, args.setup_spec, args.manifest, args.confirm)
    elif args.command == "provision-service-shell":
        if not args.manifest:
            parser.error("provision-service-shell requires --manifest")
        report = provision_service_shell(files, args.manifest, args.confirm)
    elif args.command == "inventory":
        report = safe_inventory(files)
        report["ok"] = all(section.get("status") == 200 for section in (
            report["objects"],
            report["forms"],
            report["services"],
            report["service_locations"],
            report["associations"],
            report["tags"],
            report["workflows"],
        ))
    else:
        report = {
            "mode": "read_only",
            "credential_values_printed": False,
            "sources": [probe_file(path) for path in files],
        }
        report["ok"] = any(source.get("usable") is True for source in report["sources"])

    rendered = json.dumps(report, indent=2)
    if args.output:
        args.output.parent.mkdir(parents=True, exist_ok=True)
        args.output.write_text(rendered + "\n", encoding="utf-8")
    print(rendered)
    return 0 if report["ok"] else 1


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except Exception as exc:
        print(json.dumps({"ok": False, "error": str(exc)}))
        raise SystemExit(1)

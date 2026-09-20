#!/usr/bin/env python3
"""Report Backup Care runtime credential readiness without exposing values."""

from __future__ import annotations

import argparse
import json
from pathlib import Path

from ghl_backup_care_live import parse_env


SECRET_NAMES = {
    "ghl_token": ("CHROMA_BACKUP_CARE_GHL_TOKEN", "GHL_ACCESS_TOKEN", "GHL_PRIVATE_INTEGRATION_KEY"),
}


def classify(role: str, value: str) -> str:
    return "configured" if value else "missing"


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--env", action="append", required=True)
    parser.add_argument("--output")
    args = parser.parse_args()
    sources = []
    found = {role: None for role in SECRET_NAMES}
    for item in args.env:
        path = Path(item)
        values = parse_env(path) if path.exists() else {}
        sources.append({"path": str(path), "exists": path.exists()})
        for role, names in SECRET_NAMES.items():
            if found[role] is not None:
                continue
            for name in names:
                value = values.get(name, "").strip()
                if value:
                    found[role] = {
                        "configured": True,
                        "classification": classify(role, value),
                        "source_path": str(path),
                        "source_variable": name,
                    }
                    break
    for role in found:
        if found[role] is None:
            found[role] = {"configured": False, "classification": "missing"}
    report = {
        "mode": "read_only_secret_safe_runtime_preflight",
        "ok": True,
        "credential_values_printed": False,
        "sources": sources,
        "credentials": found,
        "payment_provider": "ghl_invoice_with_connected_stripe",
        "direct_stripe_credentials_required": False,
        "google_geocoding_required": False,
        "staging_test_acceptance_ready": found["ghl_token"]["configured"],
    }
    encoded = json.dumps(report, indent=2)
    if args.output:
        Path(args.output).write_text(encoded + "\n", encoding="utf-8")
    print(encoded)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

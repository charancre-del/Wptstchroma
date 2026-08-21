from __future__ import annotations

import importlib.util
import json
import tempfile
import unittest
from pathlib import Path
from unittest import mock


ROOT = Path(__file__).resolve().parents[1]
SPEC = importlib.util.spec_from_file_location(
    "ghl_backup_care_live", ROOT / "scripts" / "ghl_backup_care_live.py"
)
assert SPEC and SPEC.loader
MODULE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)


class BackupCareProvisionGuardTests(unittest.TestCase):
    def manifest_path(self, *, live: bool, calendars: bool, location_id: str = "expected") -> Path:
        handle = tempfile.NamedTemporaryFile("w", encoding="utf-8", delete=False)
        with handle:
            json.dump(
                {
                    "live_changes_allowed": live,
                    "ghl": {
                        "calendar_provisioning_enabled": calendars,
                        "location_id": location_id,
                    },
                    "campuses": [],
                },
                handle,
            )
        path = Path(handle.name)
        self.addCleanup(path.unlink, missing_ok=True)
        return path

    def test_live_change_gate_blocks_before_credentials(self):
        path = self.manifest_path(live=False, calendars=True)
        with mock.patch.object(MODULE, "usable_credentials") as credentials:
            with self.assertRaisesRegex(RuntimeError, "does not authorize live changes"):
                MODULE.provision_service_shell(tuple(), path, MODULE.APPLY_CONFIRMATION)
            credentials.assert_not_called()

    def test_calendar_gate_blocks_before_credentials(self):
        path = self.manifest_path(live=True, calendars=False)
        with mock.patch.object(MODULE, "usable_credentials") as credentials:
            with self.assertRaisesRegex(RuntimeError, "disables GHL calendar provisioning"):
                MODULE.provision_service_shell(tuple(), path, MODULE.APPLY_CONFIRMATION)
            credentials.assert_not_called()

    def test_wrong_tenant_blocks_before_mutations(self):
        path = self.manifest_path(live=True, calendars=True, location_id="expected")
        with mock.patch.object(
            MODULE, "usable_credentials", return_value=(Path("env"), "token", "wrong")
        ), mock.patch.object(MODULE, "ensure_service_locations") as locations, mock.patch.object(
            MODULE, "ensure_private_service"
        ) as service:
            with self.assertRaisesRegex(RuntimeError, "does not match the manifest location"):
                MODULE.provision_service_shell(tuple(), path, MODULE.APPLY_CONFIRMATION)
            locations.assert_not_called()
            service.assert_not_called()


if __name__ == "__main__":
    unittest.main()

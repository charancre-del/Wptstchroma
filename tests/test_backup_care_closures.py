import csv
import json
import sys
import tempfile
import unittest
from datetime import date, timedelta
from pathlib import Path
from unittest import mock


ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "scripts"))

import backup_care_closures as closures


class BackupCareClosureTests(unittest.TestCase):
    def setUp(self):
        self.manifest = json.loads(
            (ROOT / "infrastructure/ghl/backup-care/manifest.json").read_text(encoding="utf-8")
        )
        self.today = date(2026, 8, 19)

    def write_csv(self, rows):
        handle = tempfile.NamedTemporaryFile("w", encoding="utf-8", newline="", delete=False)
        with handle:
            writer = csv.DictWriter(handle, fieldnames=closures.CSV_FIELDS)
            writer.writeheader()
            writer.writerows(rows)
        self.addCleanup(Path(handle.name).unlink, missing_ok=True)
        return Path(handle.name)

    def test_validates_all_and_campus_specific_closures(self):
        path = self.write_csv(
            [
                {"closure_date": "2026-09-07", "campus_id": "all", "reason": "Holiday", "status": "active"},
                {"closure_date": "2026-10-02", "campus_id": "grayson", "reason": "Maintenance", "status": "active"},
            ]
        )
        rows = closures.validate_csv(path, self.manifest, today=self.today)
        self.assertEqual("all__2026-09-07", rows[0]["closure_key"])
        self.assertEqual("grayson__2026-10-02", rows[1]["closure_key"])

    def test_rejects_unknown_campus(self):
        path = self.write_csv(
            [{"closure_date": "2026-09-07", "campus_id": "not-a-campus", "reason": "", "status": "active"}]
        )
        with self.assertRaisesRegex(ValueError, "unknown campus_id"):
            closures.validate_csv(path, self.manifest, today=self.today)

    def test_rejects_duplicate_and_out_of_horizon_rows(self):
        duplicate = {"closure_date": "2026-09-07", "campus_id": "all", "reason": "Holiday", "status": "active"}
        path = self.write_csv([duplicate, duplicate])
        with self.assertRaisesRegex(ValueError, "duplicate closure"):
            closures.validate_csv(path, self.manifest, today=self.today)

        late = self.today + timedelta(days=366)
        path = self.write_csv(
            [{"closure_date": late.isoformat(), "campus_id": "all", "reason": "", "status": "active"}]
        )
        with self.assertRaisesRegex(ValueError, "must be between"):
            closures.validate_csv(path, self.manifest, today=self.today)

    def test_apply_requires_exact_confirmation_before_credentials(self):
        rows = [{
            "closure_key": "all__2026-09-07",
            "closure_date": "2026-09-07",
            "campus_id": "all",
            "reason": "Holiday",
            "status": "active",
        }]
        with mock.patch.object(closures, "usable_credentials") as credentials:
            with self.assertRaisesRegex(ValueError, "Apply requires"):
                closures.apply_closures(rows, tuple(), self.manifest, "wrong")
            credentials.assert_not_called()

    def test_create_performs_exact_readback(self):
        properties = {
            "closure_key": "all__2026-09-07",
            "closure_date": "2026-09-07",
            "campus_id": "all",
            "reason": "Holiday",
            "status": "active",
        }
        responses = [
            (200, {"customObjectRecords": []}),
            (201, {"record": {"id": "closure-record-1"}}),
            (200, {"customObjectRecords": [{"id": "closure-record-1", "properties": properties}]}),
        ]
        with mock.patch.object(closures, "request_json", side_effect=responses) as request:
            result = closures.upsert_record("token", "location", properties)
        self.assertEqual("created", result["action"])
        self.assertEqual(3, request.call_count)
        self.assertEqual("2023-02-21", request.call_args_list[1].kwargs["version"])


if __name__ == "__main__":
    unittest.main()

from __future__ import annotations

import importlib.util
import json
import unittest
from copy import deepcopy
from datetime import datetime, timedelta, timezone
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SPEC = importlib.util.spec_from_file_location(
    "backup_care_order", ROOT / "scripts" / "backup_care_order.py"
)
assert SPEC and SPEC.loader
MODULE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)


class BackupCareOrderTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        manifest_path = (
            ROOT / "infrastructure" / "ghl" / "backup-care" / "manifest.json"
        )
        cls.manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
        cls.eastern = timezone(timedelta(hours=-4))
        cls.now = datetime(2026, 8, 17, 7, 0, tzinfo=cls.eastern)

    def order(self) -> dict:
        return {
            "contract_version": 1,
            "client_request_id": "cart_01K2YWRJAEV0D6KQW56KXQ3N6Z",
            "campus_id": "lilburn",
            "parent": {
                "first_name": "Test",
                "last_name": "Parent",
                "email": "parent@example.test",
                "mobile_phone": "+1 404 555 0100",
            },
            "children": [
                {
                    "client_child_id": "child_a",
                    "first_name": "Child",
                    "last_name": "One",
                    "date_of_birth": "2022-03-10",
                    "age_group": "preschool",
                    "enrollment_record_id": "record_child_a",
                    "enrollment_record_complete": True,
                },
                {
                    "client_child_id": "child_b",
                    "first_name": "Child",
                    "last_name": "Two",
                    "date_of_birth": "2020-05-11",
                    "age_group": "school",
                    "enrollment_record_id": "record_child_b",
                    "enrollment_record_complete": True,
                },
            ],
            "attendance": [
                {"client_child_id": "child_a", "care_date": "2026-08-18"},
                {"client_child_id": "child_a", "care_date": "2026-08-19"},
                {"client_child_id": "child_b", "care_date": "2026-08-18"},
                {"client_child_id": "child_b", "care_date": "2026-08-19"},
            ],
            "policy_acceptance": {
                "backup_care_terms": True,
                "full_payment": True,
                "refund_and_reschedule_deadline": True,
                "no_discretionary_exceptions": True,
                "privacy_and_communications": True,
            },
        }

    def test_multiple_children_and_dates_are_one_quote(self) -> None:
        result = MODULE.validate_and_quote(
            self.order(), self.manifest, now=self.now
        )
        self.assertTrue(result["contract_valid"], result["errors"])
        self.assertEqual(result["child_count"], 2)
        self.assertEqual(result["care_date_count"], 2)
        self.assertEqual(result["quote"]["unit_count"], 4)
        self.assertEqual(result["quote"]["total_amount_cents"], 46000)
        keys = {item["line_item_key"] for item in result["quote"]["line_items"]}
        self.assertEqual(len(keys), 4)
        self.assertFalse(result["payment_creation_allowed"])

    def test_authoritative_checks_allow_payment_creation(self) -> None:
        result = MODULE.validate_and_quote(
            self.order(), self.manifest, now=self.now, closures=set(), occupancy={}
        )
        self.assertTrue(result["contract_valid"], result["errors"])
        self.assertTrue(result["payment_creation_allowed"])

    def test_closure_blocks_payment(self) -> None:
        result = MODULE.validate_and_quote(
            self.order(),
            self.manifest,
            now=self.now,
            closures={"lilburn|2026-08-18"},
            occupancy={},
        )
        self.assertFalse(result["contract_valid"])
        self.assertTrue(any("closed for backup care" in error for error in result["errors"]))

    def test_all_campus_closure_blocks_payment(self) -> None:
        result = MODULE.validate_and_quote(
            self.order(),
            self.manifest,
            now=self.now,
            closures={"all|2026-08-18"},
            occupancy={},
        )
        self.assertFalse(result["contract_valid"])
        self.assertTrue(any("closed for backup care" in error for error in result["errors"]))

    def test_capacity_blocks_payment(self) -> None:
        result = MODULE.validate_and_quote(
            self.order(),
            self.manifest,
            now=self.now,
            closures=set(),
            occupancy={"lilburn|2026-08-18": 99},
        )
        self.assertFalse(result["contract_valid"])
        self.assertTrue(any("Capacity is unavailable" in error for error in result["errors"]))

    def test_duplicate_child_date_pair_is_rejected(self) -> None:
        order = self.order()
        order["attendance"].append(deepcopy(order["attendance"][0]))
        result = MODULE.validate_and_quote(order, self.manifest, now=self.now)
        self.assertFalse(result["contract_valid"])
        self.assertTrue(
            any("Duplicate child-date unit" in error for error in result["errors"])
        )

    def test_unknown_child_is_rejected(self) -> None:
        order = self.order()
        order["attendance"][0]["client_child_id"] = "child_missing"
        result = MODULE.validate_and_quote(order, self.manifest, now=self.now)
        self.assertFalse(result["contract_valid"])
        self.assertTrue(
            any("does not match a child" in error for error in result["errors"])
        )

    def test_same_day_order_after_730_is_rejected(self) -> None:
        order = self.order()
        order["attendance"] = [
            {"client_child_id": "child_a", "care_date": "2026-08-17"},
            {"client_child_id": "child_b", "care_date": "2026-08-18"},
        ]
        late = datetime(2026, 8, 17, 7, 31, tzinfo=self.eastern)
        result = MODULE.validate_and_quote(order, self.manifest, now=late)
        self.assertFalse(result["contract_valid"])
        self.assertTrue(
            any("missed the 7:30 AM" in error for error in result["errors"])
        )

    def test_incomplete_enrollment_is_rejected_before_payment(self) -> None:
        order = self.order()
        order["children"][0]["enrollment_record_complete"] = False
        result = MODULE.validate_and_quote(order, self.manifest, now=self.now)
        self.assertFalse(result["contract_valid"])
        self.assertTrue(
            any("enrollment_record_complete" in error for error in result["errors"])
        )

    def test_client_supplied_total_is_rejected(self) -> None:
        order = self.order()
        order["total_amount_cents"] = 1
        result = MODULE.validate_and_quote(order, self.manifest, now=self.now)
        self.assertFalse(result["contract_valid"])
        self.assertTrue(
            any("unsupported fields" in error for error in result["errors"])
        )

    def test_weekend_date_is_rejected(self) -> None:
        order = self.order()
        order["attendance"] = [
            {"client_child_id": "child_a", "care_date": "2026-08-22"},
            {"client_child_id": "child_b", "care_date": "2026-08-24"},
        ]
        result = MODULE.validate_and_quote(order, self.manifest, now=self.now)
        self.assertFalse(result["contract_valid"])
        self.assertTrue(
            any("not an operating day" in error for error in result["errors"])
        )

    def test_date_beyond_365_day_horizon_is_rejected(self) -> None:
        order = self.order()
        order["attendance"] = [
            {"client_child_id": "child_a", "care_date": "2027-08-18"},
            {"client_child_id": "child_b", "care_date": "2026-08-18"},
        ]
        result = MODULE.validate_and_quote(order, self.manifest, now=self.now)
        self.assertFalse(result["contract_valid"])
        self.assertTrue(
            any("exceeds the booking horizon" in error for error in result["errors"])
        )


if __name__ == "__main__":
    unittest.main()

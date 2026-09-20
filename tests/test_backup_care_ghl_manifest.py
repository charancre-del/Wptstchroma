import json
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
MANIFEST_PATH = ROOT / "infrastructure" / "ghl" / "backup-care" / "manifest.json"
INVENTORY_PATH = ROOT / "reports" / "backup-care-ghl-final-inventory-20260819.json"
ACCEPTANCE_PATH = ROOT / "reports" / "backup-care-ghl-acceptance-20260819.json"
CALENDAR_INVENTORY_PATH = ROOT / "reports" / "backup-care-calendar-inventory-20260820.json"
CALENDAR_ACCEPTANCE_PATH = ROOT / "reports" / "backup-care-calendar-matrix-acceptance-20260820.json"
CALENDAR_FIELD_PATH = ROOT / "reports" / "backup-care-calendar-field-20260820.json"
WORKFLOW_READBACK_PATH = ROOT / "reports" / "backup-care-workflow-readback-20260820.json"
WORKFLOW_UI_READBACK_PATH = ROOT / "reports" / "backup-care-workflow-ui-readback-20260820.json"
RUNTIME_PREFLIGHT_PATH = ROOT / "reports" / "backup-care-runtime-preflight-20260820.json"
SETUP_SPEC_PATH = ROOT / "infrastructure" / "ghl" / "backup-care" / "ghl-setup-spec.json"
CART_JS_PATH = ROOT / "chroma-plugins" / "chroma-backup-care" / "assets" / "backup-care-cart.js"


class BackupCareGhlManifestTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.manifest = json.loads(MANIFEST_PATH.read_text(encoding="utf-8"))
        cls.inventory = json.loads(INVENTORY_PATH.read_text(encoding="utf-8"))
        cls.acceptance = json.loads(ACCEPTANCE_PATH.read_text(encoding="utf-8"))
        cls.calendar_inventory = json.loads(CALENDAR_INVENTORY_PATH.read_text(encoding="utf-8"))
        cls.calendar_acceptance = json.loads(CALENDAR_ACCEPTANCE_PATH.read_text(encoding="utf-8"))
        cls.calendar_field = json.loads(CALENDAR_FIELD_PATH.read_text(encoding="utf-8"))
        cls.workflow_readback = json.loads(WORKFLOW_READBACK_PATH.read_text(encoding="utf-8"))
        cls.workflow_ui_readback = json.loads(
            WORKFLOW_UI_READBACK_PATH.read_text(encoding="utf-8")
        )
        cls.runtime_preflight = json.loads(RUNTIME_PREFLIGHT_PATH.read_text(encoding="utf-8"))
        cls.setup_spec = json.loads(SETUP_SPEC_PATH.read_text(encoding="utf-8"))

    def test_live_object_and_association_ids_match_readback(self):
        ghl = self.manifest["ghl"]
        accepted_objects = self.acceptance["objects"]
        self.assertEqual(ghl["custom_object_ids"]["child"], accepted_objects["child_id"])
        self.assertEqual(ghl["custom_object_ids"]["order"], accepted_objects["order_id"])
        self.assertEqual(
            ghl["custom_object_ids"]["attendance"], accepted_objects["attendance_id"]
        )
        self.assertEqual(ghl["custom_object_ids"]["closure"], accepted_objects["closure_id"])
        self.assertEqual(accepted_objects["booking_unique_field_rules_confirmed"], 4)
        self.assertEqual(accepted_objects["total_unique_field_rules_confirmed"], 5)
        self.assertEqual(
            ghl["association_ids"], self.acceptance["associations"]["ids"]
        )
        self.assertTrue(self.acceptance["associations"]["one_to_many_confirmed"])

    def test_private_service_and_all_campus_locations_match_readback(self):
        ghl = self.manifest["ghl"]
        service_config = ghl["services_v2"]
        live_services = {
            item["id"]: item for item in self.inventory["services"]["items"]
        }
        service = live_services[service_config["service_id"]]
        self.assertTrue(service["isPrivate"])
        self.assertEqual(service["payment"]["amount"], 115)

        live_location_ids = {
            item["id"] for item in self.inventory["service_locations"]["items"]
        }
        configured_locations = service_config["service_location_ids"]
        self.assertEqual(len(configured_locations), 24)
        self.assertEqual(len(set(configured_locations.values())), 24)
        self.assertTrue(set(configured_locations.values()) <= live_location_ids)

    def test_tags_match_and_activation_remains_blocked(self):
        ghl = self.manifest["ghl"]
        self.assertEqual(ghl["tag_ids"], self.acceptance["tags"]["ids"])

        self.assertFalse(self.manifest["live_changes_allowed"])
        self.assertFalse(ghl["workflow_activation_enabled"])
        self.assertFalse(ghl["services_v2"]["published"])
        self.assertFalse(self.manifest["ghl_payment"]["test_payment_verified"])
        self.assertEqual(
            self.manifest["ghl_payment"]["provider"],
            "stripe_connected_inside_ghl",
        )
        self.assertEqual(self.acceptance["stripe_test"]["amount_cents"], 11500)
        self.assertEqual(self.acceptance["workflows"]["count"], 8)
        self.assertEqual(self.acceptance["workflows"]["status"], "draft")
        workflow_readback = self.acceptance["workflows"]["live_readback"]
        self.assertTrue(workflow_readback["all_detail_requests_succeeded"])
        self.assertTrue(workflow_readback["all_trigger_requests_succeeded"])
        self.assertTrue(workflow_readback["all_draft"])
        self.assertEqual(workflow_readback["workflows_with_zero_automatic_triggers"], 8)
        self.assertEqual(workflow_readback["workflows_with_one_executable_tag_action"], 8)
        self.assertEqual(workflow_readback["workflows_with_contact_context"], 8)
        workflow_ids = {item["event_key"]: item["id"] for item in self.manifest["workflow_specification"]}
        self.assertEqual(workflow_ids, self.acceptance["workflows"]["ids"])
        self.assertEqual(set(ghl["form_ids"].values()), set(self.acceptance["forms"].values()))
        self.assertFalse(self.acceptance["native_checkout_acceptance"]["passed"])
        self.assertEqual(self.manifest["schema_version"], 10)
        self.assertNotIn("booking_terms", ghl["public_form_ids"])
        self.assertEqual(ghl["retired_form_ids"]["booking_terms"], ghl["form_ids"]["booking_terms"])
        gate_status = {gate["id"]: gate["status"] for gate in self.manifest["deployment_gates"]}
        self.assertEqual(gate_status["ghl_inactive_workflows"], "complete")
        self.assertEqual(gate_status["parent_email_verification_delivery"], "pending")
        self.assertEqual(gate_status["ghl_booking_terms_form_retired"], "pending")

    def test_calendar_projection_routes_all_campuses_and_four_child_dates(self):
        self.assertTrue(self.calendar_inventory["ok"])
        self.assertEqual(self.calendar_inventory["campus_count"], 24)
        self.assertEqual(self.calendar_inventory["single_member_calendar_count"], 24)
        configured = {
            campus["id"]: (
                campus["source_calendar_id"],
                campus["backup_care_calendar_user_id"],
            )
            for campus in self.manifest["campuses"]
        }
        inventoried = {
            campus["campus_id"]: (
                campus["calendar_id"],
                campus["team_member_ids"][0],
            )
            for campus in self.calendar_inventory["campuses"]
        }
        self.assertEqual(configured, inventoried)

        self.assertTrue(self.calendar_acceptance["ok"])
        self.assertEqual(
            self.calendar_acceptance["matrix"]["created_appointments"], 4
        )
        self.assertTrue(self.calendar_acceptance["cleanup_ok"])
        self.assertFalse(self.calendar_acceptance["payment_attempted"])
        self.assertFalse(self.calendar_acceptance["workflow_activation_attempted"])
        self.assertEqual(
            self.calendar_field["field_key"],
            "custom_objects.backup_care_attendance.ghl_calendar_event_id",
        )
        self.assertTrue(self.calendar_field["ok"])

    def test_current_workflow_and_runtime_blockers_are_not_hidden(self):
        ghl = self.manifest["ghl"]
        self.assertTrue(ghl["workflows_created_inactive"])
        self.assertEqual(self.workflow_readback["expected_workflow_count"], 8)
        self.assertEqual(self.workflow_readback["expected_ids_found"], 0)
        self.assertEqual(self.workflow_ui_readback["workflow_count"], 8)
        self.assertTrue(self.workflow_ui_readback["all_draft"])
        self.assertTrue(self.workflow_ui_readback["all_zero_automatic_triggers"])
        self.assertTrue(self.workflow_ui_readback["all_tag_actions_verified"])
        self.assertFalse(self.workflow_ui_readback["workflow_activation_attempted"])
        configured_ids = {
            item["id"] for item in self.manifest["workflow_specification"]
        }
        ui_ids = {item["id"] for item in self.workflow_ui_readback["workflows"]}
        self.assertEqual(configured_ids, ui_ids)
        self.assertFalse(self.runtime_preflight["staging_test_acceptance_ready"])
        self.assertFalse(self.runtime_preflight["direct_stripe_credentials_required"])
        self.assertFalse(self.runtime_preflight["google_geocoding_required"])

    def test_ghl_invoice_payment_contract(self):
        payment = self.manifest["ghl_payment"]
        self.assertEqual(payment["checkout_mode"], "ghl_test_invoice_sent_by_email")
        self.assertEqual(payment["test_parent_email"], "charancre@gmail.com")
        self.assertEqual(payment["test_matrix_amount_cents"], 46000)
        self.assertEqual(payment["reconciliation_interval_minutes"], 5)
        self.assertFalse(payment["test_payment_verified"])
        self.assertFalse(self.setup_spec["services_v2_pilot"]["stripe_test_payment_verified"])

    def test_enrollment_handoff_does_not_put_family_pii_in_url(self):
        source = CART_JS_PATH.read_text(encoding="utf-8")
        handoff = source[source.index("function renderErrors"):source.index("function renderQuote")]
        self.assertIn("child_record_key", handoff)
        for forbidden in (
            "order.parent.first_name",
            "order.parent.last_name",
            "order.parent.mobile_phone",
            "order.parent.email",
            "child_first_name",
            "child_last_name",
            "child_date_of_birth",
            "record_status",
        ):
            self.assertNotIn(forbidden, handoff)


if __name__ == "__main__":
    unittest.main()

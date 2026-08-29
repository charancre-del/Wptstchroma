import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "chroma-excellence-theme"
TEMPLATE_PATH = THEME / "page-backup-care.php"
ASSET_LOADER_PATH = THEME / "inc" / "backup-care.php"
BOOKING_JS_PATH = THEME / "assets" / "js" / "backup-care-ghl.js"


class BackupCareThemeGhlTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.template = TEMPLATE_PATH.read_text(encoding="utf-8")
        cls.asset_loader = ASSET_LOADER_PATH.read_text(encoding="utf-8")
        cls.booking_js = BOOKING_JS_PATH.read_text(encoding="utf-8")

    def test_template_uses_theme_owned_booking_mount(self):
        self.assertIn("data-chroma-backup-care-ghl", self.template)
        self.assertNotIn("shortcode_exists", self.template)
        self.assertNotIn("do_shortcode", self.template)
        self.assertNotIn("chroma_backup_care_cart", self.template)

    def test_booking_flow_has_no_wordpress_api_dependency(self):
        for forbidden in ("wp-json", "rest_url", "admin-ajax", "ChromaBackupCareCart"):
            self.assertNotIn(forbidden, self.booking_js)
        self.assertIn("config.formUrl", self.booking_js)
        self.assertIn("https://link.msgsndr.com/js/form_embed.js", self.booking_js)

    def test_child_date_matrix_and_business_rules_are_configured(self):
        self.assertIn("state.children.length * state.dates.length", self.booking_js)
        self.assertIn("'unitAmountCents' => 11500", self.asset_loader)
        self.assertIn("'bookingHorizonDays' => 365", self.asset_loader)
        self.assertIn("'minimumNoticeMinutes' => 120", self.asset_loader)
        self.assertIn("'sameDayDeadline' => '07:30'", self.asset_loader)
        self.assertIn("'dropoffCutoff' => '09:30'", self.asset_loader)

    def test_checkout_is_a_native_ghl_embed(self):
        self.assertIn("api.leadconnectorhq.com/widget/form/", self.asset_loader)
        self.assertIn("cbc-ghl-frame", self.booking_js)
        self.assertIn("reservation_details", self.booking_js)


if __name__ == "__main__":
    unittest.main()

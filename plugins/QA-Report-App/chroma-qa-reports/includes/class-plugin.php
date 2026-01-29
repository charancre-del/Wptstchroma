<?php
/**
 * Core Plugin Class
 *
 * @package ChromaQAReports
 */

namespace ChromaQA;

/**
 * Main plugin class that initializes all components.
 */
class Plugin
{

    /**
     * Plugin version.
     *
     * @var string
     */
    protected $version;

    /**
     * Admin instance.
     *
     * @var Admin\Admin_Menu
     */
    protected $admin;

    /**
     * REST API controller.
     *
     * @var API\REST_Controller
     */
    protected $api;

    /**
     * Initialize the plugin.
     */
    public function __construct()
    {
        $this->version = CQA_VERSION;
    }

    /**
     * Run the plugin.
     */
    public function run()
    {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_api_hooks();
        $this->define_cron_hooks();
        $this->init_enhancements();
        $this->check_version();

        // Manifest serving
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'serve_manifest']);
        add_action('init', [$this, 'add_manifest_rewrite']);
    }

    /**
     * Check for plugin updates and run activation if needed.
     */
    private function check_version()
    {
        // Safe upgrade check
        require_once CQA_PLUGIN_DIR . 'includes/class-upgrade-manager.php';
        Upgrade_Manager::check_and_run();
    }

    /**
     * Load required dependencies.
     */
    private function load_dependencies()
    {
        // Services
        require_once CQA_PLUGIN_DIR . 'includes/services/class-cleanup-service.php';

        // Models
        require_once CQA_PLUGIN_DIR . 'includes/models/class-school.php';
        require_once CQA_PLUGIN_DIR . 'includes/models/class-report.php';
        require_once CQA_PLUGIN_DIR . 'includes/models/class-checklist-response.php';
        require_once CQA_PLUGIN_DIR . 'includes/models/class-photo.php';

        // Template model (Phase 12)
        if (file_exists(CQA_PLUGIN_DIR . 'includes/models/class-template.php')) {
            require_once CQA_PLUGIN_DIR . 'includes/models/class-template.php';
        }

        // Checklists
        require_once CQA_PLUGIN_DIR . 'includes/checklists/class-checklist-manager.php';
        require_once CQA_PLUGIN_DIR . 'includes/checklists/class-classroom-checklist.php';

        // Auth
        require_once CQA_PLUGIN_DIR . 'includes/auth/class-google-oauth.php';
        require_once CQA_PLUGIN_DIR . 'includes/auth/class-user-roles.php';

        // Integrations
        require_once CQA_PLUGIN_DIR . 'includes/integrations/class-google-drive.php';

        // AI
        require_once CQA_PLUGIN_DIR . 'includes/ai/class-gemini-service.php';
        require_once CQA_PLUGIN_DIR . 'includes/ai/class-document-parser.php';
        require_once CQA_PLUGIN_DIR . 'includes/ai/class-executive-summary.php';

        // AI Enhancements (Phase 16)
        if (file_exists(CQA_PLUGIN_DIR . 'includes/ai/class-photo-analyzer.php')) {
            require_once CQA_PLUGIN_DIR . 'includes/ai/class-photo-analyzer.php';
        }
        if (file_exists(CQA_PLUGIN_DIR . 'includes/ai/class-comparative-insights.php')) {
            require_once CQA_PLUGIN_DIR . 'includes/ai/class-comparative-insights.php';
        }

        // Export
        require_once CQA_PLUGIN_DIR . 'includes/export/class-pdf-generator.php';

        // Notifications (Phase 13)
        if (file_exists(CQA_PLUGIN_DIR . 'includes/notifications/class-email-notifications.php')) {
            require_once CQA_PLUGIN_DIR . 'includes/notifications/class-email-notifications.php';
        }
        if (file_exists(CQA_PLUGIN_DIR . 'includes/notifications/class-reminders.php')) {
            require_once CQA_PLUGIN_DIR . 'includes/notifications/class-reminders.php';
        }

        // Workflow (Phase 13)
        if (file_exists(CQA_PLUGIN_DIR . 'includes/workflow/class-approval-workflow.php')) {
            require_once CQA_PLUGIN_DIR . 'includes/workflow/class-approval-workflow.php';
        }

        // Analytics (Phase 15)
        if (file_exists(CQA_PLUGIN_DIR . 'includes/analytics/class-trends.php')) {
            require_once CQA_PLUGIN_DIR . 'includes/analytics/class-trends.php';
        }

        // Utils (Phase 14)
        if (file_exists(CQA_PLUGIN_DIR . 'includes/utils/class-location.php')) {
            require_once CQA_PLUGIN_DIR . 'includes/utils/class-location.php';
        }
        if (file_exists(CQA_PLUGIN_DIR . 'includes/utils/class-photo-comparison.php')) {
            require_once CQA_PLUGIN_DIR . 'includes/utils/class-photo-comparison.php';
        }

        // Frontend
        if (file_exists(CQA_PLUGIN_DIR . 'public/class-frontend-controller.php')) {
            require_once CQA_PLUGIN_DIR . 'public/class-frontend-controller.php';
        }

        // Admin (Conditional)
        if (is_admin()) {
            require_once CQA_PLUGIN_DIR . 'admin/class-admin-menu.php';
        }

        // Settings
        require_once CQA_PLUGIN_DIR . 'includes/class-settings.php';

        // API (Conditional)
        if (defined('REST_REQUEST') && REST_REQUEST) {
            require_once CQA_PLUGIN_DIR . 'includes/api/class-rest-controller.php';
        } elseif (is_admin()) {
            // Also need API controller in admin for some shared logic? 
            // Usually REST-related logic is only needed for REST, but admin might register routes.
            require_once CQA_PLUGIN_DIR . 'includes/api/class-rest-controller.php';
        }
    }

    /**
     * Set plugin text domain for internationalization.
     */
    private function set_locale()
    {
        add_action('init', function () {
            load_plugin_textdomain(
                'chroma-qa-reports',
                false,
                dirname(CQA_PLUGIN_BASENAME) . '/languages/'
            );
        });
    }

    /**
     * Register admin hooks.
     */
    private function define_admin_hooks()
    {
        if (is_admin()) {
            $this->admin = new Admin\Admin_Menu();

            add_action('admin_menu', [$this->admin, 'register_menu']);
            add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_styles']);
            add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_scripts']);

            // React app enqueue (Phase 1 React Migration)
            add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_react_app']);
        }

        // Add PWA manifest to head
        add_action('admin_head', [$this, 'add_pwa_manifest']);
    }

    private function define_api_hooks()
    {
        $this->api = new API\REST_Controller();
        add_action('rest_api_init', [$this->api, 'register_routes']);
    }

    /**
     * Register Cron hooks.
     */
    private function define_cron_hooks()
    {
        add_action('cqa_cleanup_orphaned_media', [$this, 'cleanup_orphaned_media']);
        add_action('cqa_daily_cleanup', ['ChromaQA\Services\Cleanup_Service', 'daily_cleanup']);

        if (!wp_next_scheduled('cqa_cleanup_orphaned_media')) {
            wp_schedule_event(time(), 'weekly', 'cqa_cleanup_orphaned_media');
        }
    }

    /**
     * Terminate the "Ghost Photos" - Cleanup DB records without reports.
     */
    public function cleanup_orphaned_media()
    {
        global $wpdb;
        $photos_table = $wpdb->prefix . 'cqa_photos';
        $reports_table = $wpdb->prefix . 'cqa_reports';

        $orphans = $wpdb->get_results("
            SELECT p.id 
            FROM {$photos_table} p 
            LEFT JOIN {$reports_table} r ON p.report_id = r.id 
            WHERE r.id IS NULL
        ");

        if (!empty($orphans)) {
            foreach ($orphans as $orphan) {
                $wpdb->delete($photos_table, ['id' => $orphan->id], ['%d']);
            }
        }
    }

    /**
     * AI logic is now contained within services.
     */

    /**
     * Initialize enhancement modules.
     */
    private function init_enhancements()
    {
        // Phase 13: Notifications
        if (class_exists('ChromaQA\\Notifications\\Email_Notifications')) {
            Notifications\Email_Notifications::init();
        }
        if (class_exists('ChromaQA\\Notifications\\Reminders')) {
            Notifications\Reminders::init();
        }

        // Phase 13: Workflow
        if (class_exists('ChromaQA\\Workflow\\Approval_Workflow')) {
            Workflow\Approval_Workflow::init();
        }

        // Phase 14: Location
        if (class_exists('ChromaQA\\Utils\\Location')) {
            Utils\Location::init();
        }

        // Phase 15: Analytics
        if (class_exists('ChromaQA\\Analytics\\Trends')) {
            Analytics\Trends::init();
        }

        // Phase 16: AI Enhancements
        if (class_exists('ChromaQA\\AI\\Photo_Analyzer')) {
            AI\Photo_Analyzer::init();
        }
        if (class_exists('ChromaQA\\AI\\Comparative_Insights')) {
            AI\Comparative_Insights::init();
        }

        // Frontend
        if (class_exists('ChromaQA\\Frontend\\Frontend_Controller')) {
            Frontend\Frontend_Controller::init();
        }
    }

    /**
     * Register rewrite rules.
     */
    public function add_manifest_rewrite()
    {
        add_rewrite_rule('^qa-reports/manifest\.json', 'index.php?cqa_manifest=1', 'top');
    }

    /**
     * Add query vars.
     */
    public function add_query_vars($vars)
    {
        $vars[] = 'cqa_manifest';
        return $vars;
    }

    /**
     * Serve manifest with correct headers.
     */
    public function serve_manifest()
    {
        if (get_query_var('cqa_manifest')) {
            $manifest_path = CQA_PLUGIN_DIR . 'manifest.json';

            if (file_exists($manifest_path)) {
                header('Content-Type: application/manifest+json; charset=utf-8');
                readfile($manifest_path);
                exit;
            }
        }
    }

    /**
     * Add PWA manifest link to admin head.
     */
    public function add_pwa_manifest()
    {
        // Use dynamic URL to ensure correct headers
        $manifest_url = home_url('/qa-reports/manifest.json');
        echo '<link rel="manifest" href="' . esc_url($manifest_url) . '">';
        echo '<meta name="theme-color" content="#6366f1">';
        echo '<meta name="apple-mobile-web-app-capable" content="yes">';
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">';
    }

    /**
     * Get the plugin version.
     *
     * @return string
     */
    public function get_version()
    {
        return $this->version;
    }
}

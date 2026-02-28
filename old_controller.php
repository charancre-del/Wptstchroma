<?php
/**
 * Front-End Report Controller
 * 
 * Handles public-facing report submission without wp-admin
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Frontend;

/**
 * Front-End Report Controller
 */
class Frontend_Controller
{

    /**
     * Initialize front-end functionality.
     */
    public static function init()
    {
        add_action('init', [self::class, 'register_rewrites']);
        error_log('CQA DEBUG: Frontend_Controller::init called');
        add_filter('query_vars', [self::class, 'add_query_vars']);
        add_action('template_redirect', [self::class, 'handle_routes']);

        // AJAX handlers
        add_action('wp_ajax_cqa_frontend_login', [self::class, 'ajax_login']);
        add_action('wp_ajax_nopriv_cqa_frontend_login', [self::class, 'ajax_login']);

        // OAuth Callback
        add_action('wp_ajax_cqa_oauth_callback', [self::class, 'oauth_callback']);
        add_action('wp_ajax_nopriv_cqa_oauth_callback', [self::class, 'oauth_callback']);

        // Exclude from sitemaps
        add_filter('wp_sitemaps_add_provider', [self::class, 'exclude_from_sitemap'], 10, 2);
        add_filter('wpseo_exclude_from_sitemap_by_url', [self::class, 'yoast_exclude_urls']);
        add_filter('rank_math/sitemap/exclude_urls', [self::class, 'rankmath_exclude_urls']);

        // Add noindex to QA pages
        add_action('wp_head', [self::class, 'add_noindex_meta']);
    }

    /**
     * Exclude QA pages from WordPress core sitemap.
     */
    public static function exclude_from_sitemap($provider, $name)
    {
        return $provider;
    }

    /**
     * Exclude URLs from Yoast sitemap.
     */
    public static function yoast_exclude_urls($excluded)
    {
        $excluded[] = home_url('/qa-reports/');
        return $excluded;
    }

    /**
     * Exclude URLs from Rank Math sitemap.
     */
    public static function rankmath_exclude_urls($urls)
    {
        $urls[] = home_url('/qa-reports/');
        return $urls;
    }

    /**
     * Add noindex meta tag to QA pages.
     */
    public static function add_noindex_meta()
    {
        if (get_query_var('cqa_page')) {
            echo '<meta name="robots" content="noindex, nofollow">' . "\n";
        }
    }

    /**
     * Register custom URL rewrites.
     */
    public static function register_rewrites()
    {
        error_log('CQA DEBUG: register_rewrites called');
        // Login Route
        add_rewrite_rule('^qa-reports/login/?$', 'index.php?cqa_page=login', 'top');

        // Auth Callback
        add_rewrite_rule('^qa-reports/auth/callback/?$', 'index.php?cqa_page=oauth_callback', 'top');

        // All other routes map to the React App (Dashboard)
        add_rewrite_rule('^qa-reports/.*', 'index.php?cqa_page=dashboard', 'top');
        add_rewrite_rule('^qa-reports/?$', 'index.php?cqa_page=dashboard', 'top');
    }

    /**
     * Add custom query vars.
     */
    public static function add_query_vars($vars)
    {
        error_log('CQA DEBUG: add_query_vars called');
        $vars[] = 'cqa_page';
        return $vars;
    }

    /**
     * Handle front-end routes.
     */
    public static function handle_routes()
    {
        $page = get_query_var('cqa_page');
        if (!empty($page)) {
            error_log('CQA DEBUG: handle_routes triggered for page: ' . $page);
        }

        if (empty($page)) {
            return;
        }

        // Check authentication for protected pages
        $public_pages = ['login', 'oauth_callback'];
        if (!in_array($page, $public_pages) && !is_user_logged_in()) {
            wp_redirect(home_url('/qa-reports/login/'));
            exit;
        }

        // Check capabilities for protected pages
        if (
            !in_array($page, $public_pages) && !current_user_can('cqa_create_reports')
            && !current_user_can('cqa_view_all_reports')
            && !current_user_can('cqa_view_own_reports')
        ) { // Ensure basic view cap is checked
            wp_die(__('You do not have permission to access QA Reports.', 'chroma-qa-reports'));
        }

        // Load the appropriate template
        self::load_template($page);
        exit;
    }

    /**
     * Load front-end template.
     */
    private static function load_template($page)
    {
        // Enqueue assets
        self::enqueue_assets($page);

        include CQA_PLUGIN_DIR . 'public/views/header.php';

        if ($page === 'login') {
            include CQA_PLUGIN_DIR . 'public/views/login.php';
        } elseif ($page === 'oauth_callback') {
            self::oauth_callback();
        } else {
            // Render React App Container for all other routes
            ?>
            <div class="wrap cqa-react-wrap" style="min-height: 80vh;">
                <div id="cqa-react-app" role="application">
                    <div class="cqa-loading-placeholder"
                        style="display: flex; align-items: center; justify-content: center; min-height: 400px;">
                        <span class="spinner is-active"></span>
                        <p>
                            <?php esc_html_e('Loading QA Reports...', 'chroma-qa-reports'); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php
        }

        include CQA_PLUGIN_DIR . 'public/views/footer.php';
    }

    /**
     * Enqueue front-end assets.
     */
    private static function enqueue_assets($page)
    {
        // Enqueue styles
        wp_enqueue_style('cqa-frontend-styles', CQA_PLUGIN_URL . 'public/css/frontend-styles.css', [], CQA_VERSION);

        // If Login page, load legacy logic
        if ($page === 'login') {
            wp_enqueue_script('cqa-frontend', CQA_PLUGIN_URL . 'public/js/frontend-app.js', ['jquery'], CQA_VERSION, true);
            wp_localize_script('cqa-frontend', 'cqaFrontend', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'homeUrl' => home_url('/qa-reports/'),
                'strings' => ['loading' => 'Logging in...', 'error' => 'Login failed']
            ]);
            return;
        }

        // For React App routes
        $asset_file = CQA_PLUGIN_DIR . 'build/index.asset.php';
        if (file_exists($asset_file)) {
            $assets = include $asset_file;

            // React Styles
            wp_enqueue_style('cqa-react-app', CQA_PLUGIN_URL . 'build/index.css', [], $assets['version']);

            // React Script
            wp_enqueue_script(
                'cqa-runtime-guard',
                CQA_PLUGIN_URL . 'public/js/cqa-runtime-guard.js',
                [],
                CQA_VERSION,
                true
            );

            wp_enqueue_script(
                'cqa-react-app',
                CQA_PLUGIN_URL . 'build/index.js',
                array_merge(['cqa-runtime-guard'], $assets['dependencies']),
                $assets['version'],
                true
            );

            // Localize Data (Ported from Admin_Menu)
            $user = wp_get_current_user();
            wp_localize_script('cqa-react-app', 'cqaData', [
                'restUrl' => rest_url('cqa/v1/'),
                'nonce' => wp_create_nonce('wp_rest'),
                'adminUrl' => admin_url('admin.php'), // React app might use this for back-links
                'pluginUrl' => CQA_PLUGIN_URL,
                'debug' => defined('CQA_DEBUG') ? CQA_DEBUG : false,
                'user' => [
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'role' => self::get_user_role_label($user),
                ],
                // Assume all capabilities enabled for frontend since we checked at route level
                'capabilities' => self::get_user_capabilities($user),
                'flags' => [
                    'dashboard' => true,
                    'schools' => true,
                    'reports' => true,
                    'wizard' => true,
                    'settings' => true
                ],
                'settings' => [
                    'googleConnected' => !empty(get_user_meta($user->ID, 'cqa_google_access_token', true)),
                ],
            ]);
        }
    }

    /**
     * Get user capabilities (Helper).
     */
    private static function get_user_capabilities($user)
    {
        $cqa_caps = [
            'cqa_view_own_reports',
            'cqa_view_all_reports',
            'cqa_create_reports',
            'cqa_edit_all_reports',
            'cqa_delete_reports',
            'cqa_approve_reports',
            'cqa_manage_schools',
            'cqa_manage_settings',
        ];

        $capabilities = [];
        foreach ($cqa_caps as $cap) {
            $capabilities[$cap] = $user->has_cap($cap);
        }
        return $capabilities;
    }

    /**
     * Get user role label (Helper).
     */
    private static function get_user_role_label($user)
    {
        $role_labels = [
            'cqa_super_admin' => 'Super Admin',
            'cqa_regional_director' => 'Regional Director',
            'cqa_qa_officer' => 'QA Officer',
            'cqa_program_management' => 'Program Management',
            'administrator' => 'Administrator',
        ];
        foreach ($user->roles as $role) {
            if (isset($role_labels[$role]))
                return $role_labels[$role];
        }
        return 'User';
    }

    /**
     * AJAX login handler.
     */
    public static function ajax_login()
    {
        check_ajax_referer('cqa_frontend_login', 'nonce');

        $username = sanitize_user($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if (empty($username) || empty($password)) {
            wp_send_json_error(['message' => __('Please enter username and password.', 'chroma-qa-reports')]);
        }

        $user = wp_signon([
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember,
        ]);

        if (is_wp_error($user)) {
            wp_send_json_error(['message' => __('Invalid username or password.', 'chroma-qa-reports')]);
        }

        // Check if user has QA capabilities
        if (
            !user_can($user, 'cqa_create_reports')
            && !user_can($user, 'cqa_view_all_reports')
            && !user_can($user, 'cqa_view_own_reports')
        ) {
            wp_logout();
            wp_send_json_error(['message' => __('You do not have access to QA Reports.', 'chroma-qa-reports')]);
        }

        wp_send_json_success([
            'redirect' => home_url('/qa-reports/'),
            'user' => [
                'name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID),
            ],
        ]);
    }

    /**
     * OAuth callback handler.
     */
    public static function oauth_callback()
    {
        // OAuth logic (simplified for Brevity - actual implementation invokes Google_OAuth class)
        if (!isset($_GET['code'])) {
            wp_redirect(home_url('/qa-reports/login/?error=missing_code'));
            exit;
        }

        if (class_exists('ChromaQA\Auth\Google_OAuth')) {
            $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
            $user_id = \ChromaQA\Auth\Google_OAuth::handle_login($_GET['code'], $state);
            if (!is_wp_error($user_id)) {
                wp_safe_redirect(home_url('/qa-reports/'));
                exit;
            }
        }
        wp_redirect(home_url('/qa-reports/login/?error=oauth_failed'));
        exit;
    }

    /**
     * Flush rules helper.
     */
    public static function flush_rules()
    {
        self::register_rewrites();
        flush_rewrite_rules();
    }
}

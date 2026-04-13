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
     * Public portal base slugs.
     *
     * Keep both values for backwards compatibility with older links that
     * referenced the singular slug.
     *
     * @var string[]
     */
    private const ROUTE_BASES = ['qa-reports', 'qa-report'];

    /**
     * Rewrite rules schema version.
     *
     * Bump this when route rules change so active installs get a one-time
     * rewrite flush without requiring manual permalink saves.
     */
    private const REWRITE_VERSION = '2';

    /**
     * Initialize front-end functionality.
     */
    public static function init()
    {
        add_action('init', [self::class, 'register_rewrites']);
        add_action('init', [self::class, 'maybe_flush_rewrite_rules'], 99);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CQA DEBUG: Frontend_Controller::init called');
        }
        add_filter('query_vars', [self::class, 'add_query_vars']);
        add_action('template_redirect', [self::class, 'handle_routes']);
        add_filter('show_admin_bar', [self::class, 'maybe_hide_admin_bar_on_qa_portal']);

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
        add_filter('redirect_canonical', [self::class, 'preserve_qa_route_canonical'], 1, 2);
        add_filter('pre_option_chroma_seo_redirect_canonical', [self::class, 'disable_custom_canonical_on_qa_routes'], 10, 3);

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
        foreach (self::ROUTE_BASES as $base) {
            $excluded[] = home_url('/' . $base . '/');
        }
        return $excluded;
    }

    /**
     * Exclude URLs from Rank Math sitemap.
     */
    public static function rankmath_exclude_urls($urls)
    {
        foreach (self::ROUTE_BASES as $base) {
            $urls[] = home_url('/' . $base . '/');
        }
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
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CQA DEBUG: register_rewrites called');
        }
        foreach (self::ROUTE_BASES as $base) {
            // Login Route
            add_rewrite_rule('^' . $base . '/login/?$', 'index.php?cqa_page=login', 'top');

            // Auth Callback
            add_rewrite_rule('^' . $base . '/auth/callback/?$', 'index.php?cqa_page=oauth_callback', 'top');

            // All other routes map to the React App (Dashboard)
            add_rewrite_rule('^' . $base . '/.*', 'index.php?cqa_page=dashboard', 'top');
            add_rewrite_rule('^' . $base . '/?$', 'index.php?cqa_page=dashboard', 'top');
        }
    }

    /**
     * Flush rewrite rules once after route changes.
     */
    public static function maybe_flush_rewrite_rules()
    {
        $stored_version = get_option('cqa_frontend_rewrite_version', '');
        if ($stored_version === self::REWRITE_VERSION) {
            return;
        }

        self::register_rewrites();
        flush_rewrite_rules(false);
        update_option('cqa_frontend_rewrite_version', self::REWRITE_VERSION, false);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CQA DEBUG: Frontend rewrite rules flushed to version ' . self::REWRITE_VERSION);
        }
    }

    /**
     * Prevent core canonical redirects from hijacking QA routes.
     *
     * @param string|false $redirect_url Redirect URL candidate.
     * @param string $requested_url Current requested URL.
     * @return string|false
     */
    public static function preserve_qa_route_canonical($redirect_url, $requested_url)
    {
        $path = wp_parse_url((string) $requested_url, PHP_URL_PATH);
        if (!is_string($path)) {
            $path = '';
        }

        if (self::is_qa_route_path($path)) {
            return false;
        }

        return $redirect_url;
    }

    /**
     * Disable custom canonical redirect option for QA routes only.
     *
     * @param mixed $pre_option Pre-option value.
     * @param string $option Option key.
     * @param mixed $default Default option value.
     * @return mixed
     */
    public static function disable_custom_canonical_on_qa_routes($pre_option, $option, $default)
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        if (!is_string($path)) {
            $path = '';
        }

        if (self::is_qa_route_path($path)) {
            return false;
        }

        return $pre_option;
    }

    /**
     * Whether the path is one of the public QA portal routes.
     *
     * @param string $path Request path.
     * @return bool
     */
    private static function is_qa_route_path($path)
    {
        return is_string($path) && (bool) preg_match('#^/qa-reports?(?:/|$)#i', $path);
    }

    /**
     * Add custom query vars.
     */
    public static function add_query_vars($vars)
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CQA DEBUG: add_query_vars called');
        }
        $vars[] = 'cqa_page';
        return $vars;
    }

    /**
     * Hide WP Admin Bar on QA portal routes only.
     *
     * @param bool $show Whether to show admin bar.
     * @return bool
     */
    public static function maybe_hide_admin_bar_on_qa_portal($show)
    {
        if (is_admin()) {
            return $show;
        }

        $page = get_query_var('cqa_page');
        if (!empty($page)) {
            return false;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        if (is_string($request_uri)) {
            foreach (self::ROUTE_BASES as $base) {
                if (strpos($request_uri, '/' . $base) !== false) {
                    return false;
                }
            }
        }

        return $show;
    }

    /**
     * Handle front-end routes.
     */
    public static function handle_routes()
    {
        $page = get_query_var('cqa_page');
        if (!empty($page)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CQA DEBUG: handle_routes triggered for page: ' . $page);
            }
        }

        if (empty($page)) {
            return;
        }

        // Prevent Caching for all QA pages
        nocache_headers();

        // Check authentication for protected pages
        $public_pages = ['login', 'oauth_callback'];

        $is_logged_in = is_user_logged_in();

        if (!in_array($page, $public_pages) && !$is_logged_in) {
            wp_redirect(home_url('/qa-reports/login/'));
            exit;
        }

        // Failsafe: If user is administrator, ensure they have CQA caps
        if (current_user_can('manage_options')) {
            $user = wp_get_current_user();
            if (
                !user_can($user, 'cqa_create_reports') ||
                !user_can($user, 'cqa_view_all_reports') ||
                !user_can($user, 'cqa_view_own_reports')
            ) {
                require_once CQA_PLUGIN_DIR . 'includes/class-activator.php';
                \ChromaQA\Activator::create_roles();
            }
        }

        // Check capabilities for protected pages
        if (
            !in_array($page, $public_pages) && !current_user_can('cqa_create_reports')
            && !current_user_can('cqa_view_all_reports')
            && !current_user_can('cqa_view_own_reports')
        ) { // Ensure basic view cap is checked
            if (is_user_logged_in()) {
                wp_die(__('You do not have permission to access QA Reports. Please contact your administrator.', 'chroma-qa-reports'), __('Access Denied', 'chroma-qa-reports'), ['response' => 403]);
            }
            wp_redirect(home_url('/qa-reports/login/'));
            exit;
        }

        // Handle OAuth callback before template loading to ensure redirects work
        // (load_template outputs HTML via header.php, which prevents wp_redirect from working)
        if ($page === 'oauth_callback') {
            self::oauth_callback();
            exit;
        }

        // Handle login form POST submission directly (not via AJAX).
        // This ensures the Set-Cookie header and the redirect are in the same HTTP
        // response, which reliably establishes the session. AJAX-based login via
        // admin-ajax.php sets cookies in the XHR response, but browsers may not
        // persist them for the subsequent page navigation.
        if ($page === 'login' && 'POST' === $_SERVER['REQUEST_METHOD'] && !empty($_POST['username'])) {
            self::handle_login_post();
            // handle_login_post() always exits; this is a safety fallback
            exit;
        }

        // Redirect logged-in users away from login page ONLY if they have CQA access.
        // This prevents a redirect loop when a user is logged into WordPress but lacks
        // CQA capabilities: login.php would redirect to /qa-reports/, which redirects
        // back to /qa-reports/login/, creating an infinite loop.
        if ($page === 'login' && $is_logged_in) {
            $has_cqa_access = current_user_can('manage_options')
                || current_user_can('cqa_create_reports')
                || current_user_can('cqa_view_all_reports')
                || current_user_can('cqa_view_own_reports');

            if ($has_cqa_access) {
                wp_redirect(home_url('/qa-reports/'));
                exit;
            }
            // User is logged in but lacks CQA capabilities - fall through to show
            // the login page with an appropriate message so they can switch accounts.
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
        self::disable_theme_marketing_scripts();

        if (!did_action('wp_enqueue_scripts')) {
            do_action('wp_enqueue_scripts');
        }

        // Enqueue assets
        self::enqueue_assets($page);
        self::restrict_asset_queue($page);

        include CQA_PLUGIN_DIR . 'public/views/header.php';

        if ($page === 'login') {
            include CQA_PLUGIN_DIR . 'public/views/login.php';
        } elseif ($page === 'oauth_callback') {
            self::oauth_callback();
        } else {
            // Render React App Container for all other routes
            ?>
            <div class="cqa-react-wrap">
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
     * Limit QA portal output to the handles the portal actually needs.
     *
     * This prevents third-party themes/plugins from injecting unrelated CSS,
     * widgets, or analytics just because they enqueue assets globally.
     *
     * @param string $page Current portal page.
     * @return void
     */
    private static function restrict_asset_queue($page)
    {
        $allowed_styles = ['chroma-font-awesome', 'chroma-main'];
        $allowed_scripts = [];

        if ($page === 'login') {
            $allowed_styles[] = 'cqa-frontend-styles';
            $allowed_scripts = ['cqa-frontend'];
        } else {
            $allowed_styles[] = 'cqa-react-app';
            $allowed_scripts = ['cqa-runtime-guard', 'cqa-react-app'];
        }

        self::restrict_wp_style_queue($allowed_styles);
        self::restrict_wp_script_queue($allowed_scripts);
    }

    /**
     * Restrict enqueued style handles to an allow-list and their dependencies.
     *
     * @param string[] $allowed_handles Allowed top-level handles.
     * @return void
     */
    private static function restrict_wp_style_queue(array $allowed_handles)
    {
        global $wp_styles;

        if (!($wp_styles instanceof \WP_Styles)) {
            return;
        }

        $allowed = self::expand_dependency_handles($allowed_handles, $wp_styles->registered);
        $queued = is_array($wp_styles->queue) ? $wp_styles->queue : [];

        foreach ($queued as $handle) {
            if (!isset($allowed[$handle])) {
                wp_dequeue_style($handle);
                wp_deregister_style($handle);
            }
        }
    }

    /**
     * Restrict enqueued script handles to an allow-list and their dependencies.
     *
     * @param string[] $allowed_handles Allowed top-level handles.
     * @return void
     */
    private static function restrict_wp_script_queue(array $allowed_handles)
    {
        global $wp_scripts;

        if (!($wp_scripts instanceof \WP_Scripts)) {
            return;
        }

        $allowed = self::expand_dependency_handles($allowed_handles, $wp_scripts->registered);
        $queued = is_array($wp_scripts->queue) ? $wp_scripts->queue : [];

        foreach ($queued as $handle) {
            if (!isset($allowed[$handle])) {
                wp_dequeue_script($handle);
                wp_deregister_script($handle);
            }
        }
    }

    /**
     * Expand a list of handles to include all recursively registered deps.
     *
     * @param string[] $handles Root handles to keep.
     * @param array<string,\_WP_Dependency> $registered Registered dependency map.
     * @return array<string,bool>
     */
    private static function expand_dependency_handles(array $handles, array $registered)
    {
        $allowed = [];
        $stack = array_values(array_unique(array_filter($handles, 'is_string')));

        while (!empty($stack)) {
            $handle = array_pop($stack);

            if ($handle === '' || isset($allowed[$handle])) {
                continue;
            }

            $allowed[$handle] = true;

            if (!isset($registered[$handle]) || empty($registered[$handle]->deps)) {
                continue;
            }

            foreach ((array) $registered[$handle]->deps as $dep) {
                if (is_string($dep) && $dep !== '' && !isset($allowed[$dep])) {
                    $stack[] = $dep;
                }
            }
        }

        return $allowed;
    }

    /**
     * Strip site-wide marketing widgets/scripts from the QA portal shell.
     *
     * The QA React app should render in a clean environment without global
     * footer widgets, analytics snippets, or modal markup interfering with its
     * layout, runtime, or accessibility.
     *
     * @return void
     */
    private static function disable_theme_marketing_scripts()
    {
        \remove_action('wp_head', 'chroma_output_header_scripts', 1);
        \remove_action('wp_footer', 'chroma_output_footer_scripts', 99);
        \remove_action('wp_footer', 'chroma_render_booking_modal');
        \remove_action('wp_footer', 'chroma_render_pdf_modal');
    }

    /**
     * Enqueue front-end assets.
     */
    private static function enqueue_assets($page)
    {
        // If Login page, load legacy logic
        if ($page === 'login') {
            wp_enqueue_style('cqa-frontend-styles', CQA_PLUGIN_URL . 'public/css/frontend-styles.css', [], CQA_VERSION);
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
            wp_add_inline_style('cqa-react-app', self::get_local_font_face_css());
            wp_add_inline_style('cqa-react-app', self::get_react_shell_css());

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
     * Local font-face declarations used by QA React app styles.
     *
     * @return string
     */
    private static function get_local_font_face_css()
    {
        $outfit_regular = esc_url_raw(get_theme_file_uri('/assets/webfonts/Outfit-Regular.woff2'));
        $outfit_medium = esc_url_raw(get_theme_file_uri('/assets/webfonts/Outfit-Medium.woff2'));
        $outfit_bold = esc_url_raw(get_theme_file_uri('/assets/webfonts/Outfit-Bold.woff2'));
        $playfair_semibold = esc_url_raw(get_theme_file_uri('/assets/webfonts/PlayfairDisplay-SemiBold.woff2'));
        $playfair_bold = esc_url_raw(get_theme_file_uri('/assets/webfonts/PlayfairDisplay-Bold.woff2'));

        return "
@font-face{font-family:'Outfit';src:url('{$outfit_regular}') format('woff2');font-weight:400;font-style:normal;font-display:swap;}
@font-face{font-family:'Outfit';src:url('{$outfit_medium}') format('woff2');font-weight:500;font-style:normal;font-display:swap;}
@font-face{font-family:'Outfit';src:url('{$outfit_bold}') format('woff2');font-weight:700;font-style:normal;font-display:swap;}
@font-face{font-family:'Playfair Display';src:url('{$playfair_semibold}') format('woff2');font-weight:600;font-style:normal;font-display:swap;}
@font-face{font-family:'Playfair Display';src:url('{$playfair_bold}') format('woff2');font-weight:700;font-style:normal;font-display:swap;}
@font-face{font-family:'DM Serif Display';src:url('{$playfair_semibold}') format('woff2');font-weight:400;font-style:normal;font-display:swap;}
@font-face{font-family:'DM Serif Display';src:url('{$playfair_bold}') format('woff2');font-weight:400;font-style:italic;font-display:swap;}";
    }

    /**
     * Minimal shell CSS for React QA routes.
     *
     * Keep this intentionally tiny so the React app controls its own layout
     * without inheriting the legacy public portal styles.
     *
     * @return string
     */
    private static function get_react_shell_css()
    {
        return "
html, body.cqa-frontend {
    margin: 0;
    padding: 0;
    min-height: 100vh;
    background: #f7f4ec;
    color: #263238;
}
body.cqa-frontend {
    width: 100%;
    overflow-x: hidden;
}
.cqa-main,
.cqa-react-wrap,
#cqa-react-app {
    width: 100%;
    max-width: none;
    min-width: 0;
    min-height: 100vh;
    margin: 0;
    padding: 0;
}
#cqa-react-app {
    display: block;
}
";
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
     * Handle login form POST submission (non-AJAX).
     *
     * Processes credentials and sets the auth cookie in the same response as the
     * redirect, which is more reliable than AJAX-based cookie setting.
     */
    private static function handle_login_post()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cqa_frontend_login')) {
            wp_redirect(home_url('/qa-reports/login/?error=security&message=' . urlencode('Security check failed. Please try again.')));
            exit;
        }

        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if (empty($username) || empty($password)) {
            wp_redirect(home_url('/qa-reports/login/?error=missing&message=' . urlencode('Please enter username and password.')));
            exit;
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            wp_redirect(home_url('/qa-reports/login/?error=invalid&message=' . urlencode('Invalid username or password.')));
            exit;
        }

        // Check CQA capabilities (administrators always get access)
        if (
            !user_can($user, 'manage_options')
            && !user_can($user, 'cqa_create_reports')
            && !user_can($user, 'cqa_view_all_reports')
            && !user_can($user, 'cqa_view_own_reports')
        ) {
            wp_redirect(home_url('/qa-reports/login/?error=no_access&message=' . urlencode('You do not have access to QA Reports.')));
            exit;
        }

        // Set auth cookie and redirect in the same response
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        do_action('wp_login', $user->user_login, $user);

        wp_safe_redirect(home_url('/qa-reports/'));
        exit;
    }

    /**
     * AJAX login handler (kept as fallback).
     */
    public static function ajax_login()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cqa_frontend_login')) {
            wp_send_json_error(['message' => __('Security check failed. Please refresh the page.', 'chroma-qa-reports')]);
        }

        $username = sanitize_text_field($_POST['username'] ?? '');
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

        // Check if user has QA capabilities (Administrators always get access)
        if (
            !user_can($user, 'manage_options')
            && !user_can($user, 'cqa_create_reports')
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

<?php
/**
 * Plugin Name: Chroma Parent Portal
 * Description: Premium Parent Portal with React Frontend and PIN Authentication.
 * Version: 1.0.0
 * Author: Chroma Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}


define('CHROMA_PORTAL_VERSION', '1.0.3'); // Force refresh for logo/PDF fixes
define('CHROMA_PORTAL_PATH', plugin_dir_path(__FILE__));
define('CHROMA_PORTAL_URL', plugin_dir_url(__FILE__));

function chroma_parent_portal_is_public_page()
{
    if (is_front_page()) {
        return false;
    }

    $post = get_post();
    return is_page('parent-portal') || ($post && has_shortcode($post->post_content, 'chroma_parent_portal'));
}

// Ensure the standalone portal remains responsive without disabling browser zoom.
add_action('wp_head', function () {
    if (is_page('parent-portal')) {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    }
});

// Disable Theme Customizer Scripts and Booking Modals on Portal Page
add_action('wp', function () {
    $is_portal = chroma_parent_portal_is_public_page();

    if ($is_portal) {
        // 1. Remove Hooked Actions
        remove_action('wp_head', 'chroma_output_header_scripts', 1);
        remove_action('wp_footer', 'chroma_output_footer_scripts', 99);
        remove_action('wp_head', 'chroma_custom_scripts', 100);
        remove_action('wp_footer', 'chroma_render_booking_modal', 10);

        // 2. Filter Theme Mods (for hardcoded echoes in footer.php/header.php)
        add_filter('theme_mod_chroma_header_scripts', '__return_empty_string', 999);
        add_filter('theme_mod_chroma_footer_scripts', '__return_empty_string', 999);
        add_filter('theme_mod_chroma_custom_scripts', '__return_empty_string', 999);

        // 3. Prevent Caching (Fix "Cookie Check Failed" stale nonce issue)
        nocache_headers();
    }
}, 1);

add_action('wp', function () {
    if (!chroma_parent_portal_is_public_page()) {
        return;
    }

    add_filter('wp_robots', function ($robots) {
        if (!is_array($robots)) {
            $robots = [];
        }

        unset($robots['index'], $robots['follow']);
        $robots['noindex'] = true;
        $robots['nofollow'] = true;

        return $robots;
    }, 20);

    add_filter('wpseo_robots', function ($robots) {
        return 'noindex,nofollow';
    }, 20);

    add_filter('pre_get_document_title', function ($title) {
        return 'Parent Portal | Chroma';
    }, PHP_INT_MAX);

    add_filter('wpseo_title', function ($title) {
        return 'Parent Portal | Chroma';
    }, PHP_INT_MAX);

    add_filter('wpseo_metadesc', function ($description) {
        return 'Secure family portal for tuition, daily reports, classroom updates, and school resources.';
    }, PHP_INT_MAX);

    add_filter('wpseo_opengraph_desc', function ($description) {
        return 'Secure family portal for tuition, daily reports, classroom updates, and school resources.';
    }, PHP_INT_MAX);
}, 5);

/**
 * Ignore stale wp_rest nonces for public/token parent-portal routes.
 *
 * These routes do not rely on cookie authentication, but stale cached nonces
 * can trigger WordPress "Cookie check failed" errors before route callbacks run.
 */
add_filter('rest_authentication_errors', function ($result) {
    if (!is_wp_error($result) || 'rest_cookie_invalid_nonce' !== $result->get_error_code()) {
        return $result;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    $rest_route = '';

    // Support both pretty permalinks (/wp-json/...) and plain permalinks (?rest_route=...).
    if (isset($_GET['rest_route'])) {
        $rest_route = sanitize_text_field(wp_unslash($_GET['rest_route']));
    } elseif (false !== strpos($request_uri, '/wp-json/')) {
        $parts = explode('/wp-json', $request_uri, 2);
        $rest_route = isset($parts[1]) ? $parts[1] : '';
    }

    if (0 !== strpos($rest_route, '/chroma-portal/v1/')) {
        return $result;
    }

    $is_login_route = 0 === strpos($rest_route, '/chroma-portal/v1/login');
    $is_token_route = (
        (
            0 === strpos($rest_route, '/chroma-portal/v1/content/dashboard')
            || 0 === strpos($rest_route, '/chroma-portal/v1/years')
            || 0 === strpos($rest_route, '/chroma-portal/v1/taxonomy/')
        )
        && !empty($_SERVER['HTTP_X_PORTAL_TOKEN'])
    );

    if ($is_login_route || $is_token_route) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Chroma Parent Portal: bypassed stale rest nonce for token/public route: ' . $rest_route);
        }
        return null;
    }

    return $result;
}, 20);

/**
 * Cache Invalidation for Parent Portal (P0-4)
 */
add_action('save_post', function ($post_id) {
    if (wp_is_post_revision($post_id)) {
        return;
    }
    $types = ['cp_announcement', 'cp_lesson_plan', 'cp_home_activity', 'cp_meal_plan', 'cp_resource', 'cp_form', 'cp_event'];
    if (in_array(get_post_type($post_id), $types)) {
        if (function_exists('chroma_invalidate_cache_group')) {
            chroma_invalidate_cache_group('portal_dashboard');
        }
    }
});

// Load Backend Components (Always for CPT registration and API)
require_once CHROMA_PORTAL_PATH . 'includes/class-cpt-registrar.php';
require_once CHROMA_PORTAL_PATH . 'includes/class-api-routes.php';

// Load Admin-only Components
if (is_admin()) {
    require_once CHROMA_PORTAL_PATH . 'includes/class-meta-boxes.php';
    require_once CHROMA_PORTAL_PATH . 'includes/class-bulk-importer.php';
    require_once CHROMA_PORTAL_PATH . 'includes/class-bulk-uploader.php';
}

// Activation Hook
register_activation_hook(__FILE__, function () {
    if (!get_page_by_path('parent-portal')) {
        wp_insert_post([
            'post_title' => 'Parent Portal',
            'post_name' => 'parent-portal',
            'post_content' => '[chroma_parent_portal]',
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
    }
});

// Register Assets
add_action('wp_enqueue_scripts', function () {
    $post = get_post();
    // Broaden check: If it's the specific page OR has shortcode
    $is_portal_page = !is_front_page() && is_page('parent-portal');
    $has_shortcode = !is_front_page() && $post && has_shortcode($post->post_content, 'chroma_parent_portal');

    if (!$is_portal_page && !$has_shortcode) {
        return;
    }

    $asset_file_path = CHROMA_PORTAL_PATH . 'build/index.asset.php';
    if (!file_exists($asset_file_path)) {
        return;
    }

    $asset_file = include $asset_file_path;


    wp_enqueue_script(
        'chroma-portal-app',
        CHROMA_PORTAL_URL . 'build/index.js',
        $asset_file['dependencies'],
        $asset_file['version'], // Use build hash for versioning
        true
    );

    wp_enqueue_style(
        'chroma-portal-styles',
        CHROMA_PORTAL_URL . 'build/index.css',
        [], // Remove dependency on Google Fonts
        $asset_file['version'] // Use build hash for versioning
    );

    wp_enqueue_script(
        'chroma-portal-login-enhancements',
        CHROMA_PORTAL_URL . 'assets/login-enhancements.js',
        ['chroma-portal-app'],
        CHROMA_PORTAL_VERSION,
        true
    );

    wp_localize_script('chroma-portal-app', 'chromaPortalSettings', [
        'root' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest'),
        'assetsUrl' => CHROMA_PORTAL_URL . 'build/',
    ]);
});

// Add Body Class for Full App Mode
add_filter('body_class', function ($classes) {
    if (!is_front_page() && is_page('parent-portal')) {
        $classes[] = 'portal-is-active';
    }
    return $classes;
});

// Shortcode
add_shortcode('chroma_parent_portal', function () {
    wp_enqueue_script('chroma-portal-app');
    wp_enqueue_style('chroma-portal-styles');

    // Fallback/Loading state that React will replace
    return '<div id="chroma-parent-portal-root" style="display: flex !important; flex-direction: column; justify-content: center; align-items: center; height: 100vh !important; width: 100vw !important; position: fixed !important; top: 0 !important; left: 0 !important; z-index: 99999 !important; background: #FDFBF7; color: #263238; text-align: center; overflow: visible !important; font-family: system-ui, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, sans-serif;">
        <div style="width: 44px; height: 44px; border: 4px solid rgba(38, 50, 56, 0.14); border-top-color: #263238; border-radius: 999px; animation: chromaPortalSpin 1s linear infinite;"></div>
        <p style="font-size: 18px; margin: 20px 0 0; font-weight: 700;">Loading Parent Portal</p>
        <style>@keyframes chromaPortalSpin { to { transform: rotate(360deg); } } @media (prefers-reduced-motion: reduce) { #chroma-parent-portal-root > div { animation: none !important; } }</style>
    </div>';
});

<?php
/**
 * Chroma Excellence Theme Functions
 *
 * Homepage Template: front-page.php (WordPress default)
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PRODUCTION HARDENING: Disable error display to prevent JSON corruption in REST API.
 * Errors should be logged to debug.log, not the screen.
 */
if (!defined('CHROMA_DEBUG') || !CHROMA_DEBUG) {
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
}

/**
 * Increase Memory Limit for SEO Engine
 */
@ini_set('memory_limit', '256M');

/**
 * Define theme constants
 */
define('CHROMA_VERSION', '2.0.0');
define('CHROMA_THEME_DIR', get_template_directory());
define('CHROMA_THEME_URI', get_template_directory_uri());

/**
 * CORE PERFORMANCE HELPERS
 */

/**
 * Feature flag checker
 * Allows safe-first rollout of risky performance changes
 */
function chroma_perf_enabled($flag)
{
    // Enable by default on staging/dev via WP_DEBUG
    if (defined('WP_DEBUG') && WP_DEBUG) {
        return true;
    }
    $flags = get_option('chroma_perf_flags', []);
    return in_array($flag, (array) $flags);
}

/**
 * Get versioned cache token (last_changed)
 * Follows core WP pattern for object cache invalidation
 */
function chroma_get_last_changed($group)
{
    $last_changed = wp_cache_get($group . '_last_changed', 'chroma');
    if (!$last_changed) {
        // Fallback to transient if object cache is not persistent
        $last_changed = get_transient('chroma_last_changed_' . $group);
        if (!$last_changed) {
            $last_changed = microtime(true); // Use float version for stability (no spaces)
            set_transient('chroma_last_changed_' . $group, $last_changed, YEAR_IN_SECONDS);
        }
        wp_cache_set($group . '_last_changed', $last_changed, 'chroma');
    }
    return (string) $last_changed;
}

/**
 * Increment last_changed token to invalidate group cache
 */
function chroma_invalidate_cache_group($group)
{
    $new_time = microtime();
    wp_cache_set($group . '_last_changed', $new_time, 'chroma');
    set_transient('chroma_last_changed_' . $group, $new_time, YEAR_IN_SECONDS);
}

/**
 * Simple lock to prevent cache stampede
 */
function chroma_acquire_lock($key, $ttl = 15)
{
    return wp_cache_add($key . '_lock', 1, 'chroma', $ttl);
}

function chroma_release_lock($key)
{
    wp_cache_delete($key . '_lock', 'chroma');
}

/**
 * Admin Performance: Prefetch Meta for Lists (P1)
 */
add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    $post_type = $_GET['post_type'] ?? '';
    if (in_array($post_type, ['location', 'chroma_lead_log'])) {
        $query->set('update_post_meta_cache', true);
        $query->set('update_post_term_cache', true);
    }
});

/**
 * Plugin Polyfills
 */
if (!function_exists('chroma_url')) {
    function chroma_url($path = '')
    {
        return home_url($path);
    }
}

if (!function_exists('chroma_get_theme_mod')) {
    /**
     * Get theme mod with original-theme fallback.
     *
     * V2 uses a different stylesheet slug, so Customizer values live under a
     * different option name. Post meta remains shared automatically, but
     * theme mods need a safe read-through while the migration runs.
     *
     * @param string $name Theme mod name
     * @param mixed $default Default value
     * @return mixed
     */
    function chroma_get_theme_mod($name, $default = false)
    {
        $value = get_theme_mod($name, null);

        if (null !== $value) {
            return $value;
        }

        $stylesheet = get_stylesheet();
        $source_stylesheets = array_unique(array(
            'chroma-excellence-theme-v2',
            'chroma-excellence-theme-v2-0',
            'chroma-excellence-theme-v2-0-2',
            'chroma-excellence-theme',
        ));

        foreach ($source_stylesheets as $source_stylesheet) {
            if ($source_stylesheet === $stylesheet) {
                continue;
            }

            $source_mods = get_option('theme_mods_' . $source_stylesheet, array());

            if (is_array($source_mods) && array_key_exists($name, $source_mods)) {
                return $source_mods[$name];
            }
        }

        return $default;
    }
}

/**
 * Cached WP_Query helper function
 * Reduces database queries by caching results in transients
 *
 * @param array  $args            WP_Query arguments
 * @param string $cache_key_prefix Cache key prefix for identification
 * @param int    $expiration      Cache duration in seconds (default: 1 hour)
 * @return WP_Query Cached or fresh query results
 */
if (!function_exists('chroma_cached_query')) {
    function chroma_cached_query($args, $cache_key_prefix, $expiration = HOUR_IN_SECONDS)
    {
        $cache_key = 'chroma_' . $cache_key_prefix . '_' . md5(serialize($args));
        $cached = get_transient($cache_key);

        if (false !== $cached && $cached instanceof WP_Query) {
            return $cached;
        }

        $query = new WP_Query($args);
        set_transient($cache_key, $query, $expiration);

        return $query;
    }
}

/**
 * Clear cached queries when posts are updated
 * Ensures fresh data after content changes
 */
function chroma_clear_query_cache($post_id)
{
    $post_type = get_post_type($post_id);
    if (!$post_type) {
        return;
    }

    // Map post types to cache prefixes
    $cache_prefixes = array(
        'post' => array('footer_blog', 'newsroom'),
        'location' => array('locations'),
        'program' => array('programs'),
        'city' => array('cities'),
        'team_member' => array('team', 'team_members_about'),
    );

    if (isset($cache_prefixes[$post_type])) {
        foreach ($cache_prefixes[$post_type] as $prefix) {
            // Delete all transients with this prefix
            global $wpdb;
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                    '_transient_chroma_' . $wpdb->esc_like($prefix) . '%',
                    '_transient_timeout_chroma_' . $wpdb->esc_like($prefix) . '%'
                )
            );
        }
    }
}
add_action('save_post', 'chroma_clear_query_cache');
add_action('delete_post', 'chroma_clear_query_cache');
add_action('trash_post', 'chroma_clear_query_cache');

/**
 * Keep the Careers page dynamic so external job-feed availability and
 * JobPosting schema are not frozen by page caches.
 */
function chroma_disable_careers_page_cache()
{
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || !is_page('careers')) {
        return;
    }

    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }

    nocache_headers();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}
add_action('template_redirect', 'chroma_disable_careers_page_cache', -1000);



/**
 * Load core theme functionality
 * Order matters - load dependencies first
 */

// Core setup and configuration
require_once CHROMA_THEME_DIR . '/inc/theme-migrations.php';
require_once CHROMA_THEME_DIR . '/inc/setup.php';
require_once CHROMA_THEME_DIR . '/inc/nav-menus.php';

// Frontend-only includes
if (!is_admin() && !wp_doing_cron()) {
    require_once CHROMA_THEME_DIR . '/inc/critical-css.php';
    require_once CHROMA_THEME_DIR . '/inc/enqueue.php';
    require_once CHROMA_THEME_DIR . '/inc/chroma-pdf-viewer.php';
    require_once CHROMA_THEME_DIR . '/inc/chroma-booking-modal.php';
}

// Admin-only includes
if (is_admin()) {
    require_once CHROMA_THEME_DIR . '/inc/admin/class-menu-sync.php';
}

require_once CHROMA_THEME_DIR . '/inc/program-settings.php';

// Custom Post Types
require_once CHROMA_THEME_DIR . '/inc/cpt-programs.php';
require_once CHROMA_THEME_DIR . '/inc/cpt-locations.php';
require_once CHROMA_THEME_DIR . '/inc/cpt-cities.php';
require_once CHROMA_THEME_DIR . '/inc/cpt-team-members.php';
require_once CHROMA_THEME_DIR . '/inc/cpt-careers.php';
// require_once CHROMA_THEME_DIR . '/inc/class-program-enhancements.php';
require_once CHROMA_THEME_DIR . '/inc/class-amp-blog.php';
require_once CHROMA_THEME_DIR . '/inc/class-data-service.php';
require_once CHROMA_THEME_DIR . '/inc/class-branding-engine.php';
require_once CHROMA_THEME_DIR . '/inc/performance-optimizations.php';

// API Handlers


// Page Meta Boxes - Admin Only
if (is_admin()) {
    require_once CHROMA_THEME_DIR . '/inc/about-page-meta.php';
    require_once CHROMA_THEME_DIR . '/inc/curriculum-page-meta.php';
    require_once CHROMA_THEME_DIR . '/inc/contact-page-meta.php';
    require_once CHROMA_THEME_DIR . '/inc/early-start-program-meta.php';
    require_once CHROMA_THEME_DIR . '/inc/stories-page-meta.php';
    require_once CHROMA_THEME_DIR . '/inc/parents-page-meta.php';
    require_once CHROMA_THEME_DIR . '/inc/careers-page-meta.php';
    require_once CHROMA_THEME_DIR . '/inc/employers-page-meta.php';
    require_once CHROMA_THEME_DIR . '/inc/privacy-page-meta.php';
    // require_once CHROMA_THEME_DIR . '/inc/schema-meta-boxes.php';
    require_once CHROMA_THEME_DIR . '/inc/general-seo-meta.php';
    require_once CHROMA_THEME_DIR . '/inc/home-page-meta.php';
}


// Utility Functions
require_once CHROMA_THEME_DIR . '/inc/translation-helpers.php';
require_once CHROMA_THEME_DIR . '/inc/template-tags.php';
require_once CHROMA_THEME_DIR . '/inc/dynamic-links.php';
require_once CHROMA_THEME_DIR . '/inc/guide-aliases.php';
require_once CHROMA_THEME_DIR . '/inc/staging-cache.php';
require_once CHROMA_THEME_DIR . '/inc/archive-root-query-context.php';
require_once CHROMA_THEME_DIR . '/inc/seo-profile.php';
require_once CHROMA_THEME_DIR . '/inc/seo-runtime.php';
// require_once CHROMA_THEME_DIR . '/inc/about-seo.php';
// Customizers - Admin or Preview Only
if (is_admin() || is_customize_preview()) {
    require_once CHROMA_THEME_DIR . '/inc/customizer-home.php';
    require_once CHROMA_THEME_DIR . '/inc/customizer-header.php';
    require_once CHROMA_THEME_DIR . '/inc/customizer-footer.php';
    require_once CHROMA_THEME_DIR . '/inc/customizer-locations.php';
    require_once CHROMA_THEME_DIR . '/inc/customizer-scripts.php';
}

/**
 * Determine whether the current request is a normal frontend HTML request.
 *
 * Global header/footer scripts should load on rendered site pages, but not on
 * REST, feeds, AJAX, cron, or other bootstrap paths where they add no value
 * and can widen the blast radius of a bad include.
 */
function chroma_should_load_global_script_customizer()
{
    if (is_customize_preview()) {
        return true;
    }

    if (is_admin()) {
        return false;
    }

    if (wp_doing_ajax() || wp_doing_cron()) {
        return false;
    }

    if ((defined('REST_REQUEST') && REST_REQUEST) || (function_exists('wp_is_json_request') && wp_is_json_request())) {
        return false;
    }

    if (is_feed() || is_robots() || is_trackback() || is_embed()) {
        return false;
    }

    return true;
}

if (chroma_should_load_global_script_customizer()) {
    require_once CHROMA_THEME_DIR . '/inc/customizer-scripts.php';
}
require_once CHROMA_THEME_DIR . '/inc/customizer-seo.php';

// Legacy helper files (ACF plugin optional; helpers run on core WP functions only)
require_once CHROMA_THEME_DIR . '/inc/acf-options.php';
require_once CHROMA_THEME_DIR . '/inc/acf-homepage.php';

require_once CHROMA_THEME_DIR . '/inc/cleanup.php';

require_once CHROMA_THEME_DIR . '/inc/city-slug-logic.php';
require_once CHROMA_THEME_DIR . '/inc/monthly-seo-cron.php';

// Spanish Variant Generator
require_once CHROMA_THEME_DIR . '/inc/spanish-variant-generator.php';

require_once CHROMA_THEME_DIR . '/inc/security.php';
require_once CHROMA_THEME_DIR . '/inc/force-trailing-slashes.php';

/**
 * Optimize WordPress Heartbeat API
 * Reduces server load by increasing the heartbeat interval and disabling it on non-essential pages.
 */
function chroma_optimize_heartbeat($settings)
{
    // Heartbeat is only used in admin/editor contexts
    if (!is_admin()) {
        return $settings;
    }

    if (!function_exists('get_current_screen')) {
        return $settings;
    }

    $screen = get_current_screen();
    if ($screen && $screen->base !== 'post') {
        $settings['interval'] = 60; // Increase interval to 60s for non-editor screens
    }
    return $settings;
}
add_filter('heartbeat_settings', 'chroma_optimize_heartbeat');

/**
 * Remove Legacy JavaScript & Styles
 * - WP Emoji
 * - WP Embeds
 */
function chroma_remove_legacy_assets()
{
    // Remove Embeds
    if (!is_admin()) {
        wp_deregister_script('wp-embed');
    }
}
add_action('init', 'chroma_remove_legacy_assets');

/**
 * Remove Gutenberg Block Library CSS on Frontend
 * This theme doesn't use Gutenberg blocks, so we can remove these render-blocking styles
 */
function chroma_remove_block_library_css()
{
    if (!is_admin()) {
        // Remove core block library CSS
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');

        // Remove WooCommerce block CSS (if any)
        wp_dequeue_style('wc-blocks-style');

        // Remove global styles (theme.json generated)
        wp_dequeue_style('global-styles');
        wp_dequeue_style('wp-block-navigation');
        wp_dequeue_style('经典主题样式'); // Classic Theme Styles
        wp_dequeue_style('classic-theme-styles');

        // Remove Dashicons for non-logged-in users
        if (!is_user_logged_in()) {
            wp_dequeue_style('dashicons');
        }
    }
}
add_action('wp_enqueue_scripts', 'chroma_remove_block_library_css', 100);

// Disable separate block assets loading (WordPress 5.8+)
add_filter('should_load_separate_core_block_assets', '__return_false');

// Remove inline block styles for specific blocks
add_action('wp_enqueue_scripts', function () {
    // Get all registered block styles and remove them
    $blocks_to_remove = ['heading', 'paragraph', 'list', 'list-item', 'quote', 'image', 'separator'];
    foreach ($blocks_to_remove as $block) {
        wp_dequeue_style("wp-block-{$block}");
        wp_deregister_style("wp-block-{$block}");
    }
}, 200);

/**
 * Exclude images with 'no-lazy' class from LiteSpeed lazy loading
 * This prevents CLS on hero images and other critical above-the-fold images
 */
add_filter('litespeed_media_lazy_img_excludes', function ($excludes) {
    $excludes[] = 'no-lazy';
    $excludes[] = 'fetchpriority';
    return $excludes;
});

// Also exclude from native WordPress lazy loading
add_filter('wp_img_tag_add_loading_attr', function ($value, $image, $context) {
    if (strpos($image, 'no-lazy') !== false || strpos($image, 'fetchpriority') !== false) {
        return false; // Don't add loading="lazy"
    }
    return $value;
}, 10, 3);


/**
 * Add CORS Headers for Font Files
 * Fixes: Cross-origin font loading when site is accessed via www vs non-www
 */
function chroma_add_cors_headers()
{
    // Only add headers for font file requests
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

    if (preg_match('/\.(woff2?|ttf|otf|eot)$/i', $request_uri)) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
    }
}
add_action('send_headers', 'chroma_add_cors_headers');

/**
 * Add CORS headers to font files served by WordPress
 * This filter adds headers when fonts are served through WordPress
 */
function chroma_cors_font_headers($headers, $path)
{
    if (preg_match('/\.(woff2?|ttf|otf|eot)$/i', $path)) {
        $headers['Access-Control-Allow-Origin'] = '*';
    }
    return $headers;
}
add_filter('wp_get_attachment_headers', 'chroma_cors_font_headers', 10, 2);

/**
 * Performance Optimizations - Phase 1 (Safe Mode)
 * Added: [Current Date]
 */

// Force image dimensions to prevent layout shift (CLS improvement)
add_filter('wp_img_tag_add_width_and_height_attr', '__return_true');

// Force Elementor to output image dimensions
add_filter('elementor/image/print_dimensions', '__return_true');

/**
 * Add width and height attributes to post thumbnails for CLS optimization
 * Filter: post_thumbnail_html
 */
function chroma_add_post_thumbnail_dims($html, $post_id, $post_thumbnail_id)
{
    if (!$post_thumbnail_id) {
        return $html;
    }
    return chroma_inject_dimensions($html, $post_thumbnail_id);
}
add_filter('post_thumbnail_html', 'chroma_add_post_thumbnail_dims', 10, 3);

/**
 * Add width and height attributes to content images
 * Filter: get_image_tag
 */
function chroma_add_content_image_dims($html, $id, $alt)
{
    if (!$id) {
        return $html;
    }
    return chroma_inject_dimensions($html, $id);
}
add_filter('get_image_tag', 'chroma_add_content_image_dims', 10, 3);

/**
 * Helper function to inject dimensions
 */
function chroma_inject_dimensions($html, $attachment_id)
{
    // If width is already defined, skip
    if (empty($html) || strpos($html, 'width=') !== false) {
        return $html;
    }

    $metadata = wp_get_attachment_metadata($attachment_id);
    if (isset($metadata['width']) && isset($metadata['height'])) {
        $html = str_replace('<img', sprintf(
            '<img width="%d" height="%d"',
            $metadata['width'],
            $metadata['height']
        ), $html);
    }

    return $html;
}

/**
 * Allow WebP uploads
 */
function chroma_mime_types($mimes)
{
    $mimes['webp'] = 'image/webp';
    return $mimes;
}
add_filter('upload_mimes', 'chroma_mime_types');



/**
 * LCP Optimization: Preload hero image to improve Largest Content Paint
 */
function chroma_preload_lcp_image()
{
    // Front-page hero preload is handled in header.php.
    if (is_front_page()) {
        return;
    }

    // On single blog posts, preload the featured image (common LCP target).
    if (is_singular('post') && has_post_thumbnail()) {
        $thumb_id = get_post_thumbnail_id();
        $thumb_url = wp_get_attachment_image_url($thumb_id, 'full');
        if (!$thumb_url) {
            return;
        }

        $srcset = wp_get_attachment_image_srcset($thumb_id, 'full');
        $sizes = '(max-width: 1280px) 100vw, 1280px';

        echo '<link rel="preload" as="image" href="' . esc_url($thumb_url) . '"';
        if (!empty($srcset)) {
            echo ' imagesrcset="' . esc_attr($srcset) . '" imagesizes="' . esc_attr($sizes) . '"';
        }
        echo ' fetchpriority="high">' . "\n";
    }
}
add_action('wp_head', 'chroma_preload_lcp_image', 1);

/**
 * LiteSpeed Cache: Exclude LCP/hero images from lazy loading
 */
function chroma_litespeed_exclude_lcp()
{
    return array('logo_optimized', 'chroma-logo', 'hero', 'chroma-1920w', 'chroma-1920w.webp', 'logo');
}
add_filter('litespeed_img_lazy_exclude', 'chroma_litespeed_exclude_lcp');

/**
 * SEO: Dynamic Meta Descriptions
 */


/**
 * Dequeue LeadConnector Plugin Scripts
 * The plugin loads scripts immediately, blocking render
 * We dequeue them and load manually with lazy-loading below
 */
function chroma_dequeue_leadconnector_plugin()
{
    // Always dequeue to allow JS to handle loading logic (Cloudflare compatible)
    // Dequeue all LeadConnector plugin scripts
    wp_dequeue_script('leadconnector-widget');
    wp_deregister_script('leadconnector-widget');
    wp_dequeue_script('leadconnector');
    wp_deregister_script('leadconnector');
    wp_dequeue_script('lc-widget');
    wp_deregister_script('lc-widget');

    // Also dequeue any styles
    wp_dequeue_style('leadconnector');
    wp_deregister_style('leadconnector');
}
add_action('wp_enqueue_scripts', 'chroma_dequeue_leadconnector_plugin', 9999);




/**
 * URL Consistency: Force trailing slashes on all URLs
 * This prevents duplicate content issues like /programs vs /programs/
 */
function chroma_enforce_trailing_slash($url, $type)
{
    // Skip files (anything with an extension)
    if (preg_match('/\.[a-zA-Z0-9]+(\?|$)/', $url)) {
        return $url;
    }

    // Skip feed URLs
    if ($type === 'single_feed' || $type === 'category_feed') {
        return $url;
    }

    return trailingslashit($url);
}
add_filter('user_trailingslashit', 'chroma_enforce_trailing_slash', 10, 2);

/**
 * Route Safety Net (Theme-level)
 * If server/plugin rewrite rules are stale, map critical pretty URLs to query vars:
 * - wp-sitemap URLs
 * - combo pages
 * - near-me pages
 *
 * IMPORTANT: Always MERGE into $query_vars and clear conflicting keys like
 * 'pagename', 'name', 'error' so WordPress doesn't treat the URL as a page
 * lookup and 404 before the actual handler fires.
 */
function chroma_force_sitemap_request_vars($query_vars)
{
    if (is_admin()) {
        return $query_vars;
    }

    // If sitemap query vars are already set (e.g., by working rewrite rules), bail.
    if (!empty($query_vars['sitemap']) || !empty($query_vars['sitemap-stylesheet'])) {
        return $query_vars;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return $query_vars;
    }

    $path = '/' . ltrim($path, '/');
    $inject = null;

    // --- Sitemaps ---

    if (preg_match('#/wp-sitemap\.xml$#i', $path)) {
        $inject = ['sitemap' => 'index'];
    } elseif (preg_match('#/wp-sitemap\.xsl$#i', $path)) {
        $inject = ['sitemap-stylesheet' => 'sitemap'];
    } elseif (preg_match('#/wp-sitemap-index\.xsl$#i', $path)) {
        $inject = ['sitemap-stylesheet' => 'index'];
    } elseif (preg_match('#/wp-sitemap-([a-z]+)-([a-z0-9_-]+)-([0-9]+)\.xml$#i', $path, $matches)) {
        $inject = [
            'sitemap' => strtolower($matches[1]),
            'sitemap-subtype' => strtolower($matches[2]),
            'paged' => max(1, (int) $matches[3]),
        ];
    } elseif (preg_match('#/wp-sitemap-([a-z]+)-([0-9]+)\.xml$#i', $path, $matches)) {
        $inject = [
            'sitemap' => strtolower($matches[1]),
            'paged' => max(1, (int) $matches[2]),
        ];
    }

    // --- Combo pages: /{program}-in-{city}-{state}/ and /es/{program}-in-{city}-{state}/ ---
    elseif (preg_match('#^/(es/)?([a-z0-9-]+)-in-([a-z-]+)-([a-z]{2})/?$#i', $path, $matches)) {
        $inject = [
            'chroma_combo' => 1,
            'combo_program' => sanitize_title($matches[2]),
            'combo_city' => sanitize_title($matches[3]),
            'combo_state' => strtoupper($matches[4]),
        ];
        if (!empty($matches[1])) {
            $inject['chroma_lang'] = 'es';
        }
    }

    // --- Near-me pages: /{keyword}-near-me/ ---
    elseif (preg_match('#^/(es/)?([a-z0-9-]+)-near-me/?$#i', $path, $matches)) {
        $inject = [
            'chroma_near_me' => sanitize_title($matches[2]),
        ];
        if (!empty($matches[1])) {
            $inject['chroma_lang'] = 'es';
        }
    }

    // --- Near-city pages: /{keyword}-near-{city}-{state}/ ---
    elseif (preg_match('#^/(es/)?([a-z0-9-]+)-near-([a-z-]+)-([a-z]{2})/?$#i', $path, $matches)) {
        $inject = [
            'chroma_near_me' => sanitize_title($matches[2]),
            'near_city' => sanitize_title($matches[3]),
            'near_state' => strtoupper($matches[4]),
        ];
        if (!empty($matches[1])) {
            $inject['chroma_lang'] = 'es';
        }
    }

    if ($inject !== null) {
        // CRITICAL: Remove conflicting vars so WP doesn't treat URL as a page lookup.
        unset($query_vars['pagename'], $query_vars['name'], $query_vars['page'], $query_vars['error']);
        return array_merge($query_vars, $inject);
    }

    return $query_vars;
}
add_filter('request', 'chroma_force_sitemap_request_vars', 0);

/**
 * Disable WordPress native sitemaps — we serve our own at /sitemap.xml.
 */
add_filter('wp_sitemaps_enabled', '__return_false');

/**
 * Single unified sitemap at /sitemap.xml.
 *
 * Completely bypasses WordPress's native sitemap system and its rewrite rules.
 * Generates one flat XML sitemap containing approved indexable URLs:
 * - Published posts, pages, locations, programs, and approved cities
 * - Search-approved generated routes registered through chroma_sitemap_urls
 * - Spanish /es/ variants only when translated content is available
 */
function chroma_serve_custom_sitemap()
{
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = '/' . ltrim(strtok($request_uri, '?'), '/');

    // Legacy aliases → redirect to /sitemap.xml
    $legacy = [
        '/sitemap_index.xml',
        '/wp-sitemap.xml',
        '/sitemap-combos.xml',
        '/sitemap-combos-es.xml',
        '/sitemap-spanish.xml',
        '/sitemap-near-me.xml',
        '/sitemap-near-me-es.xml',
    ];
    if (in_array($path, $legacy, true)) {
        if (function_exists('chroma_is_staging_request') && chroma_is_staging_request()) {
            status_header(404);
            nocache_headers();
            header('X-Robots-Tag: noindex, nofollow, noarchive', true);
            exit;
        }

        wp_safe_redirect(home_url('/sitemap.xml'), 301);
        exit;
    }

    if ($path !== '/sitemap.xml') {
        return;
    }

    if (function_exists('chroma_is_staging_request') && chroma_is_staging_request()) {
        status_header(404);
        nocache_headers();
        header('X-Robots-Tag: noindex, nofollow, noarchive', true);
        exit;
    }

    // Prevent caching issues
    nocache_headers();
    header('Content-Type: application/xml; charset=UTF-8');
    status_header(200);

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    // Central Registry: any component can add URLs here.
    // Each entry should be an array like: ['loc' => '...', 'lastmod' => '...']
    $urls = apply_filters('chroma_sitemap_urls', []);

    // Also include standard posts/pages locally for simplicity
    $standard_urls = chroma_get_standard_sitemap_urls();
    $urls = array_merge($urls, $standard_urls);

    // Filter out duplicates
    $unique_urls = [];
    foreach ($urls as $url_data) {
        $loc = $url_data['loc'] ?? '';
        if ($loc && !isset($unique_urls[$loc])) {
            $unique_urls[$loc] = $url_data;
            echo "  <url>\n";
            echo "    <loc>" . esc_url($loc) . "</loc>\n";
            if (!empty($url_data['lastmod'])) {
                echo "    <lastmod>" . esc_html($url_data['lastmod']) . "</lastmod>\n";
            }
            echo "  </url>\n";
        }
    }

    echo '</urlset>' . "\n";
    exit;
}
add_action('template_redirect', 'chroma_serve_custom_sitemap', -999);

/**
 * Gets standard WordPress posts and pages for the sitemap.
 */
function chroma_get_standard_sitemap_urls()
{
    $urls = [];
    $base = rtrim(home_url('/'), '/');
    $post_types = apply_filters('chroma_sitemap_post_types', ['page', 'post', 'location', 'program', 'city']);

    $posts = get_posts([
        'post_type' => $post_types,
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    $front_page_id = (int) get_option('page_on_front');
    if ($front_page_id > 0 && chroma_post_has_verified_spanish_variant($front_page_id)) {
        $front_lastmod = get_the_modified_date('c', $front_page_id) ?: gmdate('c');
        $urls[] = [
            'loc' => $base . '/es/',
            'lastmod' => $front_lastmod,
        ];
    }

    foreach ($posts as $post) {
        // The secure Parent Portal is intentionally noindex and must not be
        // advertised as an organic-search landing page in the public sitemap.
        if (
            $post->post_type === 'page'
            && in_array($post->post_name, ['parent-portal', 'blog'], true)
        ) {
            continue;
        }

        if (
            $post->post_type === 'post'
            && function_exists('chroma_is_legacy_local_doorway_post')
            && chroma_is_legacy_local_doorway_post($post->ID)
        ) {
            continue;
        }

        if (
            $post->post_type === 'post'
            && function_exists('chroma_is_unfinished_empty_post')
            && chroma_is_unfinished_empty_post($post->ID)
        ) {
            continue;
        }

        if (
            $post->post_type === 'city'
            && function_exists('chroma_city_is_search_approved')
            && !chroma_city_is_search_approved($post->ID)
        ) {
            continue;
        }

        $permalink = get_permalink($post->ID);
        if (!$permalink)
            continue;

        $urls[] = [
            'loc' => $permalink,
            'lastmod' => get_the_modified_date('c', $post->ID),
        ];

        // Publish a Spanish URL only when its post has translated content.
        $rel_path = trim(str_replace($base, '', $permalink), '/');
        if ($rel_path && !str_starts_with($rel_path, 'es/') && chroma_post_has_verified_spanish_variant($post->ID)) {
            $urls[] = [
                'loc' => $base . '/es/' . $rel_path . '/',
                'lastmod' => get_the_modified_date('c', $post->ID),
            ];
        }
    }

    $unique_urls = [];
    foreach ($urls as $entry) {
        $loc = isset($entry['loc']) ? (string) $entry['loc'] : '';
        if ($loc === '') {
            continue;
        }

        $unique_urls[$loc] = $entry;
    }

    return array_values($unique_urls);
}

/**
 * Preserve dynamic SEO routes from core canonical redirects.
 */
function chroma_preserve_dynamic_route_redirects($redirect_url, $requested_url)
{
    $path = wp_parse_url((string) $requested_url, PHP_URL_PATH);
    if (!is_string($path)) {
        return $redirect_url;
    }

    if (preg_match('#/(wp-sitemap(?:-[^/]+)?\.xml|sitemap(?:_index)?\.xml|sitemap-[^/]+\.xml)$#i', $path)) {
        return false;
    }

    if (preg_match('#^/(es/)?[a-z0-9-]+-in-[a-z-]+-[a-z]{2}/?$#i', $path)) {
        return false;
    }

    if (preg_match('#^/(es/)?[a-z0-9-]+-near(?:-me|-[a-z-]+-[a-z]{2})/?$#i', $path)) {
        return false;
    }

    if (preg_match('#^/(es/)?daycare-\d{5}/?$#i', $path) || preg_match('#^/(es/)?childcare-in-[a-z0-9-]+-county/?$#i', $path)) {
        return false;
    }

    return $redirect_url;
}
add_filter('redirect_canonical', 'chroma_preserve_dynamic_route_redirects', 1, 2);

/**
 * Build the canonical path for dynamic combo and near-me routes.
 *
 * @param string $path Request path.
 * @return string
 */
function chroma_get_dynamic_route_canonical_path($path)
{
    if (!is_string($path) || $path === '') {
        return '';
    }

    if (preg_match('#^/(es/)?([a-z0-9-]+)-in-([a-z-]+)-([a-z]{2})/?$#i', $path, $matches)) {
        $segments = [];
        if (!empty($matches[1])) {
            $segments[] = 'es';
        }

        $program_slug = sanitize_title($matches[2]);
        $city_slug = sanitize_title($matches[3]);
        $state = strtolower($matches[4]);

        if (function_exists('chroma_seo_resolve_virtual_city_context')) {
            $city_context = chroma_seo_resolve_virtual_city_context($city_slug, $state);
            if (is_array($city_context) && !empty($city_context['canonical_slug'])) {
                $city_slug = sanitize_title((string) $city_context['canonical_slug']);
            }
        }

        $segments[] = $program_slug . '-in-' . $city_slug . '-' . $state;
        return '/' . implode('/', $segments) . '/';
    }

    if (preg_match('#^/(es/)?([a-z0-9-]+)-near-me/?$#i', $path, $matches)) {
        $segments = [];
        if (!empty($matches[1])) {
            $segments[] = 'es';
        }

        $segments[] = sanitize_title($matches[2]) . '-near-me';
        return '/' . implode('/', $segments) . '/';
    }

    if (preg_match('#^/(es/)?([a-z0-9-]+)-near-([a-z-]+)-([a-z]{2})/?$#i', $path, $matches)) {
        $segments = [];
        if (!empty($matches[1])) {
            $segments[] = 'es';
        }

        $keyword = sanitize_title($matches[2]);
        $city_slug = sanitize_title($matches[3]);
        $state = strtolower($matches[4]);

        if (function_exists('chroma_seo_resolve_virtual_city_context')) {
            $city_context = chroma_seo_resolve_virtual_city_context($city_slug, $state);
            if (is_array($city_context) && !empty($city_context['canonical_slug'])) {
                $city_slug = sanitize_title((string) $city_context['canonical_slug']);
            }
        }

        $segments[] = $keyword . '-near-' . $city_slug . '-' . $state;
        return '/' . implode('/', $segments) . '/';
    }

    if (preg_match('#^/(es/)?daycare-(\d{5})/?$#i', $path, $matches)) {
        $segments = [];
        if (!empty($matches[1])) {
            $segments[] = 'es';
        }

        $segments[] = 'daycare-' . $matches[2];
        return '/' . implode('/', $segments) . '/';
    }

    if (preg_match('#^/(es/)?childcare-in-([a-z0-9-]+)-county/?$#i', $path, $matches)) {
        $segments = [];
        if (!empty($matches[1])) {
            $segments[] = 'es';
        }

        $segments[] = 'childcare-in-' . sanitize_title($matches[2]) . '-county';
        return '/' . implode('/', $segments) . '/';
    }

    return '';
}

/**
 * Normalize dynamic combo and near-me routes to their slash canonical.
 */
function chroma_redirect_dynamic_route_canonical()
{
    if (is_admin() || wp_doing_ajax() || !isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return;
    }

    $canonical_path = chroma_get_dynamic_route_canonical_path($path);
    if ($canonical_path === '' || $canonical_path === $path) {
        return;
    }

    $query = wp_parse_url($request_uri, PHP_URL_QUERY);
    $target = home_url($canonical_path);
    if (is_string($query) && $query !== '') {
        $target .= '?' . $query;
    }

    wp_safe_redirect($target, 301);
    exit;
}
add_action('template_redirect', 'chroma_redirect_dynamic_route_canonical', 0);

/**
 * Prevent custom canonical enforcer from redirecting dynamic routes to home.
 */
function chroma_disable_custom_canonical_redirect_for_dynamic_routes($pre_option, $option, $default)
{
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return $pre_option;
    }

    if (preg_match('#/(wp-sitemap(?:-[^/]+)?\.xml|sitemap(?:_index)?\.xml|sitemap-[^/]+\.xml)$#i', $path)) {
        return false;
    }

    if (preg_match('#^/(es/)?[a-z0-9-]+-in-[a-z-]+-[a-z]{2}/?$#i', $path)) {
        return false;
    }

    if (preg_match('#^/(es/)?[a-z0-9-]+-near(?:-me|-[a-z-]+-[a-z]{2})/?$#i', $path)) {
        return false;
    }

    return $pre_option;
}
add_filter('pre_option_chroma_seo_redirect_canonical', 'chroma_disable_custom_canonical_redirect_for_dynamic_routes', 10, 3);

/**
 * Title Length Optimization for SEO
 * Ensures titles stay within recommended limits
 */
function chroma_optimize_title_length($title_parts)
{
    return $title_parts;
}
add_filter('document_title_parts', 'chroma_optimize_title_length', 10);

/**
 * Use shorter title separator for cleaner titles
 */
function chroma_title_separator($sep)
{
    return '|';
}
add_filter('document_title_separator', 'chroma_title_separator');

/**
 * Disable Speculation Rules
 * Prevents browser prefetching/prerendering which can cause issues with dynamic content
 */
// Programmatically disable Speculation Rules API from WordPress Core or Performance Lab plugin
remove_action('wp_head', 'wp_speculation_rules');
remove_action('wp_footer', 'wp_speculation_rules');
add_filter('wp_speculation_rules_configuration', '__return_empty_array', PHP_INT_MAX);
add_filter('pl_speculation_rules_configuration', '__return_empty_array', PHP_INT_MAX);

/**
 * Performance Profiling Helper
 * Appends debug info to the end of the page for users with specific permission
 */
add_action('wp_footer', function () {
    if (!defined('CHROMA_PERF_PROFILE') || !CHROMA_PERF_PROFILE) {
        return;
    }

    if (!current_user_can('manage_options') && !isset($_GET['perf_debug'])) {
        return;
    }

    $queries = get_num_queries();
    $time = timer_stop(0, 4);
    $memory = round(memory_get_peak_usage() / 1024 / 1024, 2);

    echo "\n<!-- \nPERFORMANCE AUDIT:\n";
    echo "Total Queries: $queries\n";
    echo "Execution Time: $time seconds\n";
    echo "Peak Memory: $memory MB\n";
    echo "-->\n";
}, 999);

/**
 * Register [chroma_contact_form] shortcode
 */
function chroma_register_contact_form_shortcode()
{
    add_shortcode('chroma_contact_form', 'chroma_render_contact_form');
}
add_action('init', 'chroma_register_contact_form_shortcode');

/**
 * Render Contact Form
 */
/**
 * Render Contact Form (AJAX Enhanced)
 * Used in page-contact.php via [chroma_contact_form] shortcode.
 * RESTORED: Now uses the Chroma Tour Form plugin for GHL integration.
 */
function chroma_render_contact_form()
{
    if (function_exists('chroma_contact_form_shortcode')) {
        return chroma_contact_form_shortcode();
    }

    $form_id = get_option('chroma_contact_form_id', 'ibinKhrBmF0n4S5tFcz6');
    if (!$form_id || '848tl2LjoZVsUIhhNOxd' === $form_id) {
        $form_id = 'ibinKhrBmF0n4S5tFcz6';
    }
    $form_url = 'https://api.leadconnectorhq.com/widget/form/' . rawurlencode($form_id);

    return sprintf(
        '<div class="chroma-contact-form-wrapper"><iframe src="%1$s" loading="lazy" title="%2$s" style="width:100%%;height:779px;border:0;border-radius:1.5rem" allow="clipboard-write"></iframe></div>',
        esc_url($form_url),
        esc_attr__('Contact Chroma Early Learning', 'chroma-excellence')
    );
}

/**
 * Determine whether a post has a verified Spanish variant suitable for indexing.
 */
function chroma_post_has_verified_spanish_variant($post_id)
{
    $post_id = absint($post_id);
    if (!$post_id) {
        return false;
    }

    $alternate_url = trim((string) get_post_meta($post_id, 'alternate_url_es', true));
    if ($alternate_url !== '') {
        return true;
    }

    $looks_spanish = static function ($value) {
        $value = trim(wp_strip_all_tags(html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8')));
        if ($value === '') {
            return false;
        }
        if (preg_match('/[áéíóúüñ¿¡]/iu', $value)) {
            return true;
        }
        $normalized = ' ' . strtolower($value) . ' ';
        $signals = array(' el ', ' la ', ' los ', ' las ', ' de ', ' del ', ' que ', ' para ', ' con ', ' una ', ' un ', ' niños', ' familias', ' aprendizaje', ' desarrollo', ' programa', ' cuidado', ' maestros', ' padres', ' escuela', ' nuestro', ' nuestra', ' cada ');
        $matches = 0;
        foreach ($signals as $signal) {
            if (strpos($normalized, $signal) !== false) {
                $matches++;
            }
        }
        return $matches >= 3;
    };

    $post = get_post($post_id);
    if ($post instanceof WP_Post && $post->post_type === 'post') {
        return $looks_spanish(get_post_meta($post_id, '_chroma_es_content', true));
    }

    if ($post instanceof WP_Post && $post->post_type === 'page' && $post->post_name === 'chroma-early-start') {
        return $looks_spanish(get_post_meta($post_id, '_chroma_es_content', true));
    }

    foreach (array('_chroma_es_title', '_chroma_es_content', '_chroma_es_excerpt', '_chroma_es_meta_description', '_chroma_es_seo_title') as $meta_key) {
        if (trim((string) get_post_meta($post_id, $meta_key, true)) !== '') {
            return true;
        }
    }

    global $wpdb;
    $translated_rows = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s AND meta_value <> ''",
        $post_id,
        $wpdb->esc_like('_chroma_es_') . '%'
    ));

    return $translated_rows > 0;
}

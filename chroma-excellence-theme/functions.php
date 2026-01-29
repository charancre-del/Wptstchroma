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

// Enable error reporting for development (Remedy Step: Quality Hardening)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

/**
 * Increase Memory Limit for SEO Engine
 */
@ini_set('memory_limit', '256M');

/**
 * Define theme constants
 */
define('CHROMA_VERSION', '1.0.0');
define('CHROMA_THEME_DIR', get_template_directory());
define('CHROMA_THEME_URI', get_template_directory_uri());

/**
 * Plugin Polyfills
 * Prevent theme crash if Chroma SEO Pro plugin is disabled
 */
if (!function_exists('chroma_url')) {
    function chroma_url($path = '')
    {
        return home_url($path);
    }
}

if (!function_exists('chroma_get_theme_mod')) {
    /**
     * Get theme mod with fallback
     * Simple wrapper around get_theme_mod
     *
     * @param string $name Theme mod name
     * @param mixed $default Default value
     * @return mixed
     */
    function chroma_get_theme_mod($name, $default = false)
    {
        return get_theme_mod($name, $default);
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
        'team_member' => array('team'),
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
 * Load core theme functionality
 * Order matters - load dependencies first
 */

// Core setup and configuration
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

// API Handlers


// Page Meta Boxes - Admin Only
if (is_admin()) {
    require_once CHROMA_THEME_DIR . '/inc/about-page-meta.php';
    require_once CHROMA_THEME_DIR . '/inc/curriculum-page-meta.php';
    require_once CHROMA_THEME_DIR . '/inc/contact-page-meta.php';
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
// require_once CHROMA_THEME_DIR . '/inc/about-seo.php';
// Customizers - Admin or Preview Only
if (is_admin() || is_customize_preview()) {
    require_once CHROMA_THEME_DIR . '/inc/customizer-home.php';
    require_once CHROMA_THEME_DIR . '/inc/customizer-header.php';
    require_once CHROMA_THEME_DIR . '/inc/customizer-footer.php';
    require_once CHROMA_THEME_DIR . '/inc/customizer-locations.php';
    require_once CHROMA_THEME_DIR . '/inc/customizer-seo.php';
    require_once CHROMA_THEME_DIR . '/inc/customizer-scripts.php';
}

// Legacy helper files (ACF plugin optional; helpers run on core WP functions only)
require_once CHROMA_THEME_DIR . '/inc/acf-options.php';
require_once CHROMA_THEME_DIR . '/inc/acf-homepage.php';

require_once CHROMA_THEME_DIR . '/inc/cleanup.php';

// SEO and Internationalization
// require_once CHROMA_THEME_DIR . '/inc/seo-engine.php';
require_once CHROMA_THEME_DIR . '/inc/city-slug-logic.php';
// require_once CHROMA_THEME_DIR . '/inc/spanish-variant-generator.php';
require_once CHROMA_THEME_DIR . '/inc/monthly-seo-cron.php';

// LLM SEO / Citation Module (Legacy - Disabled to prevent conflict with Advanced SEO/LLM)
// require_once CHROMA_THEME_DIR . '/inc/llm-seo/bootstrap.php';

// Advanced SEO/LLM Module - MOVED TO PLUGIN
// require_once CHROMA_THEME_DIR . '/inc/advanced-seo-llm/bootstrap.php';

// SEO Automations (Internal Linking, Geo SEO, etc.) - MOVED TO PLUGIN
// require_once CHROMA_THEME_DIR . '/inc/seo-automations/bootstrap.php';

// Spanish Variant Generator
require_once CHROMA_THEME_DIR . '/inc/spanish-variant-generator.php';



require_once CHROMA_THEME_DIR . '/inc/security.php';
require_once CHROMA_THEME_DIR . '/inc/force-trailing-slashes.php';

/**
 * Remove Legacy JavaScript & Styles
 * - WP Emoji
 * - WP Embeds
 */
function chroma_remove_legacy_assets()
{
    // Remove Emoji - DISABLED (User requested emojis back)
    // remove_action('wp_head', 'print_emoji_detection_script', 7);
    // remove_action('admin_print_scripts', 'print_emoji_detection_script');
    // remove_action('wp_print_styles', 'print_emoji_styles');
    // remove_action('admin_print_styles', 'print_emoji_styles');
    // remove_filter('the_content_feed', 'wp_staticize_emoji');
    // remove_filter('comment_text_rss', 'wp_staticize_emoji');
    // remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

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
        wp_dequeue_style('classic-theme-styles');
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
    // Using optimized logo as LCP candidate since specific hero image is missing
    $logo_url = get_template_directory_uri() . '/assets/images/logo_chromacropped_140x140.webp';
    echo '<link rel="preload" as="image" href="' . esc_url($logo_url) . '" fetchpriority="high">' . "\n";
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
 * Title Length Optimization for SEO
 * Ensures titles stay within recommended limits
 */
function chroma_optimize_title_length($title_parts)
{
    // Truncate very long titles
    if (isset($title_parts['title']) && mb_strlen($title_parts['title']) > 50) {
        $title_parts['title'] = mb_substr($title_parts['title'], 0, 47) . '...';
    }

    // Use shorter site name suffix on blog posts
    if (is_single() && isset($title_parts['site'])) {
        $title_parts['site'] = 'Chroma';
    }

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

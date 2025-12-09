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
 * Load core theme functionality
 * Order matters - load dependencies first
 */

// Core setup and configuration
require_once CHROMA_THEME_DIR . '/inc/setup.php';
require_once CHROMA_THEME_DIR . '/inc/critical-css.php';
require_once CHROMA_THEME_DIR . '/inc/enqueue.php';
require_once CHROMA_THEME_DIR . '/inc/program-settings.php';
require_once CHROMA_THEME_DIR . '/inc/nav-menus.php';

// Custom Post Types
require_once CHROMA_THEME_DIR . '/inc/cpt-programs.php';
require_once CHROMA_THEME_DIR . '/inc/cpt-locations.php';
require_once CHROMA_THEME_DIR . '/inc/cpt-cities.php';
require_once CHROMA_THEME_DIR . '/inc/cpt-team-members.php';

// API Handlers
require_once CHROMA_THEME_DIR . '/inc/careers-api.php';

// Page Meta Boxes
require_once CHROMA_THEME_DIR . '/inc/about-page-meta.php';
require_once CHROMA_THEME_DIR . '/inc/curriculum-page-meta.php';
require_once CHROMA_THEME_DIR . '/inc/contact-page-meta.php';
require_once CHROMA_THEME_DIR . '/inc/stories-page-meta.php';
require_once CHROMA_THEME_DIR . '/inc/parents-page-meta.php';
require_once CHROMA_THEME_DIR . '/inc/careers-page-meta.php';
require_once CHROMA_THEME_DIR . '/inc/employers-page-meta.php';
require_once CHROMA_THEME_DIR . '/inc/privacy-page-meta.php';
require_once CHROMA_THEME_DIR . '/inc/schema-meta-boxes.php';
require_once CHROMA_THEME_DIR . '/inc/general-seo-meta.php';


// Utility Functions
require_once CHROMA_THEME_DIR . '/inc/template-tags.php';
require_once CHROMA_THEME_DIR . '/inc/about-seo.php';
require_once CHROMA_THEME_DIR . '/inc/customizer-home.php';
require_once CHROMA_THEME_DIR . '/inc/customizer-header.php';
require_once CHROMA_THEME_DIR . '/inc/customizer-footer.php';
require_once CHROMA_THEME_DIR . '/inc/customizer-locations.php';

// Legacy helper files (ACF plugin optional; helpers run on core WP functions only)
require_once CHROMA_THEME_DIR . '/inc/acf-options.php';
require_once CHROMA_THEME_DIR . '/inc/acf-homepage.php';

require_once CHROMA_THEME_DIR . '/inc/cleanup.php';

// SEO and Internationalization
require_once CHROMA_THEME_DIR . '/inc/seo-engine.php';
require_once CHROMA_THEME_DIR . '/inc/city-slug-logic.php';
require_once CHROMA_THEME_DIR . '/inc/spanish-variant-generator.php';
require_once CHROMA_THEME_DIR . '/inc/monthly-seo-cron.php';

// LLM SEO / Citation Module (Legacy - Disabled to prevent conflict with Advanced SEO/LLM)
// require_once CHROMA_THEME_DIR . '/inc/llm-seo/bootstrap.php';

// Advanced SEO/LLM Module (Editable Fields)
require_once CHROMA_THEME_DIR . '/inc/advanced-seo-llm/bootstrap.php';



require_once CHROMA_THEME_DIR . '/inc/security.php';
require_once CHROMA_THEME_DIR . '/inc/critical-css.php';

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
 * Move jQuery to footer for better performance
 * This prevents jQuery from blocking initial render
/**
 * Defer non-critical third-party scripts.
 */
function chroma_defer_scripts($tag, $handle, $src)
{
    // List of scripts to defer
    $defer_scripts = array('jquery.min.js', 'jquery-migrate.min.js', 'gtag', 'did-0014');

    foreach ($defer_scripts as $script) {
        if ($src && strpos($src, $script) !== false) {
            return str_replace(' src', ' defer src', $tag);
        }
    }

    return $tag;
}
add_filter('script_loader_tag', 'chroma_defer_scripts', 10, 3);
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
 * Lazy Load LeadConnector Widget
 * Delays loading until 3 seconds after page load or on first user interaction
 * This prevents the widget from blocking initial render and improves LCP/FCP
 */
function chroma_lazy_load_leadconnector()
{

    ?>
    <script>
        (function () {
            var loaded = false;

            // Function to load the widget
            var loadWidget = function () {
                if (loaded) return;
                loaded = true;

                // Find existing LeadConnector script if present and remove it
                var existingScripts = document.querySelectorAll('script[src*="leadconnectorhq.com"]');
                existingScripts.forEach(function (script) {
                    script.remove();
                });

                // Load the widget script
                var script = document.createElement('script');
                script.src = 'https://widgets.leadconnectorhq.com/loader.js';
                script.setAttribute('data-resources-url', 'https://widgets.leadconnectorhq.com/chat-widget/loader.js');
                script.async = true;
                document.body.appendChild(script);
                console.log('LeadConnector Widget Loaded');
            };

            // Device Detection Logic (Client-Side)
            var isMobile = window.innerWidth < 768;

            if (isMobile) {
                // Mobile: Lazy load after 3.5 seconds
                console.log('Mobile detected: Lazy loading LeadConnector (3.5s delay)');
                setTimeout(loadWidget, 3500);

                // Or on user interaction
                var events = ['mousedown', 'touchstart', 'keydown', 'scroll'];
                events.forEach(function (event) {
                    window.addEventListener(event, loadWidget, { once: true, passive: true });
                });
            } else {
                // Desktop: Load immediately (but defer slightly to let LCP finish)
                console.log('Desktop detected: Loading LeadConnector immediately');
                setTimeout(loadWidget, 100);
            }
        })();
    </script>
    <?php
}
add_action('wp_footer', 'chroma_lazy_load_leadconnector', 999);


<?php
/**
 * Enqueue scripts and styles.
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly.
}

/**
 * Detect app-like routes that should not load marketing globals.
 *
 * @return bool
 */
function chroma_is_app_shell_route()
{
        $chroma_view = (string) get_query_var('chroma_view');
        if ($chroma_view === 'portal' || $chroma_view === 'tv') {
                return true;
        }

        if ((string) get_query_var('cqa_page') !== '') {
                return true;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        if ($request_uri === '') {
                return false;
        }

        return (strpos($request_uri, '/portal') === 0 || strpos($request_uri, '/qa-reports') === 0);
}

/**
 * Determine whether map assets should be enqueued.
 */
function chroma_should_load_maps()
{
        if (chroma_is_app_shell_route()) {
                return false;
        }

        $should_load_maps = is_post_type_archive('location') || is_singular('location') || is_page('locations') || is_page_template('page-summer-camp.php');

        if (is_front_page() && function_exists('chroma_home_locations_preview')) {
                $locations_preview = chroma_home_locations_preview();
                $should_load_maps = $should_load_maps || (!empty($locations_preview['map_points']));
        }

        return $should_load_maps;
}

/**
 * Read optional hashed asset manifest generated during build.
 *
 * @return array<string,string>
 */
function chroma_get_asset_manifest()
{
        static $manifest = null;
        if (is_array($manifest)) {
                return $manifest;
        }

        $manifest = array();
        $manifest_path = CHROMA_THEME_DIR . '/assets/manifest.json';
        if (!file_exists($manifest_path)) {
                return $manifest;
        }

        $decoded = json_decode((string) file_get_contents($manifest_path), true);
        if (is_array($decoded)) {
                $manifest = $decoded;
        }

        return $manifest;
}

/**
 * Resolve theme asset URL with hashed-filename support and cache-safe versioning.
 *
 * @param string $relative_path Path relative to theme root (e.g. assets/css/main.css).
 * @return array{url:string,path:string,version:?string,immutable:bool}
 */
function chroma_get_theme_asset($relative_path)
{
        static $content_hash_cache = array();

        $relative_path = ltrim((string) $relative_path, '/');
        $manifest = chroma_get_asset_manifest();
        $resolved_relative = isset($manifest[$relative_path]) ? ltrim((string) $manifest[$relative_path], '/') : $relative_path;

        $disk_path = CHROMA_THEME_DIR . '/' . $resolved_relative;
        if (!file_exists($disk_path)) {
                $disk_path = CHROMA_THEME_DIR . '/' . $relative_path;
                $resolved_relative = $relative_path;
        }

        $url = CHROMA_THEME_URI . '/' . $resolved_relative;
        $immutable = ($resolved_relative !== $relative_path);

        if ($immutable) {
                return array(
                        'url' => $url,
                        'path' => $disk_path,
                        'version' => null,
                        'immutable' => true,
                );
        }

        if (!isset($content_hash_cache[$disk_path])) {
                $content_hash_cache[$disk_path] = file_exists($disk_path)
                        ? substr(md5_file($disk_path), 0, 12)
                        : (string) CHROMA_VERSION;
        }

        return array(
                'url' => $url,
                'path' => $disk_path,
                'version' => (string) $content_hash_cache[$disk_path],
                'immutable' => false,
        );
}

/**
 * Check whether current singular content has one of the provided shortcodes.
 *
 * @param string[] $shortcodes
 */
function chroma_page_has_shortcode(array $shortcodes)
{
        $post_id = get_queried_object_id();
        if (!$post_id) {
                return false;
        }

        $content = (string) get_post_field('post_content', $post_id);
        if ($content === '') {
                return false;
        }

        foreach ($shortcodes as $shortcode) {
                if (has_shortcode($content, $shortcode)) {
                        return true;
                }
        }

        return false;
}

/**
 * Determine if visual-effects bundle should load for current route.
 */
function chroma_should_load_effects_bundle()
{
        return is_front_page()
                || is_page(array('about', 'locations', 'programs', 'schedule-tour', 'schedule-a-tour'))
                || is_page_template('page-summer-camp.php')
                || is_post_type_archive('location')
                || is_post_type_archive('program')
                || is_singular(array('location', 'program'))
                || is_post_type_archive('city');
}

/**
 * Determine if form-specific bundle should load for current route.
 */
function chroma_should_load_forms_bundle()
{
        return is_page(array('contact', 'careers', 'schedule-tour', 'schedule-a-tour'))
                || is_singular('post')
                || is_page_template('page-summer-camp.php')
                || chroma_page_has_shortcode(array('chroma_contact_form', 'chroma_career_form', 'chroma_tour_form', 'contact-form-7'));
}

/**
 * Enqueue theme styles and scripts
 */
function chroma_enqueue_assets()
{
        $script_dependencies = array();
        $skip_global_scripts = chroma_is_app_shell_route();

        $fa_asset = chroma_get_theme_asset('assets/css/font-awesome.css');
        wp_enqueue_style(
                'chroma-font-awesome',
                $fa_asset['url'],
                array(),
                $fa_asset['version'],
                'all'
        );

        // Chart.js - Removed global enqueue. Lazy loaded in main.js
        // via IntersectionObserver when #curriculum section is visible.

        // Compiled core CSS.
        $main_css_asset = chroma_get_theme_asset('assets/css/main.css');

        // Compiled Tailwind CSS - loads synchronously
        wp_enqueue_style(
                'chroma-main',
                $main_css_asset['url'],
                array(),
                $main_css_asset['version'],
                'all' // Load normally to prevent FOUC
        );

        // CRITICAL ACCESSIBILITY FIXES (Injected Inline to bypass cache/build)
        $custom_css = "
                /* Darkened Brand Colors for WCAG AA Compliance (Enhanced) */
                .text-chroma-red { color: #964030 !important; }
                .bg-chroma-red { background-color: #964030 !important; }
                .text-chroma-orange { color: #A8551E !important; }
                .bg-chroma-orange { background-color: #A8551E !important; }
                .text-chroma-green { color: #4D5C54 !important; }
                .bg-chroma-green { background-color: #4D5C54 !important; }
                .text-chroma-yellow { color: #8C6B2F !important; }
                .bg-chroma-yellow { background-color: #8C6B2F !important; }
                
                /* Footer Social Links - Touch Target Fix (48px) */
                footer .flex.gap-3 a {
                        width: 48px !important;
                        height: 48px !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                }
                footer .flex.gap-3 a i {
                        font-size: 1.25rem !important;
                }
                
                /* Footer Navigation Links - Touch Target Fix */
                footer nav a {
                        display: inline-block !important;
                        min-height: 48px !important;
                        min-width: 48px !important;
                        padding: 12px 16px !important;
                        line-height: 1.5 !important;
                        display: flex !important;
                        align-items: center !important;
                }
                
                /* Review Carousel Dots - Touch Target Fix (48px) */
                [data-reviews-dots] button {
                        min-width: 48px !important;
                        min-height: 48px !important;
                        padding: 12px !important;
                }

                /* Global Button Touch Targets */
                a[class*='px-8'][class*='py-4'], 
                button[class*='px-8'][class*='py-4'] {
                        min-height: 48px !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                }

                /* Form Inputs Touch Targets */
                input[type='text'],
                input[type='email'],
                input[type='tel'],
                input[type='number'],
                select,
                textarea {
                        min-height: 48px !important;
                        font-size: 16px !important; /* Prevent iOS zoom */
                }

                /* Accessibility: Increase contrast for muted text */
                .text-brand-ink\/60 { color: rgba(38, 50, 56, 0.9) !important; }
                .text-brand-ink\/70 { color: rgba(38, 50, 56, 0.95) !important; }
        ";
        wp_add_inline_style('chroma-main', $custom_css);

        // Page-type bundles for low-use styles.
        if (chroma_should_load_effects_bundle()) {
                $effects_asset = chroma_get_theme_asset('assets/css/page-effects.css');
                wp_enqueue_style('chroma-page-effects', $effects_asset['url'], array('chroma-main'), $effects_asset['version'], 'all');
        }

        if (chroma_should_load_forms_bundle()) {
                $forms_asset = chroma_get_theme_asset('assets/css/page-forms.css');
                wp_enqueue_style('chroma-page-forms', $forms_asset['url'], array('chroma-main'), $forms_asset['version'], 'all');
        }

        if (is_page('careers')) {
                $careers_asset = chroma_get_theme_asset('assets/css/page-careers.css');
                wp_enqueue_style('chroma-page-careers', $careers_asset['url'], array('chroma-main'), $careers_asset['version'], 'all');
        }

        // Main JavaScript.
        $main_js_asset = chroma_get_theme_asset('assets/js/main.js');

        if (!$skip_global_scripts) {
                wp_enqueue_script(
                        'chroma-main-js',
                        $main_js_asset['url'],
                        $script_dependencies,
                        $main_js_asset['version'],
                        true
                );

                // Defer re-enabled for FCP optimization
                wp_script_add_data('chroma-main-js', 'defer', true);

                // Map Facade (Lazy Load Leaflet).
                $should_load_maps = chroma_should_load_maps();

                if ($should_load_maps) {
                        $map_js_asset = chroma_get_theme_asset('assets/js/map-facade.js');
                        wp_enqueue_script(
                                'chroma-map-facade',
                                $map_js_asset['url'],
                                array('chroma-main-js'), // Depend on main to ensure chromaData is available
                                $map_js_asset['version'],
                                true
                        );
                        wp_script_add_data('chroma-map-facade', 'defer', true);
                }

                $map_layer_asset = chroma_get_theme_asset('assets/js/map-layer.js');
                $map_layer_url = $map_layer_asset['url'];
                if (!empty($map_layer_asset['version'])) {
                        $map_layer_url = add_query_arg('ver', rawurlencode((string) $map_layer_asset['version']), $map_layer_url);
                }

                // Localize script for AJAX and dynamic data.
                wp_localize_script(
                        'chroma-main-js',
                        'chromaData',
                        array(
                                'ajaxUrl' => admin_url('admin-ajax.php'),
                                'nonce' => wp_create_nonce('chroma_nonce'),
                                'themeUrl' => CHROMA_THEME_URI,
                                'mapLayerUrl' => $map_layer_url,
                                'homeUrl' => home_url(),
                                'viewCampus' => __('View campus', 'chroma-excellence'),
                        )
                );
        }
}
add_action('wp_enqueue_scripts', 'chroma_enqueue_assets');



/**
 * Add resource hints for external assets to improve initial page performance.
 */
function chroma_resource_hints($urls, $relation_type)
{
        // Keep this conservative: no broad global hints.
        // Third-party form/map scripts are intent-loaded and should not force early connections.
        return $urls;
}
add_filter('wp_resource_hints', 'chroma_resource_hints', 10, 2);

/**
 * Enqueue admin assets
 */
function chroma_enqueue_admin_assets($hook)
{
        // Only load on post edit screens
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
                return;
        }

        // Font Awesome for icon previews in admin (using local version)
        $admin_fa_asset = chroma_get_theme_asset('assets/css/font-awesome.css');

        wp_enqueue_style(
                'font-awesome-admin',
                $admin_fa_asset['url'],
                array(),
                $admin_fa_asset['version'] // Use same version as frontend for consistency
        );

        // Media uploader
        wp_enqueue_media();

        // Custom admin script for media uploader
        $admin_js_asset = chroma_get_theme_asset('assets/js/admin.js');
        wp_enqueue_script(
                'chroma-admin',
                $admin_js_asset['url'],
                array('jquery', 'media-editor', 'media-views'),
                $admin_js_asset['version'],
                true
        );
}
add_action('admin_enqueue_scripts', 'chroma_enqueue_admin_assets');

/**
 * Async load CSS for fonts only (not main CSS to prevent FOUC)
 */
function chroma_is_layout_critical_route()
{
        if (is_front_page()) {
                return true;
        }

        // Blog single pages need stable, non-async layout CSS to avoid CLS.
        if (is_singular('post')) {
                return true;
        }

        if (is_page(array('contact', 'careers', 'career', 'tour', 'schedule-a-tour'))) {
                return true;
        }

        $post_id = get_queried_object_id();
        if (!$post_id) {
                return false;
        }

        $content = (string) get_post_field('post_content', $post_id);
        if ($content === '') {
                return false;
        }

        $layout_critical_shortcodes = array(
                'chroma_contact_form',
                'chroma_career_form',
                'chroma_tour_form',
                'contact-form-7',
        );

        foreach ($layout_critical_shortcodes as $shortcode) {
                if (has_shortcode($content, $shortcode)) {
                        return true;
                }
        }

        return false;
}

/**
 * Async load CSS for non-critical routes.
 */
function chroma_async_styles($html, $handle, $href, $media)
{
        if (!in_array($handle, array('chroma-font-awesome', 'chroma-main', 'chroma-page-effects'), true)) {
                return $html;
        }

        // Prevent downstream optimizers from recombining this tag.
        $html = str_replace('<link', '<link data-no-optimize="1"', $html);

        // Keep core layout stylesheet synchronous for visual stability on critical routes.
        if ($handle === 'chroma-main' && chroma_is_layout_critical_route()) {
                return $html;
        }

        // Effects bundle is non-critical; always load asynchronously.
        // Other handles continue to load async on non-critical routes.
        $html = str_replace("media='all'", "media='print' onload=\"this.media='all'\"", $html);
        $html = str_replace('media="all"', "media='print' onload=\"this.media='all'\"", $html);
        $html = str_replace("media='print'", "media='print' onload=\"this.media='all'\"", $html);
        $html = str_replace('media="print"', "media='print' onload=\"this.media='all'\"", $html);

        if (strpos($html, '<noscript><link rel=') === false) {
                $html .= "<noscript><link rel='stylesheet' href='" . esc_url($href) . "' media='all'></noscript>";
        }

        return $html;
}
add_filter('style_loader_tag', 'chroma_async_styles', 10, 4);

/**
 * Move jQuery to Footer and Make Loading Conditional (LCP Optimization)
 * 
 * LIGHTHOUSE FIX: Reduce unused JavaScript
 * Only loads jQuery if another script explicitly depends on it.
 * Deregisters core jQuery and re-registers in footer only when needed.
 */
function chroma_move_jquery_to_footer()
{
        if (is_admin()) {
                return;
        }

        // Keep app routes lean and avoid overriding plugin-owned script bootstraps.
        if (chroma_is_app_shell_route()) {
                return;
        }

        wp_deregister_script('jquery-core');
        wp_deregister_script('jquery-migrate');

        // Register jQuery in footer. It is loaded only if another script depends on it.
        wp_register_script('jquery', includes_url('js/jquery/jquery.min.js'), array(), null, true);
}

add_action('wp_enqueue_scripts', 'chroma_move_jquery_to_footer', 1);

/**
 * Dequeue Dashicons for non-logged in users to improve performance
 */
function chroma_dequeue_dashicons()
{
        if (!is_user_logged_in()) {
                wp_dequeue_style('dashicons');
                wp_deregister_style('dashicons');
        }
}
/**
 * Preload Main CSS to minimize FOUC (Flash of Unstyled Content)
 */
function chroma_preload_main_css()
{
        $main_css_asset = chroma_get_theme_asset('assets/css/main.css');
        $href = $main_css_asset['url'];
        if (!empty($main_css_asset['version'])) {
                $href = add_query_arg('ver', rawurlencode((string) $main_css_asset['version']), $href);
        }

        echo '<link rel="preload" href="' . esc_url($href) . '" as="style">' . "\n";
}
add_action('wp_head', 'chroma_preload_main_css', 1);

add_action('wp_enqueue_scripts', 'chroma_dequeue_dashicons');


/**
 * Dequeue CDN styles (specifically Font Awesome) to force local loading.
 * Runs at priority 100 to ensure it runs after plugins.
 */
function chroma_dequeue_cdn_styles()
{
        global $wp_styles;
        if (empty($wp_styles->queue)) {
                return;
        }

        foreach ($wp_styles->queue as $handle) {
                if (!isset($wp_styles->registered[$handle])) {
                        continue;
                }

                $src = $wp_styles->registered[$handle]->src;

                // Check if it's Font Awesome and coming from a CDN
                if (
                        (strpos($handle, 'font-awesome') !== false || strpos($handle, 'fontawesome') !== false || strpos($handle, 'fa-') !== false) &&
                        (strpos($src, 'cdnjs') !== false || strpos($src, 'cloudflare') !== false || strpos($src, 'jsdelivr') !== false)
                ) {
                        wp_dequeue_style($handle);
                        wp_deregister_style($handle);
                }
        }
}
add_action('wp_enqueue_scripts', 'chroma_dequeue_cdn_styles', 100);

/**
 * Add performance attributes to enqueued scripts
 */
function chroma_optimize_script_tags($tag, $handle, $src)
{
        // Strict allowlist so only known-safe handles receive defer/async.
        $defer_allowlist = array(
                'chroma-main-js',
                'chroma-map-facade',
                'chroma-tv-pdfjs',
                'chroma-tv-dashboard-js',
                'chroma-portal-js',
                'cqa-frontend',
                'cqa-runtime-guard',
                'cqa-react-app',
        );

        $async_allowlist = array();

        // 2. Scripts for Low Priority (Non-Critical)
        $low_priority = array('chroma-map-facade', 'leadconnector-widget', 'lc-widget');

        if (is_admin()) {
                return $tag;
        }

        if (in_array($handle, $async_allowlist, true) && strpos($tag, 'async') === false) {
                $tag = str_replace(' src', ' async src', $tag);
        }

        if (in_array($handle, $defer_allowlist, true) && strpos($tag, 'defer') === false && strpos($tag, 'async') === false) {
                $tag = str_replace(' src', ' defer src', $tag);
        }

        // Add low fetchpriority to non-critical scripts
        if (in_array($handle, $low_priority, true)) {
                $tag = str_replace('<script', '<script fetchpriority="low"', $tag);
        }

        return $tag;
}
add_filter('script_loader_tag', 'chroma_optimize_script_tags', 10, 3);



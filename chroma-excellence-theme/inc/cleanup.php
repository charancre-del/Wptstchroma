<?php
/**
 * WordPress Cleanup Functions
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
        exit;
}

/**
 * Disable comments on attachments
 */
function chroma_disable_attachment_comments($open, $post_id)
{
        $post = get_post($post_id);
        if ($post && $post->post_type === 'attachment') {
                return false;
        }
        return $open;
}
add_filter('comments_open', 'chroma_disable_attachment_comments', 10, 2);

/**
 * Redirect attachment pages to parent or home
 */
function chroma_redirect_attachment_pages()
{
        if (is_attachment()) {
                global $post;
                if ($post && $post->post_parent) {
                        wp_safe_redirect(get_permalink($post->post_parent), 301);
                } else {
                        wp_safe_redirect(home_url(), 301);
                }
                exit;
        }
}
add_action('template_redirect', 'chroma_redirect_attachment_pages');

/**
 * Disable author archives
 */
function chroma_disable_author_archives()
{
        if (is_author()) {
                wp_safe_redirect(home_url(), 301);
                exit;
        }
}
add_action('template_redirect', 'chroma_disable_author_archives');

/**
 * Redirect tracked marketing 404 URLs to homepage.
 *
 * Some ad platforms append tracking parameters (or malformed tracking suffixes)
 * to landing URLs. If that results in a 404, recover to homepage while preserving
 * attribution parameters where possible.
 */
function chroma_redirect_tracked_404_to_home()
{
        if (is_admin() || wp_doing_ajax() || !is_404()) {
                return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '';

        // Skip internal/system endpoints.
        $skip_prefixes = array(
                '/wp-admin',
                '/wp-json',
                '/wp-content',
                '/wp-includes',
        );

        foreach ($skip_prefixes as $prefix) {
                if (strpos($path, $prefix) === 0) {
                        return;
                }
        }

        $skip_exact = array('/robots.txt', '/favicon.ico', '/sitemap.xml');
        if (in_array($path, $skip_exact, true)) {
                return;
        }

        $tracking_keys = array(
                'fbclid',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'utm_id',
                'gclid',
                'msclkid',
                'ttclid',
                'twclid',
                'wbraid',
                'gbraid',
                'li_fat_id',
                'mc_cid',
                'mc_eid',
                'yclid',
        );

        $has_tracking_marker = false;
        foreach ($tracking_keys as $key) {
                if (isset($_GET[$key])) {
                        $has_tracking_marker = true;
                        break;
                }
        }

        // Fallback detection for malformed paths that include fbclid-like suffixes.
        if (
                !$has_tracking_marker
                && is_string($request_uri)
                && preg_match('/(?:fbclid|utm_|gclid|msclkid|ttclid|wbraid|gbraid|twclid)=/i', $request_uri)
        ) {
                $has_tracking_marker = true;
        }

        if (!$has_tracking_marker) {
                return;
        }

        $query_forward = array();
        foreach ($tracking_keys as $key) {
                if (!isset($_GET[$key])) {
                        continue;
                }

                $value = wp_unslash($_GET[$key]);
                if (is_array($value)) {
                        continue;
                }

                $query_forward[$key] = sanitize_text_field((string) $value);
        }

        $target = home_url('/');
        if (!empty($query_forward)) {
                $target = add_query_arg($query_forward, $target);
        }

        wp_safe_redirect($target, 302);
        exit;
}
add_action('template_redirect', 'chroma_redirect_tracked_404_to_home', 0);

/**
 * Disable XML-RPC
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Remove WordPress version from head
 */
remove_action('wp_head', 'wp_generator');

/**
 * Disable RSS feeds
 */
function chroma_disable_feeds()
{
        wp_die(__('No feed available. Please visit the <a href="' . esc_url(home_url('/')) . '">homepage</a>!', 'chroma-excellence'));
}
add_action('do_feed', 'chroma_disable_feeds', 1);
add_action('do_feed_rdf', 'chroma_disable_feeds', 1);
add_action('do_feed_rss', 'chroma_disable_feeds', 1);
add_action('do_feed_rss2', 'chroma_disable_feeds', 1);
add_action('do_feed_atom', 'chroma_disable_feeds', 1);

/**
 * Disable emojis to reduce extraneous HTTP requests and inline scripts.
 */
function chroma_disable_emojis()
{
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', 'chroma_disable_emojis_tinymce');
        add_filter('wp_resource_hints', 'chroma_disable_emojis_dns_prefetch', 10, 2);
}
add_action('init', 'chroma_disable_emojis');

/**
 * Filter out the emoji plugin from TinyMCE.
 */
function chroma_disable_emojis_tinymce($plugins)
{
        if (is_array($plugins)) {
                return array_diff($plugins, array('wpemoji'));
        }

        return array();
}

/**
 * Remove emoji CDN DNS prefetch.
 */
function chroma_disable_emojis_dns_prefetch($urls, $relation_type)
{
        if ('dns-prefetch' !== $relation_type) {
                return $urls;
        }

        return array_filter(
                $urls,
                function ($url) {
                        return false === strpos($url, 's.w.org/images/core/emoji/');
                }
        );
}
/**
 * Enforce canonical URLs via PHP for better SEO and performance.
 * Replaces the brittle JS-based canonical hack.
 */
function chroma_enforce_canonical()
{
        // Plugin canonical is primary. Theme canonical is fallback-only.
        if ((class_exists('Chroma_Canonical_Enforcer') && get_option('chroma_seo_enable_canonical', true))
                || did_action('chroma_canonical_output_done')) {
                return;
        }

        if (is_singular()) {
                $canonical_url = get_permalink();
        } elseif (is_front_page()) {
                $canonical_url = home_url('/');
        } elseif (is_category() || is_tag() || is_tax()) {
                $canonical_url = get_term_link(get_queried_object());
        } elseif (is_post_type_archive()) {
                $canonical_url = get_post_type_archive_link(get_query_var('post_type'));
        } else {
                $canonical_url = home_url($_SERVER['REQUEST_URI']);
        }

        if (!is_wp_error($canonical_url) && $canonical_url) {
                $filtered_canonical = apply_filters('wpseo_canonical', $canonical_url);
                if (is_string($filtered_canonical) && $filtered_canonical !== '') {
                        $canonical_url = $filtered_canonical;
                }

                $canonical_url = strtok($canonical_url, '?');
                if (!preg_match('/\.(html?|xml|json|php)$/i', $canonical_url)) {
                        $canonical_url = trailingslashit($canonical_url);
                }
                echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
                do_action('chroma_canonical_output_done', $canonical_url);
        }
}
add_action('wp_head', 'chroma_enforce_canonical', 2);

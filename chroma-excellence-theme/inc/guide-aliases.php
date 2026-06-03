<?php
/**
 * Guide alias redirects.
 *
 * Keeps editorial guide links stable while draft guide posts are still private.
 *
 * @package Chroma_Excellence
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Return guide alias fallbacks for currently published content.
 *
 * Exact published post/page slugs always win before these fallbacks are used.
 *
 * @return array<string,string>
 */
function chroma_guide_alias_fallbacks()
{
    return array(
        'how-to-choose-daycare-georgia' => 'top-questions-to-ask-childcare-providers',
        'daycare-costs-georgia-2026' => 'chroma-early-learning-academy-tuition-fees-guide',
        'ga-pre-k-vs-private-pre-k' => 'georgia-pre-k-eligibility-requirements-application-guide',
    );
}

/**
 * Find a published post or page by slug without exposing drafts.
 *
 * @param string $slug Post/page slug.
 * @return WP_Post|null
 */
function chroma_get_published_post_or_page_by_slug($slug)
{
    global $wpdb;

    $slug = sanitize_title($slug);
    if ('' === $slug) {
        return null;
    }

    $post_id = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_name = %s
               AND post_status = 'publish'
               AND post_type IN ('post', 'page')
             ORDER BY FIELD(post_type, 'page', 'post'), ID DESC
             LIMIT 1",
            $slug
        )
    );

    return $post_id > 0 ? get_post($post_id) : null;
}

/**
 * Redirect /guides/{slug}/ aliases to the best published destination.
 */
function chroma_maybe_redirect_guide_alias()
{
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $path = trim((string) wp_parse_url($request_uri, PHP_URL_PATH), '/');

    if (!preg_match('#^(?:es/)?guides/([a-z0-9-]+)/?$#i', $path, $matches)) {
        return;
    }

    $slug = sanitize_title($matches[1]);
    if ('' === $slug) {
        return;
    }

    $target = chroma_get_published_post_or_page_by_slug($slug);

    if (!$target) {
        $fallbacks = apply_filters('chroma_guide_alias_fallbacks', chroma_guide_alias_fallbacks());
        $fallback_slug = isset($fallbacks[$slug]) ? sanitize_title((string) $fallbacks[$slug]) : '';
        if ('' !== $fallback_slug) {
            $target = chroma_get_published_post_or_page_by_slug($fallback_slug);
        }
    }

    if (!$target) {
        return;
    }

    $target_url = get_permalink($target);
    if (!$target_url) {
        return;
    }

    wp_safe_redirect($target_url, 301);
    exit;
}
add_action('template_redirect', 'chroma_maybe_redirect_guide_alias', -500);

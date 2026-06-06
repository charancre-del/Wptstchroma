<?php
/**
 * Force Trailing Slashes on Canonicals
 *
 * Ensures all canonical URLs have a trailing slash, resolving mismatch errors in SEO audits.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filter Yoast Canonical to Enforce Trailing Slash
 */
function chroma_enforce_trailing_slash_canonical($canonical) {
    // If empty or has file extension (like .html, .xml, .jpg), leave alone
    if (!$canonical || pathinfo($canonical, PATHINFO_EXTENSION)) {
        return $canonical;
    }

    // Ignore if it has query parameters
    if (strpos($canonical, '?') !== false) {
        return $canonical;
    }

    // Add slash if missing
    return trailingslashit($canonical);
}
add_filter('wpseo_canonical', 'chroma_enforce_trailing_slash_canonical', 50);

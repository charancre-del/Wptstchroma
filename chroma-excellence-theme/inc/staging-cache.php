<?php
/**
 * Staging cache guards.
 *
 * The staging domain is used for deployment verification, so dynamic HTML must
 * not be frozen by edge caches between code pushes.
 *
 * @package Chroma_Excellence
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Determine whether the current request is for a known staging host.
 *
 * @return bool
 */
function chroma_is_staging_request()
{
    $host = '';
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = strtolower((string) wp_unslash($_SERVER['HTTP_HOST']));
    }

    $host = preg_replace('/:\d+$/', '', $host);

    if ($host === 'x3yyntt5tp-staging.wpdns.site') {
        return true;
    }

    $site_url = strtolower((string) get_option('siteurl'));
    return strpos($site_url, 'x3yyntt5tp-staging.wpdns.site') !== false;
}

/**
 * Override page-cache headers on staging after other plugins have registered theirs.
 */
function chroma_disable_staging_html_cache()
{
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || headers_sent() || !chroma_is_staging_request()) {
        return;
    }

    if (!defined('DONOTCACHEPAGE')) {
        define('DONOTCACHEPAGE', true);
    }

    header_remove('Cache-Control');
    header_remove('Expires');
    header_remove('Pragma');
    header_remove('Surrogate-Control');
    header_remove('CDN-Cache-Control');
    header_remove('Cloudflare-CDN-Cache-Control');

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, s-maxage=0');
    header('Pragma: no-cache');
    header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
    header('Surrogate-Control: no-store');
    header('CDN-Cache-Control: no-store');
    header('Cloudflare-CDN-Cache-Control: no-store');
}
add_action('send_headers', 'chroma_disable_staging_html_cache', PHP_INT_MAX);

/**
 * Filter WordPress headers too, so plugins using wp_headers cannot reintroduce edge TTLs.
 *
 * @param array $headers Response headers.
 * @return array
 */
function chroma_filter_staging_html_cache_headers($headers)
{
    if (is_admin() || !chroma_is_staging_request()) {
        return $headers;
    }

    $headers['Cache-Control'] = 'no-store, no-cache, must-revalidate, max-age=0, s-maxage=0';
    $headers['Pragma'] = 'no-cache';
    $headers['Expires'] = 'Wed, 11 Jan 1984 05:00:00 GMT';
    $headers['Surrogate-Control'] = 'no-store';
    $headers['CDN-Cache-Control'] = 'no-store';
    $headers['Cloudflare-CDN-Cache-Control'] = 'no-store';

    return $headers;
}
add_filter('wp_headers', 'chroma_filter_staging_html_cache_headers', PHP_INT_MAX);

/**
 * Keep the verification host out of search indexes even when an SEO plugin
 * replaces WordPress core robots output.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function chroma_noindex_staging_robots($robots)
{
    if (!chroma_is_staging_request()) {
        return $robots;
    }

    $robots['noindex'] = true;
    $robots['nofollow'] = true;
    $robots['noarchive'] = true;

    unset($robots['index'], $robots['follow']);

    return $robots;
}
add_filter('wp_robots', 'chroma_noindex_staging_robots', PHP_INT_MAX);

/**
 * Mirror the staging directive for Yoast-compatible robots filters.
 *
 * @param string $robots Existing directive.
 * @return string
 */
function chroma_noindex_staging_wpseo_robots($robots)
{
    return chroma_is_staging_request() ? 'noindex,nofollow,noarchive' : $robots;
}
add_filter('wpseo_robots', 'chroma_noindex_staging_wpseo_robots', PHP_INT_MAX);

/**
 * Send an HTTP-level staging directive as the definitive safety net.
 */
function chroma_send_staging_robots_header()
{
    if (is_admin() || headers_sent() || !chroma_is_staging_request()) {
        return;
    }

    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
}
add_action('send_headers', 'chroma_send_staging_robots_header', PHP_INT_MAX);

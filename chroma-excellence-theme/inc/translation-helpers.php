<?php
/**
 * Translation Helpers
 * Functions to assist with retrieving translated content.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('chroma_get_translated_meta')) {
    /**
     * Retrieve translated post meta if available and language is Spanish.
     * otherwise return default meta.
     *
     * @param int    $post_id Post ID.
     * @param string $key     Meta key.
     * @param bool   $single  Whether to return a single value.
     * @return mixed
     */
    function chroma_get_translated_meta($post_id, $key, $single = true)
    {
        static $meta_cache = array();
        $cache_key = "{$post_id}_{$key}_" . ($single ? '1' : '0');

        if (isset($meta_cache[$cache_key])) {
            return $meta_cache[$cache_key];
        }

        // Check if we are in Spanish mode
        $is_spanish = false;

        // Check URL
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/es/') !== false) {
            $is_spanish = true;
        }

        // Check Multilingual Manager if exists
        if (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish')) {
            if (Chroma_Multilingual_Manager::is_spanish()) {
                $is_spanish = true;
            }
        }

        if ($is_spanish) {
            $es_key = '_chroma_es_' . $key;
            $val = get_post_meta($post_id, $es_key, $single);
            if (!empty($val)) {
                $meta_cache[$cache_key] = $val;
                return $val;
            }
        }

        // Fallback to original
        $val = get_post_meta($post_id, $key, $single);
        $meta_cache[$cache_key] = $val;
        return $val;
    }
}

if (!function_exists('chroma_get_localized_url')) {
    /**
     * Determine whether an internal URL has a verified Spanish destination.
     *
     * WordPress-backed routes must have translated content before links are
     * rewritten to /es/. Dynamic routes are left eligible because they are
     * generated independently and may not resolve through url_to_postid().
     *
     * @param string $url Internal absolute or relative URL.
     * @return bool
     */
    function chroma_url_has_verified_spanish_variant($url)
    {
        if (!is_string($url) || trim($url) === '') {
            return false;
        }

        $absolute_url = strpos($url, '/') === 0 ? home_url($url) : $url;
        $post_id = url_to_postid($absolute_url);
        if ($post_id <= 0) {
            return true;
        }

        return !function_exists('chroma_post_has_verified_spanish_variant')
            || chroma_post_has_verified_spanish_variant($post_id);
    }

    /**
     * Prepend /es/ to internal URLs if the current language is Spanish.
     *
     * @param string $url The internal URL.
     * @return string The localized URL.
     */
    function chroma_get_localized_url($url)
    {
        if (empty($url)) {
            return $url;
        }

        // 1. Handle anchor-only URLs immediately (e.g. #tour)
        if (strpos($url, '#') === 0) {
            return $url;
        }

        // Only localize relative or internal absolute URLs
        $home_url = home_url();
        $is_internal = (strpos($url, $home_url) === 0 || strpos($url, '/') === 0) && strpos($url, '://') === false;

        // Handle URLs that already have the protocol but are internal (e.g. http://site.com/about)
        if (!$is_internal && strpos($url, $home_url) === 0) {
            $is_internal = true;
        }

        if (!$is_internal) {
            return $url;
        }

        $original_path = (string) wp_parse_url($url, PHP_URL_PATH);
        $is_system_path = (bool) preg_match('#^/(?:wp-admin|wp-content|wp-includes|wp-json)(?:/|$)#i', $original_path);
        $is_file = (bool) preg_match('/\.[a-z0-9]{2,8}$/i', $original_path);
        if ($is_system_path || $is_file) {
            return $url;
        }

        // Split anchor/query if present to protect from trailing slashes
        $parts = explode('#', $url);
        $path_query = $parts[0];
        $anchor = isset($parts[1]) ? '#' . $parts[1] : '';

        // Detect current language
        $is_spanish = false;
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/es/') !== false) {
            $is_spanish = true;
        }
        if (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish')) {
            if (Chroma_Multilingual_Manager::is_spanish()) {
                $is_spanish = true;
            }
        }

        // Process path (add /es/ if Spanish)
        $processed_url = $path_query;
        if (
            $is_spanish
            && strpos($processed_url, '/es/') === false
            && chroma_url_has_verified_spanish_variant($path_query)
        ) {
            if (strpos($processed_url, $home_url) === 0) {
                $processed_url = str_replace($home_url, $home_url . '/es', $processed_url);
            } else {
                $processed_url = '/es' . (strpos($processed_url, '/') === 0 ? '' : '/') . $processed_url;
            }
        }

        // Apply trailing slash to the path component (don't apply to anchors)
        // Only apply if it's not a direct file link (e.g. .png, .pdf)
        $path_only = explode('?', $processed_url)[0];
        if (!preg_match('/\.(jpg|jpeg|png|gif|pdf|doc|docx|zip|webp)$/i', $path_only)) {
            $processed_url = user_trailingslashit($processed_url);
        }

        return $processed_url . $anchor;
    }
}

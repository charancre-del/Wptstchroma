<?php
/**
 * Validation Caching System
 * Handles caching of schema validation results to improve performance
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Validation_Cache
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    const TTL = 3600;

    /**
     * Get a cached result
     * 
     * @param string $url The URL to retrieve cache for
     * @return array|false The cached result or false if not found
     */
    public static function get($url)
    {
        $key = self::get_key($url);
        return get_transient($key);
    }

    /**
     * Set a cached result
     * 
     * @param string $url The URL to cache
     * @param array $result The validation result to store
     * @return bool True on success
     */
    public static function set($url, $result)
    {
        $key = self::get_key($url);
        return set_transient($key, $result, self::TTL);
    }

    /**
     * Invalidate a specific URL
     * 
     * @param string $url The URL to invalidate
     * @return bool True on success
     */
    public static function invalidate($url)
    {
        $key = self::get_key($url);
        return delete_transient($key);
    }

    /**
     * Clear all validation caches
     * Effectively done by incrementing the version suffix
     */
    public static function clear_all()
    {
        $version = (int) get_option('chroma_validation_cache_ver', 1);
        update_option('chroma_validation_cache_ver', $version + 1);
        return true;
    }

    /**
     * Generate a cache key for a URL
     * Uses a version suffix to allow global clearing
     * 
     * @param string $url
     * @return string
     */
    private static function get_key($url)
    {
        $version = (int) get_option('chroma_validation_cache_ver', 1);
        return 'chroma_val_v' . $version . '_' . md5($url);
    }
}



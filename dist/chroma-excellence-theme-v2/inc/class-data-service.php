<?php
/**
 * Chroma Data Service (Service Layer)
 * 
 * Centralizes data fetching into a single singleton to eliminate N+1 queries.
 * Pre-warms data into memory during theme initialization.
 *
 * @package ChromaExcellence
 */

if (!defined('ABSPATH'))
    exit;

class Chroma_Data_Service
{

    private static $instance = null;
    private $cached_locations = null;
    private $cached_programs = null;
    private $cached_regions = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct()
    {
        // NO GLOBAL HOOKS - Service is now lazy-loaded on demand
    }

    /**
     * Get all Locations (Lazy Loaded & Cached)
     */
    public function get_locations()
    {
        if ($this->cached_locations !== null) {
            return $this->cached_locations;
        }

        $token = chroma_get_last_changed('locations');
        $cache_key = 'locations_index:' . $token;
        $cached = wp_cache_get($cache_key, 'chroma');

        if (false !== $cached) {
            $this->cached_locations = $cached;
            return $cached;
        }

        // Stampede protection
        if (!chroma_acquire_lock($cache_key)) {
            // If locked, fallback to stale transient if exists, or just do the query if critical
            $stale = get_transient('chroma_stale_locations');
            return $stale ?: $this->fetch_and_cache_locations($cache_key);
        }

        $data = $this->fetch_and_cache_locations($cache_key);
        chroma_release_lock($cache_key);

        return $data;
    }

    private function fetch_and_cache_locations($cache_key)
    {
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
        ]);

        wp_cache_set($cache_key, $locations, 'chroma', HOUR_IN_SECONDS);
        set_transient('chroma_stale_locations', $locations, DAY_IN_SECONDS);

        $this->cached_locations = $locations;
        return $locations;
    }

    /**
     * Get all Programs (Lazy Loaded & Cached)
     */
    public function get_programs()
    {
        if ($this->cached_programs !== null) {
            return $this->cached_programs;
        }

        $token = chroma_get_last_changed('programs');
        $cache_key = 'programs_index:' . $token;
        $cached = wp_cache_get($cache_key, 'chroma');

        if (false !== $cached) {
            $this->cached_programs = $cached;
            return $cached;
        }

        if (!chroma_acquire_lock($cache_key)) {
            $stale = get_transient('chroma_stale_programs');
            return $stale ?: $this->fetch_and_cache_programs($cache_key);
        }

        $data = $this->fetch_and_cache_programs($cache_key);
        chroma_release_lock($cache_key);

        return $data;
    }

    private function fetch_and_cache_programs($cache_key)
    {
        $programs = get_posts([
            'post_type' => 'program',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
        ]);

        wp_cache_set($cache_key, $programs, 'chroma', HOUR_IN_SECONDS);
        set_transient('chroma_stale_programs', $programs, DAY_IN_SECONDS);

        $this->cached_programs = $programs;
        return $programs;
    }

    /**
     * Get specific meta from a post, using cache-primed data if available
     */
    public function get_meta($post_id, $key = '', $default = '')
    {
        // Core get_post_meta is already reasonably optimized if update_post_meta_cache was used
        // But for consistency with the audit's "No pre-warm" goal, we rely on core caching
        return get_post_meta($post_id, $key, true) ?: $default;
    }

    /**
     * Get all regions (Lazy Loaded & Cached)
     */
    public function get_regions()
    {
        if ($this->cached_regions !== null) {
            return $this->cached_regions;
        }

        $cache_key = 'regions_index:' . chroma_get_last_changed('regions');
        $cached = wp_cache_get($cache_key, 'chroma');

        if (false !== $cached) {
            $this->cached_regions = $cached;
            return $cached;
        }

        $regions = get_terms([
            'taxonomy' => 'location_region',
            'hide_empty' => true,
        ]);

        wp_cache_set($cache_key, $regions, 'chroma', HOUR_IN_SECONDS);
        $this->cached_regions = $regions;

        return $regions;
    }
}

/**
 * CACHE INVALIDATION HOOKS
 */
add_action('save_post_location', function () {
    chroma_invalidate_cache_group('locations');
});
add_action('save_post_program', function () {
    chroma_invalidate_cache_group('programs');
});
add_action('edited_location_region', function () {
    chroma_invalidate_cache_group('regions');
});
add_action('created_location_region', function () {
    chroma_invalidate_cache_group('regions');
});
add_action('delete_location_region', function () {
    chroma_invalidate_cache_group('regions');
});
add_action('save_post_chroma_school', function () {
    chroma_invalidate_cache_group('schools');
});

// Initialise the service
Chroma_Data_Service::get_instance();

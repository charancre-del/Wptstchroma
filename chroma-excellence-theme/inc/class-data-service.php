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

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Data storage
     */
    private $locations = [];
    private $programs = [];
    private $regions = [];
    private $meta_cache = [];
    private $term_meta_cache = [];

    /**
     * Get singleton instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - protected to enforce singleton
     */
    protected function __construct()
    {
        // We'll hook into 'init' or 'wp' depending on when we need the data
        add_action('wp', [$this, 'pre_warm'], 5);
    }

    /**
     * Pre-warm the data cache with a single optimized query
     */
    public function pre_warm()
    {
        // Skip for admin/ajax to keep it light where not needed
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }

        global $wpdb;

        // 1. Fetch Locations & Programs IDs
        $location_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'location' AND post_status = 'publish'");
        $program_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'program' AND post_status = 'publish'");
        $all_ids = array_merge($location_ids, $program_ids);

        if (empty($all_ids))
            return;

        // 2. Optimized Meta Fetching: ALL meta for all IDs
        $meta_rows = $wpdb->get_results("
            SELECT post_id, meta_key, meta_value 
            FROM {$wpdb->postmeta} 
            WHERE post_id IN (" . implode(',', array_map('intval', $all_ids)) . ")
        ");

        // Organise meta into our cache
        foreach ($meta_rows as $row) {
            $this->meta_cache[$row->post_id][$row->meta_key] = maybe_unserialize($row->meta_value);
        }

        // 3. Store the objects statically for order preservation
        $this->locations = get_posts(['post_type' => 'location', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']);
        $this->programs = get_posts(['post_type' => 'program', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'menu_order', 'order' => 'ASC']);

        // 3. Fetch Regions (Taxonomy)
        $regions = get_terms([
            'taxonomy' => 'location_region',
            'hide_empty' => true,
        ]);

        if (!empty($regions) && !is_wp_error($regions)) {
            $this->regions = $regions;
            $term_ids = wp_list_pluck($regions, 'term_id');

            // 4. Fetch Term Meta in one hit
            $term_meta_rows = $wpdb->get_results("
                SELECT term_id, meta_key, meta_value 
                FROM {$wpdb->termmeta} 
                WHERE term_id IN (" . implode(',', array_map('intval', $term_ids)) . ")
            ");

            foreach ($term_meta_rows as $row) {
                $this->term_meta_cache[$row->term_id][$row->meta_key] = maybe_unserialize($row->meta_value);
            }
        }

        // Store locations
        $this->locations = $location_posts;

        error_log('Chroma Data Service: Pre-warmed ' . count($this->locations) . ' locations and ' . count($this->regions) . ' regions.');
    }

    /**
     * Get all regions
     */
    public function get_regions()
    {
        return $this->regions;
    }

    /**
     * Get term meta from memory
     */
    public function get_term_meta($term_id, $key = '', $default = '')
    {
        if (!isset($this->term_meta_cache[$term_id])) {
            return get_term_meta($term_id, $key, true) ?: $default;
        }

        if (empty($key)) {
            return $this->term_meta_cache[$term_id];
        }

        return isset($this->term_meta_cache[$term_id][$key]) ? $this->term_meta_cache[$term_id][$key] : $default;
    }

    /**
     * Get all programs
     */
    public function get_programs()
    {
        return $this->programs;
    }

    /**
     * Get all locations from memory
     */
    public function get_locations()
    {
        return $this->locations;
    }

    /**
     * Get specific location meta from memory
     */
    public function get_meta($post_id, $key = '', $default = '')
    {
        if (!isset($this->meta_cache[$post_id])) {
            // Fallback to native if not cached (safety)
            return get_post_meta($post_id, $key, true) ?: $default;
        }

        if (empty($key)) {
            return $this->meta_cache[$post_id];
        }

        return isset($this->meta_cache[$post_id][$key]) ? $this->meta_cache[$post_id][$key] : $default;
    }

    /**
     * Helper to get translated meta (Proxy for existing logic)
     */
    public function get_translated_meta($post_id, $key, $default = '')
    {
        $val = $this->get_meta($post_id, $key, $default);

        // If we have translation helpers loaded, we can apply them here
        if (function_exists('chroma_translate_value')) {
            return chroma_translate_value($val, $key);
        }

        return $val;
    }
}

// Initialise the service
Chroma_Data_Service::get_instance();

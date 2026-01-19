<?php
/**
 * Combo Page Data Storage
 * Stores editable data for program+city combo pages
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Combo_Page_Data
{
    /**
     * Option prefix for combo page data
     */
    const OPTION_PREFIX = 'chroma_combo_';
    
    /**
     * Get the option key for a combo page
     */
    public static function get_option_key($program_slug, $city_slug, $state) {
        return self::OPTION_PREFIX . sanitize_title($program_slug) . '_' . sanitize_title($city_slug) . '_' . strtolower($state);
    }
    
    /**
     * Get combo page data
     */
    public static function get($program_slug, $city_slug, $state) {
        $key = self::get_option_key($program_slug, $city_slug, $state);
        $data = get_option($key, []);
        
        return wp_parse_args($data, self::get_defaults());
    }
    
    /**
     * Save combo page data
     */
    public static function save($program_slug, $city_slug, $state, $data) {
        $key = self::get_option_key($program_slug, $city_slug, $state);
        $existing = self::get($program_slug, $city_slug, $state);
        
        $merged = wp_parse_args($data, $existing);
        $merged['last_updated'] = current_time('timestamp');
        
        return update_option($key, $merged);
    }
    
    /**
     * Get default data structure
     */
    public static function get_defaults() {
        return [
            'neighborhoods' => [],
            'major_road' => '',
            'local_employers' => '',
            'county' => '',
            'custom_intro' => '',
            'custom_benefits' => [],
            'faqs' => [],
            
            // Spanish Translation Fields
            'neighborhoods_es' => [],
            'major_road_es' => '',
            'local_employers_es' => '',
            'custom_intro_es' => '',
            
            'status' => 'auto', // auto, draft, published
            'last_ai_update' => null,
            'last_updated' => null,
            'ai_generated' => false
        ];
    }
    
    /**
     * Check if combo page has custom data
     */
    public static function has_data($program_slug, $city_slug, $state) {
        $data = self::get($program_slug, $city_slug, $state);
        return !empty($data['neighborhoods']) || !empty($data['custom_intro']) || $data['status'] !== 'auto';
    }
    
    /**
     * Get status label
     */
    public static function get_status_label($status) {
        $labels = [
            'auto' => 'Auto',
            'draft' => 'Draft',
            'published' => 'Published',
            'publish' => 'Published', // Alias for robustness
            'ai_pending' => 'AI Pending'
        ];
        return $labels[$status] ?? 'Unknown';
    }
    
    /**
     * Get all stored combo page keys
     */
    public static function get_all_stored_keys() {
        global $wpdb;
        
        $results = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                self::OPTION_PREFIX . '%'
            )
        );
        
        return $results;
    }
    
    /**
     * Delete combo page data
     */
    public static function delete($program_slug, $city_slug, $state) {
        $key = self::get_option_key($program_slug, $city_slug, $state);
        return delete_option($key);
    }
    
    /**
     * Bulk update status
     */
    public static function bulk_update_status($combos, $new_status) {
        $updated = 0;
        foreach ($combos as $combo) {
            if (self::save($combo['program_slug'], $combo['city_slug'], $combo['state'], ['status' => $new_status])) {
                $updated++;
            }
        }
        return $updated;
    }
}



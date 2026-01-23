<?php
/**
 * Fallback Resolver
 * Computes smart default values when advanced SEO/LLM fields are not manually filled
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Fallback_Resolver
{
    /**
     * Get a cached AI-generated value for a field
     *
     * @param int $post_id
     * @param string $field_key
     * @return string|null
     */
    public static function get_cached_ai_value($post_id, $field_key)
    {
        $cache = get_post_meta($post_id, '_chroma_ai_fallback_cache', true);
        if (is_array($cache) && isset($cache[$field_key])) {
            return $cache[$field_key];
        }
        return null;
    }

    /**
     * Save an AI-generated value to the cache
     *
     * @param int $post_id
     * @param string $field_key
     * @param mixed $value
     */
    public static function set_ai_field_cache($post_id, $field_key, $value)
    {
        $cache = get_post_meta($post_id, '_chroma_ai_fallback_cache', true);
        if (!is_array($cache)) {
            $cache = [];
        }
        $cache[$field_key] = $value;
        update_post_meta($post_id, '_chroma_ai_fallback_cache', $cache);
    }

    /**
     * Get service area circle data
     *
     * @param int $location_id Location post ID
     * @return array|null Array with lat, lng, radius or null if unavailable
     */
    public static function get_service_area_circle($location_id)
    {
        // Check if manually set
        $lat = get_post_meta($location_id, 'seo_llm_service_area_lat', true);
        $lng = get_post_meta($location_id, 'seo_llm_service_area_lng', true);
        $radius = get_post_meta($location_id, 'seo_llm_service_area_radius', true);

        if ($lat && $lng) {
            return [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
                'radius' => $radius ? (float) $radius : 10,
            ];
        }

        // Fallback to existing location coordinates
        $lat = get_post_meta($location_id, 'location_latitude', true);
        $lng = get_post_meta($location_id, 'location_longitude', true);

        if ($lat && $lng) {
            return [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
                'radius' => 10, // Default 10-mile radius
            ];
        }

        return null;
    }

    /**
     * Get service area cities
     *
     * @param int $location_id Location post ID
     * @return array Array of city names
     */
    public static function get_service_area_cities($location_id)
    {
        // Check if manually set
        $cities = get_post_meta($location_id, 'seo_llm_service_area_cities', true);
        if (!empty($cities) && is_array($cities)) {
            return array_filter($cities);
        }

        // Fallback to location's city
        $city = get_post_meta($location_id, 'location_city', true);
        if ($city) {
            return [$city];
        }

        // Check service areas meta (comma-separated)
        $service_areas = get_post_meta($location_id, 'location_service_areas', true);
        if ($service_areas) {
            $areas = array_map('trim', explode(',', $service_areas));
            return array_filter($areas);
        }

        return [];
    }

    /**
     * Get LLM description for a location
     *
     * @param int $location_id Location post ID
     * @return string Description text
     */
    public static function get_llm_description($location_id)
    {
        // Check if manually set
        $desc = get_post_meta($location_id, 'seo_llm_description', true);
        if ($desc) {
            return $desc;
        }

        // Check AI Cache
        $ai_desc = self::get_cached_ai_value($location_id, 'description');
        if ($ai_desc) {
            return $ai_desc;
        }

        // Build from existing data
        $name = get_post_field('post_title', $location_id);
        $city = get_post_meta($location_id, 'location_city', true);
        $quality = get_post_meta($location_id, 'location_quality_rated', true);
        $ages = get_post_meta($location_id, 'location_ages_served', true);

        $parts = [$name, 'is'];

        if ($quality) {
            $stars = is_numeric($quality) ? $quality . '-Star' : '';
            $parts[] = $stars . ' Quality Rated';
        }

        $parts[] = 'childcare center';

        if ($city) {
            $parts[] = 'in ' . $city . ', Georgia';
        }

        if ($ages) {
            $parts[] = 'serving children ' . $ages;
        }

        return implode(' ', array_filter($parts)) . '.';
    }

    /**
     * Get LLM target queries for a location
     *
     * @param int $location_id Location post ID
     * @return array Array of query strings
     */
    public static function get_llm_target_queries($location_id)
    {
        // Check if manually set
        $queries = get_post_meta($location_id, 'seo_llm_target_queries', true);
        if (!empty($queries) && is_array($queries)) {
            return array_filter($queries);
        }

        // Check AI Cache
        $ai_queries = self::get_cached_ai_value($location_id, 'target_queries');
        if (!empty($ai_queries) && is_array($ai_queries)) {
            return array_filter($ai_queries);
        }

        // Auto-generate from location data
        $city = get_post_meta($location_id, 'location_city', true);
        $name = get_post_field('post_title', $location_id);

        $queries = [];

        if ($city) {
            $queries[] = "best daycare near " . $city . " GA";
            $queries[] = "childcare in " . $city . " Georgia";
            $queries[] = "preschool " . $city;
        }

        $queries[] = $name . " reviews";

        return array_filter($queries);
    }

    /**
     * Get LLM key differentiators for a location
     *
     * @param int $location_id Location post ID
     * @return array Array of differentiator strings
     */
    public static function get_llm_key_differentiators($location_id)
    {
        // Check if manually set
        $differentiators = get_post_meta($location_id, 'seo_llm_key_differentiators', true);
        if (!empty($differentiators) && is_array($differentiators)) {
            return array_filter($differentiators);
        }

        // Check AI Cache
        $ai_diff = self::get_cached_ai_value($location_id, 'key_differentiators');
        if (!empty($ai_diff) && is_array($ai_diff)) {
            return array_filter($ai_diff);
        }

        // Auto-generate from location data
        $diff = [];

        $quality = get_post_meta($location_id, 'location_quality_rated', true);
        if ($quality) {
            $diff[] = "Quality Rated by Georgia's Bright from the Start";
        }

        $hours = get_post_meta($location_id, 'location_hours', true);
        if ($hours && strpos(strtolower($hours), '6:30') !== false) {
            $diff[] = "Extended hours for working families";
        }

        // Get programs from Program CPT relationship + manual text
        $programs = self::get_location_programs($location_id);
        if (!empty($programs) && count($programs) > 1) {
            $diff[] = "Multiple age-appropriate programs";
        }

        $capacity = get_post_meta($location_id, 'location_capacity', true);
        if ($capacity && $capacity > 100) {
            $diff[] = "Large, well-established facility";
        }

        return array_filter($diff);
    }

    /**
     * Get programs offered at a location
     * Combines Program CPT relationship + manual text field
     *
     * @param int $location_id Location post ID
     * @return array Array of program titles
     */
    public static function get_location_programs($location_id)
    {
        $all_programs = [];

        // 1. Get programs from Program CPT relationship
        $cpt_programs = get_posts([
            'post_type' => 'program',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'program_locations',
                    'value' => '"' . $location_id . '"',
                    'compare' => 'LIKE',
                ],
            ],
        ]);

        // Add CPT program titles
        foreach ($cpt_programs as $program_id) {
            $all_programs[] = get_post_field('post_title', $program_id);
        }

        // 2. ALSO merge manual programs from text field
        $manual_text = get_post_meta($location_id, 'location_special_programs', true);
        if ($manual_text) {
            $manual_programs = array_map('trim', explode(',', $manual_text));
            $all_programs = array_merge($all_programs, $manual_programs);
        }

        // Return unique list
        return array_values(array_unique(array_filter($all_programs)));
    }

    /**
     * Get comparison factors for a location
     *
     * @param int $location_id Location post ID
     * @return array Comparison factors array
     */
    public static function get_comparison_factors($location_id)
    {
        $factors = [];

        // Age range served
        $ages = get_post_meta($location_id, 'location_ages_served', true);
        if ($ages) {
            $factors['ageRangeServed'] = $ages;
        }

        // Hours of operation
        $hours = get_post_meta($location_id, 'location_hours', true);
        if ($hours) {
            // Try to extract a simple format
            if (preg_match('/(\d+:\d+\s*(?:AM|PM).*?-.*?\d+:\d+\s*(?:AM|PM))/i', $hours, $matches)) {
                $factors['hoursOfOperation'] = $matches[1];
            } else {
                $factors['hoursOfOperation'] = $hours;
            }
        }

        // Unique features from Program CPT + manual text
        $programs = self::get_location_programs($location_id);
        if (!empty($programs)) {
            $factors['uniqueFeatures'] = $programs;
        }

        // Best for (derived from data)
        $best_for = [];

        if ($hours && (strpos($hours, '6:30') !== false || strpos($hours, '6:00') !== false)) {
            $best_for[] = "Working parents needing extended hours";
        }

        $quality = get_post_meta($location_id, 'location_quality_rated', true);
        if ($quality) {
            $best_for[] = "Parents seeking quality-rated care";
        }

        if (!empty($best_for)) {
            $factors['bestFor'] = $best_for;
        }

        return $factors;
    }

    /**
     * Get citation facts for a location (auto-generated)
     *
     * @param int $location_id Location post ID
     * @return array Array of fact objects
     */
    public static function get_citation_facts($location_id)
    {
        $facts = [];

        // Quality rating fact
        $quality = get_post_meta($location_id, 'location_quality_rated', true);
        if ($quality) {
            $facts[] = [
                'label' => 'State Quality Rating',
                'value' => 'Quality Rated',
                'source' => "Georgia DECAL Bright from the Start",
                'verifiedDate' => date('Y-m-d'),
            ];
        }

        // Capacity fact
        $capacity = get_post_meta($location_id, 'location_capacity', true);
        if ($capacity) {
            $facts[] = [
                'label' => 'Licensed Capacity',
                'value' => $capacity . ' children',
                'context' => 'Maximum enrollment capacity',
            ];
        }

        // Age range fact
        $ages = get_post_meta($location_id, 'location_ages_served', true);
        if ($ages) {
            $facts[] = [
                'label' => 'Age Range Served',
                'value' => $ages,
            ];
        }

        return $facts;
    }
}



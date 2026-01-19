<?php
/**
 * Service Area Schema Builder
 * Builds service area schema from meta or fallbacks
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Service_Area_Builder
{
    /**
     * Build service area schema for a location
     *
     * @param int $location_id Location post ID
     * @return array|null Service area schema or null
     */
    public static function build($location_id)
    {
        $result = [];

        // Get circle data
        $circle = Chroma_Fallback_Resolver::get_service_area_circle($location_id);
        if ($circle) {
            $result['areaServed'] = [
                '@type' => 'GeoShape',
                'circle' => sprintf('%s,%s %smi', $circle['lat'], $circle['lng'], $circle['radius']),
            ];
        }

        // Get cities
        $cities = Chroma_Fallback_Resolver::get_service_area_cities($location_id);
        $state = get_post_meta($location_id, 'seo_llm_service_area_state', true) ?: 'Georgia';

        if (!empty($cities)) {
            $service_area = [];
            foreach ($cities as $city) {
                $service_area[] = [
                    '@type' => 'City',
                    'name' => $city,
                    'containedInPlace' => [
                        '@type' => 'State',
                        'name' => $state,
                    ],
                ];
            }
            $result['serviceArea'] = $service_area;
        }

        return !empty($result) ? $result : null;
    }
}



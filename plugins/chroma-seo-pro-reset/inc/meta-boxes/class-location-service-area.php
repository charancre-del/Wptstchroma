<?php
/**
 * Location Service Area Meta Box
 * Allows editing service area geo targeting for locations
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Location_Service_Area_Meta_Box extends Chroma_Advanced_SEO_Meta_Box_Base
{
    public function get_id()
    {
        return 'chroma_location_service_area';
    }

    public function get_title()
    {
        return __('Service Area & Geo Targeting', 'chroma-excellence');
    }

    public function get_post_types()
    {
        return ['location'];
    }

    public function render_fields($post)
    {
        // Get current values
        $lat = get_post_meta($post->ID, 'seo_llm_service_area_lat', true);
        $lng = get_post_meta($post->ID, 'seo_llm_service_area_lng', true);
        $radius = get_post_meta($post->ID, 'seo_llm_service_area_radius', true);
        $cities = get_post_meta($post->ID, 'seo_llm_service_area_cities', true) ?: [];
        $state = get_post_meta($post->ID, 'seo_llm_service_area_state', true);

        // Get fallback data
        $fallback_circle = Chroma_Fallback_Resolver::get_service_area_circle($post->ID);
        $fallback_cities = Chroma_Fallback_Resolver::get_service_area_cities($post->ID);

        echo '<div style="margin-bottom: 20px;">';
        echo '<p class="description"><strong>Define the geographic area served by this location for better "near me" SEO.</strong></p>';
        echo '</div>';

        // Circle center coordinates
        echo '<h4>' . __('Service Circle', 'chroma-excellence') . '</h4>';

        $fallback_notice = '';
        if ($fallback_circle && empty($lat)) {
            $fallback_notice = sprintf(
                'Using %s, %s from location coordinates',
                $fallback_circle['lat'],
                $fallback_circle['lng']
            );
        }

        $this->render_number_field([
            'id' => 'seo_llm_service_area_lat',
            'label' => __('Circle Center Latitude', 'chroma-excellence'),
            'value' => $lat,
            'step' => '0.000001',
            'placeholder' => $fallback_circle ? $fallback_circle['lat'] : '',
            'description' => 'Latitude for the center of your service area circle',
            'fallback_notice' => $fallback_notice,
        ]);

        $this->render_number_field([
            'id' => 'seo_llm_service_area_lng',
            'label' => __('Circle Center Longitude', 'chroma-excellence'),
            'value' => $lng,
            'step' => '0.000001',
            'placeholder' => $fallback_circle ? $fallback_circle['lng'] : '',
            'description' => 'Longitude for the center of your service area circle',
        ]);

        $this->render_number_field([
            'id' => 'seo_llm_service_area_radius',
            'label' => __('Radius (miles)', 'chroma-excellence'),
            'value' => $radius,
            'step' => '0.5',
            'min' => '0',
            'placeholder' => '10',
            'description' => 'Service area radius in miles',
            'fallback_notice' => empty($radius) ? '10 miles' : '',
        ]);

        // Cities served
        echo '<h4 style="margin-top: 20px;">' . __('Cities Served', 'chroma-excellence') . '</h4>';

        $cities_fallback_notice = '';
        if (!empty($fallback_cities) && empty($cities)) {
            $cities_fallback_notice = 'Using: ' . implode(', ', $fallback_cities);
        }

        if ($cities_fallback_notice) {
            echo '<p class="description fallback-notice"><em>Fallback: ' . esc_html($cities_fallback_notice) . '</em></p>';
        }

        $this->render_repeater_field([
            'id' => 'seo_llm_service_area_cities',
            'label' => __('City Names', 'chroma-excellence'),
            'values' => $cities,
            'description' => 'Add cities within your service area (e.g., Canton, Holly Springs, Woodstock)',
            'placeholder' => 'City name',
            'button_text' => 'Add City',
        ]);

        // State
        $this->render_text_field([
            'id' => 'seo_llm_service_area_state',
            'label' => __('State', 'chroma-excellence'),
            'value' => $state,
            'placeholder' => 'Georgia',
            'description' => 'State name for service area',
            'fallback_notice' => empty($state) ? 'Georgia' : '',
            'class' => 'regular-text',
        ]);
    }

    public function save_fields($post_id)
    {
        // Save latitude
        if (isset($_POST['seo_llm_service_area_lat'])) {
            $lat = Chroma_Field_Sanitizer::sanitize_latitude($_POST['seo_llm_service_area_lat']);
            update_post_meta($post_id, 'seo_llm_service_area_lat', $lat);
        }

        // Save longitude
        if (isset($_POST['seo_llm_service_area_lng'])) {
            $lng = Chroma_Field_Sanitizer::sanitize_longitude($_POST['seo_llm_service_area_lng']);
            update_post_meta($post_id, 'seo_llm_service_area_lng', $lng);
        }

        // Save radius
        if (isset($_POST['seo_llm_service_area_radius'])) {
            $radius = Chroma_Field_Sanitizer::sanitize_number($_POST['seo_llm_service_area_radius']);
            update_post_meta($post_id, 'seo_llm_service_area_radius', $radius);
        }

        // Save cities
        if (isset($_POST['seo_llm_service_area_cities'])) {
            $cities = Chroma_Field_Sanitizer::sanitize_text_array($_POST['seo_llm_service_area_cities']);
            update_post_meta($post_id, 'seo_llm_service_area_cities', array_filter($cities));
        } else {
            update_post_meta($post_id, 'seo_llm_service_area_cities', []);
        }

        // Save state
        if (isset($_POST['seo_llm_service_area_state'])) {
            $state = Chroma_Field_Sanitizer::sanitize_text($_POST['seo_llm_service_area_state']);
            update_post_meta($post_id, 'seo_llm_service_area_state', $state);
        }
    }
}



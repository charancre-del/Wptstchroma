<?php
/**
 * Google Places API Client
 * Integrates with Google Places API to fetch GMB data (reviews, hours, photos)
 * Auto-extracts Place ID from existing GMB URLs
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Google_Places_Client
{
    private $api_key;
    
    public function __construct() {
        $this->api_key = get_option('chroma_google_places_api_key', '');
        
        // Register AJAX handlers
        add_action('wp_ajax_chroma_sync_gmb_data', [$this, 'ajax_sync_gmb_data']);
        add_action('wp_ajax_chroma_get_place_id', [$this, 'ajax_get_place_id']);
    }
    
    /**
     * Get Place ID from GMB URL or coordinates
     */
    public function get_place_id($post_id) {
        // Check if we already have it cached
        $cached_id = get_post_meta($post_id, '_chroma_place_id', true);
        if ($cached_id) {
            return $cached_id;
        }
        
        // Try to extract from GMB URL
        $gmb_url = get_post_meta($post_id, 'location_gmb_url', true);
        if ($gmb_url) {
            $place_id = $this->extract_place_id_from_url($gmb_url);
            if ($place_id) {
                update_post_meta($post_id, '_chroma_place_id', $place_id);
                return $place_id;
            }
        }
        
        // Try to search by name and address
        $name = get_the_title($post_id);
        $address = get_post_meta($post_id, 'location_address', true);
        if ($name && $address) {
            $place_id = $this->search_place_id($name, $address);
            if ($place_id) {
                update_post_meta($post_id, '_chroma_place_id', $place_id);
                return $place_id;
            }
        }
        
        // Try using coordinates from map iframe or meta
        $lat = get_post_meta($post_id, 'location_latitude', true) 
            ?: get_post_meta($post_id, 'geo_lat', true);
        $lng = get_post_meta($post_id, 'location_longitude', true) 
            ?: get_post_meta($post_id, 'geo_lng', true);
        
        if ($lat && $lng) {
            $place_id = $this->get_place_id_from_coords($lat, $lng, $name);
            if ($place_id) {
                update_post_meta($post_id, '_chroma_place_id', $place_id);
                return $place_id;
            }
        }
        
        return false;
    }
    
    /**
     * Extract Place ID from various GMB URL formats
     */
    private function extract_place_id_from_url($url) {
        // Format: https://maps.google.com/?cid=XXXXX
        if (preg_match('/[?&]cid=(\d+)/', $url, $matches)) {
            return 'ChIJ' . base_convert($matches[1], 10, 36);
        }
        
        // Format: https://www.google.com/maps/place/.../data=...!1sCHIJ...
        if (preg_match('/!1s(ChIJ[a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }
        
        // Format: g.page/business-name
        // Need to follow redirect to get actual Place ID
        if (strpos($url, 'g.page') !== false) {
            return $this->follow_gpage_redirect($url);
        }
        
        return false;
    }
    
    /**
     * Follow g.page redirect to get final URL with Place ID
     */
    private function follow_gpage_redirect($url) {
        $response = wp_remote_head($url, [
            'redirection' => 0,
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $final_url = wp_remote_retrieve_header($response, 'location');
        if ($final_url) {
            return $this->extract_place_id_from_url($final_url);
        }
        
        return false;
    }
    
    /**
     * Search for Place ID using name and address
     */
    private function search_place_id($name, $address) {
        if (empty($this->api_key)) {
            return false;
        }
        
        $query = urlencode($name . ' ' . $address);
        $url = "https://maps.googleapis.com/maps/api/place/findplacefromtext/json"
            . "?input={$query}"
            . "&inputtype=textquery"
            . "&fields=place_id"
            . "&key={$this->api_key}";
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['candidates'][0]['place_id'])) {
            return $data['candidates'][0]['place_id'];
        }
        
        return false;
    }
    
    /**
     * Get Place ID from coordinates using Nearby Search
     */
    private function get_place_id_from_coords($lat, $lng, $name = '') {
        if (empty($this->api_key)) {
            return false;
        }
        
        $url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json"
            . "?location={$lat},{$lng}"
            . "&radius=50"
            . "&type=establishment"
            . "&key={$this->api_key}";
        
        if ($name) {
            $url .= "&keyword=" . urlencode($name);
        }
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['results'][0]['place_id'])) {
            return $data['results'][0]['place_id'];
        }
        
        return false;
    }
    
    /**
     * Get Place Details (reviews, hours, photos, etc.)
     */
    public function get_place_details($place_id) {
        if (empty($this->api_key) || empty($place_id)) {
            return false;
        }
        
        $fields = 'name,formatted_address,formatted_phone_number,website,'
            . 'rating,user_ratings_total,reviews,opening_hours,'
            . 'photos,price_level,business_status,geometry';
        
        $url = "https://maps.googleapis.com/maps/api/place/details/json"
            . "?place_id={$place_id}"
            . "&fields={$fields}"
            . "&key={$this->api_key}";
        
        // Cache for 24 hours
        $cache_key = 'chroma_gmb_' . md5($place_id);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $response = wp_remote_get($url, ['timeout' => 15]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['result'])) {
            set_transient($cache_key, $data['result'], DAY_IN_SECONDS);
            return $data['result'];
        }
        
        return false;
    }
    
    /**
     * Sync GMB data to post meta
     */
    public function sync_gmb_to_post($post_id) {
        $place_id = $this->get_place_id($post_id);
        if (!$place_id) {
            return new WP_Error('no_place_id', 'Could not find Google Place ID');
        }
        
        $details = $this->get_place_details($place_id);
        if (!$details) {
            return new WP_Error('no_details', 'Could not fetch place details');
        }
        
        $synced = [];
        
        // Sync phone if not set
        if (!empty($details['formatted_phone_number'])) {
            $current = get_post_meta($post_id, 'location_phone', true);
            if (empty($current)) {
                update_post_meta($post_id, 'location_phone', $details['formatted_phone_number']);
                $synced[] = 'phone';
            }
        }
        
        // Sync rating/reviews for schema
        if (!empty($details['rating'])) {
            update_post_meta($post_id, '_gmb_rating', $details['rating']);
            update_post_meta($post_id, '_gmb_review_count', $details['user_ratings_total'] ?? 0);
            $synced[] = 'rating';
        }
        
        // Sync opening hours
        if (!empty($details['opening_hours']['weekday_text'])) {
            update_post_meta($post_id, '_gmb_hours', $details['opening_hours']['weekday_text']);
            $synced[] = 'hours';
        }
        
        // Sync coordinates
        if (!empty($details['geometry']['location'])) {
            $geo = $details['geometry']['location'];
            update_post_meta($post_id, 'geo_lat', $geo['lat']);
            update_post_meta($post_id, 'geo_lng', $geo['lng']);
            $synced[] = 'geo';
        }
        
        // Sync individual reviews for aggregateRating
        if (!empty($details['reviews'])) {
            $reviews = [];
            foreach ($details['reviews'] as $review) {
                $reviews[] = [
                    'author' => $review['author_name'],
                    'rating' => $review['rating'],
                    'text' => $review['text'],
                    'time' => $review['time']
                ];
            }
            update_post_meta($post_id, '_gmb_reviews', $reviews);
            $synced[] = 'reviews';
        }
        
        // Record last sync time
        update_post_meta($post_id, '_gmb_last_sync', current_time('mysql'));
        
        return $synced;
    }
    
    /**
     * AJAX: Sync GMB data
     */
    public function ajax_sync_gmb_data() {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(['message' => 'No post ID provided']);
        }
        
        $result = $this->sync_gmb_to_post($post_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => 'Synced: ' . implode(', ', $result),
            'synced' => $result
        ]);
    }
    
    /**
     * AJAX: Get Place ID
     */
    public function ajax_get_place_id() {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(['message' => 'No post ID']);
        }
        
        $place_id = $this->get_place_id($post_id);
        
        if ($place_id) {
            wp_send_json_success(['place_id' => $place_id]);
        } else {
            wp_send_json_error(['message' => 'Could not determine Place ID']);
        }
    }
    
    /**
     * Get GMB rating for schema
     */
    public static function get_rating_for_schema($post_id) {
        $rating = get_post_meta($post_id, '_gmb_rating', true);
        $count = get_post_meta($post_id, '_gmb_review_count', true);
        
        if (!$rating) {
            return null;
        }
        
        return [
            '@type' => 'AggregateRating',
            'ratingValue' => floatval($rating),
            'reviewCount' => intval($count) ?: 1,
            'bestRating' => '5',
            'worstRating' => '1'
        ];
    }
}

// Initialize
new Chroma_Google_Places_Client();



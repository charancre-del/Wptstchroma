<?php
/**
 * Related Locations - Auto-link nearby locations
 * Shows "Other Locations Near You" on each location page
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Related_Locations
{
    public function __construct() {
        add_filter('the_content', [$this, 'append_related_locations'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }
    
    /**
     * Append related locations after content
     */
    public function append_related_locations($content) {
        if (!is_singular('location') || doing_filter('get_the_excerpt')) {
            return $content;
        }
        
        if (!get_option('chroma_seo_show_related_locations', true)) {
            return $content;
        }
        
        $related = $this->get_nearby_locations(get_the_ID());
        
        if (empty($related)) {
            return $content;
        }
        
        ob_start();
        $this->render_related_locations($related);
        $html = ob_get_clean();
        
        return $content . $html;
    }
    
    /**
     * Get nearby locations sorted by distance
     */
    public function get_nearby_locations($post_id, $limit = 4) {
        $current_lat = get_post_meta($post_id, 'geo_lat', true) 
            ?: get_post_meta($post_id, 'location_latitude', true);
        $current_lng = get_post_meta($post_id, 'geo_lng', true) 
            ?: get_post_meta($post_id, 'location_longitude', true);
        
        if (!$current_lat || !$current_lng) {
            // Fallback: just get other locations
            return get_posts([
                'post_type' => 'location',
                'posts_per_page' => $limit,
                'post__not_in' => [$post_id],
                'post_status' => 'publish'
            ]);
        }
        
        // Get all locations with coordinates
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post__not_in' => [$post_id],
            'post_status' => 'publish'
        ]);
        
        $distances = [];
        
        foreach ($locations as $loc) {
            $lat = get_post_meta($loc->ID, 'geo_lat', true) 
                ?: get_post_meta($loc->ID, 'location_latitude', true);
            $lng = get_post_meta($loc->ID, 'geo_lng', true) 
                ?: get_post_meta($loc->ID, 'location_longitude', true);
            
            if ($lat && $lng) {
                $distance = $this->calculate_distance($current_lat, $current_lng, $lat, $lng);
                $distances[$loc->ID] = [
                    'post' => $loc,
                    'distance' => $distance
                ];
            }
        }
        
        // Sort by distance
        uasort($distances, function($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });
        
        // Return only posts, limited
        return array_map(function($item) {
            $item['post']->distance = $item['distance'];
            return $item['post'];
        }, array_slice($distances, 0, $limit, true));
    }
    
    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculate_distance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 3959; // miles
        
        $lat1 = deg2rad(floatval($lat1));
        $lon1 = deg2rad(floatval($lon1));
        $lat2 = deg2rad(floatval($lat2));
        $lon2 = deg2rad(floatval($lon2));
        
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        
        $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
        $c = 2 * asin(sqrt($a));
        
        return $earth_radius * $c;
    }
    
    /**
     * Render related locations HTML
     */
    private function render_related_locations($locations) {
        ?>
        <section class="chroma-related-locations">
            <h2>Other Locations Near You</h2>
            <div class="related-locations-grid">
                <?php foreach ($locations as $loc): 
                    $city = get_post_meta($loc->ID, 'location_city', true);
                    $distance = isset($loc->distance) ? round($loc->distance, 1) : null;
                ?>
                <a href="<?php echo get_permalink($loc); ?>" class="related-location-card">
                    <?php if (has_post_thumbnail($loc)): ?>
                        <div class="card-image">
                            <?php echo get_the_post_thumbnail($loc, 'medium'); ?>
                        </div>
                    <?php endif; ?>
                    <div class="card-content">
                        <h3><?php echo esc_html($loc->post_title); ?></h3>
                        <?php if ($city): ?>
                            <p class="location-city">Daycare in <?php echo esc_html($city); ?></p>
                        <?php endif; ?>
                        <?php if ($distance !== null): ?>
                            <p class="location-distance"><?php echo $distance; ?> miles away</p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
    
    /**
     * Enqueue styles
     */
    public function enqueue_styles() {
        if (!is_singular('location')) return;
        
        wp_add_inline_style('chroma-main', '
            .chroma-related-locations {
                margin: 40px 0;
                padding: 30px;
                background: #f9f9f9;
                border-radius: 12px;
            }
            .chroma-related-locations h2 {
                margin: 0 0 20px;
                font-size: 24px;
            }
            .related-locations-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
            .related-location-card {
                background: #fff;
                border-radius: 8px;
                overflow: hidden;
                text-decoration: none;
                color: inherit;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .related-location-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 4px 16px rgba(0,0,0,0.12);
            }
            .related-location-card .card-image img {
                width: 100%;
                height: 150px;
                object-fit: cover;
            }
            .related-location-card .card-content {
                padding: 15px;
            }
            .related-location-card h3 {
                margin: 0 0 5px;
                font-size: 16px;
            }
            .related-location-card .location-city {
                margin: 0;
                color: #666;
                font-size: 14px;
            }
            .related-location-card .location-distance {
                margin: 5px 0 0;
                color: #0073aa;
                font-size: 13px;
                font-weight: 600;
            }
        ');
    }
    
    /**
     * Shortcode: [related_locations count="4"]
     */
    public static function shortcode($atts) {
        $atts = shortcode_atts(['count' => 4], $atts);
        
        if (!is_singular('location')) {
            return '';
        }
        
        $instance = new self();
        $related = $instance->get_nearby_locations(get_the_ID(), intval($atts['count']));
        
        if (empty($related)) {
            return '';
        }
        
        ob_start();
        $instance->render_related_locations($related);
        return ob_get_clean();
    }
}

add_shortcode('related_locations', ['Chroma_Related_Locations', 'shortcode']);
new Chroma_Related_Locations();

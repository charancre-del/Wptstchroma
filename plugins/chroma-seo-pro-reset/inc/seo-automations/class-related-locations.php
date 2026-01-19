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
        <section class="chroma-related-locations py-16 border-t border-brand-ink/5 bg-brand-cream/30 mt-16">
            <div class="max-w-7xl mx-auto px-4 lg:px-6">
                <h2 class="font-serif text-3xl font-bold text-brand-ink mb-10"><?php _e('Other Locations Near You', 'chroma-excellence'); ?></h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($locations as $loc): 
                        $location_id = $loc->ID;
                        $city = get_post_meta($location_id, 'location_city', true);
                        $distance = isset($loc->distance) ? round($loc->distance, 1) : null;
                        
                        // Get region colors
                        $regions = wp_get_post_terms($location_id, 'location_region');
                        $region_term = !empty($regions) && !is_wp_error($regions) ? $regions[0] : null;
                        $colors = $region_term ? chroma_get_region_color_from_term($region_term->term_id) : array(
                            'bg' => 'chroma-blueLight', 'text' => 'chroma-blue', 'border' => 'chroma-blue'
                        );

                        // Badges
                        $is_decal = get_post_meta($location_id, 'location_decal_licensed', true);
                        $quality_rated = get_post_meta($location_id, 'location_quality_rated', true);
                    ?>
                    <div class="location-card group">
                        <div class="bg-white rounded-[2rem] p-6 shadow-card border border-brand-ink/5 hover:border-<?php echo esc_attr($colors['border']); ?>/30 transition-all hover:-translate-y-1 h-full flex flex-col relative overflow-hidden">
                            
                            <?php if (has_post_thumbnail($loc)): ?>
                                <div class="rounded-2xl overflow-hidden mb-4 aspect-[4/3] relative">
                                    <?php echo get_the_post_thumbnail($loc, 'medium', ['class' => 'w-full h-full object-cover']); ?>
                                </div>
                            <?php endif; ?>

                            <h3 class="font-serif text-xl font-bold text-brand-ink mb-2 group-hover:text-<?php echo esc_attr($colors['text']); ?> transition-colors">
                                <a href="<?php echo get_permalink($loc); ?>"><?php echo esc_html($loc->post_title); ?></a>
                            </h3>
                            
                            <?php if ($city): ?>
                                <p class="text-sm text-brand-ink/80 mb-4">Daycare in <?php echo esc_html($city); ?></p>
                            <?php endif; ?>

                            <div class="flex flex-wrap gap-2 mb-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-chroma-blueLight/50 text-chroma-blueDark text-[10px] font-bold uppercase rounded-full border border-chroma-blueDark/10">
                                    <i class="fa-solid fa-building-columns"></i> GA DECAL Licensed
                                </span>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-chroma-yellowLight/50 text-chroma-yellowDark text-[10px] font-bold uppercase rounded-full border border-chroma-yellowDark/10">
                                    <i class="fa-solid fa-check"></i> Quality Rated
                                </span>
                            </div>

                            <div class="mt-auto flex items-center justify-between">
                                <?php if ($distance !== null): ?>
                                    <span class="text-[11px] font-bold text-chroma-blue uppercase tracking-wider"><?php echo $distance; ?> miles away</span>
                                <?php endif; ?>
                                <a href="<?php echo get_permalink($loc); ?>" class="text-[10px] font-bold text-brand-ink/40 uppercase tracking-widest group-hover:text-<?php echo esc_attr($colors['text']); ?> transition-colors">
                                    Details <i class="fa-solid fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }
    
    /**
     * Enqueue styles - Replaced with theme logic
     */
    public function enqueue_styles() {
        // No longer needed but kept for hook compatibility
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



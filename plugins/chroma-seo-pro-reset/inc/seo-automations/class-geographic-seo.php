<?php
/**
 * Geographic SEO Suite
 * IP detection, service areas, county/ZIP pages
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Geographic_SEO
{
    public function __construct() {
        add_action('wp_head', [$this, 'output_geo_meta']);
        add_shortcode('nearest_location', [$this, 'nearest_location_shortcode']);
        add_action('init', [$this, 'add_service_area_rewrites']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_service_area_page']);
    }
    
    /**
     * Output geo meta tags
     */
    public function output_geo_meta() {
        if (!is_singular('location')) {
            return;
        }
        
        $post_id = get_the_ID();
        $lat = get_post_meta($post_id, 'geo_lat', true) 
            ?: get_post_meta($post_id, 'location_latitude', true);
        $lng = get_post_meta($post_id, 'geo_lng', true) 
            ?: get_post_meta($post_id, 'location_longitude', true);
        $city = get_post_meta($post_id, 'location_city', true);
        $state = get_post_meta($post_id, 'location_state', true);
        
        if ($lat && $lng) {
            echo '<meta name="geo.position" content="' . esc_attr($lat) . ';' . esc_attr($lng) . '">' . "\n";
            echo '<meta name="ICBM" content="' . esc_attr($lat) . ', ' . esc_attr($lng) . '">' . "\n";
        }
        
        if ($city && $state) {
            echo '<meta name="geo.placename" content="' . esc_attr($city . ', ' . $state) . '">' . "\n";
            echo '<meta name="geo.region" content="US-' . esc_attr($state) . '">' . "\n";
        }
    }
    
    /**
     * Detect user location from IP
     */
    public static function detect_user_location() {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Skip local IPs
        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return null;
        }
        
        // Check cache
        $cache_key = 'geo_ip_' . md5($ip);
        $cached = get_transient($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Use ip-api.com (free tier)
        $response = wp_remote_get('http://ip-api.com/json/' . $ip . '?fields=lat,lon,city,regionCode', [
            'timeout' => 3
        ]);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['lat'], $data['lon'])) {
            $result = [
                'lat' => $data['lat'],
                'lng' => $data['lon'],
                'city' => $data['city'] ?? '',
                'state' => $data['regionCode'] ?? ''
            ];
            
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
            
            return $result;
        }
        
        return null;
    }
    
    /**
     * Get nearest location to coordinates
     */
    public static function get_nearest_location($lat, $lng) {
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $nearest = null;
        $min_distance = PHP_FLOAT_MAX;
        
        foreach ($locations as $loc) {
            $loc_lat = get_post_meta($loc->ID, 'geo_lat', true) 
                ?: get_post_meta($loc->ID, 'location_latitude', true);
            $loc_lng = get_post_meta($loc->ID, 'geo_lng', true) 
                ?: get_post_meta($loc->ID, 'location_longitude', true);
            
            if (!$loc_lat || !$loc_lng) continue;
            
            $distance = self::haversine($lat, $lng, $loc_lat, $loc_lng);
            
            if ($distance < $min_distance) {
                $min_distance = $distance;
                $nearest = $loc;
                $nearest->distance = $distance;
            }
        }
        
        return $nearest;
    }
    
    /**
     * Haversine formula
     */
    private static function haversine($lat1, $lon1, $lat2, $lon2) {
        $R = 3959; // miles
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) ** 2;
        return $R * 2 * asin(sqrt($a));
    }
    
    /**
     * Shortcode: [nearest_location]
     */
    public function nearest_location_shortcode($atts) {
        $user_geo = self::detect_user_location();
        
        if (!$user_geo) {
            // Fallback: show first location
            $location = get_posts(['post_type' => 'location', 'posts_per_page' => 1])[0] ?? null;
        } else {
            $location = self::get_nearest_location($user_geo['lat'], $user_geo['lng']);
        }
        
        if (!$location) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="nearest-location-widget">
            <h3>📍 Nearest Location<?php echo $user_geo ? ' to ' . esc_html($user_geo['city']) : ''; ?></h3>
            <div class="location-info">
                <strong><?php echo esc_html($location->post_title); ?></strong>
                <?php if (isset($location->distance)): ?>
                    <span class="distance">(<?php echo round($location->distance, 1); ?> mi)</span>
                <?php endif; ?>
                <br>
                <a href="<?php echo get_permalink($location); ?>">View Details →</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add service area rewrites
     */
    public function add_service_area_rewrites() {
        // County pages: /childcare-in-forsyth-county/
        add_rewrite_rule(
            '^childcare-in-([a-z-]+)-county/?$',
            'index.php?chroma_service_area=county&area_name=$matches[1]',
            'top'
        );
        
        // ZIP pages: /daycare-30041/
        add_rewrite_rule(
            '^daycare-(\d{5})/?$',
            'index.php?chroma_service_area=zip&area_name=$matches[1]',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'chroma_service_area';
        $vars[] = 'area_name';
        return $vars;
    }
    
    /**
     * Handle service area page
     */
    public function handle_service_area_page() {
        $area_type = get_query_var('chroma_service_area');
        
        if (!$area_type) {
            return;
        }
        
        $area_name = sanitize_text_field(get_query_var('area_name'));
        
        // Get all locations
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        get_header();
        
        if ($area_type === 'county') {
            $this->render_county_page($area_name, $locations);
        } elseif ($area_type === 'zip') {
            $this->render_zip_page($area_name, $locations);
        }
        
        get_footer();
        exit;
    }
    
    /**
     * Render county page
     */
    private function render_county_page($county_slug, $locations) {
        $county_name = ucwords(str_replace('-', ' ', $county_slug)) . ' County';
        ?>
        <main class="service-area-page">
            <h1>Childcare in <?php echo esc_html($county_name); ?></h1>
            <p>Find quality early learning centers serving families in <?php echo esc_html($county_name); ?>.</p>
            
            <section class="locations-grid">
                <?php foreach ($locations as $loc): ?>
                    <?php $this->render_location_card($loc); ?>
                <?php endforeach; ?>
            </section>
        </main>
        <style>
            .service-area-page { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
            .locations-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0; }
        </style>
        <?php
    }
    
    /**
     * Render ZIP page
     */
    private function render_zip_page($zip, $locations) {
        ?>
        <main class="service-area-page">
            <h1>Daycare Near <?php echo esc_html($zip); ?></h1>
            <p>Early learning centers serving the <?php echo esc_html($zip); ?> area.</p>
            
            <section class="locations-grid">
                <?php foreach ($locations as $loc): ?>
                    <?php $this->render_location_card($loc); ?>
                <?php endforeach; ?>
            </section>
        </main>
        <?php
    }
    
    /**
     * Render location card
     */
    private function render_location_card($loc) {
        ?>
        <article class="location-card">
            <?php if (has_post_thumbnail($loc)): ?>
                <?php echo get_the_post_thumbnail($loc, 'medium'); ?>
            <?php endif; ?>
            <h3><a href="<?php echo get_permalink($loc); ?>"><?php echo esc_html($loc->post_title); ?></a></h3>
            <p><?php echo esc_html(get_post_meta($loc->ID, 'location_city', true)); ?></p>
        </article>
        <?php
    }
}

new Chroma_Geographic_SEO();



<?php
/**
 * Footer City Links - Auto-generate city links in footer
 * Helps crawlers discover all location pages
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Footer_City_Links
{
    public function __construct() {
        add_action('wp_footer', [$this, 'render_footer_links'], 5);
        add_action('widgets_init', [$this, 'register_widget']);
        add_action('save_post_location', [$this, 'clear_cache']);
    }

    /**
     * Clear city links cache
     */
    public function clear_cache() {
        delete_transient('chroma_footer_cities');
    }
    
    /**
     * Get all cities from locations
     */
    public static function get_cities() {
        $cities = get_transient('chroma_footer_cities');
        if ($cities !== false) {
            return $cities;
        }

        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $cities = [];
        
        foreach ($locations as $loc) {
            $city = trim(get_post_meta($loc->ID, 'location_city', true));
            $state = trim(get_post_meta($loc->ID, 'location_state', true));
            
            if (empty($state)) {
                $state = 'GA';
            }
            $state = strtoupper($state);
            
            if ($city) {
                $key = sanitize_title($city . '-' . $state);
                
                if (!isset($cities[$key])) {
                    $cities[$key] = [
                        'city' => $city,
                        'state' => $state,
                        'url' => get_permalink($loc),
                        'count' => 1
                    ];
                } else {
                    $cities[$key]['count']++;
                }
            }
        }
        
        uasort($cities, function($a, $b) {
            return strcmp($a['city'], $b['city']);
        });

        set_transient('chroma_footer_cities', $cities, DAY_IN_SECONDS);
        
        return $cities;
    }
    
    /**
     * Render footer links
     */
    public function render_footer_links() {
        if (!get_option('chroma_seo_show_footer_cities', true)) {
            return;
        }
        
        $cities = self::get_cities();
        
        if (empty($cities)) {
            return;
        }
        
        // Group by state
        $by_state = [];
        foreach ($cities as $c) {
            $state = $c['state'] ?: 'Other';
            $by_state[$state][] = $c;
        }
        
        ?>
        <div class="chroma-footer-cities">
            <div class="footer-cities-container">
                <?php foreach ($by_state as $state => $state_cities): ?>
                <div class="footer-cities-group">
                    <h4>Serving <?php echo esc_html($state); ?>:</h4>
                    <div class="city-links">
                        <?php 
                        $links = [];
                        foreach ($state_cities as $c) {
                            $links[] = '<a href="' . esc_url($c['url']) . '">' . esc_html($c['city']) . '</a>';
                        }
                        echo implode(' | ', $links);
                        ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <style>
            .chroma-footer-cities {
                background: #1a1a1a;
                padding: 20px 0;
                text-align: center;
            }
            .footer-cities-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 20px;
            }
            .footer-cities-group {
                margin: 10px 0;
            }
            .footer-cities-group h4 {
                color: #888;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin: 0 0 8px;
            }
            .city-links a {
                color: #ccc;
                text-decoration: none;
                font-size: 13px;
            }
            .city-links a:hover {
                color: #fff;
                text-decoration: underline;
            }
        </style>
        <?php
    }
    
    /**
     * Register widget
     */
    public function register_widget() {
        register_widget('Chroma_City_Links_Widget');
    }
    
    /**
     * Shortcode: [city_links]
     */
    public static function shortcode($atts) {
        $cities = self::get_cities();
        
        if (empty($cities)) {
            return '';
        }
        
        $links = [];
        foreach ($cities as $c) {
            $links[] = '<a href="' . esc_url($c['url']) . '">' . esc_html($c['city']) . '</a>';
        }
        
        return '<div class="city-links-inline">' . implode(' • ', $links) . '</div>';
    }
}

/**
 * City Links Widget
 */
class Chroma_City_Links_Widget extends WP_Widget
{
    public function __construct() {
        parent::__construct(
            'chroma_city_links',
            'SEO City Links',
            ['description' => 'Display links to all location cities']
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        $title = !empty($instance['title']) ? $instance['title'] : 'Our Locations';
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        $cities = Chroma_Footer_City_Links::get_cities();
        
        if (!empty($cities)) {
            echo '<ul class="city-links-widget">';
            foreach ($cities as $c) {
                echo '<li><a href="' . esc_url($c['url']) . '">' . esc_html($c['city']) . '</a></li>';
            }
            echo '</ul>';
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Our Locations';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input type="text" id="<?php echo $this->get_field_id('title'); ?>" 
                name="<?php echo $this->get_field_name('title'); ?>" 
                value="<?php echo esc_attr($title); ?>" class="widefat">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title']);
        return $instance;
    }
}

add_shortcode('city_links', ['Chroma_Footer_City_Links', 'shortcode']);
new Chroma_Footer_City_Links();



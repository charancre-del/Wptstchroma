<?php
/**
 * Near Me Pages - Hybrid approach
 * Pre-generated for SEO + JS personalization for UX
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Near_Me_Pages
{
    const REWRITE_TAG = 'chroma_near_me';
    
    private $keywords = ['daycare', 'preschool', 'childcare', 'pre-k', 'infant-care'];
    
    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_near_me_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Register Native Sitemap Provider
        if (did_action('init')) {
            $this->register_sitemap_provider();
        } else {
            add_action('init', [$this, 'register_sitemap_provider']);
        }
    }

    /**
     * Register WP Native Sitemap Provider
     */
    public function register_sitemap_provider() {
        $provider = new Chroma_Near_Me_Sitemap_Provider();
        wp_register_sitemap_provider('near-me', $provider);
    }
    
    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        // Generic: /daycare-near-me/
        foreach ($this->keywords as $kw) {
            add_rewrite_rule(
                '^' . $kw . '-near-me/?$',
                'index.php?' . self::REWRITE_TAG . '=' . $kw,
                'top'
            );
            
            // City-specific: /daycare-near-cumming-ga/
            add_rewrite_rule(
                '^' . $kw . '-near-([a-z-]+)-([a-z]{2})/?$',
                'index.php?' . self::REWRITE_TAG . '=' . $kw . '&near_city=$matches[1]&near_state=$matches[2]',
                'top'
            );
        }
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = self::REWRITE_TAG;
        $vars[] = 'near_city';
        $vars[] = 'near_state';
        return $vars;
    }
    
    /**
     * Handle near me page request
     */
    public function handle_near_me_page() {
        $keyword = get_query_var(self::REWRITE_TAG);
        
        if (!$keyword) {
            return;
        }
        
        $city_slug = sanitize_title(get_query_var('near_city'));
        $state = strtoupper(sanitize_text_field(get_query_var('near_state')));
        
        $this->render_near_me_page($keyword, $city_slug, $state);
        exit;
    }
    
    /**
     * Get all locations with geo data
     */
    private function get_locations_with_geo() {
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $result = [];
        
        foreach ($locations as $loc) {
            $lat = get_post_meta($loc->ID, 'geo_lat', true) 
                ?: get_post_meta($loc->ID, 'location_latitude', true);
            $lng = get_post_meta($loc->ID, 'geo_lng', true) 
                ?: get_post_meta($loc->ID, 'location_longitude', true);
            
            $result[] = [
                'id' => $loc->ID,
                'title' => $loc->post_title,
                'url' => get_permalink($loc),
                'city' => get_post_meta($loc->ID, 'location_city', true),
                'state' => get_post_meta($loc->ID, 'location_state', true),
                'address' => get_post_meta($loc->ID, 'location_address', true),
                'phone' => get_post_meta($loc->ID, 'location_phone', true),
                'lat' => floatval($lat),
                'lng' => floatval($lng),
                'image' => get_the_post_thumbnail_url($loc, 'medium')
            ];
        }
        
        return $result;
    }
    
    /**
     * Render near me page
     */
    private function render_near_me_page($keyword, $city_slug = '', $state = '') {
        $keyword_label = ucwords(str_replace('-', ' ', $keyword));
        $locations = $this->get_locations_with_geo();
        
        // If city-specific, filter/sort by that city
        $city_name = '';
        if ($city_slug && $state) {
            $city_name = ucwords(str_replace('-', ' ', $city_slug));
            $page_title = $keyword_label . ' Near ' . $city_name . ', ' . $state;
        } else {
            $page_title = $keyword_label . ' ' . __( 'Near Me', 'chroma-excellence' );
        }
        
        get_header();
        ?>
        <main class="near-me-page">
            <header class="near-me-header">
                <h1><?php echo esc_html($page_title); ?></h1>
                <p><?php printf( esc_html__( 'Find quality %s locations near you.', 'chroma-excellence' ), esc_html( strtolower( $keyword_label ) ) ); ?></p>
                
                <div id="nearest-highlight" style="display:none;">
                    <span class="nearest-badge">📍 <?php esc_html_e( 'Nearest to you:', 'chroma-excellence' ); ?></span>
                    <strong id="nearest-name"></strong>
                    <span id="nearest-distance"></span>
                </div>
            </header>
            
            <section class="locations-grid" id="locations-grid">
                <?php foreach ($locations as $loc): ?>
                <article class="location-card" 
                    data-lat="<?php echo esc_attr($loc['lat']); ?>" 
                    data-lng="<?php echo esc_attr($loc['lng']); ?>"
                    data-id="<?php echo esc_attr($loc['id']); ?>">
                    <?php if ($loc['image']): ?>
                        <img src="<?php echo esc_url($loc['image']); ?>" alt="<?php echo esc_attr($loc['title']); ?>">
                    <?php endif; ?>
                    <div class="card-content">
                        <h2><a href="<?php echo esc_url($loc['url']); ?>"><?php echo esc_html($loc['title']); ?></a></h2>
                        <p class="location-city"><?php echo esc_html($loc['city'] . ', ' . $loc['state']); ?></p>
                        <?php if ($loc['address']): ?>
                            <p class="address"><?php echo esc_html($loc['address']); ?></p>
                        <?php endif; ?>
                        <p class="distance-display" style="display:none;"></p>
                        <div class="card-actions">
                            <a href="<?php echo esc_url($loc['url']); ?>" class="btn-view"><?php esc_html_e( 'View Location', 'chroma-excellence' ); ?></a>
                            <?php if ($loc['phone']): ?>
                                <a href="tel:<?php echo esc_attr($loc['phone']); ?>" class="btn-call"><?php esc_html_e( 'Call Now', 'chroma-excellence' ); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </section>
        </main>
        
        <style>
            .near-me-page { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
            .near-me-header { text-align: center; margin-bottom: 40px; }
            .near-me-header h1 { font-size: 36px; margin-bottom: 10px; }
            #nearest-highlight { margin-top: 20px; padding: 15px 25px; background: #e8f5e9; border-radius: 8px; display: inline-block; }
            .nearest-badge { background: #4caf50; color: #fff; padding: 3px 10px; border-radius: 4px; margin-right: 10px; }
            .locations-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
            .location-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: transform 0.2s; }
            .location-card:hover { transform: translateY(-4px); }
            .location-card.nearest { border: 3px solid #4caf50; }
            .location-card img { width: 100%; height: 180px; object-fit: cover; }
            .card-content { padding: 20px; }
            .card-content h2 { font-size: 20px; margin: 0 0 8px; }
            .card-content h2 a { text-decoration: none; color: inherit; }
            .location-city { color: #666; margin: 0; }
            .address { color: #888; font-size: 14px; margin: 10px 0; }
            .distance-display { color: #0066cc; font-weight: 600; margin: 10px 0; }
            .card-actions { display: flex; gap: 10px; margin-top: 15px; }
            .btn-view, .btn-call { padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 14px; }
            .btn-view { background: #0066cc; color: #fff; }
            .btn-call { background: #f0f0f0; color: #333; }
        </style>
        
        <!-- Location data for JS -->
        <script type="application/json" id="locations-data">
            <?php echo json_encode($locations); ?>
        </script>
        
        <?php
        // Output schema
        $this->output_schema($keyword_label, $locations);
        
        get_footer();
    }
    
    /**
     * Enqueue personalization script
     */
    public function enqueue_scripts() {
        if (!get_query_var(self::REWRITE_TAG)) {
            return;
        }
        
        wp_add_inline_script('jquery', $this->get_personalization_script());
    }
    
    /**
     * Get personalization JS
     */
    private function get_personalization_script() {
        $miles_away_text = __( 'miles away', 'chroma-excellence' );
        $mi_text = __( 'mi', 'chroma-excellence' );
        
        return "
        document.addEventListener('DOMContentLoaded', function() {
            var locationsData = JSON.parse(document.getElementById('locations-data').textContent);
            
            // Calculate distance (Haversine)
            function calcDistance(lat1, lon1, lat2, lon2) {
                var R = 3959; // miles
                var dLat = (lat2 - lat1) * Math.PI / 180;
                var dLon = (lon2 - lon1) * Math.PI / 180;
                var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLon/2) * Math.sin(dLon/2);
                var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return R * c;
            }
            
            // Sort and highlight
            function personalize(userLat, userLng) {
                var cards = document.querySelectorAll('.location-card');
                var distances = [];
                
                cards.forEach(function(card) {
                    var lat = parseFloat(card.dataset.lat);
                    var lng = parseFloat(card.dataset.lng);
                    var dist = calcDistance(userLat, userLng, lat, lng);
                    distances.push({ card: card, distance: dist });
                    
                    // Show distance
                    var distEl = card.querySelector('.distance-display');
                    if (distEl) {
                        distEl.textContent = dist.toFixed(1) + ' ' + '$miles_away_text';
                        distEl.style.display = 'block';
                    }
                });
                
                // Sort by distance
                distances.sort(function(a, b) { return a.distance - b.distance; });
                
                // Reorder DOM
                var grid = document.getElementById('locations-grid');
                distances.forEach(function(item) {
                    grid.appendChild(item.card);
                });
                
                // Highlight nearest
                if (distances.length > 0) {
                    distances[0].card.classList.add('nearest');
                    document.getElementById('nearest-highlight').style.display = 'inline-block';
                    document.getElementById('nearest-name').textContent = distances[0].card.querySelector('h2').textContent;
                    document.getElementById('nearest-distance').textContent = ' (' + distances[0].distance.toFixed(1) + ' $mi_text)';
                }
            }
            
            // Try browser geolocation
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        personalize(pos.coords.latitude, pos.coords.longitude);
                    },
                    function() {
                        // Fallback: IP geolocation
                        fetch('https://ipapi.co/json/')
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (data.latitude && data.longitude) {
                                    personalize(data.latitude, data.longitude);
                                }
                            })
                            .catch(function() { /* silent fail */ });
                    },
                    { timeout: 5000 }
                );
            }
        });
        ";
    }
    
    /**
     * Output schema
     */
    private function output_schema($keyword, $locations) {
        $items = [];
        foreach ($locations as $i => $loc) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => [
                    '@type' => 'LocalBusiness',
                    'name' => $loc['title'],
                    'address' => $loc['address'],
                    'url' => $loc['url']
                ]
            ];
        }
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $keyword . ' Locations',
            'numberOfItems' => count($locations),
            'itemListElement' => $items
        ];
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }
    
    /**
     * Get all near me URLs for sitemap
     */
    public static function get_sitemap_urls() {
        $urls = [];
        $keywords = ['daycare', 'preschool', 'childcare', 'pre-k'];
        
        // Generic
        foreach ($keywords as $kw) {
            $urls[] = home_url('/' . $kw . '-near-me/');
        }
        
        // City-specific
        $cities = Chroma_Combo_Page_Generator::get_all_cities();
        foreach ($keywords as $kw) {
            foreach ($cities as $city) {
                $urls[] = home_url('/' . $kw . '-near-' . sanitize_title($city['city']) . '-' . strtolower($city['state']) . '/');
            }
        }
        
        return $urls;
    }
}

new Chroma_Near_Me_Pages();

/**
 * Custom Sitemap Provider for Near Me Pages
 */
class Chroma_Near_Me_Sitemap_Provider extends WP_Sitemaps_Provider {
    
    public function __construct() {
        $this->name = 'near-me'; 
        $this->object_type = 'custom'; 
    }

    public function get_url_list($page_num, $object_subtype = '') {
        $urls = [];
        $links = Chroma_Near_Me_Pages::get_sitemap_urls();
        
        $per_page = 2000;
        $offset = ($page_num - 1) * $per_page;
        $page_links = array_slice($links, $offset, $per_page);
        
        foreach ($page_links as $link) {
            $urls[] = [
                'loc' => $link,
                'lastmod' => date('c'),
                'changefreq' => 'weekly',
                'priority' => 0.8,
            ];
        }

        return $urls;
    }

    public function get_max_num_pages($object_subtype = '') {
        $links = Chroma_Near_Me_Pages::get_sitemap_urls();
        return ceil(count($links) / 2000);
    }
}


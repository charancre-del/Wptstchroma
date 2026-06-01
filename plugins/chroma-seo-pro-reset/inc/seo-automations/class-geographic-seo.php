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
        add_filter('chroma_sitemap_urls', [$this, 'add_service_areas_to_unified_sitemap']);
        add_action('admin_menu', [$this, 'add_admin_page'], 30);
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
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }
        
        // Skip local IPs
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return null;
        }

        // Skip private/reserved ranges
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }
        
        // Check cache
        $cache_key = 'geo_ip_' . md5($ip);
        $cached = get_transient($cache_key);
        if ($cached) {
            return $cached;
        }
        
        // Use HTTPS endpoint to avoid insecure transport.
        $response = wp_remote_get('https://ipapi.co/' . rawurlencode($ip) . '/json/', [
            'timeout' => 3,
            'sslverify' => true,
        ]);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['latitude'], $data['longitude'])) {
            $result = [
                'lat' => $data['latitude'],
                'lng' => $data['longitude'],
                'city' => $data['city'] ?? '',
                'state' => $data['region_code'] ?? ''
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

        add_rewrite_rule(
            '^es/childcare-in-([a-z-]+)-county/?$',
            'index.php?chroma_service_area=county&area_name=$matches[1]&chroma_lang=es',
            'top'
        );
        
        // ZIP pages: /daycare-30041/
        add_rewrite_rule(
            '^daycare-(\d{5})/?$',
            'index.php?chroma_service_area=zip&area_name=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^es/daycare-(\d{5})/?$',
            'index.php?chroma_service_area=zip&area_name=$matches[1]&chroma_lang=es',
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
        $route = $this->detect_service_area_route_from_request();

        if (!$area_type && !empty($route['type'])) {
            $area_type = $route['type'];
        }
        
        if (!$area_type) {
            return;
        }
        
        $area_name = sanitize_text_field(get_query_var('area_name'));
        if ($area_name === '' && !empty($route['area_name'])) {
            $area_name = sanitize_text_field($route['area_name']);
        }

        if ($area_name === '') {
            return;
        }
        
        // Get all locations
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        $this->prepare_virtual_page_query_state();

        add_filter('body_class', function ($classes) {
            $classes[] = 'service-area-page';
            return $classes;
        });

        $canonical = $area_type === 'zip'
            ? home_url('/daycare-' . $area_name . '/')
            : home_url('/childcare-in-' . sanitize_title($area_name) . '-county/');
        $fallbacks = $this->get_service_area_seo_fallbacks($area_type, $area_name, $canonical);
        if (class_exists('Chroma_Virtual_Page_SEO_Data')) {
            Chroma_Virtual_Page_SEO_Data::apply_filters(
                Chroma_Virtual_Page_SEO_Data::resolve('service_area', [
                    'area_type' => $area_type,
                    'area_name' => $area_name,
                ], $fallbacks)
            );
        }

        $this->register_service_area_schema($area_type, $area_name, $locations, $fallbacks);
        
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
     * Add generated county and ZIP virtual pages to the flat /sitemap.xml.
     *
     * These pages are option-backed virtual routes, so they will never appear in
     * the standard post/page sitemap. Registering them here keeps them covered
     * without turning them into hidden WordPress pages.
     *
     * @param array $urls Existing sitemap entries.
     * @return array
     */
    public function add_service_areas_to_unified_sitemap($urls) {
        if (!is_array($urls)) {
            $urls = [];
        }

        if (!class_exists('Chroma_Virtual_Page_SEO_Data')) {
            return $urls;
        }

        $base = rtrim(home_url('/'), '/');
        foreach (Chroma_Virtual_Page_SEO_Data::service_area_candidates() as $item) {
            $url = isset($item['url']) ? (string) $item['url'] : '';
            if ($url === '') {
                continue;
            }

            $lastmod = $this->get_service_area_sitemap_lastmod($item);
            $urls[] = [
                'loc' => $url,
                'lastmod' => $lastmod,
            ];

            $es_url = str_replace($base . '/', $base . '/es/', $url);
            if ($es_url === $url) {
                $path = (string) wp_parse_url($url, PHP_URL_PATH);
                if ($path !== '') {
                    $es_url = home_url('/es/' . ltrim($path, '/'));
                }
            }

            if ($es_url !== $url) {
                $urls[] = [
                    'loc' => $es_url,
                    'lastmod' => $lastmod,
                ];
            }
        }

        return $urls;
    }

    /**
     * Recognize service-area routes even when rewrite rules have not been flushed.
     *
     * @return array{type:string,area_name:string}|array
     */
    private function detect_service_area_route_from_request() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return [];
        }

        if (preg_match('#^/(?:es/)?daycare-(\d{5})/?$#i', $path, $matches)) {
            return [
                'type' => 'zip',
                'area_name' => $matches[1],
            ];
        }

        if (preg_match('#^/(?:es/)?childcare-in-([a-z0-9-]+)-county/?$#i', $path, $matches)) {
            return [
                'type' => 'county',
                'area_name' => sanitize_title($matches[1]),
            ];
        }

        return [];
    }

    /**
     * Stable lastmod for option-backed service-area sitemap entries.
     *
     * @param array $item Service-area candidate.
     * @return string
     */
    private function get_service_area_sitemap_lastmod($item) {
        $timestamps = [];

        if (!empty($item['data']['last_updated'])) {
            $timestamps[] = (int) $item['data']['last_updated'];
        }

        $posts = get_posts([
            'post_type' => 'location',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);

        if (!empty($posts) && $posts[0] instanceof WP_Post && !empty($posts[0]->post_modified_gmt)) {
            $timestamps[] = strtotime($posts[0]->post_modified_gmt);
        }

        $timestamps = array_filter(array_map('intval', $timestamps));
        return !empty($timestamps) ? gmdate('c', max($timestamps)) : gmdate('c');
    }

    private function get_service_area_seo_fallbacks($area_type, $area_name, $canonical) {
        if ($area_type === 'zip') {
            return [
                'title' => 'Daycare Near ' . $area_name . ' | Chroma Early Learning',
                'meta_description' => 'Find quality early learning and childcare serving families near ' . $area_name . '. Explore Chroma locations, programs, and tour options.',
                'canonical' => $canonical,
            ];
        }

        $county_name = ucwords(str_replace('-', ' ', sanitize_title($area_name))) . ' County';
        return [
            'title' => 'Childcare in ' . $county_name . ' | Chroma Early Learning',
            'meta_description' => 'Find quality childcare and early learning centers serving families in ' . $county_name . '. Explore Chroma locations, programs, and tour options.',
            'canonical' => $canonical,
        ];
    }

    /**
     * Register schema for county and ZIP virtual pages before wp_head runs.
     *
     * These routes have no post ID, so they cannot rely on stored post meta or
     * normal singular builders. Registering here keeps them indexable virtual
     * SEO pages with structured data.
     */
    private function register_service_area_schema($area_type, $area_name, $locations, $fallbacks) {
        if (!class_exists('Chroma_Schema_Registry')) {
            return;
        }

        $canonical = isset($fallbacks['canonical']) ? (string) $fallbacks['canonical'] : home_url('/');
        $title = isset($fallbacks['title']) ? (string) $fallbacks['title'] : get_bloginfo('name');
        $description = isset($fallbacks['meta_description']) ? (string) $fallbacks['meta_description'] : '';
        $area_label = $area_type === 'zip'
            ? (string) $area_name
            : ucwords(str_replace('-', ' ', sanitize_title($area_name))) . ' County';

        $items = [];
        $position = 1;
        foreach ((array) $locations as $location) {
            if (!$location instanceof WP_Post) {
                continue;
            }

            $city = get_post_meta($location->ID, 'location_city', true);
            $state = get_post_meta($location->ID, 'location_state', true) ?: 'GA';
            $zip = get_post_meta($location->ID, 'location_zip', true) ?: get_post_meta($location->ID, 'location_zip_code', true);
            $street = get_post_meta($location->ID, 'location_address', true) ?: get_post_meta($location->ID, 'location_street_address', true);
            $phone = get_post_meta($location->ID, 'location_phone', true);

            $place = [
                '@type' => ['ChildCare', 'Preschool', 'EducationalOrganization', 'LocalBusiness'],
                'name' => get_the_title($location),
                'url' => get_permalink($location),
            ];

            if ($phone) {
                $place['telephone'] = $phone;
            }

            if ($street || $city || $state || $zip) {
                $place['address'] = array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $street,
                    'addressLocality' => $city,
                    'addressRegion' => $state,
                    'postalCode' => $zip,
                    'addressCountry' => 'US',
                ]);
            }

            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'item' => $place,
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => trailingslashit($canonical) . '#service-area',
            'name' => $title,
            'description' => $description,
            'url' => $canonical,
            'about' => [
                '@type' => 'Service',
                'name' => $area_type === 'zip' ? 'Daycare near ' . $area_label : 'Childcare in ' . $area_label,
                'areaServed' => [
                    '@type' => $area_type === 'zip' ? 'PostalCodeRangeSpecification' : 'AdministrativeArea',
                    'name' => $area_label,
                ],
                'provider' => [
                    '@type' => 'EducationalOrganization',
                    'name' => get_bloginfo('name'),
                    'url' => home_url('/'),
                ],
            ],
        ];

        if (!empty($items)) {
            $schema['mainEntity'] = [
                '@type' => 'ItemList',
                'name' => 'Chroma locations serving ' . $area_label,
                'itemListElement' => $items,
            ];
        }

        Chroma_Schema_Registry::register($schema, ['source' => 'geographic-service-area']);
    }

    public function add_admin_page() {
        add_submenu_page(
            'chroma-seo-dashboard',
            'Virtual Page SEO',
            'Virtual Page SEO',
            'manage_options',
            'chroma-virtual-page-seo',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!class_exists('Chroma_Virtual_Page_SEO_Data')) {
            echo '<div class="wrap"><h1>Virtual Page SEO</h1><p>Virtual page SEO storage is not available.</p></div>';
            return;
        }

        if (isset($_POST['chroma_virtual_page_seo_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chroma_virtual_page_seo_nonce'])), 'chroma_virtual_page_seo_save')) {
            $type = sanitize_key($_POST['area_type'] ?? '');
            $area_name = $type === 'zip'
                ? preg_replace('/[^0-9]/', '', (string) ($_POST['area_name'] ?? ''))
                : sanitize_title($_POST['area_name'] ?? '');

            if (in_array($type, ['county', 'zip'], true) && $area_name !== '') {
                Chroma_Virtual_Page_SEO_Data::save_service_area($type, $area_name, [
                    'seo_title' => sanitize_text_field($_POST['seo_title'] ?? ''),
                    'meta_description' => sanitize_textarea_field($_POST['meta_description'] ?? ''),
                    'seo_title_es' => sanitize_text_field($_POST['seo_title_es'] ?? ''),
                    'meta_description_es' => sanitize_textarea_field($_POST['meta_description_es'] ?? ''),
                    'robots' => sanitize_text_field($_POST['robots'] ?? ''),
                ]);
                echo '<div class="notice notice-success is-dismissible"><p>Virtual page SEO saved.</p></div>';
            }
        }

        $items = Chroma_Virtual_Page_SEO_Data::service_area_candidates();
        ?>
        <div class="wrap">
            <h1>Virtual Page SEO</h1>
            <p>Edit SEO title and meta description overrides for county and ZIP virtual pages.</p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 180px;">Virtual Page</th>
                        <th>SEO Overrides</th>
                        <th style="width: 90px;">Preview</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="3">No county or ZIP virtual pages were found from published locations.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <?php $data = $item['data']; ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($item['label']); ?></strong><br>
                                <code><?php echo esc_html($item['type'] . ':' . $item['area_name']); ?></code>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field('chroma_virtual_page_seo_save', 'chroma_virtual_page_seo_nonce'); ?>
                                    <input type="hidden" name="area_type" value="<?php echo esc_attr($item['type']); ?>">
                                    <input type="hidden" name="area_name" value="<?php echo esc_attr($item['area_name']); ?>">
                                    <p><input type="text" name="seo_title" class="large-text" maxlength="70" placeholder="SEO title" value="<?php echo esc_attr($data['seo_title']); ?>"></p>
                                    <p><textarea name="meta_description" class="large-text" rows="2" maxlength="180" placeholder="Meta description"><?php echo esc_textarea($data['meta_description']); ?></textarea></p>
                                    <p><input type="text" name="seo_title_es" class="large-text" maxlength="70" placeholder="SEO title (Spanish)" value="<?php echo esc_attr($data['seo_title_es']); ?>"></p>
                                    <p><textarea name="meta_description_es" class="large-text" rows="2" maxlength="180" placeholder="Meta description (Spanish)"><?php echo esc_textarea($data['meta_description_es']); ?></textarea></p>
                                    <p>
                                        <select name="robots">
                                            <option value="" <?php selected($data['robots'], ''); ?>>Default indexable</option>
                                            <option value="index,follow" <?php selected($data['robots'], 'index,follow'); ?>>index,follow</option>
                                            <option value="noindex,follow" <?php selected($data['robots'], 'noindex,follow'); ?>>noindex,follow</option>
                                            <option value="noindex,nofollow" <?php selected($data['robots'], 'noindex,nofollow'); ?>>noindex,nofollow</option>
                                        </select>
                                        <button type="submit" class="button button-primary">Save</button>
                                    </p>
                                </form>
                            </td>
                            <td><a class="button button-small" href="<?php echo esc_url($item['url']); ?>" target="_blank">Preview</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Keep service-area routes from inheriting front-page or singular query flags.
     */
    private function prepare_virtual_page_query_state() {
        global $post, $wp_query;

        if ($wp_query instanceof WP_Query) {
            $wp_query->is_page = false;
            $wp_query->is_single = false;
            $wp_query->is_singular = false;
            $wp_query->is_home = false;
            $wp_query->is_front_page = false;
            $wp_query->is_archive = false;
            $wp_query->is_search = false;
            $wp_query->is_feed = false;
            $wp_query->is_404 = false;
            $wp_query->queried_object = null;
            $wp_query->queried_object_id = 0;
            $wp_query->post = null;
            $wp_query->posts = [];
            $wp_query->post_count = 0;
            $wp_query->found_posts = 0;
        }

        $post = null;
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



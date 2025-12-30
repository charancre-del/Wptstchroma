<?php
/**
 * Program + City Combo Page Generator
 * Auto-generates landing pages like /pre-k-in-cumming-ga/
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Combo_Page_Generator
{
    const REWRITE_TAG = 'chroma_combo';
    
    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_combo_page']);
        add_filter('wpseo_sitemap_index', [$this, 'add_to_sitemap']);
        add_action('admin_menu', [$this, 'add_admin_page']);
    }
    
    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        // Pattern: /program-in-city-state/
        add_rewrite_rule(
            '^([a-z0-9-]+)-in-([a-z-]+)-([a-z]{2})/?$',
            'index.php?' . self::REWRITE_TAG . '=1&combo_program=$matches[1]&combo_city=$matches[2]&combo_state=$matches[3]',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = self::REWRITE_TAG;
        $vars[] = 'combo_program';
        $vars[] = 'combo_city';
        $vars[] = 'combo_state';
        return $vars;
    }
    
    /**
     * Handle combo page request
     */
    public function handle_combo_page() {
        if (!get_query_var(self::REWRITE_TAG)) {
            return;
        }
        
        $program_slug = sanitize_title(get_query_var('combo_program'));
        $city_slug = sanitize_title(get_query_var('combo_city'));
        $state = strtoupper(sanitize_text_field(get_query_var('combo_state')));
        
        // Find program
        $program = get_page_by_path($program_slug, OBJECT, 'program');
        if (!$program) {
            // Try matching by title
            $programs = get_posts([
                'post_type' => 'program',
                'name' => $program_slug,
                'posts_per_page' => 1
            ]);
            $program = $programs[0] ?? null;
        }
        
        if (!$program) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }
        
        // Find nearest location in this city/state
        $location = $this->find_location_in_city($city_slug, $state);
        
        // Render the combo page
        $this->render_combo_page($program, $city_slug, $state, $location);
        exit;
    }
    
    /**
     * Find location in or near a city
     */
    private function find_location_in_city($city_slug, $state) {
        $city_normalized = str_replace('-', ' ', $city_slug);
        
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        // Exact match first
        foreach ($locations as $loc) {
            $loc_city = get_post_meta($loc->ID, 'location_city', true);
            $loc_state = get_post_meta($loc->ID, 'location_state', true);
            
            if (strtolower($loc_city) === $city_normalized && strtoupper($loc_state) === $state) {
                return $loc;
            }
        }
        
        // Return first location in state
        foreach ($locations as $loc) {
            $loc_state = get_post_meta($loc->ID, 'location_state', true);
            if (strtoupper($loc_state) === $state) {
                return $loc;
            }
        }
        
        // Return any location
        return $locations[0] ?? null;
    }
    
    /**
     * Render the combo page
     */
    private function render_combo_page($program, $city_slug, $state, $location) {
        $city_name = ucwords(str_replace('-', ' ', $city_slug));
        $page_title = $program->post_title . ' in ' . $city_name . ', ' . $state;
        
        // Get program details
        $age_range = get_post_meta($program->ID, 'program_age_range', true);
        $description = get_the_excerpt($program) ?: wp_trim_words($program->post_content, 50);
        
        // Get location details if found
        $loc_name = $location ? $location->post_title : '';
        $loc_address = $location ? get_post_meta($location->ID, 'location_address', true) : '';
        $loc_phone = $location ? get_post_meta($location->ID, 'location_phone', true) : '';
        $loc_url = $location ? get_permalink($location) : '';
        
        get_header();
        ?>
        <main class="combo-page">
            <article class="combo-content">
                <header class="combo-header">
                    <h1><?php echo esc_html($page_title); ?></h1>
                    <?php if ($age_range): ?>
                        <p class="age-badge">Ages: <?php echo esc_html($age_range); ?></p>
                    <?php endif; ?>
                </header>
                
                <section class="combo-intro">
                    <p>Looking for quality <strong><?php echo esc_html($program->post_title); ?></strong> 
                    in <strong><?php echo esc_html($city_name); ?>, <?php echo esc_html($state); ?></strong>? 
                    Chroma Early Learning provides exceptional early childhood education programs designed 
                    to nurture your child's development in a safe, engaging environment.</p>
                </section>
                
                <section class="combo-program-details">
                    <h2>About Our <?php echo esc_html($program->post_title); ?> Program</h2>
                    <?php echo wp_kses_post($description); ?>
                    <a href="<?php echo get_permalink($program); ?>" class="btn-learn-more">
                        Learn More About This Program →
                    </a>
                </section>
                
                <?php if ($location): ?>
                <section class="combo-location">
                    <h2>Nearest Location in <?php echo esc_html($city_name); ?></h2>
                    <div class="location-card">
                        <?php if (has_post_thumbnail($location)): ?>
                            <?php echo get_the_post_thumbnail($location, 'medium'); ?>
                        <?php endif; ?>
                        <div class="location-info">
                            <h3><?php echo esc_html($loc_name); ?></h3>
                            <?php if ($loc_address): ?>
                                <p class="address"><?php echo esc_html($loc_address); ?></p>
                            <?php endif; ?>
                            <?php if ($loc_phone): ?>
                                <p class="phone"><a href="tel:<?php echo esc_attr($loc_phone); ?>"><?php echo esc_html($loc_phone); ?></a></p>
                            <?php endif; ?>
                            <div class="cta-buttons">
                                <a href="<?php echo esc_url($loc_url); ?>" class="btn-primary">View Location</a>
                                <a href="<?php echo esc_url($loc_url); ?>#schedule-tour" class="btn-secondary">Schedule a Tour</a>
                            </div>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
                
                <section class="combo-faq">
                    <h2>Frequently Asked Questions</h2>
                    <div class="faq-item">
                        <h3>What ages does the <?php echo esc_html($program->post_title); ?> program serve?</h3>
                        <p><?php echo $age_range ? 'Our program serves children ages ' . esc_html($age_range) . '.' : 'Contact us for age requirements.'; ?></p>
                    </div>
                    <div class="faq-item">
                        <h3>Is there a location in <?php echo esc_html($city_name); ?>?</h3>
                        <p><?php echo $location ? 'Yes! Our ' . esc_html($loc_name) . ' location serves the ' . esc_html($city_name) . ' area.' : 'We serve the ' . esc_html($city_name) . ' area from our nearby locations.'; ?></p>
                    </div>
                </section>
            </article>
        </main>
        
        <style>
            .combo-page { max-width: 900px; margin: 0 auto; padding: 40px 20px; }
            .combo-header { text-align: center; margin-bottom: 40px; }
            .combo-header h1 { font-size: 36px; margin-bottom: 10px; }
            .age-badge { display: inline-block; background: #e8f4ff; color: #0066cc; padding: 5px 15px; border-radius: 20px; }
            .combo-intro { font-size: 18px; line-height: 1.8; margin-bottom: 40px; }
            .combo-program-details, .combo-location, .combo-faq { margin-bottom: 40px; }
            .combo-program-details h2, .combo-location h2, .combo-faq h2 { font-size: 24px; margin-bottom: 20px; }
            .btn-learn-more { display: inline-block; margin-top: 15px; color: #0066cc; }
            .location-card { display: flex; gap: 20px; background: #f9f9f9; padding: 20px; border-radius: 12px; }
            .location-card img { width: 200px; height: 150px; object-fit: cover; border-radius: 8px; }
            .location-info h3 { margin: 0 0 10px; }
            .location-info .address, .location-info .phone { margin: 5px 0; color: #666; }
            .cta-buttons { display: flex; gap: 10px; margin-top: 15px; }
            .btn-primary, .btn-secondary { padding: 10px 20px; border-radius: 6px; text-decoration: none; }
            .btn-primary { background: #0066cc; color: #fff; }
            .btn-secondary { background: #f0f0f0; color: #333; }
            .faq-item { margin-bottom: 20px; padding: 20px; background: #f5f5f5; border-radius: 8px; }
            .faq-item h3 { margin: 0 0 10px; font-size: 16px; }
            .faq-item p { margin: 0; }
        </style>
        
        <?php
        // Add schema
        $this->output_schema($program, $city_name, $state, $location);
        
        get_footer();
    }
    
    /**
     * Output schema markup
     */
    private function output_schema($program, $city_name, $state, $location) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $program->post_title . ' in ' . $city_name . ', ' . $state,
            'description' => get_the_excerpt($program) ?: wp_trim_words($program->post_content, 50),
            'provider' => [
                '@type' => 'Organization',
                'name' => 'Chroma Early Learning'
            ]
        ];
        
        if ($location) {
            $schema['provider']['address'] = [
                '@type' => 'PostalAddress',
                'addressLocality' => get_post_meta($location->ID, 'location_city', true),
                'addressRegion' => get_post_meta($location->ID, 'location_state', true)
            ];
        }
        
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>';
    }
    
    /**
     * Get all combo pages for sitemap
     */
    public static function get_all_combos() {
        $combos = [];
        
        $programs = get_posts(['post_type' => 'program', 'posts_per_page' => -1]);
        $cities = self::get_all_cities();
        
        foreach ($programs as $prog) {
            foreach ($cities as $city) {
                $combos[] = [
                    'program' => $prog,
                    'city' => $city['city'],
                    'state' => $city['state'],
                    'url' => home_url('/' . $prog->post_name . '-in-' . sanitize_title($city['city']) . '-' . strtolower($city['state']) . '/')
                ];
            }
        }
        
        return $combos;
    }
    
    /**
     * Get all cities from hybrid sources
     */
    public static function get_all_cities() {
        $cities = [];
        
        // Source 1: From location posts
        $locations = get_posts(['post_type' => 'location', 'posts_per_page' => -1]);
        foreach ($locations as $loc) {
            $city = get_post_meta($loc->ID, 'location_city', true);
            $state = get_post_meta($loc->ID, 'location_state', true);
            if ($city && $state) {
                $key = sanitize_title($city . '-' . $state);
                $cities[$key] = ['city' => $city, 'state' => $state, 'location_id' => $loc->ID];
            }
        }
        
        // Source 2: Manual additions
        $manual = get_option('chroma_seo_manual_cities', []);
        foreach ($manual as $m) {
            if (!empty($m['city']) && !empty($m['state'])) {
                $key = sanitize_title($m['city'] . '-' . $m['state']);
                if (!isset($cities[$key])) {
                    $cities[$key] = ['city' => $m['city'], 'state' => $m['state'], 'location_id' => null];
                }
            }
        }
        
        return array_values($cities);
    }
    
    /**
     * Add admin page
     */
    public function add_admin_page() {
        add_submenu_page(
            'chroma-llm-settings',
            'Auto Pages',
            'Auto Pages',
            'manage_options',
            'chroma-auto-pages',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $combos = self::get_all_combos();
        ?>
        <div class="wrap">
            <h1>Auto-Generated Pages</h1>
            
            <h2>Program + City Combo Pages (<?php echo count($combos); ?>)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($combos, 0, 50) as $combo): ?>
                    <tr>
                        <td><?php echo esc_html($combo['program']->post_title . ' in ' . $combo['city'] . ', ' . $combo['state']); ?></td>
                        <td><a href="<?php echo esc_url($combo['url']); ?>" target="_blank"><?php echo esc_html($combo['url']); ?></a></td>
                        <td><a href="<?php echo esc_url($combo['url']); ?>" target="_blank">Preview</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (count($combos) > 50): ?>
            <p><em>Showing 50 of <?php echo count($combos); ?> pages</em></p>
            <?php endif; ?>
            
            <h2>Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('chroma_auto_pages'); ?>
                <table class="form-table">
                    <tr>
                        <th>Manual Cities</th>
                        <td>
                            <textarea name="chroma_seo_manual_cities_raw" rows="5" class="large-text"><?php 
                                $manual = get_option('chroma_seo_manual_cities', []);
                                foreach ($manual as $m) {
                                    echo esc_html($m['city'] . ', ' . $m['state']) . "\n";
                                }
                            ?></textarea>
                            <p class="description">One city per line: City, ST</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Cities'); ?>
            </form>
        </div>
        <?php
    }
}

// Save manual cities
add_action('admin_init', function() {
    register_setting('chroma_auto_pages', 'chroma_seo_manual_cities_raw', function($raw) {
        $cities = [];
        $lines = explode("\n", $raw);
        foreach ($lines as $line) {
            $parts = array_map('trim', explode(',', $line));
            if (count($parts) >= 2) {
                $cities[] = ['city' => $parts[0], 'state' => strtoupper($parts[1])];
            }
        }
        update_option('chroma_seo_manual_cities', $cities);
        return $raw;
    });
});

new Chroma_Combo_Page_Generator();

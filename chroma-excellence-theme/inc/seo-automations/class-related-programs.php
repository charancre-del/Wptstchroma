<?php
/**
 * Related Programs - Auto-link programs to locations
 * Shows program ↔ location relationships
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Related_Programs
{
    public function __construct() {
        add_filter('the_content', [$this, 'append_related_content'], 21);
    }
    
    /**
     * Append related programs or locations after content
     */
    public function append_related_content($content) {
        if (!get_option('chroma_seo_link_programs_locations', true)) {
            return $content;
        }
        
        $post_type = get_post_type();
        
        if ($post_type === 'location') {
            return $content . $this->get_programs_at_location(get_the_ID());
        }
        
        if ($post_type === 'program') {
            return $content . $this->get_locations_with_program(get_the_ID());
        }
        
        return $content;
    }
    
    /**
     * Get programs offered at a location
     */
    private function get_programs_at_location($location_id) {
        // Try ACF relationship field
        $programs = [];
        
        if (function_exists('get_field')) {
            $programs = get_field('location_programs', $location_id);
        }
        
        // Fallback: get all programs
        if (empty($programs)) {
            $programs = get_posts([
                'post_type' => 'program',
                'posts_per_page' => 6,
                'post_status' => 'publish'
            ]);
        }
        
        if (empty($programs)) {
            return '';
        }
        
        ob_start();
        ?>
        <section class="chroma-related-programs">
            <h2>Programs at This Location</h2>
            <div class="programs-grid">
                <?php foreach ($programs as $prog): 
                    $prog_id = is_object($prog) ? $prog->ID : $prog;
                    $prog_obj = is_object($prog) ? $prog : get_post($prog);
                    if (!$prog_obj) continue;
                    
                    $age_range = get_post_meta($prog_id, 'program_age_range', true);
                ?>
                <a href="<?php echo get_permalink($prog_id); ?>" class="program-card">
                    <?php if (has_post_thumbnail($prog_id)): ?>
                        <?php echo get_the_post_thumbnail($prog_id, 'thumbnail'); ?>
                    <?php endif; ?>
                    <div class="program-info">
                        <h3><?php echo esc_html($prog_obj->post_title); ?></h3>
                        <?php if ($age_range): ?>
                            <span class="age-range">Ages: <?php echo esc_html($age_range); ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <style>
            .chroma-related-programs { margin: 40px 0; }
            .chroma-related-programs h2 { margin-bottom: 20px; }
            .programs-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            .program-card {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px;
                background: #f5f5f5;
                border-radius: 8px;
                text-decoration: none;
                color: inherit;
                transition: background 0.2s;
            }
            .program-card:hover { background: #e8e8e8; }
            .program-card img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; }
            .program-card h3 { margin: 0; font-size: 15px; }
            .program-card .age-range { font-size: 12px; color: #666; }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get locations offering a program
     */
    private function get_locations_with_program($program_id) {
        // Query locations that have this program in their relationship
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'location_programs',
                    'value' => $program_id,
                    'compare' => 'LIKE'
                ]
            ]
        ]);
        
        // Fallback: all locations
        if (empty($locations)) {
            $locations = get_posts([
                'post_type' => 'location',
                'posts_per_page' => 6,
                'post_status' => 'publish'
            ]);
        }
        
        if (empty($locations)) {
            return '';
        }
        
        ob_start();
        ?>
        <section class="chroma-locations-with-program">
            <h2>Locations Offering This Program</h2>
            <div class="locations-list">
                <?php foreach ($locations as $loc): 
                    $city = get_post_meta($loc->ID, 'location_city', true);
                    $phone = get_post_meta($loc->ID, 'location_phone', true);
                ?>
                <a href="<?php echo get_permalink($loc); ?>" class="location-item">
                    <h3><?php echo esc_html($loc->post_title); ?></h3>
                    <?php if ($city): ?>
                        <span class="city"><?php echo esc_html($city); ?></span>
                    <?php endif; ?>
                    <?php if ($phone): ?>
                        <span class="phone"><?php echo esc_html($phone); ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <style>
            .chroma-locations-with-program { margin: 40px 0; }
            .chroma-locations-with-program h2 { margin-bottom: 20px; }
            .locations-list {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 15px;
            }
            .location-item {
                padding: 15px;
                background: #f5f5f5;
                border-radius: 8px;
                text-decoration: none;
                color: inherit;
            }
            .location-item:hover { background: #e8e8e8; }
            .location-item h3 { margin: 0 0 5px; font-size: 16px; }
            .location-item .city { display: block; color: #666; font-size: 14px; }
            .location-item .phone { display: block; color: #0073aa; font-size: 14px; margin-top: 5px; }
        </style>
        <?php
        return ob_get_clean();
    }
}

new Chroma_Related_Programs();

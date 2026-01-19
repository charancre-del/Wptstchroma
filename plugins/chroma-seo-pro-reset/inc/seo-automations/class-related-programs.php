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
        if (!get_option('chroma_seo_link_programs_locations', true) || doing_filter('get_the_excerpt')) {
            return $content;
        }
        
        $post_type = get_post_type();

        // Only append on single pages (prevent leakage into excerpts/loops)
        if (!is_singular($post_type)) {
            return $content;
        }
        
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
        $programs = [];
        if (function_exists('get_field')) {
            $programs = get_field('location_programs', $location_id);
        }
        
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
        
        $color_map = array(
            'infant' => array('bg' => 'chroma-redLight', 'text' => 'chroma-red', 'border' => 'chroma-red/30'),
            'toddler' => array('bg' => 'chroma-blueLight', 'text' => 'chroma-blue', 'border' => 'chroma-blue/30'),
            'preschool' => array('bg' => 'chroma-yellowLight', 'text' => 'chroma-yellow', 'border' => 'chroma-yellow/30'),
            'prek' => array('bg' => 'chroma-greenLight', 'text' => 'chroma-green', 'border' => 'chroma-green/30'),
            'afterschool' => array('bg' => 'chroma-blueLight', 'text' => 'chroma-blue', 'border' => 'chroma-blue/30'),
        );

        ob_start();
        ?>
        <section class="chroma-related-programs py-12 border-t border-brand-ink/5 mt-16">
            <h2 class="font-serif text-3xl font-bold text-brand-ink mb-8"><?php _e('Programs at This Location', 'chroma-excellence'); ?></h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($programs as $prog): 
                    $prog_id = is_object($prog) ? $prog->ID : $prog;
                    $prog_obj = is_object($prog) ? $prog : get_post($prog);
                    if (!$prog_obj) continue;
                    
                    $age_range = get_post_meta($prog_id, 'program_age_range', true);
                    $slug = $prog_obj->post_name;
                    $colors = $color_map[$slug] ?? $color_map['toddler'];
                ?>
                <div class="program-card bg-white rounded-3xl shadow-card border border-brand-ink/5 hover:border-<?php echo esc_attr($colors['border']); ?> transition group overflow-hidden flex items-center p-4 relative">
                    <a href="<?php echo get_permalink($prog_id); ?>" class="absolute inset-0 z-10" aria-label="<?php echo esc_attr($prog_obj->post_title); ?>"></a>
                    
                    <?php if (has_post_thumbnail($prog_id)): ?>
                        <div class="w-16 h-16 rounded-xl overflow-hidden shrink-0 mr-4">
                            <?php echo get_the_post_thumbnail($prog_id, 'thumbnail', ['class' => 'w-full h-full object-cover group-hover:scale-110 transition-transform']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="program-info">
                        <h3 class="font-serif text-lg font-bold text-brand-ink mb-1"><?php echo esc_html($prog_obj->post_title); ?></h3>
                        <?php if ($age_range): ?>
                            <span class="bg-<?php echo esc_attr($colors['bg']); ?> text-<?php echo esc_attr($colors['text']); ?> px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wide">
                                Ages: <?php echo esc_html($age_range); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get locations offering a program
     */
    private function get_locations_with_program($program_id) {
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'location_programs',
                    'value' => '(^|;)i:' . intval($program_id) . ';',
                    'compare' => 'REGEXP'
                ]
            ]
        ]);
        
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
        <section class="chroma-locations-with-program py-16 border-t border-brand-ink/5 bg-brand-cream/30 mt-16">
            <div class="max-w-7xl mx-auto px-4 lg:px-6">
                <h2 class="font-serif text-3xl font-bold text-brand-ink mb-10"><?php _e('Locations Offering This Program', 'chroma-excellence'); ?></h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($locations as $loc): 
                        $location_id = $loc->ID;
                        $city = get_post_meta($location_id, 'location_city', true);
                        $phone = get_post_meta($location_id, 'location_phone', true);
                        
                        // Get region colors
                        $regions = wp_get_post_terms($location_id, 'location_region');
                        $region_term = !empty($regions) && !is_wp_error($regions) ? $regions[0] : null;
                        $colors = $region_term ? chroma_get_region_color_from_term($region_term->term_id) : array(
                            'bg' => 'chroma-blueLight', 'text' => 'chroma-blue', 'border' => 'chroma-blue'
                        );
                    ?>
                    <div class="location-card group">
                        <div class="bg-white rounded-[2.5rem] p-8 shadow-card border border-brand-ink/5 hover:border-<?php echo esc_attr($colors['border']); ?>/30 transition-all hover:-translate-y-1 h-full flex flex-col relative overflow-hidden">
                            
                            <div class="flex justify-between items-start mb-4">
                                <span class="bg-<?php echo esc_attr($colors['bg']); ?> text-<?php echo esc_attr($colors['text']); ?> px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide">
                                    <?php echo $region_term ? esc_html($region_term->name) : 'Metro Atlanta'; ?>
                                </span>
                            </div>

                            <h3 class="font-serif text-2xl font-bold text-brand-ink mb-2 group-hover:text-<?php echo esc_attr($colors['text']); ?> transition-colors">
                                <a href="<?php echo get_permalink($loc); ?>"><?php echo esc_html($loc->post_title); ?></a>
                            </h3>
                            
                            <?php if ($city): ?>
                                <p class="text-sm text-brand-ink/80 mb-6">Serving families in <?php echo esc_html($city); ?></p>
                            <?php endif; ?>

                            <div class="mt-auto grid grid-cols-2 gap-3">
                                <a href="<?php echo get_permalink($loc); ?>" class="flex items-center justify-center py-4 rounded-2xl bg-brand-ink text-white text-[10px] font-bold uppercase tracking-widest hover:bg-chroma-blueDark transition-colors">
                                    <?php _e('View Campus', 'chroma-excellence'); ?>
                                </a>
                                <?php if ($phone): ?>
                                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone)); ?>" class="flex items-center justify-center py-4 rounded-2xl border border-brand-ink/10 text-brand-ink text-[10px] font-bold uppercase tracking-widest hover:bg-brand-cream/50 transition-colors">
                                        <i class="fa-solid fa-phone mr-1.5"></i> <?php _e('Call', 'chroma-excellence'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

}

new Chroma_Related_Programs();



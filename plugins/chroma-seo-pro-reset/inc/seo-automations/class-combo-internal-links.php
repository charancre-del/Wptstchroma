<?php
/**
 * Combo Page Internal Links
 * Automatically injects links to combo pages from related content
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Combo_Internal_Links
{
    public function __construct() {
        // Add combo links to program single pages
        add_action('chroma_after_program_content', [$this, 'render_program_city_links']);
        
        // Add combo links to city pages
        add_action('chroma_after_city_content', [$this, 'render_city_program_links']);
        
        // Fallback: append to content if hooks not available
        add_filter('the_content', [$this, 'append_links_to_content'], 20);
    }
    
    /**
     * Render "Find This Program Near You" section on program pages
     */
    public function render_program_city_links() {
        if (!is_singular('program')) return;
        
        global $post;
        $program = $post;
        
        $cities = Chroma_Combo_Page_Generator::get_all_cities();
        if (empty($cities)) return;
        
        ?>
        <section class="program-city-links py-12 bg-brand-cream">
            <div class="max-w-6xl mx-auto px-4">
                <h2 class="text-2xl font-serif font-bold text-brand-ink mb-6">
                    Find <?php echo esc_html($program->post_title); ?> Near You
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <?php foreach (array_slice($cities, 0, 12) as $city): 
                        $url = home_url('/' . $program->post_name . '-in-' . sanitize_title($city['city']) . '-' . strtolower($city['state']) . '/');
                    ?>
                    <a href="<?php echo esc_url($url); ?>" 
                       class="block p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition border border-brand-ink/5">
                        <span class="text-sm font-semibold text-brand-ink">
                            <?php echo esc_html($city['city']); ?>, <?php echo esc_html($city['state']); ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }
    
    /**
     * Render "Programs Available in [City]" section on city pages
     */
    public function render_city_program_links() {
        if (!is_singular('city')) return;
        
        global $post;
        $city_page = $post;
        
        $city_name = get_post_meta($city_page->ID, 'city_name', true) ?: $city_page->post_title;
        $state = get_post_meta($city_page->ID, 'city_state', true) ?: 'GA';
        
        $programs = get_posts(['post_type' => 'program', 'posts_per_page' => -1]);
        if (empty($programs)) return;
        
        ?>
        <section class="city-program-links py-12 bg-white">
            <div class="max-w-6xl mx-auto px-4">
                <h2 class="text-2xl font-serif font-bold text-brand-ink mb-6">
                    Programs in <?php echo esc_html($city_name); ?>
                </h2>
                <div class="grid md:grid-cols-3 gap-4">
                    <?php foreach ($programs as $program): 
                        $url = home_url('/' . $program->post_name . '-in-' . sanitize_title($city_name) . '-' . strtolower($state) . '/');
                        $image = get_the_post_thumbnail_url($program, 'medium') ?: '';
                    ?>
                    <a href="<?php echo esc_url($url); ?>" 
                       class="block bg-brand-cream rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition group">
                        <?php if ($image): ?>
                        <div class="h-32 overflow-hidden">
                            <img src="<?php echo esc_url($image); ?>" 
                                 alt="<?php echo esc_attr($program->post_title); ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                        </div>
                        <?php endif; ?>
                        <div class="p-4">
                            <h3 class="font-bold text-brand-ink"><?php echo esc_html($program->post_title); ?></h3>
                            <p class="text-sm text-brand-ink/60 mt-1">in <?php echo esc_html($city_name); ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }
    
    /**
     * Fallback: Append links to content if custom hooks not used
     */
    public function append_links_to_content($content) {
        if (!is_singular()) return $content;
        
        global $post;
        
        // For program pages
        if ($post->post_type === 'program') {
            $cities = Chroma_Combo_Page_Generator::get_all_cities();
            if (!empty($cities)) {
                $links = [];
                foreach (array_slice($cities, 0, 8) as $city) {
                    $url = home_url('/' . $post->post_name . '-in-' . sanitize_title($city['city']) . '-' . strtolower($city['state']) . '/');
                    $links[] = '<a href="' . esc_url($url) . '">' . esc_html($city['city']) . '</a>';
                }
                
                $content .= '<div class="chroma-combo-links" style="margin-top: 2rem; padding: 1.5rem; background: #f8f8f8; border-radius: 8px;">';
                $content .= '<h3 style="margin: 0 0 1rem;">Find ' . esc_html($post->post_title) . ' Near You</h3>';
                $content .= '<p>' . implode(' &bull; ', $links) . '</p>';
                $content .= '</div>';
            }
        }
        
        // For city pages
        if ($post->post_type === 'city') {
            $city_name = get_post_meta($post->ID, 'city_name', true) ?: $post->post_title;
            $state = get_post_meta($post->ID, 'city_state', true) ?: 'GA';
            
            $programs = get_posts(['post_type' => 'program', 'posts_per_page' => -1]);
            if (!empty($programs)) {
                $links = [];
                foreach ($programs as $program) {
                    $url = home_url('/' . $program->post_name . '-in-' . sanitize_title($city_name) . '-' . strtolower($state) . '/');
                    $links[] = '<a href="' . esc_url($url) . '">' . esc_html($program->post_title) . '</a>';
                }
                
                $content .= '<div class="chroma-combo-links" style="margin-top: 2rem; padding: 1.5rem; background: #f8f8f8; border-radius: 8px;">';
                $content .= '<h3 style="margin: 0 0 1rem;">Programs in ' . esc_html($city_name) . '</h3>';
                $content .= '<p>' . implode(' &bull; ', $links) . '</p>';
                $content .= '</div>';
            }
        }
        
        return $content;
    }
}

new Chroma_Combo_Internal_Links();



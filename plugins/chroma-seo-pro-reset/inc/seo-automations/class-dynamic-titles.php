<?php
/**
 * Dynamic Title Patterns
 * Auto-generate SEO-optimized titles based on patterns
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Dynamic_Titles
{
    private $patterns = [];
    
    public function __construct() {
        add_filter('document_title_parts', [$this, 'filter_title_parts'], 20);
        add_filter('pre_get_document_title', [$this, 'filter_title'], 20);
        add_action('admin_menu', [$this, 'add_settings_page'], 20);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Get patterns
     */
    private function get_patterns() {
        if (!empty($this->patterns)) {
            return $this->patterns;
        }
        
        $this->patterns = get_option('chroma_seo_title_patterns', $this->get_default_patterns());
        
        return $this->patterns;
    }
    
    /**
     * Default patterns
     */
    private function get_default_patterns() {
        return [
            'location' => '{title} | Daycare in {city}, {state} | Chroma',
            'program' => '{title} Program | Ages {age_range} | Chroma',
            'post' => '{title} | Parenting Tips | Chroma Blog',
            'page' => '{title} | Chroma Early Learning',
            'team_member' => '{title} | Meet Our Team | Chroma',
            'archive_location' => 'Our Daycare Locations | Chroma Early Learning',
            'archive_program' => 'Early Learning Programs | Chroma',
            'home' => 'Chroma Early Learning | Quality Childcare & Preschool',
            'search' => 'Search Results for "{query}" | Chroma'
        ];
    }
    
    /**
     * Filter document title
     */
    public function filter_title($title) {
        if (!get_option('chroma_seo_enable_dynamic_titles', true)) {
            return $title;
        }
        
        $patterns = $this->get_patterns();
        $new_title = '';
        
        if (is_singular('location')) {
            $new_title = $this->apply_pattern($patterns['location'] ?? '', get_the_ID());
        } elseif (is_singular('program')) {
            $new_title = $this->apply_pattern($patterns['program'] ?? '', get_the_ID());
        } elseif (is_singular('team_member')) {
            $new_title = $this->apply_pattern($patterns['team_member'] ?? '', get_the_ID());
        } elseif (is_singular('post')) {
            $new_title = $this->apply_pattern($patterns['post'] ?? '', get_the_ID());
        } elseif (is_page()) {
            $new_title = $this->apply_pattern($patterns['page'] ?? '', get_the_ID());
        } elseif (is_post_type_archive('location')) {
            $new_title = $patterns['archive_location'] ?? '';
        } elseif (is_post_type_archive('program')) {
            $new_title = $patterns['archive_program'] ?? '';
        } elseif (is_front_page()) {
            $new_title = $patterns['home'] ?? '';
        } elseif (is_search()) {
            $new_title = str_replace('{query}', get_search_query(), $patterns['search'] ?? '');
        }
        
        return $new_title ?: $title;
    }
    
    /**
     * Filter title parts (for separator handling)
     */
    public function filter_title_parts($parts) {
        // Let pre_get_document_title handle the full title
        return $parts;
    }
    
    /**
     * Apply pattern with placeholders
     */
    private function apply_pattern($pattern, $post_id) {
        if (empty($pattern)) {
            return '';
        }
        
        $placeholders = $this->get_placeholders($post_id);
        
        foreach ($placeholders as $key => $value) {
            $pattern = str_replace('{' . $key . '}', $value, $pattern);
        }
        
        // Clean up any unused placeholders
        $pattern = preg_replace('/\{[^}]+\}/', '', $pattern);
        
        return trim($pattern);
    }
    
    /**
     * Get placeholder values for a post
     */
    private function get_placeholders($post_id) {
        $post = get_post($post_id);
        
        $placeholders = [
            'title' => $post->post_title,
            'site_name' => get_bloginfo('name'),
            'site_tagline' => get_bloginfo('description')
        ];
        
        // Location-specific
        if ($post->post_type === 'location') {
            $placeholders['city'] = get_post_meta($post_id, 'location_city', true) ?: 'Your City';
            $placeholders['state'] = get_post_meta($post_id, 'location_state', true) ?: '';
            $placeholders['phone'] = get_post_meta($post_id, 'location_phone', true) ?: '';
            $placeholders['zip'] = get_post_meta($post_id, 'location_zip', true) ?: '';
        }
        
        // Program-specific
        if ($post->post_type === 'program') {
            $placeholders['age_range'] = get_post_meta($post_id, 'program_age_range', true) ?: 'All Ages';
        }
        
        // Author
        $author = get_the_author_meta('display_name', $post->post_author);
        $placeholders['author'] = $author ?: 'Chroma Team';
        
        // Date
        $placeholders['year'] = get_the_date('Y', $post_id);
        $placeholders['month'] = get_the_date('F', $post_id);
        
        // Category (for posts)
        $categories = get_the_category($post_id);
        $placeholders['category'] = $categories[0]->name ?? '';
        
        return $placeholders;
    }
    
    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'chroma-seo-dashboard',
            'Title Patterns',
            'Title Patterns',
            'manage_options',
            'chroma-title-patterns',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('chroma_title_patterns', 'chroma_seo_enable_dynamic_titles');
        register_setting('chroma_title_patterns', 'chroma_seo_title_patterns');
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $enabled = get_option('chroma_seo_enable_dynamic_titles', true);
        $patterns = $this->get_patterns();
        ?>
        <div class="wrap">
            <h1>Dynamic Title Patterns</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('chroma_title_patterns'); ?>
                
                <table class="form-table">
                    <tr>
                        <th>Enable Dynamic Titles</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_enable_dynamic_titles" 
                                    value="1" <?php checked($enabled); ?>>
                                Use pattern-based title generation
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h2>Title Patterns</h2>
                <p>Available placeholders: <code>{title}</code> <code>{city}</code> <code>{state}</code> <code>{age_range}</code> <code>{author}</code> <code>{category}</code> <code>{year}</code></p>
                
                <table class="form-table">
                    <?php foreach ($patterns as $key => $pattern): ?>
                    <tr>
                        <th><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></th>
                        <td>
                            <input type="text" name="chroma_seo_title_patterns[<?php echo esc_attr($key); ?>]" 
                                value="<?php echo esc_attr($pattern); ?>" class="large-text">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2>Preview</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Example Title</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Location</td>
                        <td><?php echo esc_html(str_replace(['{title}', '{city}', '{state}'], ['Cumming Location', 'Cumming', 'GA'], $patterns['location'])); ?></td>
                    </tr>
                    <tr>
                        <td>Program</td>
                        <td><?php echo esc_html(str_replace(['{title}', '{age_range}'], ['Pre-K', '4-5 years'], $patterns['program'])); ?></td>
                    </tr>
                    <tr>
                        <td>Blog Post</td>
                        <td><?php echo esc_html(str_replace('{title}', '10 Tips for First Day at Preschool', $patterns['post'])); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
}

new Chroma_Dynamic_Titles();



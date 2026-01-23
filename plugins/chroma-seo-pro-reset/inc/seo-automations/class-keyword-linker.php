<?php
/**
 * Keyword Linker - Auto-link keywords in content
 * Automatically creates internal links when keywords appear
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Keyword_Linker
{
    private $keywords = [];
    private $linked_this_post = [];
    
    public function __construct() {
        add_filter('the_content', [$this, 'auto_link_keywords'], 15);
        add_action('admin_menu', [$this, 'add_settings_page'], 20);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Get keyword mappings
     */
    private function get_keywords() {
        if (!empty($this->keywords)) {
            return $this->keywords;
        }
        
        $this->keywords = get_option('chroma_seo_keyword_links', []);
        
        // Add default keywords if empty
        if (empty($this->keywords)) {
            $this->keywords = $this->get_auto_keywords();
        }
        
        return $this->keywords;
    }
    
    /**
     * Auto-generate keywords from posts
     */
    private function get_auto_keywords() {
        $keywords = [];
        
        // Programs
        $programs = get_posts(['post_type' => 'program', 'posts_per_page' => -1]);
        foreach ($programs as $prog) {
            $keywords[] = [
                'keyword' => strtolower($prog->post_title),
                'url' => get_permalink($prog),
                'max' => 1
            ];
        }
        
        // Locations
        $locations = get_posts(['post_type' => 'location', 'posts_per_page' => -1]);
        foreach ($locations as $loc) {
            $city = get_post_meta($loc->ID, 'location_city', true);
            if ($city) {
                $keywords[] = [
                    'keyword' => strtolower($city . ' daycare'),
                    'url' => get_permalink($loc),
                    'max' => 1
                ];
                $keywords[] = [
                    'keyword' => strtolower('daycare in ' . $city),
                    'url' => get_permalink($loc),
                    'max' => 1
                ];
            }
        }
        
        return $keywords;
    }
    
    /**
     * Auto-link keywords in content
     */
    public function auto_link_keywords($content) {
        if (!get_option('chroma_seo_enable_keyword_linking', true)) {
            return $content;
        }
        
        // Don't process on single locations/programs (their pages are the targets)
        if (is_singular(['location', 'program'])) {
            return $content;
        }
        
        $keywords = $this->get_keywords();
        
        if (empty($keywords)) {
            return $content;
        }
        
        $this->linked_this_post = [];
        
        // Sort by keyword length (longest first to avoid partial matches)
        usort($keywords, function($a, $b) {
            return strlen($b['keyword']) - strlen($a['keyword']);
        });
        
        foreach ($keywords as $kw) {
            $keyword = $kw['keyword'];
            $url = $kw['url'];
            $max = isset($kw['max']) ? intval($kw['max']) : 1;
            
            // Skip if already linked max times
            if (isset($this->linked_this_post[$keyword]) && $this->linked_this_post[$keyword] >= $max) {
                continue;
            }
            
            // Don't link to current page
            if ($url === get_permalink()) {
                continue;
            }
            
            $content = $this->replace_keyword($content, $keyword, $url, $max);
        }
        
        return $content;
    }
    
    /**
     * Replace keyword with link (case-insensitive, respects existing links)
     */
    private function replace_keyword($content, $keyword, $url, $max) {
        $count = 0;
        
        // Pattern: match keyword not inside a link AND not inside valid HTML tags/attributes
        // 1. (?<!["\'>]) : Not preceded by quotes (partial attribute check)
        // 2. \bKEYWORD\b : Whole word match
        // 3. (?![^<]*<\/a>) : Not inside an existing link
        // 4. (?![^<]*>) : Not inside an HTML tag (to protect src, alt, title, etc)
        $pattern = '/(?<!["\'>])(\b' . preg_quote($keyword, '/') . '\b)(?![^<]*<\/a>)(?![^<]*>)/i';
        
        $content = preg_replace_callback($pattern, function($matches) use ($url, $max, $keyword, &$count) {
            if ($count >= $max) {
                return $matches[0];
            }
            
            $count++;
            $this->linked_this_post[$keyword] = ($this->linked_this_post[$keyword] ?? 0) + 1;
            
            return '<a href="' . esc_url($url) . '" class="chroma-auto-link">' . $matches[1] . '</a>';
        }, $content, $max);
        
        return $content;
    }
    
    /**
     * Add settings page
     */
    public function add_settings_page() {
        add_submenu_page(
            'chroma-seo-dashboard',
            'Keyword Linking',
            'Keyword Linking',
            'manage_options',
            'chroma-keyword-linking',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('chroma_keyword_linking', 'chroma_seo_enable_keyword_linking');
        register_setting('chroma_keyword_linking', 'chroma_seo_keyword_links');
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $enabled = get_option('chroma_seo_enable_keyword_linking', true);
        $keywords = get_option('chroma_seo_keyword_links', []);
        $auto_keywords = $this->get_auto_keywords();
        ?>
        <div class="wrap">
            <h1>Keyword Linking</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('chroma_keyword_linking'); ?>
                
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Linking</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_enable_keyword_linking" 
                                    value="1" <?php checked($enabled); ?>>
                                Automatically link keywords in blog posts
                            </label>
                        </td>
                    </tr>
                </table>
                
                <h2>Auto-Generated Keywords (<?php echo count($auto_keywords); ?>)</h2>
                <p>These are automatically created from your programs and locations:</p>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Keyword</th>
                            <th>Target URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($auto_keywords, 0, 20) as $kw): ?>
                        <tr>
                            <td><?php echo esc_html($kw['keyword']); ?></td>
                            <td><a href="<?php echo esc_url($kw['url']); ?>" target="_blank"><?php echo esc_url($kw['url']); ?></a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($auto_keywords) > 20): ?>
                        <tr>
                            <td colspan="2"><em>... and <?php echo count($auto_keywords) - 20; ?> more</em></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <h2>Custom Keywords</h2>
                <p>Add your own keyword → URL mappings:</p>
                <div id="custom-keywords">
                    <?php foreach ($keywords as $i => $kw): ?>
                    <div class="keyword-row">
                        <input type="text" name="chroma_seo_keyword_links[<?php echo $i; ?>][keyword]" 
                            value="<?php echo esc_attr($kw['keyword']); ?>" placeholder="Keyword">
                        <input type="url" name="chroma_seo_keyword_links[<?php echo $i; ?>][url]" 
                            value="<?php echo esc_url($kw['url']); ?>" placeholder="URL">
                        <input type="number" name="chroma_seo_keyword_links[<?php echo $i; ?>][max]" 
                            value="<?php echo intval($kw['max'] ?? 1); ?>" min="1" max="5" style="width:60px">
                        <button type="button" class="button remove-keyword">×</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button" id="add-keyword">+ Add Keyword</button>
                
                <?php submit_button(); ?>
            </form>
        </div>
        
        <style>
            .keyword-row { display: flex; gap: 10px; margin: 10px 0; }
            .keyword-row input[type="text"] { width: 200px; }
            .keyword-row input[type="url"] { width: 300px; }
        </style>
        
        <script>
        jQuery(function($) {
            var i = <?php echo count($keywords); ?>;
            
            $('#add-keyword').on('click', function() {
                $('#custom-keywords').append(
                    '<div class="keyword-row">' +
                    '<input type="text" name="chroma_seo_keyword_links[' + i + '][keyword]" placeholder="Keyword">' +
                    '<input type="url" name="chroma_seo_keyword_links[' + i + '][url]" placeholder="URL">' +
                    '<input type="number" name="chroma_seo_keyword_links[' + i + '][max]" value="1" min="1" max="5" style="width:60px">' +
                    '<button type="button" class="button remove-keyword">×</button>' +
                    '</div>'
                );
                i++;
            });
            
            $(document).on('click', '.remove-keyword', function() {
                $(this).closest('.keyword-row').remove();
            });
        });
        </script>
        <?php
    }
}

new Chroma_Keyword_Linker();



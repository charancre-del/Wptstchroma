<?php
/**
 * Multilingual Manager
 * Handles URL routing, rewrite rules, and link filtering for Spanish sub-directory structure.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Multilingual_Manager
{
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize hooks
     */
    public function init()
    {
        add_action('init', [$this, 'setup_rewrites']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        
        // URL Filters
        add_filter('home_url', [$this, 'filter_home_url'], 10, 2);
        add_filter('page_link', [$this, 'filter_permalink'], 10, 2);
        add_filter('post_link', [$this, 'filter_permalink'], 10, 2);
        add_filter('post_type_link', [$this, 'filter_permalink'], 10, 2);
        add_filter('term_link', [$this, 'filter_term_link'], 10, 3);
        
        // Add body class for language
        add_filter('body_class', [$this, 'add_body_class']);
        
        // Language Attributes
        add_filter('language_attributes', [$this, 'filter_language_attributes']);

        // Hreflang Tags
        add_action('wp_head', [$this, 'output_hreflang_tags'], 1);
        
        // Frontend Content Swapping (Option A with fallback banner)
        add_filter('the_title', [$this, 'swap_title'], 10, 2);
        add_filter('the_content', [$this, 'swap_content'], 10);
        add_filter('the_excerpt', [$this, 'swap_excerpt'], 10);
        
        // Internal Link Rewriting
        add_filter('the_content', [$this, 'rewrite_content_urls'], 20);
        add_filter('nav_menu_link_attributes', [$this, 'filter_nav_menu_link'], 10, 4);
        
        // Canonical URL Correction
        add_filter('get_canonical_url', [$this, 'filter_canonical_url'], 10, 2);
        
        // Fallback Banner CSS
        add_action('wp_head', [$this, 'output_fallback_css']);
        
        // Browser Language Detection (Auto-redirect to /es/)
        add_action('template_redirect', [$this, 'detect_browser_language']);
    }

    /**
     * Output Hreflang Tags
     */
    public function output_hreflang_tags()
    {
        if (!is_singular() && !is_home() && !is_front_page()) {
            return;
        }

        $post_id = get_the_ID();
        if (!$post_id) return;

        $alternates = self::get_alternates($post_id);
        
        if (empty($alternates['en']) || empty($alternates['es'])) {
            return;
        }

        // x-default should point to the fallback (English)
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($alternates['en']) . '" />' . "\n";
        echo '<link rel="alternate" hreflang="en-US" href="' . esc_url($alternates['en']) . '" />' . "\n";
        echo '<link rel="alternate" hreflang="es-US" href="' . esc_url($alternates['es']) . '" />' . "\n";
    }

    /**
     * Setup rewrite rules
     */
    public function setup_rewrites()
    {
        add_rewrite_tag('%chroma_lang%', '([^&]+)');

        // Home Page: example.com/es/
        add_rewrite_rule('^es/?$', 'index.php?chroma_lang=es', 'top');

        // Custom Post Type Archives
        add_rewrite_rule('^es/locations/?$', 'index.php?post_type=location&chroma_lang=es', 'top');
        add_rewrite_rule('^es/programs/?$', 'index.php?post_type=program&chroma_lang=es', 'top');
        
        // Single Custom Post Types
        add_rewrite_rule('^es/locations/(.+?)/?$', 'index.php?location=$matches[1]&chroma_lang=es', 'top');
        add_rewrite_rule('^es/programs/(.+?)/?$', 'index.php?program=$matches[1]&chroma_lang=es', 'top');

        // Standard Pages (catch-all for hierarchical pages)
        // Note: This must come AFTER specific CPT rules to avoid conflict if slug collision, 
        // but typically 'pagename' regex handles paths.
        add_rewrite_rule('^es/(.+?)/?$', 'index.php?pagename=$matches[1]&chroma_lang=es', 'top');
    }

    /**
     * Add query vars
     */
    public function add_query_vars($vars)
    {
        $vars[] = 'chroma_lang';
        return $vars;
    }

    /**
     * Check if current request is Spanish
     */
    public static function is_spanish()
    {
        // Check query var
        if (get_query_var('chroma_lang') === 'es') {
            return true;
        }

        // Fallback: Check global or constant if set earlier
        if (defined('CHROMA_CURRENT_LANG') && CHROMA_CURRENT_LANG === 'es') {
            return true;
        }
        
        // Fallback: Check URL structure directly (useful during setup/debug or early hooks)
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/es/') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Get current language code
     */
    public static function get_current_language()
    {
        return self::is_spanish() ? 'es' : 'en';
    }

    /**
     * Filter Home URL
     * Appends /es/ if current language is Spanish
     */
    public function filter_home_url($url, $path)
    {
        if (self::is_spanish() && !is_admin()) {
            // Prevent infinite loop by using site_url/get_option instead of home_url()
            // And avoid double-stacking if the input $url already has /es/ (which might happen if passed from other filters)
            
            // If the RESULTING url ($url passed in) already has /es/, return strict
            if (strpos($url, '/es/') !== false || substr($url, -3) === '/es') {
                return $url;
            }

            // Construct safe base
            $home = get_option('home');
            
            // Clean paths
            $path = ltrim($path, '/');
            $home = rtrim($home, '/');
            
            // Check if path already starts with es/
            if (strpos($path, 'es/') === 0 || $path === 'es') {
                return $home . '/' . $path;
            }

            return $home . '/es/' . $path;
        }
        return $url;
    }

    /**
     * Filter Permalinks (Pages, Posts, CPTs)
     */
    public function filter_permalink($url, $post)
    {
        if (self::is_spanish() && !is_admin()) {
            // Only modify if internal link
            if (strpos($url, home_url()) !== false) {
                // Insert /es/ after home_url
                $base = home_url();
                $path = substr($url, strlen($base));
                return home_url('/es' . $path);
            }
        }
        return $url;
    }

    /**
     * Filter Term Links (Categories, Tags)
     */
    public function filter_term_link($url, $term, $taxonomy)
    {
        if (self::is_spanish() && !is_admin()) {
            if (strpos($url, home_url()) !== false) {
                $base = home_url();
                $path = substr($url, strlen($base));
                return home_url('/es' . $path);
            }
        }
        return $url;
    }
    
    /**
     * Add body class
     */
    public function add_body_class($classes) {
        if (self::is_spanish()) {
            $classes[] = 'lang-es';
            $classes[] = 'translate-spanish';
        }
        return $classes;
    }
    
    /**
     * Filter language attributes (<html> tag)
     */
    public function filter_language_attributes($output) {
        if (self::is_spanish()) {
            return 'lang="es-US"';
        }
        return $output;
    }

    /**
     * Get alternate URLs (EN/ES) for a post
     * 
     * @param int|null $post_id
     * @return array ['en' => url, 'es' => url]
     */
    public static function get_alternates($post_id = null)
    {
        if (!$post_id) $post_id = get_the_ID();
        if (!$post_id) return []; // Fallback if outside loop

        // Get instance to access filters
        $instance = self::get_instance();

        // TEMPORARILY REMOVE FILTERS to get raw English URL
        remove_filter('page_link', [$instance, 'filter_permalink'], 10);
        remove_filter('post_link', [$instance, 'filter_permalink'], 10);
        remove_filter('post_type_link', [$instance, 'filter_permalink'], 10);
        remove_filter('home_url', [$instance, 'filter_home_url'], 10);

        $en_url = get_permalink($post_id);

        // RESTORE FILTERS
        add_filter('page_link', [$instance, 'filter_permalink'], 10, 2);
        add_filter('post_link', [$instance, 'filter_permalink'], 10, 2);
        add_filter('post_type_link', [$instance, 'filter_permalink'], 10, 2);
        add_filter('home_url', [$instance, 'filter_home_url'], 10, 2);

        // Check for manual English override
        $manual_en = get_post_meta($post_id, 'alternate_url_en', true);
        if ($manual_en) {
            $en_url = $manual_en;
        }

        // Check for manual Spanish override
        $manual_es = get_post_meta($post_id, 'alternate_url_es', true);

        if ($manual_es) {
            $es_url = $manual_es;
        } else {
            // Unhook home_url filter to ensure we get the raw base URL
            remove_filter('home_url', [$instance, 'filter_home_url'], 10);
            
            $raw_home = home_url();
            $path = str_replace($raw_home, '', $en_url);
            
            // Re-hook home_url filter
            add_filter('home_url', [$instance, 'filter_home_url'], 10, 2);
            
            // Construct ES URL
            // If path is empty (homepage), we just want /es/
            // If path is /locations/foo, we want /es/locations/foo
            $es_url = rtrim($raw_home, '/') . '/es' . $path;
        }
        
        return [
            'en' => $en_url, 
            'es' => $es_url
        ];
    }

    /**
     * Swap Title for Spanish
     */
    public function swap_title($title, $post_id = null)
    {
        if (!self::is_spanish() || is_admin()) return $title;
        if (!$post_id) $post_id = get_the_ID();
        if (!$post_id) return $title;
        
        $es_title = get_post_meta($post_id, '_chroma_es_title', true);
        return $es_title ?: $title;
    }

    /**
     * Swap Content for Spanish (Option A: Fallback Banner)
     */
    public function swap_content($content)
    {
        if (!self::is_spanish() || is_admin()) return $content;
        
        $post_id = get_the_ID();
        if (!$post_id) return $content;
        
        $es_content = get_post_meta($post_id, '_chroma_es_content', true);
        
        if (empty($es_content)) {
            // Option A: Show fallback banner + English content
            $banner = '<div class="chroma-lang-fallback-notice">' .
                      '<span class="dashicons dashicons-info"></span> ' .
                      esc_html__('This page is not yet available in Spanish. Showing English version.', 'chroma-excellence') .
                      '</div>';
            return $banner . $content;
        }
        
        return $es_content;
    }

    /**
     * Swap Excerpt for Spanish
     */
    public function swap_excerpt($excerpt)
    {
        if (!self::is_spanish() || is_admin()) return $excerpt;
        
        $post_id = get_the_ID();
        if (!$post_id) return $excerpt;
        
        $es_excerpt = get_post_meta($post_id, '_chroma_es_excerpt', true);
        return $es_excerpt ?: $excerpt;
    }

    /**
     * Rewrite Internal URLs in Content
     */
    public function rewrite_content_urls($content)
    {
        if (!self::is_spanish() || is_admin()) return $content;
        
        $site_url = preg_quote(home_url(), '/');
        
        // Match href="https://site.com/path" but not href="https://site.com/es/path"
        $pattern = '/href=["\'](' . $site_url . ')(?!\/es\/)([^"\']*)["\'/i';
        $replacement = 'href="$1/es$2"';
        
        return preg_replace($pattern, $replacement, $content);
    }

    /**
     * Filter Nav Menu Link Attributes (for Custom Links)
     */
    public function filter_nav_menu_link($atts, $item, $args, $depth)
    {
        if (!self::is_spanish() || is_admin()) return $atts;
        
        if (!empty($atts['href'])) {
            $href = $atts['href'];
            $site_url = home_url();
            
            // Only modify internal links that don't already have /es/
            if (strpos($href, $site_url) === 0 && strpos($href, $site_url . '/es/') !== 0) {
                $path = substr($href, strlen($site_url));
                $atts['href'] = $site_url . '/es' . $path;
            }
        }
        
        return $atts;
    }

    /**
     * Filter Canonical URL for Spanish Pages
     */
    public function filter_canonical_url($canonical_url, $post)
    {
        if (!self::is_spanish()) return $canonical_url;
        
        // Ensure canonical points to /es/ version
        $site_url = home_url();
        if (strpos($canonical_url, $site_url . '/es/') !== 0) {
            $path = substr($canonical_url, strlen($site_url));
            return $site_url . '/es' . $path;
        }
        
        return $canonical_url;
    }

    /**
     * Output Fallback Banner CSS
     */
    public function output_fallback_css()
    {
        if (!self::is_spanish()) return;
        
        echo '<style>
        .chroma-lang-fallback-notice {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;
            color: #856404;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chroma-lang-fallback-notice .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        </style>';
    }

    /**
     * Detect Browser Language and Redirect
     * Respects user preference via cookie
     */
    public function detect_browser_language()
    {
        // Skip if already on Spanish or in admin
        if (self::is_spanish() || is_admin()) return;
        
        // Skip if user has opted out
        if (isset($_COOKIE['chroma_lang_pref'])) return;
        
        // Skip bots
        if (defined('DOING_CRON') || defined('REST_REQUEST')) return;
        
        // Check Accept-Language header
        $accept_lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
        
        // Parse language preferences
        $languages = [];
        if ($accept_lang) {
            preg_match_all('/([a-z]{2})(?:-[a-zA-Z]+)?(?:;q=([0-9.]+))?/', strtolower($accept_lang), $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $lang = $match[1];
                $quality = isset($match[2]) ? (float)$match[2] : 1.0;
                $languages[$lang] = $quality;
            }
            arsort($languages);
        }
        
        // Check if Spanish is preferred language
        $preferred = array_keys($languages);
        if (!empty($preferred) && $preferred[0] === 'es') {
            // Set cookie to remember we've redirected (24 hour expiry)
            setcookie('chroma_lang_pref', 'auto', time() + DAY_IN_SECONDS, '/');
            
            // Get Spanish URL
            $alternates = self::get_alternates(get_the_ID());
            if (!empty($alternates['es'])) {
                wp_redirect($alternates['es']);
                exit;
            }
        }
    }
}

/**
 * Global helper for theme usage
 */
function chroma_get_alternates($post_id = null) {
    if (class_exists('Chroma_Multilingual_Manager')) {
        return Chroma_Multilingual_Manager::get_alternates($post_id);
    }
    return [];
}

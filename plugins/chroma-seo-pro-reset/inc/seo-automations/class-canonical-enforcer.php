<?php
/**
 * Canonical URL Enforcer
 * Ensures proper canonical URLs to prevent duplicate content
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Canonical_Enforcer
{
    /**
     * Guard against duplicate canonical output in one request.
     *
     * @var bool
     */
    private $canonical_rendered = false;

    public function __construct() {
        // Remove WordPress default canonical
        remove_action('wp_head', 'rel_canonical');
        
        // Add our canonical
        add_action('wp_head', [$this, 'output_canonical'], 1);
        
        // Redirect non-canonical URLs
        add_action('template_redirect', [$this, 'enforce_canonical'], 1);
    }

    /**
     * Detect XML sitemap requests (native WP and legacy aliases).
     *
     * @return bool
     */
    private function is_sitemap_request()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = strtok($request_uri, '?');
        if (!is_string($path)) {
            $path = '';
        }

        if ($path !== '' && preg_match('#/(wp-sitemap(?:-[^/]+)?\.xml|sitemap(?:_index)?\.xml|sitemap-[^/]+\.xml)$#i', $path)) {
            return true;
        }

        // Core query vars used by WP sitemaps.
        if (get_query_var('sitemap') || get_query_var('sitemap-subtype') || get_query_var('sitemap-stylesheet')) {
            return true;
        }

        if (isset($_GET['sitemap']) || isset($_GET['sitemap-stylesheet'])) {
            return true;
        }

        return false;
    }
    
    /**
     * Get canonical URL for current page
     */
    public function get_canonical_url() {
        global $wp;
        
        // Start with current URL
        $url = home_url($wp->request);
        
        // Handle special cases
        if (get_query_var('chroma_combo')) {
            $program_slug = get_query_var('combo_program');
            $city_slug = get_query_var('combo_city');
            $state = get_query_var('combo_state');
            $url = home_url("/{$program_slug}-in-{$city_slug}-{$state}/");
        } elseif (is_front_page()) {
            $url = home_url('/');
        } elseif (is_singular()) {
            $url = get_permalink();
        } elseif (is_post_type_archive()) {
            $url = get_post_type_archive_link(get_post_type());
        } elseif (is_category()) {
            $url = get_category_link(get_queried_object_id());
        } elseif (is_tag()) {
            $url = get_tag_link(get_queried_object_id());
        } elseif (is_tax()) {
            $term = get_queried_object();
            if ($term && !empty($term->term_id)) {
                $url = get_term_link($term);
            }
        }

        return $this->normalize_canonical_url($url);
    }

    /**
     * Normalize canonical URL according to site rules.
     *
     * @param string $url Raw canonical candidate.
     * @return string
     */
    private function normalize_canonical_url($url) {
        if (is_wp_error($url) || empty($url)) {
            return '';
        }

        $url = $this->strip_tracking_params((string) $url);

        $parts = wp_parse_url($url);
        if (empty($parts['host'])) {
            return '';
        }

        $scheme = !empty($parts['scheme']) ? $parts['scheme'] : (is_ssl() ? 'https' : 'http');
        if (is_ssl() || strpos(home_url('/'), 'https://') === 0) {
            $scheme = 'https';
        }

        $path = isset($parts['path']) ? $parts['path'] : '/';
        $query = isset($parts['query']) ? $parts['query'] : '';

        $trailing_slash = get_option('chroma_seo_trailing_slash', true);
        $is_file = (bool) preg_match('/\.(html?|xml|json|php)$/i', $path);

        if (!$is_file) {
            $path = $trailing_slash ? trailingslashit($path) : untrailingslashit($path);
            if ($path === '') {
                $path = '/';
            }
        }

        $normalized = $scheme . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $normalized .= ':' . (int) $parts['port'];
        }
        $normalized .= $path;

        if ($query !== '') {
            $normalized .= '?' . $query;
        }

        return $normalized;
    }
    
    /**
     * Strip tracking parameters
     */
    private function strip_tracking_params($url) {
        $tracking_params = [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'fbclid',
            'gclid',
            'msclkid',
            'ref',
            'source'
        ];
        
        $parsed = parse_url($url);
        
        if (!isset($parsed['query'])) {
            return $url;
        }
        
        parse_str($parsed['query'], $params);
        
        foreach ($tracking_params as $param) {
            unset($params[$param]);
        }
        
        $base = $parsed['scheme'] . '://' . $parsed['host'];
        if (isset($parsed['path'])) {
            $base .= $parsed['path'];
        }
        
        if (!empty($params)) {
            $base .= '?' . http_build_query($params);
        }
        
        return $base;
    }
    
    /**
     * Output canonical tag
     */
    public function output_canonical() {
        if (!get_option('chroma_seo_enable_canonical', true)) {
            return;
        }

        // XML sitemap responses should not emit HTML canonical tags.
        if ($this->is_sitemap_request()) {
            return;
        }
        
        // If Yoast SEO is active, let it handle the canonical to avoid duplicates
        if (defined('WPSEO_VERSION')) {
            return;
        }
        
        if ($this->canonical_rendered || did_action('chroma_canonical_output_done')) {
            return;
        }

        $canonical = $this->get_canonical_url();

        if (!empty($canonical)) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
            $this->canonical_rendered = true;
            do_action('chroma_canonical_output_done', $canonical);
        }
    }
    
    /**
     * Enforce canonical URL via redirect
     */
    public function enforce_canonical() {
        if (!get_option('chroma_seo_redirect_canonical', true)) {
            return;
        }
        
        // Don't redirect admin, AJAX, or non-GET requests
        if (is_admin() || wp_doing_ajax() || $_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }
        
        // Don't redirect 404s
        if (is_404()) {
            return;
        }

        // Never canonical-redirect sitemap endpoints.
        if ($this->is_sitemap_request()) {
            return;
        }
        
        if (empty($_SERVER['HTTP_HOST']) || empty($_SERVER['REQUEST_URI'])) {
            return;
        }

        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $current_url = $this->normalize_canonical_url($current_url);
        $canonical = $this->get_canonical_url();

        if (empty($current_url) || empty($canonical)) {
            return;
        }
        
        // Normalize for comparison (without query string for some checks)
        $current_path = strtok($current_url, '?');
        $canonical_path = strtok($canonical, '?');
        
        if ($current_path !== $canonical_path) {
            wp_safe_redirect($canonical, 301);
            exit;
        }
    }
}

new Chroma_Canonical_Enforcer();



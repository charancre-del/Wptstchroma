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
    public function __construct() {
        // Remove WordPress default canonical
        remove_action('wp_head', 'rel_canonical');
        
        // Add our canonical
        add_action('wp_head', [$this, 'output_canonical'], 1);
        
        // Redirect non-canonical URLs
        add_action('template_redirect', [$this, 'enforce_canonical'], 1);
    }
    
    /**
     * Get canonical URL for current page
     */
    public function get_canonical_url() {
        global $wp;
        
        // Start with current URL
        $url = home_url($wp->request);
        
        // Force trailing slash preference
        $trailing_slash = get_option('chroma_seo_trailing_slash', true);
        if ($trailing_slash && !preg_match('/\.(html?|xml|json|php)$/', $url)) {
            $url = trailingslashit($url);
        } elseif (!$trailing_slash) {
            $url = untrailingslashit($url);
        }
        
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
        }
        
        // Ensure HTTPS
        if (is_ssl()) {
            $url = str_replace('http://', 'https://', $url);
        }
        
        // Remove tracking parameters
        $url = $this->strip_tracking_params($url);
        
        return $url;
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
        
        // If Yoast SEO is active, let it handle the canonical to avoid duplicates
        if (defined('WPSEO_VERSION')) {
            return;
        }
        
        $canonical = $this->get_canonical_url();
        
        if ($canonical) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
        }
    }
    
    /**
     * Enforce canonical URL via redirect
     */
    public function enforce_canonical() {
        if (!get_option('chroma_seo_redirect_canonical', false)) {
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
        
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $canonical = $this->get_canonical_url();
        
        // Normalize for comparison (without query string for some checks)
        $current_path = strtok($current_url, '?');
        $canonical_path = strtok($canonical, '?');
        
        if ($current_path !== $canonical_path) {
            wp_redirect($canonical, 301);
            exit;
        }
    }
}

new Chroma_Canonical_Enforcer();



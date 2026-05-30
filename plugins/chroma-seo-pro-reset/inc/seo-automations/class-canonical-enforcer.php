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

    /**
     * Get the current request path without query params.
     *
     * @return string
     */
    private function get_request_path()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);

        return is_string($path) ? $path : '';
    }

    public function __construct()
    {
        // Remove WordPress default canonical
        remove_action('wp_head', 'rel_canonical');

        // Add our canonical
        add_action('wp_head', [$this, 'output_canonical'], 1);
        add_filter('wp_robots', [$this, 'filter_404_robots'], 20);
        add_filter('wpseo_robots', [$this, 'filter_404_wpseo_robots'], 20);
        add_filter('wpseo_canonical', [$this, 'filter_wpseo_empty_canonical'], 20);
        add_filter('wpseo_opengraph_url', [$this, 'filter_wpseo_empty_canonical'], 20);
        add_filter('redirect_canonical', [$this, 'preserve_program_permalink_requests'], 1, 2);
        add_filter('old_slug_redirect_url', [$this, 'resolve_program_old_slug_redirect'], 10, 1);

        // Redirect non-canonical URLs
        add_action('template_redirect', [$this, 'enforce_canonical'], 1);
    }

    /**
     * Check whether the current request already matches the queried program slug.
     *
     * @return bool
     */
    private function is_current_program_permalink_request()
    {
        if (!is_singular('program')) {
            return false;
        }

        $post = get_queried_object();
        if (!$post instanceof WP_Post || $post->post_type !== 'program' || $post->post_name === '') {
            return false;
        }

        $request_path = $this->get_request_path();
        if ($request_path === '') {
            return false;
        }

        $program_slug = 'programs';
        $program_type = get_post_type_object('program');
        if ($program_type && !empty($program_type->rewrite['slug'])) {
            $program_slug = trim((string) $program_type->rewrite['slug'], '/');
        }

        $expected_paths = [
            user_trailingslashit('/' . $program_slug . '/' . $post->post_name),
            user_trailingslashit('/es/' . $program_slug . '/' . $post->post_name),
        ];

        return in_array(user_trailingslashit($request_path), $expected_paths, true);
    }

    /**
     * Prevent core canonical redirects from moving valid program permalinks.
     *
     * @param string|false $redirect_url
     * @param string       $requested_url
     * @return string|false
     */
    public function preserve_program_permalink_requests($redirect_url, $requested_url)
    {
        if ($this->is_current_program_permalink_request()) {
            return false;
        }

        return $redirect_url;
    }

    /**
     * Resolve WordPress old-slug redirects for programs by the requested slug.
     *
     * WordPress stores previous slugs in shared _wp_old_slug meta. If two program
     * records have held the same slug, core can redirect the old URL to the wrong
     * program. Prefer the program whose title/current slug matches the requested
     * path before allowing core's default old-slug target.
     *
     * @param string|false $redirect_url
     * @return string|false
     */
    public function resolve_program_old_slug_redirect($redirect_url)
    {
        $request_path = trim($this->get_request_path(), '/');
        if ($request_path === '') {
            return $redirect_url;
        }

        $program_slug = 'programs';
        $program_type = get_post_type_object('program');
        if ($program_type && !empty($program_type->rewrite['slug'])) {
            $program_slug = trim((string) $program_type->rewrite['slug'], '/');
        }

        $pattern = '#^(es/)?' . preg_quote($program_slug, '#') . '/([^/]+)/?$#i';
        if (!preg_match($pattern, $request_path, $matches)) {
            return $redirect_url;
        }

        $is_spanish = !empty($matches[1]);
        $requested_slug = sanitize_title((string) $matches[2]);
        if ($requested_slug === '') {
            return $redirect_url;
        }

        $candidates = get_posts([
            'post_type' => 'program',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => '_wp_old_slug',
            'meta_value' => $requested_slug,
            'orderby' => 'modified',
            'order' => 'DESC',
            'suppress_filters' => true,
        ]);

        if (empty($candidates)) {
            return $redirect_url;
        }

        $fallback = null;
        foreach ($candidates as $candidate) {
            if (!$candidate instanceof WP_Post) {
                continue;
            }

            if ($fallback === null) {
                $fallback = $candidate;
            }

            if (sanitize_title((string) $candidate->post_title) === $requested_slug) {
                return $this->get_program_redirect_url($candidate, $program_slug, $is_spanish);
            }

            if (strpos((string) $candidate->post_name, $requested_slug . '-') === 0) {
                return $this->get_program_redirect_url($candidate, $program_slug, $is_spanish);
            }
        }

        return $fallback instanceof WP_Post ? $this->get_program_redirect_url($fallback, $program_slug, $is_spanish) : $redirect_url;
    }

    /**
     * Build a program redirect URL, preserving Spanish route prefixes.
     *
     * @param WP_Post $post
     * @param string  $program_slug
     * @param bool    $is_spanish
     * @return string
     */
    private function get_program_redirect_url(WP_Post $post, $program_slug, $is_spanish)
    {
        if ($is_spanish) {
            return home_url('/es/' . trim((string) $program_slug, '/') . '/' . $post->post_name . '/');
        }

        return get_permalink($post);
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
     * Detect QA portal routes that should never be canonical-redirected.
     *
     * @return bool
     */
    private function is_qa_route_request()
    {
        if ((string) get_query_var('cqa_page') !== '') {
            return true;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return false;
        }

        return (bool) preg_match('#^/qa-reports?(?:/|$)#i', $path);
    }

    /**
     * Get canonical URL for current page
     */
    public function get_canonical_url()
    {
        global $wp;

        // Start with current URL
        $url = home_url($wp->request);

        $request_path = $this->get_request_path();
        if ($request_path !== '' && function_exists('chroma_get_dynamic_route_canonical_path')) {
            $dynamic_path = chroma_get_dynamic_route_canonical_path($request_path);
            if ($dynamic_path !== '') {
                return $this->normalize_canonical_url(home_url($dynamic_path));
            }
        }

        // Handle special cases
        if (get_query_var('chroma_combo')) {
            $program_slug = get_query_var('combo_program');
            $city_slug = get_query_var('combo_city');
            $state = strtolower((string) get_query_var('combo_state'));
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
    private function normalize_canonical_url($url)
    {
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
    private function strip_tracking_params($url)
    {
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
    public function output_canonical()
    {
        if (!get_option('chroma_seo_enable_canonical', true)) {
            return;
        }

        if (function_exists('chroma_is_otto_compatible_seo_mode') && chroma_is_otto_compatible_seo_mode()) {
            return;
        }

        // QA portal is app-shell driven and handles its own route behavior.
        if ($this->is_qa_route_request()) {
            return;
        }

        // XML sitemap responses should not emit HTML canonical tags.
        if ($this->is_sitemap_request()) {
            return;
        }

        // 404s should stay non-indexable and should not self-canonicalize.
        if (is_404()) {
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
     * Fill Yoast canonical gaps without overriding explicit Yoast or virtual-route canonicals.
     *
     * @param string $canonical Current Yoast canonical/OpenGraph URL.
     * @return string
     */
    public function filter_wpseo_empty_canonical($canonical)
    {
        if (!empty($canonical)) {
            return $canonical;
        }

        if (!get_option('chroma_seo_enable_canonical', true)) {
            return $canonical;
        }

        if ($this->is_qa_route_request() || $this->is_sitemap_request() || is_404()) {
            return $canonical;
        }

        $fallback = $this->get_canonical_url();
        return !empty($fallback) ? $fallback : $canonical;
    }

    /**
     * Force 404 pages to remain noindex,follow.
     *
     * @param array $robots
     * @return array
     */
    public function filter_404_robots($robots)
    {
        if (!is_404()) {
            return $robots;
        }

        if (!is_array($robots)) {
            $robots = [];
        }

        unset($robots['index'], $robots['nofollow']);
        $robots['noindex'] = true;
        $robots['follow'] = true;

        return $robots;
    }

    /**
     * Force Yoast 404 responses to stay noindex,follow when Yoast owns robots output.
     *
     * @param string $robots
     * @return string
     */
    public function filter_404_wpseo_robots($robots)
    {
        if (!is_404()) {
            return $robots;
        }

        return 'noindex,follow';
    }

    /**
     * Enforce canonical URL via redirect
     */
    public function enforce_canonical()
    {
        if (!get_option('chroma_seo_redirect_canonical', true)) {
            return;
        }

        if (function_exists('chroma_is_otto_compatible_seo_mode') && chroma_is_otto_compatible_seo_mode()) {
            return;
        }

        // Don't redirect admin, AJAX, or non-GET requests
        if (is_admin() || wp_doing_ajax() || !isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        }

        // Never canonical-redirect QA portal routes.
        if ($this->is_qa_route_request()) {
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

        // Never redirect virtual pages (combo pages, near-me pages, geographic SEO).
        if (get_query_var('chroma_combo') || get_query_var('chroma_near_me') || get_query_var('chroma_combo_sitemap') || get_query_var('chroma_service_area')) {
            return;
        }

        // A valid program permalink should not be collapsed to a different program.
        if ($this->is_current_program_permalink_request()) {
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

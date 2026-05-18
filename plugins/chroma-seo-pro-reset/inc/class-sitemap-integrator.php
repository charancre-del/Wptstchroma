<?php
/**
 * Sitemap Integrator
 * Injects Spanish URLs into the native WordPress XML sitemap.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Sitemap_Integrator
{
    public function init()
    {
        // Serve custom XML sitemap endpoints before theme-level redirects.
        add_action('template_redirect', [$this, 'serve_custom_sitemap_endpoints'], -1001);

        // NOTE: Sitemap query var routing is handled by the theme's
        // chroma_force_sitemap_request_vars() in functions.php at priority 0.

        // Keep legacy sitemap endpoints aligned to WP native sitemap index.
        add_action('template_redirect', [$this, 'handle_legacy_sitemap_aliases'], 0);

        // Exclude known duplicate winners from native post sitemaps.
        add_filter('wp_sitemaps_posts_query_args', [$this, 'filter_posts_sitemap_query_args'], 10, 2);

        // Prevent core canonical redirect logic from collapsing sitemap endpoints to home.
        add_filter('redirect_canonical', [$this, 'preserve_sitemap_endpoints'], 10, 2);

        // Keep Yoast sitemap index aligned with custom native providers when Yoast is active.
        add_filter('wpseo_sitemap_index', [$this, 'append_to_yoast_sitemap_index']);
    }

    /**
     * Serve dedicated sitemap endpoints directly from plugin code.
     *
     * Endpoints:
     * - /sitemap_index.xml
     * - /sitemap-spanish.xml
     * - /sitemap-combos.xml
     * - /sitemap-combos-es.xml
     * - /sitemap-near-me.xml
     * - /sitemap-near-me-es.xml
     */
    public function serve_custom_sitemap_endpoints()
    {
        if (is_admin() || wp_doing_ajax() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
        if (!$request_uri) {
            return;
        }

        $path = '/' . trim($request_uri, '/');

        switch ($path) {
            case '/sitemap_index.xml':
                $this->render_sitemap_index([
                    '/sitemap.xml',
                    '/sitemap-spanish.xml',
                    '/sitemap-combos.xml',
                    '/sitemap-combos-es.xml',
                    '/sitemap-near-me.xml',
                    '/sitemap-near-me-es.xml',
                ]);
                break;

            case '/sitemap-spanish.xml':
                $this->render_spanish_sitemap();
                break;

            case '/sitemap-combos.xml':
                $this->render_combo_sitemap('en');
                break;

            case '/sitemap-combos-es.xml':
                $this->render_combo_sitemap('es');
                break;

            case '/sitemap-near-me.xml':
                $this->render_near_me_sitemap('en');
                break;

            case '/sitemap-near-me-es.xml':
                $this->render_near_me_sitemap('es');
                break;
        }
    }

    /**
     * Render a sitemap index XML document.
     *
     * @param string[] $paths
     */
    private function render_sitemap_index($paths)
    {
        nocache_headers();
        header('Content-Type: application/xml; charset=UTF-8');
        status_header(200);

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ((array) $paths as $path) {
            $loc = esc_url(home_url((string) $path));
            $lastmod = $this->get_sitemap_group_lastmod((string) $path);
            echo '  <sitemap>' . "\n";
            echo '    <loc>' . $loc . '</loc>' . "\n";
            echo '    <lastmod>' . esc_html($lastmod) . '</lastmod>' . "\n";
            echo '  </sitemap>' . "\n";
        }
        echo '</sitemapindex>' . "\n";
        exit;
    }

    /**
     * Render combo sitemap XML.
     *
     * @param string $lang 'en'|'es'
     */
    private function render_combo_sitemap($lang = 'en')
    {
        $entries = [];

        if (class_exists('Chroma_Combo_Page_Generator') && class_exists('Chroma_Combo_Page_Data')) {
            $combos = Chroma_Combo_Page_Generator::get_all_combos();
            foreach ($combos as $combo) {
                if (empty($combo['program']) || empty($combo['city']) || empty($combo['state']) || empty($combo['url'])) {
                    continue;
                }

                $saved_data = Chroma_Combo_Page_Data::get(
                    $combo['program']->post_name,
                    sanitize_title($combo['city']),
                    $combo['state']
                );
                $status = $saved_data['status'] ?? 'auto';
                if ($status !== 'published' && $status !== 'publish') {
                    continue;
                }

                $url = (string) $combo['url'];
                if ($lang === 'es') {
                    $url = $this->to_spanish_url($url);
                }

                if ($url === '') {
                    continue;
                }

                $entries[] = [
                    'loc' => $url,
                    'lastmod' => $this->get_combo_lastmod($combo, $saved_data),
                ];
            }
        }

        $this->render_urlset($entries);
    }

    /**
     * Render near-me sitemap XML.
     *
     * @param string $lang 'en'|'es'
     */
    private function render_near_me_sitemap($lang = 'en')
    {
        $entries = [];

        if (class_exists('Chroma_Near_Me_Pages') && method_exists('Chroma_Near_Me_Pages', 'get_sitemap_urls')) {
            $links = Chroma_Near_Me_Pages::get_sitemap_urls();
            foreach ($links as $link) {
                $url = (string) $link;
                if ($lang === 'es') {
                    $url = $this->to_spanish_url($url);
                }

                if ($url === '') {
                    continue;
                }

                $entries[] = [
                    'loc' => $url,
                    'lastmod' => $this->get_virtual_url_lastmod($url),
                ];
            }
        }

        $this->render_urlset($entries);
    }

    /**
     * Render Spanish sitemap XML.
     */
    private function render_spanish_sitemap()
    {
        $entries = [];
        $base = rtrim(home_url('/'), '/');
        $post_types = ['page', 'location', 'program', 'city', 'post'];

        $posts = get_posts([
            'post_type' => $post_types,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        foreach ($posts as $post) {
            $en_url = get_permalink($post->ID);
            if (!$en_url) {
                continue;
            }

            $path = ltrim((string) str_replace($base, '', $en_url), '/');
            if ($path === '' || str_starts_with($path, 'es/')) {
                continue;
            }

            // Keep duplicate winners out (same rule used across sitemap logic).
            if (class_exists('Chroma_URL_Consolidator') && method_exists('Chroma_URL_Consolidator', 'is_duplicate_path')) {
                if (Chroma_URL_Consolidator::is_duplicate_path('/' . trim($path, '/') . '/')) {
                    continue;
                }
            }

            $entries[] = [
                'loc' => $base . '/es/' . trim($path, '/') . '/',
                'lastmod' => get_the_modified_date('c', $post->ID),
            ];
        }

        $this->render_urlset($entries);
    }

    /**
     * Convert an absolute EN URL to its /es/ equivalent.
     *
     * @param string $url
     * @return string
     */
    private function to_spanish_url($url)
    {
        $url = (string) $url;
        if ($url === '') {
            return '';
        }

        $base = rtrim(home_url('/'), '/');
        $es_url = str_replace($base . '/', $base . '/es/', $url);
        if ($es_url !== $url) {
            return $es_url;
        }

        $path = wp_parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '';
        }

        return home_url('/es/' . ltrim($path, '/'));
    }

    /**
     * Render urlset XML and terminate request.
     *
     * @param array[] $entries
     */
    private function render_urlset($entries)
    {
        nocache_headers();
        header('Content-Type: application/xml; charset=UTF-8');
        status_header(200);

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ((array) $entries as $entry) {
            $loc = isset($entry['loc']) ? (string) $entry['loc'] : '';
            if ($loc === '') {
                continue;
            }

            $lastmod = isset($entry['lastmod']) && $entry['lastmod'] ? (string) $entry['lastmod'] : gmdate('c');
            echo "  <url>\n";
            echo '    <loc>' . esc_url($loc) . "</loc>\n";
            echo '    <lastmod>' . esc_html($lastmod) . "</lastmod>\n";
            echo "  </url>\n";
        }

        echo '</urlset>' . "\n";
        exit;
    }

    /**
     * Manually inject sitemap query vars from the URL when rewrite rules are missing.
     *
     * WordPress native sitemaps rely on rewrite rules to map pretty URLs like
     * /wp-sitemap.xml to query vars (?sitemap=index). If flush_rewrite_rules()
     * fails to persist (hosting, caching, etc.), sitemaps silently 404.
     * This filter detects sitemap URLs and sets the query vars directly.
     *
     * @param array $query_vars Parsed query vars from WP::parse_request().
     * @return array
     */
    public function force_sitemap_query_vars($query_vars)
    {
        // Only act when query vars don't already contain sitemap info.
        if (!empty($query_vars['sitemap'])) {
            return $query_vars;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
        $path = '/' . trim($request_uri, '/');

        $sitemap_vars = null;

        // /wp-sitemap.xml → sitemap index
        if ($path === '/wp-sitemap.xml') {
            $sitemap_vars = ['sitemap' => 'index'];
        }
        // /wp-sitemap-{name}-{page}.xml → provider without subtype
        elseif (preg_match('#^/wp-sitemap-([a-z]+)-(\d+)\.xml$#', $path, $m)) {
            $sitemap_vars = ['sitemap' => $m[1], 'paged' => (int) $m[2]];
        }
        // /wp-sitemap-{name}-{subtype}-{page}.xml → provider with subtype
        elseif (preg_match('#^/wp-sitemap-([a-z]+)-([a-z\d_-]+)-(\d+)\.xml$#', $path, $m)) {
            $sitemap_vars = ['sitemap' => $m[1], 'sitemap-subtype' => $m[2], 'paged' => (int) $m[3]];
        }
        // Sitemap stylesheets
        elseif ($path === '/wp-sitemap.xsl' || $path === '/wp-sitemap-index.xsl') {
            $sitemap_vars = ['sitemap-stylesheet' => ($path === '/wp-sitemap-index.xsl') ? 'sitemap-index' : 'sitemap'];
        }

        if ($sitemap_vars !== null) {
            // CRITICAL: Remove conflicting vars that WP sets when rewrite rules fail.
            // Without this, WP treats the URL as a page request ("pagename=wp-sitemap.xml"),
            // can't find such a page, and returns 404 before the sitemap handler fires.
            unset($query_vars['pagename'], $query_vars['name'], $query_vars['page'], $query_vars['error']);
            return array_merge($query_vars, $sitemap_vars);
        }

        return $query_vars;
    }

    /**
     * Keep sitemap routes from being canonical-redirected.
     *
     * @param string|false $redirect_url
     * @param string       $requested_url
     * @return string|false
     */
    public function preserve_sitemap_endpoints($redirect_url, $requested_url)
    {
        $path = wp_parse_url((string) $requested_url, PHP_URL_PATH);
        if (!is_string($path)) {
            return $redirect_url;
        }

        if (preg_match('#/(wp-sitemap(?:-[^/]+)?\.xml|sitemap(?:_index)?\.xml|sitemap-[^/]+\.xml)$#i', $path)) {
            return false;
        }

        return $redirect_url;
    }

    /**
     * Former native provider registration - now unused.
     */
    public function register_providers()
    {
    }

    /**
     * Former legacy alias handler - now in functions.php.
     */
    public function handle_legacy_sitemap_aliases()
    {
    }

    /**
     * Exclude known duplicate URLs from native post sitemaps.
     *
     * @param array  $args
     * @param string $post_type
     * @return array
     */
    public function filter_posts_sitemap_query_args($args, $post_type)
    {
        $exclude_ids = $this->get_duplicate_excluded_post_ids();
        if (empty($exclude_ids)) {
            return $args;
        }

        if (!isset($args['post__not_in']) || !is_array($args['post__not_in'])) {
            $args['post__not_in'] = [];
        }

        $args['post__not_in'] = array_values(array_unique(array_merge($args['post__not_in'], $exclude_ids)));
        return $args;
    }

    /**
     * Append custom native sitemaps to Yoast index (without duplicate entries).
     *
     * @param string $sitemap_index
     * @return string
     */
    public function append_to_yoast_sitemap_index($sitemap_index)
    {
        $entries = [
            [
                'primary' => '/sitemap-spanish.xml',
                'aliases' => [],
            ],
            [
                'primary' => '/sitemap-combos.xml',
                'aliases' => [],
            ],
            [
                'primary' => '/sitemap-combos-es.xml',
                'aliases' => [],
            ],
            [
                'primary' => '/sitemap-near-me.xml',
                'aliases' => [],
            ],
            [
                'primary' => '/sitemap-near-me-es.xml',
                'aliases' => [],
            ],
        ];

        foreach ($entries as $entry) {
            if ($this->sitemap_index_contains_any($sitemap_index, array_merge([$entry['primary']], $entry['aliases']))) {
                continue;
            }

            $loc = esc_url(home_url($entry['primary']));
            $last_mod = $this->get_sitemap_group_lastmod((string) $entry['primary']);
            $sitemap_index .= '<sitemap><loc>' . $loc . '</loc><lastmod>' . $last_mod . '</lastmod></sitemap>';
        }

        return $sitemap_index;
    }

    /**
     * Resolve a stable sitemap-level lastmod from the content represented by a custom sitemap.
     *
     * @param string $path
     * @return string
     */
    private function get_sitemap_group_lastmod($path)
    {
        $path = '/' . trim((string) $path, '/');

        if ($path === '/sitemap-combos.xml' || $path === '/sitemap-combos-es.xml') {
            return $this->get_latest_modified_for_post_types(['program', 'city', 'location']);
        }

        if ($path === '/sitemap-near-me.xml' || $path === '/sitemap-near-me-es.xml') {
            return $this->get_latest_modified_for_post_types(['program', 'city', 'location']);
        }

        if ($path === '/sitemap-spanish.xml' || $path === '/sitemap.xml') {
            return $this->get_latest_modified_for_post_types(['page', 'location', 'program', 'city', 'post']);
        }

        return $this->get_latest_modified_for_post_types(['page', 'location', 'program', 'city', 'post']);
    }

    /**
     * Get lastmod for a program/city combo entry.
     *
     * @param array $combo
     * @param array $saved_data
     * @return string
     */
    private function get_combo_lastmod($combo, $saved_data)
    {
        $timestamps = [];

        if (!empty($saved_data['last_updated'])) {
            $timestamps[] = (int) $saved_data['last_updated'];
        }

        if (!empty($combo['program']) && $combo['program'] instanceof WP_Post && !empty($combo['program']->post_modified_gmt)) {
            $timestamps[] = strtotime($combo['program']->post_modified_gmt);
        }

        if (!empty($combo['city'])) {
            $timestamps = array_merge(
                $timestamps,
                $this->get_city_context_timestamps(sanitize_title((string) $combo['city']), (string) ($combo['state'] ?? 'GA'))
            );
        }

        $timestamps = array_filter(array_map('intval', $timestamps));
        return !empty($timestamps) ? gmdate('c', max($timestamps)) : $this->get_latest_modified_for_post_types(['program', 'city', 'location']);
    }

    /**
     * Get a stable lastmod for a virtual near-me URL.
     *
     * @param string $url
     * @return string
     */
    private function get_virtual_url_lastmod($url)
    {
        $path = wp_parse_url((string) $url, PHP_URL_PATH);
        if (!is_string($path)) {
            return $this->get_latest_modified_for_post_types(['program', 'city', 'location']);
        }

        $path = trim((string) preg_replace('#^/es(?=/|$)#', '', $path), '/');
        if (preg_match('/^[a-z0-9-]+-near-([a-z0-9-]+)-([a-z]{2})$/', $path, $matches)) {
            $timestamps = $this->get_city_context_timestamps(sanitize_title($matches[1]), strtoupper($matches[2]));
            if (!empty($timestamps)) {
                return gmdate('c', max($timestamps));
            }
        }

        return $this->get_latest_modified_for_post_types(['program', 'city', 'location']);
    }

    /**
     * Get timestamps for a virtual city context's backing records.
     *
     * @param string $city_slug
     * @param string $state
     * @return int[]
     */
    private function get_city_context_timestamps($city_slug, $state = 'GA')
    {
        if (!function_exists('chroma_seo_resolve_virtual_city_context')) {
            return [];
        }

        $context = chroma_seo_resolve_virtual_city_context($city_slug, $state);
        if (!is_array($context)) {
            return [];
        }

        $timestamps = [];
        foreach (['city_page_id', 'location_id'] as $key) {
            $post_id = (int) ($context[$key] ?? 0);
            if ($post_id <= 0) {
                continue;
            }

            $post = get_post($post_id);
            if ($post instanceof WP_Post && !empty($post->post_modified_gmt)) {
                $timestamps[] = strtotime($post->post_modified_gmt);
            }
        }

        return array_filter(array_map('intval', $timestamps));
    }

    /**
     * Latest modified timestamp across post types.
     *
     * @param string[] $post_types
     * @return string
     */
    private function get_latest_modified_for_post_types($post_types)
    {
        $posts = get_posts([
            'post_type' => (array) $post_types,
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);

        if (!empty($posts) && $posts[0] instanceof WP_Post && !empty($posts[0]->post_modified_gmt)) {
            return gmdate('c', strtotime($posts[0]->post_modified_gmt));
        }

        return gmdate('c');
    }

    /**
     * Check whether sitemap index already contains any sitemap URL path.
     *
     * @param string $sitemap_index
     * @param array  $paths
     * @return bool
     */
    private function sitemap_index_contains_any($sitemap_index, $paths)
    {
        foreach ((array) $paths as $path) {
            $url = home_url($path);
            if (strpos($sitemap_index, $url) !== false || strpos($sitemap_index, esc_url($url)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve duplicate content IDs once per request.
     *
     * @return int[]
     */
    private function get_duplicate_excluded_post_ids()
    {
        static $ids = null;
        if ($ids !== null) {
            return $ids;
        }

        $ids = [];

        $winner_map_slugs = ['employers-2'];
        foreach ($winner_map_slugs as $slug) {
            $post = get_page_by_path($slug, OBJECT, ['page', 'post', 'career']);
            if (!empty($post->ID)) {
                $ids[] = (int) $post->ID;
            }
        }

        // Career slug variants like childcare-teacher-2
        $career_dupes = get_posts([
            'post_type' => 'career',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'name' => '',
        ]);

        if (!empty($career_dupes)) {
            foreach ($career_dupes as $career_id) {
                $slug = get_post_field('post_name', $career_id);
                if ($slug && preg_match('/\-\d+$/', $slug)) {
                    $ids[] = (int) $career_id;
                }
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));
        return $ids;
    }
}

/**
 * Custom Sitemap Provider for Spanish Content
 * Includes all translated post types (Singulars Only)
 */
class Chroma_Spanish_Sitemap_Provider extends WP_Sitemaps_Provider
{

    public function __construct()
    {
        $this->name = 'spanish';
        $this->object_type = 'custom';
    }

    private $per_page = 2000;
    private $post_types = ['page', 'location', 'program', 'city', 'post'];
    private $excluded_paths = [
        'employers-2',
        'es/employers-2',
    ];

    public function get_url_list($page_num, $object_subtype = '')
    {
        $urls = [];
        $base = rtrim(get_option('home'), '/');

        // Static Post Types
        $posts = get_posts([
            'post_type' => $this->post_types,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        foreach ($posts as $post) {
            // Direct URL construction (avoids context issues with get_alternates)
            $en_permalink = get_permalink($post->ID);
            if ($en_permalink) {
                // Remove base and prepend /es/
                $path = str_replace($base, '', $en_permalink);
                $path = ltrim($path, '/');

                if ($this->should_exclude_path($path)) {
                    continue;
                }

                $es_url = $base . '/es/' . $path;

                $urls[] = [
                    'loc' => $es_url,
                    'lastmod' => get_the_modified_date('c', $post->ID),
                ];
            }
        }

        // Pagination
        $offset = ($page_num - 1) * $this->per_page;
        return array_slice($urls, $offset, $this->per_page);
    }


    public function get_max_num_pages($object_subtype = '')
    {
        $count = 0;
        foreach ($this->post_types as $type) {
            $count += (int) wp_count_posts($type)->publish;
        }
        return max(1, ceil($count / $this->per_page));
    }

    /**
     * Exclude known duplicate paths from Spanish sitemap.
     *
     * @param string $path Relative EN path.
     * @return bool
     */
    private function should_exclude_path($path)
    {
        $normalized = trim(strtolower((string) $path), '/');
        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, $this->excluded_paths, true)) {
            return true;
        }

        // Career duplicates like /career/childcare-teacher-2/
        if (preg_match('#^career/.+\-\d+$#', $normalized)) {
            return true;
        }

        if (class_exists('Chroma_URL_Consolidator') && method_exists('Chroma_URL_Consolidator', 'is_duplicate_path')) {
            return Chroma_URL_Consolidator::is_duplicate_path('/' . $normalized . '/');
        }

        return false;
    }
}

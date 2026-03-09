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
        // Ensure providers are registered after core sitemap bootstraps.
        add_action('init', [$this, 'register_providers'], 20);

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

    public function register_providers()
    {
        if (!function_exists('wp_register_sitemap_provider')) {
            return;
        }

        // One sitemap for all Spanish pages/posts (Singulars).
        wp_register_sitemap_provider('spanish', new Chroma_Spanish_Sitemap_Provider());

        // Ensure combo and near-me providers are always present in native index when available.
        if (class_exists('Chroma_Combo_Sitemap_Provider')) {
            wp_register_sitemap_provider('combos', new Chroma_Combo_Sitemap_Provider('en'));
            wp_register_sitemap_provider('combos-es', new Chroma_Combo_Sitemap_Provider('es'));
        }

        if (class_exists('Chroma_Near_Me_Sitemap_Provider')) {
            wp_register_sitemap_provider('near-me', new Chroma_Near_Me_Sitemap_Provider('en'));
            wp_register_sitemap_provider('near-me-es', new Chroma_Near_Me_Sitemap_Provider('es'));
        }
    }

    /**
     * Redirect legacy sitemap endpoints to native index.
     */
    public function handle_legacy_sitemap_aliases()
    {
        if (is_admin() || wp_doing_ajax() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
        if (!$request_uri) {
            return;
        }

        $request_uri = '/' . trim($request_uri, '/');

        $aliases = [
            '/sitemap.xml' => '/wp-sitemap.xml',
            '/sitemap_index.xml' => '/wp-sitemap.xml',
            '/sitemap-spanish.xml' => '/wp-sitemap-custom-spanish-1.xml',
            '/sitemap-combos.xml' => '/wp-sitemap-custom-combos-1.xml',
            '/sitemap-combos-es.xml' => '/wp-sitemap-custom-combos-es-1.xml',
            '/sitemap-near-me.xml' => '/wp-sitemap-custom-near-me-1.xml',
            '/sitemap-near-me-es.xml' => '/wp-sitemap-custom-near-me-es-1.xml',
        ];

        if (isset($aliases[$request_uri])) {
            wp_safe_redirect(home_url($aliases[$request_uri]), 301);
            exit;
        }
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
                'primary' => '/wp-sitemap-custom-spanish-1.xml',
                'aliases' => ['/sitemap-spanish.xml'],
            ],
            [
                'primary' => '/wp-sitemap-custom-combos-1.xml',
                'aliases' => ['/sitemap-combos.xml'],
            ],
            [
                'primary' => '/wp-sitemap-custom-combos-es-1.xml',
                'aliases' => ['/sitemap-combos-es.xml'],
            ],
            [
                'primary' => '/wp-sitemap-custom-near-me-1.xml',
                'aliases' => ['/sitemap-near-me.xml'],
            ],
            [
                'primary' => '/wp-sitemap-custom-near-me-es-1.xml',
                'aliases' => ['/sitemap-near-me-es.xml'],
            ],
        ];

        $last_mod = gmdate('c');
        foreach ($entries as $entry) {
            if ($this->sitemap_index_contains_any($sitemap_index, array_merge([$entry['primary']], $entry['aliases']))) {
                continue;
            }

            $loc = esc_url(home_url($entry['primary']));
            $sitemap_index .= '<sitemap><loc>' . $loc . '</loc><lastmod>' . $last_mod . '</lastmod></sitemap>';
        }

        return $sitemap_index;
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
class Chroma_Spanish_Sitemap_Provider extends WP_Sitemaps_Provider {
    
    public function __construct() {
        $this->name = 'spanish';
        $this->object_type = 'custom'; 
    }

    private $per_page = 2000;
    private $post_types = ['page', 'location', 'program', 'city', 'post', 'team_member'];
    private $excluded_paths = [
        'employers-2',
        'es/employers-2',
    ];

    public function get_url_list($page_num, $object_subtype = '') {
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


    public function get_max_num_pages($object_subtype = '') {
        $count = 0;
        foreach ($this->post_types as $type) {
            $count += (int)wp_count_posts($type)->publish;
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



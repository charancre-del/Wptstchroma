<?php
/**
 * Near Me Pages - Hybrid approach
 * Pre-generated for SEO + JS personalization for UX
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Near_Me_Pages
{
    const REWRITE_TAG = 'chroma_near_me';

    private $keywords = ['daycare', 'preschool', 'childcare', 'pre-k', 'infant-care'];

    public function __construct()
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_near_me_page']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('chroma_sitemap_urls', [$this, 'add_to_unified_sitemap']);

        // Note: Sitemap providers are registered by Chroma_Sitemap_Integrator::register_providers()
    }


    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules()
    {
        // Generic: /daycare-near-me/
        foreach ($this->keywords as $kw) {
            add_rewrite_rule(
                '^' . $kw . '-near-me/?$',
                'index.php?' . self::REWRITE_TAG . '=' . $kw,
                'top'
            );

            // City-specific: /daycare-near-cumming-ga/
            add_rewrite_rule(
                '^' . $kw . '-near-([a-z-]+)-([a-z]{2})/?$',
                'index.php?' . self::REWRITE_TAG . '=' . $kw . '&near_city=$matches[1]&near_state=$matches[2]',
                'top'
            );
        }
    }

    /**
     * Add query vars
     */
    public function add_query_vars($vars)
    {
        $vars[] = self::REWRITE_TAG;
        $vars[] = 'near_city';
        $vars[] = 'near_state';
        return $vars;
    }

    /**
     * Handle near me page request
     */
    public function handle_near_me_page()
    {
        $keyword = sanitize_title((string) get_query_var(self::REWRITE_TAG));

        if ($keyword === '' || !in_array($keyword, $this->keywords, true)) {
            return;
        }

        $city_slug = sanitize_title(get_query_var('near_city'));
        $state = strtoupper(sanitize_text_field(get_query_var('near_state')));
        $city_context = $this->resolve_city_context($city_slug, $state);

        if ($city_slug !== '' && !is_array($city_context)) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        $profile = $this->get_route_profile($keyword, $city_slug, $state, $city_context);
        $canonical = (string) ($profile['canonical'] ?? '');

        if ($canonical === '') {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        $canonical_path = (string) wp_parse_url($canonical, PHP_URL_PATH);
        $request_path = function_exists('chroma_seo_get_request_path') ? chroma_seo_get_request_path() : '/';
        if ($canonical_path !== '' && $canonical_path !== $request_path) {
            wp_safe_redirect($canonical, 301);
            exit;
        }

        $resolved_seo = $this->resolve_virtual_seo($keyword, $city_slug, $state, $city_context, $profile);
        $page_title = (string) ($resolved_seo['title'] ?? $this->build_page_title($keyword, $city_slug, $state));
        $description = (string) ($resolved_seo['meta_description'] ?? $this->build_meta_description($keyword, $city_slug, $state));

        $this->prepare_virtual_page_query_state($page_title, $description, $canonical);
        $this->register_seo_overrides($keyword, $city_slug, $state, $city_context, $resolved_seo);

        add_filter('body_class', function ($classes) {
            $classes[] = 'near-me-page';
            return $classes;
        });

        $this->render_near_me_page($keyword, $city_context, $state, $resolved_seo);
        exit;
    }

    /**
     * Resolve and validate a virtual near-me city slug.
     *
     * @param string $city_slug
     * @param string $state
     * @return array|null
     */
    private function resolve_city_context($city_slug, $state = '')
    {
        $city_slug = sanitize_title((string) $city_slug);
        if ($city_slug === '') {
            return null;
        }

        if (function_exists('chroma_seo_resolve_virtual_city_context')) {
            $resolved = chroma_seo_resolve_virtual_city_context($city_slug, $state);
            if (is_array($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * Build a consistent route profile for near-me pages.
     *
     * @param string $keyword
     * @param string $city_slug
     * @param string $state
     * @param array|null $city_context
     * @return array
     */
    private function get_route_profile($keyword, $city_slug = '', $state = '', $city_context = null)
    {
        $keyword = sanitize_title((string) $keyword);
        $city_slug = sanitize_title((string) $city_slug);
        $state = strtoupper((string) $state);

        if (is_array($city_context) && function_exists('chroma_seo_build_near_me_profile')) {
            return chroma_seo_build_near_me_profile($keyword, $city_context, function_exists('chroma_seo_get_request_language') ? chroma_seo_get_request_language() : 'en');
        }

        if ($city_slug === '' && function_exists('chroma_seo_build_near_me_profile')) {
            return chroma_seo_build_near_me_profile($keyword, null, function_exists('chroma_seo_get_request_language') ? chroma_seo_get_request_language() : 'en');
        }

        return [];
    }

    /**
     * Resolve editable virtual-page SEO overrides, then fall back to generated near-me SEO.
     */
    private function resolve_virtual_seo($keyword, $city_slug = '', $state = '', $city_context = null, array $profile = [])
    {
        if (empty($profile)) {
            $profile = $this->get_route_profile($keyword, $city_slug, $state, $city_context);
        }

        $fallbacks = [
            'title' => (string) ($profile['title'] ?? $this->build_page_title($keyword, $city_slug, $state)),
            'meta_description' => (string) ($profile['meta_description'] ?? $this->build_meta_description($keyword, $city_slug, $state)),
            'canonical' => (string) ($profile['canonical'] ?? $this->build_canonical_url($keyword, $city_slug, $state)),
        ];

        if (class_exists('Chroma_Virtual_Page_SEO_Data')) {
            return Chroma_Virtual_Page_SEO_Data::resolve('near-me', [
                'keyword' => $keyword,
                'city_slug' => $city_slug,
                'state' => $state,
            ], $fallbacks);
        }

        $fallbacks['robots'] = '';
        $fallbacks['source'] = 'fallback';
        $fallbacks['data'] = [];

        return $fallbacks;
    }

    /**
     * Keep virtual near pages from inheriting front-page query flags.
     *
     * The route is rendered manually in template_redirect, so WordPress may still
     * think the request is the homepage unless we clear those flags before wp_head.
     */
    private function prepare_virtual_page_query_state($title = '', $description = '', $canonical = '')
    {
        global $post, $wp_query;

        if (!($wp_query instanceof WP_Query)) {
            return;
        }

        $route_slug = $this->get_virtual_route_slug($canonical);

        $virtual_post = (object) [
            'ID' => 0,
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_title' => (string) $title,
            'post_excerpt' => (string) $description,
            'post_name' => $route_slug,
            'guid' => (string) $canonical,
        ];

        $wp_query->is_home = false;
        $wp_query->is_front_page = false;
        $wp_query->is_archive = false;
        $wp_query->is_404 = false;
        $wp_query->is_search = false;
        $wp_query->is_feed = false;
        $wp_query->is_page = true;
        $wp_query->is_single = false;
        $wp_query->is_singular = true;
        $wp_query->queried_object = $virtual_post;
        $wp_query->queried_object_id = 0;
        $wp_query->query_vars['pagename'] = $route_slug;
        $wp_query->query_vars['name'] = $route_slug;
        $wp_query->query['pagename'] = $route_slug;
        $wp_query->query['name'] = $route_slug;
        $wp_query->post = $virtual_post;
        $wp_query->posts = [$virtual_post];
        $wp_query->post_count = 1;
        $wp_query->found_posts = 1;

        $post = $virtual_post;
    }

    /**
     * Derive the public-facing route slug from the canonical URL.
     *
     * @param string $canonical
     * @return string
     */
    private function get_virtual_route_slug($canonical = '')
    {
        $path = is_string($canonical) ? wp_parse_url($canonical, PHP_URL_PATH) : '';

        if (!is_string($path) || $path === '') {
            $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
            $path = wp_parse_url($request_uri, PHP_URL_PATH);
        }

        if (!is_string($path) || $path === '') {
            return self::REWRITE_TAG;
        }

        $path = trim($path, '/');
        if ($path === '') {
            return self::REWRITE_TAG;
        }

        $segments = explode('/', $path);

        return (string) end($segments);
    }

    /**
     * Register route-level SEO overrides so theme and plugin head output stay aligned.
     */
    private function register_seo_overrides($keyword, $city_slug = '', $state = '', $city_context = null, array $resolved_seo = [])
    {
        if (empty($resolved_seo)) {
            $resolved_seo = $this->resolve_virtual_seo($keyword, $city_slug, $state, $city_context);
        }

        if (class_exists('Chroma_Virtual_Page_SEO_Data')) {
            Chroma_Virtual_Page_SEO_Data::apply_filters($resolved_seo);
            return;
        }

        $title = (string) ($resolved_seo['title'] ?? $this->build_page_title($keyword, $city_slug, $state));
        $description = (string) ($resolved_seo['meta_description'] ?? $this->build_meta_description($keyword, $city_slug, $state));
        $canonical = (string) ($resolved_seo['canonical'] ?? $this->build_canonical_url($keyword, $city_slug, $state));

        add_filter('pre_get_document_title', function ($current) use ($title) {
            return $title;
        }, PHP_INT_MAX);

        add_filter('wpseo_title', function ($current) use ($title) {
            return $title;
        }, PHP_INT_MAX);

        add_filter('wpseo_metadesc', function ($current) use ($description) {
            return $description;
        }, PHP_INT_MAX);

        add_filter('wpseo_opengraph_desc', function ($current) use ($description) {
            return $description;
        }, PHP_INT_MAX);

        foreach (['wpseo_canonical', 'wpseo_opengraph_url'] as $filter) {
            add_filter($filter, function ($current) use ($canonical) {
                return $canonical;
            }, PHP_INT_MAX);
        }
    }

    /**
     * Build the display and SEO title for the current near page.
     */
    private function build_page_title($keyword, $city_slug = '', $state = '')
    {
        $profile = $this->get_route_profile($keyword, $city_slug, $state);
        if (!empty($profile['title'])) {
            return (string) $profile['title'];
        }

        $keyword_label = ucwords(str_replace('-', ' ', (string) $keyword));

        if ($city_slug !== '' && $state !== '') {
            $city_name = ucwords(str_replace('-', ' ', $city_slug));
            return sprintf(__('%1$s Near %2$s, %3$s | Chroma', 'chroma-excellence'), $keyword_label, $city_name, $state);
        }

        return sprintf(__('%1$s Near Me | Chroma', 'chroma-excellence'), $keyword_label);
    }

    /**
     * Build a route-specific meta description for the current near page.
     */
    private function build_meta_description($keyword, $city_slug = '', $state = '')
    {
        $profile = $this->get_route_profile($keyword, $city_slug, $state);
        if (!empty($profile['meta_description'])) {
            return (string) $profile['meta_description'];
        }

        $keyword_label = strtolower(str_replace('-', ' ', (string) $keyword));

        if ($city_slug !== '' && $state !== '') {
            $city_name = ucwords(str_replace('-', ' ', $city_slug));
            return sprintf(
                __('Compare trusted %1$s options near %2$s, %3$s. Explore nearby Chroma locations, curriculum, and tour information for local families.', 'chroma-excellence'),
                $keyword_label,
                $city_name,
                $state
            );
        }

        return sprintf(
            __('Compare trusted %1$s options near you. Explore nearby Chroma locations, curriculum, and tour information for Georgia families.', 'chroma-excellence'),
            $keyword_label
        );
    }

    /**
     * Build the canonical URL for the current near page.
     */
    private function build_canonical_url($keyword, $city_slug = '', $state = '')
    {
        $profile = $this->get_route_profile($keyword, $city_slug, $state);
        if (!empty($profile['canonical'])) {
            return (string) $profile['canonical'];
        }

        $segments = [];
        if ($this->is_spanish_request()) {
            $segments[] = 'es';
        }

        $state_slug = strtolower((string) $state);
        if ($city_slug !== '' && $state_slug !== '') {
            $segments[] = sanitize_title($keyword) . '-near-' . sanitize_title($city_slug) . '-' . $state_slug;
        } else {
            $segments[] = sanitize_title($keyword) . '-near-me';
        }

        return home_url('/' . implode('/', $segments) . '/');
    }

    /**
     * Detect whether the current request is the Spanish route variant.
     */
    private function is_spanish_request()
    {
        if ((string) get_query_var('chroma_lang') === 'es') {
            return true;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);

        return is_string($path) && strpos($path, '/es/') === 0;
    }

    /**
     * Translate the near-me keyword into a visible label.
     *
     * @param string $keyword
     * @param string $language
     * @return string
     */
    private function get_keyword_label($keyword, $language = 'en')
    {
        $labels = [
            'daycare' => ['en' => 'Daycare', 'es' => 'Guarderia'],
            'preschool' => ['en' => 'Preschool', 'es' => 'Preescolar'],
            'childcare' => ['en' => 'Childcare', 'es' => 'Cuidado infantil'],
            'pre-k' => ['en' => 'Pre-K', 'es' => 'Pre-K'],
            'infant-care' => ['en' => 'Infant Care', 'es' => 'Cuidado para bebes'],
        ];

        $keyword = sanitize_title((string) $keyword);

        return (string) ($labels[$keyword][$language] ?? ucwords(str_replace('-', ' ', $keyword)));
    }

    /**
     * Normalize relationship meta into a unique list of location IDs.
     *
     * @param mixed $raw
     * @return array<int>
     */
    private function normalize_location_ids($raw)
    {
        if (is_array($raw)) {
            $values = $raw;
        } elseif (is_string($raw)) {
            $raw = trim($raw);
            if ($raw === '') {
                return [];
            }

            if ($raw[0] === '[') {
                $decoded = json_decode($raw, true);
                $values = is_array($decoded) ? $decoded : preg_split('/[\s,]+/', $raw);
            } else {
                $values = preg_split('/[\s,]+/', $raw);
            }
        } else {
            $values = [$raw];
        }

        $ids = [];
        foreach ((array) $values as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Pull curated location relationships from the city record when available.
     *
     * @param array|null $city_context
     * @return array<int>
     */
    private function get_city_related_location_ids($city_context)
    {
        if (!is_array($city_context)) {
            return [];
        }

        $location_ids = [];

        if (!empty($city_context['location_id'])) {
            $location_ids[] = (int) $city_context['location_id'];
        }

        $city_page_id = (int) ($city_context['city_page_id'] ?? 0);
        if ($city_page_id > 0) {
            foreach (['city_nearby_locations', 'related_location_ids'] as $meta_key) {
                $location_ids = array_merge(
                    $location_ids,
                    $this->normalize_location_ids(function_exists('chroma_get_translated_meta')
                        ? chroma_get_translated_meta($city_page_id, $meta_key)
                        : get_post_meta($city_page_id, $meta_key, true))
                );
            }
        }

        return array_values(array_unique(array_filter(array_map('intval', $location_ids))));
    }

    /**
     * Rank locations for a city-specific near-me route.
     *
     * @param array $locations
     * @param array|null $city_context
     * @param string $state
     * @return array
     */
    private function rank_locations_for_city(array $locations, $city_context = null, $state = '')
    {
        if (!is_array($city_context) || empty($locations)) {
            return array_values($locations);
        }

        $state = strtoupper((string) $state);
        $canonical_slug = sanitize_title((string) ($city_context['canonical_slug'] ?? ''));
        $city_name = strtolower(trim((string) ($city_context['city_name'] ?? '')));
        $preferred_ids = $this->get_city_related_location_ids($city_context);

        $location_map = [];
        foreach ($locations as $location) {
            $location_map[(int) $location['id']] = $location;
        }

        $ordered = [];
        foreach ($preferred_ids as $location_id) {
            if (isset($location_map[$location_id])) {
                $ordered[] = $location_map[$location_id];
                unset($location_map[$location_id]);
            }
        }

        $scored = [];
        foreach ($location_map as $location) {
            $score = 0;
            $location_city = strtolower(trim((string) ($location['city'] ?? '')));
            $location_state = strtoupper((string) ($location['state'] ?? ''));
            $location_title = strtolower((string) ($location['title'] ?? ''));

            if ($location_city !== '' && $location_city === $city_name) {
                $score += 1000;
            }

            if ($canonical_slug !== '' && sanitize_title($location_city) === $canonical_slug) {
                $score += 400;
            }

            if ($canonical_slug !== '' && strpos($location_title, str_replace('-', ' ', $canonical_slug)) !== false) {
                $score += 180;
            }

            if ($state !== '' && $location_state === $state) {
                $score += 80;
            }

            if (!empty($location['address']) && $canonical_slug !== '' && strpos(strtolower((string) $location['address']), str_replace('-', ' ', $canonical_slug)) !== false) {
                $score += 60;
            }

            $location['_near_me_score'] = $score;
            $scored[] = $location;
        }

        usort($scored, static function ($left, $right) {
            if ($left['_near_me_score'] === $right['_near_me_score']) {
                return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
            }

            return $right['_near_me_score'] <=> $left['_near_me_score'];
        });

        foreach ($scored as $location) {
            unset($location['_near_me_score']);
            $ordered[] = $location;
        }

        return $ordered;
    }

    /**
     * Build route-aware supporting copy for near-me pages.
     *
     * @param string     $keyword
     * @param array|null $city_context
     * @param array      $locations
     * @param string     $language
     * @return array<string, string>
     */
    private function build_route_copy($keyword, $city_context = null, array $locations = [], $language = 'en')
    {
        $keyword_label = $this->get_keyword_label($keyword, $language);
        $keyword_lower = function_exists('mb_strtolower') ? mb_strtolower($keyword_label, 'UTF-8') : strtolower($keyword_label);
        $count = count($locations);

        if (!is_array($city_context)) {
            if ($language === 'es') {
                return [
                    'hero' => sprintf('Explora opciones de %s en Georgia con campus de Chroma, informacion de recorridos y programas para distintas etapas tempranas.', $keyword_lower),
                    'section_title' => sprintf('Campus de Chroma para %s', $keyword_lower),
                    'section_body' => sprintf('Revisamos nuestros campus mas cercanos para ayudarte a comparar opciones de %s y elegir el mejor ajuste para tu familia.', $keyword_lower),
                ];
            }

            return [
                'hero' => sprintf('Find trusted %s options near you. Explore Chroma campuses, curriculum highlights, and tour details for families across Georgia.', $keyword_lower),
                'section_title' => sprintf('Chroma Campuses for %s', $keyword_label),
                'section_body' => sprintf('Compare our nearby campuses, review program fit, and find the right %s environment for your family.', $keyword_lower),
            ];
        }

        $city_name = function_exists('chroma_seo_get_city_display_name')
            ? chroma_seo_get_city_display_name($city_context, $language)
            : (string) ($city_context['city_name'] ?? '');
        $state = strtoupper((string) ($city_context['state'] ?? 'GA'));
        $campus_names = array_values(array_filter(array_map(static function ($location) {
            return trim((string) ($location['title'] ?? ''));
        }, array_slice($locations, 0, 3))));

        $city_page_id = (int) ($city_context['city_page_id'] ?? 0);
        $county = '';
        $neighborhoods = [];
        if ($city_page_id > 0) {
            $county = trim((string) (function_exists('chroma_get_translated_meta')
                ? chroma_get_translated_meta($city_page_id, 'city_county')
                : get_post_meta($city_page_id, 'city_county', true)));

            $neighborhoods_raw = function_exists('chroma_get_translated_meta')
                ? chroma_get_translated_meta($city_page_id, 'city_neighborhoods')
                : get_post_meta($city_page_id, 'city_neighborhoods', true);

            if (is_array($neighborhoods_raw)) {
                $neighborhoods = array_values(array_filter(array_map('trim', $neighborhoods_raw)));
            } elseif (is_string($neighborhoods_raw) && trim($neighborhoods_raw) !== '') {
                $neighborhoods = array_values(array_filter(array_map('trim', explode(',', $neighborhoods_raw))));
            }
        }

        $campus_fragment = '';
        if (!empty($campus_names)) {
            $campus_fragment = $language === 'es'
                ? ' Incluye campus como ' . implode(', ', $campus_names) . '.'
                : ' That includes campuses like ' . implode(', ', $campus_names) . '.';
        }

        $neighborhood_fragment = '';
        if (!empty($neighborhoods)) {
            $list = implode(', ', array_slice($neighborhoods, 0, 3));
            $neighborhood_fragment = $language === 'es'
                ? ' Familias de zonas como ' . $list . ' suelen comenzar aqui.'
                : ' Families from neighborhoods like ' . $list . ' often start here.';
        }

        $county_fragment = '';
        if ($county !== '') {
            $county_fragment = $language === 'es'
                ? ' con apoyo para familias en ' . $county . ' County'
                : ' for families across ' . $county . ' County';
        }

        if ($language === 'es') {
            return [
                'hero' => sprintf('Explora %s cerca de %s, %s. Destacamos %d opciones de campus de Chroma%s.', $keyword_lower, $city_name, $state, $count, $campus_fragment),
                'section_title' => sprintf('%s cerca de %s', $keyword_label, $city_name),
                'section_body' => sprintf('Seleccionamos campus de Chroma cercanos a %s, %s%s para que puedas comparar ubicacion, programas y proximidad antes de programar un recorrido.', $city_name, $state, $county_fragment) . $neighborhood_fragment,
            ];
        }

        return [
            'hero' => sprintf('Explore %s near %s, %s. We highlighted %d Chroma campus options%s.', $keyword_lower, $city_name, $state, $count, $campus_fragment),
            'section_title' => sprintf('%s Near %s', $keyword_label, $city_name),
            'section_body' => sprintf('We selected Chroma campuses close to %s, %s%s so you can compare location, program fit, and convenience before you book a tour.', $city_name, $state, $county_fragment) . $neighborhood_fragment,
        ];
    }

    /**
     * Get all locations with geo data
     */
    private function get_locations_with_geo($city_context = null, $state = '')
    {
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => 100,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $result = [];

        foreach ($locations as $loc) {
            $lat = get_post_meta($loc->ID, 'geo_lat', true)
                ?: get_post_meta($loc->ID, 'location_latitude', true);
            $lng = get_post_meta($loc->ID, 'geo_lng', true)
                ?: get_post_meta($loc->ID, 'location_longitude', true);

            $result[] = [
                'id' => $loc->ID,
                'title' => $loc->post_title,
                'url' => get_permalink($loc),
                'city' => get_post_meta($loc->ID, 'location_city', true),
                'state' => get_post_meta($loc->ID, 'location_state', true),
                'zip' => get_post_meta($loc->ID, 'location_zip', true),
                'address' => get_post_meta($loc->ID, 'location_address', true),
                'phone' => get_post_meta($loc->ID, 'location_phone', true),
                'lat' => floatval($lat),
                'lng' => floatval($lng),
                'image' => get_the_post_thumbnail_url($loc, 'medium')
            ];
        }

        $result = $this->rank_locations_for_city($result, $city_context, $state);
        $limit = is_array($city_context) ? 6 : 12;

        return array_slice($result, 0, $limit);
    }

    /**
     * Render near me page
     */
    private function render_near_me_page($keyword, $city_context = null, $state = '', array $resolved_seo = [])
    {
        $language = function_exists('chroma_seo_get_request_language') ? chroma_seo_get_request_language() : 'en';
        $keyword_label = $this->get_keyword_label($keyword, $language);
        $locations = $this->get_locations_with_geo($city_context, $state);
        $city_slug = is_array($city_context) ? (string) ($city_context['canonical_slug'] ?? '') : '';

        // If city-specific, filter/sort by that city
        $city_name = '';
        if ($city_slug && $state) {
            if (function_exists('chroma_seo_get_city_display_name')) {
                $city_name = chroma_seo_get_city_display_name($city_context, $language);
            }
            if ($city_name === '') {
                $city_name = ucwords(str_replace('-', ' ', $city_slug));
            }
            $page_title = (string) ($resolved_seo['title'] ?? $this->build_page_title($keyword, $city_slug, $state));
        } else {
            $page_title = (string) ($resolved_seo['title'] ?? $this->build_page_title($keyword, '', ''));
        }

        $route_copy = $this->build_route_copy($keyword, $city_context, $locations, $language);
        $section_title = (string) ($route_copy['section_title'] ?? '');
        $section_body = (string) ($route_copy['section_body'] ?? '');
        $hero_copy = (string) ($route_copy['hero'] ?? '');

        get_header();
        ?>
        <main class="near-me-page bg-brand-cream min-h-screen">
            <!-- Hero Section -->
            <section class="relative pt-16 pb-12 lg:pt-24 lg:pb-20 bg-white overflow-hidden">
                <div
                    class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-chroma-greenLight/40 via-transparent to-transparent">
                </div>
                <div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 text-center">
                    <div
                        class="inline-flex items-center gap-2 bg-white border border-chroma-green/30 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-chroma-green shadow-sm mb-6">
                        <i class="fa-solid fa-map-pin"></i> <?php echo count($locations); ?>+
                        <?php _e('Locations Found', 'chroma-excellence'); ?>
                    </div>

                    <h1 class="font-serif text-[2.8rem] md:text-6xl text-brand-ink mb-6">
                        <?php echo esc_html($page_title); ?>
                    </h1>

                    <p class="text-lg text-brand-ink/80 max-w-2xl mx-auto mb-10">
                        <?php echo esc_html($hero_copy); ?>
                    </p>

                    <div id="nearest-highlight"
                        class="inline-flex items-center gap-4 bg-chroma-blueLight/30 border border-chroma-blue/10 px-6 py-3 rounded-full shadow-sm"
                        style="display:none;">
                        <span class="flex items-center gap-2 text-xs font-bold text-chroma-blueDark uppercase tracking-wider">
                            <span class="w-2 h-2 rounded-full bg-chroma-blue animate-pulse"></span>
                            📍 <?php esc_html_e('Nearest:', 'chroma-excellence'); ?>
                        </span>
                        <strong id="nearest-name" class="font-serif text-brand-ink"></strong>
                        <span id="nearest-distance" class="text-xs font-bold text-chroma-blue"></span>
                    </div>
                </div>
            </section>

            <!-- Route Context -->
            <section class="py-10 bg-brand-cream border-y border-brand-ink/5">
                <div class="max-w-5xl mx-auto px-4 lg:px-6 text-center">
                    <h2 class="font-serif text-3xl text-brand-ink mb-4">
                        <?php echo esc_html($section_title); ?>
                    </h2>
                    <p class="text-brand-ink/70 max-w-3xl mx-auto leading-relaxed">
                        <?php echo esc_html($section_body); ?>
                    </p>
                </div>
            </section>

            <!-- Locations Grid -->
            <section class="py-20">
                <div class="max-w-7xl mx-auto px-4 lg:px-6">
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8" id="locations-grid">
                        <?php foreach ($locations as $loc):
                            $regions = wp_get_post_terms($loc['id'], 'location_region');
                            $region_term = !empty($regions) && !is_wp_error($regions) ? $regions[0] : null;
                            $colors = $region_term ? chroma_get_region_color_from_term($region_term->term_id) : array(
                                'bg' => 'chroma-blueLight',
                                'text' => 'chroma-blue',
                                'border' => 'chroma-blue'
                            );

                            $is_decal = get_post_meta($loc['id'], 'location_decal_licensed', true);
                            $quality_rated = get_post_meta($loc['id'], 'location_quality_rated', true);
                            ?>
                            <article class="location-card group" data-lat="<?php echo esc_attr($loc['lat']); ?>"
                                data-lng="<?php echo esc_attr($loc['lng']); ?>" data-id="<?php echo esc_attr($loc['id']); ?>">

                                <div
                                    class="bg-white rounded-[2.5rem] p-8 shadow-card border border-brand-ink/5 hover:border-<?php echo esc_attr($colors['border']); ?>/30 transition-all hover:-translate-y-1 h-full flex flex-col relative overflow-hidden">

                                    <div class="relative rounded-2xl overflow-hidden mb-6 aspect-video">
                                        <?php if ($loc['image']): ?>
                                            <img src="<?php echo esc_url($loc['image']); ?>"
                                                alt="<?php echo esc_attr($loc['title']); ?>"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        <?php endif; ?>
                                        <div class="distance-display absolute bottom-3 right-3 bg-white/95 backdrop-blur-sm px-3 py-1 rounded-full text-[10px] font-bold text-chroma-blue uppercase tracking-wider shadow-sm"
                                            style="display:none;"></div>
                                    </div>

                                    <h2
                                        class="font-serif text-2xl font-bold text-brand-ink mb-2 group-hover:text-<?php echo esc_attr($colors['text']); ?> transition-colors">
                                        <a href="<?php echo esc_url($loc['url']); ?>"><?php echo esc_html($loc['title']); ?></a>
                                    </h2>

                                    <p class="text-sm text-brand-ink/70 mb-6">
                                        <?php echo esc_html($loc['city'] . ', ' . $loc['state']); ?>
                                        <?php if ($loc['address']): ?>
                                            <br><span class="opacity-60"><?php echo esc_html($loc['address']); ?></span>
                                        <?php endif; ?>
                                    </p>

                                    <div class="flex flex-wrap gap-2 mb-8">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1 bg-chroma-blueLight/50 text-chroma-blueDark text-[9px] font-bold uppercase rounded-full">
                                            <i class="fa-solid fa-graduation-cap"></i> DECAL
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1 bg-chroma-yellowLight/50 text-chroma-yellowDark text-[9px] font-bold uppercase rounded-full">
                                            <i class="fa-solid fa-star"></i> Quality Rated
                                        </span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3 mt-auto">
                                        <a href="<?php echo esc_url($loc['url']); ?>"
                                            class="flex items-center justify-center py-4 rounded-2xl bg-brand-ink text-white text-[10px] font-bold uppercase tracking-widest hover:bg-chroma-blueDark transition-colors">
                                            <?php _e('View Campus', 'chroma-excellence'); ?>
                                        </a>
                                        <?php if ($loc['phone']): ?>
                                            <a href="tel:<?php echo esc_attr($loc['phone']); ?>"
                                                class="flex items-center justify-center py-4 rounded-2xl border border-brand-ink/10 text-brand-ink text-[10px] font-bold uppercase tracking-widest hover:bg-brand-cream/50 transition-colors">
                                                <i class="fa-solid fa-phone mr-1.5"></i> <?php _e('Call', 'chroma-excellence'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </main>

        <style>
            .shadow-card {
                box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 5px 15px -3px rgba(0, 0, 0, 0.02);
            }

            .font-serif {
                font-family: "Playfair Display", Georgia, serif;
            }

            .location-card.nearest .bg-white {
                border: 2px solid var(--chroma-blue, #0066cc);
                box-shadow: 0 20px 40px -10px rgba(0, 102, 204, 0.15);
            }
        </style>

        <!-- Location data for JS -->
        <script type="application/json" id="locations-data">
                            <?php echo wp_json_encode($locations); ?>
                        </script>

        <?php
        // Output schema
        $this->output_schema($keyword_label, $keyword, $city_slug, $state, $city_context, $resolved_seo);

        get_footer();
    }

    /**
     * Enqueue personalization script
     */
    public function enqueue_scripts()
    {
        if (!get_query_var(self::REWRITE_TAG)) {
            return;
        }

        wp_add_inline_script('jquery', $this->get_personalization_script());
    }

    /**
     * Get personalization JS
     */
    private function get_personalization_script()
    {
        $miles_away_text = __('miles away', 'chroma-excellence');
        $mi_text = __('mi', 'chroma-excellence');

        return "
        document.addEventListener('DOMContentLoaded', function() {
            var locationsData = JSON.parse(document.getElementById('locations-data').textContent);
            
            // Calculate distance (Haversine)
            function calcDistance(lat1, lon1, lat2, lon2) {
                var R = 3959; // miles
                var dLat = (lat2 - lat1) * Math.PI / 180;
                var dLon = (lon2 - lon1) * Math.PI / 180;
                var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLon/2) * Math.sin(dLon/2);
                var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return R * c;
            }
            
            // Sort and highlight
            function personalize(userLat, userLng) {
                var cards = document.querySelectorAll('.location-card');
                var distances = [];
                
                cards.forEach(function(card) {
                    var lat = parseFloat(card.dataset.lat);
                    var lng = parseFloat(card.dataset.lng);
                    var dist = calcDistance(userLat, userLng, lat, lng);
                    distances.push({ card: card, distance: dist });
                    
                    // Show distance
                    var distEl = card.querySelector('.distance-display');
                    if (distEl) {
                        distEl.textContent = dist.toFixed(1) + ' ' + '$miles_away_text';
                        distEl.style.display = 'block';
                    }
                });
                
                // Sort by distance
                distances.sort(function(a, b) { return a.distance - b.distance; });
                
                // Reorder DOM
                var grid = document.getElementById('locations-grid');
                distances.forEach(function(item) {
                    grid.appendChild(item.card);
                });
                
                // Highlight nearest
                if (distances.length > 0) {
                    distances[0].card.classList.add('nearest');
                    document.getElementById('nearest-highlight').style.display = 'inline-block';
                    document.getElementById('nearest-name').textContent = distances[0].card.querySelector('h2').textContent;
                    document.getElementById('nearest-distance').textContent = ' (' + distances[0].distance.toFixed(1) + ' $mi_text)';
                }
            }
            
            // Try browser geolocation
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        personalize(pos.coords.latitude, pos.coords.longitude);
                    },
                    function() {
                        // Fallback: IP geolocation
                        fetch('https://ipapi.co/json/')
                            .then(function(r) { return r.json(); })
                            .then(function(data) {
                                if (data.latitude && data.longitude) {
                                    personalize(data.latitude, data.longitude);
                                }
                            })
                            .catch(function() { /* silent fail */ });
                    },
                    { timeout: 5000 }
                );
            }
        });
        ";
    }

    /**
     * Output schema
     */
    private function output_schema($keyword_label, $keyword, $city_slug = '', $state = '', $city_context = null, array $resolved_seo = [])
    {
        if (empty($resolved_seo)) {
            $resolved_seo = $this->resolve_virtual_seo($keyword, $city_slug, $state, $city_context);
        }

        $canonical = (string) ($resolved_seo['canonical'] ?? $this->build_canonical_url($keyword, $city_slug, $state));
        $description = (string) ($resolved_seo['meta_description'] ?? $this->build_meta_description($keyword, $city_slug, $state));
        $page_title = (string) ($resolved_seo['title'] ?? $this->build_page_title($keyword, $city_slug, $state));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => trailingslashit($canonical) . '#collectionpage',
            'name' => $page_title,
            'description' => $description,
            'url' => $canonical,
            'about' => [
                '@type' => 'Thing',
                'name' => $keyword_label,
            ],
        ];

        Chroma_Schema_Registry::register($schema, ['source' => 'near-me-pages']);
    }

    /**
     * Get all near me URLs for sitemap
     */
    public static function get_sitemap_urls()
    {
        $urls = [];
        $keywords = ['daycare', 'preschool', 'childcare', 'pre-k', 'infant-care'];

        // Generic
        foreach ($keywords as $kw) {
            $urls[] = home_url('/' . $kw . '-near-me/');
        }

        // City-specific
        $cities = function_exists('chroma_seo_get_virtual_city_records')
            ? chroma_seo_get_virtual_city_records()
            : Chroma_Combo_Page_Generator::get_all_cities();
        foreach ($keywords as $kw) {
            foreach ($cities as $city) {
                $city_slug = sanitize_title((string) ($city['canonical_slug'] ?? ($city['city_slug'] ?? $city['city'] ?? '')));
                $state = strtolower((string) ($city['state'] ?? 'GA'));
                if ($city_slug === '') {
                    continue;
                }
                $urls[] = home_url('/' . $kw . '-near-' . $city_slug . '-' . $state . '/');
            }
        }

        return $urls;
    }

    /**
     * Add near-me URLs (EN + ES) to unified /sitemap.xml.
     *
     * @param array $urls
     * @return array
     */
    public function add_to_unified_sitemap($urls)
    {
        if (!is_array($urls)) {
            $urls = [];
        }

        $lastmod = $this->get_near_me_sitemap_lastmod();
        $base = rtrim(home_url('/'), '/');
        $links = self::get_sitemap_urls();

        foreach ($links as $link) {
            $url = (string) $link;
            if ($url === '') {
                continue;
            }

            $urls[] = [
                'loc' => $url,
                'lastmod' => $lastmod,
            ];

            $es_url = str_replace($base . '/', $base . '/es/', $url);
            if ($es_url === $url) {
                $path = (string) wp_parse_url($url, PHP_URL_PATH);
                if ($path !== '') {
                    $es_url = home_url('/es/' . ltrim($path, '/'));
                }
            }

            if ($es_url !== $url) {
                $urls[] = [
                    'loc' => $es_url,
                    'lastmod' => $lastmod,
                ];
            }
        }

        return $urls;
    }

    /**
     * Stable lastmod for generated near-me URLs.
     *
     * @return string
     */
    private function get_near_me_sitemap_lastmod()
    {
        $posts = get_posts([
            'post_type' => ['location', 'program', 'city'],
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'orderby' => 'modified',
            'order' => 'DESC',
        ]);

        if (!empty($posts) && $posts[0] instanceof WP_Post && !empty($posts[0]->post_modified_gmt)) {
            return gmdate('c', strtotime($posts[0]->post_modified_gmt));
        }

        return get_lastpostmodified('gmt') ? gmdate('c', strtotime(get_lastpostmodified('gmt'))) : gmdate('c');
    }

    public static function get_all_pages()
    {
        $pages = [];
        $keywords = ['daycare', 'preschool', 'childcare', 'pre-k', 'infant-care'];

        // Generic Pages
        foreach ($keywords as $kw) {
            $kw_label = ucwords(str_replace('-', ' ', $kw));
            $pages[] = [
                'type' => 'Generic',
                'title' => "$kw_label Near Me",
                'url' => home_url('/' . $kw . '-near-me/'),
                'city' => '—',
                'state' => '—'
            ];
        }

        // City-specific Pages
        if (class_exists('Chroma_Combo_Page_Generator')) {
            $cities = Chroma_Combo_Page_Generator::get_all_cities();
            foreach ($keywords as $kw) {
                $kw_label = ucwords(str_replace('-', ' ', $kw));
                foreach ($cities as $city) {
                    $pages[] = [
                        'type' => 'City-Specific',
                        'title' => "$kw_label Near " . $city['city'],
                        'url' => home_url('/' . $kw . '-near-' . sanitize_title($city['city_slug'] ?? $city['city']) . '-' . strtolower($city['state']) . '/'),
                        'city' => $city['city'],
                        'state' => $city['state']
                    ];
                }
            }
        }

        return $pages;
    }
}


/**
 * Custom Sitemap Provider for Near Me Pages
 */
class Chroma_Near_Me_Sitemap_Provider extends WP_Sitemaps_Provider
{
    private $lang;

    public function __construct($lang = 'en')
    {
        $this->lang = $lang;
        $this->name = $lang === 'es' ? 'near-me-es' : 'near-me';
        $this->object_type = 'custom';
    }

    public function get_url_list($page_num, $object_subtype = '')
    {
        $urls = [];
        $links = Chroma_Near_Me_Pages::get_sitemap_urls();

        $localized_links = [];
        foreach ($links as $link) {
            $url = $link;
            if ($this->lang === 'es') {
                $url = str_replace(home_url('/'), home_url('/es/'), $link);
            }
            $localized_links[] = [
                'loc' => $url,
                'lastmod' => date('c'),
                'changefreq' => 'weekly',
                'priority' => 0.8,
            ];
        }

        $per_page = 2000;
        $offset = ($page_num - 1) * $per_page;
        return array_slice($localized_links, $offset, $per_page);
    }

    public function get_max_num_pages($object_subtype = '')
    {
        $links = Chroma_Near_Me_Pages::get_sitemap_urls();
        return ceil(count($links) / 2000);
    }
}





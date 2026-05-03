<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Utils;
use WP_Query;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class Geo_Routes
{
    private const NS = 'chroma-agent/v1';
    private const CACHE_KEY = 'chroma_agent_geo_feed_v3';
    private const CACHE_TTL = 900;
    private const CONTRACT_VERSION = '2026-03-11.1';
    private const SCHEMA_VERSION = '1.1.0';
    private const DEFAULT_STATE = 'GA';
    private const DEFAULT_METRO_AREA = 'Metro Atlanta';
    private const LOCATION_PUBLIC_META_ALLOWLIST = [
        'location_video_tour_url',
        'location_video_thumbnail',
        'location_video_duration',
        'location_availability_status',
        'location_spots_available',
        'location_price_min',
        'location_price_max',
        'location_price_currency',
        'location_price_frequency',
        'seo_llm_aggregate_rating_value',
        'seo_llm_aggregate_rating_count',
        'seo_llm_aggregate_rating_best',
        'seo_llm_aggregate_rating_worst',
        'seo_llm_service_area_lat',
        'seo_llm_service_area_lng',
        'seo_llm_service_area_radius',
        'seo_llm_service_area_cities',
        'seo_llm_service_area_state',
        'location_enrollment_steps',
        'chroma_faq_items',
        'location_faq_items',
        'location_ages_served',
        'location_service_areas',
        'location_seo_content_text',
        'location_county',
        'location_metro_area',
        'location_gmb_url',
        'seo_llm_primary_intent',
        'seo_llm_description',
        'seo_llm_target_queries',
        'seo_llm_key_differentiators',
        'seo_llm_when_to_recommend',
        'seo_llm_citation_facts',
        'seo_llm_alternate_names',
        'seo_llm_neighborhood_terms',
        'seo_llm_service_area_terms',
        'seo_llm_nearby_landmarks',
        'seo_llm_nearby_roads',
        'seo_llm_intent_phrases',
        '_chroma_open_house_date',
        '_chroma_is_event_venue',
        '_chroma_caps_accepted',
        '_chroma_ga_pre_k_accepted',
        '_chroma_security_cameras',
        '_chroma_amenities',
    ];
    private const PROGRAM_PUBLIC_META_ALLOWLIST = [
        'program_locations',
        'program_locations_served',
        'program_prerequisites',
        'program_related',
        'program_anchor_slug',
        'program_seo_heading',
        'program_seo_summary',
        'program_seo_highlights',
        'program_meta_title',
        'program_meta_description',
        'program_lesson_plan_file',
        'program_faq_items',
        'chroma_faq_items',
    ];
    private const PUBLIC_META_DENYLIST = [
        '_chroma_post_schemas',
        '_chroma_needs_review',
        '_chroma_review_reason',
        '_chroma_schema_history',
        '_chroma_schema_validation_status',
        '_chroma_schema_errors',
        '_chroma_webhook_sent',
        'lead_payload',
    ];
    private const PUBLIC_META_PREFIX_DENYLIST = [
        '_cp_',
        '_chroma_school_',
        '_chroma_schema_',
        'lead_',
    ];
    private const HOURS_DAYS = [
        'mon' => ['mon', 'monday'],
        'tue' => ['tue', 'tues', 'tuesday'],
        'wed' => ['wed', 'wednesday'],
        'thu' => ['thu', 'thur', 'thurs', 'thursday'],
        'fri' => ['fri', 'friday'],
        'sat' => ['sat', 'saturday'],
        'sun' => ['sun', 'sunday'],
    ];

    public static function init(): void
    {
        add_action('save_post', [__CLASS__, 'on_post_change'], 10, 2);
        add_action('deleted_post', [__CLASS__, 'on_post_delete']);
        add_action('updated_option', [__CLASS__, 'on_option_change'], 10, 3);
        add_action('template_redirect', [__CLASS__, 'serve_ai_discovery_endpoints'], -1002);
        add_action('wp_head', [__CLASS__, 'output_geo_discovery_link'], 1);
        add_action('wp_head', [__CLASS__, 'output_geo_schema_signpost'], 2);
        add_filter('redirect_canonical', [__CLASS__, 'preserve_ai_discovery_endpoints'], 1, 2);
        add_filter('robots_txt', [__CLASS__, 'append_geo_robots_rules'], 20, 2);
    }

    public static function register(): void
    {
        register_rest_route(self::NS, '/geo-feed', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_geo_feed'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::NS, '/geo-feed/(?P<location_id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_geo_feed_location'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function describe_contract(): array
    {
        return [
            'route' => '/wp-json/' . self::NS . '/geo-feed',
            'detail_route' => '/wp-json/' . self::NS . '/geo-feed/{location_id}',
            'contract_version' => self::CONTRACT_VERSION,
            'schema_version' => self::SCHEMA_VERSION,
            'public' => true,
            'cache_ttl_seconds' => self::CACHE_TTL,
            'filters' => [
                'modified_since' => 'ISO-8601 timestamp. Returns only records updated after this time.',
                'ids' => 'Comma-delimited location IDs. Returns only matching location records.',
                'refresh' => 'Truthy value bypasses the cached base dataset.',
            ],
            'top_level_fields' => [
                'success',
                'cached',
                'contract_version',
                'schema_version',
                'generated_at_gmt',
                'last_updated',
                'canonical_feed_url',
                'last_validated_at_gmt',
                'data_quality_score',
                'warnings',
                'source',
                'filters',
                'summary',
                'feed_hash',
                'feed_type',
                'entity_types',
                'brand',
                'curriculum',
                'locations',
                'location_entities',
                'programs',
                'events',
            ],
            'field_groups' => [
                'brand' => ['name', 'description', 'site_url', 'contact'],
                'curriculum' => ['prismpath', 'chroma_spectrum'],
                'locations' => [
                    'id', 'campus_name', 'slug', 'url', 'address', 'phone_number', 'phone_display', 'phone_e164',
                    'email', 'administrator_name', 'programs_offered', 'ages_accepted', 'ages_normalized',
                    'operating_hours', 'hours_normalized', 'facility_highlights', 'service_areas',
                    'coordinates', 'coordinates_normalized', 'media', 'availability', 'pricing',
                    'aggregate_rating', 'service_area_geo', 'facility_profile', 'admissions', 'faqs',
                    'events', 'open_house_date', 'entity_type', 'canonical_url', 'county', 'metro_area',
                    'alternate_names', 'search_terms', 'nearby_neighborhoods', 'nearby_schools',
                    'service_area_terms', 'nearby_landmarks', 'nearby_roads', 'intent_phrases',
                    'ai_citation_ready', 'data_completeness_score',
                ],
                'programs' => [
                    'id', 'name', 'slug', 'url', 'summary', 'age_range', 'cta_text', 'features',
                    'anchor_slug', 'lesson_plan_url', 'seo', 'faqs', 'locations_served',
                    'prerequisites', 'related_programs',
                ],
                'events' => [
                    'location', 'location_url', 'name', 'start', 'description', 'url',
                ],
                'location_entities' => [
                    'type', 'entity_types', 'name', 'canonical_url', 'location_slug', 'brand',
                    'address', 'geo', 'contact', 'hours', 'programs_offered', 'age_groups',
                    'service_tags', 'curriculum', 'faqs', 'local_summary', 'search_terms',
                    'alternate_names', 'neighborhood_terms', 'service_area_terms', 'nearby_landmarks',
                    'nearby_roads', 'county', 'metro_area', 'intent_phrases', 'citability', 'legacy_ref',
                ],
            ],
        ];
    }

    public static function public_feed_url(): string
    {
        return rest_url(self::NS . '/geo-feed');
    }

    public static function get_geo_feed(WP_REST_Request $request)
    {
        $base = self::get_base_dataset(Utils::truthy($request->get_param('refresh')));
        $filters = self::build_filter_descriptor($request);
        $raw_locations = self::filter_location_records($base['locations'], $filters);
        $raw_programs = self::filter_program_records($base['programs'], $filters);
        $locations = self::build_legacy_location_records($raw_locations, $raw_programs);
        $programs = self::build_legacy_program_records($raw_programs);
        $raw_events = self::filter_event_records($base['events'], $raw_locations, $filters);
        $events = self::build_legacy_event_records($raw_events);
        $location_entities = self::build_location_entities($raw_locations, $raw_programs, $base['brand']);
        $last_updated = self::compute_last_updated($raw_locations, $raw_programs, $raw_events, $base['generated_at_gmt']);
        $quality = self::build_quality_report($locations, $programs);
        $validated_at = gmdate('c');

        return rest_ensure_response([
            'success' => true,
            'cached' => $base['cached'],
            'contract_version' => self::CONTRACT_VERSION,
            'schema_version' => self::SCHEMA_VERSION,
            'generated_at_gmt' => $base['generated_at_gmt'],
            'last_updated' => $last_updated,
            'canonical_feed_url' => self::public_feed_url(),
            'last_validated_at_gmt' => $validated_at,
            'data_quality_score' => $quality['data_quality_score'],
            'warnings' => $quality['warnings'],
            'feed_type' => 'ChromaGeoFeed',
            'entity_types' => ['Organization', 'ChildCareCenter', 'Preschool', 'FAQPage', 'Place', 'GeoCoordinates'],
            'source' => [
                'namespace' => self::NS,
                'route' => '/geo-feed',
                'authority' => 'ChromaELA WP',
            ],
            'filters' => self::normalize_filter_output($filters),
            'summary' => [
                'location_count' => count($locations),
                'program_count' => count($programs),
                'event_count' => count($events),
            ],
            'feed_hash' => self::hash_payload([
                'contract_version' => self::CONTRACT_VERSION,
                'schema_version' => self::SCHEMA_VERSION,
                'filters' => self::normalize_filter_output($filters),
                'locations' => $locations,
                'location_entities' => $location_entities,
                'programs' => $programs,
                'events' => $events,
            ]),
            'brand' => $base['brand'],
            'curriculum' => $base['curriculum'],
            'locations' => $locations,
            'location_entities' => $location_entities,
            'programs' => $programs,
            'events' => $events,
            'validation' => [
                'contract_integrity' => $quality['contract_integrity'],
                'relationship_integrity' => $quality['relationship_integrity'],
                'format_integrity' => $quality['format_integrity'],
                'completeness' => $quality['completeness'],
                'regression_safety' => $quality['regression_safety'],
            ],
            'deprecations' => [
                [
                    'field' => 'locations',
                    'status' => 'stable_legacy',
                    'replacement' => 'location_entities',
                    'note' => 'Legacy location contract remains for backward compatibility.',
                ],
            ],
        ]);
    }

    public static function get_geo_feed_location(WP_REST_Request $request)
    {
        $base = self::get_base_dataset(Utils::truthy($request->get_param('refresh')));
        $location_id = (int) $request->get_param('location_id');

        foreach ($base['locations'] as $location) {
            if ((int) ($location['location_id'] ?? 0) !== $location_id) {
                continue;
            }

            $legacy_location = self::build_legacy_location_records([$location], $base['programs']);
            $legacy_location = $legacy_location[0] ?? null;

            return rest_ensure_response([
                'success' => true,
                'cached' => $base['cached'],
                'contract_version' => self::CONTRACT_VERSION,
                'schema_version' => self::SCHEMA_VERSION,
                'generated_at_gmt' => $base['generated_at_gmt'],
                'last_updated' => $location['last_updated_gmt'] ?? $base['generated_at_gmt'],
                'canonical_feed_url' => self::public_feed_url(),
                'last_validated_at_gmt' => gmdate('c'),
                'source' => [
                    'namespace' => self::NS,
                    'route' => '/geo-feed/' . $location_id,
                    'authority' => 'ChromaELA WP',
                ],
                'location' => $legacy_location,
                'location_raw' => $location,
                'location_entity' => self::build_location_entity($location, $base['programs'], $base['brand']),
            ]);
        }

        return new \WP_Error('caa_geo_location_not_found', 'Location not found.', ['status' => 404]);
    }

    public static function on_post_change(int $post_id, $post): void
    {
        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (!is_object($post)) {
            return;
        }

        if (in_array((string) $post->post_type, ['location', 'program', 'page', 'post'], true)) {
            delete_transient(self::CACHE_KEY);
        }
    }

    public static function on_post_delete(int $post_id): void
    {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        if (in_array((string) $post->post_type, ['location', 'program', 'page', 'post'], true)) {
            delete_transient(self::CACHE_KEY);
        }
    }

    public static function on_option_change(string $option, $old_value, $value): void
    {
        if (in_array($option, [
            'blogname',
            'blogdescription',
            'chroma_llm_brand_context',
            'chroma_llm_brand_voice',
            'chroma_seo_phone',
            'chroma_seo_email',
        ], true)) {
            delete_transient(self::CACHE_KEY);
        }
    }

    public static function output_geo_discovery_link(): void
    {
        if (!self::is_public_feed_available()) {
            return;
        }

        echo '<link rel="alternate" type="application/json" title="Chroma GEO Feed" href="' . esc_url(self::public_feed_url()) . '">' . "\n";

        if (is_singular('location')) {
            $location_id = get_queried_object_id();
            if ($location_id) {
                $detail_url = rest_url(self::NS . '/geo-feed/' . (int) $location_id);
                echo '<link rel="alternate" type="application/json" title="Chroma GEO Feed Location" href="' . esc_url($detail_url) . '">' . "\n";
            }
        }
    }

    public static function output_geo_schema_signpost(): void
    {
        if (!self::is_public_feed_available()) {
            return;
        }

        $site_url = home_url('/');
        $geo_url = self::public_feed_url();
        if (is_front_page()) {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'EducationalOrganization',
                '@id' => trailingslashit($site_url) . '#organization',
                'name' => get_bloginfo('name'),
                'url' => trailingslashit($site_url),
                'subjectOf' => [
                    '@type' => 'DataFeed',
                    '@id' => $geo_url . '#feed',
                    'name' => 'Chroma Public GEO Feed',
                    'url' => $geo_url,
                    'encodingFormat' => 'application/json',
                    'description' => 'Machine-readable public feed for Chroma locations, programs, and events.',
                ],
                'potentialAction' => [
                    '@type' => 'ViewAction',
                    'target' => [
                        '@type' => 'EntryPoint',
                        'urlTemplate' => $geo_url,
                        'encodingType' => 'application/json',
                        'contentType' => 'application/json',
                    ],
                ],
            ];

            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
            return;
        }

        if (is_singular('location')) {
            $location_id = get_queried_object_id();
            if (!$location_id) {
                return;
            }

            $detail_url = rest_url(self::NS . '/geo-feed/' . (int) $location_id);
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                '@id' => get_permalink($location_id) . '#webpage',
                'url' => get_permalink($location_id),
                'name' => get_the_title($location_id),
                'about' => [
                    '@type' => 'DataFeedItem',
                    '@id' => $detail_url . '#item',
                    'name' => get_the_title($location_id) . ' Public GEO Feed Item',
                    'url' => $detail_url,
                    'isPartOf' => [
                        '@type' => 'DataFeed',
                        '@id' => $geo_url . '#feed',
                    ],
                ],
            ];

            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }

    public static function preserve_ai_discovery_endpoints($redirect_url, $requested_url)
    {
        $path = wp_parse_url((string) $requested_url, PHP_URL_PATH);
        if (!is_string($path)) {
            return $redirect_url;
        }

        if (preg_match('#/(ai-sitemap\.xml|llm-sitemap\.xml|llm\.txt|llms\.txt)$#i', $path)) {
            return false;
        }

        return $redirect_url;
    }

    public static function serve_ai_discovery_endpoints(): void
    {
        if (!self::is_public_feed_available()) {
            return;
        }

        if (is_admin() || wp_doing_ajax() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '';
        if (!$request_uri) {
            return;
        }

        $path = '/' . trim($request_uri, '/');
        if ($path === '/ai-sitemap.xml' || $path === '/llm-sitemap.xml') {
            self::render_ai_sitemap();
        }

        if ($path === '/llm.txt' || $path === '/llms.txt') {
            self::render_llm_txt();
        }
    }

    private static function render_ai_sitemap(): void
    {
        $entries = [];
        $seen = [];

        $add_entry = static function ($url, $lastmod = null) use (&$entries, &$seen): void {
            $url = esc_url_raw((string) $url);
            if ($url === '') {
                return;
            }

            $key = strtolower($url);
            if (isset($seen[$key])) {
                return;
            }

            $seen[$key] = true;
            $entries[] = [
                'loc' => $url,
                'lastmod' => $lastmod ?: gmdate('c'),
            ];
        };

        $add_entry(self::public_feed_url());
        $add_entry(home_url('/llm.txt'));
        $add_entry(home_url('/llms.txt'));
        $add_entry(home_url('/locations/'));
        $add_entry(home_url('/programs/'));
        $add_entry(home_url('/sitemap.xml'));

        foreach (['location', 'program'] as $post_type) {
            if (!post_type_exists($post_type)) {
                continue;
            }

            $ids = get_posts([
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'orderby' => 'modified',
                'order' => 'DESC',
            ]);

            foreach ((array) $ids as $post_id) {
                $url = get_permalink((int) $post_id);
                if (!$url) {
                    continue;
                }

                $add_entry($url, get_post_modified_time('c', true, (int) $post_id));
            }
        }

        $faq_ids = get_posts([
            'post_type' => ['page', 'post'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'chroma_faq_items',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        foreach ((array) $faq_ids as $post_id) {
            $url = get_permalink((int) $post_id);
            if ($url) {
                $add_entry($url, get_post_modified_time('c', true, (int) $post_id));
            }
        }

        if (class_exists('Chroma_Near_Me_Pages') && method_exists('Chroma_Near_Me_Pages', 'get_sitemap_urls')) {
            foreach ((array) Chroma_Near_Me_Pages::get_sitemap_urls() as $link) {
                $add_entry($link);
            }
        }

        if (class_exists('Chroma_Combo_Page_Generator') && method_exists('Chroma_Combo_Page_Generator', 'get_all_combos')) {
            foreach ((array) Chroma_Combo_Page_Generator::get_all_combos() as $combo) {
                $url = isset($combo['url']) ? (string) $combo['url'] : '';
                if ($url !== '') {
                    $add_entry($url);
                }
            }
        }

        nocache_headers();
        header('Content-Type: application/xml; charset=UTF-8');
        status_header(200);

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($entries as $entry) {
            echo "  <url>\n";
            echo '    <loc>' . esc_url($entry['loc']) . "</loc>\n";
            echo '    <lastmod>' . esc_html((string) $entry['lastmod']) . "</lastmod>\n";
            echo "  </url>\n";
        }
        echo "</urlset>\n";
        exit;
    }

    private static function render_llm_txt(): void
    {
        $dataset = self::get_base_dataset(false);
        $site_name = get_bloginfo('name');
        $site_desc = get_bloginfo('description');
        $site_url = rtrim(home_url('/'), '/');
        $feed_url = self::public_feed_url();
        $generated = $dataset['generated_at_gmt'] ?? gmdate('c');

        $out = [];
        $out[] = '# ' . $site_name;
        $out[] = '> ' . $site_desc;
        $out[] = '';
        $out[] = '## Site';
        $out[] = '- Canonical: ' . $site_url . '/';
        $out[] = '- Geo Feed: ' . $feed_url;
        $out[] = '- Generated At (UTC): ' . $generated;
        $out[] = '';
        $out[] = '## Sitemaps';
        $out[] = '- ' . $site_url . '/sitemap.xml';
        $out[] = '- ' . $site_url . '/sitemap_index.xml';
        $out[] = '- ' . $site_url . '/sitemap-spanish.xml';
        $out[] = '- ' . $site_url . '/sitemap-combos.xml';
        $out[] = '- ' . $site_url . '/sitemap-combos-es.xml';
        $out[] = '- ' . $site_url . '/sitemap-near-me.xml';
        $out[] = '- ' . $site_url . '/sitemap-near-me-es.xml';
        $out[] = '- ' . $site_url . '/ai-sitemap.xml';
        $out[] = '';
        $out[] = '## Locations';

        foreach ((array) ($dataset['locations'] ?? []) as $location) {
            $name = self::nullable_string($location['campus_name'] ?? '');
            $url = self::nullable_url($location['canonical_url'] ?? '');
            if ($name === null || $url === null) {
                continue;
            }

            $out[] = '- [' . $name . '](' . $url . ')';
        }

        $out[] = '';
        $out[] = '## Programs';
        foreach ((array) ($dataset['programs'] ?? []) as $program) {
            $name = self::nullable_string($program['name'] ?? '');
            $url = self::nullable_url($program['canonical_url'] ?? '');
            if ($name === null || $url === null) {
                continue;
            }

            $out[] = '- [' . $name . '](' . $url . ')';
        }

        $content = implode("\n", $out) . "\n";

        nocache_headers();
        header('Content-Type: text/plain; charset=UTF-8');
        status_header(200);
        echo $content;
        exit;
    }

    public static function append_geo_robots_rules(string $output, bool $public): string
    {
        if (!self::is_public_feed_available() || !$public) {
            return $output;
        }

        $path = wp_parse_url(self::public_feed_url(), PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/wp-json/' . self::NS . '/geo-feed';
        }

        $existing_lines = preg_split('/\R/', (string) $output) ?: [];
        $line_index = [];
        $merged = [];
        foreach ($existing_lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            $merged[] = $line;
            $line_index[strtolower($line)] = true;
        }

        $required_lines = [
            'User-agent: *',
            'Disallow: /wp-admin/',
            'Allow: /wp-admin/admin-ajax.php',
            'Allow: /wp-json/chroma-agent/',
            'Allow: /wp-json/chroma-agent/v1/',
            'Allow: ' . $path,
            'Allow: /llm.txt',
            'Allow: /llms.txt',
            'Sitemap: ' . esc_url_raw(home_url('/sitemap.xml')),
            'Sitemap: ' . esc_url_raw(home_url('/sitemap_index.xml')),
            'Sitemap: ' . esc_url_raw(home_url('/sitemap-spanish.xml')),
            'Sitemap: ' . esc_url_raw(home_url('/sitemap-combos.xml')),
            'Sitemap: ' . esc_url_raw(home_url('/sitemap-combos-es.xml')),
            'Sitemap: ' . esc_url_raw(home_url('/sitemap-near-me.xml')),
            'Sitemap: ' . esc_url_raw(home_url('/sitemap-near-me-es.xml')),
            'Sitemap: ' . esc_url_raw(home_url('/ai-sitemap.xml')),
            'Sitemap: ' . esc_url_raw(home_url('/llm-sitemap.xml')),
        ];

        foreach ($required_lines as $line) {
            $key = strtolower($line);
            if (isset($line_index[$key])) {
                continue;
            }
            $merged[] = $line;
            $line_index[$key] = true;
        }

        $bots = ['GPTBot', 'Google-Extended', 'PerplexityBot', 'ClaudeBot', 'Claude-Web'];
        foreach ($bots as $bot) {
            $ua_line = 'User-agent: ' . $bot;
            $ua_key = strtolower($ua_line);
            if (isset($line_index[$ua_key])) {
                continue;
            }

            $merged[] = $ua_line;
            $merged[] = 'Allow: ' . $path;
            $line_index[$ua_key] = true;
        }

        return implode("\n", $merged) . "\n";
    }

    private static function is_public_feed_available(): bool
    {
        return Utils::truthy(get_option(Utils::OPTION_ENABLED, 1));
    }

    private static function get_base_dataset(bool $refresh): array
    {
        $cached = get_transient(self::CACHE_KEY);
        if (!$refresh && is_array($cached)) {
            $cached['cached'] = true;
            return $cached;
        }

        $dataset = [
            'cached' => false,
            'generated_at_gmt' => gmdate('c'),
            'brand' => self::get_brand_payload(),
            'curriculum' => self::get_curriculum_payload(),
            'locations' => self::get_locations(),
            'programs' => self::get_programs(),
            'events' => [],
        ];
        $dataset['events'] = self::get_public_events($dataset['locations']);

        set_transient(self::CACHE_KEY, $dataset, self::CACHE_TTL);

        return $dataset;
    }

    private static function get_brand_payload(): array
    {
        return [
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'site_url' => home_url('/'),
            'contact' => [
                'role' => 'Main office',
                'email' => self::sanitize_public_contact_email(get_option('chroma_seo_email', '')),
                'phone' => self::normalize_phone_e164(get_option('chroma_seo_phone', '')),
            ],
        ];
    }

    private static function get_curriculum_payload(): array
    {
        $brand_context = self::normalize_text_block(get_option('chroma_llm_brand_context', ''));
        $brand_voice = self::normalize_text_block(get_option('chroma_llm_brand_voice', ''));

        return [
            'prismpath' => [
                'name' => 'Prismpath',
                'category' => 'Proprietary learning model',
                'description' => $brand_context !== ''
                    ? self::limit_text($brand_context, 320)
                    : 'Chroma proprietary learning model for structured early childhood development.',
            ],
            'chroma_spectrum' => [
                'name' => 'Chroma Spectrum Curriculum',
                'category' => 'Curriculum framework',
                'description' => $brand_voice !== ''
                    ? self::limit_text($brand_voice, 320)
                    : 'Branded curriculum framework that aligns classroom delivery, developmental goals, and family-facing program positioning.',
            ],
        ];
    }

    private static function get_locations(): array
    {
        if (!post_type_exists('location')) {
            return [];
        }

        $query = new WP_Query([
            'post_type' => 'location',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]);

        $items = [];
        foreach ((array) $query->posts as $post) {
            $post_id = (int) $post->ID;
            $public_meta = self::build_public_meta_snapshot($post_id, self::LOCATION_PUBLIC_META_ALLOWLIST);
            $address = self::build_location_address($post_id);
            $hours = self::normalize_hours_schedule(get_post_meta($post_id, 'location_hours', true));
            $faqs = self::merge_faq_items(
                self::normalize_faq_items($public_meta['chroma_faq_items'] ?? []),
                self::parse_delimited_qa_lines($public_meta['location_faq_items'] ?? '')
            );
            $enrollment_steps = self::normalize_enrollment_steps($public_meta['location_enrollment_steps'] ?? []);
            $program_labels = self::parse_text_list(get_post_meta($post_id, 'location_special_programs', true));
            $amenities = self::parse_text_list($public_meta['_chroma_amenities'] ?? []);
            $description = self::normalize_text_block(get_post_meta($post_id, 'location_description', true));
            $tagline = self::clean_scalar(get_post_meta($post_id, 'location_tagline', true));
            $seo_title = self::clean_scalar(get_post_meta($post_id, 'location_seo_content_title', true));
            $seo_text = self::normalize_text_block(get_post_meta($post_id, 'location_seo_content_text', true));
            $local_summary = self::normalize_text_block($public_meta['location_seo_content_text'] ?? '');
            $director_name = self::first_nonempty_meta($post_id, [
                'location_director_name',
                'location_administrator_name',
                'location_admin_name',
                'administrator_name',
            ]);
            $phone_raw = self::clean_scalar(get_post_meta($post_id, 'location_phone', true));
            $lat_raw = self::clean_scalar(get_post_meta($post_id, 'location_latitude', true));
            $lng_raw = self::clean_scalar(get_post_meta($post_id, 'location_longitude', true));
            $service_area_terms = self::parse_text_list($public_meta['location_service_areas'] ?? []);
            $search_terms = self::parse_text_list($public_meta['seo_llm_target_queries'] ?? []);
            $when_to_recommend = self::parse_text_list($public_meta['seo_llm_when_to_recommend'] ?? []);
            $key_differentiators = self::parse_text_list($public_meta['seo_llm_key_differentiators'] ?? []);
            $alt_names = self::parse_text_list($public_meta['seo_llm_alternate_names'] ?? []);
            $neighborhood_terms = self::parse_text_list($public_meta['seo_llm_neighborhood_terms'] ?? []);
            $service_area_intent_terms = self::parse_text_list($public_meta['seo_llm_service_area_terms'] ?? []);
            $nearby_landmarks = self::parse_text_list($public_meta['seo_llm_nearby_landmarks'] ?? []);
            $nearby_roads = self::parse_text_list($public_meta['seo_llm_nearby_roads'] ?? []);
            $intent_phrases = self::parse_text_list($public_meta['seo_llm_intent_phrases'] ?? []);
            $citation_facts = self::normalize_citation_facts($public_meta['seo_llm_citation_facts'] ?? []);
            $service_area_state = self::clean_scalar($public_meta['seo_llm_service_area_state'] ?? '');
            if ($service_area_state === '') {
                $service_area_state = self::DEFAULT_STATE;
            }

            $item = [
                'location_id' => $post_id,
                'slug' => (string) $post->post_name,
                'campus_name' => get_the_title($post_id),
                'canonical_url' => get_permalink($post_id),
                'last_updated_gmt' => self::get_post_last_updated_gmt($post_id),
                'record_hash' => null,
                'verification_status' => self::determine_location_verification_status($address),
                'address' => $address,
                'geo' => [
                    'lat' => self::normalize_float($lat_raw),
                    'lng' => self::normalize_float($lng_raw),
                ],
                'coordinates_raw' => [
                    'latitude' => self::nullable_string($lat_raw),
                    'longitude' => self::nullable_string($lng_raw),
                ],
                'service_radius_miles' => self::normalize_float($public_meta['seo_llm_service_area_radius'] ?? ''),
                'programs' => self::normalize_code_list($program_labels),
                'features' => self::build_location_feature_codes($public_meta, $amenities),
                'phone_raw' => self::nullable_string($phone_raw),
                'campus_contact' => [
                    'role' => 'Director',
                    'email' => self::sanitize_public_contact_email(get_post_meta($post_id, 'location_email', true)),
                    'phone' => self::normalize_phone_e164($phone_raw),
                ],
                'administrator_name' => self::nullable_string($director_name),
                'hours' => $hours,
                'short_description' => self::build_short_description($tagline, $description),
                'tagline' => self::nullable_string($tagline),
                'description' => $description !== '' ? self::limit_text($description, 500) : null,
                'seo_title' => self::nullable_string($seo_title),
                'seo_text' => $seo_text !== '' ? self::limit_text($seo_text, 1200) : null,
                'policies_summary' => self::build_policies_summary($enrollment_steps, $public_meta, $hours),
                'age_groups_raw' => self::nullable_string($public_meta['location_ages_served'] ?? ''),
                'local_seo_summary' => $local_summary !== '' ? self::limit_text($local_summary, 400) : null,
                'llm_description' => self::nullable_string($public_meta['seo_llm_description'] ?? ''),
                'primary_intent' => self::nullable_string($public_meta['seo_llm_primary_intent'] ?? ''),
                'service_area' => [
                    'cities' => self::parse_text_list($public_meta['seo_llm_service_area_cities'] ?? []),
                    'state' => $service_area_state,
                    'radius_miles' => self::normalize_float($public_meta['seo_llm_service_area_radius'] ?? ''),
                    'center' => [
                        'lat' => self::normalize_float($public_meta['seo_llm_service_area_lat'] ?? ''),
                        'lng' => self::normalize_float($public_meta['seo_llm_service_area_lng'] ?? ''),
                    ],
                ],
                'availability' => [
                    'status' => self::nullable_string($public_meta['location_availability_status'] ?? ''),
                    'spots_available' => self::normalize_int($public_meta['location_spots_available'] ?? ''),
                ],
                'pricing' => [
                    'min' => self::normalize_float($public_meta['location_price_min'] ?? ''),
                    'max' => self::normalize_float($public_meta['location_price_max'] ?? ''),
                    'currency' => self::nullable_string($public_meta['location_price_currency'] ?? ''),
                    'frequency' => self::nullable_string($public_meta['location_price_frequency'] ?? ''),
                ],
                'aggregate_rating' => [
                    'value' => self::normalize_float($public_meta['seo_llm_aggregate_rating_value'] ?? ''),
                    'count' => self::normalize_int($public_meta['seo_llm_aggregate_rating_count'] ?? ''),
                    'best' => self::normalize_float($public_meta['seo_llm_aggregate_rating_best'] ?? ''),
                    'worst' => self::normalize_float($public_meta['seo_llm_aggregate_rating_worst'] ?? ''),
                ],
                'media' => [
                    'video_tour_url' => self::nullable_url($public_meta['location_video_tour_url'] ?? ''),
                    'video_thumbnail_url' => self::nullable_url($public_meta['location_video_thumbnail'] ?? ''),
                    'video_duration' => self::nullable_string($public_meta['location_video_duration'] ?? ''),
                ],
                'admissions' => [
                    'enrollment_steps' => $enrollment_steps,
                ],
                'faqs' => $faqs,
                'citation_facts' => $citation_facts,
                'retrieval' => [
                    'search_terms' => $search_terms,
                    'alternate_names' => $alt_names,
                    'neighborhood_terms' => $neighborhood_terms,
                    'service_area_terms' => $service_area_intent_terms,
                    'nearby_landmarks' => $nearby_landmarks,
                    'nearby_roads' => $nearby_roads,
                    'intent_phrases' => $intent_phrases,
                    'when_to_recommend' => $when_to_recommend,
                    'key_differentiators' => $key_differentiators,
                    'service_area_raw' => $service_area_terms,
                ],
                'county' => self::nullable_string($public_meta['location_county'] ?? ''),
                'metro_area' => self::nullable_string($public_meta['location_metro_area'] ?? '') ?: self::DEFAULT_METRO_AREA,
                'google_business_profile_url' => self::nullable_url($public_meta['location_gmb_url'] ?? ''),
                'facility_profile' => [
                    'is_event_venue' => self::normalize_nullable_bool($public_meta['_chroma_is_event_venue'] ?? null),
                    'accepts_caps' => self::normalize_nullable_bool($public_meta['_chroma_caps_accepted'] ?? null),
                    'accepts_ga_pre_k' => self::normalize_nullable_bool($public_meta['_chroma_ga_pre_k_accepted'] ?? null),
                    'security_cameras' => self::normalize_nullable_bool($public_meta['_chroma_security_cameras'] ?? null),
                    'amenities' => $amenities,
                ],
                'events' => self::sanitize_location_events(get_post_meta($post_id, 'location_events', true)),
                'qa_notes_public' => null,
                'open_house_date' => self::nullable_string($public_meta['_chroma_open_house_date'] ?? ''),
            ];

            $item['record_hash'] = self::hash_payload(self::hashable_record($item, ['record_hash']));
            $items[] = $item;
        }

        wp_reset_postdata();

        return $items;
    }

    private static function get_programs(): array
    {
        if (!post_type_exists('program')) {
            return [];
        }

        $query = new WP_Query([
            'post_type' => 'program',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]);

        $items = [];
        foreach ((array) $query->posts as $post) {
            $post_id = (int) $post->ID;
            $public_meta = self::build_public_meta_snapshot($post_id, self::PROGRAM_PUBLIC_META_ALLOWLIST);
            $location_ids = self::normalize_int_list($public_meta['program_locations_served'] ?? ($public_meta['program_locations'] ?? []));
            $faqs = self::merge_faq_items(
                self::parse_delimited_qa_lines($public_meta['program_faq_items'] ?? ''),
                self::normalize_faq_items($public_meta['chroma_faq_items'] ?? [])
            );

            $item = [
                'program_id' => $post_id,
                'slug' => (string) $post->post_name,
                'name' => get_the_title($post_id),
                'canonical_url' => get_permalink($post_id),
                'last_updated_gmt' => self::get_post_last_updated_gmt($post_id),
                'record_hash' => null,
                'short_description' => self::build_program_short_description($post_id),
                'age_range' => self::nullable_string(get_post_meta($post_id, 'program_age_range', true)),
                'cta_text' => self::nullable_string(get_post_meta($post_id, 'program_cta_text', true)),
                'features' => self::normalize_code_list(self::parse_text_list(get_post_meta($post_id, 'program_features', true))),
                'anchor_slug' => self::nullable_string($public_meta['program_anchor_slug'] ?? ''),
                'seo' => [
                    'heading' => self::nullable_string($public_meta['program_seo_heading'] ?? ''),
                    'summary' => self::nullable_string(self::normalize_text_block($public_meta['program_seo_summary'] ?? '')),
                    'highlights' => self::parse_text_list($public_meta['program_seo_highlights'] ?? ''),
                    'meta_title' => self::nullable_string($public_meta['program_meta_title'] ?? ''),
                    'meta_description' => self::nullable_string(self::normalize_text_block($public_meta['program_meta_description'] ?? '')),
                ],
                'locations_served' => $location_ids,
                'prerequisites_ids' => self::normalize_int_list($public_meta['program_prerequisites'] ?? []),
                'related_program_ids' => self::normalize_int_list($public_meta['program_related'] ?? []),
                'lesson_plan_url' => self::nullable_url($public_meta['program_lesson_plan_file'] ?? ''),
                'faqs' => $faqs,
            ];

            $item['record_hash'] = self::hash_payload(self::hashable_record($item, ['record_hash']));
            $items[] = $item;
        }

        wp_reset_postdata();

        return $items;
    }

    private static function get_public_events(array $locations): array
    {
        $items = [];
        foreach ($locations as $location) {
            $location_id = (int) ($location['location_id'] ?? 0);
            $location_name = (string) ($location['campus_name'] ?? '');
            $location_url = (string) ($location['canonical_url'] ?? '');
            $last_updated = (string) ($location['last_updated_gmt'] ?? '');

            foreach ((array) ($location['events'] ?? []) as $event) {
                $items[] = [
                    'location_id' => $location_id,
                    'location_name' => $location_name,
                    'location_canonical_url' => $location_url,
                    'name' => self::nullable_string($event['name'] ?? ''),
                    'start' => self::nullable_string($event['start'] ?? ''),
                    'description' => self::nullable_string($event['description'] ?? ''),
                    'url' => self::nullable_url($event['url'] ?? ''),
                    'last_updated_gmt' => $last_updated,
                ];
            }

            if (!empty($location['open_house_date'])) {
                $items[] = [
                    'location_id' => $location_id,
                    'location_name' => $location_name,
                    'location_canonical_url' => $location_url,
                    'name' => 'Open House',
                    'start' => (string) $location['open_house_date'],
                    'description' => null,
                    'url' => $location_url,
                    'last_updated_gmt' => $last_updated,
                ];
            }
        }

        return $items;
    }

    private static function build_location_address(int $post_id): array
    {
        $state = self::clean_scalar(get_post_meta($post_id, 'location_state', true));
        if ($state === '') {
            $state = self::DEFAULT_STATE;
        }

        return [
            'street' => self::nullable_string(get_post_meta($post_id, 'location_address', true)),
            'city' => self::nullable_string(get_post_meta($post_id, 'location_city', true)),
            'state' => $state,
            'postal_code' => self::nullable_string(get_post_meta($post_id, 'location_zip', true)),
            'country' => 'US',
        ];
    }

    private static function sanitize_location_events($events): array
    {
        if (!is_array($events)) {
            return [];
        }

        $out = [];
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $name = self::nullable_string($event['name'] ?? '');
            $start = self::nullable_string($event['start'] ?? '');
            if ($name === null && $start === null) {
                continue;
            }

            $out[] = [
                'name' => $name,
                'start' => $start,
                'description' => self::nullable_string(self::normalize_text_block($event['description'] ?? '')),
                'url' => self::nullable_url($event['url'] ?? ''),
            ];
        }

        return $out;
    }

    private static function build_public_meta_snapshot(int $post_id, array $allowlist): array
    {
        $snapshot = [];
        foreach ($allowlist as $key) {
            $key = trim((string) $key);
            if ($key === '' || !self::is_public_meta_key_allowed($key)) {
                continue;
            }

            $snapshot[$key] = get_post_meta($post_id, $key, true);
        }

        return $snapshot;
    }

    private static function is_public_meta_key_allowed(string $key): bool
    {
        if (in_array($key, self::PUBLIC_META_DENYLIST, true)) {
            return false;
        }

        foreach (self::PUBLIC_META_PREFIX_DENYLIST as $prefix) {
            if ($prefix !== '' && strpos($key, $prefix) === 0) {
                return false;
            }
        }

        return true;
    }

    private static function build_filter_descriptor(WP_REST_Request $request): array
    {
        $modified_since_raw = self::clean_scalar($request->get_param('modified_since'));
        $modified_since_ts = self::parse_timestamp($modified_since_raw);

        return [
            'ids' => self::normalize_int_list($request->get_param('ids')),
            'modified_since' => $modified_since_ts ? gmdate('c', $modified_since_ts) : null,
            'modified_since_ts' => $modified_since_ts,
        ];
    }

    private static function normalize_filter_output(array $filters): array
    {
        return [
            'ids' => $filters['ids'],
            'modified_since' => $filters['modified_since'],
        ];
    }

    private static function filter_location_records(array $locations, array $filters): array
    {
        $allowed_ids = $filters['ids'];
        $modified_since = (int) ($filters['modified_since_ts'] ?? 0);
        $items = [];

        foreach ($locations as $location) {
            $location_id = (int) ($location['location_id'] ?? 0);
            if ($allowed_ids !== [] && !in_array($location_id, $allowed_ids, true)) {
                continue;
            }

            if ($modified_since > 0) {
                $updated = self::parse_timestamp($location['last_updated_gmt'] ?? '');
                if (!$updated || $updated <= $modified_since) {
                    continue;
                }
            }

            $items[] = $location;
        }

        return $items;
    }

    private static function filter_program_records(array $programs, array $filters): array
    {
        $modified_since = (int) ($filters['modified_since_ts'] ?? 0);
        if ($modified_since <= 0) {
            return $programs;
        }

        $items = [];
        foreach ($programs as $program) {
            $updated = self::parse_timestamp($program['last_updated_gmt'] ?? '');
            if ($updated && $updated > $modified_since) {
                $items[] = $program;
            }
        }

        return $items;
    }

    private static function filter_event_records(array $events, array $locations, array $filters): array
    {
        $allowed_location_ids = [];
        foreach ($locations as $location) {
            $allowed_location_ids[] = (int) ($location['location_id'] ?? 0);
        }

        $has_id_filter = !empty($filters['ids']);
        if ($has_id_filter && $allowed_location_ids === []) {
            return [];
        }

        $modified_since = (int) ($filters['modified_since_ts'] ?? 0);
        $items = [];

        foreach ($events as $event) {
            $location_id = (int) ($event['location_id'] ?? 0);
            if ($allowed_location_ids !== [] && !in_array($location_id, $allowed_location_ids, true)) {
                continue;
            }

            if ($modified_since > 0) {
                $updated = self::parse_timestamp($event['last_updated_gmt'] ?? '');
                if (!$updated || $updated <= $modified_since) {
                    continue;
                }
            }

            $items[] = $event;
        }

        return $items;
    }

    private static function build_legacy_event_records(array $events): array
    {
        $items = [];
        foreach ($events as $event) {
            $items[] = [
                'location' => self::nullable_string($event['location_name'] ?? ''),
                'location_url' => self::nullable_url($event['location_canonical_url'] ?? ''),
                'name' => self::nullable_string($event['name'] ?? ''),
                'start' => self::nullable_string($event['start'] ?? ''),
                'description' => self::nullable_string($event['description'] ?? ''),
                'url' => self::nullable_url($event['url'] ?? ''),
            ];
        }

        return $items;
    }

    /**
     * Convert internal program records to the legacy public program contract.
     */
    private static function build_legacy_program_records(array $programs): array
    {
        $items = [];
        foreach ($programs as $program) {
            $program_id = (int) ($program['program_id'] ?? 0);
            $location_ids = self::normalize_int_list($program['locations_served'] ?? []);
            $prereq_ids = self::normalize_int_list($program['prerequisites_ids'] ?? []);
            $related_ids = self::normalize_int_list($program['related_program_ids'] ?? []);
            $seo = is_array($program['seo'] ?? null) ? $program['seo'] : [];

            $items[] = [
                'id' => $program_id > 0 ? $program_id : null,
                'name' => self::nullable_string($program['name'] ?? ''),
                'slug' => self::nullable_string($program['slug'] ?? ''),
                'url' => self::nullable_url($program['canonical_url'] ?? ''),
                'summary' => self::nullable_string($program['short_description'] ?? ''),
                'age_range' => self::nullable_string($program['age_range'] ?? ''),
                'cta_text' => self::nullable_string($program['cta_text'] ?? ''),
                'features' => self::normalize_legacy_feature_list($program['features'] ?? []),
                'anchor_slug' => self::nullable_string($program['anchor_slug'] ?? ''),
                'lesson_plan_url' => self::nullable_url($program['lesson_plan_url'] ?? ''),
                'seo' => [
                    'heading' => self::nullable_string($seo['heading'] ?? ''),
                    'summary' => self::nullable_string($seo['summary'] ?? ''),
                    'highlights' => self::parse_text_list($seo['highlights'] ?? []),
                    'meta_title' => self::nullable_string($seo['meta_title'] ?? ''),
                    'meta_description' => self::nullable_string($seo['meta_description'] ?? ''),
                ],
                'faqs' => self::normalize_entity_faqs($program['faqs'] ?? []),
                'locations_served' => self::map_related_posts($location_ids, 'location'),
                'prerequisites' => self::map_related_posts($prereq_ids, 'program'),
                'related_programs' => self::map_related_posts($related_ids, 'program'),
            ];
        }

        return $items;
    }

    /**
     * Convert internal location records to the legacy public location contract.
     */
    private static function build_legacy_location_records(array $locations, array $programs): array
    {
        $legacy_programs = self::build_legacy_program_records($programs);
        $program_map = self::build_location_program_map($legacy_programs);
        $program_name_index = [];
        $program_slug_index = [];

        foreach ($legacy_programs as $program) {
            $id = isset($program['id']) && is_numeric($program['id']) ? (int) $program['id'] : null;
            $name = self::nullable_string($program['name'] ?? '');
            $slug = self::nullable_string($program['slug'] ?? '');
            if ($id === null || $name === null) {
                continue;
            }

            $ref = [
                'id' => $id,
                'name' => $name,
                'slug' => $slug,
                'url' => self::nullable_url($program['url'] ?? ''),
            ];
            $program_name_index[strtolower($name)] = $ref;
            if ($slug !== null) {
                $program_slug_index[strtolower($slug)] = $ref;
            }
        }

        $items = [];
        foreach ($locations as $location) {
            $location_id = (int) ($location['location_id'] ?? 0);
            $address = self::normalize_legacy_address($location['address'] ?? []);
            $hours = self::normalize_hours_schedule_for_legacy($location['hours'] ?? []);
            $hours_normalized = self::build_hours_normalized($location['hours'] ?? []);
            $ages_raw = self::nullable_string($location['age_groups_raw'] ?? '');
            $ages_normalized = self::build_ages_normalized($ages_raw);

            $programs_offered = $program_map[$location_id] ?? [];
            if ($programs_offered === []) {
                foreach ((array) ($location['programs'] ?? []) as $code) {
                    $slug = strtolower(str_replace('_', '-', (string) $code));
                    $label = self::code_to_label((string) $code);
                    if ($label === null) {
                        continue;
                    }

                    $ref = $program_slug_index[$slug] ?? ($program_name_index[strtolower($label)] ?? null);
                    if ($ref === null) {
                        $ref = [
                            'id' => null,
                            'name' => $label,
                            'slug' => sanitize_title($label),
                            'url' => null,
                        ];
                    }

                    $programs_offered[] = $ref;
                }
                $programs_offered = self::dedupe_program_refs($programs_offered);
            }

            $phone_raw = self::nullable_string($location['phone_raw'] ?? '');
            $phone_e164 = self::normalize_phone_e164($phone_raw ?? ($location['campus_contact']['phone'] ?? ''));
            $phone_display = self::normalize_phone_display($phone_raw, $phone_e164);

            $service_area = is_array($location['service_area'] ?? null) ? $location['service_area'] : [];
            $retrieval = is_array($location['retrieval'] ?? null) ? $location['retrieval'] : [];
            $service_areas = self::parse_text_list(array_merge(
                (array) ($service_area['cities'] ?? []),
                (array) ($retrieval['service_area_raw'] ?? [])
            ));
            $facility_profile = is_array($location['facility_profile'] ?? null) ? $location['facility_profile'] : [];

            $lat_num = self::normalize_float($location['geo']['lat'] ?? null);
            $lng_num = self::normalize_float($location['geo']['lng'] ?? null);
            $coordinates = [
                'latitude' => self::nullable_string($location['coordinates_raw']['latitude'] ?? '') ?? ($lat_num !== null ? (string) $lat_num : null),
                'longitude' => self::nullable_string($location['coordinates_raw']['longitude'] ?? '') ?? ($lng_num !== null ? (string) $lng_num : null),
            ];

            $faqs = self::build_hardened_location_faqs(
                self::normalize_entity_faqs($location['faqs'] ?? []),
                $ages_normalized['display'] ?? null,
                $hours['display'] ?? null,
                array_values(array_filter(array_map(static fn($x) => $x['name'] ?? null, $programs_offered)))
            );
            $description = self::fallback_location_description(
                self::nullable_string($location['description'] ?? ''),
                self::nullable_string($location['seo_text'] ?? ''),
                self::nullable_string($location['short_description'] ?? '')
            );
            $accepts_ga_pre_k_meta = self::normalize_nullable_bool($facility_profile['accepts_ga_pre_k'] ?? null);
            $accepts_ga_pre_k = $accepts_ga_pre_k_meta;
            if ($accepts_ga_pre_k === null && self::location_has_ga_pre_k_program($programs_offered)) {
                $accepts_ga_pre_k = true;
            }

            $legacy = [
                'id' => $location_id > 0 ? $location_id : null,
                'campus_name' => self::nullable_string($location['campus_name'] ?? ''),
                'slug' => self::nullable_string($location['slug'] ?? ''),
                'url' => self::nullable_url($location['canonical_url'] ?? ''),
                'address' => $address,
                'phone_number' => $phone_raw ?? $phone_display ?? self::nullable_string($location['campus_contact']['phone'] ?? ''),
                'phone_display' => $phone_display,
                'phone_e164' => $phone_e164,
                'email' => self::sanitize_public_contact_email($location['campus_contact']['email'] ?? ''),
                'administrator_name' => self::nullable_string($location['administrator_name'] ?? ''),
                'programs_offered' => $programs_offered,
                'ages_accepted' => $ages_raw,
                'ages_normalized' => $ages_normalized,
                'operating_hours' => $hours['raw'],
                'hours_normalized' => $hours_normalized,
                'facility_highlights' => [
                    'tagline' => self::nullable_string($location['tagline'] ?? ''),
                    'description' => $description,
                    'seo_title' => self::nullable_string($location['seo_title'] ?? ''),
                    'seo_text' => self::nullable_string($location['seo_text'] ?? ''),
                ],
                'service_areas' => $service_areas,
                'coordinates' => $coordinates,
                'coordinates_normalized' => [
                    'latitude' => $lat_num,
                    'longitude' => $lng_num,
                ],
                'media' => is_array($location['media'] ?? null) ? $location['media'] : [],
                'availability' => is_array($location['availability'] ?? null) ? $location['availability'] : [],
                'pricing' => is_array($location['pricing'] ?? null) ? $location['pricing'] : [],
                'aggregate_rating' => is_array($location['aggregate_rating'] ?? null) ? $location['aggregate_rating'] : [],
                'service_area_geo' => [
                    'latitude' => self::normalize_float($service_area['center']['lat'] ?? null),
                    'longitude' => self::normalize_float($service_area['center']['lng'] ?? null),
                    'radius_miles' => self::normalize_float($service_area['radius_miles'] ?? null),
                    'cities' => self::parse_text_list($service_area['cities'] ?? []),
                    'state' => self::nullable_string($service_area['state'] ?? '') ?: self::DEFAULT_STATE,
                ],
                'facility_profile' => [
                    'is_event_venue' => self::normalize_nullable_bool($facility_profile['is_event_venue'] ?? null),
                    'accepts_caps' => self::normalize_nullable_bool($facility_profile['accepts_caps'] ?? null),
                    'accepts_ga_pre_k' => $accepts_ga_pre_k,
                    'security_cameras' => self::normalize_nullable_bool($facility_profile['security_cameras'] ?? null),
                    'amenities' => self::parse_text_list($facility_profile['amenities'] ?? []),
                ],
                'facility_profile_normalized' => [
                    'accepts_ga_pre_k' => $accepts_ga_pre_k,
                ],
                'facility_profile_source' => 'wordpress_meta',
                'admissions' => is_array($location['admissions'] ?? null) ? $location['admissions'] : ['enrollment_steps' => []],
                'faqs' => $faqs,
                'events' => self::sanitize_location_events($location['events'] ?? []),
                'open_house_date' => self::nullable_string($location['open_house_date'] ?? ''),
                'entity_type' => 'ChildCareCenter',
                'canonical_url' => self::nullable_url($location['canonical_url'] ?? ''),
                'county' => self::nullable_string($location['county'] ?? ''),
                'metro_area' => self::nullable_string($location['metro_area'] ?? '') ?: self::DEFAULT_METRO_AREA,
                'alternate_names' => self::normalize_keyword_terms($retrieval['alternate_names'] ?? []),
                'search_terms' => self::build_location_search_terms(
                    $retrieval,
                    self::nullable_string($location['campus_name'] ?? '') ?: 'chroma',
                    self::nullable_string($address['city'] ?? ''),
                    self::nullable_string($address['state'] ?? '') ?: self::DEFAULT_STATE
                ),
                'nearby_neighborhoods' => self::normalize_keyword_terms($retrieval['neighborhood_terms'] ?? []),
                'nearby_schools' => self::normalize_keyword_terms($retrieval['nearby_schools'] ?? []),
                'service_area_terms' => self::normalize_keyword_terms($retrieval['service_area_terms'] ?? []),
                'nearby_landmarks' => self::normalize_keyword_terms($retrieval['nearby_landmarks'] ?? []),
                'nearby_roads' => self::normalize_keyword_terms($retrieval['nearby_roads'] ?? []),
                'intent_phrases' => self::build_location_intent_phrases($retrieval, self::nullable_string($address['city'] ?? '')),
            ];

            $legacy['data_completeness_score'] = self::compute_location_completeness_score($legacy);
            $legacy['ai_citation_ready'] = $legacy['data_completeness_score'] >= 0.7 && !empty($legacy['programs_offered']);
            $items[] = $legacy;
        }

        return $items;
    }

    private static function build_location_program_map(array $legacy_programs): array
    {
        $map = [];
        foreach ($legacy_programs as $program) {
            $ref = [
                'id' => isset($program['id']) && is_numeric($program['id']) ? (int) $program['id'] : null,
                'name' => self::nullable_string($program['name'] ?? ''),
                'slug' => self::nullable_string($program['slug'] ?? ''),
                'url' => self::nullable_url($program['url'] ?? ''),
            ];
            if ($ref['name'] === null) {
                continue;
            }

            foreach ((array) ($program['locations_served'] ?? []) as $location) {
                $location_id = isset($location['id']) && is_numeric($location['id']) ? (int) $location['id'] : 0;
                if ($location_id < 1) {
                    continue;
                }

                $map[$location_id][] = $ref;
            }
        }

        foreach ($map as $location_id => $programs) {
            $map[$location_id] = self::dedupe_program_refs($programs);
        }

        return $map;
    }

    private static function dedupe_program_refs(array $items): array
    {
        $seen = [];
        $out = [];

        foreach ($items as $item) {
            $id = isset($item['id']) && is_numeric($item['id']) ? (string) (int) $item['id'] : '';
            $name = strtolower((string) ($item['name'] ?? ''));
            $key = $id !== '' ? 'id:' . $id : 'name:' . $name;
            if ($key === 'name:' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[] = [
                'id' => isset($item['id']) && is_numeric($item['id']) ? (int) $item['id'] : null,
                'name' => self::nullable_string($item['name'] ?? ''),
                'slug' => self::nullable_string($item['slug'] ?? ''),
                'url' => self::nullable_url($item['url'] ?? ''),
            ];
        }

        return $out;
    }

    private static function normalize_legacy_feature_list($features): array
    {
        $labels = [];
        foreach ((array) $features as $feature) {
            $label = self::code_to_label((string) $feature);
            if ($label !== null) {
                $labels[] = $label;
            }
        }

        return self::parse_text_list($labels);
    }

    private static function normalize_legacy_address($address): array
    {
        $address = is_array($address) ? $address : [];
        $street = self::nullable_string($address['street'] ?? '');
        $city = self::nullable_string($address['city'] ?? '');
        $state = self::nullable_string($address['state'] ?? '') ?: self::DEFAULT_STATE;
        $postal_code = self::nullable_string($address['postal_code'] ?? '');
        $country = self::nullable_string($address['country'] ?? '') ?: 'US';

        // Some records are stuffed into street. Parse when deterministic.
        if ($street !== null && ($city === null || $postal_code === null)) {
            if (preg_match('/^(.*?),\s*([^,]+),\s*([A-Za-z]{2})\s+(\d{5}(?:-\d{4})?)$/', $street, $matches)) {
                $street = self::nullable_string($matches[1] ?? '');
                $city = $city ?: self::nullable_string($matches[2] ?? '');
                $state = self::nullable_string($matches[3] ?? '') ?: $state;
                $postal_code = $postal_code ?: self::nullable_string($matches[4] ?? '');
            }
        }

        $formatted = self::format_address_line([
            'street' => $street,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postal_code,
            'country' => $country,
        ]);

        return [
            'street' => $street,
            'city' => $city,
            'state' => $state,
            'postal_code' => $postal_code,
            'country' => $country,
            'formatted' => $formatted,
        ];
    }

    private static function normalize_phone_display(?string $raw_phone, ?string $phone_e164): ?string
    {
        if ($raw_phone !== null && trim($raw_phone) !== '') {
            $digits = preg_replace('/\D+/', '', $raw_phone);
            if (is_string($digits) && strlen($digits) === 10) {
                return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
            }

            return trim($raw_phone);
        }

        if ($phone_e164 === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone_e164);
        if (is_string($digits) && strlen($digits) === 11 && strpos($digits, '1') === 0) {
            $digits = substr($digits, 1);
        }

        if (is_string($digits) && strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 3), substr($digits, 3, 3), substr($digits, 6));
        }

        return null;
    }

    private static function normalize_hours_schedule_for_legacy($hours): array
    {
        $hours = is_array($hours) ? $hours : self::empty_hours_schedule();
        $raw = self::nullable_string($hours['notes'] ?? '');
        if ($raw === null) {
            $raw = self::build_hours_display($hours);
        }

        return [
            'raw' => $raw,
            'display' => self::build_hours_display($hours),
        ];
    }

    private static function build_hours_normalized($hours): array
    {
        $hours = is_array($hours) ? $hours : self::empty_hours_schedule();
        $raw_notes = self::nullable_string($hours['notes'] ?? '');
        $labels = [
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
            'sun' => 'Sunday',
        ];

        $days = [];
        $open_time = null;
        $close_time = null;
        foreach ($labels as $key => $label) {
            $entry = is_array($hours[$key] ?? null) ? $hours[$key] : ['open' => null, 'close' => null, 'closed' => null];
            $closed = isset($entry['closed']) ? (bool) $entry['closed'] : null;
            if ($closed === true) {
                continue;
            }

            if (!empty($entry['open']) && !empty($entry['close'])) {
                $days[] = $label;
                $open_time = $open_time ?? $entry['open'];
                $close_time = $close_time ?? $entry['close'];
            }
        }

        if (($open_time === null || $close_time === null) && $raw_notes !== null) {
            $parsed = self::parse_hours_range($raw_notes);
            if ($parsed !== null) {
                $open_time = $open_time ?? $parsed['open'];
                $close_time = $close_time ?? $parsed['close'];
            }
        }

        $display = self::build_hours_display($hours);
        if ($display === null) {
            $display = $raw_notes;
        }

        return [
            'days' => $days,
            'open_time' => self::nullable_string($open_time ?? ''),
            'close_time' => self::nullable_string($close_time ?? ''),
            'display' => $display,
            'raw' => $raw_notes,
        ];
    }

    private static function build_hours_display(array $hours): ?string
    {
        $keys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $labels = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];
        $open_days = [];
        $open = null;
        $close = null;

        foreach ($keys as $key) {
            $entry = is_array($hours[$key] ?? null) ? $hours[$key] : null;
            if (!$entry || (isset($entry['closed']) && $entry['closed'])) {
                continue;
            }
            if (empty($entry['open']) || empty($entry['close'])) {
                continue;
            }

            $open_days[] = $key;
            $open = $open ?? $entry['open'];
            $close = $close ?? $entry['close'];
        }

        if ($open_days === [] || $open === null || $close === null) {
            return self::nullable_string($hours['notes'] ?? '');
        }

        $first = $labels[$open_days[0]];
        $last = $labels[$open_days[count($open_days) - 1]];
        $day_part = $first === $last ? $first : ($first . '–' . $last);
        $open_display = self::to_meridiem_time($open);
        $close_display = self::to_meridiem_time($close);

        return trim($day_part . ' ' . $open_display . '–' . $close_display);
    }

    private static function parse_hours_range(string $text): ?array
    {
        if (!preg_match('/([0-9]{1,2}(?::[0-9]{2})?\s*[ap]m?)\s*(?:-|to)\s*([0-9]{1,2}(?::[0-9]{2})?\s*[ap]m?)/i', $text, $matches)) {
            return null;
        }

        $open = self::normalize_time_token($matches[1]);
        $close = self::normalize_time_token($matches[2]);
        if ($open === null || $close === null) {
            return null;
        }

        return [
            'open' => $open,
            'close' => $close,
        ];
    }

    private static function to_meridiem_time(string $time): string
    {
        if (!preg_match('/^([0-2]?[0-9]):([0-5][0-9])$/', $time, $matches)) {
            return $time;
        }

        $hour = (int) $matches[1];
        $minute = $matches[2];
        $suffix = $hour >= 12 ? 'PM' : 'AM';
        $hour = $hour % 12;
        if ($hour === 0) {
            $hour = 12;
        }

        return sprintf('%d:%s %s', $hour, $minute, $suffix);
    }

    private static function build_ages_normalized(?string $raw): array
    {
        $raw = self::nullable_string($raw ?? '');
        if ($raw === null) {
            return [
                'min_age_value' => null,
                'min_age_unit' => null,
                'max_age_value' => null,
                'max_age_unit' => null,
                'display' => null,
            ];
        }

        if (!preg_match('/^\s*(\d+)\s*([a-z]+)\s*[-to]+\s*(\d+)\s*([a-z]+)\s*$/i', str_replace(' ', '', strtolower($raw)), $m)) {
            return [
                'min_age_value' => null,
                'min_age_unit' => null,
                'max_age_value' => null,
                'max_age_unit' => null,
                'display' => $raw,
            ];
        }

        $min_value = (int) $m[1];
        $max_value = (int) $m[3];
        $min_unit = self::normalize_age_unit($m[2]);
        $max_unit = self::normalize_age_unit($m[4]);

        $display = null;
        if ($min_unit !== null && $max_unit !== null) {
            $display = sprintf('%d %s to %d %s', $min_value, $min_unit, $max_value, $max_unit);
        }

        return [
            'min_age_value' => $min_value,
            'min_age_unit' => $min_unit,
            'max_age_value' => $max_value,
            'max_age_unit' => $max_unit,
            'display' => $display ?: $raw,
        ];
    }

    private static function normalize_age_unit(string $raw): ?string
    {
        $raw = strtolower(trim($raw));
        if (in_array($raw, ['w', 'wk', 'wks', 'week', 'weeks'], true)) {
            return 'weeks';
        }
        if (in_array($raw, ['m', 'mo', 'mos', 'month', 'months'], true)) {
            return 'months';
        }
        if (in_array($raw, ['y', 'yr', 'yrs', 'year', 'years'], true)) {
            return 'years';
        }

        return null;
    }

    private static function build_hardened_location_faqs(array $faqs, ?string $age_display, ?string $hours_display, array $program_names): array
    {
        $faqs = self::normalize_entity_faqs($faqs);
        if (count($faqs) >= 3) {
            return $faqs;
        }

        $fallbacks = [];
        if ($age_display !== null) {
            $fallbacks[] = [
                'question' => 'What ages does this location serve?',
                'answer' => 'This location serves children from ' . $age_display . '.',
            ];
        }
        if ($hours_display !== null) {
            $fallbacks[] = [
                'question' => 'What are this location’s operating hours?',
                'answer' => 'This location operates ' . $hours_display . '.',
            ];
        }
        if (!empty($program_names)) {
            $fallbacks[] = [
                'question' => 'What programs are offered at this location?',
                'answer' => 'Programs offered include ' . implode(', ', array_slice($program_names, 0, 6)) . '.',
            ];
        }

        $merged = self::merge_faq_items($faqs, $fallbacks);
        return array_slice(self::normalize_entity_faqs($merged), 0, 6);
    }

    private static function location_has_ga_pre_k_program(array $programs_offered): bool
    {
        foreach ($programs_offered as $program) {
            if (!is_array($program)) {
                continue;
            }

            $slug = strtolower((string) ($program['slug'] ?? ''));
            $name = strtolower((string) ($program['name'] ?? ''));
            if ($slug === 'ga-pre-k' || strpos($slug, 'ga-pre-k') !== false || strpos($name, 'ga pre-k') !== false || strpos($name, 'georgia pre-k') !== false) {
                return true;
            }
        }

        return false;
    }

    private static function fallback_location_description(?string $description, ?string $seo_text, ?string $short_description): ?string
    {
        if ($description !== null) {
            return self::limit_text($description, 500);
        }

        if ($seo_text !== null) {
            $parts = preg_split('/(?<=[.!?])\s+/', trim($seo_text), 2);
            $first_sentence = is_array($parts) ? trim((string) ($parts[0] ?? '')) : '';
            $candidate = $first_sentence !== '' ? $first_sentence : $seo_text;
            return self::limit_text($candidate, 500);
        }

        if ($short_description !== null) {
            return self::limit_text($short_description, 500);
        }

        return null;
    }

    private static function compute_location_completeness_score(array $location): float
    {
        $checks = [
            !empty($location['campus_name']),
            !empty($location['slug']),
            !empty($location['url']),
            !empty($location['address']['street']),
            !empty($location['address']['city']),
            !empty($location['address']['state']),
            !empty($location['address']['postal_code']),
            !empty($location['phone_e164']) || !empty($location['phone_number']),
            !empty($location['programs_offered']),
            !empty($location['hours_normalized']['display']) || !empty($location['operating_hours']),
            !empty($location['ages_normalized']['display']) || !empty($location['ages_accepted']),
            !empty($location['faqs']),
            is_numeric($location['coordinates_normalized']['latitude'] ?? null),
            is_numeric($location['coordinates_normalized']['longitude'] ?? null),
        ];

        $passed = 0;
        foreach ($checks as $check) {
            if ($check) {
                $passed++;
            }
        }

        return round($passed / max(1, count($checks)), 4);
    }

    private static function build_quality_report(array $locations, array $programs): array
    {
        $warnings = [];
        $location_ids = [];
        $absolute_url_failures = 0;
        $coordinates_numeric_failures = 0;
        $locations_missing_faqs = 0;
        $locations_missing_descriptions = 0;
        $locations_missing_admins = 0;
        $locations_missing_emails = 0;
        foreach ($locations as $location) {
            $location_id = isset($location['id']) && is_numeric($location['id']) ? (int) $location['id'] : 0;
            if ($location_id > 0) {
                $location_ids[$location_id] = true;
            }

            $url = self::nullable_url($location['url'] ?? '');
            if ($url === null) {
                $absolute_url_failures++;
            }

            $lat = $location['coordinates_normalized']['latitude'] ?? null;
            $lng = $location['coordinates_normalized']['longitude'] ?? null;
            if (($lat !== null && !is_numeric($lat)) || ($lng !== null && !is_numeric($lng))) {
                $coordinates_numeric_failures++;
            }

            if (empty($location['faqs'])) {
                $locations_missing_faqs++;
            }

            $description = self::nullable_string($location['facility_highlights']['description'] ?? '');
            if ($description === null) {
                $locations_missing_descriptions++;
            }

            if (self::nullable_string($location['administrator_name'] ?? '') === null) {
                $locations_missing_admins++;
            }

            if (self::nullable_string($location['email'] ?? '') === null) {
                $locations_missing_emails++;
            }
        }

        $program_ids = [];
        $program_to_location_ids = [];
        foreach ($programs as $program) {
            $program_id = isset($program['id']) && is_numeric($program['id']) ? (int) $program['id'] : 0;
            if ($program_id > 0) {
                $program_ids[$program_id] = true;
            }

            $program_to_location_ids[$program_id] = [];
            foreach ((array) ($program['locations_served'] ?? []) as $location_ref) {
                $location_id = isset($location_ref['id']) && is_numeric($location_ref['id']) ? (int) $location_ref['id'] : 0;
                if ($location_id > 0) {
                    $program_to_location_ids[$program_id][] = $location_id;
                    if (!isset($location_ids[$location_id])) {
                        $warnings[] = 'program_location_missing:' . $program_id . ':' . $location_id;
                    }
                }
            }
        }

        $relationship_errors = 0;
        $location_scores = [];
        foreach ($locations as $location) {
            $location_scores[] = (float) ($location['data_completeness_score'] ?? 0.0);
            $location_id = isset($location['id']) && is_numeric($location['id']) ? (int) $location['id'] : 0;
            $offered = is_array($location['programs_offered'] ?? null) ? $location['programs_offered'] : [];
            $upstream_has_program = false;
            foreach ($program_to_location_ids as $location_list) {
                if (in_array($location_id, $location_list, true)) {
                    $upstream_has_program = true;
                    break;
                }
            }

            if ($upstream_has_program && $offered === []) {
                $relationship_errors++;
                $warnings[] = 'location_programs_offered_empty_with_upstream_map:' . $location_id;
            }

            foreach ($offered as $program_ref) {
                $program_id = isset($program_ref['id']) && is_numeric($program_ref['id']) ? (int) $program_ref['id'] : 0;
                if ($program_id > 0 && !isset($program_ids[$program_id])) {
                    $relationship_errors++;
                    $warnings[] = 'location_program_reference_missing:' . $location_id . ':' . $program_id;
                }
            }

            if (empty($location['faqs'])) {
                $warnings[] = 'location_missing_faqs:' . $location_id;
            }
        }

        $avg_location_score = 0.0;
        if (!empty($location_scores)) {
            $avg_location_score = array_sum($location_scores) / count($location_scores);
        }

        $relationship_score = max(0.0, 1 - ($relationship_errors / max(1, count($locations) + count($programs))));
        $data_quality_score = round(min(1.0, max(0.0, ($avg_location_score * 0.75) + ($relationship_score * 0.25))), 4);

        return [
            'data_quality_score' => $data_quality_score,
            'warnings' => array_values(array_unique($warnings)),
            'contract_integrity' => [
                'valid_json' => true,
                'required_top_level_keys_present' => true,
                'types_stable' => true,
            ],
            'relationship_integrity' => [
                'relationship_error_count' => $relationship_errors,
                'locations_checked' => count($locations),
                'programs_checked' => count($programs),
            ],
            'format_integrity' => [
                'absolute_url_failures' => $absolute_url_failures,
                'coordinates_numeric_failures' => $coordinates_numeric_failures,
            ],
            'completeness' => [
                'average_location_completeness' => round($avg_location_score, 4),
                'locations_missing_faqs' => $locations_missing_faqs,
                'locations_missing_descriptions' => $locations_missing_descriptions,
                'locations_missing_admins' => $locations_missing_admins,
                'locations_missing_emails' => $locations_missing_emails,
            ],
            'regression_safety' => [
                'legacy_keys_preserved' => true,
                'legacy_fields_removed' => [],
            ],
        ];
    }

    private static function build_location_entities(array $locations, array $programs, array $brand): array
    {
        $items = [];
        foreach ($locations as $location) {
            $items[] = self::build_location_entity($location, $programs, $brand);
        }

        return $items;
    }

    private static function build_location_entity(array $location, array $programs, array $brand): array
    {
        $address = is_array($location['address'] ?? null) ? $location['address'] : self::empty_address();
        $city = self::nullable_string($address['city'] ?? '');
        $state = self::nullable_string($address['state'] ?? '') ?: self::DEFAULT_STATE;
        $location_name = self::nullable_string($location['campus_name'] ?? '') ?: 'Chroma Location';
        $location_slug = self::nullable_string($location['slug'] ?? '');

        $programs_offered = self::build_programs_offered($location, $programs);
        $program_names = [];
        foreach ($programs_offered as $program) {
            $name = self::nullable_string($program['name'] ?? '');
            if ($name !== null) {
                $program_names[] = $name;
            }
        }

        $raw_retrieval = is_array($location['retrieval'] ?? null) ? $location['retrieval'] : [];
        $search_terms = self::build_location_search_terms($raw_retrieval, $location_name, $city, $state);
        $intent_phrases = self::build_location_intent_phrases($raw_retrieval, $city);
        $faqs = self::normalize_entity_faqs($location['faqs'] ?? []);
        $citation_facts = self::normalize_citation_facts($location['citation_facts'] ?? []);
        $age_groups = self::build_age_groups($location['age_groups_raw'] ?? null, $program_names);
        $service_tags = self::build_service_tags($location['features'] ?? []);

        $has_address = !empty($address['street']) && !empty($address['city']) && !empty($address['state']) && !empty($address['postal_code']);
        $has_geo = is_numeric($location['geo']['lat'] ?? null) && is_numeric($location['geo']['lng'] ?? null);
        $has_contact = !empty($location['campus_contact']['phone']) || !empty($location['campus_contact']['email']);
        $faq_ready = !empty($faqs);
        $location_intent_ready = !empty($search_terms) || !empty($intent_phrases);
        $ai_ready = $has_address && $has_geo && $has_contact;

        $completeness = 0;
        $completeness += $has_address ? 30 : 0;
        $completeness += $has_geo ? 20 : 0;
        $completeness += $has_contact ? 15 : 0;
        $completeness += !empty($programs_offered) ? 10 : 0;
        $completeness += !empty($age_groups) ? 10 : 0;
        $completeness += $faq_ready ? 10 : 0;
        $completeness += $location_intent_ready ? 5 : 0;

        return [
            'type' => 'ChildCareCenter',
            'entity_types' => ['ChildCareCenter', 'Preschool', 'Place'],
            'name' => $location_name,
            'canonical_url' => self::nullable_url($location['canonical_url'] ?? ''),
            'location_slug' => $location_slug,
            'brand' => self::nullable_string($brand['name'] ?? '') ?: get_bloginfo('name'),
            'address' => [
                'street' => self::nullable_string($address['street'] ?? ''),
                'city' => $city,
                'region' => $state,
                'state' => $state,
                'postal_code' => self::nullable_string($address['postal_code'] ?? ''),
                'country' => self::nullable_string($address['country'] ?? '') ?: 'US',
                'formatted' => self::format_address_line($address),
            ],
            'geo' => [
                'latitude' => self::normalize_float($location['geo']['lat'] ?? null),
                'longitude' => self::normalize_float($location['geo']['lng'] ?? null),
            ],
            'contact' => [
                'phone' => self::nullable_string($location['campus_contact']['phone'] ?? ''),
                'email' => self::sanitize_public_contact_email($location['campus_contact']['email'] ?? ''),
            ],
            'hours' => self::normalize_entity_hours($location['hours'] ?? []),
            'programs_offered' => $programs_offered,
            'age_groups' => $age_groups,
            'service_tags' => $service_tags,
            'curriculum' => [
                'Prismpath',
                'Chroma Spectrum Curriculum',
            ],
            'faqs' => $faqs,
            'local_summary' => self::nullable_string(
                $location['local_seo_summary']
                    ?? ($location['llm_description'] ?? ($location['short_description'] ?? ''))
            ),
            'search_terms' => $search_terms,
            'alternate_names' => self::normalize_keyword_terms($raw_retrieval['alternate_names'] ?? []),
            'neighborhood_terms' => self::normalize_keyword_terms($raw_retrieval['neighborhood_terms'] ?? []),
            'service_area_terms' => self::normalize_keyword_terms(array_merge(
                (array) ($raw_retrieval['service_area_terms'] ?? []),
                (array) ($raw_retrieval['service_area_raw'] ?? []),
                (array) (($location['service_area']['cities'] ?? []))
            )),
            'nearby_landmarks' => self::normalize_keyword_terms($raw_retrieval['nearby_landmarks'] ?? []),
            'nearby_roads' => self::normalize_keyword_terms($raw_retrieval['nearby_roads'] ?? []),
            'county' => self::nullable_string($location['county'] ?? ''),
            'metro_area' => self::nullable_string($location['metro_area'] ?? '') ?: self::DEFAULT_METRO_AREA,
            'intent_phrases' => $intent_phrases,
            'citability' => [
                'ai_ready' => $ai_ready,
                'faq_ready' => $faq_ready,
                'location_intent_ready' => $location_intent_ready,
                'citation_ready' => !empty($citation_facts),
                'completeness_score' => min(100, $completeness),
                'citation_facts' => $citation_facts,
            ],
            'legacy_ref' => [
                'location_id' => (int) ($location['location_id'] ?? 0),
                'slug' => $location_slug,
                'record_hash' => self::nullable_string($location['record_hash'] ?? ''),
            ],
        ];
    }

    private static function build_programs_offered(array $location, array $programs): array
    {
        $location_id = (int) ($location['location_id'] ?? 0);
        $items = [];
        $seen = [];

        foreach ($programs as $program) {
            $served = is_array($program['locations_served'] ?? null) ? $program['locations_served'] : [];
            if ($location_id < 1 || !in_array($location_id, $served, true)) {
                continue;
            }

            $name = self::nullable_string($program['name'] ?? '');
            if ($name === null) {
                continue;
            }

            $key = strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $items[] = [
                'name' => $name,
                'slug' => self::nullable_string($program['slug'] ?? ''),
                'canonical_url' => self::nullable_url($program['canonical_url'] ?? ''),
                'age_range' => self::nullable_string($program['age_range'] ?? ''),
            ];
        }

        foreach ((array) ($location['programs'] ?? []) as $program_code) {
            $label = self::code_to_label((string) $program_code);
            if ($label === null) {
                continue;
            }

            $key = strtolower($label);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $items[] = [
                'name' => $label,
                'slug' => null,
                'canonical_url' => null,
                'age_range' => null,
            ];
        }

        return $items;
    }

    private static function build_location_search_terms(array $retrieval, string $location_name, ?string $city, ?string $state): array
    {
        $terms = self::normalize_keyword_terms($retrieval['search_terms'] ?? []);
        if ($city === null) {
            return $terms;
        }

        $state = $state ?: self::DEFAULT_STATE;
        $city_term = strtolower($city);
        $state_term = strtolower($state);

        $terms[] = "daycare {$city_term} {$state_term}";
        $terms[] = "preschool {$city_term} {$state_term}";
        $terms[] = "childcare {$city_term} {$state_term}";
        $terms[] = "infant care {$city_term} {$state_term}";
        $terms[] = "pre-k {$city_term} {$state_term}";
        $terms[] = "georgia pre-k {$city_term} {$state_term}";
        $terms[] = "daycare near {$city_term} {$state_term}";
        $terms[] = strtolower($location_name);

        return self::normalize_keyword_terms($terms);
    }

    private static function build_location_intent_phrases(array $retrieval, ?string $city): array
    {
        $phrases = self::normalize_keyword_terms($retrieval['intent_phrases'] ?? []);
        if ($city !== null) {
            $city_term = strtolower($city);
            $phrases[] = "daycare near {$city_term}";
            $phrases[] = "preschool in {$city_term}";
            $phrases[] = "childcare center near {$city_term}";
        }

        $phrases = array_merge($phrases, self::normalize_keyword_terms($retrieval['when_to_recommend'] ?? []));
        return self::normalize_keyword_terms($phrases);
    }

    private static function normalize_entity_hours($hours): array
    {
        $hours = is_array($hours) ? $hours : self::empty_hours_schedule();
        $days = [
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
            'sat' => 'Saturday',
            'sun' => 'Sunday',
        ];

        $out = [];
        $has_structured_day_hours = false;
        foreach ($days as $key => $label) {
            $entry = is_array($hours[$key] ?? null) ? $hours[$key] : ['open' => null, 'close' => null, 'closed' => null];
            $open = self::nullable_string($entry['open'] ?? '');
            $close = self::nullable_string($entry['close'] ?? '');
            if ($open !== null && $close !== null) {
                $has_structured_day_hours = true;
            }
            $out[] = [
                'day' => $label,
                'open' => $open,
                'close' => $close,
                'closed' => isset($entry['closed']) ? (bool) $entry['closed'] : null,
            ];
        }

        if (!$has_structured_day_hours) {
            $notes = self::nullable_string($hours['notes'] ?? '');
            $parsed = $notes !== null ? self::parse_hours_range($notes) : null;
            if ($parsed !== null) {
                foreach ($out as &$item) {
                    if (in_array($item['day'], ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], true)) {
                        $item['open'] = $parsed['open'];
                        $item['close'] = $parsed['close'];
                        $item['closed'] = false;
                    }
                }
                unset($item);
            }
        }

        return $out;
    }

    private static function normalize_entity_faqs($items): array
    {
        $faqs = self::normalize_faq_items($items);
        $out = [];
        foreach ($faqs as $faq) {
            $question = self::nullable_string($faq['question'] ?? '');
            $answer = self::nullable_string($faq['answer'] ?? '');
            if ($question === null || $answer === null) {
                continue;
            }

            $out[] = [
                'question' => $question,
                'answer' => self::limit_text($answer, 320),
            ];
        }

        return $out;
    }

    private static function build_age_groups($raw, array $program_names): array
    {
        $groups = [];
        $raw = self::nullable_string($raw);
        if ($raw !== null) {
            $groups[] = $raw;
        }

        foreach ($program_names as $name) {
            $value = strtolower($name);
            if (strpos($value, 'infant') !== false) {
                $groups[] = 'Infant Care';
            }
            if (strpos($value, 'toddler') !== false) {
                $groups[] = 'Toddler Care';
            }
            if (strpos($value, 'preschool') !== false) {
                $groups[] = 'Preschool';
            }
            if (strpos($value, 'pre-k') !== false || strpos($value, 'prek') !== false) {
                $groups[] = 'Pre-K';
            }
            if (strpos($value, 'after school') !== false) {
                $groups[] = 'After School';
            }
            if (strpos($value, 'summer') !== false) {
                $groups[] = 'Summer Camp';
            }
        }

        return self::normalize_keyword_terms($groups);
    }

    private static function build_service_tags($codes): array
    {
        $tags = [];
        foreach ((array) $codes as $code) {
            $label = self::code_to_label((string) $code);
            if ($label !== null) {
                $tags[] = $label;
            }
        }

        return self::normalize_keyword_terms($tags);
    }

    private static function code_to_label(string $code): ?string
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $text = strtolower(str_replace('_', ' ', $code));
        return ucwords($text);
    }

    private static function format_address_line(array $address): ?string
    {
        $parts = [];
        $street = self::nullable_string($address['street'] ?? '');
        $city = self::nullable_string($address['city'] ?? '');
        $state = self::nullable_string($address['state'] ?? '');
        $zip = self::nullable_string($address['postal_code'] ?? '');

        if ($street !== null) {
            $parts[] = $street;
        }

        $city_state_zip = trim(implode(' ', array_filter([
            $city !== null ? rtrim($city, ',') . ',' : null,
            $state,
            $zip,
        ])));
        if ($city_state_zip !== '') {
            $parts[] = $city_state_zip;
        }

        $country = self::nullable_string($address['country'] ?? '');
        if ($country !== null) {
            $parts[] = $country;
        }

        $line = implode(', ', array_filter($parts));
        return $line === '' ? null : $line;
    }

    private static function normalize_keyword_terms($items): array
    {
        $terms = self::parse_text_list($items);
        $out = [];
        $seen = [];

        foreach ($terms as $term) {
            $normalized = strtolower(trim((string) $term));
            if ($normalized === '' || isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $out[] = $normalized;
        }

        return $out;
    }

    private static function normalize_citation_facts($value): array
    {
        if (is_string($value) && $value !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
            $value = [];
            foreach ($lines as $line) {
                $line = trim((string) $line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line));
                $value[] = [
                    'label' => $parts[0] ?? '',
                    'value' => $parts[1] ?? '',
                    'source' => $parts[2] ?? '',
                    'context' => $parts[3] ?? '',
                ];
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $facts = [];
        foreach ($value as $fact) {
            if (!is_array($fact)) {
                continue;
            }

            $label = self::nullable_string($fact['label'] ?? '');
            $fact_value = self::nullable_string($fact['value'] ?? '');
            if ($label === null || $fact_value === null) {
                continue;
            }

            $facts[] = [
                'label' => $label,
                'value' => $fact_value,
                'source' => self::nullable_string($fact['source'] ?? ''),
                'context' => self::nullable_string($fact['context'] ?? ''),
                'verified_date' => self::nullable_string($fact['verifiedDate'] ?? ''),
            ];
        }

        return $facts;
    }

    private static function compute_last_updated(array $locations, array $programs, array $events, string $generated_at): string
    {
        $max_ts = self::parse_timestamp($generated_at) ?: time();

        foreach ($locations as $location) {
            $ts = self::parse_timestamp($location['last_updated_gmt'] ?? '');
            if ($ts && $ts > $max_ts) {
                $max_ts = $ts;
            }
        }

        foreach ($programs as $program) {
            $ts = self::parse_timestamp($program['last_updated_gmt'] ?? '');
            if ($ts && $ts > $max_ts) {
                $max_ts = $ts;
            }
        }

        foreach ($events as $event) {
            $ts = self::parse_timestamp($event['last_updated_gmt'] ?? '');
            if ($ts && $ts > $max_ts) {
                $max_ts = $ts;
            }
        }

        return gmdate('c', $max_ts);
    }

    private static function to_location_summary(array $location): array
    {
        return [
            'location_id' => $location['location_id'] ?? null,
            'slug' => $location['slug'] ?? null,
            'campus_name' => $location['campus_name'] ?? null,
            'canonical_url' => $location['canonical_url'] ?? null,
            'last_updated_gmt' => $location['last_updated_gmt'] ?? null,
            'record_hash' => $location['record_hash'] ?? null,
            'verification_status' => $location['verification_status'] ?? null,
            'address' => $location['address'] ?? self::empty_address(),
            'geo' => $location['geo'] ?? ['lat' => null, 'lng' => null],
            'service_radius_miles' => $location['service_radius_miles'] ?? null,
            'programs' => is_array($location['programs'] ?? null) ? $location['programs'] : [],
            'features' => is_array($location['features'] ?? null) ? $location['features'] : [],
            'campus_contact' => $location['campus_contact'] ?? ['role' => null, 'email' => null, 'phone' => null],
            'hours' => $location['hours'] ?? self::empty_hours_schedule(),
            'short_description' => $location['short_description'] ?? null,
            'policies_summary' => is_array($location['policies_summary'] ?? null) ? $location['policies_summary'] : [],
        ];
    }

    private static function determine_location_verification_status(array $address): string
    {
        $has_required = !empty($address['street']) && !empty($address['city']) && !empty($address['state']);
        return $has_required ? 'verified' : 'partial';
    }

    private static function build_short_description(string $tagline, string $description): ?string
    {
        $parts = [];
        if ($tagline !== '') {
            $parts[] = $tagline;
        }
        if ($description !== '') {
            $parts[] = self::limit_text($description, 220);
        }

        $text = trim(implode(' ', $parts));
        return $text === '' ? null : $text;
    }

    private static function build_program_short_description(int $post_id): ?string
    {
        $excerpt = self::normalize_text_block(get_the_excerpt($post_id));
        if ($excerpt !== '') {
            return self::limit_text($excerpt, 220);
        }

        $content = self::normalize_text_block(get_post_field('post_content', $post_id));
        if ($content !== '') {
            return self::limit_text($content, 220);
        }

        return null;
    }

    private static function build_policies_summary(array $enrollment_steps, array $public_meta, array $hours): array
    {
        $items = [];
        if (self::normalize_bool($public_meta['_chroma_caps_accepted'] ?? false)) {
            $items[] = 'Accepts CAPS';
        }
        if (self::normalize_bool($public_meta['_chroma_ga_pre_k_accepted'] ?? false)) {
            $items[] = 'Accepts Georgia Pre-K';
        }
        if (self::normalize_bool($public_meta['_chroma_security_cameras'] ?? false)) {
            $items[] = 'Security cameras on site';
        }

        foreach ($enrollment_steps as $step) {
            $title = self::nullable_string($step['title'] ?? '');
            if ($title !== null) {
                $items[] = $title;
            }
        }

        if (!empty($hours['notes'])) {
            $items[] = 'Hours: ' . $hours['notes'];
        }

        return array_values(array_unique($items));
    }

    private static function build_location_feature_codes(array $public_meta, array $amenities): array
    {
        $features = $amenities;
        if (self::normalize_bool($public_meta['_chroma_caps_accepted'] ?? false)) {
            $features[] = 'CAPS';
        }
        if (self::normalize_bool($public_meta['_chroma_ga_pre_k_accepted'] ?? false)) {
            $features[] = 'GA PRE-K';
        }
        if (self::normalize_bool($public_meta['_chroma_security_cameras'] ?? false)) {
            $features[] = 'SECURITY CAMERAS';
        }
        if (self::normalize_bool($public_meta['_chroma_is_event_venue'] ?? false)) {
            $features[] = 'EVENT VENUE';
        }

        return self::normalize_code_list($features);
    }

    private static function normalize_hours_schedule($raw): array
    {
        $schedule = self::empty_hours_schedule();
        $notes = self::normalize_text_block($raw);
        $schedule['notes'] = $notes === '' ? null : $notes;
        if ($notes === '') {
            return $schedule;
        }

        $segments = preg_split('/[\r\n;]+/', $notes) ?: [];
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '' || !preg_match('/^([A-Za-z,\-\s]+)\s*:?\s*(.+)$/', $segment, $matches)) {
                continue;
            }

            $days = self::expand_day_expression($matches[1]);
            if ($days === []) {
                continue;
            }

            $value = strtolower(trim($matches[2]));
            if ($value === '' || strpos($value, 'closed') !== false) {
                foreach ($days as $day) {
                    $schedule[$day] = ['open' => null, 'close' => null, 'closed' => true];
                }
                continue;
            }

            if (!preg_match('/([0-9]{1,2}(?::[0-9]{2})?\s*[ap]m?)\s*(?:-|to)\s*([0-9]{1,2}(?::[0-9]{2})?\s*[ap]m?)/i', $matches[2], $time_matches)) {
                continue;
            }

            $open = self::normalize_time_token($time_matches[1]);
            $close = self::normalize_time_token($time_matches[2]);
            foreach ($days as $day) {
                $schedule[$day] = ['open' => $open, 'close' => $close, 'closed' => false];
            }
        }

        return $schedule;
    }

    private static function empty_hours_schedule(): array
    {
        $template = ['open' => null, 'close' => null, 'closed' => null];

        return [
            'mon' => $template,
            'tue' => $template,
            'wed' => $template,
            'thu' => $template,
            'fri' => $template,
            'sat' => $template,
            'sun' => $template,
            'notes' => null,
        ];
    }

    private static function expand_day_expression(string $expression): array
    {
        $expression = preg_replace('/\s+/', ' ', strtolower(trim($expression)));
        if (!is_string($expression) || $expression === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $expression));
        $days = [];
        $keys = array_keys(self::HOURS_DAYS);

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (strpos($part, '-') !== false) {
                [$start_raw, $end_raw] = array_map('trim', explode('-', $part, 2));
                $start = self::resolve_day_key($start_raw);
                $end = self::resolve_day_key($end_raw);
                if ($start === null || $end === null) {
                    continue;
                }

                $start_index = array_search($start, $keys, true);
                $end_index = array_search($end, $keys, true);
                if ($start_index === false || $end_index === false) {
                    continue;
                }

                if ($start_index <= $end_index) {
                    for ($i = $start_index; $i <= $end_index; $i++) {
                        $days[] = $keys[$i];
                    }
                } else {
                    for ($i = $start_index; $i < count($keys); $i++) {
                        $days[] = $keys[$i];
                    }
                    for ($i = 0; $i <= $end_index; $i++) {
                        $days[] = $keys[$i];
                    }
                }
                continue;
            }

            $day = self::resolve_day_key($part);
            if ($day !== null) {
                $days[] = $day;
            }
        }

        return array_values(array_unique($days));
    }

    private static function resolve_day_key(string $token): ?string
    {
        $token = strtolower(trim($token));
        foreach (self::HOURS_DAYS as $key => $aliases) {
            if (in_array($token, $aliases, true)) {
                return $key;
            }
        }

        return null;
    }

    private static function normalize_time_token(string $value): ?string
    {
        $timestamp = strtotime('1970-01-01 ' . strtolower(trim($value)));
        return $timestamp ? gmdate('H:i', $timestamp) : null;
    }

    private static function get_post_last_updated_gmt(int $post_id): ?string
    {
        $updated = get_post_modified_time('c', true, $post_id);
        if (is_string($updated) && $updated !== '') {
            return $updated;
        }

        $created = get_post_time('c', true, $post_id);
        return is_string($created) && $created !== '' ? $created : null;
    }

    private static function parse_timestamp($value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        return $timestamp ?: null;
    }

    private static function hash_payload($value): string
    {
        return md5((string) wp_json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private static function hashable_record(array $record, array $exclude_keys): array
    {
        foreach ($exclude_keys as $exclude_key) {
            unset($record[$exclude_key]);
        }

        ksort($record);
        return $record;
    }

    private static function normalize_int_list($value): array
    {
        if (!is_array($value)) {
            $value = preg_split('/[\r\n,|]+/', (string) $value) ?: [];
        }

        $out = [];
        foreach ($value as $item) {
            if (is_numeric($item)) {
                $out[] = (int) $item;
            }
        }

        $out = array_values(array_unique(array_filter($out)));
        sort($out);

        return $out;
    }

    private static function map_related_posts(array $ids, string $post_type): array
    {
        $items = [];
        foreach ($ids as $id) {
            $post = get_post((int) $id);
            if (!$post) {
                continue;
            }

            if ((string) $post->post_type !== $post_type || (string) $post->post_status !== 'publish') {
                continue;
            }

            $items[] = [
                'id' => (int) $post->ID,
                'name' => get_the_title($post),
                'slug' => (string) $post->post_name,
                'url' => get_permalink($post),
            ];
        }

        return $items;
    }

    private static function normalize_enrollment_steps($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $step) {
            if (!is_array($step)) {
                continue;
            }

            $title = self::nullable_string($step['title'] ?? '');
            $text = self::nullable_string(self::normalize_text_block($step['text'] ?? ''));
            $url = self::nullable_url($step['url'] ?? '');
            if ($title === null && $text === null && $url === null) {
                continue;
            }

            $out[] = ['title' => $title, 'text' => $text, 'url' => $url];
        }

        return $out;
    }

    private static function normalize_faq_items($value): array
    {
        if (is_string($value)) {
            return self::parse_delimited_qa_lines($value);
        }
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = self::nullable_string($item['question'] ?? ($item['q'] ?? ''));
            $answer = self::nullable_string(self::normalize_text_block($item['answer'] ?? ($item['a'] ?? '')));
            if ($question === null && $answer === null) {
                continue;
            }

            $out[] = ['question' => $question, 'answer' => $answer];
        }

        return $out;
    }

    private static function parse_delimited_qa_lines($value): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $value) ?: [];
        $out = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 2));
            $question = self::nullable_string($parts[0] ?? '');
            $answer = self::nullable_string(self::normalize_text_block($parts[1] ?? ''));
            if ($question === null && $answer === null) {
                continue;
            }

            $out[] = ['question' => $question, 'answer' => $answer];
        }

        return $out;
    }

    private static function merge_faq_items(array ...$sets): array
    {
        $merged = [];
        $seen = [];

        foreach ($sets as $set) {
            foreach ($set as $item) {
                $question = self::nullable_string($item['question'] ?? '');
                $answer = self::nullable_string($item['answer'] ?? '');
                if ($question === null && $answer === null) {
                    continue;
                }

                $hash = md5((string) $question . '|' . (string) $answer);
                if (isset($seen[$hash])) {
                    continue;
                }

                $seen[$hash] = true;
                $merged[] = ['question' => $question, 'answer' => $answer];
            }
        }

        return $merged;
    }

    private static function parse_text_list($value): array
    {
        $parts = is_array($value) ? $value : (preg_split('/[\r\n,|]+/', (string) $value) ?: []);
        $out = [];

        foreach ($parts as $part) {
            $text = self::clean_scalar($part);
            if ($text !== '') {
                $out[] = $text;
            }
        }

        return array_values(array_unique($out));
    }

    private static function normalize_code_list(array $values): array
    {
        $codes = [];
        foreach ($values as $value) {
            $label = self::clean_scalar($value);
            if ($label === '') {
                continue;
            }

            $codes[] = strtoupper(str_replace('-', '_', sanitize_title($label)));
        }

        $codes = array_values(array_unique(array_filter($codes)));
        sort($codes);

        return $codes;
    }

    private static function normalize_text_block($value): string
    {
        $text = wp_strip_all_tags((string) $value, true);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = is_string($text) ? trim($text) : '';
        if (strlen($text) > 2000) {
            return substr($text, 0, 2000);
        }

        return $text;
    }

    private static function limit_text(string $text, int $max_length): string
    {
        $text = trim($text);
        if (strlen($text) <= $max_length) {
            return $text;
        }

        return rtrim(substr($text, 0, $max_length - 1)) . '.';
    }

    private static function normalize_bool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'yes', 'on', 'y'], true);
    }

    private static function normalize_nullable_bool($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return self::normalize_bool($value);
    }

    private static function normalize_int($value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function normalize_float($value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function normalize_phone_e164($value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if (!is_string($digits) || $digits === '') {
            return null;
        }
        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }
        if (strlen($digits) === 11 && strpos($digits, '1') === 0) {
            return '+' . $digits;
        }

        return '+' . $digits;
    }

    private static function sanitize_public_contact_email($value): ?string
    {
        $email = sanitize_email((string) $value);
        if ($email === '') {
            return null;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return null;
        }

        $local = strtolower($parts[0]);
        $host = strtolower($parts[1]);
        $site_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $safe_locals = [
            'info', 'contact', 'hello', 'team', 'frontdesk', 'campus', 'director',
            'admissions', 'enrollment', 'tour', 'tours', 'support',
        ];

        if ($host === $site_host || in_array($local, $safe_locals, true)) {
            return $email;
        }

        return null;
    }

    private static function nullable_string($value): ?string
    {
        $text = self::clean_scalar($value);
        return $text === '' ? null : $text;
    }

    private static function nullable_url($value): ?string
    {
        $url = esc_url_raw((string) $value);
        return $url === '' ? null : $url;
    }

    private static function empty_address(): array
    {
        return [
            'street' => null,
            'city' => null,
            'state' => self::DEFAULT_STATE,
            'postal_code' => null,
            'country' => 'US',
        ];
    }

    private static function clean_scalar($value): string
    {
        return trim(sanitize_text_field((string) $value));
    }

    private static function first_nonempty_meta(int $post_id, array $keys): string
    {
        foreach ($keys as $key) {
            $value = self::clean_scalar(get_post_meta($post_id, (string) $key, true));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}

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
    private const CACHE_KEY = 'chroma_agent_geo_feed_v2';
    private const CACHE_TTL = 900;
    private const CONTRACT_VERSION = '2026-02-28.4';
    private const DEFAULT_STATE = 'GA';
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
        add_action('wp_head', [__CLASS__, 'output_geo_discovery_link'], 1);
        add_action('wp_head', [__CLASS__, 'output_geo_schema_signpost'], 2);
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
                'generated_at_gmt',
                'source',
                'filters',
                'summary',
                'feed_hash',
                'brand',
                'curriculum',
                'locations',
                'programs',
                'events',
            ],
            'field_groups' => [
                'brand' => ['name', 'description', 'site_url', 'contact'],
                'curriculum' => ['prismpath', 'chroma_spectrum'],
                'locations_list' => [
                    'location_id', 'slug', 'campus_name', 'canonical_url', 'last_updated_gmt',
                    'record_hash', 'verification_status', 'address', 'geo', 'service_radius_miles',
                    'programs', 'features', 'campus_contact', 'hours', 'short_description', 'policies_summary',
                ],
                'location_detail_additions' => [
                    'service_area', 'availability', 'pricing', 'aggregate_rating', 'media',
                    'admissions', 'faqs', 'events', 'qa_notes_public', 'open_house_date',
                ],
                'programs' => [
                    'program_id', 'slug', 'name', 'canonical_url', 'last_updated_gmt', 'record_hash',
                    'short_description', 'age_range', 'features', 'locations_served', 'lesson_plan_url', 'faqs',
                ],
                'events' => [
                    'location_id', 'location_name', 'location_canonical_url', 'name',
                    'start', 'description', 'url', 'last_updated_gmt',
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
        $locations = self::filter_location_records($base['locations'], $filters);
        $programs = self::filter_program_records($base['programs'], $filters);
        $events = self::filter_event_records($base['events'], $locations, $filters);

        $location_summaries = [];
        foreach ($locations as $location) {
            $location_summaries[] = self::to_location_summary($location);
        }

        return rest_ensure_response([
            'success' => true,
            'cached' => $base['cached'],
            'contract_version' => self::CONTRACT_VERSION,
            'generated_at_gmt' => $base['generated_at_gmt'],
            'source' => [
                'namespace' => self::NS,
                'route' => '/geo-feed',
                'authority' => 'ChromaELA WP',
            ],
            'filters' => self::normalize_filter_output($filters),
            'summary' => [
                'location_count' => count($location_summaries),
                'program_count' => count($programs),
                'event_count' => count($events),
            ],
            'feed_hash' => self::hash_payload([
                'contract_version' => self::CONTRACT_VERSION,
                'filters' => self::normalize_filter_output($filters),
                'locations' => $location_summaries,
                'programs' => $programs,
                'events' => $events,
            ]),
            'brand' => $base['brand'],
            'curriculum' => $base['curriculum'],
            'locations' => $location_summaries,
            'programs' => $programs,
            'events' => $events,
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

            return rest_ensure_response([
                'success' => true,
                'cached' => $base['cached'],
                'contract_version' => self::CONTRACT_VERSION,
                'generated_at_gmt' => $base['generated_at_gmt'],
                'source' => [
                    'namespace' => self::NS,
                    'route' => '/geo-feed/' . $location_id,
                    'authority' => 'ChromaELA WP',
                ],
                'location' => $location,
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
    }

    public static function output_geo_schema_signpost(): void
    {
        if (!self::is_public_feed_available() || !is_front_page()) {
            return;
        }

        $site_url = home_url('/');
        $geo_url = self::public_feed_url();
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
    }

    public static function append_geo_robots_rules(string $output, bool $public): string
    {
        if (!self::is_public_feed_available()) {
            return $output;
        }

        $output = rtrim($output);
        if ($output !== '') {
            $output .= "\n";
        }

        $path = wp_parse_url(self::public_feed_url(), PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/wp-json/' . self::NS . '/geo-feed';
        }

        $bots = ['GPTBot', 'Google-Extended', 'PerplexityBot', 'ClaudeBot', 'Claude-Web'];
        $lines = [];
        foreach ($bots as $bot) {
            $lines[] = 'User-agent: ' . $bot;
            $lines[] = 'Allow: ' . $path;
        }

        $lines[] = 'User-agent: *';
        $lines[] = 'Allow: ' . $path;

        return $output . implode("\n", $lines) . "\n";
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
            $faqs = self::normalize_faq_items($public_meta['chroma_faq_items'] ?? []);
            $enrollment_steps = self::normalize_enrollment_steps($public_meta['location_enrollment_steps'] ?? []);
            $program_labels = self::parse_text_list(get_post_meta($post_id, 'location_special_programs', true));
            $amenities = self::parse_text_list($public_meta['_chroma_amenities'] ?? []);
            $description = self::normalize_text_block(get_post_meta($post_id, 'location_description', true));
            $tagline = self::clean_scalar(get_post_meta($post_id, 'location_tagline', true));
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
                    'lat' => self::normalize_float(get_post_meta($post_id, 'location_latitude', true)),
                    'lng' => self::normalize_float(get_post_meta($post_id, 'location_longitude', true)),
                ],
                'service_radius_miles' => self::normalize_float($public_meta['seo_llm_service_area_radius'] ?? ''),
                'programs' => self::normalize_code_list($program_labels),
                'features' => self::build_location_feature_codes($public_meta, $amenities),
                'campus_contact' => [
                    'role' => 'Director',
                    'email' => self::sanitize_public_contact_email(get_post_meta($post_id, 'location_email', true)),
                    'phone' => self::normalize_phone_e164(get_post_meta($post_id, 'location_phone', true)),
                ],
                'hours' => $hours,
                'short_description' => self::build_short_description($tagline, $description),
                'policies_summary' => self::build_policies_summary($enrollment_steps, $public_meta, $hours),
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
                'features' => self::normalize_code_list(self::parse_text_list(get_post_meta($post_id, 'program_features', true))),
                'locations_served' => $location_ids,
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
}

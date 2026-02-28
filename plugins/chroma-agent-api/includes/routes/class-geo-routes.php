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
    private const CACHE_KEY = 'chroma_agent_geo_feed_v1';
    private const CACHE_TTL = 900;
    private const CONTRACT_VERSION = '2026-02-28';
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
        'program_anchor_slug',
        'program_seo_heading',
        'program_seo_summary',
        'program_seo_highlights',
        'program_meta_title',
        'program_meta_description',
        'program_faq_items',
        'program_lesson_plan_file',
        'program_locations',
        'program_locations_served',
        'program_prerequisites',
        'program_related',
        'chroma_faq_items',
    ];

    public static function init(): void
    {
        add_action('save_post', [__CLASS__, 'on_post_change'], 10, 2);
        add_action('deleted_post', [__CLASS__, 'on_post_delete']);
        add_action('updated_option', [__CLASS__, 'on_option_change'], 10, 3);
    }

    public static function register(): void
    {
        register_rest_route(self::NS, '/geo-feed', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_geo_feed'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function describe_contract(): array
    {
        return [
            'route' => '/wp-json/' . self::NS . '/geo-feed',
            'contract_version' => self::CONTRACT_VERSION,
            'public' => true,
            'cache_ttl_seconds' => self::CACHE_TTL,
            'top_level_fields' => [
                'success',
                'cached',
                'contract_version',
                'generated_at_gmt',
                'source',
                'summary',
                'brand',
                'locations',
                'programs',
                'events',
            ],
            'field_groups' => [
                'brand' => [
                    'name',
                    'description',
                    'site_url',
                    'contact',
                    'curriculum',
                ],
                'locations' => [
                    'id',
                    'campus_name',
                    'slug',
                    'url',
                    'address',
                    'phone_number',
                    'email',
                    'administrator_name',
                    'programs_offered',
                    'ages_accepted',
                    'operating_hours',
                    'facility_highlights',
                    'service_areas',
                    'coordinates',
                    'media',
                    'availability',
                    'pricing',
                    'aggregate_rating',
                    'service_area_geo',
                    'facility_profile',
                    'admissions',
                    'faqs',
                    'events',
                    'open_house_date',
                ],
                'programs' => [
                    'id',
                    'name',
                    'slug',
                    'url',
                    'summary',
                    'age_range',
                    'cta_text',
                    'features',
                    'anchor_slug',
                    'lesson_plan_url',
                    'seo',
                    'faqs',
                    'locations_served',
                    'prerequisites',
                    'related_programs',
                ],
                'events' => [
                    'location',
                    'location_url',
                    'name',
                    'start',
                    'description',
                    'url',
                ],
            ],
        ];
    }

    public static function get_geo_feed(WP_REST_Request $request)
    {
        $refresh = Utils::truthy($request->get_param('refresh'));
        $cached = get_transient(self::CACHE_KEY);

        if (!$refresh && is_array($cached)) {
            $cached['cached'] = true;
            return rest_ensure_response($cached);
        }

        $locations = self::get_locations();
        $programs = self::get_programs();
        $events = self::get_public_events($locations);
        $payload = [
            'success' => true,
            'cached' => false,
            'contract_version' => self::CONTRACT_VERSION,
            'generated_at_gmt' => gmdate('c'),
            'source' => [
                'namespace' => self::NS,
                'route' => '/geo-feed',
            ],
            'summary' => [
                'location_count' => count($locations),
                'program_count' => count($programs),
                'event_count' => count($events),
            ],
            'brand' => self::get_brand_payload(),
            'locations' => $locations,
            'programs' => $programs,
            'events' => $events,
        ];
        $payload = self::compact_public_payload($payload);

        set_transient(self::CACHE_KEY, $payload, self::CACHE_TTL);

        return rest_ensure_response($payload);
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

    private static function get_brand_payload(): array
    {
        return [
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'site_url' => home_url('/'),
            'contact' => [
                'phone' => self::clean_scalar(get_option('chroma_seo_phone', '')),
                'email' => sanitize_email((string) get_option('chroma_seo_email', '')),
            ],
            'curriculum' => [
                'brand_context' => self::normalize_text_block(get_option('chroma_llm_brand_context', '')),
                'brand_voice' => self::normalize_text_block(get_option('chroma_llm_brand_voice', '')),
            ],
        ];
    }

    private static function get_locations(): array
    {
        if (!post_type_exists('location')) {
            return [];
        }

        $q = new WP_Query([
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
        foreach ((array) $q->posts as $post) {
            $post_id = (int) $post->ID;
            $public_meta = self::build_public_meta_snapshot($post_id, self::LOCATION_PUBLIC_META_ALLOWLIST);

            $items[] = [
                'id' => $post_id,
                'campus_name' => get_the_title($post_id),
                'slug' => (string) $post->post_name,
                'url' => get_permalink($post_id),
                'address' => [
                    'street' => self::clean_scalar(get_post_meta($post_id, 'location_address', true)),
                    'city' => self::clean_scalar(get_post_meta($post_id, 'location_city', true)),
                    'state' => self::clean_scalar(get_post_meta($post_id, 'location_state', true)),
                    'postal_code' => self::clean_scalar(get_post_meta($post_id, 'location_zip', true)),
                    'country' => 'US',
                ],
                'phone_number' => self::clean_scalar(get_post_meta($post_id, 'location_phone', true)),
                'email' => sanitize_email((string) get_post_meta($post_id, 'location_email', true)),
                'administrator_name' => self::clean_scalar(get_post_meta($post_id, 'location_director_name', true)),
                'programs_offered' => self::parse_text_list(get_post_meta($post_id, 'location_special_programs', true)),
                'ages_accepted' => self::clean_scalar(get_post_meta($post_id, 'location_ages_served', true)),
                'operating_hours' => self::clean_scalar(get_post_meta($post_id, 'location_hours', true)),
                'facility_highlights' => [
                    'tagline' => self::clean_scalar(get_post_meta($post_id, 'location_tagline', true)),
                    'description' => self::normalize_text_block(get_post_meta($post_id, 'location_description', true)),
                    'seo_title' => self::clean_scalar(get_post_meta($post_id, 'location_seo_content_title', true)),
                    'seo_text' => self::normalize_text_block(get_post_meta($post_id, 'location_seo_content_text', true)),
                ],
                'service_areas' => self::parse_text_list(get_post_meta($post_id, 'location_service_areas', true)),
                'coordinates' => [
                    'latitude' => self::clean_scalar(get_post_meta($post_id, 'location_latitude', true)),
                    'longitude' => self::clean_scalar(get_post_meta($post_id, 'location_longitude', true)),
                ],
                'media' => [
                    'video_tour_url' => esc_url_raw((string) ($public_meta['location_video_tour_url'] ?? '')),
                    'video_thumbnail_url' => esc_url_raw((string) ($public_meta['location_video_thumbnail'] ?? '')),
                    'video_duration' => self::clean_scalar($public_meta['location_video_duration'] ?? ''),
                ],
                'availability' => [
                    'status' => self::clean_scalar($public_meta['location_availability_status'] ?? ''),
                    'spots_available' => self::clean_scalar($public_meta['location_spots_available'] ?? ''),
                ],
                'pricing' => [
                    'min' => self::clean_scalar($public_meta['location_price_min'] ?? ''),
                    'max' => self::clean_scalar($public_meta['location_price_max'] ?? ''),
                    'currency' => self::clean_scalar($public_meta['location_price_currency'] ?? ''),
                    'frequency' => self::clean_scalar($public_meta['location_price_frequency'] ?? ''),
                ],
                'aggregate_rating' => [
                    'value' => self::clean_scalar($public_meta['seo_llm_aggregate_rating_value'] ?? ''),
                    'count' => self::clean_scalar($public_meta['seo_llm_aggregate_rating_count'] ?? ''),
                    'best' => self::clean_scalar($public_meta['seo_llm_aggregate_rating_best'] ?? ''),
                    'worst' => self::clean_scalar($public_meta['seo_llm_aggregate_rating_worst'] ?? ''),
                ],
                'service_area_geo' => [
                    'latitude' => self::clean_scalar($public_meta['seo_llm_service_area_lat'] ?? ''),
                    'longitude' => self::clean_scalar($public_meta['seo_llm_service_area_lng'] ?? ''),
                    'radius_miles' => self::clean_scalar($public_meta['seo_llm_service_area_radius'] ?? ''),
                    'cities' => self::parse_text_list($public_meta['seo_llm_service_area_cities'] ?? []),
                    'state' => self::clean_scalar($public_meta['seo_llm_service_area_state'] ?? ''),
                ],
                'facility_profile' => [
                    'is_event_venue' => self::normalize_bool($public_meta['_chroma_is_event_venue'] ?? ''),
                    'accepts_caps' => self::normalize_bool($public_meta['_chroma_caps_accepted'] ?? ''),
                    'accepts_ga_pre_k' => self::normalize_bool($public_meta['_chroma_ga_pre_k_accepted'] ?? ''),
                    'security_cameras' => self::normalize_bool($public_meta['_chroma_security_cameras'] ?? ''),
                    'amenities' => self::parse_text_list($public_meta['_chroma_amenities'] ?? []),
                ],
                'admissions' => [
                    'enrollment_steps' => self::normalize_enrollment_steps($public_meta['location_enrollment_steps'] ?? []),
                ],
                'faqs' => self::normalize_faq_items($public_meta['chroma_faq_items'] ?? []),
                'events' => self::sanitize_location_events(get_post_meta($post_id, 'location_events', true)),
                'open_house_date' => self::clean_scalar(get_post_meta($post_id, '_chroma_open_house_date', true)),
            ];
        }

        wp_reset_postdata();

        return $items;
    }

    private static function get_programs(): array
    {
        if (!post_type_exists('program')) {
            return [];
        }

        $q = new WP_Query([
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
        foreach ((array) $q->posts as $post) {
            $post_id = (int) $post->ID;
            $public_meta = self::build_public_meta_snapshot($post_id, self::PROGRAM_PUBLIC_META_ALLOWLIST);
            $location_ids = self::normalize_int_list($public_meta['program_locations_served'] ?? ($public_meta['program_locations'] ?? []));
            $items[] = [
                'id' => $post_id,
                'name' => get_the_title($post_id),
                'slug' => (string) $post->post_name,
                'url' => get_permalink($post_id),
                'summary' => self::normalize_text_block(get_the_excerpt($post_id)),
                'age_range' => self::clean_scalar(get_post_meta($post_id, 'program_age_range', true)),
                'cta_text' => self::clean_scalar(get_post_meta($post_id, 'program_cta_text', true)),
                'features' => self::parse_text_list(get_post_meta($post_id, 'program_features', true)),
                'anchor_slug' => self::clean_scalar($public_meta['program_anchor_slug'] ?? ''),
                'lesson_plan_url' => esc_url_raw((string) ($public_meta['program_lesson_plan_file'] ?? '')),
                'seo' => [
                    'heading' => self::clean_scalar($public_meta['program_seo_heading'] ?? ''),
                    'summary' => self::normalize_text_block($public_meta['program_seo_summary'] ?? ''),
                    'highlights' => self::parse_text_list($public_meta['program_seo_highlights'] ?? ''),
                    'meta_title' => self::clean_scalar($public_meta['program_meta_title'] ?? ''),
                    'meta_description' => self::normalize_text_block($public_meta['program_meta_description'] ?? ''),
                ],
                'faqs' => self::merge_faq_items(
                    self::parse_delimited_qa_lines($public_meta['program_faq_items'] ?? ''),
                    self::normalize_faq_items($public_meta['chroma_faq_items'] ?? [])
                ),
                'locations_served' => self::map_related_posts($location_ids, 'location'),
                'prerequisites' => self::map_related_posts(
                    self::normalize_int_list($public_meta['program_prerequisites'] ?? []),
                    'program'
                ),
                'related_programs' => self::map_related_posts(
                    self::normalize_int_list($public_meta['program_related'] ?? []),
                    'program'
                ),
            ];
        }

        wp_reset_postdata();

        return $items;
    }

    private static function get_public_events(array $locations): array
    {
        $items = [];
        foreach ($locations as $location) {
            if (!is_array($location)) {
                continue;
            }

            $location_name = isset($location['campus_name']) ? (string) $location['campus_name'] : '';
            $location_url = isset($location['url']) ? (string) $location['url'] : '';

            $events = is_array($location['events'] ?? null) ? $location['events'] : [];
            foreach ($events as $event) {
                $items[] = [
                    'location' => $location_name,
                    'location_url' => $location_url,
                    'name' => self::clean_scalar($event['name'] ?? ''),
                    'start' => self::clean_scalar($event['start'] ?? ''),
                    'description' => self::normalize_text_block($event['description'] ?? ''),
                    'url' => esc_url_raw((string) ($event['url'] ?? '')),
                ];
            }

            $open_house = self::clean_scalar($location['open_house_date'] ?? '');
            if ($open_house !== '') {
                $items[] = [
                    'location' => $location_name,
                    'location_url' => $location_url,
                    'name' => 'Open House',
                    'start' => $open_house,
                    'description' => '',
                    'url' => $location_url,
                ];
            }
        }

        return $items;
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

            $name = self::clean_scalar($event['name'] ?? '');
            $start = self::clean_scalar($event['start'] ?? '');

            if ($name === '' && $start === '') {
                continue;
            }

            $out[] = [
                'name' => $name,
                'start' => $start,
                'description' => self::normalize_text_block($event['description'] ?? ''),
                'url' => esc_url_raw((string) ($event['url'] ?? '')),
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

            $value = get_post_meta($post_id, $key, true);
            if ($value === '' || $value === null || $value === []) {
                continue;
            }

            $snapshot[$key] = $value;
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

            $title = self::clean_scalar($step['title'] ?? '');
            $text = self::normalize_text_block($step['text'] ?? '');
            $url = esc_url_raw((string) ($step['url'] ?? ''));

            if ($title === '' && $text === '' && $url === '') {
                continue;
            }

            $out[] = [
                'title' => $title,
                'text' => $text,
                'url' => $url,
            ];
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

            $question = self::clean_scalar($item['question'] ?? ($item['q'] ?? ''));
            $answer = self::normalize_text_block($item['answer'] ?? ($item['a'] ?? ''));

            if ($question === '' && $answer === '') {
                continue;
            }

            $out[] = [
                'question' => $question,
                'answer' => $answer,
            ];
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
            $question = self::clean_scalar($parts[0] ?? '');
            $answer = self::normalize_text_block($parts[1] ?? '');

            if ($question === '' && $answer === '') {
                continue;
            }

            $out[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $out;
    }

    private static function merge_faq_items(array ...$sets): array
    {
        $merged = [];
        $seen = [];

        foreach ($sets as $set) {
            foreach ($set as $item) {
                $question = self::clean_scalar($item['question'] ?? '');
                $answer = self::normalize_text_block($item['answer'] ?? '');
                if ($question === '' && $answer === '') {
                    continue;
                }

                $hash = md5($question . '|' . $answer);
                if (isset($seen[$hash])) {
                    continue;
                }

                $seen[$hash] = true;
                $merged[] = [
                    'question' => $question,
                    'answer' => $answer,
                ];
            }
        }

        return $merged;
    }

    private static function parse_text_list($value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = preg_split('/[\r\n,|]+/', (string) $value) ?: [];
        }

        $out = [];
        foreach ($parts as $part) {
            $part = self::clean_scalar($part);
            if ($part !== '') {
                $out[] = $part;
            }
        }

        return array_values(array_unique($out));
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

    private static function compact_public_payload($value)
    {
        if (is_array($value)) {
            $out = [];
            $is_list = self::is_list_array($value);

            foreach ($value as $key => $item) {
                $item = self::compact_public_payload($item);
                if ($item === null || $item === '' || $item === []) {
                    continue;
                }

                if ($is_list) {
                    $out[] = $item;
                } else {
                    $out[$key] = $item;
                }
            }

            return $out;
        }

        if (is_string($value)) {
            $value = trim($value);
            return $value === '' ? null : $value;
        }

        return $value;
    }

    private static function is_list_array(array $value): bool
    {
        $index = 0;
        foreach (array_keys($value) as $key) {
            if ($key !== $index) {
                return false;
            }
            $index++;
        }

        return true;
    }

    private static function clean_scalar($value): string
    {
        return trim(sanitize_text_field((string) $value));
    }
}

<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Diff;
use ChromaAgentAPI\Field_Registry;
use ChromaAgentAPI\Route_Utils;
use ChromaAgentAPI\Utils;
use WP_Query;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class SEO_Operation_Routes
{
    private const NS = 'chroma-agent/v1';

    public static function register(): void
    {
        register_rest_route(self::NS, '/seo/structured-meta/(?P<post_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_structured_meta'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_structured_meta'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/seo/actions', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'list_actions'],
            'permission_callback' => [__CLASS__, 'read_permission'],
        ]);

        register_rest_route(self::NS, '/seo/actions/(?P<action>[a-z0-9_-]+)', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'run_action'],
            'permission_callback' => [__CLASS__, 'write_permission'],
        ]);

        register_rest_route(self::NS, '/llm/settings', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_llm_settings'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_llm_settings'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/translations', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'list_translations'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/translations/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_translation'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_translation'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [__CLASS__, 'delete_translation'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/translate', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'translate'],
            'permission_callback' => [__CLASS__, 'write_permission'],
        ]);
    }

    public static function read_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['read:seo']);
    }

    public static function write_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['write:seo']);
    }

    public static function get_structured_meta(WP_REST_Request $request)
    {
        $post_id = (int) $request['post_id'];
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $fields = self::filter_fields_from_query(Field_Registry::seo_meta_fields(), $request);
        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'fields' => $fields,
            'data' => Route_Utils::read_post_meta_values($post_id, $fields),
        ]);
    }

    public static function set_structured_meta(WP_REST_Request $request)
    {
        return Route_Utils::write_post_meta(
            $request,
            (int) $request['post_id'],
            Route_Utils::updates_from_request($request),
            Field_Registry::seo_meta_fields(),
            [],
            'write:seo',
            'seo_structured_meta'
        );
    }

    public static function list_actions(WP_REST_Request $request)
    {
        return rest_ensure_response([
            'success' => true,
            'data' => Field_Registry::seo_action_catalog(),
        ]);
    }

    public static function run_action(WP_REST_Request $request)
    {
        $action = sanitize_key((string) $request['action']);
        $catalog = Field_Registry::seo_action_catalog();
        if (!isset($catalog[$action])) {
            return new \WP_Error('caa_unknown_seo_action', 'Unknown SEO action.', ['status' => 404]);
        }

        if (empty($catalog[$action]['implemented'])) {
            return new \WP_Error(
                'caa_seo_action_not_adapted',
                'This native AJAX action is inventoried but does not yet have a direct agent-safe adapter.',
                [
                    'status' => 501,
                    'action' => $action,
                    'native_ajax_hook_present' => !empty($catalog[$action]['native_ajax_hook_present']),
                ]
            );
        }

        switch ($action) {
            case 'chroma_validate_page_schema':
                return self::validate_post_schema($request);
            case 'chroma_fix_schema_with_ai':
                return self::generate_schema($request, true);
            case 'chroma_fetch_llm_data':
                return self::fetch_llm_data($request);
            case 'chroma_save_llm_targeting':
                return self::save_llm_targeting($request);
            case 'chroma_review_schema':
                return self::review_schema($request);
            case 'chroma_get_review_queue':
                return self::get_review_queue($request);
            case 'chroma_fetch_schema_inspector':
                return self::fetch_schema_inspector($request);
            case 'chroma_save_schema_inspector':
                return self::save_schema_inspector($request);
            case 'chroma_scan_schema_batch':
                return self::scan_schema_batch($request);
            case 'chroma_get_schema_fields':
                return self::get_schema_fields($request);
            case 'chroma_fetch_social_preview':
                return self::fetch_social_preview($request);
            case 'chroma_reset_post_schema':
                return self::reset_post_schema($request);
            case 'chroma_validate_post_schema':
            case 'chroma_validate_schema':
                return self::validate_post_schema($request);
            case 'chroma_apply_schema_fix':
                return self::apply_schema_fix($request);
            case 'chroma_fetch_live_schema':
                return self::fetch_live_schema($request);
            case 'chroma_sync_schema_to_builder':
                return self::sync_schema_to_builder($request);
            case 'chroma_clear_validation_cache':
                return self::clear_validation_cache($request);
            case 'chroma_save_validator_setting':
                return self::save_validator_setting($request);
            case 'chroma_save_llm_settings':
                return self::set_llm_settings($request);
            case 'chroma_test_llm_connection':
                return self::test_llm_connection($request);
            case 'chroma_generate_schema':
                return self::generate_schema($request, false);
            case 'chroma_generate_llm_targeting':
                return self::generate_llm_targeting($request);
            case 'chroma_generate_general_seo_meta':
                return self::generate_general_seo_meta($request);
            case 'chroma_translate_text':
                return self::translate($request);
            case 'chroma_fetch_available_models':
                return self::fetch_available_models($request);
            case 'chroma_scan_theme_strings':
                return self::scan_theme_strings($request);
            case 'chroma_save_string_translations':
                return self::save_string_translations($request);
            case 'chroma_bulk_translate_strings':
                return self::bulk_translate_strings($request);
            case 'chroma_export_po':
                return self::export_po($request);
            case 'chroma_debug_meta':
                return self::debug_meta($request);
            case 'chroma_save_sitemap_urls':
                return self::save_sitemap_urls($request);
            case 'chroma_parse_sitemap_urls':
                return self::parse_sitemap_urls($request);
            case 'chroma_validate_url':
                return self::validate_url($request);
            case 'chroma_run_link_analysis':
                return self::run_link_analysis($request);
            case 'chroma_link_equity_ai_preview':
                return self::run_link_analysis($request, true);
            case 'chroma_link_equity_ai_apply':
                return self::apply_link_equity($request);
            case 'chroma_combo_ai_generate':
                return self::combo_generate($request, false, false);
            case 'chroma_combo_ai_bulk_generate':
                return self::combo_generate($request, true, false);
            case 'chroma_combo_bulk_status':
                return self::combo_status($request);
            case 'chroma_combo_save_data':
                return self::combo_save_data($request);
            case 'chroma_combo_get_data':
                return self::combo_get_data($request);
            case 'chroma_combo_ai_translate':
                return self::combo_generate($request, false, true);
            case 'chroma_combo_ai_bulk_translate':
                return self::combo_generate($request, true, true);
            case 'chroma_bulk_reset_schema':
                return self::bulk_reset_meta($request, [
                    '_chroma_post_schemas',
                    '_chroma_schema_override',
                    '_chroma_schema_type',
                    '_chroma_schema_data',
                    '_chroma_schema_confidence',
                    '_chroma_needs_review',
                    '_chroma_review_reason',
                    '_chroma_schema_history',
                    '_chroma_schema_validation_status',
                    '_chroma_schema_errors',
                ], 'schema');
            case 'chroma_bulk_reset_faq':
                return self::bulk_reset_meta($request, ['chroma_faq_items', '_chroma_es_chroma_faq_items'], 'faq');
            case 'chroma_save_breadcrumb_settings':
                return self::save_breadcrumb_settings($request);
            case 'chroma_schema_cleanup_scan':
                return self::schema_cleanup_scan($request);
            case 'chroma_schema_cleanup_execute':
                return self::schema_cleanup_execute($request);
            case 'chroma_analyze_image':
                return self::analyze_image($request);
            case 'chroma_compare_competitor':
                return self::compare_competitor($request);
        }

        return new \WP_Error('caa_unhandled_seo_action', 'Unhandled SEO action.', ['status' => 500]);
    }

    public static function get_llm_settings(WP_REST_Request $request)
    {
        $groups = Field_Registry::seo_option_groups();
        $fields = (array) ($groups['llm']['fields'] ?? []);
        return rest_ensure_response([
            'success' => true,
            'fields' => $fields,
            'secret_fields' => array_values(array_intersect($fields, Field_Registry::secret_option_keys())),
            'data' => Route_Utils::read_options($fields),
        ]);
    }

    public static function set_llm_settings(WP_REST_Request $request)
    {
        $groups = Field_Registry::seo_option_groups();
        return Route_Utils::write_options(
            $request,
            Route_Utils::updates_from_request($request),
            (array) ($groups['llm']['fields'] ?? []),
            'write:seo',
            'seo_llm_settings'
        );
    }

    public static function list_translations(WP_REST_Request $request)
    {
        $page = max(1, (int) $request->get_param('page'));
        $per_page = (int) $request->get_param('per_page');
        if ($per_page <= 0) {
            $per_page = 20;
        }
        $per_page = min(100, $per_page);

        $post_type = $request->get_param('post_type') ?: get_post_types(['public' => true], 'names');
        if (is_string($post_type) && strpos($post_type, ',') !== false) {
            $post_type = array_filter(array_map('trim', explode(',', $post_type)));
        }

        $query = new WP_Query([
            'post_type' => $post_type,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => sanitize_text_field((string) $request->get_param('search')),
            'meta_query' => [
                'relation' => 'OR',
                ['key' => '_chroma_es_title', 'compare' => 'EXISTS'],
                ['key' => '_chroma_es_content', 'compare' => 'EXISTS'],
                ['key' => '_chroma_es_excerpt', 'compare' => 'EXISTS'],
            ],
            'no_found_rows' => false,
        ]);

        $items = [];
        foreach ((array) $query->posts as $post) {
            $items[] = [
                'post_id' => (int) $post->ID,
                'post_type' => (string) $post->post_type,
                'title' => get_the_title($post),
                'modified_gmt' => (string) $post->post_modified_gmt,
                'has_title_es' => get_post_meta((int) $post->ID, '_chroma_es_title', true) !== '',
                'has_content_es' => get_post_meta((int) $post->ID, '_chroma_es_content', true) !== '',
                'has_excerpt_es' => get_post_meta((int) $post->ID, '_chroma_es_excerpt', true) !== '',
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => (int) $query->found_posts,
                'total_pages' => (int) $query->max_num_pages,
            ],
        ]);
    }

    public static function get_translation(WP_REST_Request $request)
    {
        $post_id = (int) $request['id'];
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'data' => Route_Utils::read_post_meta_values($post_id, self::translation_fields()),
        ]);
    }

    public static function set_translation(WP_REST_Request $request)
    {
        return Route_Utils::write_post_meta(
            $request,
            (int) $request['id'],
            Route_Utils::updates_from_request($request),
            self::translation_fields(),
            [],
            'write:seo',
            'translation'
        );
    }

    public static function delete_translation(WP_REST_Request $request)
    {
        $updates = [];
        foreach (self::translation_fields() as $field) {
            $updates[$field] = null;
        }

        return Route_Utils::write_post_meta(
            $request,
            (int) $request['id'],
            $updates,
            self::translation_fields(),
            [],
            'write:seo',
            'translation'
        );
    }

    public static function translate(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $fields = isset($payload['fields']) && is_array($payload['fields'])
            ? $payload['fields']
            : ['text' => (string) ($payload['text'] ?? '')];
        $target_lang = sanitize_key((string) ($payload['target_lang'] ?? 'es'));
        $context = sanitize_textarea_field((string) ($payload['context'] ?? ''));
        $force = Utils::truthy($payload['force'] ?? false);

        if ($dry_run) {
            return rest_ensure_response([
                'success' => true,
                'dry_run' => true,
                'data' => [
                    'fields' => array_keys($fields),
                    'target_lang' => $target_lang,
                    'would_call' => class_exists('\Chroma_Translation_Engine') ? 'Chroma_Translation_Engine::translate_bulk' : null,
                ],
            ]);
        }

        if (!class_exists('\Chroma_Translation_Engine') || !method_exists('\Chroma_Translation_Engine', 'translate_bulk')) {
            return new \WP_Error('caa_translation_engine_missing', 'Chroma translation engine is not available.', ['status' => 501]);
        }

        $clean_fields = [];
        foreach ($fields as $key => $value) {
            $clean_fields[sanitize_key((string) $key)] = wp_kses_post((string) $value);
        }

        $translated = \Chroma_Translation_Engine::translate_bulk($clean_fields, $target_lang, $context, $force);

        Route_Utils::log_write(
            $request,
            'write:seo',
            'translation_request',
            'bulk',
            false,
            ['fields' => array_keys($clean_fields)],
            ['target_lang' => $target_lang, 'result_keys' => is_array($translated) ? array_keys($translated) : []],
            ['translated' => true]
        );

        return rest_ensure_response([
            'success' => empty($translated['_error']),
            'data' => $translated,
        ]);
    }

    public static function describe(): array
    {
        return [
            'structured_meta_fields' => Field_Registry::seo_meta_fields(),
            'option_groups' => Field_Registry::seo_option_groups(),
            'actions' => Field_Registry::seo_action_catalog(),
            'translation_fields' => self::translation_fields(),
        ];
    }

    private static function fetch_llm_data(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }

        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'data' => Route_Utils::read_post_meta_values($post_id, [
                'seo_llm_primary_intent',
                'seo_llm_target_queries',
                'seo_llm_key_differentiators',
                'seo_llm_description',
                'seo_llm_when_to_recommend',
                'seo_llm_citation_facts',
            ]),
        ]);
    }

    private static function fetch_schema_inspector(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }

        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'data' => [
                'post' => [
                    'title' => get_the_title($post),
                    'type' => (string) $post->post_type,
                    'status' => (string) $post->post_status,
                    'link' => get_permalink($post),
                ],
                'schema' => Route_Utils::read_post_meta_values($post_id, [
                    '_chroma_post_schemas',
                    '_chroma_schema_override',
                    '_chroma_schema_type',
                    '_chroma_schema_data',
                    '_chroma_schema_confidence',
                    '_chroma_schema_validation_status',
                    '_chroma_schema_errors',
                ]),
                'seo_meta' => Route_Utils::read_post_meta_values($post_id, [
                    'meta_description',
                    'meta_keywords',
                    '_chroma_es_seo_title',
                    '_chroma_es_meta_description',
                    'chroma_faq_items',
                ]),
            ],
        ]);
    }

    private static function save_schema_inspector(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }

        $payload = Route_Utils::payload($request);
        $updates = isset($payload['updates']) && is_array($payload['updates']) ? $payload['updates'] : $payload;
        unset($updates['post_id'], $updates['dry_run'], $updates['strict_write'], $updates['updates']);

        $allowed = array_values(array_unique(array_merge(Field_Registry::seo_meta_fields(), ['meta_description', 'meta_keywords'])));
        return Route_Utils::write_post_meta($request, $post_id, $updates, $allowed, [], 'write:seo', 'schema_inspector');
    }

    private static function scan_schema_batch(WP_REST_Request $request)
    {
        $page = max(1, (int) ($request->get_param('page') ?: 1));
        $per_page = min(100, max(1, (int) ($request->get_param('per_page') ?: 20)));
        $include_data = Utils::truthy($request->get_param('include_data'));
        $query = new WP_Query([
            'post_type' => get_post_types(['public' => true], 'names'),
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => [
                'relation' => 'OR',
                ['key' => '_chroma_post_schemas', 'compare' => 'EXISTS'],
                ['key' => '_chroma_schema_override', 'compare' => 'EXISTS'],
                ['key' => '_chroma_schema_data', 'compare' => 'EXISTS'],
            ],
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => false,
        ]);

        $items = [];
        foreach ((array) $query->posts as $post) {
            $item = [
                'post_id' => (int) $post->ID,
                'title' => get_the_title($post),
                'post_type' => (string) $post->post_type,
                'validation_status' => get_post_meta((int) $post->ID, '_chroma_schema_validation_status', true),
                'needs_review' => Utils::truthy(get_post_meta((int) $post->ID, '_chroma_needs_review', true)),
            ];
            if ($include_data) {
                $item['schema'] = Route_Utils::read_post_meta_values((int) $post->ID, [
                    '_chroma_post_schemas',
                    '_chroma_schema_override',
                    '_chroma_schema_data',
                ]);
            }
            $items[] = $item;
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => (int) $query->found_posts,
                'total_pages' => (int) $query->max_num_pages,
            ],
        ]);
    }

    private static function get_schema_fields(WP_REST_Request $request)
    {
        return rest_ensure_response([
            'success' => true,
            'data' => [
                'structured_meta_fields' => Field_Registry::seo_meta_fields(),
                'translation_fields' => self::translation_fields(),
                'theme_page_groups' => Field_Registry::theme_page_meta_groups(),
                'theme_cpt_groups' => Field_Registry::theme_cpt_meta_groups(),
            ],
        ]);
    }

    private static function fetch_social_preview(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $description = get_post_meta($post_id, 'meta_description', true);
        if ($description === '') {
            $description = has_excerpt($post) ? $post->post_excerpt : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 30);
        }

        $title = get_post_meta($post_id, 'program_meta_title', true);
        if ($title === '') {
            $title = get_the_title($post);
        }

        $image = get_option('chroma_default_og_image', '');
        if (has_post_thumbnail($post)) {
            $thumb = get_the_post_thumbnail_url($post, 'large');
            if ($thumb) {
                $image = $thumb;
            }
        }

        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'data' => [
                'title' => $title,
                'description' => $description,
                'image' => $image,
                'url' => get_permalink($post),
                'twitter_site' => get_option('chroma_twitter_site', ''),
                'twitter_card_type' => get_option('chroma_twitter_card_type', ''),
            ],
        ]);
    }

    private static function save_llm_targeting(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }

        $payload = Route_Utils::payload($request);
        $updates = isset($payload['updates']) && is_array($payload['updates']) ? $payload['updates'] : $payload;
        unset($updates['post_id'], $updates['dry_run'], $updates['strict_write'], $updates['updates']);

        return Route_Utils::write_post_meta(
            $request,
            $post_id,
            $updates,
            [
                'seo_llm_primary_intent',
                'seo_llm_target_queries',
                'seo_llm_key_differentiators',
                'seo_llm_description',
                'seo_llm_when_to_recommend',
                'seo_llm_citation_facts',
            ],
            [],
            'write:seo',
            'seo_llm_targeting'
        );
    }

    private static function review_schema(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }

        $payload = Route_Utils::payload($request);
        $needs_review = array_key_exists('needs_review', $payload) ? Utils::truthy($payload['needs_review']) : true;
        $reason = sanitize_textarea_field((string) ($payload['reason'] ?? $payload['review_reason'] ?? 'Agent review requested.'));

        return Route_Utils::write_post_meta(
            $request,
            $post_id,
            [
                '_chroma_needs_review' => $needs_review,
                '_chroma_review_reason' => $reason,
            ],
            ['_chroma_needs_review', '_chroma_review_reason'],
            [],
            'write:seo',
            'schema_review'
        );
    }

    private static function get_review_queue(WP_REST_Request $request)
    {
        $per_page = min(100, max(1, (int) ($request->get_param('per_page') ?: 25)));
        $query = new WP_Query([
            'post_type' => get_post_types(['public' => true], 'names'),
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => $per_page,
            'meta_query' => [
                ['key' => '_chroma_needs_review', 'value' => ['1', 1, true], 'compare' => 'IN'],
            ],
            'no_found_rows' => true,
        ]);

        $items = [];
        foreach ((array) $query->posts as $post) {
            $items[] = [
                'post_id' => (int) $post->ID,
                'post_type' => (string) $post->post_type,
                'title' => get_the_title($post),
                'review_reason' => get_post_meta((int) $post->ID, '_chroma_review_reason', true),
            ];
        }

        return rest_ensure_response(['success' => true, 'data' => $items]);
    }

    private static function reset_post_schema(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }

        $updates = [];
        foreach ([
            '_chroma_post_schemas',
            '_chroma_schema_override',
            '_chroma_schema_type',
            '_chroma_schema_data',
            '_chroma_schema_confidence',
            '_chroma_needs_review',
            '_chroma_review_reason',
            '_chroma_schema_history',
            '_chroma_schema_validation_status',
            '_chroma_schema_errors',
        ] as $key) {
            $updates[$key] = null;
        }

        return Route_Utils::write_post_meta($request, $post_id, $updates, array_keys($updates), [], 'write:seo', 'schema_reset');
    }

    private static function apply_schema_fix(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }
        $payload = Route_Utils::payload($request);
        $updates = [];
        foreach (['_chroma_schema_override', '_chroma_schema_data', '_chroma_schema_type', '_chroma_post_schemas'] as $key) {
            if (array_key_exists($key, $payload)) {
                $updates[$key] = $payload[$key];
            }
        }
        if (isset($payload['schema']) && !isset($updates['_chroma_schema_override'])) {
            $updates['_chroma_schema_override'] = is_string($payload['schema']) ? $payload['schema'] : wp_json_encode($payload['schema']);
        }
        return Route_Utils::write_post_meta($request, $post_id, $updates, ['_chroma_schema_override', '_chroma_schema_data', '_chroma_schema_type', '_chroma_post_schemas'], [], 'write:seo', 'schema_fix');
    }

    private static function validate_post_schema(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }

        $schemas = get_post_meta($post_id, '_chroma_post_schemas', true);
        $override = get_post_meta($post_id, '_chroma_schema_override', true);
        $errors = [];
        $schema_count = is_array($schemas) ? count($schemas) : 0;

        if ($schema_count === 0 && empty($override)) {
            $errors[] = 'No stored schema data or override found.';
        }

        if (is_string($override) && $override !== '') {
            json_decode($override, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'Schema override is not valid JSON.';
            }
        }

        $is_valid = empty($errors);
        $payload = Route_Utils::payload($request);
        if (!Route_Utils::dry_run($payload)) {
            update_post_meta($post_id, '_chroma_schema_validation_status', $is_valid ? 'valid' : 'invalid');
            update_post_meta($post_id, '_chroma_last_validated', time());
            if ($is_valid) {
                delete_post_meta($post_id, '_chroma_schema_errors');
            } else {
                update_post_meta($post_id, '_chroma_schema_errors', $errors);
            }
        }

        Route_Utils::log_write(
            $request,
            'write:seo',
            'schema_validation',
            (string) $post_id,
            Route_Utils::dry_run($payload),
            null,
            ['valid' => $is_valid, 'errors' => $errors],
            ['validated' => true]
        );

        return rest_ensure_response([
            'success' => true,
            'dry_run' => Route_Utils::dry_run($payload),
            'post_id' => $post_id,
            'valid' => $is_valid,
            'schema_count' => $schema_count,
            'errors' => $errors,
        ]);
    }

    private static function fetch_live_schema(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }

        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'data' => Route_Utils::read_post_meta_values($post_id, [
                '_chroma_post_schemas',
                '_chroma_schema_override',
                '_chroma_schema_type',
                '_chroma_schema_data',
                '_chroma_schema_validation_status',
                '_chroma_schema_errors',
            ]),
        ]);
    }

    private static function sync_schema_to_builder(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }
        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $override = get_post_meta($post_id, '_chroma_schema_override', true);
        $current = get_post_meta($post_id, '_chroma_post_schemas', true);
        $candidate = $current;
        if ($override !== '') {
            $decoded = json_decode((string) $override, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded) {
                $candidate = [$decoded];
            }
        }
        if (!$dry_run) {
            update_post_meta($post_id, '_chroma_post_schemas', $candidate);
        }
        Route_Utils::log_write($request, 'write:seo', 'schema_sync', (string) $post_id, $dry_run, ['schemas' => $current], ['schemas' => $candidate], Diff::compare(['schemas' => $current], ['schemas' => $candidate]));
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'post_id' => $post_id, 'data' => ['schemas' => $candidate]]);
    }

    private static function test_llm_connection(WP_REST_Request $request)
    {
        $options = Route_Utils::read_options((array) (Field_Registry::seo_option_groups()['llm']['fields'] ?? []), false);
        $has_key = !empty($options['chroma_openai_api_key']) || !empty($options['chroma_google_places_api_key']);
        $model = (string) ($options['chroma_llm_model'] ?? '');
        $base = (string) ($options['chroma_llm_base_url'] ?? '');
        return rest_ensure_response([
            'success' => true,
            'data' => [
                'configured' => $has_key,
                'model' => $model,
                'base_url' => $base,
                'status' => $has_key ? 'ready' : 'missing_api_key',
            ],
        ]);
    }

    private static function generate_schema(WP_REST_Request $request, bool $fix_mode)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => self::schema_type_for_post($post),
            'name' => get_the_title($post),
            'url' => get_permalink($post),
            'description' => get_post_meta($post_id, 'meta_description', true) ?: wp_trim_words(wp_strip_all_tags((string) $post->post_content), 30),
        ];
        if (has_post_thumbnail($post)) {
            $image = get_the_post_thumbnail_url($post, 'large');
            if ($image) {
                $schema['image'] = $image;
            }
        }

        $payload = Route_Utils::payload($request);
        $apply = Utils::truthy($payload['apply'] ?? (!$fix_mode ? false : true));
        $dry_run = Route_Utils::dry_run($payload);
        if ($apply && !$dry_run) {
            update_post_meta($post_id, '_chroma_post_schemas', [$schema]);
            update_post_meta($post_id, '_chroma_schema_data', $schema);
            update_post_meta($post_id, '_chroma_schema_type', $schema['@type']);
        }

        Route_Utils::log_write($request, 'write:seo', 'generated_schema', (string) $post_id, $dry_run || !$apply, null, $schema, ['generated' => true]);
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run || !$apply, 'post_id' => $post_id, 'data' => $schema, 'applied' => $apply && !$dry_run]);
    }

    private static function generate_llm_targeting(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $title = get_the_title($post);
        $tokens = preg_split('/\s+/', strtolower($title)) ?: [];
        $keywords = array_values(array_unique(array_filter($tokens, static function ($token) {
            return strlen($token) > 3;
        })));
        $suggestion = [
            'seo_llm_primary_intent' => sanitize_text_field($post->post_type . ': ' . $title),
            'seo_llm_target_queries' => array_slice($keywords, 0, 8),
            'seo_llm_key_differentiators' => [sanitize_text_field($title . ' at Chroma')],
        ];

        $payload = Route_Utils::payload($request);
        $apply = Utils::truthy($payload['apply'] ?? false);
        $dry_run = Route_Utils::dry_run($payload);
        if ($apply && !$dry_run) {
            foreach ($suggestion as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        }

        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run || !$apply, 'post_id' => $post_id, 'data' => $suggestion, 'applied' => $apply && !$dry_run]);
    }

    private static function generate_general_seo_meta(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $description = has_excerpt($post) ? $post->post_excerpt : wp_trim_words(wp_strip_all_tags((string) $post->post_content), 26);
        $keywords = implode(', ', array_slice(array_values(array_unique(array_filter(preg_split('/\W+/', strtolower(get_the_title($post))) ?: [], static function ($token) {
            return strlen($token) > 3;
        }))), 0, 8));
        $suggestion = [
            'meta_description' => sanitize_textarea_field($description),
            'meta_keywords' => sanitize_text_field($keywords),
        ];

        $payload = Route_Utils::payload($request);
        $apply = Utils::truthy($payload['apply'] ?? false);
        $dry_run = Route_Utils::dry_run($payload);
        if ($apply && !$dry_run) {
            foreach ($suggestion as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        }
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run || !$apply, 'post_id' => $post_id, 'data' => $suggestion, 'applied' => $apply && !$dry_run]);
    }

    private static function fetch_available_models(WP_REST_Request $request)
    {
        $stored = get_option('chroma_llm_available_models', []);
        if (!is_array($stored) || empty($stored)) {
            $stored = ['gpt-4.1', 'gpt-4o', 'gpt-4.1-mini'];
        }
        return rest_ensure_response(['success' => true, 'data' => array_values($stored)]);
    }

    private static function scan_theme_strings(WP_REST_Request $request)
    {
        $files = glob(get_stylesheet_directory() . '/**/*.php');
        if ($files === false || empty($files)) {
            $files = glob(get_stylesheet_directory() . '/*.php');
        }
        $patterns = [
            '/__\(\s*[\'"]([^\'"]+)[\'"]/',
            '/_e\(\s*[\'"]([^\'"]+)[\'"]/',
            '/esc_html__\(\s*[\'"]([^\'"]+)[\'"]/',
            '/esc_attr__\(\s*[\'"]([^\'"]+)[\'"]/',
        ];
        $strings = [];
        foreach ((array) $files as $file) {
            $content = @file_get_contents($file);
            if (!is_string($content)) {
                continue;
            }
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ((array) ($matches[1] ?? []) as $match) {
                        $strings[] = $match;
                    }
                }
            }
        }
        $strings = array_values(array_unique(array_filter($strings)));
        sort($strings);
        return rest_ensure_response(['success' => true, 'data' => $strings]);
    }

    private static function save_string_translations(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $translations = isset($payload['translations']) && is_array($payload['translations']) ? $payload['translations'] : [];
        return Route_Utils::write_options($request, ['chroma_theme_string_translations' => $translations], ['chroma_theme_string_translations'], 'write:seo', 'theme_string_translations', false);
    }

    private static function bulk_translate_strings(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $strings = isset($payload['strings']) && is_array($payload['strings']) ? $payload['strings'] : [];
        if (empty($strings)) {
            return new \WP_Error('caa_strings_required', 'strings array is required.', ['status' => 400]);
        }
        $fields = [];
        foreach ($strings as $index => $value) {
            $fields['string_' . $index] = (string) $value;
        }
        $translate_request = new \WP_REST_Request('POST', $request->get_route());
        $translate_request->set_body_params([
            'fields' => $fields,
            'target_lang' => (string) ($payload['target_lang'] ?? 'es'),
            'context' => (string) ($payload['context'] ?? 'Theme string translation'),
            'dry_run' => $payload['dry_run'] ?? false,
        ]);
        return self::translate($translate_request);
    }

    private static function export_po(WP_REST_Request $request)
    {
        $translations = get_option('chroma_theme_string_translations', []);
        if (!is_array($translations)) {
            $translations = [];
        }
        $lines = [
            'msgid ""',
            'msgstr ""',
            '"Content-Type: text/plain; charset=UTF-8\n"',
            '',
        ];
        foreach ($translations as $source => $target) {
            $lines[] = 'msgid "' . addslashes((string) $source) . '"';
            $lines[] = 'msgstr "' . addslashes((string) $target) . '"';
            $lines[] = '';
        }
        return rest_ensure_response(['success' => true, 'data' => ['content' => implode("\n", $lines)]]);
    }

    private static function debug_meta(WP_REST_Request $request)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }
        $meta = get_post_meta($post_id);
        $normalized = [];
        foreach ((array) $meta as $key => $values) {
            $normalized[$key] = count((array) $values) === 1 ? maybe_unserialize($values[0]) : array_map('maybe_unserialize', (array) $values);
        }
        return rest_ensure_response(['success' => true, 'post_id' => $post_id, 'data' => $normalized]);
    }

    private static function clear_validation_cache(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $old = (int) get_option('chroma_validation_cache_ver', 0);
        $new = $old + 1;

        if (!Route_Utils::dry_run($payload)) {
            update_option('chroma_validation_cache_ver', $new, false);
        }

        $diff = Diff::compare(['chroma_validation_cache_ver' => $old], ['chroma_validation_cache_ver' => $new]);
        Route_Utils::log_write($request, 'write:seo', 'validation_cache', 'global', Route_Utils::dry_run($payload), ['version' => $old], ['version' => $new], $diff);

        return rest_ensure_response(['success' => true, 'dry_run' => Route_Utils::dry_run($payload), 'data' => ['old_version' => $old, 'new_version' => $new]]);
    }

    private static function save_validator_setting(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $updates = isset($payload['updates']) && is_array($payload['updates'])
            ? $payload['updates']
            : [(string) ($payload['setting'] ?? '') => $payload['value'] ?? null];
        $groups = Field_Registry::seo_option_groups();

        return Route_Utils::write_options(
            $request,
            $updates,
            (array) ($groups['validator']['fields'] ?? []),
            'write:seo',
            'seo_validator_setting'
        );
    }

    private static function save_sitemap_urls(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $updates = [
            'chroma_validator_sitemaps' => $payload['urls'] ?? ($payload['sitemaps'] ?? []),
        ];
        if (array_key_exists('exclusions', $payload)) {
            $updates['chroma_validator_exclusions'] = $payload['exclusions'];
        }
        if (array_key_exists('options', $payload)) {
            $updates['chroma_sitemap_options'] = $payload['options'];
        }

        return Route_Utils::write_options($request, $updates, ['chroma_validator_sitemaps', 'chroma_validator_exclusions', 'chroma_sitemap_options'], 'write:seo', 'seo_sitemaps');
    }

    private static function parse_sitemap_urls(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $raw = (string) ($payload['raw'] ?? $payload['urls'] ?? '');
        $parts = preg_split('/[\r\n,]+/', $raw) ?: [];
        $urls = [];
        foreach ($parts as $part) {
            $url = esc_url_raw(trim((string) $part));
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        return rest_ensure_response(['success' => true, 'data' => array_values(array_unique($urls))]);
    }

    private static function validate_url(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $url = esc_url_raw((string) ($payload['url'] ?? ''));
        if ($url === '') {
            return new \WP_Error('caa_url_required', 'url is required.', ['status' => 400]);
        }
        $valid = (bool) filter_var($url, FILTER_VALIDATE_URL);
        return rest_ensure_response(['success' => true, 'data' => ['url' => $url, 'valid' => $valid]]);
    }

    private static function run_link_analysis(WP_REST_Request $request, bool $preview_only = false)
    {
        $post_id = self::post_id_from_payload($request);
        if ($post_id <= 0) {
            return new \WP_Error('caa_post_id_required', 'post_id is required.', ['status' => 400]);
        }
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }
        preg_match_all('/href=["\']([^"\']+)["\']/', (string) $post->post_content, $matches);
        $links = array_values(array_unique((array) ($matches[1] ?? [])));
        $internal = array_values(array_filter($links, static function ($link) {
            return strpos((string) $link, home_url()) === 0 || strpos((string) $link, '/') === 0;
        }));
        $report = [
            'total_links' => count($links),
            'internal_links' => count($internal),
            'sample_links' => array_slice($links, 0, 10),
            'recommended_anchor_targets' => self::combo_candidates(),
        ];
        if (!$preview_only && !Route_Utils::dry_run(Route_Utils::payload($request))) {
            update_option('chroma_seo_link_report', $report, false);
        }
        return rest_ensure_response(['success' => true, 'post_id' => $post_id, 'preview' => $preview_only, 'data' => $report]);
    }

    private static function apply_link_equity(WP_REST_Request $request)
    {
        $report = self::run_link_analysis($request, false);
        if (is_wp_error($report)) {
            return $report;
        }
        return rest_ensure_response(['success' => true, 'data' => ['applied' => true, 'report' => $report->get_data()['data']]]);
    }

    private static function combo_status(WP_REST_Request $request)
    {
        return rest_ensure_response(['success' => true, 'data' => self::combo_candidates()]);
    }

    private static function combo_get_data(WP_REST_Request $request)
    {
        return rest_ensure_response([
            'success' => true,
            'data' => [
                'manual_cities' => get_option('chroma_seo_manual_cities', []),
                'manual_cities_raw' => get_option('chroma_seo_manual_cities_raw', ''),
                'auto_publish' => get_option('chroma_combo_auto_publish', false),
                'candidates' => self::combo_candidates(),
            ],
        ]);
    }

    private static function combo_save_data(WP_REST_Request $request)
    {
        $allowed = ['chroma_combo_auto_publish', 'chroma_seo_manual_cities', 'chroma_seo_manual_cities_raw'];
        return Route_Utils::write_options($request, Route_Utils::updates_from_request($request), $allowed, 'write:seo', 'combo_data', false);
    }

    private static function combo_generate(WP_REST_Request $request, bool $bulk, bool $translate)
    {
        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $candidates = self::combo_candidates();
        $generated = [];
        foreach (array_slice((array) $candidates['combinations'], 0, $bulk ? 50 : 5) as $combo) {
            $generated[] = [
                'slug' => sanitize_title(implode('-', $combo)),
                'title' => $translate ? 'ES ' . implode(' - ', $combo) : implode(' - ', $combo),
                'components' => $combo,
            ];
        }
        if (!$dry_run) {
            update_option('chroma_combo_generation_preview', $generated, false);
        }
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'data' => $generated]);
    }

    private static function analyze_image(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $attachment_id = (int) ($payload['attachment_id'] ?? 0);
        if ($attachment_id <= 0) {
            return new \WP_Error('caa_attachment_id_required', 'attachment_id is required.', ['status' => 400]);
        }
        $post = get_post($attachment_id);
        if (!$post || $post->post_type !== 'attachment') {
            return new \WP_Error('caa_attachment_not_found', 'Attachment not found.', ['status' => 404]);
        }
        $meta = wp_get_attachment_metadata($attachment_id);
        return rest_ensure_response([
            'success' => true,
            'data' => [
                'id' => $attachment_id,
                'mime_type' => get_post_mime_type($attachment_id),
                'url' => wp_get_attachment_url($attachment_id),
                'metadata' => $meta,
                'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            ],
        ]);
    }

    private static function compare_competitor(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $competitor = esc_url_raw((string) ($payload['competitor_url'] ?? $payload['url'] ?? ''));
        $our = home_url('/');
        if ($competitor === '') {
            return new \WP_Error('caa_competitor_url_required', 'competitor_url is required.', ['status' => 400]);
        }
        return rest_ensure_response([
            'success' => true,
            'data' => [
                'our_domain' => wp_parse_url($our, PHP_URL_HOST),
                'competitor_domain' => wp_parse_url($competitor, PHP_URL_HOST),
                'same_tld' => substr((string) wp_parse_url($our, PHP_URL_HOST), -4) === substr((string) wp_parse_url($competitor, PHP_URL_HOST), -4),
            ],
        ]);
    }

    private static function bulk_reset_meta(WP_REST_Request $request, array $keys, string $label)
    {
        $payload = Route_Utils::payload($request);
        $post_ids = isset($payload['post_ids']) && is_array($payload['post_ids']) ? array_map('intval', $payload['post_ids']) : [];
        if (empty($post_ids)) {
            return new \WP_Error('caa_post_ids_required', 'post_ids array is required.', ['status' => 400]);
        }

        $results = [];
        foreach ($post_ids as $post_id) {
            if ($post_id <= 0 || !get_post($post_id)) {
                $results[$post_id] = ['success' => false, 'error' => 'post_not_found'];
                continue;
            }

            if (!Route_Utils::dry_run($payload)) {
                foreach ($keys as $key) {
                    delete_post_meta($post_id, $key);
                }
            }
            $results[$post_id] = ['success' => true, 'deleted_keys' => $keys];
        }

        Route_Utils::log_write($request, 'write:seo', 'bulk_' . $label . '_reset', 'batch', Route_Utils::dry_run($payload), null, $results, ['reset' => true]);
        return rest_ensure_response(['success' => true, 'dry_run' => Route_Utils::dry_run($payload), 'data' => $results]);
    }

    private static function save_breadcrumb_settings(WP_REST_Request $request)
    {
        $groups = Field_Registry::seo_option_groups();
        return Route_Utils::write_options(
            $request,
            Route_Utils::updates_from_request($request),
            (array) ($groups['breadcrumbs']['fields'] ?? []),
            'write:seo',
            'seo_breadcrumb_settings'
        );
    }

    private static function schema_cleanup_scan(WP_REST_Request $request)
    {
        $limit = min(200, max(1, (int) ($request->get_param('limit') ?: 100)));
        $query = new WP_Query([
            'post_type' => get_post_types(['public' => true], 'names'),
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => $limit,
            'meta_query' => [
                ['key' => '_chroma_post_schemas', 'compare' => 'EXISTS'],
            ],
            'no_found_rows' => true,
        ]);

        $items = [];
        foreach ((array) $query->posts as $post) {
            $schemas = get_post_meta((int) $post->ID, '_chroma_post_schemas', true);
            if (!is_array($schemas)) {
                continue;
            }
            $encoded = array_map('wp_json_encode', $schemas);
            $duplicates = count($encoded) - count(array_unique($encoded));
            if ($duplicates > 0) {
                $items[] = [
                    'post_id' => (int) $post->ID,
                    'title' => get_the_title($post),
                    'schema_count' => count($schemas),
                    'duplicate_count' => $duplicates,
                ];
            }
        }

        return rest_ensure_response(['success' => true, 'data' => $items]);
    }

    private static function schema_cleanup_execute(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $post_ids = isset($payload['post_ids']) && is_array($payload['post_ids']) ? array_map('intval', $payload['post_ids']) : [];
        if (empty($post_ids)) {
            return new \WP_Error('caa_post_ids_required', 'post_ids array is required.', ['status' => 400]);
        }

        $results = [];
        foreach ($post_ids as $post_id) {
            $schemas = get_post_meta($post_id, '_chroma_post_schemas', true);
            if (!is_array($schemas)) {
                $results[$post_id] = ['success' => false, 'error' => 'schemas_not_array'];
                continue;
            }

            $seen = [];
            $clean = [];
            foreach ($schemas as $schema) {
                $fingerprint = wp_json_encode($schema);
                if (isset($seen[$fingerprint])) {
                    continue;
                }
                $seen[$fingerprint] = true;
                $clean[] = $schema;
            }

            if (!Route_Utils::dry_run($payload)) {
                update_post_meta($post_id, '_chroma_post_schemas', $clean);
            }

            $results[$post_id] = [
                'success' => true,
                'before_count' => count($schemas),
                'after_count' => count($clean),
            ];
        }

        Route_Utils::log_write($request, 'write:seo', 'schema_cleanup', 'batch', Route_Utils::dry_run($payload), null, $results, ['cleanup' => true]);
        return rest_ensure_response(['success' => true, 'dry_run' => Route_Utils::dry_run($payload), 'data' => $results]);
    }

    private static function filter_fields_from_query(array $fields, WP_REST_Request $request): array
    {
        $keys = (string) $request->get_param('keys');
        if ($keys === '') {
            return $fields;
        }

        $requested = array_filter(array_map('trim', explode(',', $keys)));
        return array_values(array_intersect($fields, $requested));
    }

    private static function post_id_from_payload(WP_REST_Request $request): int
    {
        $payload = Route_Utils::payload($request);
        return (int) ($payload['post_id'] ?? $request->get_param('post_id'));
    }

    private static function translation_fields(): array
    {
        return [
            '_chroma_es_title',
            '_chroma_es_content',
            '_chroma_es_excerpt',
            '_chroma_es_seo_title',
            '_chroma_es_meta_description',
            '_chroma_es_history',
            '_chroma_es_chroma_faq_items',
        ];
    }

    private static function schema_type_for_post($post): string
    {
        if ($post->post_type === 'location') {
            return 'LocalBusiness';
        }
        if ($post->post_type === 'program') {
            return 'EducationalOccupationalProgram';
        }
        if ($post->post_type === 'post') {
            return 'Article';
        }
        return 'WebPage';
    }

    private static function combo_candidates(): array
    {
        $locations = get_posts([
            'post_type' => 'location',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'fields' => 'ids',
        ]);
        $programs = get_posts([
            'post_type' => 'program',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'fields' => 'ids',
        ]);
        $cities = get_option('chroma_seo_manual_cities', []);
        if (!is_array($cities)) {
            $cities = [];
        }

        $location_titles = array_map('get_the_title', array_slice((array) $locations, 0, 5));
        $program_titles = array_map('get_the_title', array_slice((array) $programs, 0, 5));
        $cities = array_slice(array_values(array_filter(array_map('strval', $cities))), 0, 5);
        $combinations = [];
        foreach ($program_titles as $program) {
            foreach ($cities as $city) {
                $combinations[] = [$program, $city];
            }
        }

        return [
            'locations' => $location_titles,
            'programs' => $program_titles,
            'cities' => $cities,
            'combinations' => $combinations,
        ];
    }
}

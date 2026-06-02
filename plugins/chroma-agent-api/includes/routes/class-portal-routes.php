<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Diff;
use ChromaAgentAPI\Field_Registry;
use ChromaAgentAPI\Route_Utils;
use ChromaAgentAPI\Snapshot_Store;
use ChromaAgentAPI\Utils;
use WP_Query;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class Portal_Routes
{
    private const NS = 'chroma-agent/v1';

    public static function register(): void
    {
        register_rest_route(self::NS, '/portal/content', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'list_content'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [__CLASS__, 'create_content'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/portal/content/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_content'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'update_content'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [__CLASS__, 'delete_content'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/portal/taxonomies', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'list_taxonomies'],
            'permission_callback' => [__CLASS__, 'read_permission'],
        ]);

        register_rest_route(self::NS, '/portal/dashboard', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'dashboard'],
            'permission_callback' => [__CLASS__, 'read_permission'],
        ]);

        register_rest_route(self::NS, '/portal/years', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'years'],
            'permission_callback' => [__CLASS__, 'read_permission'],
        ]);

        register_rest_route(self::NS, '/portal/taxonomies/(?P<taxonomy>[a-z0-9_-]+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'list_terms'],
            'permission_callback' => [__CLASS__, 'read_permission'],
        ]);

        register_rest_route(self::NS, '/portal/bulk-import', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'bulk_import_capabilities'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [__CLASS__, 'bulk_import'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/portal/families/(?P<id>\d+)/pin', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'reset_family_pin'],
            'permission_callback' => [__CLASS__, 'write_permission'],
        ]);
    }

    public static function read_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['read:portal']);
    }

    public static function write_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['write:portal']);
    }

    public static function list_content(WP_REST_Request $request)
    {
        $page = max(1, (int) $request->get_param('page'));
        $per_page = min(100, max(1, (int) ($request->get_param('per_page') ?: 20)));
        $post_type = $request->get_param('post_type');
        if (empty($post_type)) {
            $post_type = Field_Registry::portal_post_types();
        } elseif (is_string($post_type) && strpos($post_type, ',') !== false) {
            $post_type = array_filter(array_map('trim', explode(',', $post_type)));
        }

        $query = new WP_Query([
            'post_type' => $post_type,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => sanitize_text_field((string) $request->get_param('search')),
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => false,
        ]);

        $items = [];
        foreach ((array) $query->posts as $post) {
            $items[] = self::prepare_portal_post($post, false);
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

    public static function get_content(WP_REST_Request $request)
    {
        $post = get_post((int) $request['id']);
        if (!$post || !in_array($post->post_type, Field_Registry::portal_post_types(), true)) {
            return new \WP_Error('caa_portal_content_not_found', 'Portal content not found.', ['status' => 404]);
        }

        return rest_ensure_response(['success' => true, 'data' => self::prepare_portal_post($post, true)]);
    }

    public static function create_content(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $post_type = sanitize_key((string) ($payload['post_type'] ?? 'cp_resource'));

        if (!in_array($post_type, Field_Registry::portal_post_types(), true)) {
            return new \WP_Error('caa_invalid_portal_post_type', 'Invalid portal post type.', ['status' => 400]);
        }

        $postarr = [
            'post_title' => sanitize_text_field((string) ($payload['title'] ?? $payload['post_title'] ?? '')),
            'post_content' => wp_kses_post((string) ($payload['content'] ?? $payload['post_content'] ?? '')),
            'post_excerpt' => sanitize_textarea_field((string) ($payload['excerpt'] ?? $payload['post_excerpt'] ?? '')),
            'post_status' => sanitize_key((string) ($payload['status'] ?? $payload['post_status'] ?? 'publish')),
            'post_type' => $post_type,
        ];

        if ($postarr['post_title'] === '') {
            return new \WP_Error('caa_title_required', 'title is required.', ['status' => 400]);
        }

        [$meta, $blocked_meta] = self::meta_from_payload($payload);
        $tax = self::tax_from_payload($payload, $post_type);
        $after = ['post' => $postarr, 'meta' => $meta, 'taxonomies' => $tax];
        $diff = Diff::compare(null, $after);

        if ($dry_run) {
            Route_Utils::log_write($request, 'write:portal', 'portal_content', 'new', true, null, $after, $diff);
            return rest_ensure_response(['success' => true, 'dry_run' => true, 'blocked_keys' => $blocked_meta, 'snapshot_ids' => [], 'diff' => $diff, 'data' => $after]);
        }

        $post_id = wp_insert_post($postarr, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }

        self::apply_portal_meta((int) $post_id, $meta);
        self::apply_taxonomies((int) $post_id, $tax);
        $post = get_post((int) $post_id);

        $created = self::prepare_portal_post($post, true);
        $created_diff = Diff::compare(null, $created);
        Route_Utils::log_write($request, 'write:portal', 'portal_content', (string) $post_id, false, null, $created, $created_diff, 201);

        return new \WP_REST_Response(['success' => true, 'dry_run' => false, 'blocked_keys' => $blocked_meta, 'snapshot_ids' => [], 'diff' => $created_diff, 'data' => $created], 201);
    }

    public static function update_content(WP_REST_Request $request)
    {
        $post_id = (int) $request['id'];
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, Field_Registry::portal_post_types(), true)) {
            return new \WP_Error('caa_portal_content_not_found', 'Portal content not found.', ['status' => 404]);
        }

        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $before = self::prepare_portal_post($post, true);
        $snapshot_ids = [];

        $postarr = ['ID' => $post_id];
        foreach ([
            'post_title' => ['title', 'post_title'],
            'post_content' => ['content', 'post_content'],
            'post_excerpt' => ['excerpt', 'post_excerpt'],
            'post_status' => ['status', 'post_status'],
        ] as $field => $aliases) {
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $payload)) {
                    $postarr[$field] = $field === 'post_content'
                        ? wp_kses_post((string) $payload[$alias])
                        : sanitize_text_field((string) $payload[$alias]);
                    break;
                }
            }
        }

        [$meta, $blocked_meta] = self::meta_from_payload($payload);
        $tax = self::tax_from_payload($payload, (string) $post->post_type);

        if ($dry_run) {
            $after = $before;
            foreach ($postarr as $key => $value) {
                if ($key !== 'ID') {
                    $after[$key] = $value;
                }
            }
            $after['meta'] = array_merge($after['meta'], $meta);
            $after['taxonomies'] = array_merge($after['taxonomies'], $tax);
            $diff = Diff::compare($before, $after);
            Route_Utils::log_write($request, 'write:portal', 'portal_content', (string) $post_id, true, $before, $after, $diff);
            return rest_ensure_response(['success' => true, 'dry_run' => true, 'blocked_keys' => $blocked_meta, 'snapshot_ids' => [], 'diff' => $diff, 'data' => $after]);
        }

        if (count($postarr) > 1) {
            foreach ([
                'post_title' => 'title',
                'post_content' => 'content',
                'post_excerpt' => 'excerpt',
                'post_status' => 'status',
            ] as $post_field => $response_field) {
                if (array_key_exists($post_field, $postarr) && (string) $before[$response_field] !== (string) $postarr[$post_field]) {
                    $snapshot_ids[] = Snapshot_Store::create_snapshot(
                        Auth::current_key_id(),
                        'write:portal',
                        'post_field',
                        $post_id . ':' . $post_field,
                        $before[$response_field],
                        $postarr[$post_field]
                    );
                }
            }
            $updated = wp_update_post($postarr, true);
            if (is_wp_error($updated)) {
                return $updated;
            }
        }
        foreach ($meta as $key => $value) {
            $old_meta = get_post_meta($post_id, $key, true);
            $new_meta = $value === null ? null : Route_Utils::sanitize_value_for_storage($key, $value);
            if ($old_meta !== $new_meta) {
                $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), 'write:portal', 'post_meta', $post_id . ':' . $key, $old_meta, $new_meta);
            }
        }
        self::apply_portal_meta($post_id, $meta);
        self::apply_taxonomies($post_id, $tax);

        $after = self::prepare_portal_post(get_post($post_id), true);
        $diff = Diff::compare($before, $after);
        Route_Utils::log_write($request, 'write:portal', 'portal_content', (string) $post_id, false, $before, $after, $diff);

        return rest_ensure_response(['success' => true, 'dry_run' => false, 'blocked_keys' => $blocked_meta, 'snapshot_ids' => $snapshot_ids, 'diff' => $diff, 'data' => $after]);
    }

    public static function delete_content(WP_REST_Request $request)
    {
        $post_id = (int) $request['id'];
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, Field_Registry::portal_post_types(), true)) {
            return new \WP_Error('caa_portal_content_not_found', 'Portal content not found.', ['status' => 404]);
        }

        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $force = Utils::truthy($payload['force'] ?? $request->get_param('force'));
        $before = self::prepare_portal_post($post, true);

        $after = ['id' => $post_id, 'deleted' => true, 'force' => $force];
        $diff = Diff::compare($before, $after);

        if (!$dry_run) {
            wp_delete_post($post_id, $force);
        }

        Route_Utils::log_write($request, 'write:portal', 'portal_content', (string) $post_id, $dry_run, $before, $after, $diff);
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'snapshot_ids' => [], 'diff' => $diff, 'data' => $after]);
    }

    public static function list_taxonomies(WP_REST_Request $request)
    {
        $data = [];
        foreach (Field_Registry::portal_taxonomies() as $taxonomy) {
            $data[$taxonomy] = taxonomy_exists($taxonomy);
        }
        return rest_ensure_response(['success' => true, 'data' => $data]);
    }

    public static function dashboard(WP_REST_Request $request)
    {
        $counts = [];
        foreach (Field_Registry::portal_post_types() as $post_type) {
            $counts[$post_type] = (int) wp_count_posts($post_type)->publish;
        }

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'counts' => $counts,
                'taxonomies' => Field_Registry::portal_taxonomies(),
            ],
        ]);
    }

    public static function years(WP_REST_Request $request)
    {
        $terms = get_terms(['taxonomy' => 'portal_year', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
        if (is_wp_error($terms)) {
            return $terms;
        }
        $items = [];
        foreach ((array) $terms as $term) {
            $items[] = [
                'id' => (int) $term->term_id,
                'slug' => (string) $term->slug,
                'label' => (string) $term->name,
            ];
        }
        return rest_ensure_response(['success' => true, 'data' => $items]);
    }

    public static function list_terms(WP_REST_Request $request)
    {
        $taxonomy = sanitize_key((string) $request['taxonomy']);
        if (!in_array($taxonomy, Field_Registry::portal_taxonomies(), true)) {
            return new \WP_Error('caa_invalid_portal_taxonomy', 'Invalid portal taxonomy.', ['status' => 400]);
        }

        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']);
        if (is_wp_error($terms)) {
            return $terms;
        }

        $items = [];
        foreach ((array) $terms as $term) {
            $items[] = [
                'id' => (int) $term->term_id,
                'slug' => (string) $term->slug,
                'name' => (string) $term->name,
                'count' => (int) $term->count,
            ];
        }

        return rest_ensure_response(['success' => true, 'taxonomy' => $taxonomy, 'data' => $items]);
    }

    public static function bulk_import_capabilities(WP_REST_Request $request)
    {
        return rest_ensure_response([
            'success' => true,
            'data' => [
                'row_fields' => ['title', 'post_type', 'content', 'file_id', 'year', 'month', 'school', 'priority', 'event_date', 'taxonomies', 'meta'],
                'post_types' => Field_Registry::portal_post_types(),
                'taxonomies' => Field_Registry::portal_taxonomies(),
                'supports_dry_run' => true,
                'supports_rollback_by_ids' => true,
            ],
        ]);
    }

    public static function bulk_import(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $operation = sanitize_key((string) ($payload['operation'] ?? 'run'));
        if ($operation === 'rollback') {
            return self::bulk_rollback($request, $payload);
        }
        if ($operation === 'assign_files') {
            return self::bulk_assign_files($request, $payload);
        }
        $rows = isset($payload['rows']) && is_array($payload['rows']) ? $payload['rows'] : [];
        $dry_run = Route_Utils::dry_run($payload);
        if (empty($rows)) {
            return new \WP_Error('caa_rows_required', 'rows array is required.', ['status' => 400]);
        }

        $results = [];
        $blocked_keys = [];
        foreach ($rows as $index => $row) {
            if (!is_array($row)) {
                $results[$index] = ['success' => false, 'error' => 'invalid_row'];
                continue;
            }

            $post_type = sanitize_key((string) ($row['post_type'] ?? 'cp_resource'));
            $title = sanitize_text_field((string) ($row['title'] ?? ''));
            if (!in_array($post_type, Field_Registry::portal_post_types(), true) || $title === '') {
                $results[$index] = ['success' => false, 'error' => 'invalid_post_type_or_title'];
                continue;
            }

            [$meta, $blocked_meta] = self::meta_from_payload($row);
            $blocked_keys = array_merge($blocked_keys, $blocked_meta);

            if ($dry_run) {
                $results[$index] = ['success' => true, 'dry_run' => true, 'post_type' => $post_type, 'title' => $title, 'meta' => $meta, 'blocked_keys' => $blocked_meta];
                continue;
            }

            $post_id = wp_insert_post([
                'post_title' => $title,
                'post_content' => wp_kses_post((string) ($row['content'] ?? '')),
                'post_status' => sanitize_key((string) ($row['status'] ?? 'publish')),
                'post_type' => $post_type,
            ], true);

            if (is_wp_error($post_id)) {
                $results[$index] = ['success' => false, 'error' => $post_id->get_error_message()];
                continue;
            }

            self::apply_portal_meta((int) $post_id, $meta);
            self::apply_taxonomies((int) $post_id, self::tax_from_payload($row, $post_type));
            $results[$index] = ['success' => true, 'post_id' => (int) $post_id];
        }

        $diff = Diff::compare(null, $results);
        Route_Utils::log_write($request, 'write:portal', 'portal_bulk_import', 'batch', $dry_run, null, $results, $diff);
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'blocked_keys' => array_values(array_unique($blocked_keys)), 'snapshot_ids' => [], 'diff' => $diff, 'data' => $results]);
    }

    public static function reset_family_pin(WP_REST_Request $request)
    {
        $post_id = (int) $request['id'];
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'cp_family') {
            return new \WP_Error('caa_family_not_found', 'Portal family not found.', ['status' => 404]);
        }

        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $pin = preg_replace('/\D+/', '', (string) ($payload['pin'] ?? ''));
        if ($pin === '' || strlen($pin) < 4) {
            return new \WP_Error('caa_invalid_pin', 'A numeric PIN of at least 4 digits is required.', ['status' => 400]);
        }

        $before = [
            '_cp_pin_hash' => '[REDACTED]',
            '_cp_pin_simple_hash' => '[REDACTED]',
        ];
        $after = [
            '_cp_pin_hash' => '[REDACTED_UPDATED]',
            '_cp_pin_simple_hash' => '[REDACTED_UPDATED]',
        ];
        $diff = Diff::compare($before, $after);
        $snapshot_ids = [];

        if (!$dry_run) {
            $old_pin_hash = get_post_meta($post_id, '_cp_pin_hash', true);
            $old_simple_hash = get_post_meta($post_id, '_cp_pin_simple_hash', true);
            $new_pin_hash = wp_hash_password($pin);
            $new_simple_hash = md5($pin);

            if ($old_pin_hash !== $new_pin_hash) {
                $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), 'write:portal', 'post_meta', $post_id . ':_cp_pin_hash', $old_pin_hash, $new_pin_hash);
            }
            if ($old_simple_hash !== $new_simple_hash) {
                $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), 'write:portal', 'post_meta', $post_id . ':_cp_pin_simple_hash', $old_simple_hash, $new_simple_hash);
            }

            update_post_meta($post_id, '_cp_pin_hash', $new_pin_hash);
            update_post_meta($post_id, '_cp_pin_simple_hash', $new_simple_hash);
        }

        Route_Utils::log_write($request, 'write:portal', 'portal_family_pin', (string) $post_id, $dry_run, $before, $after, $diff);
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'snapshot_ids' => $snapshot_ids, 'diff' => $diff, 'data' => ['family_id' => $post_id, 'pin_reset' => true]]);
    }

    public static function describe(): array
    {
        return [
            'post_types' => Field_Registry::portal_post_types(),
            'taxonomies' => Field_Registry::portal_taxonomies(),
            'meta_fields' => Field_Registry::portal_meta_fields(),
            'routes' => [
                '/portal/content',
                '/portal/dashboard',
                '/portal/years',
                '/portal/taxonomies',
                '/portal/bulk-import',
                '/portal/families/{id}/pin',
            ],
        ];
    }

    private static function prepare_portal_post($post, bool $include_detail): array
    {
        $data = [
            'id' => (int) $post->ID,
            'post_type' => (string) $post->post_type,
            'status' => (string) $post->post_status,
            'title' => (string) $post->post_title,
            'modified_gmt' => (string) $post->post_modified_gmt,
        ];

        if (!$include_detail) {
            return $data;
        }

        $data['content'] = (string) $post->post_content;
        $data['excerpt'] = (string) $post->post_excerpt;
        $data['meta'] = Route_Utils::read_post_meta_values((int) $post->ID, Field_Registry::portal_meta_fields());
        $data['meta']['_cp_pin_hash'] = $data['meta']['_cp_pin_hash'] ? '[REDACTED]' : '';
        $data['meta']['_cp_pin_simple_hash'] = $data['meta']['_cp_pin_simple_hash'] ? '[REDACTED]' : '';
        $data['taxonomies'] = [];
        foreach (Field_Registry::portal_taxonomies() as $taxonomy) {
            if (taxonomy_exists($taxonomy) && is_object_in_taxonomy($post->post_type, $taxonomy)) {
                $terms = wp_get_object_terms((int) $post->ID, $taxonomy, ['fields' => 'names']);
                $data['taxonomies'][$taxonomy] = is_wp_error($terms) ? [] : array_values((array) $terms);
            }
        }

        return $data;
    }

    private static function meta_from_payload(array $payload): array
    {
        $meta = isset($payload['meta']) && is_array($payload['meta']) ? $payload['meta'] : [];
        $blocked = [];
        foreach (['_cp_pin_hash', '_cp_pin_simple_hash'] as $protected_key) {
            if (array_key_exists($protected_key, $meta)) {
                $blocked[] = $protected_key;
                unset($meta[$protected_key]);
            }
        }

        foreach ([
            'file_id' => '_cp_pdf_file_id',
            'pdf_file_id' => '_cp_pdf_file_id',
            'priority' => '_cp_priority',
            'event_date' => '_cp_event_date',
        ] as $input => $meta_key) {
            if (array_key_exists($input, $payload)) {
                $meta[$meta_key] = $payload[$input];
            }
        }

        [$meta, $policy_blocked] = Route_Utils::partition_updates($meta, Field_Registry::portal_meta_fields());
        $blocked = array_values(array_unique(array_merge($blocked, $policy_blocked)));

        return [$meta, $blocked];
    }

    private static function tax_from_payload(array $payload, string $post_type): array
    {
        $tax = [];
        if (isset($payload['taxonomies']) && is_array($payload['taxonomies'])) {
            $tax = $payload['taxonomies'];
        } elseif (isset($payload['tax']) && is_array($payload['tax'])) {
            $tax = $payload['tax'];
        }

        foreach ([
            'school' => 'portal_school',
            'year' => 'portal_year',
            'classroom' => 'portal_classroom',
        ] as $input => $taxonomy) {
            if (!empty($payload[$input])) {
                $tax[$taxonomy] = $payload[$input];
            }
        }

        if (!empty($payload['month'])) {
            $tax[self::month_taxonomy_for_type($post_type)] = $payload['month'];
        }

        return $tax;
    }

    private static function apply_portal_meta(int $post_id, array $meta): void
    {
        foreach ($meta as $key => $value) {
            if (!in_array($key, Field_Registry::portal_meta_fields(), true)) {
                continue;
            }

            if ($value === null) {
                delete_post_meta($post_id, $key);
                continue;
            }

            update_post_meta($post_id, $key, Route_Utils::sanitize_value_for_storage($key, $value));
        }
    }

    private static function apply_taxonomies(int $post_id, array $tax): void
    {
        $post_type = (string) get_post_type($post_id);
        foreach ($tax as $taxonomy => $terms) {
            $taxonomy = sanitize_key((string) $taxonomy);
            if (!in_array($taxonomy, Field_Registry::portal_taxonomies(), true) || !taxonomy_exists($taxonomy) || !is_object_in_taxonomy($post_type, $taxonomy)) {
                continue;
            }
            if (!is_array($terms)) {
                $terms = [$terms];
            }
            $clean = array_map(static function ($term) {
                return is_numeric($term) ? (int) $term : sanitize_text_field((string) $term);
            }, $terms);
            wp_set_object_terms($post_id, $clean, $taxonomy, false);
        }
    }

    private static function month_taxonomy_for_type(string $post_type): string
    {
        if ($post_type === 'cp_meal_plan') {
            return 'portal_quarter';
        }
        if (in_array($post_type, ['cp_lesson_plan', 'cp_announcement', 'cp_event'], true)) {
            return 'portal_month';
        }
        return 'portal_category';
    }

    private static function bulk_rollback(WP_REST_Request $request, array $payload)
    {
        $dry_run = Route_Utils::dry_run($payload);
        $post_ids = isset($payload['post_ids']) && is_array($payload['post_ids']) ? array_map('intval', $payload['post_ids']) : [];
        if (empty($post_ids)) {
            return new \WP_Error('caa_post_ids_required', 'post_ids array is required for rollback.', ['status' => 400]);
        }
        $results = [];
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post || !in_array($post->post_type, Field_Registry::portal_post_types(), true)) {
                $results[$post_id] = ['success' => false, 'error' => 'post_not_found'];
                continue;
            }
            if (!$dry_run) {
                wp_delete_post($post_id, true);
            }
            $results[$post_id] = ['success' => true, 'deleted' => true];
        }
        $diff = Diff::compare(null, $results);
        Route_Utils::log_write($request, 'write:portal', 'portal_bulk_rollback', 'batch', $dry_run, null, $results, $diff);
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'snapshot_ids' => [], 'diff' => $diff, 'data' => $results]);
    }

    private static function bulk_assign_files(WP_REST_Request $request, array $payload)
    {
        $dry_run = Route_Utils::dry_run($payload);
        $assignments = isset($payload['assignments']) && is_array($payload['assignments']) ? $payload['assignments'] : [];
        if (empty($assignments)) {
            return new \WP_Error('caa_assignments_required', 'assignments array is required.', ['status' => 400]);
        }
        $results = [];
        $before = [];
        $after = [];
        $snapshot_ids = [];
        foreach ($assignments as $row) {
            if (!is_array($row)) {
                continue;
            }
            $post_id = (int) ($row['post_id'] ?? 0);
            $attachment_id = (int) ($row['attachment_id'] ?? 0);
            if ($post_id <= 0 || $attachment_id <= 0 || !get_post($post_id) || !get_post($attachment_id)) {
                $results[] = ['success' => false, 'post_id' => $post_id, 'attachment_id' => $attachment_id];
                continue;
            }
            $meta_key = '_cp_pdf_file_id';
            $before[$post_id] = get_post_meta($post_id, $meta_key, true);
            $after[$post_id] = $attachment_id;
            if (!$dry_run) {
                if ($before[$post_id] !== $after[$post_id]) {
                    $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), 'write:portal', 'post_meta', $post_id . ':' . $meta_key, $before[$post_id], $after[$post_id]);
                }
                update_post_meta($post_id, $meta_key, $attachment_id);
            }
            $results[] = ['success' => true, 'post_id' => $post_id, 'attachment_id' => $attachment_id];
        }
        $diff = Diff::compare($before, $after);
        Route_Utils::log_write($request, 'write:portal', 'portal_bulk_assign_files', 'batch', $dry_run, $before, $after, $diff);
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'snapshot_ids' => $snapshot_ids, 'diff' => $diff, 'data' => $results]);
    }
}

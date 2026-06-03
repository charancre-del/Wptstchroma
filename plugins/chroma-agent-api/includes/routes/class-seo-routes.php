<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Audit_Log;
use ChromaAgentAPI\Diff;
use ChromaAgentAPI\Route_Utils;
use ChromaAgentAPI\Snapshot_Store;
use ChromaAgentAPI\Utils;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class SEO_Routes
{
    private const NS = 'chroma-agent/v1';
    private const SCHEMA_META_KEYS = [
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
    ];
    private const SCHEMA_ALIAS_MAP = [
        'schemas' => '_chroma_post_schemas',
        'schema_override' => '_chroma_schema_override',
        'schema_type' => '_chroma_schema_type',
        'schema_data' => '_chroma_schema_data',
        'schema_confidence' => '_chroma_schema_confidence',
        'needs_review' => '_chroma_needs_review',
        'review_reason' => '_chroma_review_reason',
        'schema_history' => '_chroma_schema_history',
        'validation_status' => '_chroma_schema_validation_status',
        'schema_errors' => '_chroma_schema_errors',
    ];

    public static function register(): void
    {
        register_rest_route(self::NS, '/seo/options', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_seo_options'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_seo_options'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/seo/meta/(?P<post_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_post_seo_meta'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_post_seo_meta'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/seo/schema/(?P<post_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_post_schema'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_post_schema'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/seo/schema', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'list_schema_posts'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
        ]);

        // Backwards-compatible aliases for earlier docs/clients.
        register_rest_route(self::NS, '/schema/seo', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'list_schema_posts'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/schema/seo/(?P<post_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_post_schema'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_post_schema'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
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

    public static function get_seo_options(WP_REST_Request $request)
    {
        $allowlist = Utils::get_seo_option_allowlist();
        $keys = $request->get_param('keys');

        if (is_string($keys) && $keys !== '') {
            $requested = array_filter(array_map('trim', explode(',', $keys)));
            $allowlist = array_values(array_intersect($allowlist, $requested));
        }

        $data = [];
        foreach ($allowlist as $option_name) {
            $data[$option_name] = get_option($option_name, null);
        }

        return rest_ensure_response([
            'success' => true,
            'allowlist' => $allowlist,
            'data' => $data,
        ]);
    }

    public static function set_seo_options(WP_REST_Request $request)
    {
        $payload = self::payload($request);
        $updates = isset($payload['updates']) && is_array($payload['updates']) ? $payload['updates'] : $payload;
        $dry_run = Utils::truthy($payload['dry_run'] ?? false);
        unset($updates['dry_run'], $updates['updates']);

        $allowlist = Utils::get_seo_option_allowlist();
        $blocked = [];
        $before = [];
        $after = [];
        $snapshot_ids = [];

        foreach ((array) $updates as $key => $value) {
            $option_name = (string) $key;
            if (!in_array($option_name, $allowlist, true)) {
                $blocked[] = $option_name;
                continue;
            }

            $old = get_option($option_name, null);
            $new = Utils::sanitize_mixed_for_storage($value);

            $before[$option_name] = $old;
            $after[$option_name] = $new;

            if (!$dry_run && $old !== $new) {
                $snapshot_ids[] = Snapshot_Store::create_snapshot(
                    Auth::current_key_id(),
                    'write:seo',
                    'option',
                    $option_name,
                    $old,
                    $new
                );
                update_option($option_name, $new, false);
            }
        }

        $diff = Diff::compare($before, $after);

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'write:seo',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'seo_option',
            'target_id' => 'batch',
            'dry_run' => $dry_run,
            'before' => $before,
            'after' => $after,
            'diff' => $diff,
            'status_code' => 200,
        ]);

        return rest_ensure_response([
            'success' => true,
            'dry_run' => $dry_run,
            'blocked_keys' => $blocked,
            'snapshot_ids' => $snapshot_ids,
            'diff' => $diff,
            'data' => $dry_run ? $after : self::read_options_by_keys(array_keys($after)),
        ]);
    }

    public static function get_post_seo_meta(WP_REST_Request $request)
    {
        $post_id = (int) $request['post_id'];
        $post = get_post($post_id);

        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $allowlist = Utils::get_seo_meta_allowlist();
        $data = [];
        foreach ($allowlist as $meta_key) {
            $data[$meta_key] = get_post_meta($post_id, $meta_key, true);
        }

        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'allowlist' => $allowlist,
            'data' => $data,
        ]);
    }

    public static function set_post_seo_meta(WP_REST_Request $request)
    {
        $post_id = (int) $request['post_id'];
        $post = get_post($post_id);

        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $payload = self::payload($request);
        $updates = isset($payload['updates']) && is_array($payload['updates']) ? $payload['updates'] : $payload;
        $dry_run = Utils::truthy($payload['dry_run'] ?? false);
        $strict_write = Utils::truthy($payload['strict_write'] ?? false);
        unset($updates['dry_run'], $updates['strict_write'], $updates['updates']);

        $allowlist = Utils::get_seo_meta_allowlist();
        $before = [];
        $after = [];
        $blocked = [];
        $write_mismatches = [];
        $snapshot_ids = [];

        foreach ((array) $updates as $key => $value) {
            $meta_key = (string) $key;
            if (!in_array($meta_key, $allowlist, true)) {
                $blocked[] = $meta_key;
                continue;
            }

            $old = get_post_meta($post_id, $meta_key, true);
            $before[$meta_key] = $old;

            if ($value === null) {
                $after[$meta_key] = null;
                if (!$dry_run) {
                    $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), 'write:seo', 'post_meta', $post_id . ':' . $meta_key, $old, null);
                    delete_post_meta($post_id, $meta_key);
                }
                continue;
            }

            $new = self::sanitize_meta_value_by_key($meta_key, $value);
            $after[$meta_key] = $new;

            if (!$dry_run) {
                $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), 'write:seo', 'post_meta', $post_id . ':' . $meta_key, $old, $new);
                self::persist_meta_value($post_id, $meta_key, $new);
                $saved = get_post_meta($post_id, $meta_key, true);
                if (!self::meta_values_equivalent($new, $saved)) {
                    $write_mismatches[$meta_key] = [
                        'expected' => $new,
                        'actual' => $saved,
                    ];
                }
            }
        }

        $diff = Diff::compare($before, $after);

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'write:seo',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'post_seo_meta',
            'target_id' => (string) $post_id,
            'dry_run' => $dry_run,
            'before' => $before,
            'after' => $after,
            'diff' => $diff,
            'status_code' => 200,
        ]);

        $live = [];
        foreach (array_keys($after) as $meta_key) {
            $live[$meta_key] = get_post_meta($post_id, $meta_key, true);
        }

        if (!$dry_run && $strict_write && !empty($write_mismatches)) {
            return new \WP_Error(
                'caa_write_integrity_failed',
                'One or more meta writes were altered during persistence.',
                [
                    'status' => 409,
                    'post_id' => $post_id,
                    'mismatches' => $write_mismatches,
                    'data' => $live,
                ]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'dry_run' => $dry_run,
            'post_id' => $post_id,
            'blocked_keys' => $blocked,
            'write_mismatches' => $write_mismatches,
            'snapshot_ids' => $snapshot_ids,
            'diff' => $diff,
            'data' => $dry_run ? $after : $live,
        ]);
    }

    public static function get_post_schema(WP_REST_Request $request)
    {
        $post_id = (int) $request['post_id'];
        $post = get_post($post_id);

        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $data = self::get_schema_data_for_post($post_id);

        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'schema_keys' => self::SCHEMA_META_KEYS,
            'data' => $data,
        ]);
    }

    public static function list_schema_posts(WP_REST_Request $request)
    {
        $page = max(1, (int) $request->get_param('page'));
        $per_page = (int) $request->get_param('per_page');
        if ($per_page <= 0) {
            $per_page = 20;
        }
        $per_page = min(100, $per_page);

        $post_type = $request->get_param('post_type');
        if (is_string($post_type) && strpos($post_type, ',') !== false) {
            $post_type = array_filter(array_map('trim', explode(',', $post_type)));
        }
        if (empty($post_type)) {
            $post_type = get_post_types(['public' => true], 'names');
            unset($post_type['attachment']);
            $post_type = array_values($post_type);
        }
        $post_type = is_array($post_type) ? $post_type : [(string) $post_type];
        $post_type = array_values(array_filter(array_map('sanitize_key', $post_type), 'post_type_exists'));
        if (empty($post_type)) {
            return new \WP_Error('caa_invalid_post_type', 'No valid post types were requested.', ['status' => 400]);
        }

        $needs_review_param = $request->get_param('needs_review');
        $needs_review = null;
        if ($needs_review_param !== null && $needs_review_param !== '') {
            $needs_review = Utils::truthy($needs_review_param);
        }

        $has_schema_param = $request->get_param('has_schema');
        $has_schema = true;
        if ($has_schema_param !== null && $has_schema_param !== '') {
            $has_schema = Utils::truthy($has_schema_param);
        }

        if ($has_schema) {
            return self::list_schema_posts_with_indexed_query($request, $post_type, $page, $per_page, $needs_review);
        }

        $args = [
            'post_type' => $post_type,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            's' => sanitize_text_field((string) $request->get_param('search')),
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => false,
        ];

        if ($needs_review === true) {
            $args['meta_query'] = [['key' => '_chroma_needs_review', 'compare' => 'EXISTS']];
        } elseif ($needs_review === false) {
            $args['meta_query'] = [['key' => '_chroma_needs_review', 'compare' => 'NOT EXISTS']];
        }

        $query = new \WP_Query($args);
        $items = [];
        $include_data = Utils::truthy($request->get_param('include_data'));

        foreach ((array) $query->posts as $post) {
            $schemas = get_post_meta($post->ID, '_chroma_post_schemas', true);
            $schema_count = is_array($schemas) ? count($schemas) : 0;
            $override = get_post_meta($post->ID, '_chroma_schema_override', true);
            $needs_review_value = get_post_meta($post->ID, '_chroma_needs_review', true);

            $item = [
                'post_id' => (int) $post->ID,
                'post_type' => (string) $post->post_type,
                'post_status' => (string) $post->post_status,
                'title' => get_the_title($post),
                'slug' => (string) $post->post_name,
                'permalink' => get_permalink($post),
                'modified_gmt' => (string) $post->post_modified_gmt,
                'schema_count' => $schema_count,
                'has_schema_override' => !empty($override),
                'needs_review' => Utils::truthy($needs_review_value),
                'schema_validation_status' => get_post_meta($post->ID, '_chroma_schema_validation_status', true),
            ];

            if ($include_data) {
                $item['schema'] = self::get_schema_data_for_post((int) $post->ID);
            }

            $items[] = $item;
        }

        return rest_ensure_response([
            'success' => true,
            'schema_keys' => self::SCHEMA_META_KEYS,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => (int) $query->found_posts,
                'total_pages' => (int) $query->max_num_pages,
            ],
        ]);
    }

    private static function list_schema_posts_with_indexed_query(
        WP_REST_Request $request,
        array $post_type,
        int $page,
        int $per_page,
        ?bool $needs_review
    ) {
        global $wpdb;

        $schema_keys = ['_chroma_post_schemas', '_chroma_schema_override', '_chroma_schema_data'];
        $statuses = ['publish', 'draft', 'pending', 'private'];
        $offset = ($page - 1) * $per_page;
        $search = sanitize_text_field((string) $request->get_param('search'));

        $schema_placeholders = implode(',', array_fill(0, count($schema_keys), '%s'));
        $type_placeholders = implode(',', array_fill(0, count($post_type), '%s'));
        $status_placeholders = implode(',', array_fill(0, count($statuses), '%s'));

        $where = "pm.meta_key IN ($schema_placeholders)
            AND p.post_type IN ($type_placeholders)
            AND p.post_status IN ($status_placeholders)";
        $where_args = array_merge($schema_keys, $post_type, $statuses);

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= ' AND (p.post_title LIKE %s OR p.post_content LIKE %s OR p.post_excerpt LIKE %s)';
            $where_args[] = $like;
            $where_args[] = $like;
            $where_args[] = $like;
        }

        if ($needs_review === true) {
            $where .= " AND EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} review_pm
                WHERE review_pm.post_id = p.ID AND review_pm.meta_key = %s
            )";
            $where_args[] = '_chroma_needs_review';
        } elseif ($needs_review === false) {
            $where .= " AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} review_pm
                WHERE review_pm.post_id = p.ID AND review_pm.meta_key = %s
            )";
            $where_args[] = '_chroma_needs_review';
        }

        $ids_sql = $wpdb->prepare(
            "SELECT DISTINCT p.ID
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE {$where}
             ORDER BY p.post_modified_gmt DESC, p.ID DESC
             LIMIT %d OFFSET %d",
            array_merge($where_args, [$per_page, $offset])
        );
        $count_sql = $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE {$where}",
            $where_args
        );

        $post_ids = array_map('intval', (array) $wpdb->get_col($ids_sql));
        $total = (int) $wpdb->get_var($count_sql);
        $items = [];
        $include_data = Utils::truthy($request->get_param('include_data'));

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                continue;
            }

            $schemas = get_post_meta($post_id, '_chroma_post_schemas', true);
            $schema_data = get_post_meta($post_id, '_chroma_schema_data', true);
            $schema_count = is_array($schemas) ? count($schemas) : 0;
            if ($schema_count === 0 && !empty($schema_data)) {
                $schema_count = 1;
            }
            $override = get_post_meta($post_id, '_chroma_schema_override', true);
            $needs_review_value = get_post_meta($post_id, '_chroma_needs_review', true);

            $item = [
                'post_id' => $post_id,
                'post_type' => (string) $post->post_type,
                'post_status' => (string) $post->post_status,
                'title' => get_the_title($post),
                'slug' => (string) $post->post_name,
                'permalink' => get_permalink($post),
                'modified_gmt' => (string) $post->post_modified_gmt,
                'schema_count' => $schema_count,
                'has_schema_override' => !empty($override),
                'needs_review' => Utils::truthy($needs_review_value),
                'schema_validation_status' => get_post_meta($post_id, '_chroma_schema_validation_status', true),
            ];

            if ($include_data) {
                $item['schema'] = self::get_schema_data_for_post($post_id);
            }

            $items[] = $item;
        }

        return rest_ensure_response([
            'success' => true,
            'schema_keys' => self::SCHEMA_META_KEYS,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total,
                'total_pages' => (int) ceil($total / $per_page),
            ],
        ]);
    }

    public static function set_post_schema(WP_REST_Request $request)
    {
        $post_id = (int) $request['post_id'];
        $post = get_post($post_id);

        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $payload = self::payload($request);
        $updates = isset($payload['updates']) && is_array($payload['updates']) ? $payload['updates'] : $payload;
        $updates = self::normalize_schema_updates($updates);
        $dry_run = Utils::truthy($payload['dry_run'] ?? false);
        $strict_write = Utils::truthy($payload['strict_write'] ?? false);
        unset($updates['dry_run'], $updates['strict_write'], $updates['updates']);

        $before = [];
        $after = [];
        $blocked = [];
        $write_mismatches = [];
        $snapshot_ids = [];

        foreach ((array) $updates as $key => $value) {
            $meta_key = (string) $key;
            if (!in_array($meta_key, self::SCHEMA_META_KEYS, true)) {
                $blocked[] = $meta_key;
                continue;
            }

            $old = get_post_meta($post_id, $meta_key, true);
            $before[$meta_key] = $old;

            if ($value === null) {
                $after[$meta_key] = null;
                if (!$dry_run) {
                    $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), 'write:seo', 'post_meta', $post_id . ':' . $meta_key, $old, null);
                    delete_post_meta($post_id, $meta_key);
                }
                continue;
            }

            $new = self::sanitize_meta_value_by_key($meta_key, $value);
            $after[$meta_key] = $new;

            if (!$dry_run) {
                $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), 'write:seo', 'post_meta', $post_id . ':' . $meta_key, $old, $new);
                self::persist_meta_value($post_id, $meta_key, $new);
                $saved = get_post_meta($post_id, $meta_key, true);
                if (!self::meta_values_equivalent($new, $saved)) {
                    $write_mismatches[$meta_key] = [
                        'expected' => $new,
                        'actual' => $saved,
                    ];
                }
            }
        }

        $diff = Diff::compare($before, $after);

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'write:seo',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'post_schema_meta',
            'target_id' => (string) $post_id,
            'dry_run' => $dry_run,
            'before' => $before,
            'after' => $after,
            'diff' => $diff,
            'status_code' => 200,
        ]);

        $live = [];
        foreach (array_keys($after) as $meta_key) {
            $live[$meta_key] = get_post_meta($post_id, $meta_key, true);
        }

        if (!$dry_run && $strict_write && !empty($write_mismatches)) {
            return new \WP_Error(
                'caa_write_integrity_failed',
                'One or more schema writes were altered during persistence.',
                [
                    'status' => 409,
                    'post_id' => $post_id,
                    'mismatches' => $write_mismatches,
                    'data' => $live,
                ]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'dry_run' => $dry_run,
            'post_id' => $post_id,
            'blocked_keys' => $blocked,
            'write_mismatches' => $write_mismatches,
            'snapshot_ids' => $snapshot_ids,
            'diff' => $diff,
            'data' => $dry_run ? $after : $live,
        ]);
    }

    private static function payload(WP_REST_Request $request): array
    {
        return Route_Utils::payload($request);
    }

    private static function read_options_by_keys(array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = get_option((string) $key, null);
        }
        return $data;
    }

    private static function sanitize_meta_value_by_key(string $meta_key, $value)
    {
        if (in_array($meta_key, ['_chroma_post_schemas', '_chroma_schema_data', '_chroma_schema_override'], true)) {
            return Route_Utils::sanitize_value_for_storage($meta_key, $value);
        }

        if (in_array($meta_key, ['_chroma_schema_history', '_chroma_schema_errors'], true)) {
            return Utils::sanitize_mixed_for_storage_preserve_keys($value);
        }

        if ($meta_key === '_chroma_needs_review') {
            return Utils::truthy($value);
        }

        if ($meta_key === '_chroma_schema_confidence') {
            return is_numeric($value) ? (float) $value : 0.0;
        }

        if (in_array($meta_key, ['_chroma_review_reason', '_chroma_schema_validation_status', '_chroma_schema_type'], true)) {
            return sanitize_text_field((string) $value);
        }

        return Utils::sanitize_mixed_for_storage($value);
    }

    private static function persist_meta_value(int $post_id, string $meta_key, $value): void
    {
        if (!self::should_bypass_meta_sanitizers($meta_key)) {
            update_post_meta($post_id, $meta_key, $value);
            return;
        }

        $filter = 'sanitize_post_meta_' . $meta_key;
        $passthrough = static function ($sanitized) use ($value) {
            return $value;
        };

        add_filter($filter, $passthrough, PHP_INT_MAX, 4);
        try {
            update_post_meta($post_id, $meta_key, $value);
        } finally {
            remove_filter($filter, $passthrough, PHP_INT_MAX);
        }
    }

    private static function should_bypass_meta_sanitizers(string $meta_key): bool
    {
        return in_array($meta_key, [
            '_chroma_post_schemas',
            '_chroma_schema_data',
            '_chroma_schema_history',
            '_chroma_schema_errors',
            '_chroma_schema_override',
        ], true);
    }

    private static function meta_values_equivalent($expected, $actual): bool
    {
        return self::normalize_meta_value_for_compare($expected) == self::normalize_meta_value_for_compare($actual);
    }

    private static function normalize_meta_value_for_compare($value)
    {
        if (is_object($value)) {
            return self::normalize_meta_value_for_compare((array) $value);
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[$key] = self::normalize_meta_value_for_compare($item);
            }
            return $out;
        }

        return $value;
    }

    private static function get_schema_data_for_post(int $post_id): array
    {
        $data = [];
        foreach (self::SCHEMA_META_KEYS as $meta_key) {
            $data[$meta_key] = get_post_meta($post_id, $meta_key, true);
        }
        return $data;
    }

    private static function normalize_schema_updates($updates): array
    {
        if (!is_array($updates)) {
            return [];
        }

        $normalized = $updates;
        foreach (self::SCHEMA_ALIAS_MAP as $alias => $meta_key) {
            if (array_key_exists($alias, $normalized) && !array_key_exists($meta_key, $normalized)) {
                $normalized[$meta_key] = $normalized[$alias];
            }
        }

        return $normalized;
    }
}

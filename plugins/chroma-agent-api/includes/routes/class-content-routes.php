<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Audit_Log;
use ChromaAgentAPI\Diff;
use ChromaAgentAPI\Utils;
use WP_Query;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class Content_Routes
{
    private const NS = 'chroma-agent/v1';
    private const META_WRITE_DENYLIST = [
        '_chroma_post_schemas' => [
            'reason' => 'Schema payloads are managed by the dedicated SEO schema route.',
            'preferred_route' => '/wp-json/chroma-agent/v1/seo/schema/{id}',
        ],
        '_chroma_schema_override' => [
            'reason' => 'Schema override state is managed by the dedicated SEO schema route.',
            'preferred_route' => '/wp-json/chroma-agent/v1/seo/schema/{id}',
        ],
        '_chroma_schema_type' => [
            'reason' => 'Schema type state is managed by the dedicated SEO schema route.',
            'preferred_route' => '/wp-json/chroma-agent/v1/seo/schema/{id}',
        ],
        '_chroma_schema_data' => [
            'reason' => 'Schema data is managed by the dedicated SEO schema route.',
            'preferred_route' => '/wp-json/chroma-agent/v1/seo/schema/{id}',
        ],
        '_chroma_schema_confidence' => [
            'reason' => 'Schema confidence is managed by the SEO metadata route.',
            'preferred_route' => '/wp-json/chroma-agent/v1/seo/meta/{id}',
        ],
        '_chroma_needs_review' => [
            'reason' => 'Schema review flags are managed by the SEO metadata route.',
            'preferred_route' => '/wp-json/chroma-agent/v1/seo/meta/{id}',
        ],
        '_chroma_review_reason' => [
            'reason' => 'Schema review flags are managed by the SEO metadata route.',
            'preferred_route' => '/wp-json/chroma-agent/v1/seo/meta/{id}',
        ],
        '_chroma_schema_history' => [
            'reason' => 'Schema history is managed by the SEO metadata route.',
            'preferred_route' => '/wp-json/chroma-agent/v1/seo/meta/{id}',
        ],
        '_chroma_schema_validation_status' => [
            'reason' => 'Schema validation metadata is managed by the SEO metadata route.',
            'preferred_route' => '/wp-json/chroma-agent/v1/seo/meta/{id}',
        ],
        '_chroma_schema_errors' => [
            'reason' => 'Schema validation metadata is managed by the SEO metadata route.',
            'preferred_route' => '/wp-json/chroma-agent/v1/seo/meta/{id}',
        ],
        '_chroma_webhook_sent' => [
            'reason' => 'Webhook delivery flags are system-managed.',
            'preferred_route' => '',
        ],
        'lead_payload' => [
            'reason' => 'Lead payloads are system-managed by capture integrations.',
            'preferred_route' => '',
        ],
    ];
    private const META_WRITE_PREFIX_DENYLIST = [
        '_cp_' => [
            'reason' => 'Parent portal meta must be updated through the parent portal workflow.',
            'preferred_route' => '/wp-json/chroma-portal/v1/*',
        ],
        '_chroma_school_' => [
            'reason' => 'School dashboard meta must be updated through the school dashboard route.',
            'preferred_route' => '/wp-json/chroma/v1/portal/school/{id}',
        ],
        'lead_' => [
            'reason' => 'Lead fields are system-managed and not writable via content/{id}.',
            'preferred_route' => '',
        ],
    ];

    public static function register(): void
    {
        register_rest_route(self::NS, '/content', [
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

        register_rest_route(self::NS, '/content/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_content'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST,PUT',
                'callback' => [__CLASS__, 'update_content'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [__CLASS__, 'delete_content'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/content/(?P<id>\d+)/rollback', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'rollback_content'],
            'permission_callback' => [__CLASS__, 'write_permission'],
        ]);
    }

    public static function read_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['read:content']);
    }

    public static function write_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['write:content']);
    }

    public static function describe_meta_write_policy(): array
    {
        $exact = [];
        foreach (self::META_WRITE_DENYLIST as $meta_key => $policy) {
            $exact[] = [
                'meta_key' => $meta_key,
                'reason' => (string) ($policy['reason'] ?? ''),
                'preferred_route' => (string) ($policy['preferred_route'] ?? ''),
            ];
        }

        $prefixes = [];
        foreach (self::META_WRITE_PREFIX_DENYLIST as $prefix => $policy) {
            $prefixes[] = [
                'prefix' => $prefix,
                'reason' => (string) ($policy['reason'] ?? ''),
                'preferred_route' => (string) ($policy['preferred_route'] ?? ''),
            ];
        }

        return [
            'route' => '/wp-json/' . self::NS . '/content/{id}',
            'enforcement' => 'denylist',
            'applies_to' => ['POST /content', 'PATCH/POST/PUT /content/{id}'],
            'blocked_exact' => $exact,
            'blocked_prefixes' => $prefixes,
        ];
    }

    public static function inspect_meta_write_policy(string $meta_key): array
    {
        $normalized = sanitize_key($meta_key);
        $policy = $normalized !== '' ? self::get_meta_write_policy($normalized) : null;

        return [
            'requested_meta_key' => $meta_key,
            'normalized_meta_key' => $normalized,
            'blocked' => $policy !== null,
            'reason' => (string) ($policy['reason'] ?? ''),
            'preferred_route' => (string) ($policy['preferred_route'] ?? ''),
        ];
    }

    public static function list_content(WP_REST_Request $request)
    {
        $page = max(1, (int) $request->get_param('page'));
        $per_page = (int) $request->get_param('per_page');
        if ($per_page <= 0) {
            $per_page = 20;
        }
        $per_page = min(100, $per_page);

        $post_type = $request->get_param('post_type');
        if (empty($post_type)) {
            $post_type = ['post', 'page'];
        }

        if (is_string($post_type) && strpos($post_type, ',') !== false) {
            $post_type = array_filter(array_map('trim', explode(',', $post_type)));
        }

        $status = $request->get_param('status');
        if (empty($status)) {
            $status = ['publish', 'draft', 'pending'];
        }

        $q = new WP_Query([
            'post_type' => $post_type,
            'post_status' => $status,
            's' => sanitize_text_field((string) $request->get_param('search')),
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => false,
        ]);

        $items = [];
        foreach ((array) $q->posts as $post) {
            $items[] = self::prepare_post_summary($post);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => (int) $q->found_posts,
                'total_pages' => (int) $q->max_num_pages,
            ],
        ]);
    }

    public static function get_content(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $post = get_post($id);

        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => self::prepare_post_detail($post),
        ]);
    }

    public static function create_content(WP_REST_Request $request)
    {
        $params = self::get_payload($request);
        $dry_run = self::is_dry_run($params);
        $strict_write = Utils::truthy($params['strict_write'] ?? false);

        $postarr = [
            'post_type' => sanitize_key((string) ($params['post_type'] ?? 'post')),
            'post_status' => sanitize_key((string) ($params['post_status'] ?? ($params['status'] ?? 'draft'))),
            'post_title' => sanitize_text_field((string) ($params['post_title'] ?? ($params['title'] ?? ''))),
            'post_name' => sanitize_title((string) ($params['slug'] ?? '')),
            'post_excerpt' => sanitize_textarea_field((string) ($params['post_excerpt'] ?? ($params['excerpt'] ?? ''))),
            'post_content' => isset($params['post_content']) ? wp_kses_post((string) $params['post_content']) : (isset($params['content']) ? wp_kses_post((string) $params['content']) : ''),
        ];

        if (!post_type_exists($postarr['post_type'])) {
            return new \WP_Error('caa_invalid_post_type', 'Invalid post_type.', ['status' => 400]);
        }

        $meta = isset($params['meta']) && is_array($params['meta']) ? $params['meta'] : [];
        [$meta, $write_policy_blocks] = self::partition_meta_by_policy($meta);
        $tax = isset($params['tax']) && is_array($params['tax']) ? $params['tax'] : [];

        if (!$dry_run && !empty($write_policy_blocks)) {
            return self::blocked_meta_response($write_policy_blocks);
        }

        if ($dry_run) {
            $after = [
                'post' => $postarr,
                'meta' => Utils::sanitize_mixed_for_storage($meta),
                'tax' => Utils::sanitize_mixed_for_storage($tax),
            ];

            Audit_Log::log_write([
                'actor_key_id' => Auth::current_key_id(),
                'scope' => 'write:content',
                'method' => $request->get_method(),
                'route' => $request->get_route(),
                'target_type' => 'post',
                'target_id' => 'new',
                'dry_run' => true,
                'before' => null,
                'after' => $after,
                'diff' => ['create' => true],
                'status_code' => 200,
            ]);

            return rest_ensure_response([
                'success' => true,
                'dry_run' => true,
                'write_policy_blocks' => $write_policy_blocks,
                'data' => $after,
            ]);
        }

        $post_id = wp_insert_post($postarr, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }

        $write_mismatches = self::apply_meta_and_tax((int) $post_id, $meta, $tax, true);
        $post = get_post((int) $post_id);
        $after = $post ? self::prepare_post_detail($post) : ['id' => (int) $post_id];

        if ($strict_write && !empty($write_mismatches)) {
            return new \WP_Error(
                'caa_write_integrity_failed',
                'One or more content writes were altered during persistence.',
                [
                    'status' => 409,
                    'post_id' => (int) $post_id,
                    'mismatches' => $write_mismatches,
                    'data' => $after,
                ]
            );
        }

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'write:content',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'post',
            'target_id' => (string) $post_id,
            'dry_run' => false,
            'before' => null,
            'after' => $after,
            'diff' => ['create' => true],
            'status_code' => 201,
        ]);

        return new \WP_REST_Response([
            'success' => true,
            'write_policy_blocks' => [],
            'write_mismatches' => $write_mismatches,
            'data' => $after,
        ], 201);
    }

    public static function update_content(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $post = get_post($id);

        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $params = self::get_payload($request);
        $dry_run = self::is_dry_run($params);
        $strict_write = Utils::truthy($params['strict_write'] ?? false);
        $before = self::prepare_post_detail($post);

        $postarr = ['ID' => $id];
        $mapped = [
            'post_title' => ['post_title', 'title'],
            'post_content' => ['post_content', 'content'],
            'post_excerpt' => ['post_excerpt', 'excerpt'],
            'post_name' => ['post_name', 'slug'],
            'post_status' => ['post_status', 'status'],
        ];

        foreach ($mapped as $target => $aliases) {
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $params)) {
                    if ($target === 'post_content') {
                        $postarr[$target] = wp_kses_post((string) $params[$alias]);
                    } elseif ($target === 'post_excerpt') {
                        $postarr[$target] = sanitize_textarea_field((string) $params[$alias]);
                    } elseif ($target === 'post_name') {
                        $postarr[$target] = sanitize_title((string) $params[$alias]);
                    } elseif ($target === 'post_status') {
                        $postarr[$target] = sanitize_key((string) $params[$alias]);
                    } else {
                        $postarr[$target] = sanitize_text_field((string) $params[$alias]);
                    }
                    break;
                }
            }
        }

        $meta = isset($params['meta']) && is_array($params['meta']) ? $params['meta'] : [];
        [$meta, $write_policy_blocks] = self::partition_meta_by_policy($meta);
        $tax = isset($params['tax']) && is_array($params['tax']) ? $params['tax'] : [];

        if (!$dry_run && !empty($write_policy_blocks)) {
            return self::blocked_meta_response($write_policy_blocks);
        }

        if ($dry_run) {
            $after = $before;
            foreach (['post_title', 'post_content', 'post_excerpt', 'post_name', 'post_status'] as $field) {
                if (isset($postarr[$field])) {
                    $after[$field] = $postarr[$field];
                }
            }

            if (!empty($meta)) {
                foreach ($meta as $key => $value) {
                    $safe_key = sanitize_key((string) $key);
                    if ($value === null) {
                        unset($after['meta'][$safe_key]);
                    } else {
                        $after['meta'][$safe_key] = Utils::sanitize_mixed_for_storage($value);
                    }
                }
            }

            if (!empty($tax)) {
                foreach ($tax as $taxonomy => $terms) {
                    $safe_tax = sanitize_key((string) $taxonomy);
                    if (taxonomy_exists($safe_tax)) {
                        $after['taxonomies'][$safe_tax] = is_array($terms) ? array_values($terms) : [(string) $terms];
                    }
                }
            }

            $diff = Diff::compare($before, $after);
            Audit_Log::log_write([
                'actor_key_id' => Auth::current_key_id(),
                'scope' => 'write:content',
                'method' => $request->get_method(),
                'route' => $request->get_route(),
                'target_type' => 'post',
                'target_id' => (string) $id,
                'dry_run' => true,
                'before' => $before,
                'after' => $after,
                'diff' => $diff,
                'status_code' => 200,
            ]);

            return rest_ensure_response([
                'success' => true,
                'dry_run' => true,
                'write_policy_blocks' => $write_policy_blocks,
                'diff' => $diff,
                'data' => $after,
            ]);
        }

        if (count($postarr) > 1) {
            $updated = wp_update_post($postarr, true);
            if (is_wp_error($updated)) {
                return $updated;
            }
        }

        $write_mismatches = self::apply_meta_and_tax($id, $meta, $tax, true);

        $after_post = get_post($id);
        $after = $after_post ? self::prepare_post_detail($after_post) : ['id' => $id];
        $diff = Diff::compare($before, $after);

        if ($strict_write && !empty($write_mismatches)) {
            return new \WP_Error(
                'caa_write_integrity_failed',
                'One or more content writes were altered during persistence.',
                [
                    'status' => 409,
                    'post_id' => $id,
                    'mismatches' => $write_mismatches,
                    'data' => $after,
                ]
            );
        }

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'write:content',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'post',
            'target_id' => (string) $id,
            'dry_run' => false,
            'before' => $before,
            'after' => $after,
            'diff' => $diff,
            'status_code' => 200,
        ]);

        return rest_ensure_response([
            'success' => true,
            'write_policy_blocks' => [],
            'write_mismatches' => $write_mismatches,
            'diff' => $diff,
            'data' => $after,
        ]);
    }

    public static function delete_content(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $post = get_post($id);

        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $params = self::get_payload($request);
        $dry_run = self::is_dry_run($params);
        $force = Utils::truthy($params['force'] ?? $request->get_param('force'));

        $before = self::prepare_post_detail($post);

        if ($dry_run) {
            $after = ['deleted' => true, 'force' => $force];
            $diff = ['deleted' => ['from' => false, 'to' => true]];

            Audit_Log::log_write([
                'actor_key_id' => Auth::current_key_id(),
                'scope' => 'write:content',
                'method' => $request->get_method(),
                'route' => $request->get_route(),
                'target_type' => 'post',
                'target_id' => (string) $id,
                'dry_run' => true,
                'before' => $before,
                'after' => $after,
                'diff' => $diff,
                'status_code' => 200,
            ]);

            return rest_ensure_response([
                'success' => true,
                'dry_run' => true,
                'data' => $after,
            ]);
        }

        $deleted = wp_delete_post($id, $force);
        if (!$deleted) {
            return new \WP_Error('caa_delete_failed', 'Failed to delete post.', ['status' => 500]);
        }

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'write:content',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'post',
            'target_id' => (string) $id,
            'dry_run' => false,
            'before' => $before,
            'after' => ['deleted' => true, 'force' => $force],
            'diff' => ['deleted' => ['from' => false, 'to' => true]],
            'status_code' => 200,
        ]);

        return rest_ensure_response([
            'success' => true,
            'data' => ['id' => $id, 'deleted' => true, 'force' => $force],
        ]);
    }

    public static function rollback_content(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $post = get_post($id);
        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $params = self::get_payload($request);
        $dry_run = self::is_dry_run($params);
        $revision_id = isset($params['revision_id']) ? (int) $params['revision_id'] : (int) $request->get_param('revision_id');

        if ($revision_id <= 0) {
            return new \WP_Error('caa_missing_revision', 'revision_id is required.', ['status' => 400]);
        }

        $revision = get_post($revision_id);
        if (!$revision || $revision->post_type !== 'revision' || (int) $revision->post_parent !== $id) {
            return new \WP_Error('caa_invalid_revision', 'Invalid revision for this post.', ['status' => 400]);
        }

        $before = self::prepare_post_detail($post);

        if ($dry_run) {
            return rest_ensure_response([
                'success' => true,
                'dry_run' => true,
                'data' => [
                    'post_id' => $id,
                    'revision_id' => $revision_id,
                    'would_restore' => true,
                ],
            ]);
        }

        $restored = wp_restore_post_revision($revision_id);
        if (!$restored) {
            return new \WP_Error('caa_rollback_failed', 'Failed to restore revision.', ['status' => 500]);
        }

        $after = self::prepare_post_detail(get_post($id));
        $diff = Diff::compare($before, $after);

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'write:content',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'post',
            'target_id' => (string) $id,
            'dry_run' => false,
            'before' => $before,
            'after' => $after,
            'diff' => $diff,
            'status_code' => 200,
        ]);

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'post_id' => $id,
                'revision_id' => $revision_id,
                'restored' => true,
                'diff' => $diff,
            ],
        ]);
    }

    private static function apply_meta_and_tax(int $post_id, array $meta, array $tax, bool $verify = false): array
    {
        $mismatches = [];

        foreach ($meta as $key => $value) {
            $safe_key = sanitize_key((string) $key);
            if ($safe_key === '') {
                continue;
            }

            if ($value === null) {
                delete_post_meta($post_id, $safe_key);
                if ($verify && metadata_exists('post', $post_id, $safe_key)) {
                    $mismatches['meta'][$safe_key] = [
                        'expected' => null,
                        'actual' => get_post_meta($post_id, $safe_key, true),
                    ];
                }
                continue;
            }

            $expected = Utils::sanitize_mixed_for_storage($value);
            update_post_meta($post_id, $safe_key, $expected);

            if ($verify) {
                $actual = get_post_meta($post_id, $safe_key, true);
                if (!self::meta_values_equivalent($expected, $actual)) {
                    $mismatches['meta'][$safe_key] = [
                        'expected' => $expected,
                        'actual' => $actual,
                    ];
                }
            }
        }

        foreach ($tax as $taxonomy => $terms) {
            $safe_tax = sanitize_key((string) $taxonomy);
            if (!taxonomy_exists($safe_tax)) {
                continue;
            }

            if (!is_array($terms)) {
                $terms = [$terms];
            }

            $clean_terms = [];
            foreach ($terms as $term) {
                if (is_numeric($term)) {
                    $clean_terms[] = (int) $term;
                } else {
                    $clean_terms[] = sanitize_text_field((string) $term);
                }
            }

            $set_result = wp_set_object_terms($post_id, $clean_terms, $safe_tax, false);

            if (!$verify || is_wp_error($set_result)) {
                continue;
            }

            $actual_terms = wp_get_object_terms($post_id, $safe_tax, ['fields' => 'ids']);
            if (is_wp_error($actual_terms)) {
                continue;
            }

            $expected_ids = [];
            foreach ((array) $set_result as $term_id) {
                $expected_ids[] = (int) $term_id;
            }
            sort($expected_ids);

            $actual_ids = [];
            foreach ((array) $actual_terms as $term_id) {
                $actual_ids[] = (int) $term_id;
            }
            sort($actual_ids);

            if ($expected_ids !== $actual_ids) {
                $mismatches['tax'][$safe_tax] = [
                    'expected' => $expected_ids,
                    'actual' => $actual_ids,
                ];
            }
        }

        return $mismatches;
    }

    private static function meta_values_equivalent($expected, $actual): bool
    {
        return self::normalize_meta_value_for_compare($expected) == self::normalize_meta_value_for_compare($actual);
    }

    private static function partition_meta_by_policy(array $meta): array
    {
        $allowed = [];
        $blocked = [];

        foreach ($meta as $key => $value) {
            $safe_key = sanitize_key((string) $key);
            if ($safe_key === '') {
                continue;
            }

            $policy = self::get_meta_write_policy($safe_key);
            if ($policy !== null) {
                $blocked[$safe_key] = $policy;
                continue;
            }

            $allowed[$safe_key] = $value;
        }

        return [$allowed, $blocked];
    }

    private static function get_meta_write_policy(string $meta_key): ?array
    {
        if (isset(self::META_WRITE_DENYLIST[$meta_key])) {
            return self::META_WRITE_DENYLIST[$meta_key];
        }

        foreach (self::META_WRITE_PREFIX_DENYLIST as $prefix => $policy) {
            if ($prefix !== '' && strpos($meta_key, $prefix) === 0) {
                return $policy;
            }
        }

        return null;
    }

    private static function blocked_meta_response(array $blocked): \WP_Error
    {
        return new \WP_Error(
            'caa_write_policy_blocked',
            'One or more meta keys are restricted on /content. Use the dedicated route for those fields.',
            [
                'status' => 403,
                'blocked_meta' => $blocked,
            ]
        );
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

    private static function prepare_post_summary($post): array
    {
        return [
            'id' => (int) $post->ID,
            'post_type' => (string) $post->post_type,
            'post_status' => (string) $post->post_status,
            'post_title' => (string) $post->post_title,
            'post_name' => (string) $post->post_name,
            'post_date_gmt' => (string) $post->post_date_gmt,
            'post_modified_gmt' => (string) $post->post_modified_gmt,
            'author' => (int) $post->post_author,
            'link' => get_permalink($post),
        ];
    }

    private static function prepare_post_detail($post): array
    {
        $summary = self::prepare_post_summary($post);

        $summary['post_content'] = (string) $post->post_content;
        $summary['post_excerpt'] = (string) $post->post_excerpt;

        $meta = get_post_meta((int) $post->ID);
        $normalized_meta = [];
        foreach ((array) $meta as $key => $values) {
            if (!is_array($values)) {
                continue;
            }

            if (count($values) === 1) {
                $normalized_meta[$key] = maybe_unserialize($values[0]);
            } else {
                $normalized_meta[$key] = array_map('maybe_unserialize', $values);
            }
        }

        $summary['meta'] = $normalized_meta;

        $taxonomies = get_object_taxonomies($post->post_type, 'names');
        $term_map = [];
        foreach ($taxonomies as $taxonomy) {
            $term_map[$taxonomy] = wp_get_object_terms((int) $post->ID, $taxonomy, ['fields' => 'ids']);
        }
        $summary['taxonomies'] = $term_map;

        return $summary;
    }

    private static function get_payload(WP_REST_Request $request): array
    {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            $params = $request->get_params();
        }

        return is_array($params) ? $params : [];
    }

    private static function is_dry_run(array $params): bool
    {
        return Utils::truthy($params['dry_run'] ?? false);
    }
}

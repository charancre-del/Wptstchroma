<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Audit_Log;
use ChromaAgentAPI\Diff;
use ChromaAgentAPI\Snapshot_Store;
use ChromaAgentAPI\Utils;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class SEO_Routes
{
    private const NS = 'chroma-agent/v1';

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
        unset($updates['dry_run'], $updates['updates']);

        $allowlist = Utils::get_seo_meta_allowlist();
        $before = [];
        $after = [];
        $blocked = [];

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
                    delete_post_meta($post_id, $meta_key);
                }
                continue;
            }

            $new = Utils::sanitize_mixed_for_storage($value);
            $after[$meta_key] = $new;

            if (!$dry_run) {
                update_post_meta($post_id, $meta_key, $new);
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

        return rest_ensure_response([
            'success' => true,
            'dry_run' => $dry_run,
            'post_id' => $post_id,
            'blocked_keys' => $blocked,
            'diff' => $diff,
            'data' => $dry_run ? $after : $live,
        ]);
    }

    private static function payload(WP_REST_Request $request): array
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }
        return is_array($payload) ? $payload : [];
    }

    private static function read_options_by_keys(array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = get_option((string) $key, null);
        }
        return $data;
    }
}

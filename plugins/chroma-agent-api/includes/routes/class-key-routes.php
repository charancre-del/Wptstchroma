<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Audit_Log;
use ChromaAgentAPI\Key_Store;
use ChromaAgentAPI\Utils;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class Key_Routes
{
    private const NS = 'chroma-agent/v1';

    public static function register(): void
    {
        register_rest_route(self::NS, '/keys', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'list_keys'],
                'permission_callback' => [__CLASS__, 'admin_keys_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [__CLASS__, 'create_key'],
                'permission_callback' => [__CLASS__, 'admin_keys_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/keys/(?P<id>\d+)/revoke', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'revoke_key'],
            'permission_callback' => [__CLASS__, 'admin_keys_permission'],
        ]);

        register_rest_route(self::NS, '/keys/(?P<id>\d+)/rotate', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'rotate_key'],
            'permission_callback' => [__CLASS__, 'admin_keys_permission'],
        ]);
    }

    public static function admin_keys_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['admin:keys']);
    }

    public static function list_keys(WP_REST_Request $request)
    {
        $limit = (int) $request->get_param('limit');
        $offset = (int) $request->get_param('offset');

        if ($limit <= 0) {
            $limit = 100;
        }

        return rest_ensure_response([
            'success' => true,
            'data' => Key_Store::list_keys($limit, $offset),
        ]);
    }

    public static function create_key(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        if (!is_array($params)) {
            $params = $request->get_params();
        }

        $label = (string) ($params['label'] ?? 'Agent Key');
        $scopes = $params['scopes'] ?? [];

        if (is_string($scopes)) {
            $scopes = array_filter(array_map('trim', explode(',', $scopes)));
        }

        if (!is_array($scopes)) {
            $scopes = [];
        }

        $expires_at = isset($params['expires_at']) ? (string) $params['expires_at'] : null;
        $rate_limit = isset($params['rate_limit_per_min']) ? (int) $params['rate_limit_per_min'] : 120;
        $ip_allowlist = isset($params['ip_allowlist']) && is_array($params['ip_allowlist']) ? $params['ip_allowlist'] : [];

        $actor = Auth::current_key();
        $result = Key_Store::create_key(
            $label,
            $scopes,
            $expires_at,
            $rate_limit,
            (int) ($actor['id'] ?? 0),
            $ip_allowlist
        );

        if (is_wp_error($result)) {
            return $result;
        }

        Audit_Log::log_write([
            'actor_key_id' => (int) ($actor['id'] ?? 0),
            'scope' => 'admin:keys',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'api_key',
            'target_id' => (string) $result['id'],
            'dry_run' => false,
            'before' => null,
            'after' => [
                'id' => $result['id'],
                'label' => $result['label'],
                'scopes' => $result['scopes'],
                'expires_at' => $result['expires_at'],
            ],
            'diff' => ['created' => true],
            'status_code' => 201,
            'ip' => Utils::get_request_ip(),
        ]);

        return new \WP_REST_Response([
            'success' => true,
            'data' => $result,
            'warning' => 'Store this key now. It will not be shown again.',
        ], 201);
    }

    public static function revoke_key(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        if ($id <= 0) {
            return new \WP_Error('caa_invalid_id', 'Invalid key id.', ['status' => 400]);
        }

        $ok = Key_Store::revoke_key($id);
        if (!$ok) {
            return new \WP_Error('caa_revoke_failed', 'Unable to revoke key.', ['status' => 500]);
        }

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'admin:keys',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'api_key',
            'target_id' => (string) $id,
            'dry_run' => false,
            'before' => ['status' => 'active'],
            'after' => ['status' => 'revoked'],
            'diff' => ['status' => ['from' => 'active', 'to' => 'revoked']],
            'status_code' => 200,
            'ip' => Utils::get_request_ip(),
        ]);

        return rest_ensure_response(['success' => true, 'data' => ['id' => $id, 'status' => 'revoked']]);
    }

    public static function rotate_key(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        if ($id <= 0) {
            return new \WP_Error('caa_invalid_id', 'Invalid key id.', ['status' => 400]);
        }

        $result = Key_Store::rotate_key($id);
        if (is_wp_error($result)) {
            return $result;
        }

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'admin:keys',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'api_key',
            'target_id' => (string) $id,
            'dry_run' => false,
            'before' => ['rotated' => false],
            'after' => ['rotated' => true],
            'diff' => ['rotated' => ['from' => false, 'to' => true]],
            'status_code' => 200,
            'ip' => Utils::get_request_ip(),
        ]);

        return rest_ensure_response([
            'success' => true,
            'data' => $result,
            'warning' => 'Store this key now. It will not be shown again.',
        ]);
    }
}

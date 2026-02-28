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

class Audit_Routes
{
    private const NS = 'chroma-agent/v1';

    public static function register(): void
    {
        register_rest_route(self::NS, '/audit', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'list_audit_logs'],
            'permission_callback' => [__CLASS__, 'audit_permission'],
        ]);

        register_rest_route(self::NS, '/audit/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_audit_log'],
            'permission_callback' => [__CLASS__, 'audit_permission'],
        ]);

        register_rest_route(self::NS, '/snapshots', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'list_snapshots'],
            'permission_callback' => [__CLASS__, 'audit_permission'],
        ]);

        register_rest_route(self::NS, '/rollback/snapshot', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'rollback_snapshot'],
            'permission_callback' => [__CLASS__, 'rollback_permission'],
        ]);
    }

    public static function audit_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['admin:audit']);
    }

    public static function rollback_permission(WP_REST_Request $request)
    {
        // rollback mutates state; require both audit visibility and a write scope
        $audit = Auth::authorize($request, ['admin:audit']);
        if (is_wp_error($audit)) {
            return $audit;
        }

        $key = Auth::current_key();
        $scopes = is_array($key['scopes'] ?? null) ? $key['scopes'] : [];
        $write_allowed = in_array('write:theme', $scopes, true) || in_array('write:seo', $scopes, true);
        if (!$write_allowed) {
            return new \WP_Error('caa_scope_denied', 'Rollback requires write:theme or write:seo scope.', ['status' => 403]);
        }

        return true;
    }

    public static function list_audit_logs(WP_REST_Request $request)
    {
        $limit = (int) $request->get_param('limit');
        $offset = (int) $request->get_param('offset');

        if ($limit <= 0) {
            $limit = 50;
        }

        $filters = [
            'route' => $request->get_param('route'),
            'target_type' => $request->get_param('target_type'),
            'actor_key_id' => $request->get_param('actor_key_id'),
        ];

        return rest_ensure_response([
            'success' => true,
            'data' => Audit_Log::list_logs($limit, $offset, $filters),
        ]);
    }

    public static function get_audit_log(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $log = Audit_Log::get_log($id);

        if (!$log) {
            return new \WP_Error('caa_audit_not_found', 'Audit log not found.', ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $log,
        ]);
    }

    public static function list_snapshots(WP_REST_Request $request)
    {
        $limit = (int) $request->get_param('limit');
        $offset = (int) $request->get_param('offset');

        if ($limit <= 0) {
            $limit = 50;
        }

        return rest_ensure_response([
            'success' => true,
            'data' => Snapshot_Store::list_snapshots($limit, $offset),
        ]);
    }

    public static function rollback_snapshot(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $dry_run = Utils::truthy($payload['dry_run'] ?? false);
        $snapshot_id = isset($payload['snapshot_id']) ? (int) $payload['snapshot_id'] : 0;

        if ($snapshot_id <= 0) {
            return new \WP_Error('caa_snapshot_required', 'snapshot_id is required.', ['status' => 400]);
        }

        $snapshot = Snapshot_Store::get_snapshot($snapshot_id);
        if (!$snapshot) {
            return new \WP_Error('caa_snapshot_not_found', 'Snapshot not found.', ['status' => 404]);
        }

        $before = [
            'target_type' => $snapshot['target_type'],
            'target_key' => $snapshot['target_key'],
            'current_value' => null,
        ];

        $target_type = (string) $snapshot['target_type'];
        $target_key = (string) $snapshot['target_key'];

        if ($target_type === 'option') {
            $before['current_value'] = get_option($target_key, null);
        } elseif ($target_type === 'theme_mod') {
            $before['current_value'] = get_theme_mod($target_key, null);
        }

        $after = [
            'target_type' => $target_type,
            'target_key' => $target_key,
            'restored_value' => $snapshot['old_value'],
        ];

        if (!$dry_run) {
            $restored = Snapshot_Store::restore_snapshot($snapshot_id);
            if (is_wp_error($restored)) {
                return $restored;
            }
        }

        $diff = Diff::compare($before, $after);

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'admin:audit',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'rollback_snapshot',
            'target_id' => (string) $snapshot_id,
            'dry_run' => $dry_run,
            'before' => $before,
            'after' => $after,
            'diff' => $diff,
            'status_code' => 200,
        ]);

        return rest_ensure_response([
            'success' => true,
            'dry_run' => $dry_run,
            'data' => [
                'snapshot_id' => $snapshot_id,
                'restored' => !$dry_run,
                'target_type' => $target_type,
                'target_key' => $target_key,
            ],
        ]);
    }
}

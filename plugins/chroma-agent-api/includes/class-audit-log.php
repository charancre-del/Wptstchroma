<?php

namespace ChromaAgentAPI;

if (!defined('ABSPATH')) {
    exit;
}

class Audit_Log
{
    private static $sensitive_keys = [
        'password',
        'token',
        'secret',
        'authorization',
        'api_key',
        'key_hash',
    ];

    public static function log_write(array $record): int
    {
        global $wpdb;
        $table = Utils::table('audit_log');

        $before = self::sanitize_for_log($record['before'] ?? null);
        $after = self::sanitize_for_log($record['after'] ?? null);
        $diff = self::sanitize_for_log($record['diff'] ?? null);

        $wpdb->insert(
            $table,
            [
                'request_id' => sanitize_text_field((string) ($record['request_id'] ?? wp_generate_uuid4())),
                'actor_key_id' => (int) ($record['actor_key_id'] ?? 0),
                'scope' => sanitize_text_field((string) ($record['scope'] ?? '')),
                'method' => sanitize_text_field((string) ($record['method'] ?? 'GET')),
                'route' => sanitize_text_field((string) ($record['route'] ?? '')),
                'target_type' => sanitize_key((string) ($record['target_type'] ?? 'unknown')),
                'target_id' => sanitize_text_field((string) ($record['target_id'] ?? '')),
                'dry_run' => !empty($record['dry_run']) ? 1 : 0,
                'before_json' => wp_json_encode($before),
                'after_json' => wp_json_encode($after),
                'diff_json' => wp_json_encode($diff),
                'status_code' => (int) ($record['status_code'] ?? 200),
                'error_code' => sanitize_text_field((string) ($record['error_code'] ?? '')),
                'ip' => sanitize_text_field((string) ($record['ip'] ?? Utils::get_request_ip())),
                'user_agent' => sanitize_text_field((string) ($record['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''))),
                'created_at' => current_time('mysql', true),
            ],
            ['%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public static function get_log(int $id): ?array
    {
        global $wpdb;
        $table = Utils::table('audit_log');

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        if (!$row) {
            return null;
        }

        $row['before'] = self::decode_json((string) $row['before_json']);
        $row['after'] = self::decode_json((string) $row['after_json']);
        $row['diff'] = self::decode_json((string) $row['diff_json']);

        return $row;
    }

    public static function list_logs(int $limit = 50, int $offset = 0, array $filters = []): array
    {
        global $wpdb;

        $table = Utils::table('audit_log');
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        $where = ['1=1'];
        $args = [];

        if (!empty($filters['route'])) {
            $where[] = 'route LIKE %s';
            $args[] = '%' . $wpdb->esc_like((string) $filters['route']) . '%';
        }

        if (!empty($filters['target_type'])) {
            $where[] = 'target_type = %s';
            $args[] = sanitize_key((string) $filters['target_type']);
        }

        if (!empty($filters['actor_key_id'])) {
            $where[] = 'actor_key_id = %d';
            $args[] = (int) $filters['actor_key_id'];
        }

        $sql = "SELECT id, request_id, actor_key_id, scope, method, route, target_type, target_id, dry_run, status_code, error_code, created_at
                FROM {$table}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY id DESC
                LIMIT %d OFFSET %d";

        $args[] = $limit;
        $args[] = $offset;

        $rows = $wpdb->get_results($wpdb->prepare($sql, $args), ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public static function sanitize_for_log($data)
    {
        if (is_array($data)) {
            $out = [];
            foreach ($data as $key => $value) {
                $normalized_key = is_string($key) ? strtolower($key) : '';
                if ($normalized_key !== '' && in_array($normalized_key, self::$sensitive_keys, true)) {
                    $out[$key] = '[REDACTED]';
                    continue;
                }
                $out[$key] = self::sanitize_for_log($value);
            }
            return $out;
        }

        if (is_object($data)) {
            return self::sanitize_for_log((array) $data);
        }

        return $data;
    }

    private static function decode_json(string $value)
    {
        if ($value === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
    }
}

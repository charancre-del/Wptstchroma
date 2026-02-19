<?php

namespace ChromaAgentAPI;

if (!defined('ABSPATH')) {
    exit;
}

class Snapshot_Store
{
    public static function create_snapshot(
        int $actor_key_id,
        string $scope,
        string $target_type,
        string $target_key,
        $old_value,
        $new_value
    ): int {
        global $wpdb;

        $table = Utils::table('option_snapshots');
        $wpdb->insert(
            $table,
            [
                'actor_key_id' => $actor_key_id,
                'scope' => sanitize_text_field($scope),
                'target_type' => sanitize_key($target_type),
                'target_key' => sanitize_text_field($target_key),
                'old_value_json' => wp_json_encode($old_value),
                'new_value_json' => wp_json_encode($new_value),
                'created_at' => current_time('mysql', true),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public static function get_snapshot(int $id): ?array
    {
        global $wpdb;
        $table = Utils::table('option_snapshots');

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        $row['old_value'] = json_decode((string) $row['old_value_json'], true);
        $row['new_value'] = json_decode((string) $row['new_value_json'], true);
        return $row;
    }

    public static function restore_snapshot(int $id)
    {
        $snapshot = self::get_snapshot($id);
        if (!$snapshot) {
            return new \WP_Error('caa_snapshot_not_found', 'Snapshot not found.', ['status' => 404]);
        }

        $target_type = (string) $snapshot['target_type'];
        $target_key = (string) $snapshot['target_key'];
        $old_value = $snapshot['old_value'];

        if ($target_type === 'option') {
            update_option($target_key, $old_value, false);
            return true;
        }

        if ($target_type === 'theme_mod') {
            set_theme_mod($target_key, $old_value);
            return true;
        }

        return new \WP_Error('caa_snapshot_invalid_type', 'Unsupported snapshot target type.', ['status' => 400]);
    }

    public static function list_snapshots(int $limit = 50, int $offset = 0): array
    {
        global $wpdb;
        $table = Utils::table('option_snapshots');

        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, actor_key_id, scope, target_type, target_key, created_at
                 FROM {$table}
                 ORDER BY id DESC
                 LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }
}

<?php

namespace ChromaAgentAPI;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

class Key_Store
{
    public static function create_key(
        string $label,
        array $scopes,
        ?string $expires_at,
        int $rate_limit_per_min,
        int $created_by = 0,
        array $ip_allowlist = []
    ) {
        global $wpdb;

        $table = Utils::table('keys');
        $label = sanitize_text_field($label);
        $scopes = Utils::normalize_scopes($scopes);

        if ($label === '') {
            return new WP_Error('caa_invalid_label', 'Key label is required.', ['status' => 400]);
        }

        if (empty($scopes)) {
            return new WP_Error('caa_invalid_scopes', 'At least one scope is required.', ['status' => 400]);
        }

        $rate_limit_per_min = max(1, min(10000, $rate_limit_per_min));
        $expires_mysql = self::normalize_expiration($expires_at);

        $inserted = $wpdb->insert(
            $table,
            [
                'label' => $label,
                'key_hash' => 'pending',
                'key_prefix' => 'pending',
                'scopes_json' => wp_json_encode($scopes),
                'ip_allowlist_json' => wp_json_encode(array_values($ip_allowlist)),
                'status' => 'active',
                'rate_limit_per_min' => $rate_limit_per_min,
                'expires_at' => $expires_mysql,
                'created_by' => $created_by,
                'created_at' => current_time('mysql', true),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s']
        );

        if (!$inserted) {
            return new WP_Error('caa_key_create_failed', 'Failed to create API key record.', ['status' => 500]);
        }

        $id = (int) $wpdb->insert_id;
        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $raw = 'ck_live_' . $id . '.' . $secret;

        $hash = password_hash($raw, PASSWORD_DEFAULT);
        $prefix = substr($raw, 0, 18);

        $wpdb->update(
            $table,
            [
                'key_hash' => $hash,
                'key_prefix' => $prefix,
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        return [
            'id' => $id,
            'label' => $label,
            'key' => $raw,
            'key_prefix' => $prefix,
            'scopes' => $scopes,
            'expires_at' => $expires_mysql,
            'rate_limit_per_min' => $rate_limit_per_min,
            'created_at' => current_time('mysql', true),
        ];
    }

    public static function verify_key(string $raw_key)
    {
        global $wpdb;

        $raw_key = trim($raw_key);
        if (!preg_match('/^ck_(live|test)_([0-9]+)\.([A-Za-z0-9\-_]+)$/', $raw_key, $m)) {
            return new WP_Error('caa_invalid_key_format', 'Invalid API key format.', ['status' => 401]);
        }

        $id = (int) $m[2];
        $table = Utils::table('keys');

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            ARRAY_A
        );

        if (!$row) {
            return new WP_Error('caa_key_not_found', 'API key not found.', ['status' => 401]);
        }

        if (($row['status'] ?? '') !== 'active') {
            return new WP_Error('caa_key_revoked', 'API key is not active.', ['status' => 401]);
        }

        if (!empty($row['expires_at']) && strtotime((string) $row['expires_at']) < time()) {
            return new WP_Error('caa_key_expired', 'API key has expired.', ['status' => 401]);
        }

        $hash = (string) ($row['key_hash'] ?? '');
        if ($hash === '' || !password_verify($raw_key, $hash)) {
            return new WP_Error('caa_key_invalid', 'Invalid API key.', ['status' => 401]);
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $wpdb->update(
                $table,
                ['key_hash' => password_hash($raw_key, PASSWORD_DEFAULT)],
                ['id' => $id],
                ['%s'],
                ['%d']
            );
        }

        $ip_allowlist = [];
        if (!empty($row['ip_allowlist_json'])) {
            $decoded = json_decode((string) $row['ip_allowlist_json'], true);
            if (is_array($decoded)) {
                $ip_allowlist = $decoded;
            }
        }

        $ip = Utils::get_request_ip();
        if (!empty($ip_allowlist) && !in_array($ip, $ip_allowlist, true)) {
            return new WP_Error('caa_ip_denied', 'Request IP is not allowed for this key.', ['status' => 403]);
        }

        $scopes = [];
        $decoded_scopes = json_decode((string) ($row['scopes_json'] ?? '[]'), true);
        if (is_array($decoded_scopes)) {
            $scopes = Utils::normalize_scopes($decoded_scopes);
        }

        self::touch_last_used($id, $ip);

        $row['id'] = $id;
        $row['scopes'] = $scopes;
        $row['rate_limit_per_min'] = (int) ($row['rate_limit_per_min'] ?? 120);

        return $row;
    }

    public static function list_keys(int $limit = 100, int $offset = 0): array
    {
        global $wpdb;

        $table = Utils::table('keys');
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, label, key_prefix, scopes_json, status, rate_limit_per_min, expires_at, created_by, created_at, last_used_at, last_used_ip, revoked_at
                 FROM {$table}
                 ORDER BY id DESC
                 LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );

        if (!is_array($rows)) {
            return [];
        }

        foreach ($rows as &$row) {
            $decoded = json_decode((string) ($row['scopes_json'] ?? '[]'), true);
            $row['scopes'] = is_array($decoded) ? $decoded : [];
            unset($row['scopes_json']);
        }

        return $rows;
    }

    public static function revoke_key(int $id): bool
    {
        global $wpdb;
        $table = Utils::table('keys');

        $updated = $wpdb->update(
            $table,
            [
                'status' => 'revoked',
                'revoked_at' => current_time('mysql', true),
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        return $updated !== false;
    }

    public static function rotate_key(int $id)
    {
        global $wpdb;

        $table = Utils::table('keys');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);

        if (!$row) {
            return new WP_Error('caa_key_not_found', 'API key not found.', ['status' => 404]);
        }

        if (($row['status'] ?? '') !== 'active') {
            return new WP_Error('caa_key_not_active', 'Only active keys can be rotated.', ['status' => 400]);
        }

        $secret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $raw = 'ck_live_' . $id . '.' . $secret;
        $hash = password_hash($raw, PASSWORD_DEFAULT);
        $prefix = substr($raw, 0, 18);

        $updated = $wpdb->update(
            $table,
            [
                'key_hash' => $hash,
                'key_prefix' => $prefix,
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        if ($updated === false) {
            return new WP_Error('caa_rotate_failed', 'Unable to rotate API key.', ['status' => 500]);
        }

        return [
            'id' => $id,
            'key' => $raw,
            'key_prefix' => $prefix,
        ];
    }

    private static function normalize_expiration(?string $expires_at): ?string
    {
        if ($expires_at === null || trim($expires_at) === '') {
            return null;
        }

        $ts = strtotime($expires_at);
        if ($ts === false) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $ts);
    }

    private static function touch_last_used(int $id, string $ip): void
    {
        global $wpdb;

        $cache_key = 'caa_touch_' . $id;
        if (get_transient($cache_key)) {
            return;
        }

        set_transient($cache_key, 1, 60);

        $table = Utils::table('keys');
        $wpdb->update(
            $table,
            [
                'last_used_at' => current_time('mysql', true),
                'last_used_ip' => $ip,
            ],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
    }
}

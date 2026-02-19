<?php

namespace ChromaAgentAPI;

if (!defined('ABSPATH')) {
    exit;
}

class CLI
{
    public static function register(): void
    {
        if (!class_exists('WP_CLI')) {
            return;
        }

        \WP_CLI::add_command('chroma-agent key create', [__CLASS__, 'create_key']);
        \WP_CLI::add_command('chroma-agent key revoke', [__CLASS__, 'revoke_key']);
        \WP_CLI::add_command('chroma-agent key rotate', [__CLASS__, 'rotate_key']);
        \WP_CLI::add_command('chroma-agent key list', [__CLASS__, 'list_keys']);
    }

    public static function create_key(array $args, array $assoc_args): void
    {
        $label = isset($assoc_args['label']) ? (string) $assoc_args['label'] : 'Agent Key';
        $scope_arg = isset($assoc_args['scopes']) ? (string) $assoc_args['scopes'] : 'read:content';
        $scopes = array_filter(array_map('trim', explode(',', $scope_arg)));

        $expires = isset($assoc_args['expires']) ? (string) $assoc_args['expires'] : null;
        $rate_limit = isset($assoc_args['rate']) ? (int) $assoc_args['rate'] : 120;

        $result = Key_Store::create_key($label, $scopes, $expires, $rate_limit, 0, []);
        if (is_wp_error($result)) {
            \WP_CLI::error($result->get_error_message());
            return;
        }

        \WP_CLI::success('API key created: ' . $result['id']);
        \WP_CLI::line('KEY (shown once): ' . $result['key']);
    }

    public static function revoke_key(array $args): void
    {
        $id = isset($args[0]) ? (int) $args[0] : 0;
        if ($id <= 0) {
            \WP_CLI::error('Usage: wp chroma-agent key revoke <id>');
            return;
        }

        if (!Key_Store::revoke_key($id)) {
            \WP_CLI::error('Unable to revoke key.');
            return;
        }

        \WP_CLI::success('Key revoked: ' . $id);
    }

    public static function rotate_key(array $args): void
    {
        $id = isset($args[0]) ? (int) $args[0] : 0;
        if ($id <= 0) {
            \WP_CLI::error('Usage: wp chroma-agent key rotate <id>');
            return;
        }

        $result = Key_Store::rotate_key($id);
        if (is_wp_error($result)) {
            \WP_CLI::error($result->get_error_message());
            return;
        }

        \WP_CLI::success('Key rotated: ' . $id);
        \WP_CLI::line('NEW KEY (shown once): ' . $result['key']);
    }

    public static function list_keys(): void
    {
        $keys = Key_Store::list_keys(200, 0);

        if (empty($keys)) {
            \WP_CLI::line('No keys found.');
            return;
        }

        \WP_CLI\Utils\format_items('table', $keys, [
            'id',
            'label',
            'key_prefix',
            'status',
            'rate_limit_per_min',
            'expires_at',
            'last_used_at',
            'last_used_ip',
        ]);
    }
}

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
        \WP_CLI::add_command('chroma-agent geo validate', [__CLASS__, 'validate_geo_feed']);
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

    public static function validate_geo_feed(array $args, array $assoc_args): void
    {
        $request = new \WP_REST_Request('GET', '/wp-json/chroma-agent/v1/geo-feed');
        if (!empty($assoc_args['refresh'])) {
            $request->set_param('refresh', 1);
        }

        $response = Routes\Geo_Routes::get_geo_feed($request);
        $payload = is_object($response) && method_exists($response, 'get_data')
            ? (array) $response->get_data()
            : (array) $response;

        $errors = [];
        $required_top_level = [
            'success',
            'cached',
            'contract_version',
            'generated_at_gmt',
            'source',
            'summary',
            'brand',
            'curriculum',
            'locations',
            'programs',
        ];

        foreach ($required_top_level as $key) {
            if (!array_key_exists($key, $payload)) {
                $errors[] = 'Missing top-level key: ' . $key;
            }
        }

        $locations = is_array($payload['locations'] ?? null) ? $payload['locations'] : [];
        $programs = is_array($payload['programs'] ?? null) ? $payload['programs'] : [];
        $location_ids = [];
        foreach ($locations as $location) {
            $location_id = isset($location['id']) && is_numeric($location['id']) ? (int) $location['id'] : 0;
            if ($location_id > 0) {
                $location_ids[$location_id] = true;
            }
        }

        $program_ids = [];
        foreach ($programs as $program) {
            $program_id = isset($program['id']) && is_numeric($program['id']) ? (int) $program['id'] : 0;
            if ($program_id > 0) {
                $program_ids[$program_id] = true;
            }

            foreach ((array) ($program['locations_served'] ?? []) as $location_ref) {
                $location_id = isset($location_ref['id']) && is_numeric($location_ref['id']) ? (int) $location_ref['id'] : 0;
                if ($location_id > 0 && !isset($location_ids[$location_id])) {
                    $errors[] = sprintf('Program %d references missing location %d', $program_id, $location_id);
                }
            }
        }

        foreach ($locations as $location) {
            $location_id = isset($location['id']) && is_numeric($location['id']) ? (int) $location['id'] : 0;
            foreach ((array) ($location['programs_offered'] ?? []) as $program_ref) {
                $program_id = isset($program_ref['id']) && is_numeric($program_ref['id']) ? (int) $program_ref['id'] : 0;
                if ($program_id > 0 && !isset($program_ids[$program_id])) {
                    $errors[] = sprintf('Location %d references missing program %d', $location_id, $program_id);
                }
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                \WP_CLI::warning($error);
            }
            \WP_CLI::error('Geo feed validation failed with ' . count($errors) . ' error(s).');
            return;
        }

        $quality = isset($payload['data_quality_score']) ? (string) $payload['data_quality_score'] : 'n/a';
        \WP_CLI::success('Geo feed validation passed. Data quality score: ' . $quality);
    }
}

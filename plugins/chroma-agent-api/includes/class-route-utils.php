<?php

namespace ChromaAgentAPI;

use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class Route_Utils
{
    public static function payload(WP_REST_Request $request): array
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }
        return is_array($payload) ? $payload : [];
    }

    public static function updates_from_request(WP_REST_Request $request): array
    {
        $payload = self::payload($request);
        $updates = isset($payload['updates']) && is_array($payload['updates']) ? $payload['updates'] : $payload;
        unset($updates['dry_run'], $updates['strict_write'], $updates['updates']);
        return is_array($updates) ? $updates : [];
    }

    public static function dry_run(array $payload): bool
    {
        return Utils::truthy($payload['dry_run'] ?? false);
    }

    public static function strict_write(array $payload): bool
    {
        return Utils::truthy($payload['strict_write'] ?? false);
    }

    public static function allowed_by_policy(string $key, array $allowed_keys, array $allowed_prefixes = []): bool
    {
        if (in_array($key, $allowed_keys, true)) {
            return true;
        }

        foreach ($allowed_prefixes as $prefix) {
            if ($prefix !== '' && strpos($key, (string) $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    public static function partition_updates(array $updates, array $allowed_keys, array $allowed_prefixes = []): array
    {
        $allowed = [];
        $blocked = [];

        foreach ($updates as $key => $value) {
            $normalized = (string) $key;
            if ($normalized === '') {
                continue;
            }

            if (self::allowed_by_policy($normalized, $allowed_keys, $allowed_prefixes)) {
                $allowed[$normalized] = $value;
            } else {
                $blocked[] = $normalized;
            }
        }

        return [$allowed, $blocked];
    }

    public static function read_options(array $keys, bool $mask_secrets = true): array
    {
        $data = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            $value = get_option($key, null);
            $data[$key] = $mask_secrets ? self::mask_secret_if_needed($key, $value) : $value;
        }
        return $data;
    }

    public static function read_theme_mods(array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            $data[$key] = get_theme_mod($key, null);
        }
        return $data;
    }

    public static function read_post_meta_values(int $post_id, array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            $data[$key] = get_post_meta($post_id, $key, true);
        }
        return $data;
    }

    public static function read_term_meta_values(int $term_id, array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            $data[$key] = get_term_meta($term_id, $key, true);
        }
        return $data;
    }

    public static function write_options(
        WP_REST_Request $request,
        array $updates,
        array $allowed_keys,
        string $scope,
        string $target_type,
        bool $mask_secrets = true
    ) {
        $payload = self::payload($request);
        $dry_run = self::dry_run($payload);
        [$updates, $blocked] = self::partition_updates($updates, $allowed_keys);

        $before = [];
        $after = [];
        $snapshot_ids = [];

        foreach ($updates as $key => $value) {
            $old = get_option($key, null);
            $new = self::sanitize_value_for_storage($key, $value);
            $before[$key] = $mask_secrets ? self::mask_secret_if_needed($key, $old) : $old;
            $after[$key] = $mask_secrets ? self::mask_secret_if_needed($key, $new) : $new;

            if (!$dry_run && $old !== $new) {
                $snapshot_ids[] = Snapshot_Store::create_snapshot(
                    Auth::current_key_id(),
                    $scope,
                    'option',
                    $key,
                    $old,
                    $new
                );
                update_option($key, $new, false);
            }
        }

        $diff = Diff::compare($before, $after);
        self::log_write($request, $scope, $target_type, 'batch', $dry_run, $before, $after, $diff);

        return rest_ensure_response([
            'success' => true,
            'dry_run' => $dry_run,
            'blocked_keys' => $blocked,
            'snapshot_ids' => $snapshot_ids,
            'diff' => $diff,
            'data' => $dry_run ? $after : self::read_options(array_keys($after), $mask_secrets),
        ]);
    }

    public static function write_theme_mods(
        WP_REST_Request $request,
        array $updates,
        array $allowed_keys,
        string $scope,
        string $target_type
    ) {
        $payload = self::payload($request);
        $dry_run = self::dry_run($payload);
        [$updates, $blocked] = self::partition_updates($updates, $allowed_keys);

        $before = [];
        $after = [];
        $snapshot_ids = [];

        foreach ($updates as $key => $value) {
            $old = get_theme_mod($key, null);
            $new = self::sanitize_value_for_storage($key, $value);
            $before[$key] = $old;
            $after[$key] = $new;

            if (!$dry_run && $old !== $new) {
                $snapshot_ids[] = Snapshot_Store::create_snapshot(
                    Auth::current_key_id(),
                    $scope,
                    'theme_mod',
                    $key,
                    $old,
                    $new
                );
                set_theme_mod($key, $new);
            }
        }

        $diff = Diff::compare($before, $after);
        self::log_write($request, $scope, $target_type, 'batch', $dry_run, $before, $after, $diff);

        return rest_ensure_response([
            'success' => true,
            'dry_run' => $dry_run,
            'blocked_keys' => $blocked,
            'snapshot_ids' => $snapshot_ids,
            'diff' => $diff,
            'data' => $dry_run ? $after : self::read_theme_mods(array_keys($after)),
        ]);
    }

    public static function write_post_meta(
        WP_REST_Request $request,
        int $post_id,
        array $updates,
        array $allowed_keys,
        array $allowed_prefixes,
        string $scope,
        string $target_type
    ) {
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('caa_post_not_found', 'Post not found.', ['status' => 404]);
        }

        $payload = self::payload($request);
        $dry_run = self::dry_run($payload);
        $strict_write = self::strict_write($payload);
        [$updates, $blocked] = self::partition_updates($updates, $allowed_keys, $allowed_prefixes);

        $before = [];
        $after = [];
        $snapshot_ids = [];
        $write_mismatches = [];

        foreach ($updates as $key => $value) {
            $old = get_post_meta($post_id, $key, true);
            $before[$key] = $old;

            if ($value === null) {
                $after[$key] = null;
                if (!$dry_run) {
                    $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), $scope, 'post_meta', $post_id . ':' . $key, $old, null);
                    delete_post_meta($post_id, $key);
                    if (metadata_exists('post', $post_id, $key)) {
                        $write_mismatches[$key] = ['expected' => null, 'actual' => get_post_meta($post_id, $key, true)];
                    }
                }
                continue;
            }

            $new = self::sanitize_value_for_storage($key, $value);
            $after[$key] = $new;

            if (!$dry_run) {
                $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), $scope, 'post_meta', $post_id . ':' . $key, $old, $new);
                update_post_meta($post_id, $key, $new);
                $saved = get_post_meta($post_id, $key, true);
                if (!self::values_equivalent($new, $saved)) {
                    $write_mismatches[$key] = ['expected' => $new, 'actual' => $saved];
                }
            }
        }

        $diff = Diff::compare($before, $after);
        self::log_write($request, $scope, $target_type, (string) $post_id, $dry_run, $before, $after, $diff);

        if (!$dry_run && $strict_write && !empty($write_mismatches)) {
            return new \WP_Error(
                'caa_write_integrity_failed',
                'One or more meta writes were altered during persistence.',
                [
                    'status' => 409,
                    'post_id' => $post_id,
                    'mismatches' => $write_mismatches,
                    'data' => self::read_post_meta_values($post_id, array_keys($after)),
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
            'data' => $dry_run ? $after : self::read_post_meta_values($post_id, array_keys($after)),
        ]);
    }

    public static function write_term_meta(
        WP_REST_Request $request,
        int $term_id,
        array $updates,
        array $allowed_keys,
        string $scope,
        string $target_type
    ) {
        $term = get_term($term_id);
        if (!$term || is_wp_error($term)) {
            return new \WP_Error('caa_term_not_found', 'Term not found.', ['status' => 404]);
        }

        $payload = self::payload($request);
        $dry_run = self::dry_run($payload);
        [$updates, $blocked] = self::partition_updates($updates, $allowed_keys);

        $before = [];
        $after = [];
        $snapshot_ids = [];

        foreach ($updates as $key => $value) {
            $old = get_term_meta($term_id, $key, true);
            $new = $value === null ? null : self::sanitize_value_for_storage($key, $value);
            $before[$key] = $old;
            $after[$key] = $new;

            if (!$dry_run) {
                $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), $scope, 'term_meta', $term_id . ':' . $key, $old, $new);
                if ($value === null) {
                    delete_term_meta($term_id, $key);
                } else {
                    update_term_meta($term_id, $key, $new);
                }
            }
        }

        $diff = Diff::compare($before, $after);
        self::log_write($request, $scope, $target_type, (string) $term_id, $dry_run, $before, $after, $diff);

        return rest_ensure_response([
            'success' => true,
            'dry_run' => $dry_run,
            'term_id' => $term_id,
            'blocked_keys' => $blocked,
            'snapshot_ids' => $snapshot_ids,
            'diff' => $diff,
            'data' => $dry_run ? $after : self::read_term_meta_values($term_id, array_keys($after)),
        ]);
    }

    public static function sanitize_value_for_storage(string $key, $value)
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (self::is_schema_document_key($key)) {
            return self::sanitize_schema_document_for_storage($key, $value);
        }

        if (self::is_global_script_theme_mod($key)) {
            return self::sanitize_global_script_markup((string) $value);
        }

        if (substr($key, -5) === '_json') {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return wp_json_encode(Utils::sanitize_mixed_for_storage($decoded));
                }
                return wp_kses_post($value);
            }
            if (is_array($value) || is_object($value)) {
                return wp_json_encode(Utils::sanitize_mixed_for_storage($value));
            }
        }

        if (is_array($value) || is_object($value)) {
            return Utils::sanitize_mixed_for_storage_preserve_keys($value);
        }

        if (strpos($key, 'email') !== false) {
            return sanitize_email((string) $value);
        }

        if ($key === 'chroma_llm_base_url') {
            $url = rtrim(esc_url_raw((string) $value, ['http', 'https']), '/');
            if ($url === '') {
                return '';
            }
            if (function_exists('chroma_seo_validate_remote_url')) {
                return chroma_seo_validate_remote_url($url, true) ?: '';
            }
            return wp_http_validate_url($url) ? $url : '';
        }

        if (strpos($key, 'url') !== false || strpos($key, 'link') !== false || strpos($key, 'image') !== false || strpos($key, 'photo') !== false || strpos($key, 'webhook') !== false) {
            return esc_url_raw((string) $value);
        }

        if (strpos($key, 'content') !== false || strpos($key, 'description') !== false || strpos($key, 'body') !== false || strpos($key, 'embed') !== false || strpos($key, 'script') !== false || strpos($key, 'schema') !== false) {
            return wp_kses_post((string) $value);
        }

        return sanitize_text_field((string) $value);
    }

    private static function is_global_script_theme_mod(string $key): bool
    {
        return in_array($key, [
            'chroma_header_scripts',
            'chroma_footer_scripts',
        ], true);
    }

    private static function sanitize_global_script_markup(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(["\0", '<?', '?>'], ['', '&lt;?', '?&gt;'], $value);

        if (function_exists('chroma_strip_disallowed_customizer_markup')) {
            $value = chroma_strip_disallowed_customizer_markup($value);
        }

        if (
            stripos($value, '<script') === false
            && stripos($value, '<noscript') === false
            && stripos($value, '<') === false
        ) {
            return "<script>\n" . $value . "\n</script>";
        }

        return $value;
    }

    private static function is_schema_document_key(string $key): bool
    {
        return in_array($key, [
            '_chroma_post_schemas',
            '_chroma_schema_data',
            '_chroma_schema_override',
        ], true);
    }

    private static function sanitize_schema_document_for_storage(string $key, $value)
    {
        if (is_string($value)) {
            $decoded = json_decode(trim($value), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $clean = self::strip_schema_internal_keys(Utils::sanitize_mixed_for_storage_preserve_keys($decoded));
                if ($key === '_chroma_schema_override') {
                    return wp_json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }

                return self::normalize_schema_document_shape($key, $clean);
            }

            return wp_kses_post($value);
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            $clean = self::strip_schema_internal_keys(Utils::sanitize_mixed_for_storage_preserve_keys($value));
            if ($key === '_chroma_schema_override') {
                return wp_json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            return self::normalize_schema_document_shape($key, $clean);
        }

        return $value;
    }

    private static function normalize_schema_document_shape(string $key, array $value): array
    {
        if ($key !== '_chroma_post_schemas') {
            return $value;
        }

        if (self::array_is_list($value)) {
            return $value;
        }

        if (isset($value['@type']) || isset($value['type']) || isset($value['@graph'])) {
            return [$value];
        }

        return $value;
    }

    private static function strip_schema_internal_keys($value)
    {
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        $clean = [];
        foreach ($value as $key => $item) {
            if (is_string($key) && $key !== '' && $key[0] === '_') {
                continue;
            }

            $clean[$key] = self::strip_schema_internal_keys($item);
        }

        return $clean;
    }

    private static function array_is_list(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    public static function mask_secret_if_needed(string $key, $value)
    {
        if (!in_array($key, Field_Registry::secret_option_keys(), true)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return $value;
        }

        $string = (string) $value;
        $suffix = strlen($string) > 4 ? substr($string, -4) : '';
        return $suffix !== '' ? '***' . $suffix : '***';
    }

    public static function values_equivalent($expected, $actual): bool
    {
        return self::normalize_for_compare($expected) == self::normalize_for_compare($actual);
    }

    public static function log_write(
        WP_REST_Request $request,
        string $scope,
        string $target_type,
        string $target_id,
        bool $dry_run,
        $before,
        $after,
        $diff,
        int $status_code = 200
    ): void {
        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => $scope,
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => $target_type,
            'target_id' => $target_id,
            'dry_run' => $dry_run,
            'before' => $before,
            'after' => $after,
            'diff' => $diff,
            'status_code' => $status_code,
        ]);
    }

    private static function normalize_for_compare($value)
    {
        if (is_object($value)) {
            return self::normalize_for_compare((array) $value);
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $key => $item) {
                $out[$key] = self::normalize_for_compare($item);
            }
            return $out;
        }

        return $value;
    }
}

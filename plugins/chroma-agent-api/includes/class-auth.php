<?php

namespace ChromaAgentAPI;

use WP_Error;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class Auth
{
    /**
     * @var array<string,array>
     */
    private static $cache = [];

    /**
     * @var array|null
     */
    private static $current_key = null;

    public static function authorize(WP_REST_Request $request, array $required_scopes = [])
    {
        $required_scopes = Utils::normalize_scopes($required_scopes);

        $cache_id = spl_object_hash($request) . '|' . implode(',', $required_scopes);
        if (isset(self::$cache[$cache_id])) {
            $cached = self::$cache[$cache_id];
            self::$current_key = $cached['key'] ?? null;
            return $cached['result'];
        }

        if (!Utils::is_https_request()) {
            $err = new WP_Error('caa_https_required', 'HTTPS is required for Agent API access.', ['status' => 403]);
            self::$cache[$cache_id] = ['result' => $err, 'key' => null];
            return $err;
        }

        $raw_key = self::extract_api_key($request);
        if ($raw_key === '') {
            $err = new WP_Error('caa_missing_key', 'Missing API key.', ['status' => 401]);
            self::$cache[$cache_id] = ['result' => $err, 'key' => null];
            return $err;
        }

        $verified = Key_Store::verify_key($raw_key);
        if (is_wp_error($verified)) {
            self::$cache[$cache_id] = ['result' => $verified, 'key' => null];
            return $verified;
        }

        $rate = Rate_Limiter::check((int) $verified['id'], (int) ($verified['rate_limit_per_min'] ?? 120));
        if (is_wp_error($rate)) {
            self::$cache[$cache_id] = ['result' => $rate, 'key' => null];
            return $rate;
        }

        $token_check = self::validate_optional_signature($request, $raw_key);
        if (is_wp_error($token_check)) {
            self::$cache[$cache_id] = ['result' => $token_check, 'key' => null];
            return $token_check;
        }

        $granted_scopes = is_array($verified['scopes'] ?? null) ? $verified['scopes'] : [];
        $missing = array_diff($required_scopes, $granted_scopes);

        if (!empty($missing)) {
            $err = new WP_Error(
                'caa_scope_denied',
                'API key does not grant required scope(s): ' . implode(', ', $missing),
                ['status' => 403]
            );
            self::$cache[$cache_id] = ['result' => $err, 'key' => null];
            return $err;
        }

        self::$current_key = $verified;
        self::$cache[$cache_id] = ['result' => true, 'key' => $verified];

        return true;
    }

    public static function current_key(): ?array
    {
        return self::$current_key;
    }

    public static function current_key_id(): int
    {
        return (int) (self::$current_key['id'] ?? 0);
    }

    private static function extract_api_key(WP_REST_Request $request): string
    {
        $auth = (string) $request->get_header('authorization');
        if ($auth !== '' && preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return trim($m[1]);
        }

        $header_key = (string) $request->get_header('x-api-key');
        if ($header_key !== '') {
            return trim($header_key);
        }

        $param_key = (string) $request->get_param('api_key');
        return trim($param_key);
    }

    private static function validate_optional_signature(WP_REST_Request $request, string $raw_key)
    {
        $timestamp = (string) $request->get_header('x-chroma-timestamp');
        $signature = (string) $request->get_header('x-chroma-signature');

        if ($timestamp === '' && $signature === '') {
            return true;
        }

        if ($timestamp === '' || $signature === '') {
            return new WP_Error('caa_signature_missing', 'Both signature and timestamp headers are required when signing requests.', ['status' => 401]);
        }

        if (!ctype_digit($timestamp)) {
            return new WP_Error('caa_signature_invalid', 'Invalid signature timestamp.', ['status' => 401]);
        }

        $ts = (int) $timestamp;
        if (abs(time() - $ts) > 300) {
            return new WP_Error('caa_signature_expired', 'Signed request timestamp is outside the allowed window.', ['status' => 401]);
        }

        $body = $request->get_body();
        $message = $request->get_method() . '\n' . $request->get_route() . '\n' . $timestamp . '\n' . $body;
        $expected = hash_hmac('sha256', $message, $raw_key);

        if (!hash_equals($expected, $signature)) {
            return new WP_Error('caa_signature_mismatch', 'Request signature mismatch.', ['status' => 401]);
        }

        return true;
    }
}

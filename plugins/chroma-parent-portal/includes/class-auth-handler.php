<?php
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Portal_Auth
{
    private const TOKEN_VERSION = 'v1';
    private const TOKEN_TTL = 24 * HOUR_IN_SECONDS;

    public static function login($pin)
    {
        $pin = sanitize_text_field($pin);
        $hashed_lookup = md5($pin);

        $args = [
            'post_type' => 'cp_family',
            'posts_per_page' => 1,
            'meta_key' => '_cp_pin_simple_hash',
            'meta_value' => $hashed_lookup,
            'fields' => 'ids'
        ];

        $families = get_posts($args);

        if (empty($families)) {
            // SECURITY: Log failed PIN attempts for monitoring
            error_log('Parent Portal Login Failure: Invalid PIN attempted.');
            return new WP_Error('invalid_pin', 'Invalid PIN', ['status' => 401]);
        }

        $family_id = $families[0];
        $family_name = get_the_title($family_id);

        try {
            $token = self::create_signed_token($family_id, $family_name);

            return [
                'token' => $token,
                'family_name' => $family_name,
                'family_id' => $family_id
            ];
        } catch (Exception $e) {
            error_log('Parent Portal Session Creation Error: ' . $e->getMessage());
            return new WP_Error('session_error', 'Failed to create secure session.', ['status' => 500]);
        }
    }

    public static function validate_token($token)
    {
        if (empty($token)) {
            return false;
        }

        $token = sanitize_text_field(wp_unslash($token));

        $family_id = self::validate_signed_token($token);
        if ($family_id) {
            return $family_id;
        }

        // Backward compatibility for any legacy transient-backed sessions still in circulation.
        $session_data = get_transient('chroma_portal_sess_' . $token);

        if (!$session_data || !isset($session_data['family_id'])) {
            return false;
        }

        return $session_data['family_id'];
    }

    private static function create_signed_token($family_id, $family_name)
    {
        $payload = [
            'v' => self::TOKEN_VERSION,
            'fid' => (int) $family_id,
            'fam' => (string) $family_name,
            'exp' => time() + self::TOKEN_TTL,
        ];

        $payload_json = wp_json_encode($payload);
        if (!$payload_json) {
            throw new Exception('Could not encode session payload.');
        }

        $encoded_payload = self::base64url_encode($payload_json);
        $signature = hash_hmac('sha256', $encoded_payload, self::token_secret(), true);
        $encoded_signature = self::base64url_encode($signature);

        return self::TOKEN_VERSION . '.' . $encoded_payload . '.' . $encoded_signature;
    }

    private static function validate_signed_token($token)
    {
        if (!is_string($token) || '' === $token) {
            return false;
        }

        $parts = explode('.', $token);
        if (3 !== count($parts)) {
            return false;
        }

        [$version, $encoded_payload, $encoded_signature] = $parts;
        if (self::TOKEN_VERSION !== $version || '' === $encoded_payload || '' === $encoded_signature) {
            return false;
        }

        $expected_signature = self::base64url_encode(
            hash_hmac('sha256', $encoded_payload, self::token_secret(), true)
        );

        if (!hash_equals($expected_signature, $encoded_signature)) {
            return false;
        }

        $payload_json = self::base64url_decode($encoded_payload);
        if (false === $payload_json) {
            return false;
        }

        $payload = json_decode($payload_json, true);
        if (!is_array($payload)) {
            return false;
        }

        $family_id = isset($payload['fid']) ? (int) $payload['fid'] : 0;
        $expires_at = isset($payload['exp']) ? (int) $payload['exp'] : 0;

        if ($family_id <= 0 || $expires_at < time()) {
            return false;
        }

        $family_post = get_post($family_id);
        if (!$family_post || 'cp_family' !== $family_post->post_type || 'publish' !== $family_post->post_status) {
            return false;
        }

        return $family_id;
    }

    private static function token_secret()
    {
        return wp_salt('auth') . '|' . wp_salt('secure_auth') . '|chroma-parent-portal';
    }

    private static function base64url_encode($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64url_decode($value)
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}

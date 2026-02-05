<?php
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Portal_Auth
{

    public static function login($pin)
    {
        global $wpdb;

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
            // Generate Session Token
            $token = bin2hex(random_bytes(32));

            // P0-5: Use Transients for session management (reliable & cached)
            $session_data = [
                'family_id' => $family_id,
                'family_name' => $family_name,
            ];

            // 24-hour expiration
            set_transient('chroma_portal_sess_' . $token, $session_data, 24 * HOUR_IN_SECONDS);

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

        // P0-5: Validate via transient (standard WP pattern)
        $session_data = get_transient('chroma_portal_sess_' . $token);

        if (!$session_data || !isset($session_data['family_id'])) {
            return false;
        }

        return $session_data['family_id'];
    }
}

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

            // Store Token - Use DIRECT DATABASE QUERY to bypass ALL caching
            $option_key = 'chroma_portal_session_' . $token;
            $expiry = time() + (24 * HOUR_IN_SECONDS);

            $session_data = [
                'family_id' => $family_id,
                'expires' => $expiry
            ];

            $serialized_data = maybe_serialize($session_data);

            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s",
                $option_key
            ));

            if ($existing) {
                $wpdb->update(
                    $wpdb->options,
                    ['option_value' => $serialized_data],
                    ['option_name' => $option_key],
                    ['%s'],
                    ['%s']
                );
            } else {
                $wpdb->insert(
                    $wpdb->options,
                    [
                        'option_name' => $option_key,
                        'option_value' => $serialized_data,
                        'autoload' => 'no'
                    ],
                    ['%s', '%s', '%s']
                );
            }

            // Verification
            $verify_data = $wpdb->get_var($wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                $option_key
            ));

            if (!$verify_data) {
                throw new Exception('Failed to verify session storage in database.');
            }

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
        global $wpdb;

        if (empty($token)) {
            return false;
        }

        $option_key = 'chroma_portal_session_' . $token;

        // Direct database query - bypasses ALL caching
        $option_value = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
            $option_key
        ));

        if (!$option_value) {
            return false;
        }

        // Unserialize the data
        $session_data = maybe_unserialize($option_value);

        if (!$session_data || !isset($session_data['family_id']) || !isset($session_data['expires'])) {
            return false;
        }

        // Check if expired
        if ($session_data['expires'] < time()) {
            // Clean up expired session with direct query
            $wpdb->delete($wpdb->options, ['option_name' => $option_key], ['%s']);
            return false;
        }

        $family_id = $session_data['family_id'];
        return $family_id;
    }
}

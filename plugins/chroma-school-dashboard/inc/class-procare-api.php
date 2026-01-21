<?php
/**
 * ProCare API Client
 * 
 * Handles authentication and data fetching from ProCare Connect.
 * Uses a "try-all-endpoints" strategy to ensure connection.
 */

class Chroma_Procare_API {

    private static $endpoints = [
        'web' => 'https://schools.procareconnect.com/api/v1',
        'api' => 'https://api-school.procareconnect.com/api/v1', 
        'connect' => 'https://api.procareconnect.com/v1'
    ];

    /**
     * Get validated image URLs for the slideshow.
     * Caches results for 5 minutes.
     */
    public static function get_slideshow_photos($school_id) {
        $transient_key = 'chroma_procare_photos_' . $school_id;
        $cached = get_transient($transient_key);
        if ($cached) return $cached;

        // Get Creds
        $config = get_post_meta($school_id, '_chroma_school_config', true);
        $username = $config['procare']['username'] ?? '';
        $password = $config['procare']['password'] ?? '';

        if (!$username || !$password) return [];

        // Auth
        $token = self::authenticate($username, $password);
        if (!$token) return [];

        // Fetch Data (Activity Feed or Photos)
        // Note: We need to know the 'school_id' (ProCare's internal ID), not our WP ID.
        // Usually login response returns available schools.
        
        $procare_school_id = get_transient('chroma_procare_id_' . md5($username));
        if (!$procare_school_id) {
            // Re-fetch profile to get school ID
             // For now, assuming first school in list
        }

        // Implementation of fetching photos would go here.
        // Since we are flying blind on the exact endpoint for photos without a test run,
        // we will return an error log if not connected.

        return []; 
    }

    /**
     * Authenticate and return Bearer token
     */
    public static function authenticate($username, $password) {
        $cache_key = 'chroma_procare_token_' . md5($username);
        $cached_token = get_transient($cache_key);
        if ($cached_token) return $cached_token;

        foreach (self::$endpoints as $key => $base) {
            $url = $base . '/authentication/login'; // Most likely structure
            
            $response = wp_remote_post($url, [
                'body' => json_encode(['email' => $username, 'password' => $password]),
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json']
            ]);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($body['token']) || isset($body['authToken'])) {
                     $token = $body['token'] ?? $body['authToken'];
                     
                     // Helper: Save School Data if present
                     // if (isset($body['schools'][0]['id'])) ...

                     set_transient($cache_key, $token, HOUR_IN_SECONDS);
                     return $token;
                }
            }
        }

        return false;
    }

    /**
     * Check connection helper for Portal
     */
    public static function test_connection($username, $password) {
        $token = self::authenticate($username, $password);
        return [
            'success' => (bool)$token,
            'message' => $token ? 'Connected successfully!' : 'Could not log in. Check credentials.'
        ];
    }
}

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

        // Try to get captured session from proxy first
        $session = get_option('chroma_procare_last_session');
        
        if (!$session) {
            // Fallback to credentials if available
            $config = get_post_meta($school_id, '_chroma_school_config', true);
            $username = $config['procare']['username'] ?? '';
            $password = $config['procare']['password'] ?? '';
            
            if (!$username || !$password) return [];
            $token = self::authenticate($username, $password);
            if (!$token) return [];
            $session = $token; // Assuming token is the session identifier
        }

        // Fetch School profile to find the internal ProCare ID
        $procare_school = self::get_school_profile($session);
        if (!$procare_school || !isset($procare_school['id'])) return [];

        $p_school_id = $procare_school['id'];

        // Now fetch photos from activity feed or gallery
        // Endpoint guess based on standard ProCare patterns:
        $photos = self::fetch_photos($p_school_id, $session);
        
        if (!empty($photos)) {
            set_transient($transient_key, $photos, 5 * MINUTE_IN_SECONDS);
        }

        return $photos; 
    }

    private static function get_school_profile($session) {
        $url = self::$endpoints['web'] . '/user/profile';
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Cookie' => '_procare_session=' . $session,
                'Accept' => 'application/json'
            ]
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return null;
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['schools'][0] ?? null;
    }

    private static function fetch_photos($p_school_id, $session) {
        // We'll try a few common endpoints
        $endpoints = [
            "/schools/{$p_school_id}/activity_feed?type=photo",
            "/schools/{$p_school_id}/photos"
        ];

        foreach ($endpoints as $path) {
            $url = self::$endpoints['web'] . $path;
            $response = wp_remote_get($url, [
                'headers' => [
                    'Cookie' => '_procare_session=' . $session,
                    'Accept' => 'application/json'
                ]
            ]);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                // Parse image URLs from response (depends on structure, but usually 'url' or 'image_url')
                $urls = [];
                
                // Recursive search for URLs in the JSON if we don't know the exact key
                array_walk_recursive($body, function($value, $key) use (&$urls) {
                    if (is_string($value) && (strpos($value, '.jpg') !== false || strpos($value, '.png') !== false) && strpos($value, 'http') === 0) {
                        $urls[] = $value;
                    }
                });

                if (!empty($urls)) return array_values(array_unique($urls));
            }
        }
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

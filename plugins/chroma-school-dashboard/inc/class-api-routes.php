<?php

class Chroma_School_API_Routes
{
    private $current_user_school_id;

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route('chroma/v1', '/tv/(?P<slug>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_tv_data'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('chroma/v1', '/auth/google', [
            'methods' => 'POST',
            'callback' => [$this, 'login_with_google'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('chroma/v1', '/portal/school/(?P<id>\d+)', [
            'methods' => 'PATCH',
            'callback' => [$this, 'update_school'],
            'permission_callback' => [$this, 'check_director_permission']
        ]);

        register_rest_route('chroma/v1', '/portal/me', [
            'methods' => 'GET',
            'callback' => [$this, 'get_director_school'],
            'permission_callback' => [$this, 'check_director_permission']
        ]);

        register_rest_route('chroma/v1', '/weather', [
            'methods' => 'GET',
            'callback' => [$this, 'get_weather_proxy'],
            'permission_callback' => '__return_true'
        ]);

        // Global CORS for this namespace
        add_filter('rest_pre_serve_request', [$this, 'handle_cors_pre_serve'], 10, 4);
    }

    /**
     * Handle CORS for non-permission gated routes like login (P0-5)
     */
    public function handle_cors_pre_serve($served, $result, $request, $server)
    {
        if (strpos($request->get_route(), '/chroma/v1/') !== false) {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE, PATCH");
            header("Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce");

            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                status_header(200);
                exit;
            }
        }
        return $served;
    }

    /**
     * Helper to get cache token (P0-4)
     */
    private function get_cache_token($group)
    {
        if (function_exists('chroma_get_last_changed')) {
            return chroma_get_last_changed($group);
        }
        return 'v1'; // Fallback
    }

    public function get_tv_data($request)
    {
        $slug = $request['slug'];

        // P0-4: Object cache with transient fallback for TV data
        $token = $this->get_cache_token('schools');
        $cache_key = 'school_tv_data_' . $slug . ':' . $token;
        $cached = wp_cache_get($cache_key, 'chroma');

        if (false === $cached) {
            $cached = get_transient('chroma_persistent_tv_' . $slug);
            // Verify token matches if persistent (simple versioning)
            if ($cached && isset($cached['_token']) && $cached['_token'] !== $token) {
                $cached = false;
            }
        }

        if (false !== $cached) {
            return rest_ensure_response($cached);
        }

        $posts = get_posts([
            'name' => $slug,
            'post_type' => 'chroma_school',
            'numberposts' => 1,
            'post_status' => 'publish'
        ]);

        if (empty($posts)) {
            return new WP_Error('no_school', 'School not found', ['status' => 404]);
        }

        $post = $posts[0];
        $config = get_post_meta($post->ID, '_chroma_school_config', true) ?: [];

        // Fetch weather
        $weather = null;
        if (!empty($config['lat'])) {
            $weather = Chroma_Weather_Provider::get_weather($config['lat'], $config['lon']);
        }

        // Get all other meta
        $meta_keys = [
            'newsletter',
            'eom',
            'announcements',
            'today',
            'qr',
            'menu',
            'slideshow',
            'youtube',
            'slideshow_title',
            'chroma_cares',
            'celebrations',
            'music_url'
        ];

        $data = [
            'id' => $post->ID,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'config' => ['timezone' => $config['timezone'] ?? 'America/New_York'], // Don't leak email
            'weather' => $weather,
            'content' => []
        ];

        foreach ($meta_keys as $key) {
            $data['content'][$key] = get_post_meta($post->ID, '_chroma_school_' . $key, true);
        }

        // Add Global Chroma Cares
        $data['global'] = [
            'chroma_cares' => get_option('chroma_global_cares', []),
            'alert' => get_option('chroma_global_alert', [])
        ];

        $data['_token'] = $token;

        wp_cache_set($cache_key, $data, 'chroma', HOUR_IN_SECONDS);
        set_transient('chroma_persistent_tv_' . $slug, $data, DAY_IN_SECONDS);

        return rest_ensure_response($data);
    }

    /**
     * POST /auth/google
     * Verifies Google ID Token via Google API, finds matching Director, returns Session Token.
     */
    public function login_with_google($request)
    {
        $params = $request->get_json_params();
        $id_token = $params['token'] ?? $params['id_token'] ?? '';

        if (!$params || empty($id_token)) {
            return new WP_Error('missing_token', 'ID Token required', ['status' => 400]);
        }

        // 1. Verify Token with Google
        $response = wp_remote_get('https://oauth2.googleapis.com/tokeninfo?id_token=' . $id_token);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return new WP_Error('invalid_token', 'Google Token invalid', ['status' => 401]);
        }

        $google_data = json_decode(wp_remote_retrieve_body($response), true);

        // 1.1 Verify Audience (Client ID)
        $client_id = get_option('chroma_google_client_id');
        if (empty($client_id) || empty($google_data['aud']) || $google_data['aud'] !== $client_id) {
            return new WP_Error('invalid_client', 'Google Token Audience mismatch.', ['status' => 401]);
        }

        if (empty($google_data['email']) || empty($google_data['email_verified']) || $google_data['email_verified'] !== 'true') {
            return new WP_Error('email_unverified', 'Email not verified', ['status' => 401]);
        }

        $email = $google_data['email'];

        // 2. Find School by Director Email (Optimized O(1) query)
        $schools = get_posts([
            'post_type' => 'chroma_school',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_chroma_school_director_email',
                    'value' => $email,
                    'compare' => '='
                ]
            ]
        ]);

        $found_school = !empty($schools) ? $schools[0] : null;

        // 2b. Legacy Fallback: If not found, try O(N) loop for migration
        // 2. Find associated school
        $found_school = null;

        // P0-5: Optimized Meta Lookup
        $schools = get_posts([
            'post_type' => 'chroma_school',
            'meta_key' => '_chroma_school_director_email',
            'meta_value' => $email,
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'fields' => 'ids',
        ]);

        if (!empty($schools)) {
            $found_school = get_post($schools[0]);
        }

        // Fallback: Legacy O(N) Scan (Migration Path)
        if (!$found_school) {
            $all_schools = get_posts(['post_type' => 'chroma_school', 'posts_per_page' => -1]);
            foreach ($all_schools as $s) {
                $conf = get_post_meta($s->ID, '_chroma_school_config', true);
                $stored_email = $conf['director_email'] ?? 'N/A';

                if (strtolower($stored_email) === strtolower($email)) {
                    $found_school = $s;
                    // Seed the optimized key for next time
                    update_post_meta($s->ID, '_chroma_school_director_email', $email);
                    break;
                }
            }
        }

        if (!$found_school) {
            return new WP_Error('no_access', 'No school found for: ' . $email, ['status' => 403]);
        }

        // 3. Issue Session Token
        $token = bin2hex(random_bytes(32));
        $expiration = HOUR_IN_SECONDS * 12;

        // Store session transient: chroma_sess_{token} = {school_id, email, exp}
        set_transient('chroma_sess_' . $token, [
            'school_id' => $found_school->ID,
            'email' => $email,
            'exp' => time() + $expiration
        ], $expiration);

        return rest_ensure_response([
            'token' => $token,
            'school_id' => $found_school->ID,
            'school_slug' => $found_school->post_name,
            'director_email' => $email
        ]);
    }

    /**
     * GET /portal/me
     */
    public function get_director_school($request)
    {
        $school_id = $this->current_user_school_id; // Set by permission check
        if (!$school_id)
            return new WP_Error('unauthorized', 'Invalid session', ['status' => 401]);

        // P0-4: Cache Director Portal Data with transient fallback
        $token = $this->get_cache_token('schools');
        $cache_key = 'director_school_data_' . $school_id . ':' . $token;
        $cached = wp_cache_get($cache_key, 'chroma');

        if (false === $cached) {
            $cached = get_transient('chroma_persistent_director_' . $school_id);
            if ($cached && isset($cached['_token']) && $cached['_token'] !== $token) {
                $cached = false;
            }
        }

        if (false !== $cached) {
            return rest_ensure_response($cached);
        }

        // Reuse get data logic but without the HTTP request overhead
        $post = get_post($school_id);

        $meta_keys = [
            'newsletter',
            'eom',
            'announcements',
            'today',
            'qr',
            'menu',
            'slideshow',
            'youtube',
            'welcome_override',
            'slideshow_title',
            'chroma_cares',
            'celebrations',
            'music_url'
        ];
        $content = [];
        foreach ($meta_keys as $key) {
            $content[$key] = get_post_meta($school_id, '_chroma_school_' . $key, true);
        }

        $data = [
            'id' => $school_id,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'content' => $content,
            '_token' => $token
        ];

        wp_cache_set($cache_key, $data, 'chroma', HOUR_IN_SECONDS);
        set_transient('chroma_persistent_director_' . $school_id, $data, DAY_IN_SECONDS);

        return rest_ensure_response($data);
    }

    /**
     * PATCH /portal/school/{id}
     */
    public function update_school($request)
    {
        $school_id = $request['id'];
        $params = $request->get_json_params();

        // LOGGING
        $log = sprintf("[%s] PATCH School %s. Payload keys: %s\n", date('Y-m-d H:i:s'), $school_id, implode(',', array_keys($params)));
        file_put_contents(WP_CONTENT_DIR . '/uploads/portal-api.log', $log, FILE_APPEND);

        // Whitelisted fields to update
        $allowed_keys = [
            'newsletter',
            'eom',
            'announcements',
            'today',
            'qr',
            'menu',
            'slideshow',
            'youtube',
            'slideshow_title',
            'welcome_override',
            'chroma_cares',
            'celebrations',
            'music_url'
        ];

        foreach ($params as $key => $value) {
            if (in_array($key, $allowed_keys)) {
                // Determine if field is complex (array/object)
                $is_complex = in_array($key, [
                    'newsletter',
                    'eom',
                    'announcements',
                    'today',
                    'qr',
                    'slideshow',
                    'celebrations'
                ]);

                if ($is_complex) {
                    $value = is_array($value) ? $value : [];
                    // Optionally sanitize inner strings if needed, but keep structure
                } else {
                    $value = wp_kses_post($value); // Allow safe HTML in single strings
                }

                $updated = update_post_meta($school_id, '_chroma_school_' . $key, $value);
            }
        }

        // Add a global 'last updated' flag for cache busting
        update_post_meta($school_id, '_chroma_school_last_updated', time());

        // P0-4: Invalidate schools cache group
        if (function_exists('chroma_invalidate_cache_group')) {
            chroma_invalidate_cache_group('schools');
        }

        return rest_ensure_response(['success' => true]);
    }

    /**
     * Permission Callback
     * Checks Authorization: Bearer {token}
     */
    public function check_director_permission($request)
    {
        // CORS handled by handle_cors_pre_serve filter

        $auth_header = $request->get_header('Authorization');

        // Log Auth Attempt
        // file_put_contents(WP_CONTENT_DIR . '/uploads/portal-api.log', "Auth Check: " . substr($auth_header, 0, 20) . "...\n", FILE_APPEND);

        if (!$auth_header || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            return false;
        }

        $token = $matches[1];
        $session = get_transient('chroma_sess_' . $token);

        if (!$session || !isset($session['school_id'])) {
            return false;
        }

        // If route has {id}, verify it matches (for PATCH)
        $route_id = $request->get_param('id');
        if ($route_id && intval($route_id) !== intval($session['school_id'])) {
            return false;
        }

        // Store for use in callback
        $this->current_user_school_id = $session['school_id'];
        return true;
    }
}



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
    }

    /**
     * Proxy Weather Request (to avoid revealing tokens if we had them, and use server cache)
     */
    public function get_weather_proxy($request)
    {
        $lat = $request->get_param('lat');
        $lon = $request->get_param('lon');

        if (!$lat || !$lon) {
            return new WP_Error('missing_params', 'Lat/Lon required', ['status' => 400]);
        }

        $weather = Chroma_Weather_Provider::get_weather($lat, $lon);
        return rest_ensure_response($weather ?: ['error' => 'Weather unavailable']);
    }

    public function get_tv_data($request)
    {
        $slug = $request['slug'];
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
        if (!$found_school) {
            $all_schools = get_posts(['post_type' => 'chroma_school', 'posts_per_page' => -1]);
            foreach ($all_schools as $s) {
                $conf = get_post_meta($s->ID, '_chroma_school_config', true);
                $stored_email = $conf['director_email'] ?? 'N/A';

                if (strtolower($stored_email) === strtolower($email)) {
                    $found_school = $s;
                    update_post_meta($s->ID, '_chroma_school_director_email', $conf['director_email']);
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

        return rest_ensure_response([
            'id' => $school_id,
            'title' => $post->post_title,
            'slug' => $post->post_name,
            'content' => $content
        ]);
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

        return rest_ensure_response(['success' => true]);
    }

    /**
     * Permission Callback
     * Checks Authorization: Bearer {token}
     */
    public function check_director_permission($request)
    {
        // Handle Preflight / CORS for local dev or cross-domain
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE, PATCH");
        header("Access-Control-Allow-Headers: Authorization, Content-Type");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return true;
        }

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



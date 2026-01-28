<?php
/**
 * Google OAuth Handler
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Auth;

/**
 * Handles Google OAuth 2.0 authentication.
 */
class Google_OAuth
{

    /**
     * OAuth endpoints.
     */
    const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const USERINFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';

    /**
     * Get Google OAuth client ID.
     *
     * @return string
     */
    public static function get_client_id()
    {
        return \ChromaQA\Settings::get('google_client_id');
    }

    /**
     * Get Google OAuth client secret.
     *
     * @return string
     */
    public static function get_client_secret()
    {
        return \ChromaQA\Settings::get('google_client_secret');
    }

    /**
     * Get the OAuth callback URL.
     *
     * @return string
     */
    public static function get_redirect_uri()
    {
        return home_url('/qa-reports/auth/callback/');
    }

    /**
     * Check if OAuth is configured.
     *
     * @return bool
     */
    public static function is_configured()
    {
        return !empty(self::get_client_id()) && !empty(self::get_client_secret());
    }

    /**
     * Get the authorization URL.
     *
     * @param string $state State parameter for CSRF protection.
     * @return string
     */
    public static function get_auth_url($state = '')
    {
        if (empty($state)) {
            $state = wp_create_nonce('cqa_oauth_state');
        }

        $params = [
            'client_id' => self::get_client_id(),
            'redirect_uri' => self::get_redirect_uri(),
            'response_type' => 'code',
            'scope' => implode(' ', [
                'email',
                'profile',
                'https://www.googleapis.com/auth/drive.file',
            ]),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens.
     *
     * @param string $code Authorization code.
     * @return array|WP_Error
     */
    public static function exchange_code($code)
    {
        $response = wp_remote_post(self::TOKEN_URL, [
            'body' => [
                'client_id' => self::get_client_id(),
                'client_secret' => self::get_client_secret(),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => self::get_redirect_uri(),
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new \WP_Error('oauth_error', $body['error_description'] ?? $body['error']);
        }

        return $body;
    }

    /**
     * Refresh an access token.
     *
     * @param string $refresh_token Refresh token.
     * @return array|WP_Error
     */
    public static function refresh_token($refresh_token)
    {
        $response = wp_remote_post(self::TOKEN_URL, [
            'body' => [
                'client_id' => self::get_client_id(),
                'client_secret' => self::get_client_secret(),
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new \WP_Error('oauth_error', $body['error_description'] ?? $body['error']);
        }

        return $body;
    }

    /**
     * Get user info from Google.
     *
     * @param string $access_token Access token.
     * @return array|WP_Error
     */
    public static function get_user_info($access_token)
    {
        $response = wp_remote_get(self::USERINFO_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new \WP_Error('oauth_error', $body['error']['message'] ?? 'Unknown error');
        }

        return $body;
    }

    /**
     * Store tokens for a user (Encrypted).
     *
     * @param int   $user_id User ID.
     * @param array $tokens Token data.
     */
    public static function store_tokens($user_id, $tokens)
    {
        $access_token = self::encrypt($tokens['access_token']);
        update_user_meta($user_id, 'cqa_google_access_token', $access_token);
        update_user_meta($user_id, 'cqa_google_token_expires', time() + $tokens['expires_in']);

        if (isset($tokens['refresh_token'])) {
            $refresh_token = self::encrypt($tokens['refresh_token']);
            update_user_meta($user_id, 'cqa_google_refresh_token', $refresh_token);
        }
    }

    /**
     * Get a valid access token for a user.
     *
     * @param int $user_id User ID.
     * @return string|WP_Error
     */
    public static function get_access_token($user_id)
    {
        $encrypted_access_token = get_user_meta($user_id, 'cqa_google_access_token', true);
        $access_token = self::decrypt($encrypted_access_token);
        $expires = get_user_meta($user_id, 'cqa_google_token_expires', true);

        // Check if token is expired
        if (empty($access_token) || $expires < time() + 300) {
            $encrypted_refresh_token = get_user_meta($user_id, 'cqa_google_refresh_token', true);
            $refresh_token = self::decrypt($encrypted_refresh_token);

            if (empty($refresh_token)) {
                return new \WP_Error('no_refresh_token', __('No refresh token available. Please re-authenticate.', 'chroma-qa-reports'));
            }

            // Concurrency Lock: Prevent multiple threads from refreshing at the same time
            $lock_key = 'cqa_refresh_lock_' . $user_id;
            if (get_transient($lock_key)) {
                // If locked, wait briefly or return error to let the other thread finish
                // For simplicity, we wait up to 2 seconds
                for ($i = 0; $i < 4; $i++) {
                    usleep(500000); // 0.5s
                    $token = get_user_meta($user_id, 'cqa_google_access_token', true);
                    $expires = get_user_meta($user_id, 'cqa_google_token_expires', true);
                    if ($expires > time() + 300) {
                        return self::decrypt($token);
                    }
                }
                return new \WP_Error('refresh_in_progress', __('A token refresh is currently in progress. Please try again.', 'chroma-qa-reports'));
            }

            set_transient($lock_key, true, 30); // 30s lock

            $tokens = self::refresh_token($refresh_token);

            delete_transient($lock_key);

            if (is_wp_error($tokens)) {
                return $tokens;
            }

            self::store_tokens($user_id, $tokens);
            $access_token = $tokens['access_token'];
        }

        return $access_token;
    }

    /**
     * Check if user has connected Google account.
     *
     * @param int $user_id User ID.
     * @return bool
     */
    public static function is_user_connected($user_id)
    {
        return !empty(get_user_meta($user_id, 'cqa_google_refresh_token', true));
    }

    /**
     * Disconnect user's Google account.
     *
     * @param int $user_id User ID.
     */
    public static function disconnect_user($user_id)
    {
        delete_user_meta($user_id, 'cqa_google_access_token');
        delete_user_meta($user_id, 'cqa_google_refresh_token');
        delete_user_meta($user_id, 'cqa_google_token_expires');
    }
    /**
     * Handle OAuth login callback.
     *
     * @param string $code Authorization code.
     * @param string $state CSRF State parameter.
     * @return int|WP_Error User ID on success, WP_Error on failure.
     */
    public static function handle_login($code, $state = '')
    {
        // QAR-086: CSRF Protection
        if (empty($state) || !wp_verify_nonce($state, 'cqa_oauth_state')) {
            return new \WP_Error('csrf_error', __('Invalid session state. Please try again.', 'chroma-qa-reports'));
        }

        // Exchange code for tokens
        $tokens = self::exchange_code($code);
        if (is_wp_error($tokens)) {
            return $tokens;
        }

        // Get user info
        $user_info = self::get_user_info($tokens['access_token']);
        if (is_wp_error($user_info)) {
            return $user_info;
        }

        $email = $user_info['email'];
        $user = get_user_by('email', $email);

        // If user doesn't exist, check domain and create
        if (!$user) {
            $allowed_domain = get_option('cqa_sso_domain', '');

            // If domain restriction is set, verify it
            if (!empty($allowed_domain)) {
                // Normalize allowed domain (remove protocol, www, and trailing slash)
                $allowed_domain = strtolower(preg_replace('#^https?://(?:www\.)?#i', '', $allowed_domain));
                $allowed_domain = rtrim($allowed_domain, '/');
                $email_domain = strtolower(substr(strrchr($email, "@"), 1));

                if (strcasecmp($email_domain, $allowed_domain) !== 0) {
                    return new \WP_Error('invalid_domain', sprintf(__('Only emails from %s are allowed.', 'chroma-qa-reports'), $allowed_domain));
                }
            } else {
                // If no domain set, prevent public registration for security
                // unless explicitly allowed (could add another setting, but safe default is block)
                return new \WP_Error('registration_disabled', __('Public registration is disabled. Please contact administrator.', 'chroma-qa-reports'));
            }

            // Create new user
            $userdata = [
                'user_login' => $email,
                'user_email' => $email,
                'user_pass' => wp_generate_password(),
                'first_name' => $user_info['given_name'] ?? '',
                'last_name' => $user_info['family_name'] ?? '',
                'role' => 'subscriber', // Default to safe role first
            ];

            // If approval is required, set a flag and maybe don't give them the full role yet
            // For now, we defaults to subscriber which has NO CQA capabilities (per FIX-106).
            // Real role is assigned after checks or if auto-approve is on.

            $default_role = \ChromaQA\Settings::get('sso_default_role', 'cqa_qa_officer');
            $require_approval = \ChromaQA\Settings::get('sso_require_approval', false);

            if (!$require_approval) {
                $userdata['role'] = $default_role;
            }

            $user_id = wp_insert_user($userdata);
            if (is_wp_error($user_id)) {
                return $user_id;
            }

            if ($require_approval) {
                update_user_meta($user_id, 'cqa_account_status', 'pending_approval');
            } else {
                update_user_meta($user_id, 'cqa_account_status', 'active');
            }

            $user = get_user_by('id', $user_id);
        }

        // Store tokens for this user (Drive access)
        self::store_tokens($user->ID, $tokens);

        // Log user in - use both functions to ensure proper session
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        // Trigger WordPress login action (important for plugins and session handling)
        do_action('wp_login', $user->user_login, $user);

        return $user->ID;
    }
    /**
     * Encrypt a string using WP Salt.
     * 
     * @param string $plain_text Text to encrypt.
     * @return string
     */
    private static function encrypt($plain_text)
    {
        if (empty($plain_text))
            return '';

        $key = defined('LOGGED_IN_SALT') ? LOGGED_IN_SALT : 'cqa_fallback_salt';
        $method = 'aes-256-ctr';
        $iv_length = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted = openssl_encrypt($plain_text, $method, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a string using WP Salt.
     * 
     * @param string $encrypted_text Encrypted text.
     * @return string
     */
    private static function decrypt($encrypted_text)
    {
        if (empty($encrypted_text))
            return '';

        $data = base64_decode($encrypted_text);
        if (!$data)
            return '';

        $key = defined('LOGGED_IN_SALT') ? LOGGED_IN_SALT : 'cqa_fallback_salt';
        $method = 'aes-256-ctr';
        $iv_length = openssl_cipher_iv_length($method);

        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);

        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }
}

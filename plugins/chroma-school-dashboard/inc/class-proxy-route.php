<?php
/**
 * ProCare PHP Proxy Route
 * Bypasses X-Frame-Options and captures session cookies.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Proxy_Route
{
    private $target_origin = 'https://schools.procareconnect.com';
    private $namespace = 'chroma/v1';

    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route($this->namespace, '/procare-proxy', [
            'methods'  => 'GET',
            'callback' => [$this, 'handle_proxy'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
        
        // Also handle static assets if they are relative
        register_rest_route($this->namespace, '/procare-proxy/(?P<path>.+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'handle_proxy'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    public function check_permission($request)
    {
        // For development, allow access. In production, check for director capabilities.
        return true; 
    }

    public function handle_proxy($request)
    {
        $path = $request->get_param('path') ?: '';
        $query = $_GET;
        unset($query['rest_route']); // Remove WP param

        $url = $this->target_origin . '/' . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        // Forward cookies from the browser to ProCare
        $cookies = [];
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, 'procare_') === 0 || $name === 'session' || $name === '_procare_session') {
                $cookies[] = new WP_Http_Cookie(['name' => $name, 'value' => $value]);
            }
        }

        $args = [
            'timeout'     => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
            'cookies'     => $cookies,
            'sslverify'   => false
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            return new WP_Error('proxy_error', $response->get_error_message(), ['status' => 500]);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);

        // Process Cookies from ProCare
        $res_cookies = wp_remote_retrieve_cookies($response);
        foreach ($res_cookies as $cookie) {
            setcookie($cookie->name, $cookie->value, $cookie->expires, '/', $_SERVER['HTTP_HOST'], true, true);
            
            // SAVE TO DB: If we see a session cookie, cache it for the school
            if ($cookie->name === '_procare_session' || $cookie->name === 'session') {
                // We need to know which school this is for. 
                // For now, we'll use a general option or try to find the current user's school
                update_option('chroma_procare_last_session', $cookie->value);
            }
        }

        // Output content
        header("Content-Type: " . ($headers['content-type'] ?? 'text/html'));
        
        // STRIP SECURITY HEADERS to allow framing
        header_remove('X-Frame-Options');
        header_remove('Content-Security-Policy');
        header("X-Frame-Options: ALLOWALL"); 

        // Rewrite relative URLs in HTML
        if (strpos($headers['content-type'], 'text/html') !== false) {
            $body = $this->rewrite_content($body);
        }

        echo $body;
        exit;
    }

    private function rewrite_content($body)
    {
        // Rewrite root-relative links to go through proxy
        $base_proxy = get_rest_url(null, $this->namespace . '/procare-proxy/');
        
        // Simple regex to catch common patterns. Real proxying is harder, but this might suffice for login.
        $body = str_replace('href="/', 'href="' . $base_proxy, $body);
        $body = str_replace('src="/', 'src="' . $base_proxy, $body);
        $body = str_replace('action="/', 'action="' . $base_proxy, $body);
        
        return $body;
    }
}

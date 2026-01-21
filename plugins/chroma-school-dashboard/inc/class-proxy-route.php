<?php
/**
 * ProCare PHP Reverse Proxy
 * 
 * Acts as a middleware to fetch ProCare content and strip X-Frame-Options headers
 * so it can be embedded in an iframe. Also handles URL rewriting to keep
 * user navigation within the proxy.
 */

class Chroma_Procare_Proxy {

    private $base_url = 'https://schools.procareconnect.com';
    private $allowed_host = 'schools.procareconnect.com';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('chroma/v1', '/proxy', [
            'methods' => ['GET', 'POST'],
            'callback' => [$this, 'handle_request'],
            'permission_callback' => '__return_true' // Restricted by nonce/token in future if needed
        ]);
    }

    public function handle_request($request) {
        $target_url = $request->get_param('url');

        // Default to base URL if not provided
        if (empty($target_url)) {
            $target_url = $this->base_url;
        }

        // Security: Validate Host
        $parsed = parse_url($target_url);
        if (!isset($parsed['host']) || $parsed['host'] !== $this->allowed_host) {
            // Allow relative paths (assets)
            if (!isset($parsed['host']) && strpos($target_url, '/') === 0) {
                $target_url = $this->base_url . $target_url;
            } else {
                return new WP_Error('invalid_host', 'Host not allowed', ['status' => 403]);
            }
        }

        // Fetch Args
        $args = [
            'timeout' => 30,
            'redirection' => 5,
            'httpversion' => '1.1',
            'user-agent' => $_SERVER['HTTP_USER_AGENT'],
            'sslverify' => false,
            'cookies' => $_COOKIE, // Forward user cookies if any
        ];

        // Handle POST
        if ($request->get_method() === 'POST') {
            $args['body'] = $request->get_body_params();
        }

        // Make Request
        $response = wp_remote_request($target_url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        // Headers to strip
        $strip_headers = [
            'x-frame-options',
            'content-security-policy',
            'content-security-policy-report-only',
            'set-cookie' // We might need to handle this manually to proxy cookies
        ];

        // Forward headers to client
        $headers = wp_remote_retrieve_headers($response);
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $strip_headers)) {
                continue;
            }
            // header("$key: $value"); // WP REST API handles this later, but we modify raw output
        }

        $body = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        // Rewrites for HTML
        if (strpos($content_type, 'text/html') !== false) {
            $body = $this->rewrite_html($body);
            
            // Inject Login Pre-fill if on login page
            if (strpos($target_url, 'login') !== false) {
                $body = $this->inject_login_helper($body);
            }
        }

        // Output Raw (Bypass REST JSON encoding)
        header('Content-Type: ' . $content_type);
        
        // Handle Set-Cookie mapping (Simple pass-through for now)
        // Note: Real session management in PHP proxy is complex. 
        // We rely on the browser treating this as the origin.
        
        echo $body;
        exit;
    }

    private function rewrite_html($html) {
        $proxy_base = get_rest_url(null, 'chroma/v1/proxy?url=');
        
        // Rewrite asset URLs to go through proxy or be absolute
        // This is a naive regex replacer - in production might need DOMDocument
        
        // 1. Rewrite relative links href="/..." to href="proxy?url=BASE/..."
        $html = preg_replace_callback('/href="\/([^"]+)"/', function($matches) {
            return 'href="' . get_rest_url(null, 'chroma/v1/proxy?url=') . urlencode($this->base_url . '/' . $matches[1]) . '"';
        }, $html);

        // 2. Rewrite src="/..." (scripts, images)
        $html = preg_replace_callback('/src="\/([^"]+)"/', function($matches) {
            return 'src="' . get_rest_url(null, 'chroma/v1/proxy?url=') . urlencode($this->base_url . '/' . $matches[1]) . '"';
        }, $html);
        
        // 3. Rewrite absolute ProCare links
        $html = str_replace(
            $this->base_url,
            get_rest_url(null, 'chroma/v1/proxy?url=' . $this->base_url),
            $html
        );

        // 4. Inject base tag to help with relative assets we missed
        $html = str_replace('<head>', '<head><base href="' . $this->base_url . '">', $html);
        
        return $html;
    }

    private function inject_login_helper($html) {
         // Get creds from a variable? We don't have school context here nicely without params.
         // For now, simple console log to prove injection works
         $script = "<script>console.log('Chroma Proxy: Login Helper Active');</script>";
         return str_replace('</body>', $script . '</body>', $html);
    }
}

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
            error_log('ProCare Proxy Error: ' . $response->get_error_message());
            return new WP_Error('proxy_error', $response->get_error_message(), ['status' => 500]);
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);

        error_log("ProCare Proxy Request: $url | Response Code: $code");

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
        
        // Process Cookies from ProCare
        $res_cookies = wp_remote_retrieve_cookies($response);
        foreach ($res_cookies as $cookie) {
            // Set cookie on our domain so the browser sends it back to the proxy
            // Use SameSite=Lax for compatibility. Secure=true if on HTTPS.
            $is_secure = is_ssl();
            setcookie($cookie->name, $cookie->value, [
                'expires' => $cookie->expires,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => $is_secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            if ($cookie->name === '_procare_session' || $cookie->name === 'session') {
                update_option('chroma_procare_last_session', $cookie->value);
            }
        }

        // Handle Redirects: If ProCare redirects, we need to tell the browser but keep it in the proxy
        if ($code >= 300 && $code < 400 && isset($headers['location'])) {
            $loc = $headers['location'];
            $proxy_root = get_rest_url(null, $this->namespace . '/procare-proxy');
            
            if (strpos($loc, 'http') === 0) {
                $new_loc = str_replace($this->target_origin, $proxy_root, $loc);
            } else {
                $new_loc = rtrim($proxy_root, '/') . '/' . ltrim($loc, '/');
            }
            
            header("Location: $new_loc");
            exit;
        }

        // STRIP SECURITY HEADERS to allow framing
        header_remove('X-Frame-Options');
        header_remove('Content-Security-Policy');
        header("X-Frame-Options: ALLOWALL"); 

        // Rewrite content for HTML, CSS, and JS
        $ct = $headers['content-type'] ?? '';
        if (strpos($ct, 'text/html') !== false || strpos($ct, 'text/css') !== false || strpos($ct, 'javascript') !== false) {
            $body = $this->rewrite_content($body, $ct);
        }

        echo $body;
        exit;
    }

    private function rewrite_content($body, $content_type)
    {
        $base_proxy = get_rest_url(null, $this->namespace . '/procare-proxy'); // No trailing slash
        $base_proxy_clean = rtrim($base_proxy, '/');
        
        // 0. Handle scheme-less URLs (//schools.procare...)
        $target_origin_noscheme = str_replace(['http:', 'https:'], '', $this->target_origin);
        $body = str_replace($target_origin_noscheme, str_replace(['http:', 'https:'], '', $base_proxy_clean), $body);

        // 1. Rewrite root-relative links in HTML/CSS/JS (e.g. src="/js/...")
        // We ensure we don't create double slashes if base_proxy has one
        $body = preg_replace('/(href|src|action)=["\']\//', '$1="' . $base_proxy_clean . '/', $body);
        
        // 2. Rewrite absolute links to the target origin
        $body = str_replace($this->target_origin, $base_proxy_clean, $body);

        // 3. CSS url() replacements
        $body = preg_replace('/url\(["\']?\//', 'url("' . $base_proxy_clean . '/', $body);

        // 4. Fix potential triple/double slashes resulting from sloppy replacements
        $body = str_replace($base_proxy_clean . '///', $base_proxy_clean . '/', $body);
        $body = str_replace($base_proxy_clean . '//', $base_proxy_clean . '/', $body);

        // 5. Handle <base> tags which can break proxying
        $body = preg_replace('/<base\s+href=["\'][^"\'\s]+["\']\s*\/?>/i', '', $body);

        // 6. Inject script into HTML to intercept fetch/XHR
        if (strpos($content_type, 'text/html') !== false) {
            $interceptor = "
            <script>
            (function() {
                const baseProxy = '" . $base_proxy_clean . "';
                const targetOrigin = '" . $this->target_origin . "';

                function proxyUrl(url) {
                    if (typeof url !== 'string') return url;
                    // Skip data URLs, blobs, etc.
                    if (url.startsWith('data:') || url.startsWith('blob:')) return url;
                    
                    if (url.startsWith('/') && !url.startsWith('//')) {
                        return baseProxy + url;
                    }
                    if (url.includes(targetOrigin)) {
                        return url.replace(targetOrigin, baseProxy);
                    }
                    return url;
                }

                // Intercept Fetch
                const originalFetch = window.fetch;
                window.fetch = function() {
                    if (arguments[0]) arguments[0] = proxyUrl(arguments[0]);
                    return originalFetch.apply(this, arguments);
                };

                // Intercept XHR
                const originalOpen = XMLHttpRequest.prototype.open;
                XMLHttpRequest.prototype.open = function() {
                    if (arguments[1]) arguments[1] = proxyUrl(arguments[1]);
                    return originalOpen.apply(this, arguments);
                };

                // Helper to check if we are stuck
                setTimeout(() => {
                    if (document.body.innerHTML.includes('Loading')) {
                        console.warn('ProCare still loading... checking roots.');
                    }
                }, 5000);
            })();
            </script>";
            
            $body = str_replace('<head>', '<head>' . $interceptor, $body);
        }
        
        return $body;
    }
}

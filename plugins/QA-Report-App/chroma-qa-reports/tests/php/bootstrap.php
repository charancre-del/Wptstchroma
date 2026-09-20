<?php
/**
 * PHPUnit Bootstrap File
 * 
 * Sets up the WordPress testing environment for PHPUnit tests.
 */

// Define test environment constants
define('CQA_TESTS_DIR', __DIR__);
define('CQA_PLUGIN_DIR', dirname(dirname(__DIR__)) . '/');
define('CQA_VERSION', '1.0.1');
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

// Load Composer autoloader
require_once CQA_PLUGIN_DIR . 'vendor/autoload.php';

// Register the plugin autoloader used in production.
spl_autoload_register(function ($class) {
    $prefix = 'ChromaQA\\';
    $base_dir = CQA_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $path_parts = explode('\\', $relative_class);
    $class_file = 'class-' . strtolower(str_replace('_', '-', array_pop($path_parts))) . '.php';

    $file = $base_dir;
    if (!empty($path_parts)) {
        $file .= strtolower(implode('/', $path_parts)) . '/';
    }
    $file .= $class_file;

    if (file_exists($file)) {
        require_once $file;
    }
});

// Bootstrap Brain Monkey for WordPress function mocking
\Brain\Monkey\setUp();

// Mock WordPress functions that are commonly used
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') {
        return htmlspecialchars($text);
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return strip_tags($str);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return strip_tags($str);
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = []) {
        if (is_object($args)) {
            $parsed_args = get_object_vars($args);
        } elseif (is_array($args)) {
            $parsed_args = &$args;
        } else {
            parse_str($args, $parsed_args);
        }
        
        if (is_array($defaults)) {
            return array_merge($defaults, $parsed_args);
        }
        return $parsed_args;
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($value, $flags = 0, $depth = 512) {
        return json_encode($value, $flags, $depth);
    }
}

if (!function_exists('current_time')) {
    function current_time($type = 'mysql') {
        if ($type === 'mysql') {
            return '2026-04-10 12:00:00';
        }

        return time();
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return $GLOBALS['cqa_test_user_id'] ?? 99;
    }
}

if (!function_exists('get_option')) {
    function get_option($name, $default = false) {
        return $GLOBALS['cqa_test_options'][$name] ?? $default;
    }
}

if (!function_exists('sanitize_key')) {
    // Match the WordPress default sanitization; this harness has no filters.
    function sanitize_key($key) {
        return is_scalar($key) ? preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key)) : '';
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($id, $key, $single = true) {
        return $GLOBALS['cqa_test_meta'][$id][$key] ?? '';
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can($cap) {
        return !empty($GLOBALS['cqa_test_caps'][$cap]);
    }
}
if (!function_exists('update_option')) {
    function update_option($key, $value) {
        $GLOBALS['cqa_test_options'][$key] = $value;
        return true;
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($value) { return $value instanceof WP_Error; }
}
if (!function_exists('wp_die')) {
    function wp_die($message, $title = '', $args = []) { throw new RuntimeException($message, (int) ($args['response'] ?? 500)); }
}
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code;
        private $message;
        private $data;
        public function __construct($code = '', $message = '', $data = []) { $this->code = $code; $this->message = $message; $this->data = $data; }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
        public function get_error_data() { return $this->data; }
    }
}
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request implements ArrayAccess {
        private $params;
        private $route;
        private $method;
        public function __construct($method = 'GET', $route = '', $params = []) { $this->params = $params; $this->route = $route; $this->method = $method; }
        public function get_route() { return $this->route; }
        public function get_method() { return $this->method; }
        public function get_params() { return $this->params; }
        public function get_param($key) { return $this->params[$key] ?? null; }
        public function has_param($key) { return array_key_exists($key, $this->params); }
        public function offsetExists($key): bool { return isset($this->params[$key]); }
        public function offsetGet($key): mixed { return $this->get_param($key); }
        public function offsetSet($key, $value): void { $this->params[$key] = $value; }
        public function offsetUnset($key): void { unset($this->params[$key]); }
    }
}
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        private $data;
        private $status;
        public function __construct($data, $status = 200) { $this->data = $data; $this->status = $status; }
        public function get_data() { return $this->data; }
        public function get_status() { return $this->status; }
    }
}

// Register shutdown function to tear down Brain Monkey
register_shutdown_function(function() {
    \Brain\Monkey\tearDown();
});

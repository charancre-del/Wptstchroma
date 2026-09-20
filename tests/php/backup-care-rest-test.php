<?php

define('ABSPATH', __DIR__ . '/');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);

$backup_care_transients = array();
$backup_care_mail = array();

final class WP_Error
{
    private $code;
    private $message;
    private $data;

    public function __construct($code, $message, $data = array())
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public function get_error_code()
    {
        return $this->code;
    }

    public function get_error_message()
    {
        return $this->message;
    }

    public function get_error_data()
    {
        return $this->data;
    }
}

final class WP_REST_Response
{
    private $data;
    private $status;
    private $headers = array();

    public function __construct($data, $status = 200)
    {
        $this->data = $data;
        $this->status = $status;
    }

    public function get_data()
    {
        return $this->data;
    }

    public function get_status()
    {
        return $this->status;
    }

    public function header($name, $value)
    {
        $this->headers[$name] = $value;
    }
}

final class WP_REST_Request
{
    private $body;
    private $headers;

    public function __construct($body, array $headers = array())
    {
        $this->body = $body;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public function get_body()
    {
        return $this->body;
    }

    public function get_json_params()
    {
        return json_decode($this->body, true);
    }

    public function get_header($name)
    {
        $key = strtolower($name);
        return isset($this->headers[$key]) ? $this->headers[$key] : '';
    }
}

final class Backup_Care_Test_WPDB
{
    public $prefix = 'wp_';
}

final class Backup_Care_Test_Rate_Limiter
{
    public $counts = array();

    public function consume_rate_limit($key, $limit, $window)
    {
        $this->counts[$key] = isset($this->counts[$key]) ? $this->counts[$key] + 1 : 1;
        return $this->counts[$key] <= $limit;
    }

    public function reset()
    {
        $this->counts = array();
    }
}

function get_option($key, $default = false)
{
    return $default;
}

function sanitize_email($value)
{
    return filter_var($value, FILTER_SANITIZE_EMAIL);
}

function sanitize_text_field($value)
{
    return trim(strip_tags((string) $value));
}

function home_url($path = '')
{
    return 'https://staging.example.test' . $path;
}

function wp_parse_url($url, $component = -1)
{
    return parse_url($url, $component);
}

function wp_verify_nonce($nonce, $action)
{
    return $nonce === 'valid-preview-nonce' && $action === 'chroma_backup_care_public';
}

function wp_salt($scheme = 'auth')
{
    return 'test-salt-' . $scheme;
}

function get_transient($key)
{
    global $backup_care_transients;
    return isset($backup_care_transients[$key]) ? $backup_care_transients[$key] : false;
}

function set_transient($key, $value, $expiration)
{
    global $backup_care_transients;
    $backup_care_transients[$key] = $value;
    return true;
}

function delete_transient($key)
{
    global $backup_care_transients;
    unset($backup_care_transients[$key]);
    return true;
}

function wp_json_encode($value)
{
    return json_encode($value);
}

function wp_mail($to, $subject, $message, $headers = array())
{
    global $backup_care_mail;
    $backup_care_mail[] = compact('to', 'subject', 'message', 'headers');
    return true;
}

function rest_ensure_response($data)
{
    return $data instanceof WP_REST_Response ? $data : new WP_REST_Response($data, 200);
}

function is_wp_error($value)
{
    return $value instanceof WP_Error;
}

function wp_remote_get($url, array $args)
{
    return array(
        'response' => array('code' => 200),
        'body' => json_encode(array(
            'status' => 'OK',
            'results' => array(array(
                'address_components' => array(array(
                    'short_name' => 'US',
                    'types' => array('country'),
                )),
                'geometry' => array('location' => array(
                    'lat' => 33.8916793,
                    'lng' => -83.9608666,
                )),
            )),
        )),
    );
}

function wp_remote_retrieve_response_code($response)
{
    return $response['response']['code'];
}

function wp_remote_retrieve_body($response)
{
    return $response['body'];
}

function expect_true($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$_SERVER['REMOTE_ADDR'] = '192.0.2.10';

$root = dirname(__DIR__, 2);
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-config.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-domain.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-store.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-ghl-client.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-parent-access.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-service.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-rest-controller.php';

$config = new Chroma_Backup_Care_Config($root . '/infrastructure/ghl/backup-care/manifest.json');
$store = new Chroma_Backup_Care_Store(new Backup_Care_Test_WPDB());
$parent_access = new Chroma_Backup_Care_Parent_Access($config);
$service = new Chroma_Backup_Care_Service(
    $config,
    $store,
    new Chroma_Backup_Care_GHL_Client($config),
    $parent_access
);
$rate_limiter = new Backup_Care_Test_Rate_Limiter();
$controller = new Chroma_Backup_Care_REST_Controller($config, $service, $rate_limiter, $parent_access);

$allowed = new WP_REST_Request('{}', array(
    'x-chroma-backup-care-nonce' => 'valid-preview-nonce',
    'origin' => 'https://staging.example.test',
));
expect_true($controller->check_public_request($allowed) === true, 'A valid same-origin nonce must be accepted.');

$foreign = new WP_REST_Request('{}', array(
    'x-chroma-backup-care-nonce' => 'valid-preview-nonce',
    'origin' => 'https://attacker.example',
));
$foreign_result = $controller->check_public_request($foreign);
expect_true(is_wp_error($foreign_result), 'A foreign origin must be rejected.');
expect_true($foreign_result->get_error_code() === 'invalid_origin', 'The foreign-origin rejection code is incorrect.');

$missing_nonce = new WP_REST_Request('{}', array('origin' => 'https://staging.example.test'));
$nonce_result = $controller->check_public_request($missing_nonce);
expect_true(is_wp_error($nonce_result), 'A missing nonce must be rejected.');
expect_true($nonce_result->get_error_code() === 'invalid_nonce', 'The missing-nonce rejection code is incorrect.');

$rate_limiter->reset();
$access_request = $controller->request_parent_access(new WP_REST_Request(json_encode(array(
    'email' => 'parent@example.test',
))));
expect_true($access_request instanceof WP_REST_Response, 'A verification challenge must be created.');
$access_payload = $access_request->get_data();
expect_true(preg_match('/^[a-f0-9]{32}$/', $access_payload['challenge_id']) === 1, 'Challenge IDs must be opaque.');
expect_true(count($backup_care_mail) === 1, 'One verification email must be sent.');
preg_match('/\b([0-9]{6})\b/', $backup_care_mail[0]['message'], $code_match);
expect_true(!empty($code_match[1]), 'The verification email must contain a six-digit code.');

$verified = $controller->verify_parent_access(new WP_REST_Request(json_encode(array(
    'challenge_id' => $access_payload['challenge_id'],
    'email' => 'parent@example.test',
    'code' => $code_match[1],
))));
expect_true($verified instanceof WP_REST_Response, 'A valid verification code must be accepted.');
$verified_payload = $verified->get_data();
expect_true(!empty($verified_payload['parent_access_token']), 'Verification must return an access token.');
expect_true(strpos($verified_payload['parent_access_token'], 'parent@example.test') === false, 'The access token must not expose the email.');
$parent_access->assert_token($verified_payload['parent_access_token'], 'parent@example.test');

$replayed = $controller->verify_parent_access(new WP_REST_Request(json_encode(array(
    'challenge_id' => $access_payload['challenge_id'],
    'email' => 'parent@example.test',
    'code' => $code_match[1],
))));
expect_true(is_wp_error($replayed), 'A verification code must be single-use.');
expect_true($replayed->get_error_data()['status'] === 422, 'A replayed code must be rejected safely.');

echo "backup-care-rest-test: OK\n";

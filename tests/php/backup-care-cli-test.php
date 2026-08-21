<?php

define('ABSPATH', __DIR__ . '/');
define('OBJECT', 'OBJECT');
define('CHROMA_BACKUP_CARE_DIR', dirname(__DIR__, 2) . '/chroma-plugins/chroma-backup-care/');
define('CHROMA_BACKUP_CARE_GHL_TOKEN', 'ghl_contract_only');
define('CHROMA_BACKUP_CARE_STRIPE_SECRET_KEY', 'sk_test_contract_only');
define('CHROMA_BACKUP_CARE_STRIPE_WEBHOOK_SECRET', 'whsec_contract_only');

$cli_options = array(
    'chroma_backup_care_mode' => 'disabled',
    'chroma_backup_care_checkout_enabled' => false,
);
$cli_pages = array();

function get_option($key, $default = false)
{
    global $cli_options;
    return array_key_exists($key, $cli_options) ? $cli_options[$key] : $default;
}

function update_option($key, $value)
{
    global $cli_options;
    $cli_options[$key] = $value;
    return true;
}

function home_url($path = '')
{
    return 'https://staging.example.test' . $path;
}

function rest_url($path = '')
{
    return 'https://staging.example.test/wp-json/' . ltrim($path, '/');
}

function wp_parse_url($url, $component = -1)
{
    return parse_url($url, $component);
}

function sanitize_email($value)
{
    return filter_var($value, FILTER_SANITIZE_EMAIL);
}

function sanitize_key($value)
{
    return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $value));
}

function get_page_by_path($slug)
{
    global $cli_pages;
    return isset($cli_pages[$slug]) ? (object) $cli_pages[$slug] : null;
}

function wp_insert_post($values, $return_error = false)
{
    global $cli_pages;
    $id = count($cli_pages) + 100;
    $cli_pages[$values['post_name']] = array(
        'ID' => $id,
        'post_status' => $values['post_status'],
    );
    return $id;
}

function is_wp_error($value)
{
    return false;
}

function get_permalink($page)
{
    return 'https://staging.example.test/?p=' . $page->ID;
}

function wp_next_scheduled($hook)
{
    return 1234567890;
}

function wp_json_encode($value, $flags = 0)
{
    return json_encode($value, $flags);
}

function expect_true($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

final class WP_CLI
{
    public static $messages = array();
    public static $commands = array();

    public static function add_command($name, $handler)
    {
        self::$commands[$name] = $handler;
    }

    public static function line($message)
    {
        self::$messages[] = $message;
    }

    public static function log($message)
    {
        self::$messages[] = $message;
    }

    public static function success($message)
    {
        self::$messages[] = $message;
    }

    public static function error($message)
    {
        throw new RuntimeException($message);
    }
}

final class Chroma_Backup_Care_Store
{
    public function closure_impact($campus_id, $care_date)
    {
        return array(
            array('request_id' => 'order-1', 'campus_id' => 'grayson', 'unit_count' => 2),
            array('request_id' => 'order-2', 'campus_id' => 'grayson', 'unit_count' => 1),
        );
    }
}

$root = dirname(__DIR__, 2);
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-config.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-cli.php';

Chroma_Backup_Care_CLI::register();
expect_true(isset(WP_CLI::$commands['chroma backup-care']), 'The Backup Care WP-CLI command was not registered.');
$command = WP_CLI::$commands['chroma backup-care'];

$command->provision_pages(array(), array());
expect_true(count($cli_pages) === 0, 'Provisioning must default to a dry run.');
expect_true(count(array_filter(WP_CLI::$messages, function ($message) {
    return strpos($message, 'WOULD_CREATE ') === 0;
})) === 3, 'Dry run must report all three missing pages.');

$wrong_confirmation_rejected = false;
try {
    $command->provision_pages(array(), array('apply' => true, 'confirm' => 'wrong'));
} catch (RuntimeException $error) {
    $wrong_confirmation_rejected = true;
}
expect_true($wrong_confirmation_rejected, 'Page provisioning must require its exact confirmation phrase.');

$command->provision_pages(array(), array(
    'apply' => true,
    'confirm' => Chroma_Backup_Care_CLI::PROVISION_CONFIRMATION,
    'status' => 'draft',
));
expect_true(count($cli_pages) === 3, 'Provisioning must create exactly three pages.');
expect_true($cli_pages['backup-care-manage']['post_status'] === 'draft', 'Provisioned pages must honor the requested status.');

$command->enable_test(array(), array('confirm' => Chroma_Backup_Care_CLI::ENABLE_TEST_CONFIRMATION));
expect_true($cli_options['chroma_backup_care_mode'] === 'test', 'Enable-test must set test mode.');
expect_true($cli_options['chroma_backup_care_checkout_enabled'] === true, 'Enable-test must set the checkout flag.');

$command->disable();
expect_true($cli_options['chroma_backup_care_mode'] === 'disabled', 'Disable must reset mode.');
expect_true($cli_options['chroma_backup_care_checkout_enabled'] === false, 'Disable must clear the checkout flag.');

$command->closure_impact(array(), array('date' => '2026-12-24', 'campus' => 'grayson'));
$impact = json_decode(end(WP_CLI::$messages), true);
expect_true($impact['changes_made'] === false, 'Closure impact must be read-only.');
expect_true($impact['order_count'] === 2, 'Closure impact must count affected orders.');
expect_true($impact['unit_count'] === 3, 'Closure impact must count affected child-date units.');
expect_true($impact['refund_exposure_cents'] === 34500, 'Closure impact must calculate full unit-price exposure.');

echo "backup-care-cli-test: OK\n";

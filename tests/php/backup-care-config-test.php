<?php

define('ABSPATH', __DIR__ . '/');
define('CHROMA_BACKUP_CARE_GHL_TOKEN', 'ghl_contract_only');

$backup_care_options = array(
    'chroma_backup_care_mode' => 'test',
    'chroma_backup_care_checkout_enabled' => true,
);

function get_option($key, $default = false)
{
    global $backup_care_options;
    return array_key_exists($key, $backup_care_options) ? $backup_care_options[$key] : $default;
}

function home_url($path = '')
{
    return 'https://staging.example.test' . $path;
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

function expect_true($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__, 2);
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-config.php';

$config = new Chroma_Backup_Care_Config($root . '/infrastructure/ghl/backup-care/manifest.json');
$test_readiness = $config->readiness();
expect_true($test_readiness['ready'], 'Test mode should be ready with the GHL credential.');
expect_true(
    $config->public_settings()['campusSelectionMode'] === 'explicit_list',
    'Backup Care must expose explicit campus selection without address geocoding.'
);
$public_forms = $config->public_settings()['forms'];
expect_true(!isset($public_forms['booking_terms']), 'The retired Booking Terms form must not be public.');
expect_true(isset($public_forms['family_profile'], $public_forms['child_enrollment']), 'Only enrollment forms must be public.');
expect_true(
    $config->notification_workflow_id('paid') === '',
    'Draft GHL workflows must not receive coordinator enrollments.'
);
$backup_care_options['chroma_backup_care_workflow_paid'] = 'explicitly_active_workflow';
expect_true(
    $config->notification_workflow_id('paid') === 'explicitly_active_workflow',
    'An explicitly configured workflow must be eligible for coordinator enrollment.'
);
unset($backup_care_options['chroma_backup_care_workflow_paid']);

$backup_care_options['chroma_backup_care_mode'] = 'live';
$live_readiness = $config->readiness();
expect_true(!$live_readiness['ready'], 'Live mode must remain blocked by release controls.');
expect_true(
    in_array('The release manifest does not authorize live changes.', $live_readiness['errors'], true),
    'Live mode must require manifest authorization.'
);
expect_true(
    in_array('Server configuration has not approved live checkout.', $live_readiness['errors'], true),
    'Live mode must require server approval.'
);
expect_true(
    count(array_filter($live_readiness['errors'], function ($error) {
        return strpos($error, 'Required release gate is incomplete:') === 0;
    })) >= 1,
    'Live mode must reject incomplete deployment gates.'
);

echo "backup-care-config-test: OK\n";

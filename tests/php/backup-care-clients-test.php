<?php

define('ABSPATH', __DIR__ . '/');
define('CHROMA_BACKUP_CARE_GHL_TOKEN', 'ghl_contract_only');

$captured_requests = array();

function get_option($key, $default = false)
{
    if ($key === 'chroma_backup_care_mode') {
        return 'test';
    }
    if ($key === 'chroma_backup_care_email_from') {
        return 'info@chromaela.com';
    }
    return $default;
}

function sanitize_email($value) { return filter_var($value, FILTER_SANITIZE_EMAIL); }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function wp_json_encode($value) { return json_encode($value); }
function is_wp_error($value) { return false; }
function wp_remote_retrieve_response_code($response) { return $response['response']['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }

function wp_remote_request($url, array $args)
{
    global $captured_requests;
    $captured_requests[] = array('url' => $url, 'args' => $args);
    if (strpos($url, '/invoices/?') !== false) {
        return fake_response(200, array('invoices' => array(array(
            '_id' => 'inv_test_matrix',
            'name' => 'Backup Care bc_matrix_test',
            'status' => 'sent',
            'liveMode' => false,
            'total' => 460,
            'currency' => 'USD',
            'contactDetails' => array('id' => 'contact_test'),
        ))));
    }
    if (substr($url, -10) === '/invoices/') {
        return fake_response(200, array('_id' => 'inv_test_matrix', 'status' => 'draft', 'liveMode' => false));
    }
    if (strpos($url, '/invoices/inv_test_matrix/send') !== false) {
        return fake_response(200, array('invoice' => array('_id' => 'inv_test_matrix', 'status' => 'sent', 'liveMode' => false)));
    }
    return fake_response(200, array(
        '_id' => 'inv_test_matrix',
        'status' => 'paid',
        'liveMode' => false,
        'amountPaid' => 460,
        'total' => 460,
        'currency' => 'USD',
        'contactDetails' => array('id' => 'contact_test'),
    ));
}

function fake_response($status, array $body)
{
    return array('response' => array('code' => $status), 'body' => json_encode($body));
}

function expect_true($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__, 2);
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-config.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-ghl-client.php';

$config = new Chroma_Backup_Care_Config($root . '/infrastructure/ghl/backup-care/manifest.json');
$client = new Chroma_Backup_Care_GHL_Client($config);
$quote = array(
    'unit_amount_cents' => 11500,
    'line_items' => array(),
);
foreach (array('a|2026-09-01', 'a|2026-09-02', 'b|2026-09-01', 'b|2026-09-02') as $index => $unit) {
    $parts = explode('|', $unit);
    $quote['line_items'][] = array(
        'care_date' => $parts[1],
        'line_item_key' => 'bcu_test_' . $index,
    );
}
$parent = array(
    'first_name' => 'Charan',
    'last_name' => 'Test',
    'email' => 'charancre@gmail.com',
    'mobile_phone' => '+1 404 555 0100',
);
$campus = $config->campus('grayson');
$invoice = $client->create_backup_care_invoice('bc_matrix_test', $parent, 'contact_test', $campus, $quote);
expect_true($invoice['_id'] === 'inv_test_matrix', 'GHL invoice ID was not returned.');
$create = json_decode($captured_requests[0]['args']['body'], true);
expect_true($create['liveMode'] === false, 'Acceptance invoices must be in GHL test mode.');
expect_true(count($create['items']) === 4, 'Two children across two dates must create four invoice line items.');
expect_true(array_sum(array_column($create['items'], 'amount')) === 460, 'The GHL invoice must total $460.');
expect_true($create['contactDetails']['email'] === 'charancre@gmail.com', 'The acceptance invoice recipient is incorrect.');
expect_true(isset($create['paymentMethods']['stripe']), 'The invoice must use Stripe connected inside GHL.');

$projection = $config->calendar_projection('grayson');
$client->send_backup_care_invoice($invoice['_id'], $projection['assigned_user_id']);
$send = json_decode($captured_requests[1]['args']['body'], true);
expect_true($send['action'] === 'email' && $send['liveMode'] === false, 'The GHL test invoice must be sent by email.');

$paid = $client->get_invoice($invoice['_id']);
expect_true($paid['status'] === 'paid' && $paid['amountPaid'] === 460, 'Paid GHL invoice readback failed.');
$recovered = $client->find_backup_care_invoice('bc_matrix_test', 'contact_test');
expect_true($recovered['_id'] === 'inv_test_matrix', 'Invoice recovery must prevent duplicate GHL invoices after an uncertain API response.');

echo "backup-care-clients-test: OK\n";

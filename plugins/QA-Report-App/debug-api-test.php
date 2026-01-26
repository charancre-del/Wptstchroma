<?php
// Load WordPress
require_once dirname(__DIR__, 4) . '/wp-load.php';

// Check if we are logged in. If not, try to impersonate user ID 1 (Admin)
if (!is_user_logged_in()) {
    wp_set_current_user(1);
}

echo "Current User ID: " . get_current_user_id() . "\n";

// Test Schools Endpoint
$request = new WP_REST_Request('GET', '/cqa/v1/schools');
$response = rest_do_request($request);
$schools = $response->get_data();

echo "Schools Status: " . $response->get_status() . "\n";
echo "Schools Count: " . (is_array($schools) ? count($schools) : 'Not Array') . "\n";

// Test Reports Endpoint
$request = new WP_REST_Request('GET', '/cqa/v1/reports');
$response = rest_do_request($request);
$reports = $response->get_data();

echo "Reports Status: " . $response->get_status() . "\n";
echo "Reports Count: " . (is_array($reports) ? count($reports) : 'Not Array') . "\n";

// Dump raw data if empty
if (empty($schools)) {
    global $wpdb;
    echo "Direct DB Count (cqa_schools): " . $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cqa_schools") . "\n";
}

<?php
/**
 * Diagnostic Script for Chroma White Screen Issue
 * 
 * This script attempts to load the WordPress environment and check for common issues
 * that cause a white screen in the admin.
 */

// Load WordPress
define('WP_USE_THEMES', false);
define('SHORTINIT', false); // Get full environment to check all hooks

$wp_load_path = __DIR__ . '/wp-load.php';
if (!file_exists($wp_load_path)) {
    // Try parent directory
    $wp_load_path = dirname(__DIR__) . '/wp-load.php';
}

if (!file_exists($wp_load_path)) {
    die("Could not find wp-load.php. Please place this script in the WP root or a subdirectory.\n");
}

require_once $wp_load_path;

echo "WordPress Environment Loaded Successfully.\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";

// Check for active plugins
$active_plugins = get_option('active_plugins');
echo "Active Plugins Count: " . count($active_plugins) . "\n";

// Check for fatal errors in metabox registration
echo "Checking metabox registration...\n";
try {
    do_action('add_meta_boxes');
    echo "Metabox registration complete (no fatals).\n";
} catch (Throwable $e) {
    echo "FATAL ERROR in metaboxes: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

// Check REST API
echo "Checking REST API status...\n";
if (function_exists('rest_get_server')) {
    $server = rest_get_server();
    echo "REST Server initialized.\n";
} else {
    echo "REST API not initialized.\n";
}

// Check for unexpected output (which breaks AJAX/REST)
if (ob_get_length()) {
    echo "WARNING: Unexpected output detected (length: " . ob_get_length() . "). This can break the editor.\n";
    echo "Buffer content: " . ob_get_clean() . "\n";
} else {
    echo "No unexpected output detected.\n";
}

echo "Diagnostic complete.\n";

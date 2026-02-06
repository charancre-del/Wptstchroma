<?php
// Strict Baseline Inventory (Step 0)
require_once('wp-load.php');

echo "========== WP BASELINE INVENTORY ==========\n";
echo "Site URL: " . site_url() . "\n";
echo "Home URL: " . home_url() . "\n";
echo "SSL: " . (is_ssl() ? 'Yes' : 'No') . "\n";
echo "WP Version: " . $GLOBALS['wp_version'] . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Active Plugins:\n";
$plugins = get_option('active_plugins');
if ($plugins) {
    foreach ($plugins as $p) {
        echo " - " . $p . "\n";
    }
} else {
    echo " - None or error retrieving\n";
}

echo "\n--- COOKIE CONSTANTS ---\n";
echo "COOKIE_DOMAIN: " . (defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '(undefined)') . "\n";
echo "COOKIE_PATH: " . (defined('COOKIEPATH') ? COOKIEPATH : '(undefined)') . "\n";
echo "SITECOOKIEPATH: " . (defined('SITECOOKIEPATH') ? SITECOOKIEPATH : '(undefined)') . "\n";
echo "ADMIN_COOKIE_PATH: " . (defined('ADMIN_COOKIE_PATH') ? ADMIN_COOKIE_PATH : '(undefined)') . "\n";

echo "\n--- SECURITY ---\n";
echo "FORCE_SSL_ADMIN: " . (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN ? 'Yes' : 'No') . "\n";
echo "AUTH_KEY: " . (defined('AUTH_KEY') ? 'Present' : 'MISSING') . "\n";
echo "LOGGED_IN_COOKIE: " . (defined('LOGGED_IN_COOKIE') ? LOGGED_IN_COOKIE : 'Standard') . "\n";
echo "===========================================\n";

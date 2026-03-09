<?php
require_once __DIR__ . '/wp-load.php';

$sitemaps = [
    home_url('/wp-sitemap-custom-combos-1.xml'),
    home_url('/wp-sitemap-custom-near-me-1.xml'),
    home_url('/wp-sitemap.xml')
];

foreach ($sitemaps as $url) {
    echo "Fetching: $url\n";
    $resp = wp_remote_get($url, ['sslverify' => false, 'timeout' => 15]);
    if (is_wp_error($resp)) {
        echo "Error: " . $resp->get_error_message() . "\n\n";
    } else {
        echo "HTTP Code: " . wp_remote_retrieve_response_code($resp) . "\n";
        echo "Body snippet: " . substr(wp_remote_retrieve_body($resp), 0, 150) . "...\n\n";
    }
}
echo "Done.\n";

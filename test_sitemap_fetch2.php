<?php

function chroma_sitemap_test_find_wp_load($start_dir)
{
    $dir = $start_dir;
    while ($dir && is_dir($dir)) {
        $candidate = $dir . DIRECTORY_SEPARATOR . 'wp-load.php';
        if (file_exists($candidate)) {
            return $candidate;
        }

        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }

    return false;
}

$wp_load = chroma_sitemap_test_find_wp_load(__DIR__);
if (!$wp_load) {
    $message = "Unable to locate wp-load.php. Run this script from within a WordPress install or place it under the WordPress root.\n";
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message);
    } else {
        http_response_code(500);
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    }
    exit(1);
}

require_once $wp_load;

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

<?php

function chroma_maintenance_find_wp_load($start_dir)
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

$wp_load = chroma_maintenance_find_wp_load(__DIR__);
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

// Flush rewrite rules
echo "Flushing rewrite rules...\n";
flush_rewrite_rules();
echo "Flushed rewrite rules.\n";

// Clear object cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "Flushed object cache.\n";
}

// Clear LiteSpeed Cache if active
if (class_exists('LiteSpeed\Purge')) {
    LiteSpeed\Purge::purge_all();
    echo "Flushed LiteSpeed cache.\n";
} elseif (has_action('litespeed_purge_all')) {
    do_action('litespeed_purge_all');
    echo "Flushed LiteSpeed cache via action.\n";
}

// Clear W3TC if active
if (function_exists('w3tc_flush_all')) {
    w3tc_flush_all();
    echo "Flushed W3 Total Cache.\n";
}

echo "Cache clear script finished.\n";

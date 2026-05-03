<?php
require_once __DIR__ . '/wp-load.php';

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

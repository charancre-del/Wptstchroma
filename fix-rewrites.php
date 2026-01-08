<?php
// Place this in theme root or run via WP-CLI
require_once('wp-load.php');

echo "Flushing rewrite rules...\n";
flush_rewrite_rules();
echo "Rewrite rules flushed.\n";

global $wp_rewrite;
echo "Current structure: " . $wp_rewrite->permalink_structure . "\n";

<?php
define('WP_USE_THEMES', false);
require_once dirname(__FILE__) . '/wp-load.php';

header('Content-Type: text/plain');

echo "--- Rewrite Rules Diagnostic ---\n";

global $wp_rewrite;
$rules = get_option('rewrite_rules');

if (!$rules) {
    echo "NO REWRITE RULES FOUND IN DATABASE!\n";
} else {
    $found = false;
    foreach ($rules as $regex => $query) {
        if (strpos($regex, 'portal') !== false) {
            echo "Match: $regex => $query\n";
            $found = true;
        }
    }
    if (!$found) {
        echo "No 'portal' rules found in active rules.\n";
    }
}

echo "\n--- Registered Query Vars ---\n";
global $wp;
$wp->add_query_var('chroma_view');
echo "Checking 'chroma_view' in query_vars...\n";
if (in_array('chroma_view', $wp->public_query_vars)) {
    echo "SUCCESS: 'chroma_view' is a public query var.\n";
} else {
    echo "ERROR: 'chroma_view' is NOT a public query var.\n";
}

echo "\n--- Testing Match ---\n";
$url = 'portal/';
$match = $wp_rewrite->wp_rewrite_rules();
$matches_found = false;
foreach ($rules as $match => $query) {
    if (preg_match("#^$match#", $url, $matches)) {
        echo "URL '$url' Matches Rule: $match\n";
        echo "Internal Query: " . preg_replace("#^$match#", $query, $url) . "\n";
        $matches_found = true;
        break;
    }
}

if (!$matches_found) {
    echo "URL '$url' DID NOT MATCH any rules.\n";
}

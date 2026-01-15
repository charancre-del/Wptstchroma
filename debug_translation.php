<?php
require_once 'wp-load.php';

$page = get_page_by_path('about');
if (!$page) {
    // try about-us
    $page = get_page_by_path('about-us');
}

if ($page) {
    echo "Files Found: " . $page->ID . "\n";
    echo "Title: " . $page->post_title . "\n";
    $es_content = get_post_meta($page->ID, '_chroma_es_content', true);
    $es_title = get_post_meta($page->ID, '_chroma_es_title', true);
    
    echo "ES Title Meta: " . ($es_title ? $es_title : "[EMPTY]") . "\n";
    echo "ES Content Meta Length: " . strlen($es_content) . "\n";
    echo "ES Content Preview: " . substr(strip_tags($es_content), 0, 100) . "...\n";
} else {
    echo "About Page Not Found.\n";
    // List all pages to find it
    $pages = get_posts(['post_type'=>'page', 'posts_per_page'=>20]);
    foreach($pages as $p) {
        echo $p->ID . ": " . $p->post_name . " (" . $p->post_title . ")\n";
    }
}

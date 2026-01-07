<?php
// Place in theme root or root of WP installation
require_once dirname(__FILE__) . '/wp-load.php';

$posts = get_posts([
    'post_type' => 'location',
    'posts_per_page' => 1
]);

echo "<pre>";
if ($posts) {
    $id = $posts[0]->ID;
    echo "Inspecting Location ID: $id (" . $posts[0]->post_title . ")\n\n";
    $meta = get_post_meta($id);
    foreach ($meta as $k => $v) {
        if (strpos($k, '_chroma_es_') !== false || strpos($k, 'location_') !== false) {
            echo "$k: " . print_r($v[0], true) . "\n";
        }
    }
} else {
    echo "No locations found.\n";
}
echo "</pre>";

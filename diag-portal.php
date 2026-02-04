<?php
require_once 'wp-load.php';
global $post;
echo "is_front_page: " . (is_front_page() ? 'true' : 'false') . "\n";
echo "is_home: " . (is_home() ? 'true' : 'false') . "\n";
echo "post_name: " . ($post ? $post->post_name : 'null') . "\n";
echo "is_page('parent-portal'): " . (is_page('parent-portal') ? 'true' : 'false') . "\n";
if ($post) {
    echo "has_shortcode: " . (has_shortcode($post->post_content, 'chroma_parent_portal') ? 'true' : 'false') . "\n";
}
@unlink(__FILE__);

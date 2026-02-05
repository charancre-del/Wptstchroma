<?php
define('WP_USE_THEMES', false);
require_once 'c:/Users/chara/Documents/wptheme/Wptstchroma/wp-load.php';

echo "--- Portal Year Terms ---\n";
$terms = get_terms(['taxonomy' => 'portal_year', 'hide_empty' => false]);
if (is_wp_error($terms)) {
    echo "Error: " . $terms->get_error_message() . "\n";
} else {
    foreach ($terms as $t) {
        echo "Term: {$t->name} (ID: {$t->term_id})\n";
    }
}

$types = ['cp_announcement', 'cp_lesson_plan', 'cp_home_activity', 'cp_meal_plan', 'cp_resource', 'cp_form', 'cp_event'];
foreach ($types as $type) {
    echo "\n--- $type Count ---\n";
    $count = wp_count_posts($type);
    echo "Publish: " . ($count->publish ?? 0) . "\n";

    $latest = get_posts(['post_type' => $type, 'posts_per_page' => 1]);
    if (!empty($latest)) {
        echo "Latest: {$latest[0]->post_title} (ID: {$latest[0]->ID})\n";
        $pts = wp_get_post_terms($latest[0]->ID, 'portal_year');
        foreach ($pts as $t)
            echo "  - Year: {$t->name}\n";
    }
}

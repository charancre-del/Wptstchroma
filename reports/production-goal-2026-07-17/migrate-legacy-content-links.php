<?php
/**
 * Dry-run by default. Run with CHROMA_APPLY=1 to persist normalized links.
 *
 * Example:
 * CHROMA_APPLY=1 wp eval-file migrate-legacy-content-links.php
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "Load this file through WP-CLI.\n");
    exit(1);
}

if (!function_exists('chroma_normalize_legacy_content_links')) {
    fwrite(STDERR, "The Chroma Excellence Theme 2.0 cleanup helpers are not loaded.\n");
    exit(1);
}

$apply = getenv('CHROMA_APPLY') === '1';
$posts = get_posts([
    'post_type' => 'any',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => 'ID',
    'order' => 'ASC',
]);

$changed = 0;
$failed = 0;

foreach ($posts as $post) {
    $before = (string) $post->post_content;
    if ($before === '') {
        continue;
    }

    $after = chroma_normalize_legacy_content_links($before);
    if (!is_string($after) || $after === $before) {
        continue;
    }

    $changed++;
    $label = sprintf('%d %s %s', $post->ID, $post->post_type, $post->post_name);
    if (!$apply) {
        echo "DRY-RUN {$label}\n";
        continue;
    }

    $result = wp_update_post([
        'ID' => $post->ID,
        'post_content' => wp_slash($after),
    ], true);

    if (is_wp_error($result)) {
        $failed++;
        fwrite(STDERR, "FAILED {$label}: {$result->get_error_message()}\n");
        continue;
    }

    echo "UPDATED {$label}\n";
}

echo sprintf(
    "%s complete: %d candidate(s), %d failure(s).\n",
    $apply ? 'Migration' : 'Dry run',
    $changed,
    $failed
);

exit($failed > 0 ? 1 : 0);

<?php
/**
 * Remove confirmed off-topic UK childcare copy from legacy staging records.
 *
 * Usage:
 *   wp eval-file wp-content/themes/chroma-excellence-theme/tools/migrate-goal-content-cleanup.php
 *   CHROMA_APPLY=1 wp eval-file wp-content/themes/chroma-excellence-theme/tools/migrate-goal-content-cleanup.php
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "Run this script with WP-CLI eval-file.\n");
    exit(1);
}

$apply = getenv('CHROMA_APPLY') === '1';
$post_ids = [5172, 5593];
$removed_phrases = [
    'While specific UK government proposals are mentioned',
    "Understanding Childcare Ratios: Impact on Children's Outcomes",
    'Understanding Childcare Ratios: Impact on Children’s Outcomes The UK Government',
    "Understanding Childcare Ratios: Impact on Children's Outcomes The UK Government",
    'Understanding Childcare Ratios: Impact on Children&#8217;s Outcomes The UK Government',
];

function chroma_goal_cleanup_mojibake($content)
{
    return strtr((string) $content, [
        'â€™' => '’',
        'â€œ' => '“',
        'â€' => '”',
        'â€“' => '–',
        'â€”' => '—',
        'Â®' => '®',
        'Â™' => '™',
        'Â' => '',
    ]);
}

function chroma_goal_remove_matching_blocks($content, array $phrases, &$removed_blocks)
{
    $removed_blocks = [];
    $content = (string) $content;

    $patterns = [
        '#<!--\s*wp:quote[^>]*-->.*?<!--\s*/wp:quote\s*-->#isu',
        '#<!--\s*wp:paragraph[^>]*-->\s*<p[^>]*>.*?</p>\s*<!--\s*/wp:paragraph\s*-->#isu',
        '#<p[^>]*>.*?</p>#isu',
    ];

    foreach ($patterns as $pattern) {
        $content = preg_replace_callback($pattern, static function ($matches) use (&$removed_blocks, $phrases) {
            $plain_text = html_entity_decode(wp_strip_all_tags($matches[0]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $matches_target = false;

            foreach ($phrases as $phrase) {
                if (stripos($plain_text, html_entity_decode($phrase, ENT_QUOTES | ENT_HTML5, 'UTF-8')) !== false) {
                    $matches_target = true;
                    break;
                }
            }

            if (!$matches_target) {
                return $matches[0];
            }

            $removed_blocks[] = wp_strip_all_tags($matches[0]);
            return '';
        }, $content);
    }

    return $content;
}

foreach ($post_ids as $post_id) {
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        WP_CLI::warning("Post {$post_id} was not found.");
        continue;
    }

    $removed_blocks = [];
    $updated_content = chroma_goal_remove_matching_blocks($post->post_content, $removed_phrases, $removed_blocks);
    $updated_content = chroma_goal_cleanup_mojibake($updated_content);

    if (function_exists('chroma_normalize_legacy_content_links')) {
        $updated_content = chroma_normalize_legacy_content_links($updated_content);
    }

    $changed = $updated_content !== $post->post_content;
    WP_CLI::log(sprintf(
        '[%s] ID %d (%s): changed=%s removed_blocks=%d',
        $apply ? 'APPLY' : 'DRY RUN',
        $post_id,
        $post->post_status,
        $changed ? 'yes' : 'no',
        count($removed_blocks)
    ));

    foreach ($removed_blocks as $index => $block) {
        WP_CLI::log('  removed ' . ($index + 1) . ': ' . wp_trim_words($block, 24, '…'));
    }

    if (!$apply || !$changed) {
        continue;
    }

    $result = wp_update_post([
        'ID' => $post_id,
        'post_content' => $updated_content,
    ], true);

    if (is_wp_error($result)) {
        WP_CLI::error("Could not update post {$post_id}: " . $result->get_error_message(), false);
        continue;
    }

    clean_post_cache($post_id);
    WP_CLI::success("Updated post {$post_id}.");
}

WP_CLI::success($apply ? 'Content cleanup applied.' : 'Dry run complete. Set CHROMA_APPLY=1 to apply changes.');

<?php
/**
 * Chroma WP-CLI Commands
 * Adds translation management commands to WP-CLI.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_CLI')) {
    return;
}

class Chroma_CLI_Commands
{
    /**
     * Translate a single post to Spanish.
     *
     * ## OPTIONS
     *
     * <post_id>
     * : The ID of the post to translate.
     *
     * ## EXAMPLES
     *
     *     wp chroma translate 123
     *
     * @when after_wp_load
     */
    public function translate($args, $assoc_args)
    {
        $post_id = intval($args[0]);
        $post = get_post($post_id);

        if (!$post) {
            WP_CLI::error("Post $post_id not found.");
            return;
        }

        WP_CLI::log("Translating post $post_id: {$post->post_title}...");

        $fields = [
            '_chroma_es_title' => $post->post_title,
            '_chroma_es_content' => $post->post_content,
            '_chroma_es_excerpt' => $post->post_excerpt,
        ];

        $translated = Chroma_Translation_Engine::translate_bulk($fields, 'es', 'Translate for a childcare website.');

        if (isset($translated['_error'])) {
            WP_CLI::error("Translation failed: " . $translated['_error']);
            return;
        }

        foreach ($translated as $key => $value) {
            if (strpos($key, '_chroma_es_') === 0 && !empty($value)) {
                update_post_meta($post_id, $key, $value);
            }
        }

        WP_CLI::success("Post $post_id translated successfully.");
    }

    /**
     * Translate all posts of a given type.
     *
     * ## OPTIONS
     *
     * [--post-type=<type>]
     * : The post type to translate. Default: page
     *
     * [--force]
     * : Retranslate even if Spanish content exists.
     *
     * ## EXAMPLES
     *
     *     wp chroma translate-all --post-type=location
     *
     * @when after_wp_load
     */
    public function translate_all($args, $assoc_args)
    {
        $post_type = isset($assoc_args['post-type']) ? $assoc_args['post-type'] : 'page';
        $force = isset($assoc_args['force']);

        $posts = get_posts([
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $count = 0;
        $total = count($posts);

        WP_CLI::log("Found $total {$post_type}s to process.");

        foreach ($posts as $post) {
            $has_translation = get_post_meta($post->ID, '_chroma_es_content', true);
            
            if ($has_translation && !$force) {
                WP_CLI::log("Skipping {$post->ID} (already translated)");
                continue;
            }

            WP_CLI::log("Translating {$post->ID}: {$post->post_title}...");
            
            $fields = [
                '_chroma_es_title' => $post->post_title,
                '_chroma_es_content' => $post->post_content,
                '_chroma_es_excerpt' => $post->post_excerpt,
            ];

            $translated = Chroma_Translation_Engine::translate_bulk($fields, 'es', 'Translate for a childcare website.');

            if (!isset($translated['_error'])) {
                foreach ($translated as $key => $value) {
                    if (strpos($key, '_chroma_es_') === 0 && !empty($value)) {
                        update_post_meta($post->ID, $key, $value);
                    }
                }
                $count++;
            }
        }

        WP_CLI::success("Translated $count of $total {$post_type}s.");
    }

    /**
     * Flush translation memory cache.
     *
     * ## EXAMPLES
     *
     *     wp chroma flush-cache
     *
     * @when after_wp_load
     */
    public function flush_cache($args, $assoc_args)
    {
        global $wpdb;
        
        // Use prepared statements with esc_like for security
        $like_pattern = $wpdb->esc_like('_transient_chroma_trans_') . '%';
        $timeout_pattern = $wpdb->esc_like('_transient_timeout_chroma_trans_') . '%';
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $like_pattern,
                $timeout_pattern
            )
        );

        WP_CLI::success("Flushed translation memory cache. Removed $deleted entries.");
    }

    /**
     * Show translation statistics.
     *
     * ## EXAMPLES
     *
     *     wp chroma stats
     *
     * @when after_wp_load
     */
    public function stats($args, $assoc_args)
    {
        $post_types = ['page', 'location', 'program'];
        $stats = [];

        foreach ($post_types as $type) {
            $total = wp_count_posts($type)->publish;
            
            $translated = $GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$GLOBALS['wpdb']->postmeta} 
                 WHERE meta_key = '_chroma_es_content' AND meta_value != ''
                 AND post_id IN (SELECT ID FROM {$GLOBALS['wpdb']->posts} WHERE post_type = %s AND post_status = 'publish')",
                $type
            ));

            $stats[$type] = [
                'total' => $total,
                'translated' => $translated,
                'percent' => $total > 0 ? round(($translated / $total) * 100) : 0
            ];
        }

        WP_CLI::log("\n📊 Translation Statistics\n");
        
        foreach ($stats as $type => $data) {
            $bar = str_repeat('█', (int)($data['percent'] / 5)) . str_repeat('░', 20 - (int)($data['percent'] / 5));
            WP_CLI::log(sprintf(
                "%s: %s %d%% (%d/%d)",
                str_pad(ucfirst($type), 12),
                $bar,
                $data['percent'],
                $data['translated'],
                $data['total']
            ));
        }

        WP_CLI::log("");
    }
}

WP_CLI::add_command('chroma', 'Chroma_CLI_Commands');



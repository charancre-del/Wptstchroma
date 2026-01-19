<?php
/**
 * Career Feed Sync
 *
 * Synchronizes jobs from external feed into 'career' CPT.
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Career_Sync
{
    /**
     * Init
     */
    public static function init()
    {
        add_action('chroma_career_sync_event', [__CLASS__, 'run_sync']);
        add_action('wp_ajax_chroma_sync_careers', [__CLASS__, 'ajax_run_sync']);
        
        // Schedule if not scheduled
        if (!wp_next_scheduled('chroma_career_sync_event')) {
            wp_schedule_event(time(), 'hourly', 'chroma_career_sync_event');
        }
    }

    /**
     * AJAX Trigger for UI
     */
    public static function ajax_run_sync()
    {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $result = self::run_sync();
        
        if (isset($result['error'])) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success($result);
    }

    /**
     * Run the Sync Process
     */
    public static function run_sync()
    {
        // Sync Disabled by User Request
        return ['count' => 0, 'message' => 'Sync disabled'];
    }

    /**
     * Find post by external URL
     */
    private static function get_post_by_job_url($url)
    {
        $args = [
            'post_type' => 'career',
            'meta_key' => '_career_external_url',
            'meta_value' => $url,
            'posts_per_page' => 1,
            'post_status' => 'any',
            'no_found_rows' => true
        ];
        $query = new WP_Query($args);
        return $query->have_posts() ? $query->posts[0] : null;
    }

    /**
     * Trash jobs not in the current list
     */
    private static function prune_old_jobs($active_urls)
    {
        $all_jobs = get_posts([
            'post_type' => 'career',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ]);

        $trashed = 0;
        foreach ($all_jobs as $job_id) {
            $job_url = get_post_meta($job_id, '_career_external_url', true);
            if ($job_url && !in_array($job_url, $active_urls)) {
                wp_trash_post($job_id);
                $trashed++;
            }
        }
        return $trashed;
    }
}



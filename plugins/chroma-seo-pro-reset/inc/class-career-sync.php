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
        if (!class_exists('Chroma_Careers_API')) {
            return ['error' => 'Careers API class not found'];
        }

        // Get jobs from feed
        $jobs = Chroma_Careers_API::get_careers(true); // Force refresh

        if (empty($jobs)) {
            update_option('chroma_last_career_sync', current_time('mysql'));
            update_option('chroma_last_career_sync_count', 0);
            return ['count' => 0, 'message' => 'No jobs found in feed'];
        }

        $active_job_urls = [];
        $created_count = 0;
        $updated_count = 0;

        foreach ($jobs as $job) {
            $title = sanitize_text_field($job['title']);
            $url = esc_url_raw($job['url']);
            $location = sanitize_text_field($job['location']);
            $type = isset($job['type']) ? sanitize_text_field($job['type']) : 'Full Time';

            $active_job_urls[] = $url;

            // Check if post exists by unique URL meta
            $existing_post = self::get_post_by_job_url($url);

            if ($existing_post) {
                // Update existing
                $post_id = $existing_post->ID;
                $current_title = get_the_title($post_id);
                
                if ($current_title !== $title) {
                    wp_update_post([
                        'ID' => $post_id,
                        'post_title' => $title,
                    ]);
                    $updated_count++;
                }
            } else {
                // Create new
                $post_id = wp_insert_post([
                    'post_type' => 'career',
                    'post_title' => $title,
                    'post_status' => 'publish',
                    'post_content' => '',
                ]);
                $created_count++;
            }

            if ($post_id && !is_wp_error($post_id)) {
                // Update Meta
                update_post_meta($post_id, '_career_external_url', $url);
                update_post_meta($post_id, '_career_location', $location);
                update_post_meta($post_id, '_career_type', $type);
                
                if (!get_post_meta($post_id, '_career_date_posted', true)) {
                    update_post_meta($post_id, '_career_date_posted', current_time('Y-m-d'));
                }
            }
        }

        // Pruning
        $trashed_count = self::prune_old_jobs($active_job_urls);

        update_option('chroma_last_career_sync', current_time('mysql'));
        update_option('chroma_last_career_sync_count', count($jobs));

        return [
            'total' => count($jobs),
            'created' => $created_count,
            'updated' => $updated_count,
            'trashed' => $trashed_count,
            'timestamp' => current_time('mysql')
        ];
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

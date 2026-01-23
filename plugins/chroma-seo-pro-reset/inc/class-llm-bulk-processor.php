<?php
/**
 * LLM Bulk Processor
 * Queue and process bulk schema generation using WP Cron
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_LLM_Bulk_Processor
{
    const QUEUE_OPTION = 'chroma_llm_bulk_queue';
    const STATUS_OPTION = 'chroma_llm_bulk_status';
    const CRON_HOOK = 'chroma_llm_process_queue';
    
    public function __construct() {
        // Register cron hook
        add_action(self::CRON_HOOK, [$this, 'process_next_item']);
        
        // Register AJAX handlers
        add_action('wp_ajax_chroma_bulk_generate_start', [$this, 'ajax_start_bulk']);
        add_action('wp_ajax_chroma_bulk_generate_status', [$this, 'ajax_get_status']);
        add_action('wp_ajax_chroma_bulk_generate_cancel', [$this, 'ajax_cancel']);
    }
    
    /**
     * Queue posts for bulk generation
     */
    public function queue_posts($post_ids, $type = 'schema') {
        $queue = get_option(self::QUEUE_OPTION, []);
        
        foreach ($post_ids as $post_id) {
            $queue[] = [
                'post_id' => intval($post_id),
                'type' => $type,
                'status' => 'pending',
                'queued_at' => current_time('mysql')
            ];
        }
        
        update_option(self::QUEUE_OPTION, $queue);
        
        // Update status
        $status = [
            'total' => count($queue),
            'completed' => 0,
            'failed' => 0,
            'in_progress' => true,
            'started_at' => current_time('mysql')
        ];
        update_option(self::STATUS_OPTION, $status);
        
        // Schedule first processing
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time() + 5, self::CRON_HOOK);
        }
        
        return count($queue);
    }
    
    /**
     * Process next item in queue
     */
    public function process_next_item() {
        $queue = get_option(self::QUEUE_OPTION, []);
        
        if (empty($queue)) {
            $this->mark_complete();
            return;
        }
        
        // Find next pending item
        $next_index = null;
        foreach ($queue as $index => $item) {
            if ($item['status'] === 'pending') {
                $next_index = $index;
                break;
            }
        }
        
        if ($next_index === null) {
            $this->mark_complete();
            return;
        }
        
        $item = $queue[$next_index];
        $queue[$next_index]['status'] = 'processing';
        update_option(self::QUEUE_OPTION, $queue);
        
        // Process the item
        $success = $this->generate_for_post($item['post_id'], $item['type']);
        
        // Update queue
        $queue[$next_index]['status'] = $success ? 'completed' : 'failed';
        $queue[$next_index]['completed_at'] = current_time('mysql');
        update_option(self::QUEUE_OPTION, $queue);
        
        // Update status
        $status = get_option(self::STATUS_OPTION, []);
        if ($success) {
            $status['completed'] = ($status['completed'] ?? 0) + 1;
        } else {
            $status['failed'] = ($status['failed'] ?? 0) + 1;
        }
        update_option(self::STATUS_OPTION, $status);
        
        // Schedule next processing (with rate limiting)
        $delay = 5; // 5 seconds between requests
        wp_schedule_single_event(time() + $delay, self::CRON_HOOK);
    }
    
    /**
     * Generate schema/SEO for a post
     */
    private function generate_for_post($post_id, $type) {
        global $chroma_llm_client;
        
        if (!$chroma_llm_client) {
            return false;
        }
        
        try {
            if ($type === 'schema') {
                // Get appropriate schema type for post
                $schema_type = $this->detect_schema_type($post_id);
                
                // Generate schema data
                $result = $chroma_llm_client->generate_schema_data($post_id, $schema_type, []);
                
                if (!is_wp_error($result)) {
                    // Save to post meta
                    $existing = get_post_meta($post_id, '_chroma_schema_data', true) ?: [];
                    $existing[] = [
                        'type' => $schema_type,
                        'data' => $result
                    ];
                    update_post_meta($post_id, '_chroma_schema_data', $existing);
                    return true;
                }
            }
            elseif ($type === 'amenities') {
                // Tier 5: Safety Amenities Extraction
                $amenities = $chroma_llm_client->generate_amenities_data($post_id);
                
                if (is_wp_error($amenities)) {
                     chroma_debug_log('[Chroma Bulk] Amenities Error: ' . $amenities->get_error_message());
                     return false;
                }
                
                if (is_array($amenities)) {
                    update_post_meta($post_id, '_chroma_amenities', $amenities);
                    return true;
                }
            }
        } catch (Exception $e) {
            chroma_debug_log('[Chroma Bulk] Error processing post ' . $post_id . ': ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Detect best schema type for post
     */
    private function detect_schema_type($post_id) {
        $post_type = get_post_type($post_id);
        
        $type_map = [
            'location' => 'ChildCare',
            'program' => 'Course',
            'post' => 'Article',
            'page' => 'Article',
            'team_member' => 'Person',
            'job_listing' => 'JobPosting',
            'event' => 'Event'
        ];
        
        return $type_map[$post_type] ?? 'Article';
    }
    
    /**
     * Mark processing as complete
     */
    private function mark_complete() {
        $status = get_option(self::STATUS_OPTION, []);
        $status['in_progress'] = false;
        $status['completed_at'] = current_time('mysql');
        update_option(self::STATUS_OPTION, $status);
        
        // Clear cron
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }
    
    /**
     * Get queue status
     */
    public static function get_status() {
        return get_option(self::STATUS_OPTION, [
            'total' => 0,
            'completed' => 0,
            'failed' => 0,
            'in_progress' => false
        ]);
    }
    
    /**
     * Get current queue
     */
    public static function get_queue() {
        return get_option(self::QUEUE_OPTION, []);
    }
    
    /**
     * Cancel bulk processing
     */
    public function cancel() {
        wp_clear_scheduled_hook(self::CRON_HOOK);
        delete_option(self::QUEUE_OPTION);
        
        $status = get_option(self::STATUS_OPTION, []);
        $status['in_progress'] = false;
        $status['cancelled'] = true;
        $status['cancelled_at'] = current_time('mysql');
        update_option(self::STATUS_OPTION, $status);
    }
    
    /**
     * Detect content gaps across site
     */
    public static function detect_gaps($post_types = ['location', 'program']) {
        $gaps = [];
        
        $posts = get_posts([
            'post_type' => $post_types,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        foreach ($posts as $post) {
            $schema = get_post_meta($post->ID, '_chroma_schema_data', true);
            $missing = [];
            
            // Check for critical missing fields
            if (empty($schema)) {
                $missing[] = 'No schema at all';
            } else {
                // Check specific fields
                foreach ($schema as $s) {
                    $data = $s['data'] ?? [];
                    if (empty($data['description'])) $missing[] = 'description';
                    if (empty($data['telephone']) && empty($data['phone'])) $missing[] = 'telephone';
                    if (empty($data['geo_lat']) && empty($data['geo'])) $missing[] = 'geo coordinates';
                    if (empty($data['aggregateRating'])) $missing[] = 'aggregateRating';
                }
            }
            
            // Check GMB sync
            $last_sync = get_post_meta($post->ID, '_gmb_last_sync', true);
            if (empty($last_sync)) {
                $missing[] = 'GMB not synced';
            }
            
            if (!empty($missing)) {
                $gaps[$post->ID] = [
                    'title' => $post->post_title,
                    'post_type' => $post->post_type,
                    'missing' => array_unique($missing)
                ];
            }
        }
        
        return $gaps;
    }
    
    /**
     * AJAX: Start bulk generation
     */
    public function ajax_start_bulk() {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
        $type = sanitize_text_field($_POST['type'] ?? 'schema');
        
        if (empty($post_ids)) {
            wp_send_json_error(['message' => 'No posts selected']);
        }
        
        $count = $this->queue_posts($post_ids, $type);
        
        wp_send_json_success([
            'message' => "Queued $count posts for processing",
            'queued' => $count
        ]);
    }
    
    /**
     * AJAX: Get status
     */
    public function ajax_get_status() {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        
        wp_send_json_success([
            'status' => self::get_status(),
            'queue' => self::get_queue()
        ]);
    }
    
    /**
     * AJAX: Cancel
     */
    public function ajax_cancel() {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $this->cancel();
        
        wp_send_json_success(['message' => 'Cancelled']);
    }
}

// Initialize
new Chroma_LLM_Bulk_Processor();



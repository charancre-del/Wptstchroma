<?php
/**
 * Schema Review Queue
 * Manages human review for low-confidence or flagged schema generations
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Schema_Review_Queue
{
    const QUEUE_OPTION = 'chroma_schema_review_queue';
    
    public function __construct() {
        add_action('wp_ajax_chroma_review_schema', [$this, 'ajax_review']);
        add_action('wp_ajax_chroma_get_review_queue', [$this, 'ajax_get_queue']);
    }
    
    /**
     * Flag a post for review
     */
    public static function flag_for_review($post_id, $reason, $confidence = 0, $data = []) {
        $queue = get_option(self::QUEUE_OPTION, []);
        
        $queue[$post_id] = [
            'post_id' => $post_id,
            'title' => get_the_title($post_id),
            'post_type' => get_post_type($post_id),
            'reason' => $reason,
            'confidence' => $confidence,
            'data' => $data,
            'flagged_at' => current_time('mysql'),
            'status' => 'pending'
        ];
        
        update_option(self::QUEUE_OPTION, $queue);
        
        // Also store on post
        update_post_meta($post_id, '_chroma_needs_review', true);
        update_post_meta($post_id, '_chroma_review_reason', $reason);
    }
    
    /**
     * Approve and remove from queue
     */
    public static function approve($post_id) {
        $queue = get_option(self::QUEUE_OPTION, []);
        
        if (isset($queue[$post_id])) {
            unset($queue[$post_id]);
            update_option(self::QUEUE_OPTION, $queue);
        }
        
        delete_post_meta($post_id, '_chroma_needs_review');
        delete_post_meta($post_id, '_chroma_review_reason');
        
        return true;
    }
    
    /**
     * Get pending reviews
     */
    public static function get_pending() {
        $queue = get_option(self::QUEUE_OPTION, []);
        return array_filter($queue, fn($item) => $item['status'] === 'pending');
    }
    
    /**
     * Get queue count
     */
    public static function get_count() {
        return count(self::get_pending());
    }
    
    /**
     * AJAX: Review action
     */
    public function ajax_review() {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $action = sanitize_text_field($_POST['review_action'] ?? '');
        
        if (!$post_id) {
            wp_send_json_error(['message' => 'No post ID']);
        }
        
        if ($action === 'approve') {
            self::approve($post_id);
            wp_send_json_success(['message' => 'Approved']);
        }
        
        wp_send_json_error(['message' => 'Unknown action']);
    }
    
    /**
     * AJAX: Get queue
     */
    public function ajax_get_queue() {
        check_ajax_referer('chroma_seo_nonce', 'nonce');
        
        wp_send_json_success([
            'queue' => self::get_pending(),
            'count' => self::get_count()
        ]);
    }
}

/**
 * Schema Version History
 * Track changes to schema over time
 */
class Chroma_Schema_History
{
    /**
     * Save a new version
     */
    public static function save_version($post_id, $schema_data) {
        $history = get_post_meta($post_id, '_chroma_schema_history', true) ?: [];
        
        $history[] = [
            'data' => $schema_data,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'user_name' => wp_get_current_user()->display_name
        ];
        
        // Keep last 10 versions
        $history = array_slice($history, -10);
        
        update_post_meta($post_id, '_chroma_schema_history', $history);
    }
    
    /**
     * Get version history
     */
    public static function get_history($post_id) {
        return get_post_meta($post_id, '_chroma_schema_history', true) ?: [];
    }
    
    /**
     * Restore a version
     */
    public static function restore_version($post_id, $version_index) {
        $history = self::get_history($post_id);
        
        if (!isset($history[$version_index])) {
            return false;
        }
        
        $version = $history[$version_index];
        
        // Save current as new version before restoring
        $current = get_post_meta($post_id, '_chroma_schema_data', true);
        self::save_version($post_id, $current);
        
        // Restore
        update_post_meta($post_id, '_chroma_schema_data', $version['data']);
        
        return true;
    }
    
    /**
     * Compare two versions
     */
    public static function compare_versions($post_id, $version_a, $version_b) {
        $history = self::get_history($post_id);
        
        $a = $history[$version_a]['data'] ?? [];
        $b = $history[$version_b]['data'] ?? [];
        
        $added = [];
        $removed = [];
        $changed = [];
        
        // Find differences (simplified)
        foreach ($b as $key => $val) {
            if (!isset($a[$key])) {
                $added[$key] = $val;
            } elseif ($a[$key] !== $val) {
                $changed[$key] = ['old' => $a[$key], 'new' => $val];
            }
        }
        
        foreach ($a as $key => $val) {
            if (!isset($b[$key])) {
                $removed[$key] = $val;
            }
        }
        
        return [
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed
        ];
    }
}

/**
 * Field Confidence Scoring
 */
class Chroma_Field_Confidence
{
    /**
     * Calculate confidence for generated fields
     */
    public static function calculate($generated_data, $source_data) {
        $confidence = [];
        
        foreach ($generated_data as $key => $value) {
            if (empty($value)) {
                $confidence[$key] = 0;
                continue;
            }
            
            $score = 0.5; // Base score
            
            // Higher confidence if found in source data
            if (isset($source_data[$key]) && $source_data[$key] === $value) {
                $score = 1.0;
            }
            
            // Medium confidence if similar
            elseif (isset($source_data[$key]) && strpos($value, $source_data[$key]) !== false) {
                $score = 0.8;
            }
            
            // Check against common fields
            elseif (in_array($key, ['name', 'url', 'telephone', 'address'])) {
                // These are usually accurate from meta
                $score = 0.9;
            }
            
            // Lower confidence for complex fields
            elseif (in_array($key, ['description', 'aggregateRating', 'review'])) {
                $score = 0.6;
            }
            
            $confidence[$key] = round($score, 2);
        }
        
        return $confidence;
    }
    
    /**
     * Get overall confidence
     */
    public static function get_overall($confidence_scores) {
        if (empty($confidence_scores)) {
            return 0;
        }
        
        return round(array_sum($confidence_scores) / count($confidence_scores), 2);
    }
    
    /**
     * Check if review needed based on confidence
     */
    public static function needs_review($confidence_scores, $threshold = 0.7) {
        $overall = self::get_overall($confidence_scores);
        return $overall < $threshold;
    }
}

// Initialize
new Chroma_Schema_Review_Queue();



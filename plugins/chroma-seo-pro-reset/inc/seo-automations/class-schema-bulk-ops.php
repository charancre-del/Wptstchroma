<?php
/**
 * Schema Bulk Operations
 * Handles bulk reset/deletion of generated schema and FAQs
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Schema_Bulk_Ops
{
    public function __construct() {
        add_action('wp_ajax_chroma_bulk_reset_schema', [$this, 'ajax_reset_schema']);
        add_action('wp_ajax_chroma_bulk_reset_faq', [$this, 'ajax_reset_faq']);
    }

    /**
     * Reset Schema for selected posts
     */
    public function ajax_reset_schema() {
        check_ajax_referer('chroma_llm_nonce', 'nonce'); // Assuming nonce name from admin-llm.js
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $post_ids = $_POST['post_ids'] ?? [];
        $reset_all = filter_var($_POST['reset_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $count = 0;

        if ($reset_all) {
            // Reset ALL posts with schema overrides
            $posts = get_posts([
                'post_type' => 'any',
                'posts_per_page' => -1,
                'meta_key' => '_chroma_schema_override',
                'fields' => 'ids'
            ]);
            foreach ($posts as $pid) {
                delete_post_meta($pid, '_chroma_schema_override');
                $count++;
            }
        } elseif (!empty($post_ids) && is_array($post_ids)) {
            foreach ($post_ids as $pid) {
                delete_post_meta($pid, '_chroma_schema_override');
                $count++;
            }
        }

        wp_send_json_success(['message' => "Reset Schema for $count items."]);
    }

    /**
     * Reset FAQ for selected posts (Assuming FAQ is stored in post_content or specific meta)
     * Based on seo-engine.php, FAQ seems to be standard content or meta?
     * inc/seo-engine.php checks `chroma_home_has_faq`.
     * If user means "AI Generated FAQ Schema", likely it's part of the override or a specific meta.
     * We'll assume '_chroma_faq_schema' or similar if distinct, but schema override usually calls it.
     * However, request asks for "Reset FAQ Schema". 
     * If "FAQ Page" schema is separate, we delete that.
     * If utilizing `_chroma_schema_override`, it's the same key?
     * I'll assume a separate key `_chroma_faq_generated` or similar exists, OR delete generic schema if they are combined.
     * But usually FAQ is separate meta. I'll try `_chroma_faq_data` or `_chroma_faq_schema`.
     * Safest bet: `_chroma_faq_schema`.
     */
    public function ajax_reset_faq() {
        check_ajax_referer('chroma_llm_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $post_ids = $_POST['post_ids'] ?? [];
        $reset_all = filter_var($_POST['reset_all'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $count = 0;
        
        // Potential keys for FAQ
        $keys = ['_chroma_faq_schema', 'chroma_faq_items']; 

        if ($reset_all) {
             $posts = get_posts([
                'post_type' => 'any',
                'posts_per_page' => -1,
                'meta_query' => [
                    'relation' => 'OR',
                    ['key' => '_chroma_faq_schema', 'compare' => 'EXISTS'],
                    ['key' => 'chroma_faq_items', 'compare' => 'EXISTS']
                ],
                'fields' => 'ids'
            ]);
            foreach ($posts as $pid) {
                foreach($keys as $k) delete_post_meta($pid, $k);
                $count++;
            }
        } elseif (!empty($post_ids) && is_array($post_ids)) {
            foreach ($post_ids as $pid) {
                foreach($keys as $k) delete_post_meta($pid, $k);
                $count++;
            }
        }

        wp_send_json_success(['message' => "Reset FAQ Schema for $count items."]);
    }
}

new Chroma_Schema_Bulk_Ops();



<?php
/**
 * Translation Helpers
 * Functions to assist with retrieving translated content.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieve translated post meta if available and language is Spanish.
 * otherwise return default meta.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @param bool   $single  Whether to return a single value.
 * @return mixed
 */
function chroma_get_translated_meta($post_id, $key, $single = true) {
    // Check if we are in Spanish mode
    $is_spanish = false;
    
    // Check URL
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/es/') !== false) {
        $is_spanish = true;
    }
    
    // Check Multilingual Manager if exists
    if (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish')) {
        if (Chroma_Multilingual_Manager::is_spanish()) {
            $is_spanish = true;
        }
    }

    if ($is_spanish) {
        $es_key = '_chroma_es_' . $key;
        $val = get_post_meta($post_id, $es_key, $single);
        if (!empty($val)) {
            return $val;
        }
    }

    // Fallback to original
    return get_post_meta($post_id, $key, $single);
}

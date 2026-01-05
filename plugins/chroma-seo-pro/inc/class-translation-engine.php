<?php
/**
 * Translation Engine
 * Orchestrates batch translations and content localization.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Translation_Engine
{
    /**
     * Initialize Hooks
     */
    public static function init()
    {
        add_action('wp_ajax_chroma_auto_translate_post', [__CLASS__, 'ajax_auto_translate_post']);
    }

    /**
     * AJAX Handler: Auto Translate Post
     */
    public static function ajax_auto_translate_post()
    {
        check_ajax_referer('chroma_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error(['message' => 'Post not found']);
        }

        // Prepare fields to translate
        $fields = [
            '_chroma_es_title' => $post->post_title,
            '_chroma_es_content' => $post->post_content,
            '_chroma_es_excerpt' => $post->post_excerpt,
        ];

        // Add Post Type specific fields
        if ($post->post_type === 'location') {
            $fields['_chroma_es_location_city'] = get_post_meta($post_id, 'location_city', true);
            $fields['_chroma_es_location_address'] = get_post_meta($post_id, 'location_address', true);
            $fields['_chroma_es_location_hero_subtitle'] = get_post_meta($post_id, 'location_hero_subtitle', true);
            $fields['_chroma_es_location_ages_served'] = get_post_meta($post_id, 'location_ages_served', true);
            $fields['_chroma_es_location_open_text'] = 'Now Open'; // Default or fetch if exists
        } elseif ($post->post_type === 'program') {
            $fields['_chroma_es_program_age_range'] = get_post_meta($post_id, 'program_age_range', true);
            $fields['_chroma_es_program_cta_text'] = get_post_meta($post_id, 'program_cta_text', true);
            $fields['_chroma_es_program_features'] = get_post_meta($post_id, 'program_features', true);
        }

        // Translate
        $translated = self::translate_bulk($fields, 'es', 'Translate for a childcare website. Use Spanish (Latin American).');

        if (isset($translated['_error'])) {
            wp_send_json_error(['message' => $translated['_error']]);
        }

        // SAVE TO DATABASE
        foreach ($translated as $key => $value) {
            // Sanitize based on key type (content allows HTML, titles plain text)
            if (strpos($key, 'content') !== false) {
                update_post_meta($post_id, $key, wp_kses_post($value));
            } else {
                update_post_meta($post_id, $key, sanitize_text_field($value));
            }
        }

        wp_send_json_success($translated);
    }

    /**
     * Translate Fields in Bulk

     * 
     * @param array $fields Associative array of text to translate ['key1' => 'Text 1', 'key2' => 'Text 2']
     * @param string $target_lang Target language code
     * @param string $context Context description
     * @return array Translated fields ['key1' => 'Translated 1', ...]
     */
    public static function translate_bulk($fields, $target_lang = 'es', $context = '')
    {
        // Instantiate client directly
        $client = new Chroma_LLM_Client();

        // Filter out empty fields
        $fields_to_translate = array_filter($fields);
        if (empty($fields_to_translate)) {
            return $fields;
        }

        // Translation Memory: Check cache by content hash
        $content_hash = md5(json_encode($fields_to_translate) . $target_lang);
        $cache_key = 'chroma_trans_' . $content_hash;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            // Return cached translation
            return array_merge($fields, $cached);
        }

        // Construct a structured prompt for bulk translation
        $prompt = "You are a batch translation engine. Translate the following JSON object values to " . ($target_lang === 'es' ? 'Spanish (Latin American)' : $target_lang) . ".\n";
        $prompt .= "Maintain HTML tags if present. Do not translate keys.\n";
        $prompt .= "Return ONLY valid JSON.\n";
        
        if ($context) {
            $prompt .= "Context: " . $context . "\n";
        }
        
        $prompt .= "\nInput JSON:\n" . json_encode($fields_to_translate, JSON_UNESCAPED_UNICODE);

        $response = $client->make_request([
            'messages' => [
                ['role' => 'system', 'content' => 'You are a translation API. Output JSON only.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object']
        ]);

        if (is_wp_error($response)) {
            return ['_error' => $response->get_error_message()];
        }

        $content = $response['choices'][0]['message']['content'] ?? '{}';
        $translated = json_decode($content, true);

        if (!$translated) {
            return ['_error' => 'Failed to parse translation JSON'];
        }

        // Store in translation memory cache (30 days)
        set_transient($cache_key, $translated, 30 * DAY_IN_SECONDS);

        // Merge back into original
        return array_merge($fields, $translated);
    }
}

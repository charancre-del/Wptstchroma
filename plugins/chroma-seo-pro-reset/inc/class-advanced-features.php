<?php
/**
 * Advanced LLM Features
 * Competitor analysis, A/B testing, multi-language, image analysis
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Competitor Schema Analyzer
 */
class Chroma_Competitor_Analyzer
{
    /**
     * Fetch schema from competitor URL
     */
    public static function fetch_competitor_schema($url) {
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'user-agent' => 'Mozilla/5.0 (compatible; ChromaBot/1.0)'
        ]);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        $html = wp_remote_retrieve_body($response);
        $schemas = [];
        
        // Extract JSON-LD
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);
        
        foreach ($matches[1] ?? [] as $json) {
            $decoded = json_decode(trim($json), true);
            if ($decoded) {
                $schemas[] = $decoded;
            }
        }
        
        return $schemas;
    }
    
    /**
     * Compare our schema with competitor
     */
    public static function compare($our_schema, $competitor_url) {
        $competitor_schemas = self::fetch_competitor_schema($competitor_url);
        
        if (empty($competitor_schemas)) {
            return ['error' => 'No schema found on competitor site'];
        }
        
        $our_fields = self::extract_all_fields($our_schema);
        $competitor_fields = [];
        
        foreach ($competitor_schemas as $schema) {
            $competitor_fields = array_merge($competitor_fields, self::extract_all_fields($schema));
        }
        
        $competitor_fields = array_unique($competitor_fields);
        
        return [
            'our_fields' => $our_fields,
            'competitor_fields' => $competitor_fields,
            'missing' => array_diff($competitor_fields, $our_fields),
            'extra' => array_diff($our_fields, $competitor_fields)
        ];
    }
    
    /**
     * Extract all field names from schema recursively
     */
    private static function extract_all_fields($schema, $prefix = '') {
        $fields = [];
        
        if (!is_array($schema)) return $fields;
        
        foreach ($schema as $key => $value) {
            if (strpos($key, '@') === 0) continue;
            
            $full_key = $prefix ? "{$prefix}.{$key}" : $key;
            $fields[] = $full_key;
            
            if (is_array($value)) {
                $fields = array_merge($fields, self::extract_all_fields($value, $full_key));
            }
        }
        
        return $fields;
    }
    
    /**
     * Get recommendations based on comparison
     */
    public static function get_recommendations($comparison) {
        $recommendations = [];
        
        foreach ($comparison['missing'] ?? [] as $field) {
            $recommendations[] = [
                'type' => 'add',
                'field' => $field,
                'reason' => "Competitor has this field, you don't"
            ];
        }
        
        return $recommendations;
    }
}

/**
 * Schema A/B Testing
 */
class Chroma_Schema_AB_Test
{
    const OPTION_KEY = 'chroma_schema_ab_tests';
    
    /**
     * Create a new test
     */
    public static function create_test($post_id, $variant_a, $variant_b) {
        $tests = get_option(self::OPTION_KEY, []);
        
        $test_id = 'test_' . $post_id . '_' . time();
        
        $tests[$test_id] = [
            'post_id' => $post_id,
            'variant_a' => $variant_a,
            'variant_b' => $variant_b,
            'current' => 'a',
            'impressions_a' => 0,
            'impressions_b' => 0,
            'clicks_a' => 0,
            'clicks_b' => 0,
            'created_at' => current_time('mysql'),
            'status' => 'active'
        ];
        
        update_option(self::OPTION_KEY, $tests);
        
        return $test_id;
    }
    
    /**
     * Get active test for post
     */
    public static function get_active_test($post_id) {
        $tests = get_option(self::OPTION_KEY, []);
        
        foreach ($tests as $id => $test) {
            if ($test['post_id'] == $post_id && $test['status'] === 'active') {
                return array_merge(['id' => $id], $test);
            }
        }
        
        return null;
    }
    
    /**
     * Get current variant schema for output
     */
    public static function get_current_variant($post_id) {
        $test = self::get_active_test($post_id);
        
        if (!$test) return null;
        
        // Simple 50/50 split
        $variant = (rand(0, 1) === 0) ? 'a' : 'b';
        
        // Track impression
        $tests = get_option(self::OPTION_KEY, []);
        $tests[$test['id']]['impressions_' . $variant]++;
        update_option(self::OPTION_KEY, $tests);
        
        return $test['variant_' . $variant];
    }
    
    /**
     * End test and declare winner
     */
    public static function end_test($test_id) {
        $tests = get_option(self::OPTION_KEY, []);
        
        if (!isset($tests[$test_id])) return false;
        
        $test = $tests[$test_id];
        
        // Calculate CTR
        $ctr_a = $test['impressions_a'] > 0 ? $test['clicks_a'] / $test['impressions_a'] : 0;
        $ctr_b = $test['impressions_b'] > 0 ? $test['clicks_b'] / $test['impressions_b'] : 0;
        
        $tests[$test_id]['status'] = 'completed';
        $tests[$test_id]['winner'] = ($ctr_a >= $ctr_b) ? 'a' : 'b';
        $tests[$test_id]['completed_at'] = current_time('mysql');
        
        update_option(self::OPTION_KEY, $tests);
        
        return $tests[$test_id];
    }
}

/**
 * Multi-Language Schema Support
 */
class Chroma_Multilang_Schema
{
    /**
     * Generate schema in multiple languages
     */
    public static function generate_multilingual($post_id, $schema_type, $languages = ['en']) {
        global $chroma_llm_client;
        
        if (!$chroma_llm_client) return [];
        
        $schemas = [];
        
        foreach ($languages as $lang) {
            $context = [
                'language' => $lang,
                'instructions' => "Generate all text content in {$lang} language. Use locale-appropriate formatting."
            ];
            
            $result = $chroma_llm_client->generate_schema_data($post_id, $schema_type, $context);
            
            if (!is_wp_error($result)) {
                $result['inLanguage'] = $lang;
                $schemas[$lang] = $result;
            }
        }
        
        return $schemas;
    }
    
    /**
     * Get supported languages
     */
    public static function get_supported_languages() {
        return [
            'en' => 'English',
            'es' => 'Spanish',
            'zh' => 'Chinese',
            'ko' => 'Korean',
            'vi' => 'Vietnamese',
            'hi' => 'Hindi'
        ];
    }
}

/**
 * Image Analysis with Vision AI
 */
class Chroma_Image_Analyzer
{
    /**
     * Analyze image and generate alt text
     */
    public static function analyze_image($image_url) {
        $api_key = get_option('chroma_openai_api_key', '');
        
        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'No API key configured');
        }
        
        $prompt = "You are an SEO expert. Analyze this image of a childcare/preschool facility and generate:
1. An SEO-optimized alt text (max 125 chars)
2. A caption for the image
3. Keywords relevant to the image

Return JSON with keys: alt_text, caption, keywords";

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode([
                'model' => 'gpt-4o',
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $image_url]]
                    ]
                ]],
                'max_tokens' => 500
            ]),
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $content = $body['choices'][0]['message']['content'] ?? '';
        
        // Parse JSON from response
        preg_match('/\{.*\}/s', $content, $matches);
        if (!empty($matches[0])) {
            return json_decode($matches[0], true);
        }
        
        return ['alt_text' => $content];
    }
    
    /**
     * Bulk analyze images for a post
     */
    public static function analyze_post_images($post_id) {
        $results = [];
        
        // Featured image
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $url = wp_get_attachment_url($thumbnail_id);
            $results['featured'] = self::analyze_image($url);
        }
        
        // Gallery images (if ACF)
        if (function_exists('get_field')) {
            $gallery = get_field('location_gallery', $post_id);
            if ($gallery && is_array($gallery)) {
                foreach (array_slice($gallery, 0, 5) as $i => $img) {
                    $url = is_array($img) ? $img['url'] : wp_get_attachment_url($img);
                    $results['gallery_' . $i] = self::analyze_image($url);
                }
            }
        }
        
        return $results;
    }
}

// Register AJAX handlers
add_action('wp_ajax_chroma_analyze_image', function() {
    check_ajax_referer('chroma_seo_nonce', 'nonce');
    
    $image_url = esc_url_raw($_POST['image_url'] ?? '');
    if (!$image_url) {
        wp_send_json_error(['message' => 'No image URL']);
    }
    
    $result = Chroma_Image_Analyzer::analyze_image($image_url);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    
    wp_send_json_success($result);
});

add_action('wp_ajax_chroma_compare_competitor', function() {
    check_ajax_referer('chroma_seo_nonce', 'nonce');
    
    $post_id = intval($_POST['post_id'] ?? 0);
    $competitor_url = esc_url_raw($_POST['competitor_url'] ?? '');
    
    if (!$post_id || !$competitor_url) {
        wp_send_json_error(['message' => 'Missing parameters']);
    }
    
    $our_schema = get_post_meta($post_id, '_chroma_schema_data', true);
    $comparison = Chroma_Competitor_Analyzer::compare($our_schema, $competitor_url);
    
    wp_send_json_success($comparison);
});



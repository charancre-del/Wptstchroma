<?php
/**
 * LLM Client
 * Handles communication with OpenAI API
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_LLM_Client
{
    private $api_key;
    private $model;
    private $base_url;

    public function __construct()
    {
        $this->api_key = get_option('chroma_openai_api_key', '');
        $this->model = get_option('chroma_llm_model', 'gpt-4o-mini');
        $this->base_url = get_option('chroma_llm_base_url', 'https://api.openai.com/v1');

        // Register AJAX actions for saving key and testing connection
        add_action('wp_ajax_chroma_save_llm_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_chroma_test_llm_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_chroma_generate_schema', [$this, 'ajax_generate_schema']);
        add_action('wp_ajax_chroma_generate_llm_targeting', [$this, 'ajax_generate_llm_targeting']);
        add_action('wp_ajax_chroma_generate_general_seo_meta', [$this, 'ajax_generate_general_seo_meta']);
    }

    /**
     * Render Settings Section
     */
    public function render_settings()
    {
        $key = $this->api_key;
        $model = $this->model;
        $base_url = $this->base_url;
        ?>
        <div class="chroma-seo-card">
            <h2>🤖 AI Integration Settings</h2>
            <p>Configure your LLM provider (OpenAI, OpenRouter, etc.).</p>

            <table class="form-table">
                <tr>
                    <th scope="row">API Key</th>
                    <td>
                        <input type="password" id="chroma_openai_api_key" value="<?php echo esc_attr($key); ?>"
                            class="regular-text" placeholder="sk-..." autocomplete="off">
                        <p class="description">Your key is stored securely.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Model Name</th>
                    <td>
                        <input type="text" id="chroma_llm_model" value="<?php echo esc_attr($model); ?>" class="regular-text"
                            placeholder="gpt-4o-mini">
                        <p class="description">e.g., <code>gpt-4o</code>, <code>claude-3-sonnet</code> (via OpenRouter),
                            <code>llama-3</code>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Base URL</th>
                    <td>
                        <input type="text" id="chroma_llm_base_url" value="<?php echo esc_attr($base_url); ?>"
                            class="regular-text" placeholder="https://api.openai.com/v1">
                        <p class="description">Default: <code>https://api.openai.com/v1</code>. Change for OpenRouter/LocalAI.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Actions</th>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <button id="chroma-save-llm" class="button button-primary">Save Settings</button>
                            <button id="chroma-test-llm" class="button button-secondary">Test Connection</button>
                        </div>
                        <span id="chroma-llm-status" style="display:block; margin-top: 10px; font-weight: bold;"></span>
                    </td>
                </tr>
            </table>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                // Unbind previous events to prevent duplicates in case of AJAX reloads
                $(document).off('click', '#chroma-save-llm');
                $(document).off('click', '#chroma-test-llm');

                // Save Settings
                $(document).on('click', '#chroma-save-llm', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    btn.prop('disabled', true).text('Saving...');

                    $.post(ajaxurl, {
                        action: 'chroma_save_llm_settings',
                        api_key: $('#chroma_openai_api_key').val(),
                        model: $('#chroma_llm_model').val(),
                        base_url: $('#chroma_llm_base_url').val()
                    }, function (response) {
                        btn.prop('disabled', false).text('Save Settings');
                        if (response.success) {
                            alert('Settings saved! ' + (response.data.message || ''));
                        } else {
                            alert('Error saving settings: ' + (response.data.message || ''));
                        }
                    }).fail(function () {
                        btn.prop('disabled', false).text('Save Settings');
                        alert('Request failed. Please check your internet connection.');
                    });
                });

                // Test Connection
                $(document).on('click', '#chroma-test-llm', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    var status = $('#chroma-llm-status');

                    btn.prop('disabled', true).text('Testing...');
                    status.text('').css('color', 'inherit');

                    $.post(ajaxurl, {
                        action: 'chroma_test_llm_connection'
                    }, function (response) {
                        btn.prop('disabled', false).text('Test Connection');
                        if (response.success) {
                            status.text('✅ Connected! ' + (response.data.message || '')).css('color', 'green');
                        } else {
                            status.text('❌ Failed: ' + (response.data.message || 'Unknown error')).css('color', 'red');
                        }
                    }).fail(function () {
                        btn.prop('disabled', false).text('Test Connection');
                        status.text('❌ Request failed completely. Check network/console.').css('color', 'red');
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * AJAX: Save Settings
     */
    public function ajax_save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        if (isset($_POST['api_key'])) {
            $key = sanitize_text_field($_POST['api_key']);
            error_log('Chroma LLM: Saving API Key - Length: ' . strlen($key));
            if (empty($key)) {
                error_log('Chroma LLM: Warning - Saving EMPTY API Key');
            }
            $updated = update_option('chroma_openai_api_key', $key);
            error_log('Chroma LLM: update_option result: ' . ($updated ? 'true' : 'false'));
        }
        if (isset($_POST['model'])) {
            update_option('chroma_llm_model', sanitize_text_field($_POST['model']));
        }
        if (isset($_POST['base_url'])) {
            $url = esc_url_raw($_POST['base_url']);
            // Remove trailing slash for consistency
            $url = rtrim($url, '/');
            update_option('chroma_llm_base_url', $url);
        }

        $debug_msg = 'Key saved.';
        if (isset($key)) {
            $debug_msg .= ' Length: ' . strlen($key);
        }

        wp_send_json_success(['message' => $debug_msg]);
    }

    /**
     * AJAX: Test Connection
     */
    public function ajax_test_connection()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // Lazy-load API key fresh from database (in case user just saved it)
        $api_key = get_option('chroma_openai_api_key', '');
        error_log('Chroma LLM: Testing Connection. Loaded API Key Length: ' . strlen($api_key));
        if (!$api_key) {
            wp_send_json_error(['message' => 'No API Key found. (DB value empty)']);
        }

        $response = $this->make_request([
            'messages' => [
                ['role' => 'user', 'content' => 'Say "Hello" if you can hear me.']
            ]
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Connected!']);
    }

    /**
     * AJAX: Generate Schema from Content
     */
    public function ajax_generate_schema()
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $schema_type = sanitize_text_field($_POST['schema_type']);

        if (!$post_id || !$schema_type) {
            wp_send_json_error(['message' => 'Missing parameters']);
        }

        $result = $this->generate_schema_data($post_id, $schema_type);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        if (isset($_POST['auto_save']) && $_POST['auto_save'] === 'true') {
            $existing_schemas = get_post_meta($post_id, '_chroma_post_schemas', true);
            if (!is_array($existing_schemas)) {
                $existing_schemas = [];
            }
            // Append new schema
            $existing_schemas[] = [
                'type' => $schema_type,
                'data' => $result
            ];
            update_post_meta($post_id, '_chroma_post_schemas', $existing_schemas);
            $result['message'] = 'Schema generated and saved successfully.';
            $result['saved'] = true;
        }

        wp_send_json_success($result);
    }

    /**
     * Generate Schema Data (Public API)
     * 
     * @param int $post_id
     * @param string $schema_type
     * @param array $context Optional instructions or context
     * @return array|WP_Error
     */
    public function generate_schema_data($post_id, $schema_type, $context = [])
    {
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post not found');
        }

        // Get schema definition to guide the LLM
        $definitions = Chroma_Schema_Types::get_definitions();
        $expected_keys = [];
        if (isset($definitions[$schema_type]['fields'])) {
            foreach ($definitions[$schema_type]['fields'] as $key => $field) {
                if ($key !== 'custom_fields') { // Skip custom fields repeater
                    $expected_keys[] = $key . ' (' . $field['label'] . ')';
                }
            }
        }

        // Prepare prompt based on schema type
        $prompt = "Analyze the following content and extract data for a Schema.org '{$schema_type}' object.\n";
        $prompt .= "Return ONLY valid JSON. Do not include markdown formatting.\n";

        // Add custom instructions if provided
        if (!empty($context['instructions'])) {
            $prompt .= "IMPORTANT INSTRUCTIONS: " . $context['instructions'] . "\n";
        }

        if (!empty($expected_keys)) {
            $prompt .= "Map the extracted data to the following JSON keys ONLY:\n";
            $prompt .= "- " . implode("\n- ", $expected_keys) . "\n";
            $prompt .= "If a field cannot be found, leave it empty or omit it.\n";
        }


        // Fetch and format meta
        $meta = get_post_meta($post_id);
        $meta_context = "";
        if ($meta) {
            foreach ($meta as $key => $values) {
                if (strpos($key, '_') === 0)
                    continue; // Skip internal meta
                foreach ($values as $value) {
                    if (is_serialized($value)) {
                        $value = print_r(maybe_unserialize($value), true);
                    }
                    $meta_context .= "- {$key}: {$value}\n";
                }
            }
        }

        $prompt .= "\nContent Context:\n";
        $prompt .= "Title: " . $post->post_title . "\n";
        $prompt .= "Excerpt: " . $post->post_excerpt . "\n";
        $prompt .= "Meta Data:\n" . $meta_context . "\n";
        $prompt .= "Main Content:\n" . strip_tags($post->post_content);

        // Add Web Context (Live Page + Homepage)
        $web_context = $this->get_web_context($post_id);
        if ($web_context) {
            $prompt .= "\n\nWeb Context (Live Site Info):\n" . $web_context;
        }

        $response = $this->make_request([
            'messages' => [
                ['role' => 'system', 'content' => 'You are an SEO expert assistant. You extract structured data from text.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object']
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['choices'][0]['message']['content'])) {
            return new WP_Error('api_error', 'Invalid API response format');
        }

        $content = $response['choices'][0]['message']['content'];
        $json = json_decode($content, true);

        if (!$json) {
            return new WP_Error('json_error', 'Failed to parse AI response');
        }

        // Sanitize data against schema definition
        $json = $this->sanitize_schema_data($json, $schema_type);

        return $json;
    }

    /**
     * AJAX: Generate General SEO Meta (Desc/Keywords)
     */
    public function ajax_generate_general_seo_meta()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error(['message' => 'Post not found']);
        }

        $prompt = "Generate SEO metadata for the following content.\n";
        $prompt .= "Return ONLY valid JSON with two keys:\n";
        $prompt .= "- description: (string) A compelling meta description, max 160 chars.\n";
        $prompt .= "- keywords: (string) 5-8 comma-separated keywords.\n\n";

        $prompt .= "Title: " . $post->post_title . "\n";
        $prompt .= "Excerpt: " . $post->post_excerpt . "\n";
        $prompt .= "Content: " . wp_trim_words(strip_tags($post->post_content), 500) . "\n"; // Limit content for cost/speed

        // Add Web Context (Live Page + Homepage + GMB)
        $web_context = $this->get_web_context($post_id);
        if ($web_context) {
            $prompt .= "\n\nWeb Context (Live Site/GMB Info):\n" . $web_context;
        }

        $response = $this->make_request([
            'messages' => [
                ['role' => 'system', 'content' => 'You are an SEO expert.'],
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $content = $data['choices'][0]['message']['content'] ?? '';

        // Extract JSON
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                wp_send_json_success($json);
            }
        }

        // Fallback if JSON parsing fails
        wp_send_json_error(['message' => 'Failed to parse AI response. Raw: ' . substr($content, 0, 100)]);
    }

    /**
     * AJAX: Generate LLM Targeting Data
     */
    public function ajax_generate_llm_targeting()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        if (!$post_id) {
            wp_send_json_error(['message' => 'Missing Post ID']);
        }

        $result = $this->generate_llm_targeting_data($post_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        if (isset($_POST['auto_save']) && $_POST['auto_save'] === 'true') {
            if (!empty($result['primary_intent'])) {
                update_post_meta($post_id, 'seo_llm_primary_intent', sanitize_text_field($result['primary_intent']));
            }
            if (!empty($result['target_queries']) && is_array($result['target_queries'])) {
                $queries = array_map('sanitize_text_field', $result['target_queries']);
                update_post_meta($post_id, 'seo_llm_target_queries', $queries);
            }
            if (!empty($result['key_differentiators']) && is_array($result['key_differentiators'])) {
                $diffs = array_map('sanitize_text_field', $result['key_differentiators']);
                update_post_meta($post_id, 'seo_llm_key_differentiators', $diffs);
            }
            $result['message'] = 'LLM Targeting data generated and saved.';
            $result['saved'] = true;
        }

        wp_send_json_success($result);
    }

    /**
     * Generate LLM Targeting Data (Public API)
     * 
     * @param int $post_id
     * @return array|WP_Error
     */
    public function generate_llm_targeting_data($post_id)
    {
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post not found');
        }

        $prompt = "Analyze the following content and generate LLM optimization data.\n";
        $prompt .= "Return ONLY valid JSON with the following keys:\n";
        $prompt .= "- primary_intent: (string) The main user intent (e.g., 'informational', 'commercial', 'transactional').\n";
        $prompt .= "- target_queries: (array of strings) 3-5 natural language questions users might ask to find this.\n";
        $prompt .= "- key_differentiators: (array of strings) 3-5 unique selling points or key facts.\n";


        // Fetch and format meta
        $meta = get_post_meta($post_id);
        $meta_context = "";
        if ($meta) {
            foreach ($meta as $key => $values) {
                if (strpos($key, '_') === 0)
                    continue; // Skip internal meta
                foreach ($values as $value) {
                    if (is_serialized($value)) {
                        $value = print_r(maybe_unserialize($value), true);
                    }
                    $meta_context .= "- {$key}: {$value}\n";
                }
            }
        }

        $prompt .= "\nContent Context:\n";
        $prompt .= "Title: " . $post->post_title . "\n";
        $prompt .= "Excerpt: " . $post->post_excerpt . "\n";
        $prompt .= "Meta Data:\n" . $meta_context . "\n";
        $prompt .= "Main Content:\n" . strip_tags($post->post_content);

        // Add Web Context (Live Page + Homepage)
        $web_context = $this->get_web_context($post_id);
        if ($web_context) {
            $prompt .= "\n\nWeb Context (Live Site Info):\n" . $web_context;
        }

        $response = $this->make_request([
            'messages' => [
                ['role' => 'system', 'content' => 'You are an SEO expert specializing in LLM optimization (GEO).'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object']
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $content = $response['choices'][0]['message']['content'];
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Invalid JSON response from AI');
        }

        return $json;
    }

    /**
     * Make Request to OpenAI
     */
    private function make_request($data)
    {
        // Lazy-load settings fresh from database to ensure latest values are used
        $api_key = get_option('chroma_openai_api_key', '');
        $model = get_option('chroma_llm_model', 'gpt-4o-mini');
        $base_url = get_option('chroma_llm_base_url', 'https://api.openai.com/v1');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'No API Key configured. Please save your key first.');
        }

        // Use configured base URL or default
        $url = ($base_url ?: 'https://api.openai.com/v1') . '/chat/completions';

        $body = array_merge([
            'model' => $model ?: 'gpt-4o-mini',
            'temperature' => 0.7,
        ], $data);

        $args = [
            'body' => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'timeout' => 30
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200) {
            $msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API Error';
            return new WP_Error('api_error', $msg);
        }

        return $data;
    }

    /**
     * Helper: Fetch Web Context (Live Page + Homepage)
     */
    private function get_web_context($post_id)
    {
        $context = "";

        // 0. Check for GMB URL
        $gmb = get_post_meta($post_id, 'location_gmb_url', true);
        if ($gmb) {
            $context .= "Google My Business URL: " . $gmb . "\n";
            $context .= "Instruction: This is the official GMB listing. Use your internal knowledge of this business (reviews, location details) to enhance the content.\n\n";
        }

        // 1. Try to get the live page content (if published)
        $permalink = get_permalink($post_id);
        if ($permalink && get_post_status($post_id) === 'publish') {
            $response = wp_remote_get($permalink, ['timeout' => 5, 'sslverify' => false]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                // Extract main content or body text
                $text = strip_tags($body);
                // Limit length to avoid token limits (e.g., first 2000 chars)
                $context .= "Live Page Content:\n" . substr(preg_replace('/\s+/', ' ', $text), 0, 2000) . "\n\n";
            }
        }

        // 2. Always fetch Homepage for global info (Address, Phone, etc.)
        $home_url = home_url('/');
        // Avoid fetching homepage if we just fetched it as the permalink
        if ($permalink !== $home_url) {
            $response = wp_remote_get($home_url, ['timeout' => 5, 'sslverify' => false]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $text = strip_tags($body);
                $context .= "Homepage/Organization Info:\n" . substr(preg_replace('/\s+/', ' ', $text), 0, 1500) . "\n";
            }
        }

        return $context;
    }

    /**
     * Sanitize and Validate Schema Data against Definitions
     * Prevents type mismatches (e.g. Array for Textarea) which cause crashes.
     */
    private function sanitize_schema_data($data, $schema_type)
    {
        $definitions = Chroma_Schema_Types::get_definitions();
        if (!isset($definitions[$schema_type]['fields'])) {
            return $data; // No definition found, return as is
        }

        $fields = $definitions[$schema_type]['fields'];

        foreach ($data as $key => $value) {
            if (!isset($fields[$key])) {
                continue; // Unknown field, ignore or keep
            }

            $field_def = $fields[$key];
            $type = isset($field_def['type']) ? $field_def['type'] : 'text';

            // Critical Fix: mismatched Arrays for Text/Textarea fields
            if (($type === 'text' || $type === 'textarea' || $type === 'image') && is_array($value)) {
                // LLM returned a list (e.g. for sameAs), but schema expects a string
                $data[$key] = implode(', ', $value);
            }

            // Critical Fix: mismatched String for Array/Repeater fields
            // (Less common, but possible)
            if ($type === 'repeater' && !is_array($value) && !empty($value)) {
                // If it returns a single object instead of array of objects, wrap it?
                // Or if it returns a string, ignore it? 
                // For now, let's just make sure it's an array if not empty
                $data[$key] = [$value];
            }
        }

        return $data;
    }
}

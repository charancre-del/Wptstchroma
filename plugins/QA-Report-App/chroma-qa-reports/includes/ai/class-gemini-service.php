<?php
/**
 * Gemini API Client
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\AI;

/**
 * Client for Google Gemini API.
 */
class Gemini_Service
{

    /**
     * API Base URL.
     */
    const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

    /**
     * Get the API key.
     *
     * @return string
     */
    public static function get_api_key()
    {
        $key = \ChromaQA\Settings::get('gemini_api_key');

        if (defined('WP_DEBUG') && WP_DEBUG && empty($key)) {
            error_log('[CQA DEBUG] Gemini_Service::get_api_key - API key is empty.');
        }

        return $key;
    }

    /**
     * Get the configured model.
     *
     * @return string
     */
    public static function get_model()
    {
        return \ChromaQA\Settings::get('gemini_model', 'gemini-1.5-flash');
    }

    /**
     * Check if Gemini is configured.
     *
     * @return bool
     */
    public static function is_configured()
    {
        return !empty(self::get_api_key());
    }

    /**
     * Send a request to Gemini.
     *
     * @param string $prompt The prompt to send.
     * @param array  $options Additional options.
     * @return array|\WP_Error
     */
    public static function generate($prompt, $options = [])
    {
        $api_key = self::get_api_key();
        if (empty($api_key)) {
            return new \WP_Error('not_configured', __('Gemini API key is not configured.', 'chroma-qa-reports'), ['status' => 400]);
        }

        $model = self::get_model();
        if (defined('CQA_DEBUG') && CQA_DEBUG) {
            error_log('[CQA DEBUG] Gemini Generate: Using model: ' . $model);
        }

        $url = self::API_BASE_URL . $model . ':generateContent?key=' . self::get_api_key();

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'topK' => $options['topK'] ?? 40,
                'topP' => $options['topP'] ?? 0.95,
                'maxOutputTokens' => $options['maxTokens'] ?? 2048,
            ],
        ];

        $response = \wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => \wp_json_encode($body),
            'timeout' => 60,
        ]);

        if (\is_wp_error($response)) {
            \ChromaQA\Utils\Logger::error('Gemini', 'generate', ['prompt_length' => strlen($prompt)], $response->get_error_message());
            return $response;
        }

        $status_code = \wp_remote_retrieve_response_code($response);
        $body_res = \json_decode(\wp_remote_retrieve_body($response), true);

        if ($status_code !== 200) {
            $error_message = $body_res['error']['message'] ?? __('Unknown API error', 'chroma-qa-reports');
            \ChromaQA\Utils\Logger::error('Gemini', 'generate', ['prompt_length' => strlen($prompt), 'status' => $status_code], $body_res);
            return new \WP_Error('api_error', $error_message);
        }

        if (empty($body_res['candidates'][0]['content']['parts'][0]['text'])) {
            \ChromaQA\Utils\Logger::error('Gemini', 'generate', ['prompt_length' => strlen($prompt)], 'Empty response candidates');
            return new \WP_Error('no_response', __('No response from Gemini.', 'chroma-qa-reports'));
        }

        $result_text = $body_res['candidates'][0]['content']['parts'][0]['text'];
        \ChromaQA\Utils\Logger::info('Gemini', 'generate', ['prompt_length' => strlen($prompt), 'response_length' => strlen($result_text)], 'Success');

        // Return array format for consistency
        return [
            'text' => $result_text,
        ];
    }

    /**
     * Generate JSON response from Gemini.
     *
     * @param string $prompt The prompt.
     * @param array  $options Options.
     * @return array|WP_Error
     */
    public static function generate_json($prompt, $options = [])
    {
        // Add JSON instruction to prompt
        $json_prompt = $prompt . "\n\nRespond ONLY with valid JSON. Do not include any other text or markdown formatting. Ensure all strings are properly escaped.";

        $response = self::generate($json_prompt, $options);

        if (\is_wp_error($response)) {
            return $response;
        }

        // Extract text from array response
        $text = is_array($response) ? ($response['text'] ?? '') : $response;
        $text = trim($text);

        // 1. Priority: Extract from Markdown Code Block (anywhere in text)
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $text, $matches)) {
            $text = $matches[1];
        }

        // 2. Locate JSON object (find first { and last })
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start !== false && $end !== false && $end > $start) {
            $text = substr($text, $start, $end - $start + 1);
        }

        // 3. Attempt Decode
        $data = json_decode($text, true);

        // 4. Fallback: Try cleaning control characters if decode failed
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('CQA Gemini JSON Error: ' . json_last_error_msg());
            // Log a snippet for safety
            error_log('CQA Gemini Failed Text: ' . substr($text, 0, 500) . '...');
            return new \WP_Error('json_parse_error', '[DEBUG-NEW-SERVICE] JSON Error: ' . json_last_error_msg() . ' | Content: ' . substr($text, 0, 200));
        }

        return $data;
    }

    /**
     * Parse text from a document into structured report data.
     *
     * @param string $text The raw text from the document.
     * @return array|WP_Error Parsed data or error.
     */
    public static function parse_document($text)
    {
        // We need to map the text to our schema
        $prompt = "You are a QA Data Extraction AI.\n\n" .
            "I will provide text from a childcare QA inspection report. " .
            "Extract the ratings and notes for the following checklist items and return a JSON object.\n\n" .
            "STRUCTURE:\n" .
            "{\n" .
            "  \"responses\": {\n" .
            "     \"section_key\": {\n" .
            "        \"item_key\": { \"rating\": \"yes|no|sometimes|n/a\", \"notes\": \"...\" }\n" .
            "     }\n" .
            "  },\n" .
            "  \"report_type\": \"tier1\",\n" .
            "  \"overall_rating\": \"exceeds|meets|needs_improvement|pending\",\n" .
            "  \"closing_notes\": \"Summary text...\"\n" .
            "}\n\n" .
            "RULES:\n" .
            "1. Map loose terms: 'Consistently'->'yes', 'Needs Improvement'->'sometimes', 'Not Met'->'no'.\n" .
            "2. If an item is not mentioned, ignore it (do not include in JSON).\n" .
            "3. Extract section/item structure based on standard Chroma QA Tier 1 checklist.\n" .
            "4. Respond ONLY with valid JSON.\n\n" .
            "REPORT TEXT:\n" . $text;

        return self::generate_json($prompt, ['maxTokens' => 4000]);
    }

    /**
     * List available models from Gemini API.
     *
     * @return array|\WP_Error
     */
    /**
     * Get cached models without API call.
     *
     * @return array
     */
    public static function get_cached_models()
    {
        return get_transient('cqa_gemini_models_list') ?: [];
    }

    /**
     * List available models from Gemini API.
     *
     * @param string $api_key Optional API key.
     * @param bool   $force_refresh Whether to ignore cache.
     * @return array|\WP_Error
     */
    public static function list_models($api_key = null, $force_refresh = false)
    {
        // Return cached if available and not forcing refresh
        if (!$force_refresh) {
            $cached = get_transient('cqa_gemini_models_list');
            if ($cached !== false) {
                return $cached;
            }
        }

        if (empty($api_key)) {
            $api_key = self::get_api_key();
        }

        if (empty($api_key)) {
            return new \WP_Error('missing_api_key', __('Gemini API key is not configured.', 'chroma-qa-reports'), ['status' => 400]);
        }

        // Try v1beta first as it's the most common for Generative Language
        $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $api_key;

        $response = \wp_remote_get($url, [
            'timeout' => 20,
            'sslverify' => false, // Some servers have issues with certificate verification
        ]);

        if (\is_wp_error($response)) {
            return new \WP_Error('http_error', 'HTTP Request Failed: ' . $response->get_error_message());
        }

        $code = \wp_remote_retrieve_response_code($response);
        $body = \wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200) {
            $message = $data['error']['message'] ?? 'Unknown Error';
            return new \WP_Error('api_error', 'Gemini API Status ' . $code . ': ' . $message, ['status' => $code]);
        }

        if (empty($data['models'])) {
            return [];
        }

        // Filter for models that support generateContent
        $models = [];
        foreach ($data['models'] as $m) {
            $methods = $m['supportedGenerationMethods'] ?? [];
            if (in_array('generateContent', $methods)) {
                // Remove the "models/" prefix from the name for storage
                $name = str_replace('models/', '', $m['name']);

                // Prioritize newer models or specific ones if needed, 
                // but for now just return all that support text generation.
                $models[] = [
                    'name' => $name,
                    'displayName' => $m['displayName'] ?? $name,
                    'description' => $m['description'] ?? '',
                ];
            }
        }

        // Cache for 7 days
        set_transient('cqa_gemini_models_list', $models, 7 * DAY_IN_SECONDS);

        return $models;
    }
}

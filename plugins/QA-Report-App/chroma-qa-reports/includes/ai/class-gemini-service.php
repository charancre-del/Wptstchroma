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
     * Safe retry increment for JSON generation retries.
     */
    const JSON_RETRY_TOKEN_STEP = 1000;

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

        $requested_tokens = isset($options['maxTokens']) ? (int) $options['maxTokens'] : 2048;
        if ($requested_tokens < 1) {
            $requested_tokens = 2048;
        }
        $max_output_tokens = self::clamp_max_output_tokens($requested_tokens, $model);

        $url = self::API_BASE_URL . $model . ':generateContent?key=' . self::get_api_key();

        $generation_config = [
            'temperature' => $options['temperature'] ?? 0.7,
            'topK' => $options['topK'] ?? 40,
            'topP' => $options['topP'] ?? 0.95,
            'maxOutputTokens' => $max_output_tokens,
        ];

        if (!empty($options['responseMimeType']) && is_string($options['responseMimeType'])) {
            $generation_config['responseMimeType'] = $options['responseMimeType'];
        }
        if (!empty($options['responseSchema']) && is_array($options['responseSchema'])) {
            $generation_config['responseSchema'] = $options['responseSchema'];
        }

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => $generation_config,
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

        // Check if response was truncated
        $finish_reason = $body_res['candidates'][0]['finishReason'] ?? 'UNKNOWN';
        if ($finish_reason === 'MAX_TOKENS') {
            \ChromaQA\Utils\Logger::warn('Gemini', 'generate', [
                'prompt_length' => strlen($prompt),
                'response_length' => strlen($result_text),
                'finish_reason' => $finish_reason
            ], 'Response truncated due to MAX_TOKENS limit');
        }

        \ChromaQA\Utils\Logger::info('Gemini', 'generate', [
            'prompt_length' => strlen($prompt),
            'response_length' => strlen($result_text),
            'finish_reason' => $finish_reason,
            'requested_tokens' => $requested_tokens,
            'effective_tokens' => $max_output_tokens,
        ], 'Success');

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[CQA DEBUG] Gemini generate model=%s req_tokens=%d effective_tokens=%d finish_reason=%s',
                $model,
                $requested_tokens,
                $max_output_tokens,
                $finish_reason
            ));
        }

        // Return array format for consistency
        return [
            'text' => $result_text,
            'finish_reason' => $finish_reason,
            'usage' => $body_res['usageMetadata'] ?? [],
            'model' => $model,
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

        $schema = null;
        if (!empty($options['schema']) && is_array($options['schema'])) {
            $schema = $options['schema'];
        }
        unset($options['schema']);

        $use_structured_mode = !empty($schema);

        // Retry configuration
        $max_retries = 2;
        $base_tokens = $options['maxTokens'] ?? 4000;

        for ($attempt = 0; $attempt <= $max_retries; $attempt++) {
            // Increase tokens on retries
            $attempt_options = $options;
            $attempt_options['maxTokens'] = $base_tokens + ($attempt * self::JSON_RETRY_TOKEN_STEP);
            if ($use_structured_mode) {
                $attempt_options['responseMimeType'] = 'application/json';
                $attempt_options['responseSchema'] = $schema;
            }

            $response = self::generate($json_prompt, $attempt_options);

            if (\is_wp_error($response)) {
                if ($use_structured_mode && self::is_schema_unsupported_error($response)) {
                    $use_structured_mode = false;
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[CQA DEBUG] Gemini structured JSON not supported by model/endpoint; retrying without responseSchema.');
                    }
                    $attempt--;
                    continue;
                }
                // If it's not a token error, return immediately
                if ($attempt === $max_retries) {
                    return $response;
                }
                continue;
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
            } elseif ($start !== false) {
                // JSON was truncated - try to repair
                $text = substr($text, $start);
                $text = self::repair_truncated_json($text);
            }

            // 3. Attempt Decode
            $data = json_decode($text, true);

            // 4. If decode succeeded, return
            if (json_last_error() === JSON_ERROR_NONE) {
                if (!empty($schema)) {
                    $schema_error = '';
                    if (!self::validate_against_schema($data, $schema, $schema_error)) {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('[CQA DEBUG] Gemini JSON schema validation failed (attempt ' . ($attempt + 1) . '): ' . $schema_error);
                        }
                        if ($attempt < $max_retries) {
                            continue;
                        }
                        return new \WP_Error(
                            'json_schema_error',
                            __('AI response did not match the required format. Please try again.', 'chroma-qa-reports')
                        );
                    }
                }
                return $data;
            }

            // 5. On JSON error, log and retry if attempts remaining
            if ($attempt < $max_retries) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[CQA DEBUG] JSON decode failed (attempt " . ($attempt + 1) . "), retrying with more tokens: " . json_last_error_msg());
                }
                continue;
            }

            // Final attempt failed
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CQA Gemini JSON Error: ' . json_last_error_msg());
                error_log('CQA Gemini Failed Text (first 300 chars): ' . substr($text, 0, 300));
            }
            return new \WP_Error('json_parse_error', __('AI returned malformed JSON. Please try again.', 'chroma-qa-reports'));
        }

        return new \WP_Error('max_retries', __('Failed to get valid JSON after multiple attempts.', 'chroma-qa-reports'));
    }

    /**
     * Determine whether structured JSON schema options are unsupported.
     *
     * @param \WP_Error $error Error object from API call.
     * @return bool
     */
    private static function is_schema_unsupported_error($error)
    {
        if (!($error instanceof \WP_Error)) {
            return false;
        }

        $message = strtolower((string) $error->get_error_message());
        if ($message === '') {
            return false;
        }

        $indicators = [
            'responseschema',
            'response schema',
            'responsemimetype',
            'response mime',
            'invalid argument',
            'generationconfig',
            'unknown name',
        ];

        foreach ($indicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clamp requested max tokens against known model output limits.
     *
     * @param int    $requested_tokens Requested tokens.
     * @param string $model            Model name.
     * @return int
     */
    private static function clamp_max_output_tokens($requested_tokens, $model)
    {
        $requested = (int) $requested_tokens;
        if ($requested < 1) {
            $requested = 2048;
        }

        $limit = self::get_model_output_token_limit($model);
        if (empty($limit) || !is_numeric($limit)) {
            return $requested;
        }

        $limit = (int) $limit;
        if ($limit < 1) {
            return $requested;
        }

        if ($requested > $limit) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('[CQA DEBUG] Clamping maxOutputTokens for %s from %d to %d', $model, $requested, $limit));
            }
            return $limit;
        }

        return $requested;
    }

    /**
     * Get output token limit for configured model when available.
     *
     * @param string $model Model name.
     * @return int|null
     */
    private static function get_model_output_token_limit($model)
    {
        $models = self::get_cached_models();
        if (empty($models) || !is_array($models)) {
            $fetched = self::list_models(null, false);
            if (!\is_wp_error($fetched) && is_array($fetched)) {
                $models = $fetched;
            }
        }

        if (empty($models) || !is_array($models)) {
            return null;
        }

        $matched_without_limit = false;
        foreach ($models as $candidate) {
            if (!is_array($candidate) || empty($candidate['name'])) {
                continue;
            }
            if ((string) $candidate['name'] !== (string) $model) {
                continue;
            }
            if (!isset($candidate['outputTokenLimit']) || !is_numeric($candidate['outputTokenLimit'])) {
                $matched_without_limit = true;
                break;
            }
            return (int) $candidate['outputTokenLimit'];
        }

        if ($matched_without_limit) {
            $refreshed = self::list_models(null, true);
            if (!\is_wp_error($refreshed) && is_array($refreshed)) {
                foreach ($refreshed as $candidate) {
                    if (!is_array($candidate) || empty($candidate['name'])) {
                        continue;
                    }
                    if ((string) $candidate['name'] !== (string) $model) {
                        continue;
                    }
                    if (isset($candidate['outputTokenLimit']) && is_numeric($candidate['outputTokenLimit'])) {
                        return (int) $candidate['outputTokenLimit'];
                    }
                    break;
                }
            }
        }

        return null;
    }

    /**
     * Validate decoded data against a lightweight schema subset.
     *
     * @param mixed  $value  Decoded value.
     * @param array  $schema Schema array.
     * @param string $error  Output error message.
     * @param string $path   Dot path.
     * @return bool
     */
    private static function validate_against_schema($value, $schema, &$error = '', $path = 'root')
    {
        if (!is_array($schema)) {
            return true;
        }

        $type = isset($schema['type']) ? strtolower((string) $schema['type']) : '';
        if ($type === 'object') {
            if (!is_array($value) || array_values($value) === $value) {
                $error = $path . ' must be an object';
                return false;
            }

            $required = isset($schema['required']) && is_array($schema['required']) ? $schema['required'] : [];
            foreach ($required as $required_key) {
                if (!array_key_exists($required_key, $value)) {
                    $error = $path . '.' . $required_key . ' is required';
                    return false;
                }
            }

            if (!empty($schema['properties']) && is_array($schema['properties'])) {
                foreach ($schema['properties'] as $property => $property_schema) {
                    if (!array_key_exists($property, $value)) {
                        continue;
                    }
                    if (!self::validate_against_schema($value[$property], $property_schema, $error, $path . '.' . $property)) {
                        return false;
                    }
                }
            }

            return true;
        }

        if ($type === 'array') {
            if (!is_array($value)) {
                $error = $path . ' must be an array';
                return false;
            }
            if (isset($schema['minItems']) && is_numeric($schema['minItems']) && count($value) < (int) $schema['minItems']) {
                $error = $path . ' must have at least ' . (int) $schema['minItems'] . ' items';
                return false;
            }
            if (!empty($schema['items']) && is_array($schema['items'])) {
                foreach ($value as $index => $item) {
                    if (!self::validate_against_schema($item, $schema['items'], $error, $path . '[' . $index . ']')) {
                        return false;
                    }
                }
            }
            return true;
        }

        if ($type === 'string') {
            if (!is_string($value)) {
                $error = $path . ' must be a string';
                return false;
            }
            if (isset($schema['minLength']) && is_numeric($schema['minLength']) && strlen(trim($value)) < (int) $schema['minLength']) {
                $error = $path . ' is shorter than required';
                return false;
            }
            return true;
        }

        if ($type === 'number' || $type === 'integer') {
            if (!is_numeric($value)) {
                $error = $path . ' must be numeric';
                return false;
            }
            return true;
        }

        return true;
    }

    /**
     * Attempt to repair truncated JSON by closing brackets.
     *
     * @param string $json Truncated JSON string.
     * @return string Potentially repaired JSON.
     */
    private static function repair_truncated_json($json)
    {
        // Remove trailing incomplete string (common truncation point)
        $json = preg_replace('/"[^"]*$/', '""', $json);

        // Count opening and closing brackets
        $open_braces = substr_count($json, '{');
        $close_braces = substr_count($json, '}');
        $open_brackets = substr_count($json, '[');
        $close_brackets = substr_count($json, ']');

        // Close any unclosed arrays first (they're usually nested)
        while ($close_brackets < $open_brackets) {
            $json .= ']';
            $close_brackets++;
        }

        // Close any unclosed objects
        while ($close_braces < $open_braces) {
            $json .= '}';
            $close_braces++;
        }

        // Clean up common trailing issues
        $json = preg_replace('/,\s*([}\]])/', '$1', $json); // Remove trailing commas

        error_log('[CQA] Attempted JSON repair: added ' . ($close_braces - substr_count($json, '}') + ($open_braces - $close_braces)) . ' closing braces');

        return $json;
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
                    'outputTokenLimit' => isset($m['outputTokenLimit']) ? (int) $m['outputTokenLimit'] : null,
                    'inputTokenLimit' => isset($m['inputTokenLimit']) ? (int) $m['inputTokenLimit'] : null,
                ];
            }
        }

        // Cache for 7 days
        set_transient('cqa_gemini_models_list', $models, 7 * DAY_IN_SECONDS);

        return $models;
    }
}

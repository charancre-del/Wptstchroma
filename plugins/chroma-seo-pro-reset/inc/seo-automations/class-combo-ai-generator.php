<?php
/**
 * Combo Page AI Generator
 * Uses LLM to generate content for combo pages
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Combo_AI_Generator
{
    public function __construct() {
        add_action('wp_ajax_chroma_combo_ai_generate', [$this, 'ajax_generate_single']);
        add_action('wp_ajax_chroma_combo_ai_bulk_generate', [$this, 'ajax_bulk_generate']);
        add_action('wp_ajax_chroma_combo_bulk_status', [$this, 'ajax_bulk_status_update']);
        add_action('wp_ajax_chroma_combo_save_data', [$this, 'ajax_save_data']);
        add_action('wp_ajax_chroma_combo_get_data', [$this, 'ajax_get_data']);
        add_action('wp_ajax_chroma_combo_ai_translate', [$this, 'ajax_translate_single']);
        add_action('wp_ajax_chroma_combo_ai_bulk_translate', [$this, 'ajax_bulk_translate']);
    }
    
    /**
     * Generate AI content for a single combo page
     */
    public function ajax_generate_single() {
        check_ajax_referer('chroma_combo_ai', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $program_slug = sanitize_title($_POST['program_slug'] ?? '');
        $city_slug = sanitize_title($_POST['city_slug'] ?? '');
        $state = strtoupper(sanitize_text_field($_POST['state'] ?? 'GA'));
        
        if (!$program_slug || !$city_slug) {
            wp_send_json_error('Missing parameters');
        }
        
        $city_name = ucwords(str_replace('-', ' ', $city_slug));
        $program = get_page_by_path($program_slug, OBJECT, 'program');
        $program_name = $program ? $program->post_title : ucwords(str_replace('-', ' ', $program_slug));
        
        // Generate content using LLM
        $result = $this->generate_combo_content($program_name, $city_name, $state);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Get auto-publish setting
        $auto_publish = get_option('chroma_combo_auto_publish', false);
        $result['status'] = $auto_publish ? 'published' : 'draft';
        $result['ai_generated'] = true;
        $result['last_ai_update'] = current_time('timestamp');
        
        // Save data
        Chroma_Combo_Page_Data::save($program_slug, $city_slug, $state, $result);
        
        wp_send_json_success([
            'data' => $result,
            'message' => 'AI content generated successfully'
        ]);
    }
    
    /**
     * Bulk generate AI content
     */
    public function ajax_bulk_generate() {
        check_ajax_referer('chroma_combo_ai', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $combos = $_POST['combos'] ?? [];
        if (empty($combos)) {
            wp_send_json_error('No combos selected');
        }
        
        $results = [];
        $success_count = 0;
        $error_count = 0;
        
        foreach ($combos as $combo) {
            $program_slug = sanitize_title($combo['program_slug'] ?? '');
            $city_slug = sanitize_title($combo['city_slug'] ?? '');
            $state = strtoupper(sanitize_text_field($combo['state'] ?? 'GA'));
            
            if (!$program_slug || !$city_slug) {
                $error_count++;
                continue;
            }
            
            $city_name = ucwords(str_replace('-', ' ', $city_slug));
            $program = get_page_by_path($program_slug, OBJECT, 'program');
            $program_name = $program ? $program->post_title : ucwords(str_replace('-', ' ', $program_slug));
            
            $result = $this->generate_combo_content($program_name, $city_name, $state);
            
            if (is_wp_error($result)) {
                $results[] = [
                    'combo' => "$program_name in $city_name",
                    'status' => 'error',
                    'message' => $result->get_error_message()
                ];
                $error_count++;
            } else {
                $auto_publish = get_option('chroma_combo_auto_publish', false);
                $result['status'] = $auto_publish ? 'published' : 'draft';
                $result['ai_generated'] = true;
                $result['last_ai_update'] = current_time('timestamp');
                
                Chroma_Combo_Page_Data::save($program_slug, $city_slug, $state, $result);
                
                $results[] = [
                    'combo' => "$program_name in $city_name",
                    'status' => 'success'
                ];
                $success_count++;
            }
            
            // Add a small delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }
        
        wp_send_json_success([
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count
        ]);
    }

    /**
     * Bulk status update (Draft/Publish/Auto)
     */
    public function ajax_bulk_status_update() {
        check_ajax_referer('chroma_combo_ai', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $combos = $_POST['combos'] ?? [];
        $status = sanitize_text_field($_POST['status'] ?? 'draft');
        
        if (empty($combos)) {
            wp_send_json_error('No combos selected');
        }
        
        $success_count = 0;
        
        foreach ($combos as $combo) {
            $program_slug = sanitize_title($combo['program_slug'] ?? '');
            $city_slug = sanitize_title($combo['city_slug'] ?? '');
            $state = strtoupper(sanitize_text_field($combo['state'] ?? 'GA'));
            
            if ($program_slug && $city_slug) {
                if (Chroma_Combo_Page_Data::save($program_slug, $city_slug, $state, ['status' => $status])) {
                    $success_count++;
                }
            }
        }
        
        wp_send_json_success([
            'success_count' => $success_count,
            'message' => "Successfully updated $success_count items to $status"
        ]);
    }
    
    /**
     * Save combo page data manually
     */
    public function ajax_save_data() {
        check_ajax_referer('chroma_combo_ai', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $program_slug = sanitize_title($_POST['program_slug'] ?? '');
        $city_slug = sanitize_title($_POST['city_slug'] ?? '');
        $state = strtoupper(sanitize_text_field($_POST['state'] ?? 'GA'));
        
        $data = [
            'neighborhoods' => array_map('sanitize_text_field', $_POST['neighborhoods'] ?? []),
            'major_road' => sanitize_text_field($_POST['major_road'] ?? ''),
            'local_employers' => sanitize_text_field($_POST['local_employers'] ?? ''),
            'county' => sanitize_text_field($_POST['county'] ?? ''),
            'custom_intro' => wp_kses_post($_POST['custom_intro'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'draft')
        ];
        
        Chroma_Combo_Page_Data::save($program_slug, $city_slug, $state, $data);
        
        wp_send_json_success('Saved successfully');
    }

    /**
     * Get combo page data
     */
    public function ajax_get_data() {
        check_ajax_referer('chroma_combo_ai', 'nonce');
        
        $program_slug = sanitize_title($_POST['program_slug'] ?? '');
        $city_slug = sanitize_title($_POST['city_slug'] ?? '');
        $state = strtoupper(sanitize_text_field($_POST['state'] ?? 'GA'));
        
        $data = Chroma_Combo_Page_Data::get($program_slug, $city_slug, $state);
        
        wp_send_json_success($data);
    }
    
    /**
     * Generate combo page content using LLM
     */
    private function generate_combo_content($program_name, $city_name, $state) {
        /** @var Chroma_LLM_Client $chroma_llm_client */
        global $chroma_llm_client;
        
        if (!$chroma_llm_client) {
            return new WP_Error('no_llm', 'LLM client not available');
        }
        
        $prompt = $this->build_prompt($program_name, $city_name, $state);
        
        $response = $chroma_llm_client->make_request([
            'model' => get_option('chroma_llm_model', 'gpt-4o-mini'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a local SEO expert helping generate location-specific content for a childcare/preschool website. Return valid JSON only.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.7
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $content = $response['choices'][0]['message']['content'] ?? '';
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Failed to parse LLM response');
        }
        
        return [
            'neighborhoods' => $data['neighborhoods'] ?? [],
            'major_road' => $data['major_road'] ?? '',
            'local_employers' => $data['local_employers'] ?? '',
            'county' => $data['county'] ?? '',
            'custom_intro' => $data['custom_intro'] ?? ''
        ];
    }
    
    /**
     * Translate content to Spanish using LLM
     */
    public function ajax_translate_single() {
        check_ajax_referer('chroma_combo_ai', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $program_slug = sanitize_title($_POST['program_slug'] ?? '');
        $city_slug = sanitize_title($_POST['city_slug'] ?? '');
        $state = strtoupper(sanitize_text_field($_POST['state'] ?? 'GA'));
        
        // Get existing data
        $data = Chroma_Combo_Page_Data::get($program_slug, $city_slug, $state);
        
        // If data is empty or defaults, nothing to translate
        if (!Chroma_Combo_Page_Data::has_data($program_slug, $city_slug, $state)) {
             wp_send_json_error('No content to translate. Please generate English content first.');
        }
        
        $city_name = ucwords(str_replace('-', ' ', $city_slug));
        $program = get_page_by_path($program_slug, OBJECT, 'program');
        $program_name = $program ? $program->post_title : ucwords(str_replace('-', ' ', $program_slug));
        
        // Perform Translation
        $translation = $this->translate_combo_content($data, $program_name, $city_name);
        
        if (is_wp_error($translation)) {
            wp_send_json_error($translation->get_error_message());
        }
        
        // Allow partial updates - only update Spanish fields
        $data['neighborhoods_es'] = $translation['neighborhoods_es'];
        $data['major_road_es'] = $translation['major_road_es'];
        $data['local_employers_es'] = $translation['local_employers_es'];
        $data['custom_intro_es'] = $translation['custom_intro_es'];
        
        Chroma_Combo_Page_Data::save($program_slug, $city_slug, $state, $data);
        
        wp_send_json_success([
            'message' => 'Spanish translation generated successfully'
        ]);
    }

    /**
     * Generate translation using LLM
     */
    private function translate_combo_content($data, $program_name, $city_name) {
        /** @var Chroma_LLM_Client $chroma_llm_client */
        global $chroma_llm_client;
        
        if (!$chroma_llm_client) {
            return new WP_Error('no_llm', 'LLM client not available');
        }
        
        $source_json = json_encode([
            'neighborhoods' => $data['neighborhoods'],
            'major_road' => $data['major_road'],
            'local_employers' => $data['local_employers'],
            'custom_intro' => $data['custom_intro']
        ]);
        
        $prompt = <<<PROMPT
Translate the following local SEO data from English to Spanish for a childcare page ($program_name in $city_name).
Keep proper names (cities, roads, companies) in English if that is standard usage, but translate descriptions.
Return JSON only:

Source:
$source_json

Output Format:
{
    "neighborhoods_es": ["Barrio 1", ...],
    "major_road_es": "...",
    "local_employers_es": "...",
    "custom_intro_es": "..."
}
PROMPT;

        $response = $chroma_llm_client->make_request([
            'model' => get_option('chroma_llm_model', 'gpt-4o-mini'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional English-Spanish translator for a US-based audience. Return valid JSON only.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.5
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $content = $response['choices'][0]['message']['content'] ?? '';
        $result = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Failed to parse translation response');
        }
        
        return $result;
    }

    /**
     * Bulk translate content
     */
    public function ajax_bulk_translate() {
        check_ajax_referer('chroma_combo_ai', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $combos = $_POST['combos'] ?? [];
        if (empty($combos)) {
            wp_send_json_error('No combos selected');
        }
        
        $results = [];
        $success_count = 0;
        $error_count = 0;
        
        foreach ($combos as $combo) {
            $program_slug = sanitize_title($combo['program_slug'] ?? '');
            $city_slug = sanitize_title($combo['city_slug'] ?? '');
            $state = strtoupper(sanitize_text_field($combo['state'] ?? 'GA'));
            
            // Get existing data
            $data = Chroma_Combo_Page_Data::get($program_slug, $city_slug, $state);
            
            if (!Chroma_Combo_Page_Data::has_data($program_slug, $city_slug, $state)) {
                $error_count++;
                $results[] = [
                    'combo' => "$program_slug in $city_slug",
                    'status' => 'error',
                    'message' => 'No English content found'
                ];
                continue;
            }
            
            $city_name = ucwords(str_replace('-', ' ', $city_slug));
            $program = get_page_by_path($program_slug, OBJECT, 'program');
            $program_name = $program ? $program->post_title : ucwords(str_replace('-', ' ', $program_slug));
            
            $translation = $this->translate_combo_content($data, $program_name, $city_name);
            
            if (is_wp_error($translation)) {
                $results[] = [
                    'combo' => "$program_name in $city_name",
                    'status' => 'error',
                    'message' => $translation->get_error_message()
                ];
                $error_count++;
            } else {
                $data['neighborhoods_es'] = $translation['neighborhoods_es'];
                $data['major_road_es'] = $translation['major_road_es'];
                $data['local_employers_es'] = $translation['local_employers_es'];
                $data['custom_intro_es'] = $translation['custom_intro_es'];
                
                Chroma_Combo_Page_Data::save($program_slug, $city_slug, $state, $data);
                
                $results[] = [
                    'combo' => "$program_name in $city_name",
                    'status' => 'success'
                ];
                $success_count++;
            }
            
            // Add a small delay
            usleep(500000); // 0.5 seconds
        }
        
        wp_send_json_success([
            'results' => $results,
            'success_count' => $success_count,
            'error_count' => $error_count
        ]);
    }
    
    /**
     * Build the prompt for LLM
     */
    private function build_prompt($program_name, $city_name, $state) {
        return <<<PROMPT
Generate local SEO content for a childcare landing page: "$program_name in $city_name, $state"

Research and provide:
1. neighborhoods: Array of 3-5 real neighborhood names in or near $city_name, $state. IMPORTANT: If you do not know real specific neighborhood names with high certainty, use generic but accurate descriptors (e.g., "Downtown District", "Historic Center", "Residential Areas") to avoid hallucinating fake names.
2. major_road: The main highway or road near $city_name (e.g., "GA-400", "I-85")
3. local_employers: 2-3 major employers or hospitals in $city_name, comma-separated
4. county: The county that $city_name is in
5. custom_intro: A compelling 2-sentence intro for parents looking for $program_name in $city_name. Mention a specific local landmark or feature.

Return as JSON:
{
    "neighborhoods": ["Downtown $city_name", "North $city_name", ...],
    "major_road": "GA-400",
    "local_employers": "Northside Hospital, Delta Air Lines",
    "county": "Forsyth",
    "custom_intro": "Looking for quality $program_name near..."
}
PROMPT;
    }
}

new Chroma_Combo_AI_Generator();



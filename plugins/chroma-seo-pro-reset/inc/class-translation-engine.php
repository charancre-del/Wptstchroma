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
        chroma_debug_log(' Translate: Ajax Request Received for post ' . ($_POST['post_id'] ?? 'unknown'));
        // Verify nonce manually to debug
        if (!check_ajax_referer('chroma_seo_nonce', 'nonce', false)) {
            chroma_debug_log(' Translate: Invalid Nonce');
            wp_send_json_error(['message' => 'Invalid Nonce (Session Expired?)']);
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error(['message' => 'Post not found']);
        }

        // Register shutdown function to catch fatal errors
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
                // Clean any previous output
                if (ob_get_length()) ob_clean();
                wp_send_json_error(['message' => 'Fatal Error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']]);
            }
        });

        try {
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
                $fields['_chroma_es_location_tagline'] = get_post_meta($post_id, 'location_tagline', true);
                $fields['_chroma_es_location_description'] = get_post_meta($post_id, 'location_description', true);
                $fields['_chroma_es_location_ages_served'] = get_post_meta($post_id, 'location_ages_served', true);
                $fields['_chroma_es_location_open_text'] = 'Now Open'; 
                $fields['_chroma_es_location_hours'] = get_post_meta($post_id, 'location_hours', true);
                $fields['_chroma_es_location_director_name'] = get_post_meta($post_id, 'location_director_name', true);
                $fields['_chroma_es_location_director_bio'] = get_post_meta($post_id, 'location_director_bio', true);
                $fields['_chroma_es_location_hero_review_text'] = get_post_meta($post_id, 'location_hero_review_text', true);
                $fields['_chroma_es_location_hero_review_author'] = get_post_meta($post_id, 'location_hero_review_author', true);
                $fields['_chroma_es_location_seo_content_title'] = get_post_meta($post_id, 'location_seo_content_title', true);
                $fields['_chroma_es_location_seo_content_text'] = get_post_meta($post_id, 'location_seo_content_text', true);
                $fields['_chroma_es_location_school_pickups'] = get_post_meta($post_id, 'location_school_pickups', true);
                $fields['_chroma_es_location_special_programs'] = get_post_meta($post_id, 'location_special_programs', true);
                $fields['_chroma_es_location_tour_booking_link'] = get_post_meta($post_id, 'location_tour_booking_link', true);
            } elseif ($post->post_type === 'program') {
                $fields['_chroma_es_program_age_range'] = get_post_meta($post_id, 'program_age_range', true);
                $fields['_chroma_es_program_cta_text'] = get_post_meta($post_id, 'program_cta_text', true);
                $fields['_chroma_es_program_features'] = get_post_meta($post_id, 'program_features', true);
                $fields['_chroma_es_program_hero_title'] = get_post_meta($post_id, 'program_hero_title', true);
                $fields['_chroma_es_program_hero_description'] = get_post_meta($post_id, 'program_hero_description', true);
                $fields['_chroma_es_program_prism_title'] = get_post_meta($post_id, 'program_prism_title', true);
                $fields['_chroma_es_program_prism_description'] = get_post_meta($post_id, 'program_prism_description', true);
                $fields['_chroma_es_program_prism_focus_items'] = get_post_meta($post_id, 'program_prism_focus_items', true);
                $fields['_chroma_es_program_schedule_title'] = get_post_meta($post_id, 'program_schedule_title', true);
                $fields['_chroma_es_program_schedule_items'] = get_post_meta($post_id, 'program_schedule_items', true);
            } elseif ($post->post_type === 'city') {
                $fields['_chroma_es_city_intro_text'] = get_post_meta($post_id, 'city_intro_text', true);
                $fields['_chroma_es_city_state'] = get_post_meta($post_id, 'city_state', true);
                $fields['_chroma_es_city_county'] = get_post_meta($post_id, 'city_county', true);
                $fields['_chroma_es_city_neighborhoods'] = get_post_meta($post_id, 'city_neighborhoods', true);
            } elseif ($post->post_type === 'team_member') {
                $fields['_chroma_es_team_member_title'] = get_post_meta($post_id, 'team_member_title', true);
            }
            
            // Template specific
            $template = get_page_template_slug($post_id);
            if (empty($template) && (int)$post_id === (int)get_option('page_on_front')) {
                $template = 'front-page.php';
            }
            $template_keys = self::get_keys_for_template($template);
            
            foreach ($template_keys as $tkey) {
                $fields['_chroma_es_' . $tkey] = get_post_meta($post_id, $tkey, true);
            }

            // Special handling for FAQs (Repeater Field)
            $faqs = get_post_meta($post_id, 'chroma_faq_items', true);
            $faq_flat_map = []; // Map flat keys to array index
            if (is_array($faqs) && !empty($faqs)) {
                foreach ($faqs as $i => $faq) {
                    $q_key = "_chroma_es_faq_{$i}_question";
                    $a_key = "_chroma_es_faq_{$i}_answer";
                    
                    // Add to translation payload
                    $fields[$q_key] = $faq['question'];
                    $fields[$a_key] = $faq['answer'];
                    
                    $faq_flat_map[$i] = ['q' => $q_key, 'a' => $a_key];
                }
            }
            
            // Translate
            $force = isset($_POST['force']) && $_POST['force'] === 'true';
            $translated = self::translate_bulk($fields, 'es', 'Translate for a childcare website. Use Spanish (Latin American).', $force);


            if (isset($translated['_error'])) {
                $err_msg = !empty($translated['_error']) ? $translated['_error'] : 'LLM Client returned an empty error.';
                wp_send_json_error(['message' => $err_msg]);
            }

            // SAVE TO DATABASE
            $es_faqs = [];

            foreach ($translated as $key => $value) {
                // Reconstruct FAQs
                if (strpos($key, '_chroma_es_faq_') === 0 && preg_match('/_chroma_es_faq_(\d+)_(question|answer)/', $key, $matches)) {
                    $index = $matches[1];
                    $type = $matches[2];
                    $es_faqs[$index][$type] = sanitize_textarea_field($value);
                    continue; 
                }

                // Sanitize based on key type (content allows HTML, titles plain text)
                if (strpos($key, 'content') !== false) {
                    update_post_meta($post_id, $key, wp_kses_post($value));
                } else {
                    update_post_meta($post_id, $key, sanitize_text_field($value));
                }
            }

            // Save reconstructed FAQ array if exists
            if (!empty($es_faqs)) {
                // Ensure array keys are sequential and complete
                $clean_faqs = [];
                foreach ($es_faqs as $item) {
                     if (isset($item['question']) && isset($item['answer'])) {
                         $clean_faqs[] = $item;
                     }
                }
                if (!empty($clean_faqs)) {
                    update_post_meta($post_id, '_chroma_es_chroma_faq_items', $clean_faqs);
                }
            }

            wp_send_json_success($translated);

        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Exception: ' . $e->getMessage()]);
        } catch (Error $e) {
            wp_send_json_error(['message' => 'Fatal Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Helper: Get meta keys based on template
     */
    private static function get_keys_for_template($template)
    {
        $keys = [];
        switch ($template) {
            case 'page-about.php':
                return [
                    'about_hero_badge_text', 'about_hero_title', 'about_hero_description', 'about_mission_quote',
                    'about_story_title', 'about_story_paragraph1', 'about_story_paragraph2', 'about_story_image',
                    'about_stat1_label', 'about_stat2_label', 'about_stat3_label', 'about_stat4_label',
                    'about_educators_title', 'about_educators_description', 'about_educator1_title', 'about_educator1_desc',
                    'about_educator2_title', 'about_educator2_desc', 'about_educator3_title', 'about_educator3_desc',
                    'about_values_title', 'about_values_description', 'about_value1_title', 'about_value1_desc',
                    'about_value2_title', 'about_value2_desc', 'about_value3_title', 'about_value3_desc',
                    'about_value4_title', 'about_value4_desc', 'about_leadership_title', 'about_nutrition_title',
                    'about_nutrition_description', 'about_nutrition_bullet1_text', 'about_nutrition_bullet2_text',
                    'about_nutrition_bullet3_text', 'about_philanthropy_title', 'about_philanthropy_subtitle',
                    'about_philanthropy_description', 'about_philanthropy_bullet1_text', 'about_philanthropy_bullet2_text',
                    'about_philanthropy_bullet3_text', 'about_cta_title', 'about_cta_description'
                ];
            case 'page-careers.php':
                return [
                    'careers_hero_badge', 'careers_hero_title', 'careers_hero_description',
                    'careers_hero_button_text', 'careers_culture_title', 'careers_culture_description',
                    'careers_benefit1_title', 'careers_benefit1_desc',
                    'careers_benefit2_title', 'careers_benefit2_desc',
                    'careers_benefit3_title', 'careers_benefit3_desc',
                    'careers_cta_title', 'careers_cta_description',
                ];
            case 'page-contact.php':
                return [
                    'contact_hero_badge', 'contact_hero_title', 'contact_hero_description',
                    'contact_locations_title', 'contact_locations_description',
                    'contact_form_title', 'contact_form_description', 'contact_form_submit_text',
                    'contact_info_title', 'contact_corporate_address',
                    'contact_cta_title', 'contact_cta_description', 'contact_cta_button_text'
                ];
            case 'page-curriculum.php':
                return [
                    'curriculum_hero_badge', 'curriculum_hero_title', 'curriculum_hero_description',
                    'curriculum_framework_title', 'curriculum_framework_description',
                    'curriculum_pillar_physical_title', 'curriculum_pillar_physical_desc',
                    'curriculum_pillar_emotional_title', 'curriculum_pillar_emotional_desc',
                    'curriculum_pillar_social_title', 'curriculum_pillar_social_desc',
                    'curriculum_pillar_academic_title', 'curriculum_pillar_academic_desc',
                    'curriculum_pillar_creative_title', 'curriculum_pillar_creative_desc',
                    'curriculum_timeline_badge', 'curriculum_timeline_title', 'curriculum_timeline_description',
                    'curriculum_stage_foundation_title', 'curriculum_stage_foundation_desc',
                    'curriculum_stage_discovery_title', 'curriculum_stage_discovery_desc',
                    'curriculum_stage_readiness_title', 'curriculum_stage_readiness_desc',
                    'curriculum_env_badge', 'curriculum_env_title', 'curriculum_env_description',
                    'curriculum_zone_construction_title', 'curriculum_zone_construction_desc',
                    'curriculum_zone_atelier_title', 'curriculum_zone_atelier_desc',
                    'curriculum_zone_literacy_title', 'curriculum_zone_literacy_desc',
                    'curriculum_milestones_title', 'curriculum_milestones_subtitle',
                    'curriculum_milestone_tracking_title', 'curriculum_milestone_tracking_desc',
                    'curriculum_milestone_tracking_bullet1', 'curriculum_milestone_tracking_bullet2',
                    'curriculum_milestone_screenings_title', 'curriculum_milestone_screenings_desc',
                    'curriculum_milestone_screenings_bullet1', 'curriculum_milestone_screenings_bullet2',
                    'curriculum_milestone_assessments_title', 'curriculum_milestone_assessments_desc',
                    'curriculum_milestone_assessments_bullet1', 'curriculum_milestone_assessments_bullet2',
                    'curriculum_cta_title', 'curriculum_cta_description'
                ];
            case 'page-employers.php':
                return [
                    'employers_hero_badge', 'employers_hero_title', 'employers_hero_description',
                    'employers_intro_title', 'employers_intro_description',
                    'employers_benefit1_title', 'employers_benefit1_desc',
                    'employers_benefit2_title', 'employers_benefit2_desc',
                    'employers_benefit3_title', 'employers_benefit3_desc',
                    'employers_solutions_title', 'employers_solutions_desc',
                    'employers_solution1_title', 'employers_solution1_desc', 'employers_solution1_features',
                    'employers_solution2_title', 'employers_solution2_desc', 'employers_solution2_features',
                    'employers_solution3_title', 'employers_solution3_desc', 'employers_solution3_features',
                    'employers_tax_title', 'employers_tax_desc',
                    'employers_federal_title', 'employers_federal_desc',
                    'employers_state_title', 'employers_state_desc',
                    'employers_tax_disclaimer',
                    'employers_cta_title', 'employers_cta_desc', 'employers_cta_button_text'
                ];
            case 'front-page.php':
                return [
                    // Post meta keys
                    'home_hero_heading', 'home_hero_subheading', 'home_hero_cta_label', 'home_hero_secondary_label',
                    'home_prismpath_eyebrow', 'home_prismpath_heading', 'home_prismpath_subheading', 'home_prismpath_cta_label',
                    'home_prismpath_readiness_heading', 'home_prismpath_readiness_desc',
                    'home_locations_heading', 'home_locations_subheading', 'home_locations_cta_label',
                    'home_faq_heading', 'home_faq_subheading',
                    // Theme mod keys (for chroma_get_theme_mod fallback)
                    'chroma_home_hero_heading', 'chroma_home_hero_subheading', 
                    'chroma_home_hero_cta_label', 'chroma_home_hero_secondary_label',
                    'chroma_home_prismpath_eyebrow', 'chroma_home_prismpath_heading', 
                    'chroma_home_prismpath_subheading', 'chroma_home_prismpath_cta_label',
                    'chroma_home_locations_heading', 'chroma_home_locations_subheading',
                    'chroma_home_faq_heading', 'chroma_home_faq_subheading'
                ];

            case 'page-parents.php':
                return [
                    'parents_hero_badge', 'parents_hero_title', 'parents_hero_desc',
                    'parents_essentials_title', 'parents_essentials_desc',
                    'parents_resource_procare_title', 'parents_resource_procare_desc', 'parents_resource_procare_button_text',
                    'parents_resource_handbook_title', 'parents_resource_handbook_desc', 'parents_resource_handbook_button_text',
                    'parents_events_title', 'parents_events_desc',
                    'parents_nutrition_title', 'parents_nutrition_desc',
                    'parents_safety_title', 'parents_safety_desc',
                    'parents_safety1_title', 'parents_safety1_desc',
                    'parents_safety2_title', 'parents_safety2_desc',
                    'parents_safety3_title', 'parents_safety3_desc',
                    'parents_faq_title', 'parents_faq_desc',
                    'parents_faq1_question', 'parents_faq1_answer',
                    'parents_faq2_question', 'parents_faq2_answer',
                    'parents_faq3_question', 'parents_faq3_answer',
                    'parents_faq4_question', 'parents_faq4_answer',
                    'parents_faq5_question', 'parents_faq5_answer',
                    'parents_referral_title', 'parents_referral_desc', 'parents_referral_button_text'
                ];
            case 'page-privacy.php':
                return [
                    'privacy_last_updated', 'privacy_intro_title', 'privacy_intro_content',
                    'privacy_section1_title', 'privacy_section1_content',
                    'privacy_section2_title', 'privacy_section2_content',
                    'privacy_section3_title', 'privacy_section3_content',
                    'privacy_section4_title', 'privacy_section4_content',
                    'privacy_section5_title', 'privacy_section5_content',
                    'privacy_section6_title', 'privacy_section6_content',
                    'privacy_section7_title', 'privacy_section7_content',
                    'privacy_section8_title', 'privacy_section8_content'
                ];
            case 'page-terms.php':
                return [
                     'tos_last_updated', 'tos_intro_title', 'tos_intro_content',
                     'tos_section1_title', 'tos_section1_content',
                ];
            default:
                return [];
        }


    }

    /**
     * Translate Fields in Bulk

     * 
     * @param array $fields Associative array of text to translate ['key1' => 'Text 1', 'key2' => 'Text 2']
     * @param string $target_lang Target language code
     * @param string $context Context description
     * @return array Translated fields ['key1' => 'Translated 1', ...]
     */
    public static function translate_bulk($fields, $target_lang = 'es', $context = '', $force = false)
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
        
        if (!$force) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                // Return cached translation
                return array_merge($fields, $cached);
            }
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

        $content = $response['choices'][0]['message']['content'] ?? '';
        
        // Clean markdown code blocks (Commonly returned by Gemini/Claude)
        $content = preg_replace('/^```(?:json)?\s*|```$/m', '', trim($content));
        
        // Attempt to find JSON if there's surrounding text
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $content = $matches[0];
        }

        $translated = json_decode($content, true);

        if (!$translated) {
            chroma_debug_log(' Translate: JSON Parse Failure. Raw Content: ' . substr($content, 0, 500));
            return ['_error' => 'Failed to parse translation JSON. The AI returned an invalid format.'];
        }

        // Store in translation memory cache (30 days)
        set_transient($cache_key, $translated, 30 * DAY_IN_SECONDS);

        // Merge back into original
        return array_merge($fields, $translated);
    }
}



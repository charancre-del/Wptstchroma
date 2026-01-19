<?php
/**
 * Location LLM Prompt Meta Box
 * Allows editing LLM description and recommendation criteria
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Location_LLM_Prompt_Meta_Box extends Chroma_Advanced_SEO_Meta_Box_Base
{
    public function get_id()
    {
        return 'chroma_location_llm_prompt';
    }

    public function get_title()
    {
        return __('LLM Description & Recommendations', 'chroma-excellence');
    }

    public function get_post_types()
    {
        return ['location'];
    }

    public function render_fields($post)
    {
        // Get current values
        $llm_description = get_post_meta($post->ID, 'seo_llm_description', true);
        $when_to_recommend = get_post_meta($post->ID, 'seo_llm_when_to_recommend', true) ?: [];

        // Get fallback
        $fallback_description = Chroma_Fallback_Resolver::get_llm_description($post->ID);

        echo '<div style="margin-bottom: 20px;">';
        echo '<p class="description"><strong>Help LLMs understand when and how to recommend this location.</strong></p>';
        echo '</div>';

        // LLM Description
        echo '<h4>' . __('Natural Language Description', 'chroma-excellence') . '</h4>';

        $this->render_textarea_field([
            'id' => 'seo_llm_description',
            'label' => __('LLM-Optimized Description', 'chroma-excellence'),
            'value' => $llm_description,
            'description' => 'A natural, conversational description LLMs will use when citing this location',
            'fallback_notice' => empty($llm_description) ? $fallback_description : '',
            'rows' => 4,
        ]);

        // When to recommend
        echo '<h4 style="margin-top: 20px;">' . __('"When to Recommend" Criteria', 'chroma-excellence') . '</h4>';
        echo '<p class="description">Explicit scenarios when LLMs should suggest this location.</p>';

        $this->render_repeater_field([
            'id' => 'seo_llm_when_to_recommend',
            'label' => '',
            'values' => $when_to_recommend,
            'placeholder' => 'e.g., "User asks for quality daycare in Canton"',
            'button_text' => 'Add Scenario',
        ]);
    }

    public function save_fields($post_id)
    {
        // Save LLM description
        if (isset($_POST['seo_llm_description'])) {
            $description = Chroma_Field_Sanitizer::sanitize_textarea($_POST['seo_llm_description']);
            update_post_meta($post_id, 'seo_llm_description', $description);
        }

        // Save when to recommend
        if (isset($_POST['seo_llm_when_to_recommend'])) {
            $scenarios = Chroma_Field_Sanitizer::sanitize_text_array($_POST['seo_llm_when_to_recommend']);
            update_post_meta($post_id, 'seo_llm_when_to_recommend', array_filter($scenarios));
        } else {
            update_post_meta($post_id, 'seo_llm_when_to_recommend', []);
        }
    }
}



<?php
/**
 * Location LLM Context Meta Box
 * Allows editing LLM-specific targeting fields
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Location_LLM_Context_Meta_Box extends Chroma_Advanced_SEO_Meta_Box_Base
{
    public function get_id()
    {
        return 'chroma_location_llm_context';
    }

    public function get_title()
    {
        return __('LLM Context & Targeting', 'chroma-excellence');
    }

    public function get_post_types()
    {
        return ['location'];
    }

    public function render_fields($post)
    {
        // Get current values
        $primary_intent = get_post_meta($post->ID, 'seo_llm_primary_intent', true);
        $target_queries = get_post_meta($post->ID, 'seo_llm_target_queries', true) ?: [];
        $key_differentiators = get_post_meta($post->ID, 'seo_llm_key_differentiators', true) ?: [];

        // Get fallbacks
        $fallback_queries = Chroma_Fallback_Resolver::get_llm_target_queries($post->ID);
        $fallback_differentiators = Chroma_Fallback_Resolver::get_llm_key_differentiators($post->ID);

        echo '<div style="margin-bottom: 20px;">';
        echo '<p class="description"><strong>Optimize how AI assistants (ChatGPT, Claude, Perplexity) recommend this location.</strong></p>';
        echo '</div>';

        // Primary intent
        $this->render_text_field([
            'id' => 'seo_llm_primary_intent',
            'label' => __('Primary Intent', 'chroma-excellence'),
            'value' => $primary_intent,
            'placeholder' => 'childcare_discovery',
            'description' => 'What is the primary search intent this location serves?',
            'fallback_notice' => empty($primary_intent) ? 'childcare_discovery' : '',
            'class' => 'regular-text',
        ]);

        // Target queries
        echo '<h4 style="margin-top: 20px;">' . __('Target Queries', 'chroma-excellence') . '</h4>';
        echo '<p class="description">Natural language queries where LLMs should recommend this location.</p>';

        if (!empty($fallback_queries) && empty($target_queries)) {
            echo '<p class="description fallback-notice"><em>Auto-generated examples: ' . implode(', ', array_slice($fallback_queries, 0, 2)) . '</em></p>';
        }

        $this->render_repeater_field([
            'id' => 'seo_llm_target_queries',
            'label' => '',
            'values' => $target_queries,
            'placeholder' => 'e.g., "best daycare near Canton GA"',
            'button_text' => 'Add Query',
        ]);

        // Key differentiators
        echo '<h4 style="margin-top: 20px;">' . __('Key Differentiators', 'chroma-excellence') . '</h4>';
        echo '<p class="description">What makes this location stand out? LLMs use these as talking points.</p>';

        if (!empty($fallback_differentiators) && empty($key_differentiators)) {
            echo '<p class="description fallback-notice"><em>Auto-discovered: ' . implode('; ', array_slice($fallback_differentiators, 0, 2)) . '</em></p>';
        }

        $this->render_repeater_field([
            'id' => 'seo_llm_key_differentiators',
            'label' => '',
            'values' => $key_differentiators,
            'placeholder' => 'e.g., "Quality Rated by Bright from the Start"',
            'button_text' => 'Add Differentiator',
        ]);
    }

    public function save_fields($post_id)
    {
        // Save primary intent
        if (isset($_POST['seo_llm_primary_intent'])) {
            $intent = Chroma_Field_Sanitizer::sanitize_text($_POST['seo_llm_primary_intent']);
            update_post_meta($post_id, 'seo_llm_primary_intent', $intent);
        }

        // Save target queries
        if (isset($_POST['seo_llm_target_queries'])) {
            $queries = Chroma_Field_Sanitizer::sanitize_text_array($_POST['seo_llm_target_queries']);
            update_post_meta($post_id, 'seo_llm_target_queries', array_filter($queries));
        } else {
            update_post_meta($post_id, 'seo_llm_target_queries', []);
        }

        // Save key differentiators
        if (isset($_POST['seo_llm_key_differentiators'])) {
            $differentiators = Chroma_Field_Sanitizer::sanitize_text_array($_POST['seo_llm_key_differentiators']);
            update_post_meta($post_id, 'seo_llm_key_differentiators', array_filter($differentiators));
        } else {
            update_post_meta($post_id, 'seo_llm_key_differentiators', []);
        }
    }
}



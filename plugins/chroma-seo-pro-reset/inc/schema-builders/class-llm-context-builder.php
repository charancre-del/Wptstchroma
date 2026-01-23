<?php
/**
 * LLM Context Builder
 * Builds LLM context data from meta or fallbacks
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_LLM_Context_Builder
{
    /**
     * Build LLM context array for a location
     *
     * @param int $location_id Location post ID
     * @return array|null LLM context data or null
     */
    public static function build($location_id)
    {
        $context = [];

        // Primary intent
        $intent = get_post_meta($location_id, 'seo_llm_primary_intent', true);
        if (!$intent) {
            $intent = 'childcare_discovery';
        }
        $context['primary_intent'] = $intent;

        // Target queries
        $queries = Chroma_Fallback_Resolver::get_llm_target_queries($location_id);
        if (!empty($queries)) {
            $context['target_queries'] = $queries;
        }

        // Key differentiators
        $differentiators = Chroma_Fallback_Resolver::get_llm_key_differentiators($location_id);
        if (!empty($differentiators)) {
            $context['key_differentiators'] = $differentiators;
        }

        return !empty($context) ? $context : null;
    }

    /**
     * Build LLM prompt fields for a location
     *
     * @param int $location_id Location post ID
     * @return array LLM prompt data
     */
    public static function build_prompt_fields($location_id)
    {
        $data = [];

        // Description
        $description = Chroma_Fallback_Resolver::get_llm_description($location_id);
        if ($description) {
            $data['llm_description'] = $description;
        }

        // When to recommend
        $when_to_recommend = get_post_meta($location_id, 'seo_llm_when_to_recommend', true);
        if (!empty($when_to_recommend) && is_array($when_to_recommend)) {
            $data['llm_when_to_recommend'] = array_filter($when_to_recommend);
        }

        // Comparison factors
        $factors = Chroma_Fallback_Resolver::get_comparison_factors($location_id);
        if (!empty($factors)) {
            $data['comparisonFactors'] = $factors;
        }

        return $data;
    }
}



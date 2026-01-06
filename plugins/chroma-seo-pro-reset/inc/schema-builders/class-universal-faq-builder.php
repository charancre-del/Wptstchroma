<?php
/**
 * Universal FAQ Schema Builder
 * Generates JSON-LD for FAQPage Schema from universal meta box
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Universal_FAQ_Builder
{
    /**
     * Output FAQ schema
     */
    public static function output()
    {
        if (!is_singular()) {
            return;
        }

        // Check if disabled globally (User Preference for Otto)
        if (get_option('chroma_faq_schema_disabled', 'no') === 'yes') {
            return;
        }

        // Check for manual override (AI Fixed Schema)
        $override = get_post_meta(get_queried_object_id(), '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        $post_id = get_the_ID();
        $faqs = get_post_meta($post_id, 'chroma_faq_items', true);

        if (empty($faqs) || !is_array($faqs)) {
            return;
        }

        $main_entity = [];
        foreach ($faqs as $faq) {
            // Skip if question or answer is empty
            $question = isset($faq['question']) ? trim($faq['question']) : '';
            $answer = isset($faq['answer']) ? trim($faq['answer']) : '';

            if (empty($question) || empty($answer)) {
                continue;
            }

            $main_entity[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer
                ]
            ];
        }

        // Only output if we have valid FAQ items
        if (empty($main_entity)) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $main_entity
        ];

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}

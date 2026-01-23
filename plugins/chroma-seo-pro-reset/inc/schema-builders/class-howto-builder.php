<?php
/**
 * HowTo Schema Builder
 * Generates JSON-LD for HowTo Schema
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_HowTo_Schema_Builder
{
    /**
     * Build HowTo schema for a location
     *
     * @param int $post_id Location ID
     * @return array|null Schema array or null
     */
    public static function build($post_id)
    {
        $steps = get_post_meta($post_id, 'location_enrollment_steps', true);
        if (empty($steps) || !is_array($steps)) {
            return null;
        }

        $step_objects = [];
        foreach ($steps as $step) {
            $step_objects[] = [
                '@type' => 'HowToStep',
                'name' => $step['title'],
                'text' => $step['text'],
                'url' => !empty($step['url']) ? $step['url'] : get_permalink($post_id),
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => sprintf(__('How to Enroll at %s', 'chroma-excellence'), get_the_title($post_id)),
            'description' => sprintf(__('Step-by-step guide to enrolling your child at %s.', 'chroma-excellence'), get_the_title($post_id)),
            'step' => $step_objects,
        ];
    }

    /**
     * Output schema to head
     */
    public static function output()
    {
        if (!is_singular('location')) {
            return;
        }

        // Check for manual override (AI Fixed Schema)
        $override = get_post_meta(get_queried_object_id(), '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        $schema = self::build(get_the_ID());
        if ($schema) {
            Chroma_Schema_Registry::register($schema, ['source' => 'howto-builder']);
        }
    }
}



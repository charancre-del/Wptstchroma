<?php
/**
 * LearningResource Schema Builder
 * 
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Learning_Resource_Builder
{
    public static function output()
    {
        if (!is_singular('program')) {
            return;
        }

        $post_id = get_the_ID();
        $resource = get_post_meta($post_id, '_chroma_learning_resource', true);

        if (empty($resource) || empty($resource['name'])) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LearningResource',
            'name' => $resource['name'],
            'description' => isset($resource['description']) ? $resource['description'] : get_the_excerpt(),
            'learningResourceType' => isset($resource['type']) ? $resource['type'] : 'Curriculum',
            'educationalLevel' => get_post_meta($post_id, 'program_age_range', true) ?: 'Early Childhood',
        ];

        Chroma_Schema_Registry::register($schema, ['source' => 'learning-resource-builder']);
    }
}



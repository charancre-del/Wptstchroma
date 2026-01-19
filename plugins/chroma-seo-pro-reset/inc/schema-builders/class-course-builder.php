<?php
/**
 * Course Schema Builder
 * Generates Course and CourseInstance schema for educational programs
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Course_Builder
{
    /**
     * Output Course Schema
     */
    public static function output()
    {
        if (!is_singular('program')) {
            return;
        }

        $post_id = get_the_ID();
        $title = get_the_title($post_id);

        // Filter: Only apply to relevant programs (Pre-K, Preschool, Kindergarten)
        if (stripos($title, 'Pre-K') === false && stripos($title, 'Preschool') === false && stripos($title, 'Kindergarten') === false) {
            return;
        }

        // Check for manual override (AI Fixed Schema)
        $override = get_post_meta($post_id, '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        $description = get_the_excerpt($post_id) ?: wp_trim_words(get_the_content(), 55);
        
        // Course Schema (Parent)
        $course_schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $title,
            'description' => $description,
            'provider' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'sameAs' => home_url()
            ],
            // Link to the specific instance
            'hasCourseInstance' => [
                '@type' => 'CourseInstance',
                'name' => $title . ' - Current Session',
                'description' => $description,
                'courseMode' => 'Onsite',
                'location' => [
                     '@type' => 'Place',
                     // Since programs are abstract until tied to a location, we can't easily put a specific address here
                     // unless we loop through all locations offering it. 
                     // For now, let's omit specific location address to avoid invalid multiplicity or confusion.
                     'name' => get_bloginfo('name') . ' Centers' 
                ]
            ]
        ];

        Chroma_Schema_Registry::register($course_schema, ['source' => 'course-builder']);
    }
}



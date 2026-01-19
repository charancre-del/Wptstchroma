<?php
/**
 * Page Type Schema Builder
 * Generates specific schema for About and Contact pages
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Page_Type_Builder
{
    /**
     * Output schema
     */
    public static function output()
    {
        if (!is_page()) {
            return;
        }

        // Check for manual override (AI Fixed Schema)
        $override = get_post_meta(get_queried_object_id(), '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        $post_id = get_the_ID();
        $slug = get_post_field('post_name', $post_id);
        $schema = null;

        // Check for About Page
        if ($slug === 'about' || $slug === 'about-us' || $slug === 'our-story') {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'AboutPage',
                'mainEntity' => [
                    '@type' => 'Organization',
                    '@id' => home_url() . '#organization',
                    'name' => get_bloginfo('name'),
                    'description' => get_the_excerpt($post_id)
                ],
                'name' => get_the_title($post_id),
                'url' => get_permalink($post_id)
            ];
        }

        // Check for Contact Page
        if ($slug === 'contact' || $slug === 'contact-us' || $slug === 'get-in-touch') {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'ContactPage',
                'mainEntity' => [
                    '@type' => 'Organization',
                    '@id' => home_url() . '#organization'
                ],
                'name' => get_the_title($post_id),
                'url' => get_permalink($post_id)
            ];
        }

        if ($schema) {
            Chroma_Schema_Registry::register($schema, ['source' => 'page-type-builder']);
        }
    }
}



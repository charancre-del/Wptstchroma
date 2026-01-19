<?php
/**
 * Article/BlogPosting Schema Builder
 * 
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Article_Builder
{
    public static function output()
    {
        if (!is_singular('post')) {
            return;
        }

        $post_id = get_the_ID();
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id)
            ],
            'headline' => get_the_title($post_id),
            'image' => [
                '@type' => 'ImageObject',
                'url' => get_the_post_thumbnail_url($post_id, 'full')
            ],
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', get_post_field('post_author', $post_id)),
                'url' => get_author_posts_url(get_post_field('post_author', $post_id))
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : ''
                ]
            ],
            'description' => get_the_excerpt($post_id)
        ];

        Chroma_Schema_Registry::register($schema, ['source' => 'article-builder']);
    }
}



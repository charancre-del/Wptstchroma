<?php
/**
 * Archive ItemList Schema Builder
 * Auto-generates ItemList schema for archive pages (locations, programs)
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Archive_ItemList_Builder
{
    /**
     * Output ItemList schema for archive pages
     */
    public static function output()
    {
        if (!is_post_type_archive(['location', 'program'])) {
            return;
        }

        global $wp_query;
        $post_type = get_post_type();
        
        // Build ItemList
        $items = [];
        $position = 1;
        
        while (have_posts()) {
            the_post();
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => get_the_title(),
                'url' => get_permalink()
            ];
            $position++;
        }
        
        // Reset query
        wp_reset_postdata();
        
        if (empty($items)) {
            return;
        }

        // Determine schema type based on post type
        $list_name = ($post_type === 'location') ? 'Our Locations' : 'Our Programs';
        $list_desc = ($post_type === 'location') 
            ? 'All Chroma Early Learning childcare locations' 
            : 'All educational programs at Chroma Early Learning';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $list_name,
            'description' => $list_desc,
            'url' => get_post_type_archive_link($post_type),
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $items,
                'numberOfItems' => count($items)
            ]
        ];

        Chroma_Schema_Registry::register($schema, ['source' => 'archive-itemlist-builder']);
    }
}



<?php
/**
 * SpecialAnnouncement Schema Builder
 * 
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Special_Announcement_Builder
{
    public static function output()
    {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        $announcement = get_post_meta($post_id, '_chroma_special_announcement', true);

        if (empty($announcement) || empty($announcement['text'])) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'SpecialAnnouncement',
            'name' => isset($announcement['title']) ? $announcement['title'] : get_the_title(),
            'text' => $announcement['text'],
            'datePosted' => get_the_date('c'),
        ];

        if (!empty($announcement['category'])) {
            $schema['category'] = $announcement['category'];
        }

        if (!empty($announcement['expires'])) {
            $schema['expires'] = date('c', strtotime($announcement['expires']));
        }

        Chroma_Schema_Registry::register($schema, ['source' => 'special-announcement-builder']);
    }
}



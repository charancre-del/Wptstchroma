<?php
/**
 * Archive page schema builder.
 *
 * OTTO appears to infer the "primary page" for archive routes from any
 * head-level child-item lists we expose. Archive routes should therefore
 * describe the archive page itself, not its first child entity.
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
     * Output self-referential CollectionPage schema for archive pages.
     */
    public static function output()
    {
        if (!is_post_type_archive(['location', 'program', 'city'])) {
            return;
        }

        $post_type = self::get_current_archive_post_type();
        if ($post_type === '') {
            return;
        }

        $archive_url = self::get_current_archive_url($post_type);
        if ($archive_url === '') {
            return;
        }

        [$list_name, $list_desc] = self::get_archive_metadata($post_type);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => trailingslashit($archive_url) . '#collectionpage',
            'name' => $list_name,
            'description' => $list_desc,
            'url' => $archive_url,
        ];

        Chroma_Schema_Registry::register($schema, ['source' => 'archive-itemlist-builder']);
    }

    /**
     * Resolve the current archive post type in a route-safe way.
     *
     * @return string
     */
    private static function get_current_archive_post_type()
    {
        $post_type = get_post_type();
        if (is_string($post_type) && $post_type !== '') {
            return $post_type;
        }

        $queried_object = get_queried_object();
        if (is_object($queried_object) && !empty($queried_object->name)) {
            return (string) $queried_object->name;
        }

        $query_post_type = get_query_var('post_type');
        if (is_array($query_post_type)) {
            return (string) reset($query_post_type);
        }

        return is_string($query_post_type) ? $query_post_type : '';
    }

    /**
     * Use the current request path so localized archives stay localized.
     *
     * @param string $post_type
     * @return string
     */
    private static function get_current_archive_url($post_type)
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);

        if (is_string($path) && $path !== '') {
            return home_url(user_trailingslashit($path));
        }

        $fallback = get_post_type_archive_link($post_type);

        return is_string($fallback) ? $fallback : '';
    }

    /**
     * Human-friendly archive metadata.
     *
     * @param string $post_type
     * @return array{0:string,1:string}
     */
    private static function get_archive_metadata($post_type)
    {
        switch ($post_type) {
            case 'location':
                return [
                    'Our Locations',
                    'Explore all Chroma Early Learning locations across Metro Atlanta.',
                ];

            case 'program':
                return [
                    'Our Programs',
                    'Explore Chroma early learning programs for every age, from infant care and toddler classrooms to GA Pre-K, after-school, and seasonal camps.',
                ];

            case 'city':
                return [
                    'Our Communities',
                    'Explore Chroma communities across Georgia and find campuses, programs, and tour information near your family.',
                ];

            default:
                return [
                    (string) get_the_archive_title(),
                    trim(wp_strip_all_tags((string) get_the_archive_description())),
                ];
        }
    }
}



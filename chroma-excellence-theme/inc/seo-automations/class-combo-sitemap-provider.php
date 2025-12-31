<?php
/**
 * Custom Sitemap Provider for Combo Pages
 * Integration with Native WordPress Sitemaps
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Combo_Sitemap_Provider extends WP_Sitemaps_Provider {

    public function __construct() {
        $this->name = 'combos'; // sitemap-combos.xml
        $this->object_type = 'custom'; 
    }

    /**
     * Get a list of URLs for a sitemap.
     *
     * @param int    $page_num       The page number.
     * @param string $object_subtype The object subtype.
     * @return array[] Array of URL information.
     */
    public function get_url_list($page_num, $object_subtype = '') {
        $urls = [];
        $combos = Chroma_Combo_Page_Generator::get_all_combos();
        
        // Filter for published status
        // Since we don't have a direct index, we iterate.
        // For performance on large sets, we might need an index option later.
        
        $published_combos = [];
        foreach ($combos as $combo) {
            $program_slug = $combo['program']->post_name;
            $city_slug = sanitize_title($combo['city']);
            $state = $combo['state'];
            
            // Check saved status
            $saved_data = Chroma_Combo_Page_Data::get($program_slug, $city_slug, $state);
            $status = $saved_data['status'] ?? 'auto'; // Default is auto (draft-like)
            
            // "Auto" is considered draft until published unless auto-publish is on?
            // Actually user interaction history suggests "Auto" is generic.
            // Let's only include explicitly 'published'.
            // Wait, previous fix aliased 'publish' to 'Published'. The internal key is 'published'.
            
            if ($status === 'published' || $status === 'publish') {
                $published_combos[] = $combo;
            }
        }
        
        // Pagination logic
        // WP Sitemaps default limit is 2000 per page.
        $per_page = 2000;
        $offset = ($page_num - 1) * $per_page;
        $page_combos = array_slice($published_combos, $offset, $per_page);
        
        foreach ($page_combos as $combo) {
            $urls[] = [
                'loc' => $combo['url'],
                'lastmod' => date('c'), // Dynamic pages, assume fresh
                'changefreq' => 'weekly',
                'priority' => 0.8,
            ];
        }

        return $urls;
    }

    /**
     * Get the max number of pages available for the object subtype.
     *
     * @param string $object_subtype The object subtype.
     * @return int Total number of pages.
     */
    public function get_max_num_pages($object_subtype = '') {
        // We have to calculate total published combos to know pages.
        // This is inefficient but necessary without a centralized index.
        $combos = Chroma_Combo_Page_Generator::get_all_combos();
        $count = 0;
        foreach ($combos as $combo) {
            $program_slug = $combo['program']->post_name;
            $city_slug = sanitize_title($combo['city']);
            $state = $combo['state'];
            $saved_data = Chroma_Combo_Page_Data::get($program_slug, $city_slug, $state);
            $status = $saved_data['status'] ?? 'auto';
            if ($status === 'published' || $status === 'publish') {
                $count++;
            }
        }
        
        return ceil($count / 2000);
    }
}

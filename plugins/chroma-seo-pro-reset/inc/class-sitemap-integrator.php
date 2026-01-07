<?php
/**
 * Sitemap Integrator
 * Injects Spanish URLs into the native WordPress XML sitemap.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Sitemap_Integrator
{
    public function init()
    {
        // If we are already in the 'init' hook (called from bootstrap), run immediately.
        if (did_action('init')) {
            $this->register_spanish_provider();
        } else {
            add_action('init', [$this, 'register_spanish_provider']);
        }
    }

    public function register_spanish_provider()
    {
        $provider = new Chroma_Spanish_Sitemap_Provider();
        wp_register_sitemap_provider('spanish', $provider);
    }
}

/**
 * Custom Sitemap Provider for Spanish Content
 */
class Chroma_Spanish_Sitemap_Provider extends WP_Sitemaps_Provider {
    
    public function __construct() {
        $this->name = 'spanish';
        $this->object_type = 'custom'; 
    }

    private $per_page = 200;
    private $post_types = ['page', 'location', 'program', 'post'];

    /**
     * Get URL list for a sitemap page
     */
    public function get_url_list($page_num, $object_subtype = '') {
        $urls = [];
        $offset = ($page_num - 1) * $this->per_page;
        
        $posts = get_posts([
            'post_type' => $this->post_types,
            'posts_per_page' => $this->per_page,
            'offset' => $offset,
            'post_status' => 'publish',
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC'
        ]);

        foreach ($posts as $post_id) {
            if(class_exists('Chroma_Multilingual_Manager')) {
                $alternates = Chroma_Multilingual_Manager::get_alternates($post_id);
                if (!empty($alternates['es'])) {
                    $urls[] = [
                        'loc' => $alternates['es'],
                        'lastmod' => get_the_modified_date('c', $post_id),
                    ];
                }
            }
        }

        return $urls;
    }

    /**
     * Get max number of pages
     */
    public function get_max_num_pages($object_subtype = '') {
        $total = 0;
        foreach ($this->post_types as $type) {
            $count = wp_count_posts($type);
            $total += isset($count->publish) ? $count->publish : 0;
        }
        return max(1, ceil($total / $this->per_page));
    }
}

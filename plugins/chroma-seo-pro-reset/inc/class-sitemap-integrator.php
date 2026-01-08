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
        add_action('init', [$this, 'register_providers']);
    }

    public function register_providers()
    {
        // One sitemap for all Spanish pages/posts (Singulars)
        wp_register_sitemap_provider('spanish', new Chroma_Spanish_Sitemap_Provider());
    }
}

/**
 * Custom Sitemap Provider for Spanish Content
 * Includes all translated post types (Singulars Only)
 */
class Chroma_Spanish_Sitemap_Provider extends WP_Sitemaps_Provider {
    
    public function __construct() {
        $this->name = 'spanish';
        $this->object_type = 'custom'; 
    }

    private $per_page = 2000;
    private $post_types = ['page', 'location', 'program', 'city', 'post', 'team_member'];

    public function get_url_list($page_num, $object_subtype = '') {
        $urls = [];
        
        // Static Post Types
        $posts = get_posts([
            'post_type' => $this->post_types,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',
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

        // Pagination
        $offset = ($page_num - 1) * $this->per_page;
        return array_slice($urls, $offset, $this->per_page);
    }

    public function get_max_num_pages($object_subtype = '') {
        $count = 0;
        foreach ($this->post_types as $type) {
            $count += (int)wp_count_posts($type)->publish;
        }
        return max(1, ceil($count / $this->per_page));
    }
}

<?php
/**
 * LLMs.txt Generator
 * Generates a /llms.txt file for AI crawlers to better understand the site structure.
 * Standard: https://llmstxt.org/
 * 
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_LLMs_Txt_Generator
{
    /**
     * Init hooks
     */
    public function init()
    {
        // Remove virtual rewrite rules
        // add_action('init', [$this, 'add_rewrite_rule']);
        // add_filter('query_vars', [$this, 'add_query_var']);
        // add_action('template_redirect', [$this, 'render_file']);

        // Physical File Generation Hooks
        add_action('admin_init', [$this, 'write_physical_file']); // Force check on admin load
        add_action('save_post', [$this, 'write_physical_file']);
        add_action('wp_ajax_chroma_save_llm_targeting', [$this, 'write_physical_file'], 20);
    }

    /**
     * Write Physical File
     */
    public function write_physical_file()
    {
        // Only run if we are in admin or it's an AJAX save
        if (!is_admin() && !wp_doing_ajax()) {
            return;
        }

        $file_path = ABSPATH . 'llms.txt';
        $content = $this->generate_content();

        // Write file
        $handle = @fopen($file_path, 'w');
        if ($handle) {
            fwrite($handle, $content);
            fclose($handle);
        }
    }

    // Deprecated Rewrite Functions (kept commented out or removed for clarity)
    // public function add_rewrite_rule() ... 
    // public function add_query_var($vars) ...
    // public function render_file() ...

    /**
     * Helper to get LLM Context
     */
    private function get_llm_context($post_id)
    {
        $parts = [];
        
        $intent = get_post_meta($post_id, 'seo_llm_primary_intent', true);
        $queries = get_post_meta($post_id, 'seo_llm_target_queries', true) ?: [];
        $diffs = get_post_meta($post_id, 'seo_llm_key_differentiators', true) ?: [];

        if ($intent) {
            $parts[] = "Primary Intent: {$intent}.";
        }

        if (!empty($diffs)) {
            $parts[] = "Key Features: " . implode('; ', array_slice($diffs, 0, 3)) . ".";
        }

        if (!empty($queries)) {
            $parts[] = "Relevant for: " . implode(', ', array_slice($queries, 0, 5)) . ".";
        }

        if (empty($parts)) {
            return "";
        }

        // Output as a single clean blockquote line for better parsing compliance
        return "  > " . implode(' ', $parts) . "\n";
    }

    /**
     * Generate Content
     */
    private function generate_content()
    {
        $site_name = get_bloginfo('name');
        $site_desc = get_bloginfo('description');
        $url = home_url();

        $output = "# {$site_name}\n";
        $output .= "> {$site_desc}\n\n"; // Blockquote for description as per spec conventions
        
        $output .= "## Main Sections\n\n";
        $output .= "- [Home]({$url})\n";
        $output .= "- [Locations]({$url}/locations)\n";
        $output .= "- [Programs]({$url}/programs)\n";
        $output .= "- [Blog]({$url}/stories)\n";
        $output .= "- [Careers]({$url}/careers)\n\n";

        // Programs
        $output .= "## Programs (Curriculum)\n\n";
        $programs = get_posts([
            'post_type' => 'program',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        if ($programs) {
            foreach ($programs as $program) {
                $output .= "- [{$program->post_title}](" . get_permalink($program->ID) . ")\n";
                // Add brief summary if available
                if (!empty($program->post_excerpt)) {
                    $excerpt = strip_tags($program->post_excerpt);
                    $excerpt = str_replace(["\r", "\n"], " ", $excerpt);
                    $output .= "  > {$excerpt}\n";
                }
                // Add LLM Context
                $output .= $this->get_llm_context($program->ID);
            }
            $output .= "\n";
        }

        // Locations
        $output .= "## Locations (Campuses)\n\n";
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        if ($locations) {
            foreach ($locations as $location) {
                // Get City for context
                $city = get_post_meta($location->ID, 'chroma_location_city', true);
                if (!$city) {
                     // Try to get from address
                     $address = get_post_meta($location->ID, 'chroma_location_address', true);
                     if ($address && preg_match('/, ([^,]+), [A-Z]{2}/', $address, $matches)) {
                         $city = trim($matches[1]);
                     }
                }

                $title = $location->post_title;
                if ($city) {
                    $title .= " ({$city})";
                }

                $output .= "- [{$title}](" . get_permalink($location->ID) . ")\n";
                
                // Add LLM Context
                $output .= $this->get_llm_context($location->ID);
            }
            $output .= "\n";
        }

        // Cities / Service Areas
        $cities = get_posts([
            'post_type' => 'city',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        if ($cities) {
            $output .= "## Service Areas (Cities)\n\n";
            foreach ($cities as $city) {
                $output .= "- [{$city->post_title}](" . get_permalink($city->ID) . ")\n";
                $output .= $this->get_llm_context($city->ID);
            }
            $output .= "\n";
        }

        // Core Pages (Only those with specific LLM intent set)
        $pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'seo_llm_primary_intent',
                    'value' => '',
                    'compare' => '!='
                ]
            ]
        ]);

        if ($pages) {
            $output .= "## Core Information\n\n";
            foreach ($pages as $page) {
                $output .= "- [{$page->post_title}](" . get_permalink($page->ID) . ")\n";
                $output .= $this->get_llm_context($page->ID);
            }
            $output .= "\n";
        }

        // Blog Posts (Only those with specific LLM intent set)
        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => 'seo_llm_primary_intent',
                    'value' => '',
                    'compare' => '!='
                ]
            ]
        ]);

        if ($posts) {
            $output .= "## Blog & Updates\n\n";
            foreach ($posts as $p) {
                $output .= "- [{$p->post_title}](" . get_permalink($p->ID) . ")\n";
                $output .= $this->get_llm_context($p->ID);
            }
            $output .= "\n";
        }

        // Catch-All: Any other public post type with LLM data (e.g. Events, Careers if CPT)
        $exclude_types = ['location', 'program', 'page', 'city', 'post', 'attachment', 'revision', 'nav_menu_item'];
        $public_types = get_post_types(['public' => true, '_builtin' => false]);
        $check_types = array_diff($public_types, $exclude_types);

        if (!empty($check_types)) {
            $others = get_posts([
                'post_type' => array_values($check_types),
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC',
                'meta_query' => [
                    [
                        'key' => 'seo_llm_primary_intent',
                        'value' => '',
                        'compare' => '!='
                    ]
                ]
            ]);

            if ($others) {
                $output .= "## Other Resources\n\n";
                foreach ($others as $other) {
                    $output .= "- [{$other->post_title}](" . get_permalink($other->ID) . ")\n";
                    $output .= $this->get_llm_context($other->ID);
                }
                $output .= "\n";
            }
        }

        $output .= "## About Us\n\n";
        $output .= "Chroma Early Learning Academy is a network of premium childcare and early education centers across Metro Atlanta.\n";
        $output .= "We use the Prismpath™ curriculum, focusing on physical, emotional, social, academic, and creative development.\n";

        return $output;
    }
}



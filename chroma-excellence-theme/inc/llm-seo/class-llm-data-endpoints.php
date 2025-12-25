<?php
/**
 * LLM Data Endpoints
 * Provides machine-readable JSON endpoints for each location
 * ENHANCED: Now includes LLM context and comparison factors
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_LLM_Data_Endpoints
{
    /**
     * Output FAQ schema for location pages
     * Hooked into wp_head
     */
    public static function output_location_faq_schema()
    {
        if (!is_singular('location')) {
            return;
        }

        $post_id = get_the_ID();
        $faq_items = self::build_faq_data($post_id);

        if (empty($faq_items)) {
            return;
        }

        $entities = array();
        foreach ($faq_items as $item) {
            // Validate: skip items with empty question or answer
            $question = isset($item['question']) ? trim($item['question']) : '';
            $answer = isset($item['answer']) ? trim($item['answer']) : '';

            if (empty($question) || empty($answer)) {
                continue;
            }

            $entities[] = array(
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $answer,
                ),
            );
        }

        // Only output schema if we have valid entities
        if (empty($entities)) {
            return;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        );

        echo '<script type="application/ld+json">' .
            wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) .
            '</script>' . "\n";
    }

    /**
     * Register rewrite rules for JSON endpoints
     */
    public static function register_rewrites()
    {
        // /locations/{slug}/data.json
        add_rewrite_rule(
            '^locations/([^/]+)/data\.json$',
            'index.php?chroma_location_slug=$matches[1]&chroma_data_type=data',
            'top'
        );

        // /locations/{slug}/faq.json
        add_rewrite_rule(
            '^locations/([^/]+)/faq\.json$',
            'index.php?chroma_location_slug=$matches[1]&chroma_data_type=faq',
            'top'
        );

        // /programs/{slug}/data.json
        add_rewrite_rule(
            '^programs/([^/]+)/data\.json$',
            'index.php?chroma_program_slug=$matches[1]&chroma_data_type=program_data',
            'top'
        );

        // /programs/{slug}/faq.json
        add_rewrite_rule(
            '^programs/([^/]+)/faq\.json$',
            'index.php?chroma_program_slug=$matches[1]&chroma_data_type=program_faq',
            'top'
        );
    }

    /**
     * Add custom query vars
     */
    public static function add_query_vars($vars)
    {
        $vars[] = 'chroma_location_slug';
        $vars[] = 'chroma_program_slug';
        $vars[] = 'chroma_data_type';
        return $vars;
    }

    /**
     * Maybe output JSON if the request matches our endpoints
     */
    public static function maybe_output_json()
    {
        $location_slug = get_query_var('chroma_location_slug');
        $program_slug = get_query_var('chroma_program_slug');
        $type = get_query_var('chroma_data_type');

        // Handle location endpoints
        if ($location_slug && $type) {
            $location = self::get_location_by_slug($location_slug);

            if (!$location) {
                status_header(404);
                header('Content-Type: application/json; charset=utf-8');
                echo wp_json_encode(['error' => 'Location not found']);
                exit;
            }

            header('Content-Type: application/json; charset=utf-8');

            if ('data' === $type) {
                $data = self::build_location_data($location->ID);
                echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } elseif ('faq' === $type) {
                $data = self::build_faq_data($location->ID);
                echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }

            exit;
        }

        // Handle program endpoints
        if ($program_slug && $type) {
            $program = self::get_program_by_slug($program_slug);

            if (!$program) {
                status_header(404);
                header('Content-Type: application/json; charset=utf-8');
                echo wp_json_encode(['error' => 'Program not found']);
                exit;
            }

            header('Content-Type: application/json; charset=utf-8');

            if ('program_data' === $type) {
                $data = self::build_program_data($program->ID);
                echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            } elseif ('program_faq' === $type) {
                $data = self::build_program_faq_data($program->ID);
                echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }

            exit;
        }
    }

    /**
     * Get location post by slug
     *
     * @param string $slug Location slug
     * @return WP_Post|false
     */
    private static function get_location_by_slug($slug)
    {
        $posts = get_posts([
            'name' => sanitize_title($slug),
            'post_type' => 'location',
            'post_status' => 'publish',
            'posts_per_page' => 1,
        ]);

        return !empty($posts) ? $posts[0] : false;
    }

    /**
     * Get program post by slug
     *
     * @param string $slug Program slug
     * @return WP_Post|false
     */
    private static function get_program_by_slug($slug)
    {
        $posts = get_posts([
            'name' => sanitize_title($slug),
            'post_type' => 'program',
            'post_status' => 'publish',
            'posts_per_page' => 1,
        ]);

        return !empty($posts) ? $posts[0] : false;
    }

    /**
     * Build location data array for JSON output
     * ENHANCED: Now includes LLM context and prompt fields
     *
     * @param int $post_id Location post ID
     * @return array
     */
    private static function build_location_data($post_id)
    {
        $fields = chroma_get_location_fields($post_id);

        // Get additional meta
        $hours = get_post_meta($post_id, 'location_hours', true);
        $ages_served = get_post_meta($post_id, 'location_ages_served', true);
        $special_programs = get_post_meta($post_id, 'location_special_programs', true);
        $capacity = get_post_meta($post_id, 'location_capacity', true);
        $quality_rated = get_post_meta($post_id, 'location_quality_rated', true);

        // Parse programs
        $programs = [];
        if ($special_programs) {
            $programs = array_map('trim', explode(',', $special_programs));
        }

        // Description
        $description = get_the_excerpt($post_id);
        if (!$description) {
            $description = wp_trim_words(get_post_field('post_content', $post_id), 30);
        }

        // Build structured data
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'ChildCare',
            '@id' => get_permalink($post_id),
            'location_id' => get_post_field('post_name', $post_id),
            'name' => get_the_title($post_id),
            'description' => $description,
            'url' => get_permalink($post_id),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $fields['address'],
                'addressLocality' => $fields['city'],
                'addressRegion' => $fields['state'],
                'postalCode' => $fields['zip'],
            ],
            'phone' => $fields['phone'],
        ];

        // Add optional fields if available
        if ($fields['email']) {
            $data['email'] = $fields['email'];
        }

        if ($fields['latitude'] && $fields['longitude']) {
            $data['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $fields['latitude'],
                'longitude' => (float) $fields['longitude'],
            ];
        }

        // Telephone
        if ($fields['phone']) {
            $data['telephone'] = $fields['phone'];
        }

        if ($hours) {
            $data['hours'] = $hours;
        }

        if ($ages_served) {
            $data['agesServed'] = $ages_served;
        }

        if (!empty($programs)) {
            $data['programs'] = $programs;
        }

        if ($capacity) {
            $data['capacity'] = (int) $capacity;
        }

        if ($quality_rated) {
            $data['qualityRated'] = (bool) $quality_rated;
        }

        // Director information
        $director_name = get_post_meta($post_id, 'location_director_name', true);
        $director_bio = get_post_meta($post_id, 'location_director_bio', true);
        if ($director_name) {
            $data['director'] = [
                '@type' => 'Person',
                'name' => $director_name,
            ];
            if ($director_bio) {
                $data['director']['description'] = $director_bio;
            }
        }

        // Featured image
        $image_url = get_the_post_thumbnail_url($post_id, 'full');
        if ($image_url) {
            $data['image'] = $image_url;
        }

        // Service areas
        $service_areas = get_post_meta($post_id, 'location_service_areas', true);
        if ($service_areas) {
            $data['serviceArea'] = $service_areas;
        }

        // Schools served
        $schools = get_post_meta($post_id, 'location_school_pickups', true);
        if ($schools) {
            $schools_array = array_filter(array_map('trim', explode("\n", $schools)));
            if (!empty($schools_array)) {
                $data['schoolsServed'] = $schools_array;
            }
        }

        // ADVANCED SEO/LLM: Add LLM context
        if (class_exists('Chroma_LLM_Context_Builder')) {
            $llm_context = Chroma_LLM_Context_Builder::build($post_id);
            if ($llm_context) {
                $data['llm_context'] = $llm_context;
            }

            // Add LLM prompt fields
            $prompt_fields = Chroma_LLM_Context_Builder::build_prompt_fields($post_id);
            if (!empty($prompt_fields)) {
                $data = array_merge($data, $prompt_fields);
            }
        }

        return $data;
    }

    /**
     * Build program data for JSON output
     *
     * @param int $post_id Program post ID
     * @return array
     */
    private static function build_program_data($post_id)
    {
        $data = [
            '@type' => 'Service',
            'program_id' => get_post_field('post_name', $post_id),
            'name' => get_the_title($post_id),
            'url' => get_permalink($post_id),
            'provider' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
            ],
        ];

        // Add description if available
        $excerpt = get_the_excerpt($post_id);
        if ($excerpt) {
            $data['description'] = $excerpt;
        }

        // Add featured image
        if (has_post_thumbnail($post_id)) {
            $data['image'] = get_the_post_thumbnail_url($post_id, 'large');
        }

        // Add age range if available
        $age_range = get_post_meta($post_id, 'program_age_range', true);
        if ($age_range) {
            $data['audience'] = $age_range;
        }

        return $data;
    }

    /**
     * Build FAQ data array for JSON output
     *
     * @param int $post_id Location post ID  
     * @return array
     */
    private static function build_faq_data($post_id)
    {
        $faq_raw = get_post_meta($post_id, 'location_faq_items', true);

        if (!$faq_raw) {
            return [];
        }

        // Parse FAQ items (format: Question|Answer, one per line)
        $lines = explode("\n", $faq_raw);
        $faq_items = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parts = explode('|', $line, 2);
            if (count($parts) >= 2) {
                $faq_items[] = [
                    'question' => trim($parts[0]),
                    'answer' => trim($parts[1]),
                ];
            }
        }

        return $faq_items;
    }

    /**
     * Build program FAQ data
     *
     * @param int $post_id  Program post ID
     * @return array
     */
    private static function build_program_faq_data($post_id)
    {
        // Use existing helper if available
        if (function_exists('chroma_get_program_faq_items')) {
            return chroma_get_program_faq_items($post_id);
        }

        return [];
    }
}

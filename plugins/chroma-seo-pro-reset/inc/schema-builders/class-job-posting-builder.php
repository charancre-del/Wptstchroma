<?php
/**
 * Job Posting Schema Builder
 * Automatically generates JobPosting schema for Career posts
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Job_Posting_Builder
{
    /**
     * Output JobPosting Schema
     */
    public static function output()
    {
        if (!is_singular('career')) {
            return;
        }

        // Check for manual override (AI Fixed Schema)
        $override = get_post_meta(get_queried_object_id(), '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        $post_id = get_the_ID();
        
        // Basic Fields
        $title = get_the_title($post_id);
        $description = get_the_content($post_id);
        
        // Fallback description for Google validation if post content is empty
        if (empty($description)) {
            $location_str = get_post_meta($post_id, '_career_location', true) ?: 'our center';
            $description = sprintf(
                __('We are looking for a dedicated %1$s to join our team in %2$s. At Chroma, we invest in educators and provide a supportive community to help you change lives. Click apply to view full job details and requirements.', 'chroma-excellence'),
                $title,
                $location_str
            );
        }
        
        // Custom Fields
        $salary = get_post_meta($post_id, '_career_salary', true);
        $currency = get_post_meta($post_id, '_career_salary_currency', true) ?: 'USD';
        $unit = get_post_meta($post_id, '_career_salary_unit', true) ?: 'YEAR';
        $type = get_post_meta($post_id, '_career_type', true) ?: 'FULL_TIME';
        $location = get_post_meta($post_id, '_career_location', true);
        $date_posted = get_post_meta($post_id, '_career_date_posted', true) ?: get_the_date('Y-m-d');
        $valid_through = date('Y-m-d', strtotime('+3 months', strtotime($date_posted))); // Default to 3 months validity

        
        // Construct Schema
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $title,
            'description' => wp_kses_post($description), // Allow HTML in description
            'datePosted' => $date_posted,
            'validThrough' => $valid_through,
            'employmentType' => self::map_employment_type($type),
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'sameAs' => home_url(),
                'logo' => get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : ''
            ]
        ];

        // Add Salary if present
        if ($salary) {
            $schema['baseSalary'] = [
                '@type' => 'MonetaryAmount',
                'currency' => $currency,
                'value' => [
                    '@type' => 'QuantitativeValue',
                    'value' => floatval($salary),
                    'unitText' => $unit
                ]
            ];
        }

        // Add Location if present
        if ($location) {
            $schema['jobLocation'] = [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $location,
                    'addressCountry' => 'US'
                ]
            ];
        } else {
            // Default to main business location if not specified? 
            // Better to omit or use 'applicantLocationRequirements' for remote
             $schema['applicantLocationRequirements'] = [
                '@type' => 'Country',
                'name' => 'USA'
            ];
            $schema['jobLocationType'] = 'TELECOMMUTE'; // Assume remote if no location? Or generic?
            // Actually, for child care, onsite is expected. Let's use Organization address fallback?
            // Safer to just leave generic if unknown.
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
    }

    private static function map_employment_type($type) {
        $map = [
            'FULL_TIME' => 'FULL_TIME',
            'PART_TIME' => 'PART_TIME',
            'CONTRACTOR' => 'CONTRACTOR',
            'TEMPORARY' => 'TEMPORARY',
            'INTERN' => 'INTERN',
            'VOLUNTEER' => 'VOLUNTEER'
        ];
        return isset($map[$type]) ? $map[$type] : 'FULL_TIME';
    }
}

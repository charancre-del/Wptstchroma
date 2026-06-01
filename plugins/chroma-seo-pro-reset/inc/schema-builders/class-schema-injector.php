<?php
/**
 * Schema Injector
 * Injects Organization, Person, and CourseInstance schema into relevant pages
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Invalid schema types to filter out
 * These are irrelevant types that may have been auto-generated incorrectly
 */
define('CHROMA_INVALID_SCHEMA_TYPES', array(
    'VacationRental',
    'MobileApplication',
    'SoftwareApplication',
    'WebApplication',
    'VideoGame',
    'RealEstateListing',
    'Hotel',
    'Restaurant',
    'LodgingBusiness',
    'Brand',
    'Motel',
    'Resort',
    'Hostel',
    'BedAndBreakfast',
    'Campground',
));

/**
 * Helper function to check if schema type is invalid (case-insensitive)
 */
function chroma_is_invalid_schema_type($type)
{
    if (!defined('CHROMA_INVALID_SCHEMA_TYPES')) {
        return false;
    }
    $type_lower = strtolower(trim($type));
    foreach (CHROMA_INVALID_SCHEMA_TYPES as $invalid) {
        if (strtolower($invalid) === $type_lower) {
            return true;
        }
    }
    return false;
}

class Chroma_Schema_Injector
{
    /**
     * Get Person Schema Data
     */
    public static function get_person_schema_data($post_id)
    {
        $director_name = get_post_meta($post_id, 'location_director_name', true);
        $director_bio = get_post_meta($post_id, 'location_director_bio', true);
        $director_photo = get_post_meta($post_id, 'location_director_photo', true);

        if (!$director_name) {
            return null;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            '@id' => get_permalink($post_id) . '#director-profile',
            'url' => get_permalink($post_id) . '#director-profile',
            'name' => $director_name,
            'jobTitle' => 'Center Director',
            'worksFor' => [
                '@type' => 'ChildCare',
                'name' => get_the_title($post_id),
                '@id' => get_permalink($post_id) . '#organization'
            ],
            'description' => $director_bio ? wp_strip_all_tags($director_bio) : sprintf(__('Director at %s', 'chroma-excellence'), get_the_title($post_id)),
        ];

        if ($director_photo) {
            $schema['image'] = $director_photo;
        }

        return $schema;
    }

    /**
     * Output Person Schema for Directors
     */
    public static function output_person_schema()
    {
        if (!is_singular('location')) {
            return;
        }

        // Check for manual override (AI Fixed Schema)
        if (function_exists('chroma_has_valid_schema_override_pro') && chroma_has_valid_schema_override_pro(get_queried_object_id())) {
            return;
        }

        $schema = self::get_person_schema_data(get_the_ID());
        if ($schema) {
            Chroma_Schema_Registry::register($schema, ['source' => 'schema-injector-person']);
        }
    }

    /**
     * Output ProfilePage schema for About and Location pages.
     * - About page: one ProfilePage per team_member entry.
     * - Location page: one ProfilePage for campus director.
     */
    public static function output_profile_page_schema()
    {
        if (!is_page() && !is_singular('location')) {
            return;
        }

        $post_id = get_queried_object_id();
        if (!$post_id) {
            return;
        }

        // Respect manual overrides.
        if (function_exists('chroma_has_valid_schema_override_pro') && chroma_has_valid_schema_override_pro($post_id)) {
            return;
        }

        if (is_singular('location')) {
            $person = self::get_person_schema_data($post_id);
            if (!$person) {
                return;
            }

            $profile_schema = [
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                '@id' => get_permalink($post_id) . '#director-profile-page',
                'url' => get_permalink($post_id) . '#director-profile',
                'name' => sprintf(__('Director Profile: %s', 'chroma-excellence'), $person['name']),
                'isPartOf' => [
                    '@type' => 'WebPage',
                    '@id' => get_permalink($post_id),
                ],
                'mainEntity' => $person,
            ];

            Chroma_Schema_Registry::register($profile_schema, ['source' => 'schema-injector-location-profile']);
            return;
        }

        // About page profile directory.
        $slug = get_post_field('post_name', $post_id);
        if (!in_array($slug, ['about', 'about-us', 'our-story'], true)) {
            return;
        }

        $team_posts = get_posts([
            'post_type' => 'team_member',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'post_status' => 'publish',
        ]);

        if (empty($team_posts)) {
            return;
        }

        foreach ($team_posts as $team_post) {
            $team_id = $team_post->ID;
            $profile_url = get_permalink($post_id) . '#team-member-' . $team_id;
            $job_title = get_post_meta($team_id, 'team_member_title', true);
            $image_url = get_the_post_thumbnail_url($team_id, 'full');

            $person = [
                '@type' => 'Person',
                '@id' => $profile_url,
                'url' => $profile_url,
                'name' => get_the_title($team_id),
            ];

            if (!empty($job_title)) {
                $person['jobTitle'] = $job_title;
            }

            if (!empty($image_url)) {
                $person['image'] = $image_url;
            }

            $bio = trim(wp_strip_all_tags((string) $team_post->post_content));
            if ($bio !== '') {
                $person['description'] = wp_trim_words($bio, 55);
            }

            $profile_schema = [
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                '@id' => $profile_url . '-page',
                'url' => $profile_url,
                'name' => sprintf(__('%s Profile', 'chroma-excellence'), $person['name']),
                'isPartOf' => [
                    '@type' => 'WebPage',
                    '@id' => get_permalink($post_id),
                ],
                'mainEntity' => $person,
            ];

            Chroma_Schema_Registry::register($profile_schema, [
                'source' => 'schema-injector-about-profile',
                'allow_duplicate' => true,
            ]);
        }
    }

    /**
     * Output JobPosting Schema for Career Pages
     */
    public static function output_job_posting_schema()
    {
        if (class_exists('Chroma_Job_Posting_Builder')) {
            Chroma_Job_Posting_Builder::output();
        }
    }

    /**
     * Output CourseInstance Schema for Pre-K Programs
     */
    /**
     * Output CourseInstance Schema for Pre-K Programs
     */
    public static function output_course_schema()
    {
        if (class_exists('Chroma_Course_Builder')) {
            Chroma_Course_Builder::output();
        }
    }

    /**
     * Get Organization Schema Data
     */
    public static function get_organization_schema_data()
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => home_url() . '#organization',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
            'logo' => get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '',
            'sameAs' => [],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => get_theme_mod('chroma_phone_number'),
                'contactType' => 'customer service'
            ]
        ];

        $phonetic = get_option('chroma_seo_phonetic_name');
        if ($phonetic) {
            $data['alternateName'] = $phonetic;
        }

        return $data;
    }

    /**
     * Output Global Organization Schema
     */
    /**
     * Output Global Organization Schema
     */
    public static function output_organization_schema()
    {
        // Output on Front Page AND About Page
        if (!is_front_page() && !is_page('about')) {
            return;
        }

        $target_id = is_front_page() ? get_option('page_on_front') : get_queried_object_id();

        // Check for manual override (AI Fixed Schema)
        if (function_exists('chroma_has_valid_schema_override_pro') && chroma_has_valid_schema_override_pro($target_id)) {
            return;
        }

        $schema = self::get_organization_schema_data();

        // Inject Team Members on About Page
        if (is_page('about')) {
            $team_posts = get_posts([
                'post_type' => 'team_member',
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_status' => 'publish'
            ]);

            if (!empty($team_posts)) {
                $schema['employee'] = [];
                foreach ($team_posts as $post) {
                    $job_title = get_post_meta($post->ID, 'team_member_title', true);
                    $image_url = get_the_post_thumbnail_url($post->ID, 'medium');

                    $person = [
                        '@type' => 'Person',
                        'name' => $post->post_title,
                        'jobTitle' => $job_title ?: 'Team Member'
                    ];

                    if ($image_url) {
                        $person['image'] = $image_url;
                    }

                    // Optional: Add bio if content exists
                    if (!empty($post->post_content)) {
                        $person['description'] = wp_trim_words(strip_shortcodes($post->post_content), 30);
                    }

                    $schema['employee'][] = $person;
                }
            }
        }

        Chroma_Schema_Registry::register($schema, ['source' => 'schema-injector-organization']);
    }

    /**
     * Output WebSite Schema with SearchAction
     * Enables Sitelinks Search Box in Google SERPs
     */
    public static function output_website_schema()
    {
        if (!is_front_page()) {
            return;
        }

        // Check for manual override (AI Fixed Schema)
        if (function_exists('chroma_has_valid_schema_override_pro') && chroma_has_valid_schema_override_pro(get_option('page_on_front'))) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => home_url('/') . '#website',
            'url' => home_url('/'),
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ],
                'query-input' => 'required name=search_term_string'
            ],
            'publisher' => ['@id' => home_url('/') . '#organization']
        ];

        Chroma_Schema_Registry::register($schema, ['source' => 'schema-injector-website']);
    }

    /**
     * Output LocalBusiness/ChildCare Schema for Location Pages
     * Consolidated from Theme's seo-engine.php with all advanced features
     */
    public static function output_location_schema()
    {
        if (!is_singular('location')) {
            return;
        }

        $location_id = get_the_ID();

        // Check for manual override (AI Fixed Schema)
        if (function_exists('chroma_has_valid_schema_override_pro') && chroma_has_valid_schema_override_pro($location_id)) {
            return;
        }

        if (
            function_exists('chroma_post_has_stored_schema_type_pro')
            && chroma_post_has_stored_schema_type_pro($location_id, ['ChildCare', 'Preschool', 'EducationalOrganization', 'LocalBusiness'])
        ) {
            return;
        }

        // Build multi-type array
        $types = ['ChildCare', 'Preschool', 'EducationalOrganization', 'LocalBusiness'];

        // Feature: Event Venue toggle
        if (get_post_meta($location_id, '_chroma_is_event_venue', true)) {
            $types[] = 'EventVenue';
        }

        // Get location meta
        $name = get_the_title();
        $description = get_the_excerpt() ?: wp_trim_words(get_the_content(), 55);
        $phone = get_post_meta($location_id, 'location_phone', true);
        $email = get_post_meta($location_id, 'location_email', true);
        $address = get_post_meta($location_id, 'location_address', true);
        $city = get_post_meta($location_id, 'location_city', true);
        $state = get_post_meta($location_id, 'location_state', true);
        $zip = get_post_meta($location_id, 'location_zip', true);
        $lat = get_post_meta($location_id, 'location_latitude', true) ?: get_post_meta($location_id, 'geo_lat', true);
        $lng = get_post_meta($location_id, 'location_longitude', true) ?: get_post_meta($location_id, 'geo_lng', true);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $types,
            '@id' => get_permalink() . '#organization',
            'name' => $name,
            'description' => $description,
            'url' => get_permalink(),
            'image' => get_the_post_thumbnail_url($location_id, 'full'),
            'logo' => get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '',
            'telephone' => $phone,
            'email' => $email,
            'priceRange' => '$$',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => $city,
                'addressRegion' => $state,
                'postalCode' => $zip,
                'addressCountry' => 'US'
            ],
            'sameAs' => array_filter([
                get_theme_mod('chroma_facebook_url'),
                get_theme_mod('chroma_instagram_url'),
                get_theme_mod('chroma_linkedin_url'),
                get_post_meta($location_id, 'location_facebook', true)
            ])
        ];

        // Opening Hours (Tier 1)
        $hours_raw = get_post_meta($location_id, 'location_hours', true);
        if ($hours_raw) {
            // Simple check: if it looks like "7:00 am - 6:00 pm", we can try to parse. 
            // For now, output as standard string unless we have the repeater data.
            $schema['openingHours'] = $hours_raw;
        }

        // Geo Coordinates
        if ($lat && $lng) {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => floatval($lat),
                'longitude' => floatval($lng)
            ];
        }

        // Feature: Google Maps CID
        $cid = get_post_meta($location_id, '_chroma_google_maps_cid', true);
        if ($cid) {
            $schema['hasMap'] = "https://www.google.com/maps?cid=$cid";
        } elseif ($address && $city && $state) {
            $addr_string = urlencode("$address, $city, $state $zip");
            $schema['hasMap'] = "https://www.google.com/maps/search/?api=1&query=$addr_string";
        }

        // Feature: License/Credentials
        $license = get_post_meta($location_id, '_chroma_license_number', true);
        if ($license) {
            $schema['hasCredential'] = [
                '@type' => 'EducationalOccupationalCredential',
                'credentialCategory' => 'license',
                'name' => 'Georgia DECAL License',
                'identifier' => [
                    '@type' => 'PropertyValue',
                    'propertyID' => 'License Number',
                    'value' => $license
                ],
                'recognizedBy' => [
                    '@type' => 'GovernmentOrganization',
                    'name' => 'Georgia Department of Early Care and Learning',
                    'url' => 'https://www.decal.ga.gov/'
                ]
            ];
        }

        // Feature: Amenities
        $amenities = get_post_meta($location_id, '_chroma_amenities', true);
        if (is_array($amenities) && !empty($amenities)) {
            $schema['amenityFeature'] = [];
            foreach ($amenities as $amenity) {
                $schema['amenityFeature'][] = [
                    '@type' => 'LocationFeatureSpecification',
                    'name' => $amenity,
                    'value' => true
                ];
            }
        }

        // Quality Rated
        $quality_rated = get_post_meta($location_id, 'location_quality_rated', true);
        if ($quality_rated) {
            if (!isset($schema['amenityFeature'])) {
                $schema['amenityFeature'] = [];
            }
            $schema['amenityFeature'][] = [
                '@type' => 'LocationFeatureSpecification',
                'name' => 'Quality Rated',
                'value' => true
            ];
        }

        // Aggregate Rating
        $rating = get_post_meta($location_id, 'location_google_rating', true);
        $review_count = get_post_meta($location_id, 'seo_llm_rating_count', true);
        if ($rating) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $rating,
                'reviewCount' => $review_count ?: '1',
                'bestRating' => '5',
                'worstRating' => '1'
            ];
        }

        // Feature: Director (Person)
        $director_name = get_post_meta($location_id, 'location_director_name', true);
        if ($director_name) {
            $schema['employee'] = [
                '@type' => 'Person',
                'name' => $director_name,
                'jobTitle' => 'Center Director',
                'image' => get_post_meta($location_id, 'location_director_photo', true)
            ];
        }

        // Feature: Related Programs (makesOffer)
        $programs = get_posts([
            'post_type' => 'program',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'program_locations',
                    'value' => '"' . $location_id . '"',
                    'compare' => 'LIKE'
                ]
            ]
        ]);
        if (!empty($programs)) {
            $schema['makesOffer'] = [];
            foreach ($programs as $prog) {
                $schema['makesOffer'][] = [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => $prog->post_title,
                        'url' => get_permalink($prog->ID)
                    ]
                ];
            }
        }

        Chroma_Schema_Registry::register($schema, ['source' => 'schema-injector-location']);

        // Feature: Open House Event Schema (separate output)
        $open_house_date = get_post_meta($location_id, '_chroma_open_house_date', true);
        if ($open_house_date) {
            $event_schema = [
                '@context' => 'https://schema.org',
                '@type' => 'Event',
                'name' => 'Open House - ' . $name,
                'startDate' => date('c', strtotime($open_house_date)),
                'endDate' => date('c', strtotime($open_house_date) + 7200),
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'eventStatus' => 'https://schema.org/EventScheduled',
                'location' => [
                    '@type' => 'Place',
                    'name' => $name,
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => $address,
                        'addressLocality' => $city,
                        'addressRegion' => $state,
                        'postalCode' => $zip,
                        'addressCountry' => 'US'
                    ]
                ],
                'description' => "Join us for an Open House at $name. Meet the teachers, tour the classrooms, and learn about our curriculum.",
                'organizer' => [
                    '@type' => 'Organization',
                    'name' => $name,
                    'url' => get_permalink()
                ]
            ];
            Chroma_Schema_Registry::register($event_schema, ['source' => 'schema-injector-open-house']);
        }
    }

    /**
     * Output Author/Person Schema for Blog Posts (E-E-A-T)
     */
    public static function output_author_schema()
    {
        if (!is_singular('post')) {
            return;
        }

        $post_id = get_the_ID();
        $author_id = get_post_field('post_author', $post_id);

        if (!$author_id) {
            return;
        }

        $author_name = get_the_author_meta('display_name', $author_id);
        $author_url = get_author_posts_url($author_id);
        $author_avatar = get_avatar_url($author_id, ['size' => 160]);
        $author_bio = get_the_author_meta('description', $author_id);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $author_name,
            'url' => $author_url
        ];

        if ($author_avatar) {
            $schema['image'] = $author_avatar;
        }

        if ($author_bio) {
            $schema['description'] = $author_bio;
        }

        Chroma_Schema_Registry::register($schema, ['source' => 'schema-injector-author']);
    }
    /**
     * Get default schema data for a given post type
     * Used by Schema Builder to pre-fill with intelligent defaults
     */
    public static function get_default_schema_for_post_type($post_id)
    {
        $post_type = get_post_type($post_id);
        $defaults = [];

        switch ($post_type) {
            case 'location':
                // ChildCare schema for locations
                $location_name = get_the_title($post_id);
                $address = get_post_meta($post_id, 'location_address', true);
                $phone = get_post_meta($post_id, 'location_phone', true);
                $excerpt = get_post_field('post_excerpt', $post_id);
                if (empty($excerpt)) {
                    $content = get_post_field('post_content', $post_id);
                    $excerpt = wp_trim_words(strip_shortcodes($content), 55);
                }
                $description = $excerpt ?: get_post_meta($post_id, 'location_short_description', true);

                $defaults[] = [
                    'type' => 'ChildCare',
                    'data' => [
                        'name' => $location_name,
                        'description' => $description ?: sprintf(__('Quality childcare and early education at %s', 'chroma-excellence'), $location_name),
                        'address' => $address ?: '',
                        'telephone' => $phone ?: get_theme_mod('chroma_phone_number', ''),
                        'url' => get_permalink($post_id),
                        'priceRange' => '$$',
                    ]
                ];

                // Add AggregateRating if reviews exist
                $reviews = get_post_meta($post_id, 'location_reviews', true);
                if (!empty($reviews) && is_array($reviews)) {
                    $ratings = [];
                    foreach ($reviews as $review) {
                        if (!empty($review['rating'])) {
                            $ratings[] = floatval($review['rating']);
                        }
                    }
                    if (!empty($ratings)) {
                        $defaults[0]['data']['aggregateRating'] = [
                            '@type' => 'AggregateRating',
                            'ratingValue' => round(array_sum($ratings) / count($ratings), 1),
                            'reviewCount' => count($ratings),
                            'bestRating' => '5',
                            'worstRating' => '1'
                        ];
                    }
                }
                break;

            case 'program':
                // Service schema for programs
                $program_name = get_the_title($post_id);
                if (function_exists('chroma_schema_clean_program_description_pro')) {
                    $program_desc = chroma_schema_clean_program_description_pro($post_id, get_post_meta($post_id, 'schema_prog_description', true));
                } else {
                    $program_desc = get_post_field('post_excerpt', $post_id);
                    if (empty($program_desc)) {
                        $content = get_post_field('post_content', $post_id);
                        $program_desc = wp_trim_words(wp_strip_all_tags(strip_shortcodes($content)), 35);
                    }
                }
                $age_range = get_post_meta($post_id, 'program_age_range', true);

                $defaults[] = [
                    'type' => 'Service',
                    'data' => [
                        'name' => $program_name,
                        'description' => $program_desc ?: sprintf(__('%s program at Chroma Early Learning', 'chroma-excellence'), $program_name),
                        'provider' => [
                            '@type' => 'Organization',
                            'name' => get_bloginfo('name'),
                            'url' => home_url()
                        ],
                        'serviceType' => 'Educational Program',
                        'areaServed' => 'Metro Atlanta, Georgia',
                    ]
                ];
                break;

            case 'page':
                // About page gets Organization schema
                if (is_page('about')) {
                    $defaults[] = [
                        'type' => 'Organization',
                        'data' => self::get_organization_schema_data()
                    ];
                }
                break;
        }

        return $defaults;
    }

    /**
     * Output Modular Schemas from Schema Builder
     */
    public static function output_modular_schemas()
    {
        if (!is_singular()) {
            return;
        }

        // Check for manual override (AI Fixed Schema)
        if (function_exists('chroma_has_valid_schema_override_pro') && chroma_has_valid_schema_override_pro(get_queried_object_id())) {
            return;
        }

        $post_id = get_the_ID();
        $schemas = get_post_meta($post_id, '_chroma_post_schemas', true);
        $schemas = is_array($schemas) ? $schemas : [];

        if (!empty($schemas)) {
            $schemas = self::expand_schema_graph_documents($schemas);
            $schemas = self::filter_preparable_schema_rows($schemas);
        }

        if (empty($schemas)) {
            $legacy_schemas = self::normalize_legacy_schema_data(get_post_meta($post_id, '_chroma_schema_data', true));
            $schemas = self::filter_preparable_schema_rows(self::expand_schema_graph_documents($legacy_schemas));
            if (empty($schemas)) {
                return;
            }
        }

        $graph = [];

        foreach ($schemas as $schema_index => $schema_data) {
            if (function_exists('chroma_schema_strip_internal_keys')) {
                $schema_data = chroma_schema_strip_internal_keys($schema_data);
            }

            $prepared = self::prepare_modular_schema_input($schema_data);
            if (empty($prepared['type'])) {
                continue;
            }

            $schema_type = $prepared['type'];

            // Global Suppression Check (e.g. for external AI schema)
            if (
                function_exists('chroma_has_valid_schema_override_pro')
                && chroma_has_valid_schema_override_pro($post_id)
                && ($schema_type === 'FAQPage' || $schema_type === 'BreadcrumbList')
            ) {
                continue;
            }

            // Internal Duplicate Suppression
            global $chroma_faq_output_done;
            if ($schema_type === 'FAQPage' && !empty($chroma_faq_output_done)) {
                continue;
            }

            // Skip invalid/irrelevant schema types using case-insensitive helper
            if (function_exists('chroma_is_invalid_schema_type') && chroma_is_invalid_schema_type($schema_type)) {
                continue;
            }

            // Check Global Disable Flags
            // 1. FAQ Schema
            if ($schema_type === 'FAQPage' && (get_option('chroma_faq_schema_disabled', 'no') === 'yes' || is_front_page())) {
                continue;
            }
            // 2. Breadcrumbs (if present in modular list)
            if ($schema_type === 'BreadcrumbList' && get_option('chroma_breadcrumbs_schema_disabled', 'no') === 'yes') {
                continue;
            }

            $fields = $prepared['fields'];
            if (function_exists('chroma_schema_strip_internal_keys')) {
                $fields = chroma_schema_strip_internal_keys($fields);
            }
            $schema_id = !empty($prepared['id']) ? $prepared['id'] : self::build_deterministic_schema_id($post_id, $schema_type, (int) $schema_index);

            $schema_output = [
                '@type' => $schema_type,
                '@id' => $schema_id
            ];

            // Add fields
            foreach ($fields as $key => $value) {
                if (self::is_empty_schema_value($value))
                    continue;

                // Basic sanitization, but allow some HTML if needed? 
                // For now, assume text/url/date.
                // If value is array (repeater), handle it?
                // The current builder saves simple key-value pairs. 
                // If we have complex fields later, we need to handle them here.

                // Check if key is a known schema property
                // We trust the builder to provide valid keys

                // Handle Repeater Fields
                if (is_array($value)) {
                    if ($key === 'custom_fields') {
                        foreach ($value as $field) {
                            if (!empty($field['key']) && !empty($field['value'])) {
                                $schema_output[sanitize_key($field['key'])] = sanitize_textarea_field($field['value']);
                            }
                        }
                    } elseif ($schema_type === 'FAQPage' && $key === 'questions') {
                        $entities = self::normalize_faq_questions($value);
                        if (!empty($entities)) {
                            if (empty($schema_output['mainEntity']) || !is_array($schema_output['mainEntity'])) {
                                $schema_output['mainEntity'] = [];
                            }
                            $schema_output['mainEntity'] = array_merge($schema_output['mainEntity'], $entities);
                        }
                    } elseif ($schema_type === 'FAQPage' && $key === 'mainEntity') {
                        $entities = self::normalize_faq_main_entity($value);
                        if (!empty($entities)) {
                            $schema_output['mainEntity'] = $entities;
                        }
                    } elseif ($schema_type === 'HowTo' && $key === 'steps') {
                        $schema_output['step'] = [];
                        foreach ($value as $s) {
                            $step = [
                                '@type' => 'HowToStep',
                                'name' => isset($s['name']) ? $s['name'] : '',
                                'text' => isset($s['text']) ? $s['text'] : '',
                            ];
                            if (!empty($s['image'])) {
                                $step['image'] = $s['image'];
                            }
                            $schema_output['step'][] = $step;
                        }
                    } elseif ($key === 'offers') {
                        $schema_output['offers'] = [];
                        foreach ($value as $offer) {
                            $offer_schema = [
                                '@type' => 'Offer',
                                'price' => isset($offer['price']) ? $offer['price'] : '',
                                'priceCurrency' => isset($offer['priceCurrency']) ? $offer['priceCurrency'] : 'USD'
                            ];
                            if (isset($offer['name']))
                                $offer_schema['name'] = $offer['name'];
                            if (isset($offer['url']))
                                $offer_schema['url'] = $offer['url'];
                            if (isset($offer['availability']))
                                $offer_schema['availability'] = $offer['availability'];
                            else
                                $offer_schema['availability'] = 'https://schema.org/InStock';

                            $schema_output['offers'][] = $offer_schema;
                        }
                    } elseif ($key === 'review') {
                        $schema_output['review'] = [];
                        foreach ($value as $r) {
                            $schema_output['review'][] = [
                                '@type' => 'Review',
                                'author' => [
                                    '@type' => 'Person',
                                    'name' => isset($r['author']) ? $r['author'] : ''
                                ],
                                'reviewRating' => [
                                    '@type' => 'Rating',
                                    'ratingValue' => isset($r['reviewRating']) ? $r['reviewRating'] : ''
                                ],
                                'reviewBody' => isset($r['reviewBody']) ? $r['reviewBody'] : ''
                            ];
                        }
                    } elseif ($key === 'hasCredential') {
                        $schema_output['hasCredential'] = [];
                        foreach ($value as $cred) {
                            $cred_schema = [
                                '@type' => 'EducationalOccupationalCredential',
                                'name' => isset($cred['name']) ? $cred['name'] : ''
                            ];
                            if (!empty($cred['credentialCategory'])) {
                                $cred_schema['credentialCategory'] = $cred['credentialCategory'];
                            }
                            if (!empty($cred['recognizedBy'])) {
                                $cred_schema['recognizedBy'] = [
                                    '@type' => 'Organization',
                                    'name' => $cred['recognizedBy']
                                ];
                            }
                            $schema_output['hasCredential'][] = $cred_schema;
                        }
                    } elseif ($key === 'amenityFeature') {
                        $schema_output['amenityFeature'] = [];
                        foreach ($value as $amenity) {
                            $schema_output['amenityFeature'][] = [
                                '@type' => 'LocationFeatureSpecification',
                                'name' => isset($amenity['name']) ? $amenity['name'] : '',
                                'value' => isset($amenity['value']) ? $amenity['value'] : 'true'
                            ];
                        }
                    } elseif ($key === 'knowsLanguage') {
                        // Handle comma-separated languages
                        if (is_string($value) && strpos($value, ',') !== false) {
                            $schema_output['knowsLanguage'] = array_map('trim', explode(',', $value));
                        } else {
                            $schema_output['knowsLanguage'] = $value;
                        }
                    } elseif ($key === 'hasMenuSection') {
                        $schema_output['hasMenuSection'] = [];
                        foreach ($value as $section) {
                            $schema_output['hasMenuSection'][] = [
                                '@type' => 'MenuSection',
                                'name' => isset($section['name']) ? $section['name'] : '',
                                'description' => isset($section['description']) ? $section['description'] : ''
                            ];
                        }
                    } elseif ($key === 'itemListElement') {
                        $schema_output['itemListElement'] = [];
                        foreach ($value as $item) {
                            $schema_output['itemListElement'][] = [
                                '@type' => 'ListItem',
                                'position' => isset($item['position']) ? intval($item['position']) : '',
                                'name' => isset($item['name']) ? $item['name'] : '',
                                'url' => isset($item['url']) ? $item['url'] : ''
                            ];
                        }
                    } elseif ($key === 'image' && is_array($value) && isset($value[0])) {
                        // Image gallery handling
                        $schema_output['image'] = [];
                        foreach ($value as $img) {
                            $img_schema = [
                                '@type' => 'ImageObject',
                                'contentUrl' => isset($img['contentUrl']) ? $img['contentUrl'] : ''
                            ];
                            if (!empty($img['caption'])) {
                                $img_schema['caption'] = $img['caption'];
                            }
                            if (!empty($img['description'])) {
                                $img_schema['description'] = $img['description'];
                            }
                            $schema_output['image'][] = $img_schema;
                        }
                    } elseif ($key === 'openingHours' && is_array($value)) {
                        // Convert openingHours repeater to OpeningHoursSpecification
                        $schema_output['openingHoursSpecification'] = [];
                        foreach ($value as $hours) {
                            if (!empty($hours['dayOfWeek']) && !empty($hours['opens']) && !empty($hours['closes'])) {
                                $schema_output['openingHoursSpecification'][] = [
                                    '@type' => 'OpeningHoursSpecification',
                                    'dayOfWeek' => $hours['dayOfWeek'],
                                    'opens' => $hours['opens'],
                                    'closes' => $hours['closes']
                                ];
                            }
                        }
                    } else {
                        // Generic array output (if needed for other types)
                        $schema_output[$key] = $value;
                    }
                } else {
                    // Handle Special Nested Fields for JobPosting
                    if ($schema_type === 'JobPosting') {
                        if ($key === 'hiringOrganization_name') {
                            $schema_output['hiringOrganization'] = [
                                '@type' => 'Organization',
                                'name' => $value
                            ];
                            continue;
                        }
                        if ($key === 'jobLocation_address') {
                            $schema_output['jobLocation'] = [
                                '@type' => 'Place',
                                'address' => [
                                    '@type' => 'PostalAddress',
                                    'streetAddress' => $value
                                ]
                            ];
                            continue;
                        }
                        if ($key === 'baseSalary_value') {
                            // We need currency to form PriceSpecification
                            $currency = isset($fields['baseSalary_currency']) ? $fields['baseSalary_currency'] : 'USD';
                            $schema_output['baseSalary'] = [
                                '@type' => 'MonetaryAmount',
                                'currency' => $currency,
                                'value' => [
                                    '@type' => 'QuantitativeValue',
                                    'value' => $value,
                                    'unitText' => 'YEAR' // Defaulting to YEAR for simplicity
                                ]
                            ];
                            continue;
                        }
                        if ($key === 'baseSalary_currency')
                            continue; // Handled above
                    }

                    // Handle Special Nested Fields for Event
                    if ($schema_type === 'Event') {
                        if ($key === 'location_name') {
                            // We need address too
                            $address = isset($fields['location_address']) ? $fields['location_address'] : '';
                            $schema_output['location'] = [
                                '@type' => 'Place',
                                'name' => $value,
                                'address' => [
                                    '@type' => 'PostalAddress',
                                    'streetAddress' => $address
                                ]
                            ];
                            continue;
                        }
                        if ($key === 'location_address')
                            continue; // Handled above



                        if ($key === 'organizer') {
                            $schema_output['organizer'] = [
                                '@type' => 'Organization',
                                'name' => $value
                            ];
                            continue;
                        }
                    }

                    // Handle geo_lat and geo_lng to create GeoCoordinates (important for GMB)
                    if ($key === 'geo_lat') {
                        $lng = isset($fields['geo_lng']) ? $fields['geo_lng'] : '';
                        if (!empty($value) && !empty($lng)) {
                            $schema_output['geo'] = [
                                '@type' => 'GeoCoordinates',
                                'latitude' => floatval($value),
                                'longitude' => floatval($lng)
                            ];
                        }
                        continue;
                    }
                    if ($key === 'geo_lng') {
                        continue; // Handled above with geo_lat
                    }

                    $schema_output[$key] = $value;
                }
            }

            if ($schema_type === 'FAQPage') {
                if (empty($schema_output['mainEntity']) || !is_array($schema_output['mainEntity'])) {
                    $schema_output['mainEntity'] = self::get_faq_entities_from_meta($post_id);
                } else {
                    $schema_output['mainEntity'] = self::normalize_faq_main_entity($schema_output['mainEntity']);
                }

                if (empty($schema_output['mainEntity'])) {
                    continue;
                }
            }

            $schema_output = self::normalize_modular_schema_output($schema_type, $schema_output, $fields, $post_id);
            if (function_exists('chroma_schema_strip_internal_keys')) {
                $schema_output = chroma_schema_strip_internal_keys($schema_output);
            }
            if (!is_array($schema_output) || empty($schema_output)) {
                continue;
            }

            if (class_exists('Chroma_Schema_Validator')) {
                Chroma_Schema_Validator::validate($schema_output, 'Modular schema');
                $report = Chroma_Schema_Validator::get_report();
                if (empty($report['valid'])) {
                    if (defined('WP_DEBUG') && WP_DEBUG && function_exists('chroma_debug_log')) {
                        chroma_debug_log('Modular schema blocked (' . $schema_type . '): ' . wp_json_encode($report['errors']));
                    }
                    continue;
                }
            }

            if ($schema_type === 'FAQPage') {
                global $chroma_faq_output_done;
                $chroma_faq_output_done = true;
            }

            $graph[] = $schema_output;
        }

        if (!empty($graph)) {
            // Use Schema Registry for deduplication if available
            if (class_exists('Chroma_Schema_Registry')) {
                foreach ($graph as $schema) {
                    // Add @context since registry expects standalone schemas
                    $schema['@context'] = 'https://schema.org';
                    Chroma_Schema_Registry::register($schema, ['source' => 'modular_builder']);
                }
                // Registry will output at priority 99 - don't echo here
            } else {
                $final_schema = [
                    '@context' => 'https://schema.org',
                    '@graph' => $graph
                ];
                echo '<script type="application/ld+json">' . wp_json_encode($final_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
            }
        }
    }

    /**
     * Keep only rows that can be converted into a schema type.
     *
     * @param mixed $schemas
     * @return array<int,array>
     */
    private static function filter_preparable_schema_rows($schemas)
    {
        if (empty($schemas) || !is_array($schemas)) {
            return [];
        }

        $valid = [];
        foreach ($schemas as $schema) {
            $prepared = self::prepare_modular_schema_input($schema);
            if (!empty($prepared['type'])) {
                $valid[] = $schema;
            }
        }

        return $valid;
    }

    /**
     * Normalize legacy schema storage into the modular schema row list.
     *
     * @param mixed $schema_data
     * @return array<int,array>
     */
    private static function normalize_legacy_schema_data($schema_data)
    {
        if (is_object($schema_data)) {
            $schema_data = (array) $schema_data;
        }

        if (!is_array($schema_data) || empty($schema_data)) {
            return [];
        }

        if (function_exists('chroma_schema_strip_internal_keys')) {
            $schema_data = chroma_schema_strip_internal_keys($schema_data);
        }

        if (!empty($schema_data['@graph']) && is_array($schema_data['@graph'])) {
            return array_values(array_filter($schema_data['@graph'], 'is_array'));
        }

        if (function_exists('chroma_schema_array_is_list') && chroma_schema_array_is_list($schema_data)) {
            return array_values(array_filter($schema_data, 'is_array'));
        }

        if (!empty($schema_data['@type']) || !empty($schema_data['type'])) {
            return [$schema_data];
        }

        return [];
    }

    /**
     * Expand full JSON-LD documents stored inside _chroma_post_schemas.
     *
     * Some Agent API clients send a complete JSON-LD object like
     * ['@context' => 'https://schema.org', '@graph' => [...]] instead of the
     * Builder's row list. Render those existing records without requiring a
     * re-save by flattening graph nodes into normal schema rows.
     *
     * @param array $schemas
     * @return array<int,array>
     */
    private static function expand_schema_graph_documents(array $schemas)
    {
        $expanded = [];

        foreach ($schemas as $schema) {
            if (is_object($schema)) {
                $schema = (array) $schema;
            }

            if (!is_array($schema)) {
                continue;
            }

            if (!empty($schema['@graph']) && is_array($schema['@graph'])) {
                foreach ($schema['@graph'] as $node) {
                    if (is_object($node)) {
                        $node = (array) $node;
                    }
                    if (is_array($node) && !empty($node)) {
                        $expanded[] = $node;
                    }
                }
                continue;
            }

            $expanded[] = $schema;
        }

        return $expanded;
    }

    /**
     * Accept schema rows in both builder format and raw JSON-LD-like format.
     *
     * @param mixed $schema_data
     * @return array{type:string,fields:array,id:string}
     */
    private static function prepare_modular_schema_input($schema_data)
    {
        if (!is_array($schema_data)) {
            return ['type' => '', 'fields' => [], 'id' => ''];
        }

        if (function_exists('chroma_schema_strip_internal_keys')) {
            $schema_data = chroma_schema_strip_internal_keys($schema_data);
        }

        // Native builder shape: ['type' => 'FAQPage', 'data' => [...]]
        if (!empty($schema_data['type'])) {
            $type = sanitize_text_field((string) $schema_data['type']);
            $fields = isset($schema_data['data']) && is_array($schema_data['data']) ? $schema_data['data'] : [];
            $id = isset($schema_data['@id']) ? sanitize_text_field((string) $schema_data['@id']) : '';
            return ['type' => $type, 'fields' => $fields, 'id' => $id];
        }

        // Raw JSON-LD-like shape: ['@type' => 'FAQPage', 'mainEntity' => [...]]
        if (empty($schema_data['@type'])) {
            return ['type' => '', 'fields' => [], 'id' => ''];
        }

        $type = $schema_data['@type'];
        if (is_array($type)) {
            $type = reset($type);
        }
        $type = sanitize_text_field((string) $type);

        $fields = $schema_data;
        unset($fields['@type'], $fields['@context'], $fields['@id']);

        $id = isset($schema_data['@id']) ? sanitize_text_field((string) $schema_data['@id']) : '';

        return ['type' => $type, 'fields' => $fields, 'id' => $id];
    }

    /**
     * Build stable schema IDs to avoid per-request random IDs.
     *
     * @param int    $post_id
     * @param string $schema_type
     * @param int    $schema_index
     * @return string
     */
    private static function build_deterministic_schema_id($post_id, $schema_type, $schema_index)
    {
        $base = untrailingslashit(get_permalink($post_id));
        $type_slug = sanitize_title($schema_type);
        return $base . '#schema-' . $type_slug . '-' . intval($schema_index + 1);
    }

    /**
     * Determine if a schema value should be treated as empty.
     *
     * @param mixed $value
     * @return bool
     */
    private static function is_empty_schema_value($value)
    {
        if ($value === null) {
            return true;
        }
        if (is_string($value) && trim($value) === '') {
            return true;
        }
        if (is_array($value) && empty($value)) {
            return true;
        }
        return false;
    }

    /**
     * Normalize FAQ question/answer rows to Question objects.
     *
     * @param array $rows
     * @return array
     */
    private static function normalize_faq_questions($rows)
    {
        if (!is_array($rows)) {
            return [];
        }

        $entities = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $question = isset($row['question']) ? trim(wp_strip_all_tags((string) $row['question'])) : '';
            $answer = isset($row['answer']) ? trim(wp_kses_post((string) $row['answer'])) : '';
            if ($question === '' || $answer === '') {
                continue;
            }
            $entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        return $entities;
    }

    /**
     * Normalize mainEntity for FAQPage.
     *
     * @param mixed $main_entity
     * @return array
     */
    private static function normalize_faq_main_entity($main_entity)
    {
        if (!is_array($main_entity)) {
            return [];
        }

        $entities = [];
        foreach ($main_entity as $entity) {
            if (!is_array($entity)) {
                continue;
            }
            $question = isset($entity['name']) ? trim(wp_strip_all_tags((string) $entity['name'])) : '';
            $answer = '';
            if (isset($entity['acceptedAnswer']) && is_array($entity['acceptedAnswer'])) {
                $answer = isset($entity['acceptedAnswer']['text']) ? trim(wp_kses_post((string) $entity['acceptedAnswer']['text'])) : '';
            }
            if ($question === '' || $answer === '') {
                continue;
            }
            $entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        return $entities;
    }

    /**
     * Fallback FAQ source from universal FAQ meta.
     *
     * @param int $post_id
     * @return array
     */
    private static function get_faq_entities_from_meta($post_id)
    {
        $faqs = [];
        if (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish()) {
            $faqs = get_post_meta($post_id, '_chroma_es_chroma_faq_items', true);
        }
        if (empty($faqs) || !is_array($faqs)) {
            $faqs = get_post_meta($post_id, 'chroma_faq_items', true);
        }

        return self::normalize_faq_questions(is_array($faqs) ? $faqs : []);
    }

    /**
     * Normalize modular builder output into valid Schema.org structures.
     * Keeps backward compatibility with legacy flat builder fields.
     *
     * @param string $schema_type
     * @param array  $schema
     * @param array  $raw_fields
     * @param int    $post_id
     * @return array|null
     */
    private static function normalize_modular_schema_output($schema_type, $schema, $raw_fields, $post_id)
    {
        if (!is_array($schema) || empty($schema_type)) {
            return null;
        }

        // Convert CSV sameAs to array.
        if (isset($schema['sameAs']) && is_string($schema['sameAs'])) {
            $same_as = array_filter(array_map('trim', explode(',', $schema['sameAs'])));
            $schema['sameAs'] = array_values($same_as);
        }

        // Ensure ItemList items are ListItem objects.
        if ($schema_type === 'ItemList' && !empty($schema['itemListElement']) && is_array($schema['itemListElement'])) {
            $items = [];
            $pos = 1;
            foreach ($schema['itemListElement'] as $item) {
                if (is_array($item)) {
                    if (empty($item['@type'])) {
                        $item['@type'] = 'ListItem';
                    }
                    if (empty($item['position'])) {
                        $item['position'] = $pos;
                    }
                    $items[] = $item;
                }
                $pos++;
            }
            $schema['itemListElement'] = $items;
        }

        // Ensure Offer objects include @type.
        if (!empty($schema['offers']) && is_array($schema['offers'])) {
            $offers = [];
            foreach ($schema['offers'] as $offer) {
                if (!is_array($offer)) {
                    continue;
                }
                if (empty($offer['@type'])) {
                    $offer['@type'] = 'Offer';
                }
                $offers[] = $offer;
            }
            $schema['offers'] = $offers;
        }

        if (in_array($schema_type, ['LocalBusiness', 'ChildCare', 'Preschool', 'EducationalOrganization'], true)) {
            $schema = self::normalize_local_business_like_schema($schema, $raw_fields, $post_id);
        }

        if ($schema_type === 'Review') {
            $schema = self::normalize_review_schema($schema, $post_id);
        }

        if (in_array($schema_type, ['Article', 'BlogPosting', 'NewsArticle'], true)) {
            $schema = self::normalize_article_schema($schema, $post_id);
        }

        if ($schema_type === 'Service') {
            $schema = self::normalize_service_schema($schema);
        }

        if ($schema_type === 'Event') {
            $schema = self::normalize_event_schema($schema, $raw_fields, $post_id);
            if ($schema === null) {
                return null;
            }
        }

        return $schema;
    }

    /**
     * Normalize LocalBusiness/ChildCare address and geo structures.
     *
     * @param array $schema
     * @param array $raw_fields
     * @param int   $post_id
     * @return array
     */
    private static function normalize_local_business_like_schema($schema, $raw_fields, $post_id)
    {
        $street = $schema['streetAddress'] ?? ($raw_fields['streetAddress'] ?? '');
        $city = $schema['addressLocality'] ?? ($raw_fields['addressLocality'] ?? '');
        $region = $schema['addressRegion'] ?? ($raw_fields['addressRegion'] ?? '');
        $postal = $schema['postalCode'] ?? ($raw_fields['postalCode'] ?? '');

        // Backfill from location post meta when available.
        if (empty($city)) {
            $city = get_post_meta($post_id, 'location_city', true);
        }
        if (empty($region)) {
            $region = get_post_meta($post_id, 'location_state', true);
        }
        if (empty($postal)) {
            $postal = get_post_meta($post_id, 'location_zip', true);
        }
        if (empty($street)) {
            $street = get_post_meta($post_id, 'location_address', true);
        }

        if (isset($schema['address']) && is_string($schema['address']) && empty($street)) {
            $street = $schema['address'];
        }

        $address = $schema['address'] ?? [];
        if (is_string($address)) {
            $address = ['streetAddress' => $address];
        }
        if (!is_array($address)) {
            $address = [];
        }

        if (empty($address['@type'])) {
            $address['@type'] = 'PostalAddress';
        }
        if (empty($address['streetAddress']) && !empty($street)) {
            $address['streetAddress'] = $street;
        }
        if (empty($address['addressLocality']) && !empty($city)) {
            $address['addressLocality'] = $city;
        }
        if (empty($address['addressRegion']) && !empty($region)) {
            $address['addressRegion'] = $region;
        }
        if (empty($address['postalCode']) && !empty($postal)) {
            $address['postalCode'] = $postal;
        }
        if (empty($address['addressCountry'])) {
            $address['addressCountry'] = 'US';
        }

        $schema['address'] = $address;

        // Remove legacy flat address keys after nesting.
        unset($schema['streetAddress'], $schema['addressLocality'], $schema['addressRegion'], $schema['postalCode']);

        // Convert legacy geo fields (builder + common location meta fallbacks).
        $lat = $raw_fields['geo_lat'] ?? '';
        $lng = $raw_fields['geo_lng'] ?? '';
        if ($lat === '') {
            $lat = get_post_meta($post_id, 'location_latitude', true);
        }
        if ($lng === '') {
            $lng = get_post_meta($post_id, 'location_longitude', true);
        }
        if ($lat === '') {
            $lat = get_post_meta($post_id, 'location_lat', true);
        }
        if ($lng === '') {
            $lng = get_post_meta($post_id, 'location_lng', true);
        }

        if (empty($schema['geo']) && $lat !== '' && $lng !== '') {
            $schema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => floatval($lat),
                'longitude' => floatval($lng),
            ];
        }
        unset($schema['geo_lat'], $schema['geo_lng']);

        return $schema;
    }

    /**
     * Normalize Review schema from legacy flat fields.
     *
     * @param array $schema
     * @param int   $post_id
     * @return array
     */
    private static function normalize_review_schema($schema, $post_id)
    {
        if (!empty($schema['author']) && is_string($schema['author'])) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $schema['author'],
            ];
        }

        if (empty($schema['author']) && !empty($schema['author_name'])) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $schema['author_name'],
            ];
        }

        if (!empty($schema['author']) && is_array($schema['author']) && empty($schema['author']['@type']) && !empty($schema['author']['name'])) {
            $schema['author']['@type'] = 'Person';
        }

        if (!empty($schema['itemReviewed']) && is_string($schema['itemReviewed'])) {
            $item_type = 'Thing';
            $post_type = get_post_type($post_id);
            if ($post_type === 'location') {
                $item_type = 'LocalBusiness';
            } elseif ($post_type === 'program') {
                $item_type = 'Service';
            }

            $schema['itemReviewed'] = [
                '@type' => $item_type,
                'name' => $schema['itemReviewed'],
            ];
        }

        if (empty($schema['itemReviewed']) && !empty($schema['itemReviewed_name'])) {
            $item_type = 'Thing';
            $post_type = get_post_type($post_id);
            if ($post_type === 'location') {
                $item_type = 'LocalBusiness';
            } elseif ($post_type === 'program') {
                $item_type = 'Service';
            }

            $schema['itemReviewed'] = [
                '@type' => $item_type,
                'name' => $schema['itemReviewed_name'],
            ];
        }

        if (empty($schema['itemReviewed']) && in_array(get_post_type($post_id), ['location', 'program'], true)) {
            $schema['itemReviewed'] = [
                '@type' => get_post_type($post_id) === 'location' ? 'LocalBusiness' : 'Service',
                'name' => get_the_title($post_id),
            ];
        }

        // Convert scalar reviewRating to Rating object.
        if (isset($schema['reviewRating']) && !is_array($schema['reviewRating'])) {
            $schema['reviewRating'] = [
                '@type' => 'Rating',
                'ratingValue' => $schema['reviewRating'],
            ];
            if (!empty($schema['bestRating'])) {
                $schema['reviewRating']['bestRating'] = $schema['bestRating'];
            }
        }

        unset($schema['author_name'], $schema['itemReviewed_name']);
        return $schema;
    }

    /**
     * Normalize Article-like schema from API/builder rows.
     *
     * @param array $schema
     * @param int   $post_id
     * @return array
     */
    private static function normalize_article_schema($schema, $post_id)
    {
        if (empty($schema['headline'])) {
            $schema['headline'] = get_the_title($post_id);
        }

        if (empty($schema['description'])) {
            $description = get_the_excerpt($post_id);
            if (empty($description)) {
                $description = wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $post_id)), 35);
            }
            if (!empty($description)) {
                $schema['description'] = $description;
            }
        }

        if (empty($schema['datePublished'])) {
            $schema['datePublished'] = get_the_date('c', $post_id);
        }

        if (empty($schema['dateModified'])) {
            $schema['dateModified'] = get_the_modified_date('c', $post_id);
        }

        if (!empty($schema['author']) && is_string($schema['author'])) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $schema['author'],
            ];
        }

        if (empty($schema['author']) && !empty($schema['author_name'])) {
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $schema['author_name'],
            ];
        }

        if (empty($schema['author'])) {
            $author_id = (int) get_post_field('post_author', $post_id);
            $author_name = $author_id > 0 ? get_the_author_meta('display_name', $author_id) : '';
            if (empty($author_name)) {
                $author_name = get_bloginfo('name');
            }
            $schema['author'] = [
                '@type' => 'Person',
                'name' => $author_name,
            ];
            if ($author_id > 0) {
                $schema['author']['url'] = get_author_posts_url($author_id);
            }
        }

        if (!empty($schema['author']) && is_array($schema['author']) && empty($schema['author']['@type']) && !empty($schema['author']['name'])) {
            $schema['author']['@type'] = 'Person';
        }

        if (!empty($schema['publisher']) && is_string($schema['publisher'])) {
            $schema['publisher'] = [
                '@type' => 'Organization',
                'name' => $schema['publisher'],
            ];
        }

        if (empty($schema['publisher']) || !is_array($schema['publisher'])) {
            $schema['publisher'] = [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
            ];
        } elseif (empty($schema['publisher']['@type'])) {
            $schema['publisher']['@type'] = 'Organization';
        }

        if (empty($schema['publisher']['logo'])) {
            $logo = get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '';
            if (!empty($logo)) {
                $schema['publisher']['logo'] = [
                    '@type' => 'ImageObject',
                    'url' => $logo,
                ];
            }
        }

        if (!empty($schema['image']) && is_string($schema['image'])) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $schema['image'],
            ];
        }

        if (empty($schema['image'])) {
            $image = get_the_post_thumbnail_url($post_id, 'full');
            if (!empty($image)) {
                $schema['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $image,
                ];
            }
        }

        if (empty($schema['mainEntityOfPage'])) {
            $schema['mainEntityOfPage'] = [
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id),
            ];
        }

        unset($schema['author_name']);
        return $schema;
    }

    /**
     * Normalize Service provider structure from legacy provider_name.
     *
     * @param array $schema
     * @return array
     */
    private static function normalize_service_schema($schema)
    {
        if (!empty($schema['provider']) && is_string($schema['provider'])) {
            $schema['provider'] = [
                '@type' => 'Organization',
                'name' => $schema['provider'],
            ];
        }

        if (empty($schema['provider'])) {
            $provider_name = '';
            if (!empty($schema['provider_name'])) {
                $provider_name = $schema['provider_name'];
            } else {
                $provider_name = get_bloginfo('name');
            }

            if (!empty($provider_name)) {
                $schema['provider'] = [
                    '@type' => 'Organization',
                    'name' => $provider_name,
                    'url' => home_url('/'),
                ];
            }
        }

        if (!empty($schema['provider']) && is_array($schema['provider']) && empty($schema['provider']['@type']) && !empty($schema['provider']['name'])) {
            $schema['provider']['@type'] = 'Organization';
        }

        unset($schema['provider_name']);
        return $schema;
    }

    /**
     * Normalize Event structure and suppress invalid empty event shells.
     *
     * @param array $schema
     * @param array $raw_fields
     * @param int   $post_id
     * @return array|null
     */
    private static function normalize_event_schema($schema, $raw_fields, $post_id)
    {
        $location_name = $raw_fields['location_name'] ?? ($schema['location_name'] ?? '');
        $location_addr = $raw_fields['location_address'] ?? ($schema['location_address'] ?? '');
        $location_city = $raw_fields['location_city'] ?? '';
        $location_region = $raw_fields['location_state'] ?? '';
        $location_postal = $raw_fields['location_zip'] ?? '';

        if (empty($location_name) && get_post_type($post_id) === 'location') {
            $location_name = get_the_title($post_id);
        }
        if (empty($location_addr) && get_post_type($post_id) === 'location') {
            $location_addr = get_post_meta($post_id, 'location_address', true);
        }
        if (empty($location_city) && get_post_type($post_id) === 'location') {
            $location_city = get_post_meta($post_id, 'location_city', true);
        }
        if (empty($location_region) && get_post_type($post_id) === 'location') {
            $location_region = get_post_meta($post_id, 'location_state', true);
        }
        if (empty($location_postal) && get_post_type($post_id) === 'location') {
            $location_postal = get_post_meta($post_id, 'location_zip', true);
        }

        if (empty($schema['location']) && (!empty($location_name) || !empty($location_addr))) {
            $schema['location'] = [
                '@type' => 'Place',
                'name' => $location_name,
            ];
            if (!empty($location_addr) || !empty($location_city) || !empty($location_region) || !empty($location_postal)) {
                $schema['location']['address'] = [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $location_addr,
                    'addressLocality' => $location_city,
                    'addressRegion' => $location_region,
                    'postalCode' => $location_postal,
                    'addressCountry' => 'US',
                ];
            }
        } elseif (isset($schema['location']) && is_string($schema['location']) && !empty($schema['location'])) {
            $schema['location'] = [
                '@type' => 'Place',
                'name' => $schema['location'],
            ];
        }

        if (!empty($schema['location']) && is_array($schema['location']) && !empty($schema['location']['address']) && is_string($schema['location']['address'])) {
            $schema['location']['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $schema['location']['address'],
                'addressLocality' => $location_city,
                'addressRegion' => $location_region,
                'postalCode' => $location_postal,
                'addressCountry' => 'US',
            ];
        }

        if (!empty($schema['location']) && is_array($schema['location']) && !empty($schema['location']['address']) && is_array($schema['location']['address'])) {
            if (empty($schema['location']['address']['@type'])) {
                $schema['location']['address']['@type'] = 'PostalAddress';
            }
            if (empty($schema['location']['address']['addressCountry'])) {
                $schema['location']['address']['addressCountry'] = 'US';
            }
        }

        if (!empty($schema['startDate'])) {
            if (empty($schema['endDate'])) {
                $schema['endDate'] = date('c', strtotime($schema['startDate']) + 7200);
            }
            if (empty($schema['eventAttendanceMode'])) {
                $schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
            }
            if (empty($schema['eventStatus'])) {
                $schema['eventStatus'] = 'https://schema.org/EventScheduled';
            }
        }

        if (empty($schema['offers'])) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock',
            ];
        }

        unset($schema['location_name'], $schema['location_address']);

        // Do not output Event schema shells that cannot pass validation.
        if (empty($schema['startDate']) || empty($schema['location'])) {
            return null;
        }

        return $schema;
    }
}



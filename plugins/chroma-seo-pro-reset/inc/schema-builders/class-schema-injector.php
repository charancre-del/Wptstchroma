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
function chroma_is_invalid_schema_type($type) {
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
        $override = get_post_meta(get_queried_object_id(), '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        $schema = self::get_person_schema_data(get_the_ID());
        if ($schema) {
            Chroma_Schema_Registry::register($schema, ['source' => 'schema-injector-person']);
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
        return [
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
        $override = get_post_meta($target_id, '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        $schema = self::get_organization_schema_data();

        // Inject Team Members on About Page
        if (is_page('about')) {
            $team_posts = get_posts([
                'post_type'      => 'team_member',
                'posts_per_page' => -1,
                'orderby'        => 'menu_order',
                'order'          => 'ASC',
                'post_status'    => 'publish'
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
        $override = get_post_meta(get_option('page_on_front'), '_chroma_schema_override', true);
        if ($override) {
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
        $override = get_post_meta($location_id, '_chroma_schema_override', true);
        if ($override) {
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
                $program_desc = get_post_field('post_excerpt', $post_id);
                if (empty($program_desc)) {
                    $content = get_post_field('post_content', $post_id);
                    $program_desc = wp_trim_words(strip_shortcodes($content), 55);
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
        $override = get_post_meta(get_queried_object_id(), '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        $post_id = get_the_ID();
        $schemas = get_post_meta($post_id, '_chroma_post_schemas', true);

        if (empty($schemas) || !is_array($schemas)) {
            return;
        }

        $graph = [];

        foreach ($schemas as $schema_data) {
            if (empty($schema_data['type'])) {
                continue;
            }

            $schema_type = sanitize_text_field($schema_data['type']);

            // Global Suppression Check (e.g. for external AI schema)
            $override = get_post_meta($post_id, '_chroma_schema_override', true);
            if ($override && ($schema_type === 'FAQPage' || $schema_type === 'BreadcrumbList')) {
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

            $fields = isset($schema_data['data']) ? $schema_data['data'] : [];

            $schema_output = [
                '@type' => $schema_type,
                '@id' => get_permalink($post_id) . '#' . strtolower($schema_type) . '-' . uniqid()
            ];

            // Add fields
            foreach ($fields as $key => $value) {
                if (empty($value))
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
                        $schema_output['mainEntity'] = [];
                        foreach ($value as $q) {
                            $schema_output['mainEntity'][] = [
                                '@type' => 'Question',
                                'name' => isset($q['question']) ? $q['question'] : '',
                                'acceptedAnswer' => [
                                    '@type' => 'Answer',
                                    'text' => isset($q['answer']) ? $q['answer'] : ''
                                ]
                            ];
                        }
                        global $chroma_faq_output_done;
                        $chroma_faq_output_done = true;
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
                // Fallback: also route through Registry for consistency
                $final_schema = [
                    '@context' => 'https://schema.org',
                    '@graph' => $graph
                ];
                Chroma_Schema_Registry::register($final_schema, ['source' => 'schema-injector-modular-legacy']);
            }
        }
    }
}



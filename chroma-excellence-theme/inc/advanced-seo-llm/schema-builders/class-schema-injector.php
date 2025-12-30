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
));

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

        $schema = self::get_person_schema_data(get_the_ID());
        if ($schema) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
        }
    }

    /**
     * Output CourseInstance Schema for Pre-K Programs
     */
    public static function output_course_schema()
    {
        if (!is_singular('program')) {
            return;
        }

        $post_id = get_the_ID();
        $title = get_the_title($post_id);

        // Only apply to Pre-K or educational programs
        if (stripos($title, 'Pre-K') === false && stripos($title, 'Preschool') === false && stripos($title, 'Kindergarten') === false) {
            return;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CourseInstance',
            'name' => $title,
            'description' => get_the_excerpt($post_id),
            'courseMode' => 'onsite',
            'provider' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ]
        ];

        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
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
    }

    /**
     * Output Global Organization Schema
     */
    public static function output_organization_schema()
    {
        if (!is_front_page()) {
            return;
        }

        $schema = self::get_organization_schema_data();
        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
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

        echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
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

            // Skip invalid/irrelevant schema types
            if (in_array($schema_type, CHROMA_INVALID_SCHEMA_TYPES, true)) {
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
            $final_schema = [
                '@context' => 'https://schema.org',
                '@graph' => $graph
            ];
            echo '<script type="application/ld+json">' . wp_json_encode($final_schema) . '</script>' . "\n";
        }
    }
}

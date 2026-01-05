<?php
/**
 * Theme Schema Compatibility Layer
 * 
 * Contains schema functions migrated from theme's seo-engine.php.
 * These functions output schema for Location, City, Program, and general content pages.
 *
 * @package Chroma_SEO_Pro
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Global Schema Override Handler (for standard pages/posts)
 * Hooks early to catch generic pages that have manual fixes
 */
if (!function_exists('chroma_general_content_schema')) {
    function chroma_general_content_schema() {
        if (is_singular('location') || is_singular('program') || is_singular('city') || is_front_page()) {
            return; // Handled by specific functions below
        }

        $post_id = get_the_ID();
        if (!$post_id) return;

        $override = get_post_meta($post_id, '_chroma_schema_override', true);
        if ($override) {
            if (strpos($override, '<script') !== false) {
                echo $override;
            } else {
                echo '<script type="application/ld+json">' . $override . '</script>' . "\n";
            }
        }
    }
}
add_action('wp_head', 'chroma_general_content_schema', 1);

/**
 * Helper: Get Schema Value (English or Spanish)
 */
function chroma_get_schema_val($post_id, $es_meta_key, $en_val) {
    if (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) {
        $es_val = get_post_meta($post_id, $es_meta_key, true);
        return $es_val ?: $en_val;
    }
    return $en_val;
}

/**
 * Add LocalBusiness Schema to Location Pages
 */
if (!function_exists('chroma_location_schema')) {
function chroma_location_schema()
{
    if (!is_singular('location')) {
        return;
    }

    $location_id = get_the_ID();
    
    // Check for manual override first
    $override = get_post_meta($location_id, '_chroma_schema_override', true);
    if ($override) {
        if (strpos($override, '<script') !== false) {
            echo $override;
        } else {
            echo '<script type="application/ld+json">' . $override . '</script>';
        }
        return;
    }

    // Ensure Advanced SEO classes are available
    if (!class_exists('Chroma_Fallback_Resolver')) {
        return;
    }

    // Get location fields (theme helper function)
    if (!function_exists('chroma_get_location_fields')) {
        return;
    }

    $location_fields = chroma_get_location_fields($location_id);
    $service_area = Chroma_Fallback_Resolver::get_service_area_circle($location_id);

    // Meta Fields
    $en_name = get_post_meta($location_id, 'schema_loc_name', true) ?: get_the_title();
    $name = chroma_get_schema_val($location_id, '_chroma_es_title', $en_name);
    
    $en_desc = get_post_meta($location_id, 'schema_loc_description', true) ?: get_the_excerpt();
    $description = chroma_get_schema_val($location_id, '_chroma_es_excerpt', $en_desc);

    $telephone = get_post_meta($location_id, 'schema_loc_telephone', true) ?: ($location_fields['phone'] ?? '');
    $email = get_post_meta($location_id, 'schema_loc_email', true) ?: ($location_fields['email'] ?? '');
    $opening_hours_raw = get_post_meta($location_id, 'schema_loc_opening_hours', true) ?: ($location_fields['hours'] ?? '');
    $payment = get_post_meta($location_id, 'schema_loc_payment_accepted', true);
    
    // Address Fields (Localized)
    $street = chroma_get_schema_val($location_id, '_chroma_es_location_address', ($location_fields['address'] ?? ''));
    $city   = chroma_get_schema_val($location_id, '_chroma_es_location_city', ($location_fields['city'] ?? ''));
    
    $price_range = get_post_meta($location_id, 'seo_llm_price_min', true);
    $quality_rated = get_post_meta($location_id, 'location_quality_rated', true);
    
    // Price Range Formatting
    if ($price_range) {
        $price_max = get_post_meta($location_id, 'seo_llm_price_max', true);
        $currency = get_post_meta($location_id, 'seo_llm_price_currency', true) ?: 'USD';
        $frequency = get_post_meta($location_id, 'seo_llm_price_frequency', true) ?: 'week';
        $price_range = "$currency $price_range" . ($price_max ? "-$price_max" : "") . " per $frequency";
    } else {
        $price_range = get_post_meta($location_id, 'schema_loc_price_range', true) ?: '$$';
    }

    // Schema Construction
    $types = array('ChildCare', 'Preschool', 'EducationalOrganization', 'LocalBusiness');
    
    if (get_post_meta($location_id, '_chroma_is_event_venue', true)) {
        $types[] = 'EventVenue';
    }

    // Get logo from theme or plugin setting
    $logo = '';
    if (function_exists('chroma_get_global_setting')) {
        $logo = chroma_get_global_setting('global_logo', '');
    }
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => $types,
        '@id' => get_permalink() . '#organization',
        'name' => $name,
        'description' => $description,
        'url' => get_permalink(), // URL is automatically filtered by Multilingual Manager? Yes.
        'image' => get_the_post_thumbnail_url($location_id, 'full'),
        'logo' => $logo,
        'telephone' => $telephone,
        'email' => $email,
        'priceRange' => $price_range,
        'address' => array(
            '@type' => 'PostalAddress',
            'streetAddress' => $street,
            'addressLocality' => $city,
            'addressRegion' => $location_fields['state'] ?? '',
            'postalCode' => $location_fields['zip'] ?? '',
            'addressCountry' => 'US'
        ),
    );

    // Social Profiles
    $socials = array();
    if (function_exists('chroma_global_facebook_url')) $socials[] = chroma_global_facebook_url();
    if (function_exists('chroma_global_instagram_url')) $socials[] = chroma_global_instagram_url();
    if (function_exists('chroma_global_linkedin_url')) $socials[] = chroma_global_linkedin_url();
    $socials = array_filter($socials);
    if (!empty($socials)) {
        $schema['sameAs'] = array_values($socials);
    }

    // Geo Coordinates
    $area_served = array();
    if ($service_area) {
        $schema['geo'] = array(
            '@type' => 'GeoCoordinates',
            'latitude' => $service_area['lat'],
            'longitude' => $service_area['lng'],
        );
        $area_served[] = array(
            '@type' => 'GeoCircle',
            'geoMidpoint' => $schema['geo'],
            'geoRadius' => ($service_area['radius'] * 1609.34)
        );
    } elseif (!empty($location_fields['latitude']) && !empty($location_fields['longitude'])) {
        $schema['geo'] = array(
            '@type' => 'GeoCoordinates',
            'latitude' => $location_fields['latitude'],
            'longitude' => $location_fields['longitude'],
        );
    }

    if (!empty($area_served)) {
        $schema['areaServed'] = $area_served;
    }

    // Google Maps CID
    $cid = get_post_meta($location_id, '_chroma_google_maps_cid', true);
    if ($cid) {
        $schema['hasMap'] = "https://www.google.com/maps?cid=$cid";
    } elseif (!empty($location_fields['map_link'])) {
        $schema['hasMap'] = $location_fields['map_link'];
    }

    // Opening Hours
    if ($opening_hours_raw) {
        $hours_lines = explode("\n", $opening_hours_raw);
        $specs = array();
        foreach ($hours_lines as $line) {
            if (preg_match('/(\d{1,2}(?::\d{2})?\s*[ap]m)\s*-\s*(\d{1,2}(?::\d{2})?\s*[ap]m)/i', $line, $matches)) {
                $opens = date("H:i", strtotime($matches[1]));
                $closes = date("H:i", strtotime($matches[2]));
                $specs[] = array(
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'),
                    'opens' => $opens,
                    'closes' => $closes
                );
            }
        }
        if (!empty($specs)) {
            $schema['openingHoursSpecification'] = $specs;
        } else {
            $schema['openingHours'] = $opening_hours_raw;
        }
    }

    // Amenities & Credentials
    $schema['amenityFeature'] = array();
    
    $license_num = get_post_meta($location_id, '_chroma_license_number', true);
    if ($license_num) {
        $schema['hasCredential'] = array(
            '@type' => 'EducationalOccupationalCredential',
            'credentialCategory' => 'license',
            'name' => 'Georgia DECAL License',
            'identifier' => array(
                '@type' => 'PropertyValue',
                'propertyID' => 'License Number',
                'value' => $license_num
            ),
        );
    }

    $amenities = get_post_meta($location_id, '_chroma_amenities', true);
    if (is_array($amenities) && !empty($amenities)) {
        foreach ($amenities as $amenity) {
            $schema['amenityFeature'][] = array(
                '@type' => 'LocationFeatureSpecification',
                'name' => $amenity,
                'value' => true
            );
        }
    }

    if ($quality_rated) {
        $schema['amenityFeature'][] = array(
            '@type' => 'LocationFeatureSpecification',
            'name' => 'Quality Rated',
            'value' => true
        );
    }

    if (empty($schema['amenityFeature'])) {
        unset($schema['amenityFeature']);
    }

    // Director
    $director_name = get_post_meta($location_id, 'location_director_name', true);
    if ($director_name) {
        $director_bio = get_post_meta($location_id, 'location_director_bio', true);
        $director_photo = get_post_meta($location_id, 'location_director_photo', true);
        
        $schema['employee'] = array(
            '@type' => 'Person',
            'name' => $director_name,
            'jobTitle' => 'Center Director',
            'description' => $director_bio ? wp_strip_all_tags($director_bio) : ''
        );
        if ($director_photo) {
            $schema['employee']['image'] = $director_photo;
        }
    }

    // Reviews
    $rating_value = get_post_meta($location_id, 'seo_llm_rating_value', true) ?: get_post_meta($location_id, 'location_google_rating', true);
    $rating_count = get_post_meta($location_id, 'seo_llm_rating_count', true);

    if ($rating_value) {
        $schema['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => $rating_value,
            'reviewCount' => $rating_count ?: '1',
            'bestRating' => '5',
            'worstRating' => '1'
        );
    }

    if ($payment) {
        $schema['paymentAccepted'] = $payment;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
}
add_action('wp_head', 'chroma_location_schema');

/**
 * Add Service Schema to City Pages
 */
if (!function_exists('chroma_city_schema')) {
function chroma_city_schema()
{
    if (!is_singular('city')) {
        return;
    }

    $post_id = get_the_ID();
    
    $override = get_post_meta($post_id, '_chroma_schema_override', true);
    if ($override) {
        if (strpos($override, '<script') !== false) {
            echo $override;
        } else {
            echo '<script type="application/ld+json">' . $override . '</script>' . "\n";
        }
        return;
    }

    $en_name = get_the_title();
    $city_name = chroma_get_schema_val($post_id, '_chroma_es_title', $en_name);

    $en_desc = get_the_excerpt() ?: "Premier child care and early education services in $en_name, GA.";
    $desc = chroma_get_schema_val($post_id, '_chroma_es_excerpt', $en_desc);
    
    $location_ids = get_post_meta($post_id, 'city_nearby_locations', true);

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => "Daycare & Preschool in $city_name",
        'serviceType' => 'Child Care',
        'provider' => array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url()
        ),
        'areaServed' => array(
            '@type' => 'City',
            'name' => $city_name
        ),
        'description' => $desc,
        'url' => get_permalink()
    );

    if (!empty($location_ids) && is_array($location_ids)) {
        $offers = array();
        foreach ($location_ids as $loc_id) {
            $loc_name = chroma_get_schema_val($loc_id, '_chroma_es_title', get_the_title($loc_id));
            $offers[] = array(
                '@type' => 'Offer',
                'itemOffered' => array(
                    '@type' => 'ChildCare',
                    'name' => $loc_name,
                    'url' => get_permalink($loc_id)
                )
            );
        }
        $schema['hasOfferCatalog'] = array(
            '@type' => 'OfferCatalog',
            'name' => "Schools serving $city_name",
            'itemListElement' => $offers
        );
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
}
add_action('wp_head', 'chroma_city_schema');

/**
 * Add Service Schema to Program Pages
 */
if (!function_exists('chroma_program_schema')) {
function chroma_program_schema()
{
    if (!is_singular('program')) {
        return;
    }

    $program_id = get_the_ID();
    
    $override = get_post_meta($program_id, '_chroma_schema_override', true);
    if ($override) {
        if (strpos($override, '<script') !== false) {
            echo $override;
        } else {
            echo '<script type="application/ld+json">' . $override . '</script>';
        }
        return;
    }

    $en_name = get_post_meta($program_id, 'schema_prog_name', true) ?: get_the_title();
    $name = chroma_get_schema_val($program_id, '_chroma_es_title', $en_name);

    $en_desc = get_post_meta($program_id, 'schema_prog_description', true) ?: get_the_excerpt();
    $description = chroma_get_schema_val($program_id, '_chroma_es_excerpt', $en_desc);

    $service_type = get_post_meta($program_id, 'schema_prog_service_type', true) ?: 'Early Childhood Education';
    $provider_name = get_post_meta($program_id, 'schema_prog_provider_name', true) ?: get_bloginfo('name');
    $area_served = get_post_meta($program_id, 'schema_prog_area_served', true) ?: 'Metro Atlanta';
    $category = get_post_meta($program_id, 'schema_prog_category', true);

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $name,
        'description' => $description,
        'url' => get_permalink(),
        'provider' => array(
            '@type' => 'Organization',
            'name' => $provider_name,
        ),
        'serviceType' => $service_type,
        'areaServed' => $area_served,
    );

    if ($category) {
        $schema['category'] = $category;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
}
add_action('wp_head', 'chroma_program_schema');

/**
 * Add FAQPage Schema to City Pages
 * Generates common FAQ questions about childcare in the specific city
 */
if (!function_exists('chroma_city_faq_schema_output')) {
function chroma_city_faq_schema_output()
{
    if (!is_singular('city')) {
        return;
    }

    // Check for manual override
    $override = get_post_meta(get_the_ID(), '_chroma_schema_override', true);
    if ($override) {
        return;
    }

    $city = get_the_title();
    $county = get_post_meta(get_the_ID(), 'city_county', true) ?: 'Local';

    // Questions and Answers
    $faq_items = array(
        array(
            'question' => "Do you offer GA Lottery Pre-K in $city?",
            'answer' => "Yes! Our locations serving $city participate in the Georgia Lottery Pre-K program. It is tuition-free for all 4-year-olds living in Georgia."
        ),
        array(
            'question' => "Do you provide transportation from $city schools?",
            'answer' => "We provide safe bus transportation from most major elementary schools in the $county School District. Check the specific campus page for a full list."
        ),
        array(
            'question' => "What ages do you accept at your $city centers?",
            'answer' => "We serve children from 6 weeks old (Infant Care) up to 12 years old (After School). We also offer a Pre-K Prep option at select locations."
        ),
        array(
            'question' => "How do I enroll my child in $city?",
            'answer' => "The best way to start is by scheduling a tour at your preferred location. You can book online or call us directly. We'll walk you through the enrollment process and answer all your questions."
        ),
    );

    $entities = array();
    foreach ($faq_items as $item) {
        $entities[] = array(
            '@type' => 'Question',
            'name' => $item['question'],
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => $item['answer'],
            ),
        );
    }

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $entities,
    );

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
}
add_action('wp_head', 'chroma_city_faq_schema_output');


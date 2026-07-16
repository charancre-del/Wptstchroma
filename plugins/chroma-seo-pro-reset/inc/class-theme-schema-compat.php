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
 * Safely output schema override JSON-LD.
 *
 * Accepts raw JSON or legacy <script type="application/ld+json"> wrappers.
 * Never echoes raw HTML directly.
 *
 * @param int    $post_id Post ID.
 * @param string $source  Source label for registry debug.
 * @return bool
 */
if (!function_exists('chroma_output_schema_override_pro')) {
    function chroma_output_schema_override_pro($post_id, $source = 'theme-compat-override') {
        $items = function_exists('chroma_get_schema_override_items_pro')
            ? chroma_get_schema_override_items_pro($post_id)
            : [];

        if (empty($items)) {
            return false;
        }

        $emitted = false;

        foreach ($items as $schema_item) {
            if (!is_array($schema_item) || empty($schema_item)) {
                continue;
            }

            if (function_exists('chroma_schema_strip_internal_keys')) {
                $schema_item = chroma_schema_strip_internal_keys($schema_item);
            }

            if (!isset($schema_item['@context'])) {
                $schema_item['@context'] = 'https://schema.org';
            }

            if (class_exists('Chroma_Schema_Registry') && isset($schema_item['@type'])) {
                $emitted = Chroma_Schema_Registry::register($schema_item, ['source' => $source]) || $emitted;
                continue;
            }

            echo '<script type="application/ld+json">' . wp_json_encode($schema_item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
            $emitted = true;
        }

        return $emitted;
    }
}

/**
 * Decode a stored manual schema override into usable JSON-LD items.
 *
 * Some older records contain scalar placeholders such as "1". Those values
 * should not suppress generated or legacy API-loaded schema.
 *
 * @param int $post_id
 * @return array<int,array>
 */
if (!function_exists('chroma_get_schema_override_items_pro')) {
    function chroma_get_schema_override_items_pro($post_id) {
        $override = get_post_meta($post_id, '_chroma_schema_override', true);
        if (empty($override)) {
            return [];
        }

        if (is_array($override)) {
            $decoded = $override;
        } elseif (is_string($override)) {
            $raw = trim(wp_unslash($override));
            if ($raw === '') {
                return [];
            }

            // Backward compatibility: allow stored script wrapper, but parse JSON only.
            if (stripos($raw, '<script') !== false) {
                if (preg_match('/<script[^>]*application\/ld\+json[^>]*>(.*?)<\/script>/is', $raw, $matches)) {
                    $raw = trim($matches[1]);
                } else {
                    return [];
                }
            }

            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [];
            }
        } else {
            return [];
        }

        if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
            $decoded = $decoded['@graph'];
        }

        $items = isset($decoded[0]) && is_array($decoded[0]) ? $decoded : [$decoded];
        $valid = [];

        foreach ($items as $schema_item) {
            if (!is_array($schema_item) || empty($schema_item)) {
                continue;
            }

            if (isset($schema_item['@type']) || isset($schema_item['@graph'])) {
                $valid[] = $schema_item;
            }
        }

        return $valid;
    }
}

if (!function_exists('chroma_has_valid_schema_override_pro')) {
    function chroma_has_valid_schema_override_pro($post_id) {
        return !empty(chroma_get_schema_override_items_pro($post_id));
    }
}

/**
 * Global Schema Override Handler (for standard pages/posts)
 * Hooks early to catch generic pages that have manual fixes
 */
if (!function_exists('chroma_general_content_schema_pro')) {
    function chroma_general_content_schema_pro() {
        if (is_singular('location') || is_singular('program') || is_singular('city') || is_front_page()) {
            return; 
        }

        $post_id = get_the_ID();
        if (!$post_id) return;

        if (chroma_output_schema_override_pro($post_id, 'theme-compat-override')) {
            return;
        }
    }
}
add_action('wp_head', 'chroma_general_content_schema_pro', 1);

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

if (!function_exists('chroma_schema_clean_program_description_pro')) {
    /**
     * Build a plain-text program schema description without injected internal-link copy.
     *
     * Program content can receive combo-page "Find [Program] Near You" links via
     * content filters. Schema descriptions need the educational summary, not that
     * navigational block.
     *
     * @param int $post_id Program post ID.
     * @param mixed $candidate Preferred candidate text.
     * @return string
     */
    function chroma_schema_clean_program_description_pro($post_id, $candidate = '') {
        $values = [
            $candidate,
            get_post_meta($post_id, 'program_meta_description', true),
            get_post_meta($post_id, 'program_short_description', true),
            get_post_meta($post_id, 'program_hero_description', true),
            get_post_meta($post_id, 'schema_prog_description', true),
            get_post_field('post_excerpt', $post_id),
            get_post_field('post_content', $post_id),
        ];

        $charset = get_bloginfo('charset') ?: 'UTF-8';

        foreach ($values as $value) {
            $text = wp_strip_all_tags(strip_shortcodes((string) $value));
            for ($i = 0; $i < 3; $i++) {
                $decoded = html_entity_decode(wp_specialchars_decode($text, ENT_QUOTES), ENT_QUOTES | ENT_HTML5, $charset);
                if ($decoded === $text) {
                    break;
                }
                $text = $decoded;
            }

            $text = preg_replace('/\x{00a0}/u', ' ', $text);
            $text = preg_replace('/\s*Find\s+[^.?!\r\n]*?\s+Near\s+You[^\s.?!\r\n]*.*$/iu', '', $text);
            $text = preg_replace('/\s*(?:&bull;|\x{2022}|\x{00e2}\x{20ac}\x{00a2})\s*[^.?!\r\n]*$/iu', '', $text);
            $text = trim(preg_replace('/\s+/u', ' ', (string) $text));

            if ($text !== '') {
                return wp_trim_words($text, 35, '...');
            }
        }

        $title = get_the_title($post_id);
        return $title ? sprintf(__('%s program at Chroma Early Learning Academy.', 'chroma-excellence'), $title) : '';
    }
}

if (!function_exists('chroma_post_has_stored_schema_type_pro')) {
    function chroma_post_has_stored_schema_type_pro($post_id, array $types) {
        if (!function_exists('chroma_schema_store_has_any_type')) {
            return false;
        }

        foreach (['_chroma_post_schemas', '_chroma_schema_data'] as $meta_key) {
            $stored = get_post_meta($post_id, $meta_key, true);
            if (!empty($stored) && chroma_schema_store_has_any_type($stored, $types)) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Add LocalBusiness Schema to Location Pages
 */
if (!function_exists('chroma_location_schema_pro')) {
function chroma_location_schema_pro()
{
    if (!is_singular('location')) {
        return;
    }

    $location_id = get_the_ID();
    
    // Check for manual override first
    if (chroma_output_schema_override_pro($location_id, 'theme-compat-location-override')) {
        return;
    }

    // Suppress fallback only when builder/API schema has a real business replacement.
    if (chroma_post_has_stored_schema_type_pro($location_id, ['ChildCare', 'LocalBusiness', 'Preschool', 'EducationalOrganization'])) {
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
        
        // Localize frequency label
        $freq_label = $frequency;
        if (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) {
            $freq_map = ['week' => 'semana', 'month' => 'mes', 'year' => 'año'];
            $freq_label = $freq_map[$frequency] ?? $frequency;
            $price_range = "$currency $price_range" . ($price_max ? "-$price_max" : "") . " por $freq_label";
        } else {
            $price_range = "$currency $price_range" . ($price_max ? "-$price_max" : "") . " per $freq_label";
        }
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
            'name' => (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) ? 'Licencia Georgia DECAL' : 'Georgia DECAL License',
            'identifier' => array(
                '@type' => 'PropertyValue',
                'propertyID' => (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) ? 'Número de Licencia' : 'License Number',
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
            'name' => (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) ? 'Calificación de Calidad' : 'Quality Rated',
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
            'jobTitle' => (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) ? 'Director del Centro' : 'Center Director',
            'description' => $director_bio ? wp_strip_all_tags($director_bio) : ''
        );
        if ($director_photo) {
            $schema['employee']['image'] = $director_photo;
        }
    }

    // Reviews
    $rating_value = get_post_meta($location_id, 'seo_llm_rating_value', true) ?: get_post_meta($location_id, 'location_google_rating', true);
    $rating_count = get_post_meta($location_id, 'seo_llm_rating_count', true);

    $rating_value = (float) $rating_value;
    $rating_count = (int) $rating_count;
    if ($rating_value >= 1 && $rating_value <= 5 && $rating_count > 0) {
        $schema['aggregateRating'] = array(
            '@type' => 'AggregateRating',
            'ratingValue' => $rating_value,
            'reviewCount' => $rating_count,
            'bestRating' => '5',
            'worstRating' => '1'
        );
    }

    if ($payment) {
        $schema['paymentAccepted'] = $payment;
    }

    // Use Schema Registry for deduplication if available
    if (class_exists('Chroma_Schema_Registry')) {
        $schema['@context'] = 'https://schema.org';
        Chroma_Schema_Registry::register($schema, ['source' => 'location_schema_pro']);
    } else {
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
}
}
add_action('wp_head', 'chroma_location_schema_pro');

/**
 * Add Service Schema to City Pages
 */
if (!function_exists('chroma_city_schema_pro')) {
function chroma_city_schema_pro()
{
    if (!is_singular('city')) {
        return;
    }

    $post_id = get_the_ID();
    
    // Check for manual override
    if (chroma_output_schema_override_pro($post_id, 'theme-compat-city-override')) {
        return;
    }

    // Suppress fallback only when builder/API schema has a real service replacement.
    if (chroma_post_has_stored_schema_type_pro($post_id, ['Service'])) {
        return;
    }

    $en_name = get_the_title();
    $city_name = chroma_get_schema_val($post_id, '_chroma_es_title', $en_name);

    $en_desc = get_the_excerpt() ?: "Premier child care and early education services in $en_name, GA.";
    $desc = chroma_get_schema_val($post_id, '_chroma_es_excerpt', $en_desc);
    
    // Localize English Fallback if needed
    if ((class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) && !$desc) {
        $desc = "Servicios de cuidado infantil y educación temprana de primer nivel en $city_name, GA.";
    }

    $service_name = "Daycare & Preschool in $city_name";
    if (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) {
        $service_name = "Guardería y Preescolar en $city_name";
    }
    
    $location_ids = get_post_meta($post_id, 'city_nearby_locations', true);

    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $service_name,
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
        $catalog_name = "Schools serving $city_name";
        if (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) {
            $catalog_name = "Escuelas que sirven a $city_name";
        }

        $schema['hasOfferCatalog'] = array(
            '@type' => 'OfferCatalog',
            'name' => $catalog_name,
            'itemListElement' => $offers
        );
    }

    Chroma_Schema_Registry::register($schema, ['source' => 'theme-compat-city']);
}
}
add_action('wp_head', 'chroma_city_schema_pro');

/**
 * Add Service Schema to Program Pages
 */
if (!function_exists('chroma_program_schema_pro')) {
function chroma_program_schema_pro()
{
    if (!is_singular('program')) {
        return;
    }

    $program_id = get_the_ID();
    
    if (chroma_output_schema_override_pro($program_id, 'theme-compat-program-override')) {
        return;
    }

    // Suppress fallback only when builder/API schema has a real service replacement.
    if (chroma_post_has_stored_schema_type_pro($program_id, ['Service'])) {
        return;
    }

    $en_name = get_post_meta($program_id, 'schema_prog_name', true) ?: get_the_title();
    $name = chroma_get_schema_val($program_id, '_chroma_es_title', $en_name);

    $en_desc = chroma_schema_clean_program_description_pro($program_id, get_post_meta($program_id, 'schema_prog_description', true));
    $description = chroma_get_schema_val($program_id, '_chroma_es_excerpt', $en_desc);

    $service_type = get_post_meta($program_id, 'schema_prog_service_type', true) ?: 'Early Childhood Education';
    if (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish() && $service_type === 'Early Childhood Education') {
        $service_type = 'Educación de la Primera Infancia';
    }

    $provider_name = get_post_meta($program_id, 'schema_prog_provider_name', true) ?: get_bloginfo('name');
    
    $area_served = get_post_meta($program_id, 'schema_prog_area_served', true) ?: 'Metro Atlanta';
    if (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish() && $area_served === 'Metro Atlanta') {
        $area_served = 'Área Metropolitana de Atlanta';
    }
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

    Chroma_Schema_Registry::register($schema, ['source' => 'theme-compat-program']);
}
}
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
    if (function_exists('chroma_has_valid_schema_override_pro') && chroma_has_valid_schema_override_pro(get_the_ID())) {
        return;
    }

    // Suppress fallback only when builder/API schema has a FAQ replacement.
    if (chroma_post_has_stored_schema_type_pro(get_the_ID(), ['FAQPage'])) {
        return;
    }

    // Internal Duplicate Suppression
    global $chroma_faq_output_done;
    if (!empty($chroma_faq_output_done)) {
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

    // Switch to Spanish if active
    if (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) {
        $faq_items = array(
            array(
                'question' => "¿Ofrecen GA Lottery Pre-K en $city?",
                'answer' => "¡Sí! Nuestras ubicaciones que sirven a $city participan en el programa Georgia Lottery Pre-K. Es gratuito para todos los niños de 4 años que viven en Georgia."
            ),
            array(
                'question' => "¿Proporcionan transporte desde las escuelas de $city?",
                'answer' => "Proporcionamos transporte seguro en autobús desde la mayoría de las principales escuelas primarias en el Distrito Escolar de $county. Consulte la página del campus específico para obtener una lista completa."
            ),
            array(
                'question' => "¿Qué edades aceptan en sus centros de $city?",
                'answer' => "Servimos a niños desde 6 semanas de edad (Cuidado de Bebés) hasta 12 años (Después de la Escuela). También ofrecemos una opción de Preparación para Pre-K en ubicaciones seleccionadas."
            ),
            array(
                'question' => "¿Cómo inscribo a mi hijo en $city?",
                'answer' => "La mejor manera de comenzar es programando un recorrido en su ubicación preferida. Puede reservar en línea o llamarnos directamente. Lo guiaremos a través del proceso de inscripción y responderemos todas sus preguntas."
            ),
        );
    }

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

    Chroma_Schema_Registry::register($schema, ['source' => 'theme-compat-city-faq']);
    global $chroma_faq_output_done;
    $chroma_faq_output_done = true;
}
}
add_action('wp_head', 'chroma_city_faq_schema_output');
add_action('wp_head', 'chroma_program_schema_pro');


/**
 * Theme Schema Compatibility
 * Added via Audit Migration
 */

// Rename to avoid collision with legacy theme (Global Namespace)
if (!function_exists('chroma_organization_schema_pro')) {
    function chroma_organization_schema_pro() {
        if (!is_front_page()) return;
        $homepage_id = get_option('page_on_front');
        
        if (chroma_output_schema_override_pro($homepage_id, 'theme-compat-home-override')) {
            return;
        }

        // Suppress fallback only when builder/API schema has an organization replacement.
        if (chroma_post_has_stored_schema_type_pro($homepage_id, ['ChildCare', 'Organization', 'LocalBusiness', 'EducationalOrganization'])) {
            return;
        }

        $en_name = get_post_meta($homepage_id, 'schema_org_name', true) ?: get_bloginfo('name');
        $name = chroma_get_schema_val($homepage_id, '_chroma_es_title', $en_name);

        $url = get_post_meta($homepage_id, 'schema_org_url', true) ?: home_url();
        if (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) {
            $url = home_url('/es');
        }

        $logo = get_post_meta($homepage_id, 'schema_org_logo', true) ?: (function_exists('chroma_get_global_setting') ? chroma_get_global_setting('global_logo', '') : '');
        
        $en_desc = get_post_meta($homepage_id, 'schema_org_description', true) ?: (function_exists('chroma_global_seo_default_description') ? chroma_global_seo_default_description() : '');
        $description = chroma_get_schema_val($homepage_id, '_chroma_es_excerpt', $en_desc);

        $area_served = get_post_meta($homepage_id, 'schema_org_area_served', true) ?: 'Atlanta';
        $telephone = get_post_meta($homepage_id, 'schema_org_telephone', true);
        $email = get_post_meta($homepage_id, 'schema_org_email', true);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ChildCare',
            'name' => $name,
            'url' => $url,
            'logo' => $logo,
            'description' => $description,
            'areaServed' => [
                '@type' => 'City',
                'name' => $area_served,
            ],
            'sameAs' => [],
        ];
        
        if (function_exists('chroma_global_facebook_url')) {
             $schema['sameAs'] = array_filter([
                chroma_global_facebook_url(),
                chroma_global_instagram_url(),
                chroma_global_linkedin_url(),
            ]);
        }

        if ($telephone) $schema['telephone'] = $telephone;
        if ($email) $schema['email'] = $email;

        Chroma_Schema_Registry::register($schema, ['source' => 'theme-compat-organization']);
    }
}
add_action('wp_head', 'chroma_organization_schema_pro', 5);

if (!function_exists('chroma_website_schema_pro')) {
    function chroma_website_schema_pro() {
        if (!is_front_page()) return;
        $homepage_id = get_option('page_on_front');
        $url = home_url();
        if (function_exists('chroma_has_valid_schema_override_pro') && chroma_has_valid_schema_override_pro($homepage_id)) return;

        // Suppress fallback only when builder/API schema has a WebSite replacement.
        if (chroma_post_has_stored_schema_type_pro($homepage_id, ['WebSite'])) {
            return;
        }
        if (class_exists('Chroma_Multilingual_Manager') && Chroma_Multilingual_Manager::is_spanish()) {
            $url = home_url('/es');
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'url' => $url,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => home_url('/?s={search_term_string}'),
                'query-input' => 'required name=search_term_string',
            ],
        ];
        Chroma_Schema_Registry::register($schema, ['source' => 'theme-compat-website']);
    }
}
add_action('wp_head', 'chroma_website_schema_pro', 6);

if (!function_exists('chroma_faq_schema_pro')) {
    function chroma_faq_schema_pro() {
        return;
    }
}
// add_action('wp_head', 'chroma_faq_schema_pro', 10);

/**
 * Suppress Legacy Theme Schema
 * Since this file is loaded on 'init', we can remove the theme hooks immediately 
 * (as they would have been added during theme load).
 */
function chroma_remove_legacy_theme_schema() {
    // Legacy theme used default priority 10 for these (mostly)
    remove_action('wp_head', 'chroma_organization_schema', 10); 
    remove_action('wp_head', 'chroma_website_schema', 10);
    remove_action('wp_head', 'chroma_faq_schema', 10);
    remove_action('wp_head', 'chroma_general_content_schema', 1); // Explicitly 1 in theme
    
    remove_action('wp_head', 'chroma_location_schema', 10);
    remove_action('wp_head', 'chroma_city_schema', 10);
    remove_action('wp_head', 'chroma_program_schema', 10);
}
// Execute immediately if we are past theme setup, or hook to late init
add_action('wp_head', 'chroma_remove_legacy_theme_schema', 0);



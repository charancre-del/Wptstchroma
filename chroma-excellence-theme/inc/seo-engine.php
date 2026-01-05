<?php
/**
 * SEO Engine: Schema, Sitemap, Robots.txt, Meta Tags
 *
 * @package Chroma_Excellence
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
// DISABLED - Moved to Chroma SEO Pro Plugin
// add_action('wp_head', 'chroma_general_content_schema', 1);

/**
 * Add Organization Schema to Homepage
 */
function chroma_organization_schema()
{
        if (!is_front_page()) {
                return;
        }

        $homepage_id = get_option('page_on_front');

        // Check for manual override first
        $override = get_post_meta($homepage_id, '_chroma_schema_override', true);
        if ($override) {
            if (strpos($override, '<script') !== false) {
                echo $override;
            } else {
                echo '<script type="application/ld+json">' . $override . '</script>' . "\n";
            }
            return;
        }

        // Get custom values or fallbacks
        $name = get_post_meta($homepage_id, 'schema_org_name', true) ?: get_bloginfo('name');
        $url = get_post_meta($homepage_id, 'schema_org_url', true) ?: home_url();
        $logo = get_post_meta($homepage_id, 'schema_org_logo', true) ?: chroma_get_global_setting('global_logo', '');
        $description = get_post_meta($homepage_id, 'schema_org_description', true) ?: chroma_global_seo_default_description();
        $area_served = get_post_meta($homepage_id, 'schema_org_area_served', true) ?: 'Atlanta';
        $telephone = get_post_meta($homepage_id, 'schema_org_telephone', true);
        $email = get_post_meta($homepage_id, 'schema_org_email', true);

        $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'ChildCare',
                'name' => $name,
                'url' => $url,
                'logo' => $logo,
                'description' => $description,
                'areaServed' => array(
                        '@type' => 'City',
                        'name' => $area_served,
                ),
                'sameAs' => array_filter(array(
                        chroma_global_facebook_url(),
                        chroma_global_instagram_url(),
                        chroma_global_linkedin_url(),
                )),
        );

        // Add optional fields if provided
        if ($telephone) {
                $schema['telephone'] = $telephone;
        }
        if ($email) {
                $schema['email'] = $email;
        }
        
        // Phonetic Name for Voice Search (Tier 12 - TT)
        $phonetic = get_theme_mod('chroma_global_brand_phonetic', '');
        if ($phonetic) {
            $schema['phoneticName'] = $phonetic;
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
// DISABLED - Moved to Chroma SEO Pro Plugin
// add_action('wp_head', 'chroma_organization_schema');

/**
 * HTTP Header Signals (Tier 7 - Y)
 * Provides canonical and dns-prefetch hints at the HTTP header level for faster parsing
 */
function chroma_seo_headers() {
    if (headers_sent()) return;
    
    // Canonical Link Header
    if (is_singular()) {
        $link = get_permalink();
        if ($link) {
            header("Link: <$link>; rel=\"canonical\"", false);
        }
    }
    
    // DNS Prefetch for Google Services
    header("Link: <https://www.google-analytics.com>; rel=\"dns-prefetch\"", false);
    header("Link: <https://www.googletagmanager.com>; rel=\"dns-prefetch\"", false);
}
add_action('send_headers', 'chroma_seo_headers');

/**
 * Add WebSite Schema to Homepage (for Sitelinks Search Box)
 */
function chroma_website_schema()
{
        if (!is_front_page()) {
                return;
        }
        
        // Check for manual override (yield to main override if present)
        $homepage_id = get_option('page_on_front');
        $override = get_post_meta($homepage_id, '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        // Note: We don't override this individually because usually the main Org schema override covers the whole page,
        // or the user keeps this as is. If they pasted a @graph, the Org override above handles it.

        $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => get_bloginfo('name'),
                'url' => home_url(),
                'potentialAction' => array(
                        '@type' => 'SearchAction',
                        'target' => home_url('/?s={search_term_string}'),
                        'query-input' => 'required name=search_term_string',
                ),
        );

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
// DISABLED - Moved to Chroma SEO Pro Plugin
// add_action('wp_head', 'chroma_website_schema');

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
                // Check if it's already formatted with script tags or raw JSON
                if (strpos($override, '<script') !== false) {
                        echo $override; // Already contains script tags
                } else {
                        echo '<script type="application/ld+json">' . $override . '</script>';
                }
                return;
        }

        // Ensure Advanced SEO classes are available
        if (!class_exists('Chroma_Fallback_Resolver')) {
                return;
        }

        // 1. DATA GATHERING
        // -----------------
        $location_fields = chroma_get_location_fields($location_id);
        $service_area = Chroma_Fallback_Resolver::get_service_area_circle($location_id);

        // Meta Fields
        $name = get_post_meta($location_id, 'schema_loc_name', true) ?: get_the_title();
        $description = get_post_meta($location_id, 'schema_loc_description', true) ?: (get_the_excerpt() ?: chroma_trimmed_excerpt(30, $location_id));
        $telephone = get_post_meta($location_id, 'schema_loc_telephone', true) ?: $location_fields['phone'];
        $email = get_post_meta($location_id, 'schema_loc_email', true) ?: $location_fields['email'];
        $opening_hours_raw = get_post_meta($location_id, 'schema_loc_opening_hours', true) ?: $location_fields['hours'];
        $payment = get_post_meta($location_id, 'schema_loc_payment_accepted', true);
        $price_range = get_post_meta($location_id, 'seo_llm_price_min', true);
        $quality_rated = get_post_meta($location_id, 'location_quality_rated', true);
        $ages_served = get_post_meta($location_id, 'location_ages_served', true);
        $school_pickups = get_post_meta($location_id, 'location_school_pickups', true);
        // Director
        $director_name = get_post_meta($location_id, 'location_director_name', true);
        $director_bio = get_post_meta($location_id, 'location_director_bio', true);
        $director_photo = get_post_meta($location_id, 'location_director_photo', true);

        // Price Range Formatting
        if ($price_range) {
                $price_max = get_post_meta($location_id, 'seo_llm_price_max', true);
                $currency = get_post_meta($location_id, 'seo_llm_price_currency', true) ?: 'USD';
                $frequency = get_post_meta($location_id, 'seo_llm_price_frequency', true) ?: 'week';
                $price_range = "$currency $price_range" . ($price_max ? "-$price_max" : "") . " per $frequency";
        } else {
                // Fallback to manual schema field or default
                $price_range = get_post_meta($location_id, 'schema_loc_price_range', true) ?: '$$';
        }

        // 2. SCHEMA CONSTRUCTION
        // ----------------------
        $types = array('ChildCare', 'Preschool', 'EducationalOrganization', 'LocalBusiness');
        
        // Event Venue (Tier 19 - RRR) - Add if location is marked as event venue
        if (get_post_meta($location_id, '_chroma_is_event_venue', true)) {
            $types[] = 'EventVenue';
        }
        
        $schema = array(
                '@type' => $types,
                '@id' => get_permalink() . '#organization',
                'name' => $name,
                'description' => $description,
                'url' => get_permalink(),
                'image' => get_the_post_thumbnail_url($location_id, 'full'),
                'logo' => chroma_get_global_setting('global_logo', ''),
                'telephone' => $telephone,
                'email' => $email,
                'priceRange' => $price_range,
                'address' => array(
                        '@type' => 'PostalAddress',
                        'streetAddress' => $location_fields['address'],
                        'addressLocality' => $location_fields['city'],
                        'addressRegion' => $location_fields['state'],
                        'postalCode' => $location_fields['zip'],
                        'addressCountry' => 'US'
                ),
        );

        // Social Profiles (sameAs)
        $socials = array_filter(array(
                chroma_global_facebook_url(),
                chroma_global_instagram_url(),
                chroma_global_linkedin_url(),
        ));
        if (!empty($socials)) {
                $schema['sameAs'] = array_values($socials);
        }

        // Geo Coordinates & Area Served
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
                        'geoRadius' => ($service_area['radius'] * 1609.34) // Miles to meters
                );
        } elseif ($location_fields['latitude'] && $location_fields['longitude']) {
                $schema['geo'] = array(
                        '@type' => 'GeoCoordinates',
                        'latitude' => $location_fields['latitude'],
                        'longitude' => $location_fields['longitude'],
                );
        }
        
        // Bus Routes / Schools Served (Tier 10 - VV)
        $schools_served = get_post_meta($location_id, 'location_schools_served', true);
        if (!empty($schools_served)) {
            if (is_array($schools_served)) {
                foreach ($schools_served as $school) {
                    $s_name = is_array($school) ? ($school['school_name'] ?? '') : $school;
                    if ($s_name) {
                        $area_served[] = array(
                            '@type' => 'Place',
                            'name' => trim($s_name) . ' (Transportation Offered)'
                        );
                    }
                }
            } else {
                // Comma-separated fallback
                foreach (explode(',', $schools_served) as $s) {
                    if (trim($s)) {
                        $area_served[] = array('@type' => 'Place', 'name' => trim($s));
                    }
                }
            }
        }
        
        if (!empty($area_served)) {
            $schema['areaServed'] = $area_served;
        }

        // Google Maps CID Link (Tier 30 - DDDDD)
        $cid = get_post_meta($location_id, '_chroma_google_maps_cid', true);
        if ($cid) {
            $schema['hasMap'] = "https://www.google.com/maps?cid=$cid";
        } elseif ($location_fields['map_link']) {
                $schema['hasMap'] = $location_fields['map_link'];
        } else {
                // Construct Google Maps URL from address
                $addr_string = urlencode($location_fields['address'] . ', ' . $location_fields['city'] . ', ' . $location_fields['state'] . ' ' . $location_fields['zip']);
                $schema['hasMap'] = "https://www.google.com/maps/search/?api=1&query=$addr_string";
        }

        // Hours (OpeningHoursSpecification)
        if ($opening_hours_raw) {
                // Simple parser: assumes "7am - 6pm" or similar format
                // If it contains newlines, treat as multiple entries
                $hours_lines = explode("\n", $opening_hours_raw);
                $specs = array();

                foreach ($hours_lines as $line) {
                        // Try to extract times
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
                        // Fallback to simple string if parsing fails
                        $schema['openingHours'] = $opening_hours_raw;
                }
        }

        // Attributes & Credentials
        $knowsAbout = array();
        
        // Initialize amenityFeature array
        if (!isset($schema['amenityFeature'])) {
            $schema['amenityFeature'] = array();
        }
        
        // License/Permit (Tier 5 - AA)
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
                'recognizedBy' => array(
                    '@type' => 'GovernmentOrganization',
                    'name' => 'Georgia Department of Early Care and Learning',
                    'url' => 'https://www.decal.ga.gov/'
                )
            );
        }
        
        // Safety Amenities (Tier 5 - BB)
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
                $knowsAbout[] = 'Quality Rated Provider';
                $schema['amenityFeature'][] = array(
                        '@type' => 'LocationFeatureSpecification',
                        'name' => 'Quality Rated',
                        'value' => true
                );
        }
        if ($school_pickups) {
                $schema['amenityFeature'][] = array(
                        '@type' => 'LocationFeatureSpecification',
                        'name' => 'School Transportation',
                        'value' => true
                );
        }
        if (!empty($knowsAbout)) {
                $schema['knowsAbout'] = $knowsAbout;
        }
        
        // Clean empty amenityFeature array
        if (empty($schema['amenityFeature'])) {
            unset($schema['amenityFeature']);
        }

        // Audience (Ages)
        if ($ages_served) {
                $schema['audience'] = array(
                        '@type' => 'PeopleAudience',
                        'audienceType' => 'families',
                        'name' => "Children ages $ages_served"
                );
        }

        // Staff (Director)
        if ($director_name) {
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

        // Offers (Programs)
        // Query programs associated with this location
        $related_programs = get_posts(array(
                'post_type' => 'program',
                'posts_per_page' => -1,
                'meta_query' => array(
                        array(
                                'key' => 'program_locations',
                                'value' => '"' . $location_id . '"', // Serialized array search (approximate)
                                'compare' => 'LIKE'
                        )
                )
        ));

        if (!empty($related_programs)) {
                $offers = array();
                foreach ($related_programs as $program) {
                        $offers[] = array(
                                '@type' => 'Offer',
                                'name' => $program->post_title,
                                'description' => get_the_excerpt($program->ID),
                                'url' => get_permalink($program->ID),
                                'category' => get_post_meta($program->ID, 'program_age_range', true) ?: 'Child Care'
                        );
                }
                $schema['makesOffer'] = $offers;
                $schema['hasOfferCatalog'] = array(
                        '@type' => 'OfferCatalog',
                        'name' => 'Early Learning Programs',
                        'itemListElement' => $offers
                );
        }

        // Reviews (Aggregate Rating)
        $rating_value = get_post_meta($location_id, 'seo_llm_rating_value', true) ?: get_post_meta($location_id, 'location_google_rating', true);
        $rating_count = get_post_meta($location_id, 'seo_llm_rating_count', true);

        if ($rating_value) {
                $schema['aggregateRating'] = array(
                        '@type' => 'AggregateRating',
                        'ratingValue' => $rating_value,
                        'reviewCount' => $rating_count ?: '1', // Fallback to 1 if count missing but rating exists
                        'bestRating' => '5',
                        'worstRating' => '1'
                );
        }

        // Payment
        if ($payment) {
                $schema['paymentAccepted'] = $payment;
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
        
        // Open House Event Schema (Tier 4 - I)
        $open_house_date = get_post_meta($location_id, '_chroma_open_house_date', true);
        if ($open_house_date) {
            $event_schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'Event',
                'name' => 'Open House - ' . $name,
                'startDate' => date('c', strtotime($open_house_date)),
                'endDate' => date('c', strtotime($open_house_date) + 7200), // Default 2 hours
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'eventStatus' => 'https://schema.org/EventScheduled',
                'location' => array(
                    '@type' => 'Place',
                    'name' => $name,
                    'address' => array(
                        '@type' => 'PostalAddress',
                        'streetAddress' => $location_fields['address'],
                        'addressLocality' => $location_fields['city'],
                        'addressRegion' => $location_fields['state'],
                        'postalCode' => $location_fields['zip'],
                        'addressCountry' => 'US'
                    )
                ),
                'description' => "Join us for an Open House at $name. Meet the teachers, tour the classrooms, and learn about our curriculum.",
                'organizer' => array(
                    '@type' => 'Organization',
                    'name' => $name,
                    'url' => get_permalink()
                )
            );
            echo '<script type="application/ld+json">' . wp_json_encode($event_schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
        }
}
}
// DISABLED - Moved to Chroma SEO Pro Plugin
// add_action('wp_head', 'chroma_location_schema');

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
        
        // Check for manual override
        $override = get_post_meta($post_id, '_chroma_schema_override', true);
        if ($override) {
            if (strpos($override, '<script') !== false) {
                echo $override;
            } else {
                echo '<script type="application/ld+json">' . $override . '</script>' . "\n";
            }
            return;
        }

        $city_name = get_the_title();
        $location_ids = get_post_meta($post_id, 'city_nearby_locations', true);

        // Basic Service Schema
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
                'description' => get_the_excerpt() ?: "Premier child care and early education services in $city_name, GA.",
                'url' => get_permalink()
        );

        // Add Related Locations (Schools)
        if (!empty($location_ids) && is_array($location_ids)) {
                $offers = array();
                foreach ($location_ids as $loc_id) {
                        $loc_name = get_the_title($loc_id);
                        $loc_url = get_permalink($loc_id);
                        $offers[] = array(
                                '@type' => 'Offer',
                                'itemOffered' => array(
                                        '@type' => 'ChildCare',
                                        'name' => $loc_name,
                                        'url' => $loc_url
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
// DISABLED - Moved to Chroma SEO Pro Plugin
// add_action('wp_head', 'chroma_city_schema');

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
        
        // Check for manual override first
        $override = get_post_meta($program_id, '_chroma_schema_override', true);
        if ($override) {
                if (strpos($override, '<script') !== false) {
                        echo $override;
                } else {
                        echo '<script type="application/ld+json">' . $override . '</script>';
                }
                return;
        }

        // Get custom values or fallbacks
        $name = get_post_meta($program_id, 'schema_prog_name', true) ?: get_the_title();
        $description = get_post_meta($program_id, 'schema_prog_description', true) ?: (get_the_excerpt() ?: chroma_trimmed_excerpt(30, $program_id));
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

        // Add optional category if provided
        if ($category) {
                $schema['category'] = $category;
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
}
// DISABLED - Moved to Chroma SEO Pro Plugin
// add_action('wp_head', 'chroma_program_schema');

/**
 * Add FAQPage Schema to Homepage (when FAQ section exists)
 */
function chroma_faq_schema()
{
        if (!is_front_page()) {
                return;
        }

        // Check for manual override on homepage
        $homepage_id = get_option('page_on_front');
        $override = get_post_meta($homepage_id, '_chroma_schema_override', true);
        if ($override) {
            return;
        }

        // Check if FAQ data exists
        if (!function_exists('chroma_home_has_faq') || !chroma_home_has_faq()) {
                return;
        }

        if (!function_exists('chroma_home_faq')) {
                return;
        }

        $faq_data = chroma_home_faq();
        if (empty($faq_data['items'])) {
                return;
        }

        // Build FAQ schema
        $main_entity = array();
        foreach ($faq_data['items'] as $item) {
                if (empty($item['question']) || empty($item['answer'])) {
                        continue;
                }

                $main_entity[] = array(
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => array(
                                '@type' => 'Answer',
                                'text' => wp_strip_all_tags($item['answer']),
                        ),
                );
        }

        if (empty($main_entity)) {
                return;
        }

        $schema = array(
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $main_entity,
        );

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
}
// DISABLED - Moved to Chroma SEO Pro Plugin
// add_action('wp_head', 'chroma_faq_schema');

/**
 * Breadcrumb Schema is now handled by inc/advanced-seo-llm/class-breadcrumbs.php
 */

/**
 * Truncate social media title to optimal length
 * 
 * @param string $title The title to truncate
 * @param int $max_length Maximum character length (default: 60 for OG)
 * @return string Truncated title
 */
function chroma_truncate_social_title($title, $max_length = 60)
{
        if (strlen($title) <= $max_length) {
                return $title;
        }
        // Truncate and add ellipsis
        return substr($title, 0, $max_length - 3) . '...';
}

/**
 * Get the best available image for social sharing
 * Priority: 1) Featured image, 2) Custom meta field, 3) Site default
 */
function chroma_get_social_image()
{
        $image_url = '';

        // Priority 1: Featured image
        if (has_post_thumbnail()) {
                $image_url = get_the_post_thumbnail_url(null, 'large');
        }

        // Priority 2: Custom meta field for social image (if set)
        if (empty($image_url)) {
                $post_id = get_the_ID();
                if ($post_id) {
                        $custom_image = get_post_meta($post_id, '_chroma_social_image', true);
                        if (!empty($custom_image)) {
                                $image_url = $custom_image;
                        }
                }
        }

        // Priority 3: Homepage featured image as site default
        if (empty($image_url)) {
                $home_id = get_option('page_on_front');
                if ($home_id && has_post_thumbnail($home_id)) {
                        $image_url = get_the_post_thumbnail_url($home_id, 'large');
                }
        }

        // Priority 4: Theme customizer hero image
        if (empty($image_url)) {
                $hero_image = get_theme_mod('chroma_home_hero_image');
                if (!empty($hero_image)) {
                        $image_url = $hero_image;
                }
        }

        // Priority 5: Hardcoded fallback
        if (empty($image_url)) {
                $image_url = get_template_directory_uri() . '/assets/images/chroma-social-default.jpg';
        }

        return $image_url;
}

/**
 * Open Graph Tags
 */
function chroma_og_tags()
{
        // Truncate OG title to 60 characters max
        $og_title = chroma_truncate_social_title(get_the_title(), 60);

        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";

        // Always output an og:image with fallback chain
        $social_image = chroma_get_social_image();
        if (!empty($social_image)) {
                echo '<meta property="og:image" content="' . esc_url($social_image) . '" />' . "\n";
                echo '<meta property="og:image:width" content="1200" />' . "\n";
                echo '<meta property="og:image:height" content="630" />' . "\n";
        }

        $description = get_the_excerpt() ?: chroma_global_seo_default_description();
        echo '<meta property="og:description" content="' . esc_attr(wp_strip_all_tags($description)) . '" />' . "\n";
}
add_action('wp_head', 'chroma_og_tags', 5);

/**
 * Twitter Card Tags
 */
function chroma_twitter_cards()
{
        // Truncate Twitter title to 55 characters max
        $twitter_title = chroma_truncate_social_title(get_the_title(), 55);

        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($twitter_title) . '" />' . "\n";

        // Use the same fallback chain as OG tags
        $social_image = chroma_get_social_image();
        if (!empty($social_image)) {
                echo '<meta name="twitter:image" content="' . esc_url($social_image) . '" />' . "\n";
        }

        $description = get_the_excerpt() ?: chroma_global_seo_default_description();
        echo '<meta name="twitter:description" content="' . esc_attr(wp_strip_all_tags($description)) . '" />' . "\n";

        // Optional: Add Twitter site handle if available
        $twitter_handle = get_option('chroma_twitter_handle', '');
        if ($twitter_handle) {
                echo '<meta name="twitter:site" content="' . esc_attr($twitter_handle) . '" />' . "\n";
        }
}
add_action('wp_head', 'chroma_twitter_cards', 6);

/**
 * Hreflang Tags for EN/ES
 */
function chroma_hreflang_tags()
{
        $post_id = get_the_ID();

        if (!$post_id) {
                return;
        }

        $alternate_en = get_post_meta($post_id, 'alternate_url_en', true);
        $alternate_es = get_post_meta($post_id, 'alternate_url_es', true);

        if ($alternate_en) {
                echo '<link rel="alternate" hreflang="en" href="' . esc_url($alternate_en) . '" />' . "\n";
        }

        if ($alternate_es) {
                echo '<link rel="alternate" hreflang="es" href="' . esc_url($alternate_es) . '" />' . "\n";
        }
}
add_action('wp_head', 'chroma_hreflang_tags', 1);

/**
 * Shared meta description output with fallbacks
 */
/**
 * Shared meta description output with fallbacks
 */
function chroma_shared_meta_description()
{
        // Skip for combo pages - handled by class-combo-page-generator.php
        if (get_query_var('chroma_combo')) {
                return;
        }
        
        // 1. Manual Override (General SEO Meta Box)
        $post_id = get_the_ID();
        $manual_description = $post_id ? get_post_meta($post_id, 'meta_description', true) : '';

        if (!empty($manual_description)) {
                echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($manual_description)) . '" />' . "\n";
                return;
        }

        // 2. Dynamic Templates
        $description = '';

        if (is_singular('location')) {
                // Location Template: "Visit our [Location Name] campus in [City], [State]. [Tagline]. Serving families in [Service Areas]. [Phone]."
                $city = get_post_meta($post_id, 'location_city', true);
                $state = get_post_meta($post_id, 'location_state', true);
                $tagline = get_post_meta($post_id, 'location_tagline', true);
                $service_areas = get_post_meta($post_id, 'location_service_areas', true);
                $phone = get_post_meta($post_id, 'location_phone', true);

                $parts = array();
                $parts[] = 'Visit our ' . get_the_title() . ' campus';
                if ($city && $state) {
                        $parts[] = "in $city, $state";
                }
                if ($tagline) {
                        $parts[] = ". $tagline";
                }
                if ($service_areas) {
                        $parts[] = ". Serving families in " . $service_areas;
                }
                if ($phone) {
                        $parts[] = ". Call us at $phone";
                }

                $description = implode('', $parts) . '.';

        } elseif (is_singular('city')) {
                // City Template: "Best Daycare & Preschool in [City], GA. [Excerpt]"
                $excerpt = has_excerpt() ? get_the_excerpt() : chroma_trimmed_excerpt(30, $post_id);
                $description = "Best Daycare & Preschool in " . get_the_title() . ", GA. " . $excerpt;

        } elseif (is_singular('program')) {
                // Program Template: "[Program Name] at Chroma Early Learning Academy. [Excerpt]."
                $excerpt = has_excerpt() ? get_the_excerpt() : chroma_trimmed_excerpt(20, $post_id);
                $description = get_the_title() . ' at Chroma Early Learning Academy. ' . $excerpt;

        } elseif (is_singular('post')) {
                // Blog Post Template: "[Title] - [Excerpt]"
                $excerpt = has_excerpt() ? get_the_excerpt() : chroma_trimmed_excerpt(30, $post_id);
                $description = get_the_title() . ' - ' . $excerpt;

        } elseif (is_front_page()) {
                // Homepage Template: Global Default > Tagline > Constructed Fallback
                $description = chroma_global_seo_default_description();
                if (empty($description)) {
                        $description = get_bloginfo('description');
                }
                if (empty($description)) {
                        $description = get_bloginfo('name') . ' offers premier child care, daycare, and early childhood education in the Metro Atlanta area.';
                }
        }

        // 3. Global Default / Fallback
        if (empty($description)) {
                echo "<!-- Debug: Description empty, trying global fallback -->\n";
                // Preserve About page specific metadata if defined (legacy support)
                if (function_exists('chroma_is_about_template') && function_exists('chroma_get_about_seo_fields') && chroma_is_about_template()) {
                        $about_fields = chroma_get_about_seo_fields();
                        if (!empty($about_fields['description'])) {
                                $description = $about_fields['description'];
                        }
                }

                if (empty($description) && $post_id) {
                        $description = has_excerpt($post_id) ? get_the_excerpt($post_id) : chroma_trimmed_excerpt(32, $post_id);
                }

                if (empty($description)) {
                        $description = chroma_global_seo_default_description();
                }
        }

        if ($description) {
                echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($description)) . '" />' . "\n";
        } else {
                echo "<!-- Debug: Final Description is EMPTY -->\n";
        }
}
add_action('wp_head', 'chroma_shared_meta_description', 2);

/**
 * Meta Keywords Output
 */
function chroma_meta_keywords()
{
        // 1. Manual Override
        $post_id = get_the_ID();
        $manual_keywords = $post_id ? get_post_meta($post_id, 'meta_keywords', true) : '';

        if (!empty($manual_keywords)) {
                echo '<meta name="keywords" content="' . esc_attr(wp_strip_all_tags($manual_keywords)) . '" />' . "\n";
                return;
        }

        // 2. CSV-based Keyword Mapping
        $keywords = array();
        $keyword_map_file = get_template_directory() . '/inc/seo-keywords-data.php';

        if (file_exists($keyword_map_file)) {
                $keyword_map = include $keyword_map_file;
        } else {
                $keyword_map = array();
        }

        $slug = $post_id ? get_post_field('post_name', $post_id) : '';

        // Handle Homepage case
        if (is_front_page() || is_home()) {
                $slug = 'home';
        }

        if (!empty($slug) && isset($keyword_map[$slug]) && is_array($keyword_map[$slug])) {
                $keywords = array_merge($keywords, $keyword_map[$slug]);
        }

        // 3. Dynamic Templates (Fallback/Addition)
        if (is_singular('location')) {
                $city = get_post_meta($post_id, 'location_city', true);
                $service_areas = get_post_meta($post_id, 'location_service_areas', true);
                if (empty($keywords)) {
                        $keywords[] = get_the_title();
                }

                if ($city) {
                        $keywords[] = "child care $city";
                        $keywords[] = "daycare $city";
                        $keywords[] = "preschool $city";
                }
                if ($service_areas) {
                        // Split service areas by comma if it's a list
                        $areas = explode(',', $service_areas);
                        foreach ($areas as $area) {
                                $keywords[] = trim($area);
                        }
                }

        } elseif (is_singular('city')) {
                $city_name = get_the_title();
                $keywords[] = "daycare $city_name";
                $keywords[] = "child care $city_name";
                $keywords[] = "preschool $city_name";
                $keywords[] = "best daycare in $city_name";
                $keywords[] = "early learning $city_name";
                $keywords[] = "childcare near me";

        } elseif (is_singular('city')) {
                $city_name = get_the_title();
                $keywords[] = "daycare $city_name";
                $keywords[] = "child care $city_name";
                $keywords[] = "preschool $city_name";
                $keywords[] = "best daycare in $city_name";
                $keywords[] = "early learning $city_name";
                $keywords[] = "childcare near me";

        } elseif (is_singular('program')) {
                if (empty($keywords)) {
                        $keywords[] = get_the_title();
                }
                $keywords[] = 'early childhood education';
                $keywords[] = 'curriculum';
                $keywords[] = 'child development';

        } else {
                // Global defaults if still empty
                if (empty($keywords)) {
                        $keywords[] = 'child care';
                        $keywords[] = 'daycare';
                        $keywords[] = 'preschool';
                        $keywords[] = 'early learning';
                        $keywords[] = 'Atlanta';
                }
        }

        // Deduplicate and output
        $keywords = array_unique($keywords);

        if (!empty($keywords)) {
                echo '<meta name="keywords" content="' . esc_attr(implode(', ', $keywords)) . '" />' . "\n";
        }
}
add_action('wp_head', 'chroma_meta_keywords', 3);

/**
 * Custom Sitemap.xml
 */
function chroma_custom_sitemap()
{
        if (get_query_var('sitemap') !== 'xml') {
                return;
        }

        // Get Options
        $options = get_option('chroma_sitemap_options', array(
                'enable_pages' => true,
                'enable_posts' => true,
                'enable_locations' => true,
                'enable_programs' => true,
                'exclude_ids' => '',
                'custom_urls' => '',
                'use_uploaded' => false,
        ));

        header('Content-Type: application/xml; charset=utf-8');

        // 1. Check for Static Upload Override
        if (!empty($options['use_uploaded'])) {
                $upload_dir = wp_upload_dir();
                $static_path = $upload_dir['basedir'] . '/chroma-sitemap-manual.xml';
                if (file_exists($static_path)) {
                        readfile($static_path);
                        exit;
                }
        }

        // 2. Dynamic Generation
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        echo '<url>' . "\n";
        echo '  <loc>' . esc_url(user_trailingslashit(home_url('/'))) . '</loc>' . "\n";
        echo '  <lastmod>' . date('c') . '</lastmod>' . "\n";
        echo '  <changefreq>daily</changefreq>' . "\n";
        echo '  <priority>1.0</priority>' . "\n";
        echo '</url>' . "\n";

        $exclude_ids = array();
        if (!empty($options['exclude_ids'])) {
                $exclude_ids = array_map('trim', explode(',', $options['exclude_ids']));
        }

        // Pages
        if (!empty($options['enable_pages'])) {
                $pages = get_posts(array(
                        'post_type' => 'page',
                        'posts_per_page' => -1,
                        'exclude' => $exclude_ids,
                        'post_status' => 'publish'
                ));
                foreach ($pages as $page) {
                        echo '<url>' . "\n";
                        echo '  <loc>' . esc_url(user_trailingslashit(get_permalink($page->ID))) . '</loc>' . "\n";
                        echo '  <lastmod>' . get_the_modified_date('c', $page->ID) . '</lastmod>' . "\n";
                        echo '  <changefreq>weekly</changefreq>' . "\n";
                        echo '  <priority>0.8</priority>' . "\n";
                        echo '</url>' . "\n";
                }
        }

        // Programs
        if (!empty($options['enable_programs'])) {
                $programs = get_posts(array(
                        'post_type' => 'program',
                        'posts_per_page' => -1,
                        'exclude' => $exclude_ids,
                        'post_status' => 'publish'
                ));
                foreach ($programs as $program) {
                        echo '<url>' . "\n";
                        echo '  <loc>' . esc_url(user_trailingslashit(get_permalink($program->ID))) . '</loc>' . "\n";
                        echo '  <lastmod>' . get_the_modified_date('c', $program->ID) . '</lastmod>' . "\n";
                        echo '  <changefreq>monthly</changefreq>' . "\n";
                        echo '  <priority>0.9</priority>' . "\n";
                        echo '</url>' . "\n";
                }
        }

        // Locations
        if (!empty($options['enable_locations'])) {
                $locations = get_posts(array(
                        'post_type' => 'location',
                        'posts_per_page' => -1,
                        'exclude' => $exclude_ids,
                        'post_status' => 'publish'
                ));
                foreach ($locations as $location) {
                        echo '<url>' . "\n";
                        echo '  <loc>' . esc_url(user_trailingslashit(get_permalink($location->ID))) . '</loc>' . "\n";
                        echo '  <lastmod>' . get_the_modified_date('c', $location->ID) . '</lastmod>' . "\n";
                        echo '  <changefreq>monthly</changefreq>' . "\n";
                        echo '  <priority>0.9</priority>' . "\n";
                        echo '</url>' . "\n";
                }
        }

        // Posts
        if (!empty($options['enable_posts'])) {
                $posts = get_posts(array(
                        'post_type' => 'post',
                        'posts_per_page' => 100, // Limit blog posts to recent 100
                        'exclude' => $exclude_ids,
                        'post_status' => 'publish'
                ));
                foreach ($posts as $post) {
                        echo '<url>' . "\n";
                        echo '  <loc>' . esc_url(user_trailingslashit(get_permalink($post->ID))) . '</loc>' . "\n";
                        echo '  <lastmod>' . get_the_modified_date('c', $post->ID) . '</lastmod>' . "\n";
                        echo '  <changefreq>weekly</changefreq>' . "\n";
                        echo '  <priority>0.7</priority>' . "\n";
                        echo '</url>' . "\n";
                }
        }

        // Custom URLs
        if (!empty($options['custom_urls'])) {
                $custom_urls = explode("\n", $options['custom_urls']);
                foreach ($custom_urls as $url) {
                        $url = trim($url);
                        if (!empty($url)) {
                                echo '<url>' . "\n";
                                // Ensure manual URLs also get slashes if they look like internal directories
                                $url = user_trailingslashit($url);
                                echo '  <loc>' . esc_url($url) . '</loc>' . "\n";
                                echo '  <changefreq>monthly</changefreq>' . "\n";
                                echo '  <priority>0.6</priority>' . "\n";
                                echo '</url>' . "\n";
                        }
                }
        }

        echo '</urlset>';
        exit;
}
// Disable Custom Sitemap Template Redirect
// add_action('template_redirect', 'chroma_custom_sitemap');

/**
 * Custom Robots.txt
 */
/**
 * Register Sitemap Rewrite Rules
 */
function chroma_register_sitemap_rewrites()
{
        add_rewrite_rule('^sitemap\.xml$', 'index.php?sitemap=xml', 'top');
}
// Disable Custom Sitemap Rewrite Rules in favor of Native
// add_action('init', 'chroma_register_sitemap_rewrites');

/**
 * Register Sitemap Query Var
 */
function chroma_register_sitemap_query_var($vars)
{
        $vars[] = 'sitemap';
        return $vars;
}
add_filter('query_vars', 'chroma_register_sitemap_query_var');

/**
 * Disable Default WP Sitemap - REMOVED to use Native Sitemap with filters
 */
// remove_action('init', 'wp_sitemaps_get_server');

/**
 * Configure Native WordPress Sitemap
 */
function chroma_sitemap_config()
{
        // 1. Post Types: Only allow specific types
        add_filter('wp_sitemaps_post_types', function ($post_types) {
                $allowed = array('post', 'page', 'location', 'program', 'city');
                foreach ($post_types as $pt => $obj) {
                        if (!in_array($pt, $allowed)) {
                                unset($post_types[$pt]);
                        }
                }
                return $post_types;
        });

        // 2. Taxonomies: Disable ALL taxonomies
        add_filter('wp_sitemaps_taxonomies', function ($taxonomies) {
                return array(); // Empty array removes all taxonomies
        });

        // 3. Users: Disable user sitemaps
        add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
                if ('users' === $name) {
                        return false;
                }
                return $provider;
        }, 10, 2);
}
add_action('init', 'chroma_sitemap_config');

/**
 * Custom Robots.txt
 */
function chroma_custom_robots_txt($output)
{
        $output .= 'Sitemap: ' . home_url('/sitemap.xml') . "\n";
        return $output;
}

/**
 * Add FAQPage Schema to City Pages (Hidden, matches visible FAQ content)
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

        // Questions and Answers from single-city.php
        // Q1
        $q1 = "Do you offer GA Lottery Pre-K in $city?";
        $a1 = "Yes! Our locations serving $city participate in the Georgia Lottery Pre-K program. It is tuition-free for all 4-year-olds living in Georgia.";

        // Q2
        $q2 = "Do you provide transportation from $city schools?";
        $a2 = "We provide safe bus transportation from most major elementary schools in the $county School District. Check the specific campus page for a full list.";

        // Q3
        $q3 = "What ages do you accept at your $city centers?";
        $a3 = "We serve children from 6 weeks old (<a href='" . chroma_get_page_link('infant-care') . "'>Infant Care</a>) up to 12 years old (<a href='" . chroma_get_page_link('after-school') . "'>After School</a>). We also offer a <a href='" . chroma_get_page_link('pre-k-prep') . "'>Pre-K Prep</a> option at select locations.";

        // Q4
        $q4 = "How do I enroll my child in $city?";
        $a4 = "The best way to start is by scheduling a tour at your preferred location. You can book online or call us directly. We'll walk you through the enrollment process and answer all your questions.";

        $faq_items = array(
                array('question' => $q1, 'answer' => $a1),
                array('question' => $q2, 'answer' => $a2),
                array('question' => $q3, 'answer' => $a3),
                array('question' => $q4, 'answer' => $a4),
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
// DISABLED - Moved to Chroma SEO Pro Plugin
// add_action('wp_head', 'chroma_city_faq_schema_output');

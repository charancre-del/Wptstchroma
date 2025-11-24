<?php
/**
 * SEO Engine: Schema, Sitemap, Robots.txt, Meta Tags
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Add Organization Schema to Homepage
 */
function chroma_organization_schema() {
        if ( ! is_front_page() ) {
                return;
        }

        $schema = array(
                '@context'    => 'https://schema.org',
                '@type'       => 'ChildCare',
                'name'        => get_bloginfo( 'name' ),
                'url'         => home_url(),
                'logo'        => chroma_get_global_setting( 'global_logo', '' ),
                'description' => chroma_global_seo_default_description(),
                'areaServed'  => array(
                        '@type' => 'City',
                        'name'  => 'Atlanta',
                ),
                'sameAs'      => array_filter( array(
                        chroma_global_facebook_url(),
                        chroma_global_instagram_url(),
                        chroma_global_linkedin_url(),
                ) ),
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}
add_action( 'wp_head', 'chroma_organization_schema' );

/**
 * Add LocalBusiness Schema to Location Pages
 */
function chroma_location_schema() {
        if ( ! is_singular( 'location' ) ) {
                return;
        }

        $location_id     = get_the_ID();
        $location_fields = chroma_get_location_fields( $location_id );

        $schema = array(
                '@context'    => 'https://schema.org',
                '@type'       => array( 'ChildCare', 'LocalBusiness' ),
                'name'        => get_the_title(),
                'description' => get_the_excerpt() ?: chroma_trimmed_excerpt( 30, $location_id ),
                'url'         => get_permalink(),
                'image'       => get_the_post_thumbnail_url( $location_id, 'full' ),
                'address'     => array(
                        '@type'           => 'PostalAddress',
                        'streetAddress'   => $location_fields['address'],
                        'addressLocality' => $location_fields['city'],
                        'addressRegion'   => $location_fields['state'],
                        'postalCode'      => $location_fields['zip'],
                ),
                'telephone'   => $location_fields['phone'],
                'email'       => $location_fields['email'],
        );

        if ( $location_fields['latitude'] && $location_fields['longitude'] ) {
                $schema['geo'] = array(
                        '@type'     => 'GeoCoordinates',
                        'latitude'  => $location_fields['latitude'],
                        'longitude' => $location_fields['longitude'],
                );
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}
add_action( 'wp_head', 'chroma_location_schema' );

/**
 * Add Service Schema to Program Pages
 */
function chroma_program_schema() {
        if ( ! is_singular( 'program' ) ) {
                return;
        }

        $program_id = get_the_ID();

        $schema = array(
                '@context'    => 'https://schema.org',
                '@type'       => 'Service',
                'name'        => get_the_title(),
                'description' => get_the_excerpt() ?: chroma_trimmed_excerpt( 30, $program_id ),
                'url'         => get_permalink(),
                'provider'    => array(
                        '@type' => 'Organization',
                        'name'  => get_bloginfo( 'name' ),
                ),
                'serviceType' => 'Early Childhood Education',
                'areaServed'  => 'Metro Atlanta',
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
}
add_action( 'wp_head', 'chroma_program_schema' );

/**
 * Open Graph Tags
 */
function chroma_og_tags() {
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( get_the_title() ) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";

        if ( has_post_thumbnail() ) {
                echo '<meta property="og:image" content="' . esc_url( get_the_post_thumbnail_url( null, 'full' ) ) . '" />' . "\n";
        }

        $description = get_the_excerpt() ?: chroma_global_seo_default_description();
        echo '<meta property="og:description" content="' . esc_attr( wp_strip_all_tags( $description ) ) . '" />' . "\n";
}
add_action( 'wp_head', 'chroma_og_tags', 5 );

/**
 * Hreflang Tags for EN/ES
 */
function chroma_hreflang_tags() {
        $post_id = get_the_ID();

        if ( ! $post_id ) {
                return;
        }

        $alternate_en = get_post_meta( $post_id, 'alternate_url_en', true );
        $alternate_es = get_post_meta( $post_id, 'alternate_url_es', true );

        if ( $alternate_en ) {
                echo '<link rel="alternate" hreflang="en" href="' . esc_url( $alternate_en ) . '" />' . "\n";
        }

        if ( $alternate_es ) {
                echo '<link rel="alternate" hreflang="es" href="' . esc_url( $alternate_es ) . '" />' . "\n";
        }
}
add_action( 'wp_head', 'chroma_hreflang_tags', 1 );

/**
 * Custom Sitemap.xml
 */
function chroma_custom_sitemap() {
        if ( ! ( isset( $_GET['sitemap'] ) && 'xml' === $_GET['sitemap'] ) ) {
                return;
        }

        header( 'Content-Type: application/xml; charset=utf-8' );

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage.
        echo '<url><loc>' . esc_url( home_url( '/' ) ) . '</loc><priority>1.0</priority></url>' . "\n";

        // Pages.
        $pages = get_posts(
                array(
                        'post_type'      => 'page',
                        'posts_per_page' => -1,
                )
        );
        foreach ( $pages as $page ) {
                echo '<url><loc>' . esc_url( get_permalink( $page->ID ) ) . '</loc><priority>0.8</priority></url>' . "\n";
        }

        // Programs.
        $programs = get_posts(
                array(
                        'post_type'      => 'program',
                        'posts_per_page' => -1,
                )
        );
        foreach ( $programs as $program ) {
                echo '<url><loc>' . esc_url( get_permalink( $program->ID ) ) . '</loc><priority>0.9</priority></url>' . "\n";
        }

        // Locations.
        $locations = get_posts(
                array(
                        'post_type'      => 'location',
                        'posts_per_page' => -1,
                )
        );
        foreach ( $locations as $location ) {
                echo '<url><loc>' . esc_url( get_permalink( $location->ID ) ) . '</loc><priority>0.9</priority></url>' . "\n";
        }

        echo '</urlset>';
        exit;
}
add_action( 'template_redirect', 'chroma_custom_sitemap' );

/**
 * Custom Robots.txt
 */
function chroma_custom_robots_txt( $output ) {
        $output .= 'Sitemap: ' . home_url( '/?sitemap=xml' ) . "\n";
        return $output;
}
add_filter( 'robots_txt', 'chroma_custom_robots_txt' );

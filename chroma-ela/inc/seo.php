<?php
/**
 * SEO and Schema.org Structured Data
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Schema.org Organization markup to homepage
 */
function chroma_homepage_schema() {
	if ( ! is_front_page() ) {
		return;
	}

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'ChildCare',
		'name'        => 'Chroma Early Learning Academy',
		'url'         => home_url(),
		'logo'        => get_field( 'logo_light', 'option' ),
		'description' => get_bloginfo( 'description' ),
		'areaServed'  => array(
			'@type' => 'City',
			'name'  => 'Atlanta',
		),
		'sameAs'      => array(
			get_field( 'facebook_url', 'option' ),
			get_field( 'instagram_url', 'option' ),
		),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>';
}
add_action( 'wp_head', 'chroma_homepage_schema' );

/**
 * Add Schema.org LocalBusiness markup to location pages
 */
function chroma_location_schema() {
	if ( ! is_singular( 'location' ) ) {
		return;
	}

	$location_id = get_the_ID();

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => array( 'ChildCare', 'LocalBusiness' ),
		'name'        => get_the_title(),
		'description' => get_the_excerpt(),
		'url'         => get_permalink(),
		'image'       => get_the_post_thumbnail_url( $location_id, 'full' ),
		'address'     => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => get_field( 'address', $location_id ),
			'addressLocality' => get_field( 'city', $location_id ),
			'addressRegion'   => get_field( 'state', $location_id ),
			'postalCode'      => get_field( 'zip', $location_id ),
		),
		'telephone'   => get_field( 'phone', $location_id ),
		'openingHours' => get_field( 'hours', $location_id ),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>';
}
add_action( 'wp_head', 'chroma_location_schema' );

/**
 * Add custom meta description
 */
function chroma_meta_description() {
	if ( is_front_page() ) {
		$description = get_field( 'meta_description', 'option' ) ?: get_bloginfo( 'description' );
	} elseif ( is_singular() ) {
		$description = get_the_excerpt() ?: wp_trim_words( get_the_content(), 30 );
	} else {
		$description = get_bloginfo( 'description' );
	}

	if ( $description ) {
		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $description ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'chroma_meta_description', 1 );

/**
 * Add Open Graph tags
 */
function chroma_og_tags() {
	echo '<meta property="og:type" content="website">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( get_the_title() ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";

	if ( has_post_thumbnail() ) {
		echo '<meta property="og:image" content="' . esc_url( get_the_post_thumbnail_url( null, 'full' ) ) . '">' . "\n";
	} elseif ( get_field( 'og_image', 'option' ) ) {
		echo '<meta property="og:image" content="' . esc_url( get_field( 'og_image', 'option' ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'chroma_og_tags', 5 );

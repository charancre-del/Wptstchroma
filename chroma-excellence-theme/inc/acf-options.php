<?php
/**
 * ACF Options Page and Global Helpers
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register ACF Options Page
 */
if ( function_exists( 'acf_add_options_page' ) ) {
	acf_add_options_page( array(
		'page_title' => __( 'Chroma Settings', 'chroma-excellence' ),
		'menu_title' => __( 'Chroma Settings', 'chroma-excellence' ),
		'menu_slug'  => 'chroma-settings',
		'capability' => 'edit_posts',
		'icon_url'   => 'dashicons-admin-settings',
		'position'   => 2,
	) );
}

/**
 * Global Phone Helper
 */
function chroma_global_phone() {
	return get_field( 'global_phone', 'option' ) ?: '';
}

/**
 * Global Email Helper
 */
function chroma_global_email() {
	return get_field( 'global_email', 'option' ) ?: '';
}

/**
 * Global Tour Email Helper
 */
function chroma_global_tour_email() {
	return get_field( 'global_tour_email', 'option' ) ?: chroma_global_email();
}

/**
 * Global Full Address Helper
 */
function chroma_global_full_address() {
	$address = get_field( 'global_address', 'option' );
	$city    = get_field( 'global_city', 'option' );
	$state   = get_field( 'global_state', 'option' );
	$zip     = get_field( 'global_zip', 'option' );

	if ( ! $address ) {
		return '';
	}

	return trim( sprintf(
		'%s, %s, %s %s',
		$address,
		$city ?: '',
		$state ?: 'GA',
		$zip ?: ''
	) );
}

/**
 * Global Facebook URL
 */
function chroma_global_facebook_url() {
	return get_field( 'global_facebook_url', 'option' ) ?: '';
}

/**
 * Global Instagram URL
 */
function chroma_global_instagram_url() {
	return get_field( 'global_instagram_url', 'option' ) ?: '';
}

/**
 * Global LinkedIn URL
 */
function chroma_global_linkedin_url() {
	return get_field( 'global_linkedin_url', 'option' ) ?: '';
}

/**
 * Global SEO Default Title
 */
function chroma_global_seo_default_title() {
	return get_field( 'global_seo_default_title', 'option' ) ?: get_bloginfo( 'name' );
}

/**
 * Global SEO Default Description
 */
function chroma_global_seo_default_description() {
	return get_field( 'global_seo_default_description', 'option' ) ?: get_bloginfo( 'description' );
}

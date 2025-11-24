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
        return chroma_get_option_value( 'global_phone', '' );
}

/**
 * Global Email Helper
 */
function chroma_global_email() {
        return chroma_get_option_value( 'global_email', '' );
}

/**
 * Global Tour Email Helper
 */
function chroma_global_tour_email() {
        return chroma_get_option_value( 'global_tour_email', chroma_global_email() );
}

/**
 * Global Full Address Helper
 */
function chroma_global_full_address() {
        $address = chroma_get_option_value( 'global_address' );
        $city    = chroma_get_option_value( 'global_city' );
        $state   = chroma_get_option_value( 'global_state', 'GA' );
        $zip     = chroma_get_option_value( 'global_zip' );

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
        return chroma_get_option_value( 'global_facebook_url', '' );
}

/**
 * Global Instagram URL
 */
function chroma_global_instagram_url() {
        return chroma_get_option_value( 'global_instagram_url', '' );
}

/**
 * Global LinkedIn URL
 */
function chroma_global_linkedin_url() {
        return chroma_get_option_value( 'global_linkedin_url', '' );
}

/**
 * Global SEO Default Title
 */
function chroma_global_seo_default_title() {
        return chroma_get_option_value( 'global_seo_default_title', get_bloginfo( 'name' ) );
}

/**
 * Global SEO Default Description
 */
function chroma_global_seo_default_description() {
        return chroma_get_option_value( 'global_seo_default_description', get_bloginfo( 'description' ) );
}

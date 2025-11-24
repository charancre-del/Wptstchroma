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
 * Global configuration pulled from wp_options/theme_mods to avoid ACF reliance.
 */
function chroma_get_global_settings() {
	static $settings = null;

	if ( null !== $settings ) {
		return $settings;
	}

	$defaults = array(
		'phone'                   => '',
		'email'                   => '',
		'tour_email'              => '',
		'address'                 => '',
		'city'                    => '',
		'state'                   => 'GA',
		'zip'                     => '',
		'facebook_url'            => '',
		'instagram_url'           => '',
		'linkedin_url'            => '',
		'seo_default_title'       => '',
		'seo_default_description' => '',
		'logo_id'                 => 0,
		'logo_url'                => '',
	);

	$option_settings = get_option( 'chroma_global_settings', array() );

	// Allow overriding with Theme Mods if set individually.
	$theme_mod_overrides = array(
		'phone'         => get_theme_mod( 'chroma_global_phone' ),
		'email'         => get_theme_mod( 'chroma_global_email' ),
		'tour_email'    => get_theme_mod( 'chroma_global_tour_email' ),
		'address'       => get_theme_mod( 'chroma_global_address' ),
		'city'          => get_theme_mod( 'chroma_global_city' ),
		'state'         => get_theme_mod( 'chroma_global_state' ),
		'zip'           => get_theme_mod( 'chroma_global_zip' ),
		'facebook_url'  => get_theme_mod( 'chroma_global_facebook_url' ),
		'instagram_url' => get_theme_mod( 'chroma_global_instagram_url' ),
		'linkedin_url'  => get_theme_mod( 'chroma_global_linkedin_url' ),
		'logo_id'       => get_theme_mod( 'chroma_global_logo_id' ),
		'logo_url'      => get_theme_mod( 'chroma_global_logo_url' ),
	);

	$settings = wp_parse_args( array_filter( $theme_mod_overrides ), $option_settings );
	$settings = wp_parse_args( $settings, $defaults );

	/**
	 * Filters global theme settings.
	 *
	 * @param array $settings Theme configuration settings.
	 */
	$settings = apply_filters( 'chroma_global_settings', $settings );

	return $settings;
}

/**
 * Global Phone Helper
 */
function chroma_global_phone() {
	$settings = chroma_get_global_settings();
	return $settings['phone'] ?: '';
}

/**
 * Global Email Helper
 */
function chroma_global_email() {
	$settings = chroma_get_global_settings();
	return $settings['email'] ?: '';
}

/**
 * Global Tour Email Helper
 */
function chroma_global_tour_email() {
	$settings = chroma_get_global_settings();
	return $settings['tour_email'] ?: chroma_global_email();
}

/**
 * Global Full Address Helper
 */
function chroma_global_full_address() {
	$settings = chroma_get_global_settings();
	$address  = $settings['address'];
	$city     = $settings['city'];
	$state    = $settings['state'];
	$zip      = $settings['zip'];

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
	$settings = chroma_get_global_settings();
	return $settings['facebook_url'] ?: '';
}

/**
 * Global Instagram URL
 */
function chroma_global_instagram_url() {
	$settings = chroma_get_global_settings();
	return $settings['instagram_url'] ?: '';
}

/**
 * Global LinkedIn URL
 */
function chroma_global_linkedin_url() {
	$settings = chroma_get_global_settings();
	return $settings['linkedin_url'] ?: '';
}

/**
 * Global SEO Default Title
 */
function chroma_global_seo_default_title() {
	$settings = chroma_get_global_settings();
	return $settings['seo_default_title'] ?: get_bloginfo( 'name' );
}

/**
 * Global SEO Default Description
 */
function chroma_global_seo_default_description() {
	$settings = chroma_get_global_settings();
	return $settings['seo_default_description'] ?: get_bloginfo( 'description' );
}

/**
 * Global logo helper using Theme Mod or stored settings.
 */
function chroma_global_logo_url() {
	$settings = chroma_get_global_settings();

	if ( ! empty( $settings['logo_url'] ) ) {
		return $settings['logo_url'];
	}

	$logo_id = absint( $settings['logo_id'] );
	if ( $logo_id ) {
		$logo = wp_get_attachment_image_url( $logo_id, 'full' );
		if ( $logo ) {
			return $logo;
		}
	}

	$custom_logo_id = get_theme_mod( 'custom_logo' );
	return $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'full' ) : '';
}

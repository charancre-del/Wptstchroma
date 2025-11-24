<?php
/**
 * Legacy options helpers (works without ACF)
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Global settings helper
 */
function chroma_get_global_setting( $key, $default = '' ) {
        $defaults = array(
                'global_phone'                  => '',
                'global_email'                  => '',
                'global_tour_email'             => '',
                'global_address'                => '',
                'global_city'                   => '',
                'global_state'                  => 'GA',
                'global_zip'                    => '',
                'global_facebook_url'           => '',
                'global_instagram_url'          => '',
                'global_linkedin_url'           => '',
                'global_seo_default_title'      => get_bloginfo( 'name' ),
                'global_seo_default_description' => get_bloginfo( 'description' ),
                'global_logo'                   => '',
        );

        $settings = get_option( 'chroma_global_settings', array() );
        $value    = $settings[ $key ] ?? get_option( $key, $default );

        if ( '' === $value && isset( $defaults[ $key ] ) ) {
                $value = $defaults[ $key ];
        }

        return apply_filters( 'chroma_global_setting', $value, $key, $settings );
}

/**
 * Global Phone Helper
 */
function chroma_global_phone() {
return chroma_get_global_setting( 'global_phone', '' );
}

/**
 * Global Email Helper
 */
function chroma_global_email() {
return chroma_get_global_setting( 'global_email', '' );
}

/**
 * Global Tour Email Helper
 */
function chroma_global_tour_email() {
return chroma_get_global_setting( 'global_tour_email', chroma_global_email() );
}

/**
 * Global Full Address Helper
 */
function chroma_global_full_address() {
$address = chroma_get_global_setting( 'global_address', '' );
$city    = chroma_get_global_setting( 'global_city', '' );
$state   = chroma_get_global_setting( 'global_state', 'GA' );
$zip     = chroma_get_global_setting( 'global_zip', '' );

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
return chroma_get_global_setting( 'global_facebook_url', '' );
}

/**
 * Global Instagram URL
 */
function chroma_global_instagram_url() {
return chroma_get_global_setting( 'global_instagram_url', '' );
}

/**
 * Global LinkedIn URL
 */
function chroma_global_linkedin_url() {
return chroma_get_global_setting( 'global_linkedin_url', '' );
}

/**
 * Global SEO Default Title
 */
function chroma_global_seo_default_title() {
return chroma_get_global_setting( 'global_seo_default_title', get_bloginfo( 'name' ) );
}

/**
 * Global SEO Default Description
 */
function chroma_global_seo_default_description() {
return chroma_get_global_setting( 'global_seo_default_description', get_bloginfo( 'description' ) );
}

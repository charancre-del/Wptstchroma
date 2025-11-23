<?php
/**
 * Advanced Custom Fields Configuration
 *
 * @package Chroma_ELA
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
		'page_title' => 'Chroma Settings',
		'menu_title' => 'Chroma Settings',
		'menu_slug'  => 'chroma-settings',
		'capability' => 'edit_posts',
		'icon_url'   => 'dashicons-admin-settings',
		'position'   => 2,
	) );
}

/**
 * Add ACF JSON save point
 * This saves ACF field groups as JSON for version control
 */
function chroma_acf_json_save_point( $path ) {
	return CHROMA_THEME_DIR . '/acf-json';
}
add_filter( 'acf/settings/save_json', 'chroma_acf_json_save_point' );

/**
 * Add ACF JSON load point
 */
function chroma_acf_json_load_point( $paths ) {
	unset( $paths[0] );
	$paths[] = CHROMA_THEME_DIR . '/acf-json';
	return $paths;
}
add_filter( 'acf/settings/load_json', 'chroma_acf_json_load_point' );

/**
 * Hide ACF menu for non-admins
 */
function chroma_hide_acf_menu() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	return true;
}
add_filter( 'acf/settings/show_admin', 'chroma_hide_acf_menu' );

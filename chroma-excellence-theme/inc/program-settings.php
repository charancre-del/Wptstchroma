<?php
/**
 * Program settings and helpers.
 *
 * @package Chroma_Excellence
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * Default slug for the Program archive.
 */
function chroma_program_base_slug_default() {
	return 'programs';
}

/**
 * Sanitize a program base slug value.
 */
function chroma_sanitize_program_base_slug( $slug ) {
	$slug = sanitize_title( $slug );

	return $slug ?: chroma_program_base_slug_default();
}

/**
 * Retrieve the Program archive slug.
 */
function chroma_get_program_base_slug() {
	$slug = get_option( 'chroma_program_base_slug', '' );

	return chroma_sanitize_program_base_slug( $slug );
}

/**
 * Retrieve the Program archive URL.
 */
function chroma_get_program_archive_url() {
	return home_url( '/' . chroma_get_program_base_slug() );
}

/**
 * Register Customizer controls for the Program archive slug.
 */
function chroma_program_settings_customize_register( WP_Customize_Manager $wp_customize ) {
	$wp_customize->add_section(
		'chroma_program_settings',
		array(
			'title'       => __( 'Programs', 'chroma-excellence' ),
			'description' => __( 'Control the URL slug for the Program archive and permalinks.', 'chroma-excellence' ),
			'priority'    => 131,
		)
	);

	$wp_customize->add_setting(
		'chroma_program_base_slug',
		array(
			'type'              => 'option',
			'default'           => chroma_program_base_slug_default(),
			'sanitize_callback' => 'chroma_sanitize_program_base_slug',
		)
	);

	$wp_customize->add_control(
		'chroma_program_base_slug',
		array(
			'label'       => __( 'Program base slug', 'chroma-excellence' ),
			'description' => __( 'Used for the Programs archive URL and individual program permalinks.', 'chroma-excellence' ),
			'section'     => 'chroma_program_settings',
			'type'        => 'text',
		)
	);
}
add_action( 'customize_register', 'chroma_program_settings_customize_register' );

/**
 * Flush rewrites when the Program base slug changes.
 */
function chroma_maybe_flush_rewrite_on_program_slug_change( $option, $old_value = '', $value = '' ) {
	if ( 'chroma_program_base_slug' !== $option ) {
		return;
	}

	$previous = chroma_sanitize_program_base_slug( $old_value );
	$new      = chroma_sanitize_program_base_slug( $value ?: $old_value );

	if ( $previous === $new ) {
		return;
	}

	flush_rewrite_rules();
}
add_action( 'updated_option', 'chroma_maybe_flush_rewrite_on_program_slug_change', 10, 3 );
add_action( 'added_option', 'chroma_maybe_flush_rewrite_on_program_slug_change', 10, 2 );
/**
 * Ensure rewrites are refreshed on theme activation.
 */
function chroma_flush_rewrite_on_activation() {
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'chroma_flush_rewrite_on_activation' );

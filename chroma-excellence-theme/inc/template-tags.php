<?php
/**
 * Template Helper Functions
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trimmed Excerpt
 */
function chroma_trimmed_excerpt( $length = 20, $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$excerpt = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : get_the_content( null, false, $post_id );
	$excerpt = wp_strip_all_tags( $excerpt );
	$words   = explode( ' ', $excerpt );

	if ( count( $words ) > $length ) {
		$excerpt = implode( ' ', array_slice( $words, 0, $length ) ) . '...';
	}

	return $excerpt;
}

/**
 * Eyebrow Badge
 */
function chroma_eyebrow( $text, $color = 'blue' ) {
	$color_class = 'text-chroma-' . $color;
	echo '<span class="' . esc_attr( $color_class ) . ' font-bold tracking-[0.2em] text-[11px] uppercase mb-3 block">' . esc_html( $text ) . '</span>';
}

/**
 * Archive Pagination
 */
function chroma_archive_pagination() {
	the_posts_pagination( array(
		'mid_size'  => 2,
		'prev_text' => __( '← Previous', 'chroma-excellence' ),
		'next_text' => __( 'Next →', 'chroma-excellence' ),
		'class'     => 'flex items-center justify-center gap-2 mt-12',
	) );
}

/**
 * Location Address Line
 */
function chroma_location_address_line( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$address = get_field( 'location_address', $post_id );

	return $address ?: '';
}

/**
 * Location City State
 */
function chroma_location_city_state( $post_id = null ) {
	$post_id = $post_id ?: get_the_ID();
	$city    = get_field( 'location_city', $post_id );
	$state   = get_field( 'location_state', $post_id ) ?: 'GA';

	if ( ! $city ) {
		return '';
	}

	return $city . ', ' . $state;
}

/**
 * Badge Helper
 */
function chroma_badge( $text, $color = 'blue' ) {
	$bg_class = 'bg-chroma-' . $color . '/10';
	$text_class = 'text-chroma-' . $color;

	echo '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ' . esc_attr( $bg_class . ' ' . $text_class ) . '">';
	echo esc_html( $text );
	echo '</span>';
}

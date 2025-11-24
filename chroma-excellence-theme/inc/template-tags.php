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
 * Safe meta getter with default fallback.
 */
function chroma_get_meta_value( $post_id, $key, $default = '' ) {
        $value = get_post_meta( $post_id, $key, true );

        return ( '' === $value || null === $value ) ? $default : $value;
}

/**
 * Safe option getter for ACF-style option keys.
 */
function chroma_get_option_value( $key, $default = '' ) {
        $option_key = 'options_' . $key;
        $value      = get_option( $option_key, null );

        if ( null === $value || '' === $value ) {
                return $default;
        }

        return $value;
}

/**
 * Get core location meta in one call.
 */
function chroma_get_location_meta( $post_id = null ) {
        $post_id = $post_id ?: get_the_ID();

        return array(
                'address'    => chroma_get_meta_value( $post_id, 'location_address' ),
                'city'       => chroma_get_meta_value( $post_id, 'location_city' ),
                'state'      => chroma_get_meta_value( $post_id, 'location_state', 'GA' ),
                'zip'        => chroma_get_meta_value( $post_id, 'location_zip' ),
                'phone'      => chroma_get_meta_value( $post_id, 'location_phone' ),
                'email'      => chroma_get_meta_value( $post_id, 'location_email' ),
                'latitude'   => chroma_get_meta_value( $post_id, 'location_latitude' ),
                'longitude'  => chroma_get_meta_value( $post_id, 'location_longitude' ),
                'capacity'   => chroma_get_meta_value( $post_id, 'location_capacity' ),
                'enrollment' => chroma_get_meta_value( $post_id, 'location_enrollment' ),
        );
}

/**
 * Get program meta in one call.
 */
function chroma_get_program_meta( $post_id = null ) {
        $post_id = $post_id ?: get_the_ID();

        return array(
                'age_range'          => chroma_get_meta_value( $post_id, 'program_age_range' ),
                'short_description'  => chroma_get_meta_value( $post_id, 'program_short_description' ),
                'icon_class'         => chroma_get_meta_value( $post_id, 'program_icon_class', 'fas fa-child' ),
                'color'              => chroma_get_meta_value( $post_id, 'program_color', 'chroma-teal' ),
                'locations'          => chroma_get_meta_value( $post_id, 'program_locations', array() ),
        );
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
 * Safe meta accessor
 */
function chroma_get_meta_value( $post_id, $key, $default = '' ) {
    $value = get_post_meta( $post_id, $key, true );

    if ( '' === $value || null === $value ) {
        return $default;
    }

    return $value;
}

/**
 * Location meta bundle
 */
function chroma_get_location_fields( $post_id = null ) {
    $post_id = $post_id ?: get_the_ID();

    return array(
        'address'   => chroma_get_meta_value( $post_id, 'location_address', '' ),
        'city'      => chroma_get_meta_value( $post_id, 'location_city', '' ),
        'state'     => chroma_get_meta_value( $post_id, 'location_state', 'GA' ),
        'zip'       => chroma_get_meta_value( $post_id, 'location_zip', '' ),
        'phone'     => chroma_get_meta_value( $post_id, 'location_phone', '' ),
        'email'     => chroma_get_meta_value( $post_id, 'location_email', '' ),
        'latitude'  => chroma_get_meta_value( $post_id, 'location_latitude', '' ),
        'longitude' => chroma_get_meta_value( $post_id, 'location_longitude', '' ),
    );
}

/**
 * Program meta bundle
 */
function chroma_get_program_fields( $post_id = null ) {
    $post_id = $post_id ?: get_the_ID();

    return array(
        'age_range' => chroma_get_meta_value( $post_id, 'program_age_range', '' ),
        'excerpt'   => chroma_get_meta_value( $post_id, 'program_short_description', '' ),
        'icon'      => chroma_get_meta_value( $post_id, 'program_icon_class', 'fas fa-child' ),
        'color'     => chroma_get_meta_value( $post_id, 'program_color', 'chroma-teal' ),
    );
}

/**
 * Program color class mapping
 */
function chroma_program_color_classes( $color_key ) {
    $map = array(
        'chroma-teal'   => array(
            'gradient_from' => 'from-chroma-teal/10',
            'gradient_to'   => 'to-chroma-teal/5',
            'text'          => 'text-chroma-teal',
            'button'        => 'bg-chroma-teal',
        ),
        'chroma-red'    => array(
            'gradient_from' => 'from-chroma-red/10',
            'gradient_to'   => 'to-chroma-red/5',
            'text'          => 'text-chroma-red',
            'button'        => 'bg-chroma-red',
        ),
        'chroma-yellow' => array(
            'gradient_from' => 'from-chroma-yellow/10',
            'gradient_to'   => 'to-chroma-yellow/5',
            'text'          => 'text-chroma-yellow',
            'button'        => 'bg-chroma-yellow',
        ),
        'chroma-blue'   => array(
            'gradient_from' => 'from-chroma-blue/10',
            'gradient_to'   => 'to-chroma-blue/5',
            'text'          => 'text-chroma-blue',
            'button'        => 'bg-chroma-blue',
        ),
        'chroma-green'  => array(
            'gradient_from' => 'from-chroma-green/10',
            'gradient_to'   => 'to-chroma-green/5',
            'text'          => 'text-chroma-green',
            'button'        => 'bg-chroma-green',
        ),
    );

    return $map[ $color_key ] ?? $map['chroma-teal'];
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
    $fields  = chroma_get_location_fields( $post_id );
    $address = $fields['address'];

    return $address ?: '';
}

/**
 * Location City State
 */
function chroma_location_city_state( $post_id = null ) {
    $fields = chroma_get_location_fields( $post_id );
    $city   = $fields['city'];
    $state  = $fields['state'];

    if ( ! $city ) {
        return '';
    }

    return $city . ', ' . $state;
}

/**
 * Badge Helper
 */
function chroma_badge( $text, $color = 'blue' ) {
    $bg_class   = 'bg-chroma-' . $color . '/10';
    $text_class = 'text-chroma-' . $color;

    echo '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ' . esc_attr( $bg_class . ' ' . $text_class ) . '">';
    echo esc_html( $text );
    echo '</span>';
}

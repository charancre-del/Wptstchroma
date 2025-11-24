<?php
/**
 * Custom Post Type: Locations
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Location CPT
 */
function chroma_register_location_cpt() {
	$labels = array(
		'name'          => _x( 'Locations', 'Post Type General Name', 'chroma-excellence' ),
		'singular_name' => _x( 'Location', 'Post Type Singular Name', 'chroma-excellence' ),
		'menu_name'     => __( 'Locations', 'chroma-excellence' ),
		'all_items'     => __( 'All Locations', 'chroma-excellence' ),
		'add_new_item'  => __( 'Add New Location', 'chroma-excellence' ),
		'edit_item'     => __( 'Edit Location', 'chroma-excellence' ),
		'view_item'     => __( 'View Location', 'chroma-excellence' ),
	);

	$args = array(
		'label'         => __( 'Location', 'chroma-excellence' ),
		'labels'        => $labels,
		'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'public'        => true,
		'menu_position' => 21,
		'menu_icon'     => 'dashicons-location',
		'has_archive'   => 'locations',
		'rewrite'       => array( 'slug' => 'locations' ),
		'show_in_rest'  => true,
	);

	register_post_type( 'location', $args );
}
add_action( 'init', 'chroma_register_location_cpt', 0 );

/**
 * Add admin columns
 */
function chroma_location_admin_columns( $columns ) {
	$new_columns = array();
	$new_columns['cb'] = $columns['cb'];
	$new_columns['title'] = $columns['title'];
	$new_columns['city'] = __( 'City', 'chroma-excellence' );
	$new_columns['state'] = __( 'State', 'chroma-excellence' );
	$new_columns['phone'] = __( 'Phone', 'chroma-excellence' );
	$new_columns['capacity'] = __( 'Capacity', 'chroma-excellence' );
	$new_columns['date'] = $columns['date'];

	return $new_columns;
}
add_filter( 'manage_location_posts_columns', 'chroma_location_admin_columns' );

/**
 * Populate admin columns
 */
function chroma_location_admin_column_content( $column, $post_id ) {
	switch ( $column ) {
                case 'city':
                        echo esc_html( get_post_meta( $post_id, 'location_city', true ) ?: '—' );
                        break;
                case 'state':
                        echo esc_html( get_post_meta( $post_id, 'location_state', true ) ?: '—' );
                        break;
                case 'phone':
                        echo esc_html( get_post_meta( $post_id, 'location_phone', true ) ?: '—' );
                        break;
                case 'capacity':
                        $capacity = get_post_meta( $post_id, 'location_capacity', true );
                        $enrollment = get_post_meta( $post_id, 'location_enrollment', true );
                        if ( $capacity ) {
                                echo esc_html( $enrollment . ' / ' . $capacity );
                        } else {
				echo '—';
			}
			break;
	}
}
add_action( 'manage_location_posts_custom_column', 'chroma_location_admin_column_content', 10, 2 );

/**
 * Make columns sortable
 */
function chroma_location_sortable_columns( $columns ) {
	$columns['city'] = 'city';
	$columns['state'] = 'state';
	return $columns;
}
add_filter( 'manage_edit-location_sortable_columns', 'chroma_location_sortable_columns' );

/**
 * Custom title placeholder
 */
function chroma_location_title_placeholder( $title ) {
	$screen = get_current_screen();
	if ( 'location' === $screen->post_type ) {
		$title = __( 'e.g., Johns Creek Campus', 'chroma-excellence' );
	}
	return $title;
}
add_filter( 'enter_title_here', 'chroma_location_title_placeholder' );

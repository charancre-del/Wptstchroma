<?php
/**
 * Custom Post Type: Locations
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Location Custom Post Type
 */
function chroma_register_location_cpt() {
	$labels = array(
		'name'                  => _x( 'Locations', 'Post Type General Name', 'chroma-ela' ),
		'singular_name'         => _x( 'Location', 'Post Type Singular Name', 'chroma-ela' ),
		'menu_name'             => __( 'Locations', 'chroma-ela' ),
		'all_items'             => __( 'All Locations', 'chroma-ela' ),
		'add_new_item'          => __( 'Add New Location', 'chroma-ela' ),
		'add_new'               => __( 'Add New', 'chroma-ela' ),
		'edit_item'             => __( 'Edit Location', 'chroma-ela' ),
		'update_item'           => __( 'Update Location', 'chroma-ela' ),
		'view_item'             => __( 'View Location', 'chroma-ela' ),
		'search_items'          => __( 'Search Locations', 'chroma-ela' ),
		'not_found'             => __( 'Not found', 'chroma-ela' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'chroma-ela' ),
	);

	$args = array(
		'label'                 => __( 'Location', 'chroma-ela' ),
		'description'           => __( 'Chroma Location Campuses', 'chroma-ela' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 21,
		'menu_icon'             => 'dashicons-location',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => 'locations',
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
		'rewrite'               => array( 'slug' => 'location' ),
	);

	register_post_type( 'location', $args );
}
add_action( 'init', 'chroma_register_location_cpt', 0 );

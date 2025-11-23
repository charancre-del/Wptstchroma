<?php
/**
 * Custom Post Type: Programs
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Program Custom Post Type
 */
function chroma_register_program_cpt() {
	$labels = array(
		'name'                  => _x( 'Programs', 'Post Type General Name', 'chroma-ela' ),
		'singular_name'         => _x( 'Program', 'Post Type Singular Name', 'chroma-ela' ),
		'menu_name'             => __( 'Programs', 'chroma-ela' ),
		'all_items'             => __( 'All Programs', 'chroma-ela' ),
		'add_new_item'          => __( 'Add New Program', 'chroma-ela' ),
		'add_new'               => __( 'Add New', 'chroma-ela' ),
		'edit_item'             => __( 'Edit Program', 'chroma-ela' ),
		'update_item'           => __( 'Update Program', 'chroma-ela' ),
		'view_item'             => __( 'View Program', 'chroma-ela' ),
		'search_items'          => __( 'Search Programs', 'chroma-ela' ),
		'not_found'             => __( 'Not found', 'chroma-ela' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'chroma-ela' ),
	);

	$args = array(
		'label'                 => __( 'Program', 'chroma-ela' ),
		'description'           => __( 'Chroma Early Learning Programs', 'chroma-ela' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 20,
		'menu_icon'             => 'dashicons-welcome-learn-more',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => 'programs',
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
		'rewrite'               => array( 'slug' => 'program' ),
	);

	register_post_type( 'program', $args );
}
add_action( 'init', 'chroma_register_program_cpt', 0 );

<?php
/**
 * Custom Post Type: Team Members
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Team Member Custom Post Type
 */
function chroma_register_team_cpt() {
	$labels = array(
		'name'                  => _x( 'Team Members', 'Post Type General Name', 'chroma-ela' ),
		'singular_name'         => _x( 'Team Member', 'Post Type Singular Name', 'chroma-ela' ),
		'menu_name'             => __( 'Team', 'chroma-ela' ),
		'all_items'             => __( 'All Team Members', 'chroma-ela' ),
		'add_new_item'          => __( 'Add New Team Member', 'chroma-ela' ),
		'add_new'               => __( 'Add New', 'chroma-ela' ),
		'edit_item'             => __( 'Edit Team Member', 'chroma-ela' ),
		'update_item'           => __( 'Update Team Member', 'chroma-ela' ),
		'view_item'             => __( 'View Team Member', 'chroma-ela' ),
		'search_items'          => __( 'Search Team', 'chroma-ela' ),
		'not_found'             => __( 'Not found', 'chroma-ela' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'chroma-ela' ),
	);

	$args = array(
		'label'                 => __( 'Team Member', 'chroma-ela' ),
		'description'           => __( 'Chroma Team Members', 'chroma-ela' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 22,
		'menu_icon'             => 'dashicons-groups',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => 'team',
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true,
		'rewrite'               => array( 'slug' => 'team' ),
	);

	register_post_type( 'team', $args );
}
add_action( 'init', 'chroma_register_team_cpt', 0 );

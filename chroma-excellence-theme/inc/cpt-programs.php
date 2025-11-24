<?php
/**
 * Custom Post Type: Programs
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Program CPT
 */
function chroma_register_program_cpt() {
	$labels = array(
		'name'                  => _x( 'Programs', 'Post Type General Name', 'chroma-excellence' ),
		'singular_name'         => _x( 'Program', 'Post Type Singular Name', 'chroma-excellence' ),
		'menu_name'             => __( 'Programs', 'chroma-excellence' ),
		'all_items'             => __( 'All Programs', 'chroma-excellence' ),
		'add_new_item'          => __( 'Add New Program', 'chroma-excellence' ),
		'edit_item'             => __( 'Edit Program', 'chroma-excellence' ),
		'view_item'             => __( 'View Program', 'chroma-excellence' ),
	);

	$args = array(
		'label'                 => __( 'Program', 'chroma-excellence' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'public'                => true,
		'menu_position'         => 20,
		'menu_icon'             => 'dashicons-welcome-learn-more',
		'has_archive'           => 'programs',
		'rewrite'               => array( 'slug' => 'programs' ),
		'show_in_rest'          => true,
	);

	register_post_type( 'program', $args );
}
add_action( 'init', 'chroma_register_program_cpt', 0 );

/**
 * Add admin columns for Programs
 */
function chroma_program_admin_columns( $columns ) {
	$new_columns = array();
	$new_columns['cb'] = $columns['cb'];
	$new_columns['title'] = $columns['title'];
	$new_columns['age_range'] = __( 'Age Range', 'chroma-excellence' );
	$new_columns['locations'] = __( 'Locations', 'chroma-excellence' );
	$new_columns['date'] = $columns['date'];

	return $new_columns;
}
add_filter( 'manage_program_posts_columns', 'chroma_program_admin_columns' );

/**
 * Populate admin columns
 */
function chroma_program_admin_column_content( $column, $post_id ) {
switch ( $column ) {
case 'age_range':
$age_range = get_post_meta( $post_id, 'program_age_range', true );
echo $age_range ? esc_html( $age_range ) : '—';
break;

case 'locations':
$locations = get_post_meta( $post_id, 'program_locations', true );
			if ( $locations ) {
				$count = count( $locations );
				echo esc_html( $count . ' location' . ( $count > 1 ? 's' : '' ) );
			} else {
				echo '—';
			}
			break;
	}
}
add_action( 'manage_program_posts_custom_column', 'chroma_program_admin_column_content', 10, 2 );

/**
 * Custom title placeholder
 */
function chroma_program_title_placeholder( $title ) {
	$screen = get_current_screen();
	if ( 'program' === $screen->post_type ) {
		$title = __( 'e.g., Infant Care', 'chroma-excellence' );
	}
	return $title;
}
add_filter( 'enter_title_here', 'chroma_program_title_placeholder' );

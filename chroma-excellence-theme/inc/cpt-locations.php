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
 * Register Location taxonomy (counties/regions)
 */
function chroma_register_location_taxonomy() {
        $labels = array(
                'name'              => _x( 'Location Regions', 'taxonomy general name', 'chroma-excellence' ),
                'singular_name'     => _x( 'Location Region', 'taxonomy singular name', 'chroma-excellence' ),
                'search_items'      => __( 'Search Location Regions', 'chroma-excellence' ),
                'all_items'         => __( 'All Location Regions', 'chroma-excellence' ),
                'parent_item'       => __( 'Parent Region', 'chroma-excellence' ),
                'parent_item_colon' => __( 'Parent Region:', 'chroma-excellence' ),
                'edit_item'         => __( 'Edit Region', 'chroma-excellence' ),
                'update_item'       => __( 'Update Region', 'chroma-excellence' ),
                'add_new_item'      => __( 'Add New Region', 'chroma-excellence' ),
                'new_item_name'     => __( 'New Region Name', 'chroma-excellence' ),
                'menu_name'         => __( 'Location Regions', 'chroma-excellence' ),
        );

        register_taxonomy(
                'location_region',
                array( 'location' ),
                array(
                        'hierarchical'      => true,
                        'labels'            => $labels,
                        'show_ui'           => true,
                        'show_admin_column' => true,
                        'show_in_rest'      => true,
                        'query_var'         => true,
                        'rewrite'           => array( 'slug' => 'location-region' ),
                        'default_term'      => array(
                                'name' => __( 'Uncategorized Locations', 'chroma-excellence' ),
                                'slug' => 'uncategorized-locations',
                        ),
                )
        );
}
add_action( 'init', 'chroma_register_location_taxonomy', 1 );

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

/**
 * Add custom fields to location_region taxonomy
 */
function chroma_location_region_add_form_fields() {
	?>
	<div class="form-field">
		<label for="region_color_bg"><?php _e( 'Background Color Class', 'chroma-excellence' ); ?></label>
		<input type="text" name="region_color_bg" id="region_color_bg" value="chroma-greenLight" placeholder="e.g., chroma-greenLight">
		<p class="description"><?php _e( 'Tailwind background color class (e.g., chroma-greenLight, chroma-redLight, chroma-blueLight, chroma-yellowLight)', 'chroma-excellence' ); ?></p>
	</div>
	<div class="form-field">
		<label for="region_color_text"><?php _e( 'Text Color Class', 'chroma-excellence' ); ?></label>
		<input type="text" name="region_color_text" id="region_color_text" value="chroma-green" placeholder="e.g., chroma-green">
		<p class="description"><?php _e( 'Tailwind text color class (e.g., chroma-green, chroma-red, chroma-blue, chroma-yellow)', 'chroma-excellence' ); ?></p>
	</div>
	<div class="form-field">
		<label for="region_color_border"><?php _e( 'Border Color Class', 'chroma-excellence' ); ?></label>
		<input type="text" name="region_color_border" id="region_color_border" value="chroma-green" placeholder="e.g., chroma-green">
		<p class="description"><?php _e( 'Tailwind border color class (e.g., chroma-green, chroma-red, chroma-blue, chroma-yellow)', 'chroma-excellence' ); ?></p>
	</div>
	<?php
}
add_action( 'location_region_add_form_fields', 'chroma_location_region_add_form_fields' );

/**
 * Add custom fields to location_region taxonomy edit form
 */
function chroma_location_region_edit_form_fields( $term ) {
	$color_bg = get_term_meta( $term->term_id, 'region_color_bg', true );
	$color_text = get_term_meta( $term->term_id, 'region_color_text', true );
	$color_border = get_term_meta( $term->term_id, 'region_color_border', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="region_color_bg"><?php _e( 'Background Color Class', 'chroma-excellence' ); ?></label></th>
		<td>
			<input type="text" name="region_color_bg" id="region_color_bg" value="<?php echo esc_attr( $color_bg ?: 'chroma-greenLight' ); ?>">
			<p class="description"><?php _e( 'Tailwind background color class (e.g., chroma-greenLight, chroma-redLight, chroma-blueLight, chroma-yellowLight)', 'chroma-excellence' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="region_color_text"><?php _e( 'Text Color Class', 'chroma-excellence' ); ?></label></th>
		<td>
			<input type="text" name="region_color_text" id="region_color_text" value="<?php echo esc_attr( $color_text ?: 'chroma-green' ); ?>">
			<p class="description"><?php _e( 'Tailwind text color class (e.g., chroma-green, chroma-red, chroma-blue, chroma-yellow)', 'chroma-excellence' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="region_color_border"><?php _e( 'Border Color Class', 'chroma-excellence' ); ?></label></th>
		<td>
			<input type="text" name="region_color_border" id="region_color_border" value="<?php echo esc_attr( $color_border ?: 'chroma-green' ); ?>">
			<p class="description"><?php _e( 'Tailwind border color class (e.g., chroma-green, chroma-red, chroma-blue, chroma-yellow)', 'chroma-excellence' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'location_region_edit_form_fields', 'chroma_location_region_edit_form_fields' );

/**
 * Save location_region taxonomy custom fields
 */
function chroma_save_location_region_meta( $term_id ) {
	if ( isset( $_POST['region_color_bg'] ) ) {
		update_term_meta( $term_id, 'region_color_bg', sanitize_text_field( $_POST['region_color_bg'] ) );
	}
	if ( isset( $_POST['region_color_text'] ) ) {
		update_term_meta( $term_id, 'region_color_text', sanitize_text_field( $_POST['region_color_text'] ) );
	}
	if ( isset( $_POST['region_color_border'] ) ) {
		update_term_meta( $term_id, 'region_color_border', sanitize_text_field( $_POST['region_color_border'] ) );
	}
}
add_action( 'created_location_region', 'chroma_save_location_region_meta' );
add_action( 'edited_location_region', 'chroma_save_location_region_meta' );

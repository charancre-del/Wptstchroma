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

/**
 * Add meta box for location custom fields
 */
function chroma_location_custom_fields_meta_box() {
	add_meta_box(
		'chroma-location-custom-fields',
		__( 'Location Details', 'chroma-excellence' ),
		'chroma_render_location_custom_fields_meta_box',
		'location',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'chroma_location_custom_fields_meta_box' );

/**
 * Render location custom fields meta box
 */
function chroma_render_location_custom_fields_meta_box( $post ) {
	wp_nonce_field( 'chroma_location_meta_nonce', 'chroma_location_meta_nonce_field' );

	// Get existing values
	$hero_subtitle     = get_post_meta( $post->ID, 'location_hero_subtitle', true );
	$tagline          = get_post_meta( $post->ID, 'location_tagline', true );
	$description      = get_post_meta( $post->ID, 'location_description', true );
	$google_rating    = get_post_meta( $post->ID, 'location_google_rating', true );
	$hours            = get_post_meta( $post->ID, 'location_hours', true );
	$ages_served      = get_post_meta( $post->ID, 'location_ages_served', true );
	$director_name    = get_post_meta( $post->ID, 'location_director_name', true );
	$director_bio     = get_post_meta( $post->ID, 'location_director_bio', true );
	$director_photo   = get_post_meta( $post->ID, 'location_director_photo', true );
	?>
	<style>
		.chroma-meta-field { margin-bottom: 20px; }
		.chroma-meta-field label { display: block; font-weight: 600; margin-bottom: 5px; }
		.chroma-meta-field input[type="text"],
		.chroma-meta-field textarea { width: 100%; }
		.chroma-meta-field small { display: block; margin-top: 5px; color: #666; font-style: italic; }
		.chroma-meta-section { border-top: 1px solid #ddd; padding-top: 20px; margin-top: 20px; }
		.chroma-meta-section h4 { margin-top: 0; margin-bottom: 15px; font-size: 14px; font-weight: 600; text-transform: uppercase; color: #555; }
	</style>

	<div class="chroma-meta-section">
		<h4><?php _e( 'Hero Section', 'chroma-excellence' ); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_hero_subtitle"><?php _e( 'Hero Subtitle', 'chroma-excellence' ); ?></label>
			<input type="text" id="location_hero_subtitle" name="location_hero_subtitle" value="<?php echo esc_attr( $hero_subtitle ); ?>" placeholder="e.g., Now Enrolling: Pre-K & Toddlers" />
			<small><?php _e( 'Small badge text shown above the location name', 'chroma-excellence' ); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_tagline"><?php _e( 'Tagline', 'chroma-excellence' ); ?></label>
			<input type="text" id="location_tagline" name="location_tagline" value="<?php echo esc_attr( $tagline ); ?>" placeholder="e.g., Lawrenceville's home for brilliant beginnings." />
			<small><?php _e( 'Main tagline for this location (last 2 words will be italicized)', 'chroma-excellence' ); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_description"><?php _e( 'Description', 'chroma-excellence' ); ?></label>
			<textarea id="location_description" name="location_description" rows="3" placeholder="Short description of this location..."><?php echo esc_textarea( $description ); ?></textarea>
			<small><?php _e( 'Brief description shown in hero section', 'chroma-excellence' ); ?></small>
		</div>
	</div>

	<div class="chroma-meta-section">
		<h4><?php _e( 'Location Stats', 'chroma-excellence' ); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_ages_served"><?php _e( 'Ages Served', 'chroma-excellence' ); ?></label>
			<input type="text" id="location_ages_served" name="location_ages_served" value="<?php echo esc_attr( $ages_served ); ?>" placeholder="e.g., 6w - 12y" />
			<small><?php _e( 'Age range served at this location', 'chroma-excellence' ); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_google_rating"><?php _e( 'Google Rating', 'chroma-excellence' ); ?></label>
			<input type="text" id="location_google_rating" name="location_google_rating" value="<?php echo esc_attr( $google_rating ); ?>" placeholder="e.g., 4.9" />
			<small><?php _e( 'Google rating for this location (e.g., 4.9)', 'chroma-excellence' ); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_hours"><?php _e( 'Hours', 'chroma-excellence' ); ?></label>
			<input type="text" id="location_hours" name="location_hours" value="<?php echo esc_attr( $hours ); ?>" placeholder="e.g., 7am - 6pm" />
			<small><?php _e( 'Operating hours (Mon-Fri)', 'chroma-excellence' ); ?></small>
		</div>
	</div>

	<div class="chroma-meta-section">
		<h4><?php _e( 'Campus Director', 'chroma-excellence' ); ?></h4>

		<div class="chroma-meta-field">
			<label for="location_director_name"><?php _e( 'Director Name', 'chroma-excellence' ); ?></label>
			<input type="text" id="location_director_name" name="location_director_name" value="<?php echo esc_attr( $director_name ); ?>" placeholder="e.g., Sarah Williams" />
			<small><?php _e( 'Name of the campus director (leave empty to hide director section)', 'chroma-excellence' ); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_director_bio"><?php _e( 'Director Bio', 'chroma-excellence' ); ?></label>
			<textarea id="location_director_bio" name="location_director_bio" rows="4" placeholder="Brief bio of the director..."><?php echo esc_textarea( $director_bio ); ?></textarea>
			<small><?php _e( 'Brief bio/welcome message from the director', 'chroma-excellence' ); ?></small>
		</div>

		<div class="chroma-meta-field">
			<label for="location_director_photo"><?php _e( 'Director Photo URL', 'chroma-excellence' ); ?></label>
			<input type="text" id="location_director_photo" name="location_director_photo" value="<?php echo esc_attr( $director_photo ); ?>" placeholder="https://..." />
			<small><?php _e( 'URL to director photo (optional)', 'chroma-excellence' ); ?></small>
		</div>
	</div>

	<div class="chroma-meta-section">
		<p><strong><?php _e( 'Note:', 'chroma-excellence' ); ?></strong> <?php _e( 'Basic location information (address, phone, email, etc.) can be edited in the main editor above. Use the "Featured Image" box to set the hero image for this location.', 'chroma-excellence' ); ?></p>
	</div>
	<?php
}

/**
 * Save location custom fields
 */
function chroma_save_location_custom_fields( $post_id ) {
	// Verify nonce
	if ( ! isset( $_POST['chroma_location_meta_nonce_field'] ) || ! wp_verify_nonce( wp_unslash( $_POST['chroma_location_meta_nonce_field'] ), 'chroma_location_meta_nonce' ) ) {
		return;
	}

	// Check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check permissions
	if ( isset( $_POST['post_type'] ) && 'location' === $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	// Save fields
	$fields = array(
		'location_hero_subtitle',
		'location_tagline',
		'location_description',
		'location_google_rating',
		'location_hours',
		'location_ages_served',
		'location_director_name',
		'location_director_bio',
		'location_director_photo',
	);

	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			$value = wp_unslash( $_POST[ $field ] );
			// Sanitize based on field type
			if ( in_array( $field, array( 'location_description', 'location_director_bio' ) ) ) {
				$value = sanitize_textarea_field( $value );
			} else {
				$value = sanitize_text_field( $value );
			}
			update_post_meta( $post_id, $field, $value );
		}
	}
}
add_action( 'save_post_location', 'chroma_save_location_custom_fields' );

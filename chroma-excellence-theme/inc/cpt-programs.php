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

	$program_slug = chroma_get_program_base_slug();

	$args = array(
		'label'                 => __( 'Program', 'chroma-excellence' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'public'                => true,
		'menu_position'         => 20,
		'menu_icon'             => 'dashicons-welcome-learn-more',
		'has_archive'           => $program_slug,
		'rewrite'               => array( 'slug' => $program_slug ),
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

/**
 * Register meta fields for Program anchors and SEO intro
 */
function chroma_register_program_meta() {
        $meta_args = array(
                'object_subtype'  => 'program',
                'type'            => 'string',
                'single'          => true,
                'show_in_rest'    => true,
                'auth_callback'   => function() {
                        return current_user_can( 'edit_posts' );
                },
        );

        register_post_meta(
                'program',
                'program_anchor_slug',
                array_merge(
                        $meta_args,
                        array(
                                'sanitize_callback' => 'sanitize_title',
                                'default'           => '',
                        )
                )
        );

        register_post_meta(
                'program',
                'program_seo_heading',
                array_merge(
                        $meta_args,
                        array(
                                'sanitize_callback' => 'sanitize_text_field',
                        )
                )
        );

        register_post_meta(
                'program',
                'program_seo_summary',
                array_merge(
                        $meta_args,
                        array(
                                'sanitize_callback' => 'sanitize_textarea_field',
                        )
                )
        );

        register_post_meta(
                'program',
                'program_seo_highlights',
                array_merge(
                        $meta_args,
                        array(
                                'sanitize_callback' => 'sanitize_textarea_field',
                        )
                )
        );

        register_post_meta(
                'program',
                'program_meta_title',
                array_merge(
                        $meta_args,
                        array(
                                'sanitize_callback' => 'sanitize_text_field',
                        )
                )
        );

        register_post_meta(
                'program',
                'program_meta_description',
                array_merge(
                        $meta_args,
                        array(
                                'sanitize_callback' => 'sanitize_textarea_field',
                        )
                )
        );

        register_post_meta(
                'program',
                'program_faq_items',
                array_merge(
                        $meta_args,
                        array(
                                'sanitize_callback' => 'sanitize_textarea_field',
                        )
                )
        );
}
add_action( 'init', 'chroma_register_program_meta' );

/**
 * Add meta box for anchor and SEO intro fields
 */
function chroma_program_meta_box() {
        add_meta_box(
                'chroma-program-anchor-seo',
                __( 'Program Anchor & SEO Intro', 'chroma-excellence' ),
                'chroma_program_meta_box_render',
                'program',
                'side',
                'default'
        );
}
add_action( 'add_meta_boxes', 'chroma_program_meta_box' );

/**
 * Render the meta box fields
 */
function chroma_program_meta_box_render( $post ) {
        wp_nonce_field( 'chroma_program_meta_nonce', 'chroma_program_meta_nonce_field' );

        $anchor     = get_post_meta( $post->ID, 'program_anchor_slug', true );
        $heading    = get_post_meta( $post->ID, 'program_seo_heading', true );
        $summary    = get_post_meta( $post->ID, 'program_seo_summary', true );
        $highlights = get_post_meta( $post->ID, 'program_seo_highlights', true );
        $meta_title = get_post_meta( $post->ID, 'program_meta_title', true );
        $meta_desc  = get_post_meta( $post->ID, 'program_meta_description', true );
        $faq_items  = get_post_meta( $post->ID, 'program_faq_items', true );
        ?>
        <p>
                <label for="program_anchor_slug" class="screen-reader-text"><?php esc_html_e( 'Program Anchor', 'chroma-excellence' ); ?></label>
                <input type="text" id="program_anchor_slug" name="program_anchor_slug" value="<?php echo esc_attr( $anchor ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g., infant', 'chroma-excellence' ); ?>" />
                <small><?php esc_html_e( 'Used for #anchors and homepage wizard links. Defaults to the slug.', 'chroma-excellence' ); ?></small>
        </p>
        <p>
                <label for="program_seo_heading" class="screen-reader-text"><?php esc_html_e( 'SEO Heading', 'chroma-excellence' ); ?></label>
                <input type="text" id="program_seo_heading" name="program_seo_heading" value="<?php echo esc_attr( $heading ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Program intro heading', 'chroma-excellence' ); ?>" />
        </p>
        <p>
                <label for="program_seo_summary" class="screen-reader-text"><?php esc_html_e( 'SEO Summary', 'chroma-excellence' ); ?></label>
                <textarea id="program_seo_summary" name="program_seo_summary" class="widefat" rows="3" placeholder="<?php esc_attr_e( 'Short overview that lives above the card', 'chroma-excellence' ); ?>"><?php echo esc_textarea( $summary ); ?></textarea>
        </p>
        <p>
                <label for="program_seo_highlights" class="screen-reader-text"><?php esc_html_e( 'SEO Highlights', 'chroma-excellence' ); ?></label>
                <textarea id="program_seo_highlights" name="program_seo_highlights" class="widefat" rows="4" placeholder="<?php esc_attr_e( "One bullet per line (e.g. ratios, curriculum)", 'chroma-excellence' ); ?>"><?php echo esc_textarea( $highlights ); ?></textarea>
        </p>
        <hr />
        <p>
                <label for="program_meta_title" class="screen-reader-text"><?php esc_html_e( 'Meta Title', 'chroma-excellence' ); ?></label>
                <input type="text" id="program_meta_title" name="program_meta_title" value="<?php echo esc_attr( $meta_title ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Custom title tag (optional)', 'chroma-excellence' ); ?>" />
                <small><?php esc_html_e( 'Used on the program detail for search visibility.', 'chroma-excellence' ); ?></small>
        </p>
        <p>
                <label for="program_meta_description" class="screen-reader-text"><?php esc_html_e( 'Meta Description', 'chroma-excellence' ); ?></label>
                <textarea id="program_meta_description" name="program_meta_description" class="widefat" rows="3" placeholder="<?php esc_attr_e( '1–2 sentence description for search snippets', 'chroma-excellence' ); ?>"><?php echo esc_textarea( $meta_desc ); ?></textarea>
        </p>
        <p>
                <label for="program_faq_items" class="screen-reader-text"><?php esc_html_e( 'FAQ Items', 'chroma-excellence' ); ?></label>
                <textarea id="program_faq_items" name="program_faq_items" class="widefat" rows="4" placeholder="<?php esc_attr_e( 'Question | Answer (one per line)', 'chroma-excellence' ); ?>"><?php echo esc_textarea( $faq_items ); ?></textarea>
                <small><?php esc_html_e( 'Populate FAQ schema and on-page Q&A.', 'chroma-excellence' ); ?></small>
        </p>
        <?php
}

/**
 * Save meta box fields
 */
function chroma_program_meta_box_save( $post_id ) {
        if ( ! isset( $_POST['chroma_program_meta_nonce_field'] ) || ! wp_verify_nonce( wp_unslash( $_POST['chroma_program_meta_nonce_field'] ), 'chroma_program_meta_nonce' ) ) {
                return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
        }

        if ( isset( $_POST['post_type'] ) && 'program' === $_POST['post_type'] ) {
                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                        return;
                }
        }

        $anchor     = isset( $_POST['program_anchor_slug'] ) ? sanitize_title( wp_unslash( $_POST['program_anchor_slug'] ) ) : '';
        $heading    = isset( $_POST['program_seo_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['program_seo_heading'] ) ) : '';
        $summary    = isset( $_POST['program_seo_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['program_seo_summary'] ) ) : '';
        $highlights = isset( $_POST['program_seo_highlights'] ) ? sanitize_textarea_field( wp_unslash( $_POST['program_seo_highlights'] ) ) : '';
        $meta_title = isset( $_POST['program_meta_title'] ) ? sanitize_text_field( wp_unslash( $_POST['program_meta_title'] ) ) : '';
        $meta_desc  = isset( $_POST['program_meta_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['program_meta_description'] ) ) : '';
        $faq_items  = isset( $_POST['program_faq_items'] ) ? sanitize_textarea_field( wp_unslash( $_POST['program_faq_items'] ) ) : '';

        update_post_meta( $post_id, 'program_anchor_slug', $anchor );
        update_post_meta( $post_id, 'program_seo_heading', $heading );
        update_post_meta( $post_id, 'program_seo_summary', $summary );
        update_post_meta( $post_id, 'program_seo_highlights', $highlights );
        update_post_meta( $post_id, 'program_meta_title', $meta_title );
        update_post_meta( $post_id, 'program_meta_description', $meta_desc );
        update_post_meta( $post_id, 'program_faq_items', $faq_items );
}
add_action( 'save_post', 'chroma_program_meta_box_save' );

/**
 * Add meta box for program locations
 */
function chroma_program_locations_meta_box() {
	add_meta_box(
		'chroma-program-locations',
		__( 'Available at Locations', 'chroma-excellence' ),
		'chroma_program_locations_meta_box_render',
		'program',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'chroma_program_locations_meta_box' );

/**
 * Render program locations meta box
 */
function chroma_program_locations_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_program_locations_nonce', 'chroma_program_locations_nonce_field' );

	// Get all locations
	$all_locations = get_posts( array(
		'post_type'      => 'location',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );

	// Get currently selected locations
	$selected_locations = get_post_meta( $post->ID, 'program_locations', true );
	if ( ! is_array( $selected_locations ) ) {
		$selected_locations = array();
	}
	?>
	<p><?php _e( 'Select the locations where this program is available:', 'chroma-excellence' ); ?></p>
	<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
		<?php if ( ! empty( $all_locations ) ) : ?>
			<?php foreach ( $all_locations as $location ) : ?>
				<label style="display: block; margin-bottom: 8px;">
					<input
						type="checkbox"
						name="program_locations[]"
						value="<?php echo esc_attr( $location->ID ); ?>"
						<?php checked( in_array( $location->ID, $selected_locations ) ); ?>
					/>
					<?php echo esc_html( $location->post_title ); ?>
				</label>
			<?php endforeach; ?>
		<?php else : ?>
			<p><?php _e( 'No locations found. Please add locations first.', 'chroma-excellence' ); ?></p>
		<?php endif; ?>
	</div>
	<p><small><?php _e( 'This program will only appear on selected location pages.', 'chroma-excellence' ); ?></small></p>
	<?php
}

/**
 * Save program locations
 */
function chroma_program_locations_meta_box_save( $post_id ) {
	// Verify nonce
	if ( ! isset( $_POST['chroma_program_locations_nonce_field'] ) || ! wp_verify_nonce( wp_unslash( $_POST['chroma_program_locations_nonce_field'] ), 'chroma_program_locations_nonce' ) ) {
		return;
	}

	// Check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check permissions
	if ( isset( $_POST['post_type'] ) && 'program' === $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	// Save selected locations
	$selected_locations = isset( $_POST['program_locations'] ) && is_array( $_POST['program_locations'] )
		? array_map( 'intval', $_POST['program_locations'] )
		: array();

	update_post_meta( $post_id, 'program_locations', $selected_locations );
}
add_action( 'save_post_program', 'chroma_program_locations_meta_box_save' );

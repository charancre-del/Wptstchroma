<?php
/**
 * Stories Page Meta Boxes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Stories Page Meta Box
 */
function chroma_stories_page_meta_box() {
	add_meta_box(
		'chroma-stories-featured',
		__( 'Featured Post', 'chroma-excellence' ),
		'chroma_stories_featured_meta_box_render',
		'page',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'chroma_stories_page_meta_box' );

/**
 * Render Featured Post Meta Box
 */
function chroma_stories_featured_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_stories_featured_meta', 'chroma_stories_featured_nonce' );

	$featured_post_id = get_post_meta( $post->ID, 'stories_featured_post', true );

	// Get all published posts
	$posts = get_posts( array(
		'post_type'      => 'post',
		'posts_per_page' => 50,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'post_status'    => 'publish',
	) );
	?>
	<p>
		<label for="stories_featured_post"><?php _e( 'Select Featured Post', 'chroma-excellence' ); ?></label>
		<select id="stories_featured_post" name="stories_featured_post" class="widefat">
			<option value=""><?php _e( 'None', 'chroma-excellence' ); ?></option>
			<?php foreach ( $posts as $post_item ) : ?>
				<option value="<?php echo esc_attr( $post_item->ID ); ?>" <?php selected( $featured_post_id, $post_item->ID ); ?>>
					<?php echo esc_html( $post_item->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<small><?php _e( 'This post will be displayed as the large featured post at the top of the Stories page.', 'chroma-excellence' ); ?></small>
	</p>
	<?php
}

/**
 * Save Stories Page Meta
 */
function chroma_save_stories_page_meta( $post_id ) {
	// Verify nonce
	if ( ! isset( $_POST['chroma_stories_featured_nonce'] ) || ! wp_verify_nonce( $_POST['chroma_stories_featured_nonce'], 'chroma_stories_featured_meta' ) ) {
		return;
	}

	// Check autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Save featured post ID
	if ( isset( $_POST['stories_featured_post'] ) ) {
		$featured_id = intval( $_POST['stories_featured_post'] );
		update_post_meta( $post_id, 'stories_featured_post', $featured_id );
	} else {
		delete_post_meta( $post_id, 'stories_featured_post' );
	}
}
add_action( 'save_post', 'chroma_save_stories_page_meta' );

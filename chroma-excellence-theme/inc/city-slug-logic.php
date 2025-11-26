<?php
/**
 * City Slug Logic and Suggestions
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate Suggested Slug for Location
 * Pattern: service-areas-{city}-{state}
 */
function chroma_suggest_location_slug( $post_id ) {
        $city  = get_post_meta( $post_id, 'location_city', true );
        $state = get_post_meta( $post_id, 'location_state', true ) ?: 'ga';

	if ( ! $city ) {
		return '';
	}

	$city_slug  = sanitize_title( $city );
	$state_slug = sanitize_title( $state );

	return 'service-areas-' . $city_slug . '-' . $state_slug;
}

/**
 * Add Metabox to Show Slug Suggestions
 */
function chroma_slug_suggestion_metabox() {
	add_meta_box(
		'chroma_slug_suggestion',
		__( 'SEO Slug Suggestion', 'chroma-excellence' ),
		'chroma_slug_suggestion_callback',
		'location',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'chroma_slug_suggestion_metabox' );

/**
 * Metabox Callback
 */
function chroma_slug_suggestion_callback( $post ) {
	$suggested_slug = chroma_suggest_location_slug( $post->ID );
	$current_slug   = $post->post_name;
	$full_url       = home_url( '/locations/' . $suggested_slug );

	?>
	<div class="chroma-slug-info">
		<p><strong><?php _e( 'Suggested Slug:', 'chroma-excellence' ); ?></strong></p>
		<p><code><?php echo esc_html( $suggested_slug ?: 'N/A' ); ?></code></p>

		<p class="mt-2"><strong><?php _e( 'Suggested Full URL:', 'chroma-excellence' ); ?></strong></p>
		<p><code><?php echo esc_html( $full_url ); ?></code></p>

		<p class="mt-2"><strong><?php _e( 'Current Slug:', 'chroma-excellence' ); ?></strong></p>
		<p><code><?php echo esc_html( $current_slug ); ?></code></p>

		<p class="mt-3"><em><?php _e( 'Update the permalink manually to preserve existing URLs when needed.', 'chroma-excellence' ); ?></em></p>
	</div>
	<?php
}

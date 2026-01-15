<?php
/**
 * Home Page Meta Boxes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Home Page Meta Boxes
 */
function chroma_home_page_meta_boxes() {
	$post_id = isset($_GET['post']) ? $_GET['post'] : (isset($_POST['post_ID']) ? $_POST['post_ID'] : 0);
	$front_page_id = get_option('page_on_front');

	// Only show on Front Page
	if ( (int)$post_id !== (int)$front_page_id ) {
		return;
	}

	add_meta_box(
		'chroma-home-hero',
		__( 'Home: Hero Section', 'chroma-excellence' ),
		'chroma_home_hero_meta_box_render',
		'page',
		'normal',
		'high'
	);

	add_meta_box(
		'chroma-home-prismpath',
		__( 'Home: Prismpath Section', 'chroma-excellence' ),
		'chroma_home_prismpath_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-home-locations',
		__( 'Home: Locations Preview', 'chroma-excellence' ),
		'chroma_home_locations_meta_box_render',
		'page',
		'normal',
		'default'
	);
	
	add_meta_box(
		'chroma-home-faq',
		__( 'Home: FAQ Section', 'chroma-excellence' ),
		'chroma_home_faq_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-home-json-data',
		__( 'Home: Complex Data (JSON)', 'chroma-excellence' ),
		'chroma_home_json_meta_box_render',
		'page',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'chroma_home_page_meta_boxes' );

/**
 * Hero Section Meta Box
 */
function chroma_home_hero_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_home_meta', 'chroma_home_meta_nonce' );

	$heading = get_post_meta( $post->ID, 'home_hero_heading', true );
	$subheading = get_post_meta( $post->ID, 'home_hero_subheading', true );
	$cta_label = get_post_meta( $post->ID, 'home_hero_cta_label', true );
	$secondary_label = get_post_meta( $post->ID, 'home_hero_secondary_label', true );
	?>
	<p class="description">Leaving these empty will default to the Customizer settings. Fill these out to override (e.g. for Spanish).</p>
	<table class="form-table">
		<tr>
			<th><label>Heading</label></th>
			<td>
				<input type="text" name="home_hero_heading" value="<?php echo esc_attr( $heading ); ?>" class="large-text" placeholder="English Override" />
				<br>
				<input type="text" name="_chroma_es_home_hero_heading" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_home_hero_heading', true ) ); ?>" class="large-text" placeholder="[ES] Heading" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label>Subheading</label></th>
			<td>
				<textarea name="home_hero_subheading" rows="3" class="large-text" placeholder="English Override"><?php echo esc_textarea( $subheading ); ?></textarea>
				<br>
				<textarea name="_chroma_es_home_hero_subheading" rows="3" class="large-text" placeholder="[ES] Subheading" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_home_hero_subheading', true ) ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label>CTA Label</label></th>
			<td>
				<input type="text" name="home_hero_cta_label" value="<?php echo esc_attr( $cta_label ); ?>" class="regular-text" placeholder="English Override" />
				<input type="text" name="_chroma_es_home_hero_cta_label" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_home_hero_cta_label', true ) ); ?>" class="regular-text" placeholder="[ES] CTA Label" />
			</td>
		</tr>
		<tr>
			<th><label>Secondary CTA Label</label></th>
			<td>
				<input type="text" name="home_hero_secondary_label" value="<?php echo esc_attr( $secondary_label ); ?>" class="regular-text" placeholder="English Override" />
				<input type="text" name="_chroma_es_home_hero_secondary_label" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_home_hero_secondary_label', true ) ); ?>" class="regular-text" placeholder="[ES] Secondary Label" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Prismpath Section Meta Box
 */
function chroma_home_prismpath_meta_box_render( $post ) {
	$eyebrow = get_post_meta( $post->ID, 'home_prismpath_eyebrow', true );
	$heading = get_post_meta( $post->ID, 'home_prismpath_heading', true );
	$subheading = get_post_meta( $post->ID, 'home_prismpath_subheading', true );
	$cta_label = get_post_meta( $post->ID, 'home_prismpath_cta_label', true );
	
	$readiness_heading = get_post_meta( $post->ID, 'home_prismpath_readiness_heading', true );
	$readiness_desc = get_post_meta( $post->ID, 'home_prismpath_readiness_desc', true );
	?>
	<table class="form-table">
		<tr>
			<th><label>Eyebrow</label></th>
			<td>
				<input type="text" name="home_prismpath_eyebrow" value="<?php echo esc_attr( $eyebrow ); ?>" class="large-text" placeholder="English Override" />
				<input type="text" name="_chroma_es_home_prismpath_eyebrow" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_home_prismpath_eyebrow', true ) ); ?>" class="large-text" placeholder="[ES] Eyebrow" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label>Heading</label></th>
			<td>
				<input type="text" name="home_prismpath_heading" value="<?php echo esc_attr( $heading ); ?>" class="large-text" placeholder="English Override" />
				<input type="text" name="_chroma_es_home_prismpath_heading" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_home_prismpath_heading', true ) ); ?>" class="large-text" placeholder="[ES] Heading" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label>Subheading</label></th>
			<td>
				<textarea name="home_prismpath_subheading" rows="3" class="large-text" placeholder="English Override"><?php echo esc_textarea( $subheading ); ?></textarea>
				<textarea name="_chroma_es_home_prismpath_subheading" rows="3" class="large-text" placeholder="[ES] Subheading" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_home_prismpath_subheading', true ) ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label>CTA Label</label></th>
			<td>
				<input type="text" name="home_prismpath_cta_label" value="<?php echo esc_attr( $cta_label ); ?>" class="regular-text" placeholder="English Override" />
				<input type="text" name="_chroma_es_home_prismpath_cta_label" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_home_prismpath_cta_label', true ) ); ?>" class="regular-text" placeholder="[ES] CTA Label" />
			</td>
		</tr>
		<tr><th colspan="2"><strong>Kindergarten Readiness Box</strong></th></tr>
		<tr>
			<th><label>Heading</label></th>
			<td>
				<input type="text" name="home_prismpath_readiness_heading" value="<?php echo esc_attr( $readiness_heading ); ?>" class="large-text" placeholder="English Override" />
				<input type="text" name="_chroma_es_home_prismpath_readiness_heading" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_home_prismpath_readiness_heading', true ) ); ?>" class="large-text" placeholder="[ES] Heading" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label>Description</label></th>
			<td>
				<textarea name="home_prismpath_readiness_desc" rows="3" class="large-text" placeholder="English Override"><?php echo esc_textarea( $readiness_desc ); ?></textarea>
				<textarea name="_chroma_es_home_prismpath_readiness_desc" rows="3" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_home_prismpath_readiness_desc', true ) ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Locations Preview Meta Box
 */
function chroma_home_locations_meta_box_render( $post ) {
	$heading = get_post_meta( $post->ID, 'home_locations_heading', true );
	$subheading = get_post_meta( $post->ID, 'home_locations_subheading', true );
	$cta_label = get_post_meta( $post->ID, 'home_locations_cta_label', true );
	?>
	<table class="form-table">
		<tr>
			<th><label>Heading</label></th>
			<td>
				<input type="text" name="home_locations_heading" value="<?php echo esc_attr( $heading ); ?>" class="large-text" placeholder="English Override" />
				<input type="text" name="_chroma_es_home_locations_heading" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_home_locations_heading', true ) ); ?>" class="large-text" placeholder="[ES] Heading" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label>Subheading</label></th>
			<td>
				<textarea name="home_locations_subheading" rows="3" class="large-text" placeholder="English Override"><?php echo esc_textarea( $subheading ); ?></textarea>
				<textarea name="_chroma_es_home_locations_subheading" rows="3" class="large-text" placeholder="[ES] Subheading" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_home_locations_subheading', true ) ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label>CTA Label</label></th>
			<td>
				<input type="text" name="home_locations_cta_label" value="<?php echo esc_attr( $cta_label ); ?>" class="regular-text" placeholder="English Override" />
				<input type="text" name="_chroma_es_home_locations_cta_label" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_home_locations_cta_label', true ) ); ?>" class="regular-text" placeholder="[ES] CTA Label" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * FAQ Section Meta Box
 */
function chroma_home_faq_meta_box_render( $post ) {
	$heading = get_post_meta( $post->ID, 'home_faq_heading', true );
	$subheading = get_post_meta( $post->ID, 'home_faq_subheading', true );
	?>
	<table class="form-table">
		<tr>
			<th><label>Heading</label></th>
			<td>
				<input type="text" name="home_faq_heading" value="<?php echo esc_attr( $heading ); ?>" class="large-text" placeholder="English Override" />
				<input type="text" name="_chroma_es_home_faq_heading" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_home_faq_heading', true ) ); ?>" class="large-text" placeholder="[ES] Heading" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label>Subheading</label></th>
			<td>
				<textarea name="home_faq_subheading" rows="3" class="large-text" placeholder="English Override"><?php echo esc_textarea( $subheading ); ?></textarea>
				<textarea name="_chroma_es_home_faq_subheading" rows="3" class="large-text" placeholder="[ES] Subheading" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_home_faq_subheading', true ) ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * JSON Data Meta Box
 */
function chroma_home_json_meta_box_render( $post ) {
	$stats_json = get_post_meta( $post->ID, 'home_stats_json', true );
	$cards_json = get_post_meta( $post->ID, 'home_prismpath_cards_json', true );
	$faq_items_json = get_post_meta( $post->ID, 'home_faq_items_json', true );
	?>
	<p class="description">Paste translated JSON here to override the default/customizer items.</p>
	<table class="form-table">
		<tr>
			<th><label>Stats JSON [ES]</label></th>
			<td>
				<textarea name="_chroma_es_home_stats_json" rows="5" class="large-text code" placeholder="[{&quot;value&quot;:&quot;...&quot;, &quot;label&quot;:&quot;...&quot;}]"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_home_stats_json', true ) ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label>Prismpath Cards JSON [ES]</label></th>
			<td>
				<textarea name="_chroma_es_home_prismpath_cards_json" rows="5" class="large-text code"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_home_prismpath_cards_json', true ) ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label>FAQ Items JSON [ES]</label></th>
			<td>
				<textarea name="_chroma_es_home_faq_items_json" rows="5" class="large-text code"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_home_faq_items_json', true ) ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save Meta Box Data
 */
function chroma_home_save_meta_box_data( $post_id ) {
	if ( ! isset( $_POST['chroma_home_meta_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['chroma_home_meta_nonce'], 'chroma_home_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}

	$fields = [
		'home_hero_heading', '_chroma_es_home_hero_heading',
		'home_hero_subheading', '_chroma_es_home_hero_subheading',
		'home_hero_cta_label', '_chroma_es_home_hero_cta_label',
		'home_hero_secondary_label', '_chroma_es_home_hero_secondary_label',
		'home_prismpath_eyebrow', '_chroma_es_home_prismpath_eyebrow',
		'home_prismpath_heading', '_chroma_es_home_prismpath_heading',
		'home_prismpath_subheading', '_chroma_es_home_prismpath_subheading',
		'home_prismpath_cta_label', '_chroma_es_home_prismpath_cta_label',
		'home_prismpath_readiness_heading', '_chroma_es_home_prismpath_readiness_heading',
		'home_prismpath_readiness_desc', '_chroma_es_home_prismpath_readiness_desc',
		'home_locations_heading', '_chroma_es_home_locations_heading',
		'home_locations_subheading', '_chroma_es_home_locations_subheading',
		'home_locations_cta_label', '_chroma_es_home_locations_cta_label',
		'home_faq_heading', '_chroma_es_home_faq_heading',
		'home_faq_subheading', '_chroma_es_home_faq_subheading',
		'_chroma_es_home_stats_json',
		'_chroma_es_home_prismpath_cards_json',
		'_chroma_es_home_faq_items_json'
	];

	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field ] ) ) {
			// For JSON fields, we might want to validate JSON, but for now just sanitizing as textarea
			// Use wp_kses_post or similar if HTML is allowed, otherwise sanitize_text_field/textarea_field
			if (strpos($field, '_json') !== false) {
				update_post_meta( $post_id, $field, wp_kses_post( $_POST[ $field ] ) );
			} else {
				update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
			}
		}
	}
}
add_action( 'save_post', 'chroma_home_save_meta_box_data' );

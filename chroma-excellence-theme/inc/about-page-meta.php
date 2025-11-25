<?php
/**
 * About Page Meta Boxes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register About Page Meta Boxes
 */
function chroma_about_page_meta_boxes() {
	$about_page_id = get_option( 'chroma_about_page_id' );

	if ( ! $about_page_id ) {
		// Try to find the About page by slug
		$about_page = get_page_by_path( 'about' );
		if ( $about_page ) {
			$about_page_id = $about_page->ID;
			update_option( 'chroma_about_page_id', $about_page_id );
		}
	}

	add_meta_box(
		'chroma-about-hero',
		__( 'Hero Section', 'chroma-excellence' ),
		'chroma_about_hero_meta_box_render',
		'page',
		'normal',
		'high'
	);

	add_meta_box(
		'chroma-about-mission',
		__( 'Mission Section', 'chroma-excellence' ),
		'chroma_about_mission_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-about-story',
		__( 'Story & Statistics Section', 'chroma-excellence' ),
		'chroma_about_story_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-about-educators',
		__( 'Educators Section', 'chroma-excellence' ),
		'chroma_about_educators_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-about-values',
		__( 'Core Values Section', 'chroma-excellence' ),
		'chroma_about_values_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-about-leadership',
		__( 'Leadership Section', 'chroma-excellence' ),
		'chroma_about_leadership_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-about-nutrition',
		__( 'Nutrition & Wellness Section', 'chroma-excellence' ),
		'chroma_about_nutrition_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-about-philanthropy',
		__( 'Philanthropy Section', 'chroma-excellence' ),
		'chroma_about_philanthropy_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-about-cta',
		__( 'CTA Section', 'chroma-excellence' ),
		'chroma_about_cta_meta_box_render',
		'page',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'chroma_about_page_meta_boxes' );

/**
 * Hero Section Meta Box
 */
function chroma_about_hero_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_about_hero_meta', 'chroma_about_hero_nonce' );

	$hero_title = get_post_meta( $post->ID, 'about_hero_title', true );
	$hero_description = get_post_meta( $post->ID, 'about_hero_description', true );
	$hero_stats_text = get_post_meta( $post->ID, 'about_hero_stats_text', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="about_hero_title">Hero Title</label></th>
			<td>
				<input type="text" id="about_hero_title" name="about_hero_title"
					   value="<?php echo esc_attr( $hero_title ); ?>"
					   class="large-text" placeholder="e.g., Excellence in Every Color" />
			</td>
		</tr>
		<tr>
			<th><label for="about_hero_description">Hero Description</label></th>
			<td>
				<textarea id="about_hero_description" name="about_hero_description"
						  rows="4" class="large-text"><?php echo esc_textarea( $hero_description ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="about_hero_stats_text">Stats Badge Text</label></th>
			<td>
				<input type="text" id="about_hero_stats_text" name="about_hero_stats_text"
					   value="<?php echo esc_attr( $hero_stats_text ); ?>"
					   class="large-text" placeholder="e.g., 12+ Locations | 500+ Families" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Mission Section Meta Box
 */
function chroma_about_mission_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_about_mission_meta', 'chroma_about_mission_nonce' );

	$mission_title = get_post_meta( $post->ID, 'about_mission_title', true );
	$mission_description = get_post_meta( $post->ID, 'about_mission_description', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="about_mission_title">Mission Title</label></th>
			<td>
				<input type="text" id="about_mission_title" name="about_mission_title"
					   value="<?php echo esc_attr( $mission_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_mission_description">Mission Description</label></th>
			<td>
				<textarea id="about_mission_description" name="about_mission_description"
						  rows="5" class="large-text"><?php echo esc_textarea( $mission_description ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Story Section Meta Box
 */
function chroma_about_story_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_about_story_meta', 'chroma_about_story_nonce' );

	$story_title = get_post_meta( $post->ID, 'about_story_title', true );
	$story_description = get_post_meta( $post->ID, 'about_story_description', true );

	$stat1_value = get_post_meta( $post->ID, 'about_stat1_value', true );
	$stat1_label = get_post_meta( $post->ID, 'about_stat1_label', true );
	$stat2_value = get_post_meta( $post->ID, 'about_stat2_value', true );
	$stat2_label = get_post_meta( $post->ID, 'about_stat2_label', true );
	$stat3_value = get_post_meta( $post->ID, 'about_stat3_value', true );
	$stat3_label = get_post_meta( $post->ID, 'about_stat3_label', true );
	$stat4_value = get_post_meta( $post->ID, 'about_stat4_value', true );
	$stat4_label = get_post_meta( $post->ID, 'about_stat4_label', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="about_story_title">Story Title</label></th>
			<td>
				<input type="text" id="about_story_title" name="about_story_title"
					   value="<?php echo esc_attr( $story_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_story_description">Story Description</label></th>
			<td>
				<textarea id="about_story_description" name="about_story_description"
						  rows="6" class="large-text"><?php echo esc_textarea( $story_description ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th colspan="2"><strong>Statistics</strong></th>
		</tr>
		<tr>
			<th><label for="about_stat1_value">Stat 1 Value</label></th>
			<td>
				<input type="text" id="about_stat1_value" name="about_stat1_value"
					   value="<?php echo esc_attr( $stat1_value ); ?>"
					   placeholder="e.g., 12+" />
				<input type="text" id="about_stat1_label" name="about_stat1_label"
					   value="<?php echo esc_attr( $stat1_label ); ?>"
					   placeholder="e.g., Locations" style="margin-left: 10px;" />
			</td>
		</tr>
		<tr>
			<th><label for="about_stat2_value">Stat 2 Value</label></th>
			<td>
				<input type="text" id="about_stat2_value" name="about_stat2_value"
					   value="<?php echo esc_attr( $stat2_value ); ?>"
					   placeholder="e.g., 500+" />
				<input type="text" id="about_stat2_label" name="about_stat2_label"
					   value="<?php echo esc_attr( $stat2_label ); ?>"
					   placeholder="e.g., Families Served" style="margin-left: 10px;" />
			</td>
		</tr>
		<tr>
			<th><label for="about_stat3_value">Stat 3 Value</label></th>
			<td>
				<input type="text" id="about_stat3_value" name="about_stat3_value"
					   value="<?php echo esc_attr( $stat3_value ); ?>"
					   placeholder="e.g., 15" />
				<input type="text" id="about_stat3_label" name="about_stat3_label"
					   value="<?php echo esc_attr( $stat3_label ); ?>"
					   placeholder="e.g., Years of Excellence" style="margin-left: 10px;" />
			</td>
		</tr>
		<tr>
			<th><label for="about_stat4_value">Stat 4 Value</label></th>
			<td>
				<input type="text" id="about_stat4_value" name="about_stat4_value"
					   value="<?php echo esc_attr( $stat4_value ); ?>"
					   placeholder="e.g., 100%" />
				<input type="text" id="about_stat4_label" name="about_stat4_label"
					   value="<?php echo esc_attr( $stat4_label ); ?>"
					   placeholder="e.g., Organic Meals" style="margin-left: 10px;" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Educators Section Meta Box
 */
function chroma_about_educators_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_about_educators_meta', 'chroma_about_educators_nonce' );

	$educators_title = get_post_meta( $post->ID, 'about_educators_title', true );
	$educator1_title = get_post_meta( $post->ID, 'about_educator1_title', true );
	$educator1_desc = get_post_meta( $post->ID, 'about_educator1_desc', true );
	$educator2_title = get_post_meta( $post->ID, 'about_educator2_title', true );
	$educator2_desc = get_post_meta( $post->ID, 'about_educator2_desc', true );
	$educator3_title = get_post_meta( $post->ID, 'about_educator3_title', true );
	$educator3_desc = get_post_meta( $post->ID, 'about_educator3_desc', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="about_educators_title">Section Title</label></th>
			<td>
				<input type="text" id="about_educators_title" name="about_educators_title"
					   value="<?php echo esc_attr( $educators_title ); ?>"
					   class="large-text" placeholder="e.g., Our Educators Difference" />
			</td>
		</tr>
		<tr>
			<th colspan="2"><strong>Educator Card 1</strong></th>
		</tr>
		<tr>
			<th><label for="about_educator1_title">Card 1 Title</label></th>
			<td>
				<input type="text" id="about_educator1_title" name="about_educator1_title"
					   value="<?php echo esc_attr( $educator1_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_educator1_desc">Card 1 Description</label></th>
			<td>
				<textarea id="about_educator1_desc" name="about_educator1_desc"
						  rows="3" class="large-text"><?php echo esc_textarea( $educator1_desc ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th colspan="2"><strong>Educator Card 2</strong></th>
		</tr>
		<tr>
			<th><label for="about_educator2_title">Card 2 Title</label></th>
			<td>
				<input type="text" id="about_educator2_title" name="about_educator2_title"
					   value="<?php echo esc_attr( $educator2_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_educator2_desc">Card 2 Description</label></th>
			<td>
				<textarea id="about_educator2_desc" name="about_educator2_desc"
						  rows="3" class="large-text"><?php echo esc_textarea( $educator2_desc ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th colspan="2"><strong>Educator Card 3</strong></th>
		</tr>
		<tr>
			<th><label for="about_educator3_title">Card 3 Title</label></th>
			<td>
				<input type="text" id="about_educator3_title" name="about_educator3_title"
					   value="<?php echo esc_attr( $educator3_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_educator3_desc">Card 3 Description</label></th>
			<td>
				<textarea id="about_educator3_desc" name="about_educator3_desc"
						  rows="3" class="large-text"><?php echo esc_textarea( $educator3_desc ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Core Values Section Meta Box
 */
function chroma_about_values_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_about_values_meta', 'chroma_about_values_nonce' );

	$values_title = get_post_meta( $post->ID, 'about_values_title', true );

	$value1_icon = get_post_meta( $post->ID, 'about_value1_icon', true );
	$value1_title = get_post_meta( $post->ID, 'about_value1_title', true );
	$value1_desc = get_post_meta( $post->ID, 'about_value1_desc', true );

	$value2_icon = get_post_meta( $post->ID, 'about_value2_icon', true );
	$value2_title = get_post_meta( $post->ID, 'about_value2_title', true );
	$value2_desc = get_post_meta( $post->ID, 'about_value2_desc', true );

	$value3_icon = get_post_meta( $post->ID, 'about_value3_icon', true );
	$value3_title = get_post_meta( $post->ID, 'about_value3_title', true );
	$value3_desc = get_post_meta( $post->ID, 'about_value3_desc', true );

	$value4_icon = get_post_meta( $post->ID, 'about_value4_icon', true );
	$value4_title = get_post_meta( $post->ID, 'about_value4_title', true );
	$value4_desc = get_post_meta( $post->ID, 'about_value4_desc', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="about_values_title">Section Title</label></th>
			<td>
				<input type="text" id="about_values_title" name="about_values_title"
					   value="<?php echo esc_attr( $values_title ); ?>"
					   class="large-text" placeholder="e.g., Our Four Pillars" />
			</td>
		</tr>
		<tr>
			<th colspan="2"><strong>Value 1</strong></th>
		</tr>
		<tr>
			<th><label for="about_value1_icon">Icon Class</label></th>
			<td>
				<input type="text" id="about_value1_icon" name="about_value1_icon"
					   value="<?php echo esc_attr( $value1_icon ); ?>"
					   placeholder="e.g., fa-solid fa-heart" />
				<p class="description">Font Awesome icon class (e.g., fa-solid fa-heart)</p>
			</td>
		</tr>
		<tr>
			<th><label for="about_value1_title">Title</label></th>
			<td>
				<input type="text" id="about_value1_title" name="about_value1_title"
					   value="<?php echo esc_attr( $value1_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_value1_desc">Description</label></th>
			<td>
				<textarea id="about_value1_desc" name="about_value1_desc"
						  rows="2" class="large-text"><?php echo esc_textarea( $value1_desc ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th colspan="2"><strong>Value 2</strong></th>
		</tr>
		<tr>
			<th><label for="about_value2_icon">Icon Class</label></th>
			<td>
				<input type="text" id="about_value2_icon" name="about_value2_icon"
					   value="<?php echo esc_attr( $value2_icon ); ?>"
					   placeholder="e.g., fa-solid fa-users" />
				<p class="description">Font Awesome icon class</p>
			</td>
		</tr>
		<tr>
			<th><label for="about_value2_title">Title</label></th>
			<td>
				<input type="text" id="about_value2_title" name="about_value2_title"
					   value="<?php echo esc_attr( $value2_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_value2_desc">Description</label></th>
			<td>
				<textarea id="about_value2_desc" name="about_value2_desc"
						  rows="2" class="large-text"><?php echo esc_textarea( $value2_desc ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th colspan="2"><strong>Value 3</strong></th>
		</tr>
		<tr>
			<th><label for="about_value3_icon">Icon Class</label></th>
			<td>
				<input type="text" id="about_value3_icon" name="about_value3_icon"
					   value="<?php echo esc_attr( $value3_icon ); ?>"
					   placeholder="e.g., fa-solid fa-leaf" />
				<p class="description">Font Awesome icon class</p>
			</td>
		</tr>
		<tr>
			<th><label for="about_value3_title">Title</label></th>
			<td>
				<input type="text" id="about_value3_title" name="about_value3_title"
					   value="<?php echo esc_attr( $value3_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_value3_desc">Description</label></th>
			<td>
				<textarea id="about_value3_desc" name="about_value3_desc"
						  rows="2" class="large-text"><?php echo esc_textarea( $value3_desc ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th colspan="2"><strong>Value 4</strong></th>
		</tr>
		<tr>
			<th><label for="about_value4_icon">Icon Class</label></th>
			<td>
				<input type="text" id="about_value4_icon" name="about_value4_icon"
					   value="<?php echo esc_attr( $value4_icon ); ?>"
					   placeholder="e.g., fa-solid fa-lightbulb" />
				<p class="description">Font Awesome icon class</p>
			</td>
		</tr>
		<tr>
			<th><label for="about_value4_title">Title</label></th>
			<td>
				<input type="text" id="about_value4_title" name="about_value4_title"
					   value="<?php echo esc_attr( $value4_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_value4_desc">Description</label></th>
			<td>
				<textarea id="about_value4_desc" name="about_value4_desc"
						  rows="2" class="large-text"><?php echo esc_textarea( $value4_desc ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Leadership Section Meta Box
 */
function chroma_about_leadership_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_about_leadership_meta', 'chroma_about_leadership_nonce' );

	$leadership_title = get_post_meta( $post->ID, 'about_leadership_title', true );
	$leadership_description = get_post_meta( $post->ID, 'about_leadership_description', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="about_leadership_title">Leadership Title</label></th>
			<td>
				<input type="text" id="about_leadership_title" name="about_leadership_title"
					   value="<?php echo esc_attr( $leadership_title ); ?>"
					   class="large-text" placeholder="e.g., Meet Our Leadership" />
			</td>
		</tr>
		<tr>
			<th><label for="about_leadership_description">Leadership Description</label></th>
			<td>
				<textarea id="about_leadership_description" name="about_leadership_description"
						  rows="4" class="large-text"><?php echo esc_textarea( $leadership_description ); ?></textarea>
				<p class="description">Team members are managed in the "Team Members" section and will display automatically.</p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Nutrition Section Meta Box
 */
function chroma_about_nutrition_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_about_nutrition_meta', 'chroma_about_nutrition_nonce' );

	$nutrition_title = get_post_meta( $post->ID, 'about_nutrition_title', true );
	$nutrition_description = get_post_meta( $post->ID, 'about_nutrition_description', true );
	$nutrition_image = get_post_meta( $post->ID, 'about_nutrition_image', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="about_nutrition_title">Nutrition Title</label></th>
			<td>
				<input type="text" id="about_nutrition_title" name="about_nutrition_title"
					   value="<?php echo esc_attr( $nutrition_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_nutrition_description">Nutrition Description</label></th>
			<td>
				<textarea id="about_nutrition_description" name="about_nutrition_description"
						  rows="5" class="large-text"><?php echo esc_textarea( $nutrition_description ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="about_nutrition_image">Nutrition Image</label></th>
			<td>
				<input type="text" id="about_nutrition_image" name="about_nutrition_image"
					   value="<?php echo esc_attr( $nutrition_image ); ?>"
					   class="large-text chroma-image-field" />
				<button type="button" class="button chroma-upload-button" data-field="about_nutrition_image">Select Image</button>
				<button type="button" class="button chroma-clear-button" data-field="about_nutrition_image">Clear</button>
				<div class="chroma-image-preview"></div>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Philanthropy Section Meta Box
 */
function chroma_about_philanthropy_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_about_philanthropy_meta', 'chroma_about_philanthropy_nonce' );

	$philanthropy_title = get_post_meta( $post->ID, 'about_philanthropy_title', true );
	$philanthropy_description = get_post_meta( $post->ID, 'about_philanthropy_description', true );
	$philanthropy_image = get_post_meta( $post->ID, 'about_philanthropy_image', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="about_philanthropy_title">Philanthropy Title</label></th>
			<td>
				<input type="text" id="about_philanthropy_title" name="about_philanthropy_title"
					   value="<?php echo esc_attr( $philanthropy_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_philanthropy_description">Philanthropy Description</label></th>
			<td>
				<textarea id="about_philanthropy_description" name="about_philanthropy_description"
						  rows="5" class="large-text"><?php echo esc_textarea( $philanthropy_description ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="about_philanthropy_image">Philanthropy Image</label></th>
			<td>
				<input type="text" id="about_philanthropy_image" name="about_philanthropy_image"
					   value="<?php echo esc_attr( $philanthropy_image ); ?>"
					   class="large-text chroma-image-field" />
				<button type="button" class="button chroma-upload-button" data-field="about_philanthropy_image">Select Image</button>
				<button type="button" class="button chroma-clear-button" data-field="about_philanthropy_image">Clear</button>
				<div class="chroma-image-preview"></div>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * CTA Section Meta Box
 */
function chroma_about_cta_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_about_cta_meta', 'chroma_about_cta_nonce' );

	$cta_title = get_post_meta( $post->ID, 'about_cta_title', true );
	$cta_description = get_post_meta( $post->ID, 'about_cta_description', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="about_cta_title">CTA Title</label></th>
			<td>
				<input type="text" id="about_cta_title" name="about_cta_title"
					   value="<?php echo esc_attr( $cta_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="about_cta_description">CTA Description</label></th>
			<td>
				<textarea id="about_cta_description" name="about_cta_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $cta_description ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save About Page Meta
 */
function chroma_save_about_page_meta( $post_id ) {
	// Check if this is the About page
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	// Define all meta fields
	$meta_boxes = array(
		'chroma_about_hero_nonce' => array(
			'about_hero_title'       => 'sanitize_text_field',
			'about_hero_description' => 'sanitize_textarea_field',
			'about_hero_stats_text'  => 'sanitize_text_field',
		),
		'chroma_about_mission_nonce' => array(
			'about_mission_title'       => 'sanitize_text_field',
			'about_mission_description' => 'sanitize_textarea_field',
		),
		'chroma_about_story_nonce' => array(
			'about_story_title'       => 'sanitize_text_field',
			'about_story_description' => 'sanitize_textarea_field',
			'about_stat1_value'       => 'sanitize_text_field',
			'about_stat1_label'       => 'sanitize_text_field',
			'about_stat2_value'       => 'sanitize_text_field',
			'about_stat2_label'       => 'sanitize_text_field',
			'about_stat3_value'       => 'sanitize_text_field',
			'about_stat3_label'       => 'sanitize_text_field',
			'about_stat4_value'       => 'sanitize_text_field',
			'about_stat4_label'       => 'sanitize_text_field',
		),
		'chroma_about_educators_nonce' => array(
			'about_educators_title' => 'sanitize_text_field',
			'about_educator1_title' => 'sanitize_text_field',
			'about_educator1_desc'  => 'sanitize_textarea_field',
			'about_educator2_title' => 'sanitize_text_field',
			'about_educator2_desc'  => 'sanitize_textarea_field',
			'about_educator3_title' => 'sanitize_text_field',
			'about_educator3_desc'  => 'sanitize_textarea_field',
		),
		'chroma_about_values_nonce' => array(
			'about_values_title' => 'sanitize_text_field',
			'about_value1_icon'  => 'sanitize_text_field',
			'about_value1_title' => 'sanitize_text_field',
			'about_value1_desc'  => 'sanitize_textarea_field',
			'about_value2_icon'  => 'sanitize_text_field',
			'about_value2_title' => 'sanitize_text_field',
			'about_value2_desc'  => 'sanitize_textarea_field',
			'about_value3_icon'  => 'sanitize_text_field',
			'about_value3_title' => 'sanitize_text_field',
			'about_value3_desc'  => 'sanitize_textarea_field',
			'about_value4_icon'  => 'sanitize_text_field',
			'about_value4_title' => 'sanitize_text_field',
			'about_value4_desc'  => 'sanitize_textarea_field',
		),
		'chroma_about_leadership_nonce' => array(
			'about_leadership_title'       => 'sanitize_text_field',
			'about_leadership_description' => 'sanitize_textarea_field',
		),
		'chroma_about_nutrition_nonce' => array(
			'about_nutrition_title'       => 'sanitize_text_field',
			'about_nutrition_description' => 'sanitize_textarea_field',
			'about_nutrition_image'       => 'esc_url_raw',
		),
		'chroma_about_philanthropy_nonce' => array(
			'about_philanthropy_title'       => 'sanitize_text_field',
			'about_philanthropy_description' => 'sanitize_textarea_field',
			'about_philanthropy_image'       => 'esc_url_raw',
		),
		'chroma_about_cta_nonce' => array(
			'about_cta_title'       => 'sanitize_text_field',
			'about_cta_description' => 'sanitize_textarea_field',
		),
	);

	// Process each meta box
	foreach ( $meta_boxes as $nonce_field => $fields ) {
		if ( ! isset( $_POST[ $nonce_field ] ) ) {
			continue;
		}

		$nonce_action = str_replace( '_nonce', '_meta', $nonce_field );
		if ( ! wp_verify_nonce( $_POST[ $nonce_field ], $nonce_action ) ) {
			continue;
		}

		// Save each field
		foreach ( $fields as $field_name => $sanitize_function ) {
			if ( isset( $_POST[ $field_name ] ) ) {
				$value = call_user_func( $sanitize_function, $_POST[ $field_name ] );
				update_post_meta( $post_id, $field_name, $value );
			}
		}
	}
}
add_action( 'save_post', 'chroma_save_about_page_meta' );

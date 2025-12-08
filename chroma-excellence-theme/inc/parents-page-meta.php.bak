<?php
/**
 * Parents Page Meta Boxes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Parents Page Meta Boxes
 */
function chroma_parents_page_meta_boxes() {
	add_meta_box(
		'chroma-parents-hero',
		__( 'Hero Section', 'chroma-excellence' ),
		'chroma_parents_hero_meta_box_render',
		'page',
		'normal',
		'high'
	);

	add_meta_box(
		'chroma-parents-resources',
		__( 'Parent Essentials (6 Resource Cards)', 'chroma-excellence' ),
		'chroma_parents_resources_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-parents-events',
		__( 'Events Section', 'chroma-excellence' ),
		'chroma_parents_events_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-parents-nutrition',
		__( 'Nutrition & Menus Section', 'chroma-excellence' ),
		'chroma_parents_nutrition_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-parents-safety',
		__( 'Safety Section', 'chroma-excellence' ),
		'chroma_parents_safety_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-parents-faq',
		__( 'FAQ Section', 'chroma-excellence' ),
		'chroma_parents_faq_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-parents-referral',
		__( 'Referral Banner', 'chroma-excellence' ),
		'chroma_parents_referral_meta_box_render',
		'page',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'chroma_parents_page_meta_boxes' );

/**
 * Hero Section Meta Box
 */
function chroma_parents_hero_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_parents_hero_meta', 'chroma_parents_hero_nonce' );

	$hero_badge       = get_post_meta( $post->ID, 'parents_hero_badge', true );
	$hero_title       = get_post_meta( $post->ID, 'parents_hero_title', true );
	$hero_description = get_post_meta( $post->ID, 'parents_hero_description', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="parents_hero_badge">Badge Text</label></th>
			<td>
				<input type="text" id="parents_hero_badge" name="parents_hero_badge"
					   value="<?php echo esc_attr( $hero_badge ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="parents_hero_title">Title</label></th>
			<td>
				<input type="text" id="parents_hero_title" name="parents_hero_title"
					   value="<?php echo esc_attr( $hero_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="parents_hero_description">Description</label></th>
			<td>
				<textarea id="parents_hero_description" name="parents_hero_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $hero_description ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Resources Section Meta Box
 */
function chroma_parents_resources_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_parents_resources_meta', 'chroma_parents_resources_nonce' );

	$essentials_title = get_post_meta( $post->ID, 'parents_essentials_title', true );

	$resources = array(
		'procare'    => array( 'label' => 'Procare Cloud', 'icon' => 'fa-solid fa-cloud' ),
		'tuition'    => array( 'label' => 'Tuition Portal', 'icon' => 'fa-solid fa-credit-card' ),
		'handbook'   => array( 'label' => 'Parent Handbook', 'icon' => 'fa-solid fa-book-open' ),
		'enrollment' => array( 'label' => 'Enrollment Agreement', 'icon' => 'fa-solid fa-file-signature' ),
		'prekga'     => array( 'label' => 'GA Pre-K Enrollment', 'icon' => 'fa-solid fa-graduation-cap' ),
		'waitlist'   => array( 'label' => 'Join Waitlist', 'icon' => 'fa-solid fa-clock' ),
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="parents_essentials_title">Section Title</label></th>
			<td>
				<input type="text" id="parents_essentials_title" name="parents_essentials_title"
					   value="<?php echo esc_attr( $essentials_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
	</table>

	<?php foreach ( $resources as $key => $resource ) : ?>
		<?php
		$icon  = get_post_meta( $post->ID, "parents_resource_{$key}_icon", true );
		$title = get_post_meta( $post->ID, "parents_resource_{$key}_title", true );
		$desc  = get_post_meta( $post->ID, "parents_resource_{$key}_desc", true );
		$url   = get_post_meta( $post->ID, "parents_resource_{$key}_url", true );
		?>
		<hr style="margin: 20px 0;" />
		<h4 style="margin: 15px 0;"><?php echo esc_html( $resource['label'] ); ?></h4>
		<table class="form-table">
			<tr>
				<th><label for="parents_resource_<?php echo esc_attr( $key ); ?>_icon">Icon</label></th>
				<td>
					<input type="text" id="parents_resource_<?php echo esc_attr( $key ); ?>_icon"
						   name="parents_resource_<?php echo esc_attr( $key ); ?>_icon"
						   value="<?php echo esc_attr( $icon ); ?>"
						   placeholder="<?php echo esc_attr( $resource['icon'] ); ?>" />
					<p class="description">FontAwesome class (e.g., <?php echo esc_html( $resource['icon'] ); ?>)</p>
				</td>
			</tr>
			<tr>
				<th><label for="parents_resource_<?php echo esc_attr( $key ); ?>_title">Title</label></th>
				<td>
					<input type="text" id="parents_resource_<?php echo esc_attr( $key ); ?>_title"
						   name="parents_resource_<?php echo esc_attr( $key ); ?>_title"
						   value="<?php echo esc_attr( $title ); ?>"
						   class="large-text" />
				</td>
			</tr>
			<tr>
				<th><label for="parents_resource_<?php echo esc_attr( $key ); ?>_desc">Description</label></th>
				<td>
					<textarea id="parents_resource_<?php echo esc_attr( $key ); ?>_desc"
							  name="parents_resource_<?php echo esc_attr( $key ); ?>_desc"
							  rows="2" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th><label for="parents_resource_<?php echo esc_attr( $key ); ?>_url">Link URL</label></th>
				<td>
					<input type="url" id="parents_resource_<?php echo esc_attr( $key ); ?>_url"
						   name="parents_resource_<?php echo esc_attr( $key ); ?>_url"
						   value="<?php echo esc_attr( $url ); ?>"
						   class="large-text" placeholder="https://" />
				</td>
			</tr>
		</table>
	<?php endforeach; ?>
	<?php
}

/**
 * Events Section Meta Box
 */
function chroma_parents_events_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_parents_events_meta', 'chroma_parents_events_nonce' );

	$events_badge       = get_post_meta( $post->ID, 'parents_events_badge', true );
	$events_title       = get_post_meta( $post->ID, 'parents_events_title', true );
	$events_description = get_post_meta( $post->ID, 'parents_events_description', true );
	$events_image       = get_post_meta( $post->ID, 'parents_events_image', true );

	$event_items = array(
		1 => 'Event 1 (Quarterly Family Events)',
		2 => 'Event 2 (Pre-K Graduation)',
		3 => 'Event 3 (Parent-Teacher Conferences)',
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="parents_events_badge">Badge</label></th>
			<td>
				<input type="text" id="parents_events_badge" name="parents_events_badge"
					   value="<?php echo esc_attr( $events_badge ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="parents_events_title">Title</label></th>
			<td>
				<input type="text" id="parents_events_title" name="parents_events_title"
					   value="<?php echo esc_attr( $events_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="parents_events_description">Description</label></th>
			<td>
				<textarea id="parents_events_description" name="parents_events_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $events_description ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="parents_events_image">Image URL</label></th>
			<td>
				<input type="text" id="parents_events_image" name="parents_events_image"
					   value="<?php echo esc_attr( $events_image ); ?>"
					   class="large-text chroma-image-field" />
				<button type="button" class="button chroma-upload-button" data-field="parents_events_image">Select Image</button>
				<button type="button" class="button chroma-clear-button" data-field="parents_events_image">Clear</button>
			</td>
		</tr>
	</table>

	<?php foreach ( $event_items as $num => $label ) : ?>
		<?php
		$icon  = get_post_meta( $post->ID, "parents_event{$num}_icon", true );
		$title = get_post_meta( $post->ID, "parents_event{$num}_title", true );
		$desc  = get_post_meta( $post->ID, "parents_event{$num}_desc", true );
		?>
		<hr style="margin: 20px 0;" />
		<h4 style="margin: 15px 0;"><?php echo esc_html( $label ); ?></h4>
		<table class="form-table">
			<tr>
				<th><label for="parents_event<?php echo $num; ?>_icon">Icon</label></th>
				<td>
					<input type="text" id="parents_event<?php echo $num; ?>_icon"
						   name="parents_event<?php echo $num; ?>_icon"
						   value="<?php echo esc_attr( $icon ); ?>" />
					<p class="description">FontAwesome class</p>
				</td>
			</tr>
			<tr>
				<th><label for="parents_event<?php echo $num; ?>_title">Title</label></th>
				<td>
					<input type="text" id="parents_event<?php echo $num; ?>_title"
						   name="parents_event<?php echo $num; ?>_title"
						   value="<?php echo esc_attr( $title ); ?>"
						   class="large-text" />
				</td>
			</tr>
			<tr>
				<th><label for="parents_event<?php echo $num; ?>_desc">Description</label></th>
				<td>
					<textarea id="parents_event<?php echo $num; ?>_desc"
							  name="parents_event<?php echo $num; ?>_desc"
							  rows="3" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
				</td>
			</tr>
		</table>
	<?php endforeach; ?>
	<?php
}

/**
 * Nutrition Section Meta Box
 */
function chroma_parents_nutrition_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_parents_nutrition_meta', 'chroma_parents_nutrition_nonce' );

	$nutrition_badge       = get_post_meta( $post->ID, 'parents_nutrition_badge', true );
	$nutrition_title       = get_post_meta( $post->ID, 'parents_nutrition_title', true );
	$nutrition_description = get_post_meta( $post->ID, 'parents_nutrition_description', true );
	$nutrition_image       = get_post_meta( $post->ID, 'parents_nutrition_image', true );

	$menu_items = array(
		1 => 'Menu 1 (Current Month Menu)',
		2 => 'Menu 2 (Infant Puree Menu)',
		3 => 'Menu 3 (Allergy Statement)',
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="parents_nutrition_badge">Badge</label></th>
			<td>
				<input type="text" id="parents_nutrition_badge" name="parents_nutrition_badge"
					   value="<?php echo esc_attr( $nutrition_badge ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="parents_nutrition_title">Title</label></th>
			<td>
				<input type="text" id="parents_nutrition_title" name="parents_nutrition_title"
					   value="<?php echo esc_attr( $nutrition_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="parents_nutrition_description">Description</label></th>
			<td>
				<textarea id="parents_nutrition_description" name="parents_nutrition_description"
						  rows="2" class="large-text"><?php echo esc_textarea( $nutrition_description ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="parents_nutrition_image">Image URL</label></th>
			<td>
				<input type="text" id="parents_nutrition_image" name="parents_nutrition_image"
					   value="<?php echo esc_attr( $nutrition_image ); ?>"
					   class="large-text chroma-image-field" />
				<button type="button" class="button chroma-upload-button" data-field="parents_nutrition_image">Select Image</button>
				<button type="button" class="button chroma-clear-button" data-field="parents_nutrition_image">Clear</button>
			</td>
		</tr>
	</table>

	<?php foreach ( $menu_items as $num => $label ) : ?>
		<?php
		$icon     = get_post_meta( $post->ID, "parents_menu{$num}_icon", true );
		$title    = get_post_meta( $post->ID, "parents_menu{$num}_title", true );
		$subtitle = get_post_meta( $post->ID, "parents_menu{$num}_subtitle", true );
		$url      = get_post_meta( $post->ID, "parents_menu{$num}_url", true );
		?>
		<hr style="margin: 20px 0;" />
		<h4 style="margin: 15px 0;"><?php echo esc_html( $label ); ?></h4>
		<table class="form-table">
			<tr>
				<th><label for="parents_menu<?php echo $num; ?>_icon">Icon</label></th>
				<td>
					<input type="text" id="parents_menu<?php echo $num; ?>_icon"
						   name="parents_menu<?php echo $num; ?>_icon"
						   value="<?php echo esc_attr( $icon ); ?>" />
					<p class="description">FontAwesome class</p>
				</td>
			</tr>
			<tr>
				<th><label for="parents_menu<?php echo $num; ?>_title">Title</label></th>
				<td>
					<input type="text" id="parents_menu<?php echo $num; ?>_title"
						   name="parents_menu<?php echo $num; ?>_title"
						   value="<?php echo esc_attr( $title ); ?>"
						   class="large-text" />
				</td>
			</tr>
			<tr>
				<th><label for="parents_menu<?php echo $num; ?>_subtitle">Subtitle</label></th>
				<td>
					<input type="text" id="parents_menu<?php echo $num; ?>_subtitle"
						   name="parents_menu<?php echo $num; ?>_subtitle"
						   value="<?php echo esc_attr( $subtitle ); ?>"
						   class="large-text" />
				</td>
			</tr>
			<tr>
				<th><label for="parents_menu<?php echo $num; ?>_url">Download URL</label></th>
				<td>
					<input type="url" id="parents_menu<?php echo $num; ?>_url"
						   name="parents_menu<?php echo $num; ?>_url"
						   value="<?php echo esc_attr( $url ); ?>"
						   class="large-text" placeholder="https://" />
				</td>
			</tr>
		</table>
	<?php endforeach; ?>
	<?php
}

/**
 * Safety Section Meta Box
 */
function chroma_parents_safety_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_parents_safety_meta', 'chroma_parents_safety_nonce' );

	$safety_title       = get_post_meta( $post->ID, 'parents_safety_title', true );
	$safety_description = get_post_meta( $post->ID, 'parents_safety_description', true );

	$safety_items = array(
		1 => 'Item 1 (24/7 Monitored Cameras)',
		2 => 'Item 2 (Real-Time Updates)',
		3 => 'Item 3 (Secure Access Control)',
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="parents_safety_title">Section Title</label></th>
			<td>
				<input type="text" id="parents_safety_title" name="parents_safety_title"
					   value="<?php echo esc_attr( $safety_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="parents_safety_description">Description</label></th>
			<td>
				<textarea id="parents_safety_description" name="parents_safety_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $safety_description ); ?></textarea>
			</td>
		</tr>
	</table>

	<?php foreach ( $safety_items as $num => $label ) : ?>
		<?php
		$icon  = get_post_meta( $post->ID, "parents_safety{$num}_icon", true );
		$title = get_post_meta( $post->ID, "parents_safety{$num}_title", true );
		$desc  = get_post_meta( $post->ID, "parents_safety{$num}_desc", true );
		?>
		<hr style="margin: 20px 0;" />
		<h4 style="margin: 15px 0;"><?php echo esc_html( $label ); ?></h4>
		<table class="form-table">
			<tr>
				<th><label for="parents_safety<?php echo $num; ?>_icon">Icon</label></th>
				<td>
					<input type="text" id="parents_safety<?php echo $num; ?>_icon"
						   name="parents_safety<?php echo $num; ?>_icon"
						   value="<?php echo esc_attr( $icon ); ?>" />
					<p class="description">FontAwesome class</p>
				</td>
			</tr>
			<tr>
				<th><label for="parents_safety<?php echo $num; ?>_title">Title</label></th>
				<td>
					<input type="text" id="parents_safety<?php echo $num; ?>_title"
						   name="parents_safety<?php echo $num; ?>_title"
						   value="<?php echo esc_attr( $title ); ?>"
						   class="large-text" />
				</td>
			</tr>
			<tr>
				<th><label for="parents_safety<?php echo $num; ?>_desc">Description</label></th>
				<td>
					<textarea id="parents_safety<?php echo $num; ?>_desc"
							  name="parents_safety<?php echo $num; ?>_desc"
							  rows="3" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
				</td>
			</tr>
		</table>
	<?php endforeach; ?>
	<?php
}

/**
 * FAQ Section Meta Box
 */
function chroma_parents_faq_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_parents_faq_meta', 'chroma_parents_faq_nonce' );

	$faq_title       = get_post_meta( $post->ID, 'parents_faq_title', true );
	$faq_description = get_post_meta( $post->ID, 'parents_faq_description', true );

	$faq_items = array(
		1 => 'FAQ 1',
		2 => 'FAQ 2',
		3 => 'FAQ 3',
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="parents_faq_title">Section Title</label></th>
			<td>
				<input type="text" id="parents_faq_title" name="parents_faq_title"
					   value="<?php echo esc_attr( $faq_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="parents_faq_description">Description</label></th>
			<td>
				<textarea id="parents_faq_description" name="parents_faq_description"
						  rows="2" class="large-text"><?php echo esc_textarea( $faq_description ); ?></textarea>
			</td>
		</tr>
	</table>

	<?php foreach ( $faq_items as $num => $label ) : ?>
		<?php
		$question = get_post_meta( $post->ID, "parents_faq{$num}_question", true );
		$answer   = get_post_meta( $post->ID, "parents_faq{$num}_answer", true );
		?>
		<hr style="margin: 20px 0;" />
		<h4 style="margin: 15px 0;"><?php echo esc_html( $label ); ?></h4>
		<table class="form-table">
			<tr>
				<th><label for="parents_faq<?php echo $num; ?>_question">Question</label></th>
				<td>
					<input type="text" id="parents_faq<?php echo $num; ?>_question"
						   name="parents_faq<?php echo $num; ?>_question"
						   value="<?php echo esc_attr( $question ); ?>"
						   class="large-text" />
				</td>
			</tr>
			<tr>
				<th><label for="parents_faq<?php echo $num; ?>_answer">Answer</label></th>
				<td>
					<textarea id="parents_faq<?php echo $num; ?>_answer"
							  name="parents_faq<?php echo $num; ?>_answer"
							  rows="3" class="large-text"><?php echo esc_textarea( $answer ); ?></textarea>
				</td>
			</tr>
		</table>
	<?php endforeach; ?>
	<?php
}

/**
 * Referral Banner Meta Box
 */
function chroma_parents_referral_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_parents_referral_meta', 'chroma_parents_referral_nonce' );

	$referral_title       = get_post_meta( $post->ID, 'parents_referral_title', true );
	$referral_description = get_post_meta( $post->ID, 'parents_referral_description', true );
	$referral_button_text = get_post_meta( $post->ID, 'parents_referral_button_text', true );
	$referral_button_url  = get_post_meta( $post->ID, 'parents_referral_button_url', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="parents_referral_title">Title</label></th>
			<td>
				<input type="text" id="parents_referral_title" name="parents_referral_title"
					   value="<?php echo esc_attr( $referral_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="parents_referral_description">Description</label></th>
			<td>
				<textarea id="parents_referral_description" name="parents_referral_description"
						  rows="2" class="large-text"><?php echo esc_textarea( $referral_description ); ?></textarea>
				<p class="description">You can use &lt;strong&gt; tags for bold text</p>
			</td>
		</tr>
		<tr>
			<th><label for="parents_referral_button_text">Button Text</label></th>
			<td>
				<input type="text" id="parents_referral_button_text" name="parents_referral_button_text"
					   value="<?php echo esc_attr( $referral_button_text ); ?>" />
			</td>
		</tr>
		<tr>
			<th><label for="parents_referral_button_url">Button URL</label></th>
			<td>
				<input type="url" id="parents_referral_button_url" name="parents_referral_button_url"
					   value="<?php echo esc_attr( $referral_button_url ); ?>"
					   class="large-text" placeholder="https:// or mailto:" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save Parents Page Meta
 */
function chroma_save_parents_page_meta( $post_id ) {
	// Check if this is a page
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	// Define all meta fields
	$meta_boxes = array(
		'chroma_parents_hero_nonce' => array(
			'parents_hero_badge'       => 'sanitize_text_field',
			'parents_hero_title'       => 'sanitize_text_field',
			'parents_hero_description' => 'sanitize_textarea_field',
		),
		'chroma_parents_resources_nonce' => array(
			'parents_essentials_title'         => 'sanitize_text_field',
			// Procare
			'parents_resource_procare_icon'    => 'sanitize_text_field',
			'parents_resource_procare_title'   => 'sanitize_text_field',
			'parents_resource_procare_desc'    => 'sanitize_textarea_field',
			'parents_resource_procare_url'     => 'esc_url_raw',
			// Tuition
			'parents_resource_tuition_icon'    => 'sanitize_text_field',
			'parents_resource_tuition_title'   => 'sanitize_text_field',
			'parents_resource_tuition_desc'    => 'sanitize_textarea_field',
			'parents_resource_tuition_url'     => 'esc_url_raw',
			// Handbook
			'parents_resource_handbook_icon'   => 'sanitize_text_field',
			'parents_resource_handbook_title'  => 'sanitize_text_field',
			'parents_resource_handbook_desc'   => 'sanitize_textarea_field',
			'parents_resource_handbook_url'    => 'esc_url_raw',
			// Enrollment
			'parents_resource_enrollment_icon' => 'sanitize_text_field',
			'parents_resource_enrollment_title'=> 'sanitize_text_field',
			'parents_resource_enrollment_desc' => 'sanitize_textarea_field',
			'parents_resource_enrollment_url'  => 'esc_url_raw',
			// Pre-K GA
			'parents_resource_prekga_icon'     => 'sanitize_text_field',
			'parents_resource_prekga_title'    => 'sanitize_text_field',
			'parents_resource_prekga_desc'     => 'sanitize_textarea_field',
			'parents_resource_prekga_url'      => 'esc_url_raw',
			// Waitlist
			'parents_resource_waitlist_icon'   => 'sanitize_text_field',
			'parents_resource_waitlist_title'  => 'sanitize_text_field',
			'parents_resource_waitlist_desc'   => 'sanitize_textarea_field',
			'parents_resource_waitlist_url'    => 'esc_url_raw',
		),
		'chroma_parents_events_nonce' => array(
			'parents_events_badge'       => 'sanitize_text_field',
			'parents_events_title'       => 'sanitize_text_field',
			'parents_events_description' => 'sanitize_textarea_field',
			'parents_events_image'       => 'esc_url_raw',
			'parents_event1_icon'        => 'sanitize_text_field',
			'parents_event1_title'       => 'sanitize_text_field',
			'parents_event1_desc'        => 'sanitize_textarea_field',
			'parents_event2_icon'        => 'sanitize_text_field',
			'parents_event2_title'       => 'sanitize_text_field',
			'parents_event2_desc'        => 'sanitize_textarea_field',
			'parents_event3_icon'        => 'sanitize_text_field',
			'parents_event3_title'       => 'sanitize_text_field',
			'parents_event3_desc'        => 'sanitize_textarea_field',
		),
		'chroma_parents_nutrition_nonce' => array(
			'parents_nutrition_badge'       => 'sanitize_text_field',
			'parents_nutrition_title'       => 'sanitize_text_field',
			'parents_nutrition_description' => 'sanitize_textarea_field',
			'parents_nutrition_image'       => 'esc_url_raw',
			'parents_menu1_icon'            => 'sanitize_text_field',
			'parents_menu1_title'           => 'sanitize_text_field',
			'parents_menu1_subtitle'        => 'sanitize_text_field',
			'parents_menu1_url'             => 'esc_url_raw',
			'parents_menu2_icon'            => 'sanitize_text_field',
			'parents_menu2_title'           => 'sanitize_text_field',
			'parents_menu2_subtitle'        => 'sanitize_text_field',
			'parents_menu2_url'             => 'esc_url_raw',
			'parents_menu3_icon'            => 'sanitize_text_field',
			'parents_menu3_title'           => 'sanitize_text_field',
			'parents_menu3_subtitle'        => 'sanitize_text_field',
			'parents_menu3_url'             => 'esc_url_raw',
		),
		'chroma_parents_safety_nonce' => array(
			'parents_safety_title'       => 'sanitize_text_field',
			'parents_safety_description' => 'sanitize_textarea_field',
			'parents_safety1_icon'       => 'sanitize_text_field',
			'parents_safety1_title'      => 'sanitize_text_field',
			'parents_safety1_desc'       => 'sanitize_textarea_field',
			'parents_safety2_icon'       => 'sanitize_text_field',
			'parents_safety2_title'      => 'sanitize_text_field',
			'parents_safety2_desc'       => 'sanitize_textarea_field',
			'parents_safety3_icon'       => 'sanitize_text_field',
			'parents_safety3_title'      => 'sanitize_text_field',
			'parents_safety3_desc'       => 'sanitize_textarea_field',
		),
		'chroma_parents_faq_nonce' => array(
			'parents_faq_title'       => 'sanitize_text_field',
			'parents_faq_description' => 'sanitize_textarea_field',
			'parents_faq1_question'   => 'sanitize_text_field',
			'parents_faq1_answer'     => 'sanitize_textarea_field',
			'parents_faq2_question'   => 'sanitize_text_field',
			'parents_faq2_answer'     => 'sanitize_textarea_field',
			'parents_faq3_question'   => 'sanitize_text_field',
			'parents_faq3_answer'     => 'sanitize_textarea_field',
		),
		'chroma_parents_referral_nonce' => array(
			'parents_referral_title'       => 'sanitize_text_field',
			'parents_referral_description' => 'sanitize_textarea_field',
			'parents_referral_button_text' => 'sanitize_text_field',
			'parents_referral_button_url'  => 'esc_url_raw',
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
add_action( 'save_post', 'chroma_save_parents_page_meta' );

/**
 * Seed default values for Parents page when template is first applied
 */
function chroma_seed_parents_page_defaults( $post_id ) {
	// Check if this is a page
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	// Check if Parents Page template is being used
	$template = get_post_meta( $post_id, '_wp_page_template', true );
	if ( $template !== 'page-parents.php' ) {
		return;
	}

	// Check if already seeded
	$already_seeded = get_post_meta( $post_id, '_parents_defaults_seeded', true );
	if ( $already_seeded ) {
		return;
	}

	// Default values array
	$defaults = array(
		'parents_hero_badge'       => 'Parent Dashboard',
		'parents_hero_title'       => 'Partners in your child\'s journey.',
		'parents_hero_description' => 'Everything you need to manage your enrollment, stay connected, and engage with the Chroma community.',

		'parents_essentials_title' => 'Parent Essentials',

		// Procare Cloud
		'parents_resource_procare_icon'  => 'fa-solid fa-cloud',
		'parents_resource_procare_title' => 'Procare Cloud',
		'parents_resource_procare_desc'  => 'Daily reports, photos, and attendance tracking.',
		'parents_resource_procare_url'   => '#',

		// Tuition Portal
		'parents_resource_tuition_icon'  => 'fa-solid fa-credit-card',
		'parents_resource_tuition_title' => 'Tuition Portal',
		'parents_resource_tuition_desc'  => 'Securely view statements and make payments.',
		'parents_resource_tuition_url'   => '#',

		// Parent Handbook
		'parents_resource_handbook_icon'  => 'fa-solid fa-book-open',
		'parents_resource_handbook_title' => 'Parent Handbook',
		'parents_resource_handbook_desc'  => 'Policies, procedures, and operational details.',
		'parents_resource_handbook_url'   => '#',

		// Enrollment Agreement
		'parents_resource_enrollment_icon'  => 'fa-solid fa-file-signature',
		'parents_resource_enrollment_title' => 'Enrollment Agreement',
		'parents_resource_enrollment_desc'  => 'Update your annual enrollment documents.',
		'parents_resource_enrollment_url'   => '#',

		// GA Pre-K Enrollment
		'parents_resource_prekga_icon'  => 'fa-solid fa-graduation-cap',
		'parents_resource_prekga_title' => 'GA Pre-K Enrollment',
		'parents_resource_prekga_desc'  => 'Lottery registration and required state forms.',
		'parents_resource_prekga_url'   => '#',

		// Join Waitlist
		'parents_resource_waitlist_icon'  => 'fa-solid fa-clock',
		'parents_resource_waitlist_title' => 'Join Waitlist',
		'parents_resource_waitlist_desc'  => 'Reserve a spot for siblings or future terms.',
		'parents_resource_waitlist_url'   => '#',

		// Events Section
		'parents_events_badge'       => 'Community',
		'parents_events_title'       => 'Traditions & Celebrations',
		'parents_events_description' => 'We believe in building a village. Our calendar is peppered with events designed to bring families together and celebrate our students\' milestones.',
		'parents_events_image'       => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?q=80&w=800&auto=format&fit=crop',

		'parents_event1_icon'  => 'fa-solid fa-calendar-days',
		'parents_event1_title' => 'Quarterly Family Events',
		'parents_event1_desc'  => 'Every season brings a reason to gather. From our Fall Festival and Winter "Cookies & Cocoa" to our Spring Art Show and Summer Splash Days, we create memories for the whole family.',

		'parents_event2_icon'  => 'fa-solid fa-star',
		'parents_event2_title' => 'Pre-K Graduation',
		'parents_event2_desc'  => 'A cap-and-gown ceremony celebrating our 4 and 5-year-olds as they transition to Kindergarten. It\'s the highlight of our academic year!',

		'parents_event3_icon'  => 'fa-solid fa-handshake',
		'parents_event3_title' => 'Parent-Teacher Conferences',
		'parents_event3_desc'  => 'Twice a year, we sit down to review your child\'s developmental portfolio, set goals, and celebrate their individual growth curve.',

		// Nutrition Section
		'parents_nutrition_badge'       => 'Wellness',
		'parents_nutrition_title'       => 'What\'s for lunch?',
		'parents_nutrition_description' => 'Our in-house chefs prepare balanced, CACFP-compliant meals fresh daily. We are a nut-aware facility.',
		'parents_nutrition_image'       => 'https://images.unsplash.com/photo-1564834724105-918b73d1b9e0?q=80&w=800&auto=format&fit=crop',

		'parents_menu1_icon'     => 'fa-solid fa-carrot',
		'parents_menu1_title'    => 'Current Month Menu',
		'parents_menu1_subtitle' => 'Standard (Ages 1-12)',
		'parents_menu1_url'      => '#',

		'parents_menu2_icon'     => 'fa-solid fa-baby',
		'parents_menu2_title'    => 'Infant Puree Menu',
		'parents_menu2_subtitle' => 'Stage 1 & 2 Solids',
		'parents_menu2_url'      => '#',

		'parents_menu3_icon'     => 'fa-solid fa-wheat-awn-circle-exclamation',
		'parents_menu3_title'    => 'Allergy Statement',
		'parents_menu3_subtitle' => 'Our Nut-Free Protocols',
		'parents_menu3_url'      => '#',

		// Safety Section
		'parents_safety_title'       => 'Safe. Secure. Connected.',
		'parents_safety_description' => 'We employ enterprise-grade security measures and transparent communication protocols so you can have total peace of mind while you work.',

		'parents_safety1_icon'  => 'fa-solid fa-video',
		'parents_safety1_title' => '24/7 Monitored Cameras',
		'parents_safety1_desc'  => 'Our facilities are equipped with high-definition closed-circuit cameras in every classroom, hallway, and playground. Feeds are monitored by leadership to ensure policy adherence and safety.',

		'parents_safety2_icon'  => 'fa-solid fa-mobile-screen-button',
		'parents_safety2_title' => 'Real-Time Updates',
		'parents_safety2_desc'  => 'Through the Procare app, you receive real-time notifications for meals, naps, and diaper changes, plus photos of your child engaging in the curriculum throughout the day.',

		'parents_safety3_icon'  => 'fa-solid fa-lock',
		'parents_safety3_title' => 'Secure Access Control',
		'parents_safety3_desc'  => 'Our lobbies are secured with coded keypad entry systems. Codes are unique to each family and change regularly. ID is strictly required for any alternative pickups.',

		// FAQ Section
		'parents_faq_title'       => 'Operational Policy FAQ',
		'parents_faq_description' => 'Quick answers to common day-to-day questions.',

		'parents_faq1_question' => 'What is the sick child policy?',
		'parents_faq1_answer'   => 'Children must be symptom-free (fever under 100.4°F, no vomiting/diarrhea) for 24 hours without medication before returning to school. Please report any contagious illnesses to the Director immediately.',

		'parents_faq2_question' => 'How do you handle inclement weather?',
		'parents_faq2_answer'   => 'We generally follow the local county school system for weather closures, but we make independent decisions based on staff safety. Alerts will be sent via Procare and posted on our Facebook page by 6:00 AM.',

		'parents_faq3_question' => 'What is the late pickup policy?',
		'parents_faq3_answer'   => 'We close promptly at 6:00 PM. A late fee of $1 per minute is charged to your account for pickups after 6:05 PM to compensate our staff who stay late.',

		// Referral Banner
		'parents_referral_title'       => 'Love the Chroma family?',
		'parents_referral_description' => 'Refer a friend and receive a <strong>$100 tuition credit</strong> when they enroll.',
		'parents_referral_button_text' => 'Refer a Friend',
		'parents_referral_button_url'  => 'mailto:director@chromaela.com?subject=Parent%20Referral',
	);

	// Populate all default values
	foreach ( $defaults as $meta_key => $default_value ) {
		update_post_meta( $post_id, $meta_key, $default_value );
	}

	// Mark as seeded
	update_post_meta( $post_id, '_parents_defaults_seeded', '1' );
}
add_action( 'save_post', 'chroma_seed_parents_page_defaults', 5 );

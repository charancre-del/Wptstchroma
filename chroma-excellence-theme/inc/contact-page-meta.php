<?php
/**
 * Contact Page Meta Boxes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Contact Page Meta Boxes
 */
function chroma_contact_page_meta_boxes() {
	add_meta_box(
		'chroma-contact-hero',
		__( 'Hero Section', 'chroma-excellence' ),
		'chroma_contact_hero_meta_box_render',
		'page',
		'normal',
		'high'
	);

	add_meta_box(
		'chroma-contact-form',
		__( 'Form Settings', 'chroma-excellence' ),
		'chroma_contact_form_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-contact-corporate',
		__( 'Corporate Office Info', 'chroma-excellence' ),
		'chroma_contact_corporate_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-contact-careers',
		__( 'Careers Section', 'chroma-excellence' ),
		'chroma_contact_careers_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-contact-press',
		__( 'Press Inquiries Section', 'chroma-excellence' ),
		'chroma_contact_press_meta_box_render',
		'page',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'chroma_contact_page_meta_boxes' );

/**
 * Hero Section Meta Box
 */
function chroma_contact_hero_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_contact_hero_meta', 'chroma_contact_hero_nonce' );

	$hero_badge       = get_post_meta( $post->ID, 'contact_hero_badge', true );
	$hero_title       = get_post_meta( $post->ID, 'contact_hero_title', true );
	$hero_description = get_post_meta( $post->ID, 'contact_hero_description', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="contact_hero_badge">Badge Text</label></th>
			<td>
				<input type="text" id="contact_hero_badge" name="contact_hero_badge"
					   value="<?php echo esc_attr( $hero_badge ); ?>"
					   class="large-text" placeholder="e.g., Start Your Journey" />
			</td>
		</tr>
		<tr>
			<th><label for="contact_hero_title">Title</label></th>
			<td>
				<input type="text" id="contact_hero_title" name="contact_hero_title"
					   value="<?php echo esc_attr( $hero_title ); ?>"
					   class="large-text" placeholder="e.g., We'd love to meet you." />
			</td>
		</tr>
		<tr>
			<th><label for="contact_hero_description">Description</label></th>
			<td>
				<textarea id="contact_hero_description" name="contact_hero_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $hero_description ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Form Settings Meta Box
 */
function chroma_contact_form_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_contact_form_meta', 'chroma_contact_form_nonce' );

	$form_submit_text = get_post_meta( $post->ID, 'contact_form_submit_text', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="contact_form_submit_text">Submit Button Text</label></th>
			<td>
				<input type="text" id="contact_form_submit_text" name="contact_form_submit_text"
					   value="<?php echo esc_attr( $form_submit_text ); ?>"
					   class="large-text" placeholder="e.g., Submit Request" />
			</td>
		</tr>
	</table>
	<p class="description">Form functionality can be configured with a contact form plugin (Contact Form 7, Gravity Forms, etc.)</p>
	<?php
}

/**
 * Corporate Office Meta Box
 */
function chroma_contact_corporate_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_contact_corporate_meta', 'chroma_contact_corporate_nonce' );

	$corporate_title   = get_post_meta( $post->ID, 'contact_corporate_title', true );
	$corporate_name    = get_post_meta( $post->ID, 'contact_corporate_name', true );
	$corporate_address = get_post_meta( $post->ID, 'contact_corporate_address', true );
	$corporate_phone   = get_post_meta( $post->ID, 'contact_corporate_phone', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="contact_corporate_title">Section Title</label></th>
			<td>
				<input type="text" id="contact_corporate_title" name="contact_corporate_title"
					   value="<?php echo esc_attr( $corporate_title ); ?>"
					   class="large-text" placeholder="e.g., Corporate Office" />
			</td>
		</tr>
		<tr>
			<th><label for="contact_corporate_name">Office Name</label></th>
			<td>
				<input type="text" id="contact_corporate_name" name="contact_corporate_name"
					   value="<?php echo esc_attr( $corporate_name ); ?>"
					   class="large-text" placeholder="e.g., Chroma Early Learning HQ" />
			</td>
		</tr>
		<tr>
			<th><label for="contact_corporate_address">Address</label></th>
			<td>
				<textarea id="contact_corporate_address" name="contact_corporate_address"
						  rows="3" class="large-text"
						  placeholder="123 Education Way, Suite 400&#10;Atlanta, GA 30309"><?php echo esc_textarea( $corporate_address ); ?></textarea>
				<p class="description">Enter each line of the address on a new line</p>
			</td>
		</tr>
		<tr>
			<th><label for="contact_corporate_phone">Phone Number</label></th>
			<td>
				<input type="text" id="contact_corporate_phone" name="contact_corporate_phone"
					   value="<?php echo esc_attr( $corporate_phone ); ?>"
					   placeholder="e.g., (404) 555-0199" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Careers Section Meta Box
 */
function chroma_contact_careers_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_contact_careers_meta', 'chroma_contact_careers_nonce' );

	$careers_title       = get_post_meta( $post->ID, 'contact_careers_title', true );
	$careers_description = get_post_meta( $post->ID, 'contact_careers_description', true );
	$careers_link_text   = get_post_meta( $post->ID, 'contact_careers_link_text', true );
	$careers_link_url    = get_post_meta( $post->ID, 'contact_careers_link_url', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="contact_careers_title">Section Title</label></th>
			<td>
				<input type="text" id="contact_careers_title" name="contact_careers_title"
					   value="<?php echo esc_attr( $careers_title ); ?>"
					   class="large-text" placeholder="e.g., Careers" />
			</td>
		</tr>
		<tr>
			<th><label for="contact_careers_description">Description</label></th>
			<td>
				<textarea id="contact_careers_description" name="contact_careers_description"
						  rows="2" class="large-text"><?php echo esc_textarea( $careers_description ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="contact_careers_link_text">Link Text</label></th>
			<td>
				<input type="text" id="contact_careers_link_text" name="contact_careers_link_text"
					   value="<?php echo esc_attr( $careers_link_text ); ?>"
					   class="large-text" placeholder="e.g., View Open Positions" />
			</td>
		</tr>
		<tr>
			<th><label for="contact_careers_link_url">Link URL</label></th>
			<td>
				<input type="url" id="contact_careers_link_url" name="contact_careers_link_url"
					   value="<?php echo esc_attr( $careers_link_url ); ?>"
					   class="large-text" placeholder="e.g., /careers" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Press Inquiries Section Meta Box
 */
function chroma_contact_press_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_contact_press_meta', 'chroma_contact_press_nonce' );

	$press_title       = get_post_meta( $post->ID, 'contact_press_title', true );
	$press_description = get_post_meta( $post->ID, 'contact_press_description', true );
	$press_link_text   = get_post_meta( $post->ID, 'contact_press_link_text', true );
	$press_link_url    = get_post_meta( $post->ID, 'contact_press_link_url', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="contact_press_title">Section Title</label></th>
			<td>
				<input type="text" id="contact_press_title" name="contact_press_title"
					   value="<?php echo esc_attr( $press_title ); ?>"
					   class="large-text" placeholder="e.g., Press Inquiries" />
			</td>
		</tr>
		<tr>
			<th><label for="contact_press_description">Description</label></th>
			<td>
				<textarea id="contact_press_description" name="contact_press_description"
						  rows="2" class="large-text"><?php echo esc_textarea( $press_description ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="contact_press_link_text">Link Text</label></th>
			<td>
				<input type="text" id="contact_press_link_text" name="contact_press_link_text"
					   value="<?php echo esc_attr( $press_link_text ); ?>"
					   class="large-text" placeholder="e.g., Visit Newsroom" />
			</td>
		</tr>
		<tr>
			<th><label for="contact_press_link_url">Link URL</label></th>
			<td>
				<input type="url" id="contact_press_link_url" name="contact_press_link_url"
					   value="<?php echo esc_attr( $press_link_url ); ?>"
					   class="large-text" placeholder="e.g., /newsroom" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save Contact Page Meta
 */
function chroma_save_contact_page_meta( $post_id ) {
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	// Define all meta fields
	$meta_boxes = array(
		'chroma_contact_hero_nonce' => array(
			'contact_hero_badge'       => 'sanitize_text_field',
			'contact_hero_title'       => 'sanitize_text_field',
			'contact_hero_description' => 'sanitize_textarea_field',
		),
		'chroma_contact_form_nonce' => array(
			'contact_form_submit_text' => 'sanitize_text_field',
		),
		'chroma_contact_corporate_nonce' => array(
			'contact_corporate_title'   => 'sanitize_text_field',
			'contact_corporate_name'    => 'sanitize_text_field',
			'contact_corporate_address' => 'sanitize_textarea_field',
			'contact_corporate_phone'   => 'sanitize_text_field',
		),
		'chroma_contact_careers_nonce' => array(
			'contact_careers_title'       => 'sanitize_text_field',
			'contact_careers_description' => 'sanitize_textarea_field',
			'contact_careers_link_text'   => 'sanitize_text_field',
			'contact_careers_link_url'    => 'esc_url_raw',
		),
		'chroma_contact_press_nonce' => array(
			'contact_press_title'       => 'sanitize_text_field',
			'contact_press_description' => 'sanitize_textarea_field',
			'contact_press_link_text'   => 'sanitize_text_field',
			'contact_press_link_url'    => 'esc_url_raw',
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
add_action( 'save_post', 'chroma_save_contact_page_meta' );

/**
 * Seed default values for Contact page
 */
function chroma_seed_contact_page_defaults( $post_id ) {
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	$template = get_post_meta( $post_id, '_wp_page_template', true );
	if ( $template !== 'page-contact.php' ) {
		return;
	}

	$already_seeded = get_post_meta( $post_id, '_contact_defaults_seeded', true );
	if ( $already_seeded ) {
		return;
	}

	$defaults = array(
		'contact_hero_badge'       => 'Start Your Journey',
		'contact_hero_title'       => 'We\'d love to meet you.',
		'contact_hero_description' => 'Ready to experience the Chroma difference? Schedule a tour or ask us a question below to get started.',

		'contact_form_submit_text' => 'Submit Request',

		'contact_corporate_title'   => 'Corporate Office',
		'contact_corporate_name'    => 'Chroma Early Learning HQ',
		'contact_corporate_address' => "123 Education Way, Suite 400\nAtlanta, GA 30309",
		'contact_corporate_phone'   => '(404) 555-0199',

		'contact_careers_title'       => 'Careers',
		'contact_careers_description' => 'Passionate about early childhood education? We are always looking for dedicated teachers and directors.',
		'contact_careers_link_text'   => 'View Open Positions',
		'contact_careers_link_url'    => '/careers',

		'contact_press_title'       => 'Press Inquiries',
		'contact_press_description' => 'For media kits and interview requests with our leadership team.',
		'contact_press_link_text'   => 'Visit Newsroom',
		'contact_press_link_url'    => '/newsroom',
	);

	foreach ( $defaults as $meta_key => $default_value ) {
		update_post_meta( $post_id, $meta_key, $default_value );
	}

	update_post_meta( $post_id, '_contact_defaults_seeded', '1' );
}
add_action( 'save_post', 'chroma_seed_contact_page_defaults', 5 );

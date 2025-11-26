<?php
/**
 * Employers Page Meta Boxes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Employers Page Meta Boxes
 */
function chroma_employers_page_meta_boxes() {
	add_meta_box(
		'chroma-employers-hero',
		__( 'Hero Section', 'chroma-excellence' ),
		'chroma_employers_hero_meta_box_render',
		'page',
		'normal',
		'high'
	);

	add_meta_box(
		'chroma-employers-solutions',
		__( 'Solutions Section (3 Cards)', 'chroma-excellence' ),
		'chroma_employers_solutions_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-employers-tax',
		__( 'Tax Incentives Section', 'chroma-excellence' ),
		'chroma_employers_tax_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-employers-contact',
		__( 'Contact Section', 'chroma-excellence' ),
		'chroma_employers_contact_meta_box_render',
		'page',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'chroma_employers_page_meta_boxes' );

/**
 * Hero Section Meta Box
 */
function chroma_employers_hero_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_employers_hero_meta', 'chroma_employers_hero_nonce' );

	$hero_badge       = get_post_meta( $post->ID, 'employers_hero_badge', true );
	$hero_title       = get_post_meta( $post->ID, 'employers_hero_title', true );
	$hero_description = get_post_meta( $post->ID, 'employers_hero_description', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="employers_hero_badge">Badge Text</label></th>
			<td>
				<input type="text" id="employers_hero_badge" name="employers_hero_badge"
					   value="<?php echo esc_attr( $hero_badge ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="employers_hero_title">Title</label></th>
			<td>
				<input type="text" id="employers_hero_title" name="employers_hero_title"
					   value="<?php echo esc_attr( $hero_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="employers_hero_description">Description</label></th>
			<td>
				<textarea id="employers_hero_description" name="employers_hero_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $hero_description ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Solutions Section Meta Box
 */
function chroma_employers_solutions_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_employers_solutions_meta', 'chroma_employers_solutions_nonce' );

	$solutions = array(
		1 => 'Solution 1 (Priority Access)',
		2 => 'Solution 2 (Tuition Subsidies)',
		3 => 'Solution 3 (Back-Up Care)',
	);
	?>
	<?php foreach ( $solutions as $num => $label ) : ?>
		<?php
		$title = get_post_meta( $post->ID, "employers_solution{$num}_title", true );
		$desc  = get_post_meta( $post->ID, "employers_solution{$num}_desc", true );
		?>
		<?php if ( $num > 1 ) : ?>
		<hr style="margin: 20px 0;" />
		<?php endif; ?>
		<h4 style="margin: 15px 0;"><?php echo esc_html( $label ); ?></h4>
		<table class="form-table">
			<tr>
				<th><label for="employers_solution<?php echo $num; ?>_title">Title</label></th>
				<td>
					<input type="text" id="employers_solution<?php echo $num; ?>_title"
						   name="employers_solution<?php echo $num; ?>_title"
						   value="<?php echo esc_attr( $title ); ?>"
						   class="large-text" />
				</td>
			</tr>
			<tr>
				<th><label for="employers_solution<?php echo $num; ?>_desc">Description</label></th>
				<td>
					<textarea id="employers_solution<?php echo $num; ?>_desc"
							  name="employers_solution<?php echo $num; ?>_desc"
							  rows="3" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
				</td>
			</tr>
		</table>
	<?php endforeach; ?>
	<?php
}

/**
 * Tax Incentives Section Meta Box
 */
function chroma_employers_tax_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_employers_tax_meta', 'chroma_employers_tax_nonce' );

	$tax_badge       = get_post_meta( $post->ID, 'employers_tax_badge', true );
	$tax_title       = get_post_meta( $post->ID, 'employers_tax_title', true );
	$tax_description = get_post_meta( $post->ID, 'employers_tax_description', true );
	$tax_disclaimer  = get_post_meta( $post->ID, 'employers_tax_disclaimer', true );

	// Federal Credit
	$federal_icon      = get_post_meta( $post->ID, 'employers_federal_icon', true );
	$federal_title     = get_post_meta( $post->ID, 'employers_federal_title', true );
	$federal_subtitle  = get_post_meta( $post->ID, 'employers_federal_subtitle', true );
	$federal_desc      = get_post_meta( $post->ID, 'employers_federal_desc', true );
	$federal_link_text = get_post_meta( $post->ID, 'employers_federal_link_text', true );
	$federal_link_url  = get_post_meta( $post->ID, 'employers_federal_link_url', true );

	// Georgia Credit
	$georgia_icon      = get_post_meta( $post->ID, 'employers_georgia_icon', true );
	$georgia_title     = get_post_meta( $post->ID, 'employers_georgia_title', true );
	$georgia_subtitle  = get_post_meta( $post->ID, 'employers_georgia_subtitle', true );
	$georgia_desc      = get_post_meta( $post->ID, 'employers_georgia_desc', true );
	$georgia_link_text = get_post_meta( $post->ID, 'employers_georgia_link_text', true );
	$georgia_link_url  = get_post_meta( $post->ID, 'employers_georgia_link_url', true );
	?>
	<h3>Section Header</h3>
	<table class="form-table">
		<tr>
			<th><label for="employers_tax_badge">Badge Text</label></th>
			<td>
				<input type="text" id="employers_tax_badge" name="employers_tax_badge"
					   value="<?php echo esc_attr( $tax_badge ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="employers_tax_title">Section Title</label></th>
			<td>
				<input type="text" id="employers_tax_title" name="employers_tax_title"
					   value="<?php echo esc_attr( $tax_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="employers_tax_description">Description</label></th>
			<td>
				<textarea id="employers_tax_description" name="employers_tax_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $tax_description ); ?></textarea>
			</td>
		</tr>
	</table>

	<hr style="margin: 30px 0;" />
	<h3>Federal 45F Credit Card</h3>
	<table class="form-table">
		<tr>
			<th><label for="employers_federal_icon">Icon</label></th>
			<td>
				<input type="text" id="employers_federal_icon" name="employers_federal_icon"
					   value="<?php echo esc_attr( $federal_icon ); ?>" />
				<p class="description">FontAwesome class (e.g., fa-solid fa-landmark)</p>
			</td>
		</tr>
		<tr>
			<th><label for="employers_federal_title">Title</label></th>
			<td>
				<input type="text" id="employers_federal_title" name="employers_federal_title"
					   value="<?php echo esc_attr( $federal_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="employers_federal_subtitle">Subtitle</label></th>
			<td>
				<input type="text" id="employers_federal_subtitle" name="employers_federal_subtitle"
					   value="<?php echo esc_attr( $federal_subtitle ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="employers_federal_desc">Description</label></th>
			<td>
				<textarea id="employers_federal_desc" name="employers_federal_desc"
						  rows="4" class="large-text"><?php echo esc_textarea( $federal_desc ); ?></textarea>
				<p class="description">You can use &lt;strong&gt; tags for bold text</p>
			</td>
		</tr>
		<tr>
			<th><label for="employers_federal_link_text">Link Text</label></th>
			<td>
				<input type="text" id="employers_federal_link_text" name="employers_federal_link_text"
					   value="<?php echo esc_attr( $federal_link_text ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="employers_federal_link_url">Link URL</label></th>
			<td>
				<input type="url" id="employers_federal_link_url" name="employers_federal_link_url"
					   value="<?php echo esc_attr( $federal_link_url ); ?>"
					   class="large-text" placeholder="https://" />
			</td>
		</tr>
	</table>

	<hr style="margin: 30px 0;" />
	<h3>Georgia Employer's Credit Card</h3>
	<table class="form-table">
		<tr>
			<th><label for="employers_georgia_icon">Icon</label></th>
			<td>
				<input type="text" id="employers_georgia_icon" name="employers_georgia_icon"
					   value="<?php echo esc_attr( $georgia_icon ); ?>" />
				<p class="description">FontAwesome class (e.g., fa-solid fa-map-location-dot)</p>
			</td>
		</tr>
		<tr>
			<th><label for="employers_georgia_title">Title</label></th>
			<td>
				<input type="text" id="employers_georgia_title" name="employers_georgia_title"
					   value="<?php echo esc_attr( $georgia_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="employers_georgia_subtitle">Subtitle</label></th>
			<td>
				<input type="text" id="employers_georgia_subtitle" name="employers_georgia_subtitle"
					   value="<?php echo esc_attr( $georgia_subtitle ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="employers_georgia_desc">Description</label></th>
			<td>
				<textarea id="employers_georgia_desc" name="employers_georgia_desc"
						  rows="4" class="large-text"><?php echo esc_textarea( $georgia_desc ); ?></textarea>
				<p class="description">You can use &lt;strong&gt; tags for bold text</p>
			</td>
		</tr>
		<tr>
			<th><label for="employers_georgia_link_text">Link Text</label></th>
			<td>
				<input type="text" id="employers_georgia_link_text" name="employers_georgia_link_text"
					   value="<?php echo esc_attr( $georgia_link_text ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="employers_georgia_link_url">Link URL</label></th>
			<td>
				<input type="url" id="employers_georgia_link_url" name="employers_georgia_link_url"
					   value="<?php echo esc_attr( $georgia_link_url ); ?>"
					   class="large-text" placeholder="https://" />
			</td>
		</tr>
	</table>

	<hr style="margin: 30px 0;" />
	<table class="form-table">
		<tr>
			<th><label for="employers_tax_disclaimer">Disclaimer Text</label></th>
			<td>
				<textarea id="employers_tax_disclaimer" name="employers_tax_disclaimer"
						  rows="2" class="large-text"><?php echo esc_textarea( $tax_disclaimer ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Contact Section Meta Box
 */
function chroma_employers_contact_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_employers_contact_meta', 'chroma_employers_contact_nonce' );

	$contact_title = get_post_meta( $post->ID, 'employers_contact_title', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="employers_contact_title">Section Title</label></th>
			<td>
				<input type="text" id="employers_contact_title" name="employers_contact_title"
					   value="<?php echo esc_attr( $contact_title ); ?>"
					   class="large-text" />
				<p class="description">The form fields (Company Name, HR Contact Name, Work Email, Request Info Kit button) are hardcoded in the template</p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save Employers Page Meta
 */
function chroma_save_employers_page_meta( $post_id ) {
	// Check if this is a page
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	// Define all meta fields
	$meta_boxes = array(
		'chroma_employers_hero_nonce' => array(
			'employers_hero_badge'       => 'sanitize_text_field',
			'employers_hero_title'       => 'sanitize_text_field',
			'employers_hero_description' => 'sanitize_textarea_field',
		),
		'chroma_employers_solutions_nonce' => array(
			'employers_solution1_title' => 'sanitize_text_field',
			'employers_solution1_desc'  => 'sanitize_textarea_field',
			'employers_solution2_title' => 'sanitize_text_field',
			'employers_solution2_desc'  => 'sanitize_textarea_field',
			'employers_solution3_title' => 'sanitize_text_field',
			'employers_solution3_desc'  => 'sanitize_textarea_field',
		),
		'chroma_employers_tax_nonce' => array(
			'employers_tax_badge'          => 'sanitize_text_field',
			'employers_tax_title'          => 'sanitize_text_field',
			'employers_tax_description'    => 'sanitize_textarea_field',
			'employers_federal_icon'       => 'sanitize_text_field',
			'employers_federal_title'      => 'sanitize_text_field',
			'employers_federal_subtitle'   => 'sanitize_text_field',
			'employers_federal_desc'       => 'sanitize_textarea_field',
			'employers_federal_link_text'  => 'sanitize_text_field',
			'employers_federal_link_url'   => 'esc_url_raw',
			'employers_georgia_icon'       => 'sanitize_text_field',
			'employers_georgia_title'      => 'sanitize_text_field',
			'employers_georgia_subtitle'   => 'sanitize_text_field',
			'employers_georgia_desc'       => 'sanitize_textarea_field',
			'employers_georgia_link_text'  => 'sanitize_text_field',
			'employers_georgia_link_url'   => 'esc_url_raw',
			'employers_tax_disclaimer'     => 'sanitize_textarea_field',
		),
		'chroma_employers_contact_nonce' => array(
			'employers_contact_title' => 'sanitize_text_field',
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
add_action( 'save_post', 'chroma_save_employers_page_meta' );

/**
 * Seed default values for Employers page when template is first applied
 */
function chroma_seed_employers_page_defaults( $post_id ) {
	// Check if this is a page
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	// Check if Employers Page template is being used
	$template = get_post_meta( $post_id, '_wp_page_template', true );
	if ( $template !== 'page-employers.php' ) {
		return;
	}

	// Check if already seeded
	$already_seeded = get_post_meta( $post_id, '_employers_defaults_seeded', true );
	if ( $already_seeded ) {
		return;
	}

	// Default values array
	$defaults = array(
		'employers_hero_badge'       => 'Workforce Solutions',
		'employers_hero_title'       => 'Childcare is critical infrastructure.',
		'employers_hero_description' => 'Retain top talent and reduce absenteeism by offering premium childcare benefits. Chroma partners with Metro Atlanta\'s leading employers to support working parents.',

		'employers_solution1_title' => 'Priority Access',
		'employers_solution1_desc'  => 'Skip the waitlist. Reserve dedicated spots at our 19+ locations exclusively for your employees\' children.',

		'employers_solution2_title' => 'Tuition Subsidies',
		'employers_solution2_desc'  => 'We manage employer-sponsored tuition matching programs, making quality care affordable for your team.',

		'employers_solution3_title' => 'Back-Up Care',
		'employers_solution3_desc'  => 'Flexible drop-in options for when schools close or regular caregivers fall through, keeping your team at work.',

		'employers_tax_badge'       => 'Financial Incentives',
		'employers_tax_title'       => 'Maximize Your ROI with Tax Credits',
		'employers_tax_description' => 'Partnering with Chroma isn\'t just an investment in your company cultureit\'s a smart financial move. State and Federal programs significantly offset the cost of providing childcare benefits.',

		'employers_federal_icon'       => 'fa-solid fa-landmark',
		'employers_federal_title'      => 'Federal 45F Credit',
		'employers_federal_subtitle'   => 'Employer-Provided Child Care Credit',
		'employers_federal_desc'       => 'The IRS allows businesses to claim a tax credit of up to <strong>$150,000 annually</strong>. This covers 25% of qualified childcare facility expenditures (such as contracting with Chroma for reserved spots) and 10% of resource and referral expenditures.',
		'employers_federal_link_text'  => 'View IRS Form 8882',
		'employers_federal_link_url'   => 'https://www.irs.gov/forms-pubs/about-form-8882',

		'employers_georgia_icon'       => 'fa-solid fa-map-location-dot',
		'employers_georgia_title'      => 'Georgia Employer\'s Credit',
		'employers_georgia_subtitle'   => 'Georgia Child Care Tax Credit',
		'employers_georgia_desc'       => 'Georgia offers one of the most generous incentives in the nation. Employers who purchase or sponsor childcare for employees can receive a tax credit equal to <strong>75% of the employer\'s cost</strong>. This credit can be applied against 50% of your state income tax liability.',
		'employers_georgia_link_text'  => 'View Georgia DOR Details',
		'employers_georgia_link_url'   => 'https://dor.georgia.gov/tax-credits-business',

		'employers_tax_disclaimer' => 'Note: Please consult with your corporate tax professional to verify eligibility and application details.',

		'employers_contact_title' => 'Build a family-friendly culture.',
	);

	// Populate all default values
	foreach ( $defaults as $meta_key => $default_value ) {
		update_post_meta( $post_id, $meta_key, $default_value );
	}

	// Mark as seeded
	update_post_meta( $post_id, '_employers_defaults_seeded', '1' );
}
add_action( 'save_post', 'chroma_seed_employers_page_defaults', 5 );

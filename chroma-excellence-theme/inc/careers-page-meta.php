<?php
/**
 * Careers Page Meta Boxes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Careers Page Meta Boxes
 */
function chroma_careers_page_meta_boxes() {
	add_meta_box(
		'chroma-careers-hero',
		__( 'Hero Section', 'chroma-excellence' ),
		'chroma_careers_hero_meta_box_render',
		'page',
		'normal',
		'high'
	);

	add_meta_box(
		'chroma-careers-culture',
		__( 'Culture & Benefits Section', 'chroma-excellence' ),
		'chroma_careers_culture_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-careers-openings',
		__( 'Current Opportunities Section (3 Job Listings)', 'chroma-excellence' ),
		'chroma_careers_openings_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-careers-cta',
		__( 'Application CTA Section', 'chroma-excellence' ),
		'chroma_careers_cta_meta_box_render',
		'page',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'chroma_careers_page_meta_boxes' );

/**
 * Hero Section Meta Box
 */
function chroma_careers_hero_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_careers_hero_meta', 'chroma_careers_hero_nonce' );

	$hero_badge       = get_post_meta( $post->ID, 'careers_hero_badge', true );
	$hero_title       = get_post_meta( $post->ID, 'careers_hero_title', true );
	$hero_description = get_post_meta( $post->ID, 'careers_hero_description', true );
	$hero_button_text = get_post_meta( $post->ID, 'careers_hero_button_text', true );
	$hero_button_url  = get_post_meta( $post->ID, 'careers_hero_button_url', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="careers_hero_badge">Badge Text</label></th>
			<td>
				<input type="text" id="careers_hero_badge" name="careers_hero_badge"
					   value="<?php echo esc_attr( $hero_badge ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="careers_hero_title">Title</label></th>
			<td>
				<input type="text" id="careers_hero_title" name="careers_hero_title"
					   value="<?php echo esc_attr( $hero_title ); ?>"
					   class="large-text" />
				<p class="description">Use &lt;br&gt; for line breaks and &lt;span class="italic text-chroma-red"&gt; for styled text</p>
			</td>
		</tr>
		<tr>
			<th><label for="careers_hero_description">Description</label></th>
			<td>
				<textarea id="careers_hero_description" name="careers_hero_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $hero_description ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="careers_hero_button_text">Button Text</label></th>
			<td>
				<input type="text" id="careers_hero_button_text" name="careers_hero_button_text"
					   value="<?php echo esc_attr( $hero_button_text ); ?>" />
			</td>
		</tr>
		<tr>
			<th><label for="careers_hero_button_url">Button URL</label></th>
			<td>
				<input type="text" id="careers_hero_button_url" name="careers_hero_button_url"
					   value="<?php echo esc_attr( $hero_button_url ); ?>"
					   class="large-text" placeholder="#openings or https://" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Culture & Benefits Section Meta Box
 */
function chroma_careers_culture_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_careers_culture_meta', 'chroma_careers_culture_nonce' );

	$culture_title       = get_post_meta( $post->ID, 'careers_culture_title', true );
	$culture_description = get_post_meta( $post->ID, 'careers_culture_description', true );

	$benefits = array(
		1 => 'Benefit 1 (Competitive Pay)',
		2 => 'Benefit 2 (Paid Tuition)',
		3 => 'Benefit 3 (Health & Wellness)',
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="careers_culture_title">Section Title</label></th>
			<td>
				<input type="text" id="careers_culture_title" name="careers_culture_title"
					   value="<?php echo esc_attr( $culture_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="careers_culture_description">Description</label></th>
			<td>
				<textarea id="careers_culture_description" name="careers_culture_description"
						  rows="2" class="large-text"><?php echo esc_textarea( $culture_description ); ?></textarea>
			</td>
		</tr>
	</table>

	<?php foreach ( $benefits as $num => $label ) : ?>
		<?php
		$icon  = get_post_meta( $post->ID, "careers_benefit{$num}_icon", true );
		$title = get_post_meta( $post->ID, "careers_benefit{$num}_title", true );
		$desc  = get_post_meta( $post->ID, "careers_benefit{$num}_desc", true );
		?>
		<hr style="margin: 20px 0;" />
		<h4 style="margin: 15px 0;"><?php echo esc_html( $label ); ?></h4>
		<table class="form-table">
			<tr>
				<th><label for="careers_benefit<?php echo $num; ?>_icon">Icon</label></th>
				<td>
					<input type="text" id="careers_benefit<?php echo $num; ?>_icon"
						   name="careers_benefit<?php echo $num; ?>_icon"
						   value="<?php echo esc_attr( $icon ); ?>" />
					<p class="description">FontAwesome class (e.g., fa-solid fa-money-bill-wave)</p>
				</td>
			</tr>
			<tr>
				<th><label for="careers_benefit<?php echo $num; ?>_title">Title</label></th>
				<td>
					<input type="text" id="careers_benefit<?php echo $num; ?>_title"
						   name="careers_benefit<?php echo $num; ?>_title"
						   value="<?php echo esc_attr( $title ); ?>"
						   class="large-text" />
				</td>
			</tr>
			<tr>
				<th><label for="careers_benefit<?php echo $num; ?>_desc">Description</label></th>
				<td>
					<textarea id="careers_benefit<?php echo $num; ?>_desc"
							  name="careers_benefit<?php echo $num; ?>_desc"
							  rows="2" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
				</td>
			</tr>
		</table>
	<?php endforeach; ?>
	<?php
}

/**
 * Openings Section Meta Box
 */
function chroma_careers_openings_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_careers_openings_meta', 'chroma_careers_openings_nonce' );

	$openings_title = get_post_meta( $post->ID, 'careers_openings_title', true );

	$jobs = array(
		1 => 'Job Listing 1',
		2 => 'Job Listing 2',
		3 => 'Job Listing 3',
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="careers_openings_title">Section Title</label></th>
			<td>
				<input type="text" id="careers_openings_title" name="careers_openings_title"
					   value="<?php echo esc_attr( $openings_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
	</table>

	<?php foreach ( $jobs as $num => $label ) : ?>
		<?php
		$title    = get_post_meta( $post->ID, "careers_job{$num}_title", true );
		$location = get_post_meta( $post->ID, "careers_job{$num}_location", true );
		$type     = get_post_meta( $post->ID, "careers_job{$num}_type", true );
		$url      = get_post_meta( $post->ID, "careers_job{$num}_url", true );
		?>
		<hr style="margin: 20px 0;" />
		<h4 style="margin: 15px 0;"><?php echo esc_html( $label ); ?></h4>
		<table class="form-table">
			<tr>
				<th><label for="careers_job<?php echo $num; ?>_title">Job Title</label></th>
				<td>
					<input type="text" id="careers_job<?php echo $num; ?>_title"
						   name="careers_job<?php echo $num; ?>_title"
						   value="<?php echo esc_attr( $title ); ?>"
						   class="large-text" placeholder="e.g., Lead Teacher (Pre-K)" />
					<p class="description">Leave blank to hide this job listing</p>
				</td>
			</tr>
			<tr>
				<th><label for="careers_job<?php echo $num; ?>_location">Location</label></th>
				<td>
					<input type="text" id="careers_job<?php echo $num; ?>_location"
						   name="careers_job<?php echo $num; ?>_location"
						   value="<?php echo esc_attr( $location ); ?>"
						   placeholder="e.g., Lawrenceville Campus" />
				</td>
			</tr>
			<tr>
				<th><label for="careers_job<?php echo $num; ?>_type">Job Type</label></th>
				<td>
					<input type="text" id="careers_job<?php echo $num; ?>_type"
						   name="careers_job<?php echo $num; ?>_type"
						   value="<?php echo esc_attr( $type ); ?>"
						   placeholder="e.g., Full Time" />
				</td>
			</tr>
			<tr>
				<th><label for="careers_job<?php echo $num; ?>_url">Apply URL</label></th>
				<td>
					<input type="url" id="careers_job<?php echo $num; ?>_url"
						   name="careers_job<?php echo $num; ?>_url"
						   value="<?php echo esc_attr( $url ); ?>"
						   class="large-text" placeholder="#apply or https://" />
				</td>
			</tr>
		</table>
	<?php endforeach; ?>
	<?php
}

/**
 * CTA Section Meta Box
 */
function chroma_careers_cta_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_careers_cta_meta', 'chroma_careers_cta_nonce' );

	$cta_title       = get_post_meta( $post->ID, 'careers_cta_title', true );
	$cta_description = get_post_meta( $post->ID, 'careers_cta_description', true );
	$cta_button_text = get_post_meta( $post->ID, 'careers_cta_button_text', true );
	$cta_button_url  = get_post_meta( $post->ID, 'careers_cta_button_url', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="careers_cta_title">Title</label></th>
			<td>
				<input type="text" id="careers_cta_title" name="careers_cta_title"
					   value="<?php echo esc_attr( $cta_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="careers_cta_description">Description</label></th>
			<td>
				<textarea id="careers_cta_description" name="careers_cta_description"
						  rows="2" class="large-text"><?php echo esc_textarea( $cta_description ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="careers_cta_button_text">Button Text</label></th>
			<td>
				<input type="text" id="careers_cta_button_text" name="careers_cta_button_text"
					   value="<?php echo esc_attr( $cta_button_text ); ?>" />
			</td>
		</tr>
		<tr>
			<th><label for="careers_cta_button_url">Button URL</label></th>
			<td>
				<input type="url" id="careers_cta_button_url" name="careers_cta_button_url"
					   value="<?php echo esc_attr( $cta_button_url ); ?>"
					   class="large-text" placeholder="mailto: or https://" />
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save Careers Page Meta
 */
function chroma_save_careers_page_meta( $post_id ) {
	// Check if this is a page
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	// Define all meta fields
	$meta_boxes = array(
		'chroma_careers_hero_nonce' => array(
			'careers_hero_badge'       => 'sanitize_text_field',
			'careers_hero_title'       => 'sanitize_text_field',
			'careers_hero_description' => 'sanitize_textarea_field',
			'careers_hero_button_text' => 'sanitize_text_field',
			'careers_hero_button_url'  => 'sanitize_text_field',
		),
		'chroma_careers_culture_nonce' => array(
			'careers_culture_title'       => 'sanitize_text_field',
			'careers_culture_description' => 'sanitize_textarea_field',
			'careers_benefit1_icon'       => 'sanitize_text_field',
			'careers_benefit1_title'      => 'sanitize_text_field',
			'careers_benefit1_desc'       => 'sanitize_textarea_field',
			'careers_benefit2_icon'       => 'sanitize_text_field',
			'careers_benefit2_title'      => 'sanitize_text_field',
			'careers_benefit2_desc'       => 'sanitize_textarea_field',
			'careers_benefit3_icon'       => 'sanitize_text_field',
			'careers_benefit3_title'      => 'sanitize_text_field',
			'careers_benefit3_desc'       => 'sanitize_textarea_field',
		),
		'chroma_careers_openings_nonce' => array(
			'careers_openings_title' => 'sanitize_text_field',
			'careers_job1_title'     => 'sanitize_text_field',
			'careers_job1_location'  => 'sanitize_text_field',
			'careers_job1_type'      => 'sanitize_text_field',
			'careers_job1_url'       => 'sanitize_text_field',
			'careers_job2_title'     => 'sanitize_text_field',
			'careers_job2_location'  => 'sanitize_text_field',
			'careers_job2_type'      => 'sanitize_text_field',
			'careers_job2_url'       => 'sanitize_text_field',
			'careers_job3_title'     => 'sanitize_text_field',
			'careers_job3_location'  => 'sanitize_text_field',
			'careers_job3_type'      => 'sanitize_text_field',
			'careers_job3_url'       => 'sanitize_text_field',
		),
		'chroma_careers_cta_nonce' => array(
			'careers_cta_title'       => 'sanitize_text_field',
			'careers_cta_description' => 'sanitize_textarea_field',
			'careers_cta_button_text' => 'sanitize_text_field',
			'careers_cta_button_url'  => 'esc_url_raw',
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
add_action( 'save_post', 'chroma_save_careers_page_meta' );

/**
 * Seed default values for Careers page when template is first applied
 */
function chroma_seed_careers_page_defaults( $post_id ) {
	// Check if this is a page
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	// Check if Careers Page template is being used
	$template = get_post_meta( $post_id, '_wp_page_template', true );
	if ( $template !== 'page-careers.php' ) {
		return;
	}

	// Check if already seeded
	$already_seeded = get_post_meta( $post_id, '_careers_defaults_seeded', true );
	if ( $already_seeded ) {
		return;
	}

	// Default values array
	$defaults = array(
		'careers_hero_badge'       => 'Join Our Team',
		'careers_hero_title'       => 'Shape the future. <br><span class="italic text-chroma-red">Love your work.</span>',
		'careers_hero_description' => 'We don\'t just hire staff; we invest in educators. At Chroma, you\'ll find a supportive community, career pathways, and the resources you need to change lives.',
		'careers_hero_button_text' => 'View Current Openings',
		'careers_hero_button_url'  => '#openings',

		'careers_culture_title'       => 'Why Chroma?',
		'careers_culture_description' => 'We take care of you, so you can take care of them.',

		'careers_benefit1_icon'  => 'fa-solid fa-money-bill-wave',
		'careers_benefit1_title' => 'Competitive Pay & 401k',
		'careers_benefit1_desc'  => 'Above-market salaries, annual performance bonuses, and retirement matching.',

		'careers_benefit2_icon'  => 'fa-solid fa-graduation-cap',
		'careers_benefit2_title' => 'Paid Tuition & CDA',
		'careers_benefit2_desc'  => 'We pay for your Child Development Associate (CDA) credential and offer college tuition assistance.',

		'careers_benefit3_icon'  => 'fa-solid fa-heart-pulse',
		'careers_benefit3_title' => 'Health & Wellness',
		'careers_benefit3_desc'  => 'Comprehensive medical, dental, and vision insurance, plus free childcare discounts.',

		'careers_openings_title' => 'Current Opportunities',

		'careers_job1_title'    => 'Lead Teacher (Pre-K)',
		'careers_job1_location' => 'Lawrenceville Campus',
		'careers_job1_type'     => 'Full Time',
		'careers_job1_url'      => '#apply',

		'careers_job2_title'    => 'Assistant Director',
		'careers_job2_location' => 'Marietta (East) Campus',
		'careers_job2_type'     => 'Full Time',
		'careers_job2_url'      => '#apply',

		'careers_job3_title'    => 'Infant Caregiver',
		'careers_job3_location' => 'Johns Creek Campus',
		'careers_job3_type'     => 'Full Time / Part Time',
		'careers_job3_url'      => '#apply',

		'careers_cta_title'       => 'Don\'t see your role?',
		'careers_cta_description' => 'We are always growing. Send us your resume and we\'ll keep it on file.',
		'careers_cta_button_text' => 'Email HR Team',
		'careers_cta_button_url'  => 'mailto:careers@chromaela.com',
	);

	// Populate all default values
	foreach ( $defaults as $meta_key => $default_value ) {
		update_post_meta( $post_id, $meta_key, $default_value );
	}

	// Mark as seeded
	update_post_meta( $post_id, '_careers_defaults_seeded', '1' );
}
add_action( 'save_post', 'chroma_seed_careers_page_defaults', 5 );

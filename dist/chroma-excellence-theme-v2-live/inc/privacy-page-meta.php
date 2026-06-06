<?php
/**
 * Privacy Policy Page Meta Boxes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register meta boxes for Privacy Policy page
 */
function chroma_privacy_page_meta_boxes() {
	add_meta_box(
		'privacy_page_general',
		'Privacy Policy Settings',
		'chroma_privacy_page_general_callback',
		'page',
		'normal',
		'high'
	);

	add_meta_box(
		'privacy_page_sections',
		'Privacy Policy Sections',
		'chroma_privacy_page_sections_callback',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'chroma_privacy_page_meta_boxes' );

/**
 * General Settings Meta Box Callback
 */
function chroma_privacy_page_general_callback( $post ) {
	wp_nonce_field( 'chroma_privacy_page_general_nonce', 'chroma_privacy_page_general_nonce' );

	$last_updated = get_post_meta( $post->ID, 'privacy_last_updated', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="privacy_last_updated">Last Updated Date</label></th>
			<td>
				<input type="text"
				       id="privacy_last_updated"
				       name="privacy_last_updated"
				       value="<?php echo esc_attr( $last_updated ); ?>"
				       class="regular-text"
				       placeholder="e.g., October 1, 2024" />
				<p class="description">The date this policy was last updated.</p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Sections Meta Box Callback
 */
function chroma_privacy_page_sections_callback( $post ) {
	wp_nonce_field( 'chroma_privacy_page_sections_nonce', 'chroma_privacy_page_sections_nonce' );

	// Section titles and field names
	$sections = array(
		1 => 'Section 1',
		2 => 'Section 2',
		3 => 'Section 3',
		4 => 'Section 4',
		5 => 'Section 5',
	);
	?>
	<style>
		.privacy-section-box {
			margin-bottom: 30px;
			padding: 20px;
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 4px;
		}
		.privacy-section-box h4 {
			margin-top: 0;
			padding-bottom: 10px;
			border-bottom: 1px solid #ddd;
		}
		.privacy-section-box .form-field {
			margin-bottom: 15px;
		}
		.privacy-section-box label {
			display: block;
			font-weight: 600;
			margin-bottom: 5px;
		}
		.privacy-section-box input[type="text"] {
			width: 100%;
		}
		.privacy-section-box textarea {
			width: 100%;
			min-height: 150px;
			font-family: monospace;
		}
		.privacy-section-box .description {
			font-size: 12px;
			color: #666;
			margin-top: 5px;
		}
	</style>

	<?php foreach ( $sections as $num => $label ) :
		$title = get_post_meta( $post->ID, "privacy_section{$num}_title", true );
		$content = get_post_meta( $post->ID, "privacy_section{$num}_content", true );
	?>
		<div class="privacy-section-box">
			<h4><?php echo esc_html( $label ); ?></h4>

			<div class="form-field">
				<label for="privacy_section<?php echo $num; ?>_title">Section Title</label>
				<input type="text"
				       id="privacy_section<?php echo $num; ?>_title"
				       name="privacy_section<?php echo $num; ?>_title"
				       value="<?php echo esc_attr( $title ); ?>"
				       placeholder="e.g., 1. Commitment to Privacy" />
			</div>

			<div class="form-field">
				<label for="privacy_section<?php echo $num; ?>_content">Section Content</label>
				<textarea id="privacy_section<?php echo $num; ?>_content"
				          name="privacy_section<?php echo $num; ?>_content"
				          rows="10"><?php echo esc_textarea( $content ); ?></textarea>
				<p class="description">
					Supports HTML: &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;a&gt;<br>
					For bullet lists, use: &lt;ul class="list-disc pl-6 mb-4 space-y-2"&gt;&lt;li&gt;Item&lt;/li&gt;&lt;/ul&gt;<br>
					For paragraphs, use: &lt;p class="mb-4"&gt;Text&lt;/p&gt;
				</p>
			</div>
		</div>
	<?php endforeach; ?>
	<?php
}

/**
 * Save Privacy Policy Page Meta
 */
function chroma_save_privacy_page_meta( $post_id ) {
	// Check if our nonces are set
	if ( ! isset( $_POST['chroma_privacy_page_general_nonce'] ) &&
	     ! isset( $_POST['chroma_privacy_page_sections_nonce'] ) ) {
		return;
	}

	// Verify nonces
	$general_nonce_valid = isset( $_POST['chroma_privacy_page_general_nonce'] ) &&
	                       wp_verify_nonce( $_POST['chroma_privacy_page_general_nonce'], 'chroma_privacy_page_general_nonce' );
	$sections_nonce_valid = isset( $_POST['chroma_privacy_page_sections_nonce'] ) &&
	                        wp_verify_nonce( $_POST['chroma_privacy_page_sections_nonce'], 'chroma_privacy_page_sections_nonce' );

	if ( ! $general_nonce_valid && ! $sections_nonce_valid ) {
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

	// Save Last Updated
	if ( isset( $_POST['privacy_last_updated'] ) ) {
		update_post_meta( $post_id, 'privacy_last_updated', sanitize_text_field( $_POST['privacy_last_updated'] ) );
	}

	// Save all 5 sections
	for ( $i = 1; $i <= 5; $i++ ) {
		if ( isset( $_POST["privacy_section{$i}_title"] ) ) {
			update_post_meta( $post_id, "privacy_section{$i}_title", sanitize_text_field( $_POST["privacy_section{$i}_title"] ) );
		}
		if ( isset( $_POST["privacy_section{$i}_content"] ) ) {
			update_post_meta( $post_id, "privacy_section{$i}_content", wp_kses_post( $_POST["privacy_section{$i}_content"] ) );
		}
	}
}
add_action( 'save_post', 'chroma_save_privacy_page_meta' );

/**
 * Auto-seed Privacy Policy page with default content
 */
function chroma_seed_privacy_page_defaults( $post_id ) {
	// Only run for pages
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	// Check if already seeded
	if ( get_post_meta( $post_id, '_privacy_defaults_seeded', true ) ) {
		return;
	}

	// Check if this is the Privacy Policy template
	$template = get_post_meta( $post_id, '_wp_page_template', true );
	if ( $template !== 'page-privacy.php' ) {
		return;
	}

	// Default content
	$defaults = array(
		'privacy_last_updated' => 'October 1, 2024',

		'privacy_section1_title' => '1. Commitment to Privacy',
		'privacy_section1_content' => '<p class="mb-4">Chroma Early Learning Academy ("Chroma", "we", "us") respects your privacy. This policy outlines how we collect, use, and protect the personal information of our families, students, and website visitors.</p>',

		'privacy_section2_title' => '2. Information Collection',
		'privacy_section2_content' => '<p class="mb-4">We collect information necessary for enrollment, safety, and communication, including:</p>
<ul class="list-disc pl-6 mb-4 space-y-2">
  <li>Student and parent names, addresses, and contact details.</li>
  <li>Medical and immunization records required by state law.</li>
  <li>Emergency contact information.</li>
  <li>Digital media (photos/videos) for classroom documentation (with consent).</li>
</ul>',

		'privacy_section3_title' => '3. Digital Security',
		'privacy_section3_content' => '<p class="mb-4">We use secure platforms (Procare) for all student data. Classroom camera feeds are encrypted and accessible only to authorized leadership for monitoring purposes; they are not publicly broadcast.</p>',

		'privacy_section4_title' => '4. Families\' Rights',
		'privacy_section4_content' => '<p class="mb-4">Chroma upholds the rights of all families to:</p>
<ul class="list-disc pl-6 mb-4 space-y-2">
  <li>Access their child\'s records upon request.</li>
  <li>Refuse the use of their child\'s image in marketing materials.</li>
  <li>Visit the center at any time during operating hours (Open Door Policy).</li>
</ul>',

		'privacy_section5_title' => '5. Contact Us',
		'privacy_section5_content' => '<p class="mb-4">If you have questions regarding this policy, please contact our corporate office at privacy@chromaela.com.</p>',
	);

	// Seed all defaults
	foreach ( $defaults as $meta_key => $default_value ) {
		update_post_meta( $post_id, $meta_key, $default_value );
	}

	// Mark as seeded
	update_post_meta( $post_id, '_privacy_defaults_seeded', '1' );
}
add_action( 'save_post', 'chroma_seed_privacy_page_defaults', 5 );

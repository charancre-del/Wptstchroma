<?php
/**
 * Curriculum Page Meta Boxes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Curriculum Page Meta Boxes
 */
function chroma_curriculum_page_meta_boxes() {
	add_meta_box(
		'chroma-curriculum-hero',
		__( 'Hero Section', 'chroma-excellence' ),
		'chroma_curriculum_hero_meta_box_render',
		'page',
		'normal',
		'high'
	);

	add_meta_box(
		'chroma-curriculum-framework',
		__( 'Prismpath Framework (5 Pillars)', 'chroma-excellence' ),
		'chroma_curriculum_framework_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-curriculum-timeline',
		__( 'Developmental Timeline', 'chroma-excellence' ),
		'chroma_curriculum_timeline_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-curriculum-environment',
		__( 'Environment (Third Teacher)', 'chroma-excellence' ),
		'chroma_curriculum_environment_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-curriculum-milestones',
		__( 'Measuring Milestones', 'chroma-excellence' ),
		'chroma_curriculum_milestones_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-curriculum-cta',
		__( 'CTA Section', 'chroma-excellence' ),
		'chroma_curriculum_cta_meta_box_render',
		'page',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'chroma_curriculum_page_meta_boxes' );

/**
 * Hero Section Meta Box
 */
function chroma_curriculum_hero_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_curriculum_hero_meta', 'chroma_curriculum_hero_nonce' );

	$hero_badge = get_post_meta( $post->ID, 'curriculum_hero_badge', true );
	$hero_title = get_post_meta( $post->ID, 'curriculum_hero_title', true );
	$hero_description = get_post_meta( $post->ID, 'curriculum_hero_description', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="curriculum_hero_badge">Badge Text</label></th>
			<td>
				<input type="text" id="curriculum_hero_badge" name="curriculum_hero_badge"
					   value="<?php echo esc_attr( $hero_badge ); ?>"
					   class="large-text" placeholder="e.g., The Chroma Difference" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_hero_title">Title</label></th>
			<td>
				<input type="text" id="curriculum_hero_title" name="curriculum_hero_title"
					   value="<?php echo esc_attr( $hero_title ); ?>"
					   class="large-text" placeholder="e.g., Scientific rigor. Joyful delivery." />
				<p class="description">Use &lt;br&gt; for line breaks and &lt;span class='italic text-chroma-green'&gt;text&lt;/span&gt; for green italic text</p>
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_hero_description">Description</label></th>
			<td>
				<textarea id="curriculum_hero_description" name="curriculum_hero_description"
						  rows="4" class="large-text"><?php echo esc_textarea( $hero_description ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Framework Section Meta Box (5 Pillars)
 */
function chroma_curriculum_framework_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_curriculum_framework_meta', 'chroma_curriculum_framework_nonce' );

	$framework_title = get_post_meta( $post->ID, 'curriculum_framework_title', true );
	$framework_description = get_post_meta( $post->ID, 'curriculum_framework_description', true );

	$pillars = array(
		array( 'name' => 'physical', 'label' => 'Physical (Red)', 'icon' => 'fa-solid fa-person-running' ),
		array( 'name' => 'emotional', 'label' => 'Emotional (Yellow)', 'icon' => 'fa-solid fa-face-smile' ),
		array( 'name' => 'social', 'label' => 'Social (Green)', 'icon' => 'fa-solid fa-users' ),
		array( 'name' => 'academic', 'label' => 'Academic (Blue)', 'icon' => 'fa-solid fa-brain' ),
		array( 'name' => 'creative', 'label' => 'Creative (Dark Blue)', 'icon' => 'fa-solid fa-palette' ),
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="curriculum_framework_title">Section Title</label></th>
			<td>
				<input type="text" id="curriculum_framework_title" name="curriculum_framework_title"
					   value="<?php echo esc_attr( $framework_title ); ?>"
					   class="large-text" placeholder="e.g., The Prismpath™ Framework" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_framework_description">Description</label></th>
			<td>
				<textarea id="curriculum_framework_description" name="curriculum_framework_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $framework_description ); ?></textarea>
			</td>
		</tr>
		<?php foreach ( $pillars as $pillar ) :
			$icon = get_post_meta( $post->ID, "curriculum_pillar_{$pillar['name']}_icon", true );
			$title = get_post_meta( $post->ID, "curriculum_pillar_{$pillar['name']}_title", true );
			$desc = get_post_meta( $post->ID, "curriculum_pillar_{$pillar['name']}_desc", true );
		?>
		<tr>
			<th colspan="2"><strong><?php echo esc_html( $pillar['label'] ); ?></strong></th>
		</tr>
		<tr>
			<th><label for="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_icon">Icon</label></th>
			<td>
				<input type="text" id="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_icon"
					   name="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_icon"
					   value="<?php echo esc_attr( $icon ); ?>"
					   placeholder="<?php echo esc_attr( $pillar['icon'] ); ?>" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_title">Title</label></th>
			<td>
				<input type="text" id="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_title"
					   name="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_title"
					   value="<?php echo esc_attr( $title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_desc">Description</label></th>
			<td>
				<textarea id="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_desc"
						  name="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_desc"
						  rows="2" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

/**
 * Timeline Section Meta Box
 */
function chroma_curriculum_timeline_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_curriculum_timeline_meta', 'chroma_curriculum_timeline_nonce' );

	$timeline_badge = get_post_meta( $post->ID, 'curriculum_timeline_badge', true );
	$timeline_title = get_post_meta( $post->ID, 'curriculum_timeline_title', true );
	$timeline_description = get_post_meta( $post->ID, 'curriculum_timeline_description', true );
	$timeline_image = get_post_meta( $post->ID, 'curriculum_timeline_image', true );

	$stages = array(
		array( 'name' => 'foundation', 'label' => 'Foundation (Red)' ),
		array( 'name' => 'discovery', 'label' => 'Discovery (Yellow)' ),
		array( 'name' => 'readiness', 'label' => 'Readiness (Green)' ),
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="curriculum_timeline_badge">Badge Text</label></th>
			<td>
				<input type="text" id="curriculum_timeline_badge" name="curriculum_timeline_badge"
					   value="<?php echo esc_attr( $timeline_badge ); ?>"
					   placeholder="e.g., Learning Journey" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_timeline_title">Section Title</label></th>
			<td>
				<input type="text" id="curriculum_timeline_title" name="curriculum_timeline_title"
					   value="<?php echo esc_attr( $timeline_title ); ?>"
					   class="large-text" placeholder="e.g., How learning evolves." />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_timeline_description">Description</label></th>
			<td>
				<textarea id="curriculum_timeline_description" name="curriculum_timeline_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $timeline_description ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_timeline_image">Image</label></th>
			<td>
				<input type="text" id="curriculum_timeline_image" name="curriculum_timeline_image"
					   value="<?php echo esc_attr( $timeline_image ); ?>"
					   class="large-text chroma-image-field" />
				<button type="button" class="button chroma-upload-button" data-field="curriculum_timeline_image">Select Image</button>
				<button type="button" class="button chroma-clear-button" data-field="curriculum_timeline_image">Clear</button>
			</td>
		</tr>
		<?php foreach ( $stages as $stage ) :
			$title = get_post_meta( $post->ID, "curriculum_stage_{$stage['name']}_title", true );
			$desc = get_post_meta( $post->ID, "curriculum_stage_{$stage['name']}_desc", true );
		?>
		<tr>
			<th colspan="2"><strong><?php echo esc_html( $stage['label'] ); ?></strong></th>
		</tr>
		<tr>
			<th><label for="curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_title">Title</label></th>
			<td>
				<input type="text" id="curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_title"
					   name="curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_title"
					   value="<?php echo esc_attr( $title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_desc">Description</label></th>
			<td>
				<textarea id="curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_desc"
						  name="curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_desc"
						  rows="2" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

/**
 * Environment Section Meta Box
 */
function chroma_curriculum_environment_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_curriculum_environment_meta', 'chroma_curriculum_environment_nonce' );

	$env_badge = get_post_meta( $post->ID, 'curriculum_env_badge', true );
	$env_title = get_post_meta( $post->ID, 'curriculum_env_title', true );
	$env_description = get_post_meta( $post->ID, 'curriculum_env_description', true );

	$zones = array(
		array( 'name' => 'construction', 'label' => 'Zone 1' ),
		array( 'name' => 'atelier', 'label' => 'Zone 2' ),
		array( 'name' => 'literacy', 'label' => 'Zone 3' ),
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="curriculum_env_badge">Badge Text</label></th>
			<td>
				<input type="text" id="curriculum_env_badge" name="curriculum_env_badge"
					   value="<?php echo esc_attr( $env_badge ); ?>"
					   placeholder="e.g., Environment" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_env_title">Section Title</label></th>
			<td>
				<input type="text" id="curriculum_env_title" name="curriculum_env_title"
					   value="<?php echo esc_attr( $env_title ); ?>"
					   class="large-text" placeholder="e.g., The classroom is the 'Third Teacher.'" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_env_description">Description</label></th>
			<td>
				<textarea id="curriculum_env_description" name="curriculum_env_description"
						  rows="4" class="large-text"><?php echo esc_textarea( $env_description ); ?></textarea>
			</td>
		</tr>
		<?php foreach ( $zones as $zone ) :
			$emoji = get_post_meta( $post->ID, "curriculum_zone_{$zone['name']}_emoji", true );
			$title = get_post_meta( $post->ID, "curriculum_zone_{$zone['name']}_title", true );
			$desc = get_post_meta( $post->ID, "curriculum_zone_{$zone['name']}_desc", true );
		?>
		<tr>
			<th colspan="2"><strong><?php echo esc_html( $zone['label'] ); ?></strong></th>
		</tr>
		<tr>
			<th><label for="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_emoji">Emoji</label></th>
			<td>
				<input type="text" id="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_emoji"
					   name="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_emoji"
					   value="<?php echo esc_attr( $emoji ); ?>"
					   placeholder="e.g., 🧱 or 🎨" style="width: 100px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_title">Title</label></th>
			<td>
				<input type="text" id="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_title"
					   name="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_title"
					   value="<?php echo esc_attr( $title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_desc">Description</label></th>
			<td>
				<textarea id="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_desc"
						  name="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_desc"
						  rows="2" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

/**
 * Milestones Section Meta Box
 */
function chroma_curriculum_milestones_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_curriculum_milestones_meta', 'chroma_curriculum_milestones_nonce' );

	$milestones_title = get_post_meta( $post->ID, 'curriculum_milestones_title', true );
	$milestones_subtitle = get_post_meta( $post->ID, 'curriculum_milestones_subtitle', true );

	$cards = array(
		array( 'name' => 'tracking', 'label' => 'Card 1 (Blue)' ),
		array( 'name' => 'screenings', 'label' => 'Card 2 (Red)' ),
		array( 'name' => 'assessments', 'label' => 'Card 3 (Yellow)' ),
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="curriculum_milestones_title">Section Title</label></th>
			<td>
				<input type="text" id="curriculum_milestones_title" name="curriculum_milestones_title"
					   value="<?php echo esc_attr( $milestones_title ); ?>"
					   class="large-text" placeholder="e.g., Measuring Milestones" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_milestones_subtitle">Subtitle</label></th>
			<td>
				<input type="text" id="curriculum_milestones_subtitle" name="curriculum_milestones_subtitle"
					   value="<?php echo esc_attr( $milestones_subtitle ); ?>"
					   class="large-text" placeholder="e.g., We don't just watch them grow..." />
			</td>
		</tr>
		<?php foreach ( $cards as $card ) :
			$icon = get_post_meta( $post->ID, "curriculum_milestone_{$card['name']}_icon", true );
			$title = get_post_meta( $post->ID, "curriculum_milestone_{$card['name']}_title", true );
			$desc = get_post_meta( $post->ID, "curriculum_milestone_{$card['name']}_desc", true );
			$bullet1 = get_post_meta( $post->ID, "curriculum_milestone_{$card['name']}_bullet1", true );
			$bullet2 = get_post_meta( $post->ID, "curriculum_milestone_{$card['name']}_bullet2", true );
		?>
		<tr>
			<th colspan="2"><strong><?php echo esc_html( $card['label'] ); ?></strong></th>
		</tr>
		<tr>
			<th><label for="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_icon">Icon</label></th>
			<td>
				<input type="text" id="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_icon"
					   name="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_icon"
					   value="<?php echo esc_attr( $icon ); ?>"
					   placeholder="e.g., fa-solid fa-chart-line" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_title">Title</label></th>
			<td>
				<input type="text" id="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_title"
					   name="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_title"
					   value="<?php echo esc_attr( $title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_desc">Description</label></th>
			<td>
				<textarea id="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_desc"
						  name="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_desc"
						  rows="3" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
				<p class="description">Use &lt;strong&gt;text&lt;/strong&gt; for bold text</p>
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet1">Bullet 1</label></th>
			<td>
				<input type="text" id="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet1"
					   name="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet1"
					   value="<?php echo esc_attr( $bullet1 ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet2">Bullet 2</label></th>
			<td>
				<input type="text" id="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet2"
					   name="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet2"
					   value="<?php echo esc_attr( $bullet2 ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

/**
 * CTA Section Meta Box
 */
function chroma_curriculum_cta_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_curriculum_cta_meta', 'chroma_curriculum_cta_nonce' );

	$cta_title = get_post_meta( $post->ID, 'curriculum_cta_title', true );
	$cta_description = get_post_meta( $post->ID, 'curriculum_cta_description', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="curriculum_cta_title">CTA Title</label></th>
			<td>
				<input type="text" id="curriculum_cta_title" name="curriculum_cta_title"
					   value="<?php echo esc_attr( $cta_title ); ?>"
					   class="large-text" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_cta_description">CTA Description</label></th>
			<td>
				<textarea id="curriculum_cta_description" name="curriculum_cta_description"
						  rows="2" class="large-text"><?php echo esc_textarea( $cta_description ); ?></textarea>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save Curriculum Page Meta
 */
function chroma_save_curriculum_page_meta( $post_id ) {
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	// Define all meta fields
	$meta_boxes = array(
		'chroma_curriculum_hero_nonce' => array(
			'curriculum_hero_badge'       => 'sanitize_text_field',
			'curriculum_hero_title'       => 'sanitize_text_field',
			'curriculum_hero_description' => 'sanitize_textarea_field',
		),
		'chroma_curriculum_framework_nonce' => array(
			'curriculum_framework_title'       => 'sanitize_text_field',
			'curriculum_framework_description' => 'sanitize_textarea_field',
			'curriculum_pillar_physical_icon'  => 'sanitize_text_field',
			'curriculum_pillar_physical_title' => 'sanitize_text_field',
			'curriculum_pillar_physical_desc'  => 'sanitize_textarea_field',
			'curriculum_pillar_emotional_icon' => 'sanitize_text_field',
			'curriculum_pillar_emotional_title'=> 'sanitize_text_field',
			'curriculum_pillar_emotional_desc' => 'sanitize_textarea_field',
			'curriculum_pillar_social_icon'    => 'sanitize_text_field',
			'curriculum_pillar_social_title'   => 'sanitize_text_field',
			'curriculum_pillar_social_desc'    => 'sanitize_textarea_field',
			'curriculum_pillar_academic_icon'  => 'sanitize_text_field',
			'curriculum_pillar_academic_title' => 'sanitize_text_field',
			'curriculum_pillar_academic_desc'  => 'sanitize_textarea_field',
			'curriculum_pillar_creative_icon'  => 'sanitize_text_field',
			'curriculum_pillar_creative_title' => 'sanitize_text_field',
			'curriculum_pillar_creative_desc'  => 'sanitize_textarea_field',
		),
		'chroma_curriculum_timeline_nonce' => array(
			'curriculum_timeline_badge'       => 'sanitize_text_field',
			'curriculum_timeline_title'       => 'sanitize_text_field',
			'curriculum_timeline_description' => 'sanitize_textarea_field',
			'curriculum_timeline_image'       => 'esc_url_raw',
			'curriculum_stage_foundation_title' => 'sanitize_text_field',
			'curriculum_stage_foundation_desc'  => 'sanitize_textarea_field',
			'curriculum_stage_discovery_title'  => 'sanitize_text_field',
			'curriculum_stage_discovery_desc'   => 'sanitize_textarea_field',
			'curriculum_stage_readiness_title'  => 'sanitize_text_field',
			'curriculum_stage_readiness_desc'   => 'sanitize_textarea_field',
		),
		'chroma_curriculum_environment_nonce' => array(
			'curriculum_env_badge'             => 'sanitize_text_field',
			'curriculum_env_title'             => 'sanitize_text_field',
			'curriculum_env_description'       => 'sanitize_textarea_field',
			'curriculum_zone_construction_emoji' => 'sanitize_text_field',
			'curriculum_zone_construction_title' => 'sanitize_text_field',
			'curriculum_zone_construction_desc'  => 'sanitize_textarea_field',
			'curriculum_zone_atelier_emoji'      => 'sanitize_text_field',
			'curriculum_zone_atelier_title'      => 'sanitize_text_field',
			'curriculum_zone_atelier_desc'       => 'sanitize_textarea_field',
			'curriculum_zone_literacy_emoji'     => 'sanitize_text_field',
			'curriculum_zone_literacy_title'     => 'sanitize_text_field',
			'curriculum_zone_literacy_desc'      => 'sanitize_textarea_field',
		),
		'chroma_curriculum_milestones_nonce' => array(
			'curriculum_milestones_title'           => 'sanitize_text_field',
			'curriculum_milestones_subtitle'        => 'sanitize_text_field',
			'curriculum_milestone_tracking_icon'    => 'sanitize_text_field',
			'curriculum_milestone_tracking_title'   => 'sanitize_text_field',
			'curriculum_milestone_tracking_desc'    => 'sanitize_textarea_field',
			'curriculum_milestone_tracking_bullet1' => 'sanitize_text_field',
			'curriculum_milestone_tracking_bullet2' => 'sanitize_text_field',
			'curriculum_milestone_screenings_icon'    => 'sanitize_text_field',
			'curriculum_milestone_screenings_title'   => 'sanitize_text_field',
			'curriculum_milestone_screenings_desc'    => 'sanitize_textarea_field',
			'curriculum_milestone_screenings_bullet1' => 'sanitize_text_field',
			'curriculum_milestone_screenings_bullet2' => 'sanitize_text_field',
			'curriculum_milestone_assessments_icon'    => 'sanitize_text_field',
			'curriculum_milestone_assessments_title'   => 'sanitize_text_field',
			'curriculum_milestone_assessments_desc'    => 'sanitize_textarea_field',
			'curriculum_milestone_assessments_bullet1' => 'sanitize_text_field',
			'curriculum_milestone_assessments_bullet2' => 'sanitize_text_field',
		),
		'chroma_curriculum_cta_nonce' => array(
			'curriculum_cta_title'       => 'sanitize_text_field',
			'curriculum_cta_description' => 'sanitize_textarea_field',
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
add_action( 'save_post', 'chroma_save_curriculum_page_meta' );

/**
 * Seed default values for Curriculum page
 */
function chroma_seed_curriculum_page_defaults( $post_id ) {
	if ( get_post_type( $post_id ) !== 'page' ) {
		return;
	}

	$template = get_post_meta( $post_id, '_wp_page_template', true );
	if ( $template !== 'page-curriculum.php' ) {
		return;
	}

	$already_seeded = get_post_meta( $post_id, '_curriculum_defaults_seeded', true );
	if ( $already_seeded ) {
		return;
	}

	$defaults = array(
		'curriculum_hero_badge'       => 'The Chroma Difference',
		'curriculum_hero_title'       => 'Scientific rigor. <br><span class="italic text-chroma-green">Joyful delivery.</span>',
		'curriculum_hero_description' => 'Our proprietary Prismpath™ curriculum isn\'t just about ABCs. It\'s a comprehensive framework designed to build the critical thinking, emotional intelligence, and social skills needed for the 21st century.',

		'curriculum_framework_title'       => 'The Prismpath™ Framework',
		'curriculum_framework_description' => 'Just as a prism refracts light into a spectrum, our curriculum refracts "play" into five distinct pillars of development. Every activity in our classrooms targets one or more of these areas.',

		'curriculum_pillar_physical_icon'  => 'fa-solid fa-person-running',
		'curriculum_pillar_physical_title' => 'Physical',
		'curriculum_pillar_physical_desc'  => 'Gross motor coordination, fine motor grip strength, sensory integration, and nutritional health.',

		'curriculum_pillar_emotional_icon' => 'fa-solid fa-face-smile',
		'curriculum_pillar_emotional_title'=> 'Emotional',
		'curriculum_pillar_emotional_desc' => 'Self-regulation, identifying feelings, building resilience, and developing a secure sense of self.',

		'curriculum_pillar_social_icon'    => 'fa-solid fa-users',
		'curriculum_pillar_social_title'   => 'Social',
		'curriculum_pillar_social_desc'    => 'Conflict resolution, collaboration, empathy, communication, and understanding community roles.',

		'curriculum_pillar_academic_icon'  => 'fa-solid fa-brain',
		'curriculum_pillar_academic_title' => 'Academic',
		'curriculum_pillar_academic_desc'  => 'Early literacy, logic & numeracy, scientific inquiry, critical thinking, and language acquisition.',

		'curriculum_pillar_creative_icon'  => 'fa-solid fa-palette',
		'curriculum_pillar_creative_title' => 'Creative',
		'curriculum_pillar_creative_desc'  => 'Divergent thinking, artistic expression, music & movement, and dramatic/imaginative play.',

		'curriculum_timeline_badge'       => 'Learning Journey',
		'curriculum_timeline_title'       => 'How learning evolves.',
		'curriculum_timeline_description' => 'Our curriculum is not static. It shifts and matures alongside your child, moving from sensory-based discovery to logic-based inquiry.',
		'curriculum_timeline_image'       => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=800&auto=format&fit=crop',

		'curriculum_stage_foundation_title' => 'Foundation (0-18 Months)',
		'curriculum_stage_foundation_desc'  => 'Focus on security and senses. Learning happens through touch, sound, and responsive caregiving.',

		'curriculum_stage_discovery_title'  => 'Discovery (18 Months - 3 Years)',
		'curriculum_stage_discovery_desc'   => 'Focus on autonomy and language. "I can do it!" is the theme as we support potty training and early speech.',

		'curriculum_stage_readiness_title'  => 'Readiness (3 Years - 5 Years)',
		'curriculum_stage_readiness_desc'   => 'Focus on executive function and logic. Multi-step projects, early writing, and complex social play prepare for Kindergarten.',

		'curriculum_env_badge'             => 'Environment',
		'curriculum_env_title'             => 'The classroom is the "Third Teacher."',
		'curriculum_env_description'       => 'We believe the environment itself acts as a teacher, guiding learning alongside our educators. Our classrooms are intentionally designed zones that invite exploration, curiosity, and independence without needing constant adult direction.',

		'curriculum_zone_construction_emoji' => '🧱',
		'curriculum_zone_construction_title' => 'Construction Zone',
		'curriculum_zone_construction_desc'  => 'Blocks and engineering tools to teach balance, gravity, and spatial reasoning.',

		'curriculum_zone_atelier_emoji'      => '🎨',
		'curriculum_zone_atelier_title'      => 'Atelier (Art Studio)',
		'curriculum_zone_atelier_desc'       => 'Open access to paints, clays, and loose parts for unrestricted creative expression.',

		'curriculum_zone_literacy_emoji'     => '📖',
		'curriculum_zone_literacy_title'     => 'Literacy Nook',
		'curriculum_zone_literacy_desc'      => 'Cozy, soft spaces with diverse books to foster a lifelong love of reading.',

		'curriculum_milestones_title'           => 'Measuring Milestones',
		'curriculum_milestones_subtitle'        => 'We don\'t just watch them grow; we measure it to ensure no child falls behind.',

		'curriculum_milestone_tracking_icon'    => 'fa-solid fa-chart-line',
		'curriculum_milestone_tracking_title'   => 'Daily Progress Tracking',
		'curriculum_milestone_tracking_desc'    => 'We use a digital portfolio system to capture daily moments of learning. From an infant\'s first roll to a preschooler\'s first written letter, these micro-wins are documented and shared with you in real-time.',
		'curriculum_milestone_tracking_bullet1' => 'Photo/Video Evidence',
		'curriculum_milestone_tracking_bullet2' => 'Daily Activity Reports',

		'curriculum_milestone_screenings_icon'    => 'fa-solid fa-magnifying-glass-chart',
		'curriculum_milestone_screenings_title'   => 'Developmental Screenings',
		'curriculum_milestone_screenings_desc'    => 'We utilize the <strong>ASQ-3 (Ages & Stages Questionnaires)</strong> standard to conduct formal screenings at key age intervals. This helps us identify strengths and potential areas for early intervention support proactively.',
		'curriculum_milestone_screenings_bullet1' => 'Conducted at 4, 8, 12, 18, 24 Months',
		'curriculum_milestone_screenings_bullet2' => 'Partnership with Specialists',

		'curriculum_milestone_assessments_icon'    => 'fa-solid fa-file-signature',
		'curriculum_milestone_assessments_title'   => 'Formal Assessments',
		'curriculum_milestone_assessments_desc'    => 'Twice a year (Fall and Spring), teachers conduct comprehensive assessments aligning with Georgia Early Learning and Development Standards (GELDS). These form the basis for our detailed Parent-Teacher Conferences.',
		'curriculum_milestone_assessments_bullet1' => 'Biannual Conferences',
		'curriculum_milestone_assessments_bullet2' => 'Individualized Lesson Planning',

		'curriculum_cta_title'       => 'See the curriculum in action.',
		'curriculum_cta_description' => 'Schedule a tour to see our "Third Teacher" classrooms and meet the educators bringing Prismpath™ to life.',
	);

	foreach ( $defaults as $meta_key => $default_value ) {
		update_post_meta( $post_id, $meta_key, $default_value );
	}

	update_post_meta( $post_id, '_curriculum_defaults_seeded', '1' );
}
add_action( 'save_post', 'chroma_seed_curriculum_page_defaults', 5 );

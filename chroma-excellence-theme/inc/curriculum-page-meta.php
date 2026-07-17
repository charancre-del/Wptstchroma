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
		'chroma-curriculum-continuum',
		__( 'Connected Learning Continuum', 'chroma-excellence' ),
		'chroma_curriculum_continuum_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-curriculum-studio',
		__( 'Curriculum Studio', 'chroma-excellence' ),
		'chroma_curriculum_studio_meta_box_render',
		'page',
		'normal',
		'default'
	);

	add_meta_box(
		'chroma-curriculum-support',
		__( 'PrismPath Support', 'chroma-excellence' ),
		'chroma_curriculum_support_meta_box_render',
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
 * Connected learning continuum meta box.
 */
function chroma_curriculum_continuum_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_curriculum_continuum_meta', 'chroma_curriculum_continuum_nonce' );

	$fields = array(
		'curriculum_continuum_badge'          => array( 'label' => 'Badge', 'type' => 'text' ),
		'curriculum_continuum_title'          => array( 'label' => 'Section Title', 'type' => 'text' ),
		'curriculum_continuum_intro'          => array( 'label' => 'Opening Description', 'type' => 'textarea' ),
		'curriculum_continuum_foundation'     => array( 'label' => 'Infant Foundation Description', 'type' => 'textarea' ),
		'curriculum_continuum_development'    => array( 'label' => 'Growing Independence Description', 'type' => 'textarea' ),
		'curriculum_continuum_example_title'  => array( 'label' => 'Example Heading', 'type' => 'text' ),
		'curriculum_continuum_infants_body'   => array( 'label' => 'Infants Card', 'type' => 'textarea' ),
		'curriculum_continuum_toddlers_body'  => array( 'label' => 'Toddlers Card', 'type' => 'textarea' ),
		'curriculum_continuum_preschool_body' => array( 'label' => 'Preschool Card', 'type' => 'textarea' ),
		'curriculum_continuum_prek_body'      => array( 'label' => 'Pre-K Card', 'type' => 'textarea' ),
		'curriculum_continuum_closing'        => array( 'label' => 'Closing Statement', 'type' => 'textarea' ),
	);
	?>
	<table class="form-table">
		<?php foreach ( $fields as $key => $field ) : ?>
			<tr>
				<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
				<td>
					<?php if ( 'textarea' === $field['type'] ) : ?>
						<textarea id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" rows="3" class="large-text"><?php echo esc_textarea( get_post_meta( $post->ID, $key, true ) ); ?></textarea>
						<textarea id="_chroma_es_<?php echo esc_attr( $key ); ?>" name="_chroma_es_<?php echo esc_attr( $key ); ?>" rows="3" class="large-text" placeholder="[ES] <?php echo esc_attr( $field['label'] ); ?>" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_' . $key, true ) ); ?></textarea>
					<?php else : ?>
						<input type="text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, $key, true ) ); ?>" class="large-text" />
						<input type="text" id="_chroma_es_<?php echo esc_attr( $key ); ?>" name="_chroma_es_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_' . $key, true ) ); ?>" class="large-text" placeholder="[ES] <?php echo esc_attr( $field['label'] ); ?>" style="margin-top: 5px;" />
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

/**
 * Curriculum Studio meta box.
 */
function chroma_curriculum_studio_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_curriculum_studio_meta', 'chroma_curriculum_studio_nonce' );

	$fields = array(
		'curriculum_studio_badge'            => array( 'label' => 'Badge', 'type' => 'text' ),
		'curriculum_studio_title'            => array( 'label' => 'Section Title', 'type' => 'text' ),
		'curriculum_studio_subtitle'         => array( 'label' => 'Section Subtitle', 'type' => 'text' ),
		'curriculum_studio_intro'            => array( 'label' => 'Opening Copy', 'type' => 'textarea' ),
		'curriculum_studio_insight'          => array( 'label' => 'Insight Copy', 'type' => 'textarea' ),
		'curriculum_studio_personalize'      => array( 'label' => 'Personalization Copy', 'type' => 'textarea' ),
		'curriculum_studio_process_heading'  => array( 'label' => 'Process Heading', 'type' => 'text' ),
		'curriculum_studio_family_title'     => array( 'label' => 'Step 1 Title', 'type' => 'text' ),
		'curriculum_studio_family_desc'      => array( 'label' => 'Step 1 Description', 'type' => 'textarea' ),
		'curriculum_studio_teacher_title'    => array( 'label' => 'Step 2 Title', 'type' => 'text' ),
		'curriculum_studio_teacher_desc'     => array( 'label' => 'Step 2 Description', 'type' => 'textarea' ),
		'curriculum_studio_plan_title'       => array( 'label' => 'Step 3 Title', 'type' => 'text' ),
		'curriculum_studio_plan_desc'        => array( 'label' => 'Step 3 Description', 'type' => 'textarea' ),
		'curriculum_studio_coaching_title'   => array( 'label' => 'Step 4 Title', 'type' => 'text' ),
		'curriculum_studio_coaching_desc'    => array( 'label' => 'Step 4 Description', 'type' => 'textarea' ),
		'curriculum_studio_teacher_heading'  => array( 'label' => 'Teacher Purpose Heading', 'type' => 'text' ),
		'curriculum_studio_teacher_body'     => array( 'label' => 'Teacher Purpose Copy', 'type' => 'textarea' ),
		'curriculum_studio_more_heading'     => array( 'label' => 'More Than Curriculum Heading', 'type' => 'text' ),
		'curriculum_studio_more_body'        => array( 'label' => 'More Than Curriculum Copy', 'type' => 'textarea' ),
		'curriculum_studio_callout'          => array( 'label' => 'Branded Callout', 'type' => 'text' ),
		'curriculum_studio_callout_subtitle' => array( 'label' => 'Callout Subtitle', 'type' => 'text' ),
		'curriculum_studio_closing'          => array( 'label' => 'Closing Quote', 'type' => 'textarea' ),
	);
	?>
	<table class="form-table">
		<?php foreach ( $fields as $key => $field ) : ?>
			<tr>
				<th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
				<td>
					<?php if ( 'textarea' === $field['type'] ) : ?>
						<textarea id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" rows="3" class="large-text"><?php echo esc_textarea( get_post_meta( $post->ID, $key, true ) ); ?></textarea>
						<textarea id="_chroma_es_<?php echo esc_attr( $key ); ?>" name="_chroma_es_<?php echo esc_attr( $key ); ?>" rows="3" class="large-text" placeholder="[ES] <?php echo esc_attr( $field['label'] ); ?>" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_' . $key, true ) ); ?></textarea>
					<?php else : ?>
						<input type="text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, $key, true ) ); ?>" class="large-text" />
						<input type="text" id="_chroma_es_<?php echo esc_attr( $key ); ?>" name="_chroma_es_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_' . $key, true ) ); ?>" class="large-text" placeholder="[ES] <?php echo esc_attr( $field['label'] ); ?>" style="margin-top: 5px;" />
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

/**
 * Hero Section Meta Box
 */
function chroma_curriculum_hero_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_curriculum_hero_meta', 'chroma_curriculum_hero_nonce' );

	$hero_badge = get_post_meta( $post->ID, 'curriculum_hero_badge', true );
	$hero_title = get_post_meta( $post->ID, 'curriculum_hero_title', true );
	$hero_description = get_post_meta( $post->ID, 'curriculum_hero_description', true );
	$hero_description_two = get_post_meta( $post->ID, 'curriculum_hero_description_two', true );
	?>
	<table class="form-table">
		<tr>
			<th><label for="curriculum_hero_badge">Badge Text</label></th>
			<td>
				<input type="text" id="curriculum_hero_badge" name="curriculum_hero_badge"
					   value="<?php echo esc_attr( $hero_badge ); ?>"
					   class="large-text" placeholder="e.g., The Chroma Difference" />
				<br>
				<input type="text" id="_chroma_es_curriculum_hero_badge" name="_chroma_es_curriculum_hero_badge"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_hero_badge', true ) ); ?>"
					   class="large-text" placeholder="[ES] Badge Text" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_hero_title">Title</label></th>
			<td>
				<input type="text" id="curriculum_hero_title" name="curriculum_hero_title"
					   value="<?php echo esc_attr( $hero_title ); ?>"
					   class="large-text" placeholder="e.g., Scientific rigor. Joyful delivery." />
				<br>
				<input type="text" id="_chroma_es_curriculum_hero_title" name="_chroma_es_curriculum_hero_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_hero_title', true ) ); ?>"
					   class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
				<p class="description">Use &lt;br&gt; for line breaks and &lt;span class='italic text-chroma-green'&gt;text&lt;/span&gt; for green italic text</p>
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_hero_description">Description</label></th>
			<td>
				<textarea id="curriculum_hero_description" name="curriculum_hero_description"
						  rows="4" class="large-text"><?php echo esc_textarea( $hero_description ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_hero_description" name="_chroma_es_curriculum_hero_description"
						  rows="4" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_curriculum_hero_description', true ) ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_hero_description_two">Description 2</label></th>
			<td>
				<textarea id="curriculum_hero_description_two" name="curriculum_hero_description_two"
						  rows="4" class="large-text"><?php echo esc_textarea( $hero_description_two ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_hero_description_two" name="_chroma_es_curriculum_hero_description_two"
						  rows="4" class="large-text" placeholder="[ES] Description 2" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_curriculum_hero_description_two', true ) ); ?></textarea>
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
				<br>
				<input type="text" id="_chroma_es_curriculum_framework_title" name="_chroma_es_curriculum_framework_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_framework_title', true ) ); ?>"
					   class="large-text" placeholder="[ES] Section Title" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_framework_description">Description</label></th>
			<td>
				<textarea id="curriculum_framework_description" name="curriculum_framework_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $framework_description ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_framework_description" name="_chroma_es_curriculum_framework_description"
						  rows="3" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_curriculum_framework_description', true ) ); ?></textarea>
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
				<br>
				<input type="text" id="_chroma_es_curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_title"
					   name="_chroma_es_curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, "_chroma_es_curriculum_pillar_{$pillar['name']}_title", true ) ); ?>"
					   class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_desc">Description</label></th>
			<td>
				<textarea id="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_desc"
						  name="curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_desc"
						  rows="2" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_desc"
						  name="_chroma_es_curriculum_pillar_<?php echo esc_attr( $pillar['name'] ); ?>_desc"
						  rows="2" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, "_chroma_es_curriculum_pillar_{$pillar['name']}_desc", true ) ); ?></textarea>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php
}

/**
 * PrismPath Support Meta Box
 */
function chroma_curriculum_support_meta_box_render( $post ) {
	wp_nonce_field( 'chroma_curriculum_support_meta', 'chroma_curriculum_support_nonce' );

	$support_badge = get_post_meta( $post->ID, 'curriculum_support_badge', true );
	$support_title = get_post_meta( $post->ID, 'curriculum_support_title', true );
	$support_description = get_post_meta( $post->ID, 'curriculum_support_description', true );
	$parent_overview_pdf_url = get_post_meta( $post->ID, 'curriculum_parent_overview_pdf_url', true );

	$cards = array(
		array( 'name' => 'notice', 'label' => 'Card 1: Notice growth patterns' ),
		array( 'name' => 'plan', 'label' => 'Card 2: Plan next steps' ),
		array( 'name' => 'coach', 'label' => 'Card 3: Support the classroom' ),
		array( 'name' => 'share', 'label' => 'Card 4: Share with families' ),
	);
	?>
	<table class="form-table">
		<tr>
			<th><label for="curriculum_support_badge">Badge</label></th>
			<td>
				<input type="text" id="curriculum_support_badge" name="curriculum_support_badge"
					   value="<?php echo esc_attr( $support_badge ); ?>"
					   class="large-text" placeholder="e.g., Teacher-supported growth" />
				<br>
				<input type="text" id="_chroma_es_curriculum_support_badge" name="_chroma_es_curriculum_support_badge"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_support_badge', true ) ); ?>"
					   class="large-text" placeholder="[ES] Badge" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_support_title">Section Title</label></th>
			<td>
				<input type="text" id="curriculum_support_title" name="curriculum_support_title"
					   value="<?php echo esc_attr( $support_title ); ?>"
					   class="large-text" placeholder="e.g., How PrismPath supports every classroom rhythm." />
				<br>
				<input type="text" id="_chroma_es_curriculum_support_title" name="_chroma_es_curriculum_support_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_support_title', true ) ); ?>"
					   class="large-text" placeholder="[ES] Section Title" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_support_description">Description</label></th>
			<td>
				<textarea id="curriculum_support_description" name="curriculum_support_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $support_description ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_support_description" name="_chroma_es_curriculum_support_description"
						  rows="3" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_curriculum_support_description', true ) ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_parent_overview_pdf_url">Parent Overview PDF URL</label></th>
			<td>
				<input type="url" id="curriculum_parent_overview_pdf_url" name="curriculum_parent_overview_pdf_url"
					   value="<?php echo esc_attr( $parent_overview_pdf_url ); ?>"
					   class="large-text" placeholder="Leave blank to use the bundled Prismpath parent overview PDF" />
				<br>
				<input type="url" id="_chroma_es_curriculum_parent_overview_pdf_url" name="_chroma_es_curriculum_parent_overview_pdf_url"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_parent_overview_pdf_url', true ) ); ?>"
					   class="large-text" placeholder="[ES] Parent Overview PDF URL" style="margin-top: 5px;" />
			</td>
		</tr>
		<?php foreach ( $cards as $card ) :
			$title = get_post_meta( $post->ID, "curriculum_support_{$card['name']}_title", true );
			$desc = get_post_meta( $post->ID, "curriculum_support_{$card['name']}_desc", true );
		?>
		<tr>
			<th colspan="2"><strong><?php echo esc_html( $card['label'] ); ?></strong></th>
		</tr>
		<tr>
			<th><label for="curriculum_support_<?php echo esc_attr( $card['name'] ); ?>_title">Title</label></th>
			<td>
				<input type="text" id="curriculum_support_<?php echo esc_attr( $card['name'] ); ?>_title"
					   name="curriculum_support_<?php echo esc_attr( $card['name'] ); ?>_title"
					   value="<?php echo esc_attr( $title ); ?>"
					   class="large-text" />
				<br>
				<input type="text" id="_chroma_es_curriculum_support_<?php echo esc_attr( $card['name'] ); ?>_title"
					   name="_chroma_es_curriculum_support_<?php echo esc_attr( $card['name'] ); ?>_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, "_chroma_es_curriculum_support_{$card['name']}_title", true ) ); ?>"
					   class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_support_<?php echo esc_attr( $card['name'] ); ?>_desc">Description</label></th>
			<td>
				<textarea id="curriculum_support_<?php echo esc_attr( $card['name'] ); ?>_desc"
						  name="curriculum_support_<?php echo esc_attr( $card['name'] ); ?>_desc"
						  rows="3" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_support_<?php echo esc_attr( $card['name'] ); ?>_desc"
						  name="_chroma_es_curriculum_support_<?php echo esc_attr( $card['name'] ); ?>_desc"
						  rows="3" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, "_chroma_es_curriculum_support_{$card['name']}_desc", true ) ); ?></textarea>
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
				<br>
				<input type="text" id="_chroma_es_curriculum_timeline_badge" name="_chroma_es_curriculum_timeline_badge"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_timeline_badge', true ) ); ?>"
					   placeholder="[ES] Badge" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_timeline_title">Section Title</label></th>
			<td>
				<input type="text" id="curriculum_timeline_title" name="curriculum_timeline_title"
					   value="<?php echo esc_attr( $timeline_title ); ?>"
					   class="large-text" placeholder="e.g., How learning evolves." />
				<br>
				<input type="text" id="_chroma_es_curriculum_timeline_title" name="_chroma_es_curriculum_timeline_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_timeline_title', true ) ); ?>"
					   class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_timeline_description">Description</label></th>
			<td>
				<textarea id="curriculum_timeline_description" name="curriculum_timeline_description"
						  rows="3" class="large-text"><?php echo esc_textarea( $timeline_description ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_timeline_description" name="_chroma_es_curriculum_timeline_description"
						  rows="3" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_curriculum_timeline_description', true ) ); ?></textarea>
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
				<br>
				<input type="text" id="_chroma_es_curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_title"
					   name="_chroma_es_curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, "_chroma_es_curriculum_stage_{$stage['name']}_title", true ) ); ?>"
					   class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_desc">Description</label></th>
			<td>
				<textarea id="curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_desc"
						  name="curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_desc"
						  rows="2" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_desc"
						  name="_chroma_es_curriculum_stage_<?php echo esc_attr( $stage['name'] ); ?>_desc"
						  rows="2" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, "_chroma_es_curriculum_stage_{$stage['name']}_desc", true ) ); ?></textarea>
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
				<br>
				<input type="text" id="_chroma_es_curriculum_env_badge" name="_chroma_es_curriculum_env_badge"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_env_badge', true ) ); ?>"
					   placeholder="[ES] Badge" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_env_title">Section Title</label></th>
			<td>
				<input type="text" id="curriculum_env_title" name="curriculum_env_title"
					   value="<?php echo esc_attr( $env_title ); ?>"
					   class="large-text" placeholder="e.g., The classroom is the 'Third Teacher.'" />
				<br>
				<input type="text" id="_chroma_es_curriculum_env_title" name="_chroma_es_curriculum_env_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_env_title', true ) ); ?>"
					   class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_env_description">Description</label></th>
			<td>
				<textarea id="curriculum_env_description" name="curriculum_env_description"
						  rows="4" class="large-text"><?php echo esc_textarea( $env_description ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_env_description" name="_chroma_es_curriculum_env_description"
						  rows="4" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_curriculum_env_description', true ) ); ?></textarea>
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
					   placeholder="e.g., ðŸ§± or ðŸŽ¨" style="width: 100px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_title">Title</label></th>
			<td>
				<input type="text" id="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_title"
					   name="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_title"
					   value="<?php echo esc_attr( $title ); ?>"
					   class="large-text" />
				<br>
				<input type="text" id="_chroma_es_curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_title"
					   name="_chroma_es_curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, "_chroma_es_curriculum_zone_{$zone['name']}_title", true ) ); ?>"
					   class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_desc">Description</label></th>
			<td>
				<textarea id="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_desc"
						  name="curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_desc"
						  rows="2" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_desc"
						  name="_chroma_es_curriculum_zone_<?php echo esc_attr( $zone['name'] ); ?>_desc"
						  rows="2" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, "_chroma_es_curriculum_zone_{$zone['name']}_desc", true ) ); ?></textarea>
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
				<br>
				<input type="text" id="_chroma_es_curriculum_milestones_title" name="_chroma_es_curriculum_milestones_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_milestones_title', true ) ); ?>"
					   class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_milestones_subtitle">Subtitle</label></th>
			<td>
				<input type="text" id="curriculum_milestones_subtitle" name="curriculum_milestones_subtitle"
					   value="<?php echo esc_attr( $milestones_subtitle ); ?>"
					   class="large-text" placeholder="e.g., We don't just watch them grow..." />
				<br>
				<input type="text" id="_chroma_es_curriculum_milestones_subtitle" name="_chroma_es_curriculum_milestones_subtitle"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_milestones_subtitle', true ) ); ?>"
					   class="large-text" placeholder="[ES] Subtitle" style="margin-top: 5px;" />
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
				<br>
				<input type="text" id="_chroma_es_curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_title"
					   name="_chroma_es_curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, "_chroma_es_curriculum_milestone_{$card['name']}_title", true ) ); ?>"
					   class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_desc">Description</label></th>
			<td>
				<textarea id="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_desc"
						  name="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_desc"
						  rows="3" class="large-text"><?php echo esc_textarea( $desc ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_desc"
						  name="_chroma_es_curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_desc"
						  rows="3" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, "_chroma_es_curriculum_milestone_{$card['name']}_desc", true ) ); ?></textarea>
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
				<br>
				<input type="text" id="_chroma_es_curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet1"
					   name="_chroma_es_curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet1"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, "_chroma_es_curriculum_milestone_{$card['name']}_bullet1", true ) ); ?>"
					   class="large-text" placeholder="[ES] Bullet 1" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet2">Bullet 2</label></th>
			<td>
				<input type="text" id="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet2"
					   name="curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet2"
					   value="<?php echo esc_attr( $bullet2 ); ?>"
					   class="large-text" />
				<br>
				<input type="text" id="_chroma_es_curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet2"
					   name="_chroma_es_curriculum_milestone_<?php echo esc_attr( $card['name'] ); ?>_bullet2"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, "_chroma_es_curriculum_milestone_{$card['name']}_bullet2", true ) ); ?>"
					   class="large-text" placeholder="[ES] Bullet 2" style="margin-top: 5px;" />
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
				<br>
				<input type="text" id="_chroma_es_curriculum_cta_title" name="_chroma_es_curriculum_cta_title"
					   value="<?php echo esc_attr( get_post_meta( $post->ID, '_chroma_es_curriculum_cta_title', true ) ); ?>"
					   class="large-text" placeholder="[ES] Title" style="margin-top: 5px;" />
			</td>
		</tr>
		<tr>
			<th><label for="curriculum_cta_description">CTA Description</label></th>
			<td>
				<textarea id="curriculum_cta_description" name="curriculum_cta_description"
						  rows="2" class="large-text"><?php echo esc_textarea( $cta_description ); ?></textarea>
				<br>
				<textarea id="_chroma_es_curriculum_cta_description" name="_chroma_es_curriculum_cta_description"
						  rows="2" class="large-text" placeholder="[ES] Description" style="margin-top: 5px;"><?php echo esc_textarea( get_post_meta( $post->ID, '_chroma_es_curriculum_cta_description', true ) ); ?></textarea>
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
			'_chroma_es_curriculum_hero_badge'       => 'sanitize_text_field',
			'curriculum_hero_title'       => 'wp_kses_post',
			'_chroma_es_curriculum_hero_title'       => 'wp_kses_post',
			'curriculum_hero_description' => 'sanitize_textarea_field',
			'_chroma_es_curriculum_hero_description' => 'sanitize_textarea_field',
			'curriculum_hero_description_two' => 'sanitize_textarea_field',
			'_chroma_es_curriculum_hero_description_two' => 'sanitize_textarea_field',
		),
		'chroma_curriculum_framework_nonce' => array(
			'curriculum_framework_title'       => 'sanitize_text_field',
			'_chroma_es_curriculum_framework_title'       => 'sanitize_text_field',
			'curriculum_framework_description' => 'sanitize_textarea_field',
			'_chroma_es_curriculum_framework_description' => 'sanitize_textarea_field',
			'curriculum_pillar_physical_icon'  => 'sanitize_text_field',
			'curriculum_pillar_physical_title' => 'sanitize_text_field',
			'_chroma_es_curriculum_pillar_physical_title' => 'sanitize_text_field',
			'curriculum_pillar_physical_desc'  => 'sanitize_textarea_field',
			'_chroma_es_curriculum_pillar_physical_desc'  => 'sanitize_textarea_field',
			'curriculum_pillar_emotional_icon' => 'sanitize_text_field',
			'curriculum_pillar_emotional_title'=> 'sanitize_text_field',
			'_chroma_es_curriculum_pillar_emotional_title'=> 'sanitize_text_field',
			'curriculum_pillar_emotional_desc' => 'sanitize_textarea_field',
			'_chroma_es_curriculum_pillar_emotional_desc' => 'sanitize_textarea_field',
			'curriculum_pillar_social_icon'    => 'sanitize_text_field',
			'curriculum_pillar_social_title'   => 'sanitize_text_field',
			'_chroma_es_curriculum_pillar_social_title'   => 'sanitize_text_field',
			'curriculum_pillar_social_desc'    => 'sanitize_textarea_field',
			'_chroma_es_curriculum_pillar_social_desc'    => 'sanitize_textarea_field',
			'curriculum_pillar_academic_icon'  => 'sanitize_text_field',
			'curriculum_pillar_academic_title' => 'sanitize_text_field',
			'_chroma_es_curriculum_pillar_academic_title' => 'sanitize_text_field',
			'curriculum_pillar_academic_desc'  => 'sanitize_textarea_field',
			'_chroma_es_curriculum_pillar_academic_desc'  => 'sanitize_textarea_field',
			'curriculum_pillar_creative_icon'  => 'sanitize_text_field',
			'curriculum_pillar_creative_title' => 'sanitize_text_field',
			'_chroma_es_curriculum_pillar_creative_title' => 'sanitize_text_field',
			'curriculum_pillar_creative_desc'  => 'sanitize_textarea_field',
			'_chroma_es_curriculum_pillar_creative_desc'  => 'sanitize_textarea_field',
		),
		'chroma_curriculum_support_nonce' => array(
			'curriculum_support_badge'       => 'sanitize_text_field',
			'_chroma_es_curriculum_support_badge'       => 'sanitize_text_field',
			'curriculum_support_title'       => 'sanitize_text_field',
			'_chroma_es_curriculum_support_title'       => 'sanitize_text_field',
			'curriculum_support_description' => 'sanitize_textarea_field',
			'_chroma_es_curriculum_support_description' => 'sanitize_textarea_field',
			'curriculum_parent_overview_pdf_url' => 'esc_url_raw',
			'_chroma_es_curriculum_parent_overview_pdf_url' => 'esc_url_raw',
			'curriculum_support_notice_title' => 'sanitize_text_field',
			'_chroma_es_curriculum_support_notice_title' => 'sanitize_text_field',
			'curriculum_support_notice_desc' => 'sanitize_textarea_field',
			'_chroma_es_curriculum_support_notice_desc' => 'sanitize_textarea_field',
			'curriculum_support_plan_title' => 'sanitize_text_field',
			'_chroma_es_curriculum_support_plan_title' => 'sanitize_text_field',
			'curriculum_support_plan_desc' => 'sanitize_textarea_field',
			'_chroma_es_curriculum_support_plan_desc' => 'sanitize_textarea_field',
			'curriculum_support_coach_title' => 'sanitize_text_field',
			'_chroma_es_curriculum_support_coach_title' => 'sanitize_text_field',
			'curriculum_support_coach_desc' => 'sanitize_textarea_field',
			'_chroma_es_curriculum_support_coach_desc' => 'sanitize_textarea_field',
			'curriculum_support_share_title' => 'sanitize_text_field',
			'_chroma_es_curriculum_support_share_title' => 'sanitize_text_field',
			'curriculum_support_share_desc' => 'sanitize_textarea_field',
			'_chroma_es_curriculum_support_share_desc' => 'sanitize_textarea_field',
		),
		'chroma_curriculum_timeline_nonce' => array(
			'curriculum_timeline_badge'       => 'sanitize_text_field',
			'_chroma_es_curriculum_timeline_badge'       => 'sanitize_text_field',
			'curriculum_timeline_title'       => 'sanitize_text_field',
			'_chroma_es_curriculum_timeline_title'       => 'sanitize_text_field',
			'curriculum_timeline_description' => 'sanitize_textarea_field',
			'_chroma_es_curriculum_timeline_description' => 'sanitize_textarea_field',
			'curriculum_timeline_image'       => 'esc_url_raw',
			'curriculum_stage_foundation_title' => 'sanitize_text_field',
			'_chroma_es_curriculum_stage_foundation_title' => 'sanitize_text_field',
			'curriculum_stage_foundation_desc'  => 'sanitize_textarea_field',
			'_chroma_es_curriculum_stage_foundation_desc'  => 'sanitize_textarea_field',
			'curriculum_stage_discovery_title'  => 'sanitize_text_field',
			'_chroma_es_curriculum_stage_discovery_title'  => 'sanitize_text_field',
			'curriculum_stage_discovery_desc'   => 'sanitize_textarea_field',
			'_chroma_es_curriculum_stage_discovery_desc'   => 'sanitize_textarea_field',
			'curriculum_stage_readiness_title'  => 'sanitize_text_field',
			'_chroma_es_curriculum_stage_readiness_title'  => 'sanitize_text_field',
			'curriculum_stage_readiness_desc'   => 'sanitize_textarea_field',
			'_chroma_es_curriculum_stage_readiness_desc'   => 'sanitize_textarea_field',
		),
		'chroma_curriculum_continuum_nonce' => array(
			'curriculum_continuum_badge'                    => 'sanitize_text_field',
			'_chroma_es_curriculum_continuum_badge'         => 'sanitize_text_field',
			'curriculum_continuum_title'                    => 'sanitize_text_field',
			'_chroma_es_curriculum_continuum_title'         => 'sanitize_text_field',
			'curriculum_continuum_intro'                    => 'sanitize_textarea_field',
			'_chroma_es_curriculum_continuum_intro'         => 'sanitize_textarea_field',
			'curriculum_continuum_foundation'               => 'sanitize_textarea_field',
			'_chroma_es_curriculum_continuum_foundation'    => 'sanitize_textarea_field',
			'curriculum_continuum_development'              => 'sanitize_textarea_field',
			'_chroma_es_curriculum_continuum_development'   => 'sanitize_textarea_field',
			'curriculum_continuum_example_title'            => 'sanitize_text_field',
			'_chroma_es_curriculum_continuum_example_title' => 'sanitize_text_field',
			'curriculum_continuum_infants_body'             => 'sanitize_textarea_field',
			'_chroma_es_curriculum_continuum_infants_body'  => 'sanitize_textarea_field',
			'curriculum_continuum_toddlers_body'            => 'sanitize_textarea_field',
			'_chroma_es_curriculum_continuum_toddlers_body' => 'sanitize_textarea_field',
			'curriculum_continuum_preschool_body'           => 'sanitize_textarea_field',
			'_chroma_es_curriculum_continuum_preschool_body' => 'sanitize_textarea_field',
			'curriculum_continuum_prek_body'                => 'sanitize_textarea_field',
			'_chroma_es_curriculum_continuum_prek_body'     => 'sanitize_textarea_field',
			'curriculum_continuum_closing'                  => 'sanitize_textarea_field',
			'_chroma_es_curriculum_continuum_closing'       => 'sanitize_textarea_field',
		),
		'chroma_curriculum_studio_nonce' => array(
			'curriculum_studio_badge'                       => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_badge'            => 'sanitize_text_field',
			'curriculum_studio_title'                       => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_title'            => 'sanitize_text_field',
			'curriculum_studio_subtitle'                    => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_subtitle'         => 'sanitize_text_field',
			'curriculum_studio_intro'                       => 'sanitize_textarea_field',
			'_chroma_es_curriculum_studio_intro'            => 'sanitize_textarea_field',
			'curriculum_studio_insight'                     => 'sanitize_textarea_field',
			'_chroma_es_curriculum_studio_insight'          => 'sanitize_textarea_field',
			'curriculum_studio_personalize'                 => 'sanitize_textarea_field',
			'_chroma_es_curriculum_studio_personalize'      => 'sanitize_textarea_field',
			'curriculum_studio_process_heading'             => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_process_heading'  => 'sanitize_text_field',
			'curriculum_studio_family_title'                => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_family_title'     => 'sanitize_text_field',
			'curriculum_studio_family_desc'                 => 'sanitize_textarea_field',
			'_chroma_es_curriculum_studio_family_desc'      => 'sanitize_textarea_field',
			'curriculum_studio_teacher_title'               => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_teacher_title'    => 'sanitize_text_field',
			'curriculum_studio_teacher_desc'                => 'sanitize_textarea_field',
			'_chroma_es_curriculum_studio_teacher_desc'     => 'sanitize_textarea_field',
			'curriculum_studio_plan_title'                  => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_plan_title'       => 'sanitize_text_field',
			'curriculum_studio_plan_desc'                   => 'sanitize_textarea_field',
			'_chroma_es_curriculum_studio_plan_desc'        => 'sanitize_textarea_field',
			'curriculum_studio_coaching_title'              => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_coaching_title'   => 'sanitize_text_field',
			'curriculum_studio_coaching_desc'               => 'sanitize_textarea_field',
			'_chroma_es_curriculum_studio_coaching_desc'    => 'sanitize_textarea_field',
			'curriculum_studio_teacher_heading'             => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_teacher_heading'  => 'sanitize_text_field',
			'curriculum_studio_teacher_body'                => 'sanitize_textarea_field',
			'_chroma_es_curriculum_studio_teacher_body'     => 'sanitize_textarea_field',
			'curriculum_studio_more_heading'                => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_more_heading'     => 'sanitize_text_field',
			'curriculum_studio_more_body'                   => 'sanitize_textarea_field',
			'_chroma_es_curriculum_studio_more_body'        => 'sanitize_textarea_field',
			'curriculum_studio_callout'                     => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_callout'          => 'sanitize_text_field',
			'curriculum_studio_callout_subtitle'            => 'sanitize_text_field',
			'_chroma_es_curriculum_studio_callout_subtitle' => 'sanitize_text_field',
			'curriculum_studio_closing'                     => 'sanitize_textarea_field',
			'_chroma_es_curriculum_studio_closing'          => 'sanitize_textarea_field',
		),
		'chroma_curriculum_environment_nonce' => array(
			'curriculum_env_badge'             => 'sanitize_text_field',
			'_chroma_es_curriculum_env_badge'             => 'sanitize_text_field',
			'curriculum_env_title'             => 'sanitize_text_field',
			'_chroma_es_curriculum_env_title'             => 'sanitize_text_field',
			'curriculum_env_description'       => 'sanitize_textarea_field',
			'_chroma_es_curriculum_env_description'       => 'sanitize_textarea_field',
			'curriculum_zone_construction_emoji' => 'sanitize_text_field',
			'curriculum_zone_construction_title' => 'sanitize_text_field',
			'_chroma_es_curriculum_zone_construction_title' => 'sanitize_text_field',
			'curriculum_zone_construction_desc'  => 'sanitize_textarea_field',
			'_chroma_es_curriculum_zone_construction_desc'  => 'sanitize_textarea_field',
			'curriculum_zone_atelier_emoji'      => 'sanitize_text_field',
			'curriculum_zone_atelier_title'      => 'sanitize_text_field',
			'_chroma_es_curriculum_zone_atelier_title'      => 'sanitize_text_field',
			'curriculum_zone_atelier_desc'       => 'sanitize_textarea_field',
			'_chroma_es_curriculum_zone_atelier_desc'       => 'sanitize_textarea_field',
			'curriculum_zone_literacy_emoji'     => 'sanitize_text_field',
			'curriculum_zone_literacy_title'     => 'sanitize_text_field',
			'_chroma_es_curriculum_zone_literacy_title'     => 'sanitize_text_field',
			'curriculum_zone_literacy_desc'      => 'sanitize_textarea_field',
			'_chroma_es_curriculum_zone_literacy_desc'      => 'sanitize_textarea_field',
		),
		'chroma_curriculum_milestones_nonce' => array(
			'curriculum_milestones_title'           => 'sanitize_text_field',
			'_chroma_es_curriculum_milestones_title'           => 'sanitize_text_field',
			'curriculum_milestones_subtitle'        => 'sanitize_text_field',
			'_chroma_es_curriculum_milestones_subtitle'        => 'sanitize_text_field',
			'curriculum_milestone_tracking_icon'    => 'sanitize_text_field',
			'curriculum_milestone_tracking_title'   => 'sanitize_text_field',
			'_chroma_es_curriculum_milestone_tracking_title'   => 'sanitize_text_field',
			'curriculum_milestone_tracking_desc'    => 'sanitize_textarea_field',
			'_chroma_es_curriculum_milestone_tracking_desc'    => 'sanitize_textarea_field',
			'curriculum_milestone_tracking_bullet1' => 'sanitize_text_field',
			'_chroma_es_curriculum_milestone_tracking_bullet1' => 'sanitize_text_field',
			'curriculum_milestone_tracking_bullet2' => 'sanitize_text_field',
			'_chroma_es_curriculum_milestone_tracking_bullet2' => 'sanitize_text_field',
			'curriculum_milestone_screenings_icon'    => 'sanitize_text_field',
			'curriculum_milestone_screenings_title'   => 'sanitize_text_field',
			'_chroma_es_curriculum_milestone_screenings_title'   => 'sanitize_text_field',
			'curriculum_milestone_screenings_desc'    => 'sanitize_textarea_field',
			'_chroma_es_curriculum_milestone_screenings_desc'    => 'sanitize_textarea_field',
			'curriculum_milestone_screenings_bullet1' => 'sanitize_text_field',
			'_chroma_es_curriculum_milestone_screenings_bullet1' => 'sanitize_text_field',
			'curriculum_milestone_screenings_bullet2' => 'sanitize_text_field',
			'_chroma_es_curriculum_milestone_screenings_bullet2' => 'sanitize_text_field',
			'curriculum_milestone_assessments_icon'    => 'sanitize_text_field',
			'curriculum_milestone_assessments_title'   => 'sanitize_text_field',
			'_chroma_es_curriculum_milestone_assessments_title'   => 'sanitize_text_field',
			'curriculum_milestone_assessments_desc'    => 'sanitize_textarea_field',
			'_chroma_es_curriculum_milestone_assessments_desc'    => 'sanitize_textarea_field',
			'curriculum_milestone_assessments_bullet1' => 'sanitize_text_field',
			'_chroma_es_curriculum_milestone_assessments_bullet1' => 'sanitize_text_field',
			'curriculum_milestone_assessments_bullet2' => 'sanitize_text_field',
			'_chroma_es_curriculum_milestone_assessments_bullet2' => 'sanitize_text_field',
		),
		'chroma_curriculum_cta_nonce' => array(
			'curriculum_cta_title'       => 'sanitize_text_field',
			'_chroma_es_curriculum_cta_title'       => 'sanitize_text_field',
			'curriculum_cta_description' => 'sanitize_textarea_field',
			'_chroma_es_curriculum_cta_description' => 'sanitize_textarea_field',
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
		'curriculum_hero_badge'       => 'The Prismpath method',
		'curriculum_hero_title'       => 'Whole-child learning that grows with <span class="pp-script">your child.</span>',
		'curriculum_hero_description' => 'Prismpath is Chroma Early Learning Academy\'s curriculum framework for helping children grow across five connected areas: physical, emotional, social, academic, and creative development.',
		'curriculum_hero_description_two' => 'It is simple on purpose: teachers notice what children are practicing, plan meaningful next steps, and shape classroom experiences around the children in front of them.',

		'curriculum_framework_title'       => 'A clearer way to understand the whole child.',
		'curriculum_framework_description' => 'Children do not grow in straight lines. A block tower can build hand strength, problem-solving, language, peer cooperation, and imagination all at once. Prismpath helps teachers see those connected moments and plan from them.',

		'curriculum_support_badge'       => 'How it works',
		'curriculum_support_title'       => 'Teachers are supported, so children are seen clearly.',
		'curriculum_support_description' => 'Prismpath is not a script. It is a teacher-guided rhythm: observe children, understand what growth is emerging, plan the next meaningful experience, then share progress with families.',
		'curriculum_support_notice_title' => 'Notice',
		'curriculum_support_notice_desc' => 'Teachers watch for patterns in play, language, movement, relationships, and problem-solving.',
		'curriculum_support_plan_title' => 'Plan',
		'curriculum_support_plan_desc' => 'Lesson plans are shaped around the children in the room and the next skills they are ready to practice.',
		'curriculum_support_coach_title' => 'Support',
		'curriculum_support_coach_desc' => 'Classroom leaders and curriculum teams help teachers choose helpful coaching and materials.',
		'curriculum_support_share_title' => 'Share',
		'curriculum_support_share_desc' => 'Families see photos, notes, progress reports, and simple ways to continue learning at home.',

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

		'curriculum_continuum_badge'          => 'A connected learning continuum',
		'curriculum_continuum_title'          => 'Introduced Early. Deepened Over Time.',
		'curriculum_continuum_intro'          => 'PrismPath™ is designed as one connected learning journey from infancy through Pre-K. Children encounter the same foundational concepts across every age group, while the experience becomes more detailed, intentional, and challenging as they grow.',
		'curriculum_continuum_foundation'     => 'For infants, learning begins through sights, sounds, movement, touch, repetition, and responsive interaction. These early experiences create familiarity and establish the foundation for later understanding.',
		'curriculum_continuum_development'    => 'As children develop, they begin recognizing patterns, using language, making connections, solving problems, and applying what they have learned with greater independence.',
		'curriculum_continuum_example_title'  => 'The Same Concept. A New Level at Every Age.',
		'curriculum_continuum_infants_body'   => 'Babies hear alphabet songs, listen to stories, explore books, and become familiar with the sounds and rhythms of language.',
		'curriculum_continuum_toddlers_body'  => 'Children repeat words, recognize familiar pictures and symbols, participate in rhymes, and begin connecting language with meaning.',
		'curriculum_continuum_preschool_body' => 'Children identify letters, build vocabulary, recognize sound patterns, and begin connecting letters with their sounds.',
		'curriculum_continuum_prek_body'      => 'Children develop phonological and phonemic awareness, practice blending and separating sounds, explore early writing, and build the foundations for reading through phonics.',
		'curriculum_continuum_closing'        => 'What begins as early exposure becomes recognition, understanding, and eventually confident application. Each stage prepares the child for the next—without rushing development or losing the joy of discovery.',

		'curriculum_studio_badge'            => 'Curriculum Studio',
		'curriculum_studio_title'            => 'Personalized Learning Starts with Better Insight',
		'curriculum_studio_subtitle'         => 'Meet the Chroma Curriculum Studio',
		'curriculum_studio_intro'            => 'Every child develops differently, and every classroom has its own combination of strengths, interests, learning styles, and areas for growth. That is why Chroma Early Learning Academy developed the Curriculum Studio—our proprietary, in-house platform designed to help transform real insights about children into more responsive learning experiences.',
		'curriculum_studio_insight'          => 'Curriculum Studio brings together parent developmental screening responses, teacher observations, classroom notes, emerging interests, and documented learning progress. It helps our teachers and Education Team understand what children are ready to explore, which skills may need reinforcement, and where additional challenges or enrichment can be introduced.',
		'curriculum_studio_personalize'      => 'Rather than delivering the same generic lesson plan in every classroom, Curriculum Studio helps teachers adapt PrismPath™ activities, materials, instructional approaches, and levels of support to the children they are actually teaching.',
		'curriculum_studio_process_heading'  => 'One Connected System Supporting Every Level of Learning',
		'curriculum_studio_family_title'     => 'Families Share Valuable Insight',
		'curriculum_studio_family_desc'      => 'Parents provide important information through developmental screenings and ongoing communication, helping us better understand each child’s interests, routines, strengths, experiences, and developmental needs.',
		'curriculum_studio_teacher_title'    => 'Teachers Observe Learning in Action',
		'curriculum_studio_teacher_desc'     => 'Our teachers document classroom observations, emerging skills, interests, participation, progress, and areas where children may benefit from additional practice or enrichment.',
		'curriculum_studio_plan_title'       => 'Curriculum Studio Helps Personalize the Plan',
		'curriculum_studio_plan_desc'        => 'Curriculum Studio brings these insights together to help teachers differentiate PrismPath™ lesson plans for their classrooms. Children can explore the same foundational concept through different activities, materials, levels of complexity, and methods of engagement based on their developmental readiness.',
		'curriculum_studio_coaching_title'   => 'Our Education Team Strengthens Classroom Practice',
		'curriculum_studio_coaching_desc'    => 'The insight does not stop with lesson planning. Curriculum Studio also helps our Teacher Training Staff and Education Team identify classroom-specific opportunities for professional development, instructional coaching, modeling, and support.',
		'curriculum_studio_teacher_heading'  => 'Personalized for Children. Purposeful for Teachers.',
		'curriculum_studio_teacher_body'     => 'Curriculum Studio creates a continuous connection between the child, the family, the classroom teacher, and Chroma’s Education Team. As teachers document progress and children develop new skills, future learning experiences can evolve while our training and education teams gain better insight into where teachers may benefit from coaching, classroom modeling, additional resources, or targeted professional development.',
		'curriculum_studio_more_heading'     => 'More Than a Curriculum',
		'curriculum_studio_more_body'        => 'Many early learning programs purchase a curriculum and distribute the same lesson plans across every classroom. Chroma has built something different. PrismPath™ provides the educational foundation. Curriculum Studio helps personalize how that foundation is delivered, while also helping our Education Team strengthen the teachers responsible for bringing it to life.',
		'curriculum_studio_callout'          => 'Proprietary Technology. Personalized Learning. Stronger Teachers.',
		'curriculum_studio_callout_subtitle' => 'Designed in-house by Chroma to help every classroom grow.',
		'curriculum_studio_closing'          => 'At Chroma, personalization is not an occasional adjustment. It is part of the system behind how we plan, teach, train, and continuously improve.',
		'curriculum_stage_foundation_title' => 'Foundation (0-18 Months)',
		'curriculum_stage_foundation_desc'  => 'Focus on security and senses. Learning happens through touch, sound, and responsive caregiving.',

		'curriculum_stage_discovery_title'  => 'Discovery (18 Months - 3 Years)',
		'curriculum_stage_discovery_desc'   => 'Focus on autonomy and language. "I can do it!" is the theme as we support potty training and early speech.',

		'curriculum_stage_readiness_title'  => 'Readiness (3 Years - 5 Years)',
		'curriculum_stage_readiness_desc'   => 'Focus on executive function and logic. Multi-step projects, early writing, and complex social play prepare for Kindergarten.',

		'curriculum_env_badge'             => 'Environment',
		'curriculum_env_title'             => 'The classroom is the "Third Teacher."',
		'curriculum_env_description'       => 'We believe the environment itself acts as a teacher, guiding learning alongside our educators. Our classrooms are intentionally designed zones that invite exploration, curiosity, and independence without needing constant adult direction.',

		'curriculum_zone_construction_emoji' => 'ðŸ§±',
		'curriculum_zone_construction_title' => 'Construction Zone',
		'curriculum_zone_construction_desc'  => 'Blocks and engineering tools to teach balance, gravity, and spatial reasoning.',

		'curriculum_zone_atelier_emoji'      => 'ðŸŽ¨',
		'curriculum_zone_atelier_title'      => 'Atelier (Art Studio)',
		'curriculum_zone_atelier_desc'       => 'Open access to paints, clays, and loose parts for unrestricted creative expression.',

		'curriculum_zone_literacy_emoji'     => 'ðŸ“–',
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

/**
 * Repair legacy punctuation that was saved before UTF-8 defaults were fixed.
 *
 * Only exact corrupted tokens are replaced, so custom curriculum copy remains
 * untouched. The migration runs once per site and also repairs Spanish values
 * when the same legacy tokens are present there.
 */
function chroma_repair_curriculum_legacy_punctuation() {
	$repair_version = '1';
	if ( $repair_version === get_option( 'chroma_curriculum_text_repair_version' ) ) {
		return;
	}

	$page_ids = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template',
			'meta_value'     => 'page-curriculum.php',
		)
	);

	$meta_keys = array(
		'curriculum_continuum_intro',
		'_chroma_es_curriculum_continuum_intro',
		'curriculum_continuum_closing',
		'_chroma_es_curriculum_continuum_closing',
		'curriculum_cta_description',
		'_chroma_es_curriculum_cta_description',
	);

	foreach ( $page_ids as $page_id ) {
		foreach ( $meta_keys as $meta_key ) {
			$value = get_post_meta( $page_id, $meta_key, true );
			if ( ! is_string( $value ) || '' === $value ) {
				continue;
			}

			$repaired = str_replace(
				array( 'PrismPath?', 'Prismpath?', 'next?without' ),
				array( 'PrismPath™', 'Prismpath™', 'next—without' ),
				$value
			);

			if ( $repaired !== $value ) {
				update_post_meta( $page_id, $meta_key, $repaired );
			}
		}
	}

	update_option( 'chroma_curriculum_text_repair_version', $repair_version, false );
}
add_action( 'init', 'chroma_repair_curriculum_legacy_punctuation', 20 );

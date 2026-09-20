<?php
/**
 * Reusable Prismpath program chart slider.
 *
 * @package Chroma_Excellence
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$component_args = wp_parse_args(
	$args ?? array(),
	array(
		'eyebrow'     => __( 'Program rhythm', 'chroma-excellence' ),
		'title'       => __( 'Every program has its own balance.', 'chroma-excellence' ),
		'description' => __( 'Choose a program to see how PrismPath shifts across physical, emotional, social, academic, and creative growth.', 'chroma-excellence' ),
		'class'       => '',
	)
);

$options             = function_exists( 'chroma_home_program_wizard_options' ) ? chroma_home_program_wizard_options() : array();
$curriculum_profiles = function_exists( 'chroma_home_curriculum_profiles' ) ? chroma_home_curriculum_profiles() : array();
$pillar_labels       = $curriculum_profiles['labels'] ?? array(
	__( 'Physical', 'chroma-excellence' ),
	__( 'Emotional', 'chroma-excellence' ),
	__( 'Social', 'chroma-excellence' ),
	__( 'Academic', 'chroma-excellence' ),
	__( 'Creative', 'chroma-excellence' ),
);

if ( empty( $options ) ) {
	return;
}

$profiles_by_key = array();
foreach ( ( $curriculum_profiles['profiles'] ?? array() ) as $profile ) {
	if ( ! empty( $profile['key'] ) ) {
		$profiles_by_key[ sanitize_title( $profile['key'] ) ] = $profile;
	}
}

$fallback_profile = array(
	'title'       => __( 'A balanced PrismPath day', 'chroma-excellence' ),
	'description' => __( 'PrismPath keeps the five pillars connected while each program emphasizes the support children need most at that stage.', 'chroma-excellence' ),
	'color'       => '#4A6C7C',
	'data'        => array( 68, 72, 70, 66, 74 ),
);

$slider_options = array();
foreach ( $options as $option ) {
	$key           = sanitize_title( $option['key'] ?? '' );
	$label         = sanitize_text_field( $option['label'] ?? '' );
	$age_label     = sanitize_text_field( $option['age_label'] ?? '' );
	$program_title = sanitize_text_field( $option['program_title'] ?? '' ) ?: $label;

	if ( ! $age_label && $label && preg_match( '/\s*\((.*)\)\s*$/', $label, $matches ) ) {
		$age_label     = sanitize_text_field( $matches[1] );
		$program_title = trim( (string) preg_replace( '/\s*\([^)]+\)\s*$/', '', $label ) );
	}

	$profile = $profiles_by_key[ $key ] ?? $fallback_profile;
	$data    = is_array( $profile['data'] ?? null ) ? array_values( $profile['data'] ) : $fallback_profile['data'];

	$slider_options[] = array(
		'key'               => $key,
		'program_title'     => $program_title,
		'age_label'         => $age_label,
		'description'       => sanitize_textarea_field( $option['description'] ?? '' ),
		'prism_title'       => sanitize_text_field( $profile['title'] ?? $program_title ),
		'prism_description' => sanitize_textarea_field( $profile['description'] ?? $fallback_profile['description'] ),
		'prism_color'       => sanitize_hex_color( $profile['color'] ?? '' ) ?: $fallback_profile['color'],
		'prism_data'        => array_map(
			static function ( $value ) {
				return max( 0, min( 100, (int) $value ) );
			},
			array_pad( array_slice( $data, 0, 5 ), 5, 50 )
		),
		'link'              => esc_url_raw( $option['link'] ?? '' ),
	);
}

$default_index = 0;
foreach ( $slider_options as $index => $option ) {
	if ( 'preschool' === ( $option['key'] ?? '' ) ) {
		$default_index = $index;
		break;
	}
}

$default_option = $slider_options[ $default_index ] ?? $slider_options[0];
$component_id   = 'chroma-program-slider-' . wp_unique_id();
?>

<div class="chroma-program-prism-slider <?php echo esc_attr( $component_args['class'] ); ?>"
	data-program-chart-slider
	style="--program-accent: <?php echo esc_attr( $default_option['prism_color'] ?? '#4A6C7C' ); ?>;"
>
	<script type="application/json" data-program-slider-payload>
		<?php echo wp_json_encode( $slider_options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>
	</script>
	<script type="application/json" data-program-slider-labels>
		<?php echo wp_json_encode( array_values( $pillar_labels ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>
	</script>

	<div class="chroma-program-slider-head">
		<span class="pp-eyebrow"><?php echo esc_html( $component_args['eyebrow'] ); ?></span>
		<h2><?php echo esc_html( $component_args['title'] ); ?></h2>
		<p><?php echo esc_html( $component_args['description'] ); ?></p>
	</div>

	<div class="chroma-program-slider-stage">
		<article class="chroma-program-slider-copy">
			<span data-program-slider-age><?php echo esc_html( $default_option['age_label'] ?? '' ); ?></span>
			<h3 data-program-slider-title><?php echo esc_html( $default_option['program_title'] ?? '' ); ?></h3>
			<p data-program-slider-description><?php echo esc_html( $default_option['description'] ?? '' ); ?></p>
			<p data-program-slider-prism><?php echo esc_html( $default_option['prism_description'] ?? '' ); ?></p>
		</article>

		<div class="chroma-program-slider-chart">
			<div class="radarChart" aria-label="<?php esc_attr_e( 'PrismPath five-pillar program chart', 'chroma-excellence' ); ?>" data-program-slider-radar>
				<svg aria-labelledby="<?php echo esc_attr( $component_id ); ?>-title <?php echo esc_attr( $component_id ); ?>-desc" class="radarSvg" role="img" viewBox="0 0 560 430">
					<title id="<?php echo esc_attr( $component_id ); ?>-title"><?php esc_html_e( 'PrismPath five-pillar program chart', 'chroma-excellence' ); ?></title>
					<desc id="<?php echo esc_attr( $component_id ); ?>-desc"><?php esc_html_e( 'Radar chart showing how the five PrismPath pillars shift by selected program.', 'chroma-excellence' ); ?></desc>
					<g class="radarGrid" data-program-slider-grid></g>
					<polygon class="radarArea" data-program-slider-area points=""></polygon>
					<polygon class="radarStroke" data-program-slider-stroke points=""></polygon>
					<g data-program-slider-points></g>
					<?php
					$label_positions = array(
						array( 280, 35, 'middle' ),
						array( 515, 150, 'middle' ),
						array( 460, 365, 'middle' ),
						array( 100, 365, 'middle' ),
						array( 45, 150, 'middle' ),
					);
					foreach ( $pillar_labels as $pillar_index => $pillar_label ) :
						$position = $label_positions[ $pillar_index ] ?? array( 280, 35, 'middle' );
						?>
						<text class="radarLabel" text-anchor="<?php echo esc_attr( $position[2] ); ?>" x="<?php echo esc_attr( $position[0] ); ?>" y="<?php echo esc_attr( $position[1] ); ?>">
							<?php echo esc_html( $pillar_label ); ?>
						</text>
					<?php endforeach; ?>
				</svg>
			</div>
		</div>
	</div>

	<div class="chroma-program-slider-controls">
		<label class="sr-only" for="<?php echo esc_attr( $component_id ); ?>"><?php esc_html_e( 'Choose a program', 'chroma-excellence' ); ?></label>
		<input id="<?php echo esc_attr( $component_id ); ?>" type="range" min="0" max="<?php echo esc_attr( max( 0, count( $slider_options ) - 1 ) ); ?>" step="1" value="<?php echo esc_attr( $default_index ); ?>" data-program-slider-range>
		<div class="chroma-program-slider-ticks">
			<?php foreach ( $slider_options as $index => $option ) : ?>
				<button type="button" data-program-slider-tick="<?php echo esc_attr( $index ); ?>" class="<?php echo $index === $default_index ? 'is-active' : ''; ?>">
					<?php echo esc_html( $option['program_title'] ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>
</div>

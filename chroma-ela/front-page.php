<?php
/**
 * Front Page Template (Homepage)
 *
 * Uses ACF Flexible Content for modular layout
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

get_header();
?>

<?php
// ACF Flexible Content Loop
if ( have_rows( 'home_layout' ) ) :
	while ( have_rows( 'home_layout' ) ) : the_row();

		// Hero Section
		if ( get_row_layout() === 'hero_warm' ) :
			get_template_part( 'template-parts/hero/home-hero' );

		// Prismpath Section
		elseif ( get_row_layout() === 'prismpath_section' ) :
			get_template_part( 'template-parts/home/prismpath' );

		// Stats Strip
		elseif ( get_row_layout() === 'stats_strip' ) :
			get_template_part( 'template-parts/home/stats' );

		// Programs Wizard
		elseif ( get_row_layout() === 'programs_wizard' ) :
			get_template_part( 'template-parts/home/programs' );

		// Curriculum Radar
		elseif ( get_row_layout() === 'curriculum_radar' ) :
			get_template_part( 'template-parts/home/curriculum' );

		// Schedule/Day in the Life
		elseif ( get_row_layout() === 'schedule_strip' ) :
			get_template_part( 'template-parts/home/schedule' );

		// Locations Grid
		elseif ( get_row_layout() === 'locations_grid' ) :
			get_template_part( 'template-parts/home/locations' );

		// Tour CTA
		elseif ( get_row_layout() === 'tour_cta' ) :
			get_template_part( 'template-parts/home/tour' );

		// FAQ Section
		elseif ( get_row_layout() === 'faq_strip' ) :
			get_template_part( 'template-parts/home/faq' );

		endif;

	endwhile;
else :
	// Fallback if no flexible content is set
	?>
	<div class="max-w-7xl mx-auto px-4 py-20">
		<h1 class="text-4xl font-serif text-brand-ink mb-4">Welcome to Chroma Early Learning Academy</h1>
		<p class="text-brand-ink/70">Please configure the homepage layout using ACF Flexible Content.</p>
	</div>
	<?php
endif;
?>

<?php get_footer(); ?>

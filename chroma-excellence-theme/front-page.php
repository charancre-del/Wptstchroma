<?php
/**
 * Front Page Template (Homepage)
 * Uses ACF Flexible Content for modular sections
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();
?>

<!-- Hero Section -->
<?php get_template_part( 'template-parts/home/hero' ); ?>

<!-- Prismpath Expertise -->
<?php if ( chroma_home_has_prismpath_panels() ) : ?>
<?php get_template_part( 'template-parts/home/prismpath-expertise' ); ?>
<?php endif; ?>

<!-- Stats Strip -->
<?php if ( chroma_home_has_stats() ) : ?>
        <?php get_template_part( 'template-parts/home/stats-strip' ); ?>
<?php endif; ?>

<!-- Programs Wizard -->
<?php if ( chroma_home_has_program_wizard() ) : ?>
<?php get_template_part( 'template-parts/home/programs-wizard' ); ?>
<?php endif; ?>

<!-- Curriculum Radar -->
<?php if ( chroma_home_has_curriculum_profiles() ) : ?>
<?php get_template_part( 'template-parts/home/curriculum-chart' ); ?>
<?php endif; ?>

<!-- Schedule Tabs -->
<?php if ( chroma_home_has_schedule_tracks() ) : ?>
<?php get_template_part( 'template-parts/home/schedule-tabs' ); ?>
<?php endif; ?>

<!-- Locations Preview -->
<?php get_template_part( 'template-parts/home/locations-preview' ); ?>

<!-- FAQ Section -->
<?php if ( chroma_home_has_faq() ) : ?>
	<?php get_template_part( 'template-parts/home/faq' ); ?>
<?php endif; ?>

<!-- Tour CTA -->
<?php get_template_part( 'template-parts/home/tour-cta' ); ?>

<?php get_footer(); ?>

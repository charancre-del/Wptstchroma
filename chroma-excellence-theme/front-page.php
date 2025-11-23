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

<!-- Prismpath Expertise Section (Bento Grid) -->
<?php get_template_part( 'template-parts/home/prismpath-expertise' ); ?>

<!-- Stats Strip -->
<?php if ( chroma_home_has_stats() ) : ?>
	<?php get_template_part( 'template-parts/home/stats-strip' ); ?>
<?php endif; ?>

<!-- Programs Wizard -->
<?php get_template_part( 'template-parts/home/programs-wizard' ); ?>

<!-- Curriculum Chart (Prismpath with Chart.js) -->
<?php get_template_part( 'template-parts/home/curriculum-chart' ); ?>

<!-- Schedule Tabs (A Day in the Life) -->
<?php get_template_part( 'template-parts/home/schedule-tabs' ); ?>

<!-- Locations Preview -->
<?php get_template_part( 'template-parts/home/locations-preview' ); ?>

<!-- Tour CTA -->
<?php get_template_part( 'template-parts/home/tour-cta' ); ?>

<!-- FAQ Section -->
<?php if ( chroma_home_has_faq() ) : ?>
	<?php get_template_part( 'template-parts/home/faq' ); ?>
<?php endif; ?>

<?php get_footer(); ?>

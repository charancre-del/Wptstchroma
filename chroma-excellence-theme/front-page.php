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

<!-- Stats Strip -->
<?php if ( chroma_home_has_stats() ) : ?>
	<?php get_template_part( 'template-parts/home/stats-strip' ); ?>
<?php endif; ?>

<!-- Programs Preview -->
<?php get_template_part( 'template-parts/home/programs-preview' ); ?>

<!-- Curriculum/Prismpath Section -->
<?php get_template_part( 'template-parts/home/curriculum' ); ?>

<!-- Locations Preview -->
<?php get_template_part( 'template-parts/home/locations-preview' ); ?>

<!-- FAQ Section -->
<?php if ( chroma_home_has_faq() ) : ?>
	<?php get_template_part( 'template-parts/home/faq' ); ?>
<?php endif; ?>

<!-- Tour CTA -->
<?php get_template_part( 'template-parts/home/tour-cta' ); ?>

<?php get_footer(); ?>

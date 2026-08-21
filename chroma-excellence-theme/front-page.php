<?php
/**
 * Front Page Template (Homepage)
 * Uses hardcoded helpers for modular sections (ACF optional)
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();
?>

<main id="primary" class="site-main home-v2" role="main">

<!-- Hero Section -->
<?php get_template_part( 'template-parts/home/hero' ); ?>

<!-- PrismPath Expertise Section (Bento Grid) -->
<?php get_template_part( 'template-parts/home/prismpath-expertise' ); ?>

<!-- Curriculum Studio Editorial Band -->
<?php get_template_part( 'template-parts/home/curriculum-studio' ); ?>

<!-- Stats Strip -->
<?php if ( chroma_home_has_stats() ) : ?>
        <?php get_template_part( 'template-parts/home/stats-strip' ); ?>
<?php endif; ?>

<!-- Curriculum: Five Pillars -->
<?php if ( chroma_home_has_curriculum_profiles() ) : ?>
<?php get_template_part( 'template-parts/home/curriculum-chart' ); ?>
<?php endif; ?>

<!-- Schedule Tabs -->
<?php if ( chroma_home_has_schedule_tracks() ) : ?>
<?php get_template_part( 'template-parts/home/schedule-tabs' ); ?>
<?php endif; ?>

<!-- Parent Reviews Carousel -->
<?php if ( chroma_home_has_parent_reviews() ) : ?>
<?php get_template_part( 'template-parts/home/parent-reviews' ); ?>
<?php endif; ?>

<!-- Locations Preview -->
<?php get_template_part( 'template-parts/home/locations-preview' ); ?>

<!-- Backup Care -->
<?php get_template_part( 'template-parts/home/backup-care' ); ?>

<!-- Tour CTA -->
<?php get_template_part( 'template-parts/home/tour-cta' ); ?>

<!-- FAQ Section -->
<?php if ( chroma_home_has_faq() ) : ?>
	<?php get_template_part( 'template-parts/home/faq' ); ?>
<?php endif; ?>

</main>

<?php get_footer(); ?>

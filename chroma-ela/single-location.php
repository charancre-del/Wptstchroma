<?php
/**
 * Single Location Template
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>
	<article id="location-<?php the_ID(); ?>" <?php post_class(); ?>>
		<!-- Location Header -->
		<?php get_template_part( 'template-parts/location/header' ); ?>

		<!-- Location Details -->
		<?php get_template_part( 'template-parts/location/details' ); ?>

		<!-- Map -->
		<?php get_template_part( 'template-parts/location/map' ); ?>

		<!-- Programs Offered -->
		<?php
		$programs = get_field( 'programs_offered' );
		if ( $programs ) :
		?>
			<section class="py-16 bg-white border-t border-chroma-blue/10">
				<div class="max-w-6xl mx-auto px-4 lg:px-6">
					<h2 class="text-3xl font-serif font-bold text-brand-ink mb-8 text-center">Programs at This Location</h2>
					<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
						<?php foreach ( $programs as $program ) : ?>
							<a href="<?php echo get_permalink( $program->ID ); ?>" class="block p-6 bg-brand-cream rounded-2xl border border-chroma-blue/10 hover:border-chroma-red hover:shadow-soft transition">
								<h3 class="font-bold text-brand-ink mb-2"><?php echo get_the_title( $program->ID ); ?></h3>
								<p class="text-sm text-brand-ink/60"><?php echo get_field( 'age_range', $program->ID ); ?></p>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- Tour CTA -->
		<section class="py-16 bg-gradient-to-br from-chroma-red to-chroma-red/90 text-white">
			<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
				<h2 class="text-3xl font-serif font-bold mb-4">Visit <?php the_title(); ?></h2>
				<p class="text-lg text-white/90 mb-8">Schedule a personalized tour of our <?php the_title(); ?> campus.</p>
				<a href="<?php echo home_url( '/#tour' ); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-white text-chroma-red text-xs font-semibold uppercase tracking-wider hover:bg-brand-cream transition">
					Book A Tour
				</a>
			</div>
		</section>
	</article>
<?php endwhile; ?>

<?php get_footer(); ?>

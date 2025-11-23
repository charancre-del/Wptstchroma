<?php
/**
 * Single Program Template
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>
	<article id="program-<?php the_ID(); ?>" <?php post_class(); ?>>
		<!-- Program Header -->
		<?php get_template_part( 'template-parts/program/header' ); ?>

		<!-- Program Overview -->
		<?php get_template_part( 'template-parts/program/overview' ); ?>

		<!-- Curriculum Focus -->
		<?php get_template_part( 'template-parts/program/curriculum-focus' ); ?>

		<!-- Offered Locations -->
		<?php
		$locations = get_field( 'offered_locations' );
		if ( $locations ) :
		?>
			<section class="py-16 bg-white border-t border-chroma-blue/10">
				<div class="max-w-6xl mx-auto px-4 lg:px-6">
					<h2 class="text-3xl font-serif font-bold text-brand-ink mb-8 text-center">Available at These Locations</h2>
					<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
						<?php foreach ( $locations as $location ) : ?>
							<a href="<?php echo get_permalink( $location->ID ); ?>" class="block p-6 bg-brand-cream rounded-2xl border border-chroma-blue/10 hover:border-chroma-blue hover:shadow-soft transition">
								<h3 class="font-bold text-brand-ink mb-2"><?php echo get_the_title( $location->ID ); ?></h3>
								<p class="text-sm text-brand-ink/60"><?php echo get_field( 'address', $location->ID ); ?></p>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- CTA Section -->
		<section class="py-16 bg-gradient-to-br from-chroma-blue to-chroma-blueDark text-white">
			<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
				<h2 class="text-3xl font-serif font-bold mb-4">Ready to enroll in <?php the_title(); ?>?</h2>
				<p class="text-lg text-white/90 mb-8">Schedule a tour to see our program in action.</p>
				<a href="<?php echo home_url( '/#tour' ); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-white text-chroma-blue text-xs font-semibold uppercase tracking-wider hover:bg-brand-cream transition">
					Schedule a Tour
				</a>
			</div>
		</section>
	</article>
<?php endwhile; ?>

<?php get_footer(); ?>

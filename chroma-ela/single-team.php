<?php
/**
 * Single Team Member Template
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>
	<article id="team-<?php the_ID(); ?>" <?php post_class(); ?>>
		<!-- Team Header -->
		<div class="bg-gradient-to-br from-chroma-greenLight to-white py-16 border-b border-chroma-blue/10">
			<div class="max-w-5xl mx-auto px-4 lg:px-6">
				<div class="grid md:grid-cols-2 gap-12 items-center">
					<!-- Photo -->
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="rounded-[2.5rem] overflow-hidden shadow-soft">
							<?php the_post_thumbnail( 'team-photo', array( 'class' => 'w-full h-auto' ) ); ?>
						</div>
					<?php endif; ?>

					<!-- Info -->
					<div>
						<h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-3">
							<?php the_title(); ?>
						</h1>
						<?php if ( get_field( 'role' ) ) : ?>
							<p class="text-xl text-chroma-blue font-semibold mb-4">
								<?php the_field( 'role' ); ?>
							</p>
						<?php endif; ?>
						<?php if ( get_field( 'credentials' ) ) : ?>
							<p class="text-brand-ink/70 mb-6">
								<?php the_field( 'credentials' ); ?>
							</p>
						<?php endif; ?>
						<?php
						$served_locations = get_field( 'locations_served' );
						if ( $served_locations ) :
						?>
							<div class="mt-6">
								<p class="text-sm font-semibold text-brand-ink mb-2">Serves:</p>
								<div class="flex flex-wrap gap-2">
									<?php foreach ( $served_locations as $location ) : ?>
										<a href="<?php echo get_permalink( $location->ID ); ?>" class="inline-block px-3 py-1 bg-white border border-chroma-green/30 rounded-full text-xs font-semibold text-brand-ink hover:border-chroma-green hover:bg-chroma-greenLight transition">
											<?php echo get_the_title( $location->ID ); ?>
										</a>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Bio -->
		<div class="max-w-4xl mx-auto px-4 lg:px-6 py-16">
			<div class="prose prose-lg max-w-none">
				<?php the_content(); ?>
			</div>
		</div>
	</article>
<?php endwhile; ?>

<?php get_footer(); ?>

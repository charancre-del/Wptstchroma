<?php
/**
 * Programs Archive Template
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();
?>

<div class="bg-gradient-to-br from-chroma-teal/10 to-white py-16 border-b border-brand-navy/10">
	<div class="max-w-7xl mx-auto px-4 lg:px-6">
		<h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-4">Our Programs</h1>
		<p class="text-lg text-brand-ink/70 max-w-3xl">
			From infants to school-age children, we offer age-appropriate programs powered by our Prismpath™ curriculum.
		</p>
	</div>
</div>

<div class="max-w-7xl mx-auto px-4 lg:px-6 py-16">
	<?php if ( have_posts() ) : ?>
		<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
			<?php while ( have_posts() ) : the_post(); ?>
				<article class="bg-white rounded-3xl overflow-hidden shadow-card hover:shadow-soft transition">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="aspect-[4/3] overflow-hidden">
							<?php the_post_thumbnail( 'program-card', array( 'class' => 'w-full h-full object-cover' ) ); ?>
						</div>
					<?php endif; ?>
					<div class="p-6">
                                                <?php if ( $age_range = chroma_get_meta_value( get_the_ID(), 'program_age_range' ) ) : ?>
							<?php chroma_badge( $age_range, 'teal' ); ?>
						<?php endif; ?>
						<h2 class="text-2xl font-serif font-bold text-brand-ink mt-3 mb-2">
							<a href="<?php the_permalink(); ?>" class="hover:text-chroma-teal">
								<?php the_title(); ?>
							</a>
						</h2>
						<p class="text-brand-ink/70 text-sm mb-4"><?php echo chroma_trimmed_excerpt( 20 ); ?></p>
						<a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-2 text-chroma-teal font-semibold text-sm hover:text-brand-navy">
							Learn more →
						</a>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
		<?php chroma_archive_pagination(); ?>
	<?php endif; ?>
</div>

<?php get_footer(); ?>

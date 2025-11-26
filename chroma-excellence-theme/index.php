<?php
/**
 * Main Template File (Fallback)
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();
?>

<div class="max-w-7xl mx-auto px-4 lg:px-6 py-20">
	<?php if ( have_posts() ) : ?>
		<div class="space-y-12">
			<?php while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'bg-white rounded-3xl p-8 shadow-card' ); ?>>
					<h2 class="text-2xl font-serif font-bold text-brand-ink mb-4">
						<a href="<?php the_permalink(); ?>" class="hover:text-chroma-teal">
							<?php the_title(); ?>
						</a>
					</h2>
					<div class="text-brand-ink/70 prose max-w-none">
						<?php the_excerpt(); ?>
					</div>
					<a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-2 mt-4 text-chroma-teal font-semibold hover:text-brand-navy">
						Read more →
					</a>
				</article>
			<?php endwhile; ?>
		</div>
		<?php chroma_archive_pagination(); ?>
	<?php else : ?>
		<div class="text-center py-20">
			<h1 class="text-3xl font-serif text-brand-ink mb-4">Nothing Found</h1>
			<p class="text-brand-ink/70">Sorry, no content matched your criteria.</p>
		</div>
	<?php endif; ?>
</div>

<?php get_footer(); ?>

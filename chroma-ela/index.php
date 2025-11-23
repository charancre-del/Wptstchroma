<?php
/**
 * Main Template File
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

get_header();
?>

<div class="max-w-7xl mx-auto px-4 lg:px-6 py-20">
	<?php if ( have_posts() ) : ?>
		<div class="space-y-12">
			<?php while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'bg-white rounded-3xl p-8 shadow-soft' ); ?>>
					<h2 class="text-2xl font-serif font-bold text-brand-ink mb-4">
						<a href="<?php the_permalink(); ?>" class="hover:text-chroma-blue">
							<?php the_title(); ?>
						</a>
					</h2>
					<div class="text-brand-ink/70 prose max-w-none">
						<?php the_excerpt(); ?>
					</div>
					<a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-2 mt-4 text-chroma-blue font-semibold hover:text-chroma-blueDark">
						Read more →
					</a>
				</article>
			<?php endwhile; ?>
		</div>

		<div class="mt-12">
			<?php the_posts_pagination(); ?>
		</div>
	<?php else : ?>
		<div class="text-center py-20">
			<h1 class="text-3xl font-serif text-brand-ink mb-4">Nothing Found</h1>
			<p class="text-brand-ink/70">Sorry, no posts matched your criteria.</p>
		</div>
	<?php endif; ?>
</div>

<?php get_footer(); ?>

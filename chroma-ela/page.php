<?php
/**
 * Page Template
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<!-- Page Header -->
		<div class="bg-gradient-to-br from-chroma-blueLight to-white py-16 border-b border-chroma-blue/10">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-4">
					<?php the_title(); ?>
				</h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="text-lg text-brand-ink/70 max-w-3xl">
						<?php the_excerpt(); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Page Content -->
		<div class="max-w-4xl mx-auto px-4 lg:px-6 py-16">
			<div class="prose prose-lg max-w-none">
				<?php the_content(); ?>
			</div>
		</div>
	</article>
<?php endwhile; ?>

<?php get_footer(); ?>

<?php
/**
 * Single Post Template
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<!-- Post Header -->
		<div class="bg-gradient-to-br from-chroma-yellowLight to-white py-16 border-b border-chroma-blue/10">
			<div class="max-w-4xl mx-auto px-4 lg:px-6">
				<div class="text-xs font-semibold text-chroma-blue uppercase tracking-wider mb-3">
					<?php the_category( ', ' ); ?>
				</div>
				<h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-4">
					<?php the_title(); ?>
				</h1>
				<div class="flex items-center gap-4 text-sm text-brand-ink/60">
					<time datetime="<?php echo get_the_date( 'c' ); ?>">
						<?php echo get_the_date(); ?>
					</time>
					<span>•</span>
					<span><?php echo get_the_author(); ?></span>
				</div>
			</div>
		</div>

		<!-- Featured Image -->
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="max-w-5xl mx-auto px-4 lg:px-6 -mt-8 mb-12">
				<div class="rounded-3xl overflow-hidden shadow-soft">
					<?php the_post_thumbnail( 'full', array( 'class' => 'w-full h-auto' ) ); ?>
				</div>
			</div>
		<?php endif; ?>

		<!-- Post Content -->
		<div class="max-w-4xl mx-auto px-4 lg:px-6 pb-16">
			<div class="prose prose-lg max-w-none">
				<?php the_content(); ?>
			</div>
		</div>
	</article>
<?php endwhile; ?>

<?php get_footer(); ?>

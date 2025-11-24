<?php
/**
 * Single Program Template
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>
        <?php $meta = chroma_get_program_meta(); ?>
        <article id="program-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="bg-gradient-to-br from-chroma-redLight to-white py-16 border-b border-brand-navy/10">
                        <div class="max-w-6xl mx-auto px-4 lg:px-6">
                                <?php if ( $age_range = $meta['age_range'] ) : ?>
                                        <?php chroma_eyebrow( $age_range, 'teal' ); ?>
                                <?php endif; ?>
				<h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-4"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="text-lg text-brand-ink/70"><?php the_excerpt(); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="max-w-4xl mx-auto px-4 lg:px-6 py-16">
			<div class="prose prose-lg max-w-none">
				<?php the_content(); ?>
			</div>
		</div>

		<div class="bg-chroma-teal/10 py-16">
			<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
				<h2 class="text-3xl font-serif font-bold text-brand-ink mb-4">Ready to enroll?</h2>
				<p class="text-lg text-brand-ink/70 mb-8">Schedule a tour to see this program in action.</p>
				<a href="<?php echo home_url( '/contact#tour' ); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-chroma-teal text-white font-semibold hover:bg-brand-navy transition">
					Schedule a Tour
				</a>
			</div>
		</div>
	</article>
<?php endwhile; ?>

<?php get_footer(); ?>

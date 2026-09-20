<?php
/**
 * Main Template File (Fallback)
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();
?>

<main class="bg-brand-cream min-h-screen">
	<section class="pageHero chroma-v2-page-hero bg-white border-b border-chroma-blue/10 py-16 lg:py-20 text-center">
		<div class="max-w-4xl mx-auto px-4 lg:px-6">
			<span class="inline-flex items-center gap-2 rounded-full bg-chroma-red/10 border border-chroma-red/10 px-5 py-2.5 text-[11px] font-bold uppercase tracking-[0.2em] text-chroma-red mb-7">
				<span class="w-2 h-2 rounded-full bg-chroma-red"></span>
				<?php esc_html_e('Chroma Journal', 'chroma-excellence'); ?>
			</span>
			<h1 class="font-serif text-[2.75rem] md:text-6xl lg:text-7xl text-brand-ink tracking-[-0.04em] leading-[0.95]">
				<?php esc_html_e('Stories, guides, and updates.', 'chroma-excellence'); ?>
			</h1>
		</div>
	</section>

	<div class="max-w-7xl mx-auto px-4 lg:px-6 py-16 lg:py-20">
		<?php if (have_posts()): ?>
			<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
				<?php while (have_posts()):
					the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class('group bg-white rounded-[2rem] p-7 shadow-soft border border-chroma-blue/10 hover:-translate-y-1 hover:shadow-cardHover transition'); ?>>
						<p class="text-[11px] font-bold uppercase tracking-[0.2em] text-brand-ink/45 mb-4">
							<?php echo esc_html(get_the_date()); ?>
						</p>
						<h2 class="text-2xl lg:text-3xl font-serif font-bold text-brand-ink mb-4 leading-tight">
							<a href="<?php the_permalink(); ?>" class="group-hover:text-chroma-red transition-colors">
								<?php the_title(); ?>
							</a>
						</h2>
						<div class="text-brand-ink/75 prose prose-sm max-w-none">
							<?php the_excerpt(); ?>
						</div>
						<a href="<?php the_permalink(); ?>"
							class="inline-flex items-center gap-2 mt-5 text-xs font-bold uppercase tracking-[0.18em] text-chroma-red hover:text-brand-ink transition-colors">
							<?php esc_html_e('Read more', 'chroma-excellence'); ?> &rarr;
						</a>
					</article>
				<?php endwhile; ?>
			</div>
			<?php chroma_archive_pagination(); ?>
		<?php else: ?>
			<div class="text-center py-20 bg-white rounded-[2.5rem] border border-chroma-blue/10 shadow-soft">
				<h2 class="text-3xl font-serif text-brand-ink mb-4"><?php esc_html_e('Nothing found.', 'chroma-excellence'); ?></h2>
				<p class="text-brand-ink/80"><?php esc_html_e('Sorry, no content matched your criteria.', 'chroma-excellence'); ?></p>
			</div>
		<?php endif; ?>
	</div>
</main>

<?php
get_footer();

<?php
/**
 * Program Curriculum Focus
 *
 * @package Chroma_ELA
 */
?>
<?php if ( have_rows( 'curriculum_focus' ) ) : ?>
<section class="py-16 bg-brand-cream border-t border-chroma-blue/10">
	<div class="max-w-6xl mx-auto px-4 lg:px-6">
		<h2 class="text-3xl font-serif font-bold text-brand-ink mb-8 text-center">Curriculum Focus Areas</h2>
		<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
			<?php while ( have_rows( 'curriculum_focus' ) ) : the_row(); ?>
				<div class="bg-white rounded-2xl p-6 shadow-soft border border-chroma-blue/10">
					<h3 class="font-bold text-brand-ink mb-2"><?php the_sub_field( 'area' ); ?></h3>
					<p class="text-sm text-brand-ink/70"><?php the_sub_field( 'description' ); ?></p>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
</section>
<?php endif; ?>

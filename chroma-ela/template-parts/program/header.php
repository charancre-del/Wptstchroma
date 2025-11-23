<?php
/**
 * Program Header
 *
 * @package Chroma_ELA
 */
?>
<div class="bg-gradient-to-br from-chroma-redLight to-white py-16 border-b border-chroma-blue/10">
	<div class="max-w-6xl mx-auto px-4 lg:px-6">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="mb-8 rounded-3xl overflow-hidden shadow-soft max-w-2xl mx-auto">
				<?php the_post_thumbnail( 'program-card' ); ?>
			</div>
		<?php endif; ?>
		<div class="text-center">
			<h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-4"><?php the_title(); ?></h1>
			<?php if ( get_field( 'tagline' ) ) : ?>
				<p class="text-xl text-chroma-blue font-semibold mb-4"><?php the_field( 'tagline' ); ?></p>
			<?php endif; ?>
			<?php if ( get_field( 'age_range' ) ) : ?>
				<p class="text-brand-ink/70"><?php the_field( 'age_range' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>

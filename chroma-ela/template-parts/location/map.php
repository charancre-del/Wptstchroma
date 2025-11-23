<?php
/**
 * Location Map
 *
 * @package Chroma_ELA
 */
?>
<?php if ( get_field( 'google_maps_embed' ) ) : ?>
<section class="py-16 bg-brand-cream border-t border-chroma-blue/10">
	<div class="max-w-6xl mx-auto px-4 lg:px-6">
		<h2 class="text-2xl font-serif font-bold text-brand-ink mb-8 text-center">Visit Us</h2>
		<div class="rounded-3xl overflow-hidden shadow-soft">
			<?php the_field( 'google_maps_embed' ); ?>
		</div>
	</div>
</section>
<?php endif; ?>

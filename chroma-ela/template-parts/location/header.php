<?php
/**
 * Location Header
 *
 * @package Chroma_ELA
 */
?>
<div class="bg-gradient-to-br from-chroma-greenLight to-white py-16 border-b border-chroma-blue/10">
	<div class="max-w-6xl mx-auto px-4 lg:px-6 text-center">
		<h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-4"><?php the_title(); ?></h1>
		<?php if ( get_field( 'address' ) ) : ?>
			<p class="text-lg text-brand-ink/70 mb-2">
				<?php the_field( 'address' ); ?>, <?php the_field( 'city' ); ?>, <?php the_field( 'state' ); ?> <?php the_field( 'zip' ); ?>
			</p>
		<?php endif; ?>
		<?php if ( get_field( 'phone' ) ) : ?>
			<p class="text-brand-ink/70"><a href="tel:<?php the_field( 'phone' ); ?>" class="hover:text-chroma-blue"><?php the_field( 'phone' ); ?></a></p>
		<?php endif; ?>
	</div>
</div>

<?php
/**
 * Location Details
 *
 * @package Chroma_ELA
 */
?>
<section class="py-16 bg-white">
	<div class="max-w-6xl mx-auto px-4 lg:px-6">
		<div class="grid md:grid-cols-2 gap-12">
			<div>
				<h2 class="text-2xl font-serif font-bold text-brand-ink mb-6">About This Location</h2>
				<div class="prose max-w-none">
					<?php the_content(); ?>
				</div>
			</div>
			<div class="space-y-6">
				<?php if ( get_field( 'hours' ) ) : ?>
					<div class="bg-brand-cream rounded-2xl p-6">
						<h3 class="font-bold text-brand-ink mb-2">Hours</h3>
						<p class="text-brand-ink/70"><?php the_field( 'hours' ); ?></p>
					</div>
				<?php endif; ?>
				<?php if ( get_field( 'enrollment_status' ) ) : ?>
					<div class="bg-chroma-redLight rounded-2xl p-6 border border-chroma-red/30">
						<h3 class="font-bold text-brand-ink mb-2">Enrollment Status</h3>
						<p class="text-brand-ink font-semibold"><?php the_field( 'enrollment_status' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<?php
/**
 * FAQ Section
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */
?>

<section id="faq" class="py-20 bg-white">
	<div class="max-w-4xl mx-auto px-4 lg:px-6">
		<div class="text-center mb-10">
			<h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">Common questions from parents</h2>
			<p class="text-brand-ink/70 text-sm md:text-base max-w-2xl mx-auto">We've answered a few of the questions parents ask most when choosing childcare and early learning in Metro Atlanta.</p>
		</div>
		<div class="space-y-4">
			<?php if ( have_rows( 'faq_items' ) ) : ?>
				<?php while ( have_rows( 'faq_items' ) ) : the_row(); ?>
					<details class="group bg-brand-cream rounded-2xl px-5 py-4 border border-chroma-blue/10">
						<summary class="flex items-center justify-between gap-3 cursor-pointer list-none">
							<span class="font-semibold text-sm text-brand-ink"><?php the_sub_field( 'question' ); ?></span>
							<span class="text-brand-ink/60 group-open:rotate-180 transition-transform">⌄</span>
						</summary>
						<p class="mt-3 text-sm text-brand-ink/70"><?php the_sub_field( 'answer' ); ?></p>
					</details>
				<?php endwhile; ?>
			<?php else : ?>
				<!-- Default FAQs -->
				<details class="group bg-brand-cream rounded-2xl px-5 py-4 border border-chroma-blue/10">
					<summary class="flex items-center justify-between gap-3 cursor-pointer list-none"><span class="font-semibold text-sm text-brand-ink">Do you offer GA Lottery Pre-K?</span><span class="text-brand-ink/60 group-open:rotate-180 transition-transform">⌄</span></summary>
					<p class="mt-3 text-sm text-brand-ink/70">Yes. Many Chroma locations offer free GA Lottery Pre-K for 4-year-olds.</p>
				</details>
				<details class="group bg-brand-cream rounded-2xl px-5 py-4 border border-chroma-blue/10">
					<summary class="flex items-center justify-between gap-3 cursor-pointer list-none"><span class="font-semibold text-sm text-brand-ink">What ages do you serve?</span><span class="text-brand-ink/60 group-open:rotate-180 transition-transform">⌄</span></summary>
					<p class="mt-3 text-sm text-brand-ink/70">Most campuses serve children from 6 weeks through 12 years old.</p>
				</details>
				<details class="group bg-brand-cream rounded-2xl px-5 py-4 border border-chroma-blue/10">
					<summary class="flex items-center justify-between gap-3 cursor-pointer list-none"><span class="font-semibold text-sm text-brand-ink">Are meals and snacks included?</span><span class="text-brand-ink/60 group-open:rotate-180 transition-transform">⌄</span></summary>
					<p class="mt-3 text-sm text-brand-ink/70">Yes. Through the Child and Adult Care Food Program (CACFP).</p>
				</details>
			<?php endif; ?>
		</div>
	</div>
</section>

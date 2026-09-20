<?php
/**
 * Homepage Curriculum Studio editorial band.
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
	exit;
}
?>

<section class="chroma-home-studio py-14 md:py-16 bg-brand-cream border-y border-brand-ink/5">
	<div class="max-w-7xl mx-auto px-4 lg:px-6">
		<div class="rounded-[2rem] border border-brand-ink/10 bg-white shadow-soft p-7 md:p-10 lg:p-12 grid lg:grid-cols-[1.1fr_0.9fr] gap-8 lg:gap-14 items-center reveal">
			<div>
				<span class="inline-flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.18em] text-chroma-red mb-4">
					<span class="w-2 h-2 rounded-full bg-chroma-red" aria-hidden="true"></span>
					<?php esc_html_e('How Chroma Supports Growth', 'chroma-excellence'); ?>
				</span>
				<h2 class="font-serif text-3xl md:text-5xl font-semibold tracking-[-0.035em] leading-[1.02] text-brand-ink mb-5">
					<?php esc_html_e('Personalized learning starts with better insight.', 'chroma-excellence'); ?>
				</h2>
				<p class="text-base md:text-lg leading-relaxed text-brand-ink/75 max-w-3xl">
					<?php esc_html_e('Curriculum Studio connects family input, teacher observations, and classroom progress to help teachers shape responsive lesson plans—and helps Chroma’s education team provide classroom-specific coaching and support.', 'chroma-excellence'); ?>
				</p>
			</div>
			<div class="grid sm:grid-cols-3 lg:grid-cols-1 gap-3">
				<div class="rounded-2xl bg-chroma-redLight/55 border border-chroma-red/10 px-5 py-4">
					<strong class="block text-brand-ink mb-1"><?php esc_html_e('Notice', 'chroma-excellence'); ?></strong>
					<span class="text-sm text-brand-ink/70"><?php esc_html_e('See growth patterns and emerging interests.', 'chroma-excellence'); ?></span>
				</div>
				<div class="rounded-2xl bg-chroma-yellowLight/55 border border-chroma-yellow/10 px-5 py-4">
					<strong class="block text-brand-ink mb-1"><?php esc_html_e('Plan', 'chroma-excellence'); ?></strong>
					<span class="text-sm text-brand-ink/70"><?php esc_html_e('Shape next steps around the children in the room.', 'chroma-excellence'); ?></span>
				</div>
				<div class="rounded-2xl bg-chroma-blueLight/55 border border-chroma-blue/10 px-5 py-4">
					<strong class="block text-brand-ink mb-1"><?php esc_html_e('Support', 'chroma-excellence'); ?></strong>
					<span class="text-sm text-brand-ink/70"><?php esc_html_e('Connect teachers with focused coaching and resources.', 'chroma-excellence'); ?></span>
				</div>
				<div class="sm:col-span-3 lg:col-span-1 flex flex-wrap gap-3 pt-2">
					<a class="inline-flex items-center justify-center rounded-full bg-brand-ink text-white px-6 py-3 text-xs font-bold uppercase tracking-[0.16em] hover:bg-chroma-red transition" href="<?php echo esc_url(home_url('/curriculum/#curriculum-studio')); ?>">
						<?php esc_html_e('Explore Curriculum Studio', 'chroma-excellence'); ?>
					</a>
					<a class="inline-flex items-center justify-center rounded-full border border-brand-ink/15 bg-white text-brand-ink px-6 py-3 text-xs font-bold uppercase tracking-[0.16em] hover:border-chroma-red hover:text-chroma-red transition" href="<?php echo esc_url(home_url('/schedule-tour/')); ?>">
						<?php esc_html_e('Schedule a Tour', 'chroma-excellence'); ?>
					</a>
				</div>
			</div>
		</div>
	</div>
</section>

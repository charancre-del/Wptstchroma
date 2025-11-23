<?php
/**
 * Stats Strip Section
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */
?>

<section class="bg-white py-12 border-y border-chroma-blue/10">
	<div class="max-w-6xl mx-auto px-4 lg:px-6 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
		<div class="group">
			<div class="font-serif text-3xl font-bold text-chroma-red group-hover:scale-110 transition-transform duration-300">
				<?php echo esc_html( get_sub_field( 'stat_1_number' ) ?: '19+' ); ?>
			</div>
			<div class="mt-1 text-[11px] uppercase tracking-[0.2em] text-brand-ink/60">
				<?php echo esc_html( get_sub_field( 'stat_1_label' ) ?: 'Metro campuses' ); ?>
			</div>
		</div>
		<div class="group">
			<div class="font-serif text-3xl font-bold text-chroma-yellow group-hover:scale-110 transition-transform duration-300">
				<?php echo esc_html( get_sub_field( 'stat_2_number' ) ?: '2,000+' ); ?>
			</div>
			<div class="mt-1 text-[11px] uppercase tracking-[0.2em] text-brand-ink/60">
				<?php echo esc_html( get_sub_field( 'stat_2_label' ) ?: 'Children enrolled' ); ?>
			</div>
		</div>
		<div class="group">
			<div class="font-serif text-3xl font-bold text-chroma-blue group-hover:scale-110 transition-transform duration-300">
				<?php echo esc_html( get_sub_field( 'stat_3_number' ) ?: '4.8' ); ?>
			</div>
			<div class="mt-1 text-[11px] uppercase tracking-[0.2em] text-brand-ink/60">
				<?php echo esc_html( get_sub_field( 'stat_3_label' ) ?: 'Avg parent rating' ); ?>
			</div>
		</div>
		<div class="group">
			<div class="font-serif text-3xl font-bold text-chroma-green group-hover:scale-110 transition-transform duration-300">
				<?php echo esc_html( get_sub_field( 'stat_4_number' ) ?: '6w–12y' ); ?>
			</div>
			<div class="mt-1 text-[11px] uppercase tracking-[0.2em] text-brand-ink/60">
				<?php echo esc_html( get_sub_field( 'stat_4_label' ) ?: 'Age range' ); ?>
			</div>
		</div>
	</div>
</section>

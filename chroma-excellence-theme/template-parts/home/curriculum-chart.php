<?php
/**
 * Home Curriculum Prism Stack
 *
 * @package Chroma_Excellence
 */
?>

<section id="curriculum" class="chroma-prism-stack py-20 lg:py-24 bg-gradient-to-b from-brand-cream to-white border-y border-chroma-blue/10" data-section="curriculum">
	<div class="max-w-7xl mx-auto px-4 lg:px-6">
		<div class="max-w-6xl mb-14 lg:mb-16 fade-in-up">
			<div class="inline-flex items-center gap-3 rounded-full bg-chroma-yellow/10 border border-chroma-yellow/10 px-5 py-2.5 text-sm font-semibold text-chroma-yellow mb-7">
				<span class="w-2 h-2 rounded-full bg-chroma-yellow"></span>
				<?php esc_html_e('The PrismPath™ method', 'chroma-excellence'); ?>
			</div>
			<h2 class="chroma-prism-title font-serif text-brand-ink text-5xl md:text-7xl lg:text-[5.75rem] leading-[0.95] tracking-tight mb-6">
				<?php esc_html_e('Five pillars.', 'chroma-excellence'); ?>
				<em class="inline text-chroma-yellow"><?php esc_html_e('One prism.', 'chroma-excellence'); ?></em>
			</h2>
			<p class="text-brand-ink/80 text-lg md:text-xl leading-relaxed max-w-2xl">
				<?php esc_html_e('A prism refracts light into clear dimensions. PrismPath™ refracts play into five pillars of development — and rebalances the mix at each age.', 'chroma-excellence'); ?>
			</p>
		</div>

		<div class="grid md:grid-cols-2 xl:grid-cols-5 gap-5 fade-in-up">
			<?php
			$pillars = array(
				array(
					'number' => __('one', 'chroma-excellence'),
					'title' => __('Physical', 'chroma-excellence'),
					'text' => __('Motor skills, sensory processing, gross and fine coordination through movement and exploration.', 'chroma-excellence'),
					'class' => 'physical',
					'color' => '#7D5BA6',
				),
				array(
					'number' => __('two', 'chroma-excellence'),
					'title' => __('Emotional', 'chroma-excellence'),
					'text' => __('Attachment, self-regulation, and the confidence to name what is felt before reacting to it.', 'chroma-excellence'),
					'class' => 'emotional',
					'color' => '#4A6C7C',
				),
				array(
					'number' => __('three', 'chroma-excellence'),
					'title' => __('Social', 'chroma-excellence'),
					'text' => __('Collaboration, conflict resolution, and the everyday work of belonging to a group.', 'chroma-excellence'),
					'class' => 'social',
					'color' => '#4A7C59',
				),
				array(
					'number' => __('four', 'chroma-excellence'),
					'title' => __('Academic', 'chroma-excellence'),
					'text' => __('Literacy, numeracy, and curiosity — aligned to GELDS standards and the GA Pre-K framework.', 'chroma-excellence'),
					'class' => 'academic',
					'color' => '#A77B24',
				),
				array(
					'number' => __('five', 'chroma-excellence'),
					'title' => __('Creative', 'chroma-excellence'),
					'text' => __('Visual art, music, dramatic play, and the freedom to make things that did not exist that morning.', 'chroma-excellence'),
					'class' => 'creative',
					'color' => '#A84B38',
				),
			);
			foreach ($pillars as $pillar) :
				?>
				<article class="chroma-prism-card chroma-prism-card--<?php echo esc_attr($pillar['class']); ?> bg-white border border-chroma-blue/10 rounded-[1.75rem] p-7 lg:p-8 shadow-soft transition duration-300 hover:-translate-y-2 hover:shadow-cardHover" style="--prism-color: <?php echo esc_attr($pillar['color']); ?>">
					<div class="chroma-prism-orb mb-8"></div>
					<span class="chroma-prism-number block mb-3 text-xl"><?php echo esc_html($pillar['number']); ?></span>
					<h3 class="font-serif text-3xl text-brand-ink mb-5"><?php echo esc_html($pillar['title']); ?></h3>
					<p class="text-brand-ink/80 leading-relaxed"><?php echo esc_html($pillar['text']); ?></p>
				</article>
			<?php endforeach; ?>
		</div>

		<div class="mt-14 lg:mt-16 fade-in-up">
			<?php
			get_template_part(
				'template-parts/home/program-prism-slider',
				null,
				array(
					'eyebrow'     => __( 'Program chart', 'chroma-excellence' ),
					'title'       => __( 'The prism shifts by program.', 'chroma-excellence' ),
					'description' => __( 'Choose a Chroma program to see how the same five pillars rebalance as children move from early care into school readiness.', 'chroma-excellence' ),
					'class'       => 'chroma-program-prism-slider--home',
				)
			);
			?>
		</div>
	</div>
</section>

<?php
/**
 * Template Part: Prismpath Expertise Section
 *
 * @package Chroma_Excellence
 */

$panels = chroma_home_prismpath_panels();

$feature = $panels['feature'];
$cards = $panels['cards'];
$heading = $feature['heading'];
$subheading = $feature['subheading'] ?? '';
$readiness = $panels['readiness'];

$card_1 = $cards[0] ?? array();
$card_2 = $cards[1] ?? array();
$card_3 = $cards[2] ?? array();
$card_4 = $cards[3] ?? array();

$curriculum_heading = $card_1['heading'] ?? __('The PrismPath™ Curriculum', 'chroma-excellence');
$curriculum_text = $card_1['text'] ?? __('Just as a prism refracts light into a full spectrum of color, PrismPath™ refracts play into a full spectrum of development.', 'chroma-excellence');
$care_heading = $card_2['heading'] ?? __('Expert Care, Extended Family.', 'chroma-excellence');
$care_text = $card_2['text'] ?? __('Our educators build warm, responsive relationships and help each child feel known, secure, and ready to grow.', 'chroma-excellence');
$fuel_heading = $card_3['heading'] ?? __('Wholesome Fuel', 'chroma-excellence');
$fuel_text = $card_3['text'] ?? __('Meal and snack offerings are planned for young children and can be confirmed with each campus.', 'chroma-excellence');
$safety_heading = $card_4['heading'] ?? __('Uncompromised Safety', 'chroma-excellence');
$safety_text = $card_4['text'] ?? __('Campus teams follow required licensing, supervision, and visitor procedures that families can review during a tour.', 'chroma-excellence');
$readiness_heading = $readiness['heading'] ?? __('Kindergarten Readiness', 'chroma-excellence');
$readiness_text = $readiness['description'] ?? __('Our programs build the social, emotional, language, and learning habits children use as they move toward kindergarten.', 'chroma-excellence');
$color_heading = $feature['color_heading'] ?? __('Every child brings their own beautiful color to the world.', 'chroma-excellence');
$color_text = $subheading ?: __('Chroma represents the full spectrum of growth, learning, and possibility within each child. We nurture every child’s unique strengths, personality, and talents.', 'chroma-excellence');
$curriculum_text = wp_strip_all_tags($curriculum_text);
$care_text = wp_strip_all_tags($care_text);
$fuel_text = wp_strip_all_tags($fuel_text);
$safety_text = wp_strip_all_tags($safety_text);
$readiness_text = wp_strip_all_tags($readiness_text);
$color_text = wp_strip_all_tags($color_text);
?>

<section id="prismpath" class="py-24 px-4 lg:px-6 bg-white relative overflow-hidden" data-section="prismpath">
	<div class="absolute -left-10 top-10 w-80 h-80 bg-chroma-blue/5 rounded-full blur-3xl"></div>

	<div class="max-w-[1200px] mx-auto">
		<div class="text-center mb-14 chroma-bento-heading">
			<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
				<?php echo esc_html($feature['eyebrow'] ?? __('The Chroma Standard', 'chroma-excellence')); ?>
			</span>
			<h2 class="font-serif text-brand-ink"><?php echo esc_html($heading); ?></h2>
			<?php if ($subheading): ?>
				<p class="text-brand-ink/70 mt-4 max-w-2xl mx-auto"><?php echo esc_html($subheading); ?></p>
			<?php endif; ?>
		</div>

		<div class="chroma-bento-grid" aria-label="<?php esc_attr_e('Chroma standard highlights', 'chroma-excellence'); ?>">
			<article class="chroma-bento-card chroma-bento-card--curriculum">
				<div class="chroma-bento-icon" aria-hidden="true">
					<i class="fa-solid fa-user-check"></i>
				</div>
				<h3><?php echo esc_html($care_heading); ?></h3>
				<p><?php echo esc_html($care_text); ?></p>
			</article>

			<article class="chroma-bento-card chroma-bento-card--readiness">
				<div class="chroma-bento-icon" aria-hidden="true">
					<i class="fa-solid fa-graduation-cap"></i>
				</div>
				<h3><?php echo esc_html($readiness_heading); ?></h3>
				<p><?php echo esc_html($readiness_text); ?></p>
			</article>

			<article class="chroma-bento-card chroma-bento-card--care">
				<div class="chroma-bento-icon" aria-hidden="true">
					<i class="fa-solid fa-shapes"></i>
				</div>
				<h3><?php echo esc_html($curriculum_heading); ?></h3>
				<p><?php echo esc_html($curriculum_text); ?></p>
			</article>

			<article class="chroma-bento-card chroma-bento-card--color">
				<div class="chroma-bento-icon" aria-hidden="true">
					<i class="fa-solid fa-palette"></i>
				</div>
				<h3><?php echo esc_html($color_heading); ?></h3>
				<p><?php echo esc_html($color_text); ?></p>
			</article>

			<article class="chroma-bento-card chroma-bento-card--fuel">
				<div class="chroma-bento-icon" aria-hidden="true">
					<i class="fa-solid fa-apple-whole"></i>
				</div>
				<h3><?php echo esc_html($fuel_heading); ?></h3>
				<p><?php echo esc_html($fuel_text); ?></p>
			</article>

			<article class="chroma-bento-card chroma-bento-card--safety">
				<div class="chroma-bento-icon" aria-hidden="true">
					<i class="fa-solid fa-shield-halved"></i>
				</div>
				<h3><?php echo esc_html($safety_heading); ?></h3>
				<p><?php echo esc_html($safety_text); ?></p>
			</article>
		</div>
	</div>
</section>

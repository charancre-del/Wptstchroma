<?php
/**
 * Template Name: Rising Kindergarten Summer Template
 * Template Post Type: program
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

$rising_k_preload_id = get_queried_object_id();
$rising_k_preload_url = '';

if ($rising_k_preload_id) {
	$rising_k_image_override = trim((string) get_post_meta($rising_k_preload_id, 'program_hero_image', true));
	if ($rising_k_image_override !== '') {
		$rising_k_preload_url = $rising_k_image_override;
	} elseif (has_post_thumbnail($rising_k_preload_id)) {
		$rising_k_preload_url = (string) get_the_post_thumbnail_url($rising_k_preload_id, 'full');
	}
}

if ($rising_k_preload_url !== '') {
	add_action(
		'wp_head',
		static function () use ($rising_k_preload_url) {
			echo '<link rel="preload" as="image" href="' . esc_url($rising_k_preload_url) . '" fetchpriority="high">' . "\n";
		},
		2
	);
}

get_header();

while (have_posts()):
	the_post();

	$program_id = get_the_ID();
	$age_range = chroma_get_translated_meta($program_id, 'program_age_range', true) ?: __('Ages 4-5', 'chroma-excellence');
	$lesson_plan_url = get_post_meta($program_id, 'program_lesson_plan_file', true);
	$has_lesson_plan = trim((string) $lesson_plan_url) !== '' && trim((string) $lesson_plan_url) !== '#';

	$hero_title = chroma_get_translated_meta($program_id, 'program_hero_title', true) ?: __('Step into Kindergarten with absolute confidence.', 'chroma-excellence');
	$hero_description = chroma_get_translated_meta($program_id, 'program_hero_description', true) ?: __('Stop the summer slide before it starts. Our <strong>Rising Kindergarten Summer Program</strong> is a specialized 6-week curriculum designed to strengthen early reading, mathematical reasoning, and executive function skills so your child feels ready on day one.', 'chroma-excellence');
	$hero_image_override = trim((string) chroma_get_translated_meta($program_id, 'program_hero_image', true));

	if ($hero_image_override === '') {
		$hero_image_override = trim((string) get_post_meta($program_id, 'program_hero_image', true));
	}

	$hero_image_markup = '';
	if ($hero_image_override !== '') {
		$hero_image_markup = sprintf(
			'<img src="%1$s" alt="%2$s" class="w-full h-full object-cover" fetchpriority="high" loading="eager" decoding="async" sizes="(min-width: 1024px) 640px, 100vw" />',
			esc_url($hero_image_override),
			esc_attr__('Child practicing early writing skills during summer learning', 'chroma-excellence')
		);
	} elseif (has_post_thumbnail($program_id)) {
		$hero_image_markup = wp_get_attachment_image(
			get_post_thumbnail_id($program_id),
			'full',
			false,
			array(
				'class' => 'w-full h-full object-cover',
				'fetchpriority' => 'high',
				'loading' => 'eager',
				'decoding' => 'async',
				'sizes' => '(min-width: 1024px) 640px, 100vw',
				'alt' => esc_attr__('Child practicing early writing skills during summer learning', 'chroma-excellence'),
			)
		);
	}

	$focus_scores = array(
		array(
			'label' => __('Academic', 'chroma-excellence'),
			'description' => __('Phonics, blending, letter-sound mastery, and math reasoning.', 'chroma-excellence'),
			'value' => max(0, min(100, absint(get_post_meta($program_id, 'program_prism_academic', true) ?: 95))),
			'bar_class' => 'bg-chroma-blue',
		),
		array(
			'label' => __('Social', 'chroma-excellence'),
			'description' => __('Collaborative learning, communication, and peer problem-solving.', 'chroma-excellence'),
			'value' => max(0, min(100, absint(get_post_meta($program_id, 'program_prism_social', true) ?: 80))),
			'bar_class' => 'bg-chroma-green',
		),
		array(
			'label' => __('Emotional', 'chroma-excellence'),
			'description' => __('Executive function, self-regulation, and multi-step directions.', 'chroma-excellence'),
			'value' => max(0, min(100, absint(get_post_meta($program_id, 'program_prism_emotional', true) ?: 75))),
			'bar_class' => 'bg-chroma-yellow',
		),
		array(
			'label' => __('Creative', 'chroma-excellence'),
			'description' => __('Project work, storytelling, and playful expression.', 'chroma-excellence'),
			'value' => max(0, min(100, absint(get_post_meta($program_id, 'program_prism_creative', true) ?: 60))),
			'bar_class' => 'bg-chroma-red',
		),
		array(
			'label' => __('Physical', 'chroma-excellence'),
			'description' => __('Fine-motor practice, classroom stamina, and movement breaks.', 'chroma-excellence'),
			'value' => max(0, min(100, absint(get_post_meta($program_id, 'program_prism_physical', true) ?: 50))),
			'bar_class' => 'bg-brand-ink',
		),
	);

	$funding_cards = array(
		array(
			'eyebrow' => __('Georgia DECAL Summer Transition', 'chroma-excellence'),
			'title' => __('State-Funded Track', 'chroma-excellence'),
			'copy' => __('Backed by the Georgia Department of Early Care and Learning, this option provides free summer tuition for eligible children entering Kindergarten who need extra academic support before the school year begins.', 'chroma-excellence'),
			'points' => array(
				__('100% tuition covered for approved families', 'chroma-excellence'),
				__('Meals and classroom materials included', 'chroma-excellence'),
				__('Six-week intensive transition schedule', 'chroma-excellence'),
			),
			'accent_class' => 'border-chroma-green',
			'chip_class' => 'bg-chroma-greenLight text-chroma-green',
			'link_text' => __('Check Eligibility', 'chroma-excellence'),
		),
		array(
			'eyebrow' => __('Chroma Private Transition', 'chroma-excellence'),
			'title' => __('Private Tuition Track', 'chroma-excellence'),
			'copy' => __('Families who do not qualify for state funding can still access the same high-support Kindergarten bridge experience, with strong academics, small-group instruction, and optional wraparound care.', 'chroma-excellence'),
			'points' => array(
				__('Advanced literacy and math preparation', 'chroma-excellence'),
				__('Low teacher-to-student ratios', 'chroma-excellence'),
				__('Flexible care options for working families', 'chroma-excellence'),
			),
			'accent_class' => 'border-chroma-yellow',
			'chip_class' => 'bg-chroma-yellowLight text-chroma-yellow',
			'link_text' => __('Request Tuition Info', 'chroma-excellence'),
		),
	);

	$curriculum_cards = array(
		array(
			'icon_class' => 'fa-solid fa-spell-check',
			'icon_color' => 'text-chroma-blue',
			'icon_bg' => 'bg-chroma-blueLight',
			'title' => __('Literacy & Phonics', 'chroma-excellence'),
			'copy' => __('Children move from letter recognition into phonemic awareness, sound blending, handwriting stamina, and early reading routines that mirror Kindergarten expectations.', 'chroma-excellence'),
		),
		array(
			'icon_class' => 'fa-solid fa-calculator',
			'icon_color' => 'text-chroma-red',
			'icon_bg' => 'bg-chroma-redLight',
			'title' => __('Mathematical Reasoning', 'chroma-excellence'),
			'copy' => __('We go beyond counting songs into number sense, patterning, one-to-one correspondence, and the first building blocks of addition and subtraction.', 'chroma-excellence'),
		),
		array(
			'icon_class' => 'fa-solid fa-brain',
			'icon_color' => 'text-chroma-yellow',
			'icon_bg' => 'bg-chroma-yellowLight',
			'title' => __('Executive Function', 'chroma-excellence'),
			'copy' => __('The program intentionally teaches focus, independence, transitions, turn-taking, and the ability to follow multi-step directions with confidence.', 'chroma-excellence'),
		),
	);

	$schedule_tour_url = chroma_get_localized_url(home_url('/schedule-a-tour/'));
	$locations_url = chroma_get_localized_url(get_post_type_archive_link('location'));
	$contact_url = function_exists('chroma_get_page_link')
		? chroma_get_page_link('contact')
		: home_url('/contact-us/');
	$contact_url = chroma_get_localized_url($contact_url);

	if ($has_lesson_plan && function_exists('chroma_enqueue_pdf_assets')) {
		chroma_enqueue_pdf_assets();
	}
	?>

	<style>
		.rising-k-hero-panel {
			background:
				radial-gradient(circle at top right, rgba(227, 233, 236, 0.95), transparent 48%),
				linear-gradient(135deg, #ffffff 0%, #fffaf3 100%);
		}

		.rising-k-shell {
			position: relative;
			overflow: hidden;
		}

		.rising-k-shell::before {
			content: "";
			position: absolute;
			inset: auto auto -80px -80px;
			width: 220px;
			height: 220px;
			border-radius: 9999px;
			background: rgba(141, 163, 153, 0.16);
			filter: blur(10px);
		}

		.rising-k-score__bar {
			position: relative;
			height: 0.75rem;
			border-radius: 9999px;
			background: rgba(38, 50, 56, 0.08);
			overflow: hidden;
		}

		.rising-k-score__fill {
			display: block;
			height: 100%;
			width: var(--score-width, 0%);
			border-radius: 9999px;
		}
	</style>

	<section class="relative overflow-hidden bg-white border-b border-brand-ink/5">
		<div class="absolute inset-y-0 right-0 hidden lg:block w-1/2 bg-gradient-to-l from-chroma-blueLight/60 to-transparent" aria-hidden="true"></div>
		<div class="max-w-7xl mx-auto px-4 lg:px-6 py-16 lg:py-20 relative z-10 grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
			<div>
				<div class="inline-flex items-center gap-2 bg-white border border-chroma-blue/20 px-4 py-2 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-brand-ink shadow-sm mb-6">
					<i class="fa-solid fa-sun text-chroma-yellow" aria-hidden="true"></i>
					<span><?php esc_html_e('Summer Transition Program', 'chroma-excellence'); ?></span>
				</div>

				<?php if ($age_range): ?>
					<p class="text-xs font-bold uppercase tracking-[0.2em] text-chroma-blue mb-4"><?php echo esc_html($age_range); ?></p>
				<?php endif; ?>

				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink leading-tight mb-6"><?php echo esc_html($hero_title); ?></h1>
				<p class="text-lg text-brand-ink/80 leading-relaxed max-w-2xl"><?php echo wp_kses_post($hero_description); ?></p>

				<div class="flex flex-wrap gap-4 mt-10">
					<a href="#rising-k-funding" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-chroma-blue text-white text-xs font-bold uppercase tracking-[0.2em] shadow-soft hover:bg-brand-ink transition-colors">
						<?php esc_html_e('View Funding Options', 'chroma-excellence'); ?>
					</a>
					<a href="<?php echo esc_url($schedule_tour_url); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full border border-brand-ink/10 bg-white text-brand-ink text-xs font-bold uppercase tracking-[0.2em] hover:border-chroma-blue hover:text-chroma-blue transition-colors">
						<?php esc_html_e('Schedule a Tour', 'chroma-excellence'); ?>
					</a>
					<?php if ($has_lesson_plan): ?>
						<button type="button"
							class="chroma-pdf-trigger inline-flex items-center justify-center px-8 py-4 rounded-full border border-brand-ink/10 bg-white text-brand-ink text-xs font-bold uppercase tracking-[0.2em] hover:border-chroma-blue hover:text-chroma-blue transition-colors cursor-pointer"
							data-pdf-url="<?php echo esc_url($lesson_plan_url); ?>"
							data-pdf-title="<?php echo esc_attr(sprintf(__('%s Curriculum Guide', 'chroma-excellence'), get_the_title())); ?>">
							<?php esc_html_e('View Guide', 'chroma-excellence'); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>

			<div class="rising-k-shell rounded-[3rem] border border-brand-ink/5 shadow-soft p-4 lg:p-5 rising-k-hero-panel">
				<div class="relative h-[420px] lg:h-[500px] rounded-[2.5rem] overflow-hidden border-4 border-white shadow-2xl">
					<?php if ($hero_image_markup): ?>
						<?php echo $hero_image_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php else: ?>
						<div class="w-full h-full bg-gradient-to-br from-chroma-blueLight via-white to-brand-cream flex items-center justify-center p-10 text-center">
							<div>
								<div class="w-20 h-20 rounded-full bg-white shadow-soft mx-auto mb-6 flex items-center justify-center text-chroma-blue text-3xl">
									<i class="fa-solid fa-pencil" aria-hidden="true"></i>
								</div>
								<p class="font-serif text-3xl text-brand-ink mb-3"><?php esc_html_e('Kindergarten readiness starts here.', 'chroma-excellence'); ?></p>
								<p class="text-brand-ink/70 max-w-sm"><?php esc_html_e('Add a featured image to this program post for a fully visual hero while keeping the same layout.', 'chroma-excellence'); ?></p>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<section id="rising-k-funding" class="py-20 bg-brand-cream scroll-mt-28" aria-labelledby="rising-k-funding-title">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center max-w-3xl mx-auto mb-14">
				<p class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3"><?php esc_html_e('Program Tracks', 'chroma-excellence'); ?></p>
				<h2 id="rising-k-funding-title" class="font-serif text-3xl md:text-4xl font-bold text-brand-ink"><?php esc_html_e('State-Funded and Private Options', 'chroma-excellence'); ?></h2>
				<p class="text-brand-ink/80 mt-4"><?php esc_html_e('We offer two ways for families to access this 6-week summer bridge, so children can enter Kindergarten with stronger habits, stronger skills, and stronger confidence.', 'chroma-excellence'); ?></p>
			</div>

			<div class="grid md:grid-cols-2 gap-8 lg:gap-10">
				<?php foreach ($funding_cards as $card): ?>
					<article class="bg-white rounded-[2.5rem] shadow-soft border-t-8 <?php echo esc_attr($card['accent_class']); ?> p-8 lg:p-10">
						<div class="inline-flex items-center rounded-full px-4 py-2 text-[11px] font-bold uppercase tracking-[0.2em] mb-5 <?php echo esc_attr($card['chip_class']); ?>">
							<?php echo esc_html($card['eyebrow']); ?>
						</div>
						<h3 class="font-serif text-2xl font-bold text-brand-ink mb-4"><?php echo esc_html($card['title']); ?></h3>
						<p class="text-brand-ink/80 leading-relaxed mb-6"><?php echo esc_html($card['copy']); ?></p>
						<ul class="space-y-3 text-sm text-brand-ink/85 mb-8">
							<?php foreach ($card['points'] as $point): ?>
								<li class="flex items-start gap-3">
									<i class="fa-solid fa-check text-chroma-blue mt-1" aria-hidden="true"></i>
									<span><?php echo esc_html($point); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
						<a href="<?php echo esc_url($contact_url); ?>" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-brand-ink hover:text-chroma-blue transition-colors">
							<span><?php echo esc_html($card['link_text']); ?></span>
							<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="py-24 bg-white border-t border-brand-ink/5" aria-labelledby="rising-k-focus-title">
		<div class="max-w-6xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-14 items-center">
			<div class="bg-brand-cream rounded-[2.5rem] p-8 lg:p-10 border border-brand-ink/5 shadow-soft order-2 lg:order-1">
				<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php esc_html_e('Rising K Focus Profile', 'chroma-excellence'); ?></h3>
				<p class="text-sm text-brand-ink/75 mb-8"><?php esc_html_e('This summer model intentionally places the greatest emphasis on the academic and executive function skills children need most when they enter elementary school.', 'chroma-excellence'); ?></p>

				<ul class="space-y-5" aria-label="<?php esc_attr_e('Program focus scores by developmental area', 'chroma-excellence'); ?>">
					<?php foreach ($focus_scores as $score): ?>
						<li>
							<div class="flex items-baseline justify-between gap-4 mb-2">
								<div>
									<p class="font-bold text-brand-ink"><?php echo esc_html($score['label']); ?></p>
									<p class="text-sm text-brand-ink/70"><?php echo esc_html($score['description']); ?></p>
								</div>
								<p class="text-sm font-bold text-brand-ink whitespace-nowrap"><?php echo esc_html((string) $score['value']); ?>%</p>
							</div>
							<div class="rising-k-score__bar" aria-hidden="true">
								<span class="rising-k-score__fill <?php echo esc_attr($score['bar_class']); ?>" style="<?php echo esc_attr('--score-width:' . $score['value'] . '%;'); ?>"></span>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="order-1 lg:order-2">
				<p class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3"><?php esc_html_e('Prismpath Focus', 'chroma-excellence'); ?></p>
				<h2 id="rising-k-focus-title" class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-6"><?php esc_html_e('Bridging the gap before day one.', 'chroma-excellence'); ?></h2>
				<p class="text-lg text-brand-ink/80 leading-relaxed mb-6"><?php esc_html_e('Kindergarten moves fast. This template is built around the transition work families care about most: readiness in literacy, confidence in math, and the self-management skills children need in a larger school environment.', 'chroma-excellence'); ?></p>
				<ul class="space-y-4 text-sm text-brand-ink/85">
					<li class="flex items-start gap-4">
						<i class="fa-solid fa-book-open text-chroma-blue mt-1" aria-hidden="true"></i>
						<span><strong><?php esc_html_e('Academic:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Phonics, blending, beginning reading routines, number sense, and mathematical reasoning.', 'chroma-excellence'); ?></span>
					</li>
					<li class="flex items-start gap-4">
						<i class="fa-solid fa-users text-chroma-green mt-1" aria-hidden="true"></i>
						<span><strong><?php esc_html_e('Social:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Group participation, classroom collaboration, and peer conflict resolution.', 'chroma-excellence'); ?></span>
					</li>
					<li class="flex items-start gap-4">
						<i class="fa-solid fa-brain text-chroma-yellow mt-1" aria-hidden="true"></i>
						<span><strong><?php esc_html_e('Emotional:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Attention, transitions, independence, and comfort following multi-step directions.', 'chroma-excellence'); ?></span>
					</li>
				</ul>
			</div>
		</div>
	</section>

	<section class="py-24 bg-brand-ink text-white relative overflow-hidden" aria-labelledby="rising-k-curriculum-title">
		<div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.7) 1px, transparent 0); background-size: 32px 32px;" aria-hidden="true"></div>
		<div class="relative z-10 max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center max-w-3xl mx-auto mb-14">
				<p class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3"><?php esc_html_e('Curriculum Detail', 'chroma-excellence'); ?></p>
				<h2 id="rising-k-curriculum-title" class="font-serif text-3xl md:text-4xl font-bold"><?php esc_html_e('What we work on in 6 weeks', 'chroma-excellence'); ?></h2>
				<p class="text-white/80 mt-4"><?php esc_html_e('The page is structured around clear parent-facing outcomes: stronger literacy, deeper number sense, and the executive function skills that make Kindergarten feel manageable.', 'chroma-excellence'); ?></p>
			</div>

			<div class="grid md:grid-cols-3 gap-8">
				<?php foreach ($curriculum_cards as $card): ?>
					<article class="bg-white/5 border border-white/10 rounded-[2rem] p-8">
						<div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mb-6 <?php echo esc_attr($card['icon_bg']); ?> <?php echo esc_attr($card['icon_color']); ?>">
							<i class="<?php echo esc_attr($card['icon_class']); ?>" aria-hidden="true"></i>
						</div>
						<h3 class="font-serif text-xl font-bold mb-3"><?php echo esc_html($card['title']); ?></h3>
						<p class="text-sm text-white/80 leading-relaxed"><?php echo esc_html($card['copy']); ?></p>
					</article>
				<?php endforeach; ?>
			</div>

			<div class="mt-14 bg-white/5 border border-white/10 rounded-[2rem] p-8 lg:p-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
				<div>
					<h3 class="font-serif text-2xl font-bold mb-2"><?php esc_html_e('Ready to find the right campus?', 'chroma-excellence'); ?></h3>
					<p class="text-white/80"><?php esc_html_e('Use this program page with the standard Chroma header and footer, then direct families into location discovery or tour booking without sending them to a detached landing page.', 'chroma-excellence'); ?></p>
				</div>
				<div class="flex flex-wrap gap-4">
					<a href="<?php echo esc_url($locations_url); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-white text-brand-ink text-xs font-bold uppercase tracking-[0.2em] hover:bg-brand-cream transition-colors">
						<?php esc_html_e('View Locations', 'chroma-excellence'); ?>
					</a>
					<a href="<?php echo esc_url($schedule_tour_url); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full border border-white/20 text-white text-xs font-bold uppercase tracking-[0.2em] hover:bg-white hover:text-brand-ink transition-colors">
						<?php esc_html_e('Book Tour', 'chroma-excellence'); ?>
					</a>
				</div>
			</div>
		</div>
	</section>

<?php endwhile; ?>

<?php get_footer(); ?>

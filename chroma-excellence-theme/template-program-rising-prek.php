<?php
/**
 * Template Name: Rising Pre-K Summer Template
 * Template Post Type: program
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

$rising_prek_preload_id = get_queried_object_id();
$rising_prek_preload_url = '';

if ($rising_prek_preload_id) {
	$rising_prek_image_override = trim((string) get_post_meta($rising_prek_preload_id, 'program_hero_image', true));
	if ($rising_prek_image_override !== '') {
		$rising_prek_preload_url = $rising_prek_image_override;
	} elseif (has_post_thumbnail($rising_prek_preload_id)) {
		$rising_prek_preload_url = (string) get_the_post_thumbnail_url($rising_prek_preload_id, 'full');
	}
}

if ($rising_prek_preload_url !== '') {
	add_action(
		'wp_head',
		static function () use ($rising_prek_preload_url) {
			echo '<link rel="preload" as="image" href="' . esc_url($rising_prek_preload_url) . '" fetchpriority="high">' . "\n";
		},
		2
	);
}

get_header();

while (have_posts()):
	the_post();

	$program_id = get_the_ID();
	$age_range = chroma_get_translated_meta($program_id, 'program_age_range', true) ?: __('Ages 3-4', 'chroma-excellence');
	$lesson_plan_url = get_post_meta($program_id, 'program_lesson_plan_file', true);
	$has_lesson_plan = trim((string) $lesson_plan_url) !== '' && trim((string) $lesson_plan_url) !== '#';

	$hero_title = chroma_get_translated_meta($program_id, 'program_hero_title', true) ?: __('The perfect stepping stone to Pre-K.', 'chroma-excellence');
	$hero_description = chroma_get_translated_meta($program_id, 'program_hero_description', true) ?: __('Transitioning into a structured Pre-K environment is a major developmental leap. Our <strong>Rising Pre-K Summer Program</strong> is a joyful 6-week bridge that strengthens routines, social-emotional resilience, and foundational literacy before the fall semester begins.', 'chroma-excellence');
	$hero_image_override = trim((string) chroma_get_translated_meta($program_id, 'program_hero_image', true));

	if ($hero_image_override === '') {
		$hero_image_override = trim((string) get_post_meta($program_id, 'program_hero_image', true));
	}

	$hero_image_markup = '';
	if ($hero_image_override !== '') {
		$hero_image_markup = sprintf(
			'<img src="%1$s" alt="%2$s" class="w-full h-full object-cover" fetchpriority="high" loading="eager" decoding="async" sizes="(min-width: 1024px) 640px, 100vw" />',
			esc_url($hero_image_override),
			esc_attr__('Teacher and preschooler engaged in a joyful learning activity', 'chroma-excellence')
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
				'alt' => esc_attr__('Teacher and preschooler engaged in a joyful learning activity', 'chroma-excellence'),
			)
		);
	}

	$focus_scores = array(
		array(
			'label' => __('Social', 'chroma-excellence'),
			'description' => __('Taking turns, sharing, joining group routines, and learning alongside peers.', 'chroma-excellence'),
			'value' => max(0, min(100, absint(get_post_meta($program_id, 'program_prism_social', true) ?: 95))),
			'bar_class' => 'bg-chroma-green',
		),
		array(
			'label' => __('Emotional', 'chroma-excellence'),
			'description' => __('Safe separation, confidence with transitions, and expressing feelings clearly.', 'chroma-excellence'),
			'value' => max(0, min(100, absint(get_post_meta($program_id, 'program_prism_emotional', true) ?: 90))),
			'bar_class' => 'bg-chroma-red',
		),
		array(
			'label' => __('Creative', 'chroma-excellence'),
			'description' => __('Imaginative play, storytelling, music, and hands-on exploration.', 'chroma-excellence'),
			'value' => max(0, min(100, absint(get_post_meta($program_id, 'program_prism_creative', true) ?: 70))),
			'bar_class' => 'bg-chroma-yellow',
		),
		array(
			'label' => __('Academic', 'chroma-excellence'),
			'description' => __('Foundational alphabet and number exposure through play-based instruction.', 'chroma-excellence'),
			'value' => max(0, min(100, absint(get_post_meta($program_id, 'program_prism_academic', true) ?: 60))),
			'bar_class' => 'bg-chroma-blue',
		),
		array(
			'label' => __('Physical', 'chroma-excellence'),
			'description' => __('Fine-motor control, classroom stamina, and independence in care routines.', 'chroma-excellence'),
			'value' => max(0, min(100, absint(get_post_meta($program_id, 'program_prism_physical', true) ?: 60))),
			'bar_class' => 'bg-brand-ink',
		),
	);

	$funding_cards = array(
		array(
			'eyebrow' => __('Georgia DECAL Summer Transition', 'chroma-excellence'),
			'title' => __('State-Funded Track', 'chroma-excellence'),
			'copy' => __('Funded by the Georgia Department of Early Care and Learning, this option is free for eligible families and is designed to expand access for dual language learners and families meeting program requirements.', 'chroma-excellence'),
			'points' => array(
				__('100% tuition covered for approved families', 'chroma-excellence'),
				__('Meals and classroom materials included', 'chroma-excellence'),
				__('Six-week intensive transition schedule', 'chroma-excellence'),
			),
			'accent_class' => 'border-chroma-blue',
			'chip_class' => 'bg-chroma-blueLight text-chroma-blue',
			'link_text' => __('Check Eligibility', 'chroma-excellence'),
		),
		array(
			'eyebrow' => __('Chroma Private Transition', 'chroma-excellence'),
			'title' => __('Private Tuition Track', 'chroma-excellence'),
			'copy' => __('Families who do not meet DECAL requirements can still access the same classroom experience, curriculum, and teaching support through a private-pay summer bridge option.', 'chroma-excellence'),
			'points' => array(
				__('Identical Prismpath summer curriculum', 'chroma-excellence'),
				__('Low teacher-to-student ratios', 'chroma-excellence'),
				__('Flexible wraparound care options', 'chroma-excellence'),
			),
			'accent_class' => 'border-chroma-yellow',
			'chip_class' => 'bg-chroma-yellowLight text-chroma-yellow',
			'link_text' => __('Request Tuition Info', 'chroma-excellence'),
		),
	);

	$curriculum_cards = array(
		array(
			'icon_class' => 'fa-solid fa-users',
			'icon_color' => 'text-chroma-green',
			'icon_bg' => 'bg-chroma-greenLight',
			'title' => __('Social-Emotional Readiness', 'chroma-excellence'),
			'copy' => __('Children practice sharing, taking turns, expressing feelings safely, and separating from parents with increasing comfort and joy.', 'chroma-excellence'),
		),
		array(
			'icon_class' => 'fa-solid fa-list-check',
			'icon_color' => 'text-chroma-yellow',
			'icon_bg' => 'bg-chroma-yellowLight',
			'title' => __('Classroom Routines', 'chroma-excellence'),
			'copy' => __('The summer bridge builds familiarity with circle time, center transitions, hand washing, cleanup, and following two-step directions.', 'chroma-excellence'),
		),
		array(
			'icon_class' => 'fa-solid fa-shapes',
			'icon_color' => 'text-chroma-red',
			'icon_bg' => 'bg-chroma-redLight',
			'title' => __('Foundational Academics', 'chroma-excellence'),
			'copy' => __('Children build name recognition, fine-motor confidence, counting concepts, and early alphabet exposure through engaging play-based experiences.', 'chroma-excellence'),
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
		.rising-prek-hero-panel {
			background:
				radial-gradient(circle at top right, rgba(227, 235, 232, 0.95), transparent 50%),
				linear-gradient(135deg, #ffffff 0%, #fffaf3 100%);
		}

		.rising-prek-shell {
			position: relative;
			overflow: hidden;
		}

		.rising-prek-shell::before {
			content: "";
			position: absolute;
			inset: auto auto -80px -80px;
			width: 220px;
			height: 220px;
			border-radius: 9999px;
			background: rgba(227, 235, 232, 0.9);
			filter: blur(10px);
		}

		.rising-prek-score__bar {
			position: relative;
			height: 0.75rem;
			border-radius: 9999px;
			background: rgba(38, 50, 56, 0.08);
			overflow: hidden;
		}

		.rising-prek-score__fill {
			display: block;
			height: 100%;
			width: var(--score-width, 0%);
			border-radius: 9999px;
		}
	</style>

	<section class="relative overflow-hidden bg-white border-b border-brand-ink/5">
		<div class="absolute inset-y-0 right-0 hidden lg:block w-1/2 bg-gradient-to-l from-chroma-greenLight/70 to-transparent" aria-hidden="true"></div>
		<div class="max-w-7xl mx-auto px-4 lg:px-6 py-16 lg:py-20 relative z-10 grid lg:grid-cols-2 gap-10 lg:gap-14 items-center">
			<div>
				<div class="inline-flex items-center gap-2 bg-white border border-chroma-green/20 px-4 py-2 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-brand-ink shadow-sm mb-6">
					<i class="fa-solid fa-sun text-chroma-yellow" aria-hidden="true"></i>
					<span><?php esc_html_e('Summer Transition Program', 'chroma-excellence'); ?></span>
				</div>

				<?php if ($age_range): ?>
					<p class="text-xs font-bold uppercase tracking-[0.2em] text-chroma-green mb-4"><?php echo esc_html($age_range); ?></p>
				<?php endif; ?>

				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink leading-tight mb-6"><?php echo esc_html($hero_title); ?></h1>
				<p class="text-lg text-brand-ink/80 leading-relaxed max-w-2xl"><?php echo wp_kses_post($hero_description); ?></p>

				<div class="flex flex-wrap gap-4 mt-10">
					<a href="#rising-prek-funding" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-chroma-green text-white text-xs font-bold uppercase tracking-[0.2em] shadow-soft hover:bg-brand-ink transition-colors">
						<?php esc_html_e('View Funding Options', 'chroma-excellence'); ?>
					</a>
					<a href="<?php echo esc_url($schedule_tour_url); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full border border-brand-ink/10 bg-white text-brand-ink text-xs font-bold uppercase tracking-[0.2em] hover:border-chroma-green hover:text-chroma-green transition-colors">
						<?php esc_html_e('Schedule a Tour', 'chroma-excellence'); ?>
					</a>
					<?php if ($has_lesson_plan): ?>
						<button type="button"
							class="chroma-pdf-trigger inline-flex items-center justify-center px-8 py-4 rounded-full border border-brand-ink/10 bg-white text-brand-ink text-xs font-bold uppercase tracking-[0.2em] hover:border-chroma-green hover:text-chroma-green transition-colors cursor-pointer"
							data-pdf-url="<?php echo esc_url($lesson_plan_url); ?>"
							data-pdf-title="<?php echo esc_attr(sprintf(__('%s Curriculum Guide', 'chroma-excellence'), get_the_title())); ?>">
							<?php esc_html_e('View Guide', 'chroma-excellence'); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>

			<div class="rising-prek-shell rounded-[3rem] border border-brand-ink/5 shadow-soft p-4 lg:p-5 rising-prek-hero-panel">
				<div class="relative h-[420px] lg:h-[500px] rounded-[2.5rem] overflow-hidden border-4 border-white shadow-2xl">
					<?php if ($hero_image_markup): ?>
						<?php echo $hero_image_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php else: ?>
						<div class="w-full h-full bg-gradient-to-br from-chroma-greenLight via-white to-brand-cream flex items-center justify-center p-10 text-center">
							<div>
								<div class="w-20 h-20 rounded-full bg-white shadow-soft mx-auto mb-6 flex items-center justify-center text-chroma-green text-3xl">
									<i class="fa-solid fa-users" aria-hidden="true"></i>
								</div>
								<p class="font-serif text-3xl text-brand-ink mb-3"><?php esc_html_e('Pre-K confidence grows through routine.', 'chroma-excellence'); ?></p>
								<p class="text-brand-ink/70 max-w-sm"><?php esc_html_e('Add a featured image to this program post for a fully visual hero while preserving the same layout and performance profile.', 'chroma-excellence'); ?></p>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<section id="rising-prek-funding" class="py-20 bg-brand-cream scroll-mt-28" aria-labelledby="rising-prek-funding-title">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center max-w-3xl mx-auto mb-14">
				<p class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3"><?php esc_html_e('Program Tracks', 'chroma-excellence'); ?></p>
				<h2 id="rising-prek-funding-title" class="font-serif text-3xl md:text-4xl font-bold text-brand-ink"><?php esc_html_e('State-Funded and Private Options', 'chroma-excellence'); ?></h2>
				<p class="text-brand-ink/80 mt-4"><?php esc_html_e('Both tracks lead to the same Chroma summer bridge experience, giving families more than one way to secure a calm, confident start to Pre-K.', 'chroma-excellence'); ?></p>
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
									<i class="fa-solid fa-check text-chroma-green mt-1" aria-hidden="true"></i>
									<span><?php echo esc_html($point); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
						<a href="<?php echo esc_url($contact_url); ?>" class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-brand-ink hover:text-chroma-green transition-colors">
							<span><?php echo esc_html($card['link_text']); ?></span>
							<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
						</a>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="py-24 bg-white border-t border-brand-ink/5" aria-labelledby="rising-prek-focus-title">
		<div class="max-w-6xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-14 items-center">
			<div class="bg-brand-cream rounded-[2.5rem] p-8 lg:p-10 border border-brand-ink/5 shadow-soft order-2 lg:order-1">
				<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php esc_html_e('Rising Pre-K Focus Profile', 'chroma-excellence'); ?></h3>
				<p class="text-sm text-brand-ink/75 mb-8"><?php esc_html_e('This summer bridge intentionally favors the routines and emotional safety children need most before entering a more structured classroom environment.', 'chroma-excellence'); ?></p>

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
							<div class="rising-prek-score__bar" aria-hidden="true">
								<span class="rising-prek-score__fill <?php echo esc_attr($score['bar_class']); ?>" style="<?php echo esc_attr('--score-width:' . $score['value'] . '%;'); ?>"></span>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="order-1 lg:order-2">
				<p class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3"><?php esc_html_e('Prismpath Focus', 'chroma-excellence'); ?></p>
				<h2 id="rising-prek-focus-title" class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-6"><?php esc_html_e('Routines and resilience first.', 'chroma-excellence'); ?></h2>
				<p class="text-lg text-brand-ink/80 leading-relaxed mb-6"><?php esc_html_e('The Rising Pre-K page is centered on the transition parents worry about most: learning how school feels. That means social confidence, safe separations, classroom flow, and gentle exposure to academic building blocks.', 'chroma-excellence'); ?></p>
				<ul class="space-y-4 text-sm text-brand-ink/85">
					<li class="flex items-start gap-4">
						<i class="fa-solid fa-users text-chroma-green mt-1" aria-hidden="true"></i>
						<span><strong><?php esc_html_e('Social:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Taking turns, sharing, listening in groups, and building peer confidence.', 'chroma-excellence'); ?></span>
					</li>
					<li class="flex items-start gap-4">
						<i class="fa-solid fa-heart text-chroma-red mt-1" aria-hidden="true"></i>
						<span><strong><?php esc_html_e('Emotional:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Comfort with separation, emotional naming, and calm transitions through the day.', 'chroma-excellence'); ?></span>
					</li>
					<li class="flex items-start gap-4">
						<i class="fa-solid fa-shapes text-chroma-blue mt-1" aria-hidden="true"></i>
						<span><strong><?php esc_html_e('Academic:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Alphabet exposure, counting concepts, and fine-motor development woven into play.', 'chroma-excellence'); ?></span>
					</li>
				</ul>
			</div>
		</div>
	</section>

	<section class="py-24 bg-chroma-blueDark text-white relative overflow-hidden" aria-labelledby="rising-prek-curriculum-title">
		<div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.7) 1px, transparent 0); background-size: 32px 32px;" aria-hidden="true"></div>
		<div class="relative z-10 max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center max-w-3xl mx-auto mb-14">
				<p class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3"><?php esc_html_e('Curriculum Detail', 'chroma-excellence'); ?></p>
				<h2 id="rising-prek-curriculum-title" class="font-serif text-3xl md:text-4xl font-bold"><?php esc_html_e('What we teach in 6 weeks', 'chroma-excellence'); ?></h2>
				<p class="text-white/80 mt-4"><?php esc_html_e('The content is tuned to the exact behaviors and foundational skills Pre-K teachers look for when children walk in on the first day.', 'chroma-excellence'); ?></p>
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
					<h3 class="font-serif text-2xl font-bold mb-2"><?php esc_html_e('Ready to choose a campus for summer?', 'chroma-excellence'); ?></h3>
					<p class="text-white/80"><?php esc_html_e('This template stays inside the main Chroma experience, so families can move naturally from program discovery into location browsing or tour scheduling.', 'chroma-excellence'); ?></p>
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

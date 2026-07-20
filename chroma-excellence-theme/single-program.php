<?php
/**
 * Single Program Template
 *
 * @package Chroma_Excellence
 */

$chroma_program_object = get_queried_object();
if ($chroma_program_object instanceof WP_Post && $chroma_program_object->post_type === 'program' && $chroma_program_object->post_name === 'kindergarten-1') {
	require get_template_directory() . '/template-program-kindergarten.php';
	return;
}

get_header();

while (have_posts()):
	the_post();
	$program_id = get_the_ID();

	// Get program meta
	$age_range = chroma_get_translated_meta($program_id, 'program_age_range', true);
	$color_scheme = get_post_meta($program_id, 'program_color_scheme', true) ?: 'red';
	$lesson_plan_url = get_post_meta($program_id, 'program_lesson_plan_file', true);
	if (function_exists('chroma_normalize_owned_url')) {
		$lesson_plan_url = chroma_normalize_owned_url($lesson_plan_url);
	}
	$has_lesson_plan = trim((string) $lesson_plan_url) !== '' && trim((string) $lesson_plan_url) !== '#';

	$is_preschool_reference = 'preschool' === get_post_field('post_name', $program_id);
	$program_slug = (string) get_post_field('post_name', $program_id);
	$program_title_normalized = strtolower(wp_strip_all_tags((string) get_the_title($program_id)));
	$is_prek_literacy_program = in_array($program_slug, array('pre-k-ga-pre-k', 'ga-pre-k', 'pre-k', 'prek'), true)
		|| false !== strpos($program_title_normalized, 'ga pre')
		|| false !== strpos($program_title_normalized, 'pre-k')
		|| false !== strpos($program_title_normalized, 'pre k');

	// Hero section
	$hero_title = chroma_get_translated_meta($program_id, 'program_hero_title', true) ?: get_the_title();
	$hero_description = chroma_get_translated_meta($program_id, 'program_hero_description', true) ?: get_the_excerpt();
	if ($is_preschool_reference) {
		$hero_title = __('Centers of wonder.', 'chroma-excellence');
		$hero_description = __('Introduction to structured learning centers and collaborative play. We channel their boundless energy into creative expression and early concepts.', 'chroma-excellence');
	}

	// Prismpath section
	$prism_title = chroma_get_translated_meta($program_id, 'program_prism_title', true) ?: __('Our PrismPath™ Focus', 'chroma-excellence');
	$prism_description = chroma_get_translated_meta($program_id, 'program_prism_description', true);
	$prism_focus_items = chroma_get_translated_meta($program_id, 'program_prism_focus_items', true);

	// Chart data
	$prism_values = function_exists('chroma_program_prism_chart_values')
		? chroma_program_prism_chart_values($program_id)
		: array(50, 50, 50, 50, 50);
	$prism_physical = $prism_values[0];
	$prism_emotional = $prism_values[1];
	$prism_social = $prism_values[2];
	$prism_academic = $prism_values[3];
	$prism_creative = $prism_values[4];

	// Schedule
	$schedule_title = chroma_get_translated_meta($program_id, 'program_schedule_title', true) ?: __('A Rhythm, Not a Routine', 'chroma-excellence');
	$schedule_title = str_ireplace('Rythm', 'Rhythm', $schedule_title);
	$schedule_items = chroma_get_translated_meta($program_id, 'program_schedule_items', true);
	$schedule_steps = array();
	if ($schedule_items) {
		foreach (array_filter(array_map('trim', explode("\n", (string) $schedule_items))) as $item) {
			$parts = array_map('trim', explode('|', $item));
			if (count($parts) >= 3 && stripos($parts[0], 'note') === false) {
				$schedule_steps[] = array(
					'time' => $parts[0],
					'title' => $parts[1],
					'copy' => implode(' | ', array_slice($parts, 2)),
				);
			}
		}
	}

	// Color mapping
	$color_map = array(
		'red' => array('main' => 'chroma-red', 'light' => 'chroma-redLight'),
		'blue' => array('main' => 'chroma-blue', 'light' => 'chroma-blueLight'),
		'yellow' => array('main' => 'chroma-yellow', 'light' => 'chroma-yellowLight'),
		'blueDark' => array('main' => 'chroma-blueDark', 'light' => 'chroma-blueLight'),
		'green' => array('main' => 'chroma-green', 'light' => 'chroma-greenLight'),
		'orange' => array('main' => 'chroma-orange', 'light' => 'chroma-orangeLight'),
		'teal' => array('main' => 'chroma-teal', 'light' => 'chroma-tealLight'),
	);

	$colors = $color_map[$color_scheme] ?? $color_map['red'];
	$chart_colors = array(
		'red' => '#A84B38',
		'blue' => '#4A6C7C',
		'yellow' => '#C2A024',
		'blueDark' => '#2F4858',
		'green' => '#4A7C59',
		'orange' => '#C26524',
		'teal' => '#248EC2',
	);
	$hex_color = $chart_colors[$color_scheme] ?? '#A84B38';

	// Get featured image
	$hero_image = get_the_post_thumbnail_url($program_id, 'large');
	if (!$hero_image) {
		$hero_image = 'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?q=80&w=800&auto=format&fit=crop';
	}

	if ($has_lesson_plan && function_exists('chroma_enqueue_pdf_assets')) {
		chroma_enqueue_pdf_assets();
	}
	?>

	<main>
		<!-- Hero -->
		<section class="pageHero chroma-v2-page-hero relative pt-20 pb-20 bg-brand-cream overflow-hidden">
			<div
				class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-<?php echo esc_attr($colors['light']); ?>/30 to-transparent">
			</div>
			<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 grid lg:grid-cols-2 gap-12 items-center">
				<div class="fade-in-up">
					<?php if ($age_range): ?>
						<div
							class="inline-flex items-center gap-2 bg-white border border-<?php echo esc_attr($colors['main']); ?>/30 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-<?php echo esc_attr($colors['main']); ?> shadow-sm mb-6">
							<?php echo esc_html($age_range); ?>
						</div>
					<?php endif; ?>

					<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-10">
						<?php echo esc_html($hero_title); ?>
					</h1>

					<?php if ($hero_description): ?>
						<p class="text-lg text-brand-ink/80 max-w-2xl">
							<?php echo wp_kses_post(wpautop($hero_description)); ?>
						</p>
					<?php endif; ?>

					<div class="flex gap-4 flex-wrap" style="margin-top: 3rem;">
						<a href="#prism"
							class="px-8 py-4 bg-<?php echo esc_attr($colors['main']); ?> text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:opacity-90 transition-colors shadow-lg"><?php _e('View Curriculum', 'chroma-excellence'); ?></a>
						<?php if ($has_lesson_plan): ?>
							<button type="button"
								class="chroma-pdf-trigger px-8 py-4 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:border-<?php echo esc_attr($colors['main']); ?> hover:text-<?php echo esc_attr($colors['main']); ?> transition-colors cursor-pointer"
								data-pdf-url="<?php echo esc_url($lesson_plan_url); ?>"
								data-pdf-title="<?php printf(__('%s Lesson Plan', 'chroma-excellence'), esc_html(get_the_title())); ?>">
								<?php _e('View Lesson Plan', 'chroma-excellence'); ?>
							</button>
						<?php endif; ?>
						<a href="<?php echo chroma_get_localized_url(get_post_type_archive_link('location')); ?>"
							class="px-8 py-4 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:border-<?php echo esc_attr($colors['main']); ?> hover:text-<?php echo esc_attr($colors['main']); ?> transition-colors">
							<?php _e('View Locations', 'chroma-excellence'); ?>
						</a>
						<a href="<?php echo chroma_get_localized_url(home_url('/programs/')); ?>"
							class="px-8 py-4 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:border-<?php echo esc_attr($colors['main']); ?> hover:text-<?php echo esc_attr($colors['main']); ?> transition-colors">
							<?php _e('All Programs', 'chroma-excellence'); ?>
						</a>
					</div>
				</div>

				<div class="relative h-[500px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white fade-in-up"
					style="animation-delay: 0.2s;">
					<?php if (has_post_thumbnail()): ?>
						<?php the_post_thumbnail('large', array(
							'class' => 'w-full h-full object-cover',
							'alt' => get_the_title(),
							'fetchpriority' => 'high',
						)); ?>
					<?php else: ?>
						<img src="<?php echo esc_url($hero_image); ?>" class="w-full h-full object-cover"
							alt="<?php echo esc_attr(get_the_title()); ?>" fetchpriority="high" />
					<?php endif; ?>
				</div>
			</div>
		</section>

		<?php if ($is_preschool_reference): ?>
			<section class="white borderY py-20 bg-white border-y border-chroma-blue/10">
				<div class="max-w-6xl mx-auto px-4 lg:px-6 grid lg:grid-cols-[0.9fr_1.1fr] gap-10 items-center">
					<div>
						<div class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-4"><?php esc_html_e('Preschool', 'chroma-excellence'); ?></div>
						<h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] text-brand-ink mb-5">
							<?php esc_html_e('Busy hands, calm hearts.', 'chroma-excellence'); ?>
						</h2>
						<p class="text-brand-ink/75 text-lg leading-relaxed">
							<?php esc_html_e('Preschoolers are ready for bigger ideas, bigger friendships, and more independent routines. Chroma gives them the structure to feel safe and the freedom to explore.', 'chroma-excellence'); ?>
						</p>
					</div>
					<div class="rounded-[2.5rem] bg-brand-cream p-8 border border-chroma-blue/10 shadow-soft">
						<ul class="grid sm:grid-cols-2 gap-4 text-brand-ink/80">
							<li class="rounded-2xl bg-white p-4">
								<h3 class="font-bold"><?php esc_html_e('Learning Centers', 'chroma-excellence'); ?></h3>
							</li>
							<li class="rounded-2xl bg-white p-4 font-bold"><?php esc_html_e('Collaborative play', 'chroma-excellence'); ?></li>
							<li class="rounded-2xl bg-white p-4 font-bold"><?php esc_html_e('Early literacy', 'chroma-excellence'); ?></li>
							<li class="rounded-2xl bg-white p-4 font-bold"><?php esc_html_e('Creative expression', 'chroma-excellence'); ?></li>
						</ul>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- The Prismpath Focus (Chart) -->
		<section id="prism" class="cream py-24 bg-brand-cream">
			<div class="max-w-6xl mx-auto px-4 lg:px-6">
				<div class="grid lg:grid-cols-2 gap-16 items-center">
					<div class="bg-white rounded-[3rem] p-8 shadow-soft border border-brand-ink/5 order-2 lg:order-1">
						<div
							class="programChart radarChart"
							aria-label="<?php esc_attr_e('PrismPath five-pillar development chart', 'chroma-excellence'); ?>"
							data-radar-chart
							data-radar-color="<?php echo esc_attr($hex_color); ?>"
							data-radar-values="<?php echo esc_attr(wp_json_encode(array($prism_physical, $prism_emotional, $prism_social, $prism_academic, $prism_creative))); ?>">
							<svg class="radarSvg" viewBox="0 0 560 430" role="img" aria-labelledby="singleRadarTitle singleRadarDesc">
								<title id="singleRadarTitle"><?php esc_html_e('PrismPath five-pillar development chart', 'chroma-excellence'); ?></title>
								<desc id="singleRadarDesc"><?php printf(esc_html__('Radar chart showing the balance across physical, emotional, social, academic, and creative development for %s.', 'chroma-excellence'), esc_html(get_the_title())); ?></desc>
								<g class="radarGrid" data-radar-grid></g>
								<polygon class="radarArea" data-radar-area points=""></polygon>
								<polygon class="radarStroke" data-radar-stroke points=""></polygon>
								<g data-radar-points></g>
								<text class="radarLabel" x="280" y="35" text-anchor="middle"><?php esc_html_e('Physical', 'chroma-excellence'); ?></text>
								<text class="radarLabel" x="515" y="150" text-anchor="middle"><?php esc_html_e('Emotional', 'chroma-excellence'); ?></text>
								<text class="radarLabel" x="460" y="365" text-anchor="middle"><?php esc_html_e('Social', 'chroma-excellence'); ?></text>
								<text class="radarLabel" x="100" y="365" text-anchor="middle"><?php esc_html_e('Academic', 'chroma-excellence'); ?></text>
								<text class="radarLabel" x="45" y="150" text-anchor="middle"><?php esc_html_e('Creative', 'chroma-excellence'); ?></text>
							</svg>
							<p class="chartNote">
								<?php printf(esc_html__('The PrismPath™ balance for %s shifts across physical, emotional, social, academic, and creative development.', 'chroma-excellence'), esc_html(get_the_title())); ?>
							</p>
						</div>
					</div>
					<div class="order-1 lg:order-2">
						<span
							class="text-<?php echo esc_attr($colors['main']); ?> font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('PrismPath™ Focus', 'chroma-excellence'); ?></span>
						<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
							<?php echo esc_html($prism_title); ?>
						</h2>

						<?php if ($prism_description): ?>
							<div class="text-brand-ink/80 text-lg mb-6">
								<?php echo wp_kses_post(wpautop($prism_description)); ?>
							</div>
						<?php endif; ?>

						<?php if ($prism_focus_items):
							$focus_items_array = array_filter(array_map('trim', explode("\n", $prism_focus_items)));
							if (!empty($focus_items_array)):
								?>
								<ul class="space-y-3 text-sm text-brand-ink/90">
									<?php
									$item_colors = array('chroma-red', 'chroma-yellow', 'chroma-green', 'chroma-blue', 'brand-ink/20');
									foreach ($focus_items_array as $index => $item):
										$item_color = $item_colors[$index % count($item_colors)];
										?>
										<li class="flex gap-3 items-center">
											<span class="w-3 h-3 rounded-full bg-<?php echo esc_attr($item_color); ?>"></span>
											<?php echo esc_html($item); ?>
										</li>
									<?php endforeach; ?>
								</ul>
							<?php endif; endif; ?>
					</div>
				</div>
			</div>
		</section>

		<?php if ($is_prek_literacy_program): ?>
			<section class="chroma-prek-literacy-section white borderY py-20 md:py-24 bg-white border-y border-chroma-blue/10" style="--program-accent: <?php echo esc_attr($hex_color); ?>;">
				<div class="max-w-6xl mx-auto px-4 lg:px-6 grid lg:grid-cols-[0.95fr_1.05fr] gap-10 lg:gap-14 items-center">
					<div>
						<span class="inline-flex items-center gap-2 bg-brand-cream border border-brand-ink/10 px-4 py-2 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold mb-7" style="color: <?php echo esc_attr($hex_color); ?>;">
							<span class="w-2 h-2 rounded-full" style="background: <?php echo esc_attr($hex_color); ?>;" aria-hidden="true"></span>
							<?php esc_html_e('Pre-K literacy readiness', 'chroma-excellence'); ?>
						</span>
						<h2 class="font-serif text-4xl md:text-6xl font-semibold tracking-[-0.04em] leading-[0.98] text-brand-ink mb-6">
							<?php esc_html_e('Sound awareness that helps children step into kindergarten with confidence.', 'chroma-excellence'); ?>
						</h2>
						<p class="text-lg text-brand-ink/75 leading-relaxed">
							<?php esc_html_e('In Pre-K and GA Pre-K classrooms, literacy readiness grows through stories, songs, conversation, vocabulary, and playful sound work. Heggerty Phonics supports children as they learn to hear, blend, segment, and play with sounds.', 'chroma-excellence'); ?>
						</p>
					</div>
					<div class="chroma-prek-literacy-card">
						<article>
							<h3><?php esc_html_e('Heggerty Phonics', 'chroma-excellence'); ?></h3>
							<p><?php esc_html_e('Daily phonemic awareness practice helps children hear and work with sounds before formal reading begins.', 'chroma-excellence'); ?></p>
						</article>
						<article>
							<h3><?php esc_html_e('Language-rich classrooms', 'chroma-excellence'); ?></h3>
							<p><?php esc_html_e('Teachers reinforce literacy through read-alouds, songs, vocabulary, conversation, and purposeful play.', 'chroma-excellence'); ?></p>
						</article>
						<article>
							<h3><?php esc_html_e('Kindergarten confidence', 'chroma-excellence'); ?></h3>
							<p><?php esc_html_e('Children build listening, oral language, and sound-awareness skills that support a smoother next step.', 'chroma-excellence'); ?></p>
						</article>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- Schedule -->
		<?php if (!empty($schedule_steps)):
			$first_schedule_step = $schedule_steps[0];
			?>
			<section id="schedule" class="cream py-20 lg:py-24 bg-brand-cream relative">
				<div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-chroma-red via-chroma-yellow to-chroma-blue opacity-40"></div>
				<div class="max-w-6xl mx-auto px-4 lg:px-6">
					<div class="head reveal text-center max-w-3xl mx-auto mb-12">
						<span class="font-bold tracking-[0.2em] text-xs uppercase mb-4 block" style="color: <?php echo esc_attr($hex_color); ?>;">
							<?php esc_html_e('Sample Day', 'chroma-excellence'); ?>
						</span>
						<h2 class="text-3xl md:text-4xl font-serif text-brand-ink mb-3">
							<?php echo esc_html($schedule_title); ?>
						</h2>
						<p class="text-brand-ink max-w-2xl mx-auto">
							<?php printf(esc_html__('Slide through a sample %s day built from this program schedule.', 'chroma-excellence'), esc_html(get_the_title())); ?>
						</p>
					</div>

					<div class="day reveal single-program-sun-schedule" data-sun-schedule style="--program-accent: <?php echo esc_attr($hex_color); ?>;">
						<script type="application/json" data-sun-steps>
							<?php echo wp_json_encode($schedule_steps, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
						</script>
						<div class="sky" aria-hidden="true">
							<div class="sun" data-sun-orb></div>
							<div class="cloud c1"></div>
							<div class="cloud c2"></div>
						</div>
						<div class="panel">
							<div class="font-bold tracking-[0.2em] text-xs uppercase mb-3" style="color: <?php echo esc_attr($hex_color); ?>;">
								<?php echo esc_html(get_the_title()); ?>
							</div>
							<div class="time font-serif" data-sun-time><?php echo esc_html($first_schedule_step['time']); ?></div>
							<h3 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] text-brand-ink mb-4" data-sun-title>
								<?php echo esc_html($first_schedule_step['title']); ?>
							</h3>
							<p class="text-brand-ink/75 text-lg leading-relaxed min-h-[7rem]" data-sun-copy>
								<?php echo esc_html($first_schedule_step['copy']); ?>
							</p>
							<div class="track mt-8"><div class="progress" data-sun-progress></div></div>
							<input
								class="mt-5 w-full accent-chroma-yellow"
								data-sun-range
								type="range"
								min="0"
								max="<?php echo esc_attr(max(0, count($schedule_steps) - 1)); ?>"
								value="0"
								step="1"
								aria-label="<?php echo esc_attr($schedule_title); ?>"
							/>
						</div>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php
		get_template_part(
			'template-parts/program/required-details',
			null,
			array(
				'program_id' => $program_id,
				'program_title' => get_the_title($program_id),
				'program_slug' => $program_slug,
				'accent' => $hex_color,
			)
		);
		?>

		<?php if ($is_preschool_reference): ?>
			<section class="white borderY py-20 bg-white border-y border-chroma-blue/10">
				<div class="max-w-6xl mx-auto px-4 lg:px-6">
					<div class="max-w-3xl mb-10">
						<h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] text-brand-ink mb-4">
							<?php esc_html_e('No surprise add-ons.', 'chroma-excellence'); ?>
						</h2>
						<p class="text-brand-ink/70 text-lg leading-relaxed">
							<?php esc_html_e('Chroma keeps daily care practical for families, with the essentials built into the experience.', 'chroma-excellence'); ?>
						</p>
					</div>
					<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
						<?php foreach (array(__('Fresh meals', 'chroma-excellence'), __('Daily updates', 'chroma-excellence'), __('All materials', 'chroma-excellence'), __('Secure care', 'chroma-excellence')) as $included_item): ?>
							<article class="chroma-v2-card rounded-[1.5rem] p-6">
								<h3 class="font-serif text-2xl font-semibold text-brand-ink"><?php echo esc_html($included_item); ?></h3>
							</article>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
			<section class="cream py-20 bg-brand-cream">
				<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
					<h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] text-brand-ink mb-5">
						<?php esc_html_e('Ready to see the preschool room?', 'chroma-excellence'); ?>
					</h2>
					<a href="#tour" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-chroma-red text-white text-xs font-bold uppercase tracking-[0.18em] shadow-soft">
						<?php esc_html_e('Schedule a Tour', 'chroma-excellence'); ?>
					</a>
				</div>
			</section>
		<?php endif; ?>

	</main>


	<style>
		.fade-in-up {
			animation: fadeInUp 0.8s ease forwards;
			opacity: 0;
			transform: translateY(20px);
		}

		@keyframes fadeInUp {
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}
	</style>
	<?php
endwhile;
get_footer();

<?php
/**
 * Template Name: Kindergarten Template
 * Template Post Type: program
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

get_header();

while (have_posts()):
	the_post();

	$program_id = get_the_ID();
	$age_range = chroma_get_translated_meta($program_id, 'program_age_range', true) ?: __('5 Years (Private)', 'chroma-excellence');
	$lesson_plan_url = get_post_meta($program_id, 'program_lesson_plan_file', true);
	$has_lesson_plan = trim((string) $lesson_plan_url) !== '' && trim((string) $lesson_plan_url) !== '#';

	$hero_title = chroma_get_translated_meta($program_id, 'program_hero_title', true) ?: __('The ultimate foundation for 1st grade.', 'chroma-excellence');
	$hero_description = chroma_get_translated_meta($program_id, 'program_hero_description', true) ?: __('Our Private Kindergarten program blends the research-backed power of The Creative Curriculum with targeted, mastery-based instruction in reading and math. We do not just prepare students for 1st grade. We equip them with the critical thinking, independence, and confidence to lead.', 'chroma-excellence');

	$prism_title = chroma_get_translated_meta($program_id, 'program_prism_title', true) ?: __('Rigorous Academics. Joyful Discovery.', 'chroma-excellence');
	$prism_description = chroma_get_translated_meta($program_id, 'program_prism_description', true) ?: __('We believe that academic rigor and whole-child development are not mutually exclusive. By pairing purposeful play with explicit, teacher-led instruction, we cultivate a lasting love of learning alongside measurable academic mastery.', 'chroma-excellence');
	$prism_focus_items = chroma_get_translated_meta($program_id, 'program_prism_focus_items', true);
	$focus_items = $prism_focus_items ? array_filter(array_map('trim', explode("\n", $prism_focus_items))) : array(
		__('Advanced Literacy: Phonics, decoding, and reading fluency.', 'chroma-excellence'),
		__('Conceptual Math Mastery: Moving beyond rote memorization.', 'chroma-excellence'),
		__('Executive Function: Building focus, self-regulation, and collaborative problem-solving.', 'chroma-excellence'),
	);

	$chart_values = array(
		'physical' => absint(get_post_meta($program_id, 'program_prism_physical', true) ?: 60),
		'emotional' => absint(get_post_meta($program_id, 'program_prism_emotional', true) ?: 70),
		'social' => absint(get_post_meta($program_id, 'program_prism_social', true) ?: 85),
		'academic' => absint(get_post_meta($program_id, 'program_prism_academic', true) ?: 95),
		'creative' => absint(get_post_meta($program_id, 'program_prism_creative', true) ?: 80),
	);

	$hero_image = get_the_post_thumbnail_url($program_id, 'full');
	if (!$hero_image) {
		$hero_image = 'https://images.unsplash.com/photo-1588072432836-e10032774350?q=80&w=1400&auto=format&fit=crop';
	}

	$curriculum_cards = array(
		array(
			'icon' => 'fa-solid fa-lightbulb',
			'title' => __('The Creative Curriculum', 'chroma-excellence'),
			'description' => __('Our core framework delivers standards-aligned exploration in science, social studies, literacy, and the arts through project-based investigations.', 'chroma-excellence'),
		),
		array(
			'icon' => 'fa-solid fa-book-open',
			'title' => __('Heggerty Phonemic Awareness', 'chroma-excellence'),
			'description' => __('Explicit, systematic phonemic awareness instruction builds the blending, segmenting, and decoding skills strong readers need.', 'chroma-excellence'),
		),
		array(
			'icon' => 'fa-solid fa-shapes',
			'title' => __('Math-U-See', 'chroma-excellence'),
			'description' => __('Manipulative-based math instruction helps children move from counting to true conceptual understanding and durable confidence.', 'chroma-excellence'),
		),
	);

	$support_cards = array(
		array(
			'icon' => 'fa-solid fa-award',
			'accent' => 'text-chroma-blueLight',
			'title' => __('Georgia Accrediting Commission', 'chroma-excellence'),
			'description' => __('Our Private Kindergarten is aligned to the standards families expect from a strong 1st-grade bridge, supporting a smooth transition into public or private elementary pathways.', 'chroma-excellence'),
		),
		array(
			'icon' => 'fa-solid fa-shield-halved',
			'accent' => 'text-chroma-green',
			'title' => __('Promise Scholarships & SSOs', 'chroma-excellence'),
			'description' => __('Chroma works to keep premium early education accessible and can guide families through scholarship conversations, tuition planning, and next-step enrollment questions.', 'chroma-excellence'),
		),
	);

	$schedule_title = chroma_get_translated_meta($program_id, 'program_schedule_title', true) ?: __('Kindergarten Daily Rhythm', 'chroma-excellence');
	$schedule_items = chroma_get_translated_meta($program_id, 'program_schedule_items', true);
	$schedule = array();
	if ($schedule_items) {
		foreach (array_filter(array_map('trim', explode("\n", $schedule_items))) as $item) {
			$parts = array_map('trim', explode('|', $item));
			if (count($parts) >= 3) {
				$schedule[] = array(
					'time' => $parts[0],
					'title' => $parts[1],
					'copy' => $parts[2],
				);
			}
		}
	}

	if (empty($schedule)) {
		$schedule = array(
			array(
				'time' => '8:30',
				'title' => __('Morning Meeting & Literacy Block', 'chroma-excellence'),
				'copy' => __('We begin with social-emotional connection and move directly into teacher-led Heggerty routines that strengthen phonemic awareness, decoding, and fluent reading habits.', 'chroma-excellence'),
			),
			array(
				'time' => '10:00',
				'title' => __('Math-U-See & Guided Centers', 'chroma-excellence'),
				'copy' => __('Students explore mathematical logic using Math-U-See manipulatives, then rotate through guided reading, writing, and collaborative learning centers.', 'chroma-excellence'),
			),
			array(
				'time' => '1:30',
				'title' => __('Project-Based Learning', 'chroma-excellence'),
				'copy' => __('Children apply The Creative Curriculum through science investigations, social studies projects, artistic expression, and deeper critical thinking work.', 'chroma-excellence'),
			),
		);
	}

	$locations_url = chroma_get_localized_url(get_post_type_archive_link('location'));
	$programs_url = chroma_get_localized_url(home_url('/programs/'));
	$schedule_tour_url = chroma_get_localized_url(home_url('/schedule-a-tour/'));

	if ($has_lesson_plan && function_exists('chroma_enqueue_pdf_assets')) {
		chroma_enqueue_pdf_assets();
	}
	?>

	<style>
		.kinder-hero-glow {
			background:
				radial-gradient(circle at top right, rgba(74, 108, 124, 0.18), transparent 42%),
				radial-gradient(circle at bottom left, rgba(230, 190, 117, 0.14), transparent 40%);
		}

		.kinder-panel {
			backdrop-filter: blur(18px);
			background: linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(255, 252, 248, 0.94));
		}

		.kinder-card {
			box-shadow: 0 24px 60px -32px rgba(47, 72, 88, 0.24);
		}

		.kinder-next-steps {
			background: linear-gradient(135deg, #263238 0%, #2F4858 100%);
		}

		.kinder-chart-shell {
			min-height: 360px;
		}

		.kinder-rhythm::before {
			content: '';
			position: absolute;
			left: 2rem;
			top: 1rem;
			bottom: 1rem;
			width: 1px;
			background: rgba(38, 50, 56, 0.12);
		}

		@media (max-width: 767px) {
			.kinder-rhythm::before {
				left: 1.5rem;
			}
		}
	</style>

	<section class="relative overflow-hidden bg-white">
		<div class="absolute inset-0 kinder-hero-glow"></div>
		<div class="max-w-7xl mx-auto px-4 lg:px-6 pt-16 pb-20 lg:pt-24 lg:pb-24 relative z-10">
			<div class="grid lg:grid-cols-[1.05fr_0.95fr] gap-10 lg:gap-14 items-start">
				<div class="fade-in-up">
					<div class="inline-flex items-center gap-2 bg-white border border-chroma-blue/20 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-chroma-blue shadow-sm mb-6">
						<i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
						<?php echo esc_html($age_range); ?>
					</div>

					<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6 leading-tight">
						<?php echo esc_html($hero_title); ?>
					</h1>

					<p class="text-lg text-brand-ink/80 max-w-2xl">
						<?php echo esc_html($hero_description); ?>
					</p>

					<div class="flex flex-wrap gap-4 mt-8">
						<a href="<?php echo esc_url($schedule_tour_url); ?>"
							class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-brand-ink text-white text-xs font-bold uppercase tracking-[0.2em] shadow-soft hover:bg-chroma-blue transition-colors">
							<?php esc_html_e('Book Tour', 'chroma-excellence'); ?>
						</a>
						<a href="#kinder-focus"
							class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-white border border-brand-ink/10 text-brand-ink text-xs font-bold uppercase tracking-[0.2em] hover:border-chroma-blue hover:text-chroma-blue transition-colors">
							<?php esc_html_e('View Curriculum', 'chroma-excellence'); ?>
						</a>
						<?php if ($has_lesson_plan): ?>
							<button type="button"
								class="chroma-pdf-trigger inline-flex items-center justify-center px-8 py-4 rounded-full bg-white border border-brand-ink/10 text-brand-ink text-xs font-bold uppercase tracking-[0.2em] hover:border-chroma-blue hover:text-chroma-blue transition-colors cursor-pointer"
								data-pdf-url="<?php echo esc_url($lesson_plan_url); ?>"
								data-pdf-title="<?php echo esc_attr(sprintf(__('%s Curriculum Guide', 'chroma-excellence'), get_the_title())); ?>">
								<?php esc_html_e('View Guide', 'chroma-excellence'); ?>
							</button>
						<?php endif; ?>
					</div>
				</div>

				<div class="relative fade-in-up self-start" style="animation-delay: 0.15s;">
					<div class="absolute -inset-4 bg-chroma-blue/10 rounded-[3rem] blur-3xl"></div>
					<div class="relative h-[460px] md:h-[520px] rounded-[2.5rem] overflow-hidden border-4 border-white shadow-2xl">
						<img src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>"
							class="w-full h-full object-cover" fetchpriority="high" />
					</div>
					<div class="absolute -left-4 bottom-6 md:-left-10 md:bottom-10 max-w-[220px] kinder-panel rounded-[2rem] p-5 border border-white/70 shadow-soft">
						<p class="text-[10px] font-bold uppercase tracking-[0.22em] text-chroma-blue mb-2">
							<?php esc_html_e('1st Grade Readiness', 'chroma-excellence'); ?>
						</p>
						<p class="text-sm text-brand-ink/80 leading-relaxed">
							<?php esc_html_e('Reading, math, executive function, and classroom confidence are built together, not in silos.', 'chroma-excellence'); ?>
						</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<section id="kinder-focus" class="py-24 bg-brand-cream">
		<div class="max-w-6xl mx-auto px-4 lg:px-6">
			<div class="grid lg:grid-cols-2 gap-14 items-center">
				<div class="bg-white rounded-[3rem] p-8 lg:p-10 border border-brand-ink/5 shadow-soft kinder-chart-shell order-2 lg:order-1">
					<canvas id="kinderProgramChart" role="img"
						aria-label="<?php esc_attr_e('Radar chart showing Kindergarten curriculum focus across physical, emotional, social, academic, and creative development.', 'chroma-excellence'); ?>">
						<?php esc_html_e('A radar chart illustrating our Kindergarten focus across physical, emotional, social, academic, and creative development.', 'chroma-excellence'); ?>
					</canvas>
				</div>
				<div class="order-1 lg:order-2">
					<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
						<?php esc_html_e('Prismpath Focus', 'chroma-excellence'); ?>
					</span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
						<?php echo esc_html($prism_title); ?>
					</h2>
					<p class="text-brand-ink/80 text-lg mb-7">
						<?php echo esc_html($prism_description); ?>
					</p>

					<ul class="space-y-4 text-sm text-brand-ink/85">
						<?php
						$focus_icon_classes = array(
							'fa-solid fa-book-open text-chroma-blue',
							'fa-solid fa-chart-line text-chroma-red',
							'fa-solid fa-users text-chroma-green',
						);
						foreach ($focus_items as $index => $item):
							$icon_class = $focus_icon_classes[$index] ?? 'fa-solid fa-circle-check text-chroma-blueDark';
							?>
							<li class="flex gap-3 items-start">
								<i class="<?php echo esc_attr($icon_class); ?> mt-0.5" aria-hidden="true"></i>
								<span><?php echo esc_html($item); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
	</section>

	<section class="py-20 bg-white border-t border-brand-ink/5">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center max-w-3xl mx-auto mb-14">
				<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
					<?php esc_html_e('Curriculum Deep Dive', 'chroma-excellence'); ?>
				</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-4">
					<?php esc_html_e('An Elite Instructional Framework', 'chroma-excellence'); ?>
				</h2>
				<p class="text-brand-ink/80">
					<?php esc_html_e('We layer proven supplemental resources into the Kindergarten classroom to ensure children leave with a strong academic base and a genuine love of learning.', 'chroma-excellence'); ?>
				</p>
			</div>

			<div class="grid md:grid-cols-3 gap-8">
				<?php foreach ($curriculum_cards as $card): ?>
					<article class="p-8 bg-chroma-blueLight/40 rounded-[2rem] border border-chroma-blue/15 hover:-translate-y-1 transition-transform kinder-card">
						<div class="w-14 h-14 bg-white rounded-2xl shadow-sm flex items-center justify-center text-chroma-blue text-2xl mb-6">
							<i class="<?php echo esc_attr($card['icon']); ?>" aria-hidden="true"></i>
						</div>
						<h3 class="font-serif text-xl font-bold mb-3 text-brand-ink">
							<?php echo esc_html($card['title']); ?>
						</h3>
						<p class="text-sm text-brand-ink/80 leading-relaxed">
							<?php echo esc_html($card['description']); ?>
						</p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="py-20 bg-chroma-blueDark text-white overflow-hidden relative">
		<div class="absolute -right-16 top-0 w-72 h-72 bg-white/5 rounded-full blur-3xl"></div>
		<div class="max-w-5xl mx-auto px-4 lg:px-6 relative z-10">
			<div class="text-center mb-10">
				<span class="text-chroma-blueLight font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
					<?php esc_html_e('Accreditation & Affordability', 'chroma-excellence'); ?>
				</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold">
					<?php esc_html_e('Recognized Excellence. Accessible to Families.', 'chroma-excellence'); ?>
				</h2>
			</div>

			<div class="grid md:grid-cols-2 gap-8">
				<?php foreach ($support_cards as $card): ?>
					<article class="bg-white/5 p-8 rounded-[2rem] border border-white/10 hover:bg-white/10 transition-colors">
						<div class="text-4xl mb-4 <?php echo esc_attr($card['accent']); ?>">
							<i class="<?php echo esc_attr($card['icon']); ?>" aria-hidden="true"></i>
						</div>
						<h3 class="font-bold text-xl mb-3">
							<?php echo esc_html($card['title']); ?>
						</h3>
						<p class="text-sm text-white/90 leading-relaxed">
							<?php echo esc_html($card['description']); ?>
						</p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="py-24 bg-brand-cream border-t border-brand-ink/5">
		<div class="max-w-4xl mx-auto px-4 lg:px-6">
			<h2 class="text-3xl font-serif font-bold text-center text-brand-ink mb-12">
				<?php echo esc_html($schedule_title); ?>
			</h2>

			<div class="relative space-y-8 kinder-rhythm">
				<?php foreach ($schedule as $step): ?>
					<div class="flex gap-6 md:gap-8 items-start relative">
						<div class="w-12 h-12 md:w-16 md:h-16 rounded-full bg-chroma-blueLight text-brand-ink font-bold flex items-center justify-center shrink-0 z-10 border-4 border-white shadow-sm text-xs md:text-sm">
							<?php echo esc_html($step['time']); ?>
						</div>
						<div class="pt-2 md:pt-3">
							<h3 class="font-bold text-lg text-brand-ink mb-2">
								<?php echo esc_html($step['title']); ?>
							</h3>
							<p class="text-brand-ink/80 leading-relaxed">
								<?php echo esc_html($step['copy']); ?>
							</p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="py-20 bg-white border-t border-brand-ink/5">
		<div class="max-w-5xl mx-auto px-4 lg:px-6">
			<div class="rounded-[2.5rem] kinder-next-steps text-white p-10 md:p-14 text-center shadow-2xl">
				<span class="text-chroma-yellow font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
					<?php esc_html_e('Next Steps', 'chroma-excellence'); ?>
				</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold mb-4">
					<?php esc_html_e('Ready to explore Kindergarten at Chroma?', 'chroma-excellence'); ?>
				</h2>
				<p class="text-white/80 text-lg max-w-2xl mx-auto mb-8">
					<?php esc_html_e('Visit a campus, meet our leadership team, and see how our Kindergarten classrooms build confident, first-grade-ready learners.', 'chroma-excellence'); ?>
				</p>
				<div class="flex flex-wrap justify-center gap-4">
					<a href="<?php echo esc_url($locations_url); ?>"
						class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-white text-brand-ink text-xs font-bold uppercase tracking-[0.2em] hover:bg-chroma-yellow hover:text-brand-ink transition-colors">
						<?php esc_html_e('Find a Campus', 'chroma-excellence'); ?>
					</a>
					<a href="<?php echo esc_url($programs_url); ?>"
						class="inline-flex items-center justify-center px-8 py-4 rounded-full border border-white/20 text-white text-xs font-bold uppercase tracking-[0.2em] hover:bg-white/10 transition-colors">
						<?php esc_html_e('All Programs', 'chroma-excellence'); ?>
					</a>
				</div>
			</div>
		</div>
	</section>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const chartCanvas = document.getElementById('kinderProgramChart');

			if (!chartCanvas) {
				return;
			}

			const createChart = function () {
				if (typeof Chart === 'undefined') {
					return;
				}

				new Chart(chartCanvas, {
					type: 'radar',
					data: {
						labels: [
							'<?php echo esc_js(__('Physical', 'chroma-excellence')); ?>',
							'<?php echo esc_js(__('Emotional', 'chroma-excellence')); ?>',
							'<?php echo esc_js(__('Social', 'chroma-excellence')); ?>',
							'<?php echo esc_js(__('Academic', 'chroma-excellence')); ?>',
							'<?php echo esc_js(__('Creative', 'chroma-excellence')); ?>'
						],
						datasets: [{
							label: '<?php echo esc_js(get_the_title() . ' ' . __('Focus', 'chroma-excellence')); ?>',
							data: [
								<?php echo (int) $chart_values['physical']; ?>,
								<?php echo (int) $chart_values['emotional']; ?>,
								<?php echo (int) $chart_values['social']; ?>,
								<?php echo (int) $chart_values['academic']; ?>,
								<?php echo (int) $chart_values['creative']; ?>
							],
							backgroundColor: 'rgba(74, 108, 124, 0.18)',
							borderColor: '#4A6C7C',
							pointBackgroundColor: '#ffffff',
							pointBorderColor: '#4A6C7C',
							borderWidth: 2
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						scales: {
							r: {
								suggestedMin: 0,
								suggestedMax: 100,
								angleLines: { color: '#d7dee2' },
								grid: { color: '#d7dee2' },
								pointLabels: {
									color: '#263238',
									font: {
										family: 'Outfit',
										size: 13
									}
								},
								ticks: {
									display: false,
									stepSize: 20
								}
							}
						},
						plugins: {
							legend: {
								display: false
							}
						}
					}
				});
			};

			const loadChartLibrary = function () {
				if (typeof Chart !== 'undefined') {
					createChart();
					return;
				}

				const script = document.createElement('script');
				script.src = '<?php echo esc_url(get_template_directory_uri() . '/assets/js/chart.min.js'); ?>';
				script.async = true;
				script.onload = createChart;
				document.body.appendChild(script);
			};

			if ('IntersectionObserver' in window) {
				const observer = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (!entry.isIntersecting) {
							return;
						}

						observer.disconnect();
						loadChartLibrary();
					});
				}, { rootMargin: '100px 0px' });

				observer.observe(chartCanvas);
				return;
			}

			loadChartLibrary();
		});
	</script>

<?php endwhile; ?>

<?php
get_footer();

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
	$hero_description = chroma_get_translated_meta($program_id, 'program_hero_description', true) ?: __('Our Private Kindergarten program blends the research-backed power of <strong>The Creative Curriculum®</strong> with targeted, mastery-based instruction in reading and math. We don\'t just prepare students for 1st grade—we equip them with the critical thinking, independence, and unshakeable confidence to lead.', 'chroma-excellence');
	$hero_image_override = trim((string) chroma_get_translated_meta($program_id, 'program_hero_image', true));
	if ($hero_image_override === '') {
		$hero_image_override = trim((string) get_post_meta($program_id, 'program_hero_image', true));
	}

	$prism_title = chroma_get_translated_meta($program_id, 'program_prism_title', true) ?: __('Rigorous Academics. Joyful Discovery.', 'chroma-excellence');
	$prism_description = chroma_get_translated_meta($program_id, 'program_prism_description', true) ?: __('We believe that academic rigor and whole-child development are not mutually exclusive. By seamlessly integrating purposeful play with explicit, teacher-directed instruction, we cultivate a deep, enduring love of learning alongside tangible academic mastery.', 'chroma-excellence');
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

	$hero_image_markup = '';
	$hero_image_attachment_id = get_post_thumbnail_id($program_id);
	$hero_image_fallback = trim((string) get_theme_mod('chroma_default_og_image', ''));

	if ($hero_image_override !== '') {
		$hero_image_markup = sprintf(
			'<img src="%1$s" alt="%2$s" class="w-full h-full object-cover" fetchpriority="high" loading="eager" decoding="async" />',
			esc_url($hero_image_override),
			esc_attr(get_the_title())
		);
	} elseif ($hero_image_attachment_id) {
		$hero_image_markup = wp_get_attachment_image(
			$hero_image_attachment_id,
			'full',
			false,
			array(
				'class' => 'w-full h-full object-cover',
				'fetchpriority' => 'high',
				'loading' => 'eager',
				'decoding' => 'async',
				'sizes' => '(min-width: 1024px) 1400px, 100vw',
			)
		);
	} elseif ($hero_image_fallback !== '') {
		$hero_image_markup = sprintf(
			'<img src="%1$s" alt="%2$s" class="w-full h-full object-cover" fetchpriority="high" loading="eager" decoding="async" />',
			esc_url($hero_image_fallback),
			esc_attr(get_the_title())
		);
	} else {
		$hero_image_markup = sprintf(
			'<img src="%1$s" alt="%2$s" class="w-full h-full object-cover" fetchpriority="high" loading="eager" decoding="async" />',
			esc_url('https://images.unsplash.com/photo-1588072432836-e10032774350?q=80&w=1200&auto=format&fit=crop'),
			esc_attr(get_the_title())
		);
	}

	$curriculum_cards = array(
		array(
			'icon' => 'fa-solid fa-lightbulb',
			'title' => __('The Creative Curriculum®', 'chroma-excellence'),
			'description' => __('Our core framework provides an immersive, standards-aligned exploration of science, social studies, and the arts through dynamic, project-based investigations.', 'chroma-excellence'),
		),
		array(
			'icon' => 'fa-solid fa-spell-check',
			'title' => __('Heggerty Phonemic Awareness', 'chroma-excellence'),
			'description' => __('Explicit, systematic phonemic awareness training. Heggerty is a proven methodology that rapidly builds the decoding and blending skills necessary for fluent, confident early readers.', 'chroma-excellence'),
		),
		array(
			'icon' => 'fa-solid fa-cubes',
			'title' => __('Math-U-See', 'chroma-excellence'),
			'description' => __('A revolutionary, manipulative-based approach. Math-U-See shifts students from simple counting to true conceptual mastery, building deep mathematical confidence step-by-step.', 'chroma-excellence'),
		),
	);

	$support_cards = array(
		array(
			'icon' => 'fa-solid fa-award',
			'title' => __('Georgia Accrediting Commission', 'chroma-excellence'),
			'description' => __('Our Private Kindergarten is fully accredited by the <strong>Georgia Accrediting Commission (GAC)</strong>. This prestigious designation guarantees our program meets or exceeds the highest educational and operational standards in the state, ensuring a seamless academic transition to any public or private 1st-grade program.', 'chroma-excellence'),
		),
		array(
			'icon' => 'fa-solid fa-hand-holding-dollar',
			'title' => __('Promise Scholarships & SSOs', 'chroma-excellence'),
			'description' => __('We believe a premium education should be within reach. Chroma proudly accepts the <strong>Georgia Promise Scholarship</strong> and partners with multiple <strong>Student Scholarship Organizations (SSOs)</strong> to help eligible families offset the cost of private kindergarten tuition.', 'chroma-excellence'),
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
				'copy' => __('We start with social-emotional connections, transitioning directly into dynamic, teacher-led <strong>Heggerty</strong> exercises to explicitly build phonemic awareness and decoding skills.', 'chroma-excellence'),
			),
			array(
				'time' => '10:00',
				'title' => __('Math-U-See & Guided Centers', 'chroma-excellence'),
				'copy' => __('Targeted exploration of mathematical logic using <strong>Math-U-See</strong> manipulatives, followed by independent reading, writing, and collaborative learning centers.', 'chroma-excellence'),
			),
			array(
				'time' => '1:30',
				'title' => __('Project-Based Learning', 'chroma-excellence'),
				'copy' => __('Applying <strong>The Creative Curriculum®</strong> through deep scientific investigations, artistic expression, and social studies projects that challenge students to think critically.', 'chroma-excellence'),
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

	<section class="relative pt-20 pb-20 bg-white">
		<div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-chroma-blueLight/60 to-transparent"></div>
		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 grid lg:grid-cols-2 gap-12 items-center">
			<div>
				<?php if ($age_range): ?>
					<div class="inline-flex items-center gap-2 bg-white border border-chroma-blue/30 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-brand-ink shadow-sm mb-6">
						<?php echo esc_html($age_range); ?>
					</div>
				<?php endif; ?>

				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6">
					<?php echo esc_html($hero_title); ?>
				</h1>
				<p class="text-lg text-brand-ink/80 mb-8">
					<?php echo wp_kses_post($hero_description); ?>
				</p>

				<div class="flex flex-wrap gap-4 mt-10">
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

			<div class="relative h-[500px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white mt-10 lg:mt-0">
				<?php echo $hero_image_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</section>

	<section id="kinder-focus" class="py-24 bg-brand-cream">
		<div class="max-w-6xl mx-auto px-4 lg:px-6">
			<div class="grid lg:grid-cols-2 gap-16 items-center">
				<div class="bg-white rounded-[3rem] p-8 shadow-soft border border-brand-ink/5 order-2 lg:order-1" style="min-height: 360px;">
					<canvas id="kinderProgramChart" role="img"
						aria-label="<?php esc_attr_e('Radar chart showing Kindergarten curriculum focus across physical, emotional, social, academic, and creative development.', 'chroma-excellence'); ?>">
						<?php esc_html_e('A radar chart illustrating our Kindergarten focus across physical, emotional, social, academic, and creative development.', 'chroma-excellence'); ?>
					</canvas>
				</div>
				<div class="order-1 lg:order-2">
					<span class="text-brand-ink font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
						<?php esc_html_e('Prismpath™ Focus', 'chroma-excellence'); ?>
					</span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
						<?php echo esc_html($prism_title); ?>
					</h2>
					<p class="text-brand-ink/80 text-lg mb-6">
						<?php echo esc_html($prism_description); ?>
					</p>

					<ul class="space-y-3 text-sm text-brand-ink/80">
						<?php
						$focus_icon_classes = array(
							'fa-solid fa-book-open text-chroma-blue',
							'fa-solid fa-calculator text-chroma-red',
							'fa-solid fa-users-gear text-chroma-green',
						);
						foreach ($focus_items as $index => $item):
							$icon_class = $focus_icon_classes[$index] ?? 'fa-solid fa-circle-check text-chroma-blueDark';
							$parts = explode(':', $item, 2);
							?>
							<li class="flex gap-4 items-start">
								<span class="w-5 shrink-0 mt-1 text-center" aria-hidden="true">
									<i class="<?php echo esc_attr($icon_class); ?>"></i>
								</span>
								<span>
									<?php if (count($parts) === 2): ?>
										<strong><?php echo esc_html($parts[0]); ?>:</strong><?php echo esc_html($parts[1]); ?>
									<?php else: ?>
										<?php echo esc_html($item); ?>
									<?php endif; ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
	</section>

	<section class="py-20 bg-white border-t border-brand-ink/5">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center mb-16">
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink">
					<?php esc_html_e('An Elite Instructional Framework', 'chroma-excellence'); ?>
				</h2>
				<p class="text-brand-ink/80 mt-4 max-w-2xl mx-auto">
					<?php esc_html_e('We incorporate targeted, elite supplemental resources to guarantee a superior foundation in both reading and mathematics.', 'chroma-excellence'); ?>
				</p>
			</div>

			<div class="grid md:grid-cols-3 gap-8">
				<?php foreach ($curriculum_cards as $card): ?>
					<div class="p-8 bg-chroma-blueLight/50 rounded-[2rem] border border-chroma-blue/20 hover:-translate-y-1 transition-transform">
						<div class="w-14 h-14 bg-white rounded-2xl shadow-sm flex items-center justify-center text-chroma-blue text-2xl mb-6">
							<i class="<?php echo esc_attr($card['icon']); ?>" aria-hidden="true"></i>
						</div>
						<h3 class="font-serif text-xl font-bold mb-3 text-brand-ink">
							<?php echo esc_html($card['title']); ?>
						</h3>
						<p class="text-sm text-brand-ink/80 leading-relaxed">
							<?php echo esc_html($card['description']); ?>
						</p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="py-20 bg-chroma-blueDark text-white">
		<div class="max-w-5xl mx-auto px-4 lg:px-6 text-center">
			<span class="text-chroma-blueLight font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
				<?php esc_html_e('Accreditation & Affordability', 'chroma-excellence'); ?>
			</span>
			<h2 class="text-3xl md:text-4xl font-serif font-bold mb-10">
				<?php esc_html_e('Recognized Excellence. Accessible to All.', 'chroma-excellence'); ?>
			</h2>
			
			<div class="grid md:grid-cols-2 gap-8 text-left">
				<?php foreach ($support_cards as $index => $card): ?>
					<?php
					$icon_color = ($index === 0) ? 'text-chroma-blueLight' : '';
					$icon_style = ($index === 1) ? 'color: #A6C1B3;' : '';
					?>
					<div class="bg-white/5 p-8 rounded-3xl border border-white/10 hover:bg-white/10 transition-colors">
						<div class="text-4xl mb-4 <?php echo esc_attr($icon_color); ?>" style="<?php echo esc_attr($icon_style); ?>">
							<i class="<?php echo esc_attr($card['icon']); ?>" aria-hidden="true"></i>
						</div>
						<h3 class="font-bold text-xl mb-3">
							<?php echo esc_html($card['title']); ?>
						</h3>
						<p class="text-sm text-white/90 leading-relaxed">
							<?php echo wp_kses_post($card['description']); ?>
						</p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="py-24 bg-brand-cream border-t border-brand-ink/5">
		<div class="max-w-4xl mx-auto px-4 lg:px-6">
			<h2 class="text-3xl font-serif font-bold text-center mb-12 text-brand-ink">
				<?php echo esc_html($schedule_title); ?>
			</h2>

			<div class="space-y-8 relative before:absolute before:left-8 before:top-4 before:bottom-4 before:w-0.5 before:bg-brand-ink/10">
				<?php foreach ($schedule as $step): ?>
					<div class="flex gap-8 items-start relative">
						<div class="w-16 h-16 rounded-full bg-chroma-blueLight text-brand-ink font-bold flex items-center justify-center shrink-0 z-10 border-4 border-white shadow-sm text-sm" aria-hidden="true">
							<?php echo esc_html($step['time']); ?>
						</div>
						<div class="pt-3">
							<h3 class="font-bold text-lg text-brand-ink">
								<?php echo esc_html($step['title']); ?>
							</h3>
							<p class="text-brand-ink/80">
								<?php echo wp_kses_post($step['copy']); ?>
							</p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="py-20 bg-white border-t border-brand-ink/5">
		<div class="max-w-5xl mx-auto px-4 lg:px-6">
			<div class="rounded-[2.5rem] bg-gradient-to-br from-brand-ink to-[#2F4858] text-white p-10 md:p-14 text-center shadow-2xl">
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
							backgroundColor: 'rgba(74, 108, 124, 0.2)',
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

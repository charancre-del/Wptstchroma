<?php
/**
 * Single Program Template
 *
 * @package Chroma_Excellence
 */

get_header();

while (have_posts()):
	the_post();
	$program_id = get_the_ID();

	// Get program meta
	$age_range = chroma_get_translated_meta($program_id, 'program_age_range', true);
	$color_scheme = get_post_meta($program_id, 'program_color_scheme', true) ?: 'red';
	$lesson_plan_url = get_post_meta($program_id, 'program_lesson_plan_file', true);

	// Hero section
	$hero_title = chroma_get_translated_meta($program_id, 'program_hero_title', true) ?: get_the_title();
	$hero_description = chroma_get_translated_meta($program_id, 'program_hero_description', true) ?: get_the_excerpt();

	// Prismpath section
	$prism_title = chroma_get_translated_meta($program_id, 'program_prism_title', true) ?: __('Our Prismpath™ Focus', 'chroma-excellence');
	$prism_description = chroma_get_translated_meta($program_id, 'program_prism_description', true);
	$prism_focus_items = chroma_get_translated_meta($program_id, 'program_prism_focus_items', true);

	// Chart data
	$prism_physical = get_post_meta($program_id, 'program_prism_physical', true) ?: '50';
	$prism_emotional = get_post_meta($program_id, 'program_prism_emotional', true) ?: '50';
	$prism_social = get_post_meta($program_id, 'program_prism_social', true) ?: '50';
	$prism_academic = get_post_meta($program_id, 'program_prism_academic', true) ?: '50';
	$prism_creative = get_post_meta($program_id, 'program_prism_creative', true) ?: '50';

	// Schedule
	$schedule_title = chroma_get_translated_meta($program_id, 'program_schedule_title', true) ?: __('A Rhythm, Not a Routine', 'chroma-excellence');
	$schedule_items = chroma_get_translated_meta($program_id, 'program_schedule_items', true);

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

	// Get featured image
	$hero_image = get_the_post_thumbnail_url($program_id, 'large');
	if (!$hero_image) {
		$hero_image = 'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?q=80&w=800&auto=format&fit=crop';
	}
	?>

	<main>
		<!-- Hero -->
		<section class="relative pt-20 pb-20 bg-white overflow-hidden">
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
						<?php if ($lesson_plan_url): ?>
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

		<!-- The Prismpath Focus (Chart) -->
		<section id="prism" class="py-24 bg-brand-cream">
			<div class="max-w-6xl mx-auto px-4 lg:px-6">
				<div class="grid lg:grid-cols-2 gap-16 items-center">
					<div class="bg-white rounded-[3rem] p-8 shadow-soft border border-brand-ink/5 order-2 lg:order-1">
						<canvas id="programChart"></canvas>
					</div>
					<div class="order-1 lg:order-2">
						<span
							class="text-<?php echo esc_attr($colors['main']); ?> font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Prismpath™ Focus', 'chroma-excellence'); ?></span>
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

		<!-- Schedule -->
		<?php if ($schedule_items):
			$schedule_items_array = array_filter(array_map('trim', explode("\n", $schedule_items)));
			if (!empty($schedule_items_array)):
				$steps = array();
				foreach ($schedule_items_array as $item) {
					$parts = explode('|', $item);
					if (count($parts) >= 3) {
						$steps[] = array(
							'time' => trim($parts[0]),
							'title' => trim($parts[1]),
							'copy' => trim($parts[2]),
						);
					}
				}

				if (!empty($steps)):
					$total_steps = count($steps);
					$split_index = ceil($total_steps / 2);
					$top_steps = array_slice($steps, 0, $split_index);
					$bottom_steps = array_slice($steps, $split_index);
					?>
					<section id="schedule" class="py-24 bg-white">
						<div class="max-w-6xl mx-auto px-4 lg:px-6" data-schedule>
							<h2 class="text-3xl font-serif font-bold text-center mb-12 text-brand-ink">
								<?php echo esc_html($schedule_title); ?>
							</h2>

							<div class="tab-content active" data-schedule-panel="program">
								<div class="rounded-[3rem] p-8 md:p-12 bg-brand-cream text-center">

									<!-- Time Bubbles -->
									<div class="relative max-w-5xl mx-auto mb-10">
										<!-- Top Row -->
										<div class="flex flex-wrap justify-center gap-2 md:gap-4 mb-4 relative z-10 max-w-full">
											<?php foreach ($top_steps as $i => $step): ?>
												<?php
												$is_first = 0 === $i;
												$btn_classes = $is_first
													? 'bg-brand-ink text-white shadow-md transform scale-105'
													: 'bg-white text-brand-ink/80 hover:text-brand-ink hover:bg-white/80';
												?>
												<button
													class="w-14 h-14 md:w-16 md:h-16 rounded-full flex items-center justify-center text-xs md:text-sm font-bold transition-all duration-300 <?php echo esc_attr($btn_classes); ?>"
													data-schedule-step-trigger data-title="<?php echo esc_attr($step['title']); ?>"
													data-copy="<?php echo esc_attr($step['copy']); ?>"
													aria-label="<?php echo esc_attr($step['time']); ?>">
													<?php echo esc_html($step['time']); ?>
												</button>
											<?php endforeach; ?>
										</div>

										<!-- Bottom Row -->
										<div class="flex flex-wrap justify-center gap-2 md:gap-4 relative z-10 max-w-full">
											<?php foreach ($bottom_steps as $i => $step): ?>
												<?php
												$btn_classes = 'bg-white text-brand-ink/80 hover:text-brand-ink hover:bg-white/80';
												?>
												<button
													class="w-14 h-14 md:w-16 md:h-16 rounded-full flex items-center justify-center text-xs md:text-sm font-bold transition-all duration-300 <?php echo esc_attr($btn_classes); ?>"
													data-schedule-step-trigger data-title="<?php echo esc_attr($step['title']); ?>"
													data-copy="<?php echo esc_attr($step['copy']); ?>"
													aria-label="<?php echo esc_attr($step['time']); ?>">
													<?php echo esc_html($step['time']); ?>
												</button>
											<?php endforeach; ?>
										</div>
									</div>

									<!-- Dynamic Content -->
									<div class="max-w-2xl mx-auto min-h-[120px]" data-schedule-content>
										<?php if (!empty($steps[0])): ?>
											<h4 class="text-xl font-bold text-brand-ink mb-3 transition-colors duration-300"
												data-content-title>
												<?php echo esc_html($steps[0]['title']); ?>
											</h4>
											<p class="text-brand-ink/90 leading-relaxed transition-opacity duration-300" data-content-copy>
												<?php echo esc_html($steps[0]['copy']); ?>
											</p>
										<?php endif; ?>
									</div>

								</div>
							</div>
						</div>
					</section>
				<?php endif; ?>
			<?php endif; endif; ?>


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

	<script>
		// Prismpath Chart Config - Lazy Loaded
		document.addEventListener('DOMContentLoaded', function () {
            // Setup Schedule Interactions
            const steps = document.querySelectorAll('[data-schedule-step-trigger]');
            const titleEl = document.querySelector('[data-content-title]');
            const copyEl = document.querySelector('[data-content-copy]');
            
            if(steps.length > 0) {
                steps.forEach(btn => {
                    btn.addEventListener('click', function() {
                        // Reset all
                        steps.forEach(b => {
                            b.classList.remove('bg-brand-ink', 'text-white', 'shadow-md', 'transform', 'scale-105');
                            b.classList.add('bg-white', 'text-brand-ink/80');
                        });
                        
                        // Active state
                        this.classList.remove('bg-white', 'text-brand-ink/80');
                        this.classList.add('bg-brand-ink', 'text-white', 'shadow-md', 'transform', 'scale-105');
                        
                        // Update content
                        if(titleEl) titleEl.textContent = this.getAttribute('data-title');
                        if(copyEl) copyEl.textContent = this.getAttribute('data-copy');
                    });
                });
            }

            // Chart.js Handler
			const ctx = document.getElementById('programChart');
			if (ctx) {
				const observer = new IntersectionObserver((entries) => {
					entries.forEach(entry => {
						if (entry.isIntersecting) {
							// Disconnect observer immediately
							observer.disconnect();

							// Dynamically load Chart.js library
							const script = document.createElement('script');
							script.src = '<?php echo esc_url(get_template_directory_uri() . '/assets/js/chart.min.js'); ?>';
							script.async = true;
							script.onload = function () {
								// Initialize Chart after library loads
								new Chart(ctx, {
									type: 'radar',
									data: {
										labels: ['<?php _e('Physical', 'chroma-excellence'); ?>', '<?php _e('Emotional', 'chroma-excellence'); ?>', '<?php _e('Social', 'chroma-excellence'); ?>', '<?php _e('Academic', 'chroma-excellence'); ?>', '<?php _e('Creative', 'chroma-excellence'); ?>'],
										datasets: [{
											label: '<?php echo esc_js(get_the_title()); ?> Focus',
											data: [
												<?php echo absint($prism_physical); ?>,
												<?php echo absint($prism_emotional); ?>,
												<?php echo absint($prism_social); ?>,
												<?php echo absint($prism_academic); ?>,
												<?php echo absint($prism_creative); ?>
											],
											backgroundColor: '<?php
											$chart_colors = array(
												'red' => '#D67D6B',
												'blue' => '#4A6C7C',
												'yellow' => '#E6BE75',
												'blueDark' => '#2F4858',
												'green' => '#8DA399',
												'orange' => '#C26524',
												'teal' => '#4A6C7C',
											);
											$hex_color = $chart_colors[$color_scheme] ?? '#D67D6B';
											echo $hex_color . '33'; // Add 20% opacity
											?>',
											borderColor: '<?php echo $hex_color; ?>',
											pointBackgroundColor: '#fff',
											pointBorderColor: '<?php echo $hex_color; ?>',
											borderWidth: 2
										}]
									},
									options: {
										scales: {
											r: {
												angleLines: { color: '#e5e5e5' },
												grid: { color: '#e5e5e5' },
												pointLabels: { font: { family: 'Outfit', size: 14 }, color: '#263238' },
												suggestedMin: 0,
												suggestedMax: 100,
												ticks: { display: false }
											}
										},
										plugins: { legend: { display: false } }
									}
								});
							};
							document.body.appendChild(script);
						}
					});
				}, { rootMargin: '200px' }); // Start loading 200px before view
				observer.observe(ctx);
			}
		});
	</script>

	<?php
endwhile;
get_footer();

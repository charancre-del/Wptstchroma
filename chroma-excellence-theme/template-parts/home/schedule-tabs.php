<?php
/**
 * Daily Schedule Tabs
 * Template Part: Schedule Tabs
 * "A Day in the Life" - Daily rhythm tabs for different age groups
 *
 * @package Chroma_Excellence
 */

$tracks = chroma_home_schedule_tracks();

if (empty($tracks)) {
	return;
}
?>

<section id="schedule" class="py-20 bg-brand-cream relative" data-section="schedule">
	<div
		class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-chroma-red via-chroma-yellow to-chroma-blue opacity-40">
	</div>
	<div class="max-w-6xl mx-auto px-4 lg:px-6" data-schedule
		data-tracks='<?php echo esc_attr(wp_json_encode($tracks)); ?>'>
		<div class="text-center mb-12">
			<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-4 block"><?php _e('Day by Day', 'chroma-excellence'); ?></span>
			<h2 class="text-3xl md:text-4xl font-serif text-brand-ink mb-3"><?php _e('A Daily Rhythm of Joy', 'chroma-excellence'); ?></h2>
			<p class="text-brand-ink max-w-2xl mx-auto"><?php _e('We don\'t just fill time. Every classroom follows a thoughtful flow designed to balance stimulation, nourishment, and rest.', 'chroma-excellence'); ?></p>
		</div>

		<div class="flex justify-center mb-12">
			<div class="bg-white border border-chroma-blue/15 p-1 rounded-2xl flex flex-wrap gap-2 justify-center"
				data-schedule-tabs>
				<?php foreach ($tracks as $index => $track): ?>
					<?php
					$is_active = 0 === $index;
					$tab_classes = $is_active
						? 'bg-chroma-blue text-white shadow-soft'
						: 'text-gray-900 hover:text-chroma-blue';
					?>
					<button
						class="schedule-tab px-8 py-3 rounded-full text-sm font-bold transition-all duration-300 <?php echo esc_attr($tab_classes); ?>"
						data-schedule-tab="<?php echo esc_attr($track['key']); ?>"
						aria-pressed="<?php echo esc_attr($is_active ? 'true' : 'false'); ?>"><?php echo esc_html($track['label'] ?? ucfirst($track['key'])); ?></button>
				<?php endforeach; ?>
			</div>
		</div>

		<?php foreach ($tracks as $index => $track): ?>
			<?php
			$is_active = 0 === $index;
			$panel_classes = $is_active ? 'tab-content active' : 'tab-content hidden';
			$backgroundTint = !empty($track['background']) ? $track['background'] : 'bg-brand-cream';
			?>
			<div class="<?php echo esc_attr($panel_classes); ?>"
				data-schedule-panel="<?php echo esc_attr($track['key']); ?>">
				<?php
				// Get track-specific colors
				$track_color = !empty($track['color']) ? $track['color'] : 'chroma-blue';
				$badge_bg = 'bg-' . $track_color; // Solid background for active state
				$badge_text = 'text-' . $track_color;
				?>
				<div class="rounded-[3rem] p-8 md:p-12 <?php echo esc_attr($backgroundTint); ?> text-center">

					<!-- Header -->
					<div class="max-w-3xl mx-auto mb-8">
						<h3 class="text-3xl font-serif text-brand-ink mb-4">
							<?php echo esc_html($track['title']); ?>
						</h3>
						<p class="text-brand-ink leading-relaxed">
							<?php echo esc_html($track['description'] ?? ''); ?>
						</p>
					</div>

					<!-- Image -->
					<div class="max-w-xl mx-auto mb-10">
						<div class="rounded-[2rem] overflow-hidden shadow-lg aspect-video bg-white/50">
							<?php if (!empty($track['image'])): ?>
								<img src="<?php echo esc_url($track['image']); ?>"
									alt="<?php echo esc_attr($track['title']); ?>" 
									class="w-full h-full object-cover"
									width="800" height="450"
									loading="lazy"
									decoding="async" />
							<?php else: ?>
								<div class="w-full h-full flex items-center justify-center text-chroma-blueDark/20 text-6xl">
									<i class="fa-solid fa-image"></i>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Time Buttons -->
					<div class="relative max-w-5xl mx-auto mb-10">
						<?php
						$total_steps = count($track['steps']);
						$split_index = ceil($total_steps / 2);
						$top_steps = array_slice($track['steps'], 0, $split_index);
						$bottom_steps = array_slice($track['steps'], $split_index);
						?>

						<!-- Top Row -->
						<div class="flex flex-wrap justify-center gap-2 md:gap-4 mb-4 relative z-10 max-w-full">
							<?php foreach ($top_steps as $i => $step): ?>
								<?php
								$is_first = 0 === $i;
								$btn_classes = $is_first
									? 'bg-brand-ink text-white shadow-md transform scale-105'
									: 'bg-white text-brand-ink hover:text-brand-ink hover:bg-white/80';
								?>
								<button
									class="min-w-[3.5rem] h-14 md:h-16 px-4 rounded-full flex items-center justify-center text-xs md:text-sm font-bold transition-all duration-300 <?php echo esc_attr($btn_classes); ?>"
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
								// Note: No 'is_first' check here as the first item is always in the top row
								$btn_classes = 'bg-white text-brand-ink hover:text-brand-ink hover:bg-white/80';
								?>
								<button
									class="min-w-[3.5rem] h-14 md:h-16 px-4 rounded-full flex items-center justify-center text-xs md:text-sm font-bold transition-all duration-300 <?php echo esc_attr($btn_classes); ?>"
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
						<?php if (!empty($track['steps'][0])): ?>
							<h4 class="text-xl font-bold text-brand-ink mb-3 transition-colors duration-300" data-content-title>
								<?php echo esc_html($track['steps'][0]['title']); ?>
							</h4>
							<p class="text-brand-ink leading-relaxed transition-opacity duration-300" data-content-copy>
								<?php echo esc_html($track['steps'][0]['copy']); ?>
							</p>
						<?php endif; ?>
					</div>

				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
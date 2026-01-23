<?php
/**
 * Locations Archive
 * Displays all locations with search, filtering, and interactive features
 *
 * @package Chroma_Excellence
 */

get_header();

// Get all location regions from taxonomy
$all_regions = get_terms(array(
	'taxonomy' => 'location_region',
	'hide_empty' => true,
));

// Get all published locations
// Get all published locations
$locations_query = chroma_cached_query(
	array(
		'post_type'      => 'location',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'orderby'        => 'title',
		'order'          => 'ASC',
	),
	'locations_archive',
	7 * DAY_IN_SECONDS
);


?>

<main>
	<!-- Hero Section -->
	<section class="relative pt-16 pb-12 lg:pt-24 lg:pb-20 bg-white overflow-hidden">
		<!-- Decor -->
		<div
			class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-chroma-greenLight/40 via-transparent to-transparent">
		</div>

		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 text-center">
			<div
				class="inline-flex items-center gap-2 bg-white border border-chroma-green/30 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-chroma-green shadow-sm mb-6 fade-in-up">
				<i class="fa-solid fa-map-pin"></i> <?php echo $locations_query->found_posts; ?>+ <?php _e('Campuses', 'chroma-excellence'); ?>
			</div>

			<h1 class="font-serif text-[2.8rem] md:text-6xl text-brand-ink mb-6 fade-in-up"
				style="animation-delay: 0.1s;">
				<?php echo wp_kses_post(get_theme_mod('chroma_locations_archive_title', __('Find your <span class="text-chroma-green italic">Chroma Community</span> - Our Locations', 'chroma-excellence'))); ?>
			</h1>

			<p class="text-lg text-brand-ink/90 max-w-2xl mx-auto mb-10 fade-in-up" style="animation-delay: 0.2s;">
				<?php echo has_excerpt() ? get_the_excerpt() : esc_html(get_theme_mod('chroma_locations_archive_subtitle', __('Serving families across Metro Atlanta with the same high standards of safety, curriculum, and care at every single location.', 'chroma-excellence'))); ?>
			</p>

			<!-- Filter Bar -->
			<div class="max-w-7xl mx-auto bg-white p-2 rounded-full shadow-float border border-brand-ink/5 flex flex-col lg:flex-row gap-2 fade-in-up"
				style="animation-delay: 0.3s;">
				<div class="relative flex-grow max-w-md">
					<i class="fa-solid fa-search absolute left-5 top-1/2 -translate-y-1/2 text-brand-ink"></i>
					<input type="text" id="location-search" placeholder="<?php esc_attr_e('Search by ZIP code or city name...', 'chroma-excellence'); ?>"
						class="w-full pl-12 pr-4 py-3 rounded-full focus:outline-none text-brand-ink bg-white" />
				</div>
				<div class="flex gap-2 justify-start lg:justify-end flex-wrap flex-grow items-center">
					<button onclick="filterLocations('all')" data-region="all" data-color-bg="chroma-green"
						data-color-text="white"
						class="filter-btn px-6 py-3 rounded-full font-semibold bg-chroma-green text-white hover:shadow-glow transition-all duration-300 whitespace-nowrap">
						<?php echo esc_html(get_theme_mod('chroma_locations_label', __('All Locations', 'chroma-excellence'))); ?>
					</button>
					<?php foreach ($all_regions as $region):
						$colors = chroma_get_region_color_from_term($region->term_id);
						// Map 'bg' (usually light) to a solid color for the button if needed, 
						// but typically we want the 'text' color (darker) for the button background 
						// and white text for contrast.
						$btn_bg = $colors['text'];
						?>
						<button onclick="filterLocations('<?php echo esc_attr($region->slug); ?>')"
							data-region="<?php echo esc_attr($region->slug); ?>"
							data-color-bg="<?php echo esc_attr($btn_bg); ?>" data-color-text="white"
							class="filter-btn px-6 py-3 rounded-full font-semibold bg-white text-brand-ink border border-brand-ink/10 hover:bg-brand-ink/5 transition-all duration-300 whitespace-nowrap">
							<?php echo esc_html($region->name); ?>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- Locations Container -->
	<section class="py-16 lg:py-20 bg-white">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8" id="locations-grid">
				<?php
				if ($locations_query->have_posts()):
					while ($locations_query->have_posts()):
						$locations_query->the_post();
						$location_id = get_the_ID();
						$location_fields = chroma_get_location_fields($location_id);
						$location_name = get_the_title();

						// Get location meta
						$city = $location_fields['city'];
						$state = $location_fields['state'];
						$zip = $location_fields['zip'];
						$address = chroma_location_address_line($location_id);
						$phone = $location_fields['phone'];
						$lat = $location_fields['latitude'];
						$lng = $location_fields['longitude'];

						// Get region from taxonomy
						$location_regions = wp_get_post_terms($location_id, 'location_region');
						$region_term = !empty($location_regions) && !is_wp_error($location_regions) ? $location_regions[0] : null;

						// Get region name and slug for display and filtering
						$region_name = $region_term ? $region_term->name : __('Metro Atlanta', 'chroma-excellence');
						$region_slug = $region_term ? $region_term->slug : 'uncategorized';

						// Get colors for this region from term meta
						$colors = $region_term
							? chroma_get_region_color_from_term($region_term->term_id)
							: array('bg' => 'chroma-greenLight', 'text' => 'chroma-green', 'border' => 'chroma-green');

						// Check for special badges
						$is_featured = get_post_meta($location_id, 'location_featured', true);
						$is_new = get_post_meta($location_id, 'location_new', true);
						$is_enrolling = get_post_meta($location_id, 'location_enrolling', true);

						// Dynamic Open Status
						$hours_string = chroma_get_translated_meta($location_id, 'location_hours');
						$is_open = chroma_is_location_open($hours_string);

						// Badge Text Logic
						$hero_subtitle = chroma_get_translated_meta($location_id, 'location_hero_subtitle');
						if (!empty($hero_subtitle)) {
							$badge_text = $hero_subtitle;
						} elseif ($is_new) {
							$badge_text = __('New Campus', 'chroma-excellence');
						} else {
							$badge_text = get_theme_mod('chroma_locations_badge_fallback', __('Now Enrolling', 'chroma-excellence'));
						}

						// Get age ranges/programs
						$ages_served = chroma_get_translated_meta($location_id, 'location_ages_served') ?: __('Infant - 12y', 'chroma-excellence');
						$special_programs_raw = chroma_get_translated_meta($location_id, 'location_special_programs');

						if ($special_programs_raw) {
							// Explode comma-separated string
							$special_programs = array_map('trim', explode(',', $special_programs_raw));
						} else {
							$special_programs = array( __('GA Pre-K', 'chroma-excellence') ); // Default fallback
						}
						?>

						<div class="location-card group" data-region="<?php echo esc_attr($region_slug); ?>"
							data-name="<?php echo esc_attr($location_name . ' ' . $city . ' ' . $zip); ?>">
							<div
								class="bg-white rounded-[2rem] p-6 shadow-card border border-<?php echo esc_attr($is_featured ? $colors['border'] . ' border-opacity-50' : 'brand-ink/5'); ?> hover:border-<?php echo esc_attr($colors['border']); ?>/30 transition-all hover:-translate-y-1 h-full flex flex-col relative overflow-hidden">
										
								<!-- Overlay Link for entire card -->
								<a href="<?php the_permalink(); ?>" class="absolute inset-0 z-10" aria-label="<?php printf( esc_attr__('View %s', 'chroma-excellence'), esc_attr($location_name) ); ?>"></a>

								<div
									class="absolute top-0 right-0 bg-<?php echo esc_attr($is_new ? $colors['text'] : $colors['border']); ?> text-<?php echo esc_attr($is_new ? 'brand-ink' : 'white'); ?> text-[10px] font-bold uppercase px-4 py-1 rounded-bl-xl tracking-wider">
									<?php echo esc_html($badge_text); ?>
								</div>

								<div class="flex justify-between items-start mb-4 mt-2">
									<span
										class="bg-<?php echo esc_attr($colors['bg']); ?> text-<?php echo esc_attr($colors['text']); ?> px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide">
										<?php echo esc_html($region_name); ?>
									</span>
									<?php if ($is_open): ?>
										<div class="flex items-center gap-1.5" title="<?php esc_attr_e('Open Now', 'chroma-excellence'); ?>">
											<div class="w-2 h-2 rounded-full bg-chroma-green animate-pulse"></div>
											<span
												class="text-[10px] font-bold text-chroma-green uppercase tracking-wide"><?php echo esc_html(get_theme_mod('chroma_locations_open_text', __('Open Now', 'chroma-excellence'))); ?></span>
										</div>
									<?php endif; ?>
								</div>

								<h2
									class="font-serif text-2xl font-bold text-brand-ink mb-2 group-hover:text-<?php echo esc_attr($colors['text']); ?> transition-colors">
									<?php echo esc_html($location_name); ?>
								</h2>

								<p class="text-sm text-brand-ink/90 mb-4 flex-grow">
									<?php echo esc_html($address); ?><br>
									<?php echo esc_html("$city, $state $zip"); ?>
								</p>

								<div
									class="flex flex-wrap gap-2 mb-6 text-[10px] font-bold uppercase tracking-wider text-brand-ink">
									<span
										class="border border-brand-ink/10 px-2 py-1 rounded-md"><?php echo esc_html($ages_served); ?></span>
									<?php foreach (array_slice($special_programs, 0, 2) as $program): ?>
										<span
											class="border border-brand-ink/10 px-2 py-1 rounded-md"><?php echo esc_html($program); ?></span>
									<?php endforeach; ?>
								</div>

								<?php
							$booking_link = chroma_get_translated_meta($location_id, 'location_tour_booking_link');
							?>
							<div class="grid grid-cols-2 gap-3 mt-auto relative z-20">
								<a href="<?php the_permalink(); ?>"
									class="flex items-center justify-center py-3 rounded-xl bg-brand-ink/5 text-brand-ink text-xs font-bold uppercase tracking-wider hover:bg-brand-ink hover:text-white transition-colors">
									<?php _e('View Campus', 'chroma-excellence'); ?>
								</a>
								<?php if ($booking_link): ?>
									<a href="<?php echo esc_url($booking_link); ?>"
										class="booking-btn flex items-center justify-center py-3 rounded-xl border border-<?php echo esc_attr($colors['border']); ?> text-<?php echo esc_attr($colors['text']); ?> text-xs font-bold uppercase tracking-wider hover:bg-<?php echo esc_attr($colors['text']); ?> hover:text-white transition-colors">
										<?php _e('Book Tour', 'chroma-excellence'); ?>
									</a>
								<?php else: ?>
									<a href="<?php the_permalink(); ?>#contact"
										class="flex items-center justify-center py-3 rounded-xl border border-<?php echo esc_attr($colors['border']); ?> text-<?php echo esc_attr($colors['text']); ?> text-xs font-bold uppercase tracking-wider hover:bg-<?php echo esc_attr($colors['text']); ?> hover:text-white transition-colors">
										<?php _e('Contact Us', 'chroma-excellence'); ?>
									</a>
								<?php endif; ?>
							</div>
						</div>
					</div>

					<?php endwhile;
					wp_reset_postdata();
				endif;
				?>
			</div>
		</div>
	</section>

	<!-- Map & CTA Section -->
	<section class="bg-white py-20 border-t border-brand-ink/5">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div
				class="bg-chroma-blueDark rounded-[3rem] p-10 lg:p-16 text-white relative overflow-hidden flex flex-col lg:flex-row gap-12 items-center">

				<!-- Map Placeholder -->
				<div class="w-full lg:w-1/2 relative z-10">
					<div
						class="bg-white/10 rounded-[2rem] p-2 aspect-video border border-white/20 flex items-center justify-center relative overflow-hidden">
						<!-- Abstract map representation -->
						<div class="relative z-10 flex flex-wrap justify-center gap-4 p-6">
							<div class="bg-chroma-red w-4 h-4 rounded-full animate-bounce" style="animation-delay: 0s;">
							</div>
							<div class="bg-chroma-yellow w-4 h-4 rounded-full animate-bounce"
								style="animation-delay: 0.2s;"></div>
							<div class="bg-chroma-green w-4 h-4 rounded-full animate-bounce"
								style="animation-delay: 0.4s;"></div>
							<div class="bg-chroma-blue w-4 h-4 rounded-full animate-bounce"
								style="animation-delay: 0.1s;"></div>
							<div class="bg-chroma-red w-4 h-4 rounded-full animate-bounce"
								style="animation-delay: 0.3s;"></div>
							<div class="bg-chroma-green w-4 h-4 rounded-full animate-bounce"
								style="animation-delay: 0.5s;"></div>
						</div>
						<p class="absolute bottom-4 text-xs font-bold tracking-widest uppercase text-white/90">
							<?php printf( esc_html__('%s+ Locations in Metro Atlanta', 'chroma-excellence'), $locations_query->found_posts ); ?>
						</p>
					</div>
				</div>

				<!-- CTA Content -->
				<div class="w-full lg:w-1/2 relative z-10">
					<h2 class="font-serif text-3xl md:text-5xl font-bold mb-6"><?php _e('Not sure which campus is right for you?', 'chroma-excellence'); ?>
					</h2>
					<p class="text-white/90 text-lg mb-8"><?php _e('Our enrollment specialists can help you find the nearest location with openings for your child\'s age group.', 'chroma-excellence'); ?></p>
					<div class="flex flex-wrap gap-4">
						<a href="<?php echo esc_url(home_url('/contact')); ?>"
							class="px-8 py-4 bg-chroma-yellow text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:bg-white transition-colors">
							<?php _e('Contact Support', 'chroma-excellence'); ?>
						</a>
						<a href="<?php echo esc_url(home_url()); ?>"
							class="px-8 py-4 border border-white/20 text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:bg-white/10 transition-colors">
							<?php _e('Back to Home', 'chroma-excellence'); ?>
						</a>
					</div>
				</div>

				<!-- Decor -->
				<div class="absolute -right-20 -bottom-40 w-96 h-96 bg-chroma-blue rounded-full blur-3xl opacity-50">
				</div>
			</div>
		</div>
	</section>
</main>

<!-- Filter Logic -->
<script>
	function filterLocations(region) {
		const cards = document.querySelectorAll('.location-card');
		const buttons = document.querySelectorAll('.filter-btn');
		const searchInput = document.getElementById('location-search');
		const noResults = document.getElementById('no-results');
		let visibleCount = 0;

		// Reset search input visual if filtering by button
		if (region) searchInput.value = '';

		// Update button styles
		buttons.forEach(btn => {
			const activeBg = btn.dataset.colorBg || 'chroma-green';
			const activeText = btn.dataset.colorText || 'white';

			if (region === btn.dataset.region) {
				// Active State: Use dynamic color
				btn.classList.remove('bg-white', 'text-brand-ink', 'border', 'border-brand-ink/10');
				btn.classList.add('bg-' + activeBg, 'text-' + activeText, 'shadow-glow');

				// If using a Tailwind class that needs compilation, ensure it's safelisted.
				// For direct style manipulation (if classes aren't working):
				// btn.style.backgroundColor = ''; // Rely on class
			} else {
				// Inactive State
				btn.classList.add('bg-white', 'text-brand-ink', 'border', 'border-brand-ink/10');
				btn.classList.remove('bg-' + activeBg, 'text-' + activeText, 'shadow-glow');

				// Clean up any potential leftover dynamic classes from previous active states
				// (This simple removal might miss if we switch from one color to another, 
				// but since we re-add the white/ink classes, it should override or cascade correctly 
				// IF the dynamic classes are removed. To be safe, we should remove ALL potential dynamic classes.
				// However, since we don't know them all, we rely on the 'bg-white' overriding or 
				// simply removing the specific one we added.)

				// Better approach: Remove ALL bg-* classes that aren't bg-white? 
				// No, that's too aggressive.
				// We just remove the specific one this button WOULD have if it were active.
				btn.classList.remove('bg-' + activeBg, 'text-' + activeText);
			}
		});

		cards.forEach(card => {
			if (region === 'all' || card.dataset.region.includes(region)) {
				card.style.display = 'block';
				card.classList.add('fade-in-up');
				visibleCount++;
			} else {
				card.style.display = 'none';
			}
		});

		if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
	}

	// Search Filter Logic
	document.getElementById('location-search').addEventListener('keyup', function (e) {
		const term = e.target.value.toLowerCase();
		const cards = document.querySelectorAll('.location-card');
		const buttons = document.querySelectorAll('.filter-btn');
		const noResults = document.getElementById('no-results');
		let visibleCount = 0;

		// Reset buttons to inactive state
		buttons.forEach(btn => {
			const activeBg = btn.dataset.colorBg || 'chroma-green';
			const activeText = btn.dataset.colorText || 'white';

			btn.classList.add('bg-white', 'text-brand-ink', 'border', 'border-brand-ink/10');
			btn.classList.remove('bg-' + activeBg, 'text-' + activeText, 'shadow-glow');
		});

		cards.forEach(card => {
			const text = card.dataset.name.toLowerCase();
			if (text.includes(term)) {
				card.style.display = 'block';
				visibleCount++;
			} else {
				card.style.display = 'none';
			}
		});


	if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
	});
</script>

</script>

<?php
get_footer();

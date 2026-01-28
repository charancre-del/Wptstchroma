<?php
/**
 * Template Name: Locations
 * Displays all locations with search, filtering, and interactive features
 *
 * @package Chroma_Excellence
 */

get_header();

// Get Data Service
$data_service = Chroma_Data_Service::get_instance();

// Get all location regions from memory
$all_regions = $data_service->get_regions();

// Get all locations from memory
$locations = $data_service->get_locations();
$locations_count = count($locations);

// Helper function to get region color from memory
function chroma_get_region_color_mem($term_id)
{
	$data_service = Chroma_Data_Service::get_instance();
	$color_bg = $data_service->get_term_meta($term_id, 'region_color_bg');
	$color_text = $data_service->get_term_meta($term_id, 'region_color_text');
	$color_border = $data_service->get_term_meta($term_id, 'region_color_border');

	// Fallback to default green if no colors set
	return array(
		'bg' => $color_bg ?: 'chroma-greenLight',
		'text' => $color_text ?: 'chroma-green',
		'border' => $color_border ?: 'chroma-green',
	);
}
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
				<i class="fa-solid fa-map-pin"></i> <?php echo esc_html($locations_count); ?>+ Campuses
			</div>

			<h1 class="font-serif text-[2.8rem] md:text-6xl text-brand-ink mb-6 fade-in-up"
				style="animation-delay: 0.1s;">
				<?php echo wp_kses_post(get_theme_mod('chroma_locations_archive_title', 'Find your <span class="text-chroma-green italic">Chroma Community.</span>')); ?>
			</h1>

			<p class="text-lg text-brand-ink/80 max-w-2xl mx-auto mb-10 fade-in-up" style="animation-delay: 0.2s;">
				<?php echo has_excerpt() ? get_the_excerpt() : esc_html(get_theme_mod('chroma_locations_archive_subtitle', 'Serving families across Metro Atlanta with the same high standards of safety, curriculum, and care at every single location.')); ?>
			</p>

			<!-- Filter Bar -->
			<div class="max-w-4xl mx-auto bg-white p-2 rounded-full shadow-float border border-brand-ink/5 flex flex-col md:flex-row gap-2 fade-in-up"
				style="animation-delay: 0.3s;">
				<div class="relative flex-grow">
					<i class="fa-solid fa-search absolute left-5 top-1/2 -translate-y-1/2 text-brand-ink/30"></i>
					<input type="text" id="location-search" placeholder="Search by city, zip, or campus name..."
						class="w-full pl-12 pr-6 py-4 rounded-full bg-brand-cream/50 focus:bg-white focus:ring-2 ring-chroma-green/20 outline-none text-brand-ink placeholder:text-brand-ink/40 transition-all" />
				</div>
				<div class="flex gap-2 overflow-x-auto pb-2 md:pb-0 px-2 md:px-0 no-scrollbar" id="region-filters">
					<button onclick="filterLocations('all')"
						class="filter-btn active whitespace-nowrap px-6 py-4 rounded-full text-xs font-bold uppercase tracking-wider bg-brand-ink text-white shadow-md transition-all">
						<?php echo esc_html(get_theme_mod('chroma_locations_label', 'All Locations')); ?>
					</button>
					<?php if (!empty($all_regions)): ?>
						<?php foreach ($all_regions as $region_term):
							$colors = chroma_get_region_color_mem($region_term->term_id);
							?>
							<button onclick="filterLocations('<?php echo esc_attr($region_term->slug); ?>')"
								class="filter-btn whitespace-nowrap px-6 py-4 rounded-full text-xs font-bold uppercase tracking-wider bg-white text-brand-ink/80 hover:bg-<?php echo esc_attr($colors['bg']); ?> hover:text-<?php echo esc_attr($colors['text']); ?> border border-transparent hover:border-<?php echo esc_attr($colors['border']); ?>/20 transition-all">
								<?php echo esc_html($region_term->name); ?>
							</button>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- Locations Grid -->
	<section id="all-locations" class="py-20 bg-brand-cream min-h-screen">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">

			<!-- Empty State -->
			<div id="no-results" class="hidden text-center py-20">
				<div
					class="w-16 h-16 bg-brand-ink/5 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
					🤔</div>
				<h3 class="font-serif text-xl font-bold text-brand-ink">No locations found</h3>
				<p class="text-brand-ink/80 mt-2">Try adjusting your search terms or selecting
					"<?php echo esc_html(get_theme_mod('chroma_locations_label', 'All Locations')); ?>".</p>
				<button onclick="filterLocations('all')"
					class="mt-6 text-chroma-blue font-bold text-sm underline decoration-2 underline-offset-4">
					View
					<?php echo esc_html(strtolower(get_theme_mod('chroma_locations_label', 'All Locations'))); ?>
				</button>
			</div>

			<!-- Locations Container -->
			<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8" id="locations-grid">
				<?php
				if (!empty($locations)):
					foreach ($locations as $location):
						$location_id = $location->ID;
						$location_name = $location->post_title;

						// Get location meta fields from memory
						$city = $data_service->get_meta($location_id, 'location_city');
						$state = $data_service->get_meta($location_id, 'location_state', 'GA');
						$zip = $data_service->get_meta($location_id, 'location_zip');
						$address = $data_service->get_meta($location_id, 'location_address');
						$phone = $data_service->get_meta($location_id, 'location_phone');
						$lat = $data_service->get_meta($location_id, 'location_latitude');
						$lng = $data_service->get_meta($location_id, 'location_longitude');

						// Handle Region (Still taxonomy, but we could cache this too in the pre-warm)
						$location_regions = wp_get_post_terms($location_id, 'location_region');
						$region_term = !empty($location_regions) && !is_wp_error($location_regions) ? $location_regions[0] : null;

						$region_name = $region_term ? $region_term->name : 'Metro Atlanta';
						$region_slug = $region_term ? $region_term->slug : 'uncategorized';

						// Get colors for this region from memory
						$colors = $region_term
							? chroma_get_region_color_mem($region_term->term_id)
							: array('bg' => 'chroma-greenLight', 'text' => 'chroma-green', 'border' => 'chroma-green');

						// Check for special badges from memory
						$is_featured = $data_service->get_meta($location_id, 'location_featured');
						$is_new = $data_service->get_meta($location_id, 'location_new');
						$is_enrolling = $data_service->get_meta($location_id, 'location_enrolling');
						$is_open = true;

						$ages_served = $data_service->get_meta($location_id, 'location_ages_served') ?: 'Infant - 12y';
						$special_programs_raw = $data_service->get_meta($location_id, 'location_special_programs');

						if ($special_programs_raw) {
							$special_programs = array_map('trim', explode(',', $special_programs_raw));
						} else {
							$special_programs = array('GA Pre-K');
						}
						?>

						<div class="location-card group" data-region="<?php echo esc_attr($region_slug); ?>"
							data-name="<?php echo esc_attr($location_name . ' ' . $city . ' ' . $zip); ?>">
							<div
								class="bg-white rounded-[2rem] p-6 shadow-card border border-<?php echo esc_attr($is_featured ? $colors['border'] . ' border-opacity-50' : 'brand-ink/5'); ?> hover:border-<?php echo esc_attr($colors['border']); ?>/30 transition-all hover:-translate-y-1 h-full flex flex-col relative overflow-hidden">

								<?php if ($is_new || $is_enrolling): ?>
									<div
										class="absolute top-0 right-0 bg-<?php echo esc_attr($is_new ? $colors['text'] : $colors['border']); ?> text-<?php echo esc_attr($is_new ? 'brand-ink' : 'white'); ?> text-[10px] font-bold uppercase px-4 py-1 rounded-bl-xl tracking-wider">
										<?php echo $is_new ? 'New Campus' : 'Now Enrolling'; ?>
									</div>
								<?php endif; ?>

								<div
									class="flex justify-between items-start mb-4 <?php echo ($is_new || $is_enrolling) ? 'mt-2' : ''; ?>">
									<span
										class="bg-<?php echo esc_attr($colors['bg']); ?> text-<?php echo esc_attr($colors['text']); ?> px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide">
										<?php echo esc_html($region_name); ?>
									</span>
									<?php if ($is_open): ?>
										<div class="w-2 h-2 rounded-full bg-chroma-green animate-pulse" title="Open Now"></div>
									<?php endif; ?>
								</div>

								<h3
									class="font-serif text-2xl font-bold text-brand-ink mb-2 group-hover:text-<?php echo esc_attr($colors['text']); ?> transition-colors">
									<?php echo esc_html($location_name); ?>
								</h3>

								<p class="text-sm text-brand-ink/80 mb-4 flex-grow">
									<?php echo esc_html($address); ?><br>
									<?php echo esc_html("$city, $state $zip"); ?>
								</p>

								<div
									class="flex flex-wrap gap-2 mb-6 text-[10px] font-bold uppercase tracking-wider text-brand-ink/40">
									<span
										class="border border-brand-ink/10 px-2 py-1 rounded-md"><?php echo esc_html($ages_served); ?></span>
									<?php foreach (array_slice($special_programs, 0, 2) as $program): ?>
										<span
											class="border border-brand-ink/10 px-2 py-1 rounded-md"><?php echo esc_html($program); ?></span>
									<?php endforeach; ?>
								</div>

								<div class="grid grid-cols-2 gap-3 mt-auto">
									<a href="<?php the_permalink(); ?>"
										class="flex items-center justify-center py-3 rounded-xl bg-brand-ink/5 text-brand-ink text-xs font-bold uppercase tracking-wider hover:bg-brand-ink hover:text-white transition-colors">
										View Campus
									</a>
									<a href="<?php the_permalink(); ?>#tour"
										class="flex items-center justify-center py-3 rounded-xl border border-<?php echo esc_attr($colors['border']); ?> text-<?php echo esc_attr($colors['text']); ?> text-xs font-bold uppercase tracking-wider hover:bg-<?php echo esc_attr($colors['text']); ?> hover:text-white transition-colors">
										Book Tour
									</a>
								</div>
							</div>
						</div>

					<?php endforeach;
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
						<p class="absolute bottom-4 text-xs font-bold tracking-widest uppercase text-white/60">
							<?php echo (int) $locations_count; ?>+ Locations in Metro Atlanta
						</p>
					</div>
				</div>

				<!-- CTA Content -->
				<div class="w-full lg:w-1/2 relative z-10">
					<h2 class="font-serif text-3xl md:text-5xl font-bold mb-6">Not sure which campus is right for you?
					</h2>
					<p class="text-white/70 text-lg mb-8">Our enrollment specialists can help you find the nearest
						location with openings for your child's age group.</p>
					<div class="flex flex-wrap gap-4">
						<a href="<?php echo esc_url(home_url('/contact')); ?>"
							class="px-8 py-4 bg-chroma-yellow text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:bg-white transition-colors">
							Contact Support
						</a>
						<a href="<?php echo esc_url(home_url()); ?>"
							class="px-8 py-4 border border-white/20 text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:bg-white/10 transition-colors">
							Back to Home
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
			if ((region === 'all' && btn.textContent.includes('All')) || btn.textContent.includes(region)) {
				btn.classList.remove('bg-white', 'text-brand-ink/80');
				btn.classList.add('bg-brand-ink', 'text-white', 'shadow-md');
			} else {
				btn.classList.add('bg-white', 'text-brand-ink/80');
				btn.classList.remove('bg-brand-ink', 'text-white', 'shadow-md');
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

		noResults.style.display = visibleCount === 0 ? 'block' : 'none';
	}

	// Search Filter Logic
	document.getElementById('location-search').addEventListener('keyup', function (e) {
		const term = e.target.value.toLowerCase();
		const cards = document.querySelectorAll('.location-card');
		const buttons = document.querySelectorAll('.filter-btn');
		const noResults = document.getElementById('no-results');
		let visibleCount = 0;

		// Reset buttons
		buttons.forEach(btn => {
			btn.classList.add('bg-white', 'text-brand-ink/80');
			btn.classList.remove('bg-brand-ink', 'text-white', 'shadow-md');
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

		noResults.style.display = visibleCount === 0 ? 'block' : 'none';
	});
</script>

<?php
get_footer();

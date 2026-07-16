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
$archive_page_id = get_queried_object_id();
$archive_subtitle = ($archive_page_id && has_excerpt($archive_page_id))
	? get_the_excerpt($archive_page_id)
	: get_theme_mod('chroma_locations_archive_subtitle', 'Serving families across Metro Atlanta with the same high standards of safety, curriculum, and care at every single location.');

// Get all location regions from memory
$all_regions = $data_service->get_regions();

// Get all locations from memory
$locations = $data_service->get_locations();
$published_locations = wp_count_posts('location');
$locations_count = isset($published_locations->publish) ? (int) $published_locations->publish : 0;

// Guard against stale cached empty arrays from object/transient cache.
if (empty($locations) && $locations_count > 0) {
	$locations = get_posts(
		array(
			'post_type' => 'location',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'orderby' => 'title',
			'order' => 'ASC',
			'no_found_rows' => true,
			'update_post_meta_cache' => true,
		)
	);
}

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
	<h1 class="sr-only">
		<?php echo wp_kses_post(get_theme_mod('chroma_locations_archive_title', 'A Chroma near you.')); ?>
	</h1>

	<section class="chroma-location-compact-intro bg-white pt-16 md:pt-20 pb-4">
		<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
			<p class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3">
				<?php esc_html_e('Neighborhood care, one Chroma standard', 'chroma-excellence'); ?>
			</p>
			<h2 class="font-serif text-4xl md:text-6xl font-semibold tracking-[-0.04em] leading-[0.98] text-brand-ink mb-5">
				<?php esc_html_e('Local roots. One standard.', 'chroma-excellence'); ?>
			</h2>
			<p class="text-brand-ink/70 text-base md:text-lg leading-relaxed max-w-3xl mx-auto">
				<?php esc_html_e('Find a Chroma campus close to home or work. Each location is neighborhood-specific and connected by the same safety, curriculum, and care standards.', 'chroma-excellence'); ?>
			</p>
		</div>
	</section>

	<?php get_template_part('template-parts/home/locations-preview', null, array('hide_heading' => true, 'stacked' => true)); ?>

	<?php if (false): ?>
	<section class="chroma-location-promise-section cream py-20 bg-brand-cream">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="max-w-3xl mx-auto text-center mb-12">
				<p class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3">
					<?php esc_html_e('Every Campus, The Same Promise', 'chroma-excellence'); ?>
				</p>
				<h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] text-brand-ink mb-5">
					<?php esc_html_e('Local roots. One standard.', 'chroma-excellence'); ?>
				</h2>
				<p class="text-brand-ink/75 text-lg leading-relaxed">
					<?php esc_html_e('Each Chroma campus is independently warm and neighborhood-specific — and all of them share the same licensing, curriculum, and care.', 'chroma-excellence'); ?>
				</p>
			</div>

			<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
				<div class="rounded-[2rem] bg-white border border-chroma-blue/10 shadow-card p-6">
					<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php esc_html_e('Quality Rated', 'chroma-excellence'); ?></h3>
					<p class="text-sm leading-relaxed text-brand-ink/75"><?php esc_html_e('Campuses participate in Georgia DECAL quality standards and continuous improvement practices.', 'chroma-excellence'); ?></p>
				</div>
				<div class="rounded-[2rem] bg-white border border-chroma-blue/10 shadow-card p-6">
					<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php esc_html_e('GA Pre-K', 'chroma-excellence'); ?></h3>
					<p class="text-sm leading-relaxed text-brand-ink/75"><?php esc_html_e('Many Chroma locations offer Georgia Pre-K classrooms for kindergarten readiness.', 'chroma-excellence'); ?></p>
				</div>
				<div class="rounded-[2rem] bg-white border border-chroma-blue/10 shadow-card p-6">
					<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php esc_html_e('Prismpath™', 'chroma-excellence'); ?></h3>
					<p class="text-sm leading-relaxed text-brand-ink/75"><?php esc_html_e('Our five-pillar curriculum balances physical, emotional, social, academic, and creative growth.', 'chroma-excellence'); ?></p>
				</div>
				<div class="rounded-[2rem] bg-white border border-chroma-blue/10 shadow-card p-6">
					<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php esc_html_e('Family-Friendly Schedules', 'chroma-excellence'); ?></h3>
					<p class="text-sm leading-relaxed text-brand-ink/75"><?php esc_html_e('Operating hours vary by campus. View a campus page or contact its team for the current schedule.', 'chroma-excellence'); ?></p>
				</div>
			</div>
		</div>
	</section>

	<?php endif; ?>

	<section class="white borderY py-20 bg-white border-y border-chroma-blue/10">
		<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
			<h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] text-brand-ink mb-6">
				<?php esc_html_e('Found one nearby?', 'chroma-excellence'); ?>
			</h2>
			<div class="flex flex-wrap justify-center gap-4">
				<a href="<?php echo esc_url(home_url('/schedule-a-tour/')); ?>"
					class="inline-flex items-center justify-center px-7 py-4 rounded-full bg-brand-ink text-white text-xs font-bold uppercase tracking-[0.18em] hover:bg-chroma-blueDark transition shadow-soft">
					<?php esc_html_e('Schedule a Tour', 'chroma-excellence'); ?>
				</a>
				<a href="<?php echo esc_url(home_url('/contact-us/')); ?>"
					class="inline-flex items-center justify-center px-7 py-4 rounded-full border border-chroma-blue/20 bg-white text-brand-ink text-xs font-bold uppercase tracking-[0.18em] hover:border-chroma-blue hover:text-chroma-blue transition">
					<?php esc_html_e('Contact Us', 'chroma-excellence'); ?>
				</a>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();

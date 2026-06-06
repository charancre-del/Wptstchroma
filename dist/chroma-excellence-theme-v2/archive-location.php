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
		'post_type' => 'location',
		'posts_per_page' => 100, // P0: Cap archive query
		'post_status' => 'publish',
		'orderby' => 'title',
		'order' => 'ASC',
		'no_found_rows' => true,
		'update_post_meta_cache' => true, // P1: Prefetch meta
	),
	'locations_archive_v2',
	7 * DAY_IN_SECONDS
);
$published_locations = wp_count_posts('location');
$locations_count = isset($published_locations->publish) ? (int) $published_locations->publish : 0;
$archive_language = function_exists('chroma_seo_get_request_language') ? chroma_seo_get_request_language() : 'en';
$archive_title_default = $archive_language === 'es'
	? 'Un Chroma <span class="text-chroma-green italic">cerca de ti.</span>'
	: 'A Chroma <span class="text-chroma-green italic">near you.</span>';
$archive_subtitle_default = $archive_language === 'es'
	? 'Apoyamos a familias en Metro Atlanta con los mismos altos estándares de seguridad, currículo y cuidado en cada campus.'
	: 'Serving families across Metro Atlanta with the same high standards of safety, curriculum, and care at every single location.';
$archive_title = $archive_language === 'es'
	? get_theme_mod('chroma_locations_archive_title_es', __($archive_title_default, 'chroma-excellence'))
	: get_theme_mod('chroma_locations_archive_title', __($archive_title_default, 'chroma-excellence'));
$archive_description = trim(wp_strip_all_tags(get_the_archive_description()));
$archive_subtitle = $archive_description !== ''
	? $archive_description
	: ($archive_language === 'es'
		? get_theme_mod('chroma_locations_archive_subtitle_es', __($archive_subtitle_default, 'chroma-excellence'))
		: get_theme_mod('chroma_locations_archive_subtitle', __($archive_subtitle_default, 'chroma-excellence')));

// Guard against stale cached empty query objects.
if (0 === (int) $locations_query->post_count && $locations_count > 0) {
	$locations_query = new WP_Query(
		array(
			'post_type' => 'location',
			'posts_per_page' => 100,
			'post_status' => 'publish',
			'orderby' => 'title',
			'order' => 'ASC',
			'no_found_rows' => true,
			'update_post_meta_cache' => true,
		)
	);
}


?>

<main>
	<!-- Hero Section -->
		<section class="pageHero chroma-v2-page-hero relative pt-16 pb-12 lg:pt-24 lg:pb-20 bg-brand-cream overflow-hidden">
		<!-- Decor -->
		<div
			class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-chroma-greenLight/40 via-transparent to-transparent">
		</div>

		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 text-center">
			<h1 class="font-serif text-[2.8rem] md:text-6xl text-brand-ink mb-6 fade-in-up"
				style="animation-delay: 0.1s;">
				<?php echo wp_kses_post($archive_title); ?>
			</h1>

			<p class="text-lg text-brand-ink/90 max-w-2xl mx-auto mb-10 fade-in-up" style="animation-delay: 0.2s;">
				<?php echo esc_html($archive_subtitle); ?>
			</p>

		</div>
	</section>

	<?php get_template_part('template-parts/home/locations-preview', null, array('hide_heading' => true)); ?>

	<section class="chroma-location-promise-section cream py-20 bg-brand-cream">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="max-w-3xl mx-auto text-center mb-12">
				<p class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3">
					<?php esc_html_e('Campus Promise', 'chroma-excellence'); ?>
				</p>
				<h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] text-brand-ink mb-5">
					<?php esc_html_e('Local roots. One standard.', 'chroma-excellence'); ?>
				</h2>
				<p class="text-brand-ink/75 text-lg leading-relaxed">
					<?php esc_html_e('Every Chroma campus brings the same accredited care model, family communication rhythm, and Prismpath™ curriculum into the neighborhood it serves.', 'chroma-excellence'); ?>
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
					<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php esc_html_e('6:30–6:30', 'chroma-excellence'); ?></h3>
					<p class="text-sm leading-relaxed text-brand-ink/75"><?php esc_html_e('Extended weekday hours support working families across Metro Atlanta.', 'chroma-excellence'); ?></p>
				</div>
			</div>
		</div>
	</section>


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

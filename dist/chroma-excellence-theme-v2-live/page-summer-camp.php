<?php
/**
 * Template Name: Summer Camp Landing Page
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
	exit;
}

$camp_year = (int) current_time('Y');
$schedule_tour_url = home_url('/schedule-tour/');
$camp_language = function_exists('chroma_seo_get_request_language') ? chroma_seo_get_request_language() : 'en';
$camp_hero_title = $camp_language === 'es'
	? 'Un verano de <span class="text-chroma-yellow italic">descubrimiento.</span>'
	: 'A Summer of <span class="text-chroma-yellow italic">Discovery.</span>';
$camp_hero_description = $camp_language === 'es'
	? 'Cuando no hay clases, comienza la aventura. Explora temas semanales, excursiones, proyectos STEM y dias de agua en nuestros campus de Metro Atlanta para edades de 5 a 12 anos.'
	: 'When school is out, the adventure begins. Explore weekly themes, field trips, STEM projects, and splash-day fun across our Metro Atlanta campuses for ages 5 to 12.';
$camp_page_id = get_queried_object_id();
$camp_hero_image_url = $camp_page_id ? get_the_post_thumbnail_url($camp_page_id, 'hero-large') : '';
$camp_hero_image_alt = $camp_page_id ? get_the_title($camp_page_id) : __('Children enjoying Chroma summer camp activities', 'chroma-excellence');

if (!$camp_hero_image_url) {
	$camp_hero_image_url = (string) get_theme_mod('chroma_home_hero_image', '');
}

if (!$camp_hero_image_url) {
	$front_page_id = (int) get_option('page_on_front');
	if ($front_page_id && has_post_thumbnail($front_page_id)) {
		$camp_hero_image_url = get_the_post_thumbnail_url($front_page_id, 'hero-large');
		$camp_hero_image_alt = get_the_title($front_page_id);
	}
}

if (!$camp_hero_image_url && file_exists(get_template_directory() . '/assets/images/early-start/synergy-classroom.jpg')) {
	$camp_hero_image_url = get_template_directory_uri() . '/assets/images/early-start/synergy-classroom.jpg';
	$camp_hero_image_alt = __('Children exploring hands-on classroom activities', 'chroma-excellence');
}

$normalize_location_name = static function ($name) {
	$name = trim((string) $name);
	$name = preg_replace('/\s+location$/i', '', $name);
	return trim((string) $name);
};

$match_region_key = static function ($slug) {
	$slug = (string) $slug;

	if (strpos($slug, 'gwinnett') !== false) {
		return 'gwinnett';
	}

	if (strpos($slug, 'cobb') !== false) {
		return 'cobb';
	}

	if (strpos($slug, 'north') !== false) {
		return 'north-metro';
	}

	if (strpos($slug, 'south') !== false) {
		return 'south-metro';
	}

	return 'metro-atlanta';
};

$regions = array(
	'gwinnett' => array(
		'label' => __('Gwinnett', 'chroma-excellence'),
		'title' => __('Gwinnett County', 'chroma-excellence'),
		'icon' => 'fa-tree',
		'chip_bg' => 'bg-chroma-greenLight',
		'chip_text' => 'text-chroma-green',
		'accent_bg' => 'bg-chroma-green',
		'button_bg' => 'bg-chroma-green',
		'button_hover' => 'hover:bg-chroma-blueDark',
		'button_text' => 'text-white',
		'posts' => array(),
	),
	'cobb' => array(
		'label' => __('Cobb', 'chroma-excellence'),
		'title' => __('Cobb County', 'chroma-excellence'),
		'icon' => 'fa-city',
		'chip_bg' => 'bg-chroma-redLight',
		'chip_text' => 'text-chroma-red',
		'accent_bg' => 'bg-chroma-red',
		'button_bg' => 'bg-chroma-red',
		'button_hover' => 'hover:bg-chroma-blueDark',
		'button_text' => 'text-white',
		'posts' => array(),
	),
	'north-metro' => array(
		'label' => __('North Metro', 'chroma-excellence'),
		'title' => __('North Metro', 'chroma-excellence'),
		'icon' => 'fa-mountain',
		'chip_bg' => 'bg-chroma-blueLight',
		'chip_text' => 'text-chroma-blue',
		'accent_bg' => 'bg-chroma-blue',
		'button_bg' => 'bg-chroma-blue',
		'button_hover' => 'hover:bg-chroma-blueDark',
		'button_text' => 'text-white',
		'posts' => array(),
	),
	'south-metro' => array(
		'label' => __('South Metro', 'chroma-excellence'),
		'title' => __('South Metro', 'chroma-excellence'),
		'icon' => 'fa-sun',
		'chip_bg' => 'bg-chroma-yellowLight',
		'chip_text' => 'text-chroma-yellow',
		'accent_bg' => 'bg-chroma-yellow',
		'button_bg' => 'bg-chroma-yellow',
		'button_hover' => 'hover:bg-chroma-blueDark',
		'button_text' => 'text-brand-ink',
		'posts' => array(),
	),
	'metro-atlanta' => array(
		'label' => __('Metro Atlanta', 'chroma-excellence'),
		'title' => __('More Campuses', 'chroma-excellence'),
		'icon' => 'fa-location-dot',
		'chip_bg' => 'bg-brand-cream',
		'chip_text' => 'text-brand-ink',
		'accent_bg' => 'bg-brand-ink',
		'button_bg' => 'bg-brand-ink',
		'button_hover' => 'hover:bg-chroma-blueDark',
		'button_text' => 'text-white',
		'posts' => array(),
	),
);

$calendar_overrides = apply_filters('chroma_summer_camp_calendar_map', array());
$has_pdf_calendar = false;
$locations_query = new WP_Query(
	array(
		'post_type' => 'location',
		'posts_per_page' => 100,
		'post_status' => 'publish',
		'orderby' => 'title',
		'order' => 'ASC',
		'no_found_rows' => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	)
);

if ($locations_query->have_posts()) {
	while ($locations_query->have_posts()) {
		$locations_query->the_post();

		$location_id = get_the_ID();
		$location_title = $normalize_location_name(get_the_title($location_id));
		$location_key = sanitize_title($location_title);
		$location_fields = chroma_get_location_fields($location_id);
		$location_regions = wp_get_post_terms($location_id, 'location_region');
		$region_term = (!empty($location_regions) && !is_wp_error($location_regions)) ? $location_regions[0] : null;
		$region_key = $match_region_key($region_term ? $region_term->slug : '');

		$calendar_override = $calendar_overrides[$location_id] ?? ($calendar_overrides[$location_key] ?? null);
		$calendar_url = chroma_get_meta_value($location_id, 'summer_camp_calendar_url', '');
		$calendar_url = $calendar_url ?: chroma_get_meta_value($location_id, 'location_summer_camp_calendar_url', '');
		$calendar_label = __('View Calendar', 'chroma-excellence');

		if (is_array($calendar_override)) {
			$calendar_url = !empty($calendar_override['url']) ? (string) $calendar_override['url'] : $calendar_url;
			$calendar_label = !empty($calendar_override['label']) ? (string) $calendar_override['label'] : $calendar_label;
		} elseif (is_string($calendar_override) && $calendar_override !== '') {
			$calendar_url = $calendar_override;
		}
		$calendar_url = function_exists('chroma_normalize_owned_url') ? chroma_normalize_owned_url($calendar_url) : $calendar_url;

		$is_pdf_calendar = (bool) preg_match('/\.pdf(?:\?.*)?$/i', (string) $calendar_url);
		if ($is_pdf_calendar) {
			$has_pdf_calendar = true;
		}

		$booking_link = chroma_get_meta_value($location_id, 'location_tour_booking_link', '');

		$regions[$region_key]['posts'][] = array(
			'id' => $location_id,
			'title' => $location_title,
			'address' => chroma_location_address_line($location_id),
			'city' => $location_fields['city'],
			'state' => $location_fields['state'],
			'permalink' => get_permalink($location_id),
			'calendar_url' => $calendar_url,
			'calendar_label' => $calendar_label,
			'is_pdf_calendar' => $is_pdf_calendar,
			'booking_link' => $booking_link,
		);
	}

	wp_reset_postdata();
}

if ($has_pdf_calendar && function_exists('chroma_enqueue_pdf_assets')) {
	chroma_enqueue_pdf_assets();
}

$active_regions = array();
$campuses = array();
foreach ($regions as $region_key => $region_data) {
	if (!empty($region_data['posts'])) {
		$active_regions[$region_key] = $region_data;
		foreach ($region_data['posts'] as $campus) {
			$campuses[] = $campus;
		}
	}
}

get_header();
?>

<main>
	<section class="relative overflow-hidden bg-white border-b border-brand-ink/5">
		<div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-chroma-yellowLight/70 via-transparent to-transparent"></div>
		<div class="absolute -left-20 bottom-0 h-72 w-72 rounded-full bg-chroma-blueLight/70 blur-3xl"></div>

		<div class="relative max-w-7xl mx-auto px-4 lg:px-6 pt-16 pb-20 lg:pt-24 lg:pb-24">
			<div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-brand-ink/50 mb-7">
				<a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-chroma-red transition"><?php esc_html_e('Home', 'chroma-excellence'); ?></a>
				<span aria-hidden="true">&middot;</span>
				<span><?php printf(esc_html__('Summer %d', 'chroma-excellence'), $camp_year); ?></span>
			</div>

			<div class="grid lg:grid-cols-[minmax(0,0.95fr)_minmax(22rem,0.75fr)] gap-10 lg:gap-16 items-center">
				<div>
					<div class="inline-flex items-center gap-2 bg-white border border-chroma-red/20 px-4 py-2 rounded-full text-[11px] font-bold uppercase tracking-[0.2em] text-brand-ink shadow-sm mb-6">
						<span class="w-2 h-2 rounded-full bg-chroma-red" aria-hidden="true"></span>
						<span><?php printf(esc_html__('Summer %d', 'chroma-excellence'), $camp_year); ?></span>
						<span aria-hidden="true">&middot;</span>
						<span><?php esc_html_e('Ages 5-12', 'chroma-excellence'); ?></span>
					</div>

					<h1 class="font-serif text-5xl md:text-7xl lg:text-8xl font-semibold tracking-[-0.045em] leading-[0.94] text-brand-ink mb-7 max-w-4xl">
						<?php echo wp_kses_post($camp_hero_title); ?>
					</h1>

					<p class="text-lg md:text-xl text-brand-ink/75 leading-relaxed max-w-3xl mb-8">
						<?php echo esc_html($camp_hero_description); ?>
					</p>

					<div class="flex flex-wrap gap-3 mb-8">
						<span class="px-4 py-2 rounded-full bg-brand-cream text-brand-ink text-xs font-bold uppercase tracking-wider border border-brand-ink/5"><?php _e('Weekly Themes', 'chroma-excellence'); ?></span>
						<span class="px-4 py-2 rounded-full bg-brand-cream text-brand-ink text-xs font-bold uppercase tracking-wider border border-brand-ink/5"><?php _e('Field Trips', 'chroma-excellence'); ?></span>
						<span class="px-4 py-2 rounded-full bg-brand-cream text-brand-ink text-xs font-bold uppercase tracking-wider border border-brand-ink/5"><?php _e('STEM Projects', 'chroma-excellence'); ?></span>
						<span class="px-4 py-2 rounded-full bg-brand-cream text-brand-ink text-xs font-bold uppercase tracking-wider border border-brand-ink/5"><?php _e('Ages 5-12', 'chroma-excellence'); ?></span>
					</div>

					<div class="flex flex-wrap gap-4">
						<a href="#calendars" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-chroma-blueDark text-white text-xs font-bold uppercase tracking-[0.2em] shadow-soft hover:bg-brand-ink transition-colors">
							<?php _e('Find Your Camp', 'chroma-excellence'); ?>
							<i class="fa-solid fa-arrow-down ml-2"></i>
						</a>
						<a href="<?php echo esc_url($schedule_tour_url); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full border border-brand-ink/10 text-brand-ink text-xs font-bold uppercase tracking-[0.2em] hover:bg-brand-cream transition-colors">
							<?php _e('Schedule a Tour', 'chroma-excellence'); ?>
						</a>
					</div>
				</div>

				<?php if ($camp_hero_image_url): ?>
					<div class="relative">
						<div class="absolute -inset-4 rounded-[3rem] bg-chroma-redLight/60 blur-2xl"></div>
						<div class="relative h-[430px] lg:h-[480px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white rotate-2 hover:rotate-0 transition-transform duration-500">
							<img src="<?php echo esc_url($camp_hero_image_url); ?>" alt="<?php echo esc_attr($camp_hero_image_alt); ?>" class="block w-full h-full object-cover no-lazy">
						</div>
					</div>
				<?php endif; ?>
			</div>

		</div>
	</section>

	<section id="highlights" class="py-20 bg-brand-cream border-b border-brand-ink/5 scroll-mt-24">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center max-w-3xl mx-auto mb-14">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Camp Highlights', 'chroma-excellence'); ?></span>
				<h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-4"><?php _e('What makes summer at Chroma different?', 'chroma-excellence'); ?></h2>
				<p class="text-brand-ink/70"><?php _e('Summer camp keeps children active, curious, and connected through weekly adventures, creative projects, and joyful seasonal rhythms.', 'chroma-excellence'); ?></p>
			</div>
			<div class="grid md:grid-cols-3 gap-8">
				<div class="bg-white p-8 rounded-[2rem] border border-brand-ink/5 shadow-card text-center hover:-translate-y-1 transition-transform">
					<div class="w-16 h-16 mx-auto bg-chroma-blueLight rounded-full flex items-center justify-center text-2xl text-chroma-blue mb-6">
						<i class="fa-solid fa-bus"></i>
					</div>
					<h3 class="font-serif text-xl font-bold text-brand-ink mb-3"><?php _e('Weekly Field Trips', 'chroma-excellence'); ?></h3>
					<p class="text-sm text-brand-ink/70 leading-relaxed"><?php _e('From museums to community outings, camp extends learning beyond the classroom with structured, supervised adventures.', 'chroma-excellence'); ?></p>
				</div>

				<div class="bg-white p-8 rounded-[2rem] border border-brand-ink/5 shadow-card text-center hover:-translate-y-1 transition-transform">
					<div class="w-16 h-16 mx-auto bg-chroma-yellowLight rounded-full flex items-center justify-center text-2xl text-chroma-yellow mb-6">
						<i class="fa-solid fa-rocket"></i>
					</div>
					<h3 class="font-serif text-xl font-bold text-brand-ink mb-3"><?php _e('Themes, STEM, and Creativity', 'chroma-excellence'); ?></h3>
					<p class="text-sm text-brand-ink/70 leading-relaxed"><?php _e('Each week introduces a new lens for discovery with hands-on projects, maker activities, and age-appropriate collaborative play.', 'chroma-excellence'); ?></p>
				</div>

				<div class="bg-white p-8 rounded-[2rem] border border-brand-ink/5 shadow-card text-center hover:-translate-y-1 transition-transform">
					<div class="w-16 h-16 mx-auto bg-chroma-greenLight rounded-full flex items-center justify-center text-2xl text-chroma-green mb-6">
						<i class="fa-solid fa-water"></i>
					</div>
					<h3 class="font-serif text-xl font-bold text-brand-ink mb-3"><?php _e('Splash Days and Summer Energy', 'chroma-excellence'); ?></h3>
					<p class="text-sm text-brand-ink/70 leading-relaxed"><?php _e('Built-in water play and active outdoor time help campers stay cool, engaged, and moving through the warmest weeks of the season.', 'chroma-excellence'); ?></p>
				</div>
			</div>
		</div>
	</section>

	<section id="calendars" class="py-24 bg-white scroll-mt-24">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center max-w-3xl mx-auto mb-10">
				<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php printf(esc_html__('%d Camp Calendars', 'chroma-excellence'), $camp_year); ?></span>
				<h2 class="font-serif text-4xl md:text-5xl font-bold text-brand-ink mb-4"><?php _e('Find Your Camp Calendar', 'chroma-excellence'); ?></h2>
				<p class="text-brand-ink/70"><?php _e('Choose your region, then open your participating campus calendar or ask about summer availability.', 'chroma-excellence'); ?></p>
			</div>

			<?php if (!empty($active_regions)): ?>
				<div class="flex flex-wrap justify-center gap-2 mb-12 sticky top-[82px] z-40 bg-white/95 backdrop-blur-md py-4 border-y border-brand-ink/5">
					<?php foreach ($active_regions as $region_key => $region): ?>
						<a href="#region-<?php echo esc_attr($region_key); ?>" class="px-5 py-2 rounded-full border border-brand-ink/10 text-brand-ink text-xs font-bold uppercase tracking-wider <?php echo esc_attr($region['chip_bg']); ?> <?php echo esc_attr($region['chip_text']); ?> hover:border-brand-ink/20 transition-colors">
							<?php echo esc_html($region['label']); ?>
						</a>
					<?php endforeach; ?>
				</div>

				<?php foreach ($active_regions as $region_key => $region): ?>
					<section id="region-<?php echo esc_attr($region_key); ?>" class="mb-16 last:mb-0 scroll-mt-40">
						<h3 class="flex items-center gap-3 font-serif text-2xl font-bold text-brand-ink mb-6 pb-3 border-b border-brand-ink/10">
							<span class="w-10 h-10 rounded-full <?php echo esc_attr($region['accent_bg']); ?> <?php echo ('south-metro' === $region_key) ? 'text-brand-ink' : 'text-white'; ?> flex items-center justify-center text-sm">
								<i class="fa-solid <?php echo esc_attr($region['icon']); ?>"></i>
							</span>
							<?php echo esc_html($region['title']); ?>
						</h3>

						<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
							<?php foreach ($region['posts'] as $location): ?>
								<article class="bg-brand-cream p-6 rounded-[2rem] border border-brand-ink/5 shadow-sm flex flex-col h-full relative overflow-hidden">
									<span class="absolute top-4 right-4 bg-white text-brand-ink/60 text-[9px] font-bold uppercase px-2 py-1 rounded-md border border-brand-ink/10">
										<?php _e('Ages 5-12', 'chroma-excellence'); ?>
									</span>

									<div class="pr-16 mb-5">
										<h4 class="font-bold text-lg text-brand-ink mb-1"><?php echo esc_html($location['title']); ?></h4>
										<p class="text-xs text-brand-ink/60">
											<?php echo esc_html($location['address']); ?>
											<?php if (!empty($location['city'])): ?>
												<br><?php echo esc_html(trim($location['city'] . ', ' . $location['state'])); ?>
											<?php endif; ?>
										</p>
									</div>

									<div class="mt-auto flex flex-col gap-3">
										<?php if (!empty($location['calendar_url']) && $location['is_pdf_calendar']): ?>
											<a
												href="<?php echo esc_url($location['calendar_url']); ?>"
												class="chroma-pdf-trigger flex items-center justify-center gap-2 w-full py-3 bg-white border border-chroma-blue text-chroma-blue text-xs font-bold uppercase tracking-widest rounded-xl hover:bg-chroma-blue hover:text-white transition-colors"
												data-pdf-url="<?php echo esc_url($location['calendar_url']); ?>"
												data-pdf-title="<?php echo esc_attr($location['title'] . ' ' . sprintf(__('Summer %d Calendar', 'chroma-excellence'), $camp_year)); ?>"
											>
												<i class="fa-regular fa-calendar text-lg"></i>
												<?php echo esc_html($location['calendar_label']); ?>
											</a>
										<?php elseif (!empty($location['calendar_url'])): ?>
											<a
												href="<?php echo esc_url($location['calendar_url']); ?>"
												target="_blank"
												rel="noopener noreferrer"
												class="flex items-center justify-center gap-2 w-full py-3 bg-white border border-chroma-blue text-chroma-blue text-xs font-bold uppercase tracking-widest rounded-xl hover:bg-chroma-blue hover:text-white transition-colors"
											>
												<i class="fa-regular fa-calendar text-lg"></i>
												<?php echo esc_html($location['calendar_label']); ?>
											</a>
										<?php else: ?>
											<a
												href="<?php echo esc_url($location['permalink']); ?>"
												class="flex items-center justify-center gap-2 w-full py-3 bg-white border border-brand-ink/10 text-brand-ink text-xs font-bold uppercase tracking-widest rounded-xl hover:bg-brand-ink hover:text-white transition-colors"
											>
												<i class="fa-solid fa-location-arrow text-sm"></i>
												<?php _e('Camp Details', 'chroma-excellence'); ?>
											</a>
										<?php endif; ?>

										<?php if (!empty($location['booking_link'])): ?>
											<a
												href="<?php echo esc_url($location['booking_link']); ?>"
												class="booking-btn flex items-center justify-center gap-2 w-full py-3 <?php echo esc_attr($region['button_bg']); ?> <?php echo esc_attr($region['button_text']); ?> text-xs font-bold uppercase tracking-widest rounded-xl <?php echo esc_attr($region['button_hover']); ?> hover:text-white transition-colors"
											>
												<?php _e('Schedule Tour', 'chroma-excellence'); ?>
											</a>
										<?php else: ?>
											<a
												href="#camp-inquiry"
												class="summer-camp-tour-btn flex items-center justify-center gap-2 w-full py-3 <?php echo esc_attr($region['button_bg']); ?> <?php echo esc_attr($region['button_text']); ?> text-xs font-bold uppercase tracking-widest rounded-xl <?php echo esc_attr($region['button_hover']); ?> hover:text-white transition-colors"
												data-campus="<?php echo esc_attr($location['title']); ?>"
											>
												<?php _e('Ask About This Camp', 'chroma-excellence'); ?>
											</a>
										<?php endif; ?>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			<?php else: ?>
				<div class="max-w-2xl mx-auto text-center bg-brand-cream border border-brand-ink/5 rounded-[2rem] p-10">
					<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php _e('No summer camp campuses are available yet.', 'chroma-excellence'); ?></h3>
					<p class="text-brand-ink/70"><?php _e('Publish location posts to populate this directory automatically.', 'chroma-excellence'); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section id="faq" class="py-24 bg-brand-cream border-t border-brand-ink/5 scroll-mt-24">
		<div class="max-w-4xl mx-auto px-4 lg:px-6">
			<div class="text-center mb-12">
				<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Common Questions', 'chroma-excellence'); ?></span>
				<h2 class="font-serif text-3xl font-bold text-brand-ink"><?php _e('Summer Camp FAQ', 'chroma-excellence'); ?></h2>
			</div>

			<div class="space-y-4">
				<details class="group bg-white rounded-2xl p-5 border border-brand-ink/5 shadow-sm">
					<summary class="flex items-center justify-between gap-4 font-bold text-brand-ink list-none cursor-pointer">
						<span><?php _e('What ages is summer camp for?', 'chroma-excellence'); ?></span>
						<span class="text-chroma-blue group-open:rotate-180 transition-transform"><i class="fa-solid fa-chevron-down"></i></span>
					</summary>
					<p class="mt-3 text-sm text-brand-ink/70 leading-relaxed"><?php _e('Chroma Summer Camp is designed for school-age children ages 5 to 12. Check your preferred campus calendar for any campus-specific groupings or programming notes.', 'chroma-excellence'); ?></p>
				</details>

				<details class="group bg-white rounded-2xl p-5 border border-brand-ink/5 shadow-sm">
					<summary class="flex items-center justify-between gap-4 font-bold text-brand-ink list-none cursor-pointer">
						<span><?php _e('Can I enroll by week?', 'chroma-excellence'); ?></span>
						<span class="text-chroma-blue group-open:rotate-180 transition-transform"><i class="fa-solid fa-chevron-down"></i></span>
					</summary>
					<p class="mt-3 text-sm text-brand-ink/70 leading-relaxed"><?php _e('Many families build their summer schedule one week at a time. Availability varies by campus and by week, so earlier registration gives you the best selection.', 'chroma-excellence'); ?></p>
				</details>

				<details class="group bg-white rounded-2xl p-5 border border-brand-ink/5 shadow-sm">
					<summary class="flex items-center justify-between gap-4 font-bold text-brand-ink list-none cursor-pointer">
						<span><?php _e('What should my child bring each day?', 'chroma-excellence'); ?></span>
						<span class="text-chroma-blue group-open:rotate-180 transition-transform"><i class="fa-solid fa-chevron-down"></i></span>
					</summary>
					<p class="mt-3 text-sm text-brand-ink/70 leading-relaxed"><?php _e('Your campus will share the final checklist, but families should expect to send comfortable play clothes, any required personal items, and field-trip or splash-day essentials when noted on the calendar.', 'chroma-excellence'); ?></p>
				</details>

				<details class="group bg-white rounded-2xl p-5 border border-brand-ink/5 shadow-sm">
					<summary class="flex items-center justify-between gap-4 font-bold text-brand-ink list-none cursor-pointer">
						<span><?php _e('How do I view my campus calendar and field trip schedule?', 'chroma-excellence'); ?></span>
						<span class="text-chroma-blue group-open:rotate-180 transition-transform"><i class="fa-solid fa-chevron-down"></i></span>
					</summary>
					<p class="mt-3 text-sm text-brand-ink/70 leading-relaxed"><?php _e('Use the campus cards above to open the latest calendar for your preferred location. If you need help finding the right campus or week, use the inquiry form below and our team will help.', 'chroma-excellence'); ?></p>
				</details>
			</div>
		</div>
	</section>

	<section id="camp-inquiry" class="py-24 bg-chroma-blueDark text-white relative overflow-hidden scroll-mt-24">
		<div class="absolute -right-20 -top-20 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
		<div class="max-w-6xl mx-auto px-4 lg:px-6 relative z-10 grid lg:grid-cols-[0.9fr,1.1fr] gap-10 items-start">
			<div>
				<span class="text-chroma-yellow font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Need Help Choosing?', 'chroma-excellence'); ?></span>
				<h2 class="font-serif text-3xl md:text-5xl font-bold mb-4"><?php _e('Ask about camp availability or schedule a walkthrough.', 'chroma-excellence'); ?></h2>
				<p class="text-white/80 text-lg mb-6"><?php _e('Tell us your preferred campus, weeks, and child’s age. A Chroma team member will help confirm availability and next steps.', 'chroma-excellence'); ?></p>

				<div id="summer-camp-campus-panel" class="hidden bg-white/10 border border-white/15 rounded-[2rem] p-6 mb-6">
					<p class="text-[11px] font-bold uppercase tracking-[0.2em] text-white/60 mb-2"><?php _e('Selected Campus', 'chroma-excellence'); ?></p>
					<p id="summer-camp-campus-name" class="font-serif text-2xl font-bold text-white"></p>
					<p class="text-sm text-white/75 mt-2"><?php _e('If direct online booking is not available for this location yet, use the form to ask for the camp calendar and next tour times.', 'chroma-excellence'); ?></p>
				</div>

				<div class="bg-white/10 border border-white/15 rounded-[2rem] p-6">
					<p class="text-[11px] font-bold uppercase tracking-[0.2em] text-white/60 mb-2"><?php _e('What to Expect', 'chroma-excellence'); ?></p>
					<p class="text-sm text-white/80"><?php _e('Campus cards with a direct booking link keep the current booking flow. Cards without one send families here so no CTA dead-ends.', 'chroma-excellence'); ?></p>
				</div>
			</div>

			<div class="chroma-form-scroll-card chroma-form-scroll-card--summer bg-white p-4 md:p-6 rounded-[2.5rem] text-brand-ink shadow-2xl">
				<?php if (shortcode_exists('chroma_tour_form')): ?>
					<?php echo do_shortcode('[chroma_tour_form]'); ?>
				<?php else: ?>
					<div class="p-8 text-center">
						<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php _e('Tour form unavailable', 'chroma-excellence'); ?></h3>
						<p class="text-brand-ink/70"><?php _e('Activate the Chroma Tour Form plugin to render the inquiry form on this landing page.', 'chroma-excellence'); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>

</main>

<div id="chroma-tour-modal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
	<div class="absolute inset-0 bg-brand-ink/80 backdrop-blur-sm transition-opacity" id="chroma-tour-backdrop"></div>

	<div class="absolute inset-4 md:inset-10 bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-fade-in-up">
		<div class="bg-brand-cream border-b border-brand-ink/5 px-6 py-4 flex items-center justify-between flex-shrink-0">
			<h3 class="font-serif text-xl font-bold text-brand-ink"><?php _e('Schedule Your Visit', 'chroma-excellence'); ?></h3>
			<div class="flex items-center gap-4">
				<a href="#" id="chroma-tour-external" target="_blank" rel="noopener noreferrer"
					class="text-xs font-bold uppercase tracking-wider text-brand-ink/50 hover:text-chroma-blue transition-colors hidden md:block">
					<?php _e('Open in new tab', 'chroma-excellence'); ?> <i class="fa-solid fa-external-link-alt ml-1"></i>
				</a>
				<button id="chroma-tour-close"
					class="w-10 h-10 rounded-full bg-white border border-brand-ink/10 flex items-center justify-center text-brand-ink hover:bg-chroma-red hover:text-white hover:border-chroma-red transition-all">
					<i class="fa-solid fa-xmark text-lg"></i>
				</button>
			</div>
		</div>

		<div class="chroma-booking-scroll-frame flex-grow relative bg-white">
			<div id="chroma-tour-loader" class="absolute inset-0 flex items-center justify-center bg-white z-10">
				<div class="w-12 h-12 border-4 border-chroma-blue/20 border-t-chroma-blue rounded-full animate-spin"></div>
			</div>
			<iframe id="chroma-tour-frame" src="about:blank" class="w-full h-full border-0" title="<?php esc_attr_e('Schedule tour booking form', 'chroma-excellence'); ?>"
				allow="camera; microphone; autoplay; encrypted-media;"></iframe>
		</div>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		var campusPanel = document.getElementById('summer-camp-campus-panel');
		var campusName = document.getElementById('summer-camp-campus-name');
		var inquirySection = document.getElementById('camp-inquiry');
		var buttons = document.querySelectorAll('.summer-camp-tour-btn[data-campus]');
		var modal = document.getElementById('chroma-tour-modal');
		var backdrop = document.getElementById('chroma-tour-backdrop');
		var closeBtn = document.getElementById('chroma-tour-close');
		var iframe = document.getElementById('chroma-tour-frame');
		var externalLink = document.getElementById('chroma-tour-external');
		var loader = document.getElementById('chroma-tour-loader');

		function openTourModal(url) {
			if (!modal || !iframe || !loader || !externalLink) {
				window.location.href = url;
				return;
			}

			modal.classList.remove('hidden');
			document.body.style.overflow = 'hidden';
			loader.classList.remove('hidden');
			iframe.src = url;
			externalLink.href = url;

			iframe.onload = function () {
				loader.classList.add('hidden');
			};
		}

		function closeTourModal() {
			if (!modal || !iframe || !loader) {
				return;
			}

			modal.classList.add('hidden');
			document.body.style.overflow = '';
			iframe.src = 'about:blank';
			loader.classList.remove('hidden');
		}

		document.querySelectorAll('.booking-btn').forEach(function (button) {
			button.addEventListener('click', function (event) {
				var url = button.getAttribute('href');
				if (!url || url.indexOf('http') !== 0) {
					return;
				}

				event.preventDefault();
				openTourModal(url);
			});
		});

		if (!campusPanel || !campusName || !inquirySection || !buttons.length) {
			if (closeBtn) {
				closeBtn.addEventListener('click', closeTourModal);
			}
			if (backdrop) {
				backdrop.addEventListener('click', closeTourModal);
			}
			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
					closeTourModal();
				}
			});
			return;
		}

		buttons.forEach(function (button) {
			button.addEventListener('click', function () {
				var selectedCampus = button.getAttribute('data-campus');
				if (!selectedCampus) {
					return;
				}

				campusName.textContent = selectedCampus;
				campusPanel.classList.remove('hidden');

				window.setTimeout(function () {
					inquirySection.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}, 40);
			});
		});

		if (closeBtn) {
			closeBtn.addEventListener('click', closeTourModal);
		}
		if (backdrop) {
			backdrop.addEventListener('click', closeTourModal);
		}
		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
				closeTourModal();
			}
		});
	});
</script>

<?php
get_footer();

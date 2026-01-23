<?php
/**
 * Template Name: Parents Page
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();

$page_id = get_the_ID();

// Hero Section
$hero_badge = chroma_get_translated_meta($page_id, 'parents_hero_badge') ?: __('Parent Dashboard', 'chroma-excellence');
$hero_title = chroma_get_translated_meta($page_id, 'parents_hero_title') ?: __('Partners in your child\'s journey.', 'chroma-excellence');
$hero_description = chroma_get_translated_meta($page_id, 'parents_hero_description') ?: __('Everything you need to manage your enrollment, stay connected, and engage with the Chroma community.', 'chroma-excellence');

// Parent Essentials Section
$essentials_title = chroma_get_translated_meta($page_id, 'parents_essentials_title') ?: __('Parent Essentials', 'chroma-excellence');

$resources = array(
	array(
		'name' => 'procare',
		'icon' => chroma_get_translated_meta($page_id, 'parents_resource_procare_icon') ?: 'fa-solid fa-cloud',
		'title' => chroma_get_translated_meta($page_id, 'parents_resource_procare_title') ?: __('Procare Cloud', 'chroma-excellence'),
		'description' => chroma_get_translated_meta($page_id, 'parents_resource_procare_desc') ?: __('Daily reports, photos, and attendance tracking.', 'chroma-excellence'),
		'url' => chroma_get_translated_meta($page_id, 'parents_resource_procare_url') ?: '#',
		'colorClass' => 'chroma-blue',
	),
	array(
		'name' => 'tuition',
		'icon' => chroma_get_translated_meta($page_id, 'parents_resource_tuition_icon') ?: 'fa-solid fa-credit-card',
		'title' => chroma_get_translated_meta($page_id, 'parents_resource_tuition_title') ?: __('Tuition Portal', 'chroma-excellence'),
		'description' => chroma_get_translated_meta($page_id, 'parents_resource_tuition_desc') ?: __('Securely view statements and make payments.', 'chroma-excellence'),
		'url' => chroma_get_translated_meta($page_id, 'parents_resource_tuition_url') ?: '#',
		'colorClass' => 'chroma-green',
	),
	array(
		'name' => 'handbook',
		'icon' => chroma_get_translated_meta($page_id, 'parents_resource_handbook_icon') ?: 'fa-solid fa-book-open',
		'title' => chroma_get_translated_meta($page_id, 'parents_resource_handbook_title') ?: __('Parent Handbook', 'chroma-excellence'),
		'description' => chroma_get_translated_meta($page_id, 'parents_resource_handbook_desc') ?: __('Policies, procedures, and operational details.', 'chroma-excellence'),
		'url' => chroma_get_translated_meta($page_id, 'parents_resource_handbook_url') ?: '#',
		'colorClass' => 'chroma-yellow',
	),
	array(
		'name' => 'enrollment',
		'icon' => chroma_get_translated_meta($page_id, 'parents_resource_enrollment_icon') ?: 'fa-solid fa-file-signature',
		'title' => chroma_get_translated_meta($page_id, 'parents_resource_enrollment_title') ?: __('Enrollment Agreement', 'chroma-excellence'),
		'description' => chroma_get_translated_meta($page_id, 'parents_resource_enrollment_desc') ?: __('Update your annual enrollment documents.', 'chroma-excellence'),
		'url' => chroma_get_translated_meta($page_id, 'parents_resource_enrollment_url') ?: '#',
		'colorClass' => 'chroma-red',
	),
	array(
		'name' => 'prekga',
		'icon' => chroma_get_translated_meta($page_id, 'parents_resource_prekga_icon') ?: 'fa-solid fa-graduation-cap',
		'title' => chroma_get_translated_meta($page_id, 'parents_resource_prekga_title') ?: __('GA Pre-K Enrollment', 'chroma-excellence'),
		'description' => chroma_get_translated_meta($page_id, 'parents_resource_prekga_desc') ?: __('Lottery registration and required state forms.', 'chroma-excellence'),
		'url' => chroma_get_translated_meta($page_id, 'parents_resource_prekga_url') ?: '#',
		'colorClass' => 'brand-ink',
	),
	array(
		'name' => 'waitlist',
		'icon' => chroma_get_translated_meta($page_id, 'parents_resource_waitlist_icon') ?: 'fa-solid fa-clock',
		'title' => chroma_get_translated_meta($page_id, 'parents_resource_waitlist_title') ?: __('Join Waitlist', 'chroma-excellence'),
		'description' => chroma_get_translated_meta($page_id, 'parents_resource_waitlist_desc') ?: __('Reserve a spot for siblings or future terms.', 'chroma-excellence'),
		'url' => chroma_get_translated_meta($page_id, 'parents_resource_waitlist_url') ?: '#',
		'colorClass' => 'brand-ink',
	),
);

// Events Section
$events_badge = chroma_get_translated_meta($page_id, 'parents_events_badge') ?: __('Community', 'chroma-excellence');
$events_title = chroma_get_translated_meta($page_id, 'parents_events_title') ?: __('Traditions & Celebrations', 'chroma-excellence');
$events_description = chroma_get_translated_meta($page_id, 'parents_events_description') ?: __('We believe in building a village. Our calendar is peppered with events designed to bring families together and celebrate our students\' milestones.', 'chroma-excellence');
$events_image = chroma_get_translated_meta($page_id, 'parents_events_image') ?: 'https://images.unsplash.com/photo-1511895426328-dc8714191300?q=80&w=800&auto=format&fit=crop';

$events = array(
	array(
		'icon' => chroma_get_translated_meta($page_id, 'parents_event1_icon') ?: 'fa-solid fa-calendar-days',
		'color' => 'chroma-yellow',
		'title' => chroma_get_translated_meta($page_id, 'parents_event1_title') ?: __('Quarterly Family Events', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'parents_event1_desc') ?: __('Every season brings a reason to gather. From our Fall Festival and Winter "Cookies & Cocoa" to our Spring Art Show and Summer Splash Days, we create memories for the whole family.', 'chroma-excellence'),
	),
	array(
		'icon' => chroma_get_translated_meta($page_id, 'parents_event2_icon') ?: 'fa-solid fa-star',
		'color' => 'chroma-red',
		'title' => chroma_get_translated_meta($page_id, 'parents_event2_title') ?: __('Pre-K Graduation', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'parents_event2_desc') ?: __('A cap-and-gown ceremony celebrating our 4 and 5-year-olds as they transition to Kindergarten. It\'s the highlight of our academic year!', 'chroma-excellence'),
	),
	array(
		'icon' => chroma_get_translated_meta($page_id, 'parents_event3_icon') ?: 'fa-solid fa-handshake',
		'color' => 'chroma-green',
		'title' => chroma_get_translated_meta($page_id, 'parents_event3_title') ?: __('Parent-Teacher Conferences', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'parents_event3_desc') ?: __('Twice a year, we sit down to review your child\'s developmental portfolio, set goals, and celebrate their individual growth curve.', 'chroma-excellence'),
	),
);

// Nutrition Section
$nutrition_badge = chroma_get_translated_meta($page_id, 'parents_nutrition_badge') ?: __('Wellness', 'chroma-excellence');
$nutrition_title = chroma_get_translated_meta($page_id, 'parents_nutrition_title') ?: __('What\'s for lunch?', 'chroma-excellence');
$nutrition_description = chroma_get_translated_meta($page_id, 'parents_nutrition_description') ?: __('Our in-house chefs prepare balanced, CACFP-compliant meals fresh daily. We are a nut-aware facility.', 'chroma-excellence');
$nutrition_image = chroma_get_translated_meta($page_id, 'parents_nutrition_image') ?: 'https://images.unsplash.com/photo-1564834724105-918b73d1b9e0?q=80&w=800&auto=format&fit=crop';

$menus = array(
	array(
		'icon' => chroma_get_translated_meta($page_id, 'parents_menu1_icon') ?: 'fa-solid fa-carrot',
		'color' => 'chroma-green',
		'bgClass' => 'chroma-greenLight',
		'title' => chroma_get_translated_meta($page_id, 'parents_menu1_title') ?: __('Current Month Menu', 'chroma-excellence'),
		'subtitle' => chroma_get_translated_meta($page_id, 'parents_menu1_subtitle') ?: __('Standard (Ages 1-12)', 'chroma-excellence'),
		'url' => chroma_get_translated_meta($page_id, 'parents_menu1_url') ?: '#',
	),
	array(
		'icon' => chroma_get_translated_meta($page_id, 'parents_menu2_icon') ?: 'fa-solid fa-baby',
		'color' => 'chroma-blue',
		'bgClass' => 'chroma-blueLight',
		'title' => chroma_get_translated_meta($page_id, 'parents_menu2_title') ?: __('Infant Puree Menu', 'chroma-excellence'),
		'subtitle' => chroma_get_translated_meta($page_id, 'parents_menu2_subtitle') ?: __('Stage 1 & 2 Solids', 'chroma-excellence'),
		'url' => chroma_get_translated_meta($page_id, 'parents_menu2_url') ?: '#',
	),
	array(
		'icon' => chroma_get_translated_meta($page_id, 'parents_menu3_icon') ?: 'fa-solid fa-wheat-awn-circle-exclamation',
		'color' => 'chroma-red',
		'bgClass' => 'chroma-redLight',
		'title' => chroma_get_translated_meta($page_id, 'parents_menu3_title') ?: __('Allergy Statement', 'chroma-excellence'),
		'subtitle' => chroma_get_translated_meta($page_id, 'parents_menu3_subtitle') ?: __('Our Nut-Free Protocols', 'chroma-excellence'),
		'url' => chroma_get_translated_meta($page_id, 'parents_menu3_url') ?: '#',
	),
);

// Safety Section
$safety_title = chroma_get_translated_meta($page_id, 'parents_safety_title') ?: __('Safe. Secure. Connected.', 'chroma-excellence');
$safety_description = chroma_get_translated_meta($page_id, 'parents_safety_description') ?: __('We employ enterprise-grade security measures and transparent communication protocols so you can have total peace of mind while you work.', 'chroma-excellence');

$safety_items = array(
	array(
		'icon' => chroma_get_translated_meta($page_id, 'parents_safety1_icon') ?: 'fa-solid fa-video',
		'color' => 'chroma-green',
		'title' => chroma_get_translated_meta($page_id, 'parents_safety1_title') ?: __('24/7 Monitored Cameras', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'parents_safety1_desc') ?: __('Our facilities are equipped with high-definition closed-circuit cameras in every classroom, hallway, and playground. Feeds are monitored by leadership to ensure policy adherence and safety.', 'chroma-excellence'),
	),
	array(
		'icon' => chroma_get_translated_meta($page_id, 'parents_safety2_icon') ?: 'fa-solid fa-mobile-screen-button',
		'color' => 'chroma-blue',
		'title' => chroma_get_translated_meta($page_id, 'parents_safety2_title') ?: __('Real-Time Updates', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'parents_safety2_desc') ?: __('Through the Procare app, you receive real-time notifications for meals, naps, and diaper changes, plus photos of your child engaging in the curriculum throughout the day.', 'chroma-excellence'),
	),
	array(
		'icon' => chroma_get_translated_meta($page_id, 'parents_safety3_icon') ?: 'fa-solid fa-lock',
		'color' => 'chroma-red',
		'title' => chroma_get_translated_meta($page_id, 'parents_safety3_title') ?: __('Secure Access Control', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'parents_safety3_desc') ?: __('Our lobbies are secured with coded keypad entry systems. Codes are unique to each family and change regularly. ID is strictly required for any alternative pickups.', 'chroma-excellence'),
	),
);

// FAQ Section
$faq_title = chroma_get_translated_meta($page_id, 'parents_faq_title') ?: __('Operational Policy FAQ', 'chroma-excellence');
$faq_description = chroma_get_translated_meta($page_id, 'parents_faq_description') ?: __('Quick answers to common day-to-day questions.', 'chroma-excellence');

$faqs = array(
	array(
		'question' => chroma_get_translated_meta($page_id, 'parents_faq1_question') ?: __('What is the sick child policy?', 'chroma-excellence'),
		'answer' => chroma_get_translated_meta($page_id, 'parents_faq1_answer') ?: __('Children must be symptom-free (fever under 100.4°F, no vomiting/diarrhea) for 24 hours without medication before returning to school. Please report any contagious illnesses to the Director immediately.', 'chroma-excellence'),
	),
	array(
		'question' => chroma_get_translated_meta($page_id, 'parents_faq2_question') ?: __('How do you handle inclement weather?', 'chroma-excellence'),
		'answer' => chroma_get_translated_meta($page_id, 'parents_faq2_answer') ?: __('We generally follow the local county school system for weather closures, but we make independent decisions based on staff safety. Alerts will be sent via Procare and posted on our Facebook page by 6:00 AM.', 'chroma-excellence'),
	),
	array(
		'question' => chroma_get_translated_meta($page_id, 'parents_faq3_question') ?: __('What is the late pickup policy?', 'chroma-excellence'),
		'answer' => chroma_get_translated_meta($page_id, 'parents_faq3_answer') ?: __('We close promptly at 6:00 PM. A late fee of $1 per minute is charged to your account for pickups after 6:05 PM to compensate our staff who stay late.', 'chroma-excellence'),
	),
);

// Referral Banner
$referral_title = chroma_get_translated_meta($page_id, 'parents_referral_title') ?: __('Love the Chroma family?', 'chroma-excellence');
$referral_description = chroma_get_translated_meta($page_id, 'parents_referral_description') ?: __('Refer a friend and receive a <strong>$100 tuition credit</strong> when they enroll.', 'chroma-excellence');
$referral_button_text = chroma_get_translated_meta($page_id, 'parents_referral_button_text') ?: __('Refer a Friend', 'chroma-excellence');
$referral_button_url = chroma_get_translated_meta($page_id, 'parents_referral_button_url') ?: 'mailto:director@chromaela.com?subject=Parent%20Referral';

// Life at Chroma Gallery
// Moments of Joy Gallery
$gallery_raw = chroma_get_translated_meta($page_id, 'parents_moments_gallery');
$gallery_images = array();
if (!empty($gallery_raw)) {
	$lines = explode("\n", $gallery_raw);
	foreach ($lines as $line) {
		$url = trim($line);
		if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
			$gallery_images[] = esc_url($url);
		}
	}
}

// Fallback if no gallery images provided
if (empty($gallery_images)) {
	$gallery_images = array(
		'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=800&auto=format&fit=crop',
		'https://images.unsplash.com/photo-1587654780291-39c940483713?q=80&w=800&auto=format&fit=crop',
		'https://images.unsplash.com/photo-1560785496-3c9d27877182?q=80&w=800&auto=format&fit=crop',
		'https://images.unsplash.com/photo-1596464716127-f9a82741cac8?q=80&w=800&auto=format&fit=crop',
		'https://images.unsplash.com/photo-1516627145497-ae6968895b74?q=80&w=800&auto=format&fit=crop'
	);
}
?>

<main id="primary" class="site-main" role="main">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<!-- Hero -->
		<section class="py-20 bg-white text-center border-b border-brand-ink/5">
			<div class="max-w-4xl mx-auto px-4">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
					<?php echo esc_html($hero_badge); ?>
				</span>
				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6">
					<?php echo esc_html($hero_title); ?>
				</h1>
				<p class="text-lg text-brand-ink/80">
					<?php echo esc_html($hero_description); ?>
				</p>
			</div>
		</section>

		<!-- Resources Grid (Quick Links) -->
		<section id="resources" class="py-24 bg-brand-cream">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-16">
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink">
						<?php echo esc_html($essentials_title); ?>
					</h2>
				</div>

				<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-6">
					<?php foreach ($resources as $resource): 
						$is_pdf = in_array($resource['name'], array('handbook'));
						$is_external_portal = in_array($resource['name'], array('procare', 'tuition', 'enrollment', 'prekga', 'waitlist'));
						
						$link_class = 'bg-white p-8 rounded-[2rem] shadow-card hover:-translate-y-1 transition-transform group border border-brand-ink/5 flex flex-col items-center text-center';
						$attrs = '';
						
						if ($is_pdf) {
							$link_class .= ' chroma-pdf-trigger';
							$attrs = 'data-pdf-url="' . esc_url($resource['url']) . '" data-pdf-title="' . esc_attr($resource['title']) . '"';
						} elseif ($is_external_portal) {
							$attrs = 'target="_blank"';
						}
					?>
						<a href="<?php echo esc_url($resource['url']); ?>" 
						   class="<?php echo esc_attr($link_class); ?>"
						   <?php echo $attrs; ?>>
							<div
								class="w-16 h-16 bg-<?php echo esc_attr($resource['colorClass']); ?>/10 rounded-2xl flex items-center justify-center text-3xl mb-4 text-<?php echo esc_attr($resource['colorClass']); ?> group-hover:bg-<?php echo esc_attr($resource['colorClass']); ?> group-hover:text-white transition-colors">
								<i class="<?php echo esc_attr($resource['icon']); ?>"></i>
							</div>
							<h3 class="font-bold text-lg text-brand-ink mb-2">
								<?php echo esc_html($resource['title']); ?>
							</h3>
							<p class="text-xs text-brand-ink/80">
								<?php echo esc_html($resource['description']); ?>
							</p>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Events Section -->
		<section id="events" class="py-24 bg-white relative overflow-hidden">
			<div
				class="absolute top-0 right-0 w-1/2 h-full bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-chroma-yellowLight/50 via-transparent to-transparent">
			</div>
			<div class="max-w-6xl mx-auto px-4 lg:px-6 relative z-10">
				<div class="grid md:grid-cols-2 gap-16 items-center">
					<div>
						<span class="text-chroma-yellow font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
							<?php echo esc_html($events_badge); ?>
						</span>
						<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
							<?php echo esc_html($events_title); ?>
						</h2>
						<p class="text-brand-ink/80 mb-8 text-lg">
							<?php echo esc_html($events_description); ?>
						</p>

						<div class="space-y-8">
							<?php foreach ($events as $event): ?>
								<div>
									<h3 class="font-bold text-xl text-brand-ink mb-2 flex items-center gap-2">
										<i
											class="<?php echo esc_attr($event['icon']); ?> text-<?php echo esc_attr($event['color']); ?>"></i>
										<?php echo esc_html($event['title']); ?>
									</h3>
									<p class="text-sm text-brand-ink/80 leading-relaxed">
										<?php echo esc_html($event['desc']); ?>
									</p>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					<div
						class="relative h-[500px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-brand-cream rotate-2">
						<img src="<?php echo esc_url($events_image); ?>" class="w-full h-full object-cover"
							alt="<?php echo esc_attr($events_title); ?>" />
					</div>
				</div>
			</div>
		</section>

		<!-- Life at Chroma Gallery -->
		<section class="py-24 bg-white overflow-hidden">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-16">
					<span class="text-chroma-orange font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Life at Chroma', 'chroma-excellence'); ?></span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink"><?php _e('Moments of Joy', 'chroma-excellence'); ?></h2>
				</div>

				<div class="relative w-full max-w-5xl mx-auto aspect-video md:aspect-[21/9] rounded-[2rem] overflow-hidden shadow-2xl border-4 border-white"
					data-location-carousel>
					<!-- Gallery Carousel -->
					<div class="relative w-full h-full">
						<div class="flex transition-transform duration-500 ease-in-out h-full"
							data-location-carousel-track>
							<?php foreach ($gallery_images as $index => $image_url): ?>
								<div class="w-full h-full flex-shrink-0"
									data-location-slide="<?php echo esc_attr($index); ?>">
									<img src="<?php echo esc_url($image_url); ?>"
										alt="Chroma Moment <?php echo esc_attr($index + 1); ?>"
										class="w-full h-full object-cover" decoding="async" <?php if ($index === 0)
											echo 'fetchpriority="high"';
										else
											echo 'loading="lazy"'; ?> />
								</div>
							<?php endforeach; ?>
						</div>

						<?php if (count($gallery_images) > 1): ?>
							<!-- Navigation Arrows -->
							<button
								class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white/90 rounded-full shadow-lg text-brand-ink hover:bg-white transition"
								data-location-prev aria-label="Previous image">
								<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M15 19l-7-7 7-7" />
								</svg>
							</button>
							<button
								class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white/90 rounded-full shadow-lg text-brand-ink hover:bg-white transition"
								data-location-next aria-label="Next image">
								<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
										d="M9 5l7 7-7 7" />
								</svg>
							</button>

							<!-- Dots -->
							<div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2" data-location-dots>
								<?php foreach ($gallery_images as $index => $image_url): ?>
									<button
										class="w-2 h-2 rounded-full transition-all <?php echo 0 === $index ? 'bg-white w-6' : 'bg-white/50'; ?>"
										data-location-dot="<?php echo esc_attr($index); ?>"
										aria-label="Go to image <?php echo esc_attr($index + 1); ?>"></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>

		<!-- Nutrition & Menus -->
		<section id="nutrition" class="py-20 bg-brand-cream border-t border-brand-ink/5">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-12">
					<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
						<?php echo esc_html($nutrition_badge); ?>
					</span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-4">
						<?php echo esc_html($nutrition_title); ?>
					</h2>
					<p class="text-brand-ink/80 max-w-2xl mx-auto">
						<?php echo esc_html($nutrition_description); ?>
					</p>
				</div>

				<div class="grid md:grid-cols-2 gap-8 items-center">
					<!-- Menu Downloads -->
					<div class="bg-white p-8 rounded-[2rem] shadow-soft border border-brand-ink/5">
						<h3 class="font-bold text-xl text-brand-ink mb-6 flex items-center gap-3">
							<i class="fa-solid fa-utensils text-chroma-orange"></i> <?php _e('Monthly Menus', 'chroma-excellence'); ?>
						</h3>
						<div class="space-y-4">
							<?php foreach ($menus as $index => $menu): ?>
								<button type="button"
									class="chroma-pdf-trigger w-full flex items-center justify-between p-4 rounded-xl bg-brand-cream hover:bg-<?php echo esc_attr($menu['bgClass']); ?> transition-colors group text-left"
									data-pdf-url="<?php echo esc_url($menu['url']); ?>"
									data-pdf-title="<?php echo esc_attr($menu['title']); ?>">
									<div class="flex items-center gap-4">
										<div
											class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-<?php echo esc_attr($menu['color']); ?> shadow-sm">
											<i class="<?php echo esc_attr($menu['icon']); ?>"></i>
										</div>
										<div>
											<p class="font-bold text-brand-ink"><?php echo esc_html($menu['title']); ?>
											</p>
											<p class="text-xs text-brand-ink">
												<?php echo esc_html($menu['subtitle']); ?>
											</p>
										</div>
									</div>
									<i
										class="fa-solid fa-eye text-brand-ink/20 group-hover:text-<?php echo esc_attr($menu['color']); ?>"></i>
								</button>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Image -->
					<div class="relative h-[400px] rounded-[2rem] overflow-hidden shadow-card">
						<img src="<?php echo esc_url($nutrition_image); ?>" class="w-full h-full object-cover"
							alt="<?php echo esc_attr($nutrition_title); ?>" />
						<div
							class="absolute bottom-4 left-4 bg-white/90 backdrop-blur px-4 py-2 rounded-xl text-xs font-bold text-brand-ink shadow-sm">
							<i class="fa-solid fa-check-circle text-chroma-green mr-1"></i> <?php _e('Fresh Fruit Daily', 'chroma-excellence'); ?>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Safety & Communication -->
		<section id="safety" class="py-24 bg-chroma-blueDark text-white">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-16">
					<h2 class="text-3xl md:text-4xl font-serif font-bold mb-4">
						<?php echo esc_html($safety_title); ?>
					</h2>
					<p class="text-white/90 max-w-2xl mx-auto">
						<?php echo esc_html($safety_description); ?>
					</p>
				</div>

				<div class="grid md:grid-cols-3 gap-8">
					<?php foreach ($safety_items as $item): ?>
						<div class="bg-white/5 p-8 rounded-3xl border border-white/10">
							<div class="text-4xl mb-4 text-<?php echo esc_attr($item['color']); ?>">
								<i class="<?php echo esc_attr($item['icon']); ?>"></i>
							</div>
							<h3 class="font-bold text-xl mb-3">
								<?php echo esc_html($item['title']); ?>
							</h3>
							<p class="text-sm text-white/90 leading-relaxed">
								<?php echo esc_html($item['desc']); ?>
							</p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Operational FAQ -->
		<section class="py-20 bg-white">
			<div class="max-w-4xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-12">
					<h2 class="text-3xl font-serif font-bold text-brand-ink">
						<?php echo esc_html($faq_title); ?>
					</h2>
					<p class="text-brand-ink/80 mt-2">
						<?php echo esc_html($faq_description); ?>
					</p>
				</div>

				<div class="space-y-4">
					<?php foreach ($faqs as $faq): ?>
						<details class="group bg-brand-cream rounded-2xl p-5 border border-brand-ink/5 cursor-pointer">
							<summary class="flex items-center justify-between font-bold text-brand-ink list-none">
								<span><?php echo esc_html($faq['question']); ?></span>
								<span class="text-chroma-blue group-open:rotate-180 transition-transform">
									<i class="fa-solid fa-chevron-down"></i>
								</span>
							</summary>
							<p class="mt-3 text-sm text-brand-ink/80 leading-relaxed">
								<?php echo esc_html($faq['answer']); ?>
							</p>
						</details>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Referral Banner -->
		<section class="py-16 bg-brand-cream px-4">
			<div
				class="max-w-5xl mx-auto bg-gradient-to-r from-chroma-red to-chroma-yellow rounded-[2.5rem] p-8 md:p-12 relative overflow-hidden shadow-lg text-white flex flex-col md:flex-row items-center justify-between gap-8">
				<div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-16 -mt-16"></div>
				<div class="relative z-10">
					<h2 class="text-3xl md:text-4xl font-serif font-bold mb-2">
						<?php echo esc_html($referral_title); ?>
					</h2>
					<p class="text-white/90 text-lg">
						<?php echo wp_kses_post($referral_description); ?>
					</p>
				</div>
				<a href="<?php echo esc_url($referral_button_url); ?>"
					class="relative z-10 bg-white text-brand-ink font-bold uppercase tracking-widest text-xs px-8 py-4 rounded-full hover:bg-brand-ink hover:text-white transition-colors shadow-md">
					<?php echo esc_html($referral_button_text); ?>
				</a>
			</div>
		</section>

	</article>
</main>

<?php
get_footer();
?>
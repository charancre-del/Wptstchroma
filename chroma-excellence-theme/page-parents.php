<?php
/**
 * Template Name: Parents Page
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

$page_id = get_the_ID();
$has_usable_link = static function ($url) {
	$url = trim((string) $url);
	return $url !== '' && $url !== '#';
};
$neutralize_family_app_name = static function ($text) {
	return preg_replace('/\b(?:Procare(?: Cloud)?|Brightwheel)\b/i', __('the family communication app', 'chroma-excellence'), (string) $text);
};

// Hero Section
$hero_badge = chroma_get_translated_meta($page_id, 'parents_hero_badge') ?: __('Parent Dashboard', 'chroma-excellence');
$hero_title = chroma_get_translated_meta($page_id, 'parents_hero_title') ?: __('Partners in your child\'s journey.', 'chroma-excellence');
$hero_description = chroma_get_translated_meta($page_id, 'parents_hero_description') ?: __('Everything you need to manage your enrollment, stay connected, and engage with the Chroma community.', 'chroma-excellence');

// Parent Essentials Section
$essentials_title = chroma_get_translated_meta($page_id, 'parents_essentials_title') ?: __('Parent Essentials', 'chroma-excellence');
$essentials_heading = chroma_get_translated_meta($page_id, 'parents_essentials_heading') ?: __('Your quick links.', 'chroma-excellence');
$family_app_title = chroma_get_translated_meta($page_id, 'parents_resource_procare_title') ?: __('Family Communication App', 'chroma-excellence');
if (preg_match('/\b(?:Procare|Brightwheel)\b/i', $family_app_title)) {
	$family_app_title = __('Family Communication App', 'chroma-excellence');
}
$family_app_description = $neutralize_family_app_name(chroma_get_translated_meta($page_id, 'parents_resource_procare_desc') ?: __('Daily reports, photos, and attendance updates in your campus communication app.', 'chroma-excellence'));

$resources = array(
	array(
		'name' => 'procare',
		'icon' => chroma_get_translated_meta($page_id, 'parents_resource_procare_icon') ?: 'fa-solid fa-cloud',
		'title' => $family_app_title,
		'description' => $family_app_description,
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
		'name' => 'prek',
		'icon' => chroma_get_translated_meta($page_id, 'parents_resource_prek_icon') ?: 'fa-solid fa-apple-whole',
		'title' => chroma_get_translated_meta($page_id, 'parents_resource_prek_title') ?: __('GA Pre-K Enrollment', 'chroma-excellence'),
		'description' => chroma_get_translated_meta($page_id, 'parents_resource_prek_desc') ?: __('Lottery registration and required state forms.', 'chroma-excellence'),
		'url' => chroma_get_translated_meta($page_id, 'parents_resource_prek_url') ?: home_url('/programs/ga-pre-k/'),
		'colorClass' => 'chroma-red',
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
$family_updates_description = $neutralize_family_app_name(chroma_get_translated_meta($page_id, 'parents_safety2_desc') ?: __('Your campus communication app can share daily updates such as meals, naps, classroom moments, and photos, based on the tools used by your campus.', 'chroma-excellence'));

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
		'desc' => $family_updates_description,
	),
	array(
		'icon' => chroma_get_translated_meta($page_id, 'parents_safety3_icon') ?: 'fa-solid fa-lock',
		'color' => 'chroma-red',
		'title' => chroma_get_translated_meta($page_id, 'parents_safety3_title') ?: __('Secure Access Control', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'parents_safety3_desc') ?: __('Our lobbies are secured with coded keypad entry systems. Codes are unique to each family and change regularly. ID is strictly required for any alternative pickups.', 'chroma-excellence'),
	),
);

// FAQ Section
$faq_title = chroma_get_translated_meta($page_id, 'parents_faq_title') ?: __('Quick answers for day-to-day life.', 'chroma-excellence');
$faq_description = chroma_get_translated_meta($page_id, 'parents_faq_description') ?: __('Quick answers to common day-to-day questions.', 'chroma-excellence');
$late_pickup_answer = chroma_get_translated_meta($page_id, 'parents_faq3_answer') ?: __('Operating hours and late-pickup policies vary by campus. Please review your enrollment agreement or contact your campus Director for the current schedule and applicable fees.', 'chroma-excellence');
if (false !== stripos($late_pickup_answer, 'close promptly at 6:00 PM') || false !== stripos($late_pickup_answer, '$1 per minute')) {
	$late_pickup_answer = __('Operating hours and late-pickup policies vary by campus. Please review your enrollment agreement or contact your campus Director for the current schedule and applicable fees.', 'chroma-excellence');
}

$weather_answer = $neutralize_family_app_name(chroma_get_translated_meta($page_id, 'parents_faq2_answer') ?: __('Weather decisions are made with staff and family safety in mind. Updates are shared through the family communication app and other campus communication channels.', 'chroma-excellence'));

$faqs = array(
	array(
		'question' => chroma_get_translated_meta($page_id, 'parents_faq1_question') ?: __('What is the sick child policy?', 'chroma-excellence'),
		'answer' => chroma_get_translated_meta($page_id, 'parents_faq1_answer') ?: __('Children must be symptom-free (fever under 100.4°F, no vomiting/diarrhea) for 24 hours without medication before returning to school. Please report any contagious illnesses to the Director immediately.', 'chroma-excellence'),
	),
	array(
		'question' => chroma_get_translated_meta($page_id, 'parents_faq2_question') ?: __('How do you handle inclement weather?', 'chroma-excellence'),
		'answer' => $weather_answer,
	),
	array(
		'question' => chroma_get_translated_meta($page_id, 'parents_faq3_question') ?: __('What is the late pickup policy?', 'chroma-excellence'),
		'answer' => $late_pickup_answer,
	),
);

// Referral Banner
$referral_title = chroma_get_translated_meta($page_id, 'parents_referral_title') ?: __('Love the Chroma family?', 'chroma-excellence');
$referral_description = chroma_get_translated_meta($page_id, 'parents_referral_description') ?: __('Refer a friend and receive a <strong>$100 account credit</strong> when they enroll.', 'chroma-excellence');
if (false !== stripos($referral_description, 'tuition credit')) {
	$referral_description = str_ireplace('tuition credit', 'account credit', $referral_description);
}
$referral_button_text = chroma_get_translated_meta($page_id, 'parents_referral_button_text') ?: __('Refer a Friend', 'chroma-excellence');
$referral_button_url = chroma_get_translated_meta($page_id, 'parents_referral_button_url') ?: 'mailto:director@chromaela.com?subject=Parent%20Referral';


$gallery_raw = chroma_get_translated_meta($page_id, 'parents_moments_gallery');
$gallery_images = array();
if (!empty($gallery_raw)) {
	foreach (preg_split('/[
,]+/', (string) $gallery_raw) as $line) {
		$url = trim($line);
		if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
			$gallery_images[] = esc_url($url);
		}
	}
	$gallery_images = array_values(array_unique(array_filter($gallery_images)));
}


$has_parents_pdf_viewer = false;
if ($has_usable_link(chroma_get_translated_meta($page_id, 'parents_resource_handbook_url') ?: '#')) {
	$has_parents_pdf_viewer = true;
}

foreach ($menus as $menu) {
	if ($has_usable_link($menu['url'])) {
		$has_parents_pdf_viewer = true;
		break;
	}
}

if ($has_parents_pdf_viewer && function_exists('chroma_enqueue_pdf_assets')) {
	chroma_enqueue_pdf_assets();
}

get_header();
?>

<main id="primary" class="site-main chroma-parents-v2" role="main">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<!-- Hero -->
		<section class="pageHero chroma-v2-page-hero relative overflow-hidden bg-brand-cream border-b border-brand-ink/5">
			<div class="absolute inset-0 opacity-80 bg-[linear-gradient(rgba(38,50,56,0.035)_1px,transparent_1px),linear-gradient(90deg,rgba(38,50,56,0.035)_1px,transparent_1px)] bg-[size:72px_72px]"></div>
			<div class="absolute -left-20 bottom-0 h-72 w-72 rounded-full bg-chroma-redLight/70 blur-3xl"></div>
			<div class="absolute right-0 top-0 h-96 w-96 rounded-full bg-chroma-blueLight/60 blur-3xl"></div>
			<div class="relative max-w-7xl mx-auto px-4 lg:px-6 py-16 md:py-24">
				<div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-brand-ink/50 mb-7">
					<a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-chroma-red transition"><?php esc_html_e('Home', 'chroma-excellence'); ?></a>
					<span aria-hidden="true">&middot;</span>
					<span><?php esc_html_e('Parents', 'chroma-excellence'); ?></span>
				</div>
				<div class="max-w-4xl">
					<div class="inline-flex items-center gap-2 bg-white border border-chroma-red/20 px-4 py-2 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-brand-ink shadow-sm mb-7">
						<span class="w-2 h-2 rounded-full bg-chroma-red" aria-hidden="true"></span>
						<?php echo esc_html($hero_badge); ?>
					</div>
					<h1 class="font-serif text-5xl md:text-7xl lg:text-8xl font-semibold tracking-[-0.045em] leading-[0.94] text-brand-ink mb-7">
						<?php
						$parents_title = (string) $hero_title;
						if (stripos($parents_title, "child's journey") !== false) {
							echo wp_kses_post(str_ireplace("child's journey", '<em class="block text-chroma-red">child\'s journey</em>', esc_html($parents_title)));
						} else {
							echo esc_html($parents_title);
						}
						?>
					</h1>
					<p class="text-lg md:text-xl text-brand-ink/75 leading-relaxed max-w-3xl">
						<?php echo esc_html($hero_description); ?>
					</p>
				</div>
			</div>
		</section>

		<!-- Resources Grid (Quick Links) -->
		<section id="resources" class="white borderY py-20 bg-white border-y border-chroma-blue/10">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-16">
					<span class="text-chroma-purple font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
						<?php echo esc_html($essentials_title); ?>
					</span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink">
						<?php echo esc_html($essentials_heading); ?>
					</h2>
				</div>

				<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-6">
					<?php foreach ($resources as $resource): 
						$is_pdf = in_array($resource['name'], array('handbook'));
						$is_external_portal = in_array($resource['name'], array('procare', 'tuition', 'enrollment', 'prekga', 'waitlist'));
						$resource_has_url = $has_usable_link($resource['url']);
						
						$link_class = 'chroma-parent-resource-card bg-white p-8 rounded-[2rem] shadow-card transition-transform group border border-brand-ink/5 flex flex-col items-center text-center';
						$attrs = '';
						
						if ($resource_has_url) {
							$link_class .= ' hover:-translate-y-1';
							if ($is_pdf) {
								$link_class .= ' chroma-pdf-trigger';
								$attrs = 'data-pdf-url="' . esc_url($resource['url']) . '" data-pdf-title="' . esc_attr($resource['title']) . '"';
							} elseif ($is_external_portal) {
								$attrs = 'target="_blank" rel="noopener noreferrer"';
							}
						} else {
							$link_class .= ' opacity-60 cursor-default';
						}
					?>
						<<?php echo $resource_has_url ? 'a' : 'div'; ?>
							<?php if ($resource_has_url): ?>href="<?php echo esc_url($resource['url']); ?>"<?php endif; ?>
							class="<?php echo esc_attr($link_class); ?>"
							<?php if ($attrs): ?><?php echo $attrs; ?><?php endif; ?>
							<?php if (!$resource_has_url): ?>aria-disabled="true"<?php endif; ?>>
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
						</<?php echo $resource_has_url ? 'a' : 'div'; ?>>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Growth Support -->
		<section class="chroma-parent-growth-section cream py-20 md:py-24 bg-brand-cream border-t border-brand-ink/5">
			<div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-[0.95fr_1.05fr] gap-10 lg:gap-14 items-center">
				<div class="chroma-parent-growth-copy">
					<span class="inline-flex items-center gap-2 bg-white border border-chroma-red/15 px-4 py-2 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-chroma-red mb-7">
						<span class="w-2 h-2 rounded-full bg-chroma-red" aria-hidden="true"></span>
						<?php esc_html_e('How we support growth', 'chroma-excellence'); ?>
					</span>
					<h2 class="font-serif text-4xl md:text-6xl font-semibold tracking-[-0.04em] leading-[0.98] text-brand-ink mb-6">
						<?php esc_html_e('We notice the little patterns that help children grow.', 'chroma-excellence'); ?>
					</h2>
					<p class="text-lg text-brand-ink/75 leading-relaxed mb-5">
						<?php esc_html_e('Families want to know their child is understood. Our teachers pay attention to the small signals that show up across the day — how children enter play, try new language, handle routines, build friendships, and ask for help.', 'chroma-excellence'); ?>
					</p>
					<p class="text-brand-ink/65 leading-relaxed">
						<?php esc_html_e('Those observations help teachers plan with intention, so support feels calm, personal, and developmentally right.', 'chroma-excellence'); ?>
					</p>
				</div>
				<div class="chroma-parent-growth-cards">
					<article>
						<small><?php esc_html_e('Notice', 'chroma-excellence'); ?></small>
						<h3><?php esc_html_e('Seen clearly.', 'chroma-excellence'); ?></h3>
						<p><?php esc_html_e('Teachers notice where your child is growing, what feels easy, and where a little more support may help.', 'chroma-excellence'); ?></p>
					</article>
					<article>
						<small><?php esc_html_e('Plan', 'chroma-excellence'); ?></small>
						<h3><?php esc_html_e('Planned gently.', 'chroma-excellence'); ?></h3>
						<p><?php esc_html_e('Classroom plans are shaped around the children in the room, so learning feels age-appropriate and responsive.', 'chroma-excellence'); ?></p>
					</article>
					<article>
						<small><?php esc_html_e('Support', 'chroma-excellence'); ?></small>
						<h3><?php esc_html_e('Supported well.', 'chroma-excellence'); ?></h3>
						<p><?php esc_html_e('Directors and curriculum leaders help teachers choose next steps, materials, or coaching that fit the classroom.', 'chroma-excellence'); ?></p>
					</article>
				</div>
			</div>
		</section>

		<!-- Events Section -->
		<section id="events" class="cream py-24 bg-brand-cream relative overflow-hidden border-t border-brand-ink/5 chroma-parents-split-section chroma-parents-events">
			<div
				class="absolute top-0 right-0 w-1/2 h-full bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-chroma-yellowLight/50 via-transparent to-transparent">
			</div>
			<div class="max-w-6xl mx-auto px-4 lg:px-6 relative z-10">
				<div class="grid md:grid-cols-2 gap-16 items-center chroma-parents-split">
					<div>
						<span class="text-chroma-yellow font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
							<?php echo esc_html($events_badge); ?>
						</span>
						<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
							<?php echo esc_html(rtrim($events_title, '.') . '.'); ?>
						</h2>
						<p class="text-brand-ink/80 mb-8 text-lg">
							<?php echo esc_html($events_description); ?>
						</p>

						<div class="space-y-8 chroma-parents-checklist">
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
						class="relative h-[500px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-brand-cream rotate-2 chroma-parents-split-image">
						<img src="<?php echo esc_url($events_image); ?>" class="w-full h-full object-cover"
							alt="<?php echo esc_attr($events_title); ?>" />
					</div>
				</div>
			</div>
		</section>


		<?php if (!empty($gallery_images)): ?>
			<!-- Moments Gallery -->
			<section id="moments" class="white borderY py-20 bg-white border-y border-chroma-blue/10">
				<div class="max-w-7xl mx-auto px-4 lg:px-6">
					<div class="text-center mb-12">
						<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php esc_html_e('Campus Moments', 'chroma-excellence'); ?></span>
						<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink"><?php esc_html_e('Moments of Joy.', 'chroma-excellence'); ?></h2>
					</div>
					<div class="chroma-moments-carousel" data-moments-carousel role="region" aria-live="polite" aria-label="<?php esc_attr_e('Chroma campus moments', 'chroma-excellence'); ?>">
						<div class="chroma-moments-track" data-moments-track>
							<?php foreach ($gallery_images as $index => $image_url): ?>
								<?php
								$attachment_id = attachment_url_to_postid($image_url);
								$image_alt = $attachment_id ? trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true)) : '';
								if ('' === $image_alt) {
									$image_alt = sprintf(__('Children and families enjoying a Chroma campus moment, image %d', 'chroma-excellence'), $index + 1);
								}
								?>
								<figure class="chroma-moments-slide" data-moments-slide="<?php echo esc_attr($index); ?>"
									role="group" aria-roledescription="<?php esc_attr_e('slide', 'chroma-excellence'); ?>"
									aria-label="<?php echo esc_attr(sprintf(__('Campus moment %1$d of %2$d', 'chroma-excellence'), $index + 1, count($gallery_images))); ?>"
									aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>" <?php echo 0 === $index ? '' : 'inert'; ?>>
									<img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>" loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>" onerror="this.closest('[data-moments-slide]')?.remove();" />
								</figure>
							<?php endforeach; ?>
						</div>
						<?php if (count($gallery_images) > 1): ?>
							<button type="button" class="chroma-moments-arrow chroma-moments-arrow--prev" data-moments-prev aria-label="<?php esc_attr_e('Previous campus moment', 'chroma-excellence'); ?>">
								<i class="fa-solid fa-chevron-left"></i>
							</button>
							<button type="button" class="chroma-moments-arrow chroma-moments-arrow--next" data-moments-next aria-label="<?php esc_attr_e('Next campus moment', 'chroma-excellence'); ?>">
								<i class="fa-solid fa-chevron-right"></i>
							</button>
							<div class="chroma-moments-dots" data-moments-dots>
								<?php foreach ($gallery_images as $index => $image_url): ?>
									<button type="button" data-moments-dot="<?php echo esc_attr($index); ?>" aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr(sprintf(__('Go to campus moment %d', 'chroma-excellence'), $index + 1)); ?>"></button>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- Nutrition & Menus -->
		<section id="nutrition" class="cream py-20 bg-brand-cream chroma-parents-split-section chroma-parents-nutrition">
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

				<div class="grid md:grid-cols-2 gap-8 items-center chroma-parents-split">
					<!-- Menu Downloads -->
					<div class="bg-white p-8 rounded-[2rem] shadow-soft border border-brand-ink/5">
						<h3 class="font-bold text-xl text-brand-ink mb-6 flex items-center gap-3">
							<i class="fa-solid fa-utensils text-chroma-orange"></i> <?php _e('Monthly Menus', 'chroma-excellence'); ?>
						</h3>
						<div class="space-y-4">
							<?php foreach ($menus as $index => $menu): ?>
								<?php $menu_has_url = $has_usable_link($menu['url']); ?>
								<<?php echo $menu_has_url ? 'button' : 'div'; ?>
									<?php if ($menu_has_url): ?>type="button" data-pdf-url="<?php echo esc_url($menu['url']); ?>" data-pdf-title="<?php echo esc_attr($menu['title']); ?>"<?php endif; ?>
									class="w-full flex items-center justify-between p-4 rounded-xl bg-brand-cream transition-colors group text-left <?php echo $menu_has_url ? 'chroma-pdf-trigger hover:bg-' . esc_attr($menu['bgClass']) : 'opacity-60 cursor-default'; ?>"
									<?php if (!$menu_has_url): ?>aria-disabled="true"<?php endif; ?>>
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
								</<?php echo $menu_has_url ? 'button' : 'div'; ?>>
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
		<section id="safety" class="white borderY py-24 bg-white border-y border-chroma-blue/10">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-16">
					<h2 class="text-3xl md:text-4xl font-serif font-bold mb-4 text-brand-ink">
						<?php echo esc_html($safety_title); ?>
					</h2>
					<p class="text-brand-ink/70 max-w-2xl mx-auto">
						<?php echo esc_html($safety_description); ?>
					</p>
				</div>

				<div class="grid md:grid-cols-3 gap-8">
					<?php foreach ($safety_items as $item): ?>
						<div class="bg-white p-8 rounded-[2rem] border border-chroma-blue/10 shadow-soft">
							<div class="text-4xl mb-4 text-<?php echo esc_attr($item['color']); ?>">
								<i class="<?php echo esc_attr($item['icon']); ?>"></i>
							</div>
							<h3 class="font-bold text-xl mb-3 text-brand-ink">
								<?php echo esc_html($item['title']); ?>
							</h3>
							<p class="text-sm text-brand-ink/70 leading-relaxed">
								<?php echo esc_html($item['desc']); ?>
							</p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Operational FAQ -->
		<section class="cream py-20 bg-brand-cream">
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
						<details class="group bg-white rounded-2xl p-5 border border-brand-ink/5 cursor-pointer shadow-sm">
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
		<section class="white borderY py-16 bg-white border-y border-chroma-blue/10 px-4">
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

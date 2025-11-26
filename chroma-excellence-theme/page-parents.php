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
$hero_badge       = get_post_meta( $page_id, 'parents_hero_badge', true ) ?: 'Parent Dashboard';
$hero_title       = get_post_meta( $page_id, 'parents_hero_title', true ) ?: 'Partners in your child\'s journey.';
$hero_description = get_post_meta( $page_id, 'parents_hero_description', true ) ?: 'Everything you need to manage your enrollment, stay connected, and engage with the Chroma community.';

// Parent Essentials Section
$essentials_title = get_post_meta( $page_id, 'parents_essentials_title', true ) ?: 'Parent Essentials';

$resources = array(
	array(
		'name'        => 'procare',
		'icon'        => get_post_meta( $page_id, 'parents_resource_procare_icon', true ) ?: 'fa-solid fa-cloud',
		'title'       => get_post_meta( $page_id, 'parents_resource_procare_title', true ) ?: 'Procare Cloud',
		'description' => get_post_meta( $page_id, 'parents_resource_procare_desc', true ) ?: 'Daily reports, photos, and attendance tracking.',
		'url'         => get_post_meta( $page_id, 'parents_resource_procare_url', true ) ?: '#',
		'colorClass'  => 'chroma-blue',
	),
	array(
		'name'        => 'tuition',
		'icon'        => get_post_meta( $page_id, 'parents_resource_tuition_icon', true ) ?: 'fa-solid fa-credit-card',
		'title'       => get_post_meta( $page_id, 'parents_resource_tuition_title', true ) ?: 'Tuition Portal',
		'description' => get_post_meta( $page_id, 'parents_resource_tuition_desc', true ) ?: 'Securely view statements and make payments.',
		'url'         => get_post_meta( $page_id, 'parents_resource_tuition_url', true ) ?: '#',
		'colorClass'  => 'chroma-green',
	),
	array(
		'name'        => 'handbook',
		'icon'        => get_post_meta( $page_id, 'parents_resource_handbook_icon', true ) ?: 'fa-solid fa-book-open',
		'title'       => get_post_meta( $page_id, 'parents_resource_handbook_title', true ) ?: 'Parent Handbook',
		'description' => get_post_meta( $page_id, 'parents_resource_handbook_desc', true ) ?: 'Policies, procedures, and operational details.',
		'url'         => get_post_meta( $page_id, 'parents_resource_handbook_url', true ) ?: '#',
		'colorClass'  => 'chroma-yellow',
	),
	array(
		'name'        => 'enrollment',
		'icon'        => get_post_meta( $page_id, 'parents_resource_enrollment_icon', true ) ?: 'fa-solid fa-file-signature',
		'title'       => get_post_meta( $page_id, 'parents_resource_enrollment_title', true ) ?: 'Enrollment Agreement',
		'description' => get_post_meta( $page_id, 'parents_resource_enrollment_desc', true ) ?: 'Update your annual enrollment documents.',
		'url'         => get_post_meta( $page_id, 'parents_resource_enrollment_url', true ) ?: '#',
		'colorClass'  => 'chroma-red',
	),
	array(
		'name'        => 'prekga',
		'icon'        => get_post_meta( $page_id, 'parents_resource_prekga_icon', true ) ?: 'fa-solid fa-graduation-cap',
		'title'       => get_post_meta( $page_id, 'parents_resource_prekga_title', true ) ?: 'GA Pre-K Enrollment',
		'description' => get_post_meta( $page_id, 'parents_resource_prekga_desc', true ) ?: 'Lottery registration and required state forms.',
		'url'         => get_post_meta( $page_id, 'parents_resource_prekga_url', true ) ?: '#',
		'colorClass'  => 'brand-ink',
	),
	array(
		'name'        => 'waitlist',
		'icon'        => get_post_meta( $page_id, 'parents_resource_waitlist_icon', true ) ?: 'fa-solid fa-clock',
		'title'       => get_post_meta( $page_id, 'parents_resource_waitlist_title', true ) ?: 'Join Waitlist',
		'description' => get_post_meta( $page_id, 'parents_resource_waitlist_desc', true ) ?: 'Reserve a spot for siblings or future terms.',
		'url'         => get_post_meta( $page_id, 'parents_resource_waitlist_url', true ) ?: '#',
		'colorClass'  => 'brand-ink',
	),
);

// Events Section
$events_badge       = get_post_meta( $page_id, 'parents_events_badge', true ) ?: 'Community';
$events_title       = get_post_meta( $page_id, 'parents_events_title', true ) ?: 'Traditions & Celebrations';
$events_description = get_post_meta( $page_id, 'parents_events_description', true ) ?: 'We believe in building a village. Our calendar is peppered with events designed to bring families together and celebrate our students\' milestones.';
$events_image       = get_post_meta( $page_id, 'parents_events_image', true ) ?: 'https://images.unsplash.com/photo-1511895426328-dc8714191300?q=80&w=800&auto=format&fit=crop';

$events = array(
	array(
		'icon'  => get_post_meta( $page_id, 'parents_event1_icon', true ) ?: 'fa-solid fa-calendar-days',
		'color' => 'chroma-yellow',
		'title' => get_post_meta( $page_id, 'parents_event1_title', true ) ?: 'Quarterly Family Events',
		'desc'  => get_post_meta( $page_id, 'parents_event1_desc', true ) ?: 'Every season brings a reason to gather. From our Fall Festival and Winter "Cookies & Cocoa" to our Spring Art Show and Summer Splash Days, we create memories for the whole family.',
	),
	array(
		'icon'  => get_post_meta( $page_id, 'parents_event2_icon', true ) ?: 'fa-solid fa-star',
		'color' => 'chroma-red',
		'title' => get_post_meta( $page_id, 'parents_event2_title', true ) ?: 'Pre-K Graduation',
		'desc'  => get_post_meta( $page_id, 'parents_event2_desc', true ) ?: 'A cap-and-gown ceremony celebrating our 4 and 5-year-olds as they transition to Kindergarten. It\'s the highlight of our academic year!',
	),
	array(
		'icon'  => get_post_meta( $page_id, 'parents_event3_icon', true ) ?: 'fa-solid fa-handshake',
		'color' => 'chroma-green',
		'title' => get_post_meta( $page_id, 'parents_event3_title', true ) ?: 'Parent-Teacher Conferences',
		'desc'  => get_post_meta( $page_id, 'parents_event3_desc', true ) ?: 'Twice a year, we sit down to review your child\'s developmental portfolio, set goals, and celebrate their individual growth curve.',
	),
);

// Nutrition Section
$nutrition_badge       = get_post_meta( $page_id, 'parents_nutrition_badge', true ) ?: 'Wellness';
$nutrition_title       = get_post_meta( $page_id, 'parents_nutrition_title', true ) ?: 'What\'s for lunch?';
$nutrition_description = get_post_meta( $page_id, 'parents_nutrition_description', true ) ?: 'Our in-house chefs prepare balanced, CACFP-compliant meals fresh daily. We are a nut-aware facility.';
$nutrition_image       = get_post_meta( $page_id, 'parents_nutrition_image', true ) ?: 'https://images.unsplash.com/photo-1564834724105-918b73d1b9e0?q=80&w=800&auto=format&fit=crop';

$menus = array(
	array(
		'icon'     => get_post_meta( $page_id, 'parents_menu1_icon', true ) ?: 'fa-solid fa-carrot',
		'color'    => 'chroma-green',
		'bgClass'  => 'chroma-greenLight',
		'title'    => get_post_meta( $page_id, 'parents_menu1_title', true ) ?: 'Current Month Menu',
		'subtitle' => get_post_meta( $page_id, 'parents_menu1_subtitle', true ) ?: 'Standard (Ages 1-12)',
		'url'      => get_post_meta( $page_id, 'parents_menu1_url', true ) ?: '#',
	),
	array(
		'icon'     => get_post_meta( $page_id, 'parents_menu2_icon', true ) ?: 'fa-solid fa-baby',
		'color'    => 'chroma-blue',
		'bgClass'  => 'chroma-blueLight',
		'title'    => get_post_meta( $page_id, 'parents_menu2_title', true ) ?: 'Infant Puree Menu',
		'subtitle' => get_post_meta( $page_id, 'parents_menu2_subtitle', true ) ?: 'Stage 1 & 2 Solids',
		'url'      => get_post_meta( $page_id, 'parents_menu2_url', true ) ?: '#',
	),
	array(
		'icon'     => get_post_meta( $page_id, 'parents_menu3_icon', true ) ?: 'fa-solid fa-wheat-awn-circle-exclamation',
		'color'    => 'chroma-red',
		'bgClass'  => 'chroma-redLight',
		'title'    => get_post_meta( $page_id, 'parents_menu3_title', true ) ?: 'Allergy Statement',
		'subtitle' => get_post_meta( $page_id, 'parents_menu3_subtitle', true ) ?: 'Our Nut-Free Protocols',
		'url'      => get_post_meta( $page_id, 'parents_menu3_url', true ) ?: '#',
	),
);

// Safety Section
$safety_title       = get_post_meta( $page_id, 'parents_safety_title', true ) ?: 'Safe. Secure. Connected.';
$safety_description = get_post_meta( $page_id, 'parents_safety_description', true ) ?: 'We employ enterprise-grade security measures and transparent communication protocols so you can have total peace of mind while you work.';

$safety_items = array(
	array(
		'icon'  => get_post_meta( $page_id, 'parents_safety1_icon', true ) ?: 'fa-solid fa-video',
		'color' => 'chroma-green',
		'title' => get_post_meta( $page_id, 'parents_safety1_title', true ) ?: '24/7 Monitored Cameras',
		'desc'  => get_post_meta( $page_id, 'parents_safety1_desc', true ) ?: 'Our facilities are equipped with high-definition closed-circuit cameras in every classroom, hallway, and playground. Feeds are monitored by leadership to ensure policy adherence and safety.',
	),
	array(
		'icon'  => get_post_meta( $page_id, 'parents_safety2_icon', true ) ?: 'fa-solid fa-mobile-screen-button',
		'color' => 'chroma-blue',
		'title' => get_post_meta( $page_id, 'parents_safety2_title', true ) ?: 'Real-Time Updates',
		'desc'  => get_post_meta( $page_id, 'parents_safety2_desc', true ) ?: 'Through the Procare app, you receive real-time notifications for meals, naps, and diaper changes, plus photos of your child engaging in the curriculum throughout the day.',
	),
	array(
		'icon'  => get_post_meta( $page_id, 'parents_safety3_icon', true ) ?: 'fa-solid fa-lock',
		'color' => 'chroma-red',
		'title' => get_post_meta( $page_id, 'parents_safety3_title', true ) ?: 'Secure Access Control',
		'desc'  => get_post_meta( $page_id, 'parents_safety3_desc', true ) ?: 'Our lobbies are secured with coded keypad entry systems. Codes are unique to each family and change regularly. ID is strictly required for any alternative pickups.',
	),
);

// FAQ Section
$faq_title       = get_post_meta( $page_id, 'parents_faq_title', true ) ?: 'Operational Policy FAQ';
$faq_description = get_post_meta( $page_id, 'parents_faq_description', true ) ?: 'Quick answers to common day-to-day questions.';

$faqs = array(
	array(
		'question' => get_post_meta( $page_id, 'parents_faq1_question', true ) ?: 'What is the sick child policy?',
		'answer'   => get_post_meta( $page_id, 'parents_faq1_answer', true ) ?: 'Children must be symptom-free (fever under 100.4°F, no vomiting/diarrhea) for 24 hours without medication before returning to school. Please report any contagious illnesses to the Director immediately.',
	),
	array(
		'question' => get_post_meta( $page_id, 'parents_faq2_question', true ) ?: 'How do you handle inclement weather?',
		'answer'   => get_post_meta( $page_id, 'parents_faq2_answer', true ) ?: 'We generally follow the local county school system for weather closures, but we make independent decisions based on staff safety. Alerts will be sent via Procare and posted on our Facebook page by 6:00 AM.',
	),
	array(
		'question' => get_post_meta( $page_id, 'parents_faq3_question', true ) ?: 'What is the late pickup policy?',
		'answer'   => get_post_meta( $page_id, 'parents_faq3_answer', true ) ?: 'We close promptly at 6:00 PM. A late fee of $1 per minute is charged to your account for pickups after 6:05 PM to compensate our staff who stay late.',
	),
);

// Referral Banner
$referral_title       = get_post_meta( $page_id, 'parents_referral_title', true ) ?: 'Love the Chroma family?';
$referral_description = get_post_meta( $page_id, 'parents_referral_description', true ) ?: 'Refer a friend and receive a <strong>$100 tuition credit</strong> when they enroll.';
$referral_button_text = get_post_meta( $page_id, 'parents_referral_button_text', true ) ?: 'Refer a Friend';
$referral_button_url  = get_post_meta( $page_id, 'parents_referral_button_url', true ) ?: 'mailto:director@chromaela.com?subject=Parent%20Referral';
?>

<main id="primary" class="site-main" role="main">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<!-- Hero -->
		<section class="py-20 bg-white text-center border-b border-brand-ink/5">
			<div class="max-w-4xl mx-auto px-4">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
					<?php echo esc_html( $hero_badge ); ?>
				</span>
				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6">
					<?php echo esc_html( $hero_title ); ?>
				</h1>
				<p class="text-lg text-brand-ink/60">
					<?php echo esc_html( $hero_description ); ?>
				</p>
			</div>
		</section>

		<!-- Resources Grid (Quick Links) -->
		<section id="resources" class="py-24 bg-brand-cream">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-16">
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink">
						<?php echo esc_html( $essentials_title ); ?>
					</h2>
				</div>

				<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-6">
					<?php foreach ( $resources as $resource ) : ?>
					<a href="<?php echo esc_url( $resource['url'] ); ?>"
					   target="_blank"
					   class="bg-white p-8 rounded-[2rem] shadow-card hover:-translate-y-1 transition-transform group border border-brand-ink/5 flex flex-col items-center text-center">
						<div class="w-16 h-16 bg-<?php echo esc_attr( $resource['colorClass'] ); ?>/10 rounded-2xl flex items-center justify-center text-3xl mb-4 text-<?php echo esc_attr( $resource['colorClass'] ); ?> group-hover:bg-<?php echo esc_attr( $resource['colorClass'] ); ?> group-hover:text-white transition-colors">
							<i class="<?php echo esc_attr( $resource['icon'] ); ?>"></i>
						</div>
						<h3 class="font-bold text-lg text-brand-ink mb-2">
							<?php echo esc_html( $resource['title'] ); ?>
						</h3>
						<p class="text-xs text-brand-ink/60">
							<?php echo esc_html( $resource['description'] ); ?>
						</p>
					</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Events Section -->
		<section id="events" class="py-24 bg-white relative overflow-hidden">
			<div class="absolute top-0 right-0 w-1/2 h-full bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-chroma-yellowLight/50 via-transparent to-transparent"></div>
			<div class="max-w-6xl mx-auto px-4 lg:px-6 relative z-10">
				<div class="grid md:grid-cols-2 gap-16 items-center">
					<div>
						<span class="text-chroma-yellow font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
							<?php echo esc_html( $events_badge ); ?>
						</span>
						<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
							<?php echo esc_html( $events_title ); ?>
						</h2>
						<p class="text-brand-ink/70 mb-8 text-lg">
							<?php echo esc_html( $events_description ); ?>
						</p>

						<div class="space-y-8">
							<?php foreach ( $events as $event ) : ?>
							<div>
								<h3 class="font-bold text-xl text-brand-ink mb-2 flex items-center gap-2">
									<i class="<?php echo esc_attr( $event['icon'] ); ?> text-<?php echo esc_attr( $event['color'] ); ?>"></i>
									<?php echo esc_html( $event['title'] ); ?>
								</h3>
								<p class="text-sm text-brand-ink/60 leading-relaxed">
									<?php echo esc_html( $event['desc'] ); ?>
								</p>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="relative h-[500px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-brand-cream rotate-2">
						<img src="<?php echo esc_url( $events_image ); ?>"
							 class="w-full h-full object-cover"
							 alt="<?php echo esc_attr( $events_title ); ?>" />
					</div>
				</div>
			</div>
		</section>

		<!-- Nutrition & Menus -->
		<section id="nutrition" class="py-20 bg-brand-cream border-t border-brand-ink/5">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-12">
					<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
						<?php echo esc_html( $nutrition_badge ); ?>
					</span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-4">
						<?php echo esc_html( $nutrition_title ); ?>
					</h2>
					<p class="text-brand-ink/60 max-w-2xl mx-auto">
						<?php echo esc_html( $nutrition_description ); ?>
					</p>
				</div>

				<div class="grid md:grid-cols-2 gap-8 items-center">
					<!-- Menu Downloads -->
					<div class="bg-white p-8 rounded-[2rem] shadow-soft border border-brand-ink/5">
						<h3 class="font-bold text-xl text-brand-ink mb-6 flex items-center gap-3">
							<i class="fa-solid fa-utensils text-chroma-orange"></i> Monthly Menus
						</h3>
						<div class="space-y-4">
							<?php foreach ( $menus as $menu ) : ?>
							<a href="<?php echo esc_url( $menu['url'] ); ?>"
							   class="flex items-center justify-between p-4 rounded-xl bg-brand-cream hover:bg-<?php echo esc_attr( $menu['bgClass'] ); ?> transition-colors group">
								<div class="flex items-center gap-4">
									<div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-<?php echo esc_attr( $menu['color'] ); ?> shadow-sm">
										<i class="<?php echo esc_attr( $menu['icon'] ); ?>"></i>
									</div>
									<div>
										<p class="font-bold text-brand-ink"><?php echo esc_html( $menu['title'] ); ?></p>
										<p class="text-xs text-brand-ink/50"><?php echo esc_html( $menu['subtitle'] ); ?></p>
									</div>
								</div>
								<i class="fa-solid fa-download text-brand-ink/20 group-hover:text-<?php echo esc_attr( $menu['color'] ); ?>"></i>
							</a>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Image -->
					<div class="relative h-[400px] rounded-[2rem] overflow-hidden shadow-card">
						<img src="<?php echo esc_url( $nutrition_image ); ?>"
							 class="w-full h-full object-cover"
							 alt="<?php echo esc_attr( $nutrition_title ); ?>" />
						<div class="absolute bottom-4 left-4 bg-white/90 backdrop-blur px-4 py-2 rounded-xl text-xs font-bold text-brand-ink shadow-sm">
							<i class="fa-solid fa-check-circle text-chroma-green mr-1"></i> Fresh Fruit Daily
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
						<?php echo esc_html( $safety_title ); ?>
					</h2>
					<p class="text-white/60 max-w-2xl mx-auto">
						<?php echo esc_html( $safety_description ); ?>
					</p>
				</div>

				<div class="grid md:grid-cols-3 gap-8">
					<?php foreach ( $safety_items as $item ) : ?>
					<div class="bg-white/5 p-8 rounded-3xl border border-white/10">
						<div class="text-4xl mb-4 text-<?php echo esc_attr( $item['color'] ); ?>">
							<i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
						</div>
						<h3 class="font-bold text-xl mb-3">
							<?php echo esc_html( $item['title'] ); ?>
						</h3>
						<p class="text-sm text-white/60 leading-relaxed">
							<?php echo esc_html( $item['desc'] ); ?>
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
						<?php echo esc_html( $faq_title ); ?>
					</h2>
					<p class="text-brand-ink/60 mt-2">
						<?php echo esc_html( $faq_description ); ?>
					</p>
				</div>

				<div class="space-y-4">
					<?php foreach ( $faqs as $faq ) : ?>
					<details class="group bg-brand-cream rounded-2xl p-5 border border-brand-ink/5 cursor-pointer">
						<summary class="flex items-center justify-between font-bold text-brand-ink list-none">
							<span><?php echo esc_html( $faq['question'] ); ?></span>
							<span class="text-chroma-blue group-open:rotate-180 transition-transform">
								<i class="fa-solid fa-chevron-down"></i>
							</span>
						</summary>
						<p class="mt-3 text-sm text-brand-ink/70 leading-relaxed">
							<?php echo esc_html( $faq['answer'] ); ?>
						</p>
					</details>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Referral Banner -->
		<section class="py-16 bg-brand-cream px-4">
			<div class="max-w-5xl mx-auto bg-gradient-to-r from-chroma-red to-chroma-yellow rounded-[2.5rem] p-8 md:p-12 relative overflow-hidden shadow-lg text-white flex flex-col md:flex-row items-center justify-between gap-8">
				<div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full blur-3xl -mr-16 -mt-16"></div>
				<div class="relative z-10">
					<h2 class="text-3xl md:text-4xl font-serif font-bold mb-2">
						<?php echo esc_html( $referral_title ); ?>
					</h2>
					<p class="text-white/90 text-lg">
						<?php echo wp_kses_post( $referral_description ); ?>
					</p>
				</div>
				<a href="<?php echo esc_url( $referral_button_url ); ?>"
				   class="relative z-10 bg-white text-brand-ink font-bold uppercase tracking-widest text-xs px-8 py-4 rounded-full hover:bg-brand-ink hover:text-white transition-colors shadow-md">
					<?php echo esc_html( $referral_button_text ); ?>
				</a>
			</div>
		</section>

	</article>
</main>

<?php
get_footer();

<?php
/**
 * Template Name: Contact Page
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

get_header();

$page_id = get_the_ID();

// Hero Section
$hero_badge = get_post_meta($page_id, 'contact_hero_badge', true) ?: __('Get in Touch', 'chroma-excellence');
$hero_title = get_post_meta($page_id, 'contact_hero_title', true) ?: __('How can we support your family today?', 'chroma-excellence');
$hero_description = get_post_meta($page_id, 'contact_hero_description', true) ?: __('Whether you are looking for a new school, applying for a job, or have a media inquiry, we are here to connect you with the right team.', 'chroma-excellence');

// Form Settings
$form_submit_text = get_post_meta($page_id, 'contact_form_submit_text', true) ?: __('Send Message', 'chroma-excellence');

// Corporate Office
$corporate_title = get_post_meta($page_id, 'contact_corporate_title', true) ?: __('Corporate Office', 'chroma-excellence');
$corporate_name = get_post_meta($page_id, 'contact_corporate_name', true) ?: get_bloginfo('name');
$global_address = function_exists('chroma_global_full_address') ? chroma_global_full_address() : '';
$global_phone = function_exists('chroma_global_phone') ? chroma_global_phone() : '';
$global_email = function_exists('chroma_global_email') ? chroma_global_email() : '';
$global_phone = preg_match('/\d{3}/', (string) $global_phone) ? $global_phone : '';
$global_email = is_email($global_email) ? $global_email : '';
$corporate_address = get_post_meta($page_id, 'contact_corporate_address', true) ?: ($global_address ?: "3554 Old Milton Pkwy\nAlpharetta, GA 30005");
$contact_phone_meta = get_post_meta($page_id, 'contact_corporate_phone', true);
$contact_email_meta = get_post_meta($page_id, 'contact_corporate_email', true);
$corporate_phone = preg_match('/\d{3}/', (string) $contact_phone_meta) ? $contact_phone_meta : ($global_phone ?: '470-470-6589');
$corporate_email = is_email($contact_email_meta) ? $contact_email_meta : ($global_email ?: 'info@chromaela.com');

?>

<main>
	<!-- Hero Section (FCP Optimized - No Form) -->
	<section class="pageHero chroma-v2-page-hero relative bg-white pt-14 pb-12 lg:pt-16 lg:pb-14 overflow-hidden">
		<div
			class="absolute top-0 right-0 w-[500px] h-[500px] bg-chroma-greenLight rounded-full blur-[100px] opacity-40 translate-x-1/3 -translate-y-1/3">
		</div>
		<div
			class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-chroma-redLight rounded-full blur-[80px] opacity-40 -translate-x-1/3 translate-y-1/3">
		</div>

		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10">
			<div class="text-center max-w-3xl mx-auto mb-10">
				<span
					class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-4 block"><?php echo esc_html($hero_badge); ?></span>
				<h1 class="font-serif text-4xl md:text-6xl font-bold text-brand-ink mb-6">
					<?php echo esc_html($hero_title); ?></h1>
				<p class="text-lg text-brand-ink/70">
					<?php echo esc_html($hero_description); ?>
				</p>
			</div>

		</div>
	</section>

	<!-- Corporate Info & Departments -->
	<section id="tour" class="cream py-24 bg-brand-cream">
		<div class="formGrid max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-16">

			<!-- Contact Info -->
			<div class="space-y-10 order-2">
				<div>
					<h2 class="font-serif text-3xl font-bold text-brand-ink mb-6">
						<?php echo esc_html($corporate_title); ?></h2>
					<p class="text-brand-ink/70 leading-relaxed mb-6">
						<?php _e('Local roots, one connected standard. Each Chroma campus is neighborhood-led, and our support team helps every message — enrollment, programs, careers, partnerships, or family questions — reach the right person.', 'chroma-excellence'); ?>
					</p>
					<div class="space-y-4">
						<div class="flex items-start gap-4">
							<div
								class="w-10 h-10 bg-chroma-blue/10 rounded-full flex items-center justify-center text-chroma-blue shrink-0">
								<i class="fa-solid fa-location-dot"></i></div>
							<div>
								<h4 class="font-bold text-brand-ink"><?php _e('Mailing Address', 'chroma-excellence'); ?></h4>
								<p class="text-brand-ink/60">
									<?php echo nl2br(esc_html($corporate_address)); ?>
								</p>
							</div>
						</div>
						<div class="flex items-start gap-4">
							<div
								class="w-10 h-10 bg-chroma-blue/10 rounded-full flex items-center justify-center text-chroma-blue shrink-0">
								<i class="fa-solid fa-phone"></i></div>
							<div>
								<h4 class="font-bold text-brand-ink"><?php _e('Phone', 'chroma-excellence'); ?></h4>
								<p class="text-brand-ink/60"><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $corporate_phone)); ?>" class="hover:text-chroma-blue transition"><?php echo esc_html($corporate_phone); ?></a></p>
								<p class="text-xs text-brand-ink/70 mt-1"><?php _e('Mon-Fri, 9am - 5pm EST', 'chroma-excellence'); ?></p>
							</div>
						</div>
						<?php if ($corporate_email): ?>
							<div class="flex items-start gap-4">
								<div
									class="w-10 h-10 bg-chroma-blue/10 rounded-full flex items-center justify-center text-chroma-blue shrink-0">
									<i class="fa-regular fa-envelope"></i></div>
								<div>
									<h4 class="font-bold text-brand-ink"><?php _e('Email', 'chroma-excellence'); ?></h4>
									<p class="text-brand-ink/60"><a href="mailto:<?php echo esc_attr($corporate_email); ?>" class="hover:text-chroma-blue transition"><?php echo esc_html($corporate_email); ?></a></p>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="pt-8 border-t border-gray-100">
					<h3 class="font-serif text-xl font-bold text-brand-ink mb-4"><?php _e('Department Emails', 'chroma-excellence'); ?></h3>
					<div class="grid sm:grid-cols-2 gap-4">
						<a href="mailto:enrollment@chromaela.com"
							class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-chroma-blue/30 hover:bg-chroma-blue/5 transition-all group">
							<i
								class="fa-regular fa-envelope text-chroma-blue group-hover:scale-110 transition-transform"></i>
							<div>
								<span class="block text-xs font-bold uppercase text-brand-ink/70"><?php _e('Enrollment', 'chroma-excellence'); ?></span>
								<span class="font-semibold text-brand-ink text-sm">enrollment@chromaela.com</span>
							</div>
						</a>
						<a href="mailto:careers@chromaela.com"
							class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-chroma-blue/30 hover:bg-chroma-blue/5 transition-all group">
							<i
								class="fa-regular fa-envelope text-chroma-blue group-hover:scale-110 transition-transform"></i>
							<div>
								<span class="block text-xs font-bold uppercase text-brand-ink/70"><?php _e('Careers', 'chroma-excellence'); ?></span>
								<span class="font-semibold text-brand-ink text-sm">careers@chromaela.com</span>
							</div>
						</a>
						<a href="mailto:media@chromaela.com"
							class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-chroma-blue/30 hover:bg-chroma-blue/5 transition-all group">
							<i
								class="fa-regular fa-envelope text-chroma-blue group-hover:scale-110 transition-transform"></i>
							<div>
								<span class="block text-xs font-bold uppercase text-brand-ink/70"><?php _e('Press / Media', 'chroma-excellence'); ?></span>
								<span class="font-semibold text-brand-ink text-sm">media@chromaela.com</span>
							</div>
						</a>
						<a href="mailto:partnerships@chromaela.com"
							class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-chroma-blue/30 hover:bg-chroma-blue/5 transition-all group">
							<i
								class="fa-regular fa-envelope text-chroma-blue group-hover:scale-110 transition-transform"></i>
							<div>
								<span class="block text-xs font-bold uppercase text-brand-ink/70"><?php _e('Acquisitions', 'chroma-excellence'); ?></span>
								<span class="font-semibold text-brand-ink text-sm">partnerships@chromaela.com</span>
							</div>
						</a>
					</div>
				</div>
			</div>

			<!-- Contact Form -->
			<div id="general-form"
				class="tourForm chroma-form-scroll-card chroma-form-scroll-card--contact bg-white rounded-[2.5rem] p-8 md:p-10 text-brand-ink relative overflow-y-auto shadow-card border border-brand-ink/5 order-1">
				<div
					class="absolute top-0 right-0 w-64 h-64 bg-chroma-blueLight rounded-full blur-[80px] opacity-50 -translate-y-1/2 translate-x-1/2">
				</div>

				<h3 class="font-serif text-2xl font-bold mb-2 relative z-10"><?php _e('Send us a note', 'chroma-excellence'); ?></h3>
				<p class="text-brand-ink/70 text-sm mb-8 relative z-10"><?php _e('Questions about enrollment, programs, careers, partnerships, or anything else? Tell us what you need and we will connect you with the right Chroma team.', 'chroma-excellence'); ?></p>

				<div class="relative z-10">
					<?php echo do_shortcode('[chroma_contact_form]'); ?>
				</div>
			</div>

		</div>
	</section>

	<!-- FAQ Section -->
	<section class="white borderY py-20 bg-white border-y border-chroma-blue/10">
		<div class="max-w-4xl mx-auto px-4 lg:px-6">
			<h2 class="font-serif text-3xl font-bold text-brand-ink mb-10 text-center"><?php _e('Helpful answers for every question.', 'chroma-excellence'); ?></h2>
			<div class="space-y-4">
				<details class="group bg-white rounded-2xl px-6 py-4 border border-chroma-blue/10 shadow-sm">
					<summary
						class="flex items-center justify-between gap-3 cursor-pointer list-none font-bold text-brand-ink">
						<?php _e('How do I schedule a tour?', 'chroma-excellence'); ?>
						<span class="text-chroma-blue group-open:rotate-180 transition-transform"><i
								class="fa-solid fa-chevron-down"></i></span>
					</summary>
					<p class="mt-3 text-brand-ink/70 text-sm leading-relaxed">
						<?php _e('Use the form on this page, call us, or choose a campus on the <a href="/locations/" class="text-chroma-blue underline">Locations Page</a> if you already know the school you want. Either way, we will route your request to the right Chroma team.', 'chroma-excellence'); ?>
					</p>
				</details>
				<details class="group bg-white rounded-2xl px-6 py-4 border border-chroma-blue/10 shadow-sm">
					<summary
						class="flex items-center justify-between gap-3 cursor-pointer list-none font-bold text-brand-ink">
						<?php _e('Are meals included in tuition?', 'chroma-excellence'); ?>
						<span class="text-chroma-blue group-open:rotate-180 transition-transform"><i
								class="fa-solid fa-chevron-down"></i></span>
					</summary>
					<p class="mt-3 text-brand-ink/70 text-sm leading-relaxed">
						<?php _e('Yes! We participate in the USDA food program. Breakfast, lunch, and afternoon snack are prepared fresh daily and are included in tuition for all age groups eating solid foods.', 'chroma-excellence'); ?>
					</p>
				</details>
				<details class="group bg-white rounded-2xl px-6 py-4 border border-chroma-blue/10 shadow-sm">
					<summary
						class="flex items-center justify-between gap-3 cursor-pointer list-none font-bold text-brand-ink">
						<?php _e('How do I check my position on a waitlist?', 'chroma-excellence'); ?>
						<span class="text-chroma-blue group-open:rotate-180 transition-transform"><i
								class="fa-solid fa-chevron-down"></i></span>
					</summary>
					<p class="mt-3 text-brand-ink/70 text-sm leading-relaxed">
						<?php _e('Campus-specific questions are welcome here too. Send your campus, child age, and timing through the form and we will connect you with the right campus team. If you prefer direct school details, each <a href="/locations/" class="text-chroma-blue underline">location page</a> lists them.', 'chroma-excellence'); ?>
					</p>
				</details>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();

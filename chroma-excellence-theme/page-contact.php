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
$corporate_name = get_post_meta($page_id, 'contact_corporate_name', true) ?: 'Chroma Early Learning HQ';
$corporate_address = get_post_meta($page_id, 'contact_corporate_address', true) ?: "123 Holcomb Bridge Rd, Suite 200\nRoswell, GA 30076";
$corporate_phone = get_post_meta($page_id, 'contact_corporate_phone', true) ?: '(770) 555-0199';

// Careers Section (Using existing meta if available, else standard fallback)
$careers_link_url = get_post_meta($page_id, 'contact_careers_link_url', true) ?: '/careers/';

?>

<main>
	<!-- Hero Section (FCP Optimized - No Form) -->
	<section class="relative bg-white pt-20 pb-20 overflow-hidden">
		<div
			class="absolute top-0 right-0 w-[500px] h-[500px] bg-chroma-greenLight rounded-full blur-[100px] opacity-40 translate-x-1/3 -translate-y-1/3">
		</div>
		<div
			class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-chroma-redLight rounded-full blur-[80px] opacity-40 -translate-x-1/3 translate-y-1/3">
		</div>

		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10">
			<div class="text-center max-w-3xl mx-auto mb-16">
				<span
					class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-4 block"><?php echo esc_html($hero_badge); ?></span>
				<h1 class="font-serif text-4xl md:text-6xl font-bold text-brand-ink mb-6">
					<?php echo esc_html($hero_title); ?></h1>
				<p class="text-lg text-brand-ink/70">
					<?php echo esc_html($hero_description); ?>
				</p>
			</div>

			<!-- Routing Grid (Directs users before they scroll to form) -->
			<div class="grid md:grid-cols-3 gap-6">
				<!-- Prospective Families -->
				<div
					class="bg-brand-cream border border-chroma-blue/10 p-8 rounded-[2rem] hover:shadow-card transition-all group text-center">
					<div
						class="w-16 h-16 mx-auto bg-white rounded-2xl flex items-center justify-center text-3xl mb-6 shadow-sm group-hover:scale-110 transition-transform">
						👶</div>
					<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php _e('Looking for care?', 'chroma-excellence'); ?></h3>
					<p class="text-brand-ink/60 text-sm mb-6"><?php _e('Find a school near you to check pricing, availability, and book a tour.', 'chroma-excellence'); ?></p>
					<a href="/locations/"
						class="inline-block w-full py-3 bg-chroma-red text-white font-bold uppercase tracking-wider text-xs rounded-xl hover:bg-chroma-red/90 transition-colors"><?php _e('Find a School', 'chroma-excellence'); ?></a>
				</div>

				<!-- Current Families -->
				<div
					class="bg-brand-cream border border-chroma-blue/10 p-8 rounded-[2rem] hover:shadow-card transition-all group text-center">
					<div
						class="w-16 h-16 mx-auto bg-white rounded-2xl flex items-center justify-center text-3xl mb-6 shadow-sm group-hover:scale-110 transition-transform">
						👨‍👩‍👧</div>
					<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php _e('Current Family?', 'chroma-excellence'); ?></h3>
					<p class="text-brand-ink/60 text-sm mb-6"><?php _e('Access the parent portal for tuition payments, daily reports, and photos.', 'chroma-excellence'); ?></p>
					<a href="/parents/"
						class="inline-block w-full py-3 bg-chroma-blue text-white font-bold uppercase tracking-wider text-xs rounded-xl hover:bg-chroma-blueDark transition-colors"><?php _e('Parent Portal', 'chroma-excellence'); ?></a>
				</div>

				<!-- Careers -->
				<div
					class="bg-brand-cream border border-chroma-blue/10 p-8 rounded-[2rem] hover:shadow-card transition-all group text-center">
					<div
						class="w-16 h-16 mx-auto bg-white rounded-2xl flex items-center justify-center text-3xl mb-6 shadow-sm group-hover:scale-110 transition-transform">
						🍎</div>
					<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3"><?php _e('Join the team?', 'chroma-excellence'); ?></h3>
					<p class="text-brand-ink/60 text-sm mb-6"><?php _e('We are always hiring passionate educators. View open positions today.', 'chroma-excellence'); ?></p>
					<a href="<?php echo esc_url($careers_link_url); ?>"
						class="inline-block w-full py-3 bg-chroma-yellow text-brand-ink font-bold uppercase tracking-wider text-xs rounded-xl hover:bg-white transition-colors"><?php _e('View Careers', 'chroma-excellence'); ?></a>
				</div>
			</div>
		</div>
	</section>

	<!-- Corporate Info & Departments -->
	<section class="py-20 bg-white border-y border-chroma-blue/10">
		<div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-16">

			<!-- Contact Info -->
			<div class="space-y-10">
				<div>
					<h2 class="font-serif text-3xl font-bold text-brand-ink mb-6">
						<?php echo esc_html($corporate_title); ?></h2>
					<p class="text-brand-ink/70 leading-relaxed mb-6">
						<?php _e('While our schools are the heart of what we do, our administrative team supports operations from our central office in Roswell.', 'chroma-excellence'); ?>
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
								<p class="text-brand-ink/60"><?php echo esc_html($corporate_phone); ?></p>
								<p class="text-xs text-brand-ink/70 mt-1"><?php _e('Mon-Fri, 9am - 5pm EST', 'chroma-excellence'); ?></p>
							</div>
						</div>
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

			<!-- General Inquiry Form (Below Fold) -->
			<div id="general-form"
				class="bg-brand-ink rounded-[2.5rem] p-8 md:p-10 text-white relative overflow-hidden">
				<div
					class="absolute top-0 right-0 w-64 h-64 bg-chroma-blue rounded-full blur-[80px] opacity-20 -translate-y-1/2 translate-x-1/2">
				</div>

				<h3 class="font-serif text-2xl font-bold mb-2 relative z-10"><?php _e('General Inquiries', 'chroma-excellence'); ?></h3>
				<p class="text-white/90 text-sm mb-8 relative z-10"><?php _e('For general questions not related to a specific campus tour.', 'chroma-excellence'); ?></p>

				<div class="relative z-10">
					<?php echo do_shortcode('[chroma_contact_form]'); ?>
				</div>
			</div>

		</div>
	</section>

	<!-- FAQ Section -->
	<section class="py-20 bg-brand-cream">
		<div class="max-w-4xl mx-auto px-4 lg:px-6">
			<h2 class="font-serif text-3xl font-bold text-brand-ink mb-10 text-center"><?php _e('Frequently Asked Questions', 'chroma-excellence'); ?></h2>
			<div class="space-y-4">
				<details class="group bg-white rounded-2xl px-6 py-4 border border-chroma-blue/10 shadow-sm">
					<summary
						class="flex items-center justify-between gap-3 cursor-pointer list-none font-bold text-brand-ink">
						<?php _e('How do I schedule a tour?', 'chroma-excellence'); ?>
						<span class="text-chroma-blue group-open:rotate-180 transition-transform"><i
								class="fa-solid fa-chevron-down"></i></span>
					</summary>
					<p class="mt-3 text-brand-ink/70 text-sm leading-relaxed">
						<?php _e('The fastest way is to visit our <a href="/locations/" class="text-chroma-blue underline">Locations Page</a>, select your nearest campus, and use the "Book Tour" button on that specific page. This ensures your request goes directly to that school\'s director.', 'chroma-excellence'); ?>
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
						<?php _e('Please email the director of your specific campus directly. You can find their contact information on the specific <a href="/locations/" class="text-chroma-blue underline">Location Page</a>.', 'chroma-excellence'); ?>
					</p>
				</details>
			</div>
		</div>
	</section>

</main>

<?php
get_footer();

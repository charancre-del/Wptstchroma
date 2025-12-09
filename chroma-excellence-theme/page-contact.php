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
$hero_badge = get_post_meta($page_id, 'contact_hero_badge', true) ?: 'Start Your Journey';
$hero_title = get_post_meta($page_id, 'contact_hero_title', true) ?: 'We\'d love to meet you.';
$hero_description = get_post_meta($page_id, 'contact_hero_description', true) ?: 'Ready to experience the Chroma difference? Schedule a tour or ask us a question below to get started.';

// Form Settings
$form_submit_text = get_post_meta($page_id, 'contact_form_submit_text', true) ?: 'Submit Request';

// Corporate Office
$corporate_title = get_post_meta($page_id, 'contact_corporate_title', true) ?: 'Corporate Office';
$corporate_name = get_post_meta($page_id, 'contact_corporate_name', true) ?: 'Chroma Early Learning HQ';
$corporate_address = get_post_meta($page_id, 'contact_corporate_address', true) ?: "123 Education Way, Suite 400\nAtlanta, GA 30309";
$corporate_phone = get_post_meta($page_id, 'contact_corporate_phone', true) ?: '(404) 555-0199';

// Careers Section
$careers_title = get_post_meta($page_id, 'contact_careers_title', true) ?: 'Careers';
$careers_description = get_post_meta($page_id, 'contact_careers_description', true) ?: 'Passionate about early childhood education? We are always looking for dedicated teachers and directors.';
$careers_link_text = get_post_meta($page_id, 'contact_careers_link_text', true) ?: 'View Open Positions';
$careers_link_url = get_post_meta($page_id, 'contact_careers_link_url', true) ?: '/careers';

// Press Section
$press_title = get_post_meta($page_id, 'contact_press_title', true) ?: 'Press Inquiries';
$press_description = get_post_meta($page_id, 'contact_press_description', true) ?: 'For media kits and interview requests with our leadership team.';
$press_link_text = get_post_meta($page_id, 'contact_press_link_text', true) ?: 'Visit Newsroom';
$press_link_url = get_post_meta($page_id, 'contact_press_link_url', true) ?: '/newsroom';
?>

<main id="primary" class="site-main" role="main">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<!-- Hero -->
		<section class="py-20 bg-white text-center">
			<div class="max-w-4xl mx-auto px-4">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
					<?php echo esc_html($hero_badge); ?>
				</span>
				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6">
					Contact Chroma Early Learning
				</h1>
				<p class="text-lg text-brand-ink/90">
					<?php echo esc_html($hero_description); ?>
				</p>
			</div>
		</section>

		<!-- Contact Grid -->
		<section class="pb-24 bg-white">
			<div class="max-w-6xl mx-auto px-4 lg:px-6">
				<div
					class="bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 overflow-hidden flex flex-col lg:flex-row">

					<!-- Left: Info Sidebar (Narrow, Fixed Width) -->
					<div
						class="bg-gradient-to-br from-chroma-blue via-chroma-green to-chroma-yellow text-white p-6 lg:p-8 lg:w-80 flex-shrink-0">

						<!-- Corporate Office -->
						<div class="mb-6">
							<h2 class="font-serif text-xl font-bold mb-3">
								<?php echo esc_html($corporate_title); ?>
							</h2>
							<p class="text-white/90 text-sm leading-relaxed">
								<?php
								if (!empty($corporate_name)) {
									echo esc_html($corporate_name) . '<br>';
								}
								echo nl2br(esc_html($corporate_address));
								?>
							</p>
							<a href="mailto:info@chromaela.com"
								class="text-white font-semibold text-sm mt-2 block hover:underline">
								info@chromaela.com
							</a>
						</div>

						<div class="h-px bg-white/20 w-full mb-6"></div>

						<!-- Careers -->
						<div class="mb-6">
							<h2 class="font-serif text-xl font-bold mb-2">
								<?php echo esc_html($careers_title); ?>
							</h2>
							<p class="text-white/90 text-sm mb-3">
								<?php echo esc_html($careers_description); ?>
							</p>
							<a href="<?php echo esc_url($careers_link_url); ?>"
								class="text-white font-semibold text-xs uppercase tracking-wider hover:underline flex items-center gap-2">
								<?php echo esc_html($careers_link_text); ?>
								<span>→</span>
							</a>
						</div>

						<div class="h-px bg-white/20 w-full mb-6"></div>

						<!-- Press Inquiries -->
						<div>
							<h2 class="font-serif text-xl font-bold mb-2">
								<?php echo esc_html($press_title); ?>
							</h2>
							<p class="text-white/90 text-sm mb-3">
								<?php echo esc_html($press_description); ?>
							</p>
							<a href="<?php echo esc_url($press_link_url); ?>"
								class="text-white font-semibold text-xs uppercase tracking-wider hover:underline flex items-center gap-2">
								<?php echo esc_html($press_link_text); ?>
								<span>→</span>
							</a>
						</div>
					</div>

					<!-- Right: Form (Takes Remaining Space) -->
					<div class="flex-1 p-4 lg:p-6 bg-brand-cream">
						<?php echo do_shortcode('[chroma_contact_form]'); ?>
					</div>

				</div>
			</div>
		</section>

	</article>
</main>

<?php
get_footer();

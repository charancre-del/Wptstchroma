<?php
/**
 * Template Name: Careers Page
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();

$page_id = get_the_ID();

// Hero Section
$hero_badge = get_post_meta($page_id, 'careers_hero_badge', true) ?: 'Join Our Team';
$hero_title = get_post_meta($page_id, 'careers_hero_title', true) ?: 'Shape the future. <br><span class="italic text-chroma-red">Love your work.</span>';
$hero_description = get_post_meta($page_id, 'careers_hero_description', true) ?: 'We don\'t just hire staff; we invest in educators. At Chroma, you\'ll find a supportive community, career pathways, and the resources you need to change lives.';
$hero_button_text = get_post_meta($page_id, 'careers_hero_button_text', true) ?: 'View Current Openings';
$hero_button_url = get_post_meta($page_id, 'careers_hero_button_url', true) ?: '#openings';

// Culture Section
$culture_title = get_post_meta($page_id, 'careers_culture_title', true) ?: 'Why Chroma?';
$culture_description = get_post_meta($page_id, 'careers_culture_description', true) ?: 'We take care of you, so you can take care of them.';

$benefits = array(
	array(
		'icon' => get_post_meta($page_id, 'careers_benefit1_icon', true) ?: 'fa-solid fa-money-bill-wave',
		'color' => 'chroma-green',
		'title' => get_post_meta($page_id, 'careers_benefit1_title', true) ?: 'Competitive Pay & 401k',
		'desc' => get_post_meta($page_id, 'careers_benefit1_desc', true) ?: 'Above-market salaries, annual performance bonuses, and retirement matching.',
	),
	array(
		'icon' => get_post_meta($page_id, 'careers_benefit2_icon', true) ?: 'fa-solid fa-graduation-cap',
		'color' => 'chroma-blue',
		'title' => get_post_meta($page_id, 'careers_benefit2_title', true) ?: 'Paid Tuition & CDA',
		'desc' => get_post_meta($page_id, 'careers_benefit2_desc', true) ?: 'We pay for your Child Development Associate (CDA) credential and offer college tuition assistance.',
	),
	array(
		'icon' => get_post_meta($page_id, 'careers_benefit3_icon', true) ?: 'fa-solid fa-heart-pulse',
		'color' => 'chroma-red',
		'title' => get_post_meta($page_id, 'careers_benefit3_title', true) ?: 'Health & Wellness',
		'desc' => get_post_meta($page_id, 'careers_benefit3_desc', true) ?: 'Comprehensive medical, dental, and vision insurance, plus free childcare discounts.',
	),
);

// Openings Section
$openings_title = get_post_meta($page_id, 'careers_openings_title', true) ?: 'Current Opportunities';

// CTA Section
$cta_title = get_post_meta($page_id, 'careers_cta_title', true) ?: 'Don\'t see your role?';
$cta_description = get_post_meta($page_id, 'careers_cta_description', true) ?: 'We are always growing. Send us your resume and we\'ll keep it on file.';

// Fetch jobs from API
$jobs = function_exists('chroma_get_careers') ? chroma_get_careers() : array();
?>



<main id="primary" class="site-main" role="main">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<!-- Hero -->
		<section class="py-24 bg-white text-center relative overflow-hidden">
			<div class="max-w-4xl mx-auto px-4 relative z-10">
				<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-4 block">
					<?php echo esc_html($hero_badge); ?>
				</span>
				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6">
					<?php echo wp_kses_post($hero_title); ?>
				</h1>
				<p class="text-lg text-brand-ink/90 max-w-2xl mx-auto mb-10">
					<?php echo esc_html($hero_description); ?>
				</p>
				<a href="<?php echo esc_url($hero_button_url); ?>"
					class="px-8 py-4 bg-chroma-red text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:bg-brand-ink transition-colors shadow-lg">
					<?php echo esc_html($hero_button_text); ?>
				</a>
			</div>
		</section>

		<!-- Culture & Benefits -->
		<section id="culture" class="py-24 bg-brand-cream border-t border-brand-ink/5">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-16">
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink">
						<?php echo esc_html($culture_title); ?>
					</h2>
					<p class="text-brand-ink/90 mt-4">
						<?php echo esc_html($culture_description); ?>
					</p>
				</div>

				<div class="grid md:grid-cols-3 gap-8">
					<?php foreach ($benefits as $benefit): ?>
						<div
							class="bg-white p-8 rounded-[2rem] shadow-soft text-center group hover:-translate-y-1 transition-transform">
							<div
								class="w-16 h-16 mx-auto bg-<?php echo esc_attr($benefit['color']); ?>/10 rounded-full flex items-center justify-center text-2xl text-<?php echo esc_attr($benefit['color']); ?> mb-6 group-hover:bg-<?php echo esc_attr($benefit['color']); ?> group-hover:text-white transition-colors">
								<i class="<?php echo esc_attr($benefit['icon']); ?>"></i>
							</div>
							<h3 class="font-bold text-xl mb-2">
								<?php echo esc_html($benefit['title']); ?>
							</h3>
							<p class="text-sm text-brand-ink/90">
								<?php echo esc_html($benefit['desc']); ?>
							</p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Openings -->
		<section id="openings" class="py-24 bg-white">
			<div class="max-w-5xl mx-auto px-4 lg:px-6">
				<h2 class="text-3xl font-serif font-bold text-brand-ink mb-8">
					<?php echo esc_html($openings_title); ?>
				</h2>

				<!-- Scrollable Job Board Container -->
				<div class="max-h-[600px] overflow-y-auto pr-2 custom-scrollbar space-y-4">
					<?php if (!empty($jobs)): ?>
						<?php foreach ($jobs as $job): ?>
							<div
								class="relative group border border-brand-ink/10 rounded-2xl p-6 hover:border-chroma-red/50 transition-colors flex flex-col md:flex-row justify-between items-center gap-4 bg-white">
								<a href="<?php echo esc_url($job['url']); ?>" target="_blank" rel="noopener noreferrer" class="absolute inset-0 z-0" aria-label="Apply for <?php echo esc_attr($job['title']); ?>"></a>
								<div class="relative z-0">
									<h3 class="font-bold text-xl text-brand-ink">
										<?php echo esc_html($job['title']); ?>
									</h3>
									<p class="text-sm text-brand-ink/90">
										<?php echo esc_html($job['location']); ?> &bull;
										<?php echo esc_html($job['type']); ?>
									</p>
								</div>
								<a href="<?php echo esc_url($job['url']); ?>" target="_blank" rel="noopener noreferrer"
									class="relative z-10 px-6 py-3 border border-brand-ink/20 rounded-full text-xs font-bold uppercase tracking-wider hover:bg-chroma-red hover:text-white hover:border-chroma-red transition-colors whitespace-nowrap">
									Apply Now
								</a>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<div class="text-center py-12 border border-dashed border-brand-ink/20 rounded-2xl">
							<p class="text-brand-ink/90">No current openings. Please check back later.</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<!-- Application Form Anchor -->
		<section id="apply" class="py-20 bg-brand-cream">
			<div class="max-w-4xl mx-auto px-4">
				<div class="text-center mb-10">
					<h2 class="font-serif text-3xl font-bold mb-4">
						<?php echo esc_html($cta_title); ?>
					</h2>
					<p class="text-brand-ink/90">
						<?php echo esc_html($cta_description); ?>
					</p>
				</div>

				<div class="bg-white p-10 rounded-[3rem] border border-brand-ink/5 shadow-soft">
					<?php echo do_shortcode('[chroma_career_form]'); ?>
				</div>
			</div>
		</section>

	</article>
</main>

<?php
get_footer();

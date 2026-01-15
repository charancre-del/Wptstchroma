<?php
/**
 * Template Name: Employers Page
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();

$page_id = get_the_ID();

// Hero Section
$hero_badge = chroma_get_translated_meta($page_id, 'employers_hero_badge') ?: __('Workforce Solutions', 'chroma-excellence');
$hero_title = chroma_get_translated_meta($page_id, 'employers_hero_title') ?: __('Childcare is critical infrastructure.', 'chroma-excellence');
$hero_description = chroma_get_translated_meta($page_id, 'employers_hero_description') ?: __('Retain top talent and reduce absenteeism by offering premium childcare benefits. Chroma partners with Metro Atlanta\'s leading employers to support working parents.', 'chroma-excellence');

// Solutions Section (3 cards)
$solutions = array(
	array(
		'title' => chroma_get_translated_meta($page_id, 'employers_solution1_title') ?: __('Priority Access', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'employers_solution1_desc') ?: __('Skip the waitlist. Reserve dedicated spots at our 19+ locations exclusively for your employees\' children.', 'chroma-excellence'),
	),
	array(
		'title' => chroma_get_translated_meta($page_id, 'employers_solution2_title') ?: __('Tuition Subsidies', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'employers_solution2_desc') ?: __('We manage employer-sponsored tuition matching programs, making quality care affordable for your team.', 'chroma-excellence'),
	),
	array(
		'title' => chroma_get_translated_meta($page_id, 'employers_solution3_title') ?: __('Back-Up Care', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'employers_solution3_desc') ?: __('Flexible drop-in options for when schools close or regular caregivers fall through, keeping your team at work.', 'chroma-excellence'),
	),
);

// Tax Incentives Section
$tax_badge = chroma_get_translated_meta($page_id, 'employers_tax_badge') ?: __('Financial Incentives', 'chroma-excellence');
$tax_title = chroma_get_translated_meta($page_id, 'employers_tax_title') ?: __('Maximize Your ROI with Tax Credits', 'chroma-excellence');
$tax_description = chroma_get_translated_meta($page_id, 'employers_tax_description') ?: __('Partnering with Chroma isn\'t just an investment in your company culture—it\'s a smart financial move. State and Federal programs significantly offset the cost of providing childcare benefits.', 'chroma-excellence');

// Federal Credit Card
$federal_icon = chroma_get_translated_meta($page_id, 'employers_federal_icon') ?: 'fa-solid fa-landmark';
$federal_title = chroma_get_translated_meta($page_id, 'employers_federal_title') ?: __('Federal 45F Credit', 'chroma-excellence');
$federal_subtitle = chroma_get_translated_meta($page_id, 'employers_federal_subtitle') ?: __('Employer-Provided Child Care Credit', 'chroma-excellence');
$federal_desc = chroma_get_translated_meta($page_id, 'employers_federal_desc') ?: __('Under the "One Big Beautiful Bill Act" (effective 2026), businesses can claim a tax credit of up to <strong>$500,000 annually</strong>. This covers <strong>40%</strong> of qualified childcare expenditures (rising to <strong>50%</strong> and <strong>$600,000</strong> for small businesses).', 'chroma-excellence');
$federal_link_text = chroma_get_translated_meta($page_id, 'employers_federal_link_text') ?: __('View IRS Form 8882', 'chroma-excellence');
$federal_link_url = chroma_get_translated_meta($page_id, 'employers_federal_link_url') ?: 'https://www.irs.gov/forms-pubs/about-form-8882';

// Georgia Credit Card
$georgia_icon = chroma_get_translated_meta($page_id, 'employers_georgia_icon') ?: 'fa-solid fa-map-location-dot';
$georgia_title = chroma_get_translated_meta($page_id, 'employers_georgia_title') ?: __('Georgia Employer\'s Credit', 'chroma-excellence');
$georgia_subtitle = chroma_get_translated_meta($page_id, 'employers_georgia_subtitle') ?: __('Georgia Child Care Tax Credit', 'chroma-excellence');
$georgia_desc = chroma_get_translated_meta($page_id, 'employers_georgia_desc') ?: __('Georgia maintains its generous <strong>75% credit</strong> for employer-sponsored care costs. New for 2026: Employers can also claim a supplemental credit of <strong>$1,000 per child</strong> for eligible childcare payments provided to employees.', 'chroma-excellence');
$georgia_link_text = chroma_get_translated_meta($page_id, 'employers_georgia_link_text') ?: __('View Georgia DOR Details', 'chroma-excellence');
$georgia_link_url = chroma_get_translated_meta($page_id, 'employers_georgia_link_url') ?: 'https://dor.georgia.gov/tax-credits-business';

$tax_disclaimer = chroma_get_translated_meta($page_id, 'employers_tax_disclaimer') ?: __('Note: Please consult with your corporate tax professional to verify eligibility and application details.', 'chroma-excellence');

// Contact Section
$contact_title = chroma_get_translated_meta($page_id, 'employers_contact_title') ?: __('Build a family-friendly culture.', 'chroma-excellence');
?>

<main id="primary" class="site-main" role="main">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<!-- Hero -->
		<section class="py-24 bg-brand-cream text-center">
			<div class="max-w-4xl mx-auto px-4">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-4 block">
					<?php echo esc_html($hero_badge); ?>
				</span>
				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6">
					<?php _e('Corporate Childcare Solutions:', 'chroma-excellence'); ?> <span class="italic text-chroma-blue"><?php _e('Critical Infrastructure for Your Team', 'chroma-excellence'); ?></span>
				</h1>
				<p class="text-lg text-brand-ink/80 max-w-2xl mx-auto">
					<?php echo esc_html($hero_description); ?>
				</p>
			</div>
		</section>

		<!-- Solutions -->
		<section class="py-24 bg-white">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<h2 class="font-serif text-3xl font-bold text-center text-brand-ink mb-12"><?php _e('Our Partnership Models', 'chroma-excellence'); ?></h2>
				<div class="grid md:grid-cols-3 gap-12">
					<?php foreach ($solutions as $solution): ?>
						<div class="text-center">
							<h3 class="font-serif text-2xl font-bold mb-4 text-brand-ink">
								<?php echo esc_html($solution['title']); ?>
							</h3>
							<p class="text-brand-ink/80 text-sm leading-relaxed">
								<?php echo esc_html($solution['desc']); ?>
							</p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Tax Incentives Section -->
		<section class="py-24 bg-brand-cream border-t border-brand-ink/5">
			<div class="max-w-6xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-16">
					<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
						<?php echo esc_html($tax_badge); ?>
					</span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
						<?php echo esc_html($tax_title); ?>
					</h2>
					<p class="text-brand-ink/80 max-w-2xl mx-auto">
						<?php echo esc_html($tax_description); ?>
					</p>
				</div>

				<div class="grid md:grid-cols-2 gap-8">
					<!-- Federal Credit -->
					<div
						class="bg-white p-10 rounded-[2.5rem] shadow-soft border border-brand-ink/5 relative overflow-hidden group">
						<div class="absolute top-0 right-0 w-32 h-32 bg-chroma-blue/5 rounded-full -mr-10 -mt-10"></div>
						<div class="relative z-10">
							<div
								class="w-12 h-12 bg-chroma-blue text-white rounded-xl flex items-center justify-center text-xl mb-6 shadow-md">
								<i class="<?php echo esc_attr($federal_icon); ?>"></i>
							</div>
							<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3">
								<?php echo esc_html($federal_title); ?>
							</h3>
							<p class="text-sm font-bold text-chroma-blue mb-4 uppercase tracking-wider">
								<?php echo esc_html($federal_subtitle); ?>
							</p>
							<p class="text-brand-ink/80 text-sm leading-relaxed mb-6">
								<?php echo wp_kses_post($federal_desc); ?>
							</p>
							<a href="<?php echo esc_url($federal_link_url); ?>" target="_blank"
								class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-brand-ink border-b border-chroma-blue pb-1 hover:text-chroma-blue transition-colors">
								<?php echo esc_html($federal_link_text); ?>
								<i class="fa-solid fa-arrow-up-right-from-square"></i>
							</a>
						</div>
					</div>

					<!-- Georgia Credit -->
					<div
						class="bg-white p-10 rounded-[2.5rem] shadow-soft border border-brand-ink/5 relative overflow-hidden group">
						<div class="absolute top-0 right-0 w-32 h-32 bg-chroma-green/5 rounded-full -mr-10 -mt-10">
						</div>
						<div class="relative z-10">
							<div
								class="w-12 h-12 bg-chroma-green text-white rounded-xl flex items-center justify-center text-xl mb-6 shadow-md">
								<i class="<?php echo esc_attr($georgia_icon); ?>"></i>
							</div>
							<h3 class="font-serif text-2xl font-bold text-brand-ink mb-3">
								<?php echo esc_html($georgia_title); ?>
							</h3>
							<p class="text-sm font-bold text-chroma-green mb-4 uppercase tracking-wider">
								<?php echo esc_html($georgia_subtitle); ?>
							</p>
							<p class="text-brand-ink/80 text-sm leading-relaxed mb-6">
								<?php echo wp_kses_post($georgia_desc); ?>
							</p>
							<a href="<?php echo esc_url($georgia_link_url); ?>" target="_blank"
								class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-brand-ink border-b border-chroma-green pb-1 hover:text-chroma-green transition-colors">
								<?php echo esc_html($georgia_link_text); ?>
								<i class="fa-solid fa-arrow-up-right-from-square"></i>
							</a>
						</div>
					</div>
				</div>
				<p class="text-xs text-center text-brand-ink/60 mt-8 italic">
					<?php echo esc_html($tax_disclaimer); ?>
				</p>
			</div>
		</section>

		<!-- Contact Form Section -->
		<section id="contact" class="py-24 bg-chroma-blueDark text-white">
			<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
				<h2 class="font-serif text-3xl md:text-4xl font-bold mb-8">
					<?php echo esc_html($contact_title); ?>
				</h2>
				<form class="max-w-md mx-auto space-y-4 text-brand-ink">
					<input type="text" name="company_name" placeholder="<?php esc_attr_e('Company Name', 'chroma-excellence'); ?>" aria-label="<?php esc_attr_e('Company Name', 'chroma-excellence'); ?>"
						class="w-full p-4 rounded-xl" required>
					<input type="text" name="contact_name" placeholder="<?php esc_attr_e('HR Contact Name', 'chroma-excellence'); ?>" aria-label="<?php esc_attr_e('HR Contact Name', 'chroma-excellence'); ?>"
						class="w-full p-4 rounded-xl" required>
					<input type="email" name="work_email" placeholder="<?php esc_attr_e('Work Email', 'chroma-excellence'); ?>" aria-label="<?php esc_attr_e('Work Email', 'chroma-excellence'); ?>"
						class="w-full p-4 rounded-xl" required>
					<button type="submit"
						class="w-full py-4 bg-chroma-yellow text-brand-ink font-bold rounded-full uppercase tracking-widest hover:bg-white transition-colors">
						<?php _e('Request Info Kit', 'chroma-excellence'); ?>
					</button>
				</form>
			</div>
		</section>

	</article>
</main>

<?php
get_footer();

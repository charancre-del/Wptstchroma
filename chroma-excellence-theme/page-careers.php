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
$hero_badge = chroma_get_translated_meta($page_id, 'careers_hero_badge') ?: __('Join Our Team', 'chroma-excellence');
$hero_title = chroma_get_translated_meta($page_id, 'careers_hero_title') ?: __('Shape the future. <br><span class="italic text-chroma-red">Love your work.</span>', 'chroma-excellence');
$hero_description = chroma_get_translated_meta($page_id, 'careers_hero_description') ?: __('We don\'t just hire staff; we invest in educators. At Chroma, you\'ll find a supportive community, career pathways, and the resources you need to change lives.', 'chroma-excellence');
$hero_button_text = chroma_get_translated_meta($page_id, 'careers_hero_button_text') ?: __('View Current Openings', 'chroma-excellence');
$hero_button_url = chroma_get_translated_meta($page_id, 'careers_hero_button_url') ?: '#openings';

// Culture Section
$culture_title = chroma_get_translated_meta($page_id, 'careers_culture_title') ?: __('Why Chroma?', 'chroma-excellence');
$culture_description = chroma_get_translated_meta($page_id, 'careers_culture_description') ?: __('We take care of you, so you can take care of them.', 'chroma-excellence');

$benefits = array(
	array(
		'icon' => chroma_get_translated_meta($page_id, 'careers_benefit1_icon') ?: 'fa-solid fa-money-bill-wave',
		'color' => 'chroma-green',
		'title' => chroma_get_translated_meta($page_id, 'careers_benefit1_title') ?: __('Competitive Pay & 401k', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'careers_benefit1_desc') ?: __('Above-market salaries, annual performance bonuses, and retirement matching.', 'chroma-excellence'),
	),
	array(
		'icon' => chroma_get_translated_meta($page_id, 'careers_benefit2_icon') ?: 'fa-solid fa-graduation-cap',
		'color' => 'chroma-blue',
		'title' => chroma_get_translated_meta($page_id, 'careers_benefit2_title') ?: __('Paid Tuition & CDA', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'careers_benefit2_desc') ?: __('We pay for your Child Development Associate (CDA) credential and offer college tuition assistance.', 'chroma-excellence'),
	),
	array(
		'icon' => chroma_get_translated_meta($page_id, 'careers_benefit3_icon') ?: 'fa-solid fa-heart-pulse',
		'color' => 'chroma-red',
		'title' => chroma_get_translated_meta($page_id, 'careers_benefit3_title') ?: __('Health & Wellness', 'chroma-excellence'),
		'desc' => chroma_get_translated_meta($page_id, 'careers_benefit3_desc') ?: __('Comprehensive medical, dental, and vision insurance, plus free childcare discounts.', 'chroma-excellence'),
	),
);

// Openings Section
$openings_title = chroma_get_translated_meta($page_id, 'careers_openings_title') ?: __('Current Opportunities', 'chroma-excellence');

// CTA Section
$cta_title = chroma_get_translated_meta($page_id, 'careers_cta_title') ?: __('Don\'t see your role?', 'chroma-excellence');
$cta_description = chroma_get_translated_meta($page_id, 'careers_cta_description') ?: __('We are always growing. Send us your resume and we\'ll keep it on file.', 'chroma-excellence');

// Fetch jobs from API
$jobs = function_exists('chroma_get_careers') ? chroma_get_careers() : array();

// Build JobPosting schema directly on the Careers page.
$job_schema_graph = array();
if (!empty($jobs) && is_array($jobs)) {
	$organization_schema = array(
		'@type' => 'Organization',
		'name' => get_bloginfo('name'),
		'url' => home_url('/'),
	);

	if (function_exists('has_custom_logo') && has_custom_logo()) {
		$logo_id = get_theme_mod('custom_logo');
		$logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
		if ($logo_url) {
			$organization_schema['logo'] = $logo_url;
		}
	}

	$map_employment_type = static function ($raw_type) {
		$type = strtoupper(trim((string) $raw_type));
		$map = array(
			'FULL TIME' => 'FULL_TIME',
			'FULL-TIME' => 'FULL_TIME',
			'PART TIME' => 'PART_TIME',
			'PART-TIME' => 'PART_TIME',
			'CONTRACT' => 'CONTRACTOR',
			'CONTRACTOR' => 'CONTRACTOR',
			'TEMP' => 'TEMPORARY',
			'TEMPORARY' => 'TEMPORARY',
			'INTERN' => 'INTERN',
			'VOLUNTEER' => 'VOLUNTEER',
		);

		return $map[$type] ?? $type;
	};

	foreach ($jobs as $job) {
		if (!is_array($job)) {
			continue;
		}

		$title = sanitize_text_field($job['title'] ?? '');
		if (!$title) {
			continue;
		}

		$job_url = esc_url_raw($job['url'] ?? '');
		$job_location = sanitize_text_field($job['location'] ?? '');
		$job_city = sanitize_text_field($job['city'] ?? '');
		$job_state = sanitize_text_field($job['state'] ?? '');
		$job_postal_code = sanitize_text_field($job['postal_code'] ?? '');
		$job_country = sanitize_text_field($job['country'] ?? 'US') ?: 'US';
		$raw_type = $job['type'] ?? '';
		$employment_type = $raw_type ? $map_employment_type($raw_type) : '';

		$raw_description = '';
		if (!empty($job['description'])) {
			$raw_description = $job['description'];
		} elseif (!empty($job['summary'])) {
			$raw_description = $job['summary'];
		} elseif (!empty($job['content'])) {
			$raw_description = $job['content'];
		}

		$description = trim(wp_strip_all_tags((string) $raw_description));
		if (!$description) {
			$description = sprintf(
				/* translators: 1: job title, 2: company name */
				__('Apply for %1$s at %2$s.', 'chroma-excellence'),
				$title,
				get_bloginfo('name')
			);
		}

		$raw_date = $job['date_posted'] ?? ($job['posted'] ?? ($job['posted_at'] ?? ''));
		$timestamp = $raw_date ? strtotime((string) $raw_date) : false;
		$date_posted = $timestamp ? gmdate('Y-m-d', $timestamp) : gmdate('Y-m-d');

		$schema_job = array(
			'@type' => 'JobPosting',
			'title' => $title,
			'description' => $description,
			'datePosted' => $date_posted,
			'hiringOrganization' => $organization_schema,
		);

		if ($job_url) {
			$schema_job['url'] = $job_url;
			$schema_job['directApply'] = true;
		}

		if ($employment_type) {
			$schema_job['employmentType'] = $employment_type;
		}

		if ($job_location || $job_city || $job_state || $job_postal_code) {
			$address = array(
				'@type' => 'PostalAddress',
				'addressCountry' => $job_country,
			);

			if ($job_city) {
				$address['addressLocality'] = $job_city;
			} elseif ($job_location) {
				$address['addressLocality'] = $job_location;
			}

			if ($job_state) {
				$address['addressRegion'] = $job_state;
			}

			if ($job_postal_code) {
				$address['postalCode'] = $job_postal_code;
			}

			$schema_job['jobLocation'] = array(
				'@type' => 'Place',
				'address' => $address,
			);
		}

		$job_schema_graph[] = $schema_job;
	}
}
?>

<?php if (!empty($job_schema_graph)): ?>
	<script type="application/ld+json">
		<?php echo wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $job_schema_graph), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
	</script>
<?php endif; ?>



<main id="primary" class="site-main" role="main">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<!-- Hero -->
		<section class="pageHero chroma-v2-page-hero py-24 bg-white text-center relative overflow-hidden">
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
								<a href="<?php echo esc_url($job['url']); ?>" class="job-modal-trigger absolute inset-0 z-10" aria-label="Apply for <?php echo esc_attr($job['title']); ?>"></a>
								<div>
									<h3 class="font-bold text-xl text-brand-ink">
										<?php echo esc_html($job['title']); ?>
									</h3>
									<p class="text-sm text-brand-ink/90">
										<?php echo esc_html($job['location']); ?> &bull;
										<?php echo esc_html($job['type']); ?>
									</p>
								</div>
								<a href="<?php echo esc_url($job['url']); ?>" 
									class="job-modal-trigger relative z-20 px-6 py-3 border border-brand-ink/20 rounded-full text-xs font-bold uppercase tracking-wider hover:bg-chroma-red hover:text-white hover:border-chroma-red transition-colors whitespace-nowrap">
									<?php _e('Apply Now', 'chroma-excellence'); ?>
								</a>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<div class="text-center py-12 border border-dashed border-brand-ink/20 rounded-2xl">
							<p class="text-brand-ink/90"><?php _e('No current openings. Please check back later.', 'chroma-excellence'); ?></p>
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

				<div class="chroma-form-scroll-card chroma-form-scroll-card--career bg-white p-10 rounded-[3rem] border border-brand-ink/5 shadow-soft" tabindex="0" aria-label="<?php esc_attr_e( 'Career application form', 'chroma-excellence' ); ?>">
					<?php echo do_shortcode('[chroma_career_form]'); ?>
				</div>
			</div>
		</section>

	</article>
</main>

<!-- Job Application Modal -->
<div id="chroma-job-modal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
	<!-- Backdrop -->
	<div class="absolute inset-0 bg-brand-ink/80 backdrop-blur-sm transition-opacity" id="chroma-job-backdrop">
	</div>

	<!-- Modal Container -->
	<div
		class="absolute inset-4 md:inset-10 bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-fade-in-up">
		<!-- Header -->
			<div
				class="bg-brand-cream border-b border-brand-ink/5 px-6 py-4 flex items-center justify-between flex-shrink-0">
			<h3 class="font-serif text-xl font-bold text-brand-ink"><?php _e('Apply for Position', 'chroma-excellence'); ?></h3>
			<div class="flex items-center gap-4">
				<a href="#" id="chroma-job-external" target="_blank"
					class="text-xs font-bold uppercase tracking-wider text-brand-ink/70 hover:text-chroma-blue transition-colors hidden md:block">
					<?php _e('Open in new tab', 'chroma-excellence'); ?> <i class="fa-solid fa-external-link-alt ml-1"></i>
				</a>
				<button id="chroma-job-close"
					class="w-10 h-10 rounded-full bg-white border border-brand-ink/10 flex items-center justify-center text-brand-ink hover:bg-chroma-red hover:text-white hover:border-chroma-red transition-all">
					<i class="fa-solid fa-xmark text-lg"></i>
				</button>
			</div>
		</div>

		<!-- Iframe Container -->
		<div class="flex-grow relative bg-white">
			<div id="chroma-job-loader" class="absolute inset-0 flex items-center justify-center bg-white z-10">
				<div class="w-12 h-12 border-4 border-chroma-red/20 border-t-chroma-red rounded-full animate-spin">
				</div>
			</div>
			<iframe id="chroma-job-frame" src="" class="w-full h-full border-0"
				title="Job Application Frame"
				allow="camera; microphone; autoplay; encrypted-media;"></iframe>
		</div>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		const modal = document.getElementById('chroma-job-modal');
		const backdrop = document.getElementById('chroma-job-backdrop');
		const closeBtn = document.getElementById('chroma-job-close');
		const iframe = document.getElementById('chroma-job-frame');
		const externalLink = document.getElementById('chroma-job-external');
		const loader = document.getElementById('chroma-job-loader');

		function openModal(url) {
			modal.classList.remove('hidden');
			document.body.style.overflow = 'hidden';
			loader.classList.remove('hidden');
			iframe.src = url;
			externalLink.href = url;
			iframe.onload = function () {
				loader.classList.add('hidden');
			};
		}

		function closeModal() {
			modal.classList.add('hidden');
			document.body.style.overflow = '';
			iframe.src = '';
		}

		// Attach listeners to job triggers
		const triggers = document.querySelectorAll('.job-modal-trigger');
		triggers.forEach(trigger => {
			trigger.addEventListener('click', function (e) {
				const url = this.getAttribute('href');
				if (url && url.startsWith('http')) {
					e.preventDefault();
					openModal(url);
				}
			});
		});

		// Close actions
		if (closeBtn) closeBtn.addEventListener('click', closeModal);
		if (backdrop) backdrop.addEventListener('click', closeModal);
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
				closeModal();
			}
		});
	});
</script>

<?php
get_footer();

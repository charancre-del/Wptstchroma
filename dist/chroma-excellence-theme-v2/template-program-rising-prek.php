<?php
/**
 * Template Name: Rising Pre-K Summer Template
 * Template Post Type: program
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

$rising_prek_preload_url = 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?q=80&w=1200&auto=format&fit=crop';

add_action(
	'wp_head',
	static function () use ($rising_prek_preload_url) {
		echo '<link rel="preload" as="image" href="' . esc_url($rising_prek_preload_url) . '" fetchpriority="high">' . "\n";
	},
	2
);

get_header();

?>
<main id="primary" class="site-main single-program custom-program-v2" role="main">
<?php

while (have_posts()):
	the_post();

	$hero_title = __('The perfect stepping stone to Pre-K.', 'chroma-excellence');
	$hero_description = __('Transitioning into a structured Pre-K environment is a massive developmental leap. Our <strong>Rising Pre-K Summer Program</strong> is an intensive, joyful 6-week bridge designed to build classroom routines, social-emotional resilience, and early literacy before the fall semester begins.', 'chroma-excellence');
	$hero_image_markup = sprintf(
		'<img src="%1$s" alt="%2$s" class="w-full h-full object-cover" fetchpriority="high" loading="eager" decoding="async" sizes="(min-width: 1024px) 640px, 100vw" />',
		esc_url($rising_prek_preload_url),
		esc_attr__('Teacher and preschooler engaged in a joyful learning activity', 'chroma-excellence')
	);

	$funding_cards = array(
		array(
			'icon_class' => 'fa-solid fa-landmark-dome',
			'icon_color' => 'text-chroma-blue',
			'title' => __('Georgia DECAL Summer Transition', 'chroma-excellence'),
			'copy' => __('Funded by the Georgia Department of Early Care and Learning, this track is <strong>completely free</strong> for eligible families. It is specifically designed for dual language learners and families meeting specific income requirements to ensure educational equity.', 'chroma-excellence'),
			'points' => array(
				__('100% Tuition Covered', 'chroma-excellence'),
				__('Meals & materials included', 'chroma-excellence'),
				__('6-week intensive schedule', 'chroma-excellence'),
			),
			'accent_class' => 'border-chroma-blue',
			'link_text' => __('Check Eligibility', 'chroma-excellence'),
			'link_color' => 'text-chroma-blue',
		),
		array(
			'icon_class' => 'fa-solid fa-seedling',
			'icon_color' => 'text-chroma-yellow',
			'title' => __('Chroma Private Transition', 'chroma-excellence'),
			'copy' => __('For families who do not meet state DECAL requirements but still want their child to experience the profound benefits of a summer prep program, our Private Track offers identical classroom experiences, curriculum, and expert teaching.', 'chroma-excellence'),
			'points' => array(
				__('Identical Prismpath™ curriculum', 'chroma-excellence'),
				__('Low teacher-to-student ratios', 'chroma-excellence'),
				__('Flexible wrap-around care options', 'chroma-excellence'),
			),
			'accent_class' => 'border-chroma-yellow',
			'link_text' => __('Request Private Tuition Rates', 'chroma-excellence'),
			'link_color' => 'text-chroma-yellow',
		),
	);

	$curriculum_cards = array(
		array(
			'icon_class' => 'fa-solid fa-users',
			'icon_bg' => 'bg-chroma-green/20',
			'icon_color' => 'text-chroma-green',
			'title' => __('Social-Emotional Readiness', 'chroma-excellence'),
			'copy' => __('Learning to share, taking turns, expressing emotions safely, and separating from parents with confidence and joy.', 'chroma-excellence'),
		),
		array(
			'icon_class' => 'fa-solid fa-list-check',
			'icon_bg' => 'bg-chroma-yellow/20',
			'icon_color' => 'text-chroma-yellow',
			'title' => __('Classroom Routines', 'chroma-excellence'),
			'copy' => __('Mastering the flow of a school day: sitting for circle time, transitioning between centers, washing hands, and following two-step directions.', 'chroma-excellence'),
		),
		array(
			'icon_class' => 'fa-solid fa-shapes',
			'icon_bg' => 'bg-chroma-red/20',
			'icon_color' => 'text-chroma-red',
			'title' => __('Foundational Academics', 'chroma-excellence'),
			'copy' => __('Name recognition, fine motor development (holding crayons/scissors safely), counting concepts, and letter exposure.', 'chroma-excellence'),
		),
	);

	$contact_url = function_exists('chroma_get_page_link')
		? chroma_get_page_link('contact')
		: home_url('/contact-us/');
	$contact_url = chroma_get_localized_url($contact_url);
	$chart_values = function_exists('chroma_program_prism_chart_values')
		? chroma_program_prism_chart_values(get_the_ID())
		: array(60, 90, 95, 60, 70);
	?>

	<style>
		.rising-prek-hero-panel {
			background:
				radial-gradient(circle at top right, rgba(227, 235, 232, 0.95), transparent 50%),
				linear-gradient(135deg, #ffffff 0%, #fffaf3 100%);
		}

		.rising-prek-shell {
			position: relative;
			overflow: hidden;
		}

		.rising-prek-shell::before {
			content: "";
			position: absolute;
			inset: auto auto -80px -80px;
			width: 220px;
			height: 220px;
			border-radius: 9999px;
			background: rgba(227, 235, 232, 0.9);
			filter: blur(10px);
		}

		.rising-prek-chart-shell {
			min-height: 360px;
		}
	</style>

	<section class="pageHero chroma-v2-page-hero relative pb-20 bg-white overflow-hidden">
		<div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-chroma-greenLight/60 to-transparent" aria-hidden="true"></div>
		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 grid lg:grid-cols-2 gap-12 items-center">
			<div>
				<div class="inline-flex items-center gap-2 bg-white border border-chroma-green/30 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-brand-ink shadow-sm mb-6">
					<i class="fa-solid fa-sun text-chroma-yellow" aria-hidden="true"></i>
					<span><?php esc_html_e('Summer Transition Program', 'chroma-excellence'); ?></span>
				</div>
				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6 leading-tight"><?php echo esc_html($hero_title); ?></h1>
				<p class="text-lg text-brand-ink/80 mb-8 leading-relaxed">
					<?php echo wp_kses_post($hero_description); ?>
				</p>
				<div class="flex flex-wrap gap-4">
					<a href="#funding" class="px-8 py-4 bg-chroma-green text-white text-xs font-bold uppercase tracking-widest rounded-full hover:bg-brand-ink transition-colors shadow-lg">
						<?php esc_html_e('View Funding Options', 'chroma-excellence'); ?>
					</a>
				</div>
			</div>
			<div class="rising-prek-shell relative h-[500px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white rising-prek-hero-panel">
				<?php echo $hero_image_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</section>

	<section id="funding" class="py-20 bg-brand-cream">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center mb-16">
				<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php esc_html_e('Program Tracks', 'chroma-excellence'); ?></span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink"><?php esc_html_e('State-Funded & Private Options', 'chroma-excellence'); ?></h2>
				<p class="text-brand-ink/80 mt-4 max-w-2xl mx-auto"><?php esc_html_e('We believe every child deserves a strong start. To serve our community, Chroma offers two distinct funding tracks for the exact same elite summer curriculum.', 'chroma-excellence'); ?></p>
			</div>

			<div class="grid md:grid-cols-2 gap-8 lg:gap-12">
				<?php foreach ($funding_cards as $card): ?>
					<div class="bg-white p-10 rounded-[3rem] shadow-soft border-t-8 <?php echo esc_attr($card['accent_class']); ?>">
						<div class="text-3xl mb-4 <?php echo esc_attr($card['icon_color']); ?>">
							<i class="<?php echo esc_attr($card['icon_class']); ?>" aria-hidden="true"></i>
						</div>
						<h3 class="font-serif text-2xl font-bold mb-4"><?php echo esc_html($card['title']); ?></h3>
						<p class="text-brand-ink/80 mb-6 leading-relaxed"><?php echo wp_kses_post($card['copy']); ?></p>
						<ul class="space-y-3 mb-8 text-sm text-brand-ink/80">
							<?php foreach ($card['points'] as $point): ?>
								<li class="flex gap-3">
									<i class="fa-solid fa-check text-chroma-green mt-1" aria-hidden="true"></i>
									<span><?php echo esc_html($point); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
						<a href="<?php echo esc_url($contact_url); ?>" class="inline-block font-bold uppercase tracking-widest text-xs hover:text-brand-ink transition-colors <?php echo esc_attr($card['link_color']); ?>">
							<?php echo esc_html($card['link_text']); ?> &rarr;
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="py-24 bg-white border-t border-brand-ink/5">
		<div class="max-w-6xl mx-auto px-4 lg:px-6">
			<div class="grid lg:grid-cols-2 gap-16 items-center">
				<div class="bg-brand-cream rounded-[3rem] p-8 shadow-soft border border-brand-ink/5 order-2 lg:order-1 rising-prek-chart-shell">
					<canvas id="risingPrekChart" role="img" aria-label="<?php echo esc_attr(sprintf(__('Radar chart showing the Rising Pre-K Prismpath focus areas: %1$d%% Physical, %2$d%% Emotional, %3$d%% Social, %4$d%% Academic, and %5$d%% Creative.', 'chroma-excellence'), $chart_values[0], $chart_values[1], $chart_values[2], $chart_values[3], $chart_values[4])); ?>">
						<?php esc_html_e('A radar chart illustrating our curriculum\'s heavy focus on Social and Emotional routines for Pre-K.', 'chroma-excellence'); ?>
					</canvas>
				</div>
				<div class="order-1 lg:order-2">
					<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php esc_html_e('Prismpath™ Focus', 'chroma-excellence'); ?></span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6"><?php esc_html_e('Routines & Resilience.', 'chroma-excellence'); ?></h2>
					<p class="text-brand-ink/80 text-lg mb-6"><?php echo wp_kses_post(__('Transitioning into a structured Pre-K is a big leap. Our summer bridge focuses heavily on <strong>Social</strong> and <strong>Emotional</strong> development, ensuring children learn how to share, follow classroom routines, and separate from parents with confidence.', 'chroma-excellence')); ?></p>
					<ul class="space-y-3 text-sm text-brand-ink/80">
						<li class="flex gap-3 items-center">
							<i class="fa-solid fa-users text-chroma-green" aria-hidden="true"></i>
							<span><strong><?php esc_html_e('Social:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Taking turns, sharing, and group dynamics.', 'chroma-excellence'); ?></span>
						</li>
						<li class="flex gap-3 items-center">
							<i class="fa-solid fa-heart text-chroma-red" aria-hidden="true"></i>
							<span><strong><?php esc_html_e('Emotional:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Safe separation and expressing feelings.', 'chroma-excellence'); ?></span>
						</li>
						<li class="flex gap-3 items-center">
							<i class="fa-solid fa-shapes text-chroma-blue" aria-hidden="true"></i>
							<span><strong><?php esc_html_e('Academic:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Foundational alphabet and number exposure.', 'chroma-excellence'); ?></span>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</section>

	<section class="py-24 bg-chroma-blueDark text-white relative overflow-hidden">
		<div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 32px 32px;" aria-hidden="true"></div>
		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10">
			<div class="text-center mb-16">
				<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php esc_html_e('Curriculum Detail', 'chroma-excellence'); ?></span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold"><?php esc_html_e('What We Teach in 6 Weeks', 'chroma-excellence'); ?></h2>
				<p class="text-white/80 mt-4 max-w-2xl mx-auto"><?php esc_html_e('Our summer curriculum is highly targeted to build the exact skills Pre-K teachers look for on day one.', 'chroma-excellence'); ?></p>
			</div>
			<div class="grid md:grid-cols-3 gap-8">
				<?php foreach ($curriculum_cards as $card): ?>
					<div class="bg-white/5 p-8 rounded-3xl border border-white/10 hover:bg-white/10 transition-colors">
						<div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mb-6 <?php echo esc_attr($card['icon_bg']); ?> <?php echo esc_attr($card['icon_color']); ?>">
							<i class="<?php echo esc_attr($card['icon_class']); ?>" aria-hidden="true"></i>
						</div>
						<h3 class="font-serif text-xl font-bold mb-3"><?php echo esc_html($card['title']); ?></h3>
						<p class="text-sm text-white/80 leading-relaxed"><?php echo esc_html($card['copy']); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<script>
		(function () {
			const initRisingPrekChart = function () {
			const canvas = document.getElementById('risingPrekChart');

			if (!canvas) {
				return;
			}

			let chartInstance = null;
			let chartLoading = false;

			const createChart = function () {
				if (typeof window.Chart === 'undefined' || chartInstance) {
					return;
				}

				chartInstance = new Chart(canvas, {
					type: 'radar',
					data: {
						labels: ['Physical', 'Emotional', 'Social', 'Academic', 'Creative'],
						datasets: [{
							label: 'Rising Pre-K Focus',
							data: [
								<?php echo (int) $chart_values[0]; ?>,
								<?php echo (int) $chart_values[1]; ?>,
								<?php echo (int) $chart_values[2]; ?>,
								<?php echo (int) $chart_values[3]; ?>,
								<?php echo (int) $chart_values[4]; ?>
							],
							backgroundColor: 'rgba(141, 163, 153, 0.2)',
							borderColor: '#8DA399',
							pointBackgroundColor: '#fff',
							pointBorderColor: '#8DA399',
							borderWidth: 2
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						scales: {
							r: {
								suggestedMin: 0,
								suggestedMax: 100,
								ticks: { display: false }
							}
						},
						plugins: {
							legend: { display: false }
						}
					}
				});
			};

			const loadChartLibrary = function () {
				if (typeof window.Chart !== 'undefined') {
					createChart();
					return;
				}

				const existingScript = document.querySelector('script[data-chroma-chartjs]');
				if (existingScript) {
					existingScript.addEventListener('load', createChart, { once: true });
					return;
				}

				if (chartLoading) {
					return;
				}

				chartLoading = true;
				const script = document.createElement('script');
				script.src = '<?php echo esc_url(get_template_directory_uri() . '/assets/js/chart.min.js'); ?>';
				script.async = true;
				script.defer = true;
				script.dataset.chromaChartjs = 'true';
				script.onload = createChart;
				document.body.appendChild(script);
			};

			const loadChartWhenIdle = function () {
				if ('requestIdleCallback' in window) {
					window.requestIdleCallback(loadChartLibrary, { timeout: 1800 });
					return;
				}

				window.setTimeout(loadChartLibrary, 1200);
			};

			if ('IntersectionObserver' in window) {
				const observer = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (!entry.isIntersecting) {
							return;
						}

						observer.disconnect();
						loadChartLibrary();
					});
				}, { rootMargin: '100px 0px' });

				observer.observe(canvas);
				loadChartWhenIdle();
				return;
			}

			loadChartLibrary();
			};

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', initRisingPrekChart, { once: true });
			} else {
				initRisingPrekChart();
			}
		})();
	</script>

<?php endwhile; ?>

</main>

<?php get_footer(); ?>

<?php
/**
 * Template Name: Rising Kindergarten Summer Template
 * Template Post Type: program
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

$rising_k_preload_url = 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?q=80&w=1200&auto=format&fit=crop';

add_action(
	'wp_head',
	static function () use ($rising_k_preload_url) {
		echo '<link rel="preload" as="image" href="' . esc_url($rising_k_preload_url) . '" fetchpriority="high">' . "\n";
	},
	2
);

get_header();

while (have_posts()):
	the_post();

	$hero_title = __('Step into Kindergarten with absolute confidence.', 'chroma-excellence');
	$hero_description = __('Stop the &ldquo;summer slide&rdquo; before it starts. Our <strong>Rising Kindergarten Summer Program</strong> is a specialized 6-week curriculum designed to solidify early reading, mathematical reasoning, and vital executive function skills, ensuring your child is 100% prepared for day one of elementary school.', 'chroma-excellence');
	$hero_image_markup = sprintf(
		'<img src="%1$s" alt="%2$s" class="w-full h-full object-cover" fetchpriority="high" loading="eager" decoding="async" sizes="(min-width: 1024px) 640px, 100vw" />',
		esc_url($rising_k_preload_url),
		esc_attr__('Five-year-old child concentrating while writing letters in a workbook', 'chroma-excellence')
	);

	$funding_cards = array(
		array(
			'icon_class' => 'fa-solid fa-landmark-dome',
			'icon_color' => 'text-chroma-green',
			'title' => __('Georgia DECAL Summer Transition', 'chroma-excellence'),
			'copy' => __('Backed by the Georgia Department of Early Care and Learning, this program provides <strong>free summer tuition</strong> for students entering Kindergarten who need additional academic support before the school year starts, based on state eligibility requirements.', 'chroma-excellence'),
			'points' => array(
				__('100% Tuition Covered', 'chroma-excellence'),
				__('Meals & materials included', 'chroma-excellence'),
				__('6-week intensive schedule', 'chroma-excellence'),
			),
			'accent_class' => 'border-chroma-green',
			'link_text' => __('Check Eligibility', 'chroma-excellence'),
			'link_color' => 'text-chroma-green',
		),
		array(
			'icon_class' => 'fa-solid fa-seedling',
			'icon_color' => 'text-chroma-yellow',
			'title' => __('Chroma Private Transition', 'chroma-excellence'),
			'copy' => __('Available for families who do not qualify for the state-funded program but recognize the immense value of summer academic bridging. Your child receives the exact same elite Kindergarten-prep curriculum, taught by our early education experts.', 'chroma-excellence'),
			'points' => array(
				__('Advanced Literacy & Math Prep', 'chroma-excellence'),
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
			'icon_class' => 'fa-solid fa-spell-check',
			'icon_bg' => 'bg-chroma-blue/20',
			'icon_color' => 'text-chroma-blueLight',
			'title' => __('Literacy & Phonics', 'chroma-excellence'),
			'copy' => __('Transitioning from letter recognition to phonemic awareness. We practice blending sounds, early sight words, and proper pencil grip for handwriting stamina.', 'chroma-excellence'),
		),
		array(
			'icon_class' => 'fa-solid fa-calculator',
			'icon_bg' => 'bg-chroma-red/20',
			'icon_color' => 'text-chroma-redLight',
			'title' => __('Mathematical Reasoning', 'chroma-excellence'),
			'copy' => __('Going beyond rote counting to understand number sense. We explore 1-to-1 correspondence, complex patterning, and basic addition/subtraction concepts.', 'chroma-excellence'),
		),
		array(
			'icon_class' => 'fa-solid fa-brain',
			'icon_bg' => 'bg-chroma-yellow/20',
			'icon_color' => 'text-chroma-yellow',
			'title' => __('Executive Function', 'chroma-excellence'),
			'copy' => __('The secret to K success. We teach self-regulation, how to follow multi-step instructions independently, and how to resolve peer conflicts collaboratively.', 'chroma-excellence'),
		),
	);

	$contact_url = function_exists('chroma_get_page_link')
		? chroma_get_page_link('contact')
		: home_url('/contact-us/');
	$contact_url = chroma_get_localized_url($contact_url);
	$chart_values = function_exists('chroma_program_prism_chart_values')
		? chroma_program_prism_chart_values(get_the_ID())
		: array(50, 75, 80, 95, 60);
	?>

	<style>
		.rising-k-hero-panel {
			background:
				radial-gradient(circle at top right, rgba(227, 233, 236, 0.95), transparent 48%),
				linear-gradient(135deg, #ffffff 0%, #fffaf3 100%);
		}

		.rising-k-shell {
			position: relative;
			overflow: hidden;
		}

		.rising-k-shell::before {
			content: "";
			position: absolute;
			inset: auto auto -80px -80px;
			width: 220px;
			height: 220px;
			border-radius: 9999px;
			background: rgba(141, 163, 153, 0.16);
			filter: blur(10px);
		}

		.rising-k-chart-shell {
			min-height: 360px;
		}
	</style>

	<section class="relative pb-20 bg-white overflow-hidden">
		<div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-chroma-blueLight/60 to-transparent" aria-hidden="true"></div>
		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 grid lg:grid-cols-2 gap-12 items-center">
			<div>
				<div class="inline-flex items-center gap-2 bg-white border border-chroma-blue/30 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-brand-ink shadow-sm mb-6">
					<i class="fa-solid fa-sun text-chroma-yellow" aria-hidden="true"></i>
					<span><?php esc_html_e('Summer Transition Program', 'chroma-excellence'); ?></span>
				</div>
				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6 leading-tight"><?php echo esc_html($hero_title); ?></h1>
				<p class="text-lg text-brand-ink/80 mb-8 leading-relaxed">
					<?php echo wp_kses_post($hero_description); ?>
				</p>
				<div class="flex flex-wrap gap-4">
					<a href="#funding" class="px-8 py-4 bg-chroma-blue text-white text-xs font-bold uppercase tracking-widest rounded-full hover:bg-brand-ink transition-colors shadow-lg">
						<?php esc_html_e('View Funding Options', 'chroma-excellence'); ?>
					</a>
				</div>
			</div>
			<div class="rising-k-shell relative h-[500px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white rising-k-hero-panel">
				<?php echo $hero_image_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</section>

	<section id="funding" class="py-20 bg-brand-cream">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center mb-16">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php esc_html_e('Program Tracks', 'chroma-excellence'); ?></span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink"><?php esc_html_e('State-Funded & Private Options', 'chroma-excellence'); ?></h2>
				<p class="text-brand-ink/80 mt-4 max-w-2xl mx-auto"><?php esc_html_e('We offer two funding tracks for our Rising Kindergarten program, ensuring that high-level summer academic preparation is accessible to our community.', 'chroma-excellence'); ?></p>
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
									<i class="fa-solid fa-check text-chroma-blue mt-1" aria-hidden="true"></i>
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
				<div class="bg-brand-cream rounded-[3rem] p-8 shadow-soft border border-brand-ink/5 order-2 lg:order-1 rising-k-chart-shell">
					<canvas id="risingKinderChart" role="img" aria-label="<?php echo esc_attr(sprintf(__('Radar chart showing the Rising Kindergarten Prismpath focus areas: %1$d%% Physical, %2$d%% Emotional, %3$d%% Social, %4$d%% Academic, and %5$d%% Creative.', 'chroma-excellence'), $chart_values[0], $chart_values[1], $chart_values[2], $chart_values[3], $chart_values[4])); ?>">
						<?php esc_html_e('A radar chart illustrating our curriculum\'s heavy focus on Academic readiness and Executive Function for Kindergarten.', 'chroma-excellence'); ?>
					</canvas>
				</div>
				<div class="order-1 lg:order-2">
					<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php esc_html_e('Prismpath™ Focus', 'chroma-excellence'); ?></span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6"><?php esc_html_e('Bridging the Gap.', 'chroma-excellence'); ?></h2>
					<p class="text-brand-ink/80 text-lg mb-6"><?php echo wp_kses_post(__('Kindergarten moves fast. We spend the summer ensuring your child arrives ahead of the curve, heavily targeting <strong>Academic</strong> mastery and the <strong>Social & Emotional</strong> maturity required for a larger elementary school environment.', 'chroma-excellence')); ?></p>
					<ul class="space-y-3 text-sm text-brand-ink/80">
						<li class="flex gap-3 items-center">
							<i class="fa-solid fa-book-open text-chroma-blue" aria-hidden="true"></i>
							<span><strong><?php esc_html_e('Academic:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Phonics, blending, and math reasoning.', 'chroma-excellence'); ?></span>
						</li>
						<li class="flex gap-3 items-center">
							<i class="fa-solid fa-users text-chroma-green" aria-hidden="true"></i>
							<span><strong><?php esc_html_e('Social:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Collaborative learning and peer conflict resolution.', 'chroma-excellence'); ?></span>
						</li>
						<li class="flex gap-3 items-center">
							<i class="fa-solid fa-brain text-chroma-yellow" aria-hidden="true"></i>
							<span><strong><?php esc_html_e('Emotional:', 'chroma-excellence'); ?></strong> <?php esc_html_e('Executive function, focus, and multi-step directions.', 'chroma-excellence'); ?></span>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</section>

	<section class="py-24 bg-brand-ink text-white relative overflow-hidden">
		<div class="absolute inset-0 opacity-5" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 32px 32px;" aria-hidden="true"></div>
		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10">
			<div class="text-center mb-16">
				<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php esc_html_e('Curriculum Detail', 'chroma-excellence'); ?></span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold"><?php esc_html_e('What We Master in 6 Weeks', 'chroma-excellence'); ?></h2>
				<p class="text-white/80 mt-4 max-w-2xl mx-auto"><?php esc_html_e('We spend the summer ensuring your child arrives ahead of the curve, equipped with vital academic and self-regulation skills.', 'chroma-excellence'); ?></p>
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
		document.addEventListener('DOMContentLoaded', function () {
			const canvas = document.getElementById('risingKinderChart');

			if (!canvas) {
				return;
			}

			const createChart = function () {
				if (typeof Chart === 'undefined') {
					return;
				}

				new Chart(canvas, {
					type: 'radar',
					data: {
						labels: ['Physical', 'Emotional', 'Social', 'Academic', 'Creative'],
						datasets: [{
							label: 'Rising K Focus',
							data: [
								<?php echo (int) $chart_values[0]; ?>,
								<?php echo (int) $chart_values[1]; ?>,
								<?php echo (int) $chart_values[2]; ?>,
								<?php echo (int) $chart_values[3]; ?>,
								<?php echo (int) $chart_values[4]; ?>
							],
							backgroundColor: 'rgba(74, 108, 124, 0.2)',
							borderColor: '#4A6C7C',
							pointBackgroundColor: '#fff',
							pointBorderColor: '#4A6C7C',
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
				if (typeof Chart !== 'undefined') {
					createChart();
					return;
				}

				const script = document.createElement('script');
				script.src = '<?php echo esc_url(get_template_directory_uri() . '/assets/js/chart.min.js'); ?>';
				script.async = true;
				script.onload = createChart;
				document.body.appendChild(script);
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
				return;
			}

			loadChartLibrary();
		});
	</script>

<?php endwhile; ?>

<?php get_footer(); ?>

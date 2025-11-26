<?php
/**
 * Single Program Template
 *
 * @package Chroma_Excellence
 */

get_header();

while ( have_posts() ) :
	the_post();
	$program_id = get_the_ID();

	// Get program meta
	$age_range = get_post_meta( $program_id, 'program_age_range', true );
	$color_scheme = get_post_meta( $program_id, 'program_color_scheme', true ) ?: 'red';

	// Hero section
	$hero_title = get_post_meta( $program_id, 'program_hero_title', true ) ?: get_the_title();
	$hero_description = get_post_meta( $program_id, 'program_hero_description', true ) ?: get_the_excerpt();

	// Prismpath section
	$prism_title = get_post_meta( $program_id, 'program_prism_title', true ) ?: 'Our Prismpath™ Focus';
	$prism_description = get_post_meta( $program_id, 'program_prism_description', true );
	$prism_focus_items = get_post_meta( $program_id, 'program_prism_focus_items', true );

	// Chart data
	$prism_physical = get_post_meta( $program_id, 'program_prism_physical', true ) ?: '50';
	$prism_emotional = get_post_meta( $program_id, 'program_prism_emotional', true ) ?: '50';
	$prism_social = get_post_meta( $program_id, 'program_prism_social', true ) ?: '50';
	$prism_academic = get_post_meta( $program_id, 'program_prism_academic', true ) ?: '50';
	$prism_creative = get_post_meta( $program_id, 'program_prism_creative', true ) ?: '50';

	// Schedule
	$schedule_title = get_post_meta( $program_id, 'program_schedule_title', true ) ?: 'A Rhythm, Not a Routine';
	$schedule_items = get_post_meta( $program_id, 'program_schedule_items', true );

	// Color mapping
	$color_map = array(
		'red'      => array( 'main' => 'chroma-red', 'light' => 'chroma-redLight' ),
		'blue'     => array( 'main' => 'chroma-blue', 'light' => 'chroma-blueLight' ),
		'yellow'   => array( 'main' => 'chroma-yellow', 'light' => 'chroma-yellowLight' ),
		'blueDark' => array( 'main' => 'chroma-blueDark', 'light' => 'chroma-blueLight' ),
		'green'    => array( 'main' => 'chroma-green', 'light' => 'chroma-greenLight' ),
	);

	$colors = $color_map[ $color_scheme ] ?? $color_map['red'];

	// Get featured image
	$hero_image = get_the_post_thumbnail_url( $program_id, 'large' );
	if ( ! $hero_image ) {
		$hero_image = 'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?q=80&w=800&auto=format&fit=crop';
	}
?>

<main>
	<!-- Hero -->
	<section class="relative pt-20 pb-20 bg-white overflow-hidden">
		<div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-<?php echo esc_attr( $colors['light'] ); ?>/30 to-transparent"></div>
		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 grid lg:grid-cols-2 gap-12 items-center">
			<div class="fade-in-up">
				<?php if ( $age_range ) : ?>
					<div class="inline-flex items-center gap-2 bg-white border border-<?php echo esc_attr( $colors['main'] ); ?>/30 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-<?php echo esc_attr( $colors['main'] ); ?> shadow-sm mb-6">
						<?php echo esc_html( $age_range ); ?>
					</div>
				<?php endif; ?>

				<h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6">
					<?php echo esc_html( $hero_title ); ?>
				</h1>

				<?php if ( $hero_description ) : ?>
					<p class="text-lg text-brand-ink/70 mb-8 max-w-lg">
						<?php echo wp_kses_post( wpautop( $hero_description ) ); ?>
					</p>
				<?php endif; ?>

				<div class="flex gap-4">
					<a href="#prism" class="px-8 py-4 bg-<?php echo esc_attr( $colors['main'] ); ?> text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:opacity-90 transition-colors shadow-lg">View Curriculum</a>
					<a href="<?php echo esc_url( home_url( '/programs' ) ); ?>" class="px-8 py-4 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:border-<?php echo esc_attr( $colors['main'] ); ?> hover:text-<?php echo esc_attr( $colors['main'] ); ?> transition-colors">
						All Programs
					</a>
				</div>
			</div>

			<div class="relative h-[500px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white fade-in-up" style="animation-delay: 0.2s;">
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large', array(
						'class' => 'w-full h-full object-cover',
						'alt'   => get_the_title(),
					) ); ?>
				<?php else : ?>
					<img src="<?php echo esc_url( $hero_image ); ?>" class="w-full h-full object-cover" alt="<?php echo esc_attr( get_the_title() ); ?>" />
				<?php endif; ?>
			</div>
		</div>
	</section>

	<!-- The Prismpath Focus (Chart) -->
	<section id="prism" class="py-24 bg-brand-cream">
		<div class="max-w-6xl mx-auto px-4 lg:px-6">
			<div class="grid lg:grid-cols-2 gap-16 items-center">
				<div class="bg-white rounded-[3rem] p-8 shadow-soft border border-brand-ink/5 order-2 lg:order-1">
					<canvas id="programChart"></canvas>
				</div>
				<div class="order-1 lg:order-2">
					<span class="text-<?php echo esc_attr( $colors['main'] ); ?> font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Prismpath™ Focus</span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
						<?php echo esc_html( $prism_title ); ?>
					</h2>

					<?php if ( $prism_description ) : ?>
						<div class="text-brand-ink/70 text-lg mb-6">
							<?php echo wp_kses_post( wpautop( $prism_description ) ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $prism_focus_items ) :
						$focus_items_array = array_filter( array_map( 'trim', explode( "\n", $prism_focus_items ) ) );
						if ( ! empty( $focus_items_array ) ) :
					?>
						<ul class="space-y-3 text-sm text-brand-ink/80">
							<?php
							$item_colors = array( 'chroma-red', 'chroma-yellow', 'chroma-green', 'chroma-blue', 'brand-ink/20' );
							foreach ( $focus_items_array as $index => $item ) :
								$item_color = $item_colors[ $index % count( $item_colors ) ];
							?>
								<li class="flex gap-3 items-center">
									<span class="w-3 h-3 rounded-full bg-<?php echo esc_attr( $item_color ); ?>"></span>
									<?php echo esc_html( $item ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; endif; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- Schedule -->
	<?php if ( $schedule_items ) :
		$schedule_items_array = array_filter( array_map( 'trim', explode( "\n", $schedule_items ) ) );
		if ( ! empty( $schedule_items_array ) ) :
	?>
	<section id="schedule" class="py-24 bg-white">
		<div class="max-w-4xl mx-auto px-4 lg:px-6">
			<h2 class="text-3xl font-serif font-bold text-center mb-12"><?php echo esc_html( $schedule_title ); ?></h2>
			<div class="space-y-8 relative before:absolute before:left-8 before:top-4 before:bottom-4 before:w-0.5 before:bg-brand-ink/10">
				<?php foreach ( $schedule_items_array as $item ) :
					$parts = explode( '|', $item );
					if ( count( $parts ) >= 3 ) :
						$badge = trim( $parts[0] );
						$title = trim( $parts[1] );
						$description = trim( $parts[2] );
				?>
					<div class="flex gap-8 items-start relative">
						<div class="w-16 h-16 rounded-full bg-<?php echo esc_attr( $colors['light'] ); ?> text-<?php echo esc_attr( $colors['main'] ); ?> font-bold flex items-center justify-center shrink-0 z-10 border-4 border-white shadow-sm text-xs">
							<?php echo esc_html( $badge ); ?>
						</div>
						<div class="pt-3">
							<h3 class="font-bold text-lg text-brand-ink"><?php echo esc_html( $title ); ?></h3>
							<p class="text-brand-ink/60"><?php echo esc_html( $description ); ?></p>
						</div>
					</div>
				<?php endif; endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; endif; ?>

	<!-- CTA Section -->
	<section class="py-20 bg-brand-cream">
		<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
			<h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-6">Ready to learn more?</h2>
			<p class="text-brand-ink/70 mb-10">Schedule a tour to see our <?php echo esc_html( get_the_title() ); ?> program in action and meet our dedicated teachers.</p>
			<div class="flex flex-wrap justify-center gap-4">
				<a href="<?php echo esc_url( home_url( '/locations' ) ); ?>" class="px-8 py-4 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:border-<?php echo esc_attr( $colors['main'] ); ?> hover:text-<?php echo esc_attr( $colors['main'] ); ?> transition-colors">
					Find a Location
				</a>
				<a href="#tour" class="px-8 py-4 bg-<?php echo esc_attr( $colors['main'] ); ?> text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:opacity-90 transition-colors shadow-lg">
					Schedule a Tour
				</a>
			</div>
		</div>
	</section>
</main>

<style>
	.fade-in-up { animation: fadeInUp 0.8s ease forwards; opacity: 0; transform: translateY(20px); }
	@keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
</style>

<script>
	// Prismpath Chart Config
	document.addEventListener('DOMContentLoaded', function() {
		const ctx = document.getElementById('programChart');
		if (ctx) {
			new Chart(ctx, {
				type: 'radar',
				data: {
					labels: ['Physical', 'Emotional', 'Social', 'Academic', 'Creative'],
					datasets: [{
						label: '<?php echo esc_js( get_the_title() ); ?> Focus',
						data: [
							<?php echo absint( $prism_physical ); ?>,
							<?php echo absint( $prism_emotional ); ?>,
							<?php echo absint( $prism_social ); ?>,
							<?php echo absint( $prism_academic ); ?>,
							<?php echo absint( $prism_creative ); ?>
						],
						backgroundColor: 'rgba(214, 125, 107, 0.2)',
						borderColor: '<?php
							$chart_colors = array(
								'red' => '#D67D6B',
								'blue' => '#4A6C7C',
								'yellow' => '#E6BE75',
								'blueDark' => '#2F4858',
								'green' => '#8DA399',
							);
							echo $chart_colors[ $color_scheme ] ?? '#D67D6B';
						?>',
						pointBackgroundColor: '#fff',
						pointBorderColor: '<?php echo $chart_colors[ $color_scheme ] ?? '#D67D6B'; ?>',
						borderWidth: 2
					}]
				},
				options: {
					scales: {
						r: {
							angleLines: { color: '#e5e5e5' },
							grid: { color: '#e5e5e5' },
							pointLabels: { font: { family: 'Outfit', size: 14 }, color: '#263238' },
							suggestedMin: 0,
							suggestedMax: 100,
							ticks: { display: false }
						}
					},
					plugins: { legend: { display: false } }
				}
			});
		}
	});
</script>

<?php
endwhile;
get_footer();

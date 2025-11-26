<?php
/**
 * Programs Archive Template
 *
 * @package Chroma_Excellence
 */

get_header();

// Get all programs
$programs_query = new WP_Query( array(
	'post_type'      => 'program',
	'posts_per_page' => -1,
	'orderby'        => 'menu_order',
	'order'          => 'ASC',
) );
?>

<main>
	<!-- Hero Section -->
	<section class="relative pt-16 pb-12 lg:pt-24 lg:pb-20 bg-white overflow-hidden">
		<div class="absolute top-0 right-0 w-1/2 h-full bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-chroma-redLight/40 via-transparent to-transparent"></div>

		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 text-center">
			<div class="inline-flex items-center gap-2 bg-white border border-chroma-red/30 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-chroma-red shadow-sm mb-6 fade-in-up">
				<i class="fa-solid fa-shapes"></i> Ages 6 weeks to 12 years
			</div>
			<h1 class="font-serif text-[2.8rem] md:text-6xl text-brand-ink mb-6 fade-in-up delay-100">
				A curriculum that grows <span class="text-chroma-red italic">with them.</span>
			</h1>
			<p class="text-lg text-brand-ink/60 max-w-2xl mx-auto mb-10 fade-in-up delay-200">
				From sensory discovery in our infant suites to the project-based learning of Pre-K, every program uses our proprietary Prismpath™ model to meet children exactly where they are.
			</p>
		</div>
	</section>

	<!-- Programs Grid -->
	<section id="all-programs" class="py-20 bg-brand-cream">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<?php if ( $programs_query->have_posts() ) : ?>
				<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
					<?php
					$delay_class = '';
					$delay_counter = 0;
					while ( $programs_query->have_posts() ) :
						$programs_query->the_post();

						// Get program meta
						$age_range = get_post_meta( get_the_ID(), 'program_age_range', true );
						$features = get_post_meta( get_the_ID(), 'program_features', true );
						$cta_text = get_post_meta( get_the_ID(), 'program_cta_text', true ) ?: 'Schedule Tour';
						$cta_link = get_post_meta( get_the_ID(), 'program_cta_link', true ) ?: '#tour';
						$color_scheme = get_post_meta( get_the_ID(), 'program_color_scheme', true ) ?: 'red';

						// Parse features into array
						$features_array = $features ? array_filter( array_map( 'trim', explode( "\n", $features ) ) ) : array();

						// Get featured image
						$thumbnail_url = get_the_post_thumbnail_url( get_the_ID(), 'large' );
						if ( ! $thumbnail_url ) {
							$thumbnail_url = 'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?q=80&w=600&auto=format&fit=crop';
						}

						// Set delay class for staggered animation
						$delay_classes = array( '', 'delay-100', 'delay-200' );
						$delay_class = $delay_classes[ $delay_counter % 3 ];
						$delay_counter++;

						// Color mapping
						$color_map = array(
							'red'      => array( 'main' => 'chroma-red', 'light' => 'chroma-red/10', 'border' => 'chroma-red/30' ),
							'blue'     => array( 'main' => 'chroma-blue', 'light' => 'chroma-blue/10', 'border' => 'chroma-blue/30' ),
							'yellow'   => array( 'main' => 'chroma-yellow', 'light' => 'chroma-yellow/10', 'border' => 'chroma-yellow/30' ),
							'blueDark' => array( 'main' => 'chroma-blueDark', 'light' => 'chroma-blueDark/10', 'border' => 'chroma-blueDark/30' ),
							'green'    => array( 'main' => 'chroma-green', 'light' => 'chroma-green/10', 'border' => 'chroma-green/30' ),
						);

						$colors = $color_map[ $color_scheme ] ?? $color_map['red'];
					?>

					<!-- Program Card -->
					<div class="group bg-white rounded-[2.5rem] p-8 shadow-card border border-brand-ink/5 hover:border-<?php echo esc_attr( $colors['border'] ); ?> transition-all hover:-translate-y-1 flex flex-col h-full fade-in-up <?php echo esc_attr( $delay_class ); ?>">
						<div class="h-48 rounded-[2rem] overflow-hidden mb-6 relative">
							<div class="absolute inset-0 bg-<?php echo esc_attr( $colors['light'] ); ?> group-hover:bg-transparent transition-colors duration-500 z-10"></div>
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'large', array(
									'class' => 'w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700',
									'alt'   => get_the_title(),
								) ); ?>
							<?php else : ?>
								<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700" />
							<?php endif; ?>

							<?php if ( $age_range ) : ?>
								<div class="absolute top-4 right-4 z-20 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide text-<?php echo esc_attr( $colors['main'] ); ?>">
									<?php echo esc_html( $age_range ); ?>
								</div>
							<?php endif; ?>
						</div>

						<h3 class="font-serif text-2xl font-bold text-brand-ink mb-2"><?php the_title(); ?></h3>

						<p class="text-sm text-brand-ink/60 mb-6 flex-grow">
							<?php echo has_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 20 ); ?>
						</p>

						<?php if ( ! empty( $features_array ) ) : ?>
							<ul class="text-xs text-brand-ink/70 space-y-2 mb-6">
								<?php foreach ( $features_array as $feature ) : ?>
									<li class="flex gap-2">
										<i class="fa-solid fa-check text-chroma-green"></i>
										<?php echo esc_html( $feature ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>

						<a href="<?php echo esc_url( $cta_link ); ?>" class="w-full py-3 rounded-xl border border-brand-ink/10 text-brand-ink text-xs font-bold uppercase tracking-wider text-center hover:bg-<?php echo esc_attr( $colors['main'] ); ?> hover:text-white hover:border-<?php echo esc_attr( $colors['main'] ); ?> transition-colors">
							<?php echo esc_html( $cta_text ); ?>
						</a>
					</div>

					<?php endwhile; wp_reset_postdata(); ?>
				</div>
			<?php else : ?>
				<div class="text-center py-20">
					<p class="text-brand-ink/60 text-lg">No programs found. Please add programs from the WordPress admin.</p>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- Curriculum Highlight -->
	<section id="curriculum" class="py-24 bg-white border-t border-brand-ink/5">
		<div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-16 items-center">
			<div class="order-2 lg:order-1 relative">
				<div class="absolute -top-10 -left-10 w-72 h-72 bg-chroma-blue/10 rounded-full blur-3xl"></div>
				<div class="relative bg-chroma-blueDark text-white rounded-[3rem] p-10 lg:p-12 shadow-2xl overflow-hidden">
					<div class="absolute top-0 right-0 p-12 opacity-10 text-9xl"><i class="fa-brands fa-connectdevelop"></i></div>
					<h3 class="font-serif text-3xl font-bold mb-6 relative z-10">The Prismpath™ Model</h3>
					<p class="text-white/80 text-lg leading-relaxed mb-8 relative z-10">
						Just as a prism refracts light into a full spectrum of color, our proprietary curriculum refracts play into five key pillars of development.
					</p>
					<ul class="space-y-4 relative z-10">
						<li class="flex items-center gap-4">
							<span class="w-8 h-8 rounded-full bg-chroma-red flex items-center justify-center text-xs font-bold">1</span>
							<span>Physical & Sensory Health</span>
						</li>
						<li class="flex items-center gap-4">
							<span class="w-8 h-8 rounded-full bg-chroma-yellow flex items-center justify-center text-xs font-bold text-brand-ink">2</span>
							<span>Emotional Intelligence</span>
						</li>
						<li class="flex items-center gap-4">
							<span class="w-8 h-8 rounded-full bg-chroma-green flex items-center justify-center text-xs font-bold">3</span>
							<span>Social Connection</span>
						</li>
						<li class="flex items-center gap-4">
							<span class="w-8 h-8 rounded-full bg-chroma-blue flex items-center justify-center text-xs font-bold">4</span>
							<span>Academic Logic</span>
						</li>
						<li class="flex items-center gap-4">
							<span class="w-8 h-8 rounded-full bg-white text-brand-ink flex items-center justify-center text-xs font-bold">5</span>
							<span>Creative Expression</span>
						</li>
					</ul>
				</div>
			</div>
			<div class="order-1 lg:order-2">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Our Methodology</span>
				<h2 class="text-3xl md:text-5xl font-serif font-bold text-brand-ink mb-6">More than just daycare.</h2>
				<p class="text-brand-ink/70 text-lg leading-relaxed mb-8">
					We believe that education isn't just about filling a bucket, but lighting a fire. Our curriculum ensures that by the time your child graduates from Chroma, they are not just "school ready"—they are life ready.
				</p>
				<div class="grid grid-cols-2 gap-6">
					<div class="bg-brand-cream p-6 rounded-2xl border border-brand-ink/5">
						<div class="text-3xl mb-2">🧠</div>
						<h4 class="font-bold text-brand-ink mb-1">Cognitive Growth</h4>
						<p class="text-xs text-brand-ink/60">Critical thinking & problem solving.</p>
					</div>
					<div class="bg-brand-cream p-6 rounded-2xl border border-brand-ink/5">
						<div class="text-3xl mb-2">❤️</div>
						<h4 class="font-bold text-brand-ink mb-1">Emotional IQ</h4>
						<p class="text-xs text-brand-ink/60">Empathy, regulation & kindness.</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- CTA Section -->
	<section class="py-20 bg-brand-cream">
		<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
			<h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-6">Ready to find your fit?</h2>
			<p class="text-brand-ink/70 mb-10">Every campus offers tours so you can meet the teachers, see the classrooms, and experience the Chroma culture firsthand.</p>
			<div class="flex flex-wrap justify-center gap-4">
				<a href="<?php echo esc_url( home_url( '/locations' ) ); ?>" class="px-8 py-4 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:border-chroma-blue hover:text-chroma-blue transition-colors">Find a Location</a>
				<a href="#tour" class="px-8 py-4 bg-chroma-red text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:bg-chroma-red/90 transition-colors shadow-lg">Schedule a Tour</a>
			</div>
		</div>
	</section>
</main>

<style>
	.fade-in-up { animation: fadeInUp 0.8s ease forwards; opacity: 0; transform: translateY(20px); }
	.delay-100 { animation-delay: 0.1s; }
	.delay-200 { animation-delay: 0.2s; }
	.delay-300 { animation-delay: 0.3s; }
	@keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
</style>

<?php
get_footer();

<?php
/**
 * Single Location Template
 *
 * @package Chroma_Excellence
 */

get_header();

while ( have_posts() ) :
	the_post();
	$location_fields = chroma_get_location_fields();
	$location_id     = get_the_ID();
	$location_name   = get_the_title();

	// Get location meta
	$phone    = $location_fields['phone'];
	$email    = $location_fields['email'];
	$address  = chroma_location_address_line();
	$city     = $location_fields['city'];
	$state    = $location_fields['state'];
	$zip      = $location_fields['zip'];
	$lat      = $location_fields['latitude'];
	$lng      = $location_fields['longitude'];

	// Additional meta fields (with defaults)
	$hero_subtitle     = get_post_meta( $location_id, 'location_hero_subtitle', true ) ?: "Now Enrolling: Pre-K & Toddlers";
	$tagline          = get_post_meta( $location_id, 'location_tagline', true ) ?: "{$city}'s home for brilliant beginnings.";
	$description      = get_post_meta( $location_id, 'location_description', true ) ?: get_the_excerpt();
	$google_rating    = get_post_meta( $location_id, 'location_google_rating', true ) ?: '4.9';
	$hours            = get_post_meta( $location_id, 'location_hours', true ) ?: '7am - 6pm';
	$ages_served      = get_post_meta( $location_id, 'location_ages_served', true ) ?: '6w - 12y';

	// Director info
	$director_name      = get_post_meta( $location_id, 'location_director_name', true );
	$director_bio       = get_post_meta( $location_id, 'location_director_bio', true );
	$director_photo     = get_post_meta( $location_id, 'location_director_photo', true );
	$director_signature = get_post_meta( $location_id, 'location_director_signature', true );

	// Maps embed
	$maps_embed = get_post_meta( $location_id, 'location_maps_embed', true );

	// Tour booking link
	$tour_booking_link = get_post_meta( $location_id, 'location_tour_booking_link', true );

	// School pickups
	$school_pickups = get_post_meta( $location_id, 'location_school_pickups', true );

	// SEO content
	$seo_content_title = get_post_meta( $location_id, 'location_seo_content_title', true );
	$seo_content_text = get_post_meta( $location_id, 'location_seo_content_text', true );

	// Get programs at this location
	$programs_query = new WP_Query( array(
		'post_type'      => 'program',
		'posts_per_page' => 6,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => 'program_locations',
				'value'   => '"' . $location_id . '"',
				'compare' => 'LIKE',
			),
		),
	) );
?>

<main>
	<!-- Hero Section -->
	<section class="relative pt-12 pb-24 lg:pt-20 lg:pb-32 overflow-hidden">
		<!-- Background Shapes -->
		<div class="absolute top-0 right-0 w-2/3 h-full bg-gradient-to-l from-chroma-blue/5 to-transparent -z-10"></div>
		<div class="absolute -top-24 left-10 w-96 h-96 bg-chroma-yellow/10 rounded-full blur-3xl -z-10"></div>

		<div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-16 items-center">
			<div class="fade-in-up">
				<div class="inline-flex items-center gap-2 bg-white border border-chroma-blue/15 px-3 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-semibold text-brand-ink/70 shadow-sm mb-6">
					<span class="w-2 h-2 rounded-full bg-chroma-green animate-pulse"></span>
					<?php echo esc_html( $hero_subtitle ); ?>
				</div>

				<h1 class="font-serif text-[2.8rem] sm:text-[3.5rem] leading-none text-brand-ink mb-6">
					<?php
					$parts = explode( ' ', $tagline );
					if ( count( $parts ) > 3 ) {
						$last_words = array_slice( $parts, -2 );
						$first_words = array_slice( $parts, 0, count($parts) - 2 );
						echo esc_html( implode( ' ', $first_words ) ) . ' <span class="italic text-chroma-blue">' . esc_html( implode( ' ', $last_words ) ) . '</span>';
					} else {
						echo esc_html( $tagline );
					}
					?>
				</h1>

				<p class="text-lg text-brand-ink/70 mb-8 max-w-xl leading-relaxed">
					<?php echo esc_html( $description ); ?>
				</p>

				<div class="flex flex-wrap gap-4 mb-10">
					<a href="#tour" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-chroma-blue text-white text-xs font-bold uppercase tracking-[0.2em] shadow-soft hover:bg-chroma-blueDark transition-all hover:-translate-y-1">
						Schedule Visit
					</a>
					<?php if ( $phone ) : ?>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $phone ) ); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full border border-brand-ink/10 bg-white text-brand-ink text-xs font-bold uppercase tracking-[0.2em] hover:border-chroma-blue hover:text-chroma-blue transition-all">
							<?php echo esc_html( $phone ); ?>
						</a>
					<?php endif; ?>
				</div>

				<!-- Quick Stats -->
				<div class="grid grid-cols-3 gap-6 border-t border-brand-ink/5 pt-8">
					<div>
						<div class="text-2xl font-serif font-bold text-chroma-red mb-1"><?php echo esc_html( $ages_served ); ?></div>
						<div class="text-[10px] uppercase tracking-wider text-brand-ink/50 font-semibold">Ages Served</div>
					</div>
					<div>
						<div class="text-2xl font-serif font-bold text-chroma-yellow mb-1"><?php echo esc_html( $google_rating ); ?></div>
						<div class="text-[10px] uppercase tracking-wider text-brand-ink/50 font-semibold">Google Rating</div>
					</div>
					<div>
						<div class="text-2xl font-serif font-bold text-chroma-green mb-1"><?php echo esc_html( $hours ); ?></div>
						<div class="text-[10px] uppercase tracking-wider text-brand-ink/50 font-semibold">Mon - Fri</div>
					</div>
				</div>
			</div>

			<!-- Hero Image -->
			<div class="relative fade-in-up delay-200 hidden md:block">
				<div class="absolute inset-0 bg-chroma-blue/10 rounded-[3rem] rotate-6 transform translate-x-4 translate-y-4"></div>
				<div class="relative rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white aspect-[4/3]">
					<?php if ( has_post_thumbnail() ) : ?>
						<?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-full object-cover' ) ); ?>
					<?php else : ?>
                                                <img src="https://images.unsplash.com/photo-1587654780291-39c9404d746b?q=80&w=1000&auto=format&fit=crop" alt="<?php echo esc_attr( $location_name ); ?> Campus" class="w-full h-full object-cover" loading="lazy" decoding="async" width="1000" height="750" />
                                        <?php endif; ?>

					<!-- Floating Review Badge -->
					<div class="absolute bottom-6 left-6 bg-white/95 backdrop-blur-sm p-5 rounded-2xl shadow-float max-w-[200px]">
						<div class="flex items-center gap-1 mb-2">
							<?php for ( $i = 0; $i < 5; $i++ ) : ?>
								<i class="fa-solid fa-star text-chroma-yellow text-sm"></i>
							<?php endfor; ?>
						</div>
						<p class="text-xs font-serif italic text-brand-ink/80">"The best decision we made for our daughter. The teachers are incredible."</p>
						<p class="text-[10px] font-bold text-brand-ink/40 mt-2 uppercase tracking-wide">— Parent Review</p>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Campus Highlights -->
	<section id="about" class="py-20 bg-white">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center mb-16 max-w-3xl mx-auto">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Campus Features</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-4">Designed for discovery.</h2>
				<p class="text-brand-ink/60">Every corner of our <?php echo esc_html( $city ); ?> campus is intentional—from the soft lighting in our infant suites to the collaborative stations in our Pre-K classrooms.</p>
			</div>

			<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
				<!-- Feature 1 -->
				<div class="group p-8 rounded-[2rem] bg-brand-cream border border-chroma-blue/10 hover:border-chroma-blue/30 transition-all hover:-translate-y-1">
					<div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-chroma-blue text-xl mb-6 group-hover:scale-110 transition-transform">
						<i class="fa-solid fa-shield-halved"></i>
					</div>
					<h3 class="font-serif text-xl font-bold text-brand-ink mb-3">Secure Access</h3>
					<p class="text-sm text-brand-ink/70 leading-relaxed">Keypad entry, 24/7 video monitoring, and a staffed front desk ensure your child is always safe.</p>
				</div>

				<!-- Feature 2 -->
				<div class="group p-8 rounded-[2rem] bg-brand-cream border border-chroma-blue/10 hover:border-chroma-red/30 transition-all hover:-translate-y-1">
					<div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-chroma-red text-xl mb-6 group-hover:scale-110 transition-transform">
						<i class="fa-solid fa-tree"></i>
					</div>
					<h3 class="font-serif text-xl font-bold text-brand-ink mb-3">Nature Playground</h3>
					<p class="text-sm text-brand-ink/70 leading-relaxed">Our oversized, shaded outdoor space features gardening beds, trike paths, and natural sensory zones.</p>
				</div>

				<!-- Feature 3 -->
				<div class="group p-8 rounded-[2rem] bg-brand-cream border border-chroma-blue/10 hover:border-chroma-yellow/30 transition-all hover:-translate-y-1">
					<div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-chroma-yellow text-xl mb-6 group-hover:scale-110 transition-transform">
						<i class="fa-solid fa-flask"></i>
					</div>
					<h3 class="font-serif text-xl font-bold text-brand-ink mb-3">STEM Atelier</h3>
					<p class="text-sm text-brand-ink/70 leading-relaxed">A dedicated studio for science experiments, light table exploration, and early engineering projects.</p>
				</div>

				<!-- Feature 4 -->
				<div class="group p-8 rounded-[2rem] bg-brand-cream border border-chroma-blue/10 hover:border-chroma-green/30 transition-all hover:-translate-y-1">
					<div class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-chroma-green text-xl mb-6 group-hover:scale-110 transition-transform">
						<i class="fa-solid fa-graduation-cap"></i>
					</div>
					<h3 class="font-serif text-xl font-bold text-brand-ink mb-3">GA Lottery Pre-K</h3>
					<p class="text-sm text-brand-ink/70 leading-relaxed">We are a proud partner of the Georgia Pre-K Program, offering tuition-free education for 4-year-olds.</p>
				</div>
			</div>
		</div>
	</section>

	<?php if ( $director_name ) : ?>
	<!-- Director's Welcome -->
	<section id="director" class="py-20 bg-chroma-blueDark text-white relative overflow-hidden">
		<div class="absolute right-0 top-0 w-1/2 h-full bg-white/5 skew-x-12 transform origin-top-right"></div>

		<div class="max-w-6xl mx-auto px-4 lg:px-6 relative z-10 grid md:grid-cols-[300px,1fr] gap-12 items-center">
			<?php if ( $director_photo ) : ?>
				<div class="relative">
					<div class="absolute inset-0 bg-chroma-yellow rounded-[2.5rem] rotate-3"></div>
					<img src="<?php echo esc_url( $director_photo ); ?>" alt="<?php echo esc_attr( $director_name ); ?>" class="relative rounded-[2.5rem] w-full object-cover shadow-2xl grayscale hover:grayscale-0 transition-all duration-500" />
				</div>
			<?php endif; ?>

			<div>
				<span class="text-chroma-yellow font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Meet the Director</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold mb-6">Welcome to Chroma <?php echo esc_html( $city ); ?>.</h2>
				<div class="space-y-4 text-white/80 text-lg leading-relaxed mb-8">
					<?php echo wpautop( wp_kses_post( $director_bio ) ); ?>
				</div>
				<div class="flex items-center gap-4">
					<?php if ( $director_signature ) : ?>
						<img src="<?php echo esc_url( $director_signature ); ?>" alt="<?php echo esc_attr( $director_name ); ?> signature" class="h-16 w-auto opacity-80" />
					<?php endif; ?>
					<div class="text-xs uppercase tracking-wider opacity-60">
						<p class="font-bold"><?php echo esc_html( $director_name ); ?></p>
						<p>Campus Director</p>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- Programs Grid -->
	<?php if ( $programs_query->have_posts() ) : ?>
	<section id="programs" class="py-24 bg-brand-cream">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
				<div>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-3">Programs at this location</h2>
					<p class="text-brand-ink/70">Curriculum tailored to the specific developmental window of your child.</p>
				</div>
				<a href="<?php echo esc_url( chroma_get_program_archive_url() ); ?>" class="text-chroma-blue font-bold text-sm uppercase tracking-wider hover:text-chroma-blueDark flex items-center gap-2">
					View Curriculum Details <i class="fa-solid fa-arrow-right"></i>
				</a>
			</div>

			<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
				<?php
				$color_map = array(
					'infant'      => array( 'bg' => 'chroma-redLight', 'text' => 'chroma-red', 'border' => 'chroma-red/30' ),
					'toddler'     => array( 'bg' => 'chroma-blueLight', 'text' => 'chroma-blue', 'border' => 'chroma-blue/30' ),
					'preschool'   => array( 'bg' => 'chroma-yellowLight', 'text' => 'chroma-yellow', 'border' => 'chroma-yellow/30' ),
					'prek'        => array( 'bg' => 'chroma-greenLight', 'text' => 'chroma-green', 'border' => 'chroma-green/30' ),
					'afterschool' => array( 'bg' => 'chroma-blueLight', 'text' => 'chroma-blue', 'border' => 'chroma-blue/30' ),
				);

				while ( $programs_query->have_posts() ) : $programs_query->the_post();
					$program_fields = chroma_get_program_fields();
					$age_range      = $program_fields['age_range'];
					$excerpt        = $program_fields['excerpt'] ?: get_the_excerpt();
					$icon           = $program_fields['icon'];
					$slug           = get_post_field( 'post_name' );
					$colors         = $color_map[ $slug ] ?? $color_map['toddler'];
				?>
					<div class="bg-white rounded-3xl p-6 shadow-card border border-brand-ink/5 hover:border-<?php echo esc_attr( $colors['border'] ); ?> transition group">
						<div class="flex justify-between items-start mb-4">
							<?php if ( $age_range ) : ?>
								<span class="bg-<?php echo esc_attr( $colors['bg'] ); ?> text-<?php echo esc_attr( $colors['text'] ); ?> px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide">
									<?php echo esc_html( $age_range ); ?>
								</span>
							<?php endif; ?>
							<span class="text-2xl group-hover:scale-110 transition-transform"><?php echo esc_html( mb_substr( $icon, 0, 2 ) ); ?></span>
						</div>
						<h3 class="font-serif text-xl font-bold text-brand-ink mb-2"><?php the_title(); ?></h3>
						<p class="text-sm text-brand-ink/60 mb-6"><?php echo esc_html( $excerpt ); ?></p>
						<a href="<?php the_permalink(); ?>" class="text-xs font-bold text-<?php echo esc_attr( $colors['text'] ); ?> uppercase tracking-wider hover:underline">
							Learn More <i class="fa-solid fa-arrow-right text-[10px]"></i>
						</a>
					</div>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- Tour / Contact Section -->
	<section id="contact" class="py-24 bg-white relative">
		<div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-16">

			<!-- Info Side -->
			<div>
				<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Visit Us</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">Come see the magic in person.</h2>
				<p class="text-brand-ink/70 mb-8">
					Tours are the best way to feel the Chroma difference.
					<?php
					// Parse opening and closing times from hours field
					$tour_text = ' We are available for tours Monday through Friday';
					if ( $hours ) {
						// Try to parse hours like "7am - 6pm" or "7:00am - 6:00pm"
						if ( preg_match( '/([0-9]{1,2}(?::[0-9]{2})?\s*[ap]m)\s*[-–—]\s*([0-9]{1,2}(?::[0-9]{2})?\s*[ap]m)/i', $hours, $matches ) ) {
							$opening_time = trim( $matches[1] );
							$closing_time = trim( $matches[2] );
							$tour_text .= ' between ' . esc_html( $opening_time ) . ' and ' . esc_html( $closing_time );
						}
					}
					$tour_text .= '. We welcome little ones to accompany on a tour!';
					echo $tour_text;
					?>
				</p>

				<div class="space-y-6">
					<?php if ( $address ) : ?>
					<div class="flex gap-4">
						<div class="w-12 h-12 rounded-full bg-brand-cream flex items-center justify-center text-chroma-blue text-lg shrink-0">
							<i class="fa-solid fa-location-dot"></i>
						</div>
						<div>
							<h4 class="font-bold text-brand-ink">Address</h4>
							<p class="text-sm text-brand-ink/70">
								<?php echo esc_html( $address ); ?><br>
								<?php echo esc_html( "$city, $state $zip" ); ?>
							</p>
							<?php if ( $lat && $lng ) : ?>
								<a href="https://www.google.com/maps/search/?api=1&query=<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lng ); ?>" target="_blank" class="text-xs font-bold text-chroma-blue uppercase mt-1 inline-block">
									Get Directions
								</a>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( $phone || $email ) : ?>
					<div class="flex gap-4">
						<div class="w-12 h-12 rounded-full bg-brand-cream flex items-center justify-center text-chroma-blue text-lg shrink-0">
							<i class="fa-solid fa-phone"></i>
						</div>
						<div>
							<h4 class="font-bold text-brand-ink">Contact</h4>
							<p class="text-sm text-brand-ink/70">
								<?php if ( $phone ) : ?>
									Phone: <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $phone ) ); ?>" class="hover:text-chroma-blue"><?php echo esc_html( $phone ); ?></a><br>
								<?php endif; ?>
								<?php if ( $email ) : ?>
									Email: <a href="mailto:<?php echo esc_attr( $email ); ?>" class="hover:text-chroma-blue"><?php echo esc_html( $email ); ?></a>
								<?php endif; ?>
							</p>
						</div>
					</div>
					<?php endif; ?>

					<div class="flex gap-4">
						<div class="w-12 h-12 rounded-full bg-brand-cream flex items-center justify-center text-chroma-blue text-lg shrink-0">
							<i class="fa-solid fa-clock"></i>
						</div>
						<div>
							<h4 class="font-bold text-brand-ink">Hours of Operation</h4>
							<p class="text-sm text-brand-ink/70">
								Monday - Friday: <?php echo esc_html( $hours ); ?><br>
								Weekends: Closed
							</p>
						</div>
					</div>

					<?php if ( $school_pickups ) :
						$schools = array_filter( array_map( 'trim', explode( "\n", $school_pickups ) ) );
						if ( ! empty( $schools ) ) :
					?>
					<div class="flex gap-4">
						<div class="w-12 h-12 rounded-full bg-brand-cream flex items-center justify-center text-chroma-blue text-lg shrink-0">
							<i class="fa-solid fa-bus"></i>
						</div>
						<div>
							<h4 class="font-bold text-brand-ink">School Pickups</h4>
							<p class="text-sm text-brand-ink/70">We provide pickup service to:</p>
							<ul class="text-sm text-brand-ink/70 mt-2 space-y-1">
								<?php foreach ( $schools as $school ) : ?>
									<li class="flex items-start gap-2">
										<i class="fa-solid fa-check text-chroma-green text-xs mt-1"></i>
										<span><?php echo esc_html( $school ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
					<?php endif; endif; ?>
				</div>

				<!-- Map Embed -->
				<?php if ( $maps_embed ) : ?>
					<div class="mt-10">
						<div class="w-full h-80 rounded-3xl overflow-hidden shadow-card border border-brand-ink/10">
							<?php echo wp_kses( $maps_embed, array(
								'iframe' => array(
									'src'             => array(),
									'width'           => array(),
									'height'          => array(),
									'frameborder'     => array(),
									'style'           => array(),
									'allowfullscreen' => array(),
									'loading'         => array(),
									'referrerpolicy'  => array(),
									'title'           => array(),
								),
							) ); ?>
						</div>
					</div>
				<?php elseif ( $lat && $lng ) : ?>
					<div class="mt-10">
						<div
							data-chroma-map
							data-chroma-locations='[{"lat":<?php echo esc_attr( $lat ); ?>,"lng":<?php echo esc_attr( $lng ); ?>,"name":"<?php echo esc_js( get_the_title() ); ?>","city":"<?php echo esc_js( $city ); ?>","url":"<?php echo esc_url( get_permalink() ); ?>"}]'
							class="w-full h-80 rounded-3xl overflow-hidden shadow-card border border-brand-ink/10"
						></div>
					</div>
				<?php endif; ?>
			</div>

			<!-- Form Side -->
			<div id="tour" class="bg-brand-cream p-8 md:p-10 rounded-[2.5rem] shadow-soft border border-chroma-blue/10 h-fit sticky top-28">
				<h3 class="font-serif text-2xl font-bold text-brand-ink mb-2">Request a Tour</h3>
				<p class="text-sm text-brand-ink/60 mb-6">Fill out the form below and we'll contact you to confirm a time.</p>

				<?php echo do_shortcode( '[chroma_tour_form location_id="' . $location_id . '"]' ); ?>

				<?php if ( $tour_booking_link ) : ?>
					<div class="mt-6 text-center">
						<div class="flex items-center gap-4 mb-4">
							<div class="flex-1 h-px bg-brand-ink/10"></div>
							<span class="text-sm text-brand-ink/40 font-medium uppercase tracking-wider">or</span>
							<div class="flex-1 h-px bg-brand-ink/10"></div>
						</div>
						<a href="<?php echo esc_url( $tour_booking_link ); ?>" target="_blank" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-chroma-green text-white text-xs font-bold uppercase tracking-[0.2em] shadow-soft hover:bg-chroma-greenDark transition-all hover:-translate-y-1">
							Book a Tour Now
						</a>
					</div>
				<?php endif; ?>
			</div>

		</div>
	</section>

	<?php if ( $seo_content_title || $seo_content_text ) : ?>
	<!-- Location SEO Content Section -->
	<section class="py-24 bg-brand-cream relative">
		<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
			<?php if ( $seo_content_title ) : ?>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
					<?php echo esc_html( $seo_content_title ); ?>
				</h2>
			<?php endif; ?>

			<?php if ( $seo_content_text ) : ?>
				<div class="text-lg text-brand-ink/80 leading-relaxed max-w-3xl mx-auto">
					<?php echo wp_kses_post( wpautop( $seo_content_text ) ); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

</main>

<?php
endwhile;
get_footer();

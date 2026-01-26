<?php
/**
 * Single Location Template
 *
 * @package Chroma_Excellence
 */

get_header();

while (have_posts()):
	the_post();
	$location_fields = chroma_get_location_fields();
	$location_id = get_the_ID();
	$location_name = get_the_title();

	// Get location meta
	$phone = $location_fields['phone'];
	$email = $location_fields['email'];
	$address = chroma_location_address_line();
	$city = $location_fields['city'];
	$state = $location_fields['state'];
	$zip = $location_fields['zip'];
	$lat = $location_fields['latitude'];
	$lng = $location_fields['longitude'];
	$license_number = $location_fields['license_number'];

	// Additional meta fields (with defaults)
	$hero_subtitle = chroma_get_translated_meta($location_id, 'location_hero_subtitle') ?: __('Now Enrolling: Pre-K & Toddlers', 'chroma-excellence');
	$hero_gallery_raw = chroma_get_translated_meta($location_id, 'location_hero_gallery');
	$virtual_tour_embed = chroma_get_translated_meta($location_id, 'location_virtual_tour_embed');
	$tagline = chroma_get_translated_meta($location_id, 'location_tagline') ?: sprintf(__("%s's home for brilliant beginnings.", 'chroma-excellence'), $city);
	$description = chroma_get_translated_meta($location_id, 'location_description') ?: get_the_content();

	// Parse hero gallery URLs (one per line)
	$hero_gallery = array();
	if (!empty($hero_gallery_raw)) {
		$lines = explode("\n", $hero_gallery_raw);
		foreach ($lines as $line) {
			$url = trim($line);
			if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
				$hero_gallery[] = esc_url($url);
			}
		}
	}
	$google_rating = chroma_get_translated_meta($location_id, 'location_google_rating') ?: '4.9';
	$hours = chroma_get_translated_meta($location_id, 'location_hours') ?: __('7am - 6pm', 'chroma-excellence');
	$ages_served = chroma_get_translated_meta($location_id, 'location_ages_served') ?: __('6w - 12y', 'chroma-excellence');

	// Director info
	$director_name = chroma_get_translated_meta($location_id, 'location_director_name');
	$director_heading = chroma_get_translated_meta($location_id, 'location_director_heading');
	$director_bio = chroma_get_translated_meta($location_id, 'location_director_bio');
	$director_photo = chroma_get_translated_meta($location_id, 'location_director_photo');
	$director_signature = chroma_get_translated_meta($location_id, 'location_director_signature');

	// Maps embed
	$maps_embed = chroma_get_translated_meta($location_id, 'location_maps_embed');

	// Tour booking link
	$tour_booking_link = chroma_get_translated_meta($location_id, 'location_tour_booking_link');

	// School pickups
	$school_pickups = chroma_get_translated_meta($location_id, 'location_school_pickups');

	// SEO content
	$seo_content_title = chroma_get_translated_meta($location_id, 'location_seo_content_title');
	$seo_content_text = chroma_get_translated_meta($location_id, 'location_seo_content_text');

	// Get programs at this location
	$programs_query = new WP_Query(array(
		'post_type' => 'program',
		'posts_per_page' => -1,
		'orderby' => 'menu_order',
		'order' => 'ASC',
		'no_found_rows' => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => false,
		'meta_query' => array(
			array(
				'key' => 'program_locations',
				'value' => '(^|;)i:' . intval($location_id) . ';',
				'compare' => 'REGEXP',
			),
		),
	));

	// Get Region Colors
	$location_regions = wp_get_post_terms($location_id, 'location_region');
	$region_term = !empty($location_regions) && !is_wp_error($location_regions) ? $location_regions[0] : null;
	$region_colors = $region_term ? chroma_get_region_color_from_term($region_term->term_id) : array(
		'bg' => 'chroma-blueLight',
		'text' => 'chroma-blue',
		'border' => 'chroma-blue',
	);
	?>

	<main>
		<!-- Hero Section -->
		<section class="relative pt-12 pb-24 lg:pt-20 lg:pb-32 overflow-hidden">
			<!-- Background Shapes -->
			<div
				class="absolute top-0 right-0 w-2/3 h-full bg-gradient-to-l from-<?php echo esc_attr($region_colors['border']); ?>/5 to-transparent -z-10">
			</div>
			<div
				class="absolute -top-24 left-10 w-96 h-96 bg-<?php echo esc_attr($region_colors['border']); ?>/10 rounded-full blur-3xl -z-10">
			</div>

			<div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-16 items-center">
				<div class="fade-in-up">
					<div
						class="inline-flex items-center gap-2 bg-white border border-<?php echo esc_attr($region_colors['border']); ?>/15 px-3 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-semibold text-brand-ink/80 shadow-sm mb-6">
						<span
							class="w-2 h-2 rounded-full bg-<?php echo esc_attr($region_colors['text']); ?> animate-pulse"></span>
						<?php echo esc_html($hero_subtitle); ?>
					</div>

					<h1 class="font-serif text-[2.8rem] sm:text-[3.5rem] leading-none text-brand-ink mb-4">
						<?php echo esc_html($location_name); ?>
					</h1>
					<p class="font-serif text-2xl italic text-<?php echo esc_attr($region_colors['text']); ?> mb-6">
						<?php echo esc_html($tagline); ?>
					</p>

					<div class="text-lg text-brand-ink/90 mb-8 max-w-xl leading-relaxed">
						<?php echo wp_kses_post(wpautop($description)); ?>
					</div>

					<?php if ($license_number): ?>
						<div class="mb-8 flex items-center gap-2 text-sm font-semibold text-brand-ink/60">
							<i class="fa-solid fa-certificate text-<?php echo esc_attr($region_colors['text']); ?>"></i>
							<span><?php _e('DECAL License #:', 'chroma-excellence'); ?>
								<?php echo esc_html($license_number); ?></span>
						</div>
					<?php endif; ?>

					<div class="flex flex-wrap gap-4 mb-10">
						<a href="#tour"
							class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-<?php echo esc_attr($region_colors['text']); ?> text-white text-xs font-bold uppercase tracking-[0.2em] shadow-soft hover:bg-chroma-blueDark transition-all hover:-translate-y-1">
							<?php _e('Schedule Visit', 'chroma-excellence'); ?>
						</a>
						<?php if ($phone): ?>
							<a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone)); ?>"
								class="inline-flex items-center justify-center px-8 py-4 rounded-full border border-brand-ink/10 bg-white text-brand-ink text-xs font-bold uppercase tracking-[0.2em] hover:border-<?php echo esc_attr($region_colors['border']); ?> hover:text-<?php echo esc_attr($region_colors['text']); ?> transition-all">
								<?php echo esc_html($phone); ?>
							</a>
						<?php endif; ?>
					</div>

					<!-- Quick Stats -->
					<div class="grid grid-cols-3 gap-6 border-t border-brand-ink/5 pt-8">
						<div>
							<div class="text-2xl font-serif font-bold text-chroma-red mb-1">
								<?php echo esc_html($ages_served); ?>
							</div>
							<div class="text-[10px] uppercase tracking-wider text-brand-ink/80 font-semibold">
								<?php _e('Ages Served', 'chroma-excellence'); ?>
							</div>
						</div>
						<div>
							<div class="text-2xl font-serif font-bold text-chroma-yellow mb-1">
								<?php echo esc_html($google_rating); ?>
							</div>
							<div class="text-[10px] uppercase tracking-wider text-brand-ink/80 font-semibold">
								<?php _e('Google Rating', 'chroma-excellence'); ?>
							</div>
						</div>
						<div>
							<div class="text-2xl font-serif font-bold text-chroma-green mb-1">
								<?php echo esc_html($hours); ?>
							</div>
							<div class="text-[10px] uppercase tracking-wider text-brand-ink/80 font-semibold">
								<?php _e('Mon - Fri', 'chroma-excellence'); ?>
							</div>
						</div>
					</div>
				</div>

				<!-- Hero Image / Carousel -->
				<div class="relative fade-in-up delay-200 block">
					<div
						class="absolute inset-0 bg-<?php echo esc_attr($region_colors['text']); ?>/10 rounded-[3rem] rotate-6 transform translate-x-4 translate-y-4">
					</div>
					<div class="relative rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white aspect-square md:aspect-[4/3]"
						<?php if (count($hero_gallery) > 1)
							echo 'data-location-carousel'; ?>>
						<?php if (!empty($hero_gallery)): ?>
							<!-- Gallery Carousel -->
							<div class="relative w-full h-full">
								<div class="flex transition-transform duration-500 ease-in-out h-full"
									data-location-carousel-track>
									<?php foreach ($hero_gallery as $index => $image_url):
										// Try to get attachment ID to serve responsive images
										$attachment_id = attachment_url_to_postid($image_url);
										?>
										<div class="w-full h-full flex-shrink-0"
											data-location-slide="<?php echo esc_attr($index); ?>">
											<?php if ($attachment_id):
												echo wp_get_attachment_image($attachment_id, 'large', false, array(
													'class' => 'w-full h-full object-cover',
													'fetchpriority' => $index === 0 ? 'high' : 'auto',
													'loading' => $index === 0 ? 'eager' : 'lazy',
													'decoding' => 'async',
													'sizes' => '(max-width: 768px) 100vw, 50vw'
												));
											else: ?>
												<img src="<?php echo esc_url($image_url); ?>"
													alt="<?php echo esc_attr($location_name); ?> - Image <?php echo esc_attr($index + 1); ?>"
													class="w-full h-full object-cover" decoding="async"
													sizes="(max-width: 768px) 100vw, 50vw" <?php if ($index === 0)
														echo 'fetchpriority="high"';
													else
														echo 'loading="lazy"'; ?> />
											<?php endif; ?>
										</div>
									<?php endforeach; ?>
								</div>

								<?php if (count($hero_gallery) > 1): ?>
									<!-- Navigation Arrows -->
									<button
										class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center bg-white/90 rounded-full shadow-lg text-brand-ink hover:bg-white transition"
										data-location-prev aria-label="Previous image">
										<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
												d="M15 19l-7-7 7-7" />
										</svg>
									</button>
									<button
										class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 flex items-center justify-center bg-white/90 rounded-full shadow-lg text-brand-ink hover:bg-white transition"
										data-location-next aria-label="Next image">
										<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
												d="M9 5l7 7-7 7" />
										</svg>
									</button>

									<!-- Dots -->
									<div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2" data-location-dots>
										<?php foreach ($hero_gallery as $index => $image_url): ?>
											<button
												class="w-2 h-2 rounded-full transition-all <?php echo 0 === $index ? 'bg-white w-6' : 'bg-white/50'; ?>"
												data-location-dot="<?php echo esc_attr($index); ?>"
												aria-label="Go to image <?php echo esc_attr($index + 1); ?>"></button>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
						<?php elseif (has_post_thumbnail()): ?>
							<?php the_post_thumbnail('large', array('class' => 'w-full h-full object-cover', 'fetchpriority' => 'high', 'sizes' => '(max-width: 768px) 100vw, 50vw')); ?>
						<?php else:
							// Unsplash fallback with srcset
							$base_unsplash = "https://images.unsplash.com/photo-1587654780291-39c9404d746b?q=80&auto=format&fit=crop";
							$src_mobile = $base_unsplash . "&w=600&h=600";
							$src_desktop = $base_unsplash . "&w=1000&h=750";
							?>
							<img src="<?php echo esc_url($src_desktop); ?>"
								srcset="<?php echo esc_url($src_mobile); ?> 600w, <?php echo esc_url($src_desktop); ?> 1000w"
								sizes="(max-width: 768px) 100vw, 50vw" alt="<?php echo esc_attr($location_name); ?> Campus"
								class="w-full h-full object-cover" fetchpriority="high" decoding="async" width="1000"
								height="750" />
						<?php endif; ?>

						<!-- Floating Review Badge -->
						<?php
						$hero_review_text = chroma_get_translated_meta(get_the_ID(), 'location_hero_review_text');
						$hero_review_author = chroma_get_translated_meta(get_the_ID(), 'location_hero_review_author') ?: __('Parent Review', 'chroma-excellence');

						if ($hero_review_text):
							?>
							<div
								class="hidden lg:block absolute bottom-6 left-6 bg-white/95 backdrop-blur-sm p-5 rounded-2xl shadow-float max-w-[200px] fade-in-up delay-300 z-20">
								<div class="flex items-center gap-1 mb-2">
									<?php for ($i = 0; $i < 5; $i++): ?>
										<i class="fa-solid fa-star text-chroma-yellow text-sm"></i>
									<?php endfor; ?>
								</div>
								<p class="text-xs font-serif italic text-brand-ink/90">
									"<?php echo esc_html($hero_review_text); ?>"
								</p>
								<p class="text-[10px] font-bold text-brand-ink mt-2 uppercase tracking-wide">—
									<?php echo esc_html($hero_review_author); ?>
								</p>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>

		<!-- Campus Highlights -->
		<section id="about" class="py-20 bg-white">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-16 max-w-3xl mx-auto">
					<span
						class="text-<?php echo esc_attr($region_colors['text']); ?> font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Campus Features', 'chroma-excellence'); ?></span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-4">
						<?php _e('Designed for discovery.', 'chroma-excellence'); ?></h2>
					<p class="text-brand-ink/90">
						<?php printf(__('Every corner of our %s campus is intentional—from the soft lighting in our infant suites to the collaborative stations in our Pre-K classrooms.', 'chroma-excellence'), esc_html($city)); ?>
					</p>
				</div>

				<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
					<!-- Feature 1 -->
					<div
						class="group p-8 rounded-[2rem] bg-brand-cream border border-chroma-blue/10 hover:border-chroma-blue/30 transition-all hover:-translate-y-1">
						<div
							class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-chroma-blue text-xl mb-6 group-hover:scale-110 transition-transform">
							<i class="fa-solid fa-shield-halved"></i>
						</div>
						<h3 class="font-serif text-xl font-bold text-brand-ink mb-3">
							<?php _e('Secure Access', 'chroma-excellence'); ?></h3>
						<p class="text-sm text-brand-ink/90 leading-relaxed">
							<?php _e('Keypad entry, 24/7 video monitoring, and a staffed front desk ensure your child is always safe.', 'chroma-excellence'); ?>
						</p>
					</div>

					<!-- Feature 2 -->
					<div
						class="group p-8 rounded-[2rem] bg-brand-cream border border-chroma-blue/10 hover:border-chroma-red/30 transition-all hover:-translate-y-1">
						<div
							class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-chroma-red text-xl mb-6 group-hover:scale-110 transition-transform">
							<i class="fa-solid fa-tree"></i>
						</div>
						<h3 class="font-serif text-xl font-bold text-brand-ink mb-3">
							<?php _e('Nature Playground', 'chroma-excellence'); ?></h3>
						<p class="text-sm text-brand-ink/80 leading-relaxed">
							<?php _e('Our oversized, shaded outdoor space features gardening beds, trike paths, and natural sensory zones.', 'chroma-excellence'); ?>
						</p>
					</div>

					<!-- Feature 3 -->
					<div
						class="group p-8 rounded-[2rem] bg-brand-cream border border-chroma-blue/10 hover:border-chroma-yellow/30 transition-all hover:-translate-y-1">
						<div
							class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-chroma-yellow text-xl mb-6 group-hover:scale-110 transition-transform">
							<i class="fa-solid fa-flask"></i>
						</div>
						<h3 class="font-serif text-xl font-bold text-brand-ink mb-3">
							<?php _e('STEM Atelier', 'chroma-excellence'); ?></h3>
						<p class="text-sm text-brand-ink/80 leading-relaxed">
							<?php _e('A dedicated studio for science experiments, light table exploration, and early engineering projects.', 'chroma-excellence'); ?>
						</p>
					</div>

					<!-- Feature 4 -->
					<div
						class="group p-8 rounded-[2rem] bg-brand-cream border border-chroma-blue/10 hover:border-chroma-green/30 transition-all hover:-translate-y-1">
						<div
							class="w-12 h-12 bg-white rounded-xl shadow-sm flex items-center justify-center text-chroma-green text-xl mb-6 group-hover:scale-110 transition-transform">
							<i class="fa-solid fa-graduation-cap"></i>
						</div>
						<h3 class="font-serif text-xl font-bold text-brand-ink mb-3">
							<?php _e('GA Lottery Pre-K', 'chroma-excellence'); ?></h3>
						<p class="text-sm text-brand-ink/80 leading-relaxed">
							<?php _e('We are a proud partner of the Georgia Pre-K Program, offering tuition-free education for 4-year-olds.', 'chroma-excellence'); ?>
						</p>
					</div>
				</div>
			</div>
		</section>

		<?php if ($director_name): ?>
			<!-- Director's Welcome -->
			<section id="director" class="py-20 bg-chroma-blueDark text-white relative overflow-hidden">
				<div class="absolute right-0 top-0 w-1/2 h-full bg-white/5 skew-x-12 transform origin-top-right"></div>

				<div
					class="max-w-6xl mx-auto px-4 lg:px-6 relative z-10 <?php echo $director_photo ? 'grid md:grid-cols-[1fr,2fr] gap-12 items-center' : 'max-w-4xl'; ?>">
					<?php if ($director_photo): ?>
						<div class="relative">
							<div
								class="absolute inset-0 bg-<?php echo esc_attr($region_colors['text']); ?> rounded-[2.5rem] rotate-3">
							</div>
							<img src="<?php echo esc_url($director_photo); ?>" alt="<?php echo esc_attr($director_name); ?>"
								class="relative rounded-[2.5rem] w-full object-cover shadow-2xl grayscale transition-all duration-1000 ease-out"
								data-reveal-color />
						</div>
					<?php endif; ?>

					<div>
						<span
							class="text-<?php echo esc_attr($region_colors['text']); ?> font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Meet the Director', 'chroma-excellence'); ?></span>
						<h2 class="text-3xl md:text-4xl font-serif font-bold mb-6">
							<?php
							echo $director_heading ?: sprintf(__('Welcome to Chroma %s.', 'chroma-excellence'), esc_html($city));
							?>
						</h2>
						<div class="space-y-4 text-white/80 text-lg leading-relaxed mb-8">
							<?php echo wpautop(wp_kses_post($director_bio)); ?>
						</div>
						<div class="flex items-center gap-4">
							<?php if ($director_signature): ?>
								<img src="<?php echo esc_url($director_signature); ?>"
									alt="<?php echo esc_attr($director_name); ?> signature" class="h-16 w-auto opacity-80" />
							<?php endif; ?>
							<div class="text-xs uppercase tracking-wider opacity-60">
								<p class="font-bold"><?php echo esc_html($director_name); ?></p>
								<p><?php _e('Campus Director', 'chroma-excellence'); ?></p>
							</div>
						</div>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if (!empty($virtual_tour_embed)): ?>
			<!-- Virtual Tour -->
			<section id="virtual-tour" class="py-20 bg-white">
				<div class="max-w-6xl mx-auto px-4 lg:px-6">
					<div class="text-center mb-12">
						<span
							class="text-<?php echo esc_attr($region_colors['text']); ?> font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Explore Our Campus', 'chroma-excellence'); ?></span>
						<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-4">
							<?php _e('Take a Virtual Tour', 'chroma-excellence'); ?></h2>
						<p class="text-brand-ink/80 max-w-2xl mx-auto">
							<?php printf(__('Walk through our %s campus from the comfort of your home. Explore our classrooms, outdoor play areas, and learning spaces.', 'chroma-excellence'), esc_html($city)); ?>
						</p>
					</div>

					<div
						class="relative rounded-3xl overflow-hidden shadow-2xl border border-<?php echo esc_attr($region_colors['border']); ?>/10 bg-brand-cream">
						<?php
						// Allow safe HTML tags for embeds (iframe, script)
						$allowed_tags = wp_kses_allowed_html('post');
						$allowed_tags['iframe'] = array(
							'src' => true,
							'width' => true,
							'height' => true,
							'frameborder' => true,
							'allowfullscreen' => true,
							'allow' => true,
							'loading' => true,
							'style' => true,
							'class' => true,
							'title' => true,
						);
						$allowed_tags['script'] = array(
							'src' => true,
							'type' => true,
							'async' => true,
							'defer' => true,
						);

						echo wp_kses($virtual_tour_embed, $allowed_tags);
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- Programs Grid -->
		<?php if ($programs_query->have_posts()): ?>
			<section id="programs" class="py-24 bg-brand-cream">
				<div class="max-w-7xl mx-auto px-4 lg:px-6">
					<div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-6">
						<div>
							<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-3">
								<?php _e('Programs at this location', 'chroma-excellence'); ?>
							</h2>
							<p class="text-brand-ink/80">
								<?php _e('Curriculum tailored to the specific developmental window of your child.', 'chroma-excellence'); ?>
							</p>
						</div>
						<a href="<?php echo esc_url(chroma_get_program_archive_url()); ?>"
							class="text-<?php echo esc_attr($region_colors['text']); ?> font-bold text-sm uppercase tracking-wider hover:text-chroma-blueDark flex items-center gap-2">
							<?php _e('View Curriculum Details', 'chroma-excellence'); ?> <i class="fa-solid fa-arrow-right"></i>
						</a>
					</div>

					<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
						<?php
						$color_map = array(
							'infant' => array('bg' => 'chroma-redLight', 'text' => 'chroma-red', 'border' => 'chroma-red/30'),
							'toddler' => array('bg' => 'chroma-blueLight', 'text' => 'chroma-blue', 'border' => 'chroma-blue/30'),
							'preschool' => array('bg' => 'chroma-yellowLight', 'text' => 'chroma-yellow', 'border' => 'chroma-yellow/30'),
							'prek' => array('bg' => 'chroma-greenLight', 'text' => 'chroma-green', 'border' => 'chroma-green/30'),
							'afterschool' => array('bg' => 'chroma-blueLight', 'text' => 'chroma-blue', 'border' => 'chroma-blue/30'),
						);

						while ($programs_query->have_posts()):
							$programs_query->the_post();
							$program_fields = chroma_get_program_fields();
							$age_range = $program_fields['age_range'];
							$excerpt = $program_fields['excerpt'] ?: ($programs_query->post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($programs_query->post->post_content), 25, '...'));
							$slug = get_post_field('post_name');
							$colors = $color_map[$slug] ?? $color_map['toddler'];
							$prog_img = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
							?>
							<div
								class="bg-white rounded-3xl shadow-card border border-brand-ink/5 hover:border-<?php echo esc_attr($colors['border']); ?> transition group overflow-hidden flex flex-col relative">
								<a href="<?php the_permalink(); ?>" class="absolute inset-0 z-0"
									aria-label="Learn more about <?php the_title_attribute(); ?>"></a>
								<?php if ($prog_img): ?>
									<div class="h-48 overflow-hidden relative z-0">
										<img src="<?php echo esc_url($prog_img); ?>" alt="<?php the_title(); ?>"
											class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
									</div>
								<?php endif; ?>
								<div class="p-6 flex-1 flex flex-col relative z-0">
									<div class="flex justify-between items-start mb-4">
										<?php if ($age_range): ?>
											<span
												class="bg-<?php echo esc_attr($colors['bg']); ?> text-<?php echo esc_attr($colors['text']); ?> px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide">
												<?php echo esc_html($age_range); ?>
											</span>
										<?php endif; ?>
									</div>
									<h3 class="font-serif text-xl font-bold text-brand-ink mb-2"><?php the_title(); ?></h3>
									<p class="text-sm text-brand-ink/90 mb-6 flex-1"><?php echo esc_html($excerpt); ?></p>
									<a href="<?php the_permalink(); ?>"
										class="relative z-10 text-xs font-bold text-<?php echo esc_attr($colors['text']); ?> uppercase tracking-wider hover:underline mt-auto">
										<?php _e('Learn More', 'chroma-excellence'); ?> <i
											class="fa-solid fa-arrow-right text-[10px]"></i>
									</a>
								</div>
							</div>
						<?php endwhile;
						wp_reset_postdata(); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- Testimonials Section -->
		<section class="py-20 bg-white">
			<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
				<span
					class="text-<?php echo esc_attr($region_colors['text']); ?> font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Family Stories', 'chroma-excellence'); ?></span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-8">
					<?php _e('Why Families Love Us', 'chroma-excellence'); ?></h2>
				<blockquote class="text-2xl md:text-3xl font-serif italic text-brand-ink/80 leading-relaxed mb-8">
					"<?php echo esc_html($hero_review_text ?: __("We absolutely love Chroma! The teachers are so caring and my child has learned so much.", 'chroma-excellence')); ?>"
				</blockquote>
				<cite class="not-italic font-bold text-brand-ink uppercase tracking-wider text-sm">
					— <?php echo esc_html($hero_review_author ?: __("Happy Parent", 'chroma-excellence')); ?>
				</cite>
			</div>
		</section>

		<!-- FAQ Section -->
		<section class="py-20 bg-brand-cream border-t border-brand-ink/5">
			<div class="max-w-3xl mx-auto px-4 lg:px-6">
				<div class="text-center mb-12">
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-4">
						<?php _e('Frequently Asked Questions', 'chroma-excellence'); ?>
					</h2>
				</div>

				<div class="space-y-6">
					<?php
					$location_faqs = chroma_get_location_faq_items($location_id);
					foreach ($location_faqs as $item):
						?>
						<div class="bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5">
							<h3 class="font-bold text-brand-ink mb-2"><?php echo esc_html($item['question']); ?></h3>
							<div class="text-brand-ink/80 text-sm leading-relaxed">
								<?php echo wp_kses_post($item['answer']); ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Tour / Contact Section -->
		<section id="contact" class="py-24 bg-white relative">
			<div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-16">

				<!-- Info Side -->
				<div>
					<span
						class="text-<?php echo esc_attr($region_colors['text']); ?> font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Visit Us', 'chroma-excellence'); ?></span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
						<?php _e('Come see the magic in person.', 'chroma-excellence'); ?>
					</h2>
					<p class="text-brand-ink/90 mb-8">
						<?php _e('Tours are the best way to feel the Chroma difference.', 'chroma-excellence'); ?>
						<?php
						// Parse opening and closing times from hours field
						$tour_text = ' ' . __('We are available for tours Monday through Friday', 'chroma-excellence');
						if ($hours) {
							// Try to parse hours like "7am - 6pm" or "7:00am - 6:00pm"
							if (preg_match('/([0-9]{1,2}(?::[0-9]{2})?\s*[ap]m)\s*[-–—]\s*([0-9]{1,2}(?::[0-9]{2})?\s*[ap]m)/i', $hours, $matches)) {
								$opening_time = trim($matches[1]);
								$closing_time = trim($matches[2]);
								$tour_text .= sprintf(__(' between %s and %s', 'chroma-excellence'), esc_html($opening_time), esc_html($closing_time));
							}
						}
						$tour_text .= '. ' . __('We welcome little ones to accompany on a tour!', 'chroma-excellence');
						echo $tour_text;
						?>
					</p>

					<div class="space-y-6">
						<?php if ($address): ?>
							<div class="flex gap-4">
								<div
									class="w-12 h-12 rounded-full bg-brand-cream flex items-center justify-center text-<?php echo esc_attr($region_colors['text']); ?> text-lg shrink-0">
									<i class="fa-solid fa-location-dot"></i>
								</div>
								<div>
									<h3 class="font-bold text-brand-ink"><?php _e('Address', 'chroma-excellence'); ?></h3>
									<p class="text-sm text-brand-ink/80">
										<?php echo esc_html($address); ?><br>
										<?php echo esc_html("$city, $state $zip"); ?>
									</p>
									<?php if ($lat && $lng): ?>
										<a href="https://www.google.com/maps/search/?api=1&query=<?php echo esc_attr($lat); ?>,<?php echo esc_attr($lng); ?>"
											target="_blank"
											class="text-xs font-bold text-<?php echo esc_attr($region_colors['text']); ?> uppercase mt-1 inline-block">
											<?php _e('Get Directions', 'chroma-excellence'); ?>
										</a>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>

						<?php if ($phone || $email): ?>
							<div class="flex gap-4">
								<div
									class="w-12 h-12 rounded-full bg-brand-cream flex items-center justify-center text-<?php echo esc_attr($region_colors['text']); ?> text-lg shrink-0">
									<i class="fa-solid fa-phone"></i>
								</div>
								<div>
									<h3 class="font-bold text-brand-ink"><?php _e('Contact', 'chroma-excellence'); ?></h3>
									<p class="text-sm text-brand-ink/80">
										<?php if ($phone): ?>
											<?php _e('Phone:', 'chroma-excellence'); ?> <a
												href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $phone)); ?>"
												class="hover:text-<?php echo esc_attr($region_colors['text']); ?>"><?php echo esc_html($phone); ?></a><br>
										<?php endif; ?>
										<?php if ($email): ?>
											<?php _e('Email:', 'chroma-excellence'); ?> <a
												href="mailto:<?php echo esc_attr($email); ?>"
												class="hover:text-<?php echo esc_attr($region_colors['text']); ?>"><?php echo esc_html($email); ?></a>
										<?php endif; ?>
									</p>
								</div>
							</div>
						<?php endif; ?>

						<div class="flex gap-4">
							<div
								class="w-12 h-12 rounded-full bg-brand-cream flex items-center justify-center text-<?php echo esc_attr($region_colors['text']); ?> text-lg shrink-0">
								<i class="fa-solid fa-clock"></i>
							</div>
							<div>
								<h3 class="font-bold text-brand-ink"><?php _e('Hours of Operation', 'chroma-excellence'); ?>
								</h3>
								<p class="text-sm text-brand-ink/80">
									<?php _e('Monday - Friday:', 'chroma-excellence'); ?>
									<?php echo esc_html($hours); ?><br>
									<?php _e('Weekends: Closed', 'chroma-excellence'); ?>
								</p>
							</div>
						</div>

						<?php if ($school_pickups):
							$schools = array_filter(array_map('trim', explode("\n", $school_pickups)));
							if (!empty($schools)):
								?>
								<div class="flex gap-4">
									<div
										class="w-12 h-12 rounded-full bg-brand-cream flex items-center justify-center text-<?php echo esc_attr($region_colors['text']); ?> text-lg shrink-0">
										<i class="fa-solid fa-bus"></i>
									</div>
									<div>
										<h3 class="font-bold text-brand-ink"><?php _e('School Pickups', 'chroma-excellence'); ?>
										</h3>
										<p class="text-sm text-brand-ink/80">
											<?php _e('We provide pickup service to:', 'chroma-excellence'); ?></p>
										<ul class="text-sm text-brand-ink/80 mt-2 space-y-1">
											<?php foreach ($schools as $school): ?>
												<li class="flex items-start gap-2">
													<i class="fa-solid fa-check text-chroma-green text-xs mt-1"></i>
													<span><?php echo esc_html($school); ?></span>
												</li>
											<?php endforeach; ?>
										</ul>
									</div>
								</div>
							<?php endif; endif; ?>
					</div>

					<!-- Map Embed -->
					<?php if ($maps_embed): ?>
						<div class="mt-10 relative z-0">
							<div class="w-full h-80 rounded-3xl overflow-hidden shadow-card border border-brand-ink/10">
								<?php echo wp_kses($maps_embed, array(
									'iframe' => array(
										'src' => array(),
										'width' => array(),
										'height' => array(),
										'frameborder' => array(),
										'style' => array(),
										'allowfullscreen' => array(),
										'loading' => array(),
										'referrerpolicy' => array(),
										'title' => array(),
									),
								)); ?>
							</div>
						</div>
					<?php elseif ($lat && $lng): ?>
						<div class="mt-10 relative z-0">
							<div data-chroma-map
								data-chroma-locations='[{"lat":<?php echo esc_attr($lat); ?>,"lng":<?php echo esc_attr($lng); ?>,"name":"<?php echo esc_js(get_the_title()); ?>","city":"<?php echo esc_js($city); ?>","url":"<?php echo esc_url(get_permalink()); ?>"}]'
								class="w-full h-80 rounded-3xl overflow-hidden shadow-card border border-brand-ink/10"></div>
						</div>
					<?php endif; ?>
				</div>

				<!-- Form Side -->
				<div id="tour"
					class="bg-brand-cream p-8 md:p-10 rounded-[2.5rem] shadow-soft border border-<?php echo esc_attr($region_colors['border']); ?>/10 h-fit sticky top-28">
					<h3 class="font-serif text-2xl font-bold text-brand-ink mb-2">
						<?php _e('Request a Tour', 'chroma-excellence'); ?></h3>
					<p class="text-sm text-brand-ink/90 mb-6">
						<?php _e("Fill out the form below and we'll contact you to confirm a time.", 'chroma-excellence'); ?>
					</p>

					<?php echo do_shortcode('[chroma_tour_form location_id="' . $location_id . '"]'); ?>

					<?php if ($tour_booking_link): ?>
						<div class="mt-6 text-center">
							<div class="flex items-center gap-4 mb-4">
								<div class="flex-1 h-px bg-brand-ink/10"></div>
								<span
									class="text-sm text-brand-ink font-medium uppercase tracking-wider"><?php _e('or', 'chroma-excellence'); ?></span>
								<div class="flex-1 h-px bg-brand-ink/10"></div>
							</div>
							<a href="<?php echo esc_url($tour_booking_link); ?>"
								class="booking-btn inline-flex items-center justify-center px-8 py-4 rounded-full bg-chroma-green text-white text-xs font-bold uppercase tracking-[0.2em] shadow-soft hover:bg-chroma-greenDark transition-all hover:-translate-y-1">
								<?php _e('Book a Tour Now', 'chroma-excellence'); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>

			</div>
		</section>

		<?php if ($seo_content_title || $seo_content_text): ?>
			<!-- Location SEO Content Section -->
			<section class="py-24 bg-brand-cream relative">
				<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
					<?php if ($seo_content_title): ?>
						<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
							<?php echo esc_html($seo_content_title); ?>
						</h2>
					<?php endif; ?>

					<?php if ($seo_content_text): ?>
						<div class="text-lg text-brand-ink/90 leading-relaxed max-w-3xl mx-auto">
							<?php echo wp_kses_post(wpautop($seo_content_text)); ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- Filtered Content (Badges, Related Locations, etc.) -->
		<section class="pb-24 bg-brand-cream">
			<div class="max-w-7xl mx-auto px-4 lg:px-6">
				<?php the_content(); ?>
			</div>
		</section>

	</main>

	<?php
endwhile;
?>

<!-- Tour Booking Modal (Local to this template) -->
<div id="chroma-tour-modal" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true">
	<!-- Backdrop -->
	<div class="absolute inset-0 bg-brand-ink/80 backdrop-blur-sm transition-opacity" id="chroma-tour-backdrop"></div>

	<!-- Modal Container -->
	<div
		class="absolute inset-4 md:inset-10 bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col animate-fade-in-up">
		<!-- Header -->
		<div
			class="bg-brand-cream border-b border-brand-ink/5 px-6 py-4 flex items-center justify-between flex-shrink-0">
			<h3 class="font-serif text-xl font-bold text-brand-ink">
				<?php _e('Schedule Your Visit', 'chroma-excellence'); ?></h3>
			<div class="flex items-center gap-4">
				<a href="#" id="chroma-tour-external" target="_blank"
					class="text-xs font-bold uppercase tracking-wider text-brand-ink/50 hover:text-chroma-blue transition-colors hidden md:block">
					<?php _e('Open in new tab', 'chroma-excellence'); ?> <i
						class="fa-solid fa-external-link-alt ml-1"></i>
				</a>
				<button id="chroma-tour-close"
					class="w-10 h-10 rounded-full bg-white border border-brand-ink/10 flex items-center justify-center text-brand-ink hover:bg-chroma-red hover:text-white hover:border-chroma-red transition-all">
					<i class="fa-solid fa-xmark text-lg"></i>
				</button>
			</div>
		</div>

		<!-- Iframe Container -->
		<div class="flex-grow relative bg-white">
			<div id="chroma-tour-loader" class="absolute inset-0 flex items-center justify-center bg-white z-10">
				<div class="w-12 h-12 border-4 border-chroma-blue/20 border-t-chroma-blue rounded-full animate-spin">
				</div>
			</div>
			<iframe id="chroma-tour-frame" src="" class="w-full h-full border-0"
				allow="camera; microphone; autoplay; encrypted-media;"></iframe>
		</div>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		const modal = document.getElementById('chroma-tour-modal');
		const backdrop = document.getElementById('chroma-tour-backdrop');
		const closeBtn = document.getElementById('chroma-tour-close');
		const iframe = document.getElementById('chroma-tour-frame');
		const externalLink = document.getElementById('chroma-tour-external');
		const loader = document.getElementById('chroma-tour-loader');

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

		// Attach listeners to booking buttons
		const bookingBtns = document.querySelectorAll('.booking-btn');
		bookingBtns.forEach(btn => {
			btn.addEventListener('click', function (e) {
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

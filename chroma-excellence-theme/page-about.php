<?php
/**
 * Template Name: About Page
 *
 * About Us page template with proper SEO structure
 *
 * @package Chroma_Excellence
 */

get_header();

while ( have_posts() ) :
	the_post();
	$page_id = get_the_ID();

	// Hero Section
	$hero_title = get_post_meta( $page_id, 'about_hero_title', true ) ?: 'Excellence in Every Color';
	$hero_description = get_post_meta( $page_id, 'about_hero_description', true ) ?: 'At Chroma Excellence Academy, we believe every child is born with infinite potential. Our mission is to nurture that potential through play-based learning, research-backed curriculum, and educators who see childhood as sacred.';
	$hero_stats_text = get_post_meta( $page_id, 'about_hero_stats_text', true ) ?: '12+ Locations | 500+ Families';

	// Mission Section
	$mission_title = get_post_meta( $page_id, 'about_mission_title', true ) ?: 'Our Mission';
	$mission_description = get_post_meta( $page_id, 'about_mission_description', true ) ?: 'To provide a developmentally rich, joyful environment where children explore, discover, and grow into curious, confident, and compassionate citizens of the world.';

	// Story Section
	$story_title = get_post_meta( $page_id, 'about_story_title', true ) ?: 'Our Story';
	$story_description = get_post_meta( $page_id, 'about_story_description', true ) ?: 'Founded in 2010, Chroma Excellence Academy began with a simple belief: that the early years matter. What started as a single location has grown into a network of vibrant learning communities, each one grounded in the same core philosophy—play is the work of childhood, and every moment is an opportunity to learn.';

	$stat1_value = get_post_meta( $page_id, 'about_stat1_value', true ) ?: '12+';
	$stat1_label = get_post_meta( $page_id, 'about_stat1_label', true ) ?: 'Locations';
	$stat2_value = get_post_meta( $page_id, 'about_stat2_value', true ) ?: '500+';
	$stat2_label = get_post_meta( $page_id, 'about_stat2_label', true ) ?: 'Families Served';
	$stat3_value = get_post_meta( $page_id, 'about_stat3_value', true ) ?: '15';
	$stat3_label = get_post_meta( $page_id, 'about_stat3_label', true ) ?: 'Years of Excellence';
	$stat4_value = get_post_meta( $page_id, 'about_stat4_value', true ) ?: '100%';
	$stat4_label = get_post_meta( $page_id, 'about_stat4_label', true ) ?: 'Organic Meals';

	// Educators Section
	$educators_title = get_post_meta( $page_id, 'about_educators_title', true ) ?: 'Our Educators Difference';
	$educator1_title = get_post_meta( $page_id, 'about_educator1_title', true ) ?: 'Continuous Training';
	$educator1_desc = get_post_meta( $page_id, 'about_educator1_desc', true ) ?: 'Every teacher receives 40+ hours of professional development annually, staying current on child development research and best practices.';
	$educator2_title = get_post_meta( $page_id, 'about_educator2_title', true ) ?: 'Low Ratios';
	$educator2_desc = get_post_meta( $page_id, 'about_educator2_desc', true ) ?: 'We maintain ratios well below state requirements, ensuring individualized attention for every child.';
	$educator3_title = get_post_meta( $page_id, 'about_educator3_title', true ) ?: 'Passionate Educators';
	$educator3_desc = get_post_meta( $page_id, 'about_educator3_desc', true ) ?: 'Our teachers don\'t just work here—they believe in the mission. Many have been with us for 5+ years.';

	// Core Values Section
	$values_title = get_post_meta( $page_id, 'about_values_title', true ) ?: 'Our Four Pillars';

	$value1_icon = get_post_meta( $page_id, 'about_value1_icon', true ) ?: 'fa-solid fa-heart';
	$value1_title = get_post_meta( $page_id, 'about_value1_title', true ) ?: 'Compassion';
	$value1_desc = get_post_meta( $page_id, 'about_value1_desc', true ) ?: 'We lead with empathy, kindness, and care in all we do.';

	$value2_icon = get_post_meta( $page_id, 'about_value2_icon', true ) ?: 'fa-solid fa-users';
	$value2_title = get_post_meta( $page_id, 'about_value2_title', true ) ?: 'Community';
	$value2_desc = get_post_meta( $page_id, 'about_value2_desc', true ) ?: 'Families, staff, and children grow together in mutual support.';

	$value3_icon = get_post_meta( $page_id, 'about_value3_icon', true ) ?: 'fa-solid fa-leaf';
	$value3_title = get_post_meta( $page_id, 'about_value3_title', true ) ?: 'Sustainability';
	$value3_desc = get_post_meta( $page_id, 'about_value3_desc', true ) ?: 'We honor the earth through eco-conscious practices and curriculum.';

	$value4_icon = get_post_meta( $page_id, 'about_value4_icon', true ) ?: 'fa-solid fa-lightbulb';
	$value4_title = get_post_meta( $page_id, 'about_value4_title', true ) ?: 'Innovation';
	$value4_desc = get_post_meta( $page_id, 'about_value4_desc', true ) ?: 'We blend timeless pedagogy with modern research and tools.';

	// Leadership Section
	$leadership_title = get_post_meta( $page_id, 'about_leadership_title', true ) ?: 'Meet Our Leadership';
	$leadership_description = get_post_meta( $page_id, 'about_leadership_description', true ) ?: 'Our leadership team brings decades of combined experience in early childhood education, curriculum design, and educational administration.';

	// Nutrition Section
	$nutrition_title = get_post_meta( $page_id, 'about_nutrition_title', true ) ?: 'Nutrition & Wellness';
	$nutrition_description = get_post_meta( $page_id, 'about_nutrition_description', true ) ?: 'We believe that a healthy body supports a healthy mind. All meals are prepared fresh daily using organic, locally sourced ingredients whenever possible. Our menu is designed by pediatric nutritionists to support growing bodies and developing brains.';
	$nutrition_image = get_post_meta( $page_id, 'about_nutrition_image', true ) ?: 'https://images.unsplash.com/photo-1490818387583-1baba5e638af?q=80&w=800&auto=format&fit=crop';

	// Philanthropy Section
	$philanthropy_title = get_post_meta( $page_id, 'about_philanthropy_title', true ) ?: 'Giving Back';
	$philanthropy_description = get_post_meta( $page_id, 'about_philanthropy_description', true ) ?: 'Education is a right, not a privilege. Through our scholarship fund and community partnerships, we ensure that families from all backgrounds can access high-quality early education. We also partner with local nonprofits to teach children the joy of service from a young age.';
	$philanthropy_image = get_post_meta( $page_id, 'about_philanthropy_image', true ) ?: 'https://images.unsplash.com/photo-1559027615-cd4628902d4a?q=80&w=800&auto=format&fit=crop';

	// CTA Section
	$cta_title = get_post_meta( $page_id, 'about_cta_title', true ) ?: 'Ready to join our community?';
	$cta_description = get_post_meta( $page_id, 'about_cta_description', true ) ?: 'Schedule a tour to see our approach in action and meet the educators who will nurture your child\'s journey.';

	// Get Team Members
	$team_members = new WP_Query( array(
		'post_type'      => 'team_member',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	) );
?>

<main id="main-content" role="main">
	<!-- Hero Section -->
	<section class="relative pt-16 pb-12 lg:pt-24 lg:pb-20 bg-white overflow-hidden">
		<div class="absolute top-0 left-0 w-full h-full opacity-30">
			<div class="absolute top-20 right-20 w-96 h-96 bg-chroma-red/20 rounded-full blur-3xl"></div>
			<div class="absolute bottom-20 left-20 w-96 h-96 bg-chroma-blue/20 rounded-full blur-3xl"></div>
		</div>

		<div class="max-w-5xl mx-auto px-4 lg:px-6 relative z-10 text-center">
			<?php if ( $hero_stats_text ) : ?>
				<div class="inline-flex items-center gap-2 bg-white border border-chroma-red/30 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-chroma-red shadow-sm mb-6 fade-in-up">
					<i class="fa-solid fa-award"></i> <?php echo esc_html( $hero_stats_text ); ?>
				</div>
			<?php endif; ?>

			<h1 class="font-serif text-[2.8rem] md:text-6xl text-brand-ink mb-6 fade-in-up delay-100">
				<?php echo esc_html( $hero_title ); ?>
			</h1>

			<p class="text-lg text-brand-ink/70 max-w-3xl mx-auto mb-10 fade-in-up delay-200">
				<?php echo esc_html( $hero_description ); ?>
			</p>
		</div>
	</section>

	<!-- Mission Section -->
	<section class="py-16 bg-brand-cream">
		<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
			<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Our Purpose</span>
			<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
				<?php echo esc_html( $mission_title ); ?>
			</h2>
			<p class="text-xl text-brand-ink/70 leading-relaxed">
				<?php echo nl2br( esc_html( $mission_description ) ); ?>
			</p>
		</div>
	</section>

	<!-- Story & Statistics -->
	<section class="py-24 bg-white">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="grid lg:grid-cols-2 gap-16 items-center">
				<div>
					<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Since 2010</span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
						<?php echo esc_html( $story_title ); ?>
					</h2>
					<p class="text-brand-ink/70 text-lg leading-relaxed">
						<?php echo nl2br( esc_html( $story_description ) ); ?>
					</p>
				</div>

				<div class="grid grid-cols-2 gap-6">
					<?php
					$stats = array(
						array( 'value' => $stat1_value, 'label' => $stat1_label, 'color' => 'chroma-red' ),
						array( 'value' => $stat2_value, 'label' => $stat2_label, 'color' => 'chroma-yellow' ),
						array( 'value' => $stat3_value, 'label' => $stat3_label, 'color' => 'chroma-blue' ),
						array( 'value' => $stat4_value, 'label' => $stat4_label, 'color' => 'chroma-green' ),
					);

					foreach ( $stats as $stat ) :
						if ( $stat['value'] ) :
					?>
						<article class="bg-brand-cream p-8 rounded-3xl text-center border border-brand-ink/5 hover:border-<?php echo esc_attr( $stat['color'] ); ?>/30 transition-all hover:shadow-lg">
							<div class="text-4xl font-serif font-bold text-<?php echo esc_attr( $stat['color'] ); ?> mb-2">
								<?php echo esc_html( $stat['value'] ); ?>
							</div>
							<div class="text-sm text-brand-ink/60 uppercase tracking-wide">
								<?php echo esc_html( $stat['label'] ); ?>
							</div>
						</article>
					<?php endif; endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- Educators Section -->
	<section class="py-24 bg-brand-cream">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center mb-16">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Our Team</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink">
					<?php echo esc_html( $educators_title ); ?>
				</h2>
			</div>

			<div class="grid md:grid-cols-3 gap-8">
				<?php
				$educators = array(
					array( 'icon' => 'fa-solid fa-graduation-cap', 'title' => $educator1_title, 'desc' => $educator1_desc, 'color' => 'chroma-red' ),
					array( 'icon' => 'fa-solid fa-users-line', 'title' => $educator2_title, 'desc' => $educator2_desc, 'color' => 'chroma-yellow' ),
					array( 'icon' => 'fa-solid fa-heart', 'title' => $educator3_title, 'desc' => $educator3_desc, 'color' => 'chroma-blue' ),
				);

				foreach ( $educators as $educator ) :
					if ( $educator['title'] ) :
				?>
					<article class="bg-white p-8 rounded-3xl border border-brand-ink/5 hover:shadow-xl transition-all hover:-translate-y-1">
						<div class="w-16 h-16 rounded-2xl bg-<?php echo esc_attr( $educator['color'] ); ?>/10 flex items-center justify-center mb-6">
							<i class="<?php echo esc_attr( $educator['icon'] ); ?> text-2xl text-<?php echo esc_attr( $educator['color'] ); ?>"></i>
						</div>
						<h3 class="text-xl font-bold text-brand-ink mb-3">
							<?php echo esc_html( $educator['title'] ); ?>
						</h3>
						<p class="text-brand-ink/70">
							<?php echo esc_html( $educator['desc'] ); ?>
						</p>
					</article>
				<?php endif; endforeach; ?>
			</div>
		</div>
	</section>

	<!-- Core Values -->
	<section class="py-24 bg-white">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center mb-16">
				<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block">What We Stand For</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink">
					<?php echo esc_html( $values_title ); ?>
				</h2>
			</div>

			<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
				<?php
				$values = array(
					array( 'icon' => $value1_icon, 'title' => $value1_title, 'desc' => $value1_desc, 'color' => 'chroma-red' ),
					array( 'icon' => $value2_icon, 'title' => $value2_title, 'desc' => $value2_desc, 'color' => 'chroma-yellow' ),
					array( 'icon' => $value3_icon, 'title' => $value3_title, 'desc' => $value3_desc, 'color' => 'chroma-green' ),
					array( 'icon' => $value4_icon, 'title' => $value4_title, 'desc' => $value4_desc, 'color' => 'chroma-blue' ),
				);

				foreach ( $values as $value ) :
					if ( $value['title'] ) :
				?>
					<article class="text-center">
						<div class="w-20 h-20 rounded-full bg-<?php echo esc_attr( $value['color'] ); ?>/10 flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform">
							<i class="<?php echo esc_attr( $value['icon'] ); ?> text-3xl text-<?php echo esc_attr( $value['color'] ); ?>"></i>
						</div>
						<h3 class="text-xl font-bold text-brand-ink mb-3">
							<?php echo esc_html( $value['title'] ); ?>
						</h3>
						<p class="text-sm text-brand-ink/70">
							<?php echo esc_html( $value['desc'] ); ?>
						</p>
					</article>
				<?php endif; endforeach; ?>
			</div>
		</div>
	</section>

	<!-- Leadership Team -->
	<?php if ( $team_members->have_posts() ) : ?>
	<section class="py-24 bg-brand-cream">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center mb-16">
				<span class="text-chroma-blueDark font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Leadership</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-4">
					<?php echo esc_html( $leadership_title ); ?>
				</h2>
				<?php if ( $leadership_description ) : ?>
					<p class="text-lg text-brand-ink/70 max-w-3xl mx-auto">
						<?php echo nl2br( esc_html( $leadership_description ) ); ?>
					</p>
				<?php endif; ?>
			</div>

			<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-12">
				<?php while ( $team_members->have_posts() ) : $team_members->the_post(); ?>
					<article class="text-center group">
						<div class="relative mb-6 overflow-hidden rounded-3xl border-4 border-white shadow-lg aspect-square">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'large', array(
									'class' => 'w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700',
									'alt'   => get_the_title(),
								) ); ?>
							<?php else : ?>
								<div class="w-full h-full bg-gradient-to-br from-chroma-blue to-chroma-blueDark flex items-center justify-center">
									<i class="fa-solid fa-user text-6xl text-white/50"></i>
								</div>
							<?php endif; ?>
						</div>

						<h3 class="text-xl font-bold text-brand-ink mb-1">
							<?php the_title(); ?>
						</h3>

						<?php
						$member_title = get_post_meta( get_the_ID(), 'team_member_title', true );
						if ( $member_title ) :
						?>
							<p class="text-sm text-chroma-blue font-semibold uppercase tracking-wide mb-3">
								<?php echo esc_html( $member_title ); ?>
							</p>
						<?php endif; ?>

						<?php if ( get_the_content() ) : ?>
							<div class="text-sm text-brand-ink/70">
								<?php the_content(); ?>
							</div>
						<?php endif; ?>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- Nutrition & Wellness -->
	<section class="py-24 bg-white">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="grid lg:grid-cols-2 gap-16 items-center">
				<div class="order-2 lg:order-1">
					<div class="relative h-[400px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white">
						<img src="<?php echo esc_url( $nutrition_image ); ?>"
							 alt="<?php echo esc_attr( $nutrition_title ); ?>"
							 class="w-full h-full object-cover" />
					</div>
				</div>

				<div class="order-1 lg:order-2">
					<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Healthy Bodies, Healthy Minds</span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
						<?php echo esc_html( $nutrition_title ); ?>
					</h2>
					<p class="text-brand-ink/70 text-lg leading-relaxed mb-6">
						<?php echo nl2br( esc_html( $nutrition_description ) ); ?>
					</p>
					<div class="flex gap-6">
						<div class="flex items-center gap-3">
							<div class="w-12 h-12 rounded-full bg-chroma-green/10 flex items-center justify-center">
								<i class="fa-solid fa-leaf text-chroma-green"></i>
							</div>
							<span class="text-sm font-semibold text-brand-ink">Organic Ingredients</span>
						</div>
						<div class="flex items-center gap-3">
							<div class="w-12 h-12 rounded-full bg-chroma-yellow/10 flex items-center justify-center">
								<i class="fa-solid fa-utensils text-chroma-yellow"></i>
							</div>
							<span class="text-sm font-semibold text-brand-ink">Fresh Daily</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- Philanthropy -->
	<section class="py-24 bg-brand-cream">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="grid lg:grid-cols-2 gap-16 items-center">
				<div>
					<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Community Impact</span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
						<?php echo esc_html( $philanthropy_title ); ?>
					</h2>
					<p class="text-brand-ink/70 text-lg leading-relaxed mb-6">
						<?php echo nl2br( esc_html( $philanthropy_description ) ); ?>
					</p>
					<div class="flex gap-6">
						<div class="flex items-center gap-3">
							<div class="w-12 h-12 rounded-full bg-chroma-red/10 flex items-center justify-center">
								<i class="fa-solid fa-hand-holding-heart text-chroma-red"></i>
							</div>
							<span class="text-sm font-semibold text-brand-ink">Scholarship Fund</span>
						</div>
						<div class="flex items-center gap-3">
							<div class="w-12 h-12 rounded-full bg-chroma-blue/10 flex items-center justify-center">
								<i class="fa-solid fa-hands-helping text-chroma-blue"></i>
							</div>
							<span class="text-sm font-semibold text-brand-ink">Local Partnerships</span>
						</div>
					</div>
				</div>

				<div class="order-2 lg:order-1">
					<div class="relative h-[400px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white">
						<img src="<?php echo esc_url( $philanthropy_image ); ?>"
							 alt="<?php echo esc_attr( $philanthropy_title ); ?>"
							 class="w-full h-full object-cover" />
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- CTA Section -->
	<section class="py-20 bg-white">
		<div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
			<h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-6">
				<?php echo esc_html( $cta_title ); ?>
			</h2>
			<p class="text-brand-ink/70 mb-10">
				<?php echo esc_html( $cta_description ); ?>
			</p>
			<div class="flex flex-wrap justify-center gap-4">
				<a href="<?php echo esc_url( home_url( '/locations' ) ); ?>"
				   class="px-8 py-4 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:border-chroma-blue hover:text-chroma-blue transition-colors">
					Find a Location
				</a>
				<a href="#tour"
				   class="px-8 py-4 bg-chroma-red text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:opacity-90 transition-colors shadow-lg">
					Schedule a Tour
				</a>
			</div>
		</div>
	</section>
</main>

<style>
	.fade-in-up { animation: fadeInUp 0.8s ease forwards; opacity: 0; transform: translateY(20px); }
	.delay-100 { animation-delay: 0.1s; }
	.delay-200 { animation-delay: 0.2s; }
	@keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
</style>

<?php
endwhile;
get_footer();

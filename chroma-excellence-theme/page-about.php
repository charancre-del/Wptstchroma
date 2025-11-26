<?php
/**
 * Template Name: About Page
 *
 * About Us page template matching exact HTML design with proper SEO structure
 *
 * @package Chroma_Excellence
 */

get_header();

while ( have_posts() ) :
	the_post();
	$page_id = get_the_ID();

	// Hero Section
	$hero_badge_text = get_post_meta( $page_id, 'about_hero_badge_text', true ) ?: 'Established 2015';
	$hero_title = get_post_meta( $page_id, 'about_hero_title', true ) ?: 'More than a school. <span class="text-chroma-yellow italic">A second home.</span>';
	$hero_description = get_post_meta( $page_id, 'about_hero_description', true ) ?: 'We founded Chroma on a simple belief: Early education should be a perfect blend of rigorous cognitive development and the comforting warmth of family.';
	$hero_image = get_post_meta( $page_id, 'about_hero_image', true ) ?: 'https://images.unsplash.com/photo-1588072432836-e10032774350?q=80&w=1000&auto=format&fit=crop';

	// Mission Section
	$mission_quote = get_post_meta( $page_id, 'about_mission_quote', true ) ?: '"To cultivate a vibrant community of lifelong learners by blending academic rigor with the nurturing warmth of home, ensuring every child feels seen, valued, and capable."';

	// Story Section
	$story_title = get_post_meta( $page_id, 'about_story_title', true ) ?: 'From one classroom to a community.';
	$story_paragraph1 = get_post_meta( $page_id, 'about_story_paragraph1', true ) ?: 'Chroma Early Learning Academy began with a single location in Lawrenceville and a mission to redefine "daycare." We didn\'t just want to watch children; we wanted to ignite them.';
	$story_paragraph2 = get_post_meta( $page_id, 'about_story_paragraph2', true ) ?: 'Over the last decade, we have grown into a network of 19+ campuses across Metro Atlanta, yet each location retains the intimacy and personal touch of that very first school. We are locally owned, operated by educators, and driven by the success of our families.';
	$story_image = get_post_meta( $page_id, 'about_story_image', true ) ?: 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=800&auto=format&fit=crop';

	$stat1_value = get_post_meta( $page_id, 'about_stat1_value', true ) ?: '19+';
	$stat1_label = get_post_meta( $page_id, 'about_stat1_label', true ) ?: 'Locations';
	$stat2_value = get_post_meta( $page_id, 'about_stat2_value', true ) ?: '2k+';
	$stat2_label = get_post_meta( $page_id, 'about_stat2_label', true ) ?: 'Students';
	$stat3_value = get_post_meta( $page_id, 'about_stat3_value', true ) ?: '450+';
	$stat3_label = get_post_meta( $page_id, 'about_stat3_label', true ) ?: 'Educators';
	$stat4_value = get_post_meta( $page_id, 'about_stat4_value', true ) ?: '100%';
	$stat4_label = get_post_meta( $page_id, 'about_stat4_label', true ) ?: 'Licensed';

	// Educators Section
	$educators_title = get_post_meta( $page_id, 'about_educators_title', true ) ?: 'The Heart of Chroma.';
	$educators_description = get_post_meta( $page_id, 'about_educators_description', true ) ?: 'We don\'t just hire supervisors; we hire career educators. Our teachers are the most valuable asset in our classrooms, selected for their passion, patience, and professional credentials.';

	$educator1_icon = get_post_meta( $page_id, 'about_educator1_icon', true ) ?: 'fa-solid fa-certificate';
	$educator1_title = get_post_meta( $page_id, 'about_educator1_title', true ) ?: 'Certified & Credentialed';
	$educator1_desc = get_post_meta( $page_id, 'about_educator1_desc', true ) ?: 'Lead teachers hold a CDA (Child Development Associate), TCC, or higher degree in Early Childhood Education. We support ongoing education for every staff member.';

	$educator2_icon = get_post_meta( $page_id, 'about_educator2_icon', true ) ?: 'fa-solid fa-user-shield';
	$educator2_title = get_post_meta( $page_id, 'about_educator2_title', true ) ?: 'Safety First';
	$educator2_desc = get_post_meta( $page_id, 'about_educator2_desc', true ) ?: 'Every team member undergoes rigorous federal and state background checks. All staff are certified in CPR and First Aid, with regular refresher courses.';

	$educator3_icon = get_post_meta( $page_id, 'about_educator3_icon', true ) ?: 'fa-solid fa-chalkboard-user';
	$educator3_title = get_post_meta( $page_id, 'about_educator3_title', true ) ?: 'Continuous Growth';
	$educator3_desc = get_post_meta( $page_id, 'about_educator3_desc', true ) ?: 'Our educators participate in 20+ hours of annual professional development, specializing in the Prismpath™ curriculum and social-emotional learning.';

	// Core Values Section
	$values_title = get_post_meta( $page_id, 'about_values_title', true ) ?: 'The Chroma Standard';
	$values_description = get_post_meta( $page_id, 'about_values_description', true ) ?: 'Our culture is built on four non-negotiable pillars that guide every decision we make, from hiring teachers to designing playgrounds.';

	$value1_icon = get_post_meta( $page_id, 'about_value1_icon', true ) ?: 'fa-solid fa-heart';
	$value1_title = get_post_meta( $page_id, 'about_value1_title', true ) ?: 'Unconditional Joy';
	$value1_desc = get_post_meta( $page_id, 'about_value1_desc', true ) ?: 'We believe childhood should be magical. We prioritize laughter, play, and warmth in every interaction.';

	$value2_icon = get_post_meta( $page_id, 'about_value2_icon', true ) ?: 'fa-solid fa-shield-halved';
	$value2_title = get_post_meta( $page_id, 'about_value2_title', true ) ?: 'Radical Safety';
	$value2_desc = get_post_meta( $page_id, 'about_value2_desc', true ) ?: 'Physical safety is our baseline; emotional safety is our goal. Kids learn best when they feel secure.';

	$value3_icon = get_post_meta( $page_id, 'about_value3_icon', true ) ?: 'fa-solid fa-lightbulb';
	$value3_title = get_post_meta( $page_id, 'about_value3_title', true ) ?: 'Academic Excellence';
	$value3_desc = get_post_meta( $page_id, 'about_value3_desc', true ) ?: 'Using our Prismpath™ model, we deliver rigorous, age-appropriate learning that feels like play.';

	$value4_icon = get_post_meta( $page_id, 'about_value4_icon', true ) ?: 'fa-solid fa-users';
	$value4_title = get_post_meta( $page_id, 'about_value4_title', true ) ?: 'Open Partnership';
	$value4_desc = get_post_meta( $page_id, 'about_value4_desc', true ) ?: 'Parents are partners. We maintain open doors, transparent communication, and daily updates.';

	// Leadership Section
	$leadership_title = get_post_meta( $page_id, 'about_leadership_title', true ) ?: 'Led by educators, not investors.';

	// Nutrition Section
	$nutrition_title = get_post_meta( $page_id, 'about_nutrition_title', true ) ?: 'Fueling growing minds.';
	$nutrition_description = get_post_meta( $page_id, 'about_nutrition_description', true ) ?: 'We believe nutrition is a key part of education. Our in-house chefs prepare balanced, nut-free meals daily using fresh ingredients.';
	$nutrition_image = get_post_meta( $page_id, 'about_nutrition_image', true ) ?: 'https://images.unsplash.com/photo-1606914506133-2230d94922b5?q=80&w=800&auto=format&fit=crop';

	$nutrition_bullet1_icon = get_post_meta( $page_id, 'about_nutrition_bullet1_icon', true ) ?: 'fa-solid fa-apple-whole';
	$nutrition_bullet1_text = get_post_meta( $page_id, 'about_nutrition_bullet1_text', true ) ?: 'CACFP Certified Menus';
	$nutrition_bullet2_icon = get_post_meta( $page_id, 'about_nutrition_bullet2_icon', true ) ?: 'fa-solid fa-carrot';
	$nutrition_bullet2_text = get_post_meta( $page_id, 'about_nutrition_bullet2_text', true ) ?: 'Family-Style Dining to teach manners';
	$nutrition_bullet3_icon = get_post_meta( $page_id, 'about_nutrition_bullet3_icon', true ) ?: 'fa-solid fa-ban';
	$nutrition_bullet3_text = get_post_meta( $page_id, 'about_nutrition_bullet3_text', true ) ?: 'Strict Nut-Free & Allergy Protocols';

	// Philanthropy Section
	$philanthropy_title = get_post_meta( $page_id, 'about_philanthropy_title', true ) ?: 'Giving back to our future.';
	$philanthropy_subtitle = get_post_meta( $page_id, 'about_philanthropy_subtitle', true ) ?: 'Foundations For Learning Inc.';
	$philanthropy_description = get_post_meta( $page_id, 'about_philanthropy_description', true ) ?: 'At Chroma, our commitment extends beyond our classroom walls. Through our partnership with <strong>Foundations For Learning Inc.</strong>, we work to ensure that quality early education is accessible to every child in our community.';
	$philanthropy_image = get_post_meta( $page_id, 'about_philanthropy_image', true ) ?: 'https://images.unsplash.com/photo-1593113598332-cd288d649433?q=80&w=800&auto=format&fit=crop';

	$philanthropy_bullet1_icon = get_post_meta( $page_id, 'about_philanthropy_bullet1_icon', true ) ?: 'fa-solid fa-hand-holding-heart';
	$philanthropy_bullet1_text = get_post_meta( $page_id, 'about_philanthropy_bullet1_text', true ) ?: 'Scholarship opportunities for families';
	$philanthropy_bullet2_icon = get_post_meta( $page_id, 'about_philanthropy_bullet2_icon', true ) ?: 'fa-solid fa-chalkboard-user';
	$philanthropy_bullet2_text = get_post_meta( $page_id, 'about_philanthropy_bullet2_text', true ) ?: 'Teacher training grants';
	$philanthropy_bullet3_icon = get_post_meta( $page_id, 'about_philanthropy_bullet3_icon', true ) ?: 'fa-solid fa-people-roof';
	$philanthropy_bullet3_text = get_post_meta( $page_id, 'about_philanthropy_bullet3_text', true ) ?: 'Community outreach programs';

	// CTA Section
	$cta_title = get_post_meta( $page_id, 'about_cta_title', true ) ?: 'Ready to join the family?';
	$cta_description = get_post_meta( $page_id, 'about_cta_description', true ) ?: 'Come see why over 2,000 families trust Chroma with their children\'s early years.';

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
	<section class="relative pt-16 pb-20 lg:pt-24 lg:pb-32 overflow-hidden">
		<!-- Decor -->
		<div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-chroma-yellowLight/50 to-transparent -z-10"></div>
		<div class="absolute bottom-0 left-0 w-96 h-96 bg-chroma-blue/5 rounded-full blur-3xl -z-10"></div>

		<div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-16 items-center">
			<div class="fade-in-up">
				<?php if ( $hero_badge_text ) : ?>
					<div class="inline-flex items-center gap-2 bg-white border border-chroma-yellow/40 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-chroma-yellow shadow-sm mb-6">
						<i class="fa-solid fa-heart"></i> <?php echo esc_html( $hero_badge_text ); ?>
					</div>
				<?php endif; ?>

				<h1 class="font-serif text-[2.8rem] md:text-6xl text-brand-ink mb-6">
					<?php echo wp_kses_post( $hero_title ); ?>
				</h1>

				<p class="text-lg text-brand-ink/70 mb-8 leading-relaxed">
					<?php echo esc_html( $hero_description ); ?>
				</p>

				<div class="flex flex-wrap gap-4">
					<a href="#mission" class="px-8 py-4 bg-brand-ink text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:bg-chroma-blueDark transition-colors shadow-lg">Our Mission</a>
					<a href="<?php echo esc_url( home_url( '/locations' ) ); ?>" class="px-8 py-4 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:border-chroma-yellow hover:text-chroma-yellow transition-colors">Find a Campus</a>
				</div>
			</div>

			<div class="relative fade-in-up delay-200">
				<div class="absolute inset-0 bg-chroma-yellow/10 rounded-[3rem] -rotate-3 transform translate-x-4 translate-y-4"></div>
				<div class="relative rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white">
					<img src="<?php echo esc_url( $hero_image ); ?>" alt="<?php echo esc_attr( strip_tags( $hero_title ) ); ?>" class="w-full h-full object-cover" />
				</div>
			</div>
		</div>
	</section>

	<!-- Mission Statement -->
	<section id="mission" class="py-20 bg-chroma-blue text-white relative overflow-hidden">
		<div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5"></div>
		<div class="max-w-5xl mx-auto px-4 lg:px-6 text-center relative z-10">
			<span class="text-chroma-yellow font-bold tracking-[0.2em] text-xs uppercase mb-6 block">Our Purpose</span>
			<h2 class="text-3xl md:text-5xl font-serif leading-tight mb-8">
				<?php echo esc_html( $mission_quote ); ?>
			</h2>
			<div class="w-24 h-1 bg-chroma-yellow mx-auto rounded-full"></div>
		</div>
	</section>

	<!-- Our Story / Statistics -->
	<section id="story" class="py-24 bg-white">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="grid md:grid-cols-2 gap-16 items-center mb-20">
				<div class="order-2 md:order-1 relative">
					<div class="absolute -left-10 -top-10 w-40 h-40 bg-chroma-red/10 rounded-full blur-2xl"></div>
					<img src="<?php echo esc_url( $story_image ); ?>" class="rounded-[2.5rem] shadow-card border border-brand-ink/5 relative z-10" alt="Our Story" />
				</div>
				<div class="order-1 md:order-2">
					<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Our Story</span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6"><?php echo esc_html( $story_title ); ?></h2>
					<p class="text-brand-ink/70 mb-6 leading-relaxed">
						<?php echo esc_html( $story_paragraph1 ); ?>
					</p>
					<p class="text-brand-ink/70 mb-6 leading-relaxed">
						<?php echo esc_html( $story_paragraph2 ); ?>
					</p>
				</div>
			</div>

			<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
				<?php
				$stats = array(
					array( 'value' => $stat1_value, 'label' => $stat1_label, 'color' => 'chroma-blue' ),
					array( 'value' => $stat2_value, 'label' => $stat2_label, 'color' => 'chroma-red' ),
					array( 'value' => $stat3_value, 'label' => $stat3_label, 'color' => 'chroma-yellow' ),
					array( 'value' => $stat4_value, 'label' => $stat4_label, 'color' => 'chroma-green' ),
				);

				foreach ( $stats as $stat ) :
					if ( $stat['value'] ) :
				?>
					<div class="p-8 bg-brand-cream rounded-[2rem] text-center border border-brand-ink/5">
						<div class="text-3xl md:text-4xl font-serif font-bold text-<?php echo esc_attr( $stat['color'] ); ?> mb-2">
							<?php echo esc_html( $stat['value'] ); ?>
						</div>
						<div class="text-xs font-bold uppercase tracking-wider text-brand-ink/40">
							<?php echo esc_html( $stat['label'] ); ?>
						</div>
					</div>
				<?php endif; endforeach; ?>
			</div>
		</div>
	</section>

	<!-- Our Educators -->
	<section id="educators" class="py-24 bg-brand-cream border-t border-brand-ink/5">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center mb-16 max-w-3xl mx-auto">
				<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Our Educators</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-4"><?php echo esc_html( $educators_title ); ?></h2>
				<p class="text-brand-ink/70"><?php echo esc_html( $educators_description ); ?></p>
			</div>

			<div class="grid md:grid-cols-3 gap-8">
				<?php
				$educators = array(
					array( 'icon' => $educator1_icon, 'title' => $educator1_title, 'desc' => $educator1_desc, 'color' => 'chroma-redLight', 'icon_color' => 'chroma-red' ),
					array( 'icon' => $educator2_icon, 'title' => $educator2_title, 'desc' => $educator2_desc, 'color' => 'chroma-blueLight', 'icon_color' => 'chroma-blue' ),
					array( 'icon' => $educator3_icon, 'title' => $educator3_title, 'desc' => $educator3_desc, 'color' => 'chroma-greenLight', 'icon_color' => 'chroma-green' ),
				);

				foreach ( $educators as $educator ) :
					if ( $educator['title'] ) :
				?>
					<div class="bg-white p-8 rounded-[2rem] shadow-soft">
						<div class="w-14 h-14 bg-<?php echo esc_attr( $educator['color'] ); ?> text-<?php echo esc_attr( $educator['icon_color'] ); ?> rounded-2xl flex items-center justify-center text-2xl mb-6">
							<i class="<?php echo esc_attr( $educator['icon'] ); ?>"></i>
						</div>
						<h3 class="font-serif text-xl font-bold text-brand-ink mb-3">
							<?php echo esc_html( $educator['title'] ); ?>
						</h3>
						<p class="text-sm text-brand-ink/60 leading-relaxed">
							<?php echo esc_html( $educator['desc'] ); ?>
						</p>
					</div>
				<?php endif; endforeach; ?>
			</div>
		</div>
	</section>

	<!-- Core Values -->
	<section id="values" class="py-24 bg-chroma-blueDark text-white relative overflow-hidden">
		<div class="absolute right-0 top-0 w-full h-full bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white/5 via-transparent to-transparent"></div>

		<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10">
			<div class="text-center mb-16">
				<h2 class="text-3xl md:text-4xl font-serif font-bold mb-4"><?php echo esc_html( $values_title ); ?></h2>
				<p class="text-white/60 max-w-2xl mx-auto"><?php echo esc_html( $values_description ); ?></p>
			</div>

			<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
				<?php
				$values = array(
					array( 'icon' => $value1_icon, 'title' => $value1_title, 'desc' => $value1_desc, 'color' => 'chroma-red' ),
					array( 'icon' => $value2_icon, 'title' => $value2_title, 'desc' => $value2_desc, 'color' => 'chroma-blue' ),
					array( 'icon' => $value3_icon, 'title' => $value3_title, 'desc' => $value3_desc, 'color' => 'chroma-yellow' ),
					array( 'icon' => $value4_icon, 'title' => $value4_title, 'desc' => $value4_desc, 'color' => 'chroma-green' ),
				);

				foreach ( $values as $value ) :
					if ( $value['title'] ) :
				?>
					<div class="bg-white/10 backdrop-blur-sm p-8 rounded-3xl border border-white/10 hover:bg-white/15 transition-colors">
						<div class="w-12 h-12 bg-<?php echo esc_attr( $value['color'] ); ?> rounded-xl flex items-center justify-center mb-6 text-xl">
							<i class="<?php echo esc_attr( $value['icon'] ); ?>"></i>
						</div>
						<h3 class="font-serif text-xl font-bold mb-3"><?php echo esc_html( $value['title'] ); ?></h3>
						<p class="text-sm text-white/70"><?php echo esc_html( $value['desc'] ); ?></p>
					</div>
				<?php endif; endforeach; ?>
			</div>
		</div>
	</section>

	<!-- Leadership Team -->
	<?php if ( $team_members->have_posts() ) : ?>
	<section class="py-24 bg-brand-cream">
		<div class="max-w-7xl mx-auto px-4 lg:px-6">
			<div class="text-center mb-16">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Leadership</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink"><?php echo esc_html( $leadership_title ); ?></h2>
			</div>

			<div class="grid md:grid-cols-3 gap-8">
				<?php while ( $team_members->have_posts() ) : $team_members->the_post(); ?>
					<div class="group text-center">
						<div class="relative w-48 h-48 mx-auto mb-6 rounded-full overflow-hidden border-4 border-white shadow-card group-hover:scale-105 transition-transform">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'medium', array(
									'class' => 'w-full h-full object-cover',
									'alt'   => get_the_title(),
								) ); ?>
							<?php else : ?>
								<div class="w-full h-full bg-gradient-to-br from-chroma-blue to-chroma-blueDark flex items-center justify-center">
									<i class="fa-solid fa-user text-6xl text-white/50"></i>
								</div>
							<?php endif; ?>
						</div>

						<h3 class="font-serif text-xl font-bold text-brand-ink">
							<?php the_title(); ?>
						</h3>

						<?php
						$member_title = get_post_meta( get_the_ID(), 'team_member_title', true );
						if ( $member_title ) :
						?>
							<p class="text-xs font-bold uppercase tracking-wider text-chroma-blue mb-3">
								<?php echo esc_html( $member_title ); ?>
							</p>
						<?php endif; ?>

						<?php if ( get_the_content() ) : ?>
							<p class="text-sm text-brand-ink/60 max-w-xs mx-auto">
								<?php the_content(); ?>
							</p>
						<?php endif; ?>
					</div>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- Nutrition & Wellness -->
	<section class="py-24 bg-white border-t border-brand-ink/5">
		<div class="max-w-6xl mx-auto px-4 lg:px-6 grid md:grid-cols-2 gap-16 items-center">
			<div>
				<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Wellness</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6"><?php echo esc_html( $nutrition_title ); ?></h2>
				<p class="text-brand-ink/70 mb-6">
					<?php echo esc_html( $nutrition_description ); ?>
				</p>
				<ul class="space-y-4 text-brand-ink/80">
					<?php if ( $nutrition_bullet1_text ) : ?>
						<li class="flex items-center gap-3">
							<i class="<?php echo esc_attr( $nutrition_bullet1_icon ); ?> text-chroma-red text-xl"></i>
							<span><?php echo esc_html( $nutrition_bullet1_text ); ?></span>
						</li>
					<?php endif; ?>
					<?php if ( $nutrition_bullet2_text ) : ?>
						<li class="flex items-center gap-3">
							<i class="<?php echo esc_attr( $nutrition_bullet2_icon ); ?> text-chroma-orange text-xl"></i>
							<span><?php echo esc_html( $nutrition_bullet2_text ); ?></span>
						</li>
					<?php endif; ?>
					<?php if ( $nutrition_bullet3_text ) : ?>
						<li class="flex items-center gap-3">
							<i class="<?php echo esc_attr( $nutrition_bullet3_icon ); ?> text-brand-ink/40 text-xl"></i>
							<span><?php echo esc_html( $nutrition_bullet3_text ); ?></span>
						</li>
					<?php endif; ?>
				</ul>
			</div>
			<div class="relative h-[400px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white">
				<img src="<?php echo esc_url( $nutrition_image ); ?>" class="w-full h-full object-cover" alt="<?php echo esc_attr( $nutrition_title ); ?>" />
			</div>
		</div>
	</section>

	<!-- Philanthropy Section -->
	<section class="py-24 bg-brand-cream border-t border-brand-ink/5">
		<div class="max-w-6xl mx-auto px-4 lg:px-6 grid md:grid-cols-2 gap-16 items-center">
			<div class="order-2 md:order-1 relative h-[400px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white">
				<img src="<?php echo esc_url( $philanthropy_image ); ?>" class="w-full h-full object-cover" alt="<?php echo esc_attr( $philanthropy_title ); ?>" />
			</div>
			<div class="order-1 md:order-2">
				<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block">Community</span>
				<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6"><?php echo esc_html( $philanthropy_title ); ?></h2>
				<?php if ( $philanthropy_subtitle ) : ?>
					<h3 class="text-xl font-bold text-chroma-blue mb-3"><?php echo esc_html( $philanthropy_subtitle ); ?></h3>
				<?php endif; ?>
				<p class="text-brand-ink/70 mb-6 leading-relaxed">
					<?php echo wp_kses_post( $philanthropy_description ); ?>
				</p>
				<ul class="space-y-4 text-brand-ink/80">
					<?php if ( $philanthropy_bullet1_text ) : ?>
						<li class="flex items-center gap-3">
							<i class="<?php echo esc_attr( $philanthropy_bullet1_icon ); ?> text-chroma-red text-xl"></i>
							<span><?php echo esc_html( $philanthropy_bullet1_text ); ?></span>
						</li>
					<?php endif; ?>
					<?php if ( $philanthropy_bullet2_text ) : ?>
						<li class="flex items-center gap-3">
							<i class="<?php echo esc_attr( $philanthropy_bullet2_icon ); ?> text-chroma-yellow text-xl"></i>
							<span><?php echo esc_html( $philanthropy_bullet2_text ); ?></span>
						</li>
					<?php endif; ?>
					<?php if ( $philanthropy_bullet3_text ) : ?>
						<li class="flex items-center gap-3">
							<i class="<?php echo esc_attr( $philanthropy_bullet3_icon ); ?> text-chroma-green text-xl"></i>
							<span><?php echo esc_html( $philanthropy_bullet3_text ); ?></span>
						</li>
					<?php endif; ?>
				</ul>
			</div>
		</div>
	</section>

	<!-- CTA -->
	<section class="py-24 bg-white text-center">
		<div class="max-w-4xl mx-auto px-4 lg:px-6">
			<h2 class="font-serif text-3xl md:text-5xl font-bold text-brand-ink mb-8"><?php echo esc_html( $cta_title ); ?></h2>
			<p class="text-lg text-brand-ink/60 mb-10"><?php echo esc_html( $cta_description ); ?></p>
			<div class="flex flex-wrap justify-center gap-4">
				<a href="<?php echo esc_url( home_url( '/locations' ) ); ?>" class="px-8 py-4 bg-brand-cream border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:border-chroma-blue hover:text-chroma-blue transition-colors">Find a Location</a>
				<a href="<?php echo esc_url( home_url( '/locations#tour' ) ); ?>" class="px-8 py-4 bg-chroma-blue text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:bg-chroma-blueDark transition-colors shadow-lg">Schedule a Tour</a>
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

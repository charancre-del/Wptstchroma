<?php
/**
 * Template Name: About Page
 *
 * About Us page template matching exact HTML design with proper SEO structure
 *
 * @package Chroma_Excellence
 */

get_header();

while (have_posts()):
	the_post();
	$page_id = get_the_ID();

	// Hero Section
	$hero_badge_text = chroma_get_translated_meta($page_id, 'about_hero_badge_text') ?: __('Established 2015', 'chroma-excellence');
	$hero_title = chroma_get_translated_meta($page_id, 'about_hero_title') ?: __('More than a school. <span class="text-chroma-yellow italic">A second home.</span>', 'chroma-excellence');
	$hero_description = chroma_get_translated_meta($page_id, 'about_hero_description') ?: __('We founded Chroma on a simple belief: Early education should be a perfect blend of rigorous cognitive development and the comforting warmth of family.', 'chroma-excellence');
	$hero_image = chroma_get_translated_meta($page_id, 'about_hero_image') ?: 'https://images.unsplash.com/photo-1588072432836-e10032774350?q=80&w=1000&auto=format&fit=crop';

	// Mission Section
	$mission_quote = chroma_get_translated_meta($page_id, 'about_mission_quote') ?: __('"To cultivate a vibrant community of lifelong learners by blending academic rigor with the nurturing warmth of home, ensuring every child feels seen, valued, and capable."', 'chroma-excellence');

	// Story Section
	$story_title = chroma_get_translated_meta($page_id, 'about_story_title') ?: __('From one classroom to a community.', 'chroma-excellence');
	$story_paragraph1 = chroma_get_translated_meta($page_id, 'about_story_paragraph1') ?: __('Chroma Early Learning Academy began with a single location in Lawrenceville and a mission to redefine "daycare." We didn\'t just want to watch children; we wanted to ignite them.', 'chroma-excellence');
	$story_paragraph2 = chroma_get_translated_meta($page_id, 'about_story_paragraph2') ?: __('Over the last decade, we have grown into a network of 19+ campuses across Metro Atlanta, yet each location retains the intimacy and personal touch of that very first school. We are locally owned, operated by educators, and driven by the success of our families.', 'chroma-excellence');
	$story_image = chroma_get_translated_meta($page_id, 'about_story_image') ?: 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=800&auto=format&fit=crop';

	$stat1_value = chroma_get_translated_meta($page_id, 'about_stat1_value') ?: '19+';
	$stat1_label = chroma_get_translated_meta($page_id, 'about_stat1_label') ?: __('Locations', 'chroma-excellence');
	$stat2_value = chroma_get_translated_meta($page_id, 'about_stat2_value') ?: '2k+';
	$stat2_label = chroma_get_translated_meta($page_id, 'about_stat2_label') ?: __('Students', 'chroma-excellence');
	$stat3_value = chroma_get_translated_meta($page_id, 'about_stat3_value') ?: '450+';
	$stat3_label = chroma_get_translated_meta($page_id, 'about_stat3_label') ?: __('Educators', 'chroma-excellence');
	$stat4_value = chroma_get_translated_meta($page_id, 'about_stat4_value') ?: '100%';
	$stat4_label = chroma_get_translated_meta($page_id, 'about_stat4_label') ?: __('Licensed', 'chroma-excellence');

	// Educators Section
	$educators_title = chroma_get_translated_meta($page_id, 'about_educators_title') ?: __('The Heart of Chroma.', 'chroma-excellence');
	$educators_description = chroma_get_translated_meta($page_id, 'about_educators_description') ?: __('We don\'t just hire supervisors; we hire career educators. Our teachers are the most valuable asset in our classrooms, selected for their passion, patience, and professional credentials.', 'chroma-excellence');

	$educator1_icon = chroma_get_translated_meta($page_id, 'about_educator1_icon') ?: 'fa-solid fa-certificate';
	$educator1_title = chroma_get_translated_meta($page_id, 'about_educator1_title') ?: __('Certified & Credentialed', 'chroma-excellence');
	$educator1_desc = chroma_get_translated_meta($page_id, 'about_educator1_desc') ?: __('Lead teachers hold a CDA (Child Development Associate), TCC, or higher degree in Early Childhood Education. We support ongoing education for every staff member.', 'chroma-excellence');

	$educator2_icon = chroma_get_translated_meta($page_id, 'about_educator2_icon') ?: 'fa-solid fa-user-shield';
	$educator2_title = chroma_get_translated_meta($page_id, 'about_educator2_title') ?: __('Safety First', 'chroma-excellence');
	$educator2_desc = chroma_get_translated_meta($page_id, 'about_educator2_desc') ?: __('Every team member undergoes rigorous federal and state background checks. All staff are certified in CPR and First Aid, with regular refresher courses.', 'chroma-excellence');

	$educator3_icon = chroma_get_translated_meta($page_id, 'about_educator3_icon') ?: 'fa-solid fa-chalkboard-user';
	$educator3_title = chroma_get_translated_meta($page_id, 'about_educator3_title') ?: __('Continuous Growth', 'chroma-excellence');
	$educator3_desc = chroma_get_translated_meta($page_id, 'about_educator3_desc') ?: __('Our educators participate in 20+ hours of annual professional development, specializing in the Prismpath™ curriculum and social-emotional learning.', 'chroma-excellence');

	// Core Values Section
	$values_title = chroma_get_translated_meta($page_id, 'about_values_title') ?: __('The Chroma Standard', 'chroma-excellence');
	$values_description = chroma_get_translated_meta($page_id, 'about_values_description') ?: __('Our culture is built on four non-negotiable pillars that guide every decision we make, from hiring teachers to designing playgrounds.', 'chroma-excellence');

	$value1_icon = chroma_get_translated_meta($page_id, 'about_value1_icon') ?: 'fa-solid fa-heart';
	$value1_title = chroma_get_translated_meta($page_id, 'about_value1_title') ?: __('Unconditional Joy', 'chroma-excellence');
	$value1_desc = chroma_get_translated_meta($page_id, 'about_value1_desc') ?: __('We believe childhood should be magical. We prioritize laughter, play, and warmth in every interaction.', 'chroma-excellence');

	$value2_icon = chroma_get_translated_meta($page_id, 'about_value2_icon') ?: 'fa-solid fa-shield-halved';
	$value2_title = chroma_get_translated_meta($page_id, 'about_value2_title') ?: __('Radical Safety', 'chroma-excellence');
	$value2_desc = chroma_get_translated_meta($page_id, 'about_value2_desc') ?: __('Physical safety is our baseline; emotional safety is our goal. Kids learn best when they feel secure.', 'chroma-excellence');

	$value3_icon = chroma_get_translated_meta($page_id, 'about_value3_icon') ?: 'fa-solid fa-graduation-cap';
	$value3_title = chroma_get_translated_meta($page_id, 'about_value3_title') ?: __('Academic Excellence', 'chroma-excellence');
	$value3_desc = chroma_get_translated_meta($page_id, 'about_value3_desc') ?: __('Using our Prismpath™ model, we deliver rigorous, age-appropriate learning that feels like play.', 'chroma-excellence');

	$value4_icon = chroma_get_translated_meta($page_id, 'about_value4_icon') ?: 'fa-solid fa-users';
	$value4_title = chroma_get_translated_meta($page_id, 'about_value4_title') ?: __('Open Partnership', 'chroma-excellence');
	$value4_desc = chroma_get_translated_meta($page_id, 'about_value4_desc') ?: __('Parents are partners. We maintain open doors, transparent communication, and daily updates.', 'chroma-excellence');

	// Leadership Section
	$leadership_title = chroma_get_translated_meta($page_id, 'about_leadership_title') ?: __('Led by educators, not investors.', 'chroma-excellence');

	// Nutrition Section
	$nutrition_title = chroma_get_translated_meta($page_id, 'about_nutrition_title') ?: __('Fueling growing minds.', 'chroma-excellence');
	$nutrition_description = chroma_get_translated_meta($page_id, 'about_nutrition_description') ?: __('We believe nutrition is a key part of education. Our in-house chefs prepare balanced, nut-free meals daily using fresh ingredients.', 'chroma-excellence');
	$nutrition_image = chroma_get_translated_meta($page_id, 'about_nutrition_image') ?: 'https://images.unsplash.com/photo-1606914506133-2230d94922b5?q=80&w=800&auto=format&fit=crop';

	$nutrition_bullet1_icon = chroma_get_translated_meta($page_id, 'about_nutrition_bullet1_icon') ?: 'fa-solid fa-apple-whole';
	$nutrition_bullet1_text = chroma_get_translated_meta($page_id, 'about_nutrition_bullet1_text') ?: __('CACFP Certified Menus', 'chroma-excellence');
	$nutrition_bullet2_icon = chroma_get_translated_meta($page_id, 'about_nutrition_bullet2_icon') ?: 'fa-solid fa-carrot';
	$nutrition_bullet2_text = chroma_get_translated_meta($page_id, 'about_nutrition_bullet2_text') ?: __('Family-Style Dining to teach manners', 'chroma-excellence');
	$nutrition_bullet3_icon = chroma_get_translated_meta($page_id, 'about_nutrition_bullet3_icon') ?: 'fa-solid fa-ban';
	$nutrition_bullet3_text = chroma_get_translated_meta($page_id, 'about_nutrition_bullet3_text') ?: __('Strict Nut-Free & Allergy Protocols', 'chroma-excellence');

	// Philanthropy Section
	$philanthropy_title = chroma_get_translated_meta($page_id, 'about_philanthropy_title') ?: __('Giving back to our future.', 'chroma-excellence');
	$philanthropy_subtitle = chroma_get_translated_meta($page_id, 'about_philanthropy_subtitle') ?: __('Foundations For Learning Inc.', 'chroma-excellence');
	$philanthropy_description = chroma_get_translated_meta($page_id, 'about_philanthropy_description') ?: __('At Chroma, our commitment extends beyond our classroom walls. Through our partnership with <strong>Foundations For Learning Inc.</strong>, we work to ensure that quality early education is accessible to every child in our community.', 'chroma-excellence');
	$philanthropy_image = chroma_get_translated_meta($page_id, 'about_philanthropy_image') ?: 'https://images.unsplash.com/photo-1593113598332-cd288d649433?q=80&w=800&auto=format&fit=crop';

	$philanthropy_bullet1_icon = chroma_get_translated_meta($page_id, 'about_philanthropy_bullet1_icon') ?: 'fa-solid fa-hand-holding-heart';
	$philanthropy_bullet1_text = chroma_get_translated_meta($page_id, 'about_philanthropy_bullet1_text') ?: __('Scholarship opportunities for families', 'chroma-excellence');
	$philanthropy_bullet2_icon = chroma_get_translated_meta($page_id, 'about_philanthropy_bullet2_icon') ?: 'fa-solid fa-chalkboard-user';
	$philanthropy_bullet2_text = chroma_get_translated_meta($page_id, 'about_philanthropy_bullet2_text') ?: __('Teacher training grants', 'chroma-excellence');
	$philanthropy_bullet3_icon = chroma_get_translated_meta($page_id, 'about_philanthropy_bullet3_icon') ?: 'fa-solid fa-people-roof';
	$philanthropy_bullet3_text = chroma_get_translated_meta($page_id, 'about_philanthropy_bullet3_text') ?: __('Community outreach programs', 'chroma-excellence');

	// CTA Section
	$cta_title = chroma_get_translated_meta($page_id, 'about_cta_title') ?: __('Ready to join the family?', 'chroma-excellence');
	$cta_description = chroma_get_translated_meta($page_id, 'about_cta_description') ?: __('Come see why over 2,000 families trust Chroma with their children\'s early years.', 'chroma-excellence');

	// Get Team Members
	$team_members = chroma_cached_query(
		array(
			'post_type'      => 'team_member',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		),
		'team_members_about',
		7 * DAY_IN_SECONDS
	);
	?>

	<main id="main-content" role="main">
		<!-- Hero Section -->
		<section class="relative pt-16 pb-20 lg:pt-24 lg:pb-32 overflow-hidden">
			<!-- Decor -->
			<div
				class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-chroma-yellowLight/50 to-transparent -z-10">
			</div>
			<div class="absolute bottom-0 left-0 w-96 h-96 bg-chroma-blue/5 rounded-full blur-3xl -z-10"></div>

			<div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-16 items-center">
				<div class="fade-in-up">
					<?php if ($hero_badge_text): ?>
						<div
							class="inline-flex items-center gap-2 bg-white border border-chroma-yellow/40 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-chroma-yellow shadow-sm mb-6">
							<i class="fa-solid fa-heart"></i> <?php echo esc_html($hero_badge_text); ?>
						</div>
					<?php endif; ?>

					<h1 class="font-serif text-[2.8rem] md:text-6xl text-brand-ink mb-6">
						<?php echo wp_kses_post($hero_title); ?>
					</h1>

					<p class="text-lg text-brand-ink/80 mb-8 leading-relaxed">
						<?php echo esc_html($hero_description); ?>
					</p>

					<div class="flex flex-wrap gap-4">
						<a href="#mission"
							class="px-8 py-4 bg-brand-ink text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:bg-chroma-blueDark transition-colors shadow-lg"><?php _e('Our Mission', 'chroma-excellence'); ?></a>
						<a href="<?php echo esc_url(home_url('/locations/')); ?>"
							class="px-8 py-4 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:border-chroma-yellow hover:text-chroma-yellow transition-colors"><?php _e('Find a Campus', 'chroma-excellence'); ?></a>
					</div>
				</div>

				<div class="relative fade-in-up delay-200">
					<div
						class="absolute inset-0 bg-chroma-yellow/10 rounded-[3rem] -rotate-3 transform translate-x-4 translate-y-4">
					</div>
					<div class="relative rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white">
						<img src="<?php echo esc_url($hero_image); ?>"
							alt="<?php echo esc_attr(strip_tags($hero_title)); ?>" class="w-full h-full object-cover" />
					</div>
				</div>
			</div>
		</section>

		<!-- Mission Statement -->
		<section id="mission" class="py-20 bg-chroma-blue text-white relative overflow-hidden">
			<div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5">
			</div>
			<div class="max-w-5xl mx-auto px-4 lg:px-6 text-center relative z-10">
				<span class="text-chroma-yellow font-bold tracking-[0.2em] text-xs uppercase mb-6 block"><?php _e('Our Purpose', 'chroma-excellence'); ?></span>
				<h2 class="text-3xl md:text-5xl font-serif leading-tight mb-8">
					<?php echo esc_html($mission_quote); ?>
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
						<img src="<?php echo esc_url($story_image); ?>"
							class="rounded-[2.5rem] shadow-card border border-brand-ink/5 relative z-10" alt="<?php esc_attr_e('Our Story', 'chroma-excellence'); ?>" />
					</div>
					<div class="order-1 md:order-2">
						<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Our Story', 'chroma-excellence'); ?></span>
						<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
							<?php echo esc_html($story_title); ?>
						</h2>
						<p class="text-brand-ink/80 mb-6 leading-relaxed">
							<?php echo esc_html($story_paragraph1); ?>
						</p>
						<p class="text-brand-ink/80 mb-6 leading-relaxed">
							<?php echo esc_html($story_paragraph2); ?>
						</p>
					</div>
				</div>

				<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
					<?php
					$stats = array(
						array('value' => $stat1_value, 'label' => $stat1_label, 'color' => 'chroma-blue'),
						array('value' => $stat2_value, 'label' => $stat2_label, 'color' => 'chroma-red'),
						array('value' => $stat3_value, 'label' => $stat3_label, 'color' => 'chroma-yellow'),
						array('value' => $stat4_value, 'label' => $stat4_label, 'color' => 'chroma-green'),
					);

					foreach ($stats as $stat):
						if ($stat['value']):
							?>
							<div class="p-8 bg-brand-cream rounded-[2rem] text-center border border-brand-ink/5">
								<div
									class="text-3xl md:text-4xl font-serif font-bold text-<?php echo esc_attr($stat['color']); ?> mb-2">
									<?php echo esc_html($stat['value']); ?>
								</div>
								<div class="text-xs font-bold uppercase tracking-wider text-brand-ink/80">
									<?php echo esc_html($stat['label']); ?>
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
					<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Our Educators', 'chroma-excellence'); ?></span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-4">
						<?php echo esc_html($educators_title); ?>
					</h2>
					<p class="text-brand-ink/80"><?php echo esc_html($educators_description); ?></p>
				</div>

				<div class="grid md:grid-cols-3 gap-8">
					<?php
					$educators = array(
						array('icon' => $educator1_icon, 'title' => $educator1_title, 'desc' => $educator1_desc, 'color' => 'chroma-redLight', 'icon_color' => 'chroma-red'),
						array('icon' => $educator2_icon, 'title' => $educator2_title, 'desc' => $educator2_desc, 'color' => 'chroma-blueLight', 'icon_color' => 'chroma-blue'),
						array('icon' => $educator3_icon, 'title' => $educator3_title, 'desc' => $educator3_desc, 'color' => 'chroma-greenLight', 'icon_color' => 'chroma-green'),
					);

					foreach ($educators as $educator):
						if ($educator['title']):
							?>
							<div class="bg-white p-8 rounded-[2rem] shadow-soft">
								<div
									class="w-14 h-14 bg-<?php echo esc_attr($educator['color']); ?> text-<?php echo esc_attr($educator['icon_color']); ?> rounded-2xl flex items-center justify-center text-2xl mb-6">
									<i class="<?php echo esc_attr($educator['icon']); ?>"></i>
								</div>
								<h3 class="font-serif text-xl font-bold text-brand-ink mb-3">
									<?php echo esc_html($educator['title']); ?>
								</h3>
								<p class="text-sm text-brand-ink/90 leading-relaxed">
									<?php echo esc_html($educator['desc']); ?>
								</p>
							</div>
						<?php endif; endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Core Values -->
		<section id="values" class="py-24 bg-chroma-blueDark text-white relative overflow-hidden">
			<div
				class="absolute right-0 top-0 w-full h-full bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-white/5 via-transparent to-transparent">
			</div>

			<div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10">
				<div class="text-center mb-16">
					<h2 class="text-3xl md:text-4xl font-serif font-bold mb-4"><?php echo esc_html($values_title); ?></h2>
					<p class="text-white/90 max-w-2xl mx-auto"><?php echo esc_html($values_description); ?></p>
				</div>

				<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
					<?php
					$values = array(
						array('icon' => $value1_icon, 'title' => $value1_title, 'desc' => $value1_desc, 'color' => 'chroma-red'),
						array('icon' => $value2_icon, 'title' => $value2_title, 'desc' => $value2_desc, 'color' => 'chroma-blue'),
						array('icon' => $value3_icon, 'title' => $value3_title, 'desc' => $value3_desc, 'color' => 'chroma-yellow'),
						array('icon' => $value4_icon, 'title' => $value4_title, 'desc' => $value4_desc, 'color' => 'chroma-green'),
					);

					foreach ($values as $value):
						if ($value['title']):
							?>
							<div
								class="bg-white/10 backdrop-blur-sm p-8 rounded-3xl border border-white/10 hover:bg-white/15 transition-colors">
								<div
									class="w-12 h-12 bg-<?php echo esc_attr($value['color']); ?> rounded-xl flex items-center justify-center mb-6 text-xl">
									<i class="<?php echo esc_attr($value['icon']); ?>"></i>
								</div>
								<h3 class="font-serif text-xl font-bold mb-3"><?php echo esc_html($value['title']); ?></h3>
								<p class="text-sm text-white/90"><?php echo esc_html($value['desc']); ?></p>
							</div>
						<?php endif; endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Leadership Team -->
		<?php if ($team_members->have_posts()): ?>
			<section class="py-24 bg-brand-cream">
				<div class="max-w-7xl mx-auto px-4 lg:px-6">
					<div class="text-center mb-16">
						<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Leadership', 'chroma-excellence'); ?></span>
						<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink">
							<?php echo esc_html($leadership_title); ?>
						</h2>
					</div>
					<div class="grid md:grid-cols-3 gap-8">
						<?php while ($team_members->have_posts()):
							$team_members->the_post();
							$member_title = chroma_get_translated_meta(get_the_ID(), 'team_member_title');
							$member_bio = apply_filters('the_content', get_the_content());
							?>
							<div class="text-center group">
								<div
									class="relative w-48 h-48 mx-auto mb-6 rounded-full overflow-hidden shadow-lg border-4 border-white">
									<?php the_post_thumbnail('medium', array('class' => 'w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500')); ?>
								</div>
								<h3 class="font-serif text-xl font-bold text-brand-ink"><?php the_title(); ?></h3>
								<p class="text-xs font-bold uppercase tracking-wider text-chroma-blue mb-2">
									<?php echo esc_html($member_title); ?>
								</p>
								<button
									class="chroma-read-bio-btn text-sm font-bold text-chroma-blue hover:text-chroma-blueDark underline mt-2"
									data-bio-target="bio-<?php the_ID(); ?>" data-member-name="<?php the_title_attribute(); ?>"
									data-member-title="<?php echo esc_attr($member_title); ?>"
									data-member-image="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large') ?: ''); ?>"
									aria-label="<?php esc_attr_e('Read bio for', 'chroma-excellence'); ?> <?php the_title_attribute(); ?>">
									<?php _e('Read Bio', 'chroma-excellence'); ?>
								</button>
								<div id="bio-<?php the_ID(); ?>" class="hidden">
									<?php echo wp_kses_post($member_bio); ?>
								</div>
							</div>
						<?php endwhile;
						wp_reset_postdata(); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>
<!-- ... -->
		<!-- Nutrition & Wellness -->
		<section class="py-24 bg-white border-t border-brand-ink/5">
			<div class="max-w-6xl mx-auto px-4 lg:px-6 grid md:grid-cols-2 gap-16 items-center">
				<div>
					<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Wellness', 'chroma-excellence'); ?></span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
						<?php echo esc_html($nutrition_title); ?>
					</h2>
					<p class="text-brand-ink/80 text-lg mb-8 leading-relaxed">
						<?php echo esc_html($nutrition_description); ?>
					</p>
					<ul class="space-y-4">
						<?php
						$nut_bullets = array(
							array('icon' => $nutrition_bullet1_icon, 'text' => $nutrition_bullet1_text),
							array('icon' => $nutrition_bullet2_icon, 'text' => $nutrition_bullet2_text),
							array('icon' => $nutrition_bullet3_icon, 'text' => $nutrition_bullet3_text),
						);
						foreach ($nut_bullets as $bullet):
							if ($bullet['text']): ?>
								<li class="flex items-center gap-3">
									<i class="<?php echo esc_attr($bullet['icon']); ?> text-chroma-green text-xl"></i>
									<span class="text-brand-ink/90 font-medium"><?php echo esc_html($bullet['text']); ?></span>
								</li>
							<?php endif; endforeach; ?>
					</ul>
				</div>
				<div class="relative h-[400px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white">
					<img src="<?php echo esc_url($nutrition_image); ?>" class="w-full h-full object-cover"
						alt="<?php echo esc_attr($nutrition_title); ?>" />
				</div>
			</div>
		</section>
		<!-- Philanthropy Section -->
		<section class="py-24 bg-brand-cream border-t border-brand-ink/5">
			<div class="max-w-6xl mx-auto px-4 lg:px-6 grid md:grid-cols-2 gap-16 items-center">
				<div
					class="order-2 md:order-1 relative h-[400px] rounded-[3rem] overflow-hidden shadow-2xl border-4 border-white">
					<img src="<?php echo esc_url($philanthropy_image); ?>" class="w-full h-full object-cover"
						alt="<?php echo esc_attr($philanthropy_title); ?>" />
				</div>
				<div class="order-1 md:order-2">
					<span class="text-chroma-blue font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php _e('Community', 'chroma-excellence'); ?></span>
					<h2 class="text-3xl md:text-4xl font-serif font-bold text-brand-ink mb-6">
						<?php echo esc_html($philanthropy_title); ?>
					</h2>
					<?php if ($philanthropy_subtitle): ?>
						<h3 class="text-lg font-bold text-chroma-blue mb-4">
							<?php echo esc_html($philanthropy_subtitle); ?>
						</h3>
					<?php endif; ?>
					<div class="text-brand-ink/80 text-lg mb-8 leading-relaxed">
						<?php echo wp_kses_post(wpautop($philanthropy_description)); ?>
					</div>
					<ul class="space-y-4 mb-8">
						<?php
						$phil_bullets = array(
							array('icon' => $philanthropy_bullet1_icon, 'text' => $philanthropy_bullet1_text),
							array('icon' => $philanthropy_bullet2_icon, 'text' => $philanthropy_bullet2_text),
							array('icon' => $philanthropy_bullet3_icon, 'text' => $philanthropy_bullet3_text),
						);
						foreach ($phil_bullets as $bullet):
							if ($bullet['text']): ?>
								<li class="flex items-center gap-3">
									<div
										class="w-8 h-8 rounded-full bg-brand-cream border border-brand-ink/10 flex items-center justify-center text-chroma-blue">
										<i class="<?php echo esc_attr($bullet['icon']); ?>"></i>
									</div>
									<span class="text-brand-ink/90 font-medium"><?php echo esc_html($bullet['text']); ?></span>
								</li>
							<?php endif; endforeach; ?>
					</ul>
					<?php if ($cta_title): ?>
					<div class="mt-8 pt-8 border-t border-brand-ink/5">
						<h4 class="font-serif text-2xl font-bold text-brand-ink mb-2"><?php echo esc_html($cta_title); ?></h4>
						<p class="text-brand-ink/80 mb-6"><?php echo esc_html($cta_description); ?></p>
						<div class="flex flex-wrap gap-4">
							<a href="<?php echo esc_url(home_url('/locations/')); ?>"
								class="px-6 py-3 bg-brand-ink text-white font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:bg-chroma-blueDark transition-colors"><?php _e('Find a Location', 'chroma-excellence'); ?></a>
							<a href="<?php echo esc_url(home_url('/locations/#tour')); ?>"
								class="px-6 py-3 bg-white border border-brand-ink/10 text-brand-ink font-bold rounded-full uppercase tracking-[0.2em] text-xs hover:border-chroma-red hover:text-chroma-red transition-colors"><?php _e('Schedule a Tour', 'chroma-excellence'); ?></a>
						</div>
					</div>
					<?php endif; ?>
				</div>
			</div>
		</section>

	</main>

	<style>
		.fade-in-up {
			animation: fadeInUp 0.8s ease forwards;
			opacity: 0;
			transform: translateY(20px);
		}

		.delay-100 {
			animation-delay: 0.1s;
		}

		.delay-200 {
			animation-delay: 0.2s;
		}

		@keyframes fadeInUp {
			to {
				opacity: 1;
				transform: translateY(0);
			}
		}
	</style>

	<!-- Bio Modal -->
	<div id="chroma-bio-modal"
		class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-brand-ink/80 backdrop-blur-sm"
		role="dialog" aria-modal="true" aria-labelledby="chroma-bio-modal-title">
		<div
			class="bg-white rounded-[2rem] max-w-5xl w-full max-h-[90vh] overflow-hidden shadow-2xl relative flex flex-col">
			<button id="chroma-bio-close"
				class="absolute top-6 right-6 text-brand-ink/80 hover:text-chroma-red transition-colors z-10 bg-white/50 rounded-full p-2"
				aria-label="Close modal">
				<i class="fa-solid fa-xmark text-2xl"></i>
			</button>

			<div class="flex-grow overflow-y-auto p-8 md:p-12">
				<div class="grid md:grid-cols-2 gap-12 items-start">
					<!-- Image Column -->
					<div class="relative sticky top-0">
						<div id="chroma-bio-modal-image"
							class="aspect-[3/4] rounded-2xl overflow-hidden bg-brand-cream shadow-lg flex items-center justify-center">
							<!-- Image injected here -->
							<i class="fa-solid fa-user text-6xl text-brand-ink/10"></i>
						</div>
						<div class="mt-6 text-center md:text-left">
							<h3 id="chroma-bio-modal-title"
								class="font-serif text-3xl font-bold text-brand-ink mb-2 leading-tight"></h3>
							<p id="chroma-bio-modal-subtitle"
								class="text-sm font-bold uppercase tracking-wider text-chroma-blue"></p>
						</div>
					</div>

					<!-- Content Column -->
					<div id="chroma-bio-modal-content" class="prose prose-lg text-brand-ink/90">
						<!-- Bio content injected here -->
					</div>
				</div>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const modal = document.getElementById('chroma-bio-modal');
			const closeBtn = document.getElementById('chroma-bio-close');
			const title = document.getElementById('chroma-bio-modal-title');
			const subtitle = document.getElementById('chroma-bio-modal-subtitle');
			const content = document.getElementById('chroma-bio-modal-content');
			let lastFocusedElement;

			function openModal(btn) {
				const targetId = btn.getAttribute('data-bio-target');
				const name = btn.getAttribute('data-member-name');
				const jobTitle = btn.getAttribute('data-member-title');
				const imageUrl = btn.getAttribute('data-member-image');
				const sourceContent = document.getElementById(targetId);

				if (sourceContent) {
					lastFocusedElement = btn;
					title.textContent = name;
					subtitle.textContent = jobTitle || '';
					content.innerHTML = sourceContent.innerHTML;

					// Populate image if available
					const imageContainer = document.getElementById('chroma-bio-modal-image');
					if (imageUrl && imageContainer) {
						imageContainer.innerHTML = `<img src="${imageUrl}" alt="${name}" class="w-full h-full object-cover">`;
					} else if (imageContainer) {
						// Reset to placeholder if no image
						imageContainer.innerHTML = '<i class="fa-solid fa-user text-6xl text-white/30"></i>';
					}

					modal.classList.remove('hidden');
					closeBtn.focus();
					document.body.style.overflow = 'hidden';

					// Trap focus
					modal.addEventListener('keydown', trapFocus);
				}
			}

			function closeModal() {
				modal.classList.add('hidden');
				document.body.style.overflow = '';
				if (lastFocusedElement) lastFocusedElement.focus();
				modal.removeEventListener('keydown', trapFocus);
			}

			function trapFocus(e) {
				if (e.key === 'Escape') {
					closeModal();
					return;
				}
				if (e.key === 'Tab') {
					// Simple focus trap
					const focusableContent = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
					const first = focusableContent[0];
					const last = focusableContent[focusableContent.length - 1];

					if (e.shiftKey) {
						if (document.activeElement === first) {
							last.focus();
							e.preventDefault();
						}
					} else {
						if (document.activeElement === last) {
							first.focus();
							e.preventDefault();
						}
					}
				}
			}

			document.querySelectorAll('.chroma-read-bio-btn').forEach(btn => {
				btn.addEventListener('click', () => openModal(btn));
			});

			if (closeBtn) closeBtn.addEventListener('click', closeModal);

			if (modal) modal.addEventListener('click', (e) => {
				if (e.target === modal) closeModal();
			});
		});
	</script>

	<?php
endwhile;
get_footer();

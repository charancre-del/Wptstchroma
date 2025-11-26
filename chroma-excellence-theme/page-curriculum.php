<?php
/**
 * Template Name: Curriculum Page
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$page_id = get_the_ID();

// Hero Section
$hero_badge       = get_post_meta( $page_id, 'curriculum_hero_badge', true ) ?: 'The Chroma Difference';
$hero_title       = get_post_meta( $page_id, 'curriculum_hero_title', true ) ?: 'Scientific rigor. <br><span class="italic text-chroma-green">Joyful delivery.</span>';
$hero_description = get_post_meta( $page_id, 'curriculum_hero_description', true ) ?: 'Our proprietary Prismpath™ curriculum isn\'t just about ABCs. It\'s a comprehensive framework designed to build the critical thinking, emotional intelligence, and social skills needed for the 21st century.';

// Framework Section
$framework_title       = get_post_meta( $page_id, 'curriculum_framework_title', true ) ?: 'The Prismpath™ Framework';
$framework_description = get_post_meta( $page_id, 'curriculum_framework_description', true ) ?: 'Just as a prism refracts light into a spectrum, our curriculum refracts "play" into five distinct pillars of development. Every activity in our classrooms targets one or more of these areas.';

$pillars = array(
	array(
		'name'        => 'physical',
		'borderClass' => 'border-chroma-red',
		'bgClass'     => 'bg-chroma-red/10',
		'textClass'   => 'text-chroma-red',
		'icon'        => get_post_meta( $page_id, 'curriculum_pillar_physical_icon', true ) ?: 'fa-solid fa-person-running',
		'title'       => get_post_meta( $page_id, 'curriculum_pillar_physical_title', true ) ?: 'Physical',
		'desc'        => get_post_meta( $page_id, 'curriculum_pillar_physical_desc', true ) ?: 'Gross motor coordination, fine motor grip strength, sensory integration, and nutritional health.',
	),
	array(
		'name'        => 'emotional',
		'borderClass' => 'border-chroma-yellow',
		'bgClass'     => 'bg-chroma-yellow/10',
		'textClass'   => 'text-chroma-yellow',
		'icon'        => get_post_meta( $page_id, 'curriculum_pillar_emotional_icon', true ) ?: 'fa-solid fa-face-smile',
		'title'       => get_post_meta( $page_id, 'curriculum_pillar_emotional_title', true ) ?: 'Emotional',
		'desc'        => get_post_meta( $page_id, 'curriculum_pillar_emotional_desc', true ) ?: 'Self-regulation, identifying feelings, building resilience, and developing a secure sense of self.',
	),
	array(
		'name'        => 'social',
		'borderClass' => 'border-chroma-green',
		'bgClass'     => 'bg-chroma-green/10',
		'textClass'   => 'text-chroma-green',
		'icon'        => get_post_meta( $page_id, 'curriculum_pillar_social_icon', true ) ?: 'fa-solid fa-users',
		'title'       => get_post_meta( $page_id, 'curriculum_pillar_social_title', true ) ?: 'Social',
		'desc'        => get_post_meta( $page_id, 'curriculum_pillar_social_desc', true ) ?: 'Conflict resolution, collaboration, empathy, communication, and understanding community roles.',
	),
	array(
		'name'        => 'academic',
		'borderClass' => 'border-chroma-blue',
		'bgClass'     => 'bg-chroma-blue/10',
		'textClass'   => 'text-chroma-blue',
		'icon'        => get_post_meta( $page_id, 'curriculum_pillar_academic_icon', true ) ?: 'fa-solid fa-brain',
		'title'       => get_post_meta( $page_id, 'curriculum_pillar_academic_title', true ) ?: 'Academic',
		'desc'        => get_post_meta( $page_id, 'curriculum_pillar_academic_desc', true ) ?: 'Early literacy, logic & numeracy, scientific inquiry, critical thinking, and language acquisition.',
	),
	array(
		'name'        => 'creative',
		'borderClass' => 'border-chroma-blueDark',
		'bgClass'     => 'bg-chroma-blueDark/10',
		'textClass'   => 'text-chroma-blueDark',
		'icon'        => get_post_meta( $page_id, 'curriculum_pillar_creative_icon', true ) ?: 'fa-solid fa-palette',
		'title'       => get_post_meta( $page_id, 'curriculum_pillar_creative_title', true ) ?: 'Creative',
		'desc'        => get_post_meta( $page_id, 'curriculum_pillar_creative_desc', true ) ?: 'Divergent thinking, artistic expression, music & movement, and dramatic/imaginative play.',
	),
);

// Timeline Section
$timeline_badge       = get_post_meta( $page_id, 'curriculum_timeline_badge', true ) ?: 'Learning Journey';
$timeline_title       = get_post_meta( $page_id, 'curriculum_timeline_title', true ) ?: 'How learning evolves.';
$timeline_description = get_post_meta( $page_id, 'curriculum_timeline_description', true ) ?: 'Our curriculum is not static. It shifts and matures alongside your child, moving from sensory-based discovery to logic-based inquiry.';
$timeline_image       = get_post_meta( $page_id, 'curriculum_timeline_image', true ) ?: 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=800&auto=format&fit=crop';

$stages = array(
	array(
		'name'        => 'foundation',
		'borderClass' => 'border-chroma-red',
		'bgClass'     => 'bg-chroma-red',
		'title'       => get_post_meta( $page_id, 'curriculum_stage_foundation_title', true ) ?: 'Foundation (0-18 Months)',
		'desc'        => get_post_meta( $page_id, 'curriculum_stage_foundation_desc', true ) ?: 'Focus on security and senses. Learning happens through touch, sound, and responsive caregiving.',
	),
	array(
		'name'        => 'discovery',
		'borderClass' => 'border-chroma-yellow',
		'bgClass'     => 'bg-chroma-yellow',
		'title'       => get_post_meta( $page_id, 'curriculum_stage_discovery_title', true ) ?: 'Discovery (18 Months - 3 Years)',
		'desc'        => get_post_meta( $page_id, 'curriculum_stage_discovery_desc', true ) ?: 'Focus on autonomy and language. "I can do it!" is the theme as we support potty training and early speech.',
	),
	array(
		'name'        => 'readiness',
		'borderClass' => 'border-chroma-green',
		'bgClass'     => 'bg-chroma-green',
		'title'       => get_post_meta( $page_id, 'curriculum_stage_readiness_title', true ) ?: 'Readiness (3 Years - 5 Years)',
		'desc'        => get_post_meta( $page_id, 'curriculum_stage_readiness_desc', true ) ?: 'Focus on executive function and logic. Multi-step projects, early writing, and complex social play prepare for Kindergarten.',
	),
);

// Environment Section
$env_badge       = get_post_meta( $page_id, 'curriculum_env_badge', true ) ?: 'Environment';
$env_title       = get_post_meta( $page_id, 'curriculum_env_title', true ) ?: 'The classroom is the "Third Teacher."';
$env_description = get_post_meta( $page_id, 'curriculum_env_description', true ) ?: 'We believe the environment itself acts as a teacher, guiding learning alongside our educators. Our classrooms are intentionally designed zones that invite exploration, curiosity, and independence without needing constant adult direction.';

$zones = array(
	array(
		'name'  => 'construction',
		'emoji' => get_post_meta( $page_id, 'curriculum_zone_construction_emoji', true ) ?: '🧱',
		'title' => get_post_meta( $page_id, 'curriculum_zone_construction_title', true ) ?: 'Construction Zone',
		'desc'  => get_post_meta( $page_id, 'curriculum_zone_construction_desc', true ) ?: 'Blocks and engineering tools to teach balance, gravity, and spatial reasoning.',
	),
	array(
		'name'  => 'atelier',
		'emoji' => get_post_meta( $page_id, 'curriculum_zone_atelier_emoji', true ) ?: '🎨',
		'title' => get_post_meta( $page_id, 'curriculum_zone_atelier_title', true ) ?: 'Atelier (Art Studio)',
		'desc'  => get_post_meta( $page_id, 'curriculum_zone_atelier_desc', true ) ?: 'Open access to paints, clays, and loose parts for unrestricted creative expression.',
	),
	array(
		'name'  => 'literacy',
		'emoji' => get_post_meta( $page_id, 'curriculum_zone_literacy_emoji', true ) ?: '📖',
		'title' => get_post_meta( $page_id, 'curriculum_zone_literacy_title', true ) ?: 'Literacy Nook',
		'desc'  => get_post_meta( $page_id, 'curriculum_zone_literacy_desc', true ) ?: 'Cozy, soft spaces with diverse books to foster a lifelong love of reading.',
	),
);

// Milestones Section
$milestones_title    = get_post_meta( $page_id, 'curriculum_milestones_title', true ) ?: 'Measuring Milestones';
$milestones_subtitle = get_post_meta( $page_id, 'curriculum_milestones_subtitle', true ) ?: 'We don\'t just watch them grow; we measure it to ensure no child falls behind.';

$milestone_cards = array(
	array(
		'name'        => 'tracking',
		'bgClass'     => 'bg-chroma-blue/5',
		'borderClass' => 'border-chroma-blue/20',
		'iconBg'      => 'bg-chroma-blue/10',
		'textClass'   => 'text-chroma-blue',
		'icon'        => get_post_meta( $page_id, 'curriculum_milestone_tracking_icon', true ) ?: 'fa-solid fa-chart-line',
		'title'       => get_post_meta( $page_id, 'curriculum_milestone_tracking_title', true ) ?: 'Daily Progress Tracking',
		'desc'        => get_post_meta( $page_id, 'curriculum_milestone_tracking_desc', true ) ?: 'We use a digital portfolio system to capture daily moments of learning. From an infant\'s first roll to a preschooler\'s first written letter, these micro-wins are documented and shared with you in real-time.',
		'bullet1'     => get_post_meta( $page_id, 'curriculum_milestone_tracking_bullet1', true ) ?: 'Photo/Video Evidence',
		'bullet2'     => get_post_meta( $page_id, 'curriculum_milestone_tracking_bullet2', true ) ?: 'Daily Activity Reports',
	),
	array(
		'name'        => 'screenings',
		'bgClass'     => 'bg-chroma-red/5',
		'borderClass' => 'border-chroma-red/20',
		'iconBg'      => 'bg-chroma-red/10',
		'textClass'   => 'text-chroma-red',
		'icon'        => get_post_meta( $page_id, 'curriculum_milestone_screenings_icon', true ) ?: 'fa-solid fa-magnifying-glass-chart',
		'title'       => get_post_meta( $page_id, 'curriculum_milestone_screenings_title', true ) ?: 'Developmental Screenings',
		'desc'        => get_post_meta( $page_id, 'curriculum_milestone_screenings_desc', true ) ?: 'We utilize the <strong>ASQ-3 (Ages & Stages Questionnaires)</strong> standard to conduct formal screenings at key age intervals. This helps us identify strengths and potential areas for early intervention support proactively.',
		'bullet1'     => get_post_meta( $page_id, 'curriculum_milestone_screenings_bullet1', true ) ?: 'Conducted at 4, 8, 12, 18, 24 Months',
		'bullet2'     => get_post_meta( $page_id, 'curriculum_milestone_screenings_bullet2', true ) ?: 'Partnership with Specialists',
	),
	array(
		'name'        => 'assessments',
		'bgClass'     => 'bg-chroma-yellow/5',
		'borderClass' => 'border-chroma-yellow/20',
		'iconBg'      => 'bg-chroma-yellow/10',
		'textClass'   => 'text-chroma-yellow',
		'icon'        => get_post_meta( $page_id, 'curriculum_milestone_assessments_icon', true ) ?: 'fa-solid fa-file-signature',
		'title'       => get_post_meta( $page_id, 'curriculum_milestone_assessments_title', true ) ?: 'Formal Assessments',
		'desc'        => get_post_meta( $page_id, 'curriculum_milestone_assessments_desc', true ) ?: 'Twice a year (Fall and Spring), teachers conduct comprehensive assessments aligning with Georgia Early Learning and Development Standards (GELDS). These form the basis for our detailed Parent-Teacher Conferences.',
		'bullet1'     => get_post_meta( $page_id, 'curriculum_milestone_assessments_bullet1', true ) ?: 'Biannual Conferences',
		'bullet2'     => get_post_meta( $page_id, 'curriculum_milestone_assessments_bullet2', true ) ?: 'Individualized Lesson Planning',
	),
);

// CTA Section
$cta_title       = get_post_meta( $page_id, 'curriculum_cta_title', true ) ?: 'See the curriculum in action.';
$cta_description = get_post_meta( $page_id, 'curriculum_cta_description', true ) ?: 'Schedule a tour to see our "Third Teacher" classrooms and meet the educators bringing Prismpath™ to life.';
?>

<main id="primary" class="site-main" role="main">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<!-- Hero Section -->
		<section id="hero" class="relative pt-28 pb-20 px-4 lg:px-6 overflow-hidden bg-gradient-to-br from-brand-warm/10 via-white to-chroma-green/5">
			<div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(255,228,230,0.4),transparent_50%),radial-gradient(circle_at_70%_80%,rgba(168,230,207,0.3),transparent_50%)]"></div>

			<div class="max-w-5xl mx-auto text-center relative z-10">
				<div class="inline-flex items-center gap-2 bg-white border border-chroma-green/40 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-chroma-green shadow-sm mb-6">
					<i class="fa-solid fa-graduation-cap"></i>
					<?php echo esc_html( $hero_badge ); ?>
				</div>

				<h1 class="font-serif text-[2.8rem] md:text-6xl text-brand-ink mb-6 leading-tight">
					<?php echo wp_kses_post( $hero_title ); ?>
				</h1>

				<p class="text-xl md:text-2xl text-brand-ink/70 max-w-3xl mx-auto leading-relaxed">
					<?php echo esc_html( $hero_description ); ?>
				</p>
			</div>
		</section>

		<!-- Prismpath Framework Section -->
		<section id="framework" class="py-24 px-4 lg:px-6 bg-white">
			<div class="max-w-7xl mx-auto">
				<div class="text-center mb-16">
					<h2 class="font-serif text-4xl md:text-5xl text-brand-ink mb-4">
						<?php echo esc_html( $framework_title ); ?>
					</h2>
					<p class="text-lg text-brand-ink/70 max-w-3xl mx-auto">
						<?php echo esc_html( $framework_description ); ?>
					</p>
				</div>

				<div class="grid md:grid-cols-2 lg:grid-cols-5 gap-6">
					<?php foreach ( $pillars as $pillar ) : ?>
					<div class="bg-white p-8 rounded-3xl border border-brand-ink/10 shadow-card hover:shadow-cardHover transition-all group">
						<div class="border-t-4 <?php echo esc_attr( $pillar['borderClass'] ); ?> -mt-8 rounded-t-3xl pt-8">
							<div class="w-14 h-14 <?php echo esc_attr( $pillar['bgClass'] ); ?> rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
								<i class="<?php echo esc_attr( $pillar['icon'] ); ?> <?php echo esc_attr( $pillar['textClass'] ); ?> text-2xl"></i>
							</div>
							<h3 class="text-xl font-bold text-brand-ink mb-3">
								<?php echo esc_html( $pillar['title'] ); ?>
							</h3>
							<p class="text-brand-ink/70 text-sm leading-relaxed">
								<?php echo esc_html( $pillar['desc'] ); ?>
							</p>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Developmental Timeline Section -->
		<section id="timeline" class="py-24 px-4 lg:px-6 bg-brand-warm/5">
			<div class="max-w-7xl mx-auto">
				<div class="grid md:grid-cols-2 gap-16 items-center">
					<!-- Content -->
					<div>
						<span class="text-chroma-yellow font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
							<?php echo esc_html( $timeline_badge ); ?>
						</span>
						<h2 class="font-serif text-4xl md:text-5xl text-brand-ink mb-6">
							<?php echo esc_html( $timeline_title ); ?>
						</h2>
						<p class="text-lg text-brand-ink/70 mb-12">
							<?php echo esc_html( $timeline_description ); ?>
						</p>

						<div class="space-y-10">
							<?php foreach ( $stages as $stage ) : ?>
							<div class="relative pl-8 border-l-4 <?php echo esc_attr( $stage['borderClass'] ); ?>">
								<div class="absolute -left-3 top-0 w-5 h-5 <?php echo esc_attr( $stage['bgClass'] ); ?> rounded-full"></div>
								<h3 class="text-xl font-bold text-brand-ink mb-2">
									<?php echo esc_html( $stage['title'] ); ?>
								</h3>
								<p class="text-brand-ink/70">
									<?php echo esc_html( $stage['desc'] ); ?>
								</p>
							</div>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Image -->
					<div class="relative order-first md:order-last">
						<div class="absolute -right-10 -top-10 w-40 h-40 bg-chroma-green/10 rounded-full blur-2xl"></div>
						<img src="<?php echo esc_url( $timeline_image ); ?>"
							 alt="<?php echo esc_attr( $timeline_title ); ?>"
							 class="rounded-[2.5rem] shadow-card border border-brand-ink/5 relative z-10" />
					</div>
				</div>
			</div>
		</section>

		<!-- Environment (Third Teacher) Section -->
		<section id="environment" class="py-24 px-4 lg:px-6 bg-chroma-blueDark text-white relative overflow-hidden">
			<div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5"></div>

			<div class="max-w-7xl mx-auto relative z-10">
				<div class="text-center mb-16">
					<span class="text-chroma-yellow font-bold tracking-[0.2em] text-xs uppercase mb-3 block">
						<?php echo esc_html( $env_badge ); ?>
					</span>
					<h2 class="font-serif text-4xl md:text-5xl mb-4">
						<?php echo esc_html( $env_title ); ?>
					</h2>
					<p class="text-lg text-white/80 max-w-3xl mx-auto">
						<?php echo esc_html( $env_description ); ?>
					</p>
				</div>

				<div class="grid md:grid-cols-3 gap-8">
					<?php foreach ( $zones as $zone ) : ?>
					<div class="bg-white/10 backdrop-blur-sm p-8 rounded-3xl border border-white/10 hover:bg-white/15 transition-all">
						<div class="text-6xl mb-6"><?php echo esc_html( $zone['emoji'] ); ?></div>
						<h3 class="text-2xl font-bold mb-3">
							<?php echo esc_html( $zone['title'] ); ?>
						</h3>
						<p class="text-white/80 leading-relaxed">
							<?php echo esc_html( $zone['desc'] ); ?>
						</p>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- Measuring Milestones Section -->
		<section id="milestones" class="py-24 px-4 lg:px-6 bg-white">
			<div class="max-w-7xl mx-auto">
				<div class="text-center mb-16">
					<h2 class="font-serif text-4xl md:text-5xl text-brand-ink mb-4">
						<?php echo esc_html( $milestones_title ); ?>
					</h2>
					<p class="text-lg text-brand-ink/70 max-w-3xl mx-auto">
						<?php echo esc_html( $milestones_subtitle ); ?>
					</p>
				</div>

				<div class="grid md:grid-cols-3 gap-8">
					<?php foreach ( $milestone_cards as $card ) : ?>
					<div class="<?php echo esc_attr( $card['bgClass'] ); ?> p-8 rounded-3xl border <?php echo esc_attr( $card['borderClass'] ); ?> hover:shadow-card transition-all">
						<div class="w-16 h-16 <?php echo esc_attr( $card['iconBg'] ); ?> rounded-2xl flex items-center justify-center mb-6">
							<i class="<?php echo esc_attr( $card['icon'] ); ?> <?php echo esc_attr( $card['textClass'] ); ?> text-3xl"></i>
						</div>
						<h3 class="text-2xl font-bold text-brand-ink mb-4">
							<?php echo esc_html( $card['title'] ); ?>
						</h3>
						<p class="text-brand-ink/70 mb-6 leading-relaxed">
							<?php echo wp_kses_post( $card['desc'] ); ?>
						</p>
						<ul class="space-y-3">
							<li class="flex items-start gap-2 text-brand-ink/80">
								<i class="fa-solid fa-circle-check <?php echo esc_attr( $card['textClass'] ); ?> mt-1"></i>
								<span><?php echo esc_html( $card['bullet1'] ); ?></span>
							</li>
							<li class="flex items-start gap-2 text-brand-ink/80">
								<i class="fa-solid fa-circle-check <?php echo esc_attr( $card['textClass'] ); ?> mt-1"></i>
								<span><?php echo esc_html( $card['bullet2'] ); ?></span>
							</li>
						</ul>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- CTA Section -->
		<section id="cta" class="py-20 px-4 lg:px-6 bg-brand-warm/10">
			<div class="max-w-4xl mx-auto text-center">
				<h2 class="font-serif text-3xl md:text-5xl text-brand-ink mb-6">
					<?php echo esc_html( $cta_title ); ?>
				</h2>
				<p class="text-lg text-brand-ink/70 mb-10">
					<?php echo esc_html( $cta_description ); ?>
				</p>
				<div class="flex flex-wrap gap-4 justify-center">
					<a href="/schedule-tour" class="inline-flex items-center gap-2 px-8 py-4 bg-chroma-red text-white rounded-full font-bold hover:bg-chroma-red/90 transition-all shadow-md hover:shadow-lg">
						<i class="fa-solid fa-calendar"></i>
						Schedule a Tour
					</a>
					<a href="/locations" class="inline-flex items-center gap-2 px-8 py-4 bg-white text-brand-ink rounded-full font-bold hover:bg-brand-ink/5 transition-all shadow-md">
						<i class="fa-solid fa-map-marker-alt"></i>
						Find a Location
					</a>
				</div>
			</div>
		</section>

	</article>
</main>

<?php
get_footer();

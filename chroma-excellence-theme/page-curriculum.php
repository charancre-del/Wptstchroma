<?php
/**
 * Template Name: Curriculum Page
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$page_id = get_the_ID();

$default_pdf_url = get_template_directory_uri() . '/assets/docs/prismpath-parent-overview.pdf';
$parent_pdf_url  = chroma_get_translated_meta( $page_id, 'curriculum_parent_overview_pdf_url' ) ?: $default_pdf_url;

if ( $parent_pdf_url && function_exists( 'chroma_enqueue_pdf_assets' ) ) {
	chroma_enqueue_pdf_assets();
}

$hero_badge           = chroma_get_translated_meta( $page_id, 'curriculum_hero_badge' ) ?: __( 'The Prismpath method', 'chroma-excellence' );
$hero_title           = chroma_get_translated_meta( $page_id, 'curriculum_hero_title' ) ?: __( 'Whole-child learning that grows with <span class="pp-script">your child.</span>', 'chroma-excellence' );
$hero_description     = chroma_get_translated_meta( $page_id, 'curriculum_hero_description' ) ?: __( 'Prismpath is Chroma Early Learning Academy\'s curriculum framework for helping children grow across five connected areas: physical, emotional, social, academic, and creative development.', 'chroma-excellence' );
$hero_description_two = chroma_get_translated_meta( $page_id, 'curriculum_hero_description_two' ) ?: __( 'It is simple on purpose: teachers notice what children are practicing, plan meaningful next steps, and shape classroom experiences around the children in front of them.', 'chroma-excellence' );

$framework_title       = chroma_get_translated_meta( $page_id, 'curriculum_framework_title' ) ?: __( 'A clearer way to understand the whole child.', 'chroma-excellence' );
$framework_description = chroma_get_translated_meta( $page_id, 'curriculum_framework_description' ) ?: __( 'Children do not grow in straight lines. A block tower can build hand strength, problem-solving, language, peer cooperation, and imagination all at once. Prismpath helps teachers see those connected moments and plan from them.', 'chroma-excellence' );

$pillars = array(
	array(
		'name'   => 'physical',
		'number' => __( 'one', 'chroma-excellence' ),
		'title'  => chroma_get_translated_meta( $page_id, 'curriculum_pillar_physical_title' ) ?: __( 'Physical', 'chroma-excellence' ),
		'desc'   => chroma_get_translated_meta( $page_id, 'curriculum_pillar_physical_desc' ) ?: __( 'Motor skills, sensory processing, coordination, self-care, outdoor movement, and healthy routines.', 'chroma-excellence' ),
	),
	array(
		'name'   => 'emotional',
		'number' => __( 'two', 'chroma-excellence' ),
		'title'  => chroma_get_translated_meta( $page_id, 'curriculum_pillar_emotional_title' ) ?: __( 'Emotional', 'chroma-excellence' ),
		'desc'   => chroma_get_translated_meta( $page_id, 'curriculum_pillar_emotional_desc' ) ?: __( 'Attachment, confidence, self-regulation, resilience, transitions, and the ability to name what is felt.', 'chroma-excellence' ),
	),
	array(
		'name'   => 'social',
		'number' => __( 'three', 'chroma-excellence' ),
		'title'  => chroma_get_translated_meta( $page_id, 'curriculum_pillar_social_title' ) ?: __( 'Social', 'chroma-excellence' ),
		'desc'   => chroma_get_translated_meta( $page_id, 'curriculum_pillar_social_desc' ) ?: __( 'Friendship, collaboration, communication, empathy, conflict resolution, and classroom belonging.', 'chroma-excellence' ),
	),
	array(
		'name'   => 'academic',
		'number' => __( 'four', 'chroma-excellence' ),
		'title'  => chroma_get_translated_meta( $page_id, 'curriculum_pillar_academic_title' ) ?: __( 'Academic', 'chroma-excellence' ),
		'desc'   => chroma_get_translated_meta( $page_id, 'curriculum_pillar_academic_desc' ) ?: __( 'Language, literacy, numeracy, scientific inquiry, reasoning, and kindergarten readiness.', 'chroma-excellence' ),
	),
	array(
		'name'   => 'creative',
		'number' => __( 'five', 'chroma-excellence' ),
		'title'  => chroma_get_translated_meta( $page_id, 'curriculum_pillar_creative_title' ) ?: __( 'Creative', 'chroma-excellence' ),
		'desc'   => chroma_get_translated_meta( $page_id, 'curriculum_pillar_creative_desc' ) ?: __( 'Art, music, dramatic play, construction, flexible thinking, storytelling, and expression.', 'chroma-excellence' ),
	),
);

$support_badge       = chroma_get_translated_meta( $page_id, 'curriculum_support_badge' ) ?: __( 'How it works', 'chroma-excellence' );
$support_title       = chroma_get_translated_meta( $page_id, 'curriculum_support_title' ) ?: __( 'Teachers are supported, so children are seen clearly.', 'chroma-excellence' );
$support_description = chroma_get_translated_meta( $page_id, 'curriculum_support_description' ) ?: __( 'Prismpath is not a script. It is a teacher-guided rhythm: observe children, understand what growth is emerging, plan the next meaningful experience, then share progress with families.', 'chroma-excellence' );

$support_cards = array(
	array(
		'title' => chroma_get_translated_meta( $page_id, 'curriculum_support_notice_title' ) ?: __( 'Notice', 'chroma-excellence' ),
		'desc'  => chroma_get_translated_meta( $page_id, 'curriculum_support_notice_desc' ) ?: __( 'Teachers watch for patterns in play, language, movement, relationships, and problem-solving.', 'chroma-excellence' ),
	),
	array(
		'title' => chroma_get_translated_meta( $page_id, 'curriculum_support_plan_title' ) ?: __( 'Plan', 'chroma-excellence' ),
		'desc'  => chroma_get_translated_meta( $page_id, 'curriculum_support_plan_desc' ) ?: __( 'Lesson plans are shaped around the children in the room and the next skills they are ready to practice.', 'chroma-excellence' ),
	),
	array(
		'title' => chroma_get_translated_meta( $page_id, 'curriculum_support_coach_title' ) ?: __( 'Support', 'chroma-excellence' ),
		'desc'  => chroma_get_translated_meta( $page_id, 'curriculum_support_coach_desc' ) ?: __( 'Classroom leaders and curriculum teams help teachers choose helpful coaching and materials.', 'chroma-excellence' ),
	),
	array(
		'title' => chroma_get_translated_meta( $page_id, 'curriculum_support_share_title' ) ?: __( 'Share', 'chroma-excellence' ),
		'desc'  => chroma_get_translated_meta( $page_id, 'curriculum_support_share_desc' ) ?: __( 'Families see photos, notes, progress reports, and simple ways to continue learning at home.', 'chroma-excellence' ),
	),
);

$environment_cards = array(
	array(
		'title' => __( 'Construction Zone', 'chroma-excellence' ),
		'desc'  => __( 'Blocks and engineering tools invite balance, gravity, spatial reasoning, cooperation, and problem-solving.', 'chroma-excellence' ),
		'icon'  => __( 'Build', 'chroma-excellence' ),
	),
	array(
		'title' => __( 'Atelier', 'chroma-excellence' ),
		'desc'  => __( 'Open-ended art, clay, music, and loose parts give children room to represent ideas in their own way.', 'chroma-excellence' ),
		'icon'  => __( 'Make', 'chroma-excellence' ),
	),
	array(
		'title' => __( 'Literacy Nook', 'chroma-excellence' ),
		'desc'  => __( 'Cozy, language-rich spaces help children listen, retell, wonder, and build a love of books.', 'chroma-excellence' ),
		'icon'  => __( 'Read', 'chroma-excellence' ),
	),
);

$milestones = array(
	array(
		'title' => __( 'Daily documentation', 'chroma-excellence' ),
		'desc'  => __( 'Photos, notes, and activity reports capture small wins as they happen.', 'chroma-excellence' ),
	),
	array(
		'title' => __( 'Developmental screening', 'chroma-excellence' ),
		'desc'  => __( 'Screening tools help surface strengths and areas where early support may help.', 'chroma-excellence' ),
	),
	array(
		'title' => __( 'Standards-aligned assessment', 'chroma-excellence' ),
		'desc'  => __( 'Teachers review progress across early learning domains in a way families can understand.', 'chroma-excellence' ),
	),
	array(
		'title' => __( 'Family conferences', 'chroma-excellence' ),
		'desc'  => __( 'We sit down together, look at growth, and choose the next goals.', 'chroma-excellence' ),
	),
);

$faq_items = array(
	array(
		'q' => __( 'Is Prismpath a packaged curriculum?', 'chroma-excellence' ),
		'a' => __( 'No. It is Chroma\'s framework for planning, observing, and supporting whole-child development across classrooms.', 'chroma-excellence' ),
	),
	array(
		'q' => __( 'How does it help my child individually?', 'chroma-excellence' ),
		'a' => __( 'Teachers use observations and progress information to understand what each child is ready to practice next.', 'chroma-excellence' ),
	),
	array(
		'q' => __( 'How do families see progress?', 'chroma-excellence' ),
		'a' => __( 'Families can see daily updates, photos, documentation, progress reports, conferences, and home connection ideas.', 'chroma-excellence' ),
	),
	array(
		'q' => __( 'How does it support kindergarten readiness?', 'chroma-excellence' ),
		'a' => __( 'Children build early literacy, math thinking, social confidence, independence, communication, and flexible problem-solving through developmentally appropriate classroom experiences.', 'chroma-excellence' ),
	),
	array(
		'q' => __( 'Does every classroom look exactly the same?', 'chroma-excellence' ),
		'a' => __( 'No. Each classroom keeps its own rhythm while the five pillars and planning process stay connected.', 'chroma-excellence' ),
	),
	array(
		'q' => __( 'How are teachers supported?', 'chroma-excellence' ),
		'a' => __( 'Directors, curriculum leaders, and training teams use classroom patterns to choose coaching, materials, and planning support that fit the children in the room.', 'chroma-excellence' ),
	),
);

$tour_url      = chroma_get_localized_url( home_url( '/schedule-a-tour/' ) );
$locations_url = chroma_get_localized_url( get_post_type_archive_link( 'location' ) );
?>

<main id="primary" class="site-main pp-page" role="main">
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<section class="pp-hero">
			<span class="pp-orb pp-orb-one" aria-hidden="true"></span>
			<span class="pp-orb pp-orb-two" aria-hidden="true"></span>
			<div class="pp-container pp-hero-grid">
				<div class="pp-reveal">
					<span class="pp-eyebrow"><?php echo esc_html( $hero_badge ); ?></span>
					<h1 class="pp-title pp-title-xl"><?php echo wp_kses_post( $hero_title ); ?></h1>
					<p class="pp-copy pp-lead"><?php echo esc_html( $hero_description ); ?></p>
					<p class="pp-copy-sm pp-sublead"><?php echo esc_html( $hero_description_two ); ?></p>
					<div class="pp-actions">
						<a class="pp-btn primary" href="#program-spectrum"><?php esc_html_e( 'Watch It Grow', 'chroma-excellence' ); ?></a>
						<a class="pp-btn" href="<?php echo esc_url( $tour_url ); ?>"><?php esc_html_e( 'Schedule a Tour', 'chroma-excellence' ); ?></a>
					</div>
				</div>

				<div class="pp-prism-card pp-reveal pp-delay-1" data-prism-card>
					<svg class="pp-prism-svg" viewBox="0 0 520 400" role="img" aria-labelledby="pp-prism-title pp-prism-desc">
						<title id="pp-prism-title"><?php esc_html_e( 'The Prismpath prism', 'chroma-excellence' ); ?></title>
						<desc id="pp-prism-desc"><?php esc_html_e( 'A beam of light enters a prism and refracts into five developmental pillars.', 'chroma-excellence' ); ?></desc>
						<defs>
							<linearGradient id="pp-prism-gradient" x1="0" y1="0" x2="1" y2="1">
								<stop offset="0" stop-color="#7D5BA6" />
								<stop offset="1" stop-color="#C26524" />
							</linearGradient>
						</defs>
						<line x1="0" y1="150" x2="150" y2="196" class="pp-prism-beam pp-prism-beam-in" />
						<line x1="0" y1="150" x2="150" y2="196" class="pp-prism-dash" />
						<text x="16" y="138" class="pp-prism-label pp-ink-label"><?php esc_html_e( 'One activity', 'chroma-excellence' ); ?></text>
						<polygon points="150,196 265,110 265,282" fill="url(#pp-prism-gradient)" opacity=".08" stroke="#263238" stroke-width="2.6" />
						<g class="pp-prism-fan">
							<line x1="265" y1="196" x2="425" y2="64" class="pp-prism-ray ray-physical" />
							<line x1="265" y1="196" x2="425" y2="130" class="pp-prism-ray ray-emotional" />
							<line x1="265" y1="196" x2="425" y2="196" class="pp-prism-ray ray-social" />
							<line x1="265" y1="196" x2="425" y2="262" class="pp-prism-ray ray-academic" />
							<line x1="265" y1="196" x2="425" y2="328" class="pp-prism-ray ray-creative" />
							<line x1="265" y1="196" x2="425" y2="64" class="pp-prism-dash" />
							<line x1="265" y1="196" x2="425" y2="196" class="pp-prism-dash" />
							<line x1="265" y1="196" x2="425" y2="328" class="pp-prism-dash" />
							<text x="434" y="60" class="pp-prism-label ray-physical-text"><?php esc_html_e( 'Physical', 'chroma-excellence' ); ?></text>
							<text x="434" y="126" class="pp-prism-label ray-emotional-text"><?php esc_html_e( 'Emotional', 'chroma-excellence' ); ?></text>
							<text x="434" y="192" class="pp-prism-label ray-social-text"><?php esc_html_e( 'Social', 'chroma-excellence' ); ?></text>
							<text x="434" y="258" class="pp-prism-label ray-academic-text"><?php esc_html_e( 'Academic', 'chroma-excellence' ); ?></text>
							<text x="434" y="324" class="pp-prism-label ray-creative-text"><?php esc_html_e( 'Creative', 'chroma-excellence' ); ?></text>
						</g>
					</svg>
					<p class="pp-prism-caption">
						<?php esc_html_e( 'A prism turns one beam of light into a full spectrum.', 'chroma-excellence' ); ?>
						<strong><?php esc_html_e( 'Prismpath does the same with play.', 'chroma-excellence' ); ?></strong>
					</p>
				</div>
			</div>
		</section>

		<section id="pillars" class="pp-section pp-white pp-border-y">
			<div class="pp-container">
				<div class="pp-section-head pp-reveal">
					<span class="pp-kicker"><?php esc_html_e( 'Five pillars. One prism.', 'chroma-excellence' ); ?></span>
					<h2 class="pp-title pp-title-lg"><?php echo esc_html( $framework_title ); ?></h2>
					<p class="pp-copy"><?php echo esc_html( $framework_description ); ?></p>
				</div>
				<div class="pp-pillars">
					<?php foreach ( $pillars as $index => $pillar ) : ?>
						<article class="pp-pillar pp-reveal pp-delay-<?php echo esc_attr( min( $index, 4 ) ); ?> <?php echo esc_attr( $pillar['name'] ); ?>">
							<div class="pp-pillar-orb"></div>
							<em><?php echo esc_html( $pillar['number'] ); ?></em>
							<h3><?php echo esc_html( $pillar['title'] ); ?></h3>
							<p><?php echo esc_html( $pillar['desc'] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section id="program-spectrum" class="pp-section pp-cream">
			<div class="pp-container">
				<?php
				get_template_part(
					'template-parts/home/program-prism-slider',
					null,
					array(
						'eyebrow'     => __( 'Program spectrum', 'chroma-excellence' ),
						'title'       => __( 'Choose a program. Watch the spectrum shift.', 'chroma-excellence' ),
						'description' => __( 'The five pillars stay connected, while the balance changes with each classroom stage and program rhythm.', 'chroma-excellence' ),
						'class'       => 'chroma-program-prism-slider--curriculum pp-reveal',
					)
				);
				?>
			</div>
		</section>

		<section class="pp-section pp-white pp-border-y">
			<div class="pp-container">
				<div class="pp-section-head pp-reveal">
					<span class="pp-kicker"><?php esc_html_e( 'Classroom example', 'chroma-excellence' ); ?></span>
					<h2 class="pp-title pp-title-lg"><?php esc_html_e( 'One activity. Many kinds of learning.', 'chroma-excellence' ); ?></h2>
					<p class="pp-copy"><?php esc_html_e( 'Children build a bridge with blocks and loose parts. The activity is simple. The learning underneath it is rich.', 'chroma-excellence' ); ?></p>
				</div>
				<div class="pp-activity">
					<div class="pp-activity-card pp-reveal">
						<svg class="pp-activity-svg" viewBox="0 0 440 300" role="img" aria-label="<?php esc_attr_e( 'A block bridge refracting into five kinds of learning', 'chroma-excellence' ); ?>">
							<rect x="150" y="196" width="34" height="60" rx="6" fill="#C26524" opacity=".85" />
							<rect x="256" y="196" width="34" height="60" rx="6" fill="#C26524" opacity=".85" />
							<rect x="136" y="176" width="168" height="22" rx="7" fill="#A84B38" />
							<rect x="196" y="150" width="48" height="26" rx="6" fill="#C2A024" opacity=".9" />
							<line x1="0" y1="256" x2="440" y2="256" stroke="#263238" stroke-width="2" opacity=".18" />
							<line class="pp-skill-ray ray-physical" x1="220" y1="140" x2="120" y2="52" />
							<line class="pp-skill-ray ray-emotional" x1="220" y1="140" x2="176" y2="34" />
							<line class="pp-skill-ray ray-social" x1="220" y1="140" x2="240" y2="26" />
							<line class="pp-skill-ray ray-academic" x1="220" y1="140" x2="300" y2="40" />
							<line class="pp-skill-ray ray-creative" x1="220" y1="140" x2="352" y2="62" />
							<circle cx="120" cy="52" r="5" fill="#7D5BA6" />
							<circle cx="176" cy="34" r="5" fill="#4A6C7C" />
							<circle cx="240" cy="26" r="5" fill="#4A7C59" />
							<circle cx="300" cy="40" r="5" fill="#C2A024" />
							<circle cx="352" cy="62" r="5" fill="#A84B38" />
						</svg>
					</div>
					<div class="pp-activity-copy pp-reveal pp-delay-1">
						<div class="pp-skill-chip-row">
							<span class="pp-skill-chip physical"><?php esc_html_e( 'Moving and balancing materials', 'chroma-excellence' ); ?></span>
							<span class="pp-skill-chip emotional"><?php esc_html_e( 'Managing frustration when it falls', 'chroma-excellence' ); ?></span>
							<span class="pp-skill-chip social"><?php esc_html_e( 'Negotiating ideas with a friend', 'chroma-excellence' ); ?></span>
							<span class="pp-skill-chip academic"><?php esc_html_e( 'Testing height, width, and weight', 'chroma-excellence' ); ?></span>
							<span class="pp-skill-chip creative"><?php esc_html_e( 'Designing something new', 'chroma-excellence' ); ?></span>
						</div>
						<p class="pp-copy-sm"><?php esc_html_e( 'The teacher\'s role is to ask good questions, introduce helpful vocabulary, document what children try, and plan what comes next.', 'chroma-excellence' ); ?></p>
						<div class="pp-teacher-note">
							<span><?php esc_html_e( 'Teacher documentation', 'chroma-excellence' ); ?></span>
							<?php esc_html_e( '"Children tested bridge height, traded blocks, used words like balance and strong, and adjusted their design after it fell."', 'chroma-excellence' ); ?>
						</div>
					</div>
				</div>
			</div>
		</section>

		<section class="pp-section pp-cream">
			<div class="pp-container">
				<div class="pp-section-head pp-reveal">
					<span class="pp-kicker"><?php echo esc_html( $support_badge ); ?></span>
					<h2 class="pp-title pp-title-lg"><?php echo esc_html( $support_title ); ?></h2>
					<p class="pp-copy"><?php echo esc_html( $support_description ); ?></p>
				</div>
				<div class="pp-flow">
					<?php foreach ( $support_cards as $index => $card ) : ?>
						<article class="pp-flow-card pp-reveal pp-delay-<?php echo esc_attr( min( $index, 3 ) ); ?>">
							<div class="pp-flow-number"><?php echo esc_html( (string) ( $index + 1 ) ); ?></div>
							<h3><?php echo esc_html( $card['title'] ); ?></h3>
							<p><?php echo esc_html( $card['desc'] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
				<p class="pp-flow-loop pp-reveal pp-delay-4"><?php esc_html_e( '...and back to noticing. Every week, every room.', 'chroma-excellence' ); ?></p>
			</div>
		</section>

		<section class="pp-section pp-white pp-border-y">
			<div class="pp-container">
				<div class="pp-section-head pp-reveal">
					<span class="pp-kicker"><?php esc_html_e( 'Environment', 'chroma-excellence' ); ?></span>
					<h2 class="pp-title pp-title-lg"><?php esc_html_e( 'The classroom is the Third Teacher.', 'chroma-excellence' ); ?></h2>
					<p class="pp-copy"><?php esc_html_e( 'The environment itself guides learning alongside our educators. Classrooms are designed as zones that invite exploration and independence without constant adult direction.', 'chroma-excellence' ); ?></p>
				</div>
				<div class="pp-zones">
					<?php foreach ( $environment_cards as $index => $card ) : ?>
						<article class="pp-zone pp-reveal pp-delay-<?php echo esc_attr( min( $index, 2 ) ); ?>">
							<span class="pp-zone-icon"><?php echo esc_html( $card['icon'] ); ?></span>
							<h3><?php echo esc_html( $card['title'] ); ?></h3>
							<p><?php echo esc_html( $card['desc'] ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="pp-section pp-cream">
			<div class="pp-container">
				<div class="pp-section-head pp-reveal">
					<span class="pp-kicker"><?php esc_html_e( 'Measuring milestones', 'chroma-excellence' ); ?></span>
					<h2 class="pp-title pp-title-lg"><?php esc_html_e( 'We do not just watch them grow. We notice it.', 'chroma-excellence' ); ?></h2>
					<p class="pp-copy"><?php esc_html_e( 'Documentation and developmental review help teachers see progress clearly, plan next steps, and keep families connected to what is happening in the classroom.', 'chroma-excellence' ); ?></p>
				</div>
				<div class="pp-assess">
					<div class="pp-timeline pp-reveal">
						<?php foreach ( $milestones as $milestone ) : ?>
							<div class="pp-timeline-item">
								<strong><?php echo esc_html( $milestone['title'] ); ?></strong>
								<span><?php echo esc_html( $milestone['desc'] ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="pp-gelds pp-reveal pp-delay-1">
						<h3><?php esc_html_e( 'Aligned to early learning domains', 'chroma-excellence' ); ?></h3>
						<p><?php esc_html_e( 'Prismpath organizes classroom learning around the whole child, with language that can connect back to Georgia early learning domains while staying parent-friendly.', 'chroma-excellence' ); ?></p>
						<div class="pp-domain"><i style="background:#7D5BA6"></i><?php esc_html_e( 'Approaches to Play and Learning', 'chroma-excellence' ); ?></div>
						<div class="pp-domain"><i style="background:#4A6C7C"></i><?php esc_html_e( 'Social and Emotional Development', 'chroma-excellence' ); ?></div>
						<div class="pp-domain"><i style="background:#4A7C59"></i><?php esc_html_e( 'Communication, Language and Literacy', 'chroma-excellence' ); ?></div>
						<div class="pp-domain"><i style="background:#C2A024"></i><?php esc_html_e( 'Cognitive Development and General Knowledge', 'chroma-excellence' ); ?></div>
						<div class="pp-domain"><i style="background:#A84B38"></i><?php esc_html_e( 'Physical Development and Motor Skills', 'chroma-excellence' ); ?></div>
						<?php if ( $parent_pdf_url ) : ?>
							<div class="pp-actions">
								<a class="pp-btn chroma-pdf-trigger" href="#" data-pdf-url="<?php echo esc_url( $parent_pdf_url ); ?>" data-pdf-title="<?php esc_attr_e( 'Prismpath Parent Overview', 'chroma-excellence' ); ?>"><?php esc_html_e( 'View Parent Overview PDF', 'chroma-excellence' ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>

		<section class="pp-section pp-white pp-border-y">
			<div class="pp-container">
				<div class="pp-section-head pp-reveal">
					<span class="pp-kicker"><?php esc_html_e( 'Questions families ask', 'chroma-excellence' ); ?></span>
					<h2 class="pp-title pp-title-lg"><?php esc_html_e( 'A few plain answers.', 'chroma-excellence' ); ?></h2>
				</div>
				<div class="pp-faq">
					<?php foreach ( $faq_items as $index => $faq ) : ?>
						<details <?php echo 0 === $index ? 'open' : ''; ?>>
							<summary><?php echo esc_html( $faq['q'] ); ?></summary>
							<p><?php echo esc_html( $faq['a'] ); ?></p>
						</details>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="pp-section pp-cream">
			<div class="pp-container">
				<div class="pp-final pp-reveal" data-pp-cta>
					<span class="pp-final-glow" aria-hidden="true"></span>
					<span class="pp-spectrum-line" aria-hidden="true"></span>
					<h2 class="pp-title pp-title-lg"><?php esc_html_e( 'See the rhythm in a classroom.', 'chroma-excellence' ); ?></h2>
					<p class="pp-copy"><?php esc_html_e( 'The best way to understand Prismpath is to see children creating, communicating, and trying new things with teachers who know what support comes next.', 'chroma-excellence' ); ?></p>
					<div class="pp-actions pp-centered-actions">
						<a class="pp-btn primary" href="<?php echo esc_url( $tour_url ); ?>"><?php esc_html_e( 'Schedule a Tour', 'chroma-excellence' ); ?></a>
						<a class="pp-btn" href="<?php echo esc_url( $locations_url ); ?>"><?php esc_html_e( 'Find a Campus', 'chroma-excellence' ); ?></a>
					</div>
				</div>
			</div>
		</section>
	</article>
</main>

<?php
get_footer();

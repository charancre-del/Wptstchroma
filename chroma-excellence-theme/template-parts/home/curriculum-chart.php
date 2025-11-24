<?php
/**
 * Curriculum Radar Chart
 * Template Part: Curriculum Chart
 * Interactive radar chart showing Prismpath™ curriculum focus by age
 *
 * @package Chroma_Excellence
 */

$profiles = chroma_home_curriculum_profiles();

if ( empty( $profiles['labels'] ) || empty( $profiles['profiles'] ) ) {
        return;
}

$labels  = $profiles['labels'];
$profile_list = array_values( $profiles['profiles'] );
$first   = $profile_list[0];
?>

<section id="curriculum" class="py-20 bg-brand-cream border-y border-chroma-blue/10">
        <div class="max-w-6xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-5">
                        <span class="text-chroma-blue font-bold tracking-[0.2em] text-[11px] uppercase">The Prismpath™ Curriculum</span>
                        <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink">A curriculum that shifts as your child grows</h2>
                        <p class="text-brand-ink/70 text-sm md:text-base">Our Prismpath™ framework balances five pillars – physical, emotional, social, academic, and creative development. The mix changes at each age so your child gets exactly what they need, when they need it.</p>
                        <div class="flex flex-wrap gap-2 text-xs" data-curriculum-buttons>
                                <?php foreach ( $profiles['profiles'] as $index => $profile ) : ?>
                                        <?php
                                        $is_active = 0 === $index;
                                        $button_classes = $is_active
                                                ? 'bg-chroma-blue text-white shadow-soft'
                                                : 'bg-white text-brand-ink/70 hover:border-chroma-blue';
                                        ?>
                                        <button class="px-4 py-2 rounded-full font-semibold border border-chroma-blue/20 <?php echo esc_attr( $button_classes ); ?>" data-curriculum-button="<?php echo esc_attr( $profile['key'] ); ?>"><?php echo esc_html( ucfirst( $profile['key'] ) ); ?></button>
                                <?php endforeach; ?>
                        </div>
                        <div class="bg-white rounded-3xl border-l-4 border-chroma-red shadow-soft p-6 md:p-7">
                                <h3 class="font-serif text-xl md:text-2xl font-bold text-brand-ink mb-2" data-curriculum-title><?php echo esc_html( $first['title'] ); ?></h3>
                                <p class="text-brand-ink/70 text-sm md:text-base" data-curriculum-description><?php echo esc_html( $first['description'] ); ?></p>
                        </div>
                </div>
                <div>
                        <div class="bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 p-6">
                                <div class="relative h-[340px] md:h-[380px]">
                                        <canvas data-curriculum-chart aria-label="Curriculum focus radar chart" role="img"></canvas>
                                </div>
                        </div>
                </div>
        </div>
        <script type="application/json" data-curriculum-config><?php echo wp_json_encode( $profiles ); ?></script>
</section>
$home_id = chroma_get_home_page_id();

// Get heading/subheading
$pill_text = get_field( 'curriculum_pill_text', $home_id ) ?: 'The Prismpath™ Curriculum';
$heading = get_field( 'curriculum_heading', $home_id ) ?: 'A curriculum that shifts as your child grows';
$subheading = get_field( 'curriculum_subheading', $home_id ) ?: 'Our Prismpath™ framework balances five pillars – physical, emotional, social, academic, and creative development. The mix changes at each age so your child gets exactly what they need, when they need it.';

// Curriculum configuration for Chart.js
$curriculum_data = array(
	'infant' => array(
		'title'  => 'Foundation Phase',
		'desc'   => 'Infant classrooms emphasize emotional security, attachment, physical health, and sensory experiences. Academics are embedded through language-rich interactions.',
		'color'  => '#D67D6B',
		'data'   => array( 90, 90, 40, 15, 40 ),
	),
	'toddler' => array(
		'title'  => 'Discovery Phase',
		'desc'   => 'Toddlers explore movement, language, early problem-solving, and social skills through guided play and routines.',
		'color'  => '#4A6C7C',
		'data'   => array( 85, 75, 65, 30, 70 ),
	),
	'preschool' => array(
		'title'  => 'Exploration Phase',
		'desc'   => 'Preschoolers work on early literacy, math concepts, dramatic play, and collaborative projects, supported by strong routines.',
		'color'  => '#E6BE75',
		'data'   => array( 75, 65, 70, 55, 80 ),
	),
	'prep' => array(
		'title'  => 'Pre-K Prep Phase',
		'desc'   => 'Children build stamina for small-group work, early writing, and multi-step directions while strengthening self-regulation.',
		'color'  => '#2F4858',
		'data'   => array( 65, 60, 75, 75, 70 ),
	),
	'prek' => array(
		'title'  => 'GA Pre-K Readiness',
		'desc'   => 'Balanced academic readiness, social-emotional learning, and joyful experiences aligned with GA standards.',
		'color'  => '#4A6C7C',
		'data'   => array( 60, 60, 80, 90, 70 ),
	),
	'afterschool' => array(
		'title'  => 'Enrichment Phase',
		'desc'   => 'School-age programming offers homework help, social clubs, athletic play, and creative enrichment for older children.',
		'color'  => '#E6BE75',
		'data'   => array( 50, 70, 85, 75, 80 ),
	),
);
?>

<section id="curriculum" class="py-20 bg-brand-cream border-y border-brand-navy/10" data-section="curriculum">
	<div class="max-w-6xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-12 items-center">

		<!-- Left Column: Info + Tabs -->
		<div class="space-y-5">
			<span class="text-chroma-teal font-bold tracking-[0.2em] text-[11px] uppercase">
				<?php echo esc_html( $pill_text ); ?>
			</span>
			<h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink">
				<?php echo esc_html( $heading ); ?>
			</h2>
			<p class="text-brand-ink/70 text-sm md:text-base">
				<?php echo esc_html( $subheading ); ?>
			</p>

			<!-- Age Tabs -->
			<div class="flex flex-wrap gap-2 text-xs" data-curriculum-tabs>
				<button data-curriculum-tab="infant" class="curriculum-tab px-4 py-2 rounded-full font-semibold bg-chroma-teal text-white shadow-soft">Infant</button>
				<button data-curriculum-tab="toddler" class="curriculum-tab px-4 py-2 rounded-full font-semibold bg-white text-brand-ink/70 border border-brand-navy/20 hover:border-chroma-teal">Toddler</button>
				<button data-curriculum-tab="preschool" class="curriculum-tab px-4 py-2 rounded-full font-semibold bg-white text-brand-ink/70 border border-brand-navy/20 hover:border-chroma-teal">Preschool</button>
				<button data-curriculum-tab="prep" class="curriculum-tab px-4 py-2 rounded-full font-semibold bg-white text-brand-ink/70 border border-brand-navy/20 hover:border-chroma-teal">Pre-K Prep</button>
				<button data-curriculum-tab="prek" class="curriculum-tab px-4 py-2 rounded-full font-semibold bg-white text-brand-ink/70 border border-brand-navy/20 hover:border-chroma-teal">GA Pre-K</button>
				<button data-curriculum-tab="afterschool" class="curriculum-tab px-4 py-2 rounded-full font-semibold bg-white text-brand-ink/70 border border-brand-navy/20 hover:border-chroma-teal">After School</button>
			</div>

			<!-- Description Panel -->
			<div class="bg-white rounded-3xl border-l-4 border-chroma-red shadow-soft p-6 md:p-7">
				<h3 id="curriculum-title" class="font-serif text-xl md:text-2xl font-bold text-brand-ink mb-2">
					Foundation Phase
				</h3>
				<p id="curriculum-desc" class="text-brand-ink/70 text-sm md:text-base">
					Infant classrooms emphasize emotional security, attachment, physical health, and sensory experiences. Academics are embedded through language-rich interactions.
				</p>
			</div>
		</div>

		<!-- Right Column: Chart -->
		<div>
			<div class="bg-white rounded-[2.5rem] shadow-soft border border-brand-navy/10 p-6">
				<div class="relative h-[340px] md:h-[380px]">
					<canvas id="curriculumChart" aria-label="Curriculum focus radar chart" role="img"></canvas>
				</div>
			</div>
		</div>

	</div>
</section>

<!-- Curriculum data for JavaScript -->
<script>
window.chromaCurriculumData = <?php echo wp_json_encode( $curriculum_data ); ?>;
window.chromaCurriculumLabels = <?php echo wp_json_encode( array( 'Physical', 'Emotional', 'Social', 'Academic', 'Creative' ) ); ?>;
</script>

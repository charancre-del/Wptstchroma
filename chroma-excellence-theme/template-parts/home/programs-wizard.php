<?php
/**
 * Template Part: Programs Wizard
 * Interactive age-based program selector
 *
 * @package Chroma_Excellence
 */

$home_id = chroma_get_home_page_id();

// Get heading/subheading
$heading = get_field( 'programs_wizard_heading', $home_id ) ?: 'Find the right program in 10 seconds';
$subheading = get_field( 'programs_wizard_subheading', $home_id ) ?: 'Choose your child\'s age and we\'ll suggest the Chroma program designed for their development stage and your family\'s needs.';

// Program data structure (can be replaced with ACF repeater)
$programs = array(
	'infant' => array(
		'title'       => 'Infant Care (6 weeks–12 months)',
		'description' => 'Low ratios, safe sleep practices, responsive caregiving, and sensory play in a peaceful, predictable environment.',
		'link'        => home_url( '/programs#infant' ),
	),
	'toddler' => array(
		'title'       => 'Toddler Program (1 year)',
		'description' => 'Curated environments for walkers and explorers with language bursts and social skills.',
		'link'        => home_url( '/programs#toddler' ),
	),
	'preschool' => array(
		'title'       => 'Preschool (2 years)',
		'description' => 'Early concepts in math, literacy, and science introduced through hands-on centers and guided play.',
		'link'        => home_url( '/programs#preschool' ),
	),
	'prep' => array(
		'title'       => 'Pre-K Prep (3 years)',
		'description' => 'Structured centers and small-group instruction that build independence before GA Pre-K.',
		'link'        => home_url( '/programs#pre-k-prep' ),
	),
	'prek' => array(
		'title'       => 'GA Pre-K (4 years)',
		'description' => 'Balanced academic readiness, social-emotional learning, and joyful experiences aligned with GA standards.',
		'link'        => home_url( '/programs#ga-pre-k' ),
	),
	'afterschool' => array(
		'title'       => 'After-School Program (5–12 years)',
		'description' => 'Transportation from local schools, homework support, clubs, and outdoor play.',
		'link'        => home_url( '/programs#after-school' ),
	),
);
?>

<section id="programs" class="py-20 bg-brand-cream border-b border-brand-navy/10" data-section="programs">
	<div class="max-w-5xl mx-auto px-4 lg:px-6">

		<!-- Section Header -->
		<div class="text-center mb-10">
			<h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">
				<?php echo esc_html( $heading ); ?>
			</h2>
			<p class="text-brand-ink/70 text-sm md:text-base max-w-2xl mx-auto">
				<?php echo esc_html( $subheading ); ?>
			</p>
		</div>

		<!-- Wizard Container -->
		<div class="bg-white rounded-3xl p-6 md:p-8 border border-brand-navy/10 shadow-soft">

			<!-- Age Selection Buttons -->
			<div id="wizard-step-1" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
				<button data-wizard-age="infant" class="p-4 bg-chroma-red/10 rounded-2xl border border-chroma-red/30 hover:border-chroma-red hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">👶</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">Infant<br>(6 weeks–12m)</span>
				</button>
				<button data-wizard-age="toddler" class="p-4 bg-white rounded-2xl border border-brand-navy/20 hover:border-chroma-teal hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">🚀</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">Toddler<br>(1 year)</span>
				</button>
				<button data-wizard-age="preschool" class="p-4 bg-white rounded-2xl border border-chroma-yellow/20 hover:border-chroma-yellow hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">🎨</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">Preschool<br>(2 years)</span>
				</button>
				<button data-wizard-age="prep" class="p-4 bg-white rounded-2xl border border-brand-navy/20 hover:border-chroma-teal hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">✏️</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">Pre-K Prep<br>(3 years)</span>
				</button>
				<button data-wizard-age="prek" class="p-4 bg-white rounded-2xl border border-brand-navy/20 hover:border-chroma-teal hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">🎓</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">GA Pre-K<br>(4 years)</span>
				</button>
				<button data-wizard-age="afterschool" class="p-4 bg-white rounded-2xl border border-chroma-green/20 hover:border-chroma-green hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">🚌</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">After School<br>(5–12 years)</span>
				</button>
			</div>

			<!-- Result Panel (Hidden by default) -->
			<div id="wizard-result" class="hidden text-center pt-6 space-y-3" data-wizard-result>
				<h3 id="wizard-title" class="text-2xl font-serif font-bold text-brand-ink mb-2"></h3>
				<p id="wizard-desc" class="text-brand-ink/70 max-w-xl mx-auto text-sm md:text-base"></p>
				<div class="flex flex-wrap justify-center gap-3 text-xs">
					<a id="wizard-learn-link" href="#" class="inline-flex items-center justify-center px-5 py-2 rounded-full border border-brand-navy/20 bg-white text-brand-ink font-semibold hover:border-chroma-teal hover:text-chroma-teal transition">
						Learn more about this program
					</a>
					<a href="#tour" class="inline-flex items-center justify-center px-5 py-2 rounded-full bg-chroma-red text-white font-semibold hover:bg-chroma-red/90 transition">
						Speak to an enrollment specialist
					</a>
					<button type="button" data-wizard-reset class="text-brand-ink/50 hover:text-brand-ink underline decoration-dotted">
						Start Over
					</button>
				</div>
			</div>

		</div>
	</div>
</section>

<!-- Programs data for JavaScript -->
<script>
window.chromaProgramsData = <?php echo wp_json_encode( $programs ); ?>;
</script>

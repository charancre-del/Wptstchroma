<?php
/**
 * Program required details fallback.
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
	exit;
}

$program_id = isset($args['program_id']) ? absint($args['program_id']) : get_the_ID();
$program_title = isset($args['program_title']) ? (string) $args['program_title'] : get_the_title($program_id);
$program_slug = isset($args['program_slug']) ? (string) $args['program_slug'] : (string) get_post_field('post_name', $program_id);
$accent = isset($args['accent']) ? (string) $args['accent'] : '#A84B38';
$tour_url = function_exists('chroma_get_localized_url')
	? chroma_get_localized_url(home_url('/schedule-a-tour/'))
	: home_url('/schedule-a-tour/');

$transition_copy = array(
	'infant-care' => __('Infant care gently supports the move into toddler routines as children become more mobile, expressive, and curious.', 'chroma-excellence'),
	'toddlers' => __('Toddler classrooms help children grow toward preschool with more language, confidence, and comfort in classroom rhythms.', 'chroma-excellence'),
	'preschool' => __('Preschool prepares children for Pre-K by strengthening language, friendship, independence, and early problem-solving.', 'chroma-excellence'),
	'pre-k-ga-pre-k' => __('Pre-K supports the next step into kindergarten with joyful literacy, social confidence, and classroom independence.', 'chroma-excellence'),
	'kindergarten-1' => __('Kindergarten supports the move toward first grade with stronger literacy, math reasoning, and independent learning habits.', 'chroma-excellence'),
	'schoolagers' => __('School-age care helps children unwind, connect, and keep building responsibility after the school day.', 'chroma-excellence'),
	'camp-summer-winter-fall' => __('Camp experiences help children return to school with fresh confidence, friendships, and curiosity.', 'chroma-excellence'),
	'rising-pre-k' => __('Rising Pre-K helps children step into Pre-K routines with confidence, communication, and playful readiness.', 'chroma-excellence'),
	'rising-kindergarten' => __('Rising Kindergarten helps children feel more prepared for elementary school without losing the joy of summer discovery.', 'chroma-excellence'),
	'parents-day-out' => __('Parent\'s Day Out offers a flexible bridge into group care, classroom routines, and early friendships.', 'chroma-excellence'),
);

$program_emphasis = array(
	'infant-care' => array(
		'title' => __('Responsive care from the very beginning', 'chroma-excellence'),
		'copy' => __('Responsive caregiving follows each baby\'s cues. Songs, stories, conversation, and the sounds of language offer gentle language exposure, while safe movement and sensory exploration happen within individual feeding and rest routines.', 'chroma-excellence'),
	),
	'toddler-care' => array(
		'title' => __('Growing independence with steady support', 'chroma-excellence'),
		'copy' => __('Toddlers build independence through hands-on exploration, predictable routines, movement, early peer interaction, language growth, and warm guidance that supports emotional regulation.', 'chroma-excellence'),
	),
	'toddlers' => array(
		'title' => __('Growing independence with steady support', 'chroma-excellence'),
		'copy' => __('Toddlers build independence through hands-on exploration, predictable routines, movement, early peer interaction, language growth, and warm guidance that supports emotional regulation.', 'chroma-excellence'),
	),
	'preschool' => array(
		'title' => __('Curiosity becomes school readiness', 'chroma-excellence'),
		'copy' => __('Inquiry and communication grow through early literacy, mathematical thinking, cooperative play, problem solving, and creative projects. Teachers also support self-regulation and kindergarten readiness without rushing development.', 'chroma-excellence'),
	),
	'pre-k-prep' => array(
		'title' => __('Ready for a more connected classroom rhythm', 'chroma-excellence'),
		'copy' => __('Children strengthen inquiry and communication through early literacy, mathematical thinking, cooperative play, and problem solving. Teachers support self-regulation and kindergarten readiness through purposeful, age-appropriate experiences.', 'chroma-excellence'),
	),
	'ga-pre-k' => array(
		'title' => __('Georgia Pre-K enrollment and wraparound care', 'chroma-excellence'),
		'copy' => __('Georgia Pre-K is a state-funded program for eligible four-year-old children. Enrollment follows the Georgia lottery and application process, with campus availability determining placement. Before- and after-school wraparound care options vary by campus; families should confirm the current schedule and availability with enrollment.', 'chroma-excellence'),
	),
	'pre-k-ga-pre-k' => array(
		'title' => __('Georgia Pre-K enrollment and wraparound care', 'chroma-excellence'),
		'copy' => __('Georgia Pre-K is a state-funded program for eligible four-year-old children. Enrollment follows the Georgia lottery and application process, with campus availability determining placement. Before- and after-school wraparound care options vary by campus; families should confirm the current schedule and availability with enrollment.', 'chroma-excellence'),
	),
	'kindergarten' => array(
		'title' => __('Private Kindergarten enrollment details', 'chroma-excellence'),
		'copy' => __('Private Kindergarten tuition, class size, and campus availability vary by location and enrollment period. Academic expectations include literacy, writing, math, science, and independent classroom routines. Teachers provide regular progress reporting and family updates; enrollment can confirm current details without relying on outdated numbers.', 'chroma-excellence'),
	),
	'kindergarten-1' => array(
		'title' => __('Private Kindergarten enrollment details', 'chroma-excellence'),
		'copy' => __('Private Kindergarten tuition, class size, and campus availability vary by location and enrollment period. Academic expectations include literacy, writing, math, science, and independent classroom routines. Teachers provide regular progress reporting and family updates; enrollment can confirm current details without relying on outdated numbers.', 'chroma-excellence'),
	),
);

$cards = array(
	array(
		'title' => __('Teacher and family partnership', 'chroma-excellence'),
		'copy' => __('Families stay close to the learning. Teachers share observations, celebrate growth, and help parents understand what support comes next.', 'chroma-excellence'),
	),
	array(
		'title' => __('Classroom environment', 'chroma-excellence'),
		'copy' => __('Each classroom is arranged for age-appropriate exploration, small-group connection, calm transitions, and purposeful play.', 'chroma-excellence'),
	),
	array(
		'title' => __('Meals, rest, and daily care', 'chroma-excellence'),
		'copy' => __('Daily rhythms make room for nourishment, rest, movement, and the steady routines children need to feel secure.', 'chroma-excellence'),
	),
	array(
		'title' => __('Safety and supervision', 'chroma-excellence'),
		'copy' => __('Children are guided through predictable routines, attentive supervision, and warm boundaries that help them feel safe while they grow.', 'chroma-excellence'),
	),
	array(
		'title' => __('Ready for the next stage', 'chroma-excellence'),
		'copy' => $transition_copy[$program_slug] ?? sprintf(__('This program helps children build the confidence, skills, and classroom comfort they need for their next step after %s.', 'chroma-excellence'), $program_title),
	),
	array(
		'title' => __('Questions parents usually ask', 'chroma-excellence'),
		'copy' => __('A campus director can walk through classroom fit, daily rhythms, availability, tour timing, and any support your child may need.', 'chroma-excellence'),
	),
);

if (isset($program_emphasis[$program_slug])) {
	array_unshift($cards, $program_emphasis[$program_slug]);
}

$faq_items = function_exists('chroma_get_program_faq_items')
	? chroma_get_program_faq_items($program_id)
	: array();
?>

<section class="chroma-program-required-details py-16 md:py-20 bg-white border-y border-brand-ink/5" style="--program-accent: <?php echo esc_attr($accent); ?>;">
	<div class="max-w-6xl mx-auto px-4 lg:px-6">
		<div class="grid lg:grid-cols-[0.75fr_1.25fr] gap-10 lg:gap-14 items-start">
			<div>
				<span class="inline-flex items-center gap-2 bg-brand-cream border border-brand-ink/10 px-4 py-2 rounded-full text-[11px] uppercase tracking-[0.18em] font-bold text-brand-ink/70 mb-6">
					<span class="w-2 h-2 rounded-full" style="background: var(--program-accent);" aria-hidden="true"></span>
					<?php esc_html_e('What families can expect', 'chroma-excellence'); ?>
				</span>
				<h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] leading-[1] text-brand-ink mb-5">
					<?php printf(esc_html__('%s is supported by more than a schedule.', 'chroma-excellence'), esc_html($program_title)); ?>
				</h2>
				<p class="text-brand-ink/75 text-lg leading-relaxed">
					<?php esc_html_e('The daily experience is built around relationships, thoughtful routines, and developmentally appropriate support so children can feel known, safe, and ready for what comes next.', 'chroma-excellence'); ?>
				</p>
				<a href="<?php echo esc_url($tour_url); ?>" class="inline-flex items-center justify-center mt-8 px-7 py-3 rounded-full text-white text-xs font-bold uppercase tracking-[0.18em] shadow-soft" style="background: var(--program-accent);">
					<?php esc_html_e('Ask About This Program', 'chroma-excellence'); ?>
				</a>
			</div>
			<div class="grid sm:grid-cols-2 gap-4">
				<?php foreach ($cards as $card): ?>
					<article class="rounded-[1.5rem] border border-brand-ink/10 bg-brand-cream/45 p-6 shadow-sm">
						<h3 class="font-serif text-2xl font-semibold tracking-[-0.02em] leading-tight text-brand-ink mb-3">
							<?php echo esc_html($card['title']); ?>
						</h3>
						<p class="text-brand-ink/75 leading-relaxed">
							<?php echo esc_html($card['copy']); ?>
						</p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>

<?php if (!empty($faq_items)): ?>
	<section class="chroma-program-faq py-16 md:py-20 bg-brand-cream border-b border-brand-ink/5" style="--program-accent: <?php echo esc_attr($accent); ?>;">
		<div class="max-w-6xl mx-auto px-4 lg:px-6">
			<div class="max-w-3xl mb-8">
				<span class="text-xs font-bold uppercase tracking-[0.18em]" style="color: var(--program-accent);">
					<?php esc_html_e('Questions families often ask', 'chroma-excellence'); ?>
				</span>
				<h2 class="font-serif text-3xl md:text-4xl font-semibold tracking-[-0.03em] text-brand-ink mt-3">
					<?php printf(esc_html__('Planning for %s', 'chroma-excellence'), esc_html($program_title)); ?>
				</h2>
			</div>
			<div class="grid md:grid-cols-2 gap-4">
				<?php foreach ($faq_items as $index => $faq_item): ?>
					<details class="group rounded-[1.25rem] border border-brand-ink/10 bg-white p-5 shadow-sm"<?php echo 0 === $index ? ' open' : ''; ?>>
						<summary class="cursor-pointer list-none flex items-start justify-between gap-4 font-bold text-brand-ink">
							<span><?php echo esc_html($faq_item['question']); ?></span>
							<span aria-hidden="true" class="text-xl leading-none transition-transform group-open:rotate-45">+</span>
						</summary>
						<div class="pt-4 text-brand-ink/75 leading-relaxed">
							<?php echo wp_kses_post(wpautop($faq_item['answer'])); ?>
						</div>
					</details>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

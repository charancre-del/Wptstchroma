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
	'toddler-care' => __('Toddler classrooms help children grow toward preschool with more language, confidence, and comfort in classroom rhythms.', 'chroma-excellence'),
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
		'title' => __('Georgia Pre-K with a strong literacy foundation', 'chroma-excellence'),
		'copy' => __('Georgia Pre-K is a state-funded program for eligible four-year-old children. Classroom experiences align with Georgia standards and include Heggerty Phonics to strengthen sound awareness, oral language, and confident kindergarten preparation.', 'chroma-excellence'),
	),
	'pre-k-ga-pre-k' => array(
		'title' => __('Georgia Pre-K with a strong literacy foundation', 'chroma-excellence'),
		'copy' => __('Georgia Pre-K is a state-funded program for eligible four-year-old children. Classroom experiences align with Georgia standards and include Heggerty Phonics to strengthen sound awareness, oral language, and confident kindergarten preparation.', 'chroma-excellence'),
	),
	'kindergarten' => array(
		'title' => __('A confident bridge into elementary learning', 'chroma-excellence'),
		'copy' => __('Private Kindergarten brings literacy, writing, math, science, and independent classroom routines together in a warm setting. Teachers share progress with families and help children build the habits they need for first grade.', 'chroma-excellence'),
	),
	'kindergarten-1' => array(
		'title' => __('A confident bridge into elementary learning', 'chroma-excellence'),
		'copy' => __('Private Kindergarten brings literacy, writing, math, science, and independent classroom routines together in a warm setting. Teachers share progress with families and help children build the habits they need for first grade.', 'chroma-excellence'),
	),
);

$program_cards = array(
	'infant-care' => array(
		array('title' => __('Responsive daily care', 'chroma-excellence'), 'copy' => __('Feeding, rest, diapering, and play follow each baby’s cues within calm, predictable routines.', 'chroma-excellence')),
		array('title' => __('Sensory and movement discovery', 'chroma-excellence'), 'copy' => __('Safe floor play, reaching, grasping, texture, sound, and movement support early physical confidence.', 'chroma-excellence')),
		array('title' => __('Language from the beginning', 'chroma-excellence'), 'copy' => __('Songs, stories, conversation, and responsive back-and-forth introduce the sounds and rhythms of language.', 'chroma-excellence')),
		array('title' => __('Close family connection', 'chroma-excellence'), 'copy' => __('Teachers share daily care notes and observations so home and classroom routines can support one another.', 'chroma-excellence')),
	),
	'toddler-care' => array(
		array('title' => __('Words for a growing world', 'chroma-excellence'), 'copy' => __('Conversation, songs, picture books, and naming experiences help toddlers turn curiosity into language.', 'chroma-excellence')),
		array('title' => __('Independence through routine', 'chroma-excellence'), 'copy' => __('Simple choices, self-help skills, and predictable transitions help children participate with confidence.', 'chroma-excellence')),
		array('title' => __('Early friendship skills', 'chroma-excellence'), 'copy' => __('Teachers model turn-taking, empathy, boundaries, and ways to express strong feelings safely.', 'chroma-excellence')),
		array('title' => __('Movement with purpose', 'chroma-excellence'), 'copy' => __('Climbing, carrying, dancing, building, and outdoor play strengthen coordination and body awareness.', 'chroma-excellence')),
	),
	'toddlers' => array(
		array('title' => __('Words for a growing world', 'chroma-excellence'), 'copy' => __('Conversation, songs, picture books, and naming experiences help toddlers turn curiosity into language.', 'chroma-excellence')),
		array('title' => __('Independence through routine', 'chroma-excellence'), 'copy' => __('Simple choices, self-help skills, and predictable transitions help children participate with confidence.', 'chroma-excellence')),
		array('title' => __('Early friendship skills', 'chroma-excellence'), 'copy' => __('Teachers model turn-taking, empathy, boundaries, and ways to express strong feelings safely.', 'chroma-excellence')),
		array('title' => __('Movement with purpose', 'chroma-excellence'), 'copy' => __('Climbing, carrying, dancing, building, and outdoor play strengthen coordination and body awareness.', 'chroma-excellence')),
	),
	'preschool' => array(
		array('title' => __('Learning centers and inquiry', 'chroma-excellence'), 'copy' => __('Children investigate questions through blocks, dramatic play, art, science, sensory materials, and small-group work.', 'chroma-excellence')),
		array('title' => __('Early literacy in context', 'chroma-excellence'), 'copy' => __('Stories, vocabulary, rhymes, names, symbols, and meaningful print build language and pre-reading awareness.', 'chroma-excellence')),
		array('title' => __('Math and science thinking', 'chroma-excellence'), 'copy' => __('Sorting, counting, comparing, measuring, predicting, and testing ideas make abstract concepts concrete.', 'chroma-excellence')),
		array('title' => __('A cooperative classroom', 'chroma-excellence'), 'copy' => __('Group projects and guided play help children practice communication, self-regulation, and problem solving.', 'chroma-excellence')),
	),
	'pre-k-prep' => array(
		array('title' => __('Purposeful language and literacy', 'chroma-excellence'), 'copy' => __('Children build vocabulary, sound awareness, storytelling, print knowledge, and confidence expressing ideas.', 'chroma-excellence')),
		array('title' => __('Connected math learning', 'chroma-excellence'), 'copy' => __('Numbers, patterns, shapes, measurement, and reasoning appear in projects, routines, and hands-on play.', 'chroma-excellence')),
		array('title' => __('Self-regulation for school', 'chroma-excellence'), 'copy' => __('Children practice listening, planning, completing steps, managing transitions, and working with classmates.', 'chroma-excellence')),
		array('title' => __('Projects with growing depth', 'chroma-excellence'), 'copy' => __('Longer investigations encourage children to ask questions, revisit ideas, and explain what they discover.', 'chroma-excellence')),
	),
	'ga-pre-k' => array(
		array('title' => __('Georgia-aligned learning', 'chroma-excellence'), 'copy' => __('Classroom experiences support the Georgia Pre-K framework through purposeful, play-based investigation.', 'chroma-excellence')),
		array('title' => __('Heggerty Phonics', 'chroma-excellence'), 'copy' => __('Daily oral-language routines strengthen phonological and phonemic awareness for confident reading readiness.', 'chroma-excellence')),
		array('title' => __('Kindergarten independence', 'chroma-excellence'), 'copy' => __('Children practice classroom routines, collaboration, problem solving, and completing increasingly complex tasks.', 'chroma-excellence')),
		array('title' => __('Progress shared with families', 'chroma-excellence'), 'copy' => __('Teachers document learning, celebrate growth, and help families understand the next developmental steps.', 'chroma-excellence')),
	),
	'after-school' => array(
		array('title' => __('A calm transition after school', 'chroma-excellence'), 'copy' => __('Children have time to recharge, eat a snack, connect with friends, and settle into the afternoon.', 'chroma-excellence')),
		array('title' => __('Homework support', 'chroma-excellence'), 'copy' => __('Staff provide a structured space for assignments while encouraging responsibility and independent work habits.', 'chroma-excellence')),
		array('title' => __('Clubs and creative choices', 'chroma-excellence'), 'copy' => __('Art, STEM, games, movement, and collaborative projects give children meaningful ways to explore interests.', 'chroma-excellence')),
		array('title' => __('Transportation connections', 'chroma-excellence'), 'copy' => __('Participating campuses coordinate pickup from listed local schools and maintain clear arrival routines.', 'chroma-excellence')),
	),
	'schoolagers' => array(
		array('title' => __('A calm transition after school', 'chroma-excellence'), 'copy' => __('Children have time to recharge, eat a snack, connect with friends, and settle into the afternoon.', 'chroma-excellence')),
		array('title' => __('Homework support', 'chroma-excellence'), 'copy' => __('Staff provide a structured space for assignments while encouraging responsibility and independent work habits.', 'chroma-excellence')),
		array('title' => __('Clubs and creative choices', 'chroma-excellence'), 'copy' => __('Art, STEM, games, movement, and collaborative projects give children meaningful ways to explore interests.', 'chroma-excellence')),
		array('title' => __('Transportation connections', 'chroma-excellence'), 'copy' => __('Participating campuses coordinate pickup from listed local schools and maintain clear arrival routines.', 'chroma-excellence')),
	),
	'camp-summer-winter-fall' => array(
		array('title' => __('Weekly themes', 'chroma-excellence'), 'copy' => __('Each week gives children a fresh question, topic, or challenge to explore through play and projects.', 'chroma-excellence')),
		array('title' => __('Active days', 'chroma-excellence'), 'copy' => __('Outdoor play, team games, movement, and special events keep school breaks energetic and social.', 'chroma-excellence')),
		array('title' => __('Hands-on discovery', 'chroma-excellence'), 'copy' => __('STEM, art, building, cooking, and creative challenges invite children to make and test ideas.', 'chroma-excellence')),
		array('title' => __('Campus calendars', 'chroma-excellence'), 'copy' => __('Participating campuses publish current themes, field trips, and registration details for their local camp.', 'chroma-excellence')),
	),
	'kindergarten-1' => array(
		array('title' => __('Reading and writing foundations', 'chroma-excellence'), 'copy' => __('Children build phonics, comprehension, handwriting, vocabulary, and confidence communicating in print.', 'chroma-excellence')),
		array('title' => __('Math and science reasoning', 'chroma-excellence'), 'copy' => __('Hands-on problems help children explain thinking, use evidence, and connect concepts across subjects.', 'chroma-excellence')),
		array('title' => __('Independent learning habits', 'chroma-excellence'), 'copy' => __('Children practice organizing materials, following multi-step directions, and completing meaningful work.', 'chroma-excellence')),
		array('title' => __('Progress families can understand', 'chroma-excellence'), 'copy' => __('Teachers share observations and progress so families know what is growing and what comes next.', 'chroma-excellence')),
	),
	'rising-pre-k' => array(
		array('title' => __('Pre-K classroom rhythm', 'chroma-excellence'), 'copy' => __('Children practice group times, learning centers, transitions, and following classroom routines.', 'chroma-excellence')),
		array('title' => __('Language and sound play', 'chroma-excellence'), 'copy' => __('Stories, rhymes, conversation, and sound games strengthen communication and early literacy awareness.', 'chroma-excellence')),
		array('title' => __('Social confidence', 'chroma-excellence'), 'copy' => __('Guided play supports friendship, sharing ideas, solving small conflicts, and asking for help.', 'chroma-excellence')),
		array('title' => __('Growing independence', 'chroma-excellence'), 'copy' => __('Children practice self-help skills, choices, persistence, and taking responsibility for classroom materials.', 'chroma-excellence')),
	),
	'rising-kindergarten' => array(
		array('title' => __('Literacy and math refresh', 'chroma-excellence'), 'copy' => __('Playful review keeps sound awareness, vocabulary, counting, patterns, and reasoning active over summer.', 'chroma-excellence')),
		array('title' => __('Kindergarten routines', 'chroma-excellence'), 'copy' => __('Children practice listening, transitions, multi-step directions, and participating in a larger group.', 'chroma-excellence')),
		array('title' => __('Confidence for a new school', 'chroma-excellence'), 'copy' => __('Teachers make space for questions, feelings, problem solving, and the independence a new setting requires.', 'chroma-excellence')),
		array('title' => __('Summer discovery stays joyful', 'chroma-excellence'), 'copy' => __('Projects, movement, art, and friendship keep preparation active without turning summer into formal school.', 'chroma-excellence')),
	),
	'parents-day-out' => array(
		array('title' => __('A flexible classroom rhythm', 'chroma-excellence'), 'copy' => __('A part-time schedule introduces children to group care, predictable transitions, and time away from home.', 'chroma-excellence')),
		array('title' => __('Early friendship', 'chroma-excellence'), 'copy' => __('Children practice playing near and with peers while teachers guide sharing, communication, and empathy.', 'chroma-excellence')),
		array('title' => __('Language and discovery', 'chroma-excellence'), 'copy' => __('Stories, songs, sensory play, art, and movement support curiosity and emerging communication.', 'chroma-excellence')),
		array('title' => __('Warm family partnership', 'chroma-excellence'), 'copy' => __('Teachers share how the day went and help families understand how their child is settling into classroom life.', 'chroma-excellence')),
	),
);

$program_card_aliases = array(
	'pre-k-ga-pre-k' => 'ga-pre-k',
	'kindergarten' => 'kindergarten-1',
);
$program_card_key = $program_card_aliases[$program_slug] ?? $program_slug;

$cards = $program_cards[$program_card_key] ?? array(
	array('title' => __('Teacher and family partnership', 'chroma-excellence'), 'copy' => __('Teachers share observations, celebrate growth, and help families understand what support comes next.', 'chroma-excellence')),
	array('title' => __('Classroom environment', 'chroma-excellence'), 'copy' => __('The classroom is arranged for age-appropriate exploration, connection, calm transitions, and purposeful play.', 'chroma-excellence')),
	array('title' => __('Ready for the next stage', 'chroma-excellence'), 'copy' => $transition_copy[$program_slug] ?? sprintf(__('This program helps children build confidence and classroom comfort for their next step after %s.', 'chroma-excellence'), $program_title)),
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
					<?php esc_html_e('Schedule a Tour', 'chroma-excellence'); ?>
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

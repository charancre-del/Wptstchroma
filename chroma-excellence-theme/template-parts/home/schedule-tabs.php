<?php
/**
 * Daily Schedule Tabs
 * Template Part: Schedule Tabs
 * "A Day in the Life" - Daily rhythm tabs for different age groups
 *
 * @package Chroma_Excellence
 */

$tracks = chroma_home_schedule_tracks();

if ( empty( $tracks ) ) {
        return;
}
?>

<section id="schedule" class="py-20 bg-brand-cream relative">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-chroma-red via-chroma-yellow to-chroma-blue opacity-40"></div>
        <div class="max-w-6xl mx-auto px-4 lg:px-6" data-schedule data-tracks='<?php echo esc_attr( wp_json_encode( $tracks ) ); ?>'>
                <div class="text-center mb-12">
                        <span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-4 block">Day by Day</span>
                        <h2 class="text-3xl md:text-4xl font-serif text-brand-ink mb-3">A Daily Rhythm of Joy</h2>
                        <p class="text-brand-ink/70 max-w-2xl mx-auto">We don't just fill time. Every classroom follows a thoughtful flow designed to balance stimulation, nourishment, and rest.</p>
                </div>
                <div class="flex justify-center mb-12">
                        <div class="bg-white border border-chroma-blue/15 p-1 rounded-full inline-flex" data-schedule-tabs>
<?php foreach ( $tracks as $index => $track ) : ?>
<?php
$is_active = 0 === $index;
$tab_classes = $is_active
? 'bg-chroma-blue text-white shadow-soft'
: 'text-brand-ink/60 hover:text-chroma-blue';
?>
<button class="schedule-tab px-8 py-3 rounded-full text-sm font-bold transition-all duration-300 <?php echo esc_attr( $tab_classes ); ?>" data-schedule-tab="<?php echo esc_attr( $track['key'] ); ?>" aria-pressed="<?php echo esc_attr( $is_active ? 'true' : 'false' ); ?>"><?php echo esc_html( $track['label'] ?? ucfirst( $track['key'] ) ); ?></button>
<?php endforeach; ?>
</div>
</div>
<?php foreach ( $tracks as $index => $track ) : ?>
<?php
$is_active      = 0 === $index;
$panel_classes  = $is_active ? 'tab-content active' : 'tab-content hidden';
$backgroundTint = ! empty( $track['background'] ) ? $track['background'] : 'bg-brand-cream';
?>
<div class="<?php echo esc_attr( $panel_classes ); ?>" data-schedule-panel="<?php echo esc_attr( $track['key'] ); ?>">
<div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
<div class="rounded-[3rem] p-10 h-full <?php echo esc_attr( $backgroundTint ); ?>">
                                                <h3 class="text-2xl font-serif text-brand-ink mb-6"><?php echo esc_html( $track['title'] ); ?></h3>
                                                <p class="text-brand-ink/70 mb-8 leading-relaxed">Thoughtfully balanced flow tailored to this age group's needs.</p>
                                                <div class="space-y-6 relative">
                                                        <div class="absolute left-[19px] top-2 bottom-2 w-0.5 bg-chroma-blue/20"></div>
                                                        <?php foreach ( $track['steps'] as $step ) : ?>
                                                                <div class="flex gap-6 items-start">
                                                                        <div class="w-10 h-10 rounded-full bg-white text-brand-ink flex items-center justify-center shadow-sm relative z-10 text-xs font-bold"><?php echo esc_html( $step['time'] ); ?></div>
                                                                        <div>
                                                                                <h4 class="font-bold text-brand-ink"><?php echo esc_html( $step['title'] ); ?></h4>
                                                                                <p class="text-sm text-brand-ink/60"><?php echo esc_html( $step['copy'] ); ?></p>
                                                                        </div>
                                                                </div>
                                                        <?php endforeach; ?>
                                                </div>
                                        </div>
                                        <div class="rounded-[3rem] overflow-hidden shadow-2xl h-[320px] md:h-[400px] bg-chroma-blueLight">
                                                <div class="w-full h-full flex items-center justify-center text-chroma-blueDark/60 text-5xl"><i class="fa-solid fa-image"></i></div>
                                        </div>
                                </div>
                        </div>
                <?php endforeach; ?>
        </div>
$home_id = chroma_get_home_page_id();

// Get heading/subheading
$pill_text = get_field( 'schedule_pill_text', $home_id ) ?: 'Day by Day';
$heading = get_field( 'schedule_heading', $home_id ) ?: 'A Daily Rhythm of Joy';
$subheading = get_field( 'schedule_subheading', $home_id ) ?: 'We don\'t just fill time. Every classroom follows a thoughtful flow designed to balance stimulation, nourishment, and rest.';
?>

<section id="schedule" class="py-20 bg-brand-cream relative" data-section="schedule">
	<div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-chroma-red via-chroma-yellow to-chroma-teal opacity-40"></div>

	<div class="max-w-6xl mx-auto px-4 lg:px-6">

		<!-- Section Header -->
		<div class="text-center mb-12">
			<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-4 block">
				<?php echo esc_html( $pill_text ); ?>
			</span>
			<h2 class="text-3xl md:text-4xl font-serif text-brand-ink mb-3">
				<?php echo esc_html( $heading ); ?>
			</h2>
			<p class="text-brand-ink/70 max-w-2xl mx-auto">
				<?php echo esc_html( $subheading ); ?>
			</p>
		</div>

		<!-- Tab Buttons -->
		<div class="flex justify-center mb-12">
			<div class="bg-white border border-brand-navy/15 p-1 rounded-full inline-flex">
				<button data-schedule-tab="infant" class="schedule-tab px-8 py-3 rounded-full text-sm font-bold transition-all duration-300 bg-chroma-teal text-white shadow-soft">
					Infants
				</button>
				<button data-schedule-tab="toddler" class="schedule-tab px-8 py-3 rounded-full text-sm font-bold transition-all duration-300 text-brand-ink/60 hover:text-chroma-teal">
					Toddlers
				</button>
				<button data-schedule-tab="prek" class="schedule-tab px-8 py-3 rounded-full text-sm font-bold transition-all duration-300 text-brand-ink/60 hover:text-chroma-teal">
					Pre-K
				</button>
			</div>
		</div>

		<!-- Tab Panels -->
		<div data-schedule-panels>

			<!-- Infant Panel -->
			<div data-schedule-panel="infant" class="schedule-panel grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
				<div class="bg-gradient-to-br from-chroma-teal/10 to-chroma-teal/5 rounded-[3rem] p-10 h-full">
					<h3 class="text-2xl font-serif text-brand-navy mb-6">The Nurturing Nest</h3>
					<p class="text-brand-ink/70 mb-8 leading-relaxed">
						Individualized schedules follow infants' cues for sleeping and eating, with gentle sensory play.
					</p>
					<div class="space-y-6 relative">
						<div class="absolute left-[19px] top-2 bottom-2 w-0.5 bg-chroma-teal/20"></div>
						<div class="flex gap-6 items-start">
							<div class="w-10 h-10 rounded-full bg-white text-chroma-teal flex items-center justify-center shadow-sm relative z-10 text-xs font-bold">AM</div>
							<div>
								<h4 class="font-bold text-brand-navy">Warm Welcome & Cuddles</h4>
								<p class="text-sm text-brand-ink/60">Transition from parent, bottle feeding, and floor play.</p>
							</div>
						</div>
						<div class="flex gap-6 items-start">
							<div class="w-10 h-10 rounded-full bg-white text-chroma-teal flex items-center justify-center shadow-sm relative z-10 text-xs font-bold">Mid</div>
							<div>
								<h4 class="font-bold text-brand-navy">Sensory Discovery</h4>
								<p class="text-sm text-brand-ink/60">Tummy time, soft textures, and mirror play.</p>
							</div>
						</div>
						<div class="flex gap-6 items-start">
							<div class="w-10 h-10 rounded-full bg-white text-chroma-teal flex items-center justify-center shadow-sm relative z-10 text-xs font-bold">PM</div>
							<div>
								<h4 class="font-bold text-brand-navy">Stroller Walk & Songs</h4>
								<p class="text-sm text-brand-ink/60">Fresh air (weather permitting) and gentle music.</p>
							</div>
						</div>
					</div>
				</div>
				<div class="rounded-[3rem] overflow-hidden shadow-2xl h-[400px]">
					<img src="https://images.unsplash.com/photo-1555252333-9f8e92e65df9?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover" alt="Infant classroom" loading="lazy" />
				</div>
			</div>

			<!-- Toddler Panel -->
			<div data-schedule-panel="toddler" class="schedule-panel hidden grid-cols-1 md:grid-cols-2 gap-12 items-center">
				<div class="bg-gradient-to-br from-chroma-yellow/10 to-chroma-yellow/5 rounded-[3rem] p-10 h-full">
					<h3 class="text-2xl font-serif text-brand-ink mb-6">Explorers & Builders</h3>
					<p class="text-brand-ink/70 mb-8 leading-relaxed">
						Structured circle time and communal meals help toddlers understand social cues and transitions.
					</p>
					<div class="space-y-6 relative">
						<div class="absolute left-[19px] top-2 bottom-2 w-0.5 bg-chroma-yellow/20"></div>
						<div class="flex gap-6 items-start">
							<div class="w-10 h-10 rounded-full bg-white text-chroma-yellow flex items-center justify-center shadow-sm relative z-10 text-xs font-bold">9:00</div>
							<div>
								<h4 class="font-bold text-brand-ink">Morning Circle</h4>
								<p class="text-sm text-brand-ink/60">Songs, greeting friends, and introducing the daily theme.</p>
							</div>
						</div>
						<div class="flex gap-6 items-start">
							<div class="w-10 h-10 rounded-full bg-white text-chroma-yellow flex items-center justify-center shadow-sm relative z-10 text-xs font-bold">10:30</div>
							<div>
								<h4 class="font-bold text-brand-ink">Prismpath Play</h4>
								<p class="text-sm text-brand-ink/60">Block building, art stations, and guided motor skills.</p>
							</div>
						</div>
						<div class="flex gap-6 items-start">
							<div class="w-10 h-10 rounded-full bg-white text-chroma-yellow flex items-center justify-center shadow-sm relative z-10 text-xs font-bold">12:00</div>
							<div>
								<h4 class="font-bold text-brand-ink">Family-Style Lunch</h4>
								<p class="text-sm text-brand-ink/60">Learning to pass bowls, use utensils, and chat with friends.</p>
							</div>
						</div>
					</div>
				</div>
				<div class="rounded-[3rem] overflow-hidden shadow-2xl h-[400px]">
					<img src="https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover" alt="Toddler classroom" loading="lazy" />
				</div>
			</div>

			<!-- Pre-K Panel -->
			<div data-schedule-panel="prek" class="schedule-panel hidden grid-cols-1 md:grid-cols-2 gap-12 items-center">
				<div class="bg-gradient-to-br from-chroma-red/10 to-chroma-red/5 rounded-[3rem] p-10 h-full">
					<h3 class="text-2xl font-serif text-chroma-red mb-6">Kindergarten Readiness</h3>
					<p class="text-brand-ink/70 mb-8 leading-relaxed">
						The Pre-K rhythm mirrors elementary flow, building stamina and focus.
					</p>
					<div class="space-y-6 relative">
						<div class="absolute left-[19px] top-2 bottom-2 w-0.5 bg-chroma-red/20"></div>
						<div class="flex gap-6 items-start">
							<div class="w-10 h-10 rounded-full bg-white text-chroma-red flex items-center justify-center shadow-sm relative z-10 text-xs font-bold">9:00</div>
							<div>
								<h4 class="font-bold text-chroma-red">Literacy & Logic</h4>
								<p class="text-sm text-brand-ink/60">Phonics games, calendar math, and story comprehension.</p>
							</div>
						</div>
						<div class="flex gap-6 items-start">
							<div class="w-10 h-10 rounded-full bg-white text-chroma-red flex items-center justify-center shadow-sm relative z-10 text-xs font-bold">11:00</div>
							<div>
								<h4 class="font-bold text-chroma-red">Project-Based Learning</h4>
								<p class="text-sm text-brand-ink/60">Collaborative science experiments and art projects.</p>
							</div>
						</div>
						<div class="flex gap-6 items-start">
							<div class="w-10 h-10 rounded-full bg-white text-chroma-red flex items-center justify-center shadow-sm relative z-10 text-xs font-bold">2:00</div>
							<div>
								<h4 class="font-bold text-chroma-red">Social Centers</h4>
								<p class="text-sm text-brand-ink/60">Dramatic play and negotiation skills.</p>
							</div>
						</div>
					</div>
				</div>
				<div class="rounded-[3rem] overflow-hidden shadow-2xl h-[400px]">
					<img src="https://images.unsplash.com/photo-1503919545874-86c1d9a04595?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover" alt="Pre-K classroom" loading="lazy" />
				</div>
			</div>

		</div>
	</div>
</section>

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

<section id="schedule" class="py-20 bg-brand-cream relative" data-section="schedule">
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
                                                <p class="text-brand-ink/70 mb-8 leading-relaxed"><?php echo esc_html( $track['description'] ?? '' ); ?></p>
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
                                                <?php if ( ! empty( $track['image'] ) ) : ?>
                                                        <img src="<?php echo esc_url( $track['image'] ); ?>" alt="<?php echo esc_attr( $track['title'] ); ?>" class="w-full h-full object-cover" />
                                                <?php else : ?>
                                                        <div class="w-full h-full flex items-center justify-center text-chroma-blueDark/60 text-5xl"><i class="fa-solid fa-image"></i></div>
                                                <?php endif; ?>
                                        </div>
                                </div>
                        </div>
                <?php endforeach; ?>
        </div>
</section>

<?php
/**
 * Template Part: Stats Strip
 * Displays 4 key stats with dynamic data from ACF
 *
 * @package Chroma_Excellence
 */

$stats = chroma_home_stats_strip();
if ( ! $stats ) {
    return;
}
?>

<section class="bg-white py-12 border-y border-brand-navy/10" data-section="stats">
    <div class="max-w-6xl mx-auto px-4 lg:px-6 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">

        <?php if ( ! empty( $stats['stat_1_number'] ) ) : ?>
        <div class="group" data-stat="1">
            <div class="font-serif text-3xl font-bold text-chroma-red group-hover:scale-110 transition-transform duration-300">
                <?php echo esc_html( $stats['stat_1_number'] ); ?>
            </div>
            <div class="mt-1 text-[11px] uppercase tracking-[0.2em] text-brand-ink/60">
                <?php echo esc_html( $stats['stat_1_label'] ); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $stats['stat_2_number'] ) ) : ?>
        <div class="group" data-stat="2">
            <div class="font-serif text-3xl font-bold text-chroma-yellow group-hover:scale-110 transition-transform duration-300">
                <?php echo esc_html( $stats['stat_2_number'] ); ?>
            </div>
            <div class="mt-1 text-[11px] uppercase tracking-[0.2em] text-brand-ink/60">
                <?php echo esc_html( $stats['stat_2_label'] ); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $stats['stat_3_number'] ) ) : ?>
        <div class="group" data-stat="3">
            <div class="font-serif text-3xl font-bold text-chroma-teal group-hover:scale-110 transition-transform duration-300">
                <?php echo esc_html( $stats['stat_3_number'] ); ?>
            </div>
            <div class="mt-1 text-[11px] uppercase tracking-[0.2em] text-brand-ink/60">
                <?php echo esc_html( $stats['stat_3_label'] ); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ( ! empty( $stats['stat_4_number'] ) ) : ?>
        <div class="group" data-stat="4">
            <div class="font-serif text-3xl font-bold text-chroma-green group-hover:scale-110 transition-transform duration-300">
                <?php echo esc_html( $stats['stat_4_number'] ); ?>
            </div>
            <div class="mt-1 text-[11px] uppercase tracking-[0.2em] text-brand-ink/60">
                <?php echo esc_html( $stats['stat_4_label'] ); ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>

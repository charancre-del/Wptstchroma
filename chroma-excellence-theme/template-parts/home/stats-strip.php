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

<section class="bg-brand-navy py-16" data-section="stats">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <?php if ( ! empty( $stats['stat_1_number'] ) ) : ?>
            <div class="text-center" data-stat="1">
                <div class="text-5xl font-bold text-chroma-yellow mb-2">
                    <?php echo esc_html( $stats['stat_1_number'] ); ?>
                </div>
                <div class="text-brand-cream text-lg">
                    <?php echo esc_html( $stats['stat_1_label'] ); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $stats['stat_2_number'] ) ) : ?>
            <div class="text-center" data-stat="2">
                <div class="text-5xl font-bold text-chroma-teal mb-2">
                    <?php echo esc_html( $stats['stat_2_number'] ); ?>
                </div>
                <div class="text-brand-cream text-lg">
                    <?php echo esc_html( $stats['stat_2_label'] ); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $stats['stat_3_number'] ) ) : ?>
            <div class="text-center" data-stat="3">
                <div class="text-5xl font-bold text-chroma-red mb-2">
                    <?php echo esc_html( $stats['stat_3_number'] ); ?>
                </div>
                <div class="text-brand-cream text-lg">
                    <?php echo esc_html( $stats['stat_3_label'] ); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( ! empty( $stats['stat_4_number'] ) ) : ?>
            <div class="text-center" data-stat="4">
                <div class="text-5xl font-bold text-chroma-green mb-2">
                    <?php echo esc_html( $stats['stat_4_number'] ); ?>
                </div>
                <div class="text-brand-cream text-lg">
                    <?php echo esc_html( $stats['stat_4_label'] ); ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</section>

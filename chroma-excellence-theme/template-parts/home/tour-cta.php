<?php
/**
 * Template Part: Tour CTA
 * Final conversion section with tour form
 *
 * @package Chroma_Excellence
 */

$tour_cta = chroma_home_tour_cta();
if (!$tour_cta) {
    return;
}
?>

<section id="tour" class="py-20 bg-brand-cream border-t border-chroma-blue/10" data-section="tour-cta">
    <div class="max-w-6xl mx-auto px-4 lg:px-6">

        <!-- Header -->
        <div class="text-center mb-10">
            <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">
                <?php echo esc_html($tour_cta['heading'] ?: 'Schedule a private tour'); ?>
            </h2>
            <p class="text-brand-ink text-sm md:text-base max-w-2xl mx-auto">
                <?php echo esc_html($tour_cta['subheading']); ?>
            </p>
        </div>

        <!-- Benefits Row (Horizontal) -->
        <div
            class="bg-gradient-to-r from-chroma-blue via-chroma-green to-chroma-yellow text-white rounded-2xl p-6 mb-8">
            <p class="text-[11px] font-semibold tracking-[0.2em] uppercase mb-4 text-center">Why families choose Chroma
            </p>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                <div class="flex gap-2"><span class="text-white">✓</span><span>Warm, consistent teachers</span></div>
                <div class="flex gap-2"><span class="text-white">✓</span><span>Daily parent communication</span></div>
                <div class="flex gap-2"><span class="text-white">✓</span><span>Healthy meals included</span></div>
                <div class="flex gap-2"><span class="text-white">✓</span><span>Age-appropriate security</span></div>
                <div class="flex gap-2"><span class="text-white">✓</span><span>GA Lottery Pre-K available</span></div>
            </div>
        </div>

        <!-- Full-Width Form Container -->
        <div class="bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 overflow-hidden p-8 md:p-10">
            <?php
            if (shortcode_exists('chroma_tour_form')) {
                echo do_shortcode('[chroma_tour_form]');
            } else {
                ?>
                <div class="text-brand-ink text-sm">Please activate the "Chroma Tour Form" plugin to display the tour
                    booking form.</div>
                <?php
            }
            ?>
            <?php if (!empty($tour_cta['trust_text'])): ?>
                <p class="text-[11px] text-brand-ink mt-6 text-center"><?php echo esc_html($tour_cta['trust_text']); ?></p>
            <?php endif; ?>
            <p class="text-xs text-brand-ink mt-4 text-center">
                <span class="font-semibold">Typical tour length: 20–30 minutes</span> — Meet the Director, walk
                classrooms, and get tuition details.
            </p>
        </div>

    </div>
</section>
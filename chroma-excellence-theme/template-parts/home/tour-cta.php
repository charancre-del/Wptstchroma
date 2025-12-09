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
    <div class="max-w-5xl mx-auto px-4 lg:px-6">

        <!-- Header -->
        <div class="text-center mb-8">
            <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">
                <?php echo esc_html($tour_cta['heading'] ?: 'Schedule a private tour'); ?>
            </h2>
            <p class="text-brand-ink text-sm md:text-base max-w-2xl mx-auto">
                <?php echo esc_html($tour_cta['subheading']); ?>
            </p>
        </div>

        <!-- Benefits Bar (Compact Horizontal) -->
        <div
            class="bg-gradient-to-r from-chroma-blue via-chroma-green to-chroma-yellow text-white rounded-2xl p-5 mb-6">
            <div class="flex flex-wrap justify-center gap-x-8 gap-y-2 text-sm">
                <span class="flex items-center gap-2"><span>✓</span> Warm, consistent teachers</span>
                <span class="flex items-center gap-2"><span>✓</span> Daily parent communication</span>
                <span class="flex items-center gap-2"><span>✓</span> Healthy meals included</span>
                <span class="flex items-center gap-2"><span>✓</span> Age-appropriate security</span>
                <span class="flex items-center gap-2"><span>✓</span> GA Lottery Pre-K</span>
            </div>
        </div>

        <!-- Form Container (Full Width for 2-column form) -->
        <div class="bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 overflow-hidden p-4">
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
            <div class="text-center mt-4 text-xs text-brand-ink">
                <span class="font-semibold">Typical tour length: 20–30 minutes</span> — Meet the Director and get
                tuition details.
                <?php if (!empty($tour_cta['trust_text'])): ?>
                    <p class="mt-2"><?php echo esc_html($tour_cta['trust_text']); ?></p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>
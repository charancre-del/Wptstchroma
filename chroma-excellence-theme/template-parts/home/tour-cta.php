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
    <div class="max-w-screen-xl mx-auto px-4 lg:px-6">

        <!-- Header -->
        <div class="text-center mb-8">
            <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">
                <?php echo esc_html($tour_cta['heading'] ?: 'Schedule a private tour'); ?>
            </h2>
            <p class="text-brand-ink text-sm md:text-base max-w-2xl mx-auto">
                <?php echo esc_html($tour_cta['subheading']); ?>
            </p>
        </div>

        <!-- Two Column: Narrow Benefits + Wide Form -->
        <div class="bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 overflow-hidden flex flex-col lg:flex-row">

            <!-- Left: Benefits (Narrow, Fixed Width) -->
            <div class="bg-gradient-to-br from-chroma-blue via-chroma-green to-chroma-yellow text-white p-6 lg:p-8 lg:w-80 flex-shrink-0">
                <p class="text-[11px] font-semibold tracking-[0.2em] uppercase mb-4">Why families choose Chroma</p>
                <ul class="space-y-3 text-sm">
                    <li class="flex gap-2"><span>✓</span><span>Warm, consistent teachers</span></li>
                    <li class="flex gap-2"><span>✓</span><span>Daily parent communication</span></li>
                    <li class="flex gap-2"><span>✓</span><span>Healthy meals included</span></li>
                    <li class="flex gap-2"><span>✓</span><span>Age-appropriate security</span></li>
                    <li class="flex gap-2"><span>✓</span><span>GA Lottery Pre-K available</span></li>
                </ul>
                <div class="mt-6 pt-4 border-t border-white/20 text-xs">
                    <p class="font-semibold">Tour: 20–30 min</p>
                </div>
            </div>

            <!-- Right: Form (Takes Remaining Space) -->
            <div class="flex-1 p-4 lg:p-6">
                <?php
                if (shortcode_exists('chroma_tour_form')) {
                    echo do_shortcode('[chroma_tour_form]');
                } else {
                    ?>
                    <div class="text-brand-ink text-sm">Please activate the "Chroma Tour Form" plugin.</div>
                    <?php
                }
                ?>
                <?php if (!empty($tour_cta['trust_text'])): ?>
                    <p class="text-[11px] text-brand-ink mt-4"><?php echo esc_html($tour_cta['trust_text']); ?></p>
                <?php endif; ?>
            </div>

        </div>

    </div>
</section>
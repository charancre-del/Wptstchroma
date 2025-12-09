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
    <div class="max-w-7xl mx-auto px-4 lg:px-6">

        <!-- Header -->
        <div class="text-center mb-10">
            <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">
                <?php echo esc_html($tour_cta['heading'] ?: 'Schedule a private tour'); ?>
            </h2>
            <p class="text-brand-ink text-sm md:text-base max-w-2xl mx-auto">
                <?php echo esc_html($tour_cta['subheading']); ?>
            </p>
        </div>

        <!-- Two Column: Benefits + Form Side by Side -->
        <div
            class="bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 overflow-hidden grid lg:grid-cols-[1fr,1.2fr]">

            <!-- Left: Why Families Choose Chroma -->
            <div
                class="bg-gradient-to-br from-chroma-blue via-chroma-green to-chroma-yellow text-white p-8 lg:p-10 flex flex-col justify-between">
                <div>
                    <p class="text-[11px] font-semibold tracking-[0.2em] uppercase mb-6">Why families choose Chroma</p>
                    <ul class="space-y-4 text-sm">
                        <li class="flex gap-3"><span class="mt-0.5 text-white">✓</span><span>Warm, consistent teachers
                                who know your child well</span></li>
                        <li class="flex gap-3"><span class="mt-0.5 text-white">✓</span><span>Daily parent communication
                                with photos and updates</span></li>
                        <li class="flex gap-3"><span class="mt-0.5 text-white">✓</span><span>Healthy meals included
                                through CACFP participation</span></li>
                        <li class="flex gap-3"><span class="mt-0.5 text-white">✓</span><span>Age-appropriate security
                                and safety protocols</span></li>
                        <li class="flex gap-3"><span class="mt-0.5 text-white">✓</span><span>GA Lottery Pre-K at many
                                locations</span></li>
                    </ul>
                </div>
                <div class="mt-8 text-xs text-white/90">
                    <p class="font-semibold mb-1">Typical tour length: 20–30 minutes</p>
                    <p>Meet the Director, walk classrooms, and get tuition details for your child's age group.</p>
                </div>
            </div>

            <!-- Right: Form -->
            <div class="p-6 lg:p-8">
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
                    <p class="text-[11px] text-brand-ink mt-4"><?php echo esc_html($tour_cta['trust_text']); ?></p>
                <?php endif; ?>
            </div>

        </div>

    </div>
</section>
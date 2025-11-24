<?php
/**
 * Template Part: Tour CTA
 * Final conversion section with tour form
 *
 * @package Chroma_Excellence
 */

$tour_cta = chroma_home_tour_cta();
if ( ! $tour_cta ) {
    return;
}
?>

<section id="tour" class="py-20 bg-brand-cream border-t border-chroma-blue/10" data-section="tour-cta">
    <div class="max-w-5xl mx-auto px-4 lg:px-6">
        <div class="bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 overflow-hidden grid md:grid-cols-[1.1fr,1fr]">
            <div class="p-8 md:p-10">
                <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3"><?php echo esc_html( $tour_cta['heading'] ?: 'Schedule a private tour' ); ?></h2>
                <p class="text-brand-ink/70 text-sm md:text-base mb-6"><?php echo esc_html( $tour_cta['subheading'] ); ?></p>
                <div class="space-y-4">
                    <?php
                    if ( shortcode_exists( 'chroma_tour_form' ) ) {
                        echo do_shortcode( '[chroma_tour_form]' );
                    } else {
                        ?>
                        <div class="text-brand-ink/60 text-sm">Please activate the "Chroma Tour Form" plugin to display the tour booking form.</div>
                        <?php
                    }
                    ?>
                </div>
                <?php if ( ! empty( $tour_cta['trust_text'] ) ) : ?>
                        <p class="text-[11px] text-brand-ink/50 mt-3"><?php echo esc_html( $tour_cta['trust_text'] ); ?></p>
                <?php endif; ?>
            </div>
            <div class="bg-gradient-to-br from-chroma-blue via-chroma-green to-chroma-yellow text-white p-7 md:p-8 flex flex-col justify-between">
                <div>
                    <p class="text-[11px] font-semibold tracking-[0.2em] uppercase mb-2">Why families choose Chroma</p>
                    <ul class="space-y-3 text-sm">
                        <li class="flex gap-2"><span class="mt-0.5 text-white">✓</span><span>Warm, consistent teachers who know your child well</span></li>
                        <li class="flex gap-2"><span class="mt-0.5 text-white">✓</span><span>Daily parent communication with photos and updates</span></li>
                        <li class="flex gap-2"><span class="mt-0.5 text-white">✓</span><span>Healthy meals included through CACFP participation</span></li>
                        <li class="flex gap-2"><span class="mt-0.5 text-white">✓</span><span>Age-appropriate security and safety protocols</span></li>
                        <li class="flex gap-2"><span class="mt-0.5 text-white">✓</span><span>GA Lottery Pre-K at many locations</span></li>
                    </ul>
                </div>
                <div class="mt-6 text-xs text-white/80">
                    <p class="font-semibold mb-1">Typical tour length: 20–30 minutes</p>
                    <p>Meet the Director, walk classrooms, and get tuition details for your child’s age group.</p>
                </div>
            </div>
        </div>
    </div>
</section>

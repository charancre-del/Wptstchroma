<?php
/**
 * Template Part: Tour CTA
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
                <div class="text-center mb-8">
                        <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">
                                <?php echo esc_html($tour_cta['heading']); ?>
                        </h2>
                        <p class="text-brand-ink text-sm md:text-base max-w-2xl mx-auto">
                                <?php echo esc_html($tour_cta['subheading']); ?>
                        </p>
                </div>

                <div class="bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 overflow-hidden flex flex-col lg:flex-row">
                        <div class="bg-gradient-to-br from-chroma-blue via-chroma-green to-chroma-yellow text-white p-6 lg:p-8 lg:w-80 flex-shrink-0">
                                <p class="text-[11px] font-semibold tracking-[0.2em] uppercase mb-4">
                                        <?php echo esc_html($tour_cta['benefits_heading']); ?>
                                </p>
                                <ul class="space-y-3 text-sm">
                                        <?php foreach ($tour_cta['benefit_items'] as $item): ?>
                                                <li class="flex gap-2"><span>&#10003;</span><span><?php echo esc_html($item); ?></span></li>
                                        <?php endforeach; ?>
                                </ul>
                                <div class="mt-6 pt-4 border-t border-white/20 text-xs">
                                        <p class="font-semibold"><?php echo esc_html($tour_cta['time_label']); ?></p>
                                </div>
                        </div>

                        <div class="flex-1 p-4 lg:p-6">
                                <?php if (shortcode_exists('chroma_tour_form')): ?>
                                        <?php echo do_shortcode('[chroma_tour_form]'); ?>
                                <?php else: ?>
                                        <div class="text-brand-ink text-sm"><?php echo esc_html($tour_cta['plugin_missing_message']); ?></div>
                                <?php endif; ?>

                                <?php if (!empty($tour_cta['trust_text'])): ?>
                                        <p class="text-[11px] text-brand-ink mt-4"><?php echo esc_html($tour_cta['trust_text']); ?></p>
                                <?php endif; ?>
                        </div>
                </div>
        </div>
</section>

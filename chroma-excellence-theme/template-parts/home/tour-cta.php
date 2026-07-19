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
                <div class="chroma-tour-grid grid lg:grid-cols-[0.9fr_1.1fr] gap-7 items-start" data-tour-scroll-grid>
                        <div class="chroma-tour-info-card bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 p-8 lg:p-9" data-tour-info-card>
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-chroma-red mb-4">
                                        <?php echo esc_html($tour_cta['heading']); ?>
                                </p>
                                <h2 class="font-serif text-4xl md:text-5xl lg:text-[3.65rem] font-semibold tracking-[-0.04em] leading-[0.95] text-brand-ink mb-5">
                                        <?php esc_html_e('Share a few details and your preferred campus.', 'chroma-excellence'); ?>
                                </h2>
                                <p class="text-brand-ink/70 text-base md:text-lg leading-relaxed">
                                        <?php esc_html_e('A Chroma Director will reach out to confirm tour times.', 'chroma-excellence'); ?>
                                </p>
                                <ul class="grid gap-3 mt-7 text-sm">
                                        <?php foreach ($tour_cta['benefit_items'] as $item): ?>
                                                <li class="flex gap-2 rounded-2xl bg-chroma-red/10 px-4 py-3 font-bold text-brand-ink"><span>&#10003;</span><span><?php echo esc_html($item); ?></span></li>
                                        <?php endforeach; ?>
                                </ul>
                                <div class="mt-4 rounded-2xl bg-chroma-red/10 px-4 py-3 text-sm font-bold text-brand-ink">
                                        <?php echo esc_html($tour_cta['time_label']); ?>
                                </div>
                        </div>

                        <div class="chroma-tour-form-card chroma-form-scroll-card bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 p-5 lg:p-7" data-tour-form-card tabindex="0" aria-label="<?php esc_attr_e( 'Schedule a tour form', 'chroma-excellence' ); ?>">
                                <?php if (shortcode_exists('chroma_tour_form')): ?>
                                        <?php echo do_shortcode('[chroma_tour_form]'); ?>
                                <?php else: ?>
                                        <div class="text-brand-ink text-sm"><?php echo esc_html($tour_cta['plugin_missing_message']); ?></div>
                                <?php endif; ?>

                                <?php if (!empty($tour_cta['trust_text'])): ?>
					<p class="text-xs leading-relaxed text-brand-ink mt-4"><?php echo esc_html($tour_cta['trust_text']); ?></p>
                                <?php endif; ?>
                        </div>
                </div>
        </div>
</section>

<?php
/**
 * Template Part: FAQ Section
 * Accordion-based frequently asked questions
 *
 * @package Chroma_Excellence
 */

$faq_data = chroma_home_faq();
if (!$faq_data || empty($faq_data['items'])) {
    return;
}
?>

<section id="faq" class="py-20 bg-brand-cream" data-section="faq">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="faqBox bg-white border border-chroma-blue/10 rounded-[2.5rem] shadow-soft p-7 md:p-10 lg:p-12">
            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-chroma-red mb-3">
                <?php echo esc_html($faq_data['heading'] ?: __('Common questions from parents', 'chroma-excellence')); ?>
            </p>
            <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] leading-tight text-brand-ink mb-8">
                <?php echo esc_html($faq_data['subheading'] ?: __('We’ve answered a few of the questions parents ask most when choosing childcare and early learning.', 'chroma-excellence')); ?>
            </h2>

            <div class="faqWrap grid gap-3" data-accordion-group>
                <?php foreach ($faq_data['items'] as $index => $item):
                    if (empty($item['question'])) {
                        continue;
                    }
                    $faq_id = 'faq-' . ($index + 1);
                    ?>
                    <?php $is_open = 0 === $index; ?>
                    <div class="faqItem bg-white rounded-[1.25rem] border border-chroma-blue/10 overflow-hidden <?php echo $is_open ? 'open' : ''; ?>" data-accordion>
                        <button class="faqQ w-full text-left flex items-center justify-between gap-3 px-5 py-4"
                            data-accordion-trigger
                            aria-expanded="<?php echo esc_attr($is_open ? 'true' : 'false'); ?>"
                            aria-controls="<?php echo esc_attr($faq_id); ?>">
                            <span class="font-bold text-brand-ink">
                                <?php echo esc_html($item['question']); ?>
                            </span>
                            <span class="text-brand-ink text-xl flex-shrink-0" data-accordion-icon><?php echo $is_open ? '−' : '+'; ?></span>
                        </button>
                        <div id="<?php echo esc_attr($faq_id); ?>" class="faqA px-5 pb-5 text-brand-ink/75 <?php echo $is_open ? '' : 'hidden'; ?>"
                            data-accordion-content>
                            <div><?php echo wp_kses_post(wpautop($item['answer'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($faq_data['cta_text']) && !empty($faq_data['cta_link'])): ?>
                <div class="text-center mt-10">
                    <p class="text-brand-ink mb-4">
                        <?php echo esc_html($faq_data['cta_text']); ?>
                    </p>
                    <a href="<?php echo esc_url($faq_data['cta_link']); ?>"
                        class="inline-block bg-chroma-red text-white px-8 py-3 rounded-full font-bold hover:bg-chroma-red/90 transition-colors">
                        <?php echo esc_html($faq_data['cta_label'] ?: __('Contact Us', 'chroma-excellence')); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

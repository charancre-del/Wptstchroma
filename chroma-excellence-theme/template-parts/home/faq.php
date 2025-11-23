<?php
/**
 * Template Part: FAQ Section
 * Accordion-based frequently asked questions
 *
 * @package Chroma_Excellence
 */

$faq_data = chroma_home_faq();
if ( ! $faq_data || empty( $faq_data['items'] ) ) {
    return;
}
?>

<section class="py-20 bg-brand-cream" data-section="faq">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header -->
        <div class="text-center mb-12">
            <h2 class="text-4xl md:text-5xl font-bold text-brand-ink mb-4">
                <?php echo esc_html( $faq_data['heading'] ?: 'Frequently Asked Questions' ); ?>
            </h2>
            <?php if ( ! empty( $faq_data['subheading'] ) ) : ?>
                <p class="text-xl text-brand-ink/80">
                    <?php echo esc_html( $faq_data['subheading'] ); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- FAQ Accordion -->
        <div class="space-y-4" data-accordion-group>
            <?php foreach ( $faq_data['items'] as $index => $item ) :
                if ( empty( $item['question'] ) ) {
                    continue;
                }
                $faq_id = 'faq-' . ( $index + 1 );
            ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden" data-accordion>
                <button 
                    class="w-full text-left px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
                    data-accordion-trigger
                    aria-expanded="false"
                    aria-controls="<?php echo esc_attr( $faq_id ); ?>"
                >
                    <span class="text-lg font-semibold text-brand-ink pr-8">
                        <?php echo esc_html( $item['question'] ); ?>
                    </span>
                    <span class="text-chroma-teal text-2xl flex-shrink-0" data-accordion-icon>
                        <i class="fas fa-chevron-down transition-transform duration-300"></i>
                    </span>
                </button>
                <div 
                    id="<?php echo esc_attr( $faq_id ); ?>"
                    class="px-6 overflow-hidden transition-all duration-300"
                    data-accordion-content
                    style="max-height: 0;"
                >
                    <div class="pb-4 text-brand-ink/70">
                        <?php echo wp_kses_post( wpautop( $item['answer'] ) ); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Additional CTA -->
        <?php if ( ! empty( $faq_data['cta_text'] ) && ! empty( $faq_data['cta_link'] ) ) : ?>
        <div class="text-center mt-12">
            <p class="text-brand-ink/80 mb-4">
                <?php echo esc_html( $faq_data['cta_text'] ); ?>
            </p>
            <a href="<?php echo esc_url( $faq_data['cta_link'] ); ?>" class="inline-block bg-chroma-teal text-white px-8 py-3 rounded-lg font-bold hover:bg-chroma-teal/90 transition-colors">
                <?php echo esc_html( $faq_data['cta_label'] ?: 'Contact Us' ); ?>
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>

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

<section class="py-20 bg-gradient-to-br from-chroma-teal to-chroma-green" data-section="tour-cta">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- CTA Content -->
        <div class="text-center mb-12">
            <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                <?php echo esc_html( $tour_cta['heading'] ?: 'Schedule Your Tour Today' ); ?>
            </h2>
            <?php if ( ! empty( $tour_cta['subheading'] ) ) : ?>
                <p class="text-xl text-white/90 max-w-3xl mx-auto mb-8">
                    <?php echo esc_html( $tour_cta['subheading'] ); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Tour Form -->
        <div class="bg-white rounded-xl shadow-2xl p-8 md:p-12">
            <?php
            // Output tour form shortcode
            if ( shortcode_exists( 'chroma_tour_form' ) ) {
                echo do_shortcode( '[chroma_tour_form]' );
            } else {
                ?>
                <div class="text-center text-brand-ink/60 py-8">
                    <p class="mb-4">Tour form plugin not activated.</p>
                    <p class="text-sm">Please activate the "Chroma Tour Form" plugin to display the tour booking form.</p>
                </div>
                <?php
            }
            ?>
        </div>

        <!-- Trust Indicators -->
        <?php if ( ! empty( $tour_cta['trust_text'] ) ) : ?>
        <div class="text-center mt-8 text-white/90">
            <p class="text-sm">
                <i class="fas fa-shield-alt mr-2"></i>
                <?php echo esc_html( $tour_cta['trust_text'] ); ?>
            </p>
        </div>
        <?php endif; ?>

    </div>
</section>

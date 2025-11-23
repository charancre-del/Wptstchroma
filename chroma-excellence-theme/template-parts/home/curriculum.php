<?php
/**
 * Template Part: Curriculum (Prismpath)
 * Showcase the proprietary curriculum with multi-card layout
 *
 * @package Chroma_Excellence
 */

$curriculum = chroma_home_curriculum();
if ( ! $curriculum ) {
    return;
}
?>

<section class="py-20 bg-gradient-to-br from-chroma-teal/10 to-chroma-green/10" data-section="curriculum">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-brand-ink mb-4">
                <?php echo esc_html( $curriculum['heading'] ?: 'The Prismpath Curriculum' ); ?>
            </h2>
            <?php if ( ! empty( $curriculum['subheading'] ) ) : ?>
                <p class="text-xl text-brand-ink/80 max-w-3xl mx-auto">
                    <?php echo esc_html( $curriculum['subheading'] ); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Curriculum Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            
            <!-- Card 1: Philosophy -->
            <?php if ( ! empty( $curriculum['card_1_heading'] ) ) : ?>
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-2xl transition-shadow duration-300" data-curriculum-card="philosophy">
                <div class="text-chroma-red text-5xl mb-4">
                    <i class="fas fa-brain"></i>
                </div>
                <h3 class="text-2xl font-bold text-brand-ink mb-4">
                    <?php echo esc_html( $curriculum['card_1_heading'] ); ?>
                </h3>
                <p class="text-brand-ink/70">
                    <?php echo esc_html( $curriculum['card_1_text'] ); ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Card 2: Approach -->
            <?php if ( ! empty( $curriculum['card_2_heading'] ) ) : ?>
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-2xl transition-shadow duration-300" data-curriculum-card="approach">
                <div class="text-chroma-teal text-5xl mb-4">
                    <i class="fas fa-palette"></i>
                </div>
                <h3 class="text-2xl font-bold text-brand-ink mb-4">
                    <?php echo esc_html( $curriculum['card_2_heading'] ); ?>
                </h3>
                <p class="text-brand-ink/70">
                    <?php echo esc_html( $curriculum['card_2_text'] ); ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Card 3: Outcomes -->
            <?php if ( ! empty( $curriculum['card_3_heading'] ) ) : ?>
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-2xl transition-shadow duration-300" data-curriculum-card="outcomes">
                <div class="text-chroma-yellow text-5xl mb-4">
                    <i class="fas fa-trophy"></i>
                </div>
                <h3 class="text-2xl font-bold text-brand-ink mb-4">
                    <?php echo esc_html( $curriculum['card_3_heading'] ); ?>
                </h3>
                <p class="text-brand-ink/70">
                    <?php echo esc_html( $curriculum['card_3_text'] ); ?>
                </p>
            </div>
            <?php endif; ?>

        </div>

        <!-- Description -->
        <?php if ( ! empty( $curriculum['description'] ) ) : ?>
        <div class="max-w-4xl mx-auto text-center mb-10">
            <div class="prose prose-lg mx-auto text-brand-ink/80">
                <?php echo wp_kses_post( wpautop( $curriculum['description'] ) ); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <?php if ( ! empty( $curriculum['cta_link'] ) ) : ?>
        <div class="text-center">
            <a href="<?php echo esc_url( $curriculum['cta_link'] ); ?>" class="inline-block bg-chroma-red text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-chroma-red/90 transition-colors">
                <?php echo esc_html( $curriculum['cta_label'] ?: 'Learn About Prismpath' ); ?>
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>

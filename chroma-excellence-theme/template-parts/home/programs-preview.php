<?php
/**
 * Template Part: Programs Preview
 * Featured programs grid with CTAs
 *
 * @package Chroma_Excellence
 */

$programs = chroma_home_programs_preview();
if ( ! $programs ) {
    return;
}

// Get featured programs or fallback to latest 3
$featured = get_field( 'home_featured_programs', 'option' );
if ( ! $featured ) {
    $featured = get_posts( array(
        'post_type'      => 'program',
        'posts_per_page' => 3,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    ) );
}
?>

<section class="py-20 bg-brand-cream" data-section="programs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header -->
        <div class="text-center mb-12">
            <h2 class="text-4xl md:text-5xl font-bold text-brand-ink mb-4">
                <?php echo esc_html( $programs['heading'] ?: 'Our Programs' ); ?>
            </h2>
            <?php if ( ! empty( $programs['subheading'] ) ) : ?>
                <p class="text-xl text-brand-ink/80 max-w-3xl mx-auto">
                    <?php echo esc_html( $programs['subheading'] ); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Programs Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <?php foreach ( $featured as $program ) :
                setup_postdata( $program );
                $age_range = get_field( 'program_age_range', $program->ID );
                $excerpt   = get_field( 'program_short_description', $program->ID ) ?: wp_trim_words( $program->post_content, 20 );
                $icon      = get_field( 'program_icon_class', $program->ID ) ?: 'fas fa-child';
            ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300" data-program="<?php echo esc_attr( $program->ID ); ?>">
                <div class="p-8">
                    <div class="text-chroma-teal text-4xl mb-4">
                        <i class="<?php echo esc_attr( $icon ); ?>"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-brand-ink mb-2">
                        <?php echo esc_html( $program->post_title ); ?>
                    </h3>
                    <?php if ( $age_range ) : ?>
                        <div class="text-chroma-yellow font-semibold mb-4">
                            Ages <?php echo esc_html( $age_range ); ?>
                        </div>
                    <?php endif; ?>
                    <p class="text-brand-ink/70 mb-6">
                        <?php echo esc_html( $excerpt ); ?>
                    </p>
                    <a href="<?php echo esc_url( get_permalink( $program ) ); ?>" class="inline-block bg-chroma-teal text-white px-6 py-3 rounded-lg font-semibold hover:bg-chroma-teal/90 transition-colors">
                        Learn More
                    </a>
                </div>
            </div>
            <?php endforeach; wp_reset_postdata(); ?>
        </div>

        <!-- View All CTA -->
        <?php if ( ! empty( $programs['cta_link'] ) ) : ?>
        <div class="text-center">
            <a href="<?php echo esc_url( $programs['cta_link'] ); ?>" class="inline-block bg-brand-navy text-brand-cream px-8 py-4 rounded-lg font-bold text-lg hover:bg-brand-navy/90 transition-colors">
                <?php echo esc_html( $programs['cta_label'] ?: 'View All Programs' ); ?>
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>

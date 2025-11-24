<?php
/**
 * Template Name: Programs
 * Displays all programs in a categorized grid
 *
 * @package Chroma_Excellence
 */

get_header();

// Get all published programs
$programs = get_posts( array(
    'post_type'      => 'program',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
) );
?>

<main id="primary" class="site-main">
    
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-chroma-red to-chroma-yellow py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                <?php the_title(); ?>
            </h1>
            <?php if ( has_excerpt() ) : ?>
                <p class="text-xl text-white/90 max-w-3xl mx-auto">
                    <?php the_excerpt(); ?>
                </p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Programs Grid -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <?php if ( ! empty( $programs ) ) : ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ( $programs as $program ) :
                        setup_postdata( $program );
                        $meta      = chroma_get_program_meta( $program->ID );
                        $age_range = $meta['age_range'];
                        $excerpt   = $meta['short_description'] ?: wp_trim_words( $program->post_content, 25 );
                        $icon      = $meta['icon_class'];
                        $color     = $meta['color'];
                        $age_range = get_field( 'program_age_range', $program->ID );
                        $excerpt   = get_field( 'program_short_description', $program->ID ) ?: wp_trim_words( $program->post_content, 25 );
                        $icon      = get_field( 'program_icon_class', $program->ID ) ?: 'fas fa-child';

                        $color_key = get_field( 'program_color', $program->ID ) ?: 'chroma-teal';
                        $palettes  = array(
                            'chroma-teal'   => array(
                                'gradient_from' => 'from-chroma-teal/10',
                                'gradient_to'   => 'to-chroma-teal/5',
                                'text'          => 'text-chroma-teal',
                                'button'        => 'bg-chroma-teal hover:bg-chroma-teal/90',
                            ),
                            'chroma-blue'   => array(
                                'gradient_from' => 'from-chroma-blue/10',
                                'gradient_to'   => 'to-chroma-blue/5',
                                'text'          => 'text-chroma-blue',
                                'button'        => 'bg-chroma-blue hover:bg-chroma-blueDark',
                            ),
                            'chroma-red'    => array(
                                'gradient_from' => 'from-chroma-red/10',
                                'gradient_to'   => 'to-chroma-red/5',
                                'text'          => 'text-chroma-red',
                                'button'        => 'bg-chroma-red hover:bg-chroma-red/90',
                            ),
                            'chroma-green'  => array(
                                'gradient_from' => 'from-chroma-green/10',
                                'gradient_to'   => 'to-chroma-green/5',
                                'text'          => 'text-chroma-green',
                                'button'        => 'bg-chroma-green hover:bg-chroma-green/90',
                            ),
                            'chroma-yellow' => array(
                                'gradient_from' => 'from-chroma-yellow/10',
                                'gradient_to'   => 'to-chroma-yellow/5',
                                'text'          => 'text-chroma-yellow',
                                'button'        => 'bg-chroma-yellow hover:bg-chroma-yellow/90',
                            ),
                        );

                        $palette = $palettes[ $color_key ] ?? $palettes['chroma-teal'];
                    ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-shadow duration-300" data-program="<?php echo esc_attr( $program->ID ); ?>">
                        <div class="bg-gradient-to-br <?php echo esc_attr( $palette['gradient_from'] . ' ' . $palette['gradient_to'] ); ?> p-8">
                            <div class="<?php echo esc_attr( $palette['text'] ); ?> text-5xl mb-4">
                                <i class="<?php echo esc_attr( $icon ); ?>"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-brand-ink mb-2">
                                <?php echo esc_html( $program->post_title ); ?>
                            </h2>
                            <?php if ( $age_range ) : ?>
                                <div class="text-chroma-yellow font-semibold mb-4">
                                    Ages <?php echo esc_html( $age_range ); ?>
                                </div>
                            <?php endif; ?>
                            <p class="text-brand-ink/70 mb-6">
                                <?php echo esc_html( $excerpt ); ?>
                            </p>
                            <a href="<?php echo esc_url( get_permalink( $program ) ); ?>" class="inline-block <?php echo esc_attr( $palette['button'] ); ?> text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                                Learn More
                            </a>
                        </div>
                    </div>
                    <?php endforeach; wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <p class="text-center text-brand-ink/60 text-lg">No programs found.</p>
            <?php endif; ?>

        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-brand-cream">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-brand-ink mb-4">
                Ready to Enroll?
            </h2>
            <p class="text-xl text-brand-ink/80 mb-8">
                Schedule a tour to see our programs in action and meet our team.
            </p>
            <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="inline-block bg-chroma-teal text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-chroma-teal/90 transition-colors">
                Schedule Your Tour
            </a>
        </div>
    </section>

</main>

<?php
get_footer();

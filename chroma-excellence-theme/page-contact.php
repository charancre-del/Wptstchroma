<?php
/**
 * Template Name: Contact
 * Contact page with form and location information
 *
 * @package Chroma_Excellence
 */

get_header();

// Get global contact info
$global_phone   = chroma_global_phone();
$global_email   = chroma_global_email();
$global_address = chroma_global_full_address();
?>

<main id="primary" class="site-main">
    
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-chroma-teal to-chroma-green py-16">
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

    <!-- Contact Form & Info -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                
                <!-- Contact Form -->
                <div>
                    <h2 class="text-3xl font-bold text-brand-ink mb-6">
                        Send Us a Message
                    </h2>
                    <div class="bg-brand-cream rounded-xl p-8">
                        <?php
                        // Output tour form shortcode
                        if ( shortcode_exists( 'chroma_tour_form' ) ) {
                            echo do_shortcode( '[chroma_tour_form]' );
                        } else {
                            ?>
                            <p class="text-brand-ink/60">Tour form plugin not activated. Please activate the "Chroma Tour Form" plugin.</p>
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <!-- Contact Information -->
                <div>
                    <h2 class="text-3xl font-bold text-brand-ink mb-6">
                        Get In Touch
                    </h2>
                    
                    <div class="space-y-6 mb-8">
                        <?php if ( $global_phone ) : ?>
                        <div class="flex items-start">
                            <div class="text-chroma-teal text-2xl mr-4 mt-1">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-brand-ink mb-1">Phone</h3>
                                <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $global_phone ) ); ?>" class="text-brand-ink/70 hover:text-chroma-teal">
                                    <?php echo esc_html( $global_phone ); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ( $global_email ) : ?>
                        <div class="flex items-start">
                            <div class="text-chroma-teal text-2xl mr-4 mt-1">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-brand-ink mb-1">Email</h3>
                                <a href="mailto:<?php echo esc_attr( $global_email ); ?>" class="text-brand-ink/70 hover:text-chroma-teal">
                                    <?php echo esc_html( $global_email ); ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ( $global_address ) : ?>
                        <div class="flex items-start">
                            <div class="text-chroma-teal text-2xl mr-4 mt-1">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-brand-ink mb-1">Address</h3>
                                <p class="text-brand-ink/70">
                                    <?php echo nl2br( esc_html( $global_address ) ); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Office Hours -->
                    <?php
                    $office_hours = get_option( 'office_hours', '' );
                    if ( $office_hours ) :
                    ?>
                    <div class="bg-brand-cream rounded-xl p-6">
                        <h3 class="font-bold text-brand-ink mb-3 flex items-center">
                            <i class="fas fa-clock text-chroma-yellow mr-2"></i>
                            Office Hours
                        </h3>
                        <div class="text-brand-ink/70">
                            <?php echo wp_kses_post( wpautop( $office_hours ) ); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Social Media -->
                    <?php
                    $social = array(
                        'facebook'  => get_option( 'social_facebook', '' ),
                        'instagram' => get_option( 'social_instagram', '' ),
                        'linkedin'  => get_option( 'social_linkedin', '' ),
                        'twitter'   => get_option( 'social_twitter', '' ),
                    );
                    $has_social = array_filter( $social );
                    if ( $has_social ) :
                    ?>
                    <div class="mt-8">
                        <h3 class="font-bold text-brand-ink mb-4">Follow Us</h3>
                        <div class="flex gap-4">
                            <?php if ( $social['facebook'] ) : ?>
                                <a href="<?php echo esc_url( $social['facebook'] ); ?>" class="text-3xl text-brand-ink hover:text-chroma-teal transition-colors" target="_blank" rel="noopener">
                                    <i class="fab fa-facebook"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ( $social['instagram'] ) : ?>
                                <a href="<?php echo esc_url( $social['instagram'] ); ?>" class="text-3xl text-brand-ink hover:text-chroma-teal transition-colors" target="_blank" rel="noopener">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ( $social['linkedin'] ) : ?>
                                <a href="<?php echo esc_url( $social['linkedin'] ); ?>" class="text-3xl text-brand-ink hover:text-chroma-teal transition-colors" target="_blank" rel="noopener">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ( $social['twitter'] ) : ?>
                                <a href="<?php echo esc_url( $social['twitter'] ); ?>" class="text-3xl text-brand-ink hover:text-chroma-teal transition-colors" target="_blank" rel="noopener">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

            </div>
        </div>
    </section>

    <!-- Locations Quick Links -->
    <?php
    $locations = get_posts( array(
        'post_type'      => 'location',
        'posts_per_page' => 4,
        'post_status'    => 'publish',
    ) );
    if ( $locations ) :
    ?>
    <section class="py-16 bg-brand-cream">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-brand-ink mb-8 text-center">
                Visit a Location
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ( $locations as $location ) :
                    $fields = chroma_get_location_fields( $location->ID );
                    $city   = $fields['city'];
                    $state  = $fields['state'];
                ?>
                <a href="<?php echo esc_url( get_permalink( $location ) ); ?>" class="bg-white rounded-lg p-6 text-center hover:shadow-lg transition-shadow">
                    <i class="fas fa-map-marker-alt text-chroma-teal text-3xl mb-3"></i>
                    <h3 class="font-bold text-brand-ink mb-1">
                        <?php echo esc_html( $location->post_title ); ?>
                    </h3>
                    <?php if ( $city && $state ) : ?>
                        <p class="text-brand-ink/70 text-sm">
                            <?php echo esc_html( $city . ', ' . strtoupper( $state ) ); ?>
                        </p>
                    <?php endif; ?>
                </a>
                <?php endforeach; wp_reset_postdata(); ?>
            </div>
            <div class="text-center mt-8">
                <a href="<?php echo esc_url( home_url( '/locations/' ) ); ?>" class="text-chroma-teal font-semibold hover:text-chroma-teal/80">
                    View All Locations →
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php
get_footer();

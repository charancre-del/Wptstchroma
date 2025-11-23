<?php
/**
 * Template Name: Locations
 * Displays all locations with interactive map and filterable grid
 *
 * @package Chroma_Excellence
 */

get_header();

// Get all published locations
$locations = get_posts( array(
    'post_type'      => 'location',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'title',
    'order'          => 'ASC',
) );

// Build map data
$map_data = array();
foreach ( $locations as $loc ) {
    $lat = get_field( 'location_latitude', $loc->ID );
    $lng = get_field( 'location_longitude', $loc->ID );
    if ( $lat && $lng ) {
        $map_data[] = array(
            'id'    => $loc->ID,
            'title' => $loc->post_title,
            'lat'   => floatval( $lat ),
            'lng'   => floatval( $lng ),
            'url'   => get_permalink( $loc ),
        );
    }
}
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

    <!-- Interactive Map -->
    <?php if ( ! empty( $map_data ) ) : ?>
    <section class="py-12 bg-brand-cream">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div 
                id="locations-map" 
                data-chroma-map 
                data-chroma-locations='<?php echo esc_attr( wp_json_encode( $map_data ) ); ?>'
                class="w-full h-[500px] rounded-xl shadow-lg"
            ></div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Locations Grid -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <?php if ( ! empty( $locations ) ) : ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ( $locations as $location ) :
                        setup_postdata( $location );
                        $city    = get_field( 'location_city', $location->ID );
                        $state   = get_field( 'location_state', $location->ID );
                        $phone   = get_field( 'location_phone', $location->ID );
                        $address = get_field( 'location_address', $location->ID );
                        $email   = get_field( 'location_email', $location->ID );
                    ?>
                    <div class="bg-gradient-to-br from-brand-cream to-white rounded-lg shadow-md p-6 hover:shadow-xl transition-shadow" data-location="<?php echo esc_attr( $location->ID ); ?>">
                        <h2 class="text-2xl font-bold text-brand-ink mb-2">
                            <?php echo esc_html( $location->post_title ); ?>
                        </h2>
                        <?php if ( $city && $state ) : ?>
                            <div class="text-chroma-teal font-semibold mb-4">
                                <?php echo esc_html( $city . ', ' . strtoupper( $state ) ); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="space-y-2 mb-6 text-brand-ink/70">
                            <?php if ( $address ) : ?>
                                <p>
                                    <i class="fas fa-map-marker-alt text-chroma-red mr-2"></i>
                                    <?php echo esc_html( $address ); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ( $phone ) : ?>
                                <p>
                                    <i class="fas fa-phone text-chroma-yellow mr-2"></i>
                                    <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9]/', '', $phone ) ); ?>" class="hover:text-chroma-teal">
                                        <?php echo esc_html( $phone ); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                            <?php if ( $email ) : ?>
                                <p>
                                    <i class="fas fa-envelope text-chroma-green mr-2"></i>
                                    <a href="mailto:<?php echo esc_attr( $email ); ?>" class="hover:text-chroma-teal">
                                        <?php echo esc_html( $email ); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <a href="<?php echo esc_url( get_permalink( $location ) ); ?>" class="inline-block bg-chroma-teal text-white px-6 py-2 rounded-lg font-semibold hover:bg-chroma-teal/90 transition-colors w-full text-center">
                            View Details
                        </a>
                    </div>
                    <?php endforeach; wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <p class="text-center text-brand-ink/60 text-lg">No locations found.</p>
            <?php endif; ?>

        </div>
    </section>

</main>

<?php
get_footer();

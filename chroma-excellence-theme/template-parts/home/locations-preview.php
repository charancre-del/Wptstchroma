<?php
/**
 * Template Part: Locations Preview
 * Interactive map + featured locations cards
 *
 * @package Chroma_Excellence
 */

$locations_data = chroma_home_locations_preview();
if ( ! $locations_data ) {
    return;
}

$map_json = $locations_data['map_points'] ?? array();
$featured = $locations_data['featured'] ?? array();
?>

<section class="py-20 bg-white" data-section="locations">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header -->
        <div class="text-center mb-12">
            <h2 class="text-4xl md:text-5xl font-bold text-brand-ink mb-4">
                <?php echo esc_html( $locations_data['heading'] ?: 'Our Locations' ); ?>
            </h2>
            <?php if ( ! empty( $locations_data['subheading'] ) ) : ?>
                <p class="text-xl text-brand-ink/80 max-w-3xl mx-auto">
                    <?php echo esc_html( $locations_data['subheading'] ); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Interactive Map -->
        <?php if ( ! empty( $map_json ) ) : ?>
        <div class="mb-12">
            <div 
                id="chroma-locations-map" 
                data-chroma-map 
                data-chroma-locations='<?php echo esc_attr( wp_json_encode( $map_json ) ); ?>'
                class="w-full h-96 rounded-xl shadow-lg"
            ></div>
        </div>
        <?php endif; ?>

        <!-- Featured Locations Grid -->
        <?php if ( ! empty( $featured ) ) : ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <?php foreach ( $featured as $location ) :
                $city    = $location['city'] ?? '';
                $state   = $location['state'] ?? '';
                $phone   = $location['phone'] ?? '';
                $address = $location['address'] ?? '';
                $url     = $location['url'] ?? '#';
            ?>
            <div class="bg-gradient-to-br from-chroma-teal/5 to-chroma-green/5 rounded-lg p-6 hover:shadow-lg transition-shadow" data-location="<?php echo esc_attr( $location['title'] ); ?>">
                <h3 class="text-2xl font-bold text-brand-ink mb-2">
                    <?php echo esc_html( $location['title'] ); ?>
                </h3>
                <?php if ( $city && $state ) : ?>
                    <div class="text-chroma-teal font-semibold mb-4">
                        <?php echo esc_html( $city . ', ' . strtoupper( $state ) ); ?>
                    </div>
                <?php endif; ?>
                <?php if ( $address ) : ?>
                    <p class="text-brand-ink/70 mb-2">
                        <i class="fas fa-map-marker-alt text-chroma-red mr-2"></i>
                        <?php echo esc_html( $address ); ?>
                    </p>
                <?php endif; ?>
                <?php if ( $phone ) : ?>
                    <p class="text-brand-ink/70 mb-4">
                        <i class="fas fa-phone text-chroma-yellow mr-2"></i>
                        <?php echo esc_html( $phone ); ?>
                    </p>
                <?php endif; ?>
                <a href="<?php echo esc_url( $url ); ?>" class="inline-block bg-chroma-teal text-white px-6 py-2 rounded-lg font-semibold hover:bg-chroma-teal/90 transition-colors">
                    View Location
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- View All CTA -->
        <?php if ( ! empty( $locations_data['cta_link'] ) ) : ?>
        <div class="text-center">
            <a href="<?php echo esc_url( $locations_data['cta_link'] ); ?>" class="inline-block bg-brand-navy text-brand-cream px-8 py-4 rounded-lg font-bold text-lg hover:bg-brand-navy/90 transition-colors">
                <?php echo esc_html( $locations_data['cta_label'] ?: 'View All Locations' ); ?>
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>

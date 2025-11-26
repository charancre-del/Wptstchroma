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

if ( ! function_exists( 'chroma_region_emoji' ) ) {
    function chroma_region_emoji( $label ) {
        $map = array(
            'Cobb County'      => '🍑',
            'Gwinnett County'  => '🌳',
            'North Metro'      => '🏙️',
            'South Metro'      => '⛰️',
        );

        return $map[ $label ] ?? '📍';
    }
}

$map_json = $locations_data['map_points'] ?? array();
$grouped  = $locations_data['grouped'] ?? array();
?>

<section class="py-20 bg-white" data-section="locations">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header -->
        <div class="text-center mb-12">
            <h2 class="text-4xl md:text-5xl font-bold text-brand-ink mb-3">
                <?php echo esc_html( $locations_data['heading'] ?: 'Our Locations' ); ?>
            </h2>
            <?php if ( ! empty( $locations_data['subheading'] ) ) : ?>
                <p class="text-brand-ink/70 text-sm md:text-base max-w-2xl mx-auto">
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
        <?php if ( ! empty( $grouped ) ) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <?php foreach ( $grouped as $group ) : ?>
                <div class="bg-white border border-chroma-blue/10 rounded-3xl p-6 shadow-soft">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xl"><?php echo esc_html( chroma_region_emoji( $group['label'] ?? '' ) ); ?></span>
                        <h3 class="text-xs uppercase tracking-[0.18em] text-brand-ink/60 font-semibold"><?php echo esc_html( $group['label'] ?? '' ); ?></h3>
                    </div>
                    <?php if ( ! empty( $group['locations'] ) ) : ?>
                        <ul class="space-y-4">
                            <?php foreach ( $group['locations'] as $location ) :
                                $city    = $location['city'] ?? '';
                                $state   = $location['state'] ?? '';
                                $phone   = $location['phone'] ?? '';
                                $address = $location['address'] ?? '';
                                $url     = $location['url'] ?? '#';
                            ?>
                                <li class="pb-2">
                                    <div class="flex items-start justify-between gap-3 p-3 rounded-xl border border-chroma-blue/10 hover:border-chroma-red/50 hover:bg-chroma-redLight transition">
                                        <div>
                                            <a href="<?php echo esc_url( $url ); ?>" class="text-lg font-semibold text-brand-ink hover:text-chroma-blue transition-colors">
                                                <?php echo esc_html( $location['title'] ); ?>
                                            </a>
                                            <?php if ( $city && $state ) : ?>
                                                <div class="text-chroma-blue font-semibold">
                                                    <?php echo esc_html( $city . ', ' . strtoupper( $state ) ); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ( $address ) : ?>
                                                <p class="text-brand-ink/70 text-[11px] mt-2">
                                                    <i class="fas fa-map-marker-alt text-chroma-red mr-2"></i>
                                                    <?php echo esc_html( $address ); ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ( $phone ) : ?>
                                                <p class="text-brand-ink/70 text-[11px] mt-1">
                                                    <i class="fas fa-phone text-chroma-yellow mr-2"></i>
                                                    <?php echo esc_html( $phone ); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- View All CTA -->
        <?php if ( ! empty( $locations_data['cta_link'] ) ) : ?>
        <div class="text-center">
            <a href="<?php echo esc_url( $locations_data['cta_link'] ); ?>" class="inline-block bg-brand-ink text-white px-8 py-4 rounded-full font-bold text-lg hover:bg-chroma-blueDark transition-colors">
                <?php echo esc_html( $locations_data['cta_label'] ?: 'View All Locations' ); ?>
            </a>
        </div>
        <?php endif; ?>

    </div>
</section>

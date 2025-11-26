<?php
/**
 * Curriculum Radar Chart
 * Template Part: Curriculum Chart
 * Interactive radar chart showing Prismpath™ curriculum focus by age
 *
 * @package Chroma_Excellence
 */

$profiles = chroma_home_curriculum_profiles();

if ( empty( $profiles['labels'] ) || empty( $profiles['profiles'] ) ) {
        return;
}

$labels       = $profiles['labels'];
$profile_list = array_values( $profiles['profiles'] );
$first        = $profile_list[0];
?>

<section id="curriculum" class="py-20 bg-brand-cream border-y border-chroma-blue/10" data-section="curriculum">
        <div class="max-w-6xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-5">
                        <span class="text-chroma-blue font-bold tracking-[0.2em] text-[11px] uppercase">The Prismpath™ Curriculum</span>
                        <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink">A curriculum that shifts as your child grows</h2>
                        <p class="text-brand-ink/70 text-sm md:text-base">Our Prismpath™ framework balances five pillars – physical, emotional, social, academic, and creative development. The mix changes at each age so your child gets exactly what they need, when they need it.</p>
                        <div class="flex flex-wrap gap-2 text-xs" data-curriculum-buttons>
                                <?php foreach ( $profiles['profiles'] as $index => $profile ) :
                                        $label = $profile['label'] ?? ucfirst( $profile['key'] );
                                ?>
                                        <?php
                                        $is_active = 0 === $index;
                                        $button_classes = $is_active
                                                ? 'bg-chroma-blue text-white shadow-soft'
                                                : 'bg-white text-brand-ink/70 hover:border-chroma-blue';
                                        ?>
                                        <button class="px-4 py-2 rounded-full font-semibold border border-chroma-blue/20 <?php echo esc_attr( $button_classes ); ?>" data-curriculum-button="<?php echo esc_attr( $profile['key'] ); ?>"><?php echo esc_html( $label ); ?></button>
                                <?php endforeach; ?>
                        </div>
                        <div class="bg-white rounded-3xl border-l-4 border-chroma-red shadow-soft p-6 md:p-7">
                                <h3 class="font-serif text-xl md:text-2xl font-bold text-brand-ink mb-2" data-curriculum-title><?php echo esc_html( $first['title'] ); ?></h3>
                                <p class="text-brand-ink/70 text-sm md:text-base" data-curriculum-description><?php echo esc_html( $first['description'] ); ?></p>
                        </div>
                </div>
                <div>
                        <div class="bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 p-6">
                                <div class="relative h-[340px] md:h-[380px]">
                                        <canvas data-curriculum-chart aria-label="Curriculum focus radar chart" role="img"></canvas>
                                </div>
                        </div>
                </div>
        </div>
        <script type="application/json" data-curriculum-config><?php echo wp_json_encode( $profiles ); ?></script>
</section>

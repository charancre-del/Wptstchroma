<?php
/**
 * Template Part: Programs Wizard
 *
 * @package Chroma_Excellence
 */

$options = chroma_home_program_wizard_options();
$program_slug = chroma_get_program_base_slug();
$program_archive_url = chroma_get_program_archive_url();

if ( empty( $options ) ) {
	return;
}

// Helper to map specific colors to specific program keys (matching the HTML sample)
function chroma_get_wizard_color_classes( $key ) {
    $map = array(
        'infant' => 'bg-chroma-redLight border-chroma-red/30 hover:border-chroma-red',
        'toddler' => 'bg-white border-chroma-blue/20 hover:border-chroma-blue',
        'preschool' => 'bg-white border-chroma-yellow/20 hover:border-chroma-yellow',
        'prep' => 'bg-white border-chroma-blue/20 hover:border-chroma-blue',
        'prek' => 'bg-white border-chroma-blue/20 hover:border-chroma-blue',
        'afterschool' => 'bg-white border-chroma-green/20 hover:border-chroma-green',
    );

    // Default fallback
    return $map[ $key ] ?? 'bg-white border-chroma-blue/20 hover:border-chroma-blue';
}
?>

<section id="<?php echo esc_attr( $program_slug ); ?>" class="py-20 bg-brand-cream border-b border-chroma-blue/10" data-section="<?php echo esc_attr( $program_slug ); ?>">
    <div class="max-w-5xl mx-auto px-4 lg:px-6">

        <div class="text-center mb-10 fade-in-up">
            <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">Find the right program in 10 seconds</h2>
            <p class="text-brand-ink/70 text-sm md:text-base max-w-2xl mx-auto">Choose your child's age and we'll suggest the Chroma program designed for their development stage and your family's needs.</p>
        </div>

        <div class="bg-white rounded-3xl p-6 md:p-8 border border-chroma-blue/10 shadow-soft fade-in-up" data-program-wizard data-options='<?php echo esc_attr( wp_json_encode( $options ) ); ?>'>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4" data-program-wizard-options>
                <?php foreach ( $options as $option ) :
                    $key = $option['key'];
                    $color_classes = chroma_get_wizard_color_classes( $key );
                ?>
                    <button
                        class="p-4 rounded-2xl border hover:shadow-soft transition group text-center <?php echo esc_attr( $color_classes ); ?>"
                        data-program-wizard-option="<?php echo esc_attr( $key ); ?>"
                    >
                        <span class="text-2xl block mb-2 group-hover:scale-110 transition-transform"><?php echo esc_html( $option['emoji'] ); ?></span>
                        <span class="font-semibold text-brand-ink text-xs leading-tight"><?php echo wp_kses_post( nl2br( $option['label'] ) ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="hidden text-center pt-6 space-y-3" data-program-wizard-result>
                <h3 class="text-2xl font-serif font-bold text-brand-ink mb-2" data-program-wizard-title>Program Name</h3>
                <p class="text-brand-ink/70 max-w-xl mx-auto text-sm md:text-base" data-program-wizard-description>Description goes here.</p>

                <div class="flex flex-wrap justify-center gap-3 text-xs">
                    <a class="inline-flex items-center justify-center px-5 py-2 rounded-full border border-chroma-blue/20 bg-white text-brand-ink font-semibold hover:border-chroma-blue hover:text-chroma-blue transition"
                       data-program-wizard-link
                       href="<?php echo esc_url( $program_archive_url ); ?>">
                       Learn more about this program
                    </a>
                    <a href="#tour" class="inline-flex items-center justify-center px-5 py-2 rounded-full bg-chroma-red text-white font-semibold hover:bg-chroma-red/90 transition">
                        Speak to an enrollment specialist
                    </a>
                    <button type="button" class="text-brand-ink/50 hover:text-brand-ink underline decoration-dotted" data-program-wizard-reset>
                        Start Over
                    </button>
                </div>
            </div>

        </div>
    </div>
</section>

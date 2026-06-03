<?php
/**
 * Template Part: Programs Wizard
 *
 * @package Chroma_Excellence
 */

$options = chroma_home_program_wizard_options();
$wizard_content = chroma_home_program_wizard_content();
$program_slug = chroma_get_program_base_slug();
$program_archive_url = chroma_get_program_archive_url();
$default_option = $options[0] ?? array();
$default_option_label = sanitize_text_field($default_option['label'] ?? '');
$default_option_description = sanitize_textarea_field($default_option['description'] ?? '');
$default_option_link = !empty($default_option['link']) ? $default_option['link'] : $program_archive_url;
$default_option_image = !empty($default_option['image']) ? $default_option['image'] : '';

if (empty($options)) {
    return;
}

// Helper to map specific colors to specific program keys (matching the HTML sample)
function chroma_get_wizard_color_classes($key)
{
    $map = array(
        'infant-care' => 'bg-chroma-redLight border-chroma-red/30 text-brand-ink hover:border-chroma-red hover:text-chroma-red',
        'toddlers' => 'bg-white border-chroma-blue/20 text-brand-ink hover:border-chroma-blue hover:text-chroma-blue',
        'preschool' => 'bg-white border-chroma-yellow/20 text-brand-ink hover:border-chroma-yellow hover:text-chroma-yellow',
        'pre-k-prep' => 'bg-white border-chroma-blue/20 text-brand-ink hover:border-chroma-blue hover:text-chroma-blue',
        'pre-k-ga-pre-k' => 'bg-white border-chroma-blue/20 text-brand-ink hover:border-chroma-blue hover:text-chroma-blue',
        'schoolagers' => 'bg-white border-chroma-green/20 text-brand-ink hover:border-chroma-green hover:text-chroma-green',
        'camp' => 'bg-white border-chroma-orange/20 text-brand-ink hover:border-chroma-orange hover:text-chroma-orange',
        'parents-day-out' => 'bg-white border-chroma-teal/20 text-brand-ink hover:border-chroma-teal hover:text-chroma-teal',
        // Fallbacks for old keys just in case
        'infant' => 'bg-chroma-redLight border-chroma-red/30 text-brand-ink hover:border-chroma-red hover:text-chroma-red',
        'toddler' => 'bg-white border-chroma-blue/20 text-brand-ink hover:border-chroma-blue hover:text-chroma-blue',
        'prep' => 'bg-white border-chroma-blue/20 text-brand-ink hover:border-chroma-blue hover:text-chroma-blue',
        'prek' => 'bg-white border-chroma-blue/20 text-brand-ink hover:border-chroma-blue hover:text-chroma-blue',
        'afterschool' => 'bg-white border-chroma-green/20 text-brand-ink hover:border-chroma-green hover:text-chroma-green',
    );

    // Default fallback
    return $map[$key] ?? 'bg-white border-chroma-blue/20 text-brand-ink hover:border-chroma-blue hover:text-chroma-blue';
}
?>

<section id="<?php echo esc_attr($program_slug); ?>" class="py-20 bg-brand-cream border-b border-chroma-blue/10"
    data-section="<?php echo esc_attr($program_slug); ?>">
    <div class="max-w-5xl mx-auto px-4 lg:px-6">

        <div class="text-center mb-10 fade-in-up">
            <h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">
                <?php echo esc_html($wizard_content['heading']); ?></h2>
            <p class="text-brand-ink text-sm md:text-base max-w-2xl mx-auto">
                <?php echo esc_html($wizard_content['subheading']); ?>
            </p>
        </div>

        <div class="bg-white rounded-3xl p-6 md:p-8 border border-chroma-blue/10 shadow-soft fade-in-up"
            data-program-wizard>
            <script type="application/json" data-program-wizard-payload>
                <?php echo wp_json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
            </script>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4" data-program-wizard-options>
                <?php foreach ($options as $option):
                    $key = $option['key'];
                    $color_classes = chroma_get_wizard_color_classes($key);
                    ?>
                    <button
                        class="p-4 rounded-2xl border hover:shadow-soft transition group text-center <?php echo esc_attr($color_classes); ?>"
                        data-program-wizard-option="<?php echo esc_attr($key); ?>">
                        <span
                            class="text-2xl block mb-2 group-hover:scale-110 transition-transform"><?php echo esc_html($option['emoji']); ?></span>
                        <span
                            class="font-semibold text-xs leading-tight"><?php echo wp_kses_post(nl2br($option['label'])); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="hidden pt-6 opacity-0 translate-y-4 transition-all duration-500 ease-out"
                data-program-wizard-result>

                <div class="grid md:grid-cols-2 gap-8 items-center">
                    <!-- Text Content (Left) -->
                    <div class="text-center md:text-left space-y-4 order-2 md:order-1">
                        <h3 class="text-2xl md:text-3xl font-serif font-bold text-brand-ink" data-program-wizard-title>
                            <?php echo esc_html($default_option_label); ?></h3>
                        <p class="text-brand-ink text-sm md:text-base leading-relaxed" data-program-wizard-description>
                            <?php echo esc_html($default_option_description); ?></p>

                        <div class="flex flex-wrap gap-3 text-xs pt-2 justify-center md:justify-start">
                            <a class="inline-flex items-center justify-center px-6 py-3 rounded-full border border-chroma-blue/20 bg-white text-brand-ink font-semibold hover:border-chroma-blue hover:text-chroma-blue transition shadow-sm"
                                data-program-wizard-link href="<?php echo esc_url($default_option_link); ?>"
                                aria-label="<?php echo esc_attr($wizard_content['primary_cta_aria_label']); ?>">
                                <?php echo esc_html($wizard_content['primary_cta_label']); ?>
                            </a>
                            <a href="#tour"
                                class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-chroma-red text-white font-semibold hover:bg-chroma-red/90 transition shadow-soft">
                                <?php echo esc_html($wizard_content['secondary_cta_label']); ?>
                            </a>

                        </div>
                    </div>

                    <!-- Image (Right) -->
                    <div
                        class="order-1 md:order-2 relative w-full aspect-video md:aspect-[4/3] rounded-2xl overflow-hidden shadow-card border-4 border-white transform rotate-2 transition-transform duration-700 hover:rotate-0 bg-brand-cream/50">
                        <img src="<?php echo esc_url($default_option_image); ?>"
                            alt="<?php echo esc_attr($wizard_content['image_alt']); ?>"
                            class="w-full h-full object-cover" width="640" height="480" data-program-wizard-image />
                    </div>
                </div>

                <!-- Start Over (Centered Below) -->
                <div class="text-center mt-8 w-full">
                    <button type="button"
                        class="text-brand-ink hover:text-chroma-blue underline decoration-dotted text-sm transition-colors"
                        data-program-wizard-reset>
                        <?php echo esc_html($wizard_content['reset_label']); ?>
                    </button>
                </div>

            </div>

        </div>
    </div>
</section>

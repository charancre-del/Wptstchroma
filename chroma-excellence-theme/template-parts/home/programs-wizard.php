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
$curriculum_profiles = chroma_home_curriculum_profiles();
$pillar_labels = $curriculum_profiles['labels'] ?? array(
    __('Physical', 'chroma-excellence'),
    __('Emotional', 'chroma-excellence'),
    __('Social', 'chroma-excellence'),
    __('Academic', 'chroma-excellence'),
    __('Creative', 'chroma-excellence'),
);
$profiles_by_key = array();

foreach (($curriculum_profiles['profiles'] ?? array()) as $profile) {
    if (!empty($profile['key'])) {
        $profiles_by_key[$profile['key']] = $profile;
    }
}

if (empty($options)) {
    return;
}

$fallback_profile = array(
    'title' => __('A balanced PrismPath™ day', 'chroma-excellence'),
    'description' => __('The chart shows how PrismPath™ balances physical, emotional, social, academic, and creative development for each program.', 'chroma-excellence'),
    'color' => '#4A6C7C',
    'data' => array(68, 72, 70, 66, 74),
);

$enhanced_options = array_map(
    function ($option) use ($profiles_by_key, $fallback_profile) {
        $key = sanitize_title($option['key'] ?? '');
        $label = sanitize_text_field($option['label'] ?? '');
        $age_label = sanitize_text_field($option['age_label'] ?? '');
        $program_title = sanitize_text_field($option['program_title'] ?? '') ?: $label;

        if (!$age_label && preg_match('/\s*\((.*)\)\s*$/', $label, $matches)) {
            $age_label = sanitize_text_field($matches[1]);
            $program_title = trim((string) preg_replace('/\s*\([^)]+\)\s*$/', '', $label));
        }

        $profile = $profiles_by_key[$key] ?? $fallback_profile;

        return array_merge(
            $option,
            array(
                'key' => $key,
                'program_title' => $program_title,
                'age_label' => $age_label,
                'prism_title' => sanitize_text_field($profile['title'] ?? $fallback_profile['title']),
                'prism_description' => sanitize_textarea_field($profile['description'] ?? $fallback_profile['description']),
                'prism_color' => sanitize_hex_color($profile['color'] ?? '') ?: $fallback_profile['color'],
                'prism_data' => array_map('intval', $profile['data'] ?? $fallback_profile['data']),
            )
        );
    },
    $options
);

$default_index = 0;
foreach ($enhanced_options as $index => $option) {
    if ('preschool' === ($option['key'] ?? '')) {
        $default_index = $index;
        break;
    }
}

$default_option = $enhanced_options[$default_index] ?? ($enhanced_options[0] ?? array());
$default_option_link = !empty($default_option['link']) ? $default_option['link'] : $program_archive_url;
$default_option_image = !empty($default_option['image']) ? $default_option['image'] : '';
?>

<section id="<?php echo esc_attr($program_slug); ?>" class="chroma-programs-showcase py-20 md:py-24 bg-brand-cream border-b border-chroma-blue/10"
    data-section="<?php echo esc_attr($program_slug); ?>">
    <div class="max-w-7xl mx-auto px-4 lg:px-6">

        <div class="text-center mb-10 fade-in-up">
            <h2 class="font-serif text-4xl md:text-6xl font-semibold tracking-[-0.035em] text-brand-ink mb-4">
                <?php echo esc_html($wizard_content['heading']); ?></h2>
            <p class="text-brand-ink/70 text-base md:text-lg leading-relaxed max-w-3xl mx-auto">
                <?php echo esc_html($wizard_content['subheading']); ?>
            </p>
        </div>

        <div class="chroma-program-shell bg-white/80 rounded-[2rem] border border-chroma-blue/10 shadow-soft p-4 md:p-6 lg:p-7 fade-in-up"
            data-program-wizard style="--program-accent: <?php echo esc_attr($default_option['prism_color'] ?? '#4A6C7C'); ?>">
            <script type="application/json" data-program-wizard-payload>
                <?php echo wp_json_encode($enhanced_options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
            </script>
            <div class="chroma-program-shell-grid grid lg:grid-cols-[330px_minmax(0,1fr)] gap-6 lg:gap-7">
                <div class="chroma-program-tabs grid gap-3 content-start max-h-[46rem] overflow-y-auto pr-1" data-program-wizard-options>
                    <?php foreach ($enhanced_options as $index => $option): ?>
                        <?php $is_default = $index === $default_index; ?>
                        <button
                            class="chroma-program-tab rounded-[1.25rem] border px-5 py-4 text-left transition <?php echo $is_default ? 'is-active' : ''; ?>"
                            type="button"
                            data-program-wizard-option="<?php echo esc_attr($option['key']); ?>"
                            data-program-tab="<?php echo esc_attr($option['key']); ?>"
                            aria-pressed="<?php echo $is_default ? 'true' : 'false'; ?>">
                            <strong class="block text-sm md:text-base font-bold text-current leading-tight">
                                <?php echo esc_html($option['program_title']); ?>
                            </strong>
                            <?php if (!empty($option['age_label'])): ?>
                                <span class="block text-xs text-current/65 mt-1">
                                    <?php echo esc_html($option['age_label']); ?>
                                </span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <article class="chroma-program-card bg-white rounded-[2rem] border border-chroma-blue/10 shadow-card"
                    data-program-wizard-result>
                    <div class="p-7 md:p-9 lg:p-10">
                        <?php if (!empty($default_option['age_label'])): ?>
                            <div class="chroma-program-age inline-flex rounded-full px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] mb-6"
                                data-program-wizard-age>
                                <?php echo esc_html($default_option['age_label']); ?>
                            </div>
                        <?php else: ?>
                            <div class="chroma-program-age inline-flex rounded-full px-4 py-2 text-xs font-bold uppercase tracking-[0.16em] mb-6 hidden"
                                data-program-wizard-age></div>
                        <?php endif; ?>

                        <h3 class="chroma-program-title font-serif text-5xl md:text-6xl lg:text-7xl font-semibold tracking-[-0.04em] leading-[0.95] text-brand-ink mb-5"
                            data-program-wizard-title
                            data-program-title>
                            <?php echo esc_html($default_option['program_title'] ?? ''); ?>
                        </h3>

                        <p class="text-brand-ink/70 text-lg leading-relaxed max-w-2xl mb-8" data-program-wizard-description>
                            <?php echo esc_html($default_option['description'] ?? ''); ?>
                        </p>

                    <div class="flex flex-wrap gap-3">
                        <a class="inline-flex items-center justify-center px-7 py-4 rounded-full bg-brand-ink text-white text-xs font-bold uppercase tracking-[0.18em] hover:bg-chroma-blueDark transition shadow-soft"
                            data-program-wizard-link href="<?php echo esc_url($default_option_link); ?>"
                            aria-label="<?php echo esc_attr($wizard_content['primary_cta_aria_label']); ?>">
                            <?php echo esc_html($wizard_content['primary_cta_label']); ?>
                        </a>
                    </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>

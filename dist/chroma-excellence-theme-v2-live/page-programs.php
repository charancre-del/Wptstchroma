<?php
/**
 * Template Name: Programs
 * Displays all programs in a categorized grid.
 *
 * @package Chroma_Excellence
 */

get_header();

$data_service = Chroma_Data_Service::get_instance();
$programs = $data_service->get_programs();

$is_flexible_program = static function ($program) {
    $title = strtolower(get_the_title($program->ID));

    foreach (array('camp', 'kindergarten', 'rising', 'parent', 'therapy', 'early learning') as $keyword) {
        if (strpos($title, $keyword) !== false) {
            return true;
        }
    }

    return false;
};

$core_programs = array();
$flexible_programs = array();

foreach ($programs as $program) {
    if ($is_flexible_program($program)) {
        $flexible_programs[] = $program;
    } else {
        $core_programs[] = $program;
    }
}

$color_map = array(
    'red' => '#A84B38',
    'blue' => '#4A6C7C',
    'yellow' => '#A77B24',
    'blueDark' => '#2F4858',
    'green' => '#4A7C59',
    'orange' => '#C26524',
    'teal' => '#248EC2',
);
?>

<main id="primary" class="site-main">
    <section class="chroma-v2-page-hero py-16 md:py-20 bg-brand-cream overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 lg:px-6">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.14em] text-brand-ink/50 mb-7">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-chroma-red transition"><?php esc_html_e('Home', 'chroma-excellence'); ?></a>
                <span aria-hidden="true">·</span>
                <span><?php esc_html_e('Programs', 'chroma-excellence'); ?></span>
            </div>

            <div class="max-w-4xl">
                <div
                    class="inline-flex items-center gap-2 bg-white border border-chroma-red/20 px-4 py-2 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-chroma-red shadow-sm mb-7">
                    <span class="w-2 h-2 rounded-full bg-chroma-red" aria-hidden="true"></span>
                    <?php esc_html_e('Ages 6 weeks to 12 years', 'chroma-excellence'); ?>
                </div>

                <h1 class="font-serif text-5xl md:text-7xl lg:text-8xl font-semibold tracking-[-0.045em] leading-[0.94] text-brand-ink mb-7">
                    <?php esc_html_e('Programs that', 'chroma-excellence'); ?>
                    <em class="block md:inline text-chroma-red"><?php esc_html_e('grow with them.', 'chroma-excellence'); ?></em>
                </h1>
                <p class="text-lg md:text-xl text-brand-ink/75 leading-relaxed max-w-3xl">
                    <?php esc_html_e('From sensory discovery in our infant suites to project-based Pre-K, every program uses Prismpath™ to meet children exactly where they are.', 'chroma-excellence'); ?>
                </p>
            </div>
        </div>
    </section>

    <section id="all-programs" class="white borderY py-20 md:py-24 bg-white border-y border-chroma-blue/10">
        <div class="max-w-7xl mx-auto px-4 lg:px-6">
            <?php if (!empty($core_programs)): ?>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-7">
                    <?php foreach ($core_programs as $index => $program):
                        $program_id = $program->ID;
                        $program_title = get_the_title($program_id);
                        $program_permalink = get_permalink($program_id);
                        $age_range = $data_service->get_translated_meta($program_id, 'program_age_range');
                        $features = $data_service->get_translated_meta($program_id, 'program_features');
                        $features_array = $features ? array_values(array_filter(array_map('trim', explode("\n", $features)))) : array();
                        $color_scheme = $data_service->get_meta($program_id, 'program_color_scheme', 'red');
                        $accent = $color_map[$color_scheme] ?? $color_map['red'];
                        $thumbnail_url = get_the_post_thumbnail_url($program_id, 'large');
                        $program_excerpt = has_excerpt($program_id)
                            ? get_the_excerpt($program_id)
                            : wp_trim_words(wp_strip_all_tags((string) get_post_field('post_content', $program_id)), 24);
                        ?>

                        <a href="<?php echo esc_url($program_permalink); ?>"
                            class="chroma-template-card group bg-white rounded-[2rem] border border-chroma-blue/10 shadow-soft overflow-hidden flex flex-col"
                            style="--card-accent: <?php echo esc_attr($accent); ?>">
                            <?php if ($thumbnail_url): ?>
                                <div class="chroma-template-card-image aspect-[3/2] overflow-hidden bg-brand-cream">
                                    <img src="<?php echo esc_url($thumbnail_url); ?>"
                                        alt="<?php echo esc_attr($program_title); ?>"
                                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                        loading="<?php echo $index < 3 ? 'eager' : 'lazy'; ?>"
                                        width="640"
                                        height="430">
                                </div>
                            <?php endif; ?>
                            <div class="p-7 flex flex-col gap-4 flex-1">
                                <?php if ($age_range): ?>
                                    <div class="text-xs font-bold uppercase tracking-[0.14em] text-brand-ink/50">
                                        <?php echo esc_html($age_range); ?>
                                    </div>
                                <?php endif; ?>

                                <h3 class="font-serif text-3xl font-semibold tracking-[-0.025em] leading-none text-brand-ink">
                                    <?php echo esc_html($program_title); ?>
                                </h3>

                                <p class="text-brand-ink/70 text-base leading-relaxed">
                                    <?php echo esc_html($program_excerpt); ?>
                                </p>

                                <?php if (!empty($features_array)): ?>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach (array_slice($features_array, 0, 3) as $feature): ?>
                                            <span class="chroma-card-tag">
                                                <?php echo esc_html($feature); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <span class="chroma-card-link mt-auto">
                                    <?php esc_html_e('Learn More', 'chroma-excellence'); ?>
                                    <span aria-hidden="true">→</span>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-20 bg-brand-cream rounded-[2rem] border border-chroma-blue/10">
                    <p class="text-brand-ink/80 text-lg">
                        <?php esc_html_e('No programs found. Please add programs from the WordPress admin.', 'chroma-excellence'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>


    <section id="curriculum" class="white borderY chroma-prism-stack py-20 md:py-24 bg-white border-y border-chroma-blue/10">
        <div class="max-w-7xl mx-auto px-4 lg:px-6 grid lg:grid-cols-[0.95fr_1.05fr] gap-10 items-center">
            <div>
                <div class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-4">
                    <?php esc_html_e('The Prismpath™ Model', 'chroma-excellence'); ?>
                </div>
                <h2 class="font-serif text-4xl md:text-6xl font-semibold tracking-[-0.04em] leading-[0.98] text-brand-ink mb-6">
                    <?php esc_html_e('Five pillars.', 'chroma-excellence'); ?>
                    <em class="block md:inline text-chroma-red"><?php esc_html_e('One prism.', 'chroma-excellence'); ?></em>
                </h2>
                <p class="text-brand-ink/75 text-lg leading-relaxed mb-8">
                    <?php esc_html_e('Every age group gets a different blend of physical, emotional, social, academic, and creative development — so the day feels joyful while the learning stays intentional.', 'chroma-excellence'); ?>
                </p>
                <a href="<?php echo esc_url(home_url('/curriculum/')); ?>"
                    class="inline-flex items-center justify-center px-7 py-4 rounded-full bg-brand-ink text-white text-xs font-bold uppercase tracking-[0.18em] hover:bg-chroma-blueDark transition shadow-soft">
                    <?php esc_html_e('Explore Prismpath™', 'chroma-excellence'); ?>
                </a>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <?php
                $pillars = array(
                    array(__('Physical', 'chroma-excellence'), __('Movement, motor planning, and sensory confidence.', 'chroma-excellence'), '#7D5BA6'),
                    array(__('Emotional', 'chroma-excellence'), __('Attachment, regulation, trust, and resilience.', 'chroma-excellence'), '#4A6C7C'),
                    array(__('Social', 'chroma-excellence'), __('Friendship, communication, empathy, and belonging.', 'chroma-excellence'), '#4A7C59'),
                    array(__('Academic', 'chroma-excellence'), __('Literacy, numeracy, curiosity, and school readiness.', 'chroma-excellence'), '#A77B24'),
                    array(__('Creative', 'chroma-excellence'), __('Art, music, dramatic play, and expression.', 'chroma-excellence'), '#A84B38'),
                );
                foreach ($pillars as $pillar): ?>
                    <article class="bg-white/90 rounded-[1.5rem] border border-chroma-blue/10 p-6 shadow-soft"
                        style="--prism-color: <?php echo esc_attr($pillar[2]); ?>">
                        <div class="chroma-prism-orb !w-12 !h-12 mb-5"></div>
                        <h3 class="font-serif text-2xl font-semibold text-brand-ink mb-2"><?php echo esc_html($pillar[0]); ?></h3>
                        <p class="text-brand-ink/70 text-sm leading-relaxed"><?php echo esc_html($pillar[1]); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <section class="py-20 bg-brand-cream">
        <div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
            <h2 class="font-serif text-4xl md:text-5xl font-semibold tracking-[-0.035em] text-brand-ink mb-5">
                <?php esc_html_e('Ready to find your fit?', 'chroma-excellence'); ?>
            </h2>
            <p class="text-brand-ink/75 text-lg leading-relaxed mb-9">
                <?php esc_html_e('Tour a campus, meet the teachers, and we’ll help match your child with the right room and rhythm.', 'chroma-excellence'); ?>
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="<?php echo esc_url(home_url('/locations/')); ?>"
                    class="inline-flex items-center justify-center px-7 py-4 rounded-full border border-chroma-blue/20 bg-white text-brand-ink text-xs font-bold uppercase tracking-[0.18em] hover:border-chroma-blue hover:text-chroma-blue transition">
                    <?php esc_html_e('Find a Location', 'chroma-excellence'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/schedule-a-tour/')); ?>"
                    class="inline-flex items-center justify-center px-7 py-4 rounded-full bg-chroma-red text-white text-xs font-bold uppercase tracking-[0.18em] hover:bg-chroma-red/90 transition shadow-soft">
                    <?php esc_html_e('Schedule a Tour', 'chroma-excellence'); ?>
                </a>
            </div>
        </div>
    </section>
</main>

<?php
get_footer();

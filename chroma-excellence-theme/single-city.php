<?php
/**
 * Single City Template
 *
 * City pages are visitor-facing service-area guides. They remain noindex until
 * a page receives explicit search approval through city_search_approved.
 *
 * @package Chroma_Excellence
 */

get_header();

$city_id = get_the_ID();
$city = get_the_title($city_id);
$state = chroma_get_translated_meta($city_id, 'city_state') ?: 'ga';
$state_upper = strtoupper((string) $state);
$neighborhoods = chroma_get_translated_meta($city_id, 'city_neighborhoods');
$location_ids = chroma_get_translated_meta($city_id, 'city_nearby_locations');
$intro_text = chroma_get_translated_meta($city_id, 'city_intro_text');

if (empty($location_ids)) {
    $location_ids = chroma_get_translated_meta($city_id, 'related_location_ids');
}

$location_ids = array_values(array_filter(array_map('absint', (array) $location_ids)));
$local_fallback = get_template_directory_uri() . '/assets/images/logo_chromacropped_140x140.webp';
?>

<main class="city-template-v2">
    <section class="pageHero chroma-v2-page-hero relative pt-12 pb-16 bg-white overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-chroma-blue/5 rounded-full blur-3xl translate-x-1/2 -translate-y-1/2"></div>

        <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
            <span class="inline-block py-1 px-4 rounded-full bg-chroma-blue/10 text-chroma-blue text-xs font-bold uppercase tracking-widest mb-6">
                <?php printf(esc_html__('Serving Families From %s', 'chroma-excellence'), esc_html($city)); ?>
            </span>

            <h1 class="font-serif text-4xl md:text-6xl text-brand-ink mb-6 leading-tight">
                <?php
                printf(
                    wp_kses_post(__('Chroma Campuses Near <span class="italic text-chroma-blue">%1$s, %2$s.</span>', 'chroma-excellence')),
                    esc_html($city),
                    esc_html($state_upper)
                );
                ?>
            </h1>

            <p class="text-lg md:text-xl text-brand-ink/80 max-w-2xl mx-auto mb-10">
                <?php
                printf(
                    esc_html__('Explore Chroma Early Learning Academy campuses that serve families from %s. Program availability, transportation, and enrollment details vary by campus.', 'chroma-excellence'),
                    esc_html($city)
                );
                ?>
            </p>

            <a href="#locations" class="inline-flex items-center gap-2 text-chroma-red font-bold border-b-2 border-chroma-red pb-1 hover:text-brand-ink hover:border-brand-ink transition-all">
                <?php esc_html_e('Explore Nearby Campuses', 'chroma-excellence'); ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </a>
        </div>
    </section>

    <section class="py-20 bg-brand-cream border-y border-brand-ink/5">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="font-serif text-3xl md:text-5xl text-brand-ink mb-8 leading-tight">
                <?php
                printf(
                    wp_kses_post(__('A Local Starting Point for <br><span class="text-chroma-blue">%s Families</span>', 'chroma-excellence')),
                    esc_html($city)
                );
                ?>
            </h2>

            <?php if (!empty($intro_text)): ?>
                <div class="text-lg md:text-xl text-brand-ink/80 leading-relaxed max-w-3xl mx-auto">
                    <?php echo wp_kses_post($intro_text); ?>
                </div>
            <?php else: ?>
                <p class="text-lg md:text-xl text-brand-ink/80 leading-relaxed max-w-3xl mx-auto">
                    <?php esc_html_e('Chroma combines warm, responsive care with purposeful play and the connected PrismPath™ curriculum.', 'chroma-excellence'); ?>
                    <br><br>
                    <?php
                    printf(
                        esc_html__('Use the campus links below to compare verified addresses, contact details, and current program information for locations serving families from %s.', 'chroma-excellence'),
                        esc_html($city)
                    );
                    ?>
                </p>
            <?php endif; ?>
        </div>
    </section>

    <section id="locations" class="py-20 bg-white scroll-mt-24">
        <div class="max-w-7xl mx-auto px-4 lg:px-6">
            <div class="text-center mb-12">
                <h2 class="font-serif text-2xl md:text-3xl font-bold text-brand-ink">
                    <?php printf(esc_html__('Chroma Locations Serving %s', 'chroma-excellence'), esc_html($city)); ?>
                </h2>
                <p class="text-brand-ink/60 mt-3">
                    <?php esc_html_e('Compare nearby campuses, then confirm current programs and openings directly with the campus team.', 'chroma-excellence'); ?>
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php if (!empty($location_ids)): ?>
                    <?php
                    $locations_query = new WP_Query([
                        'post_type' => 'location',
                        'post__in' => array_slice($location_ids, 0, 50),
                        'orderby' => 'post__in',
                        'posts_per_page' => 50,
                        'post_status' => 'publish',
                        'no_found_rows' => true,
                        'update_post_meta_cache' => true,
                        'update_post_term_cache' => false,
                    ]);
                    ?>

                    <?php while ($locations_query->have_posts()): $locations_query->the_post(); ?>
                        <?php
                        $location_id = get_the_ID();
                        $address = trim((string) get_post_meta($location_id, 'location_address', true));
                        $location_city = trim((string) get_post_meta($location_id, 'location_city', true));
                        $rating = trim((string) get_post_meta($location_id, 'location_google_rating', true));
                        $image = get_the_post_thumbnail_url($location_id, 'medium_large');

                        if ($address === '' && $location_city !== '') {
                            $address = $location_city;
                        }

                        if (!$image) {
                            $gallery = trim((string) get_post_meta($location_id, 'location_hero_gallery', true));
                            if ($gallery !== '') {
                                $gallery_lines = preg_split('/\R/', $gallery) ?: [];
                                $image = trim((string) ($gallery_lines[0] ?? ''));
                            }
                        }

                        if (!$image) {
                            $image = $local_fallback;
                        }
                        ?>

                        <article class="group p-6 rounded-3xl bg-brand-cream border border-brand-ink/5 hover:border-chroma-blue/30 transition-all hover:-translate-y-1 flex flex-col shadow-card">
                            <div class="h-48 rounded-2xl bg-chroma-blue/5 mb-6 overflow-hidden relative">
                                <?php if ($image !== $local_fallback): ?>
                                    <img src="<?php echo esc_url($image); ?>" class="w-full h-full object-cover" alt="<?php the_title_attribute(); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="absolute inset-0 bg-gradient-to-br from-chroma-blue/20 to-chroma-green/20 flex items-center justify-center">
                                        <img src="<?php echo esc_url($local_fallback); ?>" alt="" class="w-16 h-16 opacity-30" loading="lazy">
                                    </div>
                                <?php endif; ?>

                                <?php if ($rating !== ''): ?>
                                    <div class="absolute top-4 right-4 bg-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide shadow-sm">
                                        <?php echo esc_html($rating); ?> ★
                                    </div>
                                <?php endif; ?>
                            </div>

                            <h3 class="font-serif text-xl font-bold text-brand-ink mb-2"><?php the_title(); ?></h3>
                            <?php if ($address !== ''): ?>
                                <p class="text-sm text-brand-ink/60 mb-1"><?php echo esc_html($address); ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-brand-ink font-bold uppercase tracking-widest mb-6">
                                <?php printf(esc_html__('Serving %s Families', 'chroma-excellence'), esc_html($city)); ?>
                            </p>
                            <div class="mt-auto">
                                <a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(sprintf(__('View Campus: %s', 'chroma-excellence'), get_the_title())); ?>" class="block w-full py-3 bg-chroma-blue text-white text-center rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-chroma-blue/90 transition-colors">
                                    <?php esc_html_e('View Campus', 'chroma-excellence'); ?>
                                </a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-12">
                        <p class="text-brand-ink/60">
                            <?php esc_html_e('No campus has been verified for this city page. Use the full location directory to find the nearest Chroma campus.', 'chroma-excellence'); ?>
                        </p>
                        <a href="<?php echo esc_url(home_url('/locations/')); ?>" class="inline-block mt-4 text-chroma-blue font-semibold hover:underline">
                            <?php esc_html_e('View All Locations →', 'chroma-excellence'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($neighborhoods) && is_array($neighborhoods)): ?>
                <div class="mt-12 text-center">
                    <p class="text-brand-ink/60 text-sm">
                        <strong><?php esc_html_e('Families also visit from:', 'chroma-excellence'); ?></strong><br>
                        <?php echo esc_html(implode(', ', $neighborhoods)); ?>.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="programs" class="py-20 bg-brand-cream scroll-mt-24">
        <div class="max-w-7xl mx-auto px-4 lg:px-6">
            <div class="text-center mb-12">
                <h2 class="font-serif text-2xl md:text-3xl font-bold text-brand-ink"><?php esc_html_e('Explore Chroma Programs', 'chroma-excellence'); ?></h2>
                <p class="text-brand-ink/60 mt-3"><?php esc_html_e('Review each program, then confirm current availability with your preferred campus.', 'chroma-excellence'); ?></p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                $programs_query = new WP_Query([
                    'post_type' => 'program',
                    'posts_per_page' => -1,
                    'orderby' => 'menu_order',
                    'order' => 'ASC',
                    'post_status' => 'publish',
                    'no_found_rows' => true,
                    'update_post_meta_cache' => true,
                    'update_post_term_cache' => false,
                ]);
                ?>
                <?php while ($programs_query->have_posts()): $programs_query->the_post(); ?>
                    <?php $age_range = trim((string) get_post_meta(get_the_ID(), 'program_age_range', true)); ?>
                    <article class="group p-6 rounded-3xl bg-white border border-brand-ink/5 hover:border-chroma-blue/30 transition-all hover:-translate-y-1 flex flex-col shadow-card">
                        <div class="h-48 rounded-2xl bg-chroma-blue/5 mb-6 overflow-hidden relative">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('medium_large', ['class' => 'w-full h-full object-cover', 'loading' => 'lazy']); ?>
                            <?php else: ?>
                                <div class="absolute inset-0 bg-gradient-to-br from-chroma-blue/20 to-chroma-green/20"></div>
                            <?php endif; ?>
                        </div>
                        <h3 class="font-serif text-xl font-bold text-brand-ink mb-2"><?php the_title(); ?></h3>
                        <?php if ($age_range !== ''): ?>
                            <p class="text-xs text-brand-ink/60 font-bold uppercase tracking-widest mb-6">
                                <?php printf(esc_html__('Ages %s', 'chroma-excellence'), esc_html($age_range)); ?>
                            </p>
                        <?php endif; ?>
                        <div class="mt-auto">
                            <a href="<?php the_permalink(); ?>" class="block w-full py-3 bg-chroma-blue text-white text-center rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-chroma-blue/90 transition-colors">
                                <?php esc_html_e('View Program', 'chroma-excellence'); ?>
                            </a>
                        </div>
                    </article>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </div>
    </section>

    <section class="py-20 bg-white border-t border-brand-ink/5">
        <div class="max-w-4xl mx-auto px-4">
            <h2 class="font-serif text-2xl md:text-3xl font-bold text-brand-ink mb-10 text-center">
                <?php printf(esc_html__('Questions From %s Families', 'chroma-excellence'), esc_html($city)); ?>
            </h2>

            <div class="space-y-4">
                <?php
                $faqs = [
                    [
                        sprintf(__('Is Georgia Pre-K available near %s?', 'chroma-excellence'), $city),
                        sprintf(__('Georgia Pre-K is offered at many Chroma campuses. Contact a campus serving %s to confirm current availability, eligibility, and enrollment details.', 'chroma-excellence'), $city),
                    ],
                    [
                        sprintf(__('Is school transportation available near %s?', 'chroma-excellence'), $city),
                        __('Transportation routes are campus-specific and may change. Contact your preferred campus for the current school list, route details, and eligibility.', 'chroma-excellence'),
                    ],
                    [
                        sprintf(__('What ages do Chroma campuses serving %s accept?', 'chroma-excellence'), $city),
                        sprintf(__('Chroma offers programs spanning infancy through school age across its network. Age ranges and programs vary by campus. Review the <a href="%s" class="text-chroma-blue hover:underline">program directory</a> and confirm availability with your preferred location.', 'chroma-excellence'), esc_url(home_url('/programs/'))),
                    ],
                    [
                        sprintf(__('How do I begin enrollment from %s?', 'chroma-excellence'), $city),
                        __('Start by choosing a nearby campus and scheduling a tour. The campus team can explain current openings, enrollment steps, and the programs that fit your child’s age and needs.', 'chroma-excellence'),
                    ],
                ];
                ?>

                <?php foreach ($faqs as $faq): ?>
                    <details class="group bg-brand-cream rounded-2xl p-6 shadow-sm border border-brand-ink/5">
                        <summary class="flex items-center justify-between font-bold text-brand-ink list-none cursor-pointer">
                            <span><?php echo esc_html($faq[0]); ?></span>
                            <span class="text-chroma-blue" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-4 text-sm text-brand-ink/80"><?php echo wp_kses_post($faq[1]); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <div class="py-8 bg-white text-center">
        <a href="<?php echo esc_url(get_post_type_archive_link('city')); ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-ink/60 hover:text-chroma-blue transition-colors">
            <span aria-hidden="true">←</span>
            <?php esc_html_e('Back to All Communities', 'chroma-excellence'); ?>
        </a>
    </div>
</main>

<?php get_footer(); ?>

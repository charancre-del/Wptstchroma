<?php
/**
 * Single City Template
 * Hyperlocal landing page for a specific city
 *
 * @package Chroma_Excellence
 */

get_header();

// Get city data
$city = get_the_title();
$city_slug = get_post_field('post_name');
$state = chroma_get_translated_meta(get_the_ID(), 'city_state') ?: 'ga';
$state_upper = strtoupper($state);
$county = chroma_get_translated_meta(get_the_ID(), 'city_county') ?: 'Local';
$neighborhoods = chroma_get_translated_meta(get_the_ID(), 'city_neighborhoods');
$location_ids = chroma_get_translated_meta(get_the_ID(), 'city_nearby_locations');
$intro_text = chroma_get_translated_meta(get_the_ID(), 'city_intro_text');

// Fallback for related locations (try alternative meta key)
if (empty($location_ids)) {
    $location_ids = chroma_get_translated_meta(get_the_ID(), 'related_location_ids');
}

$location_count = is_array($location_ids) ? count($location_ids) : 0;

// Local fallback image
$local_fallback = get_template_directory_uri() . '/assets/images/logo_chromacropped_140x140.webp';
?>

<!-- SEO Hero: High Intent Keywords -->
<section class="relative pt-12 pb-16 bg-white overflow-hidden">
    <div
        class="absolute top-0 right-0 w-96 h-96 bg-chroma-blue/5 rounded-full blur-3xl translate-x-1/2 -translate-y-1/2">
    </div>

    <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
        <span
            class="inline-block py-1 px-4 rounded-full bg-chroma-blue/10 text-chroma-blue text-xs font-bold uppercase tracking-widest mb-6">
            <?php printf(__('Serving %s & %s County', 'chroma-excellence'), esc_html($city), esc_html($county)); ?>
        </span>

        <h1 class="font-serif text-4xl md:text-6xl text-brand-ink mb-6 leading-tight">
            <?php printf(__('The Best Daycare in <span class="italic text-chroma-blue">%s, %s.</span>', 'chroma-excellence'), esc_html($city), esc_html($state_upper)); ?>
        </h1>

        <p class="text-lg md:text-xl text-brand-ink/80 max-w-2xl mx-auto mb-10">
            <?php printf(__('Are you looking for "daycare near me"? Discover the highest-rated early learning centers in the %s area, featuring the Prismpath™ curriculum and GA Pre-K.', 'chroma-excellence'), esc_html($city)); ?>
        </p>

        <a href="#locations"
            class="inline-flex items-center gap-2 text-chroma-red font-bold border-b-2 border-chroma-red pb-1 hover:text-brand-ink hover:border-brand-ink transition-all">
            <?php printf(__('See Locations in %s', 'chroma-excellence'), esc_html($city)); ?>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3">
                </path>
            </svg>
        </a>
    </div>
</section>

<!-- SEO Content Block -->
<section class="py-20 bg-brand-cream border-y border-brand-ink/5">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="font-serif text-3xl md:text-5xl text-brand-ink mb-8 leading-tight">
            <?php printf(__('Early Education and <br> Care in <span class="text-chroma-blue">%s, GA</span>', 'chroma-excellence'), esc_html($city)); ?>
        </h2>

        <?php if ($intro_text): ?>
            <div class="text-lg md:text-xl text-brand-ink/80 leading-relaxed max-w-3xl mx-auto">
                <?php echo wp_kses_post($intro_text); ?>
            </div>
        <?php else: ?>
            <p class="text-lg md:text-xl text-brand-ink/80 leading-relaxed max-w-3xl mx-auto">
                <?php _e('Our school is more than a daycare. Through purposeful play and nurturing guidance, we help lay the foundation for a lifelong love of learning.', 'chroma-excellence'); ?>
                <br><br>
                <?php printf(__('Conveniently located near major highways and down the road from local landmarks and top-rated elementary schools, we are the convenient choice for %s working parents. Come by and see Prismpath™ in action at one of our nearby campuses.', 'chroma-excellence'), esc_html($city)); ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<!-- Locations Grid -->
<section id="locations" class="py-20 bg-white scroll-mt-24">
    <div class="max-w-7xl mx-auto px-4 lg:px-6">
        <div class="text-center mb-12">
            <h2 class="font-serif text-2xl md:text-3xl font-bold text-brand-ink">
                <?php printf(__('Chroma Locations Serving %s', 'chroma-excellence'), esc_html($city)); ?>
            </h2>
            <p class="text-brand-ink/60 mt-3">
                <?php _e('Select the campus closest to your home or work.', 'chroma-excellence'); ?></p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            if (!empty($location_ids) && is_array($location_ids)):
                $locations_query = new WP_Query([
                    'post_type' => 'location',
                    'post__in' => $location_ids,
                    'orderby' => 'post__in',
                    'posts_per_page' => -1,
                    'no_found_rows' => true,
                    'update_post_meta_cache' => true,
                    'update_post_term_cache' => false, // No terms used in the loop
                ]);

                if ($locations_query->have_posts()):
                    while ($locations_query->have_posts()):
                        $locations_query->the_post();

                        // Get location data
                        $address = get_post_meta(get_the_ID(), 'location_address', true);
                        $loc_city = get_post_meta(get_the_ID(), 'location_city', true);
                        if ($loc_city && !$address) {
                            $address = $loc_city;
                        }

                        $rating = get_post_meta(get_the_ID(), 'location_google_rating', true) ?: '4.9';

                        // Get image
                        $image = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                        if (!$image) {
                            $gallery = get_post_meta(get_the_ID(), 'location_hero_gallery', true);
                            if ($gallery) {
                                $lines = explode("\n", $gallery);
                                $image = trim($lines[0]);
                            }
                        }
                        if (!$image) {
                            $image = $local_fallback;
                        }
                        ?>
                        <!-- Location Card -->
                        <div
                            class="group p-6 rounded-3xl bg-brand-cream border border-brand-ink/5 hover:border-chroma-blue/30 transition-all hover:-translate-y-1 flex flex-col shadow-card">
                            <div class="h-48 rounded-2xl bg-chroma-blue/5 mb-6 overflow-hidden relative">
                                <?php if ($image !== $local_fallback): ?>
                                    <img src="<?php echo esc_url($image); ?>" class="w-full h-full object-cover"
                                        alt="<?php the_title_attribute(); ?>" loading="lazy">
                                <?php else: ?>
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-chroma-blue/20 to-chroma-green/20 flex items-center justify-center">
                                        <img src="<?php echo esc_url($local_fallback); ?>" alt="Chroma" class="w-16 h-16 opacity-30"
                                            loading="lazy">
                                    </div>
                                <?php endif; ?>

                                <div
                                    class="absolute top-4 right-4 bg-white px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide shadow-sm">
                                    <?php echo esc_html($rating); ?> ★
                                </div>
                            </div>

                            <h3 class="font-serif text-xl font-bold text-brand-ink mb-2"><?php the_title(); ?></h3>

                            <?php if ($address): ?>
                                <p class="text-sm text-brand-ink/60 mb-1"><?php echo esc_html($address); ?></p>
                            <?php endif; ?>

                            <p class="text-xs text-brand-ink font-bold uppercase tracking-widest mb-6">
                                <?php printf(__('Serving %s Families', 'chroma-excellence'), esc_html($city)); ?>
                            </p>

                            <div class="mt-auto">
                                <a href="<?php the_permalink(); ?>" aria-label="View Campus: <?php the_title_attribute(); ?>"
                                    class="block w-full py-3 bg-chroma-blue text-white text-center rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-chroma-blue/90 transition-colors">
                                    <?php _e('View Campus', 'chroma-excellence'); ?>
                                </a>
                            </div>
                        </div>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                endif;
            else:
                ?>
                <div class="col-span-full text-center py-12">
                    <p class="text-brand-ink/60">
                        <?php _e('No locations are currently linked to this city. Please check back soon!', 'chroma-excellence'); ?>
                    </p>
                    <a href="<?php echo esc_url(home_url('/locations/')); ?>"
                        class="inline-block mt-4 text-chroma-blue font-semibold hover:underline">
                        <?php _e('View All Locations →', 'chroma-excellence'); ?>
                    </a>
                </div>
                <?php
            endif;
            ?>
        </div>

        <?php if (!empty($neighborhoods) && is_array($neighborhoods)): ?>
            <div class="mt-12 text-center">
                <p class="text-brand-ink/60 text-sm">
                    <strong><?php _e('Also proudly serving families in:', 'chroma-excellence'); ?></strong><br>
                    <?php echo esc_html(implode(', ', $neighborhoods)); ?>.
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Programs Grid -->
<section id="programs" class="py-20 bg-white scroll-mt-24">
    <div class="max-w-7xl mx-auto px-4 lg:px-6">
        <div class="text-center mb-12">
            <h2 class="font-serif text-2xl md:text-3xl font-bold text-brand-ink">
                <?php printf(__('Programs Available in %s', 'chroma-excellence'), esc_html($city)); ?>
            </h2>
            <p class="text-brand-ink/60 mt-3">
                <?php _e('World-class curriculum served locally.', 'chroma-excellence'); ?></p>
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

            if ($programs_query->have_posts()):
                while ($programs_query->have_posts()):
                    $programs_query->the_post();
                    $program_slug = get_post_field('post_name');
                    $city_slug = sanitize_title($city);
                    // Construct Combo URL: /program-in-city-state/
                    $combo_url = home_url("/{$program_slug}-in-{$city_slug}-{$state}/");

                    $age_range = get_post_meta(get_the_ID(), 'program_age_range', true);
                    ?>
                    <div
                        class="group p-6 rounded-3xl bg-brand-cream border border-brand-ink/5 hover:border-chroma-blue/30 transition-all hover:-translate-y-1 flex flex-col shadow-card">
                        <div class="h-48 rounded-2xl bg-chroma-blue/5 mb-6 overflow-hidden relative">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('medium_large', ['class' => 'w-full h-full object-cover']); ?>
                            <?php else: ?>
                                <div
                                    class="absolute inset-0 bg-gradient-to-br from-chroma-blue/20 to-chroma-green/20 flex items-center justify-center">
                                    <span class="text-4xl">📚</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <h3 class="font-serif text-xl font-bold text-brand-ink mb-2"><?php the_title(); ?></h3>

                        <?php if ($age_range): ?>
                            <p class="text-xs text-brand-ink/60 font-bold uppercase tracking-widest mb-6">
                                <?php printf(__('Ages %s', 'chroma-excellence'), esc_html($age_range)); ?>
                            </p>
                        <?php endif; ?>

                        <div class="mt-auto">
                            <a href="<?php echo esc_url($combo_url); ?>"
                                class="block w-full py-3 bg-chroma-blue text-white text-center rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-chroma-blue/90 transition-colors">
                                <?php _e('View Program', 'chroma-excellence'); ?>
                            </a>
                        </div>
                    </div>
                    <?php
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>
    </div>
</section>

<!-- Local FAQ for SEO -->
<section class="py-20 bg-brand-cream border-t border-brand-ink/5">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="font-serif text-2xl md:text-3xl font-bold text-brand-ink mb-10 text-center">
            <?php printf(__('Questions about Childcare in %s', 'chroma-excellence'), esc_html($city)); ?>
        </h2>

        <div class="space-y-4">
            <details class="group bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5">
                <summary class="flex items-center justify-between font-bold text-brand-ink list-none cursor-pointer">
                    <span><?php printf(__('Do you offer GA Lottery Pre-K in %s?', 'chroma-excellence'), esc_html($city)); ?></span>
                    <svg class="w-5 h-5 text-chroma-blue group-open:rotate-180 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </summary>
                <p class="mt-4 text-sm text-brand-ink/80">
                    <?php printf(__('Yes! Our locations serving %s participate in the Georgia Lottery Pre-K program. It is tuition-free for all 4-year-olds living in Georgia.', 'chroma-excellence'), esc_html($city)); ?>
                </p>
            </details>

            <details class="group bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5">
                <summary class="flex items-center justify-between font-bold text-brand-ink list-none cursor-pointer">
                    <span><?php printf(__('Do you provide transportation from %s schools?', 'chroma-excellence'), esc_html($city)); ?></span>
                    <svg class="w-5 h-5 text-chroma-blue group-open:rotate-180 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </summary>
                <p class="mt-4 text-sm text-brand-ink/80">
                    <?php printf(__('We provide safe bus transportation from most major elementary schools in the %s School District. Check the specific campus page for a full list.', 'chroma-excellence'), esc_html($county)); ?>
                </p>
            </details>

            <details class="group bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5">
                <summary class="flex items-center justify-between font-bold text-brand-ink list-none cursor-pointer">
                    <span><?php printf(__('What ages do you accept at your %s centers?', 'chroma-excellence'), esc_html($city)); ?></span>
                    <svg class="w-5 h-5 text-chroma-blue group-open:rotate-180 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </summary>
                <p class="mt-4 text-sm text-brand-ink/80">
                    <?php printf(__('We serve children from 6 weeks old (<a href="%s" class="text-chroma-blue hover:underline">Infant Care</a>) up to 12 years old (<a href="%s" class="text-chroma-blue hover:underline">After School</a>). We also offer a <a href="%s" class="text-chroma-blue hover:underline">Pre-K Prep</a> program.', 'chroma-excellence'), esc_url(home_url('/programs/infant-care/')), esc_url(home_url('/programs/after-school/')), esc_url(chroma_get_page_link('pre-k-prep'))); ?>
                </p>
            </details>

            <details class="group bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5">
                <summary class="flex items-center justify-between font-bold text-brand-ink list-none cursor-pointer">
                    <span><?php printf(__('How do I enroll my child in %s?', 'chroma-excellence'), esc_html($city)); ?></span>
                    <svg class="w-5 h-5 text-chroma-blue group-open:rotate-180 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </summary>
                <p class="mt-4 text-sm text-brand-ink/80">
                    <?php _e("The best way to start is by scheduling a tour at your preferred location. You can book online or call us directly. We'll walk you through the enrollment process and answer all your questions.", 'chroma-excellence'); ?>
                </p>
            </details>
        </div>
    </div>
</section>

<!-- Back to Communities -->
<div class="py-8 bg-white text-center">
    <a href="<?php echo esc_url(get_post_type_archive_link('city')); ?>"
        class="inline-flex items-center gap-2 text-sm font-semibold text-brand-ink/60 hover:text-chroma-blue transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"></path>
        </svg>
        <?php _e('Back to All Communities', 'chroma-excellence'); ?>
    </a>
</div>

<?php get_footer(); ?>
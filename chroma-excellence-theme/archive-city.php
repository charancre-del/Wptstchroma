<?php
/**
 * Archive Template for City CPT
 * Displays all cities in a filterable grid (Locations Style)
 *
 * @package Chroma_Excellence
 */

get_header();

// Get all cities for the grid (Load All for client-side filtering)
// Optimized: Pre-cache meta, skip term cache, collect counties during display
$cities_query = chroma_cached_query(
    [
        'post_type'              => 'city',
        'posts_per_page'         => -1,
        'post_status'            => 'publish',
        'orderby'                => 'title',
        'order'                  => 'ASC',
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
        'no_found_rows'          => true,
    ],
    'cities_archive',
    7 * DAY_IN_SECONDS
);

// Counties will be collected during the main loop to avoid double iteration
$unique_counties = [];

// Local fallback image
$local_fallback = get_template_directory_uri() . '/assets/images/logo_chromacropped_140x140.webp';

// Pre-collect counties from posts (single pass, using cached meta)
if ($cities_query->have_posts()) {
    foreach ($cities_query->posts as $p) {
        $c = chroma_get_translated_meta($p->ID, 'city_county');
        if ($c && !in_array($c, $unique_counties)) {
            $unique_counties[] = $c;
        }
    }
    sort($unique_counties);
}


/**
 * Helper: Get city formatted image HTML
 * Handles featured image, meta image (ID or URL), and fallback.
 * Uses wp_get_attachment_image for correct aspect ratio attributes.
 */
if (!function_exists('chroma_get_city_image_html')) {
    function chroma_get_city_image_html($city_post, $fallback_url)
    {
        $img_class = 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500';

        // 1. Try Featured Image
        if (has_post_thumbnail($city_post->ID)) {
            return get_the_post_thumbnail($city_post->ID, 'medium_large', ['class' => $img_class, 'loading' => 'lazy']);
        }

        // 2. Try Hero Image Meta
        $hero_val = get_post_meta($city_post->ID, 'city_hero_image', true);
        if ($hero_val) {
            if (is_numeric($hero_val)) {
                return wp_get_attachment_image($hero_val, 'medium_large', false, ['class' => $img_class, 'loading' => 'lazy']);
            } else {
                return sprintf(
                    '<img src="%s" class="%s" alt="%s" loading="lazy">',
                    esc_url($hero_val),
                    esc_attr($img_class),
                    esc_attr($city_post->post_title . ' community')
                );
            }
        }

        // 3. Fallback (Return null to trigger CSS fallback)
        return null;
    }
}
?>

<main>
    <!-- Hero Section -->
    <section class="relative pt-16 pb-12 lg:pt-24 lg:pb-20 bg-white overflow-hidden">
        <!-- Decor -->
        <div
            class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-chroma-blueLight/40 via-transparent to-transparent">
        </div>

        <div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 text-center">
            <div
                class="inline-flex items-center gap-2 bg-white border border-chroma-blue/30 px-4 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-bold text-chroma-blue shadow-sm mb-6 fade-in-up">
                <i class="fa-solid fa-city"></i> <?php echo $cities_query->found_posts; ?> <?php _e('Communities', 'chroma-excellence'); ?>
            </div>

            <h1 class="font-serif text-[2.8rem] md:text-6xl text-brand-ink mb-6 fade-in-up"
                style="animation-delay: 0.1s;">
                <?php _e('Our <span class="text-chroma-blue italic">Communities</span>', 'chroma-excellence'); ?>
            </h1>

            <p class="text-lg text-brand-ink/90 max-w-2xl mx-auto mb-10 fade-in-up" style="animation-delay: 0.2s;">
                <?php _e('Discover our network of excellence across Georgia\'s most vibrant neighborhoods. Select your city to find local campuses.', 'chroma-excellence'); ?>
            </p>

            <!-- Filter Bar -->
            <div class="max-w-7xl mx-auto bg-white p-2 rounded-full shadow-float border border-brand-ink/5 flex flex-col lg:flex-row gap-2 fade-in-up"
                style="animation-delay: 0.3s;">
                <div class="relative flex-grow max-w-md">
                    <i class="fa-solid fa-search absolute left-5 top-1/2 -translate-y-1/2 text-brand-ink"></i>
                    <input type="text" id="city-search" placeholder="<?php esc_attr_e('Search for your city...', 'chroma-excellence'); ?>"
                        class="w-full pl-12 pr-4 py-3 rounded-full focus:outline-none text-brand-ink bg-white" />
                </div>
                <div
                    class="flex gap-2 justify-start lg:justify-center flex-wrap flex-grow items-center overflow-x-auto pb-2 lg:pb-0">
                    <button onclick="filterCities('all')" data-county="all"
                        class="filter-btn px-6 py-3 rounded-full font-semibold bg-chroma-blue text-white hover:shadow-glow transition-all duration-300 whitespace-nowrap">
                        <?php _e('All', 'chroma-excellence'); ?>
                    </button>
                    <?php foreach ($unique_counties as $county):
                        $slug = sanitize_title($county);
                        ?>
                        <button onclick="filterCities('<?php echo esc_attr($slug); ?>')"
                            data-county="<?php echo esc_attr($slug); ?>"
                            class="filter-btn px-6 py-3 rounded-full font-semibold bg-white text-brand-ink border border-brand-ink/10 hover:bg-brand-ink/5 transition-all duration-300 whitespace-nowrap">
                            <?php echo esc_html($county); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Cities Grid -->
    <section class="py-16 lg:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 lg:px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 md:gap-8" id="cities-grid">

                <?php if ($cities_query->have_posts()): ?>
                    <?php while ($cities_query->have_posts()):
                        $cities_query->the_post();
                        $city_id = get_the_ID();
                        $county = chroma_get_translated_meta($city_id, 'city_county') ?: __('Other', 'chroma-excellence');
                        $county_slug = sanitize_title($county);

                        $city_html = chroma_get_city_image_html($post, $local_fallback);
                        $city_description = chroma_get_translated_meta($city_id, 'city_intro_text');
                        ?>
                        <div class="city-card group" data-county="<?php echo esc_attr($county_slug); ?>"
                            data-name="<?php echo esc_attr(strtolower(get_the_title())); ?>">
                            <a href="<?php the_permalink(); ?>"
                                class="block h-full bg-white rounded-3xl overflow-hidden shadow-card hover:shadow-xl transition-all duration-300 border border-brand-ink/5"
                                aria-label="View schools in <?php the_title_attribute(); ?>">

                                <div class="h-48 overflow-hidden relative bg-chroma-blue/5">
                                    <div
                                        class="absolute inset-0 bg-brand-ink/10 group-hover:bg-transparent transition-colors z-10">
                                    </div>

                                    <?php if ($city_html): ?>
                                        <?php echo $city_html; ?>
                                    <?php else: ?>
                                        <div
                                            class="absolute inset-0 bg-gradient-to-br from-chroma-blue/20 to-chroma-green/20 flex items-center justify-center">
                                            <img src="<?php echo esc_url($local_fallback); ?>" alt="Chroma"
                                                class="w-20 h-20 opacity-30" loading="lazy">
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="p-6">
                                    <div class="mb-2">
                                        <span
                                            class="inline-block px-2 py-0.5 rounded-full bg-brand-ink/5 text-brand-ink/60 text-[10px] font-bold uppercase tracking-wider">
                                            <?php echo esc_html($county); ?>
                                        </span>
                                    </div>
                                    <h3
                                        class="font-serif text-xl md:text-2xl font-bold text-brand-ink mb-2 group-hover:text-chroma-blue transition-colors">
                                        <?php the_title(); ?>
                                    </h3>
                                    <p class="text-sm text-brand-ink/60 mb-4 line-clamp-2">
                                        <?php echo esc_html($city_description); ?>
                                    </p>

                                    <span
                                        class="text-xs font-bold uppercase tracking-wider text-chroma-red inline-flex items-center gap-2">
                                        <?php _e('View Schools', 'chroma-excellence'); ?>
                                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <p class="col-span-full text-center text-xl text-brand-ink/60"><?php _e('No communities found.', 'chroma-excellence'); ?></p>
                <?php endif; ?>

                <!-- No Results Msg -->
                <div id="no-results" class="hidden col-span-full text-center py-12">
                    <p class="text-xl text-brand-ink/60"><?php _e('No cities match your search.', 'chroma-excellence'); ?></p>
                </div>

            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="bg-chroma-blueDark py-16 text-white text-center">
        <div class="max-w-4xl mx-auto px-4">
            <h2 class="font-serif text-3xl font-bold mb-4"><?php _e('Don\'t see your city?', 'chroma-excellence'); ?></h2>
            <p class="text-white/80 mb-8 max-w-2xl mx-auto"><?php _e('We are constantly expanding. Contact our enrollment team to find the nearest campus to you.', 'chroma-excellence'); ?></p>
            <a href="<?php echo esc_url(chroma_get_page_link('contact')); ?>"
                class="inline-block bg-chroma-yellow text-brand-ink font-bold rounded-full px-8 py-4 uppercase tracking-widest text-xs hover:bg-white transition-colors">
                <?php _e('Contact Us', 'chroma-excellence'); ?>
            </a>
        </div>
    </section>
</main>

<script>
    function filterCities(county) {
        const cards = document.querySelectorAll('.city-card');
        const buttons = document.querySelectorAll('.filter-btn');
        const searchInput = document.getElementById('city-search');
        const noResults = document.getElementById('no-results');
        let visibleCount = 0;

        if (county) searchInput.value = '';

        buttons.forEach(btn => {
            if (county === btn.dataset.county) {
                btn.classList.remove('bg-white', 'text-brand-ink', 'border');
                btn.classList.add('bg-chroma-blue', 'text-white', 'shadow-glow');
            } else {
                btn.classList.add('bg-white', 'text-brand-ink', 'border');
                btn.classList.remove('bg-chroma-blue', 'text-white', 'shadow-glow');
            }
        });

        cards.forEach(card => {
            if (county === 'all' || card.dataset.county === county) {
                card.style.display = 'block';
                // Trigger animation reflow
                card.classList.remove('animate-fade-in-up');
                void card.offsetWidth;
                card.classList.add('animate-fade-in-up');
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    }

    document.getElementById('city-search').addEventListener('keyup', function (e) {
        const term = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.city-card');
        const buttons = document.querySelectorAll('.filter-btn');
        const noResults = document.getElementById('no-results');
        let visibleCount = 0;

        // Reset sidebar active state visually (optional, keeps 'All' effectively active)
        buttons.forEach(btn => {
            btn.classList.add('bg-white', 'text-brand-ink', 'border');
            btn.classList.remove('bg-chroma-blue', 'text-white', 'shadow-glow');
        });

        cards.forEach(card => {
            if (card.dataset.name.includes(term)) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noResults) noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    });
</script>

<?php get_footer(); ?>
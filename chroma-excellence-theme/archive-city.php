<?php
/**
 * Archive Template for City CPT
 * Displays all cities grouped by county with dynamic counts
 *
 * @package Chroma_Excellence
 */

get_header();

// Pagination settings
$cities_per_page = 12;
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Get total count of all cities for display
$total_cities_query = new WP_Query([
    'post_type' => 'city',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'fields' => 'ids'
]);
$total_cities = $total_cities_query->found_posts;
wp_reset_postdata();

// Get all cities for grouping (we need all for county grouping)
$all_cities = get_posts([
    'post_type' => 'city',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC'
]);

// County icons and colors
$county_icons = [
    'Cobb' => '🍑',
    'Gwinnett' => '🌳',
    'Fulton' => '🏙️',
    'DeKalb' => '🌆',
    'Cherokee' => '🏔️',
    'Forsyth' => '🌲',
    'Clayton' => '✈️',
    'Henry' => '🏡',
    'Douglas' => '🌾',
    'Paulding' => '🦌',
    'Fayette' => '🌿',
    'Rockdale' => '🏛️',
    'Other' => '📍',
    'default' => '📍'
];

// Group cities by county
$cities_by_county = [];
foreach ($all_cities as $city_post) {
    $county = get_post_meta($city_post->ID, 'city_county', true);
    if (!$county) {
        $county = 'Other';
    }
    if (!isset($cities_by_county[$county])) {
        $cities_by_county[$county] = [];
    }
    $cities_by_county[$county][] = $city_post;
}

// Sort counties alphabetically
ksort($cities_by_county);

// Local fallback image (use theme asset)
$local_fallback = get_template_directory_uri() . '/assets/images/logo_chromacropped_140x140.webp';

/**
 * Helper: Get city featured image with fallbacks
 */
function chroma_get_city_image($city_post, $fallback_url)
{
    // Try featured image first
    $image = get_the_post_thumbnail_url($city_post->ID, 'medium_large');

    if (!$image) {
        // Try city_hero_image meta
        $hero_image = get_post_meta($city_post->ID, 'city_hero_image', true);
        if ($hero_image) {
            $image = $hero_image;
        }
    }

    if (!$image) {
        // Use theme fallback
        $image = $fallback_url;
    }

    return $image;
}

/**
 * Helper: Get city description with fallbacks
 */
function chroma_get_city_description($city_post, $max_length = 60)
{
    $description = get_post_meta($city_post->ID, 'city_intro_text', true);

    if (!$description) {
        $description = $city_post->post_excerpt;
    }

    if (!$description) {
        $description = 'Serving families in ' . $city_post->post_title . ' and surrounding areas.';
    }

    // Truncate
    if (strlen($description) > $max_length) {
        $description = substr($description, 0, $max_length - 3) . '...';
    }

    return $description;
}
?>

<section class="relative bg-white pt-12 pb-16 overflow-hidden">
    <!-- Abstract Background -->
    <div
        class="absolute top-0 right-0 w-96 h-96 bg-chroma-blue/5 rounded-full blur-3xl translate-x-1/2 -translate-y-1/2">
    </div>
    <div
        class="absolute bottom-0 left-0 w-80 h-80 bg-chroma-yellow/10 rounded-full blur-3xl -translate-x-1/2 translate-y-1/2">
    </div>

    <div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 text-center">
        <span class="inline-block text-chroma-red font-bold tracking-widest text-xs uppercase mb-4">Metro Atlanta</span>
        <h1 class="font-serif text-4xl md:text-6xl font-bold text-brand-ink mb-6">Our Communities</h1>
        <p class="text-lg text-brand-ink/70 max-w-2xl mx-auto mb-10">
            Chroma serves <strong class="text-chroma-blue"><?php echo esc_html($total_cities); ?></strong> distinct
            communities across Metro Atlanta. Select your city to see local campuses, waitlist times, and director
            profiles.
        </p>

        <!-- Quick Jump Buttons (Counties) -->
        <div class="flex flex-wrap justify-center gap-3">
            <?php foreach (array_keys($cities_by_county) as $county_name):
                $county_slug = sanitize_title($county_name);
                ?>
                <a href="#county-<?php echo esc_attr($county_slug); ?>"
                    class="px-4 py-2 rounded-full border border-brand-ink/10 bg-white text-sm font-semibold text-brand-ink hover:bg-brand-ink hover:text-white transition-all shadow-sm">
                    <?php echo esc_html($county_name); ?> County
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 lg:px-6 py-12 space-y-20">

    <?php
    $city_counter = 0;
    $start_index = ($paged - 1) * $cities_per_page;
    $end_index = $start_index + $cities_per_page;

    foreach ($cities_by_county as $county_name => $county_cities):
        $county_slug = sanitize_title($county_name);
        $county_icon = isset($county_icons[$county_name]) ? $county_icons[$county_name] : $county_icons['default'];

        // Check if any cities from this county are in current page range
        $cities_to_show = [];
        foreach ($county_cities as $city_post) {
            if ($city_counter >= $start_index && $city_counter < $end_index) {
                $cities_to_show[] = $city_post;
            }
            $city_counter++;
        }

        // Skip county if no cities to show on this page
        // For now, show all (pagination is optional for smaller datasets)
        $cities_to_show = $county_cities;
        ?>
        <!-- <?php echo esc_html($county_name); ?> County Group -->
        <section id="county-<?php echo esc_attr($county_slug); ?>" class="scroll-mt-32">
            <div class="flex items-center gap-4 mb-8">
                <span class="text-3xl" role="img"
                    aria-label="<?php echo esc_attr($county_name); ?> icon"><?php echo $county_icon; ?></span>
                <h2 class="font-serif text-2xl md:text-3xl font-bold text-brand-ink"><?php echo esc_html($county_name); ?>
                    County</h2>
                <div class="h-px bg-brand-ink/10 flex-1 ml-4"></div>
                <span class="text-sm text-brand-ink/50"><?php echo count($county_cities); ?>
                    <?php echo count($county_cities) === 1 ? 'city' : 'cities'; ?></span>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($cities_to_show as $city_post):
                    $city_title = $city_post->post_title;
                    $city_permalink = get_permalink($city_post->ID);

                    // Get nearby locations count
                    $nearby_locations = get_post_meta($city_post->ID, 'city_nearby_locations', true);
                    if (!is_array($nearby_locations)) {
                        $nearby_locations = [];
                    }
                    $campus_count = count($nearby_locations);

                    // Get city image
                    $city_image = chroma_get_city_image($city_post, $local_fallback);

                    // Check status
                    $is_opening_soon = ($campus_count === 0);

                    // Get description
                    $city_description = chroma_get_city_description($city_post);
                    ?>
                    <!-- <?php echo esc_html($city_title); ?> City Card -->
                    <a href="<?php echo esc_url($city_permalink); ?>"
                        class="group relative bg-white rounded-3xl overflow-hidden shadow-card hover:shadow-xl transition-all duration-300 border border-brand-ink/5"
                        aria-label="View schools in <?php echo esc_attr($city_title); ?>">
                        <div class="h-48 overflow-hidden relative bg-chroma-blue/5">
                            <div class="absolute inset-0 bg-brand-ink/10 group-hover:bg-transparent transition-colors z-10">
                            </div>

                            <?php if ($city_image !== $local_fallback): ?>
                                <img src="<?php echo esc_url($city_image); ?>"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    alt="<?php echo esc_attr($city_title); ?> community" loading="lazy">
                            <?php else: ?>
                                <!-- Fallback: Gradient with logo -->
                                <div
                                    class="absolute inset-0 bg-gradient-to-br from-chroma-blue/20 to-chroma-green/20 flex items-center justify-center">
                                    <img src="<?php echo esc_url($local_fallback); ?>" alt="Chroma" class="w-20 h-20 opacity-30"
                                        loading="lazy">
                                </div>
                            <?php endif; ?>


                        </div>

                        <div class="p-6">
                            <h3
                                class="font-serif text-xl md:text-2xl font-bold text-brand-ink mb-2 group-hover:text-chroma-blue transition-colors">
                                <?php echo esc_html($city_title); ?>
                            </h3>
                            <p class="text-sm text-brand-ink/60 mb-4 line-clamp-2">
                                <?php echo esc_html($city_description); ?>
                            </p>

                            <span
                                class="text-xs font-bold uppercase tracking-wider text-chroma-red inline-flex items-center gap-2">
                                View Schools
                                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                </svg>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php
    // Pagination (if needed in future)
    $total_pages = ceil($total_cities / $cities_per_page);
    if ($total_pages > 1):
        ?>
        <nav class="flex justify-center items-center gap-2 mt-16" aria-label="Pagination">
            <?php if ($paged > 1): ?>
                <a href="<?php echo esc_url(get_pagenum_link($paged - 1)); ?>"
                    class="px-4 py-2 rounded-full border border-brand-ink/10 text-sm font-semibold text-brand-ink hover:bg-brand-ink hover:text-white transition-all">
                    ← Previous
                </a>
            <?php endif; ?>

            <span class="px-4 py-2 text-sm text-brand-ink/60">
                Page <?php echo $paged; ?> of <?php echo $total_pages; ?>
            </span>

            <?php if ($paged < $total_pages): ?>
                <a href="<?php echo esc_url(get_pagenum_link($paged + 1)); ?>"
                    class="px-4 py-2 rounded-full border border-brand-ink/10 text-sm font-semibold text-brand-ink hover:bg-brand-ink hover:text-white transition-all">
                    Next →
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

</div>

<?php get_footer(); ?>
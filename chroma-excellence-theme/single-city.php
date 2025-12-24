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
$county = get_post_meta(get_the_ID(), 'city_county', true) ?: 'Local';
$neighborhoods = get_post_meta(get_the_ID(), 'city_neighborhoods', true);
$location_ids = get_post_meta(get_the_ID(), 'city_nearby_locations', true);
$intro_text = get_post_meta(get_the_ID(), 'city_intro_text', true);

// Fallback for related locations (try alternative meta key)
if (empty($location_ids)) {
    $location_ids = get_post_meta(get_the_ID(), 'related_location_ids', true);
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
            Serving <?php echo esc_html($city); ?> & <?php echo esc_html($county); ?> County
        </span>

        <h1 class="font-serif text-4xl md:text-6xl text-brand-ink mb-6 leading-tight">
            The Best Daycare in <span class="italic text-chroma-blue"><?php echo esc_html($city); ?>, GA.</span>
        </h1>

        <p class="text-lg md:text-xl text-brand-ink/80 max-w-2xl mx-auto mb-10">
            Are you looking for "daycare near me"? Discover the highest-rated early learning centers in the
            <?php echo esc_html($city); ?> area, featuring the Prismpath™ curriculum and GA Pre-K.
        </p>

        <a href="#locations"
            class="inline-flex items-center gap-2 text-chroma-red font-bold border-b-2 border-chroma-red pb-1 hover:text-brand-ink hover:border-brand-ink transition-all">
            See Locations in <?php echo esc_html($city); ?>
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
            Early Education and <br>
            Care in <span class="text-chroma-blue"><?php echo esc_html($city); ?>, GA</span>
        </h2>

        <?php if ($intro_text): ?>
            <div class="text-lg md:text-xl text-brand-ink/80 leading-relaxed max-w-3xl mx-auto">
                <?php echo wp_kses_post($intro_text); ?>
            </div>
        <?php else: ?>
            <p class="text-lg md:text-xl text-brand-ink/80 leading-relaxed max-w-3xl mx-auto">
                Our school is more than a daycare. Through purposeful play and nurturing guidance, we help lay the
                foundation for a lifelong love of learning.
                <br><br>
                Conveniently located near major highways and down the road from local landmarks and top-rated
                elementary schools, we are the convenient choice for <?php echo esc_html($city); ?> working parents.
                Come by and see Prismpath™ in action at one of our nearby campuses.
            </p>
        <?php endif; ?>
    </div>
</section>

<!-- Locations Grid -->
<section id="locations" class="py-20 bg-white scroll-mt-24">
    <div class="max-w-7xl mx-auto px-4 lg:px-6">
        <div class="text-center mb-12">
            <h2 class="font-serif text-2xl md:text-3xl font-bold text-brand-ink">
                Chroma Locations Serving <?php echo esc_html($city); ?>
            </h2>
            <p class="text-brand-ink/60 mt-3">Select the campus closest to your home or work.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            if (!empty($location_ids) && is_array($location_ids)):
                $locations_query = new WP_Query([
                    'post_type' => 'location',
                    'post__in' => $location_ids,
                    'orderby' => 'post__in',
                    'posts_per_page' => -1
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
                                Serving <?php echo esc_html($city); ?> Families
                            </p>

                            <div class="mt-auto">
                                <a href="<?php the_permalink(); ?>" aria-label="View Campus: <?php the_title_attribute(); ?>"
                                    class="block w-full py-3 bg-chroma-blue text-white text-center rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-chroma-blue/90 transition-colors">
                                    View Campus
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
                    <p class="text-brand-ink/60">No locations are currently linked to this city. Please check back soon!</p>
                    <a href="<?php echo esc_url(home_url('/locations/')); ?>"
                        class="inline-block mt-4 text-chroma-blue font-semibold hover:underline">
                        View All Locations →
                    </a>
                </div>
                <?php
            endif;
            ?>
        </div>

        <?php if (!empty($neighborhoods) && is_array($neighborhoods)): ?>
            <div class="mt-12 text-center">
                <p class="text-brand-ink/60 text-sm">
                    <strong>Also proudly serving families in:</strong><br>
                    <?php echo esc_html(implode(', ', $neighborhoods)); ?>.
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Local FAQ for SEO -->
<section class="py-20 bg-brand-cream border-t border-brand-ink/5">
    <div class="max-w-4xl mx-auto px-4">
        <h2 class="font-serif text-2xl md:text-3xl font-bold text-brand-ink mb-10 text-center">
            Questions about Childcare in <?php echo esc_html($city); ?>
        </h2>

        <div class="space-y-4">
            <details class="group bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5">
                <summary class="flex items-center justify-between font-bold text-brand-ink list-none cursor-pointer">
                    <span>Do you offer GA Lottery Pre-K in <?php echo esc_html($city); ?>?</span>
                    <svg class="w-5 h-5 text-chroma-blue group-open:rotate-180 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </summary>
                <p class="mt-4 text-sm text-brand-ink/80">
                    Yes! Our locations serving <?php echo esc_html($city); ?> participate in the Georgia Lottery Pre-K
                    program.
                    It is tuition-free for all 4-year-olds living in Georgia.
                </p>
            </details>

            <details class="group bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5">
                <summary class="flex items-center justify-between font-bold text-brand-ink list-none cursor-pointer">
                    <span>Do you provide transportation from <?php echo esc_html($city); ?> schools?</span>
                    <svg class="w-5 h-5 text-chroma-blue group-open:rotate-180 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </summary>
                <p class="mt-4 text-sm text-brand-ink/80">
                    We provide safe bus transportation from most major elementary schools in the
                    <?php echo esc_html($county); ?> School District.
                    Check the specific campus page for a full list.
                </p>
            </details>

            <details class="group bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5">
                <summary class="flex items-center justify-between font-bold text-brand-ink list-none cursor-pointer">
                    <span>What ages do you accept at your <?php echo esc_html($city); ?> centers?</span>
                    <svg class="w-5 h-5 text-chroma-blue group-open:rotate-180 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </summary>
                <p class="mt-4 text-sm text-brand-ink/80">
                    We serve children from 6 weeks old (<a
                        href="<?php echo esc_url(home_url('/programs/infant-care/')); ?>"
                        class="text-chroma-blue hover:underline">Infant Care</a>) up to 12 years old (<a
                        href="<?php echo esc_url(home_url('/programs/after-school/')); ?>"
                        class="text-chroma-blue hover:underline">After School</a>).
                    We also offer a <a href="<?php echo esc_url(home_url('/programs/pre-k/')); ?>"
                        class="text-chroma-blue hover:underline">Private Kindergarten</a> option at select locations.
                </p>
            </details>

            <details class="group bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5">
                <summary class="flex items-center justify-between font-bold text-brand-ink list-none cursor-pointer">
                    <span>How do I enroll my child in <?php echo esc_html($city); ?>?</span>
                    <svg class="w-5 h-5 text-chroma-blue group-open:rotate-180 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </summary>
                <p class="mt-4 text-sm text-brand-ink/80">
                    The best way to start is by scheduling a tour at your preferred location.
                    You can book online or call us directly. We'll walk you through the enrollment process and answer
                    all your questions.
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
        Back to All Communities
    </a>
</div>

<?php get_footer(); ?>
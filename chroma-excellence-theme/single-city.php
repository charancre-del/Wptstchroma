<?php
/**
 * Single City Template
 *
 * @package Chroma_Excellence
 */

$city = get_the_title();
$city_slug = get_post_field('post_name');
$county = get_post_meta(get_the_ID(), 'city_county', true) ?: 'Local';
$neighborhoods = get_post_meta(get_the_ID(), 'city_neighborhoods', true);
$location_ids = get_post_meta(get_the_ID(), 'related_location_ids', true);
$hero_image = get_post_meta(get_the_ID(), 'city_hero_image', true);

// Fallback image
if (!$hero_image) {
    $hero_image = 'https://images.unsplash.com/photo-1587654780291-39c9404d746b?q=80&w=1200&auto=format&fit=crop';
}

$location_count = is_array($location_ids) ? count($location_ids) : 0;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="UTF-8" />
    <!-- SEO Title: Pure Keyword Focus -->
    <title>Best Daycare & Preschool in <?php echo esc_html($city); ?>, GA | Chroma Early Learning</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description"
        content="Looking for 5-star rated daycare in <?php echo esc_html($city); ?>? Chroma offers accredited infant care, toddler programs, and Free GA Pre-K at <?php echo esc_html($location_count); ?> convenient locations near you." />

    <!-- Organization Schema -->
    <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "EducationalOrganization",
    "name": "Chroma Early Learning Academy - <?php echo esc_js($city); ?> Region",
    "url": "<?php echo esc_url(get_permalink()); ?>",
    "areaServed": {
      "@type": "City",
      "name": "<?php echo esc_js($city); ?>"
    }
  }
  </script>

    <!-- Fonts & Styles -->
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'], serif: ['Playfair Display', 'serif'] },
                    colors: { brand: { ink: '#263238', cream: '#FFFCF8' }, chroma: { blue: '#4A6C7C', red: '#D67D6B', green: '#8DA399', yellow: '#E6BE75', blueDark: '#2F4858' } },
                    boxShadow: { soft: '0 20px 40px -10px rgba(74, 108, 124, 0.08)', card: '0 10px 30px -5px rgba(0, 0, 0, 0.04)' }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
    </style>
    <?php wp_head(); ?>
</head>

<body class="bg-brand-cream text-brand-ink antialiased">

    <!-- Simplified Header -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-xl border-b border-chroma-blue/10">
        <div class="max-w-7xl mx-auto px-4 h-[82px] flex items-center justify-between">
            <a href="/"
                class="font-bold text-lg text-brand-ink focus:outline-none focus:ring-2 focus:ring-chroma-blue rounded-lg px-2">Chroma
                <span class="text-chroma-blue text-xs uppercase tracking-widest ml-2"><?php echo esc_html($city); ?>
                    Area</span></a>
            <a href="#locations"
                class="bg-chroma-red text-white px-6 py-3 rounded-full text-xs font-bold uppercase tracking-widest hover:bg-chroma-blueDark focus:ring-2 focus:ring-offset-2 focus:ring-chroma-red focus:outline-none transition-colors">Find
                Nearest School</a>
        </div>
    </header>

    <main>
        <!-- SEO Hero: High Intent Keywords -->
        <section class="relative pt-20 pb-20 bg-white overflow-hidden">
            <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-chroma-blue/5 to-transparent -z-10">
            </div>
            <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
                <span
                    class="inline-block py-1 px-3 rounded-full bg-chroma-blue/10 text-chroma-blue text-[10px] font-bold uppercase tracking-widest mb-6">
                    Serving <?php echo esc_html($city); ?> & <?php echo esc_html($county); ?> County
                </span>
                <h1 class="font-serif text-5xl md:text-7xl text-brand-ink mb-6 leading-tight">
                    The Best Daycare in <span class="italic text-chroma-blue"><?php echo esc_html($city); ?>, GA.</span>
                </h1>
                <p class="text-xl text-brand-ink max-w-2xl mx-auto mb-10">
                    Are you looking for "daycare near me"? Discover the highest-rated early learning centers in the
                    <?php echo esc_html($city); ?> area, featuring the Prismpath™ curriculum and GA Pre-K.
                </p>
                <a href="#locations"
                    class="inline-flex items-center gap-2 text-chroma-red font-bold border-b-2 border-chroma-red pb-1 hover:text-brand-ink hover:border-brand-ink focus:outline-none focus:ring-2 focus:ring-chroma-red rounded px-1 transition-all">
                    See Locations in <?php echo esc_html($city); ?> <i class="fa-solid fa-arrow-down"></i>
                </a>
            </div>
        </section>

        <!-- The "Pooler, GA" Style SEO Content Block -->
        <section class="py-24 bg-brand-cream border-y border-brand-ink/5">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <!-- H2 styled exactly like the "Early Education..." screenshot -->
                <h2 class="font-serif text-4xl md:text-6xl text-brand-ink mb-8 leading-none font-normal">
                    Early Education and <br>
                    Care in <span class="text-chroma-blue"><?php echo esc_html($city); ?>, GA</span>
                </h2>

                <!-- SEO Body Text -->
                <p class="text-lg md:text-xl text-brand-ink leading-relaxed max-w-3xl mx-auto">
                    Our school is more than a daycare. Through purposeful play and nurturing guidance, we help lay the
                    foundation for a lifelong love of learning.
                    <br><br>
                    Conveniently located near major highways and down the road from local landmarks and top-rated
                    elementary schools, we are the convenient choice for <?php echo esc_html($city); ?> working parents.
                    Come by and see Balanced Learning® in action at one of our nearby campuses.
                </p>
            </div>
        </section>

        <!-- "Campuses Near You" Grid -->
        <section id="locations" class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 lg:px-6">
                <div class="text-center mb-16">
                    <h2 class="font-serif text-3xl font-bold text-brand-ink">Chroma Locations Serving
                        <?php echo esc_html($city); ?>
                    </h2>
                    <p class="text-brand-ink/60 mt-4">Select the campus closest to your home or work.</p>
                </div>

                <!-- Dynamic Grid of Locations in this Region -->
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">

                    <?php
                    if (!empty($location_ids) && is_array($location_ids)):
                        $locations_query = new WP_Query([
                            'post_type' => 'location',
                            'post__in' => $location_ids,
                            'orderby' => 'post__in'
                        ]);

                        if ($locations_query->have_posts()):
                            while ($locations_query->have_posts()):
                                $locations_query->the_post();
                                $address = chroma_location_address_line(); // Using helper if available, or get_post_meta
                                if (!$address)
                                    $address = get_post_meta(get_the_ID(), 'location_address', true);

                                $rating = get_post_meta(get_the_ID(), 'location_google_rating', true) ?: '4.9';
                                $image = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                                if (!$image) {
                                    // Try gallery
                                    $gallery = get_post_meta(get_the_ID(), 'location_hero_gallery', true);
                                    if ($gallery) {
                                        $lines = explode("\n", $gallery);
                                        $image = trim($lines[0]);
                                    }
                                }
                                // Fallback
                                if (!$image)
                                    $image = 'https://images.unsplash.com/photo-1587654780291-39c9404d746b?q=80&w=600';
                                ?>
                                <!-- Location Card -->
                                <div
                                    class="group p-8 rounded-[2.5rem] bg-brand-cream border border-brand-ink/5 hover:border-chroma-blue/30 transition-all hover:-translate-y-1 flex flex-col">
                                    <div class="h-48 rounded-[2rem] bg-gray-200 mb-6 overflow-hidden relative">
                                        <img src="<?php echo esc_url($image); ?>" class="w-full h-full object-cover"
                                            alt="<?php the_title(); ?>">
                                        <div
                                            class="absolute top-4 right-4 bg-white px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide shadow-sm">
                                            <?php echo esc_html($rating); ?> ★
                                        </div>
                                    </div>
                                    <h3 class="font-serif text-2xl font-bold text-brand-ink mb-2"><?php the_title(); ?></h3>
                                    <p class="text-sm text-brand-ink/60 mb-1"><?php echo esc_html($address); ?></p>
                                    <p class="text-xs text-brand-ink font-bold uppercase tracking-widest mb-6">Serving
                                        <?php echo esc_html($city); ?> Families
                                    </p>
                                    <div class="mt-auto flex gap-3">
                                        <a href="<?php the_permalink(); ?>"
                                            aria-label="View Campus: <?php the_title_attribute(); ?>"
                                            class="w-full py-3 bg-chroma-blue text-white text-center rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-chroma-blueDark focus:ring-2 focus:ring-offset-2 focus:ring-chroma-blue focus:outline-none transition-colors">View
                                            Campus</a>
                                    </div>
                                </div>
                                <?php
                            endwhile;
                            wp_reset_postdata();
                        endif;
                    endif;
                    ?>

                </div>

                <?php if (!empty($neighborhoods) && is_array($neighborhoods)): ?>
                    <div class="mt-16 text-center">
                        <p class="text-brand-ink/60 text-sm">
                            <strong>Also proudly serving families in:</strong><br>
                            <?php echo esc_html(implode(', ', $neighborhoods)); ?>.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Hyper-Local FAQ for SEO Snippets -->
        <section class="py-20 bg-brand-cream border-t border-brand-ink/5">
            <div class="max-w-4xl mx-auto px-4">
                <h2 class="font-serif text-3xl font-bold text-brand-ink mb-10 text-center">Questions about Childcare in
                    <?php echo esc_html($city); ?>
                </h2>

                <div class="space-y-4">
                    <details
                        class="group bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5 cursor-pointer focus-within:ring-2 focus-within:ring-chroma-blue focus-within:ring-offset-2">
                        <summary
                            class="flex items-center justify-between font-bold text-brand-ink list-none focus:outline-none">
                            <span>Do you offer GA Lottery Pre-K in <?php echo esc_html($city); ?>?</span>
                            <span class="text-chroma-blue group-open:rotate-180 transition-transform"><i
                                    class="fa-solid fa-chevron-down"></i></span>
                        </summary>
                        <p class="mt-3 text-sm text-brand-ink">Yes! Our locations serving
                            <?php echo esc_html($city); ?> participate in the Georgia Lottery Pre-K program. It is
                            tuition-free for all 4-year-olds living in Georgia.
                        </p>
                    </details>

                    <details
                        class="group bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5 cursor-pointer focus-within:ring-2 focus-within:ring-chroma-blue focus-within:ring-offset-2">
                        <summary
                            class="flex items-center justify-between font-bold text-brand-ink list-none focus:outline-none">
                            <span>Do you provide transportation from <?php echo esc_html($city); ?> schools?</span>
                            <span class="text-chroma-blue group-open:rotate-180 transition-transform"><i
                                    class="fa-solid fa-chevron-down"></i></span>
                        </summary>
                        <p class="mt-3 text-sm text-brand-ink">We provide safe bus transportation from most major
                            elementary schools in the <?php echo esc_html($county); ?> School District. Check the
                            specific campus page for a full list.</p>
                    </details>

                    <details
                        class="group bg-white rounded-2xl p-6 shadow-sm border border-brand-ink/5 cursor-pointer focus-within:ring-2 focus-within:ring-chroma-blue focus-within:ring-offset-2">
                        <summary
                            class="flex items-center justify-between font-bold text-brand-ink list-none focus:outline-none">
                            <span>What ages do you accept at your <?php echo esc_html($city); ?> centers?</span>
                            <span class="text-chroma-blue group-open:rotate-180 transition-transform"><i
                                    class="fa-solid fa-chevron-down"></i></span>
                        </summary>
                        <p class="mt-3 text-sm text-brand-ink">We serve children from 6 weeks old (Infant Care) up to
                            12 years old (After School). We also offer a Private Kindergarten option at select
                            locations.</p>
                    </details>
                </div>
            </div>
        </section>

    </main>

    <footer class="bg-brand-ink text-white py-12 text-center text-sm opacity-60">
        <p>© <?php echo date('Y'); ?> Chroma Early Learning. Proudly serving <?php echo esc_html($city); ?> and
            <?php echo esc_html($county); ?> County.
        </p>
    </footer>
    <?php wp_footer(); ?>
</body>

</html>
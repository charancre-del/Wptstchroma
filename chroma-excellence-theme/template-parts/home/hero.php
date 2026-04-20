<?php
/**
 * Home Hero Section
 *
 * @package Chroma_Excellence
 */

$hero = chroma_home_hero();

// Get the static front page ID
$home_id = get_option('page_on_front');

// Get hero image sources
$hero_image = get_theme_mod('chroma_home_hero_image');
$hero_video_path = get_template_directory() . '/assets/video/hero-classroom.mp4';
$hero_video_url = get_template_directory_uri() . '/assets/video/hero-classroom.mp4';
$loc_count = wp_count_posts('location')->publish ?? 19;
$location_pill = sprintf($hero['pill_format'], $loc_count);
?>

<section
    class="relative overflow-hidden bg-gradient-to-br from-brand-cream via-white to-chroma-yellowLight pt-20 pb-20 lg:pt-24">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute -top-24 left-10 w-96 h-96 bg-chroma-red/10 blur-3xl"></div>
        <div class="absolute top-20 right-16 w-80 h-80 bg-chroma-blue/10 blur-[120px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 grid lg:grid-cols-2 gap-14 items-center overflow-x-hidden">
        <div class="space-y-8 fade-in-up min-w-0">
            <div
                class="inline-flex max-w-full items-center gap-2 bg-white border border-chroma-blue/15 px-3 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-semibold text-brand-ink shadow-soft text-left break-words sm:whitespace-nowrap">
                <span class="w-2 h-2 rounded-full bg-chroma-blue animate-pulse"></span>
                <?php echo esc_html($location_pill); ?>
            </div>

            <h1 class="font-serif text-brand-ink text-4xl sm:text-[3.4rem] leading-tight tracking-tight break-words max-w-full">
                <?php echo wp_kses_post($hero['heading']); ?>
            </h1>

            <p class="text-[15px] leading-relaxed text-brand-ink max-w-full lg:max-w-xl">
                <?php echo esc_html($hero['subheading']); ?>
            </p>
            <p class="text-sm leading-relaxed text-brand-ink/80 max-w-full lg:max-w-xl">
                <?php echo esc_html($hero['supporting_text']); ?>
            </p>

            <div class="flex flex-col sm:flex-row gap-4 sm:items-center">
                <a href="<?php echo esc_url($hero['cta_url']); ?>"
                    class="inline-flex w-full sm:w-auto items-center justify-center px-8 py-4 rounded-full bg-chroma-red text-white text-xs font-semibold uppercase tracking-[0.22em] shadow-soft hover:bg-chroma-red/90 transition">
                    <?php echo esc_html($hero['cta_label']); ?>
                </a>
                <a href="<?php echo esc_url($hero['secondary_url']); ?>"
                    class="inline-flex w-full sm:w-auto items-center justify-center px-7 py-3.5 rounded-full border border-chroma-blue/30 bg-white text-xs font-semibold uppercase tracking-[0.18em] text-brand-ink hover:border-chroma-blue hover:text-chroma-blue transition">
                    <?php echo esc_html($hero['secondary_label']); ?>
                </a>
            </div>

            <div class="flex min-w-0 flex-col sm:flex-row sm:flex-wrap items-start sm:items-center gap-3 sm:gap-5 text-[12px] text-brand-ink">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <span class="text-chroma-yellow text-lg">★★★★★</span>
                    <span><?php echo esc_html($hero['rating_label']); ?></span>
                </div>
                <div class="hidden sm:block w-[1px] h-5 bg-chroma-blue/20"></div>
                <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto min-w-0">
                    <span class="w-2 h-2 rounded-full bg-chroma-green"></span>
                    <span class="leading-relaxed"><?php echo esc_html($hero['quality_badge_text']); ?></span>
                </div>
            </div>
        </div>

        <div class="chroma-hero-lock relative w-full min-w-0 isolate mt-8 sm:mt-0"
            style="min-height:clamp(400px,42vw,500px);aspect-ratio:4/3;">
            <!-- Background Decorations -->
            <div
                class="absolute top-10 right-10 w-72 h-72 bg-chroma-greenLight rounded-[3rem] -z-10 rotate-3 hidden sm:block">
            </div>
            <div
                class="absolute bottom-6 left-6 w-72 h-72 bg-chroma-yellowLight rounded-full -z-10 blur-2xl opacity-70 hidden sm:block">
            </div>

            <!-- Main Image Frame - uses bg-brand-cream as placeholder until image loads -->
            <div class="absolute inset-0 sm:inset-y-0 sm:left-12 lg:left-16 sm:right-0 rounded-[2rem] sm:rounded-[3rem] overflow-hidden border border-white/10 shadow-soft z-0"
                style="background: linear-gradient(135deg, #FFFCF8 0%, #E3E9EC 100%);">
                <?php if ($hero_image): ?>
                    <!-- Priority 1: Customizer hero image -->
                    <img src="<?php echo esc_url($hero_image); ?>" class="w-full h-full object-cover no-lazy"
                        alt="<?php echo esc_attr($hero['image_alt']); ?>" width="800" height="600" fetchpriority="high" decoding="sync"
                        data-no-lazy="1" data-no-async="1" />
                <?php elseif ($home_id && has_post_thumbnail($home_id)): ?>
                    <!-- Priority 2: Homepage featured image -->
                    <?php echo get_the_post_thumbnail($home_id, 'hero-large', array(
                        'class' => 'w-full h-full object-cover no-lazy',
                        'fetchpriority' => 'high',
                        'decoding' => 'sync',
                        'data-no-lazy' => '1',
                        'data-no-async' => '1',
                        'width' => '800',
                        'height' => '600'
                    )); ?>
                <?php elseif (file_exists($hero_video_path)): ?>
                    <!-- Priority 3: Hero video file -->
                    <video autoplay muted playsinline loop class="w-full h-full object-cover" width="800" height="600"
                        preload="auto">
                        <source src="<?php echo esc_url($hero_video_url); ?>" type="video/mp4" />
                    </video>
                <?php else: ?>
                    <!-- Fallback: Gradient background -->
                    <div
                        class="w-full h-full bg-gradient-to-br from-chroma-blue/20 via-chroma-green/20 to-chroma-yellow/20 flex items-center justify-center">
                        <div class="text-center text-chroma-blueDark/30">
                            <i class="fa-solid fa-image text-6xl mb-4"></i>
                            <p class="text-sm font-semibold"><?php echo esc_html($hero['fallback_label']); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Badge (Kindergarten Ready) -->
            <div
                class="absolute bottom-6 left-4 right-4 sm:right-auto sm:-left-8 bg-white/90 backdrop-blur-md p-4 sm:p-5 rounded-2xl shadow-soft max-w-none sm:max-w-xs border border-white z-10 flex gap-3 sm:gap-4 items-center">
                <div
                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-chroma-blue/10 flex items-center justify-center text-chroma-blue text-lg sm:text-xl shrink-0">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <p class="font-bold text-xs sm:text-sm text-brand-ink">
                        <?php echo esc_html($hero['badge_heading']); ?>
                    </p>
                    <p class="text-[10px] sm:text-[11px] text-brand-ink">
                        <?php echo esc_html($hero['badge_text']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>


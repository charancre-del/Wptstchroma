<?php
/**
 * Home Hero Section
 *
 * @package Chroma_Excellence
 */

$hero    = chroma_home_hero();
$home_id = chroma_get_home_page_id();
?>

<section class="relative overflow-hidden bg-gradient-to-br from-brand-cream via-white to-chroma-yellowLight pt-20 pb-20 lg:pt-24">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute -top-24 left-10 w-96 h-96 bg-chroma-red/10 blur-3xl"></div>
        <div class="absolute top-20 right-16 w-80 h-80 bg-chroma-blue/10 blur-[120px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 lg:px-6 relative z-10 grid lg:grid-cols-2 gap-14 items-center">
        <div class="space-y-8 fade-in-up">
            <div class="inline-flex items-center gap-2 bg-white border border-chroma-blue/15 px-3 py-1.5 rounded-full text-[11px] uppercase tracking-[0.2em] font-semibold text-brand-ink/70 shadow-soft">
                <span class="w-2 h-2 rounded-full bg-chroma-blue animate-pulse"></span>
                19+ Metro Atlanta Locations
            </div>

            <h1 class="font-serif text-brand-ink text-[2.8rem] sm:text-[3.4rem] leading-tight tracking-tight">
                <?php echo wp_kses_post( $hero['heading'] ); ?>
            </h1>

            <p class="text-[15px] leading-relaxed text-brand-ink/70 max-w-xl">
                <?php echo esc_html( $hero['subheading'] ); ?>
            </p>

            <div class="flex flex-col sm:flex-row gap-4 sm:items-center">
                <a href="<?php echo esc_url( $hero['cta_url'] ); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-chroma-red text-white text-xs font-semibold uppercase tracking-[0.22em] shadow-soft hover:bg-chroma-red/90 transition">
                    <?php echo esc_html( $hero['cta_label'] ); ?>
                </a>
                <a href="<?php echo esc_url( $hero['secondary_url'] ); ?>" class="inline-flex items-center justify-center px-7 py-3.5 rounded-full border border-chroma-blue/30 bg-white text-xs font-semibold uppercase tracking-[0.18em] text-brand-ink hover:border-chroma-blue hover:text-chroma-blue transition">
                    <?php echo esc_html( $hero['secondary_label'] ); ?>
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-5 text-[12px] text-brand-ink/60">
                <div class="flex items-center gap-2">
                    <span class="text-chroma-yellow text-lg">★★★★★</span>
                    <span>4.8 Average Parent Rating</span>
                </div>
                <div class="hidden sm:block w-[1px] h-5 bg-chroma-blue/20"></div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-chroma-green"></span>
                    <span>Licensed • Quality Rated • GA Pre-K Partner</span>
                </div>
            </div>
        </div>

        <div class="relative w-full h-[430px] sm:h-[470px] lg:h-[500px] fade-in-up delay-200">
            <div class="absolute top-10 right-10 w-72 h-72 bg-chroma-greenLight rounded-[3rem] -z-10 rotate-3"></div>
            <div class="absolute bottom-6 left-6 w-72 h-72 bg-chroma-yellowLight rounded-full -z-10 blur-2xl opacity-70"></div>

            <div class="absolute inset-y-0 left-16 right-0 rounded-[3rem] overflow-hidden border border-white/10 shadow-soft">
                <?php if ( has_post_thumbnail( $home_id ) ) : ?>
                    <?php echo get_the_post_thumbnail( $home_id, 'hero-large', array( 'class' => 'w-full h-full object-cover' ) ); ?>
                <?php else : ?>
                    <video autoplay muted playsinline loop class="w-full h-full object-cover">
                        <source src="<?php echo get_template_directory_uri(); ?>/assets/video/hero-classroom.mp4" type="video/mp4" />
                    </video>
                <?php endif; ?>
            </div>

            <div class="absolute bottom-6 -left-8 bg-white/90 backdrop-blur-md p-5 rounded-2xl shadow-soft max-w-xs border border-white">
                <div class="flex gap-4 items-center">
                    <div class="w-12 h-12 rounded-xl bg-chroma-blue/10 flex items-center justify-center text-chroma-blue text-xl">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <p class="font-bold text-sm text-brand-ink">Kindergarten Ready</p>
                        <p class="text-[11px] text-brand-ink/60">Comprehensive Prep</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

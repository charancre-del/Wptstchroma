<?php
/**
 * Template Name: Employers
 * Corporate partnerships and employer benefits
 *
 * @package Chroma_Excellence
 */

get_header();
?>

<main id="primary" class="site-main">
    
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-chroma-yellow to-chroma-red py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                <?php the_title(); ?>
            </h1>
            <?php if ( has_excerpt() ) : ?>
                <p class="text-xl text-white/90 max-w-3xl mx-auto">
                    <?php the_excerpt(); ?>
                </p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <?php while ( have_posts() ) : the_post(); ?>
                    <div class="prose prose-lg max-w-none">
                        <?php the_content(); ?>
                    </div>
                <?php endwhile; ?>
            </article>
        </div>
    </section>

    <!-- Benefits Grid -->
    <?php
$employer_benefits = get_post_meta( get_the_ID(), 'employer_benefits', true );
    if ( $employer_benefits ) :
    ?>
    <section class="py-16 bg-brand-cream">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-brand-ink mb-10 text-center">
                Partnership Benefits
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ( $employer_benefits as $benefit ) : ?>
                    <div class="bg-white rounded-xl p-8 shadow-md hover:shadow-lg transition-shadow">
                        <?php if ( ! empty( $benefit['icon'] ) ) : ?>
                            <div class="text-chroma-yellow text-4xl mb-4">
                                <i class="<?php echo esc_attr( $benefit['icon'] ); ?>"></i>
                            </div>
                        <?php endif; ?>
                        <h3 class="text-xl font-bold text-brand-ink mb-3">
                            <?php echo esc_html( $benefit['title'] ); ?>
                        </h3>
                        <p class="text-brand-ink/70">
                            <?php echo esc_html( $benefit['description'] ); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Partnership Types -->
    <?php
$partnership_types = get_post_meta( get_the_ID(), 'partnership_types', true );
    if ( $partnership_types ) :
    ?>
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-brand-ink mb-10 text-center">
                Partnership Options
            </h2>
            <div class="space-y-8">
                <?php foreach ( $partnership_types as $type ) : ?>
                    <div class="bg-gradient-to-r from-brand-cream to-white rounded-xl p-8 shadow-md">
                        <h3 class="text-2xl font-bold text-brand-ink mb-4">
                            <?php echo esc_html( $type['name'] ); ?>
                        </h3>
                        <p class="text-brand-ink/70 mb-4">
                            <?php echo esc_html( $type['description'] ); ?>
                        </p>
                        <?php if ( ! empty( $type['features'] ) ) : ?>
                            <ul class="space-y-2">
                                <?php foreach ( explode( "\n", $type['features'] ) as $feature ) : ?>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-chroma-teal mr-3 mt-1"></i>
                                        <span class="text-brand-ink/80"><?php echo esc_html( trim( $feature ) ); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Testimonials -->
    <?php
$testimonials = get_post_meta( get_the_ID(), 'employer_testimonials', true );
    if ( $testimonials ) :
    ?>
    <section class="py-16 bg-brand-cream">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-brand-ink mb-10 text-center">
                What Our Partners Say
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach ( $testimonials as $testimonial ) : ?>
                    <div class="bg-white rounded-xl p-8 shadow-md">
                        <div class="text-chroma-yellow text-3xl mb-4">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="text-brand-ink/80 mb-6 italic">
                            <?php echo esc_html( $testimonial['quote'] ); ?>
                        </p>
                        <div class="flex items-center">
                            <div>
                                <div class="font-bold text-brand-ink">
                                    <?php echo esc_html( $testimonial['name'] ); ?>
                                </div>
                                <div class="text-sm text-brand-ink/60">
                                    <?php echo esc_html( $testimonial['company'] ); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Contact CTA -->
    <section class="py-16 bg-gradient-to-r from-chroma-yellow to-chroma-red">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">
                Ready to Partner?
            </h2>
            <p class="text-xl text-white/90 mb-8">
                Let's discuss how Chroma can support your employees and their families.
            </p>
            <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="inline-block bg-white text-chroma-yellow px-8 py-4 rounded-lg font-bold text-lg hover:bg-white/90 transition-colors">
                Contact Our Team
            </a>
        </div>
    </section>

</main>

<?php
get_footer();

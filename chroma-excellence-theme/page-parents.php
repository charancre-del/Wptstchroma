<?php
/**
 * Template Name: Parents & Resources
 * Parent resources, guides, and helpful information
 *
 * @package Chroma_Excellence
 */

get_header();
?>

<main id="primary" class="site-main">
    
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-chroma-green to-chroma-teal py-16">
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

    <!-- Resource Cards -->
    <?php
$resources = chroma_get_meta_value( get_the_ID(), 'parent_resources', array() );
    if ( $resources ) :
    ?>
    <section class="py-16 bg-brand-cream">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-brand-ink mb-8 text-center">
                Helpful Resources
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ( $resources as $resource ) : ?>
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                        <?php if ( ! empty( $resource['icon'] ) ) : ?>
                            <div class="text-chroma-teal text-4xl mb-4">
                                <i class="<?php echo esc_attr( $resource['icon'] ); ?>"></i>
                            </div>
                        <?php endif; ?>
                        <h3 class="text-xl font-bold text-brand-ink mb-3">
                            <?php echo esc_html( $resource['title'] ); ?>
                        </h3>
                        <p class="text-brand-ink/70 mb-4">
                            <?php echo esc_html( $resource['description'] ); ?>
                        </p>
                        <?php if ( ! empty( $resource['link'] ) ) : ?>
                            <a href="<?php echo esc_url( $resource['link'] ); ?>" class="text-chroma-teal font-semibold hover:text-chroma-teal/80">
                                Learn More →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Contact CTA -->
    <section class="py-16 bg-gradient-to-r from-chroma-teal to-chroma-green">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">
                Have Questions?
            </h2>
            <p class="text-xl text-white/90 mb-8">
                Our team is here to help. Reach out anytime.
            </p>
            <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="inline-block bg-white text-chroma-teal px-8 py-4 rounded-lg font-bold text-lg hover:bg-white/90 transition-colors">
                Contact Us
            </a>
        </div>
    </section>

</main>

<?php
get_footer();

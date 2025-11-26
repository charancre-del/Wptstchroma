<?php
/**
 * Template: Default Page
 * Generic page template with flexible content support
 *
 * @package Chroma_Excellence
 */

get_header();
?>

<main id="primary" class="site-main">
    
    <?php while ( have_posts() ) : the_post(); ?>
        
        <!-- Page Header -->
        <section class="bg-gradient-to-r from-chroma-teal to-chroma-green py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    <?php the_title(); ?>
                </h1>
                <?php if ( has_excerpt() ) : ?>
                    <div class="text-xl text-white/90 max-w-3xl">
                        <?php the_excerpt(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Page Content -->
        <section class="py-16 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="prose prose-lg max-w-none">
                        <?php the_content(); ?>
                    </div>
                    
                    <?php
                    wp_link_pages( array(
                        'before' => '<div class="page-links mt-8 pt-8 border-t">' . esc_html__( 'Pages:', 'chroma-excellence' ),
                        'after'  => '</div>',
                    ) );
                    ?>
                </article>
            </div>
        </section>

        <?php
        // If comments are open or we have at least one comment, load up the comment template
        if ( comments_open() || get_comments_number() ) :
            comments_template();
        endif;
        ?>

    <?php endwhile; ?>

</main>

<?php
get_footer();

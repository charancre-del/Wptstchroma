<?php
/**
 * Template: Default Page
 * Generic page template with flexible content support
 *
 * @package Chroma_Excellence
 */

get_header();
?>

<main id="primary" class="site-main bg-white">

    <?php while ( have_posts() ) : the_post(); ?>

        <section class="pageHero chroma-v2-page-hero relative overflow-hidden bg-white pt-14 pb-12 lg:pt-20 lg:pb-16 border-b border-chroma-blue/10">
            <div class="absolute top-0 right-0 w-[32rem] h-[32rem] bg-chroma-greenLight/35 rounded-full blur-3xl translate-x-1/3 -translate-y-1/3" aria-hidden="true"></div>
            <div class="absolute bottom-0 left-0 w-[26rem] h-[26rem] bg-chroma-redLight/45 rounded-full blur-3xl -translate-x-1/3 translate-y-1/2" aria-hidden="true"></div>
            <div class="max-w-5xl mx-auto px-4 lg:px-6 text-center relative z-10">
                <span class="inline-flex items-center gap-2 rounded-full bg-chroma-yellow/10 border border-chroma-yellow/15 px-5 py-2.5 text-[11px] font-bold uppercase tracking-[0.2em] text-brand-ink/70 mb-7">
                    <span class="w-2 h-2 rounded-full bg-chroma-yellow"></span>
                    <?php esc_html_e( 'Chroma Early Learning', 'chroma-excellence' ); ?>
                </span>
                <h1 class="font-serif text-[2.65rem] md:text-6xl lg:text-7xl text-brand-ink tracking-[-0.04em] leading-[0.95] mb-6">
                    <?php echo esc_html( rtrim( get_the_title(), '.' ) . '.' ); ?>
                </h1>
                <?php if ( has_excerpt() ) : ?>
                    <div class="text-lg md:text-xl text-brand-ink/75 leading-relaxed max-w-3xl mx-auto">
                        <?php the_excerpt(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="py-16 lg:py-20 bg-brand-cream">
            <div class="max-w-5xl mx-auto px-4 lg:px-6">
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'bg-white rounded-[2.5rem] border border-chroma-blue/10 shadow-soft p-7 md:p-10 lg:p-12' ); ?>>
                    <div class="prose prose-lg max-w-none prose-headings:font-serif prose-headings:text-brand-ink prose-p:text-brand-ink/80 prose-a:text-chroma-blue hover:prose-a:text-brand-ink prose-strong:text-brand-ink prose-li:text-brand-ink/80">
                        <?php the_content(); ?>
                    </div>

                    <?php
                    wp_link_pages( array(
                        'before' => '<div class="page-links mt-8 pt-8 border-t border-brand-ink/10 text-sm font-semibold text-brand-ink/70">' . esc_html__( 'Pages:', 'chroma-excellence' ),
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

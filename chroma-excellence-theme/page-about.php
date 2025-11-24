<?php
/**
 * Template Name: About
 * About page with team, mission, and values
 *
 * @package Chroma_Excellence
 */

get_header();
?>

<main id="primary" class="site-main">
    
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-brand-navy to-brand-ink py-16">
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

    <!-- Mission & Values -->
    <?php
$mission = get_post_meta( get_the_ID(), 'about_mission', true );
$values  = get_post_meta( get_the_ID(), 'about_values', true );
    if ( $mission || $values ) :
    ?>
    <section class="py-16 bg-brand-cream">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                
                <?php if ( $mission ) : ?>
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <div class="text-chroma-teal text-4xl mb-4">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-brand-ink mb-4">Our Mission</h2>
                    <div class="prose prose-lg text-brand-ink/80">
                        <?php echo wp_kses_post( wpautop( $mission ) ); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $values ) : ?>
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <div class="text-chroma-yellow text-4xl mb-4">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-brand-ink mb-4">Our Values</h2>
                    <div class="prose prose-lg text-brand-ink/80">
                        <?php echo wp_kses_post( wpautop( $values ) ); ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Team Section -->
    <?php
    $team_query = new WP_Query(
        array(
            'post_type'      => 'team_member',
            'posts_per_page' => -1,
            'orderby'        => array(
                'menu_order' => 'ASC',
                'title'      => 'ASC',
            ),
        )
    );

    if ( $team_query->have_posts() ) :
    ?>
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-brand-ink mb-8 text-center">
                Meet Our Team
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php
                while ( $team_query->have_posts() ) :
                    $team_query->the_post();
                    $title = get_post_meta( get_the_ID(), 'team_member_title', true );
                    ?>
                    <div class="text-center">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'medium', array( 'class' => 'w-48 h-48 rounded-full mx-auto mb-4 object-cover', 'alt' => esc_attr( get_the_title() ) ) ); ?>
                        <?php endif; ?>
                        <h3 class="text-xl font-bold text-brand-ink mb-1">
                            <?php the_title(); ?>
                        </h3>
                        <?php if ( ! empty( $title ) ) : ?>
                            <p class="text-chroma-teal font-semibold mb-3">
                                <?php echo esc_html( $title ); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ( get_the_content() ) : ?>
                            <div class="text-brand-ink/70 text-sm prose prose-sm mx-auto">
                                <?php echo wp_kses_post( wpautop( get_the_content() ) ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php
        wp_reset_postdata();
    endif;
    ?>

    <!-- CTA Section -->
    <section class="py-16 bg-gradient-to-r from-chroma-teal to-chroma-green">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">
                Join the Chroma Family
            </h2>
            <p class="text-xl text-white/90 mb-8">
                Experience the difference of Prismpath education.
            </p>
            <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="inline-block bg-white text-chroma-teal px-8 py-4 rounded-lg font-bold text-lg hover:bg-white/90 transition-colors">
                Schedule Your Tour
            </a>
        </div>
    </section>

</main>

<?php
get_footer();

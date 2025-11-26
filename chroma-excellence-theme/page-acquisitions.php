<?php
/**
 * Template Name: Acquisitions
 * Acquisition opportunities form and information
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
                    <div class="prose prose-lg max-w-none mb-12">
                        <?php the_content(); ?>
                    </div>
                <?php endwhile; ?>
            </article>
        </div>
    </section>

    <!-- Benefits Section -->
    <?php
$benefits = get_post_meta( get_the_ID(), 'acquisition_benefits', true );
    if ( $benefits ) :
    ?>
    <section class="py-16 bg-brand-cream">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-brand-ink mb-10 text-center">
                Why Partner With Chroma?
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ( $benefits as $benefit ) : ?>
                    <div class="bg-white rounded-xl p-8 shadow-md">
                        <?php if ( ! empty( $benefit['icon'] ) ) : ?>
                            <div class="text-chroma-teal text-4xl mb-4">
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

    <!-- Acquisition Form -->
    <section class="py-16 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-brand-ink mb-4">
                    Start the Conversation
                </h2>
                <p class="text-xl text-brand-ink/80">
                    Fill out the form below and our acquisitions team will be in touch.
                </p>
            </div>
            
            <div class="bg-gradient-to-br from-brand-cream to-white rounded-xl shadow-lg p-8">
                <?php
                // Output acquisition form shortcode
                if ( shortcode_exists( 'chroma_acquisition_form' ) ) {
                    echo do_shortcode( '[chroma_acquisition_form]' );
                } else {
                    ?>
                    <div class="text-center text-brand-ink/60 py-8">
                        <p class="mb-4">Acquisitions form plugin not activated.</p>
                        <p class="text-sm">Please activate the "Chroma Acquisitions Form" plugin to display the acquisition form.</p>
                        <p class="mt-6">In the meantime, reach out to:</p>
                        <p class="font-semibold text-chroma-teal mt-2">
                            <a href="mailto:acquisitions@chromaela.com">acquisitions@chromaela.com</a>
                        </p>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Process Timeline -->
    <?php
$process_steps = get_post_meta( get_the_ID(), 'acquisition_process', true );
    if ( $process_steps ) :
    ?>
    <section class="py-16 bg-brand-cream">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-brand-ink mb-10 text-center">
                Our Process
            </h2>
            <div class="space-y-6">
                <?php foreach ( $process_steps as $index => $step ) : ?>
                    <div class="flex items-start gap-6 bg-white rounded-lg p-6 shadow-md">
                        <div class="flex-shrink-0 w-12 h-12 bg-chroma-teal text-white rounded-full flex items-center justify-center font-bold text-xl">
                            <?php echo esc_html( $index + 1 ); ?>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-brand-ink mb-2">
                                <?php echo esc_html( $step['title'] ); ?>
                            </h3>
                            <p class="text-brand-ink/70">
                                <?php echo esc_html( $step['description'] ); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php
get_footer();

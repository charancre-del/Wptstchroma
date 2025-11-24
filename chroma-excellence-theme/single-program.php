<?php
/**
 * Single Program Template
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();
?>

<?php while ( have_posts() ) : the_post();
        $program_id  = get_the_ID();
        $anchor_slug = chroma_get_program_anchor_slug( $program_id );
        $meta_tags   = chroma_get_program_meta_tags( $program_id );
        $faq_items   = chroma_get_program_faq_items( $program_id );

        $program_order = get_posts(
                array(
                        'post_type'      => 'program',
                        'fields'         => 'ids',
                        'orderby'        => 'menu_order title',
                        'order'          => 'ASC',
                        'posts_per_page' => -1,
                )
        );

        $current_index = array_search( $program_id, $program_order, true );
        $prev_id       = false !== $current_index && $current_index > 0 ? $program_order[ $current_index - 1 ] : null;
        $next_id       = false !== $current_index && $current_index < ( count( $program_order ) - 1 ) ? $program_order[ $current_index + 1 ] : null;
?>
        <article id="program-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="bg-gradient-to-br from-chroma-redLight to-white py-16 border-b border-brand-navy/10">
                        <div class="max-w-6xl mx-auto px-4 lg:px-6">
                                <?php if ( $age_range = chroma_get_meta_value( $program_id, 'program_age_range' ) ) : ?>
                                        <?php chroma_eyebrow( $age_range, 'teal' ); ?>
                                <?php endif; ?>
                                <h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-4"><?php the_title(); ?></h1>
                                <?php if ( has_excerpt() ) : ?>
                                        <p class="text-lg text-brand-ink/70"><?php the_excerpt(); ?></p>
                                <?php endif; ?>
                        </div>
                        <div class="max-w-6xl mx-auto px-4 lg:px-6 mt-6">
                                <div class="flex flex-wrap gap-3 text-sm text-brand-ink/70 items-center">
                                        <a class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white/70 border border-brand-navy/10 hover:border-chroma-teal hover:text-chroma-teal transition" href="<?php echo esc_url( home_url( '/programs#' . $anchor_slug ) ); ?>">← Programs overview</a>
                                        <?php if ( $prev_id ) : ?>
                                                <a class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white/70 border border-brand-navy/10 hover:border-chroma-teal hover:text-chroma-teal transition" href="<?php echo esc_url( get_permalink( $prev_id ) ); ?>">← <?php echo esc_html( get_the_title( $prev_id ) ); ?></a>
                                        <?php endif; ?>
                                        <?php if ( $next_id ) : ?>
                                                <a class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white/70 border border-brand-navy/10 hover:border-chroma-teal hover:text-chroma-teal transition" href="<?php echo esc_url( get_permalink( $next_id ) ); ?>"><?php echo esc_html( get_the_title( $next_id ) ); ?> →</a>
                                        <?php endif; ?>
                                </div>
                        </div>
                </div>

                <div class="max-w-4xl mx-auto px-4 lg:px-6 py-16">
                        <div class="prose prose-lg max-w-none">
                                <?php the_content(); ?>
                        </div>
                </div>

                <?php
                $locations = (array) get_post_meta( $program_id, 'program_locations', true );
                $locations = array_filter( array_map( 'intval', $locations ) );

                if ( ! empty( $locations ) ) :
                        ?>
                        <div class="bg-gradient-to-br from-chroma-blue/5 to-white py-16 border-t border-brand-navy/5">
                                <div class="max-w-6xl mx-auto px-4 lg:px-6">
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                                                <div>
                                                        <p class="text-xs font-semibold tracking-[0.2em] uppercase text-chroma-blue">Served at these locations</p>
                                                        <h2 class="text-3xl font-serif font-bold text-brand-ink">Find this program near you</h2>
                                                </div>
                                                <p class="text-brand-ink/70 max-w-xl">Connect with the centers that currently offer this program to schedule a tour or ask questions.</p>
                                        </div>
                                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                                                <?php foreach ( $locations as $location_id ) :
                                                        if ( 'publish' !== get_post_status( $location_id ) ) {
                                                                continue;
                                                        }

                                                        $address    = chroma_location_address_line( $location_id );
                                                        $city_state = chroma_location_city_state( $location_id );
                                                        ?>
                                                        <article class="bg-white border border-chroma-blue/10 rounded-2xl p-5 shadow-soft hover:shadow-card transition">
                                                                <h3 class="text-xl font-serif font-bold text-brand-ink mb-2"><a class="hover:text-chroma-teal" href="<?php echo esc_url( get_permalink( $location_id ) ); ?>"><?php echo esc_html( get_the_title( $location_id ) ); ?></a></h3>
                                                                <?php if ( $address ) : ?>
                                                                        <p class="text-sm text-brand-ink/70"><?php echo esc_html( $address ); ?></p>
                                                                <?php endif; ?>
                                                                <?php if ( $city_state ) : ?>
                                                                        <p class="text-sm text-brand-ink/60 mb-3"><?php echo esc_html( $city_state ); ?></p>
                                                                <?php endif; ?>
                                                                <a class="inline-flex items-center gap-2 text-chroma-teal font-semibold text-sm" href="<?php echo esc_url( get_permalink( $location_id ) ); ?>">View location →</a>
                                                        </article>
                                                <?php endforeach; ?>
                                        </div>
                                </div>
                        </div>
                <?php endif; ?>

                <?php
                $related_programs = new WP_Query(
                        array(
                                'post_type'      => 'program',
                                'posts_per_page' => 3,
                                'post__not_in'   => array( $program_id ),
                                'orderby'        => 'menu_order title',
                                'order'          => 'ASC',
                        )
                );

                if ( $related_programs->have_posts() ) :
                        ?>
                        <div class="max-w-6xl mx-auto px-4 lg:px-6 py-16">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                                        <div>
                                                <p class="text-xs font-semibold tracking-[0.2em] uppercase text-chroma-blue">More age bands</p>
                                                <h2 class="text-3xl font-serif font-bold text-brand-ink">Related programs</h2>
                                        </div>
                                        <p class="text-brand-ink/70 max-w-xl">Explore other programs families often consider next so you can guide them to the right fit.</p>
                                </div>
                                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <?php
                                        while ( $related_programs->have_posts() ) :
                                                $related_programs->the_post();
                                                $program_fields = chroma_get_program_fields();
                                                ?>
                                                <article class="bg-white border border-brand-navy/10 rounded-2xl p-5 shadow-card hover:shadow-soft transition">
                                                        <?php if ( $program_fields['age_range'] ) : ?>
                                                                <?php chroma_badge( $program_fields['age_range'], 'teal' ); ?>
                                                        <?php endif; ?>
                                                        <h3 class="text-xl font-serif font-bold text-brand-ink mt-3 mb-2"><a class="hover:text-chroma-teal" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                                        <?php if ( $program_fields['excerpt'] ) : ?>
                                                                <p class="text-sm text-brand-ink/70 mb-3"><?php echo esc_html( $program_fields['excerpt'] ); ?></p>
                                                        <?php else : ?>
                                                                <p class="text-sm text-brand-ink/70 mb-3"><?php echo esc_html( chroma_trimmed_excerpt( 24 ) ); ?></p>
                                                        <?php endif; ?>
                                                        <a class="inline-flex items-center gap-2 text-chroma-teal font-semibold text-sm" href="<?php the_permalink(); ?>">View details →</a>
                                                </article>
                                        <?php endwhile; ?>
                                </div>
                        </div>
                        <?php wp_reset_postdata(); ?>
                <?php endif; ?>

                <div class="max-w-4xl mx-auto px-4 lg:px-6 py-14">
                        <div class="bg-white border border-chroma-blue/15 rounded-3xl p-8 shadow-soft">
                                <p class="text-xs font-semibold tracking-[0.2em] uppercase text-chroma-blue mb-2">SEO focus</p>
                                <h2 class="text-2xl font-serif font-bold text-brand-ink mb-3"><?php echo esc_html( $meta_tags['title'] ); ?></h2>
                                <p class="text-brand-ink/70 mb-6 leading-relaxed"><?php echo esc_html( $meta_tags['description'] ); ?></p>

                                <?php if ( ! empty( $faq_items ) ) : ?>
                                        <div class="space-y-4">
                                                <h3 class="text-lg font-semibold text-brand-ink">Program FAQs</h3>
                                                <div class="space-y-3">
                                                        <?php foreach ( $faq_items as $faq ) : ?>
                                                                <details class="group border border-brand-navy/10 rounded-2xl p-4 bg-chroma-teal/5">
                                                                        <summary class="flex items-center justify-between cursor-pointer text-brand-ink font-semibold"><?php echo esc_html( $faq['question'] ); ?><span class="text-chroma-teal group-open:rotate-90 transition">→</span></summary>
                                                                        <div class="mt-3 text-brand-ink/80 leading-relaxed"><?php echo wp_kses_post( wpautop( $faq['answer'] ) ); ?></div>
                                                                </details>
                                                        <?php endforeach; ?>
                                                </div>
                                        </div>
                                <?php endif; ?>

                                <?php chroma_render_program_faq_schema( $faq_items ); ?>
                        </div>
                </div>

                <div class="bg-chroma-teal/10 py-16">
                        <div class="max-w-4xl mx-auto px-4 lg:px-6 text-center">
                                <h2 class="text-3xl font-serif font-bold text-brand-ink mb-4">Ready to enroll?</h2>
                                <p class="text-lg text-brand-ink/70 mb-8">Schedule a tour to see this program in action.</p>
                                <a href="<?php echo home_url( '/contact#tour' ); ?>" class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-chroma-teal text-white font-semibold hover:bg-brand-navy transition">
                                        Schedule a Tour
                                </a>
                        </div>
                </div>
        </article>
<?php endwhile; ?>

<?php get_footer(); ?>

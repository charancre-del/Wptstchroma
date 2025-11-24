<?php
/**
 * Programs Archive Template
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();
?>

<div class="bg-gradient-to-br from-chroma-teal/10 to-white py-16 border-b border-brand-navy/10">
	<div class="max-w-7xl mx-auto px-4 lg:px-6">
		<h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-4">Our Programs</h1>
		<p class="text-lg text-brand-ink/70 max-w-3xl">
			From infants to school-age children, we offer age-appropriate programs powered by our Prismpath™ curriculum.
		</p>
	</div>
</div>

<div class="max-w-7xl mx-auto px-4 lg:px-6 py-16">
        <?php if ( have_posts() ) : ?>
                <?php
                global $wp_query;
                $program_posts = $wp_query->posts;
                ?>

                <?php if ( ! empty( $program_posts ) ) : ?>
                        <div class="bg-white border border-chroma-blue/10 rounded-3xl shadow-soft p-6 mb-10">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                                        <div>
                                                <p class="text-xs font-semibold tracking-[0.2em] uppercase text-chroma-blue">Programs table of contents</p>
                                                <h2 class="text-xl font-serif font-bold text-brand-ink">Jump to an age group</h2>
                                        </div>
                                        <p class="text-sm text-brand-ink/70 max-w-xl">Use these quick links to explore our programs and share direct anchors with families or search engines.</p>
                                </div>
                                <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                        <?php foreach ( $program_posts as $program_post ) :
                                                $anchor_slug = chroma_get_program_anchor_slug( $program_post->ID );
                                                $age_range   = chroma_get_meta_value( $program_post->ID, 'program_age_range', '' );
                                                ?>
                                                <a class="flex items-center justify-between gap-3 border border-chroma-blue/15 rounded-2xl px-4 py-3 hover:border-chroma-blue hover:shadow-soft transition" href="<?php echo esc_url( '#' . $anchor_slug ); ?>">
                                                        <div>
                                                                <p class="text-sm font-semibold text-brand-ink"><?php echo esc_html( get_the_title( $program_post ) ); ?></p>
                                                                <?php if ( $age_range ) : ?>
                                                                        <span class="text-[12px] text-brand-ink/60"><?php echo esc_html( $age_range ); ?></span>
                                                                <?php endif; ?>
                                                        </div>
                                                        <span aria-hidden="true" class="text-chroma-teal">→</span>
                                                </a>
                                        <?php endforeach; ?>
                                </div>
                        </div>
                <?php endif; ?>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <?php while ( have_posts() ) : the_post();
                                $anchor_slug = chroma_get_program_anchor_slug();
                                $seo_fields  = chroma_get_program_seo_fields();
                                ?>
                                <article id="<?php echo esc_attr( $anchor_slug ); ?>" class="bg-white rounded-3xl overflow-hidden shadow-card hover:shadow-soft transition" data-program-anchor="<?php echo esc_attr( $anchor_slug ); ?>">
                                        <?php if ( $seo_fields['heading'] || $seo_fields['summary'] || ! empty( $seo_fields['highlights'] ) ) : ?>
                                                <div class="p-6 pb-0 space-y-3 bg-gradient-to-br from-chroma-blue/5 to-white border-b border-chroma-blue/10">
                                                        <?php if ( $seo_fields['heading'] ) : ?>
                                                                <h3 class="text-xl font-serif font-bold text-brand-ink"><?php echo esc_html( $seo_fields['heading'] ); ?></h3>
                                                        <?php endif; ?>
                                                        <?php if ( $seo_fields['summary'] ) : ?>
                                                                <p class="text-brand-ink/70 text-sm leading-relaxed"><?php echo esc_html( $seo_fields['summary'] ); ?></p>
                                                        <?php endif; ?>
                                                        <?php if ( ! empty( $seo_fields['highlights'] ) ) : ?>
                                                                <ul class="list-disc list-inside text-sm text-brand-ink/80 space-y-1">
                                                                        <?php foreach ( $seo_fields['highlights'] as $highlight ) : ?>
                                                                                <li><?php echo esc_html( $highlight ); ?></li>
                                                                        <?php endforeach; ?>
                                                                </ul>
                                                        <?php endif; ?>
                                                </div>
                                        <?php endif; ?>

                                        <?php if ( has_post_thumbnail() ) : ?>
                                                <div class="aspect-[4/3] overflow-hidden">
                                                        <?php the_post_thumbnail( 'program-card', array( 'class' => 'w-full h-full object-cover' ) ); ?>
                                                </div>
                                        <?php endif; ?>
                                        <div class="p-6">
                                                <?php if ( $age_range = chroma_get_meta_value( get_the_ID(), 'program_age_range' ) ) : ?>
                                                        <?php chroma_badge( $age_range, 'teal' ); ?>
                                                <?php endif; ?>
                                                <h2 class="text-2xl font-serif font-bold text-brand-ink mt-3 mb-2">
                                                        <a href="<?php the_permalink(); ?>" class="hover:text-chroma-teal">
                                                                <?php the_title(); ?>
                                                        </a>
                                                </h2>
                                                <p class="text-brand-ink/70 text-sm mb-4"><?php echo chroma_trimmed_excerpt( 20 ); ?></p>
                                                <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-2 text-chroma-teal font-semibold text-sm hover:text-brand-navy">
                                                        Learn more →
                                                </a>
                                        </div>
                                </article>
                        <?php endwhile; ?>
                </div>
                <?php chroma_archive_pagination(); ?>
        <?php endif; ?>
</div>

<?php get_footer(); ?>

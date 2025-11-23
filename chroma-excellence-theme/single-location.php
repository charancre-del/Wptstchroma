<?php
/**
 * Single Location Template
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();
?>

<?php while ( have_posts() ) : the_post(); ?>
	<article id="location-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="bg-gradient-to-br from-chroma-greenLight to-white py-16 border-b border-brand-navy/10">
			<div class="max-w-6xl mx-auto px-4 lg:px-6 text-center">
				<h1 class="text-4xl md:text-5xl font-serif font-bold text-brand-ink mb-4"><?php the_title(); ?></h1>
				<?php if ( $address = chroma_location_address_line() ) : ?>
					<p class="text-lg text-brand-ink/70 mb-2"><?php echo esc_html( $address ); ?></p>
				<?php endif; ?>
				<?php if ( $city_state = chroma_location_city_state() ) : ?>
					<p class="text-brand-ink/70"><?php echo esc_html( $city_state ); ?></p>
				<?php endif; ?>
				<?php if ( $phone = get_field( 'location_phone' ) ) : ?>
					<p class="mt-4"><a href="tel:<?php echo esc_attr( $phone ); ?>" class="text-chroma-teal font-semibold hover:text-brand-navy"><?php echo esc_html( $phone ); ?></a></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="max-w-4xl mx-auto px-4 lg:px-6 py-16">
			<div class="prose prose-lg max-w-none">
				<?php the_content(); ?>
			</div>
		</div>

		<?php if ( $lat = get_field( 'location_latitude' ) ) : ?>
			<div class="max-w-6xl mx-auto px-4 lg:px-6 py-16">
				<h2 class="text-2xl font-serif font-bold text-brand-ink mb-8 text-center">Visit Us</h2>
				<div
					data-chroma-map
					data-chroma-locations='[{"lat":<?php echo esc_attr( $lat ); ?>,"lng":<?php echo esc_attr( get_field( 'location_longitude' ) ); ?>,"name":"<?php echo esc_js( get_the_title() ); ?>","city":"<?php echo esc_js( get_field( 'location_city' ) ); ?>","url":"<?php echo esc_url( get_permalink() ); ?>"}]'
					class="w-full h-96 rounded-3xl overflow-hidden shadow-soft"
				></div>
			</div>
		<?php endif; ?>
	</article>
<?php endwhile; ?>

<?php get_footer(); ?>

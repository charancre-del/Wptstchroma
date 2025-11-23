<?php
/**
 * Locations Grid Section
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */
?>

<section id="locations" class="py-20 bg-white">
	<div class="max-w-7xl mx-auto px-4 lg:px-6">
		<div class="text-center mb-12">
			<h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">19+ neighborhood locations across Metro Atlanta</h2>
			<p class="text-brand-ink/70 text-sm md:text-base max-w-2xl mx-auto">Find a Chroma campus near your home or work. All locations share the same safety standards, curriculum framework, and warm Chroma culture.</p>
		</div>
		<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8 text-sm">
			<?php
			$locations = new WP_Query( array(
				'post_type'      => 'location',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			) );

			if ( $locations->have_posts() ) :
				$county_groups = array();
				while ( $locations->have_posts() ) : $locations->the_post();
					$county = get_field( 'county' ) ?: 'Other';
					if ( ! isset( $county_groups[ $county ] ) ) {
						$county_groups[ $county ] = array();
					}
					$county_groups[ $county ][] = get_the_ID();
				endwhile;
				wp_reset_postdata();

				foreach ( $county_groups as $county => $location_ids ) :
				?>
					<div>
						<div class="flex items-center gap-2 mb-3">
							<span class="text-xl">📍</span>
							<h3 class="font-semibold text-xs uppercase tracking-[0.18em] text-brand-ink/60"><?php echo esc_html( $county ); ?></h3>
						</div>
						<ul class="space-y-2">
							<?php foreach ( $location_ids as $loc_id ) :
								$enrollment_status = get_field( 'enrollment_status', $loc_id );
								$is_enrolling = ( $enrollment_status === 'Now Enrolling' );
							?>
								<li>
									<a href="<?php echo get_permalink( $loc_id ); ?>" class="block p-3 rounded-xl border <?php echo $is_enrolling ? 'border-chroma-red/70 bg-chroma-redLight/60' : 'border-chroma-blue/10 hover:border-chroma-blue/50 hover:bg-chroma-blueLight'; ?> transition">
										<p class="font-semibold text-brand-ink"><?php echo get_the_title( $loc_id ); ?></p>
										<p class="text-[11px] text-brand-ink/60"><?php echo esc_html( get_field( 'address', $loc_id ) ); ?></p>
										<?php if ( $is_enrolling ) : ?>
											<p class="text-[10px] font-semibold text-chroma-blue mt-1">Now Enrolling</p>
										<?php endif; ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach;
			endif;
			?>
		</div>
	</div>
</section>

<?php
/**
 * The template for displaying the footer
 *
 * @package Chroma_Excellence
 */
?>
</main>

<footer class="bg-brand-ink text-white py-12 px-4 lg:px-6">
	<div class="max-w-7xl mx-auto">
		<!-- Top Section -->
		<div class="grid md:grid-cols-2 lg:grid-cols-5 gap-8 mb-8">
			<!-- Logo and Description -->
			<div class="md:col-span-1">
				<a href="<?php echo chroma_get_localized_url(home_url('/')); ?>" class="block mb-4">
					<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_70x70.webp'); ?>"
						srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_70x70.webp'); ?> 1x,
								 <?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_140x140.webp'); ?> 2x"
						alt="Chroma Early Learning" width="48" height="48"
						class="h-12 w-12 opacity-90 hover:opacity-100 transition-opacity" />
				</a>
				<p class="text-[11px] text-white/80 leading-relaxed">
					<?php _e('Premium childcare & early education across Metro Atlanta.', 'chroma-excellence'); ?>
				</p>
			</div>

			<!-- Quick Links -->
			<div class="md:col-span-1">
				<h3 class="font-bold text-sm mb-3"><?php _e('Quick Links', 'chroma-excellence'); ?></h3>
				<div class="text-xs text-white/90 space-y-2">
					<?php chroma_footer_nav(); ?>
				</div>
			</div>

			<!-- Contact Info -->
			<div class="md:col-span-1">
				<h3 class="font-bold text-sm mb-3"><?php _e('Contact', 'chroma-excellence'); ?></h3>
				<div class="space-y-2 text-xs text-white/90">
					<?php
					// Get contact info from customizer (with fallback to global settings)
					$footer_phone = chroma_get_theme_mod('chroma_footer_phone', '') ?: chroma_global_phone();
					$footer_email = chroma_get_theme_mod('chroma_footer_email', '') ?: chroma_global_email();
					$footer_address = chroma_get_theme_mod('chroma_footer_address', '') ?: chroma_global_full_address();
					?>
					<?php if ($footer_phone): ?>
						<p><a href="tel:<?php echo esc_attr($footer_phone); ?>"
								class="hover:text-white"><?php echo esc_html($footer_phone); ?></a></p>
					<?php endif; ?>
					<?php if ($footer_email): ?>
						<p><a href="mailto:<?php echo esc_attr($footer_email); ?>"
								class="hover:text-white"><?php echo esc_html($footer_email); ?></a></p>
					<?php endif; ?>
					<?php if ($footer_address): ?>
						<p><?php echo esc_html($footer_address); ?></p>
					<?php endif; ?>

					<?php // Custom Contact Menu ?>
					<?php chroma_footer_contact_nav(); ?>
				</div>
			</div>

			<!-- Social Links -->
			<div class="md:col-span-1">
				<h3 class="font-bold text-sm mb-3"><?php _e('Connect With Us', 'chroma-excellence'); ?></h3>
				<div class="flex gap-3">
					<a href="https://www.facebook.com/ChromaPreschool/" target="_blank" rel="noopener noreferrer"
						class="w-12 h-12 flex items-center justify-center bg-white/10 rounded-full hover:bg-white/20 transition"
						aria-label="Visit our Facebook page">
						<i class="fa-brands fa-facebook-f text-lg"></i>
					</a>
					<a href="https://www.instagram.com/chromapreschool/" target="_blank" rel="noopener noreferrer"
						class="w-12 h-12 flex items-center justify-center bg-white/10 rounded-full hover:bg-white/20 transition"
						aria-label="Visit our Instagram page">
						<i class="fa-brands fa-instagram text-lg"></i>
					</a>
				</div>
			</div>

			<!-- Latest News -->
			<div class="md:col-span-1 lg:col-span-1">
				<h3 class="font-bold text-sm mb-4"><?php _e('Latest Blogs', 'chroma-excellence'); ?></h3>
				<?php
				$footer_blog_query = chroma_cached_query(
					array(
						'post_type' => 'post',
						'posts_per_page' => 2,
						'ignore_sticky_posts' => 1,
					),
					'footer_blog',
					DAY_IN_SECONDS
				);

				if ($footer_blog_query->have_posts()): ?>
					<div class="grid grid-cols-2 gap-4">
						<?php while ($footer_blog_query->have_posts()):
							$footer_blog_query->the_post(); ?>
							<a href="<?php the_permalink(); ?>" class="group block">
								<div class="aspect-video relative rounded-lg overflow-hidden bg-brand-ink/10 mb-2">
									<?php if (has_post_thumbnail()): ?>
										<?php the_post_thumbnail('medium', array('class' => 'w-full h-full object-cover transition-transform duration-300 group-hover:scale-105')); ?>
									<?php else: ?>
										<div
											class="w-full h-full flex items-center justify-center bg-brand-ink/5 text-brand-ink/20">
											<i class="fa-solid fa-newspaper text-xl"></i>
										</div>
									<?php endif; ?>
									<div
										class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300">
									</div>
								</div>
								<h4
									class="text-[10px] font-bold leading-tight group-hover:text-chroma-blue transition-colors line-clamp-3">
									<?php the_title(); ?>
								</h4>
								<span class="text-[9px] text-white/50 mt-1 block"><?php echo get_the_date('M j, Y'); ?></span>
							</a>
						<?php endwhile; ?>
					</div>
					<?php wp_reset_postdata(); ?>
				<?php else: ?>
					<p class="text-xs text-white/60"><?php _e('No recent updates.', 'chroma-excellence'); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Footer SEO Text (Tier 12 - SS) -->
		<?php
		$seo_text = chroma_get_theme_mod('chroma_footer_seo_text');
		if ($seo_text): ?>
			<div
				class="border-t border-white/10 pt-6 mb-6 text-[11px] text-white/60 leading-relaxed text-center max-w-5xl mx-auto">
				<?php echo wp_kses_post($seo_text); ?>
			</div>
		<?php endif; ?>

		<!-- Bottom Section -->
		<div
			class="border-t border-white/10 pt-6 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-white/80">
			<p>&copy; <?php echo date('Y'); ?>
				<?php _e('Chroma Early Learning Academy. All rights reserved.', 'chroma-excellence'); ?>
			</p>

			<div class="flex items-center gap-6">
				<?php
				if (function_exists('chroma_render_language_switcher')) {
					chroma_render_language_switcher();
				}
				?>
				<div class="flex gap-4 border-l border-white/10 pl-6">
					<a href="<?php echo chroma_get_localized_url(home_url('/privacy-policy/')); ?>"
						class="hover:text-white"><?php _e('Privacy Policy', 'chroma-excellence'); ?></a>
					<a href="<?php echo chroma_get_localized_url(home_url('/terms-of-service/')); ?>"
						class="hover:text-white"><?php _e('Terms of Service', 'chroma-excellence'); ?></a>
				</div>
			</div>
		</div>
	</div>
</footer>


<?php
// Global Sticky CTA Logic
$show_sticky_cta = true;
$sticky_text = __('Ready to experience the Chroma difference?', 'chroma-excellence');
$sticky_btn_text = __('Schedule a Tour', 'chroma-excellence');
$sticky_url = chroma_get_localized_url(home_url('/schedule-a-tour/'));

if (is_page('schedule-a-tour')) {
	$show_sticky_cta = false;
} elseif (is_singular('program')) {
	$sticky_text = sprintf(__('Ready to enroll in <strong>%s</strong>?', 'chroma-excellence'), get_the_title());
} elseif (is_singular('location')) {
	$sticky_text = sprintf(__('Ready to visit our <strong>%s</strong> campus?', 'chroma-excellence'), get_the_title());
} elseif (is_page('careers')) {
	$show_sticky_cta = false;
}

if ($show_sticky_cta):
	?>
	<div id="sticky-cta"
		class="md:hidden will-change-transform transform translate-y-full fixed bottom-0 left-0 right-0 bg-brand-ink/95 backdrop-blur-md text-white py-4 px-6 z-50 shadow-[0_-5px_20px_rgba(0,0,0,0.1)] border-t border-white/10 transition-transform duration-500 ease-out">
		<div
			class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left">
			<span class="text-sm md:text-base font-medium tracking-wide">
				<?php echo $sticky_text; // Allowed html tags ?>
			</span>
			<a href="<?php echo esc_url($sticky_url); ?>"
				class="inline-block bg-chroma-red text-white text-xs font-bold uppercase tracking-wider px-8 py-3 rounded-full hover:bg-white hover:text-chroma-red transition-all shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
				<?php echo esc_html($sticky_btn_text); ?>
			</a>
		</div>
	</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>

</html>
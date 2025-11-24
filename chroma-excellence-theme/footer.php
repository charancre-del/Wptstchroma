	</main>

	<footer class="bg-brand-ink text-white py-12 px-4 lg:px-6">
		<div class="max-w-7xl mx-auto">
			<!-- Top Section -->
			<div class="grid md:grid-cols-4 gap-8 mb-8">
				<!-- Logo and Description -->
				<div class="md:col-span-1">
					<div class="flex items-center gap-2 mb-4">
						<div class="flex -space-x-1">
							<span class="w-3 h-3 rounded-full bg-chroma-red"></span>
							<span class="w-3 h-3 rounded-full bg-chroma-yellow"></span>
							<span class="w-3 h-3 rounded-full bg-chroma-green"></span>
                                                        <span class="w-3 h-3 rounded-full bg-chroma-blue"></span>
						</div>
						<span class="font-semibold text-white text-sm">Chroma</span>
					</div>
					<p class="text-[11px] text-white/60 leading-relaxed">
						Premium childcare & early education across Metro Atlanta.
					</p>
				</div>

				<!-- Quick Links -->
				<div class="md:col-span-1">
					<h3 class="font-bold text-sm mb-3">Quick Links</h3>
					<nav class="space-y-2 text-xs text-white/70">
						<?php chroma_footer_nav(); ?>
					</nav>
				</div>

				<!-- Contact Info -->
				<div class="md:col-span-1">
					<h3 class="font-bold text-sm mb-3">Contact</h3>
					<div class="space-y-2 text-xs text-white/70">
						<?php if ( $phone = chroma_global_phone() ) : ?>
							<p><a href="tel:<?php echo esc_attr( $phone ); ?>" class="hover:text-white"><?php echo esc_html( $phone ); ?></a></p>
						<?php endif; ?>
						<?php if ( $email = chroma_global_email() ) : ?>
							<p><a href="mailto:<?php echo esc_attr( $email ); ?>" class="hover:text-white"><?php echo esc_html( $email ); ?></a></p>
						<?php endif; ?>
						<?php if ( $address = chroma_global_full_address() ) : ?>
							<p class="mt-2"><?php echo esc_html( $address ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<!-- Social Links -->
				<div class="md:col-span-1">
					<h3 class="font-bold text-sm mb-3">Follow Us</h3>
					<div class="flex gap-3">
                                                <?php if ( $facebook = chroma_global_facebook_url() ) : ?>
                                                        <a href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener" class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center hover:bg-white/20 transition">
                                                                <i class="fa-brands fa-facebook-f text-xs"></i>
                                                        </a>
                                                <?php endif; ?>
                                                <?php if ( $instagram = chroma_global_instagram_url() ) : ?>
                                                        <a href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener" class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center hover:bg-white/20 transition">
                                                                <i class="fa-brands fa-instagram text-xs"></i>
                                                        </a>
                                                <?php endif; ?>
                                                <?php if ( $linkedin = chroma_global_linkedin_url() ) : ?>
                                                        <a href="<?php echo esc_url( $linkedin ); ?>" target="_blank" rel="noopener" class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center hover:bg-white/20 transition">
                                                                <i class="fa-brands fa-linkedin-in text-xs"></i>
                                                        </a>
                                                <?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Bottom Section -->
			<div class="border-t border-white/10 pt-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 text-[11px] text-white/60">
				<p>&copy; <?php echo date( 'Y' ); ?> Chroma Early Learning Academy. All rights reserved.</p>
				<div class="flex gap-4">
					<a href="<?php echo esc_url( home_url( '/privacy-policy' ) ); ?>" class="hover:text-white">Privacy Policy</a>
					<a href="<?php echo esc_url( home_url( '/terms-of-service' ) ); ?>" class="hover:text-white">Terms of Service</a>
				</div>
			</div>
		</div>
	</footer>

	<?php wp_footer(); ?>
</body>
</html>

	</main>

	<footer class="bg-brand-ink text-white py-12 px-4 lg:px-6">
		<div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-5">
			<!-- Logo and Description -->
			<div class="flex items-center gap-3">
				<div class="flex -space-x-1">
					<span class="w-3 h-3 rounded-full bg-chroma-red"></span>
					<span class="w-3 h-3 rounded-full bg-chroma-yellow"></span>
					<span class="w-3 h-3 rounded-full bg-chroma-green"></span>
					<span class="w-3 h-3 rounded-full bg-chroma-blue"></span>
				</div>
				<div>
					<p class="font-semibold text-white text-sm">Chroma Early Learning Academy</p>
					<p class="text-[11px] text-white/60">
						<?php echo esc_html( get_field( 'footer_tagline', 'option' ) ?: 'Premium childcare & early education across Metro Atlanta.' ); ?>
					</p>
				</div>
			</div>

			<!-- Footer Info -->
			<div class="flex flex-wrap items-center gap-4 text-[11px] text-white/60">
				<span>&copy; <?php echo date( 'Y' ); ?> Chroma Early Learning Academy</span>
				<span class="hidden md:inline-block w-[1px] h-4 bg-white/20"></span>
				<span>All rights reserved</span>
			</div>
		</div>
	</footer>

	<?php wp_footer(); ?>
</body>
</html>

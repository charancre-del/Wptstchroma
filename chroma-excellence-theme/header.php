<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php // Canonical URL is handled by Yoast SEO and class-canonical-enforcer.php via wp_head ?>
	<link rel="preload" as="font"
		href="<?php echo get_template_directory_uri(); ?>/assets/webfonts/Outfit-Regular.woff2" type="font/woff2"
		crossorigin>
	<link rel="preload" as="font"
		href="<?php echo get_template_directory_uri(); ?>/assets/webfonts/Outfit-SemiBold.woff2" type="font/woff2"
		crossorigin>
	<link rel="preload" as="font" href="<?php echo get_template_directory_uri(); ?>/assets/webfonts/Outfit-Bold.woff2"
		type="font/woff2" crossorigin>
	<link rel="preload" as="font"
		href="<?php echo get_template_directory_uri(); ?>/assets/webfonts/PlayfairDisplay-SemiBold.woff2"
		type="font/woff2" crossorigin>
	<link rel="preload" as="font"
		href="<?php echo get_template_directory_uri(); ?>/assets/webfonts/PlayfairDisplay-Bold.woff2" type="font/woff2"
		crossorigin>
	<link rel="preload" as="font"
		href="<?php echo get_template_directory_uri(); ?>/assets/webfonts/PlayfairDisplay-ExtraBold.woff2"
		type="font/woff2" crossorigin>

	<?php
	// Hero Image LCP Preload
	if (is_front_page()) {
		$chroma_hero_url = get_theme_mod('chroma_home_hero_image');
		if (!$chroma_hero_url) {
			$chroma_front_id = get_option('page_on_front');
			if ($chroma_front_id && has_post_thumbnail($chroma_front_id)) {
				$chroma_hero_url = get_the_post_thumbnail_url($chroma_front_id, 'hero-large');
			}
		}
		if ($chroma_hero_url) {
			echo '<link rel="preload" as="image" href="' . esc_url($chroma_hero_url) . '" fetchpriority="high">';
		}
	}
	?>

	<?php
	$branding = Chroma_Branding_Engine::get_instance();
	$favicon = $branding->get_setting('assets', 'favicon_url');
	if ($favicon): ?>
		<link rel="icon" href="<?php echo esc_url($favicon); ?>" />
	<?php endif; ?>

	<!-- Tier 3: Instant Navigation (Speculation Rules API) -->
	<script type="speculationrules">
	{
		"prerender": [
			{
				"source": "document",
				"where": {
					"and": [
						{ "href_matches": "/*" },
						{ "not": { "href_matches": "/wp-admin/*" } }
					]
				},
				"eagerness": "moderate"
			}
		]
	}
	</script>

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

	<!-- Skip Links for Accessibility -->
	<a href="#main-content"
		class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-white text-brand-ink p-4 z-50 rounded-lg shadow-lg"><?php _e('Skip to content', 'chroma-excellence'); ?></a>

	<header class="fixed w-full top-0 z-50 transition-all duration-300 bg-white/95 backdrop-blur-sm shadow-sm"
		data-header>
		<div class="max-w-7xl mx-auto px-4 lg:px-6 h-20 lg:h-24 flex items-center justify-between">

			<!-- Logo -->
			<a href="<?php echo esc_url(chroma_url('/')); ?>" class="flex items-center gap-4 group">
				<?php
				$logo_width_desktop = get_theme_mod('chroma_logo_width_desktop', 70);
				$logo_width_mobile = get_theme_mod('chroma_logo_width_mobile', 56);
				?>
				<style>
					.chroma-logo {
						width:
							<?php echo intval($logo_width_mobile); ?>
							px;
						height:
							<?php echo intval($logo_width_mobile); ?>
							px;
					}

					@media (min-width: 1024px) {
						.chroma-logo {
							width:
								<?php echo intval($logo_width_desktop); ?>
								px;
							height:
								<?php echo intval($logo_width_desktop); ?>
								px;
						}
					}
				</style>
				<?php
				$logo_url = Chroma_Branding_Engine::get_instance()->get_setting('assets', 'logo_url');
				$logo_url = $logo_url ?: get_template_directory_uri() . '/assets/images/logo_chromacropped_70x70.webp';
				?>
				<img src="<?php echo esc_url($logo_url); ?>"
					alt="<?php echo esc_attr__('Chroma Early Learning', 'chroma-excellence'); ?>"
					width="<?php echo intval($logo_width_mobile); ?>" height="<?php echo intval($logo_width_mobile); ?>"
					fetchpriority="high" loading="eager"
					class="chroma-logo transition-transform duration-300 group-hover:scale-105 no-lazy"
					data-no-lazy="1" />

				<!-- Header Text -->
				<?php
				$header_text = chroma_get_theme_mod('chroma_header_text', "Early Learning\nAcademy");
				$lines = explode("\n", $header_text);
				$first_line = array_shift($lines);
				?>
				<div class="block leading-tight">
					<span class="block font-sans text-xl lg:text-2xl font-bold text-brand-ink">
						<?php echo esc_html(__($first_line, 'chroma-excellence')); ?>
					</span>
					<?php foreach ($lines as $line): ?>
						<span class="block text-[10px] lg:text-xs font-bold tracking-[0.15em] text-chroma-blue uppercase">
							<?php echo esc_html(__($line, 'chroma-excellence')); ?>
						</span>
					<?php endforeach; ?>
				</div>
			</a>

			<!-- Desktop Nav -->
			<nav class="hidden lg:flex items-center gap-8">
				<?php chroma_primary_nav(); ?>

				<!-- Language Switcher (Hidden from public until launch) -->
				<?php if (current_user_can('manage_options') && function_exists('chroma_render_language_switcher')): ?>
					<?php chroma_render_language_switcher(); ?>
				<?php endif; ?>

				<!-- CTA Button -->
				<?php
				$cta_url = chroma_get_theme_mod('chroma_book_tour_url', home_url('/contact-us/#tour'));
				// Ensure CTA URL is localized if needed (though home_url filters should handle it if applied globally)
				$cta_text = chroma_get_theme_mod('chroma_header_cta_text', 'Book a Tour');
				?>
				<a href="<?php echo esc_url($cta_url); ?>"
					class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-chroma-red text-white text-xs font-semibold uppercase tracking-widest hover:bg-chroma-red/90 transition shadow-soft">
					<?php _e($cta_text, 'chroma-excellence'); ?>
				</a>
			</nav>

			<!-- Mobile Menu Toggle -->
			<button data-mobile-nav-toggle class="lg:hidden text-brand-ink p-2"
				aria-label="<?php esc_attr_e('Toggle menu', 'chroma-excellence'); ?>">
				<i class="fa-solid fa-bars text-2xl"></i>
			</button>
		</div>

	</header>

	<!-- Mobile Menu Overlay -->
	<div data-mobile-nav
		class="fixed inset-0 bg-white transform translate-x-full transition-transform duration-300 lg:hidden flex flex-col h-full w-full overflow-hidden"
		style="z-index: 9999;">
		<div class="flex items-center justify-between p-4 border-b border-brand-ink/5">
			<div class="flex items-center gap-3">
				<img src="<?php echo esc_url($logo_url); ?>" alt="Chroma Early Learning" width="40" height="40"
					class="h-10 w-auto" />
				<span
					class="font-serif text-lg font-bold text-brand-ink"><?php _e('Menu', 'chroma-excellence'); ?></span>
			</div>
			<button data-mobile-nav-toggle class="text-3xl text-brand-ink"
				aria-label="<?php esc_attr_e('Close menu', 'chroma-excellence'); ?>">&times;</button>
		</div>

		<nav class="flex-1 px-6 py-6 overflow-y-auto">
			<?php chroma_mobile_nav(); ?>

			<!-- Mobile Language Switcher (Hidden from public until launch) -->
			<div class="mt-6 mb-4">
				<?php if (current_user_can('manage_options') && function_exists('chroma_render_language_switcher')): ?>
					<?php chroma_render_language_switcher(); ?>
				<?php endif; ?>
			</div>

			<a href="<?php echo esc_url($cta_url); ?>"
				class="block w-full text-center mt-6 px-6 py-4 rounded-xl bg-chroma-red text-white font-semibold uppercase tracking-widest hover:bg-chroma-red/90 transition shadow-soft">
				<?php _e($cta_text, 'chroma-excellence'); ?>
			</a>
		</nav>
	</div>

	<main id="main-content" class="pt-20 lg:pt-24">
		<?php
		// Disabled to prevent duplication with external plugins
		// do_action('chroma_breadcrumbs'); 
		?>
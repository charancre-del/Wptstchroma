<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php if (is_singular()): ?>
		<link rel="canonical" href="<?php echo esc_url(user_trailingslashit(get_permalink())); ?>" />
	<?php else: ?>
		<link rel="canonical" href="<?php echo esc_url(user_trailingslashit(home_url($_SERVER['REQUEST_URI']))); ?>" />
	<?php endif; ?>
	<link rel="preload" as="font"
		href="<?php echo get_template_directory_uri(); ?>/assets/webfonts/Outfit-Regular.woff2" type="font/woff2"
		crossorigin>
	<link rel="preload" as="font" href="<?php echo get_template_directory_uri(); ?>/assets/webfonts/Outfit-Bold.woff2"
		type="font/woff2" crossorigin>
	<link rel="preload" as="font"
		href="<?php echo get_template_directory_uri(); ?>/assets/webfonts/PlayfairDisplay-Bold.woff2" type="font/woff2"
		crossorigin>

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

	<!-- Skip Links for Accessibility -->
	<a href="#main-content"
		class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-white text-brand-ink p-4 z-50 rounded-lg shadow-lg">Skip
		to content</a>

	<header class="fixed w-full top-0 z-50 transition-all duration-300 bg-white/95 backdrop-blur-sm shadow-sm"
		data-header>
		<div class="max-w-7xl mx-auto px-4 lg:px-6 h-20 lg:h-24 flex items-center justify-between">

			<!-- Logo -->
			<a href="<?php echo esc_url(home_url('/')); ?>" class="flex items-center gap-4 group">
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
				<img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_70x70.webp'); ?>"
					srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_70x70.webp'); ?> 1x,
							<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_140x140.webp'); ?> 2x"
					alt="Chroma Early Learning" width="<?php echo intval($logo_width_desktop); ?>"
					height="<?php echo intval($logo_width_desktop); ?>" fetchpriority="high" loading="eager"
					class="chroma-logo transition-transform duration-300 group-hover:scale-105 no-lazy"
					data-no-lazy="1" />

				<!-- Header Text -->
				<?php
				$header_text = get_theme_mod('chroma_header_text', "Early Learning\nAcademy");
				$lines = explode("\n", $header_text);
				$first_line = array_shift($lines);
				?>
				<div class="block leading-tight">
					<span class="block font-sans text-xl lg:text-2xl font-bold text-brand-ink">
						<?php echo esc_html($first_line); ?>
					</span>
					<?php foreach ($lines as $line): ?>
						<span class="block text-[10px] lg:text-xs font-bold tracking-[0.15em] text-chroma-blue uppercase">
							<?php echo esc_html($line); ?>
						</span>
					<?php endforeach; ?>
				</div>
			</a>

			<!-- Desktop Nav -->
			<nav class="hidden lg:flex items-center gap-8">
				<?php chroma_primary_nav(); ?>

				<!-- CTA Button -->
				<?php
				$cta_url = get_theme_mod('chroma_book_tour_url', home_url('/contact/#tour'));
				// Ensure local links have trailing slash if not present
				if (strpos($cta_url, home_url()) !== false && strpos($cta_url, '/#') !== false) {
					// It already has slash? or needs one?
					// safer:
					$cta_url = str_replace('/contact/#', '/contact/#', $cta_url); // No-op visually but let's just hardcode the default properly first.
				}
				// Actually, cleaner update:
				$cta_url = get_theme_mod('chroma_book_tour_url', home_url('/contact/#tour'));
				// Manual check if likely internal
				if (strpos($cta_url, 'contact/#') !== false && strpos($cta_url, 'contact/#') === false) {
					// Logic is getting messy. Let's just fix the default first.
				}
				$cta_text = get_theme_mod('chroma_header_cta_text', 'Book a Tour');
				?>
				<a href="<?php echo esc_url($cta_url); ?>"
					class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-chroma-red text-white text-xs font-semibold uppercase tracking-widest hover:bg-chroma-red/90 transition shadow-soft">
					<?php echo esc_html($cta_text); ?>
				</a>
			</nav>

			<!-- Mobile Menu Toggle -->
			<button data-mobile-nav-toggle class="lg:hidden text-brand-ink p-2" aria-label="Toggle menu">
				<i class="fa-solid fa-bars text-2xl"></i>
			</button>
		</div>

	</header>

	<!-- Mobile Menu Overlay -->
	<div data-mobile-nav
		class="fixed inset-0 bg-white transform translate-x-full transition-transform duration-300 lg:hidden flex flex-col h-full w-full overflow-hidden"
		style="z-index: 9999;">
		<div class="flex items-center justify-between p-4 border-b border-brand-ink/5">
			<div class="flex items-center gap-3"> <img
					src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_40x40.webp'); ?>"
					srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_40x40.webp'); ?> 1x,
				<?php echo esc_url(get_template_directory_uri() . '/assets/images/logo_chromacropped_70x70.webp'); ?>
				2x" alt="Chroma Early Learning" width="40" height="40" class="h-10 w-auto" />
				<span class="font-serif text-lg font-bold text-brand-ink">Menu</span>
			</div>
			<button data-mobile-nav-toggle class="text-3xl text-brand-ink" aria-label="Close menu">&times;</button>
		</div>

		<nav class="flex-1 px-6 py-6 overflow-y-auto">
			<?php chroma_mobile_nav(); ?>

			<a href="<?php echo esc_url($cta_url); ?>"
				class="block w-full text-center mt-6 px-6 py-4 rounded-xl bg-chroma-red text-white font-semibold uppercase tracking-widest hover:bg-chroma-red/90 transition shadow-soft">
				<?php echo esc_html($cta_text); ?>
			</a>
		</nav>
	</div>

	<main id="main-content" class="pt-20 lg:pt-24">
		<?php do_action('chroma_breadcrumbs'); ?>
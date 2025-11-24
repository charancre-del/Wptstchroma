<!DOCTYPE html>
<html <?php language_attributes(); ?> class="scroll-smooth">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'bg-brand-cream text-brand-ink antialiased selection:bg-chroma-red selection:text-white' ); ?>>
<?php wp_body_open(); ?>

<header class="sticky top-0 z-40 bg-white/85 backdrop-blur-xl border-b border-chroma-blue/10">
	<div class="max-w-7xl mx-auto px-4 lg:px-6 h-[82px] flex items-center justify-between">
		<!-- Logo -->
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-3 group">
			<div class="flex -space-x-1">
				<span class="w-3 h-3 rounded-full bg-chroma-red opacity-90"></span>
				<span class="w-3 h-3 rounded-full bg-chroma-yellow opacity-90"></span>
				<span class="w-3 h-3 rounded-full bg-chroma-green opacity-90"></span>
                                <span class="w-3 h-3 rounded-full bg-chroma-blue opacity-90"></span>
			</div>
			<div class="leading-tight">
				<p class="font-bold text-lg text-brand-ink">Chroma</p>
                                <p class="text-[10px] uppercase tracking-[0.2em] text-chroma-blue">Early Learning</p>
			</div>
		</a>

		<!-- Desktop Navigation -->
		<nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-brand-ink/70">
			<?php chroma_primary_nav(); ?>
		</nav>

		<!-- CTA Button -->
                <a href="<?php echo esc_url( home_url( '/contact#tour' ) ); ?>" class="hidden sm:inline-flex items-center gap-2 bg-brand-ink text-white text-xs font-semibold tracking-[0.2em] px-5 py-3 rounded-full shadow-soft hover:bg-chroma-blueDark">
			Book A Tour
		</a>

		<!-- Mobile Menu Button -->
		<button data-mobile-nav-toggle class="md:hidden text-2xl text-brand-ink" aria-label="Open menu">☰</button>
	</div>

	<!-- Mobile Menu -->
        <div data-mobile-nav class="fixed inset-0 bg-white z-50 translate-x-full transition-transform duration-300 md:hidden flex flex-col">
                <div class="flex items-center justify-between px-5 py-5 border-b border-chroma-blue/10">
			<div class="flex items-center gap-2">
				<div class="flex -space-x-1">
					<span class="w-3 h-3 rounded-full bg-chroma-red"></span>
					<span class="w-3 h-3 rounded-full bg-chroma-yellow"></span>
					<span class="w-3 h-3 rounded-full bg-chroma-green"></span>
                                        <span class="w-3 h-3 rounded-full bg-chroma-blue"></span>
				</div>
				<span class="font-serif text-lg font-bold text-brand-ink">Chroma Menu</span>
			</div>
			<button data-mobile-nav-toggle class="text-3xl text-brand-ink" aria-label="Close menu">×</button>
		</div>
		<nav class="flex-1 px-6 py-6 text-lg font-semibold text-brand-ink space-y-6">
			<?php chroma_primary_nav(); ?>
                        <a href="<?php echo esc_url( home_url( '/contact#tour' ) ); ?>" class="block bg-brand-ink text-white text-center py-3 rounded-2xl shadow-soft hover:bg-chroma-blueDark transition mt-4">
				Book A Tour
			</a>
		</nav>
	</div>
</header>

<main>

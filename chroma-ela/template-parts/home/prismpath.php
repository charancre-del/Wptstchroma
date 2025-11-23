<?php
/**
 * Prismpath Section
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */
?>

<section id="prismpath" class="py-24 px-4 lg:px-6 bg-white relative overflow-hidden">
	<div class="absolute -left-10 top-10 w-80 h-80 bg-chroma-blue/5 rounded-full blur-3xl"></div>
	<div class="max-w-[1200px] mx-auto">
		<div class="text-center mb-12">
			<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block">The Chroma Standard</span>
			<h2 class="text-3xl md:text-5xl font-serif text-brand-ink">Grounded in Expertise. Wrapped in Love.</h2>
		</div>
		<div class="grid grid-cols-1 md:grid-cols-12 gap-6 md:h-[620px]">
			<!-- Main Prismpath Card -->
			<div class="md:col-span-7 bg-chroma-blue rounded-[3rem] p-10 text-white flex flex-col justify-between relative overflow-hidden">
				<div class="absolute top-0 right-0 p-10 opacity-10 text-8xl"><i class="fa-solid fa-shapes"></i></div>
				<div class="relative z-10 space-y-4">
					<div class="flex items-start justify-between">
						<div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-xl mb-6"><i class="fa-brands fa-connectdevelop"></i></div>
						<span class="bg-white/10 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wider">Proprietary Model</span>
					</div>
					<h3 class="text-3xl font-serif">The Prismpath™ Curriculum</h3>
					<p class="text-white/80 text-lg leading-relaxed max-w-xl">Just as a prism refracts light into a full spectrum of color, <strong>Prismpath™</strong> refracts play into a full spectrum of development.</p>
					<div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20">
						<h4 class="font-bold text-white mb-2 flex items-center gap-2"><i class="fa-solid fa-check-circle text-chroma-yellow"></i> Kindergarten Readiness</h4>
						<p class="text-sm text-white/80">Our graduates enter school confident, socially capable, and academically prepared.</p>
					</div>
				</div>
			</div>

			<!-- Expert Care Card -->
			<div class="md:col-span-5 md:row-span-2 bg-chroma-red rounded-[3rem] p-10 text-white relative overflow-hidden">
				<div class="absolute top-0 right-0 p-12 opacity-10 text-8xl"><i class="fa-solid fa-heart"></i></div>
				<div class="relative z-10 h-full flex flex-col justify-between">
					<div>
						<div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center text-2xl mb-8"><i class="fa-solid fa-user-check"></i></div>
						<h3 class="text-3xl font-serif mb-6">Expert Care, Extended Family.</h3>
						<p class="text-white/90 text-lg leading-relaxed">Our educators are state-certified professionals who understand that the most important credential is kindness.</p>
					</div>
					<a href="<?php echo esc_url( home_url( '/team' ) ); ?>" class="mt-8 bg-white text-chroma-red px-6 py-3 rounded-full w-max text-sm font-bold uppercase tracking-wide hover:bg-brand-cream transition">Meet the Team</a>
				</div>
			</div>

			<!-- Nutrition Card -->
			<div class="md:col-span-3 bg-chroma-green rounded-[3rem] p-8 text-white">
				<div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mb-4"><i class="fa-solid fa-apple-whole"></i></div>
				<h3 class="text-xl font-bold mb-2">Wholesome Fuel</h3>
				<p class="text-white/80 text-sm">Organic, balanced meals served family-style to fuel growing minds.</p>
			</div>

			<!-- Safety Card -->
			<div class="md:col-span-4 bg-white border border-chroma-blue/10 shadow-soft rounded-[3rem] p-8 flex flex-col gap-4">
				<div class="flex items-center gap-3">
					<i class="fa-solid fa-shield-halved text-chroma-yellow text-2xl"></i>
					<h3 class="text-xl font-bold text-brand-ink">Uncompromised Safety</h3>
				</div>
				<p class="text-brand-ink/70 text-sm">Secure, monitored facilities with open-door transparency for parents.</p>
			</div>
		</div>
	</div>
</section>

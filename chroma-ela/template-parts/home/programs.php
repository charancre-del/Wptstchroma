<?php
/**
 * Programs Wizard Section
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */
?>

<section id="programs" class="py-20 bg-brand-cream border-b border-chroma-blue/10">
	<div class="max-w-5xl mx-auto px-4 lg:px-6">
		<div class="text-center mb-10 fade-in-up">
			<h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">Find the right program in 10 seconds</h2>
			<p class="text-brand-ink/70 text-sm md:text-base max-w-2xl mx-auto">Choose your child's age and we'll suggest the Chroma program designed for their development stage and your family's needs.</p>
		</div>
		<div class="bg-white rounded-3xl p-6 md:p-8 border border-chroma-blue/10 shadow-soft fade-in-up">
			<div id="wizard-step-1" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
				<button onclick="showWizardResult('infant')" class="p-4 bg-chroma-redLight rounded-2xl border border-chroma-red/30 hover:border-chroma-red hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">👶</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">Infant<br>(6 weeks–12m)</span>
				</button>
				<button onclick="showWizardResult('toddler')" class="p-4 bg-white rounded-2xl border border-chroma-blue/20 hover:border-chroma-blue hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">🚀</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">Toddler<br>(1 year)</span>
				</button>
				<button onclick="showWizardResult('preschool')" class="p-4 bg-white rounded-2xl border border-chroma-yellow/20 hover:border-chroma-yellow hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">🎨</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">Preschool<br>(2 years)</span>
				</button>
				<button onclick="showWizardResult('prep')" class="p-4 bg-white rounded-2xl border border-chroma-blue/20 hover:border-chroma-blue hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">✏️</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">Pre-K Prep<br>(3 years)</span>
				</button>
				<button onclick="showWizardResult('prek')" class="p-4 bg-white rounded-2xl border border-chroma-blue/20 hover:border-chroma-blue hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">🎓</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">GA Pre-K<br>(4 years)</span>
				</button>
				<button onclick="showWizardResult('afterschool')" class="p-4 bg-white rounded-2xl border border-chroma-green/20 hover:border-chroma-green hover:shadow-soft transition group text-center">
					<span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">🚌</span>
					<span class="font-semibold text-brand-ink text-xs leading-tight">After School<br>(5–12 years)</span>
				</button>
			</div>
			<div id="wizard-result" class="hidden text-center pt-6 space-y-3">
				<h3 id="wizard-title" class="text-2xl font-serif font-bold text-brand-ink mb-2">Program Name</h3>
				<p id="wizard-desc" class="text-brand-ink/70 max-w-xl mx-auto text-sm md:text-base">Description goes here.</p>
				<div class="flex flex-wrap justify-center gap-3 text-xs">
					<a id="wizard-learn-link" href="/programs" class="inline-flex items-center justify-center px-5 py-2 rounded-full border border-chroma-blue/20 bg-white text-brand-ink font-semibold hover:border-chroma-blue hover:text-chroma-blue transition">Learn more about this program</a>
					<a href="#tour" class="inline-flex items-center justify-center px-5 py-2 rounded-full bg-chroma-red text-white font-semibold hover:bg-chroma-red/90 transition">Speak to an enrollment specialist</a>
					<button type="button" onclick="resetWizard()" class="text-brand-ink/50 hover:text-brand-ink underline decoration-dotted">Start Over</button>
				</div>
			</div>
		</div>
	</div>
</section>

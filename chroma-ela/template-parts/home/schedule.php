<?php
/**
 * Schedule / Day in the Life Section
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */
?>

<section id="schedule" class="py-20 bg-brand-cream relative">
	<div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-chroma-red via-chroma-yellow to-chroma-blue opacity-40"></div>
	<div class="max-w-6xl mx-auto px-4 lg:px-6">
		<div class="text-center mb-12">
			<span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-4 block">Day by Day</span>
			<h2 class="text-3xl md:text-4xl font-serif text-brand-ink mb-3">A Daily Rhythm of Joy</h2>
			<p class="text-brand-ink/70 max-w-2xl mx-auto">We don't just fill time. Every classroom follows a thoughtful flow designed to balance stimulation, nourishment, and rest.</p>
		</div>
		<div class="flex justify-center mb-12">
			<div class="bg-white border border-chroma-blue/15 p-1 rounded-full inline-flex">
				<button onclick="switchTab('infant')" id="btn-infant" class="schedule-tab px-8 py-3 rounded-full text-sm font-bold transition-all duration-300 bg-chroma-blue text-white shadow-soft">Infants</button>
				<button onclick="switchTab('toddler')" id="btn-toddler" class="schedule-tab px-8 py-3 rounded-full text-sm font-bold transition-all duration-300 text-brand-ink/60 hover:text-chroma-blue">Toddlers</button>
				<button onclick="switchTab('prek')" id="btn-prek" class="schedule-tab px-8 py-3 rounded-full text-sm font-bold transition-all duration-300 text-brand-ink/60 hover:text-chroma-blue">Pre-K</button>
			</div>
		</div>

		<!-- Tab content would go here - simplified for demo -->
		<div id="tab-infant" class="tab-content active">
			<div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
				<div class="bg-chroma-blueLight rounded-[3rem] p-10 h-full">
					<h3 class="text-2xl font-serif text-chroma-blueDark mb-6">The Nurturing Nest</h3>
					<p class="text-brand-ink/70 mb-8 leading-relaxed">Individualized schedules follow infants' cues for sleeping and eating, with gentle sensory play.</p>
				</div>
			</div>
		</div>
	</div>
</section>

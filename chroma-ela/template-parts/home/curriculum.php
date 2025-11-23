<?php
/**
 * Curriculum Radar Section
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */
?>

<section id="curriculum" class="py-20 bg-brand-cream border-y border-chroma-blue/10">
	<div class="max-w-6xl mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-12 items-center">
		<div class="space-y-5 fade-in-up">
			<span class="text-chroma-blue font-bold tracking-[0.2em] text-[11px] uppercase">The Prismpath™ Curriculum</span>
			<h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink">A curriculum that shifts as your child grows</h2>
			<p class="text-brand-ink/70 text-sm md:text-base">Our Prismpath™ framework balances five pillars – physical, emotional, social, academic, and creative development. The mix changes at each age so your child gets exactly what they need, when they need it.</p>
			<div class="flex flex-wrap gap-2 text-xs">
				<button id="btn-cur-infant" onclick="updateCurriculum('infant')" class="px-4 py-2 rounded-full font-semibold bg-chroma-blue text-white shadow-soft">Infant</button>
				<button id="btn-cur-toddler" onclick="updateCurriculum('toddler')" class="px-4 py-2 rounded-full font-semibold bg-white text-brand-ink/70 border border-chroma-blue/20 hover:border-chroma-blue">Toddler</button>
				<button id="btn-cur-preschool" onclick="updateCurriculum('preschool')" class="px-4 py-2 rounded-full font-semibold bg-white text-brand-ink/70 border border-chroma-blue/20 hover:border-chroma-blue">Preschool</button>
				<button id="btn-cur-prep" onclick="updateCurriculum('prep')" class="px-4 py-2 rounded-full font-semibold bg-white text-brand-ink/70 border border-chroma-blue/20 hover:border-chroma-blue">Pre-K Prep</button>
				<button id="btn-cur-prek" onclick="updateCurriculum('prek')" class="px-4 py-2 rounded-full font-semibold bg-white text-brand-ink/70 border border-chroma-blue/20 hover:border-chroma-blue">GA Pre-K</button>
				<button id="btn-cur-afterschool" onclick="updateCurriculum('afterschool')" class="px-4 py-2 rounded-full font-semibold bg-white text-brand-ink/70 border border-chroma-blue/20 hover:border-chroma-blue">After School</button>
			</div>
			<div class="bg-white rounded-3xl border-l-4 border-chroma-red shadow-soft p-6 md:p-7">
				<h3 id="curriculum-title" class="font-serif text-xl md:text-2xl font-bold text-brand-ink mb-2">Foundation Phase</h3>
				<p id="curriculum-desc" class="text-brand-ink/70 text-sm md:text-base">Infant classrooms emphasize emotional security, attachment, physical health, and sensory experiences. Academics are embedded through language-rich interactions.</p>
			</div>
		</div>
		<div class="fade-in-up">
			<div class="bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 p-6">
				<div class="relative h-[340px] md:h-[380px]">
					<canvas id="curriculumChart" aria-label="Curriculum focus radar chart" role="img"></canvas>
				</div>
			</div>
		</div>
	</div>
</section>

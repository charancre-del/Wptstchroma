<?php
/**
 * Tour CTA Section
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */
?>

<section id="tour" class="py-20 bg-brand-cream border-t border-chroma-blue/10">
	<div class="max-w-5xl mx-auto px-4 lg:px-6">
		<div class="bg-white rounded-[2.5rem] shadow-soft border border-chroma-blue/10 overflow-hidden grid md:grid-cols-[1.1fr,1fr]">
			<div class="p-8 md:p-10">
				<h2 class="font-serif text-3xl md:text-4xl font-bold text-brand-ink mb-3">Schedule a private tour</h2>
				<p class="text-brand-ink/70 text-sm md:text-base mb-6">Share a few details and your preferred campus. A Chroma Director will reach out to confirm tour times.</p>
				<form class="space-y-4" method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
					<input type="hidden" name="action" value="chroma_tour_request" />
					<?php wp_nonce_field( 'chroma_tour_nonce', 'tour_nonce' ); ?>

					<div class="grid md:grid-cols-2 gap-4">
						<div>
							<label class="block text-[11px] font-semibold text-brand-ink/60 uppercase mb-1.5">Parent Name</label>
							<input type="text" name="parent_name" required class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-sm" placeholder="Jane Doe" />
						</div>
						<div>
							<label class="block text-[11px] font-semibold text-brand-ink/60 uppercase mb-1.5">Phone</label>
							<input type="tel" name="phone" required class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-sm" placeholder="(555) 123-4567" />
						</div>
					</div>
					<div class="grid md:grid-cols-2 gap-4">
						<div>
							<label class="block text-[11px] font-semibold text-brand-ink/60 uppercase mb-1.5">Email</label>
							<input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-sm" placeholder="you@email.com" />
						</div>
						<div>
							<label class="block text-[11px] font-semibold text-brand-ink/60 uppercase mb-1.5">Preferred Campus</label>
							<select name="location" class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-sm">
								<option value="">Select a location…</option>
								<?php
								$locations = get_posts( array( 'post_type' => 'location', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
								foreach ( $locations as $location ) :
								?>
									<option value="<?php echo esc_attr( $location->ID ); ?>"><?php echo esc_html( $location->post_title ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div>
						<label class="block text-[11px] font-semibold text-brand-ink/60 uppercase mb-1.5">Child(ren) Age(s)</label>
						<input type="text" name="child_ages" class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-sm" placeholder="e.g., 10 months, 3 years" />
					</div>
					<button type="submit" class="w-full mt-2 inline-flex items-center justify-center bg-chroma-red text-white text-xs font-semibold tracking-[0.24em] uppercase py-3.5 rounded-full shadow-soft hover:bg-chroma-red/90">Request Tour Times</button>
					<p class="text-[11px] text-brand-ink/50 mt-2">No obligation. We'll never share your information.</p>
				</form>
			</div>
			<div class="bg-gradient-to-br from-chroma-blue via-chroma-green to-chroma-yellow text-white p-7 md:p-8 flex flex-col justify-between">
				<div>
					<p class="text-[11px] font-semibold tracking-[0.2em] uppercase mb-2">Why families choose Chroma</p>
					<ul class="space-y-3 text-sm">
						<li class="flex gap-2"><span class="mt-0.5 text-white">✓</span><span>Warm, consistent teachers who know your child well</span></li>
						<li class="flex gap-2"><span class="mt-0.5 text-white">✓</span><span>Daily parent communication with photos and updates</span></li>
						<li class="flex gap-2"><span class="mt-0.5 text-white">✓</span><span>Healthy meals included through CACFP participation</span></li>
						<li class="flex gap-2"><span class="mt-0.5 text-white">✓</span><span>Age-appropriate security and safety protocols</span></li>
						<li class="flex gap-2"><span class="mt-0.5 text-white">✓</span><span>GA Lottery Pre-K at many locations</span></li>
					</ul>
				</div>
				<div class="mt-6 text-xs text-white/80">
					<p class="font-semibold mb-1">Typical tour length: 20–30 minutes</p>
					<p>Meet the Director, walk classrooms, and get tuition details for your child's age group.</p>
				</div>
			</div>
		</div>
	</div>
</section>

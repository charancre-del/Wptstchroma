<?php
/**
 * Template Part: Prismpath Expertise Section
 * "Grounded in Expertise. Wrapped in Love." - Bento grid with colored cards
 *
 * @package Chroma_Excellence
 */

$panels = chroma_home_prismpath_panels();

$feature = $panels['feature'];
$cards   = $panels['cards'];
$heading = $feature['heading'];
$subheading = $feature['subheading'] ?? '';

$card_1 = $cards[0];
$card_2 = $cards[1];
$card_3 = $cards[2];
$card_4 = $cards[3];

$readiness       = $panels['readiness'];
$readiness_heading = $readiness['heading'];
$readiness_text    = $readiness['description'];
?>

<section id="prismpath" class="py-24 px-4 lg:px-6 bg-white relative overflow-hidden" data-section="prismpath">
        <div class="absolute -left-10 top-10 w-80 h-80 bg-chroma-blue/5 rounded-full blur-3xl"></div>

	<div class="max-w-[1200px] mx-auto">
		<!-- Section Header -->
		<div class="text-center mb-12">
			<span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block">The Chroma Standard</span>
                        <h2 class="text-3xl md:text-5xl font-serif text-brand-ink"><?php echo esc_html( $heading ); ?></h2>
                        <?php if ( $subheading ) : ?>
                                <p class="text-brand-ink/70 mt-4 max-w-2xl mx-auto"><?php echo esc_html( $subheading ); ?></p>
                        <?php endif; ?>
                </div>

		<!-- Bento Grid -->
		<div class="grid grid-cols-1 md:grid-cols-12 md:grid-rows-2 gap-6 md:auto-rows-fr">

			<!-- Card 1: Large Blue Card (7 columns) -->
                        <div class="md:col-span-7 md:row-span-1 bg-chroma-blue rounded-[3rem] p-10 text-white flex flex-col justify-between relative overflow-hidden min-h-[300px] md:min-h-0">
				<div class="absolute top-0 right-0 p-10 opacity-10 text-8xl">
					<i class="fa-solid fa-shapes"></i>
				</div>
				<div class="relative z-10 space-y-4">
					<div class="flex items-start justify-between">
						<div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-xl mb-6">
							<i class="fa-brands fa-connectdevelop"></i>
						</div>
						<span class="bg-white/10 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
							<?php echo esc_html( $card_1['badge'] ); ?>
						</span>
					</div>
                                        <h3 class="text-3xl font-serif"><?php echo esc_html( $card_1['heading'] ); ?></h3>
                                        <p class="text-white/80 text-lg leading-relaxed max-w-xl">
                                                <?php echo esc_html( $card_1['text'] ?? '' ); ?>
                                        </p>
					<div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20">
						<h4 class="font-bold text-white mb-2 flex items-center gap-2">
							<i class="fa-solid fa-check-circle text-chroma-yellow"></i>
							<?php echo esc_html( $readiness_heading ); ?>
						</h4>
						<p class="text-sm text-white/80">
							<?php echo esc_html( $readiness_text ); ?>
						</p>
					</div>
				</div>
			</div>

			<!-- Card 2: Large Red Card (5 columns, tall) -->
                        <div class="md:col-span-5 md:row-span-2 bg-chroma-red rounded-[3rem] p-10 text-white relative overflow-hidden min-h-[400px] md:min-h-0">
				<div class="absolute top-0 right-0 p-12 opacity-10 text-8xl">
					<i class="fa-solid fa-heart"></i>
				</div>
				<div class="relative z-10 h-full flex flex-col justify-between">
					<div>
						<div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center text-2xl mb-8">
							<i class="fa-solid fa-user-check"></i>
						</div>
                                                <h3 class="text-3xl font-serif mb-6"><?php echo esc_html( $card_2['heading'] ); ?></h3>
                                                <p class="text-white/90 text-lg leading-relaxed">
                                                        <?php echo esc_html( $card_2['text'] ?? '' ); ?>
                                                </p>
                                        </div>
                                        <?php if ( ! empty( $card_2['button'] ) ) : ?>
                                                <a href="<?php echo esc_url( $card_2['url'] ?? '#' ); ?>" class="mt-8 bg-white text-chroma-red px-6 py-3 rounded-full w-max text-sm font-bold uppercase tracking-wide hover:bg-brand-cream transition">
                                                        <?php echo esc_html( $card_2['button'] ); ?>
                                                </a>
                                        <?php endif; ?>
                                </div>
                        </div>

			<!-- Card 3: Green Card (3 columns) -->
			<div class="md:col-span-3 md:row-span-1 bg-gradient-to-br from-chroma-green to-chroma-green/90 rounded-[3rem] p-8 text-white min-h-[300px] md:min-h-0">
				<div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mb-4">
					<i class="fa-solid fa-apple-whole"></i>
				</div>
				<h3 class="text-xl font-bold mb-2"><?php echo esc_html( $card_3['heading'] ); ?></h3>
				<p class="text-white/80 text-sm">
					<?php echo esc_html( $card_3['text'] ); ?>
				</p>
			</div>

			<!-- Card 4: White Card (4 columns) -->
                        <div class="md:col-span-4 md:row-span-1 bg-white border border-chroma-blue/10 shadow-soft rounded-[3rem] p-8 flex flex-col gap-4 min-h-[300px] md:min-h-0">
				<div class="flex items-center gap-3">
					<i class="fa-solid fa-shield-halved text-chroma-yellow text-2xl"></i>
					<h3 class="text-xl font-bold text-brand-ink"><?php echo esc_html( $card_4['heading'] ); ?></h3>
				</div>
				<p class="text-brand-ink/70 text-sm">
					<?php echo esc_html( $card_4['text'] ); ?>
				</p>
			</div>

		</div>
	</div>
</section>

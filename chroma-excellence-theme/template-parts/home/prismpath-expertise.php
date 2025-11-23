<?php
/**
 * Prismpath Expertise Section
 *
 * @package Chroma_Excellence
 */

$panels  = chroma_home_prismpath_panels();
$cards   = $panels['cards'] ?? array();
$feature = $panels['feature'] ?? array();

if ( empty( $cards ) || empty( $feature ) || count( $cards ) < 4 ) {
        return;
}
?>

<section id="prismpath" class="py-24 px-4 lg:px-6 bg-white relative overflow-hidden">
        <div class="absolute -left-10 top-10 w-80 h-80 bg-chroma-blue/5 rounded-full blur-3xl"></div>
        <div class="max-w-[1200px] mx-auto">
                <div class="text-center mb-12">
                        <span class="text-chroma-red font-bold tracking-[0.2em] text-xs uppercase mb-3 block"><?php echo esc_html( $feature['eyebrow'] ); ?></span>
                        <h2 class="text-3xl md:text-5xl font-serif text-brand-ink"><?php echo esc_html( $feature['heading'] ); ?></h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 md:h-[620px]">
                        <div class="md:col-span-7 bg-chroma-blue rounded-[3rem] p-10 text-white flex flex-col justify-between relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-10 opacity-10 text-8xl"><i class="fa-solid fa-shapes"></i></div>
                                <div class="relative z-10 space-y-4">
                                        <div class="flex items-start justify-between">
                                                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-xl mb-6"><i class="fa-brands fa-connectdevelop"></i></div>
                                                <span class="bg-white/10 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wider"><?php echo esc_html( $cards[0]['badge'] ); ?></span>
                                        </div>
                                        <h3 class="text-3xl font-serif"><?php echo esc_html( $cards[0]['title'] ); ?></h3>
                                        <p class="text-white/80 text-lg leading-relaxed max-w-xl"><?php echo esc_html( $cards[0]['description'] ); ?></p>
                                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-6 border border-white/20">
                                                <h4 class="font-bold text-white mb-2 flex items-center gap-2"><i class="fa-solid fa-check-circle text-chroma-yellow"></i> Kindergarten Readiness</h4>
                                                <p class="text-sm text-white/80">Our graduates enter school confident, socially capable, and academically prepared.</p>
                                        </div>
                                </div>
                        </div>
                        <div class="md:col-span-5 md:row-span-2 bg-chroma-red rounded-[3rem] p-10 text-white relative overflow-hidden">
                                <div class="absolute top-0 right-0 p-12 opacity-10 text-8xl"><i class="fa-solid fa-heart"></i></div>
                                <div class="relative z-10 h-full flex flex-col justify-between">
                                        <div>
                                                <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center text-2xl mb-8"><i class="fa-solid fa-user-check"></i></div>
                                                <h3 class="text-3xl font-serif mb-6"><?php echo esc_html( $cards[1]['title'] ); ?></h3>
                                                <p class="text-white/90 text-lg leading-relaxed"><?php echo esc_html( $cards[1]['description'] ); ?></p>
                                        </div>
                                        <a class="mt-8 bg-white text-chroma-red px-6 py-3 rounded-full w-max text-sm font-bold uppercase tracking-wide hover:bg-brand-cream transition" href="<?php echo esc_url( $feature['cta_url'] ); ?>"><?php echo esc_html( $feature['cta_label'] ); ?></a>
                                </div>
                        </div>
                        <div class="md:col-span-3 bg-chroma-green rounded-[3rem] p-8 text-white">
                                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mb-4"><i class="fa-solid fa-apple-whole"></i></div>
                                <h3 class="text-xl font-bold mb-2"><?php echo esc_html( $cards[2]['title'] ); ?></h3>
                                <p class="text-white/80 text-sm"><?php echo esc_html( $cards[2]['description'] ); ?></p>
                        </div>
                        <div class="md:col-span-4 bg-white border border-chroma-blue/10 shadow-soft rounded-[3rem] p-8 flex flex-col gap-4">
                                <div class="flex items-center gap-3">
                                        <i class="fa-solid fa-shield-halved text-chroma-yellow text-2xl"></i>
                                        <h3 class="text-xl font-bold text-brand-ink"><?php echo esc_html( $cards[3]['title'] ); ?></h3>
                                </div>
                                <p class="text-brand-ink/70 text-sm"><?php echo esc_html( $cards[3]['description'] ); ?></p>
                        </div>
                </div>
        </div>
</section>

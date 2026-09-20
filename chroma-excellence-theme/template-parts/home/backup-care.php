<?php
/**
 * Homepage backup care section.
 *
 * @package Chroma_Excellence
 */
?>

<section class="py-16 md:py-20 bg-chroma-blueDark text-white border-y border-white/10" aria-labelledby="home-backup-care-title">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-[1.15fr_0.85fr] gap-10 lg:gap-16 items-center">
        <div>
            <p class="text-xs font-bold uppercase tracking-normal text-chroma-yellowLight mb-3"><?php esc_html_e('Care when plans change', 'chroma-excellence'); ?></p>
            <h2 id="home-backup-care-title" class="font-serif text-4xl md:text-5xl font-semibold leading-tight tracking-normal">
                <?php esc_html_e('Backup care, booked directly.', 'chroma-excellence'); ?>
            </h2>
            <p class="mt-5 text-lg text-white/80 leading-relaxed max-w-2xl">
                <?php esc_html_e('Any family can reserve weekday care at a Chroma campus. Add multiple children and multiple dates to one secure order.', 'chroma-excellence'); ?>
            </p>
            <a href="<?php echo esc_url(chroma_backup_care_url()); ?>" class="mt-7 inline-flex items-center justify-center min-h-12 px-7 py-3 bg-chroma-red text-white font-bold hover:bg-white hover:text-brand-ink transition">
                <?php esc_html_e('Book backup care', 'chroma-excellence'); ?>
                <i class="fa-solid fa-arrow-right ml-2" aria-hidden="true"></i>
            </a>
        </div>
        <dl class="grid grid-cols-2 gap-px bg-white/20 border border-white/20">
            <div class="bg-chroma-blueDark p-5 md:p-7"><dt class="text-white/70 text-sm"><?php esc_html_e('Daily rate', 'chroma-excellence'); ?></dt><dd class="mt-1 text-2xl font-bold">$115</dd></div>
            <div class="bg-chroma-blueDark p-5 md:p-7"><dt class="text-white/70 text-sm"><?php esc_html_e('Book ahead', 'chroma-excellence'); ?></dt><dd class="mt-1 text-2xl font-bold"><?php esc_html_e('12 months', 'chroma-excellence'); ?></dd></div>
            <div class="bg-chroma-blueDark p-5 md:p-7"><dt class="text-white/70 text-sm"><?php esc_html_e('Same-day deadline', 'chroma-excellence'); ?></dt><dd class="mt-1 text-2xl font-bold">7:30 AM</dd></div>
            <div class="bg-chroma-blueDark p-5 md:p-7"><dt class="text-white/70 text-sm"><?php esc_html_e('Latest drop-off', 'chroma-excellence'); ?></dt><dd class="mt-1 text-2xl font-bold">9:30 AM</dd></div>
        </dl>
    </div>
</section>

<?php
/**
 * Location page backup care section.
 *
 * @package Chroma_Excellence
 */

$campus_id = isset($args['campus_id']) ? sanitize_key((string) $args['campus_id']) : '';
$location_name = isset($args['location_name']) ? (string) $args['location_name'] : get_the_title();
?>

<section class="py-14 md:py-20 bg-chroma-greenLight border-y border-chroma-green/20" aria-labelledby="location-backup-care-title">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
        <div class="max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-normal text-chroma-green mb-3"><?php esc_html_e('Flexible weekday care', 'chroma-excellence'); ?></p>
            <h2 id="location-backup-care-title" class="font-serif text-3xl md:text-4xl font-semibold leading-tight tracking-normal text-brand-ink">
                <?php printf(esc_html__('Book backup care at %s.', 'chroma-excellence'), esc_html($location_name)); ?>
            </h2>
            <p class="mt-4 text-brand-ink/70 text-lg leading-relaxed">
                <?php esc_html_e('$115 per child per date, full payment at booking. Any family is eligible, and one reservation can cover multiple children and dates.', 'chroma-excellence'); ?>
            </p>
        </div>
        <a href="<?php echo esc_url(chroma_backup_care_url($campus_id)); ?>" class="inline-flex items-center justify-center min-h-12 px-7 py-3 bg-chroma-green text-white font-bold hover:bg-brand-ink transition shrink-0">
            <?php esc_html_e('Reserve backup care', 'chroma-excellence'); ?>
            <i class="fa-solid fa-arrow-right ml-2" aria-hidden="true"></i>
        </a>
    </div>
</section>

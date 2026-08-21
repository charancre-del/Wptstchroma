<?php
/**
 * Backup Care Management Page Template.
 *
 * @package Chroma_Excellence
 */

get_header();
?>

<main id="primary" class="site-main bg-brand-cream" role="main">
    <section class="py-14 md:py-20 bg-chroma-blueDark text-white" aria-labelledby="backup-care-manage-title">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-xs font-bold uppercase tracking-normal text-chroma-yellowLight mb-3"><?php esc_html_e('Secure reservation access', 'chroma-excellence'); ?></p>
            <h1 id="backup-care-manage-title" class="font-serif text-4xl md:text-5xl font-semibold tracking-normal"><?php esc_html_e('Manage Backup Care', 'chroma-excellence'); ?></h1>
            <p class="mt-4 text-lg text-white/80 max-w-2xl"><?php esc_html_e('Cancel eligible child-date units or move one to a different available date.', 'chroma-excellence'); ?></p>
        </div>
    </section>
    <section class="py-12 md:py-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <?php if (shortcode_exists('chroma_backup_care_manage')) : ?>
                <?php echo do_shortcode('[chroma_backup_care_manage]'); ?>
            <?php else : ?>
                <p class="border border-brand-ink/20 bg-white p-6"><?php esc_html_e('Reservation management is not available right now. Contact billing@chromaela.com.', 'chroma-excellence'); ?></p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>

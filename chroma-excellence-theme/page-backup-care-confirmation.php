<?php
/**
 * Backup Care Payment Confirmation Page Template.
 *
 * @package Chroma_Excellence
 */

get_header();
?>

<main id="primary" class="site-main bg-brand-cream" role="main">
    <section class="min-h-[32rem] py-20 flex items-center">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-chroma-green text-white" aria-hidden="true"><i class="fa-solid fa-check text-xl"></i></span>
            <p class="mt-7 text-xs font-bold uppercase tracking-normal text-chroma-green"><?php esc_html_e('Payment submitted', 'chroma-excellence'); ?></p>
            <h1 class="mt-3 font-serif text-4xl md:text-5xl font-semibold tracking-normal text-brand-ink"><?php esc_html_e('We are finalizing your reservation.', 'chroma-excellence'); ?></h1>
            <p class="mt-5 text-lg leading-relaxed text-brand-ink/70"><?php esc_html_e('Your confirmation email will list every child, care date, campus detail, and the secure link for future changes.', 'chroma-excellence'); ?></p>
            <p class="mt-3 text-brand-ink/70"><?php esc_html_e('Do not repeat payment. If confirmation does not arrive, contact billing@chromaela.com.', 'chroma-excellence'); ?></p>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="mt-8 inline-flex items-center justify-center min-h-12 px-7 py-3 bg-chroma-red text-white font-bold hover:bg-brand-ink transition"><?php esc_html_e('Return home', 'chroma-excellence'); ?></a>
        </div>
    </section>
</main>

<?php get_footer(); ?>

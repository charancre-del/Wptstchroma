<?php
/**
 * Backup Care Payment Confirmation Page Template.
 *
 * @package Chroma_Excellence
 */

$settings = function_exists('chroma_backup_care_public_settings')
    ? chroma_backup_care_public_settings()
    : array();
$support_email = isset($settings['supportEmail']) ? sanitize_email($settings['supportEmail']) : 'info@chromaela.com';
$billing_email = isset($settings['billingSupportEmail']) ? sanitize_email($settings['billingSupportEmail']) : 'billing@chromaela.com';
$refund_hours = isset($settings['refundCutoffHours']) ? max(0, (int) $settings['refundCutoffHours']) : 72;
$reschedule_hours = isset($settings['rescheduleCutoffHours'])
    ? max(0, (int) $settings['rescheduleCutoffHours'])
    : $refund_hours;

get_header();
?>

<main id="primary" class="site-main bg-brand-cream" role="main">
    <section class="min-h-[32rem] py-20 flex items-center">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-chroma-green text-white" aria-hidden="true"><i class="fa-solid fa-check text-xl"></i></span>
            <p class="mt-7 text-xs font-bold uppercase tracking-normal text-chroma-green"><?php esc_html_e('Payment received', 'chroma-excellence'); ?></p>
            <h1 class="mt-3 font-serif text-4xl md:text-5xl font-semibold tracking-normal text-brand-ink"><?php esc_html_e('Check your email for your reservation details.', 'chroma-excellence'); ?></h1>
            <p class="mt-5 text-lg leading-relaxed text-brand-ink/70"><?php esc_html_e('Your confirmation lists the campus, every child and care date, directions, enrollment next steps, and the secure link for future changes.', 'chroma-excellence'); ?></p>
            <p class="mt-3 text-brand-ink/70"><?php
                echo esc_html(sprintf(
                    __('Refundable cancellation closes %1$d hours before each care date; rescheduling closes %2$d hours before each care date.', 'chroma-excellence'),
                    $refund_hours,
                    $reschedule_hours
                ));
            ?></p>
            <p class="mt-3 text-brand-ink/70"><?php
                echo wp_kses_post(sprintf(
                    __('If confirmation does not arrive, contact <a href="mailto:%1$s">%1$s</a>. Refund questions go to <a href="mailto:%2$s">%2$s</a>.', 'chroma-excellence'),
                    esc_attr($support_email),
                    esc_attr($billing_email)
                ));
            ?></p>
            <button type="button" onclick="window.print()" class="mt-8 inline-flex items-center justify-center min-h-12 px-7 py-3 border border-brand-ink/20 text-brand-ink font-bold hover:bg-white transition"><?php esc_html_e('Print this page', 'chroma-excellence'); ?></button>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="mt-8 inline-flex items-center justify-center min-h-12 px-7 py-3 bg-chroma-red text-white font-bold hover:bg-brand-ink transition"><?php esc_html_e('Return home', 'chroma-excellence'); ?></a>
        </div>
    </section>
</main>

<?php get_footer(); ?>

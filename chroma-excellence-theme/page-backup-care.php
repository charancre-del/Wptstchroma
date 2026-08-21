<?php
/**
 * Backup Care Page Template.
 *
 * @package Chroma_Excellence
 */

get_header();

$booking_url = '#book-backup-care';
$hero_image = get_the_post_thumbnail_url(get_queried_object_id(), 'full');
if (!$hero_image) {
    $hero_image = get_theme_file_uri('assets/images/early-start/synergy-classroom.jpg');
}
?>

<main id="primary" class="site-main bg-white" role="main">
    <section class="relative min-h-[34rem] h-[72vh] max-h-[48rem] flex items-end overflow-hidden bg-brand-ink"
        aria-labelledby="backup-care-title"
        style="background-image:url('<?php echo esc_url($hero_image); ?>');background-size:cover;background-position:center;">
        <div class="absolute inset-0" style="background-color:rgba(25,35,43,.72)" aria-hidden="true"></div>
        <div class="relative w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-14 md:pb-20 text-white">
            <p class="text-xs font-bold uppercase tracking-normal text-chroma-yellowLight mb-4">
                <?php esc_html_e('Care when plans change', 'chroma-excellence'); ?>
            </p>
            <h1 id="backup-care-title" class="font-serif text-5xl md:text-7xl font-semibold leading-tight tracking-normal max-w-4xl">
                <?php esc_html_e('Backup Care', 'chroma-excellence'); ?>
            </h1>
            <p class="mt-5 text-lg md:text-xl leading-relaxed text-white/90 max-w-2xl">
                    <?php esc_html_e('Choose the Chroma campus that works for your family, then reserve weekday care for one child or several, on one date or many.', 'chroma-excellence'); ?>
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="<?php echo esc_url($booking_url); ?>" class="inline-flex items-center justify-center min-h-12 px-7 py-3 bg-chroma-red text-white font-bold hover:bg-white hover:text-brand-ink transition">
                    <?php esc_html_e('Book backup care', 'chroma-excellence'); ?>
                </a>
                <a href="tel:+14704198981" class="inline-flex items-center justify-center min-h-12 px-7 py-3 border border-white/70 text-white font-bold hover:bg-white hover:text-brand-ink transition">
                    <i class="fa-solid fa-phone mr-2" aria-hidden="true"></i>
                    <?php esc_html_e('Call Chroma', 'chroma-excellence'); ?>
                </a>
            </div>
        </div>
    </section>

    <section class="border-b border-brand-ink/10 bg-brand-cream" aria-label="Backup care highlights">
        <div class="max-w-7xl mx-auto grid grid-cols-2 lg:grid-cols-4 px-4 sm:px-6 lg:px-8">
            <?php
            $highlights = array(
                array('$115', __('per child, per day', 'chroma-excellence')),
                array(__('12 months', 'chroma-excellence'), __('advance booking', 'chroma-excellence')),
                array(__('2 hours', 'chroma-excellence'), __('minimum notice', 'chroma-excellence')),
                array(__('9:30 AM', 'chroma-excellence'), __('latest drop-off', 'chroma-excellence')),
            );
            foreach ($highlights as $highlight) :
                ?>
                <div class="py-7 px-3 md:px-6 border-l border-brand-ink/10 first:border-l-0">
                    <strong class="block text-xl md:text-2xl text-brand-ink"><?php echo esc_html($highlight[0]); ?></strong>
                    <span class="block mt-1 text-sm text-brand-ink/70"><?php echo esc_html($highlight[1]); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="py-16 md:py-24 bg-white" aria-labelledby="backup-care-how-title">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-[0.72fr_1.28fr] gap-12 lg:gap-20">
            <div>
                <p class="text-xs font-bold uppercase tracking-normal text-chroma-red mb-3"><?php esc_html_e('Simple by design', 'chroma-excellence'); ?></p>
                <h2 id="backup-care-how-title" class="font-serif text-4xl md:text-5xl font-semibold leading-tight tracking-normal text-brand-ink">
                    <?php esc_html_e('A direct path to care.', 'chroma-excellence'); ?>
                </h2>
                <p class="mt-5 text-lg leading-relaxed text-brand-ink/70">
                    <?php esc_html_e('Any family may book. Campus approval is not required, and full payment securely confirms the reservation.', 'chroma-excellence'); ?>
                </p>
            </div>
            <ol class="grid md:grid-cols-3 gap-px bg-brand-ink/10 border border-brand-ink/10">
                <li class="bg-brand-cream p-6 md:p-8">
                    <span class="text-sm font-bold text-chroma-red">01</span>
                    <h3 class="mt-4 text-xl font-bold text-brand-ink"><?php esc_html_e('Choose campus and dates', 'chroma-excellence'); ?></h3>
                    <p class="mt-3 text-brand-ink/70 leading-relaxed"><?php esc_html_e('Select a campus from the full Chroma campus list and choose every needed care date in one order.', 'chroma-excellence'); ?></p>
                </li>
                <li class="bg-brand-cream p-6 md:p-8">
                    <span class="text-sm font-bold text-chroma-red">02</span>
                    <h3 class="mt-4 text-xl font-bold text-brand-ink"><?php esc_html_e('Complete child records', 'chroma-excellence'); ?></h3>
                    <p class="mt-3 text-brand-ink/70 leading-relaxed"><?php esc_html_e('We will request a secure GHL enrollment form only when a child record is missing or incomplete.', 'chroma-excellence'); ?></p>
                </li>
                <li class="bg-brand-cream p-6 md:p-8">
                    <span class="text-sm font-bold text-chroma-red">03</span>
                    <h3 class="mt-4 text-xl font-bold text-brand-ink"><?php esc_html_e('Pay and receive confirmation', 'chroma-excellence'); ?></h3>
                    <p class="mt-3 text-brand-ink/70 leading-relaxed"><?php esc_html_e('Review the total and use the secure GHL invoice emailed to you to pay through Chroma\'s connected Stripe account.', 'chroma-excellence'); ?></p>
                </li>
            </ol>
        </div>
    </section>

    <section id="book-backup-care" class="py-16 md:py-24 bg-brand-cream border-y border-brand-ink/10" aria-labelledby="backup-care-book-title">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mb-10">
                <p class="text-xs font-bold uppercase tracking-normal text-chroma-red mb-3"><?php esc_html_e('Secure reservation', 'chroma-excellence'); ?></p>
                <h2 id="backup-care-book-title" class="font-serif text-4xl md:text-5xl font-semibold leading-tight tracking-normal text-brand-ink">
                    <?php esc_html_e('Book all children and dates together.', 'chroma-excellence'); ?>
                </h2>
                <p class="mt-4 text-brand-ink/70 text-lg leading-relaxed">
                    <?php esc_html_e('Same-day reservations close at 7:30 AM. Drop-off must be no later than 9:30 AM.', 'chroma-excellence'); ?>
                </p>
            </div>
            <?php if (shortcode_exists('chroma_backup_care_cart')) : ?>
                <?php echo do_shortcode('[chroma_backup_care_cart]'); ?>
            <?php else : ?>
                <div class="border border-brand-ink/20 bg-white p-6 text-brand-ink">
                    <p><?php esc_html_e('Online backup-care booking is not available right now. Please call Chroma for assistance.', 'chroma-excellence'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="py-16 md:py-24 bg-white" aria-labelledby="backup-care-policy-title">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-12 lg:gap-20">
            <div>
                <p class="text-xs font-bold uppercase tracking-normal text-chroma-red mb-3"><?php esc_html_e('Before you reserve', 'chroma-excellence'); ?></p>
                <h2 id="backup-care-policy-title" class="font-serif text-4xl md:text-5xl font-semibold leading-tight tracking-normal text-brand-ink">
                    <?php esc_html_e('Clear policies, with no surprises.', 'chroma-excellence'); ?>
                </h2>
            </div>
            <dl class="divide-y divide-brand-ink/10 border-y border-brand-ink/10">
                <div class="py-5 grid sm:grid-cols-[10rem_1fr] gap-2 sm:gap-6">
                    <dt class="font-bold text-brand-ink"><?php esc_html_e('Eligibility', 'chroma-excellence'); ?></dt>
                    <dd class="text-brand-ink/70"><?php esc_html_e('Any family may reserve for children from 6 weeks through age 12; prior enrollment at Chroma is not required.', 'chroma-excellence'); ?></dd>
                </div>
                <div class="py-5 grid sm:grid-cols-[10rem_1fr] gap-2 sm:gap-6">
                    <dt class="font-bold text-brand-ink"><?php esc_html_e('Payment', 'chroma-excellence'); ?></dt>
                    <dd class="text-brand-ink/70"><?php esc_html_e('The full $115 child-date price is due through the secure GHL invoice emailed after checkout. Payment is processed by Chroma\'s connected Stripe account.', 'chroma-excellence'); ?></dd>
                </div>
                <div class="py-5 grid sm:grid-cols-[10rem_1fr] gap-2 sm:gap-6">
                    <dt class="font-bold text-brand-ink"><?php esc_html_e('Changes', 'chroma-excellence'); ?></dt>
                    <dd class="text-brand-ink/70"><?php esc_html_e('Cancellation is refundable and rescheduling is allowed until 72 hours before care.', 'chroma-excellence'); ?></dd>
                </div>
                <div class="py-5 grid sm:grid-cols-[10rem_1fr] gap-2 sm:gap-6">
                    <dt class="font-bold text-brand-ink"><?php esc_html_e('Late changes', 'chroma-excellence'); ?></dt>
                    <dd class="text-brand-ink/70"><?php esc_html_e('Changes inside 72 hours are not refundable or reschedulable. No exceptions apply.', 'chroma-excellence'); ?></dd>
                </div>
                <div class="py-5 grid sm:grid-cols-[10rem_1fr] gap-2 sm:gap-6">
                    <dt class="font-bold text-brand-ink"><?php esc_html_e('Campus closures', 'chroma-excellence'); ?></dt>
                    <dd class="text-brand-ink/70"><?php esc_html_e('If Chroma closes the selected campus, the parent may choose a refund or reschedule.', 'chroma-excellence'); ?></dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="py-16 md:py-24 bg-chroma-blueDark text-white" aria-labelledby="backup-care-help-title">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row md:items-center md:justify-between gap-7">
            <div class="max-w-3xl">
                <h2 id="backup-care-help-title" class="font-serif text-3xl md:text-4xl font-semibold tracking-normal"><?php esc_html_e('Need help with an existing reservation?', 'chroma-excellence'); ?></h2>
                <p class="mt-3 text-white/80 text-lg"><?php esc_html_e('Use the secure link in your confirmation email or contact our billing team.', 'chroma-excellence'); ?></p>
            </div>
            <a href="mailto:billing@chromaela.com" class="inline-flex items-center justify-center min-h-12 px-7 py-3 bg-white text-brand-ink font-bold hover:bg-chroma-yellowLight transition">
                <i class="fa-solid fa-envelope mr-2" aria-hidden="true"></i>
                billing@chromaela.com
            </a>
        </div>
    </section>
</main>

<?php get_footer(); ?>

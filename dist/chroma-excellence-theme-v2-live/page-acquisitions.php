<?php
/**
 * Template Name: Acquisitions
 * Acquisition opportunities form and information
 *
 * @package Chroma_Excellence
 */

get_header();
?>

<main id="primary" class="site-main">

    <section class="pageHero chroma-v2-page-hero py-20 lg:py-24 bg-white text-center">
        <div class="max-w-3xl mx-auto px-4 lg:px-6">
            <span class="text-chroma-green font-bold tracking-[0.2em] text-xs uppercase mb-4 block">
                <?php esc_html_e('Succession Planning', 'chroma-excellence'); ?>
            </span>
            <h1 class="font-serif text-5xl md:text-6xl text-brand-ink mb-6">
                <?php esc_html_e('Preserve Your Legacy.', 'chroma-excellence'); ?>
            </h1>
            <p class="text-lg text-brand-ink/60 leading-relaxed">
                <?php esc_html_e("You've built more than a business; you've built a community. When it's time to move on, choose a partner who values education over profit. We are educators, not private equity.", 'chroma-excellence'); ?>
            </p>
        </div>
    </section>

    <section class="py-20 lg:py-24 bg-brand-cream">
        <div class="max-w-6xl mx-auto px-4 lg:px-6 grid md:grid-cols-2 gap-12 lg:gap-16 items-start">
            <div>
                <h2 class="font-serif text-3xl font-bold mb-6 text-brand-ink">
                    <?php esc_html_e('Why Sell to Chroma?', 'chroma-excellence'); ?>
                </h2>
                <ul class="space-y-6">
                    <?php
                    $reasons = array(
                        array(__('Staff Retention', 'chroma-excellence'), __('We prioritize keeping your existing team, offering them improved benefits and career growth.', 'chroma-excellence')),
                        array(__('Educational Integrity', 'chroma-excellence'), __('We maintain high standards. Your families will see an investment in curriculum and facilities, not cost-cutting.', 'chroma-excellence')),
                        array(__('Confidential Process', 'chroma-excellence'), __('We understand the sensitivity of a sale. We move quickly and discreetly to protect your staff and enrollment.', 'chroma-excellence')),
                    );
                    foreach ($reasons as $index => $reason):
                        ?>
                        <li class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-chroma-green/20 flex items-center justify-center text-chroma-green shrink-0 font-bold">
                                <?php echo esc_html($index + 1); ?>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-brand-ink"><?php echo esc_html($reason[0]); ?></h3>
                                <p class="text-sm text-brand-ink/60"><?php echo esc_html($reason[1]); ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div id="contact" class="bg-white p-8 lg:p-10 rounded-[3rem] shadow-lg border border-brand-ink/5">
                <h2 class="font-bold text-xl mb-4 text-brand-ink">
                    <?php esc_html_e('Start a Conversation', 'chroma-excellence'); ?>
                </h2>
                <p class="text-xs text-brand-ink/50 mb-6">
                    <?php esc_html_e('All inquiries go directly to our CEO and are strictly confidential.', 'chroma-excellence'); ?>
                </p>
                <?php
                if (shortcode_exists('chroma_acquisition_form')) {
                    echo do_shortcode('[chroma_acquisition_form]');
                } else {
                    ?>
                    <form class="space-y-4" action="mailto:acquisitions@chromaela.com" method="post" enctype="text/plain">
                        <input type="text" name="name" placeholder="<?php esc_attr_e('Your Name', 'chroma-excellence'); ?>" class="w-full p-4 bg-brand-cream rounded-xl border-none" required>
                        <input type="text" name="school" placeholder="<?php esc_attr_e('School Name (Optional)', 'chroma-excellence'); ?>" class="w-full p-4 bg-brand-cream rounded-xl border-none">
                        <input type="email" name="email" placeholder="<?php esc_attr_e('Direct Email', 'chroma-excellence'); ?>" class="w-full p-4 bg-brand-cream rounded-xl border-none" required>
                        <input type="tel" name="phone" placeholder="<?php esc_attr_e('Direct Phone', 'chroma-excellence'); ?>" class="w-full p-4 bg-brand-cream rounded-xl border-none">
                        <button class="w-full py-4 bg-chroma-green text-white font-bold rounded-full uppercase tracking-widest hover:bg-brand-ink transition-colors">
                            <?php esc_html_e('Submit Inquiry', 'chroma-excellence'); ?>
                        </button>
                    </form>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>

</main>

<?php
get_footer();

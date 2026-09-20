<?php
/**
 * Parent-first Backup Care page template.
 *
 * @package Chroma_Excellence
 */

get_header();

$fallback_settings = array(
    'priceDisplay' => '$115',
    'billingUnitLabel' => 'per child, per care date',
    'eligibleAgeRange' => '6 weeks to 12 years',
    'operatingDaysLabel' => 'Weekdays',
    'sameDayDeadlineLabel' => '7:30 AM',
    'dropoffCutoffLabel' => '9:30 AM',
    'bookingHorizonDays' => 365,
    'refundCutoffHours' => 72,
    'supportEmail' => 'info@chromaela.com',
    'billingSupportEmail' => 'billing@chromaela.com',
    'completedEnrollmentRequired' => true,
    'enrollmentRequirementMessage' => 'Required enrollment and health records must be complete before care begins.',
    'availabilityNotice' => 'Campus and date options are subject to operational closures, staffing, and classroom ratio requirements.',
);
$runtime_settings = function_exists('chroma_backup_care_public_settings')
    ? chroma_backup_care_public_settings()
    : array();
$settings = array_merge($fallback_settings, is_array($runtime_settings) ? $runtime_settings : array());
$booking_url = '#reserve-backup-care';
$hero_image = get_the_post_thumbnail_url(get_queried_object_id(), 'full');
if (!$hero_image) {
    $hero_image = get_theme_file_uri('assets/images/early-start/synergy-classroom.jpg');
}

$facts = array(
    array($settings['eligibleAgeRange'], __('eligible ages', 'chroma-excellence')),
    array($settings['priceDisplay'], $settings['billingUnitLabel']),
    array($settings['operatingDaysLabel'], __('choose your campus', 'chroma-excellence')),
    array(sprintf(__('Book by %s', 'chroma-excellence'), $settings['sameDayDeadlineLabel']), __('for same-day care', 'chroma-excellence')),
);

$expectations = array(
    array('fa-door-open', __('Arrival', 'chroma-excellence'), __('The campus team reviews your reservation, confirms classroom placement, and helps your child settle in calmly.', 'chroma-excellence')),
    array('fa-people-roof', __('Classroom placement', 'chroma-excellence'), __('Children join an age-appropriate classroom based on the selected campus, the day’s staffing, and required ratios.', 'chroma-excellence')),
    array('fa-utensils', __('Meals and rest', 'chroma-excellence'), __('The campus shares the day’s meal, rest, and routine guidance so you know what is provided and what your child may need.', 'chroma-excellence')),
    array('fa-person-walking-arrow-right', __('Pickup', 'chroma-excellence'), __('Only authorized adults may pick up. Bring photo identification and follow the campus team’s pickup instructions.', 'chroma-excellence')),
);

$faqs = array(
    array(
        __('Does my child need to be enrolled at Chroma?', 'chroma-excellence'),
        __('No. Backup Care is available to enrolled and non-enrolled families. Each child must have complete required enrollment and health records before care begins.', 'chroma-excellence'),
    ),
    array(
        __('Can I reserve more than one child or date?', 'chroma-excellence'),
        __('Yes. One reservation can include siblings, multiple eligible weekdays, or both. Your summary shows every child-date and the complete total before payment.', 'chroma-excellence'),
    ),
    array(
        __('How does same-day booking work?', 'chroma-excellence'),
        sprintf(__('Same-day reservations must be completed by %1$s, provide the required notice, and plan for arrival by %2$s.', 'chroma-excellence'), $settings['sameDayDeadlineLabel'], $settings['dropoffCutoffLabel']),
    ),
    array(
        __('Can I cancel or move a reservation?', 'chroma-excellence'),
        sprintf(__('A paid child-date may be cancelled for a refund or moved to another eligible date at the same campus until %d hours before planned drop-off. Refund questions are handled by %s.', 'chroma-excellence'), (int) $settings['refundCutoffHours'], $settings['billingSupportEmail']),
    ),
    array(
        __('Is a selected campus or date guaranteed?', 'chroma-excellence'),
        $settings['availabilityNotice'],
    ),
    array(
        __('What if required records are incomplete?', 'chroma-excellence'),
        __('The reservation flow provides the enrollment links needed for each child. Care cannot begin until the required records have been reviewed and completed.', 'chroma-excellence'),
    ),
);
?>

<main id="primary" class="site-main backup-care-page" role="main">
    <section class="backup-care-hero" aria-labelledby="backup-care-title" style="background-image:url('<?php echo esc_url($hero_image); ?>')">
        <div class="backup-care-hero__overlay" aria-hidden="true"></div>
        <div class="backup-care-container backup-care-hero__content">
            <p class="backup-care-kicker"><?php esc_html_e('Care when plans change', 'chroma-excellence'); ?></p>
            <h1 id="backup-care-title"><?php esc_html_e('A dependable place for an unexpected day.', 'chroma-excellence'); ?></h1>
            <p><?php esc_html_e('Reserve warm, age-appropriate care when school is closed, a caregiver is unavailable, or work plans change.', 'chroma-excellence'); ?></p>
            <a class="backup-care-primary-button" href="<?php echo esc_url($booking_url); ?>"><?php esc_html_e('Reserve Backup Care', 'chroma-excellence'); ?></a>
        </div>
    </section>

    <section class="backup-care-facts" aria-label="Backup Care essentials">
        <div class="backup-care-container backup-care-facts__grid">
            <?php foreach ($facts as $fact) : ?>
                <div class="backup-care-fact">
                    <strong><?php echo esc_html($fact[0]); ?></strong>
                    <span><?php echo esc_html($fact[1]); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="reserve-backup-care" class="backup-care-booking" aria-labelledby="backup-care-booking-title">
        <div class="backup-care-container">
            <div class="backup-care-section-heading">
                <p class="backup-care-kicker"><?php esc_html_e('Reserve your dates', 'chroma-excellence'); ?></p>
                <h2 id="backup-care-booking-title"><?php esc_html_e('Build one reservation for your family.', 'chroma-excellence'); ?></h2>
                <p><?php esc_html_e('Verify your email, choose a campus and dates, add each child, then review the complete total before secure payment.', 'chroma-excellence'); ?></p>
            </div>
            <div
                class="chroma-backup-care-cart"
                data-chroma-backup-care-ghl
                data-campus="<?php echo esc_attr(isset($_GET['campus']) ? sanitize_key(wp_unslash($_GET['campus'])) : ''); ?>"
            >
                <p class="cbc-status"><?php esc_html_e('Loading secure GHL booking...', 'chroma-excellence'); ?></p>
                <noscript>
                    <div class="backup-care-notice">
                        <p><?php esc_html_e('JavaScript is required to build a multi-child, multi-date reservation.', 'chroma-excellence'); ?></p>
                        <a href="mailto:<?php echo esc_attr($settings['supportEmail']); ?>"><?php echo esc_html($settings['supportEmail']); ?></a>
                    </div>
                </noscript>
            </div>
        </div>
    </section>

    <section class="backup-care-support" aria-labelledby="backup-care-expect-title">
        <div class="backup-care-container">
            <div class="backup-care-section-heading backup-care-section-heading--center">
                <p class="backup-care-kicker"><?php esc_html_e('What to expect', 'chroma-excellence'); ?></p>
                <h2 id="backup-care-expect-title"><?php esc_html_e('A clear plan from arrival through pickup.', 'chroma-excellence'); ?></h2>
            </div>
            <div class="backup-care-expect-grid">
                <?php foreach ($expectations as $expectation) : ?>
                    <article class="backup-care-info-card">
                        <span class="backup-care-info-card__icon" aria-hidden="true"><i class="fa-solid <?php echo esc_attr($expectation[0]); ?>"></i></span>
                        <h3><?php echo esc_html($expectation[1]); ?></h3>
                        <p><?php echo esc_html($expectation[2]); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="backup-care-practical-grid">
                <article class="backup-care-practical-card">
                    <p class="backup-care-kicker"><?php esc_html_e('What to bring', 'chroma-excellence'); ?></p>
                    <h2><?php esc_html_e('Pack for your child’s age and routine.', 'chroma-excellence'); ?></h2>
                    <ul>
                        <li><?php esc_html_e('A labeled change of clothes and any comfort item your child uses.', 'chroma-excellence'); ?></li>
                        <li><?php esc_html_e('Diapers, wipes, bottles, formula, or sleep items when requested by the campus.', 'chroma-excellence'); ?></li>
                        <li><?php esc_html_e('Required medication or procedure documentation arranged with the campus before care.', 'chroma-excellence'); ?></li>
                        <li><?php esc_html_e('Photo identification for the authorized adult handling pickup.', 'chroma-excellence'); ?></li>
                    </ul>
                    <p class="backup-care-small-copy"><?php esc_html_e('Your selected campus will confirm its location-specific instructions before care.', 'chroma-excellence'); ?></p>
                </article>
                <article class="backup-care-practical-card backup-care-practical-card--accent">
                    <p class="backup-care-kicker"><?php esc_html_e('Before care begins', 'chroma-excellence'); ?></p>
                    <h2><?php esc_html_e('Records help us welcome your child safely.', 'chroma-excellence'); ?></h2>
                    <p><?php echo esc_html($settings['enrollmentRequirementMessage']); ?></p>
                    <p><?php echo esc_html($settings['availabilityNotice']); ?></p>
                    <a href="mailto:<?php echo esc_attr($settings['supportEmail']); ?>"><?php echo esc_html($settings['supportEmail']); ?></a>
                </article>
            </div>
        </div>
    </section>

    <section class="backup-care-faq" aria-labelledby="backup-care-faq-title">
        <div class="backup-care-container backup-care-faq__layout">
            <div class="backup-care-faq__intro">
                <p class="backup-care-kicker"><?php esc_html_e('Good to know', 'chroma-excellence'); ?></p>
                <h2 id="backup-care-faq-title"><?php esc_html_e('Clear answers before you reserve.', 'chroma-excellence'); ?></h2>
                <p><?php esc_html_e('For general help with Backup Care, contact our family support team.', 'chroma-excellence'); ?></p>
                <a href="mailto:<?php echo esc_attr($settings['supportEmail']); ?>"><?php echo esc_html($settings['supportEmail']); ?></a>
            </div>
            <div class="backup-care-faq__items">
                <?php foreach ($faqs as $index => $faq) : ?>
                    <details<?php echo 0 === $index ? ' open' : ''; ?>>
                        <summary><span><?php echo esc_html($faq[0]); ?></span><span aria-hidden="true">+</span></summary>
                        <p><?php echo esc_html($faq[1]); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="backup-care-closing" aria-labelledby="backup-care-closing-title">
        <div class="backup-care-container backup-care-closing__inner">
            <div>
                <p class="backup-care-kicker"><?php esc_html_e('One less thing to worry about', 'chroma-excellence'); ?></p>
                <h2 id="backup-care-closing-title"><?php esc_html_e('When your plan changes, Chroma can help the day keep moving.', 'chroma-excellence'); ?></h2>
            </div>
            <a class="backup-care-secondary-button" href="<?php echo esc_url($booking_url); ?>"><?php esc_html_e('Reserve Backup Care', 'chroma-excellence'); ?></a>
        </div>
    </section>
</main>

<?php get_footer(); ?>

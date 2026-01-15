<?php
/**
 * Template Name: Terms of Service
 *
 * Terms of Service page template
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();

$page_id = get_the_ID();

// Get last updated date
$last_updated = chroma_get_translated_meta($page_id, 'tos_last_updated') ?: 'December 26, 2024';

// Default Terms of Service content
$default_sections = array(
    array(
        'title' => __('Acceptance of Terms', 'chroma-excellence'),
        'content' => '<p>' . __('By enrolling your child at Chroma Early Learning Academy or using our website and services, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.', 'chroma-excellence') . '</p>
        <p>' . __('These terms apply to all parents, guardians, and visitors to our facilities and website. We reserve the right to modify these terms at any time, and such modifications will be effective immediately upon posting.', 'chroma-excellence') . '</p>'
    ),
    array(
        'title' => __('Enrollment & Registration', 'chroma-excellence'),
        'content' => '<p>' . __('Enrollment at Chroma Early Learning Academy is subject to:', 'chroma-excellence') . '</p>
        <ul>
            <li><strong>' . __('Age Requirements:', 'chroma-excellence') . '</strong> ' . __('Children must meet age requirements for their specific program (6 weeks to 12 years)', 'chroma-excellence') . '</li>
            <li><strong>' . __('Documentation:', 'chroma-excellence') . '</strong> ' . __('Complete enrollment forms, immunization records, and emergency contact information must be provided', 'chroma-excellence') . '</li>
            <li><strong>' . __('Registration Fee:', 'chroma-excellence') . '</strong> ' . __('A non-refundable registration fee is required to secure enrollment', 'chroma-excellence') . '</li>
            <li><strong>' . __('Availability:', 'chroma-excellence') . '</strong> ' . __('Enrollment is subject to space availability at your preferred location', 'chroma-excellence') . '</li>
        </ul>
        <p>' . __('We reserve the right to refuse or terminate enrollment for reasons including but not limited to: safety concerns, inability to meet the child\'s needs, or non-payment of fees.', 'chroma-excellence') . '</p>'
    ),
    array(
        'title' => __('Tuition & Payment', 'chroma-excellence'),
        'content' => '<p>' . __('By enrolling, you agree to the following payment terms:', 'chroma-excellence') . '</p>
        <ul>
            <li>' . __('Tuition is due weekly/monthly in advance as specified in your enrollment agreement', 'chroma-excellence') . '</li>
            <li>' . __('Late payments may incur additional fees as outlined in your enrollment contract', 'chroma-excellence') . '</li>
            <li>' . __('Tuition is due regardless of absences, holidays, or closures (except extended closures beyond 5 consecutive days)', 'chroma-excellence') . '</li>
            <li>' . __('A minimum of two weeks written notice is required for withdrawal', 'chroma-excellence') . '</li>
            <li>' . __('We accept major credit cards, ACH transfers, and approved subsidy payments (CAPS)', 'chroma-excellence') . '</li>
        </ul>
        <p>' . __('Failure to maintain current payment may result in suspension or termination of enrollment.', 'chroma-excellence') . '</p>'
    ),
    array(
        'title' => __('Hours of Operation & Policies', 'chroma-excellence'),
        'content' => '<p>' . __('Our standard hours of operation are 6:30 AM to 6:30 PM, Monday through Friday. Specific hours may vary by location.', 'chroma-excellence') . '</p>
        <ul>
            <li><strong>' . __('Drop-off/Pick-up:', 'chroma-excellence') . '</strong> ' . __('Children must be signed in and out daily by an authorized adult', 'chroma-excellence') . '</li>
            <li><strong>' . __('Late Pick-up:', 'chroma-excellence') . '</strong> ' . __('Late fees apply for pick-ups after closing time ($1 per minute after 6:35 PM)', 'chroma-excellence') . '</li>
            <li><strong>' . __('Illness Policy:', 'chroma-excellence') . '</strong> ' . __('Sick children may not attend and must be picked up within one hour of notification', 'chroma-excellence') . '</li>
            <li><strong>' . __('Closures:', 'chroma-excellence') . '</strong> ' . __('We observe major holidays and may close for inclement weather or emergencies', 'chroma-excellence') . '</li>
        </ul>
        <p>' . __('Complete policies are provided in your enrollment packet and posted at each location.', 'chroma-excellence') . '</p>'
    ),
    array(
        'title' => __('Health & Safety', 'chroma-excellence'),
        'content' => '<p>' . __('The health and safety of every child is our top priority. By enrolling, you agree to:', 'chroma-excellence') . '</p>
        <ul>
            <li>' . __('Provide accurate and complete health information for your child', 'chroma-excellence') . '</li>
            <li>' . __('Keep immunizations current as required by Georgia law', 'chroma-excellence') . '</li>
            <li>' . __('Notify us immediately of any changes to your child\'s health, allergies, or medications', 'chroma-excellence') . '</li>
            <li>' . __('Keep your child home when they are ill (fever, vomiting, diarrhea, contagious conditions)', 'chroma-excellence') . '</li>
            <li>' . __('Authorize emergency medical treatment if we cannot reach you', 'chroma-excellence') . '</li>
        </ul>
        <p>' . __('We are licensed by Georgia DECAL and maintain all required health and safety standards.', 'chroma-excellence') . '</p>'
    ),
    array(
        'title' => __('Photography & Media', 'chroma-excellence'),
        'content' => '<p>' . __('With your consent, we may photograph or video record your child for:', 'chroma-excellence') . '</p>
        <ul>
            <li>' . __('Daily activity updates shared through our parent communication app', 'chroma-excellence') . '</li>
            <li>' . __('Marketing materials (website, social media, brochures) - separate opt-in required', 'chroma-excellence') . '</li>
            <li>' . __('Internal training and curriculum development', 'chroma-excellence') . '</li>
        </ul>
        <p>' . __('You may opt out of marketing photography at any time by notifying your center director in writing.', 'chroma-excellence') . '</p>'
    ),
    array(
        'title' => __('Liability & Indemnification', 'chroma-excellence'),
        'content' => '<p>' . __('While we take every precaution to ensure your child\'s safety, you acknowledge that:', 'chroma-excellence') . '</p>
        <ul>
            <li>' . __('Children may occasionally sustain minor injuries during normal play activities', 'chroma-excellence') . '</li>
            <li>' . __('You will be notified immediately of any injury requiring medical attention', 'chroma-excellence') . '</li>
            <li>' . __('Chroma Early Learning Academy is not liable for lost or damaged personal items', 'chroma-excellence') . '</li>
            <li>' . __('You agree to indemnify and hold harmless Chroma Early Learning Academy from claims arising from your or your child\'s actions', 'chroma-excellence') . '</li>
        </ul>
        <p>' . __('Our liability is limited to the extent permitted by Georgia law.', 'chroma-excellence') . '</p>'
    ),
    array(
        'title' => __('Website Terms', 'chroma-excellence'),
        'content' => '<p>' . __('Use of our website (chromaela.com) is subject to the following:', 'chroma-excellence') . '</p>
        <ul>
            <li>' . __('Content is provided for informational purposes only', 'chroma-excellence') . '</li>
            <li>' . __('We do not guarantee the accuracy or completeness of website information', 'chroma-excellence') . '</li>
            <li>' . __('Links to third-party websites are provided for convenience and do not imply endorsement', 'chroma-excellence') . '</li>
            <li>' . __('Unauthorized use of our website may give rise to a claim for damages', 'chroma-excellence') . '</li>
        </ul>
        <p>' . __('Our website uses cookies to improve your experience. See our', 'chroma-excellence') . ' <a href="/privacy-policy/" class="text-chroma-blue hover:underline">' . __('Privacy Policy', 'chroma-excellence') . '</a> ' . __('for details.', 'chroma-excellence') . '</p>'
    ),
    array(
        'title' => __('Governing Law', 'chroma-excellence'),
        'content' => '<p>' . __('These Terms of Service shall be governed by and construed in accordance with the laws of the State of Georgia, without regard to its conflict of law provisions.', 'chroma-excellence') . '</p>
        <p>' . __('Any disputes arising from these terms or your use of our services shall be resolved through binding arbitration in Cobb County, Georgia, in accordance with the rules of the American Arbitration Association.', 'chroma-excellence') . '</p>'
    ),
    array(
        'title' => __('Contact Information', 'chroma-excellence'),
        'content' => '<p>' . __('If you have questions about these Terms of Service, please contact us:', 'chroma-excellence') . '</p>
        <p><strong>' . __('Chroma Early Learning Academy', 'chroma-excellence') . '</strong><br>
        ' . __('Email: info@chromaela.com', 'chroma-excellence') . '<br>
        ' . __('Phone: (404) 800-8000', 'chroma-excellence') . '<br>
        ' . __('Website: www.chromaela.com', 'chroma-excellence') . '</p>'
    ),
);

// Get stored sections or use defaults
$sections = array();
$has_custom_content = false;
for ($i = 1; $i <= 10; $i++) {
    $title = chroma_get_translated_meta($page_id, "tos_section{$i}_title");
    $content = chroma_get_translated_meta($page_id, "tos_section{$i}_content");

    if (!empty($title) || !empty($content)) {
        $has_custom_content = true;
        $sections[] = array(
            'title' => $title,
            'content' => $content,
        );
    }
}

// If no custom content, use defaults
if (!$has_custom_content) {
    $sections = $default_sections;
}
?>

<main class="min-h-screen bg-brand-cream py-24">
    <div class="max-w-3xl mx-auto px-4 lg:px-6">
        <h1 class="font-serif text-4xl md:text-5xl font-bold text-brand-ink mb-8"><?php the_title(); ?></h1>
        <p class="text-sm text-brand-ink/60 mb-12"><?php _e('Last Updated:', 'chroma-excellence'); ?> <?php echo esc_html($last_updated); ?></p>

        <div class="prose prose-lg text-brand-ink/80 max-w-none">
            <p class="text-lg leading-relaxed mb-8">
                <?php _e('Welcome to Chroma Early Learning Academy. These Terms of Service ("Terms") govern your use of our childcare services and website. Please read them carefully before enrolling your child or using our services.', 'chroma-excellence'); ?>
            </p>

            <?php foreach ($sections as $index => $section): ?>
                <?php if (!empty($section['title'])): ?>
                    <h2 class="font-serif font-bold text-2xl text-brand-ink mt-12 mb-4">
                        <?php echo ($index + 1) . '. ' . esc_html($section['title']); ?>
                    </h2>
                    <?php if (!empty($section['content'])): ?>
                        <div class="tos-section-content space-y-4">
                            <?php echo wp_kses_post($section['content']); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="mt-16 pt-8 border-t border-chroma-blue/20">
            <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>" class="text-chroma-blue hover:underline">
                <?php _e('View our Privacy Policy →', 'chroma-excellence'); ?>
            </a>
        </div>
    </div>
</main>

<?php get_footer(); ?>
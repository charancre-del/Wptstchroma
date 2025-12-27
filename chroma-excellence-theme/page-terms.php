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
$last_updated = get_post_meta($page_id, 'tos_last_updated', true) ?: 'December 26, 2024';

// Default Terms of Service content
$default_sections = array(
    array(
        'title' => 'Acceptance of Terms',
        'content' => '<p>By enrolling your child at Chroma Early Learning Academy or using our website and services, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>
        <p>These terms apply to all parents, guardians, and visitors to our facilities and website. We reserve the right to modify these terms at any time, and such modifications will be effective immediately upon posting.</p>'
    ),
    array(
        'title' => 'Enrollment & Registration',
        'content' => '<p>Enrollment at Chroma Early Learning Academy is subject to:</p>
        <ul>
            <li><strong>Age Requirements:</strong> Children must meet age requirements for their specific program (6 weeks to 12 years)</li>
            <li><strong>Documentation:</strong> Complete enrollment forms, immunization records, and emergency contact information must be provided</li>
            <li><strong>Registration Fee:</strong> A non-refundable registration fee is required to secure enrollment</li>
            <li><strong>Availability:</strong> Enrollment is subject to space availability at your preferred location</li>
        </ul>
        <p>We reserve the right to refuse or terminate enrollment for reasons including but not limited to: safety concerns, inability to meet the child\'s needs, or non-payment of fees.</p>'
    ),
    array(
        'title' => 'Tuition & Payment',
        'content' => '<p>By enrolling, you agree to the following payment terms:</p>
        <ul>
            <li>Tuition is due weekly/monthly in advance as specified in your enrollment agreement</li>
            <li>Late payments may incur additional fees as outlined in your enrollment contract</li>
            <li>Tuition is due regardless of absences, holidays, or closures (except extended closures beyond 5 consecutive days)</li>
            <li>A minimum of two weeks written notice is required for withdrawal</li>
            <li>We accept major credit cards, ACH transfers, and approved subsidy payments (CAPS)</li>
        </ul>
        <p>Failure to maintain current payment may result in suspension or termination of enrollment.</p>'
    ),
    array(
        'title' => 'Hours of Operation & Policies',
        'content' => '<p>Our standard hours of operation are 6:30 AM to 6:30 PM, Monday through Friday. Specific hours may vary by location.</p>
        <ul>
            <li><strong>Drop-off/Pick-up:</strong> Children must be signed in and out daily by an authorized adult</li>
            <li><strong>Late Pick-up:</strong> Late fees apply for pick-ups after closing time ($1 per minute after 6:35 PM)</li>
            <li><strong>Illness Policy:</strong> Sick children may not attend and must be picked up within one hour of notification</li>
            <li><strong>Closures:</strong> We observe major holidays and may close for inclement weather or emergencies</li>
        </ul>
        <p>Complete policies are provided in your enrollment packet and posted at each location.</p>'
    ),
    array(
        'title' => 'Health & Safety',
        'content' => '<p>The health and safety of every child is our top priority. By enrolling, you agree to:</p>
        <ul>
            <li>Provide accurate and complete health information for your child</li>
            <li>Keep immunizations current as required by Georgia law</li>
            <li>Notify us immediately of any changes to your child\'s health, allergies, or medications</li>
            <li>Keep your child home when they are ill (fever, vomiting, diarrhea, contagious conditions)</li>
            <li>Authorize emergency medical treatment if we cannot reach you</li>
        </ul>
        <p>We are licensed by Georgia DECAL and maintain all required health and safety standards.</p>'
    ),
    array(
        'title' => 'Photography & Media',
        'content' => '<p>With your consent, we may photograph or video record your child for:</p>
        <ul>
            <li>Daily activity updates shared through our parent communication app</li>
            <li>Marketing materials (website, social media, brochures) - separate opt-in required</li>
            <li>Internal training and curriculum development</li>
        </ul>
        <p>You may opt out of marketing photography at any time by notifying your center director in writing.</p>'
    ),
    array(
        'title' => 'Liability & Indemnification',
        'content' => '<p>While we take every precaution to ensure your child\'s safety, you acknowledge that:</p>
        <ul>
            <li>Children may occasionally sustain minor injuries during normal play activities</li>
            <li>You will be notified immediately of any injury requiring medical attention</li>
            <li>Chroma Early Learning Academy is not liable for lost or damaged personal items</li>
            <li>You agree to indemnify and hold harmless Chroma Early Learning Academy from claims arising from your or your child\'s actions</li>
        </ul>
        <p>Our liability is limited to the extent permitted by Georgia law.</p>'
    ),
    array(
        'title' => 'Website Terms',
        'content' => '<p>Use of our website (chromaela.com) is subject to the following:</p>
        <ul>
            <li>Content is provided for informational purposes only</li>
            <li>We do not guarantee the accuracy or completeness of website information</li>
            <li>Links to third-party websites are provided for convenience and do not imply endorsement</li>
            <li>Unauthorized use of our website may give rise to a claim for damages</li>
        </ul>
        <p>Our website uses cookies to improve your experience. See our <a href="/privacy-policy/" class="text-chroma-blue hover:underline">Privacy Policy</a> for details.</p>'
    ),
    array(
        'title' => 'Governing Law',
        'content' => '<p>These Terms of Service shall be governed by and construed in accordance with the laws of the State of Georgia, without regard to its conflict of law provisions.</p>
        <p>Any disputes arising from these terms or your use of our services shall be resolved through binding arbitration in Cobb County, Georgia, in accordance with the rules of the American Arbitration Association.</p>'
    ),
    array(
        'title' => 'Contact Information',
        'content' => '<p>If you have questions about these Terms of Service, please contact us:</p>
        <p><strong>Chroma Early Learning Academy</strong><br>
        Email: info@chromaela.com<br>
        Phone: (404) 800-8000<br>
        Website: www.chromaela.com</p>'
    ),
);

// Get stored sections or use defaults
$sections = array();
$has_custom_content = false;
for ($i = 1; $i <= 10; $i++) {
    $title = get_post_meta($page_id, "tos_section{$i}_title", true);
    $content = get_post_meta($page_id, "tos_section{$i}_content", true);

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
        <p class="text-sm text-brand-ink/60 mb-12">Last Updated: <?php echo esc_html($last_updated); ?></p>

        <div class="prose prose-lg text-brand-ink/80 max-w-none">
            <p class="text-lg leading-relaxed mb-8">
                Welcome to Chroma Early Learning Academy. These Terms of Service ("Terms") govern your use of our
                childcare services and website. Please read them carefully before enrolling your child or using our
                services.
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
                View our Privacy Policy →
            </a>
        </div>
    </div>
</main>

<?php get_footer(); ?>
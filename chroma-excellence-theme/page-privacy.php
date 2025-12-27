<?php
/**
 * Template Name: Privacy Policy
 *
 * Privacy & Families' Rights Policy page template using theme header/footer
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

get_header();

$page_id = get_the_ID();

// Get last updated date
$last_updated = get_post_meta($page_id, 'privacy_last_updated', true) ?: 'December 26, 2024';

// Default content if no sections are set
$default_sections = array(
  array(
    'title' => 'Information We Collect',
    'content' => '<p>At Chroma Early Learning Academy, we collect information necessary to provide excellent childcare services to your family. This includes:</p>
        <ul>
            <li><strong>Contact Information:</strong> Names, addresses, phone numbers, and email addresses of parents/guardians</li>
            <li><strong>Child Information:</strong> Child\'s name, date of birth, allergies, medical conditions, and emergency contacts</li>
            <li><strong>Enrollment Data:</strong> Program preferences, schedules, and payment information</li>
            <li><strong>Website Usage:</strong> Cookies and analytics data when you visit our website</li>
        </ul>'
  ),
  array(
    'title' => 'How We Use Your Information',
    'content' => '<p>We use the information we collect to:</p>
        <ul>
            <li>Provide safe, quality childcare services</li>
            <li>Communicate with you about your child\'s care and development</li>
            <li>Process enrollment applications and payments</li>
            <li>Comply with state licensing requirements (Georgia DECAL)</li>
            <li>Improve our programs and services</li>
            <li>Send occasional newsletters and updates (you may opt out at any time)</li>
        </ul>'
  ),
  array(
    'title' => 'Information Security',
    'content' => '<p>We take the security of your personal information seriously. We implement appropriate technical and organizational measures to protect your data, including:</p>
        <ul>
            <li>Secure, encrypted storage of sensitive information</li>
            <li>Limited access to personal data on a need-to-know basis</li>
            <li>Regular staff training on privacy and data protection</li>
            <li>Physical security measures at all our locations</li>
        </ul>
        <p>While we strive to protect your information, no method of transmission over the Internet is 100% secure. We cannot guarantee absolute security.</p>'
  ),
  array(
    'title' => 'Your Rights',
    'content' => '<p>As a parent or guardian enrolled with Chroma Early Learning Academy, you have the right to:</p>
        <ul>
            <li><strong>Access:</strong> Request a copy of the personal information we hold about you and your child</li>
            <li><strong>Correction:</strong> Request corrections to any inaccurate information</li>
            <li><strong>Deletion:</strong> Request deletion of your data, subject to legal retention requirements</li>
            <li><strong>Opt-Out:</strong> Unsubscribe from marketing communications at any time</li>
        </ul>
        <p>To exercise any of these rights, please contact your center director or email us at privacy@chromaela.com.</p>'
  ),
  array(
    'title' => 'Contact Us',
    'content' => '<p>If you have any questions about this Privacy Policy or our data practices, please contact us:</p>
        <p><strong>Chroma Early Learning Academy</strong><br>
        Email: privacy@chromaela.com<br>
        Phone: (404) 800-8000</p>
        <p>This policy may be updated from time to time. We will notify you of any material changes by posting the new policy on this page with an updated "Last Updated" date.</p>'
  ),
);

// Get stored sections or use defaults
$sections = array();
$has_custom_content = false;
for ($i = 1; $i <= 5; $i++) {
  $title = get_post_meta($page_id, "privacy_section{$i}_title", true);
  $content = get_post_meta($page_id, "privacy_section{$i}_content", true);

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
        Chroma Early Learning Academy ("we," "us," or "our") is committed to protecting your privacy and that of your
        children. This Privacy Policy explains how we collect, use, and safeguard your personal information.
      </p>

      <?php foreach ($sections as $section): ?>
        <?php if (!empty($section['title'])): ?>
          <h2 class="font-serif font-bold text-2xl text-brand-ink mt-12 mb-4"><?php echo esc_html($section['title']); ?>
          </h2>
          <?php if (!empty($section['content'])): ?>
            <div class="privacy-section-content space-y-4">
              <?php echo wp_kses_post($section['content']); ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <div class="mt-16 pt-8 border-t border-chroma-blue/20">
      <a href="<?php echo esc_url(home_url('/terms-of-service/')); ?>" class="text-chroma-blue hover:underline">
        View our Terms of Service →
      </a>
    </div>
  </div>
</main>

<?php get_footer(); ?>
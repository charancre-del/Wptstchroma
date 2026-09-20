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
$last_updated = chroma_get_translated_meta($page_id, 'privacy_last_updated') ?: 'December 26, 2024';

// Default content if no sections are set
$default_sections = array(
  array(
    'title' => __('Information We Collect', 'chroma-excellence'),
    'content' => '<p>' . __('At Chroma Early Learning Academy, we collect information necessary to provide excellent childcare services to your family. This includes:', 'chroma-excellence') . '</p>
        <ul>
            <li><strong>' . __('Contact Information:', 'chroma-excellence') . '</strong> ' . __('Names, addresses, phone numbers, and email addresses of parents/guardians', 'chroma-excellence') . '</li>
            <li><strong>' . __('Child Information:', 'chroma-excellence') . '</strong> ' . __('Child\'s name, date of birth, allergies, medical conditions, and emergency contacts', 'chroma-excellence') . '</li>
            <li><strong>' . __('Enrollment Data:', 'chroma-excellence') . '</strong> ' . __('Program preferences, schedules, and payment information', 'chroma-excellence') . '</li>
            <li><strong>' . __('Website Usage:', 'chroma-excellence') . '</strong> ' . __('Cookies and analytics data when you visit our website', 'chroma-excellence') . '</li>
        </ul>'
  ),
  array(
    'title' => __('How We Use Your Information', 'chroma-excellence'),
    'content' => '<p>' . __('We use the information we collect to:', 'chroma-excellence') . '</p>
        <ul>
            <li>' . __('Provide safe, quality childcare services', 'chroma-excellence') . '</li>
            <li>' . __('Communicate with you about your child\'s care and development', 'chroma-excellence') . '</li>
            <li>' . __('Process enrollment applications and payments', 'chroma-excellence') . '</li>
            <li>' . __('Comply with state licensing requirements (Georgia DECAL)', 'chroma-excellence') . '</li>
            <li>' . __('Improve our programs and services', 'chroma-excellence') . '</li>
            <li>' . __('Send occasional newsletters and updates (you may opt out at any time)', 'chroma-excellence') . '</li>
        </ul>'
  ),
  array(
    'title' => __('Information Security', 'chroma-excellence'),
    'content' => '<p>' . __('We take the security of your personal information seriously. We implement appropriate technical and organizational measures to protect your data, including:', 'chroma-excellence') . '</p>
        <ul>
            <li>' . __('Secure, encrypted storage of sensitive information', 'chroma-excellence') . '</li>
            <li>' . __('Limited access to personal data on a need-to-know basis', 'chroma-excellence') . '</li>
            <li>' . __('Regular staff training on privacy and data protection', 'chroma-excellence') . '</li>
            <li>' . __('Physical security measures at all our locations', 'chroma-excellence') . '</li>
        </ul>
        <p>' . __('While we strive to protect your information, no method of transmission over the Internet is 100% secure. We cannot guarantee absolute security.', 'chroma-excellence') . '</p>'
  ),
  array(
    'title' => __('Your Rights', 'chroma-excellence'),
    'content' => '<p>' . __('As a parent or guardian enrolled with Chroma Early Learning Academy, you have the right to:', 'chroma-excellence') . '</p>
        <ul>
            <li><strong>' . __('Access:', 'chroma-excellence') . '</strong> ' . __('Request a copy of the personal information we hold about you and your child', 'chroma-excellence') . '</li>
            <li><strong>' . __('Correction:', 'chroma-excellence') . '</strong> ' . __('Request corrections to any inaccurate information', 'chroma-excellence') . '</li>
            <li><strong>' . __('Deletion:', 'chroma-excellence') . '</strong> ' . __('Request deletion of your data, subject to legal retention requirements', 'chroma-excellence') . '</li>
            <li><strong>' . __('Opt-Out:', 'chroma-excellence') . '</strong> ' . __('Unsubscribe from marketing communications at any time', 'chroma-excellence') . '</li>
        </ul>
        <p>' . __('To exercise any of these rights, please contact your center director or email us at privacy@chromaela.com.', 'chroma-excellence') . '</p>'
  ),
  array(
    'title' => __('Contact Us', 'chroma-excellence'),
    'content' => '<p>' . __('If you have any questions about this Privacy Policy or our data practices, please contact us:', 'chroma-excellence') . '</p>
        <p><strong>' . __('Chroma Early Learning Academy', 'chroma-excellence') . '</strong><br>
        ' . __('Email: privacy@chromaela.com', 'chroma-excellence') . '<br>
        ' . __('Phone: (404) 800-8000', 'chroma-excellence') . '</p>
        <p>' . __('This policy may be updated from time to time. We will notify you of any material changes by posting the new policy on this page with an updated "Last Updated" date.', 'chroma-excellence') . '</p>'
  ),
);

// Get stored sections or use defaults
$sections = array();
$has_custom_content = false;
for ($i = 1; $i <= 5; $i++) {
  $title = chroma_get_translated_meta($page_id, "privacy_section{$i}_title");
  $content = chroma_get_translated_meta($page_id, "privacy_section{$i}_content");

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

<main class="min-h-screen bg-brand-cream">
  <section class="pageHero chroma-v2-page-hero bg-white border-b border-chroma-blue/10 py-16 lg:py-20 text-center">
    <div class="max-w-4xl mx-auto px-4 lg:px-6">
      <span class="inline-flex items-center gap-2 rounded-full bg-chroma-blue/10 border border-chroma-blue/10 px-5 py-2.5 text-[11px] font-bold uppercase tracking-[0.2em] text-chroma-blue mb-7">
        <span class="w-2 h-2 rounded-full bg-chroma-blue"></span>
        <?php _e('Family Privacy', 'chroma-excellence'); ?>
      </span>
      <h1 class="font-serif text-[2.65rem] md:text-6xl lg:text-7xl text-brand-ink tracking-[-0.04em] leading-[0.95] mb-5"><?php the_title(); ?></h1>
      <p class="text-sm text-brand-ink/60"><?php _e('Last Updated:', 'chroma-excellence'); ?> <?php echo esc_html($last_updated); ?></p>
    </div>
  </section>

  <section class="py-16 lg:py-20">
  <div class="max-w-3xl mx-auto px-4 lg:px-6">
    <div class="bg-white rounded-[2.5rem] border border-chroma-blue/10 shadow-soft p-7 md:p-10 lg:p-12 prose prose-lg text-brand-ink/80 max-w-none">
      <p class="text-lg leading-relaxed mb-8">
        <?php _e('Chroma Early Learning Academy ("we," "us," or "our") is committed to protecting your privacy and that of your children. This Privacy Policy explains how we collect, use, and safeguard your personal information.', 'chroma-excellence'); ?>
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
        <?php _e('View our Terms of Service →', 'chroma-excellence'); ?>
      </a>
    </div>
  </div>
  </section>
</main>

<?php get_footer(); ?>

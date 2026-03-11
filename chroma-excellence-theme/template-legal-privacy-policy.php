<?php
/**
 * Template Name: Privacy Policy (Legal)
 * Template Post Type: page
 *
 * @package Chroma_Excellence
 */

get_header();

while (have_posts()):
    the_post();

    $company_name = get_bloginfo('name');
    $last_updated = get_the_modified_date('F j, Y');
    $default_sections = [
        [
            'title' => '1. Information We Collect',
            'content' => '<p>We may collect contact details, enrollment information, child-related records you submit, payment and billing information handled by approved processors, and technical website data such as IP address, device type, and analytics events.</p>',
        ],
        [
            'title' => '2. How We Use Information',
            'content' => '<p>We use information to operate services, communicate with families, process enrollment and billing, maintain safety and compliance, improve programs, and provide support.</p>',
        ],
        [
            'title' => '3. Sharing and Disclosure',
            'content' => '<p>We may share information with authorized service providers, staff, affiliates, and legal authorities where required. We do not sell personal information.</p>',
        ],
        [
            'title' => '4. Security and Retention',
            'content' => '<p>We apply reasonable administrative, technical, and physical safeguards and retain records only as long as required for operations, legal obligations, and dispute resolution.</p>',
        ],
        [
            'title' => '5. Your Rights and Contact',
            'content' => '<p>You may request access, correction, or deletion of your information, subject to legal limits. Contact us through the contact details published on this site.</p>',
        ],
    ];
    ?>

    <main class="min-h-screen bg-brand-cream py-24">
        <div class="max-w-3xl mx-auto px-4 lg:px-6">
            <h1 class="font-serif text-4xl md:text-5xl font-bold text-brand-ink mb-6"><?php the_title(); ?></h1>
            <p class="text-sm text-brand-ink/60 mb-10">Last Updated: <?php echo esc_html($last_updated); ?></p>

            <div class="prose prose-lg text-brand-ink/80 max-w-none">
                <p><strong><?php echo esc_html($company_name); ?></strong> respects your privacy. This policy explains how information is collected, used, shared, and protected.</p>

                <?php if (trim((string) get_the_content()) !== ''): ?>
                    <?php the_content(); ?>
                <?php else: ?>
                    <?php foreach ($default_sections as $section): ?>
                        <h2 class="font-serif text-2xl font-bold text-brand-ink mt-10 mb-3"><?php echo esc_html($section['title']); ?></h2>
                        <?php echo wp_kses_post($section['content']); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="mt-12 pt-6 border-t border-brand-ink/10">
                <a href="<?php echo esc_url(home_url('/terms-of-use/')); ?>" class="text-chroma-blue hover:underline">View Terms of Use</a>
            </div>
        </div>
    </main>

<?php
endwhile;

get_footer();
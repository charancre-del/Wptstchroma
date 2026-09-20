<?php
/**
 * Template Name: Terms of Use (Legal)
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
            'title' => '1. Acceptance of Terms',
            'content' => '<p>By accessing this website or using our services, you agree to these Terms of Use. If you do not agree, do not use this site or services.</p>',
        ],
        [
            'title' => '2. Permitted and Prohibited Use',
            'content' => '<p>You may use this site only for lawful purposes. You may not attempt unauthorized access, interfere with platform operations, submit false information, or misuse content and intellectual property.</p>',
        ],
        [
            'title' => '3. Intellectual Property',
            'content' => '<p>All site content, logos, text, graphics, and software are owned by or licensed to us and protected by applicable law. No rights are granted except as explicitly stated.</p>',
        ],
        [
            'title' => '4. Disclaimers and Liability',
            'content' => '<p>The site and services are provided on an "as is" and "as available" basis. To the maximum extent allowed by law, we disclaim warranties and limit liability for indirect or consequential damages.</p>',
        ],
        [
            'title' => '5. Governing Law and Updates',
            'content' => '<p>These Terms are governed by applicable state law. We may revise these Terms at any time by posting updates on this page.</p>',
        ],
    ];
    ?>

    <main class="min-h-screen bg-brand-cream py-24">
        <div class="max-w-3xl mx-auto px-4 lg:px-6">
            <h1 class="font-serif text-4xl md:text-5xl font-bold text-brand-ink mb-6"><?php the_title(); ?></h1>
            <p class="text-sm text-brand-ink/60 mb-10">Last Updated: <?php echo esc_html($last_updated); ?></p>

            <div class="prose prose-lg text-brand-ink/80 max-w-none">
                <p>These Terms of Use apply to your use of <strong><?php echo esc_html($company_name); ?></strong> digital properties and related services.</p>

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
                <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>" class="text-chroma-blue hover:underline">View Privacy Policy</a>
            </div>
        </div>
    </main>

<?php
endwhile;

get_footer();
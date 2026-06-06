<?php
/**
 * Template Name: HIPAA Notice (Legal)
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
            'title' => '1. Scope of This Notice',
            'content' => '<p>This Notice describes how protected health information may be used and disclosed if and when HIPAA applies to services provided by our organization.</p>',
        ],
        [
            'title' => '2. Uses and Disclosures',
            'content' => '<p>Protected health information may be used or disclosed for treatment, payment, health care operations, and other purposes permitted or required by law.</p>',
        ],
        [
            'title' => '3. Uses Requiring Authorization',
            'content' => '<p>Any use or disclosure not otherwise permitted by law will require written authorization, which may be revoked in writing subject to legal limits.</p>',
        ],
        [
            'title' => '4. Individual Rights',
            'content' => '<p>Individuals may request access, amendment, restrictions, confidential communications, accounting of disclosures, and a paper copy of this notice, as applicable by law.</p>',
        ],
        [
            'title' => '5. Complaints and Contact',
            'content' => '<p>You may submit privacy complaints without retaliation. Contact the Privacy Officer listed on this page for questions or requests.</p>',
        ],
        [
            'title' => '6. Changes to This Notice',
            'content' => '<p>We may revise this notice and publish updates on this page with a new effective date.</p>',
        ],
    ];
    ?>

    <main class="min-h-screen bg-brand-cream py-24">
        <div class="max-w-3xl mx-auto px-4 lg:px-6">
            <h1 class="font-serif text-4xl md:text-5xl font-bold text-brand-ink mb-6"><?php the_title(); ?></h1>
            <p class="text-sm text-brand-ink/60 mb-10">Last Updated: <?php echo esc_html($last_updated); ?></p>

            <div class="prose prose-lg text-brand-ink/80 max-w-none">
                <p><strong><?php echo esc_html($company_name); ?></strong> provides this HIPAA Notice template for legal/privacy communications where applicable.</p>

                <?php if (trim((string) get_the_content()) !== ''): ?>
                    <?php the_content(); ?>
                <?php else: ?>
                    <?php foreach ($default_sections as $section): ?>
                        <h2 class="font-serif text-2xl font-bold text-brand-ink mt-10 mb-3"><?php echo esc_html($section['title']); ?></h2>
                        <?php echo wp_kses_post($section['content']); ?>
                    <?php endforeach; ?>
                    <p><em>Important: Confirm with legal counsel whether HIPAA applies to your operations and update this page accordingly.</em></p>
                <?php endif; ?>
            </div>

            <div class="mt-12 pt-6 border-t border-brand-ink/10">
                <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>" class="text-chroma-blue hover:underline">Back to Privacy Policy</a>
            </div>
        </div>
    </main>

<?php
endwhile;

get_footer();
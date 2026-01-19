<?php
/**
 * Hreflang Auditor Dashboard
 * Validates hreflang implementation and identifies issues.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Hreflang_Auditor
{
    public function init()
    {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu()
    {
        add_submenu_page(
            'chroma-seo-dashboard',
            'Hreflang Auditor',
            'Hreflang Auditor',
            'manage_options',
            'chroma-hreflang-auditor',
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        $post_types = ['page', 'location', 'program', 'city', 'post', 'team_member'];
        $issues = [];
        $passed = [];

        $posts = get_posts([
            'post_type' => $post_types,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        foreach ($posts as $post) {
            $alternates = [];
            if (class_exists('Chroma_Multilingual_Manager')) {
                $alternates = Chroma_Multilingual_Manager::get_alternates($post->ID);
            }

            $has_es_content = get_post_meta($post->ID, '_chroma_es_content', true);
            $es_url = $alternates['es'] ?? '';
            $en_url = $alternates['en'] ?? '';

            // Check for issues
            $post_issues = [];

            if (empty($en_url)) {
                $post_issues[] = 'Missing English URL';
            }
            if (empty($es_url)) {
                $post_issues[] = 'Missing Spanish URL';
            }
            if (!empty($es_url) && empty($has_es_content)) {
                $post_issues[] = 'Spanish URL exists but no Spanish content (will show fallback banner)';
            }

            // Check for self-referencing (good practice)
            // Check URLs are valid
            if (!empty($en_url) && !filter_var($en_url, FILTER_VALIDATE_URL)) {
                $post_issues[] = 'Invalid English URL format';
            }
            if (!empty($es_url) && !filter_var($es_url, FILTER_VALIDATE_URL)) {
                $post_issues[] = 'Invalid Spanish URL format';
            }

            if (empty($post_issues)) {
                $passed[] = $post;
            } else {
                $issues[] = [
                    'post' => $post,
                    'issues' => $post_issues,
                    'en_url' => $en_url,
                    'es_url' => $es_url
                ];
            }
        }

        ?>
        <div class="wrap chroma-seo-dashboard">
            <h1>🔍 Hreflang Auditor</h1>
            <p>Validates hreflang implementation across your site.</p>

            <!-- Summary Card -->
            <div class="card" style="padding: 20px; max-width: 600px; margin-bottom: 20px;">
                <h3>📊 Audit Summary</h3>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; text-align: center;">
                    <div>
                        <div style="font-size: 32px; font-weight: bold; color: #333;"><?php echo count($posts); ?></div>
                        <div style="color: #666;">Total Pages</div>
                    </div>
                    <div>
                        <div style="font-size: 32px; font-weight: bold; color: green;"><?php echo count($passed); ?></div>
                        <div style="color: #666;">Passed</div>
                    </div>
                    <div>
                        <div style="font-size: 32px; font-weight: bold; color: <?php echo count($issues) > 0 ? '#856404' : 'green'; ?>"><?php echo count($issues); ?></div>
                        <div style="color: #666;">Issues</div>
                    </div>
                </div>
            </div>

            <?php if (count($issues) > 0): ?>
            <div class="card" style="padding: 20px; max-width: 1200px;">
                <h3>⚠️ Issues Found</h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Type</th>
                            <th>Issues</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($issues as $item): ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($item['post']->ID); ?>">
                                    <?php echo esc_html($item['post']->post_title); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html(ucfirst($item['post']->post_type)); ?></td>
                            <td>
                                <ul style="margin: 0; padding-left: 20px;">
                                    <?php foreach ($item['issues'] as $issue): ?>
                                        <li style="color: #856404;"><?php echo esc_html($issue); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td>
                                <a href="<?php echo get_edit_post_link($item['post']->ID); ?>" class="button">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card" style="padding: 20px; max-width: 600px; background: #d4edda; border-color: #c3e6cb;">
                <h3 style="color: #155724; margin-top: 0;">✅ All Clear!</h3>
                <p style="color: #155724; margin-bottom: 0;">
                    All pages have valid hreflang configurations. Your multilingual SEO is properly set up.
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}



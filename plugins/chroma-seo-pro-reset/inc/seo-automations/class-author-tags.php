<?php
/**
 * Author Tags - E-E-A-T Enhancement
 * Adds proper author attribution to content
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Author_Tags
{
    public function __construct() {
        add_action('wp_head', [$this, 'output_author_meta']);
        add_filter('the_content', [$this, 'append_author_box'], 30);
    }
    
    /**
     * Output author meta tag
     */
    public function output_author_meta() {
        if (!get_option('chroma_seo_show_author_meta', true)) {
            return;
        }
        
        if (!is_singular(['post', 'page'])) {
            return;
        }
        
        $author = $this->get_author_info(get_the_ID());
        
        if ($author) {
            echo '<meta name="author" content="' . esc_attr($author['name']) . '">' . "\n";
            
            // Schema.org Person
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => $author['name']
            ];
            
            if ($author['url']) {
                $schema['url'] = $author['url'];
            }
            
            if ($author['avatar']) {
                $schema['image'] = $author['avatar'];
            }
            
            if ($author['description']) {
                $schema['description'] = $author['description'];
            }
            
            Chroma_Schema_Registry::register($schema, ['source' => 'author-tags']);
        }
    }
    
    /**
     * Append author box after content
     */
    public function append_author_box($content) {
        if (!get_option('chroma_seo_show_author_box', true) || doing_filter('get_the_excerpt')) {
            return $content;
        }
        
        if (!is_singular('post')) {
            return $content;
        }
        
        $author = $this->get_author_info(get_the_ID());
        
        if (!$author) {
            return $content;
        }
        
        ob_start();
        ?>
        <div class="chroma-author-box">
            <?php if ($author['avatar']): ?>
                <img src="<?php echo esc_url($author['avatar']); ?>" alt="<?php echo esc_attr($author['name']); ?>" class="author-avatar">
            <?php endif; ?>
            <div class="author-info">
                <p class="author-label">Written by</p>
                <h4 class="author-name">
                    <?php if ($author['url']): ?>
                        <a href="<?php echo esc_url($author['url']); ?>"><?php echo esc_html($author['name']); ?></a>
                    <?php else: ?>
                        <?php echo esc_html($author['name']); ?>
                    <?php endif; ?>
                </h4>
                <?php if ($author['title']): ?>
                    <p class="author-title"><?php echo esc_html($author['title']); ?></p>
                <?php endif; ?>
                <?php if ($author['description']): ?>
                    <p class="author-bio"><?php echo esc_html($author['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <style>
            .chroma-author-box {
                display: flex;
                gap: 20px;
                padding: 25px;
                background: #f8f9fa;
                border-radius: 12px;
                margin: 40px 0;
            }
            .author-avatar {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                object-fit: cover;
            }
            .author-info { flex: 1; }
            .author-label {
                margin: 0;
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: #888;
            }
            .author-name {
                margin: 5px 0 10px;
                font-size: 18px;
            }
            .author-name a {
                color: inherit;
                text-decoration: none;
            }
            .author-name a:hover { text-decoration: underline; }
            .author-title {
                margin: 0 0 10px;
                color: #666;
                font-size: 14px;
            }
            .author-bio {
                margin: 0;
                font-size: 14px;
                line-height: 1.6;
                color: #555;
            }
        </style>
        <?php
        
        return $content . ob_get_clean();
    }
    
    /**
     * Get author info
     */
    private function get_author_info($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return null;
        }
        
        $author_id = $post->post_author;
        
        // Check for team member association
        $team_member_id = get_post_meta($post_id, '_author_team_member', true);
        
        if ($team_member_id) {
            $team_member = get_post($team_member_id);
            if ($team_member) {
                return [
                    'name' => $team_member->post_title,
                    'url' => get_permalink($team_member),
                    'avatar' => get_the_post_thumbnail_url($team_member, 'thumbnail'),
                    'title' => get_post_meta($team_member_id, 'team_member_title', true),
                    'description' => get_the_excerpt($team_member)
                ];
            }
        }
        
        // Default to WP user
        return [
            'name' => get_the_author_meta('display_name', $author_id),
            'url' => get_author_posts_url($author_id),
            'avatar' => get_avatar_url($author_id, ['size' => 160]),
            'title' => get_the_author_meta('title', $author_id),
            'description' => get_the_author_meta('description', $author_id)
        ];
    }
}

new Chroma_Author_Tags();



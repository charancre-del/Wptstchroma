<?php
/**
 * Lightweight AMP for Blog Posts
 * Auto-generates AMP versions at /post-slug/amp/
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_AMP_Blog
{
    const ENDPOINT = 'amp';
    
    /**
     * Get theme colors - centralized source of truth
     * Matches tailwind.config.js colors
     */
    public static function get_theme_colors() {
        return apply_filters('chroma_amp_colors', [
            'brand_ink'     => get_theme_mod('chroma_brand_ink', '#263238'),
            'brand_cream'   => get_theme_mod('chroma_brand_cream', '#FFFCF8'),
            'brand_navy'    => get_theme_mod('chroma_brand_navy', '#4A6C7C'),
            'chroma_red'    => get_theme_mod('chroma_primary_color', '#A84B38'),
            'chroma_blue'   => get_theme_mod('chroma_accent_color', '#4A6C7C'),
            'chroma_green'  => '#4A7C59',
            'chroma_yellow' => '#C2A024',
        ]);
    }
    
    public function __construct() {
        add_action('init', [$this, 'add_rewrite_endpoint']);
        add_action('template_redirect', [$this, 'handle_amp_request']);
        add_action('wp_head', [$this, 'add_amphtml_link']);
        add_filter('the_content', [$this, 'clean_content_for_amp'], 999);
    }
    
    /**
     * Add /amp/ endpoint to posts
     */
    public function add_rewrite_endpoint() {
        add_rewrite_endpoint(self::ENDPOINT, EP_PERMALINK);
    }
    
    /**
     * Add amphtml link to regular blog posts
     */
    public function add_amphtml_link() {
        if (!is_singular('post')) {
            return;
        }
        
        $amp_url = trailingslashit(get_permalink()) . self::ENDPOINT . '/';
        echo '<link rel="amphtml" href="' . esc_url($amp_url) . '">' . "\n";
    }
    
    /**
     * Handle AMP request
     */
    public function handle_amp_request() {
        global $wp_query;
        
        // Check if AMP endpoint is requested
        if (!isset($wp_query->query_vars[self::ENDPOINT])) {
            return;
        }
        
        // Only for blog posts
        if (!is_singular('post')) {
            return;
        }
        
        // Render AMP version
        $this->render_amp_page();
        exit;
    }
    
    /**
     * Render AMP page
     */
    private function render_amp_page() {
        global $post;
        
        $title = get_the_title();
        $content = apply_filters('the_content', $post->post_content);
        $content = $this->convert_to_amp($content);
        $excerpt = get_the_excerpt();
        $date = get_the_date('c');
        $modified = get_the_modified_date('c');
        $author = get_the_author();
        $canonical = get_permalink();
        $site_name = get_bloginfo('name');
        $site_url = home_url('/');
        $logo_url = get_theme_mod('custom_logo') ? wp_get_attachment_url(get_theme_mod('custom_logo')) : '';
        
        // Featured image
        $image_url = '';
        $image_width = 1200;
        $image_height = 675;
        if (has_post_thumbnail()) {
            $image_id = get_post_thumbnail_id();
            $image_data = wp_get_attachment_image_src($image_id, 'large');
            if ($image_data) {
                $image_url = $image_data[0];
                $image_width = $image_data[1];
                $image_height = $image_data[2];
            }
        }
        
        // Categories
        $categories = get_the_category();
        $category_name = !empty($categories) ? $categories[0]->name : 'Blog';
        
        // Schema
        $schema = $this->get_article_schema($post, $author, $image_url);
        
        // Get dynamic colors
        $colors = self::get_theme_colors();
        
        ?>
<!doctype html>
<html amp lang="en">
<head>
    <meta charset="utf-8">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <title><?php echo esc_html($title); ?> | <?php echo esc_html($site_name); ?></title>
    <link rel="canonical" href="<?php echo esc_url($canonical); ?>">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <meta name="description" content="<?php echo esc_attr($excerpt); ?>">
    
    <script type="application/ld+json">
    <?php echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
    </script>
    
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style>
    <noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    
    <style amp-custom>
        :root {
            --brand-ink: <?php echo esc_attr($colors['brand_ink']); ?>;
            --brand-cream: <?php echo esc_attr($colors['brand_cream']); ?>;
            --chroma-red: <?php echo esc_attr($colors['chroma_red']); ?>;
            --chroma-blue: <?php echo esc_attr($colors['chroma_blue']); ?>;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #fff;
            color: var(--brand-ink);
            line-height: 1.7;
        }
        
        .amp-header {
            background: var(--brand-ink);
            padding: 15px 20px;
            text-align: center;
        }
        .amp-header a {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            font-size: 18px;
        }
        
        .amp-article {
            max-width: 700px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .amp-category {
            display: inline-block;
            background: var(--brand-cream);
            color: var(--chroma-blue);
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }
        
        .amp-title {
            font-size: 32px;
            line-height: 1.3;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .amp-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .amp-meta strong { color: var(--brand-ink); }
        
        .amp-featured-image {
            margin-bottom: 30px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .amp-content {
            font-size: 18px;
        }
        .amp-content p {
            margin-bottom: 1.5em;
        }
        .amp-content h2 {
            font-size: 24px;
            margin: 2em 0 0.8em;
        }
        .amp-content h3 {
            font-size: 20px;
            margin: 1.5em 0 0.6em;
        }
        .amp-content ul, .amp-content ol {
            margin: 1em 0 1.5em 1.5em;
        }
        .amp-content li {
            margin-bottom: 0.5em;
        }
        .amp-content a {
            color: var(--chroma-blue);
        }
        .amp-content blockquote {
            border-left: 4px solid var(--chroma-red);
            padding-left: 20px;
            margin: 1.5em 0;
            font-style: italic;
            color: #555;
        }
        
        .amp-cta {
            background: linear-gradient(135deg, var(--chroma-red) 0%, #c26a5a 100%);
            color: #fff;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin: 40px 0;
        }
        .amp-cta h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }
        .amp-cta p {
            opacity: 0.9;
            margin-bottom: 20px;
        }
        .amp-cta a {
            display: inline-block;
            background: #fff;
            color: var(--chroma-red);
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 700;
            text-decoration: none;
        }
        
        .amp-footer {
            background: var(--brand-ink);
            color: #fff;
            padding: 30px 20px;
            text-align: center;
            font-size: 14px;
        }
        .amp-footer a {
            color: #fff;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header class="amp-header">
        <a href="<?php echo esc_url($site_url); ?>"><?php echo esc_html($site_name); ?></a>
    </header>
    
    <article class="amp-article">
        <span class="amp-category"><?php echo esc_html($category_name); ?></span>
        <h1 class="amp-title"><?php echo esc_html($title); ?></h1>
        
        <div class="amp-meta">
            By <strong><?php echo esc_html($author); ?></strong> · 
            <?php echo get_the_date('F j, Y'); ?> · 
            <?php echo $this->reading_time($post->post_content); ?> min read
        </div>
        
        <?php if ($image_url): ?>
        <div class="amp-featured-image">
            <amp-img src="<?php echo esc_url($image_url); ?>" 
                     width="<?php echo esc_attr($image_width); ?>" 
                     height="<?php echo esc_attr($image_height); ?>" 
                     layout="responsive"
                     alt="<?php echo esc_attr($title); ?>">
            </amp-img>
        </div>
        <?php endif; ?>
        
        <div class="amp-content">
            <?php echo $content; ?>
        </div>
        
        <div class="amp-cta">
            <h3>Schedule a Tour Today</h3>
            <p>See our programs in action and meet our teachers.</p>
            <a href="<?php echo esc_url(home_url('/schedule-a-tour/')); ?>">Book Your Visit</a>
        </div>
    </article>
    
    <footer class="amp-footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($site_name); ?>. All rights reserved.</p>
        <p><a href="<?php echo esc_url($canonical); ?>">View full version</a></p>
    </footer>
</body>
</html>
        <?php
    }
    
    /**
     * Convert content to AMP-compatible HTML
     */
    /**
     * Convert content to AMP-compatible HTML
     */
    private function convert_to_amp($content) {
        // 1. Strict Sanitization: Remove all disallowed tags and attributes (especially inline styles)
        $allowed_html = [
            'div' => ['class' => [], 'id' => [], 'align' => []],
            'span' => ['class' => [], 'id' => []],
            'p' => ['class' => [], 'id' => [], 'align' => []],
            'a' => ['href' => [], 'title' => [], 'target' => [], 'rel' => [], 'class' => [], 'id' => []],
            'img' => ['src' => [], 'alt' => [], 'width' => [], 'height' => [], 'class' => [], 'id' => [], 'title' => []],
            'h1' => ['class' => [], 'id' => []],
            'h2' => ['class' => [], 'id' => []],
            'h3' => ['class' => [], 'id' => []],
            'h4' => ['class' => [], 'id' => []],
            'h5' => ['class' => [], 'id' => []],
            'h6' => ['class' => [], 'id' => []],
            'ul' => ['class' => [], 'id' => []],
            'ol' => ['class' => [], 'id' => [], 'start' => [], 'type' => []],
            'li' => ['class' => [], 'id' => []],
            'blockquote' => ['class' => [], 'id' => [], 'cite' => []],
            'b' => [], 'strong' => [], 'i' => [], 'em' => [], 'u' => [], 's' => [], 'del' => [], 
            'br' => [], 'hr' => ['class' => []],
            'table' => ['class' => [], 'id' => [], 'border' => [], 'cellspacing' => [], 'cellpadding' => []],
            'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [], 'th' => ['colspan' => [], 'rowspan' => []], 'td' => ['colspan' => [], 'rowspan' => []],
        ];

        // This effectively strips <style>, <script>, <link>, <form> and inline style attributes
        $content = wp_kses($content, $allowed_html);

        // 2. Convert <img> to <amp-img>
        // Note: regex needs to be robust for attributes in any order
        $content = preg_replace_callback(
            '/<img([^>]+)>/i',
            function($matches) {
                $attrs = $matches[1];
                
                // Extract src
                preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $src);
                $src_val = $src[1] ?? '';
                if (!$src_val) return ''; // Skip images without src

                // Extract width/height
                preg_match('/width=["\']?(\d+)["\']?/i', $attrs, $width);
                preg_match('/height=["\']?(\d+)["\']?/i', $attrs, $height);
                $w_val = $width[1] ?? '800'; // Default width if missing
                $h_val = $height[1] ?? '600'; // Default height if missing
                
                // Extract alt
                preg_match('/alt=["\']([^"\']*)["\']?/i', $attrs, $alt);
                $alt_val = $alt[1] ?? '';
                
                // Convert to amp-img
                return sprintf(
                    '<amp-img src="%s" width="%s" height="%s" layout="responsive" alt="%s"></amp-img>',
                    esc_url($src_val),
                    esc_attr($w_val),
                    esc_attr($h_val),
                    esc_attr($alt_val)
                );
            },
            $content
        );

        return $content;
    }
    
    /**
     * Clean content for AMP (filter version)
     */
    public function clean_content_for_amp($content) {
        global $wp_query;
        
        // Only apply on AMP pages
        if (!isset($wp_query->query_vars[self::ENDPOINT])) {
            return $content;
        }
        
        return $this->convert_to_amp($content);
    }
    
    /**
     * Calculate reading time
     */
    private function reading_time($content) {
        $word_count = str_word_count(strip_tags($content));
        $minutes = ceil($word_count / 200);
        return max(1, $minutes);
    }
    
    /**
     * Get Article schema for AMP
     */
    private function get_article_schema($post, $author, $image_url) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => get_the_title($post),
            'description' => get_the_excerpt($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post),
            'author' => [
                '@type' => 'Person',
                'name' => $author
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => get_theme_mod('custom_logo') ? wp_get_attachment_url(get_theme_mod('custom_logo')) : ''
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => get_permalink($post)
            ]
        ];
        
        if ($image_url) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $image_url
            ];
        }
        
        return $schema;
    }
    
    /**
     * Get AMP URL for a post
     */
    public static function get_amp_url($post_id = null) {
        $post_id = $post_id ?: get_the_ID();
        return trailingslashit(get_permalink($post_id)) . self::ENDPOINT . '/';
    }
}

new Chroma_AMP_Blog();

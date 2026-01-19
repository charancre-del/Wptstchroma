<?php
/**
 * Accessibility SEO
 * ARIA labels, skip nav, focus indicators
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Accessibility_SEO
{
    public function __construct() {
        add_action('wp_body_open', [$this, 'add_skip_nav']);
        add_action('wp_head', [$this, 'add_accessibility_styles']);
        add_filter('the_content', [$this, 'enhance_content_accessibility']);
        add_filter('post_thumbnail_html', [$this, 'ensure_alt_text'], 10, 5);
        add_action('admin_notices', [$this, 'alt_text_warnings']);
    }
    
    /**
     * Add skip navigation link
     */
    public function add_skip_nav() {
        if (!get_option('chroma_seo_enable_skip_nav', true)) {
            return;
        }
        
        echo '<a class="skip-to-content" href="#main-content">Skip to main content</a>';
    }
    
    /**
     * Add accessibility styles
     */
    public function add_accessibility_styles() {
        ?>
        <style>
            /* Skip navigation */
            .skip-to-content {
                position: absolute;
                left: -9999px;
                top: auto;
                width: 1px;
                height: 1px;
                overflow: hidden;
                z-index: 9999;
            }
            .skip-to-content:focus {
                position: fixed;
                top: 10px;
                left: 10px;
                width: auto;
                height: auto;
                padding: 15px 25px;
                background: #0066cc;
                color: #fff;
                font-size: 16px;
                font-weight: 600;
                text-decoration: none;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            }
            
            /* Focus indicators */
            <?php if (get_option('chroma_seo_enable_focus_indicators', true)): ?>
            a:focus,
            button:focus,
            input:focus,
            select:focus,
            textarea:focus,
            [tabindex]:focus {
                outline: 3px solid #0066cc !important;
                outline-offset: 2px !important;
            }
            a:focus:not(:focus-visible),
            button:focus:not(:focus-visible) {
                outline: none !important;
            }
            a:focus-visible,
            button:focus-visible,
            input:focus-visible,
            [tabindex]:focus-visible {
                outline: 3px solid #0066cc !important;
                outline-offset: 2px !important;
            }
            <?php endif; ?>
            
            /* Screen reader only text */
            .sr-only {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }
        </style>
        <?php
    }
    
    /**
     * Enhance content accessibility
     */
    public function enhance_content_accessibility($content) {
        // Add aria-label to external links
        $content = preg_replace_callback(
            '/<a([^>]+)href=["\'](https?:\/\/(?!' . preg_quote(parse_url(home_url(), PHP_URL_HOST), '/') . ')[^"\']+)["\']([^>]*)>/i',
            function($matches) {
                // Check if already has aria-label
                if (stripos($matches[0], 'aria-label') !== false) {
                    return $matches[0];
                }
                return '<a' . $matches[1] . 'href="' . $matches[2] . '"' . $matches[3] . ' aria-label="Opens in new window" rel="noopener">';
            },
            $content
        );
        
        // Ensure images have alt text
        $content = preg_replace_callback(
            '/<img([^>]*)>/i',
            function($matches) {
                $img = $matches[0];
                
                // Check for alt attribute
                if (!preg_match('/\salt=/i', $img)) {
                    // Try to extract from src filename
                    preg_match('/src=["\'](.*?)["\']/i', $img, $src);
                    $filename = $src[1] ?? '';
                    $alt = ucwords(str_replace(['-', '_'], ' ', pathinfo($filename, PATHINFO_FILENAME)));
                    $alt = preg_replace('/\d+x\d+$/', '', $alt); // Remove dimensions
                    
                    $img = str_replace('<img', '<img alt="' . esc_attr(trim($alt)) . '"', $img);
                }
                
                return $img;
            },
            $content
        );
        
        return $content;
    }
    
    /**
     * Ensure alt text on thumbnails
     */
    public function ensure_alt_text($html, $post_id, $thumb_id, $size, $attr) {
        if (empty($html)) {
            return $html;
        }
        
        // Check if alt is empty
        if (preg_match('/alt=["\']\s*["\']/i', $html)) {
            // Get image alt from attachment
            $alt = get_post_meta($thumb_id, '_wp_attachment_image_alt', true);
            
            if (empty($alt)) {
                // Use post title as fallback
                $alt = get_the_title($post_id);
            }
            
            $html = preg_replace('/alt=["\']\s*["\']/i', 'alt="' . esc_attr($alt) . '"', $html);
        }
        
        return $html;
    }
    
    /**
     * Show admin warnings for missing alt text
     */
    public function alt_text_warnings() {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        $screen = get_current_screen();
        
        if ($screen->id !== 'dashboard') {
            return;
        }
        
        // Check for images without alt text
        $images_without_alt = get_posts([
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => 10,
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => '_wp_attachment_image_alt',
                    'value' => '',
                    'compare' => '='
                ]
            ]
        ]);
        
        if (!empty($images_without_alt)) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>SEO Alert:</strong> ' . count($images_without_alt) . ' images are missing alt text. ';
            echo '<a href="' . admin_url('upload.php?mode=list') . '">Add alt text →</a></p>';
            echo '</div>';
        }
    }
    
    /**
     * Check heading hierarchy
     */
    public static function check_heading_hierarchy($content) {
        $issues = [];
        
        preg_match_all('/<h([1-6])[^>]*>/i', $content, $matches);
        
        if (empty($matches[1])) {
            return $issues;
        }
        
        $levels = array_map('intval', $matches[1]);
        
        // Check for missing H1
        if (!in_array(1, $levels)) {
            $issues[] = 'No H1 tag found on page';
        }
        
        // Check for multiple H1s
        $h1_count = count(array_filter($levels, fn($l) => $l === 1));
        if ($h1_count > 1) {
            $issues[] = 'Multiple H1 tags found (' . $h1_count . ')';
        }
        
        // Check for skipped levels
        $prev = 1;
        foreach ($levels as $level) {
            if ($level > $prev + 1) {
                $issues[] = 'Heading level skipped from H' . $prev . ' to H' . $level;
                break;
            }
            $prev = $level;
        }
        
        return $issues;
    }
}

new Chroma_Accessibility_SEO();



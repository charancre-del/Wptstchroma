<?php
/**
 * Image Alt Text Automation
 * Automatically generates context-aware alt text for images if missing
 * Uses Location Name + Program Context
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Image_Alt_Automation
{
    /**
     * Initialize the module
     */
    public function init()
    {
        add_filter('wp_get_attachment_image_attributes', [$this, 'inject_auto_alt_text'], 10, 3);
    }

    /**
     * Inject alt text if missing
     *
     * @param array $attr Attributes for the image markup.
     * @param WP_Post $attachment Image attachment post.
     * @param string|array $size Requested size.
     * @return array Filtered attributes.
     */
    public function inject_auto_alt_text($attr, $attachment, $size)
    {
        // If alt text exists, do nothing
        if (!empty($attr['alt'])) {
            return $attr;
        }

        // Get current context
        $post_id = get_the_ID();
        if (!$post_id) {
            return $attr;
        }

        $post_type = get_post_type($post_id);
        $title = get_the_title($post_id);

        // Generate smart alt text based on context
        $auto_alt = '';

        if ($post_type === 'location') {
            $city = get_post_meta($post_id, 'location_city', true);
            $auto_alt = sprintf(
                __('Childcare facility at %s in %s - Quality Rated learning environment', 'chroma-excellence'),
                $title,
                $city ? $city : 'Georgia'
            );
        } elseif ($post_type === 'program') {
            $auto_alt = sprintf(
                __('%s program for early childhood education - Hands-on learning activities', 'chroma-excellence'),
                $title
            );
        } else {
            // Generic fallback using site name
            $auto_alt = sprintf(
                __('%s - Early Childhood Education Center', 'chroma-excellence'),
                get_bloginfo('name')
            );
        }

        // Set the attribute
        if ($auto_alt) {
            $attr['alt'] = $auto_alt;
        }

        return $attr;
    }
}

<?php
/**
 * Post Newsroom Meta Box
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Post_Newsroom extends Chroma_Advanced_SEO_Meta_Box_Base
{
    /**
     * Get meta box ID
     *
     * @return string
     */
    public function get_id()
    {
        return 'chroma_post_newsroom';
    }

    /**
     * Get meta box title
     *
     * @return string
     */
    public function get_title()
    {
        return 'Newsroom Settings';
    }

    /**
     * Get allowed post types
     *
     * @return array
     */
    public function get_post_types()
    {
        return ['post'];
    }

    /**
     * Render the meta box fields
     *
     * @param WP_Post $post The post object.
     */
    public function render_fields($post)
    {
        $show_in_newsroom = get_post_meta($post->ID, '_chroma_show_in_newsroom', true);
        ?>
        <div class="chroma-field-wrapper">
            <label for="chroma_show_in_newsroom">
                <input type="checkbox" id="chroma_show_in_newsroom" name="chroma_show_in_newsroom" value="1" <?php checked($show_in_newsroom, '1'); ?>>
                Show in Newsroom
            </label>
            <p class="description">Check this box to display this post on the Newsroom page.</p>
        </div>
        <?php
    }

    /**
     * Save the meta box fields
     *
     * @param int $post_id The post ID.
     */
    public function save_fields($post_id)
    {
        if (isset($_POST['chroma_show_in_newsroom'])) {
            update_post_meta($post_id, '_chroma_show_in_newsroom', '1');
        } else {
            delete_post_meta($post_id, '_chroma_show_in_newsroom');
        }
    }
}



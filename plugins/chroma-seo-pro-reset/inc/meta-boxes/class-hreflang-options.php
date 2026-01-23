<?php
/**
 * Hreflang Options Meta Box
 * Allows linking English and Spanish versions of a page
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Hreflang_Options extends Chroma_Advanced_SEO_Meta_Box_Base
{
    /**
     * Get the meta box ID
     *
     * @return string
     */
    public function get_id()
    {
        return 'chroma_hreflang_options';
    }

    /**
     * Get the meta box title
     *
     * @return string
     */
    public function get_title()
    {
        return __('International SEO (Hreflang)', 'chroma-excellence');
    }

    /**
     * Get post types this meta box applies to
     *
     * @return array
     */
    public function get_post_types()
    {
        return ['page', 'location', 'program', 'post'];
    }

    /**
     * Render the meta box fields
     *
     * @param WP_Post $post Current post object
     */
    public function render_fields($post)
    {
        $alternate_en = get_post_meta($post->ID, 'alternate_url_en', true);
        $alternate_es = get_post_meta($post->ID, 'alternate_url_es', true);
        ?>
        <div class="chroma-field-wrapper">
            <p class="description">
                <?php _e('Link this page to its translated counterpart. This tells Google that these are the same content in different languages.', 'chroma-excellence'); ?>
            </p>

            <div style="margin-bottom: 15px;">
                <label for="alternate_url_en"><?php _e('English Version URL:', 'chroma-excellence'); ?></label>
                <input type="url" id="alternate_url_en" name="alternate_url_en" value="<?php echo esc_url($alternate_en); ?>"
                    class="widefat" placeholder="https://..." />
                <p class="description">
                    <?php _e('If this IS the English page, leave blank or enter self-ref.', 'chroma-excellence'); ?></p>
            </div>

            <div style="margin-bottom: 15px;">
                <label for="alternate_url_es"><?php _e('Spanish Version URL:', 'chroma-excellence'); ?></label>
                <input type="url" id="alternate_url_es" name="alternate_url_es" value="<?php echo esc_url($alternate_es); ?>"
                    class="widefat" placeholder="https://..." />
                <p class="description">
                    <?php _e('Enter the full URL of the Spanish version of this page.', 'chroma-excellence'); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Save the meta box fields
     *
     * @param int $post_id Post ID
     */
    public function save_fields($post_id)
    {
        if (isset($_POST['alternate_url_en'])) {
            update_post_meta($post_id, 'alternate_url_en', esc_url_raw($_POST['alternate_url_en']));
        }
        if (isset($_POST['alternate_url_es'])) {
            update_post_meta($post_id, 'alternate_url_es', esc_url_raw($_POST['alternate_url_es']));
        }
    }
}



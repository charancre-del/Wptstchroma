<?php
/**
 * HowTo Schema Meta Box
 * Handles "How to Enroll" steps for rich snippets
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Location_HowTo_Meta_Box extends Chroma_Advanced_SEO_Meta_Box_Base
{
    /**
     * Get meta box ID
     *
     * @return string
     */
    public function get_id()
    {
        return 'chroma_location_howto';
    }

    /**
     * Get meta box title
     *
     * @return string
     */
    public function get_title()
    {
        return __('How to Enroll (HowTo Schema)', 'chroma-excellence');
    }

    /**
     * Get allowed post types
     *
     * @return array
     */
    public function get_post_types()
    {
        return ['location'];
    }

    /**
     * Render the meta box fields
     *
     * @param WP_Post $post Current post object
     */
    public function render_fields($post)
    {
        $steps = get_post_meta($post->ID, 'location_enrollment_steps', true);
        if (!is_array($steps)) {
            $steps = [];
        }

        ?>
        <div class="chroma-meta-section">
            <p class="description">
                <?php _e('Define the enrollment process steps. Google uses this to generate "HowTo" rich snippets.', 'chroma-excellence'); ?>
            </p>

            <div class="chroma-repeater-field" data-field-id="location_enrollment_steps">
                <div class="chroma-repeater-items">
                    <?php if (!empty($steps)): ?>
                        <?php foreach ($steps as $step): ?>
                            <div class="chroma-repeater-item" style="align-items: flex-start;">
                                <div style="flex: 1; display: flex; gap: 10px; flex-direction: column;">
                                    <input type="text" name="location_enrollment_steps[title][]"
                                        value="<?php echo esc_attr($step['title']); ?>" class="widefat"
                                        placeholder="Step Title (e.g., Schedule a Tour)" />
                                    <textarea name="location_enrollment_steps[text][]" class="widefat" rows="2"
                                        placeholder="Step Description"><?php echo esc_textarea($step['text']); ?></textarea>
                                    <input type="url" name="location_enrollment_steps[url][]"
                                        value="<?php echo esc_url($step['url']); ?>" class="widefat" placeholder="URL (Optional)" />
                                </div>
                                <button type="button" class="button chroma-remove-item">Remove</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button"
                    class="button chroma-add-item-howto"><?php _e('Add Step', 'chroma-excellence'); ?></button>
            </div>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                // Custom handler for HowTo repeater because it has multiple inputs per item
                $(document).on('click', '.chroma-add-item-howto', function (e) {
                    e.preventDefault();
                    var $wrapper = $(this).closest('.chroma-repeater-field');
                    var $items = $wrapper.find('.chroma-repeater-items');

                    var $newItem = $('<div class="chroma-repeater-item" style="align-items: flex-start;">' +
                        '<div style="flex: 1; display: flex; gap: 10px; flex-direction: column;">' +
                        '<input type="text" name="location_enrollment_steps[title][]" class="widefat" placeholder="Step Title" />' +
                        '<textarea name="location_enrollment_steps[text][]" class="widefat" rows="2" placeholder="Step Description"></textarea>' +
                        '<input type="url" name="location_enrollment_steps[url][]" class="widefat" placeholder="URL (Optional)" />' +
                        '</div>' +
                        '<button type="button" class="button chroma-remove-item">Remove</button>' +
                        '</div>');

                    $items.append($newItem);
                });
            });
        </script>
        <?php
    }

    /**
     * Save the meta box fields
     *
     * @param int $post_id Post ID
     */
    public function save_fields($post_id)
    {
        if (isset($_POST['location_enrollment_steps'])) {
            $titles = $_POST['location_enrollment_steps']['title'] ?? [];
            $texts = $_POST['location_enrollment_steps']['text'] ?? [];
            $urls = $_POST['location_enrollment_steps']['url'] ?? [];

            $steps = [];
            for ($i = 0; $i < count($titles); $i++) {
                if (!empty($titles[$i])) {
                    $steps[] = [
                        'title' => sanitize_text_field($titles[$i]),
                        'text' => sanitize_textarea_field($texts[$i]),
                        'url' => esc_url_raw($urls[$i]),
                    ];
                }
            }

            update_post_meta($post_id, 'location_enrollment_steps', $steps);
        }
    }
}



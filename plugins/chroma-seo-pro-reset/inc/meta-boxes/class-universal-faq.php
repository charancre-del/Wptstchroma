<?php
/**
 * Universal FAQ Meta Box
 * Allows adding FAQ items to any page for FAQPage schema
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Universal_FAQ extends Chroma_Advanced_SEO_Meta_Box_Base
{
    /**
     * Get the meta box ID
     *
     * @return string
     */
    public function get_id()
    {
        return 'chroma_universal_faq';
    }

    /**
     * Get the meta box title
     *
     * @return string
     */
    public function get_title()
    {
        return __('FAQ Schema (LLM Optimized)', 'chroma-excellence');
    }

    /**
     * Get post types this meta box applies to
     *
     * @return array
     */
    public function get_post_types()
    {
        return ['page', 'location', 'program', 'post', 'city'];
    }

    /**
     * Render the meta box fields
     *
     * @param WP_Post $post Current post object
     */
    public function render_fields($post)
    {
        $faqs = get_post_meta($post->ID, 'chroma_faq_items', true);
        if (!is_array($faqs)) {
            $faqs = [];
        }
        ?>
        <div class="chroma-field-wrapper">
            <p class="description">
                <?php _e('Add Questions and Answers here. They will be output as <strong>FAQPage Schema</strong>, which is highly visible to Google and LLMs.', 'chroma-excellence'); ?>
            </p>

            <div class="chroma-repeater-field">
                <div class="chroma-repeater-items">
                    <?php if (!empty($faqs)): ?>
                        <?php foreach ($faqs as $faq): ?>
                            <div class="chroma-repeater-item"
                                style="display:block; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:10px;">
                                <div style="margin-bottom:5px;">
                                    <label><?php _e('Question:', 'chroma-excellence'); ?></label>
                                    <input type="text" name="chroma_faq_question[]" value="<?php echo esc_attr($faq['question']); ?>"
                                        class="widefat" />
                                </div>
                                <div style="display:flex; gap:10px; align-items:flex-start;">
                                    <div style="flex:1;">
                                        <label><?php _e('Answer:', 'chroma-excellence'); ?></label>
                                        <textarea name="chroma_faq_answer[]" rows="2"
                                            class="widefat"><?php echo esc_textarea($faq['answer']); ?></textarea>
                                    </div>
                                    <button class="button chroma-remove-item" style="margin-top:20px;">&times;</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="chroma-repeater-item"
                            style="display:block; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:10px;">
                            <div style="margin-bottom:5px;">
                                <label><?php _e('Question:', 'chroma-excellence'); ?></label>
                                <input type="text" name="chroma_faq_question[]" class="widefat" />
                            </div>
                            <div style="display:flex; gap:10px; align-items:flex-start;">
                                <div style="flex:1;">
                                    <label><?php _e('Answer:', 'chroma-excellence'); ?></label>
                                    <textarea name="chroma_faq_answer[]" rows="2" class="widefat"></textarea>
                                </div>
                                <button class="button chroma-remove-item" style="margin-top:20px;">&times;</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="button chroma-add-item"><?php _e('Add Question', 'chroma-excellence'); ?></button>
            </div>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                // Enhanced repeater for block-style items
                $('.chroma-add-item').off('click').on('click', function (e) {
                    e.preventDefault();
                    var $wrapper = $(this).closest('.chroma-repeater-field');
                    var $items = $wrapper.find('.chroma-repeater-items');
                    var $clone = $items.find('.chroma-repeater-item').first().clone();
                    $clone.find('input, textarea').val('');
                    $items.append($clone);
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
        if (isset($_POST['chroma_faq_question']) && isset($_POST['chroma_faq_answer'])) {
            $questions = $_POST['chroma_faq_question'];
            $answers = $_POST['chroma_faq_answer'];
            $faqs = [];

            for ($i = 0; $i < count($questions); $i++) {
                if (!empty($questions[$i]) && !empty($answers[$i])) {
                    $faqs[] = [
                        'question' => sanitize_text_field($questions[$i]),
                        'answer' => sanitize_textarea_field($answers[$i]),
                    ];
                }
            }

            update_post_meta($post_id, 'chroma_faq_items', $faqs);
        } else {
            delete_post_meta($post_id, 'chroma_faq_items');
        }
    }
}



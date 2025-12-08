<?php
/**
 * General SEO Meta Boxes
 * Adds "SEO Meta" box to all public post types
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register SEO Meta Box
 */
function chroma_register_general_seo_meta_box()
{
    $screens = array('post', 'page', 'program', 'location');

    foreach ($screens as $screen) {
        add_meta_box(
            'chroma-general-seo',
            __('SEO Meta', 'chroma-excellence'),
            'chroma_render_general_seo_meta_box',
            $screen,
            'side',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'chroma_register_general_seo_meta_box');

/**
 * Render SEO Meta Box
 */
function chroma_render_general_seo_meta_box($post)
{
    wp_nonce_field('chroma_general_seo_nonce', 'chroma_general_seo_nonce_field');

    $meta_description = get_post_meta($post->ID, 'meta_description', true);
    $meta_keywords = get_post_meta($post->ID, 'meta_keywords', true);
    ?>
    <div class="chroma-seo-field" style="margin-bottom: 15px;">
        <label for="meta_description" style="display: block; font-weight: 600; margin-bottom: 5px;">
            <?php _e('Meta Description', 'chroma-excellence'); ?>
        </label>
        <textarea id="meta_description" name="meta_description" rows="4" style="width: 100%;"
            placeholder="<?php _e('Enter custom meta description...', 'chroma-excellence'); ?>"><?php echo esc_textarea($meta_description); ?></textarea>
        <p class="description" style="font-size: 12px; margin-top: 5px;">
            <?php _e('Leave empty to use the auto-generated description.', 'chroma-excellence'); ?>
        </p>
    </div>

    <div class="chroma-seo-field">
        <label for="meta_keywords" style="display: block; font-weight: 600; margin-bottom: 5px;">
            <?php _e('Meta Keywords', 'chroma-excellence'); ?>
        </label>
        <textarea id="meta_keywords" name="meta_keywords" rows="2" style="width: 100%;"
            placeholder="<?php _e('keyword1, keyword2, keyword3', 'chroma-excellence'); ?>"><?php echo esc_textarea($meta_keywords); ?></textarea>
        <p class="description" style="font-size: 12px; margin-top: 5px;">
            <?php _e('Comma-separated list. Leave empty to use auto-generated keywords.', 'chroma-excellence'); ?>
        </p>
    </div>
    </div>

    <!-- AI Auto-Fill -->
    <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
        <button type="button" id="chroma-seo-autofill" class="button">
            <span class="dashicons dashicons-superhero"
                style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle; margin-right: 3px;"></span>
            <?php _e('Auto-Fill with AI', 'chroma-excellence'); ?>
        </button>
        <span class="spinner" id="chroma-seo-spinner" style="float: none; margin: 0 5px;"></span>
        <p class="description" style="margin-top: 5px;">
            <?php _e('Generates a description and keywords based on page content.', 'chroma-excellence'); ?>
        </p>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            $('#chroma-seo-autofill').on('click', function (e) {
                e.preventDefault();
                var btn = $(this);
                var post_id = $('#post_ID').val();

                if (!confirm('<?php _e('This will overwrite existing SEO fields with AI-generated content. Continue?', 'chroma-excellence'); ?>')) {
                    return;
                }

                btn.prop('disabled', true);
                $('#chroma-seo-spinner').addClass('is-active');

                $.post(ajaxurl, {
                    action: 'chroma_generate_general_seo_meta',
                    nonce: '<?php echo wp_create_nonce('chroma_seo_dashboard_nonce'); ?>', // Reusing dashboard nonce for simplicity
                    post_id: post_id
                }, function (response) {
                    btn.prop('disabled', false);
                    $('#chroma-seo-spinner').removeClass('is-active');

                    if (response.success) {
                        $('#meta_description').val(response.data.description).css('background-color', '#f0f6fc').animate({ backgroundColor: '#fff' }, 2000);
                        $('#meta_keywords').val(response.data.keywords).css('background-color', '#f0f6fc').animate({ backgroundColor: '#fff' }, 2000);
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error'));
                    }
                }).fail(function () {
                    btn.prop('disabled', false);
                    $('#chroma-seo-spinner').removeClass('is-active');
                    alert('Network error.');
                });
            });
        });
    </script>
    <?php
}

/**
 * Save SEO Meta Box
 */
function chroma_save_general_seo_meta($post_id)
{
    // Verify nonce
    if (!isset($_POST['chroma_general_seo_nonce_field']) || !wp_verify_nonce(wp_unslash($_POST['chroma_general_seo_nonce_field']), 'chroma_general_seo_nonce')) {
        return;
    }

    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check permissions
    if (isset($_POST['post_type'])) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    // Save fields
    if (isset($_POST['meta_description'])) {
        update_post_meta($post_id, 'meta_description', sanitize_textarea_field($_POST['meta_description']));
    }

    if (isset($_POST['meta_keywords'])) {
        update_post_meta($post_id, 'meta_keywords', sanitize_textarea_field($_POST['meta_keywords']));
    }
}
add_action('save_post', 'chroma_save_general_seo_meta');

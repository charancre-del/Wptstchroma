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
        document.addEventListener('DOMContentLoaded', function () {
            var button = document.getElementById('chroma-seo-autofill');
            var spinner = document.getElementById('chroma-seo-spinner');
            var descriptionField = document.getElementById('meta_description');
            var keywordsField = document.getElementById('meta_keywords');
            var postIdField = document.getElementById('post_ID');

            if (!button || !spinner || !descriptionField || !keywordsField || !postIdField) {
                return;
            }

            var flashField = function (field) {
                field.style.backgroundColor = '#f0f6fc';
                window.setTimeout(function () {
                    field.style.backgroundColor = '#fff';
                }, 1200);
            };

            button.addEventListener('click', function (event) {
                event.preventDefault();

                if (typeof ajaxurl === 'undefined') {
                    window.alert('Admin AJAX URL is unavailable.');
                    return;
                }

                if (!window.confirm('<?php echo esc_js(__('This will overwrite existing SEO fields with AI-generated content. Continue?', 'chroma-excellence')); ?>')) {
                    return;
                }

                button.disabled = true;
                spinner.classList.add('is-active');

                var body = new URLSearchParams();
                body.append('action', 'chroma_generate_general_seo_meta');
                body.append('nonce', '<?php echo esc_js(wp_create_nonce('chroma_seo_dashboard_nonce')); ?>');
                body.append('post_id', postIdField.value);

                window.fetch(ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: body.toString()
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (response) {
                        button.disabled = false;
                        spinner.classList.remove('is-active');

                        if (response && response.success) {
                            descriptionField.value = response.data.description || '';
                            keywordsField.value = response.data.keywords || '';
                            flashField(descriptionField);
                            flashField(keywordsField);
                            return;
                        }

                        var message = 'Unknown error';
                        if (response && response.data && response.data.message) {
                            message = response.data.message;
                        }
                        window.alert('Error: ' + message);
                    })
                    .catch(function () {
                        button.disabled = false;
                        spinner.classList.remove('is-active');
                        window.alert('Network error.');
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

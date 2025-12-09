<?php
/**
 * Plugin Name: Chroma Tour Form
 * Description: Tour request form with lead logging for Chroma Early Learning Academy. Fully editable fields via Settings.
 * Version: 1.2.0
 * Author: Chroma Development Team
 * Text Domain: chroma-tour-form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Default Fields Configuration
 */
function chroma_tour_default_fields()
{
    return array(
        array(
            'id' => 'parent_name',
            'label' => 'Parent Name',
            'type' => 'text',
            'required' => true,
            'width' => 'half',
            'placeholder' => ''
        ),
        array(
            'id' => 'phone',
            'label' => 'Phone',
            'type' => 'tel',
            'required' => true,
            'width' => 'half',
            'placeholder' => ''
        ),
        array(
            'id' => 'email',
            'label' => 'Email',
            'type' => 'email',
            'required' => true,
            'width' => 'half',
            'placeholder' => ''
        ),
        array(
            'id' => 'location_id',
            'label' => 'Preferred Location',
            'type' => 'select_location',
            'required' => false,
            'width' => 'half',
            'placeholder' => 'Select a location...'
        ),
        array(
            'id' => 'child_ages',
            'label' => 'Child(ren) Age(s)',
            'type' => 'text',
            'required' => false,
            'width' => 'full',
            'placeholder' => 'e.g., 10 months, 3 years'
        )
    );
}

/**
 * Admin Menu & Settings
 */
function chroma_tour_register_settings()
{
    register_setting('chroma_tour_options', 'chroma_tour_fields', array(
        'type' => 'string',
        'sanitize_callback' => 'chroma_tour_sanitize_json',
        'default' => wp_json_encode(chroma_tour_default_fields())
    ));
    
    register_setting('chroma_tour_options', 'chroma_tour_webhook_url', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => ''
    ));

    register_setting('chroma_tour_options', 'chroma_tour_email_recipient', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default' => get_option('admin_email')
    ));
}
add_action('admin_init', 'chroma_tour_register_settings');

function chroma_tour_sanitize_json($input)
{
    $decoded = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        add_settings_error('chroma_tour_fields', 'invalid_json', 'Invalid JSON format. Changes not saved.');
        return get_option('chroma_tour_fields');
    }
    return $input;
}

function chroma_tour_admin_menu()
{
    add_options_page(
        'Tour Form Settings',
        'Tour Form',
        'manage_options',
        'chroma-tour-form',
        'chroma_tour_settings_page_html'
    );
}
add_action('admin_menu', 'chroma_tour_admin_menu');

function chroma_tour_settings_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post" id="chroma-tour-settings-form">
            <?php
            settings_fields('chroma_tour_options');
            do_settings_sections('chroma_tour_options');
            
            $fields_json = get_option('chroma_tour_fields', wp_json_encode(chroma_tour_default_fields()));
            $webhook_url = get_option('chroma_tour_webhook_url', '');
            $email_recipient = get_option('chroma_tour_email_recipient', get_option('admin_email'));
            ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Email Recipient</th>
                    <td>
                        <input type="email" name="chroma_tour_email_recipient" value="<?php echo esc_attr($email_recipient); ?>" class="regular-text" />
                        <p class="description">The email address where notifications will be sent. Defaults to admin email.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Webhook URL</th>
                    <td>
                        <input type="url" name="chroma_tour_webhook_url" value="<?php echo esc_attr($webhook_url); ?>" class="regular-text" placeholder="https://hooks.zapier.com/..." />
                        <p class="description">Optional. Send form submissions to this URL via POST request.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Form Fields</th>
                    <td>
                        <div id="chroma-fields-editor"></div>
                        <input type="hidden" name="chroma_tour_fields" id="chroma_tour_fields_input" value="<?php echo esc_attr($fields_json); ?>">
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Settings'); ?>
        </form>
    </div>

    <style>
        .chroma-field-row {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            cursor: move;
        }
        .chroma-field-row.ui-sortable-helper {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .chroma-field-col {
            flex: 1;
        }
        .chroma-field-actions {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .chroma-input-group {
            margin-bottom: 10px;
        }
        .chroma-input-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 12px;
        }
        .chroma-input-group input, .chroma-input-group select {
            width: 100%;
        }
        .chroma-btn-remove {
            color: #d63638;
            border-color: #d63638;
        }
        .chroma-btn-remove:hover {
            background: #d63638;
            color: #fff;
            border-color: #d63638;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('chroma-fields-editor');
            const input = document.getElementById('chroma_tour_fields_input');
            let fields = JSON.parse(input.value || '[]');

            function render() {
                container.innerHTML = '';
                
                fields.forEach((field, index) => {
                    const row = document.createElement('div');
                    row.className = 'chroma-field-row';
                    row.innerHTML = `
                        <div class="chroma-field-col">
                            <div class="chroma-input-group">
                                <label>Label</label>
                                <input type="text" value="${escapeHtml(field.label)}" onchange="updateField(${index}, 'label', this.value)">
                            </div>
                            <div class="chroma-input-group">
                                <label>Field ID (Unique)</label>
                                <input type="text" value="${escapeHtml(field.id)}" onchange="updateField(${index}, 'id', this.value)">
                            </div>
                        </div>
                        <div class="chroma-field-col">
                            <div class="chroma-input-group">
                                <label>Type</label>
                                <select onchange="updateField(${index}, 'type', this.value)">
                                    <option value="text" ${field.type === 'text' ? 'selected' : ''}>Text</option>
                                    <option value="email" ${field.type === 'email' ? 'selected' : ''}>Email</option>
                                    <option value="tel" ${field.type === 'tel' ? 'selected' : ''}>Phone</option>
                                    <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Text Area</option>
                                    <option value="select_location" ${field.type === 'select_location' ? 'selected' : ''}>Location Select</option>
                                </select>
                            </div>
                            <div class="chroma-input-group">
                                <label>Width</label>
                                <select onchange="updateField(${index}, 'width', this.value)">
                                    <option value="half" ${field.width === 'half' ? 'selected' : ''}>Half Width (50%)</option>
                                    <option value="full" ${field.width === 'full' ? 'selected' : ''}>Full Width (100%)</option>
                                </select>
                            </div>
                        </div>
                        <div class="chroma-field-col">
                             <div class="chroma-input-group">
                                <label>Placeholder</label>
                                <input type="text" value="${escapeHtml(field.placeholder || '')}" onchange="updateField(${index}, 'placeholder', this.value)">
                            </div>
                            <div class="chroma-input-group">
                                <label>
                                    <input type="checkbox" ${field.required ? 'checked' : ''} onchange="updateField(${index}, 'required', this.checked)">
                                    Required
                                </label>
                            </div>
                        </div>
                        <div class="chroma-field-actions">
                            <button type="button" class="button button-small chroma-btn-remove" onclick="removeField(${index})">Remove</button>
                        </div>
                    `;
                    container.appendChild(row);
                });

                const addBtn = document.createElement('button');
                addBtn.type = 'button';
                addBtn.className = 'button button-primary';
                addBtn.innerText = '+ Add Field';
                addBtn.onclick = addField;
                container.appendChild(addBtn);

                input.value = JSON.stringify(fields);
            }

            window.updateField = function(index, key, value) {
                fields[index][key] = value;
                input.value = JSON.stringify(fields);
            };

            window.removeField = function(index) {
                if(confirm('Are you sure you want to remove this field?')) {
                    fields.splice(index, 1);
                    render();
                }
            };

            window.addField = function() {
                fields.push({
                    id: 'new_field_' + Date.now(),
                    label: 'New Field',
                    type: 'text',
                    required: false,
                    width: 'half',
                    placeholder: ''
                });
                render();
            };

            function escapeHtml(text) {
                if (!text) return '';
                return text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

            render();
        });
    </script>
    <?php
}

/**
 * Safe accessor for global settings without relying on ACF.
 */
function chroma_tour_get_global_setting($key, $default = '')
{
    if (function_exists('chroma_get_global_setting')) {
        return chroma_get_global_setting($key, $default);
    }
    $settings = get_option('chroma_global_settings', array());
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Tour Form Shortcode
 * Usage: [chroma_tour_form]
 */
function chroma_tour_form_shortcode()
{
    $fields_json = get_option('chroma_tour_fields', wp_json_encode(chroma_tour_default_fields()));
    $fields = json_decode($fields_json, true);
    if (!is_array($fields)) {
        $fields = chroma_tour_default_fields();
    }

    ob_start();
    ?>
    <form class="chroma-tour-form space-y-4" method="post" action="">
        <?php wp_nonce_field('chroma_tour_submit', 'chroma_tour_nonce'); ?>
        <input type="hidden" name="chroma_tour_redirect" value="<?php echo esc_url(get_permalink()); ?>" />

        <div class="grid md:grid-cols-2 gap-4">
            <?php foreach ($fields as $field):
                $id = esc_attr($field['id']);
                $label = esc_html($field['label']);
                $type = esc_attr($field['type']);
                $required = !empty($field['required']) ? 'required' : '';
                $width = isset($field['width']) && $field['width'] === 'full' ? 'md:col-span-2' : '';
                $placeholder = isset($field['placeholder']) ? esc_attr($field['placeholder']) : '';
                $asterisk = !empty($field['required']) ? ' *' : '';
                ?>

                <div class="<?php echo esc_attr($width); ?>">
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="tour_<?php echo $id; ?>">
                        <?php echo $label . $asterisk; ?>
                    </label>

                    <?php if ($type === 'select_location'): ?>
                        <select id="tour_<?php echo $id; ?>" name="<?php echo $id; ?>" <?php echo $required; ?> aria-label="<?php echo $label; ?>"
                            class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink">
                            <option value=""><?php echo $placeholder ?: 'Select a location...'; ?></option>
                            <?php
                            $locations = get_posts(array('post_type' => 'location', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                            foreach ($locations as $location):
                                ?>
                                <option value="<?php echo esc_attr($location->ID); ?>">
                                    <?php echo esc_html($location->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($type === 'textarea'): ?>
                        <textarea id="tour_<?php echo $id; ?>" name="<?php echo $id; ?>" <?php echo $required; ?> aria-label="<?php echo $label; ?>"
                            placeholder="<?php echo $placeholder; ?>"
                            class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink h-32"></textarea>

                    <?php else: ?>
                        <input type="<?php echo $type; ?>" id="tour_<?php echo $id; ?>" name="<?php echo $id; ?>" <?php echo $required; ?>
                            aria-label="<?php echo $label; ?>" placeholder="<?php echo $placeholder; ?>"
                            class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink" />
                    <?php endif; ?>
                </div>

            <?php endforeach; ?>
        </div>

        <button type="submit" name="chroma_tour_submit"
            class="w-full bg-chroma-red text-white text-xs font-semibold uppercase tracking-wider py-4 rounded-full shadow-soft hover:bg-chroma-red/90 transition">
            Request Tour
        </button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('chroma_tour_form', 'chroma_tour_form_shortcode');

/**
 * Handle Form Submission
 */
function chroma_handle_tour_submission()
{
    if (!isset($_POST['chroma_tour_submit']) || !wp_verify_nonce(wp_unslash($_POST['chroma_tour_nonce'] ?? ''), 'chroma_tour_submit')) {
        return;
    }

    // Get fields configuration
    $fields_json = get_option('chroma_tour_fields', wp_json_encode(chroma_tour_default_fields()));
    $fields = json_decode($fields_json, true);
    if (!is_array($fields)) {
        $fields = chroma_tour_default_fields();
    }

    $submission_data = array();
    $has_error = false;
    $parent_name = 'Unknown';
    $email = '';
    $location_id = 0;

    // Process fields
    foreach ($fields as $field) {
        $id = $field['id'];
        $required = !empty($field['required']);
        $value = isset($_POST[$id]) ? sanitize_text_field(wp_unslash($_POST[$id])) : '';

        if ($field['type'] === 'email') {
            $value = sanitize_email($value);
            if ($required && !is_email($value)) {
                $has_error = true;
            }
            if (is_email($value)) {
                $email = $value; // Capture email for sending
            }
        }

        if ($required && empty($value)) {
            $has_error = true;
        }

        // Capture specific fields for logic
        if ($id === 'parent_name') $parent_name = $value;
        if ($id === 'location_id') $location_id = intval($value);

        $submission_data[$field['label']] = $value;
    }

    $redirect_fallback = home_url('/contact/');
    $redirect_target = !empty($_POST['chroma_tour_redirect']) ? esc_url_raw(wp_unslash($_POST['chroma_tour_redirect'])) : (wp_get_referer() ?: $redirect_fallback);
    $redirect_url = wp_validate_redirect($redirect_target, $redirect_fallback);

    if ($has_error || empty($email)) {
        wp_safe_redirect(add_query_arg('tour_sent', '0', $redirect_url));
        exit;
    }

    // Determine email recipients
    $global_recipients_str = get_option('chroma_tour_email_recipient', 'enrollment@chromaela.com, info@chromaela.com');
    $recipients = array_map('trim', explode(',', $global_recipients_str));
    
    if ($location_id) {
        $location_email = get_post_meta($location_id, 'location_email', true);
        if ($location_email && is_email($location_email)) {
            $recipients[] = $location_email;
        }
    }

    // Remove duplicates and empty values
    $recipients = array_unique(array_filter($recipients, 'is_email'));
    
    if (empty($recipients)) {
        $recipients = array(get_option('admin_email'));
    }

    // Construct Message
    $subject = 'New Tour Request from ' . $parent_name;
    $message = "New tour request:\n\n";
    foreach ($submission_data as $label => $val) {
        $val_display = $val;
        if ($label === 'Preferred Location' && is_numeric($val) && $val > 0) {
            $val_display = get_the_title($val);
        }
        $message .= $label . ": " . $val_display . "\n";
    }

    wp_mail($recipients, $subject, $message);

    // Log to Lead Log CPT
    if (post_type_exists('lead_log')) {
        wp_insert_post(
            array(
                'post_type' => 'lead_log',
                'post_title' => 'Tour: ' . $parent_name,
                'post_status' => 'publish',
                'meta_input' => array(
                    'lead_type' => 'tour',
                    'lead_name' => $parent_name,
                    'lead_email' => $email,
                    'lead_location' => $location_id,
                    'lead_payload' => wp_json_encode($submission_data),
                ),
            )
        );
    }
    
    // Webhook Integration
    $webhook_url = get_option('chroma_tour_webhook_url', '');
    if (!empty($webhook_url)) {
        $webhook_data = array(
            'form_name' => 'Tour Request',
            'submitted_at' => current_time('mysql'),
            'data' => $submission_data
        );
        
        wp_remote_post($webhook_url, array(
            'body' => wp_json_encode($webhook_data),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 15,
            'blocking' => false // Don't wait for response
        ));
    }

    wp_safe_redirect(add_query_arg('tour_sent', '1', $redirect_url));
    exit;
}
add_action('template_redirect', 'chroma_handle_tour_submission');

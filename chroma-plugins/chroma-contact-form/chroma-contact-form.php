<?php
/**
 * Plugin Name: Chroma Contact Form
 * Description: General contact form with GUI editor, Webhooks, and Lead Log integration.
 * Version: 1.0.0
 * Author: Chroma Development Team
 * Text Domain: chroma-contact-form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Default Fields Configuration
 */
function chroma_contact_default_fields()
{
    return array(
        array(
            'id' => 'first_name',
            'label' => 'First Name',
            'type' => 'text',
            'required' => true,
            'width' => 'half',
            'placeholder' => 'Jane'
        ),
        array(
            'id' => 'last_name',
            'label' => 'Last Name',
            'type' => 'text',
            'required' => true,
            'width' => 'half',
            'placeholder' => 'Doe'
        ),
        array(
            'id' => 'email',
            'label' => 'Email Address',
            'type' => 'email',
            'required' => true,
            'width' => 'half',
            'placeholder' => 'you@example.com'
        ),
        array(
            'id' => 'phone',
            'label' => 'Phone Number',
            'type' => 'tel',
            'required' => true,
            'width' => 'half',
            'placeholder' => '(555) 123-4567'
        ),
        array(
            'id' => 'preferred_campus',
            'label' => 'Preferred Campus',
            'type' => 'select_location',
            'required' => false,
            'width' => 'half',
            'placeholder' => 'Select a location...'
        ),
        array(
            'id' => 'child_age',
            'label' => 'Child\'s Age',
            'type' => 'text',
            'required' => false,
            'width' => 'half',
            'placeholder' => 'e.g. 2 years'
        ),
        array(
            'id' => 'topic',
            'label' => 'Topic',
            'type' => 'select',
            'required' => false,
            'width' => 'full',
            'options' => 'Schedule a Tour, Tuition Inquiry, Careers / HR, Partnerships, Other Inquiry',
            'placeholder' => 'Select a topic...'
        ),
        array(
            'id' => 'message',
            'label' => 'Message',
            'type' => 'textarea',
            'required' => false,
            'width' => 'full',
            'placeholder' => 'Tell us about your family\'s needs...'
        )
    );
}

/**
 * Admin Menu & Settings
 */
function chroma_contact_register_settings()
{
    register_setting('chroma_contact_options', 'chroma_contact_fields', array(
        'type' => 'string',
        'sanitize_callback' => 'chroma_contact_sanitize_json',
        'default' => wp_json_encode(chroma_contact_default_fields())
    ));

    register_setting('chroma_contact_options', 'chroma_contact_webhook_url', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => ''
    ));

    register_setting('chroma_contact_options', 'chroma_contact_email_recipient', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default' => get_option('admin_email')
    ));

    // GHL Embed Settings
    register_setting('chroma_contact_options', 'chroma_contact_form_id', array(
        'type' => 'string',
        'default' => 'ibinKhrBmF0n4S5tFcz6',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    register_setting('chroma_contact_options', 'chroma_contact_form_height', array(
        'type' => 'integer',
        'default' => 779,
        'sanitize_callback' => 'absint'
    ));
    register_setting('chroma_contact_options', 'chroma_contact_form_name', array(
        'type' => 'string',
        'default' => 'Contact Us- Chroma Early Learning',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    register_setting('chroma_contact_options', 'chroma_contact_lazy_load', array(
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ));
    register_setting('chroma_contact_options', 'chroma_contact_lazy_delay', array(
        'type' => 'integer',
        'default' => 2000,
        'sanitize_callback' => 'absint'
    ));
}
add_action('admin_init', 'chroma_contact_register_settings');

function chroma_contact_sanitize_json($input)
{
    $decoded = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        add_settings_error('chroma_contact_fields', 'invalid_json', 'Invalid JSON format. Changes not saved.');
        return get_option('chroma_contact_fields');
    }
    return $input;
}

function chroma_contact_admin_menu()
{
    add_options_page(
        'Contact Form Settings',
        'Contact Form',
        'manage_options',
        'chroma-contact-form',
        'chroma_contact_settings_page_html'
    );
}
add_action('admin_menu', 'chroma_contact_admin_menu');

function chroma_contact_settings_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post" id="chroma-contact-settings-form">
            <?php
            settings_fields('chroma_contact_options');
            do_settings_sections('chroma_contact_options');

            $fields_json = get_option('chroma_contact_fields', wp_json_encode(chroma_contact_default_fields()));
            $webhook_url = get_option('chroma_contact_webhook_url', '');
            $email_recipient = get_option('chroma_contact_email_recipient', get_option('admin_email'));
            ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Email Recipient</th>
                    <td>
                        <input type="email" name="chroma_contact_email_recipient"
                            value="<?php echo esc_attr($email_recipient); ?>" class="regular-text" />
                        <p class="description">The email address where notifications will be sent.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Webhook URL</th>
                    <td>
                        <input type="url" name="chroma_contact_webhook_url" value="<?php echo esc_attr($webhook_url); ?>"
                            class="regular-text" placeholder="https://hooks.zapier.com/..." />
                        <p class="description">Optional. Send form submissions to this URL via POST request.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Form Fields</th>
                    <td>
                        <div id="chroma-fields-editor"></div>
                        <input type="hidden" name="chroma_contact_fields" id="chroma_contact_fields_input"
                            value="<?php echo esc_attr($fields_json); ?>">
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

        .chroma-input-group input,
        .chroma-input-group select,
        .chroma-input-group textarea {
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
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('chroma-fields-editor');
            const input = document.getElementById('chroma_contact_fields_input');
            let fields = JSON.parse(input.value || '[]');

            function render() {
                container.innerHTML = '';

                fields.forEach((field, index) => {
                    const row = document.createElement('div');
                    row.className = 'chroma-field-row';

                    let optionsHtml = '';
                    if (field.type === 'select') {
                        optionsHtml = `
                            <div class="chroma-input-group">
                                <label>Options (comma separated)</label>
                                <input type="text" value="${escapeHtml(field.options || '')}" onchange="updateField(${index}, 'options', this.value)" placeholder="Option 1, Option 2">
                            </div>
                        `;
                    }

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
                            ${optionsHtml}
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
                                    <option value="select" ${field.type === 'select' ? 'selected' : ''}>Dropdown (Custom)</option>
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

            window.updateField = function (index, key, value) {
                fields[index][key] = value;
                // Re-render if type changes to show/hide options
                if (key === 'type') {
                    render();
                } else {
                    input.value = JSON.stringify(fields);
                }
            };

            window.removeField = function (index) {
                if (confirm('Are you sure you want to remove this field?')) {
                    fields.splice(index, 1);
                    render();
                }
            };

            window.addField = function () {
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
 * Contact Form Shortcode
 * Uses official GHL iframe embed.
 * Usage: [chroma_contact_form]
 */
function chroma_contact_form_shortcode()
{
    // Get settings
    $form_id = get_option('chroma_contact_form_id', 'ibinKhrBmF0n4S5tFcz6');
    $form_height = get_option('chroma_contact_form_height', 779);
    $form_name = get_option('chroma_contact_form_name', 'Contact Us- Chroma Early Learning');
    $lazy_load = get_option('chroma_contact_lazy_load', true);
    $lazy_delay = get_option('chroma_contact_lazy_delay', 2000);
    
    $form_url = 'https://api.leadconnectorhq.com/widget/form/' . esc_attr($form_id);
    $loading_attr = $lazy_load ? 'lazy' : 'eager';
    
    ob_start();
    ?>
    <div class="chroma-contact-form-wrapper" data-lazy="<?php echo $lazy_load ? 'true' : 'false'; ?>" data-delay="<?php echo esc_attr($lazy_delay); ?>">
        <div class="chroma-ghl-iframe-container" style="min-height: <?php echo esc_attr($form_height); ?>px;">
            <iframe src="<?php echo esc_url($form_url); ?>"
                style="width:100%;height:100%;border:none;border-radius:3px;min-height:<?php echo esc_attr($form_height); ?>px;"
                id="inline-<?php echo esc_attr($form_id); ?>" 
                loading="<?php echo esc_attr($loading_attr); ?>"
                data-layout="{'id':'INLINE'}" data-trigger-type="alwaysShow"
                data-trigger-value="" data-activation-type="alwaysActivated" data-activation-value=""
                data-deactivation-type="neverDeactivate" data-deactivation-value=""
                data-form-name="<?php echo esc_attr($form_name); ?>" data-height="<?php echo esc_attr($form_height); ?>"
                data-layout-iframe-id="inline-<?php echo esc_attr($form_id); ?>" data-form-id="<?php echo esc_attr($form_id); ?>"
                title="<?php echo esc_attr($form_name); ?>">
            </iframe>
        </div>
    </div>

    <style>
        .chroma-contact-form-wrapper {
            width: 100%;
            margin: 0 auto;
        }

        .chroma-contact-form-wrapper .chroma-ghl-iframe-container {
            position: relative;
            overflow: hidden;
            border-radius: 0.75rem;
        }

        .chroma-contact-form-wrapper .chroma-ghl-iframe-container iframe {
            display: block;
        }
    </style>

    <?php if ($lazy_load): ?>
    <script>
        (function () {
            var loaded = false;
            var container = document.querySelector('.chroma-contact-form-wrapper');
            var delay = <?php echo intval($lazy_delay); ?>;

            function loadGHLScript() {
                if (loaded) return;
                loaded = true;

                var script = document.createElement('script');
                script.src = 'https://link.msgsndr.com/js/form_embed.js';
                script.async = true;
                document.body.appendChild(script);
            }

            // Load after configured delay OR when form is scrolled into view
            var timer = delay > 0 ? setTimeout(loadGHLScript, delay) : null;

            // IntersectionObserver to load when visible
            if ('IntersectionObserver' in window && container) {
                var observer = new IntersectionObserver(function (entries) {
                    if (entries[0].isIntersecting) {
                        if (timer) clearTimeout(timer);
                        loadGHLScript();
                        observer.disconnect();
                    }
                }, { rootMargin: '200px' });
                observer.observe(container);
            }
        })();
    </script>
    <?php else: ?>
    <script src="https://link.msgsndr.com/js/form_embed.js"></script>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}
add_shortcode('chroma_contact_form', 'chroma_contact_form_shortcode');

/**
 * Handle Form Submission
 */
function chroma_handle_contact_submission()
{
    if (!isset($_POST['chroma_contact_submit']) || !wp_verify_nonce(wp_unslash($_POST['chroma_contact_nonce'] ?? ''), 'chroma_contact_submit')) {
        return;
    }

    // Get fields configuration
    $fields_json = get_option('chroma_contact_fields', wp_json_encode(chroma_contact_default_fields()));
    $fields = json_decode($fields_json, true);
    if (!is_array($fields)) {
        $fields = chroma_contact_default_fields();
    }

    $submission_data = array();
    $has_error = false;
    $name = 'Unknown';
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
                $email = $value;
            }
        }

        if ($required && empty($value)) {
            $has_error = true;
        }

        // Capture specific fields for logic
        if ($id === 'first_name')
            $name = $value;
        if ($id === 'last_name')
            $name .= ' ' . $value;
        if ($id === 'preferred_campus' && is_numeric($value))
            $location_id = intval($value);

        $submission_data[$field['label']] = $value;
    }

    $redirect_fallback = home_url('/contact/');
    $redirect_target = !empty($_POST['chroma_contact_redirect']) ? esc_url_raw(wp_unslash($_POST['chroma_contact_redirect'])) : (wp_get_referer() ?: $redirect_fallback);
    $redirect_url = wp_validate_redirect($redirect_target, $redirect_fallback);

    if ($has_error || empty($email)) {
        wp_safe_redirect(add_query_arg('contact_sent', '0', $redirect_url));
        exit;
    }

    // Determine email recipients
    $global_recipients_str = get_option('chroma_contact_email_recipient', get_option('admin_email'));
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
    $subject = 'New Contact Inquiry from ' . $name;
    $message = "New contact inquiry:\n\n";
    foreach ($submission_data as $label => $val) {
        $val_display = $val;
        if ($label === 'Preferred Campus' && is_numeric($val) && $val > 0) {
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
                'post_title' => 'Contact: ' . $name,
                'post_status' => 'publish',
                'meta_input' => array(
                    'lead_type' => 'contact',
                    'lead_name' => $name,
                    'lead_email' => $email,
                    'lead_location' => $location_id,
                    'lead_payload' => wp_json_encode($submission_data),
                ),
            )
        );
    }

    // Webhook Integration
    $webhook_url = get_option('chroma_contact_webhook_url', '');
    if (!empty($webhook_url)) {
        $webhook_data = array(
            'form_name' => 'Contact Inquiry',
            'submitted_at' => current_time('mysql'),
            'data' => $submission_data
        );

        wp_remote_post($webhook_url, array(
            'body' => wp_json_encode($webhook_data),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 15,
            'blocking' => false
        ));
    }

    wp_safe_redirect(add_query_arg('contact_sent', '1', $redirect_url));
    exit;
}
add_action('template_redirect', 'chroma_handle_contact_submission');

<?php
/**
 * Plugin Name: Chroma Career Form
 * Description: Job application form with file upload support. Fully editable fields via Settings.
 * Version: 1.0.0
 * Author: Chroma Development Team
 * Text Domain: chroma-career-form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Default Fields Configuration
 */
function chroma_career_default_fields()
{
    return array(
        array(
            'id' => 'applicant_name',
            'label' => 'Full Name',
            'type' => 'text',
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
            'id' => 'phone',
            'label' => 'Phone',
            'type' => 'tel',
            'required' => true,
            'width' => 'half',
            'placeholder' => ''
        ),
        array(
            'id' => 'position',
            'label' => 'Position Applied For',
            'type' => 'text',
            'required' => false,
            'width' => 'half',
            'placeholder' => 'e.g. Lead Teacher'
        ),
        array(
            'id' => 'resume',
            'label' => 'Resume / Cover Letter',
            'type' => 'file',
            'required' => true,
            'width' => 'full',
            'placeholder' => ''
        ),
        array(
            'id' => 'message',
            'label' => 'Message',
            'type' => 'textarea',
            'required' => false,
            'width' => 'full',
            'placeholder' => 'Tell us about yourself...'
        )
    );
}

/**
 * Admin Menu & Settings
 */
function chroma_career_register_settings()
{
    register_setting('chroma_career_options', 'chroma_career_fields', array(
        'type' => 'string',
        'sanitize_callback' => 'chroma_career_sanitize_json',
        'default' => wp_json_encode(chroma_career_default_fields())
    ));

    register_setting('chroma_career_options', 'chroma_career_webhook_url', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => ''
    ));

    register_setting('chroma_career_options', 'chroma_career_email_recipient', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default' => 'careers@chromaela.com'
    ));

    // GHL Embed Settings
    register_setting('chroma_career_options', 'chroma_career_form_id', array(
        'type' => 'string',
        'default' => 'WYGFB2WBYuti6S6ys30H',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    register_setting('chroma_career_options', 'chroma_career_form_height', array(
        'type' => 'integer',
        'default' => 522,
        'sanitize_callback' => 'absint'
    ));
    register_setting('chroma_career_options', 'chroma_career_form_name', array(
        'type' => 'string',
        'default' => 'Careers Form - Chroma Early Learning',
        'sanitize_callback' => 'sanitize_text_field'
    ));
    register_setting('chroma_career_options', 'chroma_career_lazy_load', array(
        'type' => 'boolean',
        'default' => true,
        'sanitize_callback' => 'rest_sanitize_boolean'
    ));
    register_setting('chroma_career_options', 'chroma_career_lazy_delay', array(
        'type' => 'integer',
        'default' => 2000,
        'sanitize_callback' => 'absint'
    ));
}
add_action('admin_init', 'chroma_career_register_settings');

function chroma_career_sanitize_json($input)
{
    $decoded = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        add_settings_error('chroma_career_fields', 'invalid_json', 'Invalid JSON format. Changes not saved.');
        return get_option('chroma_career_fields');
    }
    return $input;
}

function chroma_career_admin_menu()
{
    add_options_page(
        'Career Form Settings',
        'Career Form',
        'manage_options',
        'chroma-career-form',
        'chroma_career_settings_page_html'
    );
}
add_action('admin_menu', 'chroma_career_admin_menu');

function chroma_career_settings_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post" id="chroma-career-settings-form">
            <?php
            settings_fields('chroma_career_options');
            do_settings_sections('chroma_career_options');

            $fields_json = get_option('chroma_career_fields', wp_json_encode(chroma_career_default_fields()));
            $webhook_url = get_option('chroma_career_webhook_url', '');
            $email_recipient = get_option('chroma_career_email_recipient', 'careers@chromaela.com');
            ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Email Recipient</th>
                    <td>
                        <input type="email" name="chroma_career_email_recipient"
                            value="<?php echo esc_attr($email_recipient); ?>" class="regular-text" />
                        <p class="description">The email address where applications will be sent.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Webhook URL</th>
                    <td>
                        <input type="url" name="chroma_career_webhook_url" value="<?php echo esc_attr($webhook_url); ?>"
                            class="regular-text" placeholder="https://hooks.zapier.com/..." />
                        <p class="description">Optional. Send form submissions to this URL via POST request.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Form Fields</th>
                    <td>
                        <div id="chroma-fields-editor"></div>
                        <input type="hidden" name="chroma_career_fields" id="chroma_career_fields_input"
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
        .chroma-input-group select {
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
            const input = document.getElementById('chroma_career_fields_input');
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
                                    <option value="file" ${field.type === 'file' ? 'selected' : ''}>File Upload</option>
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
                input.value = JSON.stringify(fields);
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
 * Career Form Shortcode
 * Uses official GHL iframe embed.
 * Usage: [chroma_career_form]
 */
function chroma_career_form_shortcode()
{
    // Get settings
    $form_id = get_option('chroma_career_form_id', 'WYGFB2WBYuti6S6ys30H');
    $form_height = get_option('chroma_career_form_height', 522);
    $form_name = get_option('chroma_career_form_name', 'Careers Form - Chroma Early Learning');
    $lazy_load = get_option('chroma_career_lazy_load', true);
    $lazy_delay = get_option('chroma_career_lazy_delay', 2000);

    $form_url = 'https://api.leadconnectorhq.com/widget/form/' . esc_attr($form_id);
    $loading_attr = $lazy_load ? 'lazy' : 'eager';

    ob_start();
    ?>
    <div class="chroma-career-form-wrapper" data-lazy="<?php echo $lazy_load ? 'true' : 'false'; ?>"
        data-delay="<?php echo esc_attr($lazy_delay); ?>">
        <div class="chroma-ghl-iframe-container" style="min-height: <?php echo esc_attr($form_height); ?>px;">
            <iframe src="<?php echo esc_url($form_url); ?>"
                style="width:100%;height:100%;border:none;border-radius:3px;min-height:<?php echo esc_attr($form_height); ?>px;"
                id="inline-<?php echo esc_attr($form_id); ?>" loading="<?php echo esc_attr($loading_attr); ?>"
                data-layout="{'id':'INLINE'}" data-trigger-type="alwaysShow" data-trigger-value=""
                data-activation-type="alwaysActivated" data-activation-value="" data-deactivation-type="neverDeactivate"
                data-deactivation-value="" data-form-name="<?php echo esc_attr($form_name); ?>"
                data-height="<?php echo esc_attr($form_height); ?>"
                data-layout-iframe-id="inline-<?php echo esc_attr($form_id); ?>"
                data-form-id="<?php echo esc_attr($form_id); ?>" title="<?php echo esc_attr($form_name); ?>">
            </iframe>
        </div>
    </div>

    <style>
        .chroma-career-form-wrapper {
            width: 100%;
            margin: 0 auto;
        }

        .chroma-career-form-wrapper .chroma-ghl-iframe-container {
            position: relative;
            overflow: hidden;
            border-radius: 0.75rem;
        }

        .chroma-career-form-wrapper .chroma-ghl-iframe-container iframe {
            display: block;
        }
    </style>

    <?php if ($lazy_load): ?>
        <script>
            (function () {
                var loaded = false;
                var container = document.querySelector('.chroma-career-form-wrapper');
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
add_shortcode('chroma_career_form', 'chroma_career_form_shortcode');

/**
 * Handle Form Submission
 */
function chroma_handle_career_submission()
{
    if (!isset($_POST['chroma_career_submit']) || !wp_verify_nonce(wp_unslash($_POST['chroma_career_nonce'] ?? ''), 'chroma_career_submit')) {
        return;
    }

    // Get fields configuration
    $fields_json = get_option('chroma_career_fields', wp_json_encode(chroma_career_default_fields()));
    $fields = json_decode($fields_json, true);
    if (!is_array($fields)) {
        $fields = chroma_career_default_fields();
    }

    $submission_data = array();
    $attachments = array();
    $has_error = false;
    $applicant_name = 'Unknown';
    $email = '';

    // Process fields
    foreach ($fields as $field) {
        $id = $field['id'];
        $required = !empty($field['required']);

        // Handle File Uploads
        if ($field['type'] === 'file') {
            if (!empty($_FILES[$id]['name'])) {
                $file = $_FILES[$id];

                // Basic validation
                $allowed_types = array('application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png');
                if (!in_array($file['type'], $allowed_types)) {
                    // Invalid file type
                    $has_error = true;
                    continue;
                }

                // Handle upload
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }

                $upload_overrides = array('test_form' => false);
                $movefile = wp_handle_upload($file, $upload_overrides);

                if ($movefile && !isset($movefile['error'])) {
                    $attachments[] = $movefile['file']; // For email attachment
                    $submission_data[$field['label']] = $movefile['url']; // Store URL in log
                } else {
                    $has_error = true;
                }
            } elseif ($required) {
                $has_error = true;
            }
            continue;
        }

        // Handle Text Fields
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

        if ($id === 'applicant_name')
            $applicant_name = $value;

        $submission_data[$field['label']] = $value;
    }

    $redirect_fallback = home_url('/careers/');
    $redirect_target = !empty($_POST['chroma_career_redirect']) ? esc_url_raw(wp_unslash($_POST['chroma_career_redirect'])) : (wp_get_referer() ?: $redirect_fallback);
    $redirect_url = wp_validate_redirect($redirect_target, $redirect_fallback);

    if ($has_error || empty($email)) {
        wp_safe_redirect(add_query_arg('career_sent', '0', $redirect_url));
        exit;
    }

    // Email to careers team
    $to_email = get_option('chroma_career_email_recipient', 'careers@chromaela.com');
    $subject = 'New Job Application: ' . $applicant_name;
    $message = "New job application:\n\n";
    foreach ($submission_data as $label => $val) {
        $message .= $label . ": " . $val . "\n";
    }

    wp_mail($to_email, $subject, $message, '', $attachments);

    // Log to Lead Log CPT
    if (post_type_exists('lead_log')) {
        wp_insert_post(
            array(
                'post_type' => 'lead_log',
                'post_title' => 'Application: ' . $applicant_name,
                'post_status' => 'publish',
                'meta_input' => array(
                    'lead_type' => 'career',
                    'lead_name' => $applicant_name,
                    'lead_email' => $email,
                    'lead_payload' => wp_json_encode($submission_data),
                ),
            )
        );
    }

    // Webhook Integration
    $webhook_url = get_option('chroma_career_webhook_url', '');
    if (!empty($webhook_url)) {
        $webhook_data = array(
            'form_name' => 'Job Application',
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

    // Cleanup attachments if needed (optional, but keeping them in uploads is usually better for record keeping)

    wp_safe_redirect(add_query_arg('career_sent', '1', $redirect_url));
    exit;
}
add_action('template_redirect', 'chroma_handle_career_submission');

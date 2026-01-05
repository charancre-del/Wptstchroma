<?php
/**
 * Plugin Name: Chroma Acquisitions Form
 * Description: Acquisitions inquiry form for potential sellers to Chroma ELA. Fully editable fields via Settings.
 * Version: 1.1.0
 * Author: Chroma Development Team
 * Text Domain: chroma-acquisitions-form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Default Fields Configuration
 */
function chroma_acquisition_default_fields()
{
    return array(
        array(
            'id' => 'contact_name',
            'label' => 'Your Name',
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
            'width' => 'full',
            'placeholder' => ''
        ),
        array(
            'id' => 'facility_name',
            'label' => 'Facility Name',
            'type' => 'text',
            'required' => true,
            'width' => 'full',
            'placeholder' => ''
        ),
        array(
            'id' => 'facility_location',
            'label' => 'Facility Location (City, State)',
            'type' => 'text',
            'required' => true,
            'width' => 'full',
            'placeholder' => ''
        ),
        array(
            'id' => 'details',
            'label' => 'Additional Details',
            'type' => 'textarea',
            'required' => false,
            'width' => 'full',
            'placeholder' => ''
        )
    );
}

/**
 * Admin Menu & Settings
 */
function chroma_acquisition_register_settings()
{
    register_setting('chroma_acquisition_options', 'chroma_acquisition_fields', array(
        'type' => 'string',
        'sanitize_callback' => 'chroma_acquisition_sanitize_json',
        'default' => wp_json_encode(chroma_acquisition_default_fields())
    ));
    
    register_setting('chroma_acquisition_options', 'chroma_acquisition_webhook_url', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => ''
    ));

    register_setting('chroma_acquisition_options', 'chroma_acquisition_email_recipient', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default' => 'acquisitions@chromaela.com'
    ));
}
add_action('admin_init', 'chroma_acquisition_register_settings');

function chroma_acquisition_sanitize_json($input)
{
    $decoded = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        add_settings_error('chroma_acquisition_fields', 'invalid_json', 'Invalid JSON format. Changes not saved.');
        return get_option('chroma_acquisition_fields');
    }
    return $input;
}

function chroma_acquisition_admin_menu()
{
    add_options_page(
        'Acquisition Form Settings',
        'Acquisition Form',
        'manage_options',
        'chroma-acquisition-form',
        'chroma_acquisition_settings_page_html'
    );
}
add_action('admin_menu', 'chroma_acquisition_admin_menu');

function chroma_acquisition_settings_page_html()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post" id="chroma-acquisition-settings-form">
            <?php
            settings_fields('chroma_acquisition_options');
            do_settings_sections('chroma_acquisition_options');
            
            $fields_json = get_option('chroma_acquisition_fields', wp_json_encode(chroma_acquisition_default_fields()));
            $webhook_url = get_option('chroma_acquisition_webhook_url', '');
            $email_recipient = get_option('chroma_acquisition_email_recipient', 'csingh@chromaela.com');
            ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Email Recipient</th>
                    <td>
                        <input type="email" name="chroma_acquisition_email_recipient" value="<?php echo esc_attr($email_recipient); ?>" class="regular-text" />
                        <p class="description">The email address where notifications will be sent.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Webhook URL</th>
                    <td>
                        <input type="url" name="chroma_acquisition_webhook_url" value="<?php echo esc_attr($webhook_url); ?>" class="regular-text" placeholder="https://hooks.zapier.com/..." />
                        <p class="description">Optional. Send form submissions to this URL via POST request.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Form Fields</th>
                    <td>
                        <div id="chroma-fields-editor"></div>
                        <input type="hidden" name="chroma_acquisition_fields" id="chroma_acquisition_fields_input" value="<?php echo esc_attr($fields_json); ?>">
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
            const input = document.getElementById('chroma_acquisition_fields_input');
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
 * Acquisitions Form Shortcode
 * Usage: [chroma_acquisition_form]
 */
function chroma_acquisition_form_shortcode()
{
    $fields_json = get_option('chroma_acquisition_fields', wp_json_encode(chroma_acquisition_default_fields()));
    $fields = json_decode($fields_json, true);
    if (!is_array($fields)) {
        $fields = chroma_acquisition_default_fields();
    }

    ob_start();
    ?>
    <form class="chroma-acquisition-form space-y-4" method="post" action="">
        <?php wp_nonce_field('chroma_acquisition_submit', 'chroma_acquisition_nonce'); ?>
        <input type="hidden" name="chroma_acquisition_redirect" value="<?php echo esc_url(get_permalink()); ?>" />

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
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="acq_<?php echo $id; ?>">
                        <?php echo $label . $asterisk; ?>
                    </label>

                    <?php if ($type === 'textarea'): ?>
                        <textarea id="acq_<?php echo $id; ?>" name="<?php echo $id; ?>" <?php echo $required; ?> aria-label="<?php echo $label; ?>"
                            placeholder="<?php echo $placeholder; ?>"
                            class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink h-32"></textarea>

                    <?php else: ?>
                        <input type="<?php echo $type; ?>" id="acq_<?php echo $id; ?>" name="<?php echo $id; ?>" <?php echo $required; ?>
                            aria-label="<?php echo $label; ?>" placeholder="<?php echo $placeholder; ?>"
                            class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink" />
                    <?php endif; ?>
                </div>

            <?php endforeach; ?>
        </div>

        <button type="submit" name="chroma_acquisition_submit"
            class="w-full bg-chroma-red text-white text-xs font-semibold uppercase tracking-wider py-4 rounded-full shadow-soft hover:bg-chroma-red/90 transition">
            Submit Inquiry
        </button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('chroma_acquisition_form', 'chroma_acquisition_form_shortcode');

/**
 * Handle Form Submission
 */
function chroma_handle_acquisition_submission()
{
    if (!isset($_POST['chroma_acquisition_submit']) || !wp_verify_nonce(wp_unslash($_POST['chroma_acquisition_nonce'] ?? ''), 'chroma_acquisition_submit')) {
        return;
    }

    // Get fields configuration
    $fields_json = get_option('chroma_acquisition_fields', wp_json_encode(chroma_acquisition_default_fields()));
    $fields = json_decode($fields_json, true);
    if (!is_array($fields)) {
        $fields = chroma_acquisition_default_fields();
    }

    $submission_data = array();
    $has_error = false;
    $contact_name = 'Unknown';
    $email = '';
    $facility_name = 'Unknown Facility';

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
        if ($id === 'contact_name') $contact_name = $value;
        if ($id === 'facility_name') $facility_name = $value;

        $submission_data[$field['label']] = $value;
    }

    $redirect_fallback = home_url('/acquisitions/');
    $redirect_target = !empty($_POST['chroma_acquisition_redirect']) ? esc_url_raw(wp_unslash($_POST['chroma_acquisition_redirect'])) : (wp_get_referer() ?: $redirect_fallback);
    $redirect_url = wp_validate_redirect($redirect_target, $redirect_fallback);

    if ($has_error || empty($email)) {
        wp_safe_redirect(add_query_arg('acquisition_sent', '0', $redirect_url));
        exit;
    }

    // Email to acquisitions team
    $to_email = get_option('chroma_acquisition_email_recipient', 'acquisitions@chromaela.com');
    $subject = 'New Acquisition Inquiry: ' . $facility_name;
    $message = "New acquisition inquiry:\n\n";
    foreach ($submission_data as $label => $val) {
        $message .= $label . ": " . $val . "\n";
    }

    wp_mail($to_email, $subject, $message);

    // Log to Lead Log CPT
    if (post_type_exists('lead_log')) {
        wp_insert_post(
            array(
                'post_type' => 'lead_log',
                'post_title' => 'Acquisition: ' . $facility_name,
                'post_status' => 'publish',
                'meta_input' => array(
                    'lead_type' => 'acquisition',
                    'lead_name' => $contact_name,
                    'lead_email' => $email,
                    'lead_payload' => wp_json_encode($submission_data),
                ),
            )
        );
    }
    
    // Webhook Integration
    $webhook_url = get_option('chroma_acquisition_webhook_url', '');
    if (!empty($webhook_url)) {
        $webhook_data = array(
            'form_name' => 'Acquisition Inquiry',
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

    wp_safe_redirect(add_query_arg('acquisition_sent', '1', $redirect_url));
    exit;
}
add_action('template_redirect', 'chroma_handle_acquisition_submission');

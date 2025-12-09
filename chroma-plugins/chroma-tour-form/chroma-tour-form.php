<?php
/**
 * Plugin Name: Chroma Tour Form
 * Description: Native tour request form with dynamic LeadConnector options and direct API submission.
 * Version: 2.0.1
 * Author: Chroma Development Team
 * Text Domain: chroma-tour-form
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fetch and Parse Dynamic Options from GHL
 * Caches results for 12 hours.
 */
function chroma_tour_get_dynamic_options()
{
    $transient_key = 'chroma_tour_ghl_options';
    $cached = get_transient($transient_key);

    if ($cached !== false && is_array($cached)) {
        return $cached;
    }

    $url = 'https://api.leadconnectorhq.com/widget/form/JpecxfWJrxyWE7Ufdtkd';
    $response = wp_remote_get($url, array('timeout' => 15));

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return array(); // Fail silently/gracefully
    }

    $html = wp_remote_retrieve_body($response);

    // Suppress warnings for malformed HTML
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    // Encoding hack to prevent font issues
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $options = array(
        'ages' => array(),
        'locations' => array()
    );

    // Extract Ages: ID tjcMDuffDYLzvezpnxly
    $age_nodes = $xpath->query('//ul[contains(@id, "tjcMDuffDYLzvezpnxly")]//li//span[@class="multiselect__option" or contains(@class, "multiselect__option--highlight")]');
    if ($age_nodes->length > 0) {
        foreach ($age_nodes as $node) {
            $text = trim($node->textContent);
            if ($text && strpos($text, 'No elements found') === false && strpos($text, 'List is empty') === false) {
                $options['ages'][] = $text;
            }
        }
    } else {
        // Fallback defaults if scraping fails
        $options['ages'] = array('Infant', 'Toddler', 'Preschool', 'Pre-K', 'AfterSchool/Summer Camp');
    }

    // Extract Locations: ID DKcjpcd5izdAklwt1Bby
    $location_nodes = $xpath->query('//ul[contains(@id, "DKcjpcd5izdAklwt1Bby")]//li//span[@class="multiselect__option" or contains(@class, "multiselect__option--highlight")]');
    if ($location_nodes->length > 0) {
        foreach ($location_nodes as $node) {
            $text = trim($node->textContent);
            if ($text && strpos($text, 'No elements found') === false && strpos($text, 'List is empty') === false) {
                $options['locations'][] = $text;
            }
        }
    } else {
        // Fallback defaults if scraping fails
        $options['locations'] = array('1205 Upper Burris Rd, Canton, GA 30114, USA');
    }

    // Cache if we found data
    set_transient($transient_key, $options, 12 * HOUR_IN_SECONDS);

    return $options;
}

/**
 * Tour Form Shortcode
 * Uses official GHL iframe embed with theme-styled container overlay.
 */
function chroma_tour_form_shortcode()
{
    ob_start();
    ?>
    <div class="chroma-tour-form-wrapper">
        <!-- Theme-Matched Container -->
        <div class="bg-white rounded-2xl shadow-soft p-6 md:p-8 border border-gray-100">

            <!-- GHL Iframe - Official Embed -->
            <div class="chroma-ghl-iframe-container" style="min-height: 788px;">
                <iframe src="https://api.leadconnectorhq.com/widget/form/JpecxfWJrxyWE7Ufdtkd"
                    style="width:100%;height:100%;border:none;border-radius:3px;min-height:788px;"
                    id="inline-JpecxfWJrxyWE7Ufdtkd" data-layout="{'id':'INLINE'}" data-trigger-type="alwaysShow"
                    data-trigger-value="" data-activation-type="alwaysActivated" data-activation-value=""
                    data-deactivation-type="neverDeactivate" data-deactivation-value=""
                    data-form-name="VIRTUAL TOUR INFORMATION - Chroma Early Learning" data-height="788"
                    data-layout-iframe-id="inline-JpecxfWJrxyWE7Ufdtkd" data-form-id="JpecxfWJrxyWE7Ufdtkd"
                    title="VIRTUAL TOUR INFORMATION - Chroma Early Learning">
                </iframe>
            </div>
        </div>
    </div>

    <style>
        .chroma-tour-form-wrapper {
            max-width: 1100px;
            margin: 0 auto;
        }

        .chroma-ghl-iframe-container {
            position: relative;
            overflow: hidden;
            border-radius: 0.75rem;
        }

        .chroma-ghl-iframe-container iframe {
            display: block;
        }
    </style>

    <script src="https://link.msgsndr.com/js/form_embed.js"></script>
    <?php
    return ob_get_clean();
}
add_shortcode('chroma_tour_form', 'chroma_tour_form_shortcode');

/**
 * Handle Form Submission via API
 */
function chroma_handle_tour_submission()
{
    if (!isset($_POST['chroma_tour_submit']) || !wp_verify_nonce(wp_unslash($_POST['chroma_tour_nonce'] ?? ''), 'chroma_tour_submit')) {
        return;
    }

    $fields = array(
        'first_name',
        'last_name',
        'phone',
        'email',
        'KXEHzTOMGosdJUu1Eqri',
        'dTabDQmMvBfwpMCUaPpU',
        '9dpin9NpFnCaEY9hTL51',
        'tjcMDuffDYLzvezpnxly',
        'DKcjpcd5izdAklwt1Bby'
    );

    $payload = array();
    $payload['formId'] = 'JpecxfWJrxyWE7Ufdtkd';
    $payload['locationId'] = 'euN4JvLvKNYTYh4Xyh3p';
    $payload['companyId'] = 'aXTQYHsTlryLiFQng6a9'; // Critical missing field
    $payload['traceId'] = '48e8401e-b945-440e-889d-210e75758ee7'; // Updated from V2 source
    $payload['country'] = 'US';
    $payload['inputType'] = 'form_builder';

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field(wp_unslash($_POST[$field]));

            // Strict Phone Formatting for GHL (E.164)
            if ($field === 'phone' && !empty($value)) {
                // Remove non-digits
                $digits = preg_replace('/\D/', '', $value);
                // If 10 digits, add +1
                if (strlen($digits) === 10) {
                    $value = '+1' . $digits;
                } elseif (strlen($digits) === 11 && strpos($digits, '1') === 0) {
                    $value = '+' . $digits;
                }
                // If incorrect length, sending as-is but it likely fails validation
            }

            $payload[$field] = $value;
        }
    }

    // Submit to GHL (Using backend. endpoint from source config)
    $response = wp_remote_post('https://backend.leadconnectorhq.com/forms/submit', array(
        'body' => wp_json_encode($payload),
        'headers' => array(
            'Content-Type' => 'application/json',
            'Referer' => 'https://api.leadconnectorhq.com/widget/form/JpecxfWJrxyWE7Ufdtkd',
            'Origin' => 'https://api.leadconnectorhq.com',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ),
        'timeout' => 20
    ));

    $redirect_fallback = home_url('/contact/');
    $redirect_target = !empty($_POST['chroma_tour_redirect']) ? esc_url_raw(wp_unslash($_POST['chroma_tour_redirect'])) : (wp_get_referer() ?: $redirect_fallback);

    // Check success (GHL usually returns 200 or 201)
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) < 400) {
        // Log locally for backup
        if (post_type_exists('lead_log')) {
            wp_insert_post(array(
                'post_type' => 'lead_log',
                'post_title' => 'Tour: ' . $payload['first_name'] . ' ' . $payload['last_name'],
                'post_status' => 'publish',
                'meta_input' => array(
                    'lead_type' => 'tour',
                    'lead_payload' => wp_json_encode($payload),
                ),
            ));
        }

        wp_safe_redirect(add_query_arg('tour_sent', '1', $redirect_target));
    } else {
        // DEBUG: Die with detailed info
        echo '<div style="background:#f00; color:#fff; padding:20px; text-align:left;">';
        echo '<h1>Form Submission Failed</h1>';
        echo '<p>Please report this error:</p>';
        echo '<pre>';
        if (is_wp_error($response)) {
            echo 'WP Error: ' . $response->get_error_message();
        } else {
            echo 'Status Code: ' . wp_remote_retrieve_response_code($response) . "\n";
            echo 'Response Body: ' . esc_html(wp_remote_retrieve_body($response));
        }
        echo '</pre></div>';
        exit;
    }

    exit;
}
add_action('template_redirect', 'chroma_handle_tour_submission');

/**
 * Legacy Settings (Admin Interface Preserved but Inactive)
 */
function chroma_tour_admin_menu()
{
    add_options_page('Tour Form Settings', 'Tour Form', 'manage_options', 'chroma-tour-form', 'chroma_tour_settings_page_html');
}
add_action('admin_menu', 'chroma_tour_admin_menu');

function chroma_tour_settings_page_html()
{
    echo '<div class="wrap"><h1>Tour Form</h1><p>The tour form is currently using the native integration with LeadConnector (GHL). Field settings here are currently bypassed.</p></div>';
}

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
 * Native implementation submitting to GHL.
 */
function chroma_tour_form_shortcode()
{
    $dynamic_options = chroma_tour_get_dynamic_options();
    $locations = isset($dynamic_options['locations']) ? $dynamic_options['locations'] : array();
    $ages = isset($dynamic_options['ages']) ? $dynamic_options['ages'] : array();

    ob_start();
    ?>
    <form class="chroma-tour-form space-y-6" method="post" action="">
        <?php wp_nonce_field('chroma_tour_submit', 'chroma_tour_nonce'); ?>
        <input type="hidden" name="chroma_tour_redirect" value="<?php echo esc_url(get_permalink()); ?>" />

        <!-- Parent Information -->
        <div class="space-y-4">
            <h3 class="text-xl font-bold text-brand-offblack">Parent Information</h3>

            <div class="grid md:grid-cols-2 gap-4">
                <!-- First Name -->
                <div>
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="first_name">
                        First Name *
                    </label>
                    <input type="text" id="first_name" name="first_name" required
                        class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink" />
                </div>

                <!-- Last Name -->
                <div>
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="last_name">
                        Last Name *
                    </label>
                    <input type="text" id="last_name" name="last_name" required
                        class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink" />
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="phone">
                        Cell Phone *
                    </label>
                    <input type="tel" id="phone" name="phone" required
                        class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink" />
                </div>

                <!-- Alternate Phone -->
                <div>
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="uCpuvkOPhwArxUCz820O">
                        Alternate Phone *
                    </label>
                    <input type="tel" id="uCpuvkOPhwArxUCz820O" name="uCpuvkOPhwArxUCz820O" required
                        class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink" />
                </div>

                <!-- Email -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="email">
                        Email Address *
                    </label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink" />
                </div>
            </div>
        </div>

        <!-- Child's Information -->
        <div class="space-y-4 pt-4 border-t border-gray-100">
            <h3 class="text-xl font-bold text-brand-offblack">Child's Information</h3>

            <div class="grid md:grid-cols-2 gap-4">
                <!-- Child First Name -->
                <div>
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="KXEHzTOMGosdJUu1Eqri">
                        Child's First Name *
                    </label>
                    <input type="text" id="KXEHzTOMGosdJUu1Eqri" name="KXEHzTOMGosdJUu1Eqri" required
                        class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink" />
                </div>

                <!-- Child Last Name -->
                <div>
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="dTabDQmMvBfwpMCUaPpU">
                        Child's Last Name *
                    </label>
                    <input type="text" id="dTabDQmMvBfwpMCUaPpU" name="dTabDQmMvBfwpMCUaPpU" required
                        class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink" />
                </div>

                <!-- Gender -->
                <div>
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="ZC8R7WzKJdl1C13rOD4D">
                        Gender *
                    </label>
                    <select id="ZC8R7WzKJdl1C13rOD4D" name="ZC8R7WzKJdl1C13rOD4D" required
                        class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink">
                        <option value="">Select...</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <!-- Desired Start Date -->
                <div>
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="9dpin9NpFnCaEY9hTL51">
                        Desired Start Date *
                    </label>
                    <input type="date" id="9dpin9NpFnCaEY9hTL51" name="9dpin9NpFnCaEY9hTL51" required
                        class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink" />
                </div>

                <!-- Child Age -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="tjcMDuffDYLzvezpnxly">
                        How old is your child *
                    </label>
                    <select id="tjcMDuffDYLzvezpnxly" name="tjcMDuffDYLzvezpnxly" required
                        class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink">
                        <option value="">Select...</option>
                        <?php if (!empty($ages)): ?>
                            <?php foreach ($ages as $age): ?>
                                <option value="<?php echo esc_attr($age); ?>"><?php echo esc_html($age); ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="Infant">Infant</option>
                            <option value="Toddler">Toddler</option>
                            <option value="Preschool">Preschool</option>
                            <option value="Pre-K">Pre-K</option>
                            <option value="AfterSchool/Summer Camp">AfterSchool/Summer Camp</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Location -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-brand-ink uppercase mb-1.5" for="DKcjpcd5izdAklwt1Bby">
                        What Metro Atlanta Location is best suited for your needs? *
                    </label>
                    <select id="DKcjpcd5izdAklwt1Bby" name="DKcjpcd5izdAklwt1Bby" required
                        class="w-full px-4 py-3 rounded-xl border border-chroma-blue/20 bg-white focus:border-chroma-blue outline-none text-brand-ink">
                        <option value="">Select a location...</option>
                        <?php if (!empty($locations)): ?>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo esc_attr($location); ?>"><?php echo esc_html($location); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
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
        'uCpuvkOPhwArxUCz820O',
        'email',
        'KXEHzTOMGosdJUu1Eqri',
        'dTabDQmMvBfwpMCUaPpU',
        'ZC8R7WzKJdl1C13rOD4D',
        '9dpin9NpFnCaEY9hTL51',
        'tjcMDuffDYLzvezpnxly',
        'DKcjpcd5izdAklwt1Bby'
    );

    $payload = array();
    $payload['formId'] = 'JpecxfWJrxyWE7Ufdtkd';
    $payload['locationId'] = '82'; // From source

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $payload[$field] = sanitize_text_field(wp_unslash($_POST[$field]));
        }
    }

    // Submit to GHL (try services. subdomain if backend. fails)
    $response = wp_remote_post('https://services.leadconnectorhq.com/forms/submit', array(
        'body' => wp_json_encode($payload),
        'headers' => array('Content-Type' => 'application/json'),
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
        echo '<h1>Submission Failed</h1>';
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

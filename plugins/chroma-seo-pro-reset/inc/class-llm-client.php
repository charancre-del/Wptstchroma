<?php
/**
 * LLM Client
 * Handles communication with OpenAI API
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_LLM_Client
{
    private $api_key;
    private $model;
    private $base_url;

    public function __construct()
    {
        $this->api_key = get_option('chroma_openai_api_key', '');
        $this->model = get_option('chroma_llm_model', 'gpt-4o-mini');
        $this->base_url = get_option('chroma_llm_base_url', 'https://api.openai.com/v1');

        // Register AJAX actions for saving key and testing connection
        add_action('wp_ajax_chroma_save_llm_settings', [$this, 'ajax_save_settings']);
        add_action('wp_ajax_chroma_test_llm_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_chroma_generate_schema', [$this, 'ajax_generate_schema']);
        add_action('wp_ajax_chroma_generate_llm_targeting', [$this, 'ajax_generate_llm_targeting']);
        add_action('wp_ajax_chroma_generate_general_seo_meta', [$this, 'ajax_generate_general_seo_meta']);
        add_action('wp_ajax_chroma_translate_text', [$this, 'ajax_translate_text']);
        add_action('wp_ajax_chroma_fetch_available_models', [$this, 'ajax_fetch_available_models']);
    }

    /**
     * AJAX: Fetch available models from Gemini API
     */
    public function ajax_fetch_available_models() {
        check_ajax_referer('chroma_fetch_models', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $api_key = get_option('chroma_openai_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'API key not configured']);
        }

        $base_url = get_option('chroma_llm_base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $url = rtrim($base_url, '/') . '/models?key=' . $api_key;

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($body['models'])) {
            wp_send_json_error(['message' => 'No models returned from API. Check your API key.']);
        }

        // Parse models - filter to only generation models
        $models = [];
        foreach ($body['models'] as $model) {
            $name = $model['name'] ?? '';
            $display_name = $model['displayName'] ?? $name;
            
            // Extract the model ID (e.g., "models/gemini-2.0-flash-exp" -> "gemini-2.0-flash-exp")
            $model_id = str_replace('models/', '', $name);
            
            // Only include generative models (skip embedding, etc.)
            $supported = $model['supportedGenerationMethods'] ?? [];
            if (in_array('generateContent', $supported)) {
                $models[$model_id] = $display_name;
            }
        }

        if (empty($models)) {
            wp_send_json_error(['message' => 'No generative models found']);
        }

        // Sort models alphabetically
        asort($models);
        
        // Cache the models
        update_option('chroma_llm_available_models', $models);

        wp_send_json_success(['models' => $models]);
    }


    /**
     * Render Settings Section
     */
    public function render_settings()
    {
        $key = $this->api_key;
        $model = $this->model;
        $base_url = $this->base_url;
        ?>
        <div class="chroma-seo-card">
            <h2>🤖 AI Integration Settings</h2>
            <p>Configure your LLM provider (OpenAI, OpenRouter, etc.).</p>

            <table class="form-table">
                <tr>
                    <th scope="row">API Key</th>
                    <td>
                        <input type="password" id="chroma_openai_api_key" value="<?php echo esc_attr($key); ?>"
                            class="regular-text" placeholder="sk-..." autocomplete="off">
                        <p class="description">Your key is stored securely.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Model Name</th>
                    <td>
                        <input type="text" id="chroma_llm_model" value="<?php echo esc_attr($model); ?>" class="regular-text"
                            placeholder="gpt-4o-mini">
                        <p class="description">e.g., <code>gpt-4o</code>, <code>claude-3-sonnet</code> (via OpenRouter),
                            <code>llama-3</code>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Base URL</th>
                    <td>
                        <input type="text" id="chroma_llm_base_url" value="<?php echo esc_attr($base_url); ?>"
                            class="regular-text" placeholder="https://api.openai.com/v1">
                        <p class="description">Default: <code>https://api.openai.com/v1</code>. Change for OpenRouter/LocalAI.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Actions</th>
                    <td>
                        <div style="display: flex; gap: 10px;">
                            <button id="chroma-save-llm" class="button button-primary">Save Settings</button>
                            <button id="chroma-test-llm" class="button button-secondary">Test Connection</button>
                        </div>
                        <span id="chroma-llm-status" style="display:block; margin-top: 10px; font-weight: bold;"></span>
                    </td>
                </tr>
            </table>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                // Unbind previous events to prevent duplicates in case of AJAX reloads
                $(document).off('click', '#chroma-save-llm');
                $(document).off('click', '#chroma-test-llm');

                // Save Settings
                $(document).on('click', '#chroma-save-llm', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    btn.prop('disabled', true).text('Saving...');

                    $.post(ajaxurl, {
                        action: 'chroma_save_llm_settings',
                        api_key: $('#chroma_openai_api_key').val(),
                        model: $('#chroma_llm_model').val(),
                        base_url: $('#chroma_llm_base_url').val()
                    }, function (response) {
                        btn.prop('disabled', false).text('Save Settings');
                        if (response.success) {
                            alert('Settings saved! ' + (response.data.message || ''));
                        } else {
                            alert('Error saving settings: ' + (response.data.message || ''));
                        }
                    }).fail(function () {
                        btn.prop('disabled', false).text('Save Settings');
                        alert('Request failed. Please check your internet connection.');
                    });
                });

                // Test Connection
                $(document).on('click', '#chroma-test-llm', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    var status = $('#chroma-llm-status');

                    btn.prop('disabled', true).text('Testing...');
                    status.text('').css('color', 'inherit');

                    $.post(ajaxurl, {
                        action: 'chroma_test_llm_connection'
                    }, function (response) {
                        btn.prop('disabled', false).text('Test Connection');
                        if (response.success) {
                            status.text('✅ Connected! ' + (response.data.message || '')).css('color', 'green');
                        } else {
                            status.text('❌ Failed: ' + (response.data.message || 'Unknown error')).css('color', 'red');
                        }
                    }).fail(function () {
                        btn.prop('disabled', false).text('Test Connection');
                        status.text('❌ Request failed completely. Check network/console.').css('color', 'red');
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * AJAX: Save Settings
     */
    public function ajax_save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        if (isset($_POST['api_key'])) {
            $key = sanitize_text_field($_POST['api_key']);
            chroma_debug_log(' LLM: Saving API Key - Length: ' . strlen($key));
            if (empty($key)) {
                chroma_debug_log(' LLM: Warning - Saving EMPTY API Key');
            }
            $updated = update_option('chroma_openai_api_key', $key);
            chroma_debug_log(' LLM: update_option result: ' . ($updated ? 'true' : 'false'));
        }
        if (isset($_POST['model'])) {
            update_option('chroma_llm_model', sanitize_text_field($_POST['model']));
        }
        if (isset($_POST['base_url'])) {
            $url = esc_url_raw($_POST['base_url']);
            // Remove trailing slash for consistency
            $url = rtrim($url, '/');
            update_option('chroma_llm_base_url', $url);
        }

        $debug_msg = 'Key saved.';
        if (isset($key)) {
            $debug_msg .= ' Length: ' . strlen($key);
        }

        wp_send_json_success(['message' => $debug_msg]);
    }

    /**
     * AJAX: Test Connection
     */
    public function ajax_test_connection()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // Lazy-load API key fresh from database (in case user just saved it)
        $api_key = get_option('chroma_openai_api_key', '');
        chroma_debug_log(' LLM: Testing Connection. Loaded API Key Length: ' . strlen($api_key));
        if (!$api_key) {
            wp_send_json_error(['message' => 'No API Key found. (DB value empty)']);
        }

        $response = $this->make_request([
            'messages' => [
                ['role' => 'user', 'content' => 'Say "Hello" if you can hear me.']
            ]
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Connected!']);
    }

    /**
     * AJAX: Generate Schema from Content
     */
    public function ajax_generate_schema()
    {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $schema_type = sanitize_text_field($_POST['schema_type']);

        if (!$post_id || !$schema_type) {
            wp_send_json_error(['message' => 'Missing parameters']);
        }

        $result = $this->generate_schema_data($post_id, $schema_type);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        // Save relevant fields to AI Fallback Cache for reuse
        if (class_exists('Chroma_Fallback_Resolver')) {
            $mapped_fields = [
                'description' => 'description',
                'description_long' => 'description',
                'target_queries' => 'target_queries',
                'queries' => 'target_queries',
                'key_differentiators' => 'key_differentiators',
                'differentiators' => 'key_differentiators'
            ];
            foreach ($mapped_fields as $json_key => $cache_key) {
                if (!empty($result[$json_key])) {
                    Chroma_Fallback_Resolver::set_ai_field_cache($post_id, $cache_key, $result[$json_key]);
                }
            }
        }

        if (isset($_POST['auto_save']) && $_POST['auto_save'] === 'true') {
            $existing_schemas = get_post_meta($post_id, '_chroma_post_schemas', true);
            if (!is_array($existing_schemas)) {
                $existing_schemas = [];
            }
            
            // Look for existing schema of this type to update instead of appending duplicate
            $updated = false;
            foreach ($existing_schemas as $index => &$schema) {
                if (isset($schema['type']) && $schema['type'] === $schema_type) {
                    $schema['data'] = $result;
                    $updated = true;
                    // Fix: Ensure we only update the FIRST matching schema to avoid updating multiple duplicates if they exist?
                    // For now, let's just update the first one and break.
                    break;
                }
            }
            unset($schema); // Break reference

            if (!$updated) {
                // valid new schema
                 $existing_schemas[] = [
                    'type' => $schema_type,
                    'data' => $result
                ];
            }

            update_post_meta($post_id, '_chroma_post_schemas', $existing_schemas);
            $result['message'] = 'Schema generated and saved successfully.';
            $result['saved'] = true;
        }

        wp_send_json_success($result);
    }

    /**
     * Generate Schema Data (Public API)
     * 
     * @param int $post_id
     * @param string $schema_type
     * @param array $context Optional instructions or context
     * @return array|WP_Error
     */
    public function generate_schema_data($post_id, $schema_type, $context = [])
    {
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post not found');
        }

        // Get schema definition to guide the LLM
        $definitions = Chroma_Schema_Types::get_definitions();
        $expected_keys = [];
        if (isset($definitions[$schema_type]['fields'])) {
            foreach ($definitions[$schema_type]['fields'] as $key => $field) {
                if ($key !== 'custom_fields') { // Skip custom fields repeater
                    if (isset($field['type']) && $field['type'] === 'repeater' && isset($field['subfields'])) {
                        // Handle Repeater Fields (like FAQ questions)
                        $sub_keys = array_keys($field['subfields']);
                        $expected_keys[] = $key . ' (' . $field['label'] . ') [MUST be an array of objects with keys: ' . implode(', ', $sub_keys) . ']';
                    } else {
                        // Handle Standard Fields
                        $expected_keys[] = $key . ' (' . $field['label'] . ')';
                    }
                }
            }
        }

        // Prepare prompt based on schema type - SEO EXPERT PERSONA
        $prompt = "You are an SEO EXPERT specializing in Schema.org structured data and local SEO for businesses.\n";
        $prompt .= "Your goal is to generate the MOST COMPREHENSIVE and GOOGLE-COMPLIANT schema data possible.\n\n";
        
        $prompt .= "Analyze the following content and extract data for a '{$schema_type}' object.\n\n";
        
        $prompt .= "=== YOUR SEO EXPERTISE ===\n";
        $prompt .= "- You understand Google's Rich Results requirements\n";
        $prompt .= "- You know which fields impact Google My Business rankings\n";
        $prompt .= "- You prioritize fields that improve local pack visibility\n";
        $prompt .= "- You ensure NAP (Name, Address, Phone) consistency\n";
        $prompt .= "- You maximize rich snippet eligibility (stars, hours, etc.)\n\n";
        
        $prompt .= "=== DATA PRIORITY (USE IN THIS ORDER) ===\n";
        $prompt .= "1. WEBSITE DATA (highest priority) - Use the live page content and meta data provided\n";
        $prompt .= "2. GOOGLE MY BUSINESS - Use GMB URL to access your knowledge of this business listing\n";
        $prompt .= "3. YOUR KNOWLEDGE - For missing fields, use web search/knowledge about this specific business\n";
        $prompt .= "IMPORTANT: Website data is the source of truth. Only use GMB/web data to FILL GAPS, not override.\n\n";
        
        $prompt .= "=== BUSINESS CONTEXT ===\n";
        $prompt .= "- Industry: Early Childhood Education / Licensed Childcare\n";
        $prompt .= "- Service Type: Daycare, Preschool, Pre-K Programs\n";
        $prompt .= "- Location Type: Physical Business Locations in Georgia\n";
        $prompt .= "- Brand: Chroma Early Learning\n\n";
        
        $prompt .= "=== SCHEMA TYPE SPECIFIC INSTRUCTIONS ===\n";
        
        switch ($schema_type) {
            case 'JobPosting':
                $prompt .= "- Focus on: title, datePosted, validThrough, employmentType\n";
                $prompt .= "- EXTRACT SALARY: Look for baseSalary. Output as simple text (e.g., '50000 USD' or '$15/hour'). DO NOT return an object.\n";
                $prompt .= "- JOB LOCATION: Output as simple text (e.g. 'Atlanta, GA'). DO NOT return an object.\n";
                $prompt .= "- HIRING ORG: hiringOrganization should be 'Chroma Early Learning'. Output as text name.\n";
                $prompt .= "- DESCRIPTION: Include full job description HTML\n";
                break;

            case 'Event':
                $prompt .= "- Focus on: name, startDate, endDate, location_name, location_address, organizer\n";
                $prompt .= "- LOCATION: Return 'location_name' (text) and 'location_address' (text). DO NOT return a Place object.\n";
                $prompt .= "- OFFER: Include price, priceCurrency, availability\n";
                $prompt .= "- IMAGE: Must provide an image URL if available\n";
                break;

            case 'Article':
            case 'NewsArticle':
            case 'BlogPosting':
                $prompt .= "- Focus on: headline, image, datePublished, dateModified, author\n";
                $prompt .= "- HEADLINE: Limit to 110 characters max\n";
                $prompt .= "- AUTHOR: Must be a Person or Organization object\n";
                $prompt .= "- PUBLISHER: default to organization 'Chroma Early Learning'\n";
                break;

            case 'FAQPage':
                $prompt .= "- STRUCTURE: Return 'mainEntity' array of Question objects\n";
                $prompt .= "- QUESTION: 'name' property\n";
                $prompt .= "- ANSWER: 'acceptedAnswer.text' property\n";
                $prompt .= "- Do not hallucinate Q&A not in the text\n";
                break;
            
            case 'HowTo':
                $prompt .= "- Focus on: step-by-step instructions, totalTime, supply, tool\n";
                $prompt .= "- STEP: Must include 'name' and 'text' (or 'itemListElement' for structured steps)\n";
                $prompt .= "- IMAGES: Suggest images for each step if context implies them\n";
                break;

            case 'VideoObject':
                $prompt .= "- Focus on: name, description, uploadDate, duration, thumbnailUrl\n";
                $prompt .= "- THUMBNAIL: Required field\n";
                $prompt .= "- DURATION: ISO 8601 format (e.g. PT1M30S)\n";
                break;

            case 'Course':
                $prompt .= "- Focus on: name, description, provider, educationalLevel, coursePrerequisites, hasCourseInstance\n";
                $prompt .= "- PROVIDER: Organization 'Chroma Early Learning'\n";
                $prompt .= "- REQUIREMENTS: Extract age or grade requirements into 'coursePrerequisites'\n";
                $prompt .= "- INSTANCE: Include 'hasCourseInstance' with courseMode (Onsite) + courseWorkload (e.g. Full time, Part time)\n";
                $prompt .= "- TUITION: Map pricing to 'offers' array\n";
                break;

            case 'Product':
                $prompt .= "- Focus on: name, image, brand, offers, review, aggregateRating\n";
                $prompt .= "- OFFERS: price, priceCurrency, availability\n";
                $prompt .= "- BRAND: Brand object or text\n";
                break;

            case 'Service':
                $prompt .= "- Focus on: name, serviceType, provider, areaServed, hasOfferCatalog\n";
                $prompt .= "- PROVIDER: Organization 'Chroma Early Learning'\n";
                break;

            case 'Review':
                $prompt .= "- Focus on: itemReviewed, reviewRating, author, reviewBody\n";
                $prompt .= "- RATING: 1-5 scale\n";
                $prompt .= "- AUTHOR: Person object with name\n";
                break;

            case 'SpecialAnnouncement':
                $prompt .= "- Focus on: name, text, datePosted, expires, category\n";
                $prompt .= "- CATEGORY: e.g., 'https://schema.org/CovidTestingFacility' if applicable, or generic public health info\n";
                break;

            case 'Menu':
                $prompt .= "- Focus on: hasMenuSection, hasMenuItem\n";
                $prompt .= "- STRUCTURE: Hierarchical (Menu -> Section -> Item)\n";
                $prompt .= "- PRICES: price + priceCurrency\n";
                break;
                
            case 'ItemList':
            case 'CollectionPage':
                $prompt .= "- Focus on: itemListElement\n";
                $prompt .= "- ITEMS: ListItem with position and url\n";
                break;

            case 'LocalBusiness':
            case 'ChildCare':
            default:
                $prompt .= "- Focus on: geo coordinates (latitude/longitude) for local pack ranking\n";
                $prompt .= "- OUTPUT GEO AS FLAT FIELDS: 'geo_lat' and 'geo_lng' (do not return a GeoCoordinates object)\n";
                $prompt .= "- Add openingHoursSpecification for 'Open Now' badge\n";
                $prompt .= "- Include aggregateRating if reviews exist (critical for CTR)\n";
                $prompt .= "- Add hasCredential for trust signals (licenses, accreditations)\n";
                $prompt .= "- Use priceRange ($, $$, $$$) for filtering in maps\n";
                $prompt .= "- Include sameAs with all social profiles and GMB URL\n";
                $prompt .= "- Add amenityFeature for facility highlights\n";
                break;
        }
        $prompt .= "\n";

        $prompt .= "=== VALIDATION RULES ===\n";
        $prompt .= "- URLs must start with https://\n";
        $prompt .= "- Dates must be ISO 8601 format (YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS)\n";
        $prompt .= "- Telephone should include area code (e.g., 770-555-0123)\n";
        if ($schema_type === 'LocalBusiness' || $schema_type === 'ChildCare') {
             $prompt .= "- priceRange should be $, $$, or $$$\n";
             $prompt .= "- geo coordinates must be valid lat/lng decimals\n";
        }
        $prompt .= "- ratingValue must be between 1 and 5\n\n";
        
        $prompt .= "=== FOR MISSING FIELDS ===\n";
        $prompt .= "If you cannot find a field in the provided data:\n";
        $prompt .= "1. Check your knowledge of the GMB listing (if URL provided)\n";
        $prompt .= "2. Search the web for this specific business location\n";
        $prompt .= "3. If still not found, leave the field empty - DO NOT HALLUCINATE\n\n";
        
        $prompt .= "Return ONLY valid JSON. Do not include markdown formatting.\n";

        // Add custom instructions if provided
        if (!empty($context['instructions'])) {
            $prompt .= "IMPORTANT INSTRUCTIONS: " . $context['instructions'] . "\n";
        }

        if (!empty($expected_keys)) {
            $prompt .= "Map the extracted data to the following JSON keys ONLY:\n";
            $prompt .= "- " . implode("\n- ", $expected_keys) . "\n";
            $prompt .= "If a field cannot be found in website/GMB/web, leave it empty.\n";
        }


        // Fetch and format meta (including ACF fields)
        $meta = get_post_meta($post_id);
        $meta_context = "";
        if ($meta) {
            foreach ($meta as $key => $values) {
                if (strpos($key, '_') === 0)
                    continue; // Skip internal meta
                foreach ($values as $value) {
                    if (is_serialized($value)) {
                        $value = print_r(maybe_unserialize($value), true);
                    }
                    $meta_context .= "- {$key}: {$value}\n";
                }
            }
        }
        
        // Get ACF fields if available (higher priority)
        $acf_context = "";
        if (function_exists('get_fields')) {
            $acf_fields = get_fields($post_id);
            if ($acf_fields && is_array($acf_fields)) {
                $acf_context .= "ACF Custom Fields:\n";
                foreach ($acf_fields as $key => $value) {
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $acf_context .= "- {$key}: {$value}\n";
                }
            }
        }
        
        // Get featured image
        $featured_image = "";
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $featured_image = wp_get_attachment_url($thumbnail_id);
        }
        
        // Get site-wide settings
        // Get site-wide settings (Plugin Option > Theme Mod Fallback)
        $phone = get_option('chroma_seo_phone');
        if (empty($phone)) $phone = get_theme_mod('chroma_phone_number', '');

        $email = get_option('chroma_seo_email');
        if (empty($email)) $email = get_theme_mod('chroma_email', '');

        $site_context = "=== SITE-WIDE INFO ===\n";
        $site_context .= "Site Name: " . get_bloginfo('name') . "\n";
        $site_context .= "Tagline: " . get_bloginfo('description') . "\n";
        $site_context .= "Main Phone: " . $phone . "\n";
        $site_context .= "Main Email: " . $email . "\n";

        $prompt .= "\n=== WEBSITE DATA (HIGHEST PRIORITY) ===\n";
        $prompt .= "Title: " . $post->post_title . "\n";
        $prompt .= "URL: " . get_permalink($post_id) . "\n";
        $prompt .= "Excerpt: " . $post->post_excerpt . "\n";
        if ($featured_image) {
            $prompt .= "Featured Image: " . $featured_image . "\n";
        }
        $prompt .= "\n" . $site_context . "\n";
        if ($acf_context) {
            $prompt .= $acf_context . "\n";
        }
        $prompt .= "Post Meta Data:\n" . $meta_context . "\n";
        $prompt .= "Main Content:\n" . wp_trim_words(strip_tags($post->post_content), 500) . "\n";

        // Add Web Context (Live Page + GMB + Homepage)
        $web_context = $this->get_web_context($post_id);
        if ($web_context) {
            $prompt .= "\n=== EXTERNAL DATA (USE TO FILL GAPS) ===\n" . $web_context;
        }

        $response = $this->make_request([
            'messages' => [
                ['role' => 'system', 'content' => 'You are an SEO expert assistant. You extract structured data from text.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object']
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['choices'][0]['message']['content'])) {
            return new WP_Error('api_error', 'Invalid API response format');
        }

        $content = $response['choices'][0]['message']['content'];
        $json = json_decode($content, true);

        if (!$json) {
            return new WP_Error('json_error', 'Failed to parse AI response');
        }

        // Sanitize data against schema definition
        $json = $this->sanitize_schema_data($json, $schema_type);
        
        // Validate generated schema and provide feedback
        if (class_exists('Chroma_Schema_Validator')) {
            $test_schema = array_merge(
                ['@context' => 'https://schema.org', '@type' => $schema_type],
                $json
            );
            $validation = Chroma_Schema_Validator::validate_and_fix($test_schema, true);
            
            if (!empty($validation['warnings'])) {
                $json['_validation_warnings'] = $validation['warnings'];
            }
            if (!empty($validation['fixed'])) {
                $json['_auto_fixed'] = $validation['fixed'];
            }
            if (!empty($validation['errors'])) {
                $json['_validation_errors'] = $validation['errors'];
            }
        }

        return $json;
    }

    /**
     * AJAX: Generate General SEO Meta (Desc/Keywords)
     */
    public function ajax_generate_general_seo_meta()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error(['message' => 'Post not found']);
        }

        $prompt = "Generate SEO metadata for the following content.\n";
        $prompt .= "Return ONLY valid JSON with two keys:\n";
        $prompt .= "- description: (string) A compelling meta description, max 160 chars.\n";
        $prompt .= "- keywords: (string) 5-8 comma-separated keywords.\n\n";

        $prompt .= "Title: " . $post->post_title . "\n";
        $prompt .= "Excerpt: " . $post->post_excerpt . "\n";
        $prompt .= "Content: " . wp_trim_words(strip_tags($post->post_content), 500) . "\n"; // Limit content for cost/speed

        // Add Web Context (Live Page + Homepage + GMB)
        $web_context = $this->get_web_context($post_id);
        if ($web_context) {
            $prompt .= "\n\nWeb Context (Live Site/GMB Info):\n" . $web_context;
        }

        $response = $this->make_request([
            'messages' => [
                ['role' => 'system', 'content' => 'You are an SEO expert.'],
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $content = $data['choices'][0]['message']['content'] ?? '';

        // Extract JSON
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json) {
                wp_send_json_success($json);
            }
        }

        // Fallback if JSON parsing fails
        wp_send_json_error(['message' => 'Failed to parse AI response. Raw: ' . substr($content, 0, 100)]);
    }

    /**
     * AJAX: Generate LLM Targeting Data
     */
    public function ajax_generate_llm_targeting()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        if (!$post_id) {
            wp_send_json_error(['message' => 'Missing Post ID']);
        }

        $result = $this->generate_llm_targeting_data($post_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        if (isset($_POST['auto_save']) && $_POST['auto_save'] === 'true') {
            if (!empty($result['primary_intent'])) {
                update_post_meta($post_id, 'seo_llm_primary_intent', sanitize_text_field($result['primary_intent']));
            }
            if (!empty($result['target_queries']) && is_array($result['target_queries'])) {
                $queries = array_map('sanitize_text_field', $result['target_queries']);
                update_post_meta($post_id, 'seo_llm_target_queries', $queries);
            }
            if (!empty($result['key_differentiators']) && is_array($result['key_differentiators'])) {
                $diffs = array_map('sanitize_text_field', $result['key_differentiators']);
                update_post_meta($post_id, 'seo_llm_key_differentiators', $diffs);
            }
            $result['message'] = 'LLM Targeting data generated and saved.';
            $result['saved'] = true;
        }

        wp_send_json_success($result);
    }

    /**
     * Generate LLM Targeting Data (Public API)
     * 
     * @param int $post_id
     * @return array|WP_Error
     */
    public function generate_llm_targeting_data($post_id)
    {
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post not found');
        }

        $prompt = "Analyze the following content and generate LLM optimization data.\n";
        $prompt .= "Return ONLY valid JSON with the following keys:\n";
        $prompt .= "- primary_intent: (string) The main user intent (e.g., 'informational', 'commercial', 'transactional').\n";
        $prompt .= "- target_queries: (array of strings) 3-5 natural language questions users might ask to find this.\n";
        $prompt .= "- key_differentiators: (array of strings) 3-5 unique selling points or key facts.\n";


        // Fetch and format meta
        $meta = get_post_meta($post_id);
        $meta_context = "";
        if ($meta) {
            foreach ($meta as $key => $values) {
                if (strpos($key, '_') === 0)
                    continue; // Skip internal meta
                foreach ($values as $value) {
                    if (is_serialized($value)) {
                        $value = print_r(maybe_unserialize($value), true);
                    }
                    $meta_context .= "- {$key}: {$value}\n";
                }
            }
        }

        $prompt .= "\nContent Context:\n";
        $prompt .= "Title: " . $post->post_title . "\n";
        $prompt .= "Excerpt: " . $post->post_excerpt . "\n";
        $prompt .= "Meta Data:\n" . $meta_context . "\n";
        $prompt .= "Main Content:\n" . strip_tags($post->post_content);

        // Add Web Context (Live Page + Homepage)
        $web_context = $this->get_web_context($post_id);
        if ($web_context) {
            $prompt .= "\n\nWeb Context (Live Site Info):\n" . $web_context;
        }

        $response = $this->make_request([
            'messages' => [
                ['role' => 'system', 'content' => 'You are an SEO expert specializing in LLM optimization (GEO).'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object']
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $content = $response['choices'][0]['message']['content'];
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Invalid JSON response from AI');
        }

        return $json;
    }

    /**
     * AJAX: Translate Text
     */
    public function ajax_translate_text()
    {
        check_ajax_referer('chroma_seo_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $text = isset($_POST['text']) ? wp_unslash($_POST['text']) : '';
        $target_lang = sanitize_text_field($_POST['target_lang'] ?? 'es');
        $context = sanitize_textarea_field($_POST['context'] ?? '');

        if (!$text) {
            wp_send_json_error(['message' => 'No text provided']);
        }

        $translation = $this->translate_text($text, $target_lang, $context);

        if (is_wp_error($translation)) {
            wp_send_json_error(['message' => $translation->get_error_message()]);
        }

        wp_send_json_success(['translation' => $translation]);
    }

    /**
     * Translate Text (Public API)
     * 
     * @param string $text
     * @param string $target_lang
     * @param string $context
     * @return string|WP_Error
     */
    public function translate_text($text, $target_lang = 'es', $context = '')
    {
        // Simple cache key
        $cache_key = 'trans_' . md5($text . $target_lang . $context);
        $cached = $this->get_cached_response($cache_key);
        if ($cached) return $cached;

        $prompt = "You are a professional translator for a high-end childcare brand.\n";
        $prompt .= "Translate the following content to " . ($target_lang === 'es' ? 'Spanish (Latin American)' : $target_lang) . ".\n";
        $prompt .= "Maintain HTML tags exactly if present.\n";
        $prompt .= "Tone: Warm, professional, educational, and welcoming.\n";
        
        if ($context) {
            $prompt .= "Context: " . $context . "\n";
        }
        
        $prompt .= "\nContent to Translate:\n" . $text;

        $response = $this->make_request([
            'messages' => [
                ['role' => 'system', 'content' => 'You are a professional translator.'],
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $content = $response['choices'][0]['message']['content'] ?? '';
        
        // Remove markdown code blocks if AI wraps output
        $content = preg_replace('/^```html\s*|```$/', '', trim($content));
        
        if ($content) {
            $this->set_cached_response($cache_key, $content, WEEK_IN_SECONDS);
        }

        return $content;
    }

    /**
     * Make Request to OpenAI
     */
    public function make_request($data)
    {
        // Lazy-load settings fresh from database to ensure latest values are used
        $api_key = get_option('chroma_openai_api_key', '');
        $model = get_option('chroma_llm_model', 'gpt-4o-mini');
        $base_url = get_option('chroma_llm_base_url', 'https://api.openai.com/v1');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'No API Key configured. Please save your key first.');
        }

        // Use configured base URL or default
        $url = ($base_url ?: 'https://api.openai.com/v1') . '/chat/completions';

        $body = array_merge([
            'model' => $model ?: 'gpt-4o-mini',
            'temperature' => 0.7,
        ], $data);

        $args = [
            'body' => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'timeout' => 120
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($code !== 200) {
            $msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API Error';
            return new WP_Error('api_error', $msg);
        }

        return $data;
    }

    /**
     * Helper: Fetch Web Context (Live Page + Homepage)
     */
    private function get_web_context($post_id)
    {
        $context = "";

        // 0. Check for GMB URL
        $gmb = get_post_meta($post_id, 'location_gmb_url', true);
        if ($gmb) {
            $context .= "Google My Business URL: " . $gmb . "\n";
            $context .= "Instruction: This is the official GMB listing. Use your internal knowledge of this business (reviews, location details) to enhance the content.\n\n";
        }

        // 1. Try to get the live page content (if published)
        $permalink = get_permalink($post_id);
        if ($permalink && get_post_status($post_id) === 'publish') {
            $response = wp_remote_get($permalink, ['timeout' => 5, 'sslverify' => false]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                // Extract main content or body text
                $text = strip_tags($body);
                // Limit length to avoid token limits (e.g., first 2000 chars)
                $context .= "Live Page Content:\n" . substr(preg_replace('/\s+/', ' ', $text), 0, 2000) . "\n\n";
            }
        }

        // 2. Always fetch Homepage for global info (Address, Phone, etc.)
        $home_url = home_url('/');
        // Avoid fetching homepage if we just fetched it as the permalink
        if ($permalink !== $home_url) {
            $response = wp_remote_get($home_url, ['timeout' => 5, 'sslverify' => false]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                $text = strip_tags($body);
                $context .= "Homepage/Organization Info:\n" . substr(preg_replace('/\s+/', ' ', $text), 0, 1500) . "\n";
            }
        }

        return $context;
    }

    /**
     * Sanitize and Validate Schema Data against Definitions
     * Prevents type mismatches (e.g. Array for Textarea) which cause crashes.
     */
    private function sanitize_schema_data($data, $schema_type)
    {
        $definitions = Chroma_Schema_Types::get_definitions();
        if (!isset($definitions[$schema_type]['fields'])) {
            return $data; // No definition found, return as is
        }

        $fields = $definitions[$schema_type]['fields'];

        foreach ($data as $key => $value) {
            if (!isset($fields[$key])) {
                continue; // Unknown field, ignore or keep
            }

            $field_def = $fields[$key];
            $type = isset($field_def['type']) ? $field_def['type'] : 'text';

            // Critical Fix: mismatched Arrays for Text/Textarea fields
            if (($type === 'text' || $type === 'textarea' || $type === 'image') && is_array($value)) {
                // LLM returned a list (e.g. for sameAs), but schema expects a string
                $data[$key] = implode(', ', $value);
            }

            // Critical Fix: mismatched String for Array/Repeater fields
            // (Less common, but possible)
            if ($type === 'repeater' && !is_array($value) && !empty($value)) {
                // If it returns a single object instead of array of objects, wrap it?
                // Or if it returns a string, ignore it? 
                // For now, let's just make sure it's an array if not empty
                $data[$key] = [$value];
            }
        }

        return $data;
    }

    // =========================================================================
    // PHASE 5: CORE INFRASTRUCTURE - Caching, Rate Limiting, Logging
    // =========================================================================

    /**
     * Get cached LLM response
     */
    private function get_cached_response($cache_key) {
        return get_transient('chroma_llm_cache_' . md5($cache_key));
    }

    /**
     * Set cached LLM response
     */
    private function set_cached_response($cache_key, $data, $expiry = DAY_IN_SECONDS) {
        set_transient('chroma_llm_cache_' . md5($cache_key), $data, $expiry);
    }
    
    /**
     * Clear cache for a specific post
     */
    public function clear_cache_for_post($post_id) {
        delete_transient('chroma_llm_cache_' . md5('schema_' . $post_id));
        delete_transient('chroma_llm_cache_' . md5('seo_' . $post_id));
    }

    /**
     * Check rate limit
     */
    private function check_rate_limit() {
        $limit = get_option('chroma_llm_rate_limit', 60); // requests per minute
        $count = get_transient('chroma_llm_rate_count') ?: 0;
        return $count < $limit;
    }

    /**
     * Record rate limit usage
     */
    private function record_rate_limit() {
        $count = get_transient('chroma_llm_rate_count') ?: 0;
        set_transient('chroma_llm_rate_count', $count + 1, MINUTE_IN_SECONDS);
    }
    
    /**
     * Generate Amenities Data (Tier 5 - BB)
     * Uses LLM to extract safety amenities from post content
     */
    public function generate_amenities_data($post_id)
    {
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post not found');
        }

        $amenities_list = [
            "Keypad Access", 
            "AI Monitored Cameras", 
            "Privacy Fenced Playgrounds", 
            "Zono Sanitizing Machine", 
            "Biometric Entry", 
            "Clean Air HVAC", 
            "Storm Shelter / Safe Room", 
            "Automatic Locking Doors",
            "CPR Certified Staff",
            "Fingerprint Check-in"
        ];

        $prompt = "Analyze the following childcare location content and identify which safety/facility amenities are explicitly mentioned or clearly implied.\n";
        $prompt .= "Return ONLY a JSON array of strings containing the matches from this specific list:\n";
        $prompt .= "- " . implode("\n- ", $amenities_list) . "\n\n";
        $prompt .= "If none are found, return an empty array []. Do not invent new terms.\n\n";

        $prompt .= "Content:\n" . wp_trim_words(strip_tags($post->post_content), 1000) . "\n";
        
        $meta = get_post_meta($post_id);
        if($meta) {
             $prompt .= "\nMeta Data keywords:\n";
             foreach($meta as $k => $v) {
                 if(strpos($k, '_')!==0) $prompt .= "$k: " . implode(', ', $v) . "\n";
             }
        }

        $response = $this->make_request([
            'messages' => [
                ['role' => 'system', 'content' => 'You are a data extractor. Return JSON array only.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'response_format' => ['type' => 'json_object']
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $content = $response['choices'][0]['message']['content'];
        $json = json_decode($content, true);

        // API often returns { "amenities": [...] } or just [...]
        if (isset($json['amenities']) && is_array($json['amenities'])) {
            return $json['amenities'];
        }
        if (is_array($json)) {
             // Check if it's a list of strings
             if (isset($json[0]) && is_string($json[0])) return $json;
             // Sometimes returns { "matches": [...] }
             foreach($json as $key => $val) {
                 if(is_array($val)) return $val;
             }
        }
        
        return [];
    }

    /**
     * Log LLM request for debugging
     */
    private function log_request($status, $info, $tokens = 0, $duration = 0) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) return;
        
        error_log(sprintf(
            '[Chroma LLM] %s | Info: %s | Tokens: %d | Duration: %.2fs',
            $status,
            is_string($info) ? substr($info, 0, 100) : json_encode($info),
            $tokens,
            $duration
        ));
    }

    /**
     * Track token usage for cost monitoring
     */
    private function track_usage($tokens, $post_id = 0) {
        $month_key = 'chroma_llm_usage_' . date('Y-m');
        $usage = get_option($month_key, [
            'total_tokens' => 0, 
            'requests' => 0,
            'by_post_type' => [],
            'by_day' => []
        ]);
        
        $usage['total_tokens'] += $tokens;
        $usage['requests'] += 1;
        
        // Track by post type
        if ($post_id) {
            $post_type = get_post_type($post_id) ?: 'unknown';
            $usage['by_post_type'][$post_type] = ($usage['by_post_type'][$post_type] ?? 0) + $tokens;
        }
        
        // Track by day
        $day = date('Y-m-d');
        $usage['by_day'][$day] = ($usage['by_day'][$day] ?? 0) + $tokens;
        
        update_option($month_key, $usage);
    }
    
    /**
     * Get usage statistics
     */
    public static function get_usage_stats($month = null) {
        $month = $month ?: date('Y-m');
        return get_option('chroma_llm_usage_' . $month, [
            'total_tokens' => 0,
            'requests' => 0,
            'by_post_type' => [],
            'by_day' => []
        ]);
    }
    
    /**
     * Calculate estimated cost based on token usage
     */
    public static function estimate_cost($tokens, $model = 'gpt-4o-mini') {
        // Pricing per 1M tokens (as of Dec 2024)
        $pricing = [
            'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
            'gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00],
            'gpt-3.5-turbo' => ['input' => 0.50, 'output' => 1.50],
        ];
        
        $rate = $pricing[$model] ?? $pricing['gpt-4o-mini'];
        $avg_rate = ($rate['input'] + $rate['output']) / 2;
        
        return ($tokens / 1000000) * $avg_rate;
    }

    /**
     * Make request with retry logic
     */
    private function make_request_with_retry($data, $max_retries = 3) {
        $attempt = 0;
        $last_error = null;
        
        while ($attempt < $max_retries) {
            $attempt++;
            $start_time = microtime(true);
            
            // Check rate limit
            if (!$this->check_rate_limit()) {
                $this->log_request('[RATE LIMITED]', 'Waiting...', 0, 0);
                sleep(2);
                continue;
            }
            
            $response = $this->make_request_internal($data);
            $duration = microtime(true) - $start_time;
            
            if (!is_wp_error($response)) {
                // Success
                $this->record_rate_limit();
                $tokens = $response['usage']['total_tokens'] ?? 0;
                $this->log_request('[SUCCESS]', 'Attempt ' . $attempt, $tokens, $duration);
                $this->track_usage($tokens);
                return $response;
            }
            
            $last_error = $response;
            $error_code = $response->get_error_code();
            $error_msg = $response->get_error_message();
            
            $this->log_request('[ERROR]', "Attempt $attempt: $error_msg", 0, $duration);
            
            // Don't retry on auth errors
            if (strpos($error_msg, 'Invalid API Key') !== false || $error_code === 'no_api_key') {
                return $response;
            }
            
            if ($attempt < $max_retries) {
                $wait = pow(2, $attempt); // Exponential backoff
                sleep($wait);
            }
        }
        
        return $last_error ?: new WP_Error('max_retries', 'Request failed after ' . $max_retries . ' attempts');
    }
    
    /**
     * Internal make request (no retry, no logging)
     */
    private function make_request_internal($data) {
        $api_key = get_option('chroma_openai_api_key', '');
        $model = get_option('chroma_llm_model', 'gpt-4o-mini');
        $base_url = get_option('chroma_llm_base_url', 'https://api.openai.com/v1');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', 'No API Key configured.');
        }

        $url = ($base_url ?: 'https://api.openai.com/v1') . '/chat/completions';

        $body = array_merge([
            'model' => $model ?: 'gpt-4o-mini',
            'temperature' => 0.7,
        ], $data);

        $response = wp_remote_post($url, [
            'body' => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'timeout' => 60
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($code === 429) {
            return new WP_Error('rate_limited', 'API rate limited. Try again later.');
        }
        
        if ($code >= 500) {
            return new WP_Error('server_error', 'API server error. Code: ' . $code);
        }

        if ($code !== 200) {
            $msg = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'Unknown API Error';
            return new WP_Error('api_error', $msg);
        }

        return $decoded;
    }
    /**
     * Fix Schema Errors with AI
     * 
     * @param string $raw_schema JSON string
     * @param array $errors List of validation errors
     * @param int $retry_count Internal retry counter
     * @return array|WP_Error Fixed schema result array or error
     */
    public function fix_schema_with_ai($raw_schema, $errors, $retry_count = 0)
    {
        $prompt = "You are an expert JSON-LD Schema validator and fixer.\n";
        $prompt .= "Your task is to FIX the following JSON-LD schema which has failed validation.\n\n";
        
        $prompt .= "=== VALIDATION ERRORS ===\n";
        foreach ($errors as $error) {
            $prompt .= "- $error\n";
        }
        $prompt .= "\n";
        
        $prompt .= "=== INSTRUCTIONS ===\n";
        $prompt .= "1. **SINGLE OUTPUT**: You must output EXACTLY ONE valid JSON-LD structure wrapped in a SINGLE `<script type=\"application/ld+json\">` tag. Do NOT output multiple script tags or separate JSON blocks.\n";
        $prompt .= "2. **USE @GRAPH**: Wrap all entities in a root `@graph` array.\n";
        $prompt .= "3. **AGGRESSIVE DEDUPLICATION**: The input contains MANY duplicate schemas (e.g. 5 Organizations, 5 Breadcrumbs). You must FILTER these out.\n";
        $prompt .= "   - Create ONLY ONE `Organization` node (consolidate properties).\n";
        $prompt .= "   - Create ONLY ONE `BreadcrumbList` node.\n";
        $prompt .= "   - Create ONLY ONE `FAQPage` node (merge questions).\n";
        $prompt .= "   - Create ONLY ONE `WebPage` node.\n";
        $prompt .= "4. **CONSOLIDATE**: If multiple similar entities exist, merge them into the richest possible version. Discard the redundant ones.\n";
        $prompt .= "5. **PRESERVE DATA**: Do NOT remove valid existing properties unless they are clearly erroneous. Attempt to keep all valid data from the original schemas.\n";
        $prompt .= "6. **FIX ERRORS**: Fix the validation errors listed above.\n";
        $prompt .= "7. **CLEAN UP**: Remove empty properties or duplicate values in arrays.\n";
        $prompt .= "8. **FINAL FORMAT**: Return ONLY valid JSON. If multiple items exist, they MUST be inside a root `@graph` array.\n";
        $prompt .= "9. **CRITICAL**: Do NOT output multiple separate JSON objects (e.g. `}{`). Output exactly one JSON object.\n\n";
        
        // NEW: Tightened validation rules (Feature 13)
        $prompt .= "=== VALIDATION RULES (CRITICAL) ===\n";
        $prompt .= "10. **REQUIRED FIELDS BY TYPE**:\n";
        $prompt .= "    - Person: must have 'name'\n";
        $prompt .= "    - Organization/LocalBusiness: must have 'name', should have 'address'\n";
        $prompt .= "    - Event: must have 'name', 'startDate', 'location'\n";
        $prompt .= "    - BreadcrumbList: must have 'itemListElement' array with 'position' + ('name' OR 'item' URL)\n";
        $prompt .= "    - FAQPage: must have 'mainEntity' array of Questions with 'acceptedAnswer'\n";
        $prompt .= "11. **NO EMPTY STRINGS**: Never output empty strings for URLs (item, url, image). Either provide valid URL or OMIT the field entirely.\n";
        $prompt .= "12. **NO HTML IN TEXT**: Strip all HTML tags from name, description, text fields. Plain text only.\n";
        $prompt .= "13. **VALID URLs ONLY**: All URL fields must be complete (https://...). No relative paths, no empty strings.\n";
        $prompt .= "14. **ISO 8601 DATES**: Use format YYYY-MM-DDTHH:MM:SS or YYYY-MM-DD for dates.\n";
        $prompt .= "15. **POSITION SEQUENCE**: BreadcrumbList positions must be sequential integers starting from 1.\n\n";
        
        $prompt .= "=== BROKEN SCHEMA ===\n";
        $prompt .= $raw_schema;

        // Determine efficient max_tokens based on model
        $model = get_option('chroma_llm_model', 'gpt-4o-mini');
        $max_tokens = 4096; // Standard limit for GPT-4o / Turbo / Legacy
        
        // High-context models (Gemini, Flash, Mini)
        if (strpos($model, 'mini') !== false || strpos($model, 'gemini') !== false || strpos($model, 'flash') !== false) {
            $max_tokens = 16000; // Allow large output for efficient models
        }

        $request_args = [
            'messages' => [
                ['role' => 'system', 'content' => 'You are a JSON repair expert. Output valid JSON only. Follow ALL validation rules exactly.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $max_tokens
        ];

        // Add JSON mode for compatible models
        if (strpos($model, 'gpt') !== false || strpos($model, 'gemini') !== false) {
             $request_args['response_format'] = ['type' => 'json_object'];
        }

        $response = $this->make_request($request_args);

        if (is_wp_error($response)) {
            return $response;
        }

        $content = $response['choices'][0]['message']['content'];
        
        // Clean potential wrappers
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        // Clean <script> tags
        if (preg_match('/<script[^>]*>(.*?)<\/script>/s', $content, $matches)) {
            $content = $matches[1];
        }
        
        $content = trim($content);

        // Attempt normalization if valid JSON is not found immediately
        json_decode($content);
        if (json_last_error() !== JSON_ERROR_NONE) {
             // Handle "concatenated JSON" error (e.g. {...} {...})
             // Replace "}{" with "},{" and wrap in brackets
             $normalized = preg_replace('/}\s*{/', '},{', $content);
             $normalized = '[' . $normalized . ']';
             
             // Check if that fixed it
             $check = json_decode($normalized, true);
             if ($check) {
                 // Convert list to @graph format if needed
                 if (count($check) > 1 || !isset($check['@graph'])) {
                     $content = json_encode(['@context' => 'https://schema.org', '@graph' => $check]);
                 } else {
                     $content = json_encode($check[0]);
                 }
             }
        }
        
        // Validate final JSON syntax
        $decoded = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'AI returned invalid JSON: ' . json_last_error_msg() . '. Response preview: ' . substr($content, 0, 200));
        }

        // NEW: Run through schema validator (Feature 12)
        $validation = Chroma_Schema_Validator::validate_json_ld($content);

        if (!$validation['valid']) {
            chroma_debug_log('[Chroma SEO] AI Fix generated invalid schema (attempt ' . ($retry_count + 1) . '): ' . print_r($validation['errors'], true));
            
            // Retry up to 2 times with validation errors in prompt
            if ($retry_count < 2) {
                $combined_errors = array_merge($errors, $validation['errors']);
                return $this->fix_schema_with_ai($content, $combined_errors, $retry_count + 1);
            }
            
            // Max retries reached, return with warning
            return [
                'schema' => $content,
                'valid' => false,
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
                'ai_generated' => true,
                'retry_count' => $retry_count
            ];
        }

        // Success!
        return [
            'schema' => $content,
            'valid' => true,
            'warnings' => $validation['warnings'],
            'ai_generated' => true,
            'retry_count' => $retry_count
        ];
    }



}



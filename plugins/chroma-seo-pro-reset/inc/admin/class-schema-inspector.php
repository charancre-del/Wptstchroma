<?php
/**
 * Schema Inspector
 * Adds an admin bar tool to validat page schema and suggest AI fixes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Schema_Inspector
{
    public function __construct()
    {
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 100);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_chroma_validate_page_schema', [$this, 'ajax_validate_schema']);
        add_action('wp_ajax_chroma_fix_schema_with_ai', [$this, 'ajax_fix_schema']);
    }

    /**
     * Add "Validate Schema" to Admin Bar
     */
    public function add_admin_bar_menu($wp_admin_bar)
    {
        if (!current_user_can('edit_posts')) {
            return;
        }

        // Only show on frontend or specific admin pages if needed
        if (is_admin()) {
             // Optional: allow it in admin if needed, but primarily for frontend
        }

        $wp_admin_bar->add_node([
            'id'    => 'chroma-validate-schema',
            'title' => '<span class="ab-icon dashicons dashicons-yes-alt"></span> Validate Schema',
            'href'  => '#',
            'meta'  => [
                'class' => 'chroma-inspector-trigger',
                'title' => 'Validate Schema on this page'
            ]
        ]);
    }

    /**
     * Enqueue Inspector JS/CSS
     */
    public function enqueue_scripts()
    {
        if (!current_user_can('edit_posts')) {
            return;
        }

        // 1. Register Dummy Handle for Inline Data
        wp_register_script('chroma-schema-inspector-data', false);
        wp_enqueue_script('chroma-schema-inspector-data');
        
        // 2. Add Inline Data
        wp_add_inline_script('chroma-schema-inspector-data', sprintf(
            'const ChromaInspector = %s;',
            json_encode([
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('chroma_schema_inspector_nonce')
            ])
        ));

        // 3. Enqueue Core JS (Dependent on jquery AND our data handle)
        $js_path = CHROMA_SEO_PATH . 'assets/js/schema-inspector.js';
        if (file_exists($js_path)) {
            wp_enqueue_script(
                'chroma-schema-inspector-core', 
                CHROMA_SEO_URL . 'assets/js/schema-inspector.js', 
                ['jquery', 'chroma-schema-inspector-data'], 
                '1.0.1', 
                true
            );
        }
        
        // Add minimal CSS for the modal
        wp_add_inline_style('chroma-schema-inspector-core', '
            #chroma-schema-modal { display: none; position: fixed; z-index: 999999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(2px); }
            #chroma-schema-modal .chroma-modal-content { background-color: #fefefe; margin: 5% auto; padding: 0; border: 1px solid #888; width: 80%; max-width: 900px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
            #chroma-schema-modal-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; border-radius: 8px 8px 0 0; }
            #chroma-schema-modal-header h2 { margin: 0; font-size: 18px; color: #333; }
            #chroma-schema-close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 1; }
            #chroma-schema-close:hover { color: #000; }
            #chroma-schema-modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
            .chroma-schema-item { border: 1px solid #ddd; margin-bottom: 15px; border-radius: 6px; overflow: hidden; }
            .chroma-schema-header { padding: 10px 15px; background: #f1f1f1; display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
            .chroma-schema-header.valid { border-left: 5px solid #46b450; }
            .chroma-schema-header.invalid { border-left: 5px solid #dc3232; }
            .chroma-schema-header.warning { border-left: 5px solid #ffb900; }
            .chroma-schema-details { padding: 15px; display: none; border-top: 1px solid #eee; }
            .chroma-error-list { color: #dc3232; margin: 0 0 10px; padding-left: 20px; }
            .chroma-warning-list { color: #d69e00; margin: 0 0 10px; padding-left: 20px; }
            .chroma-json-pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; line-height: 1.5; white-space: pre-wrap; margin-top: 10px; }
            .chroma-fix-btn { background: #2271b1; color: #fff; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 13px; }
            .chroma-fix-btn:hover { background: #135e96; }
            .chroma-fix-btn:disabled { opacity: 0.6; cursor: wait; }
            .chroma-copy-btn { background: #f0f0f1; border: 1px solid #ccc; padding: 4px 8px; border-radius: 3px; font-size: 12px; cursor: pointer; margin-top: 5px; }
            .chroma-spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid rgba(0,0,0,.1); border-radius: 50%; border-top-color: #2271b1; animation: spin 1s ease-in-out infinite; vertical-align: middle; margin-right: 8px; }
            @keyframes spin { to { transform: rotate(360deg); } }
        ');
    }

    /**
     * AJAX: Validate Schema
     */
    public function ajax_validate_schema()
    {
        check_ajax_referer('chroma_schema_inspector_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $json_strings = $_POST['schemas'] ?? [];
        if (empty($json_strings) || !is_array($json_strings)) {
            wp_send_json_error(['message' => 'No schema data found to validate.']);
        }

        $results = [];

        foreach ($json_strings as $index => $json_str) {
            $json_str = wp_unslash($json_str);
            $validation = Chroma_Schema_Validator::validate_json_ld($json_str);
            
            // Format result for frontend
            $results[] = [
                'index' => $index,
                'valid' => $validation['valid'],
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
                'parsed' => $validation['parsed'], // Send back parsed object for display
                'raw' => $json_str // Keep raw for copying
            ];
        }

        wp_send_json_success(['results' => $results]);
    }

    /**
     * AJAX: Fix Schema with AI
     */
    public function ajax_fix_schema()
    {
        check_ajax_referer('chroma_schema_inspector_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        // Support both single schema and multiple schemas
        $schemas_array = $_POST['schemas'] ?? null;
        $single_schema = $_POST['schema'] ?? null;
        $errors = $_POST['errors'] ?? [];

        if (empty($schemas_array) && empty($single_schema)) {
            wp_send_json_error(['message' => 'No schema provided']);
        }

        global $chroma_llm_client;
        if (!$chroma_llm_client) {
            wp_send_json_error(['message' => 'LLM Client not initialized']);
        }

        if (!method_exists($chroma_llm_client, 'fix_schema_with_ai')) {
             wp_send_json_error(['message' => 'AI Fix method not implemented yet']);
        }

        // Handle multiple schemas
        if ($schemas_array && is_array($schemas_array)) {
            $fixed_schemas = [];
            foreach ($schemas_array as $raw_schema) {
                $raw_schema = wp_unslash($raw_schema);
                $fixed = $chroma_llm_client->fix_schema_with_ai($raw_schema, $errors);
                
                if (is_wp_error($fixed)) {
                    wp_send_json_error(['message' => 'Failed to fix schema: ' . $fixed->get_error_message()]);
                }
                $fixed_schemas[] = $fixed;
            }
            wp_send_json_success(['fixed_schemas' => $fixed_schemas]);
        } else {
            // Handle single schema (backward compatibility)
            $raw_schema = wp_unslash($single_schema);
            $fixed_schema = $chroma_llm_client->fix_schema_with_ai($raw_schema, $errors);

            if (is_wp_error($fixed_schema)) {
                wp_send_json_error(['message' => $fixed_schema->get_error_message()]);
            }

            wp_send_json_success(['fixed_schema' => $fixed_schema]);
        }
    }
}

new Chroma_Schema_Inspector();



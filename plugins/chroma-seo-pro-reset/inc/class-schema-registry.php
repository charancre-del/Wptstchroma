<?php
/**
 * Centralized Schema Registry
 * 
 * All schemas should be registered through this class instead of directly echoing.
 * This enables:
 * - Deduplication by @type and @id
 * - Filtering of invalid schema types
 * - Single output point for all schemas
 * - Debug visibility for admins
 *
 * @package Chroma_SEO_Pro
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Remove internal validator/API metadata before schema is exposed publicly.
 *
 * Schema.org properties do not use underscore-prefixed keys; those keys are
 * reserved here for admin/API diagnostics and should never be emitted as JSON-LD.
 *
 * @param mixed $value
 * @return mixed
 */
if (!function_exists('chroma_schema_strip_internal_keys')) {
    function chroma_schema_strip_internal_keys($value) {
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (!is_array($value)) {
            return $value;
        }

        $clean = [];
        foreach ($value as $key => $item) {
            if (is_string($key) && $key !== '' && $key[0] === '_') {
                continue;
            }

            $clean[$key] = chroma_schema_strip_internal_keys($item);
        }

        return $clean;
    }
}

/**
 * Normalize Chroma-owned absolute URLs in public JSON-LD to the active site URL.
 *
 * Stored/API-loaded schema can legitimately contain production URLs when data is
 * copied between environments. Keep storage untouched, but avoid leaking the
 * wrong host when the site renders from staging or another migrated domain.
 *
 * @param mixed $value
 * @return mixed
 */
if (!function_exists('chroma_schema_normalize_site_urls_for_output')) {
    function chroma_schema_normalize_site_urls_for_output($value) {
        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = chroma_schema_normalize_site_urls_for_output($item);
            }
            return $normalized;
        }

        if (!is_string($value) || $value === '') {
            return $value;
        }

        $home = home_url('/');
        $home_host = strtolower((string) wp_parse_url($home, PHP_URL_HOST));
        if ($home_host === '' || in_array($home_host, ['chromaela.com', 'www.chromaela.com'], true)) {
            return $value;
        }

        if (!preg_match('~^https?://(?:www\.)?chromaela\.com(?=/|$|[?#])~i', $value)) {
            return $value;
        }

        return preg_replace(
            '~^https?://(?:www\.)?chromaela\.com~i',
            rtrim($home, '/'),
            $value
        );
    }
}

if (!function_exists('chroma_schema_array_is_list')) {
    function chroma_schema_array_is_list(array $value) {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}

if (!function_exists('chroma_schema_normalize_type_name')) {
    function chroma_schema_normalize_type_name($type) {
        $type = trim((string) $type);
        if ($type === '') {
            return '';
        }

        if (strpos($type, 'schema.org/') !== false) {
            $parts = explode('/', rtrim($type, '/'));
            $type = (string) end($parts);
        }

        return strtolower($type);
    }
}

/**
 * Extract top-level schema types from stored builder rows, raw JSON-LD, or @graph.
 *
 * @param mixed $schema_value
 * @return array
 */
if (!function_exists('chroma_schema_extract_top_level_types')) {
    function chroma_schema_extract_top_level_types($schema_value) {
        if (is_object($schema_value)) {
            $schema_value = (array) $schema_value;
        }

        if (!is_array($schema_value)) {
            return [];
        }

        $types = [];

        if (chroma_schema_array_is_list($schema_value)) {
            foreach ($schema_value as $item) {
                $types = array_merge($types, chroma_schema_extract_top_level_types($item));
            }
            return array_values(array_unique(array_filter($types)));
        }

        foreach (['type', '@type'] as $type_key) {
            if (empty($schema_value[$type_key])) {
                continue;
            }

            $raw_types = is_array($schema_value[$type_key]) ? $schema_value[$type_key] : [$schema_value[$type_key]];
            foreach ($raw_types as $type) {
                $normalized = chroma_schema_normalize_type_name($type);
                if ($normalized !== '') {
                    $types[] = $normalized;
                }
            }
        }

        if (!empty($schema_value['@graph']) && is_array($schema_value['@graph'])) {
            $types = array_merge($types, chroma_schema_extract_top_level_types($schema_value['@graph']));
        }

        return array_values(array_unique(array_filter($types)));
    }
}

/**
 * Determine whether stored schema already provides a replacement for a fallback.
 *
 * @param mixed $schema_value
 * @param array $target_types
 * @return bool
 */
if (!function_exists('chroma_schema_store_has_any_type')) {
    function chroma_schema_store_has_any_type($schema_value, array $target_types) {
        $stored_types = chroma_schema_extract_top_level_types($schema_value);
        if (empty($stored_types)) {
            return false;
        }

        $targets = [];
        foreach ($target_types as $target_type) {
            $normalized = chroma_schema_normalize_type_name($target_type);
            if ($normalized !== '') {
                $targets[] = $normalized;
            }
        }

        return (bool) array_intersect($stored_types, $targets);
    }
}

class Chroma_Schema_Registry
{
    /**
     * Collected schemas for this page
     * @var array
     */
    private static $schemas = [];

    /**
     * Blocked schemas (for debugging)
     * @var array
     */
    private static $blocked = [];

    /**
     * Track which types have been registered (for deduplication)
     * @var array
     */
    private static $registered_types = [];

    /**
     * Track schema IDs to prevent duplicates
     * @var array
     */
    private static $registered_ids = [];

    /**
     * Has output already happened?
     * @var bool
     */
    private static $output_done = false;

    /**
     * Whether the current wp_head output buffer was started by this registry.
     *
     * @var bool
     */
    private static $head_buffer_active = false;

    /**
     * Initialize the registry
     */
    public static function init()
    {
        // Output all registered schemas at priority 99 (late, after all registrations)
        add_action('wp_head', [__CLASS__, 'output_all_schemas'], 99);

        // Frontend schema debug/admin-bar UI is intentionally disabled.
        // Debug and validation should run from the dedicated admin dashboard.
    }

    /**
     * Register a schema for output
     * 
     * @param array $schema The schema array (must have @type)
     * @param array $options Optional settings:
     *   - allow_duplicate: bool (default false) - allow multiple of same @type
     *   - source: string - identifier for debugging where this came from
     * @return bool Whether the schema was registered
     */
    public static function register($schema, $options = [])
    {
        $source = isset($options['source']) ? $options['source'] : 'unknown';
        
        if (self::$output_done) {
            self::$blocked[] = [
                'type' => 'unknown',
                'reason' => 'Output already happened',
                'source' => $source
            ];
            return false;
        }

        if (function_exists('chroma_schema_strip_internal_keys')) {
            $schema = chroma_schema_strip_internal_keys($schema);
        }

        if (empty($schema) || !is_array($schema)) {
            return false;
        }

        // Get schema type
        $type = isset($schema['@type']) ? $schema['@type'] : null;
        
        // Handle array types (e.g., ["ChildCare", "LocalBusiness"])
        if (is_array($type)) {
            $type = $type[0]; // Use first type for dedup key
        }

        if (empty($type)) {
            self::$blocked[] = [
                'type' => 'empty',
                'reason' => 'No @type specified',
                'source' => $source
            ];
            return false;
        }

        // Check if type is invalid
        if (function_exists('chroma_is_invalid_schema_type') && chroma_is_invalid_schema_type($type)) {
            self::$blocked[] = [
                'type' => $type,
                'reason' => 'Invalid schema type (blocklist)',
                'source' => $source
            ];
            return false;
        }

        // Check for @id-based deduplication
        $schema_id = isset($schema['@id']) ? $schema['@id'] : null;
        if ($schema_id && isset(self::$registered_ids[$schema_id])) {
            self::$blocked[] = [
                'type' => $type,
                'reason' => 'Duplicate @id: ' . $schema_id,
                'source' => $source
            ];
            return false;
        }

        // Check for type-based deduplication (unless allowed)
        $allow_duplicate = isset($options['allow_duplicate']) ? $options['allow_duplicate'] : false;
        if (!$allow_duplicate && isset(self::$registered_types[$type])) {
            // Already have this type - skip unless it's an allowed duplicate type
            $allowed_multiples = ['ImageObject', 'ListItem', 'Question', 'Answer', 'Review', 'Service', 'Event'];
            if (!in_array($type, $allowed_multiples)) {
                self::$blocked[] = [
                    'type' => $type,
                    'reason' => 'Duplicate type (already registered)',
                    'source' => $source
                ];
                return false;
            }
        }

        // Register the schema
        self::$schemas[] = [
            'schema' => $schema,
            'type' => $type,
            'source' => $source
        ];

        self::$registered_types[$type] = true;
        if ($schema_id) {
            self::$registered_ids[$schema_id] = true;
        }

        return true;
    }

    /**
     * Check if a type has already been registered
     */
    public static function has_type($type)
    {
        return isset(self::$registered_types[$type]);
    }

    /**
     * Get count of registered schemas
     */
    public static function get_count()
    {
        return count(self::$schemas);
    }

    /**
     * Get all registered schemas (for debugging)
     */
    public static function get_all()
    {
        return self::$schemas;
    }

    /**
     * Get all blocked schemas (for debugging)
     */
    public static function get_blocked()
    {
        return self::$blocked;
    }

    /**
     * Output all registered schemas
     * This runs at wp_head priority 99
     */
    public static function output_all_schemas()
    {
        if (self::$output_done) {
            return;
        }

        self::$output_done = true;

        if (empty(self::$schemas)) {
            return;
        }

        // Build schema graph
        $graph = [];
        foreach (self::$schemas as $item) {
            $schema = $item['schema'];

            if (function_exists('chroma_schema_strip_internal_keys')) {
                $schema = chroma_schema_strip_internal_keys($schema);
            }

            if (function_exists('chroma_schema_normalize_site_urls_for_output')) {
                $schema = chroma_schema_normalize_site_urls_for_output($schema);
            }

            // Add @context if missing
            if (!isset($schema['@context'])) {
                $schema['@context'] = 'https://schema.org';
            }
            
            $graph[] = $schema;
        }

        // Output as individual scripts
        foreach ($graph as $schema) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }

    /**
     * Start a front-end head buffer so external JSON-LD emitters can be
     * normalized on staging/migrated domains without editing their storage.
     */
    public static function begin_external_schema_url_normalization()
    {
        if (is_admin() || is_feed() || is_robots() || is_404()) {
            return;
        }

        if (!function_exists('chroma_schema_normalize_site_urls_for_output')) {
            return;
        }

        $home_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        if ($home_host === '' || in_array($home_host, ['chromaela.com', 'www.chromaela.com'], true)) {
            return;
        }

        self::$head_buffer_active = true;
        ob_start([__CLASS__, 'normalize_external_schema_output']);
    }

    /**
     * End the external schema normalization buffer.
     */
    public static function end_external_schema_url_normalization()
    {
        if (!self::$head_buffer_active) {
            return;
        }

        self::$head_buffer_active = false;
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    /**
     * Normalize JSON-LD script blocks emitted by any head participant.
     *
     * @param string $html
     * @return string
     */
    public static function normalize_external_schema_output($html)
    {
        if (!is_string($html) || stripos($html, 'application/ld+json') === false) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<script\b([^>]*)type=(["\'])application\/ld\+json\2([^>]*)>(.*?)<\/script>/is',
            function ($matches) {
                $json = trim((string) $matches[4]);
                if ($json === '' || stripos($json, 'chromaela.com') === false) {
                    return $matches[0];
                }

                $decoded = json_decode($json, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                    return $matches[0];
                }

                $decoded = chroma_schema_normalize_site_urls_for_output($decoded);
                $encoded = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                if (!is_string($encoded) || $encoded === '') {
                    return $matches[0];
                }

                return '<script' . $matches[1] . 'type=' . $matches[2] . 'application/ld+json' . $matches[2] . $matches[3] . '>' . $encoded . '</script>';
            },
            $html
        );
    }

    /**
     * Output debug panel in footer for admins
     */
    public static function output_debug_panel()
    {
        // Only show for admins with debug query param
        if (!current_user_can('manage_options') || !isset($_GET['schema_debug'])) {
            return;
        }

        $registered = self::$schemas;
        $blocked = self::$blocked;
        ?>
        <div id="schema-registry-debug" style="
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            max-height: 50vh;
            overflow-y: auto;
            background: #1e1e1e;
            color: #fff;
            font-family: monospace;
            font-size: 12px;
            z-index: 999999;
            padding: 15px;
            border-top: 3px solid #00a32a;
        ">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="margin: 0; color: #00a32a;">🔍 Schema Registry Debug</h3>
                <button onclick="this.parentElement.parentElement.remove()" style="background: #d63638; color: white; border: none; padding: 5px 10px; cursor: pointer;">Close</button>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Registered Schemas -->
                <div>
                    <h4 style="color: #00a32a; margin: 0 0 10px;">✅ Registered (<?php echo count($registered); ?>)</h4>
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <tr style="background: #333;"><th style="text-align: left; padding: 5px;">Type</th><th style="text-align: left; padding: 5px;">Source</th></tr>
                        <?php foreach ($registered as $item): ?>
                        <tr style="border-bottom: 1px solid #444;">
                            <td style="padding: 5px; color: #4fc3f7;"><?php echo esc_html($item['type']); ?></td>
                            <td style="padding: 5px; color: #aaa;"><?php echo esc_html($item['source']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                
                <!-- Blocked Schemas -->
                <div>
                    <h4 style="color: #d63638; margin: 0 0 10px;">🚫 Blocked (<?php echo count($blocked); ?>)</h4>
                    <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                        <tr style="background: #333;"><th style="text-align: left; padding: 5px;">Type</th><th style="text-align: left; padding: 5px;">Reason</th><th style="text-align: left; padding: 5px;">Source</th></tr>
                        <?php foreach ($blocked as $item): ?>
                        <tr style="border-bottom: 1px solid #444;">
                            <td style="padding: 5px; color: #ff8a80;"><?php echo esc_html($item['type']); ?></td>
                            <td style="padding: 5px; color: #ffcc80;"><?php echo esc_html($item['reason']); ?></td>
                            <td style="padding: 5px; color: #aaa;"><?php echo esc_html($item['source']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($blocked)): ?>
                        <tr><td colspan="3" style="padding: 10px; color: #aaa;">No schemas blocked</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Add indicator to admin bar
     */
    public static function admin_bar_indicator($wp_admin_bar)
    {
        if (!current_user_can('manage_options') || is_admin()) {
            return;
        }

        $count = count(self::$schemas);
        $blocked_count = count(self::$blocked);
        
        $title = sprintf('Schema: %d', $count);
        if ($blocked_count > 0) {
            $title .= sprintf(' <span style="color:#ff6b6b;">(%d blocked)</span>', $blocked_count);
        }

        $wp_admin_bar->add_node([
            'id' => 'schema-registry',
            'title' => $title,
            'href' => add_query_arg('schema_debug', '1'),
            'meta' => [
                'title' => 'Click to toggle Schema Registry Debug Panel'
            ]
        ]);
    }

    /**
     * Clear the registry (useful for testing)
     */
    public static function clear()
    {
        self::$schemas = [];
        self::$blocked = [];
        self::$registered_types = [];
        self::$registered_ids = [];
        self::$output_done = false;
    }
}

// Initialize the registry
add_action('init', ['Chroma_Schema_Registry', 'init']);
add_action('wp_head', ['Chroma_Schema_Registry', 'begin_external_schema_url_normalization'], 0);
add_action('wp_head', ['Chroma_Schema_Registry', 'end_external_schema_url_normalization'], PHP_INT_MAX);

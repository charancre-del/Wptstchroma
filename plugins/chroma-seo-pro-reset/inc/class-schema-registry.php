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
     * Initialize the registry
     */
    public static function init()
    {
        // Output all registered schemas at priority 99 (late, after all registrations)
        add_action('wp_head', [__CLASS__, 'output_all_schemas'], 99);
        
        // Add debug output in footer for admins
        add_action('wp_footer', [__CLASS__, 'output_debug_panel'], 999);
        
        // Add admin bar indicator
        add_action('admin_bar_menu', [__CLASS__, 'admin_bar_indicator'], 999);
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
            $allowed_multiples = ['ImageObject', 'ListItem', 'Question', 'Answer', 'Review', 'Service'];
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



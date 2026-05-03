<?php
/**
 * Schema Cleanup Script
 * 
 * Run this ONCE via WP-CLI or browser to clean invalid schema types from stored post meta.
 * 
 * Usage via WP-CLI:
 *   wp eval-file schema-cleanup.php
 * 
 * Usage via browser (place in theme or plugin):
 *   Add to functions.php temporarily then visit: /?run_schema_cleanup=1
 */

// Exit if accessed directly without WP
if (!defined('ABSPATH')) {
    // If running via wp eval-file, this will be defined
    if (!function_exists('add_action')) {
        // Use wp_die if available, otherwise exit safely
        if (function_exists('wp_die')) {
            wp_die(
                'This script must be run within WordPress context.',
                'Access Denied',
                array('response' => 403)
            );
        } else {
            exit('This script must be run within WordPress context.');
        }
    }
}

/**
 * Invalid schema types to remove
 */
$INVALID_TYPES = array(
    'VacationRental',
    'MobileApplication',
    'SoftwareApplication',
    'WebApplication',
    'VideoGame',
    'RealEstateListing',
    'Hotel',
    'Restaurant',
    'LodgingBusiness',
    'Brand',
    'Motel',
    'Resort',
    'Hostel',
    'BedAndBreakfast',
    'Campground',
);

/**
 * Check if type is invalid (case-insensitive)
 */
function is_invalid_type($type, $invalid_list) {
    $type_lower = strtolower(trim($type));
    foreach ($invalid_list as $invalid) {
        if (strtolower($invalid) === $type_lower) {
            return true;
        }
    }
    return false;
}

/**
 * Clean schemas for a single post
 */
function clean_post_schemas($post_id, $invalid_list, $dry_run = true, $backup_key = '') {
    $schemas = get_post_meta($post_id, '_chroma_post_schemas', true);
    
    if (empty($schemas) || !is_array($schemas)) {
        return ['changed' => false, 'removed' => [], 'before' => 0, 'after' => 0];
    }
    
    $cleaned = [];
    $removed = [];
    
    foreach ($schemas as $schema) {
        $type = isset($schema['type']) ? $schema['type'] : '';
        
        if (is_invalid_type($type, $invalid_list)) {
            $removed[] = $type;
        } else {
            $cleaned[] = $schema;
        }
    }
    
    if (!empty($removed)) {
        if (!$dry_run) {
            if (!empty($backup_key)) {
                update_post_meta($post_id, $backup_key, $schemas);
            }
            update_post_meta($post_id, '_chroma_post_schemas', $cleaned);
        }
        return [
            'changed' => true,
            'removed' => $removed,
            'remaining' => count($cleaned),
            'before' => count($schemas),
            'after' => count($cleaned),
        ];
    }
    
    return ['changed' => false, 'removed' => [], 'before' => count($schemas), 'after' => count($schemas)];
}

/**
 * Run the cleanup
 */
function run_schema_cleanup($dry_run = true) {
    global $INVALID_TYPES;
    
    echo $dry_run ? "=== DRY RUN MODE ===\n\n" : "=== EXECUTING CLEANUP ===\n\n";

    $backup_key = '_chroma_post_schemas_backup_' . gmdate('Ymd_His');
    if (!$dry_run) {
        echo "Backup key for rollback: {$backup_key}\n\n";
    }
    
    // Get all posts with schema meta
    global $wpdb;
    $post_ids = $wpdb->get_col(
        "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_chroma_post_schemas'"
    );
    
    echo "Found " . count($post_ids) . " posts with schema data.\n\n";
    
    $total_cleaned = 0;
    $total_removed = 0;
    $type_counts = [];
    $total_before = 0;
    $total_after = 0;
    
    foreach ($post_ids as $post_id) {
        $result = clean_post_schemas($post_id, $INVALID_TYPES, $dry_run, $backup_key);
        $total_before += (int) ($result['before'] ?? 0);
        $total_after += (int) ($result['after'] ?? 0);
        
        if ($result['changed']) {
            $total_cleaned++;
            $total_removed += count($result['removed']);
            
            $title = get_the_title($post_id);
            $removed_types = implode(', ', $result['removed']);

            foreach ($result['removed'] as $removed_type) {
                if (!isset($type_counts[$removed_type])) {
                    $type_counts[$removed_type] = 0;
                }
                $type_counts[$removed_type]++;
            }
            
            echo "Post $post_id ($title):\n";
            echo "  - Removed: $removed_types\n";
            echo "  - Schema count: {$result['before']} -> {$result['after']}\n\n";
        }
    }
    
    echo "=== SUMMARY ===\n";
    echo "Posts modified: $total_cleaned\n";
    echo "Schema types removed: $total_removed\n";
    echo "Total schema objects: $total_before -> $total_after\n";

    if (!empty($type_counts)) {
        echo "\nRemoved by type:\n";
        arsort($type_counts);
        foreach ($type_counts as $type => $count) {
            echo "  - {$type}: {$count}\n";
        }
    }
    
    if ($dry_run) {
        echo "\nThis was a DRY RUN. To execute, run with \$dry_run = false\n";
    } else {
        echo "\nRollback hint:\n";
        echo "  Restore backup from meta key: {$backup_key}\n";
    }
}

// If running via browser hook
if (isset($_GET['run_schema_cleanup']) && current_user_can('manage_options')) {
    header('Content-Type: text/plain');
    run_schema_cleanup(isset($_GET['execute']) ? false : true);
    exit;
}

// If running via WP-CLI eval-file
if (defined('WP_CLI') && WP_CLI) {
    // Check for --execute flag
    $dry_run = !in_array('--execute', $GLOBALS['argv'] ?? []);
    run_schema_cleanup($dry_run);
}

echo "\n";
echo "To use this script:\n";
echo "  WP-CLI (dry run): wp eval-file schema-cleanup.php\n";
echo "  WP-CLI (execute): wp eval-file schema-cleanup.php --execute\n";
echo "  Browser (dry run): /?run_schema_cleanup=1\n";
echo "  Browser (execute): /?run_schema_cleanup=1&execute=1\n";

<?php
/**
 * Upgrade Manager
 *
 * Handles database schema updates and data migrations.
 *
 * @package ChromaQAReports
 */

namespace ChromaQA;

class Upgrade_Manager
{

    /**
     * Run upgrades if version changed.
     */
    public static function check_and_run()
    {
        $current_version = get_option('cqa_db_version', '0.0.0');

        if (version_compare($current_version, CQA_VERSION, '<')) {
            self::upgrade($current_version);
        }
    }

    /**
     * Execute upgrade steps.
     *
     * @param string $current_version The version currently installed.
     */
    private static function upgrade($current_version)
    {
        // Run Schema Delta (Idempotent)
        require_once CQA_PLUGIN_DIR . 'includes/class-activator.php';
        Activator::activate(); // Re-runs dbDelta

        // Version 1.1.0: Fix schools JSON
        if (version_compare($current_version, '1.1.0', '<')) {
            self::migration_v1_1_fix_school_json();
        }

        // Version-specific data migrations
        if (version_compare($current_version, '1.1.0', '<')) {
            self::migration_v1_1_fix_school_json();
        }

        // Version 1.2.0: Consolidate Options (FIX-306)
        if (version_compare($current_version, '1.2.0', '<')) {
            self::migration_v1_2_consolidate_options();
        }

        // Update version option
        update_option('cqa_db_version', CQA_VERSION);
    }

    /**
     * Migration: Fix invalid JSON in schools table.
     * Safe on live data.
     */
    private static function migration_v1_1_fix_school_json()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cqa_schools';

        // Find rows with empty or invalid config
        $rows = $wpdb->get_results("SELECT id, classroom_config FROM {$table} WHERE classroom_config IS NULL OR classroom_config = ''");

        foreach ($rows as $row) {
            $wpdb->update(
                $table,
                ['classroom_config' => '{}'],
                ['id' => $row->id],
                ['%s'],
                ['%d']
            );
        }
    }
}

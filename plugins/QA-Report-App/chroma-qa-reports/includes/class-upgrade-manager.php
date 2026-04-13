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
        // Removed undefined migration_v1_2_consolidate_options method call

        // Version 1.3.1: Snapshot integrity hardening
        if (version_compare($current_version, '1.3.1', '<')) {
            self::migration_v1_3_1_harden_report_snapshots();
        }

        // Version 1.3.2: Snapshot typing and autosave retention
        if (version_compare($current_version, '1.3.2', '<')) {
            self::migration_v1_3_2_snapshot_types();
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

    /**
     * Migration: ensure every live report has a current-version snapshot row.
     *
     * @return void
     */
    private static function migration_v1_3_1_harden_report_snapshots()
    {
        global $wpdb;

        $reports_table = $wpdb->prefix . 'cqa_reports';
        $snapshots_table = $wpdb->prefix . 'cqa_report_snapshots';

        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $snapshots_table));
        if ($table_exists !== $snapshots_table) {
            return;
        }

        $report_ids = $wpdb->get_col(
            "SELECT r.id
             FROM {$reports_table} r
             LEFT JOIN {$snapshots_table} s
               ON s.report_id = r.id
              AND s.version_number = r.version_id
             WHERE s.id IS NULL"
        );

        foreach ($report_ids as $report_id) {
            \ChromaQA\Models\Report_Snapshot::create_snapshot((int) $report_id, 'Backfilled current version snapshot');
        }
    }

    /**
     * Migration: classify snapshots and prune autosave noise safely.
     *
     * @return void
     */
    private static function migration_v1_3_2_snapshot_types()
    {
        global $wpdb;

        $snapshots_table = $wpdb->prefix . 'cqa_report_snapshots';
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $snapshots_table));
        if ($table_exists !== $snapshots_table) {
            return;
        }

        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$snapshots_table} LIKE 'snapshot_type'");
        if ($column_exists) {
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$snapshots_table} SET snapshot_type = %s WHERE snapshot_type IS NULL OR snapshot_type = ''",
                    'manual'
                )
            );
        }

        // Note: We intentionally skip running prune_old_versions for every report here
        // to prevent `max_execution_time` timeouts (500 errors) on large databases block plugins_loaded.
        // Old autosaves will be naturally pruned the next time a report is saved via Report_Snapshot::create_snapshot.
    }
}

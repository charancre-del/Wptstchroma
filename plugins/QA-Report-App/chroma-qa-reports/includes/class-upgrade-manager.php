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
     *
     * Hardened so no migration can bubble a fatal/timeout up to `plugins_loaded`
     * and take the entire site down with a 500. The stored DB version is bumped
     * to the current plugin version *before* the data migrations run, so a
     * migration that throws or times out does not cause every subsequent request
     * to re-enter this code path.
     */
    public static function check_and_run()
    {
        $current_version = \get_option('cqa_db_version', '0.0.0');

        if (!\version_compare($current_version, CQA_VERSION, '<')) {
            return;
        }

        // Prevent concurrent bootstraps from all racing to run the same
        // migration — on a freshly deployed plugin, dozens of parallel
        // requests can otherwise pile onto the DB simultaneously and trip
        // PHP `max_execution_time`, producing empty 500s for every visitor.
        $lock_key = 'cqa_upgrade_lock';
        if (\get_transient($lock_key)) {
            return;
        }
        \set_transient($lock_key, 1, 5 * \MINUTE_IN_SECONDS);

        // Bump the stored DB version up front. If a migration below fails,
        // subsequent requests will NOT retry the full upgrade and will NOT
        // keep 500ing. Data migrations are individually idempotent/safe to
        // re-run via the admin Tools → Force Schema Update path.
        \update_option('cqa_db_version', CQA_VERSION, false);

        try {
            self::upgrade($current_version);
        } catch (\Throwable $e) {
            if (\defined('WP_DEBUG') && \WP_DEBUG) {
                \error_log('[CQA Upgrade] Upgrade aborted: ' . $e->getMessage());
            }
        } finally {
            \delete_transient($lock_key);
        }
    }

    /**
     * Execute upgrade steps.
     *
     * Each migration is isolated so one failure does not block the others.
     *
     * @param string $current_version The version currently installed.
     */
    private static function upgrade($current_version)
    {
        // Give the migration a bit more breathing room than a typical page
        // render, but don't assume it's allowed — some hosts disable
        // set_time_limit entirely.
        if (\function_exists('set_time_limit')) {
            @\set_time_limit(120);
        }

        // Run Schema Delta (Idempotent)
        require_once CQA_PLUGIN_DIR . 'includes/class-activator.php';
        self::run_step('activator', function () {
            Activator::activate();
        });

        // Version 1.1.0: Fix schools JSON
        if (\version_compare($current_version, '1.1.0', '<')) {
            self::run_step('v1_1_fix_school_json', [self::class, 'migration_v1_1_fix_school_json']);
        }

        // Version 1.2.0: Consolidate Options (FIX-306)
        // Removed undefined migration_v1_2_consolidate_options method call

        // Version 1.3.1: Snapshot integrity hardening
        //
        // The original backfill looped over every report missing a
        // current-version snapshot and called Report_Snapshot::create_snapshot()
        // for each. That internally triggers prune_old_versions() per report,
        // which on large datasets exceeds max_execution_time and 500s the
        // entire site during `plugins_loaded`.
        //
        // Snapshots are now created lazily whenever a report is saved
        // (see class-report.php and class-rest-controller.php), so running
        // this backfill synchronously at upgrade time is no longer required.

        // Version 1.3.2: Snapshot typing and autosave retention
        if (\version_compare($current_version, '1.3.2', '<')) {
            self::run_step('v1_3_2_snapshot_types', [self::class, 'migration_v1_3_2_snapshot_types']);
        }
    }

    /**
     * Run a single migration step in an isolated error boundary.
     *
     * @param string   $name    Human-readable step identifier for logs.
     * @param callable $handler Step to run.
     * @return void
     */
    private static function run_step($name, callable $handler)
    {
        try {
            \call_user_func($handler);
        } catch (\Throwable $e) {
            if (\defined('WP_DEBUG') && \WP_DEBUG) {
                \error_log(\sprintf('[CQA Upgrade] Step %s failed: %s', $name, $e->getMessage()));
            }
        }
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

<?php
/**
 * Cleanup Service
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Services;

use ChromaQA\Utils\Logger;
use ChromaQA\Models\Report_Snapshot;

/**
 * Handles scheduled cleanup tasks.
 */
class Cleanup_Service
{

    /**
     * Run daily cleanup.
     */
    public static function daily_cleanup()
    {
        self::cleanup_temp_files();
        self::prune_report_versions();
        // Future: self::cleanup_orphan_drive_files();
    }

    /**
     * Clean up temporary files older than 24 hours.
     */
    private static function cleanup_temp_files()
    {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/chroma-qa-reports/temp/';

        if (!is_dir($temp_dir)) {
            return;
        }

        $files = scandir($temp_dir);
        $now = time();
        $count = 0;

        foreach ($files as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            }

            $path = $temp_dir . $file;
            if (is_file($path)) {
                $mtime = filemtime($path);
                // Delete if older than 24 hours (86400 seconds)
                if ($now - $mtime > 86400) {
                    if (@unlink($path)) {
                        $count++;
                    }
                }
            }
        }

        if ($count > 0) {
            Logger::info('CleanupService', 'cleanup_temp_files', "Cleaned up {$count} temporary files.");
        }
    }

    /**
     * Prune old report versions beyond retention limit.
     */
    private static function prune_report_versions()
    {
        global $wpdb;
        $reports_table = $wpdb->prefix . 'cqa_reports';

        // Get all report IDs
        $report_ids = $wpdb->get_col("SELECT id FROM {$reports_table}");

        if (empty($report_ids)) {
            return;
        }

        $total_pruned = 0;

        foreach ($report_ids as $report_id) {
            $pruned = Report_Snapshot::prune_old_versions($report_id);
            $total_pruned += $pruned;
        }

        if ($total_pruned > 0) {
            Logger::info('CleanupService', 'prune_report_versions', "Pruned {$total_pruned} old version snapshots.");
        }
    }
}


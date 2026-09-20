<?php
/**
 * Cleanup Service
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Services;

use ChromaQA\Utils\Logger;
use ChromaQA\Utils\Private_Temp_Storage;
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
        $count = 0;
        try {
            $count += Private_Temp_Storage::purge_older_than(86400);
        } catch (\RuntimeException $error) {
            Logger::error('CleanupService', 'cleanup_temp_files', [], ['reason' => 'private_temp_unavailable']);
        }

        // Transitional cleanup for files produced by older releases. Never
        // recurse, traverse links, or delete anything outside the upload root.
        $upload_dir = wp_upload_dir();
        if (!empty($upload_dir['basedir'])) {
            $count += self::cleanup_legacy_temp_dir($upload_dir['basedir'], 'cqa-temp', 86400);
            $count += self::cleanup_legacy_temp_dir($upload_dir['basedir'], 'chroma-qa-reports/temp', 86400);
        }

        if ($count > 0) {
            Logger::info('CleanupService', 'cleanup_temp_files', [], ['removed' => $count]);
        }
    }

    /**
     * Clean one historical temp directory without following links.
     *
     * @param string $upload_root Canonical WordPress upload root candidate.
     * @param string $relative    Fixed historical relative path.
     * @param int    $max_age     Minimum age in seconds.
     * @return int
     */
    private static function cleanup_legacy_temp_dir($upload_root, $relative, $max_age)
    {
        $root = realpath($upload_root);
        $candidate = rtrim($upload_root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if ($root === false || is_link($candidate) || !is_dir($candidate)) {
            return 0;
        }

        $directory = realpath($candidate);
        $normalized_root = rtrim(str_replace('\\', '/', $root), '/');
        $normalized_directory = $directory === false ? '' : rtrim(str_replace('\\', '/', $directory), '/');
        if (DIRECTORY_SEPARATOR === '\\') {
            $normalized_root = strtolower($normalized_root);
            $normalized_directory = strtolower($normalized_directory);
        }
        if ($normalized_directory === '' || strpos($normalized_directory, $normalized_root . '/') !== 0) {
            return 0;
        }

        $files = scandir($directory);
        if ($files === false) {
            return 0;
        }

        $cutoff = time() - max(0, (int) $max_age);
        $count = 0;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_link($path) || !is_file($path) || dirname(realpath($path) ?: '') !== $directory) {
                continue;
            }

            $mtime = filemtime($path);
            if ($mtime !== false && $mtime < $cutoff && @unlink($path)) {
                $count++;
            }
        }

        return $count;
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


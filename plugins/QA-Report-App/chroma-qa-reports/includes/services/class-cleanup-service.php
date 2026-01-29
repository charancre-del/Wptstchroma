<?php
/**
 * Cleanup Service
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Services;

use ChromaQA\Utils\Logger;

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
}

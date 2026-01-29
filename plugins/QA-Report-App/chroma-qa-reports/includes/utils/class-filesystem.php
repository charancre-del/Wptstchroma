<?php
/**
 * File System Helper
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Utils;

/**
 * Handles file system operations safely.
 */
class FileSystem
{

    /**
     * Write data to a file atomically.
     * Writes to a temporary file first, then renames it to the destination.
     *
     * @param string $filename Path to the file.
     * @param mixed  $data     Data to write.
     * @return int|bool Number of bytes written, or false on failure.
     */
    public static function put_contents($filename, $data)
    {
        $temp_file = $filename . '.tmp.' . uniqid();

        // Write to temp file
        $bytes = file_put_contents($temp_file, $data);

        if ($bytes === false) {
            return false;
        }

        // Set permissions
        chmod($temp_file, 0644);

        // Rename/Move (Atomic operation on POSIX)
        if (rename($temp_file, $filename)) {
            return $bytes;
        }

        // Cleanup if rename failed
        unlink($temp_file);
        return false;
    }

    /**
     * Append data to a file with locking.
     *
     * @param string $filename Path to the file.
     * @param mixed  $data     Data to append.
     * @return int|bool Number of bytes written, or false on failure.
     */
    public static function append($filename, $data)
    {
        return file_put_contents($filename, $data, FILE_APPEND | LOCK_EX);
    }
}

<?php
/**
 * Private temporary file storage.
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Utils;

/**
 * Creates short-lived files outside web-served WordPress directories.
 */
class Private_Temp_Storage
{
    const DIRECTORY_NAME = 'chroma-qa-reports-private';
    const MAX_CLEANUP_WARNINGS = 3;

    /** @var int Number of cleanup warnings emitted during this request. */
    private static $cleanup_warning_count = 0;

    /**
     * Return the validated private temporary directory.
     *
     * CQA_PRIVATE_TEMP_DIR may be set to an absolute directory outside every
     * WordPress/web root. Otherwise the plugin uses the operating-system temp
     * directory and a plugin-specific child directory.
     *
     * @return string
     * @throws \RuntimeException When a safe directory cannot be established.
     */
    public static function get_root()
    {
        $configured = defined('CQA_PRIVATE_TEMP_DIR') ? CQA_PRIVATE_TEMP_DIR : getenv('CQA_PRIVATE_TEMP_DIR');

        if (is_string($configured) && trim($configured) !== '') {
            $candidate = trim($configured);
        } else {
            $base = function_exists('get_temp_dir') ? \get_temp_dir() : sys_get_temp_dir();
            $candidate = rtrim((string) $base, '/\\') . DIRECTORY_SEPARATOR . self::DIRECTORY_NAME;
        }

        self::assert_absolute_path($candidate);
        self::assert_no_symlink_components($candidate);
        self::assert_outside_web_roots($candidate);

        if (!is_dir($candidate) && !@mkdir($candidate, 0700, true) && !is_dir($candidate)) {
            throw new \RuntimeException('Unable to create the private temporary directory.');
        }

        if (is_link($candidate)) {
            throw new \RuntimeException('The private temporary directory must not be a symbolic link.');
        }

        $root = realpath($candidate);
        if ($root === false || !is_dir($root) || !is_writable($root)) {
            throw new \RuntimeException('The private temporary directory is unavailable or not writable.');
        }

        self::assert_outside_web_roots($root);
        @chmod($root, 0700);
        self::assert_permissions($root, 0700, 'directory');

        return rtrim($root, '/\\');
    }

    /**
     * Allocate a collision-resistant empty file with restrictive permissions.
     *
     * @param string $extension File extension without a leading dot.
     * @param string $prefix    Non-sensitive filename prefix.
     * @return string
     * @throws \RuntimeException When allocation fails.
     */
    public static function create($extension, $prefix = 'cqa-')
    {
        $extension = strtolower(ltrim((string) $extension, '.'));
        if (!preg_match('/^[a-z0-9]{1,10}$/', $extension)) {
            throw new \RuntimeException('Invalid private temporary file extension.');
        }

        $prefix = preg_replace('/[^a-z0-9_-]/i', '-', (string) $prefix);
        $prefix = trim(substr($prefix, 0, 40), '-_');
        if ($prefix === '') {
            $prefix = 'cqa';
        }

        $root = self::get_root();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $random = bin2hex(random_bytes(16));
            } catch (\Exception $error) {
                throw new \RuntimeException('Unable to generate a secure temporary filename.', 0, $error);
            }

            $path = $root . DIRECTORY_SEPARATOR . $prefix . '-' . $random . '.' . $extension;
            $handle = @fopen($path, 'x+b');
            if ($handle !== false) {
                fclose($handle);
                @chmod($path, 0600);
                try {
                    self::assert_permissions($path, 0600, 'file');
                } catch (\RuntimeException $error) {
                    @unlink($path);
                    throw $error;
                }
                return $path;
            }
        }

        throw new \RuntimeException('Unable to allocate a private temporary file.');
    }

    /**
     * Write a complete private temporary file.
     *
     * @param string $contents  File contents.
     * @param string $extension File extension.
     * @param string $prefix    Non-sensitive prefix.
     * @return string
     * @throws \RuntimeException When writing fails.
     */
    public static function write($contents, $extension, $prefix = 'cqa-')
    {
        $path = self::create($extension, $prefix);
        $bytes = @file_put_contents($path, $contents, LOCK_EX);
        if ($bytes === false) {
            self::delete($path, 'write_rollback');
            throw new \RuntimeException('Unable to write a private temporary file.');
        }

        @chmod($path, 0600);
        try {
            self::assert_permissions($path, 0600, 'file');
        } catch (\RuntimeException $error) {
            self::delete($path, 'write_rollback');
            throw $error;
        }
        return $path;
    }

    /**
     * Copy an uploaded/source file directly into private temporary storage.
     *
     * @param string $source    Source path.
     * @param string $extension Destination extension.
     * @param string $prefix    Non-sensitive prefix.
     * @return string
     * @throws \RuntimeException When the source cannot be copied safely.
     */
    public static function import($source, $extension, $prefix = 'upload-')
    {
        if (!is_string($source) || $source === '' || is_link($source) || !is_file($source) || !is_readable($source)) {
            throw new \RuntimeException('The uploaded temporary file is unavailable.');
        }

        $destination = self::create($extension, $prefix);
        $input = @fopen($source, 'rb');
        $output = @fopen($destination, 'wb');

        if ($input === false || $output === false) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            self::delete($destination, 'import_rollback');
            throw new \RuntimeException('Unable to open the uploaded temporary file.');
        }

        $copied = false;
        try {
            $bytes = stream_copy_to_stream($input, $output);
            if ($bytes === false || $bytes === 0 || !fflush($output)) {
                throw new \RuntimeException('Unable to copy the uploaded temporary file.');
            }
            $copied = true;
        } finally {
            fclose($input);
            fclose($output);
            if (!$copied) {
                self::delete($destination, 'import_rollback');
            }
        }

        @chmod($destination, 0600);
        try {
            self::assert_permissions($destination, 0600, 'file');
        } catch (\RuntimeException $error) {
            self::delete($destination, 'import_rollback');
            throw $error;
        }
        return $destination;
    }

    /**
     * Run a synchronous consumer and always remove the managed file afterward.
     *
     * @param string   $path     Managed path.
     * @param callable      $consumer Consumer receiving the path.
     * @param string        $context  Bounded, non-sensitive cleanup context.
     * @param callable|null $cleanup  Test seam for the unlink operation.
     * @return mixed
     * @throws \RuntimeException When the path is not a managed regular file.
     */
    public static function consume($path, callable $consumer, $context = 'consume', callable $cleanup = null)
    {
        if (!self::is_managed_file($path)) {
            throw new \RuntimeException('Refusing to consume an unmanaged temporary file.');
        }

        try {
            return $consumer($path);
        } finally {
            if ($cleanup === null) {
                self::delete($path, $context);
            } else {
                try {
                    $removed = (bool) $cleanup($path);
                } catch (\Throwable $error) {
                    $removed = false;
                }

                if (!$removed && file_exists($path)) {
                    self::report_cleanup_failure($context);
                }
            }
        }
    }

    /**
     * Determine whether a path is a regular file directly managed here.
     *
     * @param string $path Candidate path.
     * @return bool
     */
    public static function is_managed_file($path)
    {
        if (!is_string($path) || $path === '' || is_link($path) || !is_file($path)) {
            return false;
        }

        $real = realpath($path);
        if ($real === false) {
            return false;
        }

        try {
            $root = self::get_root();
        } catch (\RuntimeException $error) {
            return false;
        }

        return dirname($real) === $root && self::is_within($real, $root);
    }

    /**
     * Delete only a managed regular file.
     *
     * @param string $path    Candidate path.
     * @param string $context Bounded, non-sensitive cleanup context.
     * @return bool
     */
    public static function delete($path, $context = 'delete')
    {
        if (!self::is_managed_file($path)) {
            return false;
        }

        $removed = @unlink($path);
        if (!$removed && file_exists($path)) {
            self::report_cleanup_failure($context);
        }

        return $removed || !file_exists($path);
    }

    /**
     * Remove abandoned managed files older than the specified age.
     *
     * @param int $seconds Minimum file age.
     * @return int Number removed.
     */
    public static function purge_older_than($seconds)
    {
        $seconds = max(0, (int) $seconds);
        $root = self::get_root();
        $entries = scandir($root);
        if ($entries === false) {
            throw new \RuntimeException('Unable to inspect the private temporary directory.');
        }

        $removed = 0;
        $cutoff = time() - $seconds;
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $root . DIRECTORY_SEPARATOR . $entry;
            if (!self::is_managed_file($path)) {
                continue;
            }

            $modified = filemtime($path);
            if ($modified !== false && $modified < $cutoff && self::delete($path, 'scheduled_cleanup')) {
                $removed++;
            }
        }

        return $removed;
    }

    /** @param string $path Path to validate. */
    private static function assert_absolute_path($path)
    {
        if (!is_string($path) || strpos($path, "\0") !== false || preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
            throw new \RuntimeException('The private temporary directory path is invalid.');
        }

        if (preg_match('#(?:^|[\\\\/])\.\.?([\\\\/]|$)#', $path)) {
            throw new \RuntimeException('The private temporary directory path must not contain dot segments.');
        }

        $is_windows = (bool) preg_match('#^[a-z]:[\\\\/]#i', $path);
        $is_unc = strpos($path, '\\\\') === 0;
        $is_posix = substr($path, 0, 1) === '/';
        if (!$is_windows && !$is_unc && !$is_posix) {
            throw new \RuntimeException('The private temporary directory must be an absolute path.');
        }
    }

    /** @param string $path Path whose existing components must not be links. */
    private static function assert_no_symlink_components($path)
    {
        $probe = $path;
        while ($probe !== '' && !file_exists($probe)) {
            $parent = dirname($probe);
            if ($parent === $probe) {
                break;
            }
            $probe = $parent;
        }

        while ($probe !== '' && dirname($probe) !== $probe) {
            if (is_link($probe)) {
                throw new \RuntimeException('The private temporary directory path must not traverse symbolic links.');
            }
            $probe = dirname($probe);
        }
    }

    /** @param string $root Validated candidate root. */
    private static function assert_outside_web_roots($root)
    {
        $forbidden = [];
        if (defined('ABSPATH')) {
            $forbidden[] = ABSPATH;
        }
        if (defined('WP_CONTENT_DIR')) {
            $forbidden[] = WP_CONTENT_DIR;
        }
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            $forbidden[] = $_SERVER['DOCUMENT_ROOT'];
        }
        if (function_exists('wp_upload_dir')) {
            $uploads = \wp_upload_dir(null, false);
            if (is_array($uploads) && !empty($uploads['basedir'])) {
                $forbidden[] = $uploads['basedir'];
            }
        }

        foreach ($forbidden as $path) {
            $real = realpath((string) $path);
            if ($real !== false && self::is_within($root, $real)) {
                throw new \RuntimeException('The private temporary directory must be outside web-served WordPress paths.');
            }
        }
    }

    /**
     * Require restrictive POSIX mode bits; Windows access is ACL-managed.
     *
     * @param string $path     File or directory path.
     * @param int    $expected Expected POSIX permission bits.
     * @param string $label    Safe error-message label.
     */
    private static function assert_permissions($path, $expected, $label)
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            return;
        }

        clearstatcache(true, $path);
        $permissions = fileperms($path);
        if ($permissions === false || ($permissions & 0777) !== $expected) {
            throw new \RuntimeException('The private temporary ' . $label . ' permissions are not restrictive enough.');
        }
    }

    /**
     * Emit a bounded, path-free signal without disrupting completed work.
     *
     * @param string $context Cleanup call-site context.
     */
    private static function report_cleanup_failure($context)
    {
        if (self::$cleanup_warning_count >= self::MAX_CLEANUP_WARNINGS) {
            return;
        }

        $allowed = [
            'consume',
            'delete',
            'docx_upload',
            'import_rollback',
            'mail_attachment',
            'pdf_generation',
            'rest_export',
            'scheduled_cleanup',
            'write_rollback',
        ];
        $context = in_array($context, $allowed, true) ? $context : 'unspecified';
        self::$cleanup_warning_count++;

        if (function_exists('do_action')) {
            try {
                \do_action('cqa_private_temp_cleanup_failed', $context);
            } catch (\Throwable $error) {
                // Observability hooks must never change a completed operation.
            }
        }

        @error_log('[CQA][WARNING] Private temporary cleanup failed; context=' . $context);
    }

    /**
     * Compare canonical paths with a directory boundary.
     *
     * @param string $path Candidate path.
     * @param string $root Parent root.
     * @return bool
     */
    private static function is_within($path, $root)
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $root = rtrim(str_replace('\\', '/', $root), '/');
        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtolower($path);
            $root = strtolower($root);
        }

        return $path === $root || strpos($path, $root . '/') === 0;
    }
}

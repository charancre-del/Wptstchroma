<?php
namespace ChromaQA\Tests\Security;

use ChromaQA\Utils\Private_Temp_Storage;
use PHPUnit\Framework\TestCase;

class PrivateTempStorageTest extends TestCase
{
    private $root;
    private $original_environment;
    private $original_document_root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cqa-private-test-' . bin2hex(random_bytes(8));
        $this->original_environment = getenv('CQA_PRIVATE_TEMP_DIR');
        $this->original_document_root = $_SERVER['DOCUMENT_ROOT'] ?? null;
        putenv('CQA_PRIVATE_TEMP_DIR=' . $this->root);
        unset($_SERVER['DOCUMENT_ROOT']);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root) && !is_link($this->root)) {
            foreach (scandir($this->root) ?: [] as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    $path = $this->root . DIRECTORY_SEPARATOR . $entry;
                    if (is_file($path) || is_link($path)) {
                        unlink($path);
                    }
                }
            }
            rmdir($this->root);
        } elseif (is_link($this->root)) {
            unlink($this->root);
        }

        if ($this->original_environment === false) {
            putenv('CQA_PRIVATE_TEMP_DIR');
        } else {
            putenv('CQA_PRIVATE_TEMP_DIR=' . $this->original_environment);
        }

        if ($this->original_document_root === null) {
            unset($_SERVER['DOCUMENT_ROOT']);
        } else {
            $_SERVER['DOCUMENT_ROOT'] = $this->original_document_root;
        }
    }

    public function test_write_uses_private_root_random_name_and_restrictive_mode()
    {
        $first = Private_Temp_Storage::write('first', 'pdf', 'report-7');
        $second = Private_Temp_Storage::write('second', 'pdf', 'report-7');

        $this->assertNotSame($first, $second);
        $this->assertSame(realpath($this->root), dirname(realpath($first)));
        $this->assertSame('first', file_get_contents($first));
        $this->assertTrue(Private_Temp_Storage::is_managed_file($first));

        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->assertSame(0700, fileperms($this->root) & 0777);
            $this->assertSame(0600, fileperms($first) & 0777);
        }
    }

    public function test_consume_deletes_after_success_and_exception()
    {
        $success = Private_Temp_Storage::write('content', 'pdf');
        $this->assertSame('content', Private_Temp_Storage::consume($success, static function ($path) {
            return file_get_contents($path);
        }));
        $this->assertFileDoesNotExist($success);

        $failure = Private_Temp_Storage::write('content', 'pdf');
        try {
            Private_Temp_Storage::consume($failure, static function () {
                throw new \RuntimeException('synthetic consumer failure');
            });
            $this->fail('The synthetic consumer failure was not propagated.');
        } catch (\RuntimeException $error) {
            $this->assertSame('synthetic consumer failure', $error->getMessage());
        }
        $this->assertFileDoesNotExist($failure);
    }

    public function test_cleanup_failures_are_bounded_observable_and_do_not_replace_success()
    {
        $log = tempnam(sys_get_temp_dir(), 'cqa-cleanup-log-');
        $original_log = ini_get('error_log');
        $counter = new \ReflectionProperty(Private_Temp_Storage::class, 'cleanup_warning_count');
        $counter->setAccessible(true);
        $counter->setValue(null, 0);
        ini_set('error_log', $log);

        try {
            for ($index = 0; $index < 4; $index++) {
                $path = Private_Temp_Storage::write('content', 'pdf');
                $result = Private_Temp_Storage::consume(
                    $path,
                    static function () {
                        return 'consumer-success';
                    },
                    'rest_export',
                    static function () {
                        return false;
                    }
                );

                $this->assertSame('consumer-success', $result);
                $this->assertFileExists($path);
            }

            $entries = array_values(array_filter(file($log, FILE_IGNORE_NEW_LINES) ?: []));
            $this->assertCount(Private_Temp_Storage::MAX_CLEANUP_WARNINGS, $entries);
            foreach ($entries as $entry) {
                $this->assertStringContainsString('Private temporary cleanup failed; context=rest_export', $entry);
                $this->assertStringNotContainsString($this->root, $entry);
                $this->assertStringNotContainsString('.pdf', $entry);
            }
        } finally {
            ini_set('error_log', $original_log);
            unlink($log);
        }
    }

    public function test_import_and_age_based_purge_stay_within_managed_root()
    {
        $source = tempnam(sys_get_temp_dir(), 'cqa-source-');
        file_put_contents($source, 'synthetic docx bytes');

        try {
            $imported = Private_Temp_Storage::import($source, 'docx');
            $young = Private_Temp_Storage::write('young', 'pdf');
            touch($imported, time() - 90000);

            $this->assertSame(1, Private_Temp_Storage::purge_older_than(86400));
            $this->assertFileDoesNotExist($imported);
            $this->assertFileExists($young);

            $outside = tempnam(sys_get_temp_dir(), 'cqa-outside-');
            $this->assertFalse(Private_Temp_Storage::delete($outside));
            $this->assertFileExists($outside);
            unlink($outside);
        } finally {
            if (is_file($source)) {
                unlink($source);
            }
        }
    }

    public function test_web_root_configuration_is_rejected_before_creation()
    {
        $web_root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cqa-web-test-' . bin2hex(random_bytes(8));
        mkdir($web_root, 0700);
        $unsafe = $web_root . DIRECTORY_SEPARATOR . 'private';
        $_SERVER['DOCUMENT_ROOT'] = $web_root;
        putenv('CQA_PRIVATE_TEMP_DIR=' . $unsafe);

        try {
            $this->expectException(\RuntimeException::class);
            Private_Temp_Storage::get_root();
        } finally {
            $this->assertDirectoryDoesNotExist($unsafe);
            rmdir($web_root);
            putenv('CQA_PRIVATE_TEMP_DIR=' . $this->root);
            unset($_SERVER['DOCUMENT_ROOT']);
        }
    }
}

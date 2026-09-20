<?php
namespace ChromaQA\Tests\Security;

use ChromaQA\API\REST_Controller;
use ChromaQA\Utils\Private_Temp_Storage;
use PHPUnit\Framework\TestCase;

class ControllerPrivateMediaBoundaryTest extends TestCase
{
    private $root;
    private $original_environment;
    private $controller;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cqa-controller-test-' . bin2hex(random_bytes(8));
        $this->original_environment = getenv('CQA_PRIVATE_TEMP_DIR');
        putenv('CQA_PRIVATE_TEMP_DIR=' . $this->root);
        $this->controller = new REST_Controller();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            foreach (scandir($this->root) ?: [] as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    $path = $this->root . DIRECTORY_SEPARATOR . $entry;
                    if (is_file($path) || is_link($path)) {
                        unlink($path);
                    }
                }
            }
            rmdir($this->root);
        }

        if ($this->original_environment === false) {
            putenv('CQA_PRIVATE_TEMP_DIR');
        } else {
            putenv('CQA_PRIVATE_TEMP_DIR=' . $this->original_environment);
        }
    }

    public function test_pdf_boundary_emits_private_headers_and_deletes_after_success()
    {
        $path = Private_Temp_Storage::write('%PDF-synthetic', 'pdf');
        $headers = [];
        $method = new \ReflectionMethod(REST_Controller::class, 'stream_private_export');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->controller, [
            $path,
            'application/pdf',
            'Synthetic-QA-Report.pdf',
            static function ($private_path) {
                return file_get_contents($private_path);
            },
            static function ($header) use (&$headers) {
                $headers[] = $header;
            },
        ]);

        $this->assertSame('%PDF-synthetic', $result);
        $this->assertSame([
            'Content-Type: application/pdf',
            'Content-Disposition: inline; filename="Synthetic-QA-Report.pdf"',
            'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma: no-cache',
            'Expires: 0',
            'X-Content-Type-Options: nosniff',
        ], $headers);
        $this->assertFileDoesNotExist($path);
    }

    public function test_pdf_boundary_deletes_when_streaming_throws()
    {
        $path = Private_Temp_Storage::write('%PDF-synthetic', 'pdf');
        $headers = [];
        $method = new \ReflectionMethod(REST_Controller::class, 'stream_private_export');
        $method->setAccessible(true);

        try {
            $method->invokeArgs($this->controller, [
                $path,
                'application/pdf',
                'Synthetic-QA-Report.pdf',
                static function () {
                    throw new \RuntimeException('synthetic stream failure');
                },
                static function ($header) use (&$headers) {
                    $headers[] = $header;
                },
            ]);
            $this->fail('The synthetic stream failure was not propagated.');
        } catch (\RuntimeException $error) {
            $this->assertSame('synthetic stream failure', $error->getMessage());
        }

        $this->assertCount(6, $headers);
        $this->assertFileDoesNotExist($path);
    }

    public function test_pdf_boundary_deletes_when_header_emission_throws()
    {
        $path = Private_Temp_Storage::write('%PDF-synthetic', 'pdf');
        $method = new \ReflectionMethod(REST_Controller::class, 'stream_private_export');
        $method->setAccessible(true);

        try {
            $method->invokeArgs($this->controller, [
                $path,
                'application/pdf',
                'Synthetic-QA-Report.pdf',
                function () {
                    $this->fail('Streaming must not begin after a header failure.');
                },
                static function () {
                    throw new \RuntimeException('synthetic header failure');
                },
            ]);
            $this->fail('The synthetic header failure was not propagated.');
        } catch (\RuntimeException $error) {
            $this->assertSame('synthetic header failure', $error->getMessage());
        }

        $this->assertFileDoesNotExist($path);
    }

    public function test_docx_private_copy_is_deleted_after_parser_success()
    {
        $source = $this->createSyntheticDocxSource();
        $private_path = null;
        $method = new \ReflectionMethod(REST_Controller::class, 'process_uploaded_report_doc');
        $method->setAccessible(true);

        try {
            $result = $method->invokeArgs($this->controller, [
                $source,
                function ($path) use (&$private_path, $source) {
                    $private_path = $path;
                    $this->assertNotSame($source, $path);
                    $this->assertTrue(Private_Temp_Storage::is_managed_file($path));
                    $this->assertSame('synthetic docx bytes', file_get_contents($path));
                    return 'synthetic extracted text';
                },
                static function ($text) {
                    return ['text' => $text];
                },
            ]);

            $this->assertSame(['text' => 'synthetic extracted text'], $result);
            $this->assertFileDoesNotExist($private_path);
            $this->assertFileExists($source);
        } finally {
            unlink($source);
        }
    }

    public function test_docx_private_copy_is_deleted_when_extractor_returns_wp_error()
    {
        $source = $this->createSyntheticDocxSource();
        $private_path = null;
        $method = new \ReflectionMethod(REST_Controller::class, 'process_uploaded_report_doc');
        $method->setAccessible(true);

        try {
            $result = $method->invokeArgs($this->controller, [
                $source,
                static function ($path) use (&$private_path) {
                    $private_path = $path;
                    return new \WP_Error('synthetic_parse_error', 'Synthetic parse failure.');
                },
                function () {
                    $this->fail('The parser must not run after an extractor WP_Error.');
                },
            ]);

            $this->assertInstanceOf(\WP_Error::class, $result);
            $this->assertSame('synthetic_parse_error', $result->get_error_code());
            $this->assertFileDoesNotExist($private_path);
        } finally {
            unlink($source);
        }
    }

    public function test_docx_private_copy_is_deleted_when_parser_throws()
    {
        $source = $this->createSyntheticDocxSource();
        $private_path = null;
        $method = new \ReflectionMethod(REST_Controller::class, 'process_uploaded_report_doc');
        $method->setAccessible(true);

        try {
            try {
                $method->invokeArgs($this->controller, [
                    $source,
                    static function ($path) use (&$private_path) {
                        $private_path = $path;
                        return 'synthetic extracted text';
                    },
                    static function () {
                        throw new \RuntimeException('synthetic parser exception');
                    },
                ]);
                $this->fail('The synthetic parser exception was not propagated.');
            } catch (\RuntimeException $error) {
                $this->assertSame('synthetic parser exception', $error->getMessage());
            }
            $this->assertFileDoesNotExist($private_path);
        } finally {
            unlink($source);
        }
    }

    private function createSyntheticDocxSource()
    {
        $path = tempnam(sys_get_temp_dir(), 'cqa-docx-source-');
        file_put_contents($path, 'synthetic docx bytes');
        return $path;
    }
}

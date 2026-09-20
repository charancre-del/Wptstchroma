<?php
/** Real renderer compatibility checks with synthetic data and no provider calls. */
namespace ChromaQA\Export {
    function wp_upload_dir() { return ['basedir' => $GLOBALS['cqa_pdf_test_dir']]; }
    function wp_mkdir_p($path) { return is_dir($path) || mkdir($path, 0700, true); }
    function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
    function date_i18n($format, $timestamp = null) { return gmdate($format, $timestamp ?? 1700000000); }
}

namespace ChromaQA\Tests\Export {
    use ChromaQA\Checklists\Checklist_Manager;
    use ChromaQA\Export\PDF_Generator;
    use ChromaQA\Models\Report;
    use PHPUnit\Framework\TestCase;

    class DompdfCompatibilityTest extends TestCase
    {
        public function test_current_export_api_renders_comparison_and_plain_reports()
        {
            $cache = new \ReflectionProperty(Checklist_Manager::class, 'cache');
            $cache->setAccessible(true);
            $original_cache = $cache->getValue();
            $original_db = $GLOBALS['wpdb'] ?? null;
            $directory = getenv('CQA_PDF_TEST_OUTPUT_DIR') ?: sys_get_temp_dir() . '/cqa-pdf-' . bin2hex(random_bytes(8));
            $GLOBALS['cqa_pdf_test_dir'] = $directory;
            $GLOBALS['wpdb'] = new class {
                public $prefix = 'wp_';
                public function prepare($query, ...$args) { return $query; }
                public function get_results($query, $format) { return []; }
            };
            $cache->setValue(null, ['tier1' => ['sections' => [[
                'key' => 'synthetic', 'name' => 'Synthetic inspection criteria',
                'items' => [['key' => 'example', 'label' => 'Sample criterion - no production data']],
            ]]]]);
            $report = new class extends Report {
                public function get_school() { return (object) ['name' => 'Synthetic QA School', 'location' => 'Synthetic location']; }
                public function get_photos() { return []; }
                public function get_ai_summary() { return null; }
                public function get_previous_report() { $previous = clone $this; $previous->inspection_date = '2026-08-01'; return $previous; }
            };
            $report->id = 900001;
            $report->report_type = 'tier1';
            $report->inspection_date = '2026-09-01';
            $report->overall_rating = 'meets';
            $report->closing_notes = '';
            $files = [];
            try {
                $generator = new PDF_Generator();
                $this->assertTrue($generator->has_libraries());
                $html_method = new \ReflectionMethod(PDF_Generator::class, 'get_report_html');
                $html_method->setAccessible(true);
                foreach ([true, false] as $comparison) {
                    $html = $html_method->invoke($generator, $report, $comparison);
                    $this->assertStringContainsString('Synthetic QA School', $html);
                    $this->assertSame($comparison, strpos($html, '>Previous</th>') !== false);
                    $path = $generator->generate($report, $comparison);
                    $this->assertIsString($path);
                    $this->assertFileExists($path);
                    $bytes = file_get_contents($path);
                    $this->assertStringStartsWith('%PDF-', $bytes);
                    $this->assertGreaterThan(1000, strlen($bytes));
                    $this->assertStringContainsString('/Type /Page', $bytes);
                    $files[] = $path;
                    $report->id++;
                }
                // The security upgrade must not re-enable remote fetch or PHP.
                $options = new \Dompdf\Options();
                $this->assertFalse($options->getIsRemoteEnabled());
                $this->assertFalse($options->getIsPhpEnabled());
            } finally {
                $GLOBALS['wpdb'] = $original_db;
                $cache->setValue(null, $original_cache);
                unset($GLOBALS['cqa_pdf_test_dir']);
                if (!getenv('CQA_PDF_TEST_OUTPUT_DIR')) {
                    foreach ($files as $file) { unlink($file); }
                    if (is_dir($directory . '/cqa-temp')) { rmdir($directory . '/cqa-temp'); }
                    if (is_dir($directory)) { rmdir($directory); }
                }
            }
        }
    }
}

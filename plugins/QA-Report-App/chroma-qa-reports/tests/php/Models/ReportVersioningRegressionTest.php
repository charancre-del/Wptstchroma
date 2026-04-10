<?php
/**
 * Regression tests for report versioning and restore flows.
 */

namespace ChromaQA\AI {
    class Executive_Summary
    {
        public function save_summary($report_id, $summary)
        {
            global $wpdb;

            $table = $wpdb->prefix . 'cqa_ai_summaries';
            $wpdb->delete($table, ['report_id' => $report_id], ['%d']);

            return $wpdb->insert(
                $table,
                [
                    'report_id' => $report_id,
                    'executive_summary' => $summary['executive_summary'] ?? '',
                    'issues_json' => \wp_json_encode($summary['issues'] ?? []),
                    'poi_json' => \wp_json_encode($summary['poi'] ?? $summary['plan_of_improvement'] ?? []),
                    'comparison_json' => \wp_json_encode($summary['comparison'] ?? []),
                    'generated_at' => \current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%s', '%s']
            ) !== false;
        }
    }
}

namespace ChromaQA\Tests\Models {

use ChromaQA\Models\Checklist_Response;
use ChromaQA\Models\Photo;
use ChromaQA\Models\Report;
use ChromaQA\Models\Report_Snapshot;
use PHPUnit\Framework\TestCase;

class FakeWpdbForVersioning
{
    public $prefix = 'wp_';
    public $insert_id = 0;
    public $tables = [];
    private $nextIds = [];
    private $timeCounter = 0;

    public function __construct()
    {
        $this->tables = [
            'reports' => [],
            'responses' => [],
            'photos' => [],
            'report_snapshots' => [],
            'ai_summaries' => [],
        ];

        $this->nextIds = [
            'reports' => 1,
            'responses' => 1,
            'photos' => 1,
            'report_snapshots' => 1,
            'ai_summaries' => 1,
        ];
    }

    public function prepare($query, ...$args)
    {
        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }

        foreach ($args as $arg) {
            $replacement = is_numeric($arg) ? (string) (int) $arg : "'" . addslashes((string) $arg) . "'";
            $query = preg_replace('/%[ds]/', $replacement, $query, 1);
        }

        return $query;
    }

    public function insert($table, $data, $format = null)
    {
        $key = $this->normalizeTable($table);
        $row = $data;

        if (!isset($row['id'])) {
            $row['id'] = $this->nextIds[$key]++;
        }

        if ($key === 'reports') {
            $row['created_at'] = $row['created_at'] ?? $this->nextTimestamp();
            $row['updated_at'] = $row['updated_at'] ?? $this->nextTimestamp();
        }

        if (in_array($key, ['photos', 'report_snapshots', 'ai_summaries'], true)) {
            $row['created_at'] = $row['created_at'] ?? $this->nextTimestamp();
        }

        if ($key === 'responses') {
            $row['created_at'] = $row['created_at'] ?? $this->nextTimestamp();
        }

        $this->tables[$key][] = $row;
        $this->insert_id = (int) $row['id'];

        return 1;
    }

    public function update($table, $data, $where, $format = null, $where_format = null)
    {
        $key = $this->normalizeTable($table);
        $updated = 0;

        foreach ($this->tables[$key] as &$row) {
            if ($this->matches($row, $where)) {
                $row = array_merge($row, $data);
                if ($key === 'reports') {
                    $row['updated_at'] = $this->nextTimestamp();
                }
                $updated++;
            }
        }

        return $updated;
    }

    public function delete($table, $where, $where_format = null)
    {
        $key = $this->normalizeTable($table);
        $deleted = 0;

        foreach ($this->tables[$key] as $index => $row) {
            if ($this->matches($row, $where)) {
                unset($this->tables[$key][$index]);
                $deleted++;
            }
        }

        $this->tables[$key] = array_values($this->tables[$key]);

        return $deleted;
    }

    public function get_row($query, $output = OBJECT)
    {
        $rows = $this->findRows($query);
        $row = $rows ? $rows[0] : null;

        if ($row === null) {
            return null;
        }

        return $output === ARRAY_A ? $row : (object) $row;
    }

    public function get_results($query, $output = OBJECT)
    {
        $rows = $this->findRows($query);

        if ($output === ARRAY_A) {
            return $rows;
        }

        return array_map(static function ($row) {
            return (object) $row;
        }, $rows);
    }

    public function get_col($query)
    {
        if (preg_match('/SELECT id FROM .*report_snapshots.* WHERE report_id = (\d+) ORDER BY version_number DESC LIMIT (\d+)/i', $query, $matches)) {
            $reportId = (int) $matches[1];
            $limit = (int) $matches[2];
            $rows = array_values(array_filter($this->tables['report_snapshots'], static function ($row) use ($reportId) {
                return (int) $row['report_id'] === $reportId;
            }));

            usort($rows, static function ($a, $b) {
                return (int) $b['version_number'] <=> (int) $a['version_number'];
            });

            return array_map(static function ($row) {
                return $row['id'];
            }, array_slice($rows, 0, $limit));
        }

        return [];
    }

    public function get_var($query)
    {
        if (preg_match('/SELECT id FROM .*report_snapshots.* WHERE report_id = (\d+) AND version_number = (\d+)/i', $query, $matches)) {
            $reportId = (int) $matches[1];
            $version = (int) $matches[2];

            foreach ($this->tables['report_snapshots'] as $row) {
                if ((int) $row['report_id'] === $reportId && (int) $row['version_number'] === $version) {
                    return $row['id'];
                }
            }

            return null;
        }

        if (preg_match('/SELECT MAX\(version_number\) FROM .*report_snapshots.* WHERE report_id = (\d+)/i', $query, $matches)) {
            $reportId = (int) $matches[1];
            $max = 0;
            foreach ($this->tables['report_snapshots'] as $row) {
                if ((int) $row['report_id'] === $reportId) {
                    $max = max($max, (int) $row['version_number']);
                }
            }
            return $max;
        }

        return null;
    }

    public function query($query)
    {
        if (preg_match('/DELETE FROM .*report_snapshots.* WHERE report_id = (\d+) AND id NOT IN \(([^)]+)\)/i', $query, $matches)) {
            $reportId = (int) $matches[1];
            $keepIds = array_map('intval', array_map('trim', explode(',', $matches[2])));

            foreach ($this->tables['report_snapshots'] as $index => $row) {
                if ((int) $row['report_id'] === $reportId && !in_array((int) $row['id'], $keepIds, true)) {
                    unset($this->tables['report_snapshots'][$index]);
                }
            }

            $this->tables['report_snapshots'] = array_values($this->tables['report_snapshots']);
        }

        return true;
    }

    private function normalizeTable($table)
    {
        return preg_replace('/^' . preg_quote($this->prefix . 'cqa_', '/') . '/', '', $table);
    }

    private function nextTimestamp()
    {
        $timestamp = strtotime('2026-04-10 12:00:00 +' . $this->timeCounter . ' minutes');
        $this->timeCounter++;

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    private function matches($row, $where)
    {
        foreach ($where as $key => $value) {
            if (!array_key_exists($key, $row)) {
                return false;
            }

            if ((string) $row[$key] !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    private function findRows($query)
    {
        if (preg_match('/SELECT \* FROM .*reports.* WHERE id = (\d+)/i', $query, $matches)) {
            return array_values(array_filter($this->tables['reports'], static function ($row) use ($matches) {
                return (int) $row['id'] === (int) $matches[1];
            }));
        }

        if (preg_match('/SELECT \* FROM .*ai_summaries.* WHERE report_id = (\d+)/i', $query, $matches)) {
            return array_values(array_filter($this->tables['ai_summaries'], static function ($row) use ($matches) {
                return (int) $row['report_id'] === (int) $matches[1];
            }));
        }

        if (preg_match('/SELECT \* FROM .*responses.* WHERE report_id = (\d+) ORDER BY/i', $query, $matches)) {
            $rows = array_values(array_filter($this->tables['responses'], static function ($row) use ($matches) {
                return (int) $row['report_id'] === (int) $matches[1];
            }));

            usort($rows, static function ($a, $b) {
                return [$a['section_key'], $a['item_key']] <=> [$b['section_key'], $b['item_key']];
            });

            return $rows;
        }

        if (preg_match('/SELECT \* FROM .*photos.* WHERE report_id = (\d+) AND deleted_at IS NULL ORDER BY/i', $query, $matches)) {
            $rows = array_values(array_filter($this->tables['photos'], static function ($row) use ($matches) {
                return (int) $row['report_id'] === (int) $matches[1] && empty($row['deleted_at']);
            }));

            usort($rows, static function ($a, $b) {
                return [$a['section_key'] ?? '', (int) ($a['sort_order'] ?? 0)] <=> [$b['section_key'] ?? '', (int) ($b['sort_order'] ?? 0)];
            });

            return $rows;
        }

        if (preg_match('/SELECT id, rating, notes, evidence_type, previous_rating, previous_notes\s+FROM .*responses.* WHERE report_id = (\d+) AND section_key = \'([^\']+)\' AND item_key = \'([^\']+)\'/i', $query, $matches)) {
            return array_values(array_filter($this->tables['responses'], static function ($row) use ($matches) {
                return (int) $row['report_id'] === (int) $matches[1]
                    && (string) $row['section_key'] === stripslashes($matches[2])
                    && (string) $row['item_key'] === stripslashes($matches[3]);
            }));
        }

        if (preg_match('/SELECT \* FROM .*report_snapshots.* WHERE report_id = (\d+) AND version_number = (\d+)/i', $query, $matches)) {
            return array_values(array_filter($this->tables['report_snapshots'], static function ($row) use ($matches) {
                return (int) $row['report_id'] === (int) $matches[1]
                    && (int) $row['version_number'] === (int) $matches[2];
            }));
        }

        return [];
    }
}

class ReportVersioningRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        global $wpdb;
        $wpdb = new FakeWpdbForVersioning();
    }

    public function test_snapshot_refresh_and_restore_round_trip_preserve_full_report_state()
    {
        $report = new Report();
        $report->school_id = 11;
        $report->user_id = 7;
        $report->report_type = 'tier1';
        $report->inspection_date = '2026-04-01';
        $report->previous_report_id = 3;
        $report->overall_rating = 'meets';
        $report->closing_notes = 'Version 1 notes';
        $report->status = 'draft';

        $this->assertSame(1, $report->save());
        $this->assertSame(1, $report->version_id);

        Checklist_Response::bulk_save($report->id, [
            'health_safety' => [
                'ratios' => [
                    'rating' => 'yes',
                    'notes' => 'Version 1 response',
                ],
            ],
        ], true);

        $photo = new Photo();
        $photo->report_id = $report->id;
        $photo->section_key = 'health_safety';
        $photo->drive_file_id = 'wp_101';
        $photo->filename = 'version-1-photo.jpg';
        $photo->caption = 'Version 1 photo';
        $this->assertNotFalse($photo->save());

        $summary = [
            'executive_summary' => 'Version 1 summary',
            'issues' => [['title' => 'Issue 1']],
            'plan_of_improvement' => [['area' => 'Ratios']],
            'comparison' => [['field' => 'overall_rating']],
        ];
        $this->assertTrue((new \ChromaQA\AI\Executive_Summary())->save_summary($report->id, $summary));

        $this->assertNotFalse(Report_Snapshot::create_snapshot($report->id, 'Refresh version 1'));
        $this->assertCount(1, $this->snapshotRows());

        $versionOneSnapshot = Report_Snapshot::get_snapshot($report->id, 1);
        $this->assertSame('Version 1 notes', $versionOneSnapshot['snapshot_data']['report']['closing_notes']);
        $this->assertSame('Version 1 response', $versionOneSnapshot['snapshot_data']['responses']['health_safety']['ratios']['notes']);
        $this->assertSame('version-1-photo.jpg', $versionOneSnapshot['snapshot_data']['photos'][0]['filename']);
        $this->assertSame('Version 1 summary', $versionOneSnapshot['snapshot_data']['ai_summary']['executive_summary']);

        $report = Report::find($report->id);
        $report->inspection_date = '2026-04-09';
        $report->previous_report_id = null;
        $report->overall_rating = 'needs_improvement';
        $report->closing_notes = 'Version 2 notes';
        $report->status = 'submitted';
        $this->assertSame($report->id, $report->save('Report updated'));
        $this->assertSame(2, $report->version_id);

        Checklist_Response::bulk_save($report->id, [
            'health_safety' => [
                'ratios' => [
                    'rating' => 'no',
                    'notes' => 'Version 2 response',
                ],
            ],
        ], true);

        foreach (Photo::get_by_report($report->id) as $existingPhoto) {
            $this->assertTrue($existingPhoto->soft_delete());
        }

        $replacementPhoto = new Photo();
        $replacementPhoto->report_id = $report->id;
        $replacementPhoto->section_key = 'health_safety';
        $replacementPhoto->drive_file_id = 'wp_202';
        $replacementPhoto->filename = 'version-2-photo.jpg';
        $replacementPhoto->caption = 'Version 2 photo';
        $this->assertNotFalse($replacementPhoto->save());

        $this->assertTrue((new \ChromaQA\AI\Executive_Summary())->save_summary($report->id, [
            'executive_summary' => 'Version 2 summary',
            'issues' => [],
            'plan_of_improvement' => [],
            'comparison' => [],
        ]));

        $this->assertNotFalse(Report_Snapshot::create_snapshot($report->id, 'Refresh version 2'));
        $this->assertCount(2, $this->snapshotRows());

        $this->assertTrue(Report_Snapshot::restore($report->id, 1));

        $restoredReport = Report::find($report->id);
        $this->assertSame('2026-04-01', $restoredReport->inspection_date);
        $this->assertSame(3, $restoredReport->previous_report_id);
        $this->assertSame('meets', $restoredReport->overall_rating);
        $this->assertSame('Version 1 notes', $restoredReport->closing_notes);
        $this->assertSame('draft', $restoredReport->status);
        $this->assertSame(3, $restoredReport->version_id);

        $restoredResponses = Checklist_Response::get_by_report_array($report->id);
        $this->assertSame('yes', $restoredResponses['health_safety']['ratios']['rating']);
        $this->assertSame('Version 1 response', $restoredResponses['health_safety']['ratios']['notes']);

        $restoredPhotos = Photo::get_by_report_array($report->id);
        $this->assertCount(1, $restoredPhotos);
        $this->assertSame('version-1-photo.jpg', $restoredPhotos[0]['filename']);
        $this->assertSame('Version 1 photo', $restoredPhotos[0]['caption']);

        $restoredSummary = $restoredReport->get_ai_summary();
        $this->assertSame('Version 1 summary', $restoredSummary['executive_summary']);

        $versionThreeSnapshot = Report_Snapshot::get_snapshot($report->id, 3);
        $this->assertSame('Version 1 notes', $versionThreeSnapshot['snapshot_data']['report']['closing_notes']);
        $this->assertSame('Version 1 summary', $versionThreeSnapshot['snapshot_data']['ai_summary']['executive_summary']);
    }

    public function test_force_replace_with_empty_payload_clears_existing_responses()
    {
        $report = new Report();
        $report->school_id = 33;
        $report->user_id = 9;
        $report->report_type = 'tier1';
        $report->inspection_date = '2026-04-10';
        $report->status = 'draft';
        $this->assertSame(1, $report->save());

        $this->assertTrue(Checklist_Response::bulk_save($report->id, [
            'admin' => [
                'licensing' => [
                    'rating' => 'yes',
                    'notes' => 'Existing note',
                ],
            ],
        ], true));

        $this->assertNotEmpty(Checklist_Response::get_by_report_array($report->id));
        $this->assertTrue(Checklist_Response::bulk_save($report->id, [], true));
        $this->assertSame([], Checklist_Response::get_by_report_array($report->id));
    }

    private function snapshotRows()
    {
        global $wpdb;
        return $wpdb->tables['report_snapshots'];
    }
}

}

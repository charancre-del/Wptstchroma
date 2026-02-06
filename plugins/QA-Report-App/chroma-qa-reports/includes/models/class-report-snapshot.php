<?php
/**
 * Report Snapshot Model (Versioning)
 *
 * Handles point-in-time snapshots of reports for version history and recovery.
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Models;

/**
 * Manages report snapshots for version control.
 */
class Report_Snapshot
{
    /**
     * Table name.
     */
    private static $table = 'cqa_report_snapshots';

    /**
     * Maximum versions to keep per report.
     */
    const MAX_VERSIONS = 20;

    /**
     * Snapshot properties.
     */
    public $id;
    public $report_id;
    public $version_number;
    public $snapshot_data;
    public $change_summary;
    public $user_id;
    public $created_at;

    /**
     * Get the full table name.
     *
     * @return string
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . self::$table;
    }

    /**
     * Create a snapshot of the current report state.
     *
     * @param int    $report_id      Report ID.
     * @param string $change_summary Description of what changed.
     * @return int|false Snapshot ID or false on failure.
     */
    public static function create_snapshot($report_id, $change_summary = '')
    {
        global $wpdb;
        $table = self::get_table_name();

        // Get the report
        $report = Report::find($report_id);
        if (!$report) {
            return false;
        }

        // Get current version number
        $current_version = self::get_latest_version($report_id);
        $new_version = $current_version + 1;

        // Collect full state
        $snapshot_data = [
            'report' => self::serialize_report($report),
            'responses' => Checklist_Response::get_by_report_array($report_id),
            'photos' => Photo::get_by_report_array($report_id),
            'ai_summary' => $report->get_ai_summary(),
            'snapshot_meta' => [
                'captured_at' => current_time('mysql'),
                'plugin_version' => defined('CQA_VERSION') ? CQA_VERSION : '1.0.0',
            ],
        ];

        $result = $wpdb->insert(
            $table,
            [
                'report_id' => $report_id,
                'version_number' => $new_version,
                'snapshot_data' => wp_json_encode($snapshot_data),
                'change_summary' => \sanitize_text_field($change_summary),
                'user_id' => \get_current_user_id(),
            ],
            ['%d', '%d', '%s', '%s', '%d']
        );

        if ($result) {
            // Prune old versions
            self::prune_old_versions($report_id);
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Serialize report object to array.
     *
     * @param Report $report Report object.
     * @return array
     */
    private static function serialize_report($report)
    {
        return [
            'id' => $report->id,
            'school_id' => $report->school_id,
            'user_id' => $report->user_id,
            'report_type' => $report->report_type,
            'inspection_date' => $report->inspection_date,
            'previous_report_id' => $report->previous_report_id,
            'overall_rating' => $report->overall_rating,
            'closing_notes' => $report->closing_notes,
            'status' => $report->status,
            'version_id' => $report->version_id,
            'created_at' => $report->created_at,
            'updated_at' => $report->updated_at,
        ];
    }

    /**
     * Get the latest version number for a report.
     *
     * @param int $report_id Report ID.
     * @return int
     */
    public static function get_latest_version($report_id)
    {
        global $wpdb;
        $table = self::get_table_name();

        $version = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(version_number) FROM {$table} WHERE report_id = %d",
            $report_id
        ));

        return (int) $version;
    }

    /**
     * Get all versions for a report.
     *
     * @param int $report_id Report ID.
     * @return array
     */
    public static function get_versions($report_id)
    {
        global $wpdb;
        $table = self::get_table_name();
        $users_table = $wpdb->users;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id, s.version_number, s.change_summary, s.user_id, s.created_at, u.display_name as user_name
             FROM {$table} s
             LEFT JOIN {$users_table} u ON s.user_id = u.ID
             WHERE s.report_id = %d
             ORDER BY s.version_number DESC",
            $report_id
        ), 'ARRAY_A');

        return $rows ?: [];
    }

    /**
     * Get a specific snapshot.
     *
     * @param int $report_id      Report ID.
     * @param int $version_number Version number.
     * @return array|null
     */
    public static function get_snapshot($report_id, $version_number)
    {
        global $wpdb;
        $table = self::get_table_name();

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE report_id = %d AND version_number = %d",
            $report_id,
            $version_number
        ), 'ARRAY_A');

        if ($row) {
            $row['snapshot_data'] = json_decode($row['snapshot_data'], true);
        }

        return $row;
    }

    /**
     * Restore a report to a previous version.
     *
     * @param int $report_id      Report ID.
     * @param int $version_number Version to restore.
     * @return bool
     */
    public static function restore($report_id, $version_number)
    {
        global $wpdb;

        // Get the snapshot
        $snapshot = self::get_snapshot($report_id, $version_number);
        if (!$snapshot || empty($snapshot['snapshot_data'])) {
            return false;
        }

        // Create a new snapshot of current state before restoring
        self::create_snapshot($report_id, "Before restoring to v{$version_number}");

        $data = $snapshot['snapshot_data'];

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Restore report fields
            if (!empty($data['report'])) {
                $report = Report::find($report_id);
                if ($report) {
                    $report->overall_rating = $data['report']['overall_rating'];
                    $report->closing_notes = $data['report']['closing_notes'];
                    $report->status = $data['report']['status'];
                    $report->save("Restored to version {$version_number}");
                }
            }

            // Restore responses (using force_replace to completely replace)
            if (!empty($data['responses'])) {
                Checklist_Response::bulk_save($report_id, $data['responses'], true);
            }

            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Report_Snapshot::restore failed: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Prune old versions beyond the retention limit.
     *
     * @param int $report_id Report ID.
     * @param int $keep      Number of versions to keep.
     * @return int Number of deleted versions.
     */
    public static function prune_old_versions($report_id, $keep = null)
    {
        global $wpdb;
        $table = self::get_table_name();

        if ($keep === null) {
            $settings = \get_option('cqa_settings', []);
            $keep = isset($settings['max_report_versions']) ? (int) $settings['max_report_versions'] : self::MAX_VERSIONS;
        }

        // Get IDs of versions to keep
        $keep_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$table} WHERE report_id = %d ORDER BY version_number DESC LIMIT %d",
            $report_id,
            $keep
        ));

        if (empty($keep_ids)) {
            return 0;
        }

        // Delete older versions
        $keep_ids_string = implode(',', array_map('intval', $keep_ids));
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE report_id = %d AND id NOT IN ({$keep_ids_string})",
            $report_id
        ));

        return (int) $deleted;
    }

    /**
     * Delete a specific version.
     *
     * @param int $snapshot_id Snapshot ID.
     * @return bool
     */
    public static function delete($snapshot_id)
    {
        global $wpdb;
        $table = self::get_table_name();

        $result = $wpdb->delete($table, ['id' => $snapshot_id], ['%d']);
        return $result !== false;
    }

    /**
     * Compare two versions and return differences.
     *
     * @param int $report_id Report ID.
     * @param int $version_a  First version.
     * @param int $version_b  Second version.
     * @return array
     */
    public static function compare_versions($report_id, $version_a, $version_b)
    {
        $snapshot_a = self::get_snapshot($report_id, $version_a);
        $snapshot_b = self::get_snapshot($report_id, $version_b);

        if (!$snapshot_a || !$snapshot_b) {
            return ['error' => 'Version not found'];
        }

        $data_a = $snapshot_a['snapshot_data'];
        $data_b = $snapshot_b['snapshot_data'];

        $diff = [
            'report_changes' => [],
            'response_changes' => [],
            'photo_changes' => [],
        ];

        // Compare report fields
        if (!empty($data_a['report']) && !empty($data_b['report'])) {
            foreach ($data_a['report'] as $key => $value) {
                if (isset($data_b['report'][$key]) && $data_b['report'][$key] !== $value) {
                    $diff['report_changes'][$key] = [
                        'from' => $value,
                        'to' => $data_b['report'][$key],
                    ];
                }
            }
        }

        // Compare response counts
        $responses_a = $data_a['responses'] ?? [];
        $responses_b = $data_b['responses'] ?? [];

        $diff['response_changes'] = [
            'count_before' => self::count_nested($responses_a),
            'count_after' => self::count_nested($responses_b),
        ];

        // Compare photo counts
        $photos_a = $data_a['photos'] ?? [];
        $photos_b = $data_b['photos'] ?? [];

        $diff['photo_changes'] = [
            'count_before' => count($photos_a),
            'count_after' => count($photos_b),
        ];

        return $diff;
    }

    /**
     * Count items in nested array.
     *
     * @param array $array Nested array.
     * @return int
     */
    private static function count_nested($array)
    {
        $count = 0;
        foreach ($array as $section) {
            if (is_array($section)) {
                $count += count($section);
            }
        }
        return $count;
    }
}

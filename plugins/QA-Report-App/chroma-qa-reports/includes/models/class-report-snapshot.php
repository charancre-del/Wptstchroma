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

        $version_number = max(1, (int) $report->version_id);

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

        $payload = [
            'report_id' => $report_id,
            'version_number' => $version_number,
            'snapshot_data' => wp_json_encode($snapshot_data),
            'change_summary' => \sanitize_text_field($change_summary),
            'user_id' => \get_current_user_id(),
        ];
        $format = ['%d', '%d', '%s', '%s', '%d'];

        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE report_id = %d AND version_number = %d LIMIT 1",
            $report_id,
            $version_number
        ));

        if ($existing_id) {
            $result = $wpdb->update(
                $table,
                $payload,
                ['id' => (int) $existing_id],
                $format,
                ['%d']
            );
        } else {
            $result = $wpdb->insert($table, $payload, $format);
        }

        if ($result !== false) {
            // Prune old versions
            self::prune_old_versions($report_id);
            return $existing_id ? (int) $existing_id : (int) $wpdb->insert_id;
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
        // Get the snapshot
        $snapshot = self::get_snapshot($report_id, $version_number);
        if (!$snapshot || empty($snapshot['snapshot_data'])) {
            return false;
        }

        $data = $snapshot['snapshot_data'];
        $report = Report::find($report_id);
        if (!$report) {
            return false;
        }

        if (!empty($data['report']) && is_array($data['report'])) {
            $report_data = $data['report'];
            $report->school_id = isset($report_data['school_id']) ? (int) $report_data['school_id'] : $report->school_id;
            $report->report_type = $report_data['report_type'] ?? $report->report_type;
            $report->inspection_date = $report_data['inspection_date'] ?? $report->inspection_date;
            $report->previous_report_id = !empty($report_data['previous_report_id']) ? (int) $report_data['previous_report_id'] : null;
            $report->overall_rating = $report_data['overall_rating'] ?? $report->overall_rating;
            $report->closing_notes = $report_data['closing_notes'] ?? '';
            $report->status = $report_data['status'] ?? $report->status;
        }

        if (!$report->save("Restored to version {$version_number}")) {
            return false;
        }

        if (!Checklist_Response::bulk_save($report_id, $data['responses'] ?? [], true)) {
            return false;
        }

        if (!self::restore_photos($report_id, $data['photos'] ?? [])) {
            return false;
        }

        if (!self::restore_ai_summary($report_id, $data['ai_summary'] ?? null)) {
            return false;
        }

        return self::create_snapshot($report_id, "Restored to version {$version_number}") !== false;
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

    /**
     * Restore AI summary for a report.
     *
     * @param int        $report_id Report ID.
     * @param array|null $summary   Snapshot summary data.
     * @return bool
     */
    private static function restore_ai_summary($report_id, $summary)
    {
        global $wpdb;

        if (empty($summary) || !is_array($summary)) {
            return $wpdb->delete($wpdb->prefix . 'cqa_ai_summaries', ['report_id' => $report_id], ['%d']) !== false;
        }

        $ai = new \ChromaQA\AI\Executive_Summary();
        return $ai->save_summary($report_id, $summary);
    }

    /**
     * Restore the active photo set for a report from snapshot metadata.
     *
     * @param int   $report_id Report ID.
     * @param array $photos    Snapshot photos.
     * @return bool
     */
    private static function restore_photos($report_id, $photos)
    {
        $existing_photos = Photo::get_by_report($report_id);
        foreach ($existing_photos as $existing_photo) {
            if (!$existing_photo->soft_delete()) {
                return false;
            }
        }

        foreach ($photos as $photo_data) {
            if (!is_array($photo_data)) {
                continue;
            }

            $photo = new Photo();
            $photo->report_id = $report_id;
            $photo->section_key = \sanitize_text_field((string) ($photo_data['section_key'] ?? 'general'));
            $photo->item_key = \sanitize_text_field((string) ($photo_data['item_key'] ?? ''));
            $photo->location_tag = \sanitize_text_field((string) ($photo_data['location_tag'] ?? ''));
            $photo->drive_file_id = \sanitize_text_field((string) ($photo_data['drive_file_id'] ?? ''));
            $photo->filename = \sanitize_text_field((string) ($photo_data['filename'] ?? ''));
            $photo->caption = \sanitize_text_field((string) ($photo_data['caption'] ?? ''));
            $photo->has_markup = !empty($photo_data['has_markup']);
            $photo->sort_order = isset($photo_data['sort_order']) ? (int) $photo_data['sort_order'] : 0;

            if (!$photo->save()) {
                return false;
            }
        }

        return true;
    }
}

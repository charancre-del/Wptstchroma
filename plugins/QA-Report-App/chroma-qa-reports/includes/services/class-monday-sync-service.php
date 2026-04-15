<?php
/**
 * monday.com Report Sync Service
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Services;

use ChromaQA\Integrations\Monday;
use ChromaQA\Models\Report;
use ChromaQA\Models\School;
use WP_Error;

class Monday_Sync_Service
{
    /**
     * Sync a report's POI items to monday.com.
     *
     * @param int    $report_id Report ID.
     * @param string $trigger Sync trigger context.
     * @return array|WP_Error
     */
    public static function sync_report($report_id, $trigger = 'manual')
    {
        $report = Report::find((int) $report_id);
        if (!$report) {
            return new WP_Error('cqa_monday_report_missing', __('Report not found.', 'chroma-qa-reports'));
        }

        if ($report->status !== Report::STATUS_APPROVED) {
            return new WP_Error('cqa_monday_report_not_approved', __('Only approved reports can sync to monday.com.', 'chroma-qa-reports'));
        }

        if (!Monday::is_enabled()) {
            $error = new WP_Error('cqa_monday_disabled', __('monday.com sync is disabled in settings.', 'chroma-qa-reports'));
            self::persist_report_status($report, 'error', $error->get_error_message());
            return $error;
        }

        $school = School::find($report->school_id);
        if (!$school) {
            $error = new WP_Error('cqa_monday_school_missing', __('The report school could not be found.', 'chroma-qa-reports'));
            self::persist_report_status($report, 'error', $error->get_error_message());
            return $error;
        }

        if (empty($school->monday_board_id)) {
            $error = new WP_Error('cqa_monday_board_missing', __('This school is not mapped to a monday.com board.', 'chroma-qa-reports'));
            self::persist_report_status($report, 'error', $error->get_error_message());
            return $error;
        }

        $mapping = self::school_mapping_array($school);
        foreach (array_keys(Monday::required_columns()) as $required_field) {
            if (empty($mapping[$required_field])) {
                $error = new WP_Error('cqa_monday_columns_missing', __('This school board is missing required monday.com column mappings.', 'chroma-qa-reports'));
                self::persist_report_status($report, 'error', $error->get_error_message());
                return $error;
            }
        }

        $poi_items = self::normalize_poi_items($report);
        if (empty($poi_items)) {
            $error = new WP_Error('cqa_monday_no_poi', __('This report has no plan of improvement items to sync.', 'chroma-qa-reports'));
            self::persist_report_status($report, 'error', $error->get_error_message());
            return $error;
        }

        $group = self::ensure_report_group($report, $school);
        if (is_wp_error($group)) {
            self::persist_report_status($report, 'error', $group->get_error_message());
            return $group;
        }

        $existing_mappings = self::get_item_mappings($report->id);
        $seen_keys = [];
        $created = 0;
        $updated = 0;
        $retired = 0;

        foreach ($poi_items as $poi) {
            $poi_key = $poi['poi_key'];
            $seen_keys[] = $poi_key;
            $hash = self::hash_poi_item($poi);

            $item_name = self::item_name($poi);
            $base_values = [
                'priority' => self::priority_label($poi),
                'date' => self::due_date($poi),
                'person_id' => $school->monday_default_person_id ?: '',
            ];

            if (isset($existing_mappings[$poi_key])) {
                $row = $existing_mappings[$poi_key];
                $existing_item = Monday::get_item($row['monday_item_id']);
                if (is_wp_error($existing_item)) {
                    self::persist_report_status($report, 'error', $existing_item->get_error_message());
                    return $existing_item;
                }

                if (!$existing_item) {
                    $created_item = self::create_poi_item($school, $mapping, $group['id'], $item_name, $base_values);
                    if (is_wp_error($created_item)) {
                        self::persist_report_status($report, 'error', $created_item->get_error_message());
                        return $created_item;
                    }

                    self::upsert_item_mapping($report->id, $poi_key, $created_item['id'], $group['id'], $hash);
                    $created++;
                    continue;
                }

                if (($row['last_synced_hash'] ?? '') !== $hash) {
                    $rename = Monday::rename_item($row['monday_item_id'], $item_name);
                    if (is_wp_error($rename)) {
                        self::persist_report_status($report, 'error', $rename->get_error_message());
                        return $rename;
                    }

                    $update = Monday::update_item(
                        $school->monday_board_id,
                        $row['monday_item_id'],
                        Monday::format_column_values($mapping, $base_values),
                        false
                    );

                    if (is_wp_error($update)) {
                        self::persist_report_status($report, 'error', $update->get_error_message());
                        return $update;
                    }

                    $updated++;
                }

                self::upsert_item_mapping($report->id, $poi_key, $row['monday_item_id'], $group['id'], $hash);
                continue;
            }

            $create_values = $base_values + [
                'status' => Monday::default_status_label(),
                'notes' => self::notes_text($poi),
            ];

            $created_item = self::create_poi_item($school, $mapping, $group['id'], $item_name, $create_values);

            if (is_wp_error($created_item)) {
                self::persist_report_status($report, 'error', $created_item->get_error_message());
                return $created_item;
            }

            self::upsert_item_mapping($report->id, $poi_key, $created_item['id'], $group['id'], $hash);
            $created++;
        }

        foreach ($existing_mappings as $poi_key => $row) {
            if (in_array($poi_key, $seen_keys, true)) {
                continue;
            }

            $retire = Monday::update_item(
                $school->monday_board_id,
                $row['monday_item_id'],
                Monday::format_column_values($mapping, [
                    'status' => Monday::REMOVED_STATUS,
                ]),
                true
            );

            if (is_wp_error($retire)) {
                self::persist_report_status($report, 'error', $retire->get_error_message());
                return $retire;
            }

            $retired++;
        }

        self::persist_report_status($report, 'synced', '', $group['id']);

        return [
            'group_id' => $group['id'],
            'group_name' => $group['title'],
            'created' => $created,
            'updated' => $updated,
            'retired' => $retired,
            'total' => count($poi_items),
            'trigger' => $trigger,
        ];
    }

    /**
     * Build stable school mapping array.
     *
     * @param School $school School model.
     * @return array
     */
    private static function school_mapping_array($school)
    {
        return [
            'monday_board_id' => $school->monday_board_id,
            'monday_board_name' => $school->monday_board_name,
            'monday_status_column_id' => $school->monday_status_column_id,
            'monday_priority_column_id' => $school->monday_priority_column_id,
            'monday_date_column_id' => $school->monday_date_column_id,
            'monday_notes_column_id' => $school->monday_notes_column_id,
            'monday_person_column_id' => $school->monday_person_column_id,
            'monday_default_person_id' => $school->monday_default_person_id,
        ];
    }

    /**
     * Ensure the monday group for a report exists.
     *
     * @param Report $report Report.
     * @param School $school School.
     * @return array|WP_Error
     */
    private static function ensure_report_group($report, $school)
    {
        $group_title = self::build_group_name($report);

        if (!empty($report->monday_group_id)) {
            $existing_by_id = Monday::find_group($school->monday_board_id, $report->monday_group_id);
            if (is_wp_error($existing_by_id)) {
                return $existing_by_id;
            }

            if ($existing_by_id) {
                return [
                    'id' => (string) $existing_by_id['id'],
                    'title' => $existing_by_id['title'] ?? $group_title,
                ];
            }
        }

        $existing = Monday::find_group_by_title($school->monday_board_id, $group_title);
        if (is_wp_error($existing)) {
            return $existing;
        }

        if ($existing) {
            return [
                'id' => (string) $existing['id'],
                'title' => $existing['title'],
            ];
        }

        $created = Monday::create_group($school->monday_board_id, $group_title);
        if (is_wp_error($created)) {
            return $created;
        }

        return [
            'id' => (string) ($created['id'] ?? ''),
            'title' => $created['title'] ?? $group_title,
        ];
    }

    /**
     * Create a monday item for a POI row.
     *
     * @param School  $school School model.
     * @param array   $mapping Monday mapping array.
     * @param string  $group_id monday group ID.
     * @param string  $item_name monday item title.
     * @param array   $values Column values.
     * @return array|WP_Error
     */
    private static function create_poi_item($school, $mapping, $group_id, $item_name, $values)
    {
        return Monday::create_item(
            $school->monday_board_id,
            $group_id,
            $item_name,
            Monday::format_column_values($mapping, $values)
        );
    }

    /**
     * Normalize report POI into stable sync items.
     *
     * @param Report $report Report model.
     * @return array<int,array<string,mixed>>
     */
    private static function normalize_poi_items($report)
    {
        $summary = $report->get_ai_summary();
        $items = $summary['plan_of_improvement'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['issue'] ?? $item['title'] ?? $item['item'] ?? ''));
            if ($title === '') {
                continue;
            }

            $root_key = trim((string) ($item['root_key'] ?? ''));
            $section_key = trim((string) ($item['section_key'] ?? ''));
            $item_key = trim((string) ($item['item_key'] ?? ''));
            $source_items = $item['source_items'] ?? [];

            $poi_key = $root_key;
            if ($poi_key === '' && !empty($source_items) && is_array($source_items)) {
                $poi_key = 'source:' . md5(wp_json_encode($source_items));
            }

            if ($poi_key === '' && $section_key !== '' && $item_key !== '') {
                $poi_key = "{$section_key}.{$item_key}";
            }

            if ($poi_key === '') {
                $poi_key = 'poi_' . ($index + 1) . '_' . md5($title);
            }

            $normalized[] = $item + [
                'poi_key' => $poi_key,
                'issue' => $title,
            ];
        }

        return $normalized;
    }

    /**
     * Build monday group title for a report.
     *
     * @param Report $report Report model.
     * @return string
     */
    private static function build_group_name($report)
    {
        $timestamp = strtotime((string) $report->inspection_date);
        if (!$timestamp) {
            $timestamp = current_time('timestamp');
        }

        return sprintf(
            'QA Report - %s',
            date_i18n('F j, Y', $timestamp)
        );
    }

    /**
     * Resolve monday item display name.
     *
     * @param array $poi POI item.
     * @return string
     */
    private static function item_name($poi)
    {
        return trim((string) ($poi['issue'] ?? $poi['title'] ?? 'POI Item'));
    }

    /**
     * Resolve priority label from POI item.
     *
     * @param array $poi POI item.
     * @return string
     */
    private static function priority_label($poi)
    {
        $value = strtolower(trim((string) ($poi['priority'] ?? $poi['severity'] ?? '')));

        if (in_array($value, ['critical', 'high'], true)) {
            return 'High';
        }

        if (in_array($value, ['medium', 'moderate'], true)) {
            return 'Medium';
        }

        return 'Low';
    }

    /**
     * Resolve due date as monday yyyy-mm-dd.
     *
     * @param array $poi POI item.
     * @return string
     */
    private static function due_date($poi)
    {
        $candidates = [
            $poi['due_date'] ?? '',
            $poi['target_date'] ?? '',
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            $timestamp = strtotime($candidate);
            if ($timestamp) {
                return gmdate('Y-m-d', $timestamp);
            }
        }

        return '';
    }

    /**
     * Build initial monday notes text for first sync only.
     *
     * @param array $poi POI item.
     * @return string
     */
    private static function notes_text($poi)
    {
        $parts = [];
        foreach (['recommendation', 'action', 'action_steps', 'notes', 'timeline'] as $key) {
            if (empty($poi[$key])) {
                continue;
            }

            $label = ucwords(str_replace('_', ' ', $key));
            $value = is_array($poi[$key]) ? implode('; ', array_filter(array_map('trim', $poi[$key]))) : trim((string) $poi[$key]);
            if ($value !== '') {
                $parts[] = "{$label}: {$value}";
            }
        }

        return implode("\n", $parts);
    }

    /**
     * Hash the sync-owned portions of a POI item.
     *
     * @param array $poi POI item.
     * @return string
     */
    private static function hash_poi_item($poi)
    {
        return md5(
            wp_json_encode(
                [
                    'name' => self::item_name($poi),
                    'priority' => self::priority_label($poi),
                    'date' => self::due_date($poi),
                ]
            )
        );
    }

    /**
     * Read existing monday mapping rows for a report.
     *
     * @param int $report_id Report ID.
     * @return array<string,array<string,string>>
     */
    private static function get_item_mappings($report_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'cqa_monday_poi_syncs';
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE report_id = %d", $report_id),
            ARRAY_A
        );

        $mapped = [];
        foreach ($rows as $row) {
            $mapped[$row['poi_key']] = $row;
        }

        return $mapped;
    }

    /**
     * Insert or update an item mapping row.
     *
     * @param int    $report_id Report ID.
     * @param string $poi_key Stable POI key.
     * @param string $monday_item_id monday item ID.
     * @param string $monday_group_id monday group ID.
     * @param string $hash Sync hash.
     * @return void
     */
    private static function upsert_item_mapping($report_id, $poi_key, $monday_item_id, $monday_group_id, $hash)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'cqa_monday_poi_syncs';
        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE report_id = %d AND poi_key = %s LIMIT 1",
                $report_id,
                $poi_key
            )
        );

        $data = [
            'report_id' => (int) $report_id,
            'poi_key' => $poi_key,
            'monday_item_id' => (string) $monday_item_id,
            'monday_group_id' => (string) $monday_group_id,
            'last_synced_hash' => (string) $hash,
        ];

        $format = ['%d', '%s', '%s', '%s', '%s'];

        if ($existing_id) {
            $wpdb->update($table, $data, ['id' => (int) $existing_id], $format, ['%d']);
            return;
        }

        $wpdb->insert($table, $data, $format);
    }

    /**
     * Persist monday report sync status.
     *
     * @param Report  $report Report model.
     * @param string  $status Sync status.
     * @param string  $error Error message.
     * @param string  $group_id monday group ID.
     * @return void
     */
    private static function persist_report_status($report, $status, $error = '', $group_id = '')
    {
        $report->update_monday_sync_meta(
            [
                'monday_group_id' => $group_id !== '' ? $group_id : $report->monday_group_id,
                'monday_last_synced_at' => current_time('mysql'),
                'monday_sync_status' => $status,
                'monday_sync_error' => $error,
            ]
        );
    }
}

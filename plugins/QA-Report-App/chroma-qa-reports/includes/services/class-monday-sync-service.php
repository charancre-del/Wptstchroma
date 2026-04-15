<?php
/**
 * monday.com Report Sync Service
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Services;

use ChromaQA\Checklists\Checklist_Manager;
use ChromaQA\Integrations\Monday;
use ChromaQA\Models\Checklist_Response;
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
            return new WP_Error('cqa_monday_report_missing', __('Report not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        if ($report->status !== Report::STATUS_APPROVED) {
            return new WP_Error('cqa_monday_report_not_approved', __('Only approved reports can sync to monday.com.', 'chroma-qa-reports'), ['status' => 409]);
        }

        if (!Monday::is_enabled() && $trigger !== 'manual') {
            $error = new WP_Error('cqa_monday_disabled', __('monday.com sync is disabled in settings.', 'chroma-qa-reports'), ['status' => 422]);
            self::persist_report_status($report, 'error', $error->get_error_message());
            return $error;
        }

        $school = School::find($report->school_id);
        if (!$school) {
            $error = new WP_Error('cqa_monday_school_missing', __('The report school could not be found.', 'chroma-qa-reports'), ['status' => 404]);
            self::persist_report_status($report, 'error', $error->get_error_message());
            return $error;
        }

        if (empty($school->monday_board_id)) {
            $error = new WP_Error('cqa_monday_board_missing', __('This school is not mapped to a monday.com board.', 'chroma-qa-reports'), ['status' => 422]);
            self::persist_report_status($report, 'error', $error->get_error_message());
            return $error;
        }

        $mapping = self::school_mapping_array($school);
        $ensured_columns = Monday::ensure_board_columns($school->monday_board_id, $mapping);
        if (is_wp_error($ensured_columns)) {
            self::persist_report_status($report, 'error', $ensured_columns->get_error_message());
            return $ensured_columns;
        }

        $mapping = array_merge($mapping, $ensured_columns['mapping'] ?? []);
        $school->monday_board_name = (string) ($ensured_columns['board_name'] ?? $school->monday_board_name);
        $school->monday_status_column_id = (string) ($mapping['monday_status_column_id'] ?? $school->monday_status_column_id);
        $school->monday_priority_column_id = (string) ($mapping['monday_priority_column_id'] ?? $school->monday_priority_column_id);
        $school->monday_date_column_id = (string) ($mapping['monday_date_column_id'] ?? $school->monday_date_column_id);
        $school->monday_notes_column_id = (string) ($mapping['monday_notes_column_id'] ?? $school->monday_notes_column_id);
        $school->monday_person_column_id = (string) ($mapping['monday_person_column_id'] ?? $school->monday_person_column_id);
        $school->save();

        $missing_mapping_fields = [];
        foreach (array_keys(Monday::required_columns()) as $required_field) {
            if (empty($mapping[$required_field])) {
                $missing_mapping_fields[] = $required_field;
            }
        }

        if (!empty($missing_mapping_fields)) {
            $error = new WP_Error(
                'cqa_monday_columns_missing',
                sprintf(
                    __('This school board is missing required monday.com column mappings after validation: %s.', 'chroma-qa-reports'),
                    implode(', ', $missing_mapping_fields)
                ),
                ['status' => 422]
            );
            self::persist_report_status($report, 'error', $error->get_error_message());
            return $error;
        }

        $poi_items = self::normalize_poi_items($report);
        if (empty($poi_items)) {
            $error = new WP_Error('cqa_monday_no_poi', __('This report has no plan of improvement items or non-compliant findings to sync.', 'chroma-qa-reports'), ['status' => 422]);
            self::persist_report_status($report, 'error', $error->get_error_message());
            return $error;
        }

        $group = self::ensure_report_group($report, $school);
        if (is_wp_error($group)) {
            self::persist_report_status($report, 'error', $group->get_error_message());
            return $group;
        }

        self::persist_report_status($report, 'syncing', '', (string) ($group['id'] ?? ''));

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
                    $created_item = self::create_poi_item(
                        $school,
                        $mapping,
                        $group['id'],
                        $item_name,
                        $base_values + [
                            'status' => Monday::default_status_label(),
                            'notes' => self::notes_text($poi),
                        ]
                    );
                    if (is_wp_error($created_item)) {
                        self::persist_report_status($report, 'error', $created_item->get_error_message(), $group['id']);
                        return $created_item;
                    }

                    self::upsert_item_mapping($report->id, $poi_key, $created_item['id'], $group['id'], $hash);
                    $created++;
                    continue;
                }

                if (($row['last_synced_hash'] ?? '') !== $hash) {
                    $rename = Monday::rename_item($row['monday_item_id'], $item_name);
                    if (is_wp_error($rename)) {
                        self::persist_report_status($report, 'error', $rename->get_error_message(), $group['id']);
                        return $rename;
                    }

                    $update = Monday::update_item(
                        $school->monday_board_id,
                        $row['monday_item_id'],
                        Monday::format_column_values($mapping, $base_values),
                        true
                    );

                    if (is_wp_error($update)) {
                        self::persist_report_status($report, 'error', $update->get_error_message(), $group['id']);
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
                self::persist_report_status($report, 'error', $created_item->get_error_message(), $group['id']);
                return $created_item;
            }

            self::upsert_item_mapping($report->id, $poi_key, $created_item['id'], $group['id'], $hash);
            $created++;
        }

        foreach ($existing_mappings as $poi_key => $row) {
            if (in_array($poi_key, $seen_keys, true)) {
                continue;
            }

            $existing_item = Monday::get_item($row['monday_item_id']);
            if (is_wp_error($existing_item)) {
                self::persist_report_status($report, 'error', $existing_item->get_error_message(), $group['id']);
                return $existing_item;
            }

            if (!$existing_item) {
                self::delete_item_mapping($report->id, $poi_key);
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
                self::persist_report_status($report, 'error', $retire->get_error_message(), $group['id']);
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
        $items = is_array($summary)
            ? ($summary['plan_of_improvement'] ?? $summary['poi'] ?? [])
            : [];

        if (is_array($items)) {
            $normalized = self::normalize_raw_poi_items($items);
            if (!empty($normalized)) {
                return $normalized;
            }
        }

        return self::build_fallback_poi_items($report);
    }

    /**
     * Normalize raw POI items into stable sync items.
     *
     * @param array $items Raw POI items.
     * @return array<int,array<string,mixed>>
     */
    private static function normalize_raw_poi_items($items)
    {
        $normalized = [];

        foreach ($items as $index => $item) {
            if (is_string($item)) {
                $title = trim($item);
                if ($title === '') {
                    continue;
                }

                $normalized[] = [
                    'poi_key' => 'poi_' . ($index + 1) . '_' . md5($title),
                    'issue' => $title,
                    'action' => $title,
                ];
                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['issue'] ?? $item['title'] ?? $item['item'] ?? $item['area'] ?? $item['section'] ?? ''));
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
     * Build fallback POI items directly from non-compliant checklist responses.
     *
     * @param Report $report Report model.
     * @return array<int,array<string,mixed>>
     */
    private static function build_fallback_poi_items($report)
    {
        $responses = Checklist_Response::get_by_report_grouped($report->id);
        if (!is_array($responses) || empty($responses)) {
            return [];
        }

        $checklist = Checklist_Manager::get_checklist_for_type($report->report_type);
        $lookup = [];

        if (is_array($checklist) && !empty($checklist['sections']) && is_array($checklist['sections'])) {
            foreach ($checklist['sections'] as $section) {
                $section_key = (string) ($section['key'] ?? '');
                if ($section_key === '') {
                    continue;
                }

                $section_name = (string) ($section['name'] ?? ucwords(str_replace('_', ' ', $section_key)));
                $items = isset($section['items']) && is_array($section['items']) ? $section['items'] : [];

                foreach ($items as $item) {
                    $item_key = (string) ($item['key'] ?? '');
                    if ($item_key === '') {
                        continue;
                    }

                    $lookup[$section_key . '/' . $item_key] = [
                        'section_name' => $section_name,
                        'item_label' => (string) ($item['label'] ?? ucwords(str_replace('_', ' ', $item_key))),
                        'root_key' => (string) ($item['root_key'] ?? ($section_key . '/' . $item_key)),
                        'entry_mode' => (string) ($item['entry_mode'] ?? 'standalone'),
                    ];
                }
            }
        }

        $items = [];
        $deduped = [];

        foreach ($responses as $section_key => $section_items) {
            if (!is_array($section_items)) {
                continue;
            }

            foreach ($section_items as $item_key => $response) {
                $rating = strtolower(trim((string) (is_object($response) ? ($response->rating ?? '') : ($response['rating'] ?? ''))));
                if (!in_array($rating, ['no', 'sometimes'], true)) {
                    continue;
                }

                $meta = $lookup[$section_key . '/' . $item_key] ?? [
                    'section_name' => ucwords(str_replace('_', ' ', (string) $section_key)),
                    'item_label' => ucwords(str_replace('_', ' ', (string) $item_key)),
                    'root_key' => $section_key . '/' . $item_key,
                    'entry_mode' => 'standalone',
                ];

                $root_key = (string) ($meta['root_key'] ?? ($section_key . '/' . $item_key));
                if (($meta['entry_mode'] ?? 'standalone') === 'shared_exact' && isset($deduped[$root_key])) {
                    continue;
                }

                $deduped[$root_key] = true;
                $notes = trim((string) (is_object($response) ? ($response->notes ?? '') : ($response['notes'] ?? '')));
                $label = (string) ($meta['item_label'] ?? ucwords(str_replace('_', ' ', (string) $item_key)));
                $section_name = (string) ($meta['section_name'] ?? ucwords(str_replace('_', ' ', (string) $section_key)));

                $items[] = [
                    'poi_key' => $root_key,
                    'root_key' => $root_key,
                    'section_key' => (string) $section_key,
                    'item_key' => (string) $item_key,
                    'issue' => $label,
                    'area' => $label,
                    'section' => $section_name,
                    'current_status' => $notes !== ''
                        ? $notes
                        : sprintf('%s in %s was marked as %s.', $label, $section_name, strtoupper($rating)),
                    'action_steps' => [
                        sprintf('Review current practice for %s with the team.', $label),
                        sprintf('Implement the correction for %s and document the change.', $label),
                        sprintf('Verify %s is consistently meeting expectations before the next QA review.', $label),
                    ],
                    'action' => sprintf('Address %s.', $label),
                    'priority' => $rating === 'no' ? 1 : 2,
                    'timeline' => $rating === 'no' ? 'Within 24 hours' : 'Within 7 days',
                    'success_criteria' => sprintf('%s is consistently observed meeting expectations.', $label),
                    'support_offered' => 'Provide coaching, examples, and follow-up verification support.',
                ];
            }
        }

        return $items;
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
        return trim((string) ($poi['issue'] ?? $poi['title'] ?? $poi['area'] ?? 'POI Item'));
    }

    /**
     * Resolve priority label from POI item.
     *
     * @param array $poi POI item.
     * @return string
     */
    private static function priority_label($poi)
    {
        $raw_value = $poi['priority'] ?? $poi['severity'] ?? '';

        if (is_numeric($raw_value)) {
            $priority = (int) $raw_value;
            if ($priority <= 2) {
                return 'High';
            }

            if ($priority === 3) {
                return 'Medium';
            }

            return 'Low';
        }

        $value = strtolower(trim((string) $raw_value));

        if (in_array($value, ['critical', 'immediate', 'high'], true)) {
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
        foreach (['current_status', 'recommendation', 'action', 'action_steps', 'notes', 'timeline', 'success_criteria', 'support_offered'] as $key) {
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
     * Delete a stale POI mapping row.
     *
     * @param int    $report_id Report ID.
     * @param string $poi_key Stable POI key.
     * @return void
     */
    private static function delete_item_mapping($report_id, $poi_key)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'cqa_monday_poi_syncs';
        $wpdb->delete(
            $table,
            [
                'report_id' => (int) $report_id,
                'poi_key' => $poi_key,
            ],
            ['%d', '%s']
        );
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

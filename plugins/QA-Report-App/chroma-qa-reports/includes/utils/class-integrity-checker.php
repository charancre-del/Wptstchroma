<?php
/**
 * Integrity Checker
 *
 * Validates data integrity and legacy compatibility.
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Utils;

class Integrity_Checker
{

    /**
     * Run all checks.
     *
     * @return array Results array [ 'status' => 'pass|fail', 'issues' => [] ]
     */
    public static function run_all()
    {
        $issues = [];

        $issues = array_merge($issues, self::check_orphan_reports());
        $issues = array_merge($issues, self::check_invalid_json());

        // Add more checks here

        $status = empty($issues) ? 'pass' : 'fail';

        return [
            'status' => $status,
            'issues' => $issues,
            'timestamp' => current_time('mysql')
        ];
    }

    /**
     * Check for reports linked to non-existent schools.
     */
    private static function check_orphan_reports()
    {
        global $wpdb;
        $reports = $wpdb->prefix . 'cqa_reports';
        $schools = $wpdb->prefix . 'cqa_schools';

        $orphans = $wpdb->get_results("
            SELECT r.id, r.school_id 
            FROM {$reports} r 
            LEFT JOIN {$schools} s ON r.school_id = s.id 
            WHERE s.id IS NULL
        ");

        $issues = [];
        foreach ($orphans as $orphan) {
            $issues[] = "Orphan Report ID {$orphan->id}: Linked to missing School ID {$orphan->school_id}";
        }

        return $issues;
    }

    /**
     * Check for invalid JSON in schools.
     */
    private static function check_invalid_json()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cqa_schools';

        // We can't easily validate JSON with SQL in older MySQL versions, 
        // so we fetch and check in PHP.
        // Limit to 1000 for performance safety.
        $rows = $wpdb->get_results("SELECT id, classroom_config FROM {$table} LIMIT 1000");

        $issues = [];
        foreach ($rows as $row) {
            if (!empty($row->classroom_config)) {
                json_decode($row->classroom_config);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $issues[] = "Invalid JSON in School ID {$row->id}";
                }
            }
        }

        return $issues;
    }
}

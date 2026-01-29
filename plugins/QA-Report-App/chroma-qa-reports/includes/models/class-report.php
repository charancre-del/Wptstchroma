<?php
/**
 * Report Model
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Models;

/**
 * Represents a QA Report.
 */
class Report
{

    /**
     * Table name.
     *
     * @var string
     */
    private static $table = 'cqa_reports';

    /**
     * Report type constants.
     */
    const TYPE_NEW_ACQUISITION = 'new_acquisition';
    const TYPE_TIER1 = 'tier1';
    const TYPE_TIER1_TIER2 = 'tier1_tier2';

    /**
     * Rating constants.
     */
    const RATING_EXCEEDS = 'exceeds';
    const RATING_MEETS = 'meets';
    const RATING_NEEDS_IMPROVEMENT = 'needs_improvement';
    const RATING_PENDING = 'pending';

    /**
     * Status constants.
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';

    /**
     * Report properties.
     */
    public $id;
    public $school_id;
    public $user_id;
    public $report_type;
    public $inspection_date;
    public $previous_report_id;
    public $overall_rating;
    public $closing_notes;
    public $status;
    public $version_id;
    public $created_at;
    public $updated_at;

    /**
     * Cached school object.
     *
     * @var School|null
     */
    private $school_cache = null;

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
     * Find a report by ID.
     *
     * @param int $id Report ID.
     * @return Report|null
     */
    public static function find($id)
    {
        global $wpdb;
        $table = self::get_table_name();

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id),
            \ARRAY_A
        );

        return $row ? self::from_row($row) : null;
    }

    /**
     * Get all reports.
     *
     * @param array $args Query arguments.
     * @return Report[]
     */
    public static function all($args = [])
    {
        global $wpdb;
        $table = self::get_table_name();
        $schools_table = School::get_table_name();
        $users_table = $wpdb->users;

        $defaults = [
            'school_id' => 0,
            'user_id' => 0,
            'report_type' => '',
            'status' => '',
            'search' => '',
            'orderby' => 'inspection_date',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = [];
        $values = [];
        $join = '';

        if ($args['school_id']) {
            $where[] = 'r.school_id = %d';
            $values[] = $args['school_id'];
        }

        if ($args['user_id']) {
            $where[] = 'r.user_id = %d';
            $values[] = $args['user_id'];
        }

        if (!empty($args['report_type'])) {
            $where[] = 'r.report_type = %s';
            $values[] = $args['report_type'];
        }

        if (!empty($args['status'])) {
            $where[] = 'r.status = %s';
            $values[] = $args['status'];
        }

        $search = trim($args['search']);
        if ($search !== '') {
            $join = " LEFT JOIN {$schools_table} s ON r.school_id = s.id
                      LEFT JOIN {$users_table} u ON r.user_id = u.ID ";

            $search_like = '%' . $wpdb->esc_like($search) . '%';

            // Base search conditions (string columns)
            $conditions = [
                's.name LIKE %s',
                'u.display_name LIKE %s',
                'r.report_type LIKE %s',
                'r.inspection_date LIKE %s'
            ];
            $search_values = [$search_like, $search_like, $search_like, $search_like];

            // Optimized ID search: Only search IDs if the input is numeric
            // This avoids CAST(id AS CHAR) which kills index performance (QAR-035)
            if (is_numeric($search)) {
                $conditions[] = 'r.id = %d';
                $search_values[] = $search;

                $conditions[] = 'r.school_id = %d';
                $search_values[] = $search;
            }

            $where[] = '(' . implode(' OR ', $conditions) . ')';
            $values = array_merge($values, $search_values);
        }

        $allowed_orderby = [
            'inspection_date' => 'r.inspection_date',
            'created_at' => 'r.created_at',
            'updated_at' => 'r.updated_at',
            'status' => 'r.status',
            'report_type' => 'r.report_type',
            'id' => 'r.id',
            'school_name' => 's.name',
            'author_name' => 'u.display_name',
        ];

        if (in_array($args['orderby'], ['school_name', 'author_name'], true) && $join === '') {
            $join = " LEFT JOIN {$schools_table} s ON r.school_id = s.id
                      LEFT JOIN {$users_table} u ON r.user_id = u.ID ";
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $orderby_key = $allowed_orderby[$args['orderby']] ?? 'r.inspection_date';
        $orderby = sanitize_sql_orderby("{$orderby_key} {$order}");
        if (empty($orderby)) {
            $orderby = 'r.inspection_date DESC';
        }

        $sql = "SELECT r.* FROM {$table} r {$join} {$where_clause} ORDER BY {$orderby} LIMIT %d OFFSET %d";
        $values[] = $args['limit'];
        $values[] = $args['offset'];

        $rows = $wpdb->get_results(
            $wpdb->prepare($sql, $values),
            \ARRAY_A
        );

        return array_map([self::class, 'from_row'], $rows);
    }

    /**
     * Count total reports matching criteria.
     *
     * @param array $args Query arguments.
     * @return int
     */
    public static function count($args = [])
    {
        global $wpdb;
        $table = self::get_table_name();
        $schools_table = School::get_table_name();
        $users_table = $wpdb->users;

        $where = [];
        $values = [];
        $join = '';

        if (!empty($args['school_id'])) {
            $where[] = 'r.school_id = %d';
            $values[] = $args['school_id'];
        }

        if (!empty($args['user_id'])) {
            $where[] = 'r.user_id = %d';
            $values[] = $args['user_id'];
        }

        if (!empty($args['status'])) {
            $where[] = 'r.status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['report_type'])) {
            $where[] = 'r.report_type = %s';
            $values[] = $args['report_type'];
        }

        $search = isset($args['search']) ? trim($args['search']) : '';
        if ($search !== '') {
            $join = " LEFT JOIN {$schools_table} s ON r.school_id = s.id
                      LEFT JOIN {$users_table} u ON r.user_id = u.ID ";
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where[] = "(s.name LIKE %s OR u.display_name LIKE %s OR r.report_type LIKE %s OR r.inspection_date LIKE %s OR CAST(r.id AS CHAR) LIKE %s OR CAST(r.school_id AS CHAR) LIKE %s)";
            $values[] = $search_like;
            $values[] = $search_like;
            $values[] = $search_like;
            $values[] = $search_like;
            $values[] = $search_like;
            $values[] = $search_like;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        if (!empty($values)) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} r {$join} {$where_clause}",
                $values
            ));
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} r {$join}");
    }

    /**
     * Create a report from a database row.
     *
     * @param array $row Database row.
     * @return Report
     */
    public static function from_row($row)
    {
        $report = new self();
        $report->id = (int) $row['id'];
        $report->school_id = (int) $row['school_id'];
        $report->user_id = (int) $row['user_id'];
        $report->report_type = $row['report_type'];
        $report->inspection_date = $row['inspection_date'];
        $report->previous_report_id = $row['previous_report_id'] ? (int) $row['previous_report_id'] : null;
        $report->overall_rating = $row['overall_rating'];
        $report->closing_notes = $row['closing_notes'];
        $report->status = $row['status'];
        $report->version_id = (int) ($row['version_id'] ?? 1);
        $report->created_at = $row['created_at'];
        $report->updated_at = $row['updated_at'];
        return $report;
    }

    /**
     * Save the report.
     *
     * @return bool|int
     */
    public function save()
    {
        global $wpdb;
        $table = self::get_table_name();

        $data = [
            'school_id' => $this->school_id,
            'user_id' => $this->user_id,
            'report_type' => $this->report_type,
            'inspection_date' => $this->inspection_date,
            'previous_report_id' => $this->previous_report_id,
            'overall_rating' => $this->overall_rating ?: self::RATING_PENDING,
            'closing_notes' => $this->closing_notes,
            'status' => $this->status ?: self::STATUS_DRAFT,
            'version_id' => ($this->version_id ?: 0) + 1,
        ];

        $format = ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d'];

        if ($this->id) {
            $result = $wpdb->update($table, $data, ['id' => $this->id], $format, ['%d']);
            if ($result !== false) {
                $this->version_id = $data['version_id'];
                return $this->id;
            }
            return false;
        } else {
            $result = $wpdb->insert($table, $data, $format);
            if ($result) {
                $this->id = $wpdb->insert_id;
                $this->version_id = $data['version_id'];
                return $this->id;
            }
            return false;
        }
    }

    /**
     * Delete the report.
     *
     * @return bool
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }

        global $wpdb;

        // Use database transactions for multi-shelf deletion safety
        $wpdb->query('START TRANSACTION');

        try {
            // Delete related responses
            $wpdb->delete(
                $wpdb->prefix . 'cqa_responses',
                ['report_id' => $this->id],
                ['%d']
            );

            // Delete related photos
            $photos = $this->get_photos();
            foreach ($photos as $photo) {
                // [FIX-303] Immediate orphan cleanup (QAR-011).
                // Delete actual file from Google Drive/Local to prevent storage leaks.
                if (!empty($photo->drive_file_id)) {
                    // Check if it's a local attachment or Drive ID
                    if (strpos($photo->drive_file_id, 'wp_') === 0) {
                        wp_delete_attachment((int) substr($photo->drive_file_id, 3), true);
                    } else {
                        // It's a Google Drive ID
                        try {
                            \ChromaQA\Integrations\Google_Drive::delete_file($photo->drive_file_id);
                        } catch (\Exception $d) {
                            // Log but don't block deletion of report
                            error_log("Failed to delete Drive file {$photo->drive_file_id}: " . $d->getMessage());
                        }
                    }
                }

                // Delete photo record
                $photo->delete();
            }

            // Delete AI summary
            $wpdb->delete(
                $wpdb->prefix . 'cqa_ai_summaries',
                ['report_id' => $this->id],
                ['%d']
            );

            // Delete the report
            $result = $wpdb->delete(self::get_table_name(), ['id' => $this->id], ['%d']);

            if ($result === false) {
                throw new \Exception('Failed to delete report record');
            }

            $wpdb->query('COMMIT');
            return true;
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            error_log('Report deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the school for this report.
     *
     * @return School|null
     */
    public function get_school()
    {
        if ($this->school_cache === null) {
            $this->school_cache = School::find($this->school_id);
        }
        return $this->school_cache;
    }

    /**
     * Get the previous report for comparison.
     *
     * @return Report|null
     */
    public function get_previous_report()
    {
        if ($this->previous_report_id) {
            return self::find($this->previous_report_id);
        }
        return null;
    }

    /**
     * Get all responses for this report.
     *
     * @return Checklist_Response[]
     */
    public function get_responses()
    {
        return Checklist_Response::get_by_report($this->id);
    }

    /**
     * Get all photos for this report.
     *
     * @return Photo[]
     */
    public function get_photos()
    {
        return Photo::get_by_report($this->id);
    }

    /**
     * Get the AI summary for this report.
     *
     * @return array|null
     */
    public function get_ai_summary()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cqa_ai_summaries';

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE report_id = %d", $this->id),
            \ARRAY_A
        );

        if (!$row) {
            return null;
        }

        return [
            'executive_summary' => $row['executive_summary'],
            'issues' => json_decode($row['issues_json'] ?: '[]', true),
            'poi' => json_decode($row['poi_json'] ?: '[]', true),
            'comparison' => json_decode($row['comparison_json'] ?: '[]', true),
            'generated_at' => $row['generated_at'],
        ];
    }

    /**
     * Get report type label.
     *
     * @return string
     */
    public function get_type_label()
    {
        $labels = [
            self::TYPE_NEW_ACQUISITION => __('New Acquisition', 'chroma-qa-reports'),
            self::TYPE_TIER1 => __('Tier 1', 'chroma-qa-reports'),
            self::TYPE_TIER1_TIER2 => __('Tier 1 + Tier 2', 'chroma-qa-reports'),
        ];
        return $labels[$this->report_type] ?? $this->report_type;
    }

    /**
     * Get rating label.
     *
     * @return string
     */
    public function get_rating_label()
    {
        $labels = [
            self::RATING_EXCEEDS => __('Exceeds', 'chroma-qa-reports'),
            self::RATING_MEETS => __('Meets', 'chroma-qa-reports'),
            self::RATING_NEEDS_IMPROVEMENT => __('Needs Improvement', 'chroma-qa-reports'),
            self::RATING_PENDING => __('Pending', 'chroma-qa-reports'),
        ];
        return $labels[$this->overall_rating] ?? $this->overall_rating;
    }

    /**
     * Get status label.
     *
     * @return string
     */
    public function get_status_label()
    {
        $labels = [
            self::STATUS_DRAFT => __('Draft', 'chroma-qa-reports'),
            self::STATUS_SUBMITTED => __('Submitted', 'chroma-qa-reports'),
            self::STATUS_APPROVED => __('Approved', 'chroma-qa-reports'),
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Get the latest report for a school.
     *
     * @param int $school_id School ID.
     * @return Report|null
     */
    public static function get_latest_for_school($school_id)
    {
        $reports = self::all([
            'school_id' => $school_id,
            'status' => 'approved',
            'orderby' => 'inspection_date',
            'order' => 'DESC',
            'limit' => 1,
        ]);

        return !empty($reports) ? $reports[0] : null;
    }

    /**
     * Get reports by school with additional filtering.
     *
     * @param int   $school_id School ID.
     * @param array $args Additional query arguments.
     * @return Report[]
     */
    public static function get_by_school($school_id, $args = [])
    {
        $args['school_id'] = $school_id;
        return self::all($args);
    }
}

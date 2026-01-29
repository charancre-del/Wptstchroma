<?php
/**
 * REST API Controller
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\API;

use ChromaQA\Models\School;
use ChromaQA\Models\Report;
use ChromaQA\Models\Checklist_Response;
use ChromaQA\Models\Photo;
use ChromaQA\Integrations\Google_Drive;
use ChromaQA\Checklists\Checklist_Manager;
use ChromaQA\Utils\Docx_Parser;
use ChromaQA\AI\Gemini_Service;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST API endpoints for the QA Reports plugin.
 */
class REST_Controller
{

    /**
     * API namespace.
     *
     * @var string
     */
    const NAMESPACE = 'cqa/v1';

    /**
     * Register REST API routes.
     */
    public function register_routes()
    {
        if (defined('CQA_DEBUG') && CQA_DEBUG) {
            \add_filter('rest_request_after_callbacks', [$this, 'log_rest_errors'], 10, 3);
        }

        // Current user info (/me)
        \register_rest_route(self::NAMESPACE , '/me', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_current_user_info'],
            'permission_callback' => [$this, 'check_authenticated_permission'],
        ]);

        // Schools
        \register_rest_route(self::NAMESPACE , '/schools', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_schools'],
                'permission_callback' => [$this, 'check_read_permission'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_school'],
                'permission_callback' => [$this, 'check_manage_schools_permission'],
            ],
        ]);

        \register_rest_route(self::NAMESPACE , '/schools/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_school'],
                'permission_callback' => [$this, 'check_read_permission'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_school'],
                'permission_callback' => [$this, 'check_manage_schools_permission'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_school'],
                'permission_callback' => [$this, 'check_manage_schools_permission'],
            ],
        ]);

        // Reports
        \register_rest_route(self::NAMESPACE , '/reports', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_reports'],
                'permission_callback' => [$this, 'check_read_permission'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_report'],
                'permission_callback' => [$this, 'check_create_reports_permission'],
            ],
        ]);

        \register_rest_route(self::NAMESPACE , '/reports/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_report'],
                'permission_callback' => [$this, 'check_read_permission'],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_report'],
                'permission_callback' => [$this, 'check_edit_reports_permission'],
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_report'],
                'permission_callback' => [$this, 'check_delete_reports_permission'],
            ],
        ]);

        // Report responses
        \register_rest_route(self::NAMESPACE , '/reports/(?P<id>\d+)/responses', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_report_responses'],
                'permission_callback' => [$this, 'check_read_permission'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'save_report_responses'],
                'permission_callback' => [$this, 'check_edit_reports_permission'],
            ],
        ]);

        // Report PDF
        \register_rest_route(self::NAMESPACE , '/reports/(?P<id>\d+)/pdf', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'generate_report_pdf'],
            'permission_callback' => function ($request) {
                // Allow direct browser download if user is logged in
                return is_user_logged_in() && current_user_can('cqa_export_reports');
            },
        ]);

        // AI endpoints
        \register_rest_route(self::NAMESPACE , '/reports/(?P<id>\d+)/generate-summary', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'generate_ai_summary'],
            'permission_callback' => [$this, 'check_ai_permission'],
        ]);

        \register_rest_route(self::NAMESPACE , '/reports/upload-doc', [
            'methods' => 'POST',
            'callback' => [$this, 'upload_report_doc'],
            'permission_callback' => [$this, 'check_create_reports_permission'],
        ]);

        // PWA Manifest
        \register_rest_route(self::NAMESPACE , '/manifest', [
            'methods' => 'GET',
            'callback' => [$this, 'get_manifest'],
            'permission_callback' => '__return_true', // Public
        ]);

        // Upload Report Photos (New)
        \register_rest_route(self::NAMESPACE , '/reports/(?P<id>\d+)/photos', [
            'methods' => 'POST',
            'callback' => [$this, 'upload_report_photos'],
            'permission_callback' => [$this, 'check_edit_reports_permission'],
        ]);

        \register_rest_route(self::NAMESPACE , '/photos/(?P<id>\d+)', [
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_photo'],
                'permission_callback' => [$this, 'check_read_permission'], // Simple check for now
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_photo'],
                'permission_callback' => [$this, 'check_read_permission'], // Simple check for now
            ],
        ]);

        \register_rest_route(self::NAMESPACE , '/ai/parse-document', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'parse_document'],
            'permission_callback' => [$this, 'check_ai_permission'],
        ]);

        // Checklists
        \register_rest_route(self::NAMESPACE , '/checklists/(?P<type>[a-z0-9_]+)', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_checklist'],
            'permission_callback' => [$this, 'check_read_permission'],
        ]);

        // Schools reports (for previous report selection)
        \register_rest_route(self::NAMESPACE , '/schools/(?P<id>\d+)/reports', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_school_reports'],
            'permission_callback' => [$this, 'check_read_permission'],
        ]);

        // Settings
        \register_rest_route(self::NAMESPACE , '/settings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_settings'],
                'permission_callback' => [$this, 'check_settings_permission'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'update_settings'],
                'permission_callback' => [$this, 'check_settings_permission'],
            ],
        ]);

        // Dashboard Stats
        \register_rest_route(self::NAMESPACE , '/stats', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_read_permission'],
        ]);

        // System Health Check
        \register_rest_route(self::NAMESPACE , '/system-check', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_system_status'],
            'permission_callback' => [$this, 'check_manage_options_permission'],
        ]);
    }

    /**
     * Log REST API errors when debug mode is enabled.
     *
     * @param WP_REST_Response|WP_Error $response Response object.
     * @param array                    $handler  Handler metadata.
     * @param WP_REST_Request          $request  Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function log_rest_errors($response, $handler, $request)
    {
        if (!defined('CQA_DEBUG') || !CQA_DEBUG) {
            return $response;
        }

        $route = $request instanceof WP_REST_Request ? $request->get_route() : '';
        $method = $request instanceof WP_REST_Request ? $request->get_method() : '';

        if (is_wp_error($response)) {
            $data = $this->sanitize_log_data($response->get_error_data());
            error_log(
                sprintf(
                    '[CQA REST Error] %s %s | %s | %s',
                    $method,
                    $route,
                    $response->get_error_message(),
                    wp_json_encode($data)
                )
            );
            return $response;
        }

        if ($response instanceof WP_REST_Response) {
            $status = $response->get_status();
            if ($status >= 400) {
                $data = $this->sanitize_log_data($response->get_data());
                error_log(
                    sprintf(
                        '[CQA REST Error] %s %s | Status %d | %s',
                        $method,
                        $route,
                        $status,
                        wp_json_encode($data)
                    )
                );
            }
        }

        return $response;
    }

    /**
     * Sanitize data for logging.
     *
     * @param mixed $data Data to sanitize.
     * @return mixed
     */
    private function sanitize_log_data($data)
    {
        $sensitive_keys = ['password', 'token', 'secret', 'nonce', 'authorization'];

        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $key_label = is_string($key) ? $key : (string) $key;
                if (is_string($key_label) && in_array(strtolower($key_label), $sensitive_keys, true)) {
                    $sanitized[$key] = '[REDACTED]';
                    continue;
                }
                $sanitized[$key] = $this->sanitize_log_data($value);
            }
            return $sanitized;
        }

        if (is_object($data)) {
            return $this->sanitize_log_data((array) $data);
        }

        return $data;
    }

    // ===== PERMISSION CALLBACKS =====

    public function check_authenticated_permission()
    {
        return is_user_logged_in();
    }

    public function check_read_permission()
    {
        return \current_user_can('cqa_view_own_reports') || \current_user_can('cqa_view_all_reports');
    }

    public function check_manage_schools_permission()
    {
        return \current_user_can('cqa_manage_schools');
    }

    public function check_create_reports_permission()
    {
        return \current_user_can('cqa_create_reports');
    }

    public function check_edit_reports_permission($request)
    {
        if (\current_user_can('cqa_edit_all_reports')) {
            return true;
        }

        if (\current_user_can('cqa_edit_own_reports')) {
            $report = Report::find($request['id']);
            return $report && $report->user_id === \get_current_user_id();
        }

        return false;
    }

    public function check_delete_reports_permission()
    {
        return \current_user_can('cqa_delete_reports') || \current_user_can('cqa_delete_own_reports');
    }

    public function check_export_permission()
    {
        return \current_user_can('cqa_export_reports');
    }

    public function check_ai_permission()
    {
        return \current_user_can('cqa_use_ai_features');
    }

    public function check_manage_options_permission()
    {
        return \current_user_can('manage_options'); // Super Admin only
    }

    public function check_settings_permission()
    {
        // Enforce strict access for settings (API keys, etc.)
        return \current_user_can('cqa_manage_settings') || \current_user_can('manage_options');
    }

    // ===== CURRENT USER ENDPOINT =====

    /**
     * Get current user info for React app.
     *
     * @return WP_REST_Response
     */
    public function get_current_user_info()
    {
        $user = \wp_get_current_user();

        if (!$user || !$user->ID) {
            return new WP_Error('unauthorized', 'Not logged in', ['status' => 401]);
        }

        // Get user capabilities (only CQA-specific ones)
        $capabilities = [];
        $cqa_caps = [
            'cqa_view_own_reports',
            'cqa_view_all_reports',
            'cqa_create_reports',
            'cqa_edit_own_reports',
            'cqa_edit_all_reports',
            'cqa_delete_reports',
            'cqa_delete_own_reports',
            'cqa_export_reports',
            'cqa_use_ai_features',
            'cqa_manage_schools',
            'cqa_manage_settings',
        ];

        foreach ($cqa_caps as $cap) {
            $capabilities[$cap] = \current_user_can($cap);
        }

        // Get feature flags using Feature_Flags class
        $flags = \ChromaQA\Feature_Flags::get_user_flags($user->ID);

        // Check Google connection status
        $google_connected = (bool) \get_user_meta($user->ID, 'cqa_google_access_token', true);

        // Calculate actual nonce expiry
        // WordPress nonces are valid for two 12-hour "ticks".
        // A nonce generated in tick T is valid during T and T+1.
        // It expires exactly at the start of tick T+2.
        $nonce_expires_at = (\wp_nonce_tick() + 1) * 43200;

        $response = [
            'success' => true,
            'data' => [
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'role' => !empty($user->roles) ? $user->roles[0] : 'subscriber',
                'capabilities' => $capabilities,
                'flags' => $flags,
                'googleConnected' => $google_connected,
                'nonceExpiresAt' => $nonce_expires_at,
            ],
        ];

        return new WP_REST_Response($response, 200);
    }

    public function get_stats(WP_REST_Request $request)
    {
        global $wpdb;
        $reports_table = $wpdb->prefix . 'cqa_reports';
        $schools_table = $wpdb->prefix . 'cqa_schools';

        // 1. Total Schools
        $total_schools = (int) $wpdb->get_var("SELECT COUNT(*) FROM $schools_table");

        // 2. Overdue Visits (Schools with no reports or older than 90 days)
        // For MVP, returning schools with NO reports or last report > 90 days
        $overdue_list = $wpdb->get_results("
            SELECT s.id, s.name, MAX(r.inspection_date) as last_visit
            FROM $schools_table s
            LEFT JOIN $reports_table r ON s.id = r.school_id
            GROUP BY s.id
            HAVING last_visit IS NULL OR last_visit < DATE_SUB(NOW(), INTERVAL 90 DAY)
            LIMIT 5
        ");

        $overdue_visits = (int) $wpdb->get_var("
            SELECT COUNT(*) FROM (
                SELECT s.id, MAX(r.inspection_date) as last_visit
                FROM $schools_table s
                LEFT JOIN $reports_table r ON s.id = r.school_id
                GROUP BY s.id
                HAVING last_visit IS NULL OR last_visit < DATE_SUB(NOW(), INTERVAL 90 DAY)
            ) as sub
        ");

        // 3. Compliance Breakdown (Approved Reports Ratings)
        $ratings = $wpdb->get_results("
            SELECT rating, COUNT(*) as count 
            FROM $reports_table 
            WHERE status = 'approved' AND rating IS NOT NULL 
            GROUP BY rating
        ");

        $compliance = [
            'exceeds' => 0,
            'meets' => 0,
            'improvement' => 0
        ];

        foreach ($ratings as $row) {
            $key = strtolower(str_replace('Expectations', '', $row->rating));
            $key = trim(str_replace(' ', '_', $key));

            if ($key === 'exceeds' || $key === 'exceeds_expectations') {
                $compliance['exceeds'] += $row->count;
            } elseif ($key === 'meets' || $key === 'meets_expectations') {
                $compliance['meets'] += $row->count;
            } elseif ($key === 'needs_improvement' || $key === 'improvement') {
                $compliance['improvement'] += $row->count;
            }
        }

        // 4. Compliant Schools (Have at least one 'meets' or 'exceeds' report)
        $compliant_schools = (int) $wpdb->get_var("
            SELECT COUNT(DISTINCT school_id) FROM $reports_table
            WHERE status = 'approved' AND (overall_rating = 'meets' OR overall_rating = 'exceeds')
        ");

        // 5. My Reports (Current User)
        $user_id = \get_current_user_id();
        $my_reports = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $reports_table WHERE user_id = %d",
            $user_id
        ));

        // 6. Trend Data (Last 6 Months)
        // Mapping: Exceeds=100, Meets=85, Needs Improvement=60, Unsatisfactory=40
        $trend_sql = "
            SELECT 
                DATE_FORMAT(inspection_date, '%b') as name,
                DATE_FORMAT(inspection_date, '%m') as month_num,
                AVG(CASE 
                    WHEN rating IN ('exceeds', 'Exceeds Expectations', 'Exceeds') THEN 100
                    WHEN rating IN ('meets', 'Meets Expectations', 'Meets') THEN 85
                    WHEN rating IN ('needs_improvement', 'Needs Improvement') THEN 60
                    ELSE 40
                END) as score
            FROM $reports_table
            WHERE status = 'approved' 
            AND inspection_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(inspection_date, '%b'), DATE_FORMAT(inspection_date, '%m')
            ORDER BY month_num ASC
        ";
        $trend_data = $wpdb->get_results($trend_sql);

        // 7. Action Items
        $action_items = [];

        // Critical: Needs Improvement reports from last 30 days
        $critical_reports = $wpdb->get_results("
            SELECT r.id, s.name as title, r.inspection_date as date
            FROM $reports_table r
            JOIN $schools_table s ON r.school_id = s.id
            WHERE r.status = 'approved' 
            AND r.rating = 'Needs Improvement'
            AND r.inspection_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            LIMIT 3
        ");

        foreach ($critical_reports as $report) {
            $action_items[] = [
                'id' => 'crit_' . $report->id,
                'title' => 'Attention Needed: ' . $report->title,
                'type' => 'critical',
                'date' => human_time_diff(strtotime($report->date), current_time('timestamp')) . ' ago',
                'link' => '/reports/' . $report->id
            ];
        }

        // Overdue: Top 3 overdue
        foreach (array_slice($overdue_list, 0, 3) as $school) {
            $last = $school->last_visit ? human_time_diff(strtotime($school->last_visit), current_time('timestamp')) . ' ago' : 'Never visited';
            $action_items[] = [
                'id' => 'overdue_' . $school->id,
                'title' => 'Overdue: ' . $school->name,
                'type' => 'overdue',
                'date' => $last,
                'link' => '/create?school=' . $school->id
            ];
        }

        // Drafts: My old drafts
        $stale_drafts = $wpdb->get_results($wpdb->prepare("
            SELECT r.id, s.name as school_name, r.updated_at
            FROM $reports_table r
            LEFT JOIN $schools_table s ON r.school_id = s.id
            WHERE r.status = 'draft' 
            AND r.user_id = %d
            AND r.updated_at < DATE_SUB(NOW(), INTERVAL 3 DAY)
            LIMIT 2
        ", $user_id));

        foreach ($stale_drafts as $draft) {
            $action_items[] = [
                'id' => 'draft_' . $draft->id,
                'title' => 'Finish Report: ' . ($draft->school_name ?: 'Untitled'),
                'type' => 'info',
                'date' => 'Updated ' . human_time_diff(strtotime($draft->updated_at), current_time('timestamp')) . ' ago',
                'link' => '/edit/' . $draft->id
            ];
        }

        return new WP_REST_Response([
            'total_schools' => $total_schools,
            'overdue_visits' => $overdue_visits,
            'overdue_list' => $overdue_list,
            'compliant_schools' => $compliant_schools,
            'my_reports' => $my_reports,
            'compliance' => $compliance,
            'trend' => $trend_data,
            'action_items' => $action_items
        ], 200);
    }

    /**
     * Diagnostic endpoint for system health.
     * 
     * @return WP_REST_Response
     */
    public function get_system_status()
    {
        $status = [
            'database' => [
                'connection' => true,
                'prefix' => $GLOBALS['wpdb']->prefix,
                'version' => $GLOBALS['wpdb']->db_version(),
            ],
            'integrations' => [
                'google_drive' => ['status' => 'unknown', 'message' => ''],
                'gemini' => ['status' => 'unknown', 'message' => ''],
            ]
        ];

        // Check Google Drive
        try {
            $drive = new \ChromaQA\Integrations\Google_Drive();
            $drive_connected = $drive->test_connection();
            $status['integrations']['google_drive'] = [
                'status' => $drive_connected ? 'healthy' : 'disconnected',
                'message' => $drive_connected ? 'Connected to Google Drive API' : 'Failed to connect'
            ];
        } catch (\Exception $e) {
            $status['integrations']['google_drive'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Check Gemini
        $gemini_key = \get_option('cqa_gemini_api_key');
        if (empty($gemini_key)) {
            $status['integrations']['gemini'] = ['status' => 'missing_key', 'message' => 'API Key not configured'];
        } else {
            $status['integrations']['gemini'] = ['status' => 'configured', 'message' => 'API Key set (connectivity not tested in this health check)'];
        }

        return new WP_REST_Response(['success' => true, 'data' => $status], 200);
    }

    // ===== SCHOOLS ENDPOINTS =====

    public function get_schools(WP_REST_Request $request)
    {
        $limit = (int) ($request->get_param('per_page') ?: 100);
        $page = (int) ($request->get_param('page') ?: 1);
        $offset = ($page - 1) * $limit;

        $args = [
            'status' => $request->get_param('status') ?: '',
            'region' => $request->get_param('region') ?: '',
            'search' => $request->get_param('search') ?: '',
            'orderby' => $request->get_param('orderby') ?: 'name',
            'order' => $request->get_param('order') ?: 'ASC',
            'include_report_meta' => true,
            'limit' => $limit,
            'offset' => $offset,
        ];

        $schools = School::all($args);
        $total_count = School::count($args); // Handle pagination metadata

        $data = array_map([$this, 'prepare_school_response'], $schools);

        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $total_count,
                'total_pages' => ceil($total_count / $args['limit']),
                'pages' => ceil($total_count / $args['limit']), // Legacy alias
                'current_page' => $request->get_param('page') ?: 1,
                'per_page' => $args['limit']
            ]
        ], 200);
    }

    public function get_school(WP_REST_Request $request)
    {
        $school = School::find($request['id']);

        if (!$school) {
            return new WP_Error('not_found', __('School not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        return new WP_REST_Response($this->prepare_school_response($school), 200);
    }

    public function create_school(WP_REST_Request $request)
    {
        $school = new School();
        $school->name = \sanitize_text_field($request->get_param('name'));
        $school->location = \sanitize_text_field($request->get_param('location'));
        $school->region = \sanitize_text_field($request->get_param('region'));
        $school->acquired_date = \sanitize_text_field($request->get_param('acquired_date'));
        $school->status = \sanitize_text_field($request->get_param('status')) ?: 'active';
        $school->drive_folder_id = \sanitize_text_field($request->get_param('drive_folder_id'));
        $school->classroom_config = $request->get_param('classroom_config') ?: [];

        error_log('REST_Controller: create_school - Data: ' . print_r($request->get_params(), true));

        $result = $school->save();

        if (!$result) {
            error_log('REST_Controller: create_school - SAVE FAILED');
            return new WP_Error('create_failed', \__('Failed to create school. Check error logs.', 'chroma-qa-reports'), ['status' => 500]);
        }

        return new WP_REST_Response($this->prepare_school_response($school), 201);
    }

    public function update_school(WP_REST_Request $request)
    {
        $school = School::find($request['id']);

        if (!$school) {
            return new WP_Error('not_found', __('School not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        if ($request->has_param('name')) {
            $school->name = \sanitize_text_field($request->get_param('name'));
        }
        if ($request->has_param('location')) {
            $school->location = \sanitize_text_field($request->get_param('location'));
        }
        if ($request->has_param('region')) {
            $school->region = \sanitize_text_field($request->get_param('region'));
        }
        if ($request->has_param('acquired_date')) {
            $school->acquired_date = \sanitize_text_field($request->get_param('acquired_date'));
        }
        if ($request->has_param('status')) {
            $school->status = \sanitize_text_field($request->get_param('status'));
        }
        if ($request->has_param('drive_folder_id')) {
            $school->drive_folder_id = \sanitize_text_field($request->get_param('drive_folder_id'));
        }
        if ($request->has_param('classroom_config')) {
            $school->classroom_config = $request->get_param('classroom_config');
        }

        $school->save();

        return new WP_REST_Response($this->prepare_school_response($school), 200);
    }

    public function delete_school(WP_REST_Request $request)
    {
        $school = School::find($request['id']);

        if (!$school) {
            return new WP_Error('not_found', __('School not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        $school->delete();

        return new WP_REST_Response(null, 204);
    }

    // ===== REPORTS ENDPOINTS =====

    public function get_reports(WP_REST_Request $request)
    {
        $limit = (int) ($request->get_param('per_page') ?: 50);
        $page = (int) ($request->get_param('page') ?: 1);
        $offset = ($page - 1) * $limit;

        $order_by_param = $request->get_param('orderby') ?: 'inspection_date';
        $order_map = [
            'date' => 'inspection_date',
            'inspection_date' => 'inspection_date',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'status' => 'status',
            'report_type' => 'report_type',
            'school_name' => 'school_name',
            'author_name' => 'author_name',
            'id' => 'id',
        ];

        $args = [
            'school_id' => $request->get_param('school_id') ?: 0,
            'report_type' => $request->get_param('report_type') ?: '',
            'status' => $request->get_param('status') ?: '',
            'search' => $request->get_param('search') ?: '',
            'orderby' => $order_map[$order_by_param] ?? 'inspection_date',
            'order' => $request->get_param('order') ?: 'DESC',
            'limit' => $limit,
            'offset' => $offset,
        ];

        // Handle 'My Reports' filter
        if ($request->get_param('author') === 'me') {
            $args['user_id'] = \get_current_user_id();
        }

        $reports = Report::all($args);
        $total_count = Report::count($args);

        $data = array_map([$this, 'prepare_report_response'], $reports);

        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $total_count,
                'total_pages' => ceil($total_count / $args['limit']),
                'pages' => ceil($total_count / $args['limit']), // Legacy alias
                'current_page' => $request->get_param('page') ?: 1,
                'per_page' => $args['limit']
            ]
        ], 200);
    }

    public function get_report(WP_REST_Request $request)
    {
        $report = Report::find($request['id']);

        if (!$report) {
            return new WP_Error('not_found', __('Report not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        return new WP_REST_Response($this->prepare_report_response($report, true), 200);
    }

    public function create_report(WP_REST_Request $request)
    {
        // Rate Limit: 30 per minute
        $limit_check = $this->check_rate_limit('create_report', 30, 60);
        if (\is_wp_error($limit_check)) {
            return $limit_check;
        }

        // Initialize Report
        $report = new Report();
        $school_id = (int) $request->get_param('school_id');

        if (empty($school_id)) {
            $params = $request->get_params();
            $school_id = isset($params['school_id']) ? (int) $params['school_id'] : 0;
        }

        // [FIX-205] Validate School Existence & Status
        $school = School::find($school_id);
        if (!$school) {
            return new WP_Error('invalid_school', __('Invalid school ID.', 'chroma-qa-reports'), ['status' => 400]);
        }
        if ($school->status !== 'active') {
            return new WP_Error('inactive_school', __('Cannot create reports for inactive schools.', 'chroma-qa-reports'), ['status' => 400]);
        }

        $report->school_id = $school_id;
        $report->user_id = \get_current_user_id();
        $report->report_type = \sanitize_text_field($request->get_param('report_type'));
        $inspection_date = \sanitize_text_field($request->get_param('inspection_date'));
        $date_check = $this->validate_inspection_date($inspection_date);
        if (\is_wp_error($date_check)) {
            return $date_check;
        }
        $report->inspection_date = $inspection_date;
        $report->previous_report_id = intval($request->get_param('previous_report_id')) ?: null;

        // Auto-link previous report if not provided
        if (!$report->previous_report_id && $school_id) {
            $previous_reports = Report::all([
                'school_id' => $school_id,
                'status' => 'approved',
                'limit' => 1,
                'orderby' => 'inspection_date',
                'order' => 'DESC',
            ]);

            if (!empty($previous_reports)) {
                $report->previous_report_id = $previous_reports[0]->id;
            }
        }
        $report->overall_rating = \sanitize_text_field($request->get_param('overall_rating')) ?: 'pending';
        $report->closing_notes = \sanitize_textarea_field($request->get_param('closing_notes'));
        $report->status = \sanitize_text_field($request->get_param('status')) ?: 'draft';

        $result = $report->save();

        if (!$result) {
            return new WP_Error('create_failed', \__('Failed to create report.', 'chroma-qa-reports'), ['status' => 500]);
        }

        // Process Photos (Uploads)
        $this->process_report_photos($report->id, $request);

        // Process Drive Files (Picker)
        $this->process_drive_files($report->id, $request);

        // [FIX] Save Checklist Responses
        $responses = $request->get_param('responses');
        if (!empty($responses) && is_array($responses)) {
            \ChromaQA\Models\Checklist_Response::bulk_save($report->id, $responses);
        }

        return new WP_REST_Response($this->prepare_report_response($report), 201);
    }

    public function update_report(WP_REST_Request $request)
    {
        $report = Report::find($request['id']);

        if (!$report) {
            return new WP_Error('not_found', __('Report not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        // === CONCURRENCY PROTECTION ===
        // 1. Timestamp based (Legacy)
        $if_unmodified_since = $request->get_header('If-Unmodified-Since');
        if ($if_unmodified_since && $report->updated_at) {
            $client_timestamp = strtotime($if_unmodified_since);
            $server_timestamp = strtotime($report->updated_at);

            // If server version is newer than what client saw, conflict
            if ($server_timestamp > $client_timestamp) {
                // Get the name of user who last updated
                $updated_by_user = \get_userdata($report->user_id);
                $updated_by_name = $updated_by_user ? $updated_by_user->display_name : 'Another user';

                return new WP_Error(
                    'CONFLICT',
                    sprintf(
                        __('Report was modified by %s at %s. Please reload and try again.', 'chroma-qa-reports'),
                        $updated_by_name,
                        \wp_date('g:i A', $server_timestamp)
                    ),
                    [
                        'status' => 409,
                        'updated_by' => $updated_by_name,
                        'updated_at' => $report->updated_at,
                    ]
                );
            }
        }

        // 2. Version ID based (New precise locking via header or param)
        $client_version = $request->get_header('X-CQA-Version') ?: $request->get_param('version_id');

        if ($client_version) {
            $client_version = (int) $client_version;
            if ($client_version < $report->version_id) {
                return new WP_Error(
                    'CONCURRENCY_CONFLICT',
                    __('This report has been updated by another user. Please refresh and try again.', 'chroma-qa-reports'),
                    [
                        'status' => 409,
                        'client_version' => $client_version,
                        'server_version' => $report->version_id
                    ]
                );
            }
        }

        if ($request->has_param('report_type')) {
            $report->report_type = \sanitize_text_field($request->get_param('report_type'));
        }

        // Allow updating School ID (Vital for fixing Unknown Schools)
        if ($request->has_param('school_id')) {
            $school_id = (int) $request->get_param('school_id');
            if ($school_id > 0) {
                // [FIX-205] Validate School
                $school = School::find($school_id);
                if (!$school) {
                    return new WP_Error('invalid_school', __('Invalid school ID.', 'chroma-qa-reports'), ['status' => 400]);
                }
                $report->school_id = $school_id;
            }
        }

        if ($request->has_param('inspection_date')) {
            $inspection_date = \sanitize_text_field($request->get_param('inspection_date'));
            $date_check = $this->validate_inspection_date($inspection_date);
            if (\is_wp_error($date_check)) {
                return $date_check;
            }
            $report->inspection_date = $inspection_date;
        }
        if ($request->has_param('previous_report_id')) {
            $report->previous_report_id = intval($request->get_param('previous_report_id')) ?: null;
        }
        if ($request->has_param('overall_rating')) {
            $report->overall_rating = \sanitize_text_field($request->get_param('overall_rating'));
        }
        if ($request->has_param('closing_notes')) {
            $report->closing_notes = \sanitize_textarea_field($request->get_param('closing_notes'));
        }
        if ($request->has_param('status')) {
            $new_status = \sanitize_text_field($request->get_param('status'));

            // Permission check for approval
            if ($new_status === 'approved' && $report->status !== 'approved') {
                if (!current_user_can('cqa_edit_all_reports')) {
                    return new WP_Error('forbidden', __('You do not have permission to approve reports.', 'chroma-qa-reports'), ['status' => 403]);
                }
            }

            $report->status = $new_status;
        }

        $report->save();

        // Process Photos
        $this->process_report_photos($report->id, $request);
        $this->process_drive_files($report->id, $request);

        // [FIX] Save Checklist Responses
        $responses = $request->get_param('responses');
        if (!empty($responses) && is_array($responses)) {
            \ChromaQA\Models\Checklist_Response::bulk_save($report->id, $responses);
        }

        // Process AI Summary Updates (Plan of Improvement)
        $summary_poi = $request->get_param('summary_poi');
        if (!empty($summary_poi) && is_array($summary_poi)) {
            $existing_summary = $report->get_ai_summary();
            if ($existing_summary) {
                $ai = new \ChromaQA\AI\Executive_Summary();
                $updated_summary = $existing_summary;

                // Re-format the poi_json from the submitted data
                $poi_list = [];
                foreach ($summary_poi as $item) {
                    if (empty($item['action']))
                        continue;
                    $poi_list[] = [
                        'priority' => \sanitize_text_field($item['priority'] ?? 'ongoing'),
                        'timeline' => \sanitize_text_field($item['timeline'] ?? ''),
                        'action' => \sanitize_textarea_field($item['action']),
                    ];
                }

                $updated_summary['plan_of_improvement'] = $poi_list;
                $updated_summary['poi'] = $poi_list; // For backward compatibility in code

                $ai->save_summary($report->id, $updated_summary);
            }
        }

        return new WP_REST_Response($this->prepare_report_response($report), 200);
    }

    public function delete_report(WP_REST_Request $request)
    {
        $report = Report::find($request['id']);

        if (!$report) {
            return new WP_Error('not_found', __('Report not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        // Check ownership if user only has delete_own_reports capability
        if (!\current_user_can('cqa_delete_reports')) {
            if ($report->user_id !== \get_current_user_id()) {
                return new WP_Error('forbidden', __('You can only delete your own reports.', 'chroma-qa-reports'), ['status' => 403]);
            }
        }

        $report->delete();

        return new WP_REST_Response(null, 204);
    }

    // ===== REPORT RESPONSES =====

    public function get_report_responses(WP_REST_Request $request)
    {
        $responses = Checklist_Response::get_by_report_grouped($request['id']);
        return new WP_REST_Response($responses, 200);
    }

    public function save_report_responses(WP_REST_Request $request)
    {
        $report_id = $request['id'];
        $responses = $request->get_param('responses');

        if (!is_array($responses)) {
            return new WP_Error('invalid_data', \__('Invalid responses data.', 'chroma-qa-reports'), ['status' => 400]);
        }

        Checklist_Response::bulk_save($report_id, $responses);

        return new WP_REST_Response(['success' => true], 200);
    }

    // ===== CHECKLISTS =====

    public function get_checklist(WP_REST_Request $request)
    {
        $type = $request['type'];
        $checklist = Checklist_Manager::get_checklist_for_type($type);
        return new WP_REST_Response($checklist, 200);
    }

    public function get_school_reports(WP_REST_Request $request)
    {
        $reports = Report::all([
            'school_id' => $request['id'],
            'limit' => 10,
            'orderby' => 'inspection_date',
            'order' => 'DESC',
        ]);

        $data = array_map(function ($report) {
            return [
                'id' => $report->id,
                'report_type' => $report->report_type,
                'inspection_date' => $report->inspection_date,
                'overall_rating' => $report->overall_rating,
                'status' => $report->status,
            ];
        }, $reports);

        return new WP_REST_Response($data, 200);
    }

    // ===== AI ENDPOINTS =====

    public function generate_ai_summary(WP_REST_Request $request)
    {
        // Rate Limit: 10 per minute
        $limit_check = $this->check_rate_limit('ai_summary', 10, 60);
        if (\is_wp_error($limit_check)) {
            return $limit_check;
        }

        $report = Report::find($request['id']);

        if (!$report) {
            return new WP_Error('not_found', __('Report not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        // Use the AI Summary generator
        $ai = new \ChromaQA\AI\Executive_Summary();
        $result = $ai->generate($report);

        if (\is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($result, 200);
    }

    public function parse_document(WP_REST_Request $request)
    {
        // Rate Limit: 20 per minute
        $limit_check = $this->check_rate_limit('parse_document', 20, 60);
        if (\is_wp_error($limit_check)) {
            return $limit_check;
        }

        $files = $request->get_file_params();

        if (empty($files['document'])) {
            return new WP_Error('no_file', \__('No document provided.', 'chroma-qa-reports'), ['status' => 400]);
        }

        $parser = new \ChromaQA\AI\Document_Parser();
        $result = $parser->parse($files['document']['tmp_name']);

        if (\is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response($result, 200);
    }



    // ===== PHOTO ENDPOINTS =====

    public function upload_report_photos(WP_REST_Request $request)
    {
        // Rate Limit: 100 per minute (high volume allowed for bulk upload)
        $limit_check = $this->check_rate_limit('upload_photos', 100, 60);
        if (\is_wp_error($limit_check)) {
            return $limit_check;
        }

        $report_id = $request['id'];
        $files = $request->get_file_params();

        if (empty($files['photos'])) {
            return new WP_Error('no_photos', __('No photos provided.', 'chroma-qa-reports'), ['status' => 400]);
        }

        $report = Report::find($report_id);
        if (!$report) {
            return new WP_Error('not_found', __('Report not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        // Check permissions
        if (!\current_user_can('cqa_edit_reports')) {
            return new WP_Error('forbidden', __('Permission denied.', 'chroma-qa-reports'), ['status' => 403]);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Get school folder ID
        $school = $report->get_school();
        $folder_id = $school ? $school->drive_folder_id : null;

        $uploaded_photos = [];
        $photos = $files['photos'];

        // Normalize if single file
        if (!is_array($photos['name'])) {
            $photos = [$photos];
        } else {
            // Re-structure $_FILES array if needed (WP REST usually gives structured array per file if name="photos[]")
            // But if it comes as standard $_FILES format with arrays of names, we need to iterate.
            // Let's assume standard PHP $_FILES normalization:
            $normalized = [];
            foreach ($photos['name'] as $key => $value) {
                $normalized[] = [
                    'name' => $photos['name'][$key],
                    'type' => $photos['type'][$key],
                    'tmp_name' => $photos['tmp_name'][$key],
                    'error' => $photos['error'][$key],
                    'size' => $photos['size'][$key],
                ];
            }
            $photos = $normalized;
        }

        foreach ($photos as $file) {
            // Upload to WP Media Library
            $upload_overrides = ['test_form' => false];
            $movefile = wp_handle_upload($file, $upload_overrides);

            if ($movefile && !isset($movefile['error'])) {
                // Create attachment
                $attachment = [
                    'guid' => $movefile['url'],
                    'post_mime_type' => $movefile['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($file['name'])),
                    'post_content' => '',
                    'post_status' => 'inherit'
                ];

                $attach_id = wp_insert_attachment($attachment, $movefile['file']);

                if (!is_wp_error($attach_id)) {
                    $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);

                    // Try Google Drive Upload
                    $drive_file_id = 'wp_' . $attach_id;
                    if (\get_option('cqa_google_client_id')) {
                        $drive_result = Google_Drive::upload_file($movefile['file'], basename($movefile['file']), $folder_id);
                        if (!\is_wp_error($drive_result) && isset($drive_result['id'])) {
                            $drive_file_id = $drive_result['id'];

                            // [FIX-303] Cleanup local file if successfully uploaded to Drive to save server space?
                            // Actually, WP Media Library needs the file for thumbnails. 
                            // So we keep it in WP but record the Drive ID as the authority.
                        }
                    }

                    // Create Photo Record
                    $photo = new \ChromaQA\Models\Photo();
                    $photo->report_id = $report_id;
                    $photo->section_key = $request['section_key'] ?: 'general';
                    $photo->drive_file_id = $drive_file_id;
                    $photo->filename = basename($movefile['file']);
                    $photo->caption = sanitize_text_field($request['caption'] ?: '');
                    $photo->save();

                    $uploaded_photos[] = [
                        'id' => $photo->id,
                        'section_key' => $photo->section_key,
                        'filename' => $photo->filename,
                        'caption' => $photo->caption,
                        'thumbnail_url' => $photo->get_thumbnail_url(),
                        'view_url' => $photo->get_view_url(),
                    ];
                }
            }
        }

        return new WP_REST_Response(['success' => true, 'photos' => $uploaded_photos], 200);
    }

    public function update_photo(WP_REST_Request $request)
    {
        $photo = Photo::find($request['id']);

        if (!$photo) {
            return new WP_Error('not_found', __('Photo not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        if ($request->has_param('caption')) {
            $photo->caption = \sanitize_text_field($request->get_param('caption'));
        }

        if ($request->has_param('section_key')) {
            $photo->section_key = \sanitize_text_field($request->get_param('section_key'));
        }

        $photo->save();

        return new WP_REST_Response([
            'id' => $photo->id,
            'caption' => $photo->caption,
            'section_key' => $photo->section_key
        ], 200);
    }

    public function delete_photo(WP_REST_Request $request)
    {
        $photo = Photo::find($request['id']);

        if (!$photo) {
            return new WP_Error('not_found', __('Photo not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        $photo->delete();

        return new WP_REST_Response(['success' => true], 200);
    }

    // ===== SETTINGS ENDPOINTS =====

    public function get_settings(WP_REST_Request $request)
    {
        $settings = [
            'google_client_id' => \get_option('cqa_google_client_id'),
            'google_client_secret' => \get_option('cqa_google_client_secret'),
            'google_developer_key' => \get_option('cqa_google_developer_key'),
            'gemini_api_key' => \get_option('cqa_gemini_api_key'),
            'enable_ai' => \get_option('cqa_enable_ai', 'yes'),
        ];
        return new WP_REST_Response($settings, 200);
    }

    public function update_settings(WP_REST_Request $request)
    {
        $params = $request->get_params();

        if (isset($params['google_client_id'])) {
            \update_option('cqa_google_client_id', \sanitize_text_field($params['google_client_id']));
        }
        if (isset($params['google_client_secret'])) {
            \update_option('cqa_google_client_secret', \sanitize_text_field($params['google_client_secret']));
        }
        if (isset($params['google_developer_key'])) {
            \update_option('cqa_google_developer_key', \sanitize_text_field($params['google_developer_key']));
        }
        if (isset($params['gemini_api_key'])) {
            \update_option('cqa_gemini_api_key', \sanitize_text_field($params['gemini_api_key']));
        }
        if (isset($params['enable_ai'])) {
            \update_option('cqa_enable_ai', \sanitize_text_field($params['enable_ai']));
        }

        return new WP_REST_Response(['success' => true], 200);
    }

    public function generate_report_pdf(WP_REST_Request $request)
    {
        $report = Report::find($request['id']);

        if (!$report) {
            return new WP_Error('not_found', __('Report not found.', 'chroma-qa-reports'), ['status' => 404]);
        }

        // Nuclear Option: Disable all error reporting for PDF generation
        error_reporting(0);
        @ini_set('display_errors', 0);

        $pdf_generator = new \ChromaQA\Export\PDF_Generator();
        $pdf_path = $pdf_generator->generate($report);

        if (\is_wp_error($pdf_path)) {
            return $pdf_path;
        }

        // Clean output buffer to remove any leaked whitespace or logs
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Detect correct mime type (Fallback to HTML if PDF libs missing)
        $ext = pathinfo($pdf_path, PATHINFO_EXTENSION);
        $mime = $ext === 'html' ? 'text/html' : 'application/pdf';
        $filename = \sanitize_file_name($report->get_school()->name . '-QA-Report.' . $ext);

        // Stream the file
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        readfile($pdf_path);
        exit;
    }

    // ===== HELPERS =====

    /**
     * Process report photos (upload to Drive or fallback to Local).
     *
     * @param int             $report_id Report ID.
     * @param WP_REST_Request $request   Request object.
     */
    private function process_report_photos($report_id, $request)
    {
        // Update existing photo captions and sections
        $photo_captions = $request->get_param('photo_captions');
        $photo_sections = $request->get_param('photo_sections');

        if (!empty($photo_captions) && is_array($photo_captions)) {
            foreach ($photo_captions as $photo_id => $caption) {
                $photo = Photo::find(intval($photo_id));
                if ($photo && $photo->report_id == $report_id) {
                    $photo->caption = \sanitize_text_field($caption);

                    // Also update section if provided
                    if (!empty($photo_sections) && isset($photo_sections[$photo_id])) {
                        $photo->section_key = \sanitize_text_field($photo_sections[$photo_id]);
                    }

                    $photo->save();
                }
            }
        }

        // Delete photos marked for deletion
        $delete_photos = $request->get_param('delete_photos');
        if (!empty($delete_photos) && is_array($delete_photos)) {
            foreach ($delete_photos as $photo_id) {
                $photo = Photo::find(intval($photo_id));
                if ($photo && $photo->report_id == $report_id) {
                    $photo->delete();
                }
            }
        }

        // Process new photos
        $new_photos = $request->get_param('new_photos');

        if (empty($new_photos) || !is_array($new_photos)) {
            // Check item_photos even if new_photos is empty
            $item_photos = $request->get_param('item_photos');
            if (empty($item_photos)) {
                return;
            }
        }

        $report = Report::find($report_id);
        if (!$report) {
            return;
        }

        // Get the folder ID from the school
        $school = $report->get_school();
        $folder_id = $school ? $school->drive_folder_id : null;

        // Consolidate photo processing using memory-efficient method
        $new_photos_captions = $request->get_param('new_photos_captions') ?: [];
        $new_photos_sections = $request->get_param('new_photos_sections') ?: [];

        if (!empty($new_photos) && is_array($new_photos)) {
            // [FIX-301] Disable Base64 uploads to prevent memory exhaustion (QAR-018).
            // Frontend now uses /reports/{id}/photos multipart endpoint.
            \ChromaQA\Utils\Logger::warn('Legacy', 'Base64 upload attempt blocked', ['count' => count($new_photos)], 'Client should use multipart upload endpoint.');

            /* Legacy Base64 Logic - Disabled
            foreach ($new_photos as $index => $data_url) {
                $section = !empty($new_photos_sections[$index]) ? $new_photos_sections[$index] : 'general';
                $caption = !empty($new_photos_captions[$index]) ? $new_photos_captions[$index] : '';
                $this->process_single_photo($data_url, $report_id, $folder_id, $section, $caption);
            }
            */
        }

        // Handle Item-Specific Photos
        if (!empty($item_photos) && is_array($item_photos)) {
            // [FIX-301] Disable Base64 uploads (QAR-018)
            \ChromaQA\Utils\Logger::warn('Legacy', 'Base64 item photos blocked', [], 'Client should use multipart upload endpoint.');

            /* Legacy Base64 Logic - Disabled
            $item_captions = $request->get_param('item_photos_captions') ?: [];
            foreach ($item_photos as $section_key => $items) {
                foreach ($items as $item_key => $photos) {
                    if (!is_array($photos))
                        continue;
                    foreach ($photos as $i => $data_url) {
                        $full_section = $section_key . '|' . $item_key;
                        $caption = $item_captions[$section_key][$item_key][$i] ?? '';
                        $this->process_single_photo($data_url, $report_id, $folder_id, $full_section, $caption);
                    }
                }
            }
            */
        }
    }

    /**
     * Process a single photo with memory efficiency.
     */
    private function process_single_photo($data_url, $report_id, $folder_id, $section, $caption = '')
    {
        if (!is_string($data_url))
            return;

        // Decode Base64
        if (!preg_match('/^data:image\/(\w+);base64,/', $data_url, $type))
            return;

        $base64_str = substr($data_url, strpos($data_url, ',') + 1);
        $ext = strtolower($type[1]);
        if (!in_array($ext, ['jpg', 'jpeg', 'gif', 'png']))
            return;

        $filename = 'report-' . $report_id . '-photo-' . time() . '-' . wp_generate_uuid4() . '.' . $ext;
        $tmp_file = sys_get_temp_dir() . '/' . $filename;

        // Memory-efficient Base64 decode to file
        $fp = fopen($tmp_file, 'wb');
        if ($fp) {
            stream_filter_append($fp, 'convert.base64-decode', STREAM_FILTER_WRITE);
            fwrite($fp, $base64_str);
            fclose($fp);
            unset($base64_str); // Free up string memory
        } else {
            return;
        }

        if (filesize($tmp_file) > 10 * 1024 * 1024) {
            @unlink($tmp_file);
            return;
        }

        $drive_file_id = '';

        // 1. Try Google Drive Upload
        if (\get_option('cqa_google_client_id')) {
            $drive_result = Google_Drive::upload_file($tmp_file, $filename, $folder_id);
            if (!\is_wp_error($drive_result) && isset($drive_result['id'])) {
                $drive_file_id = $drive_result['id'];
            }
        }

        // 2. Fallback to Local Media Library
        if (empty($drive_file_id)) {
            // wp_upload_bits still needs a string, but we minimized copies elsewhere
            $decoded_data = file_get_contents($tmp_file);
            $upload = \wp_upload_bits($filename, null, $decoded_data);
            unset($decoded_data);

            if (!$upload['error']) {
                $file_path = $upload['file'];
                require_once(\ABSPATH . 'wp-admin/includes/image.php');
                require_once(\ABSPATH . 'wp-admin/includes/file.php');
                require_once(\ABSPATH . 'wp-admin/includes/media.php');

                $file_name = basename($file_path);
                $file_type = \wp_check_filetype($file_name, null);
                $attachment = [
                    'post_mime_type' => $file_type['type'],
                    'post_title' => \sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME)),
                    'post_content' => '',
                    'post_status' => 'inherit',
                ];
                $attach_id = \wp_insert_attachment($attachment, $file_path);
                $attach_data = \wp_generate_attachment_metadata($attach_id, $file_path);
                \wp_update_attachment_metadata($attach_id, $attach_data);
                $drive_file_id = 'wp_' . $attach_id;
            }
        }

        @unlink($tmp_file);

        if ($drive_file_id) {
            $photo = new Photo();
            $photo->report_id = $report_id;
            $photo->drive_file_id = $drive_file_id;
            $photo->filename = $filename;
            $photo->caption = \sanitize_text_field($caption);
            $photo->section_key = \sanitize_text_field($section);
            $photo->save();
        }
    }

    private function process_drive_files($report_id, $request)
    {
        $drive_files = $request->get_param('drive_files');

        if (empty($drive_files) || !is_array($drive_files)) {
            return;
        }

        foreach ($drive_files as $file_id) {
            if (!is_string($file_id))
                continue;

            // Check if already attached? (Optional, but good practice)
            // For now, just add new records.
            // In a real app, we might check to avoid duplicates if re-saving.

            // We need metadata (filename, etc) but the picker only sent ID in the hidden input.
            // We'll have to fetch it or just save the ID for now.
            // The picker JS callback had the name, but only inputs value=ID.
            // Ideally, we should have sent an object or array of data.
            // For now, we'll save with a placeholder filename or fetch it if we had the service.
            // Let's just save the ID. The Photo model can handle fetching metadata on view if needed,
            // or we accept that we don't have the filename yet.

            $photo = new Photo();
            $photo->report_id = $report_id;
            $photo->drive_file_id = $file_id;
            $photo->filename = 'drive-file-' . $file_id; // Placeholder
            $photo->section_key = 'general';
            $photo->caption = '';
            $photo->save();
        }
    }

    private function prepare_school_response($school)
    {
        return [
            'id' => $school->id,
            'name' => $school->name,
            'location' => $school->location,
            'address' => $school->location,
            'region' => $school->region,
            'tier' => (int) $school->tier ?: 1,
            'acquired_date' => $school->acquired_date,
            'status' => $school->status,
            'drive_folder_id' => $school->drive_folder_id,
            'classroom_config' => $school->classroom_config,
            'created_at' => $school->created_at,
            'last_inspection_date' => $school->last_inspection_date,
            'reports_count' => $school->reports_count ?? 0,
        ];
    }

    private function prepare_report_response($report, $include_details = false)
    {
        // Get author info for display
        $author = \get_userdata($report->user_id);
        $author_name = $author ? $author->display_name : 'Unknown';

        // Derive tier from report_type for frontend display
        $tier = 1; // Default tier
        if ($report->report_type === 'tier1') {
            $tier = 1;
        } elseif ($report->report_type === 'tier1_tier2') {
            $tier = 2;
        } elseif ($report->report_type === 'new_acquisition') {
            $tier = 0; // New acquisition = Tier 0
        }

        $data = [
            'id' => $report->id,
            'school_id' => $report->school_id,
            'user_id' => $report->user_id,
            'author_name' => $author_name,
            'report_type' => $report->report_type,
            'report_type_label' => $report->get_type_label(),
            'tier' => $tier,                          // Frontend tier display
            'inspection_date' => $report->inspection_date,
            'visit_date' => $report->inspection_date, // Frontend alias
            'date' => $report->inspection_date,       // Dashboard alias
            'previous_report_id' => $report->previous_report_id,
            'overall_rating' => $report->overall_rating,
            'rating' => $report->get_rating_label(),  // Frontend rating display
            'rating_label' => $report->get_rating_label(),
            'status' => $report->status,
            'status_label' => $report->get_status_label(),
            'created_at' => $report->created_at,
            'updated_at' => $report->updated_at,
            'version_id' => $report->version_id,
            'school_name' => $report->get_school() ? $report->get_school()->name : 'Unknown School',
            'is_mine' => ($report->user_id == \get_current_user_id()),
        ];

        if ($include_details) {
            $school = $report->get_school();
            $data['school'] = $school ? $this->prepare_school_response($school) : null;
            $data['responses'] = Checklist_Response::get_by_report_grouped($report->id);
            $data['photos'] = array_map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'section_key' => $photo->section_key,
                    'filename' => $photo->filename,
                    'caption' => $photo->caption,
                    'thumbnail_url' => $photo->get_thumbnail_url(),
                    'view_url' => $photo->get_view_url(),
                ];
            }, $report->get_photos());
            $data['ai_summary'] = $report->get_ai_summary();
            $data['closing_notes'] = $report->closing_notes;
        }

        return $data;
    }
    /**
     * Handle DOCX Report Upload and Parsing.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function upload_report_doc(WP_REST_Request $request)
    {
        $files = $request->get_file_params();

        if (empty($files['file'])) {
            return new WP_Error('no_file', __('No file uploaded.', 'chroma-qa-reports'), ['status' => 400]);
        }

        $file = $files['file'];

        // 1. Move to temp location
        $upload = \wp_handle_upload($file, ['test_form' => false]);
        if (isset($upload['error'])) {
            return new WP_Error('upload_error', $upload['error'], ['status' => 500]);
        }

        $file_path = $upload['file'];

        // 2. Extract Text
        require_once CQA_PLUGIN_DIR . 'includes/utils/class-docx-parser.php';
        $text = Docx_Parser::extract_text($file_path);

        if (\is_wp_error($text)) {
            // cleanup
            @unlink($file_path);
            return $text;
        }

        // 3. Parse with AI
        $parsed_data = Gemini_Service::parse_document($text);

        // Cleanup temp file
        @unlink($file_path);

        if (\is_wp_error($parsed_data)) {
            return $parsed_data;
        }

        return new WP_REST_Response($parsed_data, 200);
    }

    /**
     * Validate inspection date.
     * Ensure YYYY-MM-DD format and no future dates.
     *
     * @param string $date Date string.
     * @return bool|WP_Error
     */
    private function validate_inspection_date($date)
    {
        if (empty($date)) {
            // Check if it's optional? Using create_report it seems required or at least processed.
            // If empty string passed to createFromFormat it might fail or return false.
            // Let's assume strictness.
            return new WP_Error('invalid_date', __('Inspection date is required.', 'chroma-qa-reports'), ['status' => 400]);
        }

        $d = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            return new WP_Error('invalid_date_format', __('Invalid date format. Use YYYY-MM-DD.', 'chroma-qa-reports'), ['status' => 400]);
        }

        // Use WP local time for "today" comparison to align with user's timezone expectations
        $today = \wp_date('Y-m-d');
        if ($date > $today) {
            return new WP_Error('future_date', __('Inspection date cannot be in the future.', 'chroma-qa-reports'), ['status' => 400]);
        }

        return true;
    }

    /**
     * Check rate limit for the current user/IP.
     *
     * @param string $action Action name.
     * @param int    $limit  Max requests.
     * @param int    $window Time window in seconds.
     * @return bool|WP_Error True if allowed, WP_Error if exceeded.
     */
    private function check_rate_limit($action, $limit = 60, $window = 60)
    {
        $user_id = \get_current_user_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'cqa_rl_' . $action . '_' . ($user_id ? $user_id : $ip);

        $data = \get_transient($key);

        if (!$data) {
            $data = ['count' => 1, 'expiry' => time() + $window];
            \set_transient($key, $data, $window);
        } else {
            if ($data['count'] >= $limit) {
                return new \WP_Error('rate_limit_exceeded', \__('Too many requests. Please slow down.', 'chroma-qa-reports'), ['status' => 429]);
            }
            $data['count']++;
            // Calculate remaining time
            $remaining = $data['expiry'] - time();
            if ($remaining < 0)
                $remaining = 0;
            \set_transient($key, $data, $remaining);
        }

        return true;
    }

    /**
     * Get dynamic PWA manifest.
     *
     * @return \WP_REST_Response
     */
    public function get_manifest()
    {
        $plugin_url = CQA_PLUGIN_URL;
        $admin_url = admin_url('admin.php?page=chroma-qa-reports');

        $manifest = [
            'name' => 'Chroma QA Reports',
            'short_name' => 'QA Reports',
            'description' => 'Quality Assurance Report Management System for Chroma Early Learning Academy',
            'start_url' => $admin_url,
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#6366f1',
            'orientation' => 'portrait-primary',
            'icons' => [
                ['src' => $plugin_url . 'assets/images/icon-72.png', 'sizes' => '72x72', 'type' => 'image/png'],
                ['src' => $plugin_url . 'assets/images/icon-96.png', 'sizes' => '96x96', 'type' => 'image/png'],
                ['src' => $plugin_url . 'assets/images/icon-128.png', 'sizes' => '128x128', 'type' => 'image/png'],
                ['src' => $plugin_url . 'assets/images/icon-144.png', 'sizes' => '144x144', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => $plugin_url . 'assets/images/icon-152.png', 'sizes' => '152x152', 'type' => 'image/png'],
                ['src' => $plugin_url . 'assets/images/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => $plugin_url . 'assets/images/icon-384.png', 'sizes' => '384x384', 'type' => 'image/png'],
                ['src' => $plugin_url . 'assets/images/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
            'shortcuts' => [
                [
                    'name' => 'Create Report',
                    'short_name' => 'New Report',
                    'url' => admin_url('admin.php?page=chroma-qa-reports-create'),
                    'icons' => [['src' => $plugin_url . 'assets/images/icon-new.png', 'sizes' => '96x96']]
                ],
                [
                    'name' => 'View Schools',
                    'short_name' => 'Schools',
                    'url' => admin_url('admin.php?page=chroma-qa-reports-schools')
                ]
            ]
        ];

        return new \WP_REST_Response($manifest, 200, ['Content-Type' => 'application/manifest+json']);
    }
}

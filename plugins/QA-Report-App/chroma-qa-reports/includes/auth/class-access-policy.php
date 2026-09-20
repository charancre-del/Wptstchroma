<?php
/** Central authorization for the QA application. */
namespace ChromaQA\Auth;

use ChromaQA\Models\Report;
use ChromaQA\Models\School;
use ChromaQA\Models\Photo;
use ChromaQA\Models\Report_Snapshot;

final class Access_Policy
{
    public static function init()
    {
        add_filter('rest_request_before_callbacks', [self::class, 'guard_rest'], 5, 3);
        add_action('admin_init', [self::class, 'guard_admin']);
    }

    public static function guard_admin()
    {
        // Core has already normalized the page via plugin_basename before
        // admin_init. Authorize the dispatched page, not its raw query alias.
        $page = isset($GLOBALS['plugin_page']) && is_string($GLOBALS['plugin_page']) ? $GLOBALS['plugin_page'] : '';
        if (strpos($page, 'chroma-qa-reports') !== 0 || $page === 'chroma-qa-reports-docs') { return; }
        self::require_access(self::can_read());
        if (in_array($page, ['chroma-qa-reports-settings', 'chroma-qa-reports-tools'], true)) {
            self::require_access(self::settings());
        }
        if (in_array($page, ['chroma-qa-reports-schools', 'chroma-qa-reports-school-edit'], true)) {
            self::require_access(self::capability('cqa_manage_schools'));
            if (!empty($_GET['id'])) { self::require_access(self::school(School::find((int) $_GET['id']))); }
        }
        if ($page === 'chroma-qa-reports-create' || $page === 'chroma-qa-reports-import') {
            self::require_access(self::capability('cqa_create_reports'));
            if (!empty($_GET['id'])) { self::require_access(self::report(Report::find((int) $_GET['id']), 'edit')); }
        }
        if ($page === 'chroma-qa-reports-view') {
            self::require_access(self::report(Report::find((int) ($_GET['id'] ?? 0))));
        }
    }

    public static function active()
    {
        if (!get_current_user_id()) {
            return false;
        }
        $state = get_user_meta(get_current_user_id(), 'cqa_account_status', true);
        // Existing WP administrators retain a provisioning/recovery path. An
        // explicit suspended/pending state always wins, including for admins.
        return $state === 'active' || ($state === '' && current_user_can('manage_options'));
    }

    public static function global_scope()
    {
        return self::active() && (current_user_can('manage_options') ||
            (get_user_meta(get_current_user_id(), 'cqa_scope', true) === 'global' && current_user_can('cqa_view_all_reports')));
    }

    public static function school_ids()
    {
        $values = (array) get_user_meta(get_current_user_id(), 'cqa_school_ids', true);
        $values[] = get_user_meta(get_current_user_id(), 'cqa_school_id', true);
        $values = array_filter($values, static function ($id) { return (is_int($id) || (is_string($id) && ctype_digit($id))) && (int) $id > 0; });
        return array_values(array_unique(array_map('intval', $values)));
    }

    public static function regions()
    {
        $values = (array) get_user_meta(get_current_user_id(), 'cqa_regions', true);
        $values[] = get_user_meta(get_current_user_id(), 'cqa_region', true);
        return array_values(array_unique(array_filter(array_map('trim', array_filter($values, 'is_string')), static function ($region) { return $region !== ''; })));
    }

    public static function scoped()
    {
        return self::active() && (self::global_scope() || self::school_ids() || self::regions());
    }

    public static function capability($cap)
    {
        return self::scoped() && (current_user_can('manage_options') || current_user_can($cap));
    }

    public static function can_read()
    {
        return self::capability('cqa_view_all_reports') || self::capability('cqa_view_own_reports');
    }

    public static function school($school)
    {
        return self::scoped() && $school && (self::global_scope() ||
            in_array((int) $school->id, self::school_ids(), true) || in_array((string) $school->region, self::regions(), true));
    }

    public static function report($report, $action = 'read')
    {
        if (!$report || !self::school(School::find($report->school_id))) {
            return false;
        }
        $own = (int) $report->user_id === (int) get_current_user_id();
        $read = self::capability('cqa_view_all_reports') || ($own && self::capability('cqa_view_own_reports'));
        if ($action === 'read') { return $read; }
        if ($action === 'export') { return $read && self::capability('cqa_export_reports'); }
        if ($action === 'approve' || $action === 'sync') { return $read && self::capability('cqa_approve_reports'); }
        if ($action === 'delete') {
            return $read && ($report->status !== 'approved' || self::capability('cqa_approve_reports')) &&
                (self::capability('cqa_delete_reports') || ($own && self::capability('cqa_delete_own_reports')));
        }
        $edit = $read && (self::capability('cqa_edit_all_reports') || ($own && self::capability('cqa_edit_own_reports')) || self::capability('cqa_approve_reports')) &&
            ($report->status !== 'approved' || self::capability('cqa_approve_reports'));
        return $edit && ($action !== 'ai' || self::capability('cqa_use_ai_features'));
    }

    public static function settings()
    {
        return self::global_scope() && self::capability('cqa_manage_settings');
    }

    public static function deny()
    {
        return new \WP_Error('cqa_forbidden', __('You do not have access to this QA resource.', 'chroma-qa-reports'), ['status' => 403]);
    }

    public static function require_access($allowed)
    {
        if (!$allowed) {
            wp_die(__('You do not have access to this QA resource.', 'chroma-qa-reports'), 'Access denied', ['response' => 403]);
        }
    }

    /** Only scheduler/CLI jobs bypass interactive list scoping, never URL data. */
    private static function system_query()
    {
        return (defined('DOING_CRON') && DOING_CRON) || (defined('WP_CLI') && WP_CLI);
    }

    public static function school_sql($alias = '')
    {
        global $wpdb;
        if (self::system_query() || self::global_scope()) { return '1=1'; }
        if (!self::can_read()) { return '1=0'; }
        $prefix = $alias === '' ? '' : $alias . '.';
        $clauses = [];
        $ids = self::school_ids();
        if ($ids) { $clauses[] = $prefix . 'id IN (' . implode(',', $ids) . ')'; }
        foreach (self::regions() as $region) {
            $clauses[] = $wpdb->prepare($prefix . 'region = %s', $region);
        }
        return $clauses ? '(' . implode(' OR ', $clauses) . ')' : '1=0';
    }

    public static function report_sql($alias = '')
    {
        global $wpdb;
        if (self::system_query()) { return '1=1'; }
        if (!self::can_read()) { return '1=0'; }
        $prefix = $alias === '' ? '' : $alias . '.';
        $school_scope = self::school_sql('qa_scope_school');
        $sql = $prefix . "school_id IN (SELECT qa_scope_school.id FROM {$wpdb->prefix}cqa_schools qa_scope_school WHERE {$school_scope})";
        if (!self::capability('cqa_view_all_reports')) {
            $sql .= $wpdb->prepare(' AND ' . $prefix . 'user_id = %d', get_current_user_id());
        }
        return '(' . $sql . ')';
    }

    /** Validate every representation of report status and linked school/report. */
    public static function report_state($school_id, $previous_id, $status)
    {
        $school = School::find((int) $school_id);
        if (!self::school($school) || $school->status !== 'active') { return self::deny(); }
        if (!in_array($status, ['draft', 'submitted', 'under_review', 'needs_revision', 'approved'], true)) {
            return new \WP_Error('invalid_status', 'Invalid report status.', ['status' => 400]);
        }
        if ($status === 'approved' && !self::capability('cqa_approve_reports')) { return self::deny(); }
        if ($previous_id) {
            $previous = Report::find((int) $previous_id);
            if (!$previous || (int) $previous->school_id !== (int) $school_id || !self::report($previous)) { return self::deny(); }
        }
        return true;
    }

    /** Runs for all registered cqa/v1 handlers, including extension controllers. */
    public static function guard_rest($response, $handler, $request)
    {
        // WordPress matches registered REST routes case-insensitively.
        $route = strtolower($request->get_route());
        if (strpos($route, '/cqa/v1/') !== 0 || $response !== null) { return $response; }
        $path = substr($route, strlen('/cqa/v1'));
        $read = in_array($request->get_method(), ['GET', 'HEAD'], true);
        if ($path === '/manifest') { return $response; }
        if (!self::scoped()) { return self::deny(); }
        // WP request JSON/body/query parameters can shadow URL captures. The
        // object authorized here must be the same object the handler receives.
        if (preg_match('#^/(?:reports|schools|photos|analytics/school)/(\d+)(?:/|$)#', $path, $identity) &&
            !self::same_id($request['id'], $identity[1])) { return self::deny(); }
        if (preg_match('#^/reports/\d+/(?:versions|restore)/(\d+)(?:/|$)#', $path, $version) &&
            !self::same_id($request['version'], $version[1])) { return self::deny(); }
        if ($path === '/me') { return $response; }
        if (strpos($path, '/settings') === 0 || strpos($path, '/monday/') === 0) {
            return self::settings() ? $response : self::deny();
        }
        if ($path === '/system-check') { return self::capability('manage_options') ? $response : self::deny(); }
        if (preg_match('#^/reports/(\d+)(.*)$#', $path, $m)) {
            $report = Report::find((int) $m[1]);
            $suffix = $m[2];
            $action = $read ? 'read' : 'edit';
            if ($suffix === '/pdf') { $action = 'export'; }
            if ($suffix === '/generate-summary') { $action = 'ai'; }
            if ($suffix === '/sync-monday') { $action = 'sync'; }
            if ($request->get_method() === 'DELETE') { $action = 'delete'; }
            if ($suffix === '/workflow' && $request['action'] !== 'submit') { $action = 'approve'; }
            if (!self::report($report, $action)) { return self::deny(); }
            if ($read && preg_match('#^/versions/(\d+)$#', $suffix, $v)) {
                $snapshot = Report_Snapshot::get_snapshot($report->id, (int) $v[1]);
                if ($snapshot && !self::snapshot_visible($snapshot, $report)) { return self::deny(); }
            }
            if (!$read) {
                $state = ['school_id' => $report->school_id, 'previous_report_id' => $report->previous_report_id, 'status' => $report->status];
                if (preg_match('#^/restore/(\d+)$#', $suffix, $v) || preg_match('#^/versions/(\d+)/restore-selection$#', $suffix, $v)) {
                    $snapshot = Report_Snapshot::get_snapshot($report->id, (int) $v[1]);
                    if ($snapshot && !self::snapshot_visible($snapshot, $report)) { return self::deny(); }
                    $saved = $snapshot['snapshot_data']['report'] ?? [];
                    if (strpos($suffix, '/restore/') === 0) { $state = array_merge($state, $saved); }
                    elseif ($request['target_type'] === 'report_field' && array_key_exists((string) $request['field'], $saved)) {
                        $state[(string) $request['field']] = $saved[(string) $request['field']];
                    }
                } else {
                    foreach (array_keys($state) as $key) {
                        if ($request->has_param($key)) { $state[$key] = $request->get_param($key); }
                    }
                }
                $valid = self::report_state($state['school_id'], $state['previous_report_id'], $state['status']);
                if (is_wp_error($valid)) { return $valid; }
                if ((int) $state['previous_report_id'] === (int) $report->id) { return self::deny(); }
                if ($request->has_param('drive_files')) {
                    $files = self::drive_files($request['drive_files'], School::find((int) $state['school_id']));
                    if (is_wp_error($files)) { return $files; }
                }
            }
            return $response;
        }
        if (preg_match('#^/photos/(\d+)$#', $path, $m)) {
            $photo = Photo::find((int) $m[1]);
            return $photo && self::report(Report::find($photo->report_id), 'edit') ? $response : self::deny();
        }
        if ($path === '/reports') {
            if ($read) { return self::can_read() ? $response : self::deny(); }
            if (!self::capability('cqa_create_reports')) { return self::deny(); }
            $valid = self::report_state($request['school_id'], $request['previous_report_id'], $request['status'] ?: 'draft');
            if (is_wp_error($valid)) { return $valid; }
            if ($request->has_param('drive_files')) {
                $files = self::drive_files($request['drive_files'], School::find((int) $request['school_id']));
                if (is_wp_error($files)) { return $files; }
            }
            return $response;
        }
        if ($path === '/reports/upload-doc' || $path === '/ai/parse-document') {
            return self::capability('cqa_create_reports') && self::capability('cqa_use_ai_features') ? $response : self::deny();
        }
        if ($path === '/schools') {
            if ($read) { return self::can_read() ? $response : self::deny(); }
            if (!self::school_mapping_allowed($request)) { return self::deny(); }
            return self::can_create_school((string) $request['region']) ? $response : self::deny();
        }
        if (preg_match('#^/schools/(\d+)(/reports)?$#', $path, $m)) {
            $school = School::find((int) $m[1]);
            if (!self::school($school) || !self::can_read() || (!$read && !self::capability('cqa_manage_schools'))) { return self::deny(); }
            if (!$read && !self::school_mapping_allowed($request)) { return self::deny(); }
            if (!$read && $request->has_param('region') && (string) $request['region'] !== (string) $school->region &&
                !self::can_create_school((string) $request['region'])) { return self::deny(); }
            return $response;
        }
        if (strpos($path, '/location/') === 0) {
            return self::capability('cqa_create_reports') && self::school(School::find((int) $request['school_id'])) ? $response : self::deny();
        }
        if (strpos($path, '/analytics/school/') === 0 && preg_match('#^/analytics/school/(\d+)/(trend|export)$#', $path, $m)) {
            return self::capability('cqa_view_all_reports') && self::school(School::find((int) $m[1])) &&
                ($m[2] !== 'export' || self::capability('cqa_export_reports')) ? $response : self::deny();
        }
        if ($path === '/insights/company') {
            return self::global_scope() && self::capability('cqa_use_ai_features') ? $response : self::deny();
        }
        if ($path === '/photos/analyze' || $path === '/photos/batch-analyze') {
            // Photo analysis must use records; an arbitrary URL/path is not proof
            // that this account is authorized to disclose the image to AI.
            $ids = $path === '/photos/analyze' ? [$request['photo_id']] : $request['photo_ids'];
            if (!is_array($ids) || !$ids || count($ids) > 20) { return self::deny(); }
            foreach ($ids as $id) {
                $photo = Photo::find((int) $id);
                if (!$photo || !self::report(Report::find($photo->report_id), 'ai')) { return self::deny(); }
            }
            return $response;
        }
        if ($path === '/stats' || strpos($path, '/checklists/') === 0) { return self::can_read() ? $response : self::deny(); }
        if ($path === '/analytics/company' || $path === '/analytics/regional') {
            return self::capability('cqa_view_all_reports') ? $response : self::deny();
        }
        return self::deny();
    }

    public static function can_create_school($region)
    {
        return self::capability('cqa_manage_schools') && (self::global_scope() || in_array($region, self::regions(), true));
    }

    private static function same_id($value, $expected)
    {
        return (is_int($value) || (is_string($value) && ctype_digit($value))) &&
            (int) $value > 0 && (int) $value === (int) $expected;
    }

    public static function snapshot_visible($snapshot, $report)
    {
        $saved = $snapshot['snapshot_data']['report'] ?? null;
        if (!is_array($saved) || empty($saved['school_id'])) { return false; }
        $historical = clone $report;
        $historical->school_id = (int) $saved['school_id'];
        $historical->user_id = isset($saved['user_id']) ? (int) $saved['user_id'] : $report->user_id;
        return self::report($historical);
    }

    private static function school_mapping_allowed($request)
    {
        if (self::settings()) { return true; }
        $school = $request->has_param('id') ? School::find((int) $request['id']) : null;
        foreach (array_keys($request->get_params()) as $field) {
            if (($field === 'drive_folder_id' || strpos($field, 'monday_') === 0) &&
                (string) $request[$field] !== (string) ($school->$field ?? '')) { return false; }
        }
        return true;
    }

    public static function drive_files($ids, $school)
    {
        if (!is_array($ids) || count($ids) > 100) { return self::deny(); }
        foreach ($ids as $id) {
            if (!is_string($id) || !preg_match('/^[A-Za-z0-9_-]+$/D', $id) || strpos($id, 'wp_') === 0 || !$school->drive_folder_id) {
                return self::deny();
            }
            $file = \ChromaQA\Integrations\Google_Drive::get_file($id);
            if (is_wp_error($file)) { return new \WP_Error('cqa_drive_unavailable', 'Could not verify the selected Drive file. Try again.', ['status' => 503]); }
            if (!empty($file['trashed']) || strpos((string) ($file['mimeType'] ?? ''), 'image/') !== 0 ||
                !in_array((string) $school->drive_folder_id, (array) ($file['parents'] ?? []), true)) { return self::deny(); }
        }
        return true;
    }
}

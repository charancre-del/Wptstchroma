<?php
namespace ChromaQA\Tests\Security;

use ChromaQA\Auth\Access_Policy as Policy;
use ChromaQA\API\REST_Controller;
use ChromaQA\Models\Report;
use ChromaQA\Models\School;
use ChromaQA\Settings;
use PHPUnit\Framework\TestCase;

/** Exercises the real shared REST boundary before any handler side effects. */
class AccessPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['cqa_test_user_id'] = 10;
        $GLOBALS['cqa_test_meta'] = [10 => ['cqa_account_status' => 'active', 'cqa_school_id' => 1]];
        $GLOBALS['cqa_test_caps'] = array_fill_keys(['cqa_view_all_reports', 'cqa_create_reports', 'cqa_edit_own_reports', 'cqa_export_reports', 'cqa_use_ai_features'], true);
        $GLOBALS['cqa_test_options'] = [];
        $GLOBALS['wpdb'] = new PolicyDatabase();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['cqa_test_user_id'], $GLOBALS['cqa_test_meta'], $GLOBALS['cqa_test_caps'], $GLOBALS['cqa_test_options']);
    }

    private function request($method, $path, $params = [])
    {
        if (preg_match('#/(?:reports|schools|photos|analytics/school)/(\d+)#', $path, $m) && !array_key_exists('id', $params)) { $params['id'] = (int) $m[1]; }
        if (preg_match('#/(?:versions|restore)/(\d+)#', $path, $m) && !array_key_exists('version', $params)) { $params['version'] = (int) $m[1]; }
        return Policy::guard_rest(null, [], new \WP_REST_Request($method, '/cqa/v1' . $path, $params));
    }

    private function denied($result)
    {
        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame(403, $result->get_error_data()['status']);
    }

    public static function protectedRoutes()
    {
        return array_map(static function ($row) { return $row; }, [
            ['GET', '/reports'], ['GET', '/reports/2'], ['POST', '/reports/2'],
            ['GET', '/reports/2/responses'], ['POST', '/reports/2/responses'],
            ['GET', '/reports/2/versions'], ['GET', '/reports/2/versions/1'],
            ['POST', '/reports/2/restore/1'], ['POST', '/reports/2/versions/1/restore-selection'],
            ['POST', '/reports/2/photos'], ['PATCH', '/photos/2'], ['DELETE', '/photos/2'],
            ['GET', '/reports/2/pdf'], ['POST', '/reports/2/generate-summary'], ['POST', '/reports/2/sync-monday'],
            ['GET', '/reports/2/comments'], ['POST', '/reports/2/workflow'],
            ['GET', '/schools'], ['GET', '/schools/2'], ['GET', '/schools/2/reports'],
            ['POST', '/location/verify'], ['POST', '/location/log-override'],
            ['GET', '/stats'], ['GET', '/analytics/company'], ['GET', '/analytics/regional'],
            ['GET', '/analytics/school/2/trend'], ['GET', '/analytics/school/2/export'],
            ['GET', '/insights/company'], ['POST', '/photos/analyze'], ['POST', '/photos/batch-analyze'],
            ['POST', '/reports/upload-doc'], ['POST', '/ai/parse-document'], ['GET', '/settings'], ['POST', '/monday/test'],
        ]);
    }

    /** @dataProvider protectedRoutes */
    public function test_pending_or_unscoped_users_cannot_reach_any_handler($method, $path)
    {
        $GLOBALS['cqa_test_meta'][10]['cqa_account_status'] = 'pending_approval';
        $this->denied($this->request($method, $path));
        $GLOBALS['cqa_test_meta'][10] = ['cqa_account_status' => 'active'];
        $this->denied($this->request($method, $path));
        $GLOBALS['cqa_test_user_id'] = 0;
        $this->denied($this->request($method, $path));
    }

    public function test_cross_school_objects_are_denied_but_scoped_owner_can_edit()
    {
        foreach (['', '/responses', '/versions', '/pdf', '/comments'] as $suffix) {
            $this->denied($this->request('GET', '/reports/2' . $suffix));
        }
        $this->denied($this->request('POST', '/reports/2'));
        $this->denied($this->request('POST', '/reports/1', ['school_id' => 2]));
        $this->denied($this->request('POST', '/reports/1', ['previous_report_id' => 2]));
        $this->denied($this->request('DELETE', '/photos/2'));
        $this->assertNull($this->request('POST', '/reports/1'));
        $this->assertNull($this->request('GET', '/reports/1/pdf'));
        $this->assertNull($this->request('POST', '/reports/1/generate-summary'));
    }

    public function test_own_capability_does_not_read_another_author_in_the_same_school()
    {
        $GLOBALS['cqa_test_caps'] = ['cqa_view_own_reports' => true];
        $this->denied($this->request('GET', '/reports/3'));
        $this->assertNull($this->request('GET', '/reports/1'));
        $this->assertStringContainsString('r.user_id = 10', Policy::report_sql('r'));
    }

    public function test_body_or_query_cannot_shadow_authorized_url_identity()
    {
        foreach (['/reports/1', '/reports/1/responses', '/reports/1/generate-summary', '/schools/1/reports', '/analytics/school/1/trend'] as $route) {
            $this->denied($this->request('GET', $route, ['id' => 2]));
            $this->denied($this->request('POST', $route, ['id' => 2]));
        }
        $this->denied($this->request('GET', '/reports/1/versions/7', ['version' => 8]));
        $this->denied($this->request('POST', '/reports/1/restore/7', ['version' => 8]));
        $this->assertNull($this->request('GET', '/reports/1', ['id' => '1']));
    }

    public function test_wordpress_case_insensitive_route_aliases_keep_the_same_boundary()
    {
        $this->denied(Policy::guard_rest(null, [], new \WP_REST_Request('GET', '/CQA/v1/REPORTS/2', ['id' => 2])));
        $this->denied(Policy::guard_rest(null, [], new \WP_REST_Request('POST', '/Cqa/V1/Reports', ['school_id' => 2, 'status' => 'approved'])));
        $this->assertNull(Policy::guard_rest(null, [], new \WP_REST_Request('GET', '/CQA/V1/REPORTS/1', ['id' => 1])));
    }

    public function test_admin_gate_uses_the_page_normalized_by_wordpress()
    {
        $original_get = $_GET;
        $GLOBALS['plugin_page'] = 'chroma-qa-reports-view';
        try {
            foreach (['chroma-qa-reports-view', '/chroma-qa-reports-view', 'chroma-qa-reports-view/'] as $alias) {
                $_GET = ['page' => $alias, 'id' => 2];
                try {
                    Policy::guard_admin();
                    $this->fail('Out-of-scope normalized admin page was allowed.');
                } catch (\RuntimeException $error) {
                    $this->assertSame(403, $error->getCode());
                }
            }
            $_GET = ['page' => '/chroma-qa-reports-view', 'id' => 1];
            $this->assertNull(Policy::guard_admin());
        } finally {
            $_GET = $original_get;
            unset($GLOBALS['plugin_page']);
        }
    }

    public function test_approved_create_update_and_full_restore_require_approval()
    {
        $this->denied($this->request('POST', '/reports', ['school_id' => 1, 'status' => 'approved']));
        $this->denied($this->request('POST', '/reports/1', ['status' => 'approved']));
        $this->denied($this->request('POST', '/reports/1/restore/7'));
        $this->denied($this->request('POST', '/reports/1/versions/7/restore-selection', ['target_type' => 'report_field', 'field' => 'status']));
        $GLOBALS['cqa_test_caps']['cqa_approve_reports'] = true;
        $this->assertNull($this->request('POST', '/reports/1/restore/7'));
        $this->assertNull($this->request('POST', '/reports', ['school_id' => 1, 'status' => 'approved']));
        $this->denied($this->request('POST', '/reports/1/restore/8'));
    }

    public function test_approved_lock_covers_ai_photos_and_responses()
    {
        $GLOBALS['wpdb']->approved = true;
        foreach (['', '/photos', '/responses', '/generate-summary'] as $suffix) {
            $this->denied($this->request('POST', '/reports/1' . $suffix));
        }
        $this->denied($this->request('PATCH', '/photos/1'));
        $GLOBALS['cqa_test_caps']['cqa_approve_reports'] = true;
        $this->assertNull($this->request('POST', '/reports/1/generate-summary'));
    }

    public function test_regional_approver_can_review_but_edit_all_is_not_approval()
    {
        $GLOBALS['cqa_test_meta'][10] = ['cqa_account_status' => 'active', 'cqa_region' => 'North'];
        $GLOBALS['cqa_test_caps']['cqa_approve_reports'] = true;
        $this->assertNull($this->request('POST', '/reports/3/workflow', ['action' => 'approve']));
        $this->denied($this->request('POST', '/reports/2/workflow', ['action' => 'approve']));
        unset($GLOBALS['cqa_test_caps']['cqa_approve_reports']);
        $GLOBALS['cqa_test_caps']['cqa_edit_all_reports'] = true;
        $this->denied($this->request('POST', '/reports/3/workflow', ['action' => 'approve']));
    }

    public function test_list_and_count_intersect_request_filters_with_the_same_scope()
    {
        Report::all(['school_id' => 2]);
        Report::count(['school_id' => 2]);
        School::count();
        $queries = $GLOBALS['wpdb']->queries;
        $this->assertStringContainsString('qa_scope_school.id IN (1)', $queries[0]);
        $this->assertStringContainsString('r.school_id = 2', $queries[0]);
        $this->assertStringContainsString('qa_scope_school.id IN (1)', $queries[1]);
        $this->assertStringContainsString('r.school_id = 2', $queries[1]);
        $this->assertStringContainsString('WHERE (id IN (1))', $queries[2]);
    }

    public function test_school_scoped_manager_can_save_unchanged_region_but_not_expand_scope()
    {
        $GLOBALS['cqa_test_caps']['cqa_manage_schools'] = true;
        $this->assertNull($this->request('POST', '/schools/1', ['name' => 'Renamed school', 'region' => 'North']));
        $this->denied($this->request('POST', '/schools/1', ['region' => 'South']));
        $this->denied($this->request('POST', '/schools/2', ['region' => 'North']));
        $this->denied($this->request('POST', '/schools', ['region' => 'North']));
        $this->denied($this->request('POST', '/schools/1', ['drive_folder_id' => 'different-folder']));
        $this->assertNull($this->request('POST', '/schools/1', ['drive_folder_id' => 'synthetic-folder']));
    }

    public function test_ai_requires_authorized_photo_records_and_ai_capability()
    {
        $this->denied($this->request('POST', '/photos/analyze', ['url' => '/tmp/arbitrary.jpg']));
        $this->denied($this->request('POST', '/photos/batch-analyze', ['photo_ids' => [1, 2]]));
        $this->assertNull($this->request('POST', '/photos/analyze', ['photo_id' => 1]));
        unset($GLOBALS['cqa_test_caps']['cqa_use_ai_features']);
        $this->denied($this->request('POST', '/photos/analyze', ['photo_id' => 1]));
    }

    public function test_local_attachment_alias_is_rejected_before_provider_lookup()
    {
        $school = School::find(1);
        $this->denied(Policy::drive_files(['wp_123'], $school));
        $this->denied(Policy::drive_files(['../123'], $school));
    }

    public function test_settings_never_return_secrets_and_blank_preserves_both_stores()
    {
        $GLOBALS['cqa_test_caps']['manage_options'] = true;
        foreach (Settings::secret_fields() as $field) {
            $GLOBALS['cqa_test_options']['cqa_' . $field] = 'synthetic-' . $field;
            $GLOBALS['cqa_test_options']['cqa_settings'][$field] = 'synthetic-' . $field;
        }
        $controller = new REST_Controller();
        $response = $controller->get_settings(new \WP_REST_Request());
        foreach (Settings::secret_fields() as $field) {
            $this->assertArrayNotHasKey($field, $response->get_data());
            $this->assertTrue($response->get_data()[$field . '_configured']);
        }
        foreach (['', '   ', '*****', '••••', '.....'] as $blank) {
            $params = array_fill_keys(Settings::secret_fields(), $blank);
            $controller->update_settings(new \WP_REST_Request('POST', '', $params));
            foreach (Settings::secret_fields() as $field) {
                $this->assertSame('synthetic-' . $field, $GLOBALS['cqa_test_options']['cqa_' . $field]);
                $this->assertSame('synthetic-' . $field, Settings::get($field));
            }
        }
        $controller->update_settings(new \WP_REST_Request('POST', '', ['monday_api_token' => 'synthetic-replacement']));
        $this->assertSame('synthetic-replacement', Settings::get('monday_api_token'));
        $this->assertSame('synthetic-replacement', $GLOBALS['cqa_test_options']['cqa_monday_api_token']);
    }

    public function test_legacy_settings_inputs_only_render_masked_secret_variables()
    {
        $view = file_get_contents(CQA_PLUGIN_DIR . 'admin/views/settings.php');
        foreach (['google_client_secret', 'gemini_api_key', 'google_developer_key', 'google_maps_api_key'] as $field) {
            $this->assertStringContainsString('$' . $field . " = \$mask_secret(get_option('cqa_" . $field, $view);
            $this->assertMatchesRegularExpression('/name="cqa_' . $field . '"\\s+value="<\\?php echo esc_attr\\(\\$' . $field . '\\); \\?>"/', $view);
        }
    }
}

/** Minimal read-only database fixture; writes/provider access have no implementation. */
class PolicyDatabase
{
    public $prefix = 'wp_';
    public $users = 'wp_users';
    public $queries = [];
    public $approved = false;
    public function prepare($query, ...$args)
    {
        if (count($args) === 1 && is_array($args[0])) { $args = $args[0]; }
        foreach ($args as $arg) {
            $query = preg_replace('/%[ds]/', is_int($arg) ? (string) $arg : "'" . addslashes((string) $arg) . "'", $query, 1);
        }
        return $query;
    }
    public function get_row($query, $format = null)
    {
        preg_match('/(?:WHERE id|report_id) = (\d+)/', $query, $m);
        $id = (int) ($m[1] ?? 1);
        if ($id < 1) { return null; }
        if (strpos($query, 'cqa_report_snapshots') !== false) {
            $other = strpos($query, 'version_number = 8') !== false;
            return ['id' => 1, 'report_id' => 1, 'version_number' => 7, 'user_id' => 10, 'created_at' => '', 'change_summary' => '',
                'snapshot_data' => json_encode(['report' => ['school_id' => $other ? 2 : 1, 'status' => 'approved']])];
        }
        if (strpos($query, 'cqa_schools') !== false) {
            return ['id' => $id, 'name' => 'Synthetic school', 'location' => '', 'region' => $id === 1 ? 'North' : 'South',
                'status' => 'active', 'acquired_date' => null, 'drive_folder_id' => 'synthetic-folder', 'classroom_config' => '{}', 'created_at' => '', 'updated_at' => ''];
        }
        if (strpos($query, 'cqa_photos') !== false) {
            return ['id' => $id, 'report_id' => $id, 'section_key' => '', 'drive_file_id' => 'synthetic-file', 'filename' => 'synthetic.jpg', 'caption' => '', 'has_markup' => false, 'sort_order' => 0, 'created_at' => ''];
        }
        return ['id' => $id, 'school_id' => $id === 2 ? 2 : 1, 'user_id' => $id === 3 ? 20 : 10, 'report_type' => 'tier1',
            'inspection_date' => '2026-09-01', 'previous_report_id' => null, 'overall_rating' => 'pending', 'closing_notes' => '',
            'status' => $this->approved ? 'approved' : 'draft', 'version_id' => 10, 'created_at' => '', 'updated_at' => ''];
    }
    public function get_results($query, $format = null) { $this->queries[] = $query; return []; }
    public function get_var($query) { $this->queries[] = $query; return 0; }
}

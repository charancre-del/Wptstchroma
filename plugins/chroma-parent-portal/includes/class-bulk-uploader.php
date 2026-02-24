<?php
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Portal_Bulk_Uploader
{
    private const JOB_PREFIX = 'chroma_portal_bulk_job_';
    private const AJAX_NONCE = 'chroma_portal_bulk_uploader_nonce';
    private const BATCH_SIZE = 20;

    private $allowed_types = [
        'cp_lesson_plan',
        'cp_home_activity',
        'cp_resource',
        'cp_form',
        'cp_meal_plan',
        'cp_announcement',
        'cp_event',
    ];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu'], 30);
        add_action('admin_post_chroma_portal_bulk_prepare', [$this, 'handle_prepare']);
        add_action('wp_ajax_chroma_portal_bulk_run', [$this, 'ajax_run']);
        add_action('wp_ajax_chroma_portal_bulk_rollback', [$this, 'ajax_rollback']);
        add_action('wp_ajax_chroma_portal_bulk_assign_files', [$this, 'ajax_assign_files']);
    }

    public function register_menu()
    {
        add_submenu_page(
            'edit.php?post_type=cp_family',
            'Bulk Upload (CSV)',
            'Bulk Upload (CSV)',
            'manage_options',
            'chroma-portal-bulk-upload',
            [$this, 'render_page']
        );
        add_submenu_page(
            'edit.php?post_type=cp_lesson_plan',
            'Bulk Upload',
            'Bulk Upload',
            'manage_options',
            'chroma-portal-bulk-upload',
            [$this, 'render_page']
        );
        add_submenu_page(
            'edit.php?post_type=cp_home_activity',
            'Bulk Upload',
            'Bulk Upload',
            'manage_options',
            'chroma-portal-bulk-upload',
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $job_id = isset($_GET['job']) ? sanitize_key(wp_unslash($_GET['job'])) : '';
        $job = $job_id ? $this->get_job($job_id) : null;
        $notice = isset($_GET['bulk_notice']) ? sanitize_text_field(wp_unslash($_GET['bulk_notice'])) : '';
        $scope = $this->get_scope_context();
        if ($job && !empty($job['scope'])) {
            $scope = sanitize_key((string) $job['scope']);
        }
        $allowed_for_scope = $this->get_allowed_types_for_scope($scope);
        $default_post_type = '';
        if ('cp_lesson_plan' === $scope) {
            $default_post_type = 'cp_lesson_plan';
        } elseif ('cp_home_activity' === $scope) {
            $default_post_type = 'cp_home_activity';
        }
        ?>
        <div class="wrap">
            <h1>Parent Portal Bulk Upload</h1>
            <p>Admin-only uploader for Parent Portal content. Upload a CSV and either a ZIP or multiple PDFs, then assign PDFs to rows.</p>
            <p><strong>Scope:</strong> <?php echo esc_html($this->get_scope_label($scope)); ?> | <strong>Allowed post types:</strong> <?php echo esc_html(implode(', ', $allowed_for_scope)); ?></p>

            <?php if ($notice): ?>
                <div class="notice notice-info is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
            <?php endif; ?>

            <div class="card" style="max-width:1100px;">
                <h2>Upload Dataset</h2>
                <p><strong>CSV headers:</strong> <code>post_type,title,file,year,month,quarter,category,school,classroom,content,priority,event_date,status</code></p>
                <p>Tag fields can be left blank in CSV and set directly in UI before import.</p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('chroma_portal_bulk_prepare', 'chroma_portal_bulk_prepare_nonce'); ?>
                    <input type="hidden" name="action" value="chroma_portal_bulk_prepare" />
                    <input type="hidden" name="bulk_scope" value="<?php echo esc_attr($scope); ?>" />
                    <input type="hidden" name="default_post_type" value="<?php echo esc_attr($default_post_type); ?>" />
                    <table class="form-table" role="presentation">
                        <tbody>
                        <tr>
                            <th><label for="chroma_bulk_csv">CSV File</label></th>
                            <td><input id="chroma_bulk_csv" type="file" name="chroma_bulk_csv" accept=".csv,text/csv" required></td>
                        </tr>
                        <tr>
                            <th><label for="chroma_bulk_zip">ZIP File (Optional)</label></th>
                            <td><input id="chroma_bulk_zip" type="file" name="chroma_bulk_zip" accept=".zip,application/zip"></td>
                        </tr>
                        <tr>
                            <th><label for="chroma_bulk_pdfs">Or Upload Multiple PDFs</label></th>
                            <td><input id="chroma_bulk_pdfs" type="file" name="chroma_bulk_pdfs[]" accept=".pdf,application/pdf" multiple></td>
                        </tr>
                        <tr>
                            <th>Update Existing</th>
                            <td><label><input type="checkbox" name="update_existing" value="1"> Update by matching <code>post_type + title</code> instead of skipping duplicates.</label></td>
                        </tr>
                        </tbody>
                    </table>
                    <p><button class="button button-primary">Upload & Validate</button></p>
                </form>
            </div>

            <?php if ($job && !empty($job['rows'])): ?>
                <?php
                $summary = isset($job['summary']) ? $job['summary'] : $this->empty_summary();
                $file_options = array_keys((array) $job['file_index']);
                sort($file_options);
                $year_options = $this->get_term_names('portal_year');
                $month_options = $this->get_term_names('portal_month');
                $quarter_options = $this->get_term_names('portal_quarter');
                $category_options = $this->get_term_names('portal_category');
                $school_options = $this->get_term_names('portal_school');
                $classroom_options = $this->get_term_names('portal_classroom');
                ?>
                <div class="card" style="max-width:1100px; margin-top:20px;">
                    <h2>Prepared Job: <?php echo esc_html($job['id']); ?></h2>
                    <p>
                        Rows: <strong><?php echo esc_html((string) count($job['rows'])); ?></strong> |
                        Indexed PDFs: <strong><?php echo esc_html((string) count($job['file_index'])); ?></strong> |
                        Update Existing: <strong><?php echo !empty($job['update_existing']) ? 'Yes' : 'No'; ?></strong>
                    </p>
                    <p>
                        <button type="button" class="button" id="chroma-bulk-save-assignments">Save Tags & Assignments</button>
                        <button type="button" class="button" id="chroma-bulk-dry-run">Run Dry Run</button>
                        <button type="button" class="button button-primary" id="chroma-bulk-import">Execute Import</button>
                        <?php if (!empty($job['created_posts']) || !empty($job['updated_snapshots'])): ?>
                            <button type="button" class="button button-secondary" id="chroma-bulk-rollback">Rollback</button>
                        <?php endif; ?>
                        <span class="spinner" id="chroma-bulk-spinner"></span>
                    </p>
                    <div id="chroma-bulk-progress" style="padding:10px; border:1px solid #dcdcde; background:#f6f7f7;">
                        Ready.
                    </div>
                    <h3>Current Summary</h3>
                    <p>Created: <?php echo esc_html((string) $summary['created']); ?> | Updated: <?php echo esc_html((string) $summary['updated']); ?> | Duplicate: <?php echo esc_html((string) $summary['duplicate']); ?> | Failed: <?php echo esc_html((string) $summary['failed']); ?></p>
                    <h3>Rows & PDF Assignment</h3>
                    <?php if (empty($file_options)): ?>
                        <p><em>No PDFs indexed yet. Upload ZIP or multiple PDFs to assign documents.</em></p>
                    <?php endif; ?>
                    <datalist id="chroma-year-options"><?php foreach ($year_options as $opt): ?><option value="<?php echo esc_attr($opt); ?>"><?php endforeach; ?></datalist>
                    <datalist id="chroma-month-options"><?php foreach ($month_options as $opt): ?><option value="<?php echo esc_attr($opt); ?>"><?php endforeach; ?></datalist>
                    <datalist id="chroma-quarter-options"><?php foreach ($quarter_options as $opt): ?><option value="<?php echo esc_attr($opt); ?>"><?php endforeach; ?></datalist>
                    <datalist id="chroma-category-options"><?php foreach ($category_options as $opt): ?><option value="<?php echo esc_attr($opt); ?>"><?php endforeach; ?></datalist>
                    <datalist id="chroma-school-options"><?php foreach ($school_options as $opt): ?><option value="<?php echo esc_attr($opt); ?>"><?php endforeach; ?></datalist>
                    <datalist id="chroma-classroom-options"><?php foreach ($classroom_options as $opt): ?><option value="<?php echo esc_attr($opt); ?>"><?php endforeach; ?></datalist>
                    <div style="max-height:350px; overflow:auto;">
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Assign PDF</th>
                                    <th>Year</th>
                                    <th>Month</th>
                                    <th>Quarter</th>
                                    <th>Category</th>
                                    <th>School(s)</th>
                                    <th>Classroom(s)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($job['rows'] as $row_idx => $row): ?>
                                <tr>
                                    <td><?php echo esc_html((string) $row['_row']); ?></td>
                                    <td><?php echo esc_html($row['post_type']); ?></td>
                                    <td><?php echo esc_html($row['title']); ?></td>
                                    <td>
                                        <select class="chroma-file-assign" data-row-index="<?php echo esc_attr((string) $row_idx); ?>">
                                            <option value="">-- no file --</option>
                                            <?php foreach ($file_options as $opt): ?>
                                                <option value="<?php echo esc_attr($opt); ?>" <?php selected(strtolower((string) $row['file']), strtolower((string) $opt)); ?>>
                                                    <?php echo esc_html($opt); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" class="chroma-row-field" data-row-index="<?php echo esc_attr((string) $row_idx); ?>" data-field="year" list="chroma-year-options" value="<?php echo esc_attr((string) $row['year']); ?>" style="min-width:120px;"></td>
                                    <td><input type="text" class="chroma-row-field" data-row-index="<?php echo esc_attr((string) $row_idx); ?>" data-field="month" list="chroma-month-options" value="<?php echo esc_attr((string) $row['month']); ?>" style="min-width:110px;"></td>
                                    <td><input type="text" class="chroma-row-field" data-row-index="<?php echo esc_attr((string) $row_idx); ?>" data-field="quarter" list="chroma-quarter-options" value="<?php echo esc_attr((string) $row['quarter']); ?>" style="min-width:110px;"></td>
                                    <td><input type="text" class="chroma-row-field" data-row-index="<?php echo esc_attr((string) $row_idx); ?>" data-field="category" list="chroma-category-options" value="<?php echo esc_attr((string) $row['category']); ?>" style="min-width:130px;"></td>
                                    <td><input type="text" class="chroma-row-field" data-row-index="<?php echo esc_attr((string) $row_idx); ?>" data-field="school" list="chroma-school-options" value="<?php echo esc_attr((string) $row['school']); ?>" style="min-width:150px;"></td>
                                    <td><input type="text" class="chroma-row-field" data-row-index="<?php echo esc_attr((string) $row_idx); ?>" data-field="classroom" list="chroma-classroom-options" value="<?php echo esc_attr((string) $row['classroom']); ?>" style="min-width:150px;"></td>
                                    <td><?php echo esc_html($row['validation_error'] ? $row['validation_error'] : 'OK'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($job['run_log'])): ?>
                        <h3>Run Log</h3>
                        <div style="max-height:180px; overflow:auto; border:1px solid #dcdcde; background:#f6f7f7; padding:8px;">
                            <?php foreach ($job['run_log'] as $line): ?>
                                <div><?php echo esc_html($line); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <script>
                    jQuery(function($){
                        const nonce = <?php echo wp_json_encode(wp_create_nonce(self::AJAX_NONCE)); ?>;
                        const jobId = <?php echo wp_json_encode($job['id']); ?>;

                        function collectAssignments() {
                            const assignments = {};
                            $('.chroma-file-assign').each(function(){
                                const rowIndex = $(this).data('row-index');
                                assignments[rowIndex] = $(this).val() || '';
                            });
                            return assignments;
                        }

                        function collectRowFields() {
                            const fields = {};
                            $('.chroma-row-field').each(function(){
                                const rowIndex = $(this).data('row-index');
                                const field = $(this).data('field');
                                if (typeof fields[rowIndex] === 'undefined') {
                                    fields[rowIndex] = {};
                                }
                                fields[rowIndex][field] = $(this).val() || '';
                            });
                            return fields;
                        }

                        function saveAssignments(onDone) {
                            $('#chroma-bulk-spinner').addClass('is-active');
                            $.post(ajaxurl, {
                                action: 'chroma_portal_bulk_assign_files',
                                nonce: nonce,
                                job_id: jobId,
                                assignments: JSON.stringify(collectAssignments()),
                                row_fields: JSON.stringify(collectRowFields())
                            }).done(function(res){
                                if (!res.success) {
                                    $('#chroma-bulk-progress').text((res.data && res.data.message) ? res.data.message : 'Assignment save failed.');
                                    return;
                                }
                                $('#chroma-bulk-progress').text(res.data.message || 'Assignments saved.');
                                if (typeof onDone === 'function') {
                                    onDone();
                                }
                            }).fail(function(){
                                $('#chroma-bulk-progress').text('Assignment save request failed.');
                            }).always(function(){
                                $('#chroma-bulk-spinner').removeClass('is-active');
                            });
                        }

                        function run(dryRun) {
                            $('#chroma-bulk-spinner').addClass('is-active');
                            $('#chroma-bulk-save-assignments, #chroma-bulk-dry-run, #chroma-bulk-import, #chroma-bulk-rollback').prop('disabled', true);
                            $('#chroma-bulk-progress').text(dryRun ? 'Running dry run...' : 'Running import...');
                            function tick(reset) {
                                $.post(ajaxurl, {
                                    action: 'chroma_portal_bulk_run',
                                    nonce: nonce,
                                    job_id: jobId,
                                    dry_run: dryRun ? 1 : 0,
                                    reset: reset ? 1 : 0
                                }).done(function(res){
                                    if (!res.success) {
                                        $('#chroma-bulk-progress').text((res.data && res.data.message) ? res.data.message : 'Run failed.');
                                        finish();
                                        return;
                                    }
                                    const data = res.data || {};
                                    $('#chroma-bulk-progress').text(data.message || 'Processing...');
                                    if (data.complete) {
                                        finish();
                                        window.location.reload();
                                        return;
                                    }
                                    tick(false);
                                }).fail(function(){
                                    $('#chroma-bulk-progress').text('Request failed.');
                                    finish();
                                });
                            }
                            function finish() {
                                $('#chroma-bulk-spinner').removeClass('is-active');
                                $('#chroma-bulk-save-assignments, #chroma-bulk-dry-run, #chroma-bulk-import, #chroma-bulk-rollback').prop('disabled', false);
                            }
                            tick(true);
                        }

                        $('#chroma-bulk-save-assignments').on('click', function(){ saveAssignments(); });
                        $('#chroma-bulk-dry-run').on('click', function(){ saveAssignments(function(){ run(true); }); });
                        $('#chroma-bulk-import').on('click', function(){ if (confirm('Execute bulk import?')) { saveAssignments(function(){ run(false); }); } });
                        $('#chroma-bulk-rollback').on('click', function(){
                            if (!confirm('Rollback writes from this job?')) { return; }
                            $('#chroma-bulk-spinner').addClass('is-active');
                            $.post(ajaxurl, { action: 'chroma_portal_bulk_rollback', nonce: nonce, job_id: jobId })
                                .done(function(){ window.location.reload(); })
                                .fail(function(){ $('#chroma-bulk-progress').text('Rollback failed.'); })
                                .always(function(){ $('#chroma-bulk-spinner').removeClass('is-active'); });
                        });
                    });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }

    public function handle_prepare()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        check_admin_referer('chroma_portal_bulk_prepare', 'chroma_portal_bulk_prepare_nonce');

        $scope = isset($_POST['bulk_scope']) ? sanitize_key(wp_unslash($_POST['bulk_scope'])) : 'all';
        $allowed_for_scope = $this->get_allowed_types_for_scope($scope);
        $default_post_type = isset($_POST['default_post_type']) ? sanitize_key(wp_unslash($_POST['default_post_type'])) : '';
        if ($default_post_type && !in_array($default_post_type, $allowed_for_scope, true)) {
            $default_post_type = '';
        }

        if (empty($_FILES['chroma_bulk_csv']['name'])) {
            $this->redirect_with_notice('CSV file is required.');
        }

        $job_id = strtolower(wp_generate_password(12, false, false));
        $csv_upload = $this->upload_file('chroma_bulk_csv', ['csv' => 'text/csv', 'txt' => 'text/plain']);
        if (is_wp_error($csv_upload)) {
            $this->redirect_with_notice($csv_upload->get_error_message());
        }

        $rows = $this->parse_csv($csv_upload['file'], $default_post_type);
        if (empty($rows)) {
            $this->redirect_with_notice('No rows detected in CSV.');
        }

        $file_index = [];
        if (!empty($_FILES['chroma_bulk_zip']['name'])) {
            $zip_upload = $this->upload_file('chroma_bulk_zip', ['zip' => 'application/zip']);
            if (is_wp_error($zip_upload)) {
                $this->redirect_with_notice($zip_upload->get_error_message());
            }
            $dir = wp_upload_dir();
            $extract_dir = trailingslashit($dir['basedir']) . 'portal-bulk/' . $job_id;
            $file_index = $this->build_zip_file_index($zip_upload['file'], $extract_dir);
            if (is_wp_error($file_index)) {
                $this->redirect_with_notice($file_index->get_error_message());
            }
        }

        if (!empty($_FILES['chroma_bulk_pdfs']['name']) && is_array($_FILES['chroma_bulk_pdfs']['name'])) {
            $uploaded_index = $this->upload_multiple_pdfs('chroma_bulk_pdfs');
            if (is_wp_error($uploaded_index)) {
                $this->redirect_with_notice($uploaded_index->get_error_message());
            }
            $file_index = array_merge($file_index, $uploaded_index);
        }

        $job = [
            'id' => $job_id,
            'rows' => $rows,
            'file_index' => $file_index,
            'scope' => $scope,
            'allowed_types' => $allowed_for_scope,
            'update_existing' => !empty($_POST['update_existing']),
            'cursor' => 0,
            'summary' => $this->empty_summary(),
            'run_log' => [],
            'created_posts' => [],
            'updated_snapshots' => [],
            'status' => 'ready',
            'created_by' => get_current_user_id(),
        ];
        $this->save_job($job);
        $this->redirect_with_notice('Upload validated.', $job_id);
    }

    public function ajax_run()
    {
        $this->assert_ajax_permissions();

        $job_id = isset($_POST['job_id']) ? sanitize_key(wp_unslash($_POST['job_id'])) : '';
        $job = $this->get_job($job_id);
        if (!$job) {
            wp_send_json_error(['message' => 'Job not found.']);
        }

        $dry_run = !empty($_POST['dry_run']);
        $reset = !empty($_POST['reset']);
        if ($reset) {
            $job['cursor'] = 0;
            $job['summary'] = $this->empty_summary();
            $job['run_log'] = [];
            if (!$dry_run) {
                $job['created_posts'] = [];
                $job['updated_snapshots'] = [];
            }
        }

        $total = count($job['rows']);
        $processed = 0;
        while ($job['cursor'] < $total && $processed < self::BATCH_SIZE) {
            $row = $job['rows'][$job['cursor']];
            $result = $this->process_row($row, $job, $dry_run);
            $this->increment_summary($job['summary'], $result['status']);
            $job['run_log'][] = sprintf(
                'Row %d [%s] %s',
                isset($row['_row']) ? intval($row['_row']) : ($job['cursor'] + 1),
                strtoupper($result['status']),
                isset($result['message']) ? $result['message'] : 'Processed'
            );
            $job['run_log'] = array_slice($job['run_log'], -250);
            $job['cursor']++;
            $processed++;
        }

        $complete = ($job['cursor'] >= $total);
        $job['status'] = $complete ? ($dry_run ? 'dry-run-complete' : 'complete') : ($dry_run ? 'dry-running' : 'running');
        $this->save_job($job);

        $message = $complete
            ? sprintf(
                'Run complete. created=%d, updated=%d, duplicate=%d, failed=%d',
                intval($job['summary']['created']),
                intval($job['summary']['updated']),
                intval($job['summary']['duplicate']),
                intval($job['summary']['failed'])
            )
            : sprintf('Processed %d/%d rows...', intval($job['cursor']), intval($total));

        wp_send_json_success([
            'complete' => $complete,
            'cursor' => intval($job['cursor']),
            'total' => intval($total),
            'summary' => $job['summary'],
            'message' => $message,
        ]);
    }

    public function ajax_rollback()
    {
        $this->assert_ajax_permissions();

        $job_id = isset($_POST['job_id']) ? sanitize_key(wp_unslash($_POST['job_id'])) : '';
        $job = $this->get_job($job_id);
        if (!$job) {
            wp_send_json_error(['message' => 'Job not found.']);
        }

        $deleted = 0;
        $restored = 0;
        foreach ((array) $job['created_posts'] as $post_id) {
            $post_id = absint($post_id);
            if (!$post_id || !get_post($post_id)) {
                continue;
            }
            $file_id = absint(get_post_meta($post_id, '_cp_pdf_file_id', true));
            if ($file_id) {
                wp_delete_attachment($file_id, true);
            }
            wp_delete_post($post_id, true);
            $deleted++;
        }

        foreach ((array) $job['updated_snapshots'] as $post_id => $snapshot) {
            $post_id = absint($post_id);
            if (!$post_id || empty($snapshot) || !get_post($post_id)) {
                continue;
            }
            wp_update_post([
                'ID' => $post_id,
                'post_title' => isset($snapshot['title']) ? $snapshot['title'] : '',
                'post_content' => isset($snapshot['content']) ? $snapshot['content'] : '',
                'post_status' => isset($snapshot['status']) ? $snapshot['status'] : 'publish',
            ]);

            $meta = isset($snapshot['meta']) ? (array) $snapshot['meta'] : [];
            update_post_meta($post_id, '_cp_pdf_file_id', isset($meta['_cp_pdf_file_id']) ? absint($meta['_cp_pdf_file_id']) : 0);
            update_post_meta($post_id, '_cp_priority', isset($meta['_cp_priority']) ? sanitize_text_field($meta['_cp_priority']) : '');
            update_post_meta($post_id, '_cp_event_date', isset($meta['_cp_event_date']) ? sanitize_text_field($meta['_cp_event_date']) : '');

            $tax = isset($snapshot['tax']) ? (array) $snapshot['tax'] : [];
            foreach ($tax as $taxonomy => $values) {
                if (taxonomy_exists($taxonomy)) {
                    wp_set_object_terms($post_id, array_values(array_filter((array) $values)), $taxonomy, false);
                }
            }
            $restored++;
        }

        $job['status'] = 'rolled-back';
        $job['run_log'][] = sprintf('Rollback complete. deleted=%d restored=%d', $deleted, $restored);
        $job['run_log'] = array_slice($job['run_log'], -250);
        $this->save_job($job);

        wp_send_json_success(['message' => sprintf('Rollback complete. Deleted %d, restored %d.', $deleted, $restored)]);
    }

    public function ajax_assign_files()
    {
        $this->assert_ajax_permissions();

        $job_id = isset($_POST['job_id']) ? sanitize_key(wp_unslash($_POST['job_id'])) : '';
        $job = $this->get_job($job_id);
        if (!$job) {
            wp_send_json_error(['message' => 'Job not found.']);
        }

        $payload = isset($_POST['assignments']) ? wp_unslash($_POST['assignments']) : '';
        $assignments = json_decode($payload, true);
        if (!is_array($assignments)) {
            wp_send_json_error(['message' => 'Invalid assignments payload.']);
        }

        $valid_files = array_keys((array) $job['file_index']);
        $valid_lookup = array_fill_keys(array_map('strtolower', $valid_files), true);

        foreach ($assignments as $row_idx => $file_name) {
            $idx = absint($row_idx);
            if (!isset($job['rows'][$idx])) {
                continue;
            }
            $file_name = strtolower(sanitize_file_name((string) $file_name));
            if ('' === $file_name || isset($valid_lookup[$file_name])) {
                $job['rows'][$idx]['file'] = $file_name;
            }
        }

        $row_fields_payload = isset($_POST['row_fields']) ? wp_unslash($_POST['row_fields']) : '';
        $row_fields = json_decode($row_fields_payload, true);
        if (is_array($row_fields)) {
            foreach ($row_fields as $row_idx => $fields) {
                $idx = absint($row_idx);
                if (!isset($job['rows'][$idx]) || !is_array($fields)) {
                    continue;
                }
                $job['rows'][$idx]['year'] = sanitize_text_field(isset($fields['year']) ? $fields['year'] : $job['rows'][$idx]['year']);
                $job['rows'][$idx]['month'] = sanitize_text_field(isset($fields['month']) ? $fields['month'] : $job['rows'][$idx]['month']);
                $job['rows'][$idx]['quarter'] = sanitize_text_field(isset($fields['quarter']) ? $fields['quarter'] : $job['rows'][$idx]['quarter']);
                $job['rows'][$idx]['category'] = sanitize_text_field(isset($fields['category']) ? $fields['category'] : $job['rows'][$idx]['category']);
                $job['rows'][$idx]['school'] = sanitize_text_field(isset($fields['school']) ? $fields['school'] : $job['rows'][$idx]['school']);
                $job['rows'][$idx]['classroom'] = sanitize_text_field(isset($fields['classroom']) ? $fields['classroom'] : $job['rows'][$idx]['classroom']);
            }
        }

        $this->save_job($job);
        wp_send_json_success(['message' => 'Assignments and tags saved.']);
    }

    private function process_row($row, &$job, $dry_run)
    {
        $post_type = sanitize_key($row['post_type']);
        $title = sanitize_text_field($row['title']);

        $allowed_types = isset($job['allowed_types']) && is_array($job['allowed_types'])
            ? array_values($job['allowed_types'])
            : $this->allowed_types;

        if (!$post_type || !in_array($post_type, $allowed_types, true)) {
            return ['status' => 'failed', 'message' => 'Invalid post type'];
        }
        if (!$title) {
            return ['status' => 'failed', 'message' => 'Missing title'];
        }
        if (!empty($row['validation_error'])) {
            return ['status' => 'failed', 'message' => $row['validation_error']];
        }

        $existing = get_posts([
            'post_type' => $post_type,
            'title' => $title,
            'post_status' => 'any',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'suppress_filters' => true,
            'no_found_rows' => true,
        ]);
        $existing_id = !empty($existing) ? absint($existing[0]) : 0;
        $mode = $existing_id ? (!empty($job['update_existing']) ? 'update' : 'duplicate') : 'create';

        if ('duplicate' === $mode) {
            return ['status' => 'duplicate', 'message' => 'Duplicate skipped'];
        }
        if ($dry_run) {
            return ['status' => ('create' === $mode ? 'created' : 'updated'), 'message' => 'Dry run'];
        }

        if ('create' === $mode) {
            $post_id = wp_insert_post([
                'post_type' => $post_type,
                'post_title' => $title,
                'post_content' => wp_kses_post($row['content']),
                'post_status' => $this->normalize_status($row['status']),
            ], true);
            if (is_wp_error($post_id)) {
                return ['status' => 'failed', 'message' => $post_id->get_error_message()];
            }
            $job['created_posts'][] = absint($post_id);
        } else {
            $post_id = $existing_id;
            if (empty($job['updated_snapshots'][$post_id])) {
                $job['updated_snapshots'][$post_id] = $this->snapshot_post($post_id);
            }
            $updated = wp_update_post([
                'ID' => $post_id,
                'post_title' => $title,
                'post_content' => wp_kses_post($row['content']),
                'post_status' => $this->normalize_status($row['status']),
            ], true);
            if (is_wp_error($updated)) {
                return ['status' => 'failed', 'message' => $updated->get_error_message()];
            }
        }

        $attach_id = $this->attach_pdf_from_row($row, $job, $post_id);
        if (is_wp_error($attach_id)) {
            return ['status' => 'failed', 'message' => $attach_id->get_error_message()];
        }
        if ($attach_id > 0) {
            update_post_meta($post_id, '_cp_pdf_file_id', $attach_id);
        }

        $this->apply_terms_and_meta($post_id, $post_type, $row);
        return ['status' => ('create' === $mode ? 'created' : 'updated'), 'message' => ucfirst($mode) . 'd'];
    }

    private function apply_terms_and_meta($post_id, $post_type, $row)
    {
        if (!empty($row['year'])) {
            $year_term = $this->match_year($row['year']);
            wp_set_object_terms($post_id, $year_term ?: sanitize_text_field($row['year']), 'portal_year', false);
        }

        $school = $this->split_list($row['school']);
        if (!empty($school)) {
            wp_set_object_terms($post_id, $school, 'portal_school', false);
        }

        if (in_array($post_type, ['cp_lesson_plan', 'cp_home_activity'], true)) {
            $classroom = $this->split_list($row['classroom']);
            if (!empty($classroom)) {
                wp_set_object_terms($post_id, $classroom, 'portal_classroom', false);
            }
        }

        $month = sanitize_text_field($row['month']);
        $quarter = sanitize_text_field(!empty($row['quarter']) ? $row['quarter'] : $row['month']);
        $category = sanitize_text_field(!empty($row['category']) ? $row['category'] : $row['month']);

        if (in_array($post_type, ['cp_lesson_plan', 'cp_home_activity', 'cp_announcement', 'cp_event'], true) && !empty($month)) {
            wp_set_object_terms($post_id, $month, 'portal_month', false);
        } elseif ('cp_meal_plan' === $post_type && !empty($quarter)) {
            wp_set_object_terms($post_id, $quarter, 'portal_quarter', false);
        } elseif (in_array($post_type, ['cp_resource', 'cp_form'], true) && !empty($category)) {
            wp_set_object_terms($post_id, $category, 'portal_category', false);
        }

        if ('cp_announcement' === $post_type) {
            $priority = sanitize_key($row['priority']);
            update_post_meta($post_id, '_cp_priority', in_array($priority, ['normal', 'high'], true) ? $priority : 'normal');
        }

        if ('cp_event' === $post_type && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $row['event_date'])) {
            update_post_meta($post_id, '_cp_event_date', sanitize_text_field($row['event_date']));
        }
    }

    private function attach_pdf_from_row($row, $job, $post_id)
    {
        $file = isset($row['file']) ? trim((string) $row['file']) : '';
        if ('' === $file) {
            return 0;
        }
        $key = strtolower(wp_basename($file));
        if (empty($job['file_index'][$key])) {
            return new WP_Error('missing_file', sprintf('File "%s" is not assigned or uploaded.', $file));
        }
        $path = $job['file_index'][$key];
        if (!file_exists($path)) {
            return new WP_Error('missing_file', sprintf('File "%s" missing on disk.', $file));
        }
        return $this->sideload_pdf($path, $post_id);
    }

    private function sideload_pdf($abs_path, $post_id)
    {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $tmp_copy = wp_tempnam(wp_basename($abs_path));
        if (!$tmp_copy || !copy($abs_path, $tmp_copy)) {
            return new WP_Error('copy_failed', 'Unable to stage PDF for import.');
        }
        $file = [
            'name' => wp_basename($abs_path),
            'tmp_name' => $tmp_copy,
        ];
        $attach_id = media_handle_sideload($file, $post_id);
        if (is_wp_error($attach_id)) {
            return $attach_id;
        }
        return absint($attach_id);
    }

    private function parse_csv($path, $default_post_type = '')
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if (!$handle) {
            return [];
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return [];
        }
        $header = array_map([$this, 'normalize_header'], $header);
        $line = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            $raw = [];
            foreach ($header as $idx => $key) {
                if ($key) {
                    $raw[$key] = isset($data[$idx]) ? trim((string) $data[$idx]) : '';
                }
            }

            $parsed_type = $this->map_post_type($this->first_non_empty($raw, ['post_type', 'type']));
            if (!$parsed_type && $default_post_type) {
                $parsed_type = $default_post_type;
            }

            $row = [
                '_row' => $line,
                'post_type' => $parsed_type,
                'title' => sanitize_text_field($this->first_non_empty($raw, ['title', 'post_title'])),
                'file' => sanitize_file_name($this->first_non_empty($raw, ['file', 'filename', 'pdf', 'document'])),
                'year' => sanitize_text_field($this->first_non_empty($raw, ['year'])),
                'month' => sanitize_text_field($this->first_non_empty($raw, ['month', 'group'])),
                'quarter' => sanitize_text_field($this->first_non_empty($raw, ['quarter'])),
                'category' => sanitize_text_field($this->first_non_empty($raw, ['category'])),
                'school' => sanitize_text_field($this->first_non_empty($raw, ['school'])),
                'classroom' => sanitize_text_field($this->first_non_empty($raw, ['classroom', 'classrooms'])),
                'content' => wp_kses_post($this->first_non_empty($raw, ['content', 'description'])),
                'priority' => sanitize_text_field($this->first_non_empty($raw, ['priority'])),
                'event_date' => sanitize_text_field($this->first_non_empty($raw, ['event_date', 'date'])),
                'status' => sanitize_key($this->first_non_empty($raw, ['status'])),
                'validation_error' => '',
            ];

            if (!$row['title'] && $row['file']) {
                $row['title'] = ucwords(str_replace(['-', '_'], ' ', pathinfo($row['file'], PATHINFO_FILENAME)));
            }

            if (!$row['post_type']) {
                $row['validation_error'] = 'Invalid or missing post_type';
            } elseif (!$row['title']) {
                $row['validation_error'] = 'Missing title';
            }
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    private function build_zip_file_index($zip_path, $extract_dir)
    {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('zip_missing', 'ZipArchive is not available on this host.');
        }
        wp_mkdir_p($extract_dir);
        $zip = new ZipArchive();
        if (true !== $zip->open($zip_path)) {
            return new WP_Error('zip_invalid', 'Could not open ZIP file.');
        }

        $index = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!$name) {
                continue;
            }
            $name = str_replace('\\', '/', $name);
            if ('/' === substr($name, -1)) {
                continue;
            }
            if (false !== strpos($name, '../') || 0 === strpos($name, '/')) {
                continue;
            }

            $base = sanitize_file_name(wp_basename($name));
            if (!$base || 'pdf' !== strtolower(pathinfo($base, PATHINFO_EXTENSION))) {
                continue;
            }

            $stream = $zip->getStream($name);
            if (!$stream) {
                continue;
            }
            $target = trailingslashit($extract_dir) . wp_unique_filename($extract_dir, $base);
            $dest = fopen($target, 'w');
            if (!$dest) {
                fclose($stream);
                continue;
            }
            while (!feof($stream)) {
                fwrite($dest, fread($stream, 8192));
            }
            fclose($dest);
            fclose($stream);
            $index[strtolower($base)] = $target;
        }
        $zip->close();
        return $index;
    }

    private function get_term_names($taxonomy)
    {
        if (!taxonomy_exists($taxonomy)) {
            return [];
        }
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }
        return array_values(wp_list_pluck($terms, 'name'));
    }

    private function normalize_status($status)
    {
        $status = sanitize_key($status);
        return in_array($status, ['publish', 'draft', 'pending'], true) ? $status : 'publish';
    }

    private function map_post_type($value)
    {
        $value = sanitize_key($value);
        if (in_array($value, $this->allowed_types, true)) {
            return $value;
        }
        $aliases = [
            'lesson' => 'cp_lesson_plan',
            'lesson_plan' => 'cp_lesson_plan',
            'home_activity' => 'cp_home_activity',
            'activity' => 'cp_home_activity',
            'resource' => 'cp_resource',
            'form' => 'cp_form',
            'meal' => 'cp_meal_plan',
            'meal_plan' => 'cp_meal_plan',
            'announcement' => 'cp_announcement',
            'event' => 'cp_event',
        ];
        return isset($aliases[$value]) ? $aliases[$value] : '';
    }

    private function first_non_empty($arr, $keys)
    {
        foreach ($keys as $key) {
            if (!empty($arr[$key])) {
                return (string) $arr[$key];
            }
        }
        return '';
    }

    private function normalize_header($header)
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
        $header = strtolower(trim($header));
        return preg_replace('/[^a-z0-9_]/', '_', $header);
    }

    private function split_list($value)
    {
        $parts = preg_split('/[,|]/', (string) $value);
        $parts = array_map('trim', (array) $parts);
        $parts = array_map('sanitize_text_field', $parts);
        return array_values(array_filter(array_unique($parts)));
    }

    private function match_year($value)
    {
        if (!preg_match('/(\d{4})/', (string) $value, $m)) {
            return '';
        }
        $needle = $m[1];
        $terms = get_terms(['taxonomy' => 'portal_year', 'hide_empty' => false]);
        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }
        foreach ($terms as $term) {
            if (false !== strpos($term->name, $needle)) {
                return $term->name;
            }
        }
        return '';
    }

    private function snapshot_post($post_id)
    {
        $post = get_post($post_id);
        if (!$post) {
            return [];
        }
        $tax_names = ['portal_year', 'portal_month', 'portal_quarter', 'portal_category', 'portal_school', 'portal_classroom'];
        $tax_data = [];
        foreach ($tax_names as $taxonomy) {
            $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'names']);
            $tax_data[$taxonomy] = is_wp_error($terms) ? [] : array_values($terms);
        }
        return [
            'title' => $post->post_title,
            'content' => $post->post_content,
            'status' => $post->post_status,
            'meta' => [
                '_cp_pdf_file_id' => get_post_meta($post_id, '_cp_pdf_file_id', true),
                '_cp_priority' => get_post_meta($post_id, '_cp_priority', true),
                '_cp_event_date' => get_post_meta($post_id, '_cp_event_date', true),
            ],
            'tax' => $tax_data,
        ];
    }

    private function upload_file($field, $mimes)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $file = wp_handle_upload($_FILES[$field], ['test_form' => false, 'mimes' => $mimes]);
        if (isset($file['error'])) {
            return new WP_Error('upload_error', sanitize_text_field($file['error']));
        }
        return $file;
    }

    private function upload_multiple_pdfs($field)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $files = isset($_FILES[$field]) ? $_FILES[$field] : null;
        if (!$files || !is_array($files['name'])) {
            return [];
        }

        $index = [];
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if (empty($files['name'][$i])) {
                continue;
            }

            $single = [
                'name' => $files['name'][$i],
                'type' => isset($files['type'][$i]) ? $files['type'][$i] : '',
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => isset($files['size'][$i]) ? $files['size'][$i] : 0,
            ];

            if (UPLOAD_ERR_OK !== (int) $single['error']) {
                return new WP_Error('upload_error', sprintf('Failed to upload file: %s', sanitize_text_field($single['name'])));
            }

            $uploaded = wp_handle_upload($single, [
                'test_form' => false,
                'mimes' => ['pdf' => 'application/pdf'],
            ]);
            if (isset($uploaded['error'])) {
                return new WP_Error('upload_error', sanitize_text_field($uploaded['error']));
            }

            $key = strtolower(sanitize_file_name($single['name']));
            if ($key) {
                $index[$key] = $uploaded['file'];
            }
        }

        return $index;
    }

    private function empty_summary()
    {
        return [
            'created' => 0,
            'updated' => 0,
            'duplicate' => 0,
            'failed' => 0,
        ];
    }

    private function increment_summary(&$summary, $status)
    {
        if (!isset($summary[$status])) {
            $summary[$status] = 0;
        }
        $summary[$status]++;
    }

    private function save_job($job)
    {
        if (!empty($job['id'])) {
            set_transient(self::JOB_PREFIX . $job['id'], $job, DAY_IN_SECONDS);
        }
    }

    private function get_job($job_id)
    {
        if (!$job_id) {
            return null;
        }
        $job = get_transient(self::JOB_PREFIX . $job_id);
        return is_array($job) ? $job : null;
    }

    private function assert_ajax_permissions()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, self::AJAX_NONCE)) {
            wp_send_json_error(['message' => 'Invalid nonce.']);
        }
    }

    private function redirect_with_notice($message, $job_id = '')
    {
        $scope = $this->get_scope_context();
        $post_type = 'cp_family';
        if ('cp_lesson_plan' === $scope) {
            $post_type = 'cp_lesson_plan';
        } elseif ('cp_home_activity' === $scope) {
            $post_type = 'cp_home_activity';
        }

        $args = [
            'post_type' => $post_type,
            'page' => 'chroma-portal-bulk-upload',
            'bulk_notice' => $message,
        ];
        if ($job_id) {
            $args['job'] = $job_id;
        }
        wp_safe_redirect(add_query_arg($args, admin_url('edit.php')));
        exit;
    }

    private function get_scope_context()
    {
        $post_type = isset($_REQUEST['post_type']) ? sanitize_key(wp_unslash($_REQUEST['post_type'])) : '';
        if ('cp_lesson_plan' === $post_type) {
            return 'cp_lesson_plan';
        }
        if ('cp_home_activity' === $post_type) {
            return 'cp_home_activity';
        }
        return 'all';
    }

    private function get_allowed_types_for_scope($scope)
    {
        if ('cp_lesson_plan' === $scope) {
            return ['cp_lesson_plan', 'cp_home_activity'];
        }
        if ('cp_home_activity' === $scope) {
            return ['cp_home_activity', 'cp_lesson_plan'];
        }
        return $this->allowed_types;
    }

    private function get_scope_label($scope)
    {
        if ('cp_lesson_plan' === $scope) {
            return 'Lessons + Home Activities';
        }
        if ('cp_home_activity' === $scope) {
            return 'Home Activities + Lessons';
        }
        return 'All Parent Portal Content Types';
    }
}

new Chroma_Portal_Bulk_Uploader();

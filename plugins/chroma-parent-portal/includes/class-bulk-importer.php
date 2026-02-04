<?php
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Portal_Bulk_Importer
{
    private $seed_dir;

    public function __construct()
    {
        $upload_dir = wp_upload_dir();
        $this->seed_dir = $upload_dir['basedir'] . '/portal-seed';

        add_action('admin_menu', [$this, 'add_menu_item'], 20);
        add_action('wp_ajax_chroma_portal_run_seed', [$this, 'ajax_run_seed']);
    }

    public function add_menu_item()
    {
        add_submenu_page(
            'edit.php?post_type=cp_family',
            'Bulk Document Importer',
            'Bulk Importer',
            'manage_options',
            'chroma-portal-importer',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page()
    {
        ?>
        <div class="wrap">
            <h1>Bulk Document Importer</h1>
            <p>This tool scans <code>wp-content/uploads/portal-seed/</code> and imports PDFs as records.</p>

            <div class="card" style="max-width: 800px;">
                <h2>Importer Status</h2>
                <?php if (!file_exists($this->seed_dir)): ?>
                    <div class="notice notice-warning inline">
                        <p>Seed directory not found. Please create <code>/wp-content/uploads/portal-seed/</code> and upload your
                            folders.</p>
                    </div>
                <?php else: ?>
                    <p>Directory found: <code><?php echo esc_html($this->seed_dir); ?></code></p>
                    <div id="importer-results"
                        style="background:#f0f0f1; padding:20px; border-radius:8px; margin:20px 0; min-height:100px; max-height:400px; overflow-y:auto;">
                        <p>Click "Run Scanner" to see what is ready to import.</p>
                    </div>

                    <button id="run-scanner" class="button button-primary">Scan Seed Folder</button>
                    <button id="run-importer" class="button button-secondary" disabled>Execute Import</button>
                    <span class="spinner"></span>
                <?php endif; ?>
            </div>

            <script>
                jQuery(document).ready(function ($) {
                    var foundFiles = [];
                    var importQueue = [];
                    var totalToImport = 0;
                    var importedCount = 0;
                    var isImporting = false;

                    $('#run-scanner').on('click', function () {
                        runScan();
                    });

                    $('#run-importer').on('click', function () {
                        if (confirm('Are you sure you want to import ' + foundFiles.length + ' files? This will create records in your database.')) {
                            startImport();
                        }
                    });

                    function runScan() {
                        $('.spinner').addClass('is-active');
                        $('#run-scanner, #run-importer').prop('disabled', true);
                        $('#importer-results').html('<p>Scanning directory structure...</p>');

                        $.post(ajaxurl, {
                            action: 'chroma_portal_run_seed',
                            mode: 'scan'
                        }, function (res) {
                            $('.spinner').removeClass('is-active');
                            $('#run-scanner').prop('disabled', false);

                            if (res.success) {
                                foundFiles = res.data.files || [];
                                renderScanResults(res.data.html);
                                if (foundFiles.length > 0) {
                                    $('#run-importer').prop('disabled', false);
                                }
                            } else {
                                alert('Error: ' + res.data);
                            }
                        });
                    }

                    function renderScanResults(html) {
                        $('#importer-results').html(html);
                    }

                    async function startImport() {
                        if (isImporting) return;
                        isImporting = true;

                        $('#run-scanner, #run-importer').prop('disabled', true);
                        $('.spinner').addClass('is-active');

                        totalToImport = foundFiles.length;
                        importedCount = 0;

                        // Create Progress Bar
                        $('#importer-results').prepend(`
                            <div id="import-progress-container" style="background:#fff; padding:15px; border-radius:8px; margin-bottom:20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                    <span id="progress-status">Starting import...</span>
                                    <span id="progress-percent">0%</span>
                                </div>
                                <div style="height:10px; background:#e0e0e0; border-radius:5px; overflow:hidden;">
                                    <div id="progress-bar-inner" style="width:0%; height:100%; background:#9d8253; transition: width 0.3s ease;"></div>
                                </div>
                            </div>
                        `);

                        // Add "Status" column to existing table
                        $('#importer-results table thead tr').append('<th>Status</th>');
                        $('#importer-results table tbody tr').each(function (idx) {
                            $(this).append('<td class="row-status" id="status-row-' + idx + '" style="font-weight:600; color:#888;">Pending...</td>');
                        });

                        for (var i = 0; i < totalToImport; i++) {
                            const file = foundFiles[i];
                            const rowId = '#status-row-' + i;
                            $(rowId).text('⌛ Importing...');

                            try {
                                const res = await $.post(ajaxurl, {
                                    action: 'chroma_portal_run_seed',
                                    mode: 'import_single',
                                    file: file
                                }).promise();

                                if (res.success) {
                                    if (res.data.status === 'duplicate') {
                                        $(rowId).html('<span style="color:#d97706;">📁 Duplicate</span>');
                                    } else {
                                        $(rowId).html('<span style="color:#059669;">✅ Imported</span>');
                                    }
                                } else {
                                    $(rowId).html('<span style="color:#dc2626;">❌ Failed: ' + (res.data || 'Error') + '</span>');
                                }
                            } catch (e) {
                                $(rowId).html('<span style="color:#dc2626;">❌ Error</span>');
                            }

                            importedCount++;
                            updateProgress();
                        }

                        isImporting = false;
                        $('.spinner').removeClass('is-active');
                        $('#run-scanner').prop('disabled', false);
                        $('#progress-status').text('Import complete!');
                    }

                    function updateProgress() {
                        var percent = Math.round((importedCount / totalToImport) * 100);
                        $('#progress-bar-inner').css('width', percent + '%');
                        $('#progress-percent').text(percent + '%');
                        $('#progress-status').text('Processing ' + importedCount + ' of ' + totalToImport + ' documents...');
                    }
                });
            </script>
        </div>
        <?php
    }

    public function ajax_run_seed()
    {
        check_admin_referer(-1, false); // basic check
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $mode = $_POST['mode'] ?? 'scan';
        $files = $this->scan_directory();

        if (empty($files)) {
            wp_send_json_success(['html' => '<p>No PDFs found in the seed directory structure.</p>', 'files' => []]);
        }

        if ($mode === 'import_single') {
            $file = $_POST['file'] ?? null;
            if (!$file)
                wp_send_json_error('No file data provided');
            $result = $this->import_file($file);
            wp_send_json_success($result);
        }

        if ($mode === 'import') {
            $results = $this->process_imports($files);
            wp_send_json_success(['html' => $results['html'], 'files' => []]);
        }

        // Scan Mode Output
        $html = "<h3>Scan Complete: " . count($files) . " items found</h3>";
        $html .= "<table class='widefat'>";
        $html .= "<thead><tr><th>File</th><th>Type</th><th>Year</th><th>Month</th><th>Classroom</th><th>Category</th></tr></thead><tbody>";
        foreach ($files as $f) {
            $html .= "<tr><td>{$f['filename']}</td><td>{$f['type']}</td><td>{$f['year']}</td><td>{$f['month']}</td><td>{$f['classroom']}</td><td>{$f['category']}</td></tr>";
        }
        $html .= "</tbody></table>";

        wp_send_json_success(['html' => $html, 'files' => $files]);
    }

    private function scan_directory()
    {
        $files = [];
        if (!file_exists($this->seed_dir))
            return [];

        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->seed_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iter as $path) {
            if ($path->isDir())
                continue;
            if (strtolower($path->getExtension()) !== 'pdf')
                continue;

            $rel_path = str_replace($this->seed_dir, '', $path->getPathname());
            $rel_path = trim($rel_path, DIRECTORY_SEPARATOR);
            $parts = explode(DIRECTORY_SEPARATOR, $rel_path);

            // Logic 1: /resources/{category}/{filename}.pdf
            if (count($parts) >= 3 && $parts[0] === 'resources') {
                $files[] = [
                    'abs_path' => $path->getPathname(),
                    'filename' => $path->getFilename(),
                    'type' => 'cp_resource',
                    'year' => 'universal',
                    'month' => '',
                    'classroom' => '',
                    'category' => $parts[1]
                ];
            }
            // Logic 2: /lessons/{year}/{month}/{classroom}/{filename}.pdf
            elseif ($parts[0] === 'lessons' && count($parts) >= 4) {
                $files[] = [
                    'abs_path' => $path->getPathname(),
                    'filename' => $path->getFilename(),
                    'type' => 'cp_lesson_plan',
                    'year' => $parts[1],
                    'month' => $parts[2],
                    // If count is 5, classroom is parts[3]. If 4, classroom is empty.
                    'classroom' => (count($parts) >= 5) ? $parts[3] : '',
                    'category' => ''
                ];
            }
            // Logic 3: /home-activities/{year}/{month}/{classroom}/{filename}.pdf
            elseif ($parts[0] === 'home-activities' && count($parts) >= 4) {
                $files[] = [
                    'abs_path' => $path->getPathname(),
                    'filename' => $path->getFilename(),
                    'type' => 'cp_home_activity',
                    'year' => $parts[1],
                    'month' => $parts[2],
                    // If count is 5, classroom is parts[3]. If 4, classroom is empty.
                    'classroom' => (count($parts) >= 5) ? $parts[3] : '',
                    'category' => ''
                ];
            }
        }
        return $files;
    }

    private function process_imports($files)
    {
        $html = "<h3>Import Results</h3><ul>";
        $success = 0;
        $failed = 0;

        foreach ($files as $f) {
            $res = $this->import_file($f);
            if ($res['status'] === 'duplicate') {
                $html .= "<li>📁 Duplicate: <strong>{$res['title']}</strong></li>";
            } elseif ($res['status'] === 'success') {
                $html .= "<li>✅ Imported: <strong>{$res['title']}</strong></li>";
                $success++;
            } else {
                $html .= "<li style='color:red;'>❌ Failed: <strong>{$res['title']}</strong> - {$res['message']}</li>";
                $failed++;
            }
        }

        $html .= "</ul><p><strong>Summary:</strong> $success imported, $failed failed.</p>";
        return ['html' => $html];
    }

    private function import_file($f)
    {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $title = pathinfo($f['filename'], PATHINFO_FILENAME);
        $title = str_replace(['-', '_'], ' ', $title);
        $title = ucwords($title);

        // 1. Check if post already exists
        $existing = get_posts([
            'post_type' => $f['type'],
            'title' => $title,
            'posts_per_page' => 1
        ]);

        if ($existing) {
            return ['status' => 'duplicate', 'title' => $title];
        }

        // 2. Sideload Media
        // We need to COPY the file before sideload because media_handle_sideload might move/delete it
        $tmp_copy = wp_tempnam($f['filename']);
        copy($f['abs_path'], $tmp_copy);

        $file_sideload = [
            'name' => $f['filename'],
            'tmp_name' => $tmp_copy
        ];

        $attach_id = media_handle_sideload($file_sideload, 0);

        if (is_wp_error($attach_id)) {
            return ['status' => 'failed', 'title' => $title, 'message' => $attach_id->get_error_message()];
        }

        // 3. Create Post
        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_type' => $f['type'],
            'post_status' => 'publish'
        ]);

        if (is_wp_error($post_id)) {
            return ['status' => 'failed', 'title' => $title, 'message' => 'Failed to insert post'];
        }

        // 4. Set Meta & Tax
        update_post_meta($post_id, '_cp_pdf_file_id', $attach_id);

        // Year
        if ($f['year'] === 'universal') {
            $years = get_terms(['taxonomy' => 'portal_year', 'hide_empty' => false]);
            if (!is_wp_error($years)) {
                $year_names = wp_list_pluck($years, 'name');
                wp_set_object_terms($post_id, $year_names, 'portal_year');
            }
        } else {
            wp_set_object_terms($post_id, $f['year'], 'portal_year');
        }

        // Month
        if ($f['month']) {
            wp_set_object_terms($post_id, $f['month'], 'portal_month');
        }

        // Classroom
        if ($f['classroom']) {
            wp_set_object_terms($post_id, $f['classroom'], 'portal_classroom');
        }

        // Category
        if ($f['category']) {
            wp_set_object_terms($post_id, $f['category'], 'portal_category');
        }

        return ['status' => 'success', 'title' => $title];
    }
}

new Chroma_Portal_Bulk_Importer();

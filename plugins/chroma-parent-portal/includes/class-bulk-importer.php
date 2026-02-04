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

                    $('#run-scanner').on('click', function () {
                        runSeed('scan');
                    });

                    $('#run-importer').on('click', function () {
                        if (confirm('Are you sure you want to import ' + foundFiles.length + ' files? This will create records in your database.')) {
                            runSeed('import');
                        }
                    });

                    function runSeed(mode) {
                        $('.spinner').addClass('is-active');
                        $('#run-scanner, #run-importer').prop('disabled', true);

                        $.post(ajaxurl, {
                            action: 'chroma_portal_run_seed',
                            mode: mode
                        }, function (res) {
                            $('.spinner').removeClass('is-active');
                            $('#run-scanner').prop('disabled', false);

                            if (res.success) {
                                $('#importer-results').html(res.data.html);
                                foundFiles = res.data.files || [];
                                if (mode === 'scan' && foundFiles.length > 0) {
                                    $('#run-importer').prop('disabled', false);
                                }
                            } else {
                                alert('Error: ' + res.data);
                            }
                        });
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
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $html = "<h3>Import Results</h3><ul>";
        $success = 0;
        $failed = 0;

        foreach ($files as $f) {
            // 1. Check if post already exists with this title and type to avoid dupes
            $title = pathinfo($f['filename'], PATHINFO_FILENAME);
            $title = str_replace(['-', '_'], ' ', $title);
            $title = ucwords($title);

            $existing = get_posts([
                'post_type' => $f['type'],
                'title' => $title,
                'posts_per_page' => 1
            ]);

            if ($existing) {
                $html .= "<li>⚠️ Skipped: <strong>$title</strong> (Already exists)</li>";
                continue;
            }

            // 2. Sideload Media
            $file_array = [
                'name' => $f['filename'],
                'tmp_name' => $f['abs_path']
            ];

            // We need to COPY the file before sideload because media_handle_sideload might move/delete it
            $tmp_copy = wp_tempnam($f['filename']);
            copy($f['abs_path'], $tmp_copy);

            $file_sideload = [
                'name' => $f['filename'],
                'tmp_name' => $tmp_copy
            ];

            $attach_id = media_handle_sideload($file_sideload, 0);

            if (is_wp_error($attach_id)) {
                $html .= "<li style='color:red;'>❌ Failed sideload: {$f['filename']} - " . $attach_id->get_error_message() . "</li>";
                $failed++;
                continue;
            }

            // 3. Create Post
            $post_id = wp_insert_post([
                'post_title' => $title,
                'post_type' => $f['type'],
                'post_status' => 'publish'
            ]);

            if (is_wp_error($post_id)) {
                $html .= "<li style='color:red;'>❌ Failed post: {$f['filename']}</li>";
                $failed++;
                continue;
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

            $html .= "<li>✅ Imported: <strong>$title</strong></li>";
            $success++;
        }

        $html .= "</ul>";
        $html .= "<p><strong>Summary:</strong> $success imported, $failed failed.</p>";

        return ['html' => $html];
    }
}

new Chroma_Portal_Bulk_Importer();

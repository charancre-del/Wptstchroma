<?php
/**
 * Advanced SEO/LLM Dashboard
 * Provides a centralized view of all SEO data
 * Shows manual values vs. fallback values
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chroma_SEO_Dashboard
{
    /**
     * Initialize the dashboard
     */
    public function init()
    {
        add_action('admin_menu', [$this, 'register_menu_page'], 5); // Priority 5 - register parent menu first
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_chroma_fetch_schema_inspector', [$this, 'ajax_fetch_inspector_data']);
        add_action('wp_ajax_chroma_save_schema_inspector', [$this, 'ajax_save_inspector_data']);
        add_action('wp_ajax_chroma_scan_schema_batch', [$this, 'ajax_scan_schema_batch']);
        add_action('wp_ajax_chroma_get_schema_fields', [$this, 'ajax_get_schema_fields']);
        add_action('wp_ajax_chroma_fetch_social_preview', [$this, 'ajax_fetch_social_preview']);
        add_action('wp_ajax_chroma_fetch_llm_data', [$this, 'ajax_fetch_llm_data']);
        add_action('wp_ajax_chroma_save_llm_targeting', [$this, 'ajax_save_llm_targeting']);
        add_action('wp_ajax_chroma_reset_post_schema', [$this, 'ajax_reset_post_schema']);
        add_action('wp_ajax_chroma_scan_schema_batch', [$this, 'ajax_scan_schema_batch']);
        add_action('wp_ajax_chroma_apply_schema_fix', [$this, 'ajax_apply_schema_fix']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('chroma_llm_options', 'chroma_llm_brand_voice');
        register_setting('chroma_llm_options', 'chroma_llm_brand_context');
    }

    /**
     * Register the menu page
     */
    public function register_menu_page()
    {
        add_menu_page(
            'SEO & LLM Data',              // Page title
            'SEO & LLM',                   // Menu title
            'edit_posts',                  // Capability
            'chroma-seo-dashboard',        // Menu slug
            [$this, 'render_page'],        // Callback
            'dashicons-chart-area',        // Icon
            80                             // Position
        );
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook)
    {
        // Check if we are on the correct page
        if (!isset($_GET['page']) || $_GET['page'] !== 'chroma-seo-dashboard') {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-tooltip');

        // Simple inline styles for the dashboard
        wp_add_inline_style('common', '
			.chroma-seo-table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,0.04); }
			.chroma-seo-table th, .chroma-seo-table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e5e5; vertical-align: top; }
			.chroma-seo-table th { background: #f9f9f9; font-weight: 600; border-bottom: 2px solid #ddd; }
			.chroma-seo-table tr:hover { background: #fbfbfb; }
			.chroma-value-manual { color: #2271b1; font-weight: 500; }
			.chroma-value-fallback { color: #646970; font-style: italic; }
			.chroma-badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 11px; margin-right: 4px; }
			.chroma-badge-manual { background: #e6f6e6; color: #006600; border: 1px solid #b3e6b3; }
			.chroma-badge-auto { background: #f0f0f1; color: #646970; border: 1px solid #dcdcde; }
			.chroma-status-icon { font-size: 16px; margin-right: 5px; }
			.chroma-check { color: #00a32a; }
			.chroma-cross { color: #d63638; }
            
            /* Inspector Styles */
            .chroma-inspector-controls { background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-bottom: 20px; display: flex; gap: 20px; align-items: center; }
            .chroma-inspector-table input[type="text"], .chroma-inspector-table textarea { width: 100%; }
            .chroma-inspector-row.modified { background-color: #f0f6fc; }
            
            /* Health Dots */
            .chroma-health-dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; }
            .chroma-health-good { background-color: #00a32a; }
            .chroma-health-ok { background-color: #dba617; }
            .chroma-health-poor { background-color: #d63638; opacity: 0.3; }
		');
    }

    /**
     * Render the dashboard page
     */
    public function render_page()
    {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'locations';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">SEO & LLM Data Dashboard</h1>

            <nav class="nav-tab-wrapper">
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=locations'); ?>"
                    class="nav-tab <?php echo $active_tab === 'locations' ? 'nav-tab-active' : ''; ?>">Locations</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=programs'); ?>"
                    class="nav-tab <?php echo $active_tab === 'programs' ? 'nav-tab-active' : ''; ?>">Programs</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=pages'); ?>"
                    class="nav-tab <?php echo $active_tab === 'pages' ? 'nav-tab-active' : ''; ?>">Pages</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=cities'); ?>"
                    class="nav-tab <?php echo $active_tab === 'cities' ? 'nav-tab-active' : ''; ?>">Cities</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=posts'); ?>"
                    class="nav-tab <?php echo $active_tab === 'posts' ? 'nav-tab-active' : ''; ?>">Blog Posts</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=geo'); ?>"
                    class="nav-tab <?php echo $active_tab === 'geo' ? 'nav-tab-active' : ''; ?>">GEO Settings</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=llm'); ?>"
                    class="nav-tab <?php echo $active_tab === 'llm' ? 'nav-tab-active' : ''; ?>">LLM Settings</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=schema-builder'); ?>"
                    class="nav-tab <?php echo $active_tab === 'schema-builder' ? 'nav-tab-active' : ''; ?>">Schema Builder</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=breadcrumbs'); ?>"
                    class="nav-tab <?php echo $active_tab === 'breadcrumbs' ? 'nav-tab-active' : ''; ?>">Breadcrumbs</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=sitemap'); ?>"
                    class="nav-tab <?php echo $active_tab === 'sitemap' ? 'nav-tab-active' : ''; ?>">Sitemap</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=social'); ?>"
                    class="nav-tab <?php echo $active_tab === 'social' ? 'nav-tab-active' : ''; ?>">Social Preview</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=bulk'); ?>"
                    class="nav-tab <?php echo $active_tab === 'bulk' ? 'nav-tab-active' : ''; ?>">Bulk Builder</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=bulk-validation'); ?>"
                    class="nav-tab <?php echo $active_tab === 'bulk-validation' ? 'nav-tab-active' : ''; ?>">Bulk Validation</a>
                <?php do_action('chroma_seo_dashboard_tabs'); ?>
            </nav>

            <br>

            <?php
            switch ($active_tab) {
                case 'locations':
                    $this->render_overview_tab('location');
                    break;
                case 'programs':
                    $this->render_overview_tab('program');
                    break;
                case 'pages':
                    $this->render_overview_tab('page');
                    break;
                case 'cities':
                    $this->render_overview_tab('city');
                    break;
                case 'posts':
                    $this->render_overview_tab('post');
                    break;
                case 'geo':
                    $this->render_geo_tab();
                    break;
                case 'llm':
                    $this->render_llm_tab();
                    break;
                case 'schema-builder':
                    $this->render_schema_builder_tab();
                    break;
                case 'sitemap':
                    $this->render_sitemap_tab();
                    break;
                case 'breadcrumbs':
                    if (class_exists('Chroma_Breadcrumbs')) {
                        (new Chroma_Breadcrumbs())->render_settings();
                    } else {
                        echo '<p>Breadcrumbs module not loaded.</p>';
                    }
                    break;
                case 'social':
                    $this->render_social_tab();
                    break;
                case 'bulk':
                    $this->render_bulk_ops_tab();
                    break;
                case 'bulk-validation':
                    $this->render_bulk_validation_tab();
                    break;
                default:
                    // Allow other tabs to render via action
                    if (has_action('chroma_seo_dashboard_content')) {
                        do_action('chroma_seo_dashboard_content');
                    } else {
                        $this->render_overview_tab('location');
                    }
                    break;
            }
            ?>
        </div>
        <?php
    }

    /**
     * Render GEO Tab
     */
    private function render_geo_tab()
    {
        ?>
        <div class="chroma-seo-card">
            <h2>🌍 Geo-Optimization Settings</h2>
            <p>Manage your location-based SEO settings.</p>

            <div class="chroma-doc-section" style="margin-top: 20px;">
                <h3>KML File</h3>
                <p>Your KML file is automatically generated and available at:</p>
                <code><a href="<?php echo home_url('/locations.kml'); ?>" target="_blank"><?php echo home_url('/locations.kml'); ?></a></code>
                <p class="description">Submit this URL to Google Earth and other geo-directories.</p>
            </div>

            <div class="chroma-doc-section" style="margin-top: 20px;">
                <h3>Service Area Defaults</h3>
                <p>If a location does not have specific coordinates set, the system will attempt to geocode the address
                    automatically.</p>
                <p>Default Radius: <strong>10 miles</strong></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render LLM Tab
     */
    /**
     * Render LLM Tab
     */
    private function render_llm_tab()
    {
        // Render Global Settings First
        global $chroma_llm_client;
        if (isset($chroma_llm_client) && method_exists($chroma_llm_client, 'render_settings')) {
            $chroma_llm_client->render_settings();
            echo '<hr style="margin: 30px 0; border: 0; border-top: 1px solid #ddd;">';
        }

        // Get all posts for selector
        $locations = get_posts(['post_type' => 'location', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
        $programs = get_posts(['post_type' => 'program', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
        $pages = get_posts(['post_type' => 'page', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
        $posts = get_posts(['post_type' => 'post', 'posts_per_page' => 50, 'orderby' => 'date', 'order' => 'DESC']);

        $selected_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        ?>
        <div class="chroma-llm-controls">
            <label><strong>Select Page to Edit LLM Targeting:</strong></label>
            <select id="chroma-llm-select" style="min-width: 300px;">
                <option value="">-- Select a Page --</option>
                <optgroup label="Locations">
                    <?php foreach ($locations as $loc):
                        if (!$loc || !is_a($loc, 'WP_Post'))
                            continue; ?>
                        <option value="<?php echo $loc->ID; ?>" <?php selected($selected_id, $loc->ID); ?>>
                            <?php echo esc_html($loc->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Programs">
                    <?php foreach ($programs as $prog):
                        if (!$prog || !is_a($prog, 'WP_Post'))
                            continue; ?>
                        <option value="<?php echo $prog->ID; ?>" <?php selected($selected_id, $prog->ID); ?>>
                            <?php echo esc_html($prog->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Pages">
                    <?php foreach ($pages as $pg):
                        if (!$pg || !is_a($pg, 'WP_Post'))
                            continue; ?>
                        <option value="<?php echo $pg->ID; ?>" <?php selected($selected_id, $pg->ID); ?>>
                            <?php echo esc_html($pg->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Blog Posts">
                    <?php foreach ($posts as $pt):
                        if (!$pt || !is_a($pt, 'WP_Post'))
                            continue; ?>
                        <option value="<?php echo $pt->ID; ?>" <?php selected($selected_id, $pt->ID); ?>>
                            <?php echo esc_html($pt->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <span class="spinner" id="chroma-llm-spinner"></span>
        </div>

        <div id="chroma-llm-content">
            <p class="description">Select a page above to edit its LLM targeting data.</p>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                var chroma_nonce = '<?php echo wp_create_nonce('chroma_seo_dashboard_nonce'); ?>';
                var selectedId = '<?php echo $selected_id; ?>';

                if (selectedId && selectedId != '0') {
                    loadLLMData(selectedId);
                }

                $('#chroma-llm-select').on('change', function () {
                    var id = $(this).val();
                    if (id) loadLLMData(id);
                });

                function loadLLMData(id) {
                    $('#chroma-llm-spinner').addClass('is-active');
                    $.post(ajaxurl, {
                        action: 'chroma_fetch_llm_data',
                        nonce: chroma_nonce,
                        post_id: id
                    }, function (response) {
                        $('#chroma-llm-spinner').removeClass('is-active');
                        if (response.success) {
                            $('#chroma-llm-content').html(response.data.html);
                        } else {
                            alert('Error loading data');
                        }
                    });
                }

                // Save Handler
                $(document).on('click', '#chroma-llm-save', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    btn.prop('disabled', true).text('Saving...');

                    var primary_intent = $('#seo_llm_primary_intent').val();
                    var target_queries = [];
                    $('.chroma-llm-query-input').each(function () {
                        var val = $(this).val();
                        if (val) target_queries.push(val);
                    });
                    var key_differentiators = [];
                    $('.chroma-llm-diff-input').each(function () {
                        var val = $(this).val();
                        if (val) key_differentiators.push(val);
                    });

                    $.post(ajaxurl, {
                        action: 'chroma_save_llm_targeting',
                        nonce: chroma_nonce,
                        post_id: $('#chroma-llm-post-id').val(),
                        primary_intent: primary_intent,
                        target_queries: target_queries,
                        key_differentiators: key_differentiators
                    }, function (response) {
                        btn.prop('disabled', false).text('Save LLM Targeting');
                        if (response.success) {
                            alert('✅ Settings saved successfully!');
                        } else {
                            alert('Error saving settings.');
                        }
                    });
                });

                // Auto-Fill Handler
                $(document).on('click', '#chroma-llm-autofill', function (e) {
                    e.preventDefault();
                    var btn = $(this);

                    if (!confirm('This will overwrite existing fields with AI-generated content. Continue?')) {
                        return;
                    }

                    btn.prop('disabled', true).text('Generating...');

                    $.post(ajaxurl, {
                        action: 'chroma_generate_llm_targeting',
                        nonce: chroma_nonce,
                        post_id: $('#chroma-llm-post-id').val()
                    }, function (response) {
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-superhero" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span> Auto-Fill with AI');

                        if (response.success) {
                            var data = response.data;
                            $('#seo_llm_primary_intent').val(data.primary_intent);

                            // Clear and populate queries
                            $('#llm-queries-container').empty();
                            if (data.target_queries && Array.isArray(data.target_queries)) {
                                data.target_queries.forEach(function (q) {
                                    var html = '<div class="chroma-repeater-row" style="margin-bottom: 8px;"><input type="text" class="chroma-llm-query-input regular-text" value="' + q + '" style="width: 80%;"> <button class="button remove-llm-row">×</button></div>';
                                    $('#llm-queries-container').append(html);
                                });
                            }

                            // Clear and populate differentiators
                            $('#llm-diffs-container').empty();
                            if (data.key_differentiators && Array.isArray(data.key_differentiators)) {
                                data.key_differentiators.forEach(function (d) {
                                    var html = '<div class="chroma-repeater-row" style="margin-bottom: 8px;"><input type="text" class="chroma-llm-diff-input regular-text" value="' + d + '" style="width: 80%;"> <button class="button remove-llm-row">×</button></div>';
                                    $('#llm-diffs-container').append(html);
                                });
                            }

                            alert('✨ Content generated successfully!');
                        } else {
                            alert('AI Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                        }
                    });
                });

                // Add query row
                $(document).on('click', '#add-llm-query', function (e) {
                    e.preventDefault();
                    var html = '<div class="chroma-repeater-row" style="margin-bottom: 8px;"><input type="text" class="chroma-llm-query-input regular-text" placeholder="e.g., best preschool curriculum" style="width: 80%;"> <button class="button remove-llm-row">×</button></div>';
                    $('#llm-queries-container').append(html);
                });

                // Add differentiator row
                $(document).on('click', '#add-llm-diff', function (e) {
                    e.preventDefault();
                    var html = '<div class="chroma-repeater-row" style="margin-bottom: 8px;"><input type="text" class="chroma-llm-diff-input regular-text" placeholder="e.g., STEAM-focused curriculum" style="width: 80%;"> <button class="button remove-llm-row">×</button></div>';
                    $('#llm-diffs-container').append(html);
                });

                // Remove row
                $(document).on('click', '.remove-llm-row', function (e) {
                    e.preventDefault();
                    $(this).closest('.chroma-repeater-row').remove();
                });
            });
        </script>
        <?php
    }

    /**
     * Render Overview Tab (Generic)
     */
    private function render_overview_tab($post_type)
    {
        $args = [
            'post_type' => $post_type,
            'posts_per_page' => 50,
            'orderby' => 'title',
            'order' => 'ASC',
        ];
        $posts = get_posts($args);
        $type_obj = get_post_type_object($post_type);
        ?>
        <p class="description">
            Overview of SEO/LLM data for <strong><?php echo esc_html($type_obj->labels->name); ?></strong>.
            <span class="chroma-badge chroma-badge-manual">Manual</span> values are set by you.
            <span class="chroma-badge chroma-badge-auto">Auto</span> values are generated by the system fallbacks.
        </p>
        <br>
        <table class="chroma-seo-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">Status</th>
                    <th style="width: 250px;">Title</th>
                    <th>LLM Context</th>
                    <th>Schema</th>
                    <th style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $p):
                    if (!$p || !is_a($p, 'WP_Post'))
                        continue;
                    $id = $p->ID;
                    // LLM Context
                    $intent_manual = get_post_meta($id, 'seo_llm_primary_intent', true);
                    $desc = Chroma_Fallback_Resolver::get_llm_description($id);
                    // Schema
                    $schemas = get_post_meta($id, '_chroma_post_schemas', true);
                    $schema_count = is_array($schemas) ? count($schemas) : 0;

                    // Health
                    $health = $this->calculate_health($id, $intent_manual, $schema_count);

                    // Status Logic
                    $status_color = 'green';
                    $status_reason = 'Optimized';
                    if (empty($schemas)) {
                        $status_color = 'orange'; // Changed from red to orange
                        $status_reason = 'Default Schema'; // Changed from Missing Schema
                    } elseif (empty($intent_manual)) {
                        $status_color = 'orange';
                        $status_reason = 'Missing Intent';
                    }
                    ?>
                    <tr>
                        <td style="text-align: center;">
                            <span class="chroma-health-dot chroma-health-<?php echo esc_attr($health['status']); ?>"
                                title="<?php echo esc_attr($health['message']); ?>"></span>
                        </td>
                        <td>
                            <strong><a
                                    href="<?php echo admin_url('post.php?post=' . $id . '&action=edit'); ?>"><?php echo esc_html($p->post_title); ?></a></strong>
                            <?php if ($post_type === 'location'): ?>
                                <br><small><?php echo get_post_meta($id, 'location_city', true); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="margin-bottom: 6px;">
                                <strong>Intent:</strong>
                                <?php if ($intent_manual): ?>
                                    <span class="chroma-value-manual"><?php echo esc_html($intent_manual); ?></span>
                                <?php else: ?>
                                    <span class="chroma-value-fallback">Auto-Generated</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong>Description:</strong>
                                <div style="font-size: 11px; line-height: 1.4;"><?php echo wp_trim_words($desc, 15); ?></div>
                            </div>
                        </td>
                        <td>
                            <?php if ($schema_count > 0): ?>
                                <span class="chroma-check">✓</span> <?php echo $schema_count; ?> Custom Schema(s)
                            <?php else: ?>
                                <span style="color: #ccc;">-</span> Default
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="chroma-status-dot" style="background: <?php echo esc_attr($status_color); ?>;"></span>
                            <span
                                style="font-size: 12px; color: #666; margin-left: 5px;"><?php echo esc_html($status_reason); ?></span>
                        </td>
                        <td>
                            <a href="?page=chroma-seo-dashboard&tab=schema-builder&post_id=<?php echo $id; ?>"
                                class="button button-small">Builder</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Calculate SEO Health
     */
    private function calculate_health($post_id, $intent, $schema_count)
    {
        if ($intent && $schema_count > 0) {
            return ['status' => 'good', 'message' => 'Excellent! Custom Intent & Schema defined.'];
        } elseif ($intent || $schema_count > 0) {
            return ['status' => 'ok', 'message' => 'Good. Either Intent or Schema is customized.'];
        } else {
            return ['status' => 'poor', 'message' => 'Basic. Using all default values.'];
        }
    }

    /**
     * Render Schema Builder Tab
     */
    private function render_schema_builder_tab()
    {
        $locations = get_posts(['post_type' => 'location', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC']);
        $programs = get_posts(['post_type' => 'program', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC']);
        $pages = get_posts(['post_type' => 'page', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC']);
        $posts = get_posts(['post_type' => 'post', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC']);

        $selected_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        ?>
        <div class="chroma-inspector-controls">
            <label><strong>Select Page to Edit Schema:</strong></label>
            <select id="chroma-inspector-select" style="min-width: 300px;">
                <option value="">-- Select a Page --</option>
                <optgroup label="Locations">
                    <?php foreach ($locations as $loc):
                        if (!$loc || !is_a($loc, 'WP_Post'))
                            continue; ?>
                        <option value="<?php echo $loc->ID; ?>" <?php selected($selected_id, $loc->ID); ?>>
                            <?php echo esc_html($loc->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Programs">
                    <?php foreach ($programs as $prog):
                        if (!$prog || !is_a($prog, 'WP_Post'))
                            continue; ?>
                        <option value="<?php echo $prog->ID; ?>" <?php selected($selected_id, $prog->ID); ?>>
                            <?php echo esc_html($prog->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Pages">
                    <?php foreach ($pages as $pg):
                        if (!$pg || !is_a($pg, 'WP_Post'))
                            continue; ?>
                        <option value="<?php echo $pg->ID; ?>" <?php selected($selected_id, $pg->ID); ?>>
                            <?php echo esc_html($pg->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Cities">
                    <?php
                    $cities = get_posts(['post_type' => 'city', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                    foreach ($cities as $city):
                        if (!$city || !is_a($city, 'WP_Post'))
                            continue; ?>
                        <option value="<?php echo $city->ID; ?>" <?php selected($selected_id, $city->ID); ?>>
                            <?php echo esc_html($city->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Blog Posts">
                    <?php foreach ($posts as $pt):
                        if (!$pt || !is_a($pt, 'WP_Post'))
                            continue; ?>
                        <option value="<?php echo $pt->ID; ?>" <?php selected($selected_id, $pt->ID); ?>>
                            <?php echo esc_html($pt->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <button type="button" class="button button-link-delete" id="chroma-reset-schema-btn"
                style="margin-left: 10px; display: none;">Reset all Schemas for this Page</button>
            <span class="spinner" id="chroma-inspector-spinner"></span>
        </div>

        <div id="chroma-inspector-content">
            <p class="description">Select a page above to view and edit its Schema/SEO data.</p>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                var chroma_nonce = '<?php echo wp_create_nonce('chroma_seo_dashboard_nonce'); ?>';
                var selectedId = '<?php echo $selected_id; ?>';

                if (selectedId && selectedId != '0') {
                    loadInspectorData(selectedId);
                }

                $('#chroma-inspector-select').on('change', function () {
                    var id = $(this).val();
                    if (id) {
                        loadInspectorData(id);
                    } else {
                        $('#chroma-inspector-content').empty();
                        $('#chroma-reset-schema-btn').hide();
                    }
                });

                // Reset Schema Handler
                $('#chroma-reset-schema-btn').on('click', function (e) {
                    e.preventDefault(); if (!confirm('Are you sure you want to delete ALL schema data for this page? This cannot be undone.')) return; var id = $('#chroma-inspector-select').val();
                    if (!id) return;

                    var btn = $(this);
                    btn.prop('disabled', true);

                    $.post(ajaxurl, {
                        action: 'chroma_reset_post_schema',
                        nonce: chroma_nonce,
                        post_id: id
                    }, function (response) {
                        btn.prop('disabled', false);
                        if (response.success) {
                            alert('Schemas reset successfully.');
                            loadInspectorData(id);
                        } else {
                            alert(response.data.message || 'Error occurred.');
                        }
                    });
                });

                function loadInspectorData(id) {
                    $('#chroma-inspector-spinner').addClass('is-active');
                    $('#chroma-reset-schema-btn').show();
                    $.post(ajaxurl, {
                        action: 'chroma_fetch_schema_inspector',
                        nonce: chroma_nonce,
                        post_id: id
                    }, function (response) {
                        console.log('Schema Inspector AJAX Response:', response);
                        $('#chroma-inspector-spinner').removeClass('is-active');
                        if (response && response.success) {
                            $('#chroma-inspector-content').html(response.data.html);
                            initTooltips();
                        } else {
                            var msg = 'Error loading data.';
                            if (response && response.data && response.data.message) {
                                msg = response.data.message;
                            } else if (typeof response === 'string') {
                                msg = 'Server returned non-JSON: ' + response.substring(0, 200);
                            }
                            $('#chroma-inspector-content').html('<div style="background:#fee; padding:15px; border:1px solid #c00; color:#800;"><strong>Error:</strong> ' + msg + '</div>');
                        }
                    }).fail(function () {
                        $('#chroma-inspector-spinner').removeClass('is-active');
                        alert('Connection error');
                    });
                }

                function initTooltips() {
                    $(document).tooltip({
                        content: function () {
                            return $(this).attr('title');
                        },
                        position: {
                            my: "center bottom-20",
                            at: "center top",
                            using: function (position, feedback) {
                                $(this).css(position);
                                $("<div>")
                                    .addClass("arrow")
                                    .addClass(feedback.vertical)
                                    .addClass(feedback.horizontal)
                                    .appendTo(this);
                            }
                        }
                    });
                }

                // Add New Schema Handler
                $(document).on('click', '#chroma-add-schema-btn', function (e) {
                    e.preventDefault();
                    var type = $('#chroma-schema-type-select').val();
                    if (!type) return;

                    var container = $('#chroma-active-schemas');
                    var index = container.children('.chroma-schema-block').length;

                    // Fetch schema fields template via AJAX or use JS template
                    // For simplicity, we'll reload the inspector data with a param to add a new schema, 
                    // OR better: Append a new block via JS if we have the definitions.
                    // Given the complexity, let's trigger a reload or fetch just the new block.

                    // Strategy: We will just append a placeholder block and let the user save? 
                    // No, we need the fields. Let's ask the server for the fields for this type.

                    $.post(ajaxurl, {
                        action: 'chroma_get_schema_fields',
                        nonce: chroma_nonce,
                        schema_type: type,
                        index: index,
                        post_id: $('#chroma-inspector-post-id').val()
                    }, function (response) {
                        if (response.success) {
                            container.append(response.data.html);
                            initTooltips();
                        }
                    });
                });

                // Remove Schema Handler
                $(document).on('click', '.chroma-remove-schema', function (e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to remove this schema?')) {
                        $(this).closest('.chroma-schema-block').remove();
                    }
                });

                // Repeater: Add Row
                $(document).on('click', '.chroma-add-repeater-row', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    var fields = btn.data('fields');
                    var wrapper = btn.closest('.chroma-repeater-wrapper');
                    var container = wrapper.find('.chroma-repeater-items');

                    // Generate HTML for new row (simplified JS generation)
                    var html = '<div class="chroma-repeater-row" style="background: #f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #eee;">';
                    html += '<div style="text-align: right; margin-bottom: 5px;"><span class="chroma-remove-repeater-row dashicons dashicons-trash" style="cursor: pointer; color: #d63638;"></span></div>';

                    $.each(fields, function (key, field) {
                        html += '<div style="margin-bottom: 5px;">';
                        html += '<label style="font-size: 12px; font-weight: 600; display: block;">' + field.label + '</label>';
                        if (field.type === 'textarea') {
                            html += '<textarea class="chroma-repeater-input large-text" data-name="' + key + '" rows="2" style="width: 100%;"></textarea>';
                        } else {
                            html += '<input type="text" class="chroma-repeater-input regular-text" data-name="' + key + '" value="" style="width: 100%;">';
                        }
                        html += '</div>';
                    });
                    html += '</div>';

                    container.append(html);
                });

                // Repeater: Remove Row
                $(document).on('click', '.chroma-remove-repeater-row', function (e) {
                    e.preventDefault();
                    if (confirm('Remove this row?')) {
                        $(this).closest('.chroma-repeater-row').remove();
                    }
                });

                // Save Handler
                $(document).on('click', '#chroma-inspector-save', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    btn.prop('disabled', true).text('Saving...');

                    var schemas = [];

                    $('.chroma-schema-block').each(function () {
                        var block = $(this);
                        var schema = {
                            type: block.data('type'),
                            data: {}
                        };

                        // Regular fields
                        block.find('.chroma-schema-input').each(function () {
                            var name = $(this).data('name');
                            var val = $(this).val();
                            if (val) schema.data[name] = val;
                        });

                        // Repeater fields
                        block.find('.chroma-repeater-wrapper').each(function () {
                            var wrapper = $(this);
                            var key = wrapper.data('key');
                            var rows = [];

                            wrapper.find('.chroma-repeater-row').each(function () {
                                var row = {};
                                $(this).find('.chroma-repeater-input').each(function () {
                                    var subName = $(this).data('name');
                                    var subVal = $(this).val();
                                    if (subVal) row[subName] = subVal;
                                });
                                if (!$.isEmptyObject(row)) rows.push(row);
                            });

                            if (rows.length > 0) schema.data[key] = rows;
                        });

                        schemas.push(schema);
                    });

                    $.post(ajaxurl, {
                        action: 'chroma_save_schema_inspector',
                        nonce: chroma_nonce,
                        post_id: $('#chroma-inspector-post-id').val(),
                        schemas: schemas
                    }, function (response) {
                        btn.prop('disabled', false).text('Update Schema Settings');
                        if (response.success) {
                            alert('Settings saved successfully!');
                        } else {
                            alert('Error saving settings.');
                        }
                    });
                });
                // AI Auto-Fill Handler
                $(document).on('click', '.chroma-ai-autofill', function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    var block = btn.closest('.chroma-schema-block');
                    var type = btn.data('type');
                    var postId = $('#chroma-inspector-post-id').val();

                    if (!confirm('This will overwrite existing fields with AI-generated content. Continue?')) {
                        return;
                    }

                    btn.prop('disabled', true).text('Generating...');

                    $.post(ajaxurl, {
                        action: 'chroma_generate_schema',
                        post_id: postId,
                        schema_type: type
                    }, function (response) {
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-superhero" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span> Auto-Fill');

                        if (response.success) {
                            var data = response.data;
                            // Populate fields
                            $.each(data, function (key, value) {
                                var input = block.find('[data-name="' + key + '"]');
                                if (input.length) {
                                    if (input.hasClass('chroma-repeater-input')) {
                                        // Handle simple repeater logic if needed, for now supports simple fields
                                    } else {
                                        input.val(value);
                                        // Highlight change
                                        input.css('background-color', '#f0f6fc').animate({ backgroundColor: '#fff' }, 2000);
                                    }
                                }
                            });
                             // Auto-save after AI fills data
                                    $('#chroma-inspector-save').trigger('click');
                                } else {
                                    alert('AI Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                                }
                            });
                        });
                    });
                </script>
                <?php
    }

    /**
     * AJAX: Fetch Inspector Data (Schema Builder)
     */
    public function ajax_fetch_inspector_data()
    {
        // Capture any stray output that might corrupt JSON
        ob_start();

        // Debug Logging
        error_log('Chroma SEO: ajax_fetch_inspector_data called');

        if (!check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce', false)) {
            ob_end_clean();
            error_log('Chroma SEO: Nonce verification failed');
            wp_send_json_error(['message' => 'Security check failed (Nonce)']);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        error_log('Chroma SEO: Fetching schema for Post ID: ' . $post_id);

        if (!$post_id) {
            ob_end_clean();
            error_log('Chroma SEO: No Post ID provided');
            wp_send_json_error(['message' => 'Invalid Post ID']);
        }

        if (!class_exists('Chroma_Schema_Types')) {
            ob_end_clean();
            error_log('Chroma SEO: Chroma_Schema_Types class missing');
            wp_send_json_error(['message' => 'Critical Error: Schema Types Library missing']);
        }

        // Clean any stray output before try block
        ob_end_clean();

        try {
            // Start capturing output IMMEDIATELY in try block
            ob_start();            // Get existing schemas
            $existing_schemas = get_post_meta($post_id, '_chroma_post_schemas', true);
            if (!is_array($existing_schemas) || empty($existing_schemas)) {
                $existing_schemas = [];
                // Backwards compatibility: Check for legacy schema data
                $legacy_type = get_post_meta($post_id, '_chroma_schema_type', true);
                if ($legacy_type && $legacy_type !== 'none') {
                    $legacy_data = get_post_meta($post_id, '_chroma_schema_data', true);
                    if (!is_array($legacy_data))
                        $legacy_data = [];

                    // Add as a new modular schema
                    $existing_schemas[] = [
                        'type' => $legacy_type,
                        'data' => $legacy_data
                    ];
                }
                // If still no schemas, try to load smart defaults based on post type
                if (empty($existing_schemas)) {
                    // Use the Schema Injector to get defaults if available
                    if (class_exists('Chroma_Schema_Injector')) {
                        $defaults = Chroma_Schema_Injector::get_default_schema_for_post_type($post_id);
                        if (!empty($defaults)) {
                            $existing_schemas = $defaults;
                        }
                    }
                }
            }
            $available_types = Chroma_Schema_Types::get_definitions();
            ?>
                        <input type="hidden" id="chroma-inspector-post-id" value="<?php echo $post_id; ?>">

                        <!-- DEBUG PANEL - Remove after fixing -->
                        <details style="background: #fffbcc; border: 1px solid #e0d800; padding: 10px; margin-bottom: 15px;">
                            <summary style="cursor: pointer; font-weight: bold; color: #806600;">🔍 Debug: Click to see raw schema data from
                                database</summary>
                            <pre style="background: #fff; padding: 10px; margin-top: 10px; overflow: auto; max-height: 300px; font-size: 11px;"><?php
                            echo "Post ID: {$post_id}\n\n";
                            echo "Raw _chroma_post_schemas meta:\n";
                            echo htmlspecialchars(print_r($existing_schemas, true));
                            ?></pre>
                        </details>

                        <div id="chroma-active-schemas">
                            <?php
                            error_log('Chroma SEO: Raw existing_schemas from DB: ' . print_r($existing_schemas, true));

                            if (empty($existing_schemas)) {
                                echo '<p class="description" style="padding: 20px; text-align: center;">No custom schemas added yet. Add one above.</p>';
                            } else {
                                $valid_count = 0;
                                foreach ($existing_schemas as $index => $schema) {
                                    // Log each schema item for debugging
                                    error_log("Chroma SEO: Schema item [{$index}]: " . print_r($schema, true));

                                    if (!is_array($schema)) {
                                        error_log("Chroma SEO: Schema [{$index}] is not an array, skipping.");
                                        continue;
                                    }
                                    if (!isset($schema['type'])) {
                                        error_log("Chroma SEO: Schema [{$index}] missing 'type' key, skipping.");
                                        continue;
                                    }
                                    if (!isset($schema['data']) || !is_array($schema['data'])) {
                                        error_log("Chroma SEO: Schema [{$index}] missing or invalid 'data' key, skipping.");
                                        continue;
                                    }
                                    $valid_count++;
                                    $this->render_schema_block($schema['type'], $schema['data'], $index);
                                }
                                if ($valid_count === 0 && !empty($existing_schemas)) {
                                    echo '<div class="notice notice-error" style="padding: 10px; margin: 10px 0;">';
                                    echo '<p><strong>Warning:</strong> Schema data appears to be corrupted. The stored data is not in the expected format.</p>';
                                    echo '<p>Use the "Reset all Schemas for this Page" button above to clear and start fresh.</p>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>

                        <div
                            style="display: flex; gap: 20px; margin-top: 20px; margin-bottom: 20px; align-items: center; background: #fff; padding: 15px; border: 1px solid #ddd;">
                            <strong>Add New Schema:</strong>
                            <select id="chroma-schema-type-select">
                                <option value="">-- Select Type --</option>
                                <?php foreach ($available_types as $type => $def): ?>
                                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($def['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="chroma-add-schema-btn" class="button button-secondary">Add Schema</button>
                        </div>

                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ccc;">
                            <button id="chroma-inspector-save" class="button button-primary button-large">Save All Schemas</button>
                        </div>
                        <?php
                        $html = ob_get_clean();

                        // Force clean response
                        @header('Content-Type: application/json; charset=utf-8');
                        echo json_encode(['success' => true, 'data' => ['html' => $html]]);
                        die();

        } catch (Throwable $e) {
            ob_end_clean(); // Clean buffer if error
            error_log('Chroma SEO Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Render a single schema block
     */
    private function render_schema_block($type, $data = [], $index = 0)
    {
        $definitions = Chroma_Schema_Types::get_definitions();
        if (!isset($definitions[$type]))
            return;

        $def = $definitions[$type];
        ?>
                <div class="chroma-schema-block" data-type="<?php echo esc_attr($type); ?>"
                    style="background: #fff; border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 15px; position: relative;">
                    <h3
                        style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                        <span><?php echo esc_html($def['label']); ?></span>
                        <div>
                            <button class="button button-small chroma-ai-autofill" data-type="<?php echo esc_attr($type); ?>"
                                style="margin-right: 10px; border-color: #8c64ff; color: #6b42e4;">
                                <span class="dashicons dashicons-superhero"
                                    style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span> Auto-Fill
                            </button>
                            <button class="chroma-remove-schema button-link-delete">Remove</button>
                        </div>
                    </h3>

                    <table class="form-table" style="margin-top: 0;">
                        <?php foreach ($def['fields'] as $key => $field):
                            $val = isset($data[$key]) ? $data[$key] : '';

                            // Handle array values for non-repeater fields (like sameAs)
                            if (is_array($val) && $field['type'] !== 'repeater') {
                                $val = implode(', ', $val);
                            }
                            ?>
                                <tr>
                                    <th scope="row" style="padding: 10px 0; width: 200px;">
                                        <?php echo esc_html($field['label']); ?>
                                        <?php if (!empty($field['description'])): ?>
                                                <span class="dashicons dashicons-editor-help chroma-help-tip"
                                                    title="<?php echo esc_attr($field['description']); ?>"
                                                    style="color: #999; font-size: 16px; cursor: help;"></span>
                                        <?php endif; ?>
                                    </th>
                                    <td style="padding: 10px 0;">
                                        <?php if ($field['type'] === 'repeater'): ?>
                                                <div class="chroma-repeater-wrapper" data-key="<?php echo esc_attr($key); ?>">
                                                    <div class="chroma-repeater-items">
                                                        <?php
                                                        $sub_items = is_array($val) ? $val : [];
                                                        if (empty($sub_items)) {
                                                            // Add one empty row by default? No, let user add.
                                                        }
                                                        foreach ($sub_items as $sub_index => $sub_item) {
                                                            $this->render_repeater_row($field['subfields'], $sub_item, $key);
                                                        }
                                                        ?>
                                                    </div>
                                                    <button class="button button-small chroma-add-repeater-row"
                                                        data-fields="<?php echo esc_attr(json_encode($field['subfields'])); ?>">Add Row</button>
                                                </div>
                                        <?php elseif ($field['type'] === 'textarea'): ?>
                                                <textarea class="chroma-schema-input large-text" data-name="<?php echo esc_attr($key); ?>"
                                                    rows="3"><?php echo esc_textarea($val); ?></textarea>
                                        <?php else: ?>
                                                <input type="text" class="chroma-schema-input regular-text" data-name="<?php echo esc_attr($key); ?>"
                                                    value="<?php echo esc_attr($val); ?>" style="width: 100%;">
                                        <?php endif; ?>
                                    </td>
                                </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php
    }

    /**
     * Render a repeater row
     */
    private function render_repeater_row($subfields, $data = [], $parent_key = '')
    {
        ?>
                <div class="chroma-repeater-row"
                    style="background: #f9f9f9; padding: 10px; margin-bottom: 10px; border: 1px solid #eee;">
                    <div style="text-align: right; margin-bottom: 5px;">
                        <span class="chroma-remove-repeater-row dashicons dashicons-trash"
                            style="cursor: pointer; color: #d63638;"></span>
                    </div>
                    <?php foreach ($subfields as $sub_key => $sub_field):
                        $val = isset($data[$sub_key]) ? $data[$sub_key] : '';
                        ?>
                            <div style="margin-bottom: 5px;">
                                <label
                                    style="font-size: 12px; font-weight: 600; display: block;"><?php echo esc_html($sub_field['label']); ?></label>
                                <?php if ($sub_field['type'] === 'textarea'): ?>
                                        <textarea class="chroma-repeater-input large-text" data-name="<?php echo esc_attr($sub_key); ?>" rows="2"
                                            style="width: 100%;"><?php echo esc_textarea($val); ?></textarea>
                                <?php else: ?>
                                        <input type="text" class="chroma-repeater-input regular-text" data-name="<?php echo esc_attr($sub_key); ?>"
                                            value="<?php echo esc_attr($val); ?>" style="width: 100%;">
                                <?php endif; ?>
                            </div>
                    <?php endforeach; ?>
                </div>
                <?php
    }

    /**
     * AJAX: Get Schema Fields (for adding new block)
     */
    public function ajax_get_schema_fields()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');
        $type = sanitize_text_field($_POST['schema_type']);
        $index = intval($_POST['index']);
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        $prefill_data = [];
        if ($post_id) {
            $post = get_post($post_id);
            if ($post) {
                // Common prefill fields
                $prefill_data['name'] = $post->post_title;
                $prefill_data['headline'] = $post->post_title;
                $prefill_data['description'] = wp_trim_words($post->post_content, 25);
                $prefill_data['url'] = get_permalink($post_id);
                $prefill_data['datePublished'] = get_the_date('Y-m-d', $post);
                $prefill_data['dateModified'] = get_the_modified_date('Y-m-d', $post);

                $img_id = get_post_thumbnail_id($post_id);
                if ($img_id) {
                    $prefill_data['image'] = wp_get_attachment_image_url($img_id, 'full');
                }
            }
        }

        ob_start();
        $this->render_schema_block($type, $prefill_data, $index);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX: Save Inspector Data
     */
    public function ajax_save_inspector_data()
    {
        // Log incoming request for debugging
        error_log('Chroma SEO Save: ajax_save_inspector_data called');

        if (!check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce', false)) {
            error_log('Chroma SEO Save: Nonce verification failed');
            wp_send_json_error(['message' => 'Security check failed']);
        }

        if (!current_user_can('edit_posts')) {
            error_log('Chroma SEO Save: User lacks edit_posts capability');
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $schemas = isset($_POST['schemas']) ? $_POST['schemas'] : [];

        error_log('Chroma SEO Save: Post ID = ' . $post_id);
        error_log('Chroma SEO Save: Raw schemas received = ' . print_r($schemas, true));

        if (!$post_id) {
            error_log('Chroma SEO Save: Invalid post ID');
            wp_send_json_error(['message' => 'Invalid post ID']);
        }

        // Sanitize
        $clean_schemas = [];
        if (is_array($schemas)) {
            foreach ($schemas as $s) {
                // Be more lenient - accept schemas even if data is empty
                if (isset($s['type'])) {
                    $clean_data = [];
                    if (isset($s['data']) && is_array($s['data'])) {
                        foreach ($s['data'] as $k => $v) {
                            if (is_array($v)) {
                                // Handle Repeater (Array of Arrays)
                                $clean_repeater = [];
                                foreach ($v as $row) {
                                    if (is_array($row)) {
                                        $clean_row = [];
                                        foreach ($row as $rk => $rv) {
                                            $clean_row[sanitize_key($rk)] = sanitize_textarea_field($rv);
                                        }
                                        $clean_repeater[] = $clean_row;
                                    }
                                }
                                $clean_data[sanitize_key($k)] = $clean_repeater;
                            } else {
                                // Handle Simple Field
                                $clean_data[sanitize_key($k)] = sanitize_textarea_field($v);
                            }
                        }
                    }
                    $clean_schemas[] = [
                        'type' => sanitize_text_field($s['type']),
                        'data' => $clean_data
                    ];
                }
            }
        }

        error_log('Chroma SEO Save: Cleaned schemas = ' . print_r($clean_schemas, true));

        $result = update_post_meta($post_id, '_chroma_post_schemas', $clean_schemas);
        error_log('Chroma SEO Save: update_post_meta result = ' . ($result ? 'success/updated' : 'no change or failed'));

        wp_send_json_success(['message' => 'Saved successfully', 'schemas_count' => count($clean_schemas)]);
    }
    /**
     * Render Social Preview Tab
     */
    private function render_social_tab()
    {
        $posts = get_posts(['post_type' => 'post', 'posts_per_page' => 50]);
        ?>
                <div class="chroma-seo-card">
                    <h2>Social Media Preview</h2>
                    <p>Preview how your posts will look on Facebook, Twitter, and LinkedIn.</p>

                    <div style="margin: 20px 0;">
                        <label for="chroma-social-select"><strong>Select Post:</strong></label>
                        <select id="chroma-social-select">
                            <option value="">-- Select a Post --</option>
                            <?php foreach ($posts as $p): ?>
                                    <option value="<?php echo $p->ID; ?>"><?php echo esc_html($p->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="chroma-social-preview-container" style="display: none; max-width: 600px;">
                        <div class="chroma-seo-card">
                            <h3>Facebook / OG Preview</h3>
                            <div
                                style="border: 1px solid #dadde1; border-radius: 8px; overflow: hidden; font-family: Helvetica, Arial, sans-serif;">
                                <div id="chroma-og-image"
                                    style="height: 315px; background-color: #f0f2f5; background-size: cover; background-position: center;">
                                </div>
                                <div style="padding: 10px 12px; background: #f0f2f5; border-top: 1px solid #dadde1;">
                                    <div style="font-size: 12px; color: #606770; text-transform: uppercase;" id="chroma-og-site">
                                        <?php echo $_SERVER['HTTP_HOST']; ?>
                                    </div>
                                    <div style="font-family: Georgia, serif; font-size: 16px; color: #1d2129; font-weight: 600; margin: 5px 0;"
                                        id="chroma-og-title">Page Title</div>
                                    <div style="font-size: 14px; color: #606770; line-height: 20px; max-height: 40px; overflow: hidden;"
                                        id="chroma-og-desc">Page description goes here...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    jQuery(document).ready(function ($) {
                        $('#chroma-social-select').on('change', function () {
                            var pid = $(this).val();
                            if (!pid) {
                                $('#chroma-social-preview-container').hide();
                                return;
                            }

                            $.post(ajaxurl, {
                                action: 'chroma_fetch_social_preview',
                                nonce: '<?php echo wp_create_nonce('chroma_seo_dashboard_nonce'); ?>',
                                post_id: pid
                            }, function (response) {
                                if (response.success) {
                                    var data = response.data;
                                    $('#chroma-og-title').text(data.title);
                                    $('#chroma-og-desc').text(data.description);
                                    $('#chroma-og-site').text(data.site_name);

                                    if (data.image) {
                                        $('#chroma-og-image').css('background-image', 'url(' + data.image + ')');
                                    } else {
                                        $('#chroma-og-image').css('background-image', 'none');
                                    }

                                    $('#chroma-social-preview-container').show();
                                }
                            });
                        });
                    });
                </script>
                <?php
    }

    /**
     * AJAX: Fetch Social Preview Data
     */
    public function ajax_fetch_social_preview()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');
        $post_id = intval($_POST['post_id']);
        if (!$post_id)
            wp_send_json_error();

        $post = get_post($post_id);
        if (!$post)
            wp_send_json_error();

        // Use our Fallback Resolver to get the actual SEO data
        $title = get_post_meta($post_id, 'seo_llm_title', true) ?: $post->post_title;

        // Fallback description
        $desc = '';
        if (class_exists('Chroma_Fallback_Resolver')) {
            $desc = Chroma_Fallback_Resolver::get_llm_description($post_id);
        } else {
            $desc = get_post_meta($post_id, 'seo_llm_description', true) ?: wp_trim_words($post->post_content, 25);
        }

        // Image
        $img_id = get_post_thumbnail_id($post_id);
        $img_url = '';
        if ($img_id) {
            $img_url = wp_get_attachment_image_url($img_id, 'large');
        }

        wp_send_json_success([
            'title' => $title,
            'description' => $desc,
            'image' => $img_url,
            'site_name' => $_SERVER['HTTP_HOST']
        ]);
    }

    /**
     * AJAX: Fetch LLM Targeting Data
     */
    public function ajax_fetch_llm_data()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');

        $post_id = intval($_POST['post_id']);
        if (!$post_id)
            wp_send_json_error();

        // Get current values
        $primary_intent = get_post_meta($post_id, 'seo_llm_primary_intent', true);
        $target_queries = get_post_meta($post_id, 'seo_llm_target_queries', true) ?: [];
        $key_differentiators = get_post_meta($post_id, 'seo_llm_key_differentiators', true) ?: [];

        // Get fallbacks
        $fallback_queries = Chroma_Fallback_Resolver::get_llm_target_queries($post_id);
        $fallback_differentiators = Chroma_Fallback_Resolver::get_llm_key_differentiators($post_id);

        ob_start();
        ?>
                <input type="hidden" id="chroma-llm-post-id" value="<?php echo $post_id; ?>">

                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 20px;">
                    <h3 style="margin-top: 0;">LLM Targeting for: <?php echo get_the_title($post_id); ?></h3>

                    <p class="description" style="margin-bottom: 20px;">
                        <strong>Optimize how AI assistants (ChatGPT, Claude, Perplexity) recommend this page.</strong>
                        <button id="chroma-llm-autofill" class="button button-secondary"
                            style="margin-left: 10px; border-color: #8c64ff; color: #6b42e4;">
                            <span class="dashicons dashicons-superhero"
                                style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span> Auto-Fill with AI
                        </button>
                    </p>

                    <!-- Primary Intent -->
                    <div style="margin-bottom: 25px;">
                        <label for="seo_llm_primary_intent" style="display: block; font-weight: 600; margin-bottom: 8px;">
                            Primary Intent
                        </label>
                        <input type="text" id="seo_llm_primary_intent" class="regular-text"
                            value="<?php echo esc_attr($primary_intent); ?>"
                            placeholder="e.g., childcare_discovery, program_information" style="width: 100%; max-width: 500px;">
                        <?php if (empty($primary_intent)): ?>
                                <p class="description" style="color: #646970;">
                                    <em>Default: informational</em>
                                </p>
                        <?php endif; ?>
                    </div>

                    <!-- Target Queries -->
                    <div style="margin-bottom: 25px;">
                        <h4 style="margin-bottom: 10px;">Target Queries</h4>
                        <p class="description" style="margin-bottom: 10px;">
                            Natural language queries where LLMs should recommend this content.
                        </p>
                        <?php if (!empty($fallback_queries) && empty($target_queries)): ?>
                                <p class="description" style="color: #646970; font-style: italic; margin-bottom: 10px;">
                                    Auto-generated examples: <?php echo implode(', ', array_slice($fallback_queries, 0, 2)); ?>
                                </p>
                        <?php endif; ?>
                        <div id="llm-queries-container">
                            <?php foreach ($target_queries as $query): ?>
                                    <div class="chroma-repeater-row" style="margin-bottom: 8px;">
                                        <input type="text" class="chroma-llm-query-input regular-text" value="<?php echo esc_attr($query); ?>"
                                            placeholder="e.g., best preschool curriculum" style="width: 80%;">
                                        <button class="button remove-llm-row">×</button>
                                    </div>
                            <?php endforeach; ?>
                        </div>
                        <button id="add-llm-query" class="button button-secondary">+ Add Query</button>
                    </div>

                    <!-- Key Differentiators -->
                    <div style="margin-bottom: 25px;">
                        <h4 style="margin-bottom: 10px;">Key Differentiators</h4>
                        <p class="description" style="margin-bottom: 10px;">
                            What makes this content unique? LLMs use these as talking points.
                        </p>
                        <?php if (!empty($fallback_differentiators) && empty($key_differentiators)): ?>
                                <p class="description" style="color: #646970; font-style: italic; margin-bottom: 10px;">
                                    Auto-discovered: <?php echo implode('; ', array_slice($fallback_differentiators, 0, 2)); ?>
                                </p>
                        <?php endif; ?>
                        <div id="llm-diffs-container">
                            <?php foreach ($key_differentiators as $diff): ?>
                                    <div class="chroma-repeater-row" style="margin-bottom: 8px;">
                                        <input type="text" class="chroma-llm-diff-input regular-text" value="<?php echo esc_attr($diff); ?>"
                                            placeholder="e.g., STEAM-focused curriculum" style="width: 80%;">
                                        <button class="button remove-llm-row">×</button>
                                    </div>
                            <?php endforeach; ?>
                        </div>
                        <button id="add-llm-diff" class="button button-secondary">+ Add Differentiator</button>
                    </div>

                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccc;">
                        <button id="chroma-llm-save" class="button button-primary button-large">
                            Save LLM Targeting
                        </button>
                    </div>
                </div>
                <?php
                $html = ob_get_clean();
                wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX: Save LLM Targeting Data
     */
    public function ajax_save_llm_targeting()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_posts'))
            wp_send_json_error();

        $post_id = intval($_POST['post_id']);
        if (!$post_id)
            wp_send_json_error();

        // Save data
        update_post_meta($post_id, 'seo_llm_primary_intent', sanitize_text_field($_POST['primary_intent']));

        $target_queries = isset($_POST['target_queries']) ? array_map('sanitize_text_field', $_POST['target_queries']) : [];
        update_post_meta($post_id, 'seo_llm_target_queries', $target_queries);

        $key_differentiators = isset($_POST['key_differentiators']) ? array_map('sanitize_text_field', $_POST['key_differentiators']) : [];
        update_post_meta($post_id, 'seo_llm_key_differentiators', $key_differentiators);

        wp_send_json_success();
    }

    /**
     * AJAX: Reset Post Schema (Bulk Action)
     */
    public function ajax_reset_post_schema()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');
        if (!current_user_can('edit_posts'))
            wp_send_json_error(['message' => 'Permission denied']);

        $post_id = intval($_POST['post_id']);
        if (!$post_id)
            wp_send_json_error(['message' => 'Invalid Post ID']);

        // Delete new schema meta
        delete_post_meta($post_id, '_chroma_post_schemas');

        // Delete legacy meta if exists to ensure clean slate
        delete_post_meta($post_id, '_chroma_schema_type');
        delete_post_meta($post_id, '_chroma_schema_data');

        wp_send_json_success(['message' => 'Schemas reset successfully']);
    }

    /**
     * Render Sitemap Tab
     */
    private function render_sitemap_tab()
    {
        // Save Handler
        if (isset($_POST['chroma_sitemap_save']) && check_admin_referer('chroma_sitemap_options')) {
            $options = array(
                'enable_pages' => isset($_POST['enable_pages']),
                'enable_posts' => isset($_POST['enable_posts']),
                'enable_locations' => isset($_POST['enable_locations']),
                'enable_programs' => isset($_POST['enable_programs']),
                'exclude_ids' => sanitize_text_field($_POST['exclude_ids']),
                'custom_urls' => sanitize_textarea_field($_POST['custom_urls']),
                'use_uploaded' => isset($_POST['use_uploaded']),
            );
            update_option('chroma_sitemap_options', $options);

            // Handle File Upload
            if (!empty($_FILES['sitemap_upload']['name'])) {
                $uploaded = $_FILES['sitemap_upload'];
                $upload_dir = wp_upload_dir();
                $target_path = $upload_dir['basedir'] . '/chroma-sitemap-manual.xml';

                if (move_uploaded_file($uploaded['tmp_name'], $target_path)) {
                    echo '<div class="notice notice-success"><p>Sitemap file uploaded successfully!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Failed to move uploaded file.</p></div>';
                }
            }

            // Flush Rewrites
            flush_rewrite_rules();
            echo '<div class="notice notice-success"><p>Sitemap settings saved and rewrite rules flushed.</p></div>';
        }

        // Get Options
        $options = get_option('chroma_sitemap_options', array(
            'enable_pages' => true,
            'enable_posts' => true,
            'enable_locations' => true,
            'enable_programs' => true,
            'exclude_ids' => '',
            'custom_urls' => '',
            'use_uploaded' => false,
        ));

        $sitemap_url = home_url('/sitemap.xml');
        ?>
                <div class="chroma-seo-card">
                    <h2>🗺️ Sitemap Manager</h2>
                    <p>Manage your XML Sitemap configuration. <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank"
                            class="button">View Sitemap</a></p>

                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('chroma_sitemap_options'); ?>

                        <div class="chroma-doc-section" style="margin-top: 20px;">
                            <h3>Content Types</h3>
                            <p>Select which content types to include in the sitemap:</p>
                            <fieldset>
                                <label><input type="checkbox" name="enable_pages" <?php checked($options['enable_pages']); ?>>
                                    Pages</label><br>
                                <label><input type="checkbox" name="enable_posts" <?php checked($options['enable_posts']); ?>> Blog
                                    Posts</label><br>
                                <label><input type="checkbox" name="enable_locations" <?php checked($options['enable_locations']); ?>>
                                    Locations</label><br>
                                <label><input type="checkbox" name="enable_programs" <?php checked($options['enable_programs']); ?>>
                                    Programs</label>
                            </fieldset>
                        </div>

                        <div class="chroma-doc-section" style="margin-top: 20px;">
                            <h3>Manual Control</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="exclude_ids">Exclude Post IDs</label></th>
                                    <td>
                                        <input name="exclude_ids" type="text" id="exclude_ids"
                                            value="<?php echo esc_attr($options['exclude_ids']); ?>" class="regular-text">
                                        <p class="description">Comma-separated list of Post IDs to exclude (e.g.,
                                            <code>12, 154, 404</code>)
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="custom_urls">Additional Custom URLs</label></th>
                                    <td>
                                        <textarea name="custom_urls" id="custom_urls" rows="5"
                                            class="large-text code"><?php echo esc_textarea($options['custom_urls']); ?></textarea>
                                        <p class="description">One URL per line. These will be appended to the sitemap.</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="chroma-doc-section" style="margin-top: 20px; border-top: 1px solid #ddd; padding-top: 20px;">
                            <h3>📂 Upload Custom Sitemap</h3>
                            <p>If you prefer to serve a static XML file instead of generating one dynamically.</p>

                            <label><input type="checkbox" name="use_uploaded" <?php checked($options['use_uploaded']); ?>> <strong>Use
                                    Uploaded Sitemap File</strong></label>
                            <p class="description">If checked, the dynamic generation above is ignored, and the uploaded file is served.
                            </p>
                            <br>
                            <input type="file" name="sitemap_upload" accept=".xml">
                        </div>

                        <p class="submit">
                            <input type="submit" name="chroma_sitemap_save" id="submit" class="button button-primary"
                                value="Save Changes & Flush Permalinks">
                        </p>
                    </form>
                </div>
                <?php
    }

    /**
     * Render Bulk Operations Tab
     */
    private function render_bulk_ops_tab()
    {
        $ptype = isset($_GET['ptype']) ? sanitize_text_field($_GET['ptype']) : 'location';
        $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $posts_per_page = 50;

        $query = new WP_Query([
            'post_type' => $ptype,
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'post_status' => 'publish'
        ]);

        $schema_definitions = class_exists('Chroma_Schema_Types') ? Chroma_Schema_Types::get_definitions() : [];
        ?>
                <div class="chroma-seo-card">
                    <h2>📦 Bulk Operations</h2>
                    <p>Perform AI tasks on multiple pages at once. Build a queue of actions and apply them to all selected posts.</p>

                    <!-- Filter Bar -->
                    <div
                        style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; background: #f0f0f1; padding: 10px; border-radius: 4px;">
                        <label><strong>Post Type:</strong></label>
                        <select
                            onchange="window.location.href='<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=bulk&ptype='); ?>' + this.value">
                            <option value="location" <?php selected($ptype, 'location'); ?>>Locations</option>
                            <option value="program" <?php selected($ptype, 'program'); ?>>Programs</option>
                            <option value="page" <?php selected($ptype, 'page'); ?>>Pages</option>
                            <option value="city" <?php selected($ptype, 'city'); ?>>Cities</option>
                            <option value="post" <?php selected($ptype, 'post'); ?>>Blog Posts</option>
                        </select>
                        <span class="count" style="color: #666;">(<?php echo $query->found_posts; ?> items found)</span>
                    </div>

                    <div style="display: flex; gap: 20px;">

                        <!-- Left: Post List -->
                        <div style="flex: 2;">
                            <!-- Controls -->
                            <div
                                style="padding: 10px; background: #fff; border: 1px solid #ddd; margin-bottom: -1px; border-radius: 4px 4px 0 0;">
                                <label><input type="checkbox" id="cb-select-all-bulk"> Select All on Page</label>
                            </div>

                            <!-- List -->
                            <div style="background: #fff; border: 1px solid #ddd; max-height: 500px; overflow-y: auto;">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <td class="check-column"><input type="checkbox" disabled></td>
                                            <th>Title</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($query->have_posts()):
                                            while ($query->have_posts()):
                                                $query->the_post(); ?>
                                                        <tr>
                                                            <th scope="row" class="check-column">
                                                                <input type="checkbox" name="bulk_post[]" value="<?php the_ID(); ?>">
                                                            </th>
                                                            <td>
                                                                <strong><?php the_title(); ?></strong>
                                                                <br>
                                                                <a href="<?php echo get_edit_post_link(); ?>" target="_blank"
                                                                    style="font-size: 11px;">Edit</a>
                                                                | <a href="<?php the_permalink(); ?>" target="_blank" style="font-size: 11px;">View</a>
                                                            </td>
                                                            <td id="status-<?php the_ID(); ?>">
                                                                <span class="dashicons dashicons-minus" style="color:#ccc;"></span>
                                                            </td>
                                                        </tr>
                                                <?php endwhile; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php
                            // Pagination
                            $big = 999999999;
                            echo paginate_links(array(
                                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                                'format' => '&paged=%#%',
                                'current' => max(1, $paged),
                                'total' => $query->max_num_pages
                            ));
                            ?>
                        </div>

                        <!-- Right: Actions -->
                        <div style="flex: 1;">
                            <div style="background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
                                <h3>🛠 Job Queue</h3>
                                <p class="description">Define what to do for each selected post.</p>

                                <div id="bulk-action-queue"
                                    style="margin-bottom: 20px; border: 1px solid #eee; min-height: 50px; background: #fafafa; padding: 10px;">
                                    <p id="queue-empty-msg" style="color: #999; font-style: italic; text-align: center; margin: 0;">
                                        Queue is empty.</p>
                                </div>

                                <div
                                    style="margin-bottom: 20px; padding: 10px; background: #f0f6fc; border: 1px solid #cce5ff; border-radius: 4px;">
                                    <label style="display: block; margin-bottom: 5px;"><strong>Add Action:</strong></label>
                                    <select id="bulk-add-action-selector" style="width: 100%; margin-bottom: 5px;">
                                        <option value="">-- Choose Action --</option>
                                        <option value="reset_schema" style="color: red;">❌ Reset/Clear All Schemas</option>
                                        <option value="llm_targeting">✨ Generate LLM Targeting</option>
                                        <option value="amenities">🛡️ Extract Safety Amenities (AI)</option>
                                        <optgroup label="Add Schema">
                                            <?php foreach ($schema_definitions as $key => $def): ?>
                                                    <option value="schema:<?php echo esc_attr($key); ?>">Schema:
                                                        <?php echo esc_html($def['label']); ?>
                                                    </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                    <button id="btn-add-to-queue" class="button button-secondary" style="width: 100%;">+ Add to
                                        Queue</button>
                                </div>

                                <hr>

                                <div style="margin-top: 20px;">
                                    <button id="btn-run-bulk" class="button button-primary button-large" style="width: 100%;" disabled>
                                        ▶ Run Bulk Process
                                    </button>
                                </div>

                                <!-- Progress -->
                                <div id="bulk-progress-container" style="display:none; margin-top: 20px;">
                                    <p><strong>Total Progress:</strong> <span id="bulk-counter">0/0</span></p>
                                    <div style="background: #eee; height: 10px; border-radius: 5px; overflow: hidden;">
                                        <div id="bulk-progress-bar"
                                            style="width: 0%; height: 100%; background: #0073aa; transition: width 0.3s;"></div>
                                    </div>
                                    <textarea id="bulk-log"
                                        style="width: 100%; height: 200px; margin-top: 10px; font-family: monospace; font-size: 11px;"
                                        readonly></textarea>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    jQuery(document).ready(function ($) {
                        var actionQueue = [];
                        var chroma_nonce = '<?php echo wp_create_nonce('chroma_seo_dashboard_nonce'); ?>';

                        // Add to Queue
                        $('#btn-add-to-queue').on('click', function (e) {
                            e.preventDefault();
                            var val = $('#bulk-add-action-selector').val();
                            var label = $('#bulk-add-action-selector option:selected').text();

                            if (!val) return;

                            var actionObj = { id: Date.now(), type: '', label: label };
                            if (val === 'llm_targeting') {
                                actionObj.type = 'llm_targeting';
                            } else if (val === 'reset_schema') {
                                actionObj.type = 'reset';
                            } else if (val.startsWith('schema:')) {
                                actionObj.type = 'schema';
                                actionObj.schemaType = val.split(':')[1];
                            }

                            actionQueue.push(actionObj);
                            renderQueue();
                        });

                        // Remove from Queue
                        $(document).on('click', '.remove-queue-item', function (e) {
                            e.preventDefault();
                            var id = $(this).data('id');
                            actionQueue = actionQueue.filter(function (item) { return item.id !== id; });
                            renderQueue();
                        });

                        function renderQueue() {
                            var container = $('#bulk-action-queue');
                            container.empty();

                            if (actionQueue.length === 0) {
                                container.html('<p id="queue-empty-msg" style="color: #999; font-style: italic; text-align: center; margin: 0;">Queue is empty.</p>');
                                $('#btn-run-bulk').prop('disabled', true);
                                return;
                            }

                            $('#btn-run-bulk').prop('disabled', false);

                            $.each(actionQueue, function (i, item) {
                                var html = '<div style="background: #fff; border: 1px solid #ddd; padding: 5px 10px; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center;">';
                                html += '<span>' + (i + 1) + '. ' + item.label + '</span>';
                                html += '<a href="#" class="remove-queue-item" data-id="' + item.id + '" style="color: #d63638; text-decoration: none;">&times;</a>';
                                html += '</div>';
                                container.append(html);
                            });
                        }

                        // Select All
                        $('#cb-select-all-bulk').on('change', function () {
                            $('input[name="bulk_post[]"]').prop('checked', $(this).is(':checked'));
                        });

                        // Run Process
                        $('#btn-run-bulk').on('click', function (e) {
                            e.preventDefault();

                            var posts = [];
                            $('input[name="bulk_post[]"]:checked').each(function () {
                                posts.push($(this).val());
                            });

                            if (posts.length === 0) {
                                alert('Please select at least one post.');
                                return;
                            }

                            if (actionQueue.length === 0) {
                                alert('Please add at least one action to the queue.');
                                return;
                            }

                            if (!confirm('Run ' + actionQueue.length + ' actions on ' + posts.length + ' posts? This may take a while.')) {
                                return;
                            }

                            var total = posts.length;
                            var processed = 0;

                            // Reset UI
                            $('#bulk-progress-container').show();
                            $('#bulk-progress-bar').css('width', '0%');
                            $('#bulk-counter').text('0/' + total);
                            $('#bulk-log').val('--- Starting Batch Process ---\n');
                            $(this).prop('disabled', true);

                            // Recursive Worker
                            function processNextPost() {
                                if (posts.length === 0) {
                                    $('#bulk-log').val($('#bulk-log').val() + '✅ All Posts Completed!\n');
                                    $('#btn-run-bulk').prop('disabled', false);
                                    alert('Batch Processing Complete!');
                                    return;
                                }

                                var pid = posts.shift();
                                var rowStatus = $('#status-' + pid);
                                rowStatus.html('<span class="dashicons dashicons-update" style="color: blue; animation: spin 2s infinite linear;"></span>');

                                log('Processing Post ID: ' + pid + '...');

                                // Process Actions sequentially for this post
                                var currentActions = [...actionQueue]; // Copy

                                function processNextAction() {
                                    if (currentActions.length === 0) {
                                        // Post Done
                                        processed++;
                                        var pct = Math.round((processed / total) * 100);
                                        $('#bulk-progress-bar').css('width', pct + '%');
                                        $('#bulk-counter').text(processed + '/' + total);
                                        rowStatus.html('<span class="dashicons dashicons-yes" style="color: green;"></span>');
                                        log('> Done with Post ID: ' + pid);
                                        processNextPost();
                                        return;
                                    }

                                    var action = currentActions.shift();
                                    log('> Running: ' + action.label + '...');

                                    var ajaxAction = '';
                                    var payload = {
                                        post_id: pid,
                                        auto_save: 'true',
                                        nonce: chroma_nonce
                                    };

                                    if (action.type === 'schema') {
                                        payload.action = 'chroma_generate_schema';
                                        payload.schema_type = action.schemaType;
                                    } else if (action.type === 'reset') {
                                        payload.action = 'chroma_reset_post_schema';
                                    } else {
                                        payload.action = 'chroma_generate_llm_targeting';
                                    }

                                    $.post(ajaxurl, payload, function (response) {
                                        if (response.success) {
                                            log('  ✓ Success');
                                        } else {
                                            log('  ❌ Failed: ' + (response.data.message || 'Unknown'));
                                        }
                                        processNextAction();
                                    }).fail(function () {
                                        log('  ❌ Network Error');
                                        processNextAction(); // Continue anyway
                                    });
                                }

                                processNextAction();
                            }

                            function log(msg) {
                                var area = $('#bulk-log');
                                area.val(area.val() + msg + '\n');
                                area.scrollTop(area[0].scrollHeight);
                            }

                            processNextPost();
                        });
                    });
                </script>
                <?php
    }

    /**
     * Render Bulk Validation Tab
     */
    private function render_bulk_validation_tab()
    {
        $post_types = ['location', 'program', 'page', 'post'];
        ?>
        <div class="chroma-seo-card">
            <h2>🔍 Bulk Schema Validator</h2>
            <p>Scan your entire site for Schema.org validation errors. This process fetches the live frontend of each page to ensure accurate results.</p>
            
            <div class="chroma-inspector-controls">
                <button id="start-bulk-scan" class="button button-primary button-large">
                    <span class="dashicons dashicons-search" style="line-height: 28px;"></span> Start Full Site Scan
                </button>
                <div id="scan-progress-wrapper" style="display:none; flex: 1; margin-left: 20px;">
                    <div style="background: #f0f0f1; border-radius: 4px; overflow: hidden; height: 20px; border: 1px solid #c3c4c7;">
                        <div id="scan-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
                    </div>
                    <div style="font-size: 12px; margin-top: 5px; color: #666;">
                        Scanned: <span id="scan-count">0</span> / <span id="scan-total">0</span> pages
                    </div>
                </div>
            </div>

            <div id="bulk-scan-summary" style="margin-top: 20px; display: none;">
                <div class="notice notice-info inline" style="margin: 0;">
                    <p>
                        <strong>Scan Complete!</strong> 
                        Found <span id="error-count" style="color:red; font-weight:bold;">0</span> invalid pages 
                        and <span id="valid-count" style="color:green; font-weight:bold;">0</span> valid pages.
                    </p>
                </div>
            </div>

            <br>

            <table class="chroma-seo-table widefat fixed striped" id="bulk-results-table" style="display:none;">
                <thead>
                    <tr>
                        <th style="width: 250px;">Page</th>
                        <th style="width: 100px;">Type</th>
                        <th style="width: 100px;">Status</th>
                        <th>Issues Found</th>
                        <th style="width: 100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Results injected here -->
                </tbody>
            </table>
            
            <!-- Bulk Fix Modal -->
            <div id="chroma-bulk-modal" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.6); backdrop-filter:blur(2px);">
                <div style="background-color:#fefefe; margin:50px auto; padding:0; border:1px solid #888; width:90%; max-width:1100px; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.2);">
                    <div style="padding:15px 20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#f8f9fa; border-radius:8px 8px 0 0;">
                        <h2 style="margin:0; font-size:18px; color:#333;">🔍 Schema Inspector & Fixer</h2>
                        <span id="chroma-bulk-close" style="color:#aaa; font-size:28px; font-weight:bold; cursor:pointer; line-height:1;">&times;</span>
                    </div>
                    <div style="padding:20px; display:flex; gap:20px; height:70vh;">
                        <div style="flex:1; display:flex; flex-direction:column;">
                            <h3 style="margin-top:0;">Current Schema (JSON-LD)</h3>
                            <textarea id="bulk-schema-viewer" style="flex:1; width:100%; font-family:monospace; font-size:12px; padding:10px; background:#f0f0f1; border:1px solid #ccc; white-space:pre; overflow:auto;" readonly></textarea>
                        </div>
                        <div style="flex:1; display:flex; flex-direction:column;">
                            <h3 style="margin-top:0;">Validation Report</h3>
                            <div id="bulk-error-report" style="flex:1; overflow-y:auto; border:1px solid #eee; padding:10px; background:#fff; margin-bottom:15px;"></div>
                            
                            <div id="bulk-fix-actions" style="border-top:1px solid #eee; padding-top:15px;">
                                <div style="display:flex; gap:10px; margin-bottom:15px;">
                                    <button id="bulk-fix-btn" class="button button-secondary button-large" style="flex:1;">✨ Generate AI Proposal</button>
                                    <button id="bulk-apply-btn" class="button button-primary button-large" style="flex:1; display:none;">💾 Apply Changes</button>
                                </div>
                                <div id="bulk-fix-result" style="display:none;">
                                    <h4 style="margin:0 0 5px; color:#2e7d32;">📝 Proposed Fix (Editable)</h4>
                                    <p style="margin:0 0 5px; font-size:11px; color:#666;">Review and edit the JSON below before saving.</p>
                                    <textarea id="bulk-fixed-schema" style="width:100%; height:200px; font-family:monospace; font-size:12px; padding:10px; border:1px solid #46b450; background:#fff; color:#333;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                var isScanning = false;
                var postTypes = <?php echo json_encode($post_types); ?>;
                var processedPosts = 0;
                var errorCount = 0;
                var validCount = 0;
                var totalPosts = 0;
                
                // Store results for modal
                var scanResults = {};

                $('#start-bulk-scan').on('click', function() {
                    if (isScanning) return;
                    isScanning = true;
                    
                    $(this).prop('disabled', true).text('Scanning...');
                    $('#scan-progress-wrapper').show();
                    $('#bulk-results-table').show().find('tbody').empty();
                    $('#bulk-scan-summary').hide();
                    
                    processedPosts = 0;
                    errorCount = 0;
                    validCount = 0;
                    scanResults = {};
                    $('#scan-count').text(0);
                    $('#scan-progress-bar').css('width', '0%');

                    log('Initializing scan...');
                    startBatchProcess();
                });

                function startBatchProcess() {
                    processBatch(0, 0); 
                }

                function processBatch(typeIndex, offset) {
                    if (typeIndex >= postTypes.length) {
                        finishScan();
                        return;
                    }

                    var currentType = postTypes[typeIndex];

                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        timeout: 15000, // 15s timeout forces client to give up if server hangs
                        data: {
                            action: 'chroma_scan_schema_batch',
                            post_type: currentType,
                            offset: offset,
                            nonce: '<?php echo wp_create_nonce('chroma_seo_dashboard_nonce'); ?>'
                        },
                        success: function(response) {
                             if (response.success) {
                                var data = response.data;
                                
                                if (offset === 0 && data.total_in_type) {
                                    totalPosts += data.total_in_type;
                                    $('#scan-total').text(totalPosts);
                                }
    
                                if (data.results && data.results.length > 0) {
                                    data.results.forEach(function(item) {
                                        processedPosts++;
                                        scanResults[item.id] = item; // Store for modal
                                        renderRow(item);
                                    });
                                    
                                    $('#scan-count').text(processedPosts);
                                    var pct = totalPosts > 0 ? (processedPosts / totalPosts) * 100 : 5;
                                    $('#scan-progress-bar').css('width', pct + '%');
    
                                    if (data.has_more) {
                                        processBatch(typeIndex, offset + data.batch_size);
                                    } else {
                                        processBatch(typeIndex + 1, 0);
                                    }
                                } else {
                                     processBatch(typeIndex + 1, 0);
                                }
    
                            } else {
                                // PHP returned explicit error (e.g. permission or unexpected logic)
                                // Do NOT abort. Log and continue.
                                console.warn('Batch Error: ' + (response.data.message || 'Unknown'));
                                // Attempt to skip this batch (size 1)
                                processBatch(typeIndex, offset + 1);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Network/Server Error/Timeout at offset ' + offset + ': ' + error);
                            // Skip this batch (size 1) and continue.
                            setTimeout(function() {
                                processBatch(typeIndex, offset + 1);
                            }, 1000);
                        }
                    });
                }

                function renderRow(item) {
                    var statusIcon = item.valid ? '✅' : '❌';
                    var statusText = item.valid ? 'Valid' : 'Invalid';
                    
                    if (item.valid) validCount++;
                    else errorCount++;

                    var messages = '';
                    if (item.errors && item.errors.length) {
                        messages += '<div style="color:#d63638; margin-bottom:4px;"><strong>Errors:</strong><br>' + item.errors.join('<br>') + '</div>';
                    }
                    if (item.warnings && item.warnings.length) {
                        messages += '<div style="color:#dba617;"><strong>Warnings:</strong><br>' + item.warnings.join('<br>') + '</div>';
                    }
                    if (!messages) messages = '<span style="color:#ccc;">No issues</span>';

                    var hasWarnings = item.warnings && item.warnings.length > 0;
                    var actionBtn = (item.valid && !hasWarnings) ? 
                        `<a href="${item.permalink}" target="_blank" class="button button-small">View Page</a>` :
                        `<button class="button button-secondary chroma-open-bulk-fix" data-id="${item.id}">🔍 View & Fix</button>`;

                    var html = `
                        <tr>
                            <td>
                                <strong><a href="${item.edit_url}" target="_blank">${item.title}</a></strong>
                                <br><small><a href="${item.permalink}" target="_blank">${item.permalink}</a></small>
                            </td>
                            <td>${item.type}</td>
                            <td>${statusIcon} ${statusText}</td>
                            <td>${messages}</td>
                            <td>${actionBtn}</td>
                        </tr>
                    `;

                    if (!item.valid) {
                        $('#bulk-results-table tbody').prepend(html);
                    } else {
                        $('#bulk-results-table tbody').append(html);
                    }
                }

                function finishScan() {
                    isScanning = false;
                    $('#start-bulk-scan').prop('disabled', false).text('Start Full Site Scan');
                    $('#scan-progress-bar').css('width', '100%');
                    $('#bulk-scan-summary').show();
                    $('#error-count').text(errorCount);
                    $('#valid-count').text(validCount);
                    
                    // Add Fix All Button if errors OR warnings exist
                    $('#chroma-bulk-fix-all-btn').remove(); 
                    if (errorCount > 0) {
                        $('#bulk-scan-summary .notice').append(`
                            <div style="margin-top:10px; border-top:1px solid #ddd; padding-top:10px;">
                                <button id="chroma-bulk-fix-all-btn" class="button button-primary">
                                    ✨ Fix All Issues with AI (Errors + Warnings)
                                </button>
                                <span id="bulk-fix-progress" style="display:none; margin-left:10px; color:#666;">
                                    Processing: <span id="bulk-fix-current">0</span>/${errorCount}...
                                </span>
                            </div>
                        `);
                    }

                    if (errorCount === 0) {
                         alert('🎉 Great job! No validation errors found on the site.');
                    } else {
                         alert('Scan complete. Found ' + errorCount + ' pages with errors.');
                    }
                }

                // Bulk Fix All Handler
                $(document).on('click', '#chroma-bulk-fix-all-btn', function() {
                    if (!confirm('This will sequentially auto-repair all invalid pages (and warnings) using AI and SAVE the changes to the database. This process may take some time.\n\nAre you sure you want to proceed?')) {
                        return;
                    }

                    var $btn = $(this);
                    $btn.prop('disabled', true);
                    $('#bulk-fix-progress').show();
                    
                    // Build Queue (Errors OR Warnings)
                    var fixQueue = [];
                    $.each(scanResults, function(id, item) {
                        if (!item.valid || (item.warnings && item.warnings.length)) {
                            fixQueue.push(item);
                        }
                    });

                    var totalToFix = fixQueue.length;
                    
                    if (totalToFix === 0) {
                        alert('Nothing to fix!');
                        $btn.prop('disabled', false);
                        return;
                    }

                    var fixedSoFar = 0;

                    function processNextFix(index) {
                        if (index >= totalToFix) {
                            $btn.text('✅ All Fixed!');
                            alert('Batch Fix Complete! All ' + totalToFix + ' pages have been updated.');
                            return;
                        }

                        var item = fixQueue[index];
                        $('#bulk-fix-current').text(index + 1);

                        // Visual indicator on row
                        var $rowBtn = $('.chroma-open-bulk-fix[data-id="' + item.id + '"]');
                        $rowBtn.text('⏳ Fixing...').prop('disabled', true);

                        // 1. Generate Fix
                        var schemaJson = item.schema && item.schema.length ? item.schema[0] : '';
                        if (item.schema.length > 1) schemaJson = item.schema.join('\n\n');
                        
                        var allIssues = (item.errors || []).concat(item.warnings || []);

                        $.post(ajaxurl, {
                            action: 'chroma_fix_schema_with_ai',
                            nonce: chroma_fix_nonce,
                            schema: schemaJson,
                            errors: allIssues, // Send combined issues
                        }, function(res1) {
                            if (res1.success) {
                                var fixedSchema = res1.data.fixed_schema;

                                // 2. Apply Fix
                                $.post(ajaxurl, {
                                    action: 'chroma_apply_schema_fix',
                                    nonce: '<?php echo wp_create_nonce('chroma_seo_dashboard_nonce'); ?>', // specific nonce
                                    post_id: item.id,
                                    schema: fixedSchema
                                }, function(res2) {
                                    if (res2.success) {
                                        // Update UI
                                        fixedSoFar++;
                                        $rowBtn.replaceWith('<span class="chroma-badge chroma-badge-manual">✅ Fixed</span>');
                                        // Process Next
                                        processNextFix(index + 1);
                                    } else {
                                        $rowBtn.text('❌ Save Failed').prop('disabled', false);
                                        console.error('Save failed for ' + item.id, res2);
                                        processNextFix(index + 1); // Continue anyway
                                    }
                                });
                            } else {
                                $rowBtn.text('❌ AI Failed').prop('disabled', false);
                                console.error('AI Fix failed for ' + item.id, res1);
                                processNextFix(index + 1); // Continue anyway
                            }
                        }).fail(function() {
                            $rowBtn.text('❌ Net Error').prop('disabled', false);
                            processNextFix(index + 1);
                        });
                    }

                    // Start
                    processNextFix(0);
                });

                // Modal Logic
                var $modal = $('#chroma-bulk-modal');
                var currentSchemaData = null;

                $(document).on('click', '.chroma-open-bulk-fix', function(e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    var data = scanResults[id];
                    
                    if (!data) return;
                    currentSchemaData = data;

                    // Populate Modal
                    var schemaJson = data.schema && data.schema.length ? data.schema[0] : '';
                    if (data.schema.length > 1) {
                        schemaJson = data.schema.join('\n\n// NEXT SCHEMA BLOCK //\n\n');
                    }
                    
                    $('#bulk-schema-viewer').val(schemaJson || '// No Schema Found');
                    
                    var reportHtml = '';
                    if (data.errors && data.errors.length) {
                        reportHtml += '<h4 style="color:#d63638; margin-top:0;">❌ Errors</h4><ul style="color:#d63638; list-style:disc; padding-left:20px;">';
                        data.errors.forEach(e => reportHtml += `<li>${e}</li>`);
                        reportHtml += '</ul>';
                    }
                    if (data.warnings && data.warnings.length) {
                        reportHtml += '<h4 style="color:#dba617;">⚠️ Warnings</h4><ul style="color:#dba617; list-style:disc; padding-left:20px;">';
                        data.warnings.forEach(w => reportHtml += `<li>${w}</li>`);
                        reportHtml += '</ul>';
                    }
                    $('#bulk-error-report').html(reportHtml);
                    
                    // Reset Fix UI
                    $('#bulk-fix-result').hide();
                    $('#bulk-fix-btn').prop('disabled', false).text('✨ Auto-Fix with AI');
                    
                    $modal.show();
                });

                $('#chroma-bulk-close').on('click', function() {
                    $modal.hide();
                });
                
                // Fix Handler (Step 1: Generate Proposal)
                $('#bulk-fix-btn').on('click', function() {
                     if (!currentSchemaData || !currentSchemaData.schema || !currentSchemaData.schema.length) {
                         alert('No schema to fix!');
                         return;
                     }
                     
                     var btn = $(this);
                     btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Generating Proposal...');
                     
                     // Reset previous results
                     $('#bulk-fix-result').hide();
                     $('#bulk-apply-btn').hide();
                     
                     var allSchemas = currentSchemaData.schema;
                     var allIssues = (currentSchemaData.errors || []).concat(currentSchemaData.warnings || []);
                     
                     $.post(ajaxurl, {
                        action: 'chroma_fix_schema_with_ai',
                        nonce: '<?php echo wp_create_nonce('chroma_schema_inspector_nonce'); ?>',
                        schemas: allSchemas,
                        errors: allIssues
                     }, function(response) {
                        if (response.success) {
                            var fixedSchemas = response.data.fixed_schemas;
                            var combinedJson = '';
                            
                            // Combine if array, or just use string
                            if (Array.isArray(fixedSchemas)) {
                                combinedJson = fixedSchemas.join('\n\n');
                            } else {
                                combinedJson = fixedSchemas;
                            }
                            
                            // Show Proposal
                            $('#bulk-fixed-schema').val(combinedJson);
                            $('#bulk-fix-result').show();
                            $('#bulk-apply-btn').show();
                            
                            btn.prop('disabled', false).text('✨ Regenerate AI Proposal');
                            
                        } else {
                            btn.prop('disabled', false).text('✨ Generate AI Proposal');
                            alert('AI Generation Failed: ' + (response.data.message || 'Unknown error'));
                        }
                    }).fail(function() {
                        btn.prop('disabled', false).text('✨ Generate AI Proposal');
                        alert('Network Error during AI Reqest');
                    });
                });

                // Apply Handler (Step 2: Save Edited Schema)
                $('#bulk-apply-btn').on('click', function() {
                    var btn = $(this);
                    var editedSchema = $('#bulk-fixed-schema').val();
                    
                    if (!editedSchema.trim()) {
                        alert('Proposed schema is empty!');
                        return;
                    }

                    btn.prop('disabled', true).text('💾 Saving...');
                    
                    // We send the EDITED content as a single block (or array if we parse it, but server handles strings too)
                    // The server expects 'schemas' (array) or 'schema' (string). 
                    // Let's treat the textarea content as the final single output (since we merged duplicates).
                    
                    $.post(ajaxurl, {
                        action: 'chroma_apply_schema_fix',
                        nonce: '<?php echo wp_create_nonce('chroma_seo_dashboard_nonce'); ?>',
                        post_id: currentSchemaData.id,
                        schema: editedSchema // Send as single string 
                    }, function(applyResponse) {
                        btn.prop('disabled', false).text('💾 Apply Changes');
                        
                        if (applyResponse.success) {
                            alert('✅ Schema Saved Successfully!');
                            $modal.hide();
                            
                            // Update Table Row
                            var row = $('#bulk-results-table').find(`[data-id="${currentSchemaData.id}"]`).closest('tr');
                            row.find('td:eq(2)').html('✅ Valid (Fixed)');
                            
                            // Update stored data locally in case they open it again without rescanning
                            currentSchemaData.schema = [editedSchema];
                            currentSchemaData.valid = true;
                            currentSchemaData.errors = [];
                            currentSchemaData.warnings = [];
                            scanResults[currentSchemaData.id] = currentSchemaData;
                            
                        } else {
                            alert('Save failed: ' + (applyResponse.data.message || 'Unknown error'));
                        }
                    }).fail(function() {
                        btn.prop('disabled', false).text('💾 Apply Changes');
                        alert('Network Error during Save');
                    });
                });

                function log(msg) {
                    console.log('[Bulk Validator] ' + msg);
                }
            });
        </script>
        <?php
        // We need to make sure the nonce for fix_schema is available if it differs.
        // The fix_schema endpoint uses 'chroma_schema_inspector_nonce'.
        // So let's generate it here for safety.
        echo '<script>var chroma_fix_nonce = "' . wp_create_nonce('chroma_schema_inspector_nonce') . '";</script>';
    }

    /**
     * AJAX: Scan Schema Batch
     */
    public function ajax_scan_schema_batch()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_type = sanitize_text_field($_POST['post_type']);
        $offset = intval($_POST['offset']);
        $batch_size = 1; // Reduced to 1 to isolate crashes/heavy pages
        set_time_limit(120); // Give plenty of time for complex pages

        // Fetch Posts
        $args = [
            'post_type' => $post_type,
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'post_status' => 'publish',
            'orderby' => 'ID',
            'order' => 'DESC'
        ];

        $posts = get_posts($args);
        $total_in_type = wp_count_posts($post_type)->publish;

        $results = [];

        foreach ($posts as $post) {
            $pid = $post->ID;
            $permalink = get_permalink($pid);
            
            // Add cache busting to ensure we see the latest validation fixes
            $fetch_url = add_query_arg('chroma_nocache', time(), $permalink);
            
            // Fetch Live Page
            $response = wp_remote_get($fetch_url, [
                'timeout' => 15, 
                'sslverify' => false,
                'headers' => [
                   'Cache-Control' => 'no-cache, no-store, must-revalidate',
                   'Pragma' => 'no-cache',
                   'Expires' => '0'
                ]
            ]);
            $is_valid = false;
            $errors = [];
            $warnings = [];

            if (is_wp_error($response)) {
                $errors[] = 'HTTP Fetch Failed: ' . $response->get_error_message();
            } else {
                $body = wp_remote_retrieve_body($response);
                
                // Extract JSON-LD (Robust Regex)
                if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/s', $body, $matches)) {
                    $schemas = $matches[1];
                    $has_valid_schema = false;
                    
                    $type_counts = [];
                    
                    foreach ($schemas as $json) {
                        if (class_exists('Chroma_Schema_Validator')) {
                            $res = Chroma_Schema_Validator::validate_json_ld($json);
                            if ($res['valid']) {
                                $has_valid_schema = true;
                                
                                // Count types for duplicate detection
                                if (isset($res['parsed']['@type'])) {
                                    $t = $res['parsed']['@type'];
                                    if (is_array($t)) {
                                        foreach ($t as $subT) {
                                            if (!isset($type_counts[$subT])) $type_counts[$subT] = 0;
                                            $type_counts[$subT]++;
                                        }
                                    } else {
                                        if (!isset($type_counts[$t])) $type_counts[$t] = 0;
                                        $type_counts[$t]++;
                                    }
                                }
                            }
                            if (!$res['valid']) {
                                foreach ($res['errors'] as $e) $errors[] = $e;
                            }
                            foreach ($res['warnings'] as $w) $warnings[] = $w;
                        }
                    }

                    // Check for Duplicates (Google SEO Best Practices)
                    if (isset($type_counts['FAQPage']) && $type_counts['FAQPage'] > 1) {
                        $warnings[] = "Multiple FAQPage schemas found (" . $type_counts['FAQPage'] . "). Google recommends a single FAQPage per page.";
                    }
                    if (isset($type_counts['BreadcrumbList']) && $type_counts['BreadcrumbList'] > 1) {
                        $warnings[] = "Multiple BreadcrumbList schemas found (" . $type_counts['BreadcrumbList'] . "). This may cause search confusion.";
                    }
                    
                    if ($has_valid_schema && empty($errors)) {
                        $is_valid = true;
                    }
                } else {
                    // No schema found
                    $warnings[] = 'No JSON-LD schema found on page.';
                    // Not necessarily an error if not expected, but for us usually is.
                }
            }

            $results[] = [
                'id' => $pid,
                'title' => $post->post_title,
                'type' => $post_type,
                'permalink' => $permalink,
                'edit_url' => get_edit_post_link($pid),
                'valid' => $is_valid,
                'schema' => isset($schemas) ? $schemas : [], // Return raw schemas
                'errors' => array_unique($errors),
                'warnings' => array_unique($warnings)
            ];
        }

        wp_send_json_success([
            'results' => $results,
            'total_in_type' => intval($total_in_type),
            'offset' => $offset,
            'batch_size' => $batch_size,
            'has_more' => ($offset + $batch_size) < $total_in_type
        ]);
    }

    /**
     * AJAX: Apply Fixed Schema(s) to Post
     */
    public function ajax_apply_schema_fix()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        
        // Support both single schema and multiple schemas
        $schemas_array = $_POST['schemas'] ?? null;
        $single_schema = $_POST['schema'] ?? null;

        if (!$post_id) {
            wp_send_json_error(['message' => 'Missing post ID']);
        }

        if (empty($schemas_array) && empty($single_schema)) {
            wp_send_json_error(['message' => 'Missing schema data']);
        }

        // Handle multiple schemas
    if ($schemas_array && is_array($schemas_array)) {
        // Validate all schemas
        foreach ($schemas_array as $k => $schema) {
            $schema = wp_unslash($schema); // FIX: Remove WP slashes
            $decoded = json_decode($schema);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => 'Invalid JSON in one of the schemas: ' . json_last_error_msg()]);
            }
            // Update array with unslashed version for saving
            $schemas_array[$k] = $schema;
        }
        
        // Save as multiple script tags
        $combined = '';
        foreach ($schemas_array as $schema) {
            $combined .= '<script type="application/ld+json">' . $schema . '</script>' . "\n";
        }
        update_post_meta($post_id, '_chroma_schema_override', trim($combined));
            
            wp_send_json_success([
                'message' => 'All schemas applied successfully',
                'post_id' => $post_id,
                'count' => count($schemas_array)
            ]);
        } else {
            // Handle single schema (backward compatibility)
            $schema = wp_unslash($single_schema);
            $decoded = json_decode($schema);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(['message' => 'Invalid JSON: ' . json_last_error_msg()]);
            }

            update_post_meta($post_id, '_chroma_schema_override', $schema);

            wp_send_json_success([
                'message' => 'Schema applied successfully',
                'post_id' => $post_id
            ]);
        }
    }

}
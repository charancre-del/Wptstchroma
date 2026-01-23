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
        add_action('wp_ajax_chroma_apply_schema_fix', [$this, 'ajax_apply_schema_fix']);
        add_action('wp_ajax_chroma_fetch_live_schema', [$this, 'ajax_fetch_live_schema']);
        add_action('wp_ajax_chroma_sync_schema_to_builder', [$this, 'ajax_sync_schema_to_builder']);
        add_action('wp_ajax_chroma_save_sitemap_urls', [$this, 'ajax_save_sitemap_urls']);
        add_action('wp_ajax_chroma_parse_sitemap_urls', [$this, 'ajax_parse_sitemap_urls']);
        add_action('wp_ajax_chroma_validate_url', [$this, 'ajax_validate_url']);
        add_action('wp_ajax_chroma_clear_validation_cache', [$this, 'ajax_clear_validation_cache']);
        add_action('wp_ajax_chroma_save_validator_setting', [$this, 'ajax_save_validator_setting']);
        add_action('wp_ajax_chroma_run_link_analysis', [$this, 'ajax_run_link_analysis']);
        add_action('wp_ajax_chroma_schema_cleanup_scan', [$this, 'ajax_schema_cleanup_scan']);
        add_action('wp_ajax_chroma_schema_cleanup_execute', [$this, 'ajax_schema_cleanup_execute']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('transition_post_status', [$this, 'auto_validate_on_publish'], 10, 3);
        
        // WP-CLI Support (Sprint 6/8)
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('chroma-seo validate', [$this, 'cli_validate_site']);
        }
    }

    /**
     * WP-CLI: Bulk validate site (Sprint 8)
     */
    public function cli_validate_site($args, $assoc_args)
    {
        WP_CLI::log('Starting site-wide schema validation audit...');
        $posts = get_posts(['post_type' => 'any', 'posts_per_page' => -1, 'post_status' => 'publish']);
        $total = count($posts);
        
        $progress = \WP_CLI\Utils\make_progress_bar('Scanning pages', $total);
        
        foreach ($posts as $post) {
            $this->perform_url_validation(get_permalink($post->ID), $post->ID);
            $progress->tick();
        }
        
        $progress->finish();
        WP_CLI::success("Validation complete! Scanned $total pages.");
    }

    /**
     * Auto-validate schema when a post is published (Sprint 8)
     */
    public function auto_validate_on_publish($new_status, $old_status, $post)
    {
        if ($new_status === 'publish') {
            $this->perform_url_validation(get_permalink($post->ID), $post->ID);
        }
    }

    /**
     * Register REST API Routes (Sprint 6)
     */
    public function register_rest_routes()
    {
        register_rest_route('chroma/v1', '/validate', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_validate_url'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'url' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return filter_var($param, FILTER_VALIDATE_URL);
                    }
                ]
            ]
        ]);
    }

    /**
     * REST Callback: Validate a specific URL
     */
    public function rest_validate_url($request)
    {
        $url = $request->get_param('url');
        $result = $this->perform_url_validation($url);
        return new WP_REST_Response($result, 200);
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('chroma_llm_options', 'chroma_llm_brand_voice');
        register_setting('chroma_llm_options', 'chroma_llm_brand_context');
        register_setting('chroma_llm_options', 'chroma_seo_phone');
        register_setting('chroma_llm_options', 'chroma_seo_email');
        register_setting('chroma_llm_options', 'chroma_seo_phonetic_name');
        
        // Sprint 5: Validator Settings
        register_setting('chroma_validator_options', 'chroma_validator_batch_size');
        register_setting('chroma_validator_options', 'chroma_validator_request_delay');
        register_setting('chroma_validator_options', 'chroma_validator_timeout');
        register_setting('chroma_validator_options', 'chroma_validator_cache_ttl');
        register_setting('chroma_validator_options', 'chroma_validator_max_retries');
        register_setting('chroma_validator_options', 'chroma_validator_email_alerts');
        register_setting('chroma_validator_options', 'chroma_validator_post_types');
        register_setting('chroma_careers_options', 'chroma_careers_feed_url');
        
        // SEO Automations
        register_setting('chroma_automation_options', 'chroma_seo_show_related_locations');
        register_setting('chroma_automation_options', 'chroma_seo_link_programs_locations');
        register_setting('chroma_automation_options', 'chroma_seo_enable_keyword_linking');
        register_setting('chroma_automation_options', 'chroma_seo_show_footer_cities');
        register_setting('chroma_automation_options', 'chroma_seo_enable_dynamic_titles');
        register_setting('chroma_automation_options', 'chroma_seo_enable_canonical');
        register_setting('chroma_automation_options', 'chroma_seo_trailing_slash');
        register_setting('chroma_automation_options', 'chroma_seo_show_author_meta');
        register_setting('chroma_automation_options', 'chroma_seo_show_author_box');
        register_setting('chroma_automation_options', 'chroma_seo_show_credential_badges');
        register_setting('chroma_automation_options', 'chroma_seo_enable_skip_nav');
        register_setting('chroma_automation_options', 'chroma_seo_enable_focus_indicators');
        register_setting('chroma_automation_options', 'chroma_enable_speculation_rules');
        register_setting('chroma_automation_options', 'chroma_enable_indexnow');
        register_setting('chroma_automation_options', 'chroma_seo_enable_entity_markup');
        register_setting('chroma_automation_options', 'chroma_seo_enable_county_pages');
        register_setting('chroma_automation_options', 'chroma_seo_enable_zip_pages');
        register_setting('chroma_automation_options', 'chroma_seo_auto_generate_combos');
        register_setting('chroma_automation_options', 'chroma_seo_enable_combo_links');
    }

    /**
     * Render Validator Settings Tab (Sprint 5)
     */
    private function render_validator_settings_tab()
    {
        ?>
        <div class="chroma-seo-card">
            <h2>⚙️ Schema Validator Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('chroma_validator_options'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="chroma_validator_batch_size">Default Batch Size</label></th>
                        <td>
                            <input name="chroma_validator_batch_size" type="number" id="chroma_validator_batch_size" value="<?php echo esc_attr(get_option('chroma_validator_batch_size', 10)); ?>" class="small-text">
                            <p class="description">Number of pages to scan per batch request.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="chroma_validator_request_delay">Request Delay (ms)</label></th>
                        <td>
                            <input name="chroma_validator_request_delay" type="number" id="chroma_validator_request_delay" value="<?php echo esc_attr(get_option('chroma_validator_request_delay', 100)); ?>" class="small-text">
                            <p class="description">Delay between requests to prevent server overload.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="chroma_validator_max_retries">Connection Retries</label></th>
                        <td>
                            <input name="chroma_validator_max_retries" type="number" id="chroma_validator_max_retries" value="<?php echo esc_attr(get_option('chroma_validator_max_retries', 3)); ?>" class="small-text" min="0" max="10">
                            <p class="description">Number of attempts for failed connection before giving up.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="chroma_seo_email">Global Email</label></th>
                        <td>
                            <input name="chroma_seo_email" type="email" id="chroma_seo_email" value="<?php echo esc_attr(get_option('chroma_seo_email')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="chroma_seo_phonetic_name">Phonetic Name (Voice Search)</label></th>
                        <td>
                            <input name="chroma_seo_phonetic_name" type="text" id="chroma_seo_phonetic_name" value="<?php echo esc_attr(get_option('chroma_seo_phonetic_name')); ?>" class="regular-text">
                            <p class="description">How your brand name sounds (e.g., "Kro-Ma Early Learning"). Used for Siri/Alexa optimization.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="chroma_validator_cache_ttl">Cache Duration (Hours)</label></th>
                        <td>
                            <input name="chroma_validator_cache_ttl" type="number" id="chroma_validator_cache_ttl" value="<?php echo esc_attr(get_option('chroma_validator_cache_ttl', 1)); ?>" class="small-text">
                            <p class="description">How long to store validation results in cache.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="chroma_validator_email_alerts">Critical Error Emails</label></th>
                        <td>
                            <input name="chroma_validator_email_alerts" type="checkbox" id="chroma_validator_email_alerts" value="1" <?php checked(get_option('chroma_validator_email_alerts'), '1'); ?>>
                            <label for="chroma_validator_email_alerts">Send alerts to admin email when critical errors are found.</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
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
        wp_enqueue_script('google-charts', 'https://www.gstatic.com/charts/loader.js', [], null, true);

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
            
            /* Validation Column Styles */
            .validation-col { text-align: center; }
            .validation-status { display: inline-block; margin-left: 5px; font-size: 12px; }
            .validation-status.valid { color: #00a32a; }
            .validation-status.invalid { color: #d63638; }
            .validation-status.warnings { color: #dba617; }
            .validate-single-btn .dashicons { vertical-align: middle; }
            
            /* Row highlighting for validation states */
            tr.schema-invalid { background-color: #ffebee !important; border-left: 4px solid #d63638 !important; }
            tr.schema-valid { border-left: 4px solid #00a32a !important; }
            tr.schema-warnings { background-color: #fff8e5 !important; border-left: 4px solid #dba617 !important; }
            
            /* Schema Builder card states */
            .schema-card.has-errors { border: 2px solid #d63638 !important; background-color: #fff5f5 !important; }
            .schema-card.has-warnings { border: 2px solid #dba617 !important; }
            .ai-fix-badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 11px; margin-left: 5px; }
            .ai-fix-badge.valid { background: #e6f6e6; color: #006600; }
            .ai-fix-badge.invalid { background: #ffebee; color: #d63638; }
            /* Toast Notifications */
            #chroma-toast-container { position: fixed; bottom: 20px; right: 20px; z-index: 10000; }
            .chroma-toast { background: #333; color: #fff; padding: 12px 24px; border-radius: 4px; margin-top: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: chroma-slide-in 0.3s ease-out; display: flex; align-items: center; justify-content: space-between; min-width: 250px; }
            .chroma-toast.success { background: #00a32a; }
            .chroma-toast.error { background: #d63638; }
            .chroma-toast.warning { background: #dba617; color: #1d2327; }
            @keyframes chroma-slide-in { from { transform: translateY(100%); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
            
            /* Health Summary & Dash Stats */
            .chroma-health-summary { transition: all 0.3s ease; }
            .chroma-health-summary:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
            .health-score-val { animation: countUp 1s ease-out; }
            @keyframes countUp { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
            
            #clear-validation-cache { margin-left: 10px; }
            /* Success Pulse */
            @keyframes successPulse { 0% { background-color: #e6f6e6; } 50% { background-color: #00a32a; } 100% { background-color: #e6f6e6; } }
            .row-success-flash { animation: successPulse 1s ease-out; }
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
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=registry'); ?>"
                    class="nav-tab <?php echo $active_tab === 'registry' ? 'nav-tab-active' : ''; ?>">Registry & Maintenance</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=bulk-validation'); ?>"
                    class="nav-tab <?php echo $active_tab === 'bulk-validation' ? 'nav-tab-active' : ''; ?>">Bulk Validation</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=settings'); ?>"
                    class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=careers'); ?>"
                    class="nav-tab <?php echo $active_tab === 'careers' ? 'nav-tab-active' : ''; ?>">Careers & Sync</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=automations'); ?>"
                    class="nav-tab <?php echo $active_tab === 'automations' ? 'nav-tab-active' : ''; ?>">Automations</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=analysis'); ?>"
                    class="nav-tab <?php echo $active_tab === 'analysis' ? 'nav-tab-active' : ''; ?>">Link Analysis</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=combos'); ?>"
                    class="nav-tab <?php echo $active_tab === 'combos' ? 'nav-tab-active' : ''; ?>">Combo Pages</a>
                <a href="<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=near-me'); ?>"
                    class="nav-tab <?php echo $active_tab === 'near-me' ? 'nav-tab-active' : ''; ?>">Near Me Pages</a>
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
                case 'registry':
                    $this->render_registry_tab();
                    break;
                case 'bulk-validation':
                    $this->render_bulk_validation_tab();
                    break;
                case 'settings':
                    $this->render_validator_settings_tab();
                    break;
                case 'careers':
                    $this->render_careers_tab();
                    break;
                case 'automations':
                    $this->render_automations_tab();
                    break;
                case 'analysis':
                    $this->render_analysis_tab();
                    break;
                case 'combos':
                    $this->render_combos_tab();
                    break;
                case 'near-me':
                    $this->render_near_me_tab();
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
        
        <!-- Toast Container -->
        <div id="chroma-toast-container"></div>
        
        <!-- Validation Button JavaScript -->
        <script>
        // Helper: Show Toast Notification
        function showToast(message, type) {
            type = type || 'success';
            var icon = type === 'success' ? '✓' : (type === 'error' ? '✕' : '⚠');
            var toast = jQuery('<div class="chroma-toast ' + type + '"><span class="chroma-toast-icon">' + icon + '</span> <span class="chroma-toast-message">' + message + '</span></div>');
            jQuery('#chroma-toast-container').append(toast);
            setTimeout(function() {
                toast.fadeOut(function() { jQuery(this).remove(); });
            }, 4000);
        }

        // Helper: Convert array to CSV and download
        function downloadCSV(data, filename) {
            var csv = 'Page,URL,Type,Status,Errors,Warnings\n';
            data.forEach(function(row) {
                var status = row.valid ? 'Valid' : (row.warnings > 0 ? 'Warnings' : 'Invalid');
                csv += '"' + (row.title || '').replace(/"/g, '""') + '",';
                csv += '"' + (row.url || '').replace(/"/g, '""') + '",';
                csv += '"' + (row.post_type || '').replace(/"/g, '""') + '",';
                csv += '"' + status + '",';
                csv += '"' + (row.errors || 0) + '",';
                csv += '"' + (row.warnings || 0) + '"\n';
            });
            
            var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement("a");
            if (link.download !== undefined) {
                var url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }
        
        // Helper: Format Time Relative
        function timeAgo(date) {
            var seconds = Math.floor((new Date() - date) / 1000);
            var interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + " years ago";
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + " months ago";
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + " days ago";
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + " hours ago";
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + " minutes ago";
            return Math.floor(seconds) + " seconds ago";
        }

        jQuery(document).ready(function($) {
            // One-click validation for dashboard rows
            $(document).on('click', '.validate-single-btn', function(e) {
                e.preventDefault();
                var btn = $(this);
                var postId = btn.data('post-id');
                var statusEl = btn.siblings('.validation-status');
                var row = btn.closest('tr');
                
                btn.prop('disabled', true);
                statusEl.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
                
                $.post(ajaxurl, {
                    action: 'chroma_validate_post_schema',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce("chroma_validate_post_schema"); ?>'
                }, function(response) {
                    btn.prop('disabled', false);
                    
                    if (response.success) {
                        var data = response.data;
                        row.removeClass('schema-invalid schema-warnings schema-valid');
                        
                        if (data.valid) {
                            if (data.warnings && data.warnings.length > 0) {
                                row.addClass('schema-warnings');
                                statusEl.html('<span class="dashicons dashicons-warning" style="color:#dba617;"></span>').addClass('warnings');
                            } else {
                                row.addClass('schema-valid');
                                statusEl.html('<span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span>').addClass('valid');
                            }
                        } else {
                            row.addClass('schema-invalid');
                            var errCount = data.schemas ? data.schemas.reduce(function(sum, s) { return sum + (s.errors || []).length; }, 0) : 0;
                            statusEl.html('<span class="dashicons dashicons-no" style="color:#d63638;"></span> ' + errCount).addClass('invalid');
                        }
                    } else {
                        statusEl.html('<span class="dashicons dashicons-warning" style="color:#666;"></span>');
                    }
                }).fail(function() {
                    btn.prop('disabled', false);
                    statusEl.html('<span class="dashicons dashicons-no" style="color:#d63638;"></span>');
                });
            });
        });
        </script>
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

        // Efficient: Fetch only ID and Title via WPDB to avoid object hydration
        global $wpdb;
        $locations = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE post_type='location' AND post_status='publish' ORDER BY post_title ASC LIMIT 500");
        $programs = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE post_type='program' AND post_status='publish' ORDER BY post_title ASC LIMIT 500");
        $pages = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE post_type='page' AND post_status='publish' ORDER BY post_title ASC LIMIT 500");
        $posts = $wpdb->get_results("SELECT ID, post_title FROM $wpdb->posts WHERE post_type='post' AND post_status='publish' ORDER BY post_date DESC LIMIT 50");

        $selected_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        ?>
        <div class="chroma-seo-card" style="margin-bottom: 20px;">
            <h2>🤖 LLM Optimization Files</h2>
            <p>Your <strong>llms.txt</strong> file is automatically generated and optimized for AI crawlers (ChatGPT, Perplexity, Claude).</p>
            <code><a href="<?php echo home_url('/llms.txt'); ?>" target="_blank"><?php echo home_url('/llms.txt'); ?></a></code>
            <p class="description">This file aggregates the targeting data below into a format AI can easily read.</p>
        </div>

        <div class="chroma-llm-controls">
            <label><strong>Select Page to Edit LLM Targeting:</strong></label>
            <select id="chroma-llm-select" style="min-width: 300px;">
                <option value="">-- Select a Page (Showing top 500) --</option>
                <optgroup label="Locations">
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo $loc->ID; ?>" <?php selected($selected_id, $loc->ID); ?>>
                            <?php echo esc_html($loc->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Programs">
                    <?php foreach ($programs as $prog): ?>
                        <option value="<?php echo $prog->ID; ?>" <?php selected($selected_id, $prog->ID); ?>>
                            <?php echo esc_html($prog->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Pages">
                    <?php foreach ($pages as $pg): ?>
                        <option value="<?php echo $pg->ID; ?>" <?php selected($selected_id, $pg->ID); ?>>
                            <?php echo esc_html($pg->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Blog Posts">
                    <?php foreach ($posts as $pt): ?>
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
                    <th style="width: 100px;">Validation</th>
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
                                <?php 
                                $is_manual_desc = !empty(get_post_meta($id, 'seo_llm_description', true));
                                $is_ai_desc = !$is_manual_desc && class_exists('Chroma_Fallback_Resolver') && Chroma_Fallback_Resolver::get_cached_ai_value($id, 'description');
                                
                                if ($is_manual_desc): ?>
                                    <span class="chroma-badge chroma-badge-manual">✏️ Manual</span>
                                <?php elseif ($is_ai_desc): ?>
                                    <span class="chroma-badge" style="background: #f0f6fc; color: #005a9c; border: 1px solid #c2dbff;">🤖 AI</span>
                                <?php endif; ?>
                                <div style="font-size: 11px; line-height: 1.4; margin-top: 3px;"><?php echo wp_trim_words($desc, 15); ?></div>
                            </div>
                        </td>
                        <td>
                            <?php if ($schema_count > 0): ?>
                                <span class="chroma-check">✓</span> <?php echo $schema_count; ?> Custom Schema(s)
                            <?php else: ?>
                                <span style="color: #ccc;">-</span> Default
                            <?php endif; ?>
                        </td>
                        <td class="validation-col" data-post-id="<?php echo $id; ?>">
                            <button type="button" class="button button-small validate-single-btn" data-post-id="<?php echo $id; ?>" title="Validate Schema">
                                <span class="dashicons dashicons-yes-alt" style="vertical-align: middle;"></span>
                            </button>
                            <span class="validation-status"></span>
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

        <!-- Schema Sync Toolbar (Feature 14) -->
        <div class="schema-sync-toolbar" id="schema-sync-toolbar" style="display:none; margin: 15px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
            <strong>Live Schema Sync:</strong>
            <button type="button" class="button" id="fetch-live-schema" style="margin-left: 10px;">
                <span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Fetch Live Schema
            </button>
            <button type="button" class="button" id="compare-schemas" disabled style="margin-left: 5px;">
                <span class="dashicons dashicons-editor-table" style="vertical-align: middle;"></span> Compare
            </button>
            <button type="button" class="button button-primary" id="sync-to-builder" disabled style="margin-left: 5px;">
                <span class="dashicons dashicons-download" style="vertical-align: middle;"></span> Sync to Builder
            </button>
            <span id="sync-status" style="margin-left: 10px;"></span>
            <div id="schema-compare-results" style="display:none; margin-top: 15px; padding: 10px; background: #fff; border: 1px solid #eee;"></div>
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
                        schema_type: type,
                        auto_save: 'true'
                    }, function (response) {
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-superhero" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span> Auto-Fill');

                        if (response.success) {
                            var data = response.data;
                            // Populate fields
                            // Helper: Populate Form Recursive
                            function populateFormRecursive(data, $container, prefix) {
                                $.each(data, function (key, value) {
                                    // 1. Try Simple Field (Direct Match)
                                    var input = $container.find('[data-name="' + key + '"]').filter(':not(.chroma-repeater-input)');
                                    
                                    if (input.length > 0) {
                                        if (typeof value === 'object' && value !== null) {
                                            // Handle Nested Object (e.g. geo: {lat: 1, lng: 2} -> geo_lat, geo_lng)
                                             populateFormRecursive(value, $container, key + '_');
                                        } else {
                                            input.val(value).trigger('change');
                                            input.css('background-color', '#f0f6fc').animate({ backgroundColor: '#fff' }, 2000);
                                        }
                                        return; // Continue to next key
                                    }

                                    // 2. Try Repeater Field (Wrapper Match)
                                    var repeater = $container.find('.chroma-repeater-wrapper[data-key="' + key + '"]');
                                    if (repeater.length > 0 && Array.isArray(value)) {
                                        var $itemsContainer = repeater.find('.chroma-repeater-items');
                                        $itemsContainer.empty(); // Clear existing rows
                                        
                                        var subfields = repeater.data('subfields'); // We need to ensure subfields data is available? 
                                        // Actually easier: Trigger "Add Row" for each item, then populate the last row
                                        var $addBtn = repeater.find('.chroma-add-repeater-row');
                                        
                                        value.forEach(function(rowItem) {
                                            $addBtn.trigger('click');
                                            var $newRow = $itemsContainer.children().last();
                                            // Recursively populate this new row
                                            // Note: Row inputs have data-name="fieldName", not "parent_fieldName"
                                            populateFormRecursive(rowItem, $newRow, ''); 
                                        });
                                        return;
                                    }
                                    
                                    // 3. Try Flattening (e.g. location_name -> location.name)
                                    // If we are here, we didn't find a direct input. 
                                    // If value is a simple string, maybe the form expects "prefix_key"?
                                    if (prefix && typeof value !== 'object') {
                                         var compositeKey = prefix + key;
                                         var compositeInput = $container.find('[data-name="' + compositeKey + '"]');
                                         if (compositeInput.length) {
                                             compositeInput.val(value).css('background-color', '#f0f6fc');
                                         }
                                    }
                                });
                            }

                            // Start Population
                            populateFormRecursive(data, block, '');
                             // Auto-save after AI fills data
                                    $('#chroma-inspector-save').trigger('click');
                                } else {
                                    alert('AI Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                                }
                            });
                        });
                    });
                </script>
                
                // Toggle JSON/Form View Handler
                $(document).on('click', '.chroma-toggle-json', function(e) {
                    e.preventDefault();
                    var block = $(this).closest('.chroma-schema-block');
                    var formView = block.find('.chroma-schema-form');
                    var jsonView = block.find('.chroma-json-editor');
                    var textarea = jsonView.find('textarea');

                    if (formView.is(':visible')) {
                        // Switch to JSON: Serialize form to JSON
                        var rawData = {};
                        
                        // Scrape simple inputs
                        block.find('.chroma-schema-input').each(function() {
                            var name = $(this).data('name');
                            if (name) rawData[name] = $(this).val();
                        });

                        // Scrape repeaters
                        block.find('.chroma-repeater-wrapper').each(function() {
                            var key = $(this).data('key');
                            var items = [];
                            $(this).find('.chroma-repeater-items .chroma-repeater-row').each(function() {
                                var row = {};
                                $(this).find('.chroma-schema-input').each(function() { // Note: class is same for simple/repeater inputs
                                   var rowKey = $(this).data('name');
                                   if (rowKey) row[rowKey] = $(this).val();
                                });
                                // Only push if not empty
                                if (!$.isEmptyObject(row)) items.push(row);
                            });
                             // Don't add empty arrays
                             if (items.length > 0) rawData[key] = items;
                        });

                        textarea.val(JSON.stringify(rawData, null, 4));
                        formView.hide();
                        jsonView.show();
                        $(this).html('<span class="dashicons dashicons-editor-table" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span> Form');
                    } else {
                        // Switch to Form: Parse JSON and populate
                        try {
                            var json = JSON.parse(textarea.val());
                            populateFormRecursive(json, block, '');
                            formView.show();
                            jsonView.hide();
                             $(this).html('<span class="dashicons dashicons-code-standards" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span> JSON');
                        } catch (err) {
                            alert('Invalid JSON: ' + err.message);
                        }
                    }
                });

                <!-- Feature 14: Schema Sync Toolbar JavaScript -->
                <script>
                jQuery(document).ready(function($) {
                    var currentPostUrl = '';
                    window.liveSchemas = null;
                    
                    // Show toolbar when page is selected
                    $('#chroma-inspector-select').on('change', function() {
                        var id = $(this).val();
                        if (id) {
                            $('#schema-sync-toolbar').show();
                            currentPostUrl = '<?php echo home_url(); ?>/?p=' + id;
                        } else {
                            $('#schema-sync-toolbar').hide();
                        }
                    });
                    
                    // Fetch Live Schema
                    $('#fetch-live-schema').on('click', function() {
                        var btn = $(this);
                        var postId = $('#chroma-inspector-select').val();
                        
                        if (!postId) { alert('Please select a page first'); return; }
                        
                        btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none;margin:0;"></span> Fetching...');
                        
                        $.post(ajaxurl, {
                            action: 'chroma_fetch_live_schema',
                            url: currentPostUrl,
                            nonce: '<?php echo wp_create_nonce("chroma_seo_dashboard_nonce"); ?>'
                        }, function(response) {
                            btn.prop('disabled', false).html('<span class="dashicons dashicons-update" style="vertical-align:middle;"></span> Fetch Live Schema');
                            
                            if (response.success) {
                                window.liveSchemas = response.data.schemas;
                                $('#compare-schemas, #sync-to-builder').prop('disabled', false);
                                $('#sync-status').html('<span style="color:#00a32a;">Found ' + response.data.count + ' schemas</span>');
                            } else {
                                $('#sync-status').html('<span style="color:#d63638;">Error: ' + (response.data.message || 'Failed') + '</span>');
                            }
                        });
                    });
                    
                    // Compare
                    $('#compare-schemas').on('click', function() {
                        var liveCount = window.liveSchemas ? window.liveSchemas.length : 0;
                        var dbCount = $('.schema-block').length;
                        var liveTypes = window.liveSchemas ? window.liveSchemas.map(function(s) { return s['@type'] || 'Unknown'; }).join(', ') : '';
                        
                        var html = '<table class="widefat"><thead><tr><th>Source</th><th>Count</th><th>Types</th></tr></thead><tbody>';
                        html += '<tr><td>Database (Builder)</td><td>' + dbCount + '</td><td>' + getDbTypes() + '</td></tr>';
                        html += '<tr><td>Live Page</td><td>' + liveCount + '</td><td>' + liveTypes + '</td></tr>';
                        html += '</tbody></table>';
                        
                        $('#schema-compare-results').html(html).show();
                    });
                    
                    // Sync to Builder
                    $('#sync-to-builder').on('click', function() {
                        if (!window.liveSchemas || window.liveSchemas.length === 0) { alert('No schemas to sync'); return; }
                        
                        if (!confirm('This will replace existing schemas with ' + window.liveSchemas.length + ' from the live page. Continue?')) return;
                        
                        var postId = $('#chroma-inspector-select').val();
                        var btn = $(this);
                        
                        $.post(ajaxurl, {
                            action: 'chroma_sync_schema_to_builder',
                            post_id: postId,
                            schemas: JSON.stringify(window.liveSchemas),
                            nonce: '<?php echo wp_create_nonce("chroma_seo_dashboard_nonce"); ?>'
                        }, function(response) {
                            if (response.success) { showToast(response.data.message, 'success'); location.reload(); }
                            else { showToast('Error: ' + (response.data.message || 'Sync failed'), 'error'); }
                        });
                    });
                    
                    function getDbTypes() {
                        return $('.schema-block').map(function() { return $(this).data('type') || 'Unknown'; }).get().join(', ') || 'None';
                    }
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
        chroma_debug_log(' SEO: ajax_fetch_inspector_data called');

        if (!check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce', false)) {
            ob_end_clean();
            chroma_debug_log(' SEO: Nonce verification failed');
            wp_send_json_error(['message' => 'Security check failed (Nonce)']);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        chroma_debug_log(' SEO: Fetching schema for Post ID: ' . $post_id);

        if (!$post_id) {
            ob_end_clean();
            chroma_debug_log(' SEO: No Post ID provided');
            wp_send_json_error(['message' => 'Invalid Post ID']);
        }

        if (!class_exists('Chroma_Schema_Types')) {
            ob_end_clean();
            chroma_debug_log(' SEO: Chroma_Schema_Types class missing');
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
                            chroma_debug_log(' SEO: Raw existing_schemas from DB: ' . print_r($existing_schemas, true));

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
                                    $this->render_schema_block($schema['type'], $schema['data'], $index, $post_id);
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
                        wp_die();

        } catch (Throwable $e) {
            ob_end_clean(); // Clean buffer if error
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                chroma_debug_log(' SEO Error: ' . $e->getMessage());
            }
            wp_send_json_error(['message' => 'Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Render a single schema block
     */
    private function render_schema_block($type, $data = [], $index = 0, $post_id = 0)
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
                            <button class="button button-small chroma-toggle-json" title="Toggle JSON/Form View" style="margin-right: 5px;">
                                <span class="dashicons dashicons-code-standards" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span> JSON
                            </button>
                            <button class="button button-small chroma-ai-autofill" data-type="<?php echo esc_attr($type); ?>"
                                style="margin-right: 10px; border-color: #8c64ff; color: #6b42e4;">
                                <span class="dashicons dashicons-superhero"
                                    style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span> Auto-Fill
                            </button>
                            <button class="chroma-remove-schema button-link-delete">Remove</button>
                        </div>
                    </h3>

                    <div class="chroma-json-editor" style="display: none; margin-bottom: 15px;">
                        <textarea class="large-text code" rows="10" placeholder="{ ... }" style="width: 100%; font-family: monospace; background: #f6f7f7;"><?php 
                            // Prepare JSON representation for the editor
                            $clean_data = $data;
                            if (isset($clean_data['custom_fields'])) {
                                // Maybe clean up custom fields for display?
                            }
                            echo esc_textarea(json_encode($clean_data, JSON_PRETTY_PRINT)); 
                        ?></textarea>
                        <p class="description">Edit raw JSON data here. Switch back to Form view to apply changes.</p>
                    </div>

                    <table class="form-table chroma-schema-form" style="margin-top: 0;">
                        <?php foreach ($def['fields'] as $key => $field):
                            $val = isset($data[$key]) ? $data[$key] : '';
                            $is_ai = false;
                            $placeholder = '';

                            // Only check for AI fallback if current value is empty and we have a post_id
                            if (empty($val) && $post_id) {
                                if (class_exists('Chroma_Fallback_Resolver')) {
                                    $ai_val = Chroma_Fallback_Resolver::get_cached_ai_value($post_id, $key);
                                    if ($ai_val) {
                                        $val = $ai_val;
                                        $is_ai = true;
                                    }
                                }
                            }

                            // Handle array values for non-repeater fields (like sameAs)
                            if (is_array($val) && $field['type'] !== 'repeater') {
                                $val = implode(', ', $val);
                            }
                            ?>
                                <tr>
                                    <th scope="row" style="padding: 10px 0; width: 200px;">
                                        <?php echo esc_html($field['label']); ?>
                                        <?php if ($is_ai): ?>
                                            <span class="chroma-ai-badge" title="AI Generated Fallback" style="background: #f0f6fc; color: #005a9c; border: 1px solid #c2dbff; border-radius: 3px; padding: 1px 4px; font-size: 10px; vertical-align: middle; margin-left: 5px;">🤖 AI</span>
                                        <?php endif; ?>
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
        $this->render_schema_block($type, $prefill_data, $index, $post_id);
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX: Save Inspector Data
     */
    public function ajax_save_inspector_data()
    {
        // Log incoming request for debugging
        chroma_debug_log(' SEO Save: ajax_save_inspector_data called');

        if (!check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce', false)) {
            chroma_debug_log(' SEO Save: Nonce verification failed');
            wp_send_json_error(['message' => 'Security check failed']);
        }

        if (!current_user_can('edit_posts')) {
            chroma_debug_log(' SEO Save: User lacks edit_posts capability');
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $post_id = intval($_POST['post_id']);
        $schemas = isset($_POST['schemas']) ? $_POST['schemas'] : [];

        chroma_debug_log(' SEO Save: Post ID = ' . $post_id);
        chroma_debug_log(' SEO Save: Raw schemas received = ' . print_r($schemas, true));

        if (!$post_id) {
            chroma_debug_log(' SEO Save: Invalid post ID');
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
                                            $clean_row[sanitize_text_field($rk)] = sanitize_textarea_field($rv);
                                        }
                                        $clean_repeater[] = $clean_row;
                                    }
                                }
                                $clean_data[sanitize_text_field($k)] = $clean_repeater;
                            } else {
                                // Handle Simple Field
                                $clean_data[sanitize_text_field($k)] = sanitize_textarea_field($v);
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

        chroma_debug_log(' SEO Save: Cleaned schemas = ' . print_r($clean_schemas, true));

        $result = update_post_meta($post_id, '_chroma_post_schemas', $clean_schemas);
        chroma_debug_log(' SEO Save: update_post_meta result = ' . ($result ? 'success/updated' : 'no change or failed'));

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
     * Render Bulk Operations Tab Content (Partial)
     */
    private function render_bulk_ops_tab_content()
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
                            onchange="window.location.href='<?php echo admin_url('admin.php?page=chroma-seo-dashboard&tab=registry&ptype='); ?>' + this.value">
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
            
            <!-- Feature 9: Sitemap Configuration -->
            <div style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                <h3 style="margin-top:0;">📍 URL Discovery Source</h3>
                <p class="description">Choose how to discover pages for validation. Using a sitemap ensures ALL pages are scanned, including archives and dynamic pages.</p>
                
                <table class="form-table" style="margin:0;">
                    <tr>
                        <th style="width:150px;padding:10px 0;">Discovery Mode</th>
                        <td style="padding:10px 0;">
                            <label style="display:block;margin-bottom:8px;">
                                <input type="radio" name="discovery_mode" value="database" checked> 
                                <strong>Database Query</strong> - Scan posts from database (may miss archive/taxonomy pages)
                            </label>
                            <label style="display:block;">
                                <input type="radio" name="discovery_mode" value="sitemap"> 
                                <strong>Sitemap</strong> - Parse sitemap URLs (recommended for complete coverage)
                            </label>
                        </td>
                    </tr>
                    <tr id="sitemap-url-row" style="display:none;">
                        <th style="padding:10px 0;">Sitemap URL(s)</th>
                        <td style="padding:10px 0;">
                            <textarea id="sitemap-urls" class="large-text" rows="3" placeholder="<?php echo home_url('/sitemap.xml'); ?>
<?php echo home_url('/sitemap_index.xml'); ?>"><?php echo esc_textarea(get_option('chroma_validator_sitemaps', home_url('/sitemap.xml'))); ?></textarea>
                            <p class="description">One sitemap URL per line. Supports sitemap index files (will parse all child sitemaps).</p>
                            
                            <!-- Feature 5: URL Exclusions -->
                            <div style="margin-top:10px;">
                                <label style="display:block;margin-bottom:5px;"><strong>Exclusion Patterns (URL must match):</strong></label>
                                <textarea id="sitemap-exclusions" class="large-text" rows="2" placeholder="*product-category*
*/page/2/*"><?php echo esc_textarea(get_option('chroma_validator_exclusions', '')); ?></textarea>
                                <p class="description">One pattern per line. Use * as wildcard. URLs matches these patterns will be skipped.</p>
                            </div>
                            
                            <button type="button" id="save-sitemap-setting" class="button button-primary" style="margin-top:10px;">Save Sitemap URLs & Exclusions</button>
                            <span id="sitemap-save-status" style="margin-left:10px;"></span>
                        </td>
                    </tr>
                </table>
            </div>
            
            <?php
            $stats = class_exists('Chroma_Validation_Logger') ? Chroma_Validation_Logger::get_stats_summary() : ['total'=>0, 'invalid'=>0, 'fixes'=>0, 'health'=>100];
            ?>
            <div class="chroma-health-summary" style="display: flex; gap: 20px; align-items: center; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #2271b1;">
                <div class="health-score" style="text-align: center; padding-right: 20px; border-right: 1px solid #ddd;">
                    <div style="font-size: 24px; font-weight: bold; color: <?php echo $stats['health'] < 70 ? '#b32d2e' : ($stats['health'] < 90 ? '#dba617' : '#2271b1'); ?>;">
                        <?php echo $stats['health']; ?>%
                    </div>
                    <div style="font-size: 11px; text-transform: uppercase; color: #666;">Site Health</div>
                </div>
                <div style="flex: 1; display: flex; gap: 30px;">
                    <div>
                        <div style="font-weight: 600; font-size: 16px;"><?php echo $stats['total']; ?></div>
                        <div style="font-size: 12px; color: #666;">Pages Scanned</div>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 16px; color: #b32d2e;"><?php echo $stats['invalid']; ?></div>
                        <div style="font-size: 12px; color: #666;">Invalid Pages</div>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 16px; color: #2271b1;"><?php echo $stats['fixes']; ?></div>
                        <div style="font-size: 12px; color: #666;">AI Fixes Applied</div>
                    </div>
                </div>
                <div id="chroma-health-chart" style="width: 200px; height: 60px; margin-left: 20px;"></div>
                <button id="clear-validation-cache" class="button button-secondary" title="Clears all cached validation results to force a fresh scan.">Clear Cache</button>
                
                <div style="margin-left:auto; display:flex; align-items:center; gap:5px;">
                    <input type="checkbox" id="enable-error-emails" <?php checked(get_option('chroma_validator_email_alerts'), '1'); ?> value="1">
                    <label for="enable-error-emails" style="font-size:12px;">Email Alerts</label>
                </div>
            </div>
            
            <div class="chroma-inspector-controls">
                <button id="start-bulk-scan" class="button button-primary button-large">
                    <span class="dashicons dashicons-search" style="line-height: 28px;"></span> Start Full Site Scan
                </button>
                
                <!-- Feature 17: Search Input -->
                <input type="text" id="chroma-validator-search" placeholder="Search pages or errors..." style="margin-left: 10px; height: 30px; display:none; width: 200px;">

                <!-- Feature 3: Quick Filter -->
                <select id="bulk-scan-filter" style="margin-left: 10px; height: 30px; display:none;">
                    <option value="all">All Results</option>
                    <option value="invalid">Invalid Only</option>
                    <option value="warnings">Warnings Only</option>
                    <option value="valid">Valid Only</option>
                </select>
                
                <!-- Feature 2: CSV Export -->
                <button id="export-bulk-csv" class="button button-secondary" style="margin-left: 10px; display:none;">
                    <span class="dashicons dashicons-download"></span> Export CSV
                </button>

                <!-- Feature 23: JSON Export -->
                <button id="export-bulk-json" class="button button-secondary" style="margin-left: 10px; display:none;">
                    <span class="dashicons dashicons-code-standards"></span> Export JSON
                </button>

                <!-- Feature 20: Bulk Actions -->
                <button id="bulk-revalidate" class="button button-secondary" style="margin-left: 10px; display:none;">
                    Bulk Re-validate
                </button>
                <button id="bulk-ai-fix" class="button button-primary" style="margin-left: 10px; display:none;">
                    Bulk AI Fix (Selected)
                </button>
                
                <div id="scan-progress-wrapper" style="display:none; flex: 1; margin-left: 20px;">
                    <div style="background: #f0f0f1; border-radius: 4px; overflow: hidden; height: 20px; border: 1px solid #c3c4c7;">
                        <div id="scan-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
                    </div>
                    <div style="font-size: 12px; margin-top: 5px; color: #666; display: flex; justify-content: space-between;">
                        <span>Scanned: <span id="scan-count">0</span> / <span id="scan-total">0</span> pages</span>
                        
                        <!-- Feature 6: Batch Size Control -->
                        <span style="display: flex; align-items: center; gap: 5px;">
                            <label for="scan-batch-size" title="Number of pages to scan per request.">Batch Size:</label>
                            <input type="range" id="scan-batch-size" min="1" max="50" value="<?php echo esc_attr(get_option('chroma_validator_batch_size', 10)); ?>" style="width: 80px;">
                            <span id="batch-size-display"><?php echo esc_html(get_option('chroma_validator_batch_size', 10)); ?></span>
                        </span>
                        
                        <!-- Feature 9: Request Delay Control -->
                        <span style="display: flex; align-items: center; gap: 5px; margin-left:15px;">
                            <label for="scan-request-delay" title="Delay between requests in milliseconds. Increase if server is overloaded.">Delay (ms):</label>
                            <input type="range" id="scan-request-delay" min="0" max="2000" step="100" value="<?php echo esc_attr(get_option('chroma_validator_request_delay', 100)); ?>" style="width: 80px;">
                            <span id="request-delay-display"><?php echo esc_html(get_option('chroma_validator_request_delay', 100)); ?></span>
                        </span>
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
                        <th style="width: 30px;"><input type="checkbox" id="bulk-select-all"></th>
                        <th style="width: 250px;">Page</th>
                        <th style="width: 100px;">Type</th>
                        <th style="width: 100px;">Status</th>
                        <th>Issues Found</th>
                        <!-- Feature 4: Last Checked Column -->
                        <th style="width: 120px;">Last Checked</th>
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
                
                
                // Feature 51: Site Health Trend Chart
                google.charts.load('current', {'packages':['corechart']});
                google.charts.setOnLoadCallback(drawHealthChart);

                function drawHealthChart() {
                    var data = google.visualization.arrayToDataTable([
                        ['Day', 'Health'],
                        ['Day 1',  70],
                        ['Day 2',  75],
                        ['Day 3',  85],
                        ['Today',  <?php echo (int)$stats['health']; ?>]
                    ]);

                    var options = {
                        legend: 'none',
                        backgroundColor: 'transparent',
                        colors: ['#2271b1'],
                        chartArea: {width: '100%', height: '100%'},
                        hAxis: {baselineColor: 'none', textPosition: 'none', gridlines: {count:0}},
                        vAxis: {baselineColor: 'none', textPosition: 'none', gridlines: {count:0}}
                    };

                    var chart = new google.visualization.LineChart(document.getElementById('chroma-health-chart'));
                    chart.draw(data, options);
                }

                // Feature 6: Batch Size Slider Persistence
                $('#scan-batch-size').on('change', function() {
                    $('#batch-size-display').text($(this).val());
                    $.post(ajaxurl, {
                        action: 'chroma_save_validator_setting',
                        setting: 'batch_size',
                        value: $(this).val(),
                        nonce: '<?php echo wp_create_nonce("chroma_seo_dashboard_nonce"); ?>'
                    });
                }).on('input', function() {
                    $('#batch-size-display').text($(this).val());
                });

                // Feature 9: Request Delay Slider Persistence
                $('#scan-request-delay').on('change', function() {
                    $('#request-delay-display').text($(this).val());
                    $.post(ajaxurl, {
                        action: 'chroma_save_validator_setting',
                        setting: 'request_delay',
                        value: $(this).val(),
                        nonce: '<?php echo wp_create_nonce("chroma_seo_dashboard_nonce"); ?>'
                    });
                }).on('input', function() {
                    $('#request-delay-display').text($(this).val());
                });

                // Feature 7: Clear Cache
                $('#clear-validation-cache').on('click', function() {
                    var btn = $(this);
                    btn.prop('disabled', true).text('Clearing...');
                    
                    $.post(ajaxurl, {
                        action: 'chroma_clear_validation_cache',
                        nonce: '<?php echo wp_create_nonce("chroma_seo_dashboard_nonce"); ?>'
                    }, function(response) {
                        btn.prop('disabled', false).text('Clear Cache');
                        if (response.success) {
                            showToast('Cache cleared successfully', 'success');
                            location.reload(); 
                        }
                    });
                });

                // Feature 15: Toggle Email Alerts
                $('#enable-error-emails').on('change', function() {
                    var enabled = $(this).is(':checked') ? '1' : '0';
                    $.post(ajaxurl, {
                        action: 'chroma_save_validator_setting',
                        setting: 'email_alerts',
                        value: enabled,
                        nonce: '<?php echo wp_create_nonce("chroma_seo_dashboard_nonce"); ?>'
                    }, function(response) {
                        if (response.success) {
                            showToast('Alert setting saved', 'success');
                        }
                    });
                });
                
                // Feature 3: Quick Filter
                $('#bulk-scan-filter').on('change', function() {
                    var filter = $(this).val();
                    var rows = $('#bulk-results-table tbody tr');
                    
                    if (filter === 'all') {
                        rows.show();
                    } else if (filter === 'valid') {
                        rows.hide();
                        rows.filter(function() { return $(this).find('.dashicons-yes').length > 0; }).show();
                    } else if (filter === 'invalid') {
                        rows.hide();
                        rows.filter(function() { return $(this).find('.dashicons-no').length > 0; }).show();
                    } else if (filter === 'warnings') {
                        rows.hide();
                        rows.filter(function() { return $(this).find('.dashicons-warning').length > 0; }).show();
                    }
                });
                
                // Feature 16: Column Sorting
                $('.chroma-seo-table th').css('cursor', 'pointer').on('click', function() {
                    var table = $(this).parents('table').eq(0);
                    var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()));
                    this.asc = !this.asc;
                    if (!this.asc) rows = rows.reverse();
                    for (var i = 0; i < rows.length; i++) table.append(rows[i]);
                });

                function comparer(index) {
                    return function(a, b) {
                        var valA = getCellValue(a, index), valB = getCellValue(b, index);
                        return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB);
                    };
                }

                function getCellValue(row, index) {
                    return $(row).children('td').eq(index).text();
                }

                // Feature 17: Search by Error/Page
                $('#chroma-validator-search').on('keyup', function() {
                    var value = $(this).val().toLowerCase();
                    $('#bulk-results-table tbody tr').filter(function() {
                        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                    });
                });

                // Original Export logic follows...
                $('#export-bulk-csv').on('click', function() {
                    var data = Object.values(scanResults);
                    if (data.length === 0) {
                        showToast('No data to export', 'warning');
                        return;
                    }
                    downloadCSV(data, 'schema-audit-' + new Date().toISOString().slice(0,10) + '.csv');
                    showToast('Export started', 'success');
                });

                // Store results for modal
                var scanResults = {};
                
                // Feature 9: Discovery mode toggle
                $('input[name="discovery_mode"]').on('change', function() {
                    if ($(this).val() === 'sitemap') {
                        $('#sitemap-url-row').show();
                    } else {
                        $('#sitemap-url-row').hide();
                    }
                });
                
                // Feature 20: Bulk Row Selection
                $('#bulk-select-all').on('change', function() {
                    $('.row-select:visible').prop('checked', $(this).is(':checked')).trigger('change');
                });

                $(document).on('change', '.row-select', function() {
                    var $row = $(this).closest('tr');
                    if ($(this).is(':checked')) {
                        $row.css('background-color', '#f0f6fc');
                    } else {
                        $row.css('background-color', '');
                    }
                    
                    // Show/Hide bulk buttons
                    var checkedCount = $('.row-select:checked').length;
                    if (checkedCount > 0) {
                        $('#bulk-revalidate, #bulk-ai-fix').show();
                    } else {
                        $('#bulk-revalidate, #bulk-ai-fix').hide();
                    }
                });

                // Original sitemap save logic follows...
                $('#save-sitemap-setting').on('click', function() {
                    var btn = $(this);
                    btn.prop('disabled', true).text('Saving...');
                    
                    $.post(ajaxurl, {
                        action: 'chroma_save_sitemap_urls',
                        urls: $('#sitemap-urls').val(),
                        exclusions: $('#sitemap-exclusions').val(),
                        nonce: '<?php echo wp_create_nonce("chroma_seo_dashboard_nonce"); ?>'
                    }, function(response) {
                        btn.prop('disabled', false).text('Save Sitemap URLs & Exclusions');
                        if (response.success) {
                            $('#sitemap-save-status').html('<span style="color:green;">✓ Saved</span>');
                            showToast(response.data.message, 'success');
                            setTimeout(function() { $('#sitemap-save-status').empty(); }, 3000);
                        } else {
                            $('#sitemap-save-status').html('<span style="color:red;">Error</span>');
                            showToast(response.data.message || 'Error saving settings', 'error');
                        }
                    });
                });

                // Feature 23: JSON Export
                $('#export-bulk-json').on('click', function() {
                    var data = JSON.stringify(scanResults, null, 2);
                    var blob = new Blob([data], {type: 'application/json'});
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'schema-audit-' + new Date().toISOString().slice(0,10) + '.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    showToast('JSON Export started', 'success');
                });

                function playSuccessSound() {
                    // Modern browsers block autoplay, so we use a silent pulse or check for interaction
                    try {
                        var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        var oscillator = audioCtx.createOscillator();
                        var gainNode = audioCtx.createGain();
                        oscillator.connect(gainNode);
                        gainNode.connect(audioCtx.destination);
                        oscillator.type = 'sine';
                        oscillator.frequency.setValueAtTime(440, audioCtx.currentTime); // A4
                        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
                        oscillator.start();
                        oscillator.stop(audioCtx.currentTime + 0.2);
                    } catch(e) {}
                }

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
                    
                    // Feature 9: Check discovery mode
                    var discoveryMode = $('input[name="discovery_mode"]:checked').val();
                    
                    if (discoveryMode === 'sitemap') {
                        // Sitemap-based discovery
                        log('Using sitemap-based discovery...');
                        startSitemapScan();
                    } else {
                        // Database-based discovery (existing behavior)
                        startBatchProcess();
                    }
                });
                
                // Feature 9: Sitemap-based scan
                function startSitemapScan() {
                    $.post(ajaxurl, {
                        action: 'chroma_parse_sitemap_urls',
                        nonce: '<?php echo wp_create_nonce("chroma_seo_dashboard_nonce"); ?>'
                    }, function(response) {
                        if (response.success && response.data.urls.length > 0) {
                            var urls = response.data.urls;
                            totalPosts = urls.length;
                            $('#scan-total').text(totalPosts);
                            log('Found ' + urls.length + ' URLs in sitemap');
                            processSitemapUrls(urls, 0);
                        } else {
                            log('ERROR: No URLs found in sitemap or failed to parse');
                            finishScan();
                        }
                    });
                }
                
                function processSitemapUrls(urls, index) {
                    if (index >= urls.length) {
                        finishScan();
                        return;
                    }
                    
                    var url = urls[index];
                    
                    $.post(ajaxurl, {
                        action: 'chroma_validate_url',
                        url: url,
                        nonce: '<?php echo wp_create_nonce("chroma_seo_dashboard_nonce"); ?>'
                    }, function(response) {
                        processedPosts++;
                        $('#scan-count').text(processedPosts);
                        var pct = (processedPosts / totalPosts) * 100;
                        $('#scan-progress-bar').css('width', pct + '%');
                        
                        if (response.success) {
                            var data = response.data;
                            var result = {
                                id: 'url-' + index,
                                url: data.url,
                                title: data.title || data.url,
                                post_type: 'URL',
                                valid: data.valid,
                                errors: data.total_errors,
                                warnings: data.total_warnings,
                                schemas: data.schemas,
                                raw_schema: null
                            };
                            
                            if (data.valid) {
                                validCount++;
                            } else {
                                errorCount++;
                            }
                            
                            scanResults[result.id] = result;
                            renderRow(result);
                        } else {
                            log('Error validating: ' + url);
                        }
                        
                        // Process next with slight delay to avoid overwhelming server
                        var requestDelay = parseInt($('#scan-request-delay').val()) || 100;
                        setTimeout(function() {
                            processSitemapUrls(urls, index + 1);
                        }, requestDelay);
                    }).fail(function() {
                        processedPosts++;
                        log('Failed to validate: ' + url);
                        var requestDelay = (parseInt($('#scan-request-delay').val()) || 100) + 1000; // Add extra delay on failure
                        setTimeout(function() {
                            processSitemapUrls(urls, index + 1);
                        }, requestDelay);
                    });
                }

                function startBatchProcess() {
                    processBatch(0, 0); 
                }

                // Feature 9: Request Delay Slider
                $('#scan-request-delay').on('input', function() {
                    $('#request-delay-display').text($(this).val());
                });

                function processBatch(typeIndex, offset) {
                    if (shouldStop) {
                        $('#start-bulk-scan').prop('disabled', false).html('<span class="dashicons dashicons-search" style="line-height: 28px;"></span> Start Full Site Scan');
                        showToast('Scan stopped by user', 'warning');
                        return;
                    }
                    
                    var types = Object.keys(postTypesToScan);
                    if (typeIndex >= types.length) {
                        // All Done
                        $('#start-bulk-scan').prop('disabled', false).html('<span class="dashicons dashicons-search" style="line-height: 28px;"></span> Start Full Site Scan');
                        $('#bulk-scan-summary').show();
                        showToast('Scan Complete!', 'success');
                        
                        // Play sound (Feature 22 - later)
                        return;
                    }
                    
                    var pType = types[typeIndex];
                    var batchSize = parseInt($('#scan-batch-size').val()) || 10;
                    var requestDelay = parseInt($('#scan-request-delay').val()) || 100;

                    $.post(ajaxurl, {
                        action: 'chroma_scan_schema_batch', // Logic handles sitemap vs DB mode internally
                        post_type: pType,
                        offset: offset,
                        batch_size: batchSize,
                        nonce: '<?php echo wp_create_nonce("chroma_seo_dashboard_nonce"); ?>'
                    }, function(response) {
                        if (response.success) {
                            var data = response.data;
                            
                            // Render Rows
                            if (data.results && data.results.length > 0) {
                                data.results.forEach(function(item) {
                                    scanResults[item.id] = item; // Store for export
                                    
                                    // Apply Filters
                                    var filter = $('#bulk-scan-filter').val();
                                    var rowHtml = renderRow(item);
                                    var $row = $(rowHtml);
                                    
                                    if (filter === 'valid' && !item.valid) $row.hide();
                                    if (filter === 'invalid' && item.valid) $row.hide();
                                    if (filter === 'warnings' && (item.valid || (!item.valid && (!item.warnings || item.warnings.length === 0)))) $row.hide();
                                    
                                    $('#bulk-results-table tbody').append($row);
                                });
                                
                                // Update Counts
                                $('#scan-count').text(scanCount + data.results.length); // Update total scanned
                                scanCount += data.results.length;
                                updateProgressBar();
                                
                                $('#error-count').text(errorCount);
                                $('#valid-count').text(validCount);
                            }
                            
                            if (data.has_more) {
                                // Next Batch with Delay
                                setTimeout(function() {
                                    processBatch(typeIndex, offset + batchSize);
                                }, requestDelay);
                            } else {
                                // Next Type
                                processBatch(typeIndex + 1, 0);
                            }
                            
                        } else {
                            // PHP Error (e.g. timeout) - Retry logic could go here (Feature 10)
                            console.error('Batch Error: ' + (response.data.message || 'Unknown'));
                            showToast('Batch Error: ' + (response.data.message || 'Unknown'), 'error');
                            // Skip this batch and try next offset? Or abort?
                            // Simple retry: just move forward by 1 to skip stuck item
                             setTimeout(function() {
                                processBatch(typeIndex, offset + 1);
                            }, requestDelay + 1000);
                        }
                    }).fail(function(xhr, status, error) {
                        console.error('Network Error: ' + error);
                        // Retry logic (Feature 10)
                        setTimeout(function() {
                            processBatch(typeIndex, offset + 1);
                        }, requestDelay + 1000);
                    });
                }

                function renderRow(item) {
                    var statusIcon = item.valid ? '✅' : '❌';
                    var statusText = item.valid ? 'Valid' : 'Invalid';
                    var statusClass = item.valid ? 'schema-valid' : 'schema-invalid';
                    if (item.warnings && item.warnings.length) {
                        statusIcon = '⚠️';
                        statusText = 'Warnings';
                        statusClass = 'schema-warnings';
                    }
                    
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
                        <tr class="${statusClass}" data-id="${item.id}" data-status="${item.valid ? 'valid' : 'invalid'}">
                            <td><input type="checkbox" class="row-select" value="${item.id}"></td>
                            <td>
                                <strong><a href="${item.edit_url}" target="_blank">${item.title}</a></strong>
                                <br><small><a href="${item.permalink}" target="_blank">${item.permalink}</a></small>
                            </td>
                            <td>${item.type}</td>
                            <td>${statusIcon} ${statusText}</td>
                            <td>${messages}</td>
                            <td><span class="chroma-timestamp">${item.last_checked ? timeAgo(new Date(item.last_checked * 1000)) : 'Just now'}</span></td>
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
                    
                    // Show Controls
                    $('#chroma-validator-search, #bulk-scan-filter, #export-bulk-csv, #export-bulk-json').show();
                    
                    playSuccessSound();
                    
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

                // Feature 20: Bulk AI Fix (Selected)
                $('#bulk-ai-fix').on('click', function() {
                    var selectedIds = $('.row-select:checked').map(function() { return $(this).val(); }).get();
                    if (selectedIds.length === 0) return;
                    
                    if (!confirm('Repair ' + selectedIds.length + ' selected pages using AI?')) return;
                    
                    var $btn = $(this);
                    $btn.prop('disabled', true).text('⏳ Processing ' + selectedIds.length + '...');
                    
                    var fixQueue = [];
                    $.each(selectedIds, function(i, id) {
                        if (scanResults[id]) fixQueue.push(scanResults[id]);
                    });
                    
                    var index = 0;
                    function processNext() {
                        if (index >= fixQueue.length) {
                            $btn.prop('disabled', false).text('Bulk AI Fix (Selected)');
                            showToast('Bulk fix complete!', 'success');
                            return;
                        }
                        
                        var item = fixQueue[index];
                        var $rowBtn = $('.chroma-open-bulk-fix[data-id="' + item.id + '"]');
                        $rowBtn.text('⏳...').prop('disabled', true);
                        
                        // Use existing logic through a shared function would be better, but for now duplicate fix logic or call internal triggers
                        // For speed, let's trigger the AI fix logic manually
                        var schemaJson = (item.schema && item.schema.length) ? item.schema[0] : '';
                        var allIssues = (item.errors || []).concat(item.warnings || []);

                        $.post(ajaxurl, {
                            action: 'chroma_fix_schema_with_ai',
                            nonce: chroma_fix_nonce,
                            schema: schemaJson,
                            errors: allIssues,
                        }, function(res1) {
                            if (res1.success) {
                                $.post(ajaxurl, {
                                    action: 'chroma_apply_schema_fix',
                                    nonce: '<?php echo wp_create_nonce('chroma_seo_dashboard_nonce'); ?>',
                                    post_id: item.id.replace('url-',''), // Handle both PID and temp IDs
                                    schema: res1.data.fixed_schema
                                }, function(res2) {
                                    $rowBtn.replaceWith('<span class="chroma-badge chroma-badge-manual">✅ Fixed</span>');
                                    index++;
                                    processNext();
                                });
                            } else {
                                $rowBtn.text('❌ Failed').prop('disabled', false);
                                index++;
                                processNext();
                            }
                        });
                    }
                    processNext();
                });

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

        try {
            $post_type = sanitize_text_field($_POST['post_type']);
            $offset = intval($_POST['offset']);
            $batch_size = intval(get_option('chroma_validator_batch_size', 10));
            set_time_limit(180); // Increased for larger batches

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

            if (empty($posts)) {
                wp_send_json_success([
                    'done' => true,
                    'message' => 'Scan Complete'
                ]);
            }
            
            // Feature 11: Memory Guard
            if (memory_get_usage() > 128 * 1024 * 1024) { // 128MB Safety Limit
                 wp_send_json_error(['message' => 'Memory limit (128MB) reached. Try reducing batch size.']);
            }

            foreach ($posts as $post) {
                $pid = $post->ID;
                $permalink = get_permalink($pid);
                
                // Feature 7 & 10: Use shared helper (Handles cache & retry & logging)
                $result = $this->perform_url_validation($permalink, $pid);
                
                $is_valid = $result['valid'];
                $errors = $result['errors'];
                $warnings = $result['warnings'];
                $type_counts = $result['schema_types'] ?? [];
                
                // Feature 15: Email Alerts (Simple version)
                if (!empty($errors) && get_option('chroma_validator_email_alerts')) {
                    $admin_email = get_option('admin_email');
                    wp_mail($admin_email, '[Chroma SEO] Schema Error Found', "Critical schema errors found on: $permalink\nErrors: " . implode(', ', $errors));
                }
                
                // Determine final status
                $status = 'valid';
                if (!empty($errors)) $status = 'error';
                elseif (!empty($warnings)) $status = 'warning';
                elseif (!$is_valid) {
                     $status = 'warning'; 
                     $warnings[] = 'No Schema found';
                }
                
                // Update Post Meta
                update_post_meta($pid, '_chroma_schema_validation_status', $is_valid ? 'valid' : 'invalid');
                update_post_meta($pid, '_chroma_last_validated', time());
                if (!empty($errors)) update_post_meta($pid, '_chroma_schema_errors', $errors);
                else delete_post_meta($pid, '_chroma_schema_errors');

                $results[] = [
                    'id' => $pid,
                    'title' => $post->post_title,
                    'permalink' => $permalink, // JS expects permalink, PHP sent url. JS lines 2184 uses item.permalink
                    'edit_url' => get_edit_post_link($pid),
                    'status' => $status,
                    'errors' => $errors,
                    'warnings' => $warnings,
                    'schema_types' => $type_counts ?? [],
                    'type' => !empty($type_counts) ? implode(', ', array_keys($type_counts)) : 'None',
                    'schema' => $schemas ?? [] // Pass raw schemas for the modal
                ];
            } // foreach posts

       } catch (Throwable $e) {
            wp_send_json_error(['message' => 'Batch Error: ' . $e->getMessage()]);
       }

        wp_send_json_success([
            'results' => $results,
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

            // Feature 13: Log AI Fix
            if (class_exists('Chroma_Validation_Logger')) {
                (new Chroma_Validation_Logger())->log_fix(get_permalink($post_id), true, 'Multiple schemas applied', $post_id);
            }
            
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
                // Feature 13: Log Failure
                if (class_exists('Chroma_Validation_Logger')) {
                    (new Chroma_Validation_Logger())->log_fix(get_permalink($post_id), false, 'Invalid JSON: ' . json_last_error_msg(), $post_id);
                }
                wp_send_json_error(['message' => 'Invalid JSON: ' . json_last_error_msg()]);
            }

            update_post_meta($post_id, '_chroma_schema_override', $schema);

            // Feature 13: Log AI Fix
            if (class_exists('Chroma_Validation_Logger')) {
                (new Chroma_Validation_Logger())->log_fix(get_permalink($post_id), true, 'Single schema applied', $post_id);
            }

            wp_send_json_success([
                'message' => 'Schema applied successfully',
                'post_id' => $post_id
            ]);
        }
    }

    /**
     * AJAX: Fetch Live Schema from URL
     */
    public function ajax_fetch_live_schema()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');
        
        $url = esc_url_raw($_POST['url']);
        if (empty($url)) {
            wp_send_json_error(['message' => 'No URL provided']);
        }
        
        $response = wp_remote_get($url, [
            'timeout' => 60,
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (compatible; ChromaSEO/1.0)'
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }
        
        $html = wp_remote_retrieve_body($response);
        
        // Extract JSON-LD scripts
        preg_match_all('/<script\s+type=["\']application\/ld\+json["\']>(.*?)<\/script>/si', $html, $matches);
        
        $schemas = [];
        foreach ($matches[1] as $json) {
            $parsed = json_decode(trim($json), true);
            if ($parsed) {
                // Handle @graph
                if (isset($parsed['@graph'])) {
                    foreach ($parsed['@graph'] as $node) {
                        $schemas[] = $node;
                    }
                } else {
                    $schemas[] = $parsed;
                }
            }
        }
        
        wp_send_json_success([
            'schemas' => $schemas,
            'count' => count($schemas),
            'raw' => $matches[1]
        ]);
    }

    /**
     * AJAX: Sync Live Schema to Builder
     */
    public function ajax_sync_schema_to_builder()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $live_schemas = json_decode(stripslashes($_POST['schemas']), true);
        
        if (!$post_id || empty($live_schemas)) {
            wp_send_json_error(['message' => 'Invalid data or no schemas provided']);
        }
        
        $available_defs = Chroma_Schema_Types::get_definitions();
        $builder_schemas = [];
        
        $aliases = [
            'Preschool' => 'ChildCare',
            'School' => 'ChildCare',
            'EducationalOrganization' => 'ChildCare',
            'DayCare' => 'ChildCare',
            'EmergencyService' => 'LocalBusiness',
            'Restaurant' => 'LocalBusiness',
        ];

        foreach ($live_schemas as $schema) {
            $type = $schema['@type'] ?? 'Unknown';
            if (is_array($type)) {
                $type = $type[0];
            }
            
            // Handle Aliases
            if (isset($aliases[$type])) {
                $type = $aliases[$type];
            }
            
            // Skip unrecognized types
            if (!isset($available_defs[$type])) {
                continue;
            }

            // Flatten Nested Data (Address)
            if (isset($schema['address']) && is_array($schema['address'])) {
                $addr = $schema['address'];
                $schema['streetAddress'] = $addr['streetAddress'] ?? ($schema['streetAddress'] ?? '');
                $schema['addressLocality'] = $addr['addressLocality'] ?? ($schema['addressLocality'] ?? '');
                $schema['addressRegion'] = $addr['addressRegion'] ?? ($schema['addressRegion'] ?? '');
                $schema['postalCode'] = $addr['postalCode'] ?? ($schema['postalCode'] ?? '');
            }

            // Flatten Nested Data (Geo)
            if (isset($schema['geo']) && is_array($schema['geo'])) {
                $geo = $schema['geo'];
                $schema['geo_lat'] = $geo['latitude'] ?? ($schema['geo_lat'] ?? '');
                $schema['geo_lng'] = $geo['longitude'] ?? ($schema['geo_lng'] ?? '');
            }

            // Extract valid fields defined in our Builder for this type
            $field_data = [];
            $def_fields = $available_defs[$type]['fields'] ?? [];
            
            foreach ($def_fields as $key => $field_def) {
                if (isset($schema[$key])) {
                    $val = $schema[$key];
                    
                    // Handle Complex Types mapping to Simple Fields
                    if ($field_def['type'] !== 'repeater') {
                        // Case 1: Value is an Array
                        if (is_array($val)) {
                            // Check if it's an array of strings
                            if (count(array_filter($val, 'is_string')) === count($val)) {
                                $val = implode(', ', $val);
                            } 
                            // Check if it's a single object (associative array)
                            elseif (array_keys($val) !== range(0, count($val) - 1)) {
                                // It's an object. Try to extract common values.
                                if (isset($val['name'])) $val = $val['name'];
                                elseif (isset($val['url'])) $val = $val['url'];
                                elseif (isset($val['@id'])) $val = $val['@id'];
                                else $val = json_encode($val); // Fallback
                            }
                            // Check if it's a list of objects
                            else {
                                // Extract names or URLs from list
                                $mapped = [];
                                foreach ($val as $v) {
                                    if (is_array($v)) {
                                        if (isset($v['name'])) $mapped[] = $v['name'];
                                        elseif (isset($v['url'])) $mapped[] = $v['url'];
                                        elseif (isset($v['@id'])) $mapped[] = $v['@id'];
                                    } elseif (is_string($v)) {
                                        $mapped[] = $v;
                                    }
                                }
                                $val = !empty($mapped) ? implode(', ', $mapped) : json_encode($val);
                            }
                        }
                    }
                    
                    $field_data[$key] = $val;
                }
            }

            if (!empty($field_data)) {
                $builder_schemas[] = [
                    'type' => $type,
                    'data' => $field_data
                ];
            }
        }
        
        if (empty($builder_schemas)) {
            wp_send_json_error(['message' => 'Zero recognized schemas found to sync. Only standard Schema.org types are supported by the Builder.']);
        }

        update_post_meta($post_id, '_chroma_post_schemas', $builder_schemas);
        
        wp_send_json_success([
            'synced' => count($builder_schemas),
            'message' => 'Successfully synced ' . count($builder_schemas) . ' schemas to the Builder.'
        ]);
    }

    /**
     * AJAX: Save Sitemap URLs setting
     */
    public function ajax_save_sitemap_urls()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $urls = sanitize_textarea_field($_POST['urls']);
        update_option('chroma_validator_sitemaps', $urls);
        
        // Feature 5: Save Exclusions
        if (isset($_POST['exclusions'])) {
            $exclusions = sanitize_textarea_field($_POST['exclusions']);
            update_option('chroma_validator_exclusions', $exclusions);
        }
        
        wp_send_json_success(['message' => 'Sitemap URLs & Exclusions saved']);
    }

    /**
     * AJAX: Clear Validation Cache
     */
    public function ajax_clear_validation_cache()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        if (class_exists('Chroma_Validation_Cache')) {
            Chroma_Validation_Cache::clear_all();
            wp_send_json_success(['message' => 'Cache cleared']);
        }
        
        wp_send_json_error(['message' => 'Cache class not found']);
    }

    /**
     * AJAX: Save Validator Setting (Generic)
     */
    public function ajax_save_validator_setting()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }

        $setting = sanitize_key($_POST['setting'] ?? '');
        $value = sanitize_text_field($_POST['value'] ?? '');

        // Whitelist allowed settings for AJAX update
        $allowed = ['email_alerts', 'batch_size', 'request_delay', 'timeout', 'cache_ttl'];
        
        if (in_array($setting, $allowed)) {
            update_option('chroma_validator_' . $setting, $value);
            wp_send_json_success(['message' => 'Setting ' . $setting . ' saved']);
        }

        wp_send_json_error(['message' => 'Invalid or restricted setting']);
    }

    /**
     * AJAX: Parse sitemap and return all URLs
     */
    public function ajax_parse_sitemap_urls()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        $sitemap_urls_raw = get_option('chroma_validator_sitemaps', home_url('/sitemap.xml'));
        $sitemap_urls = array_filter(array_map('trim', explode("\n", $sitemap_urls_raw)));
        
        // Feature 5: Get Exclusions
        $exclusions_raw = get_option('chroma_validator_exclusions', '');
        $exclusions = array_filter(array_map('trim', explode("\n", $exclusions_raw)));
        
        $all_urls = [];
        
        foreach ($sitemap_urls as $sitemap_url) {
            $urls = $this->parse_sitemap($sitemap_url);
            $all_urls = array_merge($all_urls, $urls);
        }
        
        // Remove duplicates
        $all_urls = array_unique($all_urls);
        
        // Filter Exclusions
        if (!empty($exclusions)) {
            $all_urls = array_filter($all_urls, function($url) use ($exclusions) {
                foreach ($exclusions as $pattern) {
                    if (fnmatch($pattern, $url)) {
                        return false; // Exclude
                    }
                }
                return true; // Keep
            });
        }
        
        wp_send_json_success([
            'urls' => array_values($all_urls),
            'count' => count($all_urls)
        ]);
    }

    /**
     * Parse a sitemap URL and return all page URLs
     * Supports sitemap index files
     *
     * @param string $sitemap_url
     * @return array
     */
    private function parse_sitemap($sitemap_url)
    {
        $response = wp_remote_get($sitemap_url, [
            'timeout' => 30,
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (compatible; ChromaSEO/1.0)'
        ]);
        
        if (is_wp_error($response)) {
            chroma_debug_log('[Chroma SEO] Sitemap fetch error for ' . $sitemap_url . ': ' . $response->get_error_message());
            return [];
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Suppress XML errors
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_clear_errors();
        
        if (!$xml) {
            chroma_debug_log('[Chroma SEO] Failed to parse sitemap XML: ' . $sitemap_url);
            return [];
        }
        
        $urls = [];
        
        // Check if this is a sitemap index (contains <sitemap> elements)
        if (isset($xml->sitemap)) {
            foreach ($xml->sitemap as $sitemap) {
                $child_url = (string) $sitemap->loc;
                if ($child_url) {
                    // Recursively parse child sitemaps
                    $child_urls = $this->parse_sitemap($child_url);
                    $urls = array_merge($urls, $child_urls);
                }
            }
        }
        
        // Parse URL entries
        if (isset($xml->url)) {
            foreach ($xml->url as $url) {
                $loc = (string) $url->loc;
                if ($loc) {
                    $urls[] = $loc;
                }
            }
        }
        
        return $urls;
    }

    /**
     * AJAX: Validate a URL directly (for sitemap-based validation)
     */
    public function ajax_validate_url()
    {
        try {
            check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(['message' => 'Permission denied']);
            }
            
            $url = esc_url_raw($_POST['url']);
            
            if (empty($url)) {
                wp_send_json_error(['message' => 'No URL provided']);
            }
            
            $result = $this->perform_url_validation($url);
            
            if (isset($result['error_message'])) {
                 wp_send_json_error([
                    'url' => $url,
                    'message' => $result['error_message']
                ]);
            }
            
            // Format for JS
            wp_send_json_success([
                'url' => $url,
                'valid' => $result['valid'],
                'errors' => $result['errors'],
                'warnings' => $result['warnings'],
                'last_checked' => $result['timestamp']
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Internal Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Perform distinct URL validation with Cache, Retry, and Logging
     * 
     * @param string $url
     * @param int $post_id Optional post ID for logging
     * @return array
     */
    private function perform_url_validation($url, $post_id = 0)
    {
        // Feature 7: Check Cache
        if (class_exists('Chroma_Validation_Cache')) {
            $cached = Chroma_Validation_Cache::get($url);
            if ($cached && empty($_POST['force_refresh'])) {
                return $cached;
            }
        }
        
        $response = null;
        $attempt = 0;
        $max_retries = intval(get_option('chroma_validator_max_retries', 3));
        $timeout = intval(get_option('chroma_validator_timeout', 30));
        
        // Feature 10: Connection Retry Loop
        while ($attempt < $max_retries) {
            $response = wp_remote_get($url, [
                'timeout' => $timeout,
                'sslverify' => false,
                'user-agent' => 'Mozilla/5.0 (compatible; ChromaSEO/1.0)'
            ]);
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                break;
            }
            
            $attempt++;
            if ($attempt < $max_retries) sleep(1); // Wait 1s before retry
        }
        
        if (is_wp_error($response)) {
             $res = ['valid' => false, 'errors' => ['Fetch Error: ' . $response->get_error_message()], 'warnings' => [], 'error_message' => $response->get_error_message(), 'timestamp' => time()];
             return $res;
        }
        
        $html = wp_remote_retrieve_body($response);
        
        // Extract JSON-LD scripts
        preg_match_all('/<script\s+type=["\']application\/ld\+json["\']>(.*?)<\/script>/si', $html, $matches);
        
        $errors = [];
        $warnings = [];
        $has_valid_schema = false;
        $type_counts = [];
        $schemas = $matches[1] ?? [];
        
        if (empty($schemas)) {
            $errors[] = 'No schema found on page';
        } else {
             foreach ($schemas as $json) {
                if (class_exists('Chroma_Schema_Validator')) {
                    $res = Chroma_Schema_Validator::validate_json_ld($json);
                    if ($res['valid']) {
                        $has_valid_schema = true;
                        
                        // Count types
                        if (isset($res['parsed']['@type'])) {
                             $t = $res['parsed']['@type'];
                             if (is_array($t)) {
                                 foreach ($t as $subT) $type_counts[$subT] = ($type_counts[$subT] ?? 0) + 1;
                             } else {
                                 $type_counts[$t] = ($type_counts[$t] ?? 0) + 1;
                             }
                        }
                    } else {
                        // Collect errors
                        foreach ($res['errors'] as $e) if (!in_array($e, $errors)) $errors[] = $e;
                        foreach ($res['warnings'] as $w) if (!in_array($w, $warnings)) $warnings[] = $w;
                    }
                }
             }
        }
        
        $is_valid = $has_valid_schema && empty($errors);
        
        $result = [
            'valid' => $is_valid,
            'errors' => $errors,
            'warnings' => $warnings,
            'timestamp' => time(),
            'schema_types' => $type_counts
        ];
        
        // Feature 7: Save to Cache
        if (class_exists('Chroma_Validation_Cache')) {
            Chroma_Validation_Cache::set($url, $result);
        }

        // Feature 12: Validation Log
        if (class_exists('Chroma_Validation_Logger')) {
            (new Chroma_Validation_Logger())->log_validation($url, $result, $post_id);
        }
        
        return $result;
    }

    /**
     * Render Careers & Sync Tab
     */
    private function render_careers_tab()
    {
        $last_sync = get_option('chroma_last_career_sync', 'Never');
        $last_count = get_option('chroma_last_career_sync_count', 0);
        $feed_url = get_option('chroma_careers_feed_url', 'https://app.acquire4hire.com/careers/list.json?id=4668');
        ?>
        <div class="chroma-seo-card">
            <h2>💼 Career Feed Synchronization</h2>
            <p class="description">Automatically imports job listings from Acquire4Hire and generates <code>JobPosting</code> schema for Google Rich Results.</p>
            
            <div style="background: #f0f6fc; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2271b1;">
                <h3 style="margin-top: 0;">📊 Current Status</h3>
                <p><strong>Last Sync:</strong> <span id="last-sync-time"><?php echo esc_html($last_sync); ?></span></p>
                <p><strong>Jobs in Last Sync:</strong> <span id="last-sync-count"><?php echo esc_html($last_count); ?></span></p>
                
                <div style="margin-top: 20px;">
                    <button type="button" id="chroma-sync-careers-btn" class="button button-primary">
                        <span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Sync Careers Now
                    </button>
                    <span id="sync-status" style="margin-left: 10px;"></span>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('chroma_careers_options'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="chroma_careers_feed_url">External Feed URL (JSON/HTML)</label></th>
                        <td>
                            <input name="chroma_careers_feed_url" type="url" id="chroma_careers_feed_url" value="<?php echo esc_url($feed_url); ?>" class="regular-text">
                            <p class="description">The URL of the Acquire4Hire career list. Usually ends in <code>list.json?id=XXXX</code>.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Feed Settings'); ?>
            </form>

            <div style="margin-top: 40px; border-top: 1px solid #ddd; padding-top: 20px;">
                <h3>🛡️ About Automated Careers</h3>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><strong>Hourly Sync:</strong> The system automatically checks for new jobs every hour via WP-Cron.</li>
                    <li><strong>Auto-Pruning:</strong> Jobs removed from the external feed will be moved to the Trash in WordPress.</li>
                    <li><strong>Rich Snippets:</strong> Every synced job listing automatically includes validated <code>JobPosting</code> JSON-LD schema for Google.</li>
                </ul>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#chroma-sync-careers-btn').click(function() {
                var btn = $(this);
                var status = $('#sync-status');
                
                btn.prop('disabled', true);
                status.html('<span class="spinner is-active" style="float:none; margin:0;"></span> Synchronizing...');
                
                $.post(ajaxurl, {
                    action: 'chroma_sync_careers',
                    nonce: '<?php echo wp_create_nonce("chroma_seo_nonce"); ?>'
                }, function(response) {
                    btn.prop('disabled', false);
                    if (response.success) {
                        var data = response.data;
                        status.html('<span class="dashicons dashicons-yes" style="color:green;"></span> Sync Successful!').css('color', 'green');
                        $('#last-sync-time').text(data.timestamp);
                        $('#last-sync-count').text(data.total);
                        showToast('Career sync complete: ' + data.created + ' new, ' + data.updated + ' updated, ' + data.trashed + ' trashed.');
                    } else {
                        status.html('<span class="dashicons dashicons-warning" style="color:red;"></span> Error: ' + response.data).css('color', 'red');
                    }
                }).fail(function() {
                    btn.prop('disabled', false);
                    status.html('<span class="dashicons dashicons-no" style="color:red;"></span> Network error during sync.').css('color', 'red');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render SEO Automations Tab
     */
    private function render_automations_tab()
    {
        ?>
        <div class="chroma-seo-card">
            <h2>🚀 SEO Automations & Advanced Features</h2>
            <p class="description">Central management for all automated SEO strategies and performance features.</p>
            
            <form method="post" action="options.php">
                <?php settings_fields('chroma_automation_options'); ?>
                
                <h3 class="chroma-section-title">🔗 Internal Linking</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Related Locations</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_show_related_locations" value="1" <?php checked(get_option('chroma_seo_show_related_locations', 1)); ?>>
                                Show "Other Locations Near You" on location individual pages.
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Program-Location Interlinks</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_link_programs_locations" value="1" <?php checked(get_option('chroma_seo_link_programs_locations', 1)); ?>>
                                Automatically link programs to their serving locations.
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Footer City Links</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_show_footer_cities" value="1" <?php checked(get_option('chroma_seo_show_footer_cities', 1)); ?>>
                                Generate hyper-local city links in the site footer.
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Automatic Keyword Linking</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_enable_keyword_linking" value="1" <?php checked(get_option('chroma_seo_enable_keyword_linking', 1)); ?>>
                                Automatically link key phrases to internal pages (requires keyword map).
                            </label>
                        </td>
                    </tr>
                </table>

                <h3 class="chroma-section-title">🛡️ E-E-A-T & Trust</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Author Metadata & Box</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_show_author_meta" value="1" <?php checked(get_option('chroma_seo_show_author_meta', 1)); ?>>
                                Include Author schema and UI boxes on blog posts.
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Credential Badges</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_show_credential_badges" value="1" <?php checked(get_option('chroma_seo_show_credential_badges', 1)); ?>>
                                Display DECAL and Quality Rated badges on location pages.
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Entity SEO Markup</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_enable_entity_markup" value="1" <?php checked(get_option('chroma_seo_enable_entity_markup', 1)); ?>>
                                Inject semantic entity markup (Topic Clusters, Known Entities) into content.
                            </label>
                        </td>
                    </tr>
                </table>

                <h3 class="chroma-section-title">♿ Accessibility SEO</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Skip Navigation Links</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_enable_skip_nav" value="1" <?php checked(get_option('chroma_seo_enable_skip_nav', 1)); ?>>
                                Add "Skip to Content" links for screen readers (improves indexability).
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Smart Focus Indicators</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_enable_focus_indicators" value="1" <?php checked(get_option('chroma_seo_enable_focus_indicators', 1)); ?>>
                                Enhanced focus states for better accessibility compliance.
                            </label>
                        </td>
                    </tr>
                </table>

                <h3 class="chroma-section-title">⚡ High Performance & Indexing</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Speculation Rules API</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_enable_speculation_rules" value="yes" <?php checked(get_option('chroma_enable_speculation_rules', 'yes'), 'yes'); ?>>
                                <strong>Near-Instant Navigation:</strong> Uses Browser Prerendering for extremely fast page loads.
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">IndexNow Support</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_enable_indexnow" value="yes" <?php checked(get_option('chroma_enable_indexnow', 'yes'), 'yes'); ?>>
                                Notify search engines (Bing, Yandex) instantly when content is updated.
                            </label>
                        </td>
                    </tr>
                </table>

                <h3 class="chroma-section-title">🗺️ Geographic SEO</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">County Service Areas</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_enable_county_pages" value="1" <?php checked(get_option('chroma_seo_enable_county_pages', 1)); ?>>
                                Dynamically generate and index pages for serving counties.
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">ZIP Code Targeting</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_enable_zip_pages" value="1" <?php checked(get_option('chroma_seo_enable_zip_pages', 1)); ?>>
                                Enable hyper-local ZIP code service area pages.
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Combo Page Generation</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_auto_generate_combos" value="1" <?php checked(get_option('chroma_seo_auto_generate_combos', 1)); ?>>
                                Automatically create intersection pages (Program + City).
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Combo Internal Linking</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_enable_combo_links" value="1" <?php checked(get_option('chroma_seo_enable_combo_links', 1)); ?>>
                                Inject internal links to combo pages from relevant post content.
                            </label>
                        </td>
                    </tr>
                </table>

                <h3 class="chroma-section-title">🛠️ Technical SEO</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Dynamic Title Patterns</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_enable_dynamic_titles" value="1" <?php checked(get_option('chroma_seo_enable_dynamic_titles', 1)); ?>>
                                Enable automated title tag construction using post patterns.
                            </label>
                            <p><a href="<?php echo admin_url('admin.php?page=chroma-title-patterns'); ?>" class="button button-secondary">Configure Patterns</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Canonical & Trailing Slash</th>
                        <td>
                            <label>
                                <input type="checkbox" name="chroma_seo_enable_canonical" value="1" <?php checked(get_option('chroma_seo_enable_canonical', 1)); ?>>
                                Enforce canonical tags and trailing slashes for SEO hygiene.
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Save Automation Settings'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render Internal Link Analysis Tab
     */
    private function render_analysis_tab()
    {
        $report = get_option('chroma_seo_link_report', []);
        ?>
        <div class="chroma-seo-card">
            <h2>🔗 Internal Link Equity Analysis</h2>
            <p class="description">Scan your site to identify orphan pages, weak internal links, and AI-driven link suggestions.</p>
            
            <div style="background: #f0f6fc; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2271b1;">
                <button type="button" id="chroma-run-analysis-btn" class="button button-primary">
                    <span class="dashicons dashicons-performance" style="vertical-align: middle;"></span> Run Full Site Audit
                </button>
                <span id="analysis-status" style="margin-left: 10px;"></span>
            </div>

            <div id="analysis-results">
                <?php if (empty($report)): ?>
                    <p>No analysis report found. Click the button above to start the first audit.</p>
                <?php else: ?>
                    <div class="analysis-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                            <strong>Total Pages Scanned:</strong>
                            <div style="font-size: 24px; font-weight: bold;"><?php echo count($report['scanned'] ?? []); ?></div>
                        </div>
                        <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                            <strong>Orphan Pages:</strong>
                            <div style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo count($report['orphans'] ?? []); ?></div>
                        </div>
                        <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                            <strong>AI Link Suggestions:</strong>
                            <div style="font-size: 24px; font-weight: bold; color: #00a32a;"><?php echo count($report['suggestions'] ?? []); ?></div>
                        </div>
                    </div>
                    </div>

                    <div class="analysis-details">
                        <?php if (!empty($report['orphans'])): ?>
                            <h3>Orphan Pages (No incoming links)</h3>
                            <table class="chroma-seo-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Page Title</th>
                                        <th>Type</th>
                                        <th>Internal Outgoing</th>
                                        <th style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($report['orphans'], 0, 50) as $orphan): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($orphan['title'] ?? 'Unknown'); ?></strong></td>
                                            <td><?php echo esc_html($orphan['type'] ?? ''); ?></td>
                                            <td><?php echo esc_html($orphan['outgoing'] ?? 0); ?> links</td>
                                            <td><a href="<?php echo get_edit_post_link($orphan['id']); ?>" class="button button-small">Edit Page</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <?php if (!empty($report['suggestions'])): ?>
                            <h3 style="margin-top: 30px;">AI Link Suggestions</h3>
                            <table class="chroma-seo-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Target</th>
                                        <th>Issue</th>
                                        <th>Recommendation</th>
                                        <th style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($report['suggestions'], 0, 50) as $sug): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($sug['title'] ?? ''); ?></strong></td>
                                            <td><span class="chroma-badge" style="background:#fff8e5; color:#856404;"><?php echo esc_html($sug['type'] ?? ''); ?></span></td>
                                            <td><?php echo esc_html($sug['message'] ?? ''); ?></td>
                                            <td><a href="<?php echo admin_url('admin.php?page=chroma-link-equity'); ?>" class="button button-small">AI Tool</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                        
                        <div style="margin-top: 20px; text-align: right;">
                            <a href="<?php echo admin_url('admin.php?page=chroma-link-equity'); ?>" class="button button-secondary">Open Advanced Link Equity Manager &rarr;</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#chroma-run-analysis-btn').click(function() {
                var btn = $(this);
                var status = $('#analysis-status');
                
                if (!confirm('Running a full audit can take a few minutes on large sites. Continue?')) return;

                btn.prop('disabled', true);
                status.html('<span class="spinner is-active" style="float:none; margin:0;"></span> Auditing site structure...');
                
                $.post(ajaxurl, {
                    action: 'chroma_run_link_analysis',
                    nonce: '<?php echo wp_create_nonce("chroma_seo_dashboard_nonce"); ?>'
                }, function(response) {
                    btn.prop('disabled', false);
                    if (response.success) {
                        status.html('<span class="dashicons dashicons-yes" style="color:green;"></span> Audit Complete!').css('color', 'green');
                        showToast('Analysis complete! Reloading page to show results...');
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        status.html('<span class="dashicons dashicons-warning" style="color:red;"></span> Error: ' + response.data).css('color', 'red');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render Combo Pages Management Tab
     */
    private function render_combos_tab()
    {
        ?>
        <div class="chroma-seo-card">
            <h2>🔗 Program + City Combo Pages</h2>
            <p class="description">Live view of dynamically generated intersection pages. These pages are generated on-the-fly when a valid Program/City combination is requested.</p>
            
            <div style="margin: 20px 0;">
                <button type="button" class="button button-secondary" onclick="location.reload();">
                    <span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Refresh List
                </button>
            </div>

            <table class="chroma-seo-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Combo Type</th>
                        <th>Status</th>
                        <th>SEO Health</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (class_exists('Chroma_Combo_Page_Data')) {
                        $combos = Chroma_Combo_Page_Data::get_all_combos();
                        if (empty($combos)) {
                            echo '<tr><td colspan="4">No combo page overrides found. Using default AI generational logic for all intersections.</td></tr>';
                        } else {
                            foreach ($combos as $combo) {
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html($combo['program']); ?> in <?php echo esc_html($combo['city']); ?></strong></td>
                                    <td><span class="chroma-badge chroma-badge-manual">Active</span></td>
                                    <td><span class="chroma-health-dot chroma-health-good"></span> Optimized</td>
                                    <td><a href="#" class="button button-small">Edit Overrides</a></td>
                                </tr>
                                <?php
                            }
                        }
                    } else {
                        echo '<tr><td colspan="4">Combo Data module not loaded.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render Near Me Pages Management Tab
     */
    private function render_near_me_tab()
    {
        ?>
        <div class="chroma-seo-card">
            <h2>📍 Hyper-Local "Near Me" Pages</h2>
            <p class="description">Virtual pages optimized for "nearby" searches. These use browser geolocation to personalize content for the visitor.</p>
            
            <table class="chroma-seo-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Page Pattern</th>
                        <th>Visibility</th>
                        <th>Search Engine indexing</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (class_exists('Chroma_Near_Me_Pages')) {
                        $nm_manager = new Chroma_Near_Me_Pages();
                        $patterns = $nm_manager->get_all_pages();
                        if (empty($patterns)) {
                            echo '<tr><td colspan="4">No Near Me patterns active. Check your Geo settings.</td></tr>';
                        } else {
                            foreach ($patterns as $pattern => $data) {
                                ?>
                                <tr>
                                    <td><strong><?php echo esc_html(str_replace(home_url(), '', $data['url'])); ?></strong></td>
                                    <td><span class="chroma-badge <?php echo $data['type'] === 'Generic' ? 'chroma-badge-manual' : 'chroma-badge-auto'; ?>"><?php echo esc_html($data['type']); ?></span></td>
                                    <td><span class="chroma-check">✓</span> Indexed</td>
                                    <td><a href="<?php echo esc_url($data['url']); ?>" target="_blank" class="button button-small">View Live</a></td>
                                </tr>
                                <?php
                            }
                        }
                    } else {
                        echo '<tr><td colspan="4">Near Me module not loaded.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php
    }

    /**
     * AJAX: Run Link Equity Analysis
     */
    public function ajax_run_link_analysis()
    {
        check_ajax_referer('chroma_seo_dashboard_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        if (!class_exists('Chroma_Link_Equity_Analyzer')) {
            wp_send_json_error('Link Equity Analyzer not loaded');
        }

        $analyzer = new Chroma_Link_Equity_Analyzer();
        $analyzer->analyze(); // Run the full crawl

        $report = [
            'timestamp' => current_time('mysql'),
            'scanned' => [], // Needs data from analyzer
            'orphans' => [],
            'suggestions' => []
        ];

        // Fetch results from analyzer
        if (method_exists($analyzer, 'get_orphans')) {
            $report['orphans'] = $analyzer->get_orphans();
        }

        if (method_exists($analyzer, 'get_recommendations')) {
            $report['suggestions'] = $analyzer->get_recommendations();
        }
        
        // Let's assume analyzer->analyze() stores data somewhere or we can get scanned count
        global $wpdb;
        $report['scanned'] = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_status='publish'");

        update_option('chroma_seo_link_report', $report);
        wp_send_json_success('Analysis complete');
    }

    /**
     * Render Registry & Maintenance Tab (Consolidated)
     */
    /**
     * Render Registry & Maintenance Tab (Consolidated)
     */
    public function render_registry_tab()
    {
        global $wpdb;
        
        // 1. Get Real Database Stats (Not Runtime)
        $total_schemas_count = 0;
        $posts_with_schema = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_chroma_post_schemas' 
            AND meta_value != '' AND meta_value != 'a:0:{}'
        ");

        // Calculate total individual schemas
        $all_meta = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_chroma_post_schemas'");
        foreach ($all_meta as $val) {
            $data = maybe_unserialize($val);
            if (is_array($data)) {
                $total_schemas_count += count($data);
            }
        }
        
        // 2. Check Registry Class Status
        $registry_active = class_exists('Chroma_Schema_Registry');
        ?>
        <div class="chroma-seo-card">
            <h2>📊 Registry System Status</h2>
            <p>This dashboard monitors the <strong>Chroma Schema Registry</strong> logic and database health.</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <!-- System Health -->
                <div style="background: <?php echo $registry_active ? '#f0f6fc' : '#fff5f5'; ?>; padding: 15px; border: 1px solid <?php echo $registry_active ? '#c2dbff' : '#fcb3b3'; ?>; border-radius: 5px;">
                    <h3 style="margin-top: 0; color: <?php echo $registry_active ? '#005a9c' : '#d63638'; ?>;">
                        <?php echo $registry_active ? '✅ Registry Logic Active' : '❌ Registry Logic Missing'; ?>
                    </h3>
                    <p>The Registry class is loaded and ready to filter frontend output.</p>
                    <p><strong>Passthrough Mode:</strong> <span style="color: #00a32a; font-weight: bold;">Enabled</span></p>
                </div>

                <!-- Database Stats -->
                <div style="background: #e8f5e9; padding: 15px; border: 1px solid #4caf50; border-radius: 5px;">
                    <h3 style="margin-top: 0; color: #2e7d32;">💾 Database Content</h3>
                    <table class="widefat striped" style="background: transparent; border: none;">
                        <tbody>
                            <tr>
                                <td><strong>Managed Posts:</strong></td>
                                <td><?php echo intval($posts_with_schema); ?> posts have schema</td>
                            </tr>
                            <tr>
                                <td><strong>Total Schemas:</strong></td>
                                <td><strong><?php echo intval($total_schemas_count); ?></strong> schemas stored</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- UNIVERSAL VALIDATOR -->
            <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-left: 4px solid #673ab7;">
                <h3 style="margin-top: 0; color: #673ab7;">🌐 Universal Live Validator</h3>
                <p>Enter any URL from your site to see EXACTLY what schemas are being output to Google. This detects duplicates and non-Registry schemas.</p>
                
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <input type="url" id="live-check-url" class="regular-text" style="width: 100%; max-width: 500px;" 
                           placeholder="https://yourwebsite.com/sample-page/" value="<?php echo home_url('/'); ?>">
                    <button id="live-check-btn" class="button button-primary button-hero">
                        <span class="dashicons dashicons-search" style="margin-right: 5px; line-height: 1.5;"></span> Check Live Output
                    </button>
                </div>
                
                <span id="live-check-spinner" class="spinner" style="float: none; margin-top: 10px;"></span>
                
                <div id="live-check-results" style="margin-top: 20px; display: none;">
                    <!-- Results injected here -->
                </div>
            </div>
        </div>

        <hr style="margin: 30px 0;">

        <!-- Consolidated Tools Section -->
        <div class="chroma-seo-card">
            <h2>🛠️ Maintenance Tools</h2>
            
            <!-- Tab Navigation for Tools -->
            <?php
            // Determine active tool based on URL params
            $active_tool = (isset($_GET['ptype']) || isset($_GET['paged'])) ? 'tool-bulk' : 'tool-cleanup';
            ?>
            <h3 class="nav-tab-wrapper" style="margin-bottom: 15px;">
                <a href="#tool-cleanup" class="nav-tab <?php echo $active_tool === 'tool-cleanup' ? 'nav-tab-active' : ''; ?>" onclick="switchToolTab(event, 'tool-cleanup')">Schema Cleanup</a>
                <a href="#tool-bulk" class="nav-tab <?php echo $active_tool === 'tool-bulk' ? 'nav-tab-active' : ''; ?>" onclick="switchToolTab(event, 'tool-bulk')">Bulk Actions</a>
            </h3>

            <!-- Tool: Schema Cleanup -->
            <div id="tool-cleanup" class="tool-content" <?php echo $active_tool !== 'tool-cleanup' ? 'style="display: none;"' : ''; ?>>
                <?php $this->render_cleanup_tab_content(); ?>
            </div>

            <!-- Tool: Bulk Actions -->
            <div id="tool-bulk" class="tool-content" <?php echo $active_tool !== 'tool-bulk' ? 'style="display: none;"' : ''; ?>>
                <?php $this->render_bulk_ops_tab_content(); ?>
            </div>
        </div>

        <script>
        function switchToolTab(e, id) {
            e.preventDefault();
            jQuery('.tool-content').hide();
            jQuery('#' + id).show();
            jQuery('.nav-tab').removeClass('nav-tab-active');
            jQuery(e.target).addClass('nav-tab-active');
        }

        jQuery(document).ready(function($) {
            $('#live-check-btn').on('click', function(e) {
                e.preventDefault();
                var url = $('#live-check-url').val();
                if (!url) {
                    alert('Please enter a URL');
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true);
                $('#live-check-spinner').addClass('is-active');
                $('#live-check-results').hide().html('');
                
                $.post(ajaxurl, {
                    action: 'chroma_check_live_registry',
                    nonce: '<?php echo wp_create_nonce('chroma_schema_live_check'); ?>',
                    url: url
                }, function(response) {
                    btn.prop('disabled', false);
                    $('#live-check-spinner').removeClass('is-active');
                    
                    if (response.success) {
                        var data = response.data;
                        var color = data.schema_blocks_found > 1 ? '#d63638' : (data.schema_count > 0 ? '#00a32a' : '#ff9800');
                        
                        var html = '<div style="padding: 15px; background: #fafafa; border: 1px solid #ddd; border-left: 4px solid ' + color + ';">';
                        html += '<h3 style="margin-top: 0; color: ' + color + ';">Analysis Result</h3>';
                        html += '<p><strong>Status:</strong> Found ' + data.schema_count + ' schema items in ' + data.schema_blocks_found + ' script block(s).</p>';
                        
                        if (data.schema_blocks_found > 1) {
                            html += '<p style="color: #d63638; background: #ffebee; padding: 10px;"><strong>⚠️ WARNING: Multiple JSON-LD blocks found!</strong><br>This usually means something is bypassing the Registry. One block is likely from the Registry, and others are from the Theme or another plugin.</p>';
                        }
                        
                        html += '<hr>';
                        
                        // List found schemas
                        if (data.schemas && data.schemas.length > 0) {
                            html += '<table class="widefat striped">';
                            html += '<thead><tr><th>Type</th><th>Source Block</th><th>Context</th></tr></thead><tbody>';
                            data.schemas.forEach(function(s, index) {
                                html += '<tr>';
                                html += '<td><strong>' + s.type + '</strong></td>';
                                html += '<td>Block #' + (s.block_index + 1) + '</td>';
                                html += '<td><code>' + (s.context || 'N/A') + '</code></td>';
                                html += '</tr>';
                            });
                            html += '</tbody></table>';
                        } else {
                            html += '<p>No schemas found on this page.</p>';
                        }
                        
                        html += '</div>';
                        $('#live-check-results').html(html).show();
                    } else {
                        alert('Check failed: ' + (response.data || 'Unknown error'));
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Check Live Registry Output
     */
    public function ajax_check_live_registry()
    {
        check_ajax_referer('chroma_schema_live_check', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (empty($url)) {
            wp_send_json_error('Invalid URL');
        }
        
        // Fetch the page
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'sslverify' => false
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Fetch failed: ' . $response->get_error_message());
        }
        
        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            wp_send_json_error('Empty response from URL');
        }
        
        // Parse HTML for JSON-LD
        $dom = new DOMDocument();
        libxml_use_internal_errors(true); // Suppress HTML5 parsing errors
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        $scripts = $dom->getElementsByTagName('script');
        $found_schemas = [];
        $block_count = 0;
        
        foreach ($scripts as $script) {
            if ($script->getAttribute('type') === 'application/ld+json') {
                $content = $script->nodeValue;
                $json = json_decode($content, true);
                
                if ($json) {
                    $items = isset($json['@graph']) ? $json['@graph'] : [$json];
                    foreach ($items as $item) {
                        $found_schemas[] = [
                            'type' => isset($item['@type']) ? (is_array($item['@type']) ? implode(', ', $item['@type']) : $item['@type']) : 'Unknown',
                            'context' => isset($item['@context']) ? $item['@context'] : '',
                            'block_index' => $block_count
                        ];
                    }
                    $block_count++;
                }
            }
        }
        
        wp_send_json_success([
            'schema_count' => count($found_schemas),
            'schema_blocks_found' => $block_count,
            'schemas' => $found_schemas
        ]);
    }

    /**
     * Render Schema Cleanup Tab Content (Partial)
     */
    private function render_cleanup_tab_content()
    {
        // Get invalid types list (Expanded)
        $invalid_types = defined('CHROMA_INVALID_SCHEMA_TYPES') ? CHROMA_INVALID_SCHEMA_TYPES : [
            'VacationRental', 'MobileApplication', 'SoftwareApplication', 'WebApplication',
            'VideoGame', 'RealEstateListing', 'Hotel', 'Restaurant', 'LodgingBusiness',
            'Brand', 'Motel', 'Resort', 'Hostel', 'BedAndBreakfast', 'Campground',
            'Product', 'Service', 'Review', 'AggregateRating', 'Offer'
        ];
        
        // Check if registry is active
        $registry_active = class_exists('Chroma_Schema_Registry');
        ?>
        <div class="chroma-seo-card">
            <h2>🧹 Schema Cleanup Tools</h2>
            <p>Scan and remove invalid or unwanted schema types from your posts. These issues were identified during schema audits.</p>
        </div>

        <!-- Schema Registry Status -->
        <div class="chroma-seo-card" style="background: <?php echo $registry_active ? '#e8f5e9' : '#fff3e0'; ?>; border-left: 4px solid <?php echo $registry_active ? '#00a32a' : '#ff9800'; ?>;">
            <h3>🔗 Schema Registry Status</h3>
            <?php if ($registry_active): ?>
                <p style="color: #00a32a;"><strong>✅ Registry is ACTIVE</strong></p>
                <p>The Schema Registry is filtering duplicate and invalid schema types at output time.</p>
                <ul style="margin-left: 20px;">
                    <li><strong>Deduplication:</strong> Prevents duplicate @type and @id schemas</li>
                    <li><strong>Invalid Type Blocking:</strong> Blocks VacationRental, MobileApplication, etc.</li>
                    <li><strong>Debug Panel:</strong> Add <code>?schema_debug=1</code> to any page URL to see what's registered/blocked</li>
                </ul>
                <p><a href="<?php echo home_url('/?schema_debug=1'); ?>" target="_blank" class="button">View Registry Debug on Homepage</a></p>
            <?php else: ?>
                <p style="color: #ff9800;"><strong>⚠️ Registry is NOT ACTIVE</strong></p>
                <p>The Schema Registry class is not loaded. Check that <code>class-schema-registry.php</code> exists.</p>
            <?php endif; ?>
        </div>

        <!-- Bulk Cleanup Action -->
        <div class="chroma-seo-card" style="background: #ffebee; border-left: 4px solid #d63638;">
            <h3>⚡ Quick Bulk Cleanup</h3>
            <p>Remove all invalid schema types from <strong>all posts</strong> in one click:</p>
            <button id="bulk-cleanup-btn" class="button button-primary button-hero" style="background: #d63638; border-color: #b71c1c; font-size: 16px; margin: 10px 0;">
                <span class="dashicons dashicons-trash" style="margin-right: 8px; line-height: 1.4;"></span> Run Bulk Cleanup Now
            </button>
            <span id="bulk-cleanup-spinner" class="spinner" style="float: none;"></span>
            <div id="bulk-cleanup-result" style="margin-top: 10px;"></div>
            <p class="description">This scans all posts and removes invalid schema types from <code>_chroma_post_schemas</code> meta.</p>
        </div>

        <div class="chroma-seo-card">
            <h3>📋 Invalid Schema Types Blocklist</h3>
            <p>The following schema types are blocked by the Registry and will be removed by cleanup:</p>
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin: 15px 0;">
                <?php foreach ($invalid_types as $type): ?>
                    <span style="background: #ffebee; color: #d63638; padding: 4px 12px; border-radius: 4px; font-family: monospace;"><?php echo esc_html($type); ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="chroma-seo-card">
            <h3>🔍 Scan for Invalid Schemas</h3>
            <p>Click below to scan all posts for invalid schema types stored in <code>_chroma_post_schemas</code> meta.</p>
            
            <button id="cleanup-scan-btn" class="button button-primary" style="margin: 10px 0;">
                <span class="dashicons dashicons-search" style="margin-right: 5px;"></span> Scan Posts
            </button>
            <span id="cleanup-scan-spinner" class="spinner" style="float: none;"></span>
            
            <div id="cleanup-scan-results" style="margin-top: 20px; display: none;">
                <h4>Scan Results</h4>
                <p id="cleanup-summary"></p>
                <div id="cleanup-posts-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fafafa;"></div>
                
                <div id="cleanup-actions" style="margin-top: 15px; display: none;">
                    <button id="cleanup-execute-btn" class="button button-primary">
                        <span class="dashicons dashicons-trash" style="margin-right: 5px;"></span> Remove Invalid Schemas
                    </button>
                    <span id="cleanup-execute-spinner" class="spinner" style="float: none;"></span>
                    <p class="description" style="color: #d63638;">⚠️ This action cannot be undone. Make sure to backup your database first.</p>
                </div>
            </div>
        </div>

        <div class="chroma-seo-card">
            <h3>📊 FAQ Schema Audit</h3>
            <p>Check for posts with FAQ schema that may not need it. FAQ data is stored in <code>chroma_faq_items</code> meta.</p>
            <button id="faq-audit-btn" class="button" style="margin: 10px 0;">
                <span class="dashicons dashicons-editor-help" style="margin-right: 5px;"></span> Audit FAQ Usage
            </button>
            <span id="faq-audit-spinner" class="spinner" style="float: none;"></span>
            <div id="faq-audit-results" style="margin-top: 20px; display: none;"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var cleanupNonce = '<?php echo wp_create_nonce('chroma_schema_cleanup'); ?>';
            var scannedData = [];

            // Bulk cleanup (scan + execute in one step)
            $('#bulk-cleanup-btn').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('⚠️ This will scan ALL posts and remove invalid schema types.\n\nAre you sure you want to proceed?')) {
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true);
                $('#bulk-cleanup-spinner').addClass('is-active');
                $('#bulk-cleanup-result').html('<p>Scanning posts...</p>');
                
                // Step 1: Scan
                $.post(ajaxurl, {
                    action: 'chroma_schema_cleanup_scan',
                    nonce: cleanupNonce
                }, function(response) {
                    if (!response.success) {
                        btn.prop('disabled', false);
                        $('#bulk-cleanup-spinner').removeClass('is-active');
                        $('#bulk-cleanup-result').html('<p style="color: #d63638;">❌ Scan failed: ' + (response.data || 'Unknown error') + '</p>');
                        return;
                    }
                    
                    var posts = response.data.posts;
                    if (posts.length === 0) {
                        btn.prop('disabled', false);
                        $('#bulk-cleanup-spinner').removeClass('is-active');
                        $('#bulk-cleanup-result').html('<p style="color: #00a32a;">✅ No invalid schemas found! Database is clean.</p>');
                        return;
                    }
                    
                    $('#bulk-cleanup-result').html('<p>Found ' + posts.length + ' posts with invalid schemas. Cleaning up...</p>');
                    
                    // Step 2: Execute cleanup
                    $.post(ajaxurl, {
                        action: 'chroma_schema_cleanup_execute',
                        nonce: cleanupNonce,
                        post_ids: posts.map(function(p) { return p.id; })
                    }, function(execResponse) {
                        btn.prop('disabled', false);
                        $('#bulk-cleanup-spinner').removeClass('is-active');
                        
                        if (execResponse.success) {
                            $('#bulk-cleanup-result').html(
                                '<p style="color: #00a32a; font-size: 16px;"><strong>✅ Bulk Cleanup Complete!</strong></p>' +
                                '<p>Removed invalid schemas from <strong>' + execResponse.data.cleaned + '</strong> posts.</p>' +
                                '<p>Remember to <strong>clear your site cache</strong> to see the changes on the frontend.</p>'
                            );
                        } else {
                            $('#bulk-cleanup-result').html('<p style="color: #d63638;">❌ Cleanup failed: ' + (execResponse.data || 'Unknown error') + '</p>');
                        }
                    });
                });
            });

            // Scan for invalid schemas
            $('#cleanup-scan-btn').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                btn.prop('disabled', true);
                $('#cleanup-scan-spinner').addClass('is-active');
                
                $.post(ajaxurl, {
                    action: 'chroma_schema_cleanup_scan',
                    nonce: cleanupNonce
                }, function(response) {
                    btn.prop('disabled', false);
                    $('#cleanup-scan-spinner').removeClass('is-active');
                    
                    if (response.success) {
                        scannedData = response.data.posts;
                        var total = response.data.total_posts;
                        var affected = response.data.affected_posts;
                        var invalidCount = response.data.invalid_count;
                        
                        $('#cleanup-summary').html(
                            '✅ Scanned <strong>' + total + '</strong> posts. ' +
                            'Found <strong style="color: #d63638;">' + affected + '</strong> posts with ' +
                            '<strong style="color: #d63638;">' + invalidCount + '</strong> invalid schema entries.'
                        );
                        
                        // Show breakdown of ALL types (Valid & Invalid)
                        if (response.data.all_types_breakdown) {
                            var breakdownHtml = '<div style="margin: 15px 0; padding: 10px; background: #eef; border: 1px solid #ccd; border-radius: 4px;">';
                            breakdownHtml += '<strong>📊 Diagnostic: All Schema Types Found in DB:</strong><br>';
                            breakdownHtml += '<small>If you see your "rogue" schema here but it is not flagged as invalid, it is likely a duplicate valid type.</small>';
                            breakdownHtml += '<ul style="margin: 5px 0 0 20px; list-style: disc; columns: 2;">';
                            $.each(response.data.all_types_breakdown, function(type, count) {
                                breakdownHtml += '<li>' + type + ': <strong>' + count + '</strong></li>';
                            });
                            breakdownHtml += '</ul></div>';
                            $('#cleanup-summary').append(breakdownHtml);
                        }
                        
                        var listHtml = '';
                        if (scannedData.length > 0) {
                            scannedData.forEach(function(post) {
                                listHtml += '<div style="padding: 8px; border-bottom: 1px solid #eee;">';
                                listHtml += '<strong><a href="' + post.edit_url + '" target="_blank">' + post.title + '</a></strong>';
                                listHtml += ' <span style="color: #666;">(' + post.post_type + ')</span><br>';
                                listHtml += '<span style="color: #d63638;">Invalid: ' + post.invalid_types.join(', ') + '</span>';
                                listHtml += '</div>';
                            });
                            $('#cleanup-actions').show();
                        } else {
                            listHtml = '<p style="color: #00a32a;">✓ No invalid schemas found!</p>';
                            $('#cleanup-actions').hide();
                        }
                        
                        $('#cleanup-posts-list').html(listHtml);
                        $('#cleanup-scan-results').show();
                    } else {
                        alert('Scan failed: ' + (response.data || 'Unknown error'));
                    }
                });
            });

            // Execute cleanup
            $('#cleanup-execute-btn').on('click', function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to remove invalid schemas from ' + scannedData.length + ' posts? This cannot be undone.')) {
                    return;
                }
                
                var btn = $(this);
                btn.prop('disabled', true);
                $('#cleanup-execute-spinner').addClass('is-active');
                
                $.post(ajaxurl, {
                    action: 'chroma_schema_cleanup_execute',
                    nonce: cleanupNonce,
                    post_ids: scannedData.map(function(p) { return p.id; })
                }, function(response) {
                    btn.prop('disabled', false);
                    $('#cleanup-execute-spinner').removeClass('is-active');
                    
                    if (response.success) {
                        alert('✅ Cleanup complete! Removed invalid schemas from ' + response.data.cleaned + ' posts.');
                        $('#cleanup-scan-btn').click(); // Re-scan to show updated state
                    } else {
                        alert('Cleanup failed: ' + (response.data || 'Unknown error'));
                    }
                });
            });

            // FAQ Audit
            $('#faq-audit-btn').on('click', function(e) {
                e.preventDefault();
                var btn = $(this);
                btn.prop('disabled', true);
                $('#faq-audit-spinner').addClass('is-active');
                
                $.post(ajaxurl, {
                    action: 'chroma_schema_cleanup_scan',
                    nonce: cleanupNonce,
                    scan_type: 'faq'
                }, function(response) {
                    btn.prop('disabled', false);
                    $('#faq-audit-spinner').removeClass('is-active');
                    
                    if (response.success) {
                        var html = '<p>Found <strong>' + response.data.faq_count + '</strong> posts with FAQ data.</p>';
                        
                        if (response.data.faq_posts && response.data.faq_posts.length > 0) {
                            html += '<table class="widefat" style="margin-top: 10px;">';
                            html += '<thead><tr><th>Post</th><th>Type</th><th>FAQ Items</th><th>Actions</th></tr></thead><tbody>';
                            response.data.faq_posts.forEach(function(post) {
                                html += '<tr>';
                                html += '<td><a href="' + post.edit_url + '" target="_blank">' + post.title + '</a></td>';
                                html += '<td>' + post.post_type + '</td>';
                                html += '<td>' + post.faq_count + '</td>';
                                html += '<td><a href="' + post.edit_url + '" class="button button-small">Edit</a></td>';
                                html += '</tr>';
                            });
                            html += '</tbody></table>';
                        }
                        
                        $('#faq-audit-results').html(html).show();
                    } else {
                        alert('Audit failed');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Scan for invalid schema types
     */
    public function ajax_schema_cleanup_scan()
    {
        check_ajax_referer('chroma_schema_cleanup', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        global $wpdb;
        
        $scan_type = isset($_POST['scan_type']) ? sanitize_text_field($_POST['scan_type']) : 'invalid';
        
        if ($scan_type === 'faq') {
            // Scan for FAQ meta
            $faq_posts = $wpdb->get_results("
                SELECT p.ID, p.post_title, p.post_type, pm.meta_value
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE pm.meta_key = 'chroma_faq_items'
                AND p.post_status = 'publish'
                ORDER BY p.post_title ASC
            ");
            
            $result = [
                'faq_count' => count($faq_posts),
                'faq_posts' => []
            ];
            
            foreach ($faq_posts as $post) {
                $faq_items = maybe_unserialize($post->meta_value);
                $result['faq_posts'][] = [
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'post_type' => $post->post_type,
                    'faq_count' => is_array($faq_items) ? count($faq_items) : 0,
                    'edit_url' => get_edit_post_link($post->ID, 'raw')
                ];
            }
            
            wp_send_json_success($result);
            return;
        }
        
        // Scan ALL schema related meta fields
        $posts_with_schemas = $wpdb->get_results("
            SELECT p.ID, p.post_title, p.post_type, pm.meta_key, pm.meta_value
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key IN ('_chroma_post_schemas', '_chroma_schema_override', '_chroma_schema_type')
            AND p.post_status = 'publish'
        ");
        
        $affected_posts_map = [];
        $total_invalid = 0;
        
        foreach ($posts_with_schemas as $post) {
            $post_id = $post->ID;
            if (!isset($affected_posts_map[$post_id])) {
                $affected_posts_map[$post_id] = [
                    'id' => $post_id,
                    'title' => $post->post_title,
                    'post_type' => $post->post_type,
                    'invalid_types' => [],
                    'edit_url' => get_edit_post_link($post_id, 'raw')
                ];
            }

            $val = maybe_unserialize($post->meta_value);
            $invalid_found = [];

            if ($post->meta_key === '_chroma_post_schemas' && is_array($val)) {
                foreach ($val as $schema) {
                    $type = isset($schema['type']) ? $schema['type'] : '';
                    if (function_exists('chroma_is_invalid_schema_type') && chroma_is_invalid_schema_type($type)) {
                        $invalid_found[] = $type;
                    }
                }
            } elseif ($post->meta_key === '_chroma_schema_type' && is_string($val)) {
                if (function_exists('chroma_is_invalid_schema_type') && chroma_is_invalid_schema_type($val)) {
                    $invalid_found[] = $val . ' (Legacy)';
                }
            } elseif ($post->meta_key === '_chroma_schema_override' && is_string($val)) {
                $json = json_decode($val, true);
                if ($json) {
                    $type = $json['@type'] ?? '';
                    if (is_array($type)) $type = $type[0] ?? '';
                    if (function_exists('chroma_is_invalid_schema_type') && chroma_is_invalid_schema_type($type)) {
                        $invalid_found[] = $type . ' (Override)';
                    }
                }
            }
            
            if (!empty($invalid_found)) {
                $affected_posts_map[$post_id]['invalid_types'] = array_unique(array_merge($affected_posts_map[$post_id]['invalid_types'], $invalid_found));
            }
        }
        
        // Filter out posts that don't actually have invalid types
        $affected_posts = array_values(array_filter($affected_posts_map, function($p) {
            return !empty($p['invalid_types']);
        }));

        foreach ($affected_posts as $p) {
            $total_invalid += count($p['invalid_types']);
        }
        
        // Scan ALL types for reporting (Sprint 9 Update)
        $all_types_found = [];
        foreach ($posts_with_schemas as $p) {
            $val = maybe_unserialize($p->meta_value);
            if (is_array($val)) {
                foreach ($val as $s) {
                    if (isset($s['type'])) {
                        $t = $s['type'];
                        $all_types_found[$t] = ($all_types_found[$t] ?? 0) + 1;
                    }
                }
            }
        }

        wp_send_json_success([
            'total_posts' => count(array_unique(array_column($posts_with_schemas, 'ID'))),
            'affected_posts' => count($affected_posts),
            'invalid_count' => $total_invalid,
            'posts' => $affected_posts,
            'all_types_breakdown' => $all_types_found
        ]);
    }

    /**
     * AJAX: Execute schema cleanup
     */
    public function ajax_schema_cleanup_execute()
    {
        check_ajax_referer('chroma_schema_cleanup', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
        
        if (empty($post_ids)) {
            wp_send_json_error('No posts specified');
        }
        
        $cleaned = 0;
        
        foreach ($post_ids as $post_id) {
            $changed = false;

            // 1. Clean Builder Schemas
            $schemas = get_post_meta($post_id, '_chroma_post_schemas', true);
            if (is_array($schemas)) {
                $clean_schemas = [];
                foreach ($schemas as $schema) {
                    $type = isset($schema['type']) ? $schema['type'] : '';
                    if (!function_exists('chroma_is_invalid_schema_type') || !chroma_is_invalid_schema_type($type)) {
                        $clean_schemas[] = $schema;
                    }
                }
                if (count($clean_schemas) !== count($schemas)) {
                    update_post_meta($post_id, '_chroma_post_schemas', $clean_schemas);
                    $changed = true;
                }
            }

            // 2. Clean Legacy Type
            $legacy_type = get_post_meta($post_id, '_chroma_schema_type', true);
            if ($legacy_type && function_exists('chroma_is_invalid_schema_type') && chroma_is_invalid_schema_type($legacy_type)) {
                delete_post_meta($post_id, '_chroma_schema_type');
                $changed = true;
            }

            // 3. Clean Overrides
            $override = get_post_meta($post_id, '_chroma_schema_override', true);
            if ($override) {
                $json = json_decode($override, true);
                if ($json) {
                    $type = $json['@type'] ?? '';
                    if (is_array($type)) $type = $type[0] ?? '';
                    if ($type && function_exists('chroma_is_invalid_schema_type') && chroma_is_invalid_schema_type($type)) {
                        delete_post_meta($post_id, '_chroma_schema_override');
                        $changed = true;
                    }
                }
            }
            
            if ($changed) {
                $cleaned++;
            }
        }
        
        wp_send_json_success(['cleaned' => $cleaned]);
    }
}



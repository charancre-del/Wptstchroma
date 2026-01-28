<?php
/**
 * Uninstall Script
 *
 * Runs when the plugin is deleted via WordPress admin.
 *
 * @package ChromaQAReports
 */

// Exit if accessed directly or not uninstalling
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Run cleanup for a single site.
 */
function cqa_uninstall_site()
{
    global $wpdb;

    // Delete custom tables
    $tables = [
        $wpdb->prefix . 'cqa_schools',
        $wpdb->prefix . 'cqa_reports',
        $wpdb->prefix . 'cqa_responses',
        $wpdb->prefix . 'cqa_photos',
        $wpdb->prefix . 'cqa_ai_summaries',
    ];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    // Delete options
    $options = [
        'cqa_google_client_id',
        'cqa_google_client_secret',
        'cqa_gemini_api_key',
        'cqa_drive_root_folder',
        'cqa_company_name',
        'cqa_reports_per_school',
        'cqa_db_version',
        'cqa_sso_domain',
        'cqa_sso_default_role',
    ];

    foreach ($options as $option) {
        delete_option($option);
    }

    // Clean up user meta (Note: user meta is global, but we use site-specific prefixes if needed? 
    // Actually cqa_ is global in this plugin's implementation usually. 
    // We'll clean it once per site or once globally.)
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'cqa_%'");

    // Delete temp files
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/cqa-temp';

    if (is_dir($temp_dir)) {
        $files = glob($temp_dir . '/*');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        rmdir($temp_dir);
    }
}

global $wpdb;

if (is_multisite()) {
    $site_ids = get_sites(['fields' => 'ids', 'number' => 0]);
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);
        cqa_uninstall_site();
        restore_current_blog();
    }
} else {
    cqa_uninstall_site();
}

// Remove custom roles (System-wide if possible, but WP roles are often per-site in meta)
// administrator role capabilities are handled per site in cqa_uninstall_site via switch_to_blog conceptually?
// Actually roles are per-site mostly.
remove_role('cqa_super_admin');
remove_role('cqa_regional_director');
remove_role('cqa_qa_officer');
remove_role('cqa_program_manager');

// Flush rewrite rules
flush_rewrite_rules();

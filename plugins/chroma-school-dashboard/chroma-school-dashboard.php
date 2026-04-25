<?php
/**
 * Plugin Name: Chroma School Dashboard
 * Description: TV Dashboard and Director Portal API for Chroma Early Learning.
 * Version: 1.0.0
 * Author: Chroma
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CHROMA_SCHOOL_DB_PATH', plugin_dir_path(__FILE__));
define('CHROMA_SCHOOL_DB_URL', plugin_dir_url(__FILE__));
define('CHROMA_SCHOOL_VERSION', '1.0.3');

// Autoloader or includes
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-post-type.php';
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-api-routes.php';
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-template-loader.php';
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-portal-loader.php';
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-weather.php';
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-admin-settings.php';
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-media-permissions.php';

// Initialize
function chroma_school_dashboard_init()
{
    // Register everything early. Instantiating here (plugins_loaded) 
    // ensures sub-components can hook into 'init' or 'query_vars' reliably.
    new Chroma_School_Post_Type();
    new Chroma_School_API_Routes();
    new Chroma_School_Template_Loader();
    new Chroma_School_Portal_Loader();

    // Heavy UI and Admin logic
    if (is_admin()) {
        new Chroma_School_Admin_Settings();
        new Chroma_Media_Permissions();
    }
}
add_action('plugins_loaded', 'chroma_school_dashboard_init');

// Ensure rules are flushed on version increment
function chroma_school_maybe_flush()
{
    $current_ver = '1.0.3'; // Increment to force flush
    if (get_option('chroma_school_version') !== $current_ver) {
        flush_rewrite_rules(true); // Hard flush to be sure
        update_option('chroma_school_version', $current_ver);
        error_log('Chroma School Dashboard: Rewrite rules flushed to version ' . $current_ver);
    }
}
add_action('init', 'chroma_school_maybe_flush', 999);

register_activation_hook(__FILE__, 'chroma_school_flush_rules');
function chroma_school_flush_rules()
{
    chroma_school_dashboard_init();
    flush_rewrite_rules();
}

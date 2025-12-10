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

// Autoloader or includes
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-post-type.php';
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-api-routes.php';
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-template-loader.php';
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-portal-loader.php';
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-weather.php';
require_once CHROMA_SCHOOL_DB_PATH . 'inc/class-admin-settings.php';

// Initialize
function chroma_school_dashboard_init()
{
    new Chroma_School_Post_Type();
    new Chroma_School_API_Routes();
    new Chroma_School_Template_Loader();
    new Chroma_School_Portal_Loader();
    new Chroma_School_Admin_Settings();
}
add_action('plugins_loaded', 'chroma_school_dashboard_init');

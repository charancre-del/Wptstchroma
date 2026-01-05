<?php
/**
 * Plugin Name: Chroma SEO Pro
 * Plugin URI:  https://chroma.com
 * Description: Advanced AI-powered Schema validation, automated fixes, and SEO enhancements for WordPress.
 * Version:     1.0.0
 * Author:      Chroma
 * Author URI:  https://chroma.com
 * Text Domain: chroma-seo-pro
 * License:     GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants
define( 'CHROMA_SEO_VERSION', '1.0.0' );
define( 'CHROMA_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'CHROMA_SEO_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize the Plugin
 */
function chroma_seo_init() {
    // Load Bootstrap (handles all includes and hooks)
    require_once CHROMA_SEO_PATH . 'inc/bootstrap.php';
    
    // Initialize Dashboard (if not already done by bootstrap)
    if ( class_exists( 'Chroma_SEO_Dashboard' ) ) {
        // bootstrap.php calls chroma_advanced_seo_init() on 'init' hook, 
        // but we are on 'plugins_loaded'. 
        // bootstrap.php adds its own 'init' hook.
        // So we just need to require the file.
    }
}
add_action( 'plugins_loaded', 'chroma_seo_init' );

/**
 * Activation Hook
 */
function chroma_seo_activate() {
    // Load bootstrap to get class definitions
    require_once CHROMA_SEO_PATH . 'inc/bootstrap.php';
    
    // Register multilingual rewrite rules
    if (class_exists('Chroma_Multilingual_Manager')) {
        $manager = new Chroma_Multilingual_Manager();
        $manager->setup_rewrites();
    }
    
    // Flush rewrite rules to apply changes
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'chroma_seo_activate' );

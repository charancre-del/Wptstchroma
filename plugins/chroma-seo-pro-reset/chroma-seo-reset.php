<?php
/**
 * Plugin Name: Chroma SEO Pro
 * Plugin URI:  https://chroma.com
 * Description: Advanced AI-powered Schema validation, automated fixes, and SEO enhancements for WordPress.
 * Version:     1.0.1
 * Author:      Chroma
 * Author URI:  https://chroma.com
 * Text Domain: chroma-seo-pro
 * License:     GPLv2 or later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants
define( 'CHROMA_SEO_VERSION', '1.0.1' );
define( 'CHROMA_SEO_PATH', plugin_dir_path( __FILE__ ) );
define( 'CHROMA_SEO_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin textdomain for translations.
 */
function chroma_seo_pro_load_textdomain() {
    load_plugin_textdomain(
        'chroma-seo-pro',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('plugins_loaded', 'chroma_seo_pro_load_textdomain', 5);

/**
 * Initialize the Plugin
 */
function chroma_seo_init() {
    try {
        // Load Bootstrap (handles all includes and hooks)
        require_once CHROMA_SEO_PATH . 'inc/bootstrap.php';
    } catch (Throwable $e) {
        // Catch any PHP errors and show admin notice instead of crashing
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p><strong>Chroma SEO Pro Error:</strong> ' . esc_html($e->getMessage()) . ' in ' . esc_html($e->getFile()) . ' on line ' . esc_html($e->getLine()) . '</p></div>';
        });
        return;
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

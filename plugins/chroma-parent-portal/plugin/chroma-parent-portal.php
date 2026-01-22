<?php
/**
 * Plugin Name: Chroma Parent Portal
 * Description: Premium Parent Portal with React Frontend and PIN Authentication.
 * Version: 1.0.0
 * Author: Chroma Excellence
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CHROMA_PORTAL_VERSION', '1.0.0' );
define( 'CHROMA_PORTAL_PATH', plugin_dir_path( __FILE__ ) );
define( 'CHROMA_PORTAL_URL', plugin_dir_url( __FILE__ ) );

// Load Backend Components
require_once CHROMA_PORTAL_PATH . 'includes/class-cpt-registrar.php';
require_once CHROMA_PORTAL_PATH . 'includes/class-meta-boxes.php';
require_once CHROMA_PORTAL_PATH . 'includes/class-api-routes.php';

// Activation Hook
register_activation_hook( __FILE__, function() {
    if ( ! get_page_by_path( 'parent-portal' ) ) {
        wp_insert_post( [
            'post_title'   => 'Parent Portal',
            'post_name'    => 'parent-portal',
            'post_content' => '[chroma_parent_portal]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ] );
    }
} );

// Register Assets
add_action( 'wp_enqueue_scripts', function() {
    // Only load on the specific page or if shortcode is present
    $post = get_post();
    if ( ! $post || ( ! is_page( 'parent-portal' ) && ! has_shortcode( $post->post_content, 'chroma_parent_portal' ) ) ) {
        return;
    }

    $asset_file_path = CHROMA_PORTAL_PATH . 'build/index.asset.php';
    if ( ! file_exists( $asset_file_path ) ) {
        return;
    }
    
    $asset_file = include $asset_file_path;

    wp_register_script(
        'chroma-portal-app',
        CHROMA_PORTAL_URL . 'build/index.js',
        $asset_file['dependencies'],
        $asset_file['version'],
        true
    );

    wp_register_style(
        'chroma-portal-styles',
        CHROMA_PORTAL_URL . 'build/index.css',
        [],
        CHROMA_PORTAL_VERSION
    );

    wp_localize_script( 'chroma-portal-app', 'chromaPortalSettings', [
        'root' => esc_url_raw( rest_url() ),
        'nonce' => wp_create_nonce( 'wp_rest' ),
        'assetsUrl' => CHROMA_PORTAL_URL . 'build/'
    ] );
} );

// Shortcode
add_shortcode( 'chroma_parent_portal', function() {
    wp_enqueue_script( 'chroma-portal-app' );
    wp_enqueue_style( 'chroma-portal-styles' );
    return '<div id="chroma-parent-portal-root"></div>';
} );

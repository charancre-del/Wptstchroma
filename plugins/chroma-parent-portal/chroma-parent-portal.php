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

// Force Viewport for Full App feel
add_action('wp_head', function() {
    if ( is_page('parent-portal') ) {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">';
    }
});

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
    $post = get_post();
    // Broaden check: If it's the specific page OR has shortcode
    $is_portal_page = is_page( 'parent-portal' );
    $has_shortcode = $post && has_shortcode( $post->post_content, 'chroma_parent_portal' );

    if ( ! $is_portal_page && ! $has_shortcode ) {
        return;
    }

    $asset_file_path = CHROMA_PORTAL_PATH . 'build/index.asset.php';
    if ( ! file_exists( $asset_file_path ) ) {
        return;
    }
    
    $asset_file = include $asset_file_path;

    wp_enqueue_script(
        'chroma-portal-app',
        CHROMA_PORTAL_URL . 'build/index.js',
        $asset_file['dependencies'],
        $asset_file['version'],
        true
    );

    wp_enqueue_style(
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

// Add Body Class for Full App Mode
add_filter( 'body_class', function( $classes ) {
    if ( is_page( 'parent-portal' ) ) {
        $classes[] = 'portal-is-active';
    }
    return $classes;
} );

// Shortcode
add_shortcode( 'chroma_parent_portal', function() {
    wp_enqueue_script( 'chroma-portal-app' );
    wp_enqueue_style( 'chroma-portal-styles' );
    return '<div id="chroma-parent-portal-root"></div>';
} );

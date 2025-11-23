<?php
/**
 * Enqueue Scripts and Styles
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue theme styles and scripts
 */
function chroma_enqueue_assets() {
	// Google Fonts with preconnect
	?>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<?php

	wp_enqueue_style(
		'chroma-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap',
		array(),
		null
	);

	// Font Awesome
	wp_enqueue_style(
		'font-awesome',
		'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
		array(),
		'6.4.0'
	);

	// Compiled Tailwind CSS
	$css_path = CHROMA_THEME_DIR . '/assets/css/main.css';
	$css_version = file_exists( $css_path ) ? filemtime( $css_path ) : CHROMA_VERSION;

	wp_enqueue_style(
		'chroma-main',
		CHROMA_THEME_URI . '/assets/css/main.css',
		array(),
		$css_version
	);

	// Main JavaScript
	$js_path = CHROMA_THEME_DIR . '/assets/js/main.js';
	$js_version = file_exists( $js_path ) ? filemtime( $js_path ) : CHROMA_VERSION;

	wp_enqueue_script(
		'chroma-main',
		CHROMA_THEME_URI . '/assets/js/main.js',
		array(),
		$js_version,
		true
	);

	// Leaflet for maps (only on location pages)
	if ( is_post_type_archive( 'location' ) || is_singular( 'location' ) || is_page( 'locations' ) ) {
		wp_enqueue_style(
			'leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
			array(),
			'1.9.4'
		);

		wp_enqueue_script(
			'leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			array(),
			'1.9.4',
			true
		);

		wp_enqueue_script(
			'chroma-map-layer',
			CHROMA_THEME_URI . '/assets/js/map-layer.js',
			array( 'leaflet' ),
			$js_version,
			true
		);
	}

	// Localize script for AJAX and dynamic data
	wp_localize_script(
		'chroma-main',
		'chromaData',
		array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'chroma_nonce' ),
			'themeUrl' => CHROMA_THEME_URI,
			'homeUrl'  => home_url(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'chroma_enqueue_assets' );

/**
 * Remove unnecessary WordPress assets
 */
function chroma_remove_unnecessary_assets() {
	// Remove jQuery Migrate
	if ( ! is_admin() ) {
		wp_deregister_script( 'jquery' );
		wp_register_script( 'jquery', includes_url( '/js/jquery/jquery.min.js' ), false, null, true );
	}

	// Remove wp-embed.js
	wp_deregister_script( 'wp-embed' );

	// Remove emoji scripts
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );

	// Remove block library CSS (we use Tailwind)
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-blocks-style' );
}
add_action( 'wp_enqueue_scripts', 'chroma_remove_unnecessary_assets', 100 );

/**
 * Remove query strings from static resources
 */
function chroma_remove_script_version( $src ) {
	if ( strpos( $src, '?ver=' ) ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
}
add_filter( 'script_loader_src', 'chroma_remove_script_version', 15, 1 );
add_filter( 'style_loader_src', 'chroma_remove_script_version', 15, 1 );

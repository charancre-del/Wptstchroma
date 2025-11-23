<?php
/**
 * Enqueue Scripts and Styles
 *
 * @package Chroma_ELA
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
	// Google Fonts
	wp_enqueue_style(
		'chroma-fonts',
		'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap',
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

	// Tailwind CSS (compiled)
	wp_enqueue_style(
		'chroma-tailwind',
		CHROMA_THEME_URI . '/dist/app.css',
		array(),
		CHROMA_VERSION
	);

	// Theme stylesheet (for WordPress recognition only)
	wp_enqueue_style(
		'chroma-style',
		get_stylesheet_uri(),
		array( 'chroma-tailwind' ),
		CHROMA_VERSION
	);

	// Chart.js for curriculum radar
	wp_enqueue_script(
		'chartjs',
		'https://cdn.jsdelivr.net/npm/chart.js',
		array(),
		'4.4.0',
		true
	);

	// Theme JavaScript (compiled)
	wp_enqueue_script(
		'chroma-app',
		CHROMA_THEME_URI . '/dist/app.js',
		array( 'chartjs' ),
		CHROMA_VERSION,
		true
	);

	// Localize script for AJAX and dynamic data
	wp_localize_script(
		'chroma-app',
		'chromaData',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'chroma_nonce' ),
			'themeUrl' => CHROMA_THEME_URI,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'chroma_enqueue_assets' );

/**
 * Remove WordPress emoji scripts
 */
function chroma_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'chroma_disable_emojis' );

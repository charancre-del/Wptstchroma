<?php
/**
 * Chroma Early Learning Academy Theme Functions
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define theme constants
 */
define( 'CHROMA_VERSION', '1.0.0' );
define( 'CHROMA_THEME_DIR', get_template_directory() );
define( 'CHROMA_THEME_URI', get_template_directory_uri() );

/**
 * Load core theme files
 */
require CHROMA_THEME_DIR . '/inc/setup.php';
require CHROMA_THEME_DIR . '/inc/enqueue.php';
require CHROMA_THEME_DIR . '/inc/nav-menus.php';
require CHROMA_THEME_DIR . '/inc/cpt-program.php';
require CHROMA_THEME_DIR . '/inc/cpt-location.php';
require CHROMA_THEME_DIR . '/inc/cpt-team.php';
require CHROMA_THEME_DIR . '/inc/acf.php';
require CHROMA_THEME_DIR . '/inc/seo.php';
require CHROMA_THEME_DIR . '/inc/patterns.php';

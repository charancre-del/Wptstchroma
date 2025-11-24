<?php
/**
 * Chroma Excellence Theme Functions
 *
 * @package Chroma_Excellence
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
 * Load core theme functionality
 * Order matters - load dependencies first
 */

// Core setup and configuration
require_once CHROMA_THEME_DIR . '/inc/setup.php';
require_once CHROMA_THEME_DIR . '/inc/enqueue.php';
require_once CHROMA_THEME_DIR . '/inc/nav-menus.php';

// Custom Post Types
require_once CHROMA_THEME_DIR . '/inc/cpt-programs.php';
require_once CHROMA_THEME_DIR . '/inc/cpt-locations.php';

// Utility Functions
require_once CHROMA_THEME_DIR . '/inc/template-tags.php';

// Legacy helper files (ACF plugin optional; helpers run on core WP functions only)
require_once CHROMA_THEME_DIR . '/inc/acf-options.php';
require_once CHROMA_THEME_DIR . '/inc/acf-homepage.php';

require_once CHROMA_THEME_DIR . '/inc/cleanup.php';

// SEO and Internationalization
require_once CHROMA_THEME_DIR . '/inc/seo-engine.php';
require_once CHROMA_THEME_DIR . '/inc/city-slug-logic.php';
require_once CHROMA_THEME_DIR . '/inc/spanish-variant-generator.php';
require_once CHROMA_THEME_DIR . '/inc/monthly-seo-cron.php';

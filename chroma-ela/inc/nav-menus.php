<?php
/**
 * Navigation Menus
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register navigation menus
 */
function chroma_register_menus() {
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'chroma-ela' ),
		'footer'  => __( 'Footer Menu', 'chroma-ela' ),
	) );
}
add_action( 'init', 'chroma_register_menus' );

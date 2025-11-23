<?php
/**
 * Theme Setup and Configuration
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme setup
 */
function chroma_theme_setup() {
	// Add default posts and comments RSS feed links to head
	add_theme_support( 'automatic-feed-links' );

	// Let WordPress manage the document title
	add_theme_support( 'title-tag' );

	// Enable support for Post Thumbnails
	add_theme_support( 'post-thumbnails' );

	// Add custom image sizes
	add_image_size( 'hero-large', 1920, 1080, true );
	add_image_size( 'location-featured', 800, 600, true );
	add_image_size( 'program-card', 600, 450, true );
	add_image_size( 'team-photo', 400, 400, true );

	// Switch default core markup to output valid HTML5
	add_theme_support( 'html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
		'style',
		'script',
	) );

	// Add theme support for selective refresh for widgets
	add_theme_support( 'customize-selective-refresh-widgets' );

	// Add support for editor styles
	add_theme_support( 'editor-styles' );

	// Add support for responsive embeds
	add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'chroma_theme_setup' );

/**
 * Set the content width in pixels
 */
function chroma_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'chroma_content_width', 1200 );
}
add_action( 'after_setup_theme', 'chroma_content_width', 0 );

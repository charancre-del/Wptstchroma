<?php
/**
 * Block Patterns and Pattern Categories
 *
 * @package Chroma_ELA
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register custom block pattern categories
 */
function chroma_register_pattern_categories() {
	if ( function_exists( 'register_block_pattern_category' ) ) {
		register_block_pattern_category(
			'chroma-hero',
			array( 'label' => __( 'Chroma Hero Sections', 'chroma-ela' ) )
		);

		register_block_pattern_category(
			'chroma-content',
			array( 'label' => __( 'Chroma Content Blocks', 'chroma-ela' ) )
		);
	}
}
add_action( 'init', 'chroma_register_pattern_categories' );

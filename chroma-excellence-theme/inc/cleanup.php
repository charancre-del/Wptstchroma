<?php
/**
 * WordPress Cleanup Functions
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disable comments on attachments
 */
function chroma_disable_attachment_comments( $open, $post_id ) {
	$post = get_post( $post_id );
	if ( $post && $post->post_type === 'attachment' ) {
		return false;
	}
	return $open;
}
add_filter( 'comments_open', 'chroma_disable_attachment_comments', 10, 2 );

/**
 * Redirect attachment pages to parent or home
 */
function chroma_redirect_attachment_pages() {
	if ( is_attachment() ) {
		global $post;
		if ( $post && $post->post_parent ) {
			wp_safe_redirect( get_permalink( $post->post_parent ), 301 );
		} else {
			wp_safe_redirect( home_url(), 301 );
		}
		exit;
	}
}
add_action( 'template_redirect', 'chroma_redirect_attachment_pages' );

/**
 * Disable author archives
 */
function chroma_disable_author_archives() {
	if ( is_author() ) {
		wp_safe_redirect( home_url(), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'chroma_disable_author_archives' );

/**
 * Disable XML-RPC
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * Remove WordPress version from head
 */
remove_action( 'wp_head', 'wp_generator' );

/**
 * Disable RSS feeds
 */
function chroma_disable_feeds() {
	wp_die( __( 'No feed available. Please visit the <a href="' . esc_url( home_url( '/' ) ) . '">homepage</a>!', 'chroma-excellence' ) );
}
add_action( 'do_feed', 'chroma_disable_feeds', 1 );
add_action( 'do_feed_rdf', 'chroma_disable_feeds', 1 );
add_action( 'do_feed_rss', 'chroma_disable_feeds', 1 );
add_action( 'do_feed_rss2', 'chroma_disable_feeds', 1 );
add_action( 'do_feed_atom', 'chroma_disable_feeds', 1 );

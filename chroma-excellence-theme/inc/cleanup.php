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

/**
 * Disable emojis to reduce extraneous HTTP requests and inline scripts.
 */
function chroma_disable_emojis() {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        add_filter( 'tiny_mce_plugins', 'chroma_disable_emojis_tinymce' );
        add_filter( 'wp_resource_hints', 'chroma_disable_emojis_dns_prefetch', 10, 2 );
}
add_action( 'init', 'chroma_disable_emojis' );

/**
 * Filter out the emoji plugin from TinyMCE.
 */
function chroma_disable_emojis_tinymce( $plugins ) {
        if ( is_array( $plugins ) ) {
                return array_diff( $plugins, array( 'wpemoji' ) );
        }

        return array();
}

/**
 * Remove emoji CDN DNS prefetch.
 */
function chroma_disable_emojis_dns_prefetch( $urls, $relation_type ) {
        if ( 'dns-prefetch' !== $relation_type ) {
                return $urls;
        }

        return array_filter(
                $urls,
                function ( $url ) {
                        return false === strpos( $url, 's.w.org/images/core/emoji/' );
                }
        );
}

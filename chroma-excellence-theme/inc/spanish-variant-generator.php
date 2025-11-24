<?php
/**
 * Spanish Variant Generator and Language Switcher
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect Current Language Heuristically
 * Based on URL patterns (no ACF required)
 */
function chroma_detect_current_language() {
	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	// Check if URL contains '/es/' or '-es'
	if ( strpos( $current_url, '/es/' ) !== false || strpos( $current_url, '-es' ) !== false ) {
		return 'es';
	}

	return 'en';
}

/**
 * Get Alternate URL
 */
function chroma_get_alternate_url( $target_lang = 'es' ) {
	$current_lang = chroma_detect_current_language();

	if ( $current_lang === $target_lang ) {
		return ''; // Already on target language
	}

        // Check post meta fields
        $alternate_en = get_post_meta( get_the_ID(), 'alternate_url_en', true );
        $alternate_es = get_post_meta( get_the_ID(), 'alternate_url_es', true );

	if ( $target_lang === 'es' && $alternate_es ) {
		return $alternate_es;
	}

	if ( $target_lang === 'en' && $alternate_en ) {
		return $alternate_en;
	}

	return '';
}

/**
 * Render Language Switcher
 */
function chroma_render_language_switcher() {
	$current_lang = chroma_detect_current_language();
	$alternate_url = chroma_get_alternate_url( $current_lang === 'en' ? 'es' : 'en' );

	if ( ! $alternate_url ) {
		return;
	}

	$label = $current_lang === 'en' ? 'Español' : 'English';

	?>
	<div class="chroma-language-switcher">
		<a href="<?php echo esc_url( $alternate_url ); ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-brand-navy/20 text-xs font-semibold hover:border-brand-navy transition">
			<i class="fa-solid fa-globe"></i>
			<span><?php echo esc_html( $label ); ?></span>
		</a>
	</div>
	<?php
}

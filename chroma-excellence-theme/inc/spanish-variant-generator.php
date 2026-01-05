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
 * Detect Current Language
 * Delegates to plugin if available, otherwise uses basic URL check.
 */
function chroma_detect_current_language() {
    // Plugin integration
    if (class_exists('Chroma_Multilingual_Manager')) {
        return Chroma_Multilingual_Manager::is_spanish() ? 'es' : 'en';
    }

	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	if ( strpos( $current_url, '/es/' ) !== false || strpos( $current_url, '-es' ) !== false ) {
		return 'es';
	}
	return 'en';
}

/**
 * Get Alternate URL
 * Delegates to plugin logic.
 */
function chroma_get_alternate_url( $target_lang = 'es' ) {
    // 1. Plugin integration
    if (function_exists('chroma_get_alternates')) {
        $alternates = chroma_get_alternates();
        // If plugin returns a value, verify it's not just the current post permalink (loopback)
        if (!empty($alternates[$target_lang])) {
             return $alternates[$target_lang];
        }
    }

    // 2. Manual Meta Field Logic (Robust)
    $obj_id = get_queried_object_id();
    
    // If we can't find an object ID (e.g. 404), fallback to home
    if (!$obj_id) {
         return ($target_lang === 'es') ? home_url('/es/') : home_url('/');
    }

    // Check manual meta override
    $meta_key = ($target_lang === 'es') ? 'alternate_url_es' : 'alternate_url_en';
    $manual_url = get_post_meta( $obj_id, $meta_key, true );
    
    if ($manual_url) {
        return $manual_url;
    }

    // 3. Automated Fallback Logic
    
    // Homepage Fallback
    if (is_front_page()) {
        return ($target_lang === 'es') ? home_url('/es/') : home_url('/');
    }

    // General Fallback (Try to construct URL structure if predictable, or just link to home counterpart)
    // For now, if no manual link exists for inner pages, we don't want to link to a random blog post.
    // Ensure we don't return garbage.
    
    // IF nothing found, default to Home (Spanish) or Home (English) so the button ALWAYS appears and is useful.
    return ($target_lang === 'es') ? home_url('/es/') : home_url('/');
}

/**
 * Render Language Switcher
 */
function chroma_render_language_switcher() {
	$current_lang = chroma_detect_current_language();
    $target_lang = ($current_lang === 'en') ? 'es' : 'en';
	$alternate_url = chroma_get_alternate_url( $target_lang );

	if ( ! $alternate_url ) {
		// Even if empty, force a fallback to Home so the switcher is always present
        $alternate_url = ($target_lang === 'es') ? home_url('/es/') : home_url('/');
	}

	$label = $current_lang === 'en' ? 'Español' : 'English';
    $flag = $current_lang === 'en' ? '🇪🇸' : '🇺🇸';

	?>
    <style>
    .chroma-language-switcher {
        display: inline-flex;
    }
    .chroma-language-switcher a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 9999px;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: 1px solid rgba(0, 0, 0, 0.1);
        color: #333;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    .chroma-language-switcher a:hover {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-color: rgba(0, 0, 0, 0.2);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    .chroma-language-switcher .lang-flag {
        font-size: 16px;
        line-height: 1;
    }
    .chroma-language-switcher .lang-label {
        letter-spacing: 0.025em;
    }
    /* RTL Support */
    [dir="rtl"] .chroma-language-switcher a {
        flex-direction: row-reverse;
    }
    /* Dark mode variant */
    .lang-es .chroma-language-switcher a,
    body.dark-mode .chroma-language-switcher a {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        color: #fff;
        border-color: rgba(255, 255, 255, 0.1);
    }
    body.dark-mode .chroma-language-switcher a:hover {
        background: linear-gradient(135deg, #16213e 0%, #0f3460 100%);
        border-color: rgba(255, 255, 255, 0.2);
    }
    </style>
	<div class="chroma-language-switcher">
		<a href="<?php echo esc_url( $alternate_url ); ?>">
            <span class="lang-flag"><?php echo $flag; ?></span>
			<span class="lang-label"><?php echo esc_html( $label ); ?></span>
		</a>
	</div>
	<?php
}

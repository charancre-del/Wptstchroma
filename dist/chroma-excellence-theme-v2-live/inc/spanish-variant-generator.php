<?php
/**
 * Spanish Variant Generator and Language Switcher
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Detect Current Language
 * Delegates to plugin if available, otherwise uses basic URL check.
 */
function chroma_detect_current_language()
{
    if (class_exists('Chroma_Multilingual_Manager')) {
        return Chroma_Multilingual_Manager::get_current_language();
    }
    return (strpos($_SERVER['REQUEST_URI'], '/es/') !== false) ? 'es' : 'en';
}

/**
 * Get Alternate URL
 * Delegates to plugin logic.
 */
function chroma_get_alternate_url($target_lang = 'es')
{
    if (function_exists('chroma_get_alternates')) {
        $alternates = chroma_get_alternates();
        return $alternates[$target_lang] ?? home_url('/');
    }
    return ($target_lang === 'es') ? home_url('/es/') : home_url('/');
}

/**
 * Render Language Switcher
 */
function chroma_render_language_switcher($context = null)
{
    $current_lang = chroma_detect_current_language();
    $target_lang = ($current_lang === 'en') ? 'es' : 'en';
    $alternate_url = chroma_get_alternate_url($target_lang);

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
    </style>
    <div class="chroma-language-switcher">
        <a href="<?php echo esc_url($alternate_url); ?>">
            <span class="lang-flag"><?php echo $flag; ?></span>
            <span class="lang-label"><?php echo esc_html($label); ?></span>
        </a>
    </div>
    <?php
}


<?php
/**
 * Global Scripts Customizer Settings
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Global Scripts Customizer Settings
 */
function chroma_scripts_customizer_settings($wp_customize)
{
    // Add Scripts Section
    $wp_customize->add_section('chroma_scripts_settings', array(
        'title' => __('Global Scripts', 'chroma-excellence'),
        'description' => __('Add custom scripts (Google Analytics, Pixels, etc.) to your site header and footer.', 'chroma-excellence'),
        'priority' => 120,
    ));

    // Header Scripts (wp_head)
    $wp_customize->add_setting('chroma_header_scripts', array(
        'default' => '',
        'sanitize_callback' => 'chroma_sanitize_scripts', // Custom callback to allow tags
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('chroma_header_scripts', array(
        'label' => __('Header Scripts (Head)', 'chroma-excellence'),
        'description' => __('These scripts will be printed in the &lt;head&gt; section. Use for Google Analytics, GTM, etc.', 'chroma-excellence'),
        'section' => 'chroma_scripts_settings',
        'type' => 'textarea',
        'input_attrs' => array(
            'class' => 'code', // specific font for code
            'rows' => 10,
        ),
    ));

    // Footer Scripts (wp_footer)
    $wp_customize->add_setting('chroma_footer_scripts', array(
        'default' => '',
        'sanitize_callback' => 'chroma_sanitize_scripts',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('chroma_footer_scripts', array(
        'label' => __('Footer Scripts (Body End)', 'chroma-excellence'),
        'description' => __('These scripts will be printed before the closing &lt;/body&gt; tag.', 'chroma-excellence'),
        'section' => 'chroma_scripts_settings',
        'type' => 'textarea',
        'input_attrs' => array(
            'class' => 'code',
            'rows' => 10,
        ),
    ));
}
add_action('customize_register', 'chroma_scripts_customizer_settings');

/**
 * Sanitize callback for scripts (allow standard HTML/JS)
 */
function chroma_sanitize_scripts($input)
{
    if (current_user_can('unfiltered_html')) {
        return $input;
    }
    return wp_kses_post($input); // Fallback for lower permission users
}

/**
 * Output Header Scripts
 */
function chroma_third_party_targets()
{
    return array(
        'widgets.leadconnectorhq.com',
        'clarity.ms',
        'googletagmanager.com',
        'gtag(',
        'connect.facebook.net',
        'fbevents.js',
        'recaptcha',
        'searchatlas',
        'otto-pixel',
    );
}

/**
 * Check whether a script block includes one of the known third-party targets.
 */
function chroma_contains_third_party_target($content)
{
    foreach (chroma_third_party_targets() as $target) {
        if (strpos($content, $target) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Dedupe duplicate script tags across header/footer customizer fields.
 */
function chroma_dedupe_customizer_scripts($html)
{
    if (!is_string($html) || trim($html) === '') {
        return $html;
    }

    static $seen = array(
        'external' => array(),
        'inline' => array(),
    );

    $site_host = (string) wp_parse_url(home_url(), PHP_URL_HOST);

    return preg_replace_callback('/<script\b[^>]*>.*?<\/script>/is', function ($matches) use (&$seen, $site_host) {
        $script_tag = $matches[0];

        if (preg_match('/\bsrc\s*=\s*(["\'])(.*?)\1/i', $script_tag, $src_match)) {
            $src = html_entity_decode(trim($src_match[2]), ENT_QUOTES);
            $host = (string) wp_parse_url($src, PHP_URL_HOST);

            // De-duplicate external scripts only; keep first-party script tags untouched.
            if ($host !== '' && strcasecmp($host, $site_host) !== 0) {
                $path = (string) wp_parse_url($src, PHP_URL_PATH);
                $query = (string) wp_parse_url($src, PHP_URL_QUERY);
                $key = strtolower($host . $path . ($query !== '' ? '?' . $query : ''));

                if (isset($seen['external'][$key])) {
                    return '';
                }

                $seen['external'][$key] = true;
            }

            return $script_tag;
        }

        $inline = preg_replace('/^<script\b[^>]*>|<\/script>$/i', '', $script_tag);
        if (!chroma_contains_third_party_target($inline)) {
            return $script_tag;
        }

        $signature = md5(strtolower(preg_replace('/\s+/', ' ', trim($inline))));
        if (isset($seen['inline'][$signature])) {
            return '';
        }

        $seen['inline'][$signature] = true;
        return $script_tag;
    }, $html);
}

function chroma_output_header_scripts()
{
    $scripts = get_theme_mod('chroma_header_scripts');
    if ($scripts) {
        $scripts = chroma_dedupe_customizer_scripts($scripts);
        if (trim($scripts) === '') {
            return;
        }

        echo "<!-- Global Header Scripts -->\n";
        echo $scripts . "\n";
        echo "<!-- End Global Header Scripts -->\n";
    }
}
add_action('wp_head', 'chroma_output_header_scripts', 1);

/**
 * Output Footer Scripts
 * Processed to lazy-load heavy third-party widgets
 */
/**
 * Optimize and Output Customizer Footer Scripts
 */
function chroma_output_footer_scripts()
{
    $scripts = get_theme_mod('chroma_footer_scripts');
    if ($scripts) {
        $scripts = chroma_dedupe_customizer_scripts($scripts);
        if (trim($scripts) === '') {
            return;
        }

        // Advanced: Generic Lazy-Load for Third Parties (TBT/LCP Optimization)
        $scripts = chroma_optimize_third_party_scripts($scripts);

        echo "<!-- Global Footer Scripts (Optimized) -->\n";
        echo $scripts . "\n";
        echo "<!-- End Global Footer Scripts -->\n";
    }
}
add_action('wp_footer', 'chroma_output_footer_scripts', 99);

/**
 * Advanced Script Optimizer
 * Wraps heavy third-party snippets (GHL, FB, Google, Clarity) in an interaction-only loader.
 * Replaces the brittle "immediate-load" pattern with "Load-on-Intent".
 */
function chroma_optimize_third_party_scripts($html)
{
    if (is_admin()) {
        return $html;
    }

    if (!chroma_contains_third_party_target($html)) {
        return $html;
    }

    // Convert raw scripts into an interaction-triggered injection
    // This wipes out TBT from heavy third-parties during initial load
    $encoded_scripts = wp_json_encode($html);
    if (!$encoded_scripts) {
        return $html;
    }

    return "
    <script id='chroma-lazy-loader-wrapper'>
    (function() {
        if (window.__chromaThirdPartyLazyInit) return;
        window.__chromaThirdPartyLazyInit = true;

        var loadedSrc = window.__chromaThirdPartyLoadedSrc || (window.__chromaThirdPartyLoadedSrc = {});
        var loadedInline = window.__chromaThirdPartyLoadedInline || (window.__chromaThirdPartyLoadedInline = {});
        var scriptsLoaded = false;

        var normalize = function(input) {
            return (input || '').toString().replace(/^https?:/, '').trim();
        };

        var hasScript = function(src) {
            var scripts = document.getElementsByTagName('script');
            for (var i = 0; i < scripts.length; i++) {
                if (scripts[i].src === src) return true;
            }
            return false;
        };

        var detachListeners = function() {
            ['click', 'touchstart', 'keydown', 'focusin'].forEach(function(ev) {
                window.removeEventListener(ev, loadScripts, true);
            });
            window.removeEventListener('scroll', onScroll);
        };

        var loadScripts = function() {
            if (scriptsLoaded) return;
            scriptsLoaded = true;
            detachListeners();
            
            var container = document.createElement('div');
            container.innerHTML = {$encoded_scripts};
            var scripts = container.querySelectorAll('script');
            
            scripts.forEach(function(oldScript) {
                if (oldScript.src) {
                    var src = oldScript.src;
                    var srcKey = normalize(src);
                    if (!srcKey || loadedSrc[srcKey] || hasScript(src)) {
                        return;
                    }
                    loadedSrc[srcKey] = true;
                } else {
                    var inlineText = (oldScript.textContent || '').replace(/\\s+/g, ' ').trim();
                    var inlineKey = inlineText.slice(0, 500);
                    if (!inlineKey || loadedInline[inlineKey]) {
                        return;
                    }
                    loadedInline[inlineKey] = true;
                }

                var newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(function(attr) {
                    newScript.setAttribute(attr.name, attr.value);
                });
                if (oldScript.src) {
                    newScript.src = oldScript.src;
                } else {
                    newScript.innerHTML = oldScript.innerHTML;
                }
                document.body.appendChild(newScript);
            });
        };

        var onScroll = function() {
            if (window.scrollY > Math.max(240, window.innerHeight * 0.5)) {
                loadScripts();
            }
        };

        // Trigger on explicit user intent and meaningful scroll only.
        ['click', 'touchstart', 'keydown', 'focusin'].forEach(function(ev) {
            window.addEventListener(ev, loadScripts, true);
        });
        window.addEventListener('scroll', onScroll, { passive: true });
    })();
    </script>";
}

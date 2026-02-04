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
function chroma_output_header_scripts()
{
    $scripts = get_theme_mod('chroma_header_scripts');
    if ($scripts) {
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
    if (is_admin())
        return $html;

    $targets = [
        'widgets.leadconnectorhq.com',
        'clarity.ms',
        'googletagmanager.com',
        'gtag(',
        'connect.facebook.net',
        'fbevents.js',
        'recaptcha',
        'searchatlas',
        'otto-pixel'
    ];

    $found = false;
    foreach ($targets as $target) {
        if (strpos($html, $target) !== false) {
            $found = true;
            break;
        }
    }

    if (!$found)
        return $html;

    // Convert raw scripts into an interaction-triggered injection
    // This wipes out TBT from heavy third-parties during initial load
    $encoded_scripts = json_encode($html);

    return "
    <script id='chroma-lazy-loader-wrapper'>
    (function() {
        var scriptsLoaded = false;
        var loadScripts = function() {
            if (scriptsLoaded) return;
            scriptsLoaded = true;
            
            // Remove listeners
            ['scroll', 'mousemove', 'touchstart', 'keydown'].forEach(function(ev) {
                window.removeEventListener(ev, loadScripts);
            });

            console.log('[Chroma] Loading deferred third-party scripts...');
            
            var container = document.createElement('div');
            container.innerHTML = {$encoded_scripts};
            var scripts = container.querySelectorAll('script');
            
            scripts.forEach(function(oldScript) {
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

        // Trigger on interaction
        ['scroll', 'mousemove', 'touchstart', 'keydown'].forEach(function(ev) {
            window.addEventListener(ev, loadScripts, {passive: true});
        });

        // Fallback: Default to idle load after 4.5s if no interaction
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(function() {
                setTimeout(loadScripts, 3500);
            }, {timeout: 5000});
        } else {
            setTimeout(loadScripts, 4500);
        }
    })();
    </script>";
}

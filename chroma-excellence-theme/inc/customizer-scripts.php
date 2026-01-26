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
function chroma_output_footer_scripts()
{
    $scripts = get_theme_mod('chroma_footer_scripts');
    if ($scripts) {
        // Performance: Lazy-load LeadConnector if present
        if (strpos($scripts, 'widgets.leadconnectorhq.com') !== false) {
            $scripts = "
                <script>
                (function() {
                    var loadLC = function() {
                        window.removeEventListener('scroll', loadLC);
                        window.removeEventListener('mousemove', loadLC);
                        window.removeEventListener('touchstart', loadLC);
                        var div = document.createElement('div');
                        div.innerHTML = " . json_encode($scripts) . ";
                        var scripts = div.querySelectorAll('script');
                        scripts.forEach(function(s) {
                            var ns = document.createElement('script');
                            if (s.src) ns.src = s.src;
                            if (s.innerHTML) ns.innerHTML = s.innerHTML;
                            document.body.appendChild(ns);
                        });
                    };
                    window.addEventListener('scroll', loadLC, {passive:true});
                    window.addEventListener('mousemove', loadLC, {passive:true});
                    window.addEventListener('touchstart', loadLC, {passive:true});
                })();
                </script>";
        }

        echo "<!-- Global Footer Scripts (Optimized) -->\n";
        echo $scripts . "\n";
        echo "<!-- End Global Footer Scripts -->\n";
    }
}
add_action('wp_footer', 'chroma_output_footer_scripts', 99);

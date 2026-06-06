<?php
/**
 * Theme migration helpers.
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Copy site-level Customizer state from the original theme slug into the V2
 * slug. Post/page/location/program metabox data is stored in post_meta and is
 * therefore shared automatically between the original and V2 themes.
 *
 * This is intentionally additive: existing V2 values are never overwritten.
 *
 * @param string   $old_name  Previous theme name from after_switch_theme.
 * @param WP_Theme $old_theme Previous theme object from after_switch_theme.
 * @return void
 */
function chroma_migrate_v2_theme_state($old_name = '', $old_theme = null)
{
    $target_stylesheet = get_stylesheet();
    if ('chroma-excellence-theme-v2' !== $target_stylesheet) {
        return;
    }

    $source_stylesheets = array('chroma-excellence-theme');

    if ($old_theme instanceof WP_Theme) {
        $old_stylesheet = $old_theme->get_stylesheet();
        if ($old_stylesheet) {
            array_unshift($source_stylesheets, $old_stylesheet);
        }
    }

    $source_stylesheets = array_values(array_unique(array_filter($source_stylesheets)));
    $target_option = 'theme_mods_' . $target_stylesheet;
    $target_mods = get_option($target_option, array());
    $target_mods = is_array($target_mods) ? $target_mods : array();

    $copied_mods = 0;
    $source_used = '';

    foreach ($source_stylesheets as $source_stylesheet) {
        if ($source_stylesheet === $target_stylesheet) {
            continue;
        }

        $source_mods = get_option('theme_mods_' . $source_stylesheet, array());
        if (!is_array($source_mods) || empty($source_mods)) {
            continue;
        }

        foreach ($source_mods as $key => $value) {
            if (!array_key_exists($key, $target_mods)) {
                $target_mods[$key] = $value;
                $copied_mods++;
            }
        }

        if ($copied_mods > 0) {
            $source_used = $source_stylesheet;
            break;
        }
    }

    if ($copied_mods > 0) {
        update_option($target_option, $target_mods);
    }

    $copied_custom_css = false;
    if (function_exists('wp_get_custom_css') && function_exists('wp_update_custom_css_post')) {
        $target_css = trim((string) wp_get_custom_css($target_stylesheet));

        if ('' === $target_css) {
            foreach ($source_stylesheets as $source_stylesheet) {
                if ($source_stylesheet === $target_stylesheet) {
                    continue;
                }

                $source_css = trim((string) wp_get_custom_css($source_stylesheet));
                if ('' === $source_css) {
                    continue;
                }

                wp_update_custom_css_post($source_css, array('stylesheet' => $target_stylesheet));
                $copied_custom_css = true;
                if ('' === $source_used) {
                    $source_used = $source_stylesheet;
                }
                break;
            }
        }
    }

    update_option(
        'chroma_v2_theme_state_migration',
        array(
            'target' => $target_stylesheet,
            'source' => $source_used,
            'copied_theme_mods' => $copied_mods,
            'copied_custom_css' => $copied_custom_css,
            'ran_at' => current_time('mysql'),
        ),
        false
    );
}
add_action('after_switch_theme', 'chroma_migrate_v2_theme_state', 5, 2);
add_action('after_setup_theme', 'chroma_migrate_v2_theme_state', 5);

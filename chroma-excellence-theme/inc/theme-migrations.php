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
    if (0 !== strpos($target_stylesheet, 'chroma-excellence-theme-v2')) {
        return;
    }

    $source_stylesheets = array(
        'chroma-excellence-theme-v2',
        'chroma-excellence-theme',
    );

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

/**
 * Repair known launch-content relationships without replacing editorial data.
 */
function chroma_repair_launch_content_relationships()
{
    if ((int) get_option('chroma_launch_content_relationships_version', 0) >= 2) {
        return;
    }

    $find_location = static function (array $slugs, $search_term) {
        foreach ($slugs as $slug) {
            $location = get_page_by_path($slug, OBJECT, 'location');
            if ($location) {
                return $location;
            }
        }

        $matches = get_posts(array(
            'post_type' => 'location',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            's' => $search_term,
        ));

        return $matches[0] ?? null;
    };

    $chadwick = $find_location(
        array('chroma-early-learning-academy-chadwick', 'chadwick-campus'),
        'Chadwick'
    );
    $north_hall = $find_location(
        array('north-hall-campus-murraysville', 'north-hall-campus'),
        'North Hall'
    );

    $standard_program_slugs = array(
        'infant-care',
        'toddler-care',
        'preschool',
        'pre-k-prep',
        'after-school',
        'camp-summer-winter-fall',
        'parents-day-out',
    );
    $target_location_ids = array_values(array_filter(array(
        $chadwick ? (int) $chadwick->ID : 0,
        $north_hall ? (int) $north_hall->ID : 0,
    )));

    foreach ($standard_program_slugs as $program_slug) {
        $program = get_page_by_path($program_slug, OBJECT, 'program');
        if (!$program) {
            continue;
        }

        $location_ids = get_post_meta($program->ID, 'program_locations', true);
        $location_ids = is_array($location_ids) ? array_map('absint', $location_ids) : array();
        $updated_ids = array_values(array_unique(array_merge($location_ids, $target_location_ids)));

        if ($updated_ids !== $location_ids) {
            update_post_meta($program->ID, 'program_locations', $updated_ids);
            if (function_exists('chroma_sync_program_locations_taxonomy')) {
                chroma_sync_program_locations_taxonomy($program->ID, $program);
            }
        }
    }

    $ga_pre_k = get_page_by_path('ga-pre-k', OBJECT, 'program');
    if ($ga_pre_k && $target_location_ids) {
        $location_ids = get_post_meta($ga_pre_k->ID, 'program_locations', true);
        $location_ids = is_array($location_ids) ? array_map('absint', $location_ids) : array();
        $updated_ids = array_values(array_diff($location_ids, $target_location_ids));
        if ($updated_ids !== $location_ids) {
            update_post_meta($ga_pre_k->ID, 'program_locations', $updated_ids);
            if (function_exists('chroma_sync_program_locations_taxonomy')) {
                chroma_sync_program_locations_taxonomy($ga_pre_k->ID, $ga_pre_k);
            }
        }
    }

    $program_ages = array(
        'infant-care' => '6 Weeks–15 Months | Non-Walkers',
        'toddler-care' => '12–24 Months | Walkers',
        'preschool' => '24–36 Months',
        'pre-k-prep' => '3–4 Years',
        'ga-pre-k' => '4–5 Years',
        'after-school' => '5–12 Years',
        'camp-summer-winter-fall' => 'Seasonal | Ages 5–12',
        'kindergarten-1' => '5–6 Years',
        'parents-day-out' => '3–5 Years',
    );
    foreach ($program_ages as $program_slug => $age_range) {
        $program = get_page_by_path($program_slug, OBJECT, 'program');
        if ($program) {
            update_post_meta($program->ID, 'program_age_range', $age_range);
        }
    }

    $location_titles = array(
        'east-cobb-campus' => 'East Cobb Campus',
        'west-cobb-campus' => 'West Cobb Campus',
        'mcdonough' => 'McDonough Campus',
        'north-hall-campus-murraysville' => 'North Hall Campus',
    );
    foreach ($location_titles as $location_slug => $public_title) {
        $location = get_page_by_path($location_slug, OBJECT, 'location');
        if ($location && $location->post_title !== $public_title) {
            wp_update_post(array(
                'ID' => $location->ID,
                'post_title' => $public_title,
            ));
        }
    }

    update_option('chroma_launch_content_relationships_version', 2, false);
}
add_action('init', 'chroma_repair_launch_content_relationships', 35);

/**
 * Normalize the PrismPath brand casing in legacy editorial content.
 *
 * The replacement has the same byte length, so serialized meta and theme-mod
 * values remain valid while old page content and schema records are repaired.
 */
function chroma_normalize_prismpath_brand_casing()
{
    if ((int) get_option('chroma_prismpath_brand_casing_version', 0) >= 1) {
        return;
    }

    global $wpdb;

    $wpdb->query("UPDATE {$wpdb->posts} SET post_title = REPLACE(post_title, 'Prismpath', 'PrismPath'), post_excerpt = REPLACE(post_excerpt, 'Prismpath', 'PrismPath'), post_content = REPLACE(post_content, 'Prismpath', 'PrismPath') WHERE post_title LIKE '%Prismpath%' OR post_excerpt LIKE '%Prismpath%' OR post_content LIKE '%Prismpath%'");
    $wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, 'Prismpath', 'PrismPath') WHERE meta_value LIKE '%Prismpath%'");
    $wpdb->query("UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, 'Prismpath', 'PrismPath') WHERE option_value LIKE '%Prismpath%'");

    update_option('chroma_prismpath_brand_casing_version', 1, false);
}
add_action('init', 'chroma_normalize_prismpath_brand_casing', 36);

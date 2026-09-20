<?php
/**
 * Archive Root Query Context Guards
 *
 * Archive-root templates for locations, programs, and communities render their
 * own explicit queries. OTTO appears to inspect the main query on those archive
 * requests and incorrectly treat the first child post as the canonical target.
 *
 * To keep the archive request unmistakably "root archive" shaped, we strip the
 * child posts from the main query after WordPress has resolved the route but
 * before head metadata is generated. The visible archive grids keep working via
 * their dedicated template queries.
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Determine whether the current request is one of the archive roots whose main
 * query should not expose child posts.
 *
 * @param WP_Query|null $query Query to inspect. Defaults to global main query.
 * @return bool
 */
function chroma_should_neutralize_archive_root_main_query($query = null)
{
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return false;
    }

    if ((defined('REST_REQUEST') && REST_REQUEST) || (function_exists('wp_is_json_request') && wp_is_json_request())) {
        return false;
    }

    if (is_feed() || is_embed() || is_trackback() || is_robots()) {
        return false;
    }

    if (!$query instanceof WP_Query) {
        $query = $GLOBALS['wp_query'] ?? null;
    }

    if (!$query instanceof WP_Query || !$query->is_main_query()) {
        return false;
    }

    $archive_post_types = ['location', 'program', 'city'];
    if ($query->is_post_type_archive($archive_post_types)) {
        return true;
    }

    $post_type = $query->get('post_type');
    if (is_string($post_type) && in_array($post_type, $archive_post_types, true) && $query->is_archive()) {
        return true;
    }

    return false;
}

/**
 * Remove child posts from the global main query for archive-root requests while
 * preserving the archive identity itself.
 */
function chroma_neutralize_archive_root_main_query()
{
    global $post, $wp_query;

    if (!chroma_should_neutralize_archive_root_main_query($wp_query)) {
        return;
    }

    $post_type = $wp_query->get('post_type');
    if (is_array($post_type)) {
        $post_type = reset($post_type);
    }

    $wp_query->posts = [];
    $wp_query->post = null;
    $wp_query->post_count = 0;
    $wp_query->current_post = -1;
    $wp_query->in_the_loop = false;

    // Keep the request clearly classified as an archive root, not a singular.
    $wp_query->is_archive = true;
    $wp_query->is_post_type_archive = true;
    $wp_query->is_singular = false;
    $wp_query->is_single = false;
    $wp_query->is_page = false;
    $wp_query->is_404 = false;

    if (is_string($post_type) && $post_type !== '') {
        $post_type_object = get_post_type_object($post_type);
        if ($post_type_object instanceof WP_Post_Type) {
            $wp_query->queried_object = $post_type_object;
            $wp_query->queried_object_id = 0;
        }
    }

    $post = null;
}
add_action('wp', 'chroma_neutralize_archive_root_main_query', 1);

<?php
/**
 * P0-3: REGEXP to Taxonomy Migration
 * Shadow taxonomy for efficient location -> program lookups
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the shadow association taxonomy
 */
function chroma_register_perf_taxonomies()
{
    register_taxonomy('chroma_program_location', 'program', [
        'public' => false,
        'hierarchical' => false,
        'rewrite' => false,
        'show_ui' => false,
        'show_in_rest' => false,
    ]);
}
add_action('init', 'chroma_register_perf_taxonomies');

/**
 * Sync Program -> Location association taxonomy
 */
function chroma_sync_program_locations_taxonomy($post_id, $post = null)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;
    if (get_post_type($post_id) !== 'program')
        return;

    // Get locations meta
    $locations_meta = get_post_meta($post_id, 'program_locations', true);

    $location_ids = [];
    if (is_array($locations_meta)) {
        $location_ids = $locations_meta;
    } elseif (is_string($locations_meta) && !empty($locations_meta)) {
        // Handle serialized/weird format from REGEXP: (^|;)i:ID;
        if (preg_match_all('/i:(\d+);/', $locations_meta, $matches)) {
            $location_ids = array_map('intval', $matches[1]);
        }
    }

    if (!empty($location_ids)) {
        // Use ID as term slug
        $terms = array_map('strval', $location_ids);
        wp_set_object_terms($post_id, $terms, 'chroma_program_location');
    } else {
        wp_set_object_terms($post_id, [], 'chroma_program_location');
    }
}
add_action('save_post_program', 'chroma_sync_program_locations_taxonomy', 10, 2);

/**
 * Migration trigger for P0-3
 * Run once by visiting /wp-admin/?chroma_migrate_regexp=1
 */
function chroma_handle_regexp_migration()
{
    if (isset($_GET['chroma_migrate_regexp']) && current_user_can('manage_options')) {
        $programs = get_posts([
            'post_type' => 'program',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ]);

        $count = 0;
        foreach ($programs as $p) {
            chroma_sync_program_locations_taxonomy($p->ID);
            $count++;
        }

        wp_die("Migration Complete: " . $count . " programs processed into 'chroma_program_location' taxonomy.");
    }
}
add_action('admin_init', 'chroma_handle_regexp_migration');

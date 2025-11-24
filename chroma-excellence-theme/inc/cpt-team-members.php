<?php
/**
 * Custom Post Type: Team Members
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Team Member CPT
 */
function chroma_register_team_member_cpt() {
    $labels = array(
        'name'               => _x( 'Team Members', 'Post Type General Name', 'chroma-excellence' ),
        'singular_name'      => _x( 'Team Member', 'Post Type Singular Name', 'chroma-excellence' ),
        'menu_name'          => __( 'Team Members', 'chroma-excellence' ),
        'all_items'          => __( 'All Team Members', 'chroma-excellence' ),
        'add_new_item'       => __( 'Add New Team Member', 'chroma-excellence' ),
        'edit_item'          => __( 'Edit Team Member', 'chroma-excellence' ),
        'view_item'          => __( 'View Team Member', 'chroma-excellence' ),
        'search_items'       => __( 'Search Team Members', 'chroma-excellence' ),
        'not_found'          => __( 'No team members found', 'chroma-excellence' ),
        'not_found_in_trash' => __( 'No team members found in Trash', 'chroma-excellence' ),
    );

    $args = array(
        'label'               => __( 'Team Member', 'chroma-excellence' ),
        'labels'              => $labels,
        'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
        'public'              => true,
        'menu_position'       => 21,
        'menu_icon'           => 'dashicons-groups',
        'has_archive'         => false,
        'rewrite'             => array( 'slug' => 'team' ),
        'show_in_rest'        => true,
        'exclude_from_search' => true,
    );

    register_post_type( 'team_member', $args );
}
add_action( 'init', 'chroma_register_team_member_cpt' );

/**
 * Register meta fields for Team Member title/role.
 */
function chroma_register_team_member_meta() {
    register_post_meta(
        'team_member',
        'team_member_title',
        array(
            'object_subtype'    => 'team_member',
            'type'              => 'string',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function() {
                return current_user_can( 'edit_posts' );
            },
        )
    );
}
add_action( 'init', 'chroma_register_team_member_meta' );

/**
 * Add admin columns for Team Members.
 */
function chroma_team_member_admin_columns( $columns ) {
    $new_columns           = array();
    $new_columns['cb']     = $columns['cb'];
    $new_columns['title']  = __( 'Name', 'chroma-excellence' );
    $new_columns['role']   = __( 'Title', 'chroma-excellence' );
    $new_columns['date']   = $columns['date'];

    return $new_columns;
}
add_filter( 'manage_team_member_posts_columns', 'chroma_team_member_admin_columns' );

/**
 * Populate admin columns.
 */
function chroma_team_member_admin_column_content( $column, $post_id ) {
    switch ( $column ) {
        case 'role':
            $title = get_post_meta( $post_id, 'team_member_title', true );
            echo $title ? esc_html( $title ) : '—';
            break;
    }
}
add_action( 'manage_team_member_posts_custom_column', 'chroma_team_member_admin_column_content', 10, 2 );

/**
 * Seed default team members to populate About page cards.
 */
function chroma_seed_default_team_members() {
    $existing = get_posts(
        array(
            'post_type'      => 'team_member',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        )
    );

    if ( get_option( 'chroma_team_members_seeded' ) && ! empty( $existing ) ) {
        return;
    }

    // If posts exist but the option was never set (e.g., after theme updates),
    // mark the site as seeded so we do not insert duplicates on every request.
    if ( ! empty( $existing ) ) {
        update_option( 'chroma_team_members_seeded', 1 );
        return;
    }

    $defaults = array(
        array(
            'post_title'   => __( 'Jordan Avery', 'chroma-excellence' ),
            'post_content' => __( 'Jordan leads the Prismpath team with a passion for creating joyful, evidence-based learning experiences for every child.', 'chroma-excellence' ),
            'role'         => __( 'Executive Director', 'chroma-excellence' ),
        ),
        array(
            'post_title'   => __( 'Samira Patel', 'chroma-excellence' ),
            'post_content' => __( 'Samira partners with families and educators to ensure each center reflects the needs of its community.', 'chroma-excellence' ),
            'role'         => __( 'Director of Family Partnerships', 'chroma-excellence' ),
        ),
        array(
            'post_title'   => __( 'Luis Moreno', 'chroma-excellence' ),
            'post_content' => __( 'Luis coaches classroom teams, championing playful curricula that meet students where they are.', 'chroma-excellence' ),
            'role'         => __( 'Director of Learning', 'chroma-excellence' ),
        ),
    );

    foreach ( $defaults as $index => $member ) {
        $post_id = wp_insert_post(
            array(
                'post_type'    => 'team_member',
                'post_status'  => 'publish',
                'post_title'   => $member['post_title'],
                'post_content' => $member['post_content'],
                'menu_order'   => $index,
            )
        );

        if ( ! is_wp_error( $post_id ) && ! empty( $member['role'] ) ) {
            update_post_meta( $post_id, 'team_member_title', $member['role'] );
        }
    }

    update_option( 'chroma_team_members_seeded', 1 );
}
add_action( 'after_switch_theme', 'chroma_seed_default_team_members' );
add_action( 'init', 'chroma_seed_default_team_members', 20 );


<?php
/**
 * Register City Custom Post Type
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function chroma_register_city_cpt()
{
    $labels = array(
        'name' => _x('Cities', 'Post Type General Name', 'chroma-excellence'),
        'singular_name' => _x('City', 'Post Type Singular Name', 'chroma-excellence'),
        'menu_name' => __('Cities', 'chroma-excellence'),
        'name_admin_bar' => __('City', 'chroma-excellence'),
        'archives' => __('City Archives', 'chroma-excellence'),
        'attributes' => __('City Attributes', 'chroma-excellence'),
        'parent_item_colon' => __('Parent City:', 'chroma-excellence'),
        'all_items' => __('All Cities', 'chroma-excellence'),
        'add_new_item' => __('Add New City', 'chroma-excellence'),
        'add_new' => __('Add New', 'chroma-excellence'),
        'new_item' => __('New City', 'chroma-excellence'),
        'edit_item' => __('Edit City', 'chroma-excellence'),
        'update_item' => __('Update City', 'chroma-excellence'),
        'view_item' => __('View City', 'chroma-excellence'),
        'view_items' => __('View Cities', 'chroma-excellence'),
        'search_items' => __('Search City', 'chroma-excellence'),
        'not_found' => __('Not found', 'chroma-excellence'),
        'not_found_in_trash' => __('Not found in Trash', 'chroma-excellence'),
        'featured_image' => __('City Image', 'chroma-excellence'),
        'set_featured_image' => __('Set city image', 'chroma-excellence'),
        'remove_featured_image' => __('Remove city image', 'chroma-excellence'),
        'use_featured_image' => __('Use as city image', 'chroma-excellence'),
        'insert_into_item' => __('Insert into city', 'chroma-excellence'),
        'uploaded_to_this_item' => __('Uploaded to this city', 'chroma-excellence'),
        'items_list' => __('Cities list', 'chroma-excellence'),
        'items_list_navigation' => __('Cities list navigation', 'chroma-excellence'),
        'filter_items_list' => __('Filter cities list', 'chroma-excellence'),
    );
    $args = array(
        'label' => __('City', 'chroma-excellence'),
        'description' => __('Hyperlocal landing pages for cities', 'chroma-excellence'),
        'labels' => $labels,
        'supports' => array('title', 'editor', 'thumbnail', 'revisions', 'custom-fields'),
        'hierarchical' => false,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 22, // Below Locations
        'menu_icon' => 'dashicons-location',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => true,
        'can_export' => true,
        'has_archive' => 'communities',
        'exclude_from_search' => false,
        'publicly_queryable' => true,
        'capability_type' => 'page',
        'show_in_rest' => true, // Enable Gutenberg
        'rewrite' => array('slug' => 'childcare'), // e.g., /childcare/canton-ga
    );
    register_post_type('city', $args);
}
add_action('init', 'chroma_register_city_cpt', 0);

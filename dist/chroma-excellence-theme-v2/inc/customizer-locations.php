<?php
/**
 * Locations Customizer Settings
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Locations Settings
 */
function chroma_customize_locations($wp_customize)
{

    // Section: Locations Archive
    $wp_customize->add_section('chroma_locations_settings', array(
        'title' => __('Locations Archive', 'chroma-excellence'),
        'description' => __('Customize the title, subtitle, and labels for the Locations page.', 'chroma-excellence'),
        'priority' => 130,
    ));

    // Setting: Archive Title
    $wp_customize->add_setting('chroma_locations_archive_title', array(
        'default' => 'Find your Chroma <span class="text-chroma-green italic">Community</span> - Our Locations',
        'sanitize_callback' => 'chroma_sanitize_raw_html', // Allow HTML for spans
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('chroma_locations_archive_title', array(
        'label' => __('Archive Page Title', 'chroma-excellence'),
        'description' => __('The main H1 title on the Locations page. HTML allowed.', 'chroma-excellence'),
        'section' => 'chroma_locations_settings',
        'type' => 'textarea',
    ));

    // Setting: Archive Subtitle
    $wp_customize->add_setting('chroma_locations_archive_subtitle', array(
        'default' => 'Serving families across Metro Atlanta with the same high standards of safety, curriculum, and care at every single location.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('chroma_locations_archive_subtitle', array(
        'label' => __('Archive Page Subtitle', 'chroma-excellence'),
        'description' => __('The subtitle text below the main title.', 'chroma-excellence'),
        'section' => 'chroma_locations_settings',
        'type' => 'textarea',
    ));

    // Setting: "All Locations" Label
    $wp_customize->add_setting('chroma_locations_label', array(
        'default' => 'All Locations',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('chroma_locations_label', array(
        'label' => __('"All Locations" Label', 'chroma-excellence'),
        'description' => __('The label used for buttons and filters (e.g., "All Areas", "View All Locations").', 'chroma-excellence'),
        'section' => 'chroma_locations_settings',
        'type' => 'text',
    ));

    // Setting: Badge Fallback Text
    $wp_customize->add_setting('chroma_locations_badge_fallback', array(
        'default' => 'Now Enrolling',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('chroma_locations_badge_fallback', array(
        'label' => __('Badge Fallback Text', 'chroma-excellence'),
        'description' => __('Text to show on the location card badge if not "New Campus" (e.g., "Now Enrolling").', 'chroma-excellence'),
        'section' => 'chroma_locations_settings',
        'type' => 'text',
    ));

    // Setting: Open Now Text
    $wp_customize->add_setting('chroma_locations_open_text', array(
        'default' => 'Open Now',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('chroma_locations_open_text', array(
        'label' => __('"Open Now" Text', 'chroma-excellence'),
        'description' => __('Text displayed next to the pulsing dot when location is open.', 'chroma-excellence'),
        'section' => 'chroma_locations_settings',
        'type' => 'text',
    ));

}
add_action('customize_register', 'chroma_customize_locations');

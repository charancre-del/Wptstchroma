<?php
/**
 * Customizer controls for homepage content
 *
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    return;
}

/**
 * Ensure JSON textareas round-trip cleanly.
 */
function chroma_home_sanitize_json_setting($value)
{
    if (empty($value)) {
        return '';
    }

    $data = json_decode($value, true);

    if (JSON_ERROR_NONE !== json_last_error() || !is_array($data)) {
        return '';
    }

    return wp_json_encode($data);
}

/**
 * Sanitize checkbox values.
 */
function chroma_sanitize_checkbox($checked)
{
    return (isset($checked) && true === $checked) ? true : false;
}

/**
 * Register homepage customization controls.
 */
function chroma_home_customize_register(WP_Customize_Manager $wp_customize)
{
    $wp_customize->add_panel(
        'chroma_home_panel',
        array(
            'title' => __('Chroma Homepage', 'chroma-excellence'),
            'description' => __('Adjust hero copy, stats, and JSON-driven homepage sections.', 'chroma-excellence'),
            'priority' => 132,
        )
    );

    // Hero section.
    $wp_customize->add_section(
        'chroma_home_hero_section',
        array(
            'title' => __('Hero', 'chroma-excellence'),
            'panel' => 'chroma_home_panel',
        )
    );

    $hero_defaults = chroma_home_default_hero();

    $wp_customize->add_setting('chroma_home_hero_heading', array('default' => $hero_defaults['heading'], 'sanitize_callback' => 'wp_kses_post'));
    $wp_customize->add_control('chroma_home_hero_heading', array('label' => __('Heading (supports basic HTML)', 'chroma-excellence'), 'section' => 'chroma_home_hero_section', 'type' => 'textarea'));

    $wp_customize->add_setting('chroma_home_hero_subheading', array('default' => $hero_defaults['subheading'], 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_hero_subheading', array('label' => __('Subheading', 'chroma-excellence'), 'section' => 'chroma_home_hero_section', 'type' => 'textarea'));

    $wp_customize->add_setting('chroma_home_hero_cta_label', array('default' => $hero_defaults['cta_label'], 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_hero_cta_label', array('label' => __('Primary CTA label', 'chroma-excellence'), 'section' => 'chroma_home_hero_section', 'type' => 'text'));

    $wp_customize->add_setting('chroma_home_hero_cta_url', array('default' => $hero_defaults['cta_url'], 'sanitize_callback' => 'esc_url_raw'));
    $wp_customize->add_control('chroma_home_hero_cta_url', array('label' => __('Primary CTA URL', 'chroma-excellence'), 'section' => 'chroma_home_hero_section', 'type' => 'url'));

    $wp_customize->add_setting('chroma_home_hero_secondary_label', array('default' => $hero_defaults['secondary_label'], 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_hero_secondary_label', array('label' => __('Secondary CTA label', 'chroma-excellence'), 'section' => 'chroma_home_hero_section', 'type' => 'text'));

    $wp_customize->add_setting('chroma_home_hero_secondary_url', array('default' => $hero_defaults['secondary_url'], 'sanitize_callback' => 'esc_url_raw'));
    $wp_customize->add_control('chroma_home_hero_secondary_url', array('label' => __('Secondary CTA URL', 'chroma-excellence'), 'section' => 'chroma_home_hero_section', 'type' => 'url'));

    // Hero Image
    $wp_customize->add_setting('chroma_home_hero_image', array(
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'chroma_home_hero_image', array(
        'label' => __('Hero Image', 'chroma-excellence'),
        'description' => __('Upload a hero image (recommended: 1200x800px). This appears in the main homepage hero section.', 'chroma-excellence'),
        'section' => 'chroma_home_hero_section',
    )));

    // Stats JSON.
    $wp_customize->add_section(
        'chroma_home_stats_section',
        array(
            'title' => __('Stats Strip', 'chroma-excellence'),
            'panel' => 'chroma_home_panel',
        )
    );

    $wp_customize->add_setting(
        'chroma_home_stats_json',
        array(
            'default' => wp_json_encode(chroma_home_default_stats()),
            'sanitize_callback' => 'chroma_home_sanitize_json_setting',
        )
    );

    $wp_customize->add_control(
        'chroma_home_stats_json',
        array(
            'label' => __('Stats JSON (value/label pairs)', 'chroma-excellence'),
            'description' => __('Example: [{"value":"19+","label":"Metro campuses"}]', 'chroma-excellence'),
            'section' => 'chroma_home_stats_section',
            'type' => 'textarea',
        )
    );

    // Prismpath copy + cards JSON.
    $wp_customize->add_section(
        'chroma_home_prismpath_section',
        array(
            'title' => __('Prismpath', 'chroma-excellence'),
            'panel' => 'chroma_home_panel',
        )
    );

    $prismpath = chroma_home_default_prismpath();

    $wp_customize->add_setting('chroma_home_prismpath_eyebrow', array('default' => $prismpath['feature']['eyebrow'], 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_prismpath_eyebrow', array('label' => __('Eyebrow', 'chroma-excellence'), 'section' => 'chroma_home_prismpath_section', 'type' => 'text'));

    $wp_customize->add_setting('chroma_home_prismpath_heading', array('default' => $prismpath['feature']['heading'], 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_prismpath_heading', array('label' => __('Heading', 'chroma-excellence'), 'section' => 'chroma_home_prismpath_section', 'type' => 'text'));

    $wp_customize->add_setting('chroma_home_prismpath_cta_label', array('default' => $prismpath['feature']['cta_label'], 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_prismpath_cta_label', array('label' => __('CTA label', 'chroma-excellence'), 'section' => 'chroma_home_prismpath_section', 'type' => 'text'));

    $wp_customize->add_setting('chroma_home_prismpath_cta_url', array('default' => $prismpath['feature']['cta_url'], 'sanitize_callback' => 'esc_url_raw'));
    $wp_customize->add_control('chroma_home_prismpath_cta_url', array('label' => __('CTA URL', 'chroma-excellence'), 'section' => 'chroma_home_prismpath_section', 'type' => 'url'));

    $wp_customize->add_setting(
        'chroma_home_prismpath_cards_json',
        array(
            'default' => wp_json_encode($prismpath['cards'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'sanitize_callback' => 'chroma_home_sanitize_json_setting',
        )
    );

    $wp_customize->add_control(
        'chroma_home_prismpath_cards_json',
        array(
            'label' => __('Cards JSON (badge, heading, text, button, url, icons)', 'chroma-excellence'),
            'description' => __('Icon fields: "icon" for simple cards, or "icon_bg"/"icon_badge"/"icon_check" for complex cards. Use Font Awesome 6 classes: fa-solid fa-heart, fa-brands fa-connectdevelop', 'chroma-excellence'),
            'section' => 'chroma_home_prismpath_section',
            'type' => 'textarea',
        )
    );

    $wp_customize->add_setting('chroma_home_prismpath_readiness_heading', array('default' => $prismpath['readiness']['heading'], 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_prismpath_readiness_heading', array('label' => __('Readiness heading', 'chroma-excellence'), 'section' => 'chroma_home_prismpath_section', 'type' => 'text'));

    $wp_customize->add_setting('chroma_home_prismpath_readiness_desc', array('default' => $prismpath['readiness']['description'], 'sanitize_callback' => 'sanitize_textarea_field'));
    $wp_customize->add_control('chroma_home_prismpath_readiness_desc', array('label' => __('Readiness description', 'chroma-excellence'), 'section' => 'chroma_home_prismpath_section', 'type' => 'textarea'));

    // Program wizard JSON.
    $wp_customize->add_section(
        'chroma_home_programs_section',
        array(
            'title' => __('Program Wizard', 'chroma-excellence'),
            'panel' => 'chroma_home_panel',
        )
    );

    $wp_customize->add_setting(
        'chroma_home_program_wizard_json',
        array(
            'default' => wp_json_encode(chroma_home_default_program_wizard_options()),
            'sanitize_callback' => 'chroma_home_sanitize_json_setting',
        )
    );

    $wp_customize->add_control(
        'chroma_home_program_wizard_json',
        array(
            'label' => __('Program options JSON', 'chroma-excellence'),
            'description' => __('Example: [{"key":"infant","emoji":"👶","label":"Infant\\n(6 weeks–12m)","description":"..."}]', 'chroma-excellence'),
            'section' => 'chroma_home_programs_section',
            'type' => 'textarea',
        )
    );

    // Curriculum profiles JSON.
    $wp_customize->add_section(
        'chroma_home_curriculum_section',
        array(
            'title' => __('Curriculum Radar', 'chroma-excellence'),
            'panel' => 'chroma_home_panel',
        )
    );

    $wp_customize->add_setting(
        'chroma_home_curriculum_profiles_json',
        array(
            'default' => wp_json_encode(chroma_home_default_curriculum_profiles()['profiles']),
            'sanitize_callback' => 'chroma_home_sanitize_json_setting',
        )
    );

    $wp_customize->add_control(
        'chroma_home_curriculum_profiles_json',
        array(
            'label' => __('Curriculum profiles JSON', 'chroma-excellence'),
            'description' => __('Example: [{"key":"infant","title":"Foundation Phase","color":"#D67D6B","data":[90,90,40,15,40]}]', 'chroma-excellence'),
            'section' => 'chroma_home_curriculum_section',
            'type' => 'textarea',
        )
    );

    // Schedule JSON.
    $wp_customize->add_section(
        'chroma_home_schedule_section',
        array(
            'title' => __('Schedule Tabs', 'chroma-excellence'),
            'panel' => 'chroma_home_panel',
        )
    );

    $wp_customize->add_setting(
        'chroma_home_schedule_tracks_json',
        array(
            'default' => wp_json_encode(chroma_home_default_schedule_tracks()),
            'sanitize_callback' => 'chroma_home_sanitize_json_setting',
        )
    );

    $wp_customize->add_control(
        'chroma_home_schedule_tracks_json',
        array(
            'label' => __('Schedule JSON', 'chroma-excellence'),
            'description' => __('Example: [{"key":"infant","title":"The Nurturing Nest","steps":[{"time":"AM","title":"Warm Welcome"}]}]', 'chroma-excellence'),
            'section' => 'chroma_home_schedule_section',
            'type' => 'textarea',
        )
    );

    // FAQ JSON + heading.
    $wp_customize->add_section(
        'chroma_home_faq_section',
        array(
            'title' => __('FAQ', 'chroma-excellence'),
            'panel' => 'chroma_home_panel',
        )
    );

    $faq_defaults = chroma_home_default_faq();

    $wp_customize->add_setting('chroma_home_faq_heading', array('default' => $faq_defaults['heading'], 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_faq_heading', array('label' => __('FAQ heading', 'chroma-excellence'), 'section' => 'chroma_home_faq_section', 'type' => 'text'));

    $wp_customize->add_setting('chroma_home_faq_disable_schema', array('default' => false, 'sanitize_callback' => 'chroma_sanitize_checkbox'));
    $wp_customize->add_control('chroma_home_faq_disable_schema', array('label' => __('Disable FAQ Schema (JSON-LD)', 'chroma-excellence'), 'description' => __('Check this to remove strict FAQ schema but keep the visible FAQ section on the page.', 'chroma-excellence'), 'section' => 'chroma_home_faq_section', 'type' => 'checkbox'));

    $wp_customize->add_setting('chroma_home_faq_subheading', array('default' => $faq_defaults['subheading'], 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_faq_subheading', array('label' => __('FAQ subheading', 'chroma-excellence'), 'section' => 'chroma_home_faq_section', 'type' => 'textarea'));

    $wp_customize->add_setting(
        'chroma_home_faq_items_json',
        array(
            'default' => wp_json_encode($faq_defaults['items']),
            'sanitize_callback' => 'chroma_home_sanitize_json_setting',
        )
    );

    $wp_customize->add_control(
        'chroma_home_faq_items_json',
        array(
            'label' => __('FAQ JSON (question/answer)', 'chroma-excellence'),
            'description' => __('Example: [{"question":"Do you offer GA Lottery Pre-K?","answer":"Yes..."}]', 'chroma-excellence'),
            'section' => 'chroma_home_faq_section',
            'type' => 'textarea',
        )
    );

    // Locations callout.
    $wp_customize->add_section(
        'chroma_home_locations_section',
        array(
            'title' => __('Locations Preview', 'chroma-excellence'),
            'panel' => 'chroma_home_panel',
        )
    );

    $wp_customize->add_setting('chroma_home_locations_heading', array('default' => '19+ neighborhood locations across Metro Atlanta', 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_locations_heading', array('label' => __('Locations heading', 'chroma-excellence'), 'section' => 'chroma_home_locations_section', 'type' => 'text'));

    $wp_customize->add_setting('chroma_home_locations_subheading', array('default' => 'Find a Chroma campus near your home or work. All locations share the same safety standards, curriculum framework, and warm Chroma culture.', 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_locations_subheading', array('label' => __('Locations subheading', 'chroma-excellence'), 'section' => 'chroma_home_locations_section', 'type' => 'textarea'));

    $wp_customize->add_setting('chroma_home_locations_cta_label', array('default' => 'View All Locations', 'sanitize_callback' => 'sanitize_text_field'));
    $wp_customize->add_control('chroma_home_locations_cta_label', array('label' => __('CTA label', 'chroma-excellence'), 'section' => 'chroma_home_locations_section', 'type' => 'text'));

    $wp_customize->add_setting('chroma_home_locations_cta_link', array('default' => '/locations', 'sanitize_callback' => 'esc_url_raw'));
    $wp_customize->add_control('chroma_home_locations_cta_link', array('label' => __('CTA link', 'chroma-excellence'), 'section' => 'chroma_home_locations_section', 'type' => 'url'));

    // Parent Reviews Section
    $wp_customize->add_section(
        'chroma_home_reviews_section',
        array(
            'title' => __('Parent Reviews', 'chroma-excellence'),
            'description' => __('Manage testimonials displayed on the homepage carousel.', 'chroma-excellence'),
            'panel' => 'chroma_home_panel',
        )
    );

    $wp_customize->add_setting(
        'chroma_home_parent_reviews_json',
        array(
            'default' => wp_json_encode(chroma_home_default_parent_reviews(), JSON_PRETTY_PRINT),
            'sanitize_callback' => 'chroma_home_sanitize_json_setting',
        )
    );

    $wp_customize->add_control(
        'chroma_home_parent_reviews_json',
        array(
            'label' => __('Parent Reviews JSON', 'chroma-excellence'),
            'description' => __('Each review: {"name": "Parent Name", "location": "Campus Name", "rating": 5, "review": "Testimonial text..."}', 'chroma-excellence'),
            'section' => 'chroma_home_reviews_section',
            'type' => 'textarea',
            'input_attrs' => array(
                'rows' => 15,
                'style' => 'font-family: monospace; font-size: 12px;',
            ),
        )
    );
}
add_action('customize_register', 'chroma_home_customize_register');

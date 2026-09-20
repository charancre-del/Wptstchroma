<?php
/**
 * Footer Customizer Settings
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Register Footer Customizer Settings
 */
function chroma_footer_customizer_settings($wp_customize)
{

	// Add Footer Section
	$wp_customize->add_section('chroma_footer_settings', array(
		'title' => __('Footer Settings', 'chroma-excellence'),
		'priority' => 40,
	));

	/*
	 * Contact Section
	 */
	// Phone Number
	$wp_customize->add_setting('chroma_footer_phone', array(
		'default' => '',
		'sanitize_callback' => 'sanitize_text_field',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('chroma_footer_phone', array(
		'label' => __('Contact Phone', 'chroma-excellence'),
		'description' => __('Phone number to display in footer contact section', 'chroma-excellence'),
		'section' => 'chroma_footer_settings',
		'type' => 'text',
		'input_attrs' => array(
			'placeholder' => '(404) 555-1234',
		),
	));

	// Email Address
	$wp_customize->add_setting('chroma_footer_email', array(
		'default' => '',
		'sanitize_callback' => 'sanitize_email',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('chroma_footer_email', array(
		'label' => __('Contact Email', 'chroma-excellence'),
		'description' => __('Email address to display in footer contact section', 'chroma-excellence'),
		'section' => 'chroma_footer_settings',
		'type' => 'email',
		'input_attrs' => array(
			'placeholder' => 'hello@example.com',
		),
	));

	// Address
	$wp_customize->add_setting('chroma_footer_address', array(
		'default' => '',
		'sanitize_callback' => 'sanitize_textarea_field',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('chroma_footer_address', array(
		'label' => __('Contact Address', 'chroma-excellence'),
		'description' => __('Physical address to display in footer contact section', 'chroma-excellence'),
		'section' => 'chroma_footer_settings',
		'type' => 'textarea',
		'input_attrs' => array(
			'placeholder' => '123 Main St, Atlanta, GA 30301',
			'rows' => 2,
		),
	));

	/*
	 * Social Links Section
	 */
	// Facebook URL
	$wp_customize->add_setting('chroma_footer_facebook', array(
		'default' => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('chroma_footer_facebook', array(
		'label' => __('Facebook URL', 'chroma-excellence'),
		'description' => __('Full URL to your Facebook page', 'chroma-excellence'),
		'section' => 'chroma_footer_settings',
		'type' => 'url',
		'input_attrs' => array(
			'placeholder' => 'https://facebook.com/yourpage',
		),
	));

	// Instagram URL
	$wp_customize->add_setting('chroma_footer_instagram', array(
		'default' => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('chroma_footer_instagram', array(
		'label' => __('Instagram URL', 'chroma-excellence'),
		'description' => __('Full URL to your Instagram profile', 'chroma-excellence'),
		'section' => 'chroma_footer_settings',
		'type' => 'url',
		'input_attrs' => array(
			'placeholder' => 'https://instagram.com/yourprofile',
		),
	));

	// LinkedIn URL
	$wp_customize->add_setting('chroma_footer_linkedin', array(
		'default' => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('chroma_footer_linkedin', array(
		'label' => __('LinkedIn URL', 'chroma-excellence'),
		'description' => __('Full URL to your LinkedIn page', 'chroma-excellence'),
		'section' => 'chroma_footer_settings',
		'type' => 'url',
		'input_attrs' => array(
			'placeholder' => 'https://linkedin.com/company/yourcompany',
		),
	));

	// Twitter/X URL
	$wp_customize->add_setting('chroma_footer_twitter', array(
		'default' => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('chroma_footer_twitter', array(
		'label' => __('Twitter/X URL', 'chroma-excellence'),
		'description' => __('Full URL to your Twitter/X profile', 'chroma-excellence'),
		'section' => 'chroma_footer_settings',
		'type' => 'url',
		'input_attrs' => array(
			'placeholder' => 'https://twitter.com/yourprofile',
		),
	));

	// YouTube URL
	$wp_customize->add_setting('chroma_footer_youtube', array(
		'default' => '',
		'sanitize_callback' => 'esc_url_raw',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('chroma_footer_youtube', array(
		'label' => __('YouTube URL', 'chroma-excellence'),
		'description' => __('Full URL to your YouTube channel', 'chroma-excellence'),
		'section' => 'chroma_footer_settings',
		'type' => 'url',
		'input_attrs' => array(
			'placeholder' => 'https://youtube.com/@yourchannel',
		),
	));

}
add_action('customize_register', 'chroma_footer_customizer_settings');

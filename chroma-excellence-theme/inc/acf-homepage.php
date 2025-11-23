<?php
/**
 * ACF Homepage Helpers
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Home Page ID
 */
function chroma_get_home_page_id() {
	return get_option( 'page_on_front' ) ?: 0;
}

/**
 * Get Home Field
 */
function chroma_get_home_field( $key ) {
	$home_id = chroma_get_home_page_id();
	return get_field( $key, $home_id );
}

/**
 * Home Hero Data
 */
function chroma_home_hero() {
	return array(
		'heading'           => chroma_get_home_field( 'home_hero_heading' ) ?: 'The art of <span class="italic text-chroma-red">growing up.</span>',
		'subheading'        => chroma_get_home_field( 'home_hero_subheading' ) ?: 'Where accredited excellence meets the warmth of home.',
		'cta_label'         => chroma_get_home_field( 'home_hero_cta_label' ) ?: 'Schedule a Tour',
		'cta_url'           => chroma_get_home_field( 'home_hero_cta_url' ) ?: '#tour',
		'secondary_label'   => chroma_get_home_field( 'home_hero_secondary_label' ) ?: 'View Programs',
		'secondary_url'     => chroma_get_home_field( 'home_hero_secondary_url' ) ?: '/programs',
	);
}

/**
 * Home Stats
 */
function chroma_home_stats() {
        $stats = chroma_get_home_field( 'home_stats' );

	if ( ! $stats ) {
		// Default stats
		return array(
			array( 'value' => '19+', 'label' => 'Metro campuses' ),
			array( 'value' => '2,000+', 'label' => 'Children enrolled' ),
			array( 'value' => '4.8', 'label' => 'Avg parent rating' ),
			array( 'value' => '6w–12y', 'label' => 'Age range' ),
		);
	}

        return $stats;
}

/**
 * Prismpath expertise panels
 */
function chroma_home_prismpath_panels() {
        $panels = chroma_get_home_field( 'home_prismpath_panels' );

        if ( $panels ) {
                return $panels;
        }

        return array();
}

/**
 * Check if Stats Exist
 */
function chroma_home_has_stats() {
	return ! empty( chroma_home_stats() );
}

/**
 * Home Sections (Flexible Content)
 */
function chroma_home_sections() {
	return chroma_get_home_field( 'home_sections' ) ?: array();
}

/**
 * Check if Sections Exist
 */
function chroma_home_has_sections() {
	return ! empty( chroma_home_sections() );
}

/**
 * Home FAQ Items
 */
function chroma_home_faq_items() {
	$faqs = chroma_get_home_field( 'home_faq_items' );

	if ( ! $faqs ) {
		// Default FAQs
		return array(
			array(
				'question' => 'Do you offer GA Lottery Pre-K?',
				'answer'   => 'Yes. Many Chroma locations offer free GA Lottery Pre-K for 4-year-olds.',
			),
			array(
				'question' => 'What ages do you serve?',
				'answer'   => 'Most campuses serve children from 6 weeks through 12 years old.',
			),
			array(
				'question' => 'Are meals and snacks included?',
				'answer'   => 'Yes. Through the Child and Adult Care Food Program (CACFP).',
			),
		);
	}

	return $faqs;
}

/**
 * Check if FAQ Exists
 */
function chroma_home_has_faq() {
        return ! empty( chroma_home_faq_items() );
}

/**
 * Age-based program wizard options
 */
function chroma_home_program_wizard_options() {
        $options = chroma_get_home_field( 'home_program_wizard_options' );

        if ( $options ) {
                return $options;
        }

        return array();
}

/**
 * Curriculum radar profiles
 */
function chroma_home_curriculum_profiles() {
        $profiles = chroma_get_home_field( 'home_curriculum_profiles' );

        if ( $profiles ) {
                return $profiles;
        }

        return array();
}

/**
 * Daily schedule tracks
 */
function chroma_home_schedule_tracks() {
$tracks = chroma_get_home_field( 'home_schedule_tracks' );

if ( $tracks ) {
return $tracks;
}

return array();
}

/**
 * Checkers for optional sections
 */
function chroma_home_has_prismpath_panels() {
return ! empty( chroma_home_prismpath_panels() );
}

function chroma_home_has_program_wizard() {
return ! empty( chroma_home_program_wizard_options() );
}

function chroma_home_has_curriculum_profiles() {
return ! empty( chroma_home_curriculum_profiles() );
}

function chroma_home_has_schedule_tracks() {
return ! empty( chroma_home_schedule_tracks() );
}

/**
 * Home Featured Locations
 */
function chroma_home_featured_locations() {
	$locations = chroma_get_home_field( 'home_featured_locations' );

	if ( ! $locations ) {
		// Fallback to latest 6 locations
		$locations = get_posts( array(
			'post_type'      => 'location',
			'posts_per_page' => 6,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
	}

	return $locations;
}

/**
 * Home Featured Stories
 */
function chroma_home_featured_stories() {
	$stories = chroma_get_home_field( 'home_featured_stories' );

	if ( ! $stories ) {
		// Fallback to latest 3 posts
		$stories = get_posts( array(
			'post_type'      => 'post',
			'posts_per_page' => 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
	}

	return $stories;
}

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

return array(
'feature' => array(
'eyebrow'   => 'The Chroma Standard',
'heading'   => 'Grounded in Expertise. Wrapped in Love.',
'cta_label' => 'Meet the Team',
'cta_url'   => '/about',
),
'cards'   => array(
array(
'badge'       => 'Proprietary Model',
'title'       => 'The Prismpath™ Curriculum',
'description' => 'Just as a prism refracts light into a full spectrum of color, Prismpath™ refracts play into a full spectrum of development.',
),
array(
'badge'       => '',
'title'       => 'Expert Care, Extended Family.',
'description' => 'Our educators are state-certified professionals who understand that the most important credential is kindness.',
),
array(
'badge'       => '',
'title'       => 'Wholesome Fuel',
'description' => 'Organic, balanced meals served family-style to fuel growing minds.',
),
array(
'badge'       => '',
'title'       => 'Uncompromised Safety',
'description' => 'Secure, monitored facilities with open-door transparency for parents.',
),
),
);
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
 * Home FAQ block (heading, subheading, items, CTA)
 */
function chroma_home_faq() {

        return array(
                'heading'    => chroma_get_home_field( 'home_faq_heading' ) ?: 'Common questions from parents',
                'subheading' => chroma_get_home_field( 'home_faq_subheading' ) ?: 'We’ve answered a few of the questions parents ask most when choosing childcare and early learning.',
                'items'      => chroma_home_faq_items(),
                'cta_text'   => chroma_get_home_field( 'home_faq_cta_text' ) ?: '',
                'cta_label'  => chroma_get_home_field( 'home_faq_cta_label' ) ?: '',
                'cta_link'   => chroma_get_home_field( 'home_faq_cta_link' ) ?: '',
        );
}

/**
 * Age-based program wizard options
 */
function chroma_home_program_wizard_options() {
$options = chroma_get_home_field( 'home_program_wizard_options' );

if ( $options ) {
return $options;
}

return array(
array(
'key'         => 'infant',
'emoji'       => '👶',
'label'       => "Infant\n(6 weeks–12m)",
'description' => 'Low ratios, safe sleep practices, responsive caregiving, and sensory play in a peaceful, predictable environment.',
'link'        => '/programs#infant',
),
array(
'key'         => 'toddler',
'emoji'       => '🚀',
'label'       => "Toddler\n(1 year)",
'description' => 'Curated environments for walkers and explorers with language bursts and social skills.',
'link'        => '/programs#toddler',
),
array(
'key'         => 'preschool',
'emoji'       => '🎨',
'label'       => "Preschool\n(2 years)",
'description' => 'Early concepts in math, literacy, and science introduced through hands-on centers and guided play.',
'link'        => '/programs#preschool',
),
array(
'key'         => 'prep',
'emoji'       => '✏️',
'label'       => "Pre-K Prep\n(3 years)",
'description' => 'Structured centers and small-group instruction that build independence before GA Pre-K.',
'link'        => '/programs#pre-k-prep',
),
array(
'key'         => 'prek',
'emoji'       => '🎓',
'label'       => "GA Pre-K\n(4 years)",
'description' => 'Balanced academic readiness, social-emotional learning, and joyful experiences aligned with GA standards.',
'link'        => '/programs#ga-pre-k',
),
array(
'key'         => 'afterschool',
'emoji'       => '🚌',
'label'       => "After School\n(5–12 years)",
'description' => 'Transportation from local schools, homework support, clubs, and outdoor play.',
'link'        => '/programs#after-school',
),
);
}

/**
 * Curriculum radar profiles
 */
function chroma_home_curriculum_profiles() {
$profiles = chroma_get_home_field( 'home_curriculum_profiles' );

if ( $profiles ) {
return $profiles;
}

return array(
'labels'   => array( 'Physical', 'Emotional', 'Social', 'Academic', 'Creative' ),
'profiles' => array(
array(
'key'         => 'infant',
'title'       => 'Foundation Phase',
'description' => 'Infant classrooms emphasize emotional security, attachment, physical health, and sensory experiences. Academics are embedded through language-rich interactions.',
'color'       => '#D67D6B',
'data'        => array( 90, 90, 40, 15, 40 ),
),
array(
'key'         => 'toddler',
'title'       => 'Discovery Phase',
'description' => 'Toddlers explore movement, language, early problem-solving, and social skills through guided play and routines.',
'color'       => '#4A6C7C',
'data'        => array( 85, 75, 65, 30, 70 ),
),
array(
'key'         => 'preschool',
'title'       => 'Exploration Phase',
'description' => 'Preschoolers work on early literacy, math concepts, dramatic play, and collaborative projects, supported by strong routines.',
'color'       => '#E6BE75',
'data'        => array( 75, 65, 70, 55, 80 ),
),
array(
'key'         => 'prep',
'title'       => 'Pre-K Prep Phase',
'description' => 'Children build stamina for small-group work, early writing, and multi-step directions while strengthening self-regulation.',
'color'       => '#2F4858',
'data'        => array( 65, 60, 75, 75, 70 ),
),
array(
'key'         => 'prek',
'title'       => 'GA Pre-K Readiness',
'description' => 'Balanced academic readiness, social-emotional learning, and joyful experiences aligned with GA standards.',
'color'       => '#4A6C7C',
'data'        => array( 60, 60, 80, 90, 70 ),
),
array(
'key'         => 'afterschool',
'title'       => 'Enrichment Phase',
'description' => 'School-age programming offers homework help, social clubs, athletic play, and creative enrichment for older children.',
'color'       => '#E6BE75',
'data'        => array( 50, 70, 85, 75, 80 ),
),
),
);
}

/**
 * Daily schedule tracks
 */
function chroma_home_schedule_tracks() {
$tracks = chroma_get_home_field( 'home_schedule_tracks' );

if ( $tracks ) {
return $tracks;
}

return array(
array(
'key'   => 'infant',
'title' => 'The Nurturing Nest',
'color' => 'chroma-blue',
'steps' => array(
array(
'time'  => 'AM',
'title' => 'Warm Welcome & Cuddles',
'copy'  => 'Transition from parent, bottle feeding, and floor play.',
),
array(
'time'  => 'Mid',
'title' => 'Sensory Discovery',
'copy'  => 'Tummy time, soft textures, and mirror play.',
),
array(
'time'  => 'PM',
'title' => 'Stroller Walk & Songs',
'copy'  => 'Fresh air (weather permitting) and gentle music.',
),
),
),
array(
'key'   => 'toddler',
'title' => 'Explorers & Builders',
'color' => 'chroma-yellow',
'steps' => array(
array(
'time'  => '9:00',
'title' => 'Morning Circle',
'copy'  => 'Songs, greeting friends, and introducing the daily theme.',
),
array(
'time'  => '10:30',
'title' => 'Prismpath Play',
'copy'  => 'Block building, art stations, and guided motor skills.',
),
array(
'time'  => '12:00',
'title' => 'Family-Style Lunch',
'copy'  => 'Learning to pass bowls, use utensils, and chat with friends.',
),
),
),
array(
'key'   => 'prek',
'title' => 'Kindergarten Readiness',
'color' => 'chroma-red',
'steps' => array(
array(
'time'  => '9:00',
'title' => 'Literacy & Logic',
'copy'  => 'Phonics games, calendar math, and story comprehension.',
),
array(
'time'  => '11:00',
'title' => 'Project-Based Learning',
'copy'  => 'Collaborative science experiments and art projects.',
),
array(
'time'  => '2:00',
'title' => 'Social Centers',
'copy'  => 'Dramatic play and negotiation skills.',
),
),
),
);
}

/**
 * Locations preview content
 */
function chroma_home_locations_preview() {
        $heading     = chroma_get_home_field( 'home_locations_heading' );
        $subheading  = chroma_get_home_field( 'home_locations_subheading' );
        $cta_label   = chroma_get_home_field( 'home_locations_cta_label' );
        $cta_link    = chroma_get_home_field( 'home_locations_cta_link' );

        return array(
                'heading'    => $heading ?: 'Our Locations',
                'subheading' => $subheading,
                'cta_label'  => $cta_label ?: 'View All Locations',
                'cta_link'   => $cta_link ?: '/locations',
        );
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

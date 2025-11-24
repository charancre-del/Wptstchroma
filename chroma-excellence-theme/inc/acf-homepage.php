<?php
/**
 * Homepage data helpers (hardcoded)
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Get Home Page ID (for thumbnail rendering)
 */
function chroma_get_home_page_id() {
        return get_option( 'page_on_front' ) ?: 0;
}

/**
 * Home Hero Data
 */
function chroma_home_hero() {
        return array(
                'heading'         => 'The art of <span class="italic text-chroma-red">growing up.</span>',
                'subheading'      => 'Where accredited excellence meets the warmth of home.',
                'cta_label'       => 'Schedule a Tour',
                'cta_url'         => '#tour',
                'secondary_label' => 'View Programs',
                'secondary_url'   => '/programs',
        );
}

/**
 * Home Stats
 */
function chroma_home_stats() {
        return array(
                array( 'value' => '19+', 'label' => 'Metro campuses' ),
                array( 'value' => '2,000+', 'label' => 'Children enrolled' ),
                array( 'value' => '4.8', 'label' => 'Avg parent rating' ),
                array( 'value' => '6w–12y', 'label' => 'Age range' ),
        );
}

/**
 * Prismpath expertise panels
 */
function chroma_home_prismpath_panels() {
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
                                'description' => 'Just as a prism refracts play into a full spectrum of development.',
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
 * Home FAQ Items
 */
function chroma_home_faq_items() {
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

/**
 * Home FAQ block
 */
function chroma_home_faq() {
        return array(
                'heading'    => 'Common questions from parents',
                'subheading' => 'We’ve answered a few of the questions parents ask most when choosing childcare and early learning.',
                'items'      => chroma_home_faq_items(),
                'cta_text'   => '',
                'cta_label'  => '',
                'cta_link'   => '',
        );
}

/**
 * Age-based program wizard options
 */
function chroma_home_program_wizard_options() {
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
 * Programs preview grid content
 */
function chroma_home_programs_preview() {
        return array(
                'heading'    => 'Our Programs',
                'subheading' => 'Choose the Chroma program designed for your child’s age and learning stage.',
                'cta_label'  => 'View All Programs',
                'cta_link'   => '/programs',
                'featured'   => array(
                        array(
                                'title'     => 'Infant Care',
                                'age_range' => '6 weeks–12 months',
                                'icon'      => 'fa-solid fa-baby',
                                'excerpt'   => 'Safe sleep practices, responsive caregiving, and sensory-rich experiences in a peaceful environment.',
                                'url'       => '/programs#infant',
                        ),
                        array(
                                'title'     => 'Toddler Program',
                                'age_range' => '1 year',
                                'icon'      => 'fa-solid fa-rocket',
                                'excerpt'   => 'Exploratory play, language bursts, and social skill building for new walkers and talkers.',
                                'url'       => '/programs#toddler',
                        ),
                        array(
                                'title'     => 'GA Pre-K',
                                'age_range' => '4 years',
                                'icon'      => 'fa-solid fa-graduation-cap',
                                'excerpt'   => 'Balanced academics, social-emotional learning, and joyful projects aligned with Georgia standards.',
                                'url'       => '/programs#ga-pre-k',
                        ),
                ),
        );
}

/**
 * Locations preview content
 */
function chroma_home_locations_preview() {
        static $cached;

        if ( isset( $cached ) ) {
                return $cached;
        }

        $heading    = '19+ neighborhood locations across Metro Atlanta';
        $subheading = 'Find a Chroma campus near your home or work. All locations share the same safety standards, curriculum framework, and warm Chroma culture.';
        $cta_label  = 'View All Locations';
        $cta_link   = '/locations';

        $locations = get_posts(
                array(
                        'post_type'        => 'location',
                        'post_status'      => 'publish',
                        'posts_per_page'   => -1,
                        'orderby'          => 'title',
                        'order'            => 'ASC',
                        'suppress_filters' => true,
                )
        );

        $map_points = array();
        $featured   = array();

        foreach ( $locations as $location ) {
                $post_id   = $location->ID;
                $title     = get_the_title( $post_id );
                $permalink = get_permalink( $post_id );

                $city    = function_exists( 'get_field' ) ? get_field( 'location_city', $post_id ) : get_post_meta( $post_id, 'location_city', true );
                $state   = function_exists( 'get_field' ) ? get_field( 'location_state', $post_id ) : get_post_meta( $post_id, 'location_state', true );
                $phone   = function_exists( 'get_field' ) ? get_field( 'location_phone', $post_id ) : get_post_meta( $post_id, 'location_phone', true );
                $address = function_exists( 'get_field' ) ? get_field( 'location_address', $post_id ) : get_post_meta( $post_id, 'location_address', true );

                $lat = function_exists( 'get_field' ) ? get_field( 'location_latitude', $post_id ) : get_post_meta( $post_id, 'location_latitude', true );
                $lng = function_exists( 'get_field' ) ? get_field( 'location_longitude', $post_id ) : get_post_meta( $post_id, 'location_longitude', true );

                if ( $lat && $lng ) {
                        $map_points[] = array(
                                'id'    => $post_id,
                                'name'  => $title,
                                'lat'   => (float) $lat,
                                'lng'   => (float) $lng,
                                'url'   => $permalink,
                                'city'  => $city,
                                'state' => $state,
                        );
                }

                $featured[] = array(
                        'title'   => $title,
                        'city'    => $city,
                        'state'   => $state,
                        'address' => $address,
                        'phone'   => $phone,
                        'url'     => $permalink,
                );
        }

        // If no dynamic locations exist, retain the previous static defaults.
        if ( empty( $featured ) ) {
                $map_points = array(
                        array(
                                'id'    => 1,
                                'name'  => 'Marietta – East',
                                'lat'   => 33.975,
                                'lng'   => -84.507,
                                'url'   => '/locations/marietta-east',
                                'city'  => 'Marietta',
                                'state' => 'GA',
                        ),
                        array(
                                'id'    => 2,
                                'name'  => 'Austell – Tramore',
                                'lat'   => 33.815,
                                'lng'   => -84.63,
                                'url'   => '/locations/austell-tramore',
                                'city'  => 'Austell',
                                'state' => 'GA',
                        ),
                        array(
                                'id'    => 3,
                                'name'  => 'Lawrenceville',
                                'lat'   => 33.956,
                                'lng'   => -83.99,
                                'url'   => '/locations/lawrenceville',
                                'city'  => 'Lawrenceville',
                                'state' => 'GA',
                        ),
                        array(
                                'id'    => 4,
                                'name'  => 'Johns Creek',
                                'lat'   => 34.028,
                                'lng'   => -84.198,
                                'url'   => '/locations/johns-creek',
                                'city'  => 'Johns Creek',
                                'state' => 'GA',
                        ),
                );

                $featured = array(
                        array(
                                'title'   => 'Marietta – East',
                                'city'    => 'Marietta',
                                'state'   => 'GA',
                                'address' => '2499 Shallowford Rd',
                                'phone'   => '(770) 555-1201',
                                'url'     => '/locations/marietta-east',
                        ),
                        array(
                                'title'   => 'Austell – Tramore',
                                'city'    => 'Austell',
                                'state'   => 'GA',
                                'address' => '2081 Mesa Valley Rd',
                                'phone'   => '(770) 555-4432',
                                'url'     => '/locations/austell-tramore',
                        ),
                        array(
                                'title'   => 'Lawrenceville',
                                'city'    => 'Lawrenceville',
                                'state'   => 'GA',
                                'address' => '3650 Club Dr NW',
                                'phone'   => '(770) 555-8890',
                                'url'     => '/locations/lawrenceville',
                        ),
                );
        }

        // Limit featured list to the first three items for layout consistency.
        $featured = array_slice( $featured, 0, 3 );

        $cached = array(
                'heading'    => $heading,
                'subheading' => $subheading,
                'cta_label'  => $cta_label,
                'cta_link'   => $cta_link,
                'map_points' => $map_points,
                'featured'   => $featured,
        );

        return $cached;
}

/**
 * Tour CTA content
 */
function chroma_home_tour_cta() {
        return array(
                'heading'    => 'Schedule a private tour',
                'subheading' => 'Share a few details and your preferred campus. A Chroma Director will reach out to confirm tour times.',
                'trust_text' => 'No obligation. We’ll never share your information.',
        );
}

/**
 * Home Featured Locations (static)
 */
function chroma_home_featured_locations() {
        $locations = chroma_home_locations_preview();
        return $locations['featured'];
}

/**
 * Home Featured Stories (static placeholders)
 */
function chroma_home_featured_stories() {
        return array(
                array(
                        'title'   => 'Inside the Prismpath™ Classroom',
                        'excerpt' => 'Take a peek at how our educators weave play and academics together each day.',
                        'url'     => '/stories/prismpath-classroom',
                ),
                array(
                        'title'   => 'Family-Style Dining at Chroma',
                        'excerpt' => 'Why shared meals matter for social-emotional growth and independence.',
                        'url'     => '/stories/family-style-dining',
                ),
                array(
                        'title'   => 'Partnering with Parents',
                        'excerpt' => 'See how we communicate daily to keep families connected to the classroom.',
                        'url'     => '/stories/partnering-with-parents',
                ),
        );
}

/**
 * Checkers for optional sections
 */
function chroma_home_has_prismpath_panels() {
        return true;
}

function chroma_home_has_program_wizard() {
        return true;
}

function chroma_home_has_curriculum_profiles() {
        return true;
}

function chroma_home_has_schedule_tracks() {
        return true;
}

function chroma_home_has_faq() {
        return true;
}

function chroma_home_has_stats() {
        return true;
}

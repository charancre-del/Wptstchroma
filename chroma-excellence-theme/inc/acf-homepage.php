<?php
/**
 * Homepage data helpers (hardcoded)
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
        exit;
}

/**
 * Get Home Page ID (for thumbnail rendering)
 */
function chroma_get_home_page_id()
{
        return get_option('page_on_front') ?: 0;
}

function chroma_home_get_content_value($post_id, $meta_key, $theme_mod_key, $default = '', $sanitize_callback = 'sanitize_text_field')
{
        $value = '';

        if (!empty($meta_key) && function_exists('chroma_get_translated_meta')) {
                $value = chroma_get_translated_meta($post_id, $meta_key, true);
        }

        if ($value === '' || $value === null) {
                $value = chroma_get_theme_mod($theme_mod_key, $default);
        }

        if ($sanitize_callback === 'wp_kses_post') {
                return wp_kses_post($value);
        }

        return call_user_func($sanitize_callback, $value);
}

function chroma_home_default_hero()
{
        return array(
                'heading' => __('The art of <span class="italic text-chroma-red">growing up.</span>', 'chroma-excellence'),
                'subheading' => __('Where thoughtful early learning meets the warmth of home. Our Prismpath™ framework supports children from infancy through school age with joyful, age-appropriate experiences.', 'chroma-excellence'),
                'cta_label' => __('Schedule a Tour', 'chroma-excellence'),
                'cta_url' => '#tour',
                'secondary_label' => __('View Programs', 'chroma-excellence'),
                'secondary_url' => chroma_get_program_archive_url(),
                'pill_format' => __('%d Metro Atlanta Locations', 'chroma-excellence'),
                'supporting_text' => __('Our experienced educators support each child\'s growth with warm relationships, intentional learning, and close family partnership.', 'chroma-excellence'),
                'rating_label' => __('4.8 Average Parent Rating', 'chroma-excellence'),
                'quality_badge_text' => __('Programs, credentials, and availability vary by campus', 'chroma-excellence'),
                'fallback_label' => __('Hero Image Coming Soon', 'chroma-excellence'),
                'badge_heading' => __('Kindergarten Ready', 'chroma-excellence'),
                'badge_text' => __('Comprehensive Prep', 'chroma-excellence'),
                'image_alt' => __('Chroma Classroom', 'chroma-excellence'),
        );
}

function chroma_home_default_stats()
{
        return array(
                array('key' => 'locations', 'value' => '0', 'label' => __('Metro campuses', 'chroma-excellence')),
                array('key' => 'families_served', 'value' => '2,000+', 'label' => __('Children enrolled', 'chroma-excellence')),
                array('key' => 'avg_parent_rating', 'value' => '4.8', 'label' => __('Avg parent rating', 'chroma-excellence')),
                array('value' => '6w–12y', 'label' => __('Age range', 'chroma-excellence')),
        );
}

function chroma_home_default_prismpath()
{
        return array(
                'feature' => array(
                        'eyebrow' => __('The Chroma Standard', 'chroma-excellence'),
                        'heading' => __('Grounded in Expertise. Wrapped in Love.', 'chroma-excellence'),
                        'subheading' => '',
                        'color_heading' => __('Every child brings their own beautiful color to the world.', 'chroma-excellence'),
                        'cta_label' => __('Meet the Team', 'chroma-excellence'),
                        'cta_url' => '/about/',
                ),
                'cards' => array(
                        array(
                                'badge' => __('Proprietary Model', 'chroma-excellence'),
                                'heading' => __('The Prismpath™ Curriculum', 'chroma-excellence'),
                                'text' => __('Just as a prism refracts light into a full spectrum of color, Prismpath™ refracts play into a full spectrum of development.', 'chroma-excellence'),
                                'icon_bg' => 'fa-solid fa-shapes',
                                'icon_badge' => 'fa-brands fa-connectdevelop',
                                'icon_check' => 'fa-solid fa-check-circle',
                        ),
                        array(
                                'badge' => '',
                                'heading' => __('Expert Care, Extended Family.', 'chroma-excellence'),
                                'text' => __('Our educators are state-certified professionals who understand that the most important credential is kindness.', 'chroma-excellence'),
                                'button' => __('Meet the Team', 'chroma-excellence'),
                                'url' => '/about/',
                                'icon_bg' => 'fa-solid fa-heart',
                                'icon_badge' => 'fa-solid fa-user-check',
                        ),
                        array(
                                'badge' => '',
                                'heading' => __('Wholesome Fuel', 'chroma-excellence'),
                                'text' => __('Organic, balanced meals served family-style to fuel growing minds.', 'chroma-excellence'),
                                'icon' => 'fa-solid fa-apple-whole',
                        ),
                        array(
                                'badge' => '',
                                'heading' => __('Uncompromised Safety', 'chroma-excellence'),
                                'text' => __('Secure, monitored facilities with open-door transparency for parents.', 'chroma-excellence'),
                                'icon' => 'fa-solid fa-shield-halved',
                        ),
                ),
                'readiness' => array(
                        'heading' => __('Kindergarten Readiness', 'chroma-excellence'),
                        'description' => __('Our graduates enter school confident, socially capable, and academically prepared.', 'chroma-excellence'),
                ),
        );
}

function chroma_home_get_theme_mod_json($key, $default = array())
{
        $raw = chroma_get_theme_mod($key, '');

        if (is_array($raw)) {
                return $raw;
        }

        if (!is_string($raw)) {
                return $default;
        }

        if (empty($raw)) {
                return $default;
        }

        $decoded = json_decode($raw, true);

        if (JSON_ERROR_NONE !== json_last_error() || !is_array($decoded)) {
                return $default;
        }

        return $decoded;
}

/**
 * Home Hero Data
 */
function chroma_home_hero()
{
        $defaults = chroma_home_default_hero();
        $post_id = chroma_get_home_page_id();

        $hero = array(
                'heading' => chroma_home_get_content_value($post_id, 'home_hero_heading', 'chroma_home_hero_heading', $defaults['heading'], 'wp_kses_post'),
                'subheading' => chroma_home_get_content_value($post_id, 'home_hero_subheading', 'chroma_home_hero_subheading', $defaults['subheading'], 'sanitize_text_field'),
                'cta_label' => chroma_home_get_content_value($post_id, 'home_hero_cta_label', 'chroma_home_hero_cta_label', $defaults['cta_label'], 'sanitize_text_field'),
                'cta_url' => chroma_get_localized_url(esc_url_raw(chroma_get_theme_mod('chroma_home_hero_cta_url', $defaults['cta_url']))),
                'secondary_label' => chroma_home_get_content_value($post_id, 'home_hero_secondary_label', 'chroma_home_hero_secondary_label', $defaults['secondary_label'], 'sanitize_text_field'),
                'secondary_url' => chroma_get_localized_url(esc_url_raw(chroma_get_theme_mod('chroma_home_hero_secondary_url', $defaults['secondary_url']))),
                'pill_format' => sanitize_text_field(chroma_get_theme_mod('chroma_home_hero_pill_format', $defaults['pill_format'])),
                'supporting_text' => sanitize_text_field(chroma_get_theme_mod('chroma_home_hero_supporting_text', $defaults['supporting_text'])),
                'rating_label' => sanitize_text_field(chroma_get_theme_mod('chroma_home_hero_rating_label', $defaults['rating_label'])),
                'quality_badge_text' => sanitize_text_field(chroma_get_theme_mod('chroma_home_hero_quality_badge_text', $defaults['quality_badge_text'])),
                'fallback_label' => sanitize_text_field(chroma_get_theme_mod('chroma_home_hero_fallback_label', $defaults['fallback_label'])),
                'badge_heading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_hero_badge_heading', $defaults['badge_heading'])),
                'badge_text' => sanitize_text_field(chroma_get_theme_mod('chroma_home_hero_badge_text', $defaults['badge_text'])),
                'image_alt' => sanitize_text_field(chroma_get_theme_mod('chroma_home_hero_image_alt', $defaults['image_alt'])),
        );

        if (false !== stripos($hero['subheading'], 'accredited excellence')) {
                $hero['subheading'] = $defaults['subheading'];
        }

        if (false !== stripos($hero['supporting_text'], 'licensed clinicians supporting each child')) {
                $hero['supporting_text'] = $defaults['supporting_text'];
        }

        if (false !== stripos($hero['quality_badge_text'], 'Quality Rated') || false !== stripos($hero['quality_badge_text'], 'GA Pre-K Partner')) {
                $hero['quality_badge_text'] = $defaults['quality_badge_text'];
        }

        $hero['pill_format'] = (string) preg_replace('/%d\s*\+/u', '%d', $hero['pill_format']);

        return $hero;
}

/**
 * Home Stats
 */
function chroma_home_normalize_stat_text($text)
{
        $text = remove_accents(wp_strip_all_tags((string) $text));
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text);
        return trim((string) preg_replace('/\s+/', ' ', $text));
}

function chroma_home_infer_stat_key($stat, $index = 0)
{
        $raw_key = sanitize_key($stat['key'] ?? '');
        if ($raw_key !== '') {
                return $raw_key;
        }

        $label = chroma_home_normalize_stat_text($stat['label'] ?? '');
        $known_labels = array(
                'locations' => array('locations', 'metro campuses', 'campuses', 'metro atlanta locations', 'metro atlanta campuses', 'metro locations'),
                'families_served' => array('families served', 'children enrolled', 'children served', 'students', 'students served', 'families'),
                'avg_parent_rating' => array('avg parent rating', 'average parent rating', 'parent rating', 'rating'),
                'age_range' => array('age range', 'ages served', 'age groups'),
                'years_excellence' => array('years of total aggregated excellence', 'years of excellence', 'years experience', 'years of experience'),
                'meals' => array('wholesome meals', 'meals'),
        );

        foreach ($known_labels as $key => $labels) {
                if (in_array($label, $labels, true)) {
                        return $key;
                }
        }

        if ($label !== '') {
                if (strpos($label, 'family') !== false || strpos($label, 'student') !== false || strpos($label, 'children') !== false) {
                        return 'families_served';
                }

                if (strpos($label, 'campus') !== false || strpos($label, 'location') !== false) {
                        return 'locations';
                }
        }

        $fallback_keys = array('locations', 'families_served', 'avg_parent_rating', 'age_range');

        return $fallback_keys[$index] ?? 'stat_' . ($index + 1);
}

function chroma_home_get_location_count()
{
        $counts = post_type_exists('location') ? wp_count_posts('location') : null;
        return isset($counts->publish) ? max(0, (int) $counts->publish) : 0;
}

function chroma_home_format_location_stat_value($fallback_value, $location_count)
{
        $location_count = max(0, (int) $location_count);

        if ($location_count > 0) {
                return (string) $location_count;
        }

        return sanitize_text_field($fallback_value);
}

function chroma_home_normalize_location_count_copy($text, $location_count = null)
{
        $text = (string) $text;
        $location_count = $location_count === null ? chroma_home_get_location_count() : (int) $location_count;

        if ($text === '' || $location_count < 1) {
                return $text;
        }

        return (string) preg_replace_callback(
                '/\b\d+\+?\s+((?:Metro Atlanta\s+|neighborhood\s+)?(?:campuses|locations))\b/i',
                static function ($matches) use ($location_count) {
                        return $location_count . ' ' . $matches[1];
                },
                $text
        );
}

function chroma_home_stats()
{
        $post_id = chroma_get_home_page_id();
        $stats = array();

        $is_spanish = false;
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/es/') !== false) {
                $is_spanish = true;
        }
        if (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish()) {
                $is_spanish = true;
        }

        if ($is_spanish && $post_id) {
                $stats_json = get_post_meta($post_id, '_chroma_es_home_stats_json', true);

                if ($stats_json) {
                        $decoded = json_decode($stats_json, true);
                        if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
                                $stats = $decoded;
                        }
                }
        }

        if (empty($stats)) {
                $stats = chroma_home_get_theme_mod_json('chroma_home_stats_json', chroma_home_default_stats());
        }

        $cleaned = array();

        // Define color cycle for stats (red, yellow, blue, green)
        $colors = array('chroma-red', 'chroma-yellow', 'chroma-blue', 'chroma-green');
        $index = 0;
        $location_count = chroma_home_get_location_count();

        foreach ($stats as $stat) {
                $key = chroma_home_infer_stat_key($stat, $index);
                $value = sanitize_text_field($stat['value'] ?? '');
                if ($key === 'locations') {
                        $value = chroma_home_format_location_stat_value($value, $location_count);
                }

                $cleaned[] = array(
                        'key' => $key,
                        'value' => $value,
                        'label' => sanitize_text_field($stat['label'] ?? ''),
                        'color' => $colors[$index % count($colors)],
                );
                $index++;
        }

        return $cleaned;
}

function chroma_home_get_stat_by_key($key)
{
        $target_key = sanitize_key((string) $key);
        if ($target_key === '') {
                return null;
        }

        $stats = chroma_home_stats();
        foreach ($stats as $stat) {
                if (($stat['key'] ?? '') === $target_key) {
                        return $stat;
                }
        }

        if ($target_key === 'families_served' && isset($stats[1]) && is_array($stats[1])) {
                return $stats[1];
        }

        return null;
}

/**
 * Clear cached homepage stats strip markup.
 */
function chroma_home_clear_stats_strip_cache()
{
        delete_transient('chroma_home_stats_strip_en');
        delete_transient('chroma_home_stats_strip_es');
}
add_action('customize_save_after', 'chroma_home_clear_stats_strip_cache');

/**
 * Clear homepage stats strip cache when the front page is saved.
 *
 * @param int $post_id Saved page ID.
 */
function chroma_home_maybe_clear_stats_strip_cache_on_page_save($post_id)
{
        if ((int) $post_id !== (int) get_option('page_on_front')) {
                return;
        }

        chroma_home_clear_stats_strip_cache();
}
add_action('save_post_page', 'chroma_home_maybe_clear_stats_strip_cache_on_page_save');

/**
 * Prismpath expertise panels
 */
function chroma_home_prismpath_panels()
{
        $defaults = chroma_home_default_prismpath();
        $post_id = chroma_get_home_page_id();

        // Feature Text
        $feature = $defaults['feature'];
        $eyebrow = chroma_get_translated_meta($post_id, 'home_prismpath_eyebrow', true);
        $heading = chroma_get_translated_meta($post_id, 'home_prismpath_heading', true);
        $subheading = chroma_get_translated_meta($post_id, 'home_prismpath_subheading', true);
        $color_heading = chroma_get_translated_meta($post_id, 'home_prismpath_color_heading', true);
        $cta_label = chroma_get_translated_meta($post_id, 'home_prismpath_cta_label', true);

        $feature = array(
                'eyebrow' => sanitize_text_field($eyebrow ?: chroma_get_theme_mod('chroma_home_prismpath_eyebrow', $feature['eyebrow'])),
                'heading' => sanitize_text_field($heading ?: chroma_get_theme_mod('chroma_home_prismpath_heading', $feature['heading'])),
                'subheading' => sanitize_text_field($subheading ?: chroma_get_theme_mod('chroma_home_prismpath_subheading', $feature['subheading'])),
                'color_heading' => sanitize_text_field($color_heading ?: chroma_get_theme_mod('chroma_home_prismpath_color_heading', $feature['color_heading'])),
                'cta_label' => sanitize_text_field($cta_label ?: chroma_get_theme_mod('chroma_home_prismpath_cta_label', $feature['cta_label'])),
                'cta_url' => chroma_get_localized_url(esc_url_raw(chroma_get_theme_mod('chroma_home_prismpath_cta_url', $feature['cta_url']))),
        );

        // Cards (Check JSON Override)
        $cards_json = chroma_get_translated_meta($post_id, 'home_prismpath_cards_json', true);
        $cards = array();
        if ($cards_json) {
                $decoded = json_decode($cards_json, true);
                if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
                        $cards = $decoded;
                }
        }

        if (empty($cards)) {
                $cards = chroma_home_get_theme_mod_json('chroma_home_prismpath_cards_json', $defaults['cards']);
        }

        // Sanitize Cards
        $cards = array_map(
                function ($card, $index) use ($defaults) {
                        $default_card = $defaults['cards'][$index] ?? array();
                        return array(
                                'badge' => sanitize_text_field($card['badge'] ?? $default_card['badge'] ?? ''),
                                'heading' => sanitize_text_field($card['heading'] ?? $default_card['heading'] ?? ''),
                                'text' => sanitize_textarea_field($card['text'] ?? $default_card['text'] ?? ''),
                                'button' => sanitize_text_field($card['button'] ?? $default_card['button'] ?? ''),
                                'url' => chroma_get_localized_url(esc_url_raw($card['url'] ?? $default_card['url'] ?? '')),
                                'icon' => sanitize_text_field(($card['icon'] ?? '') ?: ($default_card['icon'] ?? '')),
                                'icon_bg' => sanitize_text_field(($card['icon_bg'] ?? '') ?: ($default_card['icon_bg'] ?? '')),
                                'icon_badge' => sanitize_text_field(($card['icon_badge'] ?? '') ?: ($default_card['icon_badge'] ?? '')),
                                'icon_check' => sanitize_text_field(($card['icon_check'] ?? '') ?: ($default_card['icon_check'] ?? '')),
                        );
                },
                $cards,
                array_keys($cards)
        );

        // Readiness
        $readiness = $defaults['readiness'];
        $readiness_heading = chroma_get_translated_meta($post_id, 'home_prismpath_readiness_heading', true);
        $readiness_desc = chroma_get_translated_meta($post_id, 'home_prismpath_readiness_desc', true);

        $readiness = array(
                'heading' => sanitize_text_field($readiness_heading ?: chroma_get_theme_mod('chroma_home_prismpath_readiness_heading', $readiness['heading'])),
                'description' => sanitize_textarea_field($readiness_desc ?: chroma_get_theme_mod('chroma_home_prismpath_readiness_desc', $readiness['description'])),
        );

        return array(
                'feature' => $feature,
                'cards' => $cards,
                'readiness' => $readiness,
        );
}

function chroma_home_default_program_wizard_options()
{
        $program_url = chroma_get_program_archive_url();

        return array(
                array(
                        'key' => 'infant',
                        'emoji' => '👶',
                        'label' => __("Infant\n(6 weeks–12m)", 'chroma-excellence'),
                        'description' => __('Low ratios, safe sleep practices, responsive caregiving, and sensory play in a peaceful, predictable environment.', 'chroma-excellence'),
                        'link' => $program_url . '#infant',
                ),
                array(
                        'key' => 'toddler',
                        'emoji' => '🚀',
                        'label' => __("Toddler\n(1 year)", 'chroma-excellence'),
                        'description' => __('Curated environments for walkers and explorers with language bursts and social skills.', 'chroma-excellence'),
                        'link' => $program_url . '#toddler',
                ),
                array(
                        'key' => 'preschool',
                        'emoji' => '🎨',
                        'label' => __("Preschool\n(2 years)", 'chroma-excellence'),
                        'description' => __('Early concepts in math, literacy, and science introduced through hands-on centers and guided play.', 'chroma-excellence'),
                        'link' => $program_url . '#preschool',
                ),
                array(
                        'key' => 'prep',
                        'emoji' => '✏️',
                        'label' => __("Pre-K Prep\n(3 years)", 'chroma-excellence'),
                        'description' => __('Structured centers and small-group instruction that build independence before GA Pre-K.', 'chroma-excellence'),
                        'link' => $program_url . '#pre-k-prep',
                ),
                array(
                        'key' => 'prek',
                        'emoji' => '🎓',
                        'label' => __("GA Pre-K\n(4 years)", 'chroma-excellence'),
                        'description' => __('Balanced academic readiness, social-emotional learning, and joyful experiences aligned with GA standards.', 'chroma-excellence'),
                        'link' => $program_url . '#ga-pre-k',
                ),
                array(
                        'key' => 'afterschool',
                        'emoji' => '🚌',
                        'label' => __("After School\n(5–12 years)", 'chroma-excellence'),
                        'description' => __('Transportation from local schools, homework support, clubs, and outdoor play.', 'chroma-excellence'),
                        'link' => $program_url . '#after-school',
                ),
        );
}

function chroma_home_default_program_wizard_content()
{
        return array(
                'heading' => __('Find the right program in 10 seconds', 'chroma-excellence'),
                'subheading' => __('Choose your child\'s age and we\'ll suggest the Chroma program designed for their development stage and your family\'s needs.', 'chroma-excellence'),
                'primary_cta_label' => __('View All Programs', 'chroma-excellence'),
                'primary_cta_aria_label' => __('View all programs', 'chroma-excellence'),
                'secondary_cta_label' => __('Speak to an enrollment specialist', 'chroma-excellence'),
                'image_alt' => __('Program Preview', 'chroma-excellence'),
                'reset_label' => __('Start Over', 'chroma-excellence'),
        );
}

function chroma_home_program_wizard_content()
{
        $defaults = chroma_home_default_program_wizard_content();

        return array(
                'heading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_program_wizard_heading', $defaults['heading'])),
                'subheading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_program_wizard_subheading', $defaults['subheading'])),
                'primary_cta_label' => sanitize_text_field(chroma_get_theme_mod('chroma_home_program_wizard_primary_cta_label', $defaults['primary_cta_label'])),
                'primary_cta_aria_label' => sanitize_text_field(chroma_get_theme_mod('chroma_home_program_wizard_primary_cta_aria_label', $defaults['primary_cta_aria_label'])),
                'secondary_cta_label' => sanitize_text_field(chroma_get_theme_mod('chroma_home_program_wizard_secondary_cta_label', $defaults['secondary_cta_label'])),
                'image_alt' => sanitize_text_field(chroma_get_theme_mod('chroma_home_program_wizard_image_alt', $defaults['image_alt'])),
                'reset_label' => sanitize_text_field(chroma_get_theme_mod('chroma_home_program_wizard_reset_label', $defaults['reset_label'])),
        );
}

function chroma_home_default_curriculum_profiles()
{
        return array(
                'labels' => array('Physical', 'Emotional', 'Social', 'Academic', 'Creative'),
                'profiles' => array(
                        array(
                                'key' => 'infant',
                                'label' => 'Infant',
                                'title' => 'Foundation Phase',
                                'description' => 'Infant classrooms emphasize emotional security, attachment, physical health, and sensory experiences. Academics are embedded through language-rich interactions.',
                                'color' => '#D67D6B',
                                'data' => array(90, 90, 40, 15, 40),
                        ),
                        array(
                                'key' => 'toddler',
                                'label' => 'Toddler',
                                'title' => 'Discovery Phase',
                                'description' => 'Toddlers explore movement, language, early problem-solving, and social skills through guided play and routines.',
                                'color' => '#4A6C7C',
                                'data' => array(85, 75, 65, 30, 70),
                        ),
                        array(
                                'key' => 'preschool',
                                'label' => 'Preschool',
                                'title' => 'Exploration Phase',
                                'description' => 'Preschoolers work on early literacy, math concepts, dramatic play, and collaborative projects, supported by strong routines.',
                                'color' => '#E6BE75',
                                'data' => array(75, 65, 70, 55, 80),
                        ),
                        array(
                                'key' => 'prep',
                                'label' => 'Pre-K Prep',
                                'title' => 'Pre-K Prep Phase',
                                'description' => 'Children build stamina for small-group work, early writing, and multi-step directions while strengthening self-regulation.',
                                'color' => '#2F4858',
                                'data' => array(65, 60, 75, 75, 70),
                        ),
                        array(
                                'key' => 'prek',
                                'label' => 'GA Pre-K',
                                'title' => 'GA Pre-K Readiness',
                                'description' => 'Balanced academic readiness, social-emotional learning, and joyful experiences aligned with GA standards.',
                                'color' => '#4A6C7C',
                                'data' => array(60, 60, 80, 90, 70),
                        ),
                        array(
                                'key' => 'kindergarten',
                                'label' => 'Kindergarten',
                                'title' => 'Kindergarten Mastery',
                                'description' => 'A rigorous, joyful environment ensuring mastery in early reading, conceptual mathematics, and collaborative problem-solving.',
                                'color' => '#A8551E',
                                'data' => array(55, 65, 85, 95, 75),
                        ),
                        array(
                                'key' => 'afterschool',
                                'label' => 'After School',
                                'title' => 'Enrichment Phase',
                                'description' => 'School-age programming offers homework help, social clubs, athletic play, and creative enrichment for older children.',
                                'color' => '#E6BE75',
                                'data' => array(50, 70, 85, 75, 80),
                        ),
                ),
        );
}

function chroma_home_default_curriculum_content()
{
        return array(
                'eyebrow' => __('The Prismpath™ Curriculum', 'chroma-excellence'),
                'heading' => __('A curriculum that shifts as your child grows', 'chroma-excellence'),
                'subheading' => __('Our Prismpath™ framework balances five pillars – physical, emotional, social, academic, and creative development. The mix changes at each age so your child gets exactly what they need, when they need it.', 'chroma-excellence'),
                'chart_aria_label' => __('Curriculum focus radar chart', 'chroma-excellence'),
        );
}

function chroma_home_curriculum_content()
{
        $defaults = chroma_home_default_curriculum_content();

        return array(
                'eyebrow' => sanitize_text_field(chroma_get_theme_mod('chroma_home_curriculum_eyebrow', $defaults['eyebrow'])),
                'heading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_curriculum_heading', $defaults['heading'])),
                'subheading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_curriculum_subheading', $defaults['subheading'])),
                'chart_aria_label' => sanitize_text_field(chroma_get_theme_mod('chroma_home_curriculum_chart_aria_label', $defaults['chart_aria_label'])),
        );
}

/**
 * Home FAQ Items
 */
function chroma_home_default_faq_items()
{
        return array(
                array(
                        'question' => __('Do you offer GA Lottery Pre-K?', 'chroma-excellence'),
						'answer' => __('Georgia Pre-K is available at most Chroma campuses. Contact your preferred campus directly to confirm current availability, eligibility, and enrollment details.', 'chroma-excellence'),
                ),
                array(
                        'question' => __('What ages do you serve?', 'chroma-excellence'),
                        'answer' => __('Most campuses serve children from 6 weeks through 12 years old.', 'chroma-excellence'),
                ),
                array(
                        'question' => __('Are meals and snacks included?', 'chroma-excellence'),
                        'answer' => __('Yes. Through the Child and Adult Care Food Program (CACFP).', 'chroma-excellence'),
                ),
                array(
                        'question' => __('How do you communicate with parents?', 'chroma-excellence'),
                        'answer' => __('We use a modern parent app and in-person conversations to keep you informed.', 'chroma-excellence'),
                ),
                array(
                        'question' => __('Can I tour before enrolling?', 'chroma-excellence'),
                        'answer' => __('Absolutely. We encourage tours so you can meet the Director and see classrooms in action.', 'chroma-excellence'),
                ),
        );
}

function chroma_home_default_faq()
{
        return array(
                'heading' => __('Common questions from parents', 'chroma-excellence'),
                'subheading' => __('We’ve answered a few of the questions parents ask most when choosing childcare and early learning.', 'chroma-excellence'),
                'items' => chroma_home_default_faq_items(),
                'cta_text' => '',
                'cta_label' => '',
                'cta_link' => '',
        );
}

/**
 * Curriculum radar profiles
 */
function chroma_home_default_schedule_tracks()
{
        return array(
                array(
                        'key' => 'infant',
                        'label' => 'Infants',
                        'title' => 'The Nurturing Nest',
                        'description' => 'Individualized schedules follow infants’ cues for sleeping and eating, with gentle sensory play.',
                        'color' => 'chroma-blue',
                        'background' => 'bg-chroma-blueLight',
                        'image' => 'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?q=80&w=800&auto=format&fit=crop',
                        'steps' => array(
                                array(
                                        'time' => 'AM',
                                        'title' => 'Warm Welcome & Cuddles',
                                        'copy' => 'Transition from parent, bottle feeding, and floor play.',
                                ),
                                array(
                                        'time' => 'Mid',
                                        'title' => 'Sensory Discovery',
                                        'copy' => 'Tummy time, soft textures, and mirror play.',
                                ),
                                array(
                                        'time' => 'PM',
                                        'title' => 'Stroller Walk & Songs',
                                        'copy' => 'Fresh air (weather permitting) and gentle music.',
                                ),
                        ),
                ),
                array(
                        'key' => 'toddler',
                        'label' => 'Toddlers',
                        'title' => 'Explorers & Builders',
                        'description' => 'Structured circle time and communal meals help toddlers understand social cues and transitions.',
                        'color' => 'chroma-yellow',
                        'background' => 'bg-chroma-yellowLight',
                        'image' => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?q=80&w=800&auto=format&fit=crop',
                        'steps' => array(
                                array(
                                        'time' => '9:00',
                                        'title' => 'Morning Circle',
                                        'copy' => 'Songs, greeting friends, and introducing the daily theme.',
                                ),
                                array(
                                        'time' => '10:30',
                                        'title' => 'Prismpath Play',
                                        'copy' => 'Block building, art stations, and guided motor skills.',
                                ),
                                array(
                                        'time' => '12:00',
                                        'title' => 'Family-Style Lunch',
                                        'copy' => 'Learning to pass bowls, use utensils, and chat with friends.',
                                ),
                        ),
                ),
                array(
                        'key' => 'prek',
                        'label' => 'Pre-K',
                        'title' => 'Kindergarten Readiness',
                        'description' => 'The Pre-K rhythm mirrors elementary flow, building stamina and focus.',
                        'color' => 'chroma-red',
                        'background' => 'bg-chroma-redLight',
                        'image' => 'https://images.unsplash.com/photo-1503919545874-86c1d9a04595?q=80&w=800&auto=format&fit=crop',
                        'steps' => array(
                                array(
                                        'time' => '9:00',
                                        'title' => 'Literacy & Logic',
                                        'copy' => 'Phonics games, calendar math, and story comprehension.',
                                ),
                                array(
                                        'time' => '11:00',
                                        'title' => 'Project-Based Learning',
                                        'copy' => 'Collaborative science experiments and art projects.',
                                ),
                                array(
                                        'time' => '2:00',
                                        'title' => 'Social Centers',
                                        'copy' => 'Dramatic play and negotiation skills.',
                                ),
                        ),
                ),
        );
}

function chroma_home_clean_program_copy($copy)
{
        $copy = wp_strip_all_tags(strip_shortcodes((string) $copy));
        $charset = get_bloginfo('charset') ?: 'UTF-8';

        for ($i = 0; $i < 3; $i++) {
                $decoded = html_entity_decode(wp_specialchars_decode($copy, ENT_QUOTES), ENT_QUOTES | ENT_HTML5, $charset);
                if ($decoded === $copy) {
                        break;
                }
                $copy = $decoded;
        }

        $copy = preg_replace('/\x{00a0}/u', ' ', $copy);
        $copy = preg_replace('/\s*Find\s+[^.?!\r\n]*?\s+Near\s+You[^\s.?!\r\n]*.*$/iu', '', $copy);
        $copy = preg_replace('/\s*(?:&bull;|•)\s*[^.?!\r\n]*$/iu', '', $copy);
        $copy = preg_replace('/\s+/u', ' ', $copy);

        return trim($copy);
}

function chroma_home_program_public_key($post_id)
{
        $anchor_slug = sanitize_title((string) get_post_meta($post_id, 'program_anchor_slug', true));
        if ($anchor_slug !== '' && $anchor_slug !== 'program_anchor_slug') {
                return $anchor_slug;
        }

        $permalink_path = (string) wp_parse_url((string) get_permalink($post_id), PHP_URL_PATH);
        $public_slug = sanitize_title(basename(untrailingslashit($permalink_path)));
        if ($public_slug !== '') {
                return $public_slug;
        }

        $post_slug = sanitize_title((string) get_post_field('post_name', $post_id));
        return $post_slug !== '' ? $post_slug : 'program-' . (int) $post_id;
}

function chroma_home_program_fallback_summary($post_id)
{
        $slug = chroma_home_program_public_key($post_id);
        $title = strtolower((string) get_the_title($post_id));
        $summaries = array(
                'infant-care' => __('A peaceful, shoeless infant classroom with responsive caregiving, safe sleep routines, and sensory discovery for early growth.', 'chroma-excellence'),
                'infant' => __('A peaceful, shoeless infant classroom with responsive caregiving, safe sleep routines, and sensory discovery for early growth.', 'chroma-excellence'),
                'toddlers' => __('Toddlers build language, movement, confidence, and social skills through guided play, routines, and hands-on exploration.', 'chroma-excellence'),
                'toddler' => __('Toddlers build language, movement, confidence, and social skills through guided play, routines, and hands-on exploration.', 'chroma-excellence'),
                'preschool' => __('Preschoolers explore early literacy, math, science, art, and friendship skills through purposeful centers and joyful classroom projects.', 'chroma-excellence'),
                'pre-k-prep' => __('Three-year-olds strengthen independence, self-regulation, early writing, and small-group readiness before Pre-K.', 'chroma-excellence'),
                'pre-k-ga-pre-k' => __('Pre-K blends kindergarten readiness, social-emotional learning, and joyful experiences aligned with Georgia early learning standards.', 'chroma-excellence'),
                'ga-pre-k' => __('Pre-K blends kindergarten readiness, social-emotional learning, and joyful experiences aligned with Georgia early learning standards.', 'chroma-excellence'),
                'prek' => __('Pre-K blends kindergarten readiness, social-emotional learning, and joyful experiences aligned with Georgia early learning standards.', 'chroma-excellence'),
                'schoolagers' => __('School-age students get homework support, transportation from local schools, clubs, outdoor play, and creative enrichment.', 'chroma-excellence'),
                'afterschool' => __('School-age students get homework support, transportation from local schools, clubs, outdoor play, and creative enrichment.', 'chroma-excellence'),
                'camp' => __('Seasonal camp days bring field trips, creative projects, outdoor play, friendships, and flexible care during school breaks.', 'chroma-excellence'),
                'kindergarten' => __('Kindergarten students grow through rigorous early reading, conceptual math, collaboration, creativity, and joyful mastery.', 'chroma-excellence'),
                'rising-pre-k' => __('Rising Pre-K supports classroom confidence, early academics, independence, and routines for children preparing for Pre-K.', 'chroma-excellence'),
                'rising-kindergarten' => __('Rising Kindergarten helps Pre-K graduates practice literacy, math, routines, and social skills before the next school year.', 'chroma-excellence'),
                'parents-day-out' => __('Parent\'s Day Out offers a warm, playful classroom rhythm for short-day care, socialization, creativity, and early independence.', 'chroma-excellence'),
        );

        if (isset($summaries[$slug])) {
                return $summaries[$slug];
        }

        foreach ($summaries as $key => $summary) {
                if (false !== strpos($title, str_replace('-', ' ', $key))) {
                        return $summary;
                }
        }

        foreach (chroma_home_default_program_wizard_options() as $option) {
                $option_key = sanitize_title($option['key'] ?? '');
                $option_label = strtolower(sanitize_text_field($option['label'] ?? ''));
                if ($option_key === $slug || ($option_label && false !== strpos($title, $option_label))) {
                        return sanitize_textarea_field($option['description'] ?? '');
                }
        }

        return __('A nurturing, age-appropriate Chroma program designed to support your child\'s growth, confidence, and readiness.', 'chroma-excellence');
}

function chroma_home_program_summary($post_id)
{
        $excerpt = get_post_field('post_excerpt', $post_id, 'raw');

        if ('' === trim((string) $excerpt)) {
                $content = get_post_field('post_content', $post_id, 'raw');
                $excerpt = wp_trim_words(wp_strip_all_tags(strip_shortcodes((string) $content)), 32, '');
        }

        $excerpt = chroma_home_clean_program_copy($excerpt);
        if ('' !== $excerpt) {
                return $excerpt;
        }

        return chroma_home_program_fallback_summary($post_id);
}

/**
 * Age-based program wizard options - Pull from Program CPT
 */
function chroma_home_program_wizard_options()
{
        $token = chroma_get_last_changed('programs');
        $cache_key = 'home_wizard_options:v5:' . $token;
        $cached = wp_cache_get($cache_key, 'chroma');

        if (false !== $cached) {
                return $cached;
        }

        $programs = new WP_Query(array(
                'post_type' => 'program',
                'posts_per_page' => 50, // Strict cap
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_status' => 'publish',
                'no_found_rows' => true,
                'update_post_meta_cache' => true,
        ));

        if (!$programs->have_posts()) {
                $options = chroma_home_get_theme_mod_json('chroma_home_program_wizard_json', chroma_home_default_program_wizard_options());
                $program_url = chroma_get_program_archive_url();

                $processed = array_map(function ($item) use ($program_url) {
                        $key = sanitize_title($item['key'] ?? '');
                        $anchor_slug = chroma_program_anchor_for_key($key);
                        $link_target = $anchor_slug ?: $key;
                        return array(
                                'key' => $key,
                                'emoji' => sanitize_text_field($item['emoji'] ?? ''),
                                'label' => sanitize_text_field($item['label'] ?? ''),
                                'program_title' => sanitize_text_field($item['program_title'] ?? ($item['label'] ?? '')),
                                'age_label' => sanitize_text_field($item['age_label'] ?? ''),
                                'description' => sanitize_textarea_field(chroma_home_clean_program_copy($item['description'] ?? '')),
                                'link' => chroma_get_localized_url(esc_url_raw($program_url . '#' . $link_target)),
                        );
                }, $options);

                wp_cache_set($cache_key, $processed, 'chroma', DAY_IN_SECONDS);
                return $processed;
        }

        $options = array();
        while ($programs->have_posts()) {
                $programs->the_post();
                $post_id = get_the_ID();
                $icon = chroma_get_translated_meta($post_id, 'program_icon', true) ?: '📚';
                $age_range = chroma_get_translated_meta($post_id, 'program_age_range', true) ?: '';
                $excerpt = chroma_home_program_summary($post_id);
                $anchor_slug = chroma_home_program_public_key($post_id);
                $image_url = get_the_post_thumbnail_url($post_id, 'large') ?: 'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?q=80&w=800&auto=format&fit=crop';
                $label = get_the_title();
                $program_title = $label;
                $age_label = trim($age_range, '() ');
                if ($age_range) {
                        $label .= ' (' . $age_label . ')';
                }

                $options[] = array(
                        'key' => $anchor_slug,
                        'emoji' => $icon,
                        'label' => $label,
                        'program_title' => $program_title,
                        'age_label' => $age_label,
                        'description' => $excerpt,
                        'link' => chroma_get_localized_url(get_permalink($post_id)),
                        'image' => $image_url,
                );
        }
        wp_reset_postdata();

        wp_cache_set($cache_key, $options, 'chroma', DAY_IN_SECONDS);
        return $options;
}

function chroma_home_curriculum_profiles()
{
        $defaults = chroma_home_default_curriculum_profiles();
        $token = chroma_get_last_changed('programs');
        $cache_key = 'home_curriculum_profiles:v5:' . $token;
        $cached = wp_cache_get($cache_key, 'chroma');

        if (false !== $cached) {
                return $cached;
        }

        $sanitize_profile = static function ($profile) {
                $color = $profile['color'] ?? '';
                if (!sanitize_hex_color($color)) {
                        $color = '#4A6C7C';
                }

                $raw_data = is_array($profile['data'] ?? null) ? $profile['data'] : array();
                $data = array();

                for ($i = 0; $i < 5; $i++) {
                        $value = isset($raw_data[$i]) ? (int) $raw_data[$i] : 50;
                        $data[] = max(0, min(100, $value));
                }

                return array(
                        'key' => sanitize_title($profile['key'] ?? ''),
                        'label' => sanitize_text_field($profile['label'] ?? ''),
                        'title' => sanitize_text_field($profile['title'] ?? ''),
                        'description' => sanitize_textarea_field($profile['description'] ?? ''),
                        'color' => $color,
                        'data' => $data,
                );
        };

        $programs = get_posts(array(
                'post_type' => 'program',
                'posts_per_page' => 50,
                'post_status' => 'publish',
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'no_found_rows' => true,
                'update_post_meta_cache' => true,
        ));

        $profiles = array();
        if (!empty($programs)) {
                $color_map = array(
                        'red' => '#D67D6B',
                        'blue' => '#4A6C7C',
                        'yellow' => '#E6BE75',
                        'blueDark' => '#2F4858',
                        'green' => '#8DA399',
                        'orange' => '#C26524',
                        'teal' => '#4A6C7C',
                );
                $used_keys = array();

                foreach ($programs as $program) {
                        $post_id = (int) $program->ID;
                        $slug = chroma_home_program_public_key($post_id);
                        $anchor_slug = sanitize_title((string) get_post_meta($post_id, 'program_anchor_slug', true));
                        if ($anchor_slug === 'program_anchor_slug') {
                                $anchor_slug = '';
                        }
                        $key = sanitize_title($anchor_slug ?: $slug ?: $post_id);

                        if ($key === '') {
                                $key = 'program-' . $post_id;
                        }

                        if (isset($used_keys[$key])) {
                                $key .= '-' . $post_id;
                        }
                        $used_keys[$key] = true;

                        $program_title = get_the_title($post_id);
                        $prism_title = chroma_get_translated_meta($post_id, 'program_prism_title', true) ?: $program_title;
                        $prism_description = chroma_get_translated_meta($post_id, 'program_prism_description', true);
                        $program_excerpt = chroma_home_program_summary($post_id);
                        $color_scheme = get_post_meta($post_id, 'program_color_scheme', true) ?: 'blue';

                        $prism_values = function_exists('chroma_program_prism_chart_values')
                                ? chroma_program_prism_chart_values($post_id)
                                : array(50, 50, 50, 50, 50);

                        $profiles[] = $sanitize_profile(array(
                                'key' => $key,
                                'label' => $program_title,
                                'title' => $prism_title,
                                'description' => $prism_description ?: $program_excerpt,
                                'color' => $color_map[$color_scheme] ?? '#4A6C7C',
                                'data' => $prism_values,
                        ));
                }
        }

        if (empty($profiles)) {
                $profiles = chroma_home_get_theme_mod_json('chroma_home_curriculum_profiles_json', $defaults['profiles']);
                $profiles = array_map($sanitize_profile, $profiles);
        }

        $result = array(
                'labels' => $defaults['labels'],
                'profiles' => $profiles,
        );

        wp_cache_set($cache_key, $result, 'chroma', DAY_IN_SECONDS);

        return $result;
}

/**
 * Daily schedule tracks - Pull from Program CPT
 */
function chroma_home_schedule_tracks()
{
        $token = chroma_get_last_changed('programs');
        $cache_key = 'home_schedule_tracks:v4:' . $token;
        $cached = wp_cache_get($cache_key, 'chroma');

        if (false !== $cached) {
                return $cached;
        }

        $programs = new WP_Query(array(
                'post_type' => 'program',
                'posts_per_page' => 50,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'post_status' => 'publish',
                'no_found_rows' => true,
                'update_post_meta_cache' => true,
                'meta_query' => array(
                        array(
                                'key' => 'program_schedule_items',
                                'compare' => '!=',
                                'value' => '',
                        ),
                ),
        ));

        if (!$programs->have_posts()) {
                $tracks = chroma_home_get_theme_mod_json('chroma_home_schedule_tracks_json', chroma_home_default_schedule_tracks());
                $processed = array_map(function ($track) {
                        $steps = array_map(function ($step) {
                                return array(
                                        'time' => sanitize_text_field($step['time'] ?? ''),
                                        'title' => sanitize_text_field($step['title'] ?? ''),
                                        'copy' => sanitize_textarea_field($step['copy'] ?? ''),
                                );
                        }, $track['steps'] ?? array());

                        return array(
                                'key' => sanitize_title($track['key'] ?? ''),
                                'label' => sanitize_text_field($track['label'] ?? ''),
                                'title' => sanitize_text_field($track['title'] ?? ''),
                                'description' => sanitize_textarea_field($track['description'] ?? ''),
                                'color' => sanitize_text_field($track['color'] ?? ''),
                                'background' => sanitize_text_field($track['background'] ?? ''),
                                'image' => esc_url_raw($track['image'] ?? ''),
                                'steps' => $steps,
                        );
                }, $tracks);

                wp_cache_set($cache_key, $processed, 'chroma', DAY_IN_SECONDS);
                return $processed;
        }

        $tracks = array();
        $used_keys = array();
        while ($programs->have_posts()) {
                $programs->the_post();
                $post_id = get_the_ID();
                $anchor_slug = chroma_home_program_public_key($post_id);
                $key = $anchor_slug;
                if (isset($used_keys[$key]))
                        $key .= '-' . $post_id;
                $used_keys[$key] = true;

                $schedule_title = chroma_get_translated_meta($post_id, 'program_schedule_title', true);
                $schedule_items = chroma_get_translated_meta($post_id, 'program_schedule_items', true);
                $color_scheme = get_post_meta($post_id, 'program_color_scheme', true) ?: 'blue';
                $program_title = get_the_title();
                $description = chroma_home_program_summary($post_id);

                $steps = array();
                if (!empty($schedule_items)) {
                        $lines = explode("\n", $schedule_items);
                        foreach ($lines as $line) {
                                if ($parts = explode('|', trim($line))) {
                                        if (count($parts) >= 3) {
                                                $steps[] = array(
                                                        'time' => sanitize_text_field(trim($parts[0])),
                                                        'title' => sanitize_text_field(trim($parts[1])),
                                                        'copy' => sanitize_textarea_field(trim($parts[2])),
                                                );
                                        }
                                }
                        }
                }

                if (empty($steps))
                        continue;

                $image_url = get_the_post_thumbnail_url($post_id, 'large');
                $color_map = array(
                        'red' => array('color' => 'chroma-red', 'background' => 'bg-chroma-redLight'),
                        'blue' => array('color' => 'chroma-blue', 'background' => 'bg-chroma-blueLight'),
                        'yellow' => array('color' => 'chroma-yellow', 'background' => 'bg-chroma-yellowLight'),
                        'blueDark' => array('color' => 'chroma-blueDark', 'background' => 'bg-chroma-blueDark/10'),
                        'green' => array('color' => 'chroma-green', 'background' => 'bg-chroma-greenLight'),
                        'orange' => array('color' => 'chroma-orange', 'background' => 'bg-chroma-orange/10'),
                );
                $colors = $color_map[$color_scheme] ?? $color_map['blue'];

                $tracks[] = array(
                        'key' => $key,
                        'label' => $program_title,
                        'title' => $schedule_title ?: $program_title,
                        'description' => $description,
                        'color' => $colors['color'],
                        'background' => $colors['background'],
                        'image' => $image_url ?: '',
                        'steps' => $steps,
                );
        }
        wp_reset_postdata();

        wp_cache_set($cache_key, $tracks, 'chroma', DAY_IN_SECONDS);
        return $tracks;
}

function chroma_home_default_schedule_content()
{
        return array(
                'eyebrow' => __('Day by Day', 'chroma-excellence'),
                'heading' => __('A Daily Rhythm of Joy', 'chroma-excellence'),
                'subheading' => __('We don\'t just fill time. Every classroom follows a thoughtful flow designed to balance stimulation, nourishment, and rest.', 'chroma-excellence'),
        );
}

function chroma_home_schedule_content()
{
        $defaults = chroma_home_default_schedule_content();

        return array(
                'eyebrow' => sanitize_text_field(chroma_get_theme_mod('chroma_home_schedule_eyebrow', $defaults['eyebrow'])),
                'heading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_schedule_heading', $defaults['heading'])),
                'subheading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_schedule_subheading', $defaults['subheading'])),
        );
}

/**
 * Home FAQ block
 */
/**
 * Home FAQ block
 */
function chroma_home_normalize_operational_faq_items($items)
{
		$normalized_items = array();
		foreach ((array) $items as $item) {
				if (!is_array($item)) {
						continue;
				}

				$question = sanitize_text_field(is_scalar($item['question'] ?? null) ? (string) $item['question'] : '');
				$answer = sanitize_textarea_field(chroma_home_normalize_location_count_copy(is_scalar($item['answer'] ?? null) ? (string) $item['answer'] : ''));

				if (preg_match('/(?:tuition|childcare\s+cost|how\s+much|pricing|rates?)/i', $question)) {
						continue;
				}

				if (preg_match('/meals?|snacks?/i', $question)) {
						$question = __('Are meals and snacks provided?', 'chroma-excellence');
						$answer = __('Yes. Breakfast, lunch, and afternoon snacks are prepared fresh daily for age groups eating solid foods.', 'chroma-excellence');
				}

				if (preg_match('/(?:GA\s+(?:Lottery\s+)?Pre-?K|Georgia\s+Pre-?K)/i', $question)) {
						$question = __('Do you offer Georgia Pre-K?', 'chroma-excellence');
						$answer = __('Georgia Pre-K is available at most Chroma campuses. Contact your preferred campus directly to confirm current availability, eligibility, and enrollment details.', 'chroma-excellence');
				}

				if (preg_match('/communicate\s+with\s+parents/i', $question) && preg_match('/(?:LineLeader|Procare|Brightwheel)/i', $answer)) {
						$answer = __('Campuses share daily updates, photos, and messages through the family communication tools used by that campus. Your campus team will provide access details.', 'chroma-excellence');
				}

				if (preg_match('/(?:licensed|accredit)/i', $question) && preg_match('/(?:NAEYC|GAC\s+Accredited)/i', $answer)) {
						$answer = __('Chroma campuses operate under applicable Georgia DECAL licensing requirements. Contact your preferred campus directly for current program credentials and participation details.', 'chroma-excellence');
				}

				$normalized_items[] = array(
						'question' => $question,
						'answer' => $answer,
				);
		}

		return $normalized_items;
}

function chroma_home_faq_items()
{
		$post_id = chroma_get_home_page_id();
		$items_json = chroma_get_translated_meta($post_id, 'home_faq_items_json', true);

		$items = array();
		if (is_array($items_json)) {
				$items = $items_json;
		} elseif (is_string($items_json) && $items_json !== '') {
				$decoded = json_decode($items_json, true);
				if (JSON_ERROR_NONE === json_last_error() && is_array($decoded)) {
						$items = $decoded;
				}
		}

		if (empty($items)) {
				$items = chroma_home_get_theme_mod_json('chroma_home_faq_items_json', chroma_home_default_faq_items());
		}

		return chroma_home_normalize_operational_faq_items($items);
}

/**
 * Repair legacy homepage FAQ and FAQ schema claims saved before the V2 policy
 * update. This changes only the front-page FAQ sources and preserves any
 * unrelated schema entries.
 */
function chroma_repair_home_operational_claims()
{
		if ((int) get_option('chroma_home_operational_claims_version', 0) >= 1) {
				return;
		}

		$home_id = chroma_get_home_page_id();
		if ($home_id < 1) {
				return;
		}

		$faq_meta_keys = array('_chroma_es_home_faq_items_json', 'chroma_faq_items');
		$normalized_faqs = array();
		foreach ($faq_meta_keys as $meta_key) {
				$value = get_post_meta($home_id, $meta_key, true);
				if (is_array($value) && !empty($value)) {
						$value = chroma_home_normalize_operational_faq_items($value);
						update_post_meta($home_id, $meta_key, $value);
						if (empty($normalized_faqs)) {
								$normalized_faqs = $value;
						}
				}
		}

		$theme_mod_faqs = get_theme_mod('chroma_home_faq_items_json', array());
		if (is_array($theme_mod_faqs) && !empty($theme_mod_faqs)) {
				$theme_mod_faqs = chroma_home_normalize_operational_faq_items($theme_mod_faqs);
				set_theme_mod('chroma_home_faq_items_json', $theme_mod_faqs);
				if (empty($normalized_faqs)) {
						$normalized_faqs = $theme_mod_faqs;
				}
		}

		if (empty($normalized_faqs)) {
				$normalized_faqs = chroma_home_normalize_operational_faq_items(chroma_home_default_faq_items());
		}

		$faq_schema = array(
				'@context' => 'https://schema.org',
				'@type' => 'FAQPage',
				'mainEntity' => array_map(
						static function ($item) {
								return array(
										'@type' => 'Question',
										'name' => $item['question'],
										'acceptedAnswer' => array(
												'@type' => 'Answer',
												'text' => $item['answer'],
										),
								);
						},
						$normalized_faqs
				),
		);

		$schemas = get_post_meta($home_id, '_chroma_post_schemas', true);
		$schemas = is_array($schemas) ? $schemas : array();
		$replaced = false;
		foreach ($schemas as $index => $schema) {
				if (is_array($schema) && 'FAQPage' === ($schema['@type'] ?? '')) {
						$schemas[$index] = $faq_schema;
						$replaced = true;
				}
		}
		if (!$replaced) {
				$schemas[] = $faq_schema;
		}
		update_post_meta($home_id, '_chroma_post_schemas', $schemas);
		update_post_meta($home_id, '_chroma_schema_override', wp_json_encode($faq_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

		update_option('chroma_home_operational_claims_version', 1, false);
}
add_action('init', 'chroma_repair_home_operational_claims', 30);

function chroma_home_faq()
{
        $defaults = chroma_home_default_faq();
        $post_id = chroma_get_home_page_id();
        $heading = chroma_get_translated_meta($post_id, 'home_faq_heading', true);
        $subheading = chroma_get_translated_meta($post_id, 'home_faq_subheading', true);
        $heading = is_scalar($heading) ? (string) $heading : '';
        $subheading = is_scalar($subheading) ? (string) $subheading : '';

        return array(
                'heading' => sanitize_text_field($heading ?: chroma_get_theme_mod('chroma_home_faq_heading', $defaults['heading'])),
                'subheading' => sanitize_text_field($subheading ?: chroma_get_theme_mod('chroma_home_faq_subheading', $defaults['subheading'])),
                'items' => chroma_home_faq_items(),
                'cta_text' => '',
                'cta_label' => '',
                'cta_link' => '',
        );
}

function chroma_format_location_count_text($text, $location_count)
{
        $text = (string) $text;
        $location_count = max(0, (int) $location_count);

        if ($text === '' || $location_count === 0) {
                return $text;
        }

        if (preg_match('/^\s*\d+\+?\s+/u', $text)) {
                return (string) preg_replace('/^\s*\d+\+?/u', (string) $location_count, $text, 1);
        }

        return sprintf(__('%d neighborhood locations across Metro Atlanta', 'chroma-excellence'), $location_count);
}

function chroma_home_locations_preview()
{
        $token = chroma_get_last_changed('locations');
        $cache_key = 'home_locations_preview:v2:' . $token;
        $cached = wp_cache_get($cache_key, 'chroma');

        if (false !== $cached) {
                return $cached;
        }

        $post_id = chroma_get_home_page_id();
        $subheading = sanitize_text_field(chroma_get_translated_meta($post_id, 'home_locations_subheading', true) ?: chroma_get_theme_mod('chroma_home_locations_subheading', 'Find a Chroma campus near your home or work.'));
        $cta_label = sanitize_text_field(chroma_get_translated_meta($post_id, 'home_locations_cta_label', true) ?: chroma_get_theme_mod('chroma_home_locations_cta_label', 'View All Locations'));
        $cta_link = chroma_get_localized_url(esc_url_raw(chroma_get_theme_mod('chroma_home_locations_cta_link', '/locations/')));

        $locations = get_posts(array(
                'post_type' => 'location',
                'post_status' => 'publish',
                'posts_per_page' => 100, // Reasonable cap
                'orderby' => 'title',
                'order' => 'ASC',
                'update_post_meta_cache' => true,
                'no_found_rows' => true,
        ));
        $location_count = count($locations);
        $heading_template = sanitize_text_field(chroma_get_translated_meta($post_id, 'home_locations_heading', true) ?: chroma_get_theme_mod('chroma_home_locations_heading', 'Neighborhood locations across Metro Atlanta'));
        $heading = chroma_format_location_count_text($heading_template, $location_count);

        $map_points = array();
        $featured = array();
        $grouped = array();
        $taxonomy = 'location_region';
        $fallback = (object) array('name' => __('Other Areas', 'chroma-excellence'), 'slug' => 'other-areas');

        foreach ($locations as $location) {
                $loc_id = $location->ID;
                $title = get_the_title($loc_id);
                $permalink = get_permalink($loc_id);
                $fields = chroma_get_location_fields($loc_id);

                $terms = get_the_terms($loc_id, $taxonomy) ?: array($fallback);
                $term_slugs = array();
                $term_labels = array();
                foreach ($terms as $term) {
                        $term_slugs[] = sanitize_title($term->slug ?: $term->name);
                        $term_labels[] = sanitize_text_field($term->name);
                }

                $location_data = array(
                        'id' => $loc_id,
                        'title' => $title,
                        'city' => $fields['city'],
                        'state' => $fields['state'],
                        'address' => $fields['address'],
                        'phone' => $fields['phone'],
                        'email' => sanitize_email($fields['email']),
                        'url' => $permalink,
                        'image' => get_the_post_thumbnail_url($loc_id, 'medium_large') ?: '',
                        'region_slugs' => $term_slugs,
                        'region_labels' => $term_labels,
                );

                if ($fields['latitude'] && $fields['longitude']) {
                        $map_points[] = array(
                                'id' => $loc_id,
                                'name' => $title,
                                'lat' => (float) $fields['latitude'],
                                'lng' => (float) $fields['longitude'],
                                'url' => $permalink,
                                'city' => $fields['city'],
                                'state' => $fields['state'],
                                'address' => $fields['address'],
                                'phone' => $fields['phone'],
                                'email' => sanitize_email($fields['email']),
                                'image' => get_the_post_thumbnail_url($loc_id, 'medium_large') ?: '',
                                'region_slugs' => $term_slugs,
                                'region_labels' => $term_labels,
                        );
                }

                $featured[] = $location_data;
                foreach ($terms as $term) {
                        $group_key = sanitize_title($term->slug ?: $term->name);
                        if (!isset($grouped[$group_key])) {
                                $grouped[$group_key] = array('label' => $term->name, 'slug' => $term->slug ?: $group_key, 'term_id' => $term->term_id ?? 0, 'locations' => array());
                        }
                        $grouped[$group_key]['locations'][] = $location_data;
                }
        }

        // Sort and finalize
        foreach ($grouped as &$group) {
                usort($group['locations'], function ($a, $b) {
                        return strnatcasecmp($a['title'], $b['title']); });
        }
        uasort($grouped, function ($a, $b) {
                return strnatcasecmp($a['label'], $b['label']); });

        $result = array(
                'heading' => $heading,
                'subheading' => $subheading,
                'cta_label' => $cta_label,
                'cta_link' => $cta_link,
                'map_points' => $map_points,
                'featured' => $featured,
                'grouped' => $grouped,
                'taxonomy_key' => $taxonomy,
        );

        wp_cache_set($cache_key, $result, 'chroma', DAY_IN_SECONDS);
        return $result;
}

/**
 * Tour CTA content
 */
function chroma_home_default_tour_cta()
{
        return array(
                'heading' => __('Schedule a private tour', 'chroma-excellence'),
                'subheading' => __('Share a few details and your preferred campus. A Chroma Director will reach out to confirm tour times.', 'chroma-excellence'),
                'benefits_heading' => __('Why families choose Chroma', 'chroma-excellence'),
                'benefit_items' => array(
                        __('Warm, consistent teachers', 'chroma-excellence'),
                        __('Daily parent communication', 'chroma-excellence'),
                        __('Healthy meals included', 'chroma-excellence'),
                        __('Age-appropriate security', 'chroma-excellence'),
						__('Ask about Georgia Pre-K availability', 'chroma-excellence'),
                ),
                'time_label' => __('Tour: 20–30 min', 'chroma-excellence'),
                'trust_text' => __('No obligation. We’ll never share your information.', 'chroma-excellence'),
                'plugin_missing_message' => __('Please activate the "Chroma Tour Form" plugin.', 'chroma-excellence'),
        );
}

function chroma_home_tour_cta()
{
        $defaults = chroma_home_default_tour_cta();
        $items = chroma_home_get_theme_mod_json('chroma_home_tour_benefits_json', $defaults['benefit_items']);
        $benefit_items = array_values(
                array_filter(
                        array_map(
                                function ($item) {
                                        return sanitize_text_field(is_array($item) ? ($item['text'] ?? '') : $item);
                                },
                                $items
                        )
                )
        );

        return array(
                'heading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_tour_heading', $defaults['heading'])),
                'subheading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_tour_subheading', $defaults['subheading'])),
                'benefits_heading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_tour_benefits_heading', $defaults['benefits_heading'])),
                'benefit_items' => !empty($benefit_items) ? $benefit_items : $defaults['benefit_items'],
                'time_label' => sanitize_text_field(chroma_get_theme_mod('chroma_home_tour_time_label', $defaults['time_label'])),
                'trust_text' => sanitize_text_field(chroma_get_theme_mod('chroma_home_tour_trust_text', $defaults['trust_text'])),
                'plugin_missing_message' => sanitize_text_field(chroma_get_theme_mod('chroma_home_tour_plugin_missing_message', $defaults['plugin_missing_message'])),
        );
}

/**
 * Home Featured Locations (static)
 */
function chroma_home_featured_locations()
{
        $locations = chroma_home_locations_preview();
        return $locations['featured'];
}

/**
 * Home Featured Stories (static placeholders)
 */
function chroma_home_featured_stories()
{
        return array(
                array(
                        'title' => __('Inside the Prismpath™ Classroom', 'chroma-excellence'),
                        'excerpt' => __('Take a peek at how our educators weave play and academics together each day.', 'chroma-excellence'),
                        'url' => '/stories/prismpath-classroom/',
                ),
                array(
                        'title' => __('Family-Style Dining at Chroma', 'chroma-excellence'),
                        'excerpt' => __('Why shared meals matter for social-emotional growth and independence.', 'chroma-excellence'),
                        'url' => '/stories/family-style-dining',
                ),
                array(
                        'title' => __('Partnering with Parents', 'chroma-excellence'),
                        'excerpt' => __('See how we communicate daily to keep families connected to the classroom.', 'chroma-excellence'),
                        'url' => '/stories/partnering-with-parents',
                ),
        );
}

/**
 * Parent Reviews for homepage carousel
 */
function chroma_home_default_parent_reviews()
{
        return array(
                array(
                        'name' => 'Sarah M.',
                        'location' => __('Marietta Campus', 'chroma-excellence'),
                        'rating' => '5',
                        'review' => __('Our daughter has flourished at Chroma. The teachers genuinely care, and the Prismpath curriculum has her excited to learn every day. We couldn\'t ask for a better early learning experience.', 'chroma-excellence'),
                ),
                array(
                        'name' => 'James & Lisa T.',
                        'location' => __('Johns Creek Campus', 'chroma-excellence'),
                        'rating' => '5',
                        'review' => __('After touring several centers, Chroma stood out immediately. The transparency, the warmth, and the expert care made our decision easy. Our son has been there for two years and we\'ve never looked back.', 'chroma-excellence'),
                ),
                array(
                        'name' => 'Maria G.',
                        'location' => __('Austell Campus', 'chroma-excellence'),
                        'rating' => '5',
                        'review' => __('The family-style meals, the daily communication, the beautiful facilities — everything exceeds expectations. Chroma feels like an extension of our family, and our twins are thriving.', 'chroma-excellence'),
                ),
        );
}

function chroma_home_default_parent_reviews_content()
{
        return array(
                'eyebrow' => __('What Parents Say', 'chroma-excellence'),
                'heading' => __('Trusted by thousands of Atlanta families', 'chroma-excellence'),
                'subheading' => __('Don\'t just take our word for it. Here\'s what parents have to say about their experience with Chroma Early Learning.', 'chroma-excellence'),
                'dot_aria_label_format' => __('Go to review %d', 'chroma-excellence'),
                'prev_label' => __('Previous review', 'chroma-excellence'),
                'next_label' => __('Next review', 'chroma-excellence'),
        );
}

function chroma_home_parent_reviews_section()
{
        $defaults = chroma_home_default_parent_reviews_content();

        return array(
                'eyebrow' => sanitize_text_field(chroma_get_theme_mod('chroma_home_reviews_eyebrow', $defaults['eyebrow'])),
                'heading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_reviews_heading', $defaults['heading'])),
                'subheading' => sanitize_text_field(chroma_get_theme_mod('chroma_home_reviews_subheading', $defaults['subheading'])),
                'dot_aria_label_format' => sanitize_text_field(chroma_get_theme_mod('chroma_home_reviews_dot_aria_label_format', $defaults['dot_aria_label_format'])),
                'prev_label' => sanitize_text_field(chroma_get_theme_mod('chroma_home_reviews_prev_label', $defaults['prev_label'])),
                'next_label' => sanitize_text_field(chroma_get_theme_mod('chroma_home_reviews_next_label', $defaults['next_label'])),
        );
}

function chroma_home_parent_reviews()
{
        $reviews = chroma_home_get_theme_mod_json('chroma_home_parent_reviews_json', chroma_home_default_parent_reviews());

        return array_map(
                function ($review) {
                        return array(
                                'name' => sanitize_text_field($review['name'] ?? ''),
                                'location' => sanitize_text_field($review['location'] ?? ''),
                                'rating' => absint($review['rating'] ?? 5),
                                'review' => sanitize_textarea_field($review['review'] ?? ''),
                        );
                },
                $reviews
        );
}

/**
 * Checkers for optional sections
 */
function chroma_home_has_prismpath_panels()
{
        return true;
}

function chroma_home_has_program_wizard()
{
        return true;
}

function chroma_home_has_curriculum_profiles()
{
        return true;
}

function chroma_home_has_schedule_tracks()
{
        return true;
}

function chroma_home_has_faq()
{
        return true;
}

function chroma_home_has_stats()
{
        return true;
}

function chroma_home_has_parent_reviews()
{
        $reviews = chroma_home_parent_reviews();
        return !empty($reviews);
}

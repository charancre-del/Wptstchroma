<?php
/**
 * Template Helper Functions
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trimmed Excerpt
 */
function chroma_trimmed_excerpt($length = 20, $post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    $excerpt = has_excerpt($post_id) ? get_the_excerpt($post_id) : get_the_content(null, false, $post_id);
    $excerpt = wp_strip_all_tags($excerpt);
    $words = explode(' ', $excerpt);

    if (count($words) > $length) {
        $excerpt = implode(' ', array_slice($words, 0, $length)) . '...';
    }

    return $excerpt;
}

/**
 * Safe meta accessor
 */
function chroma_get_meta_value($post_id, $key, $default = '')
{
    // Use translation helper if available, otherwise fall back to get_post_meta
    if (function_exists('chroma_get_translated_meta')) {
        $value = chroma_get_translated_meta($post_id, $key);
    } else {
        $value = get_post_meta($post_id, $key, true);
    }

    if ('' === $value || null === $value) {
        return $default;
    }

    return $value;
}

/**
 * Location meta bundle
 */
function chroma_get_location_fields($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();

    return array(
        'address' => chroma_get_meta_value($post_id, 'location_address', ''),
        'city' => chroma_get_meta_value($post_id, 'location_city', ''),
        'state' => chroma_get_meta_value($post_id, 'location_state', 'GA'),
        'zip' => chroma_get_meta_value($post_id, 'location_zip', ''),
        'phone' => chroma_get_meta_value($post_id, 'location_phone', ''),
        'email' => chroma_get_meta_value($post_id, 'location_email', ''),
        'latitude' => chroma_get_meta_value($post_id, 'location_latitude', ''),
        'longitude' => chroma_get_meta_value($post_id, 'location_longitude', ''),
        'license_number' => chroma_get_meta_value($post_id, '_chroma_license_number', ''),
    );
}

/**
 * Program meta bundle
 */
function chroma_get_program_fields($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();

    // Get manual icon override
    $icon = chroma_get_meta_value($post_id, 'program_icon', '');

    // Smart Defaults if no manual icon set
    if (empty($icon)) {
        $slug = get_post_field('post_name', $post_id);

        // Map slugs to emojis
        if (strpos($slug, 'infant') !== false) {
            $icon = '👶';
        } elseif (strpos($slug, 'toddler') !== false) {
            $icon = '🚀';
        } elseif (strpos($slug, 'preschool') !== false) {
            $icon = '🎨';
        } elseif (strpos($slug, 'pre-k') !== false || strpos($slug, 'prek') !== false) {
            $icon = '🖍️'; // Pre-K Prep
            if (strpos($slug, 'ga') !== false) {
                $icon = '🎓'; // GA Pre-K
            }
        } elseif (strpos($slug, 'school') !== false) {
            $icon = '🚌'; // After School / Schoolagers
        } elseif (strpos($slug, 'camp') !== false) {
            $icon = '☀️';
        } elseif (strpos($slug, 'parent') !== false) {
            $icon = '🎉';
        } else {
            $icon = 'fas fa-child'; // Fallback
        }
    }

    return array(
        'age_range' => chroma_get_meta_value($post_id, 'program_age_range', ''),
        'excerpt' => chroma_get_meta_value($post_id, 'program_short_description', ''),
        'icon' => $icon,
        'color' => chroma_get_meta_value($post_id, 'program_color', 'chroma-teal'),
    );
}

/**
 * Program anchor slug helper
 */
function chroma_get_program_anchor_slug($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    $anchor = chroma_get_meta_value($post_id, 'program_anchor_slug', '');

    if (!$anchor) {
        $anchor = get_post_field('post_name', $post_id);
    }

    return sanitize_title($anchor);
}

/**
 * Program SEO intro fields
 */
function chroma_get_program_seo_fields($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();

    $highlights = chroma_get_meta_value($post_id, 'program_seo_highlights', '');
    $highlights = preg_split('/\r\n|\r|\n/', $highlights);
    $highlights = array_filter(array_map('trim', (array) $highlights));

    return array(
        'heading' => chroma_get_meta_value($post_id, 'program_seo_heading', ''),
        'summary' => chroma_get_meta_value($post_id, 'program_seo_summary', ''),
        'highlights' => $highlights,
    );
}

/**
 * Program SEO meta tags
 */
function chroma_get_program_meta_tags($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    $meta_desc = chroma_get_meta_value($post_id, 'program_meta_description', '');

    if (!$meta_desc) {
        $meta_desc = has_excerpt($post_id) ? get_the_excerpt($post_id) : chroma_trimmed_excerpt(32, $post_id);
    }

    return array(
        'title' => chroma_get_meta_value($post_id, 'program_meta_title', get_the_title($post_id)),
        'description' => $meta_desc,
    );
}

/**
 * Program FAQ items as structured array
 */
function chroma_get_program_faq_items($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    $raw = chroma_get_meta_value($post_id, 'program_faq_items', '');

    if (!$raw) {
        return array();
    }

    $rows = preg_split('/\r\n|\r|\n/', $raw);
    $rows = array_filter(array_map('trim', (array) $rows));
    $faq = array();

    foreach ($rows as $row) {
        $parts = array_map('trim', explode('|', $row, 2));

        if (count($parts) < 2 || !$parts[0] || !$parts[1]) {
            continue;
        }

        $faq[] = array(
            'question' => wp_strip_all_tags($parts[0]),
            'answer' => wp_kses_post($parts[1]),
        );
    }

    return $faq;
}

/**
 * Location FAQ items as structured array with global defaults
 */
function chroma_get_location_faq_items($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    $faq = array();

    // 1. Global Defaults
    $defaults = array(
        array(
            'question' => __('Do you offer tours?', 'chroma-excellence'),
            'answer' => __('Yes! We encourage all families to book a tour to see our classrooms, meet our directors, and experience the Chroma difference firsthand.', 'chroma-excellence'),
        ),
        array(
            'question' => __('What ages do you serve?', 'chroma-excellence'),
            'answer' => __('We typically serve children from 6 weeks (Infants) up to 12 years old (After School), though specific programs may vary by campus.', 'chroma-excellence'),
        ),
        array(
            'question' => __('Is food included?', 'chroma-excellence'),
            'answer' => __('Yes, we provide nutritious, child-friendly meals and snacks prepared fresh daily.', 'chroma-excellence'),
        ),
        array(
            'question' => __('Do you offer part-time daycare or Parents Day Out?', 'chroma-excellence'),
            'answer' => __('Yes. Chroma Early Learning Academy locations offer part-time care and Parents Day Out programs. Availability and schedules vary by location, and programs are designed to support families who need flexible care options.', 'chroma-excellence'),
        ),
        array(
            'question' => __('What safety and security measures are in place?', 'chroma-excellence'),
            'answer' => __('All Chroma Locations have Keypad Controlled Access, 24/7 monitored Cameras, Carbon monoxide and Smoke Alarm Systems, Defibrillators, and Emergency Plans that are performed regularly.', 'chroma-excellence'),
        ),
        array(
            'question' => __('Do you accept CAPS?', 'chroma-excellence'),
            'answer' => __('Yes, all Chroma Early Learning Academy Locations accept CAPS (Childcare and Parent Services).', 'chroma-excellence'),
        ),
    );

    foreach ($defaults as $default) {
        $faq[] = array(
            'question' => wp_strip_all_tags($default['question']),
            'answer' => wp_kses_post($default['answer']),
        );
    }

    // 2. Location-Specific Meta FAQs
    $raw = chroma_get_meta_value($post_id, 'location_faq_items', '');
    if ($raw) {
        $rows = preg_split('/\r\n|\r|\n/', $raw);
        $rows = array_filter(array_map('trim', (array) $rows));

        foreach ($rows as $row) {
            $parts = array_map('trim', explode('|', $row, 2));

            if (count($parts) < 2 || !$parts[0] || !$parts[1]) {
                continue;
            }

            $faq[] = array(
                'question' => wp_strip_all_tags($parts[0]),
                'answer' => wp_kses_post($parts[1]),
            );
        }
    }

    return $faq;
}

/**
 * Render FAQ schema JSON-LD
 */
function chroma_render_program_faq_schema($faq_items)
{
    // DISABLED: Schema now handled by Chroma SEO Pro plugin
    return;
}

/**
 * Cached lookup of program anchors keyed by slug and title
 */
function chroma_get_program_anchor_lookup()
{
    static $lookup;

    if (null !== $lookup) {
        return $lookup;
    }

    $lookup = array();

    $programs = get_posts(
        array(
            'post_type' => 'program',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'menu_order title',
            'order' => 'ASC',
        )
    );

    foreach ($programs as $program_id) {
        $anchor = chroma_get_program_anchor_slug($program_id);
        $slug = get_post_field('post_name', $program_id);
        $title_anchor = sanitize_title(get_the_title($program_id));

        $lookup[$anchor] = $anchor;
        $lookup[$slug] = $anchor;
        $lookup[$title_anchor] = $anchor;
    }

    return $lookup;
}

/**
 * Resolve an anchor slug for a given program key
 */
function chroma_program_anchor_for_key($key)
{
    $lookup = chroma_get_program_anchor_lookup();
    $key = sanitize_title($key);

    return $lookup[$key] ?? $key;
}

/**
 * Program color class mapping
 */
function chroma_program_color_classes($color_key)
{
    $map = array(
        'chroma-teal' => array(
            'gradient_from' => 'from-chroma-teal/10',
            'gradient_to' => 'to-chroma-teal/5',
            'text' => 'text-chroma-teal',
            'button' => 'bg-chroma-teal',
        ),
        'chroma-red' => array(
            'gradient_from' => 'from-chroma-red/10',
            'gradient_to' => 'to-chroma-red/5',
            'text' => 'text-chroma-red',
            'button' => 'bg-chroma-red',
        ),
        'chroma-yellow' => array(
            'gradient_from' => 'from-chroma-yellow/10',
            'gradient_to' => 'to-chroma-yellow/5',
            'text' => 'text-chroma-yellow',
            'button' => 'bg-chroma-yellow',
        ),
        'chroma-blue' => array(
            'gradient_from' => 'from-chroma-blue/10',
            'gradient_to' => 'to-chroma-blue/5',
            'text' => 'text-chroma-blue',
            'button' => 'bg-chroma-blue',
        ),
        'chroma-green' => array(
            'gradient_from' => 'from-chroma-green/10',
            'gradient_to' => 'to-chroma-green/5',
            'text' => 'text-chroma-green',
            'button' => 'bg-chroma-green',
        ),
    );

    return $map[$color_key] ?? $map['chroma-teal'];
}

/**
 * Eyebrow Badge
 */
function chroma_eyebrow($text, $color = 'blue')
{
    $color_class = 'text-chroma-' . $color;
    echo '<span class="' . esc_attr($color_class) . ' font-bold tracking-[0.2em] text-[11px] uppercase mb-3 block">' . esc_html($text) . '</span>';
}

/**
 * Archive Pagination
 */
function chroma_archive_pagination()
{
    the_posts_pagination(array(
        'mid_size' => 2,
        'prev_text' => __('← Previous', 'chroma-excellence'),
        'next_text' => __('Next →', 'chroma-excellence'),
        'class' => 'flex items-center justify-center gap-2 mt-12',
    ));
}

/**
 * Location Address Line
 */
function chroma_location_address_line($post_id = null)
{
    $fields = chroma_get_location_fields($post_id);
    $address = $fields['address'];

    return $address ?: '';
}

/**
 * Location City State
 */
function chroma_location_city_state($post_id = null)
{
    $fields = chroma_get_location_fields($post_id);
    $city = $fields['city'];
    $state = $fields['state'];

    if (!$city) {
        return '';
    }

    return $city . ', ' . $state;
}

/**
 * Badge Helper
 */
function chroma_badge($text, $color = 'blue')
{
    $bg_class = 'bg-chroma-' . $color . '/10';
    $text_class = 'text-chroma-' . $color;

    echo '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ' . esc_attr($bg_class . ' ' . $text_class) . '">';
    echo esc_html($text);
    echo '</span>';
}

/**
 * Sanitize and validate URL field
 * Supports both http(s) URLs and mailto links
 *
 * @param string $url The URL to sanitize
 * @return string Sanitized URL or empty string if invalid
 */
function chroma_sanitize_url_field($url)
{
    if (empty($url)) {
        return '';
    }

    $url = trim($url);

    // Check if it's a mailto link
    if (strpos($url, 'mailto:') === 0) {
        return sanitize_email(str_replace('mailto:', '', $url)) ? $url : '';
    }

    // Check if it's a tel link
    if (strpos($url, 'tel:') === 0) {
        return $url; // Tel links are generally safe
    }

    // Check if it's an anchor link (starting with #)
    if (strpos($url, '#') === 0) {
        return sanitize_text_field($url);
    }

    // For regular URLs, use esc_url_raw
    $sanitized = esc_url_raw($url, array('http', 'https'));

    // Validate that it's a proper URL
    if (filter_var($sanitized, FILTER_VALIDATE_URL)) {
        return $sanitized;
    }

    // If URL validation fails, return empty (don't save invalid URLs)
    return '';
}

/**
 * Check if location is currently open based on hours string
 *
 * @param string $hours_string e.g., "7am - 6pm"
 * @return boolean
 */
function chroma_is_location_open($hours_string)
{
    if (empty($hours_string)) {
        return false;
    }

    // Check for weekends (assume closed unless string says otherwise)
    $is_weekend = (date('N') >= 6);
    if ($is_weekend && stripos($hours_string, 'Sat') === false && stripos($hours_string, 'Sun') === false) {
        return false;
    }

    // Extract times
    // Look for patterns like "7am - 6pm", "7:00 AM - 6:00 PM"
    $parts = preg_split('/(-| to )/i', $hours_string);
    if (count($parts) !== 2) {
        return false;
    }

    $start_str = trim($parts[0]);
    $end_str = trim($parts[1]);

    // Clean up "Mon-Fri" etc from start string if present
    $start_str = preg_replace('/^[A-Za-z\-, ]+/', '', $start_str);

    $start_time = strtotime($start_str);
    $end_time = strtotime($end_str);
    $now = current_time('timestamp');

    if (!$start_time || !$end_time) {
        return false;
    }

    // Compare times (minutes from midnight)
    $current_minutes = (int) date('H', $now) * 60 + (int) date('i', $now);
    $start_minutes = (int) date('H', $start_time) * 60 + (int) date('i', $start_time);
    $end_minutes = (int) date('H', $end_time) * 60 + (int) date('i', $end_time);

    return ($current_minutes >= $start_minutes && $current_minutes < $end_minutes);
}

/**
 * Helper function to get region color from term meta
 */
function chroma_get_region_color_from_term($term_id)
{
    $color_bg = get_term_meta($term_id, 'region_color_bg', true);
    $color_text = get_term_meta($term_id, 'region_color_text', true);
    $color_border = get_term_meta($term_id, 'region_color_border', true);

    // Fallback to default green if no colors set
    return array(
        'bg' => $color_bg ?: 'chroma-greenLight',
        'text' => $color_text ?: 'chroma-green',
        'border' => $color_border ?: 'chroma-green',
    );
}

/**
 * Region Emoji Helper
 */
function chroma_region_emoji($label)
{
    $map = array(
        'Cobb County' => '🍑',
        'Gwinnett County' => '🌳',
        'North Metro' => '🏙️',
        'South Metro' => '⛰️',
    );

    return $map[$label] ?? '📍';
}

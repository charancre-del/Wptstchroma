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
 * Determine whether a campus currently offers Georgia Pre-K.
 *
 * Campus editors can explicitly override the operational default. Until an
 * override is saved, every campus is treated as available except Chadwick and
 * North Hall. This keeps the public site accurate today while allowing either
 * campus to be enabled later without a theme update.
 */
function chroma_location_has_ga_pre_k($post_id = null)
{
    $post_id = $post_id ?: get_the_ID();
    $override = get_post_meta($post_id, 'location_ga_pre_k_available', true);

    if ('yes' === $override) {
        return true;
    }

    if ('no' === $override) {
        return false;
    }

    $slug = sanitize_title((string) get_post_field('post_name', $post_id));
    $title = sanitize_title((string) get_the_title($post_id));
    $identity = $slug . ' ' . $title;
    $available = false === strpos($identity, 'chadwick') && false === strpos($identity, 'north-hall');

    return (bool) apply_filters('chroma_location_has_ga_pre_k', $available, $post_id, $override);
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
        $slug = (string) get_post_field('post_name', $post_id);
        $defaults = array(
            'infant-care' => array(
                array('question' => __('How do infant teachers follow my baby\'s routine?', 'chroma-excellence'), 'answer' => __('Teachers learn each baby\'s feeding, rest, comfort, and communication cues, then partner with families to keep care responsive and consistent.', 'chroma-excellence')),
                array('question' => __('What does learning look like for infants?', 'chroma-excellence'), 'answer' => __('Learning begins through secure relationships, songs, stories, sensory exploration, safe movement, repetition, and warm back-and-forth interaction.', 'chroma-excellence')),
                array('question' => __('How do you support separation and attachment?', 'chroma-excellence'), 'answer' => __('Teachers use predictable welcomes, calm transitions, and attentive caregiving to help babies build trust at their own pace.', 'chroma-excellence')),
            ),
            'toddler-care' => array(
                array('question' => __('How do toddlers build independence at Chroma?', 'chroma-excellence'), 'answer' => __('Teachers offer simple choices, child-sized routines, and hands-on tasks that help toddlers practice doing more for themselves with patient support.', 'chroma-excellence')),
                array('question' => __('How do teachers support big feelings?', 'chroma-excellence'), 'answer' => __('Teachers name feelings, model calming strategies, and provide warm boundaries while toddlers develop language and emotional regulation.', 'chroma-excellence')),
                array('question' => __('What does early peer play look like?', 'chroma-excellence'), 'answer' => __('Toddlers begin by playing alongside one another, then practice sharing space, communicating needs, and joining short group experiences.', 'chroma-excellence')),
            ),
            'preschool' => array(
                array('question' => __('How does Preschool prepare children for Pre-K?', 'chroma-excellence'), 'answer' => __('Children strengthen communication, self-regulation, cooperative play, early literacy, mathematical thinking, and confidence with classroom routines.', 'chroma-excellence')),
                array('question' => __('Is Preschool learning still play-based?', 'chroma-excellence'), 'answer' => __('Yes. Teachers use purposeful centers, stories, questions, projects, movement, and creative play to introduce increasingly connected ideas.', 'chroma-excellence')),
                array('question' => __('How do teachers support different developmental levels?', 'chroma-excellence'), 'answer' => __('Teachers observe how each child participates and adjust questions, materials, small groups, and next steps without rushing development.', 'chroma-excellence')),
            ),
            'pre-k-prep' => array(
                array('question' => __('What is the difference between Pre-K Prep and GA Pre-K?', 'chroma-excellence'), 'answer' => __('Pre-K Prep supports three- to four-year-olds as they build the language, independence, problem solving, and classroom habits that prepare them for a Pre-K year.', 'chroma-excellence')),
                array('question' => __('How does Pre-K Prep build kindergarten readiness?', 'chroma-excellence'), 'answer' => __('Teachers deepen early literacy, mathematical thinking, cooperative play, self-regulation, and multi-step learning through developmentally appropriate experiences.', 'chroma-excellence')),
                array('question' => __('How are families kept informed?', 'chroma-excellence'), 'answer' => __('Teachers share observations and classroom updates so families can understand current growth and the skills children are practicing next.', 'chroma-excellence')),
            ),
            'ga-pre-k' => array(
                array('question' => __('Who is eligible for Georgia Pre-K?', 'chroma-excellence'), 'answer' => __('Georgia\'s state-funded Pre-K serves eligible four-year-old children. Families should confirm state eligibility rules, application dates, and campus availability for the current school year.', 'chroma-excellence')),
                array('question' => __('How does the lottery and application process work?', 'chroma-excellence'), 'answer' => __('Application and lottery procedures follow Georgia Pre-K requirements and local campus capacity. A campus director can explain current deadlines and placement steps.', 'chroma-excellence')),
                array('question' => __('Is care available outside the Georgia Pre-K day?', 'chroma-excellence'), 'answer' => __('Before- and after-school wraparound care may be available. Schedules, fees, and space vary by campus, so families should request current details directly.', 'chroma-excellence')),
            ),
            'after-school' => array(
                array('question' => __('Which schools have transportation to Chroma?', 'chroma-excellence'), 'answer' => __('School pickup routes vary by campus and may change with demand. Contact your preferred campus for the current transportation list.', 'chroma-excellence')),
                array('question' => __('Is homework support included?', 'chroma-excellence'), 'answer' => __('School-age afternoons make room for homework support, connection, movement, creative activities, and time to decompress after the school day.', 'chroma-excellence')),
                array('question' => __('Is care available during school breaks?', 'chroma-excellence'), 'answer' => __('Seasonal and school-break options vary by campus and calendar. Families can ask their campus about current full-day care and camp availability.', 'chroma-excellence')),
            ),
            'camp-summer-winter-fall' => array(
                array('question' => __('What ages can attend Chroma camp?', 'chroma-excellence'), 'answer' => __('Camp generally serves school-age children, with exact age ranges and available weeks set by each participating campus.', 'chroma-excellence')),
                array('question' => __('What do children do during camp?', 'chroma-excellence'), 'answer' => __('Camp days may include weekly themes, active play, creative projects, STEM exploration, and field experiences appropriate to the group and campus schedule.', 'chroma-excellence')),
                array('question' => __('How do I find current camp dates?', 'chroma-excellence'), 'answer' => __('Select a participating campus to review its current calendar, availability, registration details, and tour options.', 'chroma-excellence')),
            ),
            'kindergarten-1' => array(
                array('question' => __('How much is Private Kindergarten and what is the class size?', 'chroma-excellence'), 'answer' => __('Tuition and class size vary by campus and enrollment period. Contact enrollment for current figures, scholarship information, and space availability.', 'chroma-excellence')),
                array('question' => __('How is Kindergarten progress shared with families?', 'chroma-excellence'), 'answer' => __('Teachers provide regular progress updates across literacy, mathematics, classroom independence, and whole-child development.', 'chroma-excellence')),
                array('question' => __('What academic areas are included?', 'chroma-excellence'), 'answer' => __('Children build reading, writing, mathematics, science, critical thinking, collaboration, and independent learning habits within a developmentally appropriate day.', 'chroma-excellence')),
            ),
            'rising-pre-k' => array(
                array('question' => __('Who is Rising Pre-K designed for?', 'chroma-excellence'), 'answer' => __('Rising Pre-K is a summer bridge for children preparing to enter a Pre-K classroom and its routines.', 'chroma-excellence')),
                array('question' => __('What skills does the program support?', 'chroma-excellence'), 'answer' => __('Children practice communication, self-help routines, listening, early literacy, friendship skills, and confidence in group learning.', 'chroma-excellence')),
                array('question' => __('How do I confirm dates and availability?', 'chroma-excellence'), 'answer' => __('Summer schedules and participating campuses vary. Contact a campus for current dates, hours, and enrollment availability.', 'chroma-excellence')),
            ),
            'rising-kindergarten' => array(
                array('question' => __('Who is Rising Kindergarten designed for?', 'chroma-excellence'), 'answer' => __('Rising Kindergarten supports Pre-K graduates who are preparing for the routines and expectations of kindergarten.', 'chroma-excellence')),
                array('question' => __('Is this a formal academic program?', 'chroma-excellence'), 'answer' => __('It is a joyful summer bridge. Teachers weave literacy, problem solving, independence, and classroom confidence into active, age-appropriate experiences.', 'chroma-excellence')),
                array('question' => __('How do I find a participating campus?', 'chroma-excellence'), 'answer' => __('Participation and schedules vary by campus. Contact enrollment to confirm current locations, dates, and availability.', 'chroma-excellence')),
            ),
            'parents-day-out' => array(
                array('question' => __('Is Parent\'s Day Out a part-time option?', 'chroma-excellence'), 'answer' => __('Yes. It offers a flexible introduction to classroom routines and group care, with schedules and availability varying by campus.', 'chroma-excellence')),
                array('question' => __('What ages can participate?', 'chroma-excellence'), 'answer' => __('The program is designed for young children, but exact age ranges and placement options vary by campus. A director can help determine the best fit.', 'chroma-excellence')),
                array('question' => __('How does the program support a first classroom transition?', 'chroma-excellence'), 'answer' => __('Predictable routines, warm teachers, play, and small-group connection help children build comfort with separation and early friendships.', 'chroma-excellence')),
            ),
        );

        return $defaults[$slug] ?? array(
            array(
                'question' => sprintf(__('How do I know whether %s is the right fit?', 'chroma-excellence'), get_the_title($post_id)),
                'answer' => __('A campus director can discuss your child\'s age, current development, daily routine, and available classroom options during a tour.', 'chroma-excellence'),
            ),
            array(
                'question' => __('How do teachers share progress with families?', 'chroma-excellence'),
                'answer' => __('Teachers share classroom observations and updates so families can understand what children are practicing and what support comes next.', 'chroma-excellence'),
            ),
            array(
                'question' => __('How do I confirm availability?', 'chroma-excellence'),
                'answer' => __('Program schedules and space vary by campus. Contact enrollment or your preferred campus for current details.', 'chroma-excellence'),
            ),
        );
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
            'answer' => __('CAPS (Childcare and Parent Services) is accepted at all Chroma campuses. Authorization, program eligibility, and space availability apply; please confirm details with your preferred campus.', 'chroma-excellence'),
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

            $question = wp_strip_all_tags($parts[0]);
            $answer = wp_kses_post($parts[1]);

            if (false !== stripos($question, 'CAPS')) {
                $answer = __('CAPS (Childcare and Parent Services) is accepted at all Chroma campuses. Authorization, program eligibility, and space availability apply; please confirm details with your preferred campus.', 'chroma-excellence');
            }

            $faq[] = array(
                'question' => $question,
                'answer' => $answer,
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

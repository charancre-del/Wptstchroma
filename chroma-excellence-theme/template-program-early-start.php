<?php
/**
 * Template Name: Early Learning Support Template
 * Template Post Type: program
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$early_start_preload_id = get_queried_object_id();
$early_start_preload_asset_base = trailingslashit(CHROMA_THEME_URI . '/assets/images/early-start');
$early_start_preload_hero = get_post_meta($early_start_preload_id, 'early_start_hero_image', true);
if (!$early_start_preload_hero) {
    $early_start_preload_hero = $early_start_preload_asset_base . 'hero-therapy.jpg';
}

add_action('wp_head', static function () use ($early_start_preload_hero) {
    if (!$early_start_preload_hero) {
        return;
    }

    echo '<link rel="preload" as="image" href="' . esc_url($early_start_preload_hero) . '" fetchpriority="high">' . "\n";
}, 2);

if (!function_exists('chroma_early_start_icon_svg')) {
    function chroma_early_start_icon_svg($icon)
    {
        $icons = [
            'arrow-right' => '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M4 10h9.2" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/><path d="M10.8 6.2L15 10l-4.2 3.8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
            'external-link' => '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M8 4h8v8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/><path d="M16 4l-7.5 7.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/><path d="M14 10.5V15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h4.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
            'check' => '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><circle cx="10" cy="10" r="7.25" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M6.7 10.1l2.1 2.2 4.5-4.7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
            'speech' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.5 7.25h11a2.25 2.25 0 0 1 2.25 2.25v5a2.25 2.25 0 0 1-2.25 2.25h-6l-3.75 3v-3H6.5a2.25 2.25 0 0 1-2.25-2.25v-5A2.25 2.25 0 0 1 6.5 7.25Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1.8"/><path d="M8.5 11.25h7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/><path d="M8.5 14.25h4.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/></svg>',
            'occupational' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 4.25l2.1 4.25 4.7.7-3.4 3.3.8 4.7L12 15l-4.2 2.2.8-4.7-3.4-3.3 4.7-.7L12 4.25Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1.8"/><circle cx="12" cy="11.1" r="1.8" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>',
            'aba' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.5 13.5c0-2.5 2-4.5 4.5-4.5s4.5 2 4.5 4.5v3.25a2 2 0 0 1-2 2h-5a2 2 0 0 1-2-2V13.5Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1.8"/><path d="M9.25 10.25V8.9a2.75 2.75 0 1 1 5.5 0v1.35" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/><path d="M6 12.25H4.75a1.5 1.5 0 0 0-1.5 1.5v1.5a1.5 1.5 0 0 0 1.5 1.5H6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/><path d="M18 12.25h1.25a1.5 1.5 0 0 1 1.5 1.5v1.5a1.5 1.5 0 0 1-1.5 1.5H18" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        ];

        return $icons[$icon] ?? '';
    }
}

get_header();

while (have_posts()):
    the_post();

    $page_id = get_the_ID();
    $asset_base = trailingslashit(CHROMA_THEME_URI . '/assets/images/early-start');
    $request_path = isset($_SERVER['REQUEST_URI'])
        ? trim((string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH), '/')
        : '';
    $is_early_learning_route = in_array($request_path, array('early-learning', 'chroma-early-learning'), true);

    $early_learning_nav_label = function_exists('chroma_get_theme_mod')
        ? chroma_get_theme_mod('chroma_early_learning_nav_label', __('Early Learning', 'chroma-excellence'))
        : __('Early Learning', 'chroma-excellence');
    $early_learning_brand_label = function_exists('chroma_get_theme_mod')
        ? chroma_get_theme_mod('chroma_early_learning_brand_label', __('Chroma Early Learning', 'chroma-excellence'))
        : __('Chroma Early Learning', 'chroma-excellence');
    $early_learning_support_label = sprintf(__('%s Support', 'chroma-excellence'), $early_learning_nav_label);
    $early_learning_support_program_label = sprintf(__('%s support program', 'chroma-excellence'), strtolower($early_learning_nav_label));

    $normalize_early_learning_text = static function ($value) use ($is_early_learning_route, $early_learning_nav_label, $early_learning_brand_label, $early_learning_support_label, $early_learning_support_program_label) {
        if (!$is_early_learning_route) {
            return (string) $value;
        }

        return str_replace(
            [
                'Chroma Early Start',
                'Early Start',
                'Specialized Pediatric Therapy',
                'Pediatric Therapy',
                'Occupational Therapy',
                'ABA Therapy',
                'therapeutic division',
                'dedicated therapeutic division',
                'Therapists',
                'therapists',
                'therapy',
                'Therapy',
                'clinicians',
                'clinical',
            ],
            [
                $early_learning_brand_label,
                $early_learning_nav_label,
                $early_learning_support_label,
                $early_learning_nav_label,
                __('Motor & Sensory Support', 'chroma-excellence'),
                __('Behavioral Learning Support', 'chroma-excellence'),
                $early_learning_support_program_label,
                sprintf(__('dedicated %s', 'chroma-excellence'), $early_learning_support_program_label),
                __('Educators', 'chroma-excellence'),
                __('educators', 'chroma-excellence'),
                __('support', 'chroma-excellence'),
                __('Support', 'chroma-excellence'),
                __('specialists', 'chroma-excellence'),
                __('developmental', 'chroma-excellence'),
            ],
            (string) $value
        );
    };

    $normalize_early_learning_url = static function ($value, $fallback) use ($is_early_learning_route) {
        $value = (string) $value;

        if (!$is_early_learning_route) {
            return $value !== '' ? $value : $fallback;
        }

        if ($value === '' || strpos($value, 'earlystart.chromaela.com') !== false || strpos($value, 'chromaearlystart.com') !== false) {
            return chroma_get_localized_url(home_url($fallback));
        }

        return $value;
    };

    $route_text = static function ($key, $default, $early_learning_defaults = array()) use ($page_id, $is_early_learning_route, $normalize_early_learning_text) {
        $value = chroma_get_translated_meta($page_id, $key, true);
        $legacy_learning_fragments = array(
            'early learning',
            'support',
            'developmental guidance',
            'classroom care',
            'family partnership',
            'child development support',
            'daycare',
            'whole-child',
            'warmth of home',
            'preserving the warmth',
            'every program is guided by prismpath',
            'prismpath™',
            'prismpath',
        );

        if (!$is_early_learning_route) {
            $normalized_value = strtolower(trim(wp_strip_all_tags((string) $value)));
            $is_learning_fallback = in_array(trim((string) $value), $early_learning_defaults, true);

            foreach ($legacy_learning_fragments as $fragment) {
                if ($normalized_value !== '' && strpos($normalized_value, $fragment) !== false && strpos($normalized_value, 'therapy') === false && strpos($normalized_value, 'therap') === false && strpos($normalized_value, 'early start') === false) {
                    $is_learning_fallback = true;
                    break;
                }
            }

            if ($is_learning_fallback) {
                $value = '';
            }
        }

        if (!$is_early_learning_route && in_array(trim((string) $value), $early_learning_defaults, true)) {
            $value = '';
        }

        return $normalize_early_learning_text($value ?: $default);
    };

    $hero_badge = $route_text('early_start_hero_badge', $is_early_learning_route ? $early_learning_support_label : __('Specialized Pediatric Therapy', 'chroma-excellence'), array($early_learning_support_label, 'Early Learning Support'));
    $hero_title = $route_text('early_start_hero_title', __('Every child blooms at their own pace.', 'chroma-excellence'));
    $hero_description = $route_text(
        'early_start_hero_description',
        $is_early_learning_route
            ? sprintf(__('%s brings classroom care, developmental support, and family partnership together so children can thrive in the rhythm of their day.', 'chroma-excellence'), $early_learning_brand_label)
            : __('Chroma Early Start is our dedicated therapeutic division, providing Speech, Occupational, and ABA therapies. We seamlessly bridge the gap between clinical intervention and early childhood education.', 'chroma-excellence'),
        array(sprintf(__('%s brings classroom care, developmental support, and family partnership together so children can thrive in the rhythm of their day.', 'chroma-excellence'), $early_learning_brand_label))
    );
    $primary_cta_text = $route_text('early_start_primary_cta_text', $is_early_learning_route ? sprintf(__('Explore %s', 'chroma-excellence'), $early_learning_nav_label) : __('Explore Early Start', 'chroma-excellence'), array(sprintf(__('Explore %s', 'chroma-excellence'), $early_learning_nav_label)));
    $primary_cta_url = $normalize_early_learning_url(chroma_get_translated_meta($page_id, 'early_start_primary_cta_url', true), $is_early_learning_route ? '/programs/' : 'https://chromaearlystart.com/');
    $secondary_cta_text = $route_text('early_start_secondary_cta_text', $is_early_learning_route ? __('Schedule a Tour', 'chroma-excellence') : __('Inquire About Early Start', 'chroma-excellence'), array(__('Schedule a Tour', 'chroma-excellence')));
    $secondary_cta_url = $normalize_early_learning_url(chroma_get_translated_meta($page_id, 'early_start_secondary_cta_url', true), $is_early_learning_route ? '/schedule-a-tour/' : 'https://chromaearlystart.com/contact/');
    $hero_image = chroma_get_translated_meta($page_id, 'early_start_hero_image', true) ?: $asset_base . 'hero-therapy.jpg';

    $synergy_eyebrow = $route_text('early_start_synergy_eyebrow', __('The Chroma Advantage', 'chroma-excellence'));
    $synergy_title = $route_text('early_start_synergy_title', $is_early_learning_route ? __('Where support meets education.', 'chroma-excellence') : __('Where therapy meets education.', 'chroma-excellence'), array(__('Where support meets education.', 'chroma-excellence')));
    if ($is_early_learning_route && in_array(strtolower(rtrim($synergy_title, '.')), array('where therapy meets education', 'where support meets education'), true)) {
        $synergy_title = __('Where support meets education.', 'chroma-excellence');
    }
    $synergy_intro_one = $route_text(
        'early_start_synergy_intro_one',
        $is_early_learning_route
            ? sprintf(__('Families should not have to piece together care, learning, and developmental guidance across disconnected settings. %s brings that support into one warm school community.', 'chroma-excellence'), $early_learning_brand_label)
            : __('Traditionally, parents have to juggle preschool drop-offs with driving across town to therapy clinics. Chroma Early Start solves this.', 'chroma-excellence')
    );
    $synergy_intro_two = $route_text(
        'early_start_synergy_intro_two',
        $is_early_learning_route
            ? __('By aligning classroom teachers, family communication, and child development support, we create a unified care plan for each child. Strategies are reinforced in daily routines, leading to steadier confidence and growth.', 'chroma-excellence')
            : __('By integrating our Early Start therapists directly with our Early Learning Academy teachers, we create a unified, collaborative care plan for your child. Strategies used in therapy are reinforced in the classroom, leading to faster, more sustainable progress.', 'chroma-excellence')
    );
    $synergy_bullets = array_map(
        $normalize_early_learning_text,
        [
            chroma_get_translated_meta($page_id, 'early_start_synergy_bullet_one', true) ?: __('Reduced transitions and travel for parents', 'chroma-excellence'),
            chroma_get_translated_meta($page_id, 'early_start_synergy_bullet_two', true) ?: __('Real-time collaboration between teachers and support specialists', 'chroma-excellence'),
            chroma_get_translated_meta($page_id, 'early_start_synergy_bullet_three', true) ?: __('Inclusive, neurodiversity-affirming environments', 'chroma-excellence'),
        ]
    );
    $push_title = $route_text('early_start_push_title', $is_early_learning_route ? __('Push-In Support', 'chroma-excellence') : __('Push-In Therapy', 'chroma-excellence'), array(__('Push-In Support', 'chroma-excellence')));
    $push_description = $route_text('early_start_push_description', $is_early_learning_route ? __('Educators support children right inside their Chroma Academy classrooms.', 'chroma-excellence') : __('Therapists support children right inside their Chroma Academy classrooms, embedding strategies into the natural flow of the day.', 'chroma-excellence'));
    $pull_title = $route_text('early_start_pull_title', $is_early_learning_route ? __('Focused Support', 'chroma-excellence') : __('Pull-Out Therapy', 'chroma-excellence'), array(__('Focused Support', 'chroma-excellence')));
    $pull_description = $route_text('early_start_pull_description', $is_early_learning_route ? __('Dedicated sensory-friendly spaces for focused, one-on-one support.', 'chroma-excellence') : __('Dedicated sensory gyms and quiet clinic spaces for focused, one-on-one sessions when a child needs them.', 'chroma-excellence'));
    $synergy_image_one = chroma_get_translated_meta($page_id, 'early_start_synergy_image_one', true) ?: $asset_base . 'synergy-classroom.jpg';
    $synergy_image_two = chroma_get_translated_meta($page_id, 'early_start_synergy_image_two', true) ?: $asset_base . 'synergy-sensory.jpg';

    $services_title = $route_text('early_start_services_title', $is_early_learning_route ? __('Comprehensive child development support.', 'chroma-excellence') : __('Comprehensive pediatric therapies.', 'chroma-excellence'), array(__('Comprehensive child development support.', 'chroma-excellence')));
    if ($is_early_learning_route && in_array(strtolower(rtrim($services_title, '.')), array('our core clinical services', 'comprehensive pediatric therapies'), true)) {
        $services_title = __('Comprehensive child development support.', 'chroma-excellence');
    }
    $services_description = $route_text('early_start_services_description', $is_early_learning_route ? __('Warm learning support tailored to your child\'s unique developmental profile.', 'chroma-excellence') : __('Tailored to your child\'s unique developmental profile.', 'chroma-excellence'), array(__('Warm learning support tailored to your child\'s unique developmental profile.', 'chroma-excellence')));
    $services = [
        [
            'icon' => 'fa-solid fa-comment-dots',
            'accent' => '#964030',
            'accent_bg' => 'rgba(214, 125, 107, 0.12)',
            'title' => $route_text('early_start_service_1_title', $is_early_learning_route ? __('Language & Communication', 'chroma-excellence') : __('Speech & Language', 'chroma-excellence'), array(__('Language & Communication', 'chroma-excellence'))),
            'description' => $route_text('early_start_service_1_description', $is_early_learning_route ? __('Helping children find their voice through expressive language, social communication, and classroom confidence.', 'chroma-excellence') : __('Helping children find their voice — from articulation and expressive language delays to pragmatic social communication and AAC device support.', 'chroma-excellence'), array(__('Helping children find their voice through expressive language, social communication, and classroom confidence.', 'chroma-excellence'))),
            'url' => $normalize_early_learning_url(get_post_meta($page_id, 'early_start_service_1_url', true), $is_early_learning_route ? '/programs/' : 'https://chromaearlystart.com/programs/speech-therapy/'),
        ],
        [
            'icon' => 'fa-solid fa-puzzle-piece',
            'accent' => '#8C6B2F',
            'accent_bg' => 'rgba(230, 190, 117, 0.18)',
            'title' => $route_text('early_start_service_2_title', $is_early_learning_route ? __('Motor & Sensory Support', 'chroma-excellence') : __('Occupational Therapy', 'chroma-excellence'), array(__('Motor & Sensory Support', 'chroma-excellence'))),
            'description' => $route_text('early_start_service_2_description', $is_early_learning_route ? __('Building independence through fine motor practice, sensory processing support, feeding routines, and self-regulation.', 'chroma-excellence') : __('Building independence in daily living — fine motor skills, sensory processing, feeding challenges, and self-regulation techniques.', 'chroma-excellence'), array(__('Building independence through fine motor practice, sensory processing support, feeding routines, and self-regulation.', 'chroma-excellence'))),
            'url' => $normalize_early_learning_url(get_post_meta($page_id, 'early_start_service_2_url', true), $is_early_learning_route ? '/programs/' : 'https://chromaearlystart.com/programs/occupational-therapy/'),
        ],
        [
            'icon' => 'fa-solid fa-hands-holding-child',
            'accent' => '#4A6C7C',
            'accent_bg' => 'rgba(74, 108, 124, 0.12)',
            'title' => $route_text('early_start_service_3_title', $is_early_learning_route ? __('Behavioral Learning Support', 'chroma-excellence') : __('ABA Therapy', 'chroma-excellence'), array(__('Behavioral Learning Support', 'chroma-excellence'))),
            'description' => $route_text('early_start_service_3_description', $is_early_learning_route ? __('Play-based, naturalistic support focused on communication, social skills, and reducing barriers to learning.', 'chroma-excellence') : __('Play-based, naturalistic Applied Behavior Analysis focused on communication, social skills, and reducing barriers to learning.', 'chroma-excellence'), array(__('Play-based, naturalistic support focused on communication, social skills, and reducing barriers to learning.', 'chroma-excellence'))),
            'url' => $normalize_early_learning_url(get_post_meta($page_id, 'early_start_service_3_url', true), $is_early_learning_route ? '/programs/' : 'https://chromaearlystart.com/programs/aba-therapy/'),
        ],
    ];

    $cta_title = $route_text('early_start_cta_title', __('Ready to take the next step?', 'chroma-excellence'));
    $cta_description = $route_text('early_start_cta_description', $is_early_learning_route ? __('Schedule a tour with Chroma Early Learning to meet our team, explore classrooms, and talk through the support your child needs.', 'chroma-excellence') : __('Visit the official Chroma Early Start website to meet our clinical directors, view accepted insurances, and request an initial evaluation.', 'chroma-excellence'));
    $cta_button_text = $route_text('early_start_cta_button_text', $is_early_learning_route ? __('Schedule a Tour', 'chroma-excellence') : __('Go to Early Start Website', 'chroma-excellence'));
    $cta_button_url = $normalize_early_learning_url(get_post_meta($page_id, 'early_start_cta_button_url', true), $is_early_learning_route ? '/schedule-a-tour/' : 'https://chromaearlystart.com/');
    ?>

    <style>
        .ces-page {
            background: #FFFCF8;
            color: #263238;
        }

        .ces-shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .ces-section {
            padding: 88px 0;
        }

        .ces-grid {
            display: grid;
            gap: 48px;
        }

        .ces-hero {
            background: linear-gradient(135deg, #ffffff 0%, #ffffff 52%, rgba(230, 190, 117, 0.08) 100%);
            overflow: hidden;
        }

        .ces-hero-grid,
        .ces-synergy-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: center;
        }

        .ces-synergy-copy {
            order: 1;
        }

        .ces-synergy-media {
            order: 2;
        }

        .ces-badge,
        .ces-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 11px;
            line-height: 1;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .ces-badge {
            background: rgba(230, 190, 117, 0.18);
            color: #8C6B2F;
            border: 1px solid rgba(230, 190, 117, 0.35);
        }

        .ces-eyebrow {
            background: rgba(74, 108, 124, 0.08);
            color: #2F4858;
        }

        .ces-title {
            margin: 18px 0 18px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2.4rem, 5vw, 4.6rem);
            line-height: 1.08;
            font-weight: 700;
            color: #263238;
        }

        .ces-section-title {
            margin: 0 0 18px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2rem, 3.8vw, 3.4rem);
            line-height: 1.12;
            font-weight: 700;
        }

        .ces-lead,
        .ces-copy {
            color: rgba(38, 50, 56, 0.78);
            font-size: 1.05rem;
            line-height: 1.8;
        }

        .ces-copy+.ces-copy {
            margin-top: 18px;
        }

        .ces-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 32px;
        }

        .ces-button,
        .ces-button-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-radius: 999px;
            padding: 15px 24px;
            font-size: 12px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease, color 0.2s ease;
        }

        .ces-button svg,
        .ces-button-secondary svg,
        .ces-service-link svg {
            width: 16px;
            height: 16px;
            flex: 0 0 16px;
        }

        .ces-button {
            background: #263238;
            color: #ffffff;
            box-shadow: 0 18px 36px -16px rgba(38, 50, 56, 0.35);
        }

        .ces-button-secondary {
            background: #ffffff;
            color: #2F4858;
            border: 1px solid rgba(74, 108, 124, 0.18);
        }

        .ces-button:hover,
        .ces-button-secondary:hover {
            transform: translateY(-2px);
        }

        .ces-card-frame {
            position: relative;
            padding-left: 18px;
        }

        .ces-card-frame::before {
            content: "";
            position: absolute;
            inset: 18px 0 0 34px;
            border-radius: 44px;
            background: rgba(214, 125, 107, 0.18);
        }

        .ces-hero-image {
            position: relative;
            border-radius: 40px;
            overflow: hidden;
            height: clamp(360px, 40vw, 520px);
            box-shadow: 0 24px 48px -20px rgba(47, 72, 88, 0.28);
            border: 8px solid #ffffff;
            z-index: 1;
        }

        .ces-hero-image img,
        .ces-stack-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .ces-synergy {
            background: #FFFCF8;
            border-top: 1px solid rgba(38, 50, 56, 0.06);
            border-bottom: 1px solid rgba(38, 50, 56, 0.06);
        }

        .ces-stack {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .ces-stack-column {
            display: grid;
            gap: 18px;
        }

        .ces-stack-column--offset {
            padding-top: 32px;
        }

        .ces-stack-photo {
            aspect-ratio: 4 / 3;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 16px 36px -18px rgba(47, 72, 88, 0.2);
        }

        .ces-stack-copy {
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 16px 36px -20px rgba(47, 72, 88, 0.18);
        }

        .ces-stack-copy h3 {
            margin: 0 0 10px;
            font-size: 1.2rem;
            line-height: 1.3;
            font-weight: 700;
        }

        .ces-stack-copy p {
            margin: 0;
            font-size: 0.96rem;
            line-height: 1.7;
        }

        .ces-stack-copy--blue {
            background: #4A6C7C;
            color: #ffffff;
        }

        .ces-stack-copy--gold {
            background: #E6BE75;
            color: #263238;
        }

        .ces-list {
            list-style: none;
            padding: 0;
            margin: 28px 0 0;
            display: grid;
            gap: 14px;
        }

        .ces-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-weight: 600;
            color: rgba(38, 50, 56, 0.88);
        }

        .ces-list i {
            color: #4D5C54;
            margin-top: 3px;
        }

        .ces-list-icon {
            width: 20px;
            height: 20px;
            flex: 0 0 20px;
            margin-top: 2px;
            color: #4D5C54;
        }

        .ces-list-icon svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .ces-services {
            background: #ffffff;
        }

        .ces-services-header {
            max-width: 760px;
            margin: 0 auto 44px;
            text-align: center;
        }

        .ces-service-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .ces-service-card {
            background: #FFFCF8;
            border: 1px solid rgba(38, 50, 56, 0.06);
            border-radius: 34px;
            padding: 34px 30px;
            box-shadow: 0 18px 40px -28px rgba(47, 72, 88, 0.22);
        }

        .ces-service-icon {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            line-height: 1;
            margin-bottom: 22px;
        }

        .ces-service-icon i {
            display: block;
        }

        .ces-service-card h3 {
            margin: 0 0 12px;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 1.7rem;
            line-height: 1.25;
        }

        .ces-service-card p {
            margin: 0 0 18px;
            color: rgba(38, 50, 56, 0.76);
            font-size: 0.96rem;
            line-height: 1.8;
        }

        .ces-service-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 12px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .ces-cta {
            background: linear-gradient(145deg, #2F4858 0%, #263238 100%);
            color: #ffffff;
        }

        .ces-cta-shell {
            max-width: 900px;
            text-align: center;
        }

        .ces-cta .ces-section-title,
        .ces-cta .ces-copy {
            color: #ffffff;
        }

        .ces-cta .ces-copy {
            opacity: 0.8;
            max-width: 720px;
            margin: 0 auto 28px;
        }

        @media (max-width: 980px) {

            .ces-hero-grid,
            .ces-synergy-grid,
            .ces-service-grid {
                grid-template-columns: 1fr;
            }

            .ces-hero-image {
                height: clamp(300px, 56vw, 420px);
            }
        }

        @media (min-width: 981px) {
            .ces-synergy-copy {
                order: 2;
            }

            .ces-synergy-media {
                order: 1;
            }
        }

        @media (max-width: 700px) {
            .ces-shell {
                padding: 0 18px;
            }

            .ces-section {
                padding: 64px 0;
            }

            .ces-stack {
                grid-template-columns: 1fr;
            }

            .ces-stack-column--offset {
                padding-top: 0;
            }

            .ces-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .ces-button,
            .ces-button-secondary {
                width: 100%;
            }
        }
    </style>

    <main id="primary" class="site-main ces-page">
        <article id="post-<?php the_ID(); ?>" <?php post_class('ces-page'); ?>>
            <section class="pageHero ces-section ces-hero chroma-v2-page-hero">
                <div class="ces-shell ces-grid ces-hero-grid">
                    <div>
                        <span class="ces-badge"><?php echo esc_html($hero_badge); ?></span>
                        <h1 class="ces-title"><?php echo esc_html($hero_title); ?></h1>
                        <p class="ces-lead"><?php echo esc_html($hero_description); ?></p>

                        <div class="ces-actions">
                            <a class="ces-button" href="<?php echo esc_url($primary_cta_url); ?>" target="_blank"
                                rel="noopener">
                                <?php echo esc_html($primary_cta_text); ?>
                                <?php echo chroma_early_start_icon_svg('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </a>
                            <a class="ces-button-secondary" href="<?php echo esc_url($secondary_cta_url); ?>"
                                target="_blank" rel="noopener">
                                <?php echo esc_html($secondary_cta_text); ?>
                                <?php echo chroma_early_start_icon_svg('external-link'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            </a>
                        </div>
                    </div>

                    <div class="ces-card-frame">
                        <div class="ces-hero-image">
                            <img src="<?php echo esc_url($hero_image); ?>"
                                alt="<?php esc_attr_e('Chroma educator smiling and engaging with a young child', 'chroma-excellence'); ?>"
                                loading="eager" fetchpriority="high" decoding="sync" class="no-lazy" />
                        </div>
                    </div>
                </div>
            </section>

            <section class="white borderY ces-section ces-synergy">
                <div class="ces-shell ces-grid ces-synergy-grid">
                    <div class="ces-synergy-copy">
                        <span class="ces-eyebrow"><?php echo esc_html($synergy_eyebrow); ?></span>
                        <h2 class="ces-section-title"><?php echo esc_html($synergy_title); ?></h2>
                        <p class="ces-copy"><?php echo esc_html($synergy_intro_one); ?></p>
                        <p class="ces-copy"><?php echo esc_html($synergy_intro_two); ?></p>

                        <ul class="ces-list">
                            <?php foreach ($synergy_bullets as $bullet): ?>
                                <li>
                                    <span class="ces-list-icon">
                                        <?php echo chroma_early_start_icon_svg('check'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </span>
                                    <span><?php echo esc_html($bullet); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="ces-stack ces-synergy-media">
                        <div class="ces-stack-column ces-stack-column--offset">
                            <div class="ces-stack-photo">
                                <img src="<?php echo esc_url($synergy_image_one); ?>"
                                    alt="<?php esc_attr_e('Child engaged in classroom learning', 'chroma-excellence'); ?>"
                                    loading="lazy" />
                            </div>
                            <div class="ces-stack-copy ces-stack-copy--blue">
                                <h3><?php echo esc_html($push_title); ?></h3>
                                <p><?php echo esc_html($push_description); ?></p>
                            </div>
                        </div>

                        <div class="ces-stack-column">
                            <div class="ces-stack-copy ces-stack-copy--gold">
                                <h3><?php echo esc_html($pull_title); ?></h3>
                                <p><?php echo esc_html($pull_description); ?></p>
                            </div>
                            <div class="ces-stack-photo">
                                <img src="<?php echo esc_url($synergy_image_two); ?>"
                                    alt="<?php esc_attr_e('Child engaging in sensory play', 'chroma-excellence'); ?>"
                                    loading="lazy" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="white borderY ces-section ces-services">
                <div class="ces-shell">
                    <div class="ces-services-header">
                        <h2 class="ces-section-title"><?php echo esc_html($services_title); ?></h2>
                        <p class="ces-copy"><?php echo esc_html($services_description); ?></p>
                    </div>

                    <div class="ces-grid ces-service-grid">
                        <?php foreach ($services as $service): ?>
                            <?php $service_link_label = sprintf(__('Explore %s', 'chroma-excellence'), $service['title']); ?>
                            <div class="ces-service-card">
                                <div class="ces-service-icon"
                                    style="background: <?php echo esc_attr($service['accent_bg']); ?>; color: <?php echo esc_attr($service['accent']); ?>;">
                                    <i class="<?php echo esc_attr($service['icon']); ?>" aria-hidden="true"></i>
                                </div>
                                <h3><?php echo esc_html($service['title']); ?></h3>
                                <p><?php echo esc_html($service['description']); ?></p>
                                <a class="ces-service-link" href="<?php echo esc_url($service['url']); ?>" target="_blank"
                                    rel="noopener" aria-label="<?php echo esc_attr($service_link_label); ?>"
                                    style="color: <?php echo esc_attr($service['accent']); ?>;">
                                    <?php echo esc_html($service_link_label); ?>
                                    <?php echo chroma_early_start_icon_svg('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="cream ces-section ces-cta">
                <div class="ces-shell ces-cta-shell">
                    <h2 class="ces-section-title"><?php echo esc_html($cta_title); ?></h2>
                    <p class="ces-copy"><?php echo esc_html($cta_description); ?></p>
                    <a class="ces-button-secondary" href="<?php echo esc_url($cta_button_url); ?>" target="_blank"
                        rel="noopener">
                        <?php echo esc_html($cta_button_text); ?>
                        <?php echo chroma_early_start_icon_svg('external-link'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </a>
                </div>
            </section>
        </article>
    </main>

    <?php
endwhile;

get_footer();

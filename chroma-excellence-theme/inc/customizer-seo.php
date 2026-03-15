<?php
/**
 * SEO & Social Customizer Settings
 * Twitter, Facebook, Open Graph settings
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register SEO Customizer Settings
 */
function chroma_seo_customizer_settings($wp_customize) {
    
    // Add SEO Section
    $wp_customize->add_section('chroma_seo_settings', [
        'title'       => __('SEO & Social', 'chroma-excellence'),
        'description' => __('Configure Twitter Cards, Open Graph, and other SEO settings.', 'chroma-excellence'),
        'priority'    => 35,
    ]);
    
    // =========================================
    // Twitter Settings
    // =========================================
    
    // Twitter Site Handle
    $wp_customize->add_setting('chroma_twitter_site', [
        'default'           => '',
        'sanitize_callback' => 'chroma_sanitize_twitter_handle',
        'transport'         => 'refresh',
    ]);
    
    $wp_customize->add_control('chroma_twitter_site', [
        'label'       => __('Twitter Site Handle', 'chroma-excellence'),
        'description' => __('Your Twitter/X handle (e.g., @chromaela or chromaela). Used for twitter:site meta tag.', 'chroma-excellence'),
        'section'     => 'chroma_seo_settings',
        'type'        => 'text',
        'input_attrs' => [
            'placeholder' => '@chromaela',
        ],
    ]);
    
    // Twitter Card Type
    $wp_customize->add_setting('chroma_twitter_card_type', [
        'default'           => 'summary_large_image',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);
    
    $wp_customize->add_control('chroma_twitter_card_type', [
        'label'       => __('Twitter Card Type', 'chroma-excellence'),
        'description' => __('Choose how your links appear when shared on Twitter/X.', 'chroma-excellence'),
        'section'     => 'chroma_seo_settings',
        'type'        => 'select',
        'choices'     => [
            'summary'             => __('Summary (small image)', 'chroma-excellence'),
            'summary_large_image' => __('Summary with Large Image', 'chroma-excellence'),
        ],
    ]);
    
    // =========================================
    // Facebook / Open Graph Settings
    // =========================================
    
    // Facebook App ID
    $wp_customize->add_setting('chroma_fb_app_id', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);
    
    $wp_customize->add_control('chroma_fb_app_id', [
        'label'       => __('Facebook App ID', 'chroma-excellence'),
        'description' => __('Optional. Used for Facebook Insights.', 'chroma-excellence'),
        'section'     => 'chroma_seo_settings',
        'type'        => 'text',
        'input_attrs' => [
            'placeholder' => '123456789012345',
        ],
    ]);
    
    // Default OG Image
    $wp_customize->add_setting('chroma_default_og_image', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ]);
    
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'chroma_default_og_image', [
        'label'       => __('Default Social Share Image', 'chroma-excellence'),
        'description' => __('Default image for social sharing when a post has no featured image. Recommended: 1200x630px.', 'chroma-excellence'),
        'section'     => 'chroma_seo_settings',
    ]));
    
    // =========================================
    // Organization Social Links
    // =========================================
    
    // Facebook Page URL
    $wp_customize->add_setting('chroma_facebook_url', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ]);
    
    $wp_customize->add_control('chroma_facebook_url', [
        'label'       => __('Facebook Page URL', 'chroma-excellence'),
        'description' => __('Your Facebook page URL. Used in Organization schema sameAs.', 'chroma-excellence'),
        'section'     => 'chroma_seo_settings',
        'type'        => 'url',
        'input_attrs' => [
            'placeholder' => 'https://facebook.com/chromaela',
        ],
    ]);
    
    // Instagram URL
    $wp_customize->add_setting('chroma_instagram_url', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ]);
    
    $wp_customize->add_control('chroma_instagram_url', [
        'label'       => __('Instagram URL', 'chroma-excellence'),
        'section'     => 'chroma_seo_settings',
        'type'        => 'url',
        'input_attrs' => [
            'placeholder' => 'https://instagram.com/chromaela',
        ],
    ]);
    
    // LinkedIn URL
    $wp_customize->add_setting('chroma_linkedin_url', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ]);
    
    $wp_customize->add_control('chroma_linkedin_url', [
        'label'       => __('LinkedIn URL', 'chroma-excellence'),
        'section'     => 'chroma_seo_settings',
        'type'        => 'url',
        'input_attrs' => [
            'placeholder' => 'https://linkedin.com/company/chromaela',
        ],
    ]);
    
    // YouTube URL
    $wp_customize->add_setting('chroma_youtube_url', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ]);
    
    $wp_customize->add_control('chroma_youtube_url', [
        'label'       => __('YouTube URL', 'chroma-excellence'),
        'section'     => 'chroma_seo_settings',
        'type'        => 'url',
        'input_attrs' => [
            'placeholder' => 'https://youtube.com/@chromaela',
        ],
    ]);
    
    // =========================================
    // Advanced SEO Settings (Rank #1)
    // =========================================
    
    // Brand Phonetic Name (Tier 12 - TT)
    $wp_customize->add_setting('chroma_global_brand_phonetic', [
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ]);
    
    $wp_customize->add_control('chroma_global_brand_phonetic', [
        'label'       => __('Brand Phonetic Name', 'chroma-excellence'),
        'description' => __('How your brand is pronounced (for voice search). E.g., "KROH-muh Early Learning"', 'chroma-excellence'),
        'section'     => 'chroma_seo_settings',
        'type'        => 'text',
        'input_attrs' => [
            'placeholder' => 'KROH-muh Early Learning',
        ],
    ]);
    
    // Footer SEO Text (Tier 12 - SS)
    $wp_customize->add_setting('chroma_footer_seo_text', [
        'default'           => '',
        'sanitize_callback' => 'wp_kses_post',
        'transport'         => 'refresh',
    ]);
    
    $wp_customize->add_control('chroma_footer_seo_text', [
        'label'       => __('Footer SEO Text (NLP)', 'chroma-excellence'),
        'description' => __('Proximity-based footer copy for local SEO. Include city names and "near me" keywords.', 'chroma-excellence'),
        'section'     => 'chroma_seo_settings',
        'type'        => 'textarea',
    ]);
}
add_action('customize_register', 'chroma_seo_customizer_settings');

/**
 * Sanitize Twitter handle
 */
function chroma_sanitize_twitter_handle($handle) {
    $handle = sanitize_text_field($handle);
    // Remove @ if present, we'll add it back when outputting
    $handle = ltrim($handle, '@');
    return $handle;
}

/**
 * Get formatted Twitter handle with @
 */
function chroma_get_twitter_handle() {
    $handle = get_theme_mod('chroma_twitter_site', '');
    if (empty($handle)) {
        $handle = apply_filters('chroma_default_twitter_handle', 'chromaela');
    }

    $handle = ltrim((string) $handle, '@');
    if ($handle === '') {
        return '';
    }

    return '@' . $handle;
}

/**
 * Build the current page URL before SEO filters are applied.
 *
 * @return string
 */
function chroma_get_context_base_url() {
    global $wp;

    $request_path = (isset($wp) && isset($wp->request)) ? (string) $wp->request : '';
    $url = home_url(add_query_arg([], $request_path));

    if (is_singular()) {
        $post = get_post();
        if ($post) {
            $permalink = get_permalink($post);
            if ($permalink) {
                $url = $permalink;
            }
        }
    } elseif (is_home() || is_front_page()) {
        $url = home_url('/');
    } elseif (is_archive()) {
        if (is_post_type_archive()) {
            $archive_url = get_post_type_archive_link(get_post_type());
            if ($archive_url) {
                $url = $archive_url;
            }
        } elseif (is_category() || is_tag() || is_tax()) {
            $term_link = get_term_link(get_queried_object());
            if (!is_wp_error($term_link)) {
                $url = $term_link;
            }
        }
    }

    return (string) $url;
}

/**
 * Build the current canonical URL while honoring route-level overrides.
 *
 * @return string
 */
function chroma_get_context_canonical_url() {
    $url = chroma_get_context_base_url();
    $filtered = apply_filters('wpseo_canonical', $url);

    if (is_string($filtered) && $filtered !== '') {
        return $filtered;
    }

    return $url;
}

/**
 * Build context-aware meta description.
 *
 * @return string
 */
function chroma_get_context_meta_description() {
    $description = '';

    if (is_singular()) {
        $post = get_post();
        if ($post) {
            $description = get_post_meta($post->ID, 'meta_description', true);
            if (empty($description)) {
                $description = get_the_excerpt($post);
            }
            if (empty($description) && !empty($post->post_content)) {
                $description = wp_trim_words(wp_strip_all_tags($post->post_content), 30, '...');
            }
        }
    } elseif (is_home() || is_front_page()) {
        $description = get_bloginfo('description');
    } elseif (is_archive()) {
        $description = trim(strip_tags((string) get_the_archive_description()));
    }

    if (empty($description)) {
        $description = get_bloginfo('description');
    }

    $description = apply_filters('wpseo_metadesc', (string) $description);
    if ($description === '') {
        $description = get_bloginfo('description');
    }

    return wp_trim_words(strip_tags((string) $description), 30, '...');
}

/**
 * Shared meta description output.
 */
function chroma_shared_meta_description() {
    if (is_admin() || is_404() || is_search() || is_feed() || is_robots()) {
        return;
    }

    static $rendered = false;
    if ($rendered) {
        return;
    }

    $description = chroma_get_context_meta_description();
    if ($description !== '') {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        $rendered = true;
    }
}
add_action('wp_head', 'chroma_shared_meta_description', 2);

/**
 * Output Twitter and Open Graph meta tags
 */
function chroma_output_social_meta_tags() {
    if (is_admin() || is_404() || is_search() || is_feed() || is_robots() || is_trackback()) {
        return;
    }

    if (did_action('chroma_social_meta_output_done')) {
        return;
    }

    $twitter_site = chroma_get_twitter_handle();
    $twitter_card = get_theme_mod('chroma_twitter_card_type', 'summary_large_image');
    $fb_app_id = get_theme_mod('chroma_fb_app_id', '');
    $default_og_image = get_theme_mod('chroma_default_og_image', '');
    
    // Get current page data
    $title = wp_get_document_title();
    $description = chroma_get_context_meta_description();
    $description = apply_filters('wpseo_opengraph_desc', $description);
    if (!is_string($description) || $description === '') {
        $description = chroma_get_context_meta_description();
    }

    $image = $default_og_image;
    $url = chroma_get_context_canonical_url();
    $type = 'website';
    
    if (is_singular()) {
        $post = get_post();
        if ($post && has_post_thumbnail($post)) {
            $image = get_the_post_thumbnail_url($post, 'large');
        }
        $type = is_singular('post') ? 'article' : 'website';
    } elseif (is_home() || is_front_page()) {
        $url = home_url('/');
    }

    $social_url = apply_filters('wpseo_opengraph_url', $url);
    if (is_string($social_url) && $social_url !== '') {
        $url = $social_url;
    }
    
    if (empty($image) && function_exists('get_site_icon_url')) {
        $image = get_site_icon_url(512);
    }

    if (empty($image) && function_exists('has_custom_logo') && has_custom_logo()) {
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
            if ($logo_url) {
                $image = $logo_url;
            }
        }
    }
    
    // Output Twitter Card tags
    echo "\n<!-- Twitter Card Meta -->\n";
    echo '<meta name="twitter:card" content="' . esc_attr($twitter_card) . '">' . "\n";
    
    if ($twitter_site) {
        echo '<meta name="twitter:site" content="' . esc_attr($twitter_site) . '">' . "\n";
        // Also output creator if single post
        if (is_singular('post')) {
            $author_twitter = get_the_author_meta('twitter');
            if ($author_twitter) {
                echo '<meta name="twitter:creator" content="@' . esc_attr(ltrim($author_twitter, '@')) . '">' . "\n";
            } else {
                echo '<meta name="twitter:creator" content="' . esc_attr($twitter_site) . '">' . "\n";
            }
        }
    }
    
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    
    if ($image) {
        echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
    }
    
    // Output Open Graph tags
    echo "\n<!-- Open Graph Meta -->\n";
    echo '<meta property="og:type" content="' . esc_attr($type) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    
    if ($image) {
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        echo '<meta property="og:image:width" content="1200">' . "\n";
        echo '<meta property="og:image:height" content="630">' . "\n";
    }
    
    if ($fb_app_id) {
        echo '<meta property="fb:app_id" content="' . esc_attr($fb_app_id) . '">' . "\n";
    }
    
    // Article-specific OG tags
    if (is_singular('post')) {
        echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '">' . "\n";
        echo '<meta property="article:author" content="' . esc_attr(get_the_author()) . '">' . "\n";
    }

    do_action('chroma_social_meta_output_done');
}
add_action('wp_head', 'chroma_output_social_meta_tags', 5);

/**
 * Get all social URLs for schema sameAs
 */
function chroma_get_social_urls() {
    $urls = [];
    
    $platforms = [
        'chroma_twitter_site'  => 'https://twitter.com/',
        'chroma_facebook_url'  => '',
        'chroma_instagram_url' => '',
        'chroma_linkedin_url'  => '',
        'chroma_youtube_url'   => '',
    ];
    
    foreach ($platforms as $mod => $prefix) {
        $value = get_theme_mod($mod, '');
        if (empty($value)) continue;
        
        if ($mod === 'chroma_twitter_site') {
            // Twitter handle needs to be converted to URL
            $urls[] = $prefix . ltrim($value, '@');
        } else {
            $urls[] = $value;
        }
    }
    
    return array_filter($urls);
}

<?php
/**
 * SEO Automations Bootstrap
 * Loads all SEO automation classes
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Phase 1: Internal Linking
require_once __DIR__ . '/class-related-locations.php';
require_once __DIR__ . '/class-related-programs.php';
require_once __DIR__ . '/class-keyword-linker.php';
require_once __DIR__ . '/class-footer-city-links.php';

// Phase 2: Auto-Generated Pages
require_once __DIR__ . '/class-combo-page-generator.php';
require_once __DIR__ . '/class-combo-page-data.php';

require_once __DIR__ . '/class-combo-ai-generator.php';
require_once __DIR__ . '/class-combo-internal-links.php';
require_once __DIR__ . '/class-near-me-pages.php';

// Phase 3: Technical SEO
require_once __DIR__ . '/class-dynamic-titles.php';
require_once __DIR__ . '/class-canonical-enforcer.php';
require_once __DIR__ . '/class-author-tags.php';
require_once __DIR__ . '/class-speculation-rules.php';
require_once __DIR__ . '/class-indexnow.php';

// Phase 4: Analysis
require_once __DIR__ . '/class-link-equity-analyzer.php';

// Phase 5: Advanced
require_once __DIR__ . '/class-geographic-seo.php';
require_once __DIR__ . '/class-credential-badges.php';
require_once __DIR__ . '/class-entity-seo.php';
require_once __DIR__ . '/class-accessibility-seo.php';
require_once __DIR__ . '/class-schema-bulk-ops.php';

/**
 * Register default options
 */
add_action('after_setup_theme', function() {
    // Set defaults if not already set
    $defaults = [
        'chroma_seo_show_related_locations' => true,
        'chroma_seo_link_programs_locations' => true,
        'chroma_seo_enable_keyword_linking' => true,
        'chroma_seo_show_footer_cities' => true,
        'chroma_seo_enable_dynamic_titles' => true,
        'chroma_seo_enable_canonical' => true,
        'chroma_seo_trailing_slash' => true,
        'chroma_seo_show_author_meta' => true,
        'chroma_seo_show_author_box' => true,
        'chroma_seo_show_credential_badges' => true,
        'chroma_seo_enable_skip_nav' => true,
        'chroma_seo_enable_focus_indicators' => true,
        'chroma_enable_speculation_rules' => 'yes',
        'chroma_enable_indexnow' => 'yes'
    ];
    
    foreach ($defaults as $key => $default) {
        if (get_option($key) === false) {
            update_option($key, $default);
        }
    }
});

/**
 * Flush rewrite rules on activation (Plugin context)
 */
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});



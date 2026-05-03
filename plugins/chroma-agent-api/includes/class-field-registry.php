<?php

namespace ChromaAgentAPI;

if (!defined('ABSPATH')) {
    exit;
}

class Field_Registry
{
    public static function all_scopes(): array
    {
        return [
            'read:content',
            'write:content',
            'read:theme',
            'write:theme',
            'read:seo',
            'write:seo',
            'read:media',
            'write:media',
            'read:portal',
            'write:portal',
            'read:schools',
            'write:schools',
            'read:forms',
            'write:forms',
            'read:leads',
            'write:leads',
            'read:maintenance',
            'write:maintenance',
            'admin:keys',
        ];
    }

    public static function theme_customizer_groups(): array
    {
        return [
            'header' => [
                'storage' => 'theme_mod',
                'fields' => [
                    'chroma_header_text',
                    'chroma_header_cta_text',
                    'chroma_book_tour_url',
                    'chroma_logo_width_desktop',
                    'chroma_logo_width_mobile',
                ],
            ],
            'footer' => [
                'storage' => 'theme_mod',
                'fields' => [
                    'chroma_footer_phone',
                    'chroma_footer_email',
                    'chroma_footer_address',
                    'chroma_footer_facebook',
                    'chroma_footer_instagram',
                    'chroma_footer_linkedin',
                    'chroma_footer_twitter',
                    'chroma_footer_youtube',
                ],
            ],
            'home' => [
                'storage' => 'theme_mod',
                'json_fields' => [
                    'chroma_home_stats_json',
                    'chroma_home_prismpath_cards_json',
                    'chroma_home_program_wizard_json',
                    'chroma_home_curriculum_profiles_json',
                    'chroma_home_schedule_tracks_json',
                    'chroma_home_faq_items_json',
                    'chroma_home_parent_reviews_json',
                    'chroma_home_tour_benefits_json',
                ],
                'fields' => [
                    'chroma_home_hero_heading',
                    'chroma_home_hero_subheading',
                    'chroma_home_hero_cta_label',
                    'chroma_home_hero_cta_url',
                    'chroma_home_hero_secondary_label',
                    'chroma_home_hero_secondary_url',
                    'chroma_home_hero_image',
                    'chroma_home_hero_image_alt',
                    'chroma_home_hero_pill_format',
                    'chroma_home_hero_supporting_text',
                    'chroma_home_hero_rating_label',
                    'chroma_home_hero_quality_badge_text',
                    'chroma_home_hero_fallback_label',
                    'chroma_home_hero_badge_heading',
                    'chroma_home_hero_badge_text',
                    'chroma_home_stats_json',
                    'chroma_home_prismpath_eyebrow',
                    'chroma_home_prismpath_heading',
                    'chroma_home_prismpath_cta_label',
                    'chroma_home_prismpath_cta_url',
                    'chroma_home_prismpath_cards_json',
                    'chroma_home_prismpath_readiness_heading',
                    'chroma_home_prismpath_readiness_desc',
                    'chroma_home_program_wizard_heading',
                    'chroma_home_program_wizard_subheading',
                    'chroma_home_program_wizard_primary_cta_label',
                    'chroma_home_program_wizard_primary_cta_aria_label',
                    'chroma_home_program_wizard_secondary_cta_label',
                    'chroma_home_program_wizard_image_alt',
                    'chroma_home_program_wizard_reset_label',
                    'chroma_home_program_wizard_json',
                    'chroma_home_curriculum_eyebrow',
                    'chroma_home_curriculum_heading',
                    'chroma_home_curriculum_subheading',
                    'chroma_home_curriculum_chart_aria_label',
                    'chroma_home_curriculum_profiles_json',
                    'chroma_home_schedule_eyebrow',
                    'chroma_home_schedule_heading',
                    'chroma_home_schedule_subheading',
                    'chroma_home_schedule_tracks_json',
                    'chroma_home_faq_heading',
                    'chroma_home_faq_disable_schema',
                    'chroma_home_faq_subheading',
                    'chroma_home_faq_items_json',
                    'chroma_home_locations_heading',
                    'chroma_home_locations_subheading',
                    'chroma_home_locations_cta_label',
                    'chroma_home_locations_cta_link',
                    'chroma_home_reviews_eyebrow',
                    'chroma_home_reviews_heading',
                    'chroma_home_reviews_subheading',
                    'chroma_home_reviews_dot_aria_label_format',
                    'chroma_home_reviews_prev_label',
                    'chroma_home_reviews_next_label',
                    'chroma_home_parent_reviews_json',
                    'chroma_home_tour_heading',
                    'chroma_home_tour_subheading',
                    'chroma_home_tour_benefits_heading',
                    'chroma_home_tour_benefits_json',
                    'chroma_home_tour_time_label',
                    'chroma_home_tour_trust_text',
                    'chroma_home_tour_plugin_missing_message',
                ],
            ],
            'locations' => [
                'storage' => 'theme_mod',
                'fields' => [
                    'chroma_locations_archive_title',
                    'chroma_locations_archive_subtitle',
                    'chroma_locations_label',
                    'chroma_locations_badge_fallback',
                    'chroma_locations_open_text',
                ],
            ],
            'scripts' => [
                'storage' => 'theme_mod',
                'fields' => [
                    'chroma_header_scripts',
                    'chroma_footer_scripts',
                ],
            ],
            'seo-social' => [
                'storage' => 'theme_mod',
                'fields' => [
                    'chroma_twitter_site',
                    'chroma_twitter_card_type',
                    'chroma_fb_app_id',
                    'chroma_default_og_image',
                    'chroma_facebook_url',
                    'chroma_instagram_url',
                    'chroma_linkedin_url',
                    'chroma_youtube_url',
                    'chroma_global_brand_phonetic',
                    'chroma_footer_seo_text',
                ],
            ],
        ];
    }

    public static function theme_page_meta_groups(): array
    {
        return [
            'home' => [
                'template' => 'front-page.php',
                'fields' => [
                    'home_hero_heading',
                    '_chroma_es_home_hero_heading',
                    'home_hero_subheading',
                    '_chroma_es_home_hero_subheading',
                    'home_hero_cta_label',
                    '_chroma_es_home_hero_cta_label',
                    'home_hero_secondary_label',
                    '_chroma_es_home_hero_secondary_label',
                    'home_prismpath_eyebrow',
                    '_chroma_es_home_prismpath_eyebrow',
                    'home_prismpath_heading',
                    '_chroma_es_home_prismpath_heading',
                    'home_prismpath_subheading',
                    '_chroma_es_home_prismpath_subheading',
                    'home_prismpath_cta_label',
                    '_chroma_es_home_prismpath_cta_label',
                    'home_prismpath_readiness_heading',
                    '_chroma_es_home_prismpath_readiness_heading',
                    'home_prismpath_readiness_desc',
                    '_chroma_es_home_prismpath_readiness_desc',
                    'home_locations_heading',
                    '_chroma_es_home_locations_heading',
                    'home_locations_subheading',
                    '_chroma_es_home_locations_subheading',
                    'home_locations_cta_label',
                    '_chroma_es_home_locations_cta_label',
                    'home_faq_heading',
                    '_chroma_es_home_faq_heading',
                    'home_faq_subheading',
                    '_chroma_es_home_faq_subheading',
                    '_chroma_es_home_stats_json',
                    '_chroma_es_home_prismpath_cards_json',
                    '_chroma_es_home_faq_items_json',
                ],
            ],
            'stories' => [
                'template' => 'page-stories.php',
                'fields' => ['stories_featured_post'],
            ],
            'privacy' => [
                'template' => 'page-privacy.php',
                'fields' => self::privacy_page_fields(),
            ],
            'general-seo' => [
                'template' => '*',
                'fields' => [
                    'meta_description',
                    'meta_keywords',
                    'about_meta_title',
                    'about_meta_description',
                    'about_structured_data',
                ],
            ],
            'about' => [
                'template' => 'page-about.php',
                'prefixes' => ['about_', '_chroma_es_about_'],
                'fields' => ['_about_defaults_seeded'],
            ],
            'curriculum' => [
                'template' => 'page-curriculum.php',
                'prefixes' => ['curriculum_', '_chroma_es_curriculum_'],
                'fields' => ['_curriculum_defaults_seeded'],
            ],
            'contact' => [
                'template' => 'page-contact.php',
                'prefixes' => ['contact_', '_chroma_es_contact_'],
                'fields' => ['_contact_defaults_seeded'],
            ],
            'early-start' => [
                'template' => 'page-early-start.php',
                'prefixes' => ['early_start_', '_chroma_es_early_start_'],
                'fields' => ['_early_start_defaults_seeded'],
            ],
            'parents' => [
                'template' => 'page-parents.php',
                'prefixes' => ['parents_', '_chroma_es_parents_', 'parent_', '_chroma_es_parent_'],
                'fields' => ['_parents_defaults_seeded'],
            ],
            'careers' => [
                'template' => 'page-careers.php',
                'prefixes' => ['careers_', '_chroma_es_careers_', 'career_', '_chroma_es_career_'],
                'fields' => ['_careers_defaults_seeded'],
            ],
            'employers' => [
                'template' => 'page-employers.php',
                'prefixes' => ['employers_', '_chroma_es_employers_', 'employer_', '_chroma_es_employer_'],
                'fields' => ['_employers_defaults_seeded'],
            ],
        ];
    }

    public static function theme_cpt_meta_groups(): array
    {
        return [
            'program' => [
                'fields' => [
                    'program_anchor_slug',
                    'program_seo_heading',
                    'program_seo_summary',
                    'program_seo_highlights',
                    'program_meta_title',
                    'program_meta_description',
                    'program_faq_items',
                    'program_lesson_plan_file',
                    'program_locations',
                    'program_icon',
                    'program_age_range',
                    'program_features',
                    'program_cta_text',
                    'program_cta_link',
                    'program_color_scheme',
                    'program_hero_title',
                    'program_hero_description',
                    'program_hero_image',
                    'program_prism_title',
                    'program_prism_description',
                    'program_prism_focus_items',
                    'program_prism_physical',
                    'program_prism_emotional',
                    'program_prism_social',
                    'program_prism_academic',
                    'program_prism_creative',
                    'program_schedule_title',
                    'program_schedule_items',
                    'program_faqs',
                    'program_gallery',
                    'program_testimonials',
                ],
            ],
            'location' => [
                'fields' => [
                    'location_hero_subtitle',
                    'location_hero_review_text',
                    'location_hero_review_author',
                    'location_hero_gallery',
                    'location_virtual_tour_embed',
                    'location_tagline',
                    'location_description',
                    'location_google_rating',
                    'location_hours',
                    'location_ages_served',
                    'location_director_name',
                    'location_director_heading',
                    'location_director_bio',
                    'location_director_photo',
                    'location_director_signature',
                    'location_maps_embed',
                    'location_tour_booking_link',
                    'location_summer_camp_calendar_url',
                    'location_summer_camp_calendar_attachment_id',
                    'summer_camp_calendar_url',
                    'location_school_pickups',
                    'location_seo_content_title',
                    'location_seo_content_text',
                    'location_address',
                    'location_city',
                    'location_state',
                    'location_zip',
                    'location_phone',
                    'location_email',
                    'location_latitude',
                    'location_longitude',
                    'location_service_areas',
                    'location_special_programs',
                    'location_faq_items',
                    'location_gmb_url',
                    '_chroma_license_number',
                    'location_quality_rated',
                ],
            ],
            'city' => [
                'fields' => [
                    'city_county',
                    'city_intro_text',
                    'city_nearby_locations',
                ],
            ],
            'team_member' => [
                'fields' => ['team_member_title'],
            ],
            'career' => [
                'prefixes' => ['career_', '_career_'],
                'fields' => [
                    '_career_external_url',
                    '_career_location',
                    '_career_type',
                    '_career_date_posted',
                ],
            ],
        ];
    }

    public static function theme_taxonomy_meta_groups(): array
    {
        return [
            'location_region' => [
                'fields' => [
                    'region_color_bg',
                    'region_color_text',
                    'region_color_border',
                ],
            ],
        ];
    }

    public static function seo_option_groups(): array
    {
        return [
            'llm' => [
                'fields' => [
                    'chroma_openai_api_key',
                    'chroma_google_places_api_key',
                    'chroma_llm_model',
                    'chroma_llm_base_url',
                    'chroma_llm_rate_limit',
                    'chroma_llm_cache_duration',
                    'chroma_llm_available_models',
                    'chroma_llm_brand_voice',
                    'chroma_llm_brand_context',
                    'chroma_seo_phone',
                    'chroma_seo_email',
                    'chroma_seo_phonetic_name',
                ],
            ],
            'validator' => [
                'fields' => [
                    'chroma_validator_batch_size',
                    'chroma_validator_request_delay',
                    'chroma_validator_timeout',
                    'chroma_validator_cache_ttl',
                    'chroma_validator_max_retries',
                    'chroma_validator_email_alerts',
                    'chroma_validator_post_types',
                    'chroma_validator_sitemaps',
                    'chroma_validator_exclusions',
                    'chroma_validation_cache_ver',
                ],
            ],
            'automation' => [
                'fields' => [
                    'chroma_careers_feed_url',
                    'chroma_combo_auto_publish',
                    'chroma_seo_manual_cities_raw',
                    'chroma_seo_manual_cities',
                    'chroma_faq_schema_disabled',
                    'chroma_breadcrumbs_schema_disabled',
                    'chroma_seo_show_related_locations',
                    'chroma_seo_link_programs_locations',
                    'chroma_seo_enable_keyword_linking',
                    'chroma_seo_show_footer_cities',
                    'chroma_seo_enable_dynamic_titles',
                    'chroma_seo_enable_canonical',
                    'chroma_seo_trailing_slash',
                    'chroma_seo_redirect_canonical',
                    'chroma_seo_show_author_meta',
                    'chroma_seo_show_author_box',
                    'chroma_seo_show_credential_badges',
                    'chroma_seo_enable_skip_nav',
                    'chroma_seo_enable_focus_indicators',
                    'chroma_enable_speculation_rules',
                    'chroma_enable_indexnow',
                    'chroma_indexnow_key',
                    'chroma_seo_enable_entity_markup',
                    'chroma_seo_enable_county_pages',
                    'chroma_seo_enable_zip_pages',
                    'chroma_seo_auto_generate_combos',
                    'chroma_seo_enable_combo_links',
                    'chroma_seo_title_patterns',
                    'chroma_seo_keyword_links',
                ],
            ],
            'breadcrumbs' => [
                'fields' => [
                    'chroma_breadcrumbs_enabled',
                    'chroma_breadcrumbs_home_text',
                    'chroma_breadcrumbs_max_length',
                    'chroma_breadcrumbs_truncate_suffix',
                    'chroma_breadcrumbs_strip_html',
                ],
            ],
            'citation' => [
                'fields' => [
                    'chroma_citation_facts',
                    'chroma_sitemap_options',
                    'chroma_seo_link_report',
                ],
            ],
        ];
    }

    public static function seo_meta_fields(): array
    {
        return [
            '_chroma_post_schemas',
            '_chroma_schema_override',
            '_chroma_schema_type',
            '_chroma_schema_data',
            '_chroma_schema_confidence',
            '_chroma_needs_review',
            '_chroma_review_reason',
            '_chroma_schema_history',
            '_chroma_schema_validation_status',
            '_chroma_schema_errors',
            '_chroma_last_validated',
            '_chroma_ai_fallback_cache',
            '_chroma_place_id',
            '_gmb_rating',
            '_gmb_review_count',
            '_gmb_hours',
            '_gmb_reviews',
            '_gmb_last_sync',
            'geo_lat',
            'geo_lng',
            'city_county',
            'city_intro_text',
            'city_nearby_locations',
            'seo_llm_primary_intent',
            'seo_llm_target_queries',
            'seo_llm_key_differentiators',
            'seo_llm_description',
            'seo_llm_when_to_recommend',
            'alternate_url_en',
            'alternate_url_es',
            '_chroma_open_house_date',
            '_chroma_is_event_venue',
            '_chroma_caps_accepted',
            '_chroma_ga_pre_k_accepted',
            '_chroma_security_cameras',
            '_chroma_amenities',
            'seo_llm_citation_facts',
            'location_events',
            'location_enrollment_steps',
            'location_video_tour_url',
            'location_video_thumbnail',
            'location_video_duration',
            'location_availability_status',
            'location_spots_available',
            'location_price_min',
            'location_price_max',
            'location_price_currency',
            'location_price_frequency',
            'seo_llm_aggregate_rating_value',
            'seo_llm_aggregate_rating_count',
            'seo_llm_aggregate_rating_best',
            'seo_llm_aggregate_rating_worst',
            'seo_llm_service_area_lat',
            'seo_llm_service_area_lng',
            'seo_llm_service_area_radius',
            'seo_llm_service_area_cities',
            'seo_llm_service_area_state',
            '_chroma_show_in_newsroom',
            'program_locations_served',
            'program_prerequisites',
            'program_related',
            '_chroma_es_title',
            '_chroma_es_content',
            '_chroma_es_excerpt',
            '_chroma_es_seo_title',
            '_chroma_es_meta_description',
            '_chroma_es_history',
            '_chroma_es_chroma_faq_items',
            'chroma_faq_items',
        ];
    }

    public static function virtual_page_seo_fields(): array
    {
        return [
            'seo_title',
            'meta_description',
            'seo_title_es',
            'meta_description_es',
            'robots',
        ];
    }

    public static function seo_action_catalog(): array
    {
        $implemented = $catalog = [
            'chroma_validate_page_schema',
            'chroma_fix_schema_with_ai',
            'chroma_review_schema',
            'chroma_get_review_queue',
            'chroma_validate_schema',
            'chroma_validate_post_schema',
            'chroma_fetch_schema_inspector',
            'chroma_save_schema_inspector',
            'chroma_scan_schema_batch',
            'chroma_get_schema_fields',
            'chroma_fetch_social_preview',
            'chroma_fetch_llm_data',
            'chroma_save_llm_targeting',
            'chroma_reset_post_schema',
            'chroma_apply_schema_fix',
            'chroma_fetch_live_schema',
            'chroma_sync_schema_to_builder',
            'chroma_schema_cleanup_scan',
            'chroma_schema_cleanup_execute',
            'chroma_save_llm_settings',
            'chroma_test_llm_connection',
            'chroma_generate_schema',
            'chroma_generate_llm_targeting',
            'chroma_generate_general_seo_meta',
            'chroma_translate_text',
            'chroma_fetch_available_models',
            'chroma_scan_theme_strings',
            'chroma_save_string_translations',
            'chroma_bulk_translate_strings',
            'chroma_export_po',
            'chroma_debug_meta',
            'chroma_save_sitemap_urls',
            'chroma_parse_sitemap_urls',
            'chroma_validate_url',
            'chroma_clear_validation_cache',
            'chroma_save_validator_setting',
            'chroma_run_link_analysis',
            'chroma_link_equity_ai_preview',
            'chroma_link_equity_ai_apply',
            'chroma_combo_ai_generate',
            'chroma_combo_ai_bulk_generate',
            'chroma_combo_bulk_status',
            'chroma_combo_save_data',
            'chroma_combo_get_data',
            'chroma_combo_ai_translate',
            'chroma_combo_ai_bulk_translate',
            'chroma_bulk_reset_schema',
            'chroma_bulk_reset_faq',
            'chroma_analyze_image',
            'chroma_compare_competitor',
            'chroma_save_breadcrumb_settings',
        ];

        $catalog = [
            'chroma_validate_page_schema',
            'chroma_fix_schema_with_ai',
            'chroma_review_schema',
            'chroma_get_review_queue',
            'chroma_validate_schema',
            'chroma_validate_post_schema',
            'chroma_fetch_schema_inspector',
            'chroma_save_schema_inspector',
            'chroma_scan_schema_batch',
            'chroma_get_schema_fields',
            'chroma_fetch_social_preview',
            'chroma_fetch_llm_data',
            'chroma_save_llm_targeting',
            'chroma_reset_post_schema',
            'chroma_apply_schema_fix',
            'chroma_fetch_live_schema',
            'chroma_sync_schema_to_builder',
            'chroma_schema_cleanup_scan',
            'chroma_schema_cleanup_execute',
            'chroma_save_llm_settings',
            'chroma_test_llm_connection',
            'chroma_generate_schema',
            'chroma_generate_llm_targeting',
            'chroma_generate_general_seo_meta',
            'chroma_translate_text',
            'chroma_fetch_available_models',
            'chroma_scan_theme_strings',
            'chroma_save_string_translations',
            'chroma_bulk_translate_strings',
            'chroma_export_po',
            'chroma_debug_meta',
            'chroma_save_sitemap_urls',
            'chroma_parse_sitemap_urls',
            'chroma_validate_url',
            'chroma_clear_validation_cache',
            'chroma_save_validator_setting',
            'chroma_run_link_analysis',
            'chroma_link_equity_ai_preview',
            'chroma_link_equity_ai_apply',
            'chroma_combo_ai_generate',
            'chroma_combo_ai_bulk_generate',
            'chroma_combo_bulk_status',
            'chroma_combo_save_data',
            'chroma_combo_get_data',
            'chroma_combo_ai_translate',
            'chroma_combo_ai_bulk_translate',
            'chroma_bulk_reset_schema',
            'chroma_bulk_reset_faq',
            'chroma_analyze_image',
            'chroma_compare_competitor',
            'chroma_save_breadcrumb_settings',
        ];

        $out = [];
        foreach ($catalog as $action) {
            $out[$action] = [
                'implemented' => in_array($action, $implemented, true),
                'native_ajax_hook_present' => has_action('wp_ajax_' . $action) !== false,
            ];
        }

        return $out;
    }

    public static function portal_post_types(): array
    {
        return [
            'cp_lesson_plan',
            'cp_meal_plan',
            'cp_resource',
            'cp_form',
            'cp_announcement',
            'cp_event',
            'cp_family',
            'cp_home_activity',
        ];
    }

    public static function portal_taxonomies(): array
    {
        return [
            'portal_school',
            'portal_year',
            'portal_month',
            'portal_quarter',
            'portal_category',
            'portal_classroom',
        ];
    }

    public static function portal_meta_fields(): array
    {
        return [
            '_cp_pdf_file_id',
            '_cp_priority',
            '_cp_event_date',
            '_cp_pin_hash',
            '_cp_pin_simple_hash',
        ];
    }

    public static function school_meta_fields(): array
    {
        return [
            '_chroma_school_config',
            '_chroma_school_director_email',
            '_chroma_school_newsletter',
            '_chroma_school_eom',
            '_chroma_school_announcements',
            '_chroma_school_today',
            '_chroma_school_qr',
            '_chroma_school_menu',
            '_chroma_school_slideshow',
            '_chroma_school_youtube',
            '_chroma_school_slideshow_title',
            '_chroma_school_welcome_override',
            '_chroma_school_chroma_cares',
            '_chroma_school_celebrations',
            '_chroma_school_music_url',
            '_chroma_school_last_updated',
        ];
    }

    public static function school_content_keys(): array
    {
        return [
            'newsletter',
            'eom',
            'announcements',
            'today',
            'qr',
            'menu',
            'slideshow',
            'youtube',
            'slideshow_title',
            'welcome_override',
            'chroma_cares',
            'celebrations',
            'music_url',
        ];
    }

    public static function school_options(): array
    {
        return [
            'chroma_global_cares',
            'chroma_global_alert',
            'chroma_google_client_id',
        ];
    }

    public static function form_groups(): array
    {
        return [
            'contact' => [
                'fields' => [
                    'chroma_contact_fields',
                    'chroma_contact_webhook_url',
                    'chroma_contact_email_recipient',
                    'chroma_contact_form_id',
                    'chroma_contact_form_height',
                    'chroma_contact_form_name',
                    'chroma_contact_lazy_load',
                    'chroma_contact_lazy_delay',
                ],
            ],
            'career' => [
                'fields' => [
                    'chroma_career_fields',
                    'chroma_career_webhook_url',
                    'chroma_career_email_recipient',
                    'chroma_career_form_id',
                    'chroma_career_form_height',
                    'chroma_career_form_name',
                    'chroma_career_lazy_load',
                    'chroma_career_lazy_delay',
                ],
            ],
            'acquisition' => [
                'aliases' => ['acquisitions'],
                'fields' => [
                    'chroma_acquisition_fields',
                    'chroma_acquisition_webhook_url',
                    'chroma_acquisition_email_recipient',
                ],
            ],
            'tour' => [
                'fields' => [
                    'chroma_tour_form_id',
                    'chroma_tour_form_height',
                    'chroma_tour_form_name',
                    'chroma_tour_lazy_load',
                    'chroma_tour_lazy_delay',
                ],
            ],
            'lead-log' => [
                'aliases' => ['leads'],
                'fields' => [
                    'chroma_lead_log_webhook_url',
                ],
            ],
        ];
    }

    public static function lead_meta_fields(): array
    {
        return [
            'lead_type',
            'lead_name',
            'lead_email',
            'lead_phone',
            'lead_location',
            'lead_payload',
            '_chroma_webhook_sent',
        ];
    }

    public static function secret_option_keys(): array
    {
        return [
            'chroma_openai_api_key',
            'chroma_google_places_api_key',
            'chroma_contact_webhook_url',
            'chroma_career_webhook_url',
            'chroma_acquisition_webhook_url',
            'chroma_lead_log_webhook_url',
            'chroma_indexnow_key',
        ];
    }

    public static function flatten_group_fields(array $groups): array
    {
        $fields = [];
        foreach ($groups as $group) {
            $fields = array_merge($fields, (array) ($group['fields'] ?? []));
        }
        $fields = array_values(array_unique($fields));
        sort($fields);
        return $fields;
    }

    private static function privacy_page_fields(): array
    {
        $fields = ['privacy_last_updated'];
        for ($i = 1; $i <= 12; $i++) {
            $fields[] = 'privacy_section' . $i . '_title';
            $fields[] = 'privacy_section' . $i . '_content';
        }
        $fields[] = '_privacy_defaults_seeded';
        return $fields;
    }
}

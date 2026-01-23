<?php
/**
 * Advanced SEO/LLM Module - Bootstrap
 * Loads all modules and registers meta boxes / hooks
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Global to track missing files
global $chroma_missing_seo_files;
$chroma_missing_seo_files = [];

/**
 * Helper to safely load files
 */
if (!function_exists('chroma_safe_require')) {
	function chroma_safe_require($path)
	{
		global $chroma_missing_seo_files;
		if (file_exists($path)) {
			require_once $path;
			return true;
		}
		$chroma_missing_seo_files[] = basename($path);
		return false;
	}
}

/**
 * Helper function for debug logging.
 * Only logs when WP_DEBUG and WP_DEBUG_LOG are enabled.
 * 
 * @param mixed $message Message to log (string or array/object for print_r)
 * @param string $prefix Optional prefix for the log message
 */
if (!function_exists('chroma_debug_log')) {
	function chroma_debug_log($message, $prefix = 'Chroma SEO') {
		if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			$log_message = is_string($message) ? $message : print_r($message, true);
			error_log('[' . $prefix . '] ' . $log_message);
		}
	}
}

/**
 * Load Base Classes & Helpers
 */
chroma_safe_require(__DIR__ . '/class-meta-box-base.php');
chroma_safe_require(__DIR__ . '/class-field-sanitizer.php');
chroma_safe_require(__DIR__ . '/class-fallback-resolver.php');

/**
 * Load Core Classes
 */
chroma_safe_require(__DIR__ . '/class-seo-dashboard.php');
chroma_safe_require(__DIR__ . '/class-citation-datasets.php');
chroma_safe_require(__DIR__ . '/class-image-alt-automation.php');
chroma_safe_require(__DIR__ . '/class-admin-help.php');
chroma_safe_require(__DIR__ . '/class-breadcrumbs.php');
chroma_safe_require(__DIR__ . '/class-schema-types.php');
chroma_safe_require(__DIR__ . '/class-schema-validator.php');
chroma_safe_require(__DIR__ . '/class-llm-client.php');
chroma_safe_require(__DIR__ . '/class-google-places-client.php');
chroma_safe_require(__DIR__ . '/class-llm-bulk-processor.php');
chroma_safe_require(__DIR__ . '/class-translation-engine.php');
chroma_safe_require(__DIR__ . '/class-schema-quality.php');
chroma_safe_require(__DIR__ . '/class-advanced-features.php');
chroma_safe_require(__DIR__ . '/class-multilingual-manager.php');
chroma_safe_require(__DIR__ . '/class-validation-cache.php');
chroma_safe_require(__DIR__ . '/class-validation-logger.php');

// Load Admin UI
chroma_safe_require(__DIR__ . '/admin/class-llm-admin-settings.php');
chroma_safe_require(__DIR__ . '/admin/class-schema-inspector.php');
chroma_safe_require(__DIR__ . '/admin/class-theme-translator.php');
chroma_safe_require(__DIR__ . '/admin/class-content-inspector.php');
chroma_safe_require(__DIR__ . '/admin/class-hreflang-auditor.php');
chroma_safe_require(__DIR__ . '/class-sitemap-integrator.php');
chroma_safe_require(__DIR__ . '/class-cli-commands.php');
chroma_safe_require(__DIR__ . '/class-llms-txt.php');
chroma_safe_require(__DIR__ . '/class-translation-api.php');
chroma_safe_require(__DIR__ . '/class-homepage-translation-admin.php');
chroma_safe_require(__DIR__ . '/class-careers-api.php');
chroma_safe_require(__DIR__ . '/class-career-sync.php');

// Load Theme Schema Compatibility (migrated from seo-engine.php)
chroma_safe_require(__DIR__ . '/class-schema-registry.php');
chroma_safe_require(__DIR__ . '/class-theme-schema-compat.php');

// Load SEO Automations
chroma_safe_require(__DIR__ . '/seo-automations/bootstrap.php');

// Load Editor Metabox
chroma_safe_require(__DIR__ . '/meta-boxes/class-schema-editor-metabox.php');

// Initialize LLM Client
global $chroma_llm_client;
$chroma_llm_client = new Chroma_LLM_Client();

/**
 * Load Meta Boxes
 */
$meta_boxes = [
	'class-location-events.php',
	'class-location-howto.php',
	'class-general-llm-context.php', // Renamed from location-llm-context
	'class-general-llm-prompt.php',  // Renamed from location-llm-prompt
	'class-location-media.php',
	'class-location-pricing.php',
	'class-location-reviews.php',
	'class-location-service-area.php',
	'class-program-relationships.php',
	'class-universal-faq.php',
	'class-hreflang-options.php',
	'class-city-landing-meta.php',
	'class-location-citation-facts.php',
	'class-post-newsroom.php',
	'class-location-advanced-schema.php', // Tier 5: License, CID, Open House, Event Venue
	'class-spanish-content.php'
];

foreach ($meta_boxes as $file) {
	// Try loading new name first, then fallback to old name if not renamed yet (during transition)
	if (!chroma_safe_require(__DIR__ . '/meta-boxes/' . $file)) {
		// Fallback for transition period
		$old_file = str_replace('general-', 'location-', $file);
		if (file_exists(__DIR__ . '/meta-boxes/' . $old_file)) {
			require_once __DIR__ . '/meta-boxes/' . $old_file;
		}
	}
}

/**
 * Load Endpoints
 */
chroma_safe_require(__DIR__ . '/endpoints/kml-endpoint.php');

/**
 * Load Schema Builders
 */
$schema_builders = [
	'class-event-builder.php',
	'class-howto-builder.php',
	'class-llm-context-builder.php',
	'class-schema-injector.php',
	'class-service-area-builder.php',
	'class-universal-faq-builder.php',
	'class-page-type-builder.php',
	'class-archive-itemlist-builder.php',
	'class-job-posting-builder.php',
	'class-course-builder.php',
	'class-article-builder.php',
	'class-special-announcement-builder.php',
	'class-learning-resource-builder.php'
];

foreach ($schema_builders as $file) {
	chroma_safe_require(__DIR__ . '/schema-builders/' . $file);
}

/**
 * Initialize Modules
 */
if (!function_exists('chroma_advanced_seo_init')) {
function chroma_advanced_seo_init()
{
	// Core Modules
	if (class_exists('Chroma_SEO_Dashboard'))
		(new Chroma_SEO_Dashboard())->init();
	if (class_exists('Chroma_Citation_Datasets'))
		(new Chroma_Citation_Datasets())->init();
	if (class_exists('Chroma_Image_Alt_Automation'))
		(new Chroma_Image_Alt_Automation())->init();
	if (class_exists('Chroma_Admin_Help'))
		(new Chroma_Admin_Help())->init();
	if (class_exists('Chroma_Breadcrumbs'))
		(new Chroma_Breadcrumbs())->init();
	if (class_exists('Chroma_Multilingual_Manager'))
		(new Chroma_Multilingual_Manager())->init();
	if (class_exists('Chroma_Translation_Engine'))
		Chroma_Translation_Engine::init();
	if (class_exists('Chroma_Theme_Translator'))
		(new Chroma_Theme_Translator())->init();
	if (class_exists('Chroma_Content_Inspector'))
		(new Chroma_Content_Inspector())->init();
	if (class_exists('Chroma_Sitemap_Integrator'))
		(new Chroma_Sitemap_Integrator())->init();
	if (class_exists('Chroma_Hreflang_Auditor'))
		(new Chroma_Hreflang_Auditor())->init();
	if (class_exists('Chroma_Translation_API'))
		(new Chroma_Translation_API())->init();
    if (class_exists('Chroma_LLMs_Txt_Generator'))
        (new Chroma_LLMs_Txt_Generator())->init();
    if (class_exists('Chroma_Validation_Logger'))
        (new Chroma_Validation_Logger())->init();
    if (class_exists('Chroma_Career_Sync'))
        Chroma_Career_Sync::init();

	// Meta Boxes
	$meta_classes = [
		'Chroma_Location_Citation_Facts',
		'Chroma_Location_Events',
		'Chroma_Location_HowTo',
		'Chroma_General_LLM_Context', // Renamed
		'Chroma_General_LLM_Prompt',  // Renamed
		'Chroma_Location_Media',
		'Chroma_Location_Pricing',
		'Chroma_Location_Reviews',
		'Chroma_Location_Service_Area',
		'Chroma_Program_Relationships',
		'Chroma_Universal_FAQ',
		'Chroma_Hreflang_Options',
		'Chroma_City_Landing_Meta',
		'Chroma_Post_Newsroom',
		'Chroma_Location_Advanced_Schema', // Tier 5: License, CID, Open House, Event Venue
		'Chroma_Spanish_Content_Meta_Box'
	];

	// Fallback for class names if files haven't been updated yet
	if (!class_exists('Chroma_General_LLM_Context') && class_exists('Chroma_Location_LLM_Context')) {
		$meta_classes[] = 'Chroma_Location_LLM_Context';
	}
	if (!class_exists('Chroma_General_LLM_Prompt') && class_exists('Chroma_Location_LLM_Prompt')) {
		$meta_classes[] = 'Chroma_Location_LLM_Prompt';
	}

	foreach ($meta_classes as $class) {
		if (class_exists($class)) {
			(new $class())->register();
		}
	}

	// Schema Builders (Hooks)
	if (class_exists('Chroma_Event_Schema_Builder'))
		add_action('wp_head', ['Chroma_Event_Schema_Builder', 'output']);
	if (class_exists('Chroma_HowTo_Schema_Builder'))
		add_action('wp_head', ['Chroma_HowTo_Schema_Builder', 'output']);
	if (class_exists('Chroma_Schema_Injector'))
		add_action('wp_head', ['Chroma_Schema_Injector', 'output_person_schema']);
	if (class_exists('Chroma_Schema_Injector'))
		add_action('wp_head', ['Chroma_Schema_Injector', 'output_job_posting_schema']);
	if (class_exists('Chroma_Schema_Injector'))
		add_action('wp_head', ['Chroma_Schema_Injector', 'output_course_schema']);
	if (class_exists('Chroma_Universal_FAQ_Builder'))
		add_action('wp_head', ['Chroma_Universal_FAQ_Builder', 'output']);
	if (class_exists('Chroma_Page_Type_Builder'))
		add_action('wp_head', ['Chroma_Page_Type_Builder', 'output']);
	if (class_exists('Chroma_Schema_Injector'))
		add_action('wp_head', ['Chroma_Schema_Injector', 'output_website_schema']);
	if (class_exists('Chroma_Archive_ItemList_Builder'))
		add_action('wp_head', ['Chroma_Archive_ItemList_Builder', 'output']);
	if (class_exists('Chroma_Article_Builder'))
		add_action('wp_head', ['Chroma_Article_Builder', 'output']);
	
	// Advanced Schema (New Builders)
	if (class_exists('Chroma_Special_Announcement_Builder'))
		add_action('wp_head', ['Chroma_Special_Announcement_Builder', 'output']);
	if (class_exists('Chroma_Learning_Resource_Builder'))
		add_action('wp_head', ['Chroma_Learning_Resource_Builder', 'output']);
	
	// Modular Schemas from Schema Builder (stored in _chroma_post_schemas meta)
	if (class_exists('Chroma_Schema_Injector'))
		add_action('wp_head', ['Chroma_Schema_Injector', 'output_modular_schemas'], 20);

	// Flush Rewrite Rules if KML rule is missing (One-time check)
	if (get_option('chroma_seo_flush_rewrite_v6') !== 'done') {
		flush_rewrite_rules();
		update_option('chroma_seo_flush_rewrite_v6', 'done');
	}
}
add_action('init', 'chroma_advanced_seo_init');
}

/**
 * Admin Assets
 */
if (!function_exists('chroma_advanced_seo_admin_assets')) {
function chroma_advanced_seo_admin_assets($hook)
{
	// Only load on SEO Dashboard or Post Edit screens
	$screen = get_current_screen();
	$allowed_post_types = ['location', 'program', 'page', 'post', 'city'];

	$is_dashboard = (isset($_GET['page']) && $_GET['page'] === 'chroma-seo-dashboard');
	$is_post_edit = ($hook === 'post.php' || $hook === 'post-new.php');
	$is_allowed_type = ($screen && in_array($screen->post_type, $allowed_post_types));

	if (!$is_dashboard && !($is_post_edit && $is_allowed_type)) {
		return;
	}

	?>
	<style>
		.chroma-seo-meta-box {
			background: #fff;
		}

		.chroma-section-title {
			font-size: 14px;
			font-weight: 600;
			margin: 15px 0 10px;
			border-bottom: 1px solid #eee;
			padding: 10px 0;
		}

		.chroma-field-wrapper {
			margin-bottom: 20px;
		}

		.chroma-field-wrapper label {
			display: block;
			font-weight: 600;
			margin-bottom: 5px;
		}

		.chroma-field-wrapper .description {
			margin-top: 5px;
			font-style: normal;
			color: #666;
		}

		.chroma-field-wrapper .fallback-notice {
			color: #2271b1;
			font-style: italic;
		}

		.chroma-repeater-field {
			border: 1px solid #ddd;
			padding: 15px;
			background: #f9f9f9;
		}

		.chroma-repeater-items {
			margin-bottom: 10px;
		}

		.chroma-repeater-item {
			display: flex;
			gap: 10px;
			margin-bottom: 8px;
			align-items: center;
		}

		.chroma-repeater-item input {
			flex: 1;
		}

		.chroma-remove-item {
			color: #b32d2e;
		}
	</style>
	<script>
		jQuery(document).ready(function ($) {
			// Repeater Add
			$(document).on('click', '.chroma-add-item', function (e) {
				e.preventDefault();
				var $wrapper = $(this).closest('.chroma-repeater-field');
				var $items = $wrapper.find('.chroma-repeater-items');
				var $clone = $items.find('.chroma-repeater-item').first().clone();
				$clone.find('input, textarea').val('');
				$items.append($clone);
			});

			// Repeater Remove
			$(document).on('click', '.chroma-remove-item', function (e) {
				e.preventDefault();
				if ($(this).closest('.chroma-repeater-items').find('.chroma-repeater-item').length > 1) {
					$(this).closest('.chroma-repeater-item').remove();
				} else {
					$(this).closest('.chroma-repeater-item').find('input, textarea').val('');
				}
			});
		});
	</script>
	<?php
}

add_action('admin_enqueue_scripts', 'chroma_advanced_seo_admin_assets');
} // Close function_exists for chroma_advanced_seo_admin_assets

/**
 * Admin Notice for Missing Files
 */
if (!function_exists('chroma_seo_missing_files_notice')) {
function chroma_seo_missing_files_notice()
{
	global $chroma_missing_seo_files;
	if (!empty($chroma_missing_seo_files) && current_user_can('manage_options')) {
		echo '<div class="notice notice-warning is-dismissible">';
		echo '<p><strong>Advanced SEO Module Warning:</strong> The following files are missing and could not be loaded:</p>';
		echo '<ul>';
		foreach ($chroma_missing_seo_files as $file) {
			echo '<li>' . esc_html($file) . '</li>';
		}
		echo '</ul>';
		echo '<p>Please ensure all files are uploaded to <code>inc/advanced-seo-llm/</code>.</p>';
		echo '</div>';
	}
}
add_action('admin_notices', 'chroma_seo_missing_files_notice');
}


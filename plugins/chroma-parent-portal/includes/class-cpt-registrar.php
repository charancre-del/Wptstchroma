<?php
/**
 * Class CPT_Registrar
 * Handles registration of Custom Post Types and Taxonomies.
 */

if (!defined('ABSPATH')) {
	exit;
}

class Chroma_Portal_CPT_Registrar
{

	public function __construct()
	{
		add_action('init', [$this, 'register_cpts']);
		add_action('init', [$this, 'register_taxonomies']);

		// Admin Columns adjustments (Dynamic for all CP types)
		foreach (['cp_lesson_plan', 'cp_meal_plan', 'cp_resource', 'cp_form', 'cp_announcement'] as $post_type) {
			add_filter("manage_{$post_type}_posts_columns", [$this, 'add_admin_columns']);
			add_action("manage_{$post_type}_posts_custom_column", [$this, 'render_admin_columns'], 10, 2);
			add_filter("manage_edit-{$post_type}_sortable_columns", [$this, 'sortable_columns']);
		}
	}

	public function register_cpts()
	{
		// Lesson Plans
		register_post_type('cp_lesson_plan', [
			'labels' => [
				'name' => 'Lesson Plans',
				'singular_name' => 'Lesson Plan',
				'add_new' => 'Add New Plan',
				'all_items' => 'All Lesson Plans',
				'menu_name' => 'Portal: Lessons',
			],
			'public' => false, // Headless-ish
			'show_ui' => true,
			'show_in_rest' => true,
			'supports' => ['title'], // Minimal support, we use meta for files
			'menu_icon' => 'dashicons-welcome-learn-more',
		]);

		// Meal Plans
		register_post_type('cp_meal_plan', [
			'labels' => [
				'name' => 'Meal Plans',
				'singular_name' => 'Meal Plan',
				'menu_name' => 'Portal: Meals',
			],
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'supports' => ['title'],
			'menu_icon' => 'dashicons-carrot',
		]);

		// Resources (Policies & Procedures)
		register_post_type('cp_resource', [
			'labels' => [
				'name' => 'Resources',
				'singular_name' => 'Resource',
				'menu_name' => 'Portal: Resources',
			],
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'supports' => ['title'],
			'menu_icon' => 'dashicons-book',
		]);

		// Forms
		register_post_type('cp_form', [
			'labels' => [
				'name' => 'Forms',
				'singular_name' => 'Form',
				'menu_name' => 'Portal: Forms',
			],
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'supports' => ['title'],
			'menu_icon' => 'dashicons-clipboard',
		]);

		// Announcements
		register_post_type('cp_announcement', [
			'labels' => [
				'name' => 'Announcements',
				'singular_name' => 'Announcement',
				'menu_name' => 'Portal: News',
			],
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'supports' => ['title', 'editor'], // Editor for text content
			'menu_icon' => 'dashicons-megaphone',
		]);

		// Events
		register_post_type('cp_event', [
			'labels' => [
				'name' => 'Events',
				'singular_name' => 'Event',
				'menu_name' => 'Portal: Events',
			],
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'supports' => ['title', 'editor'],
			'menu_icon' => 'dashicons-calendar-alt',
		]);

		// Families (for PINs)
		register_post_type('cp_family', [
			'labels' => [
				'name' => 'Families',
				'singular_name' => 'Family',
				'menu_name' => 'Portal: Families',
			],
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'supports' => ['title'], // Title = "Smith Family"
			'menu_icon' => 'dashicons-groups',
		]);
	}

	public function register_taxonomies()
	{
		// Shared School (Tag-like but hierarchical for consistency)
		register_taxonomy('portal_school', ['cp_lesson_plan', 'cp_meal_plan', 'cp_resource', 'cp_form', 'cp_announcement', 'cp_event'], [
			'labels' => ['name' => 'Schools', 'singular_name' => 'School'],
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'hierarchical' => true,
			'show_admin_column' => true,
		]);

		// Shared Year
		register_taxonomy('portal_year', ['cp_lesson_plan', 'cp_meal_plan', 'cp_resource', 'cp_form', 'cp_announcement', 'cp_event'], [
			'labels' => ['name' => 'Years', 'singular_name' => 'Year'],
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'hierarchical' => true,
			'show_admin_column' => true,
		]);

		// Month
		register_taxonomy('portal_month', ['cp_lesson_plan', 'cp_announcement', 'cp_event'], [
			'labels' => ['name' => 'Months', 'singular_name' => 'Month'],
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'hierarchical' => true,
			'show_admin_column' => true,
		]);

		// Quarter (for Meal Plans)
		register_taxonomy('portal_quarter', ['cp_meal_plan'], [
			'labels' => ['name' => 'Quarters', 'singular_name' => 'Quarter'],
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'hierarchical' => true,
			'show_admin_column' => true,
		]);

		// Category (for Resources/Forms)
		register_taxonomy('portal_category', ['cp_resource', 'cp_form'], [
			'labels' => ['name' => 'Categories', 'singular_name' => 'Category'],
			'public' => false,
			'show_ui' => true,
			'show_in_rest' => true,
			'hierarchical' => true,
			'show_admin_column' => true,
		]);
	}

	// --- Admin Columns Helpers ---

	public function add_admin_columns($columns)
	{
		$columns['file'] = 'File (PDF)'; // Placeholder column logic
		return $columns;
	}

	public function render_admin_columns($column, $post_id)
	{
		if ($column === 'file') {
			$file_id = get_post_meta($post_id, '_cp_pdf_file_id', true);
			if ($file_id) {
				echo '<a href="' . wp_get_attachment_url($file_id) . '" target="_blank">View PDF</a>';
			} else {
				echo '<span style="color:red;">No File</span>';
			}
		}
	}

	public function sortable_columns($columns)
	{
		// $columns['portal_year'] = 'portal_year';
		return $columns;
	}
}

new Chroma_Portal_CPT_Registrar();

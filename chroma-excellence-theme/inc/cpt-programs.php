<?php
/**
 * Custom Post Type: Programs
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Register Program CPT
 */
function chroma_register_program_cpt()
{
	$labels = array(
		'name' => _x('Programs', 'Post Type General Name', 'chroma-excellence'),
		'singular_name' => _x('Program', 'Post Type Singular Name', 'chroma-excellence'),
		'menu_name' => __('Programs', 'chroma-excellence'),
		'all_items' => __('All Programs', 'chroma-excellence'),
		'add_new_item' => __('Add New Program', 'chroma-excellence'),
		'edit_item' => __('Edit Program', 'chroma-excellence'),
		'view_item' => __('View Program', 'chroma-excellence'),
	);

	// Force 'programs' as the slug. The customizer option is no longer used for the base.
	$program_slug = 'programs';

	$args = array(
		'label' => __('Program', 'chroma-excellence'),
		'labels' => $labels,
		'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
		'public' => true,
		'menu_position' => 20,
		'menu_icon' => 'dashicons-welcome-learn-more',
		'has_archive' => $program_slug,
		'rewrite' => array('slug' => $program_slug, 'with_front' => false),
		'show_in_rest' => true,
	);

	register_post_type('program', $args);

}
add_action('init', 'chroma_register_program_cpt', 0);

/**
 * Refresh Program rewrites from a safe admin context instead of live requests.
 *
 * Flushing rewrites during frontend/REST bootstrap can trigger expensive work
 * on every request if the version flag is missing or cannot be updated.
 */
function chroma_maybe_flush_program_rewrites_admin()
{
	if (!is_admin() || wp_doing_ajax() || wp_doing_cron()) {
		return;
	}

	if (get_option('chroma_program_rewrite_flushed') === 'v2') {
		return;
	}

	flush_rewrite_rules(false);
	update_option('chroma_program_rewrite_flushed', 'v2');
}
add_action('admin_init', 'chroma_maybe_flush_program_rewrites_admin', 20);

/**
 * Preserve the public Kindergarten program URL when WordPress has a duplicate slug.
 *
 * If a page or previously-created post reserved "kindergarten", WordPress keeps
 * the actual program at "kindergarten-1". Keep the frontend URL at the clean
 * /programs/kindergarten/ route while mapping the request to the real post.
 */
function chroma_get_kindergarten_program_alias_post()
{
	$program = get_page_by_path('kindergarten-1', OBJECT, 'program');
	if (!$program || strcasecmp((string) get_the_title($program), 'Kindergarten') !== 0) {
		return null;
	}

	return $program;
}

function chroma_filter_kindergarten_program_permalink($post_link, $post)
{
	if (!$post || $post->post_type !== 'program' || $post->post_name !== 'kindergarten-1') {
		return $post_link;
	}

	if (strcasecmp((string) get_the_title($post), 'Kindergarten') !== 0) {
		return $post_link;
	}

	$base_slug = function_exists('chroma_get_program_base_slug') ? chroma_get_program_base_slug() : 'programs';
	return home_url(user_trailingslashit(trim($base_slug, '/') . '/kindergarten'));
}
add_filter('post_type_link', 'chroma_filter_kindergarten_program_permalink', 10, 2);

function chroma_map_kindergarten_program_request($query_vars)
{
	$base_slug = function_exists('chroma_get_program_base_slug') ? chroma_get_program_base_slug() : 'programs';
	$requested_name = isset($query_vars['name']) ? (string) $query_vars['name'] : '';
	$requested_type = isset($query_vars['post_type']) ? (string) $query_vars['post_type'] : '';

	if ($requested_type === 'program' && $requested_name === 'kindergarten') {
		$program = chroma_get_kindergarten_program_alias_post();
		if ($program) {
			$query_vars['name'] = $program->post_name;
		}
	}

	$pagename = isset($query_vars['pagename']) ? trim((string) $query_vars['pagename'], '/') : '';
	if ($pagename === trim($base_slug . '/kindergarten', '/')) {
		$program = chroma_get_kindergarten_program_alias_post();
		if ($program) {
			unset($query_vars['pagename'], $query_vars['page']);
			$query_vars['post_type'] = 'program';
			$query_vars['name'] = $program->post_name;
		}
	}

	return $query_vars;
}
add_filter('request', 'chroma_map_kindergarten_program_request', 1);

function chroma_parse_kindergarten_program_alias_request($wp)
{
	if (!chroma_is_kindergarten_program_alias_request()) {
		return;
	}

	$program = chroma_get_kindergarten_program_alias_post();
	if (!$program) {
		return;
	}

	$wp->query_vars = array_merge($wp->query_vars, array(
		'post_type' => 'program',
		'program' => $program->post_name,
		'name' => $program->post_name,
	));

	unset($wp->query_vars['pagename'], $wp->query_vars['page'], $wp->query_vars['error']);
}
add_action('parse_request', 'chroma_parse_kindergarten_program_alias_request', 1);

function chroma_is_kindergarten_program_alias_request()
{
	if (!isset($_SERVER['REQUEST_URI'])) {
		return false;
	}

	$path = trim((string) wp_parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
	if ($path === '') {
		return false;
	}

	$base_slug = function_exists('chroma_get_program_base_slug') ? chroma_get_program_base_slug() : 'programs';
	$alias_paths = array(
		trim($base_slug . '/kindergarten', '/'),
		trim('es/' . $base_slug . '/kindergarten', '/'),
	);

	return in_array($path, $alias_paths, true);
}

function chroma_preserve_kindergarten_program_alias_redirects($redirect_url = false)
{
	if (chroma_is_kindergarten_program_alias_request() && chroma_get_kindergarten_program_alias_post()) {
		return false;
	}

	return $redirect_url;
}
add_filter('redirect_canonical', 'chroma_preserve_kindergarten_program_alias_redirects', 0, 1);
add_filter('old_slug_redirect_url', 'chroma_preserve_kindergarten_program_alias_redirects', 0, 1);

function chroma_disable_custom_canonical_redirect_for_kindergarten_alias($pre_option, $option, $default)
{
	if (chroma_is_kindergarten_program_alias_request() && chroma_get_kindergarten_program_alias_post()) {
		return false;
	}

	return $pre_option;
}
add_filter('pre_option_chroma_seo_redirect_canonical', 'chroma_disable_custom_canonical_redirect_for_kindergarten_alias', 9, 3);

function chroma_render_kindergarten_program_alias()
{
	if (!chroma_is_kindergarten_program_alias_request()) {
		return;
	}

	$program = chroma_get_kindergarten_program_alias_post();
	if (!$program) {
		return;
	}

	global $post, $wp_query;

	$post = $program;
	setup_postdata($post);

	$wp_query->queried_object = $program;
	$wp_query->queried_object_id = (int) $program->ID;
	$wp_query->post = $program;
	$wp_query->posts = array($program);
	$wp_query->post_count = 1;
	$wp_query->found_posts = 1;
	$wp_query->max_num_pages = 1;
	$wp_query->is_single = true;
	$wp_query->is_singular = true;
	$wp_query->is_404 = false;
	$wp_query->is_page = false;
	$wp_query->is_archive = false;
	$wp_query->is_post_type_archive = false;

	status_header(200);

	$template = locate_template(array('single-program.php', 'single.php', 'index.php'));
	if ($template !== '') {
		include $template;
		exit;
	}
}
add_action('template_redirect', 'chroma_render_kindergarten_program_alias', -10000);

function chroma_redirect_legacy_kindergarten_program_slug()
{
	if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
		return;
	}

	$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
	if (!in_array($method, array('GET', 'HEAD'), true)) {
		return;
	}

	$request_path = trim((string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
	$base_slug = function_exists('chroma_get_program_base_slug') ? chroma_get_program_base_slug() : 'programs';

	if ($request_path !== trim($base_slug . '/kindergarten-1', '/')) {
		return;
	}

	if (!chroma_get_kindergarten_program_alias_post()) {
		return;
	}

	wp_safe_redirect(home_url(user_trailingslashit(trim($base_slug, '/') . '/kindergarten')), 301);
	exit;
}
add_action('template_redirect', 'chroma_redirect_legacy_kindergarten_program_slug', -1000);

/**
 * Register Program-Location Relationship Taxonomy
 * Optimized for high-performance relationship queries vs REGEXP meta queries
 */
function chroma_register_program_location_taxonomy()
{
	register_taxonomy(
		'program_location',
		array('program'),
		array(
			'hierarchical' => false,
			'public' => false,
			'show_ui' => false, // Managed via meta box
			'show_admin_column' => false,
			'show_in_nav_menus' => false,
			'show_tagcloud' => false,
			'rewrite' => false,
			'query_var' => false,
		)
	);
}
add_action('init', 'chroma_register_program_location_taxonomy', 0);

/**
 * Add admin columns for Programs
 */
function chroma_program_admin_columns($columns)
{
	$new_columns = array();
	$new_columns['cb'] = $columns['cb'];
	$new_columns['title'] = $columns['title'];
	$new_columns['age_range'] = __('Age Range', 'chroma-excellence');
	$new_columns['locations'] = __('Locations', 'chroma-excellence');
	$new_columns['date'] = $columns['date'];

	return $new_columns;
}
add_filter('manage_program_posts_columns', 'chroma_program_admin_columns');

/**
 * Populate admin columns
 */
function chroma_program_admin_column_content($column, $post_id)
{
	switch ($column) {
		case 'age_range':
			$age_range = get_post_meta($post_id, 'program_age_range', true);
			echo $age_range ? esc_html($age_range) : '—';
			break;

		case 'locations':
			$locations = get_post_meta($post_id, 'program_locations', true);
			if ($locations) {
				$count = count($locations);
				echo esc_html($count . ' location' . ($count > 1 ? 's' : ''));
			} else {
				echo '—';
			}
			break;
	}
}
add_action('manage_program_posts_custom_column', 'chroma_program_admin_column_content', 10, 2);

/**
 * Custom title placeholder
 */
function chroma_program_title_placeholder($title)
{
	$screen = get_current_screen();
	if ('program' === $screen->post_type) {
		$title = __('e.g., Infant Care', 'chroma-excellence');
	}
	return $title;
}
add_filter('enter_title_here', 'chroma_program_title_placeholder');

/**
 * Program-specific Prismpath defaults used when a program has not yet saved
 * chart meta. Keeps frontend charts useful while still allowing admin edits.
 */
function chroma_program_prism_default_values($program_id = 0)
{
	$slug = $program_id ? sanitize_title(get_post_field('post_name', $program_id)) : '';
	$title = $program_id ? strtolower((string) get_the_title($program_id)) : '';
	$defaults = array(
		'infant-care' => array(90, 90, 40, 15, 40),
		'infant' => array(90, 90, 40, 15, 40),
		'toddlers' => array(85, 75, 65, 30, 70),
		'toddler' => array(85, 75, 65, 30, 70),
		'preschool' => array(75, 65, 70, 55, 80),
		'pre-k-prep' => array(65, 60, 75, 75, 70),
		'pre-k-ga-pre-k' => array(60, 60, 80, 90, 70),
		'ga-pre-k' => array(60, 60, 80, 90, 70),
		'prek' => array(60, 60, 80, 90, 70),
		'kindergarten' => array(60, 70, 85, 95, 80),
		'rising-pre-k' => array(60, 90, 95, 60, 70),
		'rising-prek' => array(60, 90, 95, 60, 70),
		'rising-kindergarten' => array(50, 75, 80, 95, 60),
		'schoolagers' => array(50, 70, 85, 75, 80),
		'afterschool' => array(50, 70, 85, 75, 80),
		'camp' => array(85, 75, 85, 55, 90),
		'parents-day-out' => array(75, 85, 70, 35, 80),
	);

	if (isset($defaults[$slug])) {
		return $defaults[$slug];
	}

	foreach ($defaults as $key => $values) {
		if ($title && false !== strpos($title, str_replace('-', ' ', $key))) {
			return $values;
		}
	}

	return array(50, 50, 50, 50, 50);
}

function chroma_program_prism_chart_values($program_id)
{
	$defaults = chroma_program_prism_default_values($program_id);
	$meta_keys = array(
		'program_prism_physical',
		'program_prism_emotional',
		'program_prism_social',
		'program_prism_academic',
		'program_prism_creative',
	);
	$values = array();
	$raw_values = array();

	foreach ($meta_keys as $index => $meta_key) {
		$raw_value = get_post_meta($program_id, $meta_key, true);
		$raw_values[] = $raw_value;
		$values[] = ('' === trim((string) $raw_value))
			? $defaults[$index]
			: max(0, min(100, absint($raw_value)));
	}

	$slug = sanitize_title(get_post_field('post_name', $program_id));
	$uses_placeholder_values = in_array($slug, array('rising-pre-k', 'rising-kindergarten'), true)
		&& array(50, 50, 50, 50, 50) === $values
		&& array(50, 50, 50, 50, 50) !== $defaults
		&& count(array_filter($raw_values, static function ($value) {
			return '' !== trim((string) $value);
		})) === count($meta_keys);

	if ($uses_placeholder_values) {
		return $defaults;
	}

	return $values;
}

/**
 * Register meta fields for Program anchors and SEO intro
 */
function chroma_register_program_meta()
{
	$meta_args = array(
		'object_subtype' => 'program',
		'type' => 'string',
		'single' => true,
		'show_in_rest' => true,
		'auth_callback' => function () {
			return current_user_can('edit_posts');
		},
	);

	register_post_meta(
		'program',
		'program_anchor_slug',
		array_merge(
			$meta_args,
			array(
				'sanitize_callback' => 'sanitize_title',
				'default' => '',
			)
		)
	);

	register_post_meta(
		'program',
		'program_seo_heading',
		array_merge(
			$meta_args,
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		)
	);

	register_post_meta(
		'program',
		'program_seo_summary',
		array_merge(
			$meta_args,
			array(
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		)
	);

	register_post_meta(
		'program',
		'program_seo_highlights',
		array_merge(
			$meta_args,
			array(
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		)
	);

	register_post_meta(
		'program',
		'program_meta_title',
		array_merge(
			$meta_args,
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		)
	);

	register_post_meta(
		'program',
		'program_meta_description',
		array_merge(
			$meta_args,
			array(
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		)
	);

	register_post_meta(
		'program',
		'program_faq_items',
		array_merge(
			$meta_args,
			array(
				'sanitize_callback' => 'sanitize_textarea_field',
			)
		)
	);

	register_post_meta(
		'program',
		'program_lesson_plan_file',
		array_merge(
			$meta_args,
			array(
				'sanitize_callback' => 'esc_url_raw',
			)
		)
	);
}
add_action('init', 'chroma_register_program_meta');

/**
 * Add meta box for anchor and SEO intro fields
 */
function chroma_program_meta_box()
{
	add_meta_box(
		'chroma-program-anchor-seo',
		__('Program Anchor & SEO Intro', 'chroma-excellence'),
		'chroma_program_meta_box_render',
		'program',
		'side',
		'default'
	);
}
add_action('add_meta_boxes', 'chroma_program_meta_box');

/**
 * Render the meta box fields
 */
function chroma_program_meta_box_render($post)
{
	wp_nonce_field('chroma_program_meta_nonce', 'chroma_program_meta_nonce_field');

	$anchor = get_post_meta($post->ID, 'program_anchor_slug', true);
	$heading = get_post_meta($post->ID, 'program_seo_heading', true);
	$summary = get_post_meta($post->ID, 'program_seo_summary', true);
	$highlights = get_post_meta($post->ID, 'program_seo_highlights', true);
	$meta_title = get_post_meta($post->ID, 'program_meta_title', true);
	$meta_desc = get_post_meta($post->ID, 'program_meta_description', true);
	$faq_items = get_post_meta($post->ID, 'program_faq_items', true);
	?>
	<p>
		<label for="program_anchor_slug"
			class="screen-reader-text"><?php esc_html_e('Program Anchor', 'chroma-excellence'); ?></label>
		<input type="text" id="program_anchor_slug" name="program_anchor_slug" value="<?php echo esc_attr($anchor); ?>"
			class="widefat" placeholder="<?php esc_attr_e('e.g., infant', 'chroma-excellence'); ?>" />
		<small><?php esc_html_e('Used for #anchors and homepage wizard links. Defaults to the slug.', 'chroma-excellence'); ?></small>
	</p>
	<p>
		<label for="program_seo_heading"
			class="screen-reader-text"><?php esc_html_e('SEO Heading', 'chroma-excellence'); ?></label>
		<input type="text" id="program_seo_heading" name="program_seo_heading" value="<?php echo esc_attr($heading); ?>"
			class="widefat" placeholder="<?php esc_attr_e('Program intro heading', 'chroma-excellence'); ?>" />
	</p>
	<p>
		<label for="program_seo_summary"
			class="screen-reader-text"><?php esc_html_e('SEO Summary', 'chroma-excellence'); ?></label>
		<textarea id="program_seo_summary" name="program_seo_summary" class="widefat" rows="3"
			placeholder="<?php esc_attr_e('Short overview that lives above the card', 'chroma-excellence'); ?>"><?php echo esc_textarea($summary); ?></textarea>
	</p>
	<p>
		<label for="program_seo_highlights"
			class="screen-reader-text"><?php esc_html_e('SEO Highlights', 'chroma-excellence'); ?></label>
		<textarea id="program_seo_highlights" name="program_seo_highlights" class="widefat" rows="4"
			placeholder="<?php esc_attr_e("One bullet per line (e.g. ratios, curriculum)", 'chroma-excellence'); ?>"><?php echo esc_textarea($highlights); ?></textarea>
	</p>
	<hr />
	<p>
		<label for="program_meta_title"
			class="screen-reader-text"><?php esc_html_e('Meta Title', 'chroma-excellence'); ?></label>
		<input type="text" id="program_meta_title" name="program_meta_title" value="<?php echo esc_attr($meta_title); ?>"
			class="widefat" placeholder="<?php esc_attr_e('Custom title tag (optional)', 'chroma-excellence'); ?>" />
		<small><?php esc_html_e('Used on the program detail for search visibility.', 'chroma-excellence'); ?></small>
	</p>
	<p>
		<label for="program_meta_description"
			class="screen-reader-text"><?php esc_html_e('Meta Description', 'chroma-excellence'); ?></label>
		<textarea id="program_meta_description" name="program_meta_description" class="widefat" rows="3"
			placeholder="<?php esc_attr_e('1–2 sentence description for search snippets', 'chroma-excellence'); ?>"><?php echo esc_textarea($meta_desc); ?></textarea>
	</p>
	<p>
		<label for="program_faq_items"
			class="screen-reader-text"><?php esc_html_e('FAQ Items', 'chroma-excellence'); ?></label>
		<textarea id="program_faq_items" name="program_faq_items" class="widefat" rows="4"
			placeholder="<?php esc_attr_e('Question | Answer (one per line)', 'chroma-excellence'); ?>"><?php echo esc_textarea($faq_items); ?></textarea>
		<small><?php esc_html_e('Populate FAQ schema and on-page Q&A.', 'chroma-excellence'); ?></small>
	</p>
	<?php
}

/**
 * Save meta box fields
 */
function chroma_program_meta_box_save($post_id)
{
	if (!isset($_POST['chroma_program_meta_nonce_field']) || !wp_verify_nonce(wp_unslash($_POST['chroma_program_meta_nonce_field']), 'chroma_program_meta_nonce')) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if (isset($_POST['post_type']) && 'program' === $_POST['post_type']) {
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
	}

	$anchor = isset($_POST['program_anchor_slug']) ? sanitize_title(wp_unslash($_POST['program_anchor_slug'])) : '';
	$heading = isset($_POST['program_seo_heading']) ? sanitize_text_field(wp_unslash($_POST['program_seo_heading'])) : '';
	$summary = isset($_POST['program_seo_summary']) ? sanitize_textarea_field(wp_unslash($_POST['program_seo_summary'])) : '';
	$highlights = isset($_POST['program_seo_highlights']) ? sanitize_textarea_field(wp_unslash($_POST['program_seo_highlights'])) : '';
	$meta_title = isset($_POST['program_meta_title']) ? sanitize_text_field(wp_unslash($_POST['program_meta_title'])) : '';
	$meta_desc = isset($_POST['program_meta_description']) ? sanitize_textarea_field(wp_unslash($_POST['program_meta_description'])) : '';
	$faq_items = isset($_POST['program_faq_items']) ? sanitize_textarea_field(wp_unslash($_POST['program_faq_items'])) : '';

	update_post_meta($post_id, 'program_anchor_slug', $anchor);
	update_post_meta($post_id, 'program_seo_heading', $heading);
	update_post_meta($post_id, 'program_seo_summary', $summary);
	update_post_meta($post_id, 'program_seo_highlights', $highlights);
	update_post_meta($post_id, 'program_meta_title', $meta_title);
	update_post_meta($post_id, 'program_meta_description', $meta_desc);
	update_post_meta($post_id, 'program_faq_items', $faq_items);
}
add_action('save_post', 'chroma_program_meta_box_save');

/**
 * Add meta box for program locations
 */
function chroma_program_locations_meta_box()
{
	add_meta_box(
		'chroma-program-locations',
		__('Available at Locations', 'chroma-excellence'),
		'chroma_program_locations_meta_box_render',
		'program',
		'side',
		'default'
	);

	add_meta_box(
		'chroma-program-details',
		__('Program Details', 'chroma-excellence'),
		'chroma_program_details_meta_box_render',
		'program',
		'normal',
		'high'
	);

	add_meta_box(
		'chroma-program-single-page',
		__('Single Page Content', 'chroma-excellence'),
		'chroma_program_single_page_meta_box_render',
		'program',
		'normal',
		'default'
	);
}
add_action('add_meta_boxes', 'chroma_program_locations_meta_box');

/**
 * Render program locations meta box
 */
function chroma_program_locations_meta_box_render($post)
{
	wp_nonce_field('chroma_program_locations_nonce', 'chroma_program_locations_nonce_field');

	// Get all locations
	$all_locations = get_posts(array(
		'post_type' => 'location',
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC',
	));

	// Get currently selected locations
	$selected_locations = get_post_meta($post->ID, 'program_locations', true);
	if (!is_array($selected_locations)) {
		$selected_locations = array();
	}
	?>
	<p><?php _e('Select the locations where this program is available:', 'chroma-excellence'); ?></p>
	<p style="margin-bottom: 10px;">
		<button type="button" id="chroma-toggle-all-locations" class="button button-secondary" style="margin-bottom: 5px;">
			<?php _e('Select All / Deselect All', 'chroma-excellence'); ?>
		</button>
	</p>
	<div id="chroma-locations-list"
		style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
		<?php if (!empty($all_locations)): ?>
			<?php foreach ($all_locations as $location): ?>
				<label style="display: block; margin-bottom: 8px;">
					<input type="checkbox" class="chroma-location-checkbox" name="program_locations[]"
						value="<?php echo esc_attr($location->ID); ?>" <?php checked(in_array($location->ID, $selected_locations)); ?> />
					<?php echo esc_html($location->post_title); ?>
				</label>
			<?php endforeach; ?>
		<?php else: ?>
			<p><?php _e('No locations found. Please add locations first.', 'chroma-excellence'); ?></p>
		<?php endif; ?>
	</div>
	<p><small><?php _e('This program will only appear on selected location pages.', 'chroma-excellence'); ?></small></p>

	<script>
		(function ($) {
			$(document).ready(function () {
				$('#chroma-toggle-all-locations').on('click', function (e) {
					e.preventDefault();

					var checkboxes = $('.chroma-location-checkbox');
					var allChecked = checkboxes.length === checkboxes.filter(':checked').length;

					// If all are checked, uncheck all. Otherwise, check all.
					checkboxes.prop('checked', !allChecked);
				});
			});
		})(jQuery);
	</script>
	<?php
}

/**
 * Save program locations
 */
function chroma_program_locations_meta_box_save($post_id)
{
	// Verify nonce
	if (!isset($_POST['chroma_program_locations_nonce_field']) || !wp_verify_nonce(wp_unslash($_POST['chroma_program_locations_nonce_field']), 'chroma_program_locations_nonce')) {
		return;
	}

	// Check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	// Check permissions
	if (isset($_POST['post_type']) && 'program' === $_POST['post_type']) {
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
	}

	// Save selected locations
	$selected_locations = isset($_POST['program_locations']) && is_array($_POST['program_locations'])
		? array_map('intval', $_POST['program_locations'])
		: array();

	// Legacy meta support (consider decommissioning after migration)
	update_post_meta($post_id, 'program_locations', $selected_locations);

	// High-performance taxonomy sync
	// We use the location ID as the slug/name for the term
	wp_set_object_terms($post_id, $selected_locations, 'program_location');
}
add_action('save_post_program', 'chroma_program_locations_meta_box_save');

/**
 * Render program details meta box
 */
function chroma_program_details_meta_box_render($post)
{
	wp_nonce_field('chroma_program_details_nonce', 'chroma_program_details_nonce_field');

	$age_range = get_post_meta($post->ID, 'program_age_range', true);
	$features = get_post_meta($post->ID, 'program_features', true);
	$cta_text = get_post_meta($post->ID, 'program_cta_text', true);
	$cta_link = get_post_meta($post->ID, 'program_cta_link', true);
	$color_scheme = get_post_meta($post->ID, 'program_color_scheme', true);
	?>
	<style>
		.chroma-program-field {
			margin-bottom: 20px;
		}

		.chroma-program-field label {
			display: block;
			font-weight: 600;
			margin-bottom: 5px;
		}

		.chroma-program-field input[type="text"],
		.chroma-program-field textarea,
		.chroma-program-field select {
			width: 100%;
			max-width: 600px;
		}

		.chroma-program-field small {
			display: block;
			margin-top: 5px;
			color: #666;
			font-style: italic;
		}

		.chroma-color-preview {
			display: inline-flex;
			align-items: center;
			gap: 15px;
			margin-top: 10px;
		}

		.chroma-color-preview .color-swatch {
			width: 40px;
			height: 40px;
			border-radius: 8px;
			border: 2px solid #ddd;
		}
	</style>

	<div class="chroma-program-field">
		<label for="program_icon"><?php _e('Program Icon/Emoji', 'chroma-excellence'); ?></label>
		<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
			<input type="text" id="program_icon" name="program_icon"
				value="<?php echo esc_attr(get_post_meta($post->ID, 'program_icon', true)); ?>" placeholder="e.g., 👶"
				style="width: 80px; text-align: center; font-size: 24px;" />

			<div class="chroma-emoji-presets" style="display: flex; gap: 5px; flex-wrap: wrap;">
				<?php
				$presets = array(
					'👶' => 'Infant',
					'🚀' => 'Toddler',
					'🎨' => 'Preschool',
					'🖍️' => 'Pre-K Prep',
					'🎓' => 'GA Pre-K',
					'🚌' => 'After School',
					'☀️' => 'Summer Camp',
					'🎉' => 'Parents Day Out',
				);
				foreach ($presets as $emoji => $label) {
					echo sprintf(
						'<button type="button" class="button chroma-emoji-btn" data-emoji="%s" title="%s" style="font-size: 18px; padding: 0 10px;">%s</button>',
						esc_attr($emoji),
						esc_attr($label),
						esc_html($emoji)
					);
				}
				?>
			</div>
		</div>
		<small><?php _e('Select a preset or type a custom emoji.', 'chroma-excellence'); ?></small>

		<script>
			jQuery(document).ready(function ($) {
				$('.chroma-emoji-btn').on('click', function () {
					$('#program_icon').val($(this).data('emoji'));
				});
			});
		</script>
	</div>

	<div class="chroma-program-field">
		<label for="program_age_range"><?php _e('Age Range', 'chroma-excellence'); ?></label>
		<input type="text" id="program_age_range" name="program_age_range" value="<?php echo esc_attr($age_range); ?>"
			placeholder="e.g., 6w - 12mo, 1 Year, 2-3 Years" />
		<small><?php _e('Age range badge shown on program card (e.g., "6w - 12mo", "1 Year", "4yr - 5yr")', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-program-field">
		<label for="program_features"><?php _e('Program Features', 'chroma-excellence'); ?></label>
		<textarea id="program_features" name="program_features" rows="4"
			placeholder="Enter one feature per line, e.g.:&#10;Individualized Schedules&#10;Sign Language Intro&#10;Daily Circle Time"><?php echo esc_textarea($features); ?></textarea>
		<small><?php _e('Enter one feature per line. These will display with checkmarks on the program card.', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-program-field">
		<label for="program_cta_text"><?php _e('CTA Button Text', 'chroma-excellence'); ?></label>
		<input type="text" id="program_cta_text" name="program_cta_text" value="<?php echo esc_attr($cta_text); ?>"
			placeholder="e.g., Schedule a Tour, Learn More" />
		<small><?php _e('Text for the call-to-action button (default: "Schedule a Tour")', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-program-field">
		<label for="program_cta_link"><?php _e('CTA Button Link', 'chroma-excellence'); ?></label>
		<input type="text" id="program_cta_link" name="program_cta_link" value="<?php echo esc_attr($cta_link); ?>"
			placeholder="#tour" />
		<small><?php _e('URL or anchor link for the CTA button (default: "#tour")', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-program-field">
		<label for="program_lesson_plan_file"><?php _e('Lesson Plan File (URL)', 'chroma-excellence'); ?></label>
		<input type="text" id="program_lesson_plan_file" name="program_lesson_plan_file"
			value="<?php echo esc_attr(get_post_meta($post->ID, 'program_lesson_plan_file', true)); ?>"
			placeholder="https://... (Paste PDF URL here)" />
		<small><?php _e('Paste the full URL to the PDF lesson plan. If set, a button will appear on the program page.', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-program-field">
		<label for="program_color_scheme"><?php _e('Color Scheme', 'chroma-excellence'); ?></label>
		<select id="program_color_scheme" name="program_color_scheme">
			<option value="red" <?php selected($color_scheme, 'red'); ?>>Red - Infant Care</option>
			<option value="blue" <?php selected($color_scheme, 'blue'); ?>>Blue - Toddler</option>
			<option value="yellow" <?php selected($color_scheme, 'yellow'); ?>>Yellow - Preschool</option>
			<option value="blueDark" <?php selected($color_scheme, 'blueDark'); ?>>Dark Blue - Pre-K Prep</option>
			<option value="green" <?php selected($color_scheme, 'green'); ?>>Green - GA Pre-K</option>
			<option value="orange" <?php selected($color_scheme, 'orange'); ?>>Orange - Kindergarten</option>
		</select>
		<div class="chroma-color-preview">
			<div class="color-swatch" style="background-color: #D67D6B;" title="Red"></div>
			<div class="color-swatch" style="background-color: #4A6C7C;" title="Blue"></div>
			<div class="color-swatch" style="background-color: #E6BE75;" title="Yellow"></div>
			<div class="color-swatch" style="background-color: #2F4858;" title="Dark Blue"></div>
			<div class="color-swatch" style="background-color: #8DA399;" title="Green"></div>
			<div class="color-swatch" style="background-color: #A8551E;" title="Orange"></div>
		</div>
		<small><?php _e('Color theme for the program card hover effects and badges', 'chroma-excellence'); ?></small>
	</div>

	<p><strong><?php _e('Note:', 'chroma-excellence'); ?></strong>
		<?php _e('The program description shown on the card comes from the "Excerpt" field. The featured image is used as the card image.', 'chroma-excellence'); ?>
	</p>
	<?php
}

/**
 * Save program details
 */
function chroma_program_details_meta_box_save($post_id)
{
	// Verify nonce
	if (!isset($_POST['chroma_program_details_nonce_field']) || !wp_verify_nonce(wp_unslash($_POST['chroma_program_details_nonce_field']), 'chroma_program_details_nonce')) {
		return;
	}

	// Check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	// Check permissions
	if (isset($_POST['post_type']) && 'program' === $_POST['post_type']) {
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
	}

	// Save fields
	$fields = array(
		'program_icon' => 'sanitize_text_field',
		'program_lesson_plan_file' => 'esc_url_raw',
		'program_age_range' => 'sanitize_text_field',
		'program_features' => 'sanitize_textarea_field',
		'program_cta_text' => 'sanitize_text_field',
		'program_cta_link' => 'esc_url_raw',
		'program_color_scheme' => 'sanitize_text_field',
	);

	foreach ($fields as $field => $sanitize_callback) {
		if (isset($_POST[$field])) {
			$value = call_user_func($sanitize_callback, wp_unslash($_POST[$field]));
			update_post_meta($post_id, $field, $value);
		}
	}
}
add_action('save_post_program', 'chroma_program_details_meta_box_save');

/**
 * Render single page content meta box
 */
function chroma_program_single_page_meta_box_render($post)
{
	wp_nonce_field('chroma_program_single_page_nonce', 'chroma_program_single_page_nonce_field');

	// Hero section
	$hero_title = get_post_meta($post->ID, 'program_hero_title', true);
	$hero_description = get_post_meta($post->ID, 'program_hero_description', true);
	$hero_image = get_post_meta($post->ID, 'program_hero_image', true);

	// Prismpath section
	$prism_title = get_post_meta($post->ID, 'program_prism_title', true);
	$prism_description = get_post_meta($post->ID, 'program_prism_description', true);
	$prism_focus_items = get_post_meta($post->ID, 'program_prism_focus_items', true);

	// Chart data
	$prism_physical = get_post_meta($post->ID, 'program_prism_physical', true) ?: '50';
	$prism_emotional = get_post_meta($post->ID, 'program_prism_emotional', true) ?: '50';
	$prism_social = get_post_meta($post->ID, 'program_prism_social', true) ?: '50';
	$prism_academic = get_post_meta($post->ID, 'program_prism_academic', true) ?: '50';
	$prism_creative = get_post_meta($post->ID, 'program_prism_creative', true) ?: '50';

	// Schedule
	$schedule_title = get_post_meta($post->ID, 'program_schedule_title', true);
	$schedule_items = get_post_meta($post->ID, 'program_schedule_items', true);
	?>
	<style>
		.chroma-single-field {
			margin-bottom: 20px;
		}

		.chroma-single-field label {
			display: block;
			font-weight: 600;
			margin-bottom: 5px;
		}

		.chroma-single-field input[type="text"],
		.chroma-single-field input[type="number"],
		.chroma-single-field textarea {
			width: 100%;
			max-width: 800px;
		}

		.chroma-single-field small {
			display: block;
			margin-top: 5px;
			color: #666;
			font-style: italic;
		}

		.chroma-section-divider {
			border-top: 2px solid #0073aa;
			margin: 30px 0 20px 0;
			padding-top: 20px;
		}

		.chroma-chart-inputs {
			display: grid;
			grid-template-columns: repeat(5, 1fr);
			gap: 15px;
			max-width: 800px;
		}

		.chroma-chart-input {
			text-align: center;
		}

		.chroma-chart-input input {
			text-align: center;
			font-weight: bold;
		}
	</style>

	<div class="chroma-section-divider">
		<h3 style="margin-top: 0; color: #0073aa;">Hero Section</h3>
	</div>

	<div class="chroma-single-field">
		<label for="program_hero_title"><?php _e('Hero Title', 'chroma-excellence'); ?></label>
		<input type="text" id="program_hero_title" name="program_hero_title" value="<?php echo esc_attr($hero_title); ?>"
			placeholder="e.g., The Foundation Phase." />
		<small><?php _e('Main heading on single program page (defaults to program title if empty)', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-single-field">
		<label for="program_hero_description"><?php _e('Hero Description', 'chroma-excellence'); ?></label>
		<textarea id="program_hero_description" name="program_hero_description" rows="3"
			placeholder="A peaceful, 'shoeless' environment..."><?php echo esc_textarea($hero_description); ?></textarea>
		<small><?php _e('Description paragraph in hero section', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-single-field">
		<label for="program_hero_image"><?php _e('Hero Image URL (Override)', 'chroma-excellence'); ?></label>
		<input type="text" id="program_hero_image" name="program_hero_image"
			value="<?php echo esc_attr($hero_image); ?>" placeholder="https://example.com/hero-image.webp" />
		<small><?php _e('Optional. Overrides the featured image on the single program page. Leave empty to use the featured image.', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-section-divider">
		<h3 style="margin-top: 0; color: #0073aa;">PrismPath™ Focus Section</h3>
	</div>

	<div class="chroma-single-field">
		<label for="program_prism_title"><?php _e('Prism Section Title', 'chroma-excellence'); ?></label>
		<input type="text" id="program_prism_title" name="program_prism_title" value="<?php echo esc_attr($prism_title); ?>"
			placeholder="e.g., Building Trust & Body." />
		<small><?php _e('Title for the PrismPath focus section', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-single-field">
		<label for="program_prism_description"><?php _e('Prism Description', 'chroma-excellence'); ?></label>
		<textarea id="program_prism_description" name="program_prism_description" rows="4"
			placeholder="In the first year, the brain grows faster than at any other time..."><?php echo esc_textarea($prism_description); ?></textarea>
		<small><?php _e('Description explaining the program\'s PrismPath focus', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-single-field">
		<label><?php _e('PrismPath Chart Values (0-100)', 'chroma-excellence'); ?></label>
		<div style="margin-bottom: 15px;">
			<span style="font-size: 12px; font-weight: bold; margin-right: 10px;">Quick Fill:</span>
			<button type="button" class="button chroma-chart-preset" data-values="[90,90,40,15,40]">Infant</button>
			<button type="button" class="button chroma-chart-preset" data-values="[85,75,65,30,70]">Toddler</button>
			<button type="button" class="button chroma-chart-preset" data-values="[75,65,70,55,80]">Preschool</button>
			<button type="button" class="button chroma-chart-preset" data-values="[65,60,75,75,70]">Pre-K Prep</button>
			<button type="button" class="button chroma-chart-preset" data-values="[60,60,80,90,70]">GA Pre-K</button>
			<button type="button" class="button chroma-chart-preset" data-values="[60,90,95,60,70]">Rising Pre-K</button>
			<button type="button" class="button chroma-chart-preset" data-values="[50,75,80,95,60]">Rising Kindergarten</button>
			<button type="button" class="button chroma-chart-preset" data-values="[50,70,85,75,80]">After School</button>
		</div>
		<div class="chroma-chart-inputs">
			<div class="chroma-chart-input">
				<label for="program_prism_physical"
					style="font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 5px;">Physical</label>
				<input type="number" id="program_prism_physical" name="program_prism_physical"
					value="<?php echo esc_attr($prism_physical); ?>" min="0" max="100" style="background: #F4E5E2;" />
			</div>
			<div class="chroma-chart-input">
				<label for="program_prism_emotional"
					style="font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 5px;">Emotional</label>
				<input type="number" id="program_prism_emotional" name="program_prism_emotional"
					value="<?php echo esc_attr($prism_emotional); ?>" min="0" max="100" style="background: #FDF6E3;" />
			</div>
			<div class="chroma-chart-input">
				<label for="program_prism_social"
					style="font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 5px;">Social</label>
				<input type="number" id="program_prism_social" name="program_prism_social"
					value="<?php echo esc_attr($prism_social); ?>" min="0" max="100" style="background: #E3EBE8;" />
			</div>
			<div class="chroma-chart-input">
				<label for="program_prism_academic"
					style="font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 5px;">Academic</label>
				<input type="number" id="program_prism_academic" name="program_prism_academic"
					value="<?php echo esc_attr($prism_academic); ?>" min="0" max="100" style="background: #E3E9EC;" />
			</div>
			<div class="chroma-chart-input">
				<label for="program_prism_creative"
					style="font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 5px;">Creative</label>
				<input type="number" id="program_prism_creative" name="program_prism_creative"
					value="<?php echo esc_attr($prism_creative); ?>" min="0" max="100" style="background: #FDF6E3;" />
			</div>
		</div>
		<small><?php _e('Set values 0-100 for each pillar. These create the radar chart.', 'chroma-excellence'); ?></small>

		<script>
			jQuery(document).ready(function ($) {
				$('.chroma-chart-preset').on('click', function () {
					var values = $(this).data('values'); // Array [Phy, Emo, Soc, Aca, Cre]
					$('#program_prism_physical').val(values[0]);
					$('#program_prism_emotional').val(values[1]);
					$('#program_prism_social').val(values[2]);
					$('#program_prism_academic').val(values[3]);
					$('#program_prism_creative').val(values[4]);
				});
			});
		</script>
	</div>

	<div class="chroma-single-field">
		<label for="program_prism_focus_items"><?php _e('Focus Items', 'chroma-excellence'); ?></label>
		<textarea id="program_prism_focus_items" name="program_prism_focus_items" rows="4"
			placeholder="Enter one item per line, e.g.:&#10;High Physical: Tummy time, rolling, reaching.&#10;High Emotional: Responsive feeding, cuddling."><?php echo esc_textarea($prism_focus_items); ?></textarea>
		<small><?php _e('Bullet points explaining the focus. One per line.', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-section-divider">
		<h3 style="margin-top: 0; color: #0073aa;">Daily Schedule/Rhythm Section</h3>
	</div>

	<div class="chroma-single-field">
		<label for="program_schedule_title"><?php _e('Schedule Section Title', 'chroma-excellence'); ?></label>
		<input type="text" id="program_schedule_title" name="program_schedule_title"
			value="<?php echo esc_attr($schedule_title); ?>" placeholder="e.g., A Rhythm, Not a Routine" />
		<small><?php _e('Title for the schedule section', 'chroma-excellence'); ?></small>
	</div>

	<div class="chroma-single-field">
		<label for="program_schedule_items"><?php _e('Schedule Items', 'chroma-excellence'); ?></label>
		<textarea id="program_schedule_items" name="program_schedule_items" rows="8"
			placeholder="Format: Badge|Title|Description (one per line)&#10;Example:&#10;AM|Warm Welcome & Bottles|Transition from parent arms to teacher arms...&#10;Mid|Sensory Discovery|Tummy time on textured mats...&#10;PM|Stroller Walks & Nap|Fresh air in our buggy carts..."><?php echo esc_textarea($schedule_items); ?></textarea>
		<small><?php _e('Format: Badge|Title|Description (one per line). Badge can be AM, Mid, PM, or any text.', 'chroma-excellence'); ?></small>
	</div>

	<p><strong><?php _e('Note:', 'chroma-excellence'); ?></strong>
		<?php _e('The featured image is used as the hero image on the single program page.', 'chroma-excellence'); ?></p>
	<?php
}

/**
 * Save single page content
 */
function chroma_program_single_page_meta_box_save($post_id)
{
	// Verify nonce
	if (!isset($_POST['chroma_program_single_page_nonce_field']) || !wp_verify_nonce(wp_unslash($_POST['chroma_program_single_page_nonce_field']), 'chroma_program_single_page_nonce')) {
		return;
	}

	// Check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	// Check permissions
	if (isset($_POST['post_type']) && 'program' === $_POST['post_type']) {
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}
	}

	// Save fields
	$fields = array(
		'program_hero_title' => 'sanitize_text_field',
		'program_hero_description' => 'sanitize_textarea_field',
		'program_hero_image' => 'esc_url_raw',
		'program_prism_title' => 'sanitize_text_field',
		'program_prism_description' => 'sanitize_textarea_field',
		'program_prism_focus_items' => 'sanitize_textarea_field',
		'program_prism_physical' => 'absint',
		'program_prism_emotional' => 'absint',
		'program_prism_social' => 'absint',
		'program_prism_academic' => 'absint',
		'program_prism_creative' => 'absint',
		'program_schedule_title' => 'sanitize_text_field',
		'program_schedule_items' => 'sanitize_textarea_field',
	);

	foreach ($fields as $field => $sanitize_callback) {
		if (isset($_POST[$field])) {
			$value = call_user_func($sanitize_callback, wp_unslash($_POST[$field]));
			update_post_meta($post_id, $field, $value);
		}
	}
}
add_action('save_post_program', 'chroma_program_single_page_meta_box_save');

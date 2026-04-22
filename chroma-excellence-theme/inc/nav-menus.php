<?php
/**
 * Navigation Menus with Tailwind Support
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Register navigation menus
 */
function chroma_register_menus()
{
	register_nav_menus(array(
		'primary' => __('Primary Menu', 'chroma-excellence'),
		'primary_es' => __('Primary Menu (Spanish)', 'chroma-excellence'),
		'footer' => __('Footer Menu', 'chroma-excellence'),
		'footer_es' => __('Footer Menu (Spanish)', 'chroma-excellence'),
		'footer_contact' => __('Footer Contact Menu', 'chroma-excellence'),
		'footer_contact_es' => __('Footer Contact Menu (Spanish)', 'chroma-excellence'),
	));
}
add_action('init', 'chroma_register_menus');

/**
 * Get the current nav cache version.
 */
function chroma_get_nav_cache_version()
{
	return (string) max(1, (int) get_option('chroma_nav_cache_version', 1));
}

/**
 * Bump the nav cache version whenever menu data changes.
 */
function chroma_bump_nav_cache_version()
{
	update_option('chroma_nav_cache_version', time(), false);
}

/**
 * Purge external page caches after nav changes so cached HTML reflects the latest menu.
 */
function chroma_purge_page_caches()
{
	static $purged = false;

	if ($purged) {
		return;
	}

	$purged = true;

	if (function_exists('wp_cache_flush')) {
		wp_cache_flush();
	}

	if (class_exists('LiteSpeed\\Purge')) {
		LiteSpeed\Purge::purge_all();
	} elseif (has_action('litespeed_purge_all')) {
		do_action('litespeed_purge_all');
	}

	if (function_exists('w3tc_flush_all')) {
		w3tc_flush_all();
	}

	if (function_exists('rocket_clean_domain')) {
		rocket_clean_domain();
	}

	if (function_exists('wpfc_clear_all_cache')) {
		wpfc_clear_all_cache(true);
	}
}

/**
 * The homepage header changes with WP menu updates, so do not allow edge caches to freeze it.
 */
function chroma_disable_front_page_edge_cache()
{
	if (is_admin() || !is_front_page() || headers_sent()) {
		return;
	}

	header_remove('Cache-Control');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
	header('Pragma: no-cache');
	header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
	header('Surrogate-Control: no-store');
}
add_action('send_headers', 'chroma_disable_front_page_edge_cache', 100);

/**
 * Nav markup on the front page goes stale most visibly, so skip transients there.
 */
function chroma_should_cache_nav_markup()
{
	return !is_front_page() && !is_customize_preview();
}

/**
 * Build a stable nav cache key that can be invalidated globally.
 */
function chroma_get_nav_cache_key($prefix, $is_es)
{
	$request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '/';
	$page_hash = md5($request_uri);

	return implode('_', array(
		$prefix,
		$is_es ? 'es' : 'en',
		chroma_get_nav_cache_version(),
		$page_hash,
	));
}

/**
 * Virtual SEO routes use synthetic query objects that can break wp_nav_menu's
 * current-item resolution. Render assigned menu links directly for those requests.
 */
function chroma_is_virtual_nav_request()
{
	return (string) get_query_var('chroma_near_me') !== ''
		|| (string) get_query_var('chroma_combo') !== ''
		|| (string) get_query_var('geo_page') !== ''
		|| (string) get_query_var('geo_city') !== '';
}

/**
 * Render top-level menu items without invoking wp_nav_menu context logic.
 *
 * @param string $location
 * @param string $link_class
 * @return bool
 */
function chroma_render_flat_menu_location($location, $link_class)
{
	$locations = get_nav_menu_locations();
	if (empty($locations[$location])) {
		return false;
	}

	$items = wp_get_nav_menu_items((int) $locations[$location], [
		'update_post_term_cache' => false,
	]);

	if (empty($items) || is_wp_error($items)) {
		return false;
	}

	usort($items, static function ($left, $right) {
		return ((int) ($left->menu_order ?? 0)) <=> ((int) ($right->menu_order ?? 0));
	});

	foreach ($items as $item) {
		if ((int) ($item->menu_item_parent ?? 0) !== 0) {
			continue;
		}

		$url = isset($item->url) ? (string) $item->url : '';
		if ($url !== '' && strpos($url, home_url()) !== false) {
			$parts = explode('#', $url, 2);
			$path = user_trailingslashit($parts[0]);
			$url = $path . (isset($parts[1]) ? '#' . $parts[1] : '');
		}
		if ($url === '') {
			$url = '#';
		}

		echo '<a href="' . esc_url($url) . '" class="' . esc_attr($link_class) . '">';
		echo esc_html(isset($item->title) ? (string) $item->title : '');
		echo '</a>';
	}

	return true;
}

/**
 * Primary Navigation with Tailwind classes
 */
function chroma_primary_nav()
{
	$is_es = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish());
	$cache_enabled = chroma_should_cache_nav_markup();
	$cache_key = chroma_get_nav_cache_key('chroma_primary_nav', $is_es);

	if ($cache_enabled) {
		$cached = get_transient($cache_key);
		if ($cached !== false) {
			echo $cached;
			return;
		}
	}

	ob_start();
	$location = $is_es ? 'primary_es' : 'primary';

	if (chroma_is_virtual_nav_request() && chroma_render_flat_menu_location($location, 'hover:text-chroma-blue transition')) {
		$output = ob_get_clean();
		if ($cache_enabled) {
			set_transient($cache_key, $output, DAY_IN_SECONDS);
		}
		echo $output;
		return;
	}

	wp_nav_menu(array(
		'theme_location' => $location,
		'container' => false,
		'menu_class' => '',
		'fallback_cb' => 'chroma_primary_nav_fallback',
		'items_wrap' => '%3$s',
		'depth' => 1,
		'walker' => new Chroma_Primary_Nav_Walker(),
	));

	$output = ob_get_clean();
	if ($cache_enabled) {
		set_transient($cache_key, $output, DAY_IN_SECONDS);
	}
	echo $output;
}

/**
 * Primary Nav Fallback
 */
function chroma_primary_nav_fallback()
{
	$is_es = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish());

	$pages = $is_es ? array(
		'programs' => 'Programas',
		'locations' => 'Ubicaciones',
		'about' => 'Nosotros',
		'contact-us' => 'Contacto'
	) : array(
		'programs' => 'Programs',
		'locations' => 'Locations',
		'about' => 'About Us',
		'contact-us' => 'Contact'
	);

	foreach ($pages as $slug => $title) {
		$url = chroma_get_page_link($slug);
		echo '<a href="' . esc_url($url) . '" class="hover:text-chroma-blue transition">' . esc_html($title) . '</a>';
	}
}

/**
 * Footer Navigation
 */
function chroma_footer_nav()
{
	$is_es = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish());
	$cache_enabled = chroma_should_cache_nav_markup();
	$cache_key = chroma_get_nav_cache_key('chroma_footer_nav', $is_es);

	if ($cache_enabled) {
		$cached = get_transient($cache_key);
		if ($cached !== false) {
			echo $cached;
			return;
		}
	}

	ob_start();
	$location = $is_es ? 'footer_es' : 'footer';

	if (chroma_is_virtual_nav_request() && chroma_render_flat_menu_location($location, 'block hover:text-white transition')) {
		$output = ob_get_clean();
		if ($cache_enabled) {
			set_transient($cache_key, $output, DAY_IN_SECONDS);
		}
		echo $output;
		return;
	}

	wp_nav_menu(array(
		'theme_location' => $location,
		'container' => false,
		'menu_class' => '',
		'fallback_cb' => 'chroma_footer_nav_fallback',
		'items_wrap' => '%3$s',
		'depth' => 1,
		'walker' => new Chroma_Footer_Nav_Walker(),
	));

	$output = ob_get_clean();
	if ($cache_enabled) {
		set_transient($cache_key, $output, DAY_IN_SECONDS);
	}
	echo $output;
}

/**
 * Footer Nav Fallback
 */
function chroma_footer_nav_fallback()
{
	$is_es = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish());

	$pages = $is_es ? array(
		'home' => 'Inicio',
		'prismpath' => 'PrismPath',
		'programs' => 'Todos los Programas',
		'parents' => 'Padres'
	) : array(
		'home' => 'Home',
		'prismpath' => 'PrismPath',
		'programs' => 'All Programs',
		'parents' => 'Parents'
	);

	foreach ($pages as $slug => $title) {
		$url = ($slug === 'home') ? home_url('/') : home_url('/' . $slug . '/');
		echo '<a href="' . esc_url($url) . '" class="block hover:text-white transition">' . esc_html($title) . '</a>';
	}
}

/**
 * Footer Contact Navigation
 */
function chroma_footer_contact_nav()
{
	$is_es = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish());
	$cache_enabled = chroma_should_cache_nav_markup();
	$cache_key = chroma_get_nav_cache_key('chroma_footer_contact_nav', $is_es);

	if ($cache_enabled) {
		$cached = get_transient($cache_key);
		if ($cached !== false) {
			echo $cached;
			return;
		}
	}

	ob_start();
	$location = $is_es ? 'footer_contact_es' : 'footer_contact';

	if (has_nav_menu($location)) {
		wp_nav_menu(array(
			'theme_location' => $location,
			'container' => false,
			'menu_class' => 'mt-4 space-y-2 pt-4 border-t border-white/10',
			'fallback_cb' => false,
			'items_wrap' => '<div class="%2$s">%3$s</div>',
			'depth' => 1,
			'walker' => new Chroma_Footer_Nav_Walker(),
		));
	} else {
		$program_slug = chroma_get_program_base_slug();

		$pages = $is_es ? array(
			$program_slug => 'Programas',
			'locations' => 'Ubicaciones',
			'about' => 'Nosotros',
			'contact-us' => 'Contacto',
		) : array(
			$program_slug => 'Programs',
			'locations' => 'Locations',
			'about' => 'About Us',
			'contact-us' => 'Contact',
		);

		foreach ($pages as $slug => $title) {
			$url = home_url('/' . $slug . '/');
			echo '<a href="' . esc_url($url) . '" class="block hover:text-white transition">' . esc_html($title) . '</a>';
		}
	}

	$output = ob_get_clean();
	if ($cache_enabled) {
		set_transient($cache_key, $output, DAY_IN_SECONDS);
	}
	echo $output;
}

/**
 * Custom Walker for Primary Navigation
 */
class Chroma_Primary_Nav_Walker extends Walker_Nav_Menu
{
	function start_lvl(&$output, $depth = 0, $args = null)
	{
		// No submenu wrapper needed
	}

	function end_lvl(&$output, $depth = 0, $args = null)
	{
		// No submenu wrapper needed
	}

	function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
	{
		$classes = 'hover:text-chroma-blue transition';

		if ($item->current) {
			$classes .= ' text-chroma-red';
		}

		$url = isset($item->url) ? (string) $item->url : '';
		// Enforce trailing slash for internal links
		if ($url !== '' && strpos($url, home_url()) !== false) {
			$parts = explode('#', $url, 2);
			$path = user_trailingslashit($parts[0]);
			$url = $path . (isset($parts[1]) ? '#' . $parts[1] : '');
		}
		if ($url === '') {
			$url = '#';
		}

		$output .= '<a href="' . esc_url($url) . '" class="' . esc_attr($classes) . '">';
		$output .= esc_html(isset($item->title) ? (string) $item->title : '');
		$output .= '</a>';
	}

	function end_el(&$output, $item, $depth = 0, $args = null)
	{
		// No closing tag needed as we are not using li
	}
}

/**
 * Custom Walker for Footer Navigation
 */
class Chroma_Footer_Nav_Walker extends Walker_Nav_Menu
{
	function start_lvl(&$output, $depth = 0, $args = null)
	{
		// No submenu wrapper needed
	}

	function end_lvl(&$output, $depth = 0, $args = null)
	{
		// No submenu wrapper needed
	}

	function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
	{
		$url = isset($item->url) ? (string) $item->url : '';
		// Enforce trailing slash for internal links
		if ($url !== '' && strpos($url, home_url()) !== false) {
			$parts = explode('#', $url, 2);
			$path = user_trailingslashit($parts[0]);
			$url = $path . (isset($parts[1]) ? '#' . $parts[1] : '');
		}
		if ($url === '') {
			$url = '#';
		}

		$output .= '<a href="' . esc_url($url) . '" class="block hover:text-white transition">';
		$output .= esc_html(isset($item->title) ? (string) $item->title : '');
		$output .= '</a>';
	}

	function end_el(&$output, $item, $depth = 0, $args = null)
	{
		// No closing tag needed as we are not using li
	}
}

/**
 * Mobile Navigation
 */
function chroma_mobile_nav()
{
	$is_es = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish());
	$cache_enabled = chroma_should_cache_nav_markup();
	$cache_key = chroma_get_nav_cache_key('chroma_mobile_nav', $is_es);

	if ($cache_enabled) {
		$cached = get_transient($cache_key);
		if ($cached !== false) {
			echo $cached;
			return;
		}
	}

	ob_start();
	$location = $is_es ? 'primary_es' : 'primary';

	if (chroma_is_virtual_nav_request() && chroma_render_flat_menu_location($location, 'block py-3 border-b border-brand-ink/5 text-lg font-semibold text-brand-ink hover:text-chroma-blue transition')) {
		$output = ob_get_clean();
		if ($cache_enabled) {
			set_transient($cache_key, $output, DAY_IN_SECONDS);
		}
		echo $output;
		return;
	}

	wp_nav_menu(array(
		'theme_location' => $location,
		'container' => false,
		'menu_class' => 'flex flex-col space-y-2',
		'fallback_cb' => 'chroma_mobile_nav_fallback',
		'items_wrap' => '%3$s',
		'depth' => 1,
		'walker' => new Chroma_Mobile_Nav_Walker(),
	));

	$output = ob_get_clean();
	if ($cache_enabled) {
		set_transient($cache_key, $output, DAY_IN_SECONDS);
	}
	echo $output;
}

/**
 * Mobile Nav Fallback
 */
function chroma_mobile_nav_fallback()
{
	$is_es = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish());
	$program_slug = chroma_get_program_base_slug();

	$pages = $is_es ? array(
		$program_slug => 'Programas',
		'prismpath' => 'Prismpath',
		'curriculum' => 'Currículo',
		'schedule' => 'Horario',
		'locations' => 'Ubicaciones',
		'faq' => 'Preguntas Frecuentes'
	) : array(
		$program_slug => 'Programs',
		'prismpath' => 'Prismpath',
		'curriculum' => 'Curriculum',
		'schedule' => 'Schedule',
		'locations' => 'Locations',
		'faq' => 'FAQ'
	);

	foreach ($pages as $slug => $title) {
		$url = ($slug === 'prismpath' || $slug === 'curriculum' || $slug === 'schedule' || $slug === 'faq')
			? '#' . $slug
			: chroma_get_page_link($slug);

		// Adjust anchor links for Spanish if needed, though usually anchors are ID based and language agnostic if the ID is static.
		// However, for page links like 'locations', we use chroma_get_page_link.

		echo '<a href="' . esc_url($url) . '" class="block py-3 border-b border-brand-ink/5 text-lg font-semibold text-brand-ink hover:text-chroma-blue transition">' . esc_html($title) . '</a>';
	}
}

/**
 * Custom Walker for Mobile Navigation
 */
class Chroma_Mobile_Nav_Walker extends Walker_Nav_Menu
{
	function start_lvl(&$output, $depth = 0, $args = null)
	{
		// No submenu wrapper needed
	}

	function end_lvl(&$output, $depth = 0, $args = null)
	{
		// No submenu wrapper needed
	}

	function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
	{
		$classes = 'block py-3 border-b border-brand-ink/5 text-lg font-semibold text-brand-ink hover:text-chroma-blue transition';

		if ($item->current) {
			$classes .= ' text-chroma-blue';
		}

		$url = isset($item->url) ? (string) $item->url : '';
		// Enforce trailing slash for internal links
		if ($url !== '' && strpos($url, home_url()) !== false) {
			$parts = explode('#', $url, 2);
			$path = user_trailingslashit($parts[0]);
			$url = $path . (isset($parts[1]) ? '#' . $parts[1] : '');
		}
		if ($url === '') {
			$url = '#';
		}

		$output .= '<a href="' . esc_url($url) . '" class="' . esc_attr($classes) . '">';
		$output .= esc_html(isset($item->title) ? (string) $item->title : '');
		$output .= '</a>';
	}

	function end_el(&$output, $item, $depth = 0, $args = null)
	{
		// No closing tag needed as we are not using li
	}
}

/**
 * Clear nav menu transients on update
 */
function chroma_clear_nav_transients()
{
	global $wpdb;
	$prefixes = array(
		'chroma_primary_nav_',
		'chroma_mobile_nav_',
		'chroma_footer_nav_',
		'chroma_footer_contact_nav_'
	);

	foreach ($prefixes as $prefix) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . $wpdb->esc_like($prefix) . '%',
				'_transient_timeout_' . $wpdb->esc_like($prefix) . '%'
			)
		);
	}

	chroma_bump_nav_cache_version();
	chroma_purge_page_caches();
}
add_action('wp_update_nav_menu', 'chroma_clear_nav_transients');
add_action('wp_update_nav_menu_item', 'chroma_clear_nav_transients');
add_action('save_post_nav_menu_item', 'chroma_clear_nav_transients');
add_action('customize_save_after', 'chroma_clear_nav_transients');
add_action('switch_theme', 'chroma_clear_nav_transients');

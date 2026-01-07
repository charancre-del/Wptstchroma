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
 * Primary Navigation with Tailwind classes
 */
function chroma_primary_nav()
{
	$location = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish()) ? 'primary_es' : 'primary';
	
	wp_nav_menu(array(
		'theme_location' => $location,
		'container' => false,
		'menu_class' => '',
		'fallback_cb' => 'chroma_primary_nav_fallback',
		'items_wrap' => '%3$s',
		'depth' => 1,
		'walker' => new Chroma_Primary_Nav_Walker(),
	));
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
	$location = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish()) ? 'footer_es' : 'footer';

	wp_nav_menu(array(
		'theme_location' => $location,
		'container' => false,
		'menu_class' => '',
		'fallback_cb' => 'chroma_footer_nav_fallback',
		'items_wrap' => '%3$s',
		'depth' => 1,
		'walker' => new Chroma_Footer_Nav_Walker(),
	));
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
	$location = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish()) ? 'footer_contact_es' : 'footer_contact';

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
		$is_es = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish());
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

		$url = $item->url;
		// Enforce trailing slash for internal links
		if (strpos($url, home_url()) !== false) {
			$parts = explode('#', $url, 2);
			$path = user_trailingslashit($parts[0]);
			$url = $path . (isset($parts[1]) ? '#' . $parts[1] : '');
		}

		$output .= '<a href="' . esc_url($url) . '" class="' . esc_attr($classes) . '">';
		$output .= esc_html($item->title);
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
		$url = $item->url;
		// Enforce trailing slash for internal links
		if (strpos($url, home_url()) !== false) {
			$parts = explode('#', $url, 2);
			$path = user_trailingslashit($parts[0]);
			$url = $path . (isset($parts[1]) ? '#' . $parts[1] : '');
		}

		$output .= '<a href="' . esc_url($url) . '" class="block hover:text-white transition">';
		$output .= esc_html($item->title);
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
	$location = (class_exists('Chroma_Multilingual_Manager') && method_exists('Chroma_Multilingual_Manager', 'is_spanish') && Chroma_Multilingual_Manager::is_spanish()) ? 'primary_es' : 'primary';

	wp_nav_menu(array(
		'theme_location' => $location,
		'container' => false,
		'menu_class' => 'flex flex-col space-y-2',
		'fallback_cb' => 'chroma_mobile_nav_fallback',
		'items_wrap' => '%3$s',
		'depth' => 1,
		'walker' => new Chroma_Mobile_Nav_Walker(),
	));
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

		$url = $item->url;
		// Enforce trailing slash for internal links
		if (strpos($url, home_url()) !== false) {
			$parts = explode('#', $url, 2);
			$path = user_trailingslashit($parts[0]);
			$url = $path . (isset($parts[1]) ? '#' . $parts[1] : '');
		}

		$output .= '<a href="' . esc_url($url) . '" class="' . esc_attr($classes) . '">';
		$output .= esc_html($item->title);
		$output .= '</a>';
	}

	function end_el(&$output, $item, $depth = 0, $args = null)
	{
		// No closing tag needed as we are not using li
	}
}

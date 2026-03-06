<?php
/**
 * URL Consolidator
 * Enforces winner URLs for known duplicate clusters.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_URL_Consolidator
{
    /**
     * Known duplicate->winner redirects.
     *
     * @return array<string, string>
     */
    public static function get_redirect_map()
    {
        return [
            '/contact/' => '/contact-us/',
            '/es/contact/' => '/es/contact-us/',
            '/employers-2/' => '/employers/',
            '/es/employers-2/' => '/es/employers/',
        ];
    }

    public function __construct()
    {
        add_action('template_redirect', [$this, 'redirect_duplicates'], 0);
    }

    /**
     * Redirect duplicate paths to canonical winners.
     */
    public function redirect_duplicates()
    {
        if (is_admin() || wp_doing_ajax() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }

        if (is_404()) {
            return;
        }

        $request_path = $this->normalize_path($_SERVER['REQUEST_URI'] ?? '');
        if ($request_path === '') {
            return;
        }

        $map = self::get_redirect_map();
        if (isset($map[$request_path])) {
            wp_safe_redirect(home_url($map[$request_path]), 301);
            exit;
        }

        $career_target = $this->get_career_duplicate_target($request_path);
        if ($career_target !== '') {
            wp_safe_redirect(home_url($career_target), 301);
            exit;
        }
    }

    /**
     * Shared duplicate path detector (used by sitemaps).
     *
     * @param string $path
     * @return bool
     */
    public static function is_duplicate_path($path)
    {
        $normalized = self::normalize_static_path($path);
        if ($normalized === '') {
            return false;
        }

        if (isset(self::get_redirect_map()[$normalized])) {
            return true;
        }

        // Suffix duplicates like /career/childcare-teacher-2/
        if (preg_match('#^/career/.+\-\d+/$#', $normalized)) {
            return true;
        }

        return false;
    }

    /**
     * Resolve career duplicate slug target if canonical base exists.
     *
     * @param string $request_path
     * @return string
     */
    private function get_career_duplicate_target($request_path)
    {
        if (!preg_match('#^/career/(.+)-(\d+)/$#', $request_path, $matches)) {
            return '';
        }

        $base_slug = sanitize_title($matches[1]);
        if ($base_slug === '') {
            return '';
        }

        $target_path = '/career/' . $base_slug . '/';
        if ($target_path === $request_path) {
            return '';
        }

        if ($this->target_exists($target_path)) {
            return $target_path;
        }

        return '';
    }

    /**
     * Check whether winner URL exists as a local object.
     *
     * @param string $target_path
     * @return bool
     */
    private function target_exists($target_path)
    {
        $target_url = home_url($target_path);
        $post_id = url_to_postid($target_url);
        if (!empty($post_id)) {
            return true;
        }

        $slug = trim($target_path, '/');
        if ($slug === '') {
            return false;
        }

        $post = get_page_by_path($slug, OBJECT, ['page', 'post', 'career', 'location', 'program', 'city']);
        return !empty($post);
    }

    /**
     * Normalize request URI path.
     *
     * @param string $uri
     * @return string
     */
    private function normalize_path($uri)
    {
        return self::normalize_static_path($uri);
    }

    /**
     * Normalize path helper shared by static and instance usage.
     *
     * @param string $path
     * @return string
     */
    private static function normalize_static_path($path)
    {
        $path = strtok((string) $path, '?');
        if ($path === false || $path === '') {
            return '';
        }

        $path = '/' . ltrim($path, '/');
        $path = trailingslashit($path);
        return strtolower($path);
    }
}

new Chroma_URL_Consolidator();


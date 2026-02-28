<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Utils;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class Discovery_Routes
{
    private const NS = 'chroma-agent/v1';

    public static function register(): void
    {
        register_rest_route(self::NS, '/discovery', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'discovery'],
            'permission_callback' => [__CLASS__, 'allow_any_valid_key'],
        ]);

        register_rest_route(self::NS, '/resources', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'resources'],
            'permission_callback' => [__CLASS__, 'allow_any_valid_key'],
        ]);

        register_rest_route(self::NS, '/write-policy', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'write_policy'],
            'permission_callback' => [__CLASS__, 'allow_any_valid_key'],
        ]);

        register_rest_route(self::NS, '/geo-contract', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'geo_contract'],
            'permission_callback' => [__CLASS__, 'allow_any_valid_key'],
        ]);
    }

    public static function allow_any_valid_key(WP_REST_Request $request)
    {
        return Auth::authorize($request, []);
    }

    public static function discovery(WP_REST_Request $request)
    {
        $plugins = [];
        foreach ((array) get_option('active_plugins', []) as $plugin_file) {
            if (strpos((string) $plugin_file, 'chroma-') !== false || strpos((string) $plugin_file, 'QA-Report-App') !== false) {
                $plugins[] = $plugin_file;
            }
        }

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'namespace' => self::NS,
                'site_url' => site_url(),
                'home_url' => home_url(),
                'wp_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'is_ssl' => Utils::is_https_request(),
                'single_site' => !is_multisite(),
                'active_plugins' => $plugins,
                'allowlists' => [
                    'theme_options' => Utils::get_theme_option_allowlist(),
                    'theme_mods' => Utils::get_theme_mod_allowlist(),
                    'seo_options' => Utils::get_seo_option_allowlist(),
                    'seo_meta' => Utils::get_seo_meta_allowlist(),
                ],
                'introspection' => [
                    'write_policy_route' => '/wp-json/' . self::NS . '/write-policy',
                    'geo_contract_route' => '/wp-json/' . self::NS . '/geo-contract',
                ],
                'scopes' => Auth::current_key()['scopes'] ?? [],
            ],
        ]);
    }

    public static function resources(WP_REST_Request $request)
    {
        $types = get_post_types(['public' => true], 'names');
        $taxonomies = get_taxonomies(['public' => true], 'names');

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'post_types' => array_values($types),
                'taxonomies' => array_values($taxonomies),
                'theme_option_allowlist' => Utils::get_theme_option_allowlist(),
                'theme_mod_allowlist' => Utils::get_theme_mod_allowlist(),
                'seo_option_allowlist' => Utils::get_seo_option_allowlist(),
                'seo_meta_allowlist' => Utils::get_seo_meta_allowlist(),
                'content_meta_write_policy' => Content_Routes::describe_meta_write_policy(),
                'geo_feed_contract' => Geo_Routes::describe_contract(),
            ],
        ]);
    }

    public static function write_policy(WP_REST_Request $request)
    {
        $meta_key = (string) $request->get_param('meta_key');
        $data = Content_Routes::describe_meta_write_policy();

        if ($meta_key !== '') {
            $data['inspection'] = Content_Routes::inspect_meta_write_policy($meta_key);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $data,
        ]);
    }

    public static function geo_contract(WP_REST_Request $request)
    {
        return rest_ensure_response([
            'success' => true,
            'data' => Geo_Routes::describe_contract(),
        ]);
    }
}

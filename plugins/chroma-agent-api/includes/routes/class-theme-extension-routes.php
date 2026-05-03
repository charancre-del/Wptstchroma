<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Field_Registry;
use ChromaAgentAPI\Route_Utils;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class Theme_Extension_Routes
{
    private const NS = 'chroma-agent/v1';

    public static function register(): void
    {
        register_rest_route(self::NS, '/theme/customizer', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_customizer'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_customizer'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/theme/customizer/(?P<group>[a-z0-9-]+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_customizer_group'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_customizer_group'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/theme/page-meta/(?P<post_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_page_meta'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_page_meta'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/theme/cpt-meta/(?P<post_type>[a-z0-9_-]+)/(?P<post_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_cpt_meta'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_cpt_meta'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/theme/taxonomy-meta/(?P<taxonomy>[a-z0-9_-]+)/(?P<term_id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_taxonomy_meta'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_taxonomy_meta'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);
    }

    public static function read_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['read:theme']);
    }

    public static function write_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['write:theme']);
    }

    public static function get_customizer(WP_REST_Request $request)
    {
        $groups = Field_Registry::theme_customizer_groups();
        $data = [];
        foreach ($groups as $group => $config) {
            $data[$group] = Route_Utils::read_theme_mods((array) ($config['fields'] ?? []));
        }

        return rest_ensure_response([
            'success' => true,
            'groups' => self::describe_customizer_groups(),
            'data' => $data,
        ]);
    }

    public static function get_customizer_group(WP_REST_Request $request)
    {
        $group = sanitize_key((string) $request['group']);
        $groups = Field_Registry::theme_customizer_groups();
        if (!isset($groups[$group])) {
            return new \WP_Error('caa_invalid_customizer_group', 'Unknown customizer group.', ['status' => 404]);
        }

        $fields = (array) ($groups[$group]['fields'] ?? []);
        return rest_ensure_response([
            'success' => true,
            'group' => $group,
            'fields' => $fields,
            'json_fields' => (array) ($groups[$group]['json_fields'] ?? []),
            'data' => Route_Utils::read_theme_mods($fields),
        ]);
    }

    public static function set_customizer(WP_REST_Request $request)
    {
        $fields = Field_Registry::flatten_group_fields(Field_Registry::theme_customizer_groups());
        return Route_Utils::write_theme_mods(
            $request,
            Route_Utils::updates_from_request($request),
            $fields,
            'write:theme',
            'theme_customizer'
        );
    }

    public static function set_customizer_group(WP_REST_Request $request)
    {
        $group = sanitize_key((string) $request['group']);
        $groups = Field_Registry::theme_customizer_groups();
        if (!isset($groups[$group])) {
            return new \WP_Error('caa_invalid_customizer_group', 'Unknown customizer group.', ['status' => 404]);
        }

        return Route_Utils::write_theme_mods(
            $request,
            Route_Utils::updates_from_request($request),
            (array) ($groups[$group]['fields'] ?? []),
            'write:theme',
            'theme_customizer'
        );
    }

    public static function get_page_meta(WP_REST_Request $request)
    {
        $post_id = (int) $request['post_id'];
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'page') {
            return new \WP_Error('caa_page_not_found', 'Page not found.', ['status' => 404]);
        }

        [$keys, $prefixes, $groups] = self::page_meta_policy($request);
        $data = Route_Utils::read_post_meta_values($post_id, $keys);
        $data = array_merge($data, self::read_prefix_meta($post_id, $prefixes));

        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'template' => get_post_meta($post_id, '_wp_page_template', true),
            'groups' => $groups,
            'fields' => $keys,
            'prefixes' => $prefixes,
            'data' => $data,
        ]);
    }

    public static function set_page_meta(WP_REST_Request $request)
    {
        $post_id = (int) $request['post_id'];
        [$keys, $prefixes] = self::page_meta_policy($request);

        return Route_Utils::write_post_meta(
            $request,
            $post_id,
            Route_Utils::updates_from_request($request),
            $keys,
            $prefixes,
            'write:theme',
            'theme_page_meta'
        );
    }

    public static function get_cpt_meta(WP_REST_Request $request)
    {
        $post_type = sanitize_key((string) $request['post_type']);
        $post_id = (int) $request['post_id'];
        $post = get_post($post_id);
        $groups = Field_Registry::theme_cpt_meta_groups();

        if (!isset($groups[$post_type])) {
            return new \WP_Error('caa_invalid_cpt_meta_type', 'Unsupported theme CPT type.', ['status' => 404]);
        }

        if (!$post || $post->post_type !== $post_type) {
            return new \WP_Error('caa_post_not_found', 'Post not found for requested post type.', ['status' => 404]);
        }

        $fields = (array) ($groups[$post_type]['fields'] ?? []);
        $prefixes = (array) ($groups[$post_type]['prefixes'] ?? []);
        $data = Route_Utils::read_post_meta_values($post_id, $fields);
        $data = array_merge($data, self::read_prefix_meta($post_id, $prefixes));

        return rest_ensure_response([
            'success' => true,
            'post_id' => $post_id,
            'post_type' => $post_type,
            'fields' => $fields,
            'prefixes' => $prefixes,
            'data' => $data,
        ]);
    }

    public static function set_cpt_meta(WP_REST_Request $request)
    {
        $post_type = sanitize_key((string) $request['post_type']);
        $post_id = (int) $request['post_id'];
        $post = get_post($post_id);
        $groups = Field_Registry::theme_cpt_meta_groups();

        if (!isset($groups[$post_type])) {
            return new \WP_Error('caa_invalid_cpt_meta_type', 'Unsupported theme CPT type.', ['status' => 404]);
        }

        if (!$post || $post->post_type !== $post_type) {
            return new \WP_Error('caa_post_not_found', 'Post not found for requested post type.', ['status' => 404]);
        }

        return Route_Utils::write_post_meta(
            $request,
            $post_id,
            Route_Utils::updates_from_request($request),
            (array) ($groups[$post_type]['fields'] ?? []),
            (array) ($groups[$post_type]['prefixes'] ?? []),
            'write:theme',
            'theme_cpt_meta'
        );
    }

    public static function get_taxonomy_meta(WP_REST_Request $request)
    {
        $taxonomy = sanitize_key((string) $request['taxonomy']);
        $term_id = (int) $request['term_id'];
        $groups = Field_Registry::theme_taxonomy_meta_groups();

        if (!isset($groups[$taxonomy])) {
            return new \WP_Error('caa_invalid_taxonomy_meta_type', 'Unsupported taxonomy meta type.', ['status' => 404]);
        }

        $term = get_term($term_id, $taxonomy);
        if (!$term || is_wp_error($term)) {
            return new \WP_Error('caa_term_not_found', 'Term not found.', ['status' => 404]);
        }

        $fields = (array) ($groups[$taxonomy]['fields'] ?? []);
        return rest_ensure_response([
            'success' => true,
            'term_id' => $term_id,
            'taxonomy' => $taxonomy,
            'fields' => $fields,
            'data' => Route_Utils::read_term_meta_values($term_id, $fields),
        ]);
    }

    public static function set_taxonomy_meta(WP_REST_Request $request)
    {
        $taxonomy = sanitize_key((string) $request['taxonomy']);
        $term_id = (int) $request['term_id'];
        $groups = Field_Registry::theme_taxonomy_meta_groups();

        if (!isset($groups[$taxonomy])) {
            return new \WP_Error('caa_invalid_taxonomy_meta_type', 'Unsupported taxonomy meta type.', ['status' => 404]);
        }

        $term = get_term($term_id, $taxonomy);
        if (!$term || is_wp_error($term)) {
            return new \WP_Error('caa_term_not_found', 'Term not found.', ['status' => 404]);
        }

        return Route_Utils::write_term_meta(
            $request,
            $term_id,
            Route_Utils::updates_from_request($request),
            (array) ($groups[$taxonomy]['fields'] ?? []),
            'write:theme',
            'theme_taxonomy_meta'
        );
    }

    public static function describe(): array
    {
        return [
            'customizer_groups' => self::describe_customizer_groups(),
            'page_meta_groups' => Field_Registry::theme_page_meta_groups(),
            'cpt_meta_groups' => Field_Registry::theme_cpt_meta_groups(),
            'taxonomy_meta_groups' => Field_Registry::theme_taxonomy_meta_groups(),
        ];
    }

    private static function describe_customizer_groups(): array
    {
        $out = [];
        foreach (Field_Registry::theme_customizer_groups() as $group => $config) {
            $out[$group] = [
                'storage' => (string) ($config['storage'] ?? 'theme_mod'),
                'fields' => (array) ($config['fields'] ?? []),
                'json_fields' => (array) ($config['json_fields'] ?? []),
            ];
        }
        return $out;
    }

    private static function page_meta_policy(WP_REST_Request $request): array
    {
        $requested_group = sanitize_key((string) ($request->get_param('group') ?? ''));
        $registry = Field_Registry::theme_page_meta_groups();
        $groups = $requested_group !== '' && isset($registry[$requested_group])
            ? [$requested_group => $registry[$requested_group]]
            : $registry;

        $keys = [];
        $prefixes = [];
        foreach ($groups as $config) {
            $keys = array_merge($keys, (array) ($config['fields'] ?? []));
            $prefixes = array_merge($prefixes, (array) ($config['prefixes'] ?? []));
        }

        $keys = array_values(array_unique($keys));
        $prefixes = array_values(array_unique($prefixes));
        sort($keys);
        sort($prefixes);

        return [$keys, $prefixes, array_keys($groups)];
    }

    private static function read_prefix_meta(int $post_id, array $prefixes): array
    {
        if (empty($prefixes)) {
            return [];
        }

        $all = get_post_meta($post_id);
        $data = [];
        foreach ($all as $key => $values) {
            foreach ($prefixes as $prefix) {
                if ($prefix !== '' && strpos((string) $key, (string) $prefix) === 0) {
                    $data[$key] = count((array) $values) === 1 ? maybe_unserialize($values[0]) : array_map('maybe_unserialize', (array) $values);
                    break;
                }
            }
        }
        return $data;
    }
}

<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Diff;
use ChromaAgentAPI\Field_Registry;
use ChromaAgentAPI\Route_Utils;
use ChromaAgentAPI\Utils;
use WP_Query;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class School_Routes
{
    private const NS = 'chroma-agent/v1';

    public static function register(): void
    {
        register_rest_route(self::NS, '/schools', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'list_schools'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/schools/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_school'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'update_school'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/schools/(?P<id>\d+)/tv', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_school_tv'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'update_school_tv'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/schools/(?P<id>\d+)/config', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_school_config'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'update_school_config'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/schools/weather', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_weather_probe'],
            'permission_callback' => [__CLASS__, 'read_permission'],
        ]);
    }

    public static function read_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['read:schools']);
    }

    public static function write_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['write:schools']);
    }

    public static function list_schools(WP_REST_Request $request)
    {
        $page = max(1, (int) $request->get_param('page'));
        $per_page = min(100, max(1, (int) ($request->get_param('per_page') ?: 20)));
        $query = new WP_Query([
            'post_type' => 'chroma_school',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => sanitize_text_field((string) $request->get_param('search')),
            'orderby' => 'title',
            'order' => 'ASC',
            'no_found_rows' => false,
        ]);

        $items = [];
        foreach ((array) $query->posts as $post) {
            $items[] = self::prepare_school_summary($post);
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => (int) $query->found_posts,
                'total_pages' => (int) $query->max_num_pages,
            ],
        ]);
    }

    public static function get_school(WP_REST_Request $request)
    {
        $school = self::require_school((int) $request['id']);
        if (is_wp_error($school)) {
            return $school;
        }

        return rest_ensure_response([
            'success' => true,
            'data' => self::prepare_school_detail($school),
        ]);
    }

    public static function update_school(WP_REST_Request $request)
    {
        $school = self::require_school((int) $request['id']);
        if (is_wp_error($school)) {
            return $school;
        }

        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $before = self::prepare_school_detail($school);

        $postarr = ['ID' => (int) $school->ID];
        if (array_key_exists('title', $payload)) {
            $postarr['post_title'] = sanitize_text_field((string) $payload['title']);
        }
        if (array_key_exists('slug', $payload)) {
            $postarr['post_name'] = sanitize_title((string) $payload['slug']);
        }
        if (array_key_exists('status', $payload)) {
            $postarr['post_status'] = sanitize_key((string) $payload['status']);
        }

        $option_updates = [];
        foreach (Field_Registry::school_options() as $key) {
            if (array_key_exists($key, $payload)) {
                $option_updates[$key] = $payload[$key];
            }
        }

        if ($dry_run) {
            $after = $before;
            if (isset($postarr['post_title'])) {
                $after['title'] = $postarr['post_title'];
            }
            if (isset($postarr['post_name'])) {
                $after['slug'] = $postarr['post_name'];
            }
            if (!empty($option_updates)) {
                $after['global_options'] = array_merge($after['global_options'], $option_updates);
            }
            $diff = Diff::compare($before, $after);
            Route_Utils::log_write($request, 'write:schools', 'school', (string) $school->ID, true, $before, $after, $diff);
            return rest_ensure_response(['success' => true, 'dry_run' => true, 'diff' => $diff, 'data' => $after]);
        }

        if (count($postarr) > 1) {
            $updated = wp_update_post($postarr, true);
            if (is_wp_error($updated)) {
                return $updated;
            }
        }

        foreach ($option_updates as $key => $value) {
            update_option($key, Route_Utils::sanitize_value_for_storage($key, $value), false);
        }

        $after = self::prepare_school_detail(get_post((int) $school->ID));
        $diff = Diff::compare($before, $after);
        Route_Utils::log_write($request, 'write:schools', 'school', (string) $school->ID, false, $before, $after, $diff);
        return rest_ensure_response(['success' => true, 'diff' => $diff, 'data' => $after]);
    }

    public static function get_school_tv(WP_REST_Request $request)
    {
        $school = self::require_school((int) $request['id']);
        if (is_wp_error($school)) {
            return $school;
        }

        $content = [];
        foreach (Field_Registry::school_content_keys() as $key) {
            $content[$key] = get_post_meta((int) $school->ID, '_chroma_school_' . $key, true);
        }

        return rest_ensure_response(['success' => true, 'school_id' => (int) $school->ID, 'data' => $content]);
    }

    public static function update_school_tv(WP_REST_Request $request)
    {
        $school = self::require_school((int) $request['id']);
        if (is_wp_error($school)) {
            return $school;
        }

        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $updates = isset($payload['updates']) && is_array($payload['updates']) ? $payload['updates'] : $payload;
        unset($updates['dry_run'], $updates['strict_write'], $updates['updates']);

        $before = [];
        $after = [];
        $blocked = [];

        foreach ($updates as $key => $value) {
            $normalized = sanitize_key((string) $key);
            if (!in_array($normalized, Field_Registry::school_content_keys(), true)) {
                $blocked[] = $normalized;
                continue;
            }

            $meta_key = '_chroma_school_' . $normalized;
            $before[$normalized] = get_post_meta((int) $school->ID, $meta_key, true);
            $after[$normalized] = Route_Utils::sanitize_value_for_storage($meta_key, $value);

            if (!$dry_run) {
                update_post_meta((int) $school->ID, $meta_key, $after[$normalized]);
            }
        }

        if (!$dry_run) {
            update_post_meta((int) $school->ID, '_chroma_school_last_updated', time());
            if (function_exists('chroma_invalidate_cache_group')) {
                chroma_invalidate_cache_group('schools');
            }
        }

        $diff = Diff::compare($before, $after);
        Route_Utils::log_write($request, 'write:schools', 'school_tv', (string) $school->ID, $dry_run, $before, $after, $diff);

        return rest_ensure_response([
            'success' => true,
            'dry_run' => $dry_run,
            'blocked_keys' => $blocked,
            'diff' => $diff,
            'data' => $dry_run ? $after : self::get_school_tv($request)->get_data()['data'],
        ]);
    }

    public static function get_school_config(WP_REST_Request $request)
    {
        $school = self::require_school((int) $request['id']);
        if (is_wp_error($school)) {
            return $school;
        }

        return rest_ensure_response([
            'success' => true,
            'school_id' => (int) $school->ID,
            'data' => [
                'config' => get_post_meta((int) $school->ID, '_chroma_school_config', true),
                'director_email' => get_post_meta((int) $school->ID, '_chroma_school_director_email', true),
                'global_options' => Route_Utils::read_options(Field_Registry::school_options(), false),
            ],
        ]);
    }

    public static function update_school_config(WP_REST_Request $request)
    {
        $school = self::require_school((int) $request['id']);
        if (is_wp_error($school)) {
            return $school;
        }

        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $config = is_array($payload['config'] ?? null) ? $payload['config'] : [];
        $director_email = array_key_exists('director_email', $payload)
            ? sanitize_email((string) $payload['director_email'])
            : sanitize_email((string) ($config['director_email'] ?? get_post_meta((int) $school->ID, '_chroma_school_director_email', true)));

        $sanitized_config = [
            'director_email' => $director_email,
            'timezone' => sanitize_text_field((string) ($config['timezone'] ?? '')),
            'lat' => sanitize_text_field((string) ($config['lat'] ?? '')),
            'lon' => sanitize_text_field((string) ($config['lon'] ?? '')),
        ];

        $before = [
            'config' => get_post_meta((int) $school->ID, '_chroma_school_config', true),
            'director_email' => get_post_meta((int) $school->ID, '_chroma_school_director_email', true),
        ];
        $after = [
            'config' => $sanitized_config,
            'director_email' => $director_email,
        ];

        if (!$dry_run) {
            update_post_meta((int) $school->ID, '_chroma_school_config', $sanitized_config);
            update_post_meta((int) $school->ID, '_chroma_school_director_email', $director_email);
            update_post_meta((int) $school->ID, '_chroma_school_last_updated', time());
        }

        $diff = Diff::compare($before, $after);
        Route_Utils::log_write($request, 'write:schools', 'school_config', (string) $school->ID, $dry_run, $before, $after, $diff);
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'diff' => $diff, 'data' => $dry_run ? $after : self::get_school_config($request)->get_data()['data']]);
    }

    public static function get_weather_probe(WP_REST_Request $request)
    {
        $school_id = (int) $request->get_param('school_id');
        if ($school_id > 0) {
            $school = self::require_school($school_id);
            if (is_wp_error($school)) {
                return $school;
            }
            $config = get_post_meta((int) $school->ID, '_chroma_school_config', true);
            return rest_ensure_response([
                'success' => true,
                'data' => [
                    'school_id' => (int) $school->ID,
                    'lat' => $config['lat'] ?? null,
                    'lon' => $config['lon'] ?? null,
                    'timezone' => $config['timezone'] ?? null,
                ],
            ]);
        }

        return rest_ensure_response(['success' => true, 'data' => ['message' => 'Provide school_id to inspect stored weather coordinates.']]);
    }

    public static function describe(): array
    {
        return [
            'content_keys' => Field_Registry::school_content_keys(),
            'meta_fields' => Field_Registry::school_meta_fields(),
            'global_options' => Field_Registry::school_options(),
            'routes' => [
                '/schools',
                '/schools/{id}',
                '/schools/{id}/tv',
                '/schools/{id}/config',
                '/schools/weather',
            ],
        ];
    }

    private static function require_school(int $school_id)
    {
        $post = get_post($school_id);
        if (!$post || $post->post_type !== 'chroma_school') {
            return new \WP_Error('caa_school_not_found', 'School not found.', ['status' => 404]);
        }
        return $post;
    }

    private static function prepare_school_summary($post): array
    {
        $config = get_post_meta((int) $post->ID, '_chroma_school_config', true);
        return [
            'id' => (int) $post->ID,
            'title' => (string) $post->post_title,
            'slug' => (string) $post->post_name,
            'status' => (string) $post->post_status,
            'director_email' => get_post_meta((int) $post->ID, '_chroma_school_director_email', true),
            'timezone' => $config['timezone'] ?? null,
        ];
    }

    private static function prepare_school_detail($post): array
    {
        $detail = self::prepare_school_summary($post);
        $detail['config'] = get_post_meta((int) $post->ID, '_chroma_school_config', true);
        $detail['content'] = [];
        foreach (Field_Registry::school_content_keys() as $key) {
            $detail['content'][$key] = get_post_meta((int) $post->ID, '_chroma_school_' . $key, true);
        }
        $detail['global_options'] = Route_Utils::read_options(Field_Registry::school_options(), false);
        return $detail;
    }
}

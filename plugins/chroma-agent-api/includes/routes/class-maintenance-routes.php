<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Diff;
use ChromaAgentAPI\Route_Utils;
use ChromaAgentAPI\Snapshot_Store;
use ChromaAgentAPI\Utils;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class Maintenance_Routes
{
    private const NS = 'chroma-agent/v1';

    public static function register(): void
    {
        register_rest_route(self::NS, '/media/(?P<id>\d+)/metadata', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_media_metadata'],
                'permission_callback' => [__CLASS__, 'read_media_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_media_metadata'],
                'permission_callback' => [__CLASS__, 'write_media_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/cache/flush', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'flush_cache'],
            'permission_callback' => [__CLASS__, 'write_maintenance_permission'],
        ]);

        register_rest_route(self::NS, '/sitemaps/refresh', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'refresh_sitemaps'],
            'permission_callback' => [__CLASS__, 'write_maintenance_permission'],
        ]);

        register_rest_route(self::NS, '/geo-feed/refresh', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'refresh_geo_feed'],
            'permission_callback' => [__CLASS__, 'write_maintenance_permission'],
        ]);
    }

    public static function read_media_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['read:media']);
    }

    public static function write_media_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['write:media']);
    }

    public static function write_maintenance_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['write:maintenance']);
    }

    public static function get_media_metadata(WP_REST_Request $request)
    {
        $attachment = self::require_attachment((int) $request['id']);
        if (is_wp_error($attachment)) {
            return $attachment;
        }

        return rest_ensure_response([
            'success' => true,
            'data' => self::read_attachment((int) $attachment->ID),
        ]);
    }

    public static function set_media_metadata(WP_REST_Request $request)
    {
        $attachment = self::require_attachment((int) $request['id']);
        if (is_wp_error($attachment)) {
            return $attachment;
        }

        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $before = self::read_attachment((int) $attachment->ID);
        $after = $before;
        $snapshot_ids = [];

        $post_update = ['ID' => (int) $attachment->ID];
        if (array_key_exists('title', $payload)) {
            $post_update['post_title'] = sanitize_text_field((string) $payload['title']);
            $after['title'] = $post_update['post_title'];
        }
        if (array_key_exists('caption', $payload)) {
            $post_update['post_excerpt'] = sanitize_textarea_field((string) $payload['caption']);
            $after['caption'] = $post_update['post_excerpt'];
        }
        if (array_key_exists('description', $payload)) {
            $post_update['post_content'] = wp_kses_post((string) $payload['description']);
            $after['description'] = $post_update['post_content'];
        }
        if (array_key_exists('alt', $payload)) {
            $after['alt'] = sanitize_text_field((string) $payload['alt']);
        }
        if (array_key_exists('post_parent', $payload)) {
            $after['post_parent'] = (int) $payload['post_parent'];
        }
        if (array_key_exists('featured_post_id', $payload)) {
            $after['featured_post_id'] = (int) $payload['featured_post_id'];
        }

        if (!$dry_run) {
            if (count($post_update) > 1) {
                foreach ([
                    'post_title' => 'title',
                    'post_excerpt' => 'caption',
                    'post_content' => 'description',
                ] as $post_field => $response_field) {
                    if (array_key_exists($post_field, $post_update) && $before[$response_field] !== $after[$response_field]) {
                        $snapshot_ids[] = Snapshot_Store::create_snapshot(
                            Auth::current_key_id(),
                            'write:media',
                            'attachment_field',
                            (int) $attachment->ID . ':' . $post_field,
                            $before[$response_field],
                            $after[$response_field]
                        );
                    }
                }
                wp_update_post($post_update);
            }
            if (array_key_exists('alt', $payload)) {
                if ($before['alt'] !== $after['alt']) {
                    $snapshot_ids[] = Snapshot_Store::create_snapshot(Auth::current_key_id(), 'write:media', 'post_meta', (int) $attachment->ID . ':_wp_attachment_image_alt', $before['alt'], $after['alt']);
                }
                update_post_meta((int) $attachment->ID, '_wp_attachment_image_alt', $after['alt']);
            }
            if (array_key_exists('post_parent', $payload)) {
                if ($before['post_parent'] !== $after['post_parent']) {
                    $snapshot_ids[] = Snapshot_Store::create_snapshot(
                        Auth::current_key_id(),
                        'write:media',
                        'attachment_field',
                        (int) $attachment->ID . ':post_parent',
                        $before['post_parent'],
                        $after['post_parent']
                    );
                }
                wp_update_post(['ID' => (int) $attachment->ID, 'post_parent' => $after['post_parent']]);
            }
            if (array_key_exists('featured_post_id', $payload) && $after['featured_post_id'] > 0) {
                $old_thumbnail_id = (int) get_post_thumbnail_id($after['featured_post_id']);
                if ($old_thumbnail_id !== (int) $attachment->ID) {
                    $snapshot_ids[] = Snapshot_Store::create_snapshot(
                        Auth::current_key_id(),
                        'write:media',
                        'post_meta',
                        $after['featured_post_id'] . ':_thumbnail_id',
                        $old_thumbnail_id > 0 ? $old_thumbnail_id : null,
                        (int) $attachment->ID
                    );
                }
                set_post_thumbnail($after['featured_post_id'], (int) $attachment->ID);
            }
            $after = self::read_attachment((int) $attachment->ID);
        }

        $diff = Diff::compare($before, $after);
        Route_Utils::log_write($request, 'write:media', 'media_metadata', (string) $attachment->ID, $dry_run, $before, $after, $diff);
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'snapshot_ids' => $snapshot_ids, 'diff' => $diff, 'data' => $after]);
    }

    public static function flush_cache(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $group = sanitize_key((string) ($payload['group'] ?? ''));
        $before = ['group' => $group];

        if (!$dry_run) {
            if ($group !== '' && function_exists('chroma_invalidate_cache_group')) {
                chroma_invalidate_cache_group($group);
            } elseif (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }

            if (function_exists('chroma_clear_query_cache')) {
                chroma_clear_query_cache();
            }
        }

        $after = ['flushed' => true, 'group' => $group];
        $diff = Diff::compare($before, $after);
        Route_Utils::log_write($request, 'write:maintenance', 'cache_flush', $group !== '' ? $group : 'all', $dry_run, $before, $after, $diff);
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'snapshot_ids' => [], 'diff' => $diff, 'data' => $after]);
    }

    public static function refresh_sitemaps(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $before = ['rewrite_rules' => 'stale'];

        if (!$dry_run) {
            flush_rewrite_rules(false);
        }

        $after = ['rewrite_rules' => 'flushed'];
        $diff = Diff::compare($before, $after);
        Route_Utils::log_write($request, 'write:maintenance', 'sitemaps_refresh', 'global', $dry_run, $before, $after, $diff);
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'snapshot_ids' => [], 'diff' => $diff, 'data' => $after]);
    }

    public static function refresh_geo_feed(WP_REST_Request $request)
    {
        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $before = ['geo_feed_cache' => 'stale'];

        if (!$dry_run) {
            delete_transient('chroma_agent_geo_feed_v3');
            delete_transient('chroma_geo_feed');
        }

        $after = [
            'geo_feed_cache' => 'cleared',
            'cache_keys' => ['chroma_agent_geo_feed_v3', 'chroma_geo_feed'],
        ];
        $diff = Diff::compare($before, $after);
        Route_Utils::log_write($request, 'write:maintenance', 'geo_feed_refresh', 'global', $dry_run, $before, $after, $diff);
        return rest_ensure_response(['success' => true, 'dry_run' => $dry_run, 'snapshot_ids' => [], 'diff' => $diff, 'data' => $after]);
    }

    public static function describe(): array
    {
        return [
            'routes' => [
                '/media/{id}/metadata',
                '/cache/flush',
                '/sitemaps/refresh',
                '/geo-feed/refresh',
            ],
        ];
    }

    private static function require_attachment(int $attachment_id)
    {
        $post = get_post($attachment_id);
        if (!$post || $post->post_type !== 'attachment') {
            return new \WP_Error('caa_attachment_not_found', 'Attachment not found.', ['status' => 404]);
        }
        return $post;
    }

    private static function read_attachment(int $attachment_id): array
    {
        $post = get_post($attachment_id);
        return [
            'id' => $attachment_id,
            'title' => (string) $post->post_title,
            'caption' => (string) $post->post_excerpt,
            'description' => (string) $post->post_content,
            'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'post_parent' => (int) $post->post_parent,
            'url' => wp_get_attachment_url($attachment_id),
        ];
    }
}

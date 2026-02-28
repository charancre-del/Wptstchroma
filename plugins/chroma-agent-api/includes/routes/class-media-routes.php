<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Audit_Log;
use ChromaAgentAPI\Diff;
use ChromaAgentAPI\Utils;
use WP_Query;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

class Media_Routes
{
    private const NS = 'chroma-agent/v1';

    public static function register(): void
    {
        register_rest_route(self::NS, '/media', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'list_media'],
                'permission_callback' => [__CLASS__, 'read_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [__CLASS__, 'upload_media'],
                'permission_callback' => [__CLASS__, 'write_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/media/attach', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'attach_media'],
            'permission_callback' => [__CLASS__, 'write_permission'],
        ]);
    }

    public static function read_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['read:media']);
    }

    public static function write_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['write:media']);
    }

    public static function list_media(WP_REST_Request $request)
    {
        $page = max(1, (int) $request->get_param('page'));
        $per_page = (int) $request->get_param('per_page');
        if ($per_page <= 0) {
            $per_page = 20;
        }
        $per_page = min(100, $per_page);

        $q = new WP_Query([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            's' => sanitize_text_field((string) $request->get_param('search')),
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => false,
        ]);

        $items = [];
        foreach ((array) $q->posts as $attachment) {
            $items[] = [
                'id' => (int) $attachment->ID,
                'title' => (string) $attachment->post_title,
                'mime_type' => (string) $attachment->post_mime_type,
                'url' => wp_get_attachment_url((int) $attachment->ID),
                'post_parent' => (int) $attachment->post_parent,
                'date_gmt' => (string) $attachment->post_date_gmt,
            ];
        }

        return rest_ensure_response([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => (int) $q->found_posts,
                'total_pages' => (int) $q->max_num_pages,
            ],
        ]);
    }

    public static function upload_media(WP_REST_Request $request)
    {
        $payload = $request->get_params();
        $dry_run = Utils::truthy($payload['dry_run'] ?? false);

        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new \WP_Error('caa_file_required', 'Upload requires multipart file field named "file".', ['status' => 400]);
        }

        $file = $files['file'];
        $name = sanitize_file_name((string) ($file['name'] ?? 'upload.bin'));

        if ($dry_run) {
            $after = [
                'filename' => $name,
                'size' => isset($file['size']) ? (int) $file['size'] : 0,
                'mime_type' => sanitize_text_field((string) ($file['type'] ?? 'application/octet-stream')),
            ];

            Audit_Log::log_write([
                'actor_key_id' => Auth::current_key_id(),
                'scope' => 'write:media',
                'method' => $request->get_method(),
                'route' => $request->get_route(),
                'target_type' => 'media',
                'target_id' => 'new',
                'dry_run' => true,
                'before' => null,
                'after' => $after,
                'diff' => ['upload' => true],
                'status_code' => 200,
            ]);

            return rest_ensure_response(['success' => true, 'dry_run' => true, 'data' => $after]);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $upload = wp_handle_upload($file, ['test_form' => false]);
        if (isset($upload['error'])) {
            return new \WP_Error('caa_upload_failed', sanitize_text_field((string) $upload['error']), ['status' => 500]);
        }

        $attachment = [
            'post_mime_type' => sanitize_text_field((string) ($upload['type'] ?? 'application/octet-stream')),
            'post_title' => sanitize_text_field(pathinfo($name, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        $attachment_id = wp_insert_attachment($attachment, $upload['file']);
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        $meta = wp_generate_attachment_metadata((int) $attachment_id, $upload['file']);
        wp_update_attachment_metadata((int) $attachment_id, $meta);

        $after = [
            'id' => (int) $attachment_id,
            'url' => wp_get_attachment_url((int) $attachment_id),
            'mime_type' => get_post_mime_type((int) $attachment_id),
            'filename' => basename((string) $upload['file']),
        ];

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'write:media',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'media',
            'target_id' => (string) $attachment_id,
            'dry_run' => false,
            'before' => null,
            'after' => $after,
            'diff' => ['upload' => true],
            'status_code' => 201,
        ]);

        return new \WP_REST_Response(['success' => true, 'data' => $after], 201);
    }

    public static function attach_media(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload)) {
            $payload = $request->get_params();
        }

        $dry_run = Utils::truthy($payload['dry_run'] ?? false);
        $post_id = isset($payload['post_id']) ? (int) $payload['post_id'] : 0;
        $attachment_id = isset($payload['attachment_id']) ? (int) $payload['attachment_id'] : 0;

        if ($post_id <= 0 || $attachment_id <= 0) {
            return new \WP_Error('caa_invalid_attach_input', 'post_id and attachment_id are required.', ['status' => 400]);
        }

        $post = get_post($post_id);
        $attachment = get_post($attachment_id);

        if (!$post || !$attachment || $attachment->post_type !== 'attachment') {
            return new \WP_Error('caa_invalid_attach_target', 'Invalid post or attachment.', ['status' => 404]);
        }

        $before = ['post_parent' => (int) $attachment->post_parent];
        $after = ['post_parent' => $post_id];
        $diff = Diff::compare($before, $after);

        if (!$dry_run) {
            wp_update_post([
                'ID' => $attachment_id,
                'post_parent' => $post_id,
            ]);
        }

        Audit_Log::log_write([
            'actor_key_id' => Auth::current_key_id(),
            'scope' => 'write:media',
            'method' => $request->get_method(),
            'route' => $request->get_route(),
            'target_type' => 'media',
            'target_id' => (string) $attachment_id,
            'dry_run' => $dry_run,
            'before' => $before,
            'after' => $after,
            'diff' => $diff,
            'status_code' => 200,
        ]);

        return rest_ensure_response([
            'success' => true,
            'dry_run' => $dry_run,
            'data' => [
                'attachment_id' => $attachment_id,
                'post_id' => $post_id,
                'attached' => true,
            ],
        ]);
    }
}

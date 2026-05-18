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

class Form_Routes
{
    private const NS = 'chroma-agent/v1';

    public static function register(): void
    {
        register_rest_route(self::NS, '/forms/(?P<form>[a-z0-9-]+)/settings', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_form_settings'],
                'permission_callback' => [__CLASS__, 'read_forms_permission'],
            ],
            [
                'methods' => 'PATCH,POST',
                'callback' => [__CLASS__, 'set_form_settings'],
                'permission_callback' => [__CLASS__, 'write_forms_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/leads', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'list_leads'],
                'permission_callback' => [__CLASS__, 'read_leads_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/leads/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [__CLASS__, 'get_lead'],
                'permission_callback' => [__CLASS__, 'read_leads_permission'],
            ],
        ]);

        register_rest_route(self::NS, '/leads/(?P<id>\d+)/retry-webhook', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'retry_lead_webhook'],
            'permission_callback' => [__CLASS__, 'write_leads_permission'],
        ]);
    }

    public static function read_forms_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['read:forms']);
    }

    public static function write_forms_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['write:forms']);
    }

    public static function read_leads_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['read:leads']);
    }

    public static function write_leads_permission(WP_REST_Request $request)
    {
        return Auth::authorize($request, ['write:leads']);
    }

    public static function get_form_settings(WP_REST_Request $request)
    {
        [$canonical, $config] = self::resolve_form_group((string) $request['form']);
        if ($config === null) {
            return new \WP_Error('caa_invalid_form_group', 'Unknown form group.', ['status' => 404]);
        }

        $fields = (array) ($config['fields'] ?? []);
        return rest_ensure_response([
            'success' => true,
            'form' => $canonical,
            'fields' => $fields,
            'secret_fields' => array_values(array_intersect($fields, Field_Registry::secret_option_keys())),
            'data' => Route_Utils::read_options($fields),
        ]);
    }

    public static function set_form_settings(WP_REST_Request $request)
    {
        [$canonical, $config] = self::resolve_form_group((string) $request['form']);
        if ($config === null) {
            return new \WP_Error('caa_invalid_form_group', 'Unknown form group.', ['status' => 404]);
        }

        return Route_Utils::write_options(
            $request,
            Route_Utils::updates_from_request($request),
            (array) ($config['fields'] ?? []),
            'write:forms',
            'form_settings'
        );
    }

    public static function list_leads(WP_REST_Request $request)
    {
        $page = max(1, (int) $request->get_param('page'));
        $per_page = min(100, max(1, (int) ($request->get_param('per_page') ?: 20)));
        $type = sanitize_key((string) $request->get_param('lead_type'));

        $args = [
            'post_type' => 'lead_log',
            'post_status' => ['publish', 'draft', 'private'],
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => false,
        ];
        if ($type !== '') {
            $args['meta_query'] = [
                ['key' => 'lead_type', 'value' => $type],
            ];
        }

        $query = new WP_Query($args);
        $items = [];
        foreach ((array) $query->posts as $post) {
            $items[] = self::prepare_lead($post, false);
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

    public static function get_lead(WP_REST_Request $request)
    {
        $post = self::require_lead((int) $request['id']);
        if (is_wp_error($post)) {
            return $post;
        }

        return rest_ensure_response(['success' => true, 'data' => self::prepare_lead($post, true)]);
    }

    public static function retry_lead_webhook(WP_REST_Request $request)
    {
        $post = self::require_lead((int) $request['id']);
        if (is_wp_error($post)) {
            return $post;
        }

        $payload = Route_Utils::payload($request);
        $dry_run = Route_Utils::dry_run($payload);
        $before = ['webhook_sent' => get_post_meta((int) $post->ID, '_chroma_webhook_sent', true)];

        if (!$dry_run) {
            delete_post_meta((int) $post->ID, '_chroma_webhook_sent');
            if (function_exists('chroma_lead_log_trigger_webhook')) {
                chroma_lead_log_trigger_webhook((int) $post->ID, $post, true);
            }
        }

        $after = ['webhook_sent' => get_post_meta((int) $post->ID, '_chroma_webhook_sent', true)];
        $diff = Diff::compare($before, $after);
        Route_Utils::log_write($request, 'write:leads', 'lead_retry_webhook', (string) $post->ID, $dry_run, $before, $after, $diff);

        return rest_ensure_response([
            'success' => true,
            'dry_run' => $dry_run,
            'data' => [
                'lead_id' => (int) $post->ID,
                'retried' => true,
                'webhook_sent' => $after['webhook_sent'],
            ],
        ]);
    }

    public static function describe(): array
    {
        return [
            'form_groups' => Field_Registry::form_groups(),
            'lead_meta_fields' => Field_Registry::lead_meta_fields(),
            'routes' => [
                '/forms/{form}/settings',
                '/leads',
                '/leads/{id}',
                '/leads/{id}/retry-webhook',
            ],
        ];
    }

    private static function resolve_form_group(string $requested): array
    {
        $requested = sanitize_key($requested);
        foreach (Field_Registry::form_groups() as $name => $config) {
            $aliases = array_merge([$name], (array) ($config['aliases'] ?? []));
            $aliases = array_map('sanitize_key', $aliases);
            if (in_array($requested, $aliases, true)) {
                return [$name, $config];
            }
        }

        return [$requested, null];
    }

    private static function require_lead(int $lead_id)
    {
        $post = get_post($lead_id);
        if (!$post || $post->post_type !== 'lead_log') {
            return new \WP_Error('caa_lead_not_found', 'Lead not found.', ['status' => 404]);
        }
        return $post;
    }

    private static function prepare_lead($post, bool $include_detail): array
    {
        $data = [
            'id' => (int) $post->ID,
            'title' => (string) $post->post_title,
            'lead_type' => get_post_meta((int) $post->ID, 'lead_type', true),
            'lead_name' => get_post_meta((int) $post->ID, 'lead_name', true),
            'lead_email' => get_post_meta((int) $post->ID, 'lead_email', true),
            'lead_phone' => get_post_meta((int) $post->ID, 'lead_phone', true),
            'created_gmt' => (string) $post->post_date_gmt,
            'webhook_sent' => Utils::truthy(get_post_meta((int) $post->ID, '_chroma_webhook_sent', true)),
        ];

        if ($include_detail) {
            $data['meta'] = Route_Utils::read_post_meta_values((int) $post->ID, Field_Registry::lead_meta_fields());
            $payload = $data['meta']['lead_payload'] ?? '';
            if (is_string($payload) && $payload !== '') {
                $decoded = json_decode($payload, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data['meta']['lead_payload'] = $decoded;
                }
            }
        }

        return $data;
    }
}

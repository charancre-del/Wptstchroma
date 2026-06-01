<?php

namespace ChromaAgentAPI\Routes;

use ChromaAgentAPI\Auth;
use ChromaAgentAPI\Field_Registry;
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
                'routes' => self::route_catalog(),
                'route_families' => [
                    'theme_extensions' => Theme_Extension_Routes::describe(),
                    'seo_operations' => SEO_Operation_Routes::describe(),
                    'portal' => Portal_Routes::describe(),
                    'schools' => School_Routes::describe(),
                    'forms_leads' => Form_Routes::describe(),
                    'maintenance' => Maintenance_Routes::describe(),
                ],
                'scopes' => Auth::current_key()['scopes'] ?? [],
                'known_scopes' => Field_Registry::all_scopes(),
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
                'theme_extension_contract' => Theme_Extension_Routes::describe(),
                'seo_operations_contract' => SEO_Operation_Routes::describe(),
                'portal_contract' => Portal_Routes::describe(),
                'school_contract' => School_Routes::describe(),
                'form_contract' => Form_Routes::describe(),
                'maintenance_contract' => Maintenance_Routes::describe(),
                'route_catalog' => self::route_catalog(),
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

    private static function route_catalog(): array
    {
        $theme_customizer_fields = [];
        foreach (Field_Registry::theme_customizer_groups() as $group) {
            $theme_customizer_fields = array_merge($theme_customizer_fields, (array) ($group['fields'] ?? []));
        }
        $theme_page_fields = [];
        foreach (Field_Registry::theme_page_meta_groups() as $group) {
            $theme_page_fields = array_merge($theme_page_fields, (array) ($group['fields'] ?? []));
        }
        $theme_cpt_fields = [];
        foreach (Field_Registry::theme_cpt_meta_groups() as $group) {
            $theme_cpt_fields = array_merge($theme_cpt_fields, (array) ($group['fields'] ?? []));
        }
        $theme_taxonomy_fields = [];
        foreach (Field_Registry::theme_taxonomy_meta_groups() as $group) {
            $theme_taxonomy_fields = array_merge($theme_taxonomy_fields, (array) ($group['fields'] ?? []));
        }

        $content_policy = Content_Routes::describe_meta_write_policy();
        $content_blocked = array_merge(
            array_column((array) ($content_policy['blocked_exact'] ?? []), 'meta_key'),
            array_map(static function ($item) {
                return (string) ($item['prefix'] ?? '');
            }, (array) ($content_policy['blocked_prefixes'] ?? []))
        );

        $schema_fields = [
            '_chroma_post_schemas',
            '_chroma_schema_override',
            '_chroma_schema_type',
            '_chroma_schema_data',
            '_chroma_schema_confidence',
            '_chroma_needs_review',
            '_chroma_review_reason',
            '_chroma_schema_history',
            '_chroma_schema_validation_status',
            '_chroma_schema_errors',
        ];
        $seo_option_fields = [];
        foreach (Field_Registry::seo_option_groups() as $group) {
            $seo_option_fields = array_merge($seo_option_fields, (array) ($group['fields'] ?? []));
        }

        return [
            self::route_entry('GET', '/discovery', [], [], []),
            self::route_entry('GET', '/resources', [], [], []),
            self::route_entry('GET', '/write-policy', [], [], []),
            self::route_entry('GET', '/geo-contract', [], [], []),

            self::route_entry('GET,POST', '/keys', ['admin:keys'], ['name', 'scopes', 'status'], ['secret']),
            self::route_entry('POST', '/keys/{id}/revoke', ['admin:keys'], [], ['secret']),
            self::route_entry('POST', '/keys/{id}/rotate', ['admin:keys'], [], ['secret']),

            self::route_entry('GET,POST', '/content', ['read:content', 'write:content'], ['post_type', 'title', 'content', 'excerpt', 'status', 'slug', 'meta'], $content_blocked),
            self::route_entry('GET,PATCH,POST,PUT,DELETE', '/content/{id}', ['read:content', 'write:content'], ['title', 'content', 'excerpt', 'status', 'slug', 'meta'], $content_blocked),
            self::route_entry('POST', '/content/{id}/rollback', ['write:content'], ['revision_id'], $content_blocked),

            self::route_entry('GET,PATCH,POST', '/theme/options', ['read:theme', 'write:theme'], Utils::get_theme_option_allowlist(), []),
            self::route_entry('GET,PATCH,POST', '/theme/mods', ['read:theme', 'write:theme'], Utils::get_theme_mod_allowlist(), []),
            self::route_entry('GET,PATCH,POST', '/theme/customizer', ['read:theme', 'write:theme'], array_values(array_unique($theme_customizer_fields)), []),
            self::route_entry('GET,PATCH,POST', '/theme/customizer/{group}', ['read:theme', 'write:theme'], array_values(array_unique($theme_customizer_fields)), []),
            self::route_entry('GET,PATCH,POST', '/theme/page-meta/{post_id}', ['read:theme', 'write:theme'], array_values(array_unique($theme_page_fields)), []),
            self::route_entry('GET,PATCH,POST', '/theme/cpt-meta/{post_type}/{post_id}', ['read:theme', 'write:theme'], array_values(array_unique($theme_cpt_fields)), []),
            self::route_entry('GET,PATCH,POST', '/theme/taxonomy-meta/{taxonomy}/{term_id}', ['read:theme', 'write:theme'], array_values(array_unique($theme_taxonomy_fields)), []),

            self::route_entry('GET,PATCH,POST', '/seo/options', ['read:seo', 'write:seo'], Utils::get_seo_option_allowlist(), []),
            self::route_entry('GET,PATCH,POST', '/seo/meta/{post_id}', ['read:seo', 'write:seo'], Utils::get_seo_meta_allowlist(), ['_chroma_schema_*']),
            self::route_entry('GET,PATCH,POST', '/seo/schema/{post_id}', ['read:seo', 'write:seo'], $schema_fields, []),
            self::route_entry('GET', '/seo/schema', ['read:seo'], [], []),
            self::route_entry('GET', '/schema/seo', ['read:seo'], [], []),
            self::route_entry('GET,PATCH,POST', '/schema/seo/{post_id}', ['read:seo', 'write:seo'], $schema_fields, []),
            self::route_entry('GET,PATCH,POST', '/seo/structured-meta/{post_id}', ['read:seo', 'write:seo'], Field_Registry::seo_meta_fields(), []),
            self::route_entry('GET', '/seo/actions', ['read:seo'], [], []),
            self::route_entry('POST', '/seo/actions/{action}', ['write:seo'], ['target_id', 'payload', 'dry_run'], []),
            self::route_entry('GET', '/seo/virtual-pages', ['read:seo'], [], []),
            self::route_entry('GET,PATCH,POST', '/seo/virtual-pages/{type}/{key}', ['read:seo', 'write:seo'], Field_Registry::virtual_page_seo_fields(), []),
            self::route_entry('GET,PATCH,POST', '/llm/settings', ['read:seo', 'write:seo'], array_values(array_unique($seo_option_fields)), ['chroma_openai_api_key', 'chroma_google_places_api_key']),
            self::route_entry('GET', '/translations', ['read:seo'], [], []),
            self::route_entry('GET,PATCH,POST,DELETE', '/translations/{id}', ['read:seo', 'write:seo'], ['content', 'status'], []),
            self::route_entry('POST', '/translate', ['write:seo'], ['text', 'post_id', 'language'], []),

            self::route_entry('GET,POST', '/media', ['read:media', 'write:media'], ['file', 'title', 'alt', 'caption', 'description'], []),
            self::route_entry('POST', '/media/attach', ['write:media'], ['media_id', 'post_id', 'field'], []),
            self::route_entry('GET,PATCH,POST', '/media/{id}/metadata', ['read:media', 'write:media'], ['alt', 'caption', 'description', 'title', 'featured_post_id'], []),

            self::route_entry('GET,PATCH,POST', '/forms/{form}/settings', ['read:forms', 'write:forms'], ['fields_json', 'webhook_url', 'email_recipient', 'ghl_form_id', 'ghl_form_name', 'ghl_form_height', 'lazy_load', 'delay'], ['webhook_secret']),
            self::route_entry('GET', '/leads', ['read:leads'], [], []),
            self::route_entry('GET', '/leads/{id}', ['read:leads'], [], []),
            self::route_entry('POST', '/leads/{id}/retry-webhook', ['write:leads'], ['dry_run'], []),

            self::route_entry('GET,POST', '/portal/content', ['read:portal', 'write:portal'], ['post_type', 'title', 'content', 'status', 'meta', 'terms'], ['_cp_pin_hash', '_cp_pin_simple_hash']),
            self::route_entry('GET,PATCH,POST,DELETE', '/portal/content/{id}', ['read:portal', 'write:portal'], ['title', 'content', 'status', 'meta', 'terms'], ['_cp_pin_hash', '_cp_pin_simple_hash']),
            self::route_entry('GET', '/portal/taxonomies', ['read:portal'], [], []),
            self::route_entry('GET', '/portal/dashboard', ['read:portal'], [], []),
            self::route_entry('GET', '/portal/years', ['read:portal'], [], []),
            self::route_entry('GET', '/portal/taxonomies/{taxonomy}', ['read:portal'], [], []),
            self::route_entry('GET,POST', '/portal/bulk-import', ['read:portal', 'write:portal'], ['operation', 'payload', 'dry_run'], []),
            self::route_entry('POST', '/portal/families/{id}/pin', ['write:portal'], ['pin', 'reset'], ['_cp_pin_hash', '_cp_pin_simple_hash']),

            self::route_entry('GET', '/schools', ['read:schools'], [], []),
            self::route_entry('GET,PATCH,POST', '/schools/{id}', ['read:schools', 'write:schools'], ['title', 'slug', 'status', 'config'], []),
            self::route_entry('GET,PATCH,POST', '/schools/{id}/tv', ['read:schools', 'write:schools'], Field_Registry::school_content_keys(), []),
            self::route_entry('GET,PATCH,POST', '/schools/{id}/config', ['read:schools', 'write:schools'], ['config', 'director_email', 'chroma_global_cares', 'chroma_global_alert', 'chroma_google_client_id'], []),
            self::route_entry('GET', '/schools/weather', ['read:schools'], [], []),

            self::route_entry('POST', '/cache/flush', ['write:maintenance'], ['target'], []),
            self::route_entry('POST', '/sitemaps/refresh', ['write:maintenance'], ['target'], []),
            self::route_entry('POST', '/geo-feed/refresh', ['write:maintenance'], ['target'], []),
            self::route_entry('GET', '/geo-feed', ['read:geo'], [], []),
            self::route_entry('GET', '/geo-feed/{location_id}', ['read:geo'], [], []),

            self::route_entry('GET', '/audit', ['admin:audit'], [], []),
            self::route_entry('GET', '/audit/{id}', ['admin:audit'], [], []),
            self::route_entry('GET', '/snapshots', ['admin:audit'], [], []),
            self::route_entry('POST', '/rollback/snapshot', ['admin:audit', 'write:theme|write:seo'], ['snapshot_id', 'dry_run'], []),
        ];
    }

    private static function route_entry(string $methods, string $route, array $scopes, array $writable_fields = [], array $blocked_fields = []): array
    {
        return [
            'methods' => array_values(array_filter(array_map('trim', explode(',', $methods)))),
            'route' => $route,
            'required_scopes' => $scopes,
            'writable_fields' => self::string_list($writable_fields),
            'blocked_fields' => self::string_list($blocked_fields),
        ];
    }

    private static function string_list(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            if (is_scalar($item)) {
                $value = trim((string) $item);
                if ($value !== '') {
                    $out[] = $value;
                }
            }
        }

        return array_values(array_unique($out));
    }
}

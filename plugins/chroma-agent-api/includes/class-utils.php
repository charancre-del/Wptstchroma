<?php

namespace ChromaAgentAPI;

if (!defined('ABSPATH')) {
    exit;
}

class Utils
{
    public const OPTION_ENABLED = 'chroma_agent_api_enabled';
    public const OPTION_THEME_OPTION_ALLOWLIST = 'chroma_agent_api_theme_option_allowlist';
    public const OPTION_THEME_MOD_ALLOWLIST = 'chroma_agent_api_theme_mod_allowlist';
    public const OPTION_SEO_OPTION_ALLOWLIST = 'chroma_agent_api_seo_option_allowlist';
    public const OPTION_SEO_META_ALLOWLIST = 'chroma_agent_api_seo_meta_allowlist';

    public static function table(string $suffix): string
    {
        global $wpdb;
        return $wpdb->prefix . 'chroma_api_' . $suffix;
    }

    public static function is_https_request(): bool
    {
        if (is_ssl()) {
            return true;
        }

        $forwarded = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) : '';
        return $forwarded === 'https';
    }

    public static function get_request_ip(): string
    {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $value = trim((string) wp_unslash($_SERVER[$key]));
                if ($key === 'HTTP_X_FORWARDED_FOR' && strpos($value, ',') !== false) {
                    $parts = explode(',', $value);
                    $value = trim($parts[0]);
                }

                if (filter_var($value, FILTER_VALIDATE_IP)) {
                    return $value;
                }
            }
        }

        return '0.0.0.0';
    }

    public static function truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (!is_string($value)) {
            return false;
        }

        $normalized = strtolower(trim($value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    public static function sanitize_recursive($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $safe_key = is_string($k) ? sanitize_key($k) : $k;
                $out[$safe_key] = self::sanitize_recursive($v);
            }
            return $out;
        }

        if (is_object($value)) {
            return self::sanitize_recursive((array) $value);
        }

        if (is_string($value)) {
            return sanitize_text_field($value);
        }

        return $value;
    }

    public static function sanitize_mixed_for_storage($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $safe_key = is_string($k) ? sanitize_key($k) : $k;
                $out[$safe_key] = self::sanitize_mixed_for_storage($v);
            }
            return $out;
        }

        if (is_object($value)) {
            return self::sanitize_mixed_for_storage((array) $value);
        }

        if (is_string($value)) {
            return wp_kses_post($value);
        }

        return $value;
    }

    /**
     * Sanitize nested data while preserving original string keys (e.g. @context, @type).
     */
    public static function sanitize_mixed_for_storage_preserve_keys($value)
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $safe_key = is_string($k) ? $k : $k;
                $out[$safe_key] = self::sanitize_mixed_for_storage_preserve_keys($v);
            }
            return $out;
        }

        if (is_object($value)) {
            return self::sanitize_mixed_for_storage_preserve_keys((array) $value);
        }

        if (is_string($value)) {
            return wp_kses_post($value);
        }

        return $value;
    }

    public static function normalize_scopes(array $scopes): array
    {
        $out = [];
        foreach ($scopes as $scope) {
            if (!is_string($scope)) {
                continue;
            }
            $scope = strtolower(trim($scope));
            if ($scope !== '') {
                $out[] = $scope;
            }
        }
        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }

    public static function get_theme_option_allowlist(): array
    {
        $saved = get_option(self::OPTION_THEME_OPTION_ALLOWLIST, []);
        if (!is_array($saved) || empty($saved)) {
            $saved = [
                'blogname',
                'blogdescription',
                'show_on_front',
                'page_on_front',
                'page_for_posts',
                'chroma_global_cares',
                'chroma_global_alert',
            ];
        }

        return self::normalize_allowlist($saved);
    }

    public static function get_theme_mod_allowlist(): array
    {
        $saved = get_option(self::OPTION_THEME_MOD_ALLOWLIST, []);
        if (!is_array($saved) || empty($saved)) {
            $saved = [
                'custom_logo',
                'background_color',
                'header_textcolor',
            ];
        }

        return self::normalize_allowlist($saved);
    }

    public static function get_seo_option_allowlist(): array
    {
        $saved = get_option(self::OPTION_SEO_OPTION_ALLOWLIST, []);
        $defaults = [
            'chroma_citation_facts',
            'chroma_llm_brand_voice',
            'chroma_llm_brand_context',
            'chroma_seo_phone',
            'chroma_seo_email',
            'chroma_seo_phonetic_name',
            'chroma_validator_batch_size',
            'chroma_validator_request_delay',
            'chroma_validator_timeout',
            'chroma_validator_cache_ttl',
            'chroma_validator_max_retries',
            'chroma_validator_email_alerts',
            'chroma_validator_post_types',
            'chroma_careers_feed_url',
            'chroma_combo_auto_publish',
            'chroma_seo_manual_cities',
            'chroma_faq_schema_disabled',
            'chroma_breadcrumbs_schema_disabled',
        ];

        if (!is_array($saved)) {
            $saved = [];
        }

        return self::normalize_allowlist(array_merge($defaults, $saved));
    }

    public static function get_seo_meta_allowlist(): array
    {
        $saved = get_option(self::OPTION_SEO_META_ALLOWLIST, []);
        $defaults = [
            '_chroma_es_title',
            '_chroma_es_content',
            '_chroma_es_excerpt',
            '_chroma_es_seo_title',
            '_chroma_es_meta_description',
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
            'chroma_faq_items',
        ];

        if (!is_array($saved)) {
            $saved = [];
        }

        return self::normalize_allowlist(array_merge($defaults, $saved));
    }

    public static function normalize_allowlist(array $values): array
    {
        $out = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }
            $value = trim($value);
            if ($value !== '') {
                $out[] = $value;
            }
        }

        $out = array_values(array_unique($out));
        sort($out);
        return $out;
    }
}

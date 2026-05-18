<?php
/**
 * Editable SEO storage and resolution for virtual pages.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Virtual_Page_SEO_Data
{
    const OPTION_PREFIX = 'chroma_virtual_page_seo_';

    public static function fields() {
        return [
            'seo_title',
            'meta_description',
            'seo_title_es',
            'meta_description_es',
            'robots',
            'last_updated',
        ];
    }

    public static function defaults() {
        return [
            'seo_title' => '',
            'meta_description' => '',
            'seo_title_es' => '',
            'meta_description_es' => '',
            'robots' => '',
            'last_updated' => null,
        ];
    }

    public static function combo_key($program_slug, $city_slug, $state) {
        return sanitize_title($program_slug) . ':' . sanitize_title($city_slug) . ':' . strtoupper(sanitize_text_field($state));
    }

    public static function service_area_key($type, $area_name) {
        return sanitize_key($type) . ':' . sanitize_title($area_name);
    }

    public static function get_combo($program_slug, $city_slug, $state) {
        if (!class_exists('Chroma_Combo_Page_Data')) {
            return self::defaults();
        }

        $data = Chroma_Combo_Page_Data::get($program_slug, $city_slug, $state);
        return wp_parse_args(self::only_seo_fields($data), self::defaults());
    }

    public static function save_combo($program_slug, $city_slug, $state, array $updates) {
        if (!class_exists('Chroma_Combo_Page_Data')) {
            return false;
        }

        return Chroma_Combo_Page_Data::save($program_slug, $city_slug, $state, self::sanitize_updates($updates));
    }

    public static function get_service_area($type, $area_name) {
        $option_key = self::service_area_option_key($type, $area_name);
        $data = get_option($option_key, []);
        return wp_parse_args(is_array($data) ? $data : [], self::defaults());
    }

    public static function save_service_area($type, $area_name, array $updates) {
        $option_key = self::service_area_option_key($type, $area_name);
        $existing = self::get_service_area($type, $area_name);
        $data = wp_parse_args(self::sanitize_updates($updates), $existing);
        $data['last_updated'] = current_time('timestamp');
        return update_option($option_key, $data, false);
    }

    public static function service_area_option_key($type, $area_name) {
        return self::OPTION_PREFIX . self::service_area_key($type, $area_name);
    }

    public static function resolve($type, array $args, array $fallbacks = []) {
        $language = function_exists('chroma_seo_get_request_language') ? chroma_seo_get_request_language() : 'en';
        $data = [];

        if ($type === 'combo') {
            $data = self::get_combo($args['program_slug'] ?? '', $args['city_slug'] ?? '', $args['state'] ?? '');
        } elseif ($type === 'service_area') {
            $data = self::get_service_area($args['area_type'] ?? '', $args['area_name'] ?? '');
        }

        $title_key = $language === 'es' ? 'seo_title_es' : 'seo_title';
        $desc_key = $language === 'es' ? 'meta_description_es' : 'meta_description';

        $title = trim((string) ($data[$title_key] ?? ''));
        $description = trim((string) ($data[$desc_key] ?? ''));

        if ($title === '' && $language === 'es') {
            $title = trim((string) ($data['seo_title'] ?? ''));
        }
        if ($description === '' && $language === 'es') {
            $description = trim((string) ($data['meta_description'] ?? ''));
        }

        return [
            'title' => $title !== '' ? $title : (string) ($fallbacks['title'] ?? ''),
            'meta_description' => $description !== '' ? $description : (string) ($fallbacks['meta_description'] ?? ''),
            'canonical' => (string) ($fallbacks['canonical'] ?? ''),
            'robots' => (string) ($data['robots'] ?? ''),
            'source' => ($title !== '' || $description !== '') ? 'override' : 'fallback',
            'data' => wp_parse_args($data, self::defaults()),
        ];
    }

    public static function apply_filters(array $seo) {
        $title = (string) ($seo['title'] ?? '');
        $description = (string) ($seo['meta_description'] ?? '');
        $canonical = (string) ($seo['canonical'] ?? '');
        $robots = trim((string) ($seo['robots'] ?? ''));

        if ($canonical !== '') {
            foreach (['wpseo_canonical', 'wpseo_opengraph_url'] as $filter) {
                add_filter($filter, static function () use ($canonical) {
                    return $canonical;
                }, PHP_INT_MAX);
            }
        }

        if ($title !== '') {
            add_filter('wpseo_title', static function () use ($title) {
                return $title;
            }, PHP_INT_MAX);
            add_filter('pre_get_document_title', static function () use ($title) {
                return $title;
            }, PHP_INT_MAX);
        }

        if ($description !== '') {
            add_filter('wpseo_metadesc', static function () use ($description) {
                return $description;
            }, PHP_INT_MAX);
            add_filter('wpseo_opengraph_desc', static function () use ($description) {
                return $description;
            }, PHP_INT_MAX);
        }

        if ($robots !== '') {
            add_filter('wpseo_robots', static function () use ($robots) {
                return $robots;
            }, PHP_INT_MAX);
        }
    }

    public static function service_area_candidates() {
        $locations = get_posts([
            'post_type' => 'location',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        $counties = [];
        $zips = [];
        foreach ($locations as $location) {
            $county = sanitize_text_field((string) get_post_meta($location->ID, 'location_county', true));
            if ($county !== '') {
                $county_slug = sanitize_title(str_replace(' County', '', $county));
                $counties[$county_slug] = [
                    'type' => 'county',
                    'area_name' => $county_slug,
                    'label' => ucwords(str_replace('-', ' ', $county_slug)) . ' County',
                    'url' => home_url('/childcare-in-' . $county_slug . '-county/'),
                    'data' => self::get_service_area('county', $county_slug),
                ];
            }

            $zip = preg_replace('/[^0-9]/', '', (string) get_post_meta($location->ID, 'location_zip', true));
            if (strlen($zip) === 5) {
                $zips[$zip] = [
                    'type' => 'zip',
                    'area_name' => $zip,
                    'label' => $zip,
                    'url' => home_url('/daycare-' . $zip . '/'),
                    'data' => self::get_service_area('zip', $zip),
                ];
            }
        }

        return array_values($counties + $zips);
    }

    public static function sanitize_updates(array $updates) {
        $clean = [];
        foreach (self::fields() as $field) {
            if (!array_key_exists($field, $updates) || $field === 'last_updated') {
                continue;
            }

            if ($field === 'robots') {
                $value = sanitize_text_field((string) $updates[$field]);
                $clean[$field] = in_array($value, ['index,follow', 'noindex,follow', 'noindex,nofollow'], true) ? $value : '';
                continue;
            }

            $clean[$field] = $field === 'meta_description' || $field === 'meta_description_es'
                ? sanitize_textarea_field((string) $updates[$field])
                : sanitize_text_field((string) $updates[$field]);
        }

        return $clean;
    }

    public static function only_seo_fields(array $data) {
        return array_intersect_key($data, array_flip(self::fields()));
    }
}

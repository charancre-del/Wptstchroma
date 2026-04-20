<?php
/**
 * SEO runtime detection and compatibility helpers.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('chroma_get_otto_detection_signals')) {
    /**
     * Gather OTTO/Search Atlas signals from the current WordPress runtime.
     *
     * @return array<string, mixed>
     */
    function chroma_get_otto_detection_signals()
    {
        $signals = [];

        $script_candidates = [
            (string) get_theme_mod('chroma_header_scripts', ''),
            (string) get_theme_mod('chroma_footer_scripts', ''),
        ];

        foreach ($script_candidates as $candidate) {
            if ($candidate !== '' && preg_match('/searchatlas|sa\.searchatlas\.com|otto(?:-pixel)?|data-otto/i', $candidate)) {
                $signals['customizer_scripts'] = true;
                break;
            }
        }

        $option_candidates = [
            'searchatlas_api_key',
            'searchatlas_api_token',
            'searchatlas_enable_otto',
            'searchatlas_otto_enabled',
            'searchatlas_otto_uuid',
            'searchatlas_otto_pixel_uuid',
            'otto_pixel_uuid',
            'otto_uuid',
        ];

        foreach ($option_candidates as $option_name) {
            $value = get_option($option_name, '');
            if (is_scalar($value) && trim((string) $value) !== '') {
                $signals['option:' . $option_name] = true;
            } elseif ($value === true) {
                $signals['option:' . $option_name] = true;
            }
        }

        $constants = [
            'SEARCHATLAS_VERSION',
            'SEARCHATLAS_PLUGIN_VERSION',
            'OTTO_PIXEL_UUID',
        ];

        foreach ($constants as $constant) {
            if (defined($constant) && constant($constant)) {
                $signals['constant:' . $constant] = true;
            }
        }

        $classes = [
            'SearchAtlas',
            'SearchAtlasPlugin',
            'SearchAtlas_Plugin',
            'SearchAtlas_WordPress',
        ];

        foreach ($classes as $class_name) {
            if (class_exists($class_name, false)) {
                $signals['class:' . $class_name] = true;
            }
        }

        return $signals;
    }
}

if (!function_exists('chroma_detect_otto_runtime')) {
    /**
     * Determine whether OTTO/Search Atlas appears to be installed and active.
     *
     * @return bool
     */
    function chroma_detect_otto_runtime()
    {
        static $detected = null;

        if ($detected !== null) {
            return $detected;
        }

        $signals = chroma_get_otto_detection_signals();
        $detected = !empty($signals);

        /**
         * Filter OTTO runtime detection.
         *
         * @param bool  $detected
         * @param array $signals
         */
        $detected = (bool) apply_filters('chroma_detect_otto_runtime', $detected, $signals);

        return $detected;
    }
}

if (!function_exists('chroma_get_seo_runtime_mode')) {
    /**
     * Resolve the site-wide SEO runtime mode.
     *
     * Modes:
     * - local
     * - otto_compatible
     *
     * @return string
     */
    function chroma_get_seo_runtime_mode()
    {
        $default_mode = chroma_detect_otto_runtime() ? 'otto_compatible' : 'local';
        $stored_mode = get_option('chroma_seo_runtime_mode', $default_mode);

        if (!is_string($stored_mode) || !in_array($stored_mode, ['local', 'otto_compatible'], true)) {
            $stored_mode = $default_mode;
        }

        /**
         * Filter the active SEO runtime mode.
         *
         * @param string $stored_mode
         * @param string $default_mode
         */
        $mode = apply_filters('chroma_seo_runtime_mode', $stored_mode, $default_mode);

        if (!is_string($mode) || !in_array($mode, ['local', 'otto_compatible'], true)) {
            return $default_mode;
        }

        return $mode;
    }
}

if (!function_exists('chroma_is_otto_compatible_seo_mode')) {
    /**
     * Check whether the current request should suppress local OTTO-overlapping SEO tags.
     *
     * @return bool
     */
    function chroma_is_otto_compatible_seo_mode()
    {
        $enabled = chroma_get_seo_runtime_mode() === 'otto_compatible';

        /**
         * Filter OTTO-compatible mode for the current request.
         *
         * @param bool   $enabled
         * @param string $mode
         */
        return (bool) apply_filters('chroma_is_otto_compatible_seo_mode', $enabled, chroma_get_seo_runtime_mode());
    }
}

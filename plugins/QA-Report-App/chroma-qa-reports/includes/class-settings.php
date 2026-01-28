<?php
/**
 * Settings Handler
 *
 * @package ChromaQAReports
 */

namespace ChromaQA;

/**
 * Handles plugin settings with backward compatibility.
 */
class Settings
{

    /**
     * Option key for consolidated settings.
     */
    const OPTION_KEY = 'cqa_settings';

    /**
     * Get a setting value.
     *
     * @param string $key     Setting key (without prefix).
     * @param mixed  $default Default value.
     * @return mixed
     */
    public static function get($key, $default = false)
    {
        // 1. Check consolidated array first
        $settings = get_option(self::OPTION_KEY, []);
        if (is_array($settings) && array_key_exists($key, $settings)) {
            return $settings[$key];
        }

        // 2. Fallback to legacy individual option
        $legacy_key = 'cqa_' . $key;
        return get_option($legacy_key, $default);
    }

    /**
     * Update a setting value.
     *
     * @param string $key   Setting key.
     * @param mixed  $value New value.
     * @return bool
     */
    public static function update($key, $value)
    {
        $settings = get_option(self::OPTION_KEY, []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $settings[$key] = $value;
        return update_option(self::OPTION_KEY, $settings);
    }

    /**
     * Delete a setting.
     * 
     * @param string $key Setting key.
     * @return bool
     */
    public static function delete($key)
    {
        $settings = get_option(self::OPTION_KEY, []);
        if (!is_array($settings)) {
            return false;
        }

        if (isset($settings[$key])) {
            unset($settings[$key]);
            return update_option(self::OPTION_KEY, $settings);
        }

        return false;
    }
}

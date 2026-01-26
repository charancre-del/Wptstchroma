<?php
/**
 * Feature Flags
 *
 * Route-level feature flags with audience scoping for React migration.
 *
 * @package ChromaQAReports
 */

namespace ChromaQA;

/**
 * Manages feature flags for gradual React rollout.
 */
class Feature_Flags
{

    /**
     * Flag definitions with defaults and initial audience.
     */
    const FLAGS = [
        'react_dashboard' => ['default' => true, 'audience' => 'admins'],
        'react_wizard' => ['default' => true, 'audience' => 'admins'],
        'react_schools' => ['default' => true, 'audience' => 'admins'],
        'react_reports' => ['default' => true, 'audience' => 'admins'],
        'react_settings' => ['default' => false, 'audience' => 'admins'],
        'sw_enabled' => ['default' => false, 'audience' => 'admins'],
    ];

    /**
     * Audience scopes in rollout order.
     */
    const AUDIENCES = ['admins', 'canary', 'qa_officers', 'all'];

    /**
     * Check if a feature is enabled for a specific user.
     *
     * @param string   $feature Feature flag name (without cqa_flag_ prefix).
     * @param int|null $user_id User ID (defaults to current user).
     * @return bool
     */
    public static function is_enabled($feature, $user_id = null)
    {
        $user_id = $user_id ?: get_current_user_id();

        if (!$user_id) {
            return false;
        }

        // Check global toggle
        $enabled = get_option("cqa_flag_{$feature}", self::FLAGS[$feature]['default'] ?? false);
        if (!$enabled) {
            return false;
        }

        // Check audience scope
        $audience = get_option("cqa_flag_{$feature}_audience", 'admins');

        return self::user_matches_audience($user_id, $audience, $feature);
    }

    /**
     * Check if user matches the required audience.
     *
     * @param int    $user_id  User ID.
     * @param string $audience Audience scope.
     * @param string $feature  Feature name (for allowlist lookup).
     * @return bool
     */
    private static function user_matches_audience($user_id, $audience, $feature)
    {
        switch ($audience) {
            case 'admins':
                return user_can($user_id, 'cqa_manage_settings');

            case 'canary':
                $allowlist = get_option("cqa_flag_{$feature}_allowlist", []);
                if (!is_array($allowlist)) {
                    $allowlist = array_map('trim', explode(',', $allowlist));
                }
                return in_array((int) $user_id, array_map('intval', $allowlist), true);

            case 'qa_officers':
                return user_can($user_id, 'cqa_create_reports');

            case 'all':
                return true;

            default:
                return false;
        }
    }

    /**
     * Get all flags for a user (for cqaData localization).
     *
     * @param int|null $user_id User ID.
     * @return array
     */
    public static function get_user_flags($user_id = null)
    {
        $flags = [];
        foreach (array_keys(self::FLAGS) as $flag) {
            $flags[$flag] = self::is_enabled($flag, $user_id);
        }
        return $flags;
    }

    /**
     * Set a flag value.
     *
     * @param string $feature  Feature name.
     * @param bool   $enabled  Whether enabled.
     * @param string $audience Audience scope.
     */
    public static function set_flag($feature, $enabled, $audience = null)
    {
        update_option("cqa_flag_{$feature}", (bool) $enabled);
        if ($audience && in_array($audience, self::AUDIENCES, true)) {
            update_option("cqa_flag_{$feature}_audience", $audience);
        }
    }

    /**
     * Set canary allowlist for a feature.
     *
     * @param string $feature  Feature name.
     * @param array  $user_ids Array of user IDs.
     */
    public static function set_canary_allowlist($feature, $user_ids)
    {
        update_option("cqa_flag_{$feature}_allowlist", array_map('intval', $user_ids));
    }

    /**
     * Get all flag statuses (for admin display).
     *
     * @return array
     */
    public static function get_all_flags()
    {
        $result = [];
        foreach (self::FLAGS as $flag => $config) {
            $result[$flag] = [
                'enabled' => (bool) get_option("cqa_flag_{$flag}", $config['default']),
                'audience' => get_option("cqa_flag_{$flag}_audience", $config['audience']),
                'allowlist' => get_option("cqa_flag_{$flag}_allowlist", []),
            ];
        }
        return $result;
    }
}

/**
 * Global helper: Check if feature is enabled.
 *
 * @param string   $feature Feature name.
 * @param int|null $user_id User ID.
 * @return bool
 */
function cqa_is_feature_enabled($feature, $user_id = null)
{
    return Feature_Flags::is_enabled($feature, $user_id);
}

/**
 * Global helper: Get all flags for user.
 *
 * @param int|null $user_id User ID.
 * @return array
 */
function cqa_get_user_flags($user_id = null)
{
    return Feature_Flags::get_user_flags($user_id);
}

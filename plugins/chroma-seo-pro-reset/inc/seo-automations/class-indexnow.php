<?php
/**
 * IndexNow Integration
 * Automatically notifies search engines when content is updated
 * 
 * @package Chroma_Excellence
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_IndexNow
{
    private static $api_url = 'https://api.indexnow.org/IndexNow';
    
    public static function init()
    {
        add_action('transition_post_status', [__CLASS__, 'on_post_status_transition'], 10, 3);
        add_action('admin_init', [__CLASS__, 'check_key_file']);
    }

    /**
     * Handle post status changes
     */
    public static function on_post_status_transition($new_status, $old_status, $post)
    {
        // Only trigger on publish or update of published posts
        if ($new_status !== 'publish') {
            return;
        }

        // Avoid triggering on autosaves/revisions
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post->ID)) {
            return;
        }

        self::notify_indexnow(get_permalink($post->ID));
    }

    /**
     * Notify IndexNow API
     */
    public static function notify_indexnow($url)
    {
        $key = get_option('chroma_indexnow_key');
        if (!$key) {
            $key = wp_generate_password(32, false);
            update_option('chroma_indexnow_key', $key);
        }

        $host = parse_url(home_url(), PHP_URL_HOST);
        
        $data = [
            'host' => $host,
            'key' => $key,
            'keyLocation' => home_url($key . '.txt'),
            'urlList' => [$url]
        ];

        wp_remote_post(self::$api_url, [
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => wp_json_encode($data),
            'timeout' => 10,
            'blocking' => false // Don't slow down the user
        ]);
    }

    /**
     * Ensure the key file exists or is handled via rewrite
     */
    public static function check_key_file()
    {
        $key = get_option('chroma_indexnow_key');
        if (!$key) return;

        // We can handle this via a query var or physical file.
        // For simplicity, we'll suggest physical file check or just handle via 'init' hook
    }
}

Chroma_IndexNow::init();



<?php
/**
 * Translation REST API
 * Provides REST endpoints for managing translations.
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Translation_API
{
    public function init()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes()
    {
        register_rest_route('chroma/v1', '/translations/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_translation'],
                'permission_callback' => [$this, 'check_permissions'],
                'args' => [
                    'id' => [
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ]
                ]
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_translation'],
                'permission_callback' => [$this, 'check_permissions'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_translation'],
                'permission_callback' => [$this, 'check_permissions'],
            ]
        ]);

        register_rest_route('chroma/v1', '/translations', [
            'methods' => 'GET',
            'callback' => [$this, 'get_all_translations'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        register_rest_route('chroma/v1', '/translate', [
            'methods' => 'POST',
            'callback' => [$this, 'auto_translate'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);

        register_rest_route('chroma/v1', '/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_stats'],
            'permission_callback' => [$this, 'check_permissions'],
        ]);
    }

    public function check_permissions()
    {
        return current_user_can('edit_posts');
    }

    /**
     * GET /chroma/v1/translations/{id}
     */
    public function get_translation($request)
    {
        $post_id = $request['id'];
        $post = get_post($post_id);

        if (!$post) {
            return new WP_Error('not_found', 'Post not found', ['status' => 404]);
        }

        $alternates = [];
        if (class_exists('Chroma_Multilingual_Manager')) {
            $alternates = Chroma_Multilingual_Manager::get_alternates($post_id);
        }

        return [
            'id' => $post_id,
            'title_en' => $post->post_title,
            'title_es' => get_post_meta($post_id, '_chroma_es_title', true),
            'content_en' => $post->post_content,
            'content_es' => get_post_meta($post_id, '_chroma_es_content', true),
            'excerpt_en' => $post->post_excerpt,
            'excerpt_es' => get_post_meta($post_id, '_chroma_es_excerpt', true),
            'url_en' => $alternates['en'] ?? '',
            'url_es' => $alternates['es'] ?? '',
            'has_translation' => !empty(get_post_meta($post_id, '_chroma_es_content', true)),
        ];
    }

    /**
     * POST /chroma/v1/translations/{id}
     */
    public function save_translation($request)
    {
        $post_id = $request['id'];
        $body = $request->get_json_params();

        if (isset($body['title_es'])) {
            update_post_meta($post_id, '_chroma_es_title', sanitize_text_field($body['title_es']));
        }
        if (isset($body['content_es'])) {
            update_post_meta($post_id, '_chroma_es_content', wp_kses_post($body['content_es']));
        }
        if (isset($body['excerpt_es'])) {
            update_post_meta($post_id, '_chroma_es_excerpt', sanitize_textarea_field($body['excerpt_es']));
        }

        return [
            'success' => true,
            'message' => 'Translation saved',
            'id' => $post_id
        ];
    }

    /**
     * DELETE /chroma/v1/translations/{id}
     */
    public function delete_translation($request)
    {
        $post_id = $request['id'];

        delete_post_meta($post_id, '_chroma_es_title');
        delete_post_meta($post_id, '_chroma_es_content');
        delete_post_meta($post_id, '_chroma_es_excerpt');

        return [
            'success' => true,
            'message' => 'Translation deleted',
            'id' => $post_id
        ];
    }

    /**
     * GET /chroma/v1/translations
     */
    public function get_all_translations($request)
    {
        $post_type = $request->get_param('post_type') ?? 'page';
        $status = $request->get_param('status'); // translated, untranslated, all

        $posts = get_posts([
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        $results = [];
        foreach ($posts as $post) {
            $has_translation = !empty(get_post_meta($post->ID, '_chroma_es_content', true));

            if ($status === 'translated' && !$has_translation) continue;
            if ($status === 'untranslated' && $has_translation) continue;

            $results[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'has_translation' => $has_translation,
                'url' => get_permalink($post->ID)
            ];
        }

        return $results;
    }

    /**
     * POST /chroma/v1/translate
     */
    public function auto_translate($request)
    {
        $body = $request->get_json_params();
        $post_id = isset($body['post_id']) ? intval($body['post_id']) : 0;

        if (!$post_id) {
            return new WP_Error('missing_id', 'Post ID required', ['status' => 400]);
        }

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post not found', ['status' => 404]);
        }

        $fields = [
            '_chroma_es_title' => $post->post_title,
            '_chroma_es_content' => $post->post_content,
            '_chroma_es_excerpt' => $post->post_excerpt,
        ];

        $translated = Chroma_Translation_Engine::translate_bulk($fields, 'es', 'Translate for a childcare website.');

        if (isset($translated['_error'])) {
            return new WP_Error('translation_failed', $translated['_error'], ['status' => 500]);
        }

        // Save translations
        foreach ($translated as $key => $value) {
            if (strpos($key, '_chroma_es_') === 0 && !empty($value)) {
                update_post_meta($post_id, $key, $value);
            }
        }

        return [
            'success' => true,
            'message' => 'Translation complete',
            'data' => $translated
        ];
    }

    /**
     * GET /chroma/v1/stats
     */
    public function get_stats($request)
    {
        global $wpdb;
        $post_types = ['page', 'location', 'program'];
        $stats = [];

        foreach ($post_types as $type) {
            $total = wp_count_posts($type)->publish;
            
            $translated = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
                 WHERE meta_key = '_chroma_es_content' AND meta_value != ''
                 AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish')",
                $type
            ));

            $stats[$type] = [
                'total' => (int)$total,
                'translated' => (int)$translated,
                'untranslated' => (int)$total - (int)$translated,
                'percent' => $total > 0 ? round(($translated / $total) * 100) : 0
            ];
        }

        $overall_total = array_sum(array_column($stats, 'total'));
        $overall_translated = array_sum(array_column($stats, 'translated'));

        return [
            'by_type' => $stats,
            'overall' => [
                'total' => $overall_total,
                'translated' => $overall_translated,
                'percent' => $overall_total > 0 ? round(($overall_translated / $overall_total) * 100) : 0
            ]
        ];
    }
}



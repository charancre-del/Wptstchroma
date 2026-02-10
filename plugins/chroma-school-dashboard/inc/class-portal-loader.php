<?php

class Chroma_School_Portal_Loader
{
    public function __construct()
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'load_portal_template']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_portal_assets']);
    }

    public function add_rewrite_rules()
    {
        add_rewrite_rule('^portal/?$', 'index.php?chroma_view=portal', 'top');
        add_rewrite_rule('^portal/(.+)?$', 'index.php?chroma_view=portal', 'top');
    }

    public function add_query_vars($vars)
    {
        if (!in_array('chroma_view', $vars)) {
            $vars[] = 'chroma_view';
        }
        return $vars;
    }

    public function load_portal_template($template)
    {
        if (get_query_var('chroma_view') === 'portal') {
            $plugin_template = CHROMA_SCHOOL_DB_PATH . 'templates/portal-dashboard.php';

            if (file_exists($plugin_template)) {
                include($plugin_template);
                exit;
            }

            // Fallback to theme if plugin template missing (unlikely now)
            $theme_template = locate_template(['page-portal.php', 'page-director-portal.php']);

            if ($theme_template) {
                include($theme_template);
                exit;
            }

            echo "Portal template not found.";
            exit;
        }
        return $template;
    }

    public function enqueue_portal_assets()
    {
        if (get_query_var('chroma_view') !== 'portal') {
            return;
        }

        $css_url = CHROMA_SCHOOL_DB_URL . 'assets/dist/portal.css';
        $js_url = CHROMA_SCHOOL_DB_URL . 'assets/dist/portal.js';
        $css_path = CHROMA_SCHOOL_DB_PATH . 'assets/dist/portal.css';
        $js_path = CHROMA_SCHOOL_DB_PATH . 'assets/dist/portal.js';
        $css_version = file_exists($css_path) ? (string) filemtime($css_path) : CHROMA_SCHOOL_VERSION;
        $js_version = file_exists($js_path) ? (string) filemtime($js_path) : CHROMA_SCHOOL_VERSION;

        // Load fonts pre-emptively
        wp_enqueue_style('chroma-fonts', 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&family=Playfair+Display:wght@600;700&display=swap', [], null);

        // Portal CSS
        wp_enqueue_style('chroma-portal-css', $css_url, [], $css_version);

        // React + ReactDOM (Core WP handles)
        wp_enqueue_script('react');
        wp_enqueue_script('react-dom');

        // Compiled Portal JS
        wp_enqueue_script('chroma-portal-js', $js_url, ['react', 'react-dom'], $js_version, true);

        // Pass config to JS
        wp_localize_script('chroma-portal-js', 'chromaPortalConfig', [
            'apiUrl' => get_rest_url(),
            'googleClientId' => trim(get_option('chroma_google_client_id', '')),
        ]);
    }
}

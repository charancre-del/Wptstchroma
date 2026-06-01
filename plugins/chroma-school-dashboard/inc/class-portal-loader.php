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
                $this->setup_portal_document_filters();
                include($plugin_template);
                exit;
            }

            // Fallback to theme if plugin template missing (unlikely now)
            $theme_template = locate_template(['page-portal.php', 'page-director-portal.php']);

            if ($theme_template) {
                $this->setup_portal_document_filters();
                include($theme_template);
                exit;
            }

            echo "Portal template not found.";
            exit;
        }
        return $template;
    }

    private function setup_portal_document_filters()
    {
        $title = __('Director Portal | Chroma Early Learning', 'chroma-school-dashboard');
        $canonical = home_url('/portal/');

        add_filter('pre_get_document_title', static function () use ($title) {
            return $title;
        }, PHP_INT_MAX);

        add_filter('wpseo_title', static function () use ($title) {
            return $title;
        }, PHP_INT_MAX);

        add_filter('wpseo_canonical', static function () use ($canonical) {
            return $canonical;
        }, PHP_INT_MAX);

        add_filter('wpseo_opengraph_url', static function () use ($canonical) {
            return $canonical;
        }, PHP_INT_MAX);

        add_filter('wpseo_robots', static function () {
            return 'noindex,nofollow,noarchive,nosnippet';
        }, PHP_INT_MAX);
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

        // Portal CSS
        wp_enqueue_style('chroma-portal-css', $css_url, [], $css_version);
        wp_add_inline_style('chroma-portal-css', $this->get_local_font_face_css());

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

    /**
     * Local font-face declarations shared by portal styles.
     *
     * @return string
     */
    private function get_local_font_face_css()
    {
        $outfit_regular = esc_url_raw(get_theme_file_uri('/assets/webfonts/Outfit-Regular.woff2'));
        $outfit_medium = esc_url_raw(get_theme_file_uri('/assets/webfonts/Outfit-Medium.woff2'));
        $outfit_bold = esc_url_raw(get_theme_file_uri('/assets/webfonts/Outfit-Bold.woff2'));
        $playfair_semibold = esc_url_raw(get_theme_file_uri('/assets/webfonts/PlayfairDisplay-SemiBold.woff2'));
        $playfair_bold = esc_url_raw(get_theme_file_uri('/assets/webfonts/PlayfairDisplay-Bold.woff2'));

        return "
@font-face{font-family:'Outfit';src:url('{$outfit_regular}') format('woff2');font-weight:400;font-style:normal;font-display:swap;}
@font-face{font-family:'Outfit';src:url('{$outfit_medium}') format('woff2');font-weight:500;font-style:normal;font-display:swap;}
@font-face{font-family:'Outfit';src:url('{$outfit_bold}') format('woff2');font-weight:700;font-style:normal;font-display:swap;}
@font-face{font-family:'Playfair Display';src:url('{$playfair_semibold}') format('woff2');font-weight:600;font-style:normal;font-display:swap;}
@font-face{font-family:'Playfair Display';src:url('{$playfair_bold}') format('woff2');font-weight:700;font-style:normal;font-display:swap;}";
    }
}

<?php

class Chroma_School_Template_Loader
{
    public function __construct()
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'load_tv_template']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_tv_assets']);
        add_action('wp_head', [$this, 'tv_meta_tags']);
    }

    public function add_rewrite_rules()
    {
        add_rewrite_rule('^tv/([^/]*)/?', 'index.php?post_type=chroma_school&name=$matches[1]&chroma_view=tv', 'top');
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'chroma_view';
        return $vars;
    }

    public function load_tv_template($template)
    {
        if (get_query_var('chroma_view') === 'tv') {
            $plugin_template = CHROMA_SCHOOL_DB_PATH . 'templates/tv-dashboard.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }

    public function tv_meta_tags()
    {
        if (get_query_var('chroma_view') === 'tv') {
            echo '<meta name="robots" content="noindex,nofollow,noarchive,nosnippet">' . "\n";
        }
    }

    public function enqueue_tv_assets()
    {
        if (get_query_var('chroma_view') !== 'tv') {
            return;
        }

        $tv_css_path = CHROMA_SCHOOL_DB_PATH . 'assets/dist/portal.css';
        $tv_js_path = CHROMA_SCHOOL_DB_PATH . 'assets/js/tv-dashboard.js';
        $tv_css_version = file_exists($tv_css_path) ? (string) filemtime($tv_css_path) : CHROMA_SCHOOL_VERSION;
        $tv_js_version = file_exists($tv_js_path) ? (string) filemtime($tv_js_path) : CHROMA_SCHOOL_VERSION;

        wp_enqueue_style(
            'chroma-tv-dashboard-css',
            CHROMA_SCHOOL_DB_URL . 'assets/dist/portal.css',
            [],
            $tv_css_version
        );

        wp_enqueue_style(
            'chroma-tv-font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );

        wp_enqueue_script(
            'chroma-tv-pdfjs',
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',
            [],
            '3.11.174',
            true
        );

        wp_add_inline_script(
            'chroma-tv-pdfjs',
            "if (window.pdfjsLib) { window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js'; }",
            'after'
        );

        wp_enqueue_script(
            'chroma-tv-dashboard-js',
            CHROMA_SCHOOL_DB_URL . 'assets/js/tv-dashboard.js',
            ['chroma-tv-pdfjs'],
            $tv_js_version,
            true
        );
    }
}

<?php

class Chroma_School_Template_Loader
{
    public function __construct()
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'load_tv_template']);
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
}

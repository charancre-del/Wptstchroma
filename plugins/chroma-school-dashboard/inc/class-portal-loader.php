<?php

class Chroma_School_Portal_Loader
{
    public function __construct()
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'load_portal_template']);
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
}

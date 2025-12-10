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
        // Matches /portal/ and anything after it (for client-side routing)
        add_rewrite_rule('^portal/?$', 'index.php?chroma_view=portal', 'top');
        add_rewrite_rule('^portal/(.+)?$', 'index.php?chroma_view=portal', 'top');
    }

    public function add_query_vars($vars)
    {
        // Already added in Template Loader, but ensuring it's here if separated
        if (!in_array('chroma_view', $vars)) {
            $vars[] = 'chroma_view';
        }
        return $vars;
    }

    public function load_portal_template($template)
    {
        if (get_query_var('chroma_view') === 'portal') {
            // Look for the built index.html in assets/portal
            $portal_index = CHROMA_SCHOOL_DB_PATH . 'assets/portal/index.html';

            if (file_exists($portal_index)) {
                // We serve the HTML directly. 
                // Note: Next.js assets (_next/static/...) must be resolvable.
                // We might need to adjust base path in Next.js or use relative paths.
                // For now, serving the catch-all index.html.
                readfile($portal_index);
                exit;
            } else {
                // Fallback if not built yet
                echo "Portal is not deployed. Please run 'npm run build' and upload 'out' folder to 'plugins/chroma-school-dashboard/assets/portal'.";
                exit;
            }
        }
        return $template;
    }
}

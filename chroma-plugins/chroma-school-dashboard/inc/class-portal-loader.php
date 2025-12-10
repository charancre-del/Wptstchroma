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

            // 1. Determine requested file path relative to /portal/
            // URL: /portal/_next/static/css/styles.css -> file: assets/portal/_next/static/css/styles.css

            $request_uri = $_SERVER['REQUEST_URI'];

            // Extract path relative to /portal/
            // Handles both /portal/foo.css and /portal/foo.css?v=1
            $path = parse_url($request_uri, PHP_URL_PATH);

            // Find where /portal/ starts
            $pos = strpos($path, '/portal/');
            if ($pos !== false) {
                // Remove everything before and including '/portal/' to get relative path
                $rel_path = substr($path, $pos + strlen('/portal/'));
            } else {
                $rel_path = '';
            }

            // Security: Prevent directory traversal
            if (strpos($rel_path, '..') !== false) {
                status_header(403);
                exit('Forbidden');
            }

            $file_path = CHROMA_SCHOOL_DB_PATH . 'assets/portal/' . $rel_path;

            // If it is a real file, serve it
            if ($rel_path && file_exists($file_path) && !is_dir($file_path)) {
                $ext = pathinfo($file_path, PATHINFO_EXTENSION);
                $mime = 'text/html';

                switch ($ext) {
                    case 'css':
                        $mime = 'text/css';
                        break;
                    case 'js':
                        $mime = 'application/javascript';
                        break;
                    case 'json':
                        $mime = 'application/json';
                        break;
                    case 'png':
                        $mime = 'image/png';
                        break;
                    case 'jpg':
                    case 'jpeg':
                        $mime = 'image/jpeg';
                        break;
                    case 'svg':
                        $mime = 'image/svg+xml';
                        break;
                    case 'woff2':
                        $mime = 'font/woff2';
                        break;
                    case 'ico':
                        $mime = 'image/x-icon';
                        break;
                }

                header('Content-Type: ' . $mime);
                readfile($file_path);
                exit;
            }

            // Fallback: Serve index.html for client-side routing
            $index_path = CHROMA_SCHOOL_DB_PATH . 'assets/portal/index.html';
            if (file_exists($index_path)) {
                readfile($index_path);
                exit;
            } else {
                echo "Portal not deployed.";
                exit;
            }
        }
        return $template;
    }
}

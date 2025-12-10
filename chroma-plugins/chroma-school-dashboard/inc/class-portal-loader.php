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

            $request_uri = $_SERVER['REQUEST_URI'];
            $path = parse_url($request_uri, PHP_URL_PATH);

            // Logic to find relative path
            $pos = strpos($path, '/portal/');
            if ($pos !== false) {
                $rel_path = substr($path, $pos + strlen('/portal/'));
            } else {
                $rel_path = '';
            }

            $rel_path = urldecode($rel_path);

            // Strip trailing slashes and spaces (fixes /file.js/ issue)
            $rel_path = rtrim($rel_path, '/ ');

            if (strpos($rel_path, '..') !== false) {
                status_header(403);
                exit('Forbidden');
            }

            $base_path = CHROMA_SCHOOL_DB_PATH . 'assets/portal/';
            $file_path = $base_path . $rel_path;

            // DEBUG LOGGING
            $log_entry = sprintf(
                "[%s] URI: %s | Rel: %s | Full: %s | Exists: %s | IsDir: %s\n",
                date('Y-m-d H:i:s'),
                $request_uri,
                $rel_path,
                $file_path,
                file_exists($file_path) ? 'YES' : 'NO',
                is_dir($file_path) ? 'YES' : 'NO'
            );
            // Log to wp-content/uploads/portal-debug.log so it's writeable
            $log_file = WP_CONTENT_DIR . '/uploads/portal-debug.log';
            @file_put_contents($log_file, $log_entry, FILE_APPEND);


            if ($rel_path && file_exists($file_path) && !is_dir($file_path)) {
                $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                $mime = 'text/html';

                $allowed_exts = ['css', 'js', 'json', 'png', 'jpg', 'jpeg', 'svg', 'woff2', 'ico', 'txt', 'map'];

                if (in_array($ext, $allowed_exts)) {
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
                        case 'txt':
                            $mime = 'text/plain';
                            break;
                        case 'map':
                            $mime = 'application/json';
                            break;
                    }

                    header('Content-Type: ' . $mime);
                    header('Cache-Control: public, max-age=31536000');
                    readfile($file_path);
                    exit;
                }
            }

            // Fallback
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

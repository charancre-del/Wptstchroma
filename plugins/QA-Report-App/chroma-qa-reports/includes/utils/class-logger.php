<?php
/**
 * Forensic Integration Logger
 *
 * @package ChromaQAReports
 */

namespace ChromaQA\Utils;

class Logger
{

    /**
     * Log an integration event.
     * 
     * @param string $service  Service name (e.g., 'GoogleDrive', 'Gemini').
     * @param string $action   Action performed.
     * @param mixed  $request  Request data.
     * @param mixed  $response Response data.
     * @param string $level    Log level (debug, info, warning, critical).
     */
    public static function log($service, $action, $request, $response, $level = 'info')
    {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/cqa-logs';

        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            // Protect directory
            file_put_contents($log_dir . '/index.php', '<?php // Silence');
            file_put_contents($log_dir . '/.htaccess', 'deny from all');
        }

        $log_file = $log_dir . '/integration.log';
        $timestamp = current_time('mysql');

        $entry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'service' => $service,
            'action' => $action,
            'user_id' => get_current_user_id(),
            'request' => $request,
            'response' => $response,
        ];

        error_log(
            sprintf("[CQA][%s][%s] %s: %s", strtoupper($level), $service, $action, json_encode($response))
        );

        file_put_contents(
            $log_file,
            json_encode($entry) . PHP_EOL,
            FILE_APPEND
        );
    }

    public static function debug($service, $action, $request, $response)
    {
        self::log($service, $action, $request, $response, 'debug');
    }

    public static function info($service, $action, $request, $response)
    {
        self::log($service, $action, $request, $response, 'info');
    }

    public static function error($service, $action, $request, $response)
    {
        self::log($service, $action, $request, $response, 'critical');
    }
}

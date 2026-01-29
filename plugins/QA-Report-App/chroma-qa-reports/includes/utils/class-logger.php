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
    /**
     * Redact sensitive keys from data.
     *
     * @param mixed $data Data to redact.
     * @return mixed Redacted data.
     */
    private static function redact_data($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (preg_match('/(password|token|auth|authorization|secret|key|api|access_token|refresh_token|card|cvv)/i', (string) $key)) {
                    $data[$key] = '***REDACTED***';
                } else {
                    $data[$key] = self::redact_data($value);
                }
            }
        } elseif (is_object($data)) {
            // Convert to array to avoid modifying original object reference or dealing with protected props
            $data = json_decode(json_encode($data), true);
            if (is_array($data)) {
                $data = self::redact_data($data);
            }
        }
        return $data;
    }

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
            FileSystem::put_contents($log_dir . '/index.php', '<?php // Silence');
            FileSystem::put_contents($log_dir . '/.htaccess', 'deny from all');
        }

        $log_file = $log_dir . '/integration.log';
        $timestamp = current_time('mysql');

        $entry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'service' => $service,
            'action' => $action,
            'user_id' => get_current_user_id(),
            'request' => self::redact_data($request),
            'response' => self::redact_data($response),
        ];

        error_log(
            sprintf("[CQA][%s][%s] %s: %s", strtoupper($level), $service, $action, json_encode($response))
        );

        FileSystem::append(
            $log_file,
            json_encode($entry) . PHP_EOL
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

    public static function warn($service, $action, $request, $response)
    {
        self::log($service, $action, $request, $response, 'warning');
    }

    public static function error($service, $action, $request, $response)
    {
        self::log($service, $action, $request, $response, 'critical');
    }
}

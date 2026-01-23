<?php
/**
 * Validation Logger
 * Handles logging of validation results and AI fixes for analytics
 *
 * @package Chroma_Excellence
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Chroma_Validation_Logger
{
    private $table_name;
    private $db_version = '1.0';

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'chroma_validation_logs';
    }

    /**
     * Initialize logger (check table)
     */
    public function init()
    {
        if (get_option('chroma_logger_db_version') != $this->db_version) {
            $this->install_table();
        }
    }

    /**
     * Create/Update Log Table
     */
    private function install_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            url varchar(500) NOT NULL,
            post_id bigint(20) DEFAULT 0,
            event_type varchar(50) NOT NULL, -- 'validation', 'fix', 'error'
            status varchar(50) NOT NULL,     -- 'valid', 'invalid', 'warning', 'success', 'failed'
            details longtext DEFAULT '',     -- JSON encoded details
            PRIMARY KEY  (id),
            KEY url (url(191)),
            KEY event_type (event_type),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('chroma_logger_db_version', $this->db_version);
    }

    /**
     * Log a validation result
     */
    public function log_validation($url, $result, $post_id = 0)
    {
        global $wpdb;
        
        $status = 'valid';
        if (!empty($result['errors'])) $status = 'invalid';
        elseif (!empty($result['warnings'])) $status = 'warning';
        elseif (isset($result['valid']) && !$result['valid']) $status = 'invalid';

        $details = [
            'error_count' => count($result['errors'] ?? []),
            'warning_count' => count($result['warnings'] ?? []),
            'errors' => array_slice($result['errors'] ?? [], 0, 5), // Store top 5
            'schema_types' => array_keys($result['schema_types'] ?? [])
        ];

        $wpdb->insert(
            $this->table_name,
            [
                'time' => current_time('mysql'),
                'url' => $url,
                'post_id' => $post_id,
                'event_type' => 'validation',
                'status' => $status,
                'details' => json_encode($details)
            ]
        );
    }

    /**
     * Log an AI fix attempt
     */
    public function log_fix($url, $success, $error_msg = '', $post_id = 0)
    {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            [
                'time' => current_time('mysql'),
                'url' => $url,
                'post_id' => $post_id,
                'event_type' => 'fix',
                'status' => $success ? 'success' : 'failed',
                'details' => json_encode(['error' => $error_msg])
            ]
        );
    }
    
    /**
     * Log a System Error
     */
    public function log_error($url, $message, $context = [])
    {
        global $wpdb;
        
        $wpdb->insert(
             $this->table_name,
             [
                 'time' => current_time('mysql'),
                 'url' => $url,
                 'post_id' => 0,
                 'event_type' => 'system_error',
                 'status' => 'error',
                 'details' => json_encode(['message' => $message, 'context' => $context])
             ]
        );
    }
    /**
     * Get Aggregated Stats (Feature 14)
     */
    public static function get_stats_summary()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chroma_validation_logs';
        
        // Check if table exists
        if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return ['total' => 0, 'invalid' => 0, 'fixes' => 0, 'health' => 100];
        }

        $total_validated = $wpdb->get_var("SELECT COUNT(DISTINCT url) FROM $table WHERE event_type = 'validation'");
        $invalid_count = $wpdb->get_var("SELECT COUNT(DISTINCT url) FROM $table WHERE event_type = 'validation' AND status = 'invalid'");
        $fix_success_count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE event_type = 'fix' AND status = 'success'");
        
        return [
            'total' => (int) $total_validated,
            'invalid' => (int) $invalid_count,
            'fixes' => (int) $fix_success_count,
            'health' => $total_validated > 0 ? round((($total_validated - $invalid_count) / $total_validated) * 100) : 100
        ];
    }
}



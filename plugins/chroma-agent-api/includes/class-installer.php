<?php

namespace ChromaAgentAPI;

if (!defined('ABSPATH')) {
    exit;
}

class Installer
{
    public static function activate(): void
    {
        self::create_tables();
        self::seed_defaults();

        if (get_option(Utils::OPTION_ENABLED, null) === null) {
            add_option(Utils::OPTION_ENABLED, 1);
        }

        flush_rewrite_rules(false);
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules(false);
    }

    private static function create_tables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $keys = Utils::table('keys');
        $audit = Utils::table('audit_log');
        $snapshots = Utils::table('option_snapshots');

        $sql = [];

        $sql[] = "CREATE TABLE {$keys} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            label VARCHAR(191) NOT NULL,
            key_hash VARCHAR(255) NOT NULL,
            key_prefix VARCHAR(64) NOT NULL,
            scopes_json LONGTEXT NOT NULL,
            ip_allowlist_json LONGTEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            rate_limit_per_min INT(11) NOT NULL DEFAULT 120,
            expires_at DATETIME NULL,
            created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            last_used_at DATETIME NULL,
            last_used_ip VARCHAR(64) NULL,
            revoked_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY status (status),
            KEY expires_at (expires_at)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$audit} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id VARCHAR(64) NOT NULL,
            actor_key_id BIGINT(20) UNSIGNED NOT NULL,
            scope VARCHAR(100) NOT NULL,
            method VARCHAR(12) NOT NULL,
            route VARCHAR(255) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_id VARCHAR(191) NOT NULL,
            dry_run TINYINT(1) NOT NULL DEFAULT 0,
            before_json LONGTEXT NULL,
            after_json LONGTEXT NULL,
            diff_json LONGTEXT NULL,
            status_code INT(11) NOT NULL DEFAULT 200,
            error_code VARCHAR(191) NULL,
            ip VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY actor_key_id (actor_key_id),
            KEY created_at (created_at),
            KEY route (route)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$snapshots} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_key_id BIGINT(20) UNSIGNED NOT NULL,
            scope VARCHAR(100) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_key VARCHAR(191) NOT NULL,
            old_value_json LONGTEXT NULL,
            new_value_json LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY target_type (target_type),
            KEY target_key (target_key),
            KEY created_at (created_at)
        ) {$charset};";

        foreach ($sql as $statement) {
            dbDelta($statement);
        }
    }

    private static function seed_defaults(): void
    {
        if (get_option(Utils::OPTION_THEME_OPTION_ALLOWLIST, null) === null) {
            add_option(Utils::OPTION_THEME_OPTION_ALLOWLIST, Utils::get_theme_option_allowlist());
        }

        if (get_option(Utils::OPTION_THEME_MOD_ALLOWLIST, null) === null) {
            add_option(Utils::OPTION_THEME_MOD_ALLOWLIST, Utils::get_theme_mod_allowlist());
        }

        if (get_option(Utils::OPTION_SEO_OPTION_ALLOWLIST, null) === null) {
            add_option(Utils::OPTION_SEO_OPTION_ALLOWLIST, Utils::get_seo_option_allowlist());
        }

        if (get_option(Utils::OPTION_SEO_META_ALLOWLIST, null) === null) {
            add_option(Utils::OPTION_SEO_META_ALLOWLIST, Utils::get_seo_meta_allowlist());
        }
    }
}

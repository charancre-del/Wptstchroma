<?php

namespace ChromaAgentAPI;

if (!defined('ABSPATH')) {
    exit;
}

class Bootstrap
{
    public static function init(): void
    {
        self::load_dependencies();
        Routes\Geo_Routes::init();

        add_action('rest_api_init', [__CLASS__, 'register_routes']);

        if (is_admin()) {
            Admin::init();
        }

        if (defined('WP_CLI') && WP_CLI) {
            CLI::register();
        }
    }

    public static function register_routes(): void
    {
        if (!Utils::truthy(get_option(Utils::OPTION_ENABLED, 1))) {
            return;
        }

        Routes\Discovery_Routes::register();
        Routes\Key_Routes::register();
        Routes\Content_Routes::register();
        Routes\Theme_Routes::register();
        Routes\SEO_Routes::register();
        Routes\Media_Routes::register();
        Routes\Audit_Routes::register();
        Routes\Geo_Routes::register();
    }

    private static function load_dependencies(): void
    {
        require_once CHROMA_AGENT_API_DIR . 'includes/class-diff.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/class-rate-limiter.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/class-key-store.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/class-auth.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/class-audit-log.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/class-snapshot-store.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/class-cli.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/class-admin.php';

        require_once CHROMA_AGENT_API_DIR . 'includes/routes/class-discovery-routes.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/routes/class-key-routes.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/routes/class-content-routes.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/routes/class-theme-routes.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/routes/class-seo-routes.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/routes/class-media-routes.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/routes/class-audit-routes.php';
        require_once CHROMA_AGENT_API_DIR . 'includes/routes/class-geo-routes.php';
    }
}

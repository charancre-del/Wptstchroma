<?php
/**
 * Plugin Name: Chroma Agent API
 * Description: Secure API-key automation layer for content, theme, and SEO management.
 * Version: 1.0.0
 * Author: Chroma
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CHROMA_AGENT_API_VERSION')) {
    define('CHROMA_AGENT_API_VERSION', '1.0.0');
}

if (!defined('CHROMA_AGENT_API_FILE')) {
    define('CHROMA_AGENT_API_FILE', __FILE__);
}

if (!defined('CHROMA_AGENT_API_DIR')) {
    define('CHROMA_AGENT_API_DIR', plugin_dir_path(__FILE__));
}

if (!defined('CHROMA_AGENT_API_URL')) {
    define('CHROMA_AGENT_API_URL', plugin_dir_url(__FILE__));
}

require_once CHROMA_AGENT_API_DIR . 'includes/class-utils.php';
require_once CHROMA_AGENT_API_DIR . 'includes/class-installer.php';
require_once CHROMA_AGENT_API_DIR . 'includes/class-bootstrap.php';

register_activation_hook(__FILE__, ['ChromaAgentAPI\\Installer', 'activate']);
register_deactivation_hook(__FILE__, ['ChromaAgentAPI\\Installer', 'deactivate']);

add_action('plugins_loaded', static function () {
    ChromaAgentAPI\Bootstrap::init();
});

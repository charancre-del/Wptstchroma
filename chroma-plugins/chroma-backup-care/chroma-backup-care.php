<?php
/**
 * Plugin Name: Chroma Backup Care
 * Description: Server-side backup-care cart with GHL records, invoices, connected Stripe payments, and calendar coordination.
 * Version: 0.8.0
 * Author: Chroma Development Team
 * Text Domain: chroma-backup-care
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CHROMA_BACKUP_CARE_VERSION', '0.8.0');
define('CHROMA_BACKUP_CARE_FILE', __FILE__);
define('CHROMA_BACKUP_CARE_DIR', plugin_dir_path(__FILE__));
define('CHROMA_BACKUP_CARE_URL', plugin_dir_url(__FILE__));

require_once CHROMA_BACKUP_CARE_DIR . 'includes/class-config.php';
require_once CHROMA_BACKUP_CARE_DIR . 'includes/class-domain.php';
require_once CHROMA_BACKUP_CARE_DIR . 'includes/class-store.php';
require_once CHROMA_BACKUP_CARE_DIR . 'includes/class-ghl-client.php';
require_once CHROMA_BACKUP_CARE_DIR . 'includes/class-parent-access.php';
require_once CHROMA_BACKUP_CARE_DIR . 'includes/class-notifications.php';
require_once CHROMA_BACKUP_CARE_DIR . 'includes/class-service.php';
require_once CHROMA_BACKUP_CARE_DIR . 'includes/class-rest-controller.php';
require_once CHROMA_BACKUP_CARE_DIR . 'includes/class-plugin.php';

if (defined('WP_CLI') && WP_CLI) {
    require_once CHROMA_BACKUP_CARE_DIR . 'includes/class-cli.php';
    Chroma_Backup_Care_CLI::register();
}

register_activation_hook(__FILE__, array('Chroma_Backup_Care_Plugin', 'activate'));
register_deactivation_hook(__FILE__, array('Chroma_Backup_Care_Plugin', 'deactivate'));
Chroma_Backup_Care_Plugin::boot();

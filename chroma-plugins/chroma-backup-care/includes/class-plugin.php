<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Chroma_Backup_Care_Plugin
{
    private static $instance;
    private $config;
    private $store;
    private $service;
    private $parent_access;

    public static function boot()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function activate()
    {
        Chroma_Backup_Care_Store::install();
        add_option('chroma_backup_care_mode', 'disabled');
        add_option('chroma_backup_care_checkout_enabled', false);
        if (!wp_next_scheduled('chroma_backup_care_cleanup')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'chroma_backup_care_cleanup');
        }
        if (!wp_next_scheduled('chroma_backup_care_arrival_reminders')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'chroma_backup_care_arrival_reminders');
        }
        if (!wp_next_scheduled('chroma_backup_care_reconcile_invoices')) {
            wp_schedule_event(time() + (5 * MINUTE_IN_SECONDS), 'chroma_five_minutes', 'chroma_backup_care_reconcile_invoices');
        }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('chroma_backup_care_cleanup');
        wp_clear_scheduled_hook('chroma_backup_care_arrival_reminders');
        wp_clear_scheduled_hook('chroma_backup_care_reconcile_invoices');
    }

    private function __construct()
    {
        $this->config = new Chroma_Backup_Care_Config();
        $this->store = new Chroma_Backup_Care_Store();
        $ghl = new Chroma_Backup_Care_GHL_Client($this->config);
        $this->parent_access = new Chroma_Backup_Care_Parent_Access($this->config);
        $this->service = new Chroma_Backup_Care_Service(
            $this->config,
            $this->store,
            $ghl,
            $this->parent_access
        );
        $notifications = new Chroma_Backup_Care_Notifications($this->config, $this->store, $ghl);
        $notifications->register();

        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_filter('cron_schedules', array($this, 'cron_schedules'));
        add_action('chroma_backup_care_cleanup', array($this->store, 'purge_expired'));
        add_action('chroma_backup_care_reconcile_invoices', array($this->service, 'reconcile_pending_invoices'));
        add_shortcode('chroma_backup_care_cart', array($this, 'cart_shortcode'));
        add_shortcode('chroma_backup_care_manage', array($this, 'manage_shortcode'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'register_settings_page'));
    }

    public function cron_schedules($schedules)
    {
        $schedules['chroma_five_minutes'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => 'Every five minutes',
        );
        return $schedules;
    }

    public function register_rest_routes()
    {
        $controller = new Chroma_Backup_Care_REST_Controller(
            $this->config,
            $this->service,
            $this->store,
            $this->parent_access
        );
        $controller->register_routes();
    }

    public function register_settings()
    {
        register_setting('chroma_backup_care', 'chroma_backup_care_mode', array(
            'type' => 'string',
            'default' => 'disabled',
            'sanitize_callback' => function ($value) {
                $value = in_array($value, array('disabled', 'test', 'live'), true) ? $value : 'disabled';
                if ($value === 'live' && $this->config->live_release_errors()) {
                    add_settings_error(
                        'chroma_backup_care_mode',
                        'chroma_backup_care_live_blocked',
                        'Live mode remains blocked until every release gate and server approval is complete.'
                    );
                    return 'disabled';
                }
                return $value;
            },
        ));
        register_setting('chroma_backup_care', 'chroma_backup_care_checkout_enabled', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ));
        register_setting('chroma_backup_care', 'chroma_backup_care_email_from', array(
            'type' => 'string',
            'default' => 'info@chromaela.com',
            'sanitize_callback' => 'sanitize_email',
        ));
    }

    public function register_settings_page()
    {
        add_options_page(
            'Backup Care',
            'Backup Care',
            'manage_options',
            'chroma-backup-care',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $readiness = $this->config->readiness();
        $live_blocked = (bool) $this->config->live_release_errors();
        ?>
        <div class="wrap">
            <h1>Chroma Backup Care</h1>
            <p><strong>Readiness:</strong> <?php echo $readiness['ready'] ? 'Ready' : 'Blocked'; ?></p>
            <?php if (!$readiness['ready']) : ?>
                <ul>
                    <?php foreach ($readiness['errors'] as $error) : ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p>Payment: GHL invoice using the Stripe account connected inside this GHL sub-account.</p>
            <p>The GHL token must be supplied through a server constant or environment variable. It is never displayed here.</p>
            <form action="options.php" method="post">
                <?php settings_fields('chroma_backup_care'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="chroma_backup_care_mode">Mode</label></th>
                        <td>
                            <select id="chroma_backup_care_mode" name="chroma_backup_care_mode">
                                <?php foreach (array('disabled', 'test', 'live') as $mode) : ?>
                                    <option value="<?php echo esc_attr($mode); ?>" <?php selected($this->config->mode(), $mode); ?> <?php disabled($mode === 'live' && $live_blocked); ?>><?php echo esc_html(ucfirst($mode)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Checkout feature flag</th>
                        <td><label><input type="checkbox" name="chroma_backup_care_checkout_enabled" value="1" <?php checked($this->config->is_checkout_enabled()); ?>> Enable checkout</label></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function cart_shortcode($attributes = array())
    {
        $attributes = shortcode_atts(array('campus' => ''), $attributes, 'chroma_backup_care_cart');
        $requested_campus = sanitize_key((string) $attributes['campus']);
        if ($requested_campus === '' && isset($_GET['campus'])) {
            $requested_campus = sanitize_key(wp_unslash($_GET['campus']));
        }
        if (!isset($this->config->campuses()[$requested_campus])) {
            $requested_campus = '';
        }

        wp_enqueue_style(
            'chroma-backup-care-cart',
            CHROMA_BACKUP_CARE_URL . 'assets/backup-care-cart.css',
            array(),
            CHROMA_BACKUP_CARE_VERSION
        );
        wp_enqueue_script(
            'chroma-backup-care-cart',
            CHROMA_BACKUP_CARE_URL . 'assets/backup-care-cart.js',
            array(),
            CHROMA_BACKUP_CARE_VERSION,
            true
        );
        wp_localize_script('chroma-backup-care-cart', 'ChromaBackupCare', array(
            'configUrl' => rest_url('chroma-backup-care/v1/config'),
            'quoteUrl' => rest_url('chroma-backup-care/v1/quote'),
            'requestAccessUrl' => rest_url('chroma-backup-care/v1/parent-access/request'),
            'verifyAccessUrl' => rest_url('chroma-backup-care/v1/parent-access/verify'),
            'checkoutUrl' => rest_url('chroma-backup-care/v1/checkout'),
            'paymentStatusUrl' => rest_url('chroma-backup-care/v1/payment-status'),
            'manageUrl' => rest_url('chroma-backup-care/v1/manage'),
            'cancelUrl' => rest_url('chroma-backup-care/v1/cancel'),
            'rescheduleUrl' => rest_url('chroma-backup-care/v1/reschedule'),
        ));
        return sprintf(
            '<div class="chroma-backup-care-cart" data-chroma-backup-care-cart data-campus="%s"><p>Loading secure booking...</p></div>',
            esc_attr($requested_campus)
        );
    }

    public function manage_shortcode()
    {
        wp_enqueue_style(
            'chroma-backup-care-cart',
            CHROMA_BACKUP_CARE_URL . 'assets/backup-care-cart.css',
            array(),
            CHROMA_BACKUP_CARE_VERSION
        );
        wp_enqueue_script(
            'chroma-backup-care-manage',
            CHROMA_BACKUP_CARE_URL . 'assets/backup-care-manage.js',
            array(),
            CHROMA_BACKUP_CARE_VERSION,
            true
        );
        wp_localize_script('chroma-backup-care-manage', 'ChromaBackupCareManage', array(
            'configUrl' => rest_url('chroma-backup-care/v1/config'),
            'manageUrl' => rest_url('chroma-backup-care/v1/manage'),
            'cancelUrl' => rest_url('chroma-backup-care/v1/cancel'),
            'rescheduleUrl' => rest_url('chroma-backup-care/v1/reschedule'),
        ));
        return '<div class="chroma-backup-care-cart" data-chroma-backup-care-manage><p class="cbc-status">Loading your reservation...</p></div>';
    }
}

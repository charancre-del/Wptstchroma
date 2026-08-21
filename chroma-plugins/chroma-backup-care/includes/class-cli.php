<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Chroma_Backup_Care_CLI
{
    const PROVISION_CONFIRMATION = 'PROVISION_BACKUP_CARE_STAGING_PAGES';
    const ENABLE_TEST_CONFIRMATION = 'ENABLE_BACKUP_CARE_TEST_CHECKOUT';

    public static function register()
    {
        WP_CLI::add_command('chroma backup-care', new self());
    }

    /**
     * Prints a secret-free readiness and WordPress resource report.
     *
     * ## EXAMPLES
     *
     *     wp chroma backup-care status
     *
     * @subcommand status
     */
    public function status()
    {
        $config = new Chroma_Backup_Care_Config();
        $readiness = $config->readiness();
        $pages = array();
        foreach ($this->page_specs() as $spec) {
            $page = get_page_by_path($spec['slug'], OBJECT, 'page');
            $pages[$spec['slug']] = $page ? array(
                'id' => (int) $page->ID,
                'status' => $page->post_status,
                'url' => get_permalink($page),
            ) : null;
        }
        WP_CLI::line(wp_json_encode(array(
            'mode' => $config->mode(),
            'checkout_enabled' => $config->is_checkout_enabled(),
            'ready' => $readiness['ready'],
            'errors' => $readiness['errors'],
            'pages' => $pages,
            'payment_status_url' => rest_url('chroma-backup-care/v1/payment-status'),
            'payment_provider' => 'ghl_invoice_with_connected_stripe',
            'cleanup_cron_scheduled' => (bool) wp_next_scheduled('chroma_backup_care_cleanup'),
            'arrival_cron_scheduled' => (bool) wp_next_scheduled('chroma_backup_care_arrival_reminders'),
            'invoice_reconciliation_cron_scheduled' => (bool) wp_next_scheduled('chroma_backup_care_reconcile_invoices'),
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Creates the three slug-routed WordPress pages. Dry-run is the default.
     *
     * ## OPTIONS
     *
     * [--apply]
     * : Create missing pages.
     *
     * [--confirm=<phrase>]
     * : Required with --apply. Use PROVISION_BACKUP_CARE_STAGING_PAGES.
     *
     * [--status=<status>]
     * : Page status, either draft or publish. Defaults to draft.
     *
     * ## EXAMPLES
     *
     *     wp chroma backup-care provision-pages
     *     wp chroma backup-care provision-pages --apply --confirm=PROVISION_BACKUP_CARE_STAGING_PAGES --status=publish
     *
     * @subcommand provision-pages
     */
    public function provision_pages($args, $assoc_args)
    {
        $apply = isset($assoc_args['apply']);
        $status = isset($assoc_args['status']) ? sanitize_key($assoc_args['status']) : 'draft';
        if (!in_array($status, array('draft', 'publish'), true)) {
            WP_CLI::error('Page status must be draft or publish.');
        }
        if ($apply && (!isset($assoc_args['confirm'])
            || !hash_equals(self::PROVISION_CONFIRMATION, (string) $assoc_args['confirm']))) {
            WP_CLI::error('Apply requires --confirm=' . self::PROVISION_CONFIRMATION . '.');
        }

        foreach ($this->page_specs() as $spec) {
            $existing = get_page_by_path($spec['slug'], OBJECT, 'page');
            if ($existing) {
                WP_CLI::log('EXISTS ' . $spec['slug'] . ' #' . $existing->ID . ' [' . $existing->post_status . ']');
                continue;
            }
            if (!$apply) {
                WP_CLI::log('WOULD_CREATE ' . $spec['slug'] . ' [' . $status . ']');
                continue;
            }
            $page_id = wp_insert_post(array(
                'post_type' => 'page',
                'post_status' => $status,
                'post_title' => $spec['title'],
                'post_name' => $spec['slug'],
                'post_content' => '',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ), true);
            if (is_wp_error($page_id)) {
                WP_CLI::error('Could not create ' . $spec['slug'] . ': ' . $page_id->get_error_message());
            }
            WP_CLI::success('Created ' . $spec['slug'] . ' #' . $page_id . ' [' . $status . ']');
        }
    }

    /**
     * Enables test checkout only when all runtime readiness checks pass.
     *
     * ## OPTIONS
     *
     * --confirm=<phrase>
     * : Use ENABLE_BACKUP_CARE_TEST_CHECKOUT.
     *
     * @subcommand enable-test
     */
    public function enable_test($args, $assoc_args)
    {
        if (!isset($assoc_args['confirm'])
            || !hash_equals(self::ENABLE_TEST_CONFIRMATION, (string) $assoc_args['confirm'])) {
            WP_CLI::error('Test enablement requires --confirm=' . self::ENABLE_TEST_CONFIRMATION . '.');
        }
        $previous_mode = get_option('chroma_backup_care_mode', 'disabled');
        $previous_flag = get_option('chroma_backup_care_checkout_enabled', false);
        update_option('chroma_backup_care_mode', 'test');
        update_option('chroma_backup_care_checkout_enabled', true);
        $readiness = (new Chroma_Backup_Care_Config())->readiness();
        if (!$readiness['ready']) {
            update_option('chroma_backup_care_mode', $previous_mode);
            update_option('chroma_backup_care_checkout_enabled', $previous_flag);
            WP_CLI::error('Test checkout remains disabled: ' . implode(' ', $readiness['errors']));
        }
        WP_CLI::success('Backup Care test checkout is enabled. Run the acceptance transaction, then disable it.');
    }

    /**
     * Immediately disables checkout and resets mode to disabled.
     *
     * @subcommand disable
     */
    public function disable()
    {
        update_option('chroma_backup_care_checkout_enabled', false);
        update_option('chroma_backup_care_mode', 'disabled');
        WP_CLI::success('Backup Care checkout is disabled.');
    }

    /**
     * Reports paid child-date units affected by a proposed closure. Read-only.
     *
     * ## OPTIONS
     *
     * --date=<YYYY-MM-DD>
     * : Closure date.
     *
     * --campus=<campus-id|all>
     * : Campus scope.
     *
     * @subcommand closure-impact
     */
    public function closure_impact($args, $assoc_args)
    {
        $date = isset($assoc_args['date']) ? (string) $assoc_args['date'] : '';
        $campus_id = isset($assoc_args['campus']) ? sanitize_key($assoc_args['campus']) : '';
        $parsed_date = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('America/New_York'));
        if (!$parsed_date || $parsed_date->format('Y-m-d') !== $date) {
            WP_CLI::error('--date must use YYYY-MM-DD.');
        }
        $config = new Chroma_Backup_Care_Config();
        if ($campus_id !== 'all' && !isset($config->campuses()[$campus_id])) {
            WP_CLI::error('--campus must be all or a configured campus ID.');
        }
        $rows = (new Chroma_Backup_Care_Store())->closure_impact($campus_id, $date);
        $unit_count = array_sum(array_map(function ($row) {
            return (int) $row['unit_count'];
        }, $rows));
        $unit_amount = (int) $config->manifest()['business_rules']['price']['amount_cents'];
        WP_CLI::line(wp_json_encode(array(
            'changes_made' => false,
            'care_date' => $date,
            'campus_id' => $campus_id,
            'order_count' => count($rows),
            'unit_count' => $unit_count,
            'refund_exposure_cents' => $unit_count * $unit_amount,
            'orders' => $rows,
        ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function page_specs()
    {
        return array(
            array('slug' => 'backup-care', 'title' => 'Backup Care'),
            array('slug' => 'backup-care-confirmation', 'title' => 'Backup Care Confirmation'),
            array('slug' => 'backup-care-manage', 'title' => 'Manage Backup Care'),
        );
    }
}

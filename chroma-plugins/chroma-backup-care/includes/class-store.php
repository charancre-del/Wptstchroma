<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Chroma_Backup_Care_Store
{
    private $wpdb;
    private $orders_table;
    private $holds_table;
    private $events_table;
    private $rate_limits_table;

    public function __construct($wpdb_instance = null)
    {
        global $wpdb;
        $this->wpdb = $wpdb_instance ?: $wpdb;
        $this->orders_table = $this->wpdb->prefix . 'chroma_backup_care_orders';
        $this->holds_table = $this->wpdb->prefix . 'chroma_backup_care_holds';
        $this->events_table = $this->wpdb->prefix . 'chroma_backup_care_events';
        $this->rate_limits_table = $this->wpdb->prefix . 'chroma_backup_care_rate_limits';
    }

    public static function install()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $orders = $wpdb->prefix . 'chroma_backup_care_orders';
        $holds = $wpdb->prefix . 'chroma_backup_care_holds';
        $events = $wpdb->prefix . 'chroma_backup_care_events';
        $rate_limits = $wpdb->prefix . 'chroma_backup_care_rate_limits';

        dbDelta("CREATE TABLE {$orders} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            request_id varchar(128) NOT NULL,
            payload_hash char(64) NOT NULL,
            payload_cipher longtext NOT NULL,
            quote_json longtext NOT NULL,
            status varchar(32) NOT NULL,
            amount_cents bigint(20) unsigned NOT NULL,
            campus_id varchar(64) NOT NULL,
            payment_provider varchar(32) NOT NULL DEFAULT 'ghl_invoice',
            ghl_invoice_id varchar(64) NOT NULL DEFAULT '',
            ghl_invoice_status varchar(32) NOT NULL DEFAULT '',
            ghl_payment_transaction_id varchar(128) NOT NULL DEFAULT '',
            ghl_order_record_id varchar(64) NOT NULL DEFAULT '',
            contact_id varchar(64) NOT NULL DEFAULT '',
            expires_at datetime NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY request_id (request_id),
            KEY status_expires (status,expires_at),
            KEY ghl_invoice_id (ghl_invoice_id)
        ) {$charset};");

        dbDelta("CREATE TABLE {$holds} (
            line_item_key varchar(32) NOT NULL,
            booking_key char(64) NOT NULL,
            request_id varchar(128) NOT NULL,
            campus_id varchar(64) NOT NULL,
            client_child_id varchar(64) NOT NULL,
            child_record_id varchar(128) NOT NULL,
            care_date date NOT NULL,
            planned_dropoff_local char(5) NOT NULL DEFAULT '',
            ghl_calendar_event_id varchar(64) NOT NULL DEFAULT '',
            status varchar(24) NOT NULL,
            expires_at datetime NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (line_item_key),
            UNIQUE KEY booking_key (booking_key),
            KEY campus_date_status (campus_id,care_date,status),
            KEY request_id (request_id),
            KEY expires_at (expires_at)
        ) {$charset};");

        dbDelta("CREATE TABLE {$events} (
            event_id varchar(255) NOT NULL,
            event_type varchar(100) NOT NULL,
            status varchar(24) NOT NULL,
            error_code varchar(100) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (event_id),
            KEY status (status)
        ) {$charset};");

        dbDelta("CREATE TABLE {$rate_limits} (
            bucket_key char(64) NOT NULL,
            request_count int(10) unsigned NOT NULL,
            window_started_at bigint(20) unsigned NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (bucket_key),
            KEY window_started_at (window_started_at)
        ) {$charset};");
    }

    public function consume_rate_limit($bucket_key, $limit, $window, $timestamp = null)
    {
        $digest = hash('sha256', (string) $bucket_key);
        $limit = max(1, (int) $limit);
        $window = max(1, (int) $window);
        $timestamp = $timestamp === null ? time() : (int) $timestamp;
        $lock_name = 'chroma_bc_rl_' . substr($digest, 0, 40);
        $acquired = (int) $this->wpdb->get_var(
            $this->wpdb->prepare('SELECT GET_LOCK(%s, 3)', $lock_name)
        );
        if ($acquired !== 1) {
            throw new RuntimeException('The request limiter is busy.');
        }

        try {
            $row = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT request_count, window_started_at FROM {$this->rate_limits_table} WHERE bucket_key = %s",
                $digest
            ), ARRAY_A);
            $expired = !$row || (int) $row['window_started_at'] + $window <= $timestamp;
            if ($expired) {
                $saved = $this->wpdb->replace(
                    $this->rate_limits_table,
                    array(
                        'bucket_key' => $digest,
                        'request_count' => 1,
                        'window_started_at' => $timestamp,
                        'updated_at' => gmdate('Y-m-d H:i:s', $timestamp),
                    ),
                    array('%s', '%d', '%d', '%s')
                );
                if ($saved === false) {
                    throw new RuntimeException('The request limiter could not be updated.');
                }
                return true;
            }
            if ((int) $row['request_count'] >= $limit) {
                return false;
            }
            $updated = $this->wpdb->update(
                $this->rate_limits_table,
                array(
                    'request_count' => (int) $row['request_count'] + 1,
                    'updated_at' => gmdate('Y-m-d H:i:s', $timestamp),
                ),
                array('bucket_key' => $digest),
                array('%d', '%s'),
                array('%s')
            );
            if ($updated === false) {
                throw new RuntimeException('The request limiter could not be updated.');
            }
            return true;
        } finally {
            $this->wpdb->get_var($this->wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
        }
    }

    public function active_hold_counts($campus_id, array $dates)
    {
        $this->purge_expired();
        if (!$dates) {
            return array();
        }
        $placeholders = implode(',', array_fill(0, count($dates), '%s'));
        $params = array_merge(array($campus_id), array_values($dates));
        $sql = $this->wpdb->prepare(
            "SELECT care_date, COUNT(*) AS total FROM {$this->holds_table}
             WHERE campus_id = %s AND care_date IN ({$placeholders})
             AND status IN ('held','confirmed') GROUP BY care_date",
            $params
        );
        $rows = $this->wpdb->get_results($sql, ARRAY_A);
        $counts = array();
        foreach ($rows as $row) {
            $counts[$campus_id . '|' . $row['care_date']] = (int) $row['total'];
        }
        return $counts;
    }

    public function reserve($request_id, $payload_hash, $payload_cipher, array $quote, $campus_id, $capacity_limit, $expires_at)
    {
        return $this->with_order_lock($request_id, function () use (
            $request_id,
            $payload_hash,
            $payload_cipher,
            $quote,
            $campus_id,
            $capacity_limit,
            $expires_at
        ) {
            return $this->reserve_locked(
                $request_id,
                $payload_hash,
                $payload_cipher,
                $quote,
                $campus_id,
                $capacity_limit,
                $expires_at
            );
        });
    }

    private function reserve_locked(
        $request_id,
        $payload_hash,
        $payload_cipher,
        array $quote,
        $campus_id,
        $capacity_limit,
        $expires_at
    )
    {
        $this->purge_expired();
        $existing = $this->find_order($request_id);
        $retrying = false;
        if ($existing) {
            if (!hash_equals($existing['payload_hash'], $payload_hash)) {
                throw new DomainException('client_request_id was already used for a different order');
            }
            if (!in_array($existing['status'], array('checkout_failed', 'payment_failed', 'expired'), true)) {
                return $existing;
            }
            $retrying = true;
        }

        $now = gmdate('Y-m-d H:i:s');
        $per_date = array();
        foreach ($quote['line_items'] as $line) {
            $care_date = $line['care_date'];
            $per_date[$care_date] = isset($per_date[$care_date]) ? $per_date[$care_date] + 1 : 1;
        }
        $lock_names = $this->acquire_capacity_locks($campus_id, array_keys($per_date));
        $this->wpdb->query('START TRANSACTION');
        try {
            if ($retrying) {
                $this->wpdb->delete($this->holds_table, array('request_id' => $request_id), array('%s'));
                $this->update_order($request_id, array(
                    'payload_cipher' => $payload_cipher,
                    'quote_json' => wp_json_encode($quote),
                    'status' => 'held',
                    'amount_cents' => (int) $quote['total_amount_cents'],
                    'campus_id' => $campus_id,
                    'payment_provider' => 'ghl_invoice',
                    'ghl_invoice_id' => '',
                    'ghl_invoice_status' => '',
                    'ghl_payment_transaction_id' => '',
                    'expires_at' => $expires_at,
                ));
            } else {
                $inserted = $this->wpdb->insert(
                    $this->orders_table,
                    array(
                        'request_id' => $request_id,
                        'payload_hash' => $payload_hash,
                        'payload_cipher' => $payload_cipher,
                        'quote_json' => wp_json_encode($quote),
                        'status' => 'held',
                        'amount_cents' => (int) $quote['total_amount_cents'],
                        'campus_id' => $campus_id,
                        'payment_provider' => 'ghl_invoice',
                        'ghl_invoice_id' => '',
                        'ghl_invoice_status' => '',
                        'ghl_payment_transaction_id' => '',
                        'expires_at' => $expires_at,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ),
                    array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
                );
                if (!$inserted) {
                    throw new RuntimeException('Could not create the checkout hold.');
                }
            }

            foreach ($per_date as $care_date => $requested) {
                $count = (int) $this->wpdb->get_var($this->wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->holds_table}
                     WHERE campus_id = %s AND care_date = %s
                     AND status IN ('held','confirmed') FOR UPDATE",
                    $campus_id,
                    $care_date
                ));
                if ($count + $requested > $capacity_limit) {
                    throw new DomainException('Capacity became unavailable for ' . $care_date);
                }
            }

            foreach ($quote['line_items'] as $line) {
                $booking_key = hash('sha256', implode('|', array(
                    $campus_id,
                    $line['child_record_id'],
                    $line['care_date'],
                )));
                $inserted = $this->wpdb->insert(
                    $this->holds_table,
                    array(
                        'line_item_key' => $line['line_item_key'],
                        'booking_key' => $booking_key,
                        'request_id' => $request_id,
                        'campus_id' => $campus_id,
                        'client_child_id' => $line['client_child_id'],
                        'child_record_id' => $line['child_record_id'],
                        'care_date' => $line['care_date'],
                        'planned_dropoff_local' => isset($line['planned_dropoff_local']) ? $line['planned_dropoff_local'] : '',
                        'ghl_calendar_event_id' => '',
                        'status' => 'held',
                        'expires_at' => $expires_at,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ),
                    array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
                );
                if (!$inserted) {
                    throw new DomainException('A child is already reserved for that campus and date.');
                }
            }
            $this->wpdb->query('COMMIT');
        } catch (Throwable $error) {
            $this->wpdb->query('ROLLBACK');
            throw $error;
        } finally {
            $this->release_capacity_locks($lock_names);
        }
        return $this->find_order($request_id);
    }

    public function find_order($request_id)
    {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->orders_table} WHERE request_id = %s", $request_id),
            ARRAY_A
        );
        return is_array($row) ? $row : null;
    }

    public function with_order_lock($request_id, callable $callback)
    {
        $lock_name = 'chroma_bc_order_' . substr(hash('sha256', (string) $request_id), 0, 40);
        $acquired = (int) $this->wpdb->get_var(
            $this->wpdb->prepare('SELECT GET_LOCK(%s, 10)', $lock_name)
        );
        if ($acquired !== 1) {
            throw new RuntimeException('This order is being updated. Please try again.');
        }

        try {
            return $callback();
        } finally {
            $this->wpdb->get_var($this->wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock_name));
        }
    }

    public function find_order_by_invoice($invoice_id)
    {
        $row = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->orders_table} WHERE ghl_invoice_id = %s", $invoice_id),
            ARRAY_A
        );
        return is_array($row) ? $row : null;
    }

    public function pending_payment_orders($limit = 50)
    {
        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->orders_table} WHERE status = 'pending_payment' AND ghl_invoice_id <> '' ORDER BY updated_at ASC LIMIT %d",
                min(100, max(1, (int) $limit))
            ),
            ARRAY_A
        );
        return is_array($rows) ? $rows : array();
    }

    public function attach_invoice($request_id, $invoice_id, $invoice_status = 'sent')
    {
        $this->update_order($request_id, array(
            'payment_provider' => 'ghl_invoice',
            'ghl_invoice_id' => $invoice_id,
            'ghl_invoice_status' => $invoice_status,
            'status' => 'pending_payment',
        ));
    }

    public function mark_invoice_status($request_id, $invoice_status)
    {
        $this->update_order($request_id, array('ghl_invoice_status' => $invoice_status));
    }

    public function mark_paid($request_id, $transaction_id, $contact_id, $ghl_order_record_id, $clear_payload = true)
    {
        $this->wpdb->query('START TRANSACTION');
        try {
            $values = array(
                'status' => 'paid',
                'ghl_invoice_status' => 'paid',
                'ghl_payment_transaction_id' => $transaction_id,
                'contact_id' => $contact_id,
                'ghl_order_record_id' => $ghl_order_record_id,
            );
            if ($clear_payload) {
                $values['payload_cipher'] = '';
            }
            $this->update_order($request_id, $values);
            $this->wpdb->update(
                $this->holds_table,
                array('status' => 'confirmed', 'updated_at' => gmdate('Y-m-d H:i:s')),
                array('request_id' => $request_id),
                array('%s', '%s'),
                array('%s')
            );
            $this->wpdb->query('COMMIT');
        } catch (Throwable $error) {
            $this->wpdb->query('ROLLBACK');
            throw $error;
        }
    }

    public function mark_failed($request_id, $status = 'payment_failed', $clear_payload = true)
    {
        $values = array('status' => $status);
        if ($clear_payload) {
            $values['payload_cipher'] = '';
        }
        $this->update_order($request_id, $values);
        $this->wpdb->update(
            $this->holds_table,
            array('status' => 'released', 'updated_at' => gmdate('Y-m-d H:i:s')),
            array('request_id' => $request_id, 'status' => 'held'),
            array('%s', '%s'),
            array('%s', '%s')
        );
    }

    public function clear_order_payload($request_id)
    {
        $this->update_order($request_id, array('payload_cipher' => ''));
    }

    public function order_units($request_id)
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->holds_table} WHERE request_id = %s ORDER BY care_date, line_item_key",
                $request_id
            ),
            ARRAY_A
        );
    }

    public function mark_units(array $line_item_keys, $status)
    {
        if (!$line_item_keys) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($line_item_keys), '%s'));
        $params = array_merge(array($status, gmdate('Y-m-d H:i:s')), array_values($line_item_keys));
        $sql = $this->wpdb->prepare(
            "UPDATE {$this->holds_table} SET status = %s, updated_at = %s
             WHERE line_item_key IN ({$placeholders})",
            $params
        );
        if ($this->wpdb->query($sql) === false) {
            throw new RuntimeException('Could not update child-date unit state.');
        }
    }

    public function set_unit_calendar_event($line_item_key, $event_id)
    {
        $updated = $this->wpdb->update(
            $this->holds_table,
            array(
                'ghl_calendar_event_id' => (string) $event_id,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ),
            array('line_item_key' => (string) $line_item_key),
            array('%s', '%s'),
            array('%s')
        );
        if ($updated === false) {
            throw new RuntimeException('Could not store the GHL calendar appointment ID.');
        }
    }

    public function set_order_state($request_id, $status)
    {
        $this->update_order($request_id, array('status' => $status));
    }

    public function confirmed_unit_count($request_id)
    {
        return (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->holds_table} WHERE request_id = %s AND status = 'confirmed'",
            $request_id
        ));
    }

    public function reserve_reschedule(
        $line_item_key,
        $new_line_item_key,
        $new_date,
        $new_dropoff,
        $capacity_limit,
        $expires_at
    )
    {
        $unit = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->holds_table} WHERE line_item_key = %s", $line_item_key),
            ARRAY_A
        );
        if (!$unit || $unit['status'] !== 'confirmed') {
            throw new DomainException('The child-date unit is not available to reschedule.');
        }
        if ($unit['care_date'] === $new_date) {
            throw new DomainException('Select a different care date when rescheduling.');
        }
        $lock_names = $this->acquire_capacity_locks($unit['campus_id'], array($new_date));
        $this->wpdb->query('START TRANSACTION');
        try {
            $unit = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT * FROM {$this->holds_table} WHERE line_item_key = %s FOR UPDATE",
                    $line_item_key
                ),
                ARRAY_A
            );
            if (!$unit || $unit['status'] !== 'confirmed') {
                throw new DomainException('The child-date unit is not available to reschedule.');
            }
            $count = (int) $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->holds_table}
                 WHERE campus_id = %s AND care_date = %s
                 AND status IN ('held','confirmed') FOR UPDATE",
                $unit['campus_id'],
                $new_date
            ));
            if ($count + 1 > $capacity_limit) {
                throw new DomainException('Capacity became unavailable for ' . $new_date);
            }
            $booking_key = hash('sha256', implode('|', array(
                $unit['campus_id'], $unit['child_record_id'], $new_date,
            )));
            $inserted = $this->wpdb->insert(
                $this->holds_table,
                array(
                    'line_item_key' => $new_line_item_key,
                    'booking_key' => $booking_key,
                    'request_id' => $unit['request_id'],
                    'campus_id' => $unit['campus_id'],
                    'client_child_id' => $unit['client_child_id'],
                    'child_record_id' => $unit['child_record_id'],
                    'care_date' => $new_date,
                    'planned_dropoff_local' => $new_dropoff,
                    'ghl_calendar_event_id' => '',
                    'status' => 'held',
                    'expires_at' => $expires_at,
                    'created_at' => gmdate('Y-m-d H:i:s'),
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            if (!$inserted) {
                throw new DomainException('That child already has a booking for the requested campus and date.');
            }
            $this->wpdb->query('COMMIT');
        } catch (Throwable $error) {
            $this->wpdb->query('ROLLBACK');
            throw $error;
        } finally {
            $this->release_capacity_locks($lock_names);
        }
        return $unit;
    }

    public function finalize_reschedule($line_item_key, $new_line_item_key)
    {
        $this->wpdb->query('START TRANSACTION');
        try {
            $old_updated = $this->wpdb->update(
                $this->holds_table,
                array('status' => 'rescheduled', 'updated_at' => gmdate('Y-m-d H:i:s')),
                array('line_item_key' => $line_item_key, 'status' => 'confirmed'),
                array('%s', '%s'),
                array('%s', '%s')
            );
            $new_updated = $this->wpdb->update(
                $this->holds_table,
                array('status' => 'confirmed', 'updated_at' => gmdate('Y-m-d H:i:s')),
                array('line_item_key' => $new_line_item_key, 'status' => 'held'),
                array('%s', '%s'),
                array('%s', '%s')
            );
            if ($old_updated !== 1 || $new_updated !== 1) {
                throw new RuntimeException('Could not finalize the rescheduled child-date unit.');
            }
            $this->wpdb->query('COMMIT');
        } catch (Throwable $error) {
            $this->wpdb->query('ROLLBACK');
            throw $error;
        }
    }

    public function release_reschedule($new_line_item_key)
    {
        $deleted = $this->wpdb->delete(
            $this->holds_table,
            array('line_item_key' => $new_line_item_key, 'status' => 'held'),
            array('%s', '%s')
        );
        if ($deleted === false) {
            throw new RuntimeException('Could not release the failed reschedule hold.');
        }
    }

    public function begin_event($event_id, $event_type)
    {
        $existing = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->events_table} WHERE event_id = %s", $event_id),
            ARRAY_A
        );
        if ($existing) {
            $processing_is_fresh = $existing['status'] === 'processing'
                && strtotime($existing['updated_at'] . ' UTC') >= time() - (5 * MINUTE_IN_SECONDS);
            if ($existing['status'] === 'complete' || $processing_is_fresh) {
                return false;
            }
            $this->wpdb->update(
                $this->events_table,
                array('status' => 'processing', 'error_code' => '', 'updated_at' => gmdate('Y-m-d H:i:s')),
                array('event_id' => $event_id),
                array('%s', '%s', '%s'),
                array('%s')
            );
            return true;
        }
        $inserted = $this->wpdb->insert(
            $this->events_table,
            array(
                'event_id' => $event_id,
                'event_type' => $event_type,
                'status' => 'processing',
                'created_at' => gmdate('Y-m-d H:i:s'),
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
        return (bool) $inserted;
    }

    public function finish_event($event_id, $status, $error_code = '')
    {
        $this->wpdb->update(
            $this->events_table,
            array('status' => $status, 'error_code' => $error_code, 'updated_at' => gmdate('Y-m-d H:i:s')),
            array('event_id' => $event_id),
            array('%s', '%s', '%s'),
            array('%s')
        );
    }

    public function arrival_reminder_groups($care_date)
    {
        $sql = $this->wpdb->prepare(
            "SELECT h.request_id, h.campus_id, h.care_date, o.contact_id,
                    COUNT(*) AS unit_count,
                    MIN(h.planned_dropoff_local) AS earliest_dropoff_local
             FROM {$this->holds_table} h
             INNER JOIN {$this->orders_table} o ON o.request_id = h.request_id
             WHERE h.status = 'confirmed' AND h.care_date = %s
             AND o.status IN ('paid','partially_refunded') AND o.contact_id <> ''
             GROUP BY h.request_id, h.campus_id, h.care_date, o.contact_id",
            $care_date
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function closure_impact($campus_id, $care_date)
    {
        $where = "h.status = 'confirmed' AND h.care_date = %s
            AND o.status IN ('paid','partially_refunded')";
        $params = array($care_date);
        if ($campus_id !== 'all') {
            $where .= ' AND h.campus_id = %s';
            $params[] = $campus_id;
        }
        $sql = $this->wpdb->prepare(
            "SELECT h.request_id, h.campus_id, COUNT(*) AS unit_count
             FROM {$this->holds_table} h
             INNER JOIN {$this->orders_table} o ON o.request_id = h.request_id
             WHERE {$where}
             GROUP BY h.request_id, h.campus_id
             ORDER BY h.campus_id, h.request_id",
            $params
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    public function purge_expired()
    {
        $now = gmdate('Y-m-d H:i:s');
        $this->wpdb->query($this->wpdb->prepare(
            "DELETE h FROM {$this->holds_table} h
             INNER JOIN {$this->orders_table} o ON o.request_id = h.request_id
             WHERE h.status = 'held' AND o.status = 'held' AND h.expires_at < %s",
            $now
        ));
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->orders_table} SET status = 'expired', payload_cipher = '', updated_at = %s
             WHERE status = 'held' AND expires_at < %s",
            $now,
            $now
        ));
    }

    private function update_order($request_id, array $values)
    {
        $values['updated_at'] = gmdate('Y-m-d H:i:s');
        $formats = array();
        foreach ($values as $key => $value) {
            $formats[] = $key === 'amount_cents' ? '%d' : '%s';
        }
        $updated = $this->wpdb->update(
            $this->orders_table,
            $values,
            array('request_id' => $request_id),
            $formats,
            array('%s')
        );
        if ($updated === false) {
            throw new RuntimeException('Could not update the checkout state.');
        }
    }

    private function acquire_capacity_locks($campus_id, array $dates)
    {
        sort($dates);
        $locks = array();
        foreach (array_unique($dates) as $date) {
            $name = 'chroma_bc_' . substr(hash('sha256', $campus_id . '|' . $date), 0, 40);
            $acquired = (int) $this->wpdb->get_var($this->wpdb->prepare('SELECT GET_LOCK(%s, 5)', $name));
            if ($acquired !== 1) {
                $this->release_capacity_locks($locks);
                throw new RuntimeException('Booking capacity is busy. Please try again.');
            }
            $locks[] = $name;
        }
        return $locks;
    }

    private function release_capacity_locks(array $locks)
    {
        foreach ($locks as $name) {
            $this->wpdb->get_var($this->wpdb->prepare('SELECT RELEASE_LOCK(%s)', $name));
        }
    }
}

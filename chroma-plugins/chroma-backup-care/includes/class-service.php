<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Chroma_Backup_Care_Service
{
    private $config;
    private $domain;
    private $store;
    private $ghl;
    private $parent_access;

    public function __construct(
        Chroma_Backup_Care_Config $config,
        Chroma_Backup_Care_Store $store,
        Chroma_Backup_Care_GHL_Client $ghl,
        $parent_access = null
    ) {
        $this->config = $config;
        $this->domain = new Chroma_Backup_Care_Domain($config->manifest());
        $this->store = $store;
        $this->ghl = $ghl;
        $this->parent_access = $parent_access ?: new Chroma_Backup_Care_Parent_Access($config);
    }

    public function quote(array $order, DateTimeImmutable $now = null)
    {
        $now = $now ?: new DateTimeImmutable('now', new DateTimeZone('America/New_York'));
        $evaluation = $this->evaluate($order, $now);
        $result = $evaluation['result'];
        if ($result['contract_valid']) {
            $result['quote_token'] = $this->create_quote_token($order, $result['quote']);
            $result['quote_expires_at'] = gmdate('c', time() + (10 * MINUTE_IN_SECONDS));
        } elseif (!empty($evaluation['enrollment_required'])) {
            $form_ids = $this->config->manifest()['ghl']['form_ids'];
            $result['enrollment_required'] = $evaluation['enrollment_required'];
            $result['family_profile_form_url'] = 'https://api.leadconnectorhq.com/widget/form/'
                . rawurlencode($form_ids['family_profile']);
            $result['child_enrollment_form_url'] = 'https://api.leadconnectorhq.com/widget/form/'
                . rawurlencode($form_ids['child_enrollment']);
        }
        return $result;
    }

    public function parent_profiles($email, $parent_access_token)
    {
        $email = strtolower(trim(sanitize_email((string) $email)));
        $this->parent_access->assert_token((string) $parent_access_token, $email);
        $contact = $this->ghl->find_contact_by_email($email);
        if (!$contact || empty($contact['id'])) {
            return array('profiles' => array());
        }
        return array('profiles' => $this->ghl->list_related_child_profiles($contact['id']));
    }

    public function checkout(array $order, $quote_token)
    {
        $readiness = $this->config->readiness();
        if (!$readiness['ready']) {
            throw new RuntimeException('Backup-care checkout is not enabled.');
        }

        $this->store->purge_expired();
        $existing = !empty($order['client_request_id'])
            ? $this->store->find_order($order['client_request_id'])
            : null;
        if ($existing && !hash_equals((string) $existing['payload_hash'], $this->payload_hash($order))) {
            throw new DomainException('client_request_id was already used for a different order.');
        }
        if ($existing && $existing['status'] === 'pending_payment' && !empty($existing['ghl_invoice_id'])) {
            return array(
                'request_id' => $existing['request_id'],
                'invoice_id' => $existing['ghl_invoice_id'],
                'payment_delivery' => 'ghl_invoice_email',
                'status' => $existing['status'],
                'idempotent_replay' => true,
            );
        }

        $evaluation = $this->evaluate(
            $order,
            new DateTimeImmutable('now', new DateTimeZone('America/New_York'))
        );
        $result = $evaluation['result'];
        if (!$result['contract_valid']) {
            throw new DomainException(implode('; ', $result['errors']));
        }
        $this->verify_quote_token($quote_token, $order, $result['quote']);

        $authoritative_order = $evaluation['order'];
        $policy_accepted_at = gmdate('c');

        $payload_hash = $this->payload_hash($order);
        $payload_cipher = $this->encrypt(wp_json_encode($authoritative_order));
        $expires_at = gmdate('Y-m-d H:i:s', time() + (2 * HOUR_IN_SECONDS));
        $capacity = (int) $this->config->manifest()['business_rules']['capacity']['max_booking_units_per_campus_per_care_date'];
        $child_records = array();
        foreach ($authoritative_order['children'] as $child) {
            $child_records[$child['client_child_id']] = $child['enrollment_record_id'];
        }
        $reservation_quote = $result['quote'];
        $reservation_quote['policy_acceptance_audit'] = array(
            'accepted_at' => $policy_accepted_at,
            'contract_version' => (int) $order['contract_version'],
            'accepted_fields' => Chroma_Backup_Care_Domain::REQUIRED_POLICIES,
        );
        foreach ($reservation_quote['line_items'] as $index => $line) {
            $reservation_quote['line_items'][$index]['child_record_id'] = $child_records[$line['client_child_id']];
        }
        $this->store->reserve(
            $order['client_request_id'],
            $payload_hash,
            $payload_cipher,
            $reservation_quote,
            $order['campus_id'],
            $capacity,
            $expires_at
        );

        $invoice_id = '';
        try {
            $campus = $this->config->campus($order['campus_id']);
            $contact_id = (string) $authoritative_order['authorized_parent_contact_id'];
            $invoice_quote = $result['quote'];
            $child_names = array();
            foreach ($authoritative_order['children'] as $child) {
                $child_names[$child['client_child_id']] = trim($child['first_name'] . ' ' . $child['last_name']);
            }
            foreach ($invoice_quote['line_items'] as $index => $line) {
                $invoice_quote['line_items'][$index]['child_name'] = isset($child_names[$line['client_child_id']])
                    ? $child_names[$line['client_child_id']]
                    : 'Child';
            }
            $invoice = $this->ghl->find_backup_care_invoice($order['client_request_id'], $contact_id);
            $recovered_invoice = (bool) $invoice;
            if (!$invoice) {
                $invoice = $this->ghl->create_backup_care_invoice(
                    $order['client_request_id'],
                    $authoritative_order['parent'],
                    $contact_id,
                    $campus,
                    $invoice_quote
                );
            }
            if ($recovered_invoice) {
                $recovered_total_cents = isset($invoice['total'])
                    ? (int) round(((float) $invoice['total']) * 100)
                    : 0;
                $recovered_currency = isset($invoice['currency'])
                    ? strtolower((string) $invoice['currency'])
                    : '';
                $recovered_live_mode = isset($invoice['liveMode']) ? (bool) $invoice['liveMode'] : null;
                if ($recovered_total_cents !== (int) $result['quote']['total_amount_cents']
                    || $recovered_currency !== 'usd'
                    || $recovered_live_mode !== ($this->config->mode() === 'live')) {
                    throw new DomainException('A recovered GHL invoice does not match the server quote.');
                }
            }
            $invoice_id = (string) $invoice['_id'];
            $sender = $this->config->calendar_projection($order['campus_id']);
            $sent_invoice = $invoice;
            if (empty($invoice['status']) || $invoice['status'] === 'draft') {
                $sent = $this->ghl->send_backup_care_invoice($invoice_id, $sender['assigned_user_id']);
                $sent_invoice = isset($sent['invoice']) && is_array($sent['invoice']) ? $sent['invoice'] : $invoice;
            }
            $invoice_status = isset($sent_invoice['status']) ? (string) $sent_invoice['status'] : 'sent';
            $this->store->attach_invoice($order['client_request_id'], $invoice_id, $invoice_status);
        } catch (Throwable $error) {
            if ($invoice_id !== '') {
                try {
                    $this->ghl->void_invoice($invoice_id);
                } catch (Throwable $void_error) {
                    error_log('Chroma Backup Care could not void a failed GHL invoice: ' . get_class($void_error));
                }
            }
            $this->store->mark_failed($order['client_request_id'], 'checkout_failed');
            throw $error;
        }

        return array(
            'request_id' => $order['client_request_id'],
            'invoice_id' => $invoice_id,
            'payment_delivery' => 'ghl_invoice_email',
            'status' => 'pending_payment',
            'idempotent_replay' => false,
        );
    }

    public function reconcile_pending_invoices($limit = 50)
    {
        $results = array();
        foreach ($this->store->pending_payment_orders($limit) as $stored) {
            try {
                $results[$stored['request_id']] = $this->sync_invoice_payment($stored['request_id']);
            } catch (Throwable $error) {
                $results[$stored['request_id']] = array('status' => 'error', 'code' => $this->error_code($error));
            }
        }
        return $results;
    }

    public function sync_invoice_payment($request_id, $authorized_email = '')
    {
        return $this->store->with_order_lock($request_id, function () use ($request_id, $authorized_email) {
            $stored = $this->store->find_order($request_id);
            if (!$stored || empty($stored['ghl_invoice_id'])) {
                throw new DomainException('The GHL invoice could not be found for this order.');
            }
            if (in_array($stored['status'], array('paid', 'partially_refunded', 'refunded'), true)
                && empty($stored['payload_cipher'])) {
                return array('request_id' => $request_id, 'status' => $stored['status']);
            }
            $invoice = $this->ghl->get_invoice($stored['ghl_invoice_id']);
            if ($authorized_email !== '') {
                $invoice_email = isset($invoice['contactDetails']['email'])
                    ? strtolower(trim((string) $invoice['contactDetails']['email']))
                    : '';
                if ($invoice_email === '' || !hash_equals(strtolower(trim($authorized_email)), $invoice_email)) {
                    throw new DomainException('The invoice does not belong to the verified parent.');
                }
            }
            $this->store->mark_invoice_status($request_id, isset($invoice['status']) ? $invoice['status'] : 'unknown');
            if (!isset($invoice['status']) || $invoice['status'] !== 'paid') {
                $expired = !empty($stored['expires_at'])
                    && strtotime($stored['expires_at'] . ' UTC') < time();
                if ($expired) {
                    if ($invoice['status'] !== 'void') {
                        $this->ghl->void_invoice($stored['ghl_invoice_id']);
                    }
                    $this->store->mark_failed($request_id, 'expired');
                    do_action('chroma_backup_care_payment_failed', array(
                        'request_id' => $request_id,
                        'contact_id' => isset($stored['contact_id']) ? $stored['contact_id'] : '',
                        'reason' => 'ghl_invoice_expired',
                    ));
                    return array('request_id' => $request_id, 'status' => 'expired');
                }
                return array('request_id' => $request_id, 'status' => 'pending_payment');
            }
            $this->fulfill_paid_invoice_locked($invoice, $request_id);
            return array('request_id' => $request_id, 'status' => 'paid');
        });
    }

    public function manage_order($manage_token)
    {
        $claims = $this->verify_manage_token($manage_token);
        $stored = $this->store->find_order($claims['request_id']);
        if (!$stored || !hash_equals((string) $stored['contact_id'], (string) $claims['contact_id'])) {
            throw new DomainException('The order management link is invalid.');
        }
        $stored_units = $this->store->order_units($stored['request_id']);
        $child_labels = array();
        foreach ($stored_units as $unit) {
            $child_record_id = isset($unit['child_record_id']) ? (string) $unit['child_record_id'] : '';
            if ($child_record_id === '' || array_key_exists($child_record_id, $child_labels)) {
                continue;
            }
            $child_labels[$child_record_id] = '';
            try {
                $record = $this->ghl->get_record($this->config->object_schema('child'), $child_record_id);
                $properties = isset($record['properties']) && is_array($record['properties'])
                    ? $record['properties']
                    : array();
                $child_labels[$child_record_id] = trim(
                    (isset($properties['first_name']) ? $properties['first_name'] : '') . ' '
                    . (isset($properties['last_name']) ? $properties['last_name'] : '')
                );
            } catch (Throwable $error) {
                // Keep the management page available if a display label cannot be read.
            }
        }
        $units = array_map(function ($unit) use ($child_labels) {
            $child_record_id = isset($unit['child_record_id']) ? (string) $unit['child_record_id'] : '';
            return array(
                'line_item_key' => $unit['line_item_key'],
                'client_child_id' => $unit['client_child_id'],
                'child_name' => !empty($child_labels[$child_record_id])
                    ? $child_labels[$child_record_id]
                    : 'Child booking',
                'care_date' => $unit['care_date'],
                'planned_dropoff_local' => $unit['planned_dropoff_local'],
                'status' => $unit['status'],
            );
        }, $stored_units);
        return array(
            'request_id' => $stored['request_id'],
            'campus_id' => $stored['campus_id'],
            'status' => $stored['status'],
            'amount_cents' => (int) $stored['amount_cents'],
            'units' => $units,
        );
    }

    public function cancel_units($manage_token, array $line_item_keys, DateTimeImmutable $now = null)
    {
        $now = $now ?: new DateTimeImmutable('now', new DateTimeZone('America/New_York'));
        $claims = $this->verify_manage_token($manage_token);
        return $this->store->with_order_lock($claims['request_id'], function () use ($claims, $line_item_keys, $now) {
            return $this->cancel_units_locked($claims, $line_item_keys, $now);
        });
    }

    private function cancel_units_locked(array $claims, array $line_item_keys, DateTimeImmutable $now)
    {
        $stored = $this->store->find_order($claims['request_id']);
        if (!$stored || !hash_equals((string) $stored['contact_id'], (string) $claims['contact_id'])) {
            throw new DomainException('The order management link is invalid.');
        }
        if (!in_array($stored['status'], array('paid', 'partially_refunded'), true)) {
            throw new DomainException('This order is not eligible for cancellation.');
        }
        $requested = array_values(array_unique(array_filter(array_map('strval', $line_item_keys))));
        if (!$requested) {
            throw new DomainException('Select at least one child-date unit to cancel.');
        }
        $units = array();
        foreach ($this->store->order_units($stored['request_id']) as $unit) {
            $units[$unit['line_item_key']] = $unit;
        }
        $selected = array();
        foreach ($requested as $key) {
            if (!isset($units[$key]) || $units[$key]['status'] !== 'confirmed') {
                throw new DomainException('A selected child-date unit is not eligible for cancellation.');
            }
            $care_at = new DateTimeImmutable(
                $units[$key]['care_date'] . ' ' . ($units[$key]['planned_dropoff_local'] ?: '06:30'),
                new DateTimeZone('America/New_York')
            );
            $cutoff_hours = $this->policy_cutoff_hours('refundable_until_hours_before_care');
            if ($care_at->getTimestamp() - $now->getTimestamp() < $cutoff_hours * HOUR_IN_SECONDS) {
                do_action('chroma_backup_care_late_cancellation', array(
                    'request_id' => $stored['request_id'],
                    'contact_id' => $stored['contact_id'],
                    'line_item_key' => $key,
                ));
                throw new DomainException(sprintf(
                    'Cancellation is not refundable within %d hours of care.',
                    $cutoff_hours
                ));
            }
            $selected[] = $units[$key];
        }

        sort($requested);
        $unit_amount = (int) $this->config->manifest()['business_rules']['price']['amount_cents'];
        $refund_key = 'cancel_' . substr(hash('sha256', $stored['request_id'] . '|' . implode('|', $requested)), 0, 24);
        foreach ($selected as $unit) {
            $this->ghl->delete_calendar_event(
                isset($unit['ghl_calendar_event_id']) ? $unit['ghl_calendar_event_id'] : ''
            );
            $this->ghl->upsert_record(
                $this->config->object_schema('attendance'),
                'attendance_id',
                $unit['line_item_key'],
                array(
                    'attendance_id' => $unit['line_item_key'],
                    'status' => 'cancelled',
                    'cancelled_at' => gmdate('c'),
                    'refund_amount_cents' => $unit_amount,
                )
            );
        }
        $this->store->mark_units($requested, 'refund_pending');
        $remaining = $this->store->confirmed_unit_count($stored['request_id']);
        $order_status = $remaining ? 'partial_refund_pending' : 'refund_pending';
        $ghl_order_status = $remaining ? 'partially_cancelled' : 'cancelled';
        $refund_status = 'pending_ghl_payments_action';
        $this->ghl->update_record(
            $this->config->object_schema('order'),
            $stored['ghl_order_record_id'],
            array('status' => $ghl_order_status, 'refund_status' => 'pending')
        );
        $this->store->set_order_state($stored['request_id'], $order_status);
        do_action('chroma_backup_care_eligible_cancellation', array(
            'request_id' => $stored['request_id'],
            'contact_id' => $stored['contact_id'],
            'event_key' => $refund_key,
            'unit_count' => count($selected),
            'refund_amount_cents' => $unit_amount * count($selected),
            'refund_status' => $refund_status,
            'ghl_invoice_id' => $stored['ghl_invoice_id'],
            'refund_owner_email' => $this->config->manifest()['business_rules']['cancellation']['refund_owner_email'],
        ));
        return array(
            'status' => $order_status,
            'cancelled_unit_count' => count($selected),
            'refund_amount_cents' => $unit_amount * count($selected),
            'refund_status' => $refund_status,
        );
    }

    public function reschedule_unit($manage_token, $line_item_key, $new_date, $new_dropoff, DateTimeImmutable $now = null)
    {
        $now = $now ?: new DateTimeImmutable('now', new DateTimeZone('America/New_York'));
        $claims = $this->verify_manage_token($manage_token);
        return $this->store->with_order_lock($claims['request_id'], function () use (
            $claims,
            $line_item_key,
            $new_date,
            $new_dropoff,
            $now
        ) {
            return $this->reschedule_unit_locked($claims, $line_item_key, $new_date, $new_dropoff, $now);
        });
    }

    private function reschedule_unit_locked(
        array $claims,
        $line_item_key,
        $new_date,
        $new_dropoff,
        DateTimeImmutable $now
    ) {
        $stored = $this->store->find_order($claims['request_id']);
        if (!$stored || !hash_equals((string) $stored['contact_id'], (string) $claims['contact_id'])) {
            throw new DomainException('The order management link is invalid.');
        }
        if (!in_array($stored['status'], array('paid', 'partially_refunded'), true)) {
            throw new DomainException('This order is not eligible for rescheduling.');
        }
        $unit = null;
        foreach ($this->store->order_units($stored['request_id']) as $candidate) {
            if ($candidate['line_item_key'] === $line_item_key) {
                $unit = $candidate;
                break;
            }
        }
        if (!$unit || $unit['status'] !== 'confirmed') {
            throw new DomainException('The child-date unit is not eligible to reschedule.');
        }
        $care_at = new DateTimeImmutable(
            $unit['care_date'] . ' ' . ($unit['planned_dropoff_local'] ?: '06:30'),
            new DateTimeZone('America/New_York')
        );
        $cutoff_hours = $this->policy_cutoff_hours('reschedulable_until_hours_before_care');
        if ($care_at->getTimestamp() - $now->getTimestamp() < $cutoff_hours * HOUR_IN_SECONDS) {
            do_action('chroma_backup_care_late_reschedule', array(
                'request_id' => $stored['request_id'],
                'contact_id' => $stored['contact_id'],
                'line_item_key' => $line_item_key,
            ));
            throw new DomainException(sprintf(
                'Rescheduling is not allowed within %d hours of care.',
                $cutoff_hours
            ));
        }

        $closures = $this->ghl->closures(array($new_date), $stored['campus_id']);
        $occupancy = $this->store->active_hold_counts($stored['campus_id'], array($new_date));
        $capacity_key = $stored['campus_id'] . '|' . $new_date;
        $errors = $this->domain->validate_service_date(
            $stored['campus_id'],
            (string) $new_date,
            (string) $new_dropoff,
            $now,
            $closures,
            isset($occupancy[$capacity_key]) ? $occupancy[$capacity_key] : 0
        );
        if ($errors) {
            throw new DomainException(implode('; ', $errors));
        }

        $new_line_item_key = 'bcr_' . substr(hash('sha256', implode('|', array(
            $stored['request_id'], $line_item_key, $new_date, $new_dropoff,
        ))), 0, 24);
        $capacity = (int) $this->config->manifest()['business_rules']['capacity']['max_booking_units_per_campus_per_care_date'];
        $hold_expires_at = gmdate('Y-m-d H:i:s', time() + 15 * MINUTE_IN_SECONDS);
        $this->store->reserve_reschedule(
            $line_item_key,
            $new_line_item_key,
            $new_date,
            $new_dropoff,
            $capacity,
            $hold_expires_at
        );
        $child_record = $this->ghl->get_record(
            $this->config->object_schema('child'),
            $unit['child_record_id']
        );
        $child_properties = isset($child_record['properties']) && is_array($child_record['properties'])
            ? $child_record['properties']
            : array();
        $replacement_line = array(
            'line_item_key' => $new_line_item_key,
            'care_date' => $new_date,
            'planned_dropoff_local' => $new_dropoff,
        );
        $event_id = isset($unit['ghl_calendar_event_id'])
            ? (string) $unit['ghl_calendar_event_id']
            : '';
        $calendar_updated = false;
        try {
            $calendar_event = $this->ghl->update_backup_care_appointment(
                $event_id,
                $stored['contact_id'],
                $stored['campus_id'],
                $replacement_line,
                $child_properties
            );
            $replacement_event_id = isset($calendar_event['id'])
                ? (string) $calendar_event['id']
                : $event_id;
            if ($replacement_event_id === '') {
                throw new RuntimeException('The rescheduled GHL calendar appointment has no ID.');
            }
            $calendar_updated = true;
            $this->store->set_unit_calendar_event($new_line_item_key, $replacement_event_id);
            $replacement = $this->ghl->upsert_record(
                $this->config->object_schema('attendance'),
                'attendance_id',
                $new_line_item_key,
                array(
                    'attendance_id' => $new_line_item_key,
                    'campus_id' => $stored['campus_id'],
                    'care_date' => $new_date,
                    'planned_dropoff_local' => $new_dropoff,
                    'status' => 'reserved',
                    'unit_amount_cents' => (int) $this->config->manifest()['business_rules']['price']['amount_cents'],
                    'rescheduled_from_attendance_id' => $line_item_key,
                    'refund_amount_cents' => 0,
                    'ghl_calendar_event_id' => $replacement_event_id,
                )
            );
            $this->ghl->create_relation(
                $this->config->association_id('order_to_attendance'),
                $replacement['id'],
                $stored['ghl_order_record_id']
            );
            $this->ghl->create_relation(
                $this->config->association_id('child_to_attendance'),
                $replacement['id'],
                $unit['child_record_id']
            );
            $this->ghl->upsert_record(
                $this->config->object_schema('attendance'),
                'attendance_id',
                $line_item_key,
                array(
                    'attendance_id' => $line_item_key,
                    'status' => 'rescheduled',
                    'ghl_calendar_event_id' => '',
                )
            );
            $this->store->finalize_reschedule($line_item_key, $new_line_item_key);
            $this->store->set_unit_calendar_event($line_item_key, '');
        } catch (Throwable $error) {
            if ($calendar_updated) {
                try {
                    if ($event_id !== '') {
                        $this->ghl->update_backup_care_appointment(
                            $event_id,
                            $stored['contact_id'],
                            $stored['campus_id'],
                            array(
                                'line_item_key' => $line_item_key,
                                'care_date' => $unit['care_date'],
                                'planned_dropoff_local' => $unit['planned_dropoff_local'],
                            ),
                            $child_properties
                        );
                    } else {
                        $this->ghl->delete_calendar_event($replacement_event_id);
                    }
                } catch (Throwable $calendar_error) {
                    error_log('Chroma Backup Care calendar rollback failure: ' . get_class($calendar_error));
                }
            }
            try {
                $this->store->release_reschedule($new_line_item_key);
            } catch (Throwable $release_error) {
                error_log('Chroma Backup Care reschedule release failure: ' . get_class($release_error));
            }
            try {
                $this->ghl->upsert_record(
                    $this->config->object_schema('attendance'),
                    'attendance_id',
                    $new_line_item_key,
                    array(
                        'attendance_id' => $new_line_item_key,
                        'status' => 'cancelled',
                        'cancelled_at' => gmdate('c'),
                    )
                );
                $this->ghl->upsert_record(
                    $this->config->object_schema('attendance'),
                    'attendance_id',
                    $line_item_key,
                    array('attendance_id' => $line_item_key, 'status' => 'reserved')
                );
            } catch (Throwable $sync_error) {
                error_log('Chroma Backup Care reschedule audit failure: ' . get_class($sync_error));
            }
            throw $error;
        }
        do_action('chroma_backup_care_eligible_reschedule', array(
            'request_id' => $stored['request_id'],
            'contact_id' => $stored['contact_id'],
            'old_line_item_key' => $line_item_key,
            'new_line_item_key' => $new_line_item_key,
            'new_date' => $new_date,
        ));
        return array(
            'status' => 'rescheduled',
            'old_line_item_key' => $line_item_key,
            'new_line_item_key' => $new_line_item_key,
            'new_date' => $new_date,
            'new_dropoff' => $new_dropoff,
        );
    }

    private function fulfill_paid_invoice_locked(array $invoice, $request_id)
    {
        if (empty($invoice['status']) || $invoice['status'] !== 'paid') {
            throw new DomainException('The GHL invoice is not paid.');
        }
        $stored = $this->store->find_order($request_id);
        if (!$stored) {
            throw new RuntimeException('The paid order could not be found.');
        }
        $invoice_id = isset($invoice['_id']) ? (string) $invoice['_id'] : '';
        if ($invoice_id === '' || empty($stored['ghl_invoice_id'])
            || !hash_equals((string) $stored['ghl_invoice_id'], $invoice_id)) {
            throw new DomainException('The GHL invoice does not match the reserved order.');
        }
        $expected_live_mode = $this->config->mode() === 'live';
        if (!array_key_exists('liveMode', $invoice) || (bool) $invoice['liveMode'] !== $expected_live_mode) {
            throw new DomainException('The GHL invoice payment mode does not match the coordinator mode.');
        }
        $total_cents = (int) round(((float) $invoice['total']) * 100);
        $paid_cents = (int) round(((float) $invoice['amountPaid']) * 100);
        if ($total_cents !== (int) $stored['amount_cents'] || $paid_cents < $total_cents) {
            throw new DomainException('The GHL invoice amount does not match the server quote.');
        }
        if (empty($invoice['currency']) || strtolower((string) $invoice['currency']) !== 'usd') {
            throw new DomainException('The GHL invoice currency does not match the server quote.');
        }
        $already_paid = in_array($stored['status'], array('paid', 'partially_refunded', 'refunded'), true);
        if ($already_paid && empty($stored['payload_cipher'])) {
            return;
        }
        $order = json_decode($this->decrypt($stored['payload_cipher']), true);
        $quote = json_decode($stored['quote_json'], true);
        if (!is_array($order) || !is_array($quote)) {
            throw new RuntimeException('The encrypted order payload is unavailable.');
        }
        if ($already_paid) {
            $this->dispatch_paid_actions(
                $stored,
                $order,
                $quote,
                $stored['contact_id'],
                $stored['ghl_order_record_id']
            );
            $this->store->clear_order_payload($request_id);
            return;
        }
        $contact_id = isset($order['authorized_parent_contact_id'])
            ? (string) $order['authorized_parent_contact_id']
            : '';
        if ($contact_id === '') {
            throw new DomainException('The paid order is missing verified parent authorization.');
        }
        $contact = $this->ghl->get_contact($contact_id);
        $contact_email = isset($contact['emailLowerCase'])
            ? $contact['emailLowerCase']
            : (isset($contact['email']) ? $contact['email'] : '');
        if (!hash_equals(
            strtolower(trim((string) $order['parent']['email'])),
            strtolower(trim((string) $contact_email))
        )) {
            throw new DomainException('The verified parent contact no longer matches the paid order.');
        }
        $invoice_contact_id = isset($invoice['contactDetails']['id'])
            ? (string) $invoice['contactDetails']['id']
            : '';
        if ($invoice_contact_id === '' || !hash_equals($contact_id, $invoice_contact_id)) {
            throw new DomainException('The GHL invoice contact does not match the verified parent.');
        }
        $care_dates = array_column($quote['line_items'], 'care_date');
        sort($care_dates);
        $ghl_order_id = 'bco_' . substr(hash('sha256', $request_id), 0, 24);
        $order_record = $this->ghl->upsert_record(
            $this->config->object_schema('order'),
            'order_id',
            $ghl_order_id,
            array(
                'order_id' => $ghl_order_id,
                'client_request_id' => $request_id,
                'campus_id' => $order['campus_id'],
                'status' => 'paid',
                'unit_amount_cents' => (int) $quote['unit_amount_cents'],
                'unit_count' => (int) $quote['unit_count'],
                'total_amount_cents' => (int) $quote['total_amount_cents'],
                'earliest_care_date' => reset($care_dates),
                'latest_care_date' => end($care_dates),
                'ghl_service_booking_id' => '',
                'ghl_order_id' => $invoice_id,
                'stripe_payment_intent_id' => '',
                'terms_accepted_at' => isset($quote['policy_acceptance_audit']['accepted_at'])
                    ? $quote['policy_acceptance_audit']['accepted_at']
                    : gmdate('c'),
                'refund_status' => 'none',
            )
        );

        $this->ghl->create_relation(
            $this->config->association_id('parent_to_order'),
            $contact_id,
            $order_record['id']
        );

        $children = array();
        foreach ($order['children'] as $child) {
            $children[$child['client_child_id']] = $child;
        }
        foreach ($quote['line_items'] as $line) {
            if (empty($children[$line['client_child_id']]['enrollment_record_id'])) {
                throw new DomainException('A child enrollment record is missing.');
            }
            $child_record_id = $children[$line['client_child_id']]['enrollment_record_id'];
            if (!$this->ghl->relation_exists(
                $child_record_id,
                $contact_id,
                $this->config->association_id('parent_to_child')
            )) {
                throw new DomainException('A child record is no longer associated with the verified parent.');
            }
            $calendar_event = $this->ghl->ensure_backup_care_appointment(
                $contact_id,
                $order['campus_id'],
                $line,
                $children[$line['client_child_id']]
            );
            $calendar_event_id = isset($calendar_event['id']) ? (string) $calendar_event['id'] : '';
            if ($calendar_event_id === '') {
                throw new RuntimeException('GHL did not return a calendar appointment ID.');
            }
            $this->store->set_unit_calendar_event($line['line_item_key'], $calendar_event_id);
            $attendance = $this->ghl->upsert_record(
                $this->config->object_schema('attendance'),
                'attendance_id',
                $line['line_item_key'],
                array(
                    'attendance_id' => $line['line_item_key'],
                    'campus_id' => $order['campus_id'],
                    'care_date' => $line['care_date'],
                    'planned_dropoff_local' => $line['planned_dropoff_local'] ?: '',
                    'status' => 'reserved',
                    'unit_amount_cents' => (int) $quote['unit_amount_cents'],
                    'refund_amount_cents' => 0,
                    'ghl_calendar_event_id' => $calendar_event_id,
                )
            );
            $this->ghl->create_relation(
                $this->config->association_id('order_to_attendance'),
                $attendance['id'],
                $order_record['id']
            );
            $this->ghl->create_relation(
                $this->config->association_id('child_to_attendance'),
                $attendance['id'],
                $child_record_id
            );
        }

        $this->store->mark_paid($request_id, $invoice_id, $contact_id, $order_record['id'], false);
        $this->dispatch_paid_actions($stored, $order, $quote, $contact_id, $order_record['id']);
        $this->store->clear_order_payload($request_id);
    }

    private function dispatch_paid_actions(array $stored, array $order, array $quote, $contact_id, $order_record_id)
    {
        $request_id = $stored['request_id'];
        $review_children = array_values(array_filter($order['children'], function ($child) {
            return !empty($child['review_required']);
        }));
        if ($review_children) {
            do_action('chroma_backup_care_mandatory_review', array(
                'request_id' => $request_id,
                'contact_id' => $contact_id,
                'campus_id' => $order['campus_id'],
                'children' => $review_children,
            ));
        }
        do_action('chroma_backup_care_order_paid', array(
            'request_id' => $request_id,
            'contact_id' => $contact_id,
            'order_record_id' => $order_record_id,
            'campus_id' => $order['campus_id'],
            'manage_token' => $this->create_manage_token($request_id, $contact_id),
            'parent' => $order['parent'],
            'children' => $order['children'],
            'line_items' => $quote['line_items'],
            'total_amount_cents' => (int) $quote['total_amount_cents'],
        ));
    }

    private function create_quote_token(array $order, array $quote)
    {
        $claims = array(
            'v' => 1,
            'exp' => time() + (10 * MINUTE_IN_SECONDS),
            'request_id' => $order['client_request_id'],
            'payload_hash' => $this->payload_hash($order),
            'total_amount_cents' => (int) $quote['total_amount_cents'],
        );
        $encoded = $this->base64url(wp_json_encode($claims));
        $signature = hash_hmac('sha256', $encoded, $this->config->quote_signing_key());
        return $encoded . '.' . $signature;
    }

    private function create_manage_token($request_id, $contact_id)
    {
        $claims = array(
            'v' => 1,
            'exp' => time() + (370 * DAY_IN_SECONDS),
            'request_id' => $request_id,
            'contact_id' => $contact_id,
        );
        $encoded = $this->base64url(wp_json_encode($claims));
        return $encoded . '.' . hash_hmac('sha256', 'manage|' . $encoded, $this->config->quote_signing_key());
    }

    private function verify_manage_token($token)
    {
        $parts = explode('.', (string) $token, 2);
        if (count($parts) !== 2) {
            throw new DomainException('The order management link is invalid.');
        }
        $expected = hash_hmac('sha256', 'manage|' . $parts[0], $this->config->quote_signing_key());
        if (!hash_equals($expected, $parts[1])) {
            throw new DomainException('The order management link is invalid.');
        }
        $claims = json_decode($this->base64url_decode($parts[0]), true);
        if (!is_array($claims) || empty($claims['exp']) || $claims['exp'] < time()
            || empty($claims['request_id']) || empty($claims['contact_id'])) {
            throw new DomainException('The order management link expired or is invalid.');
        }
        return $claims;
    }

    private function verify_quote_token($token, array $order, array $quote)
    {
        $parts = explode('.', (string) $token, 2);
        if (count($parts) !== 2) {
            throw new DomainException('The quote token is invalid.');
        }
        $expected = hash_hmac('sha256', $parts[0], $this->config->quote_signing_key());
        if (!hash_equals($expected, $parts[1])) {
            throw new DomainException('The quote token signature is invalid.');
        }
        $claims = json_decode($this->base64url_decode($parts[0]), true);
        if (!is_array($claims) || empty($claims['exp']) || $claims['exp'] < time()) {
            throw new DomainException('The quote has expired.');
        }
        if (!hash_equals((string) $claims['payload_hash'], $this->payload_hash($order))
            || (int) $claims['total_amount_cents'] !== (int) $quote['total_amount_cents']) {
            throw new DomainException('The order changed after it was quoted.');
        }
    }

    private function payload_hash(array $order)
    {
        $canonical = $this->canonicalize($order);
        return hash('sha256', wp_json_encode($canonical));
    }

    private function canonicalize($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        if (array_keys($value) !== range(0, count($value) - 1)) {
            ksort($value);
        }
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }
        return $value;
    }

    private function encrypt($plaintext)
    {
        $key = hash('sha256', $this->config->quote_signing_key(), true);
        if (function_exists('sodium_crypto_secretbox')) {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            return 's1.' . $this->base64url($nonce . sodium_crypto_secretbox($plaintext, $nonce, $key));
        }
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new RuntimeException('The order payload could not be encrypted.');
        }
        return 'o1.' . $this->base64url($iv . $tag . $ciphertext);
    }

    private function decrypt($cipher)
    {
        $parts = explode('.', (string) $cipher, 2);
        if (count($parts) !== 2) {
            throw new RuntimeException('The encrypted order payload is invalid.');
        }
        $decoded = $this->base64url_decode($parts[1]);
        $key = hash('sha256', $this->config->quote_signing_key(), true);
        if ($parts[0] === 's1' && function_exists('sodium_crypto_secretbox_open')) {
            $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $plaintext = sodium_crypto_secretbox_open(substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $key);
        } elseif ($parts[0] === 'o1') {
            $iv = substr($decoded, 0, 12);
            $tag = substr($decoded, 12, 16);
            $plaintext = openssl_decrypt(substr($decoded, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        } else {
            $plaintext = false;
        }
        if ($plaintext === false) {
            throw new RuntimeException('The encrypted order payload could not be opened.');
        }
        return $plaintext;
    }

    private function base64url($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64url_decode($value)
    {
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($value, '-_', '+/'));
    }

    private function order_dates(array $order)
    {
        $dates = array();
        foreach (isset($order['attendance']) && is_array($order['attendance']) ? $order['attendance'] : array() as $unit) {
            if (is_array($unit) && !empty($unit['care_date'])) {
                $dates[] = (string) $unit['care_date'];
            }
        }
        return array_values(array_unique($dates));
    }

    private function policy_cutoff_hours($key, $fallback = 72)
    {
        $manifest = $this->config->manifest();
        $cancellation = isset($manifest['business_rules']['cancellation'])
            && is_array($manifest['business_rules']['cancellation'])
            ? $manifest['business_rules']['cancellation']
            : array();
        return isset($cancellation[$key]) ? max(0, (int) $cancellation[$key]) : (int) $fallback;
    }

    private function evaluate(array $order, DateTimeImmutable $now)
    {
        $preflight_errors = $this->domain->preflight($order);
        if ($preflight_errors) {
            return array(
                'order' => $order,
                'result' => $this->rejected_result($order, $preflight_errors),
                'enrollment_required' => array(),
            );
        }

        $authoritative_order = $order;
        $enrollment_required = array();
        $email = isset($order['parent']['email']) ? strtolower(trim($order['parent']['email'])) : '';
        $this->parent_access->assert_token(
            isset($order['parent_access_token']) ? $order['parent_access_token'] : '',
            $email
        );
        unset($authoritative_order['parent_access_token']);
        $contact = $this->ghl->find_contact_by_email($email);
        $contact_id = $contact && !empty($contact['id']) ? (string) $contact['id'] : '';
        foreach (isset($authoritative_order['children']) && is_array($authoritative_order['children'])
            ? $authoritative_order['children']
            : array() as $index => $child) {
            if (!is_array($child)) {
                continue;
            }
            $key_source = implode('|', array(
                $email,
                strtolower(trim(isset($child['first_name']) ? $child['first_name'] : '')),
                strtolower(trim(isset($child['last_name']) ? $child['last_name'] : '')),
                isset($child['date_of_birth']) ? $child['date_of_birth'] : '',
            ));
            $child_record_key = 'bcc_' . substr(
                hash_hmac('sha256', $key_source, $this->config->quote_signing_key()),
                0,
                28
            );
            $record = $contact_id !== '' ? $this->ghl->resolve_child_record($child_record_key) : null;
            $authorized = $record && $record['complete'] && $this->ghl->relation_exists(
                $record['id'],
                $contact_id,
                $this->config->association_id('parent_to_child')
            );
            $authoritative_order['children'][$index]['child_record_key'] = $child_record_key;
            $authoritative_order['children'][$index]['enrollment_record_id'] = $authorized ? $record['id'] : '';
            $authoritative_order['children'][$index]['enrollment_record_complete'] = (bool) $authorized;
            $authoritative_order['children'][$index]['review_required'] = $authorized
                && !empty($record['review_required']);
            $authoritative_order['children'][$index]['review_reasons'] = $authorized
                && !empty($record['review_reasons']) ? $record['review_reasons'] : array();
            if (!$authorized) {
                $enrollment_required[] = array(
                    'client_child_id' => isset($child['client_child_id']) ? $child['client_child_id'] : '',
                    'child_record_key' => $child_record_key,
                );
            }
        }

        $dates = $this->order_dates($authoritative_order);
        $campus_id = isset($authoritative_order['campus_id']) ? (string) $authoritative_order['campus_id'] : '';
        $closures = $this->ghl->closures($dates, $campus_id);
        $occupancy = $this->store->active_hold_counts($campus_id, $dates);
        $result = $this->domain->quote($authoritative_order, $now, $closures, $occupancy);
        if ($contact_id !== '') {
            $authoritative_order['authorized_parent_contact_id'] = $contact_id;
        }
        return array(
            'order' => $authoritative_order,
            'result' => $result,
            'enrollment_required' => $enrollment_required,
        );
    }

    private function rejected_result(array $order, array $errors)
    {
        $children = isset($order['children']) && is_array($order['children']) ? $order['children'] : array();
        $attendance = isset($order['attendance']) && is_array($order['attendance']) ? $order['attendance'] : array();
        $dates = array();
        foreach ($attendance as $unit) {
            if (is_array($unit) && isset($unit['care_date']) && is_string($unit['care_date'])) {
                $dates[$unit['care_date']] = true;
            }
        }
        return array(
            'contract_valid' => false,
            'payment_creation_allowed' => false,
            'campus_id' => isset($order['campus_id']) ? $order['campus_id'] : null,
            'child_count' => count($children),
            'care_date_count' => count($dates),
            'unit_count' => count($attendance),
            'quote' => null,
            'errors' => $errors,
        );
    }

    private function error_code(Throwable $error)
    {
        return substr(preg_replace('/[^a-z0-9]+/', '_', strtolower(get_class($error))), 0, 100);
    }
}

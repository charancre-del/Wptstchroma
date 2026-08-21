<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Chroma_Backup_Care_Notifications
{
    private $config;
    private $store;
    private $ghl;

    public function __construct(
        Chroma_Backup_Care_Config $config,
        Chroma_Backup_Care_Store $store,
        Chroma_Backup_Care_GHL_Client $ghl
    ) {
        $this->config = $config;
        $this->store = $store;
        $this->ghl = $ghl;
    }

    public function register()
    {
        add_action('chroma_backup_care_order_paid', array($this, 'paid'), 10, 1);
        add_action('chroma_backup_care_payment_failed', array($this, 'payment_failed'), 10, 1);
        add_action('chroma_backup_care_mandatory_review', array($this, 'mandatory_review'), 10, 1);
        add_action('chroma_backup_care_eligible_cancellation', array($this, 'eligible_cancellation'), 10, 1);
        add_action('chroma_backup_care_late_cancellation', array($this, 'late_cancellation'), 10, 1);
        add_action('chroma_backup_care_eligible_reschedule', array($this, 'eligible_reschedule'), 10, 1);
        add_action('chroma_backup_care_late_reschedule', array($this, 'late_reschedule'), 10, 1);
        add_action('chroma_backup_care_refund_recorded', array($this, 'refund_recorded'), 10, 1);
        add_action('chroma_backup_care_arrival_reminders', array($this, 'arrival_reminders'));
    }

    public function paid(array $event)
    {
        $campus = $this->campus($event['campus_id']);
        $rows = '';
        $children = array();
        foreach ($event['children'] as $child) {
            $children[$child['client_child_id']] = trim($child['first_name'] . ' ' . $child['last_name']);
        }
        foreach ($event['line_items'] as $line) {
            $rows .= '<tr><td>' . esc_html($children[$line['client_child_id']]) . '</td><td>'
                . esc_html($line['care_date']) . '</td><td>'
                . esc_html($line['planned_dropoff_local'] ?: 'By 9:30 AM') . '</td></tr>';
        }
        $manage_url = home_url('/backup-care-manage/#token=' . rawurlencode($event['manage_token']));
        $parent_html = '<p>Your backup care is confirmed at <strong>' . esc_html($campus['name']) . '</strong>.</p>'
            . '<p>' . esc_html($campus['address']) . '</p>'
            . '<table><thead><tr><th>Child</th><th>Date</th><th>Drop-off</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<p>Total paid: <strong>$' . number_format($event['total_amount_cents'] / 100, 2) . '</strong></p>'
            . '<p><a href="' . esc_url($manage_url) . '">Manage this booking</a></p>';
        $this->once('paid.parent', $event['request_id'], function () use ($event, $campus, $parent_html) {
            $this->ghl->send_email(
                $event['contact_id'],
                $event['parent']['email'],
                'Backup care confirmed - ' . $campus['name'],
                $parent_html
            );

        }, true);
        $internal_html = '<p>Paid backup care order <strong>' . esc_html($event['request_id']) . '</strong></p>'
            . '<p>Campus: ' . esc_html($campus['name']) . '</p>'
            . '<table><thead><tr><th>Child</th><th>Date</th><th>Drop-off</th></tr></thead><tbody>' . $rows . '</tbody></table>';
        foreach (array($campus['notification_email'], 'info@chromaela.com') as $recipient) {
            $this->once('paid.internal', $event['request_id'] . ':' . strtolower($recipient), function () use ($recipient, $campus, $internal_html) {
                $this->send_internal(array($recipient), 'Paid backup care booking - ' . $campus['name'], $internal_html);
            }, true);
        }
        $this->once('paid.workflow', $event['request_id'], function () use ($event) {
            $this->enroll('paid', $event['contact_id']);
        }, true);
    }

    public function payment_failed(array $event)
    {
        if (empty($event['contact_id']) || empty($event['parent']['email'])) {
            return;
        }
        $this->once('payment_failed.parent', $event['request_id'], function () use ($event) {
            $this->ghl->send_email(
                $event['contact_id'],
                $event['parent']['email'],
                'Backup care payment was not completed',
                '<p>Your backup care reservation was not confirmed because payment did not complete. No care has been reserved.</p>'
            );
        }, true);
        $this->once('payment_failed.workflow', $event['request_id'], function () use ($event) {
            $this->enroll('payment_failed', $event['contact_id']);
        }, true);
    }

    public function mandatory_review(array $event)
    {
        $campus = $this->campus($event['campus_id']);
        $children = array_map(function ($child) {
            return trim($child['first_name'] . ' ' . $child['last_name']);
        }, $event['children']);
        $html = '<p>Paid backup care order <strong>' . esc_html($event['request_id'])
            . '</strong> includes an enrollment record that requires a safety preparation review.</p>'
            . '<p>Child record(s): ' . esc_html(implode(', ', $children)) . '.</p>'
            . '<p>The booking is confirmed and does not require campus approval. Review the associated child record in GHL before care.</p>';
        foreach (array($campus['notification_email'], 'info@chromaela.com') as $recipient) {
            $this->once('mandatory_review.internal', $event['request_id'] . ':' . strtolower($recipient), function () use ($recipient, $campus, $html) {
                $this->send_internal(
                    array($recipient),
                    'Backup care safety review - ' . $campus['name'],
                    $html
                );
            }, true);
        }
        $this->once('mandatory_review.workflow', $event['request_id'], function () use ($event) {
            $this->enroll('mandatory_review', $event['contact_id']);
        }, true);
    }

    public function eligible_cancellation(array $event)
    {
        $amount = '$' . number_format($event['refund_amount_cents'] / 100, 2);
        $key = !empty($event['event_key'])
            ? $event['event_key']
            : $event['request_id'] . ':' . $event['refund_amount_cents'];
        $this->once('eligible_cancellation.billing', $key, function () use ($event, $amount) {
            $this->send_internal(
                array('billing@chromaela.com'),
                'GHL backup care refund action required',
                '<p>Order ' . esc_html($event['request_id']) . ' cancelled ' . (int) $event['unit_count']
                    . ' child-date unit(s). Refund amount: ' . esc_html($amount) . '.</p>'
                    . '<p>Process the full or partial refund from GHL Payments for invoice '
                    . esc_html(isset($event['ghl_invoice_id']) ? $event['ghl_invoice_id'] : '') . '.</p>'
            );
        });
        $this->once('eligible_cancellation.workflow', $key, function () use ($event) {
            $this->enroll('eligible_cancellation', $event['contact_id']);
        });
    }

    public function late_cancellation(array $event)
    {
        $this->once('late_cancellation.workflow', $event['request_id'] . ':' . $event['line_item_key'], function () use ($event) {
            $this->enroll('late_cancellation', $event['contact_id']);
        });
    }

    public function eligible_reschedule(array $event)
    {
        $stored = $this->store->find_order($event['request_id']);
        $campus = $this->campus($stored['campus_id']);
        foreach (array($campus['notification_email'], 'info@chromaela.com') as $recipient) {
            $this->once('eligible_reschedule.internal', $event['new_line_item_key'] . ':' . strtolower($recipient), function () use ($event, $campus, $recipient) {
            $this->send_internal(
                    array($recipient),
                'Backup care date rescheduled - ' . $campus['name'],
                '<p>Order ' . esc_html($event['request_id']) . ' moved one child-date unit to '
                    . esc_html($event['new_date']) . '.</p>'
            );
            });
        }
        $this->once('eligible_reschedule.workflow', $event['new_line_item_key'], function () use ($event) {
            $this->enroll('eligible_reschedule', $event['contact_id']);
        });
    }

    public function late_reschedule(array $event)
    {
        $this->once('late_reschedule.workflow', $event['request_id'] . ':' . $event['line_item_key'], function () use ($event) {
            $this->enroll('late_reschedule', $event['contact_id']);
        });
    }

    public function refund_recorded(array $event)
    {
        if (empty($event['billing_review_required'])) {
            return;
        }
        $actual = '$' . number_format($event['actual_refund_cents'] / 100, 2);
        $expected = '$' . number_format($event['expected_refund_cents'] / 100, 2);
        $this->once('refund_recorded.billing', $event['event_key'], function () use ($event, $actual, $expected) {
            $this->send_internal(
                array('billing@chromaela.com'),
                'Backup care GHL refund requires reconciliation',
                '<p>Order ' . esc_html($event['request_id']) . ' has a cumulative GHL payment refund of '
                    . esc_html($actual) . ', while mapped child-date cancellations total ' . esc_html($expected)
                    . '.</p><p>Self-service changes are blocked until billing reconciles the refund to child-date units.</p>'
            );
        }, true);
    }

    public function arrival_reminders()
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('America/New_York'));
        if ((int) $now->format('G') < 15) {
            return;
        }
        $care_date = $now->modify('+1 day')->format('Y-m-d');
        foreach ($this->store->arrival_reminder_groups($care_date) as $group) {
            $campus = $this->campus($group['campus_id']);
            $key = $group['request_id'] . ':' . $care_date;
            $dropoff = $group['earliest_dropoff_local'] ?: 'By 9:30 AM';
            $parent_html = '<p>This is a reminder that your backup care booking is tomorrow at <strong>'
                . esc_html($campus['name']) . '</strong>.</p><p>' . esc_html($campus['address']) . '</p>'
                . '<p>Planned drop-off: ' . esc_html($dropoff) . '. All children must arrive by 9:30 AM.</p>';
            $this->once('arrival_reminder.parent', $key, function () use ($group, $campus, $parent_html) {
                $this->ghl->send_email(
                    $group['contact_id'],
                    '',
                    'Backup care reminder - ' . $campus['name'],
                    $parent_html
                );
            });
            $internal_html = '<p>Tomorrow\'s backup care roster includes ' . (int) $group['unit_count']
                . ' child-date unit(s) for order ' . esc_html($group['request_id']) . '.</p>';
            foreach (array($campus['notification_email'], 'info@chromaela.com') as $recipient) {
                $this->once('arrival_reminder.internal', $key . ':' . strtolower($recipient), function () use ($recipient, $campus, $internal_html) {
                    $this->send_internal(
                        array($recipient),
                        'Tomorrow backup care roster - ' . $campus['name'],
                        $internal_html
                    );
                });
            }
            $this->once('arrival_reminder.workflow', $key, function () use ($group) {
                $this->enroll('arrival_reminder', $group['contact_id']);
            });
        }
    }

    private function enroll($event_name, $contact_id)
    {
        $workflow_id = $this->config->notification_workflow_id($event_name);
        if ($workflow_id) {
            $this->ghl->enroll_workflow($contact_id, $workflow_id);
        }
    }

    private function campus($campus_id)
    {
        $campuses = $this->config->campuses();
        if (empty($campuses[$campus_id])) {
            throw new RuntimeException('The campus notification route is missing.');
        }
        return $campuses[$campus_id];
    }

    private function send_internal(array $recipients, $subject, $html)
    {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        foreach (array_unique(array_filter(array_map('sanitize_email', $recipients))) as $recipient) {
            if (!wp_mail($recipient, $subject, $html, $headers)) {
                throw new RuntimeException('An internal notification could not be queued.');
            }
        }
    }

    private function once($event_name, $event_key, callable $callback, $retry_webhook = false)
    {
        $id = 'notify:' . $event_name . ':' . substr(hash('sha256', $event_key), 0, 40);
        if (!$this->store->begin_event($id, 'notification.' . $event_name)) {
            return;
        }
        try {
            $callback();
            $this->store->finish_event($id, 'complete');
        } catch (Throwable $error) {
            $this->store->finish_event($id, 'failed', strtolower(get_class($error)));
            error_log('Chroma Backup Care notification failure: ' . get_class($error));
            if ($retry_webhook) {
                throw $error;
            }
        }
    }
}

<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Chroma_Backup_Care_Config
{
    const GHL_LOCATION_ID = 'euN4JvLvKNYTYh4Xyh3p';

    private $manifest;

    public function __construct($manifest_path = null)
    {
        $path = $manifest_path ?: CHROMA_BACKUP_CARE_DIR . 'config/backup-care.json';
        $contents = is_readable($path) ? file_get_contents($path) : false;
        $decoded = is_string($contents) ? json_decode($contents, true) : null;
        if (!is_array($decoded)) {
            throw new RuntimeException('Backup-care configuration is missing or invalid.');
        }
        $this->manifest = $decoded;
    }

    public function manifest()
    {
        return $this->manifest;
    }

    public function campuses()
    {
        $campuses = isset($this->manifest['campuses']) ? $this->manifest['campuses'] : array();
        $indexed = array();
        foreach ($campuses as $campus) {
            if (!empty($campus['id'])) {
                $indexed[(string) $campus['id']] = $campus;
            }
        }
        return $indexed;
    }

    public function campus($campus_id)
    {
        $campuses = $this->campuses();
        return isset($campuses[$campus_id]) ? $campuses[$campus_id] : null;
    }

    public function calendar_projection($campus_id)
    {
        $campus = $this->campus($campus_id);
        if (!$campus) {
            throw new DomainException('The selected campus is not configured.');
        }
        $calendar_id = isset($campus['source_calendar_id'])
            ? trim((string) $campus['source_calendar_id'])
            : '';
        $assigned_user_id = isset($campus['backup_care_calendar_user_id'])
            ? trim((string) $campus['backup_care_calendar_user_id'])
            : '';
        if ($calendar_id === '' || $assigned_user_id === '') {
            throw new RuntimeException('The selected campus calendar projection is incomplete.');
        }
        return array(
            'calendar_id' => $calendar_id,
            'assigned_user_id' => $assigned_user_id,
            'address' => isset($campus['address']) ? (string) $campus['address'] : '',
            'published_open' => isset($campus['published_open'])
                ? (string) $campus['published_open']
                : '06:30',
        );
    }

    public function public_settings()
    {
        $rules = isset($this->manifest['business_rules']) && is_array($this->manifest['business_rules'])
            ? $this->manifest['business_rules']
            : array();
        $price = isset($rules['price']) && is_array($rules['price']) ? $rules['price'] : array();
        $eligibility = isset($rules['eligibility']) && is_array($rules['eligibility'])
            ? $rules['eligibility']
            : array();
        $booking = isset($rules['booking']) && is_array($rules['booking']) ? $rules['booking'] : array();
        $cancellation = isset($rules['cancellation']) && is_array($rules['cancellation'])
            ? $rules['cancellation']
            : array();
        $notifications = isset($rules['notifications']) && is_array($rules['notifications'])
            ? $rules['notifications']
            : array();
        $ghl = isset($this->manifest['ghl']) && is_array($this->manifest['ghl'])
            ? $this->manifest['ghl']
            : array();
        $form_ids = isset($ghl['form_ids']) && is_array($ghl['form_ids']) ? $ghl['form_ids'] : array();
        $campuses = array();
        foreach ($this->campuses() as $campus) {
            $address = sanitize_text_field(isset($campus['address']) ? $campus['address'] : '');
            $campuses[] = array(
                'id' => sanitize_key(isset($campus['id']) ? $campus['id'] : ''),
                'name' => sanitize_text_field(isset($campus['name']) ? $campus['name'] : ''),
                'address' => $address,
                'opens' => sanitize_text_field(isset($campus['published_open']) ? $campus['published_open'] : '06:30'),
                'closes' => sanitize_text_field(isset($campus['published_close']) ? $campus['published_close'] : '18:30'),
                'directionsUrl' => $address !== ''
                    ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address)
                    : '',
            );
        }

        $amount_cents = isset($price['amount_cents']) ? max(0, (int) $price['amount_cents']) : 11500;
        $currency = isset($price['currency']) ? sanitize_text_field($price['currency']) : 'USD';
        $same_day_deadline = isset($booking['same_day_booking_deadline_local'])
            ? sanitize_text_field($booking['same_day_booking_deadline_local'])
            : '07:30';
        $dropoff_cutoff = isset($booking['dropoff_cutoff_local'])
            ? sanitize_text_field($booking['dropoff_cutoff_local'])
            : '09:30';
        $central_email = sanitize_email(isset($notifications['central_email'])
            ? $notifications['central_email']
            : 'info@chromaela.com');
        $billing_email = sanitize_email(isset($cancellation['refund_owner_email'])
            ? $cancellation['refund_owner_email']
            : 'billing@chromaela.com');

        return array(
            'contractVersion' => 2,
            'currency' => $currency,
            'unitAmountCents' => $amount_cents,
            'priceDisplay' => '$' . number_format($amount_cents / 100, 0),
            'billingUnit' => isset($price['billing_unit']) ? sanitize_key($price['billing_unit']) : 'per_child_per_care_date',
            'billingUnitLabel' => 'per child, per care date',
            'eligibleAgeRange' => sanitize_text_field(isset($eligibility['display_age_range'])
                ? $eligibility['display_age_range']
                : '6 weeks to 12 years'),
            'operatingDays' => isset($booking['operating_days']) && is_array($booking['operating_days'])
                ? array_values(array_map('intval', $booking['operating_days']))
                : array(1, 2, 3, 4, 5),
            'operatingDaysLabel' => 'Weekdays',
            'bookingHorizonDays' => isset($booking['booking_horizon_days']) ? (int) $booking['booking_horizon_days'] : 365,
            'maxCareDatesPerOrder' => isset($booking['max_care_dates_per_order']) ? (int) $booking['max_care_dates_per_order'] : 31,
            'minimumNoticeMinutes' => isset($booking['minimum_notice_minutes']) ? (int) $booking['minimum_notice_minutes'] : 120,
            'sameDayDeadline' => $same_day_deadline,
            'sameDayDeadlineLabel' => $this->format_time_label($same_day_deadline),
            'dropoffCutoff' => $dropoff_cutoff,
            'dropoffCutoffLabel' => $this->format_time_label($dropoff_cutoff),
            'refundCutoffHours' => isset($cancellation['refundable_until_hours_before_care'])
                ? (int) $cancellation['refundable_until_hours_before_care']
                : 72,
            'rescheduleCutoffHours' => isset($cancellation['reschedulable_until_hours_before_care'])
                ? (int) $cancellation['reschedulable_until_hours_before_care']
                : 72,
            'completedEnrollmentRequired' => !empty($eligibility['completed_enrollment_record_required_before_care']),
            'enrollmentRequirementMessage' => 'Required enrollment and health records must be complete before care begins.',
            'availabilityNotice' => 'Campus and date options are subject to operational closures, staffing, and classroom ratio requirements.',
            'supportEmail' => $central_email ?: 'info@chromaela.com',
            'billingSupportEmail' => $billing_email ?: 'billing@chromaela.com',
            'timezone' => isset($this->manifest['program']['timezone'])
                ? sanitize_text_field($this->manifest['program']['timezone'])
                : 'America/New_York',
            'campuses' => $campuses,
            'forms' => array(
                'family_profile' => sanitize_text_field(isset($form_ids['family_profile']) ? $form_ids['family_profile'] : ''),
                'child_enrollment' => sanitize_text_field(isset($form_ids['child_enrollment']) ? $form_ids['child_enrollment'] : ''),
            ),
            'campusSelectionMode' => 'explicit_list',
        );
    }

    private function format_time_label($time)
    {
        $date = DateTimeImmutable::createFromFormat('!H:i', (string) $time);
        return $date ? $date->format('g:i A') : sanitize_text_field((string) $time);
    }

    public function mode()
    {
        return (string) get_option('chroma_backup_care_mode', 'disabled');
    }

    public function is_checkout_enabled()
    {
        return (bool) get_option('chroma_backup_care_checkout_enabled', false);
    }

    public function ghl_token()
    {
        return $this->secret('CHROMA_BACKUP_CARE_GHL_TOKEN', 'chroma_backup_care_ghl_token');
    }

    public function email_from()
    {
        return sanitize_email((string) get_option('chroma_backup_care_email_from', 'info@chromaela.com'));
    }

    public function notification_workflow_id($event_name)
    {
        $configured = trim((string) get_option(
            'chroma_backup_care_workflow_' . sanitize_key($event_name),
            ''
        ));
        if ($configured !== '') {
            return $configured;
        }
        foreach ($this->manifest['workflow_specification'] as $workflow) {
            if (isset($workflow['event_key'], $workflow['id'])
                && $workflow['event_key'] === $event_name
                && isset($workflow['status'])
                && strtolower((string) $workflow['status']) === 'active') {
                return (string) $workflow['id'];
            }
        }
        return '';
    }

    public function quote_signing_key()
    {
        if (defined('AUTH_SALT') && AUTH_SALT) {
            return hash('sha256', AUTH_SALT . '|chroma-backup-care');
        }
        return hash('sha256', wp_salt('auth') . '|chroma-backup-care');
    }

    public function object_schema($name)
    {
        $keys = isset($this->manifest['ghl']['custom_object_schema_keys'])
            ? $this->manifest['ghl']['custom_object_schema_keys']
            : array();
        return isset($keys[$name]) ? (string) $keys[$name] : '';
    }

    public function association_id($name)
    {
        $ids = isset($this->manifest['ghl']['association_ids'])
            ? $this->manifest['ghl']['association_ids']
            : array();
        return isset($ids[$name]) ? (string) $ids[$name] : '';
    }

    public function workflow_id($name)
    {
        foreach ($this->manifest['workflow_specification'] as $workflow) {
            if (isset($workflow['name']) && $workflow['name'] === $name) {
                return (string) $workflow['id'];
            }
        }
        return '';
    }

    public function readiness()
    {
        $errors = array();
        $mode = $this->mode();
        if (!in_array($mode, array('test', 'live'), true)) {
            $errors[] = 'Checkout mode is disabled.';
        }
        if (!$this->is_checkout_enabled()) {
            $errors[] = 'Checkout feature flag is disabled.';
        }
        if ($this->ghl_token() === '') {
            $errors[] = 'GHL token is not configured.';
        }
        if ($mode === 'live') {
            $errors = array_merge($errors, $this->live_release_errors());
        }
        return array('ready' => empty($errors), 'errors' => $errors, 'mode' => $mode);
    }

    public function live_release_errors()
    {
        $errors = array();
        if (empty($this->manifest['live_changes_allowed'])) {
            $errors[] = 'The release manifest does not authorize live changes.';
        }
        if (!defined('CHROMA_BACKUP_CARE_LIVE_APPROVED')
            || CHROMA_BACKUP_CARE_LIVE_APPROVED !== true) {
            $errors[] = 'Server configuration has not approved live checkout.';
        }
        foreach (isset($this->manifest['deployment_gates']) && is_array($this->manifest['deployment_gates'])
            ? $this->manifest['deployment_gates']
            : array() as $gate) {
            if (!empty($gate['required']) && (!isset($gate['status']) || $gate['status'] !== 'complete')) {
                $errors[] = 'Required release gate is incomplete: ' . sanitize_key((string) $gate['id']);
            }
        }
        return $errors;
    }

    private function secret($constant_name, $option_name)
    {
        if (defined($constant_name)) {
            return trim((string) constant($constant_name));
        }
        $environment_value = getenv($constant_name);
        return is_string($environment_value) ? trim($environment_value) : '';
    }
}

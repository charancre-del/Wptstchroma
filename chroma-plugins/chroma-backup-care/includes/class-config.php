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
        $rules = $this->manifest['business_rules'];
        $campuses = array();
        foreach ($this->campuses() as $campus) {
            $campuses[] = array(
                'id' => $campus['id'],
                'name' => $campus['name'],
                'address' => $campus['address'],
                'opens' => $campus['published_open'],
                'closes' => $campus['published_close'],
            );
        }

        return array(
            'contractVersion' => 1,
            'currency' => $rules['price']['currency'],
            'unitAmountCents' => (int) $rules['price']['amount_cents'],
            'bookingHorizonDays' => (int) $rules['booking']['booking_horizon_days'],
            'maxCareDatesPerOrder' => (int) $rules['booking']['max_care_dates_per_order'],
            'minimumNoticeMinutes' => (int) $rules['booking']['minimum_notice_minutes'],
            'sameDayDeadline' => $rules['booking']['same_day_booking_deadline_local'],
            'dropoffCutoff' => $rules['booking']['dropoff_cutoff_local'],
            'refundCutoffHours' => (int) $rules['cancellation']['refundable_until_hours_before_care'],
            'rescheduleCutoffHours' => (int) $rules['cancellation']['reschedulable_until_hours_before_care'],
            'campuses' => $campuses,
            'forms' => array(
                'family_profile' => $this->manifest['ghl']['form_ids']['family_profile'],
                'child_enrollment' => $this->manifest['ghl']['form_ids']['child_enrollment'],
            ),
            'campusSelectionMode' => 'explicit_list',
        );
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

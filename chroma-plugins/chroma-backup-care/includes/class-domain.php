<?php

if (!defined('ABSPATH') && !defined('CHROMA_BACKUP_CARE_TESTING')) {
    exit;
}

final class Chroma_Backup_Care_Domain
{
    const REQUIRED_POLICIES = array(
        'backup_care_terms',
        'full_payment',
        'refund_and_reschedule_deadline',
        'no_discretionary_exceptions',
        'privacy_and_communications',
    );

    private $manifest;
    private $campuses;

    public function __construct(array $manifest)
    {
        $this->manifest = $manifest;
        $this->campuses = array();
        foreach ($manifest['campuses'] as $campus) {
            $this->campuses[$campus['id']] = $campus;
        }
    }

    public function quote(array $order, DateTimeImmutable $now, array $closures = array(), array $occupancy = array())
    {
        $errors = array();
        $this->reject_unknown($order, array(
            'contract_version', 'client_request_id', 'campus_id', 'parent', 'children',
            'attendance', 'policy_acceptance',
        ), 'order', $errors);

        if (!isset($order['contract_version']) || (int) $order['contract_version'] !== 1) {
            $errors[] = 'contract_version must be 1';
        }

        $request_id = $this->text($order, 'client_request_id', 'client_request_id', $errors, 16, 128);
        if ($request_id && !preg_match('/^[A-Za-z0-9_-]+$/', $request_id)) {
            $errors[] = 'client_request_id has invalid characters';
        }

        $campus_id = $this->text($order, 'campus_id', 'campus_id', $errors, 1, 64);
        $campus = isset($this->campuses[$campus_id]) ? $this->campuses[$campus_id] : null;
        if ($campus_id && !$campus) {
            $errors[] = 'campus_id is not a configured Chroma campus';
        }

        $parent = isset($order['parent']) && is_array($order['parent']) ? $order['parent'] : array();
        if (!$parent) {
            $errors[] = 'parent must be an object';
        }
        $this->reject_unknown($parent, array('first_name', 'last_name', 'email', 'mobile_phone'), 'parent', $errors);
        $this->text($parent, 'first_name', 'parent.first_name', $errors, 1, 80);
        $this->text($parent, 'last_name', 'parent.last_name', $errors, 1, 80);
        $email = $this->text($parent, 'email', 'parent.email', $errors, 3, 254);
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'parent.email is invalid';
        }
        $phone = $this->text($parent, 'mobile_phone', 'parent.mobile_phone', $errors, 7, 32);
        if ($phone && strlen(preg_replace('/\D+/', '', $phone)) < 7) {
            $errors[] = 'parent.mobile_phone is invalid';
        }

        $children = isset($order['children']) && is_array($order['children']) ? $order['children'] : array();
        if (count($children) < 1 || count($children) > 10) {
            $errors[] = 'children must contain 1 to 10 records';
        }
        $child_ids = array();
        $child_birth_dates = array();
        $age_groups = array_flip($this->manifest['program']['age_groups']);
        foreach ($children as $index => $child) {
            $field = 'children[' . $index . ']';
            if (!is_array($child)) {
                $errors[] = $field . ' must be an object';
                continue;
            }
            $this->reject_unknown($child, array(
                'client_child_id', 'first_name', 'last_name', 'date_of_birth', 'age_group',
                'child_record_key', 'enrollment_record_id', 'enrollment_record_complete',
                'review_required', 'review_reasons',
            ), $field, $errors);
            $child_id = $this->text($child, 'client_child_id', $field . '.client_child_id', $errors, 1, 64);
            if ($child_id && !preg_match('/^[A-Za-z0-9_-]+$/', $child_id)) {
                $errors[] = $field . '.client_child_id has invalid characters';
            }
            if ($child_id && isset($child_ids[$child_id])) {
                $errors[] = 'Duplicate client_child_id: ' . $child_id;
            }
            if ($child_id) {
                $child_ids[$child_id] = true;
            }
            $this->text($child, 'child_record_key', $field . '.child_record_key', $errors, 8, 128);
            $this->text($child, 'first_name', $field . '.first_name', $errors, 1, 80);
            $this->text($child, 'last_name', $field . '.last_name', $errors, 1, 80);
            $birth_date = $this->date_value(isset($child['date_of_birth']) ? $child['date_of_birth'] : null, $field . '.date_of_birth', $errors);
            if ($birth_date && $birth_date >= $now->setTime(0, 0)) {
                $errors[] = $field . '.date_of_birth must be before today';
            }
            if ($child_id && $birth_date) {
                $child_birth_dates[$child_id] = $birth_date;
            }
            if (!isset($child['age_group']) || !isset($age_groups[$child['age_group']])) {
                $errors[] = $field . '.age_group is invalid';
            }
            $this->text($child, 'enrollment_record_id', $field . '.enrollment_record_id', $errors, 8, 128);
            if (!isset($child['enrollment_record_complete']) || $child['enrollment_record_complete'] !== true) {
                $errors[] = $field . '.enrollment_record_complete must be true';
            }
        }

        $attendance = isset($order['attendance']) && is_array($order['attendance']) ? $order['attendance'] : array();
        if (count($attendance) < 1 || count($attendance) > 310) {
            $errors[] = 'attendance must contain 1 to 310 child-date units';
        }

        $booking = $this->manifest['business_rules']['booking'];
        $capacity_limit = (int) $this->manifest['business_rules']['capacity']['max_booking_units_per_campus_per_care_date'];
        $horizon = (int) $booking['booking_horizon_days'];
        $same_day_deadline = $this->minutes($booking['same_day_booking_deadline_local']);
        $dropoff_cutoff = $this->minutes($booking['dropoff_cutoff_local']);
        $minimum_notice = (int) $booking['minimum_notice_minutes'];
        $operating_days = array_flip($booking['operating_days']);
        $today = $now->setTime(0, 0);
        $pairs = array();
        $represented = array();
        $dates = array();
        $line_items = array();
        $requested_per_date = array();

        foreach ($attendance as $index => $unit) {
            $field = 'attendance[' . $index . ']';
            if (!is_array($unit)) {
                $errors[] = $field . ' must be an object';
                continue;
            }
            $this->reject_unknown($unit, array('client_child_id', 'care_date', 'planned_dropoff_local'), $field, $errors);
            $child_id = $this->text($unit, 'client_child_id', $field . '.client_child_id', $errors, 1, 64);
            if ($child_id && !isset($child_ids[$child_id])) {
                $errors[] = $field . '.client_child_id does not match a child';
            }
            $care_date_text = isset($unit['care_date']) && is_string($unit['care_date']) ? $unit['care_date'] : '';
            $care_date = $this->date_value($care_date_text, $field . '.care_date', $errors);
            $pair_key = $child_id . '|' . $care_date_text;
            if (isset($pairs[$pair_key])) {
                $errors[] = 'Duplicate child-date unit: ' . $child_id . '/' . $care_date_text;
            }
            $pairs[$pair_key] = true;
            if ($child_id) {
                $represented[$child_id] = true;
            }

            if ($care_date) {
                $dates[$care_date_text] = true;
                $day_offset = (int) $today->diff($care_date)->format('%r%a');
                if ($day_offset < 0) {
                    $errors[] = $field . '.care_date is in the past';
                }
                if (!isset($operating_days[(int) $care_date->format('N')])) {
                    $errors[] = $field . '.care_date is not an operating day';
                }
                if ($day_offset > $horizon) {
                    $errors[] = $field . '.care_date exceeds the booking horizon';
                }
                if ($day_offset === 0 && ($now->format('G') * 60 + (int) $now->format('i')) > $same_day_deadline) {
                    $errors[] = $field . '.care_date missed the 7:30 AM same-day deadline';
                }
                if (isset($closures['*|' . $care_date_text])
                    || isset($closures['all|' . $care_date_text])
                    || isset($closures[$campus_id . '|' . $care_date_text])) {
                    $errors[] = $field . '.care_date is closed for backup care';
                }
                $requested_per_date[$care_date_text] = isset($requested_per_date[$care_date_text])
                    ? $requested_per_date[$care_date_text] + 1
                    : 1;
                if ($child_id && isset($child_birth_dates[$child_id])) {
                    $eligible_from = $child_birth_dates[$child_id]->modify('+42 days');
                    $eligible_until = $child_birth_dates[$child_id]->modify('+13 years');
                    if ($care_date < $eligible_from || $care_date >= $eligible_until) {
                        $errors[] = $field . '.client_child_id must be 6 weeks through 12 years old on the care date';
                    }
                }
            }

            $planned_dropoff = isset($unit['planned_dropoff_local']) && is_string($unit['planned_dropoff_local'])
                ? $unit['planned_dropoff_local']
                : '';
            $dropoff_minutes = $this->time_value($planned_dropoff, $field . '.planned_dropoff_local', $errors);
            if ($dropoff_minutes !== null && $campus) {
                $opening = $this->minutes($campus['published_open']);
                if ($dropoff_minutes < $opening || $dropoff_minutes > $dropoff_cutoff) {
                    $errors[] = $field . '.planned_dropoff_local must be between campus opening and 9:30 AM';
                }
                if ($care_date && $care_date->format('Y-m-d') === $now->format('Y-m-d')) {
                    $now_minutes = ((int) $now->format('G')) * 60 + (int) $now->format('i');
                    if ($dropoff_minutes - $now_minutes < $minimum_notice) {
                        $errors[] = $field . '.planned_dropoff_local does not provide 120 minutes notice';
                    }
                }
            }

            if ($request_id && $campus_id && $child_id && $care_date_text) {
                $line_items[] = array(
                    'line_item_key' => $this->line_item_key($request_id, $campus_id, $child_id, $care_date_text),
                    'client_child_id' => $child_id,
                    'care_date' => $care_date_text,
                    'planned_dropoff_local' => $planned_dropoff,
                );
            }
        }

        $maximum_dates = isset($booking['max_care_dates_per_order'])
            ? (int) $booking['max_care_dates_per_order']
            : 31;
        if (count($dates) > $maximum_dates) {
            $errors[] = 'One order may contain at most ' . $maximum_dates . ' care dates';
        }

        foreach (array_keys($child_ids) as $child_id) {
            if (!isset($represented[$child_id])) {
                $errors[] = 'Every child must have at least one care date: ' . $child_id;
            }
        }
        foreach ($requested_per_date as $care_date_text => $requested) {
            $key = $campus_id . '|' . $care_date_text;
            $current = isset($occupancy[$key]) ? (int) $occupancy[$key] : 0;
            if ($current + $requested > $capacity_limit) {
                $errors[] = 'Capacity is unavailable for ' . $care_date_text;
            }
        }

        $policies = isset($order['policy_acceptance']) && is_array($order['policy_acceptance'])
            ? $order['policy_acceptance']
            : array();
        if (!$policies) {
            $errors[] = 'policy_acceptance must be an object';
        }
        $this->reject_unknown($policies, self::REQUIRED_POLICIES, 'policy_acceptance', $errors);
        foreach (self::REQUIRED_POLICIES as $policy) {
            if (!isset($policies[$policy]) || $policies[$policy] !== true) {
                $errors[] = 'policy_acceptance.' . $policy . ' must be true';
            }
        }

        $unit_amount = (int) $this->manifest['business_rules']['price']['amount_cents'];
        $quote = empty($errors) ? array(
            'currency' => strtolower($this->manifest['business_rules']['price']['currency']),
            'unit_amount_cents' => $unit_amount,
            'unit_count' => count($line_items),
            'total_amount_cents' => $unit_amount * count($line_items),
            'billing_unit' => 'per_child_per_care_date',
            'line_items' => $line_items,
        ) : null;

        return array(
            'contract_valid' => empty($errors),
            'payment_creation_allowed' => empty($errors),
            'campus_id' => $campus_id ?: null,
            'child_count' => count($children),
            'care_date_count' => count($dates),
            'unit_count' => count($attendance),
            'quote' => $quote,
            'errors' => $errors,
        );
    }

    public function preflight(array $order)
    {
        $errors = array();
        $this->reject_unknown($order, array(
            'contract_version', 'client_request_id', 'campus_id', 'parent', 'children',
            'attendance', 'policy_acceptance', 'parent_access_token',
        ), 'order', $errors);

        if (!isset($order['parent']) || !is_array($order['parent'])) {
            $errors[] = 'parent must be an object';
        }
        $children = isset($order['children']) && is_array($order['children']) ? $order['children'] : null;
        if ($children === null || count($children) < 1 || count($children) > 10) {
            $errors[] = 'children must contain 1 to 10 records';
        }
        if (is_array($children)) {
            foreach ($children as $index => $child) {
                if (!is_array($child)) {
                    $errors[] = 'children[' . $index . '] must be an object';
                }
            }
        }

        $attendance = isset($order['attendance']) && is_array($order['attendance'])
            ? $order['attendance']
            : null;
        if ($attendance === null || count($attendance) < 1 || count($attendance) > 310) {
            $errors[] = 'attendance must contain 1 to 310 child-date units';
        }
        $dates = array();
        if (is_array($attendance)) {
            foreach ($attendance as $index => $unit) {
                if (!is_array($unit)) {
                    $errors[] = 'attendance[' . $index . '] must be an object';
                    continue;
                }
                $date = isset($unit['care_date']) && is_string($unit['care_date'])
                    ? $unit['care_date']
                    : '';
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $errors[] = 'attendance[' . $index . '].care_date must use YYYY-MM-DD';
                    continue;
                }
                $dates[$date] = true;
            }
        }
        $maximum_dates = isset($this->manifest['business_rules']['booking']['max_care_dates_per_order'])
            ? (int) $this->manifest['business_rules']['booking']['max_care_dates_per_order']
            : 31;
        if (count($dates) > $maximum_dates) {
            $errors[] = 'One order may contain at most ' . $maximum_dates . ' care dates';
        }
        return array_values(array_unique($errors));
    }

    public function line_item_key($request_id, $campus_id, $child_id, $care_date)
    {
        return 'bcu_' . substr(hash('sha256', implode('|', array($request_id, $campus_id, $child_id, $care_date))), 0, 24);
    }

    public function validate_service_date($campus_id, $care_date_text, $dropoff_text, DateTimeImmutable $now, array $closures, $occupancy)
    {
        $errors = array();
        $campus = isset($this->campuses[$campus_id]) ? $this->campuses[$campus_id] : null;
        if (!$campus) {
            return array('campus_id is not a configured Chroma campus');
        }
        $care_date = $this->date_value($care_date_text, 'care_date', $errors);
        $dropoff = $this->time_value($dropoff_text, 'planned_dropoff_local', $errors);
        if (!$care_date || $dropoff === null) {
            return $errors;
        }
        $booking = $this->manifest['business_rules']['booking'];
        $today = $now->setTime(0, 0);
        $offset = (int) $today->diff($care_date)->format('%r%a');
        if ($offset < 0) {
            $errors[] = 'care_date is in the past';
        }
        if (!in_array((int) $care_date->format('N'), $booking['operating_days'], true)) {
            $errors[] = 'care_date is not an operating day';
        }
        if ($offset > (int) $booking['booking_horizon_days']) {
            $errors[] = 'care_date exceeds the booking horizon';
        }
        if (isset($closures['*|' . $care_date_text])
            || isset($closures['all|' . $care_date_text])
            || isset($closures[$campus_id . '|' . $care_date_text])) {
            $errors[] = 'care_date is closed for backup care';
        }
        $opening = $this->minutes($campus['published_open']);
        $cutoff = $this->minutes($booking['dropoff_cutoff_local']);
        if ($dropoff < $opening || $dropoff > $cutoff) {
            $errors[] = 'planned_dropoff_local must be between campus opening and 9:30 AM';
        }
        $now_minutes = ((int) $now->format('G')) * 60 + (int) $now->format('i');
        if ($offset === 0 && $now_minutes > $this->minutes($booking['same_day_booking_deadline_local'])) {
            $errors[] = 'care_date missed the 7:30 AM same-day deadline';
        }
        if ($offset === 0 && $dropoff - $now_minutes < (int) $booking['minimum_notice_minutes']) {
            $errors[] = 'planned_dropoff_local does not provide 120 minutes notice';
        }
        $capacity = (int) $this->manifest['business_rules']['capacity']['max_booking_units_per_campus_per_care_date'];
        if ((int) $occupancy + 1 > $capacity) {
            $errors[] = 'Capacity is unavailable for ' . $care_date_text;
        }
        return $errors;
    }

    private function text(array $source, $key, $field, array &$errors, $minimum, $maximum)
    {
        $value = isset($source[$key]) && is_string($source[$key]) ? trim($source[$key]) : '';
        if ($value === '') {
            $errors[] = $field . ' is required';
            return '';
        }
        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
        if ($length < $minimum) {
            $errors[] = $field . ' must contain at least ' . $minimum . ' characters';
        }
        if ($length > $maximum) {
            $errors[] = $field . ' exceeds ' . $maximum . ' characters';
        }
        return $value;
    }

    private function reject_unknown(array $source, array $allowed, $field, array &$errors)
    {
        $unknown = array_diff(array_keys($source), $allowed);
        if ($unknown) {
            sort($unknown);
            $errors[] = $field . ' contains unsupported fields: ' . implode(', ', $unknown);
        }
    }

    private function date_value($value, $field, array &$errors)
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $errors[] = $field . ' must be an ISO date';
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('America/New_York'));
        $date_errors = DateTimeImmutable::getLastErrors();
        if (!$date || (is_array($date_errors) && ($date_errors['warning_count'] || $date_errors['error_count']))) {
            $errors[] = $field . ' must be an ISO date';
            return null;
        }
        return $date;
    }

    private function time_value($value, $field, array &$errors)
    {
        if (!is_string($value) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            $errors[] = $field . ' must use HH:MM';
            return null;
        }
        return $this->minutes($value);
    }

    private function minutes($value)
    {
        list($hour, $minute) = array_map('intval', explode(':', $value, 2));
        return $hour * 60 + $minute;
    }
}

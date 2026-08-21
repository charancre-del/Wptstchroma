<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Chroma_Backup_Care_GHL_Client
{
    private $config;
    private $base_url = 'https://services.leadconnectorhq.com';

    public function __construct(Chroma_Backup_Care_Config $config)
    {
        $this->config = $config;
    }

    public function upsert_contact(array $parent)
    {
        $response = $this->request('POST', '/contacts/upsert', array(
            'firstName' => $parent['first_name'],
            'lastName' => $parent['last_name'],
            'email' => strtolower($parent['email']),
            'phone' => $parent['mobile_phone'],
            'locationId' => Chroma_Backup_Care_Config::GHL_LOCATION_ID,
            'source' => 'Chroma Backup Care',
            'createNewIfDuplicateAllowed' => false,
        ), 'v3');
        if (empty($response['contact']['id'])) {
            throw new RuntimeException('GHL did not return a contact ID.');
        }
        return $response['contact'];
    }

    public function find_contact_by_email($email)
    {
        $email = strtolower(trim((string) $email));
        $response = $this->request(
            'GET',
            '/contacts/search/duplicate?locationId=' . rawurlencode(Chroma_Backup_Care_Config::GHL_LOCATION_ID)
                . '&email=' . rawurlencode($email),
            array(),
            '2021-07-28'
        );
        $contact = isset($response['contact']) && is_array($response['contact'])
            ? $response['contact']
            : array();
        $contact_email = isset($contact['emailLowerCase'])
            ? $contact['emailLowerCase']
            : (isset($contact['email']) ? $contact['email'] : '');
        if (empty($contact['id']) || !hash_equals($email, strtolower(trim((string) $contact_email)))) {
            return null;
        }
        return $contact;
    }

    public function get_contact($contact_id)
    {
        $response = $this->request(
            'GET',
            '/contacts/' . rawurlencode((string) $contact_id),
            array(),
            'v3'
        );
        if (empty($response['contact']['id'])) {
            throw new RuntimeException('GHL did not return the verified parent contact.');
        }
        return $response['contact'];
    }

    public function relation_exists($record_id, $related_record_id, $association_id)
    {
        $response = $this->request(
            'GET',
            '/associations/relations/' . rawurlencode((string) $record_id)
                . '?locationId=' . rawurlencode(Chroma_Backup_Care_Config::GHL_LOCATION_ID)
                . '&skip=0&limit=100&associationIds=' . rawurlencode((string) $association_id),
            array(),
            'v3'
        );
        $encoded = wp_json_encode($response);
        return is_string($encoded)
            && strpos($encoded, '"' . (string) $association_id . '"') !== false
            && strpos($encoded, '"' . (string) $related_record_id . '"') !== false;
    }

    public function create_record($schema_key, array $properties)
    {
        $response = $this->request('POST', '/objects/' . rawurlencode($schema_key) . '/records', array(
            'locationId' => Chroma_Backup_Care_Config::GHL_LOCATION_ID,
            'properties' => $properties,
        ), '2023-02-21');
        if (empty($response['record']['id'])) {
            throw new RuntimeException('GHL did not return a custom object record ID.');
        }
        return $response['record'];
    }

    public function upsert_record($schema_key, $unique_key, $unique_value, array $properties)
    {
        $result = $this->search_records($schema_key, $unique_value, 20);
        foreach (isset($result['records']) ? $result['records'] : array() as $record) {
            $record_properties = isset($record['properties']) ? $record['properties'] : array();
            if (isset($record_properties[$unique_key])
                && (string) $record_properties[$unique_key] === (string) $unique_value) {
                $this->update_record($schema_key, $record['id'], $properties);
                $record['properties'] = array_merge($record_properties, $properties);
                return $record;
            }
        }
        return $this->create_record($schema_key, $properties);
    }

    public function update_record($schema_key, $record_id, array $properties)
    {
        return $this->request(
            'PUT',
            '/objects/' . rawurlencode($schema_key) . '/records/' . rawurlencode($record_id)
                . '?locationId=' . rawurlencode(Chroma_Backup_Care_Config::GHL_LOCATION_ID),
            array('properties' => $properties),
            'v3'
        );
    }

    public function get_record($schema_key, $record_id)
    {
        $response = $this->request(
            'GET',
            '/objects/' . rawurlencode($schema_key) . '/records/' . rawurlencode($record_id),
            array(),
            '2021-04-15'
        );
        if (empty($response['record']['id'])) {
            throw new RuntimeException('GHL did not return a custom object record.');
        }
        return $response['record'];
    }

    public function search_records($schema_key, $query, $page_limit = 100)
    {
        $response = $this->request('POST', '/objects/' . rawurlencode($schema_key) . '/records/search', array(
            'locationId' => Chroma_Backup_Care_Config::GHL_LOCATION_ID,
            'page' => 1,
            'pageLimit' => min(100, max(1, (int) $page_limit)),
            'query' => (string) $query,
            'searchAfter' => array(),
        ), 'v3');

        // The v3 API names this collection customObjectRecords. Normalize it so
        // the rest of the coordinator has one stable response contract.
        if (!isset($response['records'])) {
            $response['records'] = isset($response['customObjectRecords'])
                && is_array($response['customObjectRecords'])
                ? $response['customObjectRecords']
                : array();
        }
        return $response;
    }

    public function create_relation($association_id, $first_record_id, $second_record_id)
    {
        try {
            return $this->request('POST', '/associations/relations', array(
                'locationId' => Chroma_Backup_Care_Config::GHL_LOCATION_ID,
                'associationId' => $association_id,
                'firstRecordId' => $first_record_id,
                'secondRecordId' => $second_record_id,
            ), 'v3');
        } catch (RuntimeException $error) {
            $message = strtolower($error->getMessage());
            if (strpos($message, 'already exists') !== false
                || strpos($message, 'already been associated') !== false
                || strpos($message, 'duplicate') !== false
                || strpos($message, 'relation exists') !== false
                || strpos($message, 'max relation limit') !== false) {
                return array('duplicate' => true);
            }
            throw $error;
        }
    }

    public function enroll_workflow($contact_id, $workflow_id)
    {
        if (!$contact_id || !$workflow_id) {
            return null;
        }
        return $this->request(
            'POST',
            '/contacts/' . rawurlencode($contact_id) . '/workflow/' . rawurlencode($workflow_id),
            array(),
            '2021-07-28'
        );
    }

    public function send_email($contact_id, $email_to, $subject, $html)
    {
        $payload = array(
            'type' => 'Email',
            'contactId' => $contact_id,
            'emailFrom' => $this->config->email_from(),
            'subject' => $subject,
            'html' => $html,
            'message' => wp_strip_all_tags($html),
            'status' => 'pending',
        );
        if ($email_to) {
            $payload['emailTo'] = $email_to;
        }
        return $this->request('POST', '/conversations/messages', $payload, 'v3');
    }

    public function create_task($contact_id, $title, $body, $due_date, $assigned_to = '')
    {
        $payload = array(
            'title' => $title,
            'body' => $body,
            'dueDate' => $due_date,
            'completed' => false,
        );
        if ($assigned_to) {
            $payload['assignedTo'] = $assigned_to;
        }
        return $this->request(
            'POST',
            '/contacts/' . rawurlencode($contact_id) . '/tasks',
            $payload,
            'v3'
        );
    }

    public function create_backup_care_invoice(
        $request_id,
        array $parent,
        $contact_id,
        array $campus,
        array $quote
    ) {
        $items = array();
        foreach ($quote['line_items'] as $line) {
            $items[] = array(
                'name' => 'Backup Care - '
                    . (isset($line['child_name']) ? $line['child_name'] : 'Child')
                    . ' - ' . $line['care_date'],
                'description' => $campus['name'] . ' | ' . $line['line_item_key'],
                'currency' => 'USD',
                'amount' => ((int) $quote['unit_amount_cents']) / 100,
                'qty' => 1,
                'taxes' => array(),
                'type' => 'one_time',
                'taxInclusive' => true,
            );
        }

        $today = gmdate('Y-m-d');
        $live_mode = $this->config->mode() === 'live';
        $invoice = $this->request('POST', '/invoices/', array(
            'altId' => Chroma_Backup_Care_Config::GHL_LOCATION_ID,
            'altType' => 'location',
            'name' => 'Backup Care ' . $request_id,
            'businessDetails' => array(
                'name' => 'Chroma Early Learning Academy',
                'website' => 'https://chromaela.com',
            ),
            'currency' => 'USD',
            'items' => $items,
            'discount' => array('value' => 0, 'type' => 'percentage'),
            'termsNotes' => 'Full payment confirms the selected child-date units. Cancellation and rescheduling are available until 72 hours before care.',
            'title' => 'BACKUP CARE INVOICE',
            'contactDetails' => array(
                'id' => (string) $contact_id,
                'name' => trim($parent['first_name'] . ' ' . $parent['last_name']),
                'phoneNo' => $parent['mobile_phone'],
                'email' => strtolower($parent['email']),
                'customFields' => array(),
            ),
            'issueDate' => $today,
            'dueDate' => $today,
            'sentTo' => array(
                'email' => array(strtolower($parent['email'])),
                'emailCc' => array(),
                'emailBcc' => array(),
                'phoneNo' => array(),
            ),
            'liveMode' => $live_mode,
            'automaticTaxesEnabled' => false,
            'tipsConfiguration' => array('tipsPercentage' => array(), 'tipsEnabled' => false),
            'paymentMethods' => array(
                'stripe' => array('enableBankDebitOnly' => false),
            ),
        ), 'v3');
        if (empty($invoice['_id'])) {
            throw new RuntimeException('GHL did not return an invoice ID.');
        }
        return $invoice;
    }

    public function find_backup_care_invoice($request_id, $contact_id)
    {
        $name = 'Backup Care ' . (string) $request_id;
        $path = '/invoices/?altId=' . rawurlencode(Chroma_Backup_Care_Config::GHL_LOCATION_ID)
            . '&altType=location&limit=10&offset=0&search=' . rawurlencode($name)
            . '&contactId=' . rawurlencode((string) $contact_id)
            . '&paymentMode=' . ($this->config->mode() === 'live' ? 'live' : 'test');
        $response = $this->request('GET', $path, array(), 'v3');
        foreach (isset($response['invoices']) && is_array($response['invoices'])
            ? $response['invoices']
            : array() as $invoice) {
            if (!empty($invoice['_id'])
                && isset($invoice['name'])
                && hash_equals($name, (string) $invoice['name'])
                && isset($invoice['contactDetails']['id'])
                && hash_equals((string) $contact_id, (string) $invoice['contactDetails']['id'])) {
                return $invoice;
            }
        }
        return null;
    }

    public function send_backup_care_invoice($invoice_id, $sender_user_id)
    {
        if (!$sender_user_id) {
            throw new RuntimeException('The selected campus has no authorized GHL invoice sender.');
        }
        return $this->request(
            'POST',
            '/invoices/' . rawurlencode((string) $invoice_id) . '/send',
            array(
                'altId' => Chroma_Backup_Care_Config::GHL_LOCATION_ID,
                'altType' => 'location',
                'userId' => (string) $sender_user_id,
                'action' => 'email',
                'liveMode' => $this->config->mode() === 'live',
                'sentFrom' => array(
                    'fromName' => 'Chroma Early Learning Academy',
                    'fromEmail' => $this->config->email_from(),
                ),
            ),
            'v3'
        );
    }

    public function get_invoice($invoice_id)
    {
        $response = $this->request(
            'GET',
            '/invoices/' . rawurlencode((string) $invoice_id)
                . '?altId=' . rawurlencode(Chroma_Backup_Care_Config::GHL_LOCATION_ID)
                . '&altType=location',
            array(),
            'v3'
        );
        if (empty($response['_id'])) {
            throw new RuntimeException('GHL did not return the invoice.');
        }
        return $response;
    }

    public function void_invoice($invoice_id)
    {
        return $this->request(
            'POST',
            '/invoices/' . rawurlencode((string) $invoice_id) . '/void',
            array(
                'altId' => Chroma_Backup_Care_Config::GHL_LOCATION_ID,
                'altType' => 'location',
            ),
            'v3'
        );
    }

    public function ensure_backup_care_appointment(
        $contact_id,
        $campus_id,
        array $line,
        array $child
    ) {
        $projection = $this->config->calendar_projection($campus_id);
        $title = $this->backup_care_appointment_title($line['line_item_key'], $child);
        $bounds = $this->calendar_day_bounds($line['care_date']);
        $response = $this->request(
            'GET',
            '/calendars/events?locationId=' . rawurlencode(Chroma_Backup_Care_Config::GHL_LOCATION_ID)
                . '&calendarId=' . rawurlencode($projection['calendar_id'])
                . '&startTime=' . rawurlencode($bounds['start_ms'])
                . '&endTime=' . rawurlencode($bounds['end_ms']),
            array(),
            '2021-07-28'
        );
        $events = isset($response['events']) && is_array($response['events'])
            ? $response['events']
            : array();
        foreach ($events as $event) {
            if (isset($event['id'], $event['title'], $event['contactId'])
                && hash_equals($title, (string) $event['title'])
                && hash_equals((string) $contact_id, (string) $event['contactId'])) {
                return $event;
            }
        }

        $payload = $this->backup_care_appointment_payload(
            $contact_id,
            $campus_id,
            $line,
            $child
        );
        $appointment = $this->request(
            'POST',
            '/calendars/events/appointments',
            $payload,
            '2021-07-28'
        );
        if (empty($appointment['id'])) {
            throw new RuntimeException('GHL did not return a calendar appointment ID.');
        }
        return $appointment;
    }

    public function update_backup_care_appointment(
        $event_id,
        $contact_id,
        $campus_id,
        array $line,
        array $child
    ) {
        if (!$event_id) {
            return $this->ensure_backup_care_appointment($contact_id, $campus_id, $line, $child);
        }
        $appointment = $this->request(
            'PUT',
            '/calendars/events/appointments/' . rawurlencode((string) $event_id),
            $this->backup_care_appointment_payload($contact_id, $campus_id, $line, $child),
            '2021-07-28'
        );
        $appointment = isset($appointment['event']) && is_array($appointment['event'])
            ? $appointment['event']
            : $appointment;
        if (empty($appointment['id'])) {
            $appointment['id'] = (string) $event_id;
        }
        return $appointment;
    }

    public function delete_calendar_event($event_id)
    {
        if (!$event_id) {
            return null;
        }
        try {
            return $this->request(
                'DELETE',
                '/calendars/events/' . rawurlencode((string) $event_id),
                array(),
                '2021-07-28'
            );
        } catch (RuntimeException $error) {
            $message = strtolower($error->getMessage());
            if (strpos($message, 'not found') !== false || strpos($message, 'http 404') !== false) {
                return array('already_deleted' => true);
            }
            throw $error;
        }
    }

    private function backup_care_appointment_payload(
        $contact_id,
        $campus_id,
        array $line,
        array $child
    ) {
        $projection = $this->config->calendar_projection($campus_id);
        $dropoff = !empty($line['planned_dropoff_local'])
            ? (string) $line['planned_dropoff_local']
            : $projection['published_open'];
        $start = new DateTimeImmutable(
            $line['care_date'] . ' ' . $dropoff,
            new DateTimeZone('America/New_York')
        );
        return array(
            'locationId' => Chroma_Backup_Care_Config::GHL_LOCATION_ID,
            'calendarId' => $projection['calendar_id'],
            'contactId' => (string) $contact_id,
            'assignedUserId' => $projection['assigned_user_id'],
            'title' => $this->backup_care_appointment_title($line['line_item_key'], $child),
            'description' => 'Paid Backup Care child-date unit. Attendance ID: ' . $line['line_item_key'],
            'address' => $projection['address'],
            'meetingLocationType' => 'custom',
            'overrideLocationConfig' => true,
            'appointmentStatus' => 'confirmed',
            'startTime' => $start->format(DateTimeInterface::ATOM),
            'endTime' => $start->modify('+30 minutes')->format(DateTimeInterface::ATOM),
            'ignoreDateRange' => true,
            'ignoreFreeSlotValidation' => true,
            'toNotify' => false,
        );
    }

    private function backup_care_appointment_title($line_item_key, array $child)
    {
        $child_name = trim(
            (isset($child['first_name']) ? $child['first_name'] : '') . ' '
            . (isset($child['last_name']) ? $child['last_name'] : '')
        );
        return 'Backup Care | ' . ($child_name ?: 'Child') . ' | ' . (string) $line_item_key;
    }

    private function calendar_day_bounds($care_date)
    {
        $timezone = new DateTimeZone('America/New_York');
        $start = new DateTimeImmutable((string) $care_date . ' 00:00:00', $timezone);
        $end = new DateTimeImmutable((string) $care_date . ' 23:59:59', $timezone);
        return array(
            'start_ms' => (string) ($start->getTimestamp() * 1000),
            'end_ms' => (string) ($end->getTimestamp() * 1000),
        );
    }

    public function closures(array $dates, $campus_id)
    {
        $schema = 'custom_objects.backup_care_closure';
        $closures = array();
        foreach (array_unique($dates) as $date) {
            foreach (array($campus_id . '__' . $date, 'all__' . $date) as $closure_key) {
                try {
                    $result = $this->search_records($schema, $closure_key, 20);
                } catch (Throwable $error) {
                    throw new RuntimeException('Closure records could not be verified before payment.', 0, $error);
                }
                foreach (isset($result['records']) ? $result['records'] : array() as $record) {
                    $properties = isset($record['properties']) ? $record['properties'] : array();
                    $record_key = isset($properties['closure_key']) ? $properties['closure_key'] : '';
                    $record_campus = isset($properties['campus_id']) ? $properties['campus_id'] : '';
                    $active = !isset($properties['status']) || $properties['status'] === 'active';
                    if ($active && $record_key === $closure_key
                        && isset($properties['closure_date']) && $properties['closure_date'] === $date
                        && in_array($record_campus, array('*', 'all', $campus_id), true)) {
                        $normalized_campus = in_array($record_campus, array('*', 'all'), true)
                            ? 'all'
                            : $record_campus;
                        $closures[$normalized_campus . '|' . $date] = true;
                    }
                }
            }
        }
        return $closures;
    }

    public function resolve_child_record($child_record_key)
    {
        $schema = $this->config->object_schema('child');
        $result = $this->search_records($schema, $child_record_key, 20);
        foreach (isset($result['records']) ? $result['records'] : array() as $record) {
            $properties = isset($record['properties']) ? $record['properties'] : array();
            if (isset($properties['child_record_key'])
                && hash_equals((string) $properties['child_record_key'], (string) $child_record_key)) {
                $review_reasons = array();
                $missing_fields = array();
                $required_fields = isset($this->config->manifest()['ghl']['child_record_required_fields'])
                    && is_array($this->config->manifest()['ghl']['child_record_required_fields'])
                    ? $this->config->manifest()['ghl']['child_record_required_fields']
                    : array();
                foreach ($required_fields as $required_field) {
                    $value = isset($properties[$required_field]) ? $properties[$required_field] : null;
                    $present = $required_field === 'emergency_medical_authorization'
                        ? $this->has_affirmative_consent($value)
                        : $this->has_required_value($value);
                    if (!$present) {
                        $missing_fields[] = $required_field;
                    }
                }
                if ($this->has_review_value(isset($properties['medications_and_procedures'])
                    ? $properties['medications_and_procedures'] : '')) {
                    $review_reasons[] = 'medication_or_special_procedure';
                }
                if ($this->has_review_value(isset($properties['requested_accommodations'])
                    ? $properties['requested_accommodations'] : '')) {
                    $review_reasons[] = 'requested_accommodation';
                }
                return array(
                    'id' => $record['id'],
                    'complete' => isset($properties['record_status'])
                        && $properties['record_status'] === 'complete'
                        && !$missing_fields,
                    'status' => isset($properties['record_status']) ? $properties['record_status'] : 'incomplete',
                    'missing_fields' => $missing_fields,
                    'review_required' => !empty($review_reasons),
                    'review_reasons' => $review_reasons,
                );
            }
        }
        return null;
    }

    private function has_required_value($value)
    {
        if (is_array($value)) {
            return !empty(array_filter($value, function ($item) {
                return $this->has_required_value($item);
            }));
        }
        if (is_bool($value)) {
            return $value;
        }
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return false;
        }
        if ($normalized[0] === '[' || $normalized[0] === '{') {
            $decoded = json_decode($normalized, true);
            if (is_array($decoded)) {
                return $this->has_required_value($decoded);
            }
        }
        return true;
    }

    private function has_affirmative_consent($value)
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->has_affirmative_consent($item)) {
                    return true;
                }
            }
            return false;
        }
        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on', 'accepted'), true);
    }

    private function has_review_value($value)
    {
        if (is_array($value)) {
            return !empty(array_filter($value, function ($item) {
                return $this->has_review_value($item);
            }));
        }
        $normalized = strtolower(trim((string) $value));
        return !in_array($normalized, array('', 'none', 'no', 'n/a', 'na', 'not applicable'), true);
    }

    private function request($method, $path, array $body, $version)
    {
        $token = $this->config->ghl_token();
        if ($token === '') {
            throw new RuntimeException('GHL is not configured.');
        }
        $args = array(
            'method' => $method,
            'timeout' => 20,
            'redirection' => 0,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Version' => $version,
            ),
        );
        if ($method !== 'GET') {
            $args['body'] = wp_json_encode($body);
        }
        $response = wp_remote_request($this->base_url . $path, $args);
        if (is_wp_error($response)) {
            throw new RuntimeException('GHL request failed: ' . $response->get_error_code());
        }
        $status = wp_remote_retrieve_response_code($response);
        $decoded = json_decode(wp_remote_retrieve_body($response), true);
        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded) && !empty($decoded['message'])
                ? (is_array($decoded['message']) ? implode('; ', $decoded['message']) : $decoded['message'])
                : 'HTTP ' . $status;
            throw new RuntimeException('GHL request rejected: ' . sanitize_text_field($message));
        }
        return is_array($decoded) ? $decoded : array();
    }
}

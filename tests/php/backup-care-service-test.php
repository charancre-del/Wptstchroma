<?php

define('ABSPATH', __DIR__ . '/');
define('ARRAY_A', 'ARRAY_A');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);
define('CHROMA_BACKUP_CARE_GHL_TOKEN', 'ghl_contract_only');

$remote_calls = array();
$fired_actions = array();
$calendar_events = array();
$object_sequence = 0;
$calendar_sequence = 0;
$invoice_status = 'sent';
$fail_paid_action_once = false;

function get_option($key, $default = false)
{
    if ($key === 'chroma_backup_care_mode') { return 'test'; }
    if ($key === 'chroma_backup_care_checkout_enabled') { return true; }
    return $default;
}
function home_url($path = '') { return 'https://staging.example.test' . $path; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function sanitize_email($value) { return filter_var($value, FILTER_SANITIZE_EMAIL); }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function wp_strip_all_tags($value) { return strip_tags((string) $value); }
function wp_json_encode($value) { return json_encode($value); }
function wp_salt($scheme = 'auth') { return 'service-test-salt-' . $scheme; }
function is_wp_error($value) { return false; }
function wp_remote_retrieve_response_code($response) { return $response['response']['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }

function do_action($name, $payload = null)
{
    global $fired_actions, $fail_paid_action_once;
    if ($name === 'chroma_backup_care_order_paid' && $fail_paid_action_once) {
        $fail_paid_action_once = false;
        throw new RuntimeException('Simulated notification transport failure.');
    }
    $fired_actions[] = array('name' => $name, 'payload' => $payload);
}

function complete_child_properties($child_record_key)
{
    return array(
        'child_record_key' => $child_record_key,
        'first_name' => 'Test',
        'last_name' => 'Child',
        'date_of_birth' => '2022-01-01',
        'age_group' => 'preschool',
        'home_address' => '100 Test Street',
        'sex' => 'female',
        'authorized_pickups' => 'Test Parent',
        'emergency_contact' => 'Test Contact, 555-555-0100',
        'healthcare_provider' => 'Test Pediatrics',
        'allergies_and_limitations' => 'None',
        'medications_and_procedures' => 'None',
        'requested_accommodations' => 'None',
        'emergency_medical_authorization' => true,
        'immunization_record' => array('https://files.example.test/immunization.pdf'),
        'record_status' => 'complete',
    );
}

function fake_response($status, array $body)
{
    return array('response' => array('code' => $status), 'body' => json_encode($body));
}

function wp_remote_request($url, array $args)
{
    global $remote_calls, $calendar_events, $object_sequence, $calendar_sequence, $invoice_status;
    $remote_calls[] = array('url' => $url, 'args' => $args);
    if (substr($url, -10) === '/invoices/') {
        return fake_response(200, array('_id' => 'inv_service_matrix', 'status' => 'draft', 'liveMode' => false));
    }
    if (strpos($url, '/invoices/inv_service_matrix/send') !== false) {
        return fake_response(200, array('invoice' => array('_id' => 'inv_service_matrix', 'status' => 'sent', 'liveMode' => false)));
    }
    if (strpos($url, '/invoices/inv_service_matrix?') !== false) {
        return fake_response(200, array(
            '_id' => 'inv_service_matrix',
            'status' => $invoice_status,
            'liveMode' => false,
            'amountPaid' => $invoice_status === 'paid' ? 460 : 0,
            'total' => 460,
            'currency' => 'USD',
            'contactDetails' => array('id' => 'contact_service_test'),
        ));
    }
    if (strpos($url, '/calendars/events?') !== false) {
        return fake_response(200, array('events' => array_values($calendar_events)));
    }
    if (strpos($url, '/calendars/events/appointments') !== false) {
        $payload = !empty($args['body']) ? json_decode($args['body'], true) : array();
        if ($args['method'] === 'POST') {
            $calendar_sequence++;
            $payload['id'] = 'calendar_event_' . $calendar_sequence;
            $calendar_events[$payload['id']] = $payload;
            return fake_response(201, $payload);
        }
        $event_id = basename(parse_url($url, PHP_URL_PATH));
        $payload['id'] = $event_id;
        $calendar_events[$event_id] = $payload;
        return fake_response(200, array('event' => $payload));
    }
    if (strpos($url, '/calendars/events/') !== false && $args['method'] === 'DELETE') {
        unset($calendar_events[basename(parse_url($url, PHP_URL_PATH))]);
        return fake_response(200, array('succeeded' => true));
    }
    if (strpos($url, '/contacts/search/duplicate') !== false || strpos($url, '/contacts/contact_service_test') !== false) {
        return fake_response(200, array('contact' => array(
            'id' => 'contact_service_test',
            'email' => 'parent@example.test',
            'emailLowerCase' => 'parent@example.test',
        )));
    }
    if (strpos($url, '/records/search') !== false) {
        $body = json_decode($args['body'], true);
        $query = isset($body['query']) ? $body['query'] : '';
        if (strpos($url, 'backup_care_child') !== false && strpos($query, 'bcc_') === 0) {
            return fake_response(200, array('customObjectRecords' => array(array(
                'id' => 'ghl_child_' . substr($query, -8),
                'properties' => complete_child_properties($query),
            ))));
        }
        return fake_response(200, array('customObjectRecords' => array()));
    }
    if (strpos($url, '/objects/') !== false && substr($url, -8) === '/records') {
        $object_sequence++;
        return fake_response(201, array('record' => array('id' => 'ghl_record_' . $object_sequence)));
    }
    if (strpos($url, '/associations/relations/') !== false) {
        return fake_response(200, array('relations' => array(array(
            'associationId' => '6a85d7bed4a282e9de959057',
            'firstRecordId' => 'contact_service_test',
            'secondRecordId' => basename(parse_url($url, PHP_URL_PATH)),
        ))));
    }
    if (strpos($url, '/associations/relations') !== false) {
        return fake_response(201, array('relation' => array('id' => 'relation_test')));
    }
    return fake_response(200, array('record' => array('id' => 'ghl_record_existing')));
}

function expect_true($condition, $message)
{
    if (!$condition) { throw new RuntimeException($message); }
}

final class Backup_Care_Test_Parent_Access
{
    public function assert_token($token, $email)
    {
        if ($token !== 'verified_parent_token' || strtolower($email) !== 'parent@example.test') {
            throw new DomainException('Verify the parent email before reviewing the booking.');
        }
        return true;
    }
}

final class Backup_Care_Service_Wpdb
{
    public $prefix = 'wp_';
    public $orders = array();
    public $holds = array();

    public function prepare($query, ...$args)
    {
        if (count($args) === 1 && is_array($args[0])) { $args = $args[0]; }
        return array('query' => $query, 'args' => $args);
    }
    public function query($query)
    {
        if (is_array($query) && strpos($query['query'], 'UPDATE wp_chroma_backup_care_holds SET status') !== false) {
            foreach (array_slice($query['args'], 2) as $key) {
                if (isset($this->holds[$key])) { $this->holds[$key]['status'] = $query['args'][0]; }
            }
        }
        return true;
    }
    public function get_row($prepared, $format)
    {
        $query = $prepared['query'];
        $value = isset($prepared['args'][0]) ? $prepared['args'][0] : '';
        if (strpos($query, 'chroma_backup_care_orders') !== false) {
            if (strpos($query, 'request_id =') !== false) { return isset($this->orders[$value]) ? $this->orders[$value] : null; }
            foreach ($this->orders as $order) {
                if (strpos($query, 'ghl_invoice_id =') !== false && $order['ghl_invoice_id'] === $value) { return $order; }
            }
        }
        if (strpos($query, 'chroma_backup_care_holds') !== false) { return isset($this->holds[$value]) ? $this->holds[$value] : null; }
        return null;
    }
    public function get_results($prepared, $format)
    {
        $query = $prepared['query'];
        $args = $prepared['args'];
        if (strpos($query, 'GROUP BY care_date') !== false) { return array(); }
        if (strpos($query, 'ORDER BY care_date') !== false) {
            return array_values(array_filter($this->holds, function ($hold) use ($args) { return $hold['request_id'] === $args[0]; }));
        }
        return array();
    }
    public function get_var($prepared)
    {
        if (strpos($prepared['query'], 'GET_LOCK') !== false || strpos($prepared['query'], 'RELEASE_LOCK') !== false) { return 1; }
        if (strpos($prepared['query'], 'COUNT(*)') !== false && strpos($prepared['query'], 'request_id =') !== false) {
            $request_id = $prepared['args'][0];
            return count(array_filter($this->holds, function ($hold) use ($request_id) { return $hold['request_id'] === $request_id && $hold['status'] === 'confirmed'; }));
        }
        return 0;
    }
    public function insert($table, array $values, array $formats)
    {
        if (strpos($table, 'orders') !== false) {
            $values += array('ghl_invoice_id' => '', 'ghl_invoice_status' => '', 'ghl_payment_transaction_id' => '', 'ghl_order_record_id' => '', 'contact_id' => '');
            $this->orders[$values['request_id']] = $values;
            return 1;
        }
        foreach ($this->holds as $hold) { if ($hold['booking_key'] === $values['booking_key']) { return false; } }
        $this->holds[$values['line_item_key']] = $values;
        return 1;
    }
    public function update($table, array $values, array $where, array $formats, array $where_formats)
    {
        if (strpos($table, 'orders') !== false) {
            $this->orders[$where['request_id']] = array_merge($this->orders[$where['request_id']], $values);
            return 1;
        }
        foreach ($this->holds as $key => $hold) {
            $match = true;
            foreach ($where as $field => $expected) { if (!isset($hold[$field]) || $hold[$field] !== $expected) { $match = false; } }
            if ($match) { $this->holds[$key] = array_merge($hold, $values); }
        }
        return 1;
    }
    public function delete($table, array $where, array $formats) { return 0; }
}

$root = dirname(__DIR__, 2);
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-config.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-domain.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-store.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-ghl-client.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-parent-access.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-service.php';

$wpdb = new Backup_Care_Service_Wpdb();
$config = new Chroma_Backup_Care_Config($root . '/infrastructure/ghl/backup-care/manifest.json');
$store = new Chroma_Backup_Care_Store($wpdb);
$service = new Chroma_Backup_Care_Service(
    $config,
    $store,
    new Chroma_Backup_Care_GHL_Client($config),
    new Backup_Care_Test_Parent_Access()
);

$order = array(
    'contract_version' => 1,
    'parent_access_token' => 'verified_parent_token',
    'client_request_id' => 'bc_service_acceptance_001',
    'campus_id' => 'grayson',
    'parent' => array('first_name' => 'Test', 'last_name' => 'Parent', 'email' => 'parent@example.test', 'mobile_phone' => '4705550100'),
    'children' => array(
        array('client_child_id' => 'child_a', 'first_name' => 'Avery', 'last_name' => 'Test', 'date_of_birth' => '2022-01-01', 'age_group' => 'preschool'),
        array('client_child_id' => 'child_b', 'first_name' => 'Jordan', 'last_name' => 'Test', 'date_of_birth' => '2022-01-01', 'age_group' => 'preschool'),
    ),
    'attendance' => array(
        array('client_child_id' => 'child_a', 'care_date' => '2026-08-24', 'planned_dropoff_local' => '08:00'),
        array('client_child_id' => 'child_a', 'care_date' => '2026-08-25', 'planned_dropoff_local' => '08:00'),
        array('client_child_id' => 'child_b', 'care_date' => '2026-08-24', 'planned_dropoff_local' => '08:00'),
        array('client_child_id' => 'child_b', 'care_date' => '2026-08-25', 'planned_dropoff_local' => '08:00'),
    ),
    'policy_acceptance' => array(
        'backup_care_terms' => true,
        'full_payment' => true,
        'refund_and_reschedule_deadline' => true,
        'no_discretionary_exceptions' => true,
        'privacy_and_communications' => true,
    ),
);

$quote = $service->quote($order, new DateTimeImmutable('2026-08-20 06:00:00', new DateTimeZone('America/New_York')));
expect_true($quote['contract_valid'] && $quote['quote']['total_amount_cents'] === 46000, 'The 2x2 matrix must quote $460.');
$checkout = $service->checkout($order, $quote['quote_token']);
expect_true($checkout['invoice_id'] === 'inv_service_matrix', 'Checkout must create one GHL invoice.');
expect_true($checkout['payment_delivery'] === 'ghl_invoice_email', 'Payment must be delivered by GHL invoice email.');
expect_true(count($wpdb->holds) === 4, 'Checkout must reserve four child-date units.');

$pending = $service->sync_invoice_payment($order['client_request_id']);
expect_true($pending['status'] === 'pending_payment', 'An unpaid GHL invoice must not create appointments.');
expect_true(count($calendar_events) === 0, 'Appointments must wait for full GHL payment.');

$invoice_status = 'paid';
$fail_paid_action_once = true;
$retry_required = false;
try { $service->sync_invoice_payment($order['client_request_id']); } catch (RuntimeException $error) { $retry_required = true; }
expect_true($retry_required, 'A notification failure must leave paid fulfillment retryable.');
expect_true($wpdb->orders[$order['client_request_id']]['status'] === 'paid', 'Paid fulfillment must commit before notification retry.');
expect_true(count($calendar_events) === 4, 'Two children across two dates must create four GHL appointments.');

$paid = $service->sync_invoice_payment($order['client_request_id']);
expect_true($paid['status'] === 'paid', 'Paid GHL invoice reconciliation must complete.');
expect_true($wpdb->orders[$order['client_request_id']]['payload_cipher'] === '', 'Sensitive payload must clear after successful fulfillment.');
expect_true(count(array_filter($wpdb->holds, function ($hold) { return $hold['status'] === 'confirmed'; })) === 4, 'All four units must be confirmed.');

$paid_actions = array_values(array_filter($fired_actions, function ($action) { return $action['name'] === 'chroma_backup_care_order_paid'; }));
expect_true(count($paid_actions) === 1, 'Paid notification must fire exactly once.');
$manage_token = $paid_actions[0]['payload']['manage_token'];
$active_keys = array_keys(array_filter($wpdb->holds, function ($hold) { return $hold['status'] === 'confirmed'; }));
$cancelled = $service->cancel_units(
    $manage_token,
    array($active_keys[0]),
    new DateTimeImmutable('2026-08-20 06:00:00', new DateTimeZone('America/New_York'))
);
expect_true($cancelled['refund_amount_cents'] === 11500, 'One cancelled child-date unit must request a $115 GHL refund.');
expect_true($cancelled['status'] === 'partial_refund_pending', 'A partial cancellation must await GHL Payments refund action.');
expect_true($wpdb->holds[$active_keys[0]]['status'] === 'refund_pending', 'The cancelled unit must be marked refund pending.');
expect_true(count($calendar_events) === 3, 'Cancelling one unit must delete exactly one appointment.');

echo "backup-care-service-test: OK\n";

<?php

define('ABSPATH', __DIR__ . '/');
define('CHROMA_BACKUP_CARE_GHL_TOKEN', 'ghl_contract_only');

$captured_requests = array();
$relation_duplicate = false;

function get_option($key, $default = false)
{
    return $default;
}

function sanitize_email($value)
{
    return filter_var($value, FILTER_SANITIZE_EMAIL);
}

function sanitize_key($value)
{
    return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $value));
}

function sanitize_text_field($value)
{
    return trim(strip_tags((string) $value));
}

function wp_salt($scheme = 'auth')
{
    return 'test-salt-' . $scheme;
}

function home_url($path = '')
{
    return 'https://staging.example.test' . $path;
}

function wp_strip_all_tags($value)
{
    return strip_tags((string) $value);
}

function wp_json_encode($value)
{
    return json_encode($value);
}

function complete_child_properties($child_record_key, $medications = 'None')
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
        'medications_and_procedures' => $medications,
        'requested_accommodations' => 'None',
        'emergency_medical_authorization' => true,
        'immunization_record' => array('https://files.example.test/immunization.pdf'),
        'record_status' => 'complete',
    );
}

function wp_remote_request($url, array $args)
{
    global $captured_requests, $relation_duplicate;
    $captured_requests[] = array('url' => $url, 'args' => $args);

    if (strpos($url, '/contacts/search/duplicate') !== false) {
        return array(
            'response' => array('code' => 200),
            'body' => json_encode(array('contact' => array(
                'id' => 'contact_1',
                'email' => 'parent@example.test',
                'emailLowerCase' => 'parent@example.test',
            ))),
        );
    }

    if (strpos($url, '/contacts/contact_1') !== false) {
        return array(
            'response' => array('code' => 200),
            'body' => json_encode(array('contact' => array(
                'id' => 'contact_1',
                'email' => 'parent@example.test',
            ))),
        );
    }

    if (strpos($url, '/associations/relations/child_1') !== false) {
        return array(
            'response' => array('code' => 200),
            'body' => json_encode(array('relations' => array(array(
                'associationId' => '6a85d7bed4a282e9de959057',
                'firstRecordId' => 'contact_1',
                'secondRecordId' => 'child_1',
            )))),
        );
    }

    if (strpos($url, '/records/search') !== false) {
        $request = json_decode($args['body'], true);
        $query = $request['query'];
        if ($query === 'grayson__2026-09-03') {
            $record = array(
                'id' => 'closure_1',
                'properties' => array(
                    'closure_key' => $query,
                    'campus_id' => 'grayson',
                    'closure_date' => '2026-09-03',
                    'status' => 'active',
                ),
            );
        } elseif ($query === 'all__2026-09-04') {
            $record = array(
                'id' => 'closure_all',
                'properties' => array(
                    'closure_key' => $query,
                    'campus_id' => 'all',
                    'closure_date' => '2026-09-04',
                    'status' => 'active',
                ),
            );
        } elseif ($query === 'bcc_test_child') {
            $record = array(
                'id' => 'child_1',
                'properties' => complete_child_properties($query, 'EpiPen as needed'),
            );
        } elseif ($query === 'bcc_incomplete_child') {
            $properties = complete_child_properties($query);
            unset($properties['immunization_record']);
            $record = array(
                'id' => 'child_2',
                'properties' => $properties,
            );
        } else {
            $record = array(
                'id' => 'record_1',
                'properties' => array('order_id' => $query, 'status' => 'paid'),
            );
        }
        return array(
            'response' => array('code' => 201),
            'body' => json_encode(array('customObjectRecords' => array($record), 'total' => 1)),
        );
    }

    if (strpos($url, '/associations/relations') !== false && $relation_duplicate) {
        return array(
            'response' => array('code' => 400),
            'body' => json_encode(array(
                'message' => 'Max relation limit[1] reached for record[test] for association[test]',
            )),
        );
    }

    return array(
        'response' => array('code' => strpos($url, '/associations/relations') !== false ? 201 : 200),
        'body' => json_encode(array('record' => array('id' => 'record_1'))),
    );
}

function is_wp_error($value)
{
    return false;
}

function wp_remote_retrieve_response_code($response)
{
    return $response['response']['code'];
}

function wp_remote_retrieve_body($response)
{
    return $response['body'];
}

function expect_true($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__, 2);
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-config.php';
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-ghl-client.php';

$config = new Chroma_Backup_Care_Config($root . '/infrastructure/ghl/backup-care/manifest.json');
$client = new Chroma_Backup_Care_GHL_Client($config);

$record = $client->upsert_record(
    'custom_objects.backup_care_order',
    'order_id',
    'BC-TEST-001',
    array('order_id' => 'BC-TEST-001', 'status' => 'paid')
);
expect_true($record['id'] === 'record_1', 'Existing records must be updated instead of duplicated.');
expect_true(count($captured_requests) === 2, 'Upsert must perform one search and one update.');

$search_body = json_decode($captured_requests[0]['args']['body'], true);
expect_true($search_body['query'] === 'BC-TEST-001', 'Search must use the raw searchable value.');
expect_true(
    $captured_requests[0]['args']['headers']['Version'] === 'v3',
    'Custom-object search must use the v3 API contract.'
);
expect_true(
    strpos($captured_requests[1]['url'], '?locationId=' . Chroma_Backup_Care_Config::GHL_LOCATION_ID) !== false,
    'Custom-object update must include locationId in the query string.'
);
expect_true(
    $captured_requests[1]['args']['headers']['Version'] === 'v3',
    'Custom-object update must use the v3 API contract.'
);

$by_id = $client->get_record('custom_objects.backup_care_child', 'child_1');
expect_true($by_id['id'] === 'record_1', 'Record lookup must return the GHL record payload.');
$get_request = $captured_requests[count($captured_requests) - 1];
expect_true($get_request['args']['headers']['Version'] === '2021-04-15', 'Record lookup must use the documented API version.');
expect_true(!isset($get_request['args']['body']), 'GET record lookup must not send a request body.');

$closures = $client->closures(array('2026-09-03'), 'grayson');
expect_true(isset($closures['grayson|2026-09-03']), 'Active closure records must block the campus date.');
$all_closures = $client->closures(array('2026-09-04'), 'grayson');
expect_true(isset($all_closures['all|2026-09-04']), 'Canonical all-campus closures must block every campus.');

$contact = $client->find_contact_by_email('Parent@Example.Test');
expect_true($contact['id'] === 'contact_1', 'Verified parent lookup must require an exact normalized email.');
$contact_readback = $client->get_contact('contact_1');
expect_true($contact_readback['id'] === 'contact_1', 'Verified parent contact readback must use its exact ID.');
expect_true(
    $client->relation_exists('child_1', 'contact_1', '6a85d7bed4a282e9de959057'),
    'Existing child reuse must require the configured parent-to-child relation.'
);

$child = $client->resolve_child_record('bcc_test_child');
expect_true($child['complete'] === true, 'Completed native form records must be bookable.');
expect_true($child['review_required'] === true, 'Medication or special procedure data must trigger review.');
expect_true(
    $child['review_reasons'] === array('medication_or_special_procedure'),
    'Review reasons must not include fields answered with None.'
);

$incomplete_child = $client->resolve_child_record('bcc_incomplete_child');
expect_true(
    $incomplete_child['complete'] === false,
    'A complete status must not bypass missing required enrollment data.'
);
expect_true(
    $incomplete_child['missing_fields'] === array('immunization_record'),
    'The server must identify the missing required enrollment field.'
);

$relation_duplicate = true;
$duplicate = $client->create_relation('association_1', 'first_1', 'second_1');
expect_true(!empty($duplicate['duplicate']), 'A repeated one-to-many relation must be idempotent.');

echo "backup-care-ghl-client-test: OK\n";

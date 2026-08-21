<?php

define('CHROMA_BACKUP_CARE_TESTING', true);
require_once dirname(__DIR__, 2) . '/chroma-plugins/chroma-backup-care/includes/class-domain.php';

$manifest = json_decode(
    file_get_contents(dirname(__DIR__, 2) . '/chroma-plugins/chroma-backup-care/config/backup-care.json'),
    true
);
$domain = new Chroma_Backup_Care_Domain($manifest);
$now = new DateTimeImmutable('2026-08-17 07:00:00', new DateTimeZone('America/New_York'));

function order_fixture()
{
    return array(
        'contract_version' => 1,
        'client_request_id' => 'cart_01K2YWRJAEV0D6KQW56KXQ3N6Z',
        'campus_id' => 'lilburn',
        'parent' => array(
            'first_name' => 'Test',
            'last_name' => 'Parent',
            'email' => 'parent@example.test',
            'mobile_phone' => '+1 404 555 0100',
        ),
        'children' => array(
            array(
                'client_child_id' => 'child_a',
                'child_record_key' => 'bcc_child_a',
                'first_name' => 'Child',
                'last_name' => 'One',
                'date_of_birth' => '2022-03-10',
                'age_group' => 'preschool',
                'enrollment_record_id' => 'record_child_a',
                'enrollment_record_complete' => true,
            ),
            array(
                'client_child_id' => 'child_b',
                'child_record_key' => 'bcc_child_b',
                'first_name' => 'Child',
                'last_name' => 'Two',
                'date_of_birth' => '2020-05-11',
                'age_group' => 'school',
                'enrollment_record_id' => 'record_child_b',
                'enrollment_record_complete' => true,
            ),
        ),
        'attendance' => array(
            array('client_child_id' => 'child_a', 'care_date' => '2026-08-18', 'planned_dropoff_local' => '08:00'),
            array('client_child_id' => 'child_a', 'care_date' => '2026-08-19', 'planned_dropoff_local' => '08:00'),
            array('client_child_id' => 'child_b', 'care_date' => '2026-08-18', 'planned_dropoff_local' => '08:00'),
            array('client_child_id' => 'child_b', 'care_date' => '2026-08-19', 'planned_dropoff_local' => '08:00'),
        ),
        'policy_acceptance' => array(
            'backup_care_terms' => true,
            'full_payment' => true,
            'refund_and_reschedule_deadline' => true,
            'no_discretionary_exceptions' => true,
            'privacy_and_communications' => true,
        ),
    );
}

function expect_true($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function contains_error(array $result, $needle)
{
    foreach ($result['errors'] as $error) {
        if (strpos($error, $needle) !== false) {
            return true;
        }
    }
    return false;
}

$order = order_fixture();
$result = $domain->quote($order, $now, array(), array());
expect_true($result['contract_valid'], 'Expected the multi-child/multi-date order to be valid.');
expect_true($result['payment_creation_allowed'], 'Expected authoritative checks to allow payment.');
expect_true($result['quote']['unit_count'] === 4, 'Expected four child-date units.');
expect_true($result['quote']['total_amount_cents'] === 46000, 'Expected a $460 total.');

$minimum_age = order_fixture();
$minimum_age['children'] = array($minimum_age['children'][0]);
$minimum_age['children'][0]['date_of_birth'] = '2026-07-07';
$minimum_age['children'][0]['age_group'] = 'infant';
$minimum_age['attendance'] = array(
    array('client_child_id' => 'child_a', 'care_date' => '2026-08-18', 'planned_dropoff_local' => '08:00'),
);
expect_true($domain->quote($minimum_age, $now, array(), array())['contract_valid'], 'A child exactly 6 weeks old must be eligible.');
$too_young = $minimum_age;
$too_young['children'][0]['date_of_birth'] = '2026-07-08';
$too_young_result = $domain->quote($too_young, $now, array(), array());
expect_true(!$too_young_result['contract_valid'], 'A child younger than 6 weeks must be rejected.');
expect_true(contains_error($too_young_result, '6 weeks through 12 years'), 'The minimum-age error is missing.');

$maximum_age = $minimum_age;
$maximum_age['children'][0]['date_of_birth'] = '2014-08-18';
$maximum_age['children'][0]['age_group'] = 'school';
expect_true($domain->quote($maximum_age, $now, array(), array())['contract_valid'], 'A child on the 12th birthday must be eligible.');
$thirteen = $maximum_age;
$thirteen['children'][0]['date_of_birth'] = '2013-08-18';
$thirteen_result = $domain->quote($thirteen, $now, array(), array());
expect_true(!$thirteen_result['contract_valid'], 'A child on the 13th birthday must be rejected.');
expect_true(contains_error($thirteen_result, '6 weeks through 12 years'), 'The maximum-age error is missing.');

$closed = $domain->quote($order, $now, array('lilburn|2026-08-18' => true), array());
expect_true(!$closed['contract_valid'], 'Expected a closed date to fail.');
expect_true(contains_error($closed, 'closed for backup care'), 'Expected a closure error.');
$closed_all = $domain->quote($order, $now, array('all|2026-08-18' => true), array());
expect_true(!$closed_all['contract_valid'], 'Expected an all-campus closure to fail.');
expect_true(contains_error($closed_all, 'closed for backup care'), 'Expected an all-campus closure error.');

$capacity = $domain->quote($order, $now, array(), array('lilburn|2026-08-18' => 99));
expect_true(!$capacity['contract_valid'], 'Expected capacity 101 to fail.');
expect_true(contains_error($capacity, 'Capacity is unavailable'), 'Expected a capacity error.');

$duplicate_order = order_fixture();
$duplicate_order['attendance'][] = $duplicate_order['attendance'][0];
$duplicate = $domain->quote($duplicate_order, $now, array(), array());
expect_true(!$duplicate['contract_valid'], 'Expected a duplicate child-date unit to fail.');
expect_true(contains_error($duplicate, 'Duplicate child-date unit'), 'Expected a duplicate error.');

$forged = order_fixture();
$forged['total_amount_cents'] = 1;
$forged_result = $domain->quote($forged, $now, array(), array());
expect_true(!$forged_result['contract_valid'], 'Expected a browser-supplied total to fail.');
expect_true(contains_error($forged_result, 'unsupported fields'), 'Expected an unsupported total error.');

$same_day = order_fixture();
$same_day['attendance'] = array(
    array('client_child_id' => 'child_a', 'care_date' => '2026-08-17', 'planned_dropoff_local' => '08:00'),
    array('client_child_id' => 'child_b', 'care_date' => '2026-08-18', 'planned_dropoff_local' => '08:00'),
);
$late_now = new DateTimeImmutable('2026-08-17 07:31:00', new DateTimeZone('America/New_York'));
$late = $domain->quote($same_day, $late_now, array(), array());
expect_true(!$late['contract_valid'], 'Expected a same-day order after 7:30 AM to fail.');
expect_true(contains_error($late, 'same-day deadline'), 'Expected a same-day deadline error.');

$notice_order = order_fixture();
$notice_order['attendance'] = array(
    array('client_child_id' => 'child_a', 'care_date' => '2026-08-17', 'planned_dropoff_local' => '08:00'),
    array('client_child_id' => 'child_b', 'care_date' => '2026-08-18', 'planned_dropoff_local' => '08:00'),
);
$notice_now = new DateTimeImmutable('2026-08-17 06:15:00', new DateTimeZone('America/New_York'));
$notice = $domain->quote($notice_order, $notice_now, array(), array());
expect_true(!$notice['contract_valid'], 'Expected less than 120 minutes notice to fail.');
expect_true(contains_error($notice, '120 minutes notice'), 'Expected a notice error.');

$missing_dropoff = order_fixture();
unset($missing_dropoff['attendance'][0]['planned_dropoff_local']);
$missing_dropoff_result = $domain->quote($missing_dropoff, $now, array(), array());
expect_true(!$missing_dropoff_result['contract_valid'], 'Every child-date unit must require a planned drop-off time.');
expect_true(
    contains_error($missing_dropoff_result, 'planned_dropoff_local'),
    'Expected a missing planned drop-off error.'
);

$too_many_dates = order_fixture();
$too_many_dates['children'] = array($too_many_dates['children'][0]);
$too_many_dates['attendance'] = array();
$candidate = new DateTimeImmutable('2026-08-18', new DateTimeZone('America/New_York'));
while (count($too_many_dates['attendance']) < 32) {
    if ((int) $candidate->format('N') <= 5) {
        $too_many_dates['attendance'][] = array(
            'client_child_id' => 'child_a',
            'care_date' => $candidate->format('Y-m-d'),
            'planned_dropoff_local' => '08:00',
        );
    }
    $candidate = $candidate->modify('+1 day');
}
$too_many = $domain->quote($too_many_dates, $now, array(), array());
expect_true(!$too_many['contract_valid'], 'Expected more than 31 care dates to fail.');
expect_true(contains_error($too_many, 'at most 31 care dates'), 'Expected a care-date count error.');

$reschedule_errors = $domain->validate_service_date(
    'lilburn',
    '2026-08-20',
    '08:00',
    $now,
    array(),
    100
);
expect_true(in_array('Capacity is unavailable for 2026-08-20', $reschedule_errors, true), 'Expected reschedule capacity enforcement.');

$closed_reschedule = $domain->validate_service_date(
    'lilburn',
    '2026-08-20',
    '08:00',
    $now,
    array('lilburn|2026-08-20' => true),
    0
);
expect_true(in_array('care_date is closed for backup care', $closed_reschedule, true), 'Expected reschedule closure enforcement.');

echo "backup-care-domain-test: OK\n";

<?php

define('ABSPATH', __DIR__ . '/');
define('ARRAY_A', 'ARRAY_A');
define('MINUTE_IN_SECONDS', 60);

function wp_json_encode($value)
{
    return json_encode($value);
}

function expect_true($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

final class Backup_Care_Fake_Wpdb
{
    public $prefix = 'wp_';
    public $orders = array();
    public $holds = array();
    public $lock_calls = array();
    public $rate_limits = array();

    public function prepare($query, ...$args)
    {
        if (count($args) === 1 && is_array($args[0])) {
            $args = $args[0];
        }
        return array('query' => $query, 'args' => $args);
    }

    public function query($query)
    {
        return true;
    }

    public function get_row($prepared, $format)
    {
        if (is_array($prepared) && strpos($prepared['query'], 'chroma_backup_care_orders') !== false) {
            $request_id = $prepared['args'][0];
            return isset($this->orders[$request_id]) ? $this->orders[$request_id] : null;
        }
        if (is_array($prepared) && strpos($prepared['query'], 'chroma_backup_care_rate_limits') !== false) {
            $bucket_key = $prepared['args'][0];
            return isset($this->rate_limits[$bucket_key]) ? $this->rate_limits[$bucket_key] : null;
        }
        return null;
    }

    public function get_var($prepared)
    {
        if (!is_array($prepared)) {
            return 0;
        }
        if (strpos($prepared['query'], 'GET_LOCK') !== false || strpos($prepared['query'], 'RELEASE_LOCK') !== false) {
            $this->lock_calls[] = array($prepared['query'], $prepared['args'][0]);
            return 1;
        }
        if (strpos($prepared['query'], 'COUNT(*)') !== false && count($prepared['args']) >= 2) {
            $campus_id = $prepared['args'][0];
            $care_date = $prepared['args'][1];
            return count(array_filter($this->holds, function ($hold) use ($campus_id, $care_date) {
                return $hold['campus_id'] === $campus_id
                    && $hold['care_date'] === $care_date
                    && in_array($hold['status'], array('held', 'confirmed'), true);
            }));
        }
        return 0;
    }

    public function insert($table, array $values, array $formats)
    {
        if (strpos($table, 'orders') !== false) {
            $this->orders[$values['request_id']] = $values;
            return 1;
        }
        foreach ($this->holds as $hold) {
            if ($hold['booking_key'] === $values['booking_key']) {
                return false;
            }
        }
        $this->holds[$values['line_item_key']] = $values;
        return 1;
    }

    public function update($table, array $values, array $where, array $formats, array $where_formats)
    {
        if (strpos($table, 'rate_limits') !== false) {
            $key = $where['bucket_key'];
            $this->rate_limits[$key] = array_merge($this->rate_limits[$key], $values);
        }
        return 1;
    }

    public function replace($table, array $values, array $formats)
    {
        if (strpos($table, 'rate_limits') !== false) {
            $this->rate_limits[$values['bucket_key']] = $values;
            return 1;
        }
        return false;
    }

    public function delete($table, array $where, array $formats)
    {
        return 0;
    }
}

$root = dirname(__DIR__, 2);
require_once $root . '/chroma-plugins/chroma-backup-care/includes/class-store.php';

$wpdb = new Backup_Care_Fake_Wpdb();
$store = new Chroma_Backup_Care_Store($wpdb);
$quote = array(
    'total_amount_cents' => 11500,
    'line_items' => array(array(
        'line_item_key' => 'bcr_line_one',
        'client_child_id' => 'browser_child_a',
        'child_record_id' => 'ghl_child_stable_1',
        'care_date' => '2026-09-01',
        'planned_dropoff_local' => '08:00',
    )),
);
$store->reserve('request_one', hash('sha256', 'one'), 'cipher', $quote, 'grayson', 100, '2026-09-01 12:00:00');

$expected_booking_key = hash('sha256', 'grayson|ghl_child_stable_1|2026-09-01');
expect_true($wpdb->holds['bcr_line_one']['booking_key'] === $expected_booking_key, 'Booking key must use the stable GHL child ID.');
expect_true($wpdb->orders['request_one']['payment_provider'] === 'ghl_invoice', 'Order insert must use the GHL invoice provider.');
expect_true($wpdb->orders['request_one']['ghl_invoice_id'] === '', 'Order insert must initialize the GHL invoice ID.');

$duplicate_quote = $quote;
$duplicate_quote['line_items'][0]['line_item_key'] = 'bcr_line_two';
$duplicate_quote['line_items'][0]['client_child_id'] = 'different_browser_id';
$duplicate_rejected = false;
try {
    $store->reserve('request_two', hash('sha256', 'two'), 'cipher', $duplicate_quote, 'grayson', 100, '2026-09-01 12:00:00');
} catch (DomainException $error) {
    $duplicate_rejected = true;
}
expect_true($duplicate_rejected, 'A new browser child ID must not bypass duplicate child-date protection.');

$lock_result = $store->with_order_lock('request_one', function () {
    return 'locked-result';
});
expect_true($lock_result === 'locked-result', 'Order lock must return the callback result.');
expect_true(count($wpdb->lock_calls) >= 2, 'Order lock must be acquired and released.');
$last_two_lock_calls = array_slice($wpdb->lock_calls, -2);
expect_true(strpos($last_two_lock_calls[0][0], 'GET_LOCK') !== false, 'Order lock must be acquired first.');
expect_true(strpos($last_two_lock_calls[1][0], 'RELEASE_LOCK') !== false, 'Order lock must be released last.');
expect_true($last_two_lock_calls[0][1] === $last_two_lock_calls[1][1], 'Order lock must release the acquired name.');
expect_true(strlen($last_two_lock_calls[0][1]) <= 64, 'Order lock name must fit the MySQL limit.');

$failure_released = false;
try {
    $store->with_order_lock('request_failure', function () {
        throw new RuntimeException('expected callback failure');
    });
} catch (RuntimeException $error) {
    $failure_released = $error->getMessage() === 'expected callback failure';
}
expect_true($failure_released, 'Order lock must preserve callback failures.');
$failure_lock_calls = array_slice($wpdb->lock_calls, -2);
expect_true(strpos($failure_lock_calls[1][0], 'RELEASE_LOCK') !== false, 'Order lock must release after a callback failure.');
expect_true($failure_lock_calls[0][1] === $failure_lock_calls[1][1], 'Failed callbacks must release the acquired lock.');

expect_true($store->consume_rate_limit('quote|test-ip', 2, 600, 1000), 'The first request must pass.');
expect_true($store->consume_rate_limit('quote|test-ip', 2, 600, 1001), 'The second request must pass.');
expect_true(!$store->consume_rate_limit('quote|test-ip', 2, 600, 1002), 'The request above the limit must fail.');
expect_true($store->consume_rate_limit('quote|test-ip', 2, 600, 1600), 'An expired window must reset atomically.');
$rate_lock_calls = array_values(array_filter($wpdb->lock_calls, function ($call) {
    return strpos($call[1], 'chroma_bc_rl_') === 0;
}));
expect_true(count($rate_lock_calls) === 8, 'Every rate-limit decision must acquire and release one database lock.');

echo "backup-care-store-test: OK\n";

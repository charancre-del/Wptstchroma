<?php

use ChromaAgentAPI\Route_Utils;

if (!defined('ABSPATH')) {
    exit;
}

$cases = [
    'standard_google_embed' => [
        'key' => 'location_maps_embed',
        'value' => '<iframe src="https://www.google.com/maps/embed?pb=test" width="600" height="450" style="border:0" loading="lazy"></iframe>',
        'allowed' => true,
    ],
    'legacy_google_cid_embed' => [
        'key' => 'location_maps_embed',
        'value' => '<iframe src="https://maps.google.com/maps?cid=123456789&amp;output=embed" width="600" height="450"></iframe>',
        'allowed' => true,
    ],
    'non_google_host' => [
        'key' => 'location_maps_embed',
        'value' => '<iframe src="https://example.com/maps/embed"></iframe>',
        'allowed' => false,
    ],
    'unsafe_protocol' => [
        'key' => 'location_maps_embed',
        'value' => '<iframe src="javascript:alert(1)"></iframe>',
        'allowed' => false,
    ],
    'multiple_iframes' => [
        'key' => 'location_maps_embed',
        'value' => '<iframe src="https://www.google.com/maps/embed?pb=one"></iframe><iframe src="https://www.google.com/maps/embed?pb=two"></iframe>',
        'allowed' => false,
    ],
    'unrelated_embed_field' => [
        'key' => 'location_virtual_tour_embed',
        'value' => '<iframe src="https://www.google.com/maps/embed?pb=test"></iframe>',
        'allowed' => false,
    ],
];

$failed = [];

foreach ($cases as $name => $case) {
    $result = Route_Utils::sanitize_value_for_storage($case['key'], $case['value']);
    $passed = $case['allowed']
        ? strpos($result, '<iframe') !== false
        : trim($result) === '';

    echo wp_json_encode([
        'case' => $name,
        'passed' => $passed,
        'result' => $result,
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL;

    if (!$passed) {
        $failed[] = $name;
    }
}

if ($failed) {
    throw new RuntimeException('Location Maps embed sanitizer cases failed: ' . implode(', ', $failed));
}


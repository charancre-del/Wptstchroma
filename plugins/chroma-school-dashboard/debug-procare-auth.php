<?php
// Debug ProCare Auth - Internal API Hunt
// Run with: php debug-procare-auth.php

// Credentials provided by user
$username = 'charancre@gmail.com';
$password = 'Movado12!';

$endpoints = [
    'https://schools.procareconnect.com/api/v1/authentication/login',
    'https://api-school.procareconnect.com/api/v1/authentication/login',
    'https://api-school.procareconnect.com/api/v1/login',
    'https://schools.procareconnect.com/api/v1/login',
];

echo "Testing ProCare Login for user: $username\n";
echo "---------------------------------------------------\n";

foreach ($endpoints as $url) {
    echo "Trying: $url ... ";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'email' => $username, // Try 'email' first
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    ]);

    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "[$code]\n";

    if ($code === 200) {
        echo "SUCCESS! Response:\n";
        $data = json_decode($result, true);
        print_r($data);
        
        // If success, try to find school ID
        if (isset($data['token']) || isset($data['authToken'])) {
            $token = $data['token'] ?? $data['authToken'];
            echo "\nToken found! Attempting to fetch profile/schools...\n";
            // Try fetching profile
            // Guessing profile endpoint based on common patterns
            // Often it's passed in login response, check output first.
        }
        exit; // Stop on first success
    } elseif ($code === 401) {
        echo "Endpoint valid but credentials rejected (or wrong field name 'email' vs 'username').\n";
        // Retry with 'username' just in case
    } else {
        echo "Failed. Response snippet: " . substr($result, 0, 100) . "\n";
    }
    echo "---------------------------------------------------\n";
}

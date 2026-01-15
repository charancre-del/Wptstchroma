<?php
/**
 * Diagnostic script to check LLM connectivity and configuration
 */
require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    die('Unauthorized');
}

echo "<h1>LLM Diagnostic</h1>";

$api_key = get_option('chroma_openai_api_key', '');
$model = get_option('chroma_llm_model', 'gpt-4o-mini');
$base_url = get_option('chroma_llm_base_url', 'https://api.openai.com/v1');

echo "<p><strong>Model:</strong> $model</p>";
echo "<p><strong>Base URL:</strong> $base_url</p>";
echo "<p><strong>API Key Length:</strong> " . strlen($api_key) . "</p>";

if (empty($api_key)) {
    echo "<p style='color:red;'>ERROR: No API Key found.</p>";
}

$client = new Chroma_LLM_Client();
echo "<h2>Testing Connection...</h2>";

$response = $client->make_request([
    'messages' => [
        ['role' => 'user', 'content' => 'Hello diagnostic. Respond with "Ready".']
    ]
]);

if (is_wp_error($response)) {
    echo "<p style='color:red;'><strong>Connection Failed:</strong> " . $response->get_error_message() . "</p>";
} else {
    echo "<p style='color:green;'><strong>Connection Successful!</strong></p>";
    echo "<pre>" . print_r($response['choices'][0]['message']['content'], true) . "</pre>";
}

echo "<h2>Translation Memory Check</h2>";
$fields = ['test' => 'Hello World'];
$content_hash = md5(json_encode($fields) . 'es');
$cache_key = 'chroma_trans_' . $content_hash;
$cached = get_transient($cache_key);

if ($cached) {
    echo "<p>Found cached translation for 'Hello World'.</p>";
} else {
    echo "<p>No cached translation found.</p>";
}

<?php
require_once('wp-load.php');
$url = "https://app.acquire4hire.com/feed/indeed.xml?id=4668";
$response = wp_remote_get($url);
if (is_wp_error($response)) {
    echo "Error: " . $response->get_error_message();
} else {
    echo wp_remote_retrieve_body($response);
}
?>

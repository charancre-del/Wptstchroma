<?php
$url = "https://app.acquire4hire.com/feed/indeed.xml?id=4668";
$response = file_get_contents($url);
echo $response;
?>

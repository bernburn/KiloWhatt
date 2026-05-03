<?php
// test_ai.php
require_once 'api/db.php'; // This has the .env loader
$apiKey = getenv('GEMINI_API_KEY');

echo "API Key loaded: " . ($apiKey ? "YES (starts with " . substr($apiKey, 0, 5) . ")" : "NO") . "\n";

$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite-preview:generateContent?key=" . $apiKey;
$payload = [
    "contents" => [["parts" => [["text" => "Say hello"]]]]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) echo "CURL Error: $error\n";
echo "Response: $response\n";

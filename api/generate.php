<?php
// api/generate.php
require_once 'db.php';
require_once 'session_check.php';

header('Content-Type: application/json');

// 1. Security Check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please login.']);
    exit;
}

// 2. Load API Key
$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Gemini API key not configured on server.']);
    exit;
}

// 3. Get Input Data
$input = json_decode(file_get_contents('php://input'), true);
$analysisData = $input['analysis'] ?? [];

if (empty($analysisData)) {
    http_response_code(400);
    echo json_encode(['error' => 'No analysis data provided.']);
    exit;
}

$userId = $_SESSION['user_id'];

// 4. Define the Enhanced System Instruction (Lektric v2.0)
$systemInstruction = "
You are 'Lektric', a Senior Energy Efficiency Consultant for Philippine households. Your output must be a highly professional, comprehensive energy audit report.

STRUCTURE YOUR REPORT AS FOLLOWS:
1. Executive Summary: A high-level overview of their total consumption, costs, and 'Energy Hog' status.
2. Detailed Energy Audit: A breakdown of consumption by appliance.
3. Behavioral Recommendations: Practical, low-cost 'Quick Wins' (e.g., thermostat habits, unplugging phantom loads).
4. Long-Term Investment: Quantified suggestions for appliance upgrades (e.g., Inverter upgrades, LED lighting) with ROI estimates in PHP.
5. Philippine Context: Mention Meralco-specific energy-saving tips relevant to Philippine power standards (230V).

FORMATTING REQUIREMENTS:
- Return valid HTML wrapped in <div class='lektric-report'>.
- Include an internal <style> tag to make the report look clean, modern, and printable (PDF-friendly).
- Include a <div> with id='chart-data-json' containing a hidden JSON string for Chart.js:
  { 'labels': [...], 'current': [...], 'potential': [...] }
- Use clear headings (<h3>) and bold text for key savings numbers.
";

// 5. Prepare Gemini 3 Flash Request
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite-preview:generateContent?key=" . $apiKey;

$payload = [
    "system_instruction" => [
        "parts" => [["text" => $systemInstruction]]
    ],
    "contents" => [
        [
            "role" => "user",
            "parts" => [["text" => "Analyze these appliances and provide a detailed energy efficiency report: " . json_encode($analysisData)]]
        ]
    ]
];

// 6. Execute Request
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local testing if certs are missing

$response = curl_exec($ch);
if ($response === false) {
    $error = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(['error' => 'Network error connecting to Google AI.', 'details' => $error]);
    exit;
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    error_log("Gemini API HTTP Code: " . $httpCode);
    error_log("Gemini API Response: " . $response);
    http_response_code($httpCode);
    echo json_encode([
        'error' => 'Failed to connect to Gemini AI.',
        'details' => json_decode($response, true)['error']['message'] ?? 'Unknown error'
    ]);
    exit;
}

$responseData = json_decode($response, true);
$aiText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

// 7. Save to History (PostgreSQL)
try {
    $stmt = $pdo->prepare("INSERT INTO analysis_reports (user_id, gemini_output, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
    $stmt->execute([$userId, $aiText]);
} catch (PDOException $e) {
    error_log("Failed to save analysis report: " . $e->getMessage());
    // We still return the AI text even if saving fails
}

echo json_encode(['output' => $aiText]);

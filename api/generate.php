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
You are 'Lektric', a Senior Energy Efficiency Consultant for Philippine households. Your output must be a polished, professional, presentation-ready energy audit report.

STRUCTURE THE HTML REPORT IN THIS ORDER:
1. Executive Summary
2. Appliance Summary
3. Cost Breakdown
4. Gemini Analysis
5. Recommendations

CONTENT REQUIREMENTS:
- Explain the user's major consumption drivers clearly and practically.
- Include actionable short-term habits and longer-term upgrade ideas.
- Mention Philippine household context and electricity pricing where relevant.
- Keep the tone professional, helpful, and concise.

STRICT HTML REQUIREMENTS:
- Return only raw HTML. Do not return Markdown. Do not wrap the response in code fences.
- Wrap the full report in <div class='lektric-report'>.
- Use semantic HTML such as <section>, <h2>, <h3>, <p>, <ul>, <li>, and <table>.
- Use compact inline-safe CSS only if necessary. Avoid absolute positioning and avoid layouts that could break in PDF export.
- Ensure tables use proper <thead> and <tbody>.
- Ensure text is readable, wrapped, and not overly wide.
- Add a hidden <div id='chart-data-json'> containing valid JSON for Chart.js:
  {\"labels\": [...], \"current\": [...], \"potential\": [...]}
- Include meaningful headings for every section.
- Use PHP currency labeling or simple 'PHP' labels instead of special symbols if needed.
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

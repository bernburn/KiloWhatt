<?php
// api/save_bill.php
require_once 'db.php';
require_once 'session_check.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$kwh = floatval($input['total_kwh'] ?? 0);
$amount = floatval($input['total_amount'] ?? 0);
$userId = $_SESSION['user_id'];

if ($kwh <= 0 || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid bill data.']);
    exit;
}

$computedRate = $amount / $kwh;

try {
    $stmt = $pdo->prepare("INSERT INTO user_bills (user_id, total_kwh, total_amount, computed_rate) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $kwh, $amount, $computedRate]);

    echo json_encode([
        'message' => 'Bill saved successfully!',
        'computed_rate' => $computedRate
    ]);
} catch (PDOException $e) {
    error_log("Save bill error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while saving the bill.']);
}

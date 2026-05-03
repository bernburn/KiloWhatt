<?php
// api/admin/add_preset.php
require_once '../db.php';
require_once '../session_check.php';

header('Content-Type: application/json');
requireAdmin();

$input = json_decode(file_get_contents('php://input'), true);
$name = trim($input['name'] ?? '');
$category = trim($input['category'] ?? '');
$watts = floatval($input['watts'] ?? 0);
$behavior = floatval($input['behavior'] ?? 100);

if (empty($name) || $watts <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO appliance_presets (name, category, default_watts, default_usage_behavior) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $category, $watts, $behavior]);
    echo json_encode(['message' => 'Preset added successfully!']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}

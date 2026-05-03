<?php
// api/admin/update_preset.php
require_once '../db.php';
require_once '../session_check.php';

header('Content-Type: application/json');
requireAdmin();

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);
$name = trim($input['name'] ?? '');
$category = trim($input['category'] ?? '');
$watts = floatval($input['watts'] ?? 0);
$behavior = floatval($input['behavior'] ?? 100);

if (!$id || empty($name) || $watts <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE appliance_presets SET name = ?, category = ?, default_watts = ?, default_usage_behavior = ? WHERE id = ?");
    $stmt->execute([$name, $category, $watts, $behavior, $id]);
    echo json_encode(['message' => 'Preset updated successfully!']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}

<?php
// api/admin/delete_preset.php
require_once '../db.php';
require_once '../session_check.php';

header('Content-Type: application/json');
requireAdmin();

$input = json_decode(file_get_contents('php://input'), true);
$id = intval($input['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM appliance_presets WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['message' => 'Preset deleted successfully!']);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error.']);
}

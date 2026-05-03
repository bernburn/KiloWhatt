<?php
// api/admin/update_role.php
require_once '../db.php';
require_once '../session_check.php';

header('Content-Type: application/json');
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$targetUserId = intval($input['user_id'] ?? 0);
$newRole = trim($input['role'] ?? '');

// Validation
if (!$targetUserId || !in_array($newRole, ['user', 'admin'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data.']);
    exit;
}

// Prevent self-demotion (security best practice)
if ($targetUserId === $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'You cannot change your own role.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $targetUserId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['message' => 'User role updated successfully!']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found.']);
    }
} catch (PDOException $e) {
    error_log("Update role error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while updating the role.']);
}

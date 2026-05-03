<?php
// api/user_appliances.php
require_once 'db.php';
require_once 'session_check.php';

header('Content-Type: application/json');

// 1. Security Check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please login.']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT id, custom_name, watts, hours_per_day, usage_behavior_percent FROM user_appliances WHERE user_id = ? ORDER BY created_at ASC");
        $stmt->execute([$userId]);
        echo json_encode($stmt->fetchAll());
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validation
        $name = trim($input['name'] ?? 'New Appliance');
        $watts = floatval($input['watts'] ?? 0);
        $hours = floatval($input['hoursUsed'] ?? 0);
        $behavior = floatval($input['usageBehaviorPercent'] ?? 100);

        if ($watts < 0 || $hours < 0 || $hours > 24) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid appliance data provided.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO user_appliances (user_id, custom_name, watts, hours_per_day, usage_behavior_percent) VALUES (?, ?, ?, ?, ?) RETURNING id");
        $stmt->execute([$userId, $name, $watts, $hours, $behavior]);
        $newId = $stmt->fetchColumn();

        echo json_encode(['success' => true, 'id' => $newId]);
    }
    elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = intval($input['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'No appliance ID provided.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM user_appliances WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Appliance not found.']);
        }
    }
    else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (PDOException $e) {
    error_log("Database Error in user_appliances: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'A server-side database error occurred.', 'details' => $e->getMessage()]);
}

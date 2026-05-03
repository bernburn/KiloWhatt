<?php
// api/get_presets.php
require_once 'db.php';
require_once 'session_check.php';

header('Content-Type: application/json');

// Security: Only logged in users can fetch presets
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM appliance_presets ORDER BY name ASC");
    $presets = $stmt->fetchAll();
    echo json_encode($presets);
} catch (PDOException $e) {
    error_log("Fetch presets error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not load appliances.']);
}

<?php
// api/register.php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiJsonResponse(405, ['success' => false, 'error' => 'Method Not Allowed']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    apiJsonResponse(400, ['success' => false, 'error' => 'Invalid JSON request body.']);
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {
    apiJsonResponse(400, ['success' => false, 'error' => 'All fields are required.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiJsonResponse(400, ['success' => false, 'error' => 'Invalid email format.']);
}

if (strlen($password) < 8) {
    apiJsonResponse(400, ['success' => false, 'error' => 'Password must be at least 8 characters long.']);
}

try {
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        apiJsonResponse(409, ['success' => false, 'error' => 'Email already registered.']);
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
    $stmt->execute([$name, $email, $hashedPassword]);

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! You can now login.'
    ]);
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    apiJsonResponse(500, [
        'success' => false,
        'error' => 'An error occurred during registration.'
    ]);
}

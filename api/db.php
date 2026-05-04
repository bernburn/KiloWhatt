<?php
// api/db.php
// Shared database bootstrap for the plain PHP API endpoints.

if (!function_exists('apiJsonResponse')) {
    function apiJsonResponse(int $statusCode, array $payload): never
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        http_response_code($statusCode);
        echo json_encode($payload);
        exit;
    }
}

// Secure environment loading
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        if ($trimmedLine === '' || strpos($trimmedLine, '#') === 0) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '5432';
$dbname = $_ENV['DB_NAME'] ?? 'db_kilowhatt';
$user = $_ENV['DB_USER'] ?? 'postgres';
$pass = $_ENV['DB_PASS'] ?? '';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());

    apiJsonResponse(503, [
        'success' => false,
        'error' => "Database connection failed: PostgreSQL is not reachable at {$host}:{$port}."
    ]);
}

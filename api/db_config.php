<?php
/**
 * EJR Garage — Database Configuration
 * Uses Render Environment Variables
 */

// ──────────────────────────────────────────────────────────────
// getallheaders() polyfill
// ──────────────────────────────────────────────────────────────
if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headers[str_replace('_', '-', $key)] = $value;
            }
        }

        return $headers;
    }
}

// ──────────────────────────────────────────────────────────────
// MySQLi Error Reporting
// ──────────────────────────────────────────────────────────────
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ──────────────────────────────────────────────────────────────
// Load Environment Variables
// ──────────────────────────────────────────────────────────────
$host = getenv('DB_HOST');
$port = (int) getenv('DB_PORT');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// Check for missing environment variables
if (
    empty($host) ||
    empty($port) ||
    empty($db) ||
    empty($user)
) {
    http_response_code(500);
    header('Content-Type: application/json');

    echo json_encode([
        "status" => "error",
        "message" => "Database environment variables are missing."
    ]);

    exit;
}

// ──────────────────────────────────────────────────────────────
// Connect to Database
// ──────────────────────────────────────────────────────────────
try {

    $conn = new mysqli(
        $host,
        $user,
        $pass,
        $db,
        $port
    );

    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+08:00'");

} catch (mysqli_sql_exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

    exit;
}
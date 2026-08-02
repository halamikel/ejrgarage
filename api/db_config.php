<?php
/**
 * EJR Garage — Database Configuration
 *
 * Credentials are loaded from env.php (not hardcoded here).
 * See env.php for rotation instructions and .htaccess for access blocking.
 */
require_once __DIR__ . '/env.php';

// ── getallheaders() polyfill ──────────────────────────────────
// InfinityFree runs PHP-FPM/CGI where getallheaders() doesn't exist.
// This polyfill reads from $_SERVER, which is always available.
if (!function_exists('getallheaders')) {
    function getallheaders(): array {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headers[str_replace('_', '-', $key)] = $value;
            }
        }
        return $headers;
    }
}

// ── MySQLi strict error reporting (PHP 8.1+) ─────────────────
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = @new mysqli(DB_CONNECTION, DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    $conn->query("SET time_zone = '+08:00'");
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("EJR Garage DB Error: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed. Please try again later."]);
    exit;
}

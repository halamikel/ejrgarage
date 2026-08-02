<?php
/**
 * EJR Garage — Database Configuration
 * Render + TiDB Cloud Starter (SSL)
 */

// ----------------------------------------------------------------
// getallheaders() polyfill
// ----------------------------------------------------------------
if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headers[str_replace('_', '-', $key)] = $value;
            }
        }

        return $headers;
    }
}

// ----------------------------------------------------------------
// Enable MySQLi Exceptions
// ----------------------------------------------------------------
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ----------------------------------------------------------------
// Load Render Environment Variables
// ----------------------------------------------------------------
$host = getenv('DB_HOST');
$port = (int)getenv('DB_PORT');
$db   = getenv('DB_DATABASE');
$user = getenv('DB_USERNAME');
$pass = getenv('DB_PASSWORD');

// ----------------------------------------------------------------
// Validate Environment Variables
// ----------------------------------------------------------------
if (
    empty($host) ||
    empty($port) ||
    empty($db) ||
    empty($user) ||
    empty($pass)
) {
    http_response_code(500);
    header('Content-Type: application/json');

    echo json_encode([
        "status" => "error",
        "message" => "Database environment variables are missing."
    ]);

    exit;
}

// ----------------------------------------------------------------
// Connect to TiDB Cloud
// ----------------------------------------------------------------
try {

    $cert = __DIR__ . '/../certificates/isrgrootx1.pem';

    if (!file_exists($cert)) {
        throw new Exception("Certificate not found: " . $cert);
    }

    $conn = mysqli_init();

    mysqli_ssl_set(
        $conn,
        null,
        null,
        $cert,
        null,
        null
    );

    if (!mysqli_real_connect(
        $conn,
        $host,
        $user,
        $pass,
        $db,
        $port,
        null,
        MYSQLI_CLIENT_SSL
    )) {
        throw new Exception(mysqli_connect_error());
    }

    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+08:00'");

} catch (Throwable $e) {

    header('Content-Type: application/json');
    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

    exit;
}
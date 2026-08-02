<?php
/**
 * EJR Garage — Database Configuration
 * Render + TiDB Cloud Starter (SSL)
 */

require_once __DIR__ . '/env.php';

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
// Validate Required Constants
// ----------------------------------------------------------------
$required = [
    'DB_HOST'     => DB_HOST,
    'DB_PORT'     => DB_PORT,
    'DB_DATABASE' => DB_DATABASE,
    'DB_USERNAME' => DB_USERNAME,
    'DB_PASSWORD' => DB_PASSWORD,
];

foreach ($required as $key => $value) {
    if ($value === null || $value === '') {
        http_response_code(500);
        header('Content-Type: application/json');

        echo json_encode([
            "status" => "error",
            "message" => "Missing environment variable: {$key}"
        ]);

        exit;
    }
}

// ----------------------------------------------------------------
// Connect to TiDB Cloud using SSL
// ----------------------------------------------------------------
try {

    $cert = __DIR__ . '/../certificates/isrgrootx1.pem';

    if (!file_exists($cert)) {
        throw new Exception("CA certificate not found: {$cert}");
    }

    $conn = mysqli_init();

    if (!$conn) {
        throw new Exception("Unable to initialize MySQLi.");
    }

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
        DB_HOST,
        DB_USERNAME,
        DB_PASSWORD,
        DB_DATABASE,
        (int) DB_PORT,
        null,
        MYSQLI_CLIENT_SSL
    )) {
        throw new Exception(mysqli_connect_error());
    }

    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+08:00'");

} catch (Throwable $e) {

    error_log("Database Error: " . $e->getMessage());

    header('Content-Type: application/json');
    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

    exit;
}
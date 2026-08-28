<?php
// Standalone diagnostic — run with: php test_connection.php
// Isolates the SSL connection to the STAGING database (api/.env.testing)
// outside of PHPUnit, so the real mysqli error is visible.

$envPath = __DIR__ . '/api/.env.testing';
if (!file_exists($envPath)) {
    die("Missing {$envPath}\n");
}

$env = parse_ini_file($envPath);

echo "Connecting to:\n";
echo "  HOST: {$env['DB_HOST']}\n";
echo "  PORT: {$env['DB_PORT']}\n";
echo "  DB:   {$env['DB_DATABASE']}\n";
echo "  USER: {$env['DB_USERNAME']}\n\n";

$certFile = !empty($env['DB_SSL_CA']) ? $env['DB_SSL_CA'] : 'isrgrootx1.pem';
$cert     = __DIR__ . '/certificates/' . $certFile;

if (!file_exists($cert)) {
    die("CA certificate not found: {$cert}\n");
}
echo "Using CA cert: {$cert}\n\n";

mysqli_report(MYSQLI_REPORT_OFF); // don't throw — we want to inspect the error manually

$conn = mysqli_init();
mysqli_ssl_set($conn, null, null, $cert, null, null);

$ok = mysqli_real_connect(
    $conn,
    $env['DB_HOST'],
    $env['DB_USERNAME'],
    $env['DB_PASSWORD'],
    $env['DB_DATABASE'],
    (int) ($env['DB_PORT'] ?? 3306),
    null,
    MYSQLI_CLIENT_SSL
);

if (!$ok) {
    echo "CONNECTION FAILED\n";
    echo "Error #" . mysqli_connect_errno() . ": " . mysqli_connect_error() . "\n";
    exit(1);
}

echo "CONNECTION OK\n";
$result = $conn->query('SELECT 1 AS test');
print_r($result->fetch_assoc());

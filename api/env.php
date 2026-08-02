<?php
/**
 * EJR Garage Environment Loader
 * Supports:
 *  - Render Environment Variables
 *  - Local .env file (InfinityFree/local development)
 */

// ------------------------------------------------------------
// Detect Render
// ------------------------------------------------------------
$renderVars = [
    'DB_HOST',
    'DB_PORT',
    'DB_NAME',
    'DB_USER',
    'DB_PASS'
];

$usingRender = true;

foreach ($renderVars as $var) {
    if (getenv($var) === false || getenv($var) === '') {
        $usingRender = false;
        break;
    }
}

if ($usingRender) {

    // Database
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_PORT', getenv('DB_PORT'));
    define('DB_DATABASE', getenv('DB_NAME'));
    define('DB_USERNAME', getenv('DB_USER'));
    define('DB_PASSWORD', getenv('DB_PASS'));

    // JWT
    define('JWT_SECRET', getenv('JWT_SECRET'));

    // Brevo
    define('BREVO_API_KEY', getenv('BREVO_API_KEY'));
    define('BREVO_SENDER_EMAIL', getenv('BREVO_SENDER_EMAIL'));
    define('BREVO_SENDER_NAME', getenv('BREVO_SENDER_NAME'));

    // PayMongo
    define('PAYMONGO_SECRET_KEY', getenv('PAYMONGO_SECRET_KEY'));

    return;
}

// ------------------------------------------------------------
// Local .env (InfinityFree / Localhost)
// ------------------------------------------------------------

$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    http_response_code(500);
    die('Server misconfigured: missing environment configuration.');
}

foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {

    $line = trim($line);

    if (
        $line === '' ||
        $line[0] === '#' ||
        strpos($line, '=') === false
    ) {
        continue;
    }

    list($key, $value) = explode('=', $line, 2);

    $key = trim($key);
    $value = trim($value);

    // Remove surrounding quotes
    if (
        strlen($value) >= 2 &&
        (
            ($value[0] === '"' && substr($value, -1) === '"') ||
            ($value[0] === "'" && substr($value, -1) === "'")
        )
    ) {
        $value = substr($value, 1, -1);
    }

    if (!defined($key)) {
        define($key, $value);
    }
}
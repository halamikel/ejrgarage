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

    // Optional: CA cert filename (in ../certificates/) for the DB's SSL chain.
    // TiDB works with the default in db_config.php; Azure Database for MySQL
    // needs its own bundle — set DB_SSL_CA in Render's dashboard to override.
    define('DB_SSL_CA', getenv('DB_SSL_CA') ?: '');

    // JWT
    define('JWT_SECRET', getenv('JWT_SECRET'));

    // Brevo
    define('BREVO_API_KEY', getenv('BREVO_API_KEY'));
    define('BREVO_SENDER_EMAIL', getenv('BREVO_SENDER_EMAIL'));
    define('BREVO_SENDER_NAME', getenv('BREVO_SENDER_NAME'));

    // PayMongo
    define('PAYMONGO_SECRET_KEY', getenv('PAYMONGO_SECRET_KEY'));

    // Semaphore (SMS)
    define('SEMAPHORE_API_KEY', getenv('SEMAPHORE_API_KEY') ?: '');
    define('SEMAPHORE_SENDER_NAME', getenv('SEMAPHORE_SENDER_NAME') ?: 'SEMAPHORE');

    // Cloudinary (avatar storage — Render's local disk is ephemeral and
    // wipes runtime uploads on every restart, see upload_avatar.php)
    define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME'));
    define('CLOUDINARY_API_KEY', getenv('CLOUDINARY_API_KEY'));
    define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET'));

    return;
}

// ------------------------------------------------------------
// Local .env (InfinityFree / Localhost)
// ------------------------------------------------------------

$envFile = (getenv('APP_ENV') === 'testing') ? '.env.testing' : '.env';
$envPath = __DIR__ . '/' . $envFile;

if (!file_exists($envPath)) {
    http_response_code(500);
    die("Server misconfigured: missing {$envFile}.");
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
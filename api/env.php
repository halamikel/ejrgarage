<?php
/**
 * EJR Garage — Secrets loader
 *
 * OWASP A02: Cryptographic Failures / Sensitive Data Exposure
 * ─────────────────────────────────────────────────────────────
 * Actual secret values live in api/.env (plain KEY=VALUE text,
 * NOT executable PHP). This file just parses that text file and
 * defines the same constants the rest of the app already expects
 * (DB_HOST, JWT_SECRET, etc.) — no other file needs to change.
 *
 * This does NOT use getenv()/putenv()/$_ENV or any dotenv
 * library — those are unreliable on InfinityFree's shared
 * hosting. It's a plain file() read, which works anywhere PHP
 * runs.
 *
 * api/.env must stay blocked from direct HTTP access via
 * api/.htaccess. Unlike this .php loader, a raw .env file served
 * without that block would leak every secret as plaintext.
 *
 * NEVER commit api/.env to a public Git repository.
 */

$ejr_env_path = __DIR__ . '/.env';

if (!file_exists($ejr_env_path)) {
    http_response_code(500);
    die('Server misconfigured: missing .env file.');
}

foreach (file($ejr_env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ejr_env_line) {
    $ejr_env_line = trim($ejr_env_line);

    // Skip comments and malformed lines
    if ($ejr_env_line === '' || $ejr_env_line[0] === '#' || !str_contains($ejr_env_line, '=')) {
        continue;
    }

    [$ejr_key, $ejr_value] = explode('=', $ejr_env_line, 2);
    $ejr_key   = trim($ejr_key);
    $ejr_value = trim($ejr_value);

    // Strip matching surrounding quotes, if present
    $len = strlen($ejr_value);
    if ($len >= 2) {
        $first = $ejr_value[0];
        $last  = $ejr_value[$len - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $ejr_value = substr($ejr_value, 1, -1);
        }
    }

    if ($ejr_key !== '' && !defined($ejr_key)) {
        define($ejr_key, $ejr_value);
    }
}

unset($ejr_env_path, $ejr_env_line, $ejr_key, $ejr_value, $len, $first, $last);

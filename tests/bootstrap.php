<?php
/**
 * PHPUnit bootstrap.
 *
 * Marks the process as running under the test environment so that
 * api/db_config.php (via api/env.php) can point at the TEST database
 * instead of production, if you wire that up in env.php, e.g.:
 *
 *   $envFile = getenv('APP_ENV') === 'testing' ? '.env.testing' : '.env';
 *
 * IMPORTANT: These tests hit real HTTP endpoints served by
 *   php -S localhost:8000 -t .
 * started in a separate terminal (or a CI step) BEFORE running
 *   vendor/bin/phpunit
 * They do not start the server themselves, since the built-in server
 * blocks the process that starts it.
 */

putenv('APP_ENV=testing');

require_once __DIR__ . '/../vendor/autoload.php';

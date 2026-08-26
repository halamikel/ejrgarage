<?php

namespace Tests\Support;

use mysqli;

/**
 * Direct database access for test setup/teardown only.
 *
 * The HTTP endpoints intentionally don't expose things like "mark a
 * user verified" or "delete this test user", so tests reach into the
 * TEST database directly for those steps, and always go through the
 * real api/*.php endpoints for anything that's actually part of the
 * integration flow being tested.
 *
 * Reads credentials from api/.env.testing — copy api/.env, point
 * DB_DATABASE at a separate test schema, and never point this at
 * production.
 */
class TestDb
{
    private static ?mysqli $conn = null;

    public static function connection(): mysqli
    {
        if (self::$conn instanceof mysqli) {
            return self::$conn;
        }

        $envPath = __DIR__ . '/../../api/.env.testing';
        if (!file_exists($envPath)) {
            throw new \RuntimeException(
                "Missing {$envPath}. Copy api/.env to api/.env.testing and " .
                "point DB_DATABASE at a separate test schema before running tests."
            );
        }

        $env = parse_ini_file($envPath);

        $conn = new mysqli(
            $env['DB_HOST'],
            $env['DB_USERNAME'],
            $env['DB_PASSWORD'],
            $env['DB_DATABASE'],
            (int) ($env['DB_PORT'] ?? 3306)
        );

        $conn->set_charset('utf8mb4');

        return self::$conn = $conn;
    }

    public static function markUserVerified(string $email): void
    {
        $stmt = self::connection()->prepare(
            'UPDATE users SET is_verified = 1 WHERE email = ?'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
    }

    public static function deleteVehicleByPlate(string $plate): void
    {
        $stmt = self::connection()->prepare('DELETE FROM vehicles WHERE plate_number = ?');
        $stmt->bind_param('s', $plate);
        $stmt->execute();
    }

    public static function deleteUserByEmail(string $email): void
    {
        $conn = self::connection();
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            return;
        }

        $userId = $user['id'];
        $conn->prepare('DELETE FROM appointments WHERE user_id = ?')->execute([$userId]) ?? null;
        foreach (['appointments', 'vehicles'] as $table) {
            $del = $conn->prepare("DELETE FROM {$table} WHERE user_id = ?");
            $del->bind_param('i', $userId);
            $del->execute();
        }

        $del = $conn->prepare('DELETE FROM users WHERE id = ?');
        $del->bind_param('i', $userId);
        $del->execute();
    }
}

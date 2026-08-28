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

        $certFile = !empty($env['DB_SSL_CA']) ? $env['DB_SSL_CA'] : 'isrgrootx1.pem';
        $cert     = __DIR__ . '/../../certificates/' . $certFile;

        if (!file_exists($cert)) {
            throw new \RuntimeException("CA certificate not found: {$cert}");
        }

        $conn = mysqli_init();
        mysqli_ssl_set($conn, null, null, $cert, null, null);

        if (!mysqli_real_connect(
            $conn,
            $env['DB_HOST'],
            $env['DB_USERNAME'],
            $env['DB_PASSWORD'],
            $env['DB_DATABASE'],
            (int) ($env['DB_PORT'] ?? 3306),
            null,
            MYSQLI_CLIENT_SSL
        )) {
            throw new \RuntimeException('TestDb SSL connection failed: ' . mysqli_connect_error());
        }

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

    /**
     * Clears rate_limits rows recorded against localhost, so repeated
     * local test runs don't trip register.php / login.php's real
     * rate limiter (5 registrations / 5 min per IP, etc.). Safe to
     * call even if the table doesn't exist yet.
     */
    public static function resetRateLimits(): void
    {
        $conn = self::connection();
        $conn->query(
            "DELETE FROM rate_limits WHERE hit_key IN ('127.0.0.1', '::1') OR hit_key LIKE '127.0.0.1|%' OR hit_key LIKE '::1|%'"
        );
    }

    /**
     * Inserts a throwaway part for tests to order/inquire about/search for.
     * Returns the new part's id. Caller is responsible for calling
     * deletePart() in tearDown.
     *
     * @param array<string,mixed> $overrides
     */
    public static function insertTestPart(array $overrides = []): int
    {
        $defaults = [
            'name'        => 'Test Part ' . uniqid(),
            'brand'       => 'TestBrand',
            'description' => 'Created by an integration test — safe to delete.',
            'price'       => 499.00,
            'category'    => 'TestCategory',
            'image_url'   => '',
            'stock'       => 10,
        ];
        $part = array_merge($defaults, $overrides);

        $stmt = self::connection()->prepare(
            'INSERT INTO parts (name, brand, description, price, category, image_url, stock) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'sssdssi',
            $part['name'],
            $part['brand'],
            $part['description'],
            $part['price'],
            $part['category'],
            $part['image_url'],
            $part['stock']
        );
        $stmt->execute();

        return $stmt->insert_id;
    }

    /**
     * Deletes a test part and anything that references it (inquiries),
     * so tests don't leave permanent rows in the shared staging DB.
     * part_orders stores items as a JSON blob (no FK), so it's untouched.
     */
    public static function deletePart(int $partId): void
    {
        $conn = self::connection();

        $del = $conn->prepare('DELETE FROM part_inquiries WHERE part_id = ?');
        $del->bind_param('i', $partId);
        $del->execute();

        $del = $conn->prepare('DELETE FROM parts WHERE id = ?');
        $del->bind_param('i', $partId);
        $del->execute();
    }

    public static function deletePartOrderById(int $orderId): void
    {
        $stmt = self::connection()->prepare('DELETE FROM part_orders WHERE id = ?');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
    }

    public static function setAppointmentStatus(int $appointmentId, string $status): void
    {
        $stmt = self::connection()->prepare('UPDATE appointments SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $appointmentId);
        $stmt->execute();
    }

    public static function deleteAppointmentById(int $appointmentId): void
    {
        $stmt = self::connection()->prepare('DELETE FROM appointments WHERE id = ?');
        $stmt->bind_param('i', $appointmentId);
        $stmt->execute();
    }

    /**
     * Inserts a user directly (bypassing register.php, which always
     * hardcodes role_id 1), for fixtures like a role_id=3 mechanic
     * account that the public registration endpoint can't create.
     * Password is always 'Password123!' unless overridden.
     *
     * @param array<string,mixed> $overrides
     */
    public static function insertTestUser(array $overrides = []): int
    {
        $defaults = [
            'full_name' => 'Integration Test User',
            'email'     => 'test_user_' . uniqid() . '@example.com',
            'phone'     => '639' . substr(str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT), 0, 9),
            'password'  => 'Password123!',
            'role_id'   => 1,
        ];
        $u = array_merge($defaults, $overrides);
        $passwordHash = password_hash($u['password'], PASSWORD_DEFAULT);

        $stmt = self::connection()->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, is_verified, role_id) VALUES (?, ?, ?, ?, 1, ?)'
        );
        $stmt->bind_param('ssssi', $u['full_name'], $u['email'], $u['phone'], $passwordHash, $u['role_id']);
        $stmt->execute();

        return $stmt->insert_id;
    }

    public static function deleteUserById(int $userId): void
    {
        $stmt = self::connection()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }

    public static function deleteMechanicRosterByUserId(int $userId): void
    {
        $stmt = self::connection()->prepare('DELETE FROM mechanics WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }

    public static function getMechanicRosterIdByUserId(int $userId): ?int
    {
        $stmt = self::connection()->prepare('SELECT id FROM mechanics WHERE user_id = ? LIMIT 1');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row ? (int) $row['id'] : null;
    }

    public static function assignAppointmentMechanic(int $appointmentId, int $mechanicRosterId): void
    {
        $stmt = self::connection()->prepare('UPDATE appointments SET mechanic_id = ? WHERE id = ?');
        $stmt->bind_param('ii', $mechanicRosterId, $appointmentId);
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
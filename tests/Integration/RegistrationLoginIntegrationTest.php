<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Integration Test — Registration Module x Login Module
 *
 * Verifies that a user created through register.php is correctly
 * persisted and can then be authenticated through login.php, i.e.
 * that the two modules agree on the shape of the `users` row
 * (password_hash, is_verified, role_id) between them.
 */
class RegistrationLoginIntegrationTest extends TestCase
{
    private ApiClient $api;
    private string $email;
    private string $phone;
    private string $password = 'Password123!';

    protected function setUp(): void
    {
        $this->api   = new ApiClient();
        $unique      = uniqid();
        $this->email = "test_{$unique}@example.com";
        // register.php normalizes phone to 639XXXXXXXXX and requires it unique
        $this->phone = '639' . substr(str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT), 0, 9);
    }

    protected function tearDown(): void
    {
        TestDb::deleteUserByEmail($this->email);
    }

    public function test_login_fails_before_email_is_verified(): void
    {
        $register = $this->api->post('register.php', [
            'name'     => 'Test User',
            'email'    => $this->email,
            'phone'    => $this->phone,
            'password' => $this->password,
        ]);

        $this->assertSame(200, $register['status']);
        $this->assertSame('success', $register['body']['status']);

        $login = $this->api->post('login.php', [
            'email'    => $this->email,
            'password' => $this->password,
        ]);

        $this->assertSame('error', $login['body']['status']);
        $this->assertStringContainsString('verify your email', $login['body']['message']);
    }

    public function test_verified_user_can_login_and_receives_a_jwt(): void
    {
        $register = $this->api->post('register.php', [
            'name'     => 'Test User',
            'email'    => $this->email,
            'phone'    => $this->phone,
            'password' => $this->password,
        ]);
        $this->assertSame('success', $register['body']['status']);

        // register.php doesn't expose a way to verify via HTTP (that's
        // the email-link flow), so the test drives that state directly.
        TestDb::markUserVerified($this->email);

        $login = $this->api->post('login.php', [
            'email'    => $this->email,
            'password' => $this->password,
        ]);

        $this->assertSame(200, $login['status']);
        $this->assertSame('success', $login['body']['status']);
        $this->assertArrayHasKey('token', $login['body']);
        $this->assertSame($this->email, $login['body']['user']['email']);
    }

    public function test_login_rejects_wrong_password(): void
    {
        $register = $this->api->post('register.php', [
            'name'     => 'Test User',
            'email'    => $this->email,
            'phone'    => $this->phone,
            'password' => $this->password,
        ]);
        $this->assertSame('success', $register['body']['status']);
        TestDb::markUserVerified($this->email);

        $login = $this->api->post('login.php', [
            'email'    => $this->email,
            'password' => 'WrongPassword!',
        ]);

        $this->assertArrayNotHasKey('token', $login['body']);
    }
}

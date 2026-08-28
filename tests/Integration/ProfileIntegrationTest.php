<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Integration Test — Profile Module x Login Module
 *
 * Exercises get_profile.php, update_profile.php (name/phone/address,
 * the no-email-change path), and change_password.php, confirming the
 * JWT issued by login.php is accepted by all three and that writes
 * through one endpoint are immediately visible through the others.
 *
 * Uses its own throwaway user (rather than the shared fixture) since
 * this test changes the account's password.
 */
class ProfileIntegrationTest extends TestCase
{
    private ApiClient $api;
    private int $userId;
    private string $email;
    private string $password = 'Password123!';
    private string $jwt;

    protected function setUp(): void
    {
        $this->api   = new ApiClient();
        $this->email = 'integration_profile_' . uniqid() . '@example.com';

        $this->userId = TestDb::insertTestUser([
            'full_name' => 'Integration Profile User',
            'email'     => $this->email,
            'password'  => $this->password,
            'role_id'   => 1,
        ]);

        $login = $this->api->post('login.php', [
            'email'    => $this->email,
            'password' => $this->password,
        ]);
        $this->assertSame('success', $login['body']['status'] ?? null, $login['raw']);
        $this->jwt = $login['body']['token'];
    }

    protected function tearDown(): void
    {
        TestDb::deleteUserById($this->userId);
    }

    public function test_profile_can_be_fetched_and_matches_the_logged_in_user(): void
    {
        $profile = $this->api->get('get_profile.php', ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame('success', $profile['body']['status'], $profile['raw']);
        $this->assertSame($this->userId, (int) $profile['body']['user']['id']);
        $this->assertSame($this->email, $profile['body']['user']['email']);
        $this->assertSame('Integration Profile User', $profile['body']['user']['full_name']);
    }

    public function test_profile_update_without_email_change_is_immediately_visible(): void
    {
        $newName    = 'Updated Integration User ' . uniqid();
        $newAddress = '456 Updated Street, Santo Tomas, Batangas';

        $update = $this->api->post('update_profile.php', [
            'full_name' => $newName,
            'email'     => $this->email, // unchanged — takes the direct-update path
            'phone'     => '639171234567',
            'address'   => $newAddress,
        ], ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame('success', $update['body']['status'], $update['raw']);
        $this->assertSame($newName, $update['body']['user']['full_name']);

        $profile = $this->api->get('get_profile.php', ['Authorization' => "Bearer {$this->jwt}"]);
        $this->assertSame('success', $profile['body']['status']);
        $this->assertSame($newName, $profile['body']['user']['full_name']);
        $this->assertSame($newAddress, $profile['body']['user']['address']);
        $this->assertSame('639171234567', $profile['body']['user']['phone']);
    }

    public function test_changing_email_puts_the_update_in_a_pending_state_instead_of_applying_immediately(): void
    {
        $newEmail = 'integration_profile_changed_' . uniqid() . '@example.com';

        $update = $this->api->post('update_profile.php', [
            'full_name' => 'Integration Profile User',
            'email'     => $newEmail,
            'phone'     => '639171234567',
            'address'   => '123 Test Street, Santo Tomas, Batangas',
        ], ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame('pending_email', $update['body']['status'], $update['raw']);

        // The account's login email must NOT change until the
        // verification link (confirm_email_change.php) is clicked.
        $login = $this->api->post('login.php', [
            'email'    => $this->email,
            'password' => $this->password,
        ]);
        $this->assertSame('success', $login['body']['status'] ?? null, $login['raw']);
    }

    public function test_password_can_be_changed_and_the_new_password_logs_in(): void
    {
        $newPassword = 'NewPassword456!';

        $change = $this->api->post('change_password.php', [
            'current_password' => $this->password,
            'new_password'     => $newPassword,
            'confirm_password' => $newPassword,
        ], ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame(200, $change['status']);
        $this->assertStringContainsString('"status":"success"', $change['raw']);

        $oldLogin = $this->api->post('login.php', [
            'email'    => $this->email,
            'password' => $this->password,
        ]);
        $this->assertArrayNotHasKey('token', $oldLogin['body'] ?? []);

        $newLogin = $this->api->post('login.php', [
            'email'    => $this->email,
            'password' => $newPassword,
        ]);
        $this->assertSame('success', $newLogin['body']['status'] ?? null, $newLogin['raw']);
    }

    public function test_change_password_is_rejected_when_current_password_is_wrong(): void
    {
        $change = $this->api->post('change_password.php', [
            'current_password' => 'DefinitelyWrong!',
            'new_password'     => 'AnotherPassword789!',
            'confirm_password' => 'AnotherPassword789!',
        ], ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertStringContainsString('"status":"error"', $change['raw']);
        $this->assertStringContainsString('incorrect', strtolower($change['raw']));

        // Original password must still work.
        $login = $this->api->post('login.php', [
            'email'    => $this->email,
            'password' => $this->password,
        ]);
        $this->assertSame('success', $login['body']['status'] ?? null, $login['raw']);
    }
}

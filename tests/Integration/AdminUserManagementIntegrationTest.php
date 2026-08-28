<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Integration Test — User Management Module (Admin)
 *
 * Exercises admin/manage_user.php: fetching a single user's details,
 * editing a user's info, and archiving (soft-deleting) a user. Also
 * confirms a non-admin account is forbidden from every action, and
 * that an admin can't archive their own account through this endpoint.
 *
 * Note: manage_user.php's authorization-failure branch never calls
 * http_response_code(), so a rejected request still comes back over
 * HTTP 200 with {"status":"error", ...} in the body — unlike most of
 * the other admin/*.php endpoints, which return a real 403. Tests here
 * assert on the JSON body's status, not the HTTP status code, to match
 * what the endpoint actually does.
 *
 * Archiving is verified through admin/get_full_site_data.php's users
 * list, since manage_user.php's own GET-by-id doesn't filter out
 * archived users (it's a raw lookup by id, archived or not).
 */
class AdminUserManagementIntegrationTest extends TestCase
{
    private ApiClient $api;
    private string $adminJwt;
    private int $adminUserId;
    private string $customerJwt;
    private int $targetUserId;

    private string $customerEmail = 'integration_test_fixture@example.com';
    private string $customerPassword = 'Password123!';

    protected function setUp(): void
    {
        $this->api = new ApiClient();

        // Admin fixture — created directly, since register.php always
        // hardcodes role_id=1 and can't produce a role_id=2 account.
        $adminEmail = 'integration_admin_' . uniqid() . '@example.com';
        $this->adminUserId = TestDb::insertTestUser([
            'full_name' => 'Integration Test Admin',
            'email'     => $adminEmail,
            'role_id'   => 2,
        ]);

        $adminLogin = $this->api->post('login.php', [
            'email'    => $adminEmail,
            'password' => 'Password123!',
        ]);
        $this->assertSame('success', $adminLogin['body']['status'] ?? null, $adminLogin['raw']);
        $this->adminJwt = $adminLogin['body']['token'];

        // Shared customer fixture, used for the forbidden-access check.
        $custLogin = $this->api->post('login.php', [
            'email'    => $this->customerEmail,
            'password' => $this->customerPassword,
        ]);
        $this->assertSame(
            'success',
            $custLogin['body']['status'] ?? null,
            'Customer fixture login failed: ' . ($custLogin['body']['message'] ?? '')
        );
        $this->customerJwt = $custLogin['body']['token'];

        // A separate, throwaway customer account for the admin to manage
        // (fetch/edit/archive) — never the shared fixture above, since
        // other tests depend on that one staying intact.
        $this->targetUserId = TestDb::insertTestUser([
            'full_name' => 'Integration Managed User',
            'email'     => 'integration_managed_' . uniqid() . '@example.com',
            'role_id'   => 1,
        ]);
    }

    protected function tearDown(): void
    {
        TestDb::deleteUserById($this->targetUserId);
        TestDb::deleteUserById($this->adminUserId);
    }

    public function test_admin_can_fetch_user_details_by_id(): void
    {
        $fetch = $this->api->get(
            'admin/manage_user.php?id=' . $this->targetUserId,
            ['Authorization' => "Bearer {$this->adminJwt}"]
        );

        $this->assertSame('success', $fetch['body']['status'], $fetch['raw']);
        $this->assertSame($this->targetUserId, (int) $fetch['body']['data']['id']);
        $this->assertSame('Integration Managed User', $fetch['body']['data']['full_name']);
    }

    public function test_admin_can_update_user_info_and_it_is_reflected(): void
    {
        $newName  = 'Integration Managed User - Updated';
        $newEmail = 'integration_managed_updated_' . uniqid() . '@example.com';

        $update = $this->api->post('admin/manage_user.php', [
            'action'             => 'update_info',
            'user_id'            => $this->targetUserId,
            'full_name'          => $newName,
            'email'              => $newEmail,
            'role_id'            => 1,
            'is_verified'        => 1,
            'is_phone_verified'  => 0,
        ], ['Authorization' => "Bearer {$this->adminJwt}"]);
        $this->assertSame('success', $update['body']['status'], $update['raw']);

        $fetch = $this->api->get(
            'admin/manage_user.php?id=' . $this->targetUserId,
            ['Authorization' => "Bearer {$this->adminJwt}"]
        );
        $this->assertSame('success', $fetch['body']['status']);
        $this->assertSame($newName, $fetch['body']['data']['full_name']);
        $this->assertSame($newEmail, $fetch['body']['data']['email']);
        $this->assertSame(1, (int) $fetch['body']['data']['is_verified']);
    }

    public function test_admin_can_archive_a_user_and_it_disappears_from_site_data(): void
    {
        // Sanity check: visible before archiving.
        $before = $this->api->get('admin/get_full_site_data.php', ['Authorization' => "Bearer {$this->adminJwt}"]);
        $this->assertSame('success', $before['body']['status'], $before['raw']);
        // Cast: get_full_site_data.php doesn't (int) its id column, and
        // PHPUnit 11's assertContains() is strict (===).
        $beforeIds = array_map('intval', array_column($before['body']['users'], 'id'));
        $this->assertContains($this->targetUserId, $beforeIds);

        $delete = $this->api->post('admin/manage_user.php', [
            'action'  => 'delete',
            'user_id' => $this->targetUserId,
        ], ['Authorization' => "Bearer {$this->adminJwt}"]);
        $this->assertSame('success', $delete['body']['status'], $delete['raw']);

        $after = $this->api->get('admin/get_full_site_data.php', ['Authorization' => "Bearer {$this->adminJwt}"]);
        $this->assertSame('success', $after['body']['status']);
        $afterIds = array_map('intval', array_column($after['body']['users'], 'id'));
        $this->assertNotContains($this->targetUserId, $afterIds);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $delete = $this->api->post('admin/manage_user.php', [
            'action'  => 'delete',
            'user_id' => $this->adminUserId,
        ], ['Authorization' => "Bearer {$this->adminJwt}"]);

        $this->assertSame('error', $delete['body']['status']);
    }

    public function test_non_admin_customer_is_forbidden_from_managing_users(): void
    {
        $fetch = $this->api->get(
            'admin/manage_user.php?id=' . $this->targetUserId,
            ['Authorization' => "Bearer {$this->customerJwt}"]
        );
        $this->assertSame('error', $fetch['body']['status']);

        $update = $this->api->post('admin/manage_user.php', [
            'action'    => 'update_info',
            'user_id'   => $this->targetUserId,
            'full_name' => 'Should Never Apply',
            'email'     => 'should-never-apply-' . uniqid() . '@example.com',
        ], ['Authorization' => "Bearer {$this->customerJwt}"]);
        $this->assertSame('error', $update['body']['status']);
    }
}
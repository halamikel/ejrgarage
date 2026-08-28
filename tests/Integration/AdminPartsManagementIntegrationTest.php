<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Integration Test — Available Parts Module (Admin) x Available
 * Parts/Search Module
 *
 * Exercises admin/manage_parts.php's add/edit/delete actions — the
 * admin-side inventory management screen, distinct from the
 * customer-facing catalog/search already covered by
 * PartsOrdersInquiriesIntegrationTest. Every write is verified through
 * the public get_parts.php endpoint, confirming the two modules share
 * the same underlying `parts` table and that archiving a part (the
 * "delete" action) actually removes it from the public catalog rather
 * than just flipping a flag nobody reads.
 *
 * Also confirms a non-admin account is forbidden from managing parts.
 */
class AdminPartsManagementIntegrationTest extends TestCase
{
    private ApiClient $api;
    private string $adminJwt;
    private string $customerJwt;
    private int $adminUserId;
    private ?int $partId = null;

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
    }

    protected function tearDown(): void
    {
        if ($this->partId !== null) {
            TestDb::deletePart($this->partId);
        }
        TestDb::deleteUserById($this->adminUserId);
    }

    public function test_admin_added_part_appears_in_the_public_catalog(): void
    {
        $uniqueName = 'IntegrationAdminPart-' . uniqid();

        $add = $this->api->post('admin/manage_parts.php', [
            'action'      => 'add',
            'name'        => $uniqueName,
            'brand'       => 'IntegrationBrand',
            'description' => 'Created by AdminPartsManagementIntegrationTest — safe to delete.',
            'price'       => 599.00,
            'category'    => 'IntegrationCategory',
            'image_url'   => '',
            'stock'       => 7,
        ], ['Authorization' => "Bearer {$this->adminJwt}"]);
        $this->assertSame('success', $add['body']['status'], $add['raw']);

        $this->partId = TestDb::getPartIdByName($uniqueName);
        $this->assertNotNull($this->partId, 'Added part could not be found by name afterwards.');

        $catalog = $this->api->get('get_parts.php?search=' . urlencode($uniqueName));
        $this->assertSame('success', $catalog['body']['status']);
        $names = array_column($catalog['body']['parts'], 'name');
        $this->assertContains($uniqueName, $names);
    }

    public function test_admin_can_edit_a_part_and_the_change_is_reflected_publicly(): void
    {
        $originalName = 'IntegrationAdminPart-' . uniqid();
        $this->partId = TestDb::insertTestPart(['name' => $originalName, 'price' => 100.00, 'stock' => 3]);

        $updatedName = $originalName . '-Updated';
        $edit = $this->api->post('admin/manage_parts.php', [
            'action'      => 'edit',
            'id'          => $this->partId,
            'name'        => $updatedName,
            'brand'       => 'IntegrationBrand',
            'description' => 'Updated by AdminPartsManagementIntegrationTest.',
            'price'       => 350.00,
            'category'    => 'IntegrationCategory',
            'image_url'   => '',
            'stock'       => 9,
        ], ['Authorization' => "Bearer {$this->adminJwt}"]);
        $this->assertSame('success', $edit['body']['status'], $edit['raw']);

        $catalog = $this->api->get('get_parts.php?search=' . urlencode($updatedName));
        $this->assertSame('success', $catalog['body']['status']);
        $this->assertNotEmpty($catalog['body']['parts']);
        $updated = $catalog['body']['parts'][0];
        $this->assertSame($updatedName, $updated['name']);
        $this->assertEquals(350.00, (float) $updated['price']);
        $this->assertEquals(9, (int) $updated['stock']);
    }

    public function test_admin_deleted_part_is_archived_and_disappears_from_the_public_catalog(): void
    {
        $uniqueName  = 'IntegrationAdminPart-' . uniqid();
        $this->partId = TestDb::insertTestPart(['name' => $uniqueName]);

        // Sanity check: visible before archiving.
        $before = $this->api->get('get_parts.php?search=' . urlencode($uniqueName));
        $this->assertContains($uniqueName, array_column($before['body']['parts'], 'name'));

        $delete = $this->api->post('admin/manage_parts.php', [
            'action' => 'delete',
            'id'     => $this->partId,
        ], ['Authorization' => "Bearer {$this->adminJwt}"]);
        $this->assertSame('success', $delete['body']['status'], $delete['raw']);

        $after = $this->api->get('get_parts.php?search=' . urlencode($uniqueName));
        $this->assertSame('success', $after['body']['status']);
        $this->assertNotContains($uniqueName, array_column($after['body']['parts'], 'name'));
    }

    public function test_non_admin_customer_is_forbidden_from_adding_a_part(): void
    {
        $add = $this->api->post('admin/manage_parts.php', [
            'action'      => 'add',
            'name'        => 'ShouldNeverExist-' . uniqid(),
            'brand'       => 'IntegrationBrand',
            'description' => 'Should be rejected before insert.',
            'price'       => 100.00,
            'category'    => 'IntegrationCategory',
            'image_url'   => '',
            'stock'       => 1,
        ], ['Authorization' => "Bearer {$this->customerJwt}"]);

        $this->assertSame('error', $add['body']['status']);
        $this->assertSame(403, $add['status']);
    }
}

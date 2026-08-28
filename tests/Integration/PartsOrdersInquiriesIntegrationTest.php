<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Integration Test — Available Parts/Search x Shopping Cart x Orders
 * Management x Parts Inventory x Part Inquiries Modules
 *
 * Exercises get_parts.php (search/category filtering, doubling as the
 * customer-facing "Available Parts" and "Parts Inventory" read views),
 * place_order.php (the checkout/cart submission), get_my_orders.php,
 * cancel_order.php, submit_inquiry.php, and get_inquiries.php — using a
 * real, throwaway part seeded directly via TestDb rather than relying
 * on whatever inventory already exists in the shared staging DB.
 *
 * Uses the same pre-seeded, verified fixture account as
 * VehicleAppointmentIntegrationTest.
 */
class PartsOrdersInquiriesIntegrationTest extends TestCase
{
    private ApiClient $api;
    private string $jwt;
    private int $partId;

    private string $email = 'integration_test_fixture@example.com';
    private string $password = 'Password123!';

    /** @var int[] order ids created during a test, cleaned up in tearDown */
    private array $createdOrderIds = [];

    protected function setUp(): void
    {
        $this->api = new ApiClient();

        $login = $this->api->post('login.php', [
            'email'    => $this->email,
            'password' => $this->password,
        ]);

        $this->assertSame(
            'success',
            $login['body']['status'] ?? null,
            'Fixture login failed — make sure the test user exists and is verified in the test DB: ' . ($login['body']['message'] ?? '')
        );

        $this->jwt = $login['body']['token'];
    }

    protected function tearDown(): void
    {
        foreach ($this->createdOrderIds as $orderId) {
            TestDb::deletePartOrderById($orderId);
        }
        $this->createdOrderIds = [];

        if (isset($this->partId)) {
            TestDb::deletePart($this->partId);
        }
    }

    public function test_part_can_be_found_by_search_and_by_category(): void
    {
        $uniqueName     = 'IntegrationPart-' . uniqid();
        $uniqueCategory = 'IntegrationCategory-' . uniqid();

        $this->partId = TestDb::insertTestPart([
            'name'     => $uniqueName,
            'category' => $uniqueCategory,
            'stock'    => 10,
        ]);

        $bySearch = $this->api->get('get_parts.php?search=' . urlencode($uniqueName));
        $this->assertSame('success', $bySearch['body']['status']);
        $names = array_column($bySearch['body']['parts'], 'name');
        $this->assertContains($uniqueName, $names);

        $byCategory = $this->api->get('get_parts.php?category=' . urlencode($uniqueCategory));
        $this->assertSame('success', $byCategory['body']['status']);
        $names = array_column($byCategory['body']['parts'], 'name');
        $this->assertContains($uniqueName, $names);
    }

    public function test_cod_order_can_be_placed_appears_in_my_orders_and_can_be_cancelled(): void
    {
        $this->partId = TestDb::insertTestPart(['price' => 250.00, 'stock' => 5]);

        $order = $this->api->post('place_order.php', [
            'customer_name'  => 'Integration Fixture',
            'contact'        => '639171234567',
            'address'        => '123 Test Street, Santo Tomas, Batangas',
            'items'          => [['id' => $this->partId, 'qty' => 2]],
            'total_price'    => 500.00,
            'payment_method' => 'cod',
        ], ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame('success', $order['body']['status'], $order['raw']);
        $this->assertNull($order['body']['checkout_url']);

        $list = $this->api->get('get_my_orders.php', ['Authorization' => "Bearer {$this->jwt}"]);
        $this->assertSame('success', $list['body']['status']);

        $placed = null;
        foreach ($list['body']['orders'] as $o) {
            $partIds = array_column($o['items'], 'id');
            if (in_array($this->partId, $partIds, false) || in_array((string) $this->partId, $partIds, true)) {
                $placed = $o;
                break;
            }
        }
        $this->assertNotNull($placed, 'Placed order was not found in get_my_orders.php response.');
        $this->assertSame('pending', $placed['status']);
        $this->createdOrderIds[] = (int) $placed['id'];

        $cancel = $this->api->post('cancel_order.php', ['id' => $placed['id']], ['Authorization' => "Bearer {$this->jwt}"]);
        $this->assertSame('success', $cancel['body']['status'], $cancel['raw']);

        $listAfter = $this->api->get('get_my_orders.php', ['Authorization' => "Bearer {$this->jwt}"]);
        $updated = null;
        foreach ($listAfter['body']['orders'] as $o) {
            if ((int) $o['id'] === (int) $placed['id']) {
                $updated = $o;
                break;
            }
        }
        $this->assertNotNull($updated);
        $this->assertSame('cancelled', $updated['status']);
    }

    public function test_order_is_rejected_when_requested_quantity_exceeds_stock(): void
    {
        $this->partId = TestDb::insertTestPart(['stock' => 1, 'price' => 100.00]);

        $order = $this->api->post('place_order.php', [
            'customer_name'  => 'Integration Fixture',
            'contact'        => '639171234567',
            'address'        => '123 Test Street, Santo Tomas, Batangas',
            'items'          => [['id' => $this->partId, 'qty' => 5]],
            'total_price'    => 500.00,
            'payment_method' => 'cod',
        ], ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame('error', $order['body']['status']);
        $this->assertStringContainsString('stock', strtolower($order['body']['message']));
    }

    public function test_inquiry_can_be_submitted_and_appears_in_my_inquiries(): void
    {
        $this->partId = TestDb::insertTestPart();
        $uniqueMessage = 'Integration test inquiry ' . uniqid();

        $submit = $this->api->post('submit_inquiry.php', [
            'part_id'       => $this->partId,
            'customer_name' => 'Integration Fixture',
            'contact'       => '639171234567',
            'message'       => $uniqueMessage,
        ], ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame('success', $submit['body']['status'], $submit['raw']);

        $list = $this->api->get('get_inquiries.php', ['Authorization' => "Bearer {$this->jwt}"]);
        $this->assertSame('success', $list['body']['status']);

        $messages = array_column($list['body']['inquiries'], 'message');
        $this->assertContains($uniqueMessage, $messages);
    }
}

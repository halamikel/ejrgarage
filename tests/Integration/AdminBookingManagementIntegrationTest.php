<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Integration Test — Booking Module (Admin) x Service Booking x
 * Appointment Management Modules
 *
 * Exercises admin/update_appointment.php — the admin-side counterpart
 * to the customer-facing booking flow already covered by
 * VehicleAppointmentIntegrationTest and ServiceBookingSchedulingIntegrationTest.
 * A customer books a real appointment through save_booking.php, an
 * admin fixture (role_id=2) changes its status and attaches a
 * cancellation reply, and the test confirms both changes are visible
 * back through the customer's own get_appointments.php — i.e. that
 * the admin Booking module and the customer Appointment module agree
 * on the same underlying `appointments` row.
 *
 * Also confirms a non-admin account is forbidden from calling the
 * admin endpoint at all.
 */
class AdminBookingManagementIntegrationTest extends TestCase
{
    private ApiClient $api;
    private string $plate;
    private int $adminUserId;
    private string $adminJwt;
    private string $customerJwt;
    private ?int $appointmentId = null;

    private string $customerEmail = 'integration_test_fixture@example.com';
    private string $customerPassword = 'Password123!';

    protected function setUp(): void
    {
        $this->api   = new ApiClient();
        $this->plate = 'TEST-' . strtoupper(substr(uniqid(), -6));

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

        // Shared customer fixture, used to create a real appointment to manage.
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

        $booking = $this->api->post('save_booking.php', [
            'vehicle' => [
                'brand' => 'Toyota', 'model' => 'Vios', 'year' => '2022',
                'plate' => $this->plate, 'transmission' => 'Automatic', 'fuel' => 'Gasoline',
                'appointmentDate' => date('Y-m-d', strtotime('+5 days')),
            ],
            'services' => ['Change Oil'],
        ], ['Authorization' => "Bearer {$this->customerJwt}"]);
        $this->assertSame('success', $booking['body']['status'], $booking['raw']);

        $this->appointmentId = $this->findAppointmentIdByPlate($this->plate);
        $this->assertNotNull($this->appointmentId, 'Setup booking was not found via get_appointments.php.');
    }

    protected function tearDown(): void
    {
        if ($this->appointmentId !== null) {
            TestDb::deleteAppointmentById($this->appointmentId);
        }
        TestDb::deleteVehicleByPlate($this->plate);
        TestDb::deleteUserById($this->adminUserId);
    }

    private function findAppointmentIdByPlate(string $plate): ?int
    {
        $list = $this->api->get('get_appointments.php', ['Authorization' => "Bearer {$this->customerJwt}"]);
        foreach ($list['body']['appointments'] as $appt) {
            if (($appt['plate_number'] ?? null) === $plate) {
                return (int) $appt['id'];
            }
        }

        return null;
    }

    public function test_admin_can_confirm_an_appointment_and_customer_sees_the_new_status(): void
    {
        $update = $this->api->post('admin/update_appointment.php', [
            'appointment_id' => $this->appointmentId,
            'status'         => 'Confirmed',
        ], ['Authorization' => "Bearer {$this->adminJwt}"]);

        $this->assertSame('success', $update['body']['status'], $update['raw']);

        $list = $this->api->get('get_appointments.php', ['Authorization' => "Bearer {$this->customerJwt}"]);
        $this->assertSame('success', $list['body']['status']);

        $found = null;
        foreach ($list['body']['appointments'] as $appt) {
            if ((int) $appt['id'] === $this->appointmentId) {
                $found = $appt;
                break;
            }
        }
        $this->assertNotNull($found);
        $this->assertSame('Confirmed', $found['status']);
    }

    public function test_admin_cancellation_reply_is_attached_and_visible_to_the_customer(): void
    {
        $reason = 'Please reschedule — no available slots on that date (' . uniqid() . ').';

        $update = $this->api->post('admin/update_appointment.php', [
            'appointment_id'      => $this->appointmentId,
            'status'              => 'Cancelled',
            'cancellation_reply'  => $reason,
        ], ['Authorization' => "Bearer {$this->adminJwt}"]);

        $this->assertSame('success', $update['body']['status'], $update['raw']);

        $list = $this->api->get('get_appointments.php', ['Authorization' => "Bearer {$this->customerJwt}"]);
        $found = null;
        foreach ($list['body']['appointments'] as $appt) {
            if ((int) $appt['id'] === $this->appointmentId) {
                $found = $appt;
                break;
            }
        }
        $this->assertNotNull($found);
        $this->assertSame('Cancelled', $found['status']);
        $this->assertSame($reason, $found['cancellation_reply']);
    }

    public function test_non_admin_customer_is_forbidden_from_updating_appointment_status(): void
    {
        $update = $this->api->post('admin/update_appointment.php', [
            'appointment_id' => $this->appointmentId,
            'status'         => 'Confirmed',
        ], ['Authorization' => "Bearer {$this->customerJwt}"]);

        $this->assertSame('error', $update['body']['status']);
        $this->assertSame(403, $update['status']);

        // Status must remain untouched.
        $list = $this->api->get('get_appointments.php', ['Authorization' => "Bearer {$this->customerJwt}"]);
        $found = null;
        foreach ($list['body']['appointments'] as $appt) {
            if ((int) $appt['id'] === $this->appointmentId) {
                $found = $appt;
                break;
            }
        }
        $this->assertNotNull($found);
        $this->assertSame('Pending', $found['status']);
    }
}

<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Integration Test — Services x Service Booking x Busy Dates x
 * Service History Modules
 *
 * Exercises get_services.php, save_booking.php (the vehicle_id-based
 * booking flow — distinct from add_appointments.php, which
 * VehicleAppointmentIntegrationTest already covers), get_appointments.php,
 * cancel_appointment.php, get_busy_dates.php, and get_service_history.php.
 *
 * Uses the same pre-seeded, verified fixture account as the other
 * integration tests.
 */
class ServiceBookingSchedulingIntegrationTest extends TestCase
{
    private ApiClient $api;
    private string $jwt;
    private string $plate;

    private string $email = 'integration_test_fixture@example.com';
    private string $password = 'Password123!';

    protected function setUp(): void
    {
        $this->api   = new ApiClient();
        $this->plate = 'TEST-' . strtoupper(substr(uniqid(), -6));

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
        TestDb::deleteVehicleByPlate($this->plate);
    }

    public function test_services_list_can_be_fetched(): void
    {
        $services = $this->api->get('get_services.php');

        $this->assertSame('success', $services['body']['status']);
        $this->assertNotEmpty($services['body']['services']);
    }

    public function test_busy_dates_endpoint_returns_only_upcoming_dates(): void
    {
        $busy = $this->api->get('get_busy_dates.php');

        $this->assertSame('success', $busy['body']['status']);
        $this->assertIsArray($busy['body']['busy_dates']);

        $today = date('Y-m-d');
        foreach ($busy['body']['busy_dates'] as $date) {
            $this->assertGreaterThanOrEqual($today, substr($date, 0, 10));
        }
    }

    public function test_booking_can_be_saved_appears_in_appointments_and_can_be_cancelled(): void
    {
        $booking = $this->api->post('save_booking.php', [
            'vehicle' => [
                'brand'           => 'Toyota',
                'model'           => 'Vios',
                'year'            => '2022',
                'plate'           => $this->plate,
                'transmission'    => 'Automatic',
                'fuel'            => 'Gasoline',
                'appointmentDate' => date('Y-m-d', strtotime('+5 days')),
                'notes'           => 'Integration test booking via save_booking.php',
            ],
            'services' => ['Change Oil'],
        ], ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame('success', $booking['body']['status'], $booking['raw']);

        $list = $this->api->get('get_appointments.php', ['Authorization' => "Bearer {$this->jwt}"]);
        $this->assertSame('success', $list['body']['status']);

        $found = null;
        foreach ($list['body']['appointments'] as $appt) {
            if (($appt['plate_number'] ?? null) === $this->plate) {
                $found = $appt;
                break;
            }
        }
        $this->assertNotNull($found, 'Saved booking was not found in get_appointments.php response.');
        $this->assertSame('Pending', $found['status']);

        $cancel = $this->api->post('cancel_appointment.php', ['id' => $found['id']], ['Authorization' => "Bearer {$this->jwt}"]);
        $this->assertSame('success', $cancel['body']['status'], $cancel['raw']);

        $listAfter = $this->api->get('get_appointments.php', ['Authorization' => "Bearer {$this->jwt}"]);
        $updated = null;
        foreach ($listAfter['body']['appointments'] as $appt) {
            if ((int) $appt['id'] === (int) $found['id']) {
                $updated = $appt;
                break;
            }
        }
        $this->assertNotNull($updated);
        $this->assertSame('Cancelled', $updated['status']);

        TestDb::deleteAppointmentById((int) $found['id']);
    }

    public function test_service_history_only_includes_completed_appointments(): void
    {
        // Book two appointments for two different vehicles, since a single
        // vehicle can only have 2 *active* (non-Completed/Cancelled)
        // appointments at once — using separate plates sidesteps that
        // limit entirely and keeps this test independent of it.
        $completedPlate = $this->plate;
        $pendingPlate   = 'TEST-' . strtoupper(substr(uniqid(), -6)) . 'B';

        $completedBooking = $this->api->post('save_booking.php', [
            'vehicle' => [
                'brand' => 'Toyota', 'model' => 'Vios', 'year' => '2022',
                'plate' => $completedPlate, 'transmission' => 'Automatic', 'fuel' => 'Gasoline',
                'appointmentDate' => date('Y-m-d', strtotime('+2 days')),
            ],
            'services' => ['PMS'],
        ], ['Authorization' => "Bearer {$this->jwt}"]);
        $this->assertSame('success', $completedBooking['body']['status'], $completedBooking['raw']);

        $pendingBooking = $this->api->post('save_booking.php', [
            'vehicle' => [
                'brand' => 'Honda', 'model' => 'Civic', 'year' => '2021',
                'plate' => $pendingPlate, 'transmission' => 'Automatic', 'fuel' => 'Gasoline',
                'appointmentDate' => date('Y-m-d', strtotime('+3 days')),
            ],
            'services' => ['Change Oil'],
        ], ['Authorization' => "Bearer {$this->jwt}"]);
        $this->assertSame('success', $pendingBooking['body']['status'], $pendingBooking['raw']);

        $list = $this->api->get('get_appointments.php', ['Authorization' => "Bearer {$this->jwt}"]);
        $completedId = null;
        $pendingId   = null;
        foreach ($list['body']['appointments'] as $appt) {
            if (($appt['plate_number'] ?? null) === $completedPlate) {
                $completedId = (int) $appt['id'];
            }
            if (($appt['plate_number'] ?? null) === $pendingPlate) {
                $pendingId = (int) $appt['id'];
            }
        }
        $this->assertNotNull($completedId);
        $this->assertNotNull($pendingId);

        TestDb::setAppointmentStatus($completedId, 'Completed');

        $history = $this->api->get('get_service_history.php', ['Authorization' => "Bearer {$this->jwt}"]);
        $this->assertSame('success', $history['body']['status']);

        $historyIds = array_column($history['body']['history'], 'id');
        $this->assertContains($completedId, $historyIds);
        $this->assertNotContains($pendingId, $historyIds);

        TestDb::deleteAppointmentById($completedId);
        TestDb::deleteAppointmentById($pendingId);
        TestDb::deleteVehicleByPlate($pendingPlate);
    }
}

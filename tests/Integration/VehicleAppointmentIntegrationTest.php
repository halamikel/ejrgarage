<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Integration Test — Login Module x Vehicle Module x Appointment Booking Module
 *
 * Logs in as a real (pre-seeded, verified) user, registers a vehicle
 * through add_vehicle.php, confirms get_vehicles.php returns it, then
 * books an appointment through add_appointments.php and confirms it
 * shows up through get_appointments.php. This exercises the JWT issued
 * by the Login module being accepted by both downstream modules, and
 * the Vehicle module's data flowing correctly into the Appointment
 * module's booking + listing round trip.
 */
class VehicleAppointmentIntegrationTest extends TestCase
{
    private ApiClient $api;
    private string $jwt;
    private string $plate;

    /**
     * Update these to a real verified test account that already exists
     * in your TEST database (created once via the Registration module,
     * then TestDb::markUserVerified()'d — see RegistrationLoginIntegrationTest).
     */
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

    public function test_registered_vehicle_appears_in_vehicle_list(): void
    {
        $add = $this->api->post('add_vehicle.php', [
            'brand'        => 'Toyota',
            'model'        => 'Vios',
            'year'         => '2022',
            'plate'        => $this->plate,
            'transmission' => 'Automatic',
            'fuel'         => 'Gasoline',
        ], ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame('success', $add['body']['status']);

        $list = $this->api->get('get_vehicles.php', ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame('success', $list['body']['status']);
        $plates = array_column($list['body']['vehicles'], 'plate_number');
        $this->assertContains($this->plate, $plates);
    }

    public function test_appointment_can_be_booked_and_is_listed(): void
    {
        $this->api->post('add_vehicle.php', [
            'brand'        => 'Toyota',
            'model'        => 'Vios',
            'year'         => '2022',
            'plate'        => $this->plate,
            'transmission' => 'Automatic',
            'fuel'         => 'Gasoline',
        ], ['Authorization' => "Bearer {$this->jwt}"]);

        $booking = $this->api->post('add_appointments.php', [
            'services' => ['Oil Change', 'Tire Rotation'],
            'vehicle'  => [
                'brand' => 'Toyota',
                'model' => 'Vios',
                'year'  => '2022',
                'plate' => $this->plate,
            ],
            'date'  => date('Y-m-d', strtotime('+3 days')),
            'notes' => 'Integration test booking',
        ], ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame('success', $booking['body']['status']);

        $list = $this->api->get('get_appointments.php', ['Authorization' => "Bearer {$this->jwt}"]);

        $this->assertSame('success', $list['body']['status']);

        $found = array_filter(
            $list['body']['appointments'],
            fn ($appt) => str_contains($appt['vehicle_info'] ?? '', $this->plate)
                || str_contains($appt['notes'] ?? '', 'Integration test booking')
        );

        $this->assertNotEmpty($found, 'Booked appointment was not found in get_appointments.php response.');
    }
}

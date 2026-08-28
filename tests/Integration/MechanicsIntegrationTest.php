<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\ApiClient;
use Tests\Support\TestDb;

/**
 * Integration Test — Mechanics Module
 *
 * Exercises get_employees.php, get_mechanic_jobs.php,
 * update_availability.php, and update_job_status.php.
 *
 * These all require a role_id=3 (mechanic) account, which register.php
 * can never create (it hardcodes role_id=1), so this test creates and
 * tears down its own mechanic fixture directly via TestDb, on top of
 * the shared customer fixture used by the other integration tests.
 */
class MechanicsIntegrationTest extends TestCase
{
    private ApiClient $api;
    private int $mechanicUserId;
    private string $mechanicJwt;
    private string $customerJwt;
    private string $plate;

    private string $customerEmail = 'integration_test_fixture@example.com';
    private string $customerPassword = 'Password123!';

    /** @var int[] appointment ids created during a test */
    private array $createdAppointmentIds = [];

    protected function setUp(): void
    {
        $this->api = new ApiClient();
        $this->plate = 'TEST-' . strtoupper(substr(uniqid(), -6));

        // Mechanic fixture — created directly, since register.php can't.
        $mechEmail = 'integration_mechanic_' . uniqid() . '@example.com';
        $this->mechanicUserId = TestDb::insertTestUser([
            'full_name' => 'Integration Test Mechanic',
            'email'     => $mechEmail,
            'role_id'   => 3,
        ]);

        $mechLogin = $this->api->post('login.php', [
            'email'    => $mechEmail,
            'password' => 'Password123!',
        ]);
        $this->assertSame('success', $mechLogin['body']['status'] ?? null, $mechLogin['raw']);
        $this->mechanicJwt = $mechLogin['body']['token'];

        // Shared customer fixture, used to create a real appointment to assign.
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
        foreach ($this->createdAppointmentIds as $id) {
            TestDb::deleteAppointmentById($id);
        }
        TestDb::deleteVehicleByPlate($this->plate);
        TestDb::deleteMechanicRosterByUserId($this->mechanicUserId);
        TestDb::deleteUserById($this->mechanicUserId);
    }

    public function test_mechanic_appears_in_employee_directory(): void
    {
        $employees = $this->api->get('get_employees.php', ['Authorization' => "Bearer {$this->mechanicJwt}"]);

        $this->assertSame('success', $employees['body']['status'], $employees['raw']);
        $ids = array_column($employees['body']['employees'], 'id');
        $this->assertContains($this->mechanicUserId, $ids);
    }

    public function test_customer_account_is_forbidden_from_employee_directory(): void
    {
        $employees = $this->api->get('get_employees.php', ['Authorization' => "Bearer {$this->customerJwt}"]);

        $this->assertSame('error', $employees['body']['status']);
        $this->assertSame(403, $employees['status']);
    }

    public function test_mechanic_can_update_availability_and_it_reflects_in_directory(): void
    {
        $update = $this->api->post(
            'update_availability.php',
            ['status' => 'on_break'],
            ['Authorization' => "Bearer {$this->mechanicJwt}"]
        );
        $this->assertSame('success', $update['body']['status'], $update['raw']);
        $this->assertSame('on_break', $update['body']['mechanic_status']);

        $employees = $this->api->get('get_employees.php', ['Authorization' => "Bearer {$this->mechanicJwt}"]);
        $mine = null;
        foreach ($employees['body']['employees'] as $e) {
            if ((int) $e['id'] === $this->mechanicUserId) {
                $mine = $e;
                break;
            }
        }
        $this->assertNotNull($mine);
        $this->assertSame('on_break', $mine['mechanic_status']);
        $this->assertSame(0, $mine['is_available']);
    }

    public function test_mechanic_can_view_and_complete_an_assigned_job_but_not_an_unassigned_one(): void
    {
        // Triggers self-healing creation of this mechanic's roster row.
        $jobsBefore = $this->api->get('get_mechanic_jobs.php', ['Authorization' => "Bearer {$this->mechanicJwt}"]);
        $this->assertSame('success', $jobsBefore['body']['status'], $jobsBefore['raw']);

        $mechRosterId = TestDb::getMechanicRosterIdByUserId($this->mechanicUserId);
        $this->assertNotNull($mechRosterId, 'Mechanic roster row was not self-created by get_mechanic_jobs.php.');

        // Book an appointment as the customer, then assign it to this mechanic directly.
        $booking = $this->api->post('save_booking.php', [
            'vehicle' => [
                'brand' => 'Toyota', 'model' => 'Vios', 'year' => '2022',
                'plate' => $this->plate, 'transmission' => 'Automatic', 'fuel' => 'Gasoline',
                'appointmentDate' => date('Y-m-d', strtotime('+4 days')),
            ],
            'services' => ['Change Oil'],
        ], ['Authorization' => "Bearer {$this->customerJwt}"]);
        $this->assertSame('success', $booking['body']['status'], $booking['raw']);

        $custAppointments = $this->api->get('get_appointments.php', ['Authorization' => "Bearer {$this->customerJwt}"]);
        $appointmentId = null;
        foreach ($custAppointments['body']['appointments'] as $appt) {
            if (($appt['plate_number'] ?? null) === $this->plate) {
                $appointmentId = (int) $appt['id'];
                break;
            }
        }
        $this->assertNotNull($appointmentId);
        $this->createdAppointmentIds[] = $appointmentId;

        TestDb::assignAppointmentMechanic($appointmentId, $mechRosterId);

        $jobsAfter = $this->api->get('get_mechanic_jobs.php', ['Authorization' => "Bearer {$this->mechanicJwt}"]);
        $this->assertSame('success', $jobsAfter['body']['status']);
        $jobIds = array_column($jobsAfter['body']['jobs'], 'id');
        $this->assertContains($appointmentId, $jobIds);

        // Completing the assigned job should succeed.
        $complete = $this->api->post(
            'update_job_status.php',
            ['job_id' => $appointmentId, 'status' => 'Completed'],
            ['Authorization' => "Bearer {$this->mechanicJwt}"]
        );
        $this->assertSame('success', $complete['body']['status'], $complete['raw']);

        // A second, unassigned appointment should be rejected as "not yours".
        $otherPlate = 'TEST-' . strtoupper(substr(uniqid(), -6)) . 'X';
        $otherBooking = $this->api->post('save_booking.php', [
            'vehicle' => [
                'brand' => 'Honda', 'model' => 'Civic', 'year' => '2021',
                'plate' => $otherPlate, 'transmission' => 'Automatic', 'fuel' => 'Gasoline',
                'appointmentDate' => date('Y-m-d', strtotime('+6 days')),
            ],
            'services' => ['PMS'],
        ], ['Authorization' => "Bearer {$this->customerJwt}"]);
        $this->assertSame('success', $otherBooking['body']['status'], $otherBooking['raw']);

        $custAppointments2 = $this->api->get('get_appointments.php', ['Authorization' => "Bearer {$this->customerJwt}"]);
        $otherId = null;
        foreach ($custAppointments2['body']['appointments'] as $appt) {
            if (($appt['plate_number'] ?? null) === $otherPlate) {
                $otherId = (int) $appt['id'];
                break;
            }
        }
        $this->assertNotNull($otherId);
        $this->createdAppointmentIds[] = $otherId;

        $rejected = $this->api->post(
            'update_job_status.php',
            ['job_id' => $otherId, 'status' => 'Completed'],
            ['Authorization' => "Bearer {$this->mechanicJwt}"]
        );
        $this->assertSame('error', $rejected['body']['status']);
        $this->assertSame(403, $rejected['status']);

        TestDb::deleteVehicleByPlate($otherPlate);
    }
}

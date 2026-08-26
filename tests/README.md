# Integration Tests (PHPUnit)

These tests hit the real `api/*.php` endpoints over HTTP and verify that
modules work correctly together (e.g. Registration -> Login,
Login -> Vehicle -> Appointment Booking), then check the resulting
database state. They are black-box tests, not unit tests, because the
endpoints are request-handling scripts rather than classes.

## One-time setup

1. Install dependencies:
   ```bash
   composer install
   ```

2. Create a **separate test database** (same schema as production,
   e.g. `ejr_garage_test`). Never point these tests at production data.

3. Copy `api/.env` to `api/.env.testing` and change `DB_DATABASE`
   (and host/credentials if it's a different server) to point at that
   test database. `tests/Support/TestDb.php` reads this file directly.

4. Wire `api/env.php` to load `.env.testing` when `APP_ENV=testing` is
   set (the bootstrap sets this env var automatically), e.g.:
   ```php
   $envFile = getenv('APP_ENV') === 'testing' ? '.env.testing' : '.env';
   ```

5. Seed one verified fixture user in the test DB for
   `VehicleAppointmentIntegrationTest` (email/password are set at the
   top of that class) — easiest way is to run
   `RegistrationLoginIntegrationTest` once against the test DB and then
   leave that user in place, or insert it manually.

## Running the tests

In one terminal, serve the app with PHP's built-in server, pointed at
the test environment:

```bash
APP_ENV=testing php -S localhost:8000 -t .
```

In another terminal:

```bash
vendor/bin/phpunit --testdox
```

`--testdox` prints a readable pass/fail per test method — useful for
pasting into Appendix H (Detailed Integration Test Report), while
Table 15 / Table 16 in the manuscript just need the module names and a
Passed/Failed remark per row.

## What each test covers

| Test class | Modules integrated |
|---|---|
| `RegistrationLoginIntegrationTest` | Registration Module ↔ Login Module |
| `VehicleAppointmentIntegrationTest` | Login Module ↔ Vehicle Module ↔ Appointment Booking Module |

Add one test class per module pair you need to document, following the
same pattern: call the real endpoint(s) via `ApiClient`, assert on the
JSON response, and use `TestDb` only for setup/teardown steps the API
doesn't expose (like marking a user verified).

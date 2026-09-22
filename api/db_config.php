<?php
/**
 * EJR Garage — Database Configuration
 * Render + TiDB Cloud Starter (SSL)
 */

require_once __DIR__ . '/env.php';

// ----------------------------------------------------------------
// getallheaders() polyfill
// ----------------------------------------------------------------
if (!function_exists('getallheaders')) {
    function getallheaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headers[str_replace('_', '-', $key)] = $value;
            }
        }

        return $headers;
    }
}

// ----------------------------------------------------------------
// Enable MySQLi Exceptions
// ----------------------------------------------------------------
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ----------------------------------------------------------------
// Validate Required Constants
// ----------------------------------------------------------------
$required = [
    'DB_HOST'     => DB_HOST,
    'DB_PORT'     => DB_PORT,
    'DB_DATABASE' => DB_DATABASE,
    'DB_USERNAME' => DB_USERNAME,
    'DB_PASSWORD' => DB_PASSWORD,
];

foreach ($required as $key => $value) {
    if ($value === null || $value === '') {
        http_response_code(500);
        header('Content-Type: application/json');

        echo json_encode([
            "status" => "error",
            "message" => "Missing environment variable: {$key}"
        ]);

        exit;
    }
}

// ----------------------------------------------------------------
// Connect to TiDB Cloud using SSL
// ----------------------------------------------------------------
try {

    $cert = __DIR__ . '/../certificates/isrgrootx1.pem';

    if (!file_exists($cert)) {
        throw new Exception("CA certificate not found: {$cert}");
    }

    $conn = mysqli_init();

    if (!$conn) {
        throw new Exception("Unable to initialize MySQLi.");
    }

    mysqli_ssl_set(
        $conn,
        null,
        null,
        $cert,
        null,
        null
    );

    if (!mysqli_real_connect(
        $conn,
        DB_HOST,
        DB_USERNAME,
        DB_PASSWORD,
        DB_DATABASE,
        (int) DB_PORT,
        null,
        MYSQLI_CLIENT_SSL
    )) {
        throw new Exception(mysqli_connect_error());
    }

    $conn->set_charset("utf8mb4");
    $conn->query("SET time_zone = '+08:00'");

} catch (Throwable $e) {

    error_log("Database Error: " . $e->getMessage());

    header('Content-Type: application/json');
    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

    exit;
}

// ----------------------------------------------------------------
// Soft-delete / Archive helpers
//
// Instead of ever running DELETE FROM on a record the admin removes,
// every admin "delete" action should archive the row in place. These
// helpers self-heal the required columns (same self-healing pattern
// already used elsewhere in this codebase, e.g. get_full_site_data.php)
// and give every admin endpoint one consistent way to archive/restore.
// ----------------------------------------------------------------

/**
 * Ensure a table has the columns needed to support archiving:
 *   is_archived  TINYINT(1)  - 0 = active, 1 = archived
 *   archived_at  DATETIME    - when it was archived
 *   archived_by  INT         - admin user_id who archived it
 *
 * Safe to call on every request; it's a cheap SHOW COLUMNS check and
 * only ALTERs the table the first time it's missing a column.
 */
function ejr_ensure_archive_columns(mysqli $conn, string $table): void
{
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    $cols = [
        'is_archived' => "ALTER TABLE `{$table}` ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0",
        'archived_at' => "ALTER TABLE `{$table}` ADD COLUMN archived_at DATETIME NULL",
        'archived_by' => "ALTER TABLE `{$table}` ADD COLUMN archived_by INT NULL",
    ];

    foreach ($cols as $col => $alterSql) {
        $check = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
        if ($check && $check->num_rows === 0) {
            $conn->query($alterSql);
        }
    }
}

/**
 * Archive a single row (by id) instead of deleting it.
 * Returns true if a row was archived.
 */
function ejr_archive_row(mysqli $conn, string $table, int $id, ?int $adminId = null): bool
{
    ejr_ensure_archive_columns($conn, $table);
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    $stmt = $conn->prepare("
        UPDATE `{$table}`
        SET is_archived = 1,
            archived_at = NOW(),
            archived_by = ?
        WHERE id = ?
          AND (is_archived IS NULL OR is_archived = 0)
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ii", $adminId, $id);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}

/**
 * Archive every row matching a WHERE column = value condition
 * (e.g. archive every vehicle belonging to a user being archived).
 * Returns the number of rows archived.
 */
function ejr_archive_where(mysqli $conn, string $table, string $whereCol, int $whereVal, ?int $adminId = null): int
{
    ejr_ensure_archive_columns($conn, $table);
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $whereCol = preg_replace('/[^a-zA-Z0-9_]/', '', $whereCol);

    $stmt = $conn->prepare("
        UPDATE `{$table}`
        SET is_archived = 1,
            archived_at = NOW(),
            archived_by = ?
        WHERE `{$whereCol}` = ?
          AND (is_archived IS NULL OR is_archived = 0)
    ");

    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("ii", $adminId, $whereVal);
    $stmt->execute();

    return $stmt->affected_rows;
}

/**
 * Auto-approve parts orders.
 *
 * Orders sit as 'pending' until an admin approves them. To avoid needing
 * a real cron job on shared hosting, any endpoint that reads orders
 * (admin dashboard, the customer's "My Orders" page) calls this first.
 * It's a cheap, idempotent UPDATE — any 'pending' order older than the
 * configured window flips to 'approved' automatically.
 */
function ejr_auto_approve_pending_orders(mysqli $conn, int $minutes = 5): void
{
    $minutes = max(1, $minutes);
    $conn->query("
        UPDATE part_orders
        SET status = 'approved'
        WHERE status = 'pending'
          AND created_at <= (NOW() - INTERVAL {$minutes} MINUTE)
    ");
}

/**
 * Saved customer profiles (name / contact number / delivery address).
 *
 * Used by the "My Cart" checkout form and the booking "Your Details" step
 * so a customer can save multiple name + contact + address combinations
 * (e.g. "Home", "Office") and reuse them instead of retyping every time —
 * same idea as the saved vehicles ("My Garage") feature.
 *
 * Self-healing: safe to call on every request, only CREATEs the table
 * the first time it's missing (same pattern as the archive helpers above).
 */
function ejr_ensure_customer_profiles_table(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS customer_profiles (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            user_id         INT NOT NULL,
            label           VARCHAR(50)  NULL,
            full_name       VARCHAR(150) NOT NULL,
            contact_number  VARCHAR(15)  NOT NULL,
            address_line1   VARCHAR(255) NOT NULL,
            address_line2   VARCHAR(255) NULL,
            city            VARCHAR(100) NOT NULL,
            province        VARCHAR(100) NOT NULL,
            zip_code        VARCHAR(10)  NOT NULL,
            is_default      TINYINT(1)   NOT NULL DEFAULT 0,
            created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id)
        )
    ");
}

/**
 * Restore a previously archived row.
 */
function ejr_restore_row(mysqli $conn, string $table, int $id): bool
{
    ejr_ensure_archive_columns($conn, $table);
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    $stmt = $conn->prepare("
        UPDATE `{$table}`
        SET is_archived = 0,
            archived_at = NULL,
            archived_by = NULL
        WHERE id = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}
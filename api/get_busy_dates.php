<?php
header('Content-Type: application/json');
require_once 'db_config.php';

// Sync appointment_dates from actual appointments table
$conn->query("
    INSERT INTO appointment_dates (appointment_date, current_bookings, is_busy)
    SELECT 
        DATE(a.appointment_date),
        COUNT(*),
        0
    FROM appointments a
    WHERE a.status NOT IN ('Cancelled')
    AND a.appointment_date >= CURDATE()
    GROUP BY DATE(a.appointment_date)
    ON DUPLICATE KEY UPDATE
        current_bookings = VALUES(current_bookings),
        is_busy = IF(VALUES(current_bookings) >= slot_limit, 1, is_busy)
");

// Fetch busy dates
$result = $conn->query("
    SELECT appointment_date as date 
    FROM appointment_dates 
    WHERE is_busy = 1 
    AND appointment_date >= CURDATE()
");

$busyDates = [];
while ($row = $result->fetch_assoc()) {
    $busyDates[] = $row['date'];
}

echo json_encode(['status' => 'success', 'busy_dates' => $busyDates]);
?>
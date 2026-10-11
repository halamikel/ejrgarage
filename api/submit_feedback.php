<?php
/**
 * EJR Garage — Submit service feedback (customer app)
 *
 * Body: {appointment_id: int, rating: 1-5, comment?: string}
 * Saves one review per completed appointment and awards 10 EJR points
 * (once — the points ledger's UNIQUE key makes it idempotent).
 * Response: {status, message, points_awarded}
 */
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'points_helper.php';

$userId = ejr_require_user_id();

$input         = json_decode(file_get_contents('php://input'), true) ?? [];
$appointmentId = (int)($input['appointment_id'] ?? 0);
$rating        = (int)($input['rating'] ?? 0);
$comment       = trim((string)($input['comment'] ?? ''));
if (function_exists('mb_substr')) $comment = mb_substr($comment, 0, 1000);

if ($appointmentId <= 0 || $rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid appointment or rating.']);
    exit;
}

try {
    // Only the owner of a Completed appointment can review it.
    $s = $conn->prepare("SELECT status FROM appointments WHERE id = ? AND user_id = ? LIMIT 1");
    $s->bind_param("ii", $appointmentId, $userId);
    $s->execute();
    $appt = $s->get_result()->fetch_assoc();
    if (!$appt) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Appointment not found.']);
        exit;
    }
    if (strcasecmp((string)$appt['status'], 'Completed') !== 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Only completed appointments can be reviewed.']);
        exit;
    }

    $conn->query("
        CREATE TABLE IF NOT EXISTS appointment_feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            appointment_id INT NOT NULL,
            user_id INT NOT NULL,
            rating TINYINT NOT NULL,
            comment VARCHAR(1000) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_feedback_appt (appointment_id)
        )
    ");

    $ins = $conn->prepare("INSERT IGNORE INTO appointment_feedback (appointment_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $ins->bind_param("iiis", $appointmentId, $userId, $rating, $comment);
    $ins->execute();

    // Award even if the review row already existed: covers a first attempt
    // that saved the review but failed before crediting.
    $awarded = ejr_award_review_points($conn, $userId, $appointmentId);

    echo json_encode([
        'status'         => 'success',
        'message'        => 'Thank you for your feedback!',
        'points_awarded' => $awarded,
    ]);
} catch (Throwable $e) {
    error_log('EJR submit_feedback: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Could not save your feedback.']);
}

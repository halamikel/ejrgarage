<?php
/**
 * EJR Garage — Admin: Manage Mechanic Leave Requests
 *
 * Backs the "Leave Requests" section of the admin dashboard's Mechanics
 * tab (js/admin_dashboard.js's loadLeaveRequests()/approveLeaveRequest()/
 * rejectLeaveRequest()). Requests themselves are filed by mechanics via
 * ../submit_leave_request.php (same JWT role=3 gate + self-healing
 * mechanic<->user linkage pattern as update_availability.php).
 *
 * GET  (action=list, default): every leave request, pending first.
 * POST action=approve: marks the request 'approved'. The mechanic's
 *                       roster status is NOT force-changed here —
 *                       includes/leave_sync.php's ejr_sync_leave_statuses()
 *                       (called below, and at the top of every endpoint
 *                       that reads mechanic status) derives 'on_break'
 *                       from the request's start_date/end_date instead,
 *                       so leave takes effect on the actual requested
 *                       start date (not the moment it's approved) and
 *                       automatically reverts to 'available' once the
 *                       end date passes — no manual admin follow-up.
 * POST action=reject:  marks the request 'rejected'; no roster change.
 * POST action=delete:  removes the request row entirely (housekeeping).
 */
header('Content-Type: application/json');
require_once '../db_config.php';
require_once '../../includes/php-jwt/JWT.php';
require_once '../../includes/php-jwt/Key.php';
require_once '../../includes/leave_sync.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ── JWT Admin Gate ─────────────────────────────────────────────
$headers    = array_change_key_case(getallheaders(), CASE_LOWER);
$auth       = $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$adminId    = 0; $isAdmin = false;
if (preg_match('/bearer\s(\S+)/i', $auth, $m)) {
    try {
        $decoded = JWT::decode($m[1], new Key(JWT_SECRET, 'HS256'));
        $adminId = (int)($decoded->uid ?? 0);
        $role    = (int)($decoded->role ?? 0);
        $isAdmin = ($role === 2 || $adminId === 21);
    } catch (Exception $e) {}
}
if (!$isAdmin) { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Forbidden']); exit; }

// ── Auto-create leave_requests table ─────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS leave_requests (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    mechanic_id    INT NOT NULL,
    mechanic_name  VARCHAR(100) NOT NULL,
    start_date     DATE NOT NULL,
    end_date       DATE NOT NULL,
    reason         TEXT,
    status         ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_notes    VARCHAR(255) DEFAULT NULL,
    requested_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at    TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

ejr_ensure_archive_columns($conn, 'leave_requests');

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? 'list';

// Re-derive every mechanic's on-leave status from today's date before
// doing anything else, so the list (and any status this endpoint
// implies) is never stale.
ejr_sync_leave_statuses($conn);

if ($method === 'GET') {
    if ($action === 'list') {
        $result = $conn->query("SELECT * FROM leave_requests
            WHERE (is_archived IS NULL OR is_archived = 0)
            ORDER BY (status = 'pending') DESC, requested_at DESC");
        $rows = [];
        while ($r = $result->fetch_assoc()) $rows[] = $r;
        echo json_encode(['status'=>'success','leave_requests'=>$rows]);
    }
    exit;
}

if ($method === 'POST') {
    $id = (int)($input['id'] ?? 0);
    if (!$id && in_array($action, ['approve','reject','delete'], true)) {
        echo json_encode(['status'=>'error','message'=>'Missing request ID.']); exit;
    }

    switch ($action) {
        case 'approve': {
            $reqStmt = $conn->prepare("SELECT mechanic_id, mechanic_name, status FROM leave_requests WHERE id = ?");
            $reqStmt->bind_param("i", $id);
            $reqStmt->execute();
            $reqRow = $reqStmt->get_result()->fetch_assoc();
            if (!$reqRow) { echo json_encode(['status'=>'error','message'=>'Leave request not found.']); exit; }
            if ($reqRow['status'] !== 'pending') {
                echo json_encode(['status'=>'error','message'=>'This request has already been '.$reqRow['status'].'.']); exit;
            }

            $upd = $conn->prepare("UPDATE leave_requests SET status='approved', resolved_at=NOW() WHERE id=?");
            $upd->bind_param("i", $id);
            $upd->execute();

            // Re-sync roster statuses against every approved leave's
            // actual date range. If this leave's start_date is today
            // (or already in progress), the mechanic flips to
            // 'on_break' right now; if it starts later, they stay
            // whatever they currently are until that date arrives (the
            // sync re-runs on every subsequent admin/mechanic read).
            ejr_sync_leave_statuses($conn);

            echo json_encode(['status'=>'success','message'=>$reqRow['mechanic_name'].'\'s leave request was approved.']);
            break;
        }

        case 'reject': {
            $adminNotes = trim((string)($input['admin_notes'] ?? ''));

            $reqStmt = $conn->prepare("SELECT mechanic_name, status FROM leave_requests WHERE id = ?");
            $reqStmt->bind_param("i", $id);
            $reqStmt->execute();
            $reqRow = $reqStmt->get_result()->fetch_assoc();
            if (!$reqRow) { echo json_encode(['status'=>'error','message'=>'Leave request not found.']); exit; }
            if ($reqRow['status'] !== 'pending') {
                echo json_encode(['status'=>'error','message'=>'This request has already been '.$reqRow['status'].'.']); exit;
            }

            $upd = $conn->prepare("UPDATE leave_requests SET status='rejected', admin_notes=?, resolved_at=NOW() WHERE id=?");
            $upd->bind_param("si", $adminNotes, $id);
            $upd->execute();

            echo json_encode(['status'=>'success','message'=>$reqRow['mechanic_name'].'\'s leave request was declined.']);
            break;
        }

        case 'delete': {
            if (ejr_archive_row($conn, 'leave_requests', $id, $adminId)) {
                echo json_encode(['status'=>'success']);
            } else {
                echo json_encode(['status'=>'error','message'=>'Leave request not found or already archived.']);
            }
            break;
        }

        default:
            echo json_encode(['status'=>'error','message'=>'Unknown action.']);
    }
}
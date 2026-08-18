<?php
/**
 * includes/leave_sync.php
 *
 * Keeps mechanics.status in sync with APPROVED leave_requests date
 * ranges (start_date/end_date), instead of the old behavior where
 * approving a leave request immediately forced the mechanic to
 * 'on_break' regardless of whether their requested leave had actually
 * started yet — and never reverted them back to 'available' once the
 * leave period ended.
 *
 * There's no cron/scheduled task on this host, so this uses a "lazy
 * sync" pattern instead: ejr_sync_leave_statuses($conn) re-derives
 * every mechanic's on-leave state from today's date each time it's
 * called, and is cheap enough (two set-based UPDATE...JOIN queries,
 * no loops) to call at the top of any endpoint that reads or shows
 * mechanic status — admin mechanics list, drawer counts, dashboard
 * stats, leave requests list/approve, the mechanic's own job list,
 * etc. Call it BEFORE reading `mechanics.status` in those endpoints.
 *
 * Rules:
 *   - A mechanic goes to 'on_break' the moment CURDATE() falls within
 *     [start_date, end_date] of one of their APPROVED leave requests
 *     (checked on every call, so this "activates" automatically on
 *     the requested start date without any admin action).
 *   - A mechanic on 'on_break' reverts to 'available' automatically
 *     once none of their approved leave requests cover today anymore
 *     (i.e. the moment the end_date has passed).
 *   - Mechanics who are 'unavailable' or 'on_duty' for reasons
 *     unrelated to leave are left alone by the revert step — only
 *     rows already sitting at 'on_break' are ever reverted, and only
 *     when no approved leave currently covers them.
 *
 * Requires: $conn (mysqli) and the `leave_requests` / `mechanics`
 * tables to already exist (both are self-healing-created elsewhere,
 * e.g. api/admin/manage_leave_requests.php and
 * api/admin/manage_mechanics.php).
 */

function ejr_sync_leave_statuses(mysqli $conn): void {
    // Make sure both tables exist even if this is the very first call
    // (e.g. hit via drawer_counts.php before any leave request has
    // ever been filed).
    $conn->query("CREATE TABLE IF NOT EXISTS mechanics (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NULL,
        name       VARCHAR(100) NOT NULL,
        specialty  VARCHAR(150) DEFAULT '',
        status     ENUM('available','unavailable','on_break','on_duty') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

    // ── Activate ──────────────────────────────────────────────────
    // Any mechanic with an APPROVED leave request whose range covers
    // today gets flipped to 'on_break', no matter what they were
    // doing before (available/unavailable/on_duty). This is what
    // makes leave "start" automatically on the requested start_date
    // instead of the moment an admin clicks Approve.
    $conn->query("
        UPDATE mechanics m
        INNER JOIN leave_requests lr ON lr.mechanic_id = m.id
        SET m.status = 'on_break'
        WHERE lr.status = 'approved'
          AND CURDATE() BETWEEN lr.start_date AND lr.end_date
          AND m.status != 'on_break'
    ");

    // ── Deactivate ────────────────────────────────────────────────
    // Any mechanic currently sitting at 'on_break' who no longer has
    // an approved leave request covering today gets reverted back to
    // 'available' — this is what makes leave "end" automatically on
    // the day after end_date, without an admin having to remember to
    // edit them back.
    $conn->query("
        UPDATE mechanics m
        SET m.status = 'available'
        WHERE m.status = 'on_break'
          AND NOT EXISTS (
              SELECT 1 FROM leave_requests lr
              WHERE lr.mechanic_id = m.id
                AND lr.status = 'approved'
                AND CURDATE() BETWEEN lr.start_date AND lr.end_date
          )
    ");
}

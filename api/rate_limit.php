<?php
/**
 * EJR Garage — Rate Limiter
 *
 * OWASP A07: Identification & Authentication Failures
 * OWASP A05: Security Misconfiguration
 * ─────────────────────────────────────────────────────────────
 * Strategy: DB-backed sliding window, keyed by IP + action.
 * Why DB and not APCu/Redis? InfinityFree shared hosting has no
 * shared memory between PHP workers, so $_SESSION and APCu are
 * per-process and unreliable for rate limiting. The DB is the
 * only persistent store available on this tier.
 *
 * InfinityFree compatibility note:
 *  - Uses the existing $conn MySQLi connection (passed in).
 *  - Table is auto-created on first call (CREATE TABLE IF NOT EXISTS).
 *  - Old rows are pruned on ~5% of calls to avoid a cron dependency.
 *
 * Usage:
 *   require_once 'rate_limit.php';
 *   ejr_rate_limit($conn, 'login',    $ip, 10, 900);  // 10 attempts per 15 min
 *   ejr_rate_limit($conn, 'register', $ip,  5, 3600); // 5 per hour
 *   ejr_rate_limit($conn, 'otp',      $ip,  3, 600);  // 3 per 10 min
 *
 * On breach: emits HTTP 429 JSON and exits. Caller gets nothing more to do.
 */

/**
 * Get the real client IP, accounting for InfinityFree's reverse proxy.
 * OWASP: never trust X-Forwarded-For from arbitrary clients — but
 * InfinityFree's own load balancer sets it, so we read it here.
 * We take only the FIRST (leftmost) address which is the actual client.
 */
function ejr_get_ip(): string {
    $candidates = [
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['HTTP_X_REAL_IP']       ?? '',
        $_SERVER['REMOTE_ADDR']          ?? '',
    ];
    foreach ($candidates as $candidate) {
        $ip = trim(explode(',', $candidate)[0]);
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
    }
    // Fall back to REMOTE_ADDR even if it's a private IP (local/proxy)
    return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')[0]);
}

/**
 * Check and record a rate-limit attempt.
 *
 * @param mysqli $conn       Active DB connection
 * @param string $action     Logical action name, e.g. 'login', 'register'
 * @param string $key        Usually the client IP; can be IP+email for user-scoped limits
 * @param int    $maxAttempts Max hits allowed in the window
 * @param int    $windowSecs  Sliding window size in seconds
 */
function ejr_rate_limit(mysqli $conn, string $action, string $key, int $maxAttempts, int $windowSecs): void {
    // Ensure table exists (runs fast after first call due to IF NOT EXISTS)
    $conn->query("
        CREATE TABLE IF NOT EXISTS rate_limits (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            action     VARCHAR(64)  NOT NULL,
            hit_key    VARCHAR(128) NOT NULL,
            hit_time   INT          NOT NULL,
            INDEX idx_action_key (action, hit_key),
            INDEX idx_hit_time   (hit_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $now       = time();
    $windowStart = $now - $windowSecs;
    $safeKey   = substr($key,    0, 128);
    $safeAction= substr($action, 0, 64);

    // Prune old rows on ~5% of requests (avoids needing a cron job)
    if (mt_rand(1, 20) === 1) {
        $conn->query("DELETE FROM rate_limits WHERE hit_time < " . (int)($now - 86400));
    }

    // Count recent hits for this action+key within the window
    $stmt = $conn->prepare("SELECT COUNT(*) FROM rate_limits WHERE action = ? AND hit_key = ? AND hit_time >= ?");
    $stmt->bind_param("ssi", $safeAction, $safeKey, $windowStart);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count >= $maxAttempts) {
        // Tell the client how long to wait (earliest hit + window = when they're clear)
        $earliest = $conn->prepare("SELECT MIN(hit_time) FROM rate_limits WHERE action = ? AND hit_key = ? AND hit_time >= ?");
        $earliest->bind_param("ssi", $safeAction, $safeKey, $windowStart);
        $earliest->execute();
        $earliest->bind_result($minTime);
        $earliest->fetch();
        $earliest->close();

        $retryAfter = max(1, ($minTime + $windowSecs) - $now);
        header('Retry-After: ' . $retryAfter);
        http_response_code(429);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Too many attempts. Please wait ' . ceil($retryAfter / 60) . ' minute(s) before trying again.',
            'retry_after' => $retryAfter,
        ]);
        exit;
    }

    // Record this attempt
    $insert = $conn->prepare("INSERT INTO rate_limits (action, hit_key, hit_time) VALUES (?, ?, ?)");
    $insert->bind_param("ssi", $safeAction, $safeKey, $now);
    $insert->execute();
    $insert->close();
}

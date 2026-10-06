<?php

/**
 * EJR Garage Voucher Helper
 *
 * All voucher validation/calculation should happen server-side.
 */

function ejr_ensure_voucher_tables(mysqli $conn): void
{
    $conn->query("
        CREATE TABLE IF NOT EXISTS vouchers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            description VARCHAR(255) NULL,
            discount_type ENUM('percentage', 'fixed') NOT NULL,
            discount_value DECIMAL(10,2) NOT NULL,
            minimum_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            maximum_discount DECIMAL(10,2) NULL,
            usage_limit INT NULL,
            usage_count INT NOT NULL DEFAULT 0,
            per_user_limit INT NOT NULL DEFAULT 1,
            starts_at DATETIME NULL,
            expires_at DATETIME NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_voucher_code (code),
            INDEX idx_voucher_active (is_active)
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS voucher_redemptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            voucher_id INT NOT NULL,
            user_id INT NOT NULL,
            order_id INT NULL,
            discount_amount DECIMAL(10,2) NOT NULL,
            redeemed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_voucher_user_order (
                voucher_id,
                user_id,
                order_id
            ),
            INDEX idx_voucher_user (
                voucher_id,
                user_id
            ),
            INDEX idx_voucher_order (
                order_id
            )
        )
    ");

    $columns = [
        'voucher_id' => "ALTER TABLE part_orders ADD COLUMN voucher_id INT NULL",
        'voucher_code' => "ALTER TABLE part_orders ADD COLUMN voucher_code VARCHAR(50) NULL",
        'voucher_discount' => "ALTER TABLE part_orders ADD COLUMN voucher_discount DECIMAL(10,2) NOT NULL DEFAULT 0"
    ];

    foreach ($columns as $column => $sql) {
        $check = $conn->query(
            "SHOW COLUMNS FROM part_orders LIKE '" .
            $conn->real_escape_string($column) .
            "'"
        );

        if ($check && $check->num_rows === 0) {
            $conn->query($sql);
        }
    }
}


function ejr_get_voucher(
    mysqli $conn,
    string $code,
    int $user_id,
    float $subtotal,
    bool $lock = false
): array {

    $code = strtoupper(trim($code));

    if ($code === '') {
        throw new Exception('Please enter a voucher code.');
    }

    if ($subtotal <= 0) {
        throw new Exception('Invalid order amount.');
    }

    $sql = "
        SELECT *
        FROM vouchers
        WHERE code = ?
        LIMIT 1
    ";

    if ($lock) {
        $sql .= " FOR UPDATE";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();

    $voucher = $stmt->get_result()->fetch_assoc();

    if (!$voucher) {
        throw new Exception('Voucher code not found.');
    }

    if ((int)$voucher['is_active'] !== 1) {
        throw new Exception('This voucher is no longer active.');
    }

    $now = time();

    if (!empty($voucher['starts_at'])) {
        if ($now < strtotime($voucher['starts_at'])) {
            throw new Exception('This voucher is not available yet.');
        }
    }

    if (!empty($voucher['expires_at'])) {
        if ($now > strtotime($voucher['expires_at'])) {
            throw new Exception('This voucher has expired.');
        }
    }

    if ($subtotal < (float)$voucher['minimum_amount']) {
        throw new Exception(
            'Minimum purchase for this voucher is ₱' .
            number_format((float)$voucher['minimum_amount'], 2)
        );
    }

    if ($voucher['usage_limit'] !== null) {
        if ((int)$voucher['usage_count'] >= (int)$voucher['usage_limit']) {
            throw new Exception('This voucher has reached its usage limit.');
        }
    }

    /*
     * Per-user usage limit.
     */
    $usageStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM voucher_redemptions
        WHERE voucher_id = ?
          AND user_id = ?
    ");

    $voucherId = (int)$voucher['id'];

    $usageStmt->bind_param(
        "ii",
        $voucherId,
        $user_id
    );

    $usageStmt->execute();

    $usage = $usageStmt->get_result()->fetch_assoc();

    if (
        (int)$usage['total'] >=
        (int)$voucher['per_user_limit']
    ) {
        throw new Exception(
            'You have already used this voucher the maximum number of times.'
        );
    }

    /*
     * Calculate discount.
     */
    $discount = 0;

    if ($voucher['discount_type'] === 'percentage') {

        $discount =
            $subtotal *
            ((float)$voucher['discount_value'] / 100);

    } else {

        $discount =
            (float)$voucher['discount_value'];
    }

    /*
     * Never discount more than the order.
     */
    $discount = min(
        $discount,
        $subtotal
    );

    /*
     * Maximum discount.
     */
    if ($voucher['maximum_discount'] !== null) {

        $discount = min(
            $discount,
            (float)$voucher['maximum_discount']
        );
    }

    $discount = round(
        max(0, $discount),
        2
    );

    $total = round(
        max(0, $subtotal - $discount),
        2
    );

    return [
        'id' => (int)$voucher['id'],
        'code' => $voucher['code'],
        'discount_type' => $voucher['discount_type'],
        'discount_value' => (float)$voucher['discount_value'],
        'discount' => $discount,
        'subtotal' => round($subtotal, 2),
        'total' => $total
    ];
}
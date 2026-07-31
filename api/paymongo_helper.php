<?php
/**
 * EJR Garage — PayMongo Helper
 * Key loaded from env.php — never hardcoded here.
 */
if (!defined('PAYMONGO_SECRET_KEY')) require_once __DIR__ . '/env.php';

function paymongo_request(string $method, string $endpoint, array $body = []): array {
    $ch = curl_init('https://api.paymongo.com/v1' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
            'Content-Type: application/json',
            'accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if (!empty($body)) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) return ['ok' => false, 'error' => $curlError, 'code' => 0];

    $decoded = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300) return ['ok' => true, 'data' => $decoded['data'] ?? null, 'code' => $httpCode];
    return ['ok' => false, 'error' => $decoded['errors'][0]['detail'] ?? $response, 'code' => $httpCode];
}

function paymongo_create_checkout(int $orderId, string $description, float $totalAmount, string $successUrl, string $cancelUrl): array {
    $result = paymongo_request('POST', '/checkout_sessions', ['data' => ['attributes' => [
        'send_email_receipt'   => false,
        'show_description'     => true,
        'show_line_items'      => true,
        'description'          => $description,
        'reference_number'     => (string)$orderId,
        'payment_method_types' => ['card', 'qrph'],
        'success_url'          => $successUrl,
        'cancel_url'           => $cancelUrl,
        'line_items'           => [['name' => $description, 'amount' => (int)round($totalAmount * 100), 'currency' => 'PHP', 'quantity' => 1]],
    ]]]);
    if (!$result['ok']) return ['ok' => false, 'error' => $result['error']];
    return ['ok' => true, 'checkout_url' => $result['data']['attributes']['checkout_url'] ?? null, 'checkout_id' => $result['data']['id'] ?? null];
}

function paymongo_get_checkout(string $checkoutId): ?array {
    $result = paymongo_request('GET', '/checkout_sessions/' . $checkoutId);
    return $result['ok'] ? $result['data'] : null;
}

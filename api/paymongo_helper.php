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

/**
 * QR Ph (in-app) — Payment Intent workflow.
 * Docs: https://docs.paymongo.com/docs/payment-acceptance-qr-ph-api
 *
 * All three calls below are made server-side with the Secret Key, which
 * PayMongo's docs explicitly say is fine ("If calling the Attach API will
 * be on the server side, you may use your Secret API Keys and the
 * client_key will not be required") — so the app never sees any PayMongo
 * key, only our own JWT-authenticated endpoints.
 */
function paymongo_create_payment_intent(int $orderId, string $description, float $amount): array {
    $result = paymongo_request('POST', '/payment_intents', ['data' => ['attributes' => [
        'amount'                 => (int) round($amount * 100), // centavos
        'currency'                => 'PHP',
        'payment_method_allowed'  => ['qrph'],
        'description'             => $description,
        'metadata'                => ['order_id' => (string) $orderId],
    ]]]);
    if (!$result['ok']) return ['ok' => false, 'error' => $result['error']];
    return [
        'ok'         => true,
        'id'         => $result['data']['id'] ?? null,
        'client_key' => $result['data']['attributes']['client_key'] ?? null,
    ];
}

function paymongo_create_qrph_method(): array {
    $result = paymongo_request('POST', '/payment_methods', ['data' => ['attributes' => [
        'type' => 'qrph',
    ]]]);
    if (!$result['ok']) return ['ok' => false, 'error' => $result['error']];
    return ['ok' => true, 'id' => $result['data']['id'] ?? null];
}

function paymongo_attach_payment_method(string $intentId, string $paymentMethodId): array {
    $result = paymongo_request('POST', "/payment_intents/{$intentId}/attach", ['data' => ['attributes' => [
        'payment_method' => $paymentMethodId,
    ]]]);
    if (!$result['ok']) return ['ok' => false, 'error' => $result['error']];
    $attrs = $result['data']['attributes'] ?? [];
    return [
        'ok'       => true,
        'status'   => $attrs['status'] ?? null,
        'qr_image' => $attrs['next_action']['code']['image_url'] ?? null,
    ];
}

// Runs the full 3-step QR Ph generation (intent -> method -> attach) for
// an order that already exists in part_orders. Returns the QR image plus
// the payment_intent id so it can be stored for later status polling.
function paymongo_generate_qrph(int $orderId, string $description, float $amount): array {
    $intent = paymongo_create_payment_intent($orderId, $description, $amount);
    if (!$intent['ok'] || !$intent['id']) {
        return ['ok' => false, 'error' => $intent['error'] ?? 'Could not create payment intent.'];
    }

    $method = paymongo_create_qrph_method();
    if (!$method['ok'] || !$method['id']) {
        return ['ok' => false, 'error' => $method['error'] ?? 'Could not create QR Ph payment method.'];
    }

    $attach = paymongo_attach_payment_method($intent['id'], $method['id']);
    if (!$attach['ok'] || !$attach['qr_image']) {
        return ['ok' => false, 'error' => $attach['error'] ?? 'Could not generate QR code.'];
    }

    return [
        'ok'                => true,
        'payment_intent_id' => $intent['id'],
        'qr_image'          => $attach['qr_image'],
        'status'            => $attach['status'],
    ];
}

// Poll a Payment Intent's current status. Returns the raw 'status' string
// PayMongo reports: awaiting_payment_method | awaiting_next_action |
// processing | succeeded.
function paymongo_get_payment_intent_status(string $intentId): ?string {
    $result = paymongo_request('GET', "/payment_intents/{$intentId}");
    if (!$result['ok']) return null;
    return $result['data']['attributes']['status'] ?? null;
}
<?php

header('Content-Type: application/json');

require_once 'db_config.php';
require_once 'rate_limit.php';
require_once 'paymongo_helper.php';

require_once '../includes/php-jwt/JWT.php';
require_once '../includes/php-jwt/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


/*
|--------------------------------------------------------------------------
| JWT CONFIG
|--------------------------------------------------------------------------
*/

if (!defined("JWT_SECRET")) {
    require_once __DIR__ . "/env.php";
}

$secret_key = JWT_SECRET;


/*
|--------------------------------------------------------------------------
| GET AUTHORIZATION HEADER
|--------------------------------------------------------------------------
*/

$headers = array_change_key_case(
    getallheaders(),
    CASE_LOWER
);

$authHeader =
    $headers['authorization']
    ?? $_SERVER['HTTP_AUTHORIZATION']
    ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    ?? '';

$token = '';


if (
    preg_match(
        '/bearer\s+(\S+)/i',
        $authHeader,
        $matches
    )
) {

    $token = $matches[1];

}


/*
|--------------------------------------------------------------------------
| TOKEN CHECK
|--------------------------------------------------------------------------
*/

if (!$token) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized. No token provided.'
    ]);

    exit;

}


/*
|--------------------------------------------------------------------------
| DECODE JWT
|--------------------------------------------------------------------------
*/

try {

    $decoded = JWT::decode(
        $token,
        new Key(
            $secret_key,
            'HS256'
        )
    );

} catch (Exception $e) {

    http_response_code(401);

    echo json_encode([
        'status' => 'error',
        'message' =>
            'Session expired or invalid. Please log in again.'
    ]);

    exit;

}


/*
|--------------------------------------------------------------------------
| PROCESS ORDER
|--------------------------------------------------------------------------
*/

try {

    $user_id =
        (int)(
            $decoded->uid
            ?? $decoded->user_id
            ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | USER VALIDATION
    |--------------------------------------------------------------------------
    */

    if (!$user_id) {

        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid token payload.'
        ]);

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | RATE LIMIT
    |--------------------------------------------------------------------------
    */

    ejr_rate_limit(
        $conn,
        "place_order",
        ejr_get_ip() . "|" . $user_id,
        20,
        300
    );


    /*
    |--------------------------------------------------------------------------
    | READ REQUEST
    |--------------------------------------------------------------------------
    */

    $data =
        json_decode(
            file_get_contents(
                'php://input'
            ),
            true
        );


    $customer_name =
        trim(
            $data['customer_name']
            ?? ''
        );


    $contact =
        trim(
            $data['contact']
            ?? ''
        );


    $delivery_address =
        trim(
            $data['delivery_address']
            ?? ''
        );


    $items =
        $data['items']
        ?? [];


    $total_price =
        floatval(
            $data['total_price']
            ?? 0
        );


    $payment_method =
        ($data['payment_method']
            ?? 'online'
        ) === 'cod'
            ? 'cod'
            : 'card';


    /*
    |--------------------------------------------------------------------------
    | VALIDATE BASIC FIELDS
    |--------------------------------------------------------------------------
    */

    if (
        !$customer_name ||
        !$contact ||
        !$delivery_address ||
        empty($items) ||
        $total_price <= 0
    ) {

        echo json_encode([
            'status' => 'error',
            'message' =>
                'Name, contact number, delivery address, items, and total are required.'
        ]);

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | ADDRESS VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        strlen($delivery_address) < 10
    ) {

        echo json_encode([
            'status' => 'error',
            'message' =>
                'Please provide a complete delivery address.'
        ]);

        exit;

    }


    if (
        strlen($delivery_address) > 500
    ) {

        echo json_encode([
            'status' => 'error',
            'message' =>
                'Delivery address is too long.'
        ]);

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | VALIDATE CART ITEMS
    |--------------------------------------------------------------------------
    |
    | We calculate the actual total from the database instead of blindly
    | trusting the total sent by the browser.
    |
    */

    $validatedItems = [];

    $serverTotal = 0;


    foreach ($items as $item) {

        $id =
            (int)(
                $item['id']
                ?? 0
            );


        $qty =
            (int)(
                $item['qty']
                ?? 0
            );


        if (
            $id <= 0 ||
            $qty <= 0
        ) {

            echo json_encode([
                'status' => 'error',
                'message' =>
                    'Invalid cart item.'
            ]);

            exit;

        }


        /*
        |--------------------------------------------------------------------------
        | GET PART
        |--------------------------------------------------------------------------
        */

        $stockCheck =
            $conn->prepare("
                SELECT
                    id,
                    name,
                    price,
                    stock
                FROM parts
                WHERE id = ?
                LIMIT 1
            ");


        $stockCheck->bind_param(
            "i",
            $id
        );


        $stockCheck->execute();


        $result =
            $stockCheck
                ->get_result()
                ->fetch_assoc();


        if (!$result) {

            echo json_encode([
                'status' => 'error',
                'message' =>
                    'One of the selected parts no longer exists.'
            ]);

            exit;

        }


        /*
        |--------------------------------------------------------------------------
        | STOCK CHECK
        |--------------------------------------------------------------------------
        */

        if (
            (int)$result['stock']
            < $qty
        ) {

            $name =
                $result['name']
                ?? 'Unknown Part';


            echo json_encode([
                'status' => 'error',
                'message' =>
                    "Sorry, {$name} is out of stock or has insufficient stock."
            ]);

            exit;

        }


        /*
        |--------------------------------------------------------------------------
        | USE DATABASE PRICE
        |--------------------------------------------------------------------------
        */

        $price =
            (float)$result['price'];


        $subtotal =
            $price * $qty;


        $serverTotal +=
            $subtotal;


        $validatedItems[] = [

            'id' =>
                (int)$result['id'],

            'name' =>
                $result['name'],

            'price' =>
                $price,

            'qty' =>
                $qty,

            'subtotal' =>
                $subtotal

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | ROUND TOTAL
    |--------------------------------------------------------------------------
    */

    $serverTotal =
        round(
            $serverTotal,
            2
        );


    /*
    |--------------------------------------------------------------------------
    | DO NOT TRUST BROWSER TOTAL
    |--------------------------------------------------------------------------
    */

    if (
        abs(
            $serverTotal -
            $total_price
        ) > 0.01
    ) {

        $total_price =
            $serverTotal;

    }


    /*
    |--------------------------------------------------------------------------
    | JSON ITEMS
    |--------------------------------------------------------------------------
    */

    $items_json =
        json_encode(
            $validatedItems,
            JSON_UNESCAPED_UNICODE
        );


    if ($items_json === false) {

        echo json_encode([
            'status' => 'error',
            'message' =>
                'Unable to prepare order items.'
        ]);

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | INSERT ORDER
    |--------------------------------------------------------------------------
    */

    $stmt =
        $conn->prepare("
            INSERT INTO part_orders
            (
                user_id,
                customer_name,
                contact,
                delivery_address,
                items,
                total_price,
                status,
                payment_method
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'pending',
                ?
            )
        ");


    if (!$stmt) {

        throw new Exception(
            'Database prepare failed: ' .
            $conn->error
        );

    }


    $stmt->bind_param(
        "issssds",
        $user_id,
        $customer_name,
        $contact,
        $delivery_address,
        $items_json,
        $total_price,
        $payment_method
    );


    /*
    |--------------------------------------------------------------------------
    | EXECUTE ORDER
    |--------------------------------------------------------------------------
    */

    if (!$stmt->execute()) {

        throw new Exception(
            'Database execution failed: ' .
            $stmt->error
        );

    }


    $order_id =
        $stmt->insert_id;


    /*
    |--------------------------------------------------------------------------
    | CASH ON DELIVERY
    |--------------------------------------------------------------------------
    */

    if (
        $payment_method === 'cod'
    ) {

        echo json_encode([

            'status' =>
                'success',

            'message' =>
                'Order placed! You will pay in cash upon delivery/pickup.',

            'checkout_url' =>
                null,

            'order_id' =>
                $order_id

        ]);

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | PAYMONGO CHECKOUT
    |--------------------------------------------------------------------------
    */

    $checkout =
        paymongo_create_checkout(

            $order_id,

            "EJR Garage Parts Order #{$order_id}",

            $total_price,

            "https://ejrgarage.onrender.com/payment_success.php?order_id={$order_id}",

            "https://ejrgarage.onrender.com/payment_cancelled.php?order_id={$order_id}"

        );


    /*
    |--------------------------------------------------------------------------
    | CHECKOUT CREATED
    |--------------------------------------------------------------------------
    */

    if (
        $checkout['ok']
    ) {

        $upd =
            $conn->prepare("
                UPDATE part_orders
                SET paymongo_checkout_id = ?
                WHERE id = ?
            ");


        $upd->bind_param(
            "si",
            $checkout['checkout_id'],
            $order_id
        );


        $upd->execute();


        echo json_encode([

            'status' =>
                'success',

            'message' =>
                'Order placed. Redirecting to payment...',

            'checkout_url' =>
                $checkout['checkout_url'],

            'order_id' =>
                $order_id

        ]);

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | PAYMONGO FAILURE
    |--------------------------------------------------------------------------
    |
    | The order remains saved as pending, matching your existing behavior.
    |
    */

    error_log(
        "PayMongo checkout creation failed for order #{$order_id}: "
        . $checkout['error']
    );


    echo json_encode([

        'status' =>
            'success',

        'message' =>
            'Order placed, but payment session could not be created. Please contact us to pay.',

        'checkout_url' =>
            null,

        'order_id' =>
            $order_id

    ]);


} catch (Exception $e) {

    error_log(
        'EJR place_order error: '
        . $e->getMessage()
    );


    http_response_code(500);


    echo json_encode([

        'status' =>
            'error',

        'message' =>
            'Something went wrong while placing your order. Please try again.'

    ]);

}

?>
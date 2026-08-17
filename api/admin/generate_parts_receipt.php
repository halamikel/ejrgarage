<?php

session_start();
session_write_close();

require_once '../db_config.php';

$order_id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

$token = isset($_GET['token'])
    ? trim($_GET['token'])
    : '';


/*
|--------------------------------------------------------------------------
| VALIDATE ORDER ID
|--------------------------------------------------------------------------
*/

if (!$order_id) {

    http_response_code(404);

    die("Invalid order.");

}


/*
|--------------------------------------------------------------------------
| ADMIN / RECEIPT ACCESS
|--------------------------------------------------------------------------
|
| Keep your existing access behavior.
|
*/

$isAdmin = true;


/*
|--------------------------------------------------------------------------
| TOKEN FALLBACK
|--------------------------------------------------------------------------
*/

if (!$isAdmin) {

    $expectedToken =
        hash(
            'sha256',
            'parts-receipt-' .
            $order_id .
            '-EJR2026'
        );


    if (
        !$token ||
        !hash_equals(
            $expectedToken,
            $token
        )
    ) {

        http_response_code(403);

        die("
            <h3
                style='
                    font-family:sans-serif;
                    color:#dc3545;
                    text-align:center;
                    margin-top:80px;
                '
            >
                Access Denied — Invalid or missing receipt token.
            </h3>
        ");

    }

}


/*
|--------------------------------------------------------------------------
| GET ORDER
|--------------------------------------------------------------------------
|
| o.* includes delivery_address automatically.
|
*/

$stmt = $conn->prepare("
    SELECT
        o.*,
        u.full_name AS user_full_name,
        u.email
    FROM part_orders o
    JOIN users u
        ON u.id = o.user_id
    WHERE o.id = ?
");


if (!$stmt) {

    http_response_code(500);

    die("Unable to prepare order query.");

}


$stmt->bind_param(
    "i",
    $order_id
);


$stmt->execute();


$order =
    $stmt
        ->get_result()
        ->fetch_assoc();


if (!$order) {

    http_response_code(404);

    die("Order not found.");

}


date_default_timezone_set(
    'Asia/Manila'
);


/*
|--------------------------------------------------------------------------
| ORDER DATA
|--------------------------------------------------------------------------
*/

$items =
    json_decode(
        $order['items'],
        true
    );


if (!is_array($items)) {
    $items = [];
}


$invoiceNo =
    'INV-PARTS-' .
    date('Y') .
    '-' .
    str_pad(
        $order['id'],
        5,
        '0',
        STR_PAD_LEFT
    );


$orderDate =
    date(
        "F j, Y",
        strtotime(
            $order['created_at']
        )
    );


$issuedDate =
    date("F j, Y");


$issuedTime =
    date("h:i A");


$customer =
    htmlspecialchars(
        $order['customer_name'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    );


$email =
    htmlspecialchars(
        $order['email'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    );


$contact =
    htmlspecialchars(
        $order['contact'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    );


/*
|--------------------------------------------------------------------------
| DELIVERY ADDRESS
|--------------------------------------------------------------------------
*/

$deliveryAddress =
    trim(
        $order['delivery_address'] ?? ''
    );


if ($deliveryAddress === '') {

    $deliveryAddress =
        'No delivery address provided';

}


/*
|--------------------------------------------------------------------------
| ESCAPE DELIVERY ADDRESS
|--------------------------------------------------------------------------
|
| nl2br() allows the address to retain line breaks.
|
*/

$deliveryAddressHtml =
    nl2br(
        htmlspecialchars(
            $deliveryAddress,
            ENT_QUOTES,
            'UTF-8'
        )
    );


/*
|--------------------------------------------------------------------------
| PAYMENT METHOD
|--------------------------------------------------------------------------
*/

$paymentMethod =
    strtolower(
        $order['payment_method'] ?? ''
    );


if (
    $paymentMethod === 'cod'
) {

    $paymentLabel =
        'Cash on Delivery';

} else {

    $paymentLabel =
        'Pay Online';

}


/*
|--------------------------------------------------------------------------
| ORDER STATUS
|--------------------------------------------------------------------------
*/

$orderStatus =
    strtolower(
        $order['status'] ?? 'pending'
    );


$statusLabel =
    ucfirst(
        $orderStatus
    );


/*
|--------------------------------------------------------------------------
| ITEMS
|--------------------------------------------------------------------------
*/

$itemRows = '';


foreach (
    $items
    as $i => $item
) {

    $bg =
        ($i % 2 === 0)
            ? '#ffffff'
            : '#f8f9fa';


    $itemPrice =
        (float)(
            $item['price'] ?? 0
        );


    $itemQty =
        (int)(
            $item['qty'] ?? 0
        );


    $subtotal =
        $itemPrice *
        $itemQty;


    $itemName =
        htmlspecialchars(
            $item['name'] ?? 'Unknown Item',
            ENT_QUOTES,
            'UTF-8'
        );


    $itemRows .= "

        <tr
            style='
                background:{$bg};
            '
        >

            <td
                style='
                    padding:10px 14px;
                    text-align:center;
                    color:#5f6b7a;
                    border-bottom:1px solid #e3e8ef;
                '
            >
                " . ($i + 1) . "
            </td>


            <td
                style='
                    padding:10px 14px;
                    color:#1e2a36;
                    font-weight:500;
                    border-bottom:1px solid #e3e8ef;
                '
            >
                {$itemName}
            </td>


            <td
                style='
                    padding:10px 14px;
                    text-align:center;
                    color:#1e2a36;
                    border-bottom:1px solid #e3e8ef;
                '
            >
                {$itemQty}
            </td>


            <td
                style='
                    padding:10px 14px;
                    text-align:right;
                    color:#1e2a36;
                    border-bottom:1px solid #e3e8ef;
                '
            >
                ₱" .
                number_format(
                    $itemPrice,
                    2
                ) .
                "
            </td>


            <td
                style='
                    padding:10px 14px;
                    text-align:right;
                    font-weight:600;
                    color:#1e2a36;
                    border-bottom:1px solid #e3e8ef;
                '
            >
                ₱" .
                number_format(
                    $subtotal,
                    2
                ) .
                "
            </td>

        </tr>

    ";

}


header(
    'Content-Type: text/html; charset=UTF-8'
);

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Invoice <?= $invoiceNo ?>
</title>


<style>

/* ============================================================
   RESET
============================================================ */

* {

    margin: 0;

    padding: 0;

    box-sizing: border-box;

}


/* ============================================================
   BODY
============================================================ */

body {

    font-family:
        Arial,
        sans-serif;

    background:
        #f4f6f8;

    padding:
        30px;

}


/* ============================================================
   INVOICE
============================================================ */

.invoice {

    background:
        #fff;

    border-radius:
        12px;

    max-width:
        720px;

    margin:
        auto;

    padding:
        0;

    border:
        1px solid #e3e8ef;

    box-shadow:
        0 4px 20px rgba(
            0,
            0,
            0,
            0.08
        );

    overflow:
        hidden;

}


.invoice-topbar {

    background:
        #e62429;

    height:
        6px;

}


/* ============================================================
   HEADER
============================================================ */

.invoice-header {

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        flex-start;

    padding:
        32px 40px 24px;

    border-bottom:
        1px solid #e3e8ef;

}


.invoice-brand h1 {

    color:
        #e62429;

    font-size:
        26px;

    margin-bottom:
        2px;

}


.invoice-brand p {

    color:
        #5f6b7a;

    font-size:
        12px;

    margin:
        2px 0;

}


.invoice-meta {

    text-align:
        right;

}


.invoice-meta .inv-label {

    font-size:
        11px;

    color:
        #a0aab5;

    text-transform:
        uppercase;

    letter-spacing:
        0.5px;

}


.invoice-meta .inv-number {

    font-size:
        22px;

    font-weight:
        bold;

    color:
        #1e2a36;

    margin:
        4px 0;

}


.invoice-meta .inv-badge {

    display:
        inline-block;

    background:
        #cfe2ff;

    color:
        #084298;

    padding:
        3px 12px;

    border-radius:
        20px;

    font-size:
        12px;

    font-weight:
        bold;

}


/* ============================================================
   DATE ROW
============================================================ */

.date-row {

    display:
        flex;

    gap:
        0;

    border-bottom:
        1px solid #e3e8ef;

}


.date-cell {

    flex:
        1;

    padding:
        16px 40px;

}


.date-cell:not(:last-child) {

    border-right:
        1px solid #e3e8ef;

}


.date-cell .lbl {

    font-size:
        11px;

    color:
        #a0aab5;

    text-transform:
        uppercase;

    letter-spacing:
        0.5px;

    margin-bottom:
        4px;

}


.date-cell .val {

    font-size:
        14px;

    font-weight:
        bold;

    color:
        #1e2a36;

}


.date-cell .sub {

    font-size:
        11px;

    color:
        #5f6b7a;

    margin-top:
        2px;

}


/* ============================================================
   CUSTOMER / ORDER INFORMATION
============================================================ */

.info-grid {

    display:
        grid;

    grid-template-columns:
        1fr 1fr;

    border-bottom:
        1px solid #e3e8ef;

}


.info-box {

    padding:
        20px 40px;

}


.info-box:first-child {

    border-right:
        1px solid #e3e8ef;

}


.info-box .section-label {

    font-size:
        11px;

    color:
        #e62429;

    text-transform:
        uppercase;

    letter-spacing:
        0.8px;

    font-weight:
        bold;

    margin-bottom:
        10px;

}


.info-box p {

    font-size:
        13px;

    color:
        #1e2a36;

    margin-bottom:
        5px;

}


.info-box p span {

    color:
        #5f6b7a;

    display:
        inline-block;

    min-width:
        60px;

}


/* ============================================================
   DELIVERY ADDRESS
============================================================ */

.delivery-address-box {

    margin-top:
        12px;

    padding:
        12px 14px;

    background:
        #fff7f7;

    border:
        1px solid #f3c6c8;

    border-left:
        4px solid #e62429;

    border-radius:
        6px;

}


.delivery-address-label {

    font-size:
        10px;

    color:
        #e62429;

    text-transform:
        uppercase;

    letter-spacing:
        0.7px;

    font-weight:
        bold;

    margin-bottom:
        5px;

}


.delivery-address-text {

    font-size:
        12px;

    line-height:
        1.5;

    color:
        #1e2a36;

    word-break:
        break-word;

}


/* ============================================================
   ITEMS
============================================================ */

.services-wrap {

    padding:
        24px 40px 0;

}


.services-wrap .section-label {

    font-size:
        11px;

    color:
        #e62429;

    text-transform:
        uppercase;

    letter-spacing:
        0.8px;

    font-weight:
        bold;

    margin-bottom:
        12px;

}


table {

    width:
        100%;

    border-collapse:
        collapse;

    border:
        1px solid #e3e8ef;

    overflow:
        hidden;

}


thead tr {

    background:
        #1e2a36;

}


thead th {

    padding:
        11px 14px;

    color:
        #fff;

    font-size:
        12px;

    text-align:
        left;

    text-transform:
        uppercase;

    letter-spacing:
        0.5px;

}


thead th:first-child {

    text-align:
        center;

    width:
        44px;

}


thead th.right {

    text-align:
        right;

}


/* ============================================================
   TOTAL
============================================================ */

.total-wrap {

    padding:
        0 40px;

}


.total-table {

    border:
        none;

    margin-top:
        0;

}


.total-table td {

    border:
        none;

    padding:
        6px 14px;

    font-size:
        13px;

    color:
        #5f6b7a;

}


.total-table td.val {

    text-align:
        right;

    color:
        #1e2a36;

    font-weight:
        600;

}


.total-table tr.grand td {

    font-size:
        16px;

    font-weight:
        bold;

    color:
        #e62429;

    border-top:
        2px solid #e62429;

    padding-top:
        10px;

}


.total-table tr.grand td.val {

    color:
        #e62429;

}


/* ============================================================
   ORDER DETAILS STAMP
============================================================ */

.stamp-row {

    margin:
        24px 40px;

    background:
        #084298;

    color:
        #fff;

    text-align:
        center;

    padding:
        14px;

    border-radius:
        8px;

    font-size:
        14px;

    font-weight:
        bold;

    letter-spacing:
        1px;

}


/* ============================================================
   FOOTER
============================================================ */

.invoice-footer {

    padding:
        16px 40px 28px;

    display:
        flex;

    justify-content:
        space-between;

    align-items:
        center;

    border-top:
        1px solid #e3e8ef;

}


.invoice-footer .note {

    font-size:
        11px;

    color:
        #a0aab5;

}


.print-btn {

    padding:
        10px 28px;

    background:
        #e62429;

    color:
        #fff;

    border:
        none;

    border-radius:
        8px;

    font-size:
        13px;

    font-weight:
        bold;

    cursor:
        pointer;

}


.print-btn:hover {

    background:
        #c91f24;

}


/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 650px) {

    body {

        padding:
            10px;

    }


    .invoice-header {

        flex-direction:
            column;

        gap:
            20px;

        padding:
            25px 20px;

    }


    .invoice-meta {

        text-align:
            left;

    }


    .date-row {

        flex-direction:
            column;

    }


    .date-cell {

        padding:
            14px 20px;

    }


    .date-cell:not(:last-child) {

        border-right:
            none;

        border-bottom:
            1px solid #e3e8ef;

    }


    .info-grid {

        grid-template-columns:
            1fr;

    }


    .info-box {

        padding:
            18px 20px;

    }


    .info-box:first-child {

        border-right:
            none;

        border-bottom:
            1px solid #e3e8ef;

    }


    .services-wrap {

        padding:
            20px;

        overflow-x:
            auto;

    }


    .services-wrap table {

        min-width:
            600px;

    }


    .total-wrap {

        padding:
            0 20px;

    }


    .stamp-row {

        margin:
            20px;

        font-size:
            12px;

    }


    .invoice-footer {

        padding:
            16px 20px 24px;

        flex-direction:
            column;

        align-items:
            flex-start;

        gap:
            15px;

    }


    .print-btn {

        width:
            100%;

    }

}


/* ============================================================
   PRINT
============================================================ */

@media print {

    body {

        background:
            #fff;

        padding:
            0;

    }


    .print-btn {

        display:
            none;

    }


    .invoice {

        box-shadow:
            none;

        border:
            none;

    }


    .invoice-topbar {

        background:
            #e62429 !important;

        -webkit-print-color-adjust:
            exact;

        print-color-adjust:
            exact;

    }


    .stamp-row {

        -webkit-print-color-adjust:
            exact;

        print-color-adjust:
            exact;

    }


    thead tr {

        -webkit-print-color-adjust:
            exact;

        print-color-adjust:
            exact;

    }

}

</style>

</head>


<body>

<div class="invoice">


    <!-- ========================================================
         TOP BAR
    ========================================================= -->

    <div class="invoice-topbar"></div>


    <!-- ========================================================
         HEADER
    ========================================================= -->

    <div class="invoice-header">

        <div class="invoice-brand">

            <h1>
                EJR Garage
            </h1>

            <p>
                Mabalacat City, Pampanga
            </p>

            <p>
                support@ejrgarage.com
            </p>

            <p>
                ejrgarage.onrender.com
            </p>

        </div>


        <div class="invoice-meta">

            <div class="inv-label">
                Parts Order Invoice
            </div>

            <div class="inv-number">
                <?= $invoiceNo ?>
            </div>

            <div class="inv-badge">
                &#128230; Parts Order
            </div>

        </div>

    </div>


    <!-- ========================================================
         DATES
    ========================================================= -->

    <div class="date-row">


        <div class="date-cell">

            <div class="lbl">
                Invoice Issued
            </div>

            <div class="val">
                <?= $issuedDate ?>
            </div>

            <div class="sub">
                <?= $issuedTime ?> (PHT)
            </div>

        </div>


        <div class="date-cell">

            <div class="lbl">
                Order Date
            </div>

            <div class="val">
                <?= $orderDate ?>
            </div>

        </div>


        <div class="date-cell">

            <div class="lbl">
                Prepared By
            </div>

            <div class="val">
                EJR Garage
            </div>

            <div class="sub">
                Authorized Staff
            </div>

        </div>


    </div>


    <!-- ========================================================
         CUSTOMER INFORMATION
    ========================================================= -->

    <div class="info-grid">


        <!-- ====================================================
             BILLED TO
        ===================================================== -->

        <div class="info-box">

            <div class="section-label">
                Billed To
            </div>


            <p>

                <span>
                    Name:
                </span>

                <?= $customer ?>

            </p>


            <p>

                <span>
                    Email:
                </span>

                <?= $email ?>

            </p>


            <p>

                <span>
                    Contact:
                </span>

                <?= $contact ?>

            </p>


            <!-- =================================================
                 DELIVERY ADDRESS
            ================================================== -->

            <div class="delivery-address-box">

                <div class="delivery-address-label">

                    <span>
                        &#128205;
                    </span>

                    Delivery Address

                </div>


                <div class="delivery-address-text">

                    <?= $deliveryAddressHtml ?>

                </div>

            </div>

        </div>


        <!-- ====================================================
             ORDER DETAILS
        ===================================================== -->

        <div class="info-box">

            <div class="section-label">
                Order Details
            </div>


            <p>

                <span>
                    Order No:
                </span>

                <?= $invoiceNo ?>

            </p>


            <p>

                <span>
                    Items:
                </span>

                <?= count($items) ?>
                item(s)

            </p>


            <p>

                <span>
                    Payment:
                </span>

                <strong>
                    <?= htmlspecialchars(
                        $paymentLabel,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>

            </p>


            <p>

                <span>
                    Status:
                </span>

                <strong
                    style="
                        color:#084298;
                        text-transform:capitalize;
                    "
                >

                    <?= htmlspecialchars(
                        $statusLabel,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </strong>

            </p>


            <p>

                <span>
                    Total:
                </span>

                <strong
                    style="
                        color:#e62429;
                    "
                >

                    ₱<?= number_format(
                        $order['total_price'],
                        2
                    ) ?>

                </strong>

            </p>

        </div>


    </div>


    <!-- ========================================================
         ITEMS TABLE
    ========================================================= -->

    <div class="services-wrap">

        <div class="section-label">
            Items Ordered
        </div>


        <table>

            <thead>

                <tr>

                    <th>
                        #
                    </th>

                    <th>
                        Part Name
                    </th>

                    <th>
                        Qty
                    </th>

                    <th class="right">
                        Unit Price
                    </th>

                    <th class="right">
                        Subtotal
                    </th>

                </tr>

            </thead>


            <tbody>

                <?= $itemRows ?>

            </tbody>

        </table>

    </div>


    <!-- ========================================================
         TOTAL
    ========================================================= -->

    <div class="total-wrap">

        <table class="total-table">

            <tr class="grand">

                <td>
                    Total Amount
                </td>

                <td class="val">

                    ₱<?= number_format(
                        $order['total_price'],
                        2
                    ) ?>

                </td>

            </tr>

        </table>

    </div>


    <!-- ========================================================
         STAMP
    ========================================================= -->

    <div class="stamp-row">

        &#128230;&nbsp;

        PARTS ORDER

        &mdash;

        THANK YOU FOR YOUR PURCHASE

    </div>


    <!-- ========================================================
         FOOTER
    ========================================================= -->

    <div class="invoice-footer">


        <div class="note">

            <?= $invoiceNo ?>

            &mdash;

            System-generated invoice.

            &copy;

            <?= date('Y') ?>

            EJR Garage.

        </div>


        <button
            class="print-btn"
            onclick="window.print()"
        >

            &#128438;

            Print / Download

        </button>


    </div>


</div>

</body>

</html>
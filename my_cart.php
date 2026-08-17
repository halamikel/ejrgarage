<?php
  $pageTitle = "My Cart - EJR Garage";
  include 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<section class="appointment-header text-center">
  <div class="container">
    <h1>My Cart</h1>
    <p>Review your selected parts before placing an order</p>
  </div>
</section>

<section class="py-5">
  <div class="container">

    <div class="row justify-content-center">

      <div class="col-lg-8">

        <!-- =====================================================
             TOP BUTTONS
        ====================================================== -->

        <div class="d-flex justify-content-between align-items-center mb-4">

          <a
            href="available_parts.php"
            class="btn btn-outline-dark btn-sm"
          >
            <i class="bi bi-arrow-left me-1"></i>
            Continue Shopping
          </a>

          <button
            class="btn btn-outline-danger btn-sm"
            onclick="clearCart()"
            id="clearBtn"
            style="display:none;"
          >
            <i class="bi bi-trash me-1"></i>
            Clear Cart
          </button>

        </div>


        <!-- =====================================================
             CART ITEMS
        ====================================================== -->

        <div id="cartContainer"></div>


        <!-- =====================================================
             ORDER SUMMARY
        ====================================================== -->

        <div id="cartSummary" style="display:none;">

          <!-- TOTAL -->

          <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">

            <div class="d-flex justify-content-between align-items-center">

              <span class="fw-bold fs-5">
                Total
              </span>

              <span
                class="fw-bold fs-4 text-danger"
                id="cartTotal"
              >
                ₱0.00
              </span>

            </div>

          </div>


          <!-- =================================================
               PLACE ORDER
          ================================================== -->

          <div class="card border-0 shadow-sm rounded-4 p-4">

            <h5 class="fw-bold mb-4 border-bottom pb-2">

              <i class="bi bi-bag-check me-2 text-danger"></i>

              Place Order

            </h5>


            <form id="orderForm">


              <!-- =================================================
                   CUSTOMER INFORMATION
              ================================================== -->

              <div class="order-section mb-4">

                <div class="section-title">

                  <span class="section-number">
                    1
                  </span>

                  <div>

                    <div class="fw-bold">
                      Customer Information
                    </div>

                    <small class="text-muted">
                      Confirm your contact details
                    </small>

                  </div>

                </div>


                <!-- NAME -->

                <div class="mb-3">

                  <label
                    class="form-label fw-semibold small text-muted text-uppercase"
                  >
                    Your Name
                  </label>

                  <input
                    type="text"
                    id="orderCustomerName"
                    class="form-control shadow-sm"
                    placeholder="Juan Dela Cruz"
                    required
                  >

                </div>


                <!-- CONTACT -->

                <div>

                  <label
                    class="form-label fw-semibold small text-muted text-uppercase"
                  >
                    Contact Number
                  </label>

                  <input
                    type="text"
                    id="orderContact"
                    class="form-control shadow-sm"
                    placeholder="09XX-XXX-XXXX"
                    required
                  >

                </div>

              </div>


              <!-- =================================================
                   DELIVERY ADDRESS
              ================================================== -->

              <div class="order-section mb-4">

                <div class="section-title">

                  <span class="section-number">
                    2
                  </span>

                  <div>

                    <div class="fw-bold">
                      Delivery Address
                    </div>

                    <small class="text-muted">
                      Where should we deliver your order?
                    </small>

                  </div>

                </div>


                <div class="address-card">

                  <div class="d-flex align-items-start">

                    <div class="address-icon">

                      <i class="bi bi-geo-alt-fill"></i>

                    </div>

                    <div class="flex-grow-1">

                      <label
                        for="orderAddress"
                        class="form-label fw-semibold mb-2"
                      >
                        Complete Address
                      </label>

                      <textarea
                        id="orderAddress"
                        class="form-control"
                        rows="4"
                        placeholder="House/Unit No., Street, Barangay, City/Municipality, Province"
                        required
                      ></textarea>

                      <div class="d-flex justify-content-between mt-2">

                        <small class="text-muted">

                          <i class="bi bi-info-circle me-1"></i>

                          Please provide a complete and accurate address.

                        </small>

                        <small
                          class="text-muted"
                          id="addressCounter"
                        >
                          0/500
                        </small>

                      </div>

                    </div>

                  </div>

                </div>


                <!-- USE PROFILE ADDRESS -->

                <button
                  type="button"
                  class="btn btn-outline-danger btn-sm mt-3"
                  id="useProfileAddressBtn"
                  onclick="useProfileAddress()"
                >

                  <i class="bi bi-person-check me-1"></i>

                  Use My Saved Address

                </button>


                <span
                  id="addressSavedIndicator"
                  class="small text-success ms-2"
                  style="display:none;"
                >

                  <i class="bi bi-check-circle-fill me-1"></i>

                  Saved address loaded

                </span>

              </div>


              <!-- =================================================
                   PAYMENT METHOD
              ================================================== -->

              <div class="order-section mb-4">

                <div class="section-title">

                  <span class="section-number">
                    3
                  </span>

                  <div>

                    <div class="fw-bold">
                      Payment Method
                    </div>

                    <small class="text-muted">
                      Select how you want to pay
                    </small>

                  </div>

                </div>


                <!-- PAY ONLINE -->

                <div class="payment-option-card">

                  <input
                    type="radio"
                    name="paymentMethod"
                    id="payOnline"
                    value="online"
                    checked
                  >

                  <label for="payOnline">

                    <i class="bi bi-credit-card fs-4 me-2"></i>

                    <div>

                      <div class="fw-bold">
                        Pay Online
                      </div>

                      <div class="small text-muted">
                        Card or QRPH via secure checkout
                      </div>

                    </div>

                  </label>

                </div>


                <!-- COD -->

                <div class="payment-option-card">

                  <input
                    type="radio"
                    name="paymentMethod"
                    id="payCod"
                    value="cod"
                  >

                  <label for="payCod">

                    <i class="bi bi-cash-coin fs-4 me-2"></i>

                    <div>

                      <div class="fw-bold">
                        Cash on Delivery
                      </div>

                      <div class="small text-muted">
                        Pay in cash upon pickup/delivery
                      </div>

                    </div>

                  </label>

                </div>

              </div>


              <!-- =================================================
                   ORDER PREVIEW
              ================================================== -->

              <div class="order-preview mb-4">

                <div class="fw-bold mb-3">

                  <i class="bi bi-receipt me-2"></i>

                  Order Summary

                </div>


                <div class="preview-row">

                  <span>
                    Customer
                  </span>

                  <strong id="previewCustomer">
                    —
                  </strong>

                </div>


                <div class="preview-row">

                  <span>
                    Contact
                  </span>

                  <strong id="previewContact">
                    —
                  </strong>

                </div>


                <div class="preview-row address-preview-row">

                  <span>
                    Delivery Address
                  </span>

                  <strong
                    id="previewAddress"
                    class="text-end"
                  >
                    —
                  </strong>

                </div>


                <div class="preview-row">

                  <span>
                    Payment
                  </span>

                  <strong id="previewPayment">
                    Pay Online
                  </strong>

                </div>


                <div class="preview-total">

                  <span>
                    Total
                  </span>

                  <strong id="previewTotal">
                    ₱0.00
                  </strong>

                </div>

              </div>


              <!-- =================================================
                   PLACE ORDER BUTTON
              ================================================== -->

              <button
                type="submit"
                class="btn btn-danger w-100 fw-bold py-3"
                id="placeOrderBtn"
                style="
                  background-color:#dc3545!important;
                  border:none!important;
                "
              >

                <i class="bi bi-bag-check-fill me-2"></i>

                Place Order

              </button>


            </form>

          </div>

        </div>

      </div>

    </div>

  </div>
</section>


<style>

/* ============================================================
   CART
============================================================ */

.cart-item-card {

    border: none;

    border-radius: 14px;

    box-shadow:
        0 4px 15px rgba(0,0,0,0.06);

    margin-bottom: 14px;

    transition:
        box-shadow 0.2s;

}

.cart-item-card:hover {

    box-shadow:
        0 8px 25px rgba(0,0,0,0.1);

}


.qty-btn {

    width: 30px;

    height: 30px;

    padding: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 16px;

    border-radius: 50% !important;

}


/* ============================================================
   ORDER SECTIONS
============================================================ */

.order-section {

    padding-bottom: 5px;

}


.section-title {

    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 20px;

}


.section-number {

    width: 34px;

    height: 34px;

    min-width: 34px;

    border-radius: 50%;

    background: #dc3545;

    color: #fff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-weight: 700;

}


/* ============================================================
   ADDRESS
============================================================ */

.address-card {

    background: #fafbfc;

    border: 1px solid #e9ecef;

    border-radius: 14px;

    padding: 18px;

}


.address-icon {

    width: 42px;

    height: 42px;

    min-width: 42px;

    border-radius: 12px;

    background: #fff0f0;

    color: #dc3545;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-right: 14px;

    font-size: 18px;

}


#orderAddress {

    resize: vertical;

    min-height: 110px;

}


#orderAddress:focus {

    border-color: #dc3545;

    box-shadow:
        0 0 0 0.2rem rgba(220,53,69,.12);

}


/* ============================================================
   PAYMENT
============================================================ */

.payment-option-card {

    position: relative;

    margin-bottom: 10px;

}


.payment-option-card input[type="radio"] {

    position: absolute;

    opacity: 0;

    width: 0;

    height: 0;

}


.payment-option-card label {

    display: flex;

    align-items: center;

    border: 2px solid #e9ecef;

    border-radius: 12px;

    padding: 12px 16px;

    cursor: pointer;

    transition: all 0.15s;

    margin: 0;

    color: #495057;

}


.payment-option-card input[type="radio"]:checked + label {

    border-color: #dc3545;

    background: #fff5f5;

    color: #1e2a36;

}


.payment-option-card input[type="radio"]:checked + label i {

    color: #dc3545;

}


/* ============================================================
   ORDER PREVIEW
============================================================ */

.order-preview {

    background: #f8f9fa;

    border-radius: 14px;

    padding: 18px;

    border: 1px solid #e9ecef;

}


.preview-row {

    display: flex;

    justify-content: space-between;

    gap: 20px;

    padding: 9px 0;

    border-bottom: 1px solid #e9ecef;

    font-size: 14px;

}


.preview-row span {

    color: #6c757d;

}


.preview-row strong {

    color: #212529;

}


.address-preview-row strong {

    max-width: 60%;

    word-break: break-word;

}


.preview-total {

    display: flex;

    justify-content: space-between;

    padding-top: 15px;

    margin-top: 5px;

    font-size: 18px;

    font-weight: 700;

}


.preview-total strong {

    color: #dc3545;

}


/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 576px) {

    .address-preview-row {

        flex-direction: column;

        gap: 5px;

    }

    .address-preview-row strong {

        max-width: 100%;

        text-align: left !important;

    }

    .cart-item-card .d-flex {

        flex-wrap: wrap;

    }

}

</style>


<script>

/* ============================================================
   CART DATA
============================================================ */

let cart = JSON.parse(
    localStorage.getItem('ejr_cart') || '[]'
);


/* ============================================================
   USER DATA
============================================================ */

let currentUser = {};


/* ============================================================
   REFRESH USER FROM API
============================================================ */

function refreshUserFromApi() {

    const token =
        localStorage.getItem('jwt_token');

    if (!token) return;


    fetch('api/get_profile.php', {

        method: 'GET',

        headers: {
            'Authorization': 'Bearer ' + token
        },

        credentials: 'include'

    })

    .then(res => res.json())

    .then(data => {

        if (
            data.status === 'success' &&
            data.user
        ) {

            currentUser = data.user;

            localStorage.setItem(
                'user',
                JSON.stringify(data.user)
            );

            prefillUser(data.user);

            /*
             * Automatically load saved address.
             */
            if (
                data.user.address &&
                !document.getElementById('orderAddress').value.trim()
            ) {

                document.getElementById(
                    'orderAddress'
                ).value = data.user.address;

                updateAddressPreview();

                showAddressSavedIndicator();

            }

        }

    })

    .catch(err => {

        console.warn(
            'Could not refresh user:',
            err
        );

    });

}


/* ============================================================
   PREFILL CUSTOMER INFORMATION
============================================================ */

function prefillUser(user) {

    let fullName = '';


    if (user.full_name) {

        fullName =
            user.full_name.trim();

    }


    document.getElementById(
        'orderCustomerName'
    ).value = fullName;


    document.getElementById(
        'orderContact'
    ).value =
        user.phone || '';


    updateOrderPreview();

}


/* ============================================================
   PROFILE ADDRESS
============================================================ */

function useProfileAddress() {

    const address =
        currentUser.address ||
        JSON.parse(
            localStorage.getItem('user') || '{}'
        ).address ||
        '';


    if (!address.trim()) {

        Swal.fire({

            icon: 'info',

            title: 'No Saved Address',

            text:
                'You do not have an address saved in your profile yet. Please enter your delivery address manually.',

            confirmButtonColor:
                '#dc3545'

        });

        return;

    }


    document.getElementById(
        'orderAddress'
    ).value = address;


    updateAddressPreview();

    showAddressSavedIndicator();

}


function showAddressSavedIndicator() {

    const indicator =
        document.getElementById(
            'addressSavedIndicator'
        );

    if (!indicator) return;

    indicator.style.display =
        'inline-block';

    setTimeout(() => {

        indicator.style.display =
            'none';

    }, 3000);

}


/* ============================================================
   ADDRESS COUNTER
============================================================ */

function updateAddressCounter() {

    const address =
        document.getElementById(
            'orderAddress'
        );

    const counter =
        document.getElementById(
            'addressCounter'
        );

    if (!address || !counter) return;

    counter.textContent =
        `${address.value.length}/500`;

}


/* ============================================================
   ADDRESS PREVIEW
============================================================ */

function updateAddressPreview() {

    const address =
        document.getElementById(
            'orderAddress'
        ).value.trim();


    document.getElementById(
        'previewAddress'
    ).textContent =
        address || '—';


    updateAddressCounter();

}


/* ============================================================
   ORDER PREVIEW
============================================================ */

function updateOrderPreview() {

    const name =
        document.getElementById(
            'orderCustomerName'
        ).value.trim();


    const contact =
        document.getElementById(
            'orderContact'
        ).value.trim();


    const address =
        document.getElementById(
            'orderAddress'
        ).value.trim();


    const payment =
        document.querySelector(
            'input[name="paymentMethod"]:checked'
        );


    document.getElementById(
        'previewCustomer'
    ).textContent =
        name || '—';


    document.getElementById(
        'previewContact'
    ).textContent =
        contact || '—';


    document.getElementById(
        'previewAddress'
    ).textContent =
        address || '—';


    document.getElementById(
        'previewPayment'
    ).textContent =
        payment?.value === 'cod'
            ? 'Cash on Delivery'
            : 'Pay Online';


    const total =
        cart.reduce(
            (sum, item) =>
                sum +
                Number(item.price) *
                Number(item.qty),
            0
        );


    document.getElementById(
        'previewTotal'
    ).textContent =
        '₱' +
        total.toLocaleString(
            'en-PH',
            {
                minimumFractionDigits: 2
            }
        );

}


/* ============================================================
   PAGE LOAD
============================================================ */

document.addEventListener(
    'DOMContentLoaded',
    () => {

        const savedUser =
            JSON.parse(
                localStorage.getItem(
                    'user'
                ) || '{}'
            );


        currentUser =
            savedUser;


        if (savedUser.full_name) {

            prefillUser(
                savedUser
            );

        }


        if (savedUser.address) {

            document.getElementById(
                'orderAddress'
            ).value =
                savedUser.address;

        }


        updateAddressPreview();

        updateOrderPreview();

        refreshUserFromApi();

        renderCart();


        /*
         * Live preview updates.
         */

        document
            .getElementById(
                'orderCustomerName'
            )
            .addEventListener(
                'input',
                updateOrderPreview
            );


        document
            .getElementById(
                'orderContact'
            )
            .addEventListener(
                'input',
                updateOrderPreview
            );


        document
            .getElementById(
                'orderAddress'
            )
            .addEventListener(
                'input',
                () => {

                    updateAddressPreview();

                    updateOrderPreview();

                }
            );


        document
            .querySelectorAll(
                'input[name="paymentMethod"]'
            )
            .forEach(
                radio => {

                    radio.addEventListener(
                        'change',
                        updateOrderPreview
                    );

                }
            );

    }
);


/* ============================================================
   RENDER CART
============================================================ */

function renderCart() {

    const container =
        document.getElementById(
            'cartContainer'
        );


    const summary =
        document.getElementById(
            'cartSummary'
        );


    const clearBtn =
        document.getElementById(
            'clearBtn'
        );


    if (!cart.length) {

        container.innerHTML = `

            <div class="text-center py-5 text-muted">

                <i class="bi bi-cart-x fs-1 d-block mb-3"></i>

                <h5>
                    Your cart is empty
                </h5>

                <a
                    href="available_parts.php"
                    class="btn mt-3 px-4 fw-bold"
                    style="
                        background-color:#dc3545;
                        color:#fff;
                        border:none;
                        opacity:1;
                    "
                >

                    <i class="bi bi-arrow-left me-1"></i>

                    Browse Parts

                </a>

            </div>

        `;


        summary.style.display =
            'none';


        clearBtn.style.display =
            'none';


        return;

    }


    const total =
        cart.reduce(
            (sum, item) =>
                sum +
                Number(item.price) *
                Number(item.qty),
            0
        );


    container.innerHTML =
        cart.map(
            (item, idx) => `

            <div class="card cart-item-card p-3">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="fw-bold">
                            ${escapeHtml(item.name)}
                        </div>

                        <div class="text-muted small">

                            ₱${Number(item.price).toLocaleString(
                                'en-PH',
                                {
                                    minimumFractionDigits:2
                                }
                            )}

                            each

                        </div>

                    </div>


                    <div class="d-flex align-items-center gap-2">

                        <button
                            class="btn btn-outline-secondary qty-btn"
                            onclick="changeQty(${idx}, -1)"
                        >
                            −
                        </button>


                        <span class="fw-bold px-1">
                            ${item.qty}
                        </span>


                        <button
                            class="btn btn-outline-secondary qty-btn"
                            onclick="changeQty(${idx}, 1)"
                        >
                            +
                        </button>


                        <span
                            class="fw-bold text-danger ms-3"
                            style="
                                min-width:80px;
                                text-align:right;
                            "
                        >

                            ₱${(
                                Number(item.price) *
                                Number(item.qty)
                            ).toLocaleString(
                                'en-PH',
                                {
                                    minimumFractionDigits:2
                                }
                            )}

                        </span>


                        <button
                            class="btn btn-sm btn-outline-danger qty-btn ms-2"
                            onclick="removeItem(${idx})"
                        >

                            <i class="bi bi-trash"></i>

                        </button>

                    </div>

                </div>

            </div>

        `
        ).join('');


    document.getElementById(
        'cartTotal'
    ).textContent =
        '₱' +
        total.toLocaleString(
            'en-PH',
            {
                minimumFractionDigits:2
            }
        );


    summary.style.display =
        'block';


    clearBtn.style.display =
        'inline-block';


    updateOrderPreview();

}


/* ============================================================
   HTML ESCAPE
============================================================ */

function escapeHtml(value) {

    const div =
        document.createElement(
            'div'
        );

    div.textContent =
        value ?? '';

    return div.innerHTML;

}


/* ============================================================
   SAVE CART
============================================================ */

function saveCart() {

    localStorage.setItem(
        'ejr_cart',
        JSON.stringify(cart)
    );

}


/* ============================================================
   CHANGE QUANTITY
============================================================ */

function changeQty(
    idx,
    delta
) {

    cart[idx].qty =
        Number(cart[idx].qty) +
        delta;


    if (
        cart[idx].qty <= 0
    ) {

        cart.splice(
            idx,
            1
        );

    }


    saveCart();

    renderCart();

}


/* ============================================================
   REMOVE ITEM
============================================================ */

function removeItem(idx) {

    cart.splice(
        idx,
        1
    );

    saveCart();

    renderCart();

}


/* ============================================================
   CLEAR CART
============================================================ */

function clearCart() {

    Swal.fire({

        title:
            'Clear cart?',

        text:
            'All items will be removed.',

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#dc3545',

        confirmButtonText:
            'Yes, clear it'

    }).then(
        result => {

            if (
                result.isConfirmed
            ) {

                cart = [];

                saveCart();

                renderCart();

            }

        }
    );

}


/* ============================================================
   PLACE ORDER
============================================================ */

document
    .getElementById(
        'orderForm'
    )
    .addEventListener(
        'submit',
        function(e) {

            e.preventDefault();


            const token =
                localStorage.getItem(
                    'jwt_token'
                );


            if (!token) {

                Swal.fire(
                    'Not logged in',
                    'Please log in to place an order.',
                    'warning'
                );

                return;

            }


            if (!cart.length) {

                Swal.fire(
                    'Cart is empty',
                    'Please add a part before placing an order.',
                    'warning'
                );

                return;

            }


            const customerName =
                document.getElementById(
                    'orderCustomerName'
                ).value.trim();


            const contact =
                document.getElementById(
                    'orderContact'
                ).value.trim();


            const address =
                document.getElementById(
                    'orderAddress'
                ).value.trim();


            const paymentMethod =
                document.querySelector(
                    'input[name="paymentMethod"]:checked'
                ).value;


            /*
             * Validate address.
             */

            if (!address) {

                Swal.fire({

                    icon:
                        'warning',

                    title:
                        'Delivery Address Required',

                    text:
                        'Please enter your complete delivery address before placing your order.',

                    confirmButtonColor:
                        '#dc3545'

                });

                document
                    .getElementById(
                        'orderAddress'
                    )
                    .focus();

                return;

            }


            if (
                address.length < 10
            ) {

                Swal.fire({

                    icon:
                        'warning',

                    title:
                        'Address Too Short',

                    text:
                        'Please provide a more complete delivery address.',

                    confirmButtonColor:
                        '#dc3545'

                });

                return;

            }


            const btn =
                this.querySelector(
                    '[type="submit"]'
                );


            btn.disabled =
                true;


            btn.innerHTML = `

                <span
                    class="spinner-border spinner-border-sm me-2"
                ></span>

                Placing Order...

            `;


            const total =
                cart.reduce(
                    (sum, item) =>
                        sum +
                        Number(item.price) *
                        Number(item.qty),
                    0
                );


            fetch(
                'api/place_order.php',
                {

                    method:
                        'POST',

                    credentials:
                        'include',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token

                    },

                    body:
                        JSON.stringify({

                            customer_name:
                                customerName,

                            contact:
                                contact,

                            delivery_address:
                                address,

                            items:
                                cart,

                            total_price:
                                total,

                            payment_method:
                                paymentMethod

                        })

                }
            )

            .then(
                res =>
                    res.json()
            )

            .then(
                data => {

                    if (
                        data.status ===
                        'success'
                    ) {

                        cart = [];

                        saveCart();


                        if (
                            data.checkout_url
                        ) {

                            window.location.href =
                                data.checkout_url;

                        }

                        else {

                            Swal.fire({

                                icon:
                                    'success',

                                title:
                                    'Order Placed!',

                                text:
                                    paymentMethod === 'cod'
                                        ? 'Your order is pending. Please prepare exact cash for delivery/pickup.'
                                        : 'Your order is now pending. We will contact you shortly.',

                                confirmButtonColor:
                                    '#e62429'

                            })
                            .then(
                                () => {

                                    window.location.href =
                                        'available_parts.php';

                                }
                            );

                        }

                    }

                    else {

                        Swal.fire(
                            'Error',
                            data.message ||
                                'Failed to place order.',
                            'error'
                        );


                        btn.disabled =
                            false;


                        btn.innerHTML = `

                            <i class="bi bi-bag-check-fill me-2"></i>

                            Place Order

                        `;

                    }

                }
            )

            .catch(
                error => {

                    console.error(
                        'Place order error:',
                        error
                    );


                    Swal.fire(
                        'Error',
                        'Failed to connect to server.',
                        'error'
                    );


                    btn.disabled =
                        false;


                    btn.innerHTML = `

                        <i class="bi bi-bag-check-fill me-2"></i>

                        Place Order

                    `;

                }
            );

        }
    );

</script>


<?php include 'includes/footer.php'; ?>
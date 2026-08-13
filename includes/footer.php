<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/js/auth.js"></script>

<script>
if (localStorage.getItem('jwt_token')) {
    if (typeof startSessionWatch === 'function') {
        startSessionWatch();
    }
}
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


<footer class="bg-black text-white pt-5 pb-3"
        style="border-top: 4px solid #b30000; position: relative; z-index: 10;">

    <div class="container">

        <div class="row g-4 justify-content-between">

            <div class="col-md-4 text-start">

                <h6 class="fw-bold mb-4 text-uppercase"
                    style="letter-spacing: 1px;">
                    Contact Us
                </h6>

                <ul class="list-unstyled">

                    <li class="mb-3 d-flex align-items-center">
                        <i class="bi bi-geo-alt-fill text-danger me-3 fs-5"></i>
                        <span>Mabalacat City, Pampanga</span>
                    </li>

                    <li class="mb-3 d-flex align-items-center">
                        <i class="bi bi-telephone-fill text-danger me-3 fs-5"></i>
                        <span>0912-345-6789</span>
                    </li>

                    <li class="mb-3 d-flex align-items-center">
                        <i class="bi bi-envelope-fill text-danger me-3 fs-5"></i>
                        <span>info@ejrgarage.com</span>
                    </li>

                </ul>

            </div>


            <div class="col-md-4 text-center d-flex flex-column align-items-center">

                <div class="footer-brand mb-3">

                    <img
                        src="/assets/images/Inverted_EJRLogo.png"
                        alt="EJR Garage"
                        style="height: 55px; width: auto;"
                    >

                </div>

                <p class="text-secondary small px-lg-5">
                    Reliable repairs. Honest pricing. Quality service.
                </p>

                <div class="d-flex gap-3 mt-2">

                    <a href="#" class="text-white opacity-75">
                        <i class="bi bi-facebook fs-5"></i>
                    </a>

                    <a href="#" class="text-white opacity-75">
                        <i class="bi bi-instagram fs-5"></i>
                    </a>

                    <a href="#" class="text-white opacity-75">
                        <i class="bi bi-twitter-x fs-5"></i>
                    </a>

                </div>


                <a
                    href="/download-app.php"
                    class="d-inline-flex align-items-center gap-2 mt-3 px-3 py-2 text-white text-decoration-none"
                    style="background:rgba(255,255,255,0.08); border-radius:10px; font-size:0.85rem;"
                >

                    <i class="bi bi-android2 text-danger fs-5"></i>

                    Get the App

                </a>

            </div>


            <div class="col-md-4 text-start text-md-end">

                <div class="d-inline-block text-start">

                    <h6 class="fw-bold mb-4 text-uppercase"
                        style="letter-spacing: 1px;">
                        Office Hours
                    </h6>

                    <ul class="list-unstyled m-0">

                        <li class="mb-2">
                            Monday – Friday:
                            <span class="text-secondary ps-2">
                                8:00 AM – 6:00 PM
                            </span>
                        </li>

                        <li class="mb-2">
                            Saturday:
                            <span class="text-secondary ps-2">
                                8:00 AM – 5:00 PM
                            </span>
                        </li>

                        <li class="mb-0">
                            Sunday:
                            <span class="text-danger fw-bold ps-2">
                                Closed
                            </span>
                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <div class="row mt-5">

            <div class="col-12 text-center">

                <hr class="border-secondary opacity-25 mb-4">

                <p class="small text-secondary m-0">
                    © 2026 EJR Garage. All rights reserved.
                </p>

            </div>

        </div>

    </div>

</footer>



<!-- ============================================================
     EJR ASSISTANT / CHAT WIDGET
============================================================ -->

<div
    id="ejrChatContainer"
    style="
        position:fixed;
        bottom:25px;
        right:25px;
        z-index:9999999;
        pointer-events:none;
    "
>


    <!-- ========================================================
         CHAT TOGGLE
    ========================================================= -->

    <div
        id="chatPill"
        onclick="toggleChat()"
        style="
            background:linear-gradient(45deg,#e62429,#ff6b00);
            color:white;
            padding:12px 22px;
            border-radius:50px;
            cursor:pointer;
            font-weight:700;
            box-shadow:0 4px 15px rgba(0,0,0,0.4);
            display:flex;
            align-items:center;
            user-select:none;
            pointer-events:all;
        "
    >

        <i class="bi bi-chat-dots-fill me-2 fs-5"></i>

        LIVE CHAT

        <span
            id="chatNotification"
            style="
                display:none;
                margin-left:8px;
                background:#fff;
                color:#e62429;
                border-radius:50%;
                width:22px;
                height:22px;
                align-items:center;
                justify-content:center;
                font-size:11px;
                font-weight:800;
            "
        >
            0
        </span>

    </div>



    <!-- ========================================================
         CHAT BOX
    ========================================================= -->

    <div
        id="chatBox"
        style="
            display:none;
            position:absolute;
            bottom:75px;
            right:0;
            width:360px;
            background:#fff;
            border-radius:20px;
            box-shadow:0 20px 60px rgba(0,0,0,0.25);
            flex-direction:column;
            overflow:hidden;
            border:1px solid #e3e8ef;
            pointer-events:all;
        "
    >


        <!-- ====================================================
             HEADER
        ===================================================== -->

        <div
            style="
                background:linear-gradient(135deg,#1e2a36,#2d3f50);
                color:white;
                padding:16px 20px;
                display:flex;
                justify-content:space-between;
                align-items:center;
                border-bottom:3px solid #e62429;
            "
        >

            <div class="d-flex align-items-center gap-2">

                <div
                    style="
                        width:38px;
                        height:38px;
                        background:#e62429;
                        border-radius:50%;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                    "
                >

                    <i class="bi bi-robot fs-5"></i>

                </div>

                <div>

                   <div
    id="chatTitle"
    style="
        font-weight:700;
        font-size:15px;
    "
>
    EJR Assistant
</div>

<div
    id="chatStatusLabel"
    style="
        font-size:11px;
        opacity:0.75;
    "
>
    Bot Mode • Always here to help
</div>
                </div>

            </div>


            <button
                onclick="toggleChat()"
                style="
                    background:none;
                    border:none;
                    color:#fff;
                    cursor:pointer;
                    font-size:22px;
                    opacity:0.8;
                "
            >
                &times;
            </button>

        </div>



        <!-- ====================================================
             WAITING INDICATOR
        ===================================================== -->

        <div
            id="waitingIndicator"
            style="
                display:none;
                padding:8px 16px;
                background:#fff3cd;
                color:#856404;
                font-size:12px;
                text-align:center;
                border-bottom:1px solid #ffeeba;
            "
        >

            <span
                class="spinner-border spinner-border-sm me-1"
                style="width:11px;height:11px;"
            ></span>

            Live chat starts immediately when you request an agent.

        </div>



        <!-- ====================================================
             LIVE INDICATOR
        ===================================================== -->

        <div
            id="liveIndicator"
            style="
                display:none;
                padding:8px 16px;
                background:#d1fae5;
                color:#065f46;
                font-size:12px;
                text-align:center;
                border-bottom:1px solid #6ee7b7;
            "
        >

            <i
                class="bi bi-circle-fill me-1"
                style="font-size:8px;"
            ></i>

            You are now connected to a live admin

        </div>

<!-- ====================================================
     ADMIN CHAT PANEL
     
     This is shown ONLY when the logged-in account has
     role_id = 2.
     
     Admins can RECEIVE customer messages here.
     They do not get the customer bot interface.
===================================================== -->

<div
    id="adminChatPanel"
    style="
        display:none;
        height:390px;
        background:#f0f2f5;
        flex-direction:column;
    "
>

    <!-- CUSTOMER LIST -->

    <div
        id="adminChatCustomerList"
        style="
            height:150px;
            overflow-y:auto;
            background:#fff;
            border-bottom:1px solid #ddd;
        "
    >

        <div
            style="
                padding:20px;
                text-align:center;
                color:#777;
                font-size:13px;
            "
        >
            Loading customer messages...
        </div>

    </div>


    <!-- SELECTED CUSTOMER -->

    <div
        id="adminSelectedCustomer"
        style="
            padding:10px 14px;
            background:#fff;
            border-bottom:1px solid #ddd;
            font-size:13px;
            font-weight:700;
        "
    >
        Select a customer
    </div>


    <!-- CUSTOMER MESSAGES -->

    <div
        id="adminChatMessages"
        style="
            flex:1;
            padding:12px;
            overflow-y:auto;
        "
    >

        <div
            style="
                text-align:center;
                color:#888;
                margin-top:40px;
                font-size:13px;
            "
        >

            <i
                class="bi bi-chat-left-text"
                style="
                    font-size:30px;
                    display:block;
                    margin-bottom:8px;
                "
            ></i>

            Select a customer to view incoming messages.

        </div>

    </div>

</div>

        <!-- ====================================================
             MESSAGES
        ===================================================== -->

        <div
            id="chatBody"
            style="
                height:300px;
                padding:16px;
                overflow-y:auto;
                background:#f0f2f5;
                display:flex;
                flex-direction:column;
                gap:8px;
            "
        >

            <div class="ejr-bot-msg">

                Hi! 👋 Welcome to
                <strong>EJR Garage</strong>.
                How can I help you today?

            </div>

        </div>



        <!-- ====================================================
             TYPING INDICATOR
        ===================================================== -->

        <div
            id="typingIndicator"
            style="
                display:none;
                padding:6px 16px;
                background:#f0f2f5;
                border-top:1px solid #e9ecef;
            "
        >

            <div
                style="
                    background:#e9ecef;
                    padding:8px 12px;
                    border-radius:12px 12px 12px 0;
                    display:inline-flex;
                    gap:4px;
                    align-items:center;
                "
            >

                <span class="ejr-dot"></span>
                <span class="ejr-dot"></span>
                <span class="ejr-dot"></span>

            </div>

        </div>



        <!-- ====================================================
             QUICK OPTIONS
        ===================================================== -->

        <div
            id="chatOptions"
            style="
                padding:10px 14px;
                background:#fff;
                display:flex;
                flex-wrap:wrap;
                gap:6px;
                border-top:1px solid #eee;
            "
        >

            <button
                onclick="askQuickQuestion('Services Offered')"
                class="opt-btn"
            >

                <i class="bi bi-tools me-1"></i>

                Services

            </button>


            <button
                onclick="askQuickQuestion('Booking Help')"
                class="opt-btn"
            >

                <i class="bi bi-calendar me-1"></i>

                Booking

            </button>


            <button
                onclick="askQuickQuestion('Operating Hours')"
                class="opt-btn"
            >

                <i class="bi bi-clock me-1"></i>

                Hours

            </button>


            <button
                onclick="askQuickQuestion('Location')"
                class="opt-btn"
            >

                <i class="bi bi-geo-alt me-1"></i>

                Location

            </button>


            <button
                onclick="askQuickQuestion('Contact')"
                class="opt-btn"
            >

                <i class="bi bi-telephone me-1"></i>

                Contact

            </button>


            <div
                id="dynamicButtonContainer"
                style="
                    width:100%;
                    margin-top:4px;
                "
            >

                <button
                    id="btnRequestLive"
                    onclick="requestLiveChat()"
                    class="opt-btn"
                    style="
                        border-color:#1e2a36;
                        color:#1e2a36;
                        width:100%;
                        justify-content:center;
                    "
                >

                    <i class="bi bi-headset me-1"></i>

                    Talk to a Live Agent

                </button>

            </div>

        </div>



        <!-- ====================================================
             END SESSION
        ===================================================== -->

        <div
            id="userEndSessionBar"
            style="
                display:none;
                padding:8px 14px;
                background:#fff8f8;
                border-top:1px solid #ffdede;
                text-align:center;
            "
        >

            <button
                onclick="userEndSession()"
                style="
                    background:none;
                    border:1px solid #e62429;
                    color:#e62429;
                    border-radius:20px;
                    padding:4px 16px;
                    font-size:12px;
                    font-weight:600;
                    cursor:pointer;
                "
            >

                <i class="bi bi-x-circle me-1"></i>

                End Live Session

            </button>

        </div>



        <!-- ====================================================
             INPUT
        ===================================================== -->

        <div
            style="
                padding:12px 14px;
                background:#fff;
                border-top:1px solid #eee;
                display:flex;
                gap:8px;
                align-items:center;
            "
        >

            <input
                type="text"
                id="chatInput"
                placeholder="Type a message..."
                style="
                    flex:1;
                    border:1px solid #ddd;
                    padding:9px 14px;
                    border-radius:24px;
                    outline:none;
                    font-size:14px;
                    background:#f8f9fa;
                "
                onkeydown="if(event.key==='Enter') sendChatMessage()"
            >


            <button
                onclick="sendChatMessage()"
                style="
                    background:#e62429;
                    color:white;
                    border:none;
                    width:38px;
                    height:38px;
                    border-radius:50%;
                    cursor:pointer;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    flex-shrink:0;
                "
            >

                <i
                    class="bi bi-send-fill"
                    style="font-size:14px;"
                ></i>

            </button>

        </div>

    </div>

</div>



<!-- ============================================================
     CHAT STYLES
============================================================ -->

<style>

.opt-btn {

    background:#fff;

    border:1px solid #e62429;

    color:#e62429;

    border-radius:20px;

    padding:5px 12px;

    font-size:11px;

    cursor:pointer;

    font-weight:600;

    transition:0.2s;

    display:flex;

    align-items:center;

}


.opt-btn:hover {

    background:#e62429;

    color:#fff;

}


/* ============================================================
   GET STARTED BUTTON
============================================================ */

.ejr-get-started-btn {

    display:inline-flex;

    align-items:center;

    justify-content:center;

    gap:5px;

    margin-top:12px;

    padding:9px 18px;

    border:none;

    border-radius:20px;

    background:#e62429;

    color:#fff;

    font-size:12px;

    font-weight:700;

    cursor:pointer;

    transition:all 0.2s ease;

}


.ejr-get-started-btn:hover {

    background:#c91f24;

    color:#fff;

    transform:translateY(-1px);

    box-shadow:
        0 4px 10px
        rgba(230,36,41,0.25);

}


.ejr-get-started-btn:active {

    transform:translateY(0);

}


.ejr-bot-msg {

    background:#fff;

    color:#333;

    padding:10px 14px;

    border-radius:16px 16px 16px 4px;

    font-size:13px;

    align-self:flex-start;

    max-width:85%;

    box-shadow:
        0 1px 3px rgba(0,0,0,0.08);

    line-height:1.5;

}


.ejr-user-msg {

    background:
        linear-gradient(
            135deg,
            #e62429,
            #ff4444
        );

    color:white;

    padding:10px 14px;

    border-radius:16px 16px 4px 16px;

    font-size:13px;

    align-self:flex-end;

    max-width:85%;

    box-shadow:
        0 1px 3px rgba(0,0,0,0.15);

    line-height:1.5;

}


.ejr-time {

    font-size:10px;

    opacity:0.65;

    margin-top:3px;

    text-align:right;

}


.ejr-dot {

    width:7px;

    height:7px;

    background:#999;

    border-radius:50%;

    animation:ejrBounce 1.2s infinite ease-in-out;

}


.ejr-dot:nth-child(2) {

    animation-delay:0.2s;

}


.ejr-dot:nth-child(3) {

    animation-delay:0.4s;

}


@keyframes ejrBounce {

    0%,
    60%,
    100% {

        transform:translateY(0);

    }

    30% {

        transform:translateY(-6px);

    }

}


#chatBody::-webkit-scrollbar {

    width:5px;

}


#chatBody::-webkit-scrollbar-thumb {

    background:#ccc;

    border-radius:10px;

}


.swal2-container {

    z-index:99999999 !important;

}

</style>



<!-- ============================================================
     CHAT JAVASCRIPT
============================================================ -->

<script>

let lastChatSession = 'bot';

let suppressNextPoll = false;


/* ============================================================
   EJR CHAT ACCOUNT MODE
============================================================ */

/*
 * Returns the currently logged-in user.
 */
function getEJRCurrentUser() {

    try {

        return JSON.parse(
            localStorage.getItem('user') || '{}'
        );

    } catch (error) {

        console.error(
            'Unable to read user information:',
            error
        );

        return {};

    }

}


/*
 * Returns true ONLY for role_id = 2.
 */
function isEJRAdmin() {

    const user =
        getEJRCurrentUser();

    return Number(user.role_id) === 2;

}


/*
 * Get JWT token.
 */
function getEJRChatToken() {

    return localStorage.getItem(
        'jwt_token'
    ) || '';

}



// ============================================================
// TOGGLE CHAT
// ============================================================

function toggleChat() {

    const box =
        document.getElementById('chatBox');

    if (!box) {
        return;
    }


    const open =
        box.style.display === 'none';


    box.style.display =
        open ? 'flex' : 'none';


    if (!open) {
        return;
    }


    /*
     * ADMIN MODE
     *
     * The same floating widget is used,
     * but it becomes a customer-message inbox.
     */

    if (isEJRAdmin()) {

        initializeAdminChatWidget();

        return;
    }


    /*
     * CUSTOMER MODE
     */

    loadMessages();


    const notification =
        document.getElementById(
            'chatNotification'
        );

    if (notification) {
        notification.style.display = 'none';
    }

}

/* ============================================================
   ADMIN LIVE CHAT WIDGET
============================================================ */


/*
 * Switch the floating widget into ADMIN mode.
 */
function initializeAdminChatWidget() {

    const title =
        document.getElementById(
            'chatTitle'
        );

    const status =
        document.getElementById(
            'chatStatusLabel'
        );

    const chatBody =
        document.getElementById(
            'chatBody'
        );

    const chatOptions =
        document.getElementById(
            'chatOptions'
        );

    const endSession =
        document.getElementById(
            'userEndSessionBar'
        );

    const waiting =
        document.getElementById(
            'waitingIndicator'
        );

    const live =
        document.getElementById(
            'liveIndicator'
        );

    const input =
        document.getElementById(
            'chatInput'
        );

    const adminPanel =
        document.getElementById(
            'adminChatPanel'
        );


    /*
     * Change title.
     */

    if (title) {

        title.textContent =
            'Customer Live Chat';

    }


    if (status) {

        status.textContent =
            'Incoming customer messages';

    }


    /*
     * Hide customer-only elements.
     */

    if (chatBody) {

        chatBody.style.display =
            'none';

    }


    if (chatOptions) {

        chatOptions.style.display =
            'none';

    }


    if (endSession) {

        endSession.style.display =
            'none';

    }


    if (waiting) {

        waiting.style.display =
            'none';

    }


    if (live) {

        live.style.display =
            'none';

    }


    /*
     * Hide customer message input.
     */

    if (input) {

        const inputContainer =
            input.parentElement;

        if (inputContainer) {

            inputContainer.style.display =
                'none';

        }

    }


    /*
     * Show admin panel.
     */

    if (adminPanel) {

        adminPanel.style.display =
            'flex';

    }


    /*
     * Load customers immediately.
     */

    loadAdminWidgetCustomers();

}



/*
 * Load customers who have chat messages.
 */
function loadAdminWidgetCustomers() {

    if (!isEJRAdmin()) {
        return;
    }


    fetch(
        '/api/admin/get_chat_users.php?t=' +
        Date.now(),
        {
            method: 'GET',

            credentials: 'include',

            headers: {
                'Authorization':
                    'Bearer ' +
                    getEJRChatToken()
            }

        }
    )

    .then(res => {

        if (!res.ok) {

            throw new Error(
                'Unable to load customer messages.'
            );

        }

        return res.json();

    })

    .then(data => {

        if (data.status !== 'success') {

            throw new Error(
                data.message ||
                'Unable to load customer messages.'
            );

        }


        renderAdminWidgetCustomers(
            data.users || []
        );


        /*
         * Update notification badge.
         */

        updateAdminWidgetNotification(
            data.total_unread || 0
        );

    })

    .catch(error => {

        console.error(
            'Admin chat widget error:',
            error
        );

    });

}



/*
 * Display customer list.
 */
function renderAdminWidgetCustomers(
    users
) {

    const list =
        document.getElementById(
            'adminChatCustomerList'
        );

    if (!list) {
        return;
    }


    if (!users.length) {

        list.innerHTML = `

            <div
                style="
                    padding:25px;
                    text-align:center;
                    color:#777;
                    font-size:13px;
                "
            >

                <i
                    class="bi bi-chat-dots"
                    style="
                        font-size:28px;
                        display:block;
                        margin-bottom:8px;
                    "
                ></i>

                No customer messages yet.

            </div>

        `;

        return;
    }


    list.innerHTML =
        users.map(user => {

            const unread =
                Number(
                    user.unread_count || 0
                );


            const name =
                escapeAdminChatText(
                    user.full_name ||
                    'Customer'
                );


            const message =
                escapeAdminChatText(
                    user.last_message ||
                    'New message'
                );


            return `

                <div
                    onclick="
                        selectAdminWidgetCustomer(
                            ${Number(user.id)},
                            '${name.replace(/'/g, "\\'")}'
                        )
                    "
                    style="
                        padding:10px 12px;
                        border-bottom:1px solid #eee;
                        cursor:pointer;
                        background:#fff;
                    "
                    onmouseover="
                        this.style.background='#f8f9fa'
                    "
                    onmouseout="
                        this.style.background='#fff'
                    "
                >

                    <div
                        style="
                            display:flex;
                            justify-content:space-between;
                            align-items:center;
                        "
                    >

                        <strong
                            style="
                                font-size:13px;
                            "
                        >
                            ${name}
                        </strong>

                        ${
                            unread > 0
                            ? `
                                <span
                                    style="
                                        background:#e62429;
                                        color:#fff;
                                        border-radius:20px;
                                        padding:2px 7px;
                                        font-size:10px;
                                        font-weight:700;
                                    "
                                >
                                    ${unread}
                                </span>
                            `
                            : ''
                        }

                    </div>


                    <div
                        style="
                            font-size:11px;
                            color:#777;
                            margin-top:3px;
                            white-space:nowrap;
                            overflow:hidden;
                            text-overflow:ellipsis;
                        "
                    >
                        ${message}
                    </div>

                </div>

            `;

        }).join('');

}



/*
 * Select a customer.
 */
function selectAdminWidgetCustomer(
    userId,
    userName
) {

    const title =
        document.getElementById(
            'adminSelectedCustomer'
        );


    if (title) {

        title.innerHTML = `

            <i
                class="bi bi-person-circle me-1"
            ></i>

            ${escapeAdminChatText(userName)}

        `;

    }


    loadAdminWidgetMessages(
        userId
    );

}



/*
 * Load messages for selected customer.
 */
function loadAdminWidgetMessages(
    userId
) {

    if (!isEJRAdmin()) {
        return;
    }


    fetch(
        '/api/admin/get_messages.php?user_id=' +
        encodeURIComponent(userId) +
        '&t=' +
        Date.now(),
        {
            method: 'GET',

            credentials: 'include',

            headers: {
                'Authorization':
                    'Bearer ' +
                    getEJRChatToken()
            }

        }
    )

    .then(res => {

        if (!res.ok) {

            throw new Error(
                'Unable to load messages.'
            );

        }

        return res.json();

    })

    .then(data => {

        if (data.status !== 'success') {

            throw new Error(
                data.message ||
                'Unable to load messages.'
            );

        }


        const body =
            document.getElementById(
                'adminChatMessages'
            );


        if (!body) {
            return;
        }


        const messages =
            data.messages || [];


        if (!messages.length) {

            body.innerHTML = `

                <div
                    style="
                        text-align:center;
                        color:#888;
                        margin-top:35px;
                        font-size:12px;
                    "
                >
                    No messages yet.
                </div>

            `;

            return;
        }


        body.innerHTML =
            messages.map(message => {

                /*
                 * Admin widget is receive-only.
                 *
                 * We display customer messages.
                 * Admin messages are displayed only as history.
                 */

                const isCustomer =
                    message.sender === 'user';


                return `

                    <div
                        style="
                            display:flex;
                            justify-content:
                                ${
                                    isCustomer
                                    ? 'flex-start'
                                    : 'flex-end'
                                };
                            margin-bottom:8px;
                        "
                    >

                        <div
                            style="
                                max-width:85%;
                                padding:8px 11px;
                                border-radius:12px;
                                font-size:12px;
                                background:
                                    ${
                                        isCustomer
                                        ? '#ffffff'
                                        : '#e62429'
                                    };
                                color:
                                    ${
                                        isCustomer
                                        ? '#222'
                                        : '#ffffff'
                                    };
                                border:
                                    ${
                                        isCustomer
                                        ? '1px solid #ddd'
                                        : 'none'
                                    };
                            "
                        >

                            <div
                                style="
                                    font-size:9px;
                                    font-weight:700;
                                    margin-bottom:3px;
                                    opacity:.75;
                                "
                            >
                                ${
                                    isCustomer
                                    ? 'CUSTOMER'
                                    : 'ADMIN'
                                }
                            </div>

                            <div>
                                ${
                                    escapeAdminChatText(
                                        message.message || ''
                                    )
                                }
                            </div>

                            <div
                                style="
                                    font-size:8px;
                                    margin-top:3px;
                                    opacity:.65;
                                "
                            >
                                ${
                                    escapeAdminChatText(
                                        message.time || ''
                                    )
                                }
                            </div>

                        </div>

                    </div>

                `;

            }).join('');


        body.scrollTop =
            body.scrollHeight;

    })

    .catch(error => {

        console.error(
            'Admin messages error:',
            error
        );

    });

}



/*
 * Update LIVE CHAT notification count.
 */
function updateAdminWidgetNotification(
    count
) {

    const notification =
        document.getElementById(
            'chatNotification'
        );


    if (!notification) {
        return;
    }


    count =
        Number(count || 0);


    if (count > 0) {

        notification.textContent =
            count;

        notification.style.display =
            'flex';

    } else {

        notification.style.display =
            'none';

    }

}



/*
 * Escape customer messages before inserting them
 * into the widget.
 */
function escapeAdminChatText(
    text
) {

    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

}


// ============================================================
// SESSION UI
// ============================================================

// ── Update UI based on session state ────────────────────────
function applySessionUI(session) {
    currentChatMode = session;

    const waiting = document.getElementById('waitingIndicator');
    const live = document.getElementById('liveIndicator');
    const statusLabel = document.getElementById('chatStatusLabel');
    const btnContainer = document.getElementById('dynamicButtonContainer');
    const chatOptions = document.getElementById('chatOptions');
    const endSessionBar = document.getElementById('userEndSessionBar');

    // ---------------------------------------------------------
    // LIVE CHAT MODE
    // ---------------------------------------------------------
    if (session === 'live') {

        lastChatSession = 'live';

        // Hide the bot quick-question buttons
        if (chatOptions) {
            chatOptions.style.display = 'none';
        }

        // Show live status
        if (waiting) {
            waiting.style.display = 'none';
        }

        if (live) {
            live.style.display = 'block';
        }

        if (statusLabel) {
            statusLabel.textContent = 'Live with Admin';
        }

        // Show the End Live Session button
        if (endSessionBar) {
            endSessionBar.style.display = 'block';
        }

        // Make sure the dynamic button is hidden
        if (btnContainer) {
            btnContainer.style.display = 'none';
        }

        return;
    }

    // ---------------------------------------------------------
    // WAITING MODE
    // ---------------------------------------------------------
    if (session === 'waiting') {

        lastChatSession = 'waiting';

        // Keep the bot options visible while waiting
        if (chatOptions) {
            chatOptions.style.display = 'flex';
        }

        if (waiting) {
            waiting.style.display = 'block';
        }

        if (live) {
            live.style.display = 'none';
        }

        if (statusLabel) {
            statusLabel.textContent = 'Connecting to Live Agent...';
        }

        if (endSessionBar) {
            endSessionBar.style.display = 'none';
        }

        // Replace live-agent button with cancel button
        if (btnContainer) {
            btnContainer.style.display = 'block';

            btnContainer.innerHTML = `
                <button
                    onclick="cancelLiveRequest()"
                    class="opt-btn"
                    style="border-color:#6c757d;color:#6c757d;width:100%;justify-content:center;"
                >
                    <i class="bi bi-x-circle me-1"></i>
                    Cancel Request
                </button>
            `;
        }

        return;
    }

    // ---------------------------------------------------------
    // BOT MODE
    // ---------------------------------------------------------

    lastChatSession = 'bot';

    // Show bot quick-question buttons again
    if (chatOptions) {
        chatOptions.style.display = 'flex';
    }

    if (waiting) {
        waiting.style.display = 'none';
    }

    if (live) {
        live.style.display = 'none';
    }

    if (statusLabel) {
        statusLabel.textContent = 'Bot Mode • Always here to help';
    }

    // Hide end-session button
    if (endSessionBar) {
        endSessionBar.style.display = 'none';
    }

    // Show Talk to Live Agent button
    if (btnContainer) {
        btnContainer.style.display = 'block';

        btnContainer.innerHTML = `
            <button
                id="btnRequestLive"
                onclick="requestLiveChat()"
                class="opt-btn"
                style="border-color:#1e2a36;color:#1e2a36;width:100%;justify-content:center;"
            >
                <i class="bi bi-headset me-1"></i>
                Talk to a Live Agent
            </button>
        `;
    }
}

// ============================================================
// RESET CHAT
// ============================================================

// ── Reset chat to fresh bot state ────────────────────────────
function resetChatUI() {
    const body = document.getElementById('chatBody');

    body.innerHTML = `
        <div class="ejr-bot-msg">
            Your session with the admin has ended. Bot mode is now re-enabled. How else can I help you? 😊
        </div>
    `;

    body.scrollTop = 0;

    // Restore bot buttons
    const chatOptions = document.getElementById('chatOptions');

    if (chatOptions) {
        chatOptions.style.display = 'flex';
    }

    const endSessionBar = document.getElementById('userEndSessionBar');

    if (endSessionBar) {
        endSessionBar.style.display = 'none';
    }
}



// ============================================================
// LOAD MESSAGES
// ============================================================

function loadMessages() {

    const pollToken =
        localStorage.getItem(
            'jwt_token'
        ) || '';


    fetch(
        '/api/check_session.php',
        {
            method:'POST',

            headers:{
                'Content-Type':
                    'application/json',

                'Authorization':
                    'Bearer ' +
                    pollToken
            },

            body:JSON.stringify({
                type:'get_messages'
            })

        }
    )


    .then(
        res => res.json()
    )


    .then(
        data => {

            applySessionUI(
                data.chat_session ??
                'bot'
            );


            if (
                data.messages &&
                data.messages.length
            ) {

                const body =
                    document.getElementById(
                        'chatBody'
                    );


                body.innerHTML =
                    data.messages
                        .map(
                            m => `

                                <div
                                    class="${
                                        m.sender === 'user'
                                            ? 'ejr-user-msg'
                                            : 'ejr-bot-msg'
                                    }"
                                >

                                    <div>
                                        ${m.message}
                                    </div>

                                    <div class="ejr-time">
                                        ${m.time}
                                    </div>

                                </div>

                            `
                        )
                        .join('');


                body.scrollTop =
                    body.scrollHeight;

            }

        }
    )


    .catch(
        err =>
            console.error(
                'Load messages error:',
                err
            )
    );

}



// ============================================================
// APPEND MESSAGE
// ============================================================

function appendMessage(
    text,
    sender,
    showBookingButton = false
) {

    const body =
        document.getElementById(
            'chatBody'
        );


    const div =
        document.createElement(
            'div'
        );


    div.className =
        sender === 'user'
            ? 'ejr-user-msg'
            : 'ejr-bot-msg';


    let buttonHTML = '';


    if (
        sender !== 'user' &&
        showBookingButton
    ) {

        buttonHTML = `

            <div style="margin-top:12px;">

                <button
    type="button"
    class="ejr-get-started-btn"
    onclick="startBookingFromChat()"
>
    <i class="bi bi-calendar-check me-1"></i>
    Get Started
</button>

            </div>

        `;

    }


    div.innerHTML = `

        <div>${text}</div>

        ${buttonHTML}

    `;


    body.appendChild(div);


    body.scrollTop =
        body.scrollHeight;

}



// ============================================================
// TYPING INDICATOR
// ============================================================

function showTyping(show) {

    document.getElementById(
        'typingIndicator'
    ).style.display =
        show
            ? 'block'
            : 'none';

}

function startBookingFromChat() {
    const token = localStorage.getItem('jwt_token');

    if (!token) {
        window.location.href = '/login.php';
        return;
    }

    window.location.href = '/booking-details.php';
}


// ============================================================
// SEND CHAT MESSAGE
// ============================================================

// ============================================================
// BOOKING GET STARTED BUTTON
// ============================================================


function sendChatMessage() {

    if (isEJRAdmin()) {

        return;

    }

    const input =
        document.getElementById(
            'chatInput'
        );


    const msg =
        input.value.trim();


    if (!msg) {

        return;

    }


    // ========================================================
    // DISPLAY USER MESSAGE
    // ========================================================

    appendMessage(
        msg,
        'user'
    );


    input.value = '';


    showTyping(true);



    // ========================================================
    // CHECK IF BOOKING / APPOINTMENT
    // ========================================================

    const lowerMessage =
        msg.toLowerCase();


    const isBookingRequest =
        lowerMessage.includes(
            'booking'
        ) ||

        lowerMessage.includes(
            'appointment'
        ) ||

        lowerMessage.includes(
            'appointments'
        ) ||

        lowerMessage === 'book';



    // ========================================================
    // AUTH TOKEN
    // ========================================================

    const chatToken =
        localStorage.getItem(
            'jwt_token'
        ) || '';



    // ========================================================
    // SEND TO SERVER
    // ========================================================

    fetch(
        '/api/check_session.php',
        {
            method:'POST',

            headers:{
                'Content-Type':
                    'application/json',

                'Authorization':
                    'Bearer ' +
                    chatToken
            },

            body:JSON.stringify({

                type:'chat',

                action:'chat',

                message:msg

            })

        }
    )


    .then(
        res => res.json()
    )


    .then(
        data => {

            showTyping(false);


            if (data.reply) {

                appendMessage(
                    data.reply,
                    'admin',

                    isBookingRequest &&
                    data.mode !== 'live'
                );

            }


            applySessionUI(
                data.mode ??
                'bot'
            );

        }
    )


    .catch(
        err => {

            showTyping(false);

            console.error(
                'Send error:',
                err
            );

        }
    );

}



// ============================================================
// QUICK QUESTION
// ============================================================

// ── Quick question buttons ───────────────────────────────────
function askQuickQuestion(question) {

    /*
     * Admins do not use the customer bot.
     */

    if (isEJRAdmin()) {

        return;

    }


    const token =
        localStorage.getItem(
            'jwt_token'
        );

    // existing code continues...
    // Not logged in → go to login
    if (!token) {
        window.location.href = '/login.php';
        return;
    }

    // Logged in → continue normally
    document.getElementById('chatInput').value = question;
    sendChatMessage();
}



// ============================================================
// REQUEST LIVE CHAT
// ============================================================

function requestLiveChat() {

    if (isEJRAdmin()) {

        return;

    }

    const liveToken = localStorage.getItem('jwt_token') || '';

    fetch('/api/check_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + liveToken
        },
        body: JSON.stringify({
            type: 'chat',
            action: 'request_live'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            applySessionUI('live');

            appendMessage(
                data.reply || 'You are now connected to a live agent. How can we help you?',
                'admin'
            );

            loadMessages();
        } else {
            console.error('Live request error:', data.message || 'Unknown error');
        }
    })
    .catch(err => console.error('Live request error:', err));
}



// ============================================================
// CANCEL LIVE REQUEST
// ============================================================

// ============================================================
// END LIVE SESSION
// ============================================================

function userEndSession() {

    Swal.fire({

        title:
            'End Live Session?',

        text:
            'You will be returned to the bot. Your chat history will be cleared.',

        icon:
            'warning',

        showCancelButton:
            true,

        confirmButtonColor:
            '#e62429',

        cancelButtonColor:
            '#6c757d',

        confirmButtonText:
            'Yes, End Session',

        cancelButtonText:
            'Stay in Session'

    })


    .then(
        result => {

            if (
                !result.isConfirmed
            ) {

                return;

            }


            const endToken =
                localStorage.getItem(
                    'jwt_token'
                ) || '';


            fetch(
                '/api/check_session.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' +
                            endToken
                    },

                    body:JSON.stringify({

                        type:'end_session'

                    })

                }
            )


            .then(
                res => res.json()
            )


            .then(
                data => {

                    if (
                        data.status ===
                        'success'
                    ) {

                        resetChatUI();

                        applySessionUI(
                            'bot'
                        );

                    }

                    else {

                        console.error(
                            'Server error:',
                            data.message
                        );

                    }

                }
            )


            .catch(
                err =>
                    console.error(
                        'End session error:',
                        err
                    )
            );

        }
    );

}



// ============================================================
// AUTO REFRESH
// ============================================================

let currentChatMode = 'bot';


setInterval(
    () => {

        const chatBox =
            document.getElementById(
                'chatBox'
            );


        if (
            chatBox.style.display !==
            'flex'
        ) {

            return;

        }


        if (
            currentChatMode !== 'live'
        ) {

            return;

        }


        if (
            suppressNextPoll
        ) {

            suppressNextPoll =
                false;

            return;

        }


        loadMessages();

    },

    4000

);

/* ============================================================
   ADMIN CHAT POLLING
============================================================ */

setInterval(
    () => {

        /*
         * Only run this for admins.
         */

        if (!isEJRAdmin()) {
            return;
        }


        const chatBox =
            document.getElementById(
                'chatBox'
            );


        /*
         * Only poll while the widget is open.
         */

        if (
            !chatBox ||
            chatBox.style.display !== 'flex'
        ) {

            return;

        }


        /*
         * Refresh customer list and unread count.
         */

        loadAdminWidgetCustomers();

    },

    4000
);

</script>



<!-- ============================================================
     FLATPICKR
============================================================ -->

<script>

fetch('api/get_busy_dates.php')

    .then(
        res => res.json()
    )

    .then(
        data => {

            const busyDates =
                data.busy_dates ?? [];


            flatpickr(
                "#appointmentDate",
                {

                    minDate:
                        "today",

                    dateFormat:
                        "Y-m-d",

                    disableMobile:
                        true,


                    disable: [

                        function(date) {

                            return (
                                date.getDay() ===
                                0
                            );

                        }

                    ],


                    onDayCreate:
                        function(
                            dObj,
                            dStr,
                            fp,
                            dayElem
                        ) {

                            const dateStr =
                                dayElem.dateObj
                                    .toISOString()
                                    .split('T')[0];


                            if (
                                busyDates.includes(
                                    dateStr
                                )
                            ) {

                                dayElem.style.background =
                                    '#e62429';

                                dayElem.style.color =
                                    '#fff';

                                dayElem.style.borderRadius =
                                    '50%';

                                dayElem.title =
                                    'Fully booked';

                            }

                        }

                }
            );

        }
    );

</script>
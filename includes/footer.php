<!-- ============================================================
     REQUIRED LIBRARIES
============================================================ -->

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

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"
>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


<!-- ============================================================
     FOOTER
============================================================ -->

<footer
    class="bg-black text-white pt-5 pb-3"
    style="
        border-top:4px solid #b30000;
        position:relative;
        z-index:10;
    "
>

    <div class="container">

        <div class="row g-4 justify-content-between">

            <!-- CONTACT -->
            <div class="col-md-4 text-start">

                <h6
                    class="fw-bold mb-4 text-uppercase"
                    style="letter-spacing:1px;"
                >
                    Contact Us
                </h6>

                <ul class="list-unstyled">

                    <li class="mb-3 d-flex align-items-center">

                        <i
                            class="bi bi-geo-alt-fill text-danger me-3 fs-5"
                        ></i>

                        <span>
                            Mabalacat City, Pampanga
                        </span>

                    </li>

                    <li class="mb-3 d-flex align-items-center">

                        <i
                            class="bi bi-telephone-fill text-danger me-3 fs-5"
                        ></i>

                        <span>
                            0912-345-6789
                        </span>

                    </li>

                    <li class="mb-3 d-flex align-items-center">

                        <i
                            class="bi bi-envelope-fill text-danger me-3 fs-5"
                        ></i>

                        <span>
                            info@ejrgarage.com
                        </span>

                    </li>

                </ul>

            </div>


            <!-- BRAND -->
            <div
                class="col-md-4 text-center d-flex flex-column align-items-center"
            >

                <div class="footer-brand mb-3">

                    <img
                        src="/assets/images/Inverted_EJRLogo.png"
                        alt="EJR Garage"
                        style="
                            height:55px;
                            width:auto;
                        "
                    >

                </div>

                <p class="text-secondary small px-lg-5">

                    Reliable repairs.
                    Honest pricing.
                    Quality service.

                </p>

                <div class="d-flex gap-3 mt-2">

                    <a
                        href="#"
                        class="text-white opacity-75"
                    >
                        <i class="bi bi-facebook fs-5"></i>
                    </a>

                    <a
                        href="#"
                        class="text-white opacity-75"
                    >
                        <i class="bi bi-instagram fs-5"></i>
                    </a>

                    <a
                        href="#"
                        class="text-white opacity-75"
                    >
                        <i class="bi bi-twitter-x fs-5"></i>
                    </a>

                </div>

                <a
                    href="/download-app.php"
                    class="d-inline-flex align-items-center gap-2 mt-3 px-3 py-2 text-white text-decoration-none"
                    style="
                        background:rgba(255,255,255,0.08);
                        border-radius:10px;
                        font-size:0.85rem;
                    "
                >

                    <i
                        class="bi bi-android2 text-danger fs-5"
                    ></i>

                    Get the App

                </a>

            </div>


            <!-- OFFICE HOURS -->
            <div
                class="col-md-4 text-start text-md-end"
            >

                <div class="d-inline-block text-start">

                    <h6
                        class="fw-bold mb-4 text-uppercase"
                        style="letter-spacing:1px;"
                    >
                        Office Hours
                    </h6>

                    <ul class="list-unstyled m-0">

                        <li class="mb-2">

                            Monday – Friday:

                            <span
                                class="text-secondary ps-2"
                            >
                                8:00 AM – 6:00 PM
                            </span>

                        </li>

                        <li class="mb-2">

                            Saturday:

                            <span
                                class="text-secondary ps-2"
                            >
                                8:00 AM – 5:00 PM
                            </span>

                        </li>

                        <li class="mb-0">

                            Sunday:

                            <span
                                class="text-danger fw-bold ps-2"
                            >
                                Closed
                            </span>

                        </li>

                    </ul>

                </div>

            </div>

        </div>


        <div class="row mt-5">

            <div class="col-12 text-center">

                <hr
                    class="border-secondary opacity-25 mb-4"
                >

                <p
                    class="small text-secondary m-0"
                >
                    © 2026 EJR Garage.
                    All rights reserved.
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
        pointer-events:auto;
    "
>

    <!-- CHAT TOGGLE -->

    <div
        id="chatPill"
        onclick="toggleChat()"
        style="
            background:linear-gradient(
                45deg,
                #e62429,
                #ff6b00
            );

            color:white;

            padding:12px 22px;

            border-radius:50px;

            cursor:pointer;

            font-weight:700;

            box-shadow:
                0 4px 15px
                rgba(0,0,0,0.4);

            display:flex;

            align-items:center;

            user-select:none;

            pointer-events:auto;
        "
    >

        <i
            class="bi bi-chat-dots-fill me-2 fs-5"
        ></i>

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


    <!-- CHAT BOX -->

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

            box-shadow:
                0 20px 60px
                rgba(0,0,0,0.25);

            flex-direction:column;

            overflow:hidden;

            border:1px solid #e3e8ef;

            pointer-events:auto;
        "
    >

        <!-- HEADER -->

        <div
            style="
                background:
                    linear-gradient(
                        135deg,
                        #1e2a36,
                        #2d3f50
                    );

                color:white;

                padding:16px 20px;

                display:flex;

                justify-content:space-between;

                align-items:center;

                border-bottom:3px solid #e62429;
            "
        >

            <div
                class="d-flex align-items-center gap-2"
            >

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

                    <i
                        class="bi bi-robot fs-5"
                    ></i>

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
                type="button"
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


        <!-- WAITING INDICATOR -->

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
                style="
                    width:11px;
                    height:11px;
                "
            ></span>

            Live chat starts immediately
            when you request an agent.

        </div>


        <!-- LIVE INDICATOR -->

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

            You are now connected
            to a live admin

        </div>


        <!-- CUSTOMER CHAT BODY -->

        <div
            id="chatBody"
            style="
                height:300px;
                padding:14px;
                background:#f0f2f5;
                overflow-y:auto;
                display:flex;
                flex-direction:column;
                gap:8px;
            "
        >

            <div class="ejr-bot-msg">

                Hello! 👋

                I'm the EJR Assistant.

                How can I help you today?

            </div>

        </div>


        <!-- ====================================================
             ADMIN CHAT PANEL
        ===================================================== -->

        <div
            id="adminChatPanel"
            style="
                display:none;
                height:430px;
                background:#f0f2f5;
                flex-direction:column;
            "
        >

            <!-- CUSTOMER LIST -->

            <div
                id="adminChatCustomerList"
                style="
                    height:135px;
                    min-height:135px;
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


            <!-- ADMIN MESSAGES -->

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
                        margin-top:35px;
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

                    Select a customer
                    to view messages.

                </div>

            </div>


            <!-- =================================================
                 ADMIN END SESSION BAR
            ================================================== -->

            <div
                id="adminEndSessionBar"
                style="
                    display:none;
                    background:#fff8f8;
                    border-top:1px solid #ffdede;
                    padding:8px;
                    text-align:center;
                "
            >

                <button
                    type="button"
                    id="adminEndSessionButton"
                    onclick="adminEndSelectedSession()"
                    style="
                        background:#fff;
                        border:1px solid #e62429;
                        color:#e62429;
                        border-radius:20px;
                        padding:5px 16px;
                        font-size:12px;
                        font-weight:600;
                        cursor:pointer;
                    "
                >

                    <i
                        class="bi bi-x-circle me-1"
                    ></i>

                    End Customer Session

                </button>

            </div>


            <!-- ADMIN REPLY -->

            <div
                id="adminChatReplyArea"
                style="
                    display:none;
                    background:#fff;
                    border-top:1px solid #ddd;
                    padding:8px;
                "
            >

                <div
                    style="
                        display:flex;
                        gap:7px;
                        align-items:center;
                    "
                >

                    <input
                        type="text"
                        id="adminChatReplyInput"
                        class="form-control"
                        placeholder="Reply to customer..."
                        autocomplete="off"
                        style="
                            font-size:12px;
                            border-radius:20px;
                        "
                    >

                    <button
                        type="button"
                        id="adminChatReplyButton"
                        onclick="sendAdminWidgetReply()"
                        style="
                            width:38px;
                            height:38px;
                            min-width:38px;
                            border:none;
                            border-radius:50%;
                            background:#e62429;
                            color:#fff;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        "
                    >

                        <i
                            class="bi bi-send-fill"
                        ></i>

                    </button>

                </div>

            </div>

        </div>


        <!-- TYPING INDICATOR -->

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


        <!-- QUICK OPTIONS -->

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
                type="button"
                onclick="askQuickQuestion('Services Offered')"
                class="opt-btn"
            >

                <i
                    class="bi bi-tools me-1"
                ></i>

                Services

            </button>


            <button
                type="button"
                onclick="askQuickQuestion('Booking Help')"
                class="opt-btn"
            >

                <i
                    class="bi bi-calendar me-1"
                ></i>

                Booking

            </button>


            <button
                type="button"
                onclick="askQuickQuestion('Operating Hours')"
                class="opt-btn"
            >

                <i
                    class="bi bi-clock me-1"
                ></i>

                Hours

            </button>


            <button
                type="button"
                onclick="askQuickQuestion('Location')"
                class="opt-btn"
            >

                <i
                    class="bi bi-geo-alt me-1"
                ></i>

                Location

            </button>


            <button
                type="button"
                onclick="askQuickQuestion('Contact')"
                class="opt-btn"
            >

                <i
                    class="bi bi-telephone me-1"
                ></i>

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
                    type="button"
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

                    <i
                        class="bi bi-headset me-1"
                    ></i>

                    Talk to a Live Agent

                </button>

            </div>

        </div>


        <!-- CUSTOMER END SESSION -->

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
                type="button"
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

                <i
                    class="bi bi-x-circle me-1"
                ></i>

                End Live Session

            </button>

        </div>


        <!-- CUSTOMER INPUT -->

        <div
            id="customerChatInputContainer"
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
                autocomplete="off"
                style="
                    flex:1;
                    border:1px solid #ddd;
                    padding:9px 14px;
                    border-radius:24px;
                    outline:none;
                    font-size:14px;
                    background:#f8f9fa;
                "
                onkeydown="
                    if(event.key === 'Enter') {
                        sendChatMessage();
                    }
                "
            >

            <button
                type="button"
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


.ejr-bot-msg {

    background:#fff;

    color:#333;

    padding:10px 14px;

    border-radius:
        16px 16px 16px 4px;

    font-size:13px;

    align-self:flex-start;

    max-width:85%;

    box-shadow:
        0 1px 3px
        rgba(0,0,0,0.08);

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

    border-radius:
        16px 16px 4px 16px;

    font-size:13px;

    align-self:flex-end;

    max-width:85%;

    box-shadow:
        0 1px 3px
        rgba(0,0,0,0.15);

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

    animation:
        ejrBounce
        1.2s
        infinite
        ease-in-out;

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


#chatBody::-webkit-scrollbar,
#adminChatMessages::-webkit-scrollbar {

    width:5px;

}


#chatBody::-webkit-scrollbar-thumb,
#adminChatMessages::-webkit-scrollbar-thumb {

    background:#ccc;

    border-radius:10px;

}


.swal2-container {

    z-index:99999999 !important;

}


@media (max-width:500px) {

    #ejrChatContainer {

        right:15px !important;

        bottom:15px !important;

    }


    #chatBox {

        width:
            calc(100vw - 30px) !important;

        right:0 !important;

    }


    #chatPill {

        padding:
            11px 17px !important;

    }

}

</style>

<!-- ============================================================
     CHAT JAVASCRIPT - PART 2
     
     CUSTOMER CHAT
     ADMIN CHAT
     ADMIN END SESSION
     CUSTOMER END SESSION
     POLLING
============================================================ -->

<script>

/* ============================================================
   GLOBAL CHAT STATE
============================================================ */

let lastChatSession = 'bot';

let currentChatMode = 'bot';

let suppressNextPoll = false;

let adminSelectedCustomerId = null;

let adminSelectedCustomerName = '';



/* ============================================================
   GET CURRENT USER
============================================================ */

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



/* ============================================================
   CHECK ADMIN
============================================================ */

function isEJRAdmin() {

    const user =
        getEJRCurrentUser();

    return Number(user.role_id) === 2;

}



/* ============================================================
   GET JWT TOKEN
============================================================ */

function getEJRChatToken() {

    return (
        localStorage.getItem('jwt_token') || ''
    );

}



/* ============================================================
   TOGGLE CHAT
============================================================ */

function toggleChat() {

    const box =
        document.getElementById('chatBox');

    if (!box) {

        console.error(
            'EJR CHAT ERROR: #chatBox not found.'
        );

        return;

    }

    const isOpen =
        box.style.display === 'flex';

    if (isOpen) {

        box.style.display = 'none';

        return;

    }

    box.style.display = 'flex';


    /* ========================================================
       ADMIN
    ======================================================== */

    if (isEJRAdmin()) {

        initializeAdminChatWidget();

        return;

    }


    /* ========================================================
       CUSTOMER
    ======================================================== */

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
   INITIALIZE ADMIN CHAT
============================================================ */

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

    const inputContainer =
        document.getElementById(
            'customerChatInputContainer'
        );

    const adminPanel =
        document.getElementById(
            'adminChatPanel'
        );


    if (title) {

        title.textContent =
            'Customer Live Chat';

    }


    if (status) {

        status.textContent =
            'Incoming customer messages';

    }


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


    if (inputContainer) {

        inputContainer.style.display =
            'none';

    }


    if (adminPanel) {

        adminPanel.style.display =
            'flex';

    }


    loadAdminWidgetCustomers();

}



/* ============================================================
   LOAD ADMIN CUSTOMERS
============================================================ */

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

    .then(response => {

        if (!response.ok) {

            throw new Error(
                'Unable to load customer messages.'
            );

        }

        return response.json();

    })

    .then(data => {

        if (
            data.status !== 'success'
        ) {

            throw new Error(
                data.message ||
                'Unable to load customer messages.'
            );

        }


        renderAdminWidgetCustomers(
            data.users || []
        );


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



/* ============================================================
   RENDER ADMIN CUSTOMER LIST
============================================================ */

function renderAdminWidgetCustomers(users) {

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


            const safeName =
                String(
                    user.full_name ||
                    'Customer'
                )
                .replace(
                    /\\/g,
                    '\\\\'
                )
                .replace(
                    /'/g,
                    "\\'"
                );


            return `

                <div

                    onclick="
                        selectAdminWidgetCustomer(
                            ${Number(user.id)},
                            '${safeName}'
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



/* ============================================================
   SELECT ADMIN CUSTOMER
============================================================ */

function selectAdminWidgetCustomer(
    userId,
    userName
) {

    if (!isEJRAdmin()) {

        return;

    }


    adminSelectedCustomerId =
        Number(userId);


    adminSelectedCustomerName =
        userName ||
        'Customer';


    const title =
        document.getElementById(
            'adminSelectedCustomer'
        );


    if (title) {

        title.innerHTML = `

            <div
                style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    gap:10px;
                "
            >

                <div>

                    <i
                        class="bi bi-person-circle me-1"
                    ></i>

                    ${
                        escapeAdminChatText(
                            adminSelectedCustomerName
                        )
                    }

                </div>


                <!-- ADMIN END SESSION BUTTON -->

                <button
                    type="button"
                    onclick="adminEndSelectedCustomerSession()"
                    style="
                        border:1px solid #e62429;
                        background:#fff;
                        color:#e62429;
                        border-radius:16px;
                        padding:4px 9px;
                        font-size:10px;
                        font-weight:700;
                        cursor:pointer;
                        white-space:nowrap;
                    "
                    title="End this customer's live session"
                >

                    <i
                        class="bi bi-x-circle me-1"
                    ></i>

                    End Session

                </button>

            </div>

        `;

    }


    const replyArea =
        document.getElementById(
            'adminChatReplyArea'
        );


    if (replyArea) {

        replyArea.style.display =
            'block';

    }


    loadAdminWidgetMessages(
        adminSelectedCustomerId
    );


    const input =
        document.getElementById(
            'adminChatReplyInput'
        );


    if (input) {

        setTimeout(
            () => input.focus(),
            100
        );

    }

}



/* ============================================================
   ADMIN SEND REPLY
============================================================ */

function sendAdminWidgetReply() {

    if (!isEJRAdmin()) {

        return;

    }


    if (!adminSelectedCustomerId) {

        return;

    }


    const input =
        document.getElementById(
            'adminChatReplyInput'
        );


    const button =
        document.getElementById(
            'adminChatReplyButton'
        );


    if (!input) {

        return;

    }


    const message =
        input.value.trim();


    if (!message) {

        return;

    }


    input.disabled =
        true;


    if (button) {

        button.disabled =
            true;

    }


    fetch(
        '/api/admin/send_message.php',
        {

            method: 'POST',

            credentials: 'include',

            headers: {

                'Content-Type':
                    'application/json',

                'Authorization':
                    'Bearer ' +
                    getEJRChatToken()

            },

            body: JSON.stringify({

                user_id:
                    adminSelectedCustomerId,

                message:
                    message

            })

        }
    )

    .then(response => {

        return response.json()
            .then(data => {

                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        'Unable to send reply.'
                    );

                }

                return data;

            });

    })

    .then(data => {

        if (
            data.status !== 'success'
        ) {

            throw new Error(
                data.message ||
                'Unable to send reply.'
            );

        }


        input.value = '';


        loadAdminWidgetMessages(
            adminSelectedCustomerId
        );


        loadAdminWidgetCustomers();

    })

    .catch(error => {

        console.error(
            'Admin reply error:',
            error
        );


        alert(
            error.message ||
            'Unable to send reply.'
        );

    })

    .finally(() => {

        input.disabled =
            false;


        if (button) {

            button.disabled =
                false;

        }


        input.focus();

    });

}



/* ============================================================
   ADMIN ENTER KEY
============================================================ */

document.addEventListener(
    'keydown',
    function(event) {

        if (
            event.key !== 'Enter' ||
            event.shiftKey
        ) {

            return;

        }


        const input =
            document.getElementById(
                'adminChatReplyInput'
            );


        if (
            !input ||
            document.activeElement !== input
        ) {

            return;

        }


        event.preventDefault();


        sendAdminWidgetReply();

    }
);



/* ============================================================
   ADMIN END SELECTED CUSTOMER SESSION
============================================================ */

function adminEndSelectedCustomerSession() {

    if (!isEJRAdmin()) {

        alert(
            'Only an admin can end a customer session.'
        );

        return;

    }


    if (!adminSelectedCustomerId) {

        alert(
            'Please select a customer first.'
        );

        return;

    }


    const customerName =
        adminSelectedCustomerName ||
        'this customer';


    /* ========================================================
       SWEETALERT
    ======================================================== */

    if (
        typeof Swal !== 'undefined'
    ) {

        Swal.fire({

            title:
                'End Live Session?',

            html:
                `
                End the live session with
                <strong>
                    ${escapeAdminChatText(customerName)}
                </strong>?
                `,

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
                'Cancel'

        })

        .then(result => {

            if (
                result.isConfirmed
            ) {

                sendAdminEndSession();

            }

        });

        return;

    }


    /* ========================================================
       NORMAL CONFIRM
    ======================================================== */

    if (
        confirm(
            'End the live session with ' +
            customerName +
            '?'
        )
    ) {

        sendAdminEndSession();

    }

}



/* ============================================================
   SEND ADMIN END SESSION REQUEST
============================================================ */

function sendAdminEndSession() {

    if (!isEJRAdmin()) {

        return;

    }


    if (!adminSelectedCustomerId) {

        return;

    }


    const token =
        getEJRChatToken();


    if (!token) {

        alert(
            'Your admin session has expired. Please log in again.'
        );

        return;

    }


    const selectedUserId =
        adminSelectedCustomerId;


    fetch(
        '/api/admin/end_session.php',
        {

            method: 'POST',

            credentials: 'include',

            headers: {

                'Content-Type':
                    'application/json',

                'Authorization':
                    'Bearer ' +
                    token

            },

            body: JSON.stringify({

                user_id:
                    selectedUserId

            })

        }
    )

    .then(response => {

        return response.json()
            .then(data => {

                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        'Unable to end the session.'
                    );

                }

                return data;

            });

    })

    .then(data => {

        console.log(
            'Admin end session response:',
            data
        );


        if (
            data.status !== 'success'
        ) {

            throw new Error(
                data.message ||
                'Unable to end the session.'
            );

        }


        /* ====================================================
           CLEAR SELECTED CUSTOMER
        ==================================================== */

        adminSelectedCustomerId =
            null;

        adminSelectedCustomerName =
            '';


        const title =
            document.getElementById(
                'adminSelectedCustomer'
            );


        if (title) {

            title.innerHTML = `

                <div
                    style="
                        text-align:center;
                        color:#777;
                        font-size:12px;
                    "
                >

                    Customer session ended.

                </div>

            `;

        }


        const messages =
            document.getElementById(
                'adminChatMessages'
            );


        if (messages) {

            messages.innerHTML = `

                <div
                    style="
                        text-align:center;
                        color:#888;
                        margin-top:35px;
                        font-size:13px;
                    "
                >

                    <i
                        class="bi bi-check-circle"
                        style="
                            font-size:30px;
                            display:block;
                            margin-bottom:8px;
                            color:#198754;
                        "
                    ></i>

                    Live session ended.

                </div>

            `;

        }


        const replyArea =
            document.getElementById(
                'adminChatReplyArea'
            );


        if (replyArea) {

            replyArea.style.display =
                'none';

        }


        /* ====================================================
           REFRESH CUSTOMER LIST
        ==================================================== */

        loadAdminWidgetCustomers();


        /* ====================================================
           SUCCESS MESSAGE
        ==================================================== */

        if (
            typeof Swal !== 'undefined'
        ) {

            Swal.fire({

                icon:
                    'success',

                title:
                    'Session Ended',

                text:
                    'The customer has been returned to bot mode.',

                confirmButtonColor:
                    '#e62429',

                timer:
                    1800,

                showConfirmButton:
                    false

            });

        }

    })

    .catch(error => {

        console.error(
            'Admin end session error:',
            error
        );


        if (
            typeof Swal !== 'undefined'
        ) {

            Swal.fire({

                icon:
                    'error',

                title:
                    'Unable to End Session',

                text:
                    error.message ||
                    'Something went wrong.',

                confirmButtonColor:
                    '#e62429'

            });

        }

        else {

            alert(
                error.message ||
                'Unable to end the session.'
            );

        }

    });

}



/* ============================================================
   LOAD ADMIN MESSAGES
============================================================ */

function loadAdminWidgetMessages(userId) {

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

    .then(response => {

        if (!response.ok) {

            throw new Error(
                'Unable to load messages.'
            );

        }

        return response.json();

    })

    .then(data => {

        if (
            data.status !== 'success'
        ) {

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



/* ============================================================
   ADMIN NOTIFICATION
============================================================ */

function updateAdminWidgetNotification(count) {

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

    }

    else {

        notification.style.display =
            'none';

    }

}



/* ============================================================
   ESCAPE HTML
============================================================ */

function escapeAdminChatText(text) {

    return String(text)

        .replace(
            /&/g,
            '&amp;'
        )

        .replace(
            /</g,
            '&lt;'
        )

        .replace(
            />/g,
            '&gt;'
        )

        .replace(
            /"/g,
            '&quot;'
        )

        .replace(
            /'/g,
            '&#039;'
        );

}



/* ============================================================
   SESSION UI
============================================================ */

function applySessionUI(session) {

    currentChatMode =
        session;


    const waiting =
        document.getElementById(
            'waitingIndicator'
        );

    const live =
        document.getElementById(
            'liveIndicator'
        );

    const statusLabel =
        document.getElementById(
            'chatStatusLabel'
        );

    const btnContainer =
        document.getElementById(
            'dynamicButtonContainer'
        );

    const chatOptions =
        document.getElementById(
            'chatOptions'
        );

    const endSessionBar =
        document.getElementById(
            'userEndSessionBar'
        );


    /* ========================================================
       LIVE
    ======================================================== */

    if (session === 'live') {

        lastChatSession =
            'live';


        if (chatOptions) {

            chatOptions.style.display =
                'none';

        }


        if (waiting) {

            waiting.style.display =
                'none';

        }


        if (live) {

            live.style.display =
                'block';

        }


        if (statusLabel) {

            statusLabel.textContent =
                'Live with Admin';

        }


        if (endSessionBar) {

            endSessionBar.style.display =
                'block';

        }


        if (btnContainer) {

            btnContainer.style.display =
                'none';

        }


        return;

    }


    /* ========================================================
       WAITING
    ======================================================== */

    if (session === 'waiting') {

        lastChatSession =
            'waiting';


        if (chatOptions) {

            chatOptions.style.display =
                'flex';

        }


        if (waiting) {

            waiting.style.display =
                'block';

        }


        if (live) {

            live.style.display =
                'none';

        }


        if (statusLabel) {

            statusLabel.textContent =
                'Connecting to Live Agent...';

        }


        if (endSessionBar) {

            endSessionBar.style.display =
                'none';

        }


        if (btnContainer) {

            btnContainer.style.display =
                'block';


            btnContainer.innerHTML = `

                <button

                    type="button"

                    onclick="cancelLiveRequest()"

                    class="opt-btn"

                    style="
                        border-color:#6c757d;
                        color:#6c757d;
                        width:100%;
                        justify-content:center;
                    "
                >

                    <i
                        class="bi bi-x-circle me-1"
                    ></i>

                    Cancel Request

                </button>

            `;

        }


        return;

    }


    /* ========================================================
       BOT
    ======================================================== */

    lastChatSession =
        'bot';


    if (chatOptions) {

        chatOptions.style.display =
            'flex';

    }


    if (waiting) {

        waiting.style.display =
            'none';

    }


    if (live) {

        live.style.display =
            'none';

    }


    if (statusLabel) {

        statusLabel.textContent =
            'Bot Mode • Always here to help';

    }


    if (endSessionBar) {

        endSessionBar.style.display =
            'none';

    }


    if (btnContainer) {

        btnContainer.style.display =
            'block';


        btnContainer.innerHTML = `

            <button
                type="button"
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

                <i
                    class="bi bi-headset me-1"
                ></i>

                Talk to a Live Agent

            </button>

        `;

    }

}



/* ============================================================
   RESET CUSTOMER CHAT
============================================================ */

function resetChatUI() {

    const body =
        document.getElementById(
            'chatBody'
        );


    if (body) {

        body.innerHTML = `

            <div class="ejr-bot-msg">

                Your session with the admin
                has ended.

                Bot mode is now re-enabled.

                How else can I help you? 😊

            </div>

        `;

        body.scrollTop =
            0;

    }


    const chatOptions =
        document.getElementById(
            'chatOptions'
        );


    if (chatOptions) {

        chatOptions.style.display =
            'flex';

    }


    const endSessionBar =
        document.getElementById(
            'userEndSessionBar'
        );


    if (endSessionBar) {

        endSessionBar.style.display =
            'none';

    }

}



/* ============================================================
   LOAD CUSTOMER MESSAGES
============================================================ */

function loadMessages() {

    const body =
        document.getElementById(
            'chatBody'
        );


    if (!body) {

        return;

    }


    const pollToken =
        localStorage.getItem(
            'jwt_token'
        ) || '';


    if (!pollToken) {

        return;

    }


    fetch(
        '/api/check_session.php',
        {

            method: 'POST',

            headers: {

                'Content-Type':
                    'application/json',

                'Authorization':
                    'Bearer ' +
                    pollToken

            },

            body: JSON.stringify({

                type:
                    'get_messages'

            })

        }
    )

    .then(
        response =>
            response.json()
    )

    .then(data => {

        applySessionUI(
            data.chat_session ??
            'bot'
        );


        if (
            data.messages &&
            data.messages.length
        ) {

            body.innerHTML =
                data.messages
                    .map(m => `

                        <div
                            class="${
                                m.sender === 'user'
                                ? 'ejr-user-msg'
                                : 'ejr-bot-msg'
                            }"
                        >

                            <div>
                                ${
                                    escapeAdminChatText(
                                        m.message || ''
                                    )
                                }
                            </div>

                            <div class="ejr-time">
                                ${
                                    escapeAdminChatText(
                                        m.time || ''
                                    )
                                }
                            </div>

                        </div>

                    `)
                    .join('');


            body.scrollTop =
                body.scrollHeight;

        }

    })

    .catch(error => {

        console.error(
            'Load messages error:',
            error
        );

    });

}



/* ============================================================
   APPEND MESSAGE
============================================================ */

function appendMessage(
    text,
    sender,
    showBookingButton = false
) {

    const body =
        document.getElementById(
            'chatBody'
        );


    if (!body) {

        return;

    }


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

            <div
                style="margin-top:12px;"
            >

                <button
                    type="button"
                    class="ejr-get-started-btn"
                    onclick="startBookingFromChat()"
                >

                    <i
                        class="bi bi-calendar-check me-1"
                    ></i>

                    Get Started

                </button>

            </div>

        `;

    }


    div.innerHTML = `

        <div>
            ${text}
        </div>

        ${buttonHTML}

    `;


    body.appendChild(div);


    body.scrollTop =
        body.scrollHeight;

}



/* ============================================================
   TYPING INDICATOR
============================================================ */

function showTyping(show) {

    const typing =
        document.getElementById(
            'typingIndicator'
        );


    if (!typing) {

        return;

    }


    typing.style.display =
        show
        ? 'block'
        : 'none';

}



/* ============================================================
   BOOKING
============================================================ */

function startBookingFromChat() {

    const token =
        localStorage.getItem(
            'jwt_token'
        );


    if (!token) {

        window.location.href =
            '/login.php';

        return;

    }


    window.location.href =
        '/booking-details.php';

}



/* ============================================================
   SEND CUSTOMER CHAT MESSAGE
============================================================ */

function sendChatMessage() {

    if (isEJRAdmin()) {

        return;

    }


    const input =
        document.getElementById(
            'chatInput'
        );


    if (!input) {

        return;

    }


    const msg =
        input.value.trim();


    if (!msg) {

        return;

    }


    appendMessage(
        msg,
        'user'
    );


    input.value = '';


    showTyping(true);


    const lowerMessage =
        msg.toLowerCase();


    const isBookingRequest =

        lowerMessage.includes(
            'booking'
        )

        ||

        lowerMessage.includes(
            'appointment'
        )

        ||

        lowerMessage.includes(
            'appointments'
        )

        ||

        lowerMessage ===
            'book';


    const chatToken =
        localStorage.getItem(
            'jwt_token'
        ) || '';


    fetch(
        '/api/check_session.php',
        {

            method: 'POST',

            headers: {

                'Content-Type':
                    'application/json',

                'Authorization':
                    'Bearer ' +
                    chatToken

            },

            body: JSON.stringify({

                type:
                    'chat',

                action:
                    'chat',

                message:
                    msg

            })

        }
    )

    .then(response => {

        if (!response.ok) {

            throw new Error(
                'Chat request failed.'
            );

        }

        return response.json();

    })

    .then(data => {

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

    })

    .catch(error => {

        showTyping(false);


        console.error(
            'Send error:',
            error
        );


        appendMessage(
            'Sorry, I was unable to process your message. Please try again.',
            'admin'
        );

    });

}



/* ============================================================
   QUICK QUESTIONS
============================================================ */

function askQuickQuestion(question) {

    if (isEJRAdmin()) {

        return;

    }


    const token =
        localStorage.getItem(
            'jwt_token'
        );


    if (!token) {

        window.location.href =
            '/login.php';

        return;

    }


    const input =
        document.getElementById(
            'chatInput'
        );


    if (!input) {

        return;

    }


    input.value =
        question;


    sendChatMessage();

}



/* ============================================================
   REQUEST LIVE CHAT
============================================================ */

function requestLiveChat() {

    if (isEJRAdmin()) {

        return;

    }


    const liveToken =
        localStorage.getItem(
            'jwt_token'
        ) || '';


    if (!liveToken) {

        window.location.href =
            '/login.php';

        return;

    }


    const button =
        document.getElementById(
            'btnRequestLive'
        );


    if (button) {

        button.disabled =
            true;

        button.innerHTML = `

            <span
                class="spinner-border spinner-border-sm me-1"
            ></span>

            Connecting...

        `;

    }


    fetch(
        '/api/check_session.php',
        {

            method: 'POST',

            headers: {

                'Content-Type':
                    'application/json',

                'Authorization':
                    'Bearer ' +
                    liveToken

            },

            body: JSON.stringify({

                type:
                    'chat',

                action:
                    'request_live'

            })

        }
    )

    .then(response => {

        if (!response.ok) {

            throw new Error(
                'Live chat request failed.'
            );

        }

        return response.json();

    })

    .then(data => {

        if (
            data.status === 'success'
        ) {

            applySessionUI(
                data.mode ||
                'live'
            );


            appendMessage(

                data.reply ||
                'You are now connected to a live agent. How can we help you?',

                'admin'

            );


            loadMessages();

        }

        else {

            alert(
                data.message ||
                'Unable to connect to a live agent.'
            );


            applySessionUI('bot');

        }

    })

    .catch(error => {

        console.error(
            'Live request error:',
            error
        );


        alert(
            'Unable to connect to live chat. Please try again.'
        );


        applySessionUI('bot');

    });

}



/* ============================================================
   CANCEL LIVE REQUEST
============================================================ */

function cancelLiveRequest() {

    const token =
        localStorage.getItem(
            'jwt_token'
        ) || '';


    if (!token) {

        return;

    }


    fetch(
        '/api/check_session.php',
        {

            method: 'POST',

            headers: {

                'Content-Type':
                    'application/json',

                'Authorization':
                    'Bearer ' +
                    token

            },

            body: JSON.stringify({

                type:
                    'cancel_live'

            })

        }
    )

    .then(response =>
        response.json()
    )

    .then(data => {

        if (
            data.status === 'success'
        ) {

            applySessionUI('bot');

        }

        else {

            console.error(
                'Cancel error:',
                data.message
            );

        }

    })

    .catch(error => {

        console.error(
            'Cancel live error:',
            error
        );

    });

}



/* ============================================================
   CUSTOMER END LIVE SESSION
============================================================ */

function userEndSession() {

    if (
        typeof Swal === 'undefined'
    ) {

        if (
            confirm(
                'End Live Session?'
            )
        ) {

            endLiveSessionRequest();

        }

        return;

    }


    Swal.fire({

        title:
            'End Live Session?',

        text:
            'You will be returned to the bot.',

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

    .then(result => {

        if (
            result.isConfirmed
        ) {

            endLiveSessionRequest();

        }

    });

}



/* ============================================================
   CUSTOMER END SESSION REQUEST
============================================================ */

function endLiveSessionRequest() {

    const token =
        localStorage.getItem(
            'jwt_token'
        ) || '';


    if (!token) {

        return;

    }


    fetch(
        '/api/check_session.php',
        {

            method: 'POST',

            headers: {

                'Content-Type':
                    'application/json',

                'Authorization':
                    'Bearer ' +
                    token

            },

            body: JSON.stringify({

                type:
                    'end_session'

            })

        }
    )

    .then(response =>
        response.json()
    )

    .then(data => {

        if (
            data.status === 'success'
        ) {

            resetChatUI();

            applySessionUI('bot');

        }

        else {

            console.error(
                'Server error:',
                data.message
            );

        }

    })

    .catch(error => {

        console.error(
            'End session error:',
            error
        );

    });

}



/* ============================================================
   CUSTOMER CHAT POLLING
============================================================ */

setInterval(
    () => {

        const chatBox =
            document.getElementById(
                'chatBox'
            );


        if (
            !chatBox ||
            chatBox.style.display !== 'flex'
        ) {

            return;

        }


        if (
            isEJRAdmin()
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

        if (
            !isEJRAdmin()
        ) {

            return;

        }


        const chatBox =
            document.getElementById(
                'chatBox'
            );


        if (
            !chatBox ||
            chatBox.style.display !== 'flex'
        ) {

            return;

        }


        loadAdminWidgetCustomers();


        if (
            adminSelectedCustomerId
        ) {

            loadAdminWidgetMessages(
                adminSelectedCustomerId
            );

        }

    },

    4000
);



/* ============================================================
   DOM LOADED
============================================================ */

document.addEventListener(
    'DOMContentLoaded',
    function() {

        console.log(
            'EJR Chat JavaScript loaded.'
        );


        const pill =
            document.getElementById(
                'chatPill'
            );


        const box =
            document.getElementById(
                'chatBox'
            );


        const body =
            document.getElementById(
                'chatBody'
            );


        console.log(
            'Chat pill:',
            pill
        );


        console.log(
            'Chat box:',
            box
        );


        console.log(
            'Chat body:',
            body
        );

    }
);

</script>
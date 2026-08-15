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


            <!-- =================================================
                 HEADER BUTTONS
            ================================================== -->

            <div
                style="
                    display:flex;
                    align-items:center;
                    gap:4px;
                "
            >

                <!-- FULL SCREEN BUTTON -->

                <button
                    type="button"
                    id="chatFullscreenButton"
                    onclick="toggleChatFullscreen()"
                    title="Full Screen"
                    aria-label="Full Screen"
                    style="
                        background:none;
                        border:none;
                        color:#fff;
                        cursor:pointer;
                        width:32px;
                        height:32px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:8px;
                        font-size:15px;
                        opacity:.9;
                    "
                >

                    <i
                        class="bi bi-arrows-fullscreen"
                    ></i>

                </button>


                <!-- EXISTING CLOSE BUTTON -->

                <button
                    type="button"
                    onclick="toggleChat()"
                    title="Close Chat"
                    aria-label="Close Chat"
                    style="
                        background:none;
                        border:none;
                        color:#fff;
                        cursor:pointer;
                        width:32px;
                        height:32px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:8px;
                        font-size:22px;
                        opacity:.8;
                    "
                >

                    &times;

                </button>

            </div>

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

                <!-- ====================================================
             CUSTOMER TYPING INDICATOR
        ===================================================== -->

        <div
            id="typingIndicator"
            style="
                display:none;
                padding:0 14px 8px;
                background:#f0f2f5;
            "
        >

            <div
                style="
                    display:inline-flex;
                    align-items:center;
                    gap:4px;
                    background:#fff;
                    padding:7px 11px;
                    border-radius:14px;
                    color:#777;
                    font-size:11px;
                "
            >

                <span
                    style="
                        width:5px;
                        height:5px;
                        background:#999;
                        border-radius:50%;
                        display:inline-block;
                    "
                ></span>

                <span
                    style="
                        width:5px;
                        height:5px;
                        background:#999;
                        border-radius:50%;
                        display:inline-block;
                    "
                ></span>

                <span
                    style="
                        width:5px;
                        height:5px;
                        background:#999;
                        border-radius:50%;
                        display:inline-block;
                    "
                ></span>

                <span>
                    Admin is typing...
                </span>

            </div>

        </div>


        <!-- ====================================================
             CHAT OPTIONS
        ===================================================== -->

        <div
            id="chatOptions"
            style="
                display:flex;
                flex-wrap:wrap;
                gap:6px;
                padding:10px 12px;
                background:#fff;
                border-top:1px solid #eee;
            "
        >

            <button
                type="button"
                onclick="askQuickQuestion('Services')"
                class="chat-option-btn"
            >

                <i class="bi bi-tools"></i>

                Services

            </button>


            <button
                type="button"
                onclick="askQuickQuestion('Booking')"
                class="chat-option-btn"
            >

                <i class="bi bi-calendar-check"></i>

                Booking

            </button>


            <button
                type="button"
                onclick="askQuickQuestion('Hours')"
                class="chat-option-btn"
            >

                <i class="bi bi-clock"></i>

                Hours

            </button>


            <button
                type="button"
                onclick="askQuickQuestion('Location')"
                class="chat-option-btn"
            >

                <i class="bi bi-geo-alt-fill"></i>

                Location

            </button>


            <button
                type="button"
                onclick="askQuickQuestion('Contact')"
                class="chat-option-btn"
            >

                <i class="bi bi-telephone-fill"></i>

                Contact

            </button>

        </div>


        <!-- ====================================================
             TALK TO AGENT
        ===================================================== -->

        <div
            id="talkAgentArea"
            style="
                display:none;
                padding:8px 12px;
                background:#fff;
                border-top:1px solid #eee;
            "
        >

            <button
                type="button"
                id="talkAgentButton"
                onclick="requestLiveChat()"
                style="
                    width:100%;
                    border:none;
                    border-radius:10px;
                    background:#e62429;
                    color:#fff;
                    padding:10px;
                    font-size:13px;
                    font-weight:700;
                    cursor:pointer;
                "
            >

                <i
                    class="bi bi-person-headset me-1"
                ></i>

                Talk to a Live Agent

            </button>

        </div>


        <!-- ====================================================
             CUSTOMER LIVE CHAT INPUT
        ===================================================== -->

        <div
            id="customerChatInputContainer"
            style="
                display:none;
                background:#fff;
                border-top:1px solid #ddd;
                padding:9px;
            "
        >

            <div
                style="
                    display:flex;
                    align-items:center;
                    gap:7px;
                "
            >

                <input
                    type="text"
                    id="chatInput"
                    class="form-control"
                    placeholder="Type your message..."
                    autocomplete="off"
                    style="
                        border-radius:22px;
                        font-size:13px;
                        padding-left:15px;
                        padding-right:15px;
                    "
                >


                <button
                    type="button"
                    id="chatSendButton"
                    onclick="sendChatMessage()"
                    style="
                        width:40px;
                        height:40px;
                        min-width:40px;
                        border:none;
                        border-radius:50%;
                        background:#e62429;
                        color:#fff;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        cursor:pointer;
                    "
                >

                    <i
                        class="bi bi-send-fill"
                    ></i>

                </button>

            </div>


            <div
                style="
                    text-align:center;
                    margin-top:6px;
                "
            >

                <button
                    type="button"
                    onclick="endLiveSession()"
                    style="
                        border:none;
                        background:none;
                        color:#e62429;
                        font-size:11px;
                        cursor:pointer;
                        padding:3px 8px;
                    "
                >

                    <i
                        class="bi bi-x-circle me-1"
                    ></i>

                    End Live Session

                </button>

            </div>

        </div>


    </div>

</div>


<!-- ============================================================
     CHAT STYLES
============================================================ -->

<style>

/* ============================================================
   CHAT OPTION BUTTONS
============================================================ */

.chat-option-btn {

    border:1px solid #e1e5ea;

    background:#fff;

    color:#333;

    border-radius:20px;

    padding:6px 10px;

    font-size:11px;

    cursor:pointer;

    transition:
        all .2s ease;

}


.chat-option-btn:hover {

    background:#f8f9fa;

    border-color:#e62429;

    color:#e62429;

}


/* ============================================================
   BOT MESSAGE
============================================================ */

.ejr-bot-msg {

    align-self:flex-start;

    max-width:85%;

    background:#fff;

    color:#263238;

    padding:10px 12px;

    border-radius:
        14px
        14px
        14px
        4px;

    font-size:13px;

    line-height:1.45;

    box-shadow:
        0 1px 3px
        rgba(0,0,0,.08);

}


/* ============================================================
   USER MESSAGE
============================================================ */

.ejr-user-msg {

    align-self:flex-end;

    max-width:85%;

    background:#e62429;

    color:#fff;

    padding:10px 12px;

    border-radius:
        14px
        14px
        4px
        14px;

    font-size:13px;

    line-height:1.45;

    box-shadow:
        0 1px 3px
        rgba(0,0,0,.08);

}


/* ============================================================
   ADMIN MESSAGE
============================================================ */

.ejr-admin-msg {

    align-self:flex-start;

    max-width:85%;

    background:#fff;

    color:#263238;

    padding:10px 12px;

    border-radius:
        14px
        14px
        14px
        4px;

    font-size:13px;

    line-height:1.45;

    box-shadow:
        0 1px 3px
        rgba(0,0,0,.08);

}


/* ============================================================
   ADMIN CUSTOMER ITEM
============================================================ */

.admin-chat-customer-item {

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:8px;

    padding:10px 12px;

    border-bottom:1px solid #eee;

    cursor:pointer;

    background:#fff;

    transition:
        background .2s ease;

}


.admin-chat-customer-item:hover {

    background:#f8f9fa;

}


.admin-chat-customer-item.active {

    background:#fff1f1;

    border-left:
        3px solid #e62429;

}


/* ============================================================
   ADMIN CUSTOMER NAME
============================================================ */

.admin-chat-customer-name {

    font-weight:700;

    font-size:12px;

    color:#263238;

}


/* ============================================================
   ADMIN CUSTOMER STATUS
============================================================ */

.admin-chat-customer-status {

    font-size:10px;

    color:#777;

}


/* ============================================================
   UNREAD BADGE
============================================================ */

.admin-chat-unread {

    min-width:19px;

    height:19px;

    padding:0 5px;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    background:#e62429;

    color:#fff;

    font-size:9px;

    font-weight:700;

}


/* ============================================================
   FULL SCREEN BUTTON
============================================================ */

#chatFullscreenButton {

    flex-shrink:0;

    transition:
        background .2s ease,
        transform .2s ease;

}


#chatFullscreenButton:hover {

    background:
        rgba(
            255,
            255,
            255,
            .12
        ) !important;

    transform:
        scale(1.05);

}


#chatFullscreenButton i {

    font-size:15px;

}


/* ============================================================
   FULL SCREEN CHAT
============================================================ */

#chatBox.chat-fullscreen {

    position:fixed !important;

    top:0 !important;

    right:0 !important;

    bottom:0 !important;

    left:0 !important;

    width:100vw !important;

    height:100vh !important;

    max-width:none !important;

    max-height:none !important;

    margin:0 !important;

    border-radius:0 !important;

    z-index:99999999 !important;

    display:flex !important;

    flex-direction:column !important;

}


/* ============================================================
   FULL SCREEN CUSTOMER BODY
============================================================ */

#chatBox.chat-fullscreen #chatBody {

    flex:
        1 1 auto !important;

    height:auto !important;

    max-height:none !important;

    overflow-y:auto !important;

}


/* ============================================================
   FULL SCREEN ADMIN PANEL
============================================================ */

#chatBox.chat-fullscreen #adminChatPanel {

    flex:
        1 1 auto !important;

    height:auto !important;

    min-height:0 !important;

}


/* ============================================================
   ADMIN CUSTOMER LIST
============================================================ */

#chatBox.chat-fullscreen
#adminChatCustomerList {

    max-height:180px !important;

    overflow-y:auto !important;

}


/* ============================================================
   ADMIN MESSAGES
============================================================ */

#chatBox.chat-fullscreen
#adminChatMessages {

    flex:
        1 1 auto !important;

    height:auto !important;

    max-height:none !important;

    overflow-y:auto !important;

}


/* ============================================================
   INPUT AREAS
============================================================ */

#chatBox.chat-fullscreen
#customerChatInputContainer,

#chatBox.chat-fullscreen
#adminChatReplyArea {

    flex-shrink:0;

}


/* ============================================================
   BODY SCROLL LOCK
============================================================ */

body.ejr-chat-fullscreen-active {

    overflow:hidden !important;

}


/* ============================================================
   MAP
============================================================ */

.ejr-map-container {

    width:100%;

    height:220px;

    overflow:hidden;

    border-radius:12px;

}


.ejr-map-container iframe {

    width:100%;

    height:100%;

    border:0;

}


/* ============================================================
   FULL SCREEN MAP
============================================================ */

#chatBox.chat-fullscreen
.ejr-map-container {

    height:400px !important;

}


#chatBox.chat-fullscreen
.ejr-map-container iframe {

    height:400px !important;

}


/* ============================================================
   MOBILE
============================================================ */

@media (
    max-width:576px
) {

    #ejrChatContainer {

        right:12px !important;

        bottom:12px !important;

    }


    #chatBox {

        width:
            calc(
                100vw - 24px
            ) !important;

        right:0;

    }


    #chatBox.chat-fullscreen {

        width:100vw !important;

        height:100dvh !important;

        min-height:100dvh !important;

        right:0 !important;

        bottom:0 !important;

    }


    #chatBox.chat-fullscreen
    #adminChatCustomerList {

        max-height:130px !important;

    }


    #chatBox.chat-fullscreen
    .ejr-map-container {

        height:300px !important;

    }


    #chatBox.chat-fullscreen
    .ejr-map-container iframe {

        height:300px !important;

    }

}

</style>


<!-- ============================================================
     CHAT JAVASCRIPT STARTS IN PART 3
============================================================ -->

<script>

    /* ============================================================
   EJR GARAGE CHAT SYSTEM
============================================================ */


/* ============================================================
   GLOBAL CHAT VARIABLES
============================================================ */

let chatPollingInterval = null;

let adminChatPollingInterval = null;

let selectedAdminCustomer = null;

let currentChatSession = null;

let lastCustomerMessageCount = 0;

let lastAdminMessageCount = 0;


/* ============================================================
   CHECK WHETHER CURRENT USER IS ADMIN
============================================================ */

function isEJRAdmin() {

    try {

        const token =
            localStorage.getItem(
                'jwt_token'
            );

        if (!token) {

            return false;

        }


        const payload =
            JSON.parse(
                atob(
                    token
                        .split('.')[1]
                        .replace(/-/g, '+')
                        .replace(/_/g, '/')
                )
            );


        /*
         * Admin role:
         *
         * role_id = 2
         * OR
         * user id = 21
         */

        if (
            Number(payload.role_id) === 2 ||
            Number(payload.user_id) === 21 ||
            Number(payload.id) === 21
        ) {

            return true;

        }

    }

    catch (error) {

        console.warn(
            'EJR CHAT: Unable to determine admin status.',
            error
        );

    }


    return false;

}


/* ============================================================
   GET JWT TOKEN
============================================================ */

function getChatToken() {

    return localStorage.getItem(
        'jwt_token'
    );

}


/* ============================================================
   OPEN / CLOSE CHAT
============================================================ */

function toggleChat() {

    const box =
        document.getElementById(
            'chatBox'
        );


    if (!box) {

        console.error(
            'EJR CHAT ERROR: #chatBox not found.'
        );

        return;

    }


    const isOpen =
        box.style.display === 'flex';


    if (isOpen) {

        box.style.display =
            'none';

        return;

    }


    box.style.display =
        'flex';


    /*
     * ADMIN CHAT
     */

    if (isEJRAdmin()) {

        initializeAdminChatWidget();

        return;

    }


    /*
     * CUSTOMER CHAT
     */

    loadMessages();

    const notification =
        document.getElementById(
            'chatNotification'
        );


    if (notification) {

        notification.style.display =
            'none';

    }

}


/* ============================================================
   INITIALIZE CHAT
============================================================ */

function initializeChatWidget() {

    const box =
        document.getElementById(
            'chatBox'
        );


    if (!box) {

        return;

    }


    /*
     * ADMIN
     */

    if (isEJRAdmin()) {

        return;

    }


    /*
     * CUSTOMER
     */

    loadMessages();

}


/* ============================================================
   APPEND CHAT MESSAGE
============================================================ */

function appendMessage(
    text,
    sender = 'bot'
) {

    const body =
        document.getElementById(
            'chatBody'
        );


    if (!body) {

        return;

    }


    const message =
        document.createElement(
            'div'
        );


    /*
     * Determine message class
     */

    if (sender === 'user') {

        message.className =
            'ejr-user-msg';

    }

    else if (sender === 'admin') {

        message.className =
            'ejr-admin-msg';

    }

    else {

        message.className =
            'ejr-bot-msg';

    }


    /*
     * Convert basic new lines
     */

    const safeText =
        String(text || '')
            .replace(
                /\n/g,
                '<br>'
            );


    message.innerHTML =
        safeText;


    body.appendChild(
        message
    );


    body.scrollTop =
        body.scrollHeight;


    return message;

}


/* ============================================================
   CLEAR CHAT BODY
============================================================ */

function clearChatBody() {

    const body =
        document.getElementById(
            'chatBody'
        );


    if (!body) {

        return;

    }


    body.innerHTML = '';

}


/* ============================================================
   SHOW TYPING INDICATOR
============================================================ */

function showTypingIndicator() {

    const indicator =
        document.getElementById(
            'typingIndicator'
        );


    if (indicator) {

        indicator.style.display =
            'block';

    }

}


/* ============================================================
   HIDE TYPING INDICATOR
============================================================ */

function hideTypingIndicator() {

    const indicator =
        document.getElementById(
            'typingIndicator'
        );


    if (indicator) {

        indicator.style.display =
            'none';

    }

}


/* ============================================================
   SHOW LIVE CHAT UI
============================================================ */

function showLiveChatUI() {

    const options =
        document.getElementById(
            'chatOptions'
        );


    const talkAgent =
        document.getElementById(
            'talkAgentArea'
        );


    const liveIndicator =
        document.getElementById(
            'liveIndicator'
        );


    const waitingIndicator =
        document.getElementById(
            'waitingIndicator'
        );


    const inputContainer =
        document.getElementById(
            'customerChatInputContainer'
        );


    if (options) {

        options.style.display =
            'none';

    }


    if (talkAgent) {

        talkAgent.style.display =
            'none';

    }


    if (waitingIndicator) {

        waitingIndicator.style.display =
            'none';

    }


    if (liveIndicator) {

        liveIndicator.style.display =
            'block';

    }


    if (inputContainer) {

        inputContainer.style.display =
            'block';

    }


    const status =
        document.getElementById(
            'chatStatusLabel'
        );


    if (status) {

        status.textContent =
            'Live Chat • Connected to Admin';

    }

}


/* ============================================================
   SHOW WAITING UI
============================================================ */

function showWaitingUI() {

    const options =
        document.getElementById(
            'chatOptions'
        );


    const talkAgent =
        document.getElementById(
            'talkAgentArea'
        );


    const waitingIndicator =
        document.getElementById(
            'waitingIndicator'
        );


    const liveIndicator =
        document.getElementById(
            'liveIndicator'
        );


    const inputContainer =
        document.getElementById(
            'customerChatInputContainer'
        );


    if (options) {

        options.style.display =
            'none';

    }


    if (talkAgent) {

        talkAgent.style.display =
            'none';

    }


    if (waitingIndicator) {

        waitingIndicator.style.display =
            'block';

    }


    if (liveIndicator) {

        liveIndicator.style.display =
            'none';

    }


    if (inputContainer) {

        inputContainer.style.display =
            'block';

    }


    const status =
        document.getElementById(
            'chatStatusLabel'
        );


    if (status) {

        status.textContent =
            'Live Chat • Waiting for Admin';

    }

}


/* ============================================================
   SHOW BOT UI
============================================================ */

function showBotUI() {

    const options =
        document.getElementById(
            'chatOptions'
        );


    const talkAgent =
        document.getElementById(
            'talkAgentArea'
        );


    const waitingIndicator =
        document.getElementById(
            'waitingIndicator'
        );


    const liveIndicator =
        document.getElementById(
            'liveIndicator'
        );


    const inputContainer =
        document.getElementById(
            'customerChatInputContainer'
        );


    if (options) {

        options.style.display =
            'flex';

    }


    if (talkAgent) {

        talkAgent.style.display =
            'block';

    }


    if (waitingIndicator) {

        waitingIndicator.style.display =
            'none';

    }


    if (liveIndicator) {

        liveIndicator.style.display =
            'none';

    }


    if (inputContainer) {

        inputContainer.style.display =
            'none';

    }


    const status =
        document.getElementById(
            'chatStatusLabel'
        );


    if (status) {

        status.textContent =
            'Bot Mode • Always here to help';

    }

}


/* ============================================================
   QUICK QUESTIONS
============================================================ */

function askQuickQuestion(question) {

    /*
     * Admin does not use customer quick actions.
     */

    if (isEJRAdmin()) {

        return;

    }


    /*
     * Require login.
     */

    const token =
        getChatToken();


    if (!token) {

        window.location.href =
            '/login.php';

        return;

    }


    /*
     * LOCATION
     *
     * This is handled separately so that
     * the Google Maps view appears.
     */

    if (
        String(question)
            .toLowerCase()
            .trim() ===
        'location'
    ) {

        showEJRLocation();

        return;

    }


    /*
     * Normal chatbot question.
     */

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
   GOOGLE MAPS LOCATION
============================================================ */

function showEJRLocation() {

    const body =
        document.getElementById(
            'chatBody'
        );


    if (!body) {

        return;

    }


    /*
     * Show customer question.
     */

    appendMessage(
        'Location',
        'user'
    );


    /*
     * Create bot response.
     */

    const message =
        document.createElement(
            'div'
        );


    message.className =
        'ejr-bot-msg';


    message.innerHTML = `

        <div
            style="
                font-weight:700;
                margin-bottom:8px;
            "
        >

            <i
                class="bi bi-geo-alt-fill text-danger me-1"
            ></i>

            EJR Garage Location

        </div>


        <div
            style="
                margin-bottom:10px;
            "
        >

            Mabalacat City, Pampanga

        </div>


        <div
            class="ejr-map-container"
            style="
                width:100%;
                height:220px;
                overflow:hidden;
                border-radius:12px;
                margin-bottom:10px;
                border:1px solid #ddd;
            "
        >

            <iframe

                src="https://www.google.com/maps?q=EJR+Garage,+Mabalacat+City,+Pampanga&output=embed"

                width="100%"

                height="100%"

                style="
                    border:0;
                "

                loading="lazy"

                allowfullscreen

                referrerpolicy="
                    no-referrer-when-downgrade
                "

            ></iframe>

        </div>


        <a

            href="https://www.google.com/maps/dir//EJR+Garage,+6HJH%2BR9R,+Mabalacat+City,+Pampanga/@15.2638421,120.5604382,15z/data=!4m8!4m7!1m0!1m5!1m1!1s0x3396ed92e49fc18b:0x1371f631ba9a7f8e!2m2!1d120.5783981!2d15.2321026?entry=ttu&g_ep=EgoyMDI2MDgxMi4wIKXMDSoASAFQAw%3D%3D"

            target="_blank"

            rel="noopener noreferrer"

            style="
                display:flex;
                align-items:center;
                justify-content:center;
                gap:7px;
                width:100%;
                padding:9px 12px;
                background:#e62429;
                color:#fff;
                text-decoration:none;
                border-radius:9px;
                font-size:12px;
                font-weight:700;
            "

        >

            <i
                class="bi bi-map-fill"
            ></i>

            Open in Google Maps

        </a>

    `;


    body.appendChild(
        message
    );


    body.scrollTop =
        body.scrollHeight;

}


/* ============================================================
   UPDATE CHAT STATUS
============================================================ */

function updateChatStatus(
    status
) {

    const label =
        document.getElementById(
            'chatStatusLabel'
        );


    if (!label) {

        return;

    }


    if (status === 'live') {

        label.textContent =
            'Live Chat • Connected to Admin';

    }

    else if (status === 'waiting') {

        label.textContent =
            'Live Chat • Waiting for Admin';

    }

    else {

        label.textContent =
            'Bot Mode • Always here to help';

    }

}


/* ============================================================
   INITIALIZE WHEN PAGE LOADS
============================================================ */

document.addEventListener(
    'DOMContentLoaded',
    function() {

        initializeChatWidget();

    }
);

/* ============================================================
   LOAD CUSTOMER CHAT / SESSION
============================================================ */

async function loadMessages() {

    const token =
        getChatToken();


    /*
     * User is not logged in.
     */

    if (!token) {

        showBotUI();

        return;

    }


    try {

        const response =
            await fetch(
                '/api/check_session.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    },

                    body:JSON.stringify({
                        action:'check'
                    })
                }
            );


        if (!response.ok) {

            throw new Error(
                'Session request failed: ' +
                response.status
            );

        }


        const data =
            await response.json();


        /*
         * ====================================================
         * NO ACTIVE SESSION
         * ====================================================
         */

        if (
            !data ||
            !data.active
        ) {

            currentChatSession =
                null;

            showBotUI();

            return;

        }


        /*
         * ====================================================
         * SAVE SESSION
         * ====================================================
         */

        currentChatSession =
            data;


        /*
         * ====================================================
         * DETERMINE SESSION STATUS
         * ====================================================
         */

        const sessionStatus =
            String(
                data.status ||
                data.session_status ||
                ''
            ).toLowerCase();


        if (
            sessionStatus ===
            'waiting'
        ) {

            showWaitingUI();

        }

        else if (
            sessionStatus ===
            'live' ||
            sessionStatus ===
            'active' ||
            data.connected === true
        ) {

            showLiveChatUI();

        }

        else {

            showBotUI();

        }


        /*
         * ====================================================
         * LOAD MESSAGES
         * ====================================================
         */

        if (
            Array.isArray(
                data.messages
            )
        ) {

            renderCustomerMessages(
                data.messages
            );

        }

    }

    catch (error) {

        console.error(
            'EJR CHAT: Unable to load session.',
            error
        );

    }

}


/* ============================================================
   RENDER CUSTOMER MESSAGES
============================================================ */

function renderCustomerMessages(
    messages
) {

    const body =
        document.getElementById(
            'chatBody'
        );


    if (!body) {

        return;

    }


    if (
        !Array.isArray(messages)
    ) {

        return;

    }


    /*
     * Do not erase the chat if the
     * API did not return messages.
     */

    if (
        messages.length === 0
    ) {

        return;

    }


    /*
     * Avoid unnecessary redraws.
     */

    const messageCount =
        messages.length;


    if (
        messageCount ===
        lastCustomerMessageCount
    ) {

        return;

    }


    lastCustomerMessageCount =
        messageCount;


    body.innerHTML = '';


    messages.forEach(
        function(message) {

            if (!message) {

                return;

            }


            /*
             * Try several common field names
             * because the API may return
             * different names.
             */

            const text =
                message.message ??
                message.text ??
                message.content ??
                '';


            if (
                String(text).trim() ===
                ''
            ) {

                return;

            }


            const sender =
                String(
                    message.sender ||
                    message.sender_type ||
                    message.role ||
                    ''
                ).toLowerCase();


            let messageType =
                'bot';


            if (
                sender === 'user' ||
                sender === 'customer' ||
                sender === 'client'
            ) {

                messageType =
                    'user';

            }

            else if (
                sender === 'admin' ||
                sender === 'agent'
            ) {

                messageType =
                    'admin';

            }


            appendMessage(
                text,
                messageType
            );

        }
    );


    body.scrollTop =
        body.scrollHeight;

}


/* ============================================================
   POLL CUSTOMER CHAT
============================================================ */

async function pollCustomerChat() {

    const token =
        getChatToken();


    /*
     * No token means there is
     * nothing to poll.
     */

    if (!token) {

        return;

    }


    /*
     * Do not poll while admin.
     */

    if (isEJRAdmin()) {

        return;

    }


    try {

        const response =
            await fetch(
                '/api/check_session.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    },

                    body:JSON.stringify({
                        action:'check'
                    })
                }
            );


        if (!response.ok) {

            return;

        }


        const data =
            await response.json();


        /*
         * ====================================================
         * SESSION CLOSED
         * ====================================================
         */

        if (
            !data ||
            !data.active
        ) {

            if (
                currentChatSession !==
                null
            ) {

                currentChatSession =
                    null;

                showBotUI();

            }

            return;

        }


        /*
         * ====================================================
         * SAVE SESSION
         * ====================================================
         */

        currentChatSession =
            data;


        /*
         * ====================================================
         * STATUS
         * ====================================================
         */

        const status =
            String(
                data.status ||
                data.session_status ||
                ''
            ).toLowerCase();


        if (
            status === 'waiting'
        ) {

            showWaitingUI();

        }

        else if (
            status === 'live' ||
            status === 'active' ||
            data.connected === true
        ) {

            showLiveChatUI();

        }


        /*
         * ====================================================
         * MESSAGES
         * ====================================================
         */

        if (
            Array.isArray(
                data.messages
            )
        ) {

            const oldCount =
                lastCustomerMessageCount;


            renderCustomerMessages(
                data.messages
            );


            const newCount =
                data.messages.length;


            /*
             * Notification when a new
             * message arrives.
             */

            if (
                newCount >
                oldCount
            ) {

                /*
                 * If the chat is closed,
                 * show notification.
                 */

                const box =
                    document.getElementById(
                        'chatBox'
                    );


                const isClosed =
                    !box ||
                    box.style.display !==
                    'flex';


                if (isClosed) {

                    showChatNotification(
                        newCount -
                        oldCount
                    );

                }

            }

        }

    }

    catch (error) {

        console.warn(
            'EJR CHAT POLLING ERROR:',
            error
        );

    }

}


/* ============================================================
   START CUSTOMER CHAT POLLING
============================================================ */

function startCustomerChatPolling() {

    /*
     * Clear existing interval first.
     */

    if (
        chatPollingInterval
    ) {

        clearInterval(
            chatPollingInterval
        );

    }


    /*
     * Initial check.
     */

    pollCustomerChat();


    /*
     * Poll every 4 seconds.
     */

    chatPollingInterval =
        setInterval(
            function() {

                pollCustomerChat();

            },
            4000
        );

}


/* ============================================================
   STOP CUSTOMER CHAT POLLING
============================================================ */

function stopCustomerChatPolling() {

    if (
        chatPollingInterval
    ) {

        clearInterval(
            chatPollingInterval
        );

        chatPollingInterval =
            null;

    }

}


/* ============================================================
   CHAT NOTIFICATION
============================================================ */

function showChatNotification(
    count = 1
) {

    const notification =
        document.getElementById(
            'chatNotification'
        );


    if (!notification) {

        return;

    }


    notification.textContent =
        count > 9
            ? '9+'
            : String(count);


    notification.style.display =
        'flex';

}


/* ============================================================
   HIDE CHAT NOTIFICATION
============================================================ */

function hideChatNotification() {

    const notification =
        document.getElementById(
            'chatNotification'
        );


    if (!notification) {

        return;

    }


    notification.style.display =
        'none';

}


/* ============================================================
   REQUEST LIVE CHAT
============================================================ */

async function requestLiveChat() {

    const token =
        getChatToken();


    /*
     * ========================================================
     * LOGIN CHECK
     * ========================================================
     */

    if (!token) {

        window.location.href =
            '/login.php';

        return;

    }


    /*
     * ========================================================
     * PREVENT ADMIN
     * ========================================================
     */

    if (isEJRAdmin()) {

        return;

    }


    const button =
        document.getElementById(
            'talkAgentButton'
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


    try {

        const response =
            await fetch(
                '/api/check_session.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    },

                    body:JSON.stringify({
                        action:'request_live'
                    })
                }
            );


        const data =
            await response.json();


        /*
         * ====================================================
         * SUCCESS
         * ====================================================
         */

        if (
            data &&
            (
                data.success === true ||
                data.status === 'waiting' ||
                data.status === 'live'
            )
        ) {

            currentChatSession =
                data;


            /*
             * Determine whether admin
             * connected immediately.
             */

            const status =
                String(
                    data.status ||
                    data.session_status ||
                    ''
                ).toLowerCase();


            if (
                status === 'live' ||
                status === 'active' ||
                data.connected === true
            ) {

                showLiveChatUI();

            }

            else {

                showWaitingUI();

            }


            /*
             * Reset message counter
             * so polling can render
             * the current conversation.
             */

            lastCustomerMessageCount =
                0;


            pollCustomerChat();


            return;

        }


        /*
         * ====================================================
         * REQUEST FAILED
         * ====================================================
         */

        throw new Error(
            data.message ||
            data.error ||
            'Unable to request live chat.'
        );

    }

    catch (error) {

        console.error(
            'EJR LIVE CHAT REQUEST ERROR:',
            error
        );


        Swal.fire({
            icon:'error',
            title:'Live Chat Unavailable',
            text:
                error.message ||
                'Unable to connect to live chat.',
            confirmButtonColor:
                '#e62429'
        });


        /*
         * Restore button.
         */

        if (button) {

            button.disabled =
                false;

            button.innerHTML = `

                <i
                    class="bi bi-person-headset me-1"
                ></i>

                Talk to a Live Agent

            `;

        }

    }

}


/* ============================================================
   CANCEL LIVE REQUEST
============================================================ */

async function cancelLiveRequest() {

    const token =
        getChatToken();


    if (!token) {

        return;

    }


    try {

        await fetch(
            '/api/check_session.php',
            {
                method:'POST',

                headers:{
                    'Content-Type':
                        'application/json',

                    'Authorization':
                        'Bearer ' + token
                },

                body:JSON.stringify({
                    action:'cancel_live'
                })
            }
        );

    }

    catch (error) {

        console.warn(
            'EJR CHAT: Cancel live request failed.',
            error
        );

    }


    currentChatSession =
        null;


    lastCustomerMessageCount =
        0;


    showBotUI();

}


/* ============================================================
   END LIVE SESSION
============================================================ */

async function endLiveSession() {

    const token =
        getChatToken();


    if (!token) {

        return;

    }


    const result =
        await Swal.fire({

            icon:'warning',

            title:'End Live Chat?',

            text:
                'Are you sure you want to end this live chat session?',

            showCancelButton:true,

            confirmButtonText:
                'Yes, End Chat',

            cancelButtonText:
                'Keep Chatting',

            confirmButtonColor:
                '#e62429'

        });


    if (
        !result.isConfirmed
    ) {

        return;

    }


    try {

        const response =
            await fetch(
                '/api/check_session.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    },

                    body:JSON.stringify({
                        action:'end_live'
                    })
                }
            );


        const data =
            await response.json();


        if (
            !response.ok
        ) {

            throw new Error(
                data.message ||
                'Unable to end chat.'
            );

        }


        /*
         * Reset customer state.
         */

        currentChatSession =
            null;


        lastCustomerMessageCount =
            0;


        /*
         * Return to bot mode.
         */

        showBotUI();


        /*
         * Clear input.
         */

        const input =
            document.getElementById(
                'chatInput'
            );


        if (input) {

            input.value =
                '';

        }


        /*
         * Add system message.
         */

        appendMessage(
            'Your live chat session has ended. How else can I help you?',
            'bot'
        );

    }

    catch (error) {

        console.error(
            'EJR CHAT: End session failed.',
            error
        );


        Swal.fire({
            icon:'error',
            title:'Unable to End Chat',
            text:
                error.message ||
                'Please try again.',
            confirmButtonColor:
                '#e62429'
        });

    }

}


/* ============================================================
   ALIAS
   ------------------------------------------------------------
   Some older code may call endLiveSessionRequest().
============================================================ */

function endLiveSessionRequest() {

    return endLiveSession();

}


/* ============================================================
   START CUSTOMER POLLING
============================================================ */

startCustomerChatPolling();


/* ============================================================
   ENTER KEY FOR CUSTOMER CHAT
============================================================ */

document.addEventListener(
    'keydown',
    function(event) {

        const input =
            document.getElementById(
                'chatInput'
            );


        if (
            !input ||
            document.activeElement !==
            input
        ) {

            return;

        }


        if (
            event.key ===
            'Enter' &&
            !event.shiftKey
        ) {

            event.preventDefault();

            sendChatMessage();

        }

    }
);

/* ============================================================
   SEND CUSTOMER CHAT MESSAGE
============================================================ */

async function sendChatMessage() {

    const input =
        document.getElementById(
            'chatInput'
        );


    const token =
        getChatToken();


    /*
     * ========================================================
     * BASIC VALIDATION
     * ========================================================
     */

    if (!input) {

        console.error(
            'EJR CHAT: chatInput not found.'
        );

        return;

    }


    if (!token) {

        window.location.href =
            '/login.php';

        return;

    }


    const message =
        input.value.trim();


    if (!message) {

        return;

    }


    /*
     * ========================================================
     * PREVENT ADMIN FROM USING CUSTOMER SEND
     * ========================================================
     */

    if (isEJRAdmin()) {

        return;

    }


    /*
     * ========================================================
     * GET BUTTON
     * ========================================================
     */

    const sendButton =
        document.getElementById(
            'chatSendButton'
        );


    /*
     * ========================================================
     * CHECK CURRENT SESSION
     * ========================================================
     */

    const session =
        currentChatSession;


    /*
     * If there is no active live session,
     * the message should still be sent to
     * the chatbot endpoint.
     */

    try {

        /*
         * Disable send button while sending.
         */

        if (sendButton) {

            sendButton.disabled =
                true;

        }


        /*
         * ====================================================
         * SHOW CUSTOMER MESSAGE IMMEDIATELY
         * ====================================================
         */

        appendMessage(
            message,
            'user'
        );


        /*
         * Clear input immediately.
         */

        input.value =
            '';


        /*
         * ====================================================
         * LIVE CHAT MESSAGE
         * ====================================================
         *
         * When the user is connected to an
         * admin, send the message as a
         * live chat message.
         *
         * Otherwise use the chatbot.
         *
         */

        const isLive =
            session &&
            (
                String(
                    session.status ||
                    session.session_status ||
                    ''
                ).toLowerCase() ===
                'live' ||

                String(
                    session.status ||
                    session.session_status ||
                    ''
                ).toLowerCase() ===
                'active' ||

                session.connected === true
            );


        if (isLive) {

            /*
             * =================================================
             * SEND TO LIVE CHAT
             * =================================================
             */

            const response =
                await fetch(
                    '/api/check_session.php',
                    {
                        method:'POST',

                        headers:{
                            'Content-Type':
                                'application/json',

                            'Authorization':
                                'Bearer ' + token
                        },

                        body:JSON.stringify({

                            action:
                                'chat',

                            message:
                                message

                        })
                    }
                );


            if (!response.ok) {

                throw new Error(
                    'Unable to send live chat message.'
                );

            }


            const data =
                await response.json();


            if (
                data &&
                data.success === false
            ) {

                throw new Error(
                    data.message ||
                    'Unable to send message.'
                );

            }


            /*
             * Poll immediately so the
             * admin response appears quickly.
             */

            setTimeout(
                function() {

                    pollCustomerChat();

                },
                300
            );


            return;

        }


        /*
         * ====================================================
         * BOT MESSAGE
         * ====================================================
         */

        showTypingIndicator();


        const botResponse =
            await fetch(
                '/api/check_session.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    },

                    body:JSON.stringify({

                        action:
                            'chat',

                        message:
                            message

                    })
                }
            );


        if (!botResponse.ok) {

            throw new Error(
                'Unable to send message.'
            );

        }


        const botData =
            await botResponse.json();


        hideTypingIndicator();


        /*
         * ====================================================
         * DISPLAY BOT RESPONSE
         * ====================================================
         */

        if (
            botData &&
            botData.reply
        ) {

            appendMessage(
                botData.reply,
                'bot'
            );

        }

        else if (
            botData &&
            botData.message &&
            botData.success !== false
        ) {

            appendMessage(
                botData.message,
                'bot'
            );

        }

        else if (
            botData &&
            botData.response
        ) {

            appendMessage(
                botData.response,
                'bot'
            );

        }

        else {

            /*
             * If the API does not provide
             * a bot response, don't show
             * an error as a chat message.
             *
             * Polling may provide the response.
             */

            if (
                botData &&
                botData.success === false
            ) {

                throw new Error(
                    botData.message ||
                    'Unable to process your message.'
                );

            }

        }

    }

    catch (error) {

        console.error(
            'EJR CHAT SEND ERROR:',
            error
        );


        hideTypingIndicator();


        /*
         * Restore the user's message
         * to the input so it isn't lost.
         */

        if (
            input.value.trim() === ''
        ) {

            input.value =
                message;

        }


        Swal.fire({

            icon:'error',

            title:'Message Not Sent',

            text:
                error.message ||
                'Please try again.',

            confirmButtonColor:
                '#e62429'

        });

    }

    finally {

        /*
         * Always re-enable send button.
         */

        if (sendButton) {

            sendButton.disabled =
                false;

        }

    }

}


/* ============================================================
   LIVE CHAT CONNECTION CHECK
============================================================ */

async function checkLiveChatStatus() {

    const token =
        getChatToken();


    if (!token) {

        return null;

    }


    try {

        const response =
            await fetch(
                '/api/check_session.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    },

                    body:JSON.stringify({
                        action:'check'
                    })
                }
            );


        if (!response.ok) {

            return null;

        }


        return await response.json();

    }

    catch (error) {

        console.warn(
            'EJR CHAT STATUS ERROR:',
            error
        );

        return null;

    }

}


/* ============================================================
   REFRESH CUSTOMER SESSION
============================================================ */

async function refreshCustomerSession() {

    const data =
        await checkLiveChatStatus();


    if (!data) {

        return;

    }


    if (
        !data.active
    ) {

        currentChatSession =
            null;

        showBotUI();

        return;

    }


    currentChatSession =
        data;


    const status =
        String(
            data.status ||
            data.session_status ||
            ''
        ).toLowerCase();


    if (
        status === 'live' ||
        status === 'active' ||
        data.connected === true
    ) {

        showLiveChatUI();

    }

    else if (
        status === 'waiting'
    ) {

        showWaitingUI();

    }

}


/* ============================================================
   CUSTOMER CHAT INPUT EVENTS
============================================================ */

document.addEventListener(
    'DOMContentLoaded',
    function() {

        const input =
            document.getElementById(
                'chatInput'
            );


        if (!input) {

            return;

        }


        /*
         * Enter = Send
         */

        input.addEventListener(
            'keydown',
            function(event) {

                if (
                    event.key ===
                    'Enter' &&
                    !event.shiftKey
                ) {

                    event.preventDefault();

                    sendChatMessage();

                }

            }
        );


        /*
         * Typing indicator.
         *
         * This only changes the
         * local UI and does not alter
         * the existing API.
         */

        input.addEventListener(
            'input',
            function() {

                /*
                 * Keep this intentionally
                 * lightweight.
                 */

            }
        );

    }
);


/* ============================================================
   CUSTOMER POLLING STARTUP
============================================================ */

document.addEventListener(
    'DOMContentLoaded',
    function() {

        /*
         * Avoid duplicate polling
         * initialization.
         */

        if (
            !isEJRAdmin()
        ) {

            startCustomerChatPolling();

        }

    }
);

/* ============================================================
   ADMIN LIVE CHAT
============================================================ */


/* ============================================================
   INITIALIZE ADMIN CHAT WIDGET
============================================================ */

function initializeAdminChatWidget() {

    if (!isEJRAdmin()) {

        return;

    }


    const customerPanel =
        document.getElementById(
            'adminChatPanel'
        );


    const customerBody =
        document.getElementById(
            'chatBody'
        );


    const options =
        document.getElementById(
            'chatOptions'
        );


    const talkAgent =
        document.getElementById(
            'talkAgentArea'
        );


    const customerInput =
        document.getElementById(
            'customerChatInputContainer'
        );


    /*
     * Hide customer chatbot UI.
     */

    if (customerBody) {

        customerBody.style.display =
            'none';

    }


    if (options) {

        options.style.display =
            'none';

    }


    if (talkAgent) {

        talkAgent.style.display =
            'none';

    }


    if (customerInput) {

        customerInput.style.display =
            'none';

    }


    /*
     * Show admin panel.
     */

    if (customerPanel) {

        customerPanel.style.display =
            'flex';

    }


    /*
     * Change title.
     */

    const title =
        document.getElementById(
            'chatTitle'
        );


    if (title) {

        title.textContent =
            'EJR Live Chat';

    }


    const status =
        document.getElementById(
            'chatStatusLabel'
        );


    if (status) {

        status.textContent =
            'Admin • Customer Messages';

    }


    /*
     * Load customers.
     */

    loadAdminCustomers();


    /*
     * Start admin polling.
     */

    startAdminChatPolling();

}


/* ============================================================
   LOAD ADMIN CUSTOMER LIST
============================================================ */

async function loadAdminCustomers() {

    if (!isEJRAdmin()) {

        return;

    }


    const token =
        getChatToken();


    if (!token) {

        return;

    }


    const list =
        document.getElementById(
            'adminChatCustomerList'
        );


    if (!list) {

        return;

    }


    try {

        /*
         * Use the existing chat session
         * endpoint.
         */

        const response =
            await fetch(
                '/api/check_session.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    },

                    body:JSON.stringify({
                        action:'admin_sessions'
                    })
                }
            );


        if (!response.ok) {

            throw new Error(
                'Unable to load customers.'
            );

        }


        const data =
            await response.json();


        /*
         * Accept several possible response
         * formats so the existing backend
         * remains compatible.
         */

        let customers = [];


        if (
            Array.isArray(
                data.customers
            )
        ) {

            customers =
                data.customers;

        }

        else if (
            Array.isArray(
                data.sessions
            )
        ) {

            customers =
                data.sessions;

        }

        else if (
            Array.isArray(
                data.users
            )
        ) {

            customers =
                data.users;

        }

        else if (
            Array.isArray(data)
        ) {

            customers =
                data;

        }


        renderAdminCustomerList(
            customers
        );

    }

    catch (error) {

        console.error(
            'EJR ADMIN CHAT:',
            error
        );


        list.innerHTML = `

            <div
                style="
                    padding:20px;
                    text-align:center;
                    color:#888;
                    font-size:12px;
                "
            >

                <i
                    class="bi bi-chat-left-text"
                    style="
                        font-size:25px;
                        display:block;
                        margin-bottom:7px;
                    "
                ></i>

                No customer conversations
                available.

            </div>

        `;

    }

}


/* ============================================================
   RENDER ADMIN CUSTOMER LIST
============================================================ */

function renderAdminCustomerList(
    customers
) {

    const list =
        document.getElementById(
            'adminChatCustomerList'
        );


    if (!list) {

        return;

    }


    list.innerHTML = '';


    if (
        !Array.isArray(customers) ||
        customers.length === 0
    ) {

        list.innerHTML = `

            <div
                style="
                    padding:20px;
                    text-align:center;
                    color:#888;
                    font-size:12px;
                "
            >

                <i
                    class="bi bi-chat-left-text"
                    style="
                        font-size:25px;
                        display:block;
                        margin-bottom:7px;
                    "
                ></i>

                No active customer chats.

            </div>

        `;

        return;

    }


    customers.forEach(
        function(customer) {

            if (!customer) {

                return;

            }


            /*
             * Determine customer ID.
             */

            const id =
                customer.id ??
                customer.user_id ??
                customer.customer_id ??
                customer.session_id;


            if (
                id === undefined ||
                id === null
            ) {

                return;

            }


            /*
             * Customer display name.
             */

            const name =
                customer.name ??
                customer.full_name ??
                customer.username ??
                customer.email ??
                'Customer';


            /*
             * Status.
             */

            const status =
                String(
                    customer.status ||
                    customer.session_status ||
                    'waiting'
                ).toLowerCase();


            /*
             * Unread count.
             */

            const unread =
                Number(
                    customer.unread ||
                    customer.unread_count ||
                    0
                );


            const item =
                document.createElement(
                    'div'
                );


            item.className =
                'admin-chat-customer-item';


            /*
             * Mark currently selected
             * customer.
             */

            if (
                selectedAdminCustomer &&
                String(
                    selectedAdminCustomer.id
                ) ===
                String(id)
            ) {

                item.classList.add(
                    'active'
                );

            }


            item.innerHTML = `

                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:9px;
                        min-width:0;
                    "
                >

                    <div
                        style="
                            width:32px;
                            height:32px;
                            min-width:32px;
                            border-radius:50%;
                            background:#f1f3f5;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            color:#e62429;
                        "
                    >

                        <i
                            class="bi bi-person-fill"
                        ></i>

                    </div>


                    <div
                        style="
                            min-width:0;
                        "
                    >

                        <div
                            class="admin-chat-customer-name"
                            style="
                                white-space:nowrap;
                                overflow:hidden;
                                text-overflow:ellipsis;
                            "
                        >

                            ${escapeChatHtml(name)}

                        </div>


                        <div
                            class="admin-chat-customer-status"
                        >

                            ${
                                status === 'live'
                                    ? '● Live'
                                    : 'Waiting'
                            }

                        </div>

                    </div>

                </div>


                ${
                    unread > 0
                        ? `
                            <div
                                class="admin-chat-unread"
                            >
                                ${
                                    unread > 9
                                        ? '9+'
                                        : unread
                                }
                            </div>
                        `
                        : ''
                }

            `;


            /*
             * Select customer.
             */

            item.addEventListener(
                'click',
                function() {

                    selectAdminCustomer(
                        customer
                    );

                }
            );


            list.appendChild(
                item
            );

        }
    );

}


/* ============================================================
   ESCAPE HTML
============================================================ */

function escapeChatHtml(
    value
) {

    const div =
        document.createElement(
            'div'
        );


    div.textContent =
        String(
            value ?? ''
        );


    return div.innerHTML;

}


/* ============================================================
   SELECT ADMIN CUSTOMER
============================================================ */

function selectAdminCustomer(
    customer
) {

    if (!customer) {

        return;

    }


    const id =
        customer.id ??
        customer.user_id ??
        customer.customer_id ??
        customer.session_id;


    selectedAdminCustomer = {

        ...customer,

        id:id

    };


    /*
     * Update customer name.
     */

    const selectedLabel =
        document.getElementById(
            'adminSelectedCustomer'
        );


    if (selectedLabel) {

        const name =
            customer.name ??
            customer.full_name ??
            customer.username ??
            customer.email ??
            'Customer';


        selectedLabel.innerHTML = `

            <div
                style="
                    display:flex;
                    align-items:center;
                    gap:8px;
                "
            >

                <div
                    style="
                        width:28px;
                        height:28px;
                        border-radius:50%;
                        background:#f1f3f5;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        color:#e62429;
                    "
                >

                    <i
                        class="bi bi-person-fill"
                    ></i>

                </div>


                <div>

                    <div>
                        ${escapeChatHtml(name)}
                    </div>

                    <div
                        style="
                            font-size:10px;
                            color:#888;
                            font-weight:400;
                        "
                    >

                        Customer ID:
                        ${escapeChatHtml(id)}

                    </div>

                </div>

            </div>

        `;

    }


    /*
     * Show reply area.
     */

    const replyArea =
        document.getElementById(
            'adminChatReplyArea'
        );


    if (replyArea) {

        replyArea.style.display =
            'block';

    }


    /*
     * Show end session button.
     */

    const endBar =
        document.getElementById(
            'adminEndSessionBar'
        );


    if (endBar) {

        endBar.style.display =
            'block';

    }


    /*
     * Load conversation.
     */

    loadAdminConversation(
        id
    );


    /*
     * Refresh customer list so
     * selected state is visible.
     */

    loadAdminCustomers();

}


/* ============================================================
   LOAD ADMIN CONVERSATION
============================================================ */

async function loadAdminConversation(
    customerId
) {

    if (!isEJRAdmin()) {

        return;

    }


    if (
        customerId === undefined ||
        customerId === null
    ) {

        return;

    }


    const token =
        getChatToken();


    if (!token) {

        return;

    }


    const messagesContainer =
        document.getElementById(
            'adminChatMessages'
        );


    if (!messagesContainer) {

        return;

    }


    messagesContainer.innerHTML = `

        <div
            style="
                text-align:center;
                color:#888;
                padding:30px 10px;
                font-size:12px;
            "
        >

            <span
                class="spinner-border spinner-border-sm me-1"
            ></span>

            Loading conversation...

        </div>

    `;


    try {

        const response =
            await fetch(
                '/api/check_session.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    },

                    body:JSON.stringify({

                        action:
                            'admin_messages',

                        customer_id:
                            customerId

                    })
                }
            );


        if (!response.ok) {

            throw new Error(
                'Unable to load conversation.'
            );

        }


        const data =
            await response.json();


        let messages = [];


        if (
            Array.isArray(
                data.messages
            )
        ) {

            messages =
                data.messages;

        }

        else if (
            Array.isArray(
                data.chat
            )
        ) {

            messages =
                data.chat;

        }

        else if (
            Array.isArray(data)
        ) {

            messages =
                data;

        }


        renderAdminMessages(
            messages
        );

    }

    catch (error) {

        console.error(
            'EJR ADMIN CONVERSATION ERROR:',
            error
        );


        messagesContainer.innerHTML = `

            <div
                style="
                    text-align:center;
                    color:#888;
                    padding:30px 10px;
                    font-size:12px;
                "
            >

                Unable to load
                this conversation.

            </div>

        `;

    }

}


/* ============================================================
   RENDER ADMIN MESSAGES
============================================================ */

function renderAdminMessages(
    messages
) {

    const container =
        document.getElementById(
            'adminChatMessages'
        );


    if (!container) {

        return;

    }


    container.innerHTML = '';


    if (
        !Array.isArray(messages) ||
        messages.length === 0
    ) {

        container.innerHTML = `

            <div
                style="
                    text-align:center;
                    color:#888;
                    padding:35px 10px;
                    font-size:12px;
                "
            >

                <i
                    class="bi bi-chat-left-text"
                    style="
                        font-size:28px;
                        display:block;
                        margin-bottom:8px;
                    "
                ></i>

                No messages yet.

            </div>

        `;

        return;

    }


    messages.forEach(
        function(message) {

            if (!message) {

                return;

            }


            const text =
                message.message ??
                message.text ??
                message.content ??
                '';


            if (
                String(text).trim() ===
                ''
            ) {

                return;

            }


            const sender =
                String(
                    message.sender ||
                    message.sender_type ||
                    message.role ||
                    ''
                ).toLowerCase();


            const wrapper =
                document.createElement(
                    'div'
                );


            wrapper.style.display =
                'flex';


            wrapper.style.marginBottom =
                '8px';


            /*
             * Customer message
             */

            if (
                sender === 'customer' ||
                sender === 'user' ||
                sender === 'client'
            ) {

                wrapper.style.justifyContent =
                    'flex-start';


                wrapper.innerHTML = `

                    <div
                        class="ejr-user-msg"
                        style="
                            background:#fff;
                            color:#263238;
                            border:1px solid #e4e7eb;
                        "
                    >

                        ${escapeChatHtml(text)}

                    </div>

                `;

            }

            /*
             * Admin message
             */

            else {

                wrapper.style.justifyContent =
                    'flex-end';


                wrapper.innerHTML = `

                    <div
                        class="ejr-admin-msg"
                        style="
                            background:#e62429;
                            color:#fff;
                            border-radius:
                                14px
                                14px
                                4px
                                14px;
                        "
                    >

                        ${escapeChatHtml(text)}

                    </div>

                `;

            }


            container.appendChild(
                wrapper
            );

        }
    );


    container.scrollTop =
        container.scrollHeight;

}


/* ============================================================
   SEND ADMIN REPLY
============================================================ */

async function sendAdminWidgetReply() {

    if (!isEJRAdmin()) {

        return;

    }


    if (
        !selectedAdminCustomer
    ) {

        Swal.fire({

            icon:'info',

            title:'Select a Customer',

            text:
                'Please select a customer conversation first.',

            confirmButtonColor:
                '#e62429'

        });

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


    const token =
        getChatToken();


    if (!token) {

        return;

    }


    const customerId =
        selectedAdminCustomer.id;


    try {

        if (button) {

            button.disabled =
                true;

        }


        /*
         * ====================================================
         * SEND ADMIN MESSAGE
         * ====================================================
         */

        const response =
            await fetch(
                '/api/check_session.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    },

                    body:JSON.stringify({

                        action:
                            'admin_reply',

                        customer_id:
                            customerId,

                        message:
                            message

                    })
                }
            );


        if (!response.ok) {

            throw new Error(
                'Unable to send reply.'
            );

        }


        const data =
            await response.json();


        if (
            data &&
            data.success === false
        ) {

            throw new Error(
                data.message ||
                'Unable to send reply.'
            );

        }


        /*
         * Clear input.
         */

        input.value =
            '';


        /*
         * Reload conversation.
         */

        await loadAdminConversation(
            customerId
        );


        /*
         * Refresh customer list.
         */

        loadAdminCustomers();

    }

    catch (error) {

        console.error(
            'EJR ADMIN REPLY ERROR:',
            error
        );


        Swal.fire({

            icon:'error',

            title:'Reply Failed',

            text:
                error.message ||
                'Unable to send your reply.',

            confirmButtonColor:
                '#e62429'

        });

    }

    finally {

        if (button) {

            button.disabled =
                false;

        }

    }

}


/* ============================================================
   ADMIN REPLY ENTER KEY
============================================================ */

document.addEventListener(
    'keydown',
    function(event) {

        const input =
            document.getElementById(
                'adminChatReplyInput'
            );


        if (!input) {

            return;

        }


        if (
            document.activeElement !==
            input
        ) {

            return;

        }


        if (
            event.key ===
            'Enter' &&
            !event.shiftKey
        ) {

            event.preventDefault();

            sendAdminWidgetReply();

        }

    }
);


/* ============================================================
   ADMIN END SELECTED SESSION
============================================================ */

async function adminEndSelectedSession() {

    if (!isEJRAdmin()) {

        return;

    }


    if (
        !selectedAdminCustomer
    ) {

        return;

    }


    const result =
        await Swal.fire({

            icon:'warning',

            title:'End Customer Session?',

            text:
                'This will end the selected customer live chat.',

            showCancelButton:true,

            confirmButtonText:
                'End Session',

            cancelButtonText:
                'Cancel',

            confirmButtonColor:
                '#e62429'

        });


    if (
        !result.isConfirmed
    ) {

        return;

    }


    const token =
        getChatToken();


    if (!token) {

        return;

    }


    try {

        const response =
            await fetch(
                '/api/check_session.php',
                {
                    method:'POST',

                    headers:{
                        'Content-Type':
                            'application/json',

                        'Authorization':
                            'Bearer ' + token
                    },

                    body:JSON.stringify({

                        action:
                            'admin_end_session',

                        customer_id:
                            selectedAdminCustomer.id

                    })
                }
            );


        if (!response.ok) {

            throw new Error(
                'Unable to end customer session.'
            );

        }


        const data =
            await response.json();


        if (
            data &&
            data.success === false
        ) {

            throw new Error(
                data.message ||
                'Unable to end customer session.'
            );

        }


        /*
         * Clear selected customer.
         */

        selectedAdminCustomer =
            null;


        /*
         * Clear messages.
         */

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

                    Customer session ended.

                </div>

            `;

        }


        /*
         * Reset selected customer label.
         */

        const selected =
            document.getElementById(
                'adminSelectedCustomer'
            );


        if (selected) {

            selected.textContent =
                'Select a customer';

        }


        /*
         * Hide reply/end controls.
         */

        const replyArea =
            document.getElementById(
                'adminChatReplyArea'
            );


        const endBar =
            document.getElementById(
                'adminEndSessionBar'
            );


        if (replyArea) {

            replyArea.style.display =
                'none';

        }


        if (endBar) {

            endBar.style.display =
                'none';

        }


        /*
         * Refresh customer list.
         */

        loadAdminCustomers();

    }

    catch (error) {

        console.error(
            'EJR ADMIN END SESSION ERROR:',
            error
        );


        Swal.fire({

            icon:'error',

            title:'Unable to End Session',

            text:
                error.message ||
                'Please try again.',

            confirmButtonColor:
                '#e62429'

        });

    }

}


/* ============================================================
   ADMIN CHAT POLLING
============================================================ */

async function pollAdminChat() {

    if (!isEJRAdmin()) {

        return;

    }


    const token =
        getChatToken();


    if (!token) {

        return;

    }


    /*
     * Refresh customer list.
     */

    await loadAdminCustomers();


    /*
     * Refresh currently selected
     * conversation.
     */

    if (
        selectedAdminCustomer &&
        selectedAdminCustomer.id
    ) {

        await loadAdminConversation(
            selectedAdminCustomer.id
        );

    }

}


/* ============================================================
   START ADMIN POLLING
============================================================ */

function startAdminChatPolling() {

    /*
     * Clear old interval.
     */

    if (
        adminChatPollingInterval
    ) {

        clearInterval(
            adminChatPollingInterval
        );

    }


    /*
     * Initial load.
     */

    pollAdminChat();


    /*
     * Poll every 4 seconds.
     */

    adminChatPollingInterval =
        setInterval(
            function() {

                pollAdminChat();

            },
            4000
        );

}


/* ============================================================
   STOP ADMIN POLLING
============================================================ */

function stopAdminChatPolling() {

    if (
        adminChatPollingInterval
    ) {

        clearInterval(
            adminChatPollingInterval
        );

        adminChatPollingInterval =
            null;

    }

}


/* ============================================================
   ADMIN CHAT CLEANUP
============================================================ */

function resetAdminChatWidget() {

    selectedAdminCustomer =
        null;


    stopAdminChatPolling();


    const panel =
        document.getElementById(
            'adminChatPanel'
        );


    if (panel) {

        panel.style.display =
            'none';

    }


    const reply =
        document.getElementById(
            'adminChatReplyArea'
        );


    const end =
        document.getElementById(
            'adminEndSessionBar'
        );


    if (reply) {

        reply.style.display =
            'none';

    }


    if (end) {

        end.style.display =
            'none';

    }


    const title =
        document.getElementById(
            'chatTitle'
        );


    if (title) {

        title.textContent =
            'EJR Assistant';

    }


    updateChatStatus(
        'bot'
    );

}


/* ============================================================
   START ADMIN POLLING WHEN ADMIN OPENS CHAT
============================================================ */

document.addEventListener(
    'DOMContentLoaded',
    function() {

        if (
            isEJRAdmin()
        ) {

            /*
             * Do not start a second interval
             * until the chat is actually opened.
             */

            return;

        }

    }
);

/* ============================================================
   FULL SCREEN CHAT
============================================================ */

function toggleChatFullscreen() {

    const chatBox =
        document.getElementById(
            'chatBox'
        );


    const button =
        document.getElementById(
            'chatFullscreenButton'
        );


    if (!chatBox) {

        console.error(
            'EJR CHAT: Chat box not found.'
        );

        return;

    }


    const isFullscreen =
        chatBox.classList.contains(
            'chat-fullscreen'
        );


    if (isFullscreen) {

        exitChatFullscreen();

    }

    else {

        enterChatFullscreen();

    }

}


/* ============================================================
   ENTER CHAT FULL SCREEN
============================================================ */

function enterChatFullscreen() {

    const chatBox =
        document.getElementById(
            'chatBox'
        );


    const button =
        document.getElementById(
            'chatFullscreenButton'
        );


    if (!chatBox) {

        return;

    }


    /*
     * Make sure chat is visible.
     */

    chatBox.style.display =
        'flex';


    /*
     * Add full-screen class.
     */

    chatBox.classList.add(
        'chat-fullscreen'
    );


    /*
     * Prevent page scrolling.
     */

    document.body.classList.add(
        'ejr-chat-fullscreen-active'
    );


    /*
     * Change icon.
     */

    if (button) {

        button.innerHTML = `

            <i
                class="bi bi-fullscreen-exit"
            ></i>

        `;

        button.title =
            'Exit Full Screen';

        button.setAttribute(
            'aria-label',
            'Exit Full Screen'
        );

    }


    /*
     * Scroll customer messages
     * to the bottom.
     */

    const customerBody =
        document.getElementById(
            'chatBody'
        );


    if (customerBody) {

        setTimeout(
            function() {

                customerBody.scrollTop =
                    customerBody.scrollHeight;

            },
            100
        );

    }


    /*
     * Scroll admin messages
     * to the bottom.
     */

    const adminMessages =
        document.getElementById(
            'adminChatMessages'
        );


    if (adminMessages) {

        setTimeout(
            function() {

                adminMessages.scrollTop =
                    adminMessages.scrollHeight;

            },
            100
        );

    }

}


/* ============================================================
   EXIT CHAT FULL SCREEN
============================================================ */

function exitChatFullscreen() {

    const chatBox =
        document.getElementById(
            'chatBox'
        );


    const button =
        document.getElementById(
            'chatFullscreenButton'
        );


    if (!chatBox) {

        return;

    }


    /*
     * Remove full-screen class.
     */

    chatBox.classList.remove(
        'chat-fullscreen'
    );


    /*
     * Restore page scrolling.
     */

    document.body.classList.remove(
        'ejr-chat-fullscreen-active'
    );


    /*
     * Restore icon.
     */

    if (button) {

        button.innerHTML = `

            <i
                class="bi bi-arrows-fullscreen"
            ></i>

        `;

        button.title =
            'Full Screen';

        button.setAttribute(
            'aria-label',
            'Full Screen'
        );

    }

}


/* ============================================================
   ESC KEY TO EXIT FULL SCREEN
============================================================ */

document.addEventListener(
    'keydown',
    function(event) {

        if (
            event.key !==
            'Escape'
        ) {

            return;

        }


        const chatBox =
            document.getElementById(
                'chatBox'
            );


        if (!chatBox) {

            return;

        }


        if (
            chatBox.classList.contains(
                'chat-fullscreen'
            )
        ) {

            exitChatFullscreen();

        }

    }
);


/* ============================================================
   HANDLE REAL BROWSER FULLSCREEN EXIT
============================================================ */

document.addEventListener(
    'fullscreenchange',
    function() {

        /*
         * This keeps the custom
         * full-screen UI synchronized
         * if another element enters
         * browser fullscreen.
         */

        const chatBox =
            document.getElementById(
                'chatBox'
            );


        if (!chatBox) {

            return;

        }

    }
);


/* ============================================================
   CHAT OPEN/CLOSE STATE
============================================================ */

function closeChatWidget() {

    const chatBox =
        document.getElementById(
            'chatBox'
        );


    if (!chatBox) {

        return;

    }


    /*
     * Exit full screen first.
     */

    if (
        chatBox.classList.contains(
            'chat-fullscreen'
        )
    ) {

        exitChatFullscreen();

    }


    /*
     * Hide widget.
     */

    chatBox.style.display =
        'none';

}


/* ============================================================
   IMPROVED TOGGLE
============================================================ */

function openChatWidget() {

    const chatBox =
        document.getElementById(
            'chatBox'
        );


    if (!chatBox) {

        return;

    }


    chatBox.style.display =
        'flex';


    hideChatNotification();


    /*
     * ADMIN
     */

    if (isEJRAdmin()) {

        initializeAdminChatWidget();

    }

    /*
     * CUSTOMER
     */

    else {

        loadMessages();

    }

}


/* ============================================================
   KEEP ORIGINAL toggleChat() BEHAVIOR
============================================================ */

function toggleChat() {

    const chatBox =
        document.getElementById(
            'chatBox'
        );


    if (!chatBox) {

        return;

    }


    const isOpen =
        chatBox.style.display ===
        'flex';


    if (isOpen) {

        closeChatWidget();

    }

    else {

        openChatWidget();

    }

}


/* ============================================================
   CLEAN CUSTOMER POLLING
============================================================ */

function initializeCustomerPollingOnce() {

    /*
     * Never start customer polling
     * for admin accounts.
     */

    if (
        isEJRAdmin()
    ) {

        return;

    }


    /*
     * If an interval already exists,
     * do nothing.
     */

    if (
        chatPollingInterval
    ) {

        return;

    }


    startCustomerChatPolling();

}


/* ============================================================
   CLEAN ADMIN POLLING
============================================================ */

function initializeAdminPollingOnce() {

    if (
        !isEJRAdmin()
    ) {

        return;

    }


    if (
        adminChatPollingInterval
    ) {

        return;

    }


    /*
     * Admin polling begins when
     * the admin opens the widget.
     */

}


/* ============================================================
   PAGE INITIALIZATION
============================================================ */

document.addEventListener(
    'DOMContentLoaded',
    function() {

        /*
         * ====================================================
         * INITIAL CHAT STATE
         * ====================================================
         */

        const chatBox =
            document.getElementById(
                'chatBox'
            );


        if (chatBox) {

            chatBox.style.display =
                'none';

        }


        /*
         * ====================================================
         * INITIALIZE CUSTOMER POLLING
         * ====================================================
         */

        if (
            !isEJRAdmin()
        ) {

            /*
             * Start only once.
             */

            initializeCustomerPollingOnce();

        }


        /*
         * ====================================================
         * CHAT INPUT
         * ====================================================
         */

        const chatInput =
            document.getElementById(
                'chatInput'
            );


        if (chatInput) {

            chatInput.addEventListener(
                'keydown',
                function(event) {

                    if (
                        event.key ===
                        'Enter' &&
                        !event.shiftKey
                    ) {

                        event.preventDefault();

                        sendChatMessage();

                    }

                }
            );

        }


        /*
         * ====================================================
         * ADMIN INPUT
         * ====================================================
         */

        const adminInput =
            document.getElementById(
                'adminChatReplyInput'
            );


        if (adminInput) {

            adminInput.addEventListener(
                'keydown',
                function(event) {

                    if (
                        event.key ===
                        'Enter' &&
                        !event.shiftKey
                    ) {

                        event.preventDefault();

                        sendAdminWidgetReply();

                    }

                }
            );

        }

    }
);


/* ============================================================
   HANDLE LOGOUT / TOKEN REMOVAL
============================================================ */

window.addEventListener(
    'storage',
    function(event) {

        if (
            event.key !==
            'jwt_token'
        ) {

            return;

        }


        /*
         * User logged out.
         */

        if (!event.newValue) {

            stopCustomerChatPolling();

            stopAdminChatPolling();


            currentChatSession =
                null;


            selectedAdminCustomer =
                null;


            closeChatWidget();

        }

    }
);


/* ============================================================
   BEFORE PAGE UNLOAD
============================================================ */

window.addEventListener(
    'beforeunload',
    function() {

        stopCustomerChatPolling();

        stopAdminChatPolling();

    }
);


/* ============================================================
   FINAL SAFETY CHECK
============================================================ */

(function() {

    /*
     * Make sure the chatbot exists
     * before doing anything else.
     */

    const chatBox =
        document.getElementById(
            'chatBox'
        );


    if (!chatBox) {

        console.warn(
            'EJR CHAT: Chat widget was not found on this page.'
        );

        return;

    }


    /*
     * Make sure the correct mode
     * is displayed.
     */

    if (
        isEJRAdmin()
    ) {

        const title =
            document.getElementById(
                'chatTitle'
            );


        if (title) {

            title.textContent =
                'EJR Live Chat';

        }

    }

})();

</script>


<!-- ============================================================
     END OF EJR GARAGE FOOTER
============================================================ -->
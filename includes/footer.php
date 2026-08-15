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

    <!-- ========================================================
         CHAT TOGGLE
    ========================================================= -->

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

            box-shadow:
                0 20px 60px
                rgba(0,0,0,0.25);

            flex-direction:column;

            overflow:hidden;

            border:1px solid #e3e8ef;

            pointer-events:auto;
        "
    >

        <!-- ====================================================
             HEADER
        ===================================================== -->

        <div
            id="chatHeader"
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
                 HEADER CONTROLS
            ================================================== -->

            <div
                style="
                    display:flex;
                    align-items:center;
                    gap:4px;
                "
            >

                <!-- FULL SCREEN -->

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
                        opacity:0.9;
                    "
                >

                    <i
                        class="bi bi-arrows-fullscreen"
                    ></i>

                </button>


                <!-- CLOSE -->

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
                        opacity:0.8;
                    "
                >

                    &times;

                </button>

            </div>

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
                style="
                    width:11px;
                    height:11px;
                "
            ></span>

            Live chat starts immediately
            when you request an agent.

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

            You are now connected
            to a live admin

        </div>


        <!-- ====================================================
             CUSTOMER CHAT BODY
        ===================================================== -->

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


            <!-- =================================================
                 ADMIN REPLY
            ================================================== -->

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
             CHAT OPTIONS / QUICK ACTIONS
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
                onclick="askQuickQuestion('services')"
                class="chat-option-btn"
            >
                <i class="bi bi-tools"></i>
                Services
            </button>


            <button
                type="button"
                onclick="askQuickQuestion('booking')"
                class="chat-option-btn"
            >
                <i class="bi bi-calendar-check"></i>
                Booking
            </button>


            <button
                type="button"
                onclick="askQuickQuestion('hours')"
                class="chat-option-btn"
            >
                <i class="bi bi-clock"></i>
                Hours
            </button>


            <button
                type="button"
                onclick="askQuickQuestion('location')"
                class="chat-option-btn"
            >
                <i class="bi bi-geo-alt-fill"></i>
                Location
            </button>


            <button
                type="button"
                onclick="askQuickQuestion('contact')"
                class="chat-option-btn"
            >
                <i class="bi bi-telephone-fill"></i>
                Contact
            </button>

        </div>


        <!-- ====================================================
             TALK TO AGENT BUTTON
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
                onclick="requestLiveAgent()"
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


            <!-- END SESSION -->

            <div
                style="
                    text-align:center;
                    margin-top:6px;
                "
            >

                <button
                    type="button"
                    onclick="userEndSession()"
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
   CHAT OPTIONS
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
   CUSTOMER BOT MESSAGE
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
   CUSTOMER MESSAGE
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
   LIVE ADMIN MESSAGE
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
   FULL SCREEN CHAT BOX
============================================================ */

#chatBox.chat-fullscreen {

    position:fixed !important;

    top:0 !important;

    left:0 !important;

    right:0 !important;

    bottom:0 !important;

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
   FULL SCREEN HEADER
============================================================ */

#chatBox.chat-fullscreen #chatHeader {

    flex-shrink:0;

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

    height:150px;

    max-height:150px;

    overflow-y:auto;

    flex-shrink:0;

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
   ADMIN REPLY
============================================================ */

#chatBox.chat-fullscreen
#adminChatReplyArea {

    flex-shrink:0;

}


/* ============================================================
   ADMIN END SESSION
============================================================ */

#chatBox.chat-fullscreen
#adminEndSessionBar {

    flex-shrink:0;

}


/* ============================================================
   CUSTOMER OPTIONS
============================================================ */

#chatBox.chat-fullscreen
#chatOptions {

    flex-shrink:0;

}


/* ============================================================
   CUSTOMER LIVE INPUT
============================================================ */

#chatBox.chat-fullscreen
#customerChatInputContainer {

    flex-shrink:0;

}


/* ============================================================
   TALK TO AGENT
============================================================ */

#chatBox.chat-fullscreen
#talkAgentArea {

    flex-shrink:0;

}


/* ============================================================
   BODY SCROLL LOCK
============================================================ */

body.ejr-chat-fullscreen-active {

    overflow:hidden !important;

}


/* ============================================================
   MAP CONTAINER
============================================================ */

.ejr-map-container {

    width:100%;

    height:250px;

    overflow:hidden;

    border-radius:12px;

}


.ejr-map-container iframe {

    width:100%;

    height:250px;

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
   MOBILE CHAT
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

        height:130px;

        max-height:130px;

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


/* ============================================================
   CHAT FULL SCREEN TRANSITION
============================================================ */

#chatBox {

    transition:
        width .25s ease,
        height .25s ease,
        top .25s ease,
        left .25s ease,
        right .25s ease,
        bottom .25s ease,
        border-radius .25s ease;

}

</style>
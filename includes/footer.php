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
        style="border-top:4px solid #b30000;position:relative;z-index:10;">

    <div class="container">

        <div class="row g-4 justify-content-between">

            <!-- CONTACT -->
            <div class="col-md-4 text-start">

                <h6 class="fw-bold mb-4 text-uppercase"
                    style="letter-spacing:1px;">
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


            <!-- BRAND -->
            <div class="col-md-4 text-center d-flex flex-column align-items-center">

                <div class="footer-brand mb-3">

                    <img src="/assets/images/Inverted_EJRLogo.png"
                         alt="EJR Garage"
                         style="height:55px;width:auto;">

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

                <a href="/download-app.php"
                   class="d-inline-flex align-items-center gap-2 mt-3 px-3 py-2 text-white text-decoration-none"
                   style="background:rgba(255,255,255,0.08);border-radius:10px;font-size:0.85rem;">

                    <i class="bi bi-android2 text-danger fs-5"></i>
                    Get the App

                </a>

            </div>


            <!-- OFFICE HOURS -->
            <div class="col-md-4 text-start text-md-end">

                <div class="d-inline-block text-start">

                    <h6 class="fw-bold mb-4 text-uppercase"
                        style="letter-spacing:1px;">
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


<!-- ========================================================= -->
<!-- EJR GARAGE LIVE CHAT -->
<!-- ========================================================= -->

<div id="ejrChatContainer"
     style="
        position:fixed;
        bottom:25px;
        right:25px;
        z-index:9999999;
        pointer-events:none;
     ">


    <!-- CHAT BUTTON -->

    <div id="chatPill"
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
         ">

        <i class="bi bi-chat-dots-fill me-2 fs-5"></i>

        LIVE CHAT

        <span id="chatNotification"
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
              ">
            0
        </span>

    </div>


    <!-- CHAT BOX -->

    <div id="chatBox"
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
         ">


        <!-- HEADER -->

        <div style="
            background:linear-gradient(135deg,#1e2a36,#2d3f50);
            color:white;
            padding:16px 20px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            border-bottom:3px solid #e62429;
        ">

            <div class="d-flex align-items-center gap-2">

                <div style="
                    width:38px;
                    height:38px;
                    background:#e62429;
                    border-radius:50%;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                ">

                    <i class="bi bi-robot fs-5"></i>

                </div>


                <div>

                    <div style="font-weight:700;font-size:15px;">
                        EJR Assistant
                    </div>

                    <div id="chatStatusLabel"
                         style="font-size:11px;opacity:0.75;">
                        Live Chat • We're here to help
                    </div>

                </div>

            </div>


            <button onclick="toggleChat()"
                    style="
                        background:none;
                        border:none;
                        color:#fff;
                        cursor:pointer;
                        font-size:22px;
                        opacity:0.8;
                    ">

                &times;

            </button>

        </div>


        <!-- LIVE STATUS -->

        <div id="liveIndicator"
             style="
                display:block;
                padding:8px 16px;
                background:#d1fae5;
                color:#065f46;
                font-size:12px;
                text-align:center;
                border-bottom:1px solid #6ee7b7;
             ">

            <i class="bi bi-circle-fill me-1"
               style="font-size:8px;"></i>

            Send us a message and our admin will respond.

        </div>


        <!-- CHAT MESSAGES -->

        <div id="chatBody"
             style="
                height:300px;
                padding:16px;
                overflow-y:auto;
                background:#f0f2f5;
                display:flex;
                flex-direction:column;
                gap:8px;
             ">

            <div class="ejr-bot-msg">

                Hi! 👋 Welcome to
                <strong>EJR Garage</strong>.

                How can I help you today?

            </div>

        </div>


        <!-- TYPING INDICATOR -->

        <div id="typingIndicator"
             style="
                display:none;
                padding:6px 16px;
                background:#f0f2f5;
                border-top:1px solid #e9ecef;
             ">

            <div style="
                background:#e9ecef;
                padding:8px 12px;
                border-radius:12px 12px 12px 0;
                display:inline-flex;
                gap:4px;
                align-items:center;
             ">

                <span class="ejr-dot"></span>
                <span class="ejr-dot"></span>
                <span class="ejr-dot"></span>

            </div>

        </div>


        <!-- QUICK OPTIONS -->

        <div id="chatOptions"
             style="
                padding:10px 14px;
                background:#fff;
                display:flex;
                flex-wrap:wrap;
                gap:6px;
                border-top:1px solid #eee;
             ">

            <button onclick="askQuickQuestion('Services Offered')"
                    class="opt-btn">

                <i class="bi bi-tools me-1"></i>
                Services

            </button>


            <button onclick="askQuickQuestion('Booking Help')"
                    class="opt-btn">

                <i class="bi bi-calendar me-1"></i>
                Booking

            </button>


            <button onclick="askQuickQuestion('Operating Hours')"
                    class="opt-btn">

                <i class="bi bi-clock me-1"></i>
                Hours

            </button>


            <button onclick="askQuickQuestion('Location')"
                    class="opt-btn">

                <i class="bi bi-geo-alt me-1"></i>
                Location

            </button>


            <button onclick="askQuickQuestion('Contact')"
                    class="opt-btn">

                <i class="bi bi-telephone me-1"></i>
                Contact

            </button>

        </div>


        <!-- MESSAGE INPUT -->

        <div style="
            padding:12px 14px;
            background:#fff;
            border-top:1px solid #eee;
            display:flex;
            gap:8px;
            align-items:center;
         ">

            <input type="text"
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
                   onkeydown="if(event.key==='Enter') sendChatMessage()">


            <button onclick="sendChatMessage()"
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
                    ">

                <i class="bi bi-send-fill"
                   style="font-size:14px;"></i>

            </button>

        </div>

    </div>

</div>


<!-- ========================================================= -->
<!-- CHAT CSS -->
<!-- ========================================================= -->

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


.ejr-bot-msg {

    background:#fff;

    color:#333;

    padding:10px 14px;

    border-radius:16px 16px 16px 4px;

    font-size:13px;

    align-self:flex-start;

    max-width:85%;

    box-shadow:0 1px 3px rgba(0,0,0,0.08);

    line-height:1.5;

}


.ejr-user-msg {

    background:linear-gradient(135deg,#e62429,#ff4444);

    color:white;

    padding:10px 14px;

    border-radius:16px 16px 4px 16px;

    font-size:13px;

    align-self:flex-end;

    max-width:85%;

    box-shadow:0 1px 3px rgba(0,0,0,0.15);

    line-height:1.5;

}


.ejr-admin-msg {

    background:#ffffff;

    color:#333;

    padding:10px 14px;

    border-radius:16px 16px 16px 4px;

    font-size:13px;

    align-self:flex-start;

    max-width:85%;

    box-shadow:0 1px 3px rgba(0,0,0,0.08);

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

    0%,60%,100% {

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


@media (max-width:576px) {

    #ejrChatContainer {

        right:15px !important;

        bottom:15px !important;

    }

    #chatBox {

        width:calc(100vw - 30px) !important;

        right:0 !important;

    }

}

</style>


<!-- ========================================================= -->
<!-- CHAT JAVASCRIPT -->
<!-- ========================================================= -->

<script>

let chatPolling = null;


/* =========================================================
   TOGGLE CHAT
========================================================= */

function toggleChat() {

    const box = document.getElementById('chatBox');

    if (!box) return;

    const isOpening =
        box.style.display === 'none' ||
        box.style.display === '';

    box.style.display =
        isOpening ? 'flex' : 'none';


    if (isOpening) {

        loadMessages();

        const notification =
            document.getElementById('chatNotification');

        if (notification) {

            notification.style.display = 'none';

        }

    }

}


/* =========================================================
   LOAD MESSAGES
========================================================= */

function loadMessages() {

    const token =
        localStorage.getItem('jwt_token') || '';


    fetch('/api/check_session.php', {

        method:'POST',

        headers:{

            'Content-Type':'application/json',

            'Authorization':
                'Bearer ' + token

        },

        body:JSON.stringify({

            type:'get_messages'

        })

    })

    .then(response => {

        if (!response.ok) {

            throw new Error(
                'Server returned ' + response.status
            );

        }

        return response.json();

    })

    .then(data => {

        if (!data) return;


        const body =
            document.getElementById('chatBody');

        if (!body) return;


        if (
            data.messages &&
            data.messages.length > 0
        ) {

            body.innerHTML =
                data.messages.map(message => {

                    let messageClass;

                    if (
                        message.sender === 'user'
                    ) {

                        messageClass =
                            'ejr-user-msg';

                    } else {

                        messageClass =
                            'ejr-admin-msg';

                    }


                    return `

                        <div class="${messageClass}">

                            <div>
                                ${escapeChatHTML(message.message)}
                            </div>

                            ${
                                message.time
                                ? `
                                    <div class="ejr-time">
                                        ${message.time}
                                    </div>
                                  `
                                : ''
                            }

                        </div>

                    `;

                }).join('');


            body.scrollTop =
                body.scrollHeight;

        }

    })

    .catch(error => {

        console.error(
            'EJR Chat loading error:',
            error
        );

    });

}


/* =========================================================
   SEND MESSAGE
========================================================= */

function sendChatMessage() {

    const input =
        document.getElementById('chatInput');

    if (!input) return;


    const message =
        input.value.trim();


    if (!message) return;


    appendMessage(
        message,
        'user'
    );


    input.value = '';


    const token =
        localStorage.getItem('jwt_token') || '';


    fetch('/api/check_session.php', {

        method:'POST',

        headers:{

            'Content-Type':'application/json',

            'Authorization':
                'Bearer ' + token

        },

        body:JSON.stringify({

            type:'chat',

            action:'chat',

            message:message

        })

    })

    .then(response => response.json())

    .then(data => {

        if (
            data &&
            data.reply
        ) {

            appendMessage(
                data.reply,
                'admin'
            );

        }

    })

    .catch(error => {

        console.error(
            'EJR Chat send error:',
            error
        );

    });

}


/* =========================================================
   APPEND MESSAGE
========================================================= */

function appendMessage(
    text,
    sender
) {

    const body =
        document.getElementById('chatBody');

    if (!body) return;


    const div =
        document.createElement('div');


    div.className =
        sender === 'user'
        ? 'ejr-user-msg'
        : 'ejr-admin-msg';


    const messageDiv =
        document.createElement('div');


    messageDiv.textContent =
        text;


    div.appendChild(
        messageDiv
    );


    body.appendChild(
        div
    );


    body.scrollTop =
        body.scrollHeight;

}


/* =========================================================
   QUICK QUESTIONS
========================================================= */

function askQuickQuestion(question) {

    const input =
        document.getElementById('chatInput');

    if (!input) return;


    input.value =
        question;


    sendChatMessage();

}


/* =========================================================
   ESCAPE CHAT HTML
========================================================= */

function escapeChatHTML(text) {

    const div =
        document.createElement('div');

    div.textContent =
        text ?? '';

    return div.innerHTML;

}


/* =========================================================
   START REAL-TIME POLLING
========================================================= */

function startChatPolling() {

    if (chatPolling) {

        clearInterval(
            chatPolling
        );

    }


    chatPolling =
        setInterval(function() {

            const box =
                document.getElementById('chatBox');


            if (!box) return;


            if (
                box.style.display === 'flex'
            ) {

                loadMessages();

            }

        }, 2000);

}


/* =========================================================
   START CHAT
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function() {

        startChatPolling();

    }
);

</script>


<!-- ========================================================= -->
<!-- FLATPICKR -->
<!-- ========================================================= -->

<script>

fetch('api/get_busy_dates.php')

    .then(res => res.json())

    .then(data => {

        const busyDates =
            data.busy_dates ?? [];


        const appointmentDate =
            document.querySelector(
                '#appointmentDate'
            );


        if (!appointmentDate) {

            return;

        }


        flatpickr(
            '#appointmentDate',
            {

                minDate:'today',

                dateFormat:'Y-m-d',

                disableMobile:true,


                disable:[

                    function(date) {

                        return date.getDay() === 0;

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

    })

    .catch(error => {

        console.error(
            'Busy dates error:',
            error
        );

    });

</script>
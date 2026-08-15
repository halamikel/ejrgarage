/* ============================================================
   EJR GARAGE CHATBOT
   GOOGLE MAPS LOCATION
============================================================ */

/*
    EJR GARAGE LOCATION

    Address:
    6HJH+R9R, Mabalacat City, Pampanga, Philippines

    Coordinates:
    Latitude:  15.2321026
    Longitude: 120.5783981
*/


(function () {

    "use strict";


    /* ========================================================
       GOOGLE MAPS INFORMATION
    ======================================================== */

    const EJR_LOCATION = {

        name:
            "EJR Garage",

        address:
            "6HJH+R9R, Mabalacat City, Pampanga, Philippines",

        latitude:
            "15.2321026",

        longitude:
            "120.5783981"

    };


    /* ========================================================
       GOOGLE MAPS URL
    ======================================================== */

    function getGoogleMapsUrl() {

        return (
            "https://www.google.com/maps/dir/?api=1" +
            "&destination=" +
            encodeURIComponent(
                EJR_LOCATION.latitude +
                "," +
                EJR_LOCATION.longitude
            )
        );

    }


    /* ========================================================
       GOOGLE MAP EMBED URL
    ======================================================== */

    function getGoogleMapsEmbedUrl() {

        return (
            "https://www.google.com/maps" +
            "?q=" +
            encodeURIComponent(
                EJR_LOCATION.latitude +
                "," +
                EJR_LOCATION.longitude
            ) +
            "&z=16" +
            "&output=embed"
        );

    }


    /* ========================================================
       CHECK LOGIN
    ======================================================== */

    function checkChatLogin() {

        const token =
            localStorage.getItem(
                "jwt_token"
            );


        if (!token) {

            window.location.href =
                "/login.php";

            return false;

        }


        return true;

    }


    /* ========================================================
       CHECK ADMIN
    ======================================================== */

    function checkAdmin() {

        try {

            const user =
                JSON.parse(
                    localStorage.getItem(
                        "user"
                    ) || "{}"
                );


            return (
                Number(
                    user.role_id
                ) === 2
            );

        }

        catch (error) {

            console.error(
                "Unable to check admin status:",
                error
            );


            return false;

        }

    }


    /* ========================================================
       ADD USER MESSAGE
    ======================================================== */

    function addUserLocationMessage(
        chatBody
    ) {

        const message =
            document.createElement(
                "div"
            );


        message.className =
            "ejr-user-msg";


        message.textContent =
            "Location";


        chatBody.appendChild(
            message
        );

    }


    /* ========================================================
       ADD BOT LOCATION MESSAGE
    ======================================================== */

    function addLocationMessage(
        chatBody
    ) {

        const botMessage =
            document.createElement(
                "div"
            );


        botMessage.className =
            "ejr-bot-msg ejr-location-message";


        const mapsUrl =
            getGoogleMapsUrl();


        const embedUrl =
            getGoogleMapsEmbedUrl();


        botMessage.innerHTML = `

            <div class="ejr-location-card">

                <!-- ============================================
                     HEADER
                ============================================= -->

                <div class="ejr-location-header">

                    <div class="ejr-location-icon">

                        <i
                            class="bi bi-geo-alt-fill"
                        ></i>

                    </div>


                    <div>

                        <div
                            class="ejr-location-title"
                        >
                            EJR Garage
                        </div>


                        <div
                            class="ejr-location-subtitle"
                        >
                            Our Location
                        </div>

                    </div>

                </div>


                <!-- ============================================
                     ADDRESS
                ============================================= -->

                <div
                    class="ejr-location-address"
                >

                    <i
                        class="bi bi-pin-map-fill"
                    ></i>


                    <span>
                        6HJH+R9R, Mabalacat City,
                        Pampanga, Philippines
                    </span>

                </div>


                <!-- ============================================
                     GOOGLE MAP
                ============================================= -->

                <div
                    class="ejr-map-container"
                >

                    <iframe

                        src="${embedUrl}"

                        title="EJR Garage Google Maps Location"

                        loading="lazy"

                        allowfullscreen

                        referrerpolicy="
                            no-referrer-when-downgrade
                        "

                    ></iframe>

                </div>


                <!-- ============================================
                     DIRECTIONS BUTTON
                ============================================= -->

                <a

                    href="${mapsUrl}"

                    target="_blank"

                    rel="noopener noreferrer"

                    class="ejr-location-directions"

                >

                    <i
                        class="bi bi-map-fill"
                    ></i>


                    Get Directions

                </a>


                <!-- ============================================
                     LOCATION NOTE
                ============================================= -->

                <div
                    class="ejr-location-note"
                >

                    <i
                        class="bi bi-info-circle-fill"
                    ></i>


                    Tap "Get Directions" to open
                    Google Maps.

                </div>

            </div>

        `;


        chatBody.appendChild(
            botMessage
        );


        scrollChatToBottom(
            chatBody
        );

    }


    /* ========================================================
       SCROLL CHAT TO BOTTOM
    ======================================================== */

    function scrollChatToBottom(
        chatBody
    ) {

        if (!chatBody) {

            return;

        }


        setTimeout(
            function () {

                chatBody.scrollTop =
                    chatBody.scrollHeight;

            },
            50
        );

    }


    /* ========================================================
       SHOW EJR GARAGE LOCATION
    ======================================================== */

    window.showEJRGarageLocation =
        function () {

            /* ==================================================
               ADMIN CHECK
            ================================================== */

            if (
                checkAdmin()
            ) {

                return;

            }


            /* ==================================================
               LOGIN CHECK
            ================================================== */

            if (
                !checkChatLogin()
            ) {

                return;

            }


            /* ==================================================
               GET CHAT BODY
            ================================================== */

            const chatBody =
                document.getElementById(
                    "chatBody"
                );


            if (!chatBody) {

                console.error(
                    "EJR CHAT: #chatBody was not found."
                );

                return;

            }


            /* ==================================================
               ADD USER MESSAGE
            ================================================== */

            addUserLocationMessage(
                chatBody
            );


            /* ==================================================
               ADD BOT LOCATION
            ================================================== */

            addLocationMessage(
                chatBody
            );

        };


    /* ========================================================
       REPLACE QUICK QUESTION FUNCTION
    ======================================================== */

    window.askQuickQuestion =
        function (question) {

            /* ==================================================
               ADMIN USERS
            ================================================== */

            if (
                checkAdmin()
            ) {

                return;

            }


            /* ==================================================
               LOGIN CHECK
            ================================================== */

            if (
                !checkChatLogin()
            ) {

                return;

            }


            /* ==================================================
               LOCATION
            ================================================== */

            if (
                String(question)
                    .trim()
                    .toLowerCase() ===
                "location"
            ) {

                showEJRGarageLocation();

                return;

            }


            /* ==================================================
               NORMAL QUICK QUESTION
            ================================================== */

            const input =
                document.getElementById(
                    "chatInput"
                );


            if (!input) {

                console.error(
                    "EJR CHAT: #chatInput was not found."
                );

                return;

            }


            input.value =
                question;


            /* ==================================================
               USE EXISTING CHAT FUNCTION
            ================================================== */

            if (
                typeof window.sendChatMessage ===
                "function"
            ) {

                window.sendChatMessage();

            }

            else {

                console.error(
                    "EJR CHAT: sendChatMessage() was not found."
                );

            }

        };


    /* ========================================================
       ADD LOCATION CSS
    ======================================================== */

    function addLocationStyles() {

        /* ======================================================
           DON'T ADD STYLES TWICE
        ====================================================== */

        if (
            document.getElementById(
                "ejr-location-chat-styles"
            )
        ) {

            return;

        }


        const style =
            document.createElement(
                "style"
            );


        style.id =
            "ejr-location-chat-styles";


        style.textContent = `

            /* ==================================================
               LOCATION MESSAGE
            ================================================== */

            .ejr-location-message {

                width:100%;

                max-width:100%;

            }


            /* ==================================================
               LOCATION CARD
            ================================================== */

            .ejr-location-card {

                width:100%;

                box-sizing:border-box;

                background:#ffffff;

                border:1px solid #e1e5e9;

                border-radius:14px;

                padding:10px;

                box-shadow:
                    0 2px 8px
                    rgba(
                        0,
                        0,
                        0,
                        0.08
                    );

            }


            /* ==================================================
               LOCATION HEADER
            ================================================== */

            .ejr-location-header {

                display:flex;

                align-items:center;

                gap:9px;

                margin-bottom:10px;

            }


            /* ==================================================
               LOCATION ICON
            ================================================== */

            .ejr-location-icon {

                width:36px;

                height:36px;

                min-width:36px;

                border-radius:50%;

                display:flex;

                align-items:center;

                justify-content:center;

                background:
                    rgba(
                        230,
                        36,
                        41,
                        0.10
                    );

                color:#e62429;

                font-size:17px;

            }


            /* ==================================================
               LOCATION TITLE
            ================================================== */

            .ejr-location-title {

                font-size:14px;

                font-weight:700;

                color:#1e2a36;

                line-height:1.2;

            }


            /* ==================================================
               LOCATION SUBTITLE
            ================================================== */

            .ejr-location-subtitle {

                font-size:10px;

                color:#888;

                margin-top:2px;

            }


            /* ==================================================
               ADDRESS
            ================================================== */

            .ejr-location-address {

                display:flex;

                align-items:flex-start;

                gap:7px;

                font-size:11px;

                line-height:1.45;

                color:#626b75;

                margin-bottom:10px;

            }


            .ejr-location-address i {

                color:#e62429;

                font-size:13px;

                margin-top:2px;

                flex-shrink:0;

            }


            /* ==================================================
               MAP CONTAINER
            ================================================== */

            .ejr-map-container {

                width:100%;

                height:210px;

                overflow:hidden;

                border-radius:12px;

                border:1px solid #dfe3e7;

                background:#eeeeee;

                margin-bottom:10px;

            }


            /* ==================================================
               MAP IFRAME
            ================================================== */

            .ejr-map-container iframe {

                display:block;

                width:100%;

                height:210px;

                border:0;

            }


            /* ==================================================
               GET DIRECTIONS
            ================================================== */

            .ejr-location-directions {

                display:flex;

                align-items:center;

                justify-content:center;

                gap:7px;

                width:100%;

                box-sizing:border-box;

                padding:10px 12px;

                border-radius:9px;

                background:
                    linear-gradient(
                        45deg,
                        #e62429,
                        #ff6b00
                    );

                color:#ffffff !important;

                text-decoration:none !important;

                font-size:12px;

                font-weight:700;

                transition:
                    transform .2s ease,
                    box-shadow .2s ease;

            }


            .ejr-location-directions:hover {

                color:#ffffff !important;

                text-decoration:none !important;

                transform:
                    translateY(-1px);

                box-shadow:
                    0 5px 14px
                    rgba(
                        230,
                        36,
                        41,
                        0.25
                    );

            }


            /* ==================================================
               DIRECTIONS ICON
            ================================================== */

            .ejr-location-directions i {

                font-size:13px;

            }


            /* ==================================================
               LOCATION NOTE
            ================================================== */

            .ejr-location-note {

                display:flex;

                align-items:flex-start;

                gap:5px;

                margin-top:8px;

                color:#858d96;

                font-size:9px;

                line-height:1.35;

            }


            .ejr-location-note i {

                font-size:10px;

                margin-top:1px;

            }


            /* ==================================================
               MOBILE
            ================================================== */

            @media (max-width:500px) {

                .ejr-location-card {

                    padding:8px;

                }


                .ejr-map-container {

                    height:185px;

                }


                .ejr-map-container iframe {

                    height:185px;

                }


                .ejr-location-address {

                    font-size:10px;

                }

            }

        `;


        document.head.appendChild(
            style
        );

    }


    /* ========================================================
       INITIALIZE
    ======================================================== */

    function initializeLocationChat() {

        addLocationStyles();


        console.log(
            "EJR Garage Location Chat loaded."
        );

    }


    /* ========================================================
       DOM READY
    ======================================================== */

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initializeLocationChat
        );

    }

    else {

        initializeLocationChat();

    }

})();
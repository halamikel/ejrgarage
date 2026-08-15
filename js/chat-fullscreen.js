/* ============================================================
   EJR GARAGE CHAT
   FULL SCREEN FUNCTIONALITY
   CUSTOMER + ADMIN
============================================================ */

(function () {

    "use strict";


    /* ========================================================
       GET CHAT BOX
    ======================================================== */

    function getChatBox() {

        return document.getElementById(
            "chatBox"
        );

    }


    /* ========================================================
       GET FULL SCREEN BUTTON
    ======================================================== */

    function getFullscreenButton() {

        return document.getElementById(
            "chatFullscreenButton"
        );

    }


    /* ========================================================
       UPDATE FULL SCREEN BUTTON ICON
    ======================================================== */

    function updateFullscreenButton(
        fullscreen
    ) {

        const button =
            getFullscreenButton();


        if (!button) {

            return;

        }


        const icon =
            button.querySelector(
                "i"
            );


        if (!icon) {

            return;

        }


        if (fullscreen) {

            icon.className =
                "bi bi-fullscreen-exit";


            button.title =
                "Exit Full Screen";


            button.setAttribute(
                "aria-label",
                "Exit Full Screen"
            );

        }

        else {

            icon.className =
                "bi bi-arrows-fullscreen";


            button.title =
                "Full Screen";


            button.setAttribute(
                "aria-label",
                "Full Screen"
            );

        }

    }


    /* ========================================================
       LOCK PAGE SCROLL
    ======================================================== */

    function setBodyScrollLock(
        locked
    ) {

        if (locked) {

            document.body.classList.add(
                "ejr-chat-fullscreen-active"
            );

        }

        else {

            document.body.classList.remove(
                "ejr-chat-fullscreen-active"
            );

        }

    }


    /* ========================================================
       SCROLL CUSTOMER CHAT
    ======================================================== */

    function scrollCustomerChat() {

        const chatBody =
            document.getElementById(
                "chatBody"
            );


        if (!chatBody) {

            return;

        }


        chatBody.scrollTop =
            chatBody.scrollHeight;

    }


    /* ========================================================
       SCROLL ADMIN CHAT
    ======================================================== */

    function scrollAdminChat() {

        const adminMessages =
            document.getElementById(
                "adminChatMessages"
            );


        if (!adminMessages) {

            return;

        }


        adminMessages.scrollTop =
            adminMessages.scrollHeight;

    }


    /* ========================================================
       KEEP CHAT AT BOTTOM
    ======================================================== */

    function scrollChatsToBottom() {

        setTimeout(
            function () {

                scrollCustomerChat();

                scrollAdminChat();

            },
            100
        );

    }


    /* ========================================================
       ENTER FULL SCREEN
    ======================================================== */

    function enterChatFullscreen() {

        const chatBox =
            getChatBox();


        if (!chatBox) {

            console.error(
                "EJR CHAT: #chatBox was not found."
            );

            return;

        }


        /* ====================================================
           ADD FULL SCREEN CLASS
        ==================================================== */

        chatBox.classList.add(
            "chat-fullscreen"
        );


        /* ====================================================
           LOCK BODY SCROLL
        ==================================================== */

        setBodyScrollLock(
            true
        );


        /* ====================================================
           UPDATE BUTTON
        ==================================================== */

        updateFullscreenButton(
            true
        );


        /* ====================================================
           SCROLL CHAT
        ==================================================== */

        scrollChatsToBottom();

    }


    /* ========================================================
       EXIT FULL SCREEN
    ======================================================== */

    function exitChatFullscreen() {

        const chatBox =
            getChatBox();


        if (!chatBox) {

            return;

        }


        /* ====================================================
           REMOVE FULL SCREEN CLASS
        ==================================================== */

        chatBox.classList.remove(
            "chat-fullscreen"
        );


        /* ====================================================
           UNLOCK BODY SCROLL
        ==================================================== */

        setBodyScrollLock(
            false
        );


        /* ====================================================
           UPDATE BUTTON
        ==================================================== */

        updateFullscreenButton(
            false
        );


        /* ====================================================
           SCROLL CHAT
        ==================================================== */

        scrollChatsToBottom();

    }


    /* ========================================================
       TOGGLE FULL SCREEN
    ======================================================== */

    window.toggleChatFullscreen =
        function () {

            const chatBox =
                getChatBox();


            if (!chatBox) {

                console.error(
                    "EJR CHAT: #chatBox was not found."
                );

                return;

            }


            const isFullscreen =
                chatBox.classList.contains(
                    "chat-fullscreen"
                );


            if (isFullscreen) {

                exitChatFullscreen();

            }

            else {

                enterChatFullscreen();

            }

        };


    /* ========================================================
       EXIT WITH ESCAPE
    ======================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            /* ================================================
               ONLY ESCAPE
            ================================================ */

            if (
                event.key !==
                "Escape"
            ) {

                return;

            }


            /* ================================================
               GET CHAT BOX
            ================================================ */

            const chatBox =
                getChatBox();


            if (!chatBox) {

                return;

            }


            /* ================================================
               CHECK FULL SCREEN
            ================================================ */

            if (
                chatBox.classList.contains(
                    "chat-fullscreen"
                )
            ) {

                exitChatFullscreen();

            }

        }
    );


    /* ========================================================
       CLEAN UP FULL SCREEN WHEN CHAT IS CLOSED
    ======================================================== */

    function monitorChatVisibility() {

        const chatBox =
            getChatBox();


        if (!chatBox) {

            return;

        }


        const observer =
            new MutationObserver(
                function () {

                    /*
                        If another part of the application
                        hides the chatbot, make sure full-screen
                        mode is properly reset.
                    */

                    const isHidden =
                        chatBox.style.display ===
                        "none";


                    if (
                        isHidden &&
                        chatBox.classList.contains(
                            "chat-fullscreen"
                        )
                    ) {

                        exitChatFullscreen();

                    }

                }
            );


        observer.observe(
            chatBox,
            {
                attributes: true,
                attributeFilter: [
                    "style"
                ]
            }
        );

    }


    /* ========================================================
       INITIALIZE
    ======================================================== */

    function initialize() {

        /*
            Make sure the page does not remain locked
            if the browser was refreshed while full-screen.
        */

        const chatBox =
            getChatBox();


        if (chatBox) {

            chatBox.classList.remove(
                "chat-fullscreen"
            );

        }


        setBodyScrollLock(
            false
        );


        updateFullscreenButton(
            false
        );


        monitorChatVisibility();


        console.log(
            "EJR Chat Full Screen initialized."
        );

    }


    /* ========================================================
       WAIT FOR DOM
    ======================================================== */

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initialize
        );

    }

    else {

        initialize();

    }

})();
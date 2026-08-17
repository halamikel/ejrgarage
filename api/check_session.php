// ============================================================
// EJR GARAGE - AUTHENTICATION HELPER
// File: js/auth.js
//
// Handles:
//  - JWT authentication
//  - Logged-in user information
//  - Admin protection
//  - Session checking
//  - Logout
//  - Authenticated API requests
//  - Session monitoring
// ============================================================


// ============================================================
// GET JWT TOKEN
// ============================================================

function getAuthToken() {

    try {

        return localStorage.getItem(
            'jwt_token'
        );

    }
    catch (error) {

        console.error(
            'Unable to read JWT token:',
            error
        );

        return null;
    }
}


// ============================================================
// GET AUTHORIZATION HEADERS
// ============================================================

function getAuthHeaders() {

    const headers = {

        'Accept':
            'application/json',

        'Content-Type':
            'application/json'

    };


    const token =
        getAuthToken();


    if (token) {

        headers[
            'Authorization'
        ] =
            `Bearer ${token}`;
    }


    return headers;
}


// ============================================================
// GET SAVED USER
// ============================================================

function getCurrentUser() {

    try {

        const savedUser =
            localStorage.getItem(
                'user'
            );


        if (!savedUser) {

            return null;
        }


        return JSON.parse(
            savedUser
        );

    }
    catch (error) {

        console.error(
            'Unable to read saved user:',
            error
        );

        return null;
    }
}


// ============================================================
// CHECK IF LOGGED IN
// ============================================================

function isLoggedIn() {

    const token =
        getAuthToken();


    return !!token;
}


// ============================================================
// CHECK IF ADMIN
// ============================================================

function isAdmin() {

    const user =
        getCurrentUser();


    if (!user) {

        return false;
    }


    return (

        Number(
            user.role_id
        ) === 2

        ||

        Number(
            user.id
        ) === 21

    );
}


// ============================================================
// REDIRECT TO LOGIN
// ============================================================

function redirectToLogin() {

    window.location.href =
        'account.php';
}


// ============================================================
// REDIRECT NON-ADMIN USERS
// ============================================================

function requireAdmin() {

    if (!isLoggedIn()) {

        redirectToLogin();

        return false;
    }


    if (!isAdmin()) {

        window.location.href =
            'account.php';

        return false;
    }


    return true;
}


// ============================================================
// CHECK SESSION WITH SERVER
//
// IMPORTANT:
// api/check_session.php may return:
//
//     { "valid": true }
//
// or:
//
//     { "status": "success" }
//
// or:
//
//     { "authenticated": true }
//
// We accept all three.
//
// A temporary server/network failure does NOT immediately
// destroy the user's JWT.
// ============================================================

async function checkAuthSession() {

    const token =
        getAuthToken();


    /*
     * No JWT means the user is definitely not logged in.
     */

    if (!token) {

        return false;
    }


    try {

        const response =
            await fetch(
                'api/check_session.php',
                {

                    method:
                        'GET',

                    credentials:
                        'include',

                    headers:
                        getAuthHeaders(),

                    cache:
                        'no-store'

                }
            );


        /*
         * ----------------------------------------------------
         * SERVER RESPONSE ERROR
         * ----------------------------------------------------
         *
         * A real 401/403 means authentication was rejected.
         *
         * Other server errors should not immediately destroy
         * the JWT because the server may simply be temporarily
         * unavailable.
         */

        if (!response.ok) {

            console.warn(
                'Session check returned HTTP status:',
                response.status
            );


            if (
                response.status === 401 ||
                response.status === 403
            ) {

                return false;
            }


            /*
             * Temporary server error.
             *
             * Keep the JWT.
             */

            return true;
        }


        /*
         * ----------------------------------------------------
         * READ JSON RESPONSE
         * ----------------------------------------------------
         */

        const data =
            await response.json();


        /*
         * ----------------------------------------------------
         * ACCEPT SUCCESS RESPONSE
         * ----------------------------------------------------
         */

        if (
            data.status ===
            'success'
        ) {

            return true;
        }


        /*
         * ----------------------------------------------------
         * ACCEPT AUTHENTICATED RESPONSE
         * ----------------------------------------------------
         */

        if (
            data.authenticated ===
            true
        ) {

            return true;
        }


        /*
         * ----------------------------------------------------
         * ACCEPT VALID RESPONSE
         *
         * This is the response currently returned by your
         * api/check_session.php.
         * ----------------------------------------------------
         */

        if (
            data.valid ===
            true
        ) {

            return true;
        }


        /*
         * Session endpoint explicitly says the token is not
         * valid.
         */

        return false;

    }
    catch (error) {

        console.error(
            'Session check failed:',
            error
        );


        /*
         * IMPORTANT:
         *
         * Do not log the user out just because fetch()
         * encountered a network/server error.
         *
         * The JWT is still available and the protected
         * API endpoints can validate it themselves.
         */

        return true;
    }
}


// ============================================================
// LOGOUT
// ============================================================

async function logoutUser() {

    try {

        await fetch(
            'api/logout.php',
            {

                method:
                    'POST',

                credentials:
                    'include',

                headers:
                    getAuthHeaders()

            }
        );

    }
    catch (error) {

        console.warn(
            'Logout request failed:',
            error
        );
    }


    /*
     * --------------------------------------------------------
     * CLEAR AUTHENTICATION DATA
     * --------------------------------------------------------
     */

    clearAuthData();


    /*
     * --------------------------------------------------------
     * REDIRECT TO LOGIN
     * --------------------------------------------------------
     */

    window.location.href =
        'account.php';
}


// ============================================================
// START SESSION WATCH
// ============================================================

let authSessionTimer =
    null;


function startSessionWatch() {

    stopSessionWatch();


    /*
     * Check every 60 seconds.
     */

    authSessionTimer =
        setInterval(
            async function () {

                const token =
                    getAuthToken();


                /*
                 * No token.
                 */

                if (!token) {

                    return;
                }


                const valid =
                    await checkAuthSession();


                /*
                 * Only log out when the server actually says
                 * the authentication is invalid.
                 */

                if (!valid) {

                    console.warn(
                        'Authentication session expired.'
                    );


                    stopSessionWatch();


                    clearAuthData();


                    window.location.href =
                        'account.php';
                }

            },

            60000
        );
}


// ============================================================
// STOP SESSION WATCH
// ============================================================

function stopSessionWatch() {

    if (
        authSessionTimer
    ) {

        clearInterval(
            authSessionTimer
        );


        authSessionTimer =
            null;
    }
}


// ============================================================
// REQUIRE LOGIN
// ============================================================

function requireLogin() {

    if (
        !isLoggedIn()
    ) {

        redirectToLogin();

        return false;
    }


    return true;
}


// ============================================================
// FETCH WITH AUTHENTICATION
// ============================================================

async function authFetch(
    url,
    options = {}
) {

    const token =
        getAuthToken();


    /*
     * Start with our normal authentication headers.
     */

    const headers = {

        ...getAuthHeaders(),

        ...(options.headers || {})

    };


    /*
     * Make absolutely sure the latest JWT is included.
     */

    if (token) {

        headers[
            'Authorization'
        ] =
            `Bearer ${token}`;
    }


    const response =
        await fetch(
            url,
            {

                ...options,

                credentials:
                    'include',

                headers:
                    headers

            }
        );


    /*
     * --------------------------------------------------------
     * HANDLE REAL AUTHENTICATION FAILURE
     * --------------------------------------------------------
     */

    if (
        response.status ===
        401
    ) {

        console.warn(
            'Authentication expired or rejected.'
        );


        /*
         * Clear the JWT because the server explicitly rejected
         * it.
         */

        clearAuthData();


        window.location.href =
            'account.php';


        throw new Error(
            'Authentication expired.'
        );
    }


    return response;
}


// ============================================================
// PARSE AUTH RESPONSE
// ============================================================

async function parseAuthResponse(
    response
) {

    let data;


    try {

        data =
            await response.json();

    }
    catch (error) {

        throw new Error(
            'Invalid server response.'
        );
    }


    /*
     * HTTP error
     */

    if (!response.ok) {

        throw new Error(
            data.message ||
            `HTTP ${response.status}`
        );
    }


    /*
     * API returned an explicit error status.
     */

    if (
        data.status &&
        data.status !==
            'success'
    ) {

        throw new Error(
            data.message ||
            'Authentication request failed.'
        );
    }


    return data;
}


// ============================================================
// SAVE LOGIN DATA
// ============================================================

function saveAuthData(
    token,
    user
) {

    try {

        /*
         * Save JWT.
         */

        if (token) {

            localStorage.setItem(
                'jwt_token',
                token
            );
        }


        /*
         * Save user information.
         */

        if (user) {

            localStorage.setItem(
                'user',
                JSON.stringify(
                    user
                )
            );


            /*
             * Keep admin_user synchronized for compatibility
             * with older EJR Garage pages.
             */

            if (
                Number(user.role_id) === 2 ||
                Number(user.id) === 21
            ) {

                localStorage.setItem(
                    'admin_user',
                    JSON.stringify(
                        user
                    )
                );
            }
        }

    }
    catch (error) {

        console.error(
            'Unable to save authentication data:',
            error
        );
    }
}


// ============================================================
// CLEAR LOGIN DATA
// ============================================================

function clearAuthData() {

    try {

        localStorage.removeItem(
            'jwt_token'
        );


        localStorage.removeItem(
            'user'
        );


        localStorage.removeItem(
            'admin_user'
        );

    }
    catch (error) {

        console.error(
            'Unable to clear authentication data:',
            error
        );
    }
}


// ============================================================
// PROTECT ADMIN PAGE
// ============================================================

async function protectAdminPage() {

    /*
     * --------------------------------------------------------
     * CHECK JWT
     * --------------------------------------------------------
     */

    const token =
        getAuthToken();


    if (!token) {

        redirectToLogin();

        return false;
    }


    /*
     * --------------------------------------------------------
     * CHECK SAVED USER
     * --------------------------------------------------------
     */

    const user =
        getCurrentUser();


    /*
     * If we have user information and it is not an admin,
     * redirect them.
     */

    if (
        user &&
        !isAdmin()
    ) {

        window.location.href =
            'account.php';

        return false;
    }


    /*
     * --------------------------------------------------------
     * VERIFY TOKEN WITH SERVER
     * --------------------------------------------------------
     */

    const sessionValid =
        await checkAuthSession();


    if (!sessionValid) {

        clearAuthData();

        redirectToLogin();

        return false;
    }


    return true;
}


// ============================================================
// INITIALIZE AUTHENTICATION
// ============================================================

async function initializeAuth() {

    const currentPage =
        window.location.pathname
            .split('/')
            .pop()
            .toLowerCase();


    /*
     * --------------------------------------------------------
     * ADMIN DASHBOARD PROTECTION
     * --------------------------------------------------------
     */

    if (
        currentPage ===
        'admin_dashboard.php'
    ) {

        const allowed =
            await protectAdminPage();


        if (!allowed) {

            return false;
        }
    }


    /*
     * --------------------------------------------------------
     * START SESSION MONITORING
     * --------------------------------------------------------
     */

    if (
        isLoggedIn()
    ) {

        startSessionWatch();
    }


    return true;
}


// ============================================================
// AUTO INITIALIZATION
// ============================================================

if (
    document.readyState ===
    'loading'
) {

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            initializeAuth();

        }
    );

}
else {

    initializeAuth();
}


// ============================================================
// GLOBAL AUTH OBJECT
// ============================================================

window.EJRAuth = {

    getToken:
        getAuthToken,

    getHeaders:
        getAuthHeaders,

    getUser:
        getCurrentUser,

    isLoggedIn:
        isLoggedIn,

    isAdmin:
        isAdmin,

    requireLogin:
        requireLogin,

    requireAdmin:
        requireAdmin,

    checkSession:
        checkAuthSession,

    logout:
        logoutUser,

    startWatch:
        startSessionWatch,

    stopWatch:
        stopSessionWatch,

    fetch:
        authFetch,

    save:
        saveAuthData,

    clear:
        clearAuthData

};


// ============================================================
// AUTH.JS END
// ============================================================
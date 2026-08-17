// ============================================================
// EJR GARAGE - AUTHENTICATION HELPER
// File: js/auth.js
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
// ============================================================

async function checkAuthSession() {

    const token =
        getAuthToken();


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
                        getAuthHeaders()

                }
            );


        if (!response.ok) {

            return false;

        }


        const data =
            await response.json();


        if (
            data.status ===
            'success'
        ) {

            return true;

        }


        if (
            data.authenticated ===
            true
        ) {

            return true;

        }


        return false;

    }
    catch (error) {

        console.error(
            'Session check failed:',
            error
        );


        return false;

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


    // Clear authentication data.

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

        console.warn(
            'Unable to clear local storage:',
            error
        );

    }


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


    // Check every 60 seconds.

    authSessionTimer =
        setInterval(
            async function () {

                const token =
                    getAuthToken();


                if (!token) {

                    return;

                }


                const valid =
                    await checkAuthSession();


                if (!valid) {

                    console.warn(
                        'Authentication session expired.'
                    );


                    stopSessionWatch();


                    try {

                        localStorage.removeItem(
                            'jwt_token'
                        );

                    }
                    catch (error) {

                        console.warn(
                            error
                        );

                    }


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


    const headers = {

        ...getAuthHeaders(),

        ...(options.headers || {})

    };


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


    // Automatically handle expired JWT.

    if (
        response.status ===
        401
    ) {

        console.warn(
            'Authentication expired.'
        );


        try {

            localStorage.removeItem(
                'jwt_token'
            );

        }
        catch (error) {

            console.warn(
                error
            );

        }


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


    if (!response.ok) {

        throw new Error(
            data.message ||
            `HTTP ${response.status}`
        );

    }


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

        if (token) {

            localStorage.setItem(
                'jwt_token',
                token
            );

        }


        if (user) {

            localStorage.setItem(
                'user',
                JSON.stringify(
                    user
                )
            );

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

    const token =
        getAuthToken();


    if (!token) {

        redirectToLogin();

        return false;

    }


    const user =
        getCurrentUser();


    if (
        user &&
        !isAdmin()
    ) {

        window.location.href =
            'account.php';

        return false;

    }


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


    // Admin dashboard protection.

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


    // Start session monitoring.

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
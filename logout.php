<?php

session_start();

// Prevent the browser from caching this response
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Invalidate the old session ID before destroying
session_regenerate_id(true);

// Clear the session data
$_SESSION = [];

// Expire the session cookie in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the server-side session
session_destroy();

header("Location: login.php");
exit;
<?php
// Sliding session lifetime: refresh the cookie on each request
if (isset($_SESSION["user_id"])) {

    $cookieLifetime = 60 * 60 * 24 * 30; // 30 days

    setcookie(
        session_name(),
        session_id(),
        [
            'expires'  => time() + $cookieLifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION["user_id"]);
}

function isAdmin()
{
    return isLoggedIn()
        && isset($_SESSION["role"])
        && $_SESSION["role"] === "admin";
}

function isUser()
{
    return isLoggedIn()
        && isset($_SESSION["role"])
        && $_SESSION["role"] === "user";
}
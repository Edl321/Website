<?php

// NOTE: session has already been started by harden.php via config.php.
// This file only reads $_SESSION and defines helper functions.

// Extend session cookie lifetime to 30 days for logged-in users.
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
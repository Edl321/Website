<?php

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
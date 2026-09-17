<?php

// NOTE: session has already been started by harden.php via config.php.
// This file only manages CSRF tokens.

if (empty($_SESSION["csrf_token"])) {

    $_SESSION["csrf_token"] = bin2hex(
        random_bytes(32)
    );

}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars($_SESSION["csrf_token"]) .
        '">';
}

function verify_csrf_token()
{
    if (
        empty($_POST["csrf_token"]) ||
        empty($_SESSION["csrf_token"]) ||
        !hash_equals(
            $_SESSION["csrf_token"],
            $_POST["csrf_token"]
        )
    ) {
        return false;
    }

    return true;
}
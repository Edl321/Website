<?php

session_start();

require_once "database/config.php";
require_once "security/shield.php";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!verify_csrf_token()) {

        $errors[] = "Invalid security token. Please try again.";

    }

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email)) {

        $errors[] = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";

    }

    if (isset($_SESSION["login_attempts"]) && $_SESSION["login_attempts"] >= 5) {

    if (time() - $_SESSION["last_attempt"] < 300) {

        $errors[] = "Too many failed attempts. Please wait 5 minutes.";

    } else {
        $_SESSION["login_attempts"] = 0;
    }

    }

    if (empty($password)) {

        $errors[] = "Password is required.";

    }

    if (empty($errors)) {

        $sql = "SELECT id, first_name, last_name, email, password, role
                FROM users
                WHERE email = :email";

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(":email", $email);

        $stmt->execute();

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {

            // Prevent session fixation
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];

            $_SESSION["first_name"] = $user["first_name"];

            $_SESSION["last_name"] = $user["last_name"];

            $_SESSION["email"] = $user["email"];

            $_SESSION["role"] = $user["role"];
            
            $_SESSION["login_attempts"] = ($_SESSION["login_attempts"] ?? 0) + 1;

            $_SESSION["last_attempt"]   = time();

            unset($_SESSION["login_attempts"], $_SESSION["last_attempt"]);

            session_regenerate_id(true);
            
            // Extend the session cookie so it survives browser restarts
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

            if ($user["role"] === "admin") {

                header("Location: admin/admin-dashboard.php");

            } else {

                header("Location: dashboard.php");

            }

            exit;


        } else {

            $errors[] = "Invalid email or password.";

        }
    }
}

header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | EDL Gallery</title>
    <link rel="icon" type="image/x-icon" href="Images/logo.png">
    <link rel="stylesheet" type="text/css"href="style.css">
    <link rel="stylesheet" type="text/css" href="login.css">

</head>


<body>


<section class="login-page">

    <div class="login-container">

        <div class="login-header">

            <img src="Images/logo.png" alt="EDL Gallery Logo">
            <h1> Welcome Back </h1>
            <p> Sign in to your EDL Gallery account. </p>

        </div>


        <?php if (!empty($errors)): ?>

            <div class="login-errors">

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?php echo htmlspecialchars($error); ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

        <form
            class="login-form"
            method="POST"
            action="login.php"
        >

            <?php echo csrf_field(); ?>


            <div class="login-form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>"
                    required
                >

            </div>

            <div class="login-form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>

            <button type="submit" class="login-button"> LOGIN </button>

        </form>

        <p class="login-register">
            Don't have an account?
            <a href="register.php"> Create an account </a>
        </p>

        <a href="index.php" class="back-home"> ← Back to EDL Gallery</a>

    </div>


</section>


</body>

</html>
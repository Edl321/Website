<?php

session_start();

require_once "database/config.php";
require_once "security/shield.php";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ==========================================
    // CHECK CSRF TOKEN
    // ==========================================

    if (!verify_csrf_token()) {

        $errors[] = "Invalid security token. Please try again.";

    }


    // ==========================================
    // GET FORM VALUES
    // ==========================================

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    // ==========================================
    // VALIDATE EMAIL
    // ==========================================

    if (empty($email)) {

        $errors[] = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";

    }


    // ==========================================
    // VALIDATE PASSWORD
    // ==========================================

    if (empty($password)) {

        $errors[] = "Password is required.";

    }


    // ==========================================
    // LOGIN
    // ==========================================

    if (empty($errors)) {

        $sql = "SELECT id, first_name, last_name, email, password, role
                FROM users
                WHERE email = :email";

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(":email", $email);

        $stmt->execute();

        $user = $stmt->fetch();


        // ==========================================
        // CHECK EMAIL AND PASSWORD
        // ==========================================

        if ($user && password_verify($password, $user["password"])) {

            // Prevent session fixation
            session_regenerate_id(true);


            // ==========================================
            // STORE USER INFORMATION
            // ==========================================

            $_SESSION["user_id"] = $user["id"];

            $_SESSION["first_name"] = $user["first_name"];

            $_SESSION["last_name"] = $user["last_name"];

            $_SESSION["email"] = $user["email"];

            $_SESSION["role"] = $user["role"];


            // ==========================================
            // REDIRECT BASED ON ROLE
            // ==========================================

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

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login | EDL Gallery</title>


    <!-- FAVICON -->

    <link
        rel="icon"
        type="image/x-icon"
        href="Images/logo.png"
    >


    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        type="text/css"
        href="style.css"
    >


    <!-- LOGIN CSS -->

    <link
        rel="stylesheet"
        type="text/css"
        href="login.css"
    >

</head>


<body>


<section class="login-page">


    <div class="login-container">


        <!-- ==========================================
             LOGIN HEADER
        =========================================== -->

        <div class="login-header">

            <img
                src="Images/logo.png"
                alt="EDL Gallery Logo"
            >

            <h1>
                Welcome Back
            </h1>

            <p>
                Sign in to your EDL Gallery account.
            </p>

        </div>


        <!-- ==========================================
             ERROR MESSAGES
        =========================================== -->

        <?php if (!empty($errors)): ?>

            <div class="login-errors">

                <?php foreach ($errors as $error): ?>

                    <p>
                        <?php echo htmlspecialchars($error); ?>
                    </p>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- ==========================================
             LOGIN FORM
        =========================================== -->

        <form
            class="login-form"
            method="POST"
            action="login.php"
        >


            <!-- CSRF SECURITY TOKEN -->

            <?php echo csrf_field(); ?>


            <!-- ======================================
                 EMAIL
            ======================================= -->

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


            <!-- ======================================
                 PASSWORD
            ======================================= -->

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


            <!-- ======================================
                 LOGIN BUTTON
            ======================================= -->

            <button
                type="submit"
                class="login-button"
            >
                LOGIN
            </button>


        </form>


        <!-- ==========================================
             REGISTER LINK
        =========================================== -->

        <p class="login-register">

            Don't have an account?

            <a href="register.php">
                Create an account
            </a>

        </p>

        <a
            href="index.php"
            class="back-home"
        >
            ← Back to EDL Gallery
        </a>


    </div>


</section>


</body>

</html>
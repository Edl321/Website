<?php

session_start();
require_once "database/config.php";
require_once "security/shield.php";

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Check CSRF token
    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token. Please try again.";
    }

    // Get form values
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";


    // Validate email
    if (empty($email)) {

        $errors[] = "Email is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] = "Please enter a valid email address.";

    }


    // Validate password
    if (empty($password)) {

        $errors[] = "Password is required.";

    }


    // Continue only if there are no errors
    if (empty($errors)) {

        $sql = "SELECT id, first_name, last_name, email, password
                FROM users
                WHERE email = :email";

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(":email", $email);

        $stmt->execute();

        $user = $stmt->fetch();


        // Check account and password
        if ($user && password_verify($password, $user["password"])) {

            // Prevent session fixation
            session_regenerate_id(true);


            // Store user information in session
            $_SESSION["user_id"] = $user["id"];

            $_SESSION["first_name"] = $user["first_name"];

            $_SESSION["last_name"] = $user["last_name"];

            $_SESSION["email"] = $user["email"];


            // Send user to dashboard
            header("Location:dashboard.php");

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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | EDL Gallery</title>

</head>


<body>


    <h1>Login</h1>


    <!-- ERROR MESSAGES -->

    <?php if (!empty($errors)): ?>

        <div>

            <?php foreach ($errors as $error): ?>

                <p>
                    <?php echo htmlspecialchars($error); ?>
                </p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>


    <!-- LOGIN FORM -->

    <form method="POST" action="login.php">


        <!-- CSRF SECURITY TOKEN -->

        <?php echo csrf_field(); ?>


        <!-- EMAIL -->

        <label for="email">
            Email
        </label>

        <input
            type="email"
            id="email"
            name="email"
            required
        >


        <br><br>


        <!-- PASSWORD -->

        <label for="password">
            Password
        </label>

        <input
            type="password"
            id="password"
            name="password"
            required
        >


        <br><br>


        <!-- LOGIN BUTTON -->

        <button type="submit">
            Login
        </button>


    </form>


    <!-- REGISTER LINK -->

    <p>

        Don't have an account?

        <a href="register.php">
            Create an account
        </a>

    </p>


</body>

</html>